function deleteFieldset( fieldId ) {
	
	jQuery('#gform_fields li#field_' + fieldId).addClass('gform_pending_delete');
	
	var mysack = new sack("<?php echo admin_url( 'admin-ajax.php' )?>");
	
	mysack.execute = 1;
	mysack.method = 'POST';
	
	mysack.setVar("action", "rg_delete_field");
	mysack.setVar("rg_delete_field", "<?php echo wp_create_nonce( 'rg_delete_field' ) ?>");
	mysack.setVar("form_id", form.id);
	mysack.setVar("field_id", fieldId);
	
	mysack.onError = function () {
		alert(<?php echo json_encode( esc_html__( 'Ajax error while deleting field.', 'gravityforms' ) ); ?>)
	};
	
	mysack.runAJAX();
	
	return true;

}