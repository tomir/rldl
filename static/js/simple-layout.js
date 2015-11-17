(function ($) {
    var originalAddClassMethod = $.fn.addClass;
    var originalRemoveClassMethod = $.fn.removeClass;
    $.fn.addClass = function(){
    	var action=(jQuery(this).hasClass(arguments[0]) ? 'update' : 'add');
    	var result = originalAddClassMethod.apply(this, arguments);
    	jQuery(this).triggerHandler('classChange', [action, arguments[0]]);
    	return result;
    };
    $.fn.removeClass = function(){
    	var result = originalRemoveClassMethod.apply(this, arguments);
    	jQuery(this).triggerHandler('classChange', ['remove', arguments[0]]);
    	return result;
    };
})(jQuery);

var layout=function(){
	this.active=0;
	this.bg='';
	this.video=false;
	return this;
}

layout.prototype.move=function(e){
	$('#modal .outer').each(function(){
		var h=$(this).outerHeight(true);
		var w=$(this).outerWidth(true);
		$(this).css({
			left: (w<$(window).width() ? Math.round(($(window).width()-w)/2) : 'auto'),
			top: (h<$(window).height() ? Math.round(($(window).height()-h)/2) : 'auto')
		});
	});
	
}

$(function(){
	var l=new layout();
	
	$('body').addClass('ready').bind('classChange', function(e, a, c){ 
		l.move();
	});
	
	$(window).bind('scroll resize webkitTransitionEnd transitionend touchend touchmove',function(){
		l.move();
	});
});