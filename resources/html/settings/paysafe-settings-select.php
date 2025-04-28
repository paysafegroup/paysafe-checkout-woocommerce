<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
<tr valign="top">
    <th scope="row" class="titledesc" style="padding-top: 0px"></th>
    <td class="forminp" style="padding-top: 0px">
        <fieldset>
            <label for="<?php echo esc_attr( $field_key ); ?>" class="titledesc"><b><?php echo wp_kses_post( $data['title'] ); ?></b></label>
            <br />
            <legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
            <select class="select <?php echo esc_attr( $data['class'] ); ?>" name="<?php echo esc_attr( $field_key ); ?>" id="<?php echo esc_attr( $field_key ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" <?php disabled( $data['disabled'], true ); ?>>
                <?php foreach ( (array) $data['options'] as $option_key => $option_value ) : ?>
                    <?php if ( is_array( $option_value ) ) : ?>
                        <optgroup label="<?php echo esc_attr( $option_key ); ?>">
                            <?php foreach ( $option_value as $option_key_inner => $option_value_inner ) : ?>
                                <option value="<?php echo esc_attr( $option_key_inner ); ?>" <?php selected( (string) $option_key_inner, esc_attr( $value ) ); ?>><?php echo esc_html( $option_value_inner ); ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php else : ?>
                        <option value="<?php echo esc_attr( $option_key ); ?>" <?php selected( (string) $option_key, esc_attr( $value ) ); ?>><?php echo esc_html( $option_value ); ?></option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
            <?php echo wp_kses_post($this->get_description_html( $data )); // WPCS: XSS ok. ?>
        </fieldset>
    </td>
</tr>
