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
			add_action('wpcf7_admin_init', array(__CLASS__, 'wpcf7_admin_init__action'), 60);
			add_action('wpcf7_init', array(__CLASS__, 'wpcf7_init__action'));
		}

	 // ------------------------------------------------------------------------------------------------

		public static function wpcf7_admin_init__action(){
			$tag_generator = WPCF7_TagGenerator::get_instance();
			$tag_generator->add('ifwp_hidden', 'ifwp hidden', array(__CLASS__, 'wpcf7_tag_generator'));
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

		public static function wpcf7_tag_generator($contact_form, $args = ''){
			$args = wp_parse_args($args, array());
			$description = 'Generate a form-tag for a single-line hidden input field.'; ?>
			<div class="control-box">
				<fieldset>
					<legend><?php echo sprintf(esc_html($description)); ?></legend>
					<table class="form-table">
						<tbody>
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
			IFWP_Contact::insert_box('ifwp_hidden', $args['content']);
		}

	 // ------------------------------------------------------------------------------------------------

	}

 // ----------------------------------------------------------------------------------------------------

	IFWP_Contact__hidden::construct();

 // ----------------------------------------------------------------------------------------------------

