# VS Bus Booking Manager

The ultimate bus seat reservation system for WordPress & WooCommerce. Features a visual seat editor, recurring trips, PDF tickets, and a built-in QR scanner.

**Version:** 2.0.0  
**Requires PHP:** 7.4+  
**Requires WooCommerce:** 5.0+

## Description

سیستم رزرواسیون صندلی اتوبوس با انتخاب گرافیکی، زمان‌بندی سفر و لیست سیاه برای ووکامرس

VS Bus Booking Manager v2.0 is a major leap forward, transforming your website into a professional ticketing platform. It now supports time-based bookings (recurring trips), a visual drag-and-drop seat editor, and a fully functional ticket validation system.

### ✨ New in Version 2.0
- **Time-Based Booking:** Sell seats for specific dates and times (Recurring or One-time trips).
- **Visual Seat Editor:** Design bus layouts visually in the admin panel.
- **Ticket Scanner:** Validate and check-in passengers using your device's camera (QR Code).
- **Dynamic Passenger Fields:** Customize required fields (Name, National ID, Gender, etc.).
- **PDF Tickets:** Automatic generation of professional PDF tickets with QR codes.

## Features

### 🚌 Booking Management
- **Smart Date Picker:** Frontend calendar that respects recurring schedules (e.g., Only Mondays & Wednesdays).
- **Visual Seat Map:** Interactive grid for selecting seats with "Loading" states and price tooltips.
- **Dynamic Pricing:** Support for different prices per seat type.
- **Mobile Sticky Footer:** Enhanced UX for mobile users during booking.

### 🛠 Admin Tools
- **Visual Layout Editor:** Create 2x2, 1x2, or custom layouts with a click-and-paint interface.
- **Dashboard Charts:** Live statistics for revenue, sales trends (Chart.js), and ticket status.
- **Ticket Scanner:** Built-in web app to scan passenger QR codes and mark tickets as "Used".
- **Self-Healing Data:** Automatically generates missing tickets/meta for older orders.

### ⚙️ Advanced Configuration
- **Blacklist System:** Block users based on any field (National ID, Passport, Phone).
- **Custom Fields:** Drag & drop builder for passenger information forms.
- **Elementor Widget:** Dedicated widget to display the booking form anywhere.
- **Notifications:** Customizable SMS (Iranian Gateways) and Email templates.

## 🚀 Performance & Architecture

Version 2.0.0 introduces a robust MVC architecture:
- **Refactored Core:** Logic separated into Controllers, Models, and Views.
- **Database Optimization:** Time-based indexing for fast queries on large datasets.
- **Security First:** Full Nonce verification, sanitization, and output escaping.
- **Smart Caching:** Multi-layer caching (Object Cache + Transients) to reduce DB load.

## Installation

1. Upload the `vs-bus-booking-manager` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. **Important:** Go to the plugin settings to create necessary database tables (Automatic on activation).
4. Configure your Bus Products in WooCommerce (enable "Bus Seat Booking" in Product Data).
5. Use the **Visual Seat Editor** tab to design your bus layout.

## Screenshots

1. **Frontend:** Visual seat selection with date picker.
2. **Admin:** Visual Seat Editor (Grid System).
3. **Scanner:** QR Code Validator interface.
4. **Dashboard:** Sales analytics and charts.

## Support

For support, documentation, and pro features, visit [VernaSoft](https://vernasoft.ir).

## License

GPL v2 or later