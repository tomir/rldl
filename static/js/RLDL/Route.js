(function(RLDL){
	"use strict";
	if (RLDL==null) {
		throw new Error("RLDL undefined.");
	}
	
	var Route=function(reload){
		if (Route._singletonInstance!=null && reload!=true) {
			return Route._singletonInstance;
		}
		
		this._path=location.pathname.split('/');
		
		this._path.shift();
		
		this._request={};
		
		
		var request = window.location.search;
		
		if (request.length>0) {
			request=request.split("?")[1].split("&");
		
			for(var i = 0; i < request.length; i++){
				var param=request[i].split("=");
				this._request[param[0]]=decodeURIComponent(param[1]);
			}
		}
		
		this.request=function(field, type) {
			var types=['integer','int','string','bool'];
			
			if (typeof this._request[field]!=='undefined' && (typeof type=='undefined' || type==null || types.indexOf(type)>=0)) {
				var value=this._request[field];
				switch (type) {
					case 'bool':
						if (value=='true' || value=='1') {
							return true;
						}
						else {
							return false;
						}
					break;
					case 'int':
					case 'iteger':
						if (isNaN(value)) {
							return null;
						}
					break;
					default:
						value=String(value.replace(/\+/g, ' '));
						if (value.length==0) {
							return null;
						}
				}
				return value;
			}
			return null;
		}
		
		this.getParam=function(){
			return this._path.shift();
		};
		
		this.domain=function(){
			return location.protocol+'//'+location.hostname;
		}
		
		this.url=function(){
			return this.domain()+location.pathname;
		}
		
		Route._singletonInstance=this;
		return this;
	};
	Route._singletonInstance=null;
	Route.getInstance=function(reload){
		if (this._singletonInstance==null || reload==true) {
			this._singletonInstance=new Route(reload);
		}
		return this._singletonInstance;
	};
	
	RLDL.Route=Route;
	
})(typeof RLDL=='object' ? RLDL : null);