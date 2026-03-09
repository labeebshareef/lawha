# Installation Guide

## Step 1 — Create a Firebase Project

1. Go to [Firebase Console](https://console.firebase.google.com/)
2. Click **Add Project** and follow the wizard
3. Once created, navigate to **Project Settings → General**
4. Note your **Project ID** (e.g., `my-store-12345`)

## Step 2 — Enable Phone Authentication

1. In Firebase Console, go to **Authentication → Sign-in method**
2. Enable **Phone** provider
3. (Optional) Add test phone numbers under **Phone numbers for testing**

## Step 3 — Get Web API Key

1. In **Project Settings → General**, scroll to **Your apps**
2. Click **Add app → Web** (</> icon)
3. Register the app (nickname: e.g., "WooCommerce Store")
4. Copy the Firebase configuration values:
   - `apiKey`
   - `authDomain`
   - `projectId`

## Step 4 — Add Your Domain

1. In **Authentication → Settings → Authorized domains**
2. Add your WordPress site domain (e.g., `mystore.com`)
3. If testing locally, add `localhost`

## Step 5 — Install the Plugin

### Method A — FTP / File Manager
1. Upload the `woo-firebase-phone-login` folder to `wp-content/plugins/`
2. Go to **Plugins → Installed Plugins** in WordPress Admin
3. Activate **WooCommerce Firebase Phone Login**

### Method B — WordPress Admin
1. Zip the `woo-firebase-phone-login` folder
2. Go to **Plugins → Add New → Upload Plugin**
3. Upload the zip and activate

## Step 6 — Configure the Plugin

1. Navigate to **WooCommerce → Settings → Firebase Phone Login**
2. Fill in:
   - **Firebase API Key** → Paste your `apiKey`
   - **Firebase Project ID** → Paste your `projectId`
   - **Firebase Auth Domain** → Paste your `authDomain`
3. Configure features:
   - Enable/disable phone login
   - Enable/disable phone registration
   - Enable/disable checkout login
   - Configure rate limiting
4. Click **Save Changes**

## Step 7 — Test

1. Open your WooCommerce **My Account** page
2. You should see the "Login with Phone" form
3. Enter a phone number, receive OTP, verify, and login

## Troubleshooting

| Problem | Solution |
|---|---|
| OTP not received | Check Firebase Phone Auth is enabled, domain is authorized |
| reCAPTCHA errors | Add your domain to Firebase authorized domains |
| "Firebase not configured" | Verify API Key, Project ID, Auth Domain in settings |
| Token verification fails | Ensure server time is correct (NTP sync), PHP OpenSSL is installed |
| Rate limited | Adjust "Max OTP Requests / Hour" in settings |
