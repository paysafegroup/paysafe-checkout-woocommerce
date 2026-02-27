<div id="paysafe-hosted-payment-form" class="paysafe-hosted-payment-form">
	<label id="paysafepaypal_consumerid_label" class="paysafe-general-form-label" for="paysafepaypal_consumeridInput">
		<?php echo esc_html(__('PayPal email address', 'paysafe-checkout')); ?>
	</label>
	<div id="paysafepaypal_consumerid" class="paysafe-input-field">
		<input type="text" class="paysafe-input-field-input" name="paysafepaypal_consumerid_input"
		       id="paysafepaypal_consumeridInput"
		       placeholder="<?php echo esc_html(__('Your PayPal email address', 'paysafe-checkout')); ?>"
		       min="2" minLength="2" max="50" maxLength="50"/>
	</div>
	<p id="paysafepaypal_consumerid_spacer" class="paysafe-general-form-spacer">
		<?php echo esc_html(__('Enter the email from your PayPal account', 'paysafe-checkout')); ?>
	</p>
</div>
