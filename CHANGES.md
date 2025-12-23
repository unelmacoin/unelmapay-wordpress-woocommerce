# Changes from Old Plugin to New Plugin

## Critical Endpoint Updates

### Old Plugin (Incorrect)
```php
// Line 84-87 in class-unelmapay_payment_contract.php
if (strpos($domain, '.dev') !== false || strpos($domain, '.local') !== false || $domain == 'cli') {
    $this->payment_url = 'http://pay.unelmapay.local/api/payrequest';
} else {
    $this->payment_url = 'https://merchant.unelmapay/api/payrequest';
}
```

### New Plugin (Correct)
```php
if ($this->sandbox_mode) {
    $this->payment_url = 'https://dev.unelmapay.com/sci/form';
} else {
    $this->payment_url = 'https://unelmapay.com.np/sci/form';
}
```

**Why this matters:**
- Old plugin used non-existent endpoints
- New plugin uses documented API endpoints from https://docs.unelmapay.com/

## Integration Method

### Old Plugin
- Used API POST request with `wp_remote_post()`
- Complex parameter structure
- Custom encryption/decryption methods
- Required API key in headers

### New Plugin
- Uses simple HTML form POST (as per official docs)
- Standard form parameters
- No encryption needed
- Direct browser redirect to payment gateway

## IPN Handling

### Old Plugin
- No proper IPN implementation
- Used custom decryption for responses
- Complex AES-128-CBC encryption

### New Plugin
- Proper IPN callback endpoint
- Standard hash verification: `MD5(total:merchant_password:date:id_transfer)`
- Follows official documentation exactly

## WooCommerce Integration

### Old Plugin
- Custom cart system using Moltin\Cart
- Custom order post type
- Shortcode-based buttons
- Not integrated with WooCommerce checkout

### New Plugin
- Native WooCommerce payment gateway
- Uses WooCommerce orders
- Standard checkout flow
- Proper order status management

## Configuration

### Old Plugin
- Custom settings page
- Separate from WooCommerce settings
- Manual URL configuration required

### New Plugin
- Integrated with WooCommerce → Settings → Payments
- Automatic IPN URL generation
- Simple sandbox/production toggle

## Payment Parameters

### Old Plugin Parameters
```php
array(
    'order' => $order_id,
    'order_id' => $order_id,
    'merchant' => $this->merchant_id,
    'item_name' => $all_items_name,
    'item_number' => $all_items_number,
    'custom' => $all_items_number,
    'amount' => $order_total,
    'currency' => "debit_base",
    'custom' => $customer_note,  // Duplicate key!
    'first_name' => $billing_first_name,
    'last_name' => $billing_last_name,
    'email' => $billing_email,
    'phone' => $billing_phone,
    'address' => $billing_address_1,
    'city' => $billing_city,
    'state' => $billing_state,
    'country' => $billing_country,
    'postalcode' => $billing_postcode,
    'notify_url' => $redirect_url,
    'success_url' => $success_url,
    'fail_link' => "",
    'version' => 2,
    'mode' => $mode
)
```

### New Plugin Parameters (Per Docs)
```php
array(
    'merchant' => $merchant_id,
    'item_name' => $item_name,
    'amount' => $amount,
    'currency' => 'debit_base',
    'custom' => $order_id,
    'return_url' => $return_url,
    'cancel_url' => $cancel_url,
    'notify_url' => $notify_url
)
```

**Simplified and matches official documentation.**

## File Structure

### Old Plugin
```
unelmapay_payment_gateway_final/
├── admin/
│   └── class-unelmapay_payment_gateway-admin.php (484 lines)
├── includes/
│   ├── class-unelmapay_payment_contract.php (235 lines)
│   ├── class-unelmapay_payment_gateway.php
│   ├── class-unelmapay_payment_order.php
│   └── cart/ (491 items!)
├── public/
│   └── class-unelmapay_payment_gateway-public.php (1116 lines)
└── unelmapay_payment_gateway.php
```

### New Plugin
```
unelmapay-woocommerce/
├── includes/
│   └── class-wc-gateway-unelmapay.php (clean, focused)
├── unelmapay-woocommerce.php (main file)
├── README.md
├── TESTING.md
└── CHANGES.md
```

**Much simpler, focused, maintainable.**

## Code Quality Improvements

### Old Plugin Issues
1. Duplicate array keys (`'custom'` appears twice)
2. Hardcoded local development URLs
3. Mixed concerns (cart, orders, payment in one plugin)
4. No proper error handling
5. Inconsistent coding standards
6. 491 cart-related files (unnecessary)

### New Plugin Benefits
1. Clean, single-purpose code
2. Follows WordPress/WooCommerce standards
3. Proper error handling and logging
4. Well-documented
5. Easy to maintain
6. Follows official UnelmaPay API docs

## Security Improvements

### Old Plugin
- Custom encryption (potential security risk if implemented incorrectly)
- No hash verification on callbacks
- Exposed merchant key in JavaScript

### New Plugin
- Standard MD5 hash verification (as per UnelmaPay docs)
- Secure IPN handling
- No sensitive data in frontend
- Proper nonce verification

## Testing Improvements

### Old Plugin
- No clear testing instructions
- Hardcoded local URLs
- No sandbox mode toggle

### New Plugin
- Clear testing guide (TESTING.md)
- Sandbox mode checkbox
- Debug logging
- Easy to switch between environments

## Migration Path

To migrate from old to new plugin:

1. **Backup your site and database**

2. **Note your current settings:**
   - Merchant ID
   - Merchant Password (Key)

3. **Deactivate old plugin:**
   - Don't delete yet (keep as backup)

4. **Install new plugin:**
   - Upload `unelmapay-woocommerce` folder
   - Activate

5. **Configure new plugin:**
   - WooCommerce → Settings → Payments → UnelmaPay
   - Enter Merchant ID
   - Enter Merchant Password
   - Enable Sandbox Mode (for testing)
   - Enable Debug Logging

6. **Test thoroughly:**
   - Create test order
   - Complete payment
   - Verify IPN callback
   - Check order status

7. **Once confirmed working:**
   - Delete old plugin
   - Switch to production mode

## Summary

The new plugin is a **complete rewrite** that:
- ✅ Uses correct API endpoints
- ✅ Follows official documentation
- ✅ Integrates properly with WooCommerce
- ✅ Simpler and more maintainable
- ✅ Better error handling
- ✅ Proper sandbox support
- ✅ Ready for production use on dev.unelmapay.com
