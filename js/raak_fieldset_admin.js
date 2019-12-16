jQuery(document).ready(function($) {
	
	fieldSettings["FieldsetBegin"] = ".label_setting, .css_class_setting, .conditional_logic_field_setting";
	fieldSettings["FieldsetEnd"] = ".css_class_setting";
	
	function fieldsetExist() {
		
		var fieldsetsCount = jQuery( '#gform_fields .gform_fieldset' ).length;
		
		return fieldsetsCount;
		
	}
	
	jQuery(document).bind('gform_field_deleted', function( event, form, field ){
	
		var fieldsetClosed = true;
		
		jQuery.each( form.fields, function( index, value ) {
			
			if ( typeof value.type != "undefined" ) {
				
				if ( value.type == 'FieldsetBegin' ) {
					
					fieldsetClosed = false;
					
				} else if ( value.type == 'FieldsetEnd' ) {
					
					console.log( value );
					
					if ( fieldsetClosed ) {
						
						deleteFieldset( value.id );
						
						return;
						
					}
					
					fieldsetClosed = true;
					
				}
				
			}
			
		});
	
	});
	
	jQuery(document).bind( 'gform_field_added', function( event, form, field ) {
		
		if ( field['type'] == 'FieldsetBegin' || field['type'] == 'FieldsetEnd' ) {
			
			var fieldsetClosed = true;
			var index = 1;
			
			jQuery.each( form.fields, function( index, value ) {
				
				if ( typeof value.type != "undefined" ) {
					
					if ( value.type == 'FieldsetBegin' ) {
						
						if ( fieldsetClosed ) {
							
							fieldsetClosed = false;
							
						} else {
							
							StartAddField( 'FieldsetEnd', index );
							
							fieldsetClosed = true;
							
							return;
							
						}
						
					} else if ( value.type == 'FieldsetEnd' ) {
						
						if ( fieldsetClosed ) {
						
							StartAddField( 'FieldsetBegin', index );
							
							return;
							
						} else {
							
							fieldsetClosed = true;
							
						}
						
					}
					
				}
				
				index++;
				
			});
			
			if ( !fieldsetClosed ) {
				
				StartAddField( 'FieldsetEnd' );
				
			}
			
		}
	
	} );
	
});