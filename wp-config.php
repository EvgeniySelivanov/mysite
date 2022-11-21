<?php
/**
 * Основные параметры WordPress.
 *
 * Скрипт для создания wp-config.php использует этот файл в процессе установки.
 * Необязательно использовать веб-интерфейс, можно скопировать файл в "wp-config.php"
 * и заполнить значения вручную.
 *
 * Этот файл содержит следующие параметры:
 *
 * * Настройки базы данных
 * * Секретные ключи
 * * Префикс таблиц базы данных
 * * ABSPATH
 *
 * @link https://ru.wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Параметры базы данных: Эту информацию можно получить у вашего хостинг-провайдера ** //
/** Имя базы данных для WordPress */
define( 'DB_NAME', 'wp_mysite' );

/** Имя пользователя базы данных */
define( 'DB_USER', 'admin' );

/** Пароль к базе данных */
define( 'DB_PASSWORD', 'FvxPq7)ubC_A_7TB' );

/** Имя сервера базы данных */
define( 'DB_HOST', 'localhost' );

/** Кодировка базы данных для создания таблиц. */
define( 'DB_CHARSET', 'utf8mb4' );

/** Схема сопоставления. Не меняйте, если не уверены. */
define( 'DB_COLLATE', '' );

/**#@+
 * Уникальные ключи и соли для аутентификации.
 *
 * Смените значение каждой константы на уникальную фразу. Можно сгенерировать их с помощью
 * {@link https://api.wordpress.org/secret-key/1.1/salt/ сервиса ключей на WordPress.org}.
 *
 * Можно изменить их, чтобы сделать существующие файлы cookies недействительными.
 * Пользователям потребуется авторизоваться снова.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'A(]$K?KQAP0CVh|8*o&9{a)vh^v3)mw#~n`5:Y/DZ~v,pd<k$T3_WBCI 5rF<@zM' );
define( 'SECURE_AUTH_KEY',  ',v.0mEL %$6^$EGhK/4?BgeE;}M(lBR6I%iw=&fQ-/e)pWqRiI12o,KTlH%cwVa ' );
define( 'LOGGED_IN_KEY',    ']>CtQ[t+%Bm30dG2c Ei($IS>s{b}xSc>=E|El#-ZRD]ZqGXx5~ji}WQdP`=b*w@' );
define( 'NONCE_KEY',        '*4Fn{w5<nmRT+|/-}RU-EIhP<>>4Q/1<0o=vp$K)]fN_c9g,^e/I!E+KbH5_tqA5' );
define( 'AUTH_SALT',        '17b^G!3kUm|1R*8eI&y_1K =;v3JV|V9t8987L,:6Yd {]<SUsid]@Lsv29YB_F!' );
define( 'SECURE_AUTH_SALT', '_ 5nD}dKcnBG<QpPURUuvC?-}sM%d%~v^bFQp_#_^p40&Rlg1Qfj6vAKXrw8~/52' );
define( 'LOGGED_IN_SALT',   'z!)903K2N*G8n)I;lcc8NecAtO^`D SS5>X-S!a&z|0x;_0E~j4oj8~ &UY8EcXd' );
define( 'NONCE_SALT',       'W9B^w].NeYpEd}8&;p7!KLt=jRV[Zb)u.:rB?^5yU7mY{b19(u?B~5Vei16D`Xo{' );

/**#@-*/

/**
 * Префикс таблиц в базе данных WordPress.
 *
 * Можно установить несколько сайтов в одну базу данных, если использовать
 * разные префиксы. Пожалуйста, указывайте только цифры, буквы и знак подчеркивания.
 */
$table_prefix = 'wp_';

/**
 * Для разработчиков: Режим отладки WordPress.
 *
 * Измените это значение на true, чтобы включить отображение уведомлений при разработке.
 * Разработчикам плагинов и тем настоятельно рекомендуется использовать WP_DEBUG
 * в своём рабочем окружении.
 *
 * Информацию о других отладочных константах можно найти в документации.
 *
 * @link https://ru.wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_DISPLAY', false );


/* Произвольные значения добавляйте между этой строкой и надписью "дальше не редактируем". */



/* Это всё, дальше не редактируем. Успехов! */

/** Абсолютный путь к директории WordPress. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Инициализирует переменные WordPress и подключает файлы. */
require_once ABSPATH . 'wp-settings.php';
