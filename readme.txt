=== UnelmaPay Payment Gateway ===
Contributors: unelmaplatforms
Tags: woocommerce, payment gateway, unelmapay, nepal, payment, ecommerce, donations, shortcode
Requires at least: 5.2
Tested up to: 6.4
Requires PHP: 7.2
Stable tag: 2.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Accept payments via UnelmaPay. Works standalone with shortcodes or integrates with WooCommerce. Supports sandbox and production.

== Description ==

UnelmaPay Payment Gateway allows you to accept payments on your WordPress site. Works in two modes:

**Standalone Mode** - Use shortcodes for payments, donations, memberships (no WooCommerce required)
**WooCommerce Mode** - Full checkout integration when WooCommerce is active

Perfect for both e-commerce stores and non-commerce sites accepting payments.

= Features =

* **Dual Mode Operation** - Works with or without WooCommerce
* **Shortcode Support** - `[unelmapay_button]` for payments anywhere
* **WooCommerce Integration** - Full checkout integration when active
* Sandbox mode for testing (dev.unelmapay.com)
* Production mode (unelmapay.com.np)
* IPN (Instant Payment Notification) callback support
* Secure hash verification
* Debug logging for troubleshooting
* Automatic payment tracking
* Support for multiple currencies

= Use Cases =

* E-commerce stores (with WooCommerce)
* Donation pages
* Membership sites
* Service bookings
* Event registrations
* Subscription payments
* Any payment collection

= Requirements =

* WordPress 5.2 or higher
* PHP 7.2 or higher
* SSL certificate (HTTPS) for production
* UnelmaPay merchant account
* WooCommerce 4.0+ (optional, for e-commerce mode)

= About UnelmaPay =

UnelmaPay is a secure payment gateway that allows businesses to accept online payments. Visit [UnelmaPay](https://unelmapay.com/) to create a merchant account.

= Support =

For support and documentation, please visit:
* [Documentation](https://docs.unelmapay.com/)
* Email: support@unelmapay.com

== Installation ==

= Automatic Installation =

1. Log in to your WordPress admin panel
2. Go to Plugins > Add New
3. Search for "UnelmaPay Payment Gateway"
4. Click "Install Now" and then "Activate"

= Manual Installation =

1. Download the plugin zip file
2. Log in to your WordPress admin panel
3. Go to Plugins > Add New > Upload Plugin
4. Choose the downloaded zip file and click "Install Now"
5. Activate the plugin

= Configuration =

**With WooCommerce:**
1. Go to WooCommerce > Settings > Payments > UnelmaPay
2. Enable the payment gateway
3. Enter your Merchant ID and Password
4. Enable Sandbox Mode for testing
5. Save changes

**Without WooCommerce (Standalone):**
1. Go to UnelmaPay > Settings in WordPress admin
2. Enter your Merchant ID and Password
3. Enable Sandbox Mode for testing
4. Save changes
5. Use shortcode: `[unelmapay_button amount="100" title="Product Name"]`

== External services ==

This plugin connects to the UnelmaPay payment processing API to securely handle 
payment transactions.

It sends payment data including merchant ID, item name, amount, currency, 
order/payment ID, and return URLs when a user initiates a payment by clicking 
the payment button. This data is transmitted securely over HTTPS.

For testing, the sandbox environment (https://dev.unelmapay.com/sci/form) is used. 
This environment is strictly for development and testing purposes and does not 
process real transactions. It redirects to the developer documentation page: 
[UnelmaPay Developer Portal](https://dev.unelmapay.com/developers).
For live payments, the production environment 
(https://unelmapay.com.np/sci/form) is used. 
This URL is intended for programmatic use by the plugin and may not display meaningful 
content when accessed directly via a browser. If you encounter a "Coming Soon" page, 
this is expected behavior as the URL is not designed for direct browser access.

This service is provided by Unelma Platforms: 
[Terms of Service](https://unelmapay.com.np/agreement), 
[Privacy Policy](https://unelmapay.com.np/privacy).

== Frequently Asked Questions ==

= Do I need WooCommerce? =

No! The plugin works in two modes:
- **Standalone Mode**: Use shortcodes without WooCommerce
- **WooCommerce Mode**: Full checkout integration when WooCommerce is active

= Do I need a UnelmaPay merchant account? =

Yes, you need a UnelmaPay merchant account to use this plugin. Visit [UnelmaPay](https://unelmapay.com/) to sign up.

= How do I use the shortcode? =

Add this to any page or post:
`[unelmapay_button amount="100" title="Product Name" description="Description" button_text="Pay Now"]`

Parameters:
- `amount` (required) - Payment amount
- `title` (required) - Item name
- `description` (optional) - Item description
- `button_text` (optional) - Button label

= How do I test the plugin before going live? =

Enable "Sandbox Mode" in the plugin settings. This will use the test environment at dev.unelmapay.com. Make sure you have test merchant credentials from UnelmaPay.

= What is the IPN callback URL? =

The IPN callback URL is automatically generated by the plugin:
`https://yoursite.com/wc-api/WC_Gateway_UnelmaPay`

This URL must be accessible from the internet for payment notifications to work.

= Why is my order not completing automatically? =

Check the following:
* IPN URL is accessible from the internet
* Firewall is not blocking UnelmaPay requests
* Merchant password is correct
* Enable debug logging and check logs at WooCommerce > Status > Logs

= How do I switch from sandbox to production? =

1. Go to WooCommerce > Settings > Payments > UnelmaPay
2. Uncheck "Enable Sandbox Mode"
3. Enter your production Merchant ID and Password
4. Save changes

= Where can I find debug logs? =

Go to WooCommerce > Status > Logs and select the "unelmapay" log file.

== Screenshots ==

1. Plugin settings page in WooCommerce
2. Payment method selection at checkout
3. UnelmaPay payment form
4. Order completion confirmation

== Changelog ==

= 2.0.0 - 2024-12-23 =
* Complete rewrite for modern WooCommerce compatibility
* Updated to use latest UnelmaPay API endpoints
* Added sandbox mode (dev.unelmapay.com)
* Improved IPN handling with proper hash verification
* Added comprehensive debug logging
* Simplified configuration
* Better error handling
* Full WooCommerce integration
* Removed custom cart system
* Added proper order status management

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 2.0.0 =
Major update with new API endpoints and improved WooCommerce integration. Please reconfigure your settings after updating.

== Additional Information ==

= API Documentation =
* [Getting Started](https://docs.unelmapay.com/getting-started/overview/)
* [Payment Form Integration](https://docs.unelmapay.com/api/payment-form/)
* [IPN Callback](https://docs.unelmapay.com/api/ipn-callback/)

= Privacy Policy =
This plugin does not collect or store any personal data. All payment information is processed securely by UnelmaPay.

= Support =
For technical support, please contact support@unelmapay.com or visit the [support portal](https://unelmasupport.com).
