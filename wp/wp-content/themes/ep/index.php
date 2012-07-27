<?php get_header(); ?>

<section id="Content" class="clearfix">

<hgroup>
<h1><?php 
if (is_category()){ echo single_cat_title();}
elseif (is_tag())  {
  echo 'Arkiv för etiketten <span>'.single_tag_title('', false ).'</span>';
} 
elseif (is_author()){ // Skribent
	if(get_query_var('author_name')) :
    $curauth = get_user_by('slug', get_query_var('author_name'));
    else :
    $curauth = get_userdata(get_query_var('author'));
    endif;
  echo 'Artiklar skrivna av <span>'.$curauth->first_name.' '.$curauth ->last_name.'</span>';
}
elseif (is_archive()){ 
  echo 'Arkiv för <span>'; echo theme_get_archive_date(); echo '</span>';
}
else {bloginfo('name');} ?>
</h1>
<h3 class="byline"><a class="kfsk" href="http://www.kfsk.se">Kommunförbundet Skåne</a> <span class="amp">&amp;</span> <a class="regionskane" href="http://www.skane.se/">Region Skåne</a></h3>
</hgroup>

<section id="Main" role="main">

<?php if (is_home()){
/* TODO - better solution for this */
$om_id = 30;
$omevidens = get_post($om_id); 
$title = $omevidens->post_title;
$omurl = get_permalink($om_id);
?>
<article class="clearfix">
<h2 class="dropcap">I en <em>evidensbaserad praktik</em> används <strong>tre</strong> kunskapskällor för att skapa en så effektiv vård som möjligt: <strong>vetenskaplig kunskap</strong> om insatsers effekt, <strong>brukarens erfarenheter</strong> och förväntningar och den <strong>professionelles expertis.</strong></h2>
<p class="textright"><a class="more button" href="<?php echo $omurl ?>">Läs mer <span class="hidden"> av <?php echo $title ?></span></a></p>
</article>
<?php } ?>

<?php if (have_posts()) : while (have_posts()) : the_post(); ?>

<article id="post-<?php the_ID(); ?>" <?php post_class('clear	fix'); ?>>

<header>

<h2><a href="<?php the_permalink() ?>" rel="bookmark" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a></h2>

</header> <!-- end article header -->

<section class="post_content clearfix">

<?php
if (has_post_thumbnail()) {
	$thumb = wp_get_attachment_image_src(get_post_thumbnail_id(), 'thumbnail');
	echo '<a class="alignright" title="' . the_post_thumbnail_caption() .'" href="' . get_permalink() . '">' . get_the_post_thumbnail($post->ID, 'thumbnail') . '</a>';
}?>
<?php the_excerpt(); ?>
<p class="textright"><a class="more button" href="<?php the_permalink(); ?> ">Läs mer<span class="hidden"> av <?php the_title(); ?> </span></a></p>

</section><!-- end article section -->

</article> <!-- end article -->

<?php comments_template(); ?>

<?php endwhile; endif ?>

<?php if(function_exists('wp_paginate')) {
    wp_paginate();
} ?>

</section><!-- end section main -->

<?php get_sidebar(); ?>

</section><!-- end section Content -->

<?php get_footer(); ?>