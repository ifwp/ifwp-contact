<?php

 // ----------------------------------------------------------------------------------------------------

	class IFWP_Contact__hidden {

	 // ------------------------------------------------------------------------------------------------
	 //
	 // CONSTRUCT
	 //
	 // ------------------------------------------------------------------------------------------------

		public static function construct(){
			self::add_actions();
		}

	 // ------------------------------------------------------------------------------------------------
	 //
	 // ACTIONS
	 //
	 // ------------------------------------------------------------------------------------------------

		private static function add_actions(){
			add_action('wpcf7_init', array(__CLASS__, 'wpcf7_init__action'));
		}

	 // ------------------------------------------------------------------------------------------------

		public static function wpcf7_init__action(){
			IFWP_Contact::add_form_tag('ifwp_hidden', array(__CLASS__, 'wpcf7_shortcode_handler'), true);
		}

	 // ------------------------------------------------------------------------------------------------
	 //
	 // MISCELLANEOUS
	 //
	 // ------------------------------------------------------------------------------------------------

		public static function wpcf7_shortcode_handler($tag){
			$tag = IFWP_Contact::new_form_tag($tag);
			if(empty($tag->name)){
				return '';
			}
			$class = wpcf7_form_controls_class($tag->type);
			$atts = array();
			$atts['class'] = $tag->get_class_option($class);
			$atts['id'] = $tag->get_id_option();
			$value = (string) reset($tag->values);
			$value = $tag->get_default_option($value);
			$value = wpcf7_get_hangover($tag->name, $value);
			$atts['value'] = $value;
			$atts['type'] = 'hidden';
			$atts['name'] = $tag->name;
			$atts = wpcf7_format_atts($atts);
			$html = sprintf('<input %1$s />', $atts);
			return $html;
		}

	 // ------------------------------------------------------------------------------------------------

	}

 // ----------------------------------------------------------------------------------------------------

	IFWP_Contact__hidden::construct();

 // ----------------------------------------------------------------------------------------------------
