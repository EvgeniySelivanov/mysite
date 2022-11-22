
<!-- /*pagination page reviews  */ -->
<!-- лютая хуйня с процентами и закарлючками (%4$s)это поочередность отображения елементов sprintf() -->
<?php function wp_custom_pagination($args = [], $class = 'pagination') {

if ($GLOBALS['wp_query']->max_num_pages <= 1) return;

$args = wp_parse_args( $args, [
        'mid_size'                   => 2,
        'prev_next'                  => false,
        'prev_text'                  => __('Previous', 'textdomain'),
        'next_text'                  => __('Next', 'textdomain'),
        'screen_reader_text' => __('Posts navigation', 'textdomain'),
]);

$links     = paginate_links($args);
$next_link = get_next_posts_link($args['next_text']);
$prev_link = get_previous_posts_link($args['prev_text']);
$check_prev = (!empty($prev_link))?$prev_link:'Previous';
$check_next = (!empty($next_link))?$next_link:'Next';
$template    = apply_filters( 
    'navigation_markup_template', 


    '<nav aria-label="Page navigation example">
      <ul class="pagination justify-content-center">
        <li class="page-item disabled"> 
          <button type="button" class="btn btn-dark">%3$s</button>  
        </li>
            <nav class="navigation %1$s" role="navigation">
                
                    <ul class="pagination  mb-0 text-dark">
                        <li class="page-item page-link">%4$s</li>
                    </ul>
            </nav>
            
        <li class="page-item">
          <button type="button" class="btn btn-dark">%5$s</button>
        </li>
      </ul>
    </nav>'




, $args, $class);

echo sprintf($template, $class, $args['screen_reader_text'], $check_next, $links, $check_prev);

}
add_action('reviews_pagination','wp_custom_pagination');