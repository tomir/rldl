!function($){var t=function(t,e){this.options=$.extend({},SNXtableSets.defaults,e),this.source=$(t),this.rows=this.source.find("tbody tr"),this.all=this.count=this.rows.length,this.random_id="table-"+(new Date).getTime()+"-"+Math.floor(100*Math.random()+1);var s=this.pages=Math.ceil(this.count/this.options.rows);this.render={table:'<div id="'+this.random_id+'"></div>',pages:function(){for(var t=[],e=0;s>e;e++)t.push(e+1);return t},search:this.options.searchableColumns===!1||0==this.options.searchableColumns.length?!1:!0,random:this.random_id},this.init()};t.prototype={init:function(){var t=this;null!=t.options.template&&t.options.template(t.render).done(function(e){t.table=$(e),t.table.find("input.dt-filter").keyup(function(e){t.options.filter=$(this).val(),t.page(t.active)}).keydown(function(t){13==t.which&&t.preventDefault()}),t.table.find(".dt-pages .dt-page a").each(function(e){$(this).click(function(s){s.preventDefault(),t.page(e)})}),t.table.find(".dt-pages .dt-previous a").click(function(e){e.preventDefault(),t.prev()}),t.table.find(".dt-pages .dt-next a").click(function(e){e.preventDefault(),t.next()}),t.source.find("thead th").each(function(e){if(null==t.options.sortableColumns||$.inArray(e,t.options.sortableColumns)>-1){var s=$('<a href="#">');s.addClass("dt-order").click(function(s){s.preventDefault();var i=$(this).hasClass("dt-order-asc")?-1:1;t.order(e,i)}),$(this).wrapInner(s)}}),t.rows.removeClass("dt-hidden").each(function(e){var s=$(this),i=s.find("td"),a=[];i.each(function(e){(null==t.options.searchableColumns||$.inArray(e,t.options.searchableColumns)>-1)&&a.push($(this).text()),s.data("search",a)})}),t.source.before(t.table),t.table.find("#"+t.random_id).replaceWith(t.source),t.options.widthFixed&&t.source.find("thead th").each(function(){$(this).css("width",$(this).width())}),t.active=t.options.start,t.order(t.options.sort[0],t.options.sort[1])})},page:function(t){var e=this;e.filter(),t>=e.pages&&e.pages>0&&(t=e.pages-1),e.active=t;var s=e.table.find(".dt-pages .dt-page:not(.dt-hidden)");if(0==e.active?e.table.find(".dt-pages .dt-previous").addClass("disabled"):e.table.find(".dt-pages .dt-previous").removeClass("disabled"),e.active==e.pages-1?e.table.find(".dt-pages .dt-next").addClass("disabled"):e.table.find(".dt-pages .dt-next").removeClass("disabled"),s.length>e.options.visiblePages){var i=[Math.ceil(e.options.visiblePages/2),Math.floor(e.options.visiblePages/2)],a=0;t>=i[0]&&(a=t>=s.length-i[1]?s.length-e.options.visiblePages:t-i[0]+1),s.css("display","none").slice(a,a+e.options.visiblePages).css("display","")}s.removeClass("active").eq(t).addClass("active"),e.showRows()},next:function(){var t=this;t.active<t.pages-1&&t.page(t.active+1)},prev:function(){var t=this;t.active>0&&t.page(t.active-1)},showRows:function(){var t=this,e=[t.active*t.options.rows];e[1]=e[0]+t.options.rows,e[1]>t.count&&(e[1]=t.count),t.rows.css("display","none").slice(e[0],e[1]).css("display",""),t.table.find(".dt-info").html(t.options.lang(t.all>0?t.info:2,{start:e[0]+1,end:e[1],total:t.count,max:t.all}))},order:function(t,e){var s=this,i=s.source.find("thead a.dt-order");i.removeClass("dt-order-asc dt-order-desc"),i.eq(t).addClass(e>0?"dt-order-asc":"dt-order-desc"),s.source.SNXsort("tbody tr:not(.dt-hidden)",function(s,i){for(var a=[$(s).find("td").eq(t),$(i).find("td").eq(t)],n=0;1>=n;n++)a[n]=void 0!=a[n].data("order")?a[n].data("order"):a[n].text().toLowerCase();return a[0]>a[1]?e:-e}),s.rows=s.source.find("tbody tr:not(.dt-hidden)"),s.page(s.active)},filter:function(t){var e=this,t=e.options.filter,s=e.source.find("tbody tr");if(e.info=0,""==t||void 0==t)s.removeClass("dt-hidden").css("display",""),e.count=s.length;else{t=new RegExp(t,"gi");var i=function(e){for(var s=0;s<e.length;s++)if(e[s].search(t)>-1)return!0;return!1},a=0;s.each(function(){i($(this).data("search"))?($(this).removeClass("dt-hidden").css("display","block"),a++):$(this).addClass("dt-hidden").css("display","none")}),e.count=a,e.info=a>0?1:2}e.pages=0==a?1:Math.ceil(e.count/e.options.rows),e.table.find(".dt-pages .dt-page").removeClass("dt-hidden").css("display","").slice(e.pages).addClass("dt-hidden").css("display","none"),e.rows=e.source.find("tbody tr:not(.dt-hidden)"),e.pages<=1?e.table.find(".dt-pages").hide():e.table.find(".dt-pages").show()}},SNXtableSets={defaults:{template:null,rows:10,visiblePages:5,start:0,widthFixed:!0,sort:[0,1],sortableColumns:null,searchableColumns:!1,filter:"",lang:function(){return null}},set:function(t){this.defaults=$.extend(this.defaults,t)}},$.SNXtable=function(t){return SNXtableSets.set(t)},$.fn.SNXtable=function(e){return this.each(function(){$.data(this,"SNXtable")||$.data(this,"SNXtable",new t(this,e))})},$.fn.SNXsort=function(t,e){var s=this.find(t),i=$('<div id="flag">');return s.eq(0).before(i),i.after(s.sort(e)),i.remove(),this}}(jQuery);