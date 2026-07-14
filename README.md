# StorePulse Analytics

**Contributors:** fenzik, smackcoders  
**Tags:** analytics, google-analytics, woocommerce, ecommerce, tracking  
**Requires at least:** 6.4  
**Tested up to:** 7.0  
**Requires PHP:** 7.4  
**Stable tag:** 1.0.1  
**WC requires at least:** 8.0  
**WC tested up to:** 9.8  
**License:** GPLv2 or later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html

Analytics dashboard for WordPress and WooCommerce stores with real-time visitors, revenue tracking, campaign attribution, conversion funnels, and custom event tracking.
## Description

**StorePulse Analytics** is a comprehensive Google Analytics plugin designed specifically for WooCommerce stores. It provides store owners with powerful analytics tools, a user-friendly dashboard, and actionable insights into their eCommerce performance.

### Key Features

* **One-Page Dashboard** - Consolidated display of key metrics (sessions, revenue, conversion rate, average order value) with customizable widgets
* **Enhanced eCommerce Tracking** - Advanced tracking for sales, conversion funnels, product performance, and customer behavior
* **Pre-Built Reports** - Sales summary, traffic overview, product performance, and funnel analysis reports
* **UTM & Campaign Tracking** - Automatically capture UTM parameters and attribute sales to marketing campaigns
* **Real-Time Visitors** - Live counter showing visitors currently on your site
* **Custom Events & Goals** - Set up custom events and goals with minimal configuration
* **REST API** - Developer-friendly API for custom integrations and third-party dashboards
* **Modular Architecture** - Free core plugin with optional PRO addons for extended functionality

### Free Version Includes

* One-page overview dashboard with key metrics
* Basic pre-built reports (sales, traffic, products)
* Enhanced eCommerce tracking via WooCommerce hooks
* UTM and campaign tracking
* Simple funnel reports
* Customizable key metrics
* Basic event & goal tracking
* Real-time visitor counter

### PRO Addons (Available Separately)

* Advanced reports with drill-down and custom report builder
* Multi-step goal funnels with visual reports
* Automated goal suggestions
* Customer Lifetime Value (CLV) and segmentation
* Real-time heatmaps and session recordings
* Advanced campaign attribution and A/B testing insights
* Custom data export (CSV, JSON, XML)
* Security audit logs and data encryption

## Installation

### Minimum Requirements

* WordPress 6.4 or higher (tested up to WordPress 7.0)
* WooCommerce 8.0 or higher (tested up to WooCommerce 9.8)
* PHP 7.4 or higher
* Google Analytics account with GA4 property
* HPOS (High-Performance Order Storage) compatible

### Automatic Installation

1. Log in to your WordPress admin panel
2. Navigate to **Plugins** → **Add New**
3. Search for "StorePulse Analytics"
4. Click **Install Now** and then **Activate**

### Manual Installation

1. Download the plugin zip file
2. Upload it to `/wp-content/plugins/` directory via FTP
3. Extract the zip file
4. Activate the plugin through the **Plugins** menu in WordPress

### Setup

1. After activation, navigate to **Pulse Analytics** → **Settings**
2. Follow the guided setup wizard to connect your Google Analytics account
3. Configure your GA4 Property ID and authorize the plugin
4. Customize your dashboard widgets and preferences
5. Start tracking your WooCommerce store performance!

## Frequently Asked Questions

### Does this plugin work with Google Analytics 4 (GA4)?

Yes, Pulse Analytics is designed to work with Google Analytics 4 (GA4) properties. Make sure you have a GA4 property set up in your Google Analytics account.

### Do I need a Google Analytics account?

Yes, you need a Google Analytics account with a GA4 property. The plugin will guide you through the OAuth connection process during setup.

### Is WooCommerce required?

Yes, Pulse Analytics is specifically designed for WooCommerce stores and requires WooCommerce to be installed and active.

### Can I customize the dashboard?

Yes! The dashboard widgets can be rearranged via drag-and-drop, and you can choose which key metrics to display.

### Does the plugin track personal data?

The plugin tracks eCommerce events and analytics data. All data handling complies with GDPR and other data protection regulations. Personal data is anonymized where applicable.

### Can developers extend the plugin?

Yes! Pulse Analytics provides extensive hooks, filters, and REST API endpoints for developers to extend functionality and integrate with other systems.

### What's the difference between the free and PRO versions?

The free version includes essential analytics features. PRO addons provide advanced reporting, multi-step funnels, CLV tracking, heatmaps, custom exports, and more. Each PRO feature is available as a separate addon.

## Screenshots

1. **Dashboard Overview** - One-page dashboard with key metrics and customizable widgets
2. **Reports** - Detailed sales, traffic, and product performance reports
3. **Settings** - Easy-to-use settings interface with guided setup wizard
4. **Real-Time Visitors** - Live visitor counter and active session details
5. **Funnel Analysis** - Visual funnel reports showing conversion drop-offs

## External Services

This plugin communicates with the following external services:

### Google Analytics Data API
- **Purpose**: Used to fetch analytics reports and data for display in the WordPress admin dashboard.
- **Data sent**: Google Analytics property ID, date ranges, and metric/dimension filters.
- **When**: When viewing analytics reports in the dashboard.
- **Terms of Service**: https://developers.google.com/terms
- **Privacy Policy**: https://policies.google.com/privacy

### Google OAuth2 API
- **Purpose**: Used for authenticating with Google Analytics via OAuth2.
- **Data sent**: OAuth authorization code and refresh tokens.
- **When**: During initial setup and token refresh.
- **Terms of Service**: https://developers.google.com/terms
- **Privacy Policy**: https://policies.google.com/privacy

### Google PageSpeed Insights API
- **Purpose**: Used to fetch Core Web Vitals data for pages.
- **Data sent**: Page URLs to analyze.
- **When**: When viewing the Core Web Vitals report.
- **Terms of Service**: https://developers.google.com/terms
- **Privacy Policy**: https://policies.google.com/privacy

### ip-api.com Geolocation Service
- **Purpose**: Used for visitor geolocation (country and city) for the real-time visitor dashboard.
- **Data sent**: Visitor IP address (anonymized where possible).
- **When**: When a visitor accesses the frontend of the site.
- **Terms of Service**: https://ip-api.com/docs/legal
- **Privacy Policy**: https://ip-api.com/docs/legal

## Changelog

### 1.0.1
* WordPress 7.0 beta compatibility verified
* WooCommerce 9.8 compatibility verified
* Added HPOS (High-Performance Order Storage) compatibility declaration
* Added Cart & Checkout Blocks compatibility declaration
* Social Media Tracking and User Journey pages now available to all users
* Updated minimum requirements (WordPress 6.4+, WooCommerce 8.0+)

### 1.0.0
* Initial release
* Core dashboard with key metrics
* Basic pre-built reports
* Enhanced eCommerce tracking
* UTM and campaign tracking
* Real-time visitor counter
* Custom events and goals
* REST API endpoints

## Upgrade Notice

### 1.0.1
Compatibility update for WordPress 7.0 and WooCommerce 9.8. Adds HPOS support. Upgrade recommended for all users.

### 1.0.0
Initial release of StorePulse Analytics. Install and activate to start tracking your WooCommerce store performance.

## Support

For support, feature requests, and documentation, please visit:
* [Documentation](docs/)
* [GitHub Issues](https://code.zeeyes.com/wordpress/wpulse-for-google-analytics/-/issues)

## Development

### Project Structure

* `includes/` - Core plugin classes and functionality
* `admin/` - Admin interface pages and templates
* `assets/` - CSS, JavaScript, and image files
* `database/` - Database schema and migration files
* `docs/` - Documentation files

### Contributing

Contributions are welcome! Please refer to the documentation in the `docs/` folder for:
* Architecture design (`docs/Architecture-Design.md`)
* Module design (`docs/Module-Design.md`)
* Usability guidelines (`docs/Usability-Guide.md`)
* Project overview and tickets (`docs/PROJECT-OVERVIEW.md`)

### API Documentation

REST API endpoints are documented in `docs/Architecture-Design.md`. The base namespace is `/wp-json/wpulse/v1/`.

## Credits

Developed with ❤️ for the WooCommerce community by **fenzik** and **smackcoders**.

---

**Note:** This plugin requires an active Google Analytics account and WooCommerce installation. Make sure both are set up before installation.
