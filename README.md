# Ubac Booking

A lightweight, self-hosted appointment booking plugin for WordPress. Built by [Dahu-Concept](https://dahu-concept.fr).

## Features

- Two-step booking widget (calendar + time slots, then a contact form), with smooth Calendly-style transitions
- Insert anywhere via the `[ubac_booking]` shortcode or the native **Ubac Booking** Gutenberg block
- Manage your availability from the WordPress admin: weekly open days, time slots, and one-off blocked slots
- Automatic confirmation emails to both the visitor and the site admin via `wp_mail()` — compatible with any SMTP plugin
- Visitors can reschedule or cancel their booking via a secure link sent by email — no account required
- Widget styling is handled through WordPress's native Additional CSS editor (Appearance > Customize > Additional CSS)
- No external API calls, no tracking, no third-party service dependency

## Structure

```
ubac-booking/
├── ubac-booking.php              # Plugin entry point
├── uninstall.php                 # Cleanup on deletion
├── includes/
│   ├── class-ubac-booking-activator.php   # Activation: SQL tables, default options
│   ├── class-ubac-booking-db.php          # All data access logic
│   └── class-ubac-booking-mailer.php      # Email sending
├── admin/
│   ├── class-ubac-booking-admin.php       # wp-admin pages
│   └── views/                             # Admin page templates
├── public/
│   ├── class-ubac-booking-public.php      # Shortcode, AJAX, management page
│   └── views/                             # Widget and management page templates
└── assets/
    ├── css/                               # Front-end and admin styles
    └── js/                                # Widget JS logic
```

## Installation

1. Download the plugin zip
2. WordPress > Plugins > Add New > Upload Plugin
3. Activate the plugin
4. Configure your availability and settings under **Ubac Booking** in wp-admin
5. Insert the `[ubac_booking]` shortcode on any page, or use the Gutenberg block

## Technical stack

- PHP / WordPress (dedicated SQL tables via `dbDelta`)
- Vanilla JavaScript (no front-end dependencies)
- CSS with custom properties for theming

## License

GPLv2 or later — see [LICENSE](LICENSE).
