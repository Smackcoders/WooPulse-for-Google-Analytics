# StorePulse Analytics — WooCommerce Google Analytics Plugin

A WooCommerce Google Analytics plugin that connects your store to GA4, adds a self-hosted local analytics fallback, and puts real-time visitors, revenue, and conversion data on one WordPress dashboard.

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

## Overview

StorePulse Analytics is a WooCommerce ecommerce tracking plugin built for store owners who need clear, real-time answers about how their shop is performing — without digging through the standard Google Analytics 4 wordpress plugin interface. It works as a GA4 WooCommerce integration, connecting your store to your Google Analytics 4 property via OAuth2, while also running its own local analytics store so you get real time visitor tracking, sales data, and campaign attribution even before GA4 data is fully processed.

The plugin is designed for WooCommerce store owners, marketers, and developers who want a woocommerce analytics dashboard plugin that combines official GA4 reporting with a privacy-friendly, self-hosted fallback system — so dashboards keep working even when GA4 has no data yet. It is a practical alternative to relying solely on the Google Analytics web interface, and a lighter-weight option for teams evaluating a Metorik alternative or another woocommerce analytics alternative.

## Key Features

* **One-Page Dashboard** — Consolidated display of key metrics (sessions, revenue, conversion rate, average order value) with customizable widgets, powered by an admin dashboard suite covering traffic, ecommerce, and sales summary reports.
* **GA4 OAuth2 Connection & Data API Reporting** — Secure Google OAuth2 connection flow to your Google Analytics 4 property, with GA4 Data API reporting for daily metrics, device, browser, top pages, and top countries data.
* **Enhanced eCommerce Tracking** — Enhanced ecommerce events (view item, add to cart, purchase) fired via gtag.js snippet injection, with duplicate purchase guarding and Cart & Checkout Blocks compatibility.
* **Local Analytics Store & Real-Time Visitors** — A local event and session tracking system with cookie-based session IDs and heartbeat session tracking, giving you real-time visitor tracking and a real time visitors report even when GA4 data is delayed.
* **UTM & Campaign URL Tracking** — Automatic UTM source detection, campaign URL tracking, social referrer detection, and a link report for attributing sales to specific marketing campaigns.
* **Custom Goals Engine** — Build custom conversion goals and a user journey report to understand how visitors move from landing page to purchase.
* **Core Web Vitals Tracking** — A dedicated Core Web Vitals report inside WordPress, powered by the Google PageSpeed Insights API.
* **Newsletter Opt-in Capture** — Capture newsletter signups directly at checkout.
* **Daily Data Aggregation & Sync Now** — Scheduled daily wp-cron aggregation plus a manual "Sync Now" action for on-demand refreshes.
* **REST API & Role-Based Capabilities** — Developer-friendly REST API namespaces (dashboard data, settings, notes, funnels) and role-based capabilities so you can grant shop managers or editors access to analytics reports without giving full admin access.
* **HPOS & Cart/Checkout Blocks Compatibility** — Declared compatibility with WooCommerce High-Performance Order Storage and Cart & Checkout Blocks.
* **Modular Architecture** — Free core plugin with optional Pro tier addons for extended functionality.
* **Ip Anonymization & GDPR Toggle** — Privacy-friendly analytics plugin design with an IP anonymization toggle for visitor geolocation.

### Free Version Includes

* One-page overview dashboard with key metrics
* Basic pre-built reports (sales, traffic, products)
* Enhanced eCommerce tracking via WooCommerce hooks
* UTM and campaign tracking
* Simple funnel reports
* Customizable key metrics
* Basic event & goal tracking
* Real-time visitor counter

### Pro Tier Features (Available Separately)

* Advanced reports with drill-down and custom report builder
* Multi-step goal funnels with visual funnel tracking reports
* Automated goal suggestions
* Customer Lifetime Value (CLV) and segmentation
* Real-time heatmaps and session recordings
* Advanced campaign attribution and A/B testing insights
* Custom data export (CSV, JSON, XML)
* Security audit logs and data encryption

## Use Cases

* **Track WooCommerce sales in Google Analytics** without manually cross-referencing order data against GA4 reports.
* **See real-time visitors on a WooCommerce store** and react to traffic spikes, launches, or ad campaigns as they happen.
* **Measure marketing campaign ROI** with UTM tracking, campaign attribution, and a link report for content and social promotions.
* **Recover visibility when GA4 shows no data yet** by relying on the plugin's self-hosted local analytics store as a fallback.
* **Monitor Core Web Vitals and store performance** from inside the WordPress admin instead of a separate tool.
* **Grant limited analytics access** to a shop manager or editor role without exposing full site administration.
* **Evaluate a Metorik alternative** for teams that want native GA4 integration plus local analytics inside WordPress itself.

## Requirements

* **WordPress:** 6.4 or higher (tested up to WordPress 7.0)
* **PHP:** 7.4 or higher
* **WooCommerce:** 8.0 or higher (tested up to WooCommerce 9.8), including HPOS (High-Performance Order Storage) compatibility
* **Other requirements:** A Google Analytics account with a GA4 property (for GA4-connected reporting; the local analytics store works even without one)

## Installation

### Install from WordPress

1. Log in to your WordPress admin panel.
2. Navigate to **Plugins → Add New**.
3. Search for "StorePulse Analytics".
4. Click **Install Now** and then **Activate**.

### Manual Installation

1. Download the plugin ZIP file, or clone this repository.
2. Upload the plugin folder to `/wp-content/plugins/` (via FTP or your hosting file manager).
3. Extract the ZIP file if needed.
4. Activate the plugin from **WordPress Admin → Plugins**.

## Configuration / Setup

1. After activation, navigate to **Pulse Analytics → Settings**.
2. Follow the guided setup wizard to connect your Google Analytics account via OAuth2.
3. Configure your GA4 Property ID and authorize the plugin.
4. Enable the IP anonymization toggle and review GDPR compliance settings if required for your region.
5. Customize your dashboard widgets, key metrics, and role-based capabilities for shop managers or editors.
6. Start tracking your WooCommerce store performance — the local analytics store begins collecting data immediately, even before GA4 sync completes.

## Usage

Once connected, open **Pulse Analytics** from the WordPress admin menu to view the one-page dashboard: sessions, revenue, conversion rate, and average order value at a glance. From there you can:

* Drill into **Traffic Overview**, **Ecommerce Overview**, **Sales Summary**, and **Product Performance** reports.
* Check the **Real-Time Visitors** report for live session counts.
* Review the **User Journey** and **Social Media Tracking** reports to see how customers reach and move through your store.
* Trigger a manual **Sync Now** action any time you want an immediate data refresh instead of waiting for the daily aggregation.
* Set up **Custom Goals** to track conversions specific to your store (e.g., newsletter signups, specific product purchases).

## Supported Integrations

* WooCommerce (including HPOS and Cart & Checkout Blocks)
* Google Analytics 4 (GA4) Data API
* Google OAuth2 API
* Google PageSpeed Insights API (Core Web Vitals)
* ip-api.com Geolocation Service (visitor country/city)

## Screenshots / Demo

1. **Dashboard Overview** — One-page dashboard with key metrics and customizable widgets.
2. **Reports** — Detailed sales, traffic, and product performance reports.
3. **Settings** — Easy-to-use settings interface with guided setup wizard.
4. **Real-Time Visitors** — Live visitor counter and active session details.
5. **Funnel Analysis** — Visual funnel reports showing conversion drop-offs.

## Documentation

Full documentation is available in the [`docs/`](docs/) directory, including:

* [Architecture Design](docs/Architecture-Design.md) — REST API namespaces and system design (base namespace: `/wp-json/wpulse/v1/`)
* [Module Design](docs/Module-Design.md)
* [Developer Guide](docs/DEVELOPER.md)
* [Usability Guide](docs/Usability-Guide.md)
* [Changelog](docs/CHANGELOG.md)

## Frequently Asked Questions

### Does this plugin work with Google Analytics 4 (GA4)?

Yes. StorePulse Analytics is a GA4 WooCommerce integration built around the GA4 Data API. Make sure you have a GA4 property set up in your Google Analytics account, then connect it via OAuth2 during setup.

### Do I need a Google Analytics account?

For GA4-connected reporting, yes — you'll need a Google Analytics account with a GA4 property, and the plugin guides you through the OAuth2 connection process. The plugin's local analytics store also tracks sessions, events, and real-time visitors independently, so core dashboards keep working even before a GA4 connection is completed or while GA4 has no data yet.

### Is WooCommerce required?

Yes, StorePulse Analytics is specifically designed for WooCommerce stores and requires WooCommerce to be installed and active.

### Does it support HPOS and Cart/Checkout Blocks?

Yes. The plugin declares compatibility with WooCommerce High-Performance Order Storage (HPOS) and Cart & Checkout Blocks on the `before_woocommerce_init` hook.

### Does it track add-to-cart and purchase events?

Yes. Enhanced ecommerce events — view item, add to cart, and purchase — are tracked via gtag.js injection, with duplicate purchase guarding to prevent double-counted conversions.

### Can I see real-time visitors?

Yes, the dashboard includes a real-time visitor tracking report using cookie-based session IDs and heartbeat session tracking.

### Can I customize the dashboard?

Yes. Dashboard widgets can be rearranged, and you can choose which key metrics to display.

### Does the plugin track personal data?

The plugin tracks ecommerce events and analytics data, with an IP anonymization toggle and GDPR compliance considerations. Personal data is anonymized where applicable.

### Can developers extend the plugin?

Yes. StorePulse Analytics provides REST API namespaces for dashboard data, settings, notes, and funnels, along with hooks and filters for custom integrations and third-party dashboards.

### What is included in the Pro tier?

The free version includes essential analytics features. Pro tier addons add advanced report building, multi-step goal funnels, CLV tracking, heatmaps, A/B testing insights, custom data export, and audit logging. Each Pro feature is available as a separate addon with its own license check.

## Roadmap

Planned areas of investment include deeper attribution reporting, expanded A/B testing tooling, and additional data export formats for the Pro tier. Features are only added to this list once confirmed for development — see [GitHub Issues](https://code.zeeyes.com/wordpress/wpulse-for-google-analytics/-/issues) for active discussion.

## Changelog

### 1.0.1
* WordPress 7.0 beta compatibility verified
* WooCommerce 9.8 compatibility verified
* Added HPOS (High-Performance Order Storage) compatibility declaration
* Added Cart & Checkout Blocks compatibility declaration
* Social Media Tracking and User Journey pages now available to all users
* Updated minimum requirements (WordPress 6.4+, WooCommerce 8.0+)
* **Upgrade notice:** Compatibility update for WordPress 7.0 and WooCommerce 9.8. Adds HPOS support. Upgrade recommended for all users.

### 1.0.0
* Initial release
* Core dashboard with key metrics
* Basic pre-built reports
* Enhanced eCommerce tracking
* UTM and campaign tracking
* Real-time visitor counter
* Custom events and goals
* REST API endpoints
* **Upgrade notice:** Initial release of StorePulse Analytics. Install and activate to start tracking your WooCommerce store performance.

Full history: [docs/CHANGELOG.md](docs/CHANGELOG.md)

## Security

StorePulse Analytics stores GA4 OAuth2 refresh tokens securely and uses token-authenticated REST endpoints. If you discover a security vulnerability, please do not disclose it publicly in a GitHub issue. Instead, report it privately via [GitHub Issues](https://code.zeeyes.com/wordpress/wpulse-for-google-analytics/-/issues) marked confidential, or contact Smackcoders directly through [smackcoders.com](https://www.smackcoders.com/wordpress.html) so the issue can be triaged before public disclosure.

## Contributing

Contributions are welcome. Before submitting a pull request, please review:

* Architecture design ([`docs/Architecture-Design.md`](docs/Architecture-Design.md))
* Module design ([`docs/Module-Design.md`](docs/Module-Design.md))
* Usability guidelines ([`docs/Usability-Guide.md`](docs/Usability-Guide.md))
* Project overview and tickets ([`docs/PROJECT-OVERVIEW.md`](docs/PROJECT-OVERVIEW.md))

Project structure:

* `includes/` — Core plugin classes and functionality
* `admin/` — Admin interface pages and templates
* `assets/` — CSS, JavaScript, and image files
* `database/` — Database schema and migration files
* `docs/` — Documentation files

Bug reports and feature suggestions can be filed through GitHub Issues linked below.

## Support

For support, feature requests, and documentation, please visit:

* [Documentation](docs/)
* [GitHub Issues](https://code.zeeyes.com/wordpress/wpulse-for-google-analytics/-/issues)

## License

GPLv2 or later. See [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html) for full license text.

## Disclaimer

This plugin requires an active Google Analytics account and an active WooCommerce installation — make sure both are set up before installation. Google Analytics, Google Analytics 4 (GA4), and Google PageSpeed Insights are products of Google LLC; StorePulse Analytics is an independent integration and is not affiliated with, endorsed by, or sponsored by Google. WooCommerce is a trademark of Automattic Inc.

### External Services

This plugin communicates with the following external services:

**Google Analytics Data API**
- Purpose: Used to fetch analytics reports and data for display in the WordPress admin dashboard.
- Data sent: Google Analytics property ID, date ranges, and metric/dimension filters.
- When: When viewing analytics reports in the dashboard.
- Terms of Service: https://developers.google.com/terms
- Privacy Policy: https://policies.google.com/privacy

**Google OAuth2 API**
- Purpose: Used for authenticating with Google Analytics via OAuth2.
- Data sent: OAuth authorization code and refresh tokens.
- When: During initial setup and token refresh.
- Terms of Service: https://developers.google.com/terms
- Privacy Policy: https://policies.google.com/privacy

**Google PageSpeed Insights API**
- Purpose: Used to fetch Core Web Vitals data for pages.
- Data sent: Page URLs to analyze.
- When: When viewing the Core Web Vitals report.
- Terms of Service: https://developers.google.com/terms
- Privacy Policy: https://policies.google.com/privacy

**ip-api.com Geolocation Service**
- Purpose: Used for visitor geolocation (country and city) for the real-time visitor dashboard.
- Data sent: Visitor IP address (anonymized where possible).
- When: When a visitor accesses the frontend of the site.
- Terms of Service: https://ip-api.com/docs/legal
- Privacy Policy: https://ip-api.com/docs/legal

## Author / Maintainer

Developed and maintained by **Smackcoders**, with contributions from fenzik. Visit [smackcoders.com](https://www.smackcoders.com/wordpress.html) for more WordPress and WooCommerce plugins.
