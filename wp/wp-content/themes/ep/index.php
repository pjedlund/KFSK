<?php get_header(); ?>

<div id="Content">
<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
<?php if (in_category('video')) : ?>
<?php if (is_single()) : ?>
<div class="entry">
<div class="left">
<h2><?php the_title(); ?></h2>
<?php the_content(); ?>
</div><!-- end .left -->
<div class="right">
<div id="so_targ_vp_1803364850" class="flashmovie">Du behöver den <a href='http://get.adobe.com/flashplayer/'>senaste flash playern</a> för att se videon.</div>
<script type="text/javascript">
// <![CDATA[
var so_1803364850 = new SWFObject("/flash/vp.swf","fm_vp_1803364850","440","387","9","#000000","","","","","");
so_1803364850.addParam("allowFullScreen", "true");
so_1803364850.addVariable("flvurl","/flash/video/<?php $customField = get_post_custom_values("videofil");if (isset($customField[0])){echo $customField[0];} ?>");
so_1803364850.write("so_targ_vp_1803364850");
// ]]>
</script>
<p class="download"><a href="/flash/video/<?php $customField = get_post_custom_values("videofil");if (isset($customField[0])){echo $customField[0];} ?>">Ladda ner videon</a> (flash video)</p>
<p>För att se filmen på din dator behöver du också ladda ner och installera <a href="http://www.adobe.com/products/mediaplayer/">Adobe Media Player</a>.</p>
</div><!-- end .right -->
</div> <!-- end .entry -->

<?php else : ?>
<h2 class="videolista"><a href="<?php the_permalink() ?>" rel="bookmark" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a></h2>
<?php endif; ?>

<?php else : ?>
<div class="entry">

<div class="left">
<h2><a href="<?php the_permalink() ?>" rel="bookmark" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a></h2>
<?php the_content(); ?>
</div><!-- end .left -->

<div class="right">

<?php
//Get the Thumbnail URL
$src = wp_get_attachment_image_src( get_post_thumbnail_id($post->ID), array( 720,405 ), false, '' );
echo '<a href="'.get_permalink().'">' . '<img src="' . $src[0] . '" />' . '</a>';
?>

</div><!-- end .right -->
</div> <!-- end .entry -->

<div class="hr"></div><hr />

<?php endif; ?>
<?php endwhile; endif ?>

</div><!-- end Content -->

<?php get_footer(); ?>