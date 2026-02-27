(function ( $ ) {
    $ ( document ).ready(function() {
        // add payment method with paysafe
        const { __ } = wp.i18n;

        // if the page doesn't have the
        if (!$('#add-paysafe-payment-method-form').length) {
            return;
        }

        const log_paysafe_error = function(message, context) {
            if (!!paysafe_apm_settings.log_errors === true && paysafe_apm_settings.log_error_endpoint) {
                fetch(
                    paysafe_apm_settings.log_error_endpoint,
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

        const paysafe_form = $('form#add_payment_method');
        if (!paysafe_form) {
            return;
        }

        const paysafeOptions = {
            // You must provide currencyCode to the Paysafe JS SDK to enable the Payment API integration
            currencyCode: paysafe_apm_settings.currency_code,

            // select the Paysafe test / sandbox environment
            environment: paysafe_apm_settings.test_mode ? 'TEST' : 'LIVE',

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
            style: {
                input: {
                    "font-family": "sans-serif",
                    "font-weight": "normal",
                    "font-size": "16px",
                }
            },
            accounts: {
                default: parseInt(paysafe_apm_settings.card_account_id),
            },
        };

        // initialize the hosted iframes using the SDK setup function
        let paymentInstance;
        paysafe.fields
            .setup(
                paysafe_apm_settings.authorization,
                paysafeOptions
            )
            .then(instance => {
                paymentInstance = instance;
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
                log_paysafe_error(error_message, []);
                $('.woocommerce-PaymentBox--paysafe').html(error_message);
            });

        let paysafe_form_submitted = false;
        paysafe_form.on(
            'submit',
            function (e) {
                // check if paysafe is selected
                if (!$('#payment_method_paysafe').is(':checked')) {
                    return true;
                }

                if (paysafe_form_submitted) {
                    return true;
                }

                if (!paymentInstance || !paymentInstance.areAllFieldsValid()) {
                    const error_message = __('Add payment method failed. Please enter your card details and try again', 'paysafe-checkout');
                    log_paysafe_error(error_message, []);
                    $('.woocommerce-PaymentBox--paysafe').html(error_message);

                    return;
                }

                let holderNameInput = document.getElementById('holderNameInput');
                let isHolderNameValid = holderNameInput && holderNameInput.value && holderNameInput.value.length && holderNameInput.value.length >= 2 && holderNameInput.value.length <= 160;;
                if (!isHolderNameValid) {
                    const error_message = __('Add payment method failed. Card holder name must have between 2 and 160 characters!', 'paysafe-checkout');
                    log_paysafe_error(error_message, []);
                    $('.woocommerce-PaymentBox--paysafe').html(error_message);

                    return;
                }

                paysafe_form_submitted = true;

                let tokenizationOptions = {
                    transactionSource: 'WooCommerceJs',
                    amount: 0,
                    threeDs: {
                        merchantUrl: paysafe_apm_settings.apm_url,
                        deviceChannel: "BROWSER",
                        messageCategory: "NON_PAYMENT",
                        authenticationPurpose: "PAYMENT_TRANSACTION",
                        transactionIntent: "GOODS_OR_SERVICE_PURCHASE",
                        profile: {
                            email: paysafe_apm_settings.user_email,
                        },
                    },
                    transactionType: 'VERIFICATION',
                    paymentType: 'CARD',
                    merchantRefNum: paysafe_apm_settings.merchant_ref_num,
                    customerDetails: {
                        holderName: paysafe_apm_settings.billing_details.name,
                        billingDetails: {
                            country: paysafe_apm_settings.billing_details.country,
                            zip: paysafe_apm_settings.billing_details.zip,
                        },
                        profile: {
                            firstName: paysafe_apm_settings.billing_details.first_name,
                            lastName: paysafe_apm_settings.billing_details.last_name,
                            email: paysafe_apm_settings.billing_details.email
                        },
                    },
                };

                let holderName = holderNameInput && holderNameInput.value &&
                holderNameInput.value.length && holderNameInput.value.length >= 2 &&
                holderNameInput.value.length <= 160 ? holderNameInput.value : null;
                if (holderName) {
                    tokenizationOptions.customerDetails.holderName = holderName;
                }

                if (paysafe_apm_settings.billing_details.city) {
                    tokenizationOptions.customerDetails.billingDetails.city = paysafe_apm_settings.billing_details.city;
                }
                if (paysafe_apm_settings.billing_details.state) {
                    tokenizationOptions.customerDetails.billingDetails.state = paysafe_apm_settings.billing_details.state;
                }

                if (paysafe_apm_settings.merchant_descriptor && paysafe_apm_settings.merchant_phone) {
                    tokenizationOptions.merchantDescriptor = {
                        dynamicDescriptor: paysafe_apm_settings.merchant_descriptor,
                        phone: paysafe_apm_settings.merchant_phone,
                    }
                }

                paymentInstance
                    .tokenize(tokenizationOptions)
                    .then(result => {
                        if (!result || !result.token) {
                            throw 'Result failure';
                        }

                        paysafe_form.append(
                            $(document.createElement('input'))
                                .attr("type", 'hidden')
                                .attr("name", 'payment_method_paysafe_token')
                                .attr("value", result.token)
                        );
                        paysafe_form.append(
                            $(document.createElement('input'))
                                .attr("type", 'hidden')
                                .attr("name", 'payment_method_merchant_reference')
                                .attr("value", paysafe_apm_settings.merchant_ref_num)
                        );

                        paysafe_form.submit();
                    })
                    .catch(error => {
                        // this means that the tokenization of the card form failed,
                        // disable this payment method, show the error and refresh the page in 5 seconds
                        const error_message = 'ERROR ' + error.code + ': ' + error.detailedMessage;
                        log_paysafe_error(error_message, []);
                        $('.woocommerce-PaymentBox--paysafe').html(error_message);
                    });

                return false;
            }
        );
    });
}) ( jQuery );
