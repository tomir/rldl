// This [jQuery](http://jquery.com/) plugin implements an `<iframe>`
// [transport](http://api.jquery.com/extending-ajax/#Transports) so that
// `$.ajax()` calls support the uploading of files using standard HTML file
// input fields. This is done by switching the exchange from `XMLHttpRequest`
// to a hidden `iframe` element containing a form that is submitted.
// The [source for the plugin](http://github.com/cmlenz/jquery-iframe-transport)
// is available on [Github](http://github.com/) and dual licensed under the MIT
// or GPL Version 2 licenses.
// ## Usage
// To use this plugin, you simply add an `iframe` option with the value `true`
// to the Ajax settings an `$.ajax()` call, and specify the file fields to
// include in the submssion using the `files` option, which can be a selector,
// jQuery object, or a list of DOM elements containing one or more
// `<input type="file">` elements:
//     $("#myform").submit(function() {
//         $.ajax(this.action, {
//             files: $(":file", this),
//             iframe: true
//         }).complete(function(data) {
//             console.log(data);
//         });
//     });
// The plugin will construct hidden `<iframe>` and `<form>` elements, add the
// file field(s) to that form, submit the form, and process the response.
// If you want to include other form fields in the form submission, include
// them in the `data` option, and set the `processData` option to `false`:
//     $("#myform").submit(function() {
//         $.ajax(this.action, {
//             data: $(":text", this).serializeArray(),
//             files: $(":file", this),
//             iframe: true,
//             processData: false
//         }).complete(function(data) {
//             console.log(data);
//         });
//     });
// ### Response Data Types
// As the transport does not have access to the HTTP headers of the server
// response, it is not as simple to make use of the automatic content type
// detection provided by jQuery as with regular XHR. If you can't set the
// expected response data type (for example because it may vary depending on
// the outcome of processing by the server), you will need to employ a
// workaround on the server side: Send back an HTML document containing just a
// `<textarea>` element with a `data-type` attribute that specifies the MIME
// type, and put the actual payload in the textarea:
//     <textarea data-type="application/json">
//       {"ok": true, "message": "Thanks so much"}
//     </textarea>
// The iframe transport plugin will detect this and pass the value of the
// `data-type` attribute on to jQuery as if it was the "Content-Type" response
// header, thereby enabling the same kind of conversions that jQuery applies
// to regular responses. For the example above you should get a Javascript
// object as the `data` parameter of the `complete` callback, with the
// properties `ok: true` and `message: "Thanks so much"`.
// ### Handling Server Errors
// Another problem with using an `iframe` for file uploads is that it is
// impossible for the javascript code to determine the HTTP status code of the
// servers response. Effectively, all of the calls you make will look like they
// are getting successful responses, and thus invoke the `done()` or
// `complete()` callbacks. You can only determine communicate problems using
// the content of the response payload. For example, consider using a JSON
// response such as the following to indicate a problem with an uploaded file:
//     <textarea data-type="application/json">
//       {"ok": false, "message": "Please only upload reasonably sized files."}
//     </textarea>
// ### Compatibility
// This plugin has primarily been tested on Safari 5 (or later), Firefox 4 (or
// later), and Internet Explorer (all the way back to version 6). While I
// haven't found any issues with it so far, I'm fairly sure it still doesn't
// work around all the quirks in all different browsers. But the code is still
// pretty simple overall, so you should be able to fix it and contribute a
// patch :)
// ## Annotated Source
(function(e,t){"use strict";e.ajaxPrefilter(function(e,t,n){if(e.iframe){e.originalURL=e.url;return"iframe"}});e.ajaxTransport("iframe",function(t,n,r){function l(){a.prop("disabled",!1);i.remove();s.one("load",function(){s.remove()});s.attr("src","javascript:false;")}var i=null,s=null,o="iframe-"+e.now(),u=e(t.files).filter(":file:enabled"),a=null,f=null;t.dataTypes.shift();t.data=n.data;if(u.length){i=e("<form enctype='multipart/form-data' method='post'></form>").hide().attr({action:t.originalURL,target:o});typeof t.data=="string"&&t.data.length>0&&e.error("data must not be serialized");e.each(t.data||{},function(t,n){if(e.isPlainObject(n)){t=n.name;n=n.value}e("<input type='hidden' />").attr({name:t,value:n}).appendTo(i)});e("<input type='hidden' value='IFrame' name='X-Requested-With' />").appendTo(i);t.dataTypes[0]&&t.accepts[t.dataTypes[0]]?f=t.accepts[t.dataTypes[0]]+(t.dataTypes[0]!=="*"?", */*; q=0.01":""):f=t.accepts["*"];e("<input type='hidden' name='X-HTTP-Accept'>").attr("value",f).appendTo(i);a=u.after(function(t){return e(this).clone().prop("disabled",!0)}).next();u.appendTo(i);return{send:function(t,n){s=e("<iframe src='javascript:false;' name='"+o+"' id='"+o+"' style='display:none'></iframe>");s.one("load",function(){s.one("load",function(){var e=this.contentWindow?this.contentWindow.document:this.contentDocument?this.contentDocument:this.document,t=e.documentElement?e.documentElement:e.body,r=t.getElementsByTagName("textarea")[0],i=r&&r.getAttribute("data-type")||null,s=r&&r.getAttribute("data-status")||200,o=r&&r.getAttribute("data-statusText")||"OK",u={html:t.innerHTML,text:i?r.value:t?t.textContent||t.innerText:null};l();n(s,o,u,i?"Content-Type: "+i:null)});i[0].submit()});e("body").append(i,s)},abort:function(){if(s!==null){s.unbind("load").attr("src","javascript:false;");l()}}}}})})(jQuery);