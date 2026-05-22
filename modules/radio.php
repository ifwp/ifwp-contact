<?php

 // ----------------------------------------------------------------------------------------------------

	class IFWP_Contact__radio {

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
			IFWP_Contact::add_form_tag(array('ifwp_radio', 'ifwp_radio*'), array(__CLASS__, 'wpcf7_shortcode_handler'), true);
		}

	 // ------------------------------------------------------------------------------------------------
	 //
	 // FILTERS
	 //
	 // ------------------------------------------------------------------------------------------------

		private static function add_filters(){
			add_filter('wpcf7_posted_data', array(__CLASS__, 'wpcf7_posted_data__filter'));
			add_filter('wpcf7_validate_ifwp_radio', array(__CLASS__, 'wpcf7_validation__filter'), 10, 2);
			add_filter('wpcf7_validate_ifwp_radio*', array(__CLASS__, 'wpcf7_validation__filter'), 10, 2);
		}

	 // ------------------------------------------------------------------------------------------------

		public static function wpcf7_posted_data__filter($posted_data){
			$tags = IFWP_Contact::scan_form_tags(array(
				'type' => array('radio', 'radio*')
			));
			if(empty($tags)){
				return $posted_data;
			}
			foreach($tags as $tag){
				$tag = IFWP_Contact::new_form_tag($tag);
				if(!isset($posted_data[$tag->name])){
					continue;
				}
				$posted_items = (array) $posted_data[$tag->name];
				if($tag->has_option('free_text')){
					if(WPCF7_USE_PIPE){
						$values = $tag->pipes->collect_afters();
					} else {
						$values = $tag->values;
					}
					$last = array_pop($values);
					$last = html_entity_decode($last, ENT_QUOTES, 'UTF-8');
					if(in_array($last, $posted_items)){
						$posted_items = array_diff($posted_items, array($last));
						$free_text_name = sprintf('_wpcf7_%1$s_free_text_%2$s', $tag->basetype, $tag->name);
						$free_text = $posted_data[$free_text_name];
						if(!empty($free_text)){
							$posted_items[] = trim($last . ' ' . $free_text);
						} else {
							$posted_items[] = $last;
						}
					}
				}
				$posted_data[$tag->name] = $posted_items;
			}
			return $posted_data;
		}

	 // ------------------------------------------------------------------------------------------------

		public static function wpcf7_validation__filter($result, $tag){
			$tag = IFWP_Contact::new_form_tag($tag);
			$name = $tag->name;
			$value = isset( $_POST[$name] ) ? (array) $_POST[$name] : array();
			if($tag->is_required() && empty($value)){
				$result->invalidate($tag, wpcf7_get_message('invalid_required'));
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
			$label_first = $tag->has_option('label_first');
			$use_label_element = $tag->has_option('use_label_element');
			$exclusive = $tag->has_option('exclusive');
			$free_text = $tag->has_option('free_text');
			$multiple = false;
			$exclusive = false;
			$atts = array();
			$atts['class'] = $tag->get_class_option($class);
			$atts['id'] = $tag->get_id_option();
			$atts['tabindex'] = $tag->get_option('tabindex', 'int', true);
			if(false !== $tabindex){
				$tabindex = absint($tabindex);
			}
			$html = '';
			$count = 0;
			$values = (array) $tag->values;
			$labels = (array) $tag->labels;
			if($data = (array) $tag->get_data_option()){
				if($free_text){
					$values = array_merge(array_slice($values, 0, -1), array_values($data), array_slice($values, -1));
					$labels = array_merge(array_slice($labels, 0, -1), array_values($data), array_slice($labels, -1));
				} else {
					$values = array_merge($values, array_values($data));
					$labels = array_merge($labels, array_values($data));
				}
			}
			$defaults = array();
			$default_choice = $tag->get_default_option(null, 'multiple=1');
			foreach($default_choice as $value){
				$key = array_search($value, $values, true);
				if(false !== $key){
					$defaults[] = (int) $key + 1;
				}
			}
			if($matches = $tag->get_first_match_option('/^default:([0-9_]+)$/')){
				$defaults = array_merge($defaults, explode('_', $matches[1]));
			}
			$defaults = array_unique($defaults);
			$hangover = wpcf7_get_hangover($tag->name, $multiple ? array() : '');
			foreach($values as $key => $value){
				$class = 'wpcf7-list-item';
				$checked = false;
				if($hangover){
					if($multiple){
						$checked = in_array(esc_sql($value), (array) $hangover);
					} else {
						$checked = ($hangover == esc_sql($value));
					}
				} else {
					$checked = in_array($key + 1, (array) $defaults);
				}
				if(isset($labels[$key])){
					$label = $labels[$key];
				} else {
					$label = $value;
				}
				$item_atts = array(
					'type' => $tag->basetype,
					'name' => $tag->name . ($multiple ? '[]' : ''),
					'value' => $value,
					'checked' => $checked ? 'checked' : '',
					'tabindex' => $tabindex ? $tabindex : ''
				);
				$item_atts = wpcf7_format_atts($item_atts);
				if($label_first){
					$item = sprintf('<span class="wpcf7-list-item-label">%1$s</span>&nbsp;<input %2$s />', esc_html($label), $item_atts);
				} else {
					$item = sprintf('<input %2$s />&nbsp;<span class="wpcf7-list-item-label">%1$s</span>', esc_html($label), $item_atts);
				}
				if($use_label_element){
					$item = '<label>' . $item . '</label>';
				}
				if(false !== $tabindex){
					$tabindex += 1;
				}
				$count += 1;
				if(1 == $count){
					$class .= ' first';
				}
				if(count($values) == $count){
					$class .= ' last';
					if($free_text){
						$free_text_name = sprintf('_wpcf7_%1$s_free_text_%2$s', $tag->basetype, $tag->name);
						$free_text_atts = array(
							'name' => $free_text_name,
							'class' => 'wpcf7-free-text',
							'tabindex' => $tabindex ? $tabindex : ''
						);
						if(wpcf7_is_posted() && isset($_POST[$free_text_name])){
							$free_text_atts['value'] = wp_unslash($_POST[$free_text_name]);
						}
						$free_text_atts = wpcf7_format_atts($free_text_atts);
						$item .= sprintf(' <input type="text" %s />', $free_text_atts);
						$class .= ' has-free-text';
					}
				}
				$item = '<span class="' . esc_attr($class) . '">' . $item . '</span>';
				$html .= $item;
			}
			$atts = wpcf7_format_atts($atts);
			$html = sprintf('<span class="wpcf7-form-control-wrap %1$s"><span %2$s>%3$s</span>%4$s</span>', sanitize_html_class($tag->name), $atts, $html, $validation_error);
			return $html;
		}

	 // ------------------------------------------------------------------------------------------------

	}

 // ----------------------------------------------------------------------------------------------------

	IFWP_Contact__radio::construct();

 // ----------------------------------------------------------------------------------------------------
