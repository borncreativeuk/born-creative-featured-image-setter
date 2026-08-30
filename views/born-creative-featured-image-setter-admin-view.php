<?php

// -- how many without
// select count(ID) from wp_posts where post_type='post' and ID not in (select post_id from wp_postmeta where meta_key = '_thumbnail_id');

// -- how many with
// select count(post_id) from wp_postmeta where meta_key = '_thumbnail_id'
if (!defined('ABSPATH')) exit; // Exit if accessed directly
?>

<div class="wrap">

	<h1><?php esc_html_e('born-creative featured image setter.', 'born-creative-featured-image-setter'); ?></h1>
	<p><a href="https://www.born-creative.co.uk">born-creative.co.uk</a></p>

	<?php if (isset($_GET['bcfis-updated'])) : ?>
		<div class="notice notice-success is-dismissible">
			<p>
				<?php
				echo esc_html(
					sprintf(
						/* translators: %d: number of posts updated */
						_n('%d post was updated with the selected featured image.', '%d posts were updated with the selected featured image.', absint($_GET['bcfis-updated']), 'born-creative-featured-image-setter'),
						absint($_GET['bcfis-updated'])
					)
				);
				?>
			</p>
		</div>
	<?php endif; ?>

	<?php if (isset($_GET['bcfis-replaced'])) : ?>
		<div class="notice notice-success is-dismissible">
			<p>
				<?php
				echo esc_html(
					sprintf(
						/* translators: %d: number of posts updated */
						_n('%d post had its featured image replaced.', '%d posts had their featured image replaced.', absint($_GET['bcfis-replaced']), 'born-creative-featured-image-setter'),
						absint($_GET['bcfis-replaced'])
					)
				);
				?>
			</p>
		</div>
	<?php endif; ?>

	<h2><?php esc_html_e('Posts Without Featured Image Set', 'born-creative-featured-image-setter'); ?></h2>
	<?php
	echo esc_html(borncreative_featured_image_setter_count_all_posts_without_featured_image_set());
	?>
	<p><?php esc_html_e('To set an image for the above posts, optionally choose a single category to limit the update to, select an image, and click Apply.', 'born-creative-featured-image-setter'); ?></p>
	<?php
	borncreative_featured_image_setter_show_media_library();
	?>

	<h3><?php esc_html_e('Category Breakdown', 'born-creative-featured-image-setter'); ?></h3>
	<?php
	$bcfis_category_breakdown = borncreative_featured_image_setter_get_category_breakdown_without_featured_image();
	if (empty($bcfis_category_breakdown)) :
	?>
		<p><?php esc_html_e('Every category currently has a featured image set on all of its posts.', 'born-creative-featured-image-setter'); ?></p>
	<?php else : ?>
		<table class="widefat striped" style="max-width:600px;">
			<thead>
				<tr>
					<th><?php esc_html_e('Category', 'born-creative-featured-image-setter'); ?></th>
					<th><?php esc_html_e('Posts Without Featured Image', 'born-creative-featured-image-setter'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($bcfis_category_breakdown as $bcfis_category_row) : ?>
					<tr>
						<td><?php echo esc_html($bcfis_category_row['name']); ?></td>
						<td><?php echo esc_html($bcfis_category_row['count']); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>

	<h2><?php esc_html_e('Posts With Featured Image Set', 'born-creative-featured-image-setter'); ?></h2>
	<?php
	echo esc_html(borncreative_featured_image_setter_count_all_posts_with_featured_image_set());
	?>

	<h2><?php esc_html_e('Duplicate Featured Images', 'born-creative-featured-image-setter'); ?></h2>
	<p><?php esc_html_e('Featured images currently used on more than one post. Click a row to see a per-category breakdown and replace the image for a single category at a time.', 'born-creative-featured-image-setter'); ?></p>
	<?php
	borncreative_featured_image_setter_show_duplicate_images();
	?>

</div>
