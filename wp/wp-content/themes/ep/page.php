<?php get_header(); ?>

<section id="Content" class="clearfix">

<?php if (have_posts()) : while (have_posts()) : the_post(); ?>

<hgroup>
<h1><?php the_title(); ?></h1> 
<h3 class="byline"><a class="kfsk" href="http://kfsk.se">Kommunförbundet Skåne</a> <span class="amp">&amp;</span> <a class="regionskane" href="http://www.skane.se/">Region Skåne</a></h3>
</hgroup>

<div id="Main">

<section class="post_content clearfix">

<?php the_content(); ?>

</section><!-- end post_content section -->

<?php comments_template(); ?>

<?php endwhile; endif ?>

</div><!-- end section main -->

<?php get_sidebar(); ?>

</section><!-- end section Content -->

<?php get_footer(); ?>