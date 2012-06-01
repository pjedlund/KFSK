/*
 * jQuery Responsive menu plugin by Matt Kersley
 * Converts menus into a select elements for mobile devices and low browser widths
 * github.com/mattkersley/Responsive-Menu
 */(function(a){var b=0;a.fn.mobileMenu=function(d){function e(d){return d.attr("id")?a("#mobileMenu_"+d.attr("id")).length>0:(b++,d.attr("id","mm"+b),a("#mobileMenu_mm"+b).length>0)}function f(b){b.hide();a("#mobileMenu_"+b.attr("id")).show()}function g(b){if(b.is("ul, ol")){var c='<select id="mobileMenu_'+b.attr("id")+'" class="mobileMenu">';c+='<option value="">'+j.topOptionText+"</option>";b.find("li").each(function(){var b="",d=a(this).parents("ul, ol").length;for(i=1;i<d;i++)b+=j.indentString;d=a(this).find("a:first-child").attr("href");b+=a(this).clone().children("ul, ol").remove().end().text();c+='<option value="'+d+'">'+b+"</option>"});c+="</select>";b.parent().append(c);a("#mobileMenu_"+b.attr("id")).change(function(){var b=a(this);b.val()!==null&&(document.location.href=b.val())});f(b)}else alert("mobileMenu will only work with UL or OL elements!")}function h(b){a(window).width()<j.switchWidth&&!e(b)?g(b):a(window).width()<j.switchWidth&&e(b)?f(b):!(a(window).width()<j.switchWidth)&&e(b)&&(b.show(),a("#mobileMenu_"+b.attr("id")).hide())}var j={switchWidth:768,topOptionText:"Select a page",indentString:"&nbsp;&nbsp;&nbsp;"};return this.each(function(){d&&a.extend(j,d);var b=a(this);a(window).resize(function(){h(b)});h(b)})}})(jQuery);(function(a){var b={baselineAlign:function(b){var c=a.extend({container:!1},b);return this.each(function(){var b=a(this);if(b.css("display")==="inline")return;b.attr("style","");var d=Math.floor(b.height());b.css("height",d);var e=parseFloat(a("html").css("line-height").replace("px","")),f=parseFloat(a("html").css("font-size").replace("px","")),g=d;if(c.container!==!1&&b.parents(c.container).length>0){var h=b.parents(c.container);h.attr("style","");var i=Math.ceil(h.height());h.css("height",i);g=Math.floor(h.outerHeight(!1))}var j=parseFloat(g%e),k=parseFloat(e-j);k<e/4&&(k+=e);if(c.container===!1){b.css("margin-bottom",k+"px");return}if(b.parents(c.container).length>0){b.parents(c.container).css("margin-bottom",k+"px");return}b.css("margin-bottom",k+"px")})},init:function(){var c=!1,d=!1,e=this,f=arguments;a(window).resize(function(){c=!0});a(window).load(b.baselineAlign.apply(e,f));setInterval(function(){if(c){c=!1;return b.baselineAlign.apply(e,f)}},500)}};a.fn.baselineAlign=function(c){if(b[c])return b[c].apply(this,Array.prototype.slice.call(arguments,1));if(typeof c=="object"||!c)return b.init.apply(this,arguments);a.error("Method "+c+" does not exist on jQuery.baselineAlign")}})(jQuery)(function(a){function l(){c.setAttribute("content",f);g=!0}function m(){c.setAttribute("content",e);g=!1}function n(b){k=b.accelerationIncludingGravity;h=Math.abs(k.x);i=Math.abs(k.y);j=Math.abs(k.z);!a.orientation&&(h>7||(j>6&&i<8||j<8&&i>6)&&h>5)?g&&m():g||l()}if(!(/iPhone|iPad|iPod/.test(navigator.platform)&&navigator.userAgent.indexOf("AppleWebKit")>-1))return;var b=a.document;if(!b.querySelector)return;var c=b.querySelector("meta[name=viewport]"),d=c&&c.getAttribute("content"),e=d+",maximum-scale=1",f=d+",maximum-scale=10",g=!0,h,i,j,k;if(!c)return;a.addEventListener("orientationchange",l,!1);a.addEventListener("devicemotion",n,!1)})(this);