<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

class WC_Gateway_Paysafe_Blocks_Support extends AbstractPaymentMethodType
{
    /**
     * The gateway instance.
     *
     * @var WC_Gateway_Paysafe
     */
    private $gateway;

    /**
     * Initialize the payment type
     *
     * @param $name
     */
    public function __construct($name) {
        $this->name = $name;
    }

    /**
     * Initializes the payment method type.
     */
    public function initialize()
    {
        $this->settings = get_option(PAYSAFE_SETTINGS_KEYWORD, []);
        $gateways = WC()->payment_gateways->payment_gateways();
        $this->gateway = $gateways[$this->name];
    }

    /**
     * Returns if this payment method should be active.
     * If false, the scripts will not be enqueued.
     *
     * @return boolean
     */
    public function is_active()
    {
        return $this->gateway->is_available();
    }

    /**
     * Returns an array of scripts/handles to be registered for this payment method.
     *
     * @return array
     */
    public function get_payment_method_script_handles()
    {
        $payment_blocks_style = 'wc-' . $this->get_name() . '-payments-blocks-style';
        $payment_blocks = 'wc-' . $this->get_name() . '-payments-blocks';

        $script_path = 'assets/js/frontend/blocks-' . $this->get_name() . '.js';
        $script_asset_path = PAYSAFE_WOO_PLUGIN_URL
            . 'assets/js/frontend/blocks-' . $this->get_name() . '.asset.php';
        $script_asset = file_exists($script_asset_path)
            ? require_once($script_asset_path)
            : [
                'dependencies' => [],
                'version' => PAYSAFE_WOO_VERSION
            ];
        $script_url = PAYSAFE_WOO_PLUGIN_URL . $script_path;

        if (
            !wp_style_is($payment_blocks_style, 'enqueued')
            && WC_Gateway_Paysafe::PAYMENT_GATEWAY_CODE === $this->gateway->id
        ) {
            $style_url = PAYSAFE_WOO_PLUGIN_URL . 'assets/css/blocks-' . $this->get_name() . '.css';

            wp_register_style(
                $payment_blocks_style,
                $style_url,
                [],
                PAYSAFE_WOO_VERSION
            );
            wp_enqueue_style($payment_blocks_style);
        }

        if (!wp_script_is($payment_blocks, 'registered')) {
            wp_register_script(
                $payment_blocks,
                $script_url,
                $script_asset['dependencies'],
                $script_asset['version'],
                true
            );
        }

        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations(
                $payment_blocks,
                'paysafe-checkout',
                PAYSAFE_WOO_PLUGIN_URL . 'i18n/languages/'
            );
        }

        return [
            $payment_blocks
        ];
    }

    /**
     * Returns an array of key=>value pairs of data made available to the payment methods script.
     *
     * @return array
     */
    public function get_payment_method_data(): array
    {
        $paysafe_wc_options = get_option(PAYSAFE_SETTINGS_KEYWORD, []);
        $currency_code = strtoupper(get_woocommerce_currency());

        $is_test_mode = ($paysafe_wc_options['test_mode'] ?? null) === 'yes';
        $settings_prefix = $is_test_mode ? 'test_' : 'live_';
        $integration_type = $this->gateway->get_integration_type();

        $settings =  [
            'icon'              => $this->gateway->icon,
            'title'             => $this->gateway->title,
            'description'       => $this->gateway->description,
            'supports'          => array_filter($this->gateway->supports, [$this->gateway, 'supports']),

            // return url
            'register_url'      => add_query_arg(
                [
                    'payment_gateway'   => $this->gateway->id,
                ],
                WC()->api_request_url('paysafe_payment_response')
            ),

            'test_mode'         => $is_test_mode,
            'authorization'     => base64_encode($paysafe_wc_options[$settings_prefix . 'public_api_key']) ?? '',
            'account_id'        => $this->gateway->get_account_id(),
            'merchant_descriptor' => ($paysafe_wc_options['merchant_descriptor'] ?? ''),
            'merchant_phone' => ($paysafe_wc_options['merchant_phone'] ?? ''),
            'currency_code'     => $currency_code,
            'checkout_url'  => wc_get_checkout_url(),
            'locale'            => $this->gateway->get_paysafe_checkout_locale(),
            'integration_type'  => $integration_type,
            'cvv_verification'  => $this->gateway->is_cvv_token_enabled(),
            'log_errors'        => $this->gateway->is_error_logging_enabled(),
            'details'           => $this->gateway->get_extra_payment_details(),
            'consumer_id'       => $this->gateway->get_consumer_id(),
            'consumer_id_encrypted' => $this->gateway->get_encrypted_consumer_id(),
            'consumer_id_encrypted_20' => substr($this->gateway->get_encrypted_consumer_id(), 0, 20),
	        'paysafe_base_url' => PAYSAFE_WOO_PLUGIN_URL,
	        'is_subscription_payment' => $this->gateway->is_subscription_cart(),
	        'express_merchant_ref_num'=> $this->gateway->get_merchant_reference_number(),
        ];

        if ($this->gateway->is_error_logging_enabled()) {
            $settings['log_error_endpoint'] = add_query_arg(
                [
                    'payment_gateway'   => $this->gateway->id,
                ],
                WC()->api_request_url('paysafe_log_error')
            );
        }

	    if ($this->gateway->id === WC_Gateway_Paysafe_Google_Pay::PAYMENT_GATEWAY_CODE) {
		    $settings['google_pay_issuer_country'] = $this->gateway->get_account_issuer_country();
		    $settings['is_apple_pay_express_enabled'] = $this->gateway->is_express_gateway_enabled(WC_Gateway_Paysafe_Apple_Pay::PAYMENT_TYPE_CODE);
		    $settings['is_google_pay_express_enabled'] = $this->gateway->is_express_gateway_enabled(WC_Gateway_Paysafe_Google_Pay::PAYMENT_TYPE_CODE);
	    }
	    if ($this->gateway->id === WC_Gateway_Paysafe_Apple_Pay::PAYMENT_GATEWAY_CODE) {
		    $settings['is_apple_pay_express_enabled'] = $this->gateway->is_express_gateway_enabled(WC_Gateway_Paysafe_Apple_Pay::PAYMENT_TYPE_CODE);
		    $settings['is_google_pay_express_enabled'] = $this->gateway->is_express_gateway_enabled(WC_Gateway_Paysafe_Google_Pay::PAYMENT_TYPE_CODE);
	    }

	    ob_start();
        if ($this->gateway->id === WC_Gateway_Paysafe_Apple_Pay::PAYMENT_GATEWAY_CODE) {
	        if ( $settings['is_apple_pay_express_enabled'] && $settings['is_google_pay_express_enabled'] ) {
		        // load the common combo hosted buttons
		        include PAYSAFE_WOO_PLUGIN_PATH . '/resources/html/checkout/express-apple-google-combo-payment-form.php';
	        } else {
	            include PAYSAFE_WOO_PLUGIN_PATH . '/resources/html/checkout/hosted-apple-payment-form.php';
	        }
        } elseif ($this->gateway->id === WC_Gateway_Paysafe_Google_Pay::PAYMENT_GATEWAY_CODE) {
            include PAYSAFE_WOO_PLUGIN_PATH . '/resources/html/checkout/hosted-google-payment-form.php';
        } else {
            include PAYSAFE_WOO_PLUGIN_PATH . '/resources/html/checkout/hosted-payment-form.php';
        }
        $hosted_form = ob_get_clean();
        $settings['integration_hosted_form'] = $hosted_form;

        if (in_array($this->gateway->id, [
			WC_Gateway_Paysafe_Apple_Pay::PAYMENT_GATEWAY_CODE,
			WC_Gateway_Paysafe_Google_Pay::PAYMENT_GATEWAY_CODE,
	        ], true)) {
            $settings['express_checkout_endpoint'] = add_query_arg(
                [
                    'payment_gateway'   => $this->gateway->id,
                ],
                WC()->api_request_url('paysafe_express_checkout')
            );
        }

	    $settings['nonce'] = wp_create_nonce('paysafe_payment_response');

        return $settings;
    }

	/**
	 * Pass through for the `is_save_token_enabled` gateway function
	 *
	 * @return bool
	 */
	public function is_save_token_enabled(): bool
	{
		return $this->gateway->is_save_token_enabled();
	}

	/**
	 * Pass through for the `is_subscription_cart` gateway function
	 *
	 * @return bool
	 */
	public function is_subscription_cart(): bool
	{
		return $this->gateway->is_subscription_cart();
	}
}
