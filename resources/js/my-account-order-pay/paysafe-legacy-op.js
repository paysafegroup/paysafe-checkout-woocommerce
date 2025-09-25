(function ( $ ) {
    $ ( document ).ready(function() {
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


        let $form = $( 'form#order_review' );

        let is_hosted_integration = paysafe_settings.integration_type === 'paysafe_js';
        const is_cvv_verification = !!paysafe_settings.cvv_verification;

        let hostedOpPaymentInstance = null;
        let hostedOpPaymentInitialized = false;
        let hostedOpPaymentType = '';

        $( document.body ).on(
            'click',
            'form#order_review #place_order',
            function(e) {

                $form.addClass( 'processing' );

                const payment_method = $('input[name=payment_method]:checked').val();
                if (payment_method !== 'paysafe'
                    && payment_method !== 'apple_pay'
                    && payment_method !== 'google_pay'
                    && payment_method !== 'skrill'
                    && payment_method !== 'neteller'
                    && payment_method !== 'paysafecash'
                    && payment_method !== 'paysafecard') {
                    return true;
                }

                const is_paysafe = payment_method === 'paysafe';
                const is_apple_pay = payment_method === 'apple_pay';
                const is_google_pay = payment_method === 'google_pay';
                const is_skrill = payment_method === 'skrill';
                const is_neteller = payment_method === 'neteller';
                const is_paysafecash = payment_method === 'paysafecash';
                const is_paysafecard = payment_method === 'paysafecard';

                const token_id = $('input[name=wc-paysafe-payment-token]:checked').val();
                const is_token_pay = is_paysafe && undefined !== token_id && 'new' !== token_id;

                const use_hosted_integration = is_hosted_integration && is_paysafe;

                const change_subs_payment_method_order_id = $('input[name=woocommerce_change_payment]').val();
                const is_change_subs_payment_method = change_subs_payment_method_order_id !== undefined;
                const update_all_subs_payment_method = $('input[name=update_all_subscriptions_payment_method]:checked').val() !== undefined;

                // get options info and initiate the paysafe checkout popup
                let order_id = is_change_subs_payment_method ? change_subs_payment_method_order_id : null;
                const url_parts = window.location.href.split('&');
                if (!order_id && url_parts && url_parts.length) {
                    const order_pay_parameter = url_parts.filter( x => x.indexOf('order-pay') > -1);
                    if (order_pay_parameter && order_pay_parameter.length) {
                        if (order_pay_parameter[0] && order_pay_parameter[0].indexOf('http') > -1) {
                            // url rewrite case
                            order_id = parseInt(order_pay_parameter[0].substring(order_pay_parameter[0].indexOf('order-pay') + 10));
                        } else {
                            // normal url case
                            const order_pay_array = order_pay_parameter[0].split('=');
                            if (order_pay_array.length >= 2) {
                                order_id = order_pay_array[1];
                            }
                        }
                    }
                }

                let are_fields_valid = true;
                if (use_hosted_integration || is_token_pay) {
                    are_fields_valid = false;
                    if (hostedOpPaymentInstance) {
                        // check if the hosted integration is filled in and the data is valid
                        are_fields_valid = hostedOpPaymentInstance.areAllFieldsValid();
                        if (!are_fields_valid) {
                            show_op_error_message('The payment process failed. Please enter your card details and try again', true);
                        }

                        if (!is_token_pay) {
                            let holderNameInput = document.getElementById('holderNameInput');
                            let isHolderNameValid = holderNameInput && holderNameInput.value && holderNameInput.value.length && holderNameInput.value.length >= 2 && holderNameInput.value.length <= 160;

                            if (!isHolderNameValid) {
                                show_op_error_message('The payment process failed. Card holder name must have between 2 and 160 characters!', true);
                                are_fields_valid = false;
                            }
                        }
                    } else {
                        show_op_error_message('The payment process failed. Please reload the page and try again', true);
                    }
                }

                if (!are_fields_valid) {
                    return false;
                }

                fetch(
                    paysafe_settings.get_order_pay_data_url,
                    {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: new Headers({
                            // 'Content-Type': 'application/x-www-form-urlencoded',
                            'Content-Type': 'application/json',
                        }),
                        body: JSON.stringify({
                            order_id: order_id,
                            token_id: token_id
                        }),
                    }
                )
                .then(get_order_data_response => {
                    return get_order_data_response.json();
                })
                .then(response => {
                    const customerSingleUseToken = response && response.single_use_token ? response.single_use_token : '';
                    const paysafeToken = response && response.paysafe_token ? response.paysafe_token : '';

                    if (use_hosted_integration || is_token_pay) {
                        let tokenizationOpOptions = {
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
                            tokenizationOpOptions.customerDetails.holderName = holderName;
                        }

                        if (response.billing.street) {
                            tokenizationOpOptions.customerDetails.billingDetails.street = response.billing.street;
                        }
                        if (response.billing.street2) {
                            tokenizationOpOptions.customerDetails.billingDetails.street2 = response.billing.street2;
                        }
                        if (response.billing.city) {
                            tokenizationOpOptions.customerDetails.billingDetails.city = response.billing.city;
                        }
                        if (response.billing.state) {
                            tokenizationOpOptions.customerDetails.billingDetails.state = response.billing.state;
                        }
                        if (response.billing.phone) {
                            tokenizationOpOptions.customerDetails.billingDetails.phone = response.billing.phone;
                        }

                        if (paysafe_settings.merchant_descriptor && paysafe_settings.merchant_phone) {
                            tokenizationOpOptions.merchantDescriptor = {
                                dynamicDescriptor: paysafe_settings.merchant_descriptor,
                                phone: paysafe_settings.merchant_phone,
                            }
                        }

                        if (is_token_pay) {
                            tokenizationOpOptions.singleUseCustomerToken = customerSingleUseToken;
                            tokenizationOpOptions.paymentTokenFrom = paysafeToken;
                        }

                        if (is_change_subs_payment_method) {
                            tokenizationOpOptions.transactionType = 'VERIFICATION';
                            tokenizationOpOptions.amount = 0;
                            tokenizationOpOptions.threeDs.authenticationPurpose = 'RECURRING_TRANSACTION';
                        }

                        hostedOpPaymentInstance
                            .tokenize(tokenizationOpOptions)
                            .then(result => {
                                if (!result || !result.token) {
                                    throw 'Result failure';
                                }

                                let paymentData = {
                                    orderId: order_id,
                                    paymentMethod: 'CARD',
                                    transactionType: 'PAYMENT',
                                    paymentHandleToken: result.token,
                                    amount: parseInt(response.order.amount),
                                    customerOperation: '',
                                    merchantRefNum: response.merch_ref_num,
                                    order_pay_page: true,
                                };

                                if (is_change_subs_payment_method) {
                                    paymentData.change_subs_payment_method = is_change_subs_payment_method;
                                    paymentData.update_all_subs = update_all_subs_payment_method;
                                    paymentData.transactionType = 'VERIFICATION';
                                    paymentData.amount = 0;
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
                                    .then(register_response => {
                                        return register_response.json();
                                    })
                                    .then(response_data => {
                                        if (response_data.status === 'success') {
                                            handle_payment_success();
                                            redirect_to_success_page(response.success_url);
                                        } else {
                                            if (response_data.error_message) {
                                                log_paysafe_error(response_data.error_message, {'pace': 10});
                                                show_op_error_message(response_data.error_message, true);
                                            }
                                            handle_payment_failure();
                                            $form.submit();
                                        }
                                    })
                                    .catch((error) => {
                                        // the BE call failed
                                        show_op_error_message('The payment process failed. Please reload the page and try again', true);
                                        handle_payment_failure();
                                        $form.submit();
                                    });
                            })
                            .catch(error => {
                                // this means that the tokenization of the card form failed,
                                // disable this payment method, show the error and refresh the page in 5 seconds
                                let errorMessage = 'ERROR ';
                                if (error) {
                                    if (error.code) {
                                        errorMessage += error.code + ': ';
                                    }
                                    if (error.message) {
                                        errorMessage += error.message + ' ';
                                    }
                                    if (error.detailedMessage) {
                                        errorMessage += error.detailedMessage;
                                    }
                                }

                                show_op_error_message(errorMessage, true);

                                handle_payment_failure();
                                $form.submit();
                            });
                    } else {
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
                                    let payment_data = {
                                        orderId: order_id,
                                        paymentMethod: result.paymentMethod,
                                        transactionType: result.transactionType,
                                        paymentHandleToken: result.paymentHandleToken,
                                        amount: result.amount,
                                        customerOperation: result.customerOperation,
                                        merchantRefNum: response.merch_ref_num,
                                        order_pay_page: true,
                                    };

                                    fetch(
                                        paysafe_settings.register_url,
                                        {
                                            method: 'POST',
                                            credentials: 'same-origin',
                                            headers: new Headers({
                                                'Content-Type': 'application/json',
                                            }),
                                            body: JSON.stringify(payment_data),
                                        })
                                        .then(register_response => {
                                            return register_response.json();
                                        })
                                        .then(response_data => {
                                            if (response_data.status === 'success') {
                                                instance.showSuccessScreen("Your goods are now purchased. Expect them to be delivered in next 5 business days.");

                                                handle_payment_success();
                                            } else {
                                                instance.showFailureScreen("The payment process failed. Please close this popup and try again");

                                                handle_payment_failure();
                                            }
                                        })
                                        .catch((error) => {
                                            // the BE call failed
                                            instance.showFailureScreen("The payment process failed. Please close this popup and try again");

                                            handle_payment_failure();
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
                                        instance.showFailureScreen("The payment was declined. Please, try again with the same or another payment method. " + errorMessage);
                                    } else {
                                        log_paysafe_error("Payment failed. Popup was closed without a correct end message!" + ' ' + errorMessage, {'pace': 3});
                                        show_op_error_message('The payment process failed. Please reload the page and try again. ' + errorMessage, true);

                                        handle_payment_failure();
                                        $form.submit();

                                        return;
                                    }

                                    handle_payment_failure();
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
                                            handle_payment_failure();
                                            $form.submit();
                                            break;

                                        case "PAYMENT_HANDLE_CREATED" :
                                        case "PAYMENT_HANDLE_REDIRECT" :
                                        case "PAYMENT_HANDLE_PAYABLE" :
                                            // in all these cases the user closed the popup after the payment

                                            // reload the cart page, something must have happened
                                            redirect_to_success_page(response.success_url);
                                            break;

                                        default:
                                    }
                                } else {
                                    //Add action in case Checkout is expired
                                    // the popup expired,
                                    // lets reload the page so that the customer
                                    // can try another payment option
                                    handle_payment_failure();
                                    $form.submit();
                                }
                            },

                            // riskCallback
                            function (instance, amount, paymentMethod) {
                                if (amount === response.order.amount) {
                                    instance.accept();
                                } else {
                                    instance.decline("Amount is not the value expected");
                                }
                            }
                        );
                    }

                    return false;
                })
                .catch((error) => {
                    // the BE call failed
                    handle_payment_failure();
                    $form.submit();

                    return false;
                });

                return false;
            }
        );

        const get_result_variable_name = function() {
            let payment_type = $('input[name=payment_method]:checked').val();

            let variable_name = 'wc-paysafe-order-pay-result';
            if (payment_type === 'apple_pay') {
                variable_name = 'wc-apple_pay-order-pay-result';
            }
            if (payment_type === 'google_pay') {
                variable_name = 'wc-google_pay-order-pay-result';
            }
            if (payment_type === 'skrill') {
                variable_name = 'wc-skrill-order-pay-result';
            }
            if (payment_type === 'neteller') {
                variable_name = 'wc-neteller-order-pay-result';
            }
            if (payment_type === 'paysafecash') {
                variable_name = 'wc-paysafecash-order-pay-result';
            }
            if (payment_type === 'paysafecard') {
                variable_name = 'wc-paysafecard-order-pay-result';
            }

            return variable_name;
        }

        const handle_payment_failure = function() {

            let pay_result = $('#' + get_result_variable_name());
            if (pay_result.val()) {
                pay_result.val('failure');
                return;
            }

            $form.append(
                $(document.createElement('input'))
                    .attr("type", 'hidden')
                    .attr("id", get_result_variable_name())
                    .attr("name", get_result_variable_name())
                    .attr("value", 'failure')
            );
        };
        const handle_payment_success = function() {
            let pay_result = $('#' + get_result_variable_name());
            if (pay_result.val()) {
                pay_result.val('success');
                return;
            }

            $form.append(
                $(document.createElement('input'))
                    .attr("type", 'hidden')
                    .attr("id", get_result_variable_name())
                    .attr("name", get_result_variable_name())
                    .attr("value", 'success')
            );
        }

        const redirect_to_success_page = function(success_url) {
            let pay_result = $('#' + get_result_variable_name());
            if (pay_result.val() === 'success') {
                window.location = success_url;
            } else {
                $form.submit();
            }
        }

        const show_op_error_message = function(message, clear) {
            log_paysafe_error(message, []);

            let error_container = document.getElementById('paysafe-error-message-container');
            if (!error_container) {
                error_container = document.createElement('div');
                error_container.setAttribute('id', 'paysafe-error-message-container');

                let payment_container = document.getElementById('payment');
                payment_container.prepend(error_container);
            } else {
                if (clear === true) {
                    error_container.innerHTML = '';
                }
            }

            let new_error_message = document.createElement('div');
            new_error_message.className = 'woocommerce-error';
            new_error_message.innerHTML = message;
            error_container.appendChild(new_error_message);

            error_container.focus();
        }

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

            const change_subs_payment_method_order_id = $('input[name=woocommerce_change_payment]').val();
            if (change_subs_payment_method_order_id !== undefined) {
                // force hosted integration for change payment method for subscription
                is_hosted_integration = true;
            }

            const paysafe_token = $('input[name=wc-paysafe-payment-token]:checked').val();
            if (undefined !== paysafe_token && 'new' !== paysafe_token) {
                // a token was clicked
                if (hostedOpPaymentType !== 'token') {
                    hostedOpPaymentInstance = null;
                    hostedOpPaymentInitialized = false;
                    init_paysafe_js(true);
                }
            } else {
                // new card is selected
                if (hostedOpPaymentType !== 'card') {
                    hostedOpPaymentInstance = null;
                    hostedOpPaymentInitialized = false;
                    init_paysafe_js(false);
                }
            }
        };

        const init_paysafe_js = function(is_token) {
            if (!hostedOpPaymentInitialized) {
                if (!jQuery('#paysafe-hosted-payment-form').length) {
                    return;
                }

                hostedOpPaymentInitialized = true;
                hostedOpPaymentType = is_token ? 'token' : 'card';

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

                const paysafeOpOptions = {
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
                        paysafeOpOptions
                    )
                    .then(instance => {
                        hostedOpPaymentInstance = instance;
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
                        show_op_error_message(error_message);
                    });
            }
        }

        setTimeout(handle_paysafe_js_initialization, 1000);
    });
}) ( jQuery );
