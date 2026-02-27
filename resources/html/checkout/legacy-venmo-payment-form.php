<div id="paysafe-hosted-payment-form" class="paysafe-hosted-payment-form">
    <label id="paysafevenmo_consumerid_label" class="paysafe-general-form-label" for="paysafevenmo_consumeridInput">
        <?php echo esc_html(__('Venmo email address', 'paysafe-checkout')); ?>
    </label>
    <div id="paysafevenmo_consumerid" class="paysafe-input-field">
        <input type="text" class="paysafe-input-field-input" name="paysafevenmo_consumerid_input"
               id="paysafevenmo_consumeridInput"
               placeholder="<?php echo esc_html(__('Your Venmo email address', 'paysafe-checkout')); ?>"
               min="2" minLength="2" max="50" maxLength="50"/>
    </div>
    <p id="paysafevenmo_consumerid_spacer" class="paysafe-general-form-spacer">
        <?php echo esc_html(__('Enter the email from your Venmo account', 'paysafe-checkout')); ?>
    </p>
</div>
