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


const paymentMethodId = "sightline";
const settings = getSetting( paymentMethodId + '_data', {} );

let currentCartData = {};

const Content = ( props ) => {
    const { eventRegistration, emitResponse } = props;
    const { onPaymentSetup, onCheckoutSuccess } = eventRegistration;

    useEffect(() => {
        const unsubscribe = onPaymentSetup( async () => {
            let sightlineConsumerIdInput = document.getElementById('sightline_consumeridInput');
            let isSightlineConsumerIdValid = sightlineConsumerIdInput && sightlineConsumerIdInput.value
                && sightlineConsumerIdInput.value.length && sightlineConsumerIdInput.value.length >= 2
                && sightlineConsumerIdInput.value.length <= 50;

            if (isSightlineConsumerIdValid) {
                return {
                    type: emitResponse.responseTypes.SUCCESS,
                };
            }

            log_paysafe_error(__("The payment process failed. Play+ (Sightline) consumer id must have between 2 and 50 characters!", "paysafe-checkout"), {});
            return {
                type: emitResponse.responseTypes.ERROR,
                message: __("The payment process failed. Play+ (Sightline) consumer id must have between 2 and 50 characters!", "paysafe-checkout"),
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

            let sightlineConsumerIdInput = document.getElementById('sightline_consumeridInput');
            let sightlineConsumerIdValue = sightlineConsumerIdInput && sightlineConsumerIdInput.value
            && sightlineConsumerIdInput.value.length && sightlineConsumerIdInput.value.length >= 2
            && sightlineConsumerIdInput.value.length <= 50 ? sightlineConsumerIdInput.value : '';

            return main_paysafe_checkout_success_handler(response, currentCartData, settings, {
                transactionSource: "WooCommerceCheckout",
                displayPaymentMethods: [
                    'sightline'
                ],
                paymentMethodDetails: {
                    sightline: {
                        accountId: settings.account_id,
                        consumerId: '' + sightlineConsumerIdValue,
                    }
                },
                shippingAddress: {
                    city: currentCartData.billingAddress.city,
                    country: currentCartData.billingAddress.country,
                    recipientName: currentCartData.billingAddress.first_name + ' ' + currentCartData.billingAddress.last_name,
                    state: currentCartData.billingAddress.state,
                    street: currentCartData.billingAddress.address_1,
                    street2: currentCartData.billingAddress.address_2,
                    zip: currentCartData.billingAddress.postcode,
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
            <label id="sightline_consumerid_label" className="paysafe-general-form-label"
                   htmlFor="sightline_consumeridInput">{__('Play+ (Sightline) Loyalty Membership Number', 'paysafe-checkout')}</label>
            <div id="sightline_consumerid" className="paysafe-input-field">
                <input type="text" className="paysafe-input-field-input" name="sightline_consumerid_input" id="sightline_consumeridInput"
                       placeholder={__('Your Play+ (Sightline) Loyalty Membership Number', 'paysafe-checkout')} min="2"
                       minLength="2" max="50" maxLength="50"/>
            </div>
            <p id="sightline_consumerid_spacer" className="paysafe-general-form-spacer">{__('Enter the Loyalty Membership Number from your Play+ (Sightline) account', 'paysafe-checkout')}</p>
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
