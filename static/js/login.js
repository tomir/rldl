(function(){
/* RLDL Api */
var api=new RLDL.Api();
/* RLDL templates */
var tl=new RLDL.Template();
/* RLDL i18n handler */
var ll=new RLDL.I18n();
var route=new RLDL.Route();

var Loader=(function(){
	this.counter=0;
	this.start=function() {
		if (this.counter===0) {
			$('body').addClass('loading');
		}
		this.counter++;
	};
	this.end=function(force) {
		this.counter--;
		if (this.counter===0 || force) {
			this.counter=0;
			$('body').removeClass('loading');
		}
	};
	return this;
})();

$(function(){
	Loader.start();
});

$.when(
	api.get('auth/methods'),
	api.get('auth/user'),
	tl.setTemplateFile('list','login/list')
).done(function(methods, user){
	$.each(methods.data, function(i, method){
		var redirect_url=route.request('redirect','string');
		method.url+='?redirect='+encodeURI(redirect_url==null ? 'close' : redirect_url)+'&publish='+publish_redirect;
	});
	if (user.hasOwnProperty('locale')) {
		RLDL._config.set({
			locale: user.locale
		});
	}
	
	ll.load('login').done(function(){
		$(function(){
			$('title').html(ll._('Log in'));
			
			$('#content').empty().append(tl.render('list',{
				methods: methods.data
			}));
			
			Loader.end();
		});
	});
	
});
})();