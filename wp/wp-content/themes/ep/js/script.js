$(document).ready(function() {

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
    var activeOpacity   = 0.4,
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