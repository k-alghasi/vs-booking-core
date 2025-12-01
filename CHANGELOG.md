# Changelog

All notable changes to the "VS Bus Booking Manager" project will be documented in this file.

## [2.0.0] - 2025-11-29
**Major Release - The "Intelligence" Update**

### 🔥 New Features
- **Time-Based Architecture:** Added `departure_timestamp` to database to support recurring trips (Daily, Weekly, Specific Dates).
- **Visual Seat Editor:** Brand new Admin UI to design seat layouts using a click-and-paint grid system.
- **Ticket Scanner:** Built-in Web QR Code Scanner for validating and checking in tickets (Admin > Scanner).
- **PDF Tickets:** Integrated TCPDF to generate real PDF tickets with QR codes attached to emails and account page.
- **Dynamic Fields:** New setting to create custom passenger fields (Text, Select, Phone) with validation rules.
- **Elementor Widget:** Added `[vsbbm_booking_form]` widget for Elementor page builder.
- **Blacklist Target:** Ability to select which passenger field triggers the blacklist check (e.g., National ID or Passport).

### ⚡ Improvements
- **Frontend UX:**
  - Added sticky summary bar for mobile devices.
  - Smart Datepicker that disables non-scheduled days.
  - Dynamic passenger form generation based on selected seats.
- **Dashboard:**
  - Added live Chart.js graphs for 7-day sales and ticket status.
  - Added "Self-Healing" logic to fix missing tickets for completed orders automatically.
- **Security:**
  - Implemented strict Nonce verification on all AJAX endpoints.
  - Added directory protection (`index.php`) to all folders.
  - Improved input sanitization and output escaping.

### 🔧 Technical
- **Refactoring:** Complete rewrite to Singleton pattern and MVC architecture.
- **Database:** Updated `dbDelta` logic and added new indexes for `departure_timestamp`.
- **Dependencies:** Added `vendor` folder support (Composer) for TCPDF and other libraries.
- **REST API:** Updated endpoints to support timestamp-based availability checks.

## [1.9.1] - 2025-01-17
### Added
- Advanced caching system with transient storage.
- Gzip compression for AJAX responses.

### Performance
- Reduced seat loading time by 70% using smart caching.
- Optimized database queries for high-traffic sites.

## [1.9.0] - 2025-01-XX
### Added
- Basic seat reservation database structure.
- Email notification system with HTML templates.
- Admin reporting interface.

## [1.0.0] - Initial Release
- Basic WooCommerce integration.
- Simple seat selection.