<?php 
/**
 * Template Name: Portfolio page
 * Template Post Type: page
 */?>
<?php get_header();?>
<?php
//количество постов этого типа(сущности project_list)
  $numberWork=wp_count_posts('project_list')->publish;
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
<div class="myTemplatePageContent bg-portfolio ">
  <div class="container">
    
    <table class="table">
    <thead>
        <tr>
          <th scope="col">Date</th>
          <th scope="col">Name</th>
          <th scope="col">Framework</th>
          <th scope="col">Description</th>
        </tr>
      </thead>
      <tbody class="bg-secondary bg-opacity-75">
    <?php
  
    while (have_posts()) : the_post();
    ?>
        <tr>
          <!--Вывод даты с помощь функции get_field| date это поле из ACF -->
          <th scope="row" class="fs-5"><?php echo get_field('date')?></th>
          <td><a class="link-info fs-5 fw-bold" href="<?php echo get_field('address')?>"  target="_blank" ><?php echo get_field('address')?></a></td>
          <td class="text-warning fs-5 fw-bold"><?php echo get_field('tehnology_type')?></td>
          <td><?php the_content();?></td>
        </tr>
       <?php endwhile;?>
      </tbody>
    </table>
    <div class="d-flex flex-row justify-content-between align-items-center">
      <div>
        <strong>Total number of projects:<?php echo $numberWork;?></strong> 
      </div>
      <div class="d-flex justify-content-center mt-2">
        <?php do_action('myPostPagination');?>
      </div>
    </div>

  </div>
</div>


<?php get_footer();?>
