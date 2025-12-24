# UnelmaPay Standalone Mode Guide

This guide explains how to use UnelmaPay without WooCommerce for donations, payments, and subscriptions.

## Overview

UnelmaPay works in **two modes**:

1. **WooCommerce Mode** - Full e-commerce checkout integration (when WooCommerce is active)
2. **Standalone Mode** - Payment buttons via shortcodes (works without WooCommerce)

## Standalone Mode Features

- ✅ No WooCommerce required
- ✅ Simple shortcode-based payment buttons
- ✅ Payment tracking in WordPress admin
- ✅ Perfect for donations, memberships, services
- ✅ IPN callback support
- ✅ Sandbox and production modes

## Setup

### 1. Install Plugin

Upload and activate the plugin as normal. WooCommerce is **not required**.

### 2. Configure Settings

Go to **UnelmaPay → Settings** in WordPress admin:

- **Merchant ID**: Your UnelmaPay merchant ID
- **Merchant Password**: Your merchant password (for IPN verification)
- **Sandbox Mode**: ✓ Enable for testing (dev.unelmapay.com)
- **Debug Mode**: ✓ Enable for troubleshooting

### 3. Add Payment Buttons

Use the `[unelmapay_button]` shortcode anywhere on your site.

## Shortcode Usage

### Basic Syntax

```
[unelmapay_button amount="100" title="Product Name"]
```

### Parameters

| Parameter | Required | Description | Example |
|-----------|----------|-------------|---------|
| `amount` | Yes | Payment amount | `100` |
| `title` | Yes | Item/product name | `"Donation"` |
| `description` | No | Item description | `"Support our cause"` |
| `button_text` | No | Button label | `"Donate Now"` (default: "Pay Now") |

### Examples

#### Simple Payment Button
```
[unelmapay_button amount="50" title="Basic Membership"]
```

#### Donation Button
```
[unelmapay_button amount="100" title="Donation" description="Support our mission" button_text="Donate Now"]
```

#### Service Payment
```
[unelmapay_button amount="500" title="Consulting Service" description="1 hour consultation" button_text="Book Now"]
```

#### Subscription
```
[unelmapay_button amount="29.99" title="Monthly Subscription" description="Premium membership" button_text="Subscribe"]
```

## Payment Flow

1. **Customer clicks button** - Shortcode renders payment button
2. **Redirect to UnelmaPay** - Customer goes to dev.unelmapay.com (sandbox) or unelmapay.com.np (production)
3. **Customer pays** - Completes payment on UnelmaPay
4. **IPN callback** - UnelmaPay sends notification to your site
5. **Payment recorded** - Status updated in WordPress admin
6. **Customer returns** - Redirected back to your site

## Managing Payments

### View Payments

Go to **UnelmaPay → Payments** in WordPress admin to see all transactions:

- Payment ID
- Amount
- Title/Description
- Status (Pending/Completed)
- Transaction ID
- Date

### Payment Statuses

- **Pending** - Payment initiated but not completed
- **Completed** - Payment successful, IPN received

## IPN Callback

Your IPN callback URL is automatically configured:

```
https://yoursite.com/?unelmapay_ipn=1
```

Make sure this URL is accessible from the internet for payment notifications.

## Use Cases

### 1. Donation Page

```html
<h2>Support Our Cause</h2>
<p>Choose your donation amount:</p>

[unelmapay_button amount="25" title="Donation - $25" button_text="Donate $25"]
[unelmapay_button amount="50" title="Donation - $50" button_text="Donate $50"]
[unelmapay_button amount="100" title="Donation - $100" button_text="Donate $100"]
```

### 2. Membership Site

```html
<h2>Join Our Community</h2>

<div class="membership-plans">
  <h3>Basic Plan</h3>
  [unelmapay_button amount="9.99" title="Basic Membership" button_text="Join Basic"]
  
  <h3>Premium Plan</h3>
  [unelmapay_button amount="29.99" title="Premium Membership" button_text="Join Premium"]
</div>
```

### 3. Service Booking

```html
<h2>Book a Consultation</h2>
<p>30-minute consultation: $50</p>
[unelmapay_button amount="50" title="30-min Consultation" button_text="Book Now"]

<p>60-minute consultation: $90</p>
[unelmapay_button amount="90" title="60-min Consultation" button_text="Book Now"]
```

### 4. Event Registration

```html
<h2>Register for Workshop</h2>
<p>Early Bird: $75 (Limited time)</p>
[unelmapay_button amount="75" title="Workshop Registration - Early Bird" button_text="Register Now"]
```

## Testing in Sandbox

1. Enable **Sandbox Mode** in settings
2. Add shortcode to a page/post
3. Click the payment button
4. You'll be redirected to `https://dev.unelmapay.com/sci/form`
5. Complete test payment
6. Check **UnelmaPay → Payments** for the transaction

## Production Deployment

1. Disable **Sandbox Mode** in settings
2. Enter production Merchant ID and Password
3. Test with a small real transaction
4. Monitor **UnelmaPay → Payments** for status updates

## Troubleshooting

### Button not showing
- Check shortcode syntax
- Verify `amount` and `title` parameters are provided
- Check for PHP errors

### Payment not completing
- Verify IPN URL is accessible: `https://yoursite.com/?unelmapay_ipn=1`
- Check firewall settings
- Enable Debug Mode and check logs
- Verify Merchant Password is correct

### IPN not received
- Test IPN URL accessibility from external network
- Check server error logs
- Verify no .htaccess blocking
- Enable Debug Mode for detailed logs

## Combining with WooCommerce

If you install WooCommerce later:

- ✅ Shortcodes continue to work
- ✅ WooCommerce checkout integration activates automatically
- ✅ Both modes work simultaneously
- ✅ Separate payment tracking for each mode

## Advanced Customization

### Custom Button Styling

Add custom CSS to your theme:

```css
.unelmapay-button {
    background-color: #your-color !important;
    padding: 15px 30px !important;
    font-size: 18px !important;
}
```

### Custom Return Page

Payments automatically return to your homepage. To customize:

1. Create a "Thank You" page
2. Note the page URL
3. Modify the shortcode (requires code customization)

## Support

For questions about standalone mode:
- Email: support@unelmapay.com
- Documentation: https://docs.unelmapay.com/

## Comparison: Standalone vs WooCommerce

| Feature | Standalone Mode | WooCommerce Mode |
|---------|----------------|------------------|
| **Requires WooCommerce** | ❌ No | ✅ Yes |
| **Use Case** | Donations, memberships, services | E-commerce store |
| **Implementation** | Shortcodes | Checkout integration |
| **Product Management** | Manual via shortcodes | WooCommerce products |
| **Order Management** | Simple payment list | Full WooCommerce orders |
| **Inventory** | ❌ No | ✅ Yes |
| **Shipping** | ❌ No | ✅ Yes |
| **Tax Calculation** | ❌ No | ✅ Yes |
| **Coupons** | ❌ No | ✅ Yes |
| **Best For** | Simple payments | Full online store |
