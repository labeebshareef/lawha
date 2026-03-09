# WooCommerce Firebase Phone Login

**Phone number based login & registration for WooCommerce using Firebase OTP authentication.**

## Features

- 🔐 **Firebase OTP Login** — Login/register using phone number + OTP
- 🛒 **Checkout Integration** — Quick phone login at WooCommerce checkout
- 🌐 **Headless REST API** — Full API for React, mobile apps, custom frontends
- 📱 **Modern UI** — Mobile-first design with country selector, OTP digit inputs, animations
- 🧩 **Developer Extensible** — PHP API, JS SDK, hooks/filters
- ⚡ **Lightweight** — Assets loaded only when needed, under 300KB footprint
- 🔒 **Secure** — Server-side token verification, rate limiting, nonce protection

## Requirements

- WordPress 6.0+
- WooCommerce 7.0+
- PHP 7.4+ with OpenSSL extension
- Firebase project with Phone Authentication enabled

## Quick Start

1. Upload the `woo-firebase-phone-login` folder to `wp-content/plugins/`
2. Activate the plugin in **Plugins → Installed Plugins**
3. Go to **WooCommerce → Settings → Firebase Phone Login**
4. Enter your Firebase credentials (API Key, Project ID, Auth Domain)
5. Enable phone login features

See [INSTALLATION.md](INSTALLATION.md) for detailed Firebase setup instructions.

## WooCommerce Integration

Works with:
- My Account login/register page
- Checkout login
- Shortcode: `[wfpl_login_form]`
- Gutenberg block: `wfpl/phone-login`
- Popup login modal

## Headless API

| Endpoint | Method | Description |
|---|---|---|
| `/wp-json/wfpl/v1/send-otp` | POST | Get Firebase config for client-side OTP |
| `/wp-json/wfpl/v1/verify` | POST | Verify Firebase token (no login) |
| `/wp-json/wfpl/v1/login` | POST | Full login/register via Firebase token |
| `/wp-json/wfpl/v1/check-phone` | GET | Check if phone is registered |

See [DEVELOPER_GUIDE.md](DEVELOPER_GUIDE.md) for full API documentation.

## License

GPL v2 or later
