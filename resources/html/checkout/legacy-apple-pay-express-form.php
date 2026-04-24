<fieldset style="text-align: center; font-family: Manrope, sans-serif;">
	<legend style="font-size: 1em;">Express Checkout</legend>
	<span style="font-size: 1.2em"><?php echo $is_google_pay_enabled ? __('Easy and secure payments with Paysafe', 'paysafe-checkout') : $this->description; ?></span>
	<hr />
    <span style="font-size: 1.5em"><?php echo esc_html(__('Paysafe Checkout is in TEST mode. Use the built-in simulator to test payments.', 'paysafe-checkout')); ?></span>
    <div id="paysafe-checkout-express-apple-pay-container" style="margin-top: 20px;"></div>
</fieldset>
