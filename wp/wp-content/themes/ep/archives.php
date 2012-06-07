<?php get_header(); ?>

<section id="Content" class="clearfix">

<hgroup>
<h1><?php if (is_category())  { single_cat_title();} ?></h1> 

<h3 class="byline"><a class="kfsk" href="http://kfsk.se">Kommunförbundet Skåne</a> <span class="amp">&amp;</span> <a class="regionskane" href="http://www.skane.se/">Region Skåne</a></h3>
</hgroup>

<section id="Main" role="main">

<?php if (have_posts()) : while (have_posts()) : the_post(); ?>

<article id="post-<?php the_ID(); ?>" <?php post_class('clearfix'); ?>>

<header>

<h2><a href="<?php the_permalink() ?>" rel="bookmark" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a></h2>

</header> <!-- end article header -->

<section class="post_content clearfix">

<?php the_excerpt(); ?>
<p class="textright"><a class="more button" href="<?php the_permalink(); ?> ">Läs mer<span class="hidden"> av <?php the_title(); ?> </span></a></p>

</section><!-- end article section -->

</article> <!-- end article -->

<?php comments_template(); ?>

<?php endwhile; endif ?>

</section><!-- end section main -->

<?php get_sidebar(); ?>

</section><!-- end section Content -->

<?php get_footer(); ?>