(function(RLDL, Engine, moment){
	"use strict";
	if (RLDL==null) {
		throw new Error("RLDL undefined.");
	}
	
	var $=RLDL._$;
	
	var EngineLoaded;
	
	if (Engine==null) {
		EngineLoaded=$.ajax({
			url: RLDL._cdn+'/static/js/vendor/mustache-min.js',
			dataType: "script",
			cache: true
		});
		
		EngineLoaded.done(function(){
			Engine=Mustache;
		});
	}
	else {
		EngineLoaded=$.Deferred();
		EngineLoaded.resolve();
	}
	
	var cache={};
	
	var files={};
	
	var sets={};
	
	var loadFile=function(file){
		var d=$.Deferred();
		var d1=$.Deferred();
		if (!files.hasOwnProperty(file)) {
			$.ajax({
				type: 'GET',
				url: file,
				dataType: (file.indexOf('http')===0 ? 'jsonp' : 'json'),
				cache: true
			}).done(function(data){
				files[file]=data;
				d.resolve(files[file]);
			});
		}
		else {
			d.resolve(files[file]);
		}
		d.done(function(json){
			if (json.hasOwnProperty('__sets')){
				$.extend(sets,json.__sets);
				delete json.__sets;
				$.extend(cache,json);
				if (sets.hasOwnProperty('moment_lang') && moment!=null) {
					$.ajax({
						type: 'GET',
						url: RLDL._cdn+'/static/js/vendor/moment/'+sets.moment_lang+'-min.js',
						dataType: 'script',
						cache: true
					}).done(function(){
						moment.locale(sets.moment_lang);
					}).always(function(){
						d1.resolve();
					});
				}
				else {
					d1.resolve();
				}
			}
			else {
				$.extend(cache,json);
				d1.resolve();
			}
		});
		return d1;
	};
	
	if (RLDL._config.get('default_locale')==null) {
		RLDL._config.set({
			default_locale: 'en_US'
		});
	}
	
	var I18n=function(cdn, fileext){
		if (this._singletonInstance!=null) {
			return this._singletonInstance;
		}
		
		this.cdn=(typeof cdn=='string' ? cdn+'/' : RLDL._cdn+'/i18n/');
		
		this.ext=(typeof fileext=='string' ? fileext : 'json');
		
		return this;
	};
	I18n.prototype.load=function(file_names){
		var d=$.Deferred();
		
		if (!$.isArray(file_names)) {
			if (file_names=='undefined' || file_names=='') {
				file_names=[];
				d.resolve();
			}
			else {
				file_names=[file_names];
			}
		}
		else if (file_names.length==0) {
			d.resolve();
		}
		
		
		var DL=RLDL._config.get('default_locale');
		var L=RLDL._config.get('locale');
		
		var files_length=(DL!=L && L!=null) ? file_names.length*2 : file_names.length;
		
		var file_id=0;
		
		var $this=this;
		
		$.each(file_names, function(i, name){
			loadFile($this.cdn+DL+'/'+name+'.'+$this.ext).done(function(){
				file_id++;
				if (DL!=L && L!=null) {
					loadFile($this.cdn+L+'/'+name+'.'+$this.ext).always(function(){
						file_id++;
						if (file_id==files_length) {
							d.resolve();
						}
					});
				}
				else if (file_id==files_length) {
					d.resolve();
				}
			});
		});
		
		return $.when(d, EngineLoaded);
	};
	
	I18n.prototype.set=function(locales_data){
		var d=$.Deferred();
		
		if (typeof locales_data==='object') {
			var DL=RLDL._config.get('default_locale');
			var L=RLDL._config.get('locale');
			
			var locale_id=0;
			
			$.each(['default', DL, L], function(i, locale){
				locale_id++;
				
				if (locale!=null && locales_data.hasOwnProperty(locale) && typeof locales_data[locale]==='object') {
					var data=locales_data[locale];
					if (data.hasOwnProperty('__sets')){
						$.extend(sets,data.__sets);
						delete data.__sets;
						$.extend(cache,data);
						if (sets.hasOwnProperty('moment_lang') && moment!=null) {
							$.ajax({
								type: 'GET',
								url: RLDL._cdn+'/static/js/vendor/moment/'+sets.moment_lang+'-min.js',
								dataType: 'script',
								cache: true
							}).done(function(){
								moment.locale(sets.moment_lang);
							}).always(function(){
								if (locale_id==3) {
									d.resolve();
								}
							});
						}
						else {
							if (locale_id==3) {
								d.resolve();
							}
						}
					}
					else {
						$.extend(cache,data);
						if (locale_id==3) {
							d.resolve();
						}
					}
				}
				else if (locale_id==3) {
					d.resolve();
				}
			});
		}
		else {
			d.reject();
		}
		
		return $.when(d, EngineLoaded);
	};
		
	I18n.prototype._=function(r, view) {
		if (view==undefined) view=[];
		
		var rgt=/{{.+}}/i;
		
		var or=r;
		
		try	{
			var j=$.parseJSON(r);
		}
		catch(e) {
			j=null;
		}
		
		if ($.isPlainObject(j)) {
			or=r=j.template.string;
			if (j.template.hasOwnProperty('variant')) {
				if (j.template.variant.length==0) {
					j.template.variant='default';
				}
				r+='/'+j.template.variant;
			}
			if (!$.isPlainObject(j.view)) {
				j.view={};
			}
		}
		else {
			j=null;
		}
		
		
		var s=r.replace(/ /g, '_').replace(/[^a-zA-Z0-9\/_]+/g, "");
		
		var a=s.split('/');
		
		var o=cache;
		
		for (var i = 0; i < a.length; i++) {
			if (o.hasOwnProperty(a[i])) {
				if (i<(a.length-1)) {
					if ($.isPlainObject(o[a[i]])) {
						o=o[a[i]];
					}
					else if ($.type(o[a[i]])==='string') {
						if (rgt.test(o[a[i]])){
							return Engine.render(o[a[i]], j ? j.view : view);
						}
						return o[a[i]];
					}
					else if ($.type(o['default'])==='string') {
						if (rgt.test(o['default'])){
							return Engine.render(o['default'], j ? j.view : view);
						}
						return o['default'];
					}
					else return or;
				}
				else {
					if (rgt.test(o[a[i]])){
						return Engine.render(o[a[i]], j ? j.view : view);
					}
					return o[a[i]];
				}
			}
			else if (o.hasOwnProperty('default') && $.type(o['default'])==='string') {
				if (rgt.test(o['default'])){
					return Engine.render(o['default'], j ? j.view : view);
				}
				return o['default'];
			}
			else return or;
		}
	};
	
	I18n._singletonInstance=null;
	I18n.getInstance=function(){
		if (this._singletonInstance==null) {
			this._singletonInstance=new I18n();
		}
		return this._singletonInstance;
	};
	
	RLDL.I18n=I18n;
	
})(typeof RLDL=='object' ? RLDL : null, typeof Mustache=="object" ? Mustache : null, typeof moment!=="undefined" ? moment : null);