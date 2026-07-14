/**
 * Pulse Analytics Frontend Tracking Script
 * Evaluates custom conversion goals and triggers GA4 events.
 */
(function () {
    window.addEventListener('load', function () {
        if (typeof StorePulseGoals === 'undefined' || !Array.isArray(StorePulseGoals.goals)) {
            return;
        }

        const goals = StorePulseGoals.goals;
        const currentUrl = window.location.href;

        /**
         * Safe gtag wrapper
         */
        function fireGtagEvent(eventName, params = {}) {
            if (typeof gtag === 'function') {
                console.log('Pulse Analytics: Triggering Goal ->', eventName, params);
                gtag('event', eventName, params);
            } else {
                console.warn('Pulse Analytics: gtag not found. Goal trigger skipped:', eventName);
            }
        }

        /**
         * Core Logic: Evaluate Triggers
         */
        const badSelectors = new Set();

        goals.forEach(goal => {
            if (!goal.active || !goal.trigger) return;

            // 1. Page View Trigger
            if (goal.type === 'page_view') {
                if (currentUrl.includes(goal.trigger)) {
                    fireGtagEvent(goal.event_name, {
                        method: 'StorePulse_custom_goal',
                        type: 'page_view',
                        trigger: goal.trigger
                    });
                }
            }

            // 2. Click Trigger
            if (goal.type === 'click') {
                document.addEventListener('click', function (e) {
                    if (badSelectors.has(goal.trigger)) return;
                    try {
                        const target = e.target.closest(goal.trigger);
                        if (target) {
                            fireGtagEvent(goal.event_name, {
                                method: 'StorePulse_custom_goal',
                                type: 'click',
                                trigger: goal.trigger,
                                element_text: target.innerText.substring(0, 50)
                            });
                        }
                    } catch (error) {
                        badSelectors.add(goal.trigger);
                        console.warn(`Pulse Analytics: Invalid CSS selector for click goal "${goal.label || goal.event_name}": "${goal.trigger}". Skipping this goal.`);
                    }
                }, true);
            }

            // 3. Form Submission Trigger
            if (goal.type === 'form_submit') {
                document.addEventListener('submit', function (e) {
                    if (badSelectors.has(goal.trigger)) return;
                    try {
                        const target = e.target.closest(goal.trigger);
                        if (target) {
                            fireGtagEvent(goal.event_name, {
                                method: 'StorePulse_custom_goal',
                                type: 'form_submit',
                                trigger: goal.trigger
                            });
                        }
                    } catch (error) {
                        badSelectors.add(goal.trigger);
                        console.warn(`Pulse Analytics: Invalid CSS selector for form_submit goal "${goal.label || goal.event_name}": "${goal.trigger}". Skipping this goal.`);
                    }
                }, true);
            }
        });
    });

    // Link Click Tracking
    (function () {
        // Use StorePulseRestUrl or seoInsightsAjax or default
        const restBase = (typeof StorePulseRestUrl !== 'undefined'
            ? StorePulseRestUrl
            : (typeof seoInsightsAjax !== 'undefined' && seoInsightsAjax.rest_url
                ? seoInsightsAjax.rest_url
                : '/wp-json/StorePulse/v1'));

        // Session ID from cookie – ok if empty, we still track the click
        const sessionId = (document.cookie.match(/StorePulse_sid=([^;]+)/) || [])[1] || '';

        document.addEventListener('click', function (e) {
            const link = e.target.closest('a');
            if (!link) return;

            const href = link.getAttribute('href');
            if (!href || href === '#' || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) return;

            try {
                const currentDomain = window.location.hostname;
                let linkUrl = href;
                let linkDomain = currentDomain;
                let linkType = null;

                try {
                    const url = new URL(href, window.location.origin);
                    linkUrl = url.href;
                    linkDomain = url.hostname;
                } catch (err) {
                    // Relative URL, keep as-is
                }

                // Check if downloadable (first priority)
                if (/\.(pdf|zip|doc|docx|xls|xlsx|ppt|pptx|csv|txt|jpg|jpeg|png|gif|mp4|mp3|avi|mov|exe|dmg)$/i.test(href)) {
                    linkType = 'downloadable';
                }
                // Check if affiliate
                else if (
                    link.hasAttribute('data-affiliate') ||
                    link.rel && link.rel.includes('sponsored') ||
                    href.includes('/affiliate/') ||
                    href.includes('?affiliate=') ||
                    href.includes('ref=') ||
                    href.includes('aff=') ||
                    href.includes('/go/') ||
                    href.includes('/recommend/')
                ) {
                    linkType = 'affiliate';
                }
                // Check if outbound (external site)
                else if (linkDomain !== currentDomain && linkDomain !== '' && linkDomain !== 'localhost') {
                    linkType = 'outbound';
                }

                if (linkType) {
                    const nonce = typeof seoInsightsAjax !== 'undefined' ? seoInsightsAjax.nonce : '';
                    fetch(restBase + '/link-click', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-WP-Nonce': nonce
                        },
                        body: JSON.stringify({
                            link_type: linkType,
                            link_url: linkUrl,
                            page_url: window.location.href,
                            referrer: document.referrer || '',
                            session_id: sessionId
                        })
                    }).catch(function () { });
                }
            } catch (err) {
                // Silent fail
            }
        }, true);
    })();
})();
