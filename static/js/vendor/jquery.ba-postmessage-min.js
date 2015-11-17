/*!
 * jQuery postMessage - v0.5 - 9/11/2009
 * http://benalman.com/projects/jquery-postmessage-plugin/
 * 
 * Copyright (c) 2009 "Cowboy" Ben Alman
 * Dual licensed under the MIT and GPL licenses.
 * http://benalman.com/about/license/
 */// Script: jQuery postMessage: Cross-domain scripting goodness
//
// *Version: 0.5, Last updated: 9/11/2009*
// 
// Project Home - http://benalman.com/projects/jquery-postmessage-plugin/
// GitHub       - http://github.com/cowboy/jquery-postmessage/
// Source       - http://github.com/cowboy/jquery-postmessage/raw/master/jquery.ba-postmessage.js
// (Minified)   - http://github.com/cowboy/jquery-postmessage/raw/master/jquery.ba-postmessage.min.js (0.9kb)
// 
// About: License
// 
// Copyright (c) 2009 "Cowboy" Ben Alman,
// Dual licensed under the MIT and GPL licenses.
// http://benalman.com/about/license/
// 
// About: Examples
// 
// This working example, complete with fully commented code, illustrates one
// way in which this plugin can be used.
// 
// Iframe resizing - http://benalman.com/code/projects/jquery-postmessage/examples/iframe/
// 
// About: Support and Testing
// 
// Information about what version or versions of jQuery this plugin has been
// tested with and what browsers it has been tested in.
// 
// jQuery Versions - 1.3.2
// Browsers Tested - Internet Explorer 6-8, Firefox 3, Safari 3-4, Chrome, Opera 9.
// 
// About: Release History
// 
// 0.5 - (9/11/2009) Improved cache-busting
// 0.4 - (8/25/2009) Initial release
(function(e){"$:nomunge";var t,n,r=1,i,s=this,o=!1,u="postMessage",a="addEventListener",f,l=typeof s.postMessage!=undefined;e[u]=function(t,n,i){if(!n)return;t=typeof t=="string"?t:e.param(t);i=i||parent;l?i[u](t,n.replace(/([^:]+:\/\/[^\/]+).*/,"$1")):n&&(i.location=n.replace(/#.*$/,"")+"#"+ +(new Date)+r++ +"&"+t)};e.receiveMessage=f=function(r,u,c){if(l){if(r){i&&f();i=function(t){if(typeof u=="string"&&t.origin!==u||e.isFunction(u)&&u(t.origin)===o)return o;r(t)}}s[a]?s[r?a:"removeEventListener"]("message",i,o):s[r?"attachEvent":"detachEvent"]("onmessage",i)}else{t&&clearInterval(t);t=null;if(r){c=typeof u=="number"?u:typeof c=="number"?c:100;t=setInterval(function(){var e=document.location.hash,t=/^#?\d+&/;if(e!==n&&t.test(e)){n=e;r({data:e.replace(t,"")})}},c)}}}})(jQuery);