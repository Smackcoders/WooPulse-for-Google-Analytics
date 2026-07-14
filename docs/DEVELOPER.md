# StorePulse Developer Documentation

This document provides comprehensive information for developers extending StorePulse functionality.

## Table of Contents

1. [WordPress Hooks & Filters](#wordpress-hooks--filters)
2. [REST API Endpoints](#rest-api-endpoints)
3. [Custom Functions](#custom-functions)
4. [Database Schema](#database-schema)
5. [Extending Functionality](#extending-functionality)

---

## WordPress Hooks & Filters

### Actions

#### `StorePulse_run_data_aggregation`
Triggered when data aggregation runs.

**Parameters:** None

**Example:**
```php
add_action('StorePulse_run_data_aggregation', function() {
    // Custom aggregation logic
});
```

#### `StorePulse_before_event_save`
Triggered before saving an event to the database.

**Parameters:**
- `$event_type` (string) - Event type
- `$event_data` (array) - Event data array

**Example:**
```php
add_action('StorePulse_before_event_save', function($event_type, $event_data) {
    // Modify event data before saving
    $event_data['custom_field'] = 'value';
    return $event_data;
}, 10, 2);
```

#### `StorePulse_after_event_save`
Triggered after saving an event to the database.

**Parameters:**
- `$event_id` (int) - Event ID
- `$event_type` (string) - Event type

**Example:**
```php
add_action('StorePulse_after_event_save', function($event_id, $event_type) {
    // Send notification or trigger other actions
}, 10, 2);
```

### Filters

#### `StorePulse_dashboard_layout`
Filter dashboard widget layout.

**Parameters:**
- `$layout` (array) - Array of widget IDs

**Example:**
```php
add_filter('StorePulse_dashboard_layout', function($layout) {
    // Add custom widget
    $layout[] = 'custom_widget';
    return $layout;
});
```

#### `StorePulse_event_data`
Filter event data before saving.

**Parameters:**
- `$event_data` (array) - Event data array
- `$event_type` (string) - Event type

**Example:**
```php
add_filter('StorePulse_event_data', function($event_data, $event_type) {
    // Add custom data to events
    $event_data['custom_meta'] = 'value';
    return $event_data;
}, 10, 2);
```

#### `StorePulse_ga_api_request`
Filter GA API request body before sending.

**Parameters:**
- `$request_body` (array) - API request body
- `$endpoint` (string) - API endpoint

**Example:**
```php
add_filter('StorePulse_ga_api_request', function($request_body, $endpoint) {
    // Modify API request
    $request_body['limit'] = 100;
    return $request_body;
}, 10, 2);
```

#### `StorePulse_ga_api_response`
Filter GA API response data.

**Parameters:**
- `$response` (array) - API response data
- `$endpoint` (string) - API endpoint

**Example:**
```php
add_filter('StorePulse_ga_api_response', function($response, $endpoint) {
    // Transform response data
    return $response;
}, 10, 2);
```

#### `StorePulse_export_data`
Filter export data before generating file.

**Parameters:**
- `$data` (array) - Export data array
- `$format` (string) - Export format (csv/json/xml)
- `$data_type` (string) - Data type

**Example:**
```php
add_filter('StorePulse_export_data', function($data, $format, $data_type) {
    // Add custom columns to export
    foreach ($data as &$row) {
        $row['custom_field'] = 'value';
    }
    return $data;
}, 10, 3);
```

#### `StorePulse_goal_suggestions`
Filter automated goal suggestions.

**Parameters:**
- `$suggestions` (array) - Array of suggested goals

**Example:**
```php
add_filter('StorePulse_goal_suggestions', function($suggestions) {
    // Add custom suggestions
    $suggestions[] = [
        'label' => 'Custom Goal',
        'event_name' => 'custom_event',
        'type' => 'page_view',
        'trigger' => '/custom-page'
    ];
    return $suggestions;
});
```

#### `StorePulse_pro_active`
Filter PRO license check result.

**Parameters:**
- `$is_pro` (bool) - Current PRO status

**Example:**
```php
add_filter('StorePulse_pro_active', function($is_pro) {
    // Override PRO check (use with caution)
    return true;
});
```

---

## REST API Endpoints

### Base URL
`/wp-json/StorePulse/v1`

### Authentication
All endpoints require WordPress authentication (logged-in user with appropriate capabilities).

### Free Endpoints

#### Dashboard & Metrics
- `GET /dashboard/refresh` - Refresh dashboard metrics
- `GET /kpi-metrics` - Get KPI metrics
- `GET /daily-metrics` - Get daily metrics
- `GET /realtime` - Get real-time visitor count
- `GET /wc-metrics` - Get WooCommerce metrics

#### Reports
- `GET /reports` - List all reports
- `GET /reports/{id}` - Get report detail
- `POST /reports/custom` - Create custom report
- `GET /reports/compare` - Period comparison
- `GET /reports/ecommerce-overview` - Campaign performance
- `GET /reports/social-media-tracking` - Social media metrics
- `GET /reports/transactions` - Transaction list
- `GET /reports/links` - Link click data
- `GET /reports/demographics` - Demographics data

#### Settings & Integration
- `GET /settings` - Get settings
- `POST /settings` - Update settings
- `GET /integration/woocommerce` - WooCommerce status
- `GET /integration/google-analytics` - GA status
- `POST /integration/google-analytics/sync` - Trigger sync

#### Notes
- `GET /notes` - List notes
- `POST /notes` - Create note
- `PUT /notes/{id}` - Update note
- `DELETE /notes/{id}` - Delete note

#### Other
- `GET /traffic-overview` - Traffic overview data
- `GET /core-web-vitals` - Performance metrics
- `GET /user-journey` - Conversion funnel
- `POST /link-click` - Track link clicks
- `POST /integration/woocommerce/event` - WooCommerce webhook

### PRO Endpoints

All PRO endpoints require `StorePulse_is_pro_active()` to return true.

- `GET /pro/goal-suggestions` - Get automated goal suggestions
- `POST /pro/advanced-event` - Manage advanced events
- `GET /reports/funnel/{funnel_id}` - Get funnel report
- `POST /pro/custom-export` - Export data (CSV/JSON/XML)
- `GET /pro/ecommerce-insights` - CLV, segmentation, attribution
- `GET /pro/alerts` - Get alerts
- `POST /pro/alerts` - Create alert
- `GET /pro/ab-testing` - A/B testing insights
- `GET /pro/attribution` - Multi-touch attribution

See `docs/API.md` for detailed endpoint documentation.

---

## Custom Functions

### Helper Functions

#### `StorePulse_get_aggregated_metrics($date = null)`
Get aggregated metrics for a specific date.

**Parameters:**
- `$date` (string|null) - Date in Y-m-d format. Defaults to today.

**Returns:** Array with sessions, revenue, conversion_rate

#### `StorePulse_update_sync_status($sync_type, $status, $error_message = null)`
Update sync status in database.

**Parameters:**
- `$sync_type` (string) - Sync type
- `$status` (string) - Status (completed/error/pending)
- `$error_message` (string|null) - Error message if any

**Returns:** sync_id on success, false on failure

#### `StorePulse_map_source_to_network($source, $medium = '')`
Map UTM source/medium to social network name.

**Parameters:**
- `$source` (string) - UTM source
- `$medium` (string) - UTM medium

**Returns:** Network name string

#### `StorePulse_is_pro_active()`
Check if PRO license is active.

**Returns:** bool

#### `StorePulse_get_goal_suggestions($limit = 5)`
Get automated goal suggestions (PRO only).

**Parameters:**
- `$limit` (int) - Maximum suggestions to return

**Returns:** Array of suggested goals

#### `StorePulse_log_audit($action, $details = [])`
Log audit entry for settings changes (PRO only).

**Parameters:**
- `$action` (string) - Action description
- `$details` (array) - Additional details

**Returns:** log_id on success, false on failure

#### `StorePulse_generate_export_data($data_type, $start_date, $end_date)`
Generate export data for specified type and date range.

**Parameters:**
- `$data_type` (string) - Data type (events/orders/sessions/campaigns)
- `$start_date` (string) - Start date (Y-m-d)
- `$end_date` (string) - End date (Y-m-d)

**Returns:** Array of export data

---

## Database Schema

### Tables

#### `wp_storepulse_events`
Stores raw event data.

**Columns:**
- `event_id` (BIGINT) - Primary key
- `event_type` (VARCHAR) - Event type
- `order_id` (BIGINT) - WooCommerce order ID
- `user_id` (BIGINT) - WordPress user ID
- `event_data` (JSON) - Event data
- `utm_source`, `utm_medium`, `utm_campaign` (VARCHAR) - UTM parameters
- `source`, `exit_page` (VARCHAR) - Source and exit page
- `event_timestamp` (DATETIME) - Event timestamp

#### `wp_storepulse_aggregates`
Stores aggregated daily metrics.

**Columns:**
- `id` (BIGINT) - Primary key
- `aggregate_date` (DATE) - Date
- `sessions` (INT) - Session count
- `revenue` (FLOAT) - Total revenue
- `conversion_rate` (FLOAT) - Conversion rate
- `created_at` (DATETIME) - Created timestamp

#### `wp_storepulse_logs`
Stores audit logs and event logs.

**Columns:**
- `log_id` (BIGINT) - Primary key
- `event_type` (VARCHAR) - Event type
- `message` (TEXT) - Log message
- `source` (VARCHAR) - Source
- `exit_page` (VARCHAR) - Exit page
- `order_id` (BIGINT) - Order ID (optional)
- `created_at` (DATETIME) - Created timestamp

#### `wp_storepulse_notes`
Stores graph annotations/notes.

**Columns:**
- `note_id` (BIGINT) - Primary key
- `date` (DATE) - Note date
- `note_text` (TEXT) - Note content
- `created_at` (DATETIME) - Created timestamp

See `database/bootstrap.sql` for complete schema.

---

## Extending Functionality

### Adding Custom Events

```php
// Track custom event
global $wpdb;
$table = $wpdb->prefix . 'storepulse_events';

$wpdb->insert($table, [
    'event_type' => 'custom_event',
    'event_data' => json_encode([
        'session_id' => 'session_123',
        'custom_field' => 'value'
    ]),
    'event_timestamp' => current_time('mysql')
]);
```

### Adding Custom REST Endpoints

```php
add_action('rest_api_init', function() {
    register_rest_route('StorePulse/v1', '/custom-endpoint', [
        'methods' => 'GET',
        'callback' => 'my_custom_handler',
        'permission_callback' => function() {
            return current_user_can('manage_options');
        }
    ]);
});

function my_custom_handler($request) {
    return rest_ensure_response([
        'data' => 'Custom data'
    ]);
}
```

### Adding Custom Dashboard Widgets

```php
add_filter('StorePulse_dashboard_layout', function($layout) {
    $layout[] = 'custom_widget';
    return $layout;
});

// Then create widget rendering in admin/dashboard.php
```

### Customizing Export Format

```php
add_filter('StorePulse_export_data', function($data, $format, $data_type) {
    if ($format === 'csv') {
        // Custom CSV formatting
    }
    return $data;
}, 10, 3);
```

---

## PRO Features Extension

### Checking PRO Status

```php
if (function_exists('StorePulse_is_pro_active') && StorePulse_is_pro_active()) {
    // PRO features available
}
```

### Adding PRO-Only Functionality

```php
if (StorePulse_is_pro_active()) {
    // Your PRO feature code
} else {
    // Show upgrade message
    echo StorePulse_get_pro_message('Custom Feature');
}
```

---

## Best Practices

1. **Always check capabilities** before performing actions
2. **Sanitize user input** before database operations
3. **Use nonces** for form submissions
4. **Log errors** appropriately
5. **Respect PRO license** checks
6. **Use transients** for caching expensive operations
7. **Follow WordPress coding standards**

---

## Support

For questions or issues:
- Check `docs/API.md` for REST API details
- Review `docs/PROJECT-OVERVIEW.md` for architecture
- Visit: https://code.zeeyes.com/wordpress/pulse-analytics/-/issues

---

**Last Updated:** February 17, 2026
