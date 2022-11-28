<?php // User Submitted Posts - Template Tags

if (!defined('ABSPATH')) die();



/* 
	Returns a boolean value indicating whether the specified post is a public submission
	Usage: <?php if (function_exists('usp_is_public_submission')) usp_is_public_submission(); ?>
*/
function usp_is_public_submission($postId = false) {
	
	global $post;
	
	if (false === $postId) {
		
		if ($post) $postId = $post->ID;
		
	}
	
	if (get_post_meta($postId, 'is_submission', true) == true) {
		
		return true;
		
	}
	
	return false;
	
}



/* 
	Returns an array of URLs for the specified post image
	Usage: <?php $images = usp_get_post_images(); foreach ($images as $image) { echo $image; } ?>
*/
function usp_get_post_images($postId = false) {
	
	global $post;
	
	if (false === $postId) {
		
		if ($post) $postId = $post->ID;
		
	}
	
	if (usp_is_public_submission($postId)) {
		
		return get_post_meta($postId, 'user_submit_image');
		
	}
	
	return array();
	
}



/*
	Prints the URLs for all post attachments.
	Usage:  <?php if (function_exists('usp_post_attachments')) usp_post_attachments(); ?>
	Syntax: <?php if (function_exists('usp_post_attachments')) usp_post_attachments($size, $beforeUrl, $afterUrl, $numberImages, $postId); ?>
	Parameters:
		$size         = image size as thumbnail, medium, large or full -> default = full
		$beforeUrl    = text/markup displayed before the image URL     -> default = <img src="
		$afterUrl     = text/markup displayed after the image URL      -> default = " />
		$numberImages = the number of images to display for each post  -> default = false (display all)
		$postId       = an optional post ID to use                     -> default = uses global post
*/
function usp_post_attachments($size = 'full', $beforeUrl = '<img src="', $afterUrl = '" />', $numberImages = false, $postId = false) {
	
	global $post;
	
	if (false === $postId) {
		
		if ($post) $postId = $post->ID;
		
	}
	
	if (false === $numberImages || !is_numeric($numberImages)) {
		
		$numberImages = 99;
		
	}
	
	$args = array(
		'post_type'   => 'attachment', 
		'post_parent' => $postId, 
		'post_status' => 'inherit', 
		'numberposts' => $numberImages
	);
	
	$attachments = get_posts($args);
	
	foreach ($attachments as $attachment) {
		
		$info = wp_get_attachment_image_src($attachment->ID, $size);

		echo $beforeUrl . $info[0] . $afterUrl;
		
	}
	
}



/*
	For public-submitted posts, this tag displays the author's name as a link (if URL provided) or plain text (if URL not provided)
	For normal posts, this tag displays the author's name as a link to their author's post page
	Usage: <?php if (function_exists('usp_author_link')) usp_author_link(); ?>
*/
function usp_author_link() {
	
	global $post;

	$isSubmission     = get_post_meta($post->ID, 'is_submission', true);
	$submissionAuthor = get_post_meta($post->ID, 'user_submit_name', true);
	$submissionLink   = get_post_meta($post->ID, 'user_submit_url', true);

	if ($isSubmission && !empty($submissionAuthor)) {
		
		if (empty($submissionLink)) {
			
			echo '<span class="usp-author-link">' . $submissionAuthor . '</span>';
			
		} else {
			
			echo '<span class="usp-author-link"><a href="' . $submissionLink . '">' . $submissionAuthor . '</a></span>';
			
		}
		
	} else {
		
		the_author_posts_link();
		
	}
	
}



/*
	Function: usp_get_images()
	Returns an array of image URLs, wrapped in optional HTML
	Syntax: <?php if (function_exists('usp_get_images')) $images = usp_get_images($size, $before, $after, $number, $postId); ?>
	Usage:  <?php if (function_exists('usp_get_images')) $images = usp_get_images(); foreach ($images as $image) echo $image; ?>
	
	Parameters:
		$size   = image size as thumbnail, medium, large or full -> default = thumbnail
		$before = text/markup displayed before the image URL     -> default = {img src="
		$after  = text/markup displayed after the image URL      -> default = " /}
		$number = the number of images to display for each post  -> default = false (display all)
		$postId = an optional post ID to use                     -> default = false (uses global/current post)
		
	Notes:
		For $before/$after parameters, use curly brackets instead of angle brackets, for example:
		usp_get_images('thumbnail', '{img src="', '" /}'); // results in each image URL wrapped like: <img src="[image URL]" />
		
		For $before/$after parameters, use %%url%% to get the URL of the full-size image, for example:
		usp_get_images('thumbnail', '{a href="%%url%%"}{img src="', '" /}{/a}'); // outputs for each image: <a href="[full-size image URL]"><img src="[image URL]" /></a>
*/
if (!function_exists('usp_get_images')) :

function usp_get_images($size = false, $before = false, $after = false, $number = false, $postId = false) {
	
	global $post;
	
	if (false === $postId || !is_numeric($postId)) $postId = $post->ID;
	if (false === $number || !is_numeric($number)) $number = apply_filters('usp_image_attachments', 100);
	if (false === $size)                           $size   = 'thumbnail';
	if (false === $before)                         $before = '{img src="';
	if (false === $after)                          $after  = '" /}';
	
	$args = compact('before', 'after');
	
	$new = array();
	
	foreach ($args as $key => $value) {
		
		$value = str_replace("{", "<", $value);
		$value = str_replace("}", ">", $value);
		
		if (isset($value)) $new[$key] = $value;
		
	}
	
	$args = array(
			'post_status'    => 'publish', 
			'post_type'      => 'attachment', 
			'post_parent'    => $postId, 
			'post_status'    => 'inherit', 
			'posts_per_page' => $number,
			'fields'         => 'ids'
	);
	
	$args = apply_filters('usp_image_attachments_args', $args);
	
	$image_ids = get_posts($args);
	
	$urls = array(); $i = 1;
	
	foreach ($image_ids as $id) {
		
		$url = wp_get_attachment_image_src($id, $size);
		
		$original = wp_get_attachment_image_src($id, 'full');
		
		if ($url !== false && $original !== false) {
			
			$before = isset($new['before']) ? $new['before'] : '';
			$after  = isset($new['after'])  ? $new['after']  : '';
			
			$before = str_replace("%%url%%", $original[0], $before);
			$after  = str_replace("%%url%%", $original[0], $after);
			
			$urls[] = isset($url[0]) ? $before . $url[0] . $after : '';
			
			if ($i == intval($number)) break;
			
			$i++;
			
		}
		
	}
	
	return $urls;
	
}

endif;
