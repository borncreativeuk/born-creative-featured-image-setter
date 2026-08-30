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
	if (!current_user_can('edit_others_posts')) {
		wp_die(
			__('You do not have permission to do this.', 'born-creative-featured-image-setter'),
			__('Error', 'born-creative-featured-image-setter'),
			array(
				'response'  => 403,
				'back_link' => $url,
			)
		);
	}
	if (!empty($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'born-creative-featured-image-setter-form-nonce')) {
		// sanitize the input
		$image_id    = absint($_REQUEST['image_id']);
		$category_id = !empty($_REQUEST['category_id']) ? absint($_REQUEST['category_id']) : 0;
		// do the processing
		$updated = borncreative_featured_image_setter_set_all_posts_without_featured_image_set($image_id, $category_id);
		// redirect the user to the appropriate page
		wp_safe_redirect(add_query_arg(array('bcfis-updated' => $updated), $url));
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

add_action('admin_post_borncreative_featured_image_setter_replace_form_response', 'borncreative_featured_image_setter_admin_replace_save_stuff');
function borncreative_featured_image_setter_admin_replace_save_stuff()
{
	$url = admin_url('admin.php?page=set-featured-images');
	if (!current_user_can('edit_others_posts')) {
		wp_die(
			__('You do not have permission to do this.', 'born-creative-featured-image-setter'),
			__('Error', 'born-creative-featured-image-setter'),
			array(
				'response'  => 403,
				'back_link' => $url,
			)
		);
	}
	if (!empty($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'born-creative-featured-image-setter-replace-form-nonce')) {
		// sanitize the input
		$old_image_id = absint($_REQUEST['old_image_id']);
		$new_image_id = absint($_REQUEST['image_id']);
		$category_id  = !empty($_REQUEST['category_id']) ? absint($_REQUEST['category_id']) : 0;
		// do the processing
		$updated = borncreative_featured_image_setter_replace_image_for_category($old_image_id, $new_image_id, $category_id);
		// redirect the user to the appropriate page
		wp_safe_redirect(add_query_arg(array('bcfis-replaced' => $updated), $url));
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
