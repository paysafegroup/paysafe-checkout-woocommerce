import { __ } from '@wordpress/i18n';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { getSetting } from '@woocommerce/settings';
import { useEffect } from '@wordpress/element'
import {
    get_paysafe_label,
    main_paysafe_checkout_success_handler,
    resolve_paysafe_payment_description,
    log_paysafe_error
} from "./paysafe-common";


const paymentMethodId = "vippreferred";
const settings = getSetting( paymentMethodId + '_data', {} );

let currentCartData = {};
let isDefaultValueSet = false;

const Content = ( props ) => {
    const { eventRegistration, emitResponse } = props;
    const { onPaymentSetup, onCheckoutSuccess } = eventRegistration;

    if (!isDefaultValueSet) {
        isDefaultValueSet = true;

        setTimeout(() => {
            let vippreferredPhonenumberInput = document.getElementById('vippreferred_phonenumberInput');
            if (!!vippreferredPhonenumberInput) {
                vippreferredPhonenumberInput.value = currentCartData && currentCartData.billingAddress
                && currentCartData.billingAddress.phone ? currentCartData.billingAddress.phone : '';
            }
        }, 100);
    }


    useEffect(() => {
        const unsubscribe = onPaymentSetup( async () => {
            let vippreferredConsumerIdInput = document.getElementById('vippreferred_consumeridInput');
            let isVipPreferredConsumerIdValid = vippreferredConsumerIdInput && vippreferredConsumerIdInput.value
                && vippreferredConsumerIdInput.value.length && vippreferredConsumerIdInput.value.length === 9;

            let vippreferredPhonenumberInput = document.getElementById('vippreferred_phonenumberInput');
            let isVipPreferredPhonenumberValid = vippreferredPhonenumberInput && vippreferredPhonenumberInput.value
                && vippreferredPhonenumberInput.value.length && vippreferredPhonenumberInput.value.length >= 9;

            if (isVipPreferredConsumerIdValid && isVipPreferredPhonenumberValid) {
                return {
                    type: emitResponse.responseTypes.SUCCESS,
                };
            }

            log_paysafe_error("The payment process validation failed. Customer`s phone number must be provided!", {});
            return {
                type: emitResponse.responseTypes.ERROR,
                message: __("The payment process validation failed. Your phone number must be provided!", "paysafe-checkout"),
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
            let vippreferredConsumerIdInput = document.getElementById('vippreferred_consumeridInput');
            let vippreferredConsumerIdValue = vippreferredConsumerIdInput && vippreferredConsumerIdInput.value
            && vippreferredConsumerIdInput.value.length && vippreferredConsumerIdInput.value.length === 9
                ? vippreferredConsumerIdInput.value : '';

            let vippreferredPhonenumberInput = document.getElementById('vippreferred_phonenumberInput');
            let vippreferredPhonenumberValue = vippreferredPhonenumberInput && vippreferredPhonenumberInput.value
            && vippreferredPhonenumberInput.value.length && vippreferredPhonenumberInput.value.length >= 9
                ? vippreferredPhonenumberInput.value : '';

            return main_paysafe_checkout_success_handler(response, currentCartData, settings, {
                transactionSource: "WooCommerce",
                displayPaymentMethods: [
                    'vippreferred'
                ],
                paymentMethodDetails: {
                    vippreferred: {
                        accountId: settings.account_id,
                        consumerId: vippreferredConsumerIdValue,
                    }
                },
                customer: {
                    firstName: currentCartData.billingAddress.first_name,
                    lastName: currentCartData.billingAddress.last_name,
                    email: currentCartData.billingAddress.email,
                    phone: vippreferredPhonenumberValue,
                },
                additional: {
                    phone: vippreferredPhonenumberValue,
                },
                settings: {
                    add_customer_when_single_use_token: true,
                    add_billing_when_single_use_token: true,
                }
            });
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

    return resolve_paysafe_payment_description(settings, (
        <div>
            <div id="paysafe-hosted-payment-form" className="paysafe-hosted-payment-form">
                <label id="vippreferred_consumerid_label" className="paysafe-general-form-label"
                       htmlFor="vippreferred_consumeridInput">{__('Your social security number', 'paysafe-checkout')}</label>
                <div id="vippreferred_consumerid" className="paysafe-input-field">
                    <input type="text" className="paysafe-input-field-input" name="vippreferred_consumerid_input" id="vippreferred_consumeridInput"
                           placeholder={__('Your social security number', 'paysafe-checkout')} min="9"
                           minLength="9" max="9" maxLength="9" />
                </div>
                <p id="vippreferred_consumerid_spacer" className="paysafe-general-form-spacer">{__('Enter your social security number', 'paysafe-checkout')}</p>
            </div>
            <div id="paysafe-hosted-payment-form" className="paysafe-hosted-payment-form">
                <label id="vippreferred_phonenumber_label" className="paysafe-general-form-label"
                       htmlFor="vippreferred_phonenumberInput">{__('Your phone number', 'paysafe-checkout')}</label>
                <div id="vippreferred_phonenumber" className="paysafe-input-field">
                    <input type="tel" className="paysafe-input-field-input" name="vippreferred_phonenumber_input" id="vippreferred_phonenumberInput"
                           placeholder={__('Your phone number', 'paysafe-checkout')} />
                </div>
                <p id="vippreferred_consumerid_spacer" className="paysafe-general-form-spacer">{__('Enter your phone number', 'paysafe-checkout')}</p>
            </div>
        </div>
    ));
};

const PaysafePaysafeCardPayments = {
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

registerPaymentMethod(PaysafePaysafeCardPayments);
