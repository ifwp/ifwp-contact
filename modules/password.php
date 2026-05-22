<?php

 // ----------------------------------------------------------------------------------------------------

	class IFWP_Contact__password {

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
			IFWP_Contact::add_form_tag(array('ifwp_password', 'ifwp_password*'), array(__CLASS__, 'wpcf7_shortcode_handler'), true);
		}

	 // ------------------------------------------------------------------------------------------------
	 //
	 // FILTERS
	 //
	 // ------------------------------------------------------------------------------------------------

		private static function add_filters(){
			add_filter('wpcf7_validate_ifwp_password', array(__CLASS__, 'wpcf7_validation__filter'), 10, 2);
			add_filter('wpcf7_validate_ifwp_password*', array(__CLASS__, 'wpcf7_validation__filter'), 10, 2);
		}

	 // ------------------------------------------------------------------------------------------------

		public static function wpcf7_validation__filter($result, $tag){
			$tag = IFWP_Contact::new_form_tag($tag);
			$name = $tag->name;
			$value = isset($_POST[$name]) ? trim(wp_unslash(strtr((string) $_POST[$name], "\n", " "))) : '';
			if($tag->is_required() && '' == $value){
				$result->invalidate($tag, wpcf7_get_message('invalid_required'));
			}
			if(!empty($value)){
				$maxlength = $tag->get_maxlength_option();
				$minlength = $tag->get_minlength_option();
				if($maxlength && $minlength && $maxlength < $minlength){
					$maxlength = $minlength = null;
				}
				$code_units = wpcf7_count_code_units($value);
				if(false !== $code_units){
					if($maxlength && $maxlength < $code_units){
						$result->invalidate($tag, wpcf7_get_message('invalid_too_long'));
					} elseif($minlength && $code_units < $minlength){
						$result->invalidate($tag, wpcf7_get_message('invalid_too_short'));
					}
				}
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
			if($validation_error){
				$class .= ' wpcf7-not-valid';
			}
			$atts = array();
			$atts['size'] = $tag->get_size_option('40');
			$atts['maxlength'] = $tag->get_maxlength_option();
			$atts['minlength'] = $tag->get_minlength_option();
			if($atts['maxlength'] && $atts['minlength'] && $atts['maxlength'] < $atts['minlength']){
				unset($atts['maxlength'], $atts['minlength']);
			}
			$atts['class'] = $tag->get_class_option($class);
			$atts['id'] = $tag->get_id_option();
			$atts['tabindex'] = $tag->get_option('tabindex', 'int', true);
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
			$atts['type'] = 'password';
			$atts['name'] = $tag->name;
			$atts = wpcf7_format_atts($atts);
			$html = sprintf('<span class="wpcf7-form-control-wrap %1$s"><input %2$s />%3$s</span>', sanitize_html_class($tag->name), $atts, $validation_error);
			return $html;
		}

	 // ------------------------------------------------------------------------------------------------

	}

 // ----------------------------------------------------------------------------------------------------

	IFWP_Contact__password::construct();

 // ----------------------------------------------------------------------------------------------------
