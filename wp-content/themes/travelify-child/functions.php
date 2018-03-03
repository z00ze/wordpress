<?php
header('Content-Type: text/html; charset=utf-8');
add_action( 'wp_enqueue_scripts', 'my_theme_enqueue_styles' );
function my_theme_enqueue_styles() {
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );

}

/* Jos pitää lisätä jotain headeriin niin -> */
function add_toheader() {
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

// Change default WordPress email address
add_filter('wp_mail_from', 'new_mail_from');
add_filter('wp_mail_from_name', 'new_mail_from_name');

function new_mail_from($old) {
return 'riski-info@utu.fi';
}
function new_mail_from_name($old) {
return 'Riski-info';
}

/* Facebook og */
//Adding the Open Graph in the Language Attributes
function add_opengraph_doctype( $output ) {
        return $output . ' xmlns:og="http://opengraphprotocol.org/schema/" xmlns:fb="http://www.facebook.com/2008/fbml"';
    }
add_filter('language_attributes', 'add_opengraph_doctype');
 
//Lets add Open Graph Meta Info
 
function insert_fb_in_head() {
    global $post;
    if ( !is_singular()) return;
        echo '<meta property="fb:admins" content="1706607593"/>';
        echo '<meta property="og:title" content="' . get_the_title() . '"/>';
        echo '<meta property="og:type" content="article"/>';
        echo '<meta property="og:url" content="' . get_permalink() . '"/>';
        echo '<meta property="og:site_name" content="https://www.asteriski.fi"/>';
        echo '<meta property="og:description" content="Asteriski ry - Turun tietojenkäsittelytieteiden opiskelijoiden ainejärjestö."/>';
        echo '<meta property="fb:app_id" content="900688140104027"/>';
    if(!has_post_thumbnail( $post->ID )) { 
        $default_image="http://asteriski.utu.fi/wp-content/uploads/2017/09/asteriski_logo_netisivu.png";
        echo '<meta property="og:image" content="' . $default_image . '"/>';
    }
    else{
        $thumbnail_src = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'large' );
        echo '<meta property="og:image" content="' . esc_attr( $thumbnail_src[0] ) . '"/>';
    }
    echo "";
}
add_action( 'wp_head', 'insert_fb_in_head', 5 );
?>
