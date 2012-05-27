/* A fix for iPhone viewport scale bug */
/* http://www.blog.highub.com/mobile-2/a-fix-for-iphone-viewport-scale-bug/ */
(function (document) {
  window.scale = window.scale || {};
  scale.viewportmeta = document.querySelector && document.querySelector('meta[name="viewport"]');
  scale.ua = navigator.userAgent;
  scale.iOS = function () {
    if (scale.viewportmeta && /iPhone|iPad/.test(scale.ua) && !/Opera Mini/.test(scale.ua)) {
      scale.viewportmeta.content = "width=device-width,minimum-scale=1,maximum-scale=1";
      document.addEventListener("gesturestart", scale.gestureStart, false);
    }
  };
  scale.gestureStart = function () {
    scale.viewportmeta.content = "width=device-width,minimum-scale=0.25,maximum-scale=1.6";
  };
})(document);

scale.iOS(); 

$(document).ready(function() {

    /* $('.lightbox').wrap('<figure class="wrapper" />'); */
    /* $('figure').css("background","yellow", "backgroundImage"); */

		 	$(".lightbox").fancybox({
				padding: 0,

				openEffect : 'fade',
				openSpeed  : 100,

				closeEffect : 'fade',
				closeSpeed  : 100,

				helpers : {
					overlay : {
						css : {
							'background-color' : '#8c9193'
						}
					}
				}
				
			});

});


$(window).bind("load", function() {
    var activeOpacity   = 0.5,
        inactiveOpacity = 1,
        fadeTime = 350,
        images = ".lightbox img";

    $(images).fadeTo(1, inactiveOpacity);

    $(images).hover(
        function(){
            $(this).fadeTo(fadeTime, activeOpacity);
        }, function(){
            $(this).fadeTo(fadeTime, inactiveOpacity);
        }, function(){
            $(this).fadeTo(fadeTime, 0.1);
        });
        
     $(images).click(function() {
        $(this).fadeTo(fadeTime, inactiveOpacity);
     });
});