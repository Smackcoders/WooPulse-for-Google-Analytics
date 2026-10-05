/**
 * Pulse Analytics for WordPress — Free plugin asset.
 *
 * @license GPL-2.0-or-later
 * @link    https://www.gnu.org/licenses/gpl-2.0.html
 */
/**
 * WooPulse Frontend Tracking Script
 * Evaluates custom conversion goals and triggers GA4 events.
 */
(function () {
	var pulseAjax = (typeof pulseAnalyticsAjax !== 'undefined') ? pulseAnalyticsAjax : {};

    window.addEventListener('load', function () {
        const goalsPayload = typeof SmPulseAnalyticsGoals !== 'undefined'
            ? SmPulseAnalyticsGoals
            : (typeof woopulseGoals !== 'undefined' ? woopulseGoals : null);

        if (!goalsPayload || !Array.isArray(goalsPayload.goals)) {
            return;
        }

        const goals = goalsPayload.goals;
        const currentUrl = window.location.href;

        /**
         * Safe gtag wrapper
         */
        function fireGtagEvent(eventName, params = {}) {
            if (typeof gtag === 'function') {
                console.log('WooPulse: Triggering Goal ->', eventName, params);
                gtag('event', eventName, params);
            } else {
                console.warn('WooPulse: gtag not found. Goal trigger skipped:', eventName);
            }
        }

        /**
         * Core Logic: Evaluate Triggers
         */
        goals.forEach(goal => {
            if (!goal.active || !goal.trigger) return;

            // 1. Page View Trigger
            if (goal.type === 'page_view') {
                if (currentUrl.includes(goal.trigger)) {
                    fireGtagEvent(goal.event_name, {
                        method: 'woopulse_custom_goal',
                        type: 'page_view',
                        trigger: goal.trigger
                    });
                }
            }

            // 2. Click Trigger
            if (goal.type === 'click') {
                document.addEventListener('click', function (e) {
                    const target = e.target.closest(goal.trigger);
                    if (target) {
                        fireGtagEvent(goal.event_name, {
                            method: 'woopulse_custom_goal',
                            type: 'click',
                            trigger: goal.trigger,
                            element_text: target.innerText.substring(0, 50)
                        });
                    }
                }, true);
            }

            // 3. Form Submission Trigger
            if (goal.type === 'form_submit') {
                document.addEventListener('submit', function (e) {
                    const target = e.target.closest(goal.trigger);
                    if (target) {
                        fireGtagEvent(goal.event_name, {
                            method: 'woopulse_custom_goal',
                            type: 'form_submit',
                            trigger: goal.trigger
                        });
                    }
                }, true);
            }
        });
    });

    // Link Click Tracking
    (function() {
        const restBase = (!!pulseAjax && Object.keys(pulseAjax).length && pulseAjax.rest_url)
            ? pulseAjax.rest_url.replace(/\/$/, '')
            : (typeof woopulseRestUrl !== 'undefined' ? woopulseRestUrl : '/wp-json/pulse-analytics/v1');

        const defaultExts = ['pdf','zip','doc','docx','xls','xlsx','ppt','pptx','csv','txt','jpg','jpeg','png','gif','mp4','mp3','avi','mov','epub'];
        const downloadEnabled = (!pulseAjax || !Object.keys(pulseAjax).length || pulseAjax.enable_download_tracking !== false);
        const downloadExts = (!!pulseAjax && Object.keys(pulseAjax).length && Array.isArray(pulseAjax.download_extensions) && pulseAjax.download_extensions.length)
            ? pulseAjax.download_extensions
            : defaultExts;
        const affiliatePaths = (!!pulseAjax && Object.keys(pulseAjax).length && Array.isArray(pulseAjax.affiliate_paths) && pulseAjax.affiliate_paths.length)
            ? pulseAjax.affiliate_paths
            : ['/go/', '/recommend/', '/affiliate/'];

        function getSessionId() {
            const cookieMatch = document.cookie.match(/(?:sm_pulse_analytics_sid|woopulse_sid)=([^;]+)/);
            if (cookieMatch && cookieMatch[1]) {
                return cookieMatch[1];
            }
            if (typeof SmPulseAnalyticsSession !== 'undefined' && SmPulseAnalyticsSession.session_id) {
                return SmPulseAnalyticsSession.session_id;
            }
            return '';
        }

        function isDownloadableHref(href) {
            if (!downloadEnabled || !href) {
                return false;
            }
            const clean = href.split('?')[0].split('#')[0].toLowerCase();
            for (let i = 0; i < downloadExts.length; i++) {
                const ext = String(downloadExts[i] || '').toLowerCase().replace(/^\./, '');
                if (ext && clean.endsWith('.' + ext)) {
                    return true;
                }
            }
            return false;
        }

        function matchesAffiliatePath(href, pathname) {
            const candidates = [href, pathname || ''];
            for (let i = 0; i < affiliatePaths.length; i++) {
                const path = String(affiliatePaths[i] || '').trim();
                if (!path) {
                    continue;
                }
                for (let j = 0; j < candidates.length; j++) {
                    if (candidates[j] && candidates[j].indexOf(path) !== -1) {
                        return true;
                    }
                }
            }
            return false;
        }

        document.addEventListener('click', function(e) {
            const link = e.target.closest('a');
            if (!link) return;

            const href = link.getAttribute('href');
            if (!href || href === '#' || href.startsWith('javascript:')) return;

            try {
                const currentDomain = window.location.hostname;
                let linkUrl = href;
                let linkDomain = currentDomain;
                let pathname = '';
                let linkType = null;

                try {
                    const url = new URL(href, window.location.origin);
                    linkUrl = url.href;
                    linkDomain = url.hostname;
                    pathname = url.pathname || '';
                } catch (err) {
                    // Relative URL, keep as is
                }

                // Downloads first (same-site or external file links).
                if (isDownloadableHref(href) || isDownloadableHref(linkUrl) || isDownloadableHref(pathname)) {
                    linkType = 'downloadable';
                } else if (link.hasAttribute('data-affiliate') || href.includes('?affiliate=') || matchesAffiliatePath(href, pathname)) {
                    linkType = 'affiliate';
                } else if (linkDomain !== currentDomain && linkDomain !== '') {
                    linkType = 'outbound';
                }

                if (linkType) {
                    // When Affiliate Link Tracking is active, it owns affiliate/outbound clicks.
                    var proAffiliate = (typeof SmPulseAnalyticsAffiliateLinks !== 'undefined' && SmPulseAnalyticsAffiliateLinks && SmPulseAnalyticsAffiliateLinks.enabled)
                        || (!!pulseAjax && Object.keys(pulseAjax).length && pulseAjax.affiliate_pro_tracking)
                        || window.PulseAffiliateTrackerActive;
                    if (proAffiliate && (linkType === 'outbound' || linkType === 'affiliate')) {
                        return;
                    }

                    const sessionId = getSessionId();
                    fetch(restBase + '/link-click', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            link_type: linkType,
                            link_url: linkUrl,
                            page_url: window.location.href,
                            session_id: sessionId
                        })
                    }).catch(err => console.error('Failed to track link click:', err));
                }
            } catch (err) {
                console.error('Error tracking link:', err);
            }
        }, true);
    })();
})();
