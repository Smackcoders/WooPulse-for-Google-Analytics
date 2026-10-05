/**
 * Pulse Analytics - First-Party User Journey & Session Heartbeat Tracker
 */
(function() {
	'use strict';

	if (typeof window.SmPulseAnalyticsSession === 'undefined') {
		return;
	}

	var config = window.SmPulseAnalyticsSession;
	var restUrl = config.rest_url ? config.rest_url.replace(/\/$/, '') : '';
	var nonce = config.nonce || '';
	var sessionId = config.session_id || '';
	var activeSeconds = 0;
	var isTabActive = true;
	var lastActiveTime = Date.now();

	// Visitor ID generator / reader (persistent 1-year cookie)
	function getVisitorId() {
		var vid = getCookie('sm_pulse_analytics_vid');
		if (!vid) {
			vid = 'vid_' + Math.random().toString(36).substring(2, 15) + Date.now().toString(36);
			setCookie('sm_pulse_analytics_vid', vid, 365);
		}
		return vid;
	}

	function getCookie(name) {
		var matches = document.cookie.match(new RegExp(
			'(?:^|; )' + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + '=([^;]*)'
		));
		return matches ? decodeURIComponent(matches[1]) : undefined;
	}

	function setCookie(name, value, days) {
		var expires = '';
		if (days) {
			var date = new Date();
			date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
			expires = '; expires=' + date.toUTCString();
		}
		document.cookie = name + '=' + (value || '') + expires + '; path=/; SameSite=Lax';
	}

	var visitorId = getVisitorId();

	// Activity tracker
	function onUserActivity() {
		lastActiveTime = Date.now();
	}

	['mousemove', 'keydown', 'scroll', 'click', 'touchstart'].forEach(function(evt) {
		window.addEventListener(evt, onUserActivity, { passive: true });
	});

	// Interval timer counting active seconds
	setInterval(function() {
		if (isTabActive && (Date.now() - lastActiveTime < 30000)) {
			activeSeconds += 1;
		}
	}, 1000);

	// Page visibility change handler
	document.addEventListener('visibilitychange', function() {
		if (document.hidden) {
			isTabActive = false;
			sendEngagementBeacon();
		} else {
			isTabActive = true;
			lastActiveTime = Date.now();
		}
	});

	window.addEventListener('pagehide', sendEngagementBeacon);
	window.addEventListener('beforeunload', sendEngagementBeacon);

	function sendEngagementBeacon() {
		if (!restUrl || activeSeconds <= 0) {
			return;
		}

		var payload = JSON.stringify({
			session_id: sessionId,
			visitor_id: visitorId,
			page_path: window.location.pathname,
			page_title: document.title,
			duration_seconds: activeSeconds,
			timestamp: new Date().toISOString()
		});

		var endpoint = restUrl + '/page-engagement';

		if (navigator.sendBeacon) {
			var blob = new Blob([payload], { type: 'application/json' });
			navigator.sendBeacon(endpoint, blob);
		} else if (window.fetch) {
			fetch(endpoint, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': nonce
				},
				body: payload,
				keepalive: true
			}).catch(function() {});
		}
	}

	// Heartbeat interval to keep session active
	var heartbeatInterval = (config.heartbeat_interval || 30) * 1000;
	setInterval(function() {
		if (!restUrl || !isTabActive) {
			return;
		}
		if (window.fetch) {
			fetch(restUrl + '/session-ping', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': nonce
				},
				body: JSON.stringify({
					session_id: sessionId,
					visitor_id: visitorId,
					page_path: window.location.pathname,
					active_seconds: activeSeconds
				})
			}).catch(function() {});
		}
	}, heartbeatInterval);

})();
