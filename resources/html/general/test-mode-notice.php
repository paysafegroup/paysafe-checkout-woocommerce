<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
<div style="text-align: justify; margin-bottom: 10px">
    <?php echo esc_html(__('Paysafe Checkout is in TEST MODE. Use the test Visa card 4000000000001091 with any expiry date, CVC, email, or OTP token. Important notice: Please use only TEST CARDS for testing. You can find other test cards ', 'paysafe-checkout')); ?>
    <a href="https://developer.paysafe.com/en/api-docs/cards/test-and-go-live/test-cards/" target="_blank">
        <b><?php echo esc_html(__('here. ', 'paysafe-checkout')); ?></b>
    </a>
</div>
