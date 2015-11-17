(function(RLDL){
	"use strict";
	if (RLDL==null) {
		throw new Error("RLDL undefined.");
	}
	
	var $=RLDL._$;
	
	var FormatFormData=function(obj, prefix, handler){
		var ret=handler==undefined ? {} : handler;
		$.each(obj,function(name, value){
			var var_prefix=prefix;
			if (var_prefix==undefined) {
				var_prefix=name;
			}
			else {
				var_prefix=prefix+'['+name+']';
			}
			if ($.isPlainObject(obj[name])) {
				FormatFormData(obj[name], var_prefix, ret);
			}
			else {
				ret[var_prefix]=value;
			}
		});
		
		return ret;
	};
	
	var CORSfix=(function(){
		var d=$.Deferred();
		$(function(){
			var iframe=$('<iframe>');
			iframe.css({
				width: 1,
				height: 1,
				position: 'absolute',
				bottom: 0,
				right: 0,
				opacity: 0
			});
			$('body').append(iframe);
			iframe.load(function(){
				d.resolve();
			});
			iframe.attr('src', 'https://login.rldl.net/blank');
		});
		return d;
	})();
	
	var Api=function(api){
		if (Api._singletonInstance!=null) {
			return Api._singletonInstance;
		}
		
		this.api=(typeof api=='string' ? api : RLDL._api);
		
		this.locale=null;
		
		this.call=function(type, url, data, files){
			var d=$.Deferred();
			
			type.toUpperCase();
			
			if (!$.isPlainObject(data)) {
				data={};
			}
			
			url=this.formatUrl(url);
			
			var locale=RLDL._config.get('locale');
			
			if (locale!=null && locale!=this.locale) {
				data.locale=this.locale=locale;
			}
			
			var sets={
				url: url,
				dataType: 'json',
				data: data,
				async: true,
				crossDomain: true,
				xhrFields: {
		        	withCredentials: true
		        }
			};
			
			switch (type) {
				case 'PUT':
					data['REQUEST_METHOD']=type;
					type='POST';
				case 'POST':
					if ($.isPlainObject(files) && window.FormData) {
						var fd = new FormData();
						$.each(FormatFormData(data),function(name, value){
							fd.append(name, value);
						});
						$.each(files,function(name, handler){
							if (typeof handler=='object' && handler.length==1) {
								fd.append(name, handler[0].files[0]);
							}
							else {
								fd.append(name, handler);
							}
						});
						$.extend(sets,{
							data: fd,
							cache: false,
							processData: false,
							contentType: false,
						});
					} 
				break;
				case 'DELETE':
					data['REQUEST_METHOD']=type;
					type='POST';
				break;
				default:
					data['REQUEST_METHOD']=type;
					type='POST';
			}
			
			sets.type=type;
			
			CORSfix.done(function(){});
			
			$.ajax(sets).done(function(data){
				d.resolve(data);
			}).fail(function(data){
				if (!data.hasOwnProperty('status') || data.status==0 || data.status==null || data.status==undefined) {
					data.status=503;
					data.statusText='Internet disconnected.';
				}
				
				var error = {
					code: data.status,
					message: null
				}
				if (data.hasOwnProperty('responseJSON') && data.responseJSON.hasOwnProperty('error')) {
					if (data.responseJSON.error.hasOwnProperty('code')) {
						error.code=data.responseJSON.error.code;
					}
					if (data.responseJSON.error.hasOwnProperty('message_i18n')) {
						error.message=data.responseJSON.error.message_i18n;
					}
					else if (data.responseJSON.error.hasOwnProperty('message')) {
						error.message=data.responseJSON.error.message;
					}
				}
				
				if (error.message==null && data.hasOwnProperty('statusText')) {
					error.message=data.statusText;
				}
				
				d.reject(error);
			});
			
			return d;
		}
		
		this.get=function(url, data) {
			return this.call('GET', url, data);
		}
		this.put=function(url, data, files) {
			if ($.isPlainObject(files)) return this.upload('PUT', url, data, files);
			return this.call('PUT', url, data, files);
		}
		this.delete=function(url, data) {
			return this.call('DELETE', url, data);
		}
		this.post=function(url, data, files) {
			if ($.isPlainObject(files)) return this.upload('POST', url, data, files);
			return this.call('POST', url, data, files);
		}
		this.upload=function(method, url, data, files) {
			var d=$.Deferred();
			
			url=this.formatUrl(url);
			
			var $this=this;
			
			this.call('GET', 'upload', {
				url: $this.formatUrl(url)
			}).done(function(response){
				if (response.hasOwnProperty('upload_url')) {
					$this.call(method, response.upload_url, data, files).done(function(response){
						d.resolve(response);
					}).fail(function(error){
						d.reject(error);
					});
				}
				else {
					d.reject({
						code: data.status,
						message: null
					});
				}
			}).fail(function(error){
				d.reject(error);
			});
			return d;
		}
		this.formatUrl=function(url){
			var urlregexp = /(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/;
			if (!urlregexp.test(url)) {
				url=this.api+'/'+url;
			}
			return url;
		}
		
		Api._singletonInstance=this;
		return this;
	};
	Api._singletonInstance=null;
	Api.getInstance=function(){
		if (this._singletonInstance==null) {
			this._singletonInstance=new Api();
		}
		return this._singletonInstance;
	};
	
	RLDL.Api=Api;
	
})(typeof RLDL=='object' ? RLDL : null);