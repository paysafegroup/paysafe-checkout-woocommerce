=== Paysafe Checkout ===
Contributors: paysafegateway
Tags: payment, paysafe, gateway, checkout, credit card
Requires at least: 6.3
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

This plugin links your WordPress WooCommerce shop to the Paysafe payment gateway.


== Description ==

With Paysafe’s global expertise in payments, we’re the ideal partner for WooCommerce merchants looking to transform everyday transactions into exceptional customer experiences. The Paysafe Checkout allows you to accept payments via credit and debit cards, Apple Pay, Skrill and Neteller Wallets, Paysafecard and Paysafecash.

Grow your business with:

- Global coverage, worldwide transactions, and possibility to grow your business
- Quick and easy sign-up for a merchant account
- White-labeling functionality and customization that gives you the power to build your own checkout experience
- Built-in fraud protection
- Fully PCI-compliant checkout solution
- Full and partial refunds supported
- Saved cards in Customer Vault
- Tokenization

Use of the plugin requires a valid Paysafe merchant account. Don't have an account? [Sign up for free today!](https://merchant.paysafe.com/onboarding-form/#/signup?configId=505240)


== React Source Code ==

The source of the minified JavaScript files is located in "resources/js/frontend"


== Paysafe Checkout Repository ==

The public repository for this plugin can be found at [https://github.com/paysafegroup/paysafe-checkout-woocommerce](https://github.com/paysafegroup/paysafe-checkout-woocommerce)


== External services ==

This plugin connects to Paysafe’s payment gateway services to enable merchants to accept online payments using the Paysafe platform.

It communicates with Paysafe APIs during the following events:

- When the merchant saves API credentials in the plugin settings (used to authenticate and fetch available payment methods).
- When a customer proceeds through checkout (used to process payment transactions, authorizations, captures, refunds, voids, etc.).
- When a customer adds or manages saved payment methods (used to create or update customer profiles and securely tokenize payment data).

Data sent to Paysafe includes:

- Merchant credentials (username and API key).
- Order details such as amount, currency, and product metadata.
- Customer information including name, email address, billing and shipping address, and saved payment tokens (if applicable).
- Card data or payment method information, where applicable (sent securely via hosted fields or tokenization methods compliant with PCI-DSS standards).

This plugin primarily uses the following Paysafe solutions:

- Paysafe Payments API
- Paysafe Checkout
- Paysafe JS

These services are used to securely process and manage transactions, but use is not strictly limited to these APIs and may include other parts of the Paysafe platform where applicable.

This service is provided by **Paysafe Group**:

- [Website](https://developer.paysafe.com/en/api-docs/)
- [Terms of Use](https://developer.paysafe.com/en/support/reference-information/terms-of-use/)
- [Privacy Notice](https://www.paysafe.com/en/paysafegroup/comprehensive-privacy-notice/)
- [Regulatory Disclosures](https://www.paysafe.com/en/paysafegroup/regulatory-disclosures/)


== Support & Merchant Manual ==

In case of any questions, issues, or suggestions for improvement, please don’t hesitate to contact us at [zen-team@paysafe.com](mailto:zen-team@paysafe.com). We kindly ask you to read the Merchant Manual, which is included with the extension or available on our official GitHub repository: [Paysafe-Checkout-for-WooCommerce-Merchant-Guide](https://github.com/paysafegroup/paysafe-checkout-woocommerce/blob/main/Paysafe-Checkout-for-WooCommerce-Merchant-Guide.pdf)

== Changelog ==

`v1.0.0`
- Plugin creation.
