<footer role="contentinfo">
<p><small>Sidfot: Nam pulvinar, odio sed rhoncus suscipit, sem diam ultrices mauris, eu consequat purus metus eu velit. Proin metus odio, aliquam eget <a href="#">molestie nec</a>, gravida ut sapien. Phasellus quis est sed turpis sollicitudin venenatis sed eu odio. Praesent eget neque eu eros interdum malesuada non vel leo. Sed fringilla porta ligula egestas tincidunt. Nullam risus magna, ornare vitae varius eget, scelerisque a libero. Morbi eu. Nullam lorem nisi, posuere <a href="#">porttitor ipsum</a> quis volutpat eget, luctus nec massa. Pellentesque.</small></p>
</footer>

<?php wp_footer(); ?>

<!-- Scripts - jquery enqueued in functions.php  --> 
<script src="<?php echo get_template_directory_uri(); ?>/js/libs/fancybox/source/jquery.fancybox.pack.js"></script>
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