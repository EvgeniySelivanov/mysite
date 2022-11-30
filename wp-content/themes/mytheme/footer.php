</main>
<footer class="py-3 bg-dark">
  <div class="container-fluid myclass">
    <div class="col-lg-6 ms-4">
      <!-- подключаю виджеты в футер -->
      <?php if (!function_exists('dynamic_sidebar') || !dynamic_sidebar('footer')) : ?>
      <?php endif; ?>
    </div>
    <div class="col-lg-3">
      <a class="logoDown m-0 text-center" href="https://www.upwork.com/freelancers/~013aa21cfee0f3b689" target="_blank">© Evheniy Selivanov</a>
      <a class="logoDown m-0 text-center" href="mailto:genyaselivanovzp@gmail.com">| genyaselivanovzp@gmail.com</a>
      <a class="logoDown m-0 text-center" href="tel:+380665769654">| +380665769654</a>
    </div>
  </div>
</footer>
<?php wp_footer(); ?>
      </wrapper>
      
</body>
</html>