# UnelmaPay Payment Gateway for WooCommerce

A modern, lightweight WordPress plugin that integrates UnelmaPay payment gateway with WooCommerce.

## Features

- ✅ Full WooCommerce integration
- ✅ Sandbox mode for testing (dev.unelmapay.com)
- ✅ Production mode (unelmapay.com.np)
- ✅ IPN (Instant Payment Notification) callback support
- ✅ Secure hash verification
- ✅ Debug logging
- ✅ Simple form-based payment flow

## Requirements

- WordPress 5.2 or higher
- WooCommerce 4.0 or higher
- PHP 7.2 or higher
- SSL certificate (HTTPS) for production

## Installation

1. **Upload the plugin:**
   - Download or copy the `unelmapay-woocommerce` folder
   - Upload to `/wp-content/plugins/` directory
   - Or zip the folder and upload via WordPress admin

2. **Activate the plugin:**
   - Go to WordPress Admin → Plugins
   - Find "UnelmaPay Payment Gateway for WooCommerce"
   - Click "Activate"

3. **Configure the gateway:**
   - Go to WooCommerce → Settings → Payments
   - Click on "UnelmaPay"
   - Configure the following settings:

## Configuration

### Basic Settings

| Setting | Description | Example |
|---------|-------------|---------|
| **Enable/Disable** | Enable the payment gateway | ✓ Checked |
| **Title** | Payment method title shown to customers | UnelmaPay |
| **Description** | Payment method description | Pay securely via UnelmaPay |
| **Merchant ID** | Your UnelmaPay merchant ID | YOUR_MERCHANT_ID |
| **Merchant Password** | Your merchant password (for IPN verification) | YOUR_MERCHANT_PASSWORD |
| **Sandbox Mode** | Enable for testing on dev.unelmapay.com | ✓ Checked (for testing) |
| **Debug Mode** | Enable logging for troubleshooting | ✓ Checked (recommended) |

### Important URLs

The plugin automatically sets up the IPN callback URL:
```
https://yoursite.com/wc-api/WC_Gateway_UnelmaPay
```

Make sure this URL is accessible from the internet (not blocked by firewall).

## Testing on Sandbox (dev.unelmapay.com)

1. **Enable Sandbox Mode:**
   - In plugin settings, check "Enable Sandbox Mode"
   - This will use `https://dev.unelmapay.com/sci/form` as the payment endpoint

2. **Get Test Credentials:**
   - Contact UnelmaPay support for sandbox merchant ID and password
   - Or use your existing test credentials

3. **Test Payment Flow:**
   - Create a test product in WooCommerce
   - Add to cart and proceed to checkout
   - Select "UnelmaPay" as payment method
   - Complete the order
   - You'll be redirected to dev.unelmapay.com payment form
   - Complete test payment
   - Verify order status changes to "Processing" or "Completed"

4. **Check Logs:**
   - Go to WooCommerce → Status → Logs
   - Select the "unelmapay" log file
   - Review payment processing logs

## Production Deployment

1. **Uncheck Sandbox Mode:**
   - Go to WooCommerce → Settings → Payments → UnelmaPay
   - Uncheck "Enable Sandbox Mode"
   - This switches to production endpoint: `https://unelmapay.com.np/sci/form`

2. **Use Production Credentials:**
   - Enter your production Merchant ID
   - Enter your production Merchant Password

3. **Verify SSL:**
   - Ensure your site has a valid SSL certificate
   - IPN callbacks require HTTPS

4. **Test in Production:**
   - Make a small test transaction
   - Verify order completion
   - Check IPN callback is received

## Payment Flow

1. Customer adds products to cart
2. Customer proceeds to checkout
3. Customer selects "UnelmaPay" payment method
4. Customer clicks "Place Order"
5. Customer is redirected to UnelmaPay payment form
6. Customer completes payment on UnelmaPay
7. UnelmaPay sends IPN callback to your site
8. Plugin verifies IPN hash and updates order status
9. Customer is redirected back to your site

## IPN Verification

The plugin implements secure IPN verification using MD5 hash:

```php
hash = MD5(total:merchant_password:date:id_transfer)
```

All IPN requests are verified before processing to prevent fraud.

## Troubleshooting

### Orders not completing automatically

**Check:**
- IPN URL is accessible: `https://yoursite.com/wc-api/WC_Gateway_UnelmaPay`
- Firewall is not blocking UnelmaPay IPN requests
- Merchant password is correct
- Debug logs for hash verification errors

### Payment form not showing

**Check:**
- Merchant ID is configured
- WooCommerce is active
- No JavaScript errors in browser console

### Hash verification failing

**Check:**
- Merchant password matches exactly (case-sensitive)
- No extra spaces in merchant password field
- Check debug logs for received vs calculated hash

## Debug Logging

Enable debug mode to log all payment events:

1. Go to plugin settings
2. Check "Enable Debug Logging"
3. View logs at: WooCommerce → Status → Logs → unelmapay

Logs include:
- Payment form generation
- IPN requests received
- Hash verification results
- Order status updates

## API Documentation

For detailed API documentation, visit:
- https://docs.unelmapay.com/
- https://docs.unelmapay.com/api/payment-form/
- https://docs.unelmapay.com/api/ipn-callback/

## Support

- Email: support@unelmapay.com
- Documentation: https://docs.unelmapay.com/
- GitHub: https://github.com/unelmapay/wordpress-plugin

## Changelog

### Version 2.0.0
- Complete rewrite for modern WooCommerce compatibility
- Updated to use latest UnelmaPay API endpoints
- Added sandbox mode (dev.unelmapay.com)
- Improved IPN handling with proper hash verification
- Added comprehensive debug logging
- Simplified configuration
- Better error handling

## License

GPL-2.0+
