!function($){function e(e){var t=document.createElement("input"),i="on"+e,a=i in t;return a||(t.setAttribute(i,"return;"),a="function"==typeof t[i]),t=null,a}function t(e){var t="text"==e||"tel"==e;if(!t){var i=document.createElement("input");i.setAttribute("type",e),t="text"===i.type,i=null}return t}function i(e,t,a){var n=a.aliases[e];return n?(n.alias&&i(n.alias,void 0,a),$.extend(!0,a,n),$.extend(!0,a,t),!0):!1}function a(e){function t(t){function i(e,t,i,a){this.matches=[],this.isGroup=e||!1,this.isOptional=t||!1,this.isQuantifier=i||!1,this.isAlternator=a||!1,this.quantifier={min:1,max:1}}function a(t,i,a){var n=e.definitions[i],o=0==t.matches.length;if(a=void 0!=a?a:t.matches.length,n&&!r){n.placeholder=$.isFunction(n.placeholder)?n.placeholder.call(this,e):n.placeholder;for(var s=n.prevalidator,l=s?s.length:0,u=1;u<n.cardinality;u++){var c=l>=u?s[u-1]:[],d=c.validator,p=c.cardinality;t.matches.splice(a++,0,{fn:d?"string"==typeof d?new RegExp(d):new function(){this.test=d}:new RegExp("."),cardinality:p?p:1,optionality:t.isOptional,newBlockMarker:o,casing:n.casing,def:n.definitionSymbol||i,placeholder:n.placeholder,mask:i})}t.matches.splice(a++,0,{fn:n.validator?"string"==typeof n.validator?new RegExp(n.validator):new function(){this.test=n.validator}:new RegExp("."),cardinality:n.cardinality,optionality:t.isOptional,newBlockMarker:o,casing:n.casing,def:n.definitionSymbol||i,placeholder:n.placeholder,mask:i})}else t.matches.splice(a++,0,{fn:null,cardinality:0,optionality:t.isOptional,newBlockMarker:o,casing:null,def:i,placeholder:void 0,mask:i}),r=!1}for(var n=/(?:[?*+]|\{[0-9\+\*]+(?:,[0-9\+\*]*)?\})\??|[^.?*+^${[]()|\\]+|./g,r=!1,o=new i,s,l,u=[],c=[],d,p,f,v;s=n.exec(t);)switch(l=s[0],l.charAt(0)){case e.optionalmarker.end:case e.groupmarker.end:if(d=u.pop(),u.length>0){if(p=u[u.length-1],p.matches.push(d),p.isAlternator){f=u.pop();for(var m=0;m<f.matches.length;m++)f.matches[m].isGroup=!1;u.length>0?(p=u[u.length-1],p.matches.push(f)):o.matches.push(f)}}else o.matches.push(d);break;case e.optionalmarker.start:u.push(new i(!1,!0));break;case e.groupmarker.start:u.push(new i(!0));break;case e.quantifiermarker.start:var h=new i(!1,!1,!0);l=l.replace(/[{}]/g,"");var k=l.split(","),g=isNaN(k[0])?k[0]:parseInt(k[0]),b=1==k.length?g:isNaN(k[1])?k[1]:parseInt(k[1]);if(("*"==b||"+"==b)&&(g="*"==b?0:1),h.quantifier={min:g,max:b},u.length>0){var y=u[u.length-1].matches;if(s=y.pop(),!s.isGroup){var _=new i(!0);_.matches.push(s),s=_}y.push(s),y.push(h)}else{if(s=o.matches.pop(),!s.isGroup){var _=new i(!0);_.matches.push(s),s=_}o.matches.push(s),o.matches.push(h)}break;case e.escapeChar:r=!0;break;case e.alternatormarker:u.length>0?(p=u[u.length-1],v=p.matches.pop()):v=o.matches.pop(),v.isAlternator?u.push(v):(f=new i(!1,!1,!1,!0),f.matches.push(v),u.push(f));break;default:if(u.length>0){if(p=u[u.length-1],p.matches.length>0&&(v=p.matches[p.matches.length-1],v.isGroup&&(v.isGroup=!1,a(v,e.groupmarker.start,0),a(v,e.groupmarker.end))),a(p,l),p.isAlternator){f=u.pop();for(var m=0;m<f.matches.length;m++)f.matches[m].isGroup=!1;u.length>0?(p=u[u.length-1],p.matches.push(f)):o.matches.push(f)}}else o.matches.length>0&&(v=o.matches[o.matches.length-1],v.isGroup&&(v.isGroup=!1,a(v,e.groupmarker.start,0),a(v,e.groupmarker.end))),a(o,l)}return o.matches.length>0&&(v=o.matches[o.matches.length-1],v.isGroup&&(v.isGroup=!1,a(v,e.groupmarker.start,0),a(v,e.groupmarker.end)),c.push(o)),c}function i(i,a){if(void 0==i||""==i)return void 0;if(1==i.length&&0==e.greedy&&0!=e.repeat&&(e.placeholder=""),e.repeat>0||"*"==e.repeat||"+"==e.repeat){var n="*"==e.repeat?0:"+"==e.repeat?1:e.repeat;i=e.groupmarker.start+i+e.groupmarker.end+e.quantifiermarker.start+n+","+e.repeat+e.quantifiermarker.end}return void 0==$.inputmask.masksCache[i]&&($.inputmask.masksCache[i]={mask:i,maskToken:t(i),validPositions:{},_buffer:void 0,buffer:void 0,tests:{},metadata:a}),$.extend(!0,{},$.inputmask.masksCache[i])}function a(t){if(t=t.toString(),e.numericInput){t=t.split("").reverse();for(var i=0;i<t.length;i++)t[i]==e.optionalmarker.start?t[i]=e.optionalmarker.end:t[i]==e.optionalmarker.end?t[i]=e.optionalmarker.start:t[i]==e.groupmarker.start?t[i]=e.groupmarker.end:t[i]==e.groupmarker.end&&(t[i]=e.groupmarker.start);t=t.join("")}return t}var n=void 0;if($.isFunction(e.mask)&&(e.mask=e.mask.call(this,e)),$.isArray(e.mask)){if(e.mask.length>1){e.keepStatic=void 0==e.keepStatic?!0:e.keepStatic;var r="(";return $.each(e.mask,function(e,t){r.length>1&&(r+=")|("),r+=a(void 0==t.mask||$.isFunction(t.mask)?t:t.mask)}),r+=")",i(r,e.mask)}e.mask=e.mask.pop()}return e.mask&&(n=void 0==e.mask.mask||$.isFunction(e.mask.mask)?i(a(e.mask),e.mask):i(a(e.mask.mask),e.mask)),n}function n(i,a,n){function r(e,t,i){t=t||0;var a=[],n,r=0,o,l;do{if(e===!0&&s().validPositions[r]){var u=s().validPositions[r];o=u.match,n=u.locator.slice(),a.push(i===!0?u.input:O(r,o))}else l=v(r,n,r-1),o=l.match,n=l.locator.slice(),a.push(O(r,o));r++}while((void 0==ut||ut>r-1)&&null!=o.fn||null==o.fn&&""!=o.def||t>=r);return a.pop(),a}function s(){return a}function l(e){var t=s();t.buffer=void 0,t.tests={},e!==!0&&(t._buffer=void 0,t.validPositions={},t.p=0)}function c(e){var t=s(),i=-1,a=t.validPositions;void 0==e&&(e=-1);var n=i,r=i;for(var o in a){var l=parseInt(o);(-1==e||null!=a[l].match.fn)&&(e>=l&&(n=l),l>=e&&(r=l))}return i=-1!=n&&e-n>1||e>r?n:r}function p(e,t,i){if(n.insertMode&&void 0!=s().validPositions[e]&&void 0==i){var a=$.extend(!0,{},s().validPositions),r=c(),o;for(o=e;r>=o;o++)delete s().validPositions[o];s().validPositions[e]=t;var l=!0,u;for(o=e;r>=o;o++){var d=a[o];if(void 0!=d){var p=s().validPositions;u=!n.keepStatic&&p[o]&&(void 0!=p[o+1]&&k(o+1,p[o].locator.slice(),o).length>1||void 0!=p[o].alternation)?o+1:M(o),l=h(u,d.match.def)?l&&E(u,d.input,!0,!0)!==!1:null==d.match.fn}if(!l)break}if(!l)return s().validPositions=$.extend(!0,{},a),!1}else s().validPositions[e]=t;return!0}function f(e,t,i,a){var r,o=e;s().p=e,void 0!=s().validPositions[e]&&s().validPositions[e].input==n.radixPoint&&(t++,o++);var u=t;for(r=o;t>r;r++)void 0!=s().validPositions[r]&&(i===!0||0!=n.canClearPosition(s(),r,c(),a,n))&&delete s().validPositions[r];for(l(!0),r=o+1;r<=c();){for(;void 0!=s().validPositions[o];)o++;var d=s().validPositions[o];o>r&&(r=o+1);var p=s().validPositions[r];void 0!=p&&void 0==d?(h(o,p.match.def)&&E(o,p.input,!0)!==!1&&(delete s().validPositions[r],r++),o++):r++}var f=c();f>=e&&void 0!=s().validPositions[f]&&s().validPositions[f].input==n.radixPoint&&delete s().validPositions[f],l(!0)}function v(e,t,i){for(var a=k(e,t,i),r,o=c(),l=s().validPositions[o]||k(0)[0],u=void 0!=l.alternation?l.locator[l.alternation].split(","):[],d=0;d<a.length&&(r=a[d],!(r.match&&(n.greedy&&r.match.optionalQuantifier!==!0||(r.match.optionality===!1||r.match.newBlockMarker===!1)&&r.match.optionalQuantifier!==!0)&&(void 0==l.alternation||void 0!=r.locator[l.alternation]&&P(r.locator[l.alternation].toString().split(","),u))));d++);return r}function m(e){return s().validPositions[e]?s().validPositions[e].match:k(e)[0].match}function h(e,t){for(var i=!1,a=k(e),n=0;n<a.length;n++)if(a[n].match&&a[n].match.def==t){i=!0;break}return i}function k(e,t,i){function a(t,i,n,o){function c(n,o,p){if(r>1e4)return alert("jquery.inputmask: There is probably an error in your mask definition or in the code. Create an issue on github with an example of the mask you are using. "+s().mask),!0;if(r==e&&void 0==n.matches)return l.push({match:n,locator:o.reverse()}),!0;if(void 0!=n.matches){if(n.isGroup&&p!==!0){if(n=c(t.matches[d+1],o))return!0}else if(n.isOptional){var f=n;if(n=a(n,i,o,p)){var v=l[l.length-1].match,m=0==$.inArray(v,f.matches);m&&(u=!0),r=e}}else if(n.isAlternator){var h=n,k=[],g,b=l.slice(),y=o.length,_=i.length>0?i.shift():-1;if(-1==_||"string"==typeof _){var P=r,E=i.slice(),C;"string"==typeof _&&(C=_.split(","));for(var x=0;x<h.matches.length;x++){l=[],n=c(h.matches[x],[x].concat(o),p)||n,g=l.slice(),r=P,l=[];for(var M=0;M<E.length;M++)i[M]=E[M];for(var A=0;A<g.length;A++)for(var w=g[A],S=0;S<k.length;S++){var O=k[S];if(w.match.mask==O.match.mask&&("string"!=typeof _||-1!=$.inArray(w.locator[y].toString(),C))){g.splice(A,1),O.locator[y]=O.locator[y]+","+w.locator[y],O.alternation=y;break}}k=k.concat(g)}"string"==typeof _&&(k=$.map(k,function(e,t){if(isFinite(t)){var i=e.locator[y].toString().split(","),a;e.locator[y]=void 0,e.alternation=void 0;for(var n=0;n<i.length;n++)a=-1!=$.inArray(i[n],C),a&&(void 0!=e.locator[y]?(e.locator[y]+=",",e.alternation=y,e.locator[y]+=i[n]):e.locator[y]=parseInt(i[n]));if(void 0!=e.locator[y])return e}})),l=b.concat(k),u=!0}else n=c(h.matches[_],[_].concat(o),p);if(n)return!0}else if(n.isQuantifier&&p!==!0)for(var j=n,T=i.length>0&&p!==!0?i.shift():0;T<(isNaN(j.quantifier.max)?T+1:j.quantifier.max)&&e>=r;T++){var G=t.matches[$.inArray(j,t.matches)-1];if(n=c(G,[T].concat(o),!0)){var v=l[l.length-1].match;v.optionalQuantifier=T>j.quantifier.min-1;var m=0==$.inArray(v,G.matches);if(m){if(T>j.quantifier.min-1){u=!0,r=e;break}return!0}return!0}}else if(n=a(n,i,o,p))return!0}else r++}for(var d=i.length>0?i.shift():0;d<t.matches.length;d++)if(t.matches[d].isQuantifier!==!0){var p=c(t.matches[d],[d].concat(n),o);if(p&&r==e)return p;if(r>e)break}}var n=s().maskToken,r=t?i:0,o=t||[0],l=[],u=!1;if(void 0==t){for(var c=e-1,d;void 0==(d=s().validPositions[c])&&c>-1;)c--;if(void 0!=d&&c>-1)r=c,o=d.locator.slice();else{for(c=e-1;void 0==(d=s().tests[c])&&c>-1;)c--;void 0!=d&&c>-1&&(r=c,o=d[0].locator.slice())}}for(var p=o.shift();p<n.length;p++){var f=a(n[p],o,[p]);if(f&&r==e||r>e)break}return(0==l.length||u)&&l.push({match:{fn:null,cardinality:0,optionality:!0,casing:null,def:""},locator:[]}),s().tests[e]=$.extend(!0,[],l),s().tests[e]}function g(){return void 0==s()._buffer&&(s()._buffer=r(!1,1)),s()._buffer}function b(){return void 0==s().buffer&&(s().buffer=r(!0,c(),!0)),s().buffer}function y(e,t,i){if(i=i||b().slice(),e===!0)l(),e=0,t=i.length;else for(var a=e;t>a;a++)delete s().validPositions[a],delete s().tests[a];for(var a=e;t>a;a++)i[a]!=n.skipOptionalPartCharacter&&E(a,i[a],!0,!0)}function _(e,t){switch(t.casing){case"upper":e=e.toUpperCase();break;case"lower":e=e.toLowerCase()}return e}function P(e,t){for(var i=n.greedy?t:t.slice(0,1),a=!1,r=0;r<e.length;r++)if(-1!=$.inArray(e[r],i)){a=!0;break}return a}function E(e,t,i,a){function r(e,t,i,a){var r=!1;return $.each(k(e),function(o,u){for(var d=u.match,v=t?1:0,m="",h=b(),k=d.cardinality;k>v;k--)m+=w(e-(k-1));if(t&&(m+=t),r=null!=d.fn?d.fn.test(m,s(),e,i,n):t!=d.def&&t!=n.skipOptionalPartCharacter||""==d.def?!1:{c:d.def,pos:e},r!==!1){var g=void 0!=r.c?r.c:t;g=g==n.skipOptionalPartCharacter&&null===d.fn?d.def:g;var P=e;if(void 0!=r.remove&&f(r.remove,r.remove+1,!0),r.refreshFromBuffer){var C=r.refreshFromBuffer;if(i=!0,y(C===!0?C:C.start,C.end),void 0==r.pos&&void 0==r.c)return r.pos=c(),!1;if(P=void 0!=r.pos?r.pos:e,P!=e)return r=$.extend(r,E(P,g,!0)),!1}else if(r!==!0&&void 0!=r.pos&&r.pos!=e&&(P=r.pos,y(e,P),P!=e))return r=$.extend(r,E(P,g,!0)),!1;return 1!=r&&void 0==r.pos&&void 0==r.c?!1:(o>0&&l(!0),p(P,$.extend({},u,{input:_(g,d)}),a)||(r=!1),!1)}}),r}function o(e,t,i,a){var r=$.extend(!0,{},s().validPositions),o,u;for(o=c();o>=0;o--)if(s().validPositions[o]&&void 0!=s().validPositions[o].alternation){u=s().validPositions[o].alternation;break}if(void 0!=u)for(var d in s().validPositions)if(parseInt(d)>parseInt(o)&&void 0===s().validPositions[d].alternation){for(var p=s().validPositions[d],f=p.locator[u],v=s().validPositions[o].locator[u].split(","),m=0;m<v.length;m++)if(f<v[m]){for(var h,k,g=d-1;g>=0;g--)if(h=s().validPositions[g],void 0!=h){k=h.locator[u],h.locator[u]=v[m];break}if(f!=h.locator[u]){for(var y=b().slice(),_=d;_<c()+1;_++)delete s().validPositions[_],delete s().tests[_];l(!0),n.keepStatic=!n.keepStatic;for(var _=d;_<y.length;_++)y[_]!=n.skipOptionalPartCharacter&&E(c()+1,y[_],!1,!0);h.locator[u]=k;var P=E(e,t,i,a);if(n.keepStatic=!n.keepStatic,P)return P;l(),s().validPositions=$.extend(!0,{},r)}}break}return!1}function u(e,t){for(var i=s().validPositions[t],a=i.locator,n=a.length,r=e;t>r;r++)if(!C(r)){var o=k(r),l=o[0],u=-1;$.each(o,function(e,t){for(var i=0;n>i;i++)t.locator[i]&&P(t.locator[i].toString().split(","),a[i].toString().split(","))&&i>u&&(u=i,l=t)}),p(r,$.extend({},l,{input:l.match.def}),!0)}}i=i===!0;for(var d=b(),v=e-1;v>-1&&!s().validPositions[v];v--);for(v++;e>v;v++)void 0==s().validPositions[v]&&((!C(v)||d[v]!=O(v))&&k(v).length>1||d[v]==n.radixPoint||"0"==d[v]&&$.inArray(n.radixPoint,d)<v)&&r(v,d[v],!0);var m=e,h=!1,g=$.extend(!0,{},s().validPositions);if(m<x()&&(h=r(m,t,i,a),!i&&h===!1)){var A=s().validPositions[m];if(!A||null!=A.match.fn||A.match.def!=t&&t!=n.skipOptionalPartCharacter){if((n.insertMode||void 0==s().validPositions[M(m)])&&!C(m))for(var S=m+1,j=M(m);j>=S;S++)if(h=r(S,t,i,a),h!==!1){u(m,S),m=S;break}}else h={caret:M(m)}}if(h===!1&&n.keepStatic&&K(d)&&(h=o(e,t,i,a)),h===!0&&(h={pos:m}),$.isFunction(n.postValidation)&&0!=h&&!i){l(!0);var T=n.postValidation(b(),n);if(!T)return s().validPositions=$.extend(!0,{},g),!1}return h}function C(e){var t=m(e);return null!=t.fn?t.fn:!1}function x(){var e;ut=rt.prop("maxLength"),-1==ut&&(ut=void 0);var t,i=c(),a=s().validPositions[i],n=void 0!=a?a.locator.slice():void 0;for(t=i+1;void 0==a||null!=a.match.fn||null==a.match.fn&&""!=a.match.def;t++)a=v(t,n,t-1),n=a.locator.slice();return e=t,void 0==ut||ut>e?e:ut}function M(e){var t=x();if(e>=t)return t;for(var i=e;++i<t&&!C(i)&&(n.nojumps!==!0||n.nojumpsThreshold>i););return i}function A(e){var t=e;if(0>=t)return 0;for(;--t>0&&!C(t););return t}function w(e){return void 0==s().validPositions[e]?O(e):s().validPositions[e].input}function S(e,t,i,a,r){if(a&&$.isFunction(n.onBeforeWrite)){var o=n.onBeforeWrite.call(e,a,t,i,n);if(o){if(o.refreshFromBuffer){var s=o.refreshFromBuffer;y(s===!0?s:s.start,s.end,o.buffer),l(!0),t=b()}i=o.caret||i}}e._valueSet(t.join("")),void 0!=i&&F(e,i),r===!0&&(st=!0,$(e).trigger("input"))}function O(e,t){return t=t||m(e),void 0!=t.placeholder?t.placeholder:null==t.fn?t.def:n.placeholder.charAt(e%n.placeholder.length)}function j(e,t,i,a){function n(){var e=!1,t=g().slice(p,M(p)).join("").indexOf(d);if(-1!=t&&!C(p)){e=!0;for(var i=g().slice(p,p+t),a=0;a<i.length;a++)if(" "!=i[a]){e=!1;break}}return e}var r=void 0!=a?a.slice():e._valueGet().split("");l(),s().p=M(-1),t&&e._valueSet("");var o=g().slice(0,M(-1)).join(""),u=r.join("").match(new RegExp(T(o),"g"));u&&u.length>0&&r.splice(0,o.length*u.length);var d="",p=0;$.each(r,function(t,a){var r=$.Event("keypress");r.which=a.charCodeAt(0),d+=a;var o=c(),l=s().validPositions[o],u=v(o+1,l?l.locator.slice():void 0,o);if(!n()||i){var f=i?t:null==u.match.fn&&u.match.optionality&&o+1<s().p?o+1:s().p;q.call(e,r,!0,!1,i,f),p=f+1,d=""}else q.call(e,r,!0,!1,!0,o+1)}),t&&S(e,b(),$(e).is(":focus")?M(c(0)):void 0,$.Event("checkval"))}function T(e){return $.inputmask.escapeRegex.call(this,e)}function G(e){if(e.data("_inputmask")&&!e.hasClass("hasDatepicker")){var t=[],i=s().validPositions;for(var a in i)i[a].match&&null!=i[a].match.fn&&t.push(i[a].input);var r=(et?t.reverse():t).join(""),o=(et?b().slice().reverse():b()).join("");return $.isFunction(n.onUnMask)&&(r=n.onUnMask.call(e,o,r,n)||r),r}return e[0]._valueGet()}function D(e){if(et&&"number"==typeof e&&(!n.greedy||""!=n.placeholder)){var t=b().length;e=t-e}return e}function F(e,t,i){var a=e.jquery&&e.length>0?e[0]:e,r;if("number"!=typeof t)return a.setSelectionRange?(t=a.selectionStart,i=a.selectionEnd):document.selection&&document.selection.createRange&&(r=document.selection.createRange(),t=0-r.duplicate().moveStart("character",-1e5),i=t+r.text.length),{begin:D(t),end:D(i)};if(t=D(t),i=D(i),i="number"==typeof i?i:t,$(a).is(":visible")){var o=$(a).css("font-size").replace("px","")*i;a.scrollLeft=o>a.scrollWidth?o:0,0==n.insertMode&&t==i&&i++,a.setSelectionRange?(a.selectionStart=t,a.selectionEnd=i):a.createTextRange&&(r=a.createTextRange(),r.collapse(!0),r.moveEnd("character",i),r.moveStart("character",t),r.select())}}function B(e){var t=b(),i=t.length,a,n=c(),r={},o=s().validPositions[n],l=void 0!=o?o.locator.slice():void 0,u;for(a=n+1;a<t.length;a++)u=v(a,l,a-1),l=u.locator.slice(),r[a]=$.extend(!0,{},u);var d=o&&void 0!=o.alternation?o.locator[o.alternation].split(","):[];for(a=i-1;a>n&&(u=r[a].match,(u.optionality||u.optionalQuantifier||o&&void 0!=o.alternation&&void 0!=r[a].locator[o.alternation]&&-1!=$.inArray(r[a].locator[o.alternation].toString(),d))&&t[a]==O(a,u));a--)i--;return e?{l:i,def:r[i]?r[i].match:void 0}:i}function I(e){for(var t=B(),i=e.length-1;i>t&&!C(i);i--);e.splice(t,i+1-t)}function K(e){if($.isFunction(n.isComplete))return n.isComplete.call(rt,e,n);if("*"==n.repeat)return void 0;var t=!1,i=B(!0),a=A(i.l),r=c();if(r==a&&(void 0==i.def||i.def.newBlockMarker||i.def.optionalQuantifier)){t=!0;for(var o=0;a>=o;o++){var s=C(o);if(s&&(void 0==e[o]||e[o]==O(o))||!s&&e[o]!=O(o)){t=!1;break}}}return t}function R(e,t){return et?e-t>1||e-t==1&&n.insertMode:t-e>1||t-e==1&&n.insertMode}function L(e){var t=$._data(e).events;$.each(t,function(e,t){$.each(t,function(e,t){if("inputmask"==t.namespace&&"setvalue"!=t.type){var i=t.handler;t.handler=function(e){if(!this.disabled&&(!this.readOnly||"keydown"==e.type&&e.ctrlKey&&67==e.keyCode)){switch(e.type){case"input":if(st===!0)return st=!1,e.preventDefault();break;case"keydown":ot=!1;break;case"keypress":if(ot===!0)return e.preventDefault();ot=!0;break;case"compositionstart":break;case"compositionupdate":st=!0;break;case"compositionend":}return i.apply(this,arguments)}e.preventDefault()}}})})}function N(e){function t(e){if(void 0==$.valHooks[e]||1!=$.valHooks[e].inputmaskpatch){var t=$.valHooks[e]&&$.valHooks[e].get?$.valHooks[e].get:function(e){return e.value},i=$.valHooks[e]&&$.valHooks[e].set?$.valHooks[e].set:function(e,t){return e.value=t,e};$.valHooks[e]={get:function(e){var i=$(e);if(i.data("_inputmask")){if(i.data("_inputmask").opts.autoUnmask)return i.inputmask("unmaskedvalue");var a=t(e),n=i.data("_inputmask"),r=n.maskset,o=r._buffer;return o=o?o.join(""):"",a!=o?a:""}return t(e)},set:function(e,t){var a=$(e),n=a.data("_inputmask"),r;return n?(r=i(e,$.isFunction(n.opts.onBeforeMask)?n.opts.onBeforeMask.call(mt,t,n.opts)||t:t),a.triggerHandler("setvalue.inputmask")):r=i(e,t),r},inputmaskpatch:!0}}}function i(){var e=$(this),t=$(this).data("_inputmask");return t?t.opts.autoUnmask?e.inputmask("unmaskedvalue"):o.call(this)!=g().join("")?o.call(this):"":o.call(this)}function a(e){var t=$(this).data("_inputmask");t?(s.call(this,$.isFunction(t.opts.onBeforeMask)?t.opts.onBeforeMask.call(mt,e,t.opts)||e:e),$(this).triggerHandler("setvalue.inputmask")):s.call(this,e)}function r(e){$(e).bind("mouseenter.inputmask",function(e){var t=$(this),i=this,a=i._valueGet();""!=a&&a!=b().join("")&&(this._valueSet($.isFunction(n.onBeforeMask)?n.onBeforeMask.call(mt,a,n)||a:a),t.triggerHandler("setvalue.inputmask"))});var t=$._data(e).events,i=t.mouseover;if(i){for(var a=i[i.length-1],r=i.length-1;r>0;r--)i[r]=i[r-1];i[0]=a}}var o,s;if(!e._valueGet){if(Object.getOwnPropertyDescriptor)var l=Object.getOwnPropertyDescriptor(e,"value");document.__lookupGetter__&&e.__lookupGetter__("value")?(o=e.__lookupGetter__("value"),s=e.__lookupSetter__("value"),e.__defineGetter__("value",i),e.__defineSetter__("value",a)):(o=function(){return e.value},s=function(t){e.value=t},t(e.type),r(e)),e._valueGet=function(e){return et&&e!==!0?o.call(this).split("").reverse().join(""):o.call(this)},e._valueSet=function(e){s.call(this,et?e.split("").reverse().join(""):e)}}}function H(e,t,i,a){function r(){if(n.keepStatic){l(!0);var t=[],i;for(i=c();i>=0;i--)if(s().validPositions[i]){if(void 0!=s().validPositions[i].alternation)break;t.push(s().validPositions[i].input),delete s().validPositions[i]}if(i>0)for(;t.length>0;){s().p=M(c());var a=$.Event("keypress");a.which=t.pop().charCodeAt(0),q.call(e,a,!0,!1,!1,s().p)}}}if((n.numericInput||et)&&(t==$.inputmask.keyCode.BACKSPACE?t=$.inputmask.keyCode.DELETE:t==$.inputmask.keyCode.DELETE&&(t=$.inputmask.keyCode.BACKSPACE),et)){var o=i.end;i.end=i.begin,i.begin=o}if(t==$.inputmask.keyCode.BACKSPACE&&(i.end-i.begin<1||0==n.insertMode)?i.begin=A(i.begin):t==$.inputmask.keyCode.DELETE&&i.begin==i.end&&i.end++,f(i.begin,i.end,!1,a),a!==!0){r();var u=c(i.begin);u<i.begin?(-1==u&&l(),s().p=M(u)):s().p=i.begin}}function U(e,t,i){if(t&&t.refreshFromBuffer){var a=t.refreshFromBuffer;y(a===!0?a:a.start,a.end,t.buffer),l(!0),void 0!=i&&(S(e,b()),F(e,t.caret||i.begin,t.caret||i.end))}}function W(t){var i=this,a=$(i),r=t.keyCode,l=F(i);r==$.inputmask.keyCode.BACKSPACE||r==$.inputmask.keyCode.DELETE||o&&127==r||t.ctrlKey&&88==r&&!e("cut")?(t.preventDefault(),88==r&&(tt=b().join("")),H(i,r,l),S(i,b(),s().p,t,tt!=b().join("")),i._valueGet()==g().join("")?a.trigger("cleared"):K(b())===!0&&a.trigger("complete"),n.showTooltip&&a.prop("title",s().mask)):r==$.inputmask.keyCode.END||r==$.inputmask.keyCode.PAGE_DOWN?setTimeout(function(){var e=M(c());n.insertMode||e!=x()||t.shiftKey||e--,F(i,t.shiftKey?l.begin:e,e)},0):r==$.inputmask.keyCode.HOME&&!t.shiftKey||r==$.inputmask.keyCode.PAGE_UP?F(i,0,t.shiftKey?l.begin:0):n.undoOnEscape&&r==$.inputmask.keyCode.ESCAPE||90==r&&t.ctrlKey?(j(i,!0,!1,tt.split("")),a.click()):r!=$.inputmask.keyCode.INSERT||t.shiftKey||t.ctrlKey?0!=n.insertMode||t.shiftKey||(r==$.inputmask.keyCode.RIGHT?setTimeout(function(){var e=F(i);F(i,e.begin)},0):r==$.inputmask.keyCode.LEFT&&setTimeout(function(){var e=F(i);F(i,et?e.begin+1:e.begin-1)},0)):(n.insertMode=!n.insertMode,F(i,n.insertMode||l.begin!=x()?l.begin:l.begin-1)),lt=-1!=$.inArray(r,n.ignorables)}function q(e,t,i,a,r){var o=this,u=$(o),c=e.which||e.charCode||e.keyCode;if(!(t===!0||e.ctrlKey&&e.altKey)&&(e.ctrlKey||e.metaKey||lt))return!0;if(c){46==c&&0==e.shiftKey&&","==n.radixPoint&&(c=44);var d=t?{begin:r,end:r}:F(o),f,v=String.fromCharCode(c),m=R(d.begin,d.end);m&&(s().undoPositions=$.extend(!0,{},s().validPositions),H(o,$.inputmask.keyCode.DELETE,d,!0),d.begin=s().p,n.insertMode||(n.insertMode=!n.insertMode,p(d.begin,a),n.insertMode=!n.insertMode),m=!n.multi),s().writeOutBuffer=!0;var h=et&&!m?d.end:d.begin,g=E(h,v,a);if(g!==!1){if(g!==!0&&(h=void 0!=g.pos?g.pos:h,v=void 0!=g.c?g.c:v),l(!0),void 0!=g.caret)f=g.caret;else{var _=s().validPositions;f=!n.keepStatic&&(void 0!=_[h+1]&&k(h+1,_[h].locator.slice(),h).length>1||void 0!=_[h].alternation)?h+1:M(h)}s().p=f}if(i!==!1){var P=this;if(setTimeout(function(){n.onKeyValidation.call(P,g,n)},0),s().writeOutBuffer&&g!==!1){var C=b();S(o,C,t?void 0:n.numericInput?A(f):f,e,t!==!0),t!==!0&&setTimeout(function(){K(C)===!0&&u.trigger("complete")},0)}else m&&(s().buffer=void 0,s().validPositions=s().undoPositions)}else m&&(s().buffer=void 0,s().validPositions=s().undoPositions);if(n.showTooltip&&u.prop("title",s().mask),t&&$.isFunction(n.onBeforeWrite)){var x=n.onBeforeWrite.call(this,e,b(),f,n);if(x&&x.refreshFromBuffer){var w=x.refreshFromBuffer;y(w===!0?w:w.start,w.end,x.buffer),l(!0),x.caret&&(s().p=x.caret)}}e.preventDefault()}}function V(e){var t=$(this),i=this,a=e.keyCode,r=b();n.onKeyUp.call(this,e,r,n)}function Q(e){var t=this,i=$(t),a=t._valueGet(!0),r=F(t);if("propertychange"==e.type&&t._valueGet().length<=x())return!0;if("paste"==e.type){var o=a.substr(0,r.begin),s=a.substr(r.end,a.length);o==g().slice(0,r.begin).join("")&&(o=""),s==g().slice(r.end).join("")&&(s=""),window.clipboardData&&window.clipboardData.getData?a=o+window.clipboardData.getData("Text")+s:e.originalEvent&&e.originalEvent.clipboardData&&e.originalEvent.clipboardData.getData&&(a=o+e.originalEvent.clipboardData.getData("text/plain")+s)}var l=$.isFunction(n.onBeforePaste)?n.onBeforePaste.call(t,a,n)||a:a;return j(t,!0,!1,et?l.split("").reverse():l.split("")),i.click(),K(b())===!0&&i.trigger("complete"),!1}function z(e){var t=this;j(t,!0,!1),K(b())===!0&&$(t).trigger("complete"),e.preventDefault()}function J(e){var t=this;tt=b().join(""),(""==nt||0!=e.originalEvent.data.indexOf(nt))&&(at=F(t))}function Z(e){var t=this,i=at||F(t);0==e.originalEvent.data.indexOf(nt)&&(l(),i={begin:0,end:0});var a=e.originalEvent.data;F(t,i.begin,i.end);for(var r=0;r<a.length;r++){var o=$.Event("keypress");o.which=a.charCodeAt(r),ot=!1,lt=!1,q.call(t,o)}setTimeout(function(){var e=s().p;S(t,b(),n.numericInput?A(e):e)},0),nt=e.originalEvent.data}function Y(e){}function X(e){if(rt=$(e),rt.is(":input")&&t(rt.attr("type"))){if(rt.data("_inputmask",{maskset:a,opts:n,isRTL:!1}),n.showTooltip&&rt.prop("title",s().mask),("rtl"==e.dir||n.rightAlign)&&rt.css("text-align","right"),"rtl"==e.dir||n.numericInput){e.dir="ltr",rt.removeAttr("dir");var i=rt.data("_inputmask");i.isRTL=!0,rt.data("_inputmask",i),et=!0}rt.unbind(".inputmask"),rt.closest("form").bind("submit",function(e){tt!=b().join("")&&rt.change(),rt[0]._valueGet&&rt[0]._valueGet()==g().join("")&&rt[0]._valueSet(""),n.removeMaskOnSubmit&&rt.inputmask("remove")}).bind("reset",function(){setTimeout(function(){rt.triggerHandler("setvalue.inputmask")},0)}),rt.bind("mouseenter.inputmask",function(){var e=$(this),t=this;!e.is(":focus")&&n.showMaskOnHover&&t._valueGet()!=b().join("")&&S(t,b())}).bind("blur.inputmask",function(e){var t=$(this),i=this;if(t.data("_inputmask")){var a=i._valueGet(),r=b().slice();ct=!0,tt!=r.join("")&&(t.change(),tt=r.join("")),""!=a&&(n.clearMaskOnLostFocus&&(a==g().join("")?r=[]:I(r)),K(r)===!1&&(t.trigger("incomplete"),n.clearIncomplete&&(l(),r=n.clearMaskOnLostFocus?[]:g().slice())),S(i,r,void 0,e))}}).bind("focus.inputmask",function(e){var t=$(this),i=this,a=i._valueGet();n.showMaskOnFocus&&(!n.showMaskOnHover||n.showMaskOnHover&&""==a)&&i._valueGet()!=b().join("")&&S(i,b(),M(c())),tt=b().join("")}).bind("mouseleave.inputmask",function(){var e=$(this),t=this;if(n.clearMaskOnLostFocus){var i=b().slice(),a=t._valueGet();e.is(":focus")||a==e.attr("placeholder")||""==a||(a==g().join("")?i=[]:I(i),S(t,i))}}).bind("click.inputmask",function(){var e=$(this),t=this;if(e.is(":focus")){var i=F(t);if(i.begin==i.end)if(n.radixFocus&&""!=n.radixPoint&&-1!=$.inArray(n.radixPoint,b())&&(ct||b().join("")==g().join("")))F(t,$.inArray(n.radixPoint,b())),ct=!1;else{var a=et?D(i.begin):i.begin,r=M(c(a));r>a?F(t,C(a)?a:M(a)):F(t,r)}}}).bind("dblclick.inputmask",function(){var e=this;setTimeout(function(){F(e,0,M(c()))},0)}).bind(d+".inputmask dragdrop.inputmask drop.inputmask",Q).bind("setvalue.inputmask",function(){var e=this;j(e,!0,!1),tt=b().join(""),(n.clearMaskOnLostFocus||n.clearIncomplete)&&e._valueGet()==g().join("")&&e._valueSet("")}).bind("cut.inputmask",function(e){st=!0;var t=this,i=$(t),a=F(t);H(t,$.inputmask.keyCode.DELETE,a),S(t,b(),s().p,e,tt!=b().join("")),t._valueGet()==g().join("")&&i.trigger("cleared"),n.showTooltip&&i.prop("title",s().mask)}).bind("complete.inputmask",n.oncomplete).bind("incomplete.inputmask",n.onincomplete).bind("cleared.inputmask",n.oncleared),rt.bind("keydown.inputmask",W).bind("keypress.inputmask",q).bind("keyup.inputmask",V),u||rt.bind("compositionstart.inputmask",J).bind("compositionupdate.inputmask",Z).bind("compositionend.inputmask",Y),"paste"===d&&rt.bind("input.inputmask",z),N(e);var r=$.isFunction(n.onBeforeMask)?n.onBeforeMask.call(e,e._valueGet(),n)||e._valueGet():e._valueGet();j(e,!0,!1,r.split(""));var o=b().slice();tt=o.join("");var p;try{p=document.activeElement}catch(f){}K(o)===!1&&n.clearIncomplete&&l(),n.clearMaskOnLostFocus&&(o.join("")==g().join("")?o=[]:I(o)),S(e,o),p===e&&F(e,M(c())),L(e)}}var et=!1,tt,it,at,nt,rt,ot=!1,st=!1,lt=!1,ut,ct=!0;if(void 0!=i)switch(i.action){case"isComplete":return rt=$(i.el),a=rt.data("_inputmask").maskset,n=rt.data("_inputmask").opts,K(i.buffer);case"unmaskedvalue":return rt=i.$input,a=rt.data("_inputmask").maskset,n=rt.data("_inputmask").opts,et=i.$input.data("_inputmask").isRTL,G(i.$input);case"mask":tt=b().join(""),X(i.el);break;case"format":rt=$({}),rt.data("_inputmask",{maskset:a,opts:n,isRTL:n.numericInput}),n.numericInput&&(et=!0);var dt=($.isFunction(n.onBeforeMask)?n.onBeforeMask.call(rt,i.value,n)||i.value:i.value).split("");return j(rt,!1,!1,et?dt.reverse():dt),$.isFunction(n.onBeforeWrite)&&n.onBeforeWrite.call(this,void 0,b(),0,n),i.metadata?{value:et?b().slice().reverse().join(""):b().join(""),metadata:rt.inputmask("getmetadata")}:et?b().slice().reverse().join(""):b().join("");case"isValid":rt=$({}),rt.data("_inputmask",{maskset:a,opts:n,isRTL:n.numericInput}),n.numericInput&&(et=!0);var dt=i.value.split("");j(rt,!1,!0,et?dt.reverse():dt);for(var pt=b(),ft=B(),vt=pt.length-1;vt>ft&&!C(vt);vt--);return pt.splice(ft,vt+1-ft),K(pt)&&i.value==pt.join("");case"getemptymask":return rt=$(i.el),a=rt.data("_inputmask").maskset,n=rt.data("_inputmask").opts,g();case"remove":var mt=i.el;rt=$(mt),a=rt.data("_inputmask").maskset,n=rt.data("_inputmask").opts,mt._valueSet(G(rt)),rt.unbind(".inputmask"),rt.removeData("_inputmask");var ht;Object.getOwnPropertyDescriptor&&(ht=Object.getOwnPropertyDescriptor(mt,"value")),ht&&ht.get?mt._valueGet&&Object.defineProperty(mt,"value",{get:mt._valueGet,set:mt._valueSet}):document.__lookupGetter__&&mt.__lookupGetter__("value")&&mt._valueGet&&(mt.__defineGetter__("value",mt._valueGet),mt.__defineSetter__("value",mt._valueSet));try{delete mt._valueGet,delete mt._valueSet}catch(kt){mt._valueGet=void 0,mt._valueSet=void 0}break;case"getmetadata":if(rt=$(i.el),a=rt.data("_inputmask").maskset,n=rt.data("_inputmask").opts,$.isArray(a.metadata)){for(var gt,bt=c(),yt=bt;yt>=0;yt--)if(s().validPositions[yt]&&void 0!=s().validPositions[yt].alternation){gt=s().validPositions[yt].alternation;break}return void 0!=gt?a.metadata[s().validPositions[bt].locator[gt]]:a.metadata[0]}return a.metadata}}if(void 0===$.fn.inputmask){var r=navigator.userAgent,o=null!==r.match(new RegExp("iphone","i")),s=null!==r.match(new RegExp("android.*safari.*","i")),l=null!==r.match(new RegExp("android.*chrome.*","i")),u=null!==r.match(new RegExp("android.*firefox.*","i")),c=/Kindle/i.test(r)||/Silk/i.test(r)||/KFTT/i.test(r)||/KFOT/i.test(r)||/KFJWA/i.test(r)||/KFJWI/i.test(r)||/KFSOWI/i.test(r)||/KFTHWA/i.test(r)||/KFTHWI/i.test(r)||/KFAPWA/i.test(r)||/KFAPWI/i.test(r),d=e("paste")?"paste":e("input")?"input":"propertychange";$.inputmask={defaults:{placeholder:"_",optionalmarker:{start:"[",end:"]"},quantifiermarker:{start:"{",end:"}"},groupmarker:{start:"(",end:")"},alternatormarker:"|",escapeChar:"\\",mask:null,oncomplete:$.noop,onincomplete:$.noop,oncleared:$.noop,repeat:0,greedy:!0,autoUnmask:!1,removeMaskOnSubmit:!1,clearMaskOnLostFocus:!0,insertMode:!0,clearIncomplete:!1,aliases:{},alias:null,onKeyUp:$.noop,onBeforeMask:void 0,onBeforePaste:void 0,onBeforeWrite:void 0,onUnMask:void 0,showMaskOnFocus:!0,showMaskOnHover:!0,onKeyValidation:$.noop,skipOptionalPartCharacter:" ",showTooltip:!1,numericInput:!1,rightAlign:!1,undoOnEscape:!0,radixPoint:"",radixFocus:!1,nojumps:!1,nojumpsThreshold:0,keepStatic:void 0,definitions:{9:{validator:"[0-9]",cardinality:1,definitionSymbol:"*"},a:{validator:"[A-Za-zА-яЁёÀ-ÿµ]",cardinality:1,definitionSymbol:"*"},"*":{validator:"[0-9A-Za-zА-яЁёÀ-ÿµ]",cardinality:1}},ignorables:[8,9,13,19,27,33,34,35,36,37,38,39,40,45,46,93,112,113,114,115,116,117,118,119,120,121,122,123],isComplete:void 0,canClearPosition:$.noop,postValidation:void 0},keyCode:{ALT:18,BACKSPACE:8,CAPS_LOCK:20,COMMA:188,COMMAND:91,COMMAND_LEFT:91,COMMAND_RIGHT:93,CONTROL:17,DELETE:46,DOWN:40,END:35,ENTER:13,ESCAPE:27,HOME:36,INSERT:45,LEFT:37,MENU:93,NUMPAD_ADD:107,NUMPAD_DECIMAL:110,NUMPAD_DIVIDE:111,NUMPAD_ENTER:108,NUMPAD_MULTIPLY:106,NUMPAD_SUBTRACT:109,PAGE_DOWN:34,PAGE_UP:33,PERIOD:190,RIGHT:39,SHIFT:16,SPACE:32,TAB:9,UP:38,WINDOWS:91},masksCache:{},escapeRegex:function(e){var t=["/",".","*","+","?","|","(",")","[","]","{","}","\\","$","^"];return e.replace(new RegExp("(\\"+t.join("|\\")+")","gim"),"\\$1")},format:function(e,t,r){var o=$.extend(!0,{},$.inputmask.defaults,t);return i(o.alias,t,o),n({action:"format",value:e,metadata:r},a(o),o)},isValid:function(e,t){var r=$.extend(!0,{},$.inputmask.defaults,t);
return i(r.alias,t,r),n({action:"isValid",value:e},a(r),r)}},$.fn.inputmask=function(e,t){function r(e,t,a){var n=$(e);n.data("inputmask-alias")&&i(n.data("inputmask-alias"),{},t);for(var r in t){var o=n.data("inputmask-"+r.toLowerCase());void 0!=o&&("mask"==r&&0==o.indexOf("[")?(t[r]=o.replace(/[\s[\]]/g,"").split("','"),t[r][0]=t[r][0].replace("'",""),t[r][t[r].length-1]=t[r][t[r].length-1].replace("'","")):t[r]="boolean"==typeof o?o:o.toString(),a&&(a[r]=t[r]))}return t}var o=$.extend(!0,{},$.inputmask.defaults,t),s;if("string"==typeof e)switch(e){case"mask":return i(o.alias,t,o),s=a(o),void 0==s?this:this.each(function(){n({action:"mask",el:this},$.extend(!0,{},s),r(this,o))});case"unmaskedvalue":var l=$(this);return l.data("_inputmask")?n({action:"unmaskedvalue",$input:l}):l.val();case"remove":return this.each(function(){var e=$(this);e.data("_inputmask")&&n({action:"remove",el:this})});case"getemptymask":return this.data("_inputmask")?n({action:"getemptymask",el:this}):"";case"hasMaskedValue":return this.data("_inputmask")?!this.data("_inputmask").opts.autoUnmask:!1;case"isComplete":return this.data("_inputmask")?n({action:"isComplete",buffer:this[0]._valueGet().split(""),el:this}):!0;case"getmetadata":return this.data("_inputmask")?n({action:"getmetadata",el:this}):void 0;default:return i(o.alias,t,o),i(e,t,o)||(o.mask=e),s=a(o),void 0==s?this:this.each(function(){n({action:"mask",el:this},$.extend(!0,{},s),r(this,o))})}else{if("object"==typeof e)return o=$.extend(!0,{},$.inputmask.defaults,e),i(o.alias,e,o),s=a(o),void 0==s?this:this.each(function(){n({action:"mask",el:this},$.extend(!0,{},s),r(this,o))});if(void 0==e)return this.each(function(){var e=$(this).attr("data-inputmask");if(e&&""!=e)try{e=e.replace(new RegExp("'","g"),'"');var a=$.parseJSON("{"+e+"}");$.extend(!0,a,t),o=$.extend(!0,{},$.inputmask.defaults,a),o=r(this,o),i(o.alias,a,o),o.alias=void 0,$(this).inputmask("mask",o)}catch(n){}if($(this).attr("data-inputmask-mask")||$(this).attr("data-inputmask-alias")){o=$.extend(!0,{},$.inputmask.defaults,{});var s={};o=r(this,o,s),i(o.alias,s,o),o.alias=void 0,$(this).inputmask("mask",o)}})}}}return $.fn.inputmask}(jQuery);