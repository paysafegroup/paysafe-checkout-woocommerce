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
        const is_cvv_verification = !!paysafe_settings.cvv_verification;
        let hostedLegacyPaymentInstance = null;
        let hostedLegacyPaymentInitialized = false;
        let hostedLegacyPaymentType = '';

        $form.on(
            'checkout_place_order_paysafe checkout_place_order_apple_pay checkout_place_order_google_pay' +
            ' checkout_place_order_skrill checkout_place_order_neteller checkout_place_order_paysafecash checkout_place_order_paysafecard' +
            ' checkout_place_order_eft checkout_place_order_ach checkout_place_order_paypal checkout_place_order_sightline' +
            ' checkout_place_order_vippreferred checkout_place_order_paybybank checkout_place_order_venmo',
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
                const is_google_pay = $('#payment_method_google_pay').prop('checked');
                const is_skrill = $('#payment_method_skrill').prop('checked');
                const is_neteller = $('#payment_method_neteller').prop('checked');
                const is_paysafecash = $('#payment_method_paysafecash').prop('checked');
                const is_paysafecard = $('#payment_method_paysafecard').prop('checked');
                const is_eft = $('#payment_method_eft').prop('checked');
                const is_ach = $('#payment_method_ach').prop('checked');
                const is_paypal = $('#payment_method_paypal').prop('checked');
                const is_sightline = $('#payment_method_sightline').prop('checked');
                const is_vippreferred = $('#payment_method_vippreferred').prop('checked');
                const is_paybybank = $('#payment_method_paybybank').prop('checked');
                const is_venmo = $('#payment_method_venmo').prop('checked');

                const token_id = $('input[name=wc-paysafe-payment-token]:checked').val();
                const is_token_pay = is_paysafe && undefined !== token_id && 'new' !== token_id;

                const use_hosted_integration = is_hosted_integration && is_paysafe;

                let are_fields_valid = true;
                if (use_hosted_integration || is_token_pay) {
                    are_fields_valid = false;
                    if (hostedLegacyPaymentInstance) {
                        // check if the hosted integration is filled in and the data is valid
                        are_fields_valid = hostedLegacyPaymentInstance.areAllFieldsValid();
                        if (!are_fields_valid) {
                            wc_checkout_form.submit_error( '<div class="woocommerce-error">' + "The payment process failed. Please enter your card details and try again" + "</div>");
                        }

                        if (!is_token_pay) {
                            let holderNameInput = document.getElementById('holderNameInput');
                            let isHolderNameValid = holderNameInput && holderNameInput.value && holderNameInput.value.length && holderNameInput.value.length >= 2 && holderNameInput.value.length <= 160;

                            if (!isHolderNameValid) {
                                wc_checkout_form.submit_error('<div class="woocommerce-error">' + "The payment process failed. Card holder name must have between 2 and 160 characters!" + "</div>");
                                are_fields_valid = false;
                            }
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

                                    const orderId = parseInt(response.order.order_id);

                                    const customerSingleUseToken = response && response.single_use_token ? response.single_use_token : '';
                                    const paysafeToken = response && response.paysafe_token ? response.paysafe_token : '';

                                    if (use_hosted_integration || is_token_pay) {
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
                                            customerDetails: {
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
                                            }
                                        };

                                        let holderNameInput = document.getElementById('holderNameInput');
                                        let holderName = holderNameInput && holderNameInput.value &&
                                        holderNameInput.value.length && holderNameInput.value.length >= 2 &&
                                        holderNameInput.value.length <= 160 ? holderNameInput.value : null;
                                        if (holderName) {
                                            tokenizationOptions.customerDetails.holderName = holderName;
                                        }

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

                                        if (paysafe_settings.merchant_descriptor && paysafe_settings.merchant_phone) {
                                            tokenizationOptions.merchantDescriptor = {
                                                dynamicDescriptor: paysafe_settings.merchant_descriptor,
                                                phone: paysafe_settings.merchant_phone,
                                            }
                                        }

                                        if (is_token_pay) {
                                            tokenizationOptions.singleUseCustomerToken = customerSingleUseToken;
                                            tokenizationOptions.paymentTokenFrom = paysafeToken;
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

                                                let save_card_checkbox_checked = false;
                                                if (use_hosted_integration) {
                                                    let save_card_checkbox = document.getElementById('paysafe_hosted_save_card');
                                                    if (save_card_checkbox && save_card_checkbox.checked) {
                                                        save_card_checkbox_checked = true;
                                                    }
                                                }

                                                if (save_card_checkbox_checked) {
                                                    paymentData.save_card = true;
                                                }

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
                                                    .then(response_data => {
                                                        if (response_data.status === 'success') {
                                                            window.location = response_data.redirect_url;
                                                        } else {
                                                            if (response_data.error_message) {
                                                                log_paysafe_error(response_data.error_message, {'pace': 10});
                                                                wc_checkout_form.submit_error(response_data.error_message);
                                                            } else {
                                                                log_paysafe_error("The payment process failed. Please try again", []);
                                                                wc_checkout_form.submit_error("The payment process failed. Please try again");
                                                            }
                                                        }
                                                    })
                                                    .catch((error) => {
                                                        // the BE call failed
                                                        log_paysafe_error("The payment process failed. Please try again", []);
                                                        wc_checkout_form.submit_error("The payment process failed. Please try again");
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

                                        if (is_google_pay) {
                                            displayPaymentMetods = [
                                                'googlePay'
                                            ];
                                            paymentMethodDetails = {
                                                googlePay: {
                                                    accountId: paysafe_settings.google_pay_account_id,
                                                    label: 'google_pay',
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

                                        if (is_eft) {
                                            displayPaymentMetods = [
                                                'eft'
                                            ];
                                            paymentMethodDetails = {
                                                eft: {
                                                    accountId: paysafe_settings.eft_account_id,
                                                }
                                            };
                                        }

                                        if (is_ach) {
                                            displayPaymentMetods = [
                                                'ach'
                                            ];
                                            paymentMethodDetails = {
                                                ach: {
                                                    accountId: paysafe_settings.ach_account_id,
                                                }
                                            };
                                        }

                                        if (is_paypal) {
                                            displayPaymentMetods = [
                                                'paypal'
                                            ];
                                            paymentMethodDetails = {
                                                paypal: {
                                                    accountId: paysafe_settings.paypal_account_id,
                                                    consumerId: paysafe_settings.consumer_id,
                                                }
                                            };
                                        }

                                        if (is_sightline) {
                                            displayPaymentMetods = [
                                                'sightline'
                                            ];
                                            paymentMethodDetails = {
                                                sightline: {
                                                    accountId: paysafe_settings.sightline_account_id,
                                                }
                                            };
                                        }

                                        if (is_vippreferred) {
                                            displayPaymentMetods = [
                                                'vippreferred'
                                            ];
                                            paymentMethodDetails = {
                                                vippreferred: {
                                                    accountId: paysafe_settings.vippreferred_account_id,
                                                }
                                            };
                                        }

                                        if (is_paybybank) {
                                            displayPaymentMetods = [
                                                'paybybank'
                                            ];
                                            paymentMethodDetails = {
                                                paybybank: {
                                                    accountId: paysafe_settings.paybybank_account_id,
                                                }
                                            };
                                        }

                                        if (is_venmo) {
                                            displayPaymentMetods = [
                                                'venmo'
                                            ];
                                            paymentMethodDetails = {
                                                venmo: {
                                                    accountId: paysafe_settings.venmo_account_id,
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

        $( document.body ).on(
            'click',
            'li.woocommerce-SavedPaymentMethods-token, li.woocommerce-SavedPaymentMethods-new, li.wc_payment_method',
            function(e) {
                setTimeout(handle_paysafe_js_initialization, 500);
            });

        const handle_paysafe_js_initialization = function() {
            const payment_method = $('input[name=payment_method]:checked').val();
            if (payment_method !== 'paysafe') {
                return true;
            }

            const paysafe_token = $('input[name=wc-paysafe-payment-token]:checked').val();
            if (undefined !== paysafe_token && 'new' !== paysafe_token) {
                // a token was clicked
                if (hostedLegacyPaymentType !== 'token') {
                    hostedLegacyPaymentInstance = null;
                    hostedLegacyPaymentInitialized = false;
                    init_paysafe_js(true);
                }
            } else {
                // new card is selected
                if (hostedLegacyPaymentType !== 'card') {
                    hostedLegacyPaymentInstance = null;
                    hostedLegacyPaymentInitialized = false;
                    init_paysafe_js(false);
                }
            }
        };

        const init_paysafe_js = function(is_token) {
            if (!hostedLegacyPaymentInitialized) {
                if (!$('#paysafe-hosted-payment-form').length) {
                    return;
                }

                hostedLegacyPaymentInitialized = true;
                hostedLegacyPaymentType = is_token ? 'token' : 'card';

                let holderNameObject = $('#holderName');
                let cardNumberObject = $('#cardNumber');
                let expiryDateObject = $('#expiryDate');
                let cvvObject = $('#cvv');

                cardNumberObject.html('');
                expiryDateObject.html('');
                cvvObject.html('');
                if (is_token) {
                    if (is_cvv_verification) {
                        $('#paysafe-hosted-payment-form').show();

                        holderNameObject.hide();
                        $('#holderName_label').hide();
                        $('#holderName_spacer').hide();
                        cardNumberObject.addClass('optional-field');
                        $('#cardNumber_label').hide();
                        $('#cardNumber_spacer').hide();
                        expiryDateObject.addClass('optional-field');
                        $('#expiryDate_label').hide();
                        $('#expiryDate_spacer').hide();
                        $('.paysafe-cc-form-exp-cvv-row .paysafe-cc-form-exp-cvv-box1').hide();
                    } else {
                        $('#paysafe-hosted-payment-form').hide();
                    }
                } else {
                    if (is_hosted_integration) {
                        $('#paysafe-hosted-payment-form').show();
                        if (is_cvv_verification) {
                            holderNameObject.show();
                            $('#holderName_label').show();
                            $('#holderName_spacer').show();
                            cardNumberObject.removeClass('optional-field');
                            $('#cardNumber_label').show();
                            $('#cardNumber_spacer').show();
                            expiryDateObject.removeClass('optional-field');
                            $('#expiryDate_label').show();
                            $('#expiryDate_spacer').show();
                            $('.paysafe-cc-form-exp-cvv-row .paysafe-cc-form-exp-cvv-box1').show();
                        }
                    } else {
                        $('#paysafe-hosted-payment-form').hide();
                    }
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
                            optional: is_token,
                        },
                        expiryDate: {
                            selector: '#expiryDate',
                            placeholder: 'MM/YY',
                            optional: is_token,
                        },
                        cvv: {
                            selector: '#cvv',
                            placeholder: 'CVV',
                            optional: is_token && !is_cvv_verification,
                        },
                    },
                    style: {
                        input: {
                            "font-family": "sans-serif",
                            "font-weight": "normal",
                            "font-size": "16px"
                        }
                    },
                    accounts: {
                        default: parseInt(paysafe_settings.card_account_id),
                    },
                };

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
                        const error_message = 'ERROR ' + error.code + ': ' + error.detailedMessage;
                    });
            }
        }

        setTimeout(handle_paysafe_js_initialization, 1000);
    });
}) ( jQuery );
