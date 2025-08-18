<?php

use Paysafe\PhpSdk\Exceptions\PaysafeApiException;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Gateway_Paysafe_Skrill extends WC_Gateway_Paysafe_Base {

    const PAYMENT_GATEWAY_CODE = 'skrill';
    const PAYMENT_TYPE_CODE = 'SKRILL';

    const PAYMENT_ICON_FILE = 'paysafe-skrill.png';

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
            $this->title .= ' Skrill';
        } else {
            $this->title = __('Skrill', 'paysafe-checkout');
        }

        $this->description = $this->get_option('checkout_description');
        if (empty($this->description)) {
            $this->description = __('Easy and secure payments with Skrill', 'paysafe-checkout');
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
     * Get extra payment method detail parameters
     *
     * @return array
     */
    public function get_extra_payment_details(): array
    {
        return [
            'subject'   => __('Skrill details subject', 'paysafe-checkout'),
            'message'   => __('Skrill details message', 'paysafe-checkout'),
        ];
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
	 * Overwrite the integration of Google Pay with Paysafe Checkout
	 *
	 * @return string
	 */
	public function get_integration_type(): string
	{
		return self::PAYMENT_INTEGRATION_PAYSAFE_CHECKOUT;
	}
}
