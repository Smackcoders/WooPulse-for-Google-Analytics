# StorePulse Analytics

> Connects your WooCommerce store to GA4 over OAuth2 and backs it with a self-hosted local analytics store, so revenue, conversion, and visitor data show up on one WordPress dashboard.

![License](https://img.shields.io/badge/license-GPLv2%20or%20later-blue.svg)
![WordPress](https://img.shields.io/badge/WordPress-6.4%2B-21759B.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4.svg)

## Table of Contents

- [Overview](#overview)
- [Key Features](#key-features)
- [Use Cases](#use-cases)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Supported Integrations](#supported-integrations)
- [Screenshots](#screenshots)
- [Documentation](#documentation)
- [FAQ](#faq)
- [Roadmap](#roadmap)
- [Changelog](#changelog)
- [Security](#security)
- [Contributing](#contributing)
- [Support](#support)
- [License](#license)
- [Disclaimer](#disclaimer)
- [Author](#author)

## Overview

StorePulse Analytics is a GA4 WooCommerce integration for store owners who want a straight answer about how the shop is doing without cross-referencing order data against a separate reporting tool. It connects your WooCommerce store to a Google Analytics 4 property through OAuth2 and pulls sessions, revenue, device, browser, and geography data through the GA4 Data API — no manual tagging required once the connection is authorized.

What sets it apart from a thin GA4 wrapper is the local analytics layer running alongside it. Every visit and event is also logged to the plugin's own database tables, using cookie-based session IDs and a heartbeat check-in. That means real-time visitor counts, sales figures, and campaign attribution are available the moment traffic arrives, rather than waiting on GA4's processing delay. Store owners evaluating a Metorik alternative, or anyone tired of digging through Google Analytics's own interface for basic ecommerce numbers, get both an official GA4 pipeline and a fallback that never goes dark.

## Key Features

- **One-Page Dashboard** — Sessions, revenue, conversion rate, and average order value in a single customizable-widget view, backed by dedicated traffic, ecommerce, and sales-summary reports.
- **GA4 OAuth2 Connection & Data API Reporting** — A guided OAuth2 flow authorizes the plugin against your GA4 property; once connected, it pulls daily metrics, device, browser, top-pages, and top-countries data through the GA4 Data API.
- **Enhanced Ecommerce Tracking** — `view_item`, `add_to_cart`, and `purchase` events fire through a gtag.js snippet the plugin injects site-wide, with duplicate-purchase guarding on the order and full compatibility with WooCommerce Cart & Checkout Blocks.
- **Local Analytics Store & Real-Time Visitors** — An independent session and event tracking system with cookie-based session IDs and a heartbeat check, so the real-time visitors report keeps working even when GA4 has nothing to show yet.
- **UTM & Campaign URL Tracking** — Automatic UTM parameter capture, campaign URL tracking, social-referrer detection, and a link report for tying sales back to specific campaigns.
- **Custom Goals Engine** — Define conversion goals and follow a user journey report to see how visitors move from landing page to purchase.
- **Core Web Vitals Tracking** — LCP, CLS, and related metrics surfaced directly in WordPress admin, powered by the Google PageSpeed Insights API.
- **Newsletter Opt-in Capture** — Collects newsletter signups at checkout, no separate form plugin needed.
- **Daily Data Aggregation & Sync Now** — A scheduled daily wp-cron job aggregates data automatically; a manual Sync Now action is available whenever you want a refresh right away.
- **REST API & Role-Based Capabilities** — A dedicated `StorePulse/v1` REST namespace exposes dashboard data, settings, notes, and funnel endpoints, gated by two WordPress capabilities so shop managers or editors can view reports without full admin access.
- **HPOS & Cart/Checkout Blocks Compatibility** — Declares compatibility with WooCommerce High-Performance Order Storage and Cart & Checkout Blocks on the `before_woocommerce_init` hook.
- **Modular Architecture** — A free core plugin with optional Pro addons layered on top.
- **IP Anonymization & GDPR Toggle** — A settings toggle enables GA4's `anonymize_ip` flag on the gtag.js snippet. Note this applies to GA4 tracking only; the built-in visitor geolocation lookup sends the full IP to ip-api.com regardless of this toggle (see [Disclaimer](#disclaimer)).

### Free Version Includes

- One-page overview dashboard with key metrics
- Pre-built reports for sales, traffic, and products
- Enhanced ecommerce tracking via WooCommerce hooks
- UTM and campaign tracking
- Simple funnel reports
- Customizable key metrics
- Basic event and goal tracking
- Real-time visitor counter

### Pro Tier Features (Available Separately)

- Custom report builder with additional GA4 metrics and dimensions
- Multi-step goal funnels with visual funnel tracking
- Automated goal suggestions
- Customer Lifetime Value (CLV), segmentation, and attribution analysis
- Advanced campaign attribution and A/B testing insights
- Custom data export (CSV, JSON, XML)
- Security audit logs

Each Pro feature ships as a separate licensed addon. Heatmaps and session recordings are not built into StorePulse — the plugin's alert system flags where a third-party heatmap or session-recording service could plug in, but doesn't provide one itself.

## Use Cases

- **Track WooCommerce sales in Google Analytics** without manually cross-referencing order data against GA4 reports.
- **Watch real-time visitors on a WooCommerce store** and react to traffic spikes, launches, or ad campaigns as they happen.
- **Measure marketing campaign ROI** with UTM tracking, campaign attribution, and a link report for content and social promotions.
- **Keep dashboards populated when GA4 has no data yet** by relying on the plugin's local analytics store as a fallback layer.
- **Monitor Core Web Vitals and store performance** from inside WordPress admin instead of a separate tool.
- **Grant limited analytics access** to a shop manager or editor role without exposing full site administration.
- **Evaluate a Metorik alternative** for teams that want native GA4 reporting plus local analytics inside WordPress itself.

## Requirements

| Requirement | Version |
| --- | --- |
| WordPress | 6.4 or higher (tested up to 7.0) |
| PHP | 7.4 or higher |
| WooCommerce | 8.0 or higher (tested up to 9.8) |
| Google Analytics | A GA4 property, for GA4-connected reporting (the local analytics store works without one) |

## Installation

### Install from WordPress

1. Log in to your WordPress admin dashboard.
2. Go to **Plugins → Add New Plugin → Upload Plugin**.
3. Choose the StorePulse Analytics ZIP file and click **Install Now**.
4. Click **Activate** once installation finishes.

### Manual Installation

1. Download this repository or clone it.
2. Upload the plugin folder to `/wp-content/plugins/` via FTP, SFTP, or your hosting file manager.
3. Log in to WordPress admin and go to **Plugins**.
4. Locate StorePulse Analytics in the list and click **Activate**.

## Configuration

1. After activation, go to **Pulse Analytics → Settings**.
2. Follow the guided setup wizard to connect your Google Analytics account through OAuth2.
3. Enter your GA4 Property ID and authorize the plugin.
4. Turn on the IP anonymization toggle if required for your region.
5. Set up dashboard widgets, key metrics, and role-based capabilities for shop managers or editors.
6. Save your settings — the local analytics store starts collecting data immediately, even before the first GA4 sync completes.

## Usage

Once connected, open **Pulse Analytics** from the WordPress admin menu to see the one-page dashboard: sessions, revenue, conversion rate, and average order value at a glance. From there:

- Drill into **Traffic Overview**, **Ecommerce Overview**, **Sales Summary**, and **Product Performance** reports.
- Check the **Real-Time Visitors** report for live session counts.
- Review the **User Journey** and **Social Media Tracking** reports to see how customers reach and move through your store.
- Trigger **Sync Now** any time you want an immediate data refresh instead of waiting for the daily aggregation job.
- Set up **Custom Goals** to track conversions specific to your store, such as newsletter signups or purchases of a particular product.

## Supported Integrations

- WooCommerce, including HPOS and Cart & Checkout Blocks
- Google Analytics 4 (GA4) Data API
- Google OAuth2 API
- Google PageSpeed Insights API (Core Web Vitals)
- ip-api.com geolocation service (visitor country and city)

## Screenshots

This repository snapshot doesn't include image assets. Current screenshots of the dashboard, reports, and setup wizard are available from Smackcoders at [smackcoders.com/wordpress.html](https://www.smackcoders.com/wordpress.html).

## Documentation

Full documentation lives in the [`docs/`](docs/) directory, including:

- [Architecture Design](docs/Architecture-Design.md) — REST API namespace and system design (base namespace: `/wp-json/StorePulse/v1/`)
- [Module Design](docs/Module-Design.md)
- [Developer Guide](docs/DEVELOPER.md)
- [Usability Guide](docs/Usability-Guide.md)
- [Changelog](docs/CHANGELOG.md)

## FAQ

### Does this plugin work with Google Analytics 4?

Yes. StorePulse Analytics is built around the GA4 Data API. Set up a GA4 property in your Google Analytics account, then connect it through OAuth2 during setup.

### Do I need a Google Analytics account?

For GA4-connected reporting, yes — you'll need a Google Analytics account with a GA4 property, and the plugin walks you through the OAuth2 connection. Without one, the local analytics store still tracks sessions, events, and real-time visitors on its own, so core dashboards keep working before a GA4 connection exists.

### Is WooCommerce required?

Yes. StorePulse Analytics is built specifically for WooCommerce stores and requires WooCommerce to be installed and active.

### Does it support HPOS and Cart/Checkout Blocks?

Yes. The plugin declares compatibility with WooCommerce High-Performance Order Storage and Cart & Checkout Blocks on the `before_woocommerce_init` hook.

### Does it track add-to-cart and purchase events?

Yes. `view_item`, `add_to_cart`, and `purchase` events are sent through the gtag.js snippet the plugin injects, with duplicate-purchase guarding so a page refresh on the thank-you page doesn't double-count a conversion.

### Can I see real-time visitors?

Yes. The dashboard includes a real-time visitors report built on cookie-based session IDs and a heartbeat check-in.

### Can I customize the dashboard?

Yes. Widgets can be rearranged, and you choose which key metrics show on the overview page.

### Does the plugin track personal data?

It tracks ecommerce events, session identifiers, and visitor geolocation (country and city, resolved via ip-api.com). The IP anonymization toggle sets GA4's `anonymize_ip` flag for data sent to Google, but does not affect the IP address the plugin sends to ip-api.com for geolocation — review your own regional compliance requirements before relying on this toggle for GDPR purposes.

### Can developers extend the plugin?

Yes. StorePulse Analytics exposes REST endpoints under the `StorePulse/v1` namespace for dashboard data, settings, notes, and funnels, along with hooks and filters documented in the [Developer Guide](docs/DEVELOPER.md) for custom integrations.

### What's included in the Pro tier?

The free version covers core dashboard, reporting, and tracking needs. Pro addons add a custom report builder, multi-step goal funnels, CLV and segmentation, A/B testing insights, custom data export, and security audit logs. Each Pro feature is licensed as a separate addon.

## Roadmap

Planned areas of investment include deeper attribution reporting, expanded A/B testing tooling, and additional data export formats for the Pro tier. Features are only added to this list once confirmed for development — see [GitHub Issues](https://github.com/Smackcoders/WooPulse-for-Google-Analytics/issues) for active discussion.

## Changelog

### 1.0.1

- WordPress 7.0 beta compatibility verified
- WooCommerce 9.8 compatibility verified
- Added HPOS (High-Performance Order Storage) compatibility declaration
- Added Cart & Checkout Blocks compatibility declaration
- Social Media Tracking and User Journey pages now available to all users
- Updated minimum requirements (WordPress 6.4+, WooCommerce 8.0+)
- **Upgrade notice:** Compatibility update for WordPress 7.0 and WooCommerce 9.8. Adds HPOS support. Upgrade recommended for all users.

### 1.0.0

- Initial release
- Core dashboard with key metrics
- Pre-built reports for sales, traffic, and products
- Enhanced ecommerce tracking
- UTM and campaign tracking
- Real-time visitor counter
- Custom events and goals
- REST API endpoints
- **Upgrade notice:** Initial release of StorePulse Analytics. Install and activate to start tracking your WooCommerce store performance.

Full history: [docs/CHANGELOG.md](docs/CHANGELOG.md)

## Security

GA4 OAuth2 access and refresh tokens are stored in the WordPress options table and are only reachable by users holding the `StorePulse_manage_settings` capability. REST endpoints are gated by WordPress capability checks (`StorePulse_view_reports` for read access, `StorePulse_manage_settings` for configuration) rather than open to anonymous requests. If you discover a security vulnerability, please don't disclose it publicly in a GitHub issue — report it privately via [GitHub Issues](https://github.com/Smackcoders/WooPulse-for-Google-Analytics/issues) marked confidential, or contact Smackcoders directly through [smackcoders.com](https://www.smackcoders.com/wordpress.html) so it can be triaged before public disclosure.

## Contributing

Contributions are welcome. Before submitting a pull request, please review:

- [Architecture Design](docs/Architecture-Design.md)
- [Module Design](docs/Module-Design.md)
- [Usability Guide](docs/Usability-Guide.md)

Project structure:

- `includes/` — Core plugin classes and functionality
- `admin/` — Admin interface pages and templates
- `assets/` — CSS, JavaScript, and image files
- `database/` — Database schema and migration files
- `docs/` — Documentation files

Bug reports and feature suggestions can be filed through GitHub Issues linked below.

## Support

For support, feature requests, and documentation, please visit:

- [Documentation](docs/)
- [GitHub Issues](https://github.com/Smackcoders/WooPulse-for-Google-Analytics/issues)

## License

GPLv2 or later. See [gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html) for full license text.

## Disclaimer

This plugin requires an active Google Analytics account and an active WooCommerce installation — set up both before installing. Google Analytics, Google Analytics 4, and Google PageSpeed Insights are products of Google LLC; StorePulse Analytics is an independent integration and isn't affiliated with, endorsed by, or sponsored by Google. WooCommerce is a trademark of Automattic Inc.

### External Services

This plugin communicates with the following external services:

**Google Analytics Data API**

- Purpose: Fetches analytics reports and data for display in the WordPress admin dashboard.
- Data sent: Google Analytics property ID, date ranges, and metric/dimension filters.
- When: When viewing analytics reports in the dashboard.
- Terms of Service: https://developers.google.com/terms
- Privacy Policy: https://policies.google.com/privacy

**Google OAuth2 API**

- Purpose: Authenticates the plugin with Google Analytics.
- Data sent: OAuth authorization code and refresh tokens.
- When: During initial setup and token refresh.
- Terms of Service: https://developers.google.com/terms
- Privacy Policy: https://policies.google.com/privacy

**Google PageSpeed Insights API**

- Purpose: Fetches Core Web Vitals data for pages.
- Data sent: Page URLs to analyze.
- When: When viewing the Core Web Vitals report.
- Terms of Service: https://developers.google.com/terms
- Privacy Policy: https://policies.google.com/privacy

**ip-api.com Geolocation Service**

- Purpose: Resolves visitor country and city for the real-time visitor dashboard.
- Data sent: Visitor IP address. This lookup always uses the full IP; the plugin's IP anonymization setting applies to GA4 tracking only, not this service.
- When: When a visitor accesses the frontend of the site.
- Terms of Service: https://ip-api.com/docs/legal
- Privacy Policy: https://ip-api.com/docs/legal

## Author

Developed and maintained by **Smackcoders**, with contributions from fenzik. Visit [smackcoders.com](https://www.smackcoders.com/wordpress.html) for more WordPress and WooCommerce plugins.
