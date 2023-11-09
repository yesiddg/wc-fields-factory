<?php 

global $post;
$meta_list = get_post_meta($post->ID);


?>

<div class="wcff-meta-list">

<pre><?php echo esc_html(json_encode($meta_list, JSON_PRETTY_PRINT)); ?></pre>

</div>