# Changelog

All notable changes to StorePulse for Google Analytics will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Traffic Overview Dashboard with Sessions/Pageviews toggle and period comparison
- Graph Annotations/Notes System with CRUD operations
- eCommerce Overview Report with campaign performance table
- Social Media Tracking Report with period comparison
- Campaign URL Tracking with transaction table and filters
- Link Report for inbound/outbound/affiliate/downloadable links with demographics
- Core Web Vitals Dashboard with Mobile/Desktop toggle and improvement recommendations
- User Journey report with conversion funnel visualization
- Guided setup wizard with progress bar and step-by-step navigation
- In-app help & tutorials with contextual tooltips
- Role-based access control with custom capabilities
- Currency symbol configuration from WooCommerce
- Geo API fallback when primary API unavailable
- Clear All Settings & Tokens server-side handler
- REST API documentation
- PRO: Custom Report Builder with drag-and-drop interface
- PRO: Multi-step Goal Funnels with visual reports
- PRO: Automated Goal Suggestions based on traffic patterns
- PRO: eCommerce Insights (CLV, Segmentation, Attribution)
- PRO: Real-time Alerts with configurable thresholds
- PRO: A/B Testing insights and variant tracking
- PRO: Custom Data Export (CSV, JSON, XML)
- PRO: Audit Logs for settings changes
- PRO: Drill-down functionality on dashboard KPI cards
- PRO: Period comparison UI (week-over-week, month-over-month)

### Changed
- REST API namespace standardized to `StorePulse/v1`
- All REST endpoints now require proper permission checks
- Error handling standardized across all GA API calls
- Menu structure consolidated (removed duplicate Admin_UI menus)
- Transient keys use consistent `StorePulse_` prefix

### Fixed
- Database column name mismatch (created_at vs event_timestamp)
- JSON extraction syntax for session_id and order_total
- Typo in admin notice ("Movied" → "Moved")
- get_top_countries AJAX handler uses correct method
- Currency symbol hardcoded in JavaScript
- Export data validation to handle empty arrays correctly
- Misplaced comments in JavaScript toggle code and CSV export function

### Security
- Added permission callbacks to all REST endpoints
- Token-based authentication for WooCommerce webhook endpoint
- Role-based access control with custom capabilities

## [1.0.0] - Initial Release

### Added
- Core dashboard with key metrics (sessions, revenue, conversion rate, AOV)
- Basic pre-built reports (sales, traffic, products)
- Enhanced eCommerce tracking via WooCommerce hooks
- UTM and campaign tracking
- Real-time visitor counter
- Custom events and goals
- REST API endpoints
- Google Analytics 4 (GA4) integration
- Database tables for events, aggregates, reports, logs, campaigns, sync
- Multisite support for database tables
- Plugin activation and uninstallation hooks
