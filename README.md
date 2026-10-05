# Pulse Analytics for Google Analytics 4

Connects WordPress to Google Analytics 4 and see live traffic, form conversions, affiliate clicks, and audience insights right inside your dashboard.

[![WordPress Version](https://img.shields.io/badge/WordPress-6.4%2B-blue.svg)](https://wordpress.org/)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-green.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPLv2-lightgray.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

## Table of Contents
- [Overview](#overview)
- [Key Features](#key-features)
- [Free Version Includes](#free-version-includes)
- [Pro Tier Features (Available Separately)](#pro-tier-features-available-separately)
- [Use Cases](#use-cases)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Supported Integrations](#supported-integrations)

- [Documentation](#documentation)
- [FAQ](#faq)
- [Roadmap](#roadmap)
- [Changelog](#changelog)
- [Security](#security)
- [Contributing](#contributing)
- [Support](#support)
- [License](#license)
- [Disclaimer](#disclaimer)
- [External Services](#external-services)
- [Author](#author)

## Overview
Pulse Analytics brings Google Analytics 4 into your WordPress admin, designed for blogs, business sites, and online stores. Instead of logging into a separate Google Analytics account and digging through menus, you connect your GA4 property once, and your numbers show up exactly where you work. 

It is built for publishers who want to see what content earns attention, marketers tracking forms and leads, affiliate marketers measuring link clicks, and eCommerce store owners relying on WooCommerce, Easy Digital Downloads, MemberPress, or GiveWP. This is a reporting plugin for GA4—not a replacement for it. Your analytics data stays safely within your own GA4 property.

## Key Features
- **GA4 Dashboard Inside WordPress** — The dashboard runs on live GA4 data showing active visitors, sessions, page views, average session duration, and engagement rate. Supporting widgets break down new vs. returning visitors, device type, browsers, top pages, and visitor geography.
- **Forms Conversion Report** — Automatically track form impressions, starts, and conversions across your site with no manual coding. Supports WPForms, Gravity Forms, Contact Form 7, Formidable Forms, Ninja Forms, Forminator, Elementor, and Divi forms.
- **Affiliate and Outbound Link Tracking** — Affiliate and outbound clicks are tracked automatically without manual tagging. Match domains to partner names and track specific affiliate URL prefixes.
- **Downloadable Link Tracking** — Automatically track file downloads (PDF, ZIP, DOCX, MP3, MP4, etc.) to measure lead magnets and podcast performance.
- **Global Date Picker** — A unified date picker (Last 7 Days, Last 30 Days, Last 90 Days, Custom Range) ensures every widget, chart, and table aligns perfectly.
- **Session and Engagement Tracking** — A lightweight session heartbeat records active sessions and time on page to feed your engagement metrics. 
- **Privacy Controls** — IP addresses are always anonymized in frontend tracking automatically, and link tracking respects visitor privacy and cookie consent.

## Free Version Includes
- Dashboard Overview with live KPIs and traffic trend charts
- Audience Breakdown (New vs. Returning, Device, Browser, Top Pages, Geography)
- Global date picker functionality
- Advanced Forms Conversion report (Impressions, Starts, Conversions)
- Affiliate and Outbound Link tracking (Partners, Source Pages)
- Downloadable File tracking
- Session heartbeat and engagement metrics
- Privacy controls and automatic IP anonymization

## Pro Tier Features (Available Separately)
Everything in the free version is completely free with no trial period. **WP Pulse Analytics Pro** is a separate, optional upgrade that includes:
- **Real-Time Visitors** — Live report with active users and visitor geography.
- **eCommerce Analytics** — Advanced reporting for WooCommerce covering revenue, funnels, cart abandonment, and coupons.
- **Traffic Overview & AI Referral Tracking** — Channels, landing pages, source/medium, and AI traffic (ChatGPT, Claude, Gemini).
- **Google Search Console Integration** — Search queries, impressions, CTR, and average positions inside WordPress.
- **Advanced Reporting & AI Analytics (Ask AI)** — Ask plain-English questions about your GA4 data.
- **User Journey Tracking** — Visual path from a user's first click to checkout.
- **Campaign & UTM Builder** — Build tracking links matched back to GA4 performance.
- **Site Performance** — Google PageSpeed Insights integration for Core Web Vitals.
- **EU Compliance & Privacy** — Consent-gated tracking, PII stripping, and visitor opt-out for GDPR.

*Each Pro feature is part of the comprehensive premium addon. Heatmaps and session recordings are not built into Pulse Analytics.*

## Use Cases
- **Bloggers and publishers** who want to quickly see what their audience reads without leaving WordPress.
- **Business site owners** monitoring traffic and engagement on their corporate sites.
- **Marketers** who need to see which forms and outbound links generate leads and conversions.
- **Affiliate marketers** tracking which partners and pages earn clicks natively.
- **Agencies and freelancers** looking for an easy analytics setup for client sites.
- **WooCommerce, EDD, MemberPress, and GiveWP users** needing analytics seamlessly integrated into their store management flow.

## Requirements

| Requirement | Version |
|---|---|
| WordPress | 6.4 or higher |
| PHP | 7.4 or higher |
| Google Analytics | A Google Analytics 4 property |

*(WooCommerce is not required. The core plugin works perfectly on any standard WordPress site. However, commerce tracking widgets will activate if WooCommerce, EDD, MemberPress, or GiveWP are detected).*

## Installation

**Install from WordPress**
1. Log in to your WordPress admin dashboard.
2. Go to **Plugins -> Add New**.
3. Search for "WP Pulse Analytics".
4. Click **Install Now**, then **Activate**.

**Manual Installation**
1. Download the plugin ZIP file.
2. Upload the folder to `/wp-content/plugins/` via FTP, SFTP, or your hosting file manager.
3. Log in to WordPress admin and go to **Plugins**.
4. Locate the plugin in the list and click **Activate**.

## Configuration
1. After activation, go to **Pulse Analytics -> Settings -> Analytics Configuration**.
2. Enter your **GA4 Measurement ID** (starts with G-) and **GA4 Property ID**.
3. Enter your **Google Client ID** and **Google Client Secret** from Google Cloud Console.
4. Copy the Authorized Redirect URI shown on the page and add it to your OAuth client in Google Cloud Console.
5. Click **Save Analytics Configuration**, then click **Sign in with Google** to authorize.
6. Verify your connection under Google Analytics Status.

## Usage
Once connected, open **Pulse Analytics -> Dashboard**:
- Drill into Top Pages, Top Countries, and Source/Medium from the View selector.
- Use the **Global Date Picker** at the top to change reporting periods.
- Check the **Forms Conversion report** to monitor form impressions and abandonment.
- Navigate to the **Audience & Links report** to monitor affiliate clicks, outbound links, and file downloads.
- Trigger **Sync Data** anytime to refresh cached report data on demand.

## Supported Integrations
- Google Analytics 4 (GA4) Data API and Measurement Protocol
- Google OAuth2 API
- WooCommerce, Easy Digital Downloads, MemberPress, GiveWP (for commerce detection)
- Form Plugins: WPForms, Gravity Forms, Contact Form 7, Formidable Forms, Ninja Forms, Forminator, Elementor forms, Divi forms.



## Documentation
Full documentation is available on our website:
- [WP Pulse Analytics Documentation](https://www.smackcoders.com/documentation/wp-pulse-analytics)
- Covers setup, module configuration, and developer hooks.

## FAQ

**Do I need a Google Analytics account to use this plugin?**
Yes. You need a free Google Analytics 4 property at analytics.google.com. Universal Analytics is not supported.

**Do I need WooCommerce to use this plugin?**
No. The core dashboard, Forms Conversion, Affiliate Links, and Audience reports work on any WordPress site. E-commerce platforms are just auto-detected for expanded widget features.

**Which form plugins are tracked?**
WPForms, Gravity Forms, Contact Form 7, Formidable Forms, Ninja Forms, Forminator, Elementor forms, Divi forms, WooCommerce forms, WordPress comment forms, and generic HTML forms are tracked automatically.

**Does this plugin track affiliate and outbound links automatically?**
Yes. Simply add your affiliate URL prefixes or domains under Affiliate Link Tracking settings, and every matching click is reported without manual tagging.

**Is this plugin GDPR compatible?**
Visitor IP addresses are anonymized automatically in frontend tracking. Link tracking can wait for cookie consent. You remain responsible for your own consent banner, but our Pro EU Compliance module provides deeper PII stripping and visitor opt-outs.

## Roadmap
We continuously refine our tracking engine based on WordPress ecosystem changes. Upcoming integrations include enhanced data portability, broader third-party form plugin support, and deeper REST API endpoints for developers.

## Changelog
**1.0**
- Initial public release of WP Pulse Analytics.
- Added GA4-powered analytics dashboard with traffic KPIs, charts, and live analytics data.
- Added Affiliate and outbound link tracking.
- Added Form tracking with automatic submission tracking.
- Added Session heartbeat and page engagement tracking.
- Added Google Analytics 4 OAuth connection and configuration.

## Security
OAuth client secrets are stored encrypted in your database. The plugin leverages WordPress REST API with proper capability checks (`manage_options`). If you discover a security vulnerability, please report it privately to `support@smackcoders.com` before public disclosure.

## Contributing
Developer hooks and REST API endpoints are available for custom extensions. We welcome bug reports and feature requests via email or our support portal.

## Support
For help, feature requests, and documentation:
- [Documentation](https://www.smackcoders.com/documentation/wp-pulse-analytics)
- Email: `support@smackcoders.com`

## License
GPLv2 or later. See gnu.org/licenses/gpl-2.0.html for full license text.

## Disclaimer
This plugin requires an active Google Analytics 4 property. Google Analytics is a product of Google LLC; Pulse Analytics is an independent integration and isn't affiliated with, endorsed by, or sponsored by Google.

## External Services
This plugin communicates with the following external services:

**Google Analytics 4 (Google Tag and Measurement Protocol)**
- **Purpose**: Enqueues the official Google tag script and sends page views and interaction events.
- **Data sent**: Form views, link clicks, file downloads, sessions.
- **Terms of Service**: https://marketingplatform.google.com/about/analytics/terms/us/
- **Privacy Policy**: https://policies.google.com/privacy

**Google OAuth and the Google Analytics Data API**
- **Purpose**: Authenticates the plugin with Google Analytics to read your reports.
- **Data sent**: OAuth authorization code, access tokens, and Property ID.
- **Terms of Service**: https://developers.google.com/terms
- **Privacy Policy**: https://policies.google.com/privacy

You are responsible for informing your visitors about this data collection and for obtaining consent where the law requires it.

## Author
Developed and maintained by **Smackcoders**, with contributions from premairuthayarajan, fenzik, and smackmarketing. Visit [smackcoders.com](https://www.smackcoders.com/) for more WordPress plugins.
