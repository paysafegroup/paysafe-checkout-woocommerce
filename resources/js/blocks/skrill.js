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


const paymentMethodId = "skrill";
const settings = getSetting( paymentMethodId + '_data', {} );
const log_error_endpoint = settings.log_errors === true ? (settings.log_error_endpoint || null) : null;

let currentCartData = {};
let isDefaultValueSet = false;

const Content = ( props ) => {
    const { eventRegistration, emitResponse } = props;
    const { onPaymentSetup, onCheckoutSuccess } = eventRegistration;

    if (!isDefaultValueSet) {
        isDefaultValueSet = true;

        setTimeout(() => {
            let paysafeskrillConsumerIdInput = document.getElementById('paysafeskrill_consumeridInput');
            if (!!paysafeskrillConsumerIdInput) {
                paysafeskrillConsumerIdInput.value = settings.consumer_id;
            }
        }, 100);
    }

    useEffect(() => {
        const unsubscribe = onPaymentSetup( async () => {
            let paysafeskrillConsumerIdInput = document.getElementById('paysafeskrill_consumeridInput');
            let isPaysafeSkrillConsumerIdValid = paysafeskrillConsumerIdInput && paysafeskrillConsumerIdInput.value
                && paysafeskrillConsumerIdInput.value.length;

            if (isPaysafeSkrillConsumerIdValid) {
                return {
                    type: emitResponse.responseTypes.SUCCESS,
                };
            }

            log_paysafe_error(log_error_endpoint, __("The payment process failed. You have to specify your Skrill email address!", "paysafe-checkout"), {});
            return {
                type: emitResponse.responseTypes.ERROR,
                message: __("The payment process failed. You have to specify your Skrill email address!", "paysafe-checkout"),
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

            let paysafeskrillConsumerIdInput = document.getElementById('paysafeskrill_consumeridInput');
            let paysafeskrillConsumerIdValue = paysafeskrillConsumerIdInput && paysafeskrillConsumerIdInput.value
            && paysafeskrillConsumerIdInput.value.length ? paysafeskrillConsumerIdInput.value : '';

            return main_paysafe_checkout_success_handler(response, currentCartData, settings, {
                transactionSource: "WooCommerceCheckout",
                displayPaymentMethods: [
                    'skrill'
                ],
                paymentMethodDetails: {
                    skrill: {
                        accountId: settings.account_id,
                        consumerId: paysafeskrillConsumerIdValue,
                        emailSubject: settings.details.subject,
                        emailMessage: settings.details.message,
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
            <label id="paysafeskrill_consumerid_label" className="paysafe-general-form-label"
                   htmlFor="paysafeskrill_consumeridInput">{__('Skrill email address', 'paysafe-checkout')}</label>
            <div id="paysafeskrill_consumerid" className="paysafe-input-field">
                <input type="text" className="paysafe-input-field-input" name="paysafeskrill_consumerid_input" id="paysafeskrill_consumeridInput"
                       placeholder={__('Your Skrill email address', 'paysafe-checkout')} min="2"
                       minLength="2" max="50" maxLength="50"/>
            </div>
            <p id="paysafeskrill_consumerid_spacer" className="paysafe-general-form-spacer">{__('Enter the email from your Skrill account', 'paysafe-checkout')}</p>
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
