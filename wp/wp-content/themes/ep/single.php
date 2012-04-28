<?php get_header(); ?>

<div id="Content">

<?php if (have_posts()) : while (have_posts()) : the_post(); ?>

<div class="entry">

<div class="left">
<h2><?php the_title(); ?></h2>
<?php the_content(); ?>
</div><!-- end .left -->

<div class="right">

<?php
//Get the Thumbnail URL
$src = wp_get_attachment_image_src( get_post_thumbnail_id($post->ID), array( 720,405 ), false, '' );
echo '<a href="'.get_permalink().'">' . '<img src="' . $src[0] . '" />' . '</a>';
?>

</div><!-- end .right -->

</div>

<div class="hr"></div><hr>

<?php endwhile; endif ?>

</div><!-- end Content -->

<?php get_footer(); ?>