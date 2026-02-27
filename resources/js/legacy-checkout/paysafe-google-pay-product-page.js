(function( $ ) {
    'use strict';

    $( document ).ready(function() {
        init_express_google_pay_on_product_page();
    });

    const log_paysafe_error = function(message, context) {
        if (settings.log_errors === true && settings.log_error_endpoint) {
            fetch(
                settings.log_error_endpoint,
                {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: new Headers({
                        'Content-Type': 'application/json',
                    }),
                    body: JSON.stringify({
                        message: message,
                        context: context,
                    }),
                })
                .catch((error) => {});
        }
    }

    const show_paysafe_error = function (message) {
        let paysafe_container = document.getElementById('paysafe-hosted-express-product-payment-form');
        if (paysafe_container) {
            let error_message = document.createElement('div');
            error_message.className = 'paysafe-error-message';
            error_message.innerText = message;
            paysafe_container.appendChild(error_message);
        }
    }

    const clear_paysafe_error = function () {
        let paysafe_container = document.getElementById('paysafe-hosted-express-product-payment-form');
        if (paysafe_container) {
            let error_message = paysafe_container.querySelector('.paysafe-error-message');
            if (error_message) {
                paysafe_container.removeChild(error_message);
            }
        }
    }


    let hostedGooglePayInstance = null;
    let is_express_google_pay_initialized = false;
    const init_express_google_pay_on_product_page = function() {
        // if the page doesn't have the form, don't load it
        if (!jQuery('#paysafe-hosted-express-product-payment-form').length) {
            return;
        }

        if (is_express_google_pay_initialized) {
            return;
        }

        is_express_google_pay_initialized = true;
        clear_paysafe_error();


        const paysafeOptions = {
            // You must provide currencyCode to the Paysafe JS SDK to enable the Payment API integration
            currencyCode: settings.currency_code,

            // select the Paysafe test / sandbox environment
            environment: settings.test_mode ? 'TEST' : 'LIVE',

            transactionSource: 'WooCommerceJs',

            accounts: {
                default: parseInt(settings.account_id),
                googlePay: parseInt(settings.account_id),
            },

            fields: {
                googlePay: {
                    selector: '#paysafe-google-pay',
                    type: 'buy',
                    color: 'black',
                },
            },
        };


        // initialize the hosted iframes using the SDK setup function
        paysafe.fields
            .setup(
                settings.authorization,
                paysafeOptions
            )
            .then(instance => {
                hostedGooglePayInstance = instance;
                return instance.show();
            })
            .then(paymentMethods => {
                if (paymentMethods.googlePay && !paymentMethods.googlePay.error) {
                    document.getElementById('paysafe-google-pay').addEventListener(
                        'click',
                        function (event) {
                            event.preventDefault();
                            event.stopPropagation();

                            clear_paysafe_error();

                            const productCartForm = $( 'form.cart' );
                            if (productCartForm[0].checkValidity()) {
                                const productCartJson = productCartForm.serializeArray();
                                let productCartData = {};
                                productCartJson.forEach(
                                    function (val, key) {
                                        productCartData[val.name] = val.value;
                                    }
                                );
                                const product_id = $('[name="add-to-cart"]').val();
                                productCartData.product_id = product_id;


                                const register_product_purchase_data = {
                                    gateway_id: 'google_pay',
                                    nonce: settings.nonce,
                                    product_data: productCartData,
                                };


                                fetch(
                                    settings.express_checkout_url,
                                    {
                                        method: 'POST',
                                        credentials: 'same-origin',
                                        headers: new Headers({
                                            'Content-Type': 'application/json',
                                        }),
                                        body: JSON.stringify(register_product_purchase_data),
                                    })
                                    .then(result => {
                                        return result.json();
                                    })
                                    .then(json => {
                                        if (json.status === 'success') {
                                            let paymentAmount = parseInt(json.total_price);
                                            if (!paymentAmount) {
                                                log_paysafe_error("ERROR: Express checkout order amount is not set", []);
                                                show_paysafe_error(json.message ? json.message : "ERROR: Express checkout order amount is not set");
                                                return;
                                            }

                                            const order_id = json.order_id || null;
                                            if (!order_id) {
                                                log_paysafe_error("ERROR: Express checkout couldn't create Order", []);
                                                show_paysafe_error(json.message ? json.message : "ERROR: Express checkout couldn't create Order");
                                                return;
                                            }

                                            let billing_street = json.billing.address_1;
                                            let billing_city = json.billing.city;
                                            let billing_zip = json.billing.postcode;
                                            let billing_state = json.billing.state;
                                            let billing_country = json.billing.country;

                                            const currency_code = json.currency_code;
                                            const merchant_ref_num = json.merchant_ref_num || 'google_pay_' + Date.now();
                                            const hostedTokenizationOptions = {
                                                transactionSource: 'WooCommerceJs',
                                                amount: paymentAmount,
                                                transactionType: 'PAYMENT',
                                                currency: currency_code,
                                                merchantRefNum: merchant_ref_num,
                                                environment: settings.test_mode ? 'TEST' : 'LIVE',
                                                paymentType: 'GOOGLEPAY',
                                                googlePay: {
                                                    country: billing_country,
                                                    requiredBillingContactFields: ['email'],
                                                    requiredShippingContactFields: ['name'],
                                                },
                                                accountId: parseInt(settings.account_id),
                                                customerDetails: {
                                                    billingDetails: {
                                                        country: billing_country,
                                                        zip: billing_zip,
                                                        street: billing_street,
                                                        city: billing_city,
                                                        state: billing_state,
                                                    },
                                                },
                                                threeDs: {
                                                    merchantUrl: settings.checkout_url,
                                                    deviceChannel: "BROWSER",
                                                    messageCategory: "PAYMENT",
                                                    authenticationPurpose: "PAYMENT_TRANSACTION",
                                                    transactionIntent: "GOODS_OR_SERVICE_PURCHASE",
                                                },
                                            };


                                            hostedGooglePayInstance
                                                .tokenize(hostedTokenizationOptions)
                                                .then(result => {
                                                    const paymentData = {
                                                        orderId: order_id,
                                                        paymentMethod: 'GOOGLEPAY',
                                                        transactionType: 'PAYMENT',
                                                        paymentHandleToken: result.token,
                                                        amount: paymentAmount,
                                                        customerOperation: '',
                                                        merchantRefNum: merchant_ref_num,
                                                        nonce: settings.nonce,
                                                    };

                                                    fetch(
                                                        settings.register_url,
                                                        {
                                                            method: 'POST',
                                                            credentials: 'same-origin',
                                                            headers: new Headers({
                                                                'Content-Type': 'application/json',
                                                            }),
                                                            body: JSON.stringify(paymentData),
                                                        })
                                                        .then(result => {
                                                            return result.json();
                                                        })
                                                        .then(json => {
                                                            if (json.status === 'success') {
                                                                // close the Google Pay window
                                                                hostedGooglePayInstance.complete('success');
                                                            } else {
                                                                show_paysafe_error("ERROR: Express checkout payment failed" + (json.message ? " (" + json.message + ")" : ""));

                                                                hostedGooglePayInstance.complete('fail');
                                                            }

                                                            window.location = json.redirect_url;
                                                        })
                                                        .catch((error) => {
                                                            // the BE call failed
                                                            const error_message = 'ERROR ' + error.code + ': ' + error.detailedMessage;

                                                            log_paysafe_error(error_message, []);
                                                            show_paysafe_error(error_message);

                                                            hostedGooglePayInstance.complete('fail');
                                                        });
                                                })
                                                .catch(error => {

                                                    // display the tokenization error in dialog window
                                                    const error_message = (error.displayMessage ? error.displayMessage : ('ERROR ' + error.code
                                                        + (error.detailedMessage ? ': ' + error.detailedMessage : "")));

                                                    log_paysafe_error(error_message, []);
                                                    show_paysafe_error(error_message);
                                                });
                                        } else {
                                            show_paysafe_error(json.message ? json.message : "ERROR: Express checkout couldn't create the order");
                                        }
                                    })
                                    .catch((error) => {
                                        // the BE call failed
                                        const error_message = 'ERROR ' + error.code + ': ' + error.detailedMessage;

                                        log_paysafe_error(error_message, []);
                                        show_paysafe_error(error_message);
                                    });
                            }
                        },
                        false,
                    );
                }
            })
            .catch(error => {
                // this means that the initialization of the form failed,
                // disable this payment method
                const error_message = 'ERROR ' + error.code + ': ' + error.detailedMessage;


                log_paysafe_error(error_message, []);
                show_paysafe_error(error_message);
            });
    }

})( jQuery );
