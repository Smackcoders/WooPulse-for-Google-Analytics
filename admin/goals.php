<?php
/**
 * Goals management for StorePulse analytics plugin.
 *
 * @package StorePulse
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Save Goal Logic.
if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['StorePulse_save_goal'] ) && check_admin_referer( 'StorePulse_save_goal' ) ) {
	if ( ! current_user_can( 'StorePulse_manage_settings' ) ) {
		wp_die( esc_html__( 'You do not have permission to manage goals.', 'woopulse' ) );
	}

	$StorePulse_goals     = get_option( 'StorePulse_custom_goals', array() );
	$StorePulse_post_goal = isset( $_POST['goal'] ) && is_array( $_POST['goal'] ) ? map_deep( wp_unslash( $_POST['goal'] ), 'sanitize_text_field' ) : array();

	$StorePulse_new_goal = array(
		'id'         => uniqid(),
		'label'      => isset( $StorePulse_post_goal['label'] ) ? $StorePulse_post_goal['label'] : '',
		'event_name' => isset( $StorePulse_post_goal['event_name'] ) ? $StorePulse_post_goal['event_name'] : '',
		'type'       => isset( $StorePulse_post_goal['type'] ) ? $StorePulse_post_goal['type'] : 'page_view',
		'trigger'    => isset( $StorePulse_post_goal['trigger'] ) ? $StorePulse_post_goal['trigger'] : '',
		'active'     => true,
		'created_at' => current_time( 'mysql' ),
	);

	$is_valid = true;
	$error_msg = '';
	if ( in_array( $StorePulse_new_goal['type'], array( 'click', 'form_submit' ), true ) ) {
		$trigger = $StorePulse_new_goal['trigger'];
		if ( empty( $trigger ) ) {
			$is_valid = false;
			$error_msg = 'Trigger cannot be empty.';
		} elseif ( ! preg_match( '/^[#\.\\[a-zA-Z]/', $trigger ) ) {
			$is_valid = false;
			$error_msg = 'CSS selector must start with #, ., [, or a valid HTML tag name.';
		} elseif ( preg_match( '/[<!@$%^&;{}?~`]/', $trigger ) ) {
			$is_valid = false;
			$error_msg = 'CSS selector contains invalid characters. Avoid plain text.';
		}
	}

	if ( $is_valid ) {
		$StorePulse_goals[]  = $StorePulse_new_goal;
		update_option( 'StorePulse_custom_goals', $StorePulse_goals );
		echo '<div class="notice notice-success is-dismissible"><p>✅ Goal <strong>' . esc_html( $StorePulse_new_goal['label'] ) . '</strong> added successfully!</p></div>';
	} else {
		echo '<div class="notice notice-error is-dismissible"><p>❌ Failed to save goal: ' . esc_html( $error_msg ) . ' Please enter a valid CSS selector (e.g. #my-button or .submit-btn).</p></div>';
	}
}

// Delete Goal Logic.
if ( isset( $_GET['delete_goal'] ) ) {
	$StorePulse_delete_goal_id = sanitize_text_field( wp_unslash( $_GET['delete_goal'] ) );
	if ( check_admin_referer( 'delete_goal_' . $StorePulse_delete_goal_id ) ) {
		if ( ! current_user_can( 'StorePulse_manage_settings' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage goals.', 'woopulse' ) );
		}

		$StorePulse_goals = get_option( 'StorePulse_custom_goals', array() );
		$StorePulse_goals = array_filter( $StorePulse_goals, fn( $g ) => $g['id'] !== $StorePulse_delete_goal_id );
		update_option( 'StorePulse_custom_goals', array_values( $StorePulse_goals ) );
		echo '<div class="notice notice-info is-dismissible"><p>🗑️ Goal removed.</p></div>';
	}
}

$StorePulse_goals = get_option( 'StorePulse_custom_goals', array() );

$needs_update = false;
// Data Normalization (Fix for "Undefined array key" warnings).
foreach ( $StorePulse_goals as &$StorePulse_goal ) {
	if ( ! isset( $StorePulse_goal['type'] ) ) {
		$StorePulse_goal['type'] = 'page_view';
		$needs_update = true;
	}
	if ( ! isset( $StorePulse_goal['trigger'] ) ) {
		$StorePulse_goal['trigger'] = '';
		$needs_update = true;
	}
	if ( ! isset( $StorePulse_goal['label'] ) ) {
		$StorePulse_goal['label'] = 'Untitled Goal';
		$needs_update = true;
	}
	if ( ! isset( $StorePulse_goal['event_name'] ) ) {
		$StorePulse_goal['event_name'] = 'custom_event';
		$needs_update = true;
	}
	if ( ! isset( $StorePulse_goal['id'] ) ) {
		$StorePulse_goal['id'] = uniqid();
		$needs_update = true;
	}
}
unset( $StorePulse_goal );

if ( $needs_update ) {
	update_option( 'StorePulse_custom_goals', $StorePulse_goals );
}

// Get goal suggestions (PRO feature).
$StorePulse_suggestions = array();
if ( function_exists( 'StorePulse_is_pro_active' ) && StorePulse_is_pro_active() ) {
	$StorePulse_suggestions = StorePulse_get_goal_suggestions();
}
?>

<div class="wrap animate-fade-in" id="StorePulse-goals-v2">
	<div class="mb-6">
		<h2 class="text-2xl font-bold text-slate-900 leading-tight m-0">Goals</h2>
		<p class="text-sm text-slate-500 mt-1 mb-0">Create and manage custom conversion events to track user interactions and business objectives.</p>
	</div>

	<!-- Subtle PRO Notice -->
	<!-- <div
		class="bg-indigo-50 border-l-4 border-indigo-500 p-3 mb-6 flex items-center justify-between rounded-r-lg shadow-sm">
		<p class="text-sm text-indigo-800 font-medium tracking-tight">
			🚀 <strong>PRO Version:</strong> Explore advanced tracking modules like <u>Button Click Heatmaps</u> and
			<u>Form Analysis</u> in the upcoming <strong>Pulse Analytics PRO</strong> expansion.
		</p>
		<span
			class="text-[10px] bg-indigo-600 text-white px-3 py-1 rounded hover:bg-indigo-700 transition-colors font-semibold uppercase tracking-widest shadow-sm">
			Coming Soon
		</span>
	</div> -->


	<?php if ( ! empty( $StorePulse_suggestions ) && function_exists( 'StorePulse_is_pro_active' ) && StorePulse_is_pro_active() ) : ?>
		<!-- PRO: Goal Suggestions -->
		<div class="bg-gradient-to-r from-indigo-50 to-blue-50 border-l-4 border-indigo-500 p-4 mb-6 rounded-r-lg">
			<div class="flex items-center justify-between mb-3">
				<h3 class="text-sm font-bold text-gray-800 flex items-center">
					<svg class="w-4 h-4 mr-2 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>
					Suggested Goals <span class="text-xs bg-indigo-600 text-white px-2 py-0.5 rounded ml-2">PRO</span>
				</h3>
			</div>
			<div class="space-y-2">
				<?php foreach ( $StorePulse_suggestions as $StorePulse_suggestion ) : ?>
					<div class="bg-white p-3 rounded border border-indigo-100 flex items-center justify-between">
						<div class="flex-1">
							<div class="font-semibold text-sm text-gray-800"><?php echo esc_html( $StorePulse_suggestion['label'] ); ?></div>
							<div class="text-xs text-gray-500 mt-1"><?php echo esc_html( $StorePulse_suggestion['reason'] ); ?></div>
						</div>
						<button type="button"
							onclick="applySuggestion(<?php echo esc_attr( wp_json_encode( $StorePulse_suggestion ) ); ?>)"
							class="ml-3 px-3 py-1 bg-indigo-600 text-white text-xs rounded hover:bg-indigo-700 transition-colors">
							Add Goal
						</button>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>

	<div class="grid grid-cols-1 xl:grid-cols-3 gap-8">

		<!-- LEFT COLUMN: Create New Goal -->
		<div class="xl:col-span-1 space-y-6">

			<!-- Quick Templates -->
			<div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
				<h3 class="text-sm font-bold text-gray-800 mb-4 flex items-center">
					<svg class="w-4 h-4 mr-2 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg> 1-Click Templates
				</h3>
				<p class="text-[10px] text-gray-400 mb-4 ml-1">Choose a common goal to pre-fill the builder below.</p>
				<div class="grid grid-cols-1 gap-2">
					<button type="button"
						onclick="applyTemplate('Newsletter Signup', 'generate_lead', 'form_submit', 'form#newsletter')"
						class="text-left p-3 rounded-lg border border-gray-50 hover:border-indigo-200 hover:bg-indigo-50 transition-all text-xs font-medium text-gray-600 flex justify-between items-center group">
						Newsletter Form
						<span
							class="text-[10px] bg-indigo-100 text-indigo-600 px-2 py-0.5 rounded opacity-0 group-hover:opacity-100 transition-opacity">Apply</span>
					</button>
					<button type="button"
						onclick="applyTemplate('Contact Page View', 'contact', 'page_view', '/contact-us')"
						class="text-left p-3 rounded-lg border border-gray-50 hover:border-indigo-200 hover:bg-indigo-50 transition-all text-xs font-medium text-gray-600 flex justify-between items-center group">
						Contact Page View
						<span
							class="text-[10px] bg-indigo-100 text-indigo-600 px-2 py-0.5 rounded opacity-0 group-hover:opacity-100 transition-opacity">Apply</span>
					</button>
					<button type="button"
						onclick="applyTemplate('WhatsApp Chat', 'chat_start', 'click', 'a.whatsapp-btn')"
						class="text-left p-3 rounded-lg border border-gray-50 hover:border-indigo-200 hover:bg-indigo-50 transition-all text-xs font-medium text-gray-600 flex justify-between items-center group">
						WhatsApp Button Click
						<span
							class="text-[10px] bg-indigo-100 text-indigo-600 px-2 py-0.5 rounded opacity-0 group-hover:opacity-100 transition-opacity">Apply</span>
					</button>
				</div>
			</div>

			<!-- Goal Builder Form -->
			<div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
				<h3 class="text-sm font-bold text-gray-800 mb-4 flex items-center">
					<svg class="w-4 h-4 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg> Conversion Builder
				</h3>

				<form method="POST" id="goalBuilderForm" class="space-y-4">
					<?php wp_nonce_field( 'StorePulse_save_goal' ); ?>

					<div>
						<label
							class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Internal
							Name</label>
						<input type="text" name="goal[label]" id="g_label"
							class="w-full bg-gray-50 border-gray-100 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-blue-500"
							placeholder="e.g. Free Ebook Download" required>
						<p class="text-[9px] text-gray-400 mt-1 ml-1">Give your goal a name for your own reference.</p>
					</div>

					<div>
						<label
							class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1">GA4
							Event (No spaces)</label>
						<input type="text" name="goal[event_name]" id="g_event"
							class="w-full bg-gray-50 border-gray-100 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-blue-500"
							placeholder="e.g. ebook_download" required>
						<p class="text-[9px] text-gray-400 mt-1 ml-1">This name will appear in Google Analytics (use
							underscores).</p>
					</div>

					<div>
						<label
							class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Action
							Type</label>
						<select name="goal[type]" id="g_type"
							class="w-full bg-gray-50 border-gray-100 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-blue-500">
							<option value="page_view">🔗 Visiting a Specific Page</option>
							<option value="click">🖱️ Clicking a Specific Button/Link</option>
							<option value="form_submit">📝 Submitting a Form</option>
						</select>
					</div>

					<div>
						<label
							class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Target
							Match</label>
						<input type="text" name="goal[trigger]" id="g_trigger"
							class="w-full bg-gray-50 border-gray-100 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-blue-500"
							placeholder="e.g. /thank-you" required>
						<p id="triggerHint"
							class="text-[10px] text-gray-400 mt-2 ml-1 italic font-medium text-blue-500">Matches if the
							URL contains this text.</p>
					</div>

					<div class="pt-2">
						<button type="submit" name="StorePulse_save_goal"
							class="w-full bg-indigo-600 text-white font-bold py-3 rounded-xl hover:bg-indigo-700 transition-all shadow-md active:scale-[0.98]">
							Start Tracking This Goal
						</button>
					</div>
				</form>
			</div>
		</div>

		<!-- RIGHT COLUMN: Active Goals List -->
		<div class="xl:col-span-2">
			<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
				<div class="p-6 border-b border-gray-50 flex justify-between items-center">
					<h3 class="text-sm font-bold text-gray-800">Your Active Goals</h3>
					<span class="text-[10px] text-gray-400 font-medium">Auto-synced with frontend tracking
						script.</span>
				</div>

				<table class="w-full text-sm StorePulse-alternating">
					<thead class="bg-gray-50 text-[10px] font-bold text-gray-400 uppercase tracking-widest">
						<tr>
							<th class="p-4 text-left">Goal Label</th>
							<th class="p-4 text-left">Trigger</th>
							<th class="p-4 text-center">Status</th>
							<th class="p-4 text-right">Actions</th>
						</tr>
					</thead>
					<tbody class="divide-y divide-gray-50">
						<?php if ( empty( $StorePulse_goals ) ) : ?>
							<tr>
								<td colspan="4" class="p-12 text-center">
									<div class="flex flex-col items-center">
										<div class="mb-4 w-14 h-14 bg-indigo-50/50 rounded-2xl flex items-center justify-center border border-indigo-100/50">
											<svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"></path></svg>
										</div>
										<p class="text-gray-400 font-medium">No custom goals defined yet.</p>
										<p class="text-xs text-gray-300 mt-1">Use the builder on the left to start tracking
											conversions.</p>
									</div>
								</td>
							</tr>
							<?php
						else :
							$StorePulse_icons = array(
								'page_view'   => '🔗',
								'click'       => '🖱️',
								'form_submit' => '📝',
							);
							foreach ( $StorePulse_goals as $StorePulse_goal ) :
								?>
								<tr class="odd:bg-blue-50 even:bg-white hover:bg-gray-100 transition-colors">
									<td class="p-4">
										<div class="font-bold text-gray-800"><?php echo esc_html( $StorePulse_goal['label'] ); ?></div>
										<div class="text-[10px] text-indigo-500 font-semibold uppercase tracking-tighter">Event:
											<?php echo esc_html( $StorePulse_goal['event_name'] ); ?>
										</div>
									</td>
									<td class="p-4">
										<?php
										echo esc_html( $StorePulse_icons[ $StorePulse_goal['type'] ] ?? '🎯' );
										?>
										<code
											class="ml-1 text-[11px] bg-gray-100 p-1 rounded text-gray-600"><?php echo esc_html( $StorePulse_goal['trigger'] ); ?></code>
									</td>
									<td class="p-4 text-center">
										<span
											class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-green-100 text-green-700 uppercase tracking-wide">
											<span class="w-1.5 h-1.5 bg-green-500 rounded-full mr-1.5 animate-pulse"></span>
											Active
										</span>
									</td>
									<td class="p-4 text-right">
										<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=StorePulse_goals&delete_goal=' . $StorePulse_goal['id'] ), 'delete_goal_' . $StorePulse_goal['id'] ) ); ?>"
											class="text-red-400 hover:text-red-600 transition-colors p-2 flex items-center"
											onclick="return confirm('Delete this goal?')">
											<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
										</a>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
				</table>
			</div>

			<!-- Info Box -->
			<div class="mt-6 bg-blue-50/50 border border-blue-100 p-4 rounded-xl flex">
				<svg class="w-5 h-5 text-blue-400 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
				<div class="text-[11px] text-blue-800 leading-relaxed font-medium">
					<strong>How it works:</strong> Pulse Analytics automatically injects a listener into your site's frontend.
					Every time the condition (URL, Click, or Submit) is met, we trigger a standard `gtag` event that
					appears in your GA4 reports immediately.
				</div>
			</div>
		</div>

	</div>

	<!-- PRO PREVIEW SECTION -->
	<!-- <div class="mt-12 pt-8 border-t border-gray-100">

		<div class="flex items-center space-x-2 text-gray-400 mb-6 font-bold uppercase tracking-[0.2em] text-[10px]">
			<svg class="w-4 h-4 text-yellow-400 translate-y-[-1px]" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
			<span>Future Pro Expansion Roadmap</span>
		</div>

		<div
			class="grid grid-cols-1 md:grid-cols-3 gap-6 opacity-60 grayscale hover:grayscale-0 hover:opacity-100 transition-all duration-500">
			<div class="bg-white border border-gray-100 p-5 rounded-2xl relative overflow-hidden group shadow-sm">
				<div class="flex justify-between items-start mb-4">
					<span
						class="bg-indigo-50 text-indigo-700 text-[9px] font-bold px-2 py-0.5 rounded uppercase tracking-wider">Addon</span>
					<svg class="w-5 h-5 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path></svg>
				</div>
				<h4 class="text-sm font-bold text-gray-800 mb-1">Multi-Step Funnels</h4>
				<p class="text-[10px] text-gray-400 leading-relaxed">Track complex user journeys across multiple pages
					and events to identify drop-off points.</p>
			</div>
			<div class="bg-white border border-gray-100 p-5 rounded-2xl relative overflow-hidden group shadow-sm">
				<div class="flex justify-between items-start mb-4">
					<span
						class="bg-indigo-100 text-indigo-700 text-[9px] font-bold px-2 py-0.5 rounded uppercase tracking-wider">Addon</span>
					<svg class="w-5 h-5 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
				</div>
				<h4 class="text-sm font-bold text-gray-800 mb-1">AI Goal Suggestions</h4>
				<p class="text-[10px] text-gray-400 leading-relaxed">Let our pulse engine scan your store and
					automatically suggest conversion goals based on traffic patterns.</p>
			</div>
			<div class="bg-white border border-gray-100 p-5 rounded-2xl relative overflow-hidden group shadow-sm">
				<div class="flex justify-between items-start mb-4">
					<span
						class="bg-indigo-100 text-indigo-700 text-[9px] font-bold px-2 py-0.5 rounded uppercase tracking-wider">Addon</span>
					<svg class="w-5 h-5 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
				</div>
				<h4 class="text-sm font-bold text-gray-800 mb-1">Alert Notifications</h4>
				<p class="text-[10px] text-gray-400 leading-relaxed">Get instantly notified if conversion rates drop
					below your target thresholds so you can act fast.</p>
			</div>
		</div>
	</div> -->
</div>
