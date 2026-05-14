<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
<fieldset style="text-align: center; font-family: Manrope, sans-serif;">
	<legend style="font-size: 1em;"><?php echo esc_html__("Express Checkout", "paysafe-checkout"); ?></legend>
	<span style="font-size: 1.2em"><?php echo esc_html($this->description); ?></span>
	<hr />
    <span style="font-size: 1.5em"><?php echo esc_html(__('Paysafe Checkout is in TEST mode. Use the built-in simulator to test payments.', 'paysafe-checkout')); ?></span>
    <div id="paysafe-checkout-express-google-pay-container" style="margin-top: 20px;"></div>
</fieldset>
