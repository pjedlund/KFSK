<footer role="contentinfo">

<ul class="wrapper"><!-- start widget ul-wrapper -->
<?php dynamic_sidebar('Footer'); ?>
</ul><!-- end widget ul-wrapper -->

<div class="hr"></div><hr />

<p>Publiceras stolt med <a href="http://wordpress.org/">WordPress</a>.</p>

</footer>

<?php wp_footer(); ?>

<!-- Scripts - jquery enqueued in functions.php  --> 
<script src="<?php echo get_template_directory_uri(); ?>/js/libs/fancybox/source/jquery.fancybox.pack.js"></script>
<script src="<?php echo get_template_directory_uri(); ?>/js/plugins.js"></script>
<script src="<?php echo get_template_directory_uri(); ?>/js/script.js"></script>

<!--[if (lt IE 9) & (!IEMobile)]>
<script src="<?php echo get_template_directory_uri(); ?>/js/libs/imgsizer.js"></script>
<![endif]-->  

<script>
// Respond.js
yepnope({
    test : Modernizr.mq('(min-width)'),
	nope : ['<?php echo get_template_directory_uri(); ?>/js/libs/respond.min.js']
});
</script>

<!--
<script>
    var _gaq=[['_setAccount','UA-XXXXX-X'],['_trackPageview']];
    (function(d,t){var g=d.createElement(t),s=d.getElementsByTagName(t)[0];
    g.src=('https:'==location.protocol?'//ssl':'//www')+'.google-analytics.com/ga.js';
    s.parentNode.insertBefore(g,s)}(document,'script'));
</script>
-->

</body>
</html>