jQuery.fn.fadeSliderToggle=function(a){a=jQuery.extend({speed:500,easing:"swing"},a);caller=this;jQuery(caller).css("display")=="none"?jQuery(caller).animate({opacity:1,height:"toggle"},a.speed,a.easing):jQuery(caller).animate({opacity:0,height:"toggle"},a.speed,a.easing)};