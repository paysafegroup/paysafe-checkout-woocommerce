<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
<div id="add-paysafe-payment-method-form" class="add-paysafe-payment-method-form">
    <?php 
        if ($is_test_mode) {
            include_once PAYSAFE_WOO_PLUGIN_PATH . '/resources/html/general/test-mode-notice.php';
            ?>
            <hr class="paysafe-separator paysafe-margin-bottom">
            <?php
        }
    ?>

    <p class="paysafe-add-saved-payment-method-title"><b><?php esc_html_e('Billing Address', 'paysafe-checkout')?></b></p>

    <!-- Billing address fields -->
    <div class="paysafe-billing-address-form">
        <div class="paysafe-w50 paysafe-left">
            <div class="paysafe-form-field">
                <b><?php esc_html_e('First Name', 'paysafe-checkout')?></b><br>
                <?php echo esc_html($billing_details['first_name']); ?>
            </div>
        </div>
        <div class="paysafe-w50 paysafe-right">
            <div class="paysafe-form-field">
                <b><?php esc_html_e('Last Name', 'paysafe-checkout')?></b><br>
                <?php echo esc_html($billing_details['last_name']); ?>
            </div>
        </div>
        <div class="paysafe-cf"></div>
        <div class="paysafe-form-field">
            <b><?php esc_html_e('Email address', 'paysafe-checkout')?></b><br>
            <?php echo esc_html($billing_details['email']); ?>
        </div>
        <div class="paysafe-w50 paysafe-left">
            <div class="paysafe-form-field">
            <b><?php esc_html_e('Country', 'paysafe-checkout')?></b><br>
            <?php echo esc_html($countries[$billing_details['country']] ?? $billing_details['country']); ?>
            </div>
        </div>
        <div class="paysafe-w50 paysafe-right">
            <div class="paysafe-form-field">
                <b><?php esc_html_e('State', 'paysafe-checkout')?></b><br>
                <?php echo esc_html($billing_details['state']); ?>
            </div>
        </div>
        <div class="paysafe-cf"></div>
        <div class="paysafe-w50 paysafe-left">
            <div class="paysafe-form-field">
                <b><?php esc_html_e('Postal code', 'paysafe-checkout')?></b><br>
                <?php echo esc_html($billing_details['zip']); ?>
            </div>
        </div>
        <div class="paysafe-w50 paysafe-right">
            <div class="paysafe-form-field">
                <b><?php esc_html_e('City', 'paysafe-checkout')?></b><br>
                <?php echo esc_html($billing_details['city']); ?>
            </div>
        </div>
        <div class="paysafe-cf"></div>
        <div class="paysafe-form-field">
            <b><?php esc_html_e('Street', 'paysafe-checkout')?></b><br>
            <?php echo esc_html($billing_details['street']); ?>
        </div>
    </div>

    <hr class="paysafe-separator">

    <p class="paysafe-add-saved-payment-method-title"><b><?php esc_html_e('Payment Card information', 'paysafe-checkout')?></b></p>

    <!-- Create divs for the payment fields -->
    <b><?php esc_html_e('Card Holder Name', 'paysafe-checkout')?></b><br>
    <div id="holderName" class="paysafe-input-field">
        <input type="text" class="paysafe-input-field-input" name="holderName_input" id="holderNameInput" value="" placeholder="<?php echo esc_html__('Card holder name', 'paysafe-checkout'); ?>" min="2" minlength="2" max="160" maxlength="160" />
    </div>
    <p></p>
    <b><?php esc_html_e('Card Number', 'paysafe-checkout')?></b><br>
    <div id="cardNumber" class="paysafe-input-field"></div>
    <p></p>
    <div class="paysafe-cc-form-exp-cvv-row">
        <div class="paysafe-cc-form-exp-cvv-box1">
            <b><?php esc_html_e('Expiry Date', 'paysafe-checkout')?></b><br>
            <div id="expiryDate" class="paysafe-input-field"></div>
        </div>
        <div class="paysafe-cc-form-exp-cvv-box2">
            <p id="expiryDate_spacer"></p>
            <b><?php esc_html_e('CVV', 'paysafe-checkout')?></b><br>
            <div id="cvv" class="paysafe-input-field"></div>
        </div>
        <div class="paysafe-cc-form-exp-cvv-box3">
            <img class="paysafe-cc-form-cvv-image" src="<?php echo esc_url(PAYSAFE_WOO_PLUGIN_URL . 'assets/img/cvv.svg'); ?>" alt="<?php echo esc_html(__('CVV', 'paysafe-checkout'));?>" />
        </div>
    </div>
    <p></p>

    <hr class="paysafe-separator">

    <p class="paysafe-address-notice"><?php
        echo sprintf(
            /* translators: %s is the `click here` link */
            esc_html(__('Please make sure your Billing Address details are correct before adding your payment method. If the above details are not correct, %s to correct them. ', 'paysafe-checkout')),
            '<a href="' . esc_url(wc_get_endpoint_url(get_option('woocommerce_myaccount_edit_address_endpoint'))) . '">'
            . esc_html(__('click here', 'paysafe-checkout')) . '</a>'
        );
    ?></p>
    <p class="paysafe-address-notice"><?php
        echo sprintf(
            /* translators: %s is the merchant name */
            esc_html(__('By providing your payment information, you allow %s to charge your card for future payments according to the merchant\'s Terms and conditions. ', 'paysafe-checkout')),
            esc_html(get_bloginfo('name'))
        );
    ?></p>
</div>
