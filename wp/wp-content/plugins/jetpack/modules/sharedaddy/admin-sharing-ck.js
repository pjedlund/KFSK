(function(a){a(document).ready(function(){function b(){a(".preview a.sharing-anchor").unbind("mouseenter mouseenter").hover(function(){if(a(this).data("hasappeared")!==!0){var b=a(this).parents("li:first").find(".inner"),c=a(this).parents(".share-custom"),d=setTimeout(function(){a(b).css({left:a(c).position().left+"px",top:a(c).position().top+a(c).height()+3+"px"}).slideDown(200,function(){a(c).data("hasappeared",!0).data("hasoriginal",!0).data("hasitem",!1);a(b).mouseleave(d).mouseenter(e);a(c).mouseleave(f).mouseenter(g);a(c).click(h)});var d=function(){a(c).data("hasitem",!1);if(a(c).data("hasoriginal")===!1){var b=setTimeout(h,800);a(c).data("timer2",b)}},e=function(){a(c).data("hasitem",!0);clearTimeout(a(c).data("timer2"))},f=function(){a(c).data("hasoriginal",!1);if(a(c).data("hasitem")===!1){var b=setTimeout(h,800);a(c).data("timer2",b)}},g=function(){a(c).data("hasoriginal",!0);clearTimeout(a(c).data("timer2"))},h=function(){b.slideUp(200);a(c).unbind("mouseleave",f).unbind("mouseenter",g);a(b).unbind("mouseleave",d).unbind("mouseenter",d);a(c).data("hasappeared",!1);a(c).unbind("click",h);return!1}},200);a(this).data("timer",d)}},function(){clearTimeout(a(this).data("timer"));a(this).data("timer",!1)})}function c(){var c;a("#live-preview ul.preview li").remove();(a("#save-enabled-shares input[name=visible]").val()!=""||a("#save-enabled-shares input[name=hidden]").val()!="")&&a("#live-preview ul.preview").append(a("#live-preview ul.archive .sharing-label").clone());a("ul.services-enabled li").each(function(){if(a(this).hasClass("service")){var b=a(this).attr("id");a("#live-preview ul.preview").append(a("#live-preview ul.archive .preview-"+b).clone())}});if(a("#save-enabled-shares input[name=hidden]").val()!=""){a("#live-preview ul.preview").append(a("#live-preview ul.archive .share-custom").clone());a("#live-preview ul.preview li.share-custom ul li").remove();a("ul.services-hidden li").each(function(b,c){if(a(this).hasClass("service")){var d=a(this).attr("id");a("#live-preview ul.preview li.share-custom ul").append(a("#live-preview ul.archive .preview-"+d).clone());b%2==1&&a("#live-preview ul.preview li.share-custom ul").append('<li class="share-end"></div>')}});b()}a("select[name=button_style]").val()=="icon"?a("#live-preview ul.preview .option").html("&nbsp;"):a("select[name=button_style]").val()=="text"&&a("#live-preview ul.preview li.advanced").each(function(){a(this).find(".option").hasClass("option-smart-on")===!1&&a(this).find(".option").hasClass("option-smart-like")===!1&&a(this).attr("class","advanced preview-item")})}function d(){var b=this;a(this).parents("li:first").css("backgroundImage",'url("'+sharing_loading_icon+'")');a(this).parents("form").ajaxSubmit(function(d){if(d.indexOf("<!---")>=0){var e=d.substring(0,d.indexOf("<!--->")),g=d.substring(d.indexOf("<!--->")+6);if(a(b).is(":submit")===!0){a(b).parents("li:first").replaceWith(e);f()}a("#live-preview ul.archive li.preview-"+a(b).parents("form").find("input[name=service]").val()).replaceWith(g)}c();a(b).parents("li:first").removeAttr("style")});return a(b).is(":submit")===!0?!1:!0}function e(){a("#enabled-services h3 img").show();a("#enabled-services li").addClass("options");a("#available-services li").removeClass("options");a("#enabled-services ul.services-enabled li.service").length>0?a("#drag-instructions").hide():a("#drag-instructions").show();a("#enabled-services li.service").length>0?a("#live-preview .services h2").hide():a("#live-preview .services h2").show();var b=[],d=[];a("ul.services-enabled li").each(function(){a(this).hasClass("service")&&(b[b.length]=a(this).attr("id"))});a("ul.services-hidden li").each(function(){a(this).hasClass("service")&&(d[d.length]=a(this).attr("id"))});a("#save-enabled-shares input[name=visible]").val(b.join(","));a("#save-enabled-shares input[name=hidden]").val(d.join(","));c();a("#save-enabled-shares").ajaxSubmit(function(){a("#enabled-services h3 img").hide()})}function f(){a(".advanced-form form input[type=checkbox]").unbind("click").click(d);a(".advanced-form form select").unbind("change").change(d);a(".advanced-form form input[type=submit]").unbind("click").click(d);a(".advanced-form form a.remove").unbind("click").click(function(){var b=a(this).parents("form");b.find("input[name=action]").val("sharing_delete_service");a(this).parents("li:first").css("backgroundImage",'url("'+sharing_loading_icon+'")');a(this).parents("form").ajaxSubmit(function(d){b.parents("li:first").fadeOut(function(){a(this).remove();c()})});return!1})}a("#enabled-services .services ul").sortable({receive:function(a,b){e()},stop:function(){e();a("li.service").enableSelection()},over:function(b,c){a(this).find("ul").addClass("dropping");a("#enabled-services li.end-fix").remove();a("#enabled-services ul").append('<li class="end-fix"></li>')},out:function(b,c){a(this).find("ul").removeClass("dropping");a("#enabled-services li.end-fix").remove();a("#enabled-services ul").append('<li class="end-fix"></li>')},helper:function(a,b){b.find(".advanced-form").hide();return b.clone()},start:function(b,c){a(".advanced-form").hide();a("li.service").disableSelection()},placeholder:"dropzone",opacity:.8,delay:150,forcePlaceholderSize:!0,items:"li",connectWith:"#available-services ul, #enabled-services .services ul",cancel:".advanced-form"});a("#available-services ul").sortable({opacity:.8,delay:150,cursor:"move",connectWith:"#enabled-services .services ul",placeholder:"dropzone",forcePlaceholderSize:!0,start:function(){a(".advanced-form").hide()}});a(".options-toggle").live("click",function(){var b=a(this).parents("li:first").find(".advanced-form").is(":visible");a(".advanced-form").slideUp(200);b||a(this).parents("li:first").find(".advanced-form").slideDown(200)});a(".preview-hidden a").click(function(){a(this).parent().find(".preview").toggle();return!1});a("#new-service form").ajaxForm({beforeSubmit:function(){a("#new-service-form .error").hide();a("#new-service-form img").show();a("#new-service-form input[type=submit]").attr("disabled",!0)},success:function(b){a("#new-service-form img").hide();if(b=="1"){a("#new-service-form .inerror").removeClass("inerror").addClass("error");a("#new-service-form .error").show();a("#new-service-form input[type=submit]").attr("disabled",!1)}else document.location.reload()}});a("select[name=button_style]").change(function(){c();return!0});a("input[name=sharing_label]").blur(function(){a("#live-preview ul.preview li.sharing-label").html(a("<div/>").text(a(this).val()).html())});f();b()})})(jQuery);