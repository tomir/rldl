/**
 * jQuery Longclick Event
 * ======================
 * Press & hold mouse button "long click" special event for jQuery 1.4.x
 *
 * @license Longclick Event
 * Copyright (c) 2010 Petr Vostrel (http://petr.vostrel.cz/)
 * Dual licensed under the MIT (MIT-LICENSE.txt)
 * and GPL (GPL-LICENSE.txt) licenses.
 *
 * Version: 0.3.2
 * Updated: 2010-06-22
 */
!function($){function t(t){return $.each("touchstart touchmove touchend touchcancel".split(/ /),function n(i,e){t.addEventListener(e,function o(n){$(t).trigger(e)},!1)}),$(t)}function n(t){function n(){$(i).data(b,!0),t.type=c,jQuery.event.dispatch.apply(i,e)}if(!$(this).data(k)){var i=this,e=arguments;$(this).data(b,!1).data(k,setTimeout(n,$(this).data(v)||$.longclick.duration))}}function i(t){$(this).data(k,clearTimeout($(this).data(k))||null)}function e(t){return $(this).data(b)?t.stopImmediatePropagation()||!1:void 0}var o=$.fn.click;$.fn.click=function y(t,n){return n?$(this).data(v,t||null).bind(c,n):o.apply(this,arguments)},$.fn.longclick=function j(){var t=[].splice.call(arguments,0),n=t.pop(),i=t.pop(),e=$(this).data(v,i||null);return n?e.click(i,n):e.trigger(c)},$.longclick={duration:500},$.event.special.longclick={setup:function(o,c){/iphone|ipad|ipod/i.test(navigator.userAgent)?t(this).bind(p,n).bind([f,m,g].join(" "),i).bind(r,e).css({WebkitUserSelect:"none"}):$(this).bind(u,n).bind([s,d,l,h].join(" "),i).bind(r,e)},teardown:function(t){$(this).unbind(a)}};var c="longclick",a="."+c,u="mousedown"+a,r="click"+a,s="mousemove"+a,d="mouseup"+a,l="mouseout"+a,h="contextmenu"+a,p="touchstart"+a,f="touchend"+a,m="touchmove"+a,g="touchcancel"+a,v="duration"+a,k="timer"+a,b="fired"+a}(jQuery);