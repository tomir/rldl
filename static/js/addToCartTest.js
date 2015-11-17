(function($){
	if ($==null) {
		throw new Error();
	}
	
	/* RLDL init */
	var Route=new RLDL.Route();
	
	var url=Route.request('u');
	var cart=Route.request('c');
	
	var d=$.Deferred();
	
	var popup;
	
	$(function(){
		var iframe=$('#atci');
		var form=$('#atcf');
		
		iframe.load(function(){
			d.resolve();
		});
		iframe.attr('src', cart);
		
		form.attr('action', cart);
		form.submit();
		popup=window.open(cart);
		
		$('body').click(function(){
			popup=window.open(cart);
		});
	});
	
	d.done(function(){
		window.setTimeout(function(){
			//window.location=url;
			popup.location=url;
		}, 2500);
	});

})(typeof jQuery=="function" ? jQuery : null);