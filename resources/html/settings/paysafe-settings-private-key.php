<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
<tr valign="top">
    <th scope="row" class="titledesc">
        <label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
    </th>
    <td class="forminp">
        <fieldset>
            <legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
            <input class="input-text regular-input <?php echo esc_attr( $data['class'] ); ?>" type="<?php echo esc_attr( $data['type'] ); ?>" name="<?php echo esc_attr( $field_key ); ?>" id="<?php echo esc_attr( $field_key ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" value="<?php echo esc_attr( $this->get_option( $key ) ); ?>" placeholder="<?php echo esc_attr( $data['placeholder'] ); ?>" <?php disabled( $data['disabled'], true ); ?> />
            <button type="button" class="button wp-hide-pw hide-if-no-js" data-toggle="0" onclick="toggle_admin_private_key_field(this, '<?php echo esc_attr( $field_key ); ?>')">
                <span class="dashicons dashicons-visibility" aria-hidden="true"></span>
            </button>
            <?php echo wp_kses_post($this->get_description_html( $data )); // WPCS: XSS ok. ?>
        </fieldset>
    </td>
</tr>
