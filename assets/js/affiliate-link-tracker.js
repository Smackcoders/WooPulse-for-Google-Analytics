/**
 * @license GPL-2.0-or-later
 * @link    https://www.gnu.org/licenses/gpl-2.0.html
 * Pulse Analytics Affiliate & Outbound Link Tracker (#39).
 */
(function () {
	'use strict';

	var cfg = typeof SmPulseAnalyticsAffiliateLinks !== 'undefined' ? SmPulseAnalyticsAffiliateLinks : null;
	if (!cfg || !cfg.enabled) {
		return;
	}

	window.PulseAffiliateTrackerActive = true;

	var recent = {};
	var DEDUP_MS = 800;

	function readCookie(name) {
		try {
			var match = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '=([^;]*)'));
			return match ? decodeURIComponent(match[1]) : '';
		} catch (e) {
			return '';
		}
	}

	function getSessionId() {
		var cookieMatch = document.cookie.match(/(?:sm_pulse_analytics_sid|woopulse_sid)=([^;]+)/);
		if (cookieMatch && cookieMatch[1]) {
			return cookieMatch[1];
		}
		if (typeof SmPulseAnalyticsSession !== 'undefined' && SmPulseAnalyticsSession.session_id) {
			return SmPulseAnalyticsSession.session_id;
		}
		return '';
	}

	function analyticsAllowed() {
		if (!cfg.respect_consent) {
			return true;
		}
		if (typeof SmPulseAnalyticsPrivacy !== 'undefined' && typeof SmPulseAnalyticsPrivacy.isAnalyticsAllowed === 'function') {
			return !!SmPulseAnalyticsPrivacy.isAnalyticsAllowed();
		}
		return true;
	}

	function normalizeDomain(domain) {
		if (!domain) {
			return '';
		}
		domain = (domain + '').trim().toLowerCase();
		if (domain.indexOf('://') === -1 && domain.indexOf('//') !== 0) {
			domain = 'http://' + domain;
		}
		try {
			var parsed = new URL(domain);
			domain = parsed.hostname || domain;
		} catch (e) {
			domain = domain.replace(/^https?:\/\//i, '').replace(/[\/\?#].*$/, '');
		}
		return domain.replace(/^www\./i, '');
	}

	function hostMatches(host, needle) {
		if (!host || !needle) {
			return false;
		}
		host = normalizeDomain(host);
		needle = normalizeDomain(needle);
		if (!host || !needle) {
			return false;
		}
		return host === needle || host.slice(-needle.length - 1) === '.' + needle;
	}

	function classify(href) {
		if (!href || /^(mailto:|tel:|javascript:|#)/i.test(href)) {
			return 'ignored';
		}

		var path = href;
		var host = '';
		try {
			var parsed = new URL(href, window.location.origin);
			path = (parsed.pathname || href).toLowerCase();
			host = (parsed.hostname || '').toLowerCase();
		} catch (err) {
			path = href.toLowerCase();
		}

		var siteHost = normalizeDomain(cfg.site_host || window.location.hostname || '');
		var normHost = normalizeDomain(host);
		var i;

		for (i = 0; i < (cfg.excluded_prefixes || []).length; i++) {
			var exPrefix = (cfg.excluded_prefixes[i] || '').toLowerCase();
			if (exPrefix && path.indexOf(exPrefix) === 0) {
				return 'ignored';
			}
		}

		if (!normHost || normHost === siteHost) {
			for (i = 0; i < (cfg.affiliate_prefixes || []).length; i++) {
				var affPrefix = (cfg.affiliate_prefixes[i] || '').toLowerCase();
				if (affPrefix && path.indexOf(affPrefix) !== -1) {
					return 'affiliate';
				}
			}
			return 'internal';
		}

		for (i = 0; i < (cfg.excluded_domains || []).length; i++) {
			if (hostMatches(normHost, cfg.excluded_domains[i])) {
				return 'ignored';
			}
		}

		for (i = 0; i < (cfg.affiliate_domains || []).length; i++) {
			if (hostMatches(normHost, cfg.affiliate_domains[i])) {
				return 'affiliate';
			}
		}

		for (i = 0; i < (cfg.affiliate_prefixes || []).length; i++) {
			if (href.toLowerCase().indexOf((cfg.affiliate_prefixes[i] || '').toLowerCase()) !== -1) {
				return 'affiliate';
			}
		}

		if (/ref=|affiliate|aff_id|partner=|tag=/i.test(href)) {
			return 'affiliate';
		}

		return 'outbound';
	}

	function partnerForDomain(domain) {
		var norm = normalizeDomain(domain);
		var map = cfg.partner_map || [];
		for (var i = 0; i < map.length; i++) {
			if (normalizeDomain(map[i].domain) === norm) {
				return map[i].name || domain;
			}
		}
		return domain;
	}

	function shouldIgnoreElement(link) {
		var selectors = cfg.ignored_selectors || [];
		for (var i = 0; i < selectors.length; i++) {
			if (selectors[i] && link.closest(selectors[i])) {
				return true;
			}
		}
		if (link.hasAttribute('data-no-pulse-tracking') || link.classList.contains('ignore-pulse-tracking')) {
			return true;
		}
		return false;
	}

	function fireGtag(eventName, params) {
		if (typeof gtag === 'function') {
			gtag('event', eventName, params);
		}
	}

	function sendRest(linkType, linkUrl) {
		var url = cfg.rest_url || '/wp-json/sm-pulse-analytics/v1/link-click';
		try {
			fetch(url, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify({
					link_type: linkType,
					link_url: linkUrl,
					page_url: cfg.page_url || window.location.href,
					session_id: getSessionId()
				}),
				keepalive: true
			}).catch(function () { });
		} catch (e) { }
	}

	function trackClick(link, href) {
		if (!analyticsAllowed()) {
			return;
		}

		var now = Date.now();
		if (link._pulseLastClick && (now - link._pulseLastClick) < DEDUP_MS) {
			return;
		}

		var linkUrl = href;
		var domain = '';
		try {
			var parsed = new URL(href, window.location.origin);
			linkUrl = parsed.href;
			domain = parsed.hostname || '';
		} catch (err) { }

		var type = classify(linkUrl);
		if (type === 'ignored' || type === 'internal') {
			return;
		}
		if (type === 'affiliate' && !cfg.track_affiliate) {
			return;
		}
		if (type === 'outbound' && !cfg.track_outbound) {
			return;
		}

		var key = type + '|' + linkUrl;
		if (recent[key] && (now - recent[key]) < DEDUP_MS) {
			return;
		}
		recent[key] = now;
		link._pulseLastClick = now;

		var eventName = type === 'affiliate' ? 'affiliate_link_click' : 'outbound_link_click';
		var params = {
			link_url: linkUrl,
			linkUrl: linkUrl,
			link_type: type,
			link_domain: domain,
			partner_name: partnerForDomain(domain),
			page_url: cfg.page_url || window.location.href,
			page_path: cfg.page_path || window.location.pathname,
			page_title: cfg.page_title || document.title,
			link_text: (link.innerText || '').substring(0, 80)
		};

		fireGtag(eventName, params);
		sendRest(type, linkUrl);
	}

	document.addEventListener('click', function (e) {
		var link = e.target.closest('a[href]');
		if (!link || shouldIgnoreElement(link)) {
			return;
		}
		var href = link.getAttribute('href');
		if (!href) {
			return;
		}
		trackClick(link, href);
	}, true);
})();
