# WPulse Testing Guide

This document provides a comprehensive testing guide for developers and QA teams.

## Pre-Testing Setup

1. **WordPress Environment**
   - WordPress 5.0+ installed
   - WooCommerce 3.0+ installed and activated
   - PHP 7.4+ with JSON extension enabled
   - MySQL/MariaDB database

2. **Google Analytics Setup**
   - Google Analytics 4 (GA4) property created
   - OAuth 2.0 credentials from Google Cloud Console:
     - Client ID
     - Client Secret
   - Property ID from GA4 Admin settings

3. **Plugin Installation**
   - Upload plugin to `/wp-content/plugins/wpulse-for-google-analytics/`
   - Activate plugin through WordPress admin
   - Database tables will be created automatically

## Testing Checklist

### Core Functionality

#### T-01: REST API Security & Namespace
- [ ] All REST endpoints use namespace `/wp-json/wpulse/v1/`
- [ ] Endpoints require `manage_options` or `StorePulse_view_reports` capability
- [ ] Unauthenticated requests return 401/403
- [ ] Subscriber role cannot access endpoints

#### T-02: Dashboard Refresh
- [ ] Click refresh button on dashboard
- [ ] Metrics update immediately
- [ ] Transients are cleared and data re-aggregated

#### T-03: Settings API
- [ ] GET `/settings` returns dashboard layout
- [ ] POST `/settings` saves dashboard layout
- [ ] Layout persists after page reload

#### T-04: Integration Status
- [ ] GET `/integration/woocommerce` shows WooCommerce status
- [ ] GET `/integration/google-analytics` shows GA connection status
- [ ] Last sync timestamps are accurate

#### T-05: Drag-and-Drop Dashboard
- [ ] Widgets can be dragged and reordered
- [ ] Layout saves automatically
- [ ] Layout persists across sessions

#### T-06: Reports List & Detail
- [ ] GET `/reports` lists all available reports
- [ ] GET `/reports/{id}` returns report data
- [ ] Date range filtering works

#### T-07: Custom Report Builder
- [ ] POST `/reports/custom` accepts custom report definition
- [ ] Report runs successfully with GA4 API
- [ ] Option to save report works

#### T-08: Period Comparison
- [ ] GET `/reports/compare` compares two periods
- [ ] Percentage changes calculate correctly
- [ ] Week-over-week and month-over-month work

#### T-09: Real-time Visitors Widget
- [ ] Live visitor count displays on dashboard
- [ ] Count updates automatically
- [ ] Link to Real-time Visitors page works

#### T-10: Manual GA Sync
- [ ] POST `/integration/google-analytics/sync` triggers sync
- [ ] Sync status updates in database
- [ ] Metrics refresh after sync

#### T-11: WooCommerce Event Endpoint
- [ ] POST `/integration/woocommerce/event` accepts events
- [ ] Token authentication works
- [ ] Events stored in database

#### T-12: Sync Status Tracking
- [ ] Sync records created in `wp_storepulse_sync`
- [ ] Status updates correctly (completed/error)
- [ ] Error messages stored when sync fails

#### T-DB: Database Installation
- [ ] All tables created on activation
- [ ] Multisite support works (if applicable)
- [ ] Tables use correct prefix
- [ ] Uninstall hook removes all data

### UI Features

#### T-UI-01: Traffic Overview Dashboard
- [ ] Page loads with line graph
- [ ] Toggle between Sessions and Pageviews works
- [ ] Date range picker filters data
- [ ] Comparison mode shows previous period
- [ ] Graph displays correctly with Chart.js

#### T-UI-02: Graph Annotations/Notes
- [ ] Notes Viewer modal opens
- [ ] Create note form works
- [ ] Notes save to database
- [ ] Notes display for selected date range
- [ ] Edit and delete notes work

#### T-UI-03: eCommerce Overview Report
- [ ] Campaign performance table displays
- [ ] Filters (Campaign, Medium, Source) work
- [ ] Search functionality filters table
- [ ] Export to CSV works
- [ ] Column sorting works

#### T-UI-04: Social Media Tracking
- [ ] Networks grouped correctly (Youtube, Facebook, etc.)
- [ ] Current period, comparison period, and %Change rows display
- [ ] Date range updates both periods
- [ ] %Change calculations are correct

#### T-UI-05: Campaign URL Tracking
- [ ] Transaction table displays
- [ ] Filters work correctly
- [ ] Pagination works
- [ ] Transaction ID links to WooCommerce order
- [ ] Search by Transaction ID works

#### T-UI-06: Link Report
- [ ] Four link tables display (Inbound, Outbound, Affiliate, Downloadable)
- [ ] Frontend link click tracking works
- [ ] Age Distribution bar chart displays
- [ ] Gender Distribution donut chart displays
- [ ] Date range updates all sections

#### T-UI-07: Core Web Vitals
- [ ] Performance gauge displays score
- [ ] Mobile/Desktop toggle works
- [ ] Metrics cards show values with status indicators
- [ ] How to Improve section expands/collapses
- [ ] Recommendations display correctly

#### T-UI-08: User Journey
- [ ] Conversion funnel displays 4 steps
- [ ] Drop-off percentages calculate correctly
- [ ] Order stats cards display (Orders, Revenue, AOV, Conversion Rate)
- [ ] Date range updates funnel and stats

### UX Features

#### T-22: Guided Setup Wizard
- [ ] Wizard appears on first visit
- [ ] Progress bar shows current step
- [ ] Back/Next buttons navigate correctly
- [ ] Skip button works for Advanced step
- [ ] Finish Setup completes wizard
- [ ] Wizard doesn't show after completion

#### T-23: In-app Help & Tutorials
- [ ] Help icons display next to settings fields
- [ ] Tooltips show on hover
- [ ] Help & Guidance section has articles
- [ ] Documentation links work

#### T-24: Role-based Access
- [ ] Administrator has full access
- [ ] Editor can view reports but not manage settings
- [ ] Subscriber cannot access any pages
- [ ] REST endpoints respect capabilities

### Nice-to-Have Features

#### T-N5: Clear All Settings Handler
- [ ] Clear button triggers server-side handler
- [ ] Settings and tokens are cleared
- [ ] Success notice displays
- [ ] Nonce verification works

#### T-N6: Currency Symbol
- [ ] Currency symbol comes from WooCommerce
- [ ] Dashboard displays correct symbol
- [ ] JavaScript uses dynamic symbol
- [ ] Fallback to ₹ works if WooCommerce unavailable

#### T-N12: Geo API Fallback
- [ ] Primary API (ip-api.com) works
- [ ] Fallback API (ipapi.co) activates on failure
- [ ] Unknown location displays if both fail
- [ ] Errors logged appropriately

## Common Test Scenarios

### Scenario 1: First-Time Setup
1. Activate plugin
2. Wizard should appear automatically
3. Complete General → Analytics → Events → Advanced → Help
4. Connect Google Analytics
5. Verify dashboard loads with data

### Scenario 2: Dashboard Customization
1. Drag widgets to reorder
2. Refresh page
3. Verify layout persists
4. Change date range
5. Verify all widgets update

### Scenario 3: Report Generation
1. Navigate to Traffic Overview
2. Select date range
3. Toggle Sessions/Pageviews
4. Enable comparison
5. Verify graph updates correctly

### Scenario 4: Campaign Tracking
1. Create test order with UTM parameters
2. Navigate to Campaign URL Tracking
3. Filter by campaign
4. Verify transaction appears
5. Export to CSV

### Scenario 5: Error Handling
1. Disconnect Google Analytics
2. Try to access dashboard
3. Verify error messages display
4. Reconnect GA
5. Verify data loads

## Known Issues & Limitations

- **Inbound Links**: Currently returns empty array (requires GA4 pageview data)
- **Core Web Vitals**: Fully operational when `storepulse_psi_api_key` is configured (via PageSpeed API); degrades gracefully without crashing if unconfigured.
- **Demographics**: Requires GA4 demographic data enabled in property settings

## Browser Compatibility

Tested on:
- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## Performance Considerations

- Transients cache GA API responses for 1 hour
- Database queries use proper indexing
- Large date ranges may take longer to load
- Real-time updates every 30 seconds

## Debugging

Enable debug logging:
1. Go to Settings → Advanced
2. Enable "Debug Logging"
3. Check `wp-content/debug.log` for errors

Common debug scenarios:
- GA API errors: Check access token and property ID
- Database errors: Verify table creation
- REST API errors: Check permission callbacks
- Frontend errors: Check browser console

## Feedback Collection

When reporting issues, please include:
1. WordPress version
2. WooCommerce version
3. PHP version
4. Browser and version
5. Steps to reproduce
6. Expected vs actual behavior
7. Error messages (if any)
8. Screenshots (if applicable)

## Next Steps After Testing

1. Review all test results
2. Document any bugs or issues
3. Provide feedback on UX/UI improvements
4. Suggest additional features if needed
5. Verify performance under load

---

**Last Updated:** February 17, 2026
**Version:** 1.0.0
