import { sprintf, __ } from '@wordpress/i18n';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';
import { useEffect } from '@wordpress/element'

const paymentMethodId = "paysafepaypal";

const defaultLabel = 'Paysafe Checkout';

const settings = getSetting( 'paysafepaypal_data', {} );

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

const is_hosted_integration = settings.integration_type === 'paysafe_js';

const label = decodeEntities( settings.title ) || defaultLabel;
const icons = [
    {
        id: 'paypal-icon',
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
let isDefaultValueSet = false;

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
        let error_li = document.createElement('li');
        error_li.setAttribute('id', 'paysafe-fatal-error-message');
        error_li.innerHTML = error_message;
        woo_errors.appendChild(error_li);
        window.scrollTo({
            top: 0,
            left: 0,
            behavior: "smooth",
        });
    }
}

const Content = ( props ) => {
    const { eventRegistration, emitResponse } = props;
    const { onPaymentSetup, onCheckoutSuccess } = eventRegistration;

    if (!isDefaultValueSet) {
        isDefaultValueSet = true;

        setTimeout(() => {
            let paysafepaypalConsumerIdInput = document.getElementById('paysafepaypal_consumeridInput');
            if (!!paysafepaypalConsumerIdInput) {
                paysafepaypalConsumerIdInput.value = settings.consumer_id;
            }
        }, 100);
    }

    useEffect(() => {
        const unsubscribe = onPaymentSetup( async () => {
            let paysafepaypalConsumerIdInput = document.getElementById('paysafepaypal_consumeridInput');
            let isPaysafePaypalConsumerIdValid = paysafepaypalConsumerIdInput && paysafepaypalConsumerIdInput.value
                && paysafepaypalConsumerIdInput.value.length;

            if (isPaysafePaypalConsumerIdValid) {
                return {
                    type: emitResponse.responseTypes.SUCCESS,
                };
            }

            log_paysafe_error(__("The payment process failed. You have to specify your Paypal email address!", "paysafe-checkout"), {});
            return {
                type: emitResponse.responseTypes.ERROR,
                message: __("The payment process failed. You have to specify your Paypal email address!", "paysafe-checkout"),
            };
        });

        return () => {
            unsubscribe();
        };
    }, [
        emitResponse.responseTypes.ERROR,
        emitResponse.responseTypes.SUCCESS,
        onPaymentSetup
    ]);

    useEffect(() => {
        const unsubscribe = onCheckoutSuccess( (response) => {

            const orderId = response.orderId;
            const customerSingleUseToken = response && response.processingResponse && response.processingResponse.paymentDetails && response.processingResponse.paymentDetails.single_use_token ? response.processingResponse.paymentDetails.single_use_token : '';
            const merch_ref_num = response && response.processingResponse && response.processingResponse.paymentDetails && response.processingResponse.paymentDetails.merch_ref_num ? response.processingResponse.paymentDetails.merch_ref_num : '';

            let paysafepaypalConsumerIdInput = document.getElementById('paysafepaypal_consumeridInput');
            let paysafepaypalConsumerIdValue = paysafepaypalConsumerIdInput && paysafepaypalConsumerIdInput.value
                && paysafepaypalConsumerIdInput.value.length ? paysafepaypalConsumerIdInput.value : '';

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
                        'paypal'
                    ],
                    paymentMethodDetails: {
                        paypal: {
                            accountId: settings.account_id,
                            consumerId: paysafepaypalConsumerIdValue,
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
                                        instance.showSuccessScreen(__("Your goods are now purchased. Expect them to be delivered in next 5 business days.", "paysafe-checkout"));
                                    } else {
                                        instance.showFailureScreen(__("The payment process failed. Please close this popup and try again", "paysafe-checkout"));
                                    }

                                    next_page = json.redirect_url;
                                })
                                .catch((error) => {
                                    // the BE call failed
                                    log_paysafe_error(__("The payment process failed. Please close this popup and try again", "paysafe-checkout"), []);
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

    descriptionForDisplay.push(
        <div id="paysafe-hosted-payment-form" className="paysafe-hosted-payment-form">
            <label id="paysafepaypal_consumerid_label" className="paysafe-general-form-label"
                   htmlFor="paysafepaypal_consumeridInput">{__('Paypal email address', 'paysafe-checkout')}</label>
            <div id="paysafepaypal_consumerid" className="paysafe-input-field">
                <input type="text" className="paysafe-input-field-input" name="paysafepaypal_consumerid_input" id="paysafepaypal_consumeridInput"
                       placeholder={__('Your Paypal email address', 'paysafe-checkout')} min="2"
                       minLength="2" max="50" maxLength="50"/>
            </div>
            <p id="paysafepaypal_consumerid_spacer" className="paysafe-general-form-spacer">{__('Enter the email from your Paypal account', 'paysafe-checkout')}</p>
        </div>
    );

    return descriptionForDisplay;
};

const PaysafePaypalPayments = {
    name: paymentMethodId,
    paymentMethodId: paymentMethodId,
    label: <Label/>,
    content: <Content/>,
    edit: <Content/>,
    canMakePayment: (cartData => {
        currentCartData = cartData;

        return true;
    }),
    ariaLabel: label,
    supports: {
        features: settings.supports,
    },
};

if (!is_hosted_integration) {
    registerPaymentMethod(PaysafePaypalPayments);
}
