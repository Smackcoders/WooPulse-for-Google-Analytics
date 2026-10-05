<?php
/**
 * @license GPL-2.0-or-later
 * @link    https://www.gnu.org/licenses/gpl-2.0.html
 */
if (!defined('ABSPATH'))
    exit;

$sm_pulse_analytics_rest_base = rest_url('woopulse/v1/');
$sm_pulse_analytics_nonce     = wp_create_nonce('wp_rest');
?>

<div class="wrap sp-font-stack" id="woopulse-help">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800"><?php esc_html_e( 'Help & Tutorials', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></h1>
            <p class="text-gray-400 text-xs mt-1"><?php esc_html_e( 'Get help with WooPulse setup and features', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></p>
        </div>
    </div>

    <!-- Quick Help Sections -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <!-- Getting Started -->
        <div class="bg-white p-6 rounded-lg shadow border border-gray-100">
            <h2 class="text-lg font-semibold mb-4 text-gray-800">🚀 <?php esc_html_e( 'Getting Started', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></h2>
            <div class="space-y-3 text-sm text-gray-600">
                <div>
                    <h3 class="font-semibold text-gray-700 mb-1"><?php esc_html_e( '1. Connect Google Analytics', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></h3>
                    <p class="text-xs"><?php esc_html_e( 'Go to Settings → Analytics and connect your GA4 property. You\'ll need to authorize WooPulse to access your analytics data.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></p>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-700 mb-1"><?php esc_html_e( '2. Configure Tracking', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></h3>
                    <p class="text-xs"><?php esc_html_e( 'Set up event tracking for WooCommerce orders, product views, and custom events in Settings → Events.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></p>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-700 mb-1"><?php esc_html_e( '3. View Dashboard', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></h3>
                    <p class="text-xs"><?php esc_html_e( 'Visit the Dashboard to see your analytics data, including sessions, revenue, conversion rates, and more.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></p>
                </div>
            </div>
        </div>

        <!-- Common Questions -->
        <div class="bg-white p-6 rounded-lg shadow border border-gray-100">
            <h2 class="text-lg font-semibold mb-4 text-gray-800">❓ <?php esc_html_e( 'Common Questions', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></h2>
            <div class="space-y-3 text-sm text-gray-600">
                <div>
                    <h3 class="font-semibold text-gray-700 mb-1"><?php esc_html_e( 'How do I track custom events?', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></h3>
                    <p class="text-xs"><?php esc_html_e( 'Use the REST API endpoint', 'smackcoders-pulse-analytics-for-woocommerce' ); ?> <code class="bg-gray-100 px-1 rounded">POST /woopulse/v1/integration/woocommerce/event</code> <?php esc_html_e( 'or add JavaScript tracking code to your theme.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></p>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-700 mb-1"><?php esc_html_e( 'Can I export my data?', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></h3>
                    <p class="text-xs"><?php esc_html_e( 'Yes! PRO users can export data in CSV, JSON, or XML format from the Data Export page. You can also schedule automatic exports.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></p>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-700 mb-1"><?php esc_html_e( 'How do I set up alerts?', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></h3>
                    <p class="text-xs"><?php esc_html_e( 'PRO users can configure real-time alerts in Settings → Real-time Alerts. Set thresholds for metrics like conversion rate or revenue.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Feature Guides -->
    <div class="bg-white p-6 rounded-lg shadow border border-gray-100 mb-8">
        <h2 class="text-lg font-semibold mb-4 text-gray-800">📚 <?php esc_html_e( 'Feature Guides', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="border border-gray-200 rounded p-4">
                <h3 class="font-semibold text-gray-700 mb-2"><?php esc_html_e( 'Dashboard Overview', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></h3>
                <p class="text-xs text-gray-600 mb-3"><?php esc_html_e( 'Learn how to navigate and customize your dashboard widgets.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></p>
                <button class="text-xs text-blue-600 hover:text-blue-800"><?php esc_html_e( 'View Guide →', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></button>
            </div>
            <div class="border border-gray-200 rounded p-4">
                <h3 class="font-semibold text-gray-700 mb-2"><?php esc_html_e( 'Custom Reports', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></h3>
                <p class="text-xs text-gray-600 mb-3"><?php esc_html_e( 'Create custom reports with drag-and-drop metrics and dimensions.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></p>
                <button class="text-xs text-blue-600 hover:text-blue-800"><?php esc_html_e( 'View Guide →', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></button>
            </div>
            <div class="border border-gray-200 rounded p-4">
                <h3 class="font-semibold text-gray-700 mb-2"><?php esc_html_e( 'Funnel Analysis', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></h3>
                <p class="text-xs text-gray-600 mb-3"><?php esc_html_e( 'Set up multi-step funnels to track conversion paths.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></p>
                <button class="text-xs text-blue-600 hover:text-blue-800"><?php esc_html_e( 'View Guide →', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></button>
            </div>
        </div>
    </div>

    <!-- Tooltips Reference -->
    <div class="bg-white p-6 rounded-lg shadow border border-gray-100">
        <h2 class="text-lg font-semibold mb-4 text-gray-800">💡 <?php esc_html_e( 'Tooltips Reference', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></h2>
        <div class="text-sm text-gray-600 space-y-2">
            <p><strong><?php esc_html_e( 'Dashboard Widgets:', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></strong> <?php esc_html_e( 'Hover over the info icon (ℹ️) next to any metric to see a detailed explanation.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></p>
            <p><strong><?php esc_html_e( 'Settings:', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></strong> <?php esc_html_e( 'Most settings have help text below them explaining what they do.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></p>
            <p><strong><?php esc_html_e( 'Reports:', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></strong> <?php esc_html_e( 'Click the help icon in the top-right corner of any report for context-specific help.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></p>
        </div>
    </div>
</div>
