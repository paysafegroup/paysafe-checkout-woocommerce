# Paysafe Checkout plugin for Woocommerce

With Paysafe’s global expertise in payments, we’re the ideal partner for WooCommerce merchants
looking to transform everyday transactions into exceptional customer experiences.
The Paysafe Checkout allows you to accept payments via credit and debit cards, Apple Pay, Skrill
and Neteller Wallets, Paysafecard and Paysafecash.

Grow your business with:

- Global coverage, worldwide transactions, and the possibility to grow your business
- Quick and easy sign-up for a merchant account
- White-labeling functionality and customization that gives you the power to build your own checkout
  experience
- Built-in fraud protection
- Fully PCI-compliant checkout solution
- Full and partial refunds supported
- Saved cards in Customer Vault
- Tokenization

### Table of Contents

<ul>
    <li>
        <a href="#plugin-features">Plugin features</a>
        <ul>
            <li><a href="#card-payments">Card payments</a></li>
            <li><a href="#support-for-alternative-payment-methods">Support for Alternative Payment Methods</a></li>
            <li><a href="#canceling-the-payment">Canceling the Payment</a></li>
            <li><a href="#refunds">Refunds</a></li>
            <li><a href="#card-payment-with-authorizatin-only-or-with-settlement">Card payment with Authorization only or with Settlement</a></li>
            <li><a href="#saving-card-details-during-checkout">Saving card details during checkout</a></li>
            <li><a href="#post-purchase-payment-for-an-order">Post-purchase payment for an order</a></li>
        </ul>
    </li>
    <br>
    <li>
        <a href="#installation">Installation</a>
        <ul>
            <li><a href="#1-download-the-extension-as-zip-file">1. Download the extension as ZIP file</a></li>
            <li><a href="2-upload-and-install-the-plugin">2. Upload and install the plugin</a></li>
            <li><a href="#3-access-and-configure-the-settings"> 3. Access and configure the settings</a></li>
        </ul>
    </li>
    <br>
    <li><a href="#requirements">Requirements</a></li>
    <li><a href="#licence">Licence</a></li>
</ul>


## Requirements

Minimal WordPress version: 6.3

Minimal PHP version: 8.1

## Plugin features
Use of the plugin requires a valid Paysafe merchant account. Don't have an account? [Sign up for free today!](https://merchant.paysafe.com/onboarding-form/#/signup?configId=505240)

### Card payments

The Paysafe Payments API supports Cards as a Payment Instrument. You can process credit cards, and debit
cards and save or tokenize them on a Customer Profile to charge customers later.
The Payments cater to the following needs for cards:

- Payment Instrument: Credit cards, Debit cards
- Cards Supported: Visa, Visa Debit, Visa Electron, Visa Prepaid, American Express, Mastercard,
  Mastercard Debit (Maestro), Mastercard Prepaid, Discover.
- Transaction types: Payments, Refunds
- Payment authentication: Dynamic 3D Secure 2

### Support for Alternative Payment Methods

Besides card payments, the plugin supports the following payment methods (APMs):

- Skrill
- Neteller
- PaysafeCard
- PaysafeCash

### Canceling the Payment

Payment can be canceled in two ways by changing the order status to Canceled:

1. The first case is when the payment is only authorized, in which a Void transaction is issued to cancel the
   authorization.
2. The second case is canceling the settlement for authorized and captured transactions that haven't yet been
   settled in the payment gateway.

### Refunds

The extension supports automatic refunds, meaning refunds can be processed directly in WooCommerce
without the need to access the merchant portal.

### Card payment with Authorization only or with Settlement

By default, the extension is configured to authorize and capture payments simultaneously.
It is also possible to authorize payments and perform a manual capture later.
Manual capture is supported through the extension by simply changing the order status.

### Saving card details during checkout

The extension supports saving payment details for future use. Customers who choose to save their payment
details can use them for future purchases, requiring only CVV confirmation for such transactions.

### Post-purchase payment for an order

Customer can complete payment for an order if the initial payment attempt was unsuccessful.

## Installation

Follow the instructions below to install the Paysafe Checkout extension:

### 1. Download the extension

Download the plugin files from GitHub repository (preferably as a ZIP file).

### 2. Upload and install the plugin

In the WordPress admin dashboard, go to the Plugins menu and click Add New Plugin.
Click the _**Upload Plugin**_ button, then select _**Choose File**_ and locate the ZIP file you just downloaded from GitHub.
Click _**Install Now**_.
On the next screen, click _**Activate Plugin**_.
The Paysafe Checkout plugin should now appear in the list of installed plugins.

### 3. Access and configure the settings

Click _**Settings**_ to begin configuring the plugin.
Alternatively, you can access the plugin under the WooCommerce menu: go to the _**Settings**_ page and click the _**Payments**_ tab.
From there, you can adjust the plugin settings, change the order, enable/disable it, or complete the configuration.

Once installed and configured, your Paysafe Checkout plugin will be ready for use.

### Support & Merchant Manual

Dear merchants,

In case of any questions, issues, or suggestions for improvement, 
please don’t hesitate to contact us at zen-team@paysafe.com.

We kindly ask you to read the Merchant Manual, 
which is included with the extension or available on our official GitHub repository:
[Paysafe-Checkout-for-WooCommerce-Merchant-Guide](https://github.com/paysafegroup/paysafe-checkout-woocommerce/blob/main/Paysafe-Checkout-for-WooCommerce-Merchant-Guide.pdf)

### Licence

[![License: GPL v2](https://img.shields.io/badge/License-GPL_v2-blue.svg)](https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html)
