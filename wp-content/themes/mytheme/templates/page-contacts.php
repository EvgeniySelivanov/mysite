<?php 
/**
 * Template Name: Contacts page
 * Template Post Type: page
 */?>


<?php get_header(); ?>
  <div class="bg-template content">
    <div class="container row" id="pageContactTemplate">
      <div id="myContactForm" class="mt-4"><?php echo do_shortcode( '[contact-form-7 id="37" title="Contact form 1"]' ); ?></div>
      <div id="myMap"><iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2678.792165398726!2d35.17279521599603!3d47.824244979199676!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x40dc5dfd39ae29e3%3A0x5d710a0a0ef7272a!2sFortechna%20St%2C%2064%2C%20Zaporizhzhia%2C%20Zaporiz&#39;ka%20oblast%2C%2069000!5e0!3m2!1sen!2sua!4v1669362531510!5m2!1sen!2sua" width="600" height="580" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe></div>
    </div>
  </div>
<?php get_footer(); ?>
 