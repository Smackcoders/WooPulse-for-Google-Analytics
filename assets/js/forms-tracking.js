/**
 * Pulse Analytics for WordPress — Free plugin asset.
 *
 * @license GPL-2.0-or-later
 * @link    https://www.gnu.org/licenses/gpl-2.0.html
 */
/**
 * Pulse Analytics — Forms Conversion Tracking Engine (#30).
 * Detects supported form providers and tracks view/start/submit exclusively via GA4 gtag.
 * No local WordPress telemetry / REST form-event writes.
 */
(function () {
	'use strict';

	var cfg = typeof SmPulseAnalyticsForms !== 'undefined' ? SmPulseAnalyticsForms : null;
	if (!cfg || !cfg.enabled) {
		return;
	}

	var enabledProviders = Array.isArray(cfg.providers) ? cfg.providers : [];
	var excludedForms = Array.isArray(cfg.excluded_forms) ? cfg.excluded_forms : [];
	var pageMeta = {
		page_id: cfg.page_id || 0,
		page_url: window.location.href,
		page_title: cfg.page_title || document.title,
		post_type: cfg.post_type || ''
	};

	var KNOWN_FORM_SELECTORS = [
		'form.wpcf7-form',
		'form.wpforms-form',
		'.gform_wrapper form',
		'form[id^="gform_"]',
		'form.frm-show-form',
		'form.ninja-forms-form',
		'form.forminator-custom-form',
		'.forminator-ui form',
		'form.elementor-form',
		'.et_pb_contact_form form',
		'form.et_pb_contact_form',
		'form#commentform',
		'form.woocommerce-checkout',
		'form.checkout'
	].join(', ');

	var PROVIDERS = [
		{
			id: 'contact-form-7',
			selector: 'form.wpcf7-form',
			getId: function (form) { return form.getAttribute('data-wpcf7-id') || form.id || 'cf7'; },
			getName: function (form) { return form.getAttribute('data-wpcf7-id') ? 'Contact Form ' + form.getAttribute('data-wpcf7-id') : 'Contact Form 7'; },
			formType: 'contact'
		},
		{
			id: 'wpforms',
			selector: 'form.wpforms-form',
			getId: function (form) { return form.getAttribute('data-formid') || form.id || 'wpforms'; },
			getName: function (form) { return form.getAttribute('aria-label') || 'WPForm ' + (form.getAttribute('data-formid') || ''); },
			formType: 'lead'
		},
		{
			id: 'gravityforms',
			selector: '.gform_wrapper form, form[id^="gform_"]',
			getId: function (form) {
				var wrapper = form.closest('.gform_wrapper');
				if (wrapper && wrapper.id) {
					return wrapper.id.replace('gform_wrapper_', '');
				}
				return (form.id || '').replace('gform_', '') || 'gf';
			},
			getName: function (form) {
				var wrapper = form.closest('.gform_wrapper');
				var title = wrapper ? wrapper.querySelector('.gform_title') : null;
				return title ? title.textContent.trim() : 'Gravity Form';
			},
			formType: 'lead'
		},
		{
			id: 'formidable',
			selector: 'form.frm-show-form',
			getId: function (form) {
				var field = form.querySelector('[name="form_id"]');
				return field ? field.value : (form.id || 'frm');
			},
			getName: function (form) {
				return form.getAttribute('data-formid') ? 'Formidable ' + form.getAttribute('data-formid') : 'Formidable Form';
			},
			formType: 'lead'
		},
		{
			id: 'ninja-forms',
			selector: 'form.ninja-forms-form',
			getId: function (form) { return form.getAttribute('data-form-id') || form.id || 'nf'; },
			getName: function (form) { return 'Ninja Form ' + (form.getAttribute('data-form-id') || ''); },
			formType: 'lead'
		},
		{
			id: 'forminator',
			selector: 'form.forminator-custom-form, .forminator-ui form, form[data-forminator-render]',
			getId: function (form) {
				return form.getAttribute('data-form-id') || form.getAttribute('data-id') || form.id || 'forminator';
			},
			getName: function (form) {
				var titleEl = form.querySelector ? form.querySelector('.forminator-title, h3, h2') : null;
				var titleText = form.getAttribute('data-form-title') || (titleEl ? titleEl.textContent.trim() : '');
				return titleText || ('Forminator Form ' + (form.getAttribute('data-form-id') || ''));
			},
			formType: 'lead'
		},
		{
			id: 'elementor',
			selector: 'form.elementor-form',
			getId: function (form) { return form.getAttribute('data-form-id') || form.id || 'elementor'; },
			getName: function (form) { return form.getAttribute('name') || 'Elementor Form'; },
			formType: 'lead'
		},
		{
			id: 'divi',
			selector: '.et_pb_contact_form form, form.et_pb_contact_form',
			getId: function (form) { return form.id || 'divi-' + simpleHash(form.action || window.location.pathname); },
			getName: function () { return 'Divi Contact Form'; },
			formType: 'lead'
		},
		{
			id: 'woocommerce',
			selector: 'form.woocommerce-checkout, form.checkout',
			getId: function (form) { return form.getAttribute('name') || form.id || 'checkout'; },
			getName: function () { return 'WooCommerce Checkout'; },
			formType: 'checkout'
		},
		{
			id: 'comment',
			selector: 'form#commentform',
			getId: function () { return 'comment-' + (pageMeta.page_id || 'post'); },
			getName: function () { return 'WordPress Comments'; },
			formType: 'comment'
		},
		{
			id: 'generic',
			selector: 'form',
			getId: function (form) {
				if (form.id) return form.id;
				if (form.name) return form.name;
				return 'generic-' + simpleHash(form.action || window.location.pathname);
			},
			getName: function (form) { return form.getAttribute('aria-label') || form.id || form.name || 'HTML Form'; },
			formType: 'lead'
		}
	];

	function simpleHash(str) {
		var h = 0;
		for (var i = 0; i < str.length; i++) {
			h = ((h << 5) - h) + str.charCodeAt(i);
			h |= 0;
		}
		return Math.abs(h).toString(36);
	}

	function getSessionId() {
		var cookieMatch = document.cookie.match(/(?:sm_pulse_analytics_sid|woopulse_sid)=([^;]+)/);
		if (cookieMatch && cookieMatch[1]) return cookieMatch[1];
		if (typeof SmPulseAnalyticsSession !== 'undefined' && SmPulseAnalyticsSession.session_id) {
			return SmPulseAnalyticsSession.session_id;
		}
		return '';
	}

	var pageViewsTracked = {};
	var pageViewsObserved = {};
	var pageStartsTracked = {};
	var pageSubmitsTracked = {};
	var lastSubmitTimeMap = {};

	function isFormSubmitted(formKey) {
		return !!pageSubmitsTracked[formKey];
	}

	function markFormSubmitted(formKey) {
		pageSubmitsTracked[formKey] = true;
		pageViewsTracked[formKey] = true;
		pageStartsTracked[formKey] = true;
	}

	function isSubmissionLocked(formKey) {
		var now = Date.now();
		var last = lastSubmitTimeMap[formKey] || 0;
		return (now - last) < 6000;
	}

	function lockSubmission(formKey) {
		lastSubmitTimeMap[formKey] = Date.now();
	}

	function fireGtag(eventName, params) {
		params = params || {};
		params.transport = 'beacon';
		if (typeof gtag === 'function') {
			gtag('event', eventName, params);
		} else if (window.dataLayer && typeof window.dataLayer.push === 'function') {
			window.dataLayer.push(Object.assign({ event: eventName }, params));
		}
	}

	function extractFormDetails(form) {
		if (!form || !form.querySelectorAll) {
			return {
				form_action: '',
				form_id_attr: '',
				form_class: '',
				fields_count: 0,
				form_fields: ''
			};
		}

		var action = form.getAttribute('action') || form.action || '';
		var idAttr = form.id || '';
		var classAttr = form.className || '';
		var interactiveFields = Array.prototype.slice.call(
			form.querySelectorAll('input:not([type="hidden"]):not([type="submit"]):not([type="button"]):not([type="reset"]):not([type="image"]), select, textarea')
		);

		var fieldNames = [];
		for (var i = 0; i < interactiveFields.length; i++) {
			var field = interactiveFields[i];
			var identifier = field.getAttribute('name') || field.id || field.getAttribute('placeholder') || field.type;
			if (identifier && fieldNames.indexOf(identifier) === -1) {
				fieldNames.push(identifier);
			}
		}

		return {
			form_action: String(action),
			form_id_attr: String(idAttr),
			form_class: String(classAttr),
			fields_count: interactiveFields.length,
			form_fields: fieldNames.slice(0, 15).join(', ')
		};
	}

	/**
	 * Dispatch form analytics exclusively to GA4 (no local DB / REST telemetry).
	 */
	function trackEvent(eventName, meta, bypassDedupe, bypassLock) {
		var formKey = meta.form_provider + ':' + meta.form_id;
		if (excludedForms.indexOf(formKey) !== -1) {
			return;
		}

		// Normalize event name to standard GA4 form events
		var gaEventName = eventName;
		if (eventName === 'pulse_form_view' || eventName === 'form_view') {
			gaEventName = 'form_view';
		} else if (eventName === 'pulse_form_start' || eventName === 'form_start') {
			gaEventName = 'form_start';
		} else if (eventName === 'pulse_form_submit' || eventName === 'form_submit') {
			gaEventName = 'form_submit';
		}

		// Suppress any post-submission view/start events for this form on current page
		if (pageSubmitsTracked[formKey] && gaEventName !== 'form_submit') {
			return;
		}

		// Page-level event deduplication & submission suppression
		if (gaEventName === 'form_view') {
			if (pageViewsTracked[formKey] || pageSubmitsTracked[formKey]) {
				return;
			}
			pageViewsTracked[formKey] = true;
		} else if (gaEventName === 'form_start') {
			if (pageStartsTracked[formKey] || pageSubmitsTracked[formKey]) {
				return;
			}
			pageStartsTracked[formKey] = true;
		} else if (gaEventName === 'form_submit') {
			if (!bypassLock && isSubmissionLocked(formKey)) {
				return;
			}
			lockSubmission(formKey);
			markFormSubmitted(formKey);
		}

		// Standard GA4 Enhanced Measurement payload schema
		var params = {
			form_id: String(meta.form_id || ''),
			form_name: String(meta.form_name || meta.form_id || ''),
			form_destination: String(meta.form_action || window.location.href),
			form_length: typeof meta.fields_count === 'number' ? meta.fields_count : 0,
			form_provider: String(meta.form_provider || 'generic'),
			page_location: window.location.href,
			page_title: pageMeta.page_title
		};

		if (cfg && cfg.debug_mode) {
			params.debug_mode = true;
		}

		fireGtag(gaEventName, params);
	}

	function resolveProvider(form) {
		for (var i = 0; i < PROVIDERS.length; i++) {
			var p = PROVIDERS[i];
			if (p.id === 'generic') continue;
			try {
				if (form.matches(p.selector)) {
					if (enabledProviders.length > 0 && enabledProviders.indexOf(p.id) === -1) {
						enabledProviders.push(p.id);
					}
					return p;
				}
			} catch (e) { /* invalid selector */ }
		}
		if (!enabledProviders.length || enabledProviders.indexOf('generic') !== -1) {
			return PROVIDERS[PROVIDERS.length - 1];
		}
		return null;
	}

	function getFormMeta(form) {
		var provider = resolveProvider(form);
		if (!provider) return null;

		if (provider.id === 'generic') {
			try {
				if (form.matches(KNOWN_FORM_SELECTORS)) {
					return null;
				}
			} catch (e) {
				return null;
			}
		}

		var formId = String(provider.getId(form) || '');
		var formKey = provider.id + ':' + formId;
		if (excludedForms.indexOf(formKey) !== -1) return null;

		var details = extractFormDetails(form);

		return {
			form_id: formId,
			form_name: provider.getName(form),
			form_provider: provider.id,
			form_type: provider.formType || (provider.id === 'comment' ? 'comment' : 'lead'),
			form_key: formKey,
			form_action: details.form_action,
			form_id_attr: details.form_id_attr,
			form_class: details.form_class,
			fields_count: details.fields_count,
			form_fields: details.form_fields
		};
	}

	function observeViews(form, meta) {
		if (!cfg.track_views) return;
		if (pageViewsObserved[meta.form_key] || pageViewsTracked[meta.form_key] || isFormSubmitted(meta.form_key)) return;

		// Guards against attaching a second observer. pageViewsTracked is only
		// written by trackEvent once the event actually fires.
		pageViewsObserved[meta.form_key] = true;

		if ('IntersectionObserver' in window) {
			var observer = new IntersectionObserver(function (entries) {
				entries.forEach(function (entry) {
					if (entry.isIntersecting && entry.intersectionRatio >= 0.25) {
						observer.disconnect();
						trackEvent('form_view', meta);
					}
				});
			}, { threshold: [0.25] });
			observer.observe(form);
		} else {
			trackEvent('form_view', meta);
		}
	}

	function bindStarts(form, meta) {
		if (!cfg.track_starts) return;
		if (pageStartsTracked[meta.form_key] || isFormSubmitted(meta.form_key)) return;

		var started = false;
		function handleStart() {
			if (started || pageStartsTracked[meta.form_key] || isFormSubmitted(meta.form_key)) return;
			started = true;
			if (!pageViewsTracked[meta.form_key]) {
				trackEvent('form_view', meta);
			}
			trackEvent('form_start', meta, true);
		}
		form.addEventListener('focus', handleStart, true);
		form.addEventListener('focusin', handleStart, true);
		form.addEventListener('input', handleStart, true);
		form.addEventListener('change', handleStart, true);
	}

	var AJAX_PROVIDERS = ['contact-form-7', 'wpforms', 'gravityforms', 'formidable', 'ninja-forms', 'forminator', 'woocommerce'];

	function bindGenericSubmit(form, meta) {
		if (meta.form_provider === 'woocommerce') {
			return;
		}
		form.addEventListener('submit', function () {
			submitMeta(meta, { submission_status: 'success' });
		}, true);
	}

	function submitMeta(meta, overrides, bypassLock) {
		trackEvent('form_submit', Object.assign({}, meta, { submission_status: 'success' }, overrides || {}), true, bypassLock);
	}

	function findMetaByProviderId(providerId, formId) {
		var id = String(formId || '');
		var forms = document.querySelectorAll('form');
		for (var i = 0; i < forms.length; i++) {
			var meta = getFormMeta(forms[i]);
			if (!meta) continue;
			if (meta.form_provider === providerId && (!id || meta.form_id === id)) {
				return meta;
			}
		}
		return {
			form_id: id || providerId,
			form_name: providerId,
			form_provider: providerId,
			form_type: providerId === 'woocommerce' ? 'checkout' : 'lead'
		};
	}

	function bindGlobalAjaxSuccess() {
		if (!cfg.track_submissions) return;

		document.addEventListener('wpcf7mailsent', function (e) {
			var id = e.detail && e.detail.contactFormId ? String(e.detail.contactFormId) : '';
			if (enabledProviders.indexOf('contact-form-7') === -1) return;
			submitMeta(findMetaByProviderId('contact-form-7', id), {}, true);
		});

		document.addEventListener('wpformsFormSubmitSuccess', function (e) {
			if (enabledProviders.indexOf('wpforms') === -1) return;
			var detail = e.detail || {};
			submitMeta(findMetaByProviderId('wpforms', detail.formId || detail.id), {}, true);
		});

		if (!window.jQuery) {
			return;
		}

		var $ = window.jQuery;

		$(document).on('gform_confirmation_loaded', function (_e, formId) {
			if (enabledProviders.indexOf('gravityforms') === -1) return;
			submitMeta(findMetaByProviderId('gravityforms', formId), {}, true);
		});

		$(document).on('frmFormComplete', function (_event, form) {
			if (enabledProviders.indexOf('formidable') === -1) return;
			var formId = '';
			if (form && form.getAttribute) {
				var field = form.querySelector ? form.querySelector('[name="form_id"]') : null;
				formId = field ? field.value : (form.id || '');
			} else if (form && form.id) {
				formId = form.id;
			}
			submitMeta(findMetaByProviderId('formidable', formId), {}, true);
		});

		$(document).on('nfFormSubmitResponse', function (_e, response) {
			if (enabledProviders.indexOf('ninja-forms') === -1) return;
			var formId = '';
			if (response && response.id) {
				formId = response.id;
			} else if (response && response.data && response.data.form_id) {
				formId = response.data.form_id;
			} else if (response && response.response && response.response.data && response.response.data.form_id) {
				formId = response.response.data.form_id;
			}
			submitMeta(findMetaByProviderId('ninja-forms', formId), {}, true);
		});

		$(document).on('nfFormReady', function () {
			if (enabledProviders.indexOf('ninja-forms') !== -1) {
				scanForms();
			}
		});

		$(document).on('forminator:form:submit:success forminator.form.submit.success', function (_e, response) {
			if (enabledProviders.indexOf('forminator') === -1) return;
			var formId = '';
			if (response && response.form_id) {
				formId = response.form_id;
			}
			submitMeta(findMetaByProviderId('forminator', formId), {}, true);
		});

		$(document.body).on('checkout_place_order_success', function () {
			if (enabledProviders.indexOf('woocommerce') === -1) return;
			submitMeta(findMetaByProviderId('woocommerce', 'checkout'));
		});

		$(document.body).on('payment_method_selected updated_checkout', function () {
			/* Keep checkout form binding alive after AJAX refresh. */
			scanForms();
		});
	}

	function initForm(form) {
		if (form.dataset.spFormsTracked === '1') return;
		var meta = getFormMeta(form);
		if (!meta) return;
		form.dataset.spFormsTracked = '1';

		if (isFormSubmitted(meta.form_key)) {
			return;
		}

		observeViews(form, meta);
		bindStarts(form, meta);
		bindGenericSubmit(form, meta);
	}

	function scanForms() {
		document.querySelectorAll('form').forEach(initForm);
	}

	var ajaxBound = false;
	function boot() {
		scanForms();
		if (!ajaxBound) {
			ajaxBound = true;
			bindGlobalAjaxSuccess();
			document.addEventListener('submit', function (e) {
				var form = e.target;
				if (form && (form.tagName === 'FORM' || (form.nodeName && form.nodeName.toUpperCase() === 'FORM'))) {
					var meta = getFormMeta(form);
					if (meta && meta.form_provider !== 'woocommerce') {
						submitMeta(meta, { submission_status: 'success' });
					}
				}
			}, true);
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}

	if ('MutationObserver' in window) {
		var mo = new MutationObserver(function () { scanForms(); });
		mo.observe(document.documentElement, { childList: true, subtree: true });
	}
})();
