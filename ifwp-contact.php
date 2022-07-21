<?php
/**
 * Author: Luis del Cid
 * Author URI: http://luisdelcid.com
 * Description: Improvements and Fixes for Contact Form 7.
 * Domain Path:
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Network:
 * Plugin Name: IFWP Contact
 * Plugin URI: https://github.com/ifwp/ifwp-contact
 * Text Domain: ifwp-contact
 * Version: 5.7.21
 */
 // ----------------------------------------------------------------------------------------------------

	defined('ABSPATH') or die('No script kiddies please!');

 // ----------------------------------------------------------------------------------------------------

	class IFWP_Contact {

	 // ------------------------------------------------------------------------------------------------
	 //
	 // CONSTRUCT
	 //
	 // ------------------------------------------------------------------------------------------------

		public static function construct(){
			self::add_actions();
			self::add_filters();
			self::add_modules();
			self::add_shortcodes();
			self::check_for_updates();
		}

	 // ------------------------------------------------------------------------------------------------
	 //
	 // PROPERTIES
	 //
	 // ------------------------------------------------------------------------------------------------

		private static $data_options = array();

	 // ------------------------------------------------------------------------------------------------
	 //
	 // ACTIONS
	 //
	 // ------------------------------------------------------------------------------------------------

		private static function add_actions(){
			register_activation_hook(__FILE__, array(__CLASS__, 'activation__action'));
			add_action('admin_init', array(__CLASS__, 'admin_init__action'));
			add_action('admin_menu', array(__CLASS__, 'admin_menu__action'));
			add_action('admin_notices', array(__CLASS__, 'admin_notices__action'));
			add_action('generate_rewrite_rules', array(__CLASS__, 'generate_rewrite_rules__action'));
			add_action('init', array(__CLASS__, 'init__action'));
			add_action('plugins_loaded', array(__CLASS__, 'plugins_loaded__action'));
			add_action('wpcf7_before_send_mail', array(__CLASS__, 'wpcf7_before_send_mail__action'));
			add_action('wp_login', array(__CLASS__, 'wp_login__action'));
			add_action('wp_logout', array(__CLASS__, 'wp_logout__action'));
			add_action('wpcf7_enqueue_scripts', array(__CLASS__, 'wpcf7_enqueue_scripts__action'));
			add_action('wpcf7_enqueue_styles', array(__CLASS__, 'wpcf7_enqueue_styles__action'));
			add_action('wpcf7_mail_failed', array(__CLASS__, 'wpcf7_mail_failed__action'));
			add_action('wpcf7_mail_sent', array(__CLASS__, 'wpcf7_mail_sent__action'));
		}

	 // ------------------------------------------------------------------------------------------------

		public static function activation__action(){
			self::register_post_types();
			flush_rewrite_rules();
		}

	 // ------------------------------------------------------------------------------------------------

		public static function admin_init__action(){
			if(!is_plugin_active('contact-form-7/wp-contact-form-7.php')){
				deactivate_plugins(plugin_basename(__FILE__));
			}
		}

	 // ------------------------------------------------------------------------------------------------

		public static function admin_menu__action(){
			if(!isset($GLOBALS['admin_page_hooks']['ifwp'])){
				add_menu_page('Improvements and Fixes for WordPress', 'IFWP', 'manage_options', 'ifwp', array(__CLASS__, 'menu_page'), 'dashicons-editor-code');
			}
		}

	 // ------------------------------------------------------------------------------------------------

		public static function admin_notices__action(){
			if(!is_plugin_active('contact-form-7/wp-contact-form-7.php')){
				printf('<div class="error"><p>' . esc_html('%1$s requires %2$s. Please install and/or activate %2$s before activating %1$s. For now, %1$s has been deactivated.') . '</p></div>', '<strong>IFWP Contact</strong>', '<strong>Contact Form 7</strong>');
			}
		}

	 // ------------------------------------------------------------------------------------------------

		public static function generate_rewrite_rules__action($wp_rewrite){
			global $pagenow;
			if(!is_multisite() and !(is_admin() and $pagenow == 'plugins.php' and isset($_GET['action'], $_GET['plugin']) and $_GET['action'] == 'deactivate' and $_GET['plugin'] == plugin_basename(__FILE__))){
				$wp_upload_dir = wp_upload_dir();
				if(wp_mkdir_p($wp_upload_dir['basedir'] . '/ifwp-contact')){
					$regex = str_replace(site_url('/'), '', $wp_upload_dir['baseurl']) . '/ifwp-contact/(.+)';
					$query = str_replace(site_url('/'), '', plugin_dir_url(__FILE__) . 'readfile.php') . '?ifwp_file=$1';
					$wp_rewrite->add_external_rule($regex, $query);
				}
			}
		}

	 // ------------------------------------------------------------------------------------------------

		public static function init__action(){
			self::ifwp_session_start();
			$_SESSION['ifwp_current_user_id'] = get_current_user_id();
			self::register_post_types();
			if(!is_admin()){
				require_once(ABSPATH . 'wp-admin/includes/image.php');
				require_once(ABSPATH . 'wp-admin/includes/file.php');
				require_once(ABSPATH . 'wp-admin/includes/media.php');
			}
		}

	 // ------------------------------------------------------------------------------------------------

	 	public static function plugins_loaded__action(){
			remove_shortcode('contact-form-7');
			remove_shortcode('contact-form');
			add_shortcode('contact-form-7', array(__CLASS__, 'wpcf7_contact_form_tag_func'));
			add_shortcode('contact-form', array(__CLASS__, 'wpcf7_contact_form_tag_func'));
		}

	 // ------------------------------------------------------------------------------------------------

	 	public static function wpcf7_before_send_mail__action($contact_form){
	 		global $shortcode_tags;
			$contact_form = WPCF7_ContactForm::get_current();
			if($contact_form){
				$tmp = $shortcode_tags;
				remove_all_shortcodes();
				self::add_shortcodes();
				$mail = $contact_form->prop('mail');
				if(has_shortcode($mail['body'], 'ifwp_if')){
					$mail['body'] = do_shortcode($mail['body']);
				}
				$mail_2 = $contact_form->prop('mail_2');
				if(has_shortcode($mail_2['body'], 'ifwp_if')){
					$mail_2['body'] = do_shortcode($mail_2['body']);
				}
				$contact_form->set_properties(array(
					'mail' => $mail,
					'mail_2' => $mail_2
				));
				$shortcode_tags = $tmp;
			}
		}

	 // ------------------------------------------------------------------------------------------------

		public static function wp_login__action(){
			self::ifwp_session_destroy();
		}

	 // ------------------------------------------------------------------------------------------------

		public static function wp_logout__action(){
			self::ifwp_session_destroy();
		}

	 // ------------------------------------------------------------------------------------------------

		public static function wpcf7_enqueue_styles__action(){
			ob_start(); ?>
            div.wpcf7-mail-sent-ng {
                color: #a94442;
                background-color: #f2dede;
                border-color: #ebccd1;
            }
            div.wpcf7-mail-sent-ok {
                color: #3c763d;
                background-color: #dff0d8;
                border-color: #d6e9c6;
            }
            div.wpcf7-response-output {
                padding: 15px;
                margin-bottom: 20px;
                border: 1px solid transparent;
                border-radius: 4px;
            }
            div.wpcf7-spam-blocked {
                color: #a94442;
                background-color: #f2dede;
                border-color: #ebccd1;
            }
            div.wpcf7-validation-errors {
                color: #8a6d3b;
                background-color: #fcf8e3;
                border-color: #faebcc;
            }
            input.wpcf7-submit.disabled {
                -webkit-box-shadow: none;
                box-shadow: none;
                cursor: not-allowed;
                filter: alpha(opacity=65);
                opacity: .65;
                pointer-events: none;
            }<?php
            $data = ob_get_clean();
            wp_add_inline_style('contact-form-7', $data);
		}

	 // ------------------------------------------------------------------------------------------------

		public static function wpcf7_enqueue_scripts__action(){
			ob_start(); ?>
            var ifwp_contact = {
                on_sent_ok: function(into, url){
                	'use strict';
                    var a = document.createElement('a'),
                        href = '';
                    if(url == ''){
                        a.href = jQuery(location).attr('href');
                    } else {
                        a.href = url;
                    }
                    if(a.protocol){
                        href += a.protocol + '//';
                    }
                    if(a.hostname){
                        href += a.hostname;
                    }
                    if(a.port){
                        href += ':' + a.port;
                    }
                    if(a.pathname){
                        if(a.pathname[0] !== '/'){
                            href += '/';
                        }
                        href += a.pathname;
                    }
                    if(a.search){
                    	var i = 0;
                        var search = [];
                        var search_object = {};
                        var search_array = a.search.replace('?', '').split('&');
                        for(i = 0; i < search_array.length; i ++){
                            search_object[search_array[i].split('=')[0]] = search_array[i].split('=')[1];
                        }
                        jQuery.each(search_object, function(key, value){
                            if(key != 'ifwp_referer'){
                                search.push(key + '=' + value);
                            }
                        });
                        if(search.length > 0){
                            href += '?' + search.join('&') + '&';
                        } else {
                            href += '?';
                        }
                    } else {
                        href += '?';
                    }
                    href += 'ifwp_referer=' + jQuery('div' + into).find('input[name="_ifwp_uniqid"]').val();
                    if(a.hash){
                        href += a.hash;
                    }
                    jQuery(location).attr('href', href);
                },
                on_submit: function(){
                    jQuery('.wpcf7-submit').removeClass('disabled');
                }
            };
            (function($){
                'use strict';
                $(function(){
                    $('.wpcf7-form').on('keydown', 'input[type!="textarea"]', function(e){
                        if(e.keyCode === 13) {
                            e.preventDefault();
                        } else {
                            if($(this).is('input[type="submit"]') && e.keyCode === 32){
                                e.preventDefault();
                            }
                        }
                    });
                    $('.wpcf7-submit').on('click', function(){
                        $('.wpcf7-submit').addClass('disabled');
                    });
                });<?php
                if(version_compare(WPCF7_VERSION, '4.7', '>=')){ ?>
                    $('.wpcf7').on('wpcf7submit', ifwp_contact.on_submit);
                    ifwp_contact.wpcf7mailsent = [];<?php
                    $posts = get_posts(array(
                        'post_type' => 'wpcf7_contact_form',
                        'posts_per_page' => -1
                    ));
                    if($posts){
                        foreach($posts as $post){
                            $contact_form = wpcf7_contact_form($post->ID);
                            if($contact_form->is_true('ifwp_redirect')){ ?>
                                ifwp_contact.wpcf7mailsent[<?php echo $post->ID; ?>] = '';<?php
                            } else {
                                $setting = $contact_form->additional_setting('ifwp_redirect');
                                if($setting){
																	$url = filter_var(wpcf7_strip_quote($setting[0]), FILTER_SANITIZE_URL);
																	if($url){ ?>
	                                    ifwp_contact.wpcf7mailsent[<?php echo $post->ID; ?>] = '<?php echo $url; ?>';<?php
																	}
                                }
                            }
                        }
                    } ?>
										$('.wpcf7').on('wpcf7mailsent', function(e){
											var detail = null;
											if(typeof(e.originalEvent) !== 'undefined'){
												if(typeof(e.originalEvent.detail) !== 'undefined'){
													detail = e.originalEvent.detail;
												}
											} else if(typeof(e.detail) !== 'undefined'){
												detail = e.detail;
											}
											if(detail !== null){
												var wpcf7 = 0;
												var wpcf7_unit_tag = '';
												if(typeof(detail.contactFormId) !== 'undefined'){
													wpcf7 = detail.contactFormId;
												}
												if(typeof(detail.unitTag) !== 'undefined'){
													wpcf7_unit_tag = '#' + detail.unitTag;
												}
												if(wpcf7 === 0 || wpcf7_unit_tag === ''){
													if(typeof(detail.inputs) !== 'undefined'){
														var inputs = detail.inputs;
														for(var i = 0; i < inputs.length; i++){
															if('_wpcf7' == inputs[i].name){
																wpcf7 = inputs[i].value;
															}
															if('_wpcf7_unit_tag' == inputs[i].name){
																wpcf7_unit_tag = '#' + inputs[i].value;
															}
														}
													}
												}
												if(wpcf7 !== 0 && wpcf7_unit_tag !== ''){
													if(typeof ifwp_contact.wpcf7mailsent[wpcf7] !== 'undefined'){
														ifwp_contact.on_sent_ok(wpcf7_unit_tag, ifwp_contact.wpcf7mailsent[wpcf7]);
													}
												}
											}
										});<?php
                }
                if(version_compare(WPCF7_VERSION, '4.8', '>=')){ ?>
                    wpcf7.notValidTip = function(target, message){
                        var $target = $(target);
                        $('.wpcf7-not-valid-tip', $target).remove();
                        $('<span role="alert" class="wpcf7-not-valid-tip"></span>').html(message).appendTo($target);
                        if($target.is('.use-floating-validation-tip *')){
                            var fadeOut = function(target){
                                $(target).not(':hidden').animate({
                                    opacity: 0
                                }, 'fast', function(){
                                    $(this).css({
                                        'z-index': -100
                                    });
                                });
                            };
                            $target.on('mouseover', '.wpcf7-not-valid-tip', function(){
                                fadeOut(this);
                            });
                            $target.on('focus', ':input', function(){
                                fadeOut($('.wpcf7-not-valid-tip', $target));
                            });
                        }
                    };<?php
                } ?>
            })(jQuery);<?php
            $data = ob_get_clean();
            wp_add_inline_script('contact-form-7', $data);
		}

	 // ------------------------------------------------------------------------------------------------

		public static function wpcf7_mail_failed__action($contact_form){
			self::wpcf7_mail($contact_form, 'failed');
		}

	 // ------------------------------------------------------------------------------------------------

		public static function wpcf7_mail_sent__action($contact_form){
			self::wpcf7_mail($contact_form, 'sent');
		}

	 // ------------------------------------------------------------------------------------------------
	 //
	 // FILTERS
	 //
	 // ------------------------------------------------------------------------------------------------

		private static function add_filters(){
			add_filter('robots_txt', array(__CLASS__, 'robots_txt__filter'), 10, 2);
			add_filter('wp_mail', array(__CLASS__, 'wp_mail__filter'));
			add_filter('wpcf7_ajax_json_echo', array(__CLASS__, 'wpcf7_ajax_json_echo__filter'));
			add_filter('wpcf7_form_elements', array(__CLASS__, 'wpcf7_form_elements__filter'));
			add_filter('wpcf7_form_elements', 'do_shortcode', 20);
			add_filter('wpcf7_form_hidden_fields', array(__CLASS__, 'wpcf7_form_hidden_fields__filter'));
			add_filter('wpcf7_form_tag_data_option', array(__CLASS__, 'wpcf7_form_tag_data_option__filter'), 10, 2);
			add_filter('wpcf7_special_mail_tags', array(__CLASS__, 'wpcf7_special_mail_tags__filter'), 10, 3);
			add_filter('wpcf7_verify_nonce', array(__CLASS__, 'wpcf7_verify_nonce__filter'));
		}

	 // ------------------------------------------------------------------------------------------------

		public static function robots_txt__filter($output, $public){
	 		if($public != '0'){
				$wp_upload_dir = wp_upload_dir();
				if(wp_mkdir_p($wp_upload_dir['basedir'] . '/ifwp-contact')){
					$site_url = parse_url(site_url());
					$path = (isset($site_url['path']) ? $site_url['path'] : '');
					$path .= str_replace(site_url('/'), '/', $wp_upload_dir['baseurl']) . '/ifwp-contact/';
					$output .= "Disallow: $path\n";
				}
			}
			return $output;
		}

	 // ------------------------------------------------------------------------------------------------

		public static function wp_mail__filter($args){
			$to = $args['to'];
			if(!is_array($to)){
				$to = explode(',', $to);
			}
			$recipients = array();
			foreach((array) $to as $recipient){
				if(preg_match('/(.*)<(.+)>/', $recipient, $matches)){
					if(count($matches) == 3){
						$recipient = $matches[2];
					}
				}
				if($recipient = filter_var($recipient, FILTER_VALIDATE_EMAIL)){
					$recipients[] = $recipient;
				}
			}
			if(!$recipients){
				$args['attachments'] = array();
				$args['headers'] = '';
				$args['message'] = '';
				$args['subject'] = '';
				$args['to'] = '';
			}
			return $args;
		}

	 // ------------------------------------------------------------------------------------------------

		public static function wpcf7_ajax_json_echo__filter($items){
			if(version_compare(WPCF7_VERSION, '4.7') < 0){
				isset($items['onSubmit']) or $items['onSubmit'] = array();
				$items['onSubmit'][] = "ifwp_contact.on_submit();";
				$contact_form = wpcf7_contact_form($_POST['_wpcf7']);
				$setting = $contact_form->additional_setting('ifwp_redirect');
				if($setting){
					isset($items['onSentOk']) or $items['onSentOk'] = array();
					$items['onSentOk'][] = "ifwp_contact.on_sent_ok('" . $items['into'] .  "', '" . (in_array($setting[0], array('on', 'true', '1')) ? '' : filter_var(wpcf7_strip_quote($setting[0]), FILTER_SANITIZE_URL)) .  "');";
				}
			}
			return $items;
		}

	 // ------------------------------------------------------------------------------------------------

		public static function wpcf7_form_elements__filter($form){
			if(version_compare(WPCF7_VERSION, '4.6', '>=')){
				$manager = WPCF7_FormTagsManager::get_instance();
			} else {
				$manager = WPCF7_ShortcodeManager::get_instance();
			}
			$contact_form = WPCF7_ContactForm::get_current();
			$form = $contact_form->prop('form');
			if($contact_form->is_true('ifwp_autop')){
				if(version_compare(WPCF7_VERSION, '4.6', '>=')){
					$form = $manager->normalize($form);
				} else {
					$form = $manager->normalize_shortcode($form);
				}
				$form = wpcf7_autop($form);
			}
			if(version_compare(WPCF7_VERSION, '4.6', '>=')){
				$form = $manager->replace_all($form);
			} else {
				$form = $manager->do_shortcode($form);
			}
			return $form;
		}

	 // ------------------------------------------------------------------------------------------------

		public static function wpcf7_form_hidden_fields__filter($hidden_fields){
			$contact_form = WPCF7_ContactForm::get_current();
			if(!$contact_form){
				return $hidden_fields;
			}
			if(isset($_GET['ifwp_referer'])){
				$hidden_fields['_ifwp_referer'] = wpcf7_sanitize_query_var($_GET['ifwp_referer']);
			}
			$hidden_fields['_ifwp_uniqid'] = uniqid();
			if(isset($_REQUEST['ifwp_update'])){
				$post = self::get_post_to_update(wpcf7_sanitize_query_var($_REQUEST['ifwp_update']), $hidden_fields['_wpcf7']);
				if($post){
					if(isset($hidden_fields['_ifwp_referer'])){
						unset($hidden_fields['_ifwp_referer']);
					}
					$hidden_fields['_ifwp_uniqid'] = get_post_meta($post->ID, 'ifwp_uniqid', true);
					$hidden_fields['_ifwp_update'] = $post->ID;
				}
			}
			return $hidden_fields;
		}

	 // ------------------------------------------------------------------------------------------------

		public static function wpcf7_form_tag_data_option__filter($data, $options){
			foreach($options as $option){
				$option = explode('.', $option);
				if($option[0] == 'ifwp'){
					if(isset($option[1], self::$data_options[$option[1]])){
						is_array($data) or $data = array();
						$data = array_merge($data, array_values(self::$data_options[$option[1]]));
					}
				}
			}
			return $data;
		}

	 // ------------------------------------------------------------------------------------------------

		public static function wpcf7_special_mail_tags__filter($output, $name, $html){
			$name = preg_replace('/^wpcf7\./', '_', $name);
			$submission = WPCF7_Submission::get_instance();
			if(!$submission){
				return $output;
			}
			if(preg_match('/^ifwp_referred_(.+)$/', $name, $matches)){
				$tagname = trim($matches[1]);
				$referer = $submission->get_posted_data('_ifwp_referer');
				if($referer){
					$post = self::get_post($referer);
					if($post){
						$single_post_meta = array_map('array_shift', get_post_meta($post->ID));
						if(isset($single_post_meta[$tagname])){
							$submitted = maybe_unserialize($single_post_meta[$tagname]);
							$replaced = $submitted;
							$replaced = wpcf7_flat_join($replaced);
							if($html){
								$replaced = esc_html($replaced);
								$replaced = wptexturize($replaced);
							}
							$replaced = apply_filters('wpcf7_mail_tag_replaced', $replaced, $submitted, $html);
							$replaced = wp_unslash(trim($replaced));
							return $html ? esc_html($replaced) : $replaced;
						}
					}
				}
			}
			return $output;
		}

	 // ------------------------------------------------------------------------------------------------

		public static function wpcf7_verify_nonce__filter($is_active){
			if(is_user_logged_in()){
				$is_active = true;
			}
			return $is_active;
		}

	 // ------------------------------------------------------------------------------------------------
	 //
	 // MODULES
	 //
	 // ------------------------------------------------------------------------------------------------

		private static function add_modules(){
			require_once(plugin_dir_path( __FILE__ ) . 'modules/hidden.php');
			require_once(plugin_dir_path( __FILE__ ) . 'modules/number.php');
			require_once(plugin_dir_path( __FILE__ ) . 'modules/password.php');
			require_once(plugin_dir_path( __FILE__ ) . 'modules/radio.php');
		}

	 // ------------------------------------------------------------------------------------------------
	 //
	 // SHORTCODES
	 //
	 // ------------------------------------------------------------------------------------------------

		private static function add_shortcodes(){
			add_shortcode('ifwp_if', array(__CLASS__, 'if__shortcode'));
			add_shortcode('ifwp_url', array(__CLASS__, 'url__shortcode'));
		}

	 // ------------------------------------------------------------------------------------------------

	 	public static function if__shortcode($atts, $content = ''){
			$return = '';
			$submission = WPCF7_Submission::get_instance();
			if($submission){
				$posted_data = $submission->get_posted_data();
				if($posted_data){
					$atts = shortcode_atts(array(
						'compare' => '=',
						'key' => '',
						'type' => 'CHAR',
						'value' => ''
					), $atts, 'ifwp_if');
					extract($atts);
					if(in_array($type, array('NUMERIC', 'CHAR', 'DATE', 'DATETIME', 'DECIMAL', 'TIME'))){
						switch($type){
							case 'NUMERIC':
								$value = intval($value);
								if(isset($posted_data[$key])){
									$posted_data[$key] = intval($posted_data[$key]);
								}
								break;
							case 'DATE':
								$time = date('H:i:s');
								$value = strtotime(date('Y-m-d', strtotime($value)) . ' ' . $time);
								if(isset($posted_data[$key])){
									$posted_data[$key] = strtotime(date('Y-m-d', strtotime($posted_data[$key])) . ' ' . $time);
								}
								break;
							case 'DATETIME':
								$value = strtotime($value);
								if(isset($posted_data[$key])){
									$posted_data[$key] = strtotime($posted_data[$key]);
								}
								break;
							case 'DECIMAL':
								$value = floatval($value);
								if(isset($posted_data[$key])){
									$posted_data[$key] = floatval($posted_data[$key]);
								}
								break;
							case 'TIME':
								$date = date('Y-m-d');
								$value = strtotime($date . ' ' . date('H:i:s', strtotime($value)));
								if(isset($posted_data[$key])){
									$posted_data[$key] = strtotime($date . ' ' . date('H:i:s', strtotime($posted_data[$key])));
								}
								break;
						}
						if(in_array($compare, array('=', '!=', '>', '>=', '<', '<=', 'LIKE', 'NOT LIKE', 'EXISTS', 'NOT EXISTS'))){
							switch($compare){
								case '=':
									if(isset($posted_data[$key]) and $posted_data[$key] == $value){
										$return = $content;
									}
									break;
								case '!=':
									if(isset($posted_data[$key]) and $posted_data[$key] != $value){
										$return = $content;
									}
									break;
								case '>':
									if(isset($posted_data[$key]) and $posted_data[$key] > $value){
										$return = $content;
									}
									break;
								case '>=':
									if(isset($posted_data[$key]) and $posted_data[$key] >= $value){
										$return = $content;
									}
									break;
								case '<':
									if(isset($posted_data[$key]) and $posted_data[$key] < $value){
										$return = $content;
									}
									break;
								case '<=':
									if(isset($posted_data[$key]) and $posted_data[$key] <= $value){
										$return = $content;
									}
									break;
								case 'LIKE':
									if(isset($posted_data[$key]) and strpos($posted_data[$key], $value) !== false){
										$return = $content;
									}
									break;
								case 'NOT LIKE':
									if(isset($posted_data[$key]) and strpos($posted_data[$key], $value) === false){
										$return = $content;
									}
									break;
								case 'EXISTS':
									if(isset($posted_data[$key])){
										$return = $content;
									}
									break;
								case 'NOT EXISTS':
									if(!isset($posted_data[$key])){
										$return = $content;
									}
									break;
								case 'REGEXP':
									if(isset($posted_data[$key]) and preg_match($value, $posted_data[$key]) === 1){
										$return = $content;
									}
									break;
								case 'NOT REGEXP':
									if(isset($posted_data[$key]) and preg_match($value, $posted_data[$key]) === 0){
										$return = $content;
									}
									break;
							}
						}
					}
				}
			}
			return $return;
		}

	 // ------------------------------------------------------------------------------------------------

	 	public static function url__shortcode($atts, $content = ''){
	 		return urlencode($content);
		}

	 // ------------------------------------------------------------------------------------------------
	 //
	 // UPDATES
	 //
	 // ------------------------------------------------------------------------------------------------

	 	private static function check_for_updates(){
			require_once(plugin_dir_path( __FILE__ ) . 'plugin-update-checker-4.9/plugin-update-checker.php');
			Puc_v4_Factory::buildUpdateChecker('https://github.com/ifwp/ifwp-contact', __FILE__, 'ifwp-contact');
		}

	 // ------------------------------------------------------------------------------------------------
	 //
	 // MISCELLANEOUS
	 //
	 // ------------------------------------------------------------------------------------------------

		public static function add_data_option($option = '', $values = array()){
			if($option and is_array($values)){
				self::$data_options[$option] = $values;
			}
		}

	 // ------------------------------------------------------------------------------------------------

		public static function add_form_tag(){
			if(function_exists('wpcf7_add_form_tag')){
				return call_user_func_array('wpcf7_add_form_tag', func_get_args());
			} else {
				return call_user_func_array('wpcf7_add_shortcode', func_get_args());
			}
		}

	 // ------------------------------------------------------------------------------------------------

		public static function get_contact_form($contact_form = false){
			if(is_object($contact_form) and $contact_form instanceof WPCF7_ContactForm){
				return $contact_form;
			} elseif(is_int($contact_form)){
				return wpcf7_contact_form($contact_form);
			} elseif(is_string($contact_form)){
				$contact_form = trim($contact_form);
				if(is_numeric($contact_form)){
					return wpcf7_contact_form((int) $contact_form);
				} else {
					return wpcf7_get_contact_form_by_title($contact_form);
				}
			} else {
				return null;
			}
		}

	 // ------------------------------------------------------------------------------------------------

		public static function get_post($post = null){
			if(is_string($post) and strlen($post) == 13 and strpos('=', $post) === false){
				$posts = self::get_posts(array(
					'meta_key' => 'ifwp_uniqid',
					'meta_value' => $post,
					'posts_per_page' => 1
				));
				if($posts){
					return array_shift($posts);
				}
			} else {
				$post = get_post($post);
				if($post and $post->post_type == 'ifwp_saved_contact'){
					return $post;
				}
			}
			return null;
		}

	 // ------------------------------------------------------------------------------------------------

		public static function get_post_to_update($post = null, $contact_form = false){
			$post = self::get_post($post);
			if($post){
				$contact_form = self::get_contact_form($contact_form);
				if($contact_form){
					$updates = $contact_form->additional_setting('ifwp_update', false);
					if($updates){
						$updates = array_map('wpcf7_strip_quote', $updates);
						foreach($updates as $update){
							$contact_form_to_update = self::get_contact_form($update);
							if($contact_form_to_update and $contact_form_to_update->id() == get_post_meta($post->ID, 'wpcf7_ID', true)){
								$continue = false;
								$ifwp_allow_updates_from = $contact_form_to_update->additional_setting('ifwp_allow_updates_from', false);
								if($ifwp_allow_updates_from){
									$ifwp_allow_updates_from = array_map('wpcf7_strip_quote', $ifwp_allow_updates_from);
									if(in_array($contact_form->id(), $ifwp_allow_updates_from) or in_array($contact_form->title(), $ifwp_allow_updates_from)){
										$continue = true;
									}
								} elseif($contact_form_to_update->is_true('ifwp_allow_updates')){
									$continue = true;
								}
								if($continue){
									if($contact_form_to_update->is_true('ifwp_strict_update')){
										if($post->post_author == get_current_user_id() or current_user_can('edit_others_posts')){
											return $post;
										}
									} else {
										return $post;
									}
								}
							}
						}
					}
				}
			}
			return null;
		}

	 // ------------------------------------------------------------------------------------------------

		public static function get_posts($args = array()){
			return get_posts(wp_parse_args($args, array(
				'post_status' => 'private',
				'post_type' => 'ifwp_saved_contact',
				'posts_per_page' => -1
			)));
		}

	 // ------------------------------------------------------------------------------------------------

		public static function get_data_option($option = ''){
			if($option and isset(self::$data_options[$option])){
				return self::$data_options[$option];
			} else {
				return array();
			}
		}

	 // ------------------------------------------------------------------------------------------------

		private static function ifwp_session_start(){
			if(!session_id()){
				session_start();
			}
		}

	 // ------------------------------------------------------------------------------------------------

		private static function ifwp_session_destroy(){
			if(session_id()){
				session_destroy();
			}
		}

	 // ------------------------------------------------------------------------------------------------

		public static function insert_box($type = '', $args = array()){ ?>
            <div class="insert-box">
                <input type="text" name="<?php echo $type; ?>" class="tag code" readonly onfocus="this.select()" />
                <div class="submitbox">
                    <input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr(__('Insert Tag', 'contact-form-7')); ?>" />
                </div>
                <br class="clear" />
                <p class="description mail-tag">
                    <label for="<?php echo esc_attr($args['content'] . '-mailtag'); ?>"><?php
                        echo sprintf(esc_html(__('To use the value input through this field in a mail field, you need to insert the corresponding mail-tag (%s) into the field on the Mail tab.', 'contact-form-7')), '<strong><span class="mail-tag"></span></strong>'); ?>
                        <input type="text" class="mail-tag code hidden" readonly id="<?php echo esc_attr($args['content'] . '-mailtag'); ?>" />
                    </label>
                </p>
            </div><?php
        }

	 // ------------------------------------------------------------------------------------------------

	 	public static function menu_page(){
	 		if(!current_user_can('manage_options')){
				wp_die(__('You do not have sufficient permissions to access this page.'));
			}
			echo '<div class="wrap">';
			echo '<p>Improvements and Fixes for WordPress</p>';
			echo '</div>';
		}

	 // ------------------------------------------------------------------------------------------------

	 	private static function move_file($path = '', $post_id = null){
			$return = 0;
			$post = get_post($post_id);
			if($post){
				$post_id = $post->ID;
			} else {
				$post_id = 0;
			}
			if(file_exists($path)){
				$filename = basename($path);
				$wp_upload_dir = wp_upload_dir();
				$wp_upload_dir_path = $wp_upload_dir['basedir'] . '/ifwp-contact';
				if(wp_mkdir_p($wp_upload_dir_path)){
					$wp_upload_dir_url = $wp_upload_dir['baseurl'] . '/ifwp-contact';
					$new_filename = wp_unique_filename($wp_upload_dir_path, self::sanitize_file_name($filename));
					$new_path = $wp_upload_dir_path . '/' . $new_filename;
					if(copy($path, $new_path)){
						$new_filetype = wp_check_filetype($new_filename);
						$return = wp_insert_attachment(array(
							'guid' => $wp_upload_dir_url . '/' . $new_filename,
							'post_content' => '',
							'post_mime_type' => $new_filetype['type'],
							'post_status' => 'inherit',
							'post_title' => preg_replace('/\.[^.]+$/', '', $filename)
						), $new_path, $post_id);
						if($return){
							if(wp_attachment_is_image($return)){
								$metadata = wp_generate_attachment_metadata($return, $new_path);
								if($metadata){
									wp_update_attachment_metadata($return, $metadata);
								}
							}
						} else {
							@unlink($new_path);
						}
					}
				}
			}
			return $return;
		}

	 // ------------------------------------------------------------------------------------------------

		public static function new_form_tag($tag){
			if(class_exists('WPCF7_FormTag')){
				$tag = new WPCF7_FormTag($tag);
			} else {
				$tag = new WPCF7_Shortcode($tag);
			}
			$tag->basetype = str_replace('ifwp_', '', $tag->basetype);
			return $tag;
		}

	 // ------------------------------------------------------------------------------------------------

	 	private static function register_post_types(){
			register_post_type('ifwp_saved_contact', array(
				'capability_type' => 'page',
				'labels' => array(
					'name' => 'Saved Contacts',
					'singular_name' => 'Saved Contact'
				),
				'show_in_admin_bar' => false,
				'show_in_menu' => 'ifwp',
				'show_ui' => true,
				'supports' => array('custom-fields', 'title')
			));
		}

	 // ------------------------------------------------------------------------------------------------

		public static function sanitize_file_name($filename){
	 		return urldecode(implode('.', array_map('sanitize_title', explode('.', $filename))));
		}

	 // ------------------------------------------------------------------------------------------------

		public static function scan_form_tags(){
			if(function_exists('wpcf7_scan_form_tags')){
				return call_user_func_array('wpcf7_scan_form_tags', func_get_args());
			} else {
				return call_user_func_array('wpcf7_scan_shortcode', func_get_args());
			}
		}

	 // ------------------------------------------------------------------------------------------------

	 	public static function wpcf7_contact_form_tag_func($atts, $content = null, $code = ''){
			if(is_feed()){
				return '[contact-form-7]';
			}
			if('contact-form-7' == $code){
				$atts = shortcode_atts(array(
					'id' => 0,
					'title' => '',
					'html_id' => '',
					'html_name' => '',
					'html_class' => '',
					'output' => 'form'
				), $atts, 'wpcf7');
				$id = (int) $atts['id'];
				$title = trim($atts['title']);
				if(!$contact_form = wpcf7_contact_form($id)){
					$contact_form = wpcf7_get_contact_form_by_title($title);
				}
			} else {
				if(is_string($atts)){
					$atts = explode(' ', $atts, 2);
				}
				$id = (int) array_shift($atts);
				$contact_form = wpcf7_get_contact_form_by_old_id($id);
			}
			if(!$contact_form){
				return '[contact-form-7 404 "Not Found"]';
			}
			$open_message = $contact_form->additional_setting('ifwp_open_message');
			$open_message = apply_filters('ifwp_open_message', wpcf7_strip_quote((string) array_shift($open_message)), $contact_form);
			$setting = $contact_form->additional_setting('ifwp_open_date');
			if($setting){
				$setting = strtotime(wpcf7_strip_quote($setting[0]));
				if($setting){
					if(current_time('timestamp', 1) < $setting){
						return $open_message;
					}
				}
			}
			if(!apply_filters('ifwp_open', true, $contact_form)){
				return $open_message;
			}
			$close_message = $contact_form->additional_setting('ifwp_close_message');
			$close_message = apply_filters('ifwp_close_message', wpcf7_strip_quote((string) array_shift($close_message)), $contact_form);
			$setting = $contact_form->additional_setting('ifwp_close_limit');
			if($setting){
				$setting = (int) wpcf7_strip_quote($setting[0]);
				if($setting){
					$posts = self::get_posts(array(
						'fields' => 'ids',
						'meta_key' => 'wpcf7_ID',
						'meta_value' => $contact_form->id()
					));
					$post_count = count($posts);
					$post_count = apply_filters('ifwp_close_limit', $post_count, $posts);
					if($post_count >= $setting){
						return $close_message;
					}
				}
			}
			$setting = $contact_form->additional_setting('ifwp_close_date');
			if($setting){
				$setting = strtotime(wpcf7_strip_quote($setting[0]));
				if($setting){
					if(current_time('timestamp', 1) > $setting){
						return $close_message;
					}
				}
			}
			if(apply_filters('ifwp_close', false, $contact_form)){
				return $close_message;
			}
			return $contact_form->form_html($atts);
		}

	 // ------------------------------------------------------------------------------------------------

		private static function wpcf7_mail($contact_form, $status){
			$data = array();
			$data['contact_form']['name'] = $contact_form->name();
			$data['contact_form']['title'] = $contact_form->title();
			$submission = WPCF7_Submission::get_instance();
			if($submission){
				$data['submission']['remote_ip'] = $submission->get_meta('remote_ip');
				$data['submission']['response'] = $submission->get_response();
				$data['submission']['status'] = $submission->get_status();
				$data['submission']['timestamp'] = $submission->get_meta('timestamp');
				$data['submission']['url'] = $submission->get_meta('url');
				$data['submission']['user_agent'] = $submission->get_meta('user_agent');
				$posted_data = $submission->get_posted_data();

				// fix cf7 new version
				if(!array_key_exists('_wpcf7', $posted_data)){
					$posted_data = array_merge($_POST, $posted_data);
				}

				if($posted_data){
					foreach($posted_data as $key => $value){
						if(substr($key, 0, strlen('_wpcf7')) == '_wpcf7'){
							$key = preg_replace('/_wpcf7/', '', $key, 1);
							if($key){
								$key = preg_replace('/_/', '', $key, 1);
							} else {
								$key = 'ID';
							}
							$data['wpcf7'][$key] = $value;
						} elseif(substr($key, 0, strlen('_ifwp')) == '_ifwp'){
							$key = preg_replace('/_ifwp/', '', $key, 1);
							if($key){
								$key = preg_replace('/_/', '', $key, 1);
								$data['ifwp'][$key] = $value;
							}
						} else {
							$data['posted_data'][$key] = $value;
						}
					}
					if(preg_match('/^wpcf7-f(\d+)-p(\d+)-o(\d+)$/', $data['wpcf7']['unit_tag'], $matches)){
						if(count($matches) == 4){
							$post = get_post(absint($matches[2]));
							if($post){
								$data['post']['ID'] = $post->ID;
								$data['post']['name'] = $post->post_name;
								$data['post']['title'] = $post->post_title;
							}
						}
					}
				}
				$uploaded_files = $submission->uploaded_files();
				if($uploaded_files){
					foreach($uploaded_files as $key => $value){
						$data['uploaded_files'][$key] = $value;
					}
				}
			}
			if(!$contact_form->is_true('ifwp_incognito_mode')){
				$post_id = 0;
				if(isset($data['ifwp']['update'])){
					$post = self::get_post_to_update($data['ifwp']['update'], $contact_form);
					if($post){
						$post_id = $post->ID;
					}
					unset($data['ifwp']['update']);
				}
				if($post_id){
					$update = true;
				} else {
					$update = false;
					$post_id = wp_insert_post(array(
						'post_name' => sprintf('contact-form-7-id-%1$d-title-%2$s-uniqid-%3$s', $data['wpcf7']['ID'], $data['contact_form']['title'], $data['ifwp']['uniqid']),
						'post_status' => 'private',
						'post_title' => sprintf('[contact-form-7 id="%1$d" title="%2$s" uniqid="%3$s"]', $data['wpcf7']['ID'], $data['contact_form']['title'], $data['ifwp']['uniqid']),
						'post_type' => 'ifwp_saved_contact',
					));
					if($post_id){
						if(is_wp_error($post_id)){
							$post_id = 0;
						}
					}
				}
				if($post_id){
					$post = get_post($post_id);
					foreach($data['contact_form'] as $key => $value){
						add_post_meta($post_id, 'contact_form_' . $key, $value, true);
					}
					foreach($data['submission'] as $key => $value){
						add_post_meta($post_id, 'submission_' . $key, $value, true);
					}
					foreach($data['wpcf7'] as $key => $value){
						add_post_meta($post_id, 'wpcf7_' . $key, $value, true);
					}
					foreach($data['ifwp'] as $key => $value){
						add_post_meta($post_id, 'ifwp_' . $key, $value, true);
					}
					if($data['posted_data']){
						foreach($data['posted_data'] as $key => $value){
							update_post_meta($post_id, $key, $value);
						}
					}
					if($data['post']){
						foreach($data['post'] as $key => $value){
							add_post_meta($post_id, 'post_' . $key, $value, true);
						}
					}
					if(!empty($data['uploaded_files'])){
						foreach($data['uploaded_files'] as $key => $value){
							foreach((array) $value as $single){
								$attachment_id = self::move_file($single, $post_id);
								update_post_meta($post_id, 'uploaded_files_' . $key, $attachment_id);
							}
						}
					}
					do_action('ifwp_save_contact', $post_id, $post, $update);
				}
			}
		}

	 // ------------------------------------------------------------------------------------------------

	}

 // ----------------------------------------------------------------------------------------------------

	IFWP_Contact::construct();

 // ----------------------------------------------------------------------------------------------------

 	if(!class_exists('IFWP_Backcompat')){
		class IFWP_Backcompat {
			private $class_name = '';
			public function __construct($class_name = ''){
				$this->class_name = $class_name;
			}
			public function __call($name, $arguments){
				if(is_callable($this->class_name . '::' . $name)){
					return call_user_func_array($this->class_name . '::' . $name, $arguments);
				} else {
					die('IFWP Error.');
				}
			}
		}
	}
	if(!function_exists('ifwp')){
		function ifwp($class_name = ''){
			$class_name = 'ifwp_' . str_replace('-', '_', sanitize_title($class_name));
			if(class_exists($class_name)){
				return new IFWP_Backcompat($class_name);
			} else {
				return null;
			}
		}
	}

 // ----------------------------------------------------------------------------------------------------
