<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php wp_title('|', true, 'right');?>Mysite</title>
    <?php wp_head();?>
</head>
<body>
<div class="wrapper">
<!-- menu bootstrap -->
<header>
  <nav  class="navbar navbar-expand-md navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="logo" href="https://www.upwork.com/freelancers/~013aa21cfee0f3b689" target="_blank">Selivanov</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#main-menu" aria-controls="main-menu" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
  
            <div class="collapse navbar-collapse" id="main-menu">
                <?php
                wp_nav_menu(array(
                    'theme_location' => 'main-menu',
                    'container' => false,
                    'menu_class' => '',
                    'fallback_cb' => '__return_false',
                    'items_wrap' => '<ul id="%1$s" class="navbar-nav  me-auto mb-2 mb-md-0 %2$s">%3$s</ul>',
                    'depth' => 2,
                    'walker' => new bootstrap_5_wp_nav_menu_walker()
                ));
                ?>
            </div>
        </div>
          <!-- динамчиское изменение меню -->
    <div class="col-lg-3">
      <?php if (
        !function_exists('dynamic_sidebar')
        || !dynamic_sidebar('header')
      ) : ?>
      <?php endif; ?>
      <!-- user information -->
      <div class="text-info"><?php do_action('userInfo'); ?></div>
    </div>
  
    </nav>
</header>


<!-- content -->
<main>
