<div id="paysafe-hosted-payment-form" class="paysafe-hosted-payment-form">
    <label id="vippreferred_consumerid_label" class="paysafe-general-form-label" for="vippreferred_consumeridInput">
        <?php echo esc_html(__('Your social security number', 'paysafe-checkout')); ?>
    </label>
    <div id="vippreferred_consumerid" class="paysafe-input-field">
        <input type="text" class="paysafe-input-field-input" name="vippreferred_consumerid_input"
               id="vippreferred_consumeridInput"
               placeholder="<?php echo esc_html(__('Your social security number', 'paysafe-checkout')); ?>"
               min="9" minLength="9" max="9" maxLength="9" />
    </div>
    <p id="vippreferred_consumerid_spacer" class="paysafe-general-form-spacer">
        <?php echo esc_html(__('Enter your social security number', 'paysafe-checkout')); ?>
    </p>
</div>
<div id="paysafe-hosted-payment-form" class="paysafe-hosted-payment-form">
    <label id="vippreferred_phonenumber_label" class="paysafe-general-form-label" for="vippreferred_phonenumberInput">
        <?php echo esc_html(__('Your phone number', 'paysafe-checkout')); ?>
    </label>
    <div id="vippreferred_phonenumber" class="paysafe-input-field">
        <input type="tel" class="paysafe-input-field-input" name="vippreferred_phonenumber_input"
               id="vippreferred_phonenumberInput"
               placeholder="<?php echo esc_html(__('Your phone number', 'paysafe-checkout')); ?>" />
    </div>
    <p id="vippreferred_consumerid_spacer" class="paysafe-general-form-spacer">
        <?php echo esc_html(__('Enter your phone number', 'paysafe-checkout')); ?>
    </p>
</div>
