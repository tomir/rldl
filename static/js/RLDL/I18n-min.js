!function(e,t,n){"use strict";if(null==e)throw new Error("RLDL undefined.");var $=e._$,l;null==t?(l=$.ajax({url:e._cdn+"/static/js/vendor/mustache-min.js",dataType:"script",cache:!0}),l.done(function(){t=Mustache})):(l=$.Deferred(),l.resolve());var a={},r={},o={},s=function(t){var l=$.Deferred(),s=$.Deferred();return r.hasOwnProperty(t)?l.resolve(r[t]):$.ajax({type:"GET",url:t,dataType:0===t.indexOf("http")?"jsonp":"json",cache:!0}).done(function(e){r[t]=e,l.resolve(r[t])}),l.done(function(t){t.hasOwnProperty("__sets")?($.extend(o,t.__sets),delete t.__sets,$.extend(a,t),o.hasOwnProperty("moment_lang")&&null!=n?$.ajax({type:"GET",url:e._cdn+"/static/js/vendor/moment/"+o.moment_lang+"-min.js",dataType:"script",cache:!0}).done(function(){n.locale(o.moment_lang)}).always(function(){s.resolve()}):s.resolve()):($.extend(a,t),s.resolve())}),s};null==e._config.get("default_locale")&&e._config.set({default_locale:"en_US"});var i=function(t,n){return null!=this._singletonInstance?this._singletonInstance:(this.cdn="string"==typeof t?t+"/":e._cdn+"/i18n/",this.ext="string"==typeof n?n:"json",this)};i.prototype.load=function(t){var n=$.Deferred();$.isArray(t)?0==t.length&&n.resolve():"undefined"==t||""==t?(t=[],n.resolve()):t=[t];var a=e._config.get("default_locale"),r=e._config.get("locale"),o=a!=r&&null!=r?2*t.length:t.length,i=0,c=this;return $.each(t,function(e,t){s(c.cdn+a+"/"+t+"."+c.ext).done(function(){i++,a!=r&&null!=r?s(c.cdn+r+"/"+t+"."+c.ext).always(function(){i++,i==o&&n.resolve()}):i==o&&n.resolve()})}),$.when(n,l)},i.prototype.set=function(t){var r=$.Deferred();if("object"==typeof t){var s=e._config.get("default_locale"),i=e._config.get("locale"),c=0;$.each(["default",s,i],function(l,s){if(c++,null!=s&&t.hasOwnProperty(s)&&"object"==typeof t[s]){var i=t[s];i.hasOwnProperty("__sets")?($.extend(o,i.__sets),delete i.__sets,$.extend(a,i),o.hasOwnProperty("moment_lang")&&null!=n?$.ajax({type:"GET",url:e._cdn+"/static/js/vendor/moment/"+o.moment_lang+"-min.js",dataType:"script",cache:!0}).done(function(){n.locale(o.moment_lang)}).always(function(){3==c&&r.resolve()}):3==c&&r.resolve()):($.extend(a,i),3==c&&r.resolve())}else 3==c&&r.resolve()})}else r.reject();return $.when(r,l)},i.prototype._=function(e,n){void 0==n&&(n=[]);var l=/{{.+}}/i,r=e;try{var o=$.parseJSON(e)}catch(s){o=null}$.isPlainObject(o)?(r=e=o.template.string,o.template.hasOwnProperty("variant")&&(0==o.template.variant.length&&(o.template.variant="default"),e+="/"+o.template.variant),$.isPlainObject(o.view)||(o.view={})):o=null;for(var i=e.replace(/ /g,"_").replace(/[^a-zA-Z0-9\/_]+/g,""),c=i.split("/"),u=a,f=0;f<c.length;f++){if(!u.hasOwnProperty(c[f]))return u.hasOwnProperty("default")&&"string"===$.type(u["default"])?l.test(u["default"])?t.render(u["default"],o?o.view:n):u["default"]:r;if(!(f<c.length-1))return l.test(u[c[f]])?t.render(u[c[f]],o?o.view:n):u[c[f]];if(!$.isPlainObject(u[c[f]]))return"string"===$.type(u[c[f]])?l.test(u[c[f]])?t.render(u[c[f]],o?o.view:n):u[c[f]]:"string"===$.type(u["default"])?l.test(u["default"])?t.render(u["default"],o?o.view:n):u["default"]:r;u=u[c[f]]}},i._singletonInstance=null,i.getInstance=function(){return null==this._singletonInstance&&(this._singletonInstance=new i),this._singletonInstance},e.I18n=i}("object"==typeof RLDL?RLDL:null,"object"==typeof Mustache?Mustache:null,"undefined"!=typeof moment?moment:null);