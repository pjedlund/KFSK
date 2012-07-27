/*
 * jQuery Responsive menu plugin by Matt Kersley
 * Converts menus into a select elements for mobile devices and low browser widths
 * github.com/mattkersley/Responsive-Menu
 */
(function(b){var c=0;b.fn.mobileMenu=function(g){function f(a){return a.attr("id")?b("#mobileMenu_"+a.attr("id")).length>0:(c++,a.attr("id","mm"+c),b("#mobileMenu_mm"+c).length>0)}function h(a){a.hide();b("#mobileMenu_"+a.attr("id")).show()}function k(a){if(a.is("ul, ol")){var e='<select id="mobileMenu_'+a.attr("id")+'" class="mobileMenu">';e+='<option value="">'+d.topOptionText+"</option>";a.find("li").each(function(){var a="",c=b(this).parents("ul, ol").length;for(i=1;i<c;i++)a+=d.indentString;
c=b(this).find("a:first-child").attr("href");a+=b(this).clone().children("ul, ol").remove().end().text();e+='<option value="'+c+'">'+a+"</option>"});e+="</select>";a.parent().append(e);b("#mobileMenu_"+a.attr("id")).change(function(){var a=b(this);if(a.val()!==null)document.location.href=a.val()});h(a)}else alert("mobileMenu will only work with UL or OL elements!")}function j(a){b(window).width()<d.switchWidth&&!f(a)?k(a):b(window).width()<d.switchWidth&&f(a)?h(a):!(b(window).width()<d.switchWidth)&&
f(a)&&(a.show(),b("#mobileMenu_"+a.attr("id")).hide())}var d={switchWidth:768,topOptionText:"Select a page",indentString:"&nbsp;&nbsp;&nbsp;"};return this.each(function(){g&&b.extend(d,g);var a=b(this);b(window).resize(function(){j(a)});j(a)})}})(jQuery);

/*
  JQUERY.BASELINEALIGN-1.0.JS
  http://baselinealign.mattwilcox.net
  https://github.com/MattWilcox/jQuery-Baseline-Align
  This plugin operates on a given set of images, it:
    * detects the docuemnt baseline
    * applies the margin needed to ensure the baseline is maintained 
*/
(function(a){var b={baselineAlign:function(b){var c=a.extend({container:false},b);return this.each(function(){var b=a(this);if(b.css("display")==="inline"){return}b.attr("style","");var d=Math.floor(b.height());b.css("height",d);var e=parseFloat(a("html").css("line-height").replace("px",""));var f=parseFloat(a("html").css("font-size").replace("px",""));var g=d;if(c.container!==false&&b.parents(c.container).length>0){var h=b.parents(c.container);h.attr("style","");var i=Math.ceil(h.height());h.css("height",i);g=Math.floor(h.outerHeight(false))}var j=parseFloat(g%e);var k=parseFloat(e-j);if(k<e/4){k=k+e}if(c.container===false){b.css("margin-bottom",k+"px");return}if(b.parents(c.container).length>0){b.parents(c.container).css("margin-bottom",k+"px");return}b.css("margin-bottom",k+"px")})},init:function(){var c=false;var d=false;var e=this;var f=arguments;a(window).resize(function(){c=true});a(window).load(b.baselineAlign.apply(e,f));setInterval(function(){if(c){c=false;return b.baselineAlign.apply(e,f)}},500)}};a.fn.baselineAlign=function(c){if(b[c]){return b[c].apply(this,Array.prototype.slice.call(arguments,1))}else if(typeof c==="object"||!c){return b.init.apply(this,arguments)}else{a.error("Method "+c+" does not exist on jQuery.baselineAlign")}}})(jQuery)


/* A fix for the iOS orientationchange zoom bug.
Script by @scottjehl, rebound by @wilto.MIT License.*/
(function(m){if(!(/iPhone|iPad|iPod/.test(navigator.platform)&&navigator.userAgent.indexOf("AppleWebKit")>-1)){return}var l=m.document;if(!l.querySelector){return}var n=l.querySelector("meta[name=viewport]"),a=n&&n.getAttribute("content"),k=a+",maximum-scale=1",d=a+",maximum-scale=10",g=true,j,i,h,c;if(!n){return}function f(){n.setAttribute("content",d);g=true}function b(){n.setAttribute("content",k);g=false}function e(o){c=o.accelerationIncludingGravity;j=Math.abs(c.x);i=Math.abs(c.y);h=Math.abs(c.z);if(!m.orientation&&(j>7||((h>6&&i<8||h<8&&i>6)&&j>5))){if(g){b()}}else{if(!g){f()}}}m.addEventListener("orientationchange",f,false);m.addEventListener("devicemotion",e,false)})(this);

/* jQuery snipped that globally enables placeholder attribute in older browsers */
/* http://pea.rs/forms/top-labels */
$(function(){
  var d = "placeholder" in document.createElement("input");
  if (!d){
	  $("input[placeholder]").each(function(){
		  $(this).val(element.attr("placeholder")).addClass('placeholder');
	  }).bind('focus',function(){
		  if ($(this).val() == element.attr('placeholder')){
			  $(this).val('').removeClass('placeholder');
		  }
	  }).bind('blur',function(){
		  if ($(this).val() == ''){
			  $(this).val(element.attr("placeholder")).addClass('placeholder');
		  }
	  });
  }
});
