<?php

class PaysafeSettings
{
    /**
     * Return form fields for Paysafe payment settings page
     *
     * @return array
     */
    public static function get_paysafe_settings(): array
    {
        $settings = get_option(PAYSAFE_SETTINGS_KEYWORD, []);
        $currency_code = get_woocommerce_currency();

        if ($settings === null) {
            $settings = [];
        }

        list($all_payment_methods, $settings) = self::get_all_payment_methods($settings, $currency_code);

        list($paysafe_integration_list, $paysafe_integration_default) = self::get_paysafe_integration_list();

        $options = array_merge(
            self::get_paysafe_options_part1(),
            self::get_paysafe_options_part2(
                $paysafe_integration_list,
                $paysafe_integration_default
            )
        );

        if (
            isset($settings['payment_methods'][$currency_code])
            && is_array($settings['payment_methods'][$currency_code])
            && count($settings['payment_methods'][$currency_code])
        ) {
            // Add the label for "Payments Methods"
            $options['payment_methods_' . $currency_code] = [
                'title' => __('Payment Methods', 'paysafe-checkout'),
                'type' => 'title',
                'description' =>
                    __('Select payment methods you want to enable or disable.','paysafe-checkout'),
            ];

            // set the default list of payment methods
            $options = self::set_paysafe_payment_list_order($options, $all_payment_methods, $settings, $currency_code);

            // render all payment methods and its saved values
            $options = self::set_up_paysafe_payment_list($options, $settings, $all_payment_methods, $currency_code);

            // remove account id entry for payment methods that are missing it
            foreach ($all_payment_methods as $method_code => $payment_method_name) {
                if (empty($options['account_id_' . $currency_code . '_' . $method_code] ?? [])) {
                    unset($options['account_id_' . $currency_code . '_' . $method_code]);
                }
            }
        } else {
            // Add the label for "Payments Methods" when no payment method is available
            $options['payment_methods_' . $currency_code] = [
                'title' => __('Payment Methods', 'paysafe-checkout'),
                'type' => 'title',
                'description' =>
                    sprintf(
                    /* translators: %s is the currency used */
                    __('There are no available payment methods for this account and the currently set currency for this store (%s).', 'paysafe-checkout')
                    . '<br />'
                    . __(
                        'Please change your store\'s currency or contact Paysafe Customer Service.',
                        'paysafe-checkout'
                    ),
                    $currency_code
                    )
            ];
        }


        return $options;
    }

    /**
     * @param array $settings
     * @param string $currency_code
     *
     * @return array
     */
    private static function get_all_payment_methods(array $settings, string $currency_code): array
    {
        $all_payment_methods = [
            WC_Gateway_Paysafe::PAYMENT_TYPE_CODE               =>
                __('Card payments', 'paysafe-checkout'),
        ];
        if (WC_Gateway_Paysafe_Base::ALLOW_APPLE_PAY_SUPPORT) {
            $all_payment_methods[WC_Gateway_Paysafe_Apple_Pay::PAYMENT_TYPE_CODE] =
                __('Apple Pay', 'paysafe-checkout');
        } elseif
            (isset($settings['payment_methods'][$currency_code][WC_Gateway_Paysafe_Apple_Pay::PAYMENT_TYPE_CODE])) {
            unset($settings['payment_methods'][$currency_code][WC_Gateway_Paysafe_Apple_Pay::PAYMENT_TYPE_CODE]);
        }
        if (WC_Gateway_Paysafe_Base::ALLOW_LPM_PAY_SUPPORT) {
            $all_payment_methods[WC_Gateway_Paysafe_Skrill::PAYMENT_TYPE_CODE] =
                __('Skrill', 'paysafe-checkout');
            $all_payment_methods[WC_Gateway_Paysafe_Neteller::PAYMENT_TYPE_CODE] =
                __('Neteller', 'paysafe-checkout');
            $all_payment_methods[WC_Gateway_Paysafe_Card::PAYMENT_TYPE_CODE] =
                __('PaysafeCard', 'paysafe-checkout');
        } else {
            if (isset($settings['payment_methods'][$currency_code][WC_Gateway_Paysafe_Skrill::PAYMENT_TYPE_CODE])) {
                unset($settings['payment_methods'][$currency_code][WC_Gateway_Paysafe_Skrill::PAYMENT_TYPE_CODE]);
            }
            if (isset($settings['payment_methods'][$currency_code][WC_Gateway_Paysafe_Neteller::PAYMENT_TYPE_CODE])) {
                unset($settings['payment_methods'][$currency_code][WC_Gateway_Paysafe_Neteller::PAYMENT_TYPE_CODE]);
            }
            if (isset($settings['payment_methods'][$currency_code][WC_Gateway_Paysafe_Card::PAYMENT_TYPE_CODE])) {
                unset($settings['payment_methods'][$currency_code][WC_Gateway_Paysafe_Card::PAYMENT_TYPE_CODE]);
            }
        }
        if (WC_Gateway_Paysafe_Base::ALLOW_LPM_PAYSAFECASH_SUPPORT) {
            $all_payment_methods[WC_Gateway_Paysafe_Cash::PAYMENT_TYPE_CODE] =
                __('PaysafeCash', 'paysafe-checkout');
        } elseif
            (isset($settings['payment_methods'][$currency_code][WC_Gateway_Paysafe_Cash::PAYMENT_TYPE_CODE])) {
            unset($settings['payment_methods'][$currency_code][WC_Gateway_Paysafe_Cash::PAYMENT_TYPE_CODE]);
        }

        return [
            $all_payment_methods,
            $settings
        ];
    }

    /**
     * @return array
     */
    private static function get_paysafe_integration_list(): array
    {
        $paysafe_integration_list = [
            WC_Gateway_Paysafe_Base::PAYMENT_INTEGRATION_PAYSAFE_CHECKOUT =>
                __('Paysafe Checkout', 'paysafe-checkout'),
        ];
        $paysafe_integration_default = WC_Gateway_Paysafe_Base::PAYMENT_INTEGRATION_PAYSAFE_CHECKOUT;
        if (WC_Gateway_Paysafe_Base::ALLOW_JS_CHECKOUT_SUPPORT) {
            $paysafe_integration_list[WC_Gateway_Paysafe_Base::PAYMENT_INTEGRATION_PAYSAFE_JS] =
                __('Hosted Checkout', 'paysafe-checkout');
            $paysafe_integration_default = WC_Gateway_Paysafe_Base::PAYMENT_INTEGRATION_DEFAULT;
        }

        return [
            $paysafe_integration_list,
            $paysafe_integration_default
        ];
    }

    /**
     * @return array
     */
    private static function get_paysafe_options_part1(): array
    {
        return  [
            'setup_section' => [
                'title' => __('Paysafe', 'paysafe-checkout'),
                'type' => 'title',
            ],
            'enabled' => [
                'title' => __('Enable Extension', 'paysafe-checkout'),
                'label' => __('Check to enable the extension', 'paysafe-checkout'),
                'type' => 'checkbox',
                'default' => 'no',
                'description' => __('The extension must be enabled to appear on the Checkout page',
                    'paysafe-checkout'),
            ],
            'test_mode' => [
                'title' => __('Test mode', 'paysafe-checkout'),
                'label' => __('Check to activate Test mode', 'paysafe-checkout'),
                'type' => 'checkbox',
                'default' => 'yes',
                'description' =>
                    __(
                    'If you enable test mode, the extension will connect to the Test environment.',
                    'paysafe-checkout'
                    ). ' ' .
                    __(
                        'In this case, you must use test cards provided in the Paysafe documentation',
                    'paysafe-checkout'
                    ),
            ],

            'test_environment_title' => [
                'title' => __('Test Environment Credentials', 'paysafe-checkout'),
                'type' => 'title',
                'description' => __(
                    'Configure your Test Environment credentials.',
                    'paysafe-checkout'
                ),
            ],
            'test_public_api_key' => [
                'title' => __('Public API key', 'paysafe-checkout'),
                'type' => 'text',
                'default' => '',
                'description' =>
                    __('Public API key token provided by Paysafe.', 'paysafe-checkout') . ' ' .
                    __(
                        'Check your public key in your Optic Portal, under the Developer/API Keys section.',
                        'paysafe-checkout'
                    ) . ' ' .
                    __('Public API key used for integration with Paysafe Checkout.', 'paysafe-checkout')
                    . ' ' .
                    __(
'This key is accessible to customers and visible in data traffic between Paysafe Payments and Paysafe Checkout.',
                        'paysafe-checkout'
                    ) . ' ' .
                    __(
                        'Do not use this key for connecting to the Paysafe Payment API.',
                        'paysafe-checkout'
                    ),
                'custom_attributes' => [
                    'autocomplete' => 'off',
                ],
            ],
            'test_private_api_key' => [
                'title' => __('Private API key', 'paysafe-checkout'),
                'type' => 'paysafe_private_key',
                'default' => '',
                'description' =>
                    __('Private API key token provided by Paysafe.', 'paysafe-checkout') . ' ' .
                    __(
                        'Check your private key in your Optic Portal, under the Developer/API Keys section.',
                        'paysafe-checkout'
                    ) . ' ' .
                    __('Do not share your private API key.', 'paysafe-checkout') . ' ' .
                    __(
        'It should not be used for client-server communication, only for secure backend-backend communication.',
                        'paysafe-checkout'
                    ),
                'custom_attributes' => [
                    'autocomplete' => 'off',
                ],
            ],

            'live_environment_title' => [
                'title' => __('Live Environment Credentials', 'paysafe-checkout'),
                'type' => 'title',
                'description' => __(
                    'Configure your Live Environment credentials. ',
                    'paysafe-checkout'
                ),
            ],
            'live_public_api_key' => [
                'title' => __('Public API key', 'paysafe-checkout'),
                'type' => 'text',
                'default' => '',
                'description' =>
                    __('Public API key token provided by Paysafe.', 'paysafe-checkout') . ' ' .
                    __(
                        'Check your public key in your Optic Portal, under the Developer/API Keys section.',
                        'paysafe-checkout'
                    ) . ' ' .
                    __('Public API key used for integration with Paysafe Checkout.', 'paysafe-checkout')
                    . ' ' .
                    __(
'This key is accessible to customers and visible in data traffic between Paysafe Payments and Paysafe Checkout.',
                        'paysafe-checkout'
                    ) . ' ' .
                    __(
                        'Do not use this key for connecting to the Paysafe Payment API.',
                        'paysafe-checkout'
                    ),
                'custom_attributes' => [
                    'autocomplete' => 'off',
                ],
            ],
            'live_private_api_key' => [
                'title' => __('Private API key', 'paysafe-checkout'),
                'type' => 'paysafe_private_key',
                'default' => '',
                'description' =>
                    __('Private API key token provided by Paysafe.', 'paysafe-checkout') . ' ' .
                    __(
                        'Check your private key in your Optic Portal, under the Developer/API Keys section.',
                        'paysafe-checkout'
                    ) . ' ' .
                    __('Do not share your private API key.', 'paysafe-checkout') . ' ' .
                    __(
        'It should not be used for client-server communication, only for secure backend-backend communication.',
                        'paysafe-checkout'
                    ),
                'custom_attributes' => [
                    'autocomplete' => 'off',
                ],
            ],

            'webhook_title' => [
                'title' => __('Webhook Setup', 'paysafe-checkout'),
                'type' => 'title',
                'description' =>
                    __(
                    'Set up Webhook functionality at the Optic Portal, visit the Developer → Webhook Configurations section and add a new configuration.',
                    'paysafe-checkout'
                    ) . '<br />' .
                    __(
                        'For more detailed guide, please consult the WooCommerce Merchant Installation Guide.',
                        'paysafe-checkout'
                    ),
            ],
            'webhook_url' => [
                'title' => __('Webhook URL', 'paysafe-checkout'),
                'type' => 'paysafe_info_text',
                'description' => add_query_arg(
                    [
                        'payment_gateway'   => 'paysafe',
                    ],
                    WC()->api_request_url('paysafe_webhook')
                ),
            ],
            'webhook_secret_key' => [
                'title' => __('Webhook Secret Key', 'paysafe-checkout'),
                'type' => 'text',
                'default' => '',
                'description' => __('Webhook API key from Optic', 'paysafe-checkout'),
                'custom_attributes' => [
                    'autocomplete' => 'off',
                ],
            ],
        ];
    }

    /**
     * @param array $paysafe_integration_list
     * @param string $paysafe_integration_default
     *
     * @return array
     */
    private static function get_paysafe_options_part2(
        array $paysafe_integration_list,
        string $paysafe_integration_default
    ): array
    {
        return  [
            'shopping_experience_title' => [
                'title' => __('Shopping Experience', 'paysafe-checkout'),
                'type' => 'title',
                'description' => __(
                    'Basic settings related to the shopping experience and the Paysafe Checkout',
                    'paysafe-checkout'
                ),
            ],
            'merchant_descriptor' => [
                'title' => __('Merchant name', 'paysafe-checkout'),
                'description' =>
                    __('Merchant name on Bank Statement', 'paysafe-checkout') . ' ' .
                    __('(optional)', 'paysafe-checkout'),
                'type' => 'text',
                'custom_attributes' => [
                    'autocomplete' => 'off',
                ],
            ],
            'merchant_phone' => [
                'title' => __('Merchant Phone Number', 'paysafe-checkout'),
                'description' =>
                    __('Merchant Phone on Bank Statement', 'paysafe-checkout') . ' ' .
                    __('(optional)', 'paysafe-checkout'),
                'type' => 'text',
                'custom_attributes' => [
                    'autocomplete' => 'off',
                ],
            ],
            'checkout_title'    => [
                'title' => __('Payment method title', 'paysafe-checkout'),
                'type' => 'text',
                'default' => '',
                'description' =>
                    __(
                        'Payment method title visible on the WooCommerce Checkout page',
                        'paysafe-checkout'
                    ) . ' ' .
                    __('(optional)', 'paysafe-checkout'),
                'custom_attributes' => [
                    'autocomplete' => 'off',
                ],
            ],
            'checkout_description'    => [
                'title' => __('Payment method description', 'paysafe-checkout'),
                'type' => 'text',
                'default' => __('Easy and secure payments', 'paysafe-checkout'),
                'description' =>
                    __(
                        'Payment method description visible on the WooCommerce Checkout page',
                    'paysafe-checkout'
                    ) . ' ' .
                    __('(optional)', 'paysafe-checkout'),
                'custom_attributes' => [
                    'autocomplete' => 'off',
                ],
            ],
            'checkout_locale' => [
                'title' => __('Paysafe Checkout Language', 'paysafe-checkout'),
                'type' => 'select',
                'options' => [
                    'en_US' => __('English', 'paysafe-checkout'),
                    'fr_CA' => __('French (Canadian)', 'paysafe-checkout'),
                    'es_MX' => __('Spanish (Mexican)', 'paysafe-checkout'),
                    'pt_BR' => __('Portuguese (Brazilian)', 'paysafe-checkout'),
                ],
                'default' => 'en_US',
                'description' => __('The selected language for the Paysafe Checkout',
                    'paysafe-checkout'),
                'class' => 'wc-enhanced-select',
            ],
            'payment_integration_type' => [
                'title' => __('Payment form type', 'paysafe-checkout'),
                'type' => 'select',
                'options' => $paysafe_integration_list,
                'default' => $paysafe_integration_default,
                'description' =>
                    __('Type of Payment Integration Form','paysafe-checkout') . '<br />' .
                    __(
                        'The Paysafe Checkout form supports credit cards, APMs, and saved cards.',
                        'paysafe-checkout'
                    ) . '<br />' .
                    __(
                    'The Hosted Checkout supports only card payments and is a directly embedded payment method within the checkout page.',
                    'paysafe-checkout'
                    ),
                'class' => 'wc-enhanced-select',
            ],
            'payment_action' => [
                'title' => __('Payment action', 'paysafe-checkout'),
                'type' => 'select',
                'options' => [
                    WC_Gateway_Paysafe_Base::PAYMENT_SETTLE_PAYMENT =>
                        __('Capture payment immediately after authorization', 'paysafe-checkout'),
                    WC_Gateway_Paysafe_Base::PAYMENT_AUTHORIZATION_ONLY =>
                        __('Authorization only - manual capture required', 'paysafe-checkout'),
                ],
                'default' => WC_Gateway_Paysafe_Base::PAYMENT_SETTLE_PAYMENT,
                'description' =>
                    __('Select the default payment action', 'paysafe-checkout') . '<br />' .
                    '<b>' . __('Important note:', 'paysafe-checkout') . '</b> ' .
                    __(
                        'Manual capture will only be available for card payments.',
                        'paysafe-checkout'
                    ) . ' ' .
                    __(
    'For payments made via APMs/LPMs, the capture will still be automatic regardless of your selected setting.',
                        'paysafe-checkout'
                    ) . '<br />' .
                    __(
            'If you want to allow only payments with manual capture, please disable all other payment methods.',
                        'paysafe-checkout'
                    ),
                'class' => 'wc-enhanced-select',
            ],

            'advanced_section' => [
                'title' => __('Advanced', 'paysafe-checkout'),
                'type' => 'title',
            ],

            'debug_log_enabled' => [
                'title' => __('Debug logging', 'paysafe-checkout'),
                'label' => __('Check to enable debugging', 'paysafe-checkout'),
                'type' => 'checkbox',
                'default' => 'no',
                'description' => __('Enable this option to log data. This should not be used in production.',
                    'paysafe-checkout'),
            ],

            'mask_user_data' => [
                'title' => __('Mask sensitive user data in logs', 'paysafe-checkout'),
                'label' => __('Check to enable user data masking', 'paysafe-checkout'),
                'type' => 'checkbox',
                'default' => 'true',
                'description' => __('Enable this option to mask sensitive data in log files',
                    'paysafe-checkout'),
                'id' => 'mask_user_data',
            ],
        ];
    }

    /**
     * @param array $options
     * @param array $all_payment_methods
     * @param array $settings
     * @param string $currency_code
     *
     * @return array
     */
    private static function set_paysafe_payment_list_order(
        array $options,
        array $all_payment_methods,
        array $settings,
        string $currency_code
    ): array
    {
        foreach ($all_payment_methods as $method_code => $payment_method_name) {
            // Add the DEFAULT payment method option
            $options['payment_method_' . $currency_code . '_' . $method_code] = [
                'title' => $payment_method_name,
                'label' => __('Check to enable the payment method', 'paysafe-checkout'),
                'type' => 'checkbox',
                'disabled' => true,
                'default' => 'no',
                'description' => __('Enable this payment method in your merchant dashboard',
                    'paysafe-checkout'),
            ];

            if (isset($settings['payment_methods'][$currency_code][$method_code])) {
                // account id placeholder
                $options['account_id_' . $currency_code . '_' . $method_code] = [];
            }

            if ($method_code === WC_Gateway_Paysafe::PAYMENT_TYPE_CODE
                && WC_Gateway_Paysafe_Base::ALLOW_TOKENIZATION_SUPPORT) {
                // save tokens placeholder
                $options['save_tokens_' . $currency_code . '_' . $method_code] = [];
            }
        }

        return $options;
    }

    /**
     * @param array $options
     * @param array $settings
     * @param array $all_payment_methods
     * @param string $currency_code
     *
     * @return array
     */
    private static function set_up_paysafe_payment_list(
        array $options,
        array $settings,
        array $all_payment_methods,
        string $currency_code
    ): array
    {
        if (!isset($settings['payment_methods'][$currency_code])) {
            return $options;
        }
        
        foreach ($settings['payment_methods'][$currency_code] as $method_code => $method_data) {
            if ($method_data['available']) {
                $account_ids = [];
                foreach ($method_data['accounts'] ?? [] as $account_data) {
                    $account_ids[$account_data['account_id']] = $account_data['account_id'];
                }

                // Add the payment method option
                $options['payment_method_' . $currency_code . '_' . $method_code] = [
                    'title' => $all_payment_methods[$method_code] ?? $method_data['name'],
                    'label' => __('Check to enable the payment method', 'paysafe-checkout'),
                    'type' => 'checkbox',
                    'disabled' => false,
                    'default' => WC_Gateway_Paysafe_Base::ENABLE_PAYMENT_METHOD_DEFAULT_VALUE,
                    'description' => '',
                ];

                if (count($account_ids) > 1) {
                    // Add the account ID dropdown
                    $options['account_id_' . $currency_code . '_' . $method_code] = [
                        'title' => __('Account ID (FMA)', 'paysafe-checkout'),
                        'type' => 'paysafe_select',
                        'options' => $account_ids,
                        'description' => __('Select the account ID (FMA) for this payment method',
                            'paysafe-checkout'),
                    ];
                } elseif (count($account_ids) === 1) {
                    $options['account_id_' . $currency_code . '_' . $method_code] = [
                        'title' => __('Account ID (FMA)', 'paysafe-checkout'),
                        'type' => 'paysafe_account_text',
                        'account_id' => array_pop($account_ids),
                        'description' => '',
                    ];
                } else {
                    unset($options['account_id_' . $currency_code . '_' . $method_code]);
                }

                if ($method_code === WC_Gateway_Paysafe::PAYMENT_TYPE_CODE
                    && WC_Gateway_Paysafe_Base::ALLOW_TOKENIZATION_SUPPORT) {
                    // Add the save card tokens option
                    $options['save_tokens_' . $currency_code . '_' . $method_code] = [
                        'title' => __('Save tokens', 'paysafe-checkout'),
                        'label' => __('Check to enable saving tokens', 'paysafe-checkout'),
                        'type' => 'checkbox',
                        'default' => WC_Gateway_Paysafe_Base::SAVE_TOKEN_DEFAULT_VALUE,
                        'description' =>
                            __(
                            'If customer adds this payment method, save token to My account -> Payment methods',
                            'paysafe-checkout'
                            ),
                    ];
                }
            }
        }

        return $options;
    }
}
