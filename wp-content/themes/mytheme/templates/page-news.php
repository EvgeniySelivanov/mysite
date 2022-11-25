<?php
/**
 * Template Name: News page
 * Template Post Type: page,post,
 */ ?>
<!-- шаблон страницы с постами -->
<?php get_header(); ?>
<!-- ограничил доступ к странице если роль пользователя подписчик или администратор отображай страницу-->
<?php   if (current_user_can( 'subscriber')||current_user_can( 'administrator')){?>

<div class="pageNewsMain content">
  <?php $current_page = (get_query_var('paged')) ? get_query_var('paged') : 1;
  $params = array(
    'posts_per_page' => 2, // количество постов на странице
    'post_type'       => 'post', // тип постов
    'paged'           => $current_page, // текущая страница
    'cat' => '3' // ID категории постов которые выводить на этой странице
  );
  query_posts($params); ?>
  <div class="container">
    <?php $wp_query->is_archive = true;
    $wp_query->is_home = false; ?>

    <?php while (have_posts()) : the_post(); ?>

      <!-- в тело цикла вставьте HTML одного анонса записи, например: -->
      <!-- так выглядит пост на странице -->

      <div class="mt-3 mb-3 p-3 myPost">
        <h2 class="customerPostText"><?php the_title() ?></h2>
        <p class="card-text"><small class="text-white"><?php the_time('j F Y'); ?></small>
        <p class="customerPostText"><?php the_content() ?></p>
        <a href="<?php the_permalink(); ?>" class="btn btn-outline-dark shadow-none">Read more →</a>
      </div>
    <?php endwhile; ?>
<?php }
//если роль пользователя не совпадает выводи следующие
else{
  echo ('<div class="makeReg">
          <h2>This content is only available to registered users</h2>
          <div><a class="nav-link" href='.wp_registration_url().'>Registration please</a></div>
  </div>');
  
}


?>
</div>
    <?php get_footer(); ?>