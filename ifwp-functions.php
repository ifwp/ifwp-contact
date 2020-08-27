<?php

 // ----------------------------------------------------------------------------------------------------

	function ifwp_session_start(){
		if(!session_id()){
			session_start();
		}
	}

 // ----------------------------------------------------------------------------------------------------

	function ifwp_get_filename(){
		if(isset($_GET['ifwp_file'])){
			$wp_upload_dir = wp_upload_dir();
			return $wp_upload_dir['basedir'] . '/ifwp-contact/' . $_GET['ifwp_file'];
		}
		return '';
	}

 // ----------------------------------------------------------------------------------------------------

	function ifwp_get_post(){
		global $wpdb;
		if(isset($_GET['ifwp_file'])){
			$wp_upload_dir = wp_upload_dir();
			$guid = $wp_upload_dir['baseurl'] . '/ifwp-contact/' . $_GET['ifwp_file'];
			$row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->posts WHERE guid = %s", $guid));
			if($row){
				return $row;
			}
			preg_match('/^(.+)(-\d+x\d+)(\.' . substr($guid, strrpos($guid, '.') + 1) . ')?$/', $guid, $matches);
			if($matches){
				$guid = $matches[1];
				if(isset($matches[3])){
					$guid .= $matches[3];
				}
			}
			$row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->posts WHERE guid = %s", $guid));
			if($row){
				return $row;
			}
			preg_match('/^(.+)(-e\d+)(\.' . substr($guid, strrpos($guid, '.') + 1) . ')?$/', $guid, $matches);
			if($matches){
				$guid = $matches[1];
				if(isset($matches[3])){
					$guid .= $matches[3];
				}
			}
			$row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->posts WHERE guid = %s", $guid));
			if($row){
				return $row;
			}
		}
		return null;
	}

 // ----------------------------------------------------------------------------------------------------

	function ifwp_get_post_status($post_id = 0){
		global $wpdb;
		$sql = $wpdb->prepare("SELECT * FROM $wpdb->posts WHERE ID = %d", $post_id);
		$post = $wpdb->get_row($sql);
		if(!is_object($post)){
			return false;
		}
		if('attachment' == $post->post_type){
			if('private' == $post->post_status){
				return 'private';
			}
			if(('inherit' == $post->post_status) and (0 == $post->post_parent)){
				return 'publish';
			}
			if($post->post_parent and ($post->ID != $post->post_parent)){
				$parent_post_status = ifwp_get_post_status($post->post_parent);
				if('trash' == $parent_post_status){
					$sql = $wpdb->prepare("SELECT meta_value FROM $wpdb->postmeta WHERE post_id = %d AND meta_key = %s", $post->post_parent, '_wp_trash_meta_status');
					return $wpdb->get_var($sql);
				} else {
					return $parent_post_status;
				}
			}
		}
		return apply_filters('get_post_status', $post->post_status, $post);
	}

 // ----------------------------------------------------------------------------------------------------

	function ifwp_get_current_user_id(){
		ifwp_session_start();
		if(isset($_SESSION['ifwp_current_user_id']) and $_SESSION['ifwp_current_user_id']){
			return $_SESSION['ifwp_current_user_id'];
		} else {
			return 0;
		}
	}

 // ----------------------------------------------------------------------------------------------------

	function ifwp_current_user_can_read($post_author = 0){
		global $wpdb;
		$current_user_id = ifwp_get_current_user_id();
		if($current_user_id){
			if($current_user_id == $post_author){
				return true;
			} else {
				$role_caps = get_option($wpdb->prefix . 'user_roles');
				$user_caps = unserialize($wpdb->get_var($wpdb->prepare("SELECT meta_value FROM $wpdb->usermeta WHERE user_id = %d AND meta_key = %s LIMIT 1", $current_user_id, $wpdb->prefix . 'capabilities')));
				$all_caps = array();
				foreach($user_caps as $key => $value){
					if(isset($role_caps[$key]) && $value){
						$all_caps = array_merge($all_caps, $role_caps[$key]['capabilities']);
					} elseif($value){
						$all_caps = array_merge($all_caps, array(
							$key => $value
						));
					}
				}
				if(isset($all_caps['edit_others_posts']) && $all_caps['edit_others_posts']){
					return true;
				}
			}
		}
		return false;	
	}

 // ----------------------------------------------------------------------------------------------------

	function ifwp_readfile($file = ''){
		$mime = wp_check_filetype($file);
		if($mime['type'] === false && function_exists('mime_content_type')){
			$mime['type'] = mime_content_type($file);
		}
		if($mime['type']){
			$mimetype = $mime['type'];
		} else {
			$mimetype = 'image/' . substr($file, strrpos($file, '.') + 1);
		}
		header('Content-Type: ' . $mimetype);
		if(strpos($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS') === false){
			header('Content-Length: ' . filesize($file));
		}
		$last_modified = gmdate('D, d M Y H:i:s', filemtime($file));
		$etag = '"' . md5($last_modified) . '"';
		header("Last-Modified: $last_modified GMT");
		header('ETag: ' . $etag);
		header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 100000000) . ' GMT');
		$client_etag = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? stripslashes($_SERVER['HTTP_IF_NONE_MATCH']) : false;
		if(!isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])){
			$_SERVER['HTTP_IF_MODIFIED_SINCE'] = false;
		}
		$client_last_modified = trim($_SERVER['HTTP_IF_MODIFIED_SINCE']);
		$client_modified_timestamp = $client_last_modified ? strtotime($client_last_modified) : 0;
		$modified_timestamp = strtotime($last_modified);
		if(($client_last_modified && $client_etag) ? (($client_modified_timestamp >= $modified_timestamp) && ($client_etag == $etag)) : (($client_modified_timestamp >= $modified_timestamp) || ($client_etag == $etag))){
			status_header(304);
			exit;
		}
		readfile($file);
	}

 // ----------------------------------------------------------------------------------------------------

	function ifwp_show_file($post){
		$return = true;
		if(!ifwp_current_user_can_read($post->post_author)){
			$return = false;
		}
		if(defined('IFWP_SHOW_FILE_FILTER_FUNCTIONS')){
			if(file_exists(IFWP_SHOW_FILE_FILTER_FUNCTIONS)){
				require_once(IFWP_SHOW_FILE_FILTER_FUNCTIONS);
				$return = apply_filters('ifwp_show_file', $return, $post);
			}
		}
		return $return;
	}

 // ----------------------------------------------------------------------------------------------------

