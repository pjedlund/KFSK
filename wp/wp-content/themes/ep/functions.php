<?php 

/**** Add post thumbnail functionality ****/
if (function_exists('add_theme_support')){
add_theme_support( 'post-thumbnails' );
set_post_thumbnail_size( 260, 146, true );//  thumbnail size
add_image_size( 'medium', 528, 297, true ); // single post size
add_image_size( 'large', 1440, 1024 ); // lightbox size 
}

/**** Enable menus ****/
add_action( 'init', 'register_my_menus' );
function register_my_menus() {
	register_nav_menus(
		array(
			'huvudnavigering' => __( 'Huvudnavigering' ),
			'sidfotsnavigering' => __( 'Sidfotsnavigering' )
		)
	);
}

/**** Maintenance mode ****/
function admin_maintenace_mode() {
    global $current_user;
    get_currentuserinfo();
    if($current_user->user_login != 'admin') { ?>
			<style> .updated{margin:30px !important;} </style><?
			die('<h3 id="message" class="updated">Underhållsarbete pågår.</h3>');
		}
}
//add_action('admin_head', 'admin_maintenace_mode'); 

add_editor_style();

/**** Enqueue jquery.. ****/ 
if( !is_admin()){
	wp_deregister_script('jquery');
	wp_register_script('jquery', ("http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"), false, '1.7.1', true);
	wp_enqueue_script('jquery');
}

/**** ..and modernizr ****/ 
function addmod(){
wp_register_script( 'modernizr', get_template_directory_uri() . '/js/libs/modernizr-2.5.0.min.js' );
wp_enqueue_script( 'modernizr' );
}
add_action( 'wp_enqueue_scripts', 'addmod', 1 );  


/**** Add post classes  ****/
function additional_post_classes( $classes ) {
	global $wp_query;
	if( $wp_query->found_posts < 1 ) {return $classes;}
	if( $wp_query->current_post == 0 ) {$classes[] = 'post-first';}
	if( $wp_query->current_post % 2 ) {$classes[] = 'post-even';} else {$classes[] = 'post-odd';}
	if( $wp_query->current_post == ( $wp_query->post_count - 1 ) ) {$classes[] = 'post-last';}
	return $classes;
}
add_filter( 'post_class', 'additional_post_classes' );


/**** Remove target _blank etc. ****/
function rel_external($content){
	$regexp = '/\<a[^\>]*(target="_([\w]*)")[^\>]*\>[^\<]*\<\/a>/smU';
	if( preg_match_all($regexp, $content, $matches) ){
		for ($m=0;$m<count($matches[0]);$m++) {
			if ($matches[2][$m] == 'blank') {
				$temp = str_replace($matches[1][$m], 'rel="external"', $matches[0][$m]);
				$content = str_replace($matches[0][$m], $temp, $content);
			} else if ($matches[2][$m] == 'self') {
				$temp = str_replace(' ' . $matches[1][$m], '', $matches[0][$m]);
				$content = str_replace($matches[0][$m], $temp, $content);
			}
		}
	}
	return $content;
}
add_filter('the_content', 'rel_external');


/**** Clean away width and height attributes ****/
function remove_width_attributes($string) {
	return preg_replace('/\<(.*?)(width="(.*?)")(.*?)(height="(.*?)")(.*?)\>/i', '<$1$4$7>',$string);
	return $string;
}

add_filter( 'post_thumbnail_html', 'remove_thumbnail_dimensions', 10 );
add_filter( 'image_send_to_editor', 'remove_thumbnail_dimensions', 10 );
add_filter( 'the_content', 'remove_thumbnail_dimensions', 10 );	

// Removes attached image sizes as well
 /* -------------------------- */
function remove_thumbnail_dimensions( $html ) {
	$html = preg_replace( '/(width|height)=\"\d*\"\s/', "", $html );
	return $html;
}

/**** Clean away title attribute ****/
function remove_title_attributes($input) {
    return preg_replace('/\s*title\s*=\s*(["\']).*?\1/', '', $input);
}
add_filter( 'wp_list_pages', 'remove_title_attributes' );


/**** captions ****/
add_shortcode('wp_caption', 'fixed_img_caption_shortcode');
add_shortcode('caption', 'fixed_img_caption_shortcode');

function fixed_img_caption_shortcode($attr, $content = null) {
	// Allow plugins/themes to override the default caption template.
	$output = apply_filters('img_caption_shortcode', '', $attr, $content);
	if ( $output != '' ) return $output;
	extract(shortcode_atts(array(
		'id'=> '',
		'align'	=> 'alignnone',
		'width'	=> '',
		'caption' => ''), $attr));
	if ( empty($caption) )
	return $content;
	if ( $id ) $id = 'id="' . esc_attr($id) . '" ';
	return '<div ' . $id . 'class="wp-caption clearfix ' . esc_attr($align)
	. '">'
	. do_shortcode( $content ) . '<p class="wp-caption-text">'
	. $caption . '</p></div>';
}


/**** Archive date function ****/
function theme_get_archive_date() {
	if (is_day()) $this_archive = get_the_time('j F Y');
	elseif (is_month()) $this_archive = get_the_time('F Y');
	else $this_archive = get_the_time('Y');
	return $this_archive;
}


/**** Remove header stuff ****/
//remove_action( 'wp_head', 'feed_links_extra', 3 ); // Removes the links to the extra feeds such as category feeds
//remove_action( 'wp_head', 'feed_links', 2 ); // Removes links to the general feeds: Post and Comment Feed

remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wp_generator');
remove_action('wp_head', 'wlwmanifest_link');

function my_remove_recent_comments_style() {
	global $wp_widget_factory;
	remove_action('wp_head', array($wp_widget_factory->widgets['WP_Widget_Recent_Comments'], 'recent_comments_style'));
}
add_action('widgets_init', 'my_remove_recent_comments_style');


/**** Excerpt length ****/
function new_excerpt_length($length) {return 40;}
add_filter('excerpt_length', 'new_excerpt_length');


/**** Insert thumbnails in RSS ****/
function insertThumbnailRSS($content) {
   global $post;
   if(has_post_thumbnail( $post->ID)){
       $content = '<p>' . get_the_post_thumbnail( $post->ID, 'thumbnail' ) . '</p>' . $content;
   }
   return $content;
}
add_filter('the_excerpt_rss', 'insertThumbnailRSS');
add_filter('the_content_feed', 'insertThumbnailRSS');

/****  add feed links to header ****/
if (function_exists('automatic_feed_links')) {automatic_feed_links();
} else {return;}


// Don't add the wp-includes/js/comment-reply.js?ver=20090102 script to single post pages unless threaded comments are enabled
// adapted from http://bigredtin.com/behind-the-websites/including-wordpress-comment-reply-js/
function theme_queue_js(){
  if (!is_admin()){
    if (is_singular() && get_option('thread_comments') && comments_open() && have_comments())
      wp_enqueue_script('comment-reply');
  }
}
add_action('wp_print_scripts', 'theme_queue_js');


/**** Comments ****/
function custom_comments_callback($comment, $args, $depth) {
$GLOBALS['comment'] = $comment; ?>

<li <?php comment_class(); ?> id="comment-<?php comment_ID(); ?>">
<div class="comment-wrap">
<div class="gravatarHolder"><?php echo get_avatar(get_comment_author_email(), $size = '50'); ?></div>

<ul class="comment-meta">
<li><?php printf(__('%s'), get_comment_author_link()); ?></li>
<li><a class="comment-permalink" href="<?php echo htmlspecialchars(get_comment_link($comment->comment_ID)); ?>"><?php comment_date('j/n, Y'); ?> @ <?php comment_time('H.i'); ?></a></li>
<li><?php edit_comment_link('Redigera &raquo;', '', ''); ?></li>
</ul>
<?php if ($comment->comment_approved == '0') : ?>
<p class="comment-moderation"><?php _e('Your comment is awaiting moderation.'); ?></p>
<?php endif; ?>

<div class="comment-text"><?php comment_text(); ?></div>
<div class="clear"></div>
<div class="reply" id="comment-reply-<?php comment_ID(); ?>">
<?php comment_reply_link(array_merge($args, array('reply_text'=>'Svara', 'login_text'=>'Logga in för att svara', 'add_below'=>'comment-reply', 'depth'=>$depth, 'max_depth'=>$args['max_depth']))); ?> 
</div>
<div class="clear"></div>
</div>

<?php } // WP adds the closing </li>


/**** Pingbacks ****/
function custom_ping_callback($comment, $args, $depth) {
$GLOBALS['comment'] = $comment; ?>

<li <?php comment_class(); ?> id="comment-<?php comment_ID(); ?>">
<div class="comment-wrap">

<h4><?php printf(__('%s'), get_comment_author_link()); ?></h4>
<p class="pingbackdate"><a href="http://en.support.wordpress.com/pingbacks/" title="Förklaring av pingback på engelska">Pingback</a> <span>den</span> <a class="comment-permalink" href="<?php echo htmlspecialchars(get_comment_link($comment->comment_ID)); ?>"><?php comment_date('j F, Y'); ?> @ <?php comment_time('H.i'); ?></a></p>
<?php comment_text(); ?>
<div class="clear"></div>

</div>

<?php } // WP adds the closing </li>

/**** Trackbacks ****/
function custom_track_callback($comment, $args, $depth) {
$GLOBALS['comment'] = $comment; ?>

<li <?php comment_class(); ?> id="comment-<?php comment_ID(); ?>">
<div class="comment-wrap">

<h4><?php printf(__('%s'), get_comment_author_link()); ?></h4>
<p class="pingbackdate"><a href="http://en.support.wordpress.com/trackbacks/" title="Förklaring av trackbacks på engelska">Trackback</a> <span>den</span> <a class="comment-permalink" href="<?php echo htmlspecialchars(get_comment_link($comment->comment_ID)); ?>"><?php comment_date('j F, Y'); ?> @ <?php comment_time('H.i'); ?></a></p>
<?php comment_text(); ?>
<div class="clear"></div>

</div>

<?php } // WP adds the closing </li>


/**** Enable widgets ****/
function widgets_init_pj() {
	register_sidebar(array(
	'id' => 'frontpage',
	'name' => 'Frontpage',
	'before_widget' => '<li id="%1$s" class="widget-container %2$s">',
	'after_widget' => '</li>',
	'description' => '',
	'before_title' => '<h3 class="widget-title">',
	'after_title' => '</h3>'));
	
	register_sidebar(array(
	'id' => 'sidebar',
	'name' => 'Sidebar',
	'before_widget' => '<li id="%1$s" class="widget-container %2$s">',
	'after_widget' => '</li>',
	'description' => '',
	'before_title' => '<h3 class="widget-title">',
	'after_title' => '</h3>'));
	
	register_sidebar(array(
	'id' => 'footer1',
	'name' => 'Footer1',
	'before_widget' => '<li id="%1$s" class="widget-container %2$s">',
	'after_widget' => '</li>',
	'description' => '',
	'before_title' => '<h3 class="widget-title">',
	'after_title' => '</h3>'));
	
    register_sidebar(array(
	'id' => 'footer2',
	'name' => 'Footer2',
	'before_widget' => '<li id="%1$s" class="widget-container %2$s">',
	'after_widget' => '</li>',
	'description' => '',
	'before_title' => '<h3 class="widget-title">',
	'after_title' => '</h3>'));
	
	register_sidebar(array(
	'id' => 'footer3',
	'name' => 'Footer3',
	'before_widget' => '<li id="%1$s" class="widget-container %2$s">',
	'after_widget' => '</li>',
	'description' => '',
	'before_title' => '<h3 class="widget-title">',
	'after_title' => '</h3>'));
}
add_action( 'widgets_init', 'widgets_init_pj' );


/**** Limit the archive widget to X months ****/
function my_limit_archives( $args ) {
    $args['limit'] = 10;
    return $args;
}
add_filter( 'widget_archives_args', 'my_limit_archives' );


/**** Remove widgets from wp-admin panel ****/
function remove_dashboard_widgets() {
	global $wp_meta_boxes;
/* 	unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_quick_press']); 
	unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_incoming_links']);
 	unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_right_now']); */
	unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_plugins']);
/* 	unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_recent_drafts']); */
/* 	unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_recent_comments']); */
	unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_primary']);
	unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_secondary']);
}
/* if (!current_user_can('manage_options')) { */
add_action('wp_dashboard_setup', 'remove_dashboard_widgets' );
	

/**** Allow contributors to upload media ****/
if ( current_user_can('contributor') && !current_user_can('upload_files') )
    add_action('admin_init', 'allow_contributor_uploads');
function allow_contributor_uploads() {
    $contributor = get_role('contributor');
    $contributor->add_cap('upload_files');
}

/**** Add copyright info to footer ****/
/**** Usage: echo copyright_dates();  ****/
function copyright_dates() {
	global $wpdb;
	$copyright_dates = $wpdb->get_results("SELECT YEAR(min(post_date_gmt)) AS firstdate, YEAR(max(post_date_gmt)) AS lastdate FROM $wpdb->posts WHERE post_status = 'publish'");
	$output = '';
	if($copyright_dates) {
		$copyright = "&copy; " . $copyright_dates[0]->firstdate;
		if($copyright_dates[0]->firstdate != $copyright_dates[0]->lastdate) {
			$copyright .= '-' . $copyright_dates[0]->lastdate;
		}
	$output = $copyright;
	}
	return $output;
}


/**** Disable auto formatting in posts shortcode ****/
/**** Usage: [raw]Unformatted content[/raw] ****/
function my_formatter($content) {
	$new_content = '';
	$pattern_full = '{(\[raw\].*?\[/raw\])}is';
	$pattern_contents = '{\[raw\](.*?)\[/raw\]}is';
	$pieces = preg_split($pattern_full, $content, -1, PREG_SPLIT_DELIM_CAPTURE);
	foreach ($pieces as $piece) {
		if (preg_match($pattern_contents, $piece, $matches)) {
			$new_content .= $matches[1];
		} else {
			$new_content .= wptexturize(wpautop($piece));
		}
	}
	return $new_content;
}
remove_filter('the_content', 'wpautop');
remove_filter('the_content', 'wptexturize');
add_filter('the_content', 'my_formatter', 99);


/**** Enable widget shortcode ****/
add_filter('widget_text', 'do_shortcode');


/**** Obfuscate email shortcode ****/
function munge_mail_shortcode( $atts , $content=null ) {
for ($i = 0; $i < strlen($content); $i++) $encodedmail .= "&#" . ord($content[$i]) . ';';
return '<a href="mailto:'.$encodedmail.'">'.$encodedmail.'</a>';}
add_shortcode('mailto', 'munge_mail_shortcode');

?>