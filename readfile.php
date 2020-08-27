<?php

 // ----------------------------------------------------------------------------------------------------
 //
 // wp-content/plugins/ifwp-contact/readfile.php
 //
 // ----------------------------------------------------------------------------------------------------

	error_reporting(0);
	$wp_load = dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
	if(!file_exists($wp_load)){
		status_header(404);
		die('404 &#8212; File not found.');
	} else {
		define('SHORTINIT', true);
		require_once($wp_load);
		if(version_compare($wp_version, '5.1', '<')){
			require_once(ABSPATH . WPINC . '/formatting.php');
		}
		require_once(ABSPATH . WPINC . '/link-template.php');
		if(!defined('WP_CONTENT_URL')){
			define('WP_CONTENT_URL', get_option('siteurl') . '/wp-content');
		}
		require_once(dirname(__FILE__) . '/ifwp-functions.php');
		$file = ifwp_get_filename();
		if(!is_file($file)){
			status_header(404);
			die('404 &#8212; File not found.');
		} else {
			if(strpos($_SERVER['HTTP_REFERER'], admin_url()) === false){
				$post = ifwp_get_post();
				if(!$post){
					status_header(404);
					die('404 &#8212; File not found.');
				} else {
					$post_status = ifwp_get_post_status($post->ID);
					if($post_status == 'private'){
						if(!ifwp_show_file($post)){
							status_header(404);
							die('404 &#8212; File not found.');
						}
					}
				}
			}
		}
		ifwp_readfile($file);
	}

 // ----------------------------------------------------------------------------------------------------

