function AtD_restore_text_area(){AtD.remove("content");var a;navigator.appName=="Microsoft Internet Explorer"?a=jQuery("#content").html().replace(/<BR.*?class.*?atd_remove_me.*?>/gi,"\n"):a=jQuery("#content").html();jQuery("#content").replaceWith(AtD.content_canvas);jQuery("#content").val(a.replace(/\&lt\;/g,"<").replace(/\&gt\;/g,">").replace(/\&amp;/g,"&"));jQuery("#content").height(AtD.height);jQuery(AtD_qtbutton).val(AtD.getLang("button_proofread","proofread"));jQuery(AtD_qtbutton).css({color:"#464646"});jQuery(AtD_qtbutton).siblings("input").andSelf().attr("disabled",!1);AtD.autosave!=undefined&&(autosave=AtD.autosave)}function AtD_restore_if_proofreading(){jQuery(AtD_qtbutton).val()==AtD.getLang("button_edit_text","edit text")&&AtD_restore_text_area()}function AtD_unbind_proofreader_listeners(){jQuery("#save-post, #post-preview, #publish, #edButtonPreview").unbind("focus",AtD_restore_if_proofreading);jQuery("#add_poll, #add_image, #add_video, #add_audio, #add_media").unbind("click",AtD_restore_if_proofreading);jQuery("#post").unbind("submit",AtD_restore_if_proofreading)}function AtD_bind_proofreader_listeners(){jQuery("#save-post, #post-preview, #publish, #edButtonPreview").focus(AtD_restore_if_proofreading);jQuery("#add_poll, #add_image, #add_video, #add_audio, #add_media").click(AtD_restore_if_proofreading);jQuery("#post").submit(AtD_restore_if_proofreading)}function AtD_check(a){var b;if(jQuery.isFunction(a)){b=a;AtD_qtbutton||(AtD_qtbutton=jQuery("#qt_content_AtD, #ed_AtD").get(0))}else{a.id||(a=a[0]);AtD_qtbutton=a}if(!jQuery("#content").size()){"undefined"!=typeof b&&b(0);AtD_restore_if_proofreading();return}if(jQuery(AtD_qtbutton).val()==AtD.getLang("button_edit_text","edit text"))AtD_restore_text_area();else{if(!AtD.height){AtD.height=jQuery("#content").height();AtD_bind_proofreader_listeners();jQuery("#edButtonPreview").attr("onclick",null).click(function(){AtD_restore_if_proofreading();switchEditors.go("content","tinymce")});AtD.content_canvas=jQuery("#content");AtD.autosave=autosave}jQuery(AtD_qtbutton).css({color:"red"}).val(AtD.getLang("button_edit_text","edit text")).attr("disabled",!0);var c=jQuery("#content").val().replace(/\&/g,"&amp;").replace(/\</g,"&lt;").replace(/\>/g,"&gt;");if(navigator.appName=="Microsoft Internet Explorer"){c=c.replace(/[\n\r\f]/gm,'<BR class="atd_remove_me">');var d=jQuery('<div class="input" id="content" style="height: 170px">'+c+"</div>");jQuery("#content").replaceWith(d);d.css({overflow:"auto","background-color":"white",color:"black"})}else{jQuery("#content").replaceWith('<div class="input" id="content">'+c+"</div>");jQuery("#content").css({overflow:"auto","background-color":"white",color:"black","white-space":"pre-wrap"});jQuery("#content").height(AtD.height)}autosave=function(){};jQuery(AtD_qtbutton).siblings("input").andSelf().attr("disabled",!0);AtD.check("content",{success:function(a){a==0&&typeof b!="function"&&alert(AtD.getLang("message_no_errors_found","No writing errors were found"));AtD_restore_if_proofreading()},ready:function(a){jQuery(AtD_qtbutton).attr("disabled",!1);typeof b=="function"&&b(a)},error:function(a){jQuery(AtD_qtbutton).attr("disabled",!1);typeof b=="function"?b(-1):alert(AtD.getLang("message_server_error","There was a problem communicating with the Proofreading service. Try again in one minute."));AtD_restore_if_proofreading()},editSelection:function(a){var b=prompt(AtD.getLang("dialog_replace_selection","Replace selection with:"),a.text());b!=null&&a.replaceWith(b)},explain:function(a){var b=screen.width/2-240,c=screen.height/2-190;window.open(a,"","width=480,height=380,toolbar=0,status=0,resizable=0,location=0,menuBar=0,left="+b+",top="+c).focus()},ignore:function(a){jQuery.ajax({type:"GET",url:AtD.rpc_ignore+encodeURI(a).replace(/&/g,"%26"),format:"raw",error:function(a,b,c){AtD.callback_f!=undefined&&AtD.callback_f.error!=undefined&&AtD.callback_f.error(b+": "+c)}})}})}}var AtD_qtbutton;if(typeof QTags!="undefined"&&QTags.addButton)jQuery(document).ready(function(a){QTags.addButton("AtD",AtD_l10n_r0ar.button_proofread,AtD_check)});else{edButtons[edButtons.length]=new edButton("ed_AtD","AtD","","","");jQuery(document).ready(function(a){a("#ed_AtD").replaceWith('<input type="button" id="ed_AtD" accesskey="" class="ed_button" onclick="AtD_check(this);" value="'+AtD_l10n_r0ar.button_proofread+'" />')})};