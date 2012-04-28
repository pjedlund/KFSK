<?php get_header(); ?>

<div id="ContentWrapper">
<section role="content" class="clearfix">

<section role="main">

<?php if (have_posts()) : while (have_posts()) : the_post(); ?>

<article id="post-<?php the_ID(); ?>" <?php post_class('clearfix'); ?>>

<header>

<h1 class="h2"><a href="<?php the_permalink() ?>" rel="bookmark" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a></h1>

</header> <!-- end article header -->

<section class="post_content clearfix">
<?php
if (has_post_thumbnail()) {
	$fullSrc = wp_get_attachment_image_src(get_post_thumbnail_id(), 'full');
	echo '<figure><a href="#">' . get_the_post_thumbnail($post->ID, 'post-large') . '</a></figure>';}
?>
<?php the_content(); ?>
</section><!-- end article section -->

<footer>
<p class="tags"><?php the_tags('<span class="tags-title">Etiketter:</span> ', ', ', ''); ?></p>
</footer> <!-- end article footer -->

</article> <!-- end article -->

<?php endwhile; endif ?>

</section><!-- end main -->

</section><!-- end section content -->
</div><!-- end ContentWrapper -->

<?php get_footer(); ?>