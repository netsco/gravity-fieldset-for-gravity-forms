<?php
/*
Plugin Name: Gravity Fieldset for Gravity Forms Fork
Version: 0.3
Description: Extends the Gravity Forms plugin - adding a fieldset open and close field that can be used to create 'real' sections.
Author: Bas van den Wijngaard & Harro Heijboer forked by Ben Freeman
Text Domain: gravity-fieldset-for-gravity-forms
Domain Path: /languages
License: GPL2 v2
*/



/**
 * Load translations
 */

function raak_gf_fieldset_load_textdomain() {
	
	load_plugin_textdomain( 'gravity-fieldset-for-gravity-forms', FALSE, basename( dirname( __FILE__ ) ) . '/languages' );
	
}

add_action( 'plugins_loaded', 'raak_gf_fieldset_load_textdomain', 1 );

add_action( 'admin_notices', array('RAAK_GF_Fieldset', 'admin_warnings' ), 20 );



/**
 * RAAK_GF_Fieldset class.
 */

if (!class_exists('RAAK_GF_Fieldset')) :

	
	class RAAK_GF_Fieldset
	{
		
		private static $name = 'Fieldset for Gravity Forms';
		private static $slug = 'raak_gf_fieldset';
		private static $version = '0.2';
		
		
		/**
		 * Construct the plugin object
		 */
		
		public function __construct()
		{
			
			// register plugin functions through 'plugins_loaded' -
			// this delays the registration until all plugins have been loaded, ensuring it does not run before Gravity Forms is available.
			
			add_action( 'plugins_loaded', array( &$this, 'register_actions' ) );
			
		}
		
		
		/*
		 * Register plugin functions
		 */
		
		function register_actions()
		{
			
			// register actions
			
			if (self::is_gravityforms_installed()) :
				
				// start plug in
				
				// add buttons to the GF
				
				add_filter( 'gform_add_field_buttons', array( &$this, 'fieldset_add_field' ) );
				
				// add input field for field title
				
				add_filter( 'gform_field_type_title' , array( &$this, 'fieldset_title' ), 10, 2 );
				add_action( 'gform_editor_js', array( &$this, 'fieldset_custom_scripts' ) );
				add_action( 'gform_field_css_class', array( &$this, 'fieldset_custom_class' ), 10, 3 );
				add_filter( 'gform_field_content', array( &$this, 'fieldset_display_field' ), 10, 5 );
				
				
				// add filter for altering the fieldset container html
				add_filter( "gform_field_container", array( &$this, 'filter_gform_field_container'), 10, 6 );
				add_filter( 'gform_field_content', array( &$this, 'filter_gform_field_remove_label'), 10, 6 );
				
				// add filter for altering the complete form HTML
				add_filter( 'gform_get_form_filter', array( &$this, 'filter_gform_cleanup_html' ), 10, 2 );
				
			endif;
			
		}
		
		
		/**
		 * Create a new fields group in the Gravity Forms forms editor and add our fieldset 'fields' to it.
		 */
		
		public static function fieldset_add_field( $field_groups )
		{
			
			// add begin fieldset button
			
			$fieldset_begin_field = array(
				
				'class'		=> 'button',
				'value'		=> __( 'Fieldset Begin', 'gravity-fieldset-for-gravity-forms' ),
				'data-type'	=> 'FieldsetBegin',
				'onclick'	=> 'StartAddField( \'FieldsetBegin\' );'
				
			);
			
			// add end fieldset button
			
			$fieldset_end_field = array(
				
				'class'		=> 'button',
				'value'		=> __( 'Fieldset End', 'gravity-fieldset-for-gravity-forms' ),
				'data-type'	=> 'FieldsetEnd',
				'onclick'	=> 'StartAddField( \'FieldsetEnd\' );'
				
			);

			foreach ( $field_groups as &$group ) :
				
				$raak_fields_active = false;

				if ( $group["name"] === "raak_fields" ) :
					
					$raak_fields_active = true;
					
					$group["fields"][] = $fieldset_begin_field;
					$group["fields"][] = $fieldset_end_field;
					
				endif;

			endforeach;

			if ( !$raak_fields_active ) :
				
				$field_groups[] = array(
					
					'name'		=> 'raak_fields',
					'label'		=> __( 'Fieldsets', 'gravity-fieldset-for-gravity-forms' ),
					'fields'	=> array( $fieldset_begin_field, $fieldset_end_field )
					
				);
				
			endif;

			return $field_groups;
			
		}
		
		
		/**
		 * Add title to fieldset, displayed in Gravity Forms forms editor
		 */
		
		public static function fieldset_title( $title, $field_type )
		{
			
			if ( $field_type === "FieldsetBegin" ) :
				
				return __( 'Fieldset Begin', 'gravity-fieldset-for-gravity-forms' );
				
			elseif ( $field_type === "FieldsetEnd" ) :
			
				return __( 'Fieldset End', 'gravity-fieldset-for-gravity-forms' );
				
			else :
			
				return __( 'Unknown', 'gravity-fieldset-for-gravity-forms' );
				
			endif;
			
		}
		
		
		/**
		 * JavaSript to add field options to fieldset fields in the Gravity forms editor
		 */
		
		public static function fieldset_custom_scripts()
		{
			
			// add custom css
			
			wp_enqueue_style(
				
				'raak_fieldset_admin_style',
				plugins_url( '/css/raak_fieldset_admin.css', __FILE__ )
				
			);
			
			// add js
			?>
			
			<script type="text/javascript">
			
			<?php
			// include JS that do not require PHP parse
			
			include( plugin_dir_path( __FILE__ ) . '/js/raak_fieldset_admin.js' );
			
			// include JS that requires PHP parsing
			
			include( plugin_dir_path( __FILE__ ) . '/js/raak_fieldset_admin.php' );
			?>
			
			</script>
			
			<?php
			
		}
		
		
		/**
		 * Add custom classes to fieldset fields, controls CSS applied to field
		 */
		
		public static function fieldset_custom_class($classes, $field, $form)
		{
			
			if ( $field['type'] === 'FieldsetBegin' ) :
				
				$classes .= ' gform_fieldset_begin gform_fieldset';
				
			elseif ($field['type'] === 'FieldsetEnd') :
			
				$classes .= ' gform_fieldset_end gform_fieldset';
				
			endif;

			return $classes;
			
		}
		
		
		/**
		 * Displays fieldset
		 */
		
		public static function fieldset_display_field( $content, $field, $value, $lead_id, $form_id )
		{
			
			$custom_field_classes = $field->cssClass;
			
			if ( ( !is_admin() ) && ( $field['type'] == 'FieldsetBegin') ) :
				
				$content .= '<fieldset class="gfieldset gform_fieldset_begin gform_fieldset '.$custom_field_classes.'">';

				if ( isset( $field['label'] ) && trim( $field['label'] ) !== '' ) :
					
					$content .= '<legend class="gfieldset-legend">' . trim( $field['label'] ) . '</legend>';
					
				endif;

			elseif ( ( !is_admin() ) && ( $field['type'] == 'FieldsetEnd' ) ) :
				
				$content .= '</fieldset>';
				
			endif;

			return $content;
			
		}
		
		
		/*
		 * Warning message if Gravity Forms is installed and enabled
		 */
		
		public static function admin_warnings()
		{
			
			if ( !self::is_gravityforms_installed() ) :
				
				$message = __('requires Gravity Forms to be installed.', 'gravity-fieldset-for-gravity-forms');
				
			endif;

			if ( empty( $message ) ) return;
			?>
			
			<div class="error">
				
				<h3>Warning</h3>
				
				<p><?php _e('The plugin', 'gravity-fieldset-for-gravity-forms'); ?> <strong><?php echo self::$name; ?></strong> <?php echo $message; ?><br /><?php _e('Please', 'gravity-fieldset-for-gravity-forms'); ?> <a target="_blank" href="http://www.gravityforms.com/"><?php _e('download the latest version', 'gravity-fieldset-for-gravity-forms'); ?></a> <?php _e('of Gravity Forms and try again.', 'gravity-fieldset-for-gravity-forms'); ?></p>
				
			</div>
			
			<?php
		}
		
		
		/*
		 * Check if GF is installed
		 */
		
		private static function is_gravityforms_installed()
		{
			if ( !function_exists( 'is_plugin_active' ) || !function_exists( 'is_plugin_active_for_network' ) ) :
				
				require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
				
			endif;

			if (is_multisite()) :
				
				return (
					is_plugin_active_for_network( 'gravityforms/gravityforms.php' ) ||
					is_plugin_active( 'gravityforms/gravityforms.php' )
				);
				
			else :
				
				return is_plugin_active( 'gravityforms/gravityforms.php' );
				
			endif;
			
		}
		
		
		/*
		 * Alter container html when field type is fieldset
		 */
		
		public static function filter_gform_field_container( $field_container, $field, $form, $css_class, $style, $field_content )
		{

			$ul_classes = GFCommon::get_ul_classes($form);
			
			if ( ( !is_admin() ) && ( $field->type === 'FieldsetBegin' || $field->type === 'FieldsetEnd' ) ) :

				$field_container = '</ul>{FIELD_CONTENT}<ul class="'.$ul_classes.'">';
			
			endif;
			
			return $field_container;
		}

		
		/*
		 * Remove label tag when field type is fieldset
		 */
		
		public static function filter_gform_field_remove_label( $field_content, $field, $value, $lead_id, $form_id )
		{
			
			if ( ( !is_admin() ) && ( $field->type === 'FieldsetBegin' || $field->type === 'FieldsetEnd' ) ) :
			
				$field_content = preg_replace( '/<label[^>]*>([\s\S]*?)<\/label[^>]*>/', '', $field_content );
			
			endif;
			
			return $field_content;
			
		}
		
		
		/*
		 * Remove empty ul tag that is created when the fieldset close type is the last formfield.
		 */
		
		public static function filter_gform_cleanup_html( $form_string, $form )
		{
			
			if ( !is_admin() ) :
				
				$form_string = preg_replace( '#<(ul+)[^>]*>([[:space:]]|&nbsp;)*</ul>#', '', $form_string );
						
			endif;
			
			return $form_string;
			
		}
		
	}

	$RAAK_GF_Fieldset = new RAAK_GF_Fieldset();

endif;
