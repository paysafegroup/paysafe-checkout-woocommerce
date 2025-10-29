<?php

class PaysafeSettings
{
	/**
	 * Return form fields for Paysafe payment settings page
	 *
	 * @param array|null $settings
	 *
	 * @return array
	 */
    public static function get_paysafe_settings(array $settings = null): array
    {
        $settings = $settings ?? get_option(PAYSAFE_SETTINGS_KEYWORD, []);
        $currency_code = get_woocommerce_currency();

        if ($settings === null) {
            $settings = [];
        }

        list($all_payment_methods, $settings) = self::get_all_payment_methods($settings, $currency_code);

        $options = array_merge(
            self::get_paysafe_options_part1(),
            self::get_paysafe_options_part2()
        );

        // remove the subscriptions section if the plugin is not present
        if (!self::is_woocommerce_subscriptions_active()) {
            unset($options['subscriptions_title']);
            unset($options['subscriptions_enabled']);
            unset($options['subscriptions_capture']);
        } else {
            unset($options['subscriptions_title_missing']);
        }

        // Add the label for "Payments Methods"
        $options['payment_methods_' . $currency_code] = [
            'title' => __('Payment Methods', 'paysafe-checkout'),
            'type' => 'title',
            'description' =>
                __('Select payment methods you want to enable or disable.','paysafe-checkout'),
        ];

        // generate both env sets (sandbox & live)
        $options = self::build_payment_fields_for_env(
	        $options, $settings, $all_payment_methods, $currency_code,
	        'sandbox'
        );

        // generate both env sets (sandbox & live)
        $options = self::build_payment_fields_for_env(
            $options, $settings, $all_payment_methods, $currency_code,
            'live'
        );

        // Remove account_id rows that truly have no options (for both envs)
        foreach ($all_payment_methods as $method_code => $name) {
	        foreach (['sandbox','live'] as $env) {
		        $k = 'account_id_' . $currency_code . '_' . $method_code . self::env_suffix($env);
		        if (empty($options[$k]['options'] ?? [])) {
			        unset($options[$k]);
		        }
	        }
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
        $all_payment_methods = [];
        $all_payment_methods[WC_Gateway_Paysafe::PAYMENT_TYPE_CODE] =
            __('Card payments', 'paysafe-checkout');
        $all_payment_methods[WC_Gateway_Paysafe_Apple_Pay::PAYMENT_TYPE_CODE] =
            __('Apple Pay', 'paysafe-checkout');
        $all_payment_methods[WC_Gateway_Paysafe_Google_Pay::PAYMENT_TYPE_CODE] =
            __('Google Pay', 'paysafe-checkout');
        $all_payment_methods[WC_Gateway_Paysafe_Skrill::PAYMENT_TYPE_CODE] =
            __('Skrill', 'paysafe-checkout');
        $all_payment_methods[WC_Gateway_Paysafe_Neteller::PAYMENT_TYPE_CODE] =
            __('Neteller', 'paysafe-checkout');
        $all_payment_methods[WC_Gateway_Paysafe_Card::PAYMENT_TYPE_CODE] =
            __('PaysafeCard', 'paysafe-checkout');
        $all_payment_methods[WC_Gateway_Paysafe_Cash::PAYMENT_TYPE_CODE] =
            __('PaysafeCash', 'paysafe-checkout');

		if (WC_Gateway_Paysafe_Base::ALLOW_LPM_PAYPAL) {
			$all_payment_methods[WC_Gateway_Paysafe_Paypal::PAYMENT_TYPE_CODE] =
				__('Paypal', 'paysafe-checkout');
		}

		if (WC_Gateway_Paysafe_Base::ALLOW_LPM_SIGHTLINE) {
			$all_payment_methods[WC_Gateway_Paysafe_Sightline::PAYMENT_TYPE_CODE] =
				__('Sightline', 'paysafe-checkout');
		}

		if (WC_Gateway_Paysafe_Base::ALLOW_LPM_VIPPREFERRED) {
			$all_payment_methods[WC_Gateway_Paysafe_Vippreferred::PAYMENT_TYPE_CODE] =
				__('Vippreferred', 'paysafe-checkout');
		}

		if (WC_Gateway_Paysafe_Base::ALLOW_LPM_EFT) {
			$all_payment_methods[WC_Gateway_Paysafe_Eft::PAYMENT_TYPE_CODE] =
				__('EFT', 'paysafe-checkout');
		}

		if (WC_Gateway_Paysafe_Base::ALLOW_LPM_ACH) {
			$all_payment_methods[WC_Gateway_Paysafe_Ach::PAYMENT_TYPE_CODE] =
				__('ACH', 'paysafe-checkout');
		}

		if (WC_Gateway_Paysafe_Base::ALLOW_LPM_PAY_BY_BANK) {
			$all_payment_methods[WC_Gateway_Paysafe_Paybybank::PAYMENT_TYPE_CODE] =
				__('Paybybank', 'paysafe-checkout');
		}

		if (WC_Gateway_Paysafe_Base::ALLOW_LPM_VENMO) {
			$all_payment_methods[WC_Gateway_Paysafe_Venmo::PAYMENT_TYPE_CODE] =
				__('Venmo', 'paysafe-checkout');
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
            WC_Gateway_Paysafe_Base::PAYMENT_INTEGRATION_PAYSAFE_JS =>
                __('Hosted Checkout', 'paysafe-checkout')
        ];
        $paysafe_integration_default = WC_Gateway_Paysafe_Base::PAYMENT_INTEGRATION_DEFAULT;

        return [
            $paysafe_integration_list,
            $paysafe_integration_default
        ];
    }

    /**
     * @return array
     */
    private static function get_paysafe_ag_pay_integration_list(): array
    {
        $ag_pay_integration_list = [
            WC_Gateway_Paysafe_Base::PAYMENT_INTEGRATION_PAYSAFE_CHECKOUT =>
                __('Paysafe Checkout', 'paysafe-checkout'),
	        WC_Gateway_Paysafe_Base::PAYMENT_INTEGRATION_PAYSAFE_JS =>
		        __('Express Checkout', 'paysafe-checkout')
        ];
        $ag_pay_integration_default = WC_Gateway_Paysafe_Base::PAYMENT_INTEGRATION_DEFAULT;

        return [
            $ag_pay_integration_list,
            $ag_pay_integration_default
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
                'title' => __('Enable Paysafe payments', 'paysafe-checkout'),
                'label' => __('When enabled, payment methods powered by Paysafe will appear on checkout', 'paysafe-checkout'),
                'type' => 'checkbox',
                'default' => 'no',
                'description' => __('The extension must be enabled to appear on the Checkout page',
                    'paysafe-checkout'),
                'group' => 'general',
            ],
            'test_mode' => [
                'title' => __('Enable Sandbox mode', 'paysafe-checkout'),
                'label' => __('When enabled, only test transactions are possible', 'paysafe-checkout'),
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
                'group' => 'general'
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
                'description' => __('Public API key token provided by Paysafe.', 'paysafe-checkout') . ' ' .
                    __(
                        'Check your public key in your Optic Portal, under the Developer/API Keys section.',
                        'paysafe-checkout'
                    ) . ' ' .
                    __(
                        'Public API key used for integration with Paysafe Checkout.',
                        'paysafe-checkout'
                    ) . ' ' . __(
                        'This key is accessible to customers and visible in data traffic between Paysafe Payments and Paysafe Checkout.',
                        'paysafe-checkout'
                    )
                    . ' ' . __(
                        'Do not use this key for connecting to the Paysafe Payment API.',
                        'paysafe-checkout'
                    ),
                'custom_attributes' => [
                    'autocomplete' => 'off',
                ],
                'group' => 'account'
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
                    ),
                'custom_attributes' => [
                    'autocomplete' => 'off',
                ],
                'group' => 'account'
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
                'description' => __('Public API key token provided by Paysafe.', 'paysafe-checkout') . ' ' .
                    __(
                        'Check your public key in your Optic Portal, under the Developer/API Keys section.',
                        'paysafe-checkout'
                    ) . ' ' .
                    __(
                        'Public API key used for integration with Paysafe Checkout.',
                        'paysafe-checkout'
                    ) . ' ' . __(
                        'This key is accessible to customers and visible in data traffic between Paysafe Payments and Paysafe Checkout.',
                        'paysafe-checkout'
                    )
                    . ' ' . __(
                        'Do not use this key for connecting to the Paysafe Payment API.',
                        'paysafe-checkout'
                    ),
                'custom_attributes' => [
                    'autocomplete' => 'off',
                ],
                'group' => 'account'
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
                    ),
                'custom_attributes' => [
                    'autocomplete' => 'off',
                ],
                'group' => 'account'
            ],

            'subscriptions_title_missing' => [
                'title' => __('Woocommerce Subscriptions', 'paysafe-checkout'),
                'type' => 'title',
                'description' =>
                    __('The WooCommerce Subscriptions plugin is missing.','paysafe-checkout') . ' <br />' .
                    __('Please install the plugin to enable support for subscription based payments.', 'paysafe-checkout'),
                'group' => 'shopping_features'
            ],
            'subscriptions_title' => [
                'title' => __('Woocommerce Subscriptions', 'paysafe-checkout'),
                'type' => 'title',
                'description' =>
                    __(
                        'Enable or disable Woocommerce subscriptions support',
                        'paysafe-checkout'
                    ),
            ],
            'subscriptions_enabled' => [
                'title' => __('WooCommerce Subscription support', 'paysafe-checkout'),
                'label' => __('Check to enable subscription support', 'paysafe-checkout'),
                'type' => 'checkbox',
                'default' => 'no',
                'description' => __('Woocommerce Subscription plugin must be present', 'paysafe-checkout'),
                'group' => 'shopping_features'
            ],
            'subscriptions_capture' => [
                'title' => __('Automatic capture', 'paysafe-checkout'),
                'label' => __('Funds will be captured automatically even if Payment Action is set to Authorization Only', 'paysafe-checkout'),
                'type' => 'checkbox',
                'default' => 'yes',
                'description' => __('Funds will be captured automatically even if Payment Action is set to Authorization Only', 'paysafe-checkout'),
                'group' => 'shopping_features'
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
            ]
        ];
    }

    /**
     * @return array
     */
    private static function get_paysafe_options_part2(): array
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
                'group' => 'shopping_experience'
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
                'group' => 'shopping_experience'
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
                'group' => 'shopping_experience'
            ],
            'checkout_description'    => [
                'title' => __('Payment method description', 'paysafe-checkout'),
                'type' => 'text',
                'default' => '',
                'description' =>
                    __(
                        'Payment method description visible on the WooCommerce Checkout page',
                        'paysafe-checkout'
                    ) . ' ' .
                    __('(optional)', 'paysafe-checkout'),
                'custom_attributes' => [
                    'autocomplete' => 'off',
                ],
                'group' => 'shopping_experience'
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
                'group' => 'shopping_experience'
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
                'group' => 'advanced'
            ],

            'mask_user_data' => [
                'title' => __('Mask sensitive user data in logs', 'paysafe-checkout'),
                'label' => __('Check to enable user data masking', 'paysafe-checkout'),
                'type' => 'checkbox',
                'default' => 'true',
                'description' => __('Enable this option to mask sensitive data in log files',
                    'paysafe-checkout'),
                'id' => 'mask_user_data',
                'group' => 'advanced'
            ],
        ];
    }

	private static function env_suffix(string $env): string {
		return $env === 'live' ? '_live' : '_sandbox';
	}



    /**
     * Return translated description text for a given payment method code.
     *
     * @param string $code  Lowercased slug, e.g. card, applepay, googlepay, skrill, neteller, paysafecard, paysafecash
     * @return string
     */
    private static function get_pm_description( string $code ): string {
        switch ( strtolower( $code ) ) {
            case 'card':
                return __(
                    'Accept fully secure PCI DSS-compliant card payments from all major card schemes. Supports tokenization and repeat billing. A core payment method that ensures reliability, trust, and broad customer acceptance.',
                    'paysafe-checkout'
                );

            case 'applepay':
                return __(
                    'Offer seamless and secure payments for Apple users with Face ID/Touch ID. Accelerates checkout, boosts mobile conversion, and meets the high UX expectations of iOS customers.',
                    'paysafe-checkout'
                );

            case 'googlepay':
                return __(
                    'Enable fast, secure payments for Android and Chrome users using stored cards. Improves mobile checkout speed, reduces friction, and increases completed transactions.',
                    'paysafe-checkout'
                );

            case 'skrill':
                return __(
                    'Tap into a global base of digital wallet users with multi-currency support. Skrill is ideal for digital-savvy and international customers seeking secure and fast payments.',
                    'paysafe-checkout'
                );

            case 'neteller':
                return __(
                    'Attract a loyal eWallet user base and accelerate customer acquisition in emerging global markets — including high-risk regions. NETELLER enables secure, fast, and convenient payments. All funds received via NETELLER are 100% secure and fully indemnified, providing peace of mind for both merchants and customers.',
                    'paysafe-checkout'
                );

            case 'paysafecard':
                return __(
                    'PaysafeCard is a prepaid, cash-based payment method that enables customers to make online purchases without the need for a bank account or traditional payment instrument.
It enhances privacy by minimizing the amount of personal information shared online, as payments are completed using a secure 16-digit code purchased with cash at physical sales locations.',
                    'paysafe-checkout'
                );

            case 'paysafecash':
                return __(
                    'Let customers pay online by generating a barcode and completing payment in cash at retail locations. Ideal for the unbanked segment—no chargebacks, higher market reach.',
                    'paysafe-checkout'
                );

            case 'eft':
                return __(
                    'EFT enables secure electronic transfers directly between bank accounts, allowing merchants to receive or send funds without using cards or cash.',
                    'paysafe-checkout'
                );

            case 'ach':
                return __(
                    'ACH allows seamless electronic bank-to-bank transfers within the U.S., enabling merchants to process payments and withdrawals securely.',
                    'paysafe-checkout'
                );

            case 'paypal':
                return __(
                    'Enable customers to make quick, secure online transactions using their PayPal accounts without sharing financial details.',
                    'paysafe-checkout'
                );

            case 'sightline':
                return __(
                    'Sightline/Play+ provides seamless, secure cashless transactions for gaming and sports betting, connecting digital wallets directly to player\'s accounts.',
                    'paysafe-checkout'
                );

            case 'vippreferred':
                return __(
                    'Allow customers to make fast, secure deposits and withdrawals directly from their linked bank accounts using a trusted ACH network.',
                    'paysafe-checkout'
                );

            case 'pay_by_bank':
                return __(
                    'Let customers to make instant, secure online payments directly from their bank accounts.',
                    'paysafe-checkout'
                );

            case 'venmo':
                return __(
                    'Enable customers to make fast, secure mobile payments directly from their Venmo accounts for seamless online checkout.',
                    'paysafe-checkout'
                );
        }

        return '';
    }

    private static function build_payment_fields_for_env(
		array $options,
		array $settings,
		array $all_payment_methods,
		string $currency_code,
		string $env // 'sandbox' | 'live'
	): array {
		$sfx = self::env_suffix($env);

		// 1) Default rows for all methods (disabled until we know they’re available for this env)
		$allowed_payment_methods = [
			WC_Gateway_Paysafe::PAYMENT_TYPE_CODE               => WC_Gateway_Paysafe::class,
			WC_Gateway_Paysafe_Apple_Pay::PAYMENT_TYPE_CODE     => WC_Gateway_Paysafe_Apple_Pay::class,
			WC_Gateway_Paysafe_Google_Pay::PAYMENT_TYPE_CODE    => WC_Gateway_Paysafe_Google_Pay::class,
			WC_Gateway_Paysafe_Skrill::PAYMENT_TYPE_CODE        => WC_Gateway_Paysafe_Skrill::class,
			WC_Gateway_Paysafe_Neteller::PAYMENT_TYPE_CODE      => WC_Gateway_Paysafe_Neteller::class,
			WC_Gateway_Paysafe_Cash::PAYMENT_TYPE_CODE          => WC_Gateway_Paysafe_Cash::class,
			WC_Gateway_Paysafe_Card::PAYMENT_TYPE_CODE          => WC_Gateway_Paysafe_Card::class,
		];

		if (WC_Gateway_Paysafe_Base::ALLOW_LPM_PAYPAL) {
			$allowed_payment_methods[WC_Gateway_Paysafe_Paypal::PAYMENT_TYPE_CODE] = WC_Gateway_Paysafe_Paypal::class;
		}

	    if (WC_Gateway_Paysafe_Base::ALLOW_LPM_SIGHTLINE) {
			$allowed_payment_methods[WC_Gateway_Paysafe_Sightline::PAYMENT_TYPE_CODE] = WC_Gateway_Paysafe_Sightline::class;
	    }

	    if (WC_Gateway_Paysafe_Base::ALLOW_LPM_VIPPREFERRED) {
			$allowed_payment_methods[WC_Gateway_Paysafe_Vippreferred::PAYMENT_TYPE_CODE] = WC_Gateway_Paysafe_Vippreferred::class;
	    }

	    if (WC_Gateway_Paysafe_Base::ALLOW_LPM_EFT) {
			$allowed_payment_methods[WC_Gateway_Paysafe_Eft::PAYMENT_TYPE_CODE] = WC_Gateway_Paysafe_Eft::class;
	    }

	    if (WC_Gateway_Paysafe_Base::ALLOW_LPM_ACH) {
			$allowed_payment_methods[WC_Gateway_Paysafe_Ach::PAYMENT_TYPE_CODE] = WC_Gateway_Paysafe_Ach::class;
	    }

	    if (WC_Gateway_Paysafe_Base::ALLOW_LPM_PAY_BY_BANK) {
			$allowed_payment_methods[WC_Gateway_Paysafe_Paybybank::PAYMENT_TYPE_CODE] = WC_Gateway_Paysafe_Paybybank::class;
	    }

	    if (WC_Gateway_Paysafe_Base::ALLOW_LPM_VENMO) {
			$allowed_payment_methods[WC_Gateway_Paysafe_Venmo::PAYMENT_TYPE_CODE] = WC_Gateway_Paysafe_Venmo::class;
	    }

		foreach ($all_payment_methods as $method_code => $payment_method_name) {

			$custom_attributes = [];
			if (isset($allowed_payment_methods[$method_code])) {
				$gateway = $allowed_payment_methods[$method_code];
				$custom_attributes['icon'] = $gateway::get_gateway_icon();
			} else {
                continue;
            }

			$options['payment_method_' . $currency_code . '_' . $method_code . $sfx] = [
				'title'             => $payment_method_name,
				'label'             => __('Check to enable the payment method', 'paysafe-checkout'),
				'type'              => 'checkbox',
				'disabled'          => true,
				'default'           => 'no',
				'description'       => self::get_pm_description($method_code),
				'group'             => 'payment_methods',
				'custom_attributes' => $custom_attributes,
			];

			if ($method_code === WC_Gateway_Paysafe::PAYMENT_TYPE_CODE) {
				$options['payment_integration_type' . $sfx] = [];
				$options['payment_action' . $sfx]           = [];

				// save tokens placeholder
				$options[ 'save_tokens_' . $method_code . $sfx ] = [];
				$options[ 'cvv_tokens_' . $method_code . $sfx ]  = [];
			}
			if (WC_Gateway_Paysafe_Base::ALLOW_EXPRESS_PAYMENT_APPLE_PAY && $method_code === WC_Gateway_Paysafe_Apple_Pay::PAYMENT_TYPE_CODE) {
				$options['apple_pay_integration_type' . $sfx] = [];
			}
			if (WC_Gateway_Paysafe_Base::ALLOW_EXPRESS_PAYMENT_GOOGLE_PAY && $method_code === WC_Gateway_Paysafe_Google_Pay::PAYMENT_TYPE_CODE) {
				$options['google_pay_integration_type' . $sfx] = [];
			}
		}

		// 2) If we have a methods tree for this env, enable rows & add account selects
		$envTreeKey = 'payment_methods_' . $env; // <- NEW
		$apple_pay_enabled = $google_pay_enabled = false;
		if (isset($settings[$envTreeKey][$currency_code])) {
			foreach ($settings[$envTreeKey][$currency_code] as $method_code => $method_data) {
				if (empty($method_data['available'])) {
					continue;
				}

                if (!isset($allowed_payment_methods[$method_code])) {
                    continue;
                }

                // Overwrite to enable
				$pmKey = 'payment_method_' . $currency_code . '_' . $method_code . $sfx;
				if (!isset($options[$pmKey])) {
					// if "allow list" filtered some out previously
					$options[$pmKey] = [
						'title'       => $all_payment_methods[$method_code] ?? ($method_data['name'] ?? $method_code),
						'label'       => __('Check to enable the payment method', 'paysafe-checkout'),
						'type'        => 'checkbox',
						'group'       => 'payment_methods',
					];
				}
				$options[$pmKey]['disabled']    = false;
				$options[$pmKey]['default']     = WC_Gateway_Paysafe_Base::ENABLE_PAYMENT_METHOD_DEFAULT_VALUE;

                // Make card payments enabled by default
                if ($method_code === WC_Gateway_Paysafe::PAYMENT_TYPE_CODE) {
                    $options[$pmKey]['default'] = WC_Gateway_Paysafe::ENABLE_PAYMENT_METHOD_DEFAULT_VALUE;
                }
//              todo check with Zsolt what was this for
//				if (!in_array($method_code, [
//					WC_Gateway_Paysafe::PAYMENT_TYPE_CODE,
//					WC_Gateway_Paysafe_Apple_Pay::PAYMENT_TYPE_CODE,
//				])) {
//					$options[$pmKey]['description'] = ($method_code !== WC_Gateway_Paysafe::PAYMENT_TYPE_CODE)
//						? __('This payment method is only supported through the Paysafe Checkout form', 'paysafe-checkout')
//						: '';
//				}

				// Accounts
				$account_ids = [];
				foreach (($method_data['accounts'] ?? []) as $k => $acc) {
					$id = isset($acc['account_id']) ? (string)$acc['account_id'] : (string)$k;
					$account_ids[$id] = $id;
				}

				$accKey = 'account_id_' . $currency_code . '_' . $method_code . $sfx;
				if (!empty($account_ids)) {
					$first = array_key_first($account_ids);
					$options[$accKey] = [
						'title'       => __('Account ID (FMA)', 'paysafe-checkout'),
						'type'        => 'paysafe_select',
						'options'     => $account_ids,
						'description' => __('Select the account ID (FMA) for this payment method', 'paysafe-checkout'),
						'default'     => $first,
						'group'       => 'payment_methods',
					];
				} else {
					unset($options[$accKey]);
				}

				if ($method_code === WC_Gateway_Paysafe::PAYMENT_TYPE_CODE) {
					list( $paysafe_integration_list, $paysafe_integration_default ) = self::get_paysafe_integration_list();

					// 3) Modal fields for CARD group (per env)
					$options[ 'payment_integration_type' . $sfx ] = [
						'title'       => __( 'Payment form type', 'paysafe-checkout' ),
						'type'        => 'select',
						'options'     => $paysafe_integration_list,
						'default'     => $paysafe_integration_default,
						'description' =>
							__( 'Paysafe Checkout is a redirect-based method that opens in a modal window.', 'paysafe-checkout' ) .
							'<br />' .
							__( 'Hosted Checkout is an embedded integration where the payment form appears directly within the checkout page.', 'paysafe-checkout' ),
						'class'       => 'wc-enhanced-select',
						'group'       => 'card',
					];

					$options[ 'payment_action' . $sfx ] = [
						'title'       => __( 'Payment action', 'paysafe-checkout' ),
						'type'        => 'select',
						'options'     => [
							WC_Gateway_Paysafe_Base::PAYMENT_AUTHORIZATION_ONLY => __( 'Authorize Only (Manual Capture)', 'paysafe-checkout' ),
							WC_Gateway_Paysafe_Base::PAYMENT_SETTLE_PAYMENT     => __( 'Authorize and Capture (Automatic Settlement)', 'paysafe-checkout' ),
						],
						'default'     => WC_Gateway_Paysafe_Base::PAYMENT_SETTLE_PAYMENT,
						'description' =>
							__( 'Authorize Only (Manual Capture)', 'paysafe-checkout' ) . ': ' .
							__( 'The payment is authorized, but funds are not captured until you manually approve it — usually by changing the order status to "Processing."', 'paysafe-checkout' ) .
							'<br />' .
							__( 'Authorize and Capture (Automatic Settlement)', 'paysafe-checkout' ) . ': ' .
							__( 'The payment is authorized and captured immediately in a single step.', 'paysafe-checkout' ) .
							'<br />' .
							__( 'Note', 'paysafe-checkout' ) . ': ' .
							__( 'This setting determines the default behavior for all transactions. If you choose manual capture, you’ll need to take action to complete each payment.', 'paysafe-checkout' ) .
							'<br />',
						'class'       => 'wc-enhanced-select',
						'group'       => 'card',
					];

					$options[ 'save_tokens_' . WC_Gateway_Paysafe::PAYMENT_TYPE_CODE . $sfx ] = [
						'title'       => __( 'Saved cards', 'paysafe-checkout' ),
						'label'       => __( 'Enable saved cards', 'paysafe-checkout' ),
						'type'        => 'checkbox',
						'default'     => WC_Gateway_Paysafe_Base::SAVE_TOKEN_DEFAULT_VALUE,
						'description' => __( 'If customer adds this payment method, save token to My account -> Payment methods', 'paysafe-checkout' ),
						'group'       => 'card',
					];

					$options[ 'cvv_tokens_' . WC_Gateway_Paysafe::PAYMENT_TYPE_CODE . $sfx ] = [
						'title'       => __( 'CVV Check', 'paysafe-checkout' ),
						'label'       => __( 'Require CVV for Saved Cards', 'paysafe-checkout' ),
						'type'        => 'checkbox',
						'default'     => WC_Gateway_Paysafe_Base::SAVE_TOKEN_DEFAULT_VALUE,
						'description' =>
							__( 'Choose whether customers are required to enter their CVV when paying with a saved card.', 'paysafe-checkout' ) .
							'<br />' . __( 'Enabling this setting increases transaction security and may reduce fraud risk.', 'paysafe-checkout' ) .
							'<br />' . __( 'Disabling it provides a smoother, frictionless checkout experience but may lower the level of cardholder verification.', 'paysafe-checkout' ) .
							'<br />' . __( 'Default: Enabled (CVV required).', 'paysafe-checkout' ),
						'group'       => 'card',
					];
				}

				if ($method_code === WC_Gateway_Paysafe_Apple_Pay::PAYMENT_TYPE_CODE) {
					if (WC_Gateway_Paysafe_Base::ALLOW_EXPRESS_PAYMENT_APPLE_PAY) {
						list($apple_pay_integration_list, $apple_pay_integration_default) = self::get_paysafe_ag_pay_integration_list();

						$options['apple_pay_integration_type' . $sfx] = [
							'title' => __('Integration type', 'paysafe-checkout'),
							'type' => 'select',
							'options' => $apple_pay_integration_list,
							'default' => $apple_pay_integration_default,
							'description' =>
								__(
									'Paysafe Checkout is a redirect-based method that opens in a modal window.',
									'paysafe-checkout'
								) . '<br />' .
								__(
									'Express Checkout is an embedded integration where the Apple Pay button appears directly on top of the checkout page.',
									'paysafe-checkout'
								),
							'class' => 'wc-enhanced-select',
							'group' => 'applepay',
						];

						$apple_pay_enabled = true;
					} else {
						unset($options['apple_pay_integration_type' . $sfx]);
					}
				}

				if ($method_code === WC_Gateway_Paysafe_Google_Pay::PAYMENT_TYPE_CODE) {
					if (WC_Gateway_Paysafe_Base::ALLOW_EXPRESS_PAYMENT_GOOGLE_PAY) {
						list($google_pay_integration_list, $google_pay_integration_default) = self::get_paysafe_ag_pay_integration_list();

						$options['google_pay_integration_type' . $sfx] = [
							'title' => __('Integration type', 'paysafe-checkout'),
							'type' => 'select',
							'options' => $google_pay_integration_list,
							'default' => $google_pay_integration_default,
							'description' =>
								__(
									'Paysafe Checkout is a redirect-based method that opens in a modal window.',
									'paysafe-checkout'
								) . '<br />' .
								__(
									'Express Checkout is an embedded integration where the Google Pay button appears directly on top of the checkout page.',
									'paysafe-checkout'
								),
							'class' => 'wc-enhanced-select',
							'group' => 'googlepay',
						];

						$google_pay_enabled = true;
					} else {
						unset($options['google_pay_integration_type' . $sfx]);
					}
				}
			}
		}

		if (!$apple_pay_enabled) {
			unset($options['apple_pay_integration_type' . $sfx]);
		}
		if (!$google_pay_enabled) {
			unset($options['google_pay_integration_type' . $sfx]);
		}

		$options['webhook_url' . $sfx] = [
			'title' => __('Webhook URL', 'paysafe-checkout'),
			'type' => 'paysafe_info_text',
			'description' => add_query_arg(
				[
					'payment_gateway' => 'paysafe',
				],
				WC()->api_request_url('paysafe_webhook')
			),
			'group' => 'neteller',
		];

		$options['webhook_secret_key' . $sfx] = [
			'title' => __('Webhook Secret Key', 'paysafe-checkout'),
			'type' => 'text',
			'default' => '',
			'description' => __('Webhook API key from Optic', 'paysafe-checkout'),
			'custom_attributes' => [
				'autocomplete' => 'off',
			],
			'group' => 'neteller',
		];

		return $options;
	}


	/**
     * Check whether the woocommerce subscriptions plugin is active or not
     *
     * @return bool
     */
    public static function is_woocommerce_subscriptions_active(): bool
    {
        return is_plugin_active('woocommerce-subscriptions/woocommerce-subscriptions.php') &&
               class_exists('WC_Subscriptions');
    }
}
