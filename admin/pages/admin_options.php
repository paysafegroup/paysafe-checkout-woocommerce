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
        <?php $this->generate_settings_html(); ?>
    </table>
</section>
