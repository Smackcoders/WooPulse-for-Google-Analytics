<?php
/**
 * GA4 Settings Card UI Component for Admin Settings Page.
 *
 * @package Sm_Pulse_Analytics
 *
 * @license GPL-2.0-or-later
 * @link    https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sm_pulse_analytics_measurement_id = PulseAnalytics_GA4_Config::get_measurement_id();
$sm_pulse_analytics_property_id    = PulseAnalytics_GA4_Config::get_property_id();
$sm_pulse_analytics_api_secret     = PulseAnalytics_GA4_Config::get_api_secret();
$sm_pulse_analytics_is_connected   = PulseAnalytics_GA4_OAuth::is_connected();
$sm_pulse_analytics_auth_url       = PulseAnalytics_GA4_OAuth::get_auth_url();
?>

<div class="settings-card bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 shadow-sm mb-6">
	<div class="flex items-center justify-between gap-4 mb-6">
		<div class="flex items-center gap-3">
			<div class="w-10 h-10 rounded-xl bg-orange-500/10 text-orange-600 flex items-center justify-center font-bold">
				<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"></path><path d="m19 9-5 5-4-4-3 3"></path></svg>
			</div>
			<div>
				<h3 class="text-lg font-bold text-slate-900 m-0">Google Analytics 4 (GA4) Telemetry</h3>
				<p class="text-xs text-slate-500 m-0">Configure your GA4 Measurement ID and API secret for live telemetry</p>
			</div>
		</div>
		<div>
			<?php if ( $sm_pulse_analytics_is_connected ) : ?>
				<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
					<span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> Connected
				</span>
			<?php else : ?>
				<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600">
					Not Connected
				</span>
			<?php endif; ?>
		</div>
	</div>

	<form id="pulse-analytics-ga4-settings-form" class="space-y-4">
		<div>
			<label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">GA4 Measurement ID</label>
			<input type="text" name="measurement_id" value="<?php echo esc_attr( $sm_pulse_analytics_measurement_id ); ?>" placeholder="G-XXXXXXXXXX" class="w-full max-w-md px-4 py-2.5 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 text-sm font-mono transition-all" />
			<p class="text-[11px] text-slate-500 mt-1">Found in GA4 Admin → Data Streams → Web Stream Details.</p>
		</div>

		<div>
			<label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">GA4 Property ID (Optional)</label>
			<input type="text" name="property_id" value="<?php echo esc_attr( $sm_pulse_analytics_property_id ); ?>" placeholder="123456789" class="w-full max-w-md px-4 py-2.5 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 text-sm font-mono transition-all" />
		</div>

		<div>
			<label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Measurement Protocol API Secret (Optional)</label>
			<input type="password" name="api_secret" value="<?php echo esc_attr( $sm_pulse_analytics_api_secret ); ?>" placeholder="••••••••••••••••" class="w-full max-w-md px-4 py-2.5 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 text-sm font-mono transition-all" />
		</div>

		<div class="pt-2 flex items-center gap-3">
			<button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-5 py-2.5 rounded-xl text-xs transition-all shadow-sm flex items-center gap-2 cursor-pointer border-0">
				Save GA4 Settings
			</button>

			<?php if ( ! empty( $sm_pulse_analytics_auth_url ) && '#' !== $sm_pulse_analytics_auth_url ) : ?>
				<a href="<?php echo esc_url( $sm_pulse_analytics_auth_url ); ?>" class="bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold px-4 py-2.5 rounded-xl text-xs transition-all shadow-sm inline-flex items-center gap-2 text-decoration-none">
					Sign in with Google
				</a>
			<?php endif; ?>
		</div>
		<div id="pulse-analytics-ga4-msg" class="mt-3 text-xs hidden"></div>
	</form>
</div>

<?php
do_action(
	'sm_pulse_analytics_ga4_settings_tab_gsc',
	array(
		'auth_url'       => $sm_pulse_analytics_auth_url,
		'oauth_connected' => $sm_pulse_analytics_is_connected,
	)
);

