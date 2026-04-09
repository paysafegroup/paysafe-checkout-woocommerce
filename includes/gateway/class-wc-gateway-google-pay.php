<?php

use Paysafe\PhpSdk\Exceptions\PaysafeApiException;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Gateway_Paysafe_Google_Pay extends WC_Gateway_Paysafe_Base {

    const PAYMENT_GATEWAY_CODE = 'google_pay';
    const PAYMENT_TYPE_CODE = 'GOOGLEPAY';

    const PAYMENT_ICON_FILE = 'paysafe-google-pay.svg';

    /**
     * Initialize this gateway
     */
    public function __construct()
    {
        parent::__construct();

        $this->id = self::PAYMENT_GATEWAY_CODE;
        $this->title = '';
        $this->description = '';

        $this->title = $this->get_option('checkout_title');
        if (!empty($this->title)) {
            $this->title .= ' Google Pay';
        } else {
            $this->title = __('Google Pay', 'paysafe-checkout');
        }

        $this->description = $this->get_option('checkout_description');
        if (empty($this->description)) {
            $this->description = __('Easy and secure payments with Google Pay', 'paysafe-checkout');
        }

        $this->icon = PAYSAFE_WOO_PLUGIN_URL . 'assets/img/' . self::PAYMENT_ICON_FILE;

	    if (self::ALLOW_EXPRESS_PAYMENT_GOOGLE_PAY_PRODUCT_PAGE) {
		    // Add middleware to add express Google Pay button to product page
		    add_action(
			    'woocommerce_after_add_to_cart_form',
			    [
				    $this,
				    'handle_add_express_google_pay_to_product_page'
			    ]
		    );
	    }

	    if ($this->is_available()) {
		    // Add middleware to add express Google Pay button to checkout page
		    add_action(
			    'woocommerce_before_checkout_form',
			    [
				    $this,
				    'handle_add_express_google_pay_to_checkout_page'
			    ]
		    );
	    }
    }

    /**
     * Show saved tokens if user has them
     *
     * @return void
     */
    public function payment_fields() {
        if (is_add_payment_method_page()) {
            return;
        }

        parent::payment_fields();

	    $this->show_google_pay_details_form();
    }

    /**
     * Is this payment gateway available?
     *
     * @return bool
     */
    public function is_available(): bool
    {
        if (
            !parent::is_available()
            || $this->is_add_payment_method_page()
        ) {
            return false;
        }

        return $this->is_payment_method_enabled(self::PAYMENT_TYPE_CODE);
    }

    /**
     * Get the selected account id for a payment gateway
     *
     * @return int
     */
    public function get_account_id(): int
    {
        $paysafe_wc_options = $this->get_paysafe_settings();
        $currency_code = get_woocommerce_currency();

        return (int) ($paysafe_wc_options['account_id_' . $currency_code . '_' . self::PAYMENT_TYPE_CODE . $this->test_live_suffix()] ?? 0);
    }

    /**
     * Get the selected account id issuer country for a payment gateway
     *
     * @return string
     */
    public function get_account_issuer_country(): string
    {
	    return $this->settings[ 'google_pay_issuer_country' . $this->test_live_suffix() ] ?? '';
    }

	/**
	 * Show card details form at checkout
	 *
	 * @return void
	 */
	private function show_google_pay_details_form(): void
	{
		ob_start();

		include PAYSAFE_WOO_PLUGIN_PATH . '/resources/html/checkout/hosted-google-payment-form.php';

		ob_end_flush();
	}

	/**
	 * Overwrite the integration of Google Pay with Paysafe Checkout
	 *
	 * @return string
	 */
	public function get_integration_type(): string
	{
		return $this->settings[ 'google_pay_integration_type' . $this->test_live_suffix() ] ?? self::PAYMENT_INTEGRATION_DEFAULT;
	}

    /**
     * Add Express Checkout Google Pay button to product page
     *
     * @return void
     */
    public function handle_add_express_google_pay_to_product_page(): void
    {
	    if (!$this->is_available()) {
		    return;
	    }

	    if (is_product() && get_current_user_id() && $this->get_integration_type() === self::PAYMENT_INTEGRATION_PAYSAFE_JS) {
            // get settings
            $paysafe_wc_options = $this->get_paysafe_settings();
            $currency_code = get_woocommerce_currency();
            $is_test_mode = ($paysafe_wc_options['test_mode'] ?? null) === 'yes';
            $integration_type = $this->get_integration_type();

            // settings that are made available to the javascript files
            $base_settings = $this->get_paysafe_base_settings(
                $is_test_mode,
                $currency_code
            );

            // checkout specific settings
            $google_pay_product_settings = $this->get_paysafe_checkout_settings($integration_type);
            $google_pay_product_settings['account_id'] = $this->get_account_id();
            $google_pay_product_settings['express_checkout_url'] = add_query_arg(
                [
                    'payment_gateway'   => $this->id,
                ],
                WC()->api_request_url('paysafe_product_express_ag_pay_checkout')
            );
            $google_pay_product_settings['nonce'] = wp_create_nonce('paysafe_payment_response');


            $script_identifier = 'paysafe-google-pay-product';
            wp_register_script(
                $script_identifier,
                PAYSAFE_WOO_PLUGIN_URL . 'assets/js/legacy/legacy-paysafe-google-product.js',
	            ['wp-i18n'],
                PAYSAFE_WOO_VERSION,
                true,
            );
            wp_localize_script(
                $script_identifier,
                'google_pay_paysafe_pp_config',
                array_merge($base_settings, $google_pay_product_settings)
            );
            wp_enqueue_script($script_identifier);

		    if (function_exists('wp_set_script_translations')) {
			    wp_set_script_translations(
				    $script_identifier,
				    'paysafe-checkout',
				    PAYSAFE_WOO_PLUGIN_URL . 'i18n/languages/'
			    );
		    }

			if (!$this->is_payment_method_enabled(WC_Gateway_Paysafe_Apple_Pay::PAYMENT_TYPE_CODE)) {
				include PAYSAFE_WOO_PLUGIN_PATH . '/resources/html/checkout/hosted-product-page-payment-form.php';
			}
        }
    }

	/**
	 * Add Express Checkout Google Pay button to checkout page
	 *
	 * @return void
	 */
	public function handle_add_express_google_pay_to_checkout_page(): void
	{
		if (!$this->is_available()) {
			return;
		}

		if (is_checkout() && !is_wc_endpoint_url('order-pay') && !is_wc_endpoint_url('order-received') && $this->get_integration_type() === self::PAYMENT_INTEGRATION_PAYSAFE_JS) {
			$currency_code = get_woocommerce_currency();
			$is_test_mode = $this->is_test_mode();
			$integration_type = $this->get_integration_type();

			// settings that are made available to the javascript files
			$base_settings = $this->get_paysafe_base_settings(
				$is_test_mode,
				$currency_code
			);

			// checkout specific settings
			$google_pay_checkout_settings = $this->get_paysafe_checkout_settings($integration_type);
			$google_pay_checkout_settings['account_id'] = $this->get_account_id();
			$google_pay_checkout_settings['express_checkout_url'] = add_query_arg(
				[
					'payment_gateway'   => $this->id,
				],
				WC()->api_request_url('paysafe_checkout_express_ag_pay_checkout')
			);
			$google_pay_checkout_settings['nonce'] = wp_create_nonce('paysafe_payment_response');
			$google_pay_checkout_settings['is_apple_pay_express_enabled'] = $this->is_express_gateway_enabled(WC_Gateway_Paysafe_Apple_Pay::PAYMENT_TYPE_CODE);
			$google_pay_checkout_settings['is_google_pay_express_enabled'] = $this->is_express_gateway_enabled(WC_Gateway_Paysafe_Google_Pay::PAYMENT_TYPE_CODE);
			$google_pay_checkout_settings['express_merchant_ref_num'] = $this->get_merchant_reference_number();
			$google_pay_checkout_settings['express_checkout_endpoint'] = add_query_arg(
				[
					'payment_gateway'   => $this->id,
				],
				WC()->api_request_url('paysafe_express_checkout')
			);

			$script_identifier = 'paysafe-google-pay-checkout-express';
			wp_register_script(
				$script_identifier,
				PAYSAFE_WOO_PLUGIN_URL . 'assets/js/legacy/legacy-paysafe-google-express.js',
				['wp-i18n'],
				PAYSAFE_WOO_VERSION,
				true,
			);
			wp_localize_script(
				$script_identifier,
				'google_pay_paysafe_config',
				array_merge($base_settings, $google_pay_checkout_settings)
			);
			wp_enqueue_script($script_identifier);

			if (function_exists('wp_set_script_translations')) {
				wp_set_script_translations(
					$script_identifier,
					'paysafe-checkout',
					PAYSAFE_WOO_PLUGIN_URL . 'i18n/languages/'
				);
			}

			// reusing the same form container as product page for now, or creating a new one if needed
			// Let's create a specific container for checkout express
			if (
				!$this->is_payment_method_enabled(WC_Gateway_Paysafe_Apple_Pay::PAYMENT_TYPE_CODE)
				|| $this->settings['apple_pay_integration_type' . $this->test_live_suffix()] !== self::PAYMENT_INTEGRATION_PAYSAFE_JS
			) {
				$this->show_legacy_express_form();
			}
		}
	}

	/**
	 * Show card details form at checkout
	 *
	 * @return void
	 */
	private function show_legacy_express_form(): void
	{
		ob_start();

		include PAYSAFE_WOO_PLUGIN_PATH . '/resources/html/checkout/legacy-google-pay-express-form.php';

		ob_end_flush();
	}
}
