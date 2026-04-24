<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$is_subscription = $this->is_subscription_cart();
$terms_page = wc_terms_and_conditions_page_id();
$privacy_page = wc_privacy_policy_page_id();
?>
<div id="paysafe-hosted-payment-form" class="paysafe-hosted-payment-form">
    <!-- Create divs for the payment fields -->
    <label id="holderName_label" class="paysafe-cc-form-label" for="holderNameInput"><?php echo esc_html(__('Card Holder Name', 'paysafe-checkout'));?></label>
    <div id="holderName" class="paysafe-input-field">
        <input type="text" class="paysafe-input-field-input" name="holderName_input" id="holderNameInput" value="" placeholder="<?php echo esc_html__('Card holder name', 'paysafe-checkout'); ?>" min="2" minlength="2" max="160" maxlength="160" />
    </div>
    <p id="holderName_spacer"><?php echo esc_html__('Enter your name as it\'s written on the card', 'paysafe-checkout'); ?></p>
    <label id="cardNumber_label" class="paysafe-cc-form-label"><?php echo esc_html(__('Card Number', 'paysafe-checkout'));?></label>
    <div id="cardNumber" class="paysafe-input-field"></div>
    <p id="cardNumber_spacer"></p>
    <div class="paysafe-cc-form-exp-cvv-row">
        <div class="paysafe-cc-form-exp-cvv-box1">
            <label id="expiryDate_label" class="paysafe-cc-form-label"><?php echo esc_html(__('Expiry Date', 'paysafe-checkout'));?></label>
            <div id="expiryDate" class="paysafe-input-field"></div>
        </div>
        <div class="paysafe-cc-form-exp-cvv-box2">
            <p id="expiryDate_spacer" class="paysafe-cc-form-label"></p>
            <b><?php echo esc_html(__('CVV', 'paysafe-checkout'));?></b>
            <div id="cvv" class="paysafe-input-field"></div>
        </div>
        <div class="paysafe-cc-form-exp-cvv-box3">
            <img class="paysafe-cc-form-cvv-image" src="<?php echo esc_url(PAYSAFE_WOO_PLUGIN_URL . 'assets/img/cvv.svg'); ?>" alt="<?php echo esc_html(__('CVV', 'paysafe-checkout'));?>" />
        </div>
    </div>
    <p></p>

    <?php if ($this->is_save_token_enabled() && !is_checkout_pay_page()) { ?>
        <div id="save_card" class="wc-block-components-checkbox">
            <label for="paysafe_hosted_save_card">
                <input type="checkbox" class="wc-block-components-checkbox__input" name="paysafe_hosted_save_card" id="paysafe_hosted_save_card" <?php echo $is_subscription ? ' checked disabled' : ''; ?> />
                <svg class="wc-block-components-checkbox__mark" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 20"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"></path></svg>
                <span class="wc-block-components-checkbox__label"><?php echo esc_html(__('Save your payment details for future purchases', 'paysafe-checkout'));?></span>
            </label>
        </div>
        <?php if ($is_subscription) {?>
            <p style="font-size: 0.8em">
                <b><?php echo esc_html__('Note:', 'paysafe-checkout'); ?></b>
                <?php echo esc_html__('This order includes a subscription.', 'paysafe-checkout'); ?>
                <?php echo esc_html__('By proceeding with payment, your card details will be securely saved for future recurring payments.', 'paysafe-checkout'); ?>
                <br />
                <?php echo esc_html__('Card data is stored and processed securely by Paysafe in compliance with PCI DSS standards and according to the merchant’s', 'paysafe-checkout'); ?>
                <?php if ($terms_page) { ?>
                    <a href="<?php echo esc_url(get_permalink( $terms_page )); ?>"><?php echo esc_html__('Terms and Conditions', 'paysafe-checkout'); ?></a>
                <?php } else { ?>
	                <?php echo esc_html__('Terms and Conditions', 'paysafe-checkout'); ?>
                <?php } ?>
                <?php echo esc_html__('and', 'paysafe-checkout'); ?>
	            <?php if ($privacy_page) { ?>
                    <a href="<?php echo esc_url(get_permalink( $privacy_page )); ?>"><?php echo esc_html__('Privacy Policy.', 'paysafe-checkout'); ?></a>
	            <?php } else { ?>
		            <?php echo esc_html__('Privacy Policy.', 'paysafe-checkout'); ?>
	            <?php } ?>
            </p>
        <?php } ?>
        <p style="margin-bottom: 0;">
            <?php echo esc_html__('To manage your payment methods, go to My Account →', 'paysafe-checkout'); ?>
            <a href="<?php echo esc_url(wc_get_account_endpoint_url('payment-methods')); ?>"><?php echo esc_html__('Payment Methods', 'paysafe-checkout'); ?></a>
        </p>
	<?php } ?>
</div>
