// usage: log('inside coolFunc', this, arguments);
window.log=function(){log.history=log.history||[];log.history.push(arguments);if(this.console){arguments.callee=arguments.callee.caller;console.log(Array.prototype.slice.call(arguments))}};(function(a){function b(){}for(var c="assert,count,debug,dir,dirxml,error,exception,group,groupCollapsed,groupEnd,info,log,markTimeline,profile,profileEnd,time,timeEnd,trace,warn".split(","),d;d=c.pop();)a[d]=a[d]||b})(window.console=window.console||{});(function(a){var b=0;a.fn.mobileMenu=function(d){function e(d){return d.attr("id")?a("#mobileMenu_"+d.attr("id")).length>0:(b++,d.attr("id","mm"+b),a("#mobileMenu_mm"+b).length>0)}function f(b){b.hide();a("#mobileMenu_"+b.attr("id")).show()}function g(b){if(b.is("ul, ol")){var c='<select id="mobileMenu_'+b.attr("id")+'" class="mobileMenu">';c+='<option value="">'+j.topOptionText+"</option>";b.find("li").each(function(){var b="",d=a(this).parents("ul, ol").length;for(i=1;i<d;i++)b+=j.indentString;d=a(this).find("a:first-child").attr("href");b+=a(this).clone().children("ul, ol").remove().end().text();c+='<option value="'+d+'">'+b+"</option>"});c+="</select>";b.parent().append(c);a("#mobileMenu_"+b.attr("id")).change(function(){var b=a(this);b.val()!==null&&(document.location.href=b.val())});f(b)}else alert("mobileMenu will only work with UL or OL elements!")}function h(b){a(window).width()<j.switchWidth&&!e(b)?g(b):a(window).width()<j.switchWidth&&e(b)?f(b):!(a(window).width()<j.switchWidth)&&e(b)&&(b.show(),a("#mobileMenu_"+b.attr("id")).hide())}var j={switchWidth:768,topOptionText:"Select a page",indentString:"&nbsp;&nbsp;&nbsp;"};return this.each(function(){d&&a.extend(j,d);var b=a(this);a(window).resize(function(){h(b)});h(b)})}})(jQuery);(function(a){function b(a,b){for(var c=a,d=0;a=a[b];)c.tagName==a.tagName&&d++;return d}function c(a,c,d){a=b(a,d);if(c=="odd"||c=="even")d=2,a-=c!="odd";else{var f=c.indexOf("n");f>-1?(d=parseInt(c,10)||parseInt(c.substring(0,f)+"1",10),a-=(parseInt(c.substring(f+1),10)||0)-1):(d=a+1,a-=parseInt(c,10)-1)}return(d<0?a<=0:a>=0)&&a%d==0}var d={"first-of-type":function(a){return b(a,"previousSibling")==0},"last-of-type":function(a){return b(a,"nextSibling")==0},"only-of-type":function(a){return d["first-of-type"](a)&&d["last-of-type"](a)},"nth-of-type":function(a,b,d){return c(a,d[3],"previousSibling")},"nth-last-of-type":function(a,b,d){return c(a,d[3],"nextSibling")}};a.extend(a.expr[":"],d)})(jQuery);(function(a,b,c){function f(a){var b={},d=/^jQuery\d+$/;c.each(a.attributes,function(a,c){c.specified&&!d.test(c.name)&&(b[c.name]=c.value)});return b}function g(){var a=c(this);a.val()===a.attr("placeholder")&&a.hasClass("placeholder")&&(a.data("placeholder-password")?a.hide().next().show().focus().attr("id",a.removeAttr("id").data("placeholder-id")):a.val("").removeClass("placeholder"))}function h(){var a,b=c(this),d=b,e=this.id;if(b.val()===""){if(b.is(":password")){if(!b.data("placeholder-textinput")){try{a=b.clone().attr({type:"text"})}catch(h){a=c("<input>").attr(c.extend(f(this),{type:"text"}))}a.removeAttr("name").data("placeholder-password",!0).data("placeholder-id",e).bind("focus.placeholder",g);b.data("placeholder-textinput",a).data("placeholder-id",e).before(a)}b=b.removeAttr("id").hide().prev().attr("id",e).show()}b.addClass("placeholder").val(b.attr("placeholder"))}else b.removeClass("placeholder")}var d="placeholder"in b.createElement("input"),e="placeholder"in b.createElement("textarea");if(d&&e){c.fn.placeholder=function(){return this};c.fn.placeholder.input=c.fn.placeholder.textarea=!0}else{c.fn.placeholder=function(){return this.filter((d?"textarea":":input")+"[placeholder]").bind("focus.placeholder",g).bind("blur.placeholder",h).trigger("blur.placeholder").end()};c.fn.placeholder.input=d;c.fn.placeholder.textarea=e;c(function(){c("form").bind("submit.placeholder",function(){var a=c(".placeholder",this).each(g);setTimeout(function(){a.each(h)},10)})});c(a).bind("unload.placeholder",function(){c(".placeholder").val("")})}})(this,document,jQuery);