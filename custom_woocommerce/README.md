# Custom WooCommerce Theme

A lightweight WooCommerce-ready theme with a modern header/footer and subtle animations (no page builders, no widgets required).

## ✅ CJ DROPSHIPPING CUSTOM SINGLE PRODUCT PAGE - NEW!

### Features
- **Custom Layout**: Two-column design with vertical gallery on left
- **Video Support**: Upload and display multiple videos per product
- **Image Gallery**: Interactive thumbnail gallery with zoom
- **Responsive Design**: Mobile, tablet, and desktop optimized
- **Smooth Animations**: Fade transitions and hover effects
- **CJ Styling**: Red accent colors and modern design

### Quick Start

**Automatic (Default):**
- All products automatically use the custom CJ layout
- No configuration needed
- Template: `/woocommerce/single-product.php`

**Manual (Shortcode):**
```
[cw_product_display]
```

### Adding Products with Videos

1. Admin → Product Manager → Add Single Item
2. Upload main image + gallery images + videos
3. Submit form
4. Visit product page → See CJ layout!

### Layout Structure

```
┌─────────────────────────────────────┐
│ Gallery    │ Main Image  │ Product  │
│ Thumbs     │   / Video   │   Info   │
│ (vertical) │             │          │
└─────────────────────────────────────┘
```

## Setup Steps
1. Go to WordPress Admin → Appearance → Themes.
2. Activate "Custom WooCommerce Theme".
3. Go to Appearance → Menus.
4. Create a menu and assign it to "Primary Menu".
5. (Optional) Create a footer menu and assign it to "Footer Menu".

## Files

### CJ Single Product Page
- `/woocommerce/single-product.php` - Main template
- `/assets/css/cj-single-product.css` - Styling
- `/assets/js/cj-single-product.js` - Interactions

### Documentation
- `QUICK_START.txt` - Quick reference guide
- `SETUP_COMPLETE.md` - Complete setup guide
- `DEPLOYMENT_SUMMARY.md` - Technical details
- `CJ_SINGLE_PRODUCT_GUIDE.txt` - Advanced guide
6. Create pages (Home, Shop, About, Contact, etc.).
7. Add those pages to your menu.
8. Go to WooCommerce → Settings and configure store, payments, and shipping.
9. Visit the site and confirm header/footer animations and WooCommerce pages.

## Admin Add Product Page (Front-End)
1. Create a new page called “Add Product”.
2. Put this shortcode in the page content: [cw_add_product_form]
3. Publish the page.
4. Only admin users (manage_woocommerce) can access the form.
5. The image preview appears at the top of the form.

## Animations
- Elements with the attribute `data-animate` will fade/slide into view.
- The header adds a shadow and shrinks slightly on scroll.
- If a device prefers reduced motion, animations are disabled automatically.

## Files You Might Edit
- Header layout: header.php
- Footer layout: footer.php
- Styles: style.css
- Animation logic: assets/js/theme.js

## Notes
- This theme avoids page builders and widgets by default.
- WooCommerce templates are handled by WooCommerce unless you add overrides.

Register page: [cw_register]
Login page: [cw_login]
page for importing products :[cj_import_dashboard]