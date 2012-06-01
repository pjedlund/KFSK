<?php get_header(); ?>

<section id="Content" class="clearfix">

<?php if (have_posts()) : while (have_posts()) : the_post(); ?>

<hgroup>
<h1><?php the_title(); ?></h1> 
<?php $day = get_the_time('d');$month = get_the_time('m');$year  = get_the_time('Y'); $baseUrl = get_bloginfo('url');?>
<h3 class="byline"><time datetime="<?php the_time('c'); ?>"><a href="<?php  echo $baseUrl .'/'. $year .'/'. $month . '/'. $day . '/'; ?>"><?php the_time('j F Y'); ?></a></time> av <?php the_author_posts_link(); ?> i <?php the_category(', '); ?></h3>
</hgroup>

<div id="Main" role="main">


<section class="post_content clearfix">

<?php the_excerpt(); ?>
<?php
if (has_post_thumbnail()) {
	$fullSrc = wp_get_attachment_image_src(get_post_thumbnail_id(), 'large');
	echo '<figure><a class="lightbox" title="' . the_post_thumbnail_caption() .'" href="' . $fullSrc[0] . '">' . get_the_post_thumbnail($post->ID, 'medium') . '</a><figcaption>' . the_post_thumbnail_caption() . '</figcaption></figure>';}
?>
<?php the_content(); ?>

</section><!-- end post_content section -->

<?php comments_template(); ?>

<?php endwhile; endif ?>

</div><!-- end section main -->

<?php get_sidebar(); ?>

</section><!-- end section Content -->

<?php get_footer(); ?>