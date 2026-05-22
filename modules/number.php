<?php

 // ----------------------------------------------------------------------------------------------------

	class IFWP_Contact__number {

	 // ------------------------------------------------------------------------------------------------
	 //
	 // CONSTRUCT
	 //
	 // ------------------------------------------------------------------------------------------------

		public static function construct(){
			self::add_actions();
			self::add_filters();
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
			IFWP_Contact::add_form_tag(array('ifwp_number', 'ifwp_number*'), array(__CLASS__, 'wpcf7_shortcode_handler'), true);
		}

	 // ------------------------------------------------------------------------------------------------
	 //
	 // FILTERS
	 //
	 // ------------------------------------------------------------------------------------------------

		private static function add_filters(){
			add_filter('wpcf7_validate_ifwp_number', array(__CLASS__, 'wpcf7_validation__filter'), 10, 2);
			add_filter('wpcf7_validate_ifwp_number*', array(__CLASS__, 'wpcf7_validation__filter'), 10, 2);
		}

	 // ------------------------------------------------------------------------------------------------

		public static function wpcf7_validation__filter($result, $tag){
			$tag = IFWP_Contact::new_form_tag($tag);
			$name = $tag->name;
			$value = isset($_POST[$name]) ? trim(strtr((string) $_POST[$name], "\n", " ")) : '';
			$min = $tag->get_option('min', 'signed_int', true);
			$max = $tag->get_option('max', 'signed_int', true);
			if($tag->is_required() && '' == $value){
				$result->invalidate($tag, wpcf7_get_message('invalid_required'));
			} elseif('' != $value && !wpcf7_is_number($value)){
				$result->invalidate($tag, wpcf7_get_message('invalid_number'));
			} elseif('' != $value && '' != $min && (float) $value < (float) $min){
				$result->invalidate($tag, wpcf7_get_message('number_too_small'));
			} elseif('' != $value && '' != $max && (float) $max < (float) $value){
				$result->invalidate($tag, wpcf7_get_message('number_too_large'));
			}
			return $result;
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
			$validation_error = wpcf7_get_validation_error($tag->name);
			$class = wpcf7_form_controls_class($tag->type);
			$class .= ' wpcf7-validates-as-number';
			if($validation_error){
				$class .= ' wpcf7-not-valid';
			}
			$atts = array();
			$atts['class'] = $tag->get_class_option($class);
			$atts['id'] = $tag->get_id_option();
			$atts['tabindex'] = $tag->get_option('tabindex', 'int', true);
			$atts['min'] = $tag->get_option('min', 'signed_int', true);
			$atts['max'] = $tag->get_option('max', 'signed_int', true);
			$atts['step'] = $tag->get_option('step', 'int', true);
			if($tag->has_option('readonly')){
				$atts['readonly'] = 'readonly';
			}
			if($tag->is_required()){
				$atts['aria-required'] = 'true';
			}
			$atts['aria-invalid'] = $validation_error ? 'true' : 'false';
			$value = (string) reset($tag->values);
			if($tag->has_option('placeholder') || $tag->has_option('watermark')){
				$atts['placeholder'] = $value;
				$value = '';
			}
			$value = $tag->get_default_option($value);
			$value = wpcf7_get_hangover($tag->name, $value);
			$atts['value'] = $value;
			if(wpcf7_support_html5()){
				if(preg_match('/ip(ad|hone|od)/i', $_SERVER['HTTP_USER_AGENT'])){
					$atts['pattern'] = '[0-9]*';
					$atts['type'] = 'text';
				} else {
					$atts['type'] = 'number';
				}
			} else {
				$atts['type'] = 'text';
			}
			$atts['name'] = $tag->name;
			$atts = wpcf7_format_atts($atts);
			$html = sprintf('<span class="wpcf7-form-control-wrap %1$s"><input %2$s />%3$s</span>', sanitize_html_class($tag->name), $atts, $validation_error);
			return $html;
		}

	 // ------------------------------------------------------------------------------------------------

	}

 // ----------------------------------------------------------------------------------------------------

	IFWP_Contact__number::construct();

 // ----------------------------------------------------------------------------------------------------
