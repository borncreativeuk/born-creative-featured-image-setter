<?php

// -- how many without
// select count(ID) from wp_posts where post_type='post' and ID not in (select post_id from wp_postmeta where meta_key = '_thumbnail_id');

// -- how many with
// select count(post_id) from wp_postmeta where meta_key = '_thumbnail_id'
if (!defined('ABSPATH')) exit; // Exit if accessed directly   
?>

<h1><?php esc_html_e('born-creative featured image setter.', 'born-creative-featured-image-setter'); ?></h1>
<p><a href="https://www.born-creative.co.uk">born-creative.co.uk</a></p>
<h2><?php esc_html_e('Posts Without Featured Image Set', 'born-creative-featured-image-setter'); ?></h2>
<?php
echo borncreative_featured_image_setter_count_all_posts_without_featured_image_set();
?>
<p><?php esc_html_e('To set an image for the above posts, please select an image and click Apply.', 'born-creative-featured-image-setter'); ?></p>
<?php
borncreative_featured_image_setter_show_media_library();
?>
<h2><?php esc_html_e('Posts With Featured Image Set', 'born-creative-featured-image-setter'); ?></h2>
<?php
echo borncreative_featured_image_setter_count_all_posts_with_featured_image_set();
