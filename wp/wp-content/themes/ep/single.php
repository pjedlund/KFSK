<?php get_header(); ?>

<div id="ContentWrapper">
<section role="content" class="clearfix">

<section role="main">

<?php if (have_posts()) : while (have_posts()) : the_post(); ?>

<article id="post-<?php the_ID(); ?>" <?php post_class('clearfix'); ?>>

<header>

<h1 class="h2"><a href="<?php the_permalink() ?>" rel="bookmark" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a></h1>

<p class="entry-meta"><!-- start entry-meta -->
<?php $baseUrl = get_bloginfo('url');
$day = get_the_time('d');
$month = get_the_time('m');
$year  = get_the_time('Y');?>
Skrivet av <?php the_author_posts_link(); ?>, 
<time pubdate datetime="<?php the_time('c'); ?>">
<a href="<?php  echo $baseUrl .'/'. $year .'/'. $month . '/'. $day . '/'; ?>"><?php the_time('j F Y'); ?></a></time>,
i <?php the_category(', '); ?>. 
<?php the_tags('<span class="tags-title">Etiketter:</span> ', ', ', ''); ?>. 
<?php comments_popup_link('Lämna en kommentar', 'En kommentar', '% Kommentarer'); ?>
</p><!-- end entry-meta-wrapper -->

</header> <!-- end article header -->

<section class="post_content clearfix">
<?php
if (has_post_thumbnail()) {
	$fullSrc = wp_get_attachment_image_src(get_post_thumbnail_id(), 'full');
	echo '<figure><a href="#">' . get_the_post_thumbnail($post->ID, 'post-large') . '</a></figure>';}
?>
<?php the_content(); ?>
</section><!-- end post_content section -->

<footer>
<p class="tags"><?php the_tags('<span class="tags-title">Etiketter:</span> ', ', ', ''); ?></p>
</footer> <!-- end article footer -->

</article> <!-- end article -->

<?php comments_template(); ?>

<?php endwhile; endif ?>

</section><!-- end main -->

<?php get_sidebar(); ?>

</section><!-- end section content -->
</div><!-- end ContentWrapper -->

<?php get_footer(); ?>