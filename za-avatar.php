<?php
/*
Plugin Name: zaerl Avatar
Plugin Description: Avatars for bbPress
Version: 1.0
Plugin URI: http://www.zaerl.com
Author: zaerl
Author URI: http://www.zaerl.com

zaerl Avatar: avatars upload for bbPress
Copyright (C) 2010  Francesco Bigiarini

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.

This software is based on the work of `Nightgunner5'. You can download
the original code here: http://nightgunner5.wordpress.com/tag/bavatars/

*/
	
define('ZA_AVATAR_VERSION', '1.0');
define('ZA_AVATAR_ID', 'za-avatar');
define('ZA_AVATAR_NAME', 'zaerl Avatar');

function za_avatar_install() {
	mkdir(BB_PATH . 'avatars', 0777);

	for ($a = 0; $a < 16; $a++ ) {
		mkdir(BB_PATH . 'avatars/' . dechex($a ), 0777);
		for ($b = 0; $b < 16; $b++ ) {
			mkdir(BB_PATH . 'avatars/' . dechex($a ) . '/' . dechex($a ) . dechex($b ), 0777);
			for ($c = 0; $c < 16; $c++ ) {
				mkdir(BB_PATH . 'avatars/' . dechex($a ) . '/' . dechex($a ) . dechex($b ) . '/' . dechex($a ) . dechex($b ) . dechex($c ), 0777);
			}
		}
	}
}
bb_register_plugin_activation_hook(__FILE__, 'za_avatar_install');
	
function za_avatar_initialize()
{
	bb_load_plugin_textdomain(ZA_AVATAR_ID, dirname(__FILE__) . '/languages');
	
	global $za_avatar_settings;

	$za_avatar_settings = bb_get_option('za_avatar');

	if(empty($za_avatar_settings))
	{
		$za_avatar_settings['maximum_size'] = 50;
		$za_avatar_settings['thumbnail_size'] = 512;
	}
}

add_action('bb_init', 'za_avatar_initialize');

function za_avatar_configuration_page()
{
	global $za_avatar_settings;
?>
<h2><?php /* Translators: %s is replaced by the program name */ printf(__('%s Settings', ZA_AVATAR_ID), ZA_AVATAR_NAME); ?></h2>
<?php do_action('bb_admin_notices'); ?>

<form class="settings" method="post" action="<?php bb_uri('bb-admin/admin-base.php', 
	array('plugin' => 'za_avatar_configuration_page'), BB_URI_CONTEXT_FORM_ACTION + BB_URI_CONTEXT_BB_ADMIN); ?>">
	<fieldset>
<?php

	bb_option_form_element('za_avatar_max_size', array(
		'title' => /* Translators: KB is the acronym of kilobye */
			__('Image Maximum Size', ZA_AVATAR_ID),
		'value' => $za_avatar_settings['maximum_size'],
		'note' => __('Specify the maximum size, in kilobyte, of uploaded images.', ZA_AVATAR_ID)
	));

	bb_option_form_element('za_avatar_width', array(
		'title' => /* Translators: KB is the acronym of kilobye */
			__('Thumbnail Size', ZA_AVATAR_ID),
		'value' => $za_avatar_settings['thumbnail_size'],
		'note' => __('Specify the thumbnail size.', ZA_AVATAR_ID)
	));
?>
	</fieldset>
	<fieldset class="submit">
		<?php bb_nonce_field('options-za-avatar-update'); ?>
		<input type="hidden" name="action" value="update-za-avatar-settings" />
		<input class="submit" type="submit" name="submit" value="<?php _e('Save Changes', ZA_AVATAR_ID) ?>" />
	</fieldset>
</form>
<?php
}

function za_avatar_configuration_page_add()
{
	bb_admin_add_submenu(ZA_AVATAR_NAME, 'administrate', 'za_avatar_configuration_page', 'options-general.php');
}

add_action('bb_admin_menu_generator', 'za_avatar_configuration_page_add');

function za_avatar_configuration_page_process()
{
	global $za_avatar_settings;
	$changed = FALSE;

	if('post' == strtolower($_SERVER['REQUEST_METHOD']) &&
		$_POST['action'] == 'update-za-avatar-settings')
	{
		bb_check_admin_referer('options-za-avatar-update');

		$goback = remove_query_arg(array('za-avatar-invalid-size',
			'za-avatar-void-size', 'za-avatar-updated'), wp_get_referer());
		
		if(isset($_POST['za_avatar_max_size']) && isset($_POST['za_avatar_width']))
		{
			$value = stripslashes_deep(trim($_POST['za_avatar_max_size']));
		
			if($value != '')
			{
				if(is_numeric($value) && (int)$value != 0)
				{
					$za_avatar_settings['maximum_size'] = (int)$value;
				} else
				{
					$goback = add_query_arg('za-avatar-invalid-size', 'true', $goback);
					bb_safe_redirect($goback);
					exit;
				}
			} else
			{
				$goback = add_query_arg('za-avatar-void-size', 'true', $goback);
				bb_safe_redirect($goback);
				exit;
			}
			
			$value = stripslashes_deep(trim($_POST['za_avatar_width']));
		
			if($value != '')
			{
				// check
				if(is_numeric($value) && (int)$value != 0)
				{
					$za_avatar_settings['thumbnail_size'] = (int)$value;
				} else
				{
					$goback = add_query_arg('za-avatar-invalid-size', 'true', $goback);
					bb_safe_redirect($goback);
					exit;
				}
			} else
			{
				$goback = add_query_arg('za-avatar-void-size', 'true', $goback);
				bb_safe_redirect($goback);
				exit;
			}

			bb_update_option('za_avatar', $za_avatar_settings);
			$goback = add_query_arg('za-avatar-updated', 'true', $goback);
			bb_safe_redirect($goback);

			exit;
		}
		
		$goback = add_query_arg('za-avatar-void-size', 'true', $goback);
		bb_safe_redirect($goback);

		exit;
	}

	if(!empty($_GET['za-avatar-updated']))
		bb_admin_notice(__('<strong>Settings saved.</strong>', ZA_AVATAR_ID));
	else if(!empty($_GET['za-avatar-invalid-size']))
	{
		bb_admin_notice(('<strong>Please input a valid number.</strong>'), 'error');
	} else if(!empty($_GET['za-avatar-void-size']))
	{
		bb_admin_notice(('<strong>Please input something!</strong>'), 'error');
	}

	global $bb_admin_body_class;
	$bb_admin_body_class = ' bb-admin-settings';
}

add_action('za_avatar_configuration_page_pre_head', 'za_avatar_configuration_page_process');

function za_avatar_add_profile_tab()
{
	add_profile_tab(__('Avatar', ZA_AVATAR_ID), 'edit_profile', 'administrate',
		dirname(__FILE__ ) . '/za-avatar-profile.php', 'avatar');
}

add_action('bb_profile_menu', 'za_avatar_add_profile_tab');

function bb_za_avatar_filter($avatar, $id_or_email, $size, $default )
{
	global $za_avatar_settings;
	
	if (is_object($id_or_email ) ) {
		$id = $id_or_email->user_id;
	} elseif ((function_exists('is_email' ) && is_email($id_or_email ) ) ||
		(!function_exists('is_email' ) && !is_numeric($id_or_email ) ) ) {
		$id = bb_get_user($id_or_email, array('by' => 'email' ) )->ID;
	} else {
		$id = (int)$id_or_email;
	}

	if(!$id) return $avatar;

	$id = bb_get_usermeta($id, 'za_avatar');	
	if(empty($id)) return $avatar;

	$location = 'avatars/' . substr($id, 0, 1 ) . '/' . substr($id, 0, 2 ) .
		'/' . substr($id, 0, 3 ) . '/' . $id . '.png';

	if(!file_exists(BB_PATH . $location)) return $avatar;
	
	$ts = $za_avatar_settings['thumbnail_size'];

	if ($size != $ts )
	{
		$_location = $location;
		$location = 'avatars/' . substr($id, 0, 1 ) . '/' . substr($id, 0, 2 ) .
			'/' . substr($id, 0, 3 ) . '/' . $id . '_' . $size . '.png';
	}

	if (!file_exists(BB_PATH . $location ) ) {
		$src = imagecreatefrompng(BB_PATH . $_location);
		imagesavealpha($src, true);
		imagealphablending($src, false);

		$temp = imagecreatetruecolor($size, $size);
		imagesavealpha($temp, true);
		imagealphablending($temp, false);

		imagecopyresampled($temp, $src, 0, 0, 0, 0, $size, $size, $ts, $ts);

		imagepng($temp, BB_PATH . $location, 9);

		imagedestroy($temp);
		imagedestroy($src);
	}

	return '<img alt="" src="' . bb_get_option('uri' ) . $location . '" class="avatar avatar-' . $size . ' avatar-bavatar" style="height:' . $size . 'px; width:' . $size . 'px;" />';
}

add_filter('bb_get_avatar', 'bb_za_avatar_filter', 10, 4);

if (!function_exists('za_avatar_filter' ) )
	add_filter('get_avatar', 'bb_za_avatar_filter', 10, 4);

if (bb_is_admin() ) {
	function za_avatar_fix_permissions_really() {
		if (bb_verify_nonce($_GET['nonce'], 'za_avatar-fix-permissions' ) )
			za_avatar_install();
		bb_safe_redirect(wp_get_referer());
		exit;
	}

	add_action('za_avatar_fix_permissions_pre_head', 'za_avatar_fix_permissions_really');

	function za_avatar_fix_permissions() {}

	function za_avatar_admin_init()
	{
		if (!file_exists(BB_PATH . 'avatars' ) || !is_dir(BB_PATH . 'avatars' ) || !is_writable(BB_PATH . 'avatars' ) ) {
			bb_admin_notice(sprintf(__('za_avatar was unable to create the folders needed. Please create a folder called avatars in your forum root and set its permissions to 0777 (drwxrwxrwx). <a href="%s">Click here when you have done this</a>.', 'za_avatar' ), bb_get_uri('bb-admin/admin-base.php', array('plugin' => 'za_avatar_fix_permissions', 'nonce' => bb_create_nonce('za_avatar-fix-permissions' ) ), BB_CONTEXT_BB_ADMIN ) ), 'error');
		}

		if ($_GET['plugin'] == 'za_avatar_fix_permissions' )
			bb_admin_add_submenu('_', 'use_keys', 'za_avatar_fix_permissions');
	}
	add_action('bb_admin_menu_generator', 'za_avatar_admin_init');
}

?>