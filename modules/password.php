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
			add_action('wpcf7_admin_init', array(__CLASS__, 'wpcf7_admin_init__action'), 60);
			add_action('wpcf7_init', array(__CLASS__, 'wpcf7_init__action'));
		}

	 // ------------------------------------------------------------------------------------------------

		public static function wpcf7_admin_init__action(){
			$tag_generator = WPCF7_TagGenerator::get_instance();
			$tag_generator->add('ifwp_password', 'ifwp password', array(__CLASS__, 'wpcf7_tag_generator'));
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

		public static function wpcf7_tag_generator($contact_form, $args = ''){
			$args = wp_parse_args($args, array());
			$description = 'Generate a form-tag for a single-line password input field.'; ?>
			<div class="control-box">
				<fieldset>
					<legend><?php echo sprintf(esc_html($description)); ?></legend>
					<table class="form-table">
						<tbody>
                        	<tr>
                                <th scope="row"><?php
                                	echo esc_html(__('Field type', 'contact-form-7')); ?>
                                </th>
                                <td>
                                    <fieldset>
                                        <legend class="screen-reader-text"><?php
                                        	echo esc_html(__('Field type', 'contact-form-7')); ?>
                                        </legend>
                                        <label>
                                        	<input type="checkbox" name="required" /> <?php
                                        	echo esc_html(__('Required field', 'contact-form-7')); ?>
                                        </label>
                                    </fieldset>
                                </td>
                            </tr>
							<tr>
								<th scope="row">
                                    <label for="<?php echo esc_attr($args['content'] . '-name'); ?>"><?php
                                    	echo esc_html(__('Name', 'contact-form-7')); ?>
                                    </label>
                                </th>
								<td>
                                	<input type="text" name="name" class="tg-name oneline" id="<?php echo esc_attr($args['content'] . '-name'); ?>" />
                                </td>
							</tr>
							<tr>
								<th scope="row">
                                    <label for="<?php echo esc_attr($args['content'] . '-values'); ?>"><?php
                                    	echo esc_html(__('Default value', 'contact-form-7')); ?>
                                    </label>
                                </th>
								<td>
                                	<input type="text" name="values" class="oneline" id="<?php echo esc_attr($args['content'] . '-values'); ?>" />
                                </td>
							</tr>
							<tr>
								<th scope="row">
                                    <label for="<?php echo esc_attr($args['content'] . '-id'); ?>"><?php
                                    	echo esc_html(__('Id attribute', 'contact-form-7')); ?>
                                    </label>
                                </th>
								<td>
                                	<input type="text" name="id" class="idvalue oneline option" id="<?php echo esc_attr($args['content'] . '-id'); ?>" />
                                </td>
							</tr>
							<tr>
								<th scope="row">
                                    <label for="<?php echo esc_attr($args['content'] . '-class'); ?>"><?php
                                    	echo esc_html(__('Class attribute', 'contact-form-7')); ?>
                                    </label>
                                </th>
								<td>
                                	<input type="text" name="class" class="classvalue oneline option" id="<?php echo esc_attr($args['content'] . '-class'); ?>" />
                                </td>
							</tr>
						</tbody>
					</table>
				</fieldset>
			</div><?php
			IFWP_Contact::insert_box('ifwp_password', $args['content']);
		}

	 // ------------------------------------------------------------------------------------------------

	}

 // ----------------------------------------------------------------------------------------------------

	IFWP_Contact__password::construct();

 // ----------------------------------------------------------------------------------------------------

