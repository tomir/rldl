!function($){$.fn.stickyScroll=function(t){var o={init:function(t){function o(){return $(document).height()-n.container.offset().top-n.container.attr("offsetHeight")}function e(){return n.container.offset().top}function s(t){return $(t).attr("offsetHeight")}var n;return"auto"!==t.mode&&"manual"!==t.mode&&(t.container&&(t.mode="auto"),t.bottomBoundary&&(t.mode="manual")),n=$.extend({mode:"auto",container:$("body"),topBoundary:null,bottomBoundary:null},t),n.container=$(n.container),n.container.length?("auto"===n.mode&&(n.topBoundary=e(),n.bottomBoundary=o()),this.each(function(t){var i=$(this),c=$(window),a=Date.now()+t,r=s(i);i.data("sticky-id",a),c.bind("scroll.stickyscroll-"+a,function(){var t=$(document).scrollTop(),o=$(document).height()-t-r;o<=n.bottomBoundary?i.offset({top:$(document).height()-n.bottomBoundary-r}).removeClass("sticky-active").removeClass("sticky-inactive").addClass("sticky-stopped"):t>n.topBoundary?i.offset({top:$(window).scrollTop()}).removeClass("sticky-stopped").removeClass("sticky-inactive").addClass("sticky-active"):t<n.topBoundary&&i.css({position:"",top:"",bottom:""}).removeClass("sticky-stopped").removeClass("sticky-active").addClass("sticky-inactive")}),c.bind("resize.stickyscroll-"+a,function(){"auto"===n.mode&&(n.topBoundary=e(),n.bottomBoundary=o()),r=s(i),$(this).scroll()}),i.addClass("sticky-processed"),c.scroll()})):void(console&&console.log("StickyScroll: the element "+t.container+" does not exist, we're throwing in the towel"))},reset:function(){return this.each(function(){var t=$(this),o=t.data("sticky-id");t.css({position:"",top:"",bottom:""}).removeClass("sticky-stopped").removeClass("sticky-active").removeClass("sticky-inactive").removeClass("sticky-processed"),$(window).unbind(".stickyscroll-"+o)})}};return o[t]?o[t].apply(this,Array.prototype.slice.call(arguments,1)):"object"!=typeof t&&t?void(console&&console.log("Method"+t+" does not exist on jQuery.stickyScroll")):o.init.apply(this,arguments)}}(jQuery);