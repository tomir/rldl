(function(window,$){
	if ($==null) {
		throw new Error();
	}
	
	/* RLDL init */
	var Route=new RLDL.Route();
	
	var url=Route.request('u');
	var cart=Route.request('c');
	
	var d=$.Deferred();
	
	$(function(){
		var iframe=$('#atci');
		var form=$('#atcf');
		
		iframe.load(function(){
			iframe.unbind('load');
			d.resolve();
		});
		iframe.attr('src', cart);
		
//		form.attr('action', cart);
//		form.submit();
	});
	
	d.done(function(){
		window.setTimeout(function(){
			window.location=url;
		}, 1000);
	});

})(typeof window !== "undefined" ? window : this, typeof jQuery=="function" ? jQuery : null);