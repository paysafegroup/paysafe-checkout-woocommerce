<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
<div id="paysafe-hosted-payment-form" class="paysafe-hosted-payment-form">
    <!-- Create divs for the payment fields -->
    <b><?php echo esc_html(__('Card Number', 'paysafe-checkout'));?></b><br>
    <div id="cardNumber" class="paysafe-input-field"></div>
    <p></p>
    <b><?php echo esc_html(__('Expiry Date', 'paysafe-checkout'));?></b><br>
    <div id="expiryDate" class="paysafe-input-field"></div>
    <p></p>
    <b><?php echo esc_html(__('CVV', 'paysafe-checkout'));?></b><br>
    <div id="cvv" class="paysafe-input-field"></div>
    <p></p>
</div>
