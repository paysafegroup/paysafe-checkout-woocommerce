(function ( $ ) {
    $ ( document ).ready(function() {
        const { __ } = wp.i18n;

        const log_paysafe_error = function(message, context) {
            if (!!paysafe_op_settings.log_errors === true && paysafe_op_settings.log_error_endpoint) {
                fetch(
                    paysafe_op_settings.log_error_endpoint,
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

        let is_hosted_integration = paysafe_op_settings.integration_type === 'paysafe_js';
        const is_cvv_verification = !!paysafe_op_settings.cvv_verification;

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
                    && payment_method !== 'paysafecard'
                    && payment_method !== 'eft'
                    && payment_method !== 'ach'
                    && payment_method !== 'paysafepaypal'
                    && payment_method !== 'sightline'
                    && payment_method !== 'vippreferred'
                    && payment_method !== 'pay_by_bank'
                    && payment_method !== 'venmo'
                ) {
                    return true;
                }

                const is_paysafe = payment_method === 'paysafe';
                const is_apple_pay = payment_method === 'apple_pay';
                const is_google_pay = payment_method === 'google_pay';
                const is_skrill = payment_method === 'skrill';
                const is_neteller = payment_method === 'neteller';
                const is_paysafecash = payment_method === 'paysafecash';
                const is_paysafecard = payment_method === 'paysafecard';
                const is_eft = payment_method === 'eft';
                const is_ach = payment_method === 'ach';
                const is_paypal = payment_method === 'paysafepaypal';
                const is_sightline = payment_method === 'sightline';
                const is_vippreferred = payment_method === 'vippreferred';
                const is_paybybank = payment_method === 'pay_by_bank';
                const is_venmo = payment_method === 'venmo';

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
                            show_op_error_message(__('The payment process failed. Please enter your card details and try again', 'paysafe-checkout'), true);
                        }

                        if (!is_token_pay) {
                            let holderNameInput = document.getElementById('holderNameInput');
                            let isHolderNameValid = holderNameInput && holderNameInput.value && holderNameInput.value.length && holderNameInput.value.length >= 2 && holderNameInput.value.length <= 160;

                            if (!isHolderNameValid) {
                                show_op_error_message(__('The payment process failed. Card holder name must have between 2 and 160 characters!', 'paysafe-checkout'), true);
                                are_fields_valid = false;
                            }
                        }
                    } else {
                        show_op_error_message(__('The payment process failed. Please reload the page and try again', 'paysafe-checkout'), true);
                    }
                }

                if (is_paypal) {
                    let paysafepaypalConsumerIdInput = document.getElementById('paysafepaypal_consumeridInput');
                    let isPaysafePaypalConsumerIdValid = paysafepaypalConsumerIdInput && paysafepaypalConsumerIdInput.value
                        && paysafepaypalConsumerIdInput.value.length;

                    if (!isPaysafePaypalConsumerIdValid) {
                        show_op_error_message(__("The payment process failed. PayPal email address must be provided!", 'paysafe-checkout'), true);
                        are_fields_valid = false;
                    }
                }
                if (is_sightline) {
                    let sightlineConsumerIdInput = document.getElementById('sightline_consumeridInput');
                    let isSightlineConsumerIdValid = sightlineConsumerIdInput && sightlineConsumerIdInput.value
                        && sightlineConsumerIdInput.value.length && sightlineConsumerIdInput.value.length >= 2
                        && sightlineConsumerIdInput.value.length <= 50;

                    if (!isSightlineConsumerIdValid) {
                        show_op_error_message(__("The payment process failed. Play+ (Sightline) consumer id must have between 2 and 50 characters!", 'paysafe-checkout'), true);
                        are_fields_valid = false;
                    }
                }
                if (is_venmo) {
                    let paysafevenmoConsumerIdInput = document.getElementById('paysafevenmo_consumeridInput');
                    let isPaysafeVenmoConsumerIdValid = paysafevenmoConsumerIdInput && paysafevenmoConsumerIdInput.value
                        && paysafevenmoConsumerIdInput.value.length;

                    if (!isPaysafeVenmoConsumerIdValid) {
                        show_op_error_message(__("The payment process failed. You have to specify your Venmo email address!", 'paysafe-checkout'), true);
                        are_fields_valid = false;
                    }
                }
                if (is_vippreferred) {
                    let vippreferredConsumerIdInput = document.getElementById('vippreferred_consumeridInput');
                    let isVipPreferredConsumerIdValid = vippreferredConsumerIdInput && vippreferredConsumerIdInput.value
                        && vippreferredConsumerIdInput.value.length && vippreferredConsumerIdInput.value.length === 9;

                    let vippreferredPhonenumberInput = document.getElementById('vippreferred_phonenumberInput');
                    let isVipPreferredPhonenumberValid = vippreferredPhonenumberInput && vippreferredPhonenumberInput.value
                        && vippreferredPhonenumberInput.value.length && vippreferredPhonenumberInput.value.length >= 9;

                    if (!isVipPreferredConsumerIdValid || !isVipPreferredPhonenumberValid) {
                        show_op_error_message(__("The payment process validation failed. Customer`s SSN and phone number must be provided!", 'paysafe-checkout'), true);
                        are_fields_valid = false;
                    }
                }
                if (is_paybybank) {
                    let paybybankConsumerIdInput = document.getElementById('paybybank_consumeridInput');
                    let isPayByBankConsumerIdValid = paybybankConsumerIdInput && paybybankConsumerIdInput.value
                        && paybybankConsumerIdInput.value.length;

                    let paybybankDOBInput = document.getElementById('paybybank_dobInput');
                    let isPayByBankDOBValid = paybybankDOBInput && paybybankDOBInput.value
                        && paybybankDOBInput.value.length;

                    if (!isPayByBankConsumerIdValid || !isPayByBankDOBValid) {
                        show_op_error_message(__("\"The payment process failed. SSN and Date of Birth must be set!", 'paysafe-checkout'), true);
                        are_fields_valid = false;
                    }
                }

                if (!are_fields_valid) {
                    return false;
                }

                fetch(
                    paysafe_op_settings.get_order_pay_data_url,
                    {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: new Headers({
                            // 'Content-Type': 'application/x-www-form-urlencoded',
                            'Content-Type': 'application/json',
                        }),
                        body: JSON.stringify({
                            order_id: order_id,
                            token_id: token_id,
                            nonce: paysafe_op_settings.nonce
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
                            environment: paysafe_op_settings.test_mode ? 'TEST' : 'LIVE',
                            threeDs: {
                                merchantUrl: paysafe_op_settings.checkout_url,
                                deviceChannel: "BROWSER",
                                messageCategory: "PAYMENT",
                                authenticationPurpose: "PAYMENT_TRANSACTION",
                                transactionIntent: "GOODS_OR_SERVICE_PURCHASE",
                                profile: {
                                    email: paysafe_op_settings.user_email,
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
                                    locale: paysafe_op_settings.locale,
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

                        if (paysafe_op_settings.merchant_descriptor && paysafe_op_settings.merchant_phone) {
                            tokenizationOpOptions.merchantDescriptor = {
                                dynamicDescriptor: paysafe_op_settings.merchant_descriptor,
                                phone: paysafe_op_settings.merchant_phone,
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
                                    nonce: paysafe_op_settings.nonce,
                                };

                                if (is_change_subs_payment_method) {
                                    paymentData.change_subs_payment_method = is_change_subs_payment_method;
                                    paymentData.update_all_subs = update_all_subs_payment_method;
                                    paymentData.transactionType = 'VERIFICATION';
                                    paymentData.amount = 0;
                                }

                                fetch(
                                    paysafe_op_settings.register_url,
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
                                        show_op_error_message(__('The payment process failed. Please reload the page and try again', 'paysafe-checkout'), true);
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
                        let transactionSource = 'WooCommerceCheckout';
                        let displayPaymentMetods = [
                            'card'
                        ];
                        let paymentMethodDetails = {
                            card: {
                                accountId: paysafe_op_settings.card_account_id,
                            }
                        };
                        let billing_phone = null;
                        let customerData = null;
                        let shippingData = null;
                        let add_customer_when_single_use_token = false;
                        let add_billing_when_single_use_token = false;

                        if (is_apple_pay) {
                            displayPaymentMetods = [
                                'applePay'
                            ];
                            paymentMethodDetails = {
                                applePay: {
                                    accountId: paysafe_op_settings.apple_pay_account_id,
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
                                    accountId: paysafe_op_settings.google_pay_account_id,
                                    label: 'google_pay',
                                    merchantId: response.merch_ref_num,
                                    requestBillingAddress: true,
                                    requiredBillingContactFields: ['email'],
                                    requiredShippingContactFields: ['name'],
                                    country: paysafe_op_settings.google_pay_issuer_country,
                                }
                            };
                        }

                        if (is_skrill) {
                            displayPaymentMetods = [
                                'skrill'
                            ];
                            paymentMethodDetails = {
                                skrill: {
                                    accountId: paysafe_op_settings.skrill_account_id,
                                    consumerId: paysafe_op_settings.consumer_id,
                                    emailSubject: paysafe_op_settings.details.subject,
                                    emailMessage: paysafe_op_settings.details.message,
                                }
                            };
                        }

                        if (is_neteller) {
                            displayPaymentMetods = [
                                'neteller'
                            ];
                            paymentMethodDetails = {
                                neteller: {
                                    consumerId: paysafe_op_settings.consumer_id,
                                }
                            };
                        }

                        if (is_paysafecash) {
                            let consumer_id = paysafe_op_settings.consumer_id_encrypted;
                            if (!consumer_id) {
                                consumer_id = paysafe_sha512_enc(response.customer.email);
                            }

                            displayPaymentMetods = [
                                'paysafecash'
                            ];
                            paymentMethodDetails = {
                                paysafecash: {
                                    accountId: paysafe_op_settings.paysafecash_account_id,
                                    consumerId: consumer_id,
                                }
                            };
                        }

                        if (is_paysafecard) {
                            let consumer_id = paysafe_op_settings.consumer_id_encrypted;
                            if (!consumer_id) {
                                consumer_id = paysafe_sha512_enc(response.customer.email);
                            }

                            displayPaymentMetods = [
                                'paysafecard'
                            ];
                            paymentMethodDetails = {
                                paysafecard: {
                                    accountId: paysafe_op_settings.paysafecard_account_id,
                                    consumerId: consumer_id,
                                }
                            };
                        }

                        if (is_eft) {
                            displayPaymentMetods = [
                                'eft'
                            ];
                            paymentMethodDetails = {
                                eft: {
                                    accountId: paysafe_op_settings.eft_account_id,
                                }
                            };
                        }

                        if (is_ach) {
                            displayPaymentMetods = [
                                'ach'
                            ];
                            paymentMethodDetails = {
                                ach: {
                                    accountId: paysafe_op_settings.ach_account_id,
                                }
                            };
                        }

                        if (is_paypal) {
                            let paysafepaypalConsumerIdInput = document.getElementById('paysafepaypal_consumeridInput');
                            let paysafepaypalConsumerIdValue = paysafepaypalConsumerIdInput && paysafepaypalConsumerIdInput.value
                            && paysafepaypalConsumerIdInput.value.length ? paysafepaypalConsumerIdInput.value : '';

                            displayPaymentMetods = [
                                'paypal'
                            ];
                            paymentMethodDetails = {
                                paypal: {
                                    accountId: paysafe_op_settings.paysafepaypal_account_id,
                                    consumerId: paysafepaypalConsumerIdValue,
                                }
                            };
                        }

                        if (is_sightline) {
                            let sightlineConsumerIdInput = document.getElementById('sightline_consumeridInput');
                            let sightlineConsumerIdValue = sightlineConsumerIdInput && sightlineConsumerIdInput.value
                            && sightlineConsumerIdInput.value.length && sightlineConsumerIdInput.value.length >= 2
                            && sightlineConsumerIdInput.value.length <= 50 ? sightlineConsumerIdInput.value : '';

                            displayPaymentMetods = [
                                'sightline'
                            ];
                            paymentMethodDetails = {
                                sightline: {
                                    accountId: paysafe_op_settings.sightline_account_id,
                                    consumerId: '' + sightlineConsumerIdValue,
                                }
                            };
                            shippingData = {
                                city: response.billing.city,
                                country: response.billing.country,
                                recipientName: response.customer.first_name + ' ' + response.customer.last_name,
                                state: response.billing.state,
                                street: response.billing.address_1,
                                street2: response.billing.address_2,
                                zip: response.billing.postcode,
                            }
                        }

                        if (is_vippreferred) {
                            let vippreferredConsumerIdInput = document.getElementById('vippreferred_consumeridInput');
                            let vippreferredConsumerIdValue = vippreferredConsumerIdInput && vippreferredConsumerIdInput.value
                            && vippreferredConsumerIdInput.value.length && vippreferredConsumerIdInput.value.length === 9
                                ? vippreferredConsumerIdInput.value : '';

                            let vippreferredPhonenumberInput = document.getElementById('vippreferred_phonenumberInput');
                            let vippreferredPhonenumberValue = vippreferredPhonenumberInput && vippreferredPhonenumberInput.value
                            && vippreferredPhonenumberInput.value.length && vippreferredPhonenumberInput.value.length >= 9
                                ? vippreferredPhonenumberInput.value : '';

                            transactionSource = 'WooCommerce';
                            displayPaymentMetods = [
                                'vippreferred'
                            ];
                            paymentMethodDetails = {
                                vippreferred: {
                                    accountId: paysafe_op_settings.vippreferred_account_id,
                                    consumerId: vippreferredConsumerIdValue,
                                }
                            };
                            customerData = {
                                firstName: response.customer.first_name,
                                lastName: response.customer.last_name,
                                email: response.customer.email,
                                phone: vippreferredPhonenumberValue,
                            };
                            billing_phone = vippreferredPhonenumberValue;
                            add_customer_when_single_use_token = true;
                            add_billing_when_single_use_token = true;
                        }

                        if (is_paybybank) {
                            let consumer_id = paysafe_op_settings.consumer_id_encrypted;
                            if (!consumer_id) {
                                consumer_id = paysafe_sha512_enc(response.customer.email);
                            }

                            let paybybankConsumerIdInput = document.getElementById('paybybank_consumeridInput');
                            let paybybankConsumerIdValue = paybybankConsumerIdInput && paybybankConsumerIdInput.value
                            && paybybankConsumerIdInput.value.length ? paybybankConsumerIdInput.value : '';

                            let paybybankDOBInput = document.getElementById('paybybank_dobInput');
                            let paybybankDOBValue = paybybankDOBInput && paybybankDOBInput.value
                            && paybybankDOBInput.value.length ? paybybankDOBInput.value : '';
                            let paybybank_dob_year = '';
                            let paybybank_dob_month = '';
                            let paybybank_dob_day = '';
                            if (paybybankDOBValue) {
                                const dobDate = new Date(paybybankDOBValue);
                                if (!isNaN(dobDate.getTime())) {
                                    paybybank_dob_year = dobDate.getFullYear();
                                    paybybank_dob_month = dobDate.getMonth() + 1; // Months are zero-based
                                    paybybank_dob_day = dobDate.getDate();
                                }
                            }

                            displayPaymentMetods = [
                                'paybybank'
                            ];
                            paymentMethodDetails = {
                                paybybank: {
                                    accountId: paysafe_op_settings.pay_by_bank_account_id,
                                    consumerId: consumer_id,
                                }
                            };
                            customerData = {
                                firstName: response.customer.first_name,
                                lastName: response.customer.last_name,
                                email: response.customer.email,
                                identityDocuments: [{
                                    type: "SOCIAL_SECURITY",
                                    documentNumber: paybybankConsumerIdValue,
                                }],
                                dateOfBirth: {
                                    year: paybybank_dob_year,
                                    month: paybybank_dob_month,
                                    day: paybybank_dob_day,
                                }
                            }
                            billing_phone = response.billing.phone ? response.billing.phone : null;
                            add_customer_when_single_use_token = true;
                        }

                        if (is_venmo) {
                            let paysafevenmoConsumerIdInput = document.getElementById('paysafevenmo_consumeridInput');
                            let paysafevenmoConsumerIdValue = paysafevenmoConsumerIdInput && paysafevenmoConsumerIdInput.value
                            && paysafevenmoConsumerIdInput.value.length ? paysafevenmoConsumerIdInput.value : '';

                            displayPaymentMetods = [
                                'venmo'
                            ];
                            paymentMethodDetails = {
                                venmo: {
                                    accountId: paysafe_op_settings.venmo_account_id,
                                    consumerId: '' + paysafevenmoConsumerIdValue,
                                }
                            };
                        }

                        const checkout_options = {
                            transactionSource: transactionSource,
                            amount: parseInt(response.order.amount),
                            transactionType: 'PAYMENT',
                            currency: response.order.currency,
                            merchantRefNum: response.merch_ref_num,
                            environment: paysafe_op_settings.test_mode ? 'TEST' : 'LIVE',
                            displayPaymentMethods: displayPaymentMetods,
                            paymentMethodDetails: paymentMethodDetails,
                            threeDs: {
                                merchantUrl: paysafe_op_settings.checkout_url,
                                deviceChannel: "BROWSER",
                                messageCategory: "PAYMENT",
                                authenticationPurpose: "PAYMENT_TRANSACTION",
                                transactionIntent: "GOODS_OR_SERVICE_PURCHASE",
                            },
                            locale: paysafe_op_settings.locale,
                        };

                        if (paysafe_op_settings.merchant_descriptor && paysafe_op_settings.merchant_phone) {
                            checkout_options.merchantDescriptor = {
                                dynamicDescriptor: paysafe_op_settings.merchant_descriptor,
                                phone: paysafe_op_settings.merchant_phone,
                            }
                        }

                        if (customerSingleUseToken) {
                            checkout_options.singleUseCustomerToken = customerSingleUseToken;
                            if (customerData) {
                                checkout_options.customer = customerData;
                            }
                        }

                        if (!customerSingleUseToken || add_customer_when_single_use_token) {
                            if (customerData) {
                                checkout_options.customer = customerData;
                            } else {
                                checkout_options.customer = {
                                    firstName: response.customer.first_name,
                                    lastName: response.customer.last_name,
                                    email: response.customer.email,
                                };
                            }
                        }
                        if (!customerSingleUseToken || add_billing_when_single_use_token) {
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
                            if (billing_phone) {
                                checkout_options.billingAddress.phone = billing_phone;
                            }
                        }

                        if (shippingData) {
                            checkout_options.shippingAddress = shippingData;
                        }

                        paysafe.checkout.setup(
                            paysafe_op_settings.authorization,
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
                                        nonce: paysafe_op_settings.nonce,
                                    };

                                    fetch(
                                        paysafe_op_settings.register_url,
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
                                                instance.showSuccessScreen(__("Your goods are now purchased. Expect them to be delivered in next 5 business days.", 'paysafe-checkout'));

                                                handle_payment_success();
                                            } else {
                                                instance.showFailureScreen(__("The payment process failed. Please close this popup and try again", 'paysafe-checkout'));

                                                handle_payment_failure();
                                            }
                                        })
                                        .catch((error) => {
                                            // the BE call failed
                                            instance.showFailureScreen(__("The payment process failed. Please close this popup and try again", 'paysafe-checkout'));

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
                                        log_paysafe_error(__("Payment failed. Popup was closed without a correct end message!" + ' ' + errorMessage, 'paysafe-checkout'), {'pace': 3});
                                        instance.showFailureScreen(__("The payment was declined. Please, try again with the same or another payment method. " + errorMessage, 'paysafe-checkout'));
                                    } else {
                                        log_paysafe_error(__("Payment failed. Popup was closed without a correct end message!" + ' ' + errorMessage, 'paysafe-checkout'), {'pace': 3});
                                        show_op_error_message(__('The payment process failed. Please reload the page and try again. ' + errorMessage, 'paysafe-checkout'), true);

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
                                    instance.decline(__("Amount is not the value expected", 'paysafe-checkout'));
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
            if (payment_type === 'eft') {
                variable_name = 'wc-eft-order-pay-result';
            }
            if (payment_type === 'ach') {
                variable_name = 'wc-ach-order-pay-result';
            }
            if (payment_type === 'paysafepaypal') {
                variable_name = 'wc-paypal-order-pay-result';
            }
            if (payment_type === 'sightline') {
                variable_name = 'wc-sightline-order-pay-result';
            }
            if (payment_type === 'vippreferred') {
                variable_name = 'wc-vippreferred-order-pay-result';
            }
            if (payment_type === 'pay_by_bank') {
                variable_name = 'wc-paybybank-order-pay-result';
            }
            if (payment_type === 'venmo') {
                variable_name = 'wc-venmo-order-pay-result';
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

        let isDefaultValueSet4PayPal = false;
        let isDefaultValueSet4Venmo = false;
        let isDefaultValueSet4Vippreferred = false;

        $( document.body ).on(
            'click',
            'li.woocommerce-SavedPaymentMethods-token, li.woocommerce-SavedPaymentMethods-new, li.wc_payment_method',
            function(e) {
                setTimeout(handle_paysafe_js_initialization, 500);
            });

        const handle_paysafe_js_initialization = function() {
            const payment_method = $('input[name=payment_method]:checked').val();
            if (payment_method === 'paysafe') {
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
            }
            if (payment_method === 'paysafepaypal') {
                if (!isDefaultValueSet4PayPal) {
                    isDefaultValueSet4PayPal = true;
                    setTimeout(() => {
                        let paysafepaypalConsumerIdInput = document.getElementById('paysafepaypal_consumeridInput');
                        if (!!paysafepaypalConsumerIdInput) {
                            paysafepaypalConsumerIdInput.value = paysafe_op_settings.consumer_id;
                        }
                    }, 100);
                }
            }
            if (payment_method === 'venmo') {
                if (!isDefaultValueSet4Venmo) {
                    isDefaultValueSet4Venmo = true;
                    setTimeout(() => {
                        let paysafevenmoConsumerIdInput = document.getElementById('paysafevenmo_consumeridInput');
                        if (!!paysafevenmoConsumerIdInput) {
                            paysafevenmoConsumerIdInput.value = paysafe_op_settings.consumer_id;
                        }
                    }, 100);
                }
            }
            if (payment_method === 'vippreferred') {
                if (!isDefaultValueSet4Vippreferred) {
                    isDefaultValueSet4Vippreferred = true;
                    setTimeout(() => {
                        let vippreferredPhonenumberInput = document.getElementById('vippreferred_phonenumberInput');
                        if (!!vippreferredPhonenumberInput) {
                            vippreferredPhonenumberInput.value = document.getElementById('billing_phone') ? document.getElementById('billing_phone').value : '';
                        }
                    }, 100);
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
                    currencyCode: paysafe_op_settings.currency_code,

                    // select the Paysafe test / sandbox environment
                    environment: paysafe_op_settings.test_mode ? 'TEST' : 'LIVE',

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
                        default: parseInt(paysafe_op_settings.card_account_id),
                    },
                };

                // initialize the hosted iframes using the SDK setup function
                paysafe.fields
                    .setup(
                        paysafe_op_settings.authorization,
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


        const paysafe_sha512_enc = function(message) {
            const MASK_64 = (1n << 64n) - 1n;

            const H = [
                0x6a09e667f3bcc908n, 0xbb67ae8584caa73bn, 0x3c6ef372fe94f82bn, 0xa54ff53a5f1d36f1n,
                0x510e527fade682d1n, 0x9b05688c2b3e6c1fn, 0x1f83d9abfb41bd6bn, 0x5be0cd19137e2179n
            ];

            const K = [
                0x428a2f98d728ae22n,0x7137449123ef65cdn,0xb5c0fbcfec4d3b2fn,0xe9b5dba58189dbbcn,
                0x3956c25bf348b538n,0x59f111f1b605d019n,0x923f82a4af194f9bn,0xab1c5ed5da6d8118n,
                0xd807aa98a3030242n,0x12835b0145706fben,0x243185be4ee4b28cn,0x550c7dc3d5ffb4e2n,
                0x72be5d74f27b896fn,0x80deb1fe3b1696b1n,0x9bdc06a725c71235n,0xc19bf174cf692694n,
                0xe49b69c19ef14ad2n,0xefbe4786384f25e3n,0x0fc19dc68b8cd5b5n,0x240ca1cc77ac9c65n,
                0x2de92c6f592b0275n,0x4a7484aa6ea6e483n,0x5cb0a9dcbd41fbd4n,0x76f988da831153b5n,
                0x983e5152ee66dfabn,0xa831c66d2db43210n,0xb00327c898fb213fn,0xbf597fc7beef0ee4n,
                0xc6e00bf33da88fc2n,0xd5a79147930aa725n,0x06ca6351e003826fn,0x142929670a0e6e70n,
                0x27b70a8546d22ffcn,0x2e1b21385c26c926n,0x4d2c6dfc5ac42aedn,0x53380d139d95b3dfn,
                0x650a73548baf63den,0x766a0abb3c77b2a8n,0x81c2c92e47edaee6n,0x92722c851482353bn,
                0xa2bfe8a14cf10364n,0xa81a664bbc423001n,0xc24b8b70d0f89791n,0xc76c51a30654be30n,
                0xd192e819d6ef5218n,0xd69906245565a910n,0xf40e35855771202an,0x106aa07032bbd1b8n,
                0x19a4c116b8d2d0c8n,0x1e376c085141ab53n,0x2748774cdf8eeb99n,0x34b0bcb5e19b48a8n,
                0x391c0cb3c5c95a63n,0x4ed8aa4ae3418acbn,0x5b9cca4f7763e373n,0x682e6ff3d6b2b8a3n,
                0x748f82ee5defb2fcn,0x78a5636f43172f60n,0x84c87814a1f0ab72n,0x8cc702081a6439ecn,
                0x90befffa23631e28n,0xa4506cebde82bde9n,0xbef9a3f7b2c67915n,0xc67178f2e372532bn,
                0xca273eceea26619cn,0xd186b8c721c0c207n,0xeada7dd6cde0eb1en,0xf57d4f7fee6ed178n,
                0x06f067aa72176fban,0x0a637dc5a2c898a6n,0x113f9804bef90daen,0x1b710b35131c471bn,
                0x28db77f523047d84n,0x32caab7b40c72493n,0x3c9ebe0a15c9bebcn,0x431d67c49c100d4cn,
                0x4cc5d4becb3e42b6n,0x597f299cfc657e2an,0x5fcb6fab3ad6faecn,0x6c44198c4a475817n
            ];

            const ROTR = (x, n) => ((x >> BigInt(n)) | (x << BigInt(64 - n))) & MASK_64;
            const SHR  = (x, n) => x >> BigInt(n);
            const Ch   = (x, y, z) => (x & y) ^ (~x & z);
            const Maj  = (x, y, z) => (x & y) ^ (x & z) ^ (y & z);
            const S0   = x => ROTR(x, 28) ^ ROTR(x, 34) ^ ROTR(x, 39);
            const S1   = x => ROTR(x, 14) ^ ROTR(x, 18) ^ ROTR(x, 41);
            const s0   = x => ROTR(x, 1)  ^ ROTR(x, 8)  ^ SHR(x, 7);
            const s1   = x => ROTR(x, 19) ^ ROTR(x, 61) ^ SHR(x, 6);

            const bytes = Array.from(new TextEncoder().encode(message));
            const bitLen = BigInt(bytes.length) * 8n;

            bytes.push(0x80);
            while ((bytes.length % 128) !== 112) bytes.push(0);

            const high = bitLen >> 64n;
            const low  = bitLen & MASK_64;
            for (let i = 7; i >= 0; i--) bytes.push(Number((high >> BigInt(i * 8)) & 0xffn));
            for (let i = 7; i >= 0; i--) bytes.push(Number((low  >> BigInt(i * 8)) & 0xffn));

            for (let offset = 0; offset < bytes.length; offset += 128) {
                const W = new Array(80).fill(0n);

                for (let i = 0; i < 16; i++) {
                    let v = 0n;
                    for (let j = 0; j < 8; j++) v = (v << 8n) | BigInt(bytes[offset + i * 8 + j]);
                    W[i] = v;
                }
                for (let i = 16; i < 80; i++) {
                    W[i] = (s1(W[i - 2]) + W[i - 7] + s0(W[i - 15]) + W[i - 16]) & MASK_64;
                }

                let [a, b, c, d, e, f, g, h] = H;

                for (let i = 0; i < 80; i++) {
                    const T1 = (h + S1(e) + Ch(e, f, g) + K[i] + W[i]) & MASK_64;
                    const T2 = (S0(a) + Maj(a, b, c)) & MASK_64;
                    h = g; g = f; f = e;
                    e = (d + T1) & MASK_64;
                    d = c; c = b; b = a;
                    a = (T1 + T2) & MASK_64;
                }

                H[0] = (H[0] + a) & MASK_64; H[1] = (H[1] + b) & MASK_64;
                H[2] = (H[2] + c) & MASK_64; H[3] = (H[3] + d) & MASK_64;
                H[4] = (H[4] + e) & MASK_64; H[5] = (H[5] + f) & MASK_64;
                H[6] = (H[6] + g) & MASK_64; H[7] = (H[7] + h) & MASK_64;
            }

            return H.map(x => x.toString(16).padStart(16, "0")).join("").substring(0, 50);
        }


        setTimeout(handle_paysafe_js_initialization, 1000);
    });
}) ( jQuery );
