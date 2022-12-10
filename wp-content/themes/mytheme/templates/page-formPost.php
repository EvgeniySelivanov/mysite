<?php
/**
 * Template Name: Form post
 * Template Post Type: page,post,
 */ ?>
 <?php get_header(); ?>
 <?php   if (current_user_can( 'subscriber')||current_user_can( 'administrator')){?>
 <div class="container my-5 "><?php if (function_exists('user_submitted_posts')) user_submitted_posts(); ?></div>
 <?php } 
 else{
  echo ('<div class="makeReg">
          <h2>This content is only available to registered users</h2>
          <div><a class="nav-link" href='.wp_registration_url().'>Registration please</a></div>
  </div>');
 }
 ?>
 <?php get_footer(); ?>
