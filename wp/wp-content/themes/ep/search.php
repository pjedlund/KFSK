<?php get_header(); ?>

<section id="Content" class="clearfix">

<hgroup>
<h1><?php echo 'Sökresultat för &ldquo;' . $s . '&rdquo;'; ?></h1>
<h3 class="byline"><a class="kfsk" href="http://www.kfsk.se">Kommunförbundet Skåne</a> <span class="amp">&amp;</span> <a class="regionskane" href="http://www.skane.se/">Region Skåne</a></h3>
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

<?php endwhile; ?>

<?php else : ?>

<div>
<h3>Tyvärr, inga träffar för &ldquo;<em><?php echo wp_specialchars($s, 1); ?></em>&rdquo;. <?php if (function_exists('relevanssi_didyoumean')) {
    relevanssi_didyoumean(get_search_query(), "Du kanske menade ", "?", 5);
}?></h3>
</div>

<?php endif; ?>

<?php if(function_exists('wp_paginate')) {
    wp_paginate();
} ?>

</section><!-- end section main -->

<?php get_sidebar(); ?>

</section><!-- end section Content -->

<?php get_footer(); ?>