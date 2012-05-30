/*
 * atd.core.js - A building block to create a front-end for AtD
 * Author      : Raphael Mudge, Automattic
 * License     : LGPL
 * Project     : http://www.afterthedeadline.com/developers.slp
 * Contact     : raffi@automattic.com
 *//* EXPORTED_SYMBOLS is set so this file can be a JavaScript Module */function AtDCore(){this.ignore_types=["Bias Language","Cliches","Complex Expression","Diacritical Marks","Double Negatives","Hidden Verbs","Jargon Language","Passive voice","Phrases to Avoid","Redundant Expression"];this.ignore_strings={};this.i18n={}}function TokenIterator(a){this.tokens=a;this.index=0;this.count=0;this.last=0}var EXPORTED_SYMBOLS=["AtDCore"];AtDCore.prototype.getLang=function(a,b){return this.i18n[a]==undefined?b:this.i18n[a]};AtDCore.prototype.addI18n=function(a){this.i18n=a};AtDCore.prototype.setIgnoreStrings=function(a){var b=this;this.map(a.split(/,\s*/g),function(a){b.ignore_strings[a]=1})};AtDCore.prototype.showTypes=function(a){var b=a.split(/,\s*/g),c={};c["Double Negatives"]=1;c["Hidden Verbs"]=1;c["Passive voice"]=1;c["Bias Language"]=1;c.Cliches=1;c["Complex Expression"]=1;c["Diacritical Marks"]=1;c["Jargon Language"]=1;c["Phrases to Avoid"]=1;c["Redundant Expression"]=1;var d=[];this.map(b,function(a){c[a]=undefined});this.map(this.ignore_types,function(a){c[a]!=undefined&&d.push(a)});this.ignore_types=d};AtDCore.prototype.makeError=function(a,b,c,d,e){var f=new Object;f.type=c;f.string=a;f.tokens=b;(new RegExp("\\b"+a+"\\b")).test(a)?f.regexp=new RegExp("(?!"+a+"<)\\b"+a.replace(/\s+/g,d)+"\\b"):(new RegExp(a+"\\b")).test(a)?f.regexp=new RegExp("(?!"+a+"<)"+a.replace(/\s+/g,d)+"\\b"):(new RegExp("\\b"+a)).test(a)?f.regexp=new RegExp("(?!"+a+"<)\\b"+a.replace(/\s+/g,d)):f.regexp=new RegExp("(?!"+a+"<)"+a.replace(/\s+/g,d));f.used=!1;return f};AtDCore.prototype.addToErrorStructure=function(a,b,c,d){var e=this;this.map(b,function(b){var f=b.word.split(/\s+/),g=b.pre,h=f[0];if(a["__"+h]==undefined){a["__"+h]=new Object;a["__"+h].pretoks={};a["__"+h].defaults=new Array}if(g=="")a["__"+h].defaults.push(e.makeError(b.word,f,c,d,g));else{a["__"+h].pretoks["__"+g]==undefined&&(a["__"+h].pretoks["__"+g]=new Array);a["__"+h].pretoks["__"+g].push(e.makeError(b.word,f,c,d,g))}})};AtDCore.prototype.buildErrorStructure=function(a,b,c){var d=this._getSeparators(),e={};this.addToErrorStructure(e,a,"hiddenSpellError",d);this.addToErrorStructure(e,c,"hiddenGrammarError",d);this.addToErrorStructure(e,b,"hiddenSuggestion",d);return e};AtDCore.prototype._getSeparators=function(){var a="",b,c='"s!#$%&()*+,./:;<=>?@[]^_{|}';for(b=0;b<c.length;b++)a+="\\"+c.charAt(b);return"(?:(?:[ "+a+"])|(?:\\-\\-))+"};AtDCore.prototype.processXML=function(a){var b={};this.map(this.ignore_types,function(a){b[a]=1});this.suggestions=[];var c=a.getElementsByTagName("error"),d=[],e=[],f=[];for(var g=0;g<c.length;g++)if(c[g].getElementsByTagName("string").item(0).firstChild!=null){var h=c[g].getElementsByTagName("string").item(0).firstChild.data,i=c[g].getElementsByTagName("type").item(0).firstChild.data,j=c[g].getElementsByTagName("description").item(0).firstChild.data,k;c[g].getElementsByTagName("precontext").item(0).firstChild!=null?k=c[g].getElementsByTagName("precontext").item(0).firstChild.data:k="";if(this.ignore_strings[h]==undefined){var l={};l.description=j;l.suggestions=[];l.matcher=new RegExp("^"+h.replace(/\s+/,this._getSeparators())+"$");l.context=k;l.string=h;l.type=i;this.suggestions.push(l);if(c[g].getElementsByTagName("suggestions").item(0)!=undefined){var m=c[g].getElementsByTagName("suggestions").item(0).getElementsByTagName("option");for(var n=0;n<m.length;n++)l.suggestions.push(m[n].firstChild.data)}if(c[g].getElementsByTagName("url").item(0)!=undefined){var o=c[g].getElementsByTagName("url").item(0).firstChild.data;l.moreinfo=o+"&theme=tinymce"}if(b[j]==undefined){i=="suggestion"&&f.push({word:h,pre:k});i=="grammar"&&d.push({word:h,pre:k})}(i=="spelling"||j=="Homophone")&&e.push({word:h,pre:k});j=="Cliches"&&(l.description="Clich&eacute;s");j=="Spelling"&&(l.description=this.getLang("menu_title_spelling","Spelling"));j=="Repeated Word"&&(l.description=this.getLang("menu_title_repeated_word","Repeated Word"));j=="Did you mean..."&&(l.description=this.getLang("menu_title_confused_word","Did you mean..."))}}var p,q=e.length+d.length+f.length;q>0?p=this.buildErrorStructure(e,f,d):p=undefined;return{errors:p,count:q,suggestions:this.suggestions}};AtDCore.prototype.findSuggestion=function(a){var b=a.innerHTML,c=(this.getAttrib(a,"pre")+"").replace(/[\\,!\\?\\."\s]/g,"");this.getAttrib(a,"pre")==undefined&&alert(a.innerHTML);var d=undefined,e=this.suggestions.length;for(var f=0;f<e;f++){var g=this.suggestions[f].string;if((c==""||c==this.suggestions[f]["context"])&&this.suggestions[f].matcher.test(b)){d=this.suggestions[f];break}}return d};TokenIterator.prototype.next=function(){var a=this.tokens[this.index];this.count=this.last;this.last+=a.length+1;this.index++;if(a!=""){a[0]=="'"&&(a=a.substring(1,a.length));a[a.length-1]=="'"&&(a=a.substring(0,a.length-1))}return a};TokenIterator.prototype.hasNext=function(){return this.index<this.tokens.length};TokenIterator.prototype.hasNextN=function(a){return this.index+a<this.tokens.length};TokenIterator.prototype.skip=function(a,b){this.index+=a;this.last+=b;this.index<this.tokens.length&&(this.count=this.last-this.tokens[this.index].length)};TokenIterator.prototype.getCount=function(){return this.count};TokenIterator.prototype.peek=function(a){var b=new Array,c=this.index+a;for(var d=this.index;d<c;d++)b.push(this.tokens[d]);return b};AtDCore.prototype.markMyWords=function(a,b){var c=new RegExp(this._getSeparators()),d=new Array,e=0,f=this;this._walk(a,function(a){a.nodeType==3&&!f.isMarkedNode(a)&&d.push(a)});var g;this.map(d,function(a){var d;if(a.nodeType==3){d=a.nodeValue;var h=a.nodeValue.split(c),i="",j=[];g=new TokenIterator(h);while(g.hasNext()){var k=g.next(),l=b["__"+k],m;if(l!=undefined&&l.pretoks!=undefined){m=l.defaults;l=l.pretoks["__"+i];var n=!1,o,p;o=d.substr(0,g.getCount());p=d.substr(o.length,d.length);var q=function(a){if(a!=undefined&&!a.used&&r["__"+a.string]==undefined&&a.regexp.test(p)){var b=p.length;r["__"+a.string]=1;j.push([a.regexp,'<span class="'+a.type+'" pre="'+i+'">$&</span>']);a.used=!0;n=!0}},r={};if(l!=undefined){i+=" ";f.map(l,q)}if(!n){i="";f.map(m,q)}}i=k}if(j.length>0){newNode=a;for(var s=0;s<j.length;s++){var t=j[s][0],u=j[s][1],v=function(a){if(a.nodeType==3){e++;return f.isIE()&&a.nodeValue.length>0&&a.nodeValue.substr(0,1)==" "?f.create('<span class="mceItemHidden">&nbsp;</span>'+a.nodeValue.substr(1,a.nodeValue.length-1).replace(t,u),!1):f.create(a.nodeValue.replace(t,u),!1)}var b=f.contents(a);for(var c=0;c<b.length;c++)if(b[c].nodeType==3&&t.test(b[c].nodeValue)){var d;f.isIE()&&b[c].nodeValue.length>0&&b[c].nodeValue.substr(0,1)==" "?d=f.create('<span class="mceItemHidden">&nbsp;</span>'+b[c].nodeValue.substr(1,b[c].nodeValue.length-1).replace(t,u),!0):d=f.create(b[c].nodeValue.replace(t,u),!0);f.replaceWith(b[c],d);f.removeParent(d);e++;return a}return a};newNode=v(newNode)}f.replaceWith(a,newNode)}}});return e};AtDCore.prototype._walk=function(a,b){var c;for(c=0;c<a.length;c++){b.call(b,a[c]);this._walk(this.contents(a[c]),b)}};AtDCore.prototype.removeWords=function(a,b){var c=0,d=this;this.map(this.findSpans(a).reverse(),function(a){if(a&&(d.isMarkedNode(a)||d.hasClass(a,"mceItemHidden")||d.isEmptySpan(a)))if(a.innerHTML=="&nbsp;"){var e=document.createTextNode(" ");d.replaceWith(a,e)}else if(!b||a.innerHTML==b){d.removeParent(a);c++}});return c};AtDCore.prototype.isEmptySpan=function(a){return this.getAttrib(a,"class")==""&&this.getAttrib(a,"style")==""&&this.getAttrib(a,"id")==""&&!this.hasClass(a,"Apple-style-span")&&this.getAttrib(a,"mce_name")==""};AtDCore.prototype.isMarkedNode=function(a){return this.hasClass(a,"hiddenGrammarError")||this.hasClass(a,"hiddenSpellError")||this.hasClass(a,"hiddenSuggestion")};AtDCore.prototype.applySuggestion=function(a,b){if(b=="(omit)")this.remove(a);else{var c=this.create(b);this.replaceWith(a,c);this.removeParent(c)}};AtDCore.prototype.hasErrorMessage=function(a){return a!=undefined&&a.getElementsByTagName("message").item(0)!=null};AtDCore.prototype.getErrorMessage=function(a){return a.getElementsByTagName("message").item(0)};AtDCore.prototype.isIE=function(){return navigator.appName=="Microsoft Internet Explorer"};