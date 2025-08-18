<?php

use Paysafe\PhpSdk\Exceptions\PaysafeApiException;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Gateway_Paysafe_Google_Pay extends WC_Gateway_Paysafe_Base {

    const PAYMENT_GATEWAY_CODE = 'google_pay';
    const PAYMENT_TYPE_CODE = 'GOOGLEPAY';

    const PAYMENT_ICON_FILE = 'paysafe-google-pay.png';

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

        return (int) ($paysafe_wc_options['account_id_' . $currency_code . '_' . self::PAYMENT_TYPE_CODE] ?? 0);
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
		return self::PAYMENT_INTEGRATION_PAYSAFE_CHECKOUT;
	}
}
