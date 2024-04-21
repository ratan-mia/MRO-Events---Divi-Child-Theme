
<?php

// use function get_adjacent_post;

// function my_custom_module() {
//     if(class_exists("ET_Builder_Module")){
//         include("blog-module.php");
//     }
// }
// add_action('et_builder_ready', 'my_custom_module');





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


// Register Custom Meta Box

// Hook to add custom meta fields
function my_custom_meta_fields()
{
	register_meta('mroevent', 'event_start_date', array(
		'type' => 'string',
		'description' => 'Event Start Date',
		'single' => true,
		'show_in_rest' => true,
	));
}
add_action('init', 'my_custom_meta_fields');



// Register Custom Shortcode

function query_mroevents_shortcode()
{
	// Set timezone based on your WordPress settings
	date_default_timezone_set(get_option('timezone_string'));

	// Get current date in 'Y-m-d' format
	$today = date('Y-m-d');

	// WP_Query arguments to get 'mroevent' posts from today onwards
	$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
	$args = array(
		'post_type'      => 'mroevent', // Custom post type
		'posts_per_page' => 3, // Retrieve all matching posts
		'paged'          => $paged,
		'post_status'    => 'publish', // Only retrieve published posts
		'meta_key'       => 'Event_Date', // Assuming you store event date in 'event_date' meta field
		'orderby'        => 'meta_value', // Order by the date
		'order'          => 'ASC', // Ascending order
		'meta_query'     => array(
			array(
				'key'     => 'Event_Date',
				'value'   => $today,
				'compare' => '>=', // Greater than or equal to today
				'type'    => 'DATE', // Type of the custom field (date)
			),
		),
	);

	// The Query
	$query = new WP_Query($args);

	// Check if the query returns any posts
	if ($query->have_posts()) : ?>
		<div class="et_pb_section">
			<div class="et_pb_row">
				<?php while ($query->have_posts()) : $query->the_post(); ?>
					<div class="et_pb_column et_pb_column_1_3 et-last-child">
						<div class="et_pb_module">
							<a href="<?php echo get_permalink(); ?>">
								<?php the_post_thumbnail('medium'); ?>
							</a>
							<h4><a href="<?php echo get_permalink(); ?>"><?php the_title(); ?></a></h4>

							<?php
							// Get the event date meta value
							$event_date_value = get_post_meta(get_the_ID(), 'Event_Date', true);

							if (!empty($event_date_value) && DateTime::createFromFormat("Y-m-d", $event_date_value) !== false) {
								$event_date = DateTime::createFromFormat("Y-m-d", $event_date_value);

								if ($event_date !== false) {
									$formattedDate = $event_date->format("j M, Y");
									echo '<p> Event Start Date: ' . $formattedDate . '</p>';
								} else {
									echo '<p> Error: Invalid date format for Event_Date meta value. </p>';
								}
							} else {
								echo '<p> Error: Event_Date meta value is empty or not in the correct format. </p>';
							}
							?>



						</div>
					</div> <!-- Close column -->
				<?php endwhile; ?>
			</div> <!-- Close the row -->
		</div> <!-- Close the section -->

		<?php if ($query->max_num_pages > 1) { ?>
			<div class="pagination">
				<?php
				echo paginate_links(array(
					'base'    => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
					'format' => '?paged=%#%',
					'current' => max(1, $paged),
					'total' => $query->max_num_pages
				));
				?>
			</div>
		<?php } ?>



	<?php else : ?>

		<p>No upcoming events found.</p>
	<?php endif; ?>
<?php wp_reset_postdata();
}

add_shortcode('mroevents', 'query_mroevents_shortcode');

// Extend the Divi Blog module


function modify_divi_et_builder_blog_query($query, $args) {
	$timezone_string = get_option('timezone_string');
	$today = date('Y-m-d');

	if (isset($args['module_id']) && $args['module_id'] === 'mro-latest-events') {
		$query->query_vars['orderby'] = 'meta_value';
		$query->query_vars['order'] = 'ASC';
		$query->query_vars['meta_key'] = 'Event_Date';
		$query->query_vars['meta_query'] = array(
			array(
				'key'     => 'Event_Date',
				'value'   => $today,
				'compare' => '>=', // Greater than or equal to today
				'type'    => 'DATE', // Type of the custom field (date)
			),
		);
		$query = new WP_Query( $query->query_vars );
	}
	return $query;
}
add_filter('et_builder_blog_query', 'modify_divi_et_builder_blog_query', 10, 2);


// function custom_child_theme_setup() {
//     // Adjust the path as needed
//     get_template_part( 'child-next-blog-carousel.php' );
// }
// add_action( 'et_builder_ready', 'custom_child_theme_setup' );

function my_custom_module() {
    if(class_exists("ET_Builder_Module")){
        include("child-next-blog-carousel.php");
    }
}
add_action('et_builder_ready', 'my_custom_module');

// Add New Column to the MRO Event Post Type
// add new columns
add_filter( 'manage_mroevent_posts_columns', 'mroevents_custom_posts_columns' );
// the above hook will add columns only for default 'post' post type, for CPT:
// manage_{POST TYPE NAME}_posts_columns
function mroevents_custom_posts_columns( $column_array ) {

	$column_array[ 'Event_Date' ] = 'Event Date';
	$column_array[ 'Event_End_Date' ] = 'Event End Date';
	$column_array[ 'Event_Venue_City' ] = 'Event Venue City';
	// $column_array[ 'dnxte_popup-active' ] = 'Popup Active';
	$column_array[ 'Event_Link' ] = 'Event Link';
	
	// the above code will add columns at the end of the array
	// if you want columns to be added in another order, use array_slice()

	return $column_array;
}

// Populate our new columns with data
add_action( 'manage_mroevent_posts_custom_column', 'mro_events_populate_columns_data', 10, 2 );
function mro_events_populate_columns_data( $column_name, $post_id ) {

	// if you have to populate more than one column, use switch()
	switch( $column_name ) {
		case 'Event_Date': {
			$event_date = get_post_meta( $post_id, 'Event_Date', true );
			echo $event_date ? $event_date : '';
			break;
		}

		case 'Event_End_Date': {
			$event_end_date = get_post_meta( $post_id, 'Event_End_Date', true );
			echo $event_end_date ? $event_end_date : '';
			break;
		}

		case 'Event_Venue_City': {
			$event_venu_city = get_post_meta( $post_id, 'Event_Venue_City', true );
			echo $event_venu_city ? $event_venu_city : '';
			break;
		}
		// case 'dnxte_popup-active': {
		// 	$popup_active = get_post_meta( $post_id, 'dnxte_popup-active', true );
		// 	echo $popup_active ? $popup_active : '';
		// 	break;
		// }
		case 'Event_Link': {
			$event_link = get_post_meta( $post_id, 'Event_Link', true );
			echo $event_link ? $event_link : '';
			break;
		}
		
	}

}



// quick_edit_custom_box allows to add HTML in Quick Edit
add_action( 'quick_edit_custom_box',  'mro_events_quick_edit_fields', 10, 2 );

function mro_events_quick_edit_fields( $column_name, $post_type ) {

	switch( $column_name ) {
		case 'Event_Date': {
			global $post;
			$event_date = get_post_meta( $post->ID, 'Event_Date', true );
			?>
				<fieldset class="inline-edit-col-left">
					<div class="inline-edit-col">
						<label>
							<span class="title">Date</span>
							<input type="text" name="Event_Date" value="<?php echo esc_attr( $event_date ); ?>" placeholder="<?php echo esc_attr( $event_date ); ?>">
						</label>
					</div>
				</fieldset>
			<?php
			break;
		}

		case 'Event_End_Date': {
			global $post;
			$event_end_date = get_post_meta( $post->ID, 'Event_End_Date', true );
			?>
				<fieldset class="inline-edit-col-left">
					<div class="inline-edit-col">
						<label>
							<span class="title">Event End Date</span>
							<input type="text" name="Event_End_Date" value="<?php echo esc_attr( $event_end_date ); ?>" placeholder="<?php echo esc_attr( $event_end_date ); ?>">
						</label>
					</div>
				</fieldset>
			<?php
			break;
		}


		case 'Event_Venue_City': {
			global $post;
			$event_venue_city = get_post_meta( $post->ID, 'Event_Venue_City', true );
			?>
				<fieldset class="inline-edit-col-left">
					<div class="inline-edit-col">
						<label>
							<span class="title">Venue</span>
							<input type="text" name="Event_Venue_City" value="<?php echo esc_attr( $event_venue_city ); ?>" placeholder="<?php echo esc_attr( $event_venue_city ); ?>">
						</label>
					</div>
				</fieldset>
			<?php
			break;
		}
		// Do for Event_Link
		case 'Event_Link': {
			global $post;
			$event_link = get_post_meta( $post->ID, 'Event_Link', true );
			?>
				<fieldset class="inline-edit-col-left">
					<div class="inline-edit-col">
						<label>
							<span class="title">Link</span>
							<input type="text" name="Event_Link" value="<?php echo esc_attr( $event_link ); ?>" placeholder="<?php echo esc_attr( $event_link ); ?>">
						</label>
					</div>
				</fieldset>
			<?php
			break;
		}


		// case 'dnxte_popup-active': {
		// 	global $post;
		// 	$popup_active = get_post_meta( $post->ID, 'dnxte_popup-active', true );
		// 	?>
		// 		<fieldset class="inline-edit-col-left">
		// 			<div class="inline-edit-col">
		// 				<label>
		// 					<span class="title">Popup</span>
		// 					<input type="text" name="dnxte_popup-active" value="<?php echo esc_attr( $popup_active ); ?>" placeholder="<?php echo esc_attr( $popup_active ); ?>">
		// 				</label>
		// 			</div>
		// 		</fieldset>
		// 	<?php
		// 	break;
		// }


	}
}

// save fields after quick edit
add_action( 'save_post_mroevent', 'mro_events_quick_edit_save' );

function mro_events_quick_edit_save( $post_id ){

	// check inline edit nonce
	if ( ! wp_verify_nonce( $_POST[ '_inline_edit' ], 'inlineeditnonce' ) ) {
		return;
	}

	// update the event date
	$event_date = ! empty( $_POST[ 'Event_Date' ] ) ? sanitize_text_field( $_POST[ 'Event_Date' ] ) : '';
 	update_post_meta( $post_id, 'Event_Date', $event_date );


	// update the event end date
	$event_end_date = ! empty( $_POST[ 'Event_End_Date' ] ) ? sanitize_text_field( $_POST[ 'Event_End_Date' ] ) : '';
 	update_post_meta( $post_id, 'Event_End_Date', $event_end_date );

	// update the event venue city
	$event_venue_city = ! empty( $_POST[ 'Event_Venue_City' ] ) ? sanitize_text_field( $_POST[ 'Event_Venue_City' ] ) : '';
	update_post_meta( $post_id, 'Event_Venue_City', $event_venue_city );

	// update the event link
	$event_link = ! empty( $_POST[ 'Event_Link' ] ) ? sanitize_text_field( $_POST[ 'Event_Link' ] ) : '';
	update_post_meta( $post_id, 'Event_Link', $event_link );

	// update the popup active
	// $popup_active = ! empty( $_POST[ 'dnxte_popup-active' ] ) ? sanitize_text_field( $_POST[ 'dnxte_popup-active' ] ) : '';
	// update_post_meta( $post_id, 'dnxte_popup-active', $popup_active );



}




add_action( 'admin_footer', 'customize_inline_edit_for_mroevent' );

function customize_inline_edit_for_mroevent() {
    ?>
    <script>
    jQuery(function($) {

        const wp_inline_edit_function = inlineEditPost.edit;

        // we overwrite it with our own
        inlineEditPost.edit = function(post_id) {

            // let's merge arguments of the original function
            wp_inline_edit_function.apply(this, arguments);

            // get the post ID from the argument
            if (typeof(post_id) == 'object') { // if it is object, get the ID number
                post_id = parseInt(this.getId(post_id));
            }

            // add rows to variables
            const edit_row = $('#edit-' + post_id);
            const post_row = $('#post-' + post_id);

            // Get the values of Event_Date and Event_Venue_City
            const eventDate = $('.column-Event_Date', post_row).text();
			const eventEndDate = $('.column-Event_End_Date', post_row).text();
            const eventVenueCity = $('.column-Event_Venue_City', post_row).text();
			const eventLink = $('.column-Event_Link', post_row).text();
			// const popupActive = $('.column-dnxte_popup-active', post_row).text();


            // populate the inputs with column data
            $(':input[name="Event_Date"]', edit_row).val(eventDate);
			$(':input[name="Event_End_Date"]', edit_row).val(eventEndDate);
            $(':input[name="Event_Venue_City"]', edit_row).val(eventVenueCity);
			$(':input[name="Event_Link"]', edit_row).val(eventLink);
			// $(':input[name="dnxte_popup-active"]', edit_row).val(popupActive);

        }
    });
    </script>
    <?php
}

// Remove the Category from the Quick Edit

add_filter( 'quick_edit_show_taxonomy', function( $show, $taxonomy_name, $view ) {

    if ( 'mroevent_category' == $taxonomy_name )
        return false;

    return $show;
}, 10, 3 );






// Add custom thumbnail column only for 'mroevent' custom post type
add_filter( 'manage_mroevent_posts_columns', 'mro_event_add_thumbnail_column' );
function mro_event_add_thumbnail_column( $columns ) {
    // Check if current post type is 'mroevent'
    if ( get_post_type() === 'mroevent' ) {
        $columns['featured_image'] = 'Featured Image';
    }
    return $columns;
}


// Display custom thumbnail column content
add_action( 'manage_mroevent_posts_custom_column', 'mro_event_post_thumbnail_column', 10, 2 );
function mro_event_post_thumbnail_column( $column_name, $post_id ) {
    if( 'featured_image' === $column_name ) {
        // if there is no featured image for this post, print the placeholder
        if( has_post_thumbnail( $post_id ) ) {
            // Get featured image ID
            $id = get_post_thumbnail_id( $post_id );
            // Get image URL
            $url = esc_url( wp_get_attachment_image_url( $id ) );
            // Output the image with data-id attribute
            ?><img data-id="<?php echo $id ?>" src="<?php echo $url ?>" /><?php
        } else {
            // Output placeholder image with data-id attribute as -1
            ?><img data-id="-1" src="placeholder-image.png" /><?php
        }
    }
}


// Media Uploding Script to the post page

add_action( 'admin_enqueue_scripts', 'mro_event_include_myuploadscript' );
function mro_event_include_myuploadscript() {
	if ( ! did_action( 'wp_enqueue_media' ) ) {
		wp_enqueue_media();
	}
}


add_action( 'quick_edit_custom_box',  'mro_events_featured_image_quick_edit', 10, 2 );
function mro_events_featured_image_quick_edit( $column_name, $post_type ) {

	// add it only if we have featured image column
	if( 'featured_image' !== $column_name ){
		return;
	}
	?>
		<fieldset id="misha_featured_image" class="inline-edit-col-left">
			<div class="inline-edit-col">
				<span class="title">Featured Image</span>
				<div>
					<a href="#" class="button mro-event-upload-img">Set featured image</a>
					<input type="hidden" name="_thumbnail_id" value="" />
				</div>
				<a href="#" class="mro-event-remove-img">Remove Featured Image</a>
			</div>
		</fieldset>
		<?php
}


add_action( 'admin_footer', 'mro_event_quick_edit_media_upload' );

function mro_event_quick_edit_media_upload() {
    ?>
    <script>
jQuery(function($){

	// add image
	$('body').on( 'click', '.mro-event-upload-img', function( event ) {
		event.preventDefault();

		const button = $(this);
		const customUploader = wp.media({
			title: 'Set featured image',
			library : { type : 'image' },
			button: { text: 'Set featured image' },
		}).on( 'select', () => {
			const attachment = customUploader.state().get('selection').first().toJSON();
			button.removeClass('button').html( '<img src="' + attachment.url + '" />').next().val(attachment.id).parent().next().show();
		}).open();

	});

	// remove image

	$('body').on('click', '.mro-event-remove-img', function( event ) {
		event.preventDefault();
		$(this).hide().prev().find( '[name="_thumbnail_id"]').val('-1').prev().html('Set featured Image').addClass('button' );
	});

	const $wp_inline_edit = inlineEditPost.edit;

	inlineEditPost.edit = function( id ) {
		$wp_inline_edit.apply( this, arguments );
		let postId = 0;
		if( typeof( id ) == 'object' ) {
			postId = parseInt( this.getId( id ) );
		}

		if ( postId > 0 ) {
			const editRow = $( '#edit-' + postId )
			const postRow = $( '#post-' + postId )
			const featuredImage = $( '.column-featured_image', postRow ).html()
			const featuredImageId = $( '.column-featured_image', postRow ).find('img').data('id')

			if( featuredImageId != -1 ) {

				$( ':input[name="_thumbnail_id"]', editRow ).val( featuredImageId ); // ID
				$( '.mro-event-upload-img', editRow ).html( featuredImage ).removeClass( 'button' ); // image HTML
				$( '.mro-event-remove-img', editRow ).show(); // the remove link

			}
		}
	}
});


</script>
    <?php
}





// Add excerpt field to quick edit

function add_excerpt_to_quick_edit($column_name, $post_type) {
    // Check if the column is 'post_excerpt' and the post type is 'post'
    if ($column_name === 'post_excerpt' && $post_type === 'mroevent') {
        // Get the current post ID
        $post_id = get_the_ID();
        // Get the excerpt for the post
        $excerpt = get_the_excerpt($post_id);
        // Output the excerpt field
        ?>
        <fieldset class="inline-edit-col-right">
            <div class="inline-edit-col">
                <label class="inline-edit-group">
                    <span class="title"><?php _e('Excerpt'); ?></span>
                    <textarea cols="20" rows="2" name="excerpt" class="pt-excerpt"><?php echo esc_textarea($excerpt); ?></textarea>
                </label>
            </div>
        </fieldset>
        <?php
    }
}
add_action('quick_edit_custom_box', 'add_excerpt_to_quick_edit', 10, 2);






// Filter the previous post link
add_filter( 'previous_post_link', 'custom_previous_post_link', 10, 5 );
function custom_previous_post_link( $output, $format, $link, $post, $adjacent ) {
	if ( 'mroevent' !== $post->post_type ) {
		return $output;
	}

	// Get the previous post based on Event_Date
	$previous_post = get_adjacent_post( false, '', $adjacent, '', 'meta_key=Event_Date' );

	// If there's a previous post
	if ( $previous_post ) {
		// Get the link to the previous post
		$previous_post_link = get_permalink( $previous_post->ID );
		// Modify the output format as needed
		$output = sprintf( $format, $link, $previous_post_link, $previous_post->post_title );
	}

	return $output;
}

add_filter( 'next_post_link', 'custom_next_post_link', 10, 5 );
function custom_next_post_link( $output, $format, $link, $post, $adjacent ) {
	if ( 'mroevent' !== $post->post_type ) {
		return $output;
	}

	// Get the next post based on custom meta field (e.g., 'Event_Date')
	$next_post = get_adjacent_post( false, '', $adjacent, '', 'meta_key=Event_Date' );

	// If there's a next post
	if ( $next_post ) {
		// Get the link to the next post
		$next_post_link = get_permalink( $next_post->ID );
		// Modify the output format as needed
		$output = sprintf( $format, $link, $next_post_link, $next_post->post_title );
	}

	return $output;
}
