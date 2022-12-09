<?php 
/**
 * Template Name: Portfolio page
 * Template Post Type: page
 */?>
<?php get_header();?>
<?php
//количество постов этого типа(сущности)
  $numberWork=wp_count_posts()->publish;
  $current_page = (get_query_var('paged')) ? get_query_var('paged') : 1;
  $params = array(
    'posts_per_page' => 3, // количество постов на странице
    'post_type'       => 'project_list', // тип постов
    'paged'           => $current_page,// текущая страница
    'orderby' => 'modified', 
   
  );
  // query_posts() Определяет какие посты будут показаны в базовом Цикле WordPress. Создает базовый Цикл WordPress. Возвращает список записей (постов).
  query_posts($params); 
  $wp_query->is_archive = true;
  $wp_query->is_home = false;

 //var_dump($posts); 

?>
<div class="container"> 
  <div><strong>общее количество работ:<?php echo $numberWork;?></strong> </div> 
  <table class="table">
  <thead>
      <tr>
        <th scope="col">Date</th>
        <th scope="col">Name</th>
        <th scope="col">Framework</th>
        <th scope="col">Description</th>
      </tr>
    </thead>
    <tbody>
  <?php

  while (have_posts()) : the_post();
  ?>
      <tr>
        <!--Вывод даты с помощь функции get_field| date это поле из ACF -->
        <th scope="row"><?php echo get_field('date')?></th>
        <td><a href="<?php echo get_field('address')?>"><?php echo get_field('address')?></a></td>
        <td><?php echo get_field('tehnology_type')?></td>
        <td><?php the_content();?></td>
      </tr>
     <?php endwhile;?>
    </tbody>
  </table>
  <div class="d-flex justify-content-center mt-2">
      <?php do_action('myPostPagination');?>
</div>
</div>


<?php get_footer();?>
