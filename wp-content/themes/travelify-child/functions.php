<?php
header('Content-Type: text/html; charset=utf-8');
add_action( 'wp_enqueue_scripts', 'my_theme_enqueue_styles' );
function my_theme_enqueue_styles() {
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );

}

/* Jos pitää lisätä jotain headeriin niin -> */
function add_toheader() {
    ?>
        <!-- laita se tähän -->
    <?php
}
add_action('wp_head', 'add_toheader');

/* Shortcode lasten listaamiseen. */
function listaa_lapset() {

    global $post; 

if ( is_page() && $post->ID )    
    $childpages = wp_list_pages( 'sort_column=menu_order&title_li=&child_of=' . $post->ID . '&echo=0&depth=2' );

    if ( $childpages ) {    
         $string = '<ul>' . $childpages . '</ul>';
    }

    return $string;
}

add_shortcode('get_lapset', 'listaa_lapset');

/* Funktio testaamaan onko pagella lapsia */
function has_children() {
    global $post;

    $children = get_pages( array( 'child_of' => $post->ID ) );
    if( count( $children ) == 0 ) {
        return false;
    } else {
        return true;
    }
}

?>
