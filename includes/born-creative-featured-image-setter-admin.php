<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly   

add_action('admin_menu', 'borncreative_featured_image_setter_admin_stuff');
function borncreative_featured_image_setter_admin_stuff()
{

	// Create a top-level menu if it doesnt already exist
	if (!menu_page_url('born-creative', false)) {
		add_menu_page(
			'Born Creative',
			'Born Creative',
			'edit_others_posts',
			'born-creative',
			'borncreative_admin_view',
			'dashicons-admin-generic',
			2
		);
	}

	// Create a sub-menu under the top-level menu
	add_submenu_page(
		'born-creative',
		'Set Featured Images',
		'Set Featured Images',
		'edit_others_posts',
		'set-featured-images',
		'borncreative_featured_image_setter_admin_view'
	);
}

function borncreative_featured_image_setter_admin_view()
{
	// include admin view
	if (file_exists(plugin_dir_path(__FILE__) . '../views/born-creative-featured-image-setter-admin-view.php')) {
		include_once plugin_dir_path(__FILE__) . '../views/born-creative-featured-image-setter-admin-view.php';
	}
}

// create function if its not already defined (admin view function can appear from other plugins)
if (!function_exists('borncreative_admin_view')) {
	function borncreative_admin_view()
	{
		// include admin view
		if (file_exists(plugin_dir_path(__FILE__) . '../views/born-creative-view.php')) {
			require_once plugin_dir_path(__FILE__) . '../views/born-creative-view.php';
		}
	}
}

add_action('admin_post_borncreative_featured_image_setter_form_response', 'borncreative_featured_image_setter_admin_save_stuff');
function borncreative_featured_image_setter_admin_save_stuff()
{
	$url = admin_url('admin.php?page=set-featured-images');
	if (!empty($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'born-creative-featured-image-setter-form-nonce')) {
		// sanitize the input
		$image_id = absint($_REQUEST['image_id']);
		// do the processing
		borncreative_featured_image_setter_set_all_posts_without_featured_image_set($image_id);
		// redirect the user to the appropriate page
		wp_safe_redirect($url);
		exit();
	} else {
		wp_die(
			__('Invalid nonce specified', 'born-creative-featured-image-setter'),
			__('Error', 'born-creative-featured-image-setter'),
			array(
				'response'     => 403,
				'back_link' => $url,
			)
		);
	}
}
