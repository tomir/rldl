!function(e,$){if(null==$)throw new Error("RLDL requires jQuery.");var n={_$:$,_api:"https://api.rldl.net",_cdn:"https://cdn.rldl.net",_config:{_:{},set:function(e){if($this=this,!$.isPlainObject(e))throw new Error("Wrong sets data.");$.extend($this._,e)},get:function(e){return"undefined"!=typeof this._[e]?this._[e]:null}},config:function(e,t){if("undefined"!=typeof t){var r={};return r[e]=t,n._config.set(r)}return n._config.get(e)},loaded:[],load:function(e){if("undefined"==typeof e)throw new Error;var t=$.Deferred();if("string"==typeof e)e=[e];else if(!$.isArray(e)||0==e.length)throw new Error;return $.each(e,function(r,i){["Api","I18n","Template","Route"].indexOf(i)>=0&&-1==n.loaded.indexOf(i)?(n.loaded.push(i),$.ajax({url:n._cdn+"/static/js/RLDL/"+i+"-min.js",dataType:"script",cache:!0}).done(function(){r==e.length-1&&t.resolve(n.loaded)}).fail(function(){throw new Error("RLDL load module error.")})):r==e.length-1&&t.resolve(n.loaded)}),t}};e.RLDL=n}("undefined"!=typeof window?window:this,"function"==typeof jQuery?jQuery:null);