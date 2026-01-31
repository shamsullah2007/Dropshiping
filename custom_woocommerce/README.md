# Custom WooCommerce Theme

A lightweight WooCommerce-ready theme with a modern header/footer and subtle animations (no page builders, no widgets required).

## Setup Steps
1. Go to WordPress Admin → Appearance → Themes.
2. Activate “Custom WooCommerce Theme”.
3. Go to Appearance → Menus.
4. Create a menu and assign it to “Primary Menu”.
5. (Optional) Create a footer menu and assign it to “Footer Menu”.
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
