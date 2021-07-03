<?php

$za_av_message = false;

if($_SERVER['REQUEST_METHOD'] == 'POST')
{
	if(!empty($_FILES['bavatar']))
	{
		$max_size = $za_avatar_settings['maximum_size'];
		
		if($_FILES['bavatar']['size'] > $max_size * 1024)
		{
			/* Translators: KB is the acronym of kilobye */
			bb_die(sprintf(__('Your avatar\'s filesize is too large. The maximum filesize allowed is %u KB. Please upload a smaller file.', ZA_AVATAR_ID), $max_size));
		}

		$src = @imagecreatefromstring(file_get_contents($_FILES['bavatar']['tmp_name']));
		
		if(!$src) bb_die(__('The file you uploaded is not a valid image.', ZA_AVATAR_ID));
	
		imagesavealpha($src, true);
		imagealphablending($src, false);

		$temp = imagecreatefromstring(gzinflate(base64_decode('6wzwc+flkuJiYGDg9fRwCWJgYGIAYQ42IPWl4sovIMVYHOTuxLDunMxLIIctydvdheE/CC7Yu3wyUISzwCOymIGBWxiEGRlmzZEACrKXePq6st9kZRTi1nCI4qwFarzl6eIYUnHr7TVDRgYGjsMGB/Y/T2zqd3FaXzaJiaFOkpPhAMh4BgYDBoYGoAoeBoYEoAAzAwPQPPxSJCpHkiJROUKKLHeCpMhzJw9IbDSERkNoNIRGQ2g0hAZVCD04x7TkB9tfn9e/HIFiDJ6ufi7rnBKaAA==')));
		imagesavealpha($temp, true);
		imagealphablending($temp, false);
		
		$ts = $za_avatar_settings['thumbnail_size'];

		$x = imagesx($src);
		$y = imagesy($src);
		$m = max($x, $y);
		$w = $x * $ts / $m;
		$h = $y * $ts / $m;
		$l = ceil(($ts - $w) / 2);
		$t = ceil(($ts - $h) / 2);

		imagecopyresampled($temp, $src, $l, $t, 0, 0, $w, $h, $x, $y);
		imagedestroy($src);

		$id = bb_get_usermeta($user_id, 'za_avatar');
		
		if(!empty($id))
		{
			$folder = BB_PATH . 'avatars/' . substr($id, 0, 1) . '/' . substr($id, 0, 2) . '/' . substr($id, 0, 3) . '/';

			@unlink($folder . $id . '.png');

			foreach(bb_glob($folder . $id . '_*.png') as $avatarsize) {
				@unlink($avatarsize);
			}
		}
		
		$id = md5($user_id . time());
		$folder = BB_PATH . 'avatars/' . substr($id, 0, 1) . '/' . substr($id, 0, 2) . '/' . substr($id, 0, 3) . '/';
		bb_update_usermeta($user_id, 'za_avatar', $id);

		imagepng($temp, $folder . $id . '.png', 9);

		imagedestroy($temp);

		$za_av_message = __('Avatar uploaded successfully.', ZA_AVATAR_ID);
	} elseif($_POST['delete'])
	{
		bb_check_admin_referer('bavatar_delete-' . $user_id);
		$id = bb_get_usermeta($user_id, 'za_avatar');

		if(!empty($id))
		{
			$folder = BB_PATH . 'avatars/' . substr($id, 0, 1) . '/' . substr($id, 0, 2) . '/' . substr($id, 0, 3) . '/';

			@unlink($folder . $id . '.png');

			foreach(bb_glob($folder . $id . '_*.png') as $avatarsize) {
				@unlink($avatarsize);
			}
			
			bb_delete_usermeta($user_id, 'za_avatar');
			
			$za_av_message = __('Avatar deleted successfully.', ZA_AVATAR_ID);
		} else $za_av_message = __('You don\'t have an avatar!', ZA_AVATAR_ID);
	}
}

bb_get_header();

$za_av_meta = bb_get_usermeta($user_id, 'za_avatar');
?>

<div class="bbcrumb">
	<a href="<?php bb_uri(); ?>"><?php bb_option('name'); ?></a> &raquo;
	<a href="<?php user_profile_link($user_id); ?>"><?php echo get_user_display_name($user_id); ?></a> &raquo;
	<?php echo $profile_page_title; ?>
</div>
<h2 id="userlogin" role="main"><?php echo get_user_display_name($user->ID); ?> <small>(<?php echo get_user_name($user->ID); ?>)</small></h2>
<?php
if($za_av_message) echo '<div class="notice"><p>' . $za_av_message . '</p></div>';
?>
<div id="useravatar"><?php echo bb_get_avatar($user_id, 256); ?></div>
<form method="post" action="<?php profile_tab_link($user_id, 'avatar'); ?>" enctype="multipart/form-data">
<fieldset>
<legend><?php
	if(empty($za_av_meta)) printf(__('Upload a new avatar. (%u KB maximum)', ZA_AVATAR_ID),
		$za_avatar_settings['maximum_size']);
	else _e('Upload an avatar', ZA_AVATAR_ID);
?></legend>
	<input type="file" name="bavatar" id="bavatar" />
</fieldset>
<p class="submit right">
  <input type="submit" value="<?php echo esc_attr__('Aggiorna profilo &raquo;'); ?>" />
</p>
</form>
<?php
if(!empty($za_av_meta))
{ ?>
<form method="post" action="<?php profile_tab_link($user_id, 'avatar'); ?>"><?php bb_nonce_field('bavatar_delete-' . $user_id); ?>
<p class="submit left">
	<input type="submit" class="delete" name="delete" value="<?php _e('Cancella avatar &raquo;', ZA_AVATAR_ID); ?>"
		onclick="return confirm('<?php echo esc_js(__('Sei sicuro di voler cancellare il tuo avatar?')) ?>')" />
</p>
</form>
<?php }

bb_get_footer(); ?>