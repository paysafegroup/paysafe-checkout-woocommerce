window.addEventListener("load", function () {
    if(document.querySelector('button.woocommerce-save-button') != null) {
        document.querySelector('button.woocommerce-save-button').disabled = false;
    }
});
function toggle_admin_private_key_field(field_clicked, field_key) {
    let field_object = document.getElementById(field_key);
    if (field_object) {
        let is_text = field_object.getAttribute('type') === 'text';
        field_object.setAttribute('type', is_text ? 'password' : 'text');

        let span_dashicons = field_clicked.querySelectorAll('span.dashicons')[0];
        if (span_dashicons) {
            span_dashicons.className = is_text ?
                'dashicons dashicons-visibility'
                : 'dashicons dashicons-hidden';
        }
    }
}
