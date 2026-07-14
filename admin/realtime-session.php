<?php
/**
 * Real-time session monitoring for StorePulse analytics plugin.
 *
 * @package StorePulse
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'StorePulse_Session_Tracker' ) ) {
	$StorePulse_active_sessions = StorePulse_Session_Tracker::get_realtime_sessions( 90 );
} else {
	$StorePulse_active_sessions = get_option( 'StorePulse_active_sessions', array() );
}
$StorePulse_total_sessions  = count( $StorePulse_active_sessions );
?>

<div class="wrap animate-fade-in" id="StorePulse-realtime-v2">
	<div class="w-full mt-2">
		<div class="flex items-center justify-between mb-6 px-0">
			<div>
				<h2 class="text-2xl font-bold text-slate-900 leading-tight m-0">Real-time</h2>
				<p class="text-sm text-slate-500 mt-1 mb-0 flex items-center gap-2">
					Monitor active visitors currently on your site
					<span class="relative flex h-2 w-2 ml-1">
						<span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
						<span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
					</span>
					<span class="text-[11px] font-medium text-green-600">Live</span>
				</p>
			</div>

			<div class="text-indigo-600 px-3 py-1.5 rounded-lg text-[13px] font-bold border border-indigo-100 bg-white shadow-sm">
				<?php echo esc_html( $StorePulse_total_sessions ); ?> visitors
			</div>
		</div>

		<!-- Main Card -->
		<div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
			<div class="overflow-x-auto">
				<?php if ( $StorePulse_total_sessions > 0 ) : ?>
					<table class="w-full text-left border-collapse min-w-[900px]">
						<thead>
							<tr class="border-b border-slate-50">
								<th class="px-6 py-4 text-[11px] font-semibold text-slate-400 uppercase tracking-wider"
									style="width: 60px;">#</th>
								<th class="px-6 py-4 text-[11px] font-semibold text-slate-400 uppercase tracking-wider"
									style="width: 20%;">User ID</th>
								<th class="px-6 py-4 text-[11px] font-semibold text-slate-400 uppercase tracking-wider"
									style="width: 20%;">Anonymous ID</th>
								<th class="px-6 py-4 text-[11px] font-semibold text-slate-400 uppercase tracking-wider">
									Country</th>
								<th class="px-6 py-4 text-[11px] font-semibold text-slate-400 uppercase tracking-wider">City
								</th>
								<th
									class="px-6 py-4 text-[11px] font-semibold text-slate-400 uppercase tracking-wider text-right">
									Last Active</th>
							</tr>
						</thead>
						<tbody class="divide-y divide-slate-50">
							<?php
							$StorePulse_counter = 1;
							foreach ( $StorePulse_active_sessions as $StorePulse_id => $StorePulse_data ) :
								?>
								<tr
									class="hover:bg-indigo-50/30 even:bg-slate-50 transition-all group border-b border-slate-50 last:border-0">
									<td class="px-6 py-4 text-[13px] font-medium text-slate-400 tabular-nums">
										<?php echo esc_html( $StorePulse_counter++ ); ?>
									</td>
									<td class="px-6 py-4">
										<span class="text-[13px] font-medium text-slate-600 leading-tight">
											<?php
											$u_login = $StorePulse_data['user_login'] ?? 'Guest'; // phpcs:ignore
											$u_id    = $StorePulse_data['user_id'] ?? 0; // phpcs:ignore
											if ( $u_id > 0 ) {
												echo esc_html( "user-" . $u_id . " (" . $u_login . ")" );
											} else {
												echo esc_html( "Guest" );
											}
											?>
										</span>
									</td>
									<td class="px-6 py-4">
										<div
											class="inline-flex items-center px-2 py-0.5 rounded bg-slate-100 text-slate-500 text-[11px] font-medium transition-all tabular-nums">
											<?php
											$anon_id = $StorePulse_data['anon_id'] ?? ''; // phpcs:ignore
											if ( strlen( $anon_id ) > 15 ) {
												echo esc_html( substr( $anon_id, 0, 8 ) ) . '..';
											} else {
												echo esc_html( $anon_id ?: 'N/A' );
											}
											?>
										</div>
									</td>
									<td class="px-6 py-4">
										<span class="text-[13px] font-medium text-slate-600 leading-tight">
											<?php echo esc_html( $StorePulse_data['country'] ?? 'Unknown' ); ?>
										</span>
									</td>
									<td class="px-6 py-4">
										<span class="text-[13px] font-medium text-slate-500">
											<?php echo esc_html( $StorePulse_data['city'] ?? 'Unknown' ); ?>
										</span>
									</td>
									<td class="px-6 py-4 text-right">
										<span class="text-[13px] font-normal text-slate-500 tabular-nums">
											<?php echo isset( $StorePulse_data['last_active'] ) ? esc_html( human_time_diff( strtotime( $StorePulse_data['last_active'] ), current_time( 'timestamp' ) ) ) . ' ago' : 'Just now'; ?>
										</span>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php else : ?>
					<!-- Empty State -->
					<div class="py-32 flex flex-col items-center justify-center text-center">
						<div class="mb-6 w-16 h-16 bg-indigo-50/50 rounded-2xl flex items-center justify-center border border-indigo-100/50">
							<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none"
								stroke="#6366f1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
								<circle cx="9" cy="7" r="4"></circle>
								<path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
								<path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
							</svg>
						</div>
						<h3 class="text-lg font-bold text-slate-900 mb-2">No active visitors</h3>
						<p class="text-slate-400 text-[13px] max-w-sm mx-auto font-medium">It's quiet right now. Check back
							later to see real-time activity.</p>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<div class="mt-8 text-center text-[12px] font-medium text-slate-400">
			Data updates automatically based on user interactions
		</div>
	</div>
</div>
