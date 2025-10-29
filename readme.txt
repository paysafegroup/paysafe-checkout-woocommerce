=== Paysafe Checkout ===
Contributors: paysafegateway
Tags: payment, paysafe, gateway, checkout, credit card
Requires at least: 6.3
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 3.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html


This plugin links your WordPress WooCommerce shop to the Paysafe payment gateway.


== Description ==

With Paysafe’s global expertise in payments, we’re the ideal partner for WooCommerce merchants looking to transform everyday transactions into exceptional customer experiences. The Paysafe Checkout allows you to accept payments via credit and debit cards, Apple Pay, Skrill and Neteller Wallets, Paysafecard and Paysafecash.

**Grow your business with**:

- Global coverage, worldwide transactions, and possibility to grow your business
- Quick and easy sign-up for a merchant account
- White-labeling functionality and customization that gives you the power to build your own checkout experience
- Built-in fraud protection
- Fully PCI-compliant checkout solution
- Supports both redirect and embedded payment flows
- Full and partial refunds supported
- Saved cards in Customer Vault and Woocommerce
- Tokenization with CVV verification
- Apple Pay support
- Google Pay support
- WooCommerce Subscription support

Use of the plugin requires a valid Paysafe merchant account.
**Don't have an account?** [Sign up for free today!](https://merchant.paysafe.com/onboarding-form/#/signup?configId=505240)

This service is provided by **Paysafe Group**. Visit our [Developer page](https://developer.paysafe.com/en/api-docs/shopping-carts/woocommerce-official/) dedicated to the Paysafe Checkout for WooCommerce extension.

Note: WooCommerce must be installed for this plugin to work.


== Frequently Asked Questions ==

= Does this plugin use any external services? =
This plugin connects to Paysafe’s payment gateway services to enable merchants to accept online payments using the Paysafe platform.

**It communicates with Paysafe APIs during the following events**:

- When the merchant saves API credentials in the plugin settings (used to authenticate and fetch available payment methods).
- When a customer proceeds through checkout (used to process payment transactions, authorizations, captures, refunds, voids, etc.).
- When a customer adds or manages saved payment methods (used to create or update customer profiles and securely tokenize payment data).

**Data sent to Paysafe includes**:

- Merchant credentials (username and API key).
- Order details such as amount, currency, and product metadata.
- Customer information including name, email address, billing and shipping address, and saved payment tokens (if applicable).
- Card data or payment method information, where applicable (sent securely via hosted fields or tokenization methods compliant with PCI-DSS standards).

**This plugin primarily uses the following Paysafe solutions**:

- Paysafe Payments API
- Paysafe Checkout
- Paysafe JS

These services are used to securely process and manage transactions, but use is not strictly limited to these APIs and may include other parts of the Paysafe platform where applicable.

These services are provided by **Paysafe Group**:

- [Website](https://developer.paysafe.com/en/api-docs/)
- [Terms of Use](https://developer.paysafe.com/en/support/reference-information/terms-of-use/)
- [Privacy Notice](https://www.paysafe.com/en/paysafegroup/comprehensive-privacy-notice/)
- [Regulatory Disclosures](https://www.paysafe.com/en/paysafegroup/regulatory-disclosures/)

= Where can I find the React source code? =
The source of the minified JavaScript files is located in "resources/js/frontend"

= Where can I find the Paysafe Checkout repository? =
The public repository for this plugin can be found at [https://github.com/paysafegroup/paysafe-checkout-woocommerce](https://github.com/paysafegroup/paysafe-checkout-woocommerce)

= Does the plugin support test mode? =
Yes, the plugin supports both test and production modes. Merchants can configure separate API keys for each environment, allowing them to switch easily between testing and live transactions. This is useful for safe testing and integration before going live. The environment can be selected directly in the plugin settings.

= Where can I find the Merchant Manual and support? =
In case of any questions, issues, or suggestions for improvement, please don’t hesitate to contact us at [zen-team@paysafe.com](mailto:zen-team@paysafe.com). We kindly ask you to read the Merchant Manual, which is included with the extension or available on our official GitHub repository: [Paysafe-Checkout-for-WooCommerce-Merchant-Guide](https://github.com/paysafegroup/paysafe-checkout-woocommerce/blob/main/Paysafe-Checkout-for-WooCommerce-Merchant-Guide.pdf). Visit our [Developer page](https://developer.paysafe.com/en/api-docs/shopping-carts/woocommerce-official/) dedicated to the Paysafe Checkout for WooCommerce extension.


== Screenshots ==

1. Example of a checkout page with direct card payment. The customer stays on the WooCommerce checkout page throughout the process. Only card payments are supported.
2. Successful order confirmation.
3. Example of a checkout page using a redirection integration type. Saved cards, card payments and alternative payment methods (APMs) are supported.
4. Payment step where the customer enters their card details. The customer can also choose to save the card for future purchases.
5. Successful payment confirmation.


== Changelog ==

= 3.1.1, 2025-10-29 =

**New Features**

* Added Apple Pay Express checkout

[See full changelog for details](https://raw.githubusercontent.com/paysafegroup/paysafe-checkout-woocommerce/refs/heads/main/CHANGELOG.md).
