<div id="paysafe-hosted-payment-form" class="paysafe-hosted-payment-form">
    <label id="sightline_consumerid_label" class="paysafe-general-form-label" for="sightline_consumeridInput">
        <?php echo esc_html(__('Play+ (Sightline) Loyalty Membership Number', 'paysafe-checkout')); ?>
    </label>
    <div id="sightline_consumerid" class="paysafe-input-field">
        <input type="text" class="paysafe-input-field-input" name="sightline_consumerid_input"
               id="sightline_consumeridInput"
               placeholder="<?php echo esc_html(__('Your Play+ (Sightline) Loyalty Membership Number', 'paysafe-checkout')); ?>"
               min="2" minLength="2" max="50" maxLength="50"/>
    </div>
    <p id="sightline_consumerid_spacer" class="paysafe-general-form-spacer">
        <?php echo esc_html(__('Enter the Loyalty Membership Number from your Play+ (Sightline) account', 'paysafe-checkout')); ?>
    </p>
</div>
