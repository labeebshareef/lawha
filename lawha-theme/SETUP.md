# LAWHA HIJABS — WordPress Theme Setup Guide

## Requirements

- **WordPress** 6.0 or higher
- **PHP** 7.4 or higher
- **MySQL** 5.7 or higher
- **WooCommerce** plugin (latest version)
- **Yoast SEO** plugin (optional, recommended)
- **LiteSpeed Cache** plugin (optional, recommended)

---

## Installation

### 1. Upload the Theme

1. Compress the `lawha-theme/` folder into a `.zip` file
2. Go to **WordPress Admin → Appearance → Themes → Add New → Upload Theme**
3. Upload the `.zip` file and click **Install Now**
4. Click **Activate**

### 2. Install Required Plugins

Go to **Plugins → Add New** and install:

| Plugin | Purpose |
|--------|---------|
| **WooCommerce** | Product management, cart, checkout |
| **Yoast SEO** | SEO management, meta tags, sitemaps |
| **LiteSpeed Cache** | Performance optimization |

---

## Configuration

### 3. Set Up the Homepage

1. Go to **Pages → Add New**
2. Create a page titled **"Home"** (leave content empty — the theme handles it)
3. Go to **Settings → Reading**
4. Select **"A static page"**
5. Set **Homepage** to "Home"
6. Save changes

### 4. Create Required Pages

Create these pages with the matching slug:

| Page Title | Slug | Template |
|------------|------|----------|
| About | `about` | About Page (select in Page Attributes) |
| Contact | `contact` | Contact Page (select in Page Attributes) |

### 5. Set Up Navigation Menu

1. Go to **Appearance → Menus**
2. Create a new menu called **"Primary Navigation"**
3. Add these menu items:
   - Home → Front Page
   - Collections → Shop Page (WooCommerce)
   - About → About Page
   - Contact → Contact Page
4. Assign to the **"Primary Navigation"** location
5. Save

### 6. Configure WooCommerce

1. Go through the WooCommerce Setup Wizard
2. Set your **currency** to AED (or your preferred currency)
3. Configure **shipping zones** as needed
4. Set up **payment methods** (PayPal, Stripe, COD, etc.)

### 7. Add Products

1. Go to **Products → Add New**
2. Add product details:
   - **Name**: e.g., "Silk Satin — Dusty Rose"
   - **Price**: e.g., 189
   - **Image**: Upload product image
   - **Category**: Create categories like Silk, Chiffon, Jersey, Crêpe
3. Mark products as **Featured** to show on the homepage
4. Publish

### 8. Upload Custom Logo (Optional)

1. Go to **Appearance → Customize → Site Identity**
2. Upload your logo (recommended: 80×80px)

---

## Performance (LiteSpeed Cache)

If using LiteSpeed Cache, configure these settings:

1. **Page Cache** → Enable
2. **CSS/JS Optimization**:
   - CSS Minify → ON
   - JS Minify → ON
   - JS Defer → ON
   - Load CSS Asynchronously → ON
3. **Image Optimization**:
   - Lazy Load Images → ON
   - Responsive Placeholder → ON
4. **Browser Cache** → Enable

The theme is designed to be fully compatible with these optimizations.

---

## SEO (Yoast SEO)

The theme is fully Yoast-compatible:

- `wp_head()` is properly placed for meta tag injection
- Semantic HTML with proper heading hierarchy (h1 → h2 → h3)
- All images include `alt` attributes
- Schema-compatible markup

Configure Yoast:
1. Go to **SEO → General** and complete the setup wizard
2. Set homepage title/description in **SEO → Search Appearance**
3. Enable XML Sitemaps

---

## Theme File Structure

```
lawha-theme/
├── style.css              ← Theme header
├── functions.php          ← Theme config, enqueuing, WooCommerce
├── header.php             ← Global header + navbar
├── footer.php             ← Global footer
├── front-page.php         ← Homepage
├── page.php               ← Generic page
├── page-about.php         ← About page
├── page-contact.php       ← Contact page
├── single.php             ← Single post
├── archive.php            ← Post archive
├── index.php              ← Fallback
├── woocommerce/
│   ├── archive-product.php   ← Shop/Collections page
│   ├── single-product.php    ← Single product page
│   └── content-product.php   ← Product card component
├── css/
│   ├── reset.css
│   ├── variables.css
│   ├── typography.css
│   ├── layout.css
│   ├── components.css
│   ├── animations.css
│   ├── responsive.css
│   └── woocommerce.css
├── js/
│   ├── scrollAnimations.js
│   ├── navbar.js
│   ├── interactions.js
│   └── main.js
├── assets/
│   ├── banners/
│   ├── icons/
│   ├── logo/
│   ├── models/
│   └── products/
└── fonts/
```

---

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Fonts not loading | Ensure Google Fonts is not blocked by ad blockers |
| Products not showing on homepage | Mark products as **Featured** in WooCommerce |
| Category filters not working | Create product categories in WooCommerce |
| Animations not working | Ensure JavaScript is not deferred too aggressively in cache plugin |
| 404 on product pages | Go to **Settings → Permalinks** and click Save (regenerates rewrite rules) |
