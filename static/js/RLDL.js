(function(window,$){
	if ($==null) {
		throw new Error("RLDL requires jQuery.");
	}
	
	var RLDL={
		_$: $,
		_api: 'https://api.rldl.net',
		_cdn: 'https://cdn.rldl.net',
		_config: {
			_: {},
			set: function(data){
				$this=this;
				if (!$.isPlainObject(data)) {
					throw new Error("Wrong sets data.");
				}
				$.extend($this._,data);
			},
			get :function(name){
				if (typeof this._[name]!='undefined') {
					return this._[name]
				}
				return null;
			}
		},
		config: function(name, value) {
			if (typeof value!='undefined') {
				var set={};
				set[name]=value;
				return RLDL._config.set(set)
			}
			else {
				return RLDL._config.get(name);
			}
		},
		loaded: [],
		load: function(modules) {
			if (typeof modules=='undefined') {
				throw new Error();
			}
			
			var d=$.Deferred();
			
			if (typeof modules=='string') {
				modules=[modules];
			}
			else if (!$.isArray(modules) || modules.length==0) {
				throw new Error();
			}
			
			$.each(modules, function(i, module) {
				if ((['Api', 'I18n', 'Template', 'Route']).indexOf(module)>=0 && RLDL.loaded.indexOf(module)==-1) {
					RLDL.loaded.push(module);
					$.ajax({
						url: RLDL._cdn+'/static/js/RLDL/'+module+'-min.js',
						dataType: "script",
						cache: true
					}).done(function(){
						if (i==(modules.length-1)) {
							d.resolve(RLDL.loaded);
						}
					}).fail(function(){
						throw new Error("RLDL load module error.");
					});
				}
				else if (i==(modules.length-1)) {
					d.resolve(RLDL.loaded);
				}
			});
			
			return d;
		}
	};
	
	window['RLDL']=RLDL;
})(typeof window !== "undefined" ? window : this, typeof jQuery=="function" ? jQuery : null);