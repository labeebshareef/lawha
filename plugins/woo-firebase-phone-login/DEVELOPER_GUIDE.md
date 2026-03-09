# Developer Guide

## PHP API

Global functions available after plugin activation:

```php
// Verify a Firebase ID token (returns decoded payload or WP_Error)
$payload = wfpl_verify_firebase_token( $id_token );

// Full authenticate: verify token → login or create user
$result = wfpl_authenticate( $id_token );
// $result = [ 'success' => true, 'user_id' => 123, 'created' => false, 'phone' => '+971...' ]

// Login or create user by phone (bypasses Firebase verification)
$result = wfpl_login_or_create_user( '+971501234567' );

// Get WordPress user by phone
$user = wfpl_get_user_by_phone( '+971501234567' );

// Check if phone is registered
$exists = wfpl_is_phone_registered( '+971501234567' );

// Check rate limit
$limited = wfpl_is_rate_limited( '+971501234567' );
```

---

## REST API

Base URL: `https://yoursite.com/wp-json/wfpl/v1/`

### POST `/send-otp`

Returns Firebase config for client-side OTP sending.

```json
// Request
{ "phone": "+971501234567" }

// Response 200
{
  "success": true,
  "phone": "+971501234567",
  "firebase": { "apiKey": "...", "authDomain": "...", "projectId": "..." },
  "message": "Use the Firebase config to send OTP from the client."
}
```

### POST `/verify`

Verify Firebase ID token without logging in.

```json
// Request
{ "firebase_token": "eyJhbGci..." }

// Response 200
{
  "success": true,
  "phone": "+971501234567",
  "registered": true,
  "firebase_uid": "abc123"
}
```

### POST `/login`

Full authentication: verify token → login or create user → set session.

```json
// Request
{ "firebase_token": "eyJhbGci..." }

// Response 200
{
  "success": true,
  "user_id": 123,
  "created": false,
  "phone": "+971501234567"
}
```

### GET `/check-phone?phone=+971501234567`

```json
// Response 200
{ "success": true, "phone": "+971501234567", "registered": true }
```

---

## JavaScript SDK

The `WFPL` global object is available on all pages where the login form is rendered.

```js
// Send OTP (Firebase handles SMS delivery)
await WFPL.sendOTP('+971501234567', recaptchaElement);

// Verify OTP code
const credential = await WFPL.verifyOTP('123456');

// Get Firebase ID token
const idToken = await credential.user.getIdToken();

// Send token to backend for WP login
const result = await WFPL.login(idToken);
// result = { message, user_id, created, redirect_url }
```

---

## Hooks & Filters

### Actions

```php
// Before user creation
do_action( 'wfpl_before_create_user', $phone );

// After user creation
do_action( 'wfpl_user_created', $user_id, $phone );

// Before login
do_action( 'wfpl_before_login', $phone );

// After login
do_action( 'wfpl_after_login', $user_id, $phone );
```

### Filters

```php
// Modify phone number after sanitization
add_filter( 'wfpl_phone_validation', function( $phone ) {
    return $phone;
});

// Modify redirect URL after login
add_filter( 'wfpl_login_redirect', function( $url ) {
    return wc_get_page_permalink( 'shop' );
});
```

---

## Shortcode

```
[wfpl_login_form]
```

Renders the phone login form anywhere. Only shown to logged-out users.

## Gutenberg Block

Block name: `wfpl/phone-login` — available in the block editor.

---

## User Meta

Phone numbers are stored in user meta:

| Meta Key | Value | Example |
|---|---|---|
| `wfpl_phone` | E.164 phone number | `+971501234567` |
| `billing_phone` | WooCommerce billing phone | `+971501234567` |

---

## Architecture

```
woo-firebase-phone-login/
├── woo-firebase-phone-login.php   # Bootstrap, autoloader, singleton
├── includes/
│   ├── class-helpers.php          # Utilities, sanitization, rate limiting
│   ├── class-firebase-auth.php    # JWT verification with Google public keys
│   ├── class-user-handler.php     # User CRUD by phone
│   ├── class-auth-controller.php  # Orchestrates full auth flow
│   └── public-api.php             # Global wrapper functions
├── admin/
│   └── class-settings-page.php    # WooCommerce Settings tab
├── public/
│   ├── class-login-ui.php         # Frontend form rendering, asset enqueue
│   ├── class-shortcodes.php       # [wfpl_login_form] + Gutenberg block
│   └── class-ajax-handlers.php    # wp_ajax handlers
├── api/
│   └── class-rest-api.php         # REST API routes (wfpl/v1)
├── assets/
│   ├── js/
│   │   ├── firebase-auth.js       # Frontend SDK (WFPL global)
│   │   └── checkout-integration.js
│   └── css/
│       └── login.css              # Premium UI styles
├── README.md
├── INSTALLATION.md
└── DEVELOPER_GUIDE.md
```
