<?php

// -- how many without
// select count(ID) from wp_posts where post_type='post' and ID not in (select post_id from wp_postmeta where meta_key = '_thumbnail_id');

// -- how many with
// select count(post_id) from wp_postmeta where meta_key = '_thumbnail_id'
if (!defined('ABSPATH')) exit; // Exit if accessed directly

function borncreative_featured_image_setter_count_all_posts_without_featured_image_set($category_id = 0)
{
	$args = array(
		'post_type'  => 'post',
		'fields'     => 'ids',
		'meta_query' => array(
			array(
				'key' => '_thumbnail_id',
				'compare' => 'NOT EXISTS'
			),
		)
	);
	if ($category_id) {
		$args['cat'] = absint($category_id);
	}
	$query = new WP_Query($args);
	return $query->found_posts;
}

function borncreative_featured_image_setter_count_all_posts_with_featured_image_set()
{
	$args = array(
		'post_type'  => 'post',
		'meta_query' => array(
			array(
				'key' => '_thumbnail_id',
				'compare' => 'EXISTS'
			),
		)
	);
	$query = new WP_Query($args);
	return $query->found_posts;
}


function borncreative_featured_image_setter_set_all_posts_without_featured_image_set($image_id, $category_id = 0)
{
	$args = array(
		'post_type'  => 'post',
		'fields'     => 'ids',
		'meta_query' => array(
			array(
				'key' => '_thumbnail_id',
				'compare' => 'NOT EXISTS'
			),
		)
	);
	if ($category_id) {
		$args['cat'] = absint($category_id);
	}
	$query = new WP_Query($args);

	$updated = 0;
	if ($query->have_posts()) {
		foreach ($query->posts as $post_id) {
			set_post_thumbnail($post_id, $image_id);
			$updated++;
		}
	}
	return $updated;
}

/**
 * Get a per-category breakdown of posts which are missing a featured image.
 *
 * Only categories that actually have posts missing a featured image are
 * returned, ordered by the number of posts missing an image (highest first).
 *
 * @return array[] Array of arrays with keys: term_id, name, count
 */
function borncreative_featured_image_setter_get_category_breakdown_without_featured_image()
{
	$categories = get_categories(array('hide_empty' => false));
	$breakdown = array();

	foreach ($categories as $category) {
		$count = borncreative_featured_image_setter_count_all_posts_without_featured_image_set($category->term_id);
		if ($count > 0) {
			$breakdown[] = array(
				'term_id' => $category->term_id,
				'name'    => $category->name,
				'count'   => $count,
			);
		}
	}

	usort($breakdown, function ($a, $b) {
		return $b['count'] - $a['count'];
	});

	return $breakdown;
}

/**
 * Find featured images which are used by more than one post.
 *
 * @return array[] Array of arrays with keys: image_id, post_count, thumb_url, title
 */
function borncreative_featured_image_setter_get_duplicate_featured_images()
{
	global $wpdb;

	$results = $wpdb->get_results(
		"SELECT pm.meta_value AS image_id, COUNT(pm.post_id) AS post_count
		 FROM {$wpdb->postmeta} pm
		 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
		 WHERE pm.meta_key = '_thumbnail_id'
		 AND p.post_type = 'post'
		 GROUP BY pm.meta_value
		 HAVING post_count > 1
		 ORDER BY post_count DESC"
	);

	$images = array();
	if (!empty($results)) {
		foreach ($results as $row) {
			$image_id = absint($row->image_id);
			if (!$image_id) {
				continue;
			}
			$images[] = array(
				'image_id'   => $image_id,
				'post_count' => absint($row->post_count),
				'thumb_url'  => wp_get_attachment_image_url($image_id, 'thumbnail'),
				'title'      => get_the_title($image_id),
			);
		}
	}

	return $images;
}

/**
 * Get a per-category breakdown of posts using a particular image as their
 * featured image.
 *
 * @param int $image_id Attachment ID of the featured image.
 * @return array[] Array of arrays with keys: term_id, name, count
 */
function borncreative_featured_image_setter_get_image_category_breakdown($image_id)
{
	global $wpdb;
	$image_id = absint($image_id);

	$post_ids = $wpdb->get_col($wpdb->prepare(
		"SELECT pm.post_id
		 FROM {$wpdb->postmeta} pm
		 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
		 WHERE pm.meta_key = '_thumbnail_id' AND pm.meta_value = %d
		 AND p.post_type = 'post'",
		$image_id
	));

	$breakdown = array();

	foreach ($post_ids as $post_id) {
		$categories = get_the_category($post_id);
		if (empty($categories)) {
			continue;
		}
		foreach ($categories as $category) {
			if (!isset($breakdown[$category->term_id])) {
				$breakdown[$category->term_id] = array(
					'term_id' => $category->term_id,
					'name'    => $category->name,
					'count'   => 0,
				);
			}
			$breakdown[$category->term_id]['count']++;
		}
	}

	$breakdown = array_values($breakdown);

	usort($breakdown, function ($a, $b) {
		return $b['count'] - $a['count'];
	});

	return $breakdown;
}

/**
 * Replace one featured image with another, optionally scoped to a single category.
 *
 * @param int $old_image_id Attachment ID currently set as the featured image.
 * @param int $new_image_id Attachment ID to replace it with.
 * @param int $category_id  Optional. Restrict the replacement to posts in this category.
 * @return int Number of posts updated.
 */
function borncreative_featured_image_setter_replace_image_for_category($old_image_id, $new_image_id, $category_id = 0)
{
	global $wpdb;
	$old_image_id = absint($old_image_id);
	$new_image_id = absint($new_image_id);
	$category_id  = absint($category_id);

	if (!$old_image_id || !$new_image_id) {
		return 0;
	}

	$post_ids = $wpdb->get_col($wpdb->prepare(
		"SELECT pm.post_id
		 FROM {$wpdb->postmeta} pm
		 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
		 WHERE pm.meta_key = '_thumbnail_id' AND pm.meta_value = %d
		 AND p.post_type = 'post'",
		$old_image_id
	));

	$updated = 0;
	foreach ($post_ids as $post_id) {
		if ($category_id && !has_category($category_id, $post_id)) {
			continue;
		}
		set_post_thumbnail($post_id, $new_image_id);
		$updated++;
	}

	return $updated;
}


function borncreative_featured_image_setter_show_media_library()
{
	// jQuery
	wp_enqueue_script('jquery');
	// This will enqueue the Media Uploader script
	wp_enqueue_media();

	$categories = borncreative_featured_image_setter_get_category_breakdown_without_featured_image();
?>
	<div>
		<form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" id="born-creative-featured-image-setter-form" class="bcfis-form">
			<input type="hidden" name="action" value="borncreative_featured_image_setter_form_response">
			<?php
			wp_nonce_field('born-creative-featured-image-setter-form-nonce');
			?>
			<p>
				<label for="bcfis-category-select"><?php esc_html_e('Category:', 'born-creative-featured-image-setter'); ?></label>
				<select name="category_id" id="bcfis-category-select">
					<option value="0"><?php esc_html_e('All Categories', 'born-creative-featured-image-setter'); ?></option>
					<?php foreach ($categories as $category) : ?>
						<option value="<?php echo esc_attr($category['term_id']); ?>">
							<?php
							echo esc_html(
								sprintf(
									/* translators: 1: category name, 2: number of posts missing a featured image */
									__('%1$s (%2$d)', 'born-creative-featured-image-setter'),
									$category['name'],
									$category['count']
								)
							);
							?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>
			<input type="hidden" name="image_id" class="bcfis-image-id-input regular-text">
			<input type="button" name="upload-btn" class="button-secondary bcfis-select-image-btn" value="Select Image">
			<input type="submit" name="apply-btn" class="button-primary bcfis-apply-btn" value="Apply" style="display:none;">
		</form>
	</div>
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			// Wire up every "select image" button on the page (there may be
			// several - one for the main form and one per duplicate image
			// category replace form) using event delegation so it also
			// works for forms rendered inside <details> elements.
			$(document).on('click', '.bcfis-select-image-btn', function(e) {
				e.preventDefault();
				var $btn = $(this);
				var $form = $btn.closest('form');
				var $input = $form.find('.bcfis-image-id-input');
				var $apply = $form.find('.bcfis-apply-btn');

				var image = wp.media({
						title: 'Select Image',
						multiple: false
					}).open()
					.on('select', function() {
						var uploaded_image = image.state().get('selection').first();
						var image_id = uploaded_image.toJSON().id;
						$input.val(image_id);
						if ($input.val().length > 0) {
							$apply.show();
						}
					});
			});

			$('.bcfis-form').each(function() {
				var $form = $(this);
				var $input = $form.find('.bcfis-image-id-input');
				var $apply = $form.find('.bcfis-apply-btn');
				if ($input.val() && $input.val().length > 0) {
					$apply.show();
				} else {
					$apply.hide();
				}
			});
		});
	</script>
<?php
}

/**
 * Render the "Duplicate Featured Images" section - a table of images used
 * as the featured image on more than one post, expandable to show a
 * per-category breakdown with a "Replace" action for each category.
 */
function borncreative_featured_image_setter_show_duplicate_images()
{
	wp_enqueue_media();

	$images = borncreative_featured_image_setter_get_duplicate_featured_images();

	if (empty($images)) {
		echo '<p>' . esc_html__('No duplicate featured images found - every featured image is only used once.', 'born-creative-featured-image-setter') . '</p>';
		return;
	}
?>
	<table class="widefat striped bcfis-duplicates-table">
		<thead>
			<tr>
				<th><?php esc_html_e('Image', 'born-creative-featured-image-setter'); ?></th>
				<th><?php esc_html_e('Title', 'born-creative-featured-image-setter'); ?></th>
				<th><?php esc_html_e('Posts Using This Image', 'born-creative-featured-image-setter'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($images as $image) : ?>
				<tr>
					<td colspan="3" style="padding:0;">
						<details>
							<summary style="padding:8px; cursor:pointer;">
								<span style="display:inline-flex; align-items:center; gap:10px;">
									<?php if ($image['thumb_url']) : ?>
										<img src="<?php echo esc_url($image['thumb_url']); ?>" alt="" style="width:50px; height:50px; object-fit:cover;">
									<?php else : ?>
										<span style="display:inline-block; width:50px; height:50px; background:#eee;"></span>
									<?php endif; ?>
									<strong><?php echo esc_html($image['title'] ? $image['title'] : '#' . $image['image_id']); ?></strong>
									&mdash;
									<?php
									echo esc_html(
										sprintf(
											/* translators: %d: number of posts */
											_n('%d post', '%d posts', $image['post_count'], 'born-creative-featured-image-setter'),
											$image['post_count']
										)
									);
									?>
								</span>
							</summary>
							<div style="padding:10px 10px 20px 60px;">
								<?php borncreative_featured_image_setter_show_image_category_breakdown($image['image_id']); ?>
							</div>
						</details>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php
}

/**
 * Render the per-category breakdown table (with Replace forms) for a single
 * duplicated featured image.
 *
 * @param int $image_id Attachment ID of the featured image.
 */
function borncreative_featured_image_setter_show_image_category_breakdown($image_id)
{
	$image_id = absint($image_id);
	$breakdown = borncreative_featured_image_setter_get_image_category_breakdown($image_id);

	if (empty($breakdown)) {
		echo '<p>' . esc_html__('No category breakdown available for this image.', 'born-creative-featured-image-setter') . '</p>';
		return;
	}
	?>
	<table class="widefat">
		<thead>
			<tr>
				<th><?php esc_html_e('Category', 'born-creative-featured-image-setter'); ?></th>
				<th><?php esc_html_e('Posts', 'born-creative-featured-image-setter'); ?></th>
				<th><?php esc_html_e('Replace With', 'born-creative-featured-image-setter'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($breakdown as $row) : ?>
				<tr>
					<td><?php echo esc_html($row['name']); ?></td>
					<td><?php echo esc_html($row['count']); ?></td>
					<td>
						<form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" class="bcfis-form" style="display:flex; align-items:center; gap:8px;">
							<input type="hidden" name="action" value="borncreative_featured_image_setter_replace_form_response">
							<?php wp_nonce_field('born-creative-featured-image-setter-replace-form-nonce'); ?>
							<input type="hidden" name="old_image_id" value="<?php echo esc_attr($image_id); ?>">
							<input type="hidden" name="category_id" value="<?php echo esc_attr($row['term_id']); ?>">
							<input type="hidden" name="image_id" class="bcfis-image-id-input">
							<input type="button" name="upload-btn" class="button-secondary bcfis-select-image-btn" value="<?php esc_attr_e('Select Image', 'born-creative-featured-image-setter'); ?>">
							<input type="submit" name="apply-btn" class="button-primary bcfis-apply-btn" value="<?php esc_attr_e('Replace', 'born-creative-featured-image-setter'); ?>" style="display:none;">
						</form>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php
}
