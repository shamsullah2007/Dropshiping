# Custom Single Product Page - Installation & Setup Guide

## Overview

The custom single product page provides a clean, modern layout specifically designed for CJ Dropshipping products. It replaces the default WooCommerce product page with a professional two-column design featuring:

- **Left Column**: Product gallery with image/video thumbnails and main display
- **Right Column**: Product details including title, price, varieties/colors, quantity selector, and add-to-cart button
- **Below**: Product description and details

## Features

### Gallery & Media
- Image thumbnails in a vertical column (left sidebar)
- Click any thumbnail to view full-size image in main display
- Video support with preview icons
- Image counter showing current position (e.g., "3/8")
- Responsive thumbnail grid on mobile devices
- Touch gestures for mobile (swipe left/right)
- Keyboard shortcuts (Arrow keys to navigate images)

### Product Information
- Clear product title and SKU display
- Dynamic pricing based on variety selection
- Minimal shipping summary showing "Shipping From: China"
- Professional layout with proper spacing

### Color/Variety Swatches
- Grid of color options with thumbnails
- Click to select variant
- Visual feedback (blue border on selected)
- Price-per-variety support
- Images or letter abbreviations if no image provided

### Quantity Selector
- Plus/minus buttons for easy quantity adjustment
- Direct number input with validation (min: 1, max: 999)
- Weight display (e.g., "270g")
- Arrow key support (↑ to increase, ↓ to decrease)
- Keyboard accessible

### Dynamic Pricing
- Total price calculation updates in real-time
- Considers: base price + variety price × quantity
- Currency formatting with symbol detection
- Live updates as user changes quantity

### Add to Cart
- Large, prominent button
- Integrates with WooCommerce cart system
- Passes variety selection data if available

## File Structure

```
custom_woocommerce/
├── woocommerce/
│   └── single-product.php              # Template file
├── assets/
│   ├── css/
│   │   └── single-product-custom.css   # Styling
│   └── js/
│       └── single-product-custom.js    # Interactive features
```

## Installation

### Step 1: File Placement ✓
All files are already in place:
- Template: `woocommerce/single-product.php`
- CSS: `assets/css/single-product-custom.css`
- JavaScript: `assets/js/single-product-custom.js`

### Step 2: Enqueue in functions.php ✓
Already configured in `functions.php`:
```php
wp_enqueue_style('custom-woocommerce-single-product-custom', 
    get_template_directory_uri() . '/assets/css/single-product-custom.css', 
    [], '1.0.0');

wp_enqueue_script('custom-woocommerce-single-product-custom',
    get_template_directory_uri() . '/assets/js/single-product-custom.js',
    [], '1.0.0', true);
```

### Step 3: Verify WooCommerce is Active
Ensure WooCommerce plugin is installed and activated.

## CSS Classes Reference

### Container
- `.custom-single-product-page` - Main wrapper
- `.csp-container` - Two-column grid layout

### Gallery
- `.csp-gallery-section` - Left section container
- `.csp-thumbnails` - Thumbnail column
- `.csp-thumb` - Individual thumbnail
- `.csp-thumb.active` - Selected thumbnail
- `.csp-thumb.video` - Video thumbnail variant
- `.csp-main-display` - Main image/video display area
- `.csp-counter` - Image counter badge

### Product Info
- `.csp-info-section` - Right section container
- `.csp-title` - Product title
- `.csp-meta-info` - SKU and metadata
- `.csp-sku` - SKU display
- `.csp-price` - Price display
- `.csp-shipping-summary` - Shipping info box

### Varieties
- `.csp-varieties-section` - Color selector container
- `.csp-variety-label` - "Color" label
- `.csp-varieties-grid` - Grid of swatches
- `.csp-variety-swatch` - Individual color swatch
- `.csp-variety-swatch.active` - Selected swatch

### Quantity
- `.csp-quantity-section` - Quantity selector container
- `.csp-qty-label` - "QTY:" label
- `.csp-qty-input-wrapper` - Input and buttons wrapper
- `.csp-qty-btn` - Plus/minus button
- `#csp-qty-input` - Quantity input field
- `.csp-weight` - Weight display

### Pricing & Cart
- `.csp-total-section` - Total price container
- `.csp-total-label` - "Total:" label
- `.csp-total-price` - Total price value
- `.csp-add-to-cart-wrapper` - Add to cart button container

### Details Section
- `.csp-details-section` - Description/tabs area below

## JavaScript API

The custom script automatically handles these interactions:

### Gallery Navigation
```javascript
// Click thumbnails to switch images
// Keyboard: Arrow Left/Right to navigate
// Mobile: Swipe left/right on thumbnails
```

### Variety Selection
```javascript
// Click color swatch to select
// Updates selectedVariety object with:
// { index, name, price }
```

### Quantity Updates
```javascript
// Click +/- buttons or type number
// Updates total price automatically
// Updates WooCommerce form field
```

### Event Listeners
- `click` - Thumbnail selection, variety swatches, quantity buttons
- `change` - Quantity input validation
- `input` - Live quantity updates
- `keydown` - Keyboard shortcuts and arrow keys
- `touchstart/touchend` - Mobile swipe gestures

## Data Attributes

### Thumbnail Data
```html
<div class="csp-thumb" 
     data-index="0" 
     data-type="image|video" 
     data-id="attachment_id" 
     data-url="image_url">
```

### Variety Data
```html
<div class="csp-variety-swatch"
     data-variety-index="0"
     data-variety-name="Color Name"
     data-variety-price="0.00">
```

## Responsive Breakpoints

### Tablet (≤768px)
- Single column layout (gallery above info)
- Thumbnails switch to horizontal scroll
- Reduced font sizes
- Adjusted spacing

### Mobile (≤480px)
- Vertical layout optimized
- Smaller buttons and controls
- 4-column variety grid
- Touch-friendly sizing

## Customization Guide

### Change Layout Width
Edit CSS:
```css
.custom-single-product-page {
    max-width: 1200px;  /* Adjust this value */
}
```

### Modify Column Gap
```css
.csp-container {
    gap: 40px;  /* Adjust spacing between gallery and info */
}
```

### Adjust Colors
```css
.csp-variety-swatch.active {
    border-color: #007bff;  /* Change accent color */
}

.csp-add-to-cart-wrapper .single_add_to_cart_button {
    background: #007bff;  /* Change button color */
}
```

### Change Font Sizes
```css
.csp-title {
    font-size: 28px;  /* Product title */
}

.csp-price {
    font-size: 26px;  /* Price display */
}
```

### Modify Variety Grid
```css
.csp-varieties-grid {
    grid-template-columns: repeat(4, 1fr);  /* Number of columns */
}
```

## Browser Support

- Chrome/Edge: Full support
- Firefox: Full support
- Safari: Full support
- IE 11: Partial support (no CSS Grid fallbacks)

## Performance Considerations

The script is optimized for:
- Minimal DOM queries
- Event delegation where possible
- No external dependencies (vanilla JS)
- Lazy-loaded CSS and JS (only on product pages)

## Troubleshooting

### Images not loading
- Check media library for attachments
- Verify `_product_video_ids` meta key format
- Ensure gallery images are assigned to product

### Quantity updates not working
- Clear browser cache
- Verify `csp-qty-input` element exists
- Check browser console for JS errors

### Varieties not showing
- Ensure `_cj_varieties` post meta is set
- Verify variety array structure:
  ```php
  [
    ['color_name' => 'Red', 'price' => 10.00, 'image_id' => 123],
    ['color_name' => 'Blue', 'price' => 10.00, 'image_id' => 124]
  ]
  ```

### Mobile swipe not working
- Verify touchstart/touchend events fire
- Check for overlapping elements blocking touch
- Test on actual mobile device (not browser emulation)

## Technical Details

### Template Hooks
The template uses these WooCommerce hooks:
- `woocommerce_before_main_content` (opening)
- `woocommerce_after_main_content` (closing)
- `woocommerce_after_single_product_summary` (details section)

### Meta Keys
- `_product_video_ids` - Array of video attachment IDs
- `_cj_varieties` - Array of variety objects with color_name, price, image_id

### Function Dependencies
- `wp_get_attachment_image_url()` - Get image URLs
- `wp_get_attachment_url()` - Get attachment URLs
- `get_post_meta()` - Retrieve custom meta data
- `woocommerce_template_single_add_to_cart()` - WooCommerce add to cart

## CSS Reset & Normalization

The CSS includes:
- Flexbox layouts
- CSS Grid for variety swatches
- Mobile-first responsive design
- No Bootstrap/Tailwind dependencies

## Future Enhancements

Potential additions:
- Image zoom on hover
- Lightbox gallery modal
- Customer reviews section
- Product comparison
- Size chart modal
- Stock status indicators
- Social sharing buttons
- Related products carousel

## Support & Help

For issues or questions:
1. Check browser developer console for errors
2. Verify all files are in correct locations
3. Ensure WooCommerce is fully updated
4. Test with default theme to isolate issues
5. Check CJ Integration logs in admin

## Version Information

- **Current Version**: 1.0.0
- **Created**: 2024
- **Tested With**: WooCommerce 8.0+
- **PHP Version**: 7.4+
- **WordPress**: 6.0+

## License & Credits

Part of the Custom WooCommerce CJ Dropshipping integration theme.
