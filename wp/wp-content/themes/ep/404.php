<?php get_header(); ?>

<section id="Content" class="clearfix">

<hgroup>
<h1>404: Sidan finns inte</h1> 
<h3 class="byline"><a class="kfsk" href="http://www.kfsk.se">Kommunförbundet Skåne</a> <span class="amp">&amp;</span> <a class="regionskane" href="http://www.skane.se/">Region Skåne</a></h3>
</hgroup>

<div id="Main">

<section class="post_content clearfix">

<p class="excerpt dropcap">Sidan du sökte kunde tyvärr inte hittas. Kanske en felstavning av sökvägen i adressfältet..? Om så inte är fallet kan du testa att leta fram sidan genom att använda sökrutan nedan.</p>

<?php include(TEMPLATEPATH . "/searchform.php"); ?>

</section><!-- end post_content section -->

<div class="hr hidefordesktop"></div><hr />

</div><!-- end section main -->

<?php get_sidebar(); ?>

</section><!-- end section Content -->

<?php get_footer(); ?>