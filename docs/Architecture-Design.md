# StorePulse Analytics – Detailed Architecture Planning

## 1. High-Level Architecture Overview

**Architecture Layers:**

- **Presentation Layer (Frontend UI):**
    
    - Responsive dashboards, reports pages, settings, and help/tutorials.
    - Communicates with the backend via REST API endpoints using AJAX calls.
- **Business Logic Layer:**
    
    - Core processing engine that normalizes, aggregates, and transforms data.
    - Manages custom report generation, user configurations, and widget customization.
    - Implements access controls and enforces data validation.
- **Data Integration & Processing Layer:**
    
    - **WooCommerce Integration:** Captures eCommerce events via hooks and webhooks.
    - **Google Analytics Integration:** Fetches historical and real-time analytics data using the GA Reporting API.
    - **Local Data Store:** Uses WordPress custom tables, transients, or the options API for caching and persistence.
- **API Layer:**
    
    - Exposes RESTful endpoints under a defined namespace (e.g., `/wp-json/StorePulse/v1/`).
    - Handles external requests for dashboard data, reports, settings, and integrations.
- **Security & Logging:**
    
    - Enforces authentication and role-based permissions.
    - Implements error logging and audit trails for tracking API usage and configuration changes.

---

## 2. API Endpoints

**Base Namespace:** `/wp-json/StorePulse/v1/`

### A. Dashboard & Overview Endpoints

- **GET /dashboard**
    
    - **Purpose:** Retrieve aggregated key metrics for the one-page overview.
    - **Data Returned:** Sessions, revenue, conversion rates, real-time visitors.
    - **Authentication:** Requires valid WordPress user session with proper capabilities.
- **GET /dashboard/refresh**
    
    - **Purpose:** Trigger an immediate refresh of cached data.
    - **Data Returned:** Updated aggregated metrics and status confirmation.

### B. Reporting Endpoints

- **GET /reports**
    
    - **Purpose:** Retrieve a list of pre-built and custom reports.
    - **Data Returned:** Summary list with report IDs, titles, date ranges, and key metrics.
- **GET /reports/{report_id}**
    
    - **Purpose:** Fetch detailed data for a specific report.
    - **Data Returned:** Full report details with charts, tables, and drill-down data.
- **POST /reports/custom**
    
    - **Purpose:** Create or update a custom report based on user-defined parameters.
    - **Payload:** JSON object containing filters, metrics, dimensions, and date ranges.
    - **Data Returned:** Confirmation and report ID for subsequent retrieval.
- **GET /reports/compare**
    
    - **Purpose:** Retrieve data for period comparison (e.g., month-over-month).
    - **Data Returned:** Comparative metrics and graphical data points.

### C. Settings & Configuration Endpoints

- **GET /settings**
    
    - **Purpose:** Retrieve current plugin settings and dashboard customization preferences.
    - **Data Returned:** User-specific and global configuration details.
- **POST /settings**
    
    - **Purpose:** Update settings or dashboard widget configurations.
    - **Payload:** JSON with configuration changes.
    - **Data Returned:** Status and updated configuration snapshot.

### D. Integration Endpoints

#### WooCommerce Integration

- **GET /integration/woocommerce**
    
    - **Purpose:** Retrieve status of WooCommerce integration, including last sync timestamps.
    - **Data Returned:** Integration status, error logs, event counts.
- **POST /integration/woocommerce/event**
    
    - **Purpose:** Receive event data from WooCommerce hooks (order placed, refund processed, etc.).
    - **Payload:** JSON object with event type, order details, UTM parameters.
    - **Data Returned:** Acknowledgment and processed status.

#### Google Analytics Integration

- **GET /integration/google-analytics**
    
    - **Purpose:** Fetch data from the GA Reporting API.
    - **Data Returned:** Historical and real-time analytics metrics, campaign data.
    - **Authentication:** OAuth token exchange managed internally.
- **POST /integration/google-analytics/sync**
    
    - **Purpose:** Trigger a manual synchronization with GA data.
    - **Data Returned:** Synchronization status and any error messages.

### E. PRO Addon Endpoints

- **GET /pro/advanced-report**
    
    - **Purpose:** Access additional detailed reports available only in PRO mode.
    - **Data Returned:** Enhanced metrics including multi-channel attribution, customer lifetime value.
- **POST /pro/custom-export**
    
    - **Purpose:** Export analytics data in custom formats (CSV, JSON, XML).
    - **Payload:** Export options and filters.
    - **Data Returned:** Download URL or file stream.

---

## 3. Integration Layers & Communication Flow

### A. WooCommerce Integration

- **Backend Event Capture:**
    - Utilizes WooCommerce action hooks (e.g., `woocommerce_order_status_completed`) to capture order events.
    - Events are immediately processed and stored in the local cache for aggregation.
- **Frontend Event Injection (Enhanced Ecommerce):**
    - Uses hooks like `woocommerce_before_single_product`, `woocommerce_add_to_cart`, and `woocommerce_thankyou` to securely push GA4 `gtag()` events (`view_item`, `add_to_cart`, `purchase`) directly to the browser.
    - Implements strict deduplication (e.g., via `_storepulse_ga4_purchase_tracked` order meta) to ensure data hygiene.
- **Data Normalization:**
    - Incoming event data is standardized to match the plugin’s data schema.
    - Ensures consistency before merging with Google Analytics data.

### B. Google Analytics Integration

- **API Communication:**
    - Uses OAuth 2.0 for secure authentication with Google Analytics.
    - Scheduled (via WP-Cron) and on-demand synchronization of GA data.
- **Data Mapping:**
    - Aligns WooCommerce event data with GA dimensions (e.g., campaign IDs, event actions).
    - Enriches local data with GA insights (traffic sources, session duration).

### C. Data Aggregation & Caching

- **Local Data Store:**
    - Aggregated data is stored using WordPress transients or custom database tables.
    - Reduces the frequency of external API calls, improving performance.
- **Data Processing Engine:**
    - Batch processing using WP-Cron jobs to update metrics regularly.
    - On-demand refresh triggered via REST API endpoint or user action.

### D. Communication & Security

- **REST API Security:**
    
    - Endpoints protected by WordPress authentication (nonce, OAuth for external requests).
    - Role-based access control ensures only authorized users can access sensitive data.
- **Data Flow Diagram (Textual Overview):**
    
    1. **User Action:**
        - The admin triggers a data refresh from the dashboard UI.
    2. **API Call:**
        - UI sends a GET request to `/wp-json/StorePulse/v1/dashboard/refresh`.
    3. **Backend Processing:**
        - The request is authenticated and forwarded to the business logic layer.
        - The engine aggregates cached WooCommerce events and fetches updated GA data.
    4. **Response Generation:**
        - Processed data is normalized and returned as JSON to the UI.
    5. **UI Rendering:**
        - The dashboard updates, reflecting real-time metrics and any changes.

---

## 4. Developer & Project Management Considerations

- **Versioning & Documentation:**
    
    - Use versioned API endpoints (starting with `v1`) to manage future changes.
    - Provide complete API documentation (e.g., via Swagger/OpenAPI) for developer reference.
- **Error Handling & Logging:**
    
    - Implement standardized error codes and messages across all endpoints.
    - Log integration errors, sync failures, and API access issues for debugging and auditing.
- **Extensibility:**
    
    - Modular codebase allows for easy addition of new endpoints or integration features.
    - PRO addons follow the same architectural patterns and extend core functionality without altering the base code.
- **Testing & Maintenance:**
    
    - Unit tests for individual endpoints and integration tests for overall data flow.
    - Continuous integration (CI) pipelines to ensure API stability and performance.
 


___ 
## References: - 


--- 

