$(function(){
	$(window).resize(function(){
		$('#body').css({
			height: $(window).height()-$('#head').height()
		});
	}).resize();
});