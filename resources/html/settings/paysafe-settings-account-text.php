<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
<tr valign="top">
    <th scope="row" class="titledesc" style="padding-top: 0px"></th>
    <td class="forminp" style="padding-top: 0px">
        <fieldset>
            <legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
            <label for="<?php echo esc_attr( $field_key ); ?>" class="titledesc"><b><?php echo wp_kses_post( $data['title'] ); ?></b>: <b><?php echo esc_html($data['account_id']); ?></b></label>
            <?php echo wp_kses_post($this->get_description_html( $data )); // WPCS: XSS ok. ?>
        </fieldset>
    </td>
</tr>
