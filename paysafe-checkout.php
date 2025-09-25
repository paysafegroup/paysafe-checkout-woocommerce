<?php
/**
 * @link                https://www.paysafe.com/
 * @since               1.0.0
 *
 *
 * Plugin Name:         Paysafe Checkout
 * Plugin URI:          https://developer.paysafe.com/en/api-docs/shopping-carts/woocommerce-official/
 * Description:         Accept payments on your WooCommerce store while minimizing your security and PCI compliance requirements.
 * Requires at least:   6.3
 * Requires PHP:        8.1
 * Version:             3.0.0
 * Author:              Paysafe
 * Author URI:          https://www.paysafe.com/
 * License:             GPLv2 or later
 * License URI:         https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain:         paysafe-checkout
 * Domain Path:         /i18n/languages/
 *
 * @package             paysafe-checkout
*/

// Exit if this page is accessed directly
if (! defined('ABSPATH')) {
    exit;
}
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// main definitions used through the plugin
define('PAYSAFE_WOO_VERSION', '3.0.0');
define('PAYSAFE_WOO_PLUGIN_PATH', dirname(__FILE__));
define('PAYSAFE_WOO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PAYSAFE_APP_NAME', 'paysafe-checkout');
define('PAYSAFE_SETTINGS_KEYWORD', 'woocommerce_paysafe_settings');


// ######### Paysafe hooks #########

// register activation hook
register_activation_hook(__FILE__, 'paysafe_wc_activation');

// register uninstall hook
register_deactivation_hook(__FILE__, 'paysafe_wc_deactivation');

// register uninstall hook
register_uninstall_hook(__FILE__, 'paysafe_wc_uninstall');

// register paysafe with woocommerce init workflow
add_action('woocommerce_init', 'paysafe_woo_init');

// register paysafe with woocommerce blocks
add_action('woocommerce_blocks_loaded', 'paysafe_woo_blocks');

// register paysafe checkout gateways
add_filter('woocommerce_payment_gateways', 'paysafe_woo_payment_gateways');

// // add the settings/config links to paysafe plugin listing
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'paysafe_woo_plugin_links');

// Forces Payment Methods to reappear when no payment gateways are available
add_filter('woocommerce_account_menu_items', 'paysafe_woo_menu_items', 40, 2);


// ######### Paysafe functions #########

// phpcs:disable WordPress.Security.NonceVerification.Recommended
$paysafe_is_settings_page = isset( $_GET['page'], $_GET['tab'] )
    && 'wc-settings' === sanitize_text_field(wp_unslash($_GET['page']))
    && str_starts_with(sanitize_text_field(wp_unslash($_GET['tab'])), 'checkout');
// phpcs:enable WordPress.Security.NonceVerification.Recommended

/**
 * Activate Paysafe Checkout plugin
 * 
 * check whether Woocommerce plugin is installed, 
 * warn and die if not present
 *
 * @since 1.0.0
 */
function paysafe_wc_activation(): void
{
    $active_plugins = get_option('active_plugins');
    if (is_multisite()) {
        $active_plugins = array_merge(
            $active_plugins, 
            wp_get_active_network_plugins()
        );
    }

    $active_plugins = apply_filters('active_plugins', $active_plugins);

    if (! stripos(implode('', $active_plugins), '/woocommerce.php')) {
        deactivate_plugins(plugin_basename(__FILE__)); // Deactivate ourselves.

        $message =
            __(
                'Sorry ! In order to use WooCommerce Paysafe Checkout plugin, you need to install and activate the WooCommerce plugin.',
                'paysafe-checkout'
            );
        wp_die(
            esc_html($message),
            esc_html(__('Paysafe Checkout', 'paysafe-checkout')),
            ['back_link' => true]
        );
    }
}

/**
 * Deactivate this plugin
 * @since 1.0.0
 */
function paysafe_wc_deactivation()
{
    // nothing to do here at this moment
}

/**
 * Remove all that was created by this plugin
 * @since 1.0.0
 */
function paysafe_wc_uninstall(): void
{
    // delete the settings where Paysafe config data is stored
    delete_option(PAYSAFE_SETTINGS_KEYWORD);

    // ... to be completed
}

/**
 * Init Paysafe and all its dependencies
 * @since 1.0.0
 */
function paysafe_woo_init(): void
{
    // load the paysafe exception
    if (! class_exists('PaysafeException')) {
        require_once PAYSAFE_WOO_PLUGIN_PATH . '/includes/exception/class-wc-exception-paysafe.php';
    }

    // load base paysafe checkout gateway
    if (! class_exists('WC_Gateway_Paysafe_Base')) {
        require_once PAYSAFE_WOO_PLUGIN_PATH . '/includes/gateway/class-wc-gateway-paysafe-base.php';
    }

    // load main paysafe checkout gateway
    if (! class_exists('WC_Gateway_Paysafe')) {
        require_once PAYSAFE_WOO_PLUGIN_PATH . '/includes/gateway/class-wc-gateway-paysafe.php';
    }

    // load other paysafe checkout gateway
    if (! class_exists('WC_Gateway_Paysafe_Apple_Pay')) {
        require_once PAYSAFE_WOO_PLUGIN_PATH . '/includes/gateway/class-wc-gateway-apple-pay.php';
    }
	if (! class_exists('WC_Gateway_Paysafe_Google_Pay')) {
		require_once PAYSAFE_WOO_PLUGIN_PATH . '/includes/gateway/class-wc-gateway-google-pay.php';
	}
    if (! class_exists('WC_Gateway_Paysafe_Skrill')) {
        require_once PAYSAFE_WOO_PLUGIN_PATH . '/includes/gateway/class-wc-gateway-paysafe-skrill.php';
    }
    if (! class_exists('WC_Gateway_Paysafe_Neteller')) {
        require_once PAYSAFE_WOO_PLUGIN_PATH . '/includes/gateway/class-wc-gateway-paysafe-neteller.php';
    }
    if (! class_exists('WC_Gateway_Paysafe_Cash')) {
        require_once PAYSAFE_WOO_PLUGIN_PATH . '/includes/gateway/class-wc-gateway-paysafe-cash.php';
    }
    if (! class_exists('WC_Gateway_Paysafe_Card')) {
        require_once PAYSAFE_WOO_PLUGIN_PATH . '/includes/gateway/class-wc-gateway-paysafe-card.php';
    }

    // load REST endpoints for admin settings
    if (! class_exists('Paysafe_Admin_REST_Controller')) {
        require_once PAYSAFE_WOO_PLUGIN_PATH . '/includes/api/class-paysafe-admin-rest-controller.php';
    }

    // load api classes
    if (! class_exists('PaysafeApiBasePluginConnector')) {
        require_once PAYSAFE_WOO_PLUGIN_PATH . '/includes/api/class-wc-api-paysafe-base.php';
    }
    if (! class_exists('PaysafeApiGeneralPluginConnector')) {
        require_once PAYSAFE_WOO_PLUGIN_PATH . '/includes/api/class-wc-api-paysafe-general.php';
    }
    if (! class_exists('PaysafeApiCardPluginConnector')) {
        require_once PAYSAFE_WOO_PLUGIN_PATH . '/includes/api/class-wc-api-paysafe-card.php';
    }

    // load the settings
    if (! class_exists('PaysafeSettings')) {
        require_once PAYSAFE_WOO_PLUGIN_PATH . '/settings/PaysafeSettings.php';
    }

    // load the paysafe sdk loggers
    if (! class_exists('PaysafeLoggerInterface')) {
        require_once PAYSAFE_WOO_PLUGIN_PATH . '/paysafe-sdk/Logging/Interfaces/PaysafeLoggerInterface.php';
    }
    if (! class_exists('PaysafeLoggerProvider')) {
        require_once PAYSAFE_WOO_PLUGIN_PATH . '/paysafe-sdk/Logging/PaysafeLoggerProvider.php';
    }
}

/**
 * Register Paysafe with Woocommerce Blocks
 * @since 1.0.0
 */
function paysafe_woo_blocks(): void
{
    GLOBAL $paysafe_is_settings_page;

    if (! class_exists('WC_Gateway_Paysafe_Blocks_Support')) {
        require_once PAYSAFE_WOO_PLUGIN_PATH . '/includes/blocks/WC_Gateway_Paysafe_Blocks_Support.php';
    }

    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function(Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry)
            use($paysafe_is_settings_page)
        {
            $payment_method_registry->register(
                new WC_Gateway_Paysafe_Blocks_Support(WC_Gateway_Paysafe::PAYMENT_GATEWAY_CODE)
            );
            if (!$paysafe_is_settings_page) {
                $payment_method_registry->register(
                    new WC_Gateway_Paysafe_Blocks_Support(WC_Gateway_Paysafe_Apple_Pay::PAYMENT_GATEWAY_CODE)
                );
	            $payment_method_registry->register(
		            new WC_Gateway_Paysafe_Blocks_Support(WC_Gateway_Paysafe_Google_Pay::PAYMENT_GATEWAY_CODE)
	            );
                $payment_method_registry->register(
                    new WC_Gateway_Paysafe_Blocks_Support(WC_Gateway_Paysafe_Skrill::PAYMENT_GATEWAY_CODE)
                );
                $payment_method_registry->register(
                    new WC_Gateway_Paysafe_Blocks_Support(WC_Gateway_Paysafe_Neteller::PAYMENT_GATEWAY_CODE)
                );
                $payment_method_registry->register(
                    new WC_Gateway_Paysafe_Blocks_Support(WC_Gateway_Paysafe_Card::PAYMENT_GATEWAY_CODE)
                );
                $payment_method_registry->register(
                    new WC_Gateway_Paysafe_Blocks_Support(WC_Gateway_Paysafe_Cash::PAYMENT_GATEWAY_CODE)
                );
            }
        });
}

/**
 * Register Paysafe payment gateways
 * @since 1.0.0
 */
function paysafe_woo_payment_gateways(): array
{
    GLOBAL $paysafe_is_settings_page;

    $gateways = [];

    $gateways[] = 'WC_Gateway_Paysafe';
    if (!$paysafe_is_settings_page) {
        $gateways[] = 'WC_Gateway_Paysafe_Apple_Pay';
		$gateways[] = 'WC_Gateway_Paysafe_Google_Pay';
        $gateways[] = 'WC_Gateway_Paysafe_Skrill';
        $gateways[] = 'WC_Gateway_Paysafe_Neteller';
        $gateways[] = 'WC_Gateway_Paysafe_Card';
        $gateways[] = 'WC_Gateway_Paysafe_Cash';
    }

    // ... to be completed ...

    return $gateways;
}

/**
 * Add settings/config links to plugin listing
 *
 * @param mixed $links
 *
 * @return array
 * @since 1.0.0
 */
function paysafe_woo_plugin_links($links): array
{
    return array_merge (
        [
            '<a href="' . paysafe_get_admin_page('Paysafe') . '">'
            . __('Settings', 'paysafe-checkout') . '</a>',
        ], 
        $links
    );
}

/**
 * Get the admin page URI for the plugin
 * @since 1.0.0
 */
function paysafe_get_admin_page($gateway_id): ?string
{
    global $woocommerce;

    $base_url = 'admin.php?page=wc-settings&tab=checkout&section=';
    $section = strtolower($gateway_id);

    // Backward compatibility.
    if (version_compare($woocommerce->version, '2.1.0', '<')) {
        $base_url = 'admin.php?page=woocommerce_settings&tab=payment_gateways&section=';
        $section = 'WC_Gateway_' . $gateway_id;
    } elseif (version_compare($woocommerce->version, '2.6.2', '<')) {
        $section = 'wc_gateway_' . $section;
    }

    return admin_url($base_url . $section);
}

/**
 * Forces Payment Methods to reappear when no payment gateways are available
 *
 * @param $menu_items
 *
 * @return array|mixed
 * @since 1.0.0
 */
function paysafe_woo_menu_items($menu_items): mixed
{
    if (!isset($menu_items['payment-methods'])) {
        $menu_items = array_slice($menu_items, 0, 4, true)
            + array('payment-methods' =>  __( 'Payment methods', 'paysafe-checkout' ))
            + array_slice($menu_items, 4, NULL, true);
    }

    return $menu_items;
}
