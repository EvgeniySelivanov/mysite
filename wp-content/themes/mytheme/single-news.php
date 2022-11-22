<!-- шаблон карточки поста (когда вошел в пост)-->
<?php get_header();?>
<div class="container .myPostCard">
  <h1 class="my-4 page-title"><?php wp_title();?></h1>
  <p><small class="text-muted"><?php the_time('j F Y');?> <?php the_tags('');?></small></p>
  <a href="<?php the_permalink(); ?>">
        <img class="card-img-top" src="<?php echo get_the_post_thumbnail_url(null, 'post-thumbnail'); ?>"
        alt="<?php the_title(); ?>"></a>
  <?php the_content('more...');?>
  <?php comments_template(); ?>
</div>
<?php get_footer();?>



