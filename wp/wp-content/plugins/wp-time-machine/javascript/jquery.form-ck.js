/*!
 * jQuery Form Plugin
 * version: 2.43 (12-MAR-2010)
 * @requires jQuery v1.3.2 or later
 *
 * Examples and documentation at: http://malsup.com/jquery/form/
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 */(function(a){function b(){if(a.fn.ajaxSubmit.debug){var b="[jquery.form] "+Array.prototype.join.call(arguments,"");window.console&&window.console.log?window.console.log(b):window.opera&&window.opera.postError&&window.opera.postError(b)}}a.fn.ajaxSubmit=function(c){function q(){function q(){var b=j.attr("target"),c=j.attr("action");d.setAttribute("target",g);d.getAttribute("method")!="POST"&&d.setAttribute("method","POST");d.getAttribute("action")!=e.url&&d.setAttribute("action",e.url);e.skipEncodingOverride||j.attr({encoding:"multipart/form-data",enctype:"multipart/form-data"});e.timeout&&setTimeout(function(){n=!0;s()},e.timeout);var f=[];try{if(e.extraData)for(var i in e.extraData)f.push(a('<input type="hidden" name="'+i+'" value="'+e.extraData[i]+'" />').appendTo(d)[0]);h.appendTo("body");h.data("form-plugin-onload",s);d.submit()}finally{d.setAttribute("action",c);b?d.setAttribute("target",b):j.removeAttr("target");a(f).remove()}}function s(){if(m)return;var c=!0;try{if(n)throw"timeout";var d,f;f=i.contentWindow?i.contentWindow.document:i.contentDocument?i.contentDocument:i.document;var g=e.dataType=="xml"||f.XMLDocument||a.isXMLDoc(f);b("isXml="+g);if(!g&&(f.body==null||f.body.innerHTML=="")){if(--r){b("requeing onLoad callback, DOM not available");setTimeout(s,250);return}b("Could not access iframe DOM after 100 tries.");return}b("response detected");m=!0;k.responseText=f.body?f.body.innerHTML:null;k.responseXML=f.XMLDocument?f.XMLDocument:f;k.getResponseHeader=function(a){var b={"content-type":e.dataType};return b[a]};if(e.dataType=="json"||e.dataType=="script"){var j=f.getElementsByTagName("textarea")[0];if(j)k.responseText=j.value;else{var o=f.getElementsByTagName("pre")[0];o&&(k.responseText=o.innerHTML)}}else e.dataType=="xml"&&!k.responseXML&&k.responseText!=null&&(k.responseXML=t(k.responseText));d=a.httpData(k,e.dataType)}catch(p){b("error caught:",p);c=!1;k.error=p;a.handleError(e,k,"error",p)}if(c){e.success(d,"success");l&&a.event.trigger("ajaxSuccess",[k,e])}l&&a.event.trigger("ajaxComplete",[k,e]);l&&!--a.active&&a.event.trigger("ajaxStop");e.complete&&e.complete(k,c?"success":"error");setTimeout(function(){h.removeData("form-plugin-onload");h.remove();k.responseXML=null},100)}function t(a,b){if(window.ActiveXObject){b=new ActiveXObject("Microsoft.XMLDOM");b.async="false";b.loadXML(a)}else b=(new DOMParser).parseFromString(a,"text/xml");return b&&b.documentElement&&b.documentElement.tagName!="parsererror"?b:null}var d=j[0];if(a(":input[name=submit]",d).length){alert('Error: Form elements must not be named "submit".');return}var e=a.extend({},a.ajaxSettings,c),f=a.extend(!0,{},a.extend(!0,{},a.ajaxSettings),e),g="jqFormIO"+(new Date).getTime(),h=a('<iframe id="'+g+'" name="'+g+'" src="'+e.iframeSrc+'" onload="(jQuery(this).data(\'form-plugin-onload\'))()" />'),i=h[0];h.css({position:"absolute",top:"-1000px",left:"-1000px"});var k={aborted:0,responseText:null,responseXML:null,status:0,statusText:"n/a",getAllResponseHeaders:function(){},getResponseHeader:function(){},setRequestHeader:function(){},abort:function(){this.aborted=1;h.attr("src",e.iframeSrc)}},l=e.global;l&&!(a.active++)&&a.event.trigger("ajaxStart");l&&a.event.trigger("ajaxSend",[k,e]);if(f.beforeSend&&f.beforeSend(k,f)===!1){f.global&&a.active--;return}if(k.aborted)return;var m=!1,n=0,o=d.clk;if(o){var p=o.name;if(p&&!o.disabled){e.extraData=e.extraData||{};e.extraData[p]=o.value;if(o.type=="image"){e.extraData[p+".x"]=d.clk_x;e.extraData[p+".y"]=d.clk_y}}}e.forceSync?q():setTimeout(q,10);var r=100}if(!this.length){b("ajaxSubmit: skipping submit process - no element selected");return this}typeof c=="function"&&(c={success:c});var d=a.trim(this.attr("action"));d&&(d=(d.match(/^([^#]+)/)||[])[1]);d=d||window.location.href||"";c=a.extend({url:d,type:this.attr("method")||"GET",iframeSrc:/^https/i.test(window.location.href||"")?"javascript:false":"about:blank"},c||{});var e={};this.trigger("form-pre-serialize",[this,c,e]);if(e.veto){b("ajaxSubmit: submit vetoed via form-pre-serialize trigger");return this}if(c.beforeSerialize&&c.beforeSerialize(this,c)===!1){b("ajaxSubmit: submit aborted via beforeSerialize callback");return this}var f=this.formToArray(c.semantic);if(c.data){c.extraData=c.data;for(var g in c.data)if(c.data[g]instanceof Array)for(var h in c.data[g])f.push({name:g,value:c.data[g][h]});else f.push({name:g,value:c.data[g]})}if(c.beforeSubmit&&c.beforeSubmit(f,this,c)===!1){b("ajaxSubmit: submit aborted via beforeSubmit callback");return this}this.trigger("form-submit-validate",[f,this,c,e]);if(e.veto){b("ajaxSubmit: submit vetoed via form-submit-validate trigger");return this}var i=a.param(f);if(c.type.toUpperCase()=="GET"){c.url+=(c.url.indexOf("?")>=0?"&":"?")+i;c.data=null}else c.data=i;var j=this,k=[];c.resetForm&&k.push(function(){j.resetForm()});c.clearForm&&k.push(function(){j.clearForm()});if(!c.dataType&&c.target){var l=c.success||function(){};k.push(function(b){var d=c.replaceTarget?"replaceWith":"html";a(c.target)[d](b).each(l,arguments)})}else c.success&&k.push(c.success);c.success=function(a,b,d){for(var e=0,f=k.length;e<f;e++)k[e].apply(c,[a,b,d||j,j])};var m=a("input:file",this).fieldValue(),n=!1;for(var o=0;o<m.length;o++)m[o]&&(n=!0);var p=!1;m.length&&c.iframe!==!1||c.iframe||n||p?c.closeKeepAlive?a.get(c.closeKeepAlive,q):q():a.ajax(c);this.trigger("form-submit-notify",[this,c]);return this};a.fn.ajaxForm=function(b){return this.ajaxFormUnbind().bind("submit.form-plugin",function(c){c.preventDefault();a(this).ajaxSubmit(b)}).bind("click.form-plugin",function(b){var c=b.target,d=a(c);if(!d.is(":submit,input:image")){var e=d.closest(":submit");if(e.length==0)return;c=e[0]}var f=this;f.clk=c;if(c.type=="image")if(b.offsetX!=undefined){f.clk_x=b.offsetX;f.clk_y=b.offsetY}else if(typeof a.fn.offset=="function"){var g=d.offset();f.clk_x=b.pageX-g.left;f.clk_y=b.pageY-g.top}else{f.clk_x=b.pageX-c.offsetLeft;f.clk_y=b.pageY-c.offsetTop}setTimeout(function(){f.clk=f.clk_x=f.clk_y=null},100)})};a.fn.ajaxFormUnbind=function(){return this.unbind("submit.form-plugin click.form-plugin")};a.fn.formToArray=function(b){var c=[];if(this.length==0)return c;var d=this[0],e=b?d.getElementsByTagName("*"):d.elements;if(!e)return c;for(var f=0,g=e.length;f<g;f++){var h=e[f],i=h.name;if(!i)continue;if(b&&d.clk&&h.type=="image"){if(!h.disabled&&d.clk==h){c.push({name:i,value:a(h).val()});c.push({name:i+".x",value:d.clk_x},{name:i+".y",value:d.clk_y})}continue}var j=a.fieldValue(h,!0);if(j&&j.constructor==Array)for(var k=0,l=j.length;k<l;k++)c.push({name:i,value:j[k]});else j!==null&&typeof j!="undefined"&&c.push({name:i,value:j})}if(!b&&d.clk){var m=a(d.clk),n=m[0],i=n.name;if(i&&!n.disabled&&n.type=="image"){c.push({name:i,value:m.val()});c.push({name:i+".x",value:d.clk_x},{name:i+".y",value:d.clk_y})}}return c};a.fn.formSerialize=function(b){return a.param(this.formToArray(b))};a.fn.fieldSerialize=function(b){var c=[];this.each(function(){var d=this.name;if(!d)return;var e=a.fieldValue(this,b);if(e&&e.constructor==Array)for(var f=0,g=e.length;f<g;f++)c.push({name:d,value:e[f]});else e!==null&&typeof e!="undefined"&&c.push({name:this.name,value:e})});return a.param(c)};a.fn.fieldValue=function(b){for(var c=[],d=0,e=this.length;d<e;d++){var f=this[d],g=a.fieldValue(f,b);if(g===null||typeof g=="undefined"||g.constructor==Array&&!g.length)continue;g.constructor==Array?a.merge(c,g):c.push(g)}return c};a.fieldValue=function(a,b){var c=a.name,d=a.type,e=a.tagName.toLowerCase();typeof b=="undefined"&&(b=!0);if(b&&(!c||a.disabled||d=="reset"||d=="button"||(d=="checkbox"||d=="radio")&&!a.checked||(d=="submit"||d=="image")&&a.form&&a.form.clk!=a||e=="select"&&a.selectedIndex==-1))return null;if(e=="select"){var f=a.selectedIndex;if(f<0)return null;var g=[],h=a.options,i=d=="select-one",j=i?f+1:h.length;for(var k=i?f:0;k<j;k++){var l=h[k];if(l.selected){var m=l.value;m||(m=l.attributes&&l.attributes.value&&!l.attributes.value.specified?l.text:l.value);if(i)return m;g.push(m)}}return g}return a.value};a.fn.clearForm=function(){return this.each(function(){a("input,select,textarea",this).clearFields()})};a.fn.clearFields=a.fn.clearInputs=function(){return this.each(function(){var a=this.type,b=this.tagName.toLowerCase();a=="text"||a=="password"||b=="textarea"?this.value="":a=="checkbox"||a=="radio"?this.checked=!1:b=="select"&&(this.selectedIndex=-1)})};a.fn.resetForm=function(){return this.each(function(){(typeof this.reset=="function"||typeof this.reset=="object"&&!this.reset.nodeType)&&this.reset()})};a.fn.enable=function(a){a==undefined&&(a=!0);return this.each(function(){this.disabled=!a})};a.fn.selected=function(b){b==undefined&&(b=!0);return this.each(function(){var c=this.type;if(c=="checkbox"||c=="radio")this.checked=b;else if(this.tagName.toLowerCase()=="option"){var d=a(this).parent("select");b&&d[0]&&d[0].type=="select-one"&&d.find("option").selected(!1);this.selected=b}})}})(jQuery);