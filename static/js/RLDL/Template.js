(function(RLDL, Engine){
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
	
	var Template=function(cdn, fileext){
		if (Template._singletonInstance!=null) {
			return Template._singletonInstance;
		}
		
		this.cdn=(typeof cdn=='string' ? cdn+'/' : RLDL._cdn+'/templates/');
		
		this.ext=(typeof fileext=='string' ? fileext : 'mustache');
		
		this.dataType=(this.cdn.indexOf('/')===0 ? 'html' : 'jsonp');
		
		this.view={};
		
		this.templates={};
		
		this.urlToTemplate={};
		
		this.helpers={};
		
		var $this=this;
		
		this.setView=function(view) {
			if (typeof view!='object') {
				throw new Error('Wrong view data.');
			}
			this.view=$.extend(this.view,view);
		};
		
		this.setHelpers=function(helpers) {
			if (typeof helpers!='object') {
				throw new Error('Wrong helpers.');
			}
			$.each(helpers, function(name, helper){
				if (typeof helper=='function') {
					$this.helpers[name]=function(){
						return helper;
					}
				}
			});
		};
		
		if (typeof RLDL.I18n=='function') {
			this.setHelpers({
				i18n: function(i, render){
					return RLDL.I18n.getInstance()._(render(i));
				}
			})
		}
		
		this.render=function(name,view) {
			if (typeof view!='object') {
				view={};
			}
			if (this.templates[name]==undefined) {
				throw new Error('Template '+name+' not set.');
			}
			var $this=this;
			return Engine.render(this.templates[name],$.extend({},$this.view,view,$this.helpers),$this.templates);
		};
		
		this.setTemplateFile=function(name,url) {
			if (typeof name!='string') {
				throw new Error('Wrong template name.');
			}
			
			var d=$.Deferred();
			if (url==undefined) {
				url=name.replace(/_/g, '/');
			}
			
			if (this.urlToTemplate.hasOwnProperty(url)) {
				$this.templates[name]=$this.urlToTemplate[url];
				d.resolve();
			}
			else {
				var $this=this;
				$.ajax({
					type: 'GET',
					url: $this.cdn+url+'.'+$this.ext,
					dataType: $this.dataType
				}).done(function(data){
					if ($this.dataType=='html') {
						$this.templates[name]=data;
					}
					else {
						$this.templates[name]=data.template;
					}
					$this.urlToTemplate[url]=$this.templates[name];
					d.resolve();
				}).fail(function(){
					d.reject();
					throw new Error('Loading template '+name+' failed.');
				});
			}
			return $.when(d, EngineLoaded);
		};
		
		this.setTemplateString=function(name,string) {
			if (typeof name!='string') {
				throw new Error('Wrong template name.');
			}
			if (typeof string!='string') {
				throw new Error('Wrong template value.');
			}
			
			var d=$.Deferred();
			this.templates[name]=string;
			d.resolve();
			return $.when(d, EngineLoaded);
		};
		
		Template._singletonInstance=this;
		return this;
	};
	Template._singletonInstance=null;
	Template.getInstance=function(){
		if (this._singletonInstance==null) {
			this._singletonInstance=new Template();
		}
		return this._singletonInstance;
	};
	
	RLDL.Template=Template;
	
})(typeof RLDL=='object' ? RLDL : null, typeof Mustache=="object" ? Mustache : null);