<div class="push"></div>
</div><!-- end StickyFooter -->

<div id="FooterWrapper">
<footer role="contentinfo" class="clearfix">

<h3>Trust Partner</h3>

<div id="Footer1" class="widget-area">
<ul> 
<?php dynamic_sidebar('Footer1'); ?>
</ul>
</div><!-- end Footer 1 -->

<div id="Footer2" class="widget-area">
<ul>
<?php dynamic_sidebar('Footer2'); ?>
</ul>
</div><!-- end Footer 2 -->

<div id="Footer3" class="widget-area">
<ul>
<?php dynamic_sidebar('Footer3'); ?>
</ul>
</div><!-- end Footer 3 -->

</footer><!-- end footer -->
</div><!-- end FooterWrapper -->

<!-- Scripts - jquery enqueued in functions.php  -->
<?php wp_footer(); ?> 
<script src="<?php echo get_template_directory_uri(); ?>/js/plugins.js"></script>
<script src="<?php echo get_template_directory_uri(); ?>/js/script.js"></script>

<!--[if (lt IE 9) & (!IEMobile)]>
<script src="<?php echo get_template_directory_uri(); ?>js/libs/imgsizer.js"></script>
<![endif]-->

<script>
// Respond.js
yepnope({
    test : Modernizr.mq('(min-width)'),
	nope : ['<?php echo get_template_directory_uri(); ?>/js/libs/respond.min.js']
});
</script>

<script>
    var _gaq=[['_setAccount','UA-XXXXX-X'],['_trackPageview']];
    (function(d,t){var g=d.createElement(t),s=d.getElementsByTagName(t)[0];
    g.src=('https:'==location.protocol?'//ssl':'//www')+'.google-analytics.com/ga.js';
    s.parentNode.insertBefore(g,s)}(document,'script'));
</script>

</body>
</html>