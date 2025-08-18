import { sprintf, __ } from '@wordpress/i18n';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';
import { useEffect } from '@wordpress/element'

const paymentMethodId = "apple_pay";

const defaultLabel = 'Paysafe Checkout';

const settings = getSetting( 'apple_pay_data', {} );

const log_paysafe_error = function(message, context) {
    if (settings.log_errors === true && settings.log_error_endpoint) {
        fetch(
            settings.log_error_endpoint,
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

const is_hosted_integration = settings.integration_type === 'paysafe_js';

const label = decodeEntities( settings.title ) || defaultLabel;
const icons = [
    {
        id: 'apple-pay-icon',
        src: settings.icon,
        alt: label,
    }
];

const Label = () => {
    return (
        <div className={'paysafe-gateway-title'}>
            <span>{ label }</span>
            <img
                className={'paysafe-gateway-image'}
                src={ settings.icon }
                alt={ label }
            />
        </div>
    );
};

let paymentAmount = 0;
let currentCartData = {};
let next_page = null;

const show_checkout_error = function(error_message) {
    let woo_errors = document.getElementsByClassName('woocommerce-error');
    woo_errors = woo_errors.length > 0 ? woo_errors[0] : null;

    if (!woo_errors) {
        let notice_wrappers = document.getElementsByClassName('woocommerce-notices-wrapper');
        let notice_wrapper = notice_wrappers.length > 0 ? notice_wrappers[0] : null;
        if (notice_wrapper) {
            woo_errors = document.createElement('ul');
            woo_errors.className = 'woocommerce-error';

            notice_wrapper.appendChild(woo_errors);
        }
    }

    if (woo_errors) {
        let error_li = document.getElementById('paysafe-fatal-error-message');
        if (!error_li) {
            error_li = document.createElement('li');
            error_li.setAttribute('id', 'paysafe-fatal-error-message');
            error_li.innerHTML = error_message;
            woo_errors.appendChild(error_li);

            window.scrollTo({
                top: 0,
                left: 0,
                behavior: "smooth",
            });
        } else {
            error_li.innerHTML = error_message;
        }
    }
}


let hostedApplePayInstance = null;
const init_apple_pay_hosted_integration = function() {
    // if the page doesn't have the form, don't load it
    if (!jQuery('#paysafe-hosted-apple-pay-payment-form').length) {
        return;
    }

    const paysafeOptions = {
        // You must provide currencyCode to the Paysafe JS SDK to enable the Payment API integration
        currencyCode: settings.currency_code,

        // select the Paysafe test / sandbox environment
        environment: settings.test_mode ? 'TEST' : 'LIVE',

        transactionSource: 'WooCommerceJs',

        accounts: {
            default: settings.account_id,
        },

        fields: {
            applePay: {
                selector: '#paysafe-apple-pay',
                type: 'buy',
                label: __('Pay with Apple Pay', 'paysafe-checkout'),
                color: 'white-outline',
            },
        },
    };

    paymentAmount = parseInt(currentCartData.cartTotals.total_price);
    let billing_street = currentCartData.billingAddress.address_1;
    let billing_street2 = currentCartData.billingAddress.address_2;
    let billing_city = currentCartData.billingAddress.city;
    let billing_zip = currentCartData.billingAddress.postcode;
    let billing_state = currentCartData.billingAddress.state;
    let billing_country = currentCartData.billingAddress.country;

    let merchant_ref_num = 'merchant-ref-num-' + new Date().getTime();

    // initialize the hosted iframes using the SDK setup function
    paysafe.fields
        .setup(
            settings.authorization,
            paysafeOptions
        )
        .then(instance => {
            hostedApplePayInstance = instance;
            return instance.show();
        })
        .then(paymentMethods => {
            if (paymentMethods.applePay && !paymentMethods.applePay.error) {
                document.getElementById('paysafe-apple-pay').addEventListener(
                    'click',
                    function (event) {
                        hostedApplePayInstance
                            .tokenize({
                                amount: paymentAmount,
                                transactionType: 'PAYMENT',
                                paymentType: 'APPLEPAY',
                                applePay: {
                                    country: billing_country,
                                },
                                customerDetails: {
                                    billingDetails: {
                                        country: billing_country,
                                        zip: billing_zip,
                                        street: billing_street,
                                        city: billing_city,
                                        state: billing_state,
                                    },
                                },
                                merchantRefNum: merchant_ref_num,
                            })
                            .then(result => {
                                // write the Payment token value to the browser console
                                console.log(result.token);

                                const paymentData = {
                                    orderId: 150,  // todo create the order
                                    paymentMethod: result.paymentMethod,
                                    transactionType: result.transactionType,
                                    paymentHandleToken: result.paymentHandleToken,
                                    amount: result.amount,
                                    customerOperation: result.customerOperation,
                                    merchantRefNum: merchant_ref_num,
                                };

                                fetch(
                                    settings.register_url,
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
                                        if (json.status === 'success') {
                                            // close the Apple Pay window
                                            hostedApplePayInstance.complete('success');
                                        } else {
                                            // todo show error message
                                            show_checkout_error('ERROR at BE call: ' + JSON.stringify(json));

                                            hostedApplePayInstance.complete('fail');
                                        }

                                        window.location = json.redirect_url;
                                    })
                                    .catch((error) => {
                                        // the BE call failed
                                        const error_message = 'ERROR ' + error.code + ': ' + error.detailedMessage;

                                        log_paysafe_error(error_message, []);
                                        show_checkout_error(error_message);

                                        hostedApplePayInstance.complete('fail');
                                    });
                            })
                            .catch(error => {
                                // display the tokenization error in dialog window
                                const error_message = 'ERROR ' + error.code + ': ' + error.detailedMessage;

                                log_paysafe_error(error_message, []);
                                show_checkout_error(error_message);

                                // close the Apple Pay window
                                hostedApplePayInstance.complete('fail');
                            });
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
            show_checkout_error(error_message);
        });
}

const Content = ( props ) => {
    const { eventRegistration, emitResponse } = props;
    const { onCheckoutSuccess } = eventRegistration;

    useEffect(() => {
        if (is_hosted_integration) {
            init_apple_pay_hosted_integration();
        }

        const unsubscribe = onCheckoutSuccess( (response) => {

            const orderId = response.orderId;
            const customerSingleUseToken = response && response.processingResponse && response.processingResponse.paymentDetails && response.processingResponse.paymentDetails.single_use_token ? response.processingResponse.paymentDetails.single_use_token : '';
            const merch_ref_num = response && response.processingResponse && response.processingResponse.paymentDetails && response.processingResponse.paymentDetails.merch_ref_num ? response.processingResponse.paymentDetails.merch_ref_num : '';

            let master_error = null;

            paymentAmount = parseInt(currentCartData.cartTotals.total_price);

            if (orderId && paymentAmount) {
                let currency_code = currentCartData.cartTotals.currency_code;
                let customer_first_name = currentCartData.billingAddress.first_name;
                let customer_last_name = currentCartData.billingAddress.last_name;
                let customer_email = currentCartData.billingAddress.email;

                let billing_street = currentCartData.billingAddress.address_1;
                let billing_street2 = currentCartData.billingAddress.address_2;
                let billing_city = currentCartData.billingAddress.city;
                let billing_zip = currentCartData.billingAddress.postcode;
                let billing_state = currentCartData.billingAddress.state;
                let billing_country = currentCartData.billingAddress.country;
                let billing_phone = currentCartData.billingAddress.phone;

                const checkout_options = {
                    transactionSource: 'WooCommerceCheckout',
                    amount: paymentAmount,
                    transactionType: 'PAYMENT',
                    currency: currency_code,
                    merchantRefNum: merch_ref_num,
                    environment: settings.test_mode ? 'TEST' : 'LIVE',
                    displayPaymentMethods: [
                        'applePay'
                    ],
                    paymentMethodDetails: {
                        applePay: {
                            accountId: settings.account_id,
                            label: 'apple_pay',
                        }
                    },
                    threeDs: {
                        merchantUrl: settings.checkout_url,
                        deviceChannel: "BROWSER",
                        messageCategory: "PAYMENT",
                        authenticationPurpose: "PAYMENT_TRANSACTION",
                        transactionIntent: "GOODS_OR_SERVICE_PURCHASE",
                    },
                    locale: settings.locale,
                };

                if (settings.merchant_descriptor && settings.merchant_phone) {
                    checkout_options.merchantDescriptor = {
                        dynamicDescriptor: settings.merchant_descriptor,
                        phone: settings.merchant_phone,
                    }
                }

                if (customerSingleUseToken) {
                    checkout_options.singleUseCustomerToken = customerSingleUseToken;
                } else {
                    checkout_options.customer = {
                        firstName: customer_first_name,
                        lastName: customer_last_name,
                        email: customer_email,
                    };
                    checkout_options.billingAddress = {
                        nickName: "Home",
                        zip: billing_zip,
                        country: billing_country,
                    };
                    if (billing_street) {
                        checkout_options.billingAddress.street = billing_street;
                    }
                    if (billing_street2) {
                        checkout_options.billingAddress.street2 = billing_street2;
                    }
                    if (billing_city) {
                        checkout_options.billingAddress.city = billing_city;
                    }
                    if (billing_state) {
                        checkout_options.billingAddress.state = billing_state;
                    }
                }

                paysafe.checkout.setup(
                    settings.authorization,
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
                                merchantRefNum: checkout_options.merchantRefNum,
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
                                        instance.showSuccessScreen(__("Your goods are now purchased. Expect them to be delivered in next 5 business days.", "paysafe-checkout"));
                                    } else {
                                        log_paysafe_error("Payment failed. Popup was closed without a correct end message!", error);
                                        instance.showFailureScreen(__("The payment process failed. Please close this popup and try again", "paysafe-checkout"));
                                    }

                                    next_page = json.redirect_url;
                                })
                                .catch((error) => {
                                    // the BE call failed
                                    log_paysafe_error(__("The payment process failed. Please close this popup and try again", "paysafe-checkout"), error);
                                    instance.showFailureScreen(__("The payment process failed. Please close this popup and try again", "paysafe-checkout"));
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
                                log_paysafe_error(__("Payment failed. Popup was closed without a correct end message!", "paysafe-checkout") + ' ' + errorMessage, {'pace': 3});
                                instance.showFailureScreen(__("The payment was declined. Please, try again with the same or another payment method." + ' ' + errorMessage, "paysafe-checkout"));
                            } else {
                                master_error = __('The payment was declined. Please, try again with the same or another payment method.', "paysafe-checkout");
                                if (error && error.code && error.detailedMessage) {
                                    master_error += "<br />" + ' ' + error.code + ' ' + error.detailedMessage;
                                }

                                log_paysafe_error(master_error, []);
                                show_checkout_error(master_error);
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
                            // Add action in case Checkout is expired
                            // the popup expired,
                            // lets reload the page so that the customer
                            // can try another payment option
                            window.location.reload();
                        }
                    },

                    // riskCallback
                    function (instance, amount, paymentMethod) {
                        if (amount === paymentAmount) {
                            instance.accept();
                        } else {
                            log_paysafe_error(__("Amount is not the value expected", "paysafe-checkout"), []);
                            instance.decline(__("Amount is not the value expected", "paysafe-checkout"));
                        }
                    }
                );
            } else {
                log_paysafe_error(__("The payment process failed. Please close this popup and try again", "paysafe-checkout"), []);
                return {
                    type: emitResponse.responseTypes.ERROR,
                    message: __("The payment process failed. Please close this popup and try again", "paysafe-checkout"),
                }
            }

            return response;
        });

        return () => {
            unsubscribe();
        };
    }, [
        emitResponse.responseTypes.ERROR,
        emitResponse.responseTypes.SUCCESS,
        '',
        [],
        onCheckoutSuccess,
    ]);

    let descriptionForDisplay = [
        <div>{ decodeEntities( settings.description || '' ) }</div>
    ];

    if( settings.test_mode ) {
        descriptionForDisplay.push(<hr></hr>);
        descriptionForDisplay.push(
            <div style={{textAlign: "justify", marginTop: "10px"}}>
                <span>{__('Paysafe Checkout is in TEST mode. Use the built-in simulator to test payments.', 'paysafe-checkout')}</span>
            </div>
        );
    }

    if( settings.test_mode && is_hosted_integration ) {
        descriptionForDisplay.push(<p></p>);
    }

    if (is_hosted_integration) {
        descriptionForDisplay.push(wp.element.RawHTML({ children: settings.integration_hosted_form }));
    }

    return descriptionForDisplay;
};

const isApplePayAvailable = function() {
    // First, check if ApplePaySession is available in the window object
    if (window.ApplePaySession && ApplePaySession.canMakePayments) {
        // This will return a Promise resolving to true/false
        return ApplePaySession.canMakePayments();
    }

    return false;
}

const PaysafeApplePayPayments = {
    name: paymentMethodId,
    paymentMethodId: paymentMethodId,
    label: <Label />,
    content: <Content />,
    edit: <Content />,
    canMakePayment: ( cartData => {
        currentCartData = cartData;

        return isApplePayAvailable();
    }),
    ariaLabel: label,
    supports: {
        features: settings.supports,
    },
};

registerPaymentMethod(PaysafeApplePayPayments);
