<?php

use Paysafe\PhpSdk\Exceptions\PaysafeApiException;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Gateway_Paysafe extends WC_Gateway_Paysafe_Base {

    const PAYMENT_GATEWAY_CODE = 'paysafe';
    const PAYMENT_TYPE_CODE = 'CARD';

    const PAYMENT_ICON_FILE = 'card-brands.png';

    private string $error_occured_text = '';


    /**
     * Initialize this gateway
     */
    public function __construct()
    {
        parent::__construct();

        $this->id = self::PAYMENT_GATEWAY_CODE;

        $this->has_fields = true;
        $this->title = '';
        $this->description = '';

        $this->title = $this->get_option('checkout_title');
        if (!empty($this->title)) {
            $this->title .= ' Credit/Debit Cards';
        } else {
            $this->title = __('Credit/Debit Cards', 'paysafe-checkout');
        }

        $this->description = $this->get_option('checkout_description');

        $this->icon = PAYSAFE_WOO_PLUGIN_URL . 'assets/img/' . self::PAYMENT_ICON_FILE;

        $this->method_title = __('Paysafe Checkout', 'paysafe-checkout');
        $this->method_description =
            __(
            'Accept card payments on your WooCommerce store while minimising your security and PCI compliance requirements.',
            'paysafe-checkout'
            );

        // Add middlemen to handle admin options settings form options saving
        add_action('woocommerce_update_options_payment_gateways_paysafe',
            [
                $this,
                'process_admin_options',
            ]
        );


        // register client side scripts (and styles)
        add_action('wp_enqueue_scripts', [
            $this,
            'enqueue_paysafe_scripts'
        ]);

        // subscribe to delete paysafe customer token from paysafe
        add_action('woocommerce_payment_token_deleted', [
            $this,     
            'delete_paysafe_token'
        ], 10, 2);

        $this->error_occured_text = __('An error occurred, please try again!', 'paysafe-checkout');

        $this->maybe_init_subscriptions();
    }

    /**
     * Test the validity of the saved public key
     *
     * @return bool
     */
    private function is_public_key_valid(): bool
    {
        return $this->is_saved_key_valid(true);
    }

    /**
     * Test the validity of the saved private key
     *
     * @return bool
     */
    private function is_private_key_valid(): bool
    {
        return $this->is_saved_key_valid(false);
    }

    /**
     * Test saved keys validity
     *
     * @param bool $use_public_key
     *
     * @return bool
     */
    private function is_saved_key_valid(bool $use_public_key = false): bool
    {
        try {
            // initiate the paysafe general connector
            $api_connector = new PaysafeApiGeneralPluginConnector();
            return $api_connector->testSaveKey($use_public_key);
        } catch (\Exception $e) {
            $this->log_error('Paysafe Checkout: Public key invalid "' . $e->getMessage() . '"');
        }

        return false;
    }

    /**
     * Called when saving options in WP plugin settings
     * We test basic Paysafe API Calls
     *
     * @return void
     */
    public function process_admin_options(): void
    {
        parent::process_admin_options();

        // check for public key validity
        if (!$this->is_public_key_valid()) {
            $this->errors[] =
            __('The Public API Key is invalid.','paysafe-checkout') . ' ' .
            __(
            'Please make sure you have copied it correctly from your Paysafe Merchant Portal (Optic or Netbanx).',
            'paysafe-checkout'
            ) . ' ' .
            __('For detailed instructions, refer to the Merchant Guide.','paysafe-checkout');
            $this->display_errors();

            return;
        }

        // check for private key validity
        if (!$this->is_private_key_valid()) {
            $this->errors[] =
            __('The Private API Key is invalid.', 'paysafe-checkout') . ' '.
            __(
            'Please make sure you have copied it correctly from your Paysafe Merchant Portal (Optic or Netbanx).',
            'paysafe-checkout'
            ) . ' ' .
            __('For detailed instructions, refer to the Merchant Guide.', 'paysafe-checkout');
            $this->display_errors();

            return;
        }

        // get payment methods for this merchant and currency
        try {
            $currency_code = get_woocommerce_currency();

            // initiate the paysafe general connector
            $api_connector = new PaysafeApiGeneralPluginConnector();

            // get the payment methods, and test private key validity in the meantime
            $payment_methods_response = $api_connector->getPaymentMethods($currency_code);
            $this->update_paysafe_payment_methods_options(
                $payment_methods_response['paymentMethods'] ?? []
            );
        } catch (PaysafeException $e) {
            $this->log_error(
                'Paysafe Checkout: process_admin_options failed. Paysafe Exception "' . $e->getMessage() . '"',
                $e->getAdditionalData());

            $additional_data = $e->getAdditionalData();
            $this->errors[] =
                sprintf(
                /* translators: %s is replaced by the message */
                    __('ERROR: %s', 'paysafe-checkout'),
                    $e->getMessage() . ' class-wc-gateway-paysafe.php'
                    . trim(($additional_data['error_code'] ?? '') . ' '
                        . ($additional_data['error_message'] ?? ''))
                );

            if (($additional_data['error_code'] ?? 0) === PaysafeApiException::OPTIONS_EMPTY) {
                // clear payment methods
                $this->update_paysafe_payment_methods_options([]);
            }

            if ($e->getCode() === PaysafeApiException::API_INVALID_CREDENTIALS) {
                $this->display_errors();
            }
        } catch (\Exception $e) {
            $this->log_error(
                'Paysafe Checkout: process_admin_options failed. Paysafe Exception "'
                . $e->getMessage() . '"');
        }
    }

    /**
     * Handle Apple Pay payment method verification
     *
     * @param array $payment_method
     * @param array $settings
     * @param string $currency_code
     *
     * @return array
     */
    private function handle_payment_method_apple_pay(
        array $payment_method,
        array $settings,
        string $currency_code
    ): array
    {
        $acc_id = $payment_method['accountId'] ?? null;

        $payment_code_apple_pay = WC_Gateway_Paysafe_Apple_Pay::PAYMENT_TYPE_CODE;

        $apple_pay_settings = $settings['payment_methods'][$currency_code][$payment_code_apple_pay] ?? [];

        $apple_pay_settings['name'] = $payment_code_apple_pay;
        $apple_pay_settings['available'] = true;

        $apple_pay_settings['accounts'][$acc_id]['account_id'] = $acc_id;
        $apple_pay_settings['accounts'][$acc_id]['processor'] = $payment_method['processorCode'] ?? '';
        $apple_pay_settings['accounts'][$acc_id]['mcc'] = $payment_method['mcc'] ?? '';
        $apple_pay_settings['accounts'][$acc_id]['mcc_description'] = $payment_method['mccDescription'] ?? '';

        // save the default account ID for the merchant, so that he can use it right away
        if (!isset($settings['account_id_' . $currency_code . '_' . $payment_code_apple_pay]) || empty($settings['account_id_' . $currency_code . '_' . $payment_code_apple_pay])) {
            $settings['account_id_' . $currency_code . '_' . $payment_code_apple_pay]
                = $payment_method['accountId'];
        }

        $settings['payment_methods'][$currency_code][$payment_code_apple_pay] = $apple_pay_settings;

        return $settings;
    }

	/**
	 * Handle Google Pay payment method verification
	 *
	 * @param array $payment_method
	 * @param array $settings
	 * @param string $currency_code
	 *
	 * @return array
	 */
	private function handle_payment_method_google_pay(
		array $payment_method,
		array $settings,
		string $currency_code
	): array
	{
		$acc_id = $payment_method['accountId'] ?? null;

		$payment_code_google_pay = WC_Gateway_Paysafe_Google_Pay::PAYMENT_TYPE_CODE;

		$google_pay_settings = $settings['payment_methods'][$currency_code][$payment_code_google_pay] ?? [];

		$google_pay_settings['name'] = $payment_code_google_pay;
		$google_pay_settings['available'] = true;

		$google_pay_settings['accounts'][$acc_id]['account_id'] = $acc_id;
		$google_pay_settings['accounts'][$acc_id]['processor'] = $payment_method['processorCode'] ?? '';
		$google_pay_settings['accounts'][$acc_id]['mcc'] = $payment_method['mcc'] ?? '';
		$google_pay_settings['accounts'][$acc_id]['mcc_description'] = $payment_method['mccDescription'] ?? '';

		// save the default account ID for the merchant, so that he can use it right away
		if (!isset($settings['account_id_' . $currency_code . '_' . $payment_code_google_pay]) || empty($settings['account_id_' . $currency_code . '_' . $payment_code_google_pay])) {
			$settings['account_id_' . $currency_code . '_' . $payment_code_google_pay]
				= $payment_method['accountId'];
		}

		$settings['payment_methods'][$currency_code][$payment_code_google_pay] = $google_pay_settings;

		return $settings;
	}

    /**
     * Handle payment method verification
     *
     * @param array $payment_method
     * @param array $settings
     * @param string $currency_code
     *
     * @return array
     */
    private function handle_payment_method_case(
        array $payment_method,
        array $settings,
        string $currency_code
    ): array
    {
        $payment_code = $payment_method['paymentMethod'] ?? null;
        $acc_id = $payment_method['accountId'] ?? null;
        $payment_settings = $settings['payment_methods'][$currency_code][$payment_code] ?? [];

        if (
            !$payment_code
            || !$acc_id
            || !in_array($payment_code, [
                WC_Gateway_Paysafe::PAYMENT_TYPE_CODE,
                WC_Gateway_Paysafe_Apple_Pay::PAYMENT_TYPE_CODE,
                WC_Gateway_Paysafe_Google_Pay::PAYMENT_TYPE_CODE,
                WC_Gateway_Paysafe_Skrill::PAYMENT_TYPE_CODE,
                WC_Gateway_Paysafe_Neteller::PAYMENT_TYPE_CODE,
                WC_Gateway_Paysafe_Cash::PAYMENT_TYPE_CODE,
                WC_Gateway_Paysafe_Card::PAYMENT_TYPE_CODE,
            ])
        ) {
            return $settings;
        }

        $payment_settings['name'] = $payment_code;
        $payment_settings['available'] = true;

        $payment_settings['accounts'][$acc_id]['account_id'] = $acc_id;
        $payment_settings['accounts'][$acc_id]['processor'] = $payment_method['processorCode'] ?? '';
        $payment_settings['accounts'][$acc_id]['mcc'] = $payment_method['mcc'] ?? '';
        $payment_settings['accounts'][$acc_id]['mcc_description']
            = $payment_method['mccDescription'] ?? '';

        $settings['payment_methods'][$currency_code][$payment_code] = $payment_settings;

        // save the default account ID for the merchant, so that he can use it right away
        if (
            !isset($settings['account_id_' . $currency_code . '_' . $payment_code])
            || (
                (int)$payment_method['accountId']
                && !$settings['account_id_' . $currency_code . '_' . $payment_code]
            )
        ) {
            $settings['account_id_' . $currency_code . '_' . $payment_code] = $payment_method['accountId'];
        }

        // if this is the first time the merchant has saved this, add the default value to save tokens
        if (!isset($settings['save_tokens_' . $payment_code])) {
            $settings['save_tokens_' . $payment_code]
                = self::ALLOW_TOKENIZATION_SUPPORT ? self::SAVE_TOKEN_DEFAULT_VALUE : 'no';
        }

        return $settings;
    }

    /**
     * Update the payment methods settings then return the changed array
     *
     * @param array $payment_methods
     * @param array $settings
     *
     * @return array
     */
    private function handle_update_payment_methods_settings(
        array $payment_methods,
        array $settings
    ): array
    {
        // get the current currency
        $currency_code = get_woocommerce_currency();

        foreach ($payment_methods as $payment_method) {
            $settings = $this->handle_payment_method_case(
                $payment_method,
                $settings,
                $currency_code
            );

            // special code for Apple Pay, as it itself doesn't have a payment method,
            // it's a card account that has Apple Pay enabled within it
            if (($payment_method['paymentMethod'] ?? null) === WC_Gateway_Paysafe::PAYMENT_TYPE_CODE
                && ($payment_method['accountConfiguration']['isApplePay'] ?? false) === true) {

                $settings = $this->handle_payment_method_apple_pay(
                    $payment_method,
                    $settings,
                    $currency_code
                );
            }

	        // special code for Google Pay, as it itself doesn't have a payment method,
	        // it's a card account that has Google Pay enabled within it
	        if (($payment_method['paymentMethod'] ?? null) === WC_Gateway_Paysafe::PAYMENT_TYPE_CODE
	            && ($payment_method['accountConfiguration']['isGooglePay'] ?? false) === true) {

		        $settings = $this->handle_payment_method_google_pay(
			        $payment_method,
			        $settings,
			        $currency_code
		        );
	        }
        }

        return $settings;
    }

    /**
     * Update all available payment methods for this merchant
     * Disable availability for all which are not in the list
     *
     * @param array $payment_methods
     *
     * @return void
     */
    private function update_paysafe_payment_methods_options(array $payment_methods): void
    {
        $this->log_debug(
            'Paysafe Checkout: update_paysafe_payment_methods_options called',
                $payment_methods ?? []);

        // get the current settings
        $settings = $this->get_paysafe_settings();

        // get the current currency
        $currency_code = get_woocommerce_currency();

        // clear all other currencies from our settings
        $current_currency_payment_methods = $settings['payment_methods'][$currency_code] ?? [];
        $settings['payment_methods'] = [
            $currency_code  => $current_currency_payment_methods,
        ];

        // disable all payment methods
        foreach ($settings['payment_methods'][$currency_code] as $payment_code => $payment_method) {
            $settings['payment_methods'][$currency_code][$payment_code]['available'] = false;
            $settings['payment_methods'][$currency_code][$payment_code]['accounts'] = [];
        }

        // activate and update available payment methods
        $settings = $this->handle_update_payment_methods_settings(
            $payment_methods,
            $settings
        );

        if (empty($settings['checkout_description'])) {
            // add error to the settings page
            $this->errors[] = __('Payment method description is not set', 'paysafe-checkout');
        }

        // force always enable CARD payment method
        if (!empty($payment_methods)) {
            $settings['payment_method_' . $currency_code . '_' . WC_Gateway_Paysafe::PAYMENT_TYPE_CODE] = 'yes';
        }

        // set all unavailable payment methods to NO
        foreach ($settings['payment_methods'][$currency_code] as $payment_code => $payment_method) {
            if (!($payment_method['available'])) {
                $settings['payment_method_' . $currency_code . '_' . $payment_code] = 'no';
            }
        }

        // update the options values
        update_option($this->get_option_key(), $settings);

        $this->display_errors();
    }

    /**
     * Show saved tokens if user has them
     *
     * @return void
     */
    public function payment_fields() {
        if (is_add_payment_method_page()) {

            // include the payment CC form
            $this->add_payment_method_form();

            return;
        }

        parent::payment_fields();

		// only show the token list and the new payment method radio if we have tokens
        if (
			self::ALLOW_TOKENIZATION_SUPPORT &&
			$this->supports( 'tokenization' ) &&
			is_checkout() &&
			count($this->get_tokens()) > 0
        ) {
            $this->saved_payment_methods();
        }

		$this->show_card_details_form();
    }

    /**
     * Show card details form at checkout
     *
     * @return void
     */
    private function show_card_details_form(): void
    {
        ob_start();

        include PAYSAFE_WOO_PLUGIN_PATH . '/resources/html/checkout/hosted-payment-form.php';

        ob_end_flush();
    }

    /**
     * Is this payment gateway available?
     *
     * @return bool
     */
    public function is_available(): bool
    {
        if (!parent::is_available()) {
            return false;
        }

        if (
            $this->is_add_payment_method_page()
            && !$this->is_save_token_enabled(self::PAYMENT_TYPE_CODE)) {
            return false;
        }

        return $this->is_payment_method_enabled(self::PAYMENT_TYPE_CODE);
    }

    /**
     * Delete paysafe token
     *
     * @param $token_id
     * @param $token
     *
     * @return void
     */
    public function delete_paysafe_token($token_id, $token)
    {
        if (!defined('PAYSAFE_DELETE_TOKEN_TEXT')) {
            define('PAYSAFE_DELETE_TOKEN_TEXT', 'Paysafe Checkout: [Token ID ' . $token_id . '] Delete Paysafe Token');
        }

        if (!$token || $token->get_user_id() !== get_current_user_id() || $token->get_gateway_id() !== $this->id) {
            $this->log_debug(PAYSAFE_DELETE_TOKEN_TEXT . ' failed - Token not found');

            // no token
            return;
        }

        // get the actual token
        $paysafe_token = $this->get_saved_card_token($token->get_token());
        $customer_id = $this->get_paysafe_customer_id();
        if (!$customer_id || empty($paysafe_token)) {
            $this->log_error(PAYSAFE_DELETE_TOKEN_TEXT . ' failed - Customer ID or Paysafe token not found');

            return;
        }
        $paysafe_token_id = $this->get_paysafe_token_id($paysafe_token);

        if ($paysafe_token_id === null) {
            $this->log_error(
                "Paysafe Checkout: Delete payment method token failed - Paysafe token does not exist"
            );

            return;
        }
        
        try {
            // get the api connector
            $api_connector = new PaysafeApiCardPluginConnector();

            // call to paysafe for the delete paysafe token action
            $api_connector->deletePaysafeToken($customer_id, $paysafe_token_id);
        } catch (PaysafeException $e) {
            $this->log_error(
                PAYSAFE_DELETE_TOKEN_TEXT . ' - Paysafe Exception "'
                . $e->getMessage() . '"', array_merge($result ?? [], $e->getAdditionalData()));
        } catch (\Exception $e) {
            $this->log_error(PAYSAFE_DELETE_TOKEN_TEXT . ' - Exception "' . $e->getMessage() . '"');
        }
    }

    /**
     * Get paysafe token id by card id
     *
     * @param string $customer_id
     * @param string|null $card_id
     * @param array|null $card_data
     *
     * @return array|null
     */
    private function get_paysafe_payment_handle_data_by_card_id(
        string $customer_id,
        string $card_id = null,
        array $card_data = null): ?array
    {
        if (!$customer_id) {
            $this->log_debug(
                'Paysafe Checkout: [Cust ID '
                . $customer_id . '] Get Paysafe payment handle failed - Customer ID not found');

            return null;
        }

        try {
            // get the api connector
            $api_connector = new PaysafeApiCardPluginConnector();

            // call to paysafe for the delete paysafe token action
            $result = $api_connector->getPaysafeCustomerData($customer_id, ['fields' => 'paymenthandles']);

            foreach($result['paymentHandles'] ?? [] as $payment_handle) {
                // check for correct card id
                if (
                    ($card_id && $card_id === ($payment_handle['card']['id'] ?? ''))
                    || (
                        ($card_data['cardBin'] ?? null)
                        && ($card_data['lastDigits'] ?? null)
                        && (($card_data['cardBin'] ?? null) === ($payment_handle['card']['cardBin'] ?? null))
                        && (($card_data['lastDigits'] ?? null) === ($payment_handle['card']['lastDigits'] ?? null))
                    )
                ) {
                    // return the whole payment handle
                    return $payment_handle;
                }
            }
        } catch (PaysafeException $e) {
            $this->log_error(
                'Paysafe Checkout: [Cust ID ' . $customer_id . '] Paysafe Exception "'
                . $e->getMessage() . '"', array_merge($result ?? [], $e->getAdditionalData()));
        } catch (\Exception $e) {
            $this->log_error('Paysafe Checkout: [CustomerID '
                . $customer_id . '] Exception "' . $e->getMessage() . '"');
        }

        return null;
    }

    /**
     * Include the CC card form to the add payment method page
     * for the paysafe gateway
     *
     * @return void
     */
    private function add_payment_method_form(): void
    {
        ob_start();

        $current_customer = new WC_Customer( get_current_user_id() );

        $billing_details = [
            'first_name'=> $current_customer->get_billing_first_name(),
            'last_name' => $current_customer->get_billing_last_name(),
            'email' => $current_customer->get_billing_email(),
            'country' => $current_customer->get_billing_country(),
            'zip' => $current_customer->get_billing_postcode(),
            'city' => $current_customer->get_billing_city(),
            'state' => $current_customer->get_billing_state(),
            'street' => trim($current_customer->get_billing_address_1()
                    . $current_customer->get_billing_address_2()) ?? '',
        ];

        $paysafe_wc_options = $this->get_paysafe_settings();
        $is_test_mode = ($paysafe_wc_options['test_mode'] ?? null) === 'yes';

        $countries_obj = new WC_Countries();
        $countries = $countries_obj->__get('countries');

        include_once PAYSAFE_WOO_PLUGIN_PATH
            . '/resources/html/my-account/payment-methods/add-paysafe-payment-method-form.php';

        ob_end_flush();
    }

    /**
     * Handle saving the paysafe payment method on my account page
     *
     * @return array
     */
    public function add_payment_method(): array
    {
        // phpcs:disable WordPress.Security.NonceVerification.Missing
        $paysafe_token = sanitize_text_field( wp_unslash( $_POST['payment_method_paysafe_token'] ?? '' ));
        $merchant_reference = sanitize_text_field( wp_unslash( $_POST['payment_method_merchant_reference'] ?? '' ));
        // phpcs:enable WordPress.Security.NonceVerification.Missing

        if (empty($paysafe_token)) {
            $this->log_error('Paysafe Checkout: Add payment method submitted, no Paysafe token');

            return [
                'result'   => 'failure',
                'redirect' => wc_get_endpoint_url( 'add-payment-method' ),
            ];
        }

        // get the paysafe customer id from our database
        $paysafe_customer_id = $this->get_paysafe_customer_id();

        if (empty($paysafe_customer_id)) {
            // if we don't have it in our database, check on paysafe
            $paysafe_customer_id = $this->get_paysafe_customer_id_from_paysafe();
        }

        if (empty($paysafe_customer_id)) {
            // add the customer to paysafe and retrieve the multi payment token
            $success = $this->create_paysafe_customer_from_apm($paysafe_token, $merchant_reference);
        } else {
            // just create a multi use payment token
            $success = $this->create_paysafe_customer_payment_token_from_apm(
                $paysafe_customer_id, $paysafe_token, $merchant_reference);
        }

        if ($success) {
            return [
                'result'   => 'success',
                'redirect' => wc_get_endpoint_url( 'payment-methods' ),
            ];
        }

        return [
            'result'   => wc_notice_count() > 0 ? 'fail' : 'failure',
            'redirect' => wc_get_endpoint_url( 'add-payment-method' ),
        ];
    }

    /**
     * Create a paysafe customer and multi use token
     * with single use token received from the Add Payment Method page
     *
     * @param string $paysafe_token
     * @param string|null $merchant_reference
     *
     * @return bool
     */
    private function create_paysafe_customer_from_apm(string $paysafe_token, string $merchant_reference = null): bool
    {
        try {
            $merchant_reference_number = $this->get_merchant_reference_number();

            // get the api connector
            $api_connector = new PaysafeApiCardPluginConnector();

            // call to paysafe to create the customer
            $result = $api_connector->createPaysafeCustomer(
                [
                    'merchantRefNum'            => $merchant_reference_number,
                    'merchantCustomerId'        => $this->get_merchant_customer_id(),
                    'locale'                    => $this->get_paysafe_checkout_locale(),
                    'paymentType'               => self::PAYMENT_TYPE_CODE,
                    'paymentHandleTokenFrom'    => $paysafe_token,
                ]
            );

            // get the paysafe customer id that was created for this customer
            $paysafe_customer_id = $result['id'] ?? null;
            if (!$paysafe_customer_id) {
                // if there is no paysafe customer id and there is an error code
                // which means that merchant is already registered,
                // retrieve the paysafe customer id and continue
                if (((int) $result['error']['code'] ?? null)
                    === self::PAYSAFE_ERROR_MERCHANT_CUSTOMER_ALREADY_REGISTERED) {
                    $paysafe_customer_id = $this->get_paysafe_customer_id_from_paysafe();
                }

                // if we have paysafe customer id, then leats create this payment token with the traditional method
                if ($paysafe_customer_id) {
                    return $this->create_paysafe_customer_payment_token_from_apm(
                        $paysafe_customer_id,
                        $paysafe_token,
                        $merchant_reference);
                }

                throw new PaysafeException(
                    __('Create Paysafe Customer failed', 'paysafe-checkout'),
                    PaysafeException::CUSTOMER_NOT_CREATED
                );
            }
            $this->save_paysafe_customer_id($paysafe_customer_id);

            $payment_handles = $result['paymentHandles'] ?? [];
            foreach ($payment_handles as $payment_handle_data) {
                if (
                    ($payment_handle_data['paymentHandleToken'] ?? null)
                        && 'MULTI_USE' === ($payment_handle_data['usage'] ?? null)
                        && self::PAYMENT_TYPE_CODE === ($payment_handle_data['paymentType'] ?? null)
                ) {
                    return $this->save_paysafe_customer_token(
                        $payment_handle_data['paymentHandleToken'],
                        $payment_handle_data['card'] ?? []
                    );
                }
            }

            throw new PaysafeException(
                __(
                'Create Paysafe Customer with Add Payment Method failed due to missing multi-use payment handle',
                'paysafe-checkout'
                ),
                PaysafeException::CUSTOMER_CREATED_WITHOUT_MULTI_TOKEN
            );

        } catch (PaysafeException $e) {
            $this->log_error(
                'Paysafe Checkout: Paysfe Exception "' . $e->getMessage() . '"',
                array_merge($result ?? [], $e->getAdditionalData()));

            $public_error_message = match ($e->getCode()) {
                PaysafeException::CUSTOMER_CREATED_WITHOUT_MULTI_TOKEN  => $this->error_occured_text,
                PaysafeException::CUSTOMER_NOT_CREATED  => $this->error_occured_text,
                default  => $e->getMessage(),
            };
            wc_add_notice( $public_error_message, 'error' );
        } catch (\Exception $e) {
            $this->log_error('Paysafe Checkout: Exception "' . $e->getMessage() . '"');
        }

        return false;
    }

    /**
     * Create a customer payment handle from Add Payment Method page token
     *
     * @param string $paysafe_customer_id
     * @param string $paysafe_token
     * @param string|null $merchant_reference
     *
     * @return bool
     */
    private function create_paysafe_customer_payment_token_from_apm(
        string $paysafe_customer_id,
        string $paysafe_token,
        string $merchant_reference = null): bool
    {
        try {
            // get the api connector
            $api_connector = new PaysafeApiCardPluginConnector();

            // call to paysafe for the delete paysafe token action
            $result = $api_connector->createPaysafeCustomerToken(
                $paysafe_customer_id,
                [
                    'merchantRefNum'            => $this->get_merchant_reference_number(),
                    'paymentHandleTokenFrom'    => $paysafe_token,
                ]
            );

            // get the payment handle token, if it exists
            $payment_handle_token = $result['paymentHandleToken'] ?? null;

            // check for errors
            if (
                $result['error'] ?? null 
                && in_array((int)$result['error']['code'] ?? null, [7503]) 
                && !empty($result['error']['message'] ?? null)) {
                // this means that this payment method card was already added before

                // get the card data for this method
                $card_data = [];
                if ($merchant_reference) {
                    $payment_handle_data = $this->get_payment_handle_by_merchant_reference($merchant_reference);
                    $card_data = $payment_handle_data['card'] ?? [];
                }

                // try to get the correct card ID
                $error_message = $result['error']['message'] ?? '';
                $pmResult = [];
                preg_match(
                    '/[0-9a-z]+\-[0-9a-z]+\-[0-9a-z]+\-[0-9a-z]+\-[0-9a-z]+/',
                    $error_message,
                    $pmResult);
                $card_id = $pmResult[0] ?? null;
                if (!$card_id) {
                    $card_id = trim(substr($error_message, strrpos($error_message, ' - ') + 3));
                }

                // get the payment handle data back
                $payment_handle_result = $this->get_paysafe_payment_handle_data_by_card_id(
                    $paysafe_customer_id, $card_id, $card_data);

                // retrieve values used later
                $payment_handle_token = $payment_handle_result['paymentHandleToken'] ?? null;
                $result['card'] = $payment_handle_result['card'] ?? [];
            }

            // if there is no payment handle token, stop
            if (empty($payment_handle_token)) {
                throw new PaysafeException(
                    __('Add paysafe payment method failed', 'paysafe-checkout'),
                    PaysafeException::CUSTOMER_HANDLE_CREATE_FAILED
                );
            }

            // save the payment handle token and card info in our db
            return $this->save_paysafe_customer_token(
                $payment_handle_token,
                $result['card'] ?? []
            );
        } catch (PaysafeApiException|PaysafeException $e) {
            $this->log_error('Paysafe Checkout: Paysaf Exception "'
                . $e->getMessage() . '"', array_merge($result ?? [], $e->getAdditionalData()));
            
            // if there is additional data and an error code inside it
            $additional_data = $e->getAdditionalData();
            $error_code = $additional_data['error_code'];
            if ($error_code === self::PAYSAFE_ERROR_CUSTOMER_ID_NOT_FOUND) {
                // this means that the customer id stored locally is outdated,
                // we need to remove the paysafe customer id from our database
                $this->delete_paysafe_customer_id();

                // we should try creating the token without the customer id
                // and this will create a new customer OR update our current customer id
                return $this->create_paysafe_customer_from_apm($paysafe_token);
            }

            // it's another error, notify the customer that apm failed
            $public_error_message = match ($e->getCode()) {
                PaysafeException::CUSTOMER_HANDLE_CREATE_FAILED  => $this->error_occured_text,
                default  => $e->getMessage(),
            };
            wc_add_notice( $public_error_message, 'error' );
        } catch (\Exception $e) {
            $this->log_error('Paysafe Checkout: Excption "' . $e->getMessage() . '"');
        }

        return false;
    }

    /**
     * Get the paysafe payment handle for a single use token
     *
     * @param string $merchant_reference
     *
     * @return array|null
     */
    private function get_payment_handle_by_merchant_reference(string $merchant_reference): ?array
    {
        try {
            // get the api connector
            $api_connector = new PaysafeApiCardPluginConnector();

            // call to paysafe for the delete paysafe token action
            $result = $api_connector->getPaymentHandleByMerchantReference(
                [
                    'merchantRefNum'            => $merchant_reference,
                ]
            );

            if (isset($result['paymentHandles']) && isset($result['paymentHandles'][0])) {
                return $result['paymentHandles'][0];
            }
        } catch (PaysafeException $e) {
            $this->log_error(
                'Paysafe Checkout: Paysafe Exception "'
                . $e->getMessage() . '"', array_merge($result ?? [], $e->getAdditionalData()));
        } catch (\Exception $e) {
            $this->log_error('Paysafe Checkut: Exception "' . $e->getMessage() . '"');
        }

        return null;
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
	 * If the subscription feature is enabled, add gateway support for subscription
	 *
	 * @return void
	 */
    public function maybe_init_subscriptions(): void
    {
        if ($this->is_subscriptions_support_enabled()) {
            $this->supports = array_merge(
                $this->supports,
                [
                    'subscriptions',
                    'subscription_cancellation',
                    'subscription_suspension',
                    'subscription_reactivation',
                    'subscription_amount_changes',
                    'subscription_date_changes',
                    'subscription_payment_method_change',
                    'subscription_payment_method_change_customer',
                    'subscription_payment_method_change_admin',
	                'subscription_payment_method_delayed_change',
                    'multiple_subscriptions',
                ]
            );
        }
    }
}
