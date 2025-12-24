# UnelmaPay Configuration Guide

Complete guide for configuring UnelmaPay payment gateway settings.

## Configuration Fields

### Required Fields

#### 1. Merchant ID
- **Required**: Yes
- **Description**: Your unique UnelmaPay merchant identifier
- **Where to get**: Provided by UnelmaPay when you create your merchant account
- **Example**: `MERCH123456`

#### 2. Merchant Password
- **Required**: Yes
- **Description**: Secret password used for IPN verification
- **Where to get**: Provided by UnelmaPay with your merchant account
- **Security**: Never share this password publicly
- **Used for**: Verifying payment notifications (IPN callbacks)

### Optional Fields

#### 3. Merchant Name
- **Required**: No
- **Description**: Your business or merchant display name
- **Example**: `My Online Store`
- **Purpose**: For display and reference purposes

#### 4. Merchant Email
- **Required**: No
- **Description**: Contact email for your merchant account
- **Example**: `payments@mystore.com`
- **Purpose**: For notifications and support

#### 5. Success URL
- **Required**: No
- **Description**: Custom URL to redirect customers after successful payment
- **Default**: 
  - **WooCommerce**: Order received page
  - **Standalone**: Homepage with success parameter
- **Example**: `https://yoursite.com/payment-success/`
- **Use case**: Custom thank you page, tracking pixels, special offers

#### 6. Fail URL
- **Required**: No
- **Description**: Custom URL to redirect customers after failed payment
- **Default**:
  - **WooCommerce**: Checkout page
  - **Standalone**: Homepage with failed parameter
- **Example**: `https://yoursite.com/payment-failed/`
- **Use case**: Custom error page, retry instructions, support contact

#### 7. Cancel URL
- **Required**: No
- **Description**: Custom URL to redirect customers if they cancel payment
- **Default**:
  - **WooCommerce**: Cart page
  - **Standalone**: Homepage with cancel parameter
- **Example**: `https://yoursite.com/payment-cancelled/`
- **Use case**: Return to shopping, save cart, offer assistance

### System Settings

#### 8. Sandbox Mode
- **Type**: Checkbox
- **Default**: Enabled (Yes)
- **Description**: Use test environment for development
- **Sandbox URL**: `https://dev.unelmapay.com/sci/form`
- **Production URL**: `https://unelmapay.com.np/sci/form`
- **Important**: Always test in sandbox before going live!

#### 9. Debug Mode
- **Type**: Checkbox
- **Default**: Enabled (Yes)
- **Description**: Enable detailed logging for troubleshooting
- **Log location**: 
  - **WooCommerce**: WooCommerce → Status → Logs → unelmapay
  - **Standalone**: WordPress error log
- **Recommendation**: Enable during testing, optional in production

## Configuration Locations

### WooCommerce Mode
**Path**: WooCommerce → Settings → Payments → UnelmaPay

All fields available in the WooCommerce payment gateway settings page.

### Standalone Mode
**Path**: UnelmaPay → Settings (WordPress admin menu)

All fields available in the standalone settings page.

## Custom URL Setup Examples

### Example 1: Custom Thank You Page

**Create pages:**
1. `/payment-success/` - Success page
2. `/payment-failed/` - Failed page
3. `/payment-cancelled/` - Cancelled page

**Configure URLs:**
- Success URL: `https://yoursite.com/payment-success/`
- Fail URL: `https://yoursite.com/payment-failed/`
- Cancel URL: `https://yoursite.com/payment-cancelled/`

**Page content examples:**

**Success page:**
```html
<h1>Payment Successful!</h1>
<p>Thank you for your payment. Your transaction has been completed.</p>
<p><a href="/">Return to Home</a></p>
```

**Failed page:**
```html
<h1>Payment Failed</h1>
<p>We're sorry, but your payment could not be processed.</p>
<p><a href="/checkout/">Try Again</a> | <a href="/contact/">Contact Support</a></p>
```

**Cancelled page:**
```html
<h1>Payment Cancelled</h1>
<p>You have cancelled the payment process.</p>
<p><a href="/cart/">Return to Cart</a> | <a href="/shop/">Continue Shopping</a></p>
```

### Example 2: Tracking and Analytics

Add tracking codes to your custom pages:

```html
<!-- Success page with Google Analytics conversion -->
<script>
  gtag('event', 'conversion', {
    'send_to': 'AW-CONVERSION_ID/CONVERSION_LABEL',
    'transaction_id': ''
  });
</script>
```

### Example 3: Dynamic Redirects

Use query parameters for dynamic behavior:

**Success URL**: `https://yoursite.com/payment-result/?status=success`
**Fail URL**: `https://yoursite.com/payment-result/?status=failed`
**Cancel URL**: `https://yoursite.com/payment-result/?status=cancelled`

Then handle in your page template:
```php
<?php
$status = isset($_GET['status']) ? $_GET['status'] : '';
switch($status) {
    case 'success':
        echo '<h1>Payment Successful!</h1>';
        break;
    case 'failed':
        echo '<h1>Payment Failed</h1>';
        break;
    case 'cancelled':
        echo '<h1>Payment Cancelled</h1>';
        break;
}
?>
```

## Best Practices

### Security
1. **Never expose Merchant Password** in frontend code
2. **Use HTTPS** for all custom URLs
3. **Validate IPN callbacks** (handled automatically by plugin)
4. **Keep credentials secure** - don't commit to version control

### Testing
1. **Always test in Sandbox mode** first
2. **Test all three scenarios**: success, fail, cancel
3. **Verify IPN callbacks** are received
4. **Check custom URLs** redirect correctly
5. **Test with real payment amounts** in sandbox

### Production Deployment
1. **Disable Sandbox Mode** ✓
2. **Update to production credentials** ✓
3. **Test with small real transaction** ✓
4. **Monitor logs initially** ✓
5. **Disable Debug Mode** (optional, for performance)

### URL Configuration
1. **Use absolute URLs** (include https://)
2. **Ensure URLs are accessible** (not password protected)
3. **Test URLs manually** before configuring
4. **Consider mobile experience** for custom pages
5. **Add clear call-to-action** on result pages

## Troubleshooting

### Issue: Custom URLs not working
**Solution**: 
- Verify URLs are absolute (include https://)
- Check URLs are accessible (test in browser)
- Clear WordPress cache
- Check for redirect loops

### Issue: IPN not received
**Solution**:
- Verify site is accessible from internet
- Check firewall settings
- Enable Debug Mode and check logs
- Test IPN URL manually: `https://yoursite.com/?unelmapay_ipn=1`

### Issue: Settings not saving
**Solution**:
- Check user permissions (must be admin)
- Verify no plugin conflicts
- Check PHP error logs
- Try disabling other plugins temporarily

## Configuration Checklist

### Initial Setup
- [ ] Obtain Merchant ID from UnelmaPay
- [ ] Obtain Merchant Password from UnelmaPay
- [ ] Enable Sandbox Mode
- [ ] Enable Debug Mode
- [ ] Enter Merchant ID
- [ ] Enter Merchant Password
- [ ] Test payment in sandbox

### Optional Configuration
- [ ] Set Merchant Name
- [ ] Set Merchant Email
- [ ] Create custom success page
- [ ] Create custom fail page
- [ ] Create custom cancel page
- [ ] Configure Success URL
- [ ] Configure Fail URL
- [ ] Configure Cancel URL
- [ ] Test all custom URLs

### Production Launch
- [ ] Complete sandbox testing
- [ ] Create production merchant account
- [ ] Update Merchant ID (production)
- [ ] Update Merchant Password (production)
- [ ] Disable Sandbox Mode
- [ ] Test with real small transaction
- [ ] Monitor first few transactions
- [ ] Document configuration for team

## Support

For configuration assistance:
- **Email**: support@unelmapay.com
- **Documentation**: https://docs.unelmapay.com/
- **Plugin Support**: GitHub Issues
