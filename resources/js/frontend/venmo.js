import { __ } from '@wordpress/i18n';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { getSetting } from '@woocommerce/settings';
import { useEffect } from '@wordpress/element'
import {
    get_paysafe_label, log_paysafe_error,
    main_paysafe_checkout_success_handler,
    resolve_paysafe_payment_description
} from "./paysafe-common";


const paymentMethodId = "venmo";
const settings = getSetting( paymentMethodId + '_data', {} );

let currentCartData = {};
let isDefaultValueSet = false;

const Content = ( props ) => {
    const { eventRegistration, emitResponse } = props;
    const { onPaymentSetup, onCheckoutSuccess } = eventRegistration;

    if (!isDefaultValueSet) {
        isDefaultValueSet = true;

        setTimeout(() => {
            let paysafevenmoConsumerIdInput = document.getElementById('paysafevenmo_consumeridInput');
            if (!!paysafevenmoConsumerIdInput) {
                paysafevenmoConsumerIdInput.value = settings.consumer_id;
            }
        }, 100);
    }

    useEffect(() => {
        const unsubscribe = onPaymentSetup( async () => {
            let paysafevenmoConsumerIdInput = document.getElementById('paysafevenmo_consumeridInput');
            let isPaysafeVenmoConsumerIdValid = paysafevenmoConsumerIdInput && paysafevenmoConsumerIdInput.value
                && paysafevenmoConsumerIdInput.value.length;

            if (isPaysafeVenmoConsumerIdValid) {
                return {
                    type: emitResponse.responseTypes.SUCCESS,
                };
            }

            log_paysafe_error(__("The payment process failed. You have to specify your Venmo email address!", "paysafe-checkout"), {});
            return {
                type: emitResponse.responseTypes.ERROR,
                message: __("The payment process failed. You have to specify your Venmo email address!", "paysafe-checkout"),
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

            let paysafevenmoConsumerIdInput = document.getElementById('paysafevenmo_consumeridInput');
            let paysafevenmoConsumerIdValue = paysafevenmoConsumerIdInput && paysafevenmoConsumerIdInput.value
            && paysafevenmoConsumerIdInput.value.length ? paysafevenmoConsumerIdInput.value : '';


            return main_paysafe_checkout_success_handler(response, currentCartData, settings, {
                transactionSource: "WooCommerceCheckout",
                displayPaymentMethods: [
                    'venmo'
                ],
                paymentMethodDetails: {
                    venmo: {
                        accountId: settings.account_id,
                        consumerId: '' + paysafevenmoConsumerIdValue,
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
            <label id="paysafevenmo_consumerid_label" className="paysafe-general-form-label"
                   htmlFor="paysafevenmo_consumeridInput">{__('Venmo email address', 'paysafe-checkout')}</label>
            <div id="paysafevenmo_consumerid" className="paysafe-input-field">
                <input type="text" className="paysafe-input-field-input" name="paysafevenmo_consumerid_input" id="paysafevenmo_consumeridInput"
                       placeholder={__('Your Venmo email address', 'paysafe-checkout')} min="2"
                       minLength="2" max="50" maxLength="50"/>
            </div>
            <p id="paysafevenmo_consumerid_spacer" className="paysafe-general-form-spacer">{__('Enter the email from your Venmo account', 'paysafe-checkout')}</p>
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
