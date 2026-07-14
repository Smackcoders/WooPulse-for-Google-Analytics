# StorePulse – Roles & Permissions

## Custom Capabilities

StorePulse defines two custom WordPress capabilities. These are registered on
plugin **activation** and removed on **deactivation**.

| Capability | Description |
|---|---|
| `StorePulse_view_reports` | Read-only access: view Dashboard, reports, logs, real-time data |
| `StorePulse_manage_settings` | Full access: change Settings, Goals, Integration config |

---

## Role Matrix

| WordPress Role | `StorePulse_view_reports` | `StorePulse_manage_settings` |
|---|:---:|:---:|
| **Administrator** | ✅ | ✅ |
| **Developer** | ✅ | ✅ |
| **Shop Manager** *(Manager)* | ✅ | ❌ |
| **Editor** | ✅ | ❌ |
| **Author / Contributor / Subscriber** | ❌ | ❌ |

> [!NOTE]
> The **Developer** role is granted both capabilities to allow access to technical settings, API synchronization, and data export features.
>
> Roles with neither capability cannot see the StorePulse admin menu at all
> and receive **403 Forbidden** from all REST endpoints.

---

## Admin Menu Access

| Page / Section | Required Capability |
|---|---|
| Dashboard (main page) | `StorePulse_view_reports` |
| Logs | `StorePulse_view_reports` |
| Real-time Visitors | `StorePulse_view_reports` |
| Sales Summary | `StorePulse_view_reports` |
| Traffic Overview | `StorePulse_view_reports` |
| eCommerce Overview | `StorePulse_view_reports` |
| Product Performance | `StorePulse_view_reports` |
| Social Media Tracking | `StorePulse_view_reports` |
| Campaign URL Tracking | `StorePulse_view_reports` |
| Link Report | `StorePulse_view_reports` |
| Core Web Vitals | `StorePulse_view_reports` |
| User Journey | `StorePulse_view_reports` |
| **Settings** | `StorePulse_manage_settings` |
| **Custom Goals** | `StorePulse_manage_settings` |

---

## REST API Access

### Read-only routes (GET) – `StorePulse_view_reports`

```
GET /wp-json/StorePulse/v1/daily-metrics
GET /wp-json/StorePulse/v1/visitor-types
GET /wp-json/StorePulse/v1/devices
GET /wp-json/StorePulse/v1/browsers
GET /wp-json/StorePulse/v1/top-pages
GET /wp-json/StorePulse/v1/top-countries
GET /wp-json/StorePulse/v1/source-medium
GET /wp-json/StorePulse/v1/funnel
GET /wp-json/StorePulse/v1/realtime
GET /wp-json/StorePulse/v1/kpi-metrics
GET /wp-json/StorePulse/v1/wc-metrics
GET /wp-json/StorePulse/v1/dashboard/refresh
GET /wp-json/StorePulse/v1/traffic-overview
GET /wp-json/StorePulse/v1/notes
GET /wp-json/StorePulse/v1/reports/ecommerce-overview
GET /wp-json/StorePulse/v1/reports/social-media-tracking
GET /wp-json/StorePulse/v1/reports/transactions
GET /wp-json/StorePulse/v1/reports/links
GET /wp-json/StorePulse/v1/reports/demographics
GET /wp-json/StorePulse/v1/core-web-vitals
GET /wp-json/StorePulse/v1/user-journey
GET /wp-json/StorePulse/v1/reports/compare
GET /wp-json/StorePulse/v1/integration/woocommerce
GET /wp-json/StorePulse/v1/integration/google-analytics
GET /wp-json/wp_asa/v1/settings
GET /wp-json/wp_asa/v1/reports
```

### Write routes (POST / PUT / DELETE) – `StorePulse_manage_settings`

```
POST   /wp-json/StorePulse/v1/integration/google-analytics/sync
POST   /wp-json/StorePulse/v1/notes
PUT    /wp-json/StorePulse/v1/notes/{id}
DELETE /wp-json/StorePulse/v1/notes/{id}
POST   /wp-json/wp_asa/v1/settings
POST   /wp-json/wp_asa/v1/reports/custom
POST   /wp-json/StorePulse/v1/pro/advanced-event
POST   /wp-json/StorePulse/v1/pro/alerts
```

### Public / token-authenticated routes

```
POST /wp-json/StorePulse/v1/link-click
     ↳ Public — called by the frontend tracking script (anonymous visitors)

POST /wp-json/StorePulse/v1/integration/woocommerce/event
     ↳ Accepts either a logged-in admin user OR a shared secret token
       (stored in wp_option: StorePulse_webhook_token)
```

---

## Granting Access to Additional Roles

An administrator can grant capabilities to any role from PHP:

```php
// Grant view-only access to a custom role
$role = get_role('my_custom_role');
$role->add_cap('StorePulse_view_reports', true);

// Revoke a capability
$role->remove_cap('StorePulse_view_reports');
```

Or use a plugin such as **Members** or **User Role Editor** to manage
capabilities via the WordPress admin UI.

---

## Implementation Files

| File | What changed |
|---|---|
| `includes/installation/install.php` | `StorePulse_register_capabilities()` now includes `shop_manager`; called on activation and cleaned up on deactivation |
| `includes/connector.php` | Menu registrations use capability strings directly; `ajax_fetch_logs()` now checks nonce + `StorePulse_view_reports` |
| `includes/class-ga-reporter.php` | Split into `rest_permission_check_read()` and `rest_permission_check_write()`; all routes updated accordingly |

