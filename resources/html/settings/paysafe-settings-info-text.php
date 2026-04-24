<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
<tr valign="top">
    <th scope="row" class="titledesc" style="padding-top: 0px"><span><?php echo wp_kses_post( $data['title'] ); ?></span></th>
    <td class="forminp" style="padding-top: 0px">
        <fieldset>
            <legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
            <label for="<?php echo esc_attr( $field_key ); ?>" class="titledesc"><b><?php echo wp_kses_post($data['description']); ?></b></label>
        </fieldset>
    </td>
</tr>
