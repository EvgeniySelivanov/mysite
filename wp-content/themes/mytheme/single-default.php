<?php get_header();?>
<?php while(have_posts()):the_post();?>

    <h1 class="my-4 page-title"><?php wp_title();?></h1>
    <img class="img-fluid rounded" src="<?php echo get_the_post_thumbnail_url(null, 'full');?>" 
    alt="<?php the_title();?>">
    <p><small class="text-muted"><?php the_time('j F Y');?> <?php the_tags('');?></small></p>
    <?php the_content('more...');?>
    <?php comments_template(); ?>
<?php endwhile;?>

