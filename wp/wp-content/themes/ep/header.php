<!doctype html>
<!--[if lt IE 7]> <html class="no-js lt-ie9 lt-ie8 lt-ie7" lang="en"> <![endif]-->
<!--[if IE 7]>    <html class="no-js lt-ie9 lt-ie8" lang="en"> <![endif]-->
<!--[if IE 8]>    <html class="no-js lt-ie9" lang="en"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" lang="en"> <!--<![endif]-->

<head>

<meta charset="utf-8" />
<title><?php wp_title('&laquo;', true, 'right'); ?><?php bloginfo('name'); ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<!--[if (lt IE 9) & (!IEMobile)]>
<script src="<?php echo get_template_directory_uri(); ?>/js/libs/selectivizr-min.js"></script>
<![endif]-->

<?php wp_head(); ?>

<!--[if ! lte IE 6]><!-->
<link rel="stylesheet" href="<?php $theme_data = get_theme_data(TEMPLATEPATH . '/style.css'); bloginfo('stylesheet_url'); echo '?' . $theme_data['Version']; ?>" />
<!--<![endif]-->

<!--[if lte IE 6]>
<link rel="stylesheet" type="text/css" media="screen, projection" href="http://universal-ie6-css.googlecode.com/files/ie6.1.1.css">
<![endif]-->

</head>

<body <?php body_class(); ?>>

<!--[if lt IE 7]><p class=chromeframe>Din webbläsare är <em>uråldrig!</em> <a href="http://browsehappy.com/">Uppgradera till en bättre webbläsare</a> eller <a href="http://www.google.com/chromeframe/?redirect=true">installera Google Chrome Frame</a> för att se denna sida.</p><![endif]-->

<div id="StickyFooter">

<div id="HeaderWrapper">
<header role="banner" class="clearfix">

<h1 id="Logo" class="h1"><a href="<?php echo home_url(); ?>">Trust<span>partner</span></a></h1>

<ul id="Accessibility">
<li><a href="#ContentWrapper">Hoppa till innehåll</a></li>
<li><a href="#Navigation">Hoppa till navigering</a></li>
</ul>

<!-- Search -->
<?php include( TEMPLATEPATH . '/searchform.php' ); ?>

</header>
</div><!-- end HeaderWrapper -->

<nav id="Navigation" role="navigation" class="clearfix">
<?php wp_nav_menu( array( 'theme_location' => 'huvudnavigering', 'container' => '' ) ); ?>
</nav>
