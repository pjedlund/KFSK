<!doctype html>
<!--[if lt IE 7]> <html class="no-js lt-ie9 lt-ie8 lt-ie7" lang="en"> <![endif]-->
<!--[if IE 7]>    <html class="no-js lt-ie9 lt-ie8" lang="en"> <![endif]-->
<!--[if IE 8]>    <html class="no-js lt-ie9" lang="en"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" <?php language_attributes( $doctype ) ?>> <!--<![endif]-->

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

<script src="<?php echo get_template_directory_uri(); ?>/js/libs/modernizr-2.5.0.min.js"></script>

</head>

<body <?php body_class(); ?>>

<header role="banner" class="clearfix">

<ul id="Accessibility">
    <li><a href="#Content">Hoppa till innehåll</a></li>
    <li><a href="#Navigation">Hoppa till navigering</a></li>
    <li><a href="#Search">Hoppa till sökruta</a></li>
</ul>

<!--
<nav id="Enkolumnsnavigering" role="navigation" class="clearfix">
<?php wp_nav_menu( array( 'theme_location' => 'enkolumnnsavigering', 'container' => '' ) ); ?>
</nav>
-->

</header>
