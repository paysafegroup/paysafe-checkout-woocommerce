<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Paysafe\PhpSdk\Logging\Interfaces\PaysafeLoggerInterface;
use Paysafe\PhpSdk\Logging\PaysafeLoggerProvider;

abstract class WC_Gateway_Paysafe_Base extends WC_Payment_Gateway {
    protected ?PaysafeLoggerInterface $logger;

    // code switch to turn off certain features in this plugin
    const ALLOW_SUBSCRIPTION_SUPPORT = true;
    const ALLOW_REACT_ADMIN = true;
    const ALLOW_EXPRESS_PAYMENT_APPLE_PAY = true;
    const ALLOW_EXPRESS_PAYMENT_APPLE_PAY_PRODUCT_PAGE = false;
    const ALLOW_EXPRESS_PAYMENT_GOOGLE_PAY = true;
    const ALLOW_EXPRESS_PAYMENT_GOOGLE_PAY_PRODUCT_PAGE = false;

    const ALLOW_LPM_ACH = true;
    const ALLOW_LPM_EFT = true;
    const ALLOW_LPM_PAY_BY_BANK = false;
    const ALLOW_LPM_PAYPAL = true;
    const ALLOW_LPM_SIGHTLINE = false;
    const ALLOW_LPM_VIPPREFERRED = false;
    const ALLOW_LPM_VENMO = false;

    const PAYSAFE_RESPONSE_STATUS_COMPLETED = 'COMPLETED';
    const PAYSAFE_RESPONSE_STATUS_PENDING = 'PENDING';
    const PAYSAFE_RESPONSE_STATUS_PROCESSING = 'PROCESSING';
    const PAYSAFE_RESPONSE_STATUS_RECEIVED = 'RECEIVED';
    const PAYSAFE_RESPONSE_STATUS_INITIATED = 'INITIATED';

    const PAYSAFE_ACTION_HANDLE = 'HANDLE';
    const PAYSAFE_ACTION_AUTHORIZE = 'AUTHORIZE';
    const PAYSAFE_ACTION_SETTLE = 'SETTLE';
    const PAYSAFE_ACTION_VOID = 'VOID';
    const PAYSAFE_ACTION_REFUND = 'REFUND';

    const PAYSAFE_SINGLE_USE_TOKEN_ACTIVE = 'ACTIVE';

    const ORDER_META_KEY_LAST_ACTION = 'PAYSAFE_LAST_ACTION';

    const ORDER_META_KEY_VOID_ID = 'PAYSAFE_VOID_ID';
    const ORDER_META_KEY_SETTLEMENT_ID = 'PAYSAFE_SETTLEMENT_ID';
    const ORDER_META_KEY_REFUND_IDS = 'PAYSAFE_REFUND_IDS';
    const ORDER_META_KEY_PROCESS_PAYMENT_ID = 'PAYSAFE_PROCESS_PAYMENT_ID';
    const ORDER_META_KEY_SUBSCRIPTION_INITIAL_TID = 'PAYSAFE_SUBSCRIPTION_INITIAL_TID';
    const ORDER_META_KEY_SUBSCRIPTION_INITIAL_MUT = 'PAYSAFE_SUBSCRIPTION_MUT';
    const PAYSAFE_MERCHANT_REF_NUM = 'PAYSAFE_MRN';

    const USER_META_MERCHANT_CUSTOMER_ID = 'MERCHANT_CUSTOMER_ID';
    const USER_META_PAYSAFE_CUSTOMER_ID = 'PAYSAFE_CUSTOMER_ID';

    const PAYMENT_INTEGRATION_PAYSAFE_CHECKOUT = 'paysafe_checkout';
    const PAYMENT_INTEGRATION_PAYSAFE_JS = 'paysafe_js';
    const PAYMENT_INTEGRATION_DEFAULT = self::PAYMENT_INTEGRATION_PAYSAFE_JS;

    const PAYMENT_AUTHORIZATION_ONLY = 'AUTHORIZATION';
    const PAYMENT_SETTLE_PAYMENT = 'CAPTURE';

    const ORDER_STATUS_PENDING = 'pending';
    const ORDER_STATUS_PROCESSING = 'processing';
    const ORDER_STATUS_FAILED = 'failed';

    const ORDER_STATUS_CANCELLED = 'cancelled';
    const ORDER_STATUS_REFUNDED = 'refunded';
    const ORDER_STATUS_COMPLETED = 'completed';
    const ORDER_STATUS_ON_HOLD = 'on-hold';

    const CUSTOMER_OPERATION_ADD = 'ADD';

    const ENABLE_PAYMENT_METHOD_DEFAULT_VALUE = 'no';
    const SAVE_TOKEN_DEFAULT_VALUE = 'yes';

    const PAYSAFE_ERROR_MERCHANT_CUSTOMER_ALREADY_REGISTERED = 7505;
    const PAYSAFE_ERROR_CUSTOMER_ID_NOT_FOUND = 5269;
    const PAYSAFE_ERROR_INVALID_PAYMENT_TOKEN = 5068;
    const PAYSAFE_ERROR_DUPLICATE_CARD = 7503;

    const PAYSAFE_ORDER_ID_KEYWORD = '[Order ID ';

    const PAYSAFE_EXCEPTION_TEXT = 'PaySafe Checkout: Paysafe exception ';
    const PAYMENT_ICON_FILE = null;

    private static $has_subscription_callback = false;

    private $notices = [];

    private $allowed_payment_methods = [
        WC_Gateway_Paysafe::PAYMENT_GATEWAY_CODE,
        WC_Gateway_Paysafe_Apple_Pay::PAYMENT_GATEWAY_CODE,
        WC_Gateway_Paysafe_Google_Pay::PAYMENT_GATEWAY_CODE,
        WC_Gateway_Paysafe_Skrill::PAYMENT_GATEWAY_CODE,
        WC_Gateway_Paysafe_Neteller::PAYMENT_GATEWAY_CODE,
        WC_Gateway_Paysafe_Cash::PAYMENT_GATEWAY_CODE,
        WC_Gateway_Paysafe_Card::PAYMENT_GATEWAY_CODE,
	    WC_Gateway_Paysafe_Eft::PAYMENT_GATEWAY_CODE,
	    WC_Gateway_Paysafe_Ach::PAYMENT_GATEWAY_CODE,
	    WC_Gateway_Paysafe_Paypal::PAYMENT_GATEWAY_CODE,
	    WC_Gateway_Paysafe_Sightline::PAYMENT_GATEWAY_CODE,
	    WC_Gateway_Paysafe_Vippreferred::PAYMENT_GATEWAY_CODE,
	    WC_Gateway_Paysafe_Paybybank::PAYMENT_GATEWAY_CODE,
	    WC_Gateway_Paysafe_Venmo::PAYMENT_GATEWAY_CODE,
    ];

    private $lpm_gateways = [
        WC_Gateway_Paysafe_Skrill::PAYMENT_GATEWAY_CODE,
        WC_Gateway_Paysafe_Neteller::PAYMENT_GATEWAY_CODE,
        WC_Gateway_Paysafe_Cash::PAYMENT_GATEWAY_CODE,
        WC_Gateway_Paysafe_Card::PAYMENT_GATEWAY_CODE,
        WC_Gateway_Paysafe_Eft::PAYMENT_GATEWAY_CODE,
        WC_Gateway_Paysafe_Ach::PAYMENT_GATEWAY_CODE,
	    WC_Gateway_Paysafe_Paypal::PAYMENT_GATEWAY_CODE,
	    WC_Gateway_Paysafe_Sightline::PAYMENT_GATEWAY_CODE,
	    WC_Gateway_Paysafe_Vippreferred::PAYMENT_GATEWAY_CODE,
	    WC_Gateway_Paysafe_Paybybank::PAYMENT_GATEWAY_CODE,
	    WC_Gateway_Paysafe_Venmo::PAYMENT_GATEWAY_CODE,
    ];

    private $card_types_map = [
        'AM' => 'American Express',
        'DI' => 'Discover',
        'JC' => 'JCB',
        'MC' => 'Mastercard',
        'MD' => 'Maestro',
        'SO' => 'Solo',
        'VI' => 'Visa',
        'VD' => 'Visa Debit',
        'VE' => 'Visa Electron'
    ];

    private $payment_succesful_text = '';

    /**
     * Set up our payment gateway
     */
    public function __construct() {
        $this->id = WC_Gateway_Paysafe::PAYMENT_GATEWAY_CODE;
        $this->init_settings();

        $paysafe_wc_options = get_option(PAYSAFE_SETTINGS_KEYWORD, []);

        if (empty($paysafe_wc_options)) {
            $paysafe_wc_options = [];
        }

        if (($paysafe_wc_options['debug_log_enabled'] ?? null) === 'yes') {
            $this->logger = (new PaysafeLoggerProvider())->logger;
        } else {
            $this->logger = null;
        }

        $this->supports = [
            'products',
            'refunds',
            'add_payment_method',
	        'tokenization',
        ];

        if ($this->is_subscriptions_support_enabled()) {
            if ( ! self::$has_subscription_callback) {
                add_action(
                    'woocommerce_scheduled_subscription_payment_'.$this->id,
                    [$this, 'scheduled_subscription_payment'],
                    10,
                    2
                );

                self::$has_subscription_callback = true;
            }
        }

        $this->form_fields = PaysafeSettings::get_paysafe_settings();

        if ($this->is_current_section_loaded()) {
            // phpcs:disable WordPress.Security.NonceVerification.Recommended
            $admin_page = sanitize_text_field(wp_unslash($_GET['page'] ?? ''));
            // phpcs:enable WordPress.Security.NonceVerification.Recommended

            // Paysafe admin page setting scripts
            if ( ! self::ALLOW_REACT_ADMIN) {
                add_action('admin_head-woocommerce_page_'.$admin_page, [
                    $this,
                    'paysafe_woo_admin_head_scripts',
                ]);
            }
        }

        if (self::ALLOW_REACT_ADMIN) {
            add_action('rest_api_init', function () {
                register_rest_route('paysafe/v1', '/test-connection', [
                    'methods'             => 'POST',
                    'callback'            => [Paysafe_Admin_REST_Controller::class, 'test_connection'],
                    'permission_callback' => function () {
                        return current_user_can('manage_woocommerce');
                    },
                ]);

                register_rest_route('paysafe/v1', '/settings', [
                    'methods'             => ['GET', 'POST'],
                    'permission_callback' => fn() => current_user_can('manage_woocommerce'),
                    'callback'            => ['Paysafe_Admin_REST_Controller', 'handle_settings'],
                ]);
            });
        }

        // Add middleware to handle Order Status change
        add_action(
            'woocommerce_order_status_changed',
            [
                $this,
                'handle_order_status_changed'
            ],
            10,
            3
        );

        // Add middleware to handle manual order status changes
        add_action(
            'woocommerce_order_edit_status',
            [
                $this,
                'handle_order_status_edited'
            ],
            10,
            2
        );

        // register payment handle token response
        add_action(
            'woocommerce_api_paysafe_payment_response',
            [
                $this,
                'handle_payment_response'
            ]
        );

        // register get order data url
        add_action(
            'woocommerce_api_paysafe_get_order_pay_data',
            [
                $this,
                'handle_get_order_pay_data'
            ]
        );

        // register the log error url
        add_action(
            'woocommerce_api_paysafe_log_error',
            [
                $this,
                'handle_paysafe_log_error_data'
            ]
        );

        // register the express checkout url
        add_action(
            'woocommerce_api_paysafe_express_checkout',
            [
                $this,
                'handle_paysafe_express_checkout'
            ]
        );

        // delete paysafe customer data
        add_action(
            'woocommerce_api_paysafe_delete_paysafe_customer_data',
            [
                $this,
                'handle_delete_paysafe_customer_data'
            ]
        );

        // add a delete profile link to My Account profile page
        add_action('woocommerce_after_account_payment_methods',
            [
                $this,
                'paysafe_add_delete_profile_link'
            ]
        );

        // register webhook endpoint payload handle function
        add_action('woocommerce_api_paysafe_webhook',
            [
                $this,
                'handle_webhook_payload'
            ]
        );

        // register the express apple/google pay endpoint
        add_action(
            'woocommerce_api_paysafe_product_express_ag_pay_checkout',
            [
                $this,
                'handle_paysafe_product_express_ag_pay_checkout'
            ]
        );

        // add a filter to limit availability of pay and cancel buttons
        // on my-account page order section
        add_filter(
            'woocommerce_my_account_my_orders_actions',
            [
                $this,
                'my_account_my_orders_actions',
            ],
            10,
            2
        );

        // add a filter to decide whether the refund button will be shown or not
        // as certain payment methods don't support refunds
        add_filter(
            'woocommerce_admin_order_should_render_refunds',
            [
                $this,
                'allow_refunds_button',
            ],
            10,
            3
        );

        // set a max height for the gateway icons when in legacy checkout mode
        add_filter(
            'woocommerce_gateway_icon',
            [
                $this,
                'paysafe_get_icon'
            ],
            10,
            2
        );

        $this->payment_succesful_text = __('Payment successful', 'paysafe-checkout');
    }

    /**
     * Override method for generating admin settings table HTML
     *
     * @param array $form_fields
     * @param bool $echo
     *
     * @return string the HTML (can optionally echo as well)
     */
    public function generate_settings_html($form_fields = array(), $echo = true): string
    {
        if (!defined('PAYSAFE_HTML_LITERAL')) {
            define('PAYSAFE_HTML_LITERAL', '_html');
        }

        if(empty($form_fields)) {
            $form_fields = parent::get_form_fields();
        }

        $html_safe = '';
        foreach($form_fields as $k => $v) {
            $type = parent::get_field_type($v);
            if (empty($type)) {
                continue;
            }

            if ( in_array($k, [
                'test_environment_title',
                'live_environment_title',
                'shopping_experience_title',
	            'subscriptions_title_missing',
                'subscriptions_title',
                'webhook_title',
                'advanced_section',
            ], true) || str_starts_with($k, 'payment_methods_')) {
                $html_safe .= '<tr><td colspan="2"><hr></td></tr>';
            }

            if (method_exists($this, 'generate_' . $type . PAYSAFE_HTML_LITERAL )) {
                $html_safe .= $this->{'generate_' . $type . PAYSAFE_HTML_LITERAL}( $k, $v );
            } elseif (has_filter('woocommerce_generate_' . $type . PAYSAFE_HTML_LITERAL)) {
                $html_safe .= apply_filters(
                    'woocommerce_generate_' . $type . PAYSAFE_HTML_LITERAL,
                    '',
                    $k,
                    $v,
                    $this
                );
            } else {
                $html_safe .= $this->generate_text_html($k, $v);
            }
        }

        if ($echo) {
            echo wp_kses(
                $html_safe,
                array_merge(
                    wp_kses_allowed_html('post'),
                    $this->get_kses_allow_form_elements_array()
                )
            );
        }

        return $html_safe;
    }

    /**
     * Get all form elements for the WP kses escaper function
     *
     * @return array
     */
    private function get_kses_allow_form_elements_array(): array
    {
        return [
            'input' => [
                'type' => true,
                'class' => true,
                'name' => true,
                'id' => true,
                'style' => true,
                'value' => true,
                'placeholder' => true,
                'disabled' => true,
                'checked' => true,
            ],
            'select' => [
                'class' => true,
                'name' => true,
                'id' => true,
                'style' => true,
                'disabled' => true,
            ],
            'optgroup' => [
                'label' => true,
            ],
            'option' => [
                'class' => true,
                'value' => true,
                'selected' => true,
            ],
            'button'     => array(
                'disabled' => true,
                'name'     => true,
                'type'     => true,
                'value'    => true,
                'onclick'    => true,
            ),
        ];
    }

    /**
     * Plugin settings page setup
     */
    public function admin_options()
    {
        if (self::ALLOW_REACT_ADMIN) {
            global $hide_save_button;
            $hide_save_button = true;
        }

        include_once PAYSAFE_WOO_PLUGIN_PATH . '/admin/pages/admin_options.php';
    }

    /**
     * Init settings for gateways.
     */
    public function init_settings() {
        parent::init_settings();
        $this->enabled =
            ! empty( $this->settings['enabled'] )
            && 'yes' === $this->settings['enabled'] ? 'yes' : 'no';

        if (($this->settings['test_mode'] ?? '') == 'yes') {
            $this->notices[] =
                __(
                    'Test mode active! Please use test card numbers only, not real card details.',
                    'paysafe-checkout'
                );
        }
    }

    /**
     * Check whether current section is loaded
     */
    public function is_current_section_loaded()
    {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        $current_section = sanitize_text_field(wp_unslash($_GET['section'] ?? ''));
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
        if (empty($current_section)) {
            return false;
        }

        return
            (in_array($current_section, $this->allowed_payment_methods))
            || (strtolower($current_section) === strtolower(get_class($this)));
    }

    /**
     * Paysafe admin page setting scripts
     */
    public function paysafe_woo_admin_head_scripts()
    {
        $admin_javascript_handle = 'admin-paysafe-script';
        wp_register_script(
            $admin_javascript_handle,
            PAYSAFE_WOO_PLUGIN_URL . 'assets/admin/admin-paysafe.js',
            [],
            PAYSAFE_WOO_VERSION,
            true
        );
        wp_enqueue_script($admin_javascript_handle);

        $admin_css_handle = 'admin-paysafe-css';
	    wp_register_style(
		    $admin_css_handle,
		    PAYSAFE_WOO_PLUGIN_URL . 'assets/admin/admin-paysafe.css',
		    [],
		    PAYSAFE_WOO_VERSION
	    );
	    wp_enqueue_style($admin_css_handle);
    }

    public function enqueue_admin_react_app() {
        // Check we are on the WC settings page and on our gateway's section
        $screen = get_current_screen();
	    // phpcs:disable WordPress.Security.NonceVerification.Recommended
	    $section = sanitize_text_field( wp_unslash( $_GET['section'] ?? ''));
	    // phpcs:enable WordPress.Security.NonceVerification.Recommended

        if (
            $screen->id === 'woocommerce_page_wc-settings'
            && $section === $this->id
        ) {

            wp_enqueue_script(
                'paysafe-admin-react',
                PAYSAFE_WOO_PLUGIN_URL . 'assets/admin/gen/admin-paysafe.js',
                [ 'wp-element', 'wp-components', 'wp-data' ],
                PAYSAFE_WOO_VERSION,
                true
            );

            wp_localize_script('paysafe-admin-react', 'PaysafeSettingsData', [
                'apiUrl' => rest_url('paysafe/v1/settings'),
                'nonce'  => wp_create_nonce('wp_rest'),
                'plugin_url' => PAYSAFE_WOO_PLUGIN_URL . 'assets/admin/gen/'
            ]);

            wp_enqueue_style(
                'paysafe-admin-react-css',
                PAYSAFE_WOO_PLUGIN_URL . 'assets/admin/admin-paysafe.css',
                [],
                PAYSAFE_WOO_VERSION
            );

        }
    }

    /**
     * @return array
     */
    private function get_paysafe_order_pay_settings(
        string $integration_type
    ): array
    {
        return [
            'checkout_url' => wc_get_checkout_url(),
            'get_order_pay_data_url'    => add_query_arg(
                [
                    'payment_gateway'   => $this->id,
                ],
                WC()->api_request_url('paysafe_get_order_pay_data')
            ),
            'register_url'              => add_query_arg(
                [
                    'payment_gateway'   => $this->id,
                ],
                WC()->api_request_url('paysafe_payment_response')
            ),
            'integration_type'  => $integration_type,
            'cvv_verification' => $this->is_cvv_token_enabled(),
	        'nonce' => wp_create_nonce('paysafe_payment_response'),
        ];
    }

    /**
     * @return array
     */
    private function get_paysafe_apm_settings(): array
    {
        $customer_id = get_current_user_id();
        if (!$customer_id) {
            return [];
        }

        try {
            $current_customer = new WC_Customer($customer_id);
        } catch (Exception $e) {
            $current_customer = new WC_Customer();
        }

        $paysafe_wc_options = $this->get_paysafe_settings();

        return [
            'apm_url'   => wc_get_account_endpoint_url('add-payment-method'),
            'merchant_ref_num'  => $this->get_merchant_reference_number(),
            'merchant_descriptor' => ($paysafe_wc_options['merchant_descriptor'] ?? ''),
            'merchant_phone' => ($paysafe_wc_options['merchant_phone'] ?? ''),
            'billing_details'   => [
                'name'      => trim(
                        $current_customer->get_billing_first_name()
                        . ' class-wc-gateway-paysafe-base.php'
                        . $current_customer->get_billing_last_name()
                    ) ?? '',
                'first_name'=> $current_customer->get_billing_first_name(),
                'last_name' => $current_customer->get_billing_last_name(),
                'email'     => $current_customer->get_billing_email(),
                'country'   => $current_customer->get_billing_country(),
                'zip'       => $current_customer->get_billing_postcode(),
                'city'      => $current_customer->get_billing_city(),
                'state'     => $current_customer->get_billing_state(),
                'phone'     => $current_customer->get_billing_phone(),
                'street'    => trim(
                        $current_customer->get_billing_address_1()
                        . ' class-wc-gateway-paysafe-base.php'
                        . $current_customer->get_billing_address_2()
                    ) ?? '',
            ],
        ];
    }

    /**
     * @param string $integration_type
     *
     * @return array
     */
    protected function get_paysafe_checkout_settings(
        string $integration_type
    ): array
    {
        return [
            'checkout_url' => wc_get_checkout_url(),
            'register_url' => add_query_arg(
                [
                    'payment_gateway' => $this->id,
                ],
                WC()->api_request_url('paysafe_payment_response')
            ),
            'integration_type' => $integration_type,
            'cvv_verification' => $this->is_cvv_token_enabled(),
	        'nonce' => wp_create_nonce('paysafe_payment_response'),
        ];
    }

    /**
     * @param bool $is_test_mode
     * @param string $currency_code
     *
     * @return array
     */
    protected function get_paysafe_base_settings(
        bool $is_test_mode,
        string $currency_code
    ): array
    {
        $settings_prefix = $is_test_mode ? 'test_' : 'live_';

        $current_user = wp_get_current_user();
        $current_user_email = $current_user ? $current_user->user_email : null;

        $paysafe_wc_options = $this->get_paysafe_settings();

        $return = [
            'test_mode'         => $is_test_mode,
            'currency_code'     => $currency_code,
            'authorization'     => base64_encode($paysafe_wc_options[$settings_prefix . 'public_api_key']) ?? '',
            'card_account_id'   =>
                $this->get_account_id_for_gateway(WC_Gateway_Paysafe::PAYMENT_TYPE_CODE),
            'apple_pay_account_id'   =>
                $this->get_account_id_for_gateway(WC_Gateway_Paysafe_Apple_Pay::PAYMENT_TYPE_CODE),
            'google_pay_account_id'   =>
	            $this->get_account_id_for_gateway(WC_Gateway_Paysafe_Google_Pay::PAYMENT_TYPE_CODE),
            'skrill_account_id'   =>
                $this->get_account_id_for_gateway(WC_Gateway_Paysafe_Skrill::PAYMENT_TYPE_CODE),
            'neteller_account_id'   =>
                $this->get_account_id_for_gateway(WC_Gateway_Paysafe_Neteller::PAYMENT_TYPE_CODE),
            'paysafecash_account_id'   =>
                $this->get_account_id_for_gateway(WC_Gateway_Paysafe_Cash::PAYMENT_TYPE_CODE),
            'paysafecard_account_id'   =>
                $this->get_account_id_for_gateway(WC_Gateway_Paysafe_Card::PAYMENT_TYPE_CODE),
            'eft_account_id'   =>
                $this->get_account_id_for_gateway(WC_Gateway_Paysafe_Eft::PAYMENT_TYPE_CODE),
            'ach_account_id'   =>
                $this->get_account_id_for_gateway(WC_Gateway_Paysafe_Ach::PAYMENT_TYPE_CODE),
            'paysafepaypal_account_id'   =>
                $this->get_account_id_for_gateway(WC_Gateway_Paysafe_Paypal::PAYMENT_TYPE_CODE),
            'sightline_account_id'   =>
                $this->get_account_id_for_gateway(WC_Gateway_Paysafe_Sightline::PAYMENT_TYPE_CODE),
            'vippreferred_account_id'   =>
                $this->get_account_id_for_gateway(WC_Gateway_Paysafe_Vippreferred::PAYMENT_TYPE_CODE),
            'pay_by_bank_account_id'   =>
                $this->get_account_id_for_gateway(WC_Gateway_Paysafe_Paybybank::PAYMENT_TYPE_CODE),
            'venmo_account_id'   =>
                $this->get_account_id_for_gateway(WC_Gateway_Paysafe_Venmo::PAYMENT_TYPE_CODE),
            'merchant_descriptor' => ($paysafe_wc_options['merchant_descriptor'] ?? ''),
            'merchant_phone' => ($paysafe_wc_options['merchant_phone'] ?? ''),
            'locale'            => $this->get_paysafe_checkout_locale(),
            'user_email'        => $current_user_email,
            'details'           => [
                'subject'   => __('Skrill details subject', 'paysafe-checkout'),
                'message'   => __('Skrill details message', 'paysafe-checkout'),
            ],
            'consumer_id'       => $this->get_consumer_id(),
            'consumer_id_encrypted' => $this->get_encrypted_consumer_id(),
            'log_errors'    => $this->is_error_logging_enabled(),
            'google_pay_issuer_country' => $this->settings[ 'google_pay_issuer_country' . $this->test_live_suffix() ] ?? '',
        ];

        if ($this->is_error_logging_enabled()) {
            $return['log_error_endpoint'] = add_query_arg(
                [
                    'payment_gateway'   => $this->id,
                ],
                WC()->api_request_url('paysafe_log_error')
            );
        }

        return $return;
    }

    /**
     * Register all client side scripts and styles
     *
     * @return void
     */
    public function enqueue_paysafe_scripts()
    {
        if (!defined('PAYSAFE_LEGACY_KEYWORD')) {
            define('PAYSAFE_LEGACY_KEYWORD', '-legacy');
            define('PAYSAFE_LOCAL_SETTINGS_KEYWORD', '_settings');
        }

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

        if (
            $this->id === WC_Gateway_Paysafe::PAYMENT_GATEWAY_CODE
            && $this->is_available()
            && (is_checkout() || is_checkout_pay_page() || is_add_payment_method_page() || is_product())
        ) {
            // register the general css style
            $general_payment_style = 'wc-general-' . $this->id . '-payments-style';
            wp_register_style(
                $general_payment_style,
	            PAYSAFE_WOO_PLUGIN_URL . 'assets/css/general-paysafe.css',
                [],
                PAYSAFE_WOO_VERSION
            );
            wp_enqueue_style($general_payment_style);
        }

        // paysafe checkout tool
        $script_assets_base_url = 'https://hosted.paysafe.com/';
        if ($is_test_mode) {
            $script_assets_base_url = 'https://hosted.test.paysafe.com/';
        }

        wp_register_script(
	        $this->id . '-checkout',
            $script_assets_base_url . 'checkout/v2/paysafe.checkout.min.js',
            [],
            PAYSAFE_WOO_VERSION,
            true
        );
        wp_enqueue_script($this->id . '-checkout');

        // paysafe minimized external javascript help tool
        wp_register_script(
            $this->id . '-js',
            $script_assets_base_url . 'js/v1/latest/paysafe.min.js',
            [],
            PAYSAFE_WOO_VERSION,
            true
        );
        wp_enqueue_script($this->id . '-js');

	    if (is_checkout() && !is_checkout_pay_page()) {
            // checkout specific settings
            $checkout_settings = $this->get_paysafe_checkout_settings($integration_type);

            // paysafe legacy checkout support
            wp_register_script(
                $this->id . PAYSAFE_LEGACY_KEYWORD,
                PAYSAFE_WOO_PLUGIN_URL . 'resources/js/legacy-checkout/paysafe-legacy-checkout.js',
                [],
                PAYSAFE_WOO_VERSION,
                true,
            );
            wp_localize_script(
                $this->id . PAYSAFE_LEGACY_KEYWORD, $this->id . PAYSAFE_LOCAL_SETTINGS_KEYWORD,
                array_merge($base_settings, $checkout_settings)
            );
            wp_enqueue_script($this->id . PAYSAFE_LEGACY_KEYWORD);
        }

        if (is_add_payment_method_page() && $this->is_save_token_enabled()) {
            $apm_settings = $this->get_paysafe_apm_settings();

            // paysafe legacy add payment method support
            wp_register_script(
                $this->id . '-apm',
                PAYSAFE_WOO_PLUGIN_URL . 'resources/js/add-payment-method/paysafe-legacy-apm.js',
                [],
                PAYSAFE_WOO_VERSION,
                true,
            );
            wp_localize_script(
                $this->id . '-apm', $this->id . PAYSAFE_LOCAL_SETTINGS_KEYWORD,
                array_merge($base_settings, $apm_settings)
            );
            wp_enqueue_script($this->id . '-apm');
        }

        if (is_checkout_pay_page()) {
            $order_pay_settings = $this->get_paysafe_order_pay_settings($integration_type);

            // paysafe legacy add payment method support
            wp_register_script(
                $this->id . '-op',
                PAYSAFE_WOO_PLUGIN_URL . 'resources/js/my-account-order-pay/paysafe-legacy-op.js',
                [],
                PAYSAFE_WOO_VERSION,
                true,
            );
            wp_localize_script(
                $this->id . '-op', $this->id . PAYSAFE_LOCAL_SETTINGS_KEYWORD,
                array_merge($order_pay_settings, $base_settings)
            );
            wp_enqueue_script($this->id . '-op');
        }
    }

    /**
     * Get the paysafe settings the lazy way
     *
     * @return array
     */
    public function get_paysafe_settings(): array
    {
        if (is_array($this->settings)) {
            return $this->settings;
        }

        return $this->settings = get_option(PAYSAFE_SETTINGS_KEYWORD, []);
    }

    /**
     * Return the merchant reference number to be used
     *
     * @param int|null $order_id
     *
     * @return string
     */
    public function get_merchant_reference_number(int $order_id = null): string
    {
        if ($order_id) {
            $merchant_reference_number = $order_id . '.' . microtime(true);

	        // save the merchant reference number for webhook identification
            $this->save_merchant_reference_number_for_order($order_id, $merchant_reference_number);
            return $merchant_reference_number;
        }

	    return $this->safe_id();
    }

    /**
     * Check whether order payment can be voided or not
     *
     * @param int $order_id
     *
     * @return bool
     */
    public function can_void_payment(int $order_id): bool
    {
        // is the order paid with our payment gateway
        if (!$this->is_paysafe_payment($order_id, $this->id)) {
            return false;
        }

        // check last payment command, should be authorization
        $last_payment_command = get_post_meta($order_id, self::ORDER_META_KEY_LAST_ACTION, true) ?? null;
        if (
            // can_void_payment skip, last payment command is not authorization
            self::PAYSAFE_ACTION_AUTHORIZE !== $last_payment_command

            // Can\'t get Payment ID
            || !$this->get_payment_id($order_id)

            // Can\'t get Settlement ID
            || $this->get_settlement_id($order_id)
        ) {
            return false;
        }

        return true;
    }

    /**
     * Check whether order payment can be settled or not
     *
     * @param int $order_id
     *
     * @return bool
     */
    public function can_settle_payment(int $order_id): bool
    {
        return $this->can_void_payment($order_id);
    }

    /**
     * Check whether order payment can be refunded or not
     *
     * @param int $order_id
     *
     * @return bool
     */
    public function can_refund_payment(int $order_id): bool
    {
        // is the order paid with our payment gateway
        if (
            !$this->is_paysafe_payment($order_id, $this->id)
            || !$this->get_settlement_id($order_id)     // Can\'t get Settlement ID
        ) {
            return false;
        }

        $order = wc_get_order($order_id);
        if ( ! ($order->get_remaining_refund_amount() > 0) ) {
            // get_remaining_refund_amount is <= 0
            return false;
        }

        return true;
    }

    /**
     * Check whether order can be refunded or not
     *
     * @param $order
     *
     * @return bool
     */
    public function can_refund_order($order): bool
    {
        if (
            !$order
            || !is_object($order)

            // is the order paid with our payment gateway
            ||!$this->is_paysafe_payment($order->get_id(), $this->id)

            // Parent can_refund_order returned false
            || !parent::can_refund_order($order)
        ) {
            return false;
        }

        return $this->can_refund_payment($order->get_id());
    }

    /**
     * Return the payment_id from local storage
     *
     * @param int $order_id
     *
     * @return string|null
     */
    public function get_payment_id(int $order_id): ?string
    {
        $payment_id = get_post_meta($order_id, self::ORDER_META_KEY_PROCESS_PAYMENT_ID, true) ?? null;

        if (!empty($payment_id)) {
            return $payment_id;
        }

        return null;
    }

    /**
     * Return the settlement_id from local storage
     *
     * @param int $order_id
     *
     * @return string|null
     */
    public function get_settlement_id(int $order_id): ?string
    {
        $settlement_id = get_post_meta($order_id, self::ORDER_META_KEY_SETTLEMENT_ID, true);
        if (!empty($settlement_id)) {
            return $settlement_id;
        }

        return null;
    }

    /**
     * Is the order paid with one of our payment gateways?
     *
     * @param int $order_id
     * @param string|null $current_gateway
     *
     * @return bool
     */
    protected function is_paysafe_payment(int $order_id, string $current_gateway = null): bool
    {
        $order = wc_get_order($order_id);

        if (!empty($current_gateway)) {
            return $order->get_payment_method() === $current_gateway;
        }

        return in_array($order->get_payment_method(), $this->allowed_payment_methods);
    }

    /**
     * Handle order status changes
     *
     * @param int $order_id
     * @param string $old_status
     * @param string $new_status
     *
     * @return void
     */
    public function handle_order_status_changed(int $order_id, string $old_status, string $new_status): void
    {
        if (!$this->is_paysafe_payment($order_id, $this->id)) {
            // the status change is for an order with a payment method
            // that does not belong to us,
            // we shouldn't do anything here
            return;
        }

        $this->log_debug(
            self::PAYSAFE_ORDER_ID_KEYWORD
            . $order_id
            . '] Paysafe Checkout: Order status changed', [
            'old_status' => $old_status,
            'new_status' => $new_status,
        ]);

        if ($new_status === self::ORDER_STATUS_CANCELLED && null === $this->get_settlement_id($order_id)) {
            $this->log_debug(
                self::PAYSAFE_ORDER_ID_KEYWORD
                . $order_id
                . '] Paysafe Checkout: Calling void_paysafe_payment');

            $this->void_paysafe_payment($order_id);
        }
    }

    /**
     * Handle the response sent from the checkout page
     * Expected: payment handle token from the POST
     *
     * @return void
     */
    public function handle_payment_response(): void
    {
        $error_message = null;

        $response = wp_kses_post_deep(json_decode(file_get_contents('php://input'), true));
        $order_pay_page = (bool)($response['order_pay_page'] ?? false);
	    $change_subs_payment_method = (bool)($response['change_subs_payment_method'] ?? false);
        $update_all_subs = (bool)($response['update_all_subs'] ?? false);

	    $order_id = $response['orderId'] ?? null;

	    // check if the request is valid
	    $nonce = $response['nonce'] ?? null;
	    if ( ! wp_verify_nonce( $nonce, 'paysafe_payment_response' ) ) {
		    $error_message = __('Invalid nonce. Please refresh the page and try again', 'paysafe-checkout');

		    $this->log_debug(
			    self::PAYSAFE_ORDER_ID_KEYWORD . $order_id . '] Paysafe Checkout: Invalid noncer',
                $response ?? []
		    );

		    $result = [
			    'status' => 'error',
			    'redirect_url'  => wc_get_checkout_url(),
			    'error_message' => $error_message,
		    ];

		    wp_send_json($result);
		    wp_die();
	    }

        $this->log_debug('Paysafe Checkout: handle_payment_response', $response ?? []);

        $paysafe_payment_method = $response['paymentMethod'] ?? null;       // CARD

        // the payment-handle-token to use for a payment call
        $payment_handle_token = $original_sut = $response['paymentHandleToken'] ?? null;

        // the amount of the order (without decimals) ex. 1150 (for )11.50 USD)
        $amount = $response['amount'] ?? null;
        $merchant_ref_num = $response['merchantRefNum'] ?? null;
        $customer_operation = $response['customerOperation'] ?? null;

        $save_card = (bool)($response['save_card'] ?? false);

        $this->log_debug(
            self::PAYSAFE_ORDER_ID_KEYWORD . $order_id . '] Paysafe Checkout: Handling payment response',
            $response ?? []
        );

        $order = wc_get_order($order_id);
	    $is_subscription = false;

	    $success = false;
        if ($order && $payment_handle_token) {
	        $current_user_id = get_current_user_id();
	        if (
                $order->get_customer_id() !== $current_user_id) {
		        $this->log_debug(
			        self::PAYSAFE_ORDER_ID_KEYWORD . $order_id . '] Paysafe Checkout: Current user does not match order customer',
                    [
                        'order_customer_id' => $order->get_customer_id(),
                        'current_user_id'   => $current_user_id,
                    ]
		        );

		        $error_message = __(
			        'Payment failed with the current payment option.',
			        'paysafe-checkout'
		        );

		        $result = [
			        'status' => 'error',
			        'redirect_url'  => wc_get_checkout_url(),
			        'error_message' => $error_message,
		        ];

		        wp_send_json($result);
		        wp_die();
	        }

            update_post_meta(
                $order_id,
                self::ORDER_META_KEY_LAST_ACTION,
                self::PAYSAFE_ACTION_HANDLE);

            $is_save_card = $save_card &&
                             $this->id === WC_Gateway_Paysafe::PAYMENT_GATEWAY_CODE &&
                             $this->is_save_token_enabled() &&
                             $this->get_integration_type() === self::PAYMENT_INTEGRATION_PAYSAFE_JS;
            $is_subscription = $this->is_order_part_of_subscription($order_id);

            $transaction_id = null;
            if ($is_save_card || $is_subscription) {
                // exchange the single use payment handle that we received from paysafe js
                // into a multi use payment handle
	            $multi_use_token_data = $this->handle_suph_to_muph_exchange($payment_handle_token);

                $multi_use_token = $multi_use_token_data['paymentHandleToken'] ?? null;

                if ($multi_use_token) {
	                $this->save_paysafe_customer_token(
                        $multi_use_token,
                        $multi_use_token_data['card'] ?? []
                    );
                    $payment_handle_token = $multi_use_token;
                }
            }

            if ($is_subscription && $change_subs_payment_method) {
	            // add order note that payment method is changed for this subscription
	            $order->add_order_note(
		            sprintf(
		            /* translators: %s is replaced by the name of the payment method */
			            __(
				            'Payment method changed to %s.',
				            'paysafe-checkout'
			            ),
			            $this->get_payment_gateway_public_name( $order->get_payment_method() ),
		            )
	            );
            } else {
	            // add order note with payment handle
	            $order->add_order_note(
		            sprintf(
		            /* translators: %s is replaced by the name of the payment method */
			            __(
				            'Payment initiated with payment method %s.',
				            'paysafe-checkout'
			            ),
			            $this->get_payment_gateway_public_name( $order->get_payment_method() ),
		            )
	            );
            }

            // call paysafe api to make a payment api call
            if ($is_subscription) {
                if ($change_subs_payment_method) {
	                list( $success, $error_message ) = $this->handle_process_paysafe_verification(
		                $order_id,
		                $original_sut,
	                );
                } else {
	                list( $success, $error_message ) = $this->handle_process_paysafe_payment(
		                $order_id,
		                $payment_handle_token,
		                $amount
	                );
                }

	            if ($success) {
		            // we treat this point as an initial payment for this subscription
		            // even in cases when the payment happens on renewal
                    if (!$transaction_id) {
	                    $transaction_id = get_post_meta(
		                    $order_id,
		                    self::ORDER_META_KEY_PROCESS_PAYMENT_ID,
		                    true
	                    );
                    }

		            $original_order_id = $this->get_initial_subscription_order_id($order_id);
                    if ($transaction_id) {
	                    update_post_meta(
		                    $original_order_id,
		                    self::ORDER_META_KEY_SUBSCRIPTION_INITIAL_TID,
		                    $transaction_id
	                    );
                    }
		            update_post_meta(
                        $original_order_id,
                        self::ORDER_META_KEY_SUBSCRIPTION_INITIAL_MUT,
                        $multi_use_token
                    );
	            }

                if ($change_subs_payment_method && $update_all_subs) {
                    $this->update_all_subs_payment_method($order_id, $payment_handle_token, $transaction_id);
                }
            } else {
                list($success, $error_message) = $this->handle_process_paysafe_payment(
                    $order_id,
                    $payment_handle_token,
                    $amount,
                    $customer_operation,
                    $merchant_ref_num,
                    null,
                    $order_pay_page
                );
            }
        }

        if ($success) {
            if ($is_subscription && $change_subs_payment_method) {
	            $this->log_debug(
		            self::PAYSAFE_ORDER_ID_KEYWORD
		            . $order_id . '] Paysafe Checkout: Change Payment Method for Subscription Successful'
	            );
            } else {
	            $this->log_debug(
		            self::PAYSAFE_ORDER_ID_KEYWORD
		            . $order_id . '] Paysafe Checkout: Process Payment Response Successful'
	            );
            }

            $order = wc_get_order($order_id);

            $result = [
                'status' => 'success',
                'redirect_url'  => $this->get_return_url($order),
            ];

            if ($order_pay_page) {
                // add success notice on the my-account order-view page
                if ($is_subscription && $change_subs_payment_method) {
	                wc_add_notice(
                        __('Payment method changed successfully', 'paysafe-checkout'),
                        'success'
                    );
                } else {
	                wc_add_notice( $this->payment_succesful_text, 'success' );
                }

                // overwrite the payment method
                try {
                    // update order payment method title
                    $order->set_payment_method_title(
                        $this->get_payment_gateway_public_name(
                            $paysafe_payment_method
                        )
                    );
                    $order->save();
                } catch (\Exception $e) {
                    $this->log_debug(
                        self::PAYSAFE_ORDER_ID_KEYWORD
                        . $order_id
                        . '] Paysafe Checkout: Process Payment has no order');
                }
            }
        } else {
            $this->log_debug(
                self::PAYSAFE_ORDER_ID_KEYWORD
                . $order_id
                . '] Paysafe Checkout: Process Payment Response Error');

            $result = [
                'status' => 'error',
                'redirect_url'  => wc_get_checkout_url(),
                'error_message' => $error_message,
            ];

	        if ($order_pay_page && $error_message) {
		        // return a redirect page to the checkout pay page
		        // and add an error to show that payment failed
		        wc_add_notice(
			        $error_message,
			        'error'
		        );
	        }
        }

	    $this->check_paysafe_checkout_saved_cards();

        wp_send_json($result);
        wp_die();
    }

	/**
     * Check Paysafe API and remove all local stored cards that are not found on Paysafe API
     *
	 * @return void
	 */
    public function check_paysafe_checkout_saved_cards(): void
    {
	    // sync saved cards from PaysafeCheckout with our current cards
	    if (
		    $this->id === WC_Gateway_Paysafe::PAYMENT_GATEWAY_CODE &&
		    $this->is_save_token_enabled() &&
		    $this->get_integration_type() === self::PAYMENT_INTEGRATION_PAYSAFE_CHECKOUT
	    ) {
            $current_user_id = get_current_user_id();;
            if (!$current_user_id) {
                return;
            }

		    $saved_tokens = WC_Payment_Tokens::get_customer_tokens( $current_user_id, $this->id );

            if (!count($saved_tokens)) {
                return;
            }

		    $this->log_debug('Paysafe Checkout: Checking Saved Cards for customer');

		    $customer_data = $this->get_paysafe_customer_data();
            foreach ($saved_tokens as $token) {
                $token_data = $this->get_saved_card_data($token->get_token());
                $token_payment_handle_id = $token_data['id'] ?? '';
                $found = false;
	            foreach ( $customer_data['paymentHandles'] ?? [] as $payment_handle_data ) {
		            if ( ( $payment_handle_data['id'] ?? '' ) === $token_payment_handle_id ) {
                        $found = true;
			            break;
		            }
	            }

                if (!$found) {
	                $this->log_debug('Paysafe Checkout: Deleting token #' . $token->get_id());
                    $token->delete();
                }
            }
	    }
    }

    /**
     * Handle the process payment call and return the result
     *
     * @param string $order_id
     * @param int $amount
     * @param bool $capture_payment
     * @param string $payment_handle_token
     * @param string $merchant_ref
     * @param string|null $customer_operation
     *
     * @return array
     *
     * @throws PaysafeException
     */
    private function get_process_payment_result(
        string $order_id,
        int $amount,
        bool $capture_payment,
        string $payment_handle_token,
        string $merchant_ref,
        string $customer_operation = null
    ): array
    {
        $params = [
            'merchantRefNum' => $merchant_ref,
            'amount' => $amount,
            'currencyCode' => get_woocommerce_currency(),
            'settleWithAuth' => $capture_payment,
            'paymentHandleToken' => $payment_handle_token,
        ];

        if (self::CUSTOMER_OPERATION_ADD === $customer_operation) {
            $params['merchantCustomerId'] = $this->get_merchant_customer_id();
        }

        if ($this->is_subscriptions_support_enabled()) {
            if (wcs_order_contains_subscription($order_id)) {
                $params['storedCredential'] = [
                    'type'       => 'RECURRING',
                    'occurrence' => 'INITIAL',
                ];
            }

            if (wcs_order_contains_renewal($order_id)) {
	            $params['storedCredential'] = [
		            'type'                 => 'RECURRING',
		            'occurrence'           => 'SUBSEQUENT',
	            ];

	            $initial_transaction_id = $this->get_subscription_order_initial_transaction_id(wc_get_order($order_id));
	            if ($initial_transaction_id) {
	                $params['storedCredential']['initialTransactionId'] = $initial_transaction_id;
                }
            }
        }

        $api_connector = new PaysafeApiCardPluginConnector();
        return $api_connector->handleProcessPayment($params);
    }

    /**
     * Handle the settlements part of the process payment response
     *
     * @param string $order_id
     * @param array $settlements
     * @param int $amount
     *
     * @return void
     */
    private function handle_process_payment_settlements(
        string $order_id,
        array $settlements,
        int $amount
    ): void
    {
        if (!count($settlements)) {
            return;
        }

        $order = wc_get_order($order_id);
	    $currency_code = $order->get_currency();

        $settlement = $settlements;
        $settlement_id = $settlement['id'] ?? null;
        if (empty($settlement_id)) {
            // multiple settlements? Docs are unclear about this
            $settlement = array_pop($settlements);
            $settlement_id = $settlement['id'] ?? null;
        }

        $accepted_statuses = [
            self::PAYSAFE_RESPONSE_STATUS_COMPLETED,
            self::PAYSAFE_RESPONSE_STATUS_PENDING,
            self::PAYSAFE_RESPONSE_STATUS_INITIATED,
        ];
        $status = $settlement['status'] ?? null;

        if (!empty($settlement_id) && in_array($status, $accepted_statuses, true)) {
            $this->log_debug(
                self::PAYSAFE_ORDER_ID_KEYWORD
                . $order_id
                . '] Paysafe Checkout: Capture successful');

            update_post_meta($order_id, self::ORDER_META_KEY_SETTLEMENT_ID, $settlement_id);
            update_post_meta(
                $order_id,
                self::ORDER_META_KEY_LAST_ACTION,
                self::PAYSAFE_ACTION_SETTLE);

            // Settlement successful
            $order->update_status(self::ORDER_STATUS_PROCESSING);

            $this->log_debug(
                self::PAYSAFE_ORDER_ID_KEYWORD . $order_id . '] Paysafe Checkout: Order status is Processing'
            );

            // add order note with settlement id
            $order->add_order_note(
                sprintf(
                    /* translators: %1$s is replaced by currency and amount of the authorization */
                    __('Funds capture of %1$s completed successfully.','paysafe-checkout') .
                    ' ' .
                    /* translators: %2$s is replaced by ID of the settlement */
                    __('Settlement ID: %2$s. ', 'paysafe-checkout') .
                    ' ' .
                    __(
                        'To cancel the settlement, change the order status to “Cancelled”.',
                        'paysafe-checkout'
                    ) . ' ' .
                    __(
                        'To issue a refund, click the “Refund” button and select “Refund via Paysafe”.',
                        'paysafe-checkout'
                    ) . ' ' .
                    __(
                        'Note: Refunds are available only after the payment has been captured and fully settled.',
                        'paysafe-checkout'
                    ),
                    $currency_code . ' ' . number_format_i18n($amount / 100, 2),
                    $settlement_id
                )
            );
        } elseif (!empty($settlement_id) && $status === self::PAYSAFE_RESPONSE_STATUS_RECEIVED) {
            // in this case, the capture is still in the process of happening,
            // we can't really say that it's done
            $this->log_debug(
                self::PAYSAFE_ORDER_ID_KEYWORD
                . $order_id
                . '] Paysafe Checkout: Capture received, awaiting completion');
            update_post_meta($order_id, self::ORDER_META_KEY_SETTLEMENT_ID, $settlement_id);

            // add order note with settlement id
            $order->add_order_note(
                sprintf(
                    __('Payment is processing.', 'paysafe-checkout') . ' ' .
                    /* translators: %s is replaced by ID of the settlement */
                    __('Payment ID: %s.', 'paysafe-checkout') . ' ' .
                    __(
                        'If you wish to refund the payment, you must change the order status to Cancelled.',
                        'paysafe-checkout'
                    ) . ' ' .
                    __(
                        'Refunds are not available until the payment is fully captured until the transaction is settled.',
                        'paysafe-checkout'
                    ),
                    $settlement_id
                )
            );
        } else {
            $this->log_debug(
                self::PAYSAFE_ORDER_ID_KEYWORD
                . $order_id
                . '] Paysafe Checkout: Capture failed with status ' . $status,
                $settlement);
        }
    }

    /**
     * Process payment for an order
     * after getting the payment handle token from checkout popup
     *
     * @param int $order_id
     * @param string $payment_handle_token
     * @param int $amount
     * @param string|null $customer_operation
     * @param string|null $merchant_ref
     * @param WC_Payment_Token|null $token
     * @param bool $skip_cart_operations
     *
     * @return array
     */
    public function handle_process_paysafe_payment(
        int $order_id,
        string $payment_handle_token,
        int $amount,
        string $customer_operation = null,
        string $merchant_ref = null,
        WC_Payment_Token $token = null,
        bool $skip_cart_operations = false
    ): array
    {
        $error_message = null;
        $currency_code = get_woocommerce_currency();

        $this->log_debug(
            self::PAYSAFE_ORDER_ID_KEYWORD
            . $order_id . '] Paysafe Checkout: Handle process Paysafe payment', [
            'payment_handle_token' => $payment_handle_token, 'amount' => $amount
        ]);

        $order = wc_get_order($order_id);
        if (!$order) {
            $this->log_notice(self::PAYSAFE_ORDER_ID_KEYWORD
                . $order_id . '] Paysafe Checkout: Order not found');

            return [false, $error_message];
        }

        $capture_payment = ($this->settings['payment_action' . $this->test_live_suffix()] ?? null) === self::PAYMENT_SETTLE_PAYMENT;
        if (in_array($order->get_payment_method(), $this->lpm_gateways, true)) {
            $capture_payment = true;
        }

        if (
            !$capture_payment &&
            $this->is_order_part_of_subscription($order_id) &&
            $this->is_subscriptions_capture_enabled())
        {
            $capture_payment = true;
        }

        if (null === $merchant_ref) {
            $merchant_ref = $this->get_merchant_reference_number($order_id);
        }

        try {
            $result = $this->get_process_payment_result(
                $order_id,
                $amount,
                $capture_payment,
                $payment_handle_token,
                $merchant_ref,
                $customer_operation
            );

            $this->log_debug(
                self::PAYSAFE_ORDER_ID_KEYWORD
                . $order_id
                . '] Paysafe Checkout: Handle Process Payment Result', [
                'order_id' => $order_id,
                'result' => $result
            ]);

            $payment_id = $result['id'] ?? null;

            $accepted_statuses = [
                self::PAYSAFE_RESPONSE_STATUS_PROCESSING,
                self::PAYSAFE_RESPONSE_STATUS_RECEIVED,
                self::PAYSAFE_RESPONSE_STATUS_PENDING,
                self::PAYSAFE_RESPONSE_STATUS_COMPLETED,
            ];

            $status = $result['status'] ?? null;

            if (empty($payment_id) || !in_array($status, $accepted_statuses, true)) {
                $this->log_error(
                    self::PAYSAFE_ORDER_ID_KEYWORD . $order_id . '] Paysafe Checkout: '
                    . 'Process Payment call returned unknown status "' . $status . '"', [
                    'order_id' => $order_id,
                    'status' => $status,
                    'payment_handle_token' => $payment_handle_token,
                    'amount' => $amount
                ]);

                throw new PaysafeException(
                    sprintf(
                    /* translators: %s is replaced by status returned */
                        __('Process Payment call returned unknown status %s', 'paysafe-checkout'),
                        $status ?? '-'
                    ),
                    PaysafeException::PROCESS_PAYMENT_CALL_UNKNOWN_STATUS
                );
            }

            if (!$capture_payment) {
                //Authorization successful
                $order->update_status(self::ORDER_STATUS_ON_HOLD);

                // add order note with authorization id
                $order->add_order_note(
                    sprintf(
                        /* translators: %s is replaced by currency and amount of the authorization */
                        __('Payment authorization of %s successfully completed.', 'paysafe-checkout') . ' ' .
                        /* translators: %s is replaced by ID of the authorization */
                        __('Authorization ID: %s.', 'paysafe-checkout') . ' ' .
                        __('To complete the payment, please change the order status to “Processing” - this will capture the authorized amount.', 'paysafe-checkout') . ' ' .
                        __('If you want to capture only part of the authorized amount, please refer to your Merchant Manual.', 'paysafe-checkout') . ' ' .
                        __(
                            'If you want to cancel the payment, change the order status to “Cancelled”, which will void the authorization.',
                            'paysafe-checkout'
                        ) . ' ' .
                        __(
                            'Note: Refunds are only available after the payment has been captured and fully settled.',
                            'paysafe-checkout'
                        ),
	                    $currency_code . ' ' . number_format_i18n((int)$amount / 100, 2),
                        $payment_id
                    )
                );
            }

            $this->log_debug(
                self::PAYSAFE_ORDER_ID_KEYWORD
                . $order_id
                . '] Paysafe Checkout: Authorization successful');

            if (isset($result['customerId'])) {
                $this->save_paysafe_customer_id($result['customerId']);
            }
            if (isset($result['multiUsePaymentHandleToken'])) {
                $this->save_paysafe_customer_token(
                    $result['multiUsePaymentHandleToken'],
                    $result['card'] ?? null);
            }

            if (!$skip_cart_operations) {
                // Reduce stock levels
                wc_reduce_stock_levels($order->get_id());

                // Empty the cart
                WC()->cart->empty_cart();
            }

            update_post_meta($order_id, self::ORDER_META_KEY_PROCESS_PAYMENT_ID, $payment_id);
            update_post_meta(
                $order_id,
                self::ORDER_META_KEY_LAST_ACTION,
                self::PAYSAFE_ACTION_AUTHORIZE
            );

            if ($capture_payment) {
                $this->log_debug(
                    self::PAYSAFE_ORDER_ID_KEYWORD
                    . $order_id
                    . '] Paysafe Checkout: Capture with settlement');

                $this->log_debug(
                    self::PAYSAFE_ORDER_ID_KEYWORD . $order_id
                    . '] Paysafe Checkout: Settlement data', ($result['settlements'] ?? [])
                );

                // save the capture payment (settlement) data
                $this->handle_process_payment_settlements($order_id, (array)($result['settlements'] ?? []), $amount);
            } else {
                $this->log_debug(
                    self::PAYSAFE_ORDER_ID_KEYWORD . $order_id
                    . '] Paysafe Checkout: Authorization without capture'
                );
            }

            return [true, $error_message];
        }
        catch (PaysafeException $e) {
	        $error_message = $e->getMessage();

            $this->log_error(
                self::PAYSAFE_ORDER_ID_KEYWORD . $order_id
                . '] Paysafe Checkout: Payment Failed (Paysafe Exception)', [
                'order_id' => $order_id,
                'exception' => $e->getMessage(),
                'paysafe_api_error'   => $e->getAdditionalData(),
            ]);

            // Payment failed
            $order->add_order_note(
                sprintf(
                /* translators: %s is replaced by the message */
                    __('ERROR processing payment: %s', 'paysafe-checkout'),
                    $e->getMessage()
                )
            );

            $additional_data = $e->getAdditionalData();
            if (($additional_data['error_code'] ?? null) === self::PAYSAFE_ERROR_INVALID_PAYMENT_TOKEN && $token) {
                // remove this customer token from local storage,
                // it has been invalidated
                $token->delete();
            }
        }
        catch (\Exception $e) {
	        $error_message = $e->getMessage();

            $this->log_error(
                self::PAYSAFE_ORDER_ID_KEYWORD
                . $order_id
                . '] Paysafe Checkout: Payment Failed (Exception) "' . $e->getMessage() . '"');
        }

        return [
            false,
            $error_message
        ];
    }

    /**
     * Is Paysafe plugin activated
     *
     * @return bool
     */
    public function is_available(): bool
    {
        if ($this->is_subscriptions_support_enabled()
            && wcs_cart_contains_renewal()
            && $this->id !== WC_Gateway_Paysafe::PAYMENT_GATEWAY_CODE) {
            return false;
        } else {
            return parent::is_available();
        }
    }

    /**
     * Is this payment method enabled?
     *
     * @param string $payment_method
     *
     * @return bool
     */
    public function is_payment_method_enabled(string $payment_method): bool
    {
        // get the current settings
        $settings = $this->get_paysafe_settings();
        $currency_code = get_woocommerce_currency();

        return ($settings['payment_method_' . strtoupper($currency_code) . '_' . $payment_method . $this->test_live_suffix()] ?? null) === 'yes';
    }

    /**
     * Handle the manual status changes
     *
     * @param int $order_id
     * @param string|null $status_to
     *
     * @return void
     */
    public function handle_order_status_edited(int $order_id, string $status_to = null): void
    {
        // allow the webhook processing only once on the main gateway
        if (!in_array($this->id, [
                WC_Gateway_Paysafe::PAYMENT_GATEWAY_CODE,
                WC_Gateway_Paysafe_Apple_Pay::PAYMENT_GATEWAY_CODE
            ], true)) {
            return;
        }

        $this->log_debug(
            self::PAYSAFE_ORDER_ID_KEYWORD
            . $order_id
            . '] Paysafe Checkout: Order status changed manually to "' . $status_to . '"');

        if (!$this->is_paysafe_payment($order_id, $this->id)) {
            // the status change is for an order with a payment method
            // that does not belong to us,
            // we shouldn't do anything here
            return;
        }

        $order = wc_get_order($order_id);

        $this->log_debug(
            self::PAYSAFE_ORDER_ID_KEYWORD
            . $order_id
            . '] Paysafe Checkout: Order Total: '
            . $order->get_total() . ' and total to be refunded still: '
            . $order->get_remaining_refund_amount());

        if ($status_to === self::ORDER_STATUS_PROCESSING) {
            $this->log_debug(
                self::PAYSAFE_ORDER_ID_KEYWORD
                . $order_id
                . '] Paysafe Checkout: Calling settlement_paysafe_payment');

            $this->settlement_paysafe_payment($order_id);
        }

        if ($status_to === self::ORDER_STATUS_REFUNDED && $order->get_remaining_refund_amount() > 0) {
            $order->add_order_note(
                __('The order status has been changed to Refunded.', 'paysafe-checkout') . ' ' .
                __(
                    'You need to manually refund the amount to the customer through your Paysafe Optic portal.',
                    'paysafe-checkout'
                ) . ' ' .
                __(
                    'Next time you can use the "Refund" button and then select "Refund via Paysafe"',
                    'paysafe-checkout'
                )
            );
        }

        if (
            $status_to === self::ORDER_STATUS_CANCELLED
            && $this->get_settlement_id($order_id)
            && $order->get_remaining_refund_amount() > 0
        ) {
            $settlement_id = $this->get_settlement_id($order_id);
            if (self::PAYSAFE_RESPONSE_STATUS_COMPLETED === $this->get_settlement_status($settlement_id)) {
	            $refund_success = $this->process_refund($order_id);

                if ($refund_success !== true) {
	                $order->add_order_note(
		                __( 'The order status has been changed to Cancelled.', 'paysafe-checkout' ) . ' ' .
		                __(
			                'You need to manually refund the amount to the customer through your Paysafe Admin.',
			                'paysafe-checkout'
		                ) . ' ' .
		                __(
			                'Next time you can use the Refund button and then select Refund via Paysafe',
			                'paysafe-checkout'
		                )
	                );
                }
            } else {
                $cancel_success = $this->cancel_settlement($settlement_id);
                if ($cancel_success) {
                    $order->add_order_note(
                        __('The order status has been changed to Cancelled.', 'paysafe-checkout') . ' ' .
                        sprintf(
                        /* translators: %s is replaced by the settlement id */
                            __(
                                'The settlement process was cancelled with settlement ID: %s .',
                                'paysafe-checkout'
                            ),
                            $settlement_id
                        )
                    );
                } else {
                    $order->add_order_note(
                        __(
                            'The order status has been changed to Cancelled.',
                            'paysafe-checkout'
                        ) . ' ' .
                        sprintf(
                        /* translators: %s is replaced by the settlement id */
                            __(
                                'The cancel settlement cannot be completed for order with settlement ID: %s .',
                                'paysafe-checkout'
                            ),
                            $settlement_id
                        )
                    );
                }
            }
        }
    }

    /**
     * Return the paysafe checkout locale
     * Paysafe only supports English and French for now
     * if WordPress is using one of these, send it, otherwise force English
     *
     * @return string
     */
    public function get_paysafe_checkout_locale(): string
    {
        $settings = $this->get_paysafe_settings();

        return $settings['checkout_locale'] ?? 'en_US';
    }

    /**
     * Retrieve paysafe customer id
     *
     * @return string|null
     */
    public function get_paysafe_customer_id(): ?string
    {
        $customer_id = get_current_user_id();
        if (!$customer_id) {
            return null;
        }

        $paysafe_customer_id = get_user_meta(
	        $customer_id,
            self::USER_META_PAYSAFE_CUSTOMER_ID,
            true
        );

        if (!empty($paysafe_customer_id)) {
            return $paysafe_customer_id;
        }

        return null;
    }

    /**
     * Retrieve paysafe customer email
     *
     * @return string|null
     */
    public function get_paysafe_customer_email(): ?string
    {
        $customer_id = get_current_user_id();
        if (!$customer_id) {
            return null;
        }

        $paysafe_customer_email = get_user_meta(
            $customer_id,
            self::USER_META_MERCHANT_CUSTOMER_ID,
            true
        );

        if (!empty($paysafe_customer_email)) {
            return substr($paysafe_customer_email, 0, strrpos($paysafe_customer_email, '+'));
        }

        return null;
    }

    /**
     * Save paysafe customer id
     *
     * @param string $paysafe_customer_id
     *
     * @return void
     */
    public function save_paysafe_customer_id(string $paysafe_customer_id): void
    {
        $customer_id = get_current_user_id();
        if (!$customer_id) {
            return;
        }

        update_user_meta($customer_id, self::USER_META_PAYSAFE_CUSTOMER_ID, $paysafe_customer_id);
    }

    /**
     * Delete paysafe customer id
     *
     * @return void
     */
    protected function delete_paysafe_customer_id(): void
    {
	    $customer_id = get_current_user_id();
	    if (!$customer_id) {
		    return;
	    }

	    delete_user_meta($customer_id, self::USER_META_MERCHANT_CUSTOMER_ID);
        delete_user_meta($customer_id, self::USER_META_PAYSAFE_CUSTOMER_ID);
    }

    /**
     * Save the multi-use token for this user
     *
     * @param string $multi_use_token
     * @param array|null $card_details
     * @param string $payment_type
     *
     * @return bool
     */
    public function save_paysafe_customer_token(
        string $multi_use_token,
        array $card_details = null,
        string $payment_type = WC_Gateway_Paysafe::PAYMENT_TYPE_CODE
    ): bool
    {
        $customer_id = get_current_user_id();
        if (!$customer_id) {
            return false;
        }

        if (!$this->is_save_token_enabled($payment_type)) {
            return false;
        }

        if ($this->is_paysafe_customer_token_duplicate($multi_use_token)) {
            return false;
        }

        $multi_use_token_id = $this->get_paysafe_token_id($multi_use_token);
        if ($multi_use_token_id) {
            $multi_use_token = $multi_use_token_id . '--' . $multi_use_token;
        }

        $token = new WC_Payment_Token_CC();
        $token->set_token(
            base64_encode($multi_use_token)
        );
        $token->set_gateway_id( $this->id );

        if ($card_details['cardType'] ?? null) {
            $card_type = strtoupper($card_details['cardType']);
            if (isset($this->card_types_map[$card_type]) && !empty($this->card_types_map[$card_type])) {
                $card_type = $this->card_types_map[$card_type];
            }
            $token->set_card_type($card_type);
        }
        if ($card_details['lastDigits'] ?? null) {
            $token->set_last4($card_details['lastDigits']);
        }
        if ($card_details['cardExpiry']['month'] ?? null) {
            $token->set_expiry_month($card_details['cardExpiry']['month']);
        }
        if ($card_details['cardExpiry']['year'] ?? null) {
            $token->set_expiry_year($card_details['cardExpiry']['year']);
        }

        $token->set_user_id( $customer_id );

        return $token->save();
    }

    /**
     * Check whether the customer token that is to be saved is a duplicate or not
     *
     * @param string $multi_use_token
     *
     * @return bool
     */
    private function is_paysafe_customer_token_duplicate(string $multi_use_token): bool
    {
        $customer_id = get_current_user_id();
        if (!$customer_id) {
            return false;
        }

        $tokens = WC_Payment_Tokens::get_customer_tokens( $customer_id, $this->id );
        foreach ($tokens as $token) {
            if ($multi_use_token === $this->get_saved_card_token($token->get_token())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check whether the save token option is enabled or not
     *
     * @param string $payment_type
     *
     * @return bool
     */
    public function is_save_token_enabled(string $payment_type = WC_Gateway_Paysafe::PAYMENT_TYPE_CODE): bool
    {
        $settings = $this->get_paysafe_settings();

        return (
                $settings['save_tokens_' . $payment_type . $this->test_live_suffix()] ?? self::SAVE_TOKEN_DEFAULT_VALUE
            ) === 'yes';
    }

    /**
     * Check whether the save token option is enabled or not
     *
     * @param string $payment_type
     *
     * @return bool
     */
    public function is_cvv_token_enabled(string $payment_type = WC_Gateway_Paysafe::PAYMENT_TYPE_CODE): bool
    {
        if (!$this->is_save_token_enabled()) {
            return false;
        }

        $settings = $this->get_paysafe_settings();

	    return (
                $settings['cvv_tokens_' . $payment_type . $this->test_live_suffix()] ?? self::SAVE_TOKEN_DEFAULT_VALUE
            ) === 'yes';
    }

    /**
     * @return void
     */
    public function handle_get_order_pay_data(): void
    {
        $post = wp_kses_post_deep(json_decode(file_get_contents('php://input'), true));

	    $nonce = $post['nonce'] ?? null;
	    if ( ! wp_verify_nonce( $nonce, 'paysafe_payment_response' ) ) {
		    wp_send_json([
			    'status'    => 'fail',
		    ]);
		    wp_die();
	    }

        $order_id = $post['order_id'] ?? null;
        if (empty($order_id)) {
            wp_send_json([
                'status'    => 'fail',
            ]);
            wp_die();
        }

        $order = wc_get_order($order_id);
        $currency_code = $order->get_currency();

	    $current_user_id = get_current_user_id();
        if (!$current_user_id || $order->get_customer_id() !== $current_user_id) {
	        wp_send_json([
		        'status'    => 'fail',
	        ]);
	        wp_die();
        }

	    $sut_details = $this->get_paysafe_customer_single_use_token();
	    $sut = $sut_details['singleUseCustomerToken'] ?? null;

	    $token_id = $post['token_id'] ?? null;
        $paysafe_token = null;
        if ($token_id) {
	        $token = WC_Payment_Tokens::get( $token_id );
            if ($token && $token->get_user_id() === get_current_user_id()) {
                $paysafe_token = $this->get_paysafe_single_use_card_token(
                    $this->get_saved_card_token($token->get_token()),
                    $sut_details
                );

	            if (!$paysafe_token) {
		            // token is missing from the customer's paysafe wallet, we should remove it from our side
		            $this->log_debug(
			            ' Paysafe Checkout: Removing deleted paysafe saved card #' . $token->get_id()
		            );

		            $this->log_debug(
			            ' Paysafe Checkout: Aborting saved card payment, saved card token not found in customer wallet on Paysafe'
		            );

		            $token->delete();

		            wp_send_json([
			            'status'    => 'fail',
		            ]);
		            wp_die();
	            }
            }
        }

        $result = [
            'result'        => 'success',
            'merch_ref_num' => $this->get_merchant_reference_number($order_id),
            'single_use_token'  => $sut,
            'paysafe_token' => $paysafe_token,
            'order'         => [
                'order_id'  => $order_id,
                'amount'    => (int)($order->get_total() * 100),
                'currency'  => $currency_code,
            ],
            'customer'      => [
                'customer_id'   => $order->get_customer_id(),
                'first_name'    => $order->get_billing_first_name(),
                'last_name'     => $order->get_billing_last_name(),
                'email'         => $order->get_billing_email(),
                'merchant_customer_id'  => $this->get_merchant_customer_id(),
            ],
            'billing'       => [
                'street'    => $order->get_billing_address_1(),
                'street2'   => $order->get_billing_address_2(),
                'city'      => $order->get_billing_city(),
                'zip'       => $order->get_billing_postcode(),
                'country'   => $order->get_billing_country(),
                'state'     => $order->get_billing_state(),
                'phone'     => $order->get_billing_phone(),
            ],
            'success_url'   => $order->get_view_order_url(),
        ];

        wp_send_json($result);
        wp_die();
    }

    /**
     * Filter availability for pay and cancel buttons for an order
     *
     * @param array $actions
     * @param $order
     *
     * @return array
     */
    public function my_account_my_orders_actions(array $actions, $order): array
    {
        $order_id = $order->get_id();

        // if it's not a paysafe payment, don't touch it
        if (!$order_id || !$this->is_paysafe_payment($order_id, $this->id)) {
            return $actions;
        }

        // if cancel exists, we have a payment id (was paid), and payment cannot be voided or refunded,
        // do not allow customer to cancel the order
        if (
            isset($actions['cancel'])
            && $this->get_payment_id($order_id)
            && !$this->can_void_payment($order_id) && !$this->can_refund_order($order_id)) {
            unset($actions['cancel']);
        }

        // if pay exists, and we already have a payment id, do not allow the customer to pay again
        if (isset($actions['pay']) && $this->get_payment_id($order_id)) {
            unset($actions['pay']);
        }

        return $actions;
    }

    /**
     * Log an error with our internal logging system
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    protected function log_error(string $message, array $context = []): void
    {
        if ($this->logger !== null) {
            $this->logger->error($message, $context);
        }
    }

    /**
     * Log a debug with our internal logging system
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    protected function log_debug(string $message, array $context = []): void
    {
        if ($this->logger !== null) {
            $this->logger->debug($message, $context);
        }
    }

    /**
     * Log a notice with our internal logging system
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    protected function log_notice(string $message, array $context = []): void
    {
        if ($this->logger !== null) {
            $this->logger->notice($message, $context);
        }
    }

    /**
     * Deletes a user profile and associated Paysafe data
     *
     * @return void
     */
    public function handle_delete_paysafe_customer_data(): void
    {
	    if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'paysafe_delete_customer' ) ) {
		    wp_die( 'Security check failed' );
	    }

        // allow the webhook processing only once on the main gateway
        if ($this->id !== WC_Gateway_Paysafe::PAYMENT_GATEWAY_CODE) {
            return;
        }

        $customer_id = $this->get_paysafe_customer_id();
        $this->delete_paysafe_customer_id();

	    try {
		    // get user tokens
		    $tokens = WC_Payment_Tokens::get_customer_tokens(get_current_user_id(), $this->id);

		    foreach ($tokens as $token_id => $token) {
			    if ($token instanceof WC_Payment_Token) {
				    $token->delete();
			    }
		    }
	    } catch (\Exception $e) {
		    $this->log_error(self::PAYSAFE_EXCEPTION_TEXT . $e->getMessage());
	    }

        if (!empty($customer_id)) {
	        try {
		        // call paysafe api and delete customer (based on $customer_id)
		        $api_connector = new PaysafeApiCardPluginConnector();
		        $result        = $api_connector->deletePaysafeCustomer( $customer_id );

		        $this->log_debug( "PaySafe Checkout: Handle Delete Customer Result: ", [
			        'customer_id' => $customer_id,
			        'result'      => $result
		        ] );

	        } catch ( PaysafeException $e ) {
		        $this->log_error(
			        self::PAYSAFE_EXCEPTION_TEXT
			        . $e->getMessage(),
			        array_merge( $result ?? [], $e->getAdditionalData() ) );

		        wc_add_notice( $e->getMessage(), 'error' );
	        } catch ( \Exception $e ) {
		        $this->log_error( self::PAYSAFE_EXCEPTION_TEXT . $e->getMessage() );
	        }
        }

        // redirect to payment methods page
        wp_redirect(wc_get_account_endpoint_url( 'payment-methods' )); exit;
    }

    /**
     * Adds Delete button to Payment Methods page
     *
     * @return void
     */
    public function paysafe_add_delete_profile_link(): void
    {
        // allow the webhook processing only once on the main gateway
        if ($this->id !== WC_Gateway_Paysafe::PAYMENT_GATEWAY_CODE) {
            return;
        }

        $customer_id = $this->get_paysafe_customer_id();
	    $merchant_customer_id = $this->get_merchant_customer_id(true);

        if (!empty($customer_id) || !empty($merchant_customer_id)) {
            $customer_email = $this->get_paysafe_customer_email();
            $question = sprintf(
            /* translators: %s is replaced by an email address */
                __(
                    'Are you sure you want to delete your Paysafe profile registered with %s?',
                    'paysafe-checkout'
                ),
                $customer_email
            );

	        $delete_url = wp_nonce_url(
                    WC()->api_request_url('paysafe_delete_paysafe_customer_data'),
                    'paysafe_delete_customer'
            );

            ?>
            <a href="<?php echo esc_url(
                add_query_arg(
                    [
                        'payment_gateway' => WC_Gateway_Paysafe::PAYMENT_GATEWAY_CODE,
                    ],
	                $delete_url
                )
            ); ?>" onclick="return confirm('<?php echo esc_html($question);?>')">
                <?php echo esc_html(__('Delete all payment data (Paysafe Checkout)', 'paysafe-checkout')); ?>
            </a>
            <br /><br />
            <?php
        }
    }

    /**
     * Gets the Paysafe settlement status
     *
     * @param string $settlement_id
     *
     * @return string|null
     */
    public function get_settlement_status(string $settlement_id): ?string
    {
        try {
            // call paysafe api and get settlement status (based on $settlement_id)
            $api_connector = new PaysafeApiCardPluginConnector();
            $result = $api_connector->getPaysafeSettlementData($settlement_id);

            $this->log_debug("PaySafe Checkout: Handle get settlement status Result: ", [
                'settlement_id' => $settlement_id,
                'result' => $result
            ]);

            return $result['status'] ?? null;
        } catch (PaysafeException $e) {
            $this->log_error(
                self::PAYSAFE_EXCEPTION_TEXT . $e->getMessage(),
                array_merge($result ?? [], $e->getAdditionalData()));

            wc_add_notice( $e->getMessage(), 'error' );
        } catch (\Exception $e) {
            $this->log_error(self::PAYSAFE_EXCEPTION_TEXT . $e->getMessage());
        }

        return null;
    }

	/**
	 * Gets the Paysafe payment available to settle amount
	 *
	 * @param string $payment_id
	 *
	 * @return string|null
	 */
	public function get_available_to_settle_amount(string $payment_id): ?string
	{
		try {
			// call paysafe api and get available to settle amount (based on $payment_id)
			$api_connector = new PaysafeApiCardPluginConnector();
			$result = $api_connector->getPaysafePaymentData($payment_id);

			$this->log_debug("PaySafe Checkout: Handle get available to settle amount Result: ", [
				'payment_id' => $payment_id,
				'result' => $result
			]);

			return $result['availableToSettle'] ?? null;
		} catch (PaysafeException $e) {
			$this->log_error(
				self::PAYSAFE_EXCEPTION_TEXT . $e->getMessage(),
				array_merge($result ?? [], $e->getAdditionalData()));

			wc_add_notice( $e->getMessage(), 'error' );
		} catch (\Exception $e) {
			$this->log_error(self::PAYSAFE_EXCEPTION_TEXT . $e->getMessage());
		}

		return null;
	}

    /**
     * Cancels Paysafe settlement
     *
     * @param string $settlement_id
     *
     * @return bool
     */
    public function cancel_settlement(string $settlement_id): bool
    {
        try {
            // call paysafe api and cancel settlement (based on $settlement_id)
            $api_connector = new PaysafeApiCardPluginConnector();
            $result = $api_connector->cancelPaysafeSettlement($settlement_id);

            $this->log_debug("PaySafe Checkout: Handle cancel settlement Result: ", [
                'settlement_id' => $settlement_id,
                'result' => $result
            ]);

            return true;
        } catch (PaysafeException $e) {
            $this->log_error(
                self::PAYSAFE_EXCEPTION_TEXT . $e->getMessage(),
                array_merge($result ?? [], $e->getAdditionalData()));
        } catch (\Exception $e) {
            $this->log_error(self::PAYSAFE_EXCEPTION_TEXT . $e->getMessage());
        }

        return false;
    }
    /**
     * Return the merchant customer id (stored or generated)
     *
     * @return string|null
     */
    protected function get_merchant_customer_id(bool $only_from_database = false): ?string
    {
        $customer_id = get_current_user_id();
        if (!$customer_id) {
            return null;
        }

        $merchant_customer_id = get_user_meta(
            $customer_id,
            self::USER_META_MERCHANT_CUSTOMER_ID, true);

	    if (empty($merchant_customer_id) && $only_from_database) {
            return null;
	    }

        if (empty($merchant_customer_id)) {
            $current_user = wp_get_current_user();

            $merchant_customer_id = (string)$current_user->get('user_email') . '+' . time();

            update_user_meta(
                $customer_id, self::USER_META_MERCHANT_CUSTOMER_ID, $merchant_customer_id);
        }

        return $merchant_customer_id;
    }

    /**
     * Save all refund ids and amount refunded to the local storage for an order
     *
     * @param int $order_id
     * @param string $refund_id
     * @param float $amount
     *
     * @return void
     */
    protected function update_refund_meta_history(int $order_id, string $refund_id, float $amount): void
    {
        if (empty($refund_id)) {
            return;
        }

        $refund_ids = json_decode(get_post_meta(
            self::ORDER_META_KEY_REFUND_IDS, true) ?? '{}', true);
        $refund_ids[] = [
            'refund_id' => $refund_id,
            'amount'    => $amount,
        ];

        update_post_meta($order_id, self::ORDER_META_KEY_REFUND_IDS, json_encode($refund_ids));
    }

    /**
     * Return the payment integration type set in the plugin settings
     *
     * @return string
     */
    public function get_integration_type(): string
    {
        return $this->settings['payment_integration_type' . $this->test_live_suffix()] ?? self::PAYMENT_INTEGRATION_DEFAULT;
    }

    /**
     * Refund an order's payment with paysafe
     *
     * @param int $order_id
     * @param  float|null $amount Refund amount.
     * @param  string     $reason Refund reason.
     *
     * @return bool|\WP_Error True or false based on success, or a WP_Error object.
     */
    public function process_refund($order_id, $amount = null, $reason = '')
    {
        if (!$this->is_paysafe_payment($order_id, $this->id)) {
            // the status change is for an order with a payment method
            // that does not belong to us,
            // we shouldn't do anything here
            return false;
        }

        $return = false;

	    //Fetch the Woocommerce order
	    $order = wc_get_order($order_id);

	    // get the order total amount
	    $total_amount = $order->get_total();
	    if (!$amount) {
		    $amount = $total_amount;
	    }

	    $this->log_debug(
            self::PAYSAFE_ORDER_ID_KEYWORD
            . $order_id
            . '] Paysafe Checkout: Processing refund in amount of '
            . $amount . ' with reason: ' . ($reason ?? '(none)'));

        // get the settlement id that was acquired before
        $settlement_id = $this->get_settlement_id($order_id);

        // generate merchant reference unique number
        $merchant_ref = $this->get_merchant_reference_number($order_id);

        // no settlement id, means that the paysafe payment gateway workflow is not complete
        if (!$settlement_id) {
            $this->log_debug(
                self::PAYSAFE_ORDER_ID_KEYWORD
                . $order_id
                . '] Paysafe Checkout: Refund process failed - No settlement ID found for this order');

            $order->add_order_note(
                __(
                    'Refund process failed - No settlement found.',
                    'paysafe-checkout'
                )
            );

            return new WP_Error(
                'no_settlement_id',
                __('Settlement id is missing','paysafe-checkout')
            );
        }

        // paysafe amount in integer
        $amount_paysafe = (int)($amount * 100);

        try {
            // get the api connector
            $api_connector = new PaysafeApiCardPluginConnector();

            // call to paysafe for the refund action
            $result = $api_connector->refundPayment($settlement_id, [
                    'merchantRefNum' => $merchant_ref,
                    'dupCheck' => true,
                    'amount' => $amount_paysafe,
                ]
            );

            $this->log_debug(
                self::PAYSAFE_ORDER_ID_KEYWORD
                . $order_id
                . '] Paysafe Checkout: [Settlement ID '
                . $settlement_id . '] Refund response', $result ?? []);

            // paysafe return status list that is accepted as 'working on refund'
            $accepted_statuses = [
                self::PAYSAFE_RESPONSE_STATUS_COMPLETED,
                self::PAYSAFE_RESPONSE_STATUS_PENDING,
                self::PAYSAFE_RESPONSE_STATUS_RECEIVED,
                self::PAYSAFE_RESPONSE_STATUS_INITIATED,
            ];

            $status = $result['status'] ?? null;

            // if none of the accepted statuses are met, abort the refund process
            if (!in_array($status, $accepted_statuses, true)) {
                $this->log_debug(
                    self::PAYSAFE_ORDER_ID_KEYWORD
                    . $order_id
                    . '] Paysafe Checkout: Refund call returned unknown status "' . $status . '"');

                throw new PaysafeException(
                    __('Refund error: refund has returned unknown status.', 'paysafe-checkout'),
                    PaysafeException::REFUND_CALL_UNKNOWN_STATUS
                );
            }

            // get the order currency code
            $currency_code = $order->get_currency();
            $refund_id = $result['id'] ?? '-';

            /* translators: Refund finished message. 1: Currency used, 2: Refunded amount, 3: Refund ID. */
            $refund_message = __('Refunded %1$s %2$s. Refund ID: %3$s', 'paysafe-checkout');
            if ($amount < $total_amount) {
                // partial refund
                /* translators: Refund finished message. 1: Refunded amount, 2: Currency used, 3: Refund ID. */
                $refund_message = __('Partially refunded %1$s %2$s. Refund ID: %3$s','paysafe-checkout');
            }
            $this->log_debug(
                self::PAYSAFE_ORDER_ID_KEYWORD
                . $order_id
                . '] Paysafe Checkout: [Refund ID ' . $refund_id . '] Refund message: "' . $refund_message . '"');

            // mark refund with an order note
            $order->add_order_note(
                sprintf(
                    $refund_message,
                    $currency_code,
	                number_format_i18n($amount, 2),
                    '' . $refund_id
                )
            );

            // save refund paysafe data
            $this->update_refund_meta_history($order_id, $refund_id, $amount);
            update_post_meta(
                $order_id, self::ORDER_META_KEY_LAST_ACTION, self::PAYSAFE_ACTION_REFUND);
            $return = true;
        } catch (PaysafeException $e) {
            $this->log_error(
                self::PAYSAFE_ORDER_ID_KEYWORD . $order_id . '] Paysafe Checkout: [Refund ID '
                . $refund_id . '] Refund failed - Paysafe Exception "' . $e->getMessage() . '"',
                array_merge($result ?? [], $e->getAdditionalData()));

            // an error occurred, add a note to the order about it
            $order->add_order_note(
                sprintf(
                    /* translators: %s is replaced by the message */
                    __('Automatic refund processing failed with message: %s', 'paysafe-checkout'),
                    $e->getMessage()
                )
            );

            $return = new WP_Error('refund_exception', $e->getMessage());
        } catch (\Exception $e) {
            $this->log_error(
                self::PAYSAFE_ORDER_ID_KEYWORD . $order_id . '] '
                . self::PAYSAFE_EXCEPTION_TEXT . ' "' . $e->getMessage() . '"');
        }

        return $return;
    }


    /**
     * Void the authorization payment of an order
     *
     * @param int $order_id
     * @param int|null $amount
     * @param bool $is_partial_settlement
     *
     * @return void
     */
    public function void_paysafe_payment(int $order_id, int $amount = null, bool $is_partial_settlement = false): void
    {
        // Fetch the WooCommerce order
        $order = wc_get_order($order_id);

        // check if order payment can be voided
        if (!$this->can_void_payment($order_id)) {
            $this->log_debug(
                self::PAYSAFE_ORDER_ID_KEYWORD
                . $order_id
                . '] Paysafe Checkout: Void payment failed - Void not allowed for this order');

            $order->add_order_note(
                __(
                    'Paysafe Checkout: Void payment failed - Void not allowed for this order.',
                    'paysafe-checkout'
                )
            );

            return;
        }

        $payment_id = $this->get_payment_id($order_id);
        $merchant_ref = $this->get_merchant_reference_number($order_id);

        // Check if transaction ID exists
        if (!$payment_id) {
            $this->log_debug(
                self::PAYSAFE_ORDER_ID_KEYWORD
                . $order_id
                . '] Paysafe Checkout: Void payment failed - No payment ID found.');

            $order->add_order_note(
                __(
                    'Paysafe Checkout: Void payment failed - No payment ID found.',
                    'paysafe-checkout'
                )
            );

            return;
        }

	    $amount_to_void = $amount ?? (int)($order->get_total() * 100);

	    try {
            $api_connector = new PaysafeApiCardPluginConnector();
            $result = $api_connector->voidPayment($payment_id, [
                'merchantRefNum'    => $merchant_ref,
                'amount'            => $amount_to_void,
            ]);

            $this->log_debug(
                self::PAYSAFE_ORDER_ID_KEYWORD
                . $order_id
                . '] Paysafe Checkout: Void Response', $result ?? []);

            $accepted_statuses = [
                self::PAYSAFE_RESPONSE_STATUS_COMPLETED,
                self::PAYSAFE_RESPONSE_STATUS_PENDING,
                self::PAYSAFE_RESPONSE_STATUS_RECEIVED,
            ];

            $status = $result['status'] ?? null;
            $void_id = $result['id'] ?? null;

            if (empty($void_id) || !in_array($status, $accepted_statuses, true)) {
                $this->log_debug(
                    self::PAYSAFE_ORDER_ID_KEYWORD
                    . $order_id
                    . '] Paysafe Checkout: Void call returned unknown status "' . $status . '"');

                throw new PaysafeException(
                    __('Void error: void has returned unknown status.', 'paysafe-checkout'),
                    PaysafeException::VOID_CALL_UNKNOWN_STATUS
                );
            }

            $order->add_order_note(
                sprintf(
	                ($is_partial_settlement ?
                        /* translators: %s is replaced by the currency and void amount */
                        __(
                            'The remaining %s from the original authorization has been automatically voided.',
                            'paysafe-checkout'
                        ) . ' ' .
                        __('The customer will see the unused funds released back to their card shortly.', 'paysafe-checkout')
                        :
                        /* translators: %s is replaced by the currency and void amount */
                        __(
                            'The authorization of %s has been successfully voided.',
                            'paysafe-checkout'
                        )
                    ),
                    $order->get_currency() . ' ' . number_format_i18n($amount_to_void / 100, 2)
                )
            );

            update_post_meta($order_id, self::ORDER_META_KEY_VOID_ID, $void_id);
            update_post_meta($order_id, self::ORDER_META_KEY_LAST_ACTION, self::PAYSAFE_ACTION_VOID);
        } catch (PaysafeException $e) {
            $this->log_error(
                self::PAYSAFE_ORDER_ID_KEYWORD . $order_id
                . '] Paysafe Checkout: Void failed - Paysafe Exception "'
                . $e->getMessage() . '"', array_merge($result ?? [], $e->getAdditionalData()));

	        $order->add_order_note(
		        sprintf(
			        ($is_partial_settlement ?
				        /* translators: %s is replaced by the currency and void amount */
				        __(
					        'We were unable to void the remaining %s from the original authorization.',
					        'paysafe-checkout'
				        ) . ' ' .
				        __('However, this is not an issue - the unused portion of the funds will be automatically released by the customer’s bank.', 'paysafe-checkout') . ' ' .
				        __('Please note that depending on the bank, it may take up to 7 business days for the customer to see the funds available again on their card.', 'paysafe-checkout')
				        :
				        /* translators: %s is replaced by the currency and void amount */
				        __(
					        'The authorization of %s has been successfully voided.',
					        'paysafe-checkout'
				        )
			        ),
			        $order->get_currency() . ' ' . number_format_i18n($amount_to_void / 100, 2),
		        )
	        );
        } catch (\Exception $e) {
            $this->log_error(
                self::PAYSAFE_ORDER_ID_KEYWORD . $order_id . '] '
                . self::PAYSAFE_EXCEPTION_TEXT . ' "' . $e->getMessage() . '"');
        }

        $this->log_debug(
            self::PAYSAFE_ORDER_ID_KEYWORD
            . $order_id
            . '] Paysafe Checkout: Void Result', $result ?? []);
    }

    /**
     * Settle a Paysafe payment
     *
     * @param $order_id
     *
     * @return void
     */
    public function settlement_paysafe_payment($order_id) : void
    {
        // Fetch the WooCommerce order
        $order = wc_get_order($order_id);

        // check if order payment can be settled
        if (!$this->can_settle_payment($order_id)) {
            $this->log_debug(
                self::PAYSAFE_ORDER_ID_KEYWORD
                . $order_id
                . '] Paysafe Checkout: Settlement not allowed');

            $order->add_order_note(
                __(
                    'Paysafe Checkout: Settlement process failed - Settlement not allowed for this order.',
                    'paysafe-checkout'
                )
            );

            return;
        }

        $payment_id = $this->get_payment_id($order_id);
        $merchant_ref = $this->get_merchant_reference_number($order_id);

        if (!$payment_id) {
            $this->log_debug(
                self::PAYSAFE_ORDER_ID_KEYWORD
                . $order_id
                . '] Paysafe Checkout: Settlement process failed - No payment ID found');

            $order->add_order_note(
                __(
                    'Paysafe Checkout: Settlement process failed - No payment ID found.',
                    'paysafe-checkout'
                )
            );

            return;
        }

        try {
            $amount_order = $order->get_total();
            $amount_paysafe = (int)($amount_order * 100);
            $amount_authorized = $this->get_available_to_settle_amount($payment_id);

            if ($amount_paysafe > $amount_authorized) {
                // we cannot capture more than we reserved
	            $amount_paysafe = $amount_authorized;
            }

            $amount_to_void = $amount_authorized - $amount_paysafe;
            $is_partial_capture = $amount_to_void > 0;

            $api_connector = new PaysafeApiCardPluginConnector();
            $result = $api_connector->settlePayment($payment_id, [
                    'merchantRefNum'    => $merchant_ref,
                    'dupCheck'          => true,
                    'amount'            => $amount_paysafe,
                ]
            );

            $this->log_debug(
                self::PAYSAFE_ORDER_ID_KEYWORD
                . $order_id
                . '] Paysafe Checkout: Settlement response', $result ?? []);

            $accepted_statuses = [
                self::PAYSAFE_RESPONSE_STATUS_COMPLETED,
                self::PAYSAFE_RESPONSE_STATUS_PENDING,
                self::PAYSAFE_RESPONSE_STATUS_RECEIVED,
                self::PAYSAFE_RESPONSE_STATUS_INITIATED,
            ];

            $status = $result['status'] ?? null;
            $settlement_id = $result['id'] ?? null;

            if (empty($settlement_id) || !in_array($status, $accepted_statuses, true)) {
                $this->log_debug(
                    self::PAYSAFE_ORDER_ID_KEYWORD
                    . $order_id
                    . '] Paysafe Checkout: Settlement call returned unknown status "' . $status . '"');

                throw new PaysafeException(
                    __('Settlement error: settlement has returned unknown status.','paysafe-checkout'),
                    PaysafeException::SETTLEMENT_CALL_UNKNOWN_STATUS
                );
            }

            if ($status === self::PAYSAFE_RESPONSE_STATUS_RECEIVED) {
                $this->log_debug(
                    self::PAYSAFE_ORDER_ID_KEYWORD
                    . $order_id
                    . '] Paysafe Checkout: Funds capture is received. [Settlement ID '
                    . $settlement_id . ']', $result ?? []);
                $order->add_order_note(
                    sprintf(
                        /* translators: %s is replaced by the currency and the amount captured */
                        __('Funds capture of %s started.', 'paysafe-checkout') . ' ' .
                        /* translators: %s is replaced by the Settlement ID */
                        __('Settlement ID: %s', 'paysafe-checkout'),
                        $order->get_currency() . ' ' . number_format_i18n((float)$amount_order, 2),
                        $settlement_id
                    )
                );

                update_post_meta($order_id, self::ORDER_META_KEY_SETTLEMENT_ID, $settlement_id);
            } else {
                $this->log_debug(
                    self::PAYSAFE_ORDER_ID_KEYWORD
                    . $order_id
                    . '] Paysafe Checkout: Funds capture completed. [Settlement ID '
                    . $settlement_id . ']', $result ?? []);
                $order->add_order_note(
                    sprintf(
	                    (
                                $is_partial_capture ?
                                /* translators: %s is replaced by the Settlement ID */
                                __('Partial funds capture of %s completed successfully.', 'paysafe-checkout') . ' ' :
                                /* translators: %s is replaced by the Settlement ID */
                                __('Funds capture of %s completed successfully.', 'paysafe-checkout') . ' '
                        ) .

                        /* translators: %s is replaced by the Settlement ID */
                        __('Settlement ID: %s.', 'paysafe-checkout') . ' ' .
                        __(
                            'To cancel the settlement, change the order status to “Cancelled”.',
                            'paysafe-checkout'
                        ) . ' ' .
	                    ($is_partial_capture ?
                            __(
                                'To issue a refund for the captured amount, click the “Refund” button and select “Refund via Paysafe”.',
                                'paysafe-checkout'
                            ) :
                            __(
                                'To issue a refund, click the “Refund” button and select “Refund via Paysafe”.',
                                'paysafe-checkout'
                            )
                        ) . ' ' .
                        __(
                            'Note: Refunds are available only after the payment has been captured and fully settled.'
                            , 'paysafe-checkout'
                        ),
                        $order->get_currency() . ' ' . number_format_i18n($amount_paysafe / 100, 2),
                        $settlement_id
                    )
                );

                if ($amount_to_void > 0) {
                    $this->log_debug(
                        self::PAYSAFE_ORDER_ID_KEYWORD
                        . $order_id
                        . '] Paysafe Checkout: Calling void_paysafe_payment');

                    $this->void_paysafe_payment($order_id, $amount_to_void, $is_partial_capture);
                }

                update_post_meta($order_id, self::ORDER_META_KEY_SETTLEMENT_ID, $settlement_id);
                update_post_meta(
                    $order_id, self::ORDER_META_KEY_LAST_ACTION, self::PAYSAFE_ACTION_SETTLE);
            }
        } catch (PaysafeException $e) {
            $this->log_error(
                self::PAYSAFE_ORDER_ID_KEYWORD
                . $order_id
                . '] Paysafe Checkout: Settlement failed - Paysafe Exception: "'
                . $e->getMessage() . '"', array_merge($result ?? [], $e->getAdditionalData()));

            $order->add_order_note(
                sprintf(
                /* translators: %s is replaced by the message */
                    __('Paysafe settlement failed: %s', 'paysafe-checkout'),
                    $e->getMessage()
                )
            );
        } catch (\Exception $e) {
            $this->log_error(
                self::PAYSAFE_ORDER_ID_KEYWORD . $order_id . '] '
                . self::PAYSAFE_EXCEPTION_TEXT . ' "' . $e->getMessage() . '"');
        }

        $this->log_debug(
            self::PAYSAFE_ORDER_ID_KEYWORD
            . $order_id
            . '] Paysafe Checkout: Settlement Result', $result ?? []);
    }

    /**
     * Get the single use customer token for checkout
     *
     * @param bool $is_second_try
     *
     * @return array|null
     */
    public function get_paysafe_customer_single_use_token(bool $is_second_try = false): ?array
    {
        $paysafe_customer_id = $this->get_paysafe_customer_id();
        if (empty($paysafe_customer_id)) {
            return null;
        }

        $return = null;
        $merchant_ref = $this->get_merchant_reference_number();

        try {
            $api_connector = new PaysafeApiCardPluginConnector();
            $result = $api_connector->getCustomerSingleUseToken($paysafe_customer_id, [
                'merchantRefNum' => $merchant_ref,
                'paymentType' => WC_Gateway_Paysafe::PAYMENT_TYPE_CODE,
            ]);

            $this->log_debug(
                'Paysafe Checkout: Single use token response [Customer ID '
                . $paysafe_customer_id . '] [Merch. Ref. ID ' . $merchant_ref . ']',
                $result ?? []);

            $accepted_statuses = [
                self::PAYSAFE_SINGLE_USE_TOKEN_ACTIVE,
            ];

            $status = $result['status'] ?? null;

            if (!in_array($status, $accepted_statuses, true)) {
                throw new PaysafeException(
                    __(
                        'Get Customer Single Use Token: operation has returned unknown status.',
                        'paysafe-checkout'
                    ),
                    PaysafeException::CUSTOMER_SUT_CALL_UNKNOWN_STATUS
                );
            }

            $return = $result;
        } catch (PaysafeException $e) {
            $this->log_error(
                'Paysafe Checkout: [Customer ID ' . $paysafe_customer_id
                . '] [Merchant Ref. ID ' . $merchant_ref . '] Get Single Use Token Exception "'
                . $e->getMessage() . '"', array_merge($result ?? [], $e->getAdditionalData()));

            $additional_data = $e->getAdditionalData();
            $paysafe_error_code = $additional_data['error_code'] ?? null;
            if ($paysafe_error_code === self::PAYSAFE_ERROR_CUSTOMER_ID_NOT_FOUND) {
                // this means that the customer id in our local database is not in sync with paysafe
                // remove it locally
                $this->log_debug(
                    'Paysafe Checkout: [Customer ID ' . $paysafe_customer_id
                    . '] [Mercha Ref. ID ' . $merchant_ref . '] Removing local customer ID value');
                $this->delete_paysafe_customer_id();

                // don't do it the second time to avoid infinite loop
                if (!$is_second_try) {
                    // try to get it again based on email
                    $this->log_debug(
                        'Paysafe Checkout: [Merchant Ref. ID '
                        . $merchant_ref . '] Getting customer ID based on merchant customer reference');
                    $return = $this->handle_get_cust_id_from_paysafe_with_sut();
                }
            }
        } catch (\Exception $e) {
            $this->log_error('Paysafe Checkout: Exception "' . $e->getMessage() . '"');
        }

        $this->log_debug('Paysafe Checkout: Get Customer Single Use Token Response', $result ?? []);

        return $return;
    }

    /**
     * Get customer id from paysafe and then try to get the single use token with it
     *
     * @return array|null
     */
    private function handle_get_cust_id_from_paysafe_with_sut(): ?array
    {
        $paysafe_customer_id = $this->get_paysafe_customer_id_from_paysafe();
        if ($paysafe_customer_id) {
            // we got the updated customer id, now try the single use token again
            $this->log_debug('Paysafe Checkout: Getying Single Use Token for new Customer ID');
            return $this->get_paysafe_customer_single_use_token(true);
        }

        return null;
    }


    /**
     * Get the paysafe customer id based on merchant customer id
     *
     * @return string|null
     */
    protected function get_paysafe_customer_id_from_paysafe(): ?string
    {
        try {
            $merchant_customer_id = $this->get_merchant_customer_id();

            if (empty($merchant_customer_id)) {
                return null;
            }

            // get the api connector
            $api_connector = new PaysafeApiCardPluginConnector();

            // call to paysafe to get the customer data
            $result = $api_connector->getPaysafeCustomerDataByMid(
                [
                    'merchantCustomerId'        => $merchant_customer_id,
                ]
            );

            $paysafe_customer_id = $result['id'] ?? null;
            if ($paysafe_customer_id) {
                $this->save_paysafe_customer_id($paysafe_customer_id);

                return $paysafe_customer_id;
            }
        } catch (PaysafeException $e) {
            $this->log_error(
                'Paysafe Checkout: Get Paysafe customer ID from Paysafe - Paysafe Exception "'
                . $e->getMessage() . '"', array_merge($result ?? [], $e->getAdditionalData()));
        } catch (\Exception $e) {
            $this->log_error(
                'Paysafe Checkout: Get Paysafe customer ID from Paysafe - Exception "'
                . $e->getMessage() . '"');
        }

        return null;
    }

    /**
     * Is_add_payment_method_page - Returns true when viewing the add payment method page.
     *
     * @return bool
     */
    protected function is_add_payment_method_page() {
        global $wp;

        $page_id = wc_get_page_id( 'myaccount' );

        return ( $page_id && is_page( $page_id ) && isset( $wp->query_vars['add-payment-method'] ) );
    }

    /**
     * Handle business cases for checkout page
     * Return null if no blocking case met
     *
     * @param string $order_id
     *
     * @return array|null
     */
    private function handle_process_payment_checkout_page(
        string $order_id
    ): ?array
    {
        $order = wc_get_order($order_id);

        // phpcs:disable WordPress.Security.NonceVerification.Missing
        $order_pay_result = sanitize_text_field( wp_unslash($_POST['wc-' . $this->id . '-order-pay-result'] ?? '' ));
        // phpcs:enable WordPress.Security.NonceVerification.Missing

        if ($order_pay_result === 'success') {
            // payment was a success
            $this->log_debug(
                self::PAYSAFE_ORDER_ID_KEYWORD
                . $order_id
                . '] Paysafe Checkout: Processing Payment on My Account Order page was successful.');

            // return a redirect page to the view order page
            // and add a success payment notice
            wc_add_notice($this->payment_succesful_text, 'success');

            return [
                'result'    => 'success',
                'redirect'  => $order->get_view_order_url(),
            ];
        } elseif ($order_pay_result === 'failure') {
            $this->log_error(
                self::PAYSAFE_ORDER_ID_KEYWORD . $order_id
                . '] Paysafe Checkout: Processing Payment on My Account Order page failed.');

            // return a redirect page to the checkout pay page
            // and add an error to show that payment failed
            wc_add_notice(
                __(
                    'Payment failed with the current payment option.',
                    'paysafe-checkout'
                ),
                'error'
            );

            return [
                'result'    => 'failure',
                'redirect'  => $order->get_checkout_payment_url( false ),
            ];
        }

        return null;
    }

    /**
     * Handle the token payment case when process payment is called
     *
     * @param string $order_id
     * @param string $token_id
     *
     * @return array
     */
    private function handle_process_payment_token_pay(
        string $order_id,
        string $token_id
    ): array
    {
        $order = wc_get_order($order_id);

        $this->log_debug(
            self::PAYSAFE_ORDER_ID_KEYWORD
            . $order_id
            . '] Paysafe Checkout: Processing payment [Token ID ' . $token_id . ']');

        // hydrate the token object
        $token = WC_Payment_Tokens::get( $token_id );
        if ( !$token || $token->get_user_id() !== get_current_user_id() ) {
            $this->log_debug(
                self::PAYSAFE_ORDER_ID_KEYWORD
                . $order_id
                . '] Paysafe Checkout: Token not found');

            if (is_checkout_pay_page()) {
                // return a redirect page to the checkout pay page
                // and add an error to show that payment failed
                wc_add_notice(
                    __('Payment failed with the current payment option.','paysafe-checkout'),
                    'error'
                );

                $result = [
                    'result'    => 'failure',
                    'redirect'  => $order->get_checkout_payment_url( false ),
                ];
            } else {
                // no token, no payment
                $result = [
                    'result' => 'failure',
                    'redirect' => '',
                ];
            }

            return $result;
        }

        // add order note for token payment
        $order->add_order_note(
            sprintf(
            /* translators: %s is replaced by ID of the payment handle */
                __('Payment with saved payment method token', 'paysafe-checkout')
            )
        );

        // use paysafe js for the tokenized payment
        return $this->handle_process_payment_token_cvv_pay($order_id, $token);
    }

	/**
     * Find the single use card token using the multi use token from SUT data
     *
	 * @param string $paysafe_multi_use_token
	 * @param array $sut_details
	 *
	 * @return string|null
	 */
    private function get_paysafe_single_use_card_token(string $paysafe_multi_use_token, array $sut_details): ?string
    {
	    $customer_data = $this->get_paysafe_customer_data();

	    $paysafe_single_use_token = null;
	    foreach ($customer_data['paymentHandles'] ?? [] as $paymentHandleData) {
		    if (($paymentHandleData['paymentHandleToken'] ?? '') === $paysafe_multi_use_token) {
			    $paysafe_token_id = $paymentHandleData['id'] ?? '';
			    foreach ($sut_details['paymentHandles'] ?? [] as $sutPaymentHandleData) {
				    if ($sutPaymentHandleData['multiUsePaymentHandleId'] === $paysafe_token_id) {
					    $paysafe_single_use_token = $sutPaymentHandleData['paymentHandleToken'] ?? '';
					    break;
				    }
			    }
			    break;
		    }
	    }

        return $paysafe_single_use_token;
    }

	/**
	 * Handle the token payment case when process payment is called
     * AND CVV verification is enabled
     * -> return the data needed for paysafeJs process
	 *
	 * @param string $order_id
	 * @param WC_Payment_Token $token
	 *
	 * @return array
	 */
	private function handle_process_payment_token_cvv_pay(
		string $order_id,
		WC_Payment_Token $token
	): array {
        // hydrate the order
		$order = wc_get_order($order_id);

		// get the actual token
		$paysafe_token = $this->get_saved_card_token($token->get_token());


		$this->log_debug(
			self::PAYSAFE_ORDER_ID_KEYWORD
			. $order_id
			. '] Paysafe Checkout: Processing payment with CVV verification'
        );

		$sut_details = $this->get_paysafe_customer_single_use_token();
        $paysafe_single_use_token = $this->get_paysafe_single_use_card_token($paysafe_token, $sut_details);

        if (!$paysafe_single_use_token) {
            // token is missing from the customer's paysafe wallet, we should remove it from our side
	        $this->log_debug(
                 ' Paysafe Checkout: Removing deleted paysafe saved card #' . $token->get_id()
	        );

	        $this->log_debug(
                 ' Paysafe Checkout: Aborting saved card payment, saved card token not found in customer wallet on Paysafe'
	        );

	        $token->delete();

	        wc_add_notice(
		        __('Payment failed with the current payment option.','paysafe-checkout'),
		        'error'
	        );

	        return [
		        'result'    => 'failure',
		        'redirect'  => $order->get_checkout_payment_url( false ),
	        ];
        }

		return [
			'result' => 'success',
			'redirect' => '',
            'cvv_verification' => true,
            'order_id' => $order_id,
            'paysafe_token' => $paysafe_single_use_token ?? $paysafe_token,
            'single_use_token' => $sut_details['singleUseCustomerToken'] ?? null,
            'merch_ref_num' => $this->get_merchant_reference_number($order_id),
			'order'         => [
				'order_id'  => $order_id,
				'amount'    => (int)($order->get_total() * 100),
				'currency'  => $order->get_currency(),
			],
			'customer'      => [
				'customer_id'   => $order->get_customer_id(),
				'first_name'    => $order->get_billing_first_name(),
				'last_name'     => $order->get_billing_last_name(),
				'email'         => $order->get_billing_email(),
				'merchant_customer_id'  => $this->get_merchant_customer_id(),
			],
			'billing'       => [
				'street'    => $order->get_billing_address_1(),
				'street2'   => $order->get_billing_address_2(),
				'city'      => $order->get_billing_city(),
				'zip'       => $order->get_billing_postcode(),
				'country'   => $order->get_billing_country(),
				'state'     => $order->get_billing_state(),
				'phone'     => $order->get_billing_phone(),
			],
		];
	}

    /**
     * Process payment for an order
     *
     * @param $order_id
     *
     * @return string[]
     */
    public function process_payment($order_id)
    {
        update_post_meta($order_id, 'payment_gateway', $this->id);

        // Fetch the WooCommerce order
        $order = wc_get_order($order_id);
        $currency_code = $order->get_currency();

        try {
            // update order payment method title
            $order->set_payment_method_title($this->get_payment_gateway_public_name($order->get_payment_method()));
            $order->save();
        } catch (\Exception $e) {
            $this->log_debug(
                self::PAYSAFE_ORDER_ID_KEYWORD
                . $order_id
                . '] Paysafe Checkout: Process Payment has no order');
        }

        $this->log_debug(
            self::PAYSAFE_ORDER_ID_KEYWORD
            . $order_id
            . '] Paysafe Checkout: Processing payment');

        // handle my-account order-pay page
        if (is_checkout_pay_page()) {
            $result = $this->handle_process_payment_checkout_page($order_id);
            if (null !== $result) {
                return $result;
            }
        }

        // check for token id
        // phpcs:disable WordPress.Security.NonceVerification.Missing
        $token_id = sanitize_text_field( wp_unslash($_POST['wc-' . $this->id . '-payment-token'] ?? '' ));
        // phpcs:enable WordPress.Security.NonceVerification.Missing

        if ($token_id && 'new' !== $token_id) {
            // in this case, we can presume that the payment is with a token id
            // return the result, we are done here
            return $this->handle_process_payment_token_pay($order_id, $token_id);
        }

	    $sut_details = $this->get_paysafe_customer_single_use_token();
	    $sut = $sut_details['singleUseCustomerToken'] ?? null;

	    // return the data to the payment checkout popup
        return [
            'result'        => 'success',
            'redirect'      => '',
            'merch_ref_num' => $this->get_merchant_reference_number($order_id),
            'single_use_token'  => $sut,
            'order'         => [
                'order_id'  => $order_id,
                'amount'    => (int)($order->get_total() * 100),
                'currency'  => $currency_code,
            ],
            'customer'      => [
                'customer_id'   => $order->get_customer_id(),
                'first_name'    => $order->get_billing_first_name(),
                'last_name'     => $order->get_billing_last_name(),
                'email'         => $order->get_billing_email(),
                'merchant_customer_id'  => $this->get_merchant_customer_id(),
            ],
            'billing'       => [
                'street'    => $order->get_billing_address_1(),
                'street2'   => $order->get_billing_address_2(),
                'city'      => $order->get_billing_city(),
                'zip'       => $order->get_billing_postcode(),
                'country'   => $order->get_billing_country(),
                'state'     => $order->get_billing_state(),
                'phone'     => $order->get_billing_phone(),
            ],
        ];
    }

    /**
     * Generate Paysafe indented Select HTML.
     *
     * @param string $key Field key.
     * @param array  $data Field data.
     * @since  1.0.0
     * @return string
     */
    public function generate_paysafe_select_html( $key, $data )
    {
        $field_key = $this->get_field_key( $key );
        $defaults  = array(
            'title'             => '',
            'disabled'          => false,
            'class'             => '',
            'css'               => '',
            'placeholder'       => '',
            'type'              => 'text',
            'desc_tip'          => false,
            'description'       => '',
            'custom_attributes' => array(),
            'options'           => array(),
        );

        $data  = wp_parse_args( $data, $defaults );
        $value = $this->get_option( $key );

        ob_start();
        include PAYSAFE_WOO_PLUGIN_PATH . '/resources/html/settings/paysafe-settings-select.php';

        return ob_get_clean();
    }

    /**
     * Generate Paysafe private key input HTML.
     *
     * @param string $key Field key.
     * @param array  $data Field data.
     * @since  1.0.0
     * @return string
     */
    public function generate_paysafe_private_key_html( $key, $data )
    {
        $data['type'] = 'password';
        $field_key = $this->get_field_key( $key );
        $defaults  = array(
            'title'             => '',
            'disabled'          => false,
            'class'             => '',
            'css'               => '',
            'placeholder'       => '',
            'type'              => 'password',
            'desc_tip'          => false,
            'description'       => '',
            'custom_attributes' => array(),
        );

        $data = wp_parse_args( $data, $defaults );

        ob_start();
        include PAYSAFE_WOO_PLUGIN_PATH . '/resources/html/settings/paysafe-settings-private-key.php';

        return ob_get_clean();
    }

    /**
     * Generate Paysafe indented Account HTML.
     *
     * @param string $key Field key.
     * @param array  $data Field data.
     * @since  1.0.0
     * @return string
     */
    public function generate_paysafe_account_text_html( $key, $data )
    {
        $field_key = $this->get_field_key( $key );
        $defaults  = array(
            'title'             => '',
            'disabled'          => false,
            'class'             => '',
            'css'               => '',
            'placeholder'       => '',
            'type'              => 'text',
            'desc_tip'          => false,
            'description'       => '',
            'custom_attributes' => array(),
            'account_id'        => '',
        );

        $data  = wp_parse_args( $data, $defaults );
        $value = $this->get_option( $key );

        if (empty($data['account_id'])) {
            return '';
        }

        ob_start();
        include PAYSAFE_WOO_PLUGIN_PATH . '/resources/html/settings/paysafe-settings-account-text.php';

        return ob_get_clean();
    }

    /**
     * Generate Paysafe indented Info HTML.
     *
     * @param string $key Field key.
     * @param array  $data Field data.
     * @since  1.0.0
     * @return string
     */
    public function generate_paysafe_info_text_html( $key, $data )
    {
        $field_key = $this->get_field_key( $key );
        $defaults  = [
            'title'             => '',
            'class'             => '',
            'css'               => '',
            'placeholder'       => '',
            'type'              => 'text',
            'description'       => '',
            'custom_attributes' => [],
        ];

        $data  = wp_parse_args( $data, $defaults );

        ob_start();
        include PAYSAFE_WOO_PLUGIN_PATH . '/resources/html/settings/paysafe-settings-info-text.php';

        return ob_get_clean();
    }

    /**
     * Return whether the log debug option is enabled or not
     *
     * @return bool
     */
    public function is_error_logging_enabled(): bool
    {
        return $this->logger !== null;
    }

    /**
     * Log an error that is sent from a javascript file
     *
     * @return void
     */
    public function handle_paysafe_log_error_data(): void
    {
        // allow the logging only once on the main gateway
        if ($this->id !== WC_Gateway_Paysafe::PAYMENT_GATEWAY_CODE) {
            return;
        }

        $post = wp_kses_post_deep(json_decode(file_get_contents('php://input'), true));
        $error_message = $post['message'] ?? null;
        $error_context = $post['context'] ?? [];
        if (empty($error_message)) {
            return;
        }

        if ($this->logger !== null) {
            $this->logger->error('Paysafe Javascript Error: ' . $error_message, $error_context);
        }

        // if we detect the paysafe checkout popup closed by cancelling payment, sync saved cards
        if (is_array($error_context) && ($error_context['stage'] ?? '') === 'PAYMENT_HANDLE_NOT_CREATED') {
	        $this->check_paysafe_checkout_saved_cards();
        }
    }

    /**
     * Express checkout endpoint for apple pay express checkout
     *
     * @param bool $is_apple_pay Whether the request is for Apple Pay express checkout
     *
     * @return void
     */
    public function handle_paysafe_express_checkout(bool $is_apple_pay = false): void
    {
        // allow the logging only once on the main gateway
        if ($this->id !== WC_Gateway_Paysafe::PAYMENT_GATEWAY_CODE && !$is_apple_pay) {
            wp_send_json([
                'status' => 'fail',
            ], 400);
            wp_die();
        }

        $post = wp_kses_post_deep(json_decode(file_get_contents('php://input'), true));
        $gateway_id = $post['gateway_id'] ?? null;

        try {
	        // check if the request is valid
	        $nonce = $post['nonce'] ?? null;
	        if ( ! wp_verify_nonce( $nonce, 'paysafe_payment_response' ) ) {
		        throw new Exception(
			        __('Invalid nonce. Please refresh the page and try again', 'paysafe-checkout')
		        );
	        }

            $merchant_ref_number = $this->get_merchant_reference_number();
            $result = [
                'status' => 'success',
                'merchant_ref_num' => $merchant_ref_number,
                'gateway_id' => $gateway_id,
                'order_id' => 0,
            ];

            // Try to get the current order ID from the session
            $order_id = isset( WC()->session->order_awaiting_payment ) ? absint( WC()->session->order_awaiting_payment ) : absint( WC()->session->get( 'store_api_draft_order', 0 ) );
            $order_id = !$order_id ? (absint(WC()->session->get('order_awaiting_payment'))) : 0;

            $cart_hash = WC()->cart->get_cart_hash();
            $order = $order_id ? wc_get_order($order_id) : null;
            if (
                $order &&
                $order->has_cart_hash($cart_hash) &&
                $order->has_status(array(self::ORDER_STATUS_PENDING, self::ORDER_STATUS_FAILED))
            ) {
                $result['order_id'] = $order_id;
            } else {
                $cart = WC()->cart->get_cart();
                $current_user = wp_get_current_user();
                $current_customer = new WC_Customer($current_user->ID);
                $checkout = WC()->checkout();

                $order_data = [
                    'billing' => [
                        'first_name' => $current_customer->get_billing_first_name(),
                        'last_name' => $current_customer->get_billing_last_name(),
                        'email' => $current_customer->get_billing_email(),
                        'country' => $current_customer->get_billing_country(),
                        'state' => $current_customer->get_billing_state(),
                        'postcode' => $current_customer->get_billing_postcode(),
                        'city' => $current_customer->get_billing_city(),
                        'address' => $current_customer->get_billing_address(),
                        'address_1' => $current_customer->get_billing_address(),
                        'address_2' => $current_customer->get_billing_address_2(),
                    ],
                    'shipping' => [
                        'first_name' => $current_customer->get_shipping_first_name(),
                        'last_name' => $current_customer->get_shipping_last_name(),
                        'country' => $current_customer->get_shipping_country(),
                        'state' => $current_customer->get_shipping_state(),
                        'postcode' => $current_customer->get_shipping_postcode(),
                        'city' => $current_customer->get_shipping_city(),
                        'address' => $current_customer->get_shipping_address(),
                        'address_1' => $current_customer->get_shipping_address(),
                        'address_2' => $current_customer->get_shipping_address_2(),
                    ],
                    'payment_method' => $gateway_id,
                ];

                // Simulate checkout fields
                $order_id = $checkout->create_order($order_data);

                // Get the order object
                $order = wc_get_order($order_id);

                // Optionally, set status or do post-processing
                $order->update_status(self::ORDER_STATUS_PENDING, __('Order created for express checkout.', 'paysafe-checkout'));

                $order->calculate_totals();

                // Store Order ID in session, so it can be re-used after payment failure.
                WC()->session->set( 'order_awaiting_payment', $order_id );

                // We save the session early because if the payment gateway hangs
                // the request will never finish, thus the session data will never be saved,
                // and this can lead to duplicate orders if the user submits the order again.
                WC()->session->save_data();

                if ($gateway_id) {
                    update_post_meta($order_id, 'payment_gateway', $gateway_id);

                    // update order payment method title
                    $order->set_payment_method($gateway_id);
                    $order->set_payment_method_title(
                        $this->get_payment_gateway_public_name($gateway_id)
                    );
                    $order->save();
                }

                $result['order_id'] = $order_id;
            }

	        $result['merchant_ref_num'] = $this->get_merchant_reference_number($order_id);

            if ($is_apple_pay) {
                $result['total_price'] = (int)($order->get_total() * 100);
                $result['currency_code'] = $order->get_currency();
                $result['billing'] = $order_data['billing'];
            }

            wp_send_json($result, 200);
            wp_die();
        } catch (Exception $e) {
            $this->log_error(
                'Paysafe Checkout: Exception while creating order for express checkout: ' . $e->getMessage()
            );

            wp_send_json([
                'status' => 'fail',
                'message' => __('Failed to create order for express checkout.', 'paysafe-checkout'),
            ], 400);
            wp_die();
        }
    }

    /**
     * Show test mode notice at checkout
     *
     * @return void
     */
    protected function show_test_mode_notice(): void
    {
        ob_start();

        if ($this->id === WC_Gateway_Paysafe::PAYMENT_GATEWAY_CODE) {
            include_once PAYSAFE_WOO_PLUGIN_PATH . '/resources/html/general/test-mode-notice.php';
        } else {
            ?>
            <div style="text-align: justify; margin-bottom: 10px">
                <?php echo esc_html(__(
                    'Paysafe Checkout is in TEST mode. Use the built-in simulator to test payments.',
                    'paysafe-checkout'
                )); ?>
            </div>
            <?php
        }

        ob_end_flush();
    }

    /**
     * Show saved tokens if user has them
     *
     * @return void
     */
    public function payment_fields() {
        $paysafe_wc_options = $this->get_paysafe_settings();
        $is_test_mode = ($paysafe_wc_options['test_mode'] ?? null) === 'yes';
        if ($is_test_mode) {
            $this->show_test_mode_notice();
        }

        parent::payment_fields();
    }

    /**
     * Get extra payment method detail parameters
     *
     * @return array
     */
    public function get_extra_payment_details(): array
    {
        return [];
    }

    /**
     * Return the consumer id (for some payment gateways)
     *
     * @return string
     */
    public function get_consumer_id(): string
    {
        // todo find another consumer id for LPM's when the user is not logged in
        try {
            $current_customer = new WC_Customer(get_current_user_id());

            return (string)$current_customer->get_billing_email();
        }
        catch (\Exception $e) {
	        return $this->safe_id() . 'paysafe';
        }
    }

    /**
     * Encrypt the consumer id (for some payment gateways)
     *
     * @return string
     */
    public function get_encrypted_consumer_id(): string
    {
        return substr(hash("sha512", $this->get_consumer_id()), 0, 50);
    }

    /**
     * Get the selected account id for a payment gateway
     *
     * @return int
     */
    public function get_account_id(): int
    {
        return 0;
    }

    /**
     * Get the selected account id issuer country for a payment gateway
     *
     * @return string
     */
    public function get_account_issuer_country(): string
    {
        return '';
    }

    /**
     * Get the selected account id for a payment gateway
     *
     * @param string $gateway_code
     * @return int
     */
    public function get_account_id_for_gateway(string $gateway_code): int
    {
        $paysafe_wc_options = $this->get_paysafe_settings();
        $currency_code = get_woocommerce_currency();

        return (int) ($paysafe_wc_options['account_id_' . $currency_code . '_' . $gateway_code . $this->test_live_suffix()] ?? 0);
    }

    /**
     * Save merchant reference number to identify order at webhook payload processing
     *
     * @param int $order_id
     * @param string $merchant_reference_number
     *
     * @return void
     */
    public function save_merchant_reference_number_for_order(int $order_id, string $merchant_reference_number): void
    {
        add_post_meta($order_id, self::PAYSAFE_MERCHANT_REF_NUM, trim($merchant_reference_number));
    }

    /**
     * Return the order_id from a merchant reference number
     *
     * @param string $merchant_ref_num
     *
     * @return int|null
     */
    public function get_order_id_by_merchant_reference_number(string $merchant_ref_num = ''): ?int
    {
        // try to get the order id, if we can't get one, abort
        $merchant_ref_num_parts = explode('.', $merchant_ref_num ?? '');
        if (!isset($merchant_ref_num_parts[0])) {
            return null;
        }

        // get the order_id and hydrate the order
        // if order not valid, abort
        $order_id = (int)$merchant_ref_num_parts[0];
        $order = wc_get_order($order_id);
        if ($order) {
            // get the merchant reference numbers that are logged for this order
            // if there is a match, return the order id
            $merchant_reference_numbers = get_post_meta($order_id, self::PAYSAFE_MERCHANT_REF_NUM);
            foreach ($merchant_reference_numbers as $merchant_reference_number) {
                if ($merchant_reference_number === $merchant_ref_num) {
                    return $order_id;
                }
            }
        }

        // identification failed, abort
        return null;
    }

    /**
     * Handle webhook event decisions based on the event name
     *
     * @param string $order_id
     * @param string $webhook_log_number
     * @param string $event_name
     * @param string|null $id
     *
     * @return void
     */
    private function handle_webhook_payload_event_breakdown(
        string $order_id,
        string $webhook_log_number,
        string $event_name,
        string $id = null
    ): void
    {
        if (!defined('PAYSAFE_WH_ORDER_NO')) {
            define('PAYSAFE_WH_ORDER_NO', ' WebHook - Order #');
            define('PAYSAFE_RECEIVED_EVENT', ' - Received event ');
            define('PAYSAFE_CHANGING_STATUS_FROM', ' - Changing status from ');
            define('PAYSAFE_CHANGING_STATUS_TO', '` to `');
        }

        $order = wc_get_order($order_id);
        $order_current_status = $order->get_status();

        switch ($event_name) {
            case 'PAYMENT_HANDLE_INITIATED':
            case 'PAYMENT_HANDLE_PROCESSING':
            case 'PAYMENT_HANDLE_PAYABLE':
            case 'PAYMENT_HANDLE_COMPLETED':
                // positive outcome for payment handles
                $this->log_debug(
                    '(' . $webhook_log_number . ')'
                    . PAYSAFE_WH_ORDER_NO . $order_id
                    . PAYSAFE_RECEIVED_EVENT . ' ph ' . $event_name);
                break;

            case 'PAYMENT_HANDLE_FAILED':
                // negative outcome for payment handles
                $this->log_debug(
                    '(' . $webhook_log_number . ')'
                    . PAYSAFE_WH_ORDER_NO . $order_id
                    . PAYSAFE_RECEIVED_EVENT . ' phf ' . $event_name);
                break;

            case 'PAYMENT_PROCESSING':
            case 'PAYMENT_RECEIVED':
            case 'PAYMENT_COMPLETED':
                // positive outcome for the payment
                $this->log_debug(
                    '(' . $webhook_log_number . ')'
                    . PAYSAFE_WH_ORDER_NO . $order_id
                    . PAYSAFE_RECEIVED_EVENT . ' p ' . $event_name);
                break;

            case 'PAYMENT_HELD':
                // ???? outcome for the payment
                $this->log_debug(
                    '(' . $webhook_log_number . ')'
                    . PAYSAFE_WH_ORDER_NO . $order_id
                    . PAYSAFE_RECEIVED_EVENT . $event_name);
                break;

            case 'PAYMENT_FAILED':
                // negative outcome for the payment, cancel payment and order here?
                // payment failed, cancel the order
                $this->log_debug(
                    '(' . $webhook_log_number . ')'
                    . PAYSAFE_WH_ORDER_NO . $order_id
                    . PAYSAFE_CHANGING_STATUS_FROM . '`' . $order_current_status . PAYSAFE_CHANGING_STATUS_TO
                    . self::ORDER_STATUS_FAILED . '`');
                if ($order_current_status !== self::ORDER_STATUS_FAILED) {
                    $order->update_status(self::ORDER_STATUS_FAILED);
                    $order->add_order_note(
                        __('Payment failed - webhook notification', 'paysafe-checkout')
                    );
                }
                break;

            case 'PAYMENT_CANCELLED':
                // negative outcome for the payment, cancel payment and order here?
                // payment failed, cancel the order
                $this->log_debug(
                    '(' . $webhook_log_number . ')'
                    . PAYSAFE_WH_ORDER_NO . $order_id
                    . PAYSAFE_CHANGING_STATUS_FROM . '`' . $order_current_status . PAYSAFE_CHANGING_STATUS_TO
                    . self::ORDER_STATUS_CANCELLED . '`');
                if ($order_current_status !== self::ORDER_STATUS_CANCELLED) {
                    $order->update_status(self::ORDER_STATUS_CANCELLED);
                    $order->add_order_note(
                        __('Payment was cancelled - webhook notification', 'paysafe-checkout')
                    );
                }
                break;

            case 'PAYMENT_EXPIRED':
                $this->log_debug(
                    '(' . $webhook_log_number . ')'
                    . PAYSAFE_WH_ORDER_NO . $order_id
                    . PAYSAFE_CHANGING_STATUS_FROM . '`' . $order_current_status . PAYSAFE_CHANGING_STATUS_TO
                    . self::ORDER_STATUS_CANCELLED . '`');
                if ($order_current_status !== self::ORDER_STATUS_CANCELLED) {
                    $order->update_status(self::ORDER_STATUS_CANCELLED);
                    $order->add_order_note(
                        __('Payment expired - webhook notification', 'paysafe-checkout')
                    );
                }
                break;

            case 'SETTLEMENT_RECEIVED':
                // when the settlement is received, this means the payment is still to be done
                // we can save the settlement id, but no status change
                update_post_meta($order_id, self::ORDER_META_KEY_SETTLEMENT_ID, $id);
                break;

            case 'SETTLEMENT_PENDING':
            case 'SETTLEMENT_PROCESSING':
                // do not do anything !!!
                $this->log_debug(
                    '(' . $webhook_log_number . ')'
                    . PAYSAFE_WH_ORDER_NO . $order_id
                    . PAYSAFE_RECEIVED_EVENT . ' s ' . $event_name);
                break;

            case 'SETTLEMENT_COMPLETED':
                // positive outcome for the settlement
                // if status is pending, refunded, cancelled or on-hold make it processing
                if (in_array($order_current_status, [
                    self::ORDER_STATUS_PENDING,
                    self::ORDER_STATUS_REFUNDED,
                    self::ORDER_STATUS_CANCELLED,
                    self::ORDER_STATUS_ON_HOLD,
                ])) {
                    $this->log_debug(
                        '(' . $webhook_log_number . ')'
                        . PAYSAFE_WH_ORDER_NO . $order_id
                        . PAYSAFE_CHANGING_STATUS_FROM . '`' . $order_current_status . PAYSAFE_CHANGING_STATUS_TO
                        . self::ORDER_STATUS_PROCESSING . '`');
                    $order->update_status(self::ORDER_STATUS_PROCESSING);
                    $order->add_order_note(
                        sprintf(
                            __('Payment successfully completed.', 'paysafe-checkout') . ' ' .
                            /* translators: %s is replaced by ID of the settlement */
                            __('Payment ID: %s.', 'paysafe-checkout') . ' ' .
                            __(
                                'If you wish to cancel the payment, you must change the order status to Cancelled.',
                                'paysafe-checkout'
                            ) . ' ' .
                            __(
                                'To process a refund, first click the \"Refund\" button, then select \"Refunds via Paysafe\".',
                                'paysafe-checkout'
                            ) . ' ' .
                            __(
                                'Refunds are not available until the payment is fully captured and the transaction is settled.',
                                'paysafe-checkout'
                            ),
                            $id
                        )
                    );
                    update_post_meta($order_id, self::ORDER_META_KEY_SETTLEMENT_ID, $id);
                } else {
                    $this->log_debug(
                        '(' . $webhook_log_number . ')'
                        . PAYSAFE_WH_ORDER_NO . $order_id
                        . PAYSAFE_RECEIVED_EVENT . $event_name
                        . ' - current status not in allowed statuses (pending, refunded, cancelled or on-hold)');
                }
                break;

            case 'SETTLEMENT_FAILED':
                // negative outcome for the settlement
                // it failed, cancel the order
                $this->log_debug(
                    '(' . $webhook_log_number . ')'
                    . PAYSAFE_WH_ORDER_NO . $order_id
                    . PAYSAFE_CHANGING_STATUS_FROM . '`' . $order_current_status . PAYSAFE_CHANGING_STATUS_TO
                    . self::ORDER_STATUS_FAILED . '`');
                if ($order_current_status !== self::ORDER_STATUS_FAILED) {
                    $order->update_status(self::ORDER_STATUS_FAILED);
                    $order->add_order_note(
                        __('Settlement failed - webhook notification', 'paysafe-checkout')
                    );
                }
                break;

            case 'SETTLEMENT_CANCELLED':
                // negative outcome for the settlement
                // it failed, cancel the order
                $this->log_debug(
                    '(' . $webhook_log_number . ')'
                    . PAYSAFE_WH_ORDER_NO . $order_id
                    . PAYSAFE_CHANGING_STATUS_FROM . '`' . $order_current_status . PAYSAFE_CHANGING_STATUS_TO
                    . self::ORDER_STATUS_CANCELLED . '`');
                if ($order_current_status !== self::ORDER_STATUS_CANCELLED) {
                    $order->update_status(self::ORDER_STATUS_CANCELLED);
                    $order->add_order_note(
                        __('Settlement was cancelled - webhook notification', 'paysafe-checkout')
                    );
                }
                break;

            default:
                $this->log_debug(
                    '(' . $webhook_log_number . ')'
                    . PAYSAFE_WH_ORDER_NO . $order_id
                    . PAYSAFE_RECEIVED_EVENT . $event_name . ' def ' . ' - no action taken');
                break;
        }
    }

    /**
     * Handle webhook endpoint payload identification and processing
     *
     * @return void
     */
    public function handle_webhook_payload(): void
    {
        // allow the webhook processing only once on the main gateway
        if ($this->id !== WC_Gateway_Paysafe::PAYMENT_GATEWAY_CODE) {
            return;
        }

        // generate a random number to be able to follow the logs for one webhook call
        // these calls can come in a bunch at a time, and can be hard to track each log step if it's not numbered
        try {
            $webhook_log_number = random_int(1000, 9999);
        }
        catch (\Exception $e) {
            $webhook_log_number = time();
        }

        // init logging for this payload call
        $this->log_debug('(' . $webhook_log_number . ')' . ' WebHook called');

        // get the request string and request payload
        $request_string = file_get_contents('php://input');
        $request_data = wp_kses_post_deep(json_decode($request_string, true));

        // get the header signature
        $signature = sanitize_text_field(wp_unslash($_SERVER['HTTP_SIGNATURE'] ?? ''));

        // log request and signature
        $this->log_debug('(' . $webhook_log_number . ')' . ' WebHook payload',
            [
                'request'   => $request_data,
                'signature' => $signature,
            ]
        );

        // is the signature valid?
        $is_valid_signature = $this->is_valid_webhook_signature($signature, $request_string);

        // if the signature is invalid, just give up
        if (!$is_valid_signature) {
            $this->log_debug('(' . $webhook_log_number . ')' . ' WebHook signature INVALID');

            wp_send_json([
                'status'    => 'FAIL',
            ], 400);
            wp_die();
        }

        // celebrate, signature is valid
        $this->log_debug('(' . $webhook_log_number . ')' . ' WebHook signature valid');

        // get teh merchant referent number and identified order id
        $merchant_ref_num = $request_data['payload']['merchantRefNum'] ?? '';
        $order_id = $this->get_order_id_by_merchant_reference_number($merchant_ref_num);

        // is no order id was identified, just quit
        if (!$order_id) {
            $this->log_debug(
                '(' . $webhook_log_number . ')' . ' WebHook order NOT identified! (got '
                . ($merchant_ref_num ?? '-') . ')');

            wp_send_json([
                'status'    => 'FAIL',
            ], 400);
            wp_die();
        }

        // we have an order and an order id at this point
        $this->log_debug('(' . $webhook_log_number . ')' . ' WebHook order identified as #' . $order_id);
        $order = wc_get_order($order_id);

        // if webhook event refers to a card payment, skip processing it
        if ($order->get_payment_method() === WC_Gateway_Paysafe::PAYMENT_GATEWAY_CODE) {
            // all other payment gateways, except 'paysafe' are LPM's, they have to receive webhook notifications
            // 'paysafe' payments (CARD aka Paysafe Checkout) can be skipped
            $this->log_debug(
                '(' . $webhook_log_number . ')'
                . ' Order paid with CARD payment, we skip webhook events for card');
            return;
        }

        $event_name = $request_data['eventName'] ?? ($request_data['EventName'] ?? '');
        $id = $request_data['payload']['id'] ?? null;

        $this->handle_webhook_payload_event_breakdown(
            $order_id,
            $webhook_log_number,
            $event_name,
            $id
        );

        // we are done with the webhook call
        $this->log_debug('(' . $webhook_log_number . ')' . ' WebHook call done');

        // send all ok back
        wp_send_json([
            'status'    => 'OK',
        ]);
        wp_die();
    }

    /**
     * Validate webhook payload signature
     *
     * @param string $signature
     * @param string $request_string
     *
     * @return bool
     */
    protected function is_valid_webhook_signature(string $signature, string $request_string): bool
    {
        $hmac_payload_string = hash_hmac('sha256', trim($request_string), trim($this->get_webhook_secret_key()), true);

        return $signature === base64_encode($hmac_payload_string);
    }

    /**
     * Return the webhook secret key from settings
     *
     * @return string
     */
    protected function get_webhook_secret_key(): string
    {
        $paysafe_wc_options = $this->get_paysafe_settings();

        return base64_decode($paysafe_wc_options['webhook_secret_key' . $this->test_live_suffix()] ?? '');
    }

    /**
     * Don't show the refund button for certain payment gateways
     * because they are not supported by the gateway
     *
     * @param $render_refunds
     * @param $order_id
     * @param $order
     *
     * @return bool
     */
    public function allow_refunds_button($render_refunds, $order_id, $order): bool
    {
        if ($order->get_payment_method() === WC_Gateway_Paysafe_Neteller::PAYMENT_GATEWAY_CODE) {
            return false;
        }

        return $render_refunds;
    }

    /**
     * A translator for all the gateway codes to human friendly names
     *
     * @param string $gateway_code
     *
     * @return string
     */
    private function get_payment_gateway_public_name(string $gateway_code): string
    {
        return match ($gateway_code) {
            'paysafe', 'CARD' => __('Credit/Debit Cards', 'paysafe-checkout'),
            'apple_pay', 'APPLEPAY' => __('Apple Pay', 'paysafe-checkout'),
            'apple_pay_express', 'APPLEPAYEXPRESS' => __('Apple Pay Express', 'paysafe-checkout'),
            'google_pay', 'GOOGLEPAY' => __('Google Pay', 'paysafe-checkout'),
            'google_pay_express', 'GOOGLEPAYEXPRESS' => __('Google Pay Express', 'paysafe-checkout'),
            'skrill', 'SKRILL' => __('Skrill', 'paysafe-checkout'),
            'neteller', 'NETELLER' => __('Neteller', 'paysafe-checkout'),
            'paysafecash', 'PAYSAFECASH' => __('PaysafeCash', 'paysafe-checkout'),
            'paysafecard', 'PAYSAFECARD' => __('PaysafeCard', 'paysafe-checkout'),
            'eft', 'EFT' => __('Eft', 'paysafe-checkout'),
            'ach', 'ACH' => __('ACH', 'paysafe-checkout'),
            'paysafepaypal', 'PAYPAL' => __('PayPal', 'paysafe-checkout'),
            'sightline', 'SIGHTLINE' => __('Play+ (Sightline)', 'paysafe-checkout'),
            'vippreferred', 'VIPPREFERRED' => __('VIP Preferred', 'paysafe-checkout'),
            'pay_by_bank', 'PAY_BY_BANK' => __('Pay by Bank', 'paysafe-checkout'),
            'venmo', 'VENMO' => __('Venmo', 'paysafe-checkout'),
            default => __('Paysafe Checkout', 'paysafe-checkout'),
        };
    }

    /**
     * Force our legacy checkout gateway icons to be normal size
     *
     * @param $icon_element
     * @param $gateway_id
     *
     * @return mixed|string
     */
    public function paysafe_get_icon($icon_element, $gateway_id)
    {
        if ($icon_element) {
            $icon_element = str_replace('/>', ' style="max-height: 21px" />', $icon_element);
        }

        return $icon_element;
    }

    public static function get_gateway_icon(){
        return PAYSAFE_WOO_PLUGIN_URL . 'assets/img/' . static::PAYMENT_ICON_FILE;
    }

	/**
     * Get paysafe customer data
     *
	 * @return array|null
	 */
    protected function get_paysafe_customer_data(): ?array
    {
	    $customer_id = $this->get_paysafe_customer_id();

        if (empty($customer_id)) {
            return null;
        }

        try {
	        // get the api connector
	        $api_connector = new PaysafeApiCardPluginConnector();

	        return $api_connector->getPaysafeCustomerData($customer_id, ['fields' => 'paymenthandles']);
        } catch (PaysafeException $e) {
	        $this->log_error(
		        self::PAYSAFE_EXCEPTION_TEXT
		        . $e->getMessage(),
		        $e->getAdditionalData()
            );

	        wc_add_notice( $e->getMessage(), 'error' );
        }

        return null;
    }

	/**
     * Get paysafe customer payment handles and return the ID of the given payment handle token
     *
	 * @param string $paysafe_token
	 *
	 * @return string|null
	 */
    protected function get_paysafe_token_id(string $paysafe_token): ?string
    {
        $customer_data = $this->get_paysafe_customer_data();

        foreach ($customer_data['paymentHandles'] ?? [] as $payment_handle_data) {
	        if ( ( $payment_handle_data['paymentHandleToken'] ?? '' ) === $paysafe_token ) {
		        return $payment_handle_data['id'] ?? null;
	        }
        }

        return null;
    }

	/**
     * Get the paysafe payment handle form a woo saved tokne
     *
	 * @param string $woo_token
	 *
	 * @return string
	 */
    protected function get_saved_card_token(string $woo_token): string
    {
        return ($this->get_saved_card_data($woo_token))['token'] ?? '';
    }

	/**
     * Get the encrypted data stored in a woo token
     *
	 * @param string $woo_token
	 *
	 * @return array
	 */
    protected function get_saved_card_data(string $woo_token): array
    {
        $id = null;

	    $woo_token = base64_decode($woo_token);

	    if (!str_contains($woo_token, '--' ))  {
		    $token = $woo_token;
	    } else {
            $token_data = explode('--', $woo_token);
            $id = $token_data[0];
            $token = $token_data[1] ?? '';
	    }

        return [
            'id' => $id,
            'token' => $token,
        ];
    }

	/**
     * Handle the exchange of the customer's single use payment handle into a multi use payment handle
     * to save the card locally in woocommerce
     *
	 * @param string $payment_handle_token
	 *
	 * @return array|null
	 */
    protected function handle_suph_to_muph_exchange(string $payment_handle_token): ?array
    {
        try {
	        $paysafe_customer_id = $this->get_paysafe_customer_id();
            if (!$paysafe_customer_id) {
                // try to create the customer
                $paysafe_customer_id = $this->create_paysafe_customer_from_payment_handle($payment_handle_token);
            }

            if ($paysafe_customer_id) {
	            $api_connector = new PaysafeApiCardPluginConnector();

	            $multi_use_token_data = $api_connector->handleSuph2MuphExchange(
		            $paysafe_customer_id,
		            [
			            'paymentHandleTokenFrom' => $payment_handle_token,
		            ]
	            );

                if (
                    isset($multi_use_token_data['error']) &&
                    (int)$multi_use_token_data['error']['code'] ?? null === self::PAYSAFE_ERROR_DUPLICATE_CARD
                ) {
                    // in case this card is already added as a saved card,
                    // retrieve the card id and match it against the multi use tokens of the customer
                    // and return that information
                    $matches = [];
                    preg_match('/([^\s\-]+-[^\s\-]+-[^\s\-]+-[^\s\-]+-[^\s\-]+)/', $multi_use_token_data['error']['message'] ?? '', $matches);
                    $saved_card_id = $matches[1] ?? null;
                    if ($saved_card_id) {
	                    $customer_data = $this->get_paysafe_customer_data();
	                    foreach ( $customer_data['paymentHandles'] ?? [] as $payment_handle_data ) {
		                    if ( ( $payment_handle_data['card']['id'] ?? '' ) === $saved_card_id ) {
			                    $multi_use_token_data = $payment_handle_data;
                                break;
		                    }
	                    }
                    }
                }

                return $multi_use_token_data;
            }
        } catch (PaysafeException $e) {
	        $this->log_error(
		        'Paysafe Checkout: Exchange of Single use Payment Handle into Multi Use Failed (Paysafe Exception)', [
		        'exception' => $e->getMessage(),
		        'paysafe_api_error'   => $e->getAdditionalData(),
	        ]);
        }

	    return null;
    }

	/**
     * Create the paysafe customer if it's not created yet
     *
	 * @param string $paysafe_token
	 *
	 * @return string|null
	 */
	public function create_paysafe_customer_from_payment_handle(string $paysafe_token): ?string
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
					'paymentType'               => WC_Gateway_Paysafe::PAYMENT_TYPE_CODE,
					'paymentHandleTokenFrom'    => $paysafe_token,
				]
			);

			// get the paysafe customer id that was created for this customer
			$paysafe_customer_id = $result['id'] ?? null;
			if (!$paysafe_customer_id) {
				// if there is no paysafe customer id and there is an error code
				// which means that merchant is already registered,
				// retrieve the paysafe customer id and continue
				if ( ( (int) $result['error']['code'] ?? null )
				     === self::PAYSAFE_ERROR_MERCHANT_CUSTOMER_ALREADY_REGISTERED ) {
					return $this->get_paysafe_customer_id_from_paysafe();
				}
			}

            $this->save_paysafe_customer_id($paysafe_customer_id);

            return $paysafe_customer_id;
		} catch (PaysafeException $e) {
			$this->log_error(
				'Paysafe Checkout: Paysfe Exception "' . $e->getMessage() . '"',
				array_merge($result ?? [], $e->getAdditionalData()));
		} catch (\Exception $e) {
			$this->log_error('Paysafe Checkout: Exception "' . $e->getMessage() . '"');
		}

		return null;
	}

    /**
     * Scheduled_subscription_payment function.
     *
     * @param float $amount_to_charge The amount to charge.
     * @param WC_Order $renewal_order A WC_Order object created to record the renewal payment.
     *
     * @return void
     */
    public function scheduled_subscription_payment(float $amount_to_charge, WC_Order $renewal_order): void
    {
        $payment_handle_token = $this->get_subscription_payment_handle_from_renewal($renewal_order);
        $order_id = $renewal_order->get_id();
        $success = false;
        $error_message = null;
        $amount_to_charge *= 100;

        if ($payment_handle_token) {
            update_post_meta(
                $order_id,
                self::ORDER_META_KEY_LAST_ACTION,
                self::PAYSAFE_ACTION_HANDLE
            );

            // add order note with the payment handle
            $renewal_order->add_order_note(
                sprintf(
                /* translators: %s is replaced by the name of the payment method */
                    __(
                        'Payment initiated with payment method %s.',
                        'paysafe-checkout'
                    ).' '.
                    /* translators: %s is replaced by ID of the payment handle */
                    __(
                        'Payment handle created. Payment handle ID: %s',
                        'paysafe-checkout'
                    ),
                    $this->get_payment_gateway_public_name($renewal_order->get_payment_method()),
                    $payment_handle_token
                )
            );

            // call paysafe api to make a payment api call
            list($success, $error_message) = $this->handle_process_paysafe_payment(
                $order_id,
                $payment_handle_token,
                $amount_to_charge,
                null,
                null,
                null,
                true
            );
        }

        if ($success) {
            $this->log_debug(
                self::PAYSAFE_ORDER_ID_KEYWORD
                .$order_id.'] Paysafe Subscription: Process Payment Response Successful'
            );

            $initial_transaction_id = $this->get_subscription_order_initial_transaction_id(wc_get_order($order_id));
            if (!$initial_transaction_id) {
	            $transaction_id = get_post_meta(
		            $order_id,
		            self::ORDER_META_KEY_PROCESS_PAYMENT_ID,
		            true
	            );

	            $original_order_id = $this->get_initial_subscription_order_id($order_id);
	            update_post_meta(
		            $original_order_id,
		            self::ORDER_META_KEY_SUBSCRIPTION_INITIAL_TID,
		            $transaction_id
	            );
            }
            WC_Subscriptions_Manager::process_subscription_payments_on_order($renewal_order);
        } else {
            $this->log_debug(
                self::PAYSAFE_ORDER_ID_KEYWORD
                .$order_id
                .'] Paysafe Subscription: Process Payment Response Error: '
                .$error_message
            );
            $renewal_order->update_status('failed', 'Payment failed: ' . $error_message);
            WC_Subscriptions_Manager::process_subscription_payment_failure_on_order($renewal_order);
        }
    }

	/**
     * Is the order part of a subscription or a renewal?
     *
	 * @param int $order_id
	 *
	 * @return bool
	 */
	public function is_order_part_of_subscription(int $order_id): bool
	{
		if (!$this->is_subscriptions_support_enabled()) {
			return false;
		}

		if (
			wcs_is_subscription($order_id) ||
            wcs_order_contains_subscription($order_id) ||
            wcs_order_contains_renewal($order_id) ||
            wcs_order_contains_early_renewal($order_id)
        ) {
			return true;
		}

		return false;
	}

	/**
     * Return the original subscription order id in case of a subscription or renewal order
     *
	 * @param int $order_id
	 *
	 * @return int
	 */
	public function get_initial_subscription_order_id(int $order_id): int
	{
		if (!$this->is_subscriptions_support_enabled()) {
			return $order_id;
		}

		$order = wc_get_order($order_id);
		$subscriptions = wcs_get_subscriptions_for_order($order, ['order_type' => 'renewal']);

		foreach ($subscriptions as $subscription) {
			$parent = $subscription->get_parent();
			if ($parent) {
				return $parent->get_id();
			}
		}

		return $order_id;
	}

	/**
	 * Return whether the current cart/order is a subscription or not
	 *
	 * @return bool
	 */
	public function is_subscription_cart(): bool
	{
        if (!$this->is_subscriptions_support_enabled()) {
            return false;
        }

		return WC_Subscriptions_Cart::cart_contains_subscription() || !! wcs_cart_contains_renewal();
	}

    /**
     * Retrieve multi-use token of a renewal order from the parent order
     *
     * @param WC_Order $renewal_order
     *
     * @return string|null
     */
    public function get_subscription_payment_handle_from_renewal(WC_Order $renewal_order): ?string
    {
        $parent_order_id = $this->get_initial_subscription_order_id($renewal_order->get_id());

        if ($parent_order_id) {
	        return (string)get_post_meta($parent_order_id, self::ORDER_META_KEY_SUBSCRIPTION_INITIAL_MUT, true);
        }

        return null;
    }

    /**
     * Retrieve transaction id of an order from the parent order
     *
     * @param WC_Order $renewal_order
     *
     * @return string|null
     */
    public function get_subscription_order_initial_transaction_id(WC_Order $renewal_order): ?string
    {
	    $parent_order_id = $this->get_initial_subscription_order_id($renewal_order->get_id());

	    if ($parent_order_id) {
		    return (string)get_post_meta($parent_order_id, self::ORDER_META_KEY_SUBSCRIPTION_INITIAL_TID, true);
	    }

	    return null;
    }

    /**
     * Is the subscription support enabled?
     *
	 * @return bool
	 */
    public function is_subscriptions_support_enabled(): bool
    {
        if (!PaysafeSettings::is_woocommerce_subscriptions_active()) {
            return false;
        }

	    if (!self::ALLOW_SUBSCRIPTION_SUPPORT) {
		    return false;
	    }

	    $settings = $this->get_paysafe_settings();

	    return ($settings['subscriptions_enabled'] ?? null) === 'yes';
    }

    /**
     * Is the subscription automatic capture enabled?
     *
	 * @return bool
	 */
    public function is_subscriptions_capture_enabled(): bool
    {
        if (!$this->is_subscriptions_support_enabled()) {
            return false;
        }

	    $settings = $this->get_paysafe_settings();

	    return ($settings['subscriptions_capture'] ?? null) === 'yes';
    }

	/**
     * update all subscriptions to have a new payment method
     *
	 * @param int $order_id
	 * @param string $mut
	 * @param string|null $transaction_id
	 *
	 * @return void
	 */
    public function update_all_subs_payment_method(int $order_id, string $mut, string $transaction_id = null): void
    {
	    if (!$this->is_subscriptions_support_enabled()) {
		    return;
	    }

	    $order = wc_get_order($order_id);
        $subscriptions = wcs_get_subscriptions(['order_type' => 'renewal']);

	    foreach ($subscriptions as $subscription) {
		    $parent = $subscription->get_parent();
		    if ($parent) {
			    $original_order_id = $parent->get_id();
                if ($transaction_id) {
	                update_post_meta(
		                $original_order_id,
		                self::ORDER_META_KEY_SUBSCRIPTION_INITIAL_TID,
		                $transaction_id
	                );
                }
			    update_post_meta(
				    $original_order_id,
				    self::ORDER_META_KEY_SUBSCRIPTION_INITIAL_MUT,
				    $mut
			    );
		    }
	    }
    }

	/**
	 * Process payment verification for an order
	 * after getting the payment handle token from checkout
	 *
	 * @param int $order_id
	 * @param string $payment_handle_token
	 *
	 * @return array
	 */
	public function handle_process_paysafe_verification(
		int $order_id,
		string $payment_handle_token,
	): array
	{
		$error_message = null;

		$this->log_debug(
			self::PAYSAFE_ORDER_ID_KEYWORD
			. $order_id . '] Paysafe Checkout: Handle process Paysafe VERIFICATION');

		$order = wc_get_order($order_id);
		if (!$order) {
			$this->log_notice(self::PAYSAFE_ORDER_ID_KEYWORD
			                  . $order_id . '] Paysafe Checkout: Order not found');

			return [false, $error_message];
		}

        $merchant_ref = $this->get_merchant_reference_number($order_id);

        try {
            $result = $this->get_verify_payment_result($order_id, $payment_handle_token, $merchant_ref);

			$this->log_debug(
				self::PAYSAFE_ORDER_ID_KEYWORD
				. $order_id
				. '] Paysafe Checkout: Handle Verification Result', [
				'order_id' => $order_id,
				'result' => $result
			]);

			$verification_id = $result['id'] ?? null;

			$accepted_statuses = [
				self::PAYSAFE_RESPONSE_STATUS_RECEIVED,
				self::PAYSAFE_RESPONSE_STATUS_COMPLETED,
			];

			$status = $result['status'] ?? null;

			if (empty($verification_id) || !in_array($status, $accepted_statuses, true)) {
				$this->log_error(
					self::PAYSAFE_ORDER_ID_KEYWORD . $order_id . '] Paysafe Checkout: '
					. 'Verification call returned unknown status "' . $status . '"', [
					'order_id' => $order_id,
					'status' => $status,
				]);

				throw new PaysafeException(
					sprintf(
					/* translators: %s is replaced by status returned */
						__('Verification call returned unknown status %s', 'paysafe-checkout'),
						$status ?? '-'
					),
					PaysafeException::PROCESS_PAYMENT_CALL_UNKNOWN_STATUS
				);
			}

			$this->log_debug(
				self::PAYSAFE_ORDER_ID_KEYWORD
				. $order_id
				. '] Paysafe Checkout: Verification successful');

	        update_post_meta($order_id, self::ORDER_META_KEY_PROCESS_PAYMENT_ID, $verification_id);

			return [true, $error_message];
		}
		catch (PaysafeException $e) {
			$error_message = $e->getMessage();

			$this->log_error(
				self::PAYSAFE_ORDER_ID_KEYWORD . $order_id
				. '] Paysafe Checkout: Verification Failed (Paysafe Exception)', [
				'order_id' => $order_id,
				'exception' => $e->getMessage(),
				'paysafe_api_error'   => $e->getAdditionalData(),
			]);

			// Payment failed
			$order->add_order_note(
				sprintf(
				/* translators: %s is replaced by the message */
					__('ERROR payment verification: %s', 'paysafe-checkout'),
					$e->getMessage()
				)
			);
		}
		catch (\Exception $e) {
			$error_message = $e->getMessage();

			$this->log_error(
				self::PAYSAFE_ORDER_ID_KEYWORD
				. $order_id
				. '] Paysafe Checkout: Verification Failed (Exception) "' . $e->getMessage() . '"');
		}

		return [
			false,
			$error_message
		];
	}

	/**
	 * Handle the process payment call and return the result
	 *
	 * @param string $order_id
	 * @param string $payment_handle_token
	 * @param string $merchant_ref
	 *
	 * @return array
	 *
	 * @throws PaysafeException
	 */
	private function get_verify_payment_result(
		string $order_id,
		string $payment_handle_token,
		string $merchant_ref,
	): array
	{
		$order = wc_get_order($order_id);
		$ip_address = $order->get_customer_ip_address();

		$params = [
			'merchantRefNum' => $merchant_ref,
			'paymentHandleToken' => $payment_handle_token,
            'dupCheck' => false,
            'description' => 'Payment verification',
		];

        if ($ip_address) {
            $params['customerIp'] = $ip_address;
        }

		if ($this->is_subscriptions_support_enabled()) {
            $params['storedCredential'] = [
                'type'       => 'RECURRING',
                'occurrence' => 'INITIAL',
            ];
		}

		$api_connector = new PaysafeApiCardPluginConnector();
		return $api_connector->handleVerifyPayment($params);
	}

    /**
     * Handle the Express Apple/Google Pay checkout request
     *
     * @return void
     */
    public function handle_paysafe_product_express_ag_pay_checkout(): void
    {
        $status = 'success';
        $message = '';

        try {
            // get the product data from the request
            $post = wp_kses_post_deep(json_decode(file_get_contents('php://input'), true));

            // check if the request is valid
            $nonce = $post['nonce'] ?? null;
            if ( ! wp_verify_nonce( $nonce, 'paysafe_payment_response' ) ) {
                throw new Exception(
                    __('Invalid nonce. Please refresh the page and try again', 'paysafe-checkout')
                );
            }

            $product_data = $post['product_data'] ?? null;

            // sanitize the product data
            $product_data = array_map( 'sanitize_text_field', $product_data );

            // get product details
            $product_id        = $product_data['product_id'] ?? 0;
            $quantity          = $product_data['quantity'] ?? 1;
            $variation_id      = $product_data['variation_id'] ?? 0;
            $variation         = $product_data['variation'] ?? [];
            $cart_item_data    = $product_data['cart_item_data'] ?? [];

            // empty cart before adding the product
            WC()->cart->empty_cart();

            // add the product to the cart
            WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variation, $cart_item_data );

            // create the order and return information to the Apple/Google Pay FE
            $this->handle_paysafe_express_checkout(true);
        } catch (\Exception $e) {
            // an error occurred, return failure
            $status = 'failure';
            $message = $e->getMessage();
        }

        wp_send_json([
                'result' => $status,
                'message' => $message,
        ], $status === 'success' ? 200 : 403);
        wp_die();
    }

	/**
	 * @return bool
	 */
    public function is_test_mode(): bool
    {
        $paysafe_wc_options = $this->get_paysafe_settings();
	    return ($paysafe_wc_options['test_mode'] ?? null) === 'yes';
    }

	/**
	 * @return string
	 */
    public function test_live_suffix(): string
    {
        return $this->is_test_mode() ? '_sandbox' : '_live';
    }

    /**
     * Is the express gateway enabled for the given gateway code
     *
     * @param string $gateway_code
     *
     * @return bool
     */
    public function is_express_gateway_enabled(string $payment_method): bool
    {
        if (!$this->is_payment_method_enabled($payment_method)) {
            return false;
        }

        $paysafe_wc_options = $this->get_paysafe_settings();
	    if ($payment_method === WC_Gateway_Paysafe_Google_Pay::PAYMENT_TYPE_CODE
            && WC_Gateway_Paysafe_Base::ALLOW_EXPRESS_PAYMENT_GOOGLE_PAY) {
		    return ($paysafe_wc_options[ 'google_pay_integration_type' . $this->test_live_suffix() ] ?? self::PAYMENT_INTEGRATION_DEFAULT) === self::PAYMENT_INTEGRATION_PAYSAFE_JS;
	    }

	    if ($payment_method === WC_Gateway_Paysafe_Apple_Pay::PAYMENT_TYPE_CODE
            && WC_Gateway_Paysafe_Base::ALLOW_EXPRESS_PAYMENT_APPLE_PAY) {
		    return ($paysafe_wc_options[ 'apple_pay_integration_type' . $this->test_live_suffix() ] ?? self::PAYMENT_INTEGRATION_DEFAULT) === self::PAYMENT_INTEGRATION_PAYSAFE_JS;
	    }

        return false;
    }

	/**
     * Generate a safe random ID
     *
	 *
	 * @return string
	 */
	private function safe_id(): string
	{
		try {
			return bin2hex(random_bytes( 16 ));
		} catch (\Throwable $e) {
			if (\function_exists('openssl_random_pseudo_bytes')) {
				return bin2hex(openssl_random_pseudo_bytes( 16 ));
			}

			// UUID v4 via WP (modern WP uses CSPRNG; older may not)
			return str_replace('-', '', wp_generate_uuid4()); // 32 hex chars
		}
	}
}
