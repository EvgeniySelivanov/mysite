<?php

/**
 * Template Name: Main page
 * Template Post Type: page
 */ ?>
<?php get_header(); ?>
<?php
// массив с адресами картинок
$path = get_field('main_gallery');
?>
  
  <div class="d-flex flex-row">
    <div class="d-flex flex-column flex-shrink-0 p-3 text-white bg-dark" style="width: 280px;">
    <?php if (
      !function_exists('dynamic_sidebar')
      || !dynamic_sidebar('sidebarLeft')
    ) : ?>
    <?php endif; ?>
    </div>
    
    <!--карусель из бутсрапа-->
    <div class="container removeMargins">
    <div id="carouselExampleIndicators" class="carousel slide bg-dark" data-bs-ride="carousel">
      <div class="carousel-indicators">
        <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
        <!--В зависимости от количества картинок в массиве будет создаваться такое же количество прямоугольников управления-->
        <?php for ($i = 1; $i < count($path) + 1; $i++) { ?>
        <?php echo "<button type=\"button\" data-bs-target=\"#carouselExampleIndicators\" data-bs-slide-to=\"" . $i . " \"aria-label=\"Slide" . $i + 1 . "\"></button>";
        }; ?>
      </div>
      <div class="carousel-inner">
        <div class="carousel-item active">
          <!--Вывод одной картинки с классов актив обязателен-->
          <div class="d-flex justify-content-center"><img src=" <?php echo $path[3]; ?>" class="d-block " alt="wait.." width="960" height="540"></div>
        </div>
        <?php foreach ($path as $paths) { ?>
          <div class="carousel-item ">
            <!--Вывод в цикле массива адрессов картинок-->
            <div class="d-flex justify-content-center"><img src="<?php echo $paths; ?>" class="d-block " alt="..." width="960" height="540"></div>
          </div>
        <?php } ?>
      </div>
      <button class="carousel-control-prev " type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide="prev">
        <span class="carousel-control-prev-icon btn-dark" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
      </button>
      <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide="next">
        <span class="carousel-control-next-icon btn-dark" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
      </button>
    </div>
    </div>
    <div class="d-flex flex-column flex-shrink-0 p-3 text-white bg-dark" style="width: 280px;">
    <?php if (
      !function_exists('dynamic_sidebar')
      || !dynamic_sidebar('sidebarRight')
    ) : ?>
    <?php endif; ?>
    </div>
  </div>

<?php get_footer(); ?>