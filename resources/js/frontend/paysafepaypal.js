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


const paymentMethodId = "paysafepaypal";
const settings = getSetting( paymentMethodId + '_data', {} );

let currentCartData = {};
let isDefaultValueSet = false;

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

            log_paysafe_error(__("The payment process failed. You have to specify your PayPal email address!", "paysafe-checkout"), {});
            return {
                type: emitResponse.responseTypes.ERROR,
                message: __("The payment process failed. You have to specify your PayPal email address!", "paysafe-checkout"),
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

            let paysafepaypalConsumerIdInput = document.getElementById('paysafepaypal_consumeridInput');
            let paysafepaypalConsumerIdValue = paysafepaypalConsumerIdInput && paysafepaypalConsumerIdInput.value
            && paysafepaypalConsumerIdInput.value.length ? paysafepaypalConsumerIdInput.value : '';

            return main_paysafe_checkout_success_handler(response, currentCartData, settings, {
                transactionSource: "WooCommerceCheckout",
                displayPaymentMethods: [
                    'paypal'
                ],
                paymentMethodDetails: {
                    paypal: {
                        accountId: settings.account_id,
                        consumerId: paysafepaypalConsumerIdValue,
                    }
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
        <div id="paysafe-hosted-payment-form" className="paysafe-hosted-payment-form">
            <label id="paysafepaypal_consumerid_label" className="paysafe-general-form-label"
                   htmlFor="paysafepaypal_consumeridInput">{__('PayPal email address', 'paysafe-checkout')}</label>
            <div id="paysafepaypal_consumerid" className="paysafe-input-field">
                <input type="text" className="paysafe-input-field-input" name="paysafepaypal_consumerid_input" id="paysafepaypal_consumeridInput"
                       placeholder={__('Your PayPal email address', 'paysafe-checkout')} min="2"
                       minLength="2" max="50" maxLength="50"/>
            </div>
            <p id="paysafepaypal_consumerid_spacer" className="paysafe-general-form-spacer">{__('Enter the email from your PayPal account', 'paysafe-checkout')}</p>
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
