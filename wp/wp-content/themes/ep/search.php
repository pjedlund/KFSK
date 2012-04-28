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
<?php the_excerpt('&raquo; &raquo; &raquo; &raquo;'); ?>
<p class="textright"><a class="more button" href="<?php the_permalink(); ?> ">Läs mer<span class="hidden"> av <?php the_title(); ?> </span></a></p>
</section><!-- end article section -->

<footer>
<p class="tags"><?php the_tags('<span class="tags-title">Etiketter:</span> ', ', ', ''); ?></p>
</footer> <!-- end article footer -->

</article> <!-- end article -->

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
</div><!-- end ContentWrapper -->

<?php get_footer(); ?>