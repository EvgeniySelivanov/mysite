<?php
/*
Plugin Name: Тест плагин
Description: Пробник.
Version: 1.0
Author: Селиванов
Author URI: https://www.facebook.com/evgeniy.selivanov.5
*/

//объявил константу путь к плагину
define('MYPLUGIN_DIR',plugin_dir_path(__FILE__));


//фильтр-хук который принимает только текст статьи 
function myplugin_filter_the_content($the_content){

static $words=array();
if(empty($words)){
  $words=explode(',',file_get_contents(MYPLUGIN_DIR . 'words.txt'));
}
for($i=0;$i<count($words);$i++){
  $the_content=preg_replace('#'. $words[$i]. '#iu', '{badword}', $the_content);
}


//var_dump($words);
return $the_content;
}
//добавляем нашу функцию к хук фильтру которая отвечает за вывод контента т.е. вместо стандарной заданой в записи инфы выводи ту что возвращает функция
add_filter('the_content','myplugin_filter_the_content');
?>
