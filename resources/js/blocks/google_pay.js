import { sprintf, __ } from '@wordpress/i18n';
import { registerPaymentMethod, registerExpressPaymentMethod } from '@woocommerce/blocks-registry';
import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';
import { useEffect } from '@wordpress/element'
import {
    get_paysafe_label,
    log_paysafe_error,
    show_paysafe_checkout_error,
    validate_paysafe_safe_path
} from "./paysafe-common";

const paymentMethodId = "google_pay";
const settings = getSetting( 'google_pay_data', {} );
const log_error_endpoint = settings.log_errors === true ? (settings.log_error_endpoint || null) : null;
const is_hosted_integration = settings.integration_type === 'paysafe_js';

let is_apple_pay_google_pay_combo_activated = false;
if (!!settings.is_apple_pay_express_enabled && !!settings.is_google_pay_express_enabled) {
    is_apple_pay_google_pay_combo_activated = true;
}
let canExpressPayWithGooglePay = true;

let paymentAmount = 0;
let currentCartData = {};
let next_page = null;

let hostedGooglePayInstance = null;
let is_express_google_pay_initialized = false;
const init_google_pay_hosted_integration = function(onClick, onClose, onError) {
    // if the page doesn't have the form, don't load it
    if (!jQuery('#paysafe-hosted-google-pay-payment-form').length) {
        return;
    }

    if (!!settings.is_apple_pay_express_enabled && !!settings.is_google_pay_express_enabled) {
        return;
    }

    if (is_express_google_pay_initialized) {
        return;
    }

    if (typeof(paysafe) === "undefined" || typeof(paysafe.fields) === "undefined") {
        setTimeout(function() {
            init_google_pay_hosted_integration(onClick, onClose, onError);
        }, 1000);
        return;
    }

    is_express_google_pay_initialized = true;

    let paysafeOptions = {
        // You must provide currencyCode to the Paysafe JS SDK to enable the Payment API integration
        currencyCode: settings.currency_code,

        // select the Paysafe test / sandbox environment
        environment: settings.test_mode ? 'TEST' : 'LIVE',

        transactionSource: 'WooCommerceJs',

        accounts: {
            default: settings.account_id,
            googlePay: settings.account_id,
        },

        fields: {
            googlePay: {
                selector: '#paysafe-google-pay',
                type: 'buy',
                color: 'black',
                label: __('Google Pay', 'paysafe-checkout'),
            },
        },
    };
    if (settings.google_pay_issuer_country) {
        paysafeOptions.fields.googlePay.country = settings.google_pay_issuer_country;
    }
    if (settings.google_pay_merchant_id) {
        paysafeOptions.fields.googlePay.merchantId = settings.google_pay_merchant_id;
    }

    paymentAmount = parseInt(currentCartData.cartTotals.total_price);
    let billing_street = currentCartData.billingAddress.address_1;
    let billing_street2 = currentCartData.billingAddress.address_2;
    let billing_city = currentCartData.billingAddress.city;
    let billing_zip = currentCartData.billingAddress.postcode;
    let billing_state = currentCartData.billingAddress.state;
    let billing_country = currentCartData.billingAddress.country;

    if (billing_country) {
        paysafeOptions.fields.googlePay.supportedCountries = [
            billing_country
        ];
    }

    let button_clicked = false;

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
                    // document.getElementById('gpay-button-online-api-id').addEventListener(
                    'click',
                    function (event) {

                        event.preventDefault();
                        event.stopPropagation();

                        // disable the express checkout block,
                        // payment process started
                        onClick();

                        button_clicked = true;

                        const safe_path = validate_paysafe_safe_path(settings.express_checkout_endpoint);
                        fetch(
                            safe_path,
                            {
                                method: 'POST',
                                credentials: 'same-origin',
                                headers: new Headers({
                                    'Content-Type': 'application/json',
                                }),
                                body: JSON.stringify({
                                    gateway_id: paymentMethodId,
                                    billing: currentCartData.billingAddress,
                                    nonce: settings.nonce,
                                }),
                            })
                            .then(result => {
                                return result.json();
                            })
                            .then(json => {
                                if (json.status === 'success') {
                                    paymentAmount = parseInt(currentCartData.cartTotals.total_price);
                                    if (!paymentAmount) {
                                        log_paysafe_error(log_error_endpoint, "ERROR: Express checkout order amount is not set", []);
                                        onError(__("ERROR: Express checkout order amount is not set", "paysafe-checkout"));
                                        return;
                                    }

                                    const order_id = json.order_id || null;
                                    if (!order_id) {
                                        log_paysafe_error(log_error_endpoint, "ERROR: Express checkout couldn't create Order", []);
                                        onError(__("ERROR: Express checkout couldn't create the order", "paysafe-checkout"));
                                        return;
                                    }

                                    const currency_code = currentCartData.cartTotals.currency_code;
                                    const merchant_ref_num = json.merchant_ref_num || 'google_pay_' + Date.now();
                                    let hostedTokenizationOptions = {
                                        transactionSource: 'WooCommerceJs',
                                        amount: paymentAmount,
                                        transactionType: 'PAYMENT',
                                        currency: currency_code,
                                        merchantRefNum: merchant_ref_num,
                                        environment: settings.test_mode ? 'TEST' : 'LIVE',
                                        paymentType: 'GOOGLEPAY',
                                        googlePay: {
                                            requiredBillingContactFields: ['email'],
                                            requiredShippingContactFields: ['name'],
                                        },
                                        accountId: settings.account_id,
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
                                    if (settings.google_pay_issuer_country) {
                                        hostedTokenizationOptions.googlePay.country = settings.google_pay_issuer_country;
                                    }


                                    hostedGooglePayInstance
                                        .tokenize(hostedTokenizationOptions)
                                        .then(result => {
                                            // write the Payment token value to the browser console

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


                                            const safe_path = validate_paysafe_safe_path(settings.register_url);
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
                                                        onError("ERROR: Express checkout payment failed" + (json.message ? " (" + json.message + ")" : ""));

                                                        hostedGooglePayInstance.complete('fail');
                                                    }

                                                    window.location = json.redirect_url;
                                                })
                                                .catch((error) => {
                                                    // the BE call failed
                                                    const error_message = 'ERROR ' + error.code + ': ' + error.detailedMessage;

                                                    log_paysafe_error(log_error_endpoint, error_message, []);
                                                    onError(error_message);

                                                    hostedGooglePayInstance.complete('fail');
                                                });
                                        })
                                        .catch(error => {
                                            // display the tokenization error in dialog window
                                            const error_message = (error.displayMessage ? error.displayMessage : ('ERROR ' + error.code
                                                + (error.detailedMessage ? ': ' + error.detailedMessage : "")));

                                            log_paysafe_error(log_error_endpoint, error_message, []);

                                            if (error.detailedMessage && error.detailedMessage.includes('User aborted payment')) {
                                                onError(error.detailedMessage);
                                            } else {
                                                onError(error_message);
                                            }
                                        });
                                } else {
                                    onError(__("ERROR: Express checkout couldn't create the order", "paysafe-checkout"));
                                }
                            });

                    },
                    false,
                );
            }
        })
        .catch(error => {
            // this means that the initialization of the form failed,
            // disable this payment method
            if (parseInt(error.code) === 9023) {
                canExpressPayWithGooglePay = false;
            }

            let error_message = ('ERROR ' + error.code + (error.detailedMessage ? ': ' + error.detailedMessage : ""));
            if (error.displayMessage) {
                error_message = error.displayMessage + (error.detailedMessage ? ': ' + error.detailedMessage : "");
            }
            log_paysafe_error(log_error_endpoint, error_message, []);

            if (error.detailedMessage && error.detailedMessage.includes('User aborted payment')) {
                onError(error.detailedMessage);
            } else {
                if (button_clicked) {
                    button_clicked = false;
                    onError(error_message);
                } else {
                    onError(__('Error! Unable to set up Express Checkout at this moment. Please, try again later.', "paysafe-checkout") + (error.code ? ' (' + error.code + ')' : ""));
                }
            }
        });
}

const Content = ( props ) => {
    const { eventRegistration, emitResponse } = props;
    const { onCheckoutSuccess } = eventRegistration;

    useEffect(() => {
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

                let checkout_options = {
                    transactionSource: 'WooCommerceCheckout',
                    amount: paymentAmount,
                    transactionType: 'PAYMENT',
                    currency: currency_code,
                    merchantRefNum: merch_ref_num,
                    environment: settings.test_mode ? 'TEST' : 'LIVE',
                    displayPaymentMethods: [
                        'googlePay'
                    ],
                    paymentMethodDetails: {
                        googlePay: {
                            accountId: settings.account_id,
                            label: 'google_pay',
                            requestBillingAddress: true,
                            requiredBillingContactFields: ['email'],
                            requiredShippingContactFields: ['name'],
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
                if (settings.google_pay_issuer_country) {
                    checkout_options.paymentMethodDetails.googlePay.country = settings.google_pay_issuer_country;
                }
                if (settings.google_pay_merchant_id) {
                    checkout_options.paymentMethodDetails.googlePay.merchantId = settings.google_pay_merchant_id;
                }

                if (settings.merchant_descriptor && settings.merchant_phone) {
                    checkout_options.merchantDescriptor = {
                        dynamicDescriptor: settings.merchant_descriptor,
                        phone: settings.merchant_phone,
                    }
                }

                checkout_options.singleUseCustomerToken = customerSingleUseToken;
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
                                nonce: settings.nonce,
                            };

                            const safe_path = validate_paysafe_safe_path(settings.register_url);
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
                                        instance.showSuccessScreen(__("Your goods are now purchased. Expect them to be delivered in next 5 business days.", "paysafe-checkout"));
                                    } else {
                                        log_paysafe_error(log_error_endpoint, "Payment failed. Showing failure screen.", json);
                                        instance.showFailureScreen(__("The payment process failed. Please close this popup and try again", "paysafe-checkout"));
                                    }

                                    next_page = json.redirect_url;
                                })
                                .catch((error) => {
                                    // the BE call failed
                                    log_paysafe_error(log_error_endpoint, __("The payment process failed. Showing failure screen.", "paysafe-checkout"), error);
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
                                log_paysafe_error(log_error_endpoint, __("Payment failed. Showing failure screen.", "paysafe-checkout") + ' ' + errorMessage, {'pace': 3});
                                instance.showFailureScreen(__("The payment was declined. Please, try again with the same or another payment method." + ' ' + errorMessage, "paysafe-checkout"));
                            } else {
                                master_error = __('The payment was declined. Please, try again with the same or another payment method.', "paysafe-checkout");
                                if (error && error.code && error.detailedMessage) {
                                    master_error += "<br />" + ' ' + error.code + ' ' + error.detailedMessage;
                                }

                                log_paysafe_error(log_error_endpoint, master_error, []);
                                show_paysafe_checkout_error(master_error);
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
                            log_paysafe_error(log_error_endpoint, __("Amount is not the value expected", "paysafe-checkout"), []);
                            instance.decline(__("Amount is not the value expected", "paysafe-checkout"));
                        }
                    }
                );
            } else {
                log_paysafe_error(log_error_endpoint, __("The payment process failed. Please close this popup and try again", "paysafe-checkout"), []);
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

    return descriptionForDisplay;
};

const ExpressLabel = () => {
    const label = decodeEntities( settings.title ) || __('Paysafe Checkout', 'paysafe-checkout' )

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

const ExpressContent = ( props ) => {
    const { eventRegistration, onClick, onClose, onError } = props;
    const { onCheckoutSuccess } = eventRegistration;


    useEffect(() => {
        init_google_pay_hosted_integration(onClick, onClose, onError);

        const unsubscribe = onCheckoutSuccess( (response) => {
            return response;
        });

        return () => {
            unsubscribe();
        };
    }, [
        [],
        {},
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
        descriptionForDisplay.push(<p></p>);
    }

    descriptionForDisplay.push(wp.element.RawHTML({ children: settings.integration_hosted_form }));

    return descriptionForDisplay;
};

if (!is_hosted_integration) {
    const PaysafeGooglePayPayments = {
        name: paymentMethodId,
        paymentMethodId: paymentMethodId,
        label: get_paysafe_label(settings.title, settings.icon),
        content: <Content/>,
        edit: <Content/>,
        canMakePayment: (cartData => {
            currentCartData = cartData;

            return true;
        }),
        ariaLabel: __('Paysafe Checkout', 'paysafe-checkout' ),
        supports: {
            features: settings.supports,
        },
    };

    registerPaymentMethod(PaysafeGooglePayPayments);
} else {
    const PaysafeGooglePayExpressPayment = {
        name: paymentMethodId,
        title: 'Google Pay Express Checkout',
        description: settings.description,
        gatewayId: 'paysafe',
        label: <ExpressLabel/>,
        content: <ExpressContent/>,
        edit: <ExpressContent/>,
        canMakePayment: (cartData => {
            currentCartData = cartData;
            if (
                canExpressPayWithGooglePay &&
                cartData.billingAddress &&
                cartData.billingAddress.email !== '' &&
                cartData.billingAddress.first_name !== '' &&
                cartData.billingAddress.last_name !== '' &&
                cartData.billingAddress.address_1 !== '' &&
                cartData.billingAddress.city !== '' &&
                cartData.billingAddress.postcode !== ''
            ) {
                return !is_apple_pay_google_pay_combo_activated;
            }


            is_express_google_pay_initialized = false;
            return false;
        }),
        paymentMethodId: paymentMethodId,
        supports: {
            features: settings.supports,
        },
    };

    registerExpressPaymentMethod(PaysafeGooglePayExpressPayment);
}
