/* RLDL Api */
var api=new RLDL.Api();
/* RLDL templates */
var tl=new RLDL.Template();
/* RLDL i18n handler */
var ll=new RLDL.I18n();
ll.load('privacy');

/** Realdeal */
var $RD = {};

$RD.isMobile=false;
$RD.loader={
	counter: 0,
	start: function() {
		if (this.counter===0) {
			$('body').addClass('loading');
		}
		this.counter++;
	},
	end: function(force) {
		this.counter--;
		if (this.counter===0 || force) {
			this.counter=0;
			$('body').removeClass('loading');
		}
	}
};
$RD.reloadContent = function() {
	$RD.hideModal();
	$RD.loader.start();
	$RD.checkLogin().done(function(data){
		$('#body').empty().append(tl.render('body', data));
		$('#terms a').html(ll._('App terms and privacy policy'));
		if (data.user.login==true) {
			$RD.loadFollows();
		}
		$('#top').empty().append(tl.render('top', data));
		$('title').html(ll._('User account and privacy'));
		$RD.actionFix();
		$RD.loader.end();
	});
	
};

$RD.actionFix = function() {
	$('a:not(.data-action)[data-action]').addClass('data-action').click(function(e){
		e.preventDefault();
		var action=$(this).data('action').split('/');
		switch (action[0]) {
			case 'login':
				$RD.doLogin();
			break;
			case 'logout':
				$RD.doLogout();
			break;
			case 'loginWindow':
				if ($RD.isMobile) {
					window.location.href=$(this).attr('href');
				}
				else {
					var loginWindow=window.open($(this).attr('href'));
					var loginCounter=0;
					var polling=function(){
						if (loginWindow && loginWindow.closed) {
						  $RD.reloadContent();
						  loginCounter=0;
						} 
						else {
							loginCounter++;
							if (loginCounter>=10) {
								loginCounter=0;
								api.get('auth').done(function(data){
									if (data.user==true) {
										$RD.reloadContent();
									}
									else {
										setTimeout(polling,1000);
									}
								});
							}
							else {
								setTimeout(polling,1000);
							}
						}
					}
					polling();
				}
			break;
			case 'unfollow':
				if (action.length==2) {
					$RD.showModal({
						msg: 'Are you sure you want to unfollow?',
						buttons: [
							{
								title: 'Yes',
								action: 'unfollowConfirmed/'+action[1]
							},
							{
								title: 'No',
								action: 'hideModal'
							}
						]
					});
				}
			break;
			case 'unfollowConfirmed':
				$RD.hideModal();
				if (action.length==2) {
					$RD.loader.start();
					api.delete('campaign/'+action[1]+'/follow').done(function(){
						$('#follow-'+action[1]).remove();
						if ($('#follows ul li').length==0) {
							$('#follows').empty();
						}
						$RD.loader.end();
					}).fail(function(data){
						if (data.code==401) {
							$RD.doLogin();
						}
						$RD.loader.end();
					});
				}
			break;
			case 'delete':
				$RD.showModal({
					msg: 'Are you sure you want to delete your acount?',
					buttons: [
						{
							title: 'Yes',
							action: 'deleteConfirmed'
						},
						{
							title: 'No',
							action: 'hideModal'
						}
					]
				});
			break;
			case 'deleteConfirmed':
				$RD.doDelete();
			break;
			case 'hideModal':
				$RD.hideModal();
			break;
		}
	});
};

$RD.checkLogin = function() {
	var d=$.Deferred();
	api.get('auth').done(function(data){
		if (data.user==true) {
			api.get('me').done(function(user){
				RLDL._config.set({
					locale: user.locale
				});
				
				data.user=user;
				data.user.login=true;
				
				ll.load('privacy').done(function(){
					d.resolve(data);
				});
			}).fail(function(){
				d.resolve(data);
			});
		}
		else {
			api.get('auth/user').done(function(user){
				data.user={};
				if (user.probability>0.5) {
					RLDL._config.set({
						locale: user.locale
					});
					data.user=user;
					data.user.login=false;
				}
				else {
					if (user.hasOwnProperty('locale')) {
						RLDL._config.set({
							locale: user.locale
						});
					}
					data.user.login=false;
				}
				ll.load('privacy').done(function(){
					d.resolve(data);
				});
			}).fail(function(){
				d.resolve(data);
			});
		}
	}).fail(function(){
		d.resolve();
	});
	return d;
};

$RD.doLogout=function(){
	$RD.loader.start();
	api.delete('auth').always(function(){
		$RD.reloadContent();
		$RD.loader.end();
	});
};

$RD.doDelete=function(){
	$RD.loader.start();
	api.delete('me').always(function(){
		$RD.reloadContent();
		$RD.showModal({
			msg: 'Your account has been deleted.',
			buttons: [
				{
					title: 'Close',
					action: 'hideModal'
				}
			]
		});
		$RD.loader.end();
	});
};

$RD.doLogin=function(){
	$RD.loader.start();
	api.get('auth/methods').done(function(response){
		var view={
			buttons: [],
			msg: 'Login using your favorite platform.'
		};
		$.each(response.data,function(i){
			view.buttons.push({
				title: response.data[i].name,
				action: 'loginWindow',
				url: response.data[i].url+'?redirect='+($RD.isMobile ? encodeURI(window.location.href) : 'close')
			});
		});
		$RD.showModal(view);
		$RD.loader.end();
	});
};

$RD.showModal=function(view){
	$('#modal').empty().append(tl.render('modal',view));
	$RD.actionFix();
	$('body').addClass('modal');
}
$RD.hideModal=function(){
	$('body').removeClass('modal');
	$('#modal').empty();
}

$RD.loadFollows=function(){
	$RD.loader.start();
	var d=$.Deferred();
	
	var d=$RD.loadFollows.loader('me/follows?limit=100');
	
	d.done(function(data){
		if (typeof data!='null') {
			$('#follows').empty().append(tl.render('follows', {
				campaigns: data
			}));
			if ($('#follows ul li').length==0) {
				$('#follows').empty();
			}
			else {
				$RD.actionFix();
			}
		}
		$RD.loader.end();
	});
	
};
$RD.loadFollows.loader=function(url){
	var d=$.Deferred();
	api.get(url).done(function(response){
		if (response.hasOwnProperty('paging')) {
			$RD.loadFollows.loader(response.paging.next).done(function(data){
				d.resolve($.merge(response.data,(typeof data!='null' ? data : [])));
			});
		}
		else {
			d.resolve(response.data);
		}
	}).fail(function(){
		d.resolve(null);
	});
	return d;
}

$(function(){
	$RD.loader.start();
	$RD.isMobile=$('body').hasClass('mobile');
});

$.when(
	tl.setTemplateFile('privacy','privacy/layout'),
	tl.setTemplateFile('top','privacy/top'),
	tl.setTemplateFile('modal','privacy/modal'),
	tl.setTemplateFile('body','privacy/body'),
	tl.setTemplateFile('follows','privacy/follows')
).done(function(){
	$(function(){
		$RD.reloadContent();
		$RD.loader.end();
	});
});