<?php get_header();?>
<?php
$post = $wp_query->post;
if ( in_category( '3' ) ) { //ID категории
    include( TEMPLATEPATH.'/single-news.php' );//применяй этот шаблон к определенной категории
} else {
    include( TEMPLATEPATH.'/single-default.php' );
}
?>
<?php get_footer();?>
