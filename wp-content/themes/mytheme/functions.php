<?php
//подключил файл с бутраповской темой
require_once $_SERVER['DOCUMENT_ROOT'].'/mysite/wp-content/themes/mytheme/bstr/bootstrap_5_wp_nav_menu.php';
//подключил пагинация на странице превью
// require_once $_SERVER['DOCUMENT_ROOT'].'/mysite/wp-content/themes/mytheme/helpers/pagination-page-previews.php';
//подключаю стили правильно
function it_blog_style_frontend() {
   wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css');
   wp_enqueue_style('styles', get_stylesheet_directory_uri() . '/css/style.css');
   wp_enqueue_style('styles2', get_stylesheet_directory_uri() . '/css/news-page-template.css');
   wp_enqueue_style('styles3', get_stylesheet_directory_uri() . '/css/contact-page-template.css');
}
//вызвал встроенный хук и подключил стили
add_action('wp_enqueue_scripts', 'it_blog_style_frontend');
//подключаю скрипты
function it_blog_include_myscript(){
  
   wp_enqueue_script('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js', '', '1.0', true);
}
//вызвал встроенный хук и подключил  скрипт 
add_action('wp_enqueue_scripts', 'it_blog_include_myscript');

// add_theme_support('menus');//добавляет поддержку меню. Для этих целей лучше использовать функцию register_nav_menus(). 

//Регистрация шапки подвала и сайдбаров с поддрежкой виджетов
if ( function_exists('register_sidebar') )
register_sidebar(array(
  'name'=>'sidebarLeft',
  'id'=>'sidebar1',
  'description'=>'sidebar left',
  'before_widget' => '<div id="%1$s">',
  'after_widget'  => '</div>',
  'before_title'  => '<h5 class="card-header">',
  'after_title'   => '</h5><div class="card-body">',
));

register_sidebar(array(
   'name'=>'sidebarRight',
   'id'=>'sidebar2',
   'description'=>'sidebar right',
   'before_widget' => '<div id="%1$s">',
   'after_widget'  => '</div>',
   'before_title'  => '<h5 class="card-header">',
   'after_title'   => '</h5><div class="card-body">',
 ));

register_sidebar(array(
   'name'=>'footer',
   'id'=>'sidebar3',
   'description'=>'Место в подвале сайта',
   'before_widget' => '<div id="%1$s">',
   'after_widget'  => '</div>',
   'before_title'  => '<h2>',
   'after_title'   => '</h2>' )
);
register_sidebar(array(
  'name'=>'header',
  'id'=>'sidebar4',
  'description'=>'Место в голове сайта',
  'before_widget' => '<div id="%1$s">',
  'after_widget'  => '</div>',
  'before_title'  => '<h2>',
  'after_title'   => '</h2>' )
);

// Регистрируем возможности темы
add_action( 'after_setup_theme', function(){
//поддержка меню
register_nav_menu('main-menu', 'Main menu');
//Позволяет устанавливать миниатюру посту.
add_theme_support( 'post-thumbnails' );
//https://developer.wordpress.org/reference/functions/set_post_thumbnail_size/
set_post_thumbnail_size( 150, 150);
//Позволяет указывать формат посту(можно выбрать при редактировании поста)
add_theme_support(
   'post-formats',
   array(
       'link',
       'aside',
       'gallery',
       'image',
       'quote',
       'status',
       'video',
       'audio',
       'chat',
   )
 );
//Добавляет возможность изменять фон из админки
// add_theme_support( 'custom-background',
// array(
// 	'default-color'          => '',
// 	'default-image'          => '',
// 	'wp-head-callback'       => '_custom_background_cb',
// 	'admin-head-callback'    => '',
// 	'admin-preview-callback' => ''
// )
// );
// можно изменять изображения в шапке из админки
// add_theme_support( 'custom-header', array(
// 	'default-image'          => '',
// 	'random-default'         => false,
// 	'width'                  => 0,
// 	'height'                 => 0,
// 	'flex-height'            => false,
// 	'flex-width'             => false,
// 	'default-text-color'     => '', // вызывается функций get_header_textcolor()
// 	'header-text'            => true,
// 	'uploads'                => true,
// 	'wp-head-callback'       => '',
// 	'admin-head-callback'    => '',
// 	'admin-preview-callback' => '',
// 	'video'                  => false, // с 4.7
// 	'video-active-callback'  => 'is_front_page', // с 4.7
// ) );
//Включает поддержку html5 разметки для списка комментариев, формы комментариев, формы поиска, галереи и т.д.
add_theme_support(
	'html5',
	array(
	'comment-list',
	'comment-form',
	'search-form',
	'gallery',
	'caption',
	'script',
	'style',
	'navigation-widgets',
	)
);


//закрытие функции
});


?>



