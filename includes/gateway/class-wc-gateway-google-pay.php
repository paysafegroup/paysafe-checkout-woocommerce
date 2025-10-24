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

	    if (self::ALLOW_EXPRESS_PAYMENT_GOOGLE_PAY && self::ALLOW_EXPRESS_PAYMENT_GOOGLE_PAY_PRODUCT_PAGE) {
		    // Add middleware to add express Google Pay button to product page
		    add_action(
			    'woocommerce_after_add_to_cart_form',
			    [
				    $this,
				    'handle_add_express_google_pay_to_product_page'
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
		if (WC_Gateway_Paysafe_Base::ALLOW_EXPRESS_PAYMENT_GOOGLE_PAY) {
			return $this->settings[ 'google_pay_integration_type' . $this->test_live_suffix() ] ?? self::PAYMENT_INTEGRATION_DEFAULT;
		}

		return self::PAYMENT_INTEGRATION_PAYSAFE_CHECKOUT;
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
            $google_pay_product_settings['nonce'] = wp_create_nonce('paysafe_express_ag_pay_product_checkout');


            $script_identifier = 'paysafe-google-pay-product';
            wp_register_script(
                $script_identifier,
                PAYSAFE_WOO_PLUGIN_URL . 'resources/js/legacy-checkout/paysafe-google-pay-product-page.js',
                [],
                PAYSAFE_WOO_VERSION,
                true,
            );
            wp_localize_script(
                $script_identifier,
                'settings',
                array_merge($base_settings, $google_pay_product_settings)
            );
            wp_enqueue_script($script_identifier);

			if (!$this->is_payment_method_enabled(WC_Gateway_Paysafe_Apple_Pay::PAYMENT_TYPE_CODE)) {
				include PAYSAFE_WOO_PLUGIN_PATH . '/resources/html/checkout/hosted-product-page-payment-form.php';
			}
        }
    }
}
