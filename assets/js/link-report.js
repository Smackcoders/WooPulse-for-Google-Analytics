const pulseAjax = (typeof pulseAnalyticsAjax !== 'undefined') ? pulseAnalyticsAjax : {};
/**
 * Pulse Analytics for WordPress — Free plugin asset.
 * Audience & Links page — GA4 + local telemetry; sample only when GA unavailable.
 * Date range driven by the global header #link-date-range (flatpickr).
 *
 * @license GPL-2.0-or-later
 * @link    https://www.gnu.org/licenses/gpl-2.0.html
 */
/* global Chart, flatpickr, wpApiSettings, PulseAnalyticsVars, pulseAnalyticsAjax */
document.addEventListener( 'DOMContentLoaded', function () {

	function __(text) {
		return (window.wp && window.wp.i18n && window.wp.i18n.__) ? window.wp.i18n.__(text, 'smackcoders-pulse-analytics-for-woocommerce') : text;
	}

	// ── Config ──────────────────────────────────────────────────────────
	function resolveApiBase() {
		if ( window.PulseAnalyticsVars && PulseAnalyticsVars.rest_url ) {
			return String( PulseAnalyticsVars.rest_url ).replace( /\/$/, '' );
		}
		if ( pulseAjax && pulseAjax.rest_url ) {
			return String( pulseAjax.rest_url ).replace( /\/$/, '' ).replace( /\/pulse-analytics\/v1$/, '' );
		}
		if ( window.wpApiSettings && wpApiSettings.root ) {
			return String( wpApiSettings.root ).replace( /\/$/, '' );
		}
		return '/wp-json';
	}

	function resolveNonce() {
		if ( window.PulseAnalyticsVars && PulseAnalyticsVars.nonce ) {
			return PulseAnalyticsVars.nonce;
		}
		if ( pulseAjax && pulseAjax.nonce ) {
			return pulseAjax.nonce;
		}
		if ( window.wpApiSettings && wpApiSettings.nonce ) {
			return wpApiSettings.nonce;
		}
		return '';
	}

	const API_BASE = resolveApiBase();
	const NONCE    = resolveNonce();
	const REST_NS  = ( API_BASE.indexOf( '/pulse-analytics/v1' ) !== -1 )
		? ''
		: '/pulse-analytics/v1';
	const REST_URL = API_BASE + REST_NS + '/reports/link-clicks/free';
	const DEMO_URL = API_BASE + REST_NS + '/reports/demographics';

	// Sample rows — only when source === unavailable / fetch error.
	const DEMO = {
		outbound: [
			{ link: 'https://analytics.google.com', clicks: 210 },
			{ link: 'https://search.google.com/search-console', clicks: 165 },
			{ link: 'https://woocommerce.com/document/google-analytics', clicks: 115 },
			{ link: 'https://developers.google.com/analytics', clicks: 78 },
		],
		affiliate: [
			{ link: 'https://example.com/ref/partner-a', clicks: 320 },
			{ link: 'https://example.com/ref/partner-b', clicks: 245 },
			{ link: 'https://example.com/ref/partner-c', clicks: 190 },
			{ link: 'https://example.com/ref/partner-d', clicks: 115 },
		],
		downloads: [
			{ link: 'https://example.com/downloads/setup-guide.pdf', clicks: 480 },
			{ link: 'https://example.com/downloads/tracking-guide.pdf', clicks: 360 },
			{ link: 'https://example.com/downloads/campaign-builder.csv', clicks: 290 },
			{ link: 'https://example.com/downloads/web-vitals.pdf', clicks: 175 },
		],
		inbound: [
			{ link: '/', clicks: 185 },
			{ link: '/blog/', clicks: 142 },
			{ link: '/shop/', clicks: 98 },
			{ link: '/docs/', clicks: 64 },
		],
	};

	let ageChartInstance    = null;
	let genderChartInstance = null;

	// ── Subtab Switching ────────────────────────────────────────────────
	const tabBtns = document.querySelectorAll( '.links-subtab' );
	const panels  = document.querySelectorAll( '.links-tab-content' );

	tabBtns.forEach( function ( btn ) {
		btn.addEventListener( 'click', function () {
			const targetId = this.getAttribute( 'data-target' );
			if ( ! targetId ) { return; }
			tabBtns.forEach( function ( b ) { b.classList.remove( 'active' ); } );
			this.classList.add( 'active' );
			panels.forEach( function ( p ) { p.classList.add( 'hidden' ); } );
			const panel = document.getElementById( targetId );
			if ( panel ) { panel.classList.remove( 'hidden' ); }
			if ( targetId === 'links-tab-demo' ) {
				setTimeout( function () {
					if ( ageChartInstance ) { ageChartInstance.resize(); }
					if ( genderChartInstance ) { genderChartInstance.resize(); }
				}, 50 );
			}
		} );
	} );

	// ── Helpers ─────────────────────────────────────────────────────────
	function emptyRow( message, cols ) {
		return '<tr><td colspan="' + cols + '" class="text-center text-slate-400 italic py-8">' + message + '</td></tr>';
	}

	function renderLinkTable( bodyId, rows, colCount ) {
		const tbody = document.getElementById( bodyId );
		if ( ! tbody ) { return; }
		if ( ! rows || ! rows.length ) {
			tbody.innerHTML = emptyRow( __('No data found for this period.'), colCount || 2 );
			return;
		}
		tbody.innerHTML = rows.map( function ( item ) {
			return '<tr>'
				+ '<td class="font-mono text-xs break-all sp-td-max-380">' + escHtml( item.link ) + '</td>'
				+ '<td class="text-right font-semibold">' + escHtml( String( item.clicks ) ) + '</td>'
				+ '</tr>';
		} ).join( '' );
	}

	function escHtml( str ) {
		return String( str )
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' )
			.replace( /"/g, '&quot;' );
	}

	function showNotice( id ) {
		const el = document.getElementById( id );
		if ( el ) { el.classList.remove( 'hidden' ); }
	}

	function hideNotice( id ) {
		const el = document.getElementById( id );
		if ( el ) { el.classList.add( 'hidden' ); }
	}

	function sumValues( values ) {
		if ( ! values || ! values.length ) { return 0; }
		return values.reduce( function ( a, b ) { return a + Number( b || 0 ); }, 0 );
	}

	// ── Render Demographics Charts ───────────────────────────────────────
	function renderDemographicsCharts( data, isUnavailable ) {
		const ageData   = data && data.age   ? data.age   : null;
		const ageEl     = document.getElementById( 'age-chart' );
		const ageNoData = document.getElementById( 'age-no-data' );
		const ageSum    = ageData ? sumValues( ageData.values ) : 0;

		if ( ageEl && typeof Chart !== 'undefined' ) {
			if ( ! ageData || ! ageData.labels || ageSum <= 0 ) {
				if ( ageNoData ) { ageNoData.classList.remove( 'hidden' ); }
				ageEl.style.display = 'none';
				if ( ageChartInstance ) { ageChartInstance.destroy(); ageChartInstance = null; }
			} else {
				if ( ageNoData ) { ageNoData.classList.add( 'hidden' ); }
				ageEl.style.display = '';
				if ( ageChartInstance ) { ageChartInstance.destroy(); }
				ageChartInstance = new Chart( ageEl.getContext( '2d' ), {
					type: 'bar',
					data: {
						labels: ageData.labels,
						datasets: [ {
							label: __('Sessions'),
							data: ageData.values,
							backgroundColor: '#6366f1',
							borderRadius: 6,
						} ],
					},
					options: {
						responsive: true,
						maintainAspectRatio: false,
						plugins: { legend: { display: false } },
						scales: {
							x: { grid: { display: false }, ticks: { font: { size: 11 }, color: '#94a3b8' } },
							y: { grid: { color: '#f1f5f9' }, ticks: { font: { size: 10 }, color: '#94a3b8' } },
						},
					},
				} );
			}
		}

		const genderData   = data && data.gender ? data.gender : null;
		const genderEl     = document.getElementById( 'gender-chart' );
		const genderNoData = document.getElementById( 'gender-no-data' );
		const genderSum    = genderData ? sumValues( genderData.values ) : 0;

		if ( genderEl && typeof Chart !== 'undefined' ) {
			if ( ! genderData || ! genderData.labels || genderSum <= 0 ) {
				if ( genderNoData ) { genderNoData.classList.remove( 'hidden' ); }
				genderEl.style.display = 'none';
				if ( genderChartInstance ) { genderChartInstance.destroy(); genderChartInstance = null; }
			} else {
				if ( genderNoData ) { genderNoData.classList.add( 'hidden' ); }
				genderEl.style.display = '';
				if ( genderChartInstance ) { genderChartInstance.destroy(); }
				genderChartInstance = new Chart( genderEl.getContext( '2d' ), {
					type: 'doughnut',
					data: {
						labels: genderData.labels,
						datasets: [ {
							data: genderData.values,
							backgroundColor: [ '#3b82f6', '#ec4899', '#cbd5e1' ],
							borderWidth: 2,
							borderColor: '#ffffff',
						} ],
					},
					options: {
						responsive: true,
						maintainAspectRatio: false,
						cutout: '70%',
						plugins: { legend: { position: 'bottom' } },
					},
				} );
			}
		}

		if ( isUnavailable || ( ageSum <= 0 && genderSum <= 0 ) ) {
			showNotice( 'link-demo-sample-notice' );
		} else {
			hideNotice( 'link-demo-sample-notice' );
		}
	}

	function renderEmptyDemographics() {
		renderDemographicsCharts( {
			age: { labels: [ '18-24', '25-34', '35-44', '45-54', '55-64', '65+' ], values: [ 0, 0, 0, 0, 0, 0 ] },
			gender: { labels: [ __('Female'), __('Male'), __('Unknown') ], values: [ 0, 0, 0 ] },
		}, true );
	}

	// ── Fetch GA4 Demographics ───────────────────────────────────────────
	function fetchAndRenderDemographics( startDate, endDate ) {
		const ga4DemoUrl = DEMO_URL
			+ '?startDate=' + encodeURIComponent( startDate )
			+ '&endDate=' + encodeURIComponent( endDate );

		fetch( ga4DemoUrl, {
			headers: { 'X-WP-Nonce': NONCE },
			credentials: 'same-origin',
		} )
			.then( function ( r ) {
				if ( ! r.ok ) { throw new Error( 'not_available' ); }
				return r.json();
			} )
			.then( function ( json ) {
				const ageSum    = ( json && json.age && json.age.values ) ? sumValues( json.age.values ) : 0;
				const genderSum = ( json && json.gender && json.gender.values ) ? sumValues( json.gender.values ) : 0;
				if ( ageSum > 0 || genderSum > 0 ) {
					renderDemographicsCharts( json, false );
				} else {
					renderEmptyDemographics();
				}
			} )
			.catch( function () {
				renderEmptyDemographics();
			} );
	}

	// ── Fetch Real Link-Click Data ───────────────────────────────────────
	function fetchAndRenderLinks( startDate, endDate ) {
		[ 'inbound-links-body', 'outbound-links-body', 'affiliate-links-body', 'downloadable-links-body' ].forEach( function ( id ) {
			const el = document.getElementById( id );
			if ( el ) { el.innerHTML = emptyRow( __('Loading…'), 2 ); }
		} );
		hideNotice( 'link-outbound-demo-notice' );
		hideNotice( 'link-downloads-demo-notice' );

		const url = REST_URL
			+ '?startDate=' + encodeURIComponent( startDate )
			+ '&endDate=' + encodeURIComponent( endDate );

		fetch( url, {
			headers: { 'X-WP-Nonce': NONCE },
			credentials: 'same-origin',
		} )
			.then( function ( r ) {
				if ( ! r.ok ) { throw new Error( 'http_' + r.status ); }
				return r.json();
			} )
			.then( function ( json ) {
				const unavailable = ! json || json.source === 'unavailable';

				if ( unavailable ) {
					showNotice( 'link-outbound-demo-notice' );
					showNotice( 'link-downloads-demo-notice' );
					renderLinkTable( 'inbound-links-body', DEMO.inbound );
					renderLinkTable( 'outbound-links-body', DEMO.outbound );
					renderLinkTable( 'affiliate-links-body', DEMO.affiliate );
					renderLinkTable( 'downloadable-links-body', DEMO.downloads );
					return;
				}

				renderLinkTable( 'inbound-links-body', json.inbound || [] );
				renderLinkTable( 'outbound-links-body', json.outbound || [] );
				renderLinkTable( 'affiliate-links-body', json.affiliate || [] );
				renderLinkTable( 'downloadable-links-body', json.downloads || [] );
			} )
			.catch( function () {
				showNotice( 'link-outbound-demo-notice' );
				showNotice( 'link-downloads-demo-notice' );
				renderLinkTable( 'inbound-links-body', DEMO.inbound );
				renderLinkTable( 'outbound-links-body', DEMO.outbound );
				renderLinkTable( 'affiliate-links-body', DEMO.affiliate );
				renderLinkTable( 'downloadable-links-body', DEMO.downloads );
			} );
	}

	// ── Date range — header #traffic-date-range-select (script.js) ───────
	function formatDateLocal( d ) {
		if ( ! d || ! ( d instanceof Date ) || isNaN( d.getTime() ) ) { return ''; }
		const y  = d.getFullYear();
		const m  = String( d.getMonth() + 1 ).padStart( 2, '0' );
		const dy = String( d.getDate() ).padStart( 2, '0' );
		return y + '-' + m + '-' + dy;
	}

	function rangeForDays( days ) {
		const end   = new Date();
		const start = new Date();
		start.setDate( start.getDate() - days );
		return { startDate: formatDateLocal( start ), endDate: formatDateLocal( end ) };
	}

	function resolveInitialRange() {
		const selectEl = document.getElementById( 'traffic-date-range-select' );
		const val = selectEl ? selectEl.value : '30daysAgo';

		if ( val === '7daysAgo' ) {
			return rangeForDays( 7 );
		}
		if ( val === '90daysAgo' ) {
			return rangeForDays( 90 );
		}
		if ( val === 'custom' ) {
			try {
				const stored = sessionStorage.getItem( 'sm_pulse_analytics_calendar' );
				if ( stored ) {
					const parsed = JSON.parse( stored );
					if ( parsed && parsed.start && parsed.end && /^\d{4}-\d{2}-\d{2}$/.test( parsed.start ) ) {
						return { startDate: parsed.start, endDate: parsed.end };
					}
				}
			} catch ( e ) {}
		}
		// Default / Last 30 Days — match the selected header option.
		return rangeForDays( 30 );
	}

	let currentStartDate = resolveInitialRange().startDate;
	let currentEndDate   = resolveInitialRange().endDate;

	document.addEventListener( 'pulse-analytics:date-range-changed', function ( evt ) {
		if ( evt && evt.detail && evt.detail.startDate && evt.detail.endDate ) {
			if ( evt.detail.startDate !== currentStartDate || evt.detail.endDate !== currentEndDate ) {
				currentStartDate = evt.detail.startDate;
				currentEndDate   = evt.detail.endDate;
				loadAll();
			}
		}
	} );

	function loadAll() {
		fetchAndRenderLinks( currentStartDate, currentEndDate );
		fetchAndRenderDemographics( currentStartDate, currentEndDate );
	}

	loadAll();
} );
