(function ($) {

	function div() {
		return $('<div/>');
	}

	$.fn.imgSelector = function(options) {

		var $area = $(this);

		var settings = $.extend({
			// These are the defaults.
			maxInstances: 100,
			handles: true,
			onSelectEnd: function () {}
		}, options);
		
		function makehandles(selector) {
			var $newSelector = div().addClass('imgselector-resize').css({
				'top': 0, 'left': 0});

			$newSelector.bind('mousedown', function (e) {
				var oldWidth = selector.outerWidth();
				var oldHeight = selector.outerHeight();
				$(document).bind('mousemove', function(event){
					handlesResize(event, selector, 'lt', e, oldWidth-6, oldHeight-6);
				});
				
			});
			selector.append($newSelector);
			
			
			$newSelector = div().addClass('imgselector-resize').css({
				'top': 0, 'right': 0});
			$newSelector.bind('mousedown', function (e) {
				var oldWidth = selector.outerWidth();
				var oldHeight = selector.outerHeight();
				if (e.button == 0){
					$(document).bind('mousemove', function(event){
						handlesResize(event, selector, 'rt', e, oldWidth-6, oldHeight-6);
					});
				}
			});
			selector.append($newSelector);
			
			
			$newSelector = div().addClass('imgselector-resize').css({
				'bottom': 0, 'left': 0});
			$newSelector.bind('mousedown', function (e) {
				var oldWidth = selector.outerWidth();
				var oldHeight = selector.outerHeight();
				if (e.button == 0){
					$(document).bind('mousemove', function(event){
						handlesResize(event, selector, 'lb', e, oldWidth-6, oldHeight-6);
					});
				}
			});
			selector.append($newSelector);
			
			$newSelector = div().addClass('imgselector-resize').css({
				'bottom': 0, 'right': 0});
			$newSelector.bind('mousedown', function (e) {
				//if (e.button == 0){
					$(document).bind('mousemove', function(event){
						handlesResize(event, selector, 'rb');
					});
				//}
			});
			selector.append($newSelector);
		}

		function handlesResize(event, selector, pointer, e, oldWidth, oldHeight) {
			var areaW = $area.innerWidth();
			var areaH = $area.innerHeight();

			var position = selector.offset();

			switch(pointer) {

				case 'lt':
					if(selector.outerWidth() < areaW-position.left && selector.outerHeight() < areaH-position.top) {
						selector.width(oldWidth-(event.pageX-e.pageX)).height(oldHeight-(event.pageY-e.pageY)).css({top: event.pageY-e.pageY, left: event.pageX-e.pageX});
					}
				break;

				case 'rt':
					if(selector.outerWidth() < areaW-position.left && selector.outerHeight() < areaH-position.top) {
						selector.width(event.pageX-selector.position().left).height(oldHeight-(event.pageY-e.pageY)).css({top: event.pageY});
					}
				break;

				case 'rb':
					if(selector.outerWidth() < areaW-position.left && selector.outerHeight() < areaH-position.top) {
						selector.width(event.pageX-selector.position().left).height(event.pageY-selector.position().top);
					}
				break;

				case 'lb':
					if(selector.outerWidth() < areaW-position.left && selector.outerHeight() < areaH-position.top) {
						selector.width(oldWidth-(event.pageX-e.pageX)).height(event.pageY-selector.position().top).css({left: event.pageX });
					} else if(selector.outerWidth() < areaW-position.left && selector.outerHeight() >= areaH-position.top) {
						selector.width(areaW-position.left);
					}
				break;
			}
			
		}

		function handleMouseDown(e, resizeBox, area){

			if (e.button == 0){
				
				$(area).bind('mousemove', function(event){
					handleMouseMove(event, resizeBox, area, e);
				});

				$(area).bind('mouseup', function (e) {
					$(area).unbind('mousemove');
					
					if(resizeBox.find('.imgselector-close').length == 0) {
						
						var $newSelector = div().addClass('imgselector-close');
						resizeBox.append($newSelector);

						makehandles(resizeBox);

						$newSelector.bind('click', function () {
							resizeBox.remove();
						});
						
						var $newSelector2 = div().css({width : '100%', height: '100%', 'z-index': '999'});
						$newSelector2.bind('mousedown', function (ee) {
						
							$newSelector2.bind('mousemove', function(event){
								var resizeBoxW = $newSelector2.innerWidth();
								var resizeBoxH = $newSelector2.innerHeight();

								var areaW = $(area).innerWidth();
								var areaH = $(area).innerHeight();

								if(resizeBoxW + event.pageX < areaW && resizeBoxH + event.pageY < areaH) {
									//resizeBox.css({ left: event.pageX, top: event.pageY, bottom: -event.pageY, right: -event.pageX});
								}
							});

							$newSelector2.bind('mouseup', function (e) {
								$(document).unbind('mousemove');
							});
							
						});
						resizeBox.append($newSelector2);

					}
				});
				
				e.stopPropagation();
				
			}	
			

			$(resizeBox).bind('mousedown', function (ee) {
				if (ee.button == 0){ 
					$(document).bind('mousemove', function(event){
						
						var resizeBoxW = $(resizeBox).innerWidth();
						var resizeBoxH = $(resizeBox).innerHeight();
						
						var areaW = $(area).innerWidth();
						var areaH = $(area).innerHeight();
						
						if(resizeBoxW + event.pageX < areaW && resizeBoxH + event.pageY < areaH) {
							//resizeBox.css({ left: event.pageX, top: event.pageY, bottom: -event.pageY, right: -event.pageX});
						}
					});

					$(resizeBox).bind('mouseup', function (e) {
						$(document).unbind('mousemove');
					});
				}
				
				ee.stopPropagation();
			});

		}

		function handleMouseMove(e, resizeBox, area, e2){

			var min_x = $(resizeBox).offset().left;
			var min_y = $(resizeBox).offset().top;

			var areaW = $(area).innerWidth();
			var areaH = $(area).innerHeight();
	
			if(e.pageX-min_x < 0) {
				resizeBox.width((e2.pageX-e.pageX)).height((e2.pageY-e.pageY)).css({left: e.pageX, top: e.pageY});
			} else if(e.pageY-min_y < 0) {
				resizeBox.width((e.pageX)).height((e2.pageY-e.pageY)).css({top: e.pageY});
			} else if(e.pageX < areaW && e.pageY < areaH) {
				resizeBox.width(e.pageX - min_x).height(e.pageY - min_y);
				$('.cords').html('x:'+e.pageX+' y: '+e.pageY);	
			}
			
		}
		
		function getSelection() {
		
			var areaWidth = $area.innerWidth();
			var areaHeight = $area.innerHeight();

			var boxWidth = 0;
			var boxHeight = 0;
			var position = 0;
			var arr = [];
			
			$area.children().each(function(index, obj) { 
				if($(obj).hasClass('imgselector-handle')) {

					boxWidth = $(obj).innerWidth();
					boxHeight = $(obj).innerHeight();
					position = $(obj).position();
					 
					arr.push({x: ((position.left/areaWidth)*100).toFixed(2),
						y: ((position.top/areaHeight)*100).toFixed(2),
						width: ((boxWidth/areaWidth)*100).toFixed(2),
						height: ((boxHeight/areaHeight)*100).toFixed(2)
					});
				}
			});
		
			return arr;
		}
		
		return this.each(function() {
			

			$area.not('.imgselector-handle :not(.imgselector-handle)').bind('mousedown', function (e) {
				
				var $newSelector = div()
						.addClass('imgselector-handle')
						.css({ left: e.pageX, top: e.pageY});
				$area.append($newSelector);

				handleMouseDown(e, $newSelector, $area);
			});
			
			$area.bind('mouseup', function (e) {
				 
				settings.onSelectEnd(getSelection());
			});
			
		});
	};
}(jQuery));