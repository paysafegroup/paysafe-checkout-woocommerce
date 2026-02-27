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
import { paysafe_sha512 } from './paysafe-encrypt';


const paymentMethodId = "pay_by_bank";
const settings = getSetting( paymentMethodId + '_data', {} );

let currentCartData = {};

const Content = ( props ) => {
    const { eventRegistration, emitResponse } = props;
    const { onPaymentSetup, onCheckoutSuccess } = eventRegistration;

    useEffect(() => {
        const unsubscribe = onPaymentSetup( async () => {
            let paybybankConsumerIdInput = document.getElementById('paybybank_consumeridInput');
            let isPayByBankConsumerIdValid = paybybankConsumerIdInput && paybybankConsumerIdInput.value
                && paybybankConsumerIdInput.value.length;

            let paybybankDOBInput = document.getElementById('paybybank_dobInput');
            let isPayByBankDOBValid = paybybankDOBInput && paybybankDOBInput.value
                && paybybankDOBInput.value.length;

            if (isPayByBankConsumerIdValid && isPayByBankDOBValid) {
                return {
                    type: emitResponse.responseTypes.SUCCESS,
                };
            }

            log_paysafe_error(__("The payment process failed. SSN and Date of Birth must be set!", "paysafe-checkout"), {});
            return {
                type: emitResponse.responseTypes.ERROR,
                message: __("The payment process failed. SSN and Date of Birth must be set!", "paysafe-checkout"),
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
            let consumer_id = settings.consumer_id_encrypted;
            if (!consumer_id) {
                consumer_id = paysafe_sha512(currentCartData.billingAddress.email);
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

            let customer_data = {
                firstName: currentCartData.billingAddress.first_name,
                lastName: currentCartData.billingAddress.last_name,
                email: currentCartData.billingAddress.email,
                identityDocuments: [{
                    type: "SOCIAL_SECURITY",
                    documentNumber: paybybankConsumerIdValue,
                }],
                dateOfBirth: {
                    year: paybybank_dob_year,
                    month: paybybank_dob_month,
                    day: paybybank_dob_day,
                }
            };
            if (currentCartData.billingAddress.phone) {
                customer_data.phone = currentCartData.billingAddress.phone;
            }

            return main_paysafe_checkout_success_handler(response, currentCartData, settings, {
                transactionSource: "WooCommerceCheckout",
                displayPaymentMethods: [
                    'pay_by_bank'
                ],
                paymentMethodDetails: {
                    payByBank: {
                        accountId: settings.account_id,
                        consumerId: consumer_id,
                    }
                },
                customer: customer_data
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
                <label id="paybybank_consumerid_label" className="paysafe-general-form-label"
                       htmlFor="paybybank_consumeridInput">{__('SSN', 'paysafe-checkout')}</label>
                <div id="paybybank_consumerid" className="paysafe-input-field">
                    <input type="text" className="paysafe-input-field-input" name="paybybank_consumerid_input" id="paybybank_consumeridInput"
                           placeholder={__('Your SSN', 'paysafe-checkout')} min="2"
                           minLength="2" />
                </div>
                <p id="paybybank_consumerid_spacer" className="paysafe-general-form-spacer">{__('Enter the consumer id from your Pay By Bank account', 'paysafe-checkout')}</p>
            </div>
            <div id="paysafe-hosted-payment-form2" className="paysafe-hosted-payment-form">
                <label id="paybybank_dob_label" className="paysafe-general-form-label"
                       htmlFor="paybybank_dobInput">{__('Date of birth', 'paysafe-checkout')}</label>
                <div id="paybybank_dob" className="paysafe-input-field">
                    <input type="date" className="paysafe-input-field-input" name="paybybank_dob_input" id="paybybank_dobInput"
                           placeholder={__('Your Date of birth', 'paysafe-checkout')} min="2"
                           minLength="2" />
                </div>
                <p id="paybybank_dob_spacer" className="paysafe-general-form-spacer">{__('Enter your Date of Birth for your Pay By Bank account', 'paysafe-checkout')}</p>
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
