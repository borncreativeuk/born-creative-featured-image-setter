<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly   

function borncreative_featured_image_setter_count_all_posts_without_featured_image_set()
{
	$args = array(
		'post_type'  => 'post',
		'meta_query' => array(
			array(
				'key' => '_thumbnail_id',
				'compare' => 'NOT EXISTS'
			),
		)
	);
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


function borncreative_featured_image_setter_set_all_posts_without_featured_image_set($image_id)
{
	$args = array(
		'post_type'  => 'post',
		'meta_query' => array(
			array(
				'key' => '_thumbnail_id',
				'compare' => 'NOT EXISTS'
			),
		)
	);
	$query = new WP_Query($args);
	// loop and set
	if ($query->have_posts()) {
		while ($query->have_posts()) {
			$query->the_post();
			set_post_thumbnail($query->post->ID, $image_id);
		}
	}
	/* Restore original Post Data */
	wp_reset_postdata();
}



function borncreative_featured_image_setter_show_media_library()
{
	// jQuery
	wp_enqueue_script('jquery');
	// This will enqueue the Media Uploader script
	wp_enqueue_media();
?>
	<div>


		<form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" id="born-creative-featured-image-setter-form">
			<input type="hidden" name="action" value="borncreative_featured_image_setter_form_response">
			<?php
			wp_nonce_field('born-creative-featured-image-setter-form-nonce');
			?>
			<input type="hidden" name="image_id" id="image_id" class="regular-text">
			<input type="button" name="upload-btn" id="upload-btn" class="button-secondary" value="Select Image">
			<input type="submit" name="apply-btn" id="apply-btn" class="button-primary" value="Apply" style="display:none;">
		</form>
	</div>
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			if ($('#image_id').val().length > 0) {
				$('#apply-btn').show()
			} else {
				$('#apply-btn').hide()
			}
			$('#upload-btn').click(function(e) {
				e.preventDefault();
				var image = wp.media({
						title: 'Upload Image',
						// mutiple: true if you want to upload multiple files at once
						multiple: false
					}).open()
					.on('select', function(e) {
						// This will return the selected image from the Media Uploader, the result is an object
						var uploaded_image = image.state().get('selection').first();
						// We convert uploaded_image to a JSON object to make accessing it easier
						// Output to the console uploaded_image
						console.log(uploaded_image);
						var image_id = uploaded_image.toJSON().id;
						// Let's assign the url value to the input field
						$('#image_id').val(image_id);
						if ($('#image_id').val().length > 0) {
							$('#apply-btn').show()
						}
					});
			});
		});
	</script>
<?php
}
