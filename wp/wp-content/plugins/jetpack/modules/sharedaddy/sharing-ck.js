(function(a){a.fn.extend({share_is_email:function(a){return/^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i.test(this.val())}});a(document).ready(function(){a(".sharing a.sharing-anchor").click(function(){return!1});a(".sharing a").each(function(){a(this).attr("href")&&a(this).attr("href").indexOf("share=")!=-1&&a(this).attr("href",a(this).attr("href")+"&nb=1")});a(".sharing a.sharing-anchor").hover(function(){if(a(this).data("hasappeared")!==!0){var b=a(this).parents("div:first").find(".inner"),c=a(this),d=setTimeout(function(){a("#sharing_email").slideUp(200);a(b).css({left:a(c).position().left+"px",top:a(c).position().top+a(c).height()+3+"px"}).slideDown(200,function(){a(c).data("hasappeared",!0).data("hasoriginal",!0).data("hasitem",!1);a(b).mouseleave(d).mouseenter(e);a(c).mouseleave(f).mouseenter(g);a(c).click(h)});var d=function(){a(c).data("hasitem",!1);if(a(c).data("hasoriginal")===!1){var b=setTimeout(h,800);a(c).data("timer2",b)}},e=function(){a(c).data("hasitem",!0);clearTimeout(a(c).data("timer2"))},f=function(){a(c).data("hasoriginal",!1);if(a(c).data("hasitem")===!1){var b=setTimeout(h,800);a(c).data("timer2",b)}},g=function(){a(c).data("hasoriginal",!0);clearTimeout(a(c).data("timer2"))},h=function(){b.slideUp(200);a(c).unbind("mouseleave",f).unbind("mouseenter",g);a(b).unbind("mouseleave",d).unbind("mouseenter",d);a(c).data("hasappeared",!1);a(c).unbind("click",h);return!1}},200);a(this).data("timer",d)}},function(){clearTimeout(a(this).data("timer"));a(this).data("timer",!1)});a(".sharing ul").each(function(b){printUrl=function(b,c){a("body:first").append('<iframe style="position:fixed;top:100;left:100;height:1px;width:1px;border:none;" id="printFrame-'+b+'" name="printFrame-'+b+'" src="'+c+'" onload="frames[\'printFrame-'+b+"'].focus();frames['printFrame-"+b+"'].print();\"></iframe>")};a(this).find(".share-print a").click(function(){ref=a(this).attr("href");var b=function(){if(ref.indexOf("#print")==-1){uid=(new Date).getTime();printUrl(uid,ref)}else print()};a(this).parents(".sharing-hidden").length>0?a(this).parents(".inner").slideUp(0,function(){b()}):b();return!1});a(this).find(".share-press-this a").click(function(){var b="";window.getSelection?b=window.getSelection():document.getSelection?b=document.getSelection():document.selection&&(b=document.selection.createRange().text);b&&a(this).attr("href",a(this).attr("href")+"&sel="+encodeURI(b));window.open(a(this).attr("href"),"t","toolbar=0,resizable=1,scrollbars=1,status=1,width=720,height=570")||(document.location.href=a(this).attr("href"));return!1});a(this).find(".share-email a").click(function(){var b=a(this).attr("href");if(a("#sharing_email").is(":visible"))a("#sharing_email").slideUp(200);else{a(".sharing .inner").slideUp();a("#sharing_email .response").remove();a("#sharing_email form").show();a("#sharing_email form input[type=submit]").removeAttr("disabled");a("#sharing_email form a.sharing_cancel").show();a("#sharing_email").css({left:a(this).offset().left+"px",top:a(this).offset().top+a(this).height()+"px"}).slideDown(200);a("#sharing_email a.sharing_cancel").unbind("click").click(function(){a("#sharing_email .errors").hide();a("#sharing_email").slideUp(200);a("#sharing_background").fadeOut();return!1});a("#sharing_email input[type=submit]").unbind("click").click(function(){var c=a(this).parents("form");a(this).attr("disabled","disabled");c.find("a.sharing_cancel").hide();c.find("img.loading").show();a("#sharing_email .errors").hide();a("#sharing_email .error").removeClass("error");a("#sharing_email input[name=source_email]").share_is_email()==0&&a("#sharing_email input[name=source_email]").addClass("error");a("#sharing_email input[name=target_email]").share_is_email()==0&&a("#sharing_email input[name=target_email]").addClass("error");if(a("#sharing_email .error").length==0){a.ajax({url:b,type:"POST",data:c.serialize(),success:function(b){c.find("img.loading").hide();if(b=="1"||b=="2"||b=="3"){a("#sharing_email .errors-"+b).show();c.find("input[type=submit]").removeAttr("disabled");c.find("a.sharing_cancel").show()}else{a("#sharing_email form").hide();a("#sharing_email").append(b);a("#sharing_email a.sharing_cancel").click(function(){a("#sharing_email").slideUp(200);a("#sharing_background").fadeOut();return!1})}}});return!1}c.find("img.loading").hide();c.find("input[type=submit]").removeAttr("disabled");c.find("a.sharing_cancel").show();a("#sharing_email .errors-1").show();return!1})}return!1})});a("li.share-email, li.share-custom a.sharing-anchor").addClass("share-service-visible")})})(jQuery);