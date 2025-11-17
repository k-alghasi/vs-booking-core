# VS Bus Booking Manager

A comprehensive ticket and bus seat booking plugin for WooCommerce and WordPress, featuring graphical seat selection and blacklist management.

## Description

سیستم رزرواسیون صندلی اتوبوس با انتخاب گرافیکی و لیست سیاه برای ووکامرس و وردپرس

This plugin provides a complete bus seat reservation system with:
- Graphical seat selection interface
- Blacklist management for problematic customers
- WooCommerce integration
- Admin dashboard for managing bookings
- Seat availability tracking
- AJAX-powered booking process

## Features

- **Graphical Seat Selection**: Interactive seat map for easy booking
- **Blacklist System**: Block customers who violate terms
- **WooCommerce Integration**: Seamless e-commerce functionality
- **Admin Interface**: Comprehensive management dashboard
- **Booking Reports**: Track and analyze reservations
- **Responsive Design**: Works on all devices

## Installation

1. Upload the `vs-bus-booking-manager` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure settings in the admin panel
4. Add seat booking to your WooCommerce products

## Changelog

### Version 1.9.0 (Latest)
- **Advanced Order Management**
  - Enhanced bookings admin page with bulk operations
  - Advanced filtering by service, status, date range, and search
  - Bulk status updates for multiple bookings
  - Service column in bookings table for better organization
  - Improved user interface with select-all functionality
  - Quick access to reservation management and order editing

### Version 1.8.0
- **Email Notification System**
  - Comprehensive email notifications for customers and admins
  - Customizable HTML email templates with Persian/RTL support
  - Booking confirmation emails for customers
  - Booking cancellation emails with refund information
  - Admin notifications for new bookings and expired reservations
  - Configurable email settings and sender information
  - BCC functionality for admin oversight

### Version 1.7.0
- **Real Seat Reservation System**
  - Implemented proper seat reservation with database tracking
  - Added reservation states: reserved, confirmed, cancelled, expired
  - Automatic cleanup of expired reservations
  - Admin interface for managing reservations
  - Real-time seat availability checking
  - Order status integration for reservation management

### Version 1.6.0
- **Bug Fixes and UX Improvements**
  - Fixed duplicate seat selection displays
  - Resolved JavaScript scoping issues with seat selection
  - Hidden WooCommerce default add to cart button and quantity selector
  - Switched to AJAX-only cart addition for better reliability
  - Code cleanup and removal of unused hooks

### Version 1.4.0
- Initial release with core booking functionality
- Basic seat selection and blacklist features

## Requirements

- WordPress 5.0+
- WooCommerce 3.0+
- PHP 7.0+

## Support

For support and feature requests, visit [VernaSoft](https://vernasoft.ir)

## License

GPL v2 or later
