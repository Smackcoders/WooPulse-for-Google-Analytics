/**
 * StorePulse Session Heartbeat & Session-End Tracking
 *
 * - Sends a heartbeat every 25 s while the page is visible.
 * - Tracks page-visit journey steps.
 * - Persists session details in sessionStorage to support multi-page tracking.
 */
(function () {
    'use strict';

    if ( typeof StorePulseSession === 'undefined' ) {
        return;
    }

    var cfg           = StorePulseSession;
    var restBase      = cfg.rest_url;
    var nonce         = cfg.nonce;
    
    var sessionId = cfg.session_id || '';
    if ( sessionId ) {
        sessionStorage.setItem('StorePulse_sid', sessionId);
    } else {
        sessionId = sessionStorage.getItem('StorePulse_sid') || '';
    }

    // Retrieve or initialize session start time in sessionStorage
    var sessionStart = sessionStorage.getItem('StorePulse_session_start');
    if ( ! sessionStart ) {
        sessionStart = Math.floor( Date.now() / 1000 ).toString();
        sessionStorage.setItem('StorePulse_session_start', sessionStart);
    }
    sessionStart = parseInt(sessionStart, 10);

    var heartbeatMs            = ( cfg.heartbeat_interval || 25 ) * 1000;
    var hbTimer                = null;
    var isInternalNavigation   = false;

    /* ───────────── helpers ───────────── */

    function postJSON( endpoint, data, keepalive, callback ) {
        var url  = restBase + endpoint;
        var body = JSON.stringify( data );

        if ( navigator.sendBeacon && keepalive ) {
            var blob = new Blob( [ body ], { type: 'application/json' } );
            navigator.sendBeacon( url, blob );
            return;
        }

        fetch( url, {
            method      : 'POST',
            headers     : { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
            body        : body,
            keepalive   : !! keepalive,
            credentials : 'same-origin'
        } )
        .then( function ( response ) {
            return response.json();
        } )
        .then( function ( resData ) {
            if ( typeof callback === 'function' ) {
                callback( resData );
            }
        } )
        .catch( function () {} );
    }

    function sendHeartbeat() {
        if ( document.hidden || ! sessionId ) { return; }
        postJSON( '/session/heartbeat', {
            session_id : sessionId,
            user_id    : cfg.user_id || 0,
            page_url   : window.location.href,
            timestamp  : Math.floor( Date.now() / 1000 )
        }, false, function ( data ) {
            if ( data && data.new_session_id ) {
                sessionId = data.new_session_id;
                sessionStorage.setItem('StorePulse_sid', sessionId);
            }
        } );
    }

    function startHeartbeat() {
        if ( hbTimer ) { return; }
        sendHeartbeat();
        hbTimer = setInterval( function () {
            if ( ! document.hidden ) { sendHeartbeat(); }
        }, heartbeatMs );
    }

    function stopHeartbeat() {
        if ( hbTimer ) {
            clearInterval( hbTimer );
            hbTimer = null;
        }
    }

    /* ───────────── events ───────────── */

    // Detect internal link clicks to prevent ending session on internal navigation
    document.addEventListener( 'click', function ( event ) {
        var target = event.target.closest( 'a' );
        if ( target && target.href ) {
            try {
                var url = new URL( target.href );
                if ( url.origin === window.location.origin ) {
                    isInternalNavigation = true;
                }
            } catch ( e ) {}
        }
    }, true );

    // Detect internal form submissions
    document.addEventListener( 'submit', function ( event ) {
        var target = event.target;
        if ( target && target.action ) {
            try {
                var url = new URL( target.action );
                if ( url.origin === window.location.origin ) {
                    isInternalNavigation = true;
                }
            } catch ( e ) {}
        }
    }, true );

    document.addEventListener( 'visibilitychange', function () {
        if ( document.hidden ) {
            stopHeartbeat();
        } else {
            startHeartbeat();
        }
    } );

    window.addEventListener( 'pagehide', function () {
        // No explicit session end from frontend
    } );

    /* kick off */
    if ( document.readyState === 'loading' ) {
        document.addEventListener( 'DOMContentLoaded', startHeartbeat );
    } else {
        startHeartbeat();
    }

}() );