# WordPress.org Plugin Submission Guide

This document outlines the additional files and changes needed to submit the UnelmaPay plugin to WordPress.org.

## Required Files Added

### 1. `readme.txt` ✅
- **Purpose**: Official WordPress plugin readme format
- **Location**: Root of plugin folder
- **Contains**:
  - Plugin metadata (version, tags, requirements)
  - Description and features
  - Installation instructions
  - FAQ section
  - Changelog
  - Screenshots description

### 2. `.wordpress-org/` Directory ✅
- **Purpose**: Assets for WordPress.org plugin page
- **Required files**:
  - `icon-256x256.png` - Plugin icon (256x256px)
  - `icon-128x128.png` - Plugin icon (128x128px) 
  - `banner-772x250.png` - Plugin banner (772x250px)
  - `banner-1544x500.png` - Plugin banner retina (1544x500px)
  - `screenshot-1.png` - Settings page screenshot
  - `screenshot-2.png` - Checkout page screenshot
  - `screenshot-3.png` - Payment form screenshot
  - `screenshot-4.png` - Order completion screenshot

### 3. Asset Files to Create

You need to create these image files:

#### Plugin Icons (Required)
- **icon-256x256.png** - 256x256px PNG
- **icon-128x128.png** - 128x128px PNG
- Should feature UnelmaPay logo or payment-related icon
- Transparent background recommended

#### Plugin Banners (Recommended)
- **banner-772x250.png** - 772x250px PNG/JPG
- **banner-1544x500.png** - 1544x500px PNG/JPG (retina)
- Should showcase plugin features or branding

#### Screenshots (Recommended)
1. **screenshot-1.png** - Plugin settings page in WooCommerce
2. **screenshot-2.png** - Payment method selection at checkout
3. **screenshot-3.png** - UnelmaPay payment form
4. **screenshot-4.png** - Order completion confirmation

## File Structure for Submission

```
unelmapay-woocommerce/
├── .wordpress-org/              # Assets directory
│   ├── icon-256x256.png        # Plugin icon
│   ├── icon-128x128.png        # Plugin icon (smaller)
│   ├── banner-772x250.png      # Banner image
│   ├── banner-1544x500.png     # Banner retina
│   ├── screenshot-1.png        # Settings screenshot
│   ├── screenshot-2.png        # Checkout screenshot
│   ├── screenshot-3.png        # Payment form screenshot
│   └── screenshot-4.png        # Completion screenshot
├── includes/
│   └── class-wc-gateway-unelmapay.php
├── languages/                   # Translation files (optional)
│   └── unelmapay-woocommerce.pot
├── assets/                      # Frontend assets (if needed)
│   ├── css/
│   └── js/
├── unelmapay-woocommerce.php   # Main plugin file
├── readme.txt                   # WordPress.org readme ✅
├── README.md                    # GitHub readme
├── CHANGES.md                   # Change log
├── TESTING.md                   # Testing guide
└── LICENSE.txt                  # GPL license (optional)
```

## Code Requirements Checklist

### Security ✅
- [x] All user inputs are sanitized
- [x] All outputs are escaped
- [x] Nonce verification for forms
- [x] No direct file access (ABSPATH check)
- [x] Secure IPN handling with hash verification

### WordPress Coding Standards ✅
- [x] Follows WordPress PHP coding standards
- [x] Proper text domain usage
- [x] Internationalization ready
- [x] No PHP errors or warnings
- [x] Compatible with WordPress debug mode

### WooCommerce Integration ✅
- [x] Extends WC_Payment_Gateway class
- [x] Proper hooks and filters
- [x] Compatible with WooCommerce 4.0+
- [x] Proper order status handling

### Plugin Header ✅
```php
/**
 * Plugin Name: UnelmaPay Payment Gateway for WooCommerce
 * Plugin URI: https://unelmapay.com/
 * Description: Accept payments via UnelmaPay payment gateway
 * Version: 2.0.0
 * Author: UnelmaPay
 * Author URI: https://unelmapay.com/
 * Text Domain: unelmapay-woocommerce
 * Domain Path: /languages
 * Requires at least: 5.2
 * Requires PHP: 7.2
 * WC requires at least: 4.0
 * WC tested up to: 8.0
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */
```

## Submission Process

### 1. Prepare Assets
- Create all required images (icons, banners, screenshots)
- Place in `.wordpress-org/` directory
- Ensure proper dimensions and file sizes

### 2. Test Thoroughly
- Test on fresh WordPress installation
- Test with WooCommerce latest version
- Test with PHP 7.2, 7.4, 8.0, 8.1
- Test with WordPress 5.2+
- Enable WP_DEBUG and fix all errors/warnings

### 3. Create SVN Repository
WordPress.org uses SVN (not Git) for plugin hosting.

```bash
# Checkout SVN repository (after approval)
svn co https://plugins.svn.wordpress.org/unelmapay-woocommerce

cd unelmapay-woocommerce

# Create trunk directory
mkdir trunk

# Copy plugin files to trunk
cp -r /path/to/unelmapay-woocommerce/* trunk/

# Copy assets to assets directory
mkdir assets
cp .wordpress-org/* assets/

# Add files to SVN
svn add trunk/*
svn add assets/*

# Commit to SVN
svn ci -m "Initial commit of UnelmaPay Payment Gateway v2.0.0"

# Tag the release
svn cp trunk tags/2.0.0
svn ci -m "Tagging version 2.0.0"
```

### 4. Submit Plugin

1. Go to https://wordpress.org/plugins/developers/add/
2. Log in with your WordPress.org account
3. Submit plugin URL (if hosted on GitHub/GitLab)
4. Or upload plugin zip file
5. Wait for review (typically 2-14 days)

### 5. After Approval

Once approved, you'll receive SVN access:
- Update plugin via SVN commits
- Tag new versions in `tags/` directory
- Update `trunk/` for development
- Assets go in `assets/` directory (not versioned)

## Additional Recommendations

### 1. Add Translation Support
Create a `.pot` file for translations:

```bash
# Install WP-CLI
wp i18n make-pot . languages/unelmapay-woocommerce.pot
```

### 2. Add Unit Tests (Optional but Recommended)
```
tests/
├── bootstrap.php
├── test-gateway.php
└── test-ipn.php
```

### 3. Add GitHub Repository
- Host code on GitHub
- Link in readme.txt
- Use GitHub Actions for automated testing
- Add CONTRIBUTING.md for contributors

### 4. Documentation
- Create detailed documentation site
- Video tutorials
- Integration guides
- API documentation

## Common Rejection Reasons to Avoid

❌ **Security Issues**
- Unsanitized inputs
- Unescaped outputs
- SQL injection vulnerabilities
- XSS vulnerabilities

❌ **Licensing Issues**
- Non-GPL compatible code
- Missing license information
- Proprietary code

❌ **Guideline Violations**
- Calling external files unnecessarily
- Phone home functionality
- Obfuscated code
- Cryptocurrency mining

❌ **Code Quality**
- PHP errors/warnings
- Not following WordPress coding standards
- Missing text domain
- Hardcoded strings

## Pre-Submission Checklist

- [ ] All images created and placed in `.wordpress-org/`
- [ ] `readme.txt` properly formatted
- [ ] Plugin tested on clean WordPress install
- [ ] No PHP errors with WP_DEBUG enabled
- [ ] All strings are translatable
- [ ] Security best practices followed
- [ ] Compatible with latest WordPress version
- [ ] Compatible with latest WooCommerce version
- [ ] Documentation is complete
- [ ] Screenshots are clear and relevant
- [ ] License is GPL-compatible
- [ ] No external dependencies (or properly documented)
- [ ] Uninstall.php cleans up properly (if needed)

## Resources

- [Plugin Developer Handbook](https://developer.wordpress.org/plugins/)
- [Plugin Submission Guidelines](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/)
- [SVN Guide](https://developer.wordpress.org/plugins/wordpress-org/how-to-use-subversion/)
- [readme.txt Validator](https://wordpress.org/plugins/developers/readme-validator/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)

## Contact

For questions about submission:
- WordPress.org Plugin Review Team: plugins@wordpress.org
- Support Forums: https://wordpress.org/support/forum/plugins-and-hacks/
