<div id="paysafe-hosted-payment-form" class="paysafe-hosted-payment-form">
    <label id="paybybank_consumerid_label" class="paysafe-general-form-label" for="paybybank_consumeridInput">
        <?php echo esc_html(__('SSN', 'paysafe-checkout')); ?>
    </label>
    <div id="paybybank_consumerid" class="paysafe-input-field">
        <input type="text" class="paysafe-input-field-input" name="paybybank_consumerid_input"
               id="paybybank_consumeridInput"
               placeholder="<?php echo esc_html(__('Your SSN', 'paysafe-checkout')); ?>"
               min="2" minLength="2" />
    </div>
    <p id="paybybank_consumerid_spacer" class="paysafe-general-form-spacer">
        <?php echo esc_html(__('Enter the consumer id from your Pay By Bank account', 'paysafe-checkout')); ?>
    </p>
</div>
<div id="paysafe-hosted-payment-form2" class="paysafe-hosted-payment-form">
    <label id="paybybank_dob_label" class="paysafe-general-form-label" for="paybybank_dobInput">
        <?php echo esc_html(__('Date of birth', 'paysafe-checkout')); ?>
    </label>
    <div id="paybybank_dob" class="paysafe-input-field">
        <input type="date" class="paysafe-input-field-input" name="paybybank_dob_input" id="paybybank_dobInput"
               placeholder="<?php echo esc_html(__('Your Date of birth', 'paysafe-checkout')); ?>"
               min="2" minLength="2" />
    </div>
    <p id="paybybank_dob_spacer" class="paysafe-general-form-spacer">
        <?php echo esc_html(__('Enter your Date of Birth for your Pay By Bank account', 'paysafe-checkout')); ?>
    </p>
</div>
