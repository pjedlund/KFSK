<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="sv" lang="sv"><head>

<!-- Site by Edlund Design [www.edlunddesign.com]  -->

<title><?php wp_title('&laquo;', true, 'right'); ?><?php bloginfo('name'); ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="verify-v1" content="ck62T8qz7qj2PYnQh+7+HmK3mFqK/67GeH/JgDU5p2I=" />

<meta http-equiv="imagetoolbar" content="false" />
<meta http-equiv="content-language" content="sv-se" />
<meta name="google-site-verification" content="i9bY7Y0yS_QSG8mBqVTfA7ZE4QnC5Q3bKSEpSeY1heo" />

<?php wp_head(); ?>

<!--[if ! lte IE 6]><!-->
<link rel="stylesheet" href="<?php $theme_data = get_theme_data(TEMPLATEPATH . '/style.css'); bloginfo('stylesheet_url'); echo '?' . $theme_data['Version']; ?>" />
<!--<![endif]-->

<!--[if lte IE 6]>
<link rel="stylesheet" type="text/css" media="screen, projection" href="http://universal-ie6-css.googlecode.com/files/ie6.1.1.css" />
<![endif]-->

<script type="text/javascript" src="<?php bloginfo('template_directory'); ?>/js/swfobject.js"></script>

<script type="text/javascript">(function(d,c){var a,b,g,e;a=d.createElement("script");a.type="text/javascript";a.async=!0;a.src=("https:"===d.location.protocol?"https:":"http:")+'//api.mixpanel.com/site_media/js/api/mixpanel.2.js';b=d.getElementsByTagName("script")[0];b.parentNode.insertBefore(a,b);c._i=[];c.init=function(a,d,f){var b=c;"undefined"!==typeof f?b=c[f]=[]:f="mixpanel";g="disable track track_links track_forms register register_once unregister identify name_tag set_config".split(" ");for(e=0;e<
g.length;e++)(function(a){b[a]=function(){b.push([a].concat(Array.prototype.slice.call(arguments,0)))}})(g[e]);c._i.push([a,d,f])};window.mixpanel=c})(document,[]);
mixpanel.init("8d6957f79a31294f24da7884d5c53cd5");</script>
	
</head>

<body <?php body_class(); ?>> 

<div id="Container">

<div id="Header">
<h1 id="Logo"><a href="<?php bloginfo('url'); ?>" title="">Malmö Biljardklubb</a></h1>

<ul id="Accessibility">
<li><a href="#Content">Hoppa till innehåll</a></li>
<li><a href="#Nav">Hoppa till navigering</a></li>
</ul>

<?php wp_nav_menu( array( 'theme_location' => 'huvudnavigering', 'container' => '' ) ); ?>

</div><!-- end Header -->