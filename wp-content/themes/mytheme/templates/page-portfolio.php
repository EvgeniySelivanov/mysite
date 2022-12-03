<?php 
/**
 * Template Name: Portfolio page
 * Template Post Type: page
 */?>
<?php get_header();?>
<?php
$posts = get_posts( array(
	'numberposts' => 5,
	'post_type'   => 'project_list',
	'suppress_filters' => true, // подавление работы фильтров изменения SQL запроса
) );
 //var_dump($posts); 
?>
<table class="table">
<thead>
    <tr>
      <th scope="col">#</th>
      <th scope="col">Name</th>
      <th scope="col">Framework</th>
      <th scope="col">Description</th>
    </tr>
  </thead>
  <tbody>
<?php 
$numberWork=0;
foreach($posts as $post){
  setup_postdata($post);?>  
    <tr>
      <th scope="row">
        <?php 
      $numberWork+=1;
      echo $numberWork;?></th>
      
      <td><?php the_title();?></td>
      <td>Wordpress</td>
      <td><?php the_content();?></td>
    </tr>
   <?php }?>
  </tbody>
</table>
<?php wp_reset_postdata();?>
<?php get_footer();?>
