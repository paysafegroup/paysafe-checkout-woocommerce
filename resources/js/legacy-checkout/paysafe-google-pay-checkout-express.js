(function( $ ) {
    'use strict';

    const { __ } = wp.i18n;

    let is_apple_pay_google_pay_combo_activated = false;
    if (!!google_pay_paysafe_config.is_apple_pay_express_enabled && !!google_pay_paysafe_config.is_google_pay_express_enabled) {
        is_apple_pay_google_pay_combo_activated = true;
    }

    $( document ).ready(function() {
        init_express_google_pay_on_checkout_page();
    });

    const validate_paysafe_safe_path = function(endpoint) {
        const validated_endpoint = new URL(endpoint, window.location.href);

        if (validated_endpoint.origin !== window.location.origin) {
            show_paysafe_error(__('Blocked non-same-origin endpoint', "paysafe-checkout") + " " + validated_endpoint.toString());
            return;
        }

        return validated_endpoint.pathname + validated_endpoint.search + validated_endpoint.hash;
    }

    const get_cart_data = function() {
        return {
            total: parseInt(parseFloat($('.order-total .woocommerce-Price-amount').first().text().replace(/[^0-9.,]/g, ''))*100),
            currency_code: google_pay_paysafe_config.currency_code,
            billing: {
                first_name: $('#billing_first_name').val(),
                last_name: $('#billing_last_name').val(),
                address_1: $('#billing_address_1').val(),
                address_2: $('#billing_address_2').val(),
                city: $('#billing_city').val(),
                postcode: $('#billing_postcode').val(),
                state: $('#billing_state').val(),
                country: $('#billing_country').val(),
                email: $('#billing_email').val(),
                phone: $('#billing_phone').val(),
            },
            order_comments: $('#order_comments').val(),
        };
    }

    let cart_data = {};
    $( document.body ).on('updated_checkout', function() {
        cart_data = get_cart_data();
    });

    const log_paysafe_error = function(message, context) {
        if (google_pay_paysafe_config.log_errors === true && google_pay_paysafe_config.log_error_endpoint) {
            const safe_path = validate_paysafe_safe_path(google_pay_paysafe_config.log_error_endpoint);
            fetch(
                safe_path,
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
                .catch((error) => {
                });
        }
    }

    const show_paysafe_error = function (message) {
        log_paysafe_error(message, {});

        let paysafe_container = document.getElementById('paysafe-checkout-express-google-pay-container');
        if (paysafe_container) {
            let error_message = document.createElement('div');
            error_message.className = 'paysafe-error-message woocommerce-error';
            error_message.innerText = message;
            paysafe_container.appendChild(error_message);
        }
    }

    const clear_paysafe_error = function () {
        let paysafe_container = document.getElementById('paysafe-checkout-express-google-pay-container');
        if (paysafe_container) {
            let error_message = paysafe_container.querySelector('.paysafe-error-message');
            if (error_message) {
                paysafe_container.removeChild(error_message);
            }
        }
    }

    let hostedGooglePayInstance = null;
    let is_express_google_pay_initialized = false;

    const init_express_google_pay_on_checkout_page = function() {
        if (is_apple_pay_google_pay_combo_activated) {
            return;
        }

        if (!document.getElementById('paysafe-checkout-express-google-pay-container')) {
            return;
        }

        if (is_express_google_pay_initialized) {
            return;
        }

        const container = document.getElementById('paysafe-checkout-express-google-pay-container');
        // create the button container
        const button_container = document.createElement('div');
        button_container.id = 'paysafe-google-pay-express-button';
        button_container.className = 'paysafe-google-pay-button';
        button_container.style.height = '55px';
        button_container.style.width = '240px'; // Adjust as needed
        button_container.style.margin = '0 auto'; // Adjust as needed
        container.appendChild(button_container);

        is_express_google_pay_initialized = true;
        clear_paysafe_error();

        const paysafeOptions = {
            currencyCode: google_pay_paysafe_config.currency_code,
            environment: google_pay_paysafe_config.test_mode ? 'TEST' : 'LIVE',
            transactionSource: 'WooCommerceJs',
            accounts: {
                default: parseInt(google_pay_paysafe_config.account_id),
                googlePay: parseInt(google_pay_paysafe_config.account_id),
            },
            fields: {
                googlePay: {
                    selector: '#paysafe-google-pay-express-button',
                    type: 'buy',
                    color: 'black',
                    label: 'Google Pay',
                    merchantId: google_pay_paysafe_config.express_merchant_ref_num,
                    country: google_pay_paysafe_config.google_pay_issuer_country,

                    buttonWidth: '240px',
                    buttonHeight: '55px',
                },
            },
        };

        let button_clicked = false;

        paysafe.fields
            .setup(
                google_pay_paysafe_config.authorization,
                paysafeOptions
            )
            .then(instance => {
                hostedGooglePayInstance = instance;
                return instance.show();
            })
            .then(paymentMethods => {
                if (paymentMethods.googlePay && !paymentMethods.googlePay.error) {
                    document.getElementById('paysafe-google-pay-express-button').addEventListener(
                        'click',
                        function (event) {
                            event.preventDefault();
                            event.stopPropagation();
                            clear_paysafe_error();

                            let cart_data = get_cart_data();
                            let paymentAmount = parseInt(cart_data.total);
                            if (!paymentAmount) {
                                show_paysafe_error(cart_data.message ? cart_data.message : __("ERROR: Express checkout order amount is not set or invalid", "paysafe-checkout"));
                                return;
                            }

                            let billing_street = cart_data.billing.address_1;
                            let billing_street2 = cart_data.billing.address_2;
                            let billing_city = cart_data.billing.city;
                            let billing_zip = cart_data.billing.postcode;
                            let billing_state = cart_data.billing.state;
                            let billing_country = cart_data.billing.country;

                            button_clicked = true;

                            const currency_code = cart_data.currency_code;
                            const merchant_ref_num = google_pay_paysafe_config.express_merchant_ref_num || 'google_pay_' + Date.now();

                            const hostedTokenizationOptions = {
                                transactionSource: 'WooCommerceJs',
                                amount: paymentAmount,
                                transactionType: 'PAYMENT',
                                currency: currency_code,
                                merchantRefNum: merchant_ref_num,
                                environment: google_pay_paysafe_config.test_mode ? 'TEST' : 'LIVE',
                                paymentType: 'GOOGLEPAY',
                                googlePay: {
                                    country: google_pay_paysafe_config.google_pay_issuer_country,
                                    requiredBillingContactFields: ['email'],
                                    requiredShippingContactFields: ['name'],
                                },
                                accountId: parseInt(google_pay_paysafe_config.google_pay_account_id),
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
                                    merchantUrl: google_pay_paysafe_config.checkout_url,
                                    deviceChannel: "BROWSER",
                                    messageCategory: "PAYMENT",
                                    authenticationPurpose: "PAYMENT_TRANSACTION",
                                    transactionIntent: "GOODS_OR_SERVICE_PURCHASE",
                                },
                            };

                            hostedGooglePayInstance
                                .tokenize(hostedTokenizationOptions)
                                .then(result => {
                                    const paymentToken = result.token || null;
                                    if (!paymentToken) {
                                        const error_message = __("ERROR: Express checkout couldn't tokenize the payment information", "paysafe-checkout");

                                        show_paysafe_error(error_message, []);
                                        return;
                                    }

                                    button_clicked = true;

                                    const safe_path = validate_paysafe_safe_path(google_pay_paysafe_config.express_checkout_endpoint);
                                    fetch(
                                        safe_path,
                                        {
                                            method: 'POST',
                                            credentials: 'same-origin',
                                            headers: new Headers({
                                                'Content-Type': 'application/json',
                                            }),
                                            body: JSON.stringify({
                                                gateway_id: 'google_pay',
                                                billing: cart_data.billing,
                                                order_comments: cart_data.order_comments,
                                                nonce: google_pay_paysafe_config.nonce,
                                            }),
                                        })
                                        .then(result => {
                                            return result.json();
                                        })
                                        .then(json => {
                                            if (json.status === 'success') {
                                                const orderId = json.order_id || null;
                                                if (!orderId) {
                                                    show_paysafe_error(__("ERROR: Express checkout couldn't create Order", "paysafe-checkout"), []);
                                                    return;
                                                }

                                                let total_price = parseInt(json.total_price);
                                                if (!total_price || total_price !== paymentAmount) {
                                                    show_paysafe_error(__("ERROR: Express checkout Order price mismatch!", "paysafe-checkout"), []);
                                                    return;
                                                }

                                                const paymentData = {
                                                    orderId: orderId,
                                                    paymentMethod: 'GOOGLEPAY',
                                                    transactionType: 'PAYMENT',
                                                    paymentHandleToken: paymentToken,
                                                    amount: paymentAmount,
                                                    customerOperation: '',
                                                    merchantRefNum: merchant_ref_num,
                                                    nonce: google_pay_paysafe_config.nonce,
                                                };

                                                const safe_path = validate_paysafe_safe_path(google_pay_paysafe_config.register_url);
                                                fetch(
                                                    safe_path,
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
                                                            show_paysafe_error(__("ERROR: Express checkout payment failed", "paysafe-checkout") + (json.message ? " (" + json.message + ")" : ""));
                                                            hostedGooglePayInstance.complete('fail');
                                                        }

                                                        window.location = json.redirect_url;
                                                    })
                                                    .catch((error) => {
                                                        // the BE call failed
                                                        const error_message = __('ERROR', "paysafe-checkout") + ' ' + error.code + ': ' + error.detailedMessage;

                                                        show_paysafe_error(error_message);

                                                        hostedGooglePayInstance.complete('fail');
                                                    });
                                            } else {
                                                show_paysafe_error(__("ERROR: Express checkout couldn't create the order", "paysafe-checkout"));
                                            }
                                        })
                                        .catch(error => {
                                            const error_message = __('ERROR', "paysafe-checkout") + ' ' + error.code + ': ' + error.detailedMessage;

                                            show_paysafe_error(error_message);

                                            hostedGooglePayInstance.complete('fail');
                                        });
                                })
                                .catch(error => {
                                    // display the tokenization error in dialog window
                                    let error_message = (__('ERROR', "paysafe-checkout") + ' ' + error.code + (error.detailedMessage ? ': ' + error.detailedMessage : ""));
                                    if (error.displayMessage) {
                                        error_message = error.displayMessage + (error.detailedMessage ? ': ' + error.detailedMessage : "");
                                    }

                                    log_paysafe_error(error_message, []);

                                    if (error.detailedMessage && error.detailedMessage.includes('User aborted payment')) {
                                        show_paysafe_error(error.detailedMessage);
                                    } else {
                                        show_paysafe_error(error_message);
                                    }
                                });
                        },
                        false
                    );
                }
            })
            .catch(error => {
                if (error.error) {
                    error = error.error;
                }

                // this means that the initialization of the form failed,
                // disable this payment method
                let error_message = (__('ERROR', "paysafe-checkout") + ' ' + error.code + (error.detailedMessage ? ': ' + error.detailedMessage : ""));
                if (error.displayMessage) {
                    error_message = error.displayMessage + (error.detailedMessage ? ': ' + error.detailedMessage : "");
                }
                log_paysafe_error(error_message, []);

                if (error.detailedMessage && error.detailedMessage.includes('User aborted payment')) {
                    show_paysafe_error(error.detailedMessage);
                } else {
                    if (button_clicked) {
                        button_clicked = false;
                        show_paysafe_error(error_message);
                    } else {
                        show_paysafe_error(__('Error! Unable to set up Express Checkout at this moment. Please, try again later.', "paysafe-checkout") + (error.code ? ' (' + error.code + ')' : ""));
                    }
                }
            });
    };

})( jQuery );
