<?php
//подключил файл с бутраповской темой
require_once $_SERVER['DOCUMENT_ROOT'].'/mysite/wp-content/themes/mytheme/bstr/bootstrap_5_wp_nav_menu.php';


//подключаю стили правильно
function it_blog_style_frontend() {
   wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css');
   wp_enqueue_style('styles', get_stylesheet_directory_uri() . '/css/style.css');
   wp_enqueue_style('styles2', get_stylesheet_directory_uri() . '/css/news-page-template.css');
   wp_enqueue_style('styles3', get_stylesheet_directory_uri() . '/css/contact-page-template.css');
   wp_enqueue_style('styles4', get_stylesheet_directory_uri() . '/css/portfolio-page-template.css');

}
//вызвал встроенный хук и подключил стили
add_action('wp_enqueue_scripts', 'it_blog_style_frontend');
//подключаю скрипты
function it_blog_include_myscript(){
  
   wp_enqueue_script('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js', '', '1.0', true);
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
  'before_title'  => '<h5>',
  'after_title'   => '</h5><div>',
));

register_sidebar(array(
   'name'=>'sidebarRight',
   'id'=>'sidebar2',
   'description'=>'sidebar right',
   'before_widget' => '<div id="%1$s">',
   'after_widget'  => '</div>',
   'before_title'  => '<h5>',
   'after_title'   => '</h5><div>',
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



//user information in header
function userInfo(){
   //массив с данными о пользователе
   $userInfo=wp_get_current_user();
   //страница входа и параметром передаю что хочу остаться на той же странице на которой нахожусь
   $pageLogIn=wp_login_url();;
 
   //адресс страницы регистрации
   $pageReg=wp_registration_url();
   //указал что надо перенаправить на главную после выхода из аккаунта
   $linkLogOut=wp_logout_url(home_url());
   //проверка если данные о пользователе(т.е. есть ли вход в акккаунт)
   if(isset($userInfo->display_name)&&isset($userInfo->user_email)){   
      echo(
      "<ul>
         <li>User: $userInfo->display_name</li> 
         <li>Email: $userInfo->user_email</li>
         <li><a href=\"$linkLogOut\">Log Out</a></li>

      </ul>" );}
      else{
         echo(
            "<ul>
               <li><a href=\"$pageLogIn\">Log In</a></li> 
               <li><a href=\"$pageReg\">Registration</a></li>
            </ul>" );
      }

}
//хук для работы с данными пользователя, потом я его вывожу в хедере
add_action('userInfo','userInfo');

	// Disable the toolbar for subscriber only
   add_filter( 'show_admin_bar', function( $show) {
      if (current_user_can( 'subscriber')) {
          return false;
      }
      return $show;
  } );

  //администраторы перенаправляются на дефолтную страницу, а другие пользователи - на домашнюю страницу.
  add_filter( 'login_redirect', 'my_login_redirect', 10, 3 );

  function my_login_redirect( $redirect_to, $request, $user ) {

	//is there a user to check?
	if ( isset( $user->roles ) && is_array( $user->roles ) ) {

		// check for admins
		if ( in_array( 'administrator', $user->roles ) ) {
			// redirect them to the default place
			return $redirect_to;
		}
		else {
			return home_url();
		}
	}
	else {
		return $redirect_to;
	}
}

//CUSTOM PAGINATION USE BOOTSTRAP 5.0
function bsPagination(){
   //ссылка на предидущую страницу с постами
   $previuosPage=get_previous_posts_page_link();
   //чтобы не клацало больше макс к-ва страниц передается
   $nextPage=get_next_posts_page_link($GLOBALS['wp_query']->max_num_pages ?: 1);
   //номер текущей страницы
   $thisPageNumber=get_query_var( 'paged', 1 );

   $previuosPageNumber=$thisPageNumber-1;
   $nextPageNumber=$thisPageNumber+1;

   
echo "<nav aria-label=\"Page navigation example\">
<ul class=\"pagination justify-content-end \">
  <li class=\"page-item\">
    <a class=\"page-link \" href=\"$previuosPage\">Previous</a>
  </li>";
if($previuosPageNumber!==0){
   echo" <li class=\"page-item\"><a class=\"page-link\" href=\"$previuosPage\">$previuosPageNumber</a></li>
   <li class=\"page-item\"><a class=\"page-link\" href=\"#\">$thisPageNumber</a></li>
  <li class=\"page-item\"><a class=\"page-link\" href=\"$nextPage\">$nextPageNumber</a></li>";
}
 else{
   echo "<li class=\"page-item\"><a class=\"page-link\" href=\"#\">$thisPageNumber</a></li>
   <li class=\"page-item\"><a class=\"page-link\" href=\"$nextPage\">$nextPageNumber</a></li>";
 }
  echo"
  <li class=\"page-item\">
    <a class=\"page-link\" href=\"$nextPage\">Next</a>
  </li>
</ul>
</nav>";
}
add_action('myPostPagination','bsPagination');

//Создаю новый тип записи (сущность).Для добавления проэктов в список из админки.
add_action( 'init', 'register_post_types' );
function register_post_types(){
	register_post_type( 'project_list', [
		'label'  => 'Список проектов',
		'labels' => [
			'name'               => 'Список проектов', // основное название для типа записи
			'singular_name'      => 'Проект', // название для одной записи этого типа
			'add_new'            => 'Добавить проект', // для добавления новой записи
			'add_new_item'       => 'Добавить новый проект', // заголовка у вновь создаваемой записи в админ-панели.
			'edit_item'          => 'Редактирование записи', // для редактирования типа записи
			'new_item'           => 'Текст записи', // текст новой записи
			'view_item'          => 'Смотреть запись', // для просмотра записи этого типа.
			'search_items'       => 'Искать запись', // для поиска по этим типам записи
			'not_found'          => 'Не найдено', // если в результате поиска ничего не было найдено
			'not_found_in_trash' => 'Не найдено в корзине', // если не было найдено в корзине
			'parent_item_colon'  => '', // для родителей (у древовидных типов)
			'menu_name'          => 'Список проектов', // название меню
		],
		'description'         => 'Список готовых проектов.Портфолио',
		'public'              => true,
		'publicly_queryable'  => true, // зависит от public
		'exclude_from_search' => true, // зависит от public
		'show_ui'             => true, // зависит от public
		'show_in_nav_menus'   => true, // зависит от public
		'show_in_menu'        => true, // показывать ли в меню адмнки
		'show_in_admin_bar'   => true, // зависит от show_in_menu
		'show_in_rest'        => true, // добавить в REST API. C WP 4.7
		'rest_base'           => null, // $post_type. C WP 4.7
		'menu_position'       => 4,
		'menu_icon'           => 'dashicons-list-view',
		//'capability_type'   => 'post',
		//'capabilities'      => 'post', // массив дополнительных прав для этого типа записи
		//'map_meta_cap'      => null, // Ставим true чтобы включить дефолтный обработчик специальных прав
		'hierarchical'        => false,
		'supports'            => [ 'title', 'editor', 'author','thumbnail','excerpt','custom-fields'], // 'title','editor','author','thumbnail','excerpt','trackbacks','custom-fields','comments','revisions','page-attributes','post-formats'
		'taxonomies'          => [],
		'has_archive'         => false,
		'rewrite'             => true,
		'query_var'           => true,
	] );
}


?>
  