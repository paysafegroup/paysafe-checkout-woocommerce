(function ( $ ) {
    $ ( document ).ready(function() {
        let $form = $( 'form.checkout' );
        let next_page = null;

        const log_paysafe_error = function(message, context) {
            if (!!paysafe_settings.log_errors === true && paysafe_settings.log_error_endpoint) {
                fetch(
                    paysafe_settings.log_error_endpoint,
                    {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: new Headers({
                            // 'Content-Type': 'application/x-www-form-urlencoded',
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

        const is_hosted_integration = paysafe_settings.integration_type === 'paysafe_js';
        let hostedLegacyPaymentInstance;

        $form.on(
            'checkout_place_order_paysafe checkout_place_order_apple_pay checkout_place_order_skrill checkout_place_order_neteller checkout_place_order_paysafecash checkout_place_order_paysafecard',
            function(e, wc_checkout_form) {

                e.preventDefault();
                e.stopPropagation();

                $form.addClass( 'processing' );

                wc_checkout_form.blockOnSubmit( $form );

                // Attach event to block reloading the page when the form has been submitted
                wc_checkout_form.attachUnloadEventsOnSubmit();

                // ajaxSetup is global, but we use it to ensure JSON is valid once returned.
                $.ajaxSetup( {
                    dataFilter: function( raw_response, dataType ) {
                        // We only want to work with JSON
                        if ( 'json' !== dataType ) {
                            return raw_response;
                        }

                        if ( wc_checkout_form.is_valid_json( raw_response ) ) {
                            return raw_response;
                        } else {
                            // Attempt to fix the malformed JSON
                            var maybe_valid_json = raw_response.match( /{"result.*}/ );

                            if ( wc_checkout_form.is_valid_json( maybe_valid_json[0] ) ) {
                                raw_response = maybe_valid_json[0];
                            }
                        }

                        return raw_response;
                    }
                } );

                const is_paysafe = $('#payment_method_paysafe').prop('checked');
                const is_apple_pay = $('#payment_method_apple_pay').prop('checked');
                const is_skrill = $('#payment_method_skrill').prop('checked');
                const is_neteller = $('#payment_method_neteller').prop('checked');
                const is_paysafecash = $('#payment_method_paysafecash').prop('checked');
                const is_paysafecard = $('#payment_method_paysafecard').prop('checked');

                let are_fields_valid = true;
                if (is_hosted_integration) {
                    are_fields_valid = false;
                    if (hostedLegacyPaymentInstance) {
                        // check if the hosted integration is filled in and the data is valid
                        are_fields_valid = hostedLegacyPaymentInstance.areAllFieldsValid();
                        if (!are_fields_valid) {
                            wc_checkout_form.submit_error( '<div class="woocommerce-error">' + "The payment process failed. Please enter your card details and try again" + "</div>");
                        }
                    } else {
                        wc_checkout_form.submit_error('<div class="woocommerce-error">' + "The payment process failed. Please reload the page and try again" + "</div>");
                    }
                }

                if (!are_fields_valid) {
                    return false;
                }

                $.ajax({
                    type:		'POST',
                    url:		wc_checkout_params.checkout_url,
                    data:		$form.serialize(),
                    dataType:   'json',
                    success:	function( response ) {
                        // Detach the unload handler that prevents a reload / redirect
                        wc_checkout_form.detachUnloadEventsOnSubmit();

                        try {
                            if ( 'success' === response.result) {
                                if (response.order && response.order.order_id && parseInt(response.order.order_id) ) {
                                    const customerId = parseInt(response.customer.customer_id);
                                    const orderId = parseInt(response.order.order_id);

                                    const customerSingleUseToken = response && response.single_use_token ? response.single_use_token : '';

                                    if (is_hosted_integration) {
                                        let tokenizationOptions = {
                                            transactionSource: 'WooCommerceJs',
                                            amount: parseInt(response.order.amount),
                                            transactionType: 'PAYMENT',
                                            currency: response.order.currency,
                                            merchantRefNum: response.merch_ref_num,
                                            environment: paysafe_settings.test_mode ? 'TEST' : 'LIVE',
                                            threeDs: {
                                                merchantUrl: paysafe_settings.checkout_url,
                                                deviceChannel: "BROWSER",
                                                messageCategory: "PAYMENT",
                                                authenticationPurpose: "PAYMENT_TRANSACTION",
                                                transactionIntent: "GOODS_OR_SERVICE_PURCHASE",
                                                profile: {
                                                    email: paysafe_settings.user_email,
                                                },
                                            },
                                            paymentType: 'CARD',
                                        };

                                        if (paysafe_settings.merchant_descriptor && paysafe_settings.merchant_phone) {
                                            tokenizationOptions.merchantDescriptor = {
                                                dynamicDescriptor: paysafe_settings.merchant_descriptor,
                                                phone: paysafe_settings.merchant_phone,
                                            }
                                        }

                                        // if (customerSingleUseToken) {
                                        //     tokenizationOptions.singleUseCustomerToken = customerSingleUseToken;
                                        //     tokenizationOptions.paymentTokenFrom = customerSingleUseToken;
                                        // }
                                        tokenizationOptions.customerDetails = {
                                            holderName: response.customer.first_name + ' ' + response.customer.last_name,
                                            billingDetails: {
                                                nickName: "Home",
                                                zip: response.billing.zip,
                                                country: response.billing.country,
                                            },
                                            profile: {
                                                firstName: response.customer.first_name,
                                                lastName: response.customer.last_name,
                                                email: response.customer.email,
                                                locale: paysafe_settings.locale,
                                                phone: response.billing.phone,
                                            }
                                        };

                                        if (response.billing.street) {
                                            tokenizationOptions.customerDetails.billingDetails.street = response.billing.street;
                                        }
                                        if (response.billing.street2) {
                                            tokenizationOptions.customerDetails.billingDetails.street2 = response.billing.street2;
                                        }
                                        if (response.billing.city) {
                                            tokenizationOptions.customerDetails.billingDetails.city = response.billing.city;
                                        }
                                        if (response.billing.state) {
                                            tokenizationOptions.customerDetails.billingDetails.state = response.billing.state;
                                        }
                                        if (response.billing.phone) {
                                            tokenizationOptions.customerDetails.billingDetails.phone = response.billing.phone;
                                        }

                                        hostedLegacyPaymentInstance
                                            .tokenize(tokenizationOptions)
                                            .then(result => {
                                                if (!result || !result.token) {
                                                    throw 'Result failure';
                                                }

                                                const paymentData = {
                                                    orderId: orderId,
                                                    paymentMethod: 'CARD',
                                                    transactionType: 'PAYMENT',
                                                    paymentHandleToken: result.token,
                                                    amount: parseInt(response.order.amount),
                                                    customerOperation: '',
                                                    merchantRefNum: response.merch_ref_num,
                                                };

                                                fetch(
                                                    paysafe_settings.register_url,
                                                    {
                                                        method: 'POST',
                                                        credentials: 'same-origin',
                                                        headers: new Headers({
                                                            // 'Content-Type': 'application/x-www-form-urlencoded',
                                                            'Content-Type': 'application/json',
                                                        }),
                                                        body: JSON.stringify(paymentData),
                                                    })
                                                    .then(result => {
                                                        return result.json();
                                                    })
                                                    .then(json => {
                                                        window.location = json.redirect_url;
                                                    })
                                                    .catch((error) => {
                                                        // the BE call failed
                                                        log_paysafe_error("The payment process failed. Please close this popup and try again", []);
                                                        wc_checkout_form.submit_error("The payment process failed. Please close this popup and try again");
                                                    });
                                            })
                                            .catch(error => {
                                                // this means that the tokenization of the card form failed,
                                                // disable this payment method, show the error and refresh the page in 5 seconds
                                                const error_message = 'ERROR ' + error.code + ': ' + error.detailedMessage;
                                                log_paysafe_error(error_message, []);
                                                wc_checkout_form.submit_error(error_message);
                                            });
                                    } else {
                                        // clear next page
                                        next_page = null;

                                        let displayPaymentMetods = [
                                            'card'
                                        ];
                                        let paymentMethodDetails = {
                                            card: {
                                                accountId: paysafe_settings.card_account_id,
                                            }
                                        };

                                        if (is_apple_pay) {
                                            displayPaymentMetods = [
                                                'applePay'
                                            ];
                                            paymentMethodDetails = {
                                                applePay: {
                                                    accountId: paysafe_settings.apple_pay_account_id,
                                                    label: 'apple_pay',
                                                }
                                            };
                                        }

                                        if (is_skrill) {
                                            displayPaymentMetods = [
                                                'skrill'
                                            ];
                                            paymentMethodDetails = {
                                                skrill: {
                                                    accountId: paysafe_settings.skrill_account_id,
                                                    consumerId: paysafe_settings.consumer_id,
                                                    emailSubject: paysafe_settings.details.subject,
                                                    emailMessage: paysafe_settings.details.message,
                                                }
                                            };
                                        }

                                        if (is_neteller) {
                                            displayPaymentMetods = [
                                                'neteller'
                                            ];
                                            paymentMethodDetails = {
                                                neteller: {
                                                    consumerId: paysafe_settings.consumer_id,
                                                }
                                            };
                                        }

                                        if (is_paysafecash) {
                                            displayPaymentMetods = [
                                                'paysafecash'
                                            ];
                                            paymentMethodDetails = {
                                                paysafecash: {
                                                    accountId: paysafe_settings.paysafecash_account_id,
                                                    consumerId: paysafe_settings.consumer_id_encrypted,
                                                }
                                            };
                                        }

                                        if (is_paysafecard) {
                                            displayPaymentMetods = [
                                                'paysafecard'
                                            ];
                                            paymentMethodDetails = {
                                                paysafecard: {
                                                    accountId: paysafe_settings.paysafecard_account_id,
                                                    consumerId: paysafe_settings.consumer_id_encrypted,
                                                }
                                            };
                                        }

                                        const checkout_options = {
                                            transactionSource: 'WooCommerceCheckout',
                                            amount: parseInt(response.order.amount),
                                            transactionType: 'PAYMENT',
                                            currency: response.order.currency,
                                            merchantRefNum: response.merch_ref_num,
                                            environment: paysafe_settings.test_mode ? 'TEST' : 'LIVE',
                                            displayPaymentMethods: displayPaymentMetods,
                                            paymentMethodDetails: paymentMethodDetails,
                                            threeDs: {
                                                merchantUrl: paysafe_settings.checkout_url,
                                                deviceChannel: "BROWSER",
                                                messageCategory: "PAYMENT",
                                                authenticationPurpose: "PAYMENT_TRANSACTION",
                                                transactionIntent: "GOODS_OR_SERVICE_PURCHASE",
                                            },
                                            locale: paysafe_settings.locale,
                                        };

                                        if (paysafe_settings.merchant_descriptor && paysafe_settings.merchant_phone) {
                                            checkout_options.merchantDescriptor = {
                                                dynamicDescriptor: paysafe_settings.merchant_descriptor,
                                                phone: paysafe_settings.merchant_phone,
                                            }
                                        }

                                        if (customerSingleUseToken) {
                                            checkout_options.singleUseCustomerToken = customerSingleUseToken;
                                        } else {
                                            checkout_options.customer = {
                                                firstName: response.customer.first_name,
                                                lastName: response.customer.last_name,
                                                email: response.customer.email,
                                            };
                                            checkout_options.billingAddress = {
                                                nickName: "Home",
                                                zip: response.billing.zip,
                                                country: response.billing.country,
                                            };

                                            if (response.billing.street) {
                                                checkout_options.billingAddress.street = response.billing.street;
                                            }
                                            if (response.billing.street2) {
                                                checkout_options.billingAddress.street2 = response.billing.street2;
                                            }
                                            if (response.billing.city) {
                                                checkout_options.billingAddress.city = response.billing.city;
                                            }
                                            if (response.billing.state) {
                                                checkout_options.billingAddress.state = response.billing.state;
                                            }
                                        }

                                        paysafe.checkout.setup(
                                            paysafe_settings.authorization,
                                            checkout_options,

                                            // resultCallback
                                            function (instance, error, result) {
                                                if (result && result.paymentHandleToken) {
                                                    // Successfully Tokenized transaction, use result.paymentHandleToken to process a payment
                                                    // add AJAX code to send token to your merchant server
                                                    const paymentData = {
                                                        orderId: orderId,
                                                        paymentMethod: result.paymentMethod,
                                                        transactionType: result.transactionType,
                                                        paymentHandleToken: result.paymentHandleToken,
                                                        amount: result.amount,
                                                        customerOperation: result.customerOperation,
                                                        merchantRefNum: response.merch_ref_num,
                                                    };

                                                    fetch(
                                                        paysafe_settings.register_url,
                                                        {
                                                            method: 'POST',
                                                            credentials: 'same-origin',
                                                            headers: new Headers({
                                                                'Content-Type': 'application/json',
                                                            }),
                                                            body: JSON.stringify(paymentData),
                                                        })
                                                        .then(register_response => {
                                                            return register_response.json();
                                                        })
                                                        .then(json => {
                                                            if (json.status === 'success') {
                                                                instance.showSuccessScreen("Your goods are now purchased. Expect them to be delivered in next 5 business days.");
                                                            } else {
                                                                log_paysafe_error("Payment failed. Popup was closed without a correct end message!", {'pace': 1});
                                                                instance.showFailureScreen("The payment process failed. Please close this popup and try again");
                                                            }

                                                            next_page = json.redirect_url;
                                                        })
                                                        .catch((error) => {
                                                            // the BE call failed
                                                            log_paysafe_error("Payment failed. Popup was closed without a correct end message!", {'pace': 2});
                                                            instance.showFailureScreen("The payment process failed. Please close this popup and try again");
                                                        });
                                                } else {
                                                    let errorMessage = '';
                                                    if (error) {
                                                        if (error.code) {
                                                            errorMessage += error.code + ' ';
                                                        }
                                                        if (error.message) {
                                                            errorMessage += error.message + ' ';
                                                        }
                                                        if (error.detailedMessage) {
                                                            errorMessage += error.detailedMessage;
                                                        }
                                                    }

                                                    // Tokenization failed and Payment Handled moved to failed Status
                                                    if (instance) {
                                                        log_paysafe_error("Payment failed. Popup was closed without a correct end message!" + ' ' + errorMessage, {'pace': 3});
                                                        instance.showFailureScreen("The payment was declined. Please, try again with the same or another payment method.");
                                                    } else {
                                                        log_paysafe_error("Payment failed. Popup was closed without a correct end message!" + ' ' + errorMessage, {'pace': 4});
                                                        wc_checkout_form.submit_error('<div class="woocommerce-error">' + wc_checkout_params.i18n_checkout_error + " " + (error && error.detailedMessage ? error.detailedMessage : '') + '</div>');
                                                    }
                                                }
                                            },

                                            // closeCallback
                                            function (stage, expired) {
                                                if (stage) {
                                                    // Depeding upon the stage take different actions
                                                    switch (stage) {
                                                        case "PAYMENT_HANDLE_NOT_CREATED" :
                                                            // don't show any errors,
                                                            // as the customer choose to close the popup window
                                                            log_paysafe_error("Payment failed. Popup was closed without a correct end message!", {'stage': stage, 'expired': expired});
                                                            window.location.reload();
                                                            break;

                                                        case "PAYMENT_HANDLE_CREATED" :
                                                        case "PAYMENT_HANDLE_REDIRECT" :
                                                        case "PAYMENT_HANDLE_PAYABLE" :
                                                            // in all these cases the user closed the popup after the payment
                                                            if (null !== next_page) {
                                                                window.location = next_page;
                                                                return;
                                                            }

                                                            // reload the cart page, something must have happened
                                                            window.location.reload();
                                                            break;

                                                        default:
                                                    }
                                                } else {
                                                    //Add action in case Checkout is expired
                                                    // the popup expired,
                                                    // lets reload the page so that the customer
                                                    // can try another payment option
                                                    log_paysafe_error("Payment failed. Popup was closed without a correct end message!", {'pace': 4});
                                                    window.location.reload();
                                                }
                                            },

                                            // riskCallback
                                            function (instance, amount, paymentMethod) {
                                                if (amount === response.order.amount) {
                                                    instance.accept();
                                                } else {
                                                    log_paysafe_error("Amount is not the value expected", []);
                                                    instance.decline("Amount is not the value expected");
                                                }
                                            }
                                        );
                                    }

                                    return false;
                                } else if (response.redirect !== '') {
                                    // in case of paying with a saved token, we have a redirect value and at this point we need to redirect
                                    window.location = response.redirect;
                                }
                            } else if ( 'failure' === response.result ) {
                                throw 'Result failure';
                            } else {
                                throw 'Invalid response';
                            }
                        } catch( err ) {
                            // Reload page
                            if ( true === response.reload ) {
                                window.location.reload();
                                return;
                            }

                            // Trigger update in case we need a fresh nonce
                            if ( true === response.refresh ) {
                                $( document.body ).trigger( 'update_checkout' );
                            }
                            // Add new errors

                            if ( response.messages ) {
                                log_paysafe_error(response.messages, []);
                                wc_checkout_form.submit_error( response.messages );
                            } else {
                                log_paysafe_error(wc_checkout_params.i18n_checkout_error, []);
                                wc_checkout_form.submit_error( '<div class="woocommerce-error">' + wc_checkout_params.i18n_checkout_error + '</div>' ); // eslint-disable-line max-len
                            }
                        }
                    },
                    error:	function( jqXHR, textStatus, errorThrown ) {
                        // Detach the unload handler that prevents a reload / redirect
                        wc_checkout_form.detachUnloadEventsOnSubmit();

                        // This is just a technical error fallback. i18_checkout_error is expected to be always defined and localized.
                        var errorMessage = errorThrown;

                        if (
                            typeof wc_checkout_params === 'object' &&
                            wc_checkout_params !== null &&
                            wc_checkout_params.hasOwnProperty( 'i18n_checkout_error' ) &&
                            typeof wc_checkout_params.i18n_checkout_error === 'string' &&
                            wc_checkout_params.i18n_checkout_error.trim() !== ''
                        ) {
                            errorMessage = wc_checkout_params.i18n_checkout_error;
                        }

                        log_paysafe_error(errorMessage, []);
                        wc_checkout_form.submit_error(
                            '<div class="woocommerce-error">' + errorMessage + '</div>'
                        );
                    }
                });

                return false;
            }
        );

        if (is_hosted_integration) {
            if (!jQuery('#paysafe-hosted-payment-form').length) {
                return;
            }

            if (jQuery('.wc-block-checkout__form').length) {
                return;
            }

            const paysafeLegacyOptions = {
                // You must provide currencyCode to the Paysafe JS SDK to enable the Payment API integration
                currencyCode: paysafe_settings.currency_code,

                // select the Paysafe test / sandbox environment
                environment: paysafe_settings.test_mode ? 'TEST' : 'LIVE',

                transactionSource: 'WooCommerceJs',

                // set the CSS selectors to identify the payment field divs above
                // set the placeholder text to display in these fields
                fields: {
                    cardNumber: {
                        selector: '#cardNumber',
                        placeholder: 'Card number',
                        separator: ' ',
                    },
                    expiryDate: {
                        selector: '#expiryDate',
                        placeholder: 'MM/YY',
                    },
                    cvv: {
                        selector: '#cvv',
                        placeholder: 'CVV',
                        optional: false,
                    },
                },
            };

            setTimeout( () => {
                // initialize the hosted iframes using the SDK setup function
                paysafe.fields
                    .setup(
                        paysafe_settings.authorization,
                        paysafeLegacyOptions
                    )
                    .then(instance => {
                        hostedLegacyPaymentInstance = instance;
                        return instance.show();
                    })
                    .then(paymentMethods => {
                        if (paymentMethods.card && !paymentMethods.card.error) {
                            // When the customer clicks Pay Now,
                            // call the SDK tokenize function to create
                            // a single-use payment token corresponding to the card details entered
                        }
                    })
                    .catch(error => {
                        // this means that the initialization of the form failed,
                        // disable this payment method
                        log_paysafe_error('ERROR ' + error.code + ': ' + error.detailedMessage, []);
                        const error_message = 'ERROR ' + error.code + ': ' + error.detailedMessage;
                    });
            }, 1000);
        }
    });
}) ( jQuery );
