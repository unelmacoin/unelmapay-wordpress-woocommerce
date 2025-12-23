# UnelmaPay WooCommerce Plugin - Testing Guide

## Quick Start Testing on dev.unelmapay.com

### Step 1: Install the Plugin

1. Copy the `unelmapay-woocommerce` folder to your WordPress plugins directory:
   ```
   /wp-content/plugins/unelmapay-woocommerce/
   ```

2. Activate the plugin:
   - WordPress Admin → Plugins
   - Find "UnelmaPay Payment Gateway for WooCommerce"
   - Click "Activate"

### Step 2: Configure for Sandbox Testing

1. Navigate to: **WooCommerce → Settings → Payments**

2. Click on **"UnelmaPay"** to configure

3. Enter the following settings:

   ```
   ✓ Enable UnelmaPay Payment Gateway
   
   Title: UnelmaPay
   Description: Pay securely via UnelmaPay
   
   Merchant ID: [YOUR_TEST_MERCHANT_ID]
   Merchant Password: [YOUR_TEST_MERCHANT_PASSWORD]
   
   ✓ Enable Sandbox Mode (dev.unelmapay.com)
   ✓ Enable Debug Logging
   ```

4. Click **"Save changes"**

### Step 3: Create a Test Product

1. Go to **Products → Add New**
2. Create a simple product:
   - Name: "Test Product"
   - Price: 10.00
   - Click "Publish"

### Step 4: Test the Payment Flow

1. **Add to Cart:**
   - Visit your shop page
   - Add the test product to cart
   - Click "View Cart"

2. **Proceed to Checkout:**
   - Click "Proceed to Checkout"
   - Fill in billing details (use test data)
   - Select "UnelmaPay" as payment method

3. **Place Order:**
   - Click "Place Order"
   - You should see a loading message
   - You'll be auto-redirected to the UnelmaPay payment form

4. **Payment Form:**
   - You should be on: `https://dev.unelmapay.com/sci/form`
   - The form should show:
     - Merchant ID
     - Item name
     - Amount
     - Order reference

5. **Complete Payment:**
   - Complete the payment on UnelmaPay sandbox
   - You'll be redirected back to your site

6. **Verify Order:**
   - Go to **WooCommerce → Orders**
   - Find your test order
   - Status should be "Processing" or "Completed"
   - Check order notes for transaction ID

### Step 5: Check Debug Logs

1. Go to **WooCommerce → Status → Logs**
2. Select the **"unelmapay"** log file
3. Review the logs for:
   - Payment form generation
   - IPN callback received
   - Hash verification
   - Order completion

Expected log entries:
```
Processing payment for order #123
Payment form data for order #123: merchant=XXX, amount=10.00
IPN Request received: Array(...)
IPN Hash verification: received=ABC123, calculated=ABC123
IPN Success: Order #123 marked as paid. Transaction ID: TXN123
```

## Testing IPN Callback Manually

### Test IPN URL Accessibility

Your IPN URL should be:
```
https://yoursite.com/wc-api/WC_Gateway_UnelmaPay
```

Test it's accessible:
```bash
curl -I https://yoursite.com/wc-api/WC_Gateway_UnelmaPay
```

Should return HTTP 200 or 400 (not 404).

### Simulate IPN Callback

You can test IPN handling with a POST request:

```bash
# Calculate hash: MD5(total:merchant_password:date:id_transfer)
# Example: MD5("10.00:YOUR_PASSWORD:2024-12-23 19:00:00:TXN123")

curl -X POST https://yoursite.com/wc-api/WC_Gateway_UnelmaPay \
  -d "total=10.00" \
  -d "date=2024-12-23 19:00:00" \
  -d "id_transfer=TXN123" \
  -d "hash=CALCULATED_HASH" \
  -d "custom=ORDER_ID" \
  -d "item_name=Test Product" \
  -d "currency=debit_base" \
  -d "status=completed"
```

## Common Issues & Solutions

### Issue: Payment form doesn't show

**Solution:**
- Check Merchant ID is configured
- Check WooCommerce is active
- Clear browser cache
- Check for JavaScript errors in console

### Issue: IPN not received

**Solution:**
- Verify IPN URL is accessible from internet
- Check firewall settings
- Verify no .htaccess blocking
- Check debug logs for incoming requests

### Issue: Hash verification fails

**Solution:**
- Verify merchant password is correct (case-sensitive)
- No extra spaces in password field
- Check debug logs for hash comparison
- Ensure using same password as in UnelmaPay dashboard

### Issue: Order stuck in "Pending Payment"

**Solution:**
- Check if IPN was received (debug logs)
- Verify hash verification passed
- Check order notes for errors
- Manually test IPN callback

## Production Checklist

Before going live:

- [ ] Uncheck "Enable Sandbox Mode"
- [ ] Update to production Merchant ID
- [ ] Update to production Merchant Password
- [ ] Verify SSL certificate is valid
- [ ] Test with small real transaction
- [ ] Verify IPN callback works
- [ ] Monitor first few transactions
- [ ] Keep debug logging enabled initially
- [ ] Set up order notification emails

## Key Differences: Old vs New Plugin

### Old Plugin Issues:
- ❌ Used outdated endpoint: `http://pay.unelmapay.local/api/payrequest`
- ❌ Complex API integration with encryption
- ❌ No proper sandbox mode
- ❌ Custom cart system (not WooCommerce native)

### New Plugin (v2.0.0):
- ✅ Uses correct endpoint: `https://dev.unelmapay.com/sci/form`
- ✅ Simple form-based integration (as per docs)
- ✅ Proper sandbox/production toggle
- ✅ Full WooCommerce integration
- ✅ Proper IPN handling with hash verification
- ✅ Better error handling and logging

## Support

If you encounter issues:

1. Check debug logs first
2. Verify all configuration settings
3. Test IPN URL accessibility
4. Contact UnelmaPay support: support@unelmapay.com
5. Provide debug logs when reporting issues
