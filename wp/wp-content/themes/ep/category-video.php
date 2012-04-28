<?php get_header(); ?>

<div id="Content">
<div class="entry">
<div class="left">
<ul class="videolista">
<?php if (have_posts()) : while (have_posts()) : the_post(); ?>

<li><a href="<?php the_permalink() ?>" rel="bookmark" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a></li>

<?php endwhile; endif ?>

</ul>
</div><!-- end .left -->
</div> <!-- end .entry -->

<div class="hr"></div><hr />

</div><!-- end Content -->

<?php get_footer(); ?>