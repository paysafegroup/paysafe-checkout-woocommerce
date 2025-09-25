<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
<?php foreach ($this->notices as $notice) { ?>
    <p style="background: none repeat scroll 0 0 #FFFFE0;
    border: 1px solid #E6DB55;
    margin: 0 0 20px;
    padding: 10px; font-weight: bold;">
        <?php echo esc_html($notice); ?>
    </p>
<?php } ?>

<section class="paysafe">
    <table class="form-table">
        <?php if (!WC_Gateway_Paysafe_Base::ALLOW_REACT_ADMIN): ?>
        <?php $this->generate_settings_html(); ?>
        <?php else: ?>
        <div class="wrap"><div id="paysafe-admin-root"></div></div>
        <?php endif ?>
    </table>
</section>
