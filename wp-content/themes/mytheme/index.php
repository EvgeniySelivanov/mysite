<?php get_header(); ?>
<main role="main" class="container ">
  <div class="row">
    <div class="col-lg-8">
      <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
          <h2 class="card-title">
            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
          <div class="card mb-4">
            <a href="<?php the_permalink(); ?>">
              <div class="myPostIcon"><img class="card-img-top" src="<?php echo get_the_post_thumbnail_url(null, 'post-thumbnail'); ?>" alt="<?php the_title(); ?>"></div></a>
            <div class="card-body">
              <p class="card-text"><small class="text-muted"><?php the_time('j F Y'); ?></small>
                <?php the_tags(''); ?></p>
              <?php the_content('more...'); ?>
              <a href="<?php the_permalink(); ?>" class="btn btn-primary shadow-none">Читать далее →</a>
            </div>
          </div>
        <?php endwhile;
      else : ?>
        <p>Tanechka</p>
      <?php endif; ?>
      <?php the_posts_pagination(array(
        'mid_size' => 2,
        'end_size' => 2,
      )); ?>

    </div>
</main>


<?php get_footer(); ?>
