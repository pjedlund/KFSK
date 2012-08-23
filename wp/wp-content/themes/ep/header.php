<!doctype html>
<!--[if lt IE 7]> <html class="no-js lt-ie9 lt-ie8 lt-ie7" <?php language_attributes(); ?>> <![endif]-->
<!--[if IE 7]>    <html class="no-js lt-ie9 lt-ie8" <?php language_attributes(); ?>> <![endif]-->
<!--[if IE 8]>    <html class="no-js lt-ie9" <?php language_attributes(); ?>> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" <?php language_attributes(); ?>> <!--<![endif]-->

<head>

<meta charset="utf-8" />
<title><?php wp_title('&laquo;', true, 'right'); ?><?php bloginfo('name'); ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<link rel="icon" href="http://edlunddesign.com/dev/kfsk/favicon.ico" />

<!--[if (lt IE 9) & (!IEMobile)]>
<script src="<?php echo get_template_directory_uri(); ?>/js/libs/selectivizr-min.js"></script>
<![endif]-->

<!--[if ! lte IE 6]><!-->
<link rel="stylesheet" href="<?php bloginfo( 'stylesheet_url' );$my_theme = wp_get_theme(); echo "?" . $my_theme->Version; ?>" />
<link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/js/libs/fancybox/source/jquery.fancybox.css?v=2.0.6;" />
<!--<![endif]-->

<!--[if lte IE 6]>
<link rel="stylesheet" type="text/css" media="screen, projection" href="http://universal-ie6-css.googlecode.com/files/ie6.1.1.css" />
<![endif]-->

<script src="<?php echo get_template_directory_uri(); ?>/js/libs/modernizr-2.5.3.min.js"></script>

<?php if ( is_singular() ) wp_enqueue_script( 'comment-reply' ); ?>
<?php wp_head(); ?>

</head>

<body <?php body_class(); ?>>

<header role="banner" class="clearfix">

<ul id="Accessibility">
<li><a href="#Content">Hoppa till innehåll</a></li>
<li><a href="#menu-huvudnavigering">Hoppa till navigering</a></li>
<li><a href="#searchform">Hoppa till sökruta</a></li>
</ul>

<nav id="Enkolumnsnavigering" role="navigation" class="clearfix">
<?php wp_nav_menu( array( 'theme_location' => 'enkolumnnsavigering', 'container' => 'enkolumn' ) ); ?>
</nav>

</header>
