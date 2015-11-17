;(function ($) {
	var SNXtable = function(el,options){
		this.options = $.extend({}, SNXtableSets.defaults, options);
		this.source = $(el);
		this.rows = this.source.find('tbody tr');
		this.all = this.count = this.rows.length;
		this.random_id = 'table-'+(new Date()).getTime()+'-'+Math.floor((Math.random() * 100) + 1);
		var pages = this.pages = Math.ceil(this.count/this.options.rows);
		this.render = {
			table: '<div id="'+this.random_id+'"></div>',
			pages: function(){
				var p = [];
				for (var i = 0; i < pages; i++) {
					p.push((i+1));
				}
				return p;
			},
			search: (this.options.searchableColumns===false || this.options.searchableColumns.length==0) ? false : true,
			random: this.random_id
		};
		this.init();
	}
	SNXtable.prototype = {
		init: function(){
			var $this=this;
			if ($this.options.template!=null) {
				$this.options.template($this.render).done(function(template){
					$this.table = $(template);
					$this.table.find('input.dt-filter').keyup(function(e) {
						$this.options.filter = $(this).val();
						$this.page($this.active);
					}).keydown(function(e) {
						if (e.which==13) {
							e.preventDefault();
						}
					});
					$this.table.find('.dt-pages .dt-page a').each(function(i){
						$(this).click(function(e){
							e.preventDefault();
							$this.page(i);
						});
					});
					$this.table.find('.dt-pages .dt-previous a').click(function(e){
						e.preventDefault();
						$this.prev();
					});
					$this.table.find('.dt-pages .dt-next a').click(function(e){
						e.preventDefault();
						$this.next();
					});
					$this.source.find('thead th').each(function(i){
						if ($this.options.sortableColumns==null || $.inArray(i, $this.options.sortableColumns)>-1) {
							var a = $('<a href="#">');
							a.addClass('dt-order').click(function(e){
								e.preventDefault();
								var order = $(this).hasClass('dt-order-asc') ? -1 : 1;
								$this.order(i,order);
							});
							$(this).wrapInner(a);
						}
					});
					$this.rows.removeClass('dt-hidden').each(function(y){
						var row = $(this);
						var columns = row.find('td');
						var search = [];
						columns.each(function(x){
							if ($this.options.searchableColumns==null || $.inArray(x, $this.options.searchableColumns)>-1) {
								search.push($(this).text());
							}
							row.data('search', search);
						});
					});
					
					$this.source.before($this.table);
					$this.table.find('#'+$this.random_id).replaceWith($this.source);
					if ($this.options.widthFixed) {
						$this.source.find('thead th').each(function(){
							$(this).css('width',$(this).width());
						});
					}
					$this.active=$this.options.start;
					$this.order($this.options.sort[0], $this.options.sort[1]);
				});
			}
		},
		page: function(i){
			var $this=this;
			$this.filter();
			if (i>=$this.pages && $this.pages>0) {
				i=$this.pages-1;
			}
			$this.active=i;
			var pages=$this.table.find('.dt-pages .dt-page:not(.dt-hidden)');
			if ($this.active==0) {
				$this.table.find('.dt-pages .dt-previous').addClass('disabled');
			}
			else {
				$this.table.find('.dt-pages .dt-previous').removeClass('disabled');
			}
			if ($this.active==($this.pages-1)) {
				$this.table.find('.dt-pages .dt-next').addClass('disabled');
			}
			else {
				$this.table.find('.dt-pages .dt-next').removeClass('disabled');
			}
			if (pages.length>$this.options.visiblePages) {
				var center = [
					Math.ceil($this.options.visiblePages/2),
					Math.floor($this.options.visiblePages/2)
				];
				var start = 0
				if (i>=center[0]) {
					if (i>=pages.length-center[1]) {
						start = pages.length-$this.options.visiblePages;
					}
					else {
						start = i-center[0]+1;
					}
				}
				pages.css('display','none').slice(start, start+$this.options.visiblePages).css('display','');
			}
			pages.removeClass('active').eq(i).addClass('active');
			$this.showRows();
		},
		next: function(){
			var $this=this;
			if ($this.active<($this.pages-1)) {
				$this.page($this.active+1);
			}
		},
		prev: function(){
			var $this=this;
			if ($this.active>0) {
				$this.page($this.active-1);
			}
		},
		showRows: function(){
			var $this=this;
			var info=[$this.active*$this.options.rows];
			info[1] = info[0]+$this.options.rows;
			if (info[1]>$this.count) {
				info[1]=$this.count
			}
			
			$this.rows.css('display','none').slice(info[0], info[1]).css('display','');
			$this.table.find('.dt-info').html($this.options.lang(($this.all>0) ? $this.info : 2,{
				start: info[0]+1,
				end: info[1],
				total: $this.count,
				max: $this.all
			}));
		},
		order: function(column, order){
			var $this=this;
			var headers = $this.source.find('thead a.dt-order');
			headers.removeClass('dt-order-asc dt-order-desc');
			if (order>0) headers.eq(column).addClass('dt-order-asc');
			else headers.eq(column).addClass('dt-order-desc')
			$this.source.SNXsort('tbody tr:not(.dt-hidden)',function(a,b){
				var e=[$(a).find('td').eq(column),$(b).find('td').eq(column)];
				for (var i = 0; i <= 1; i++) {
					if (e[i].data('order')!=undefined) {
						e[i] = e[i].data('order');
					}
					else {
						e[i] = e[i].text().toLowerCase();
					}
				}
				return e[0] > e[1] ? order : -order;
			});
			$this.rows = $this.source.find('tbody tr:not(.dt-hidden)');
			
			$this.page($this.active);
		},
		filter: function(str){
			var $this=this;
			var str=$this.options.filter;
			var rows = $this.source.find('tbody tr');
			$this.info = 0;
			if (str=='' || str==undefined) {
				rows.removeClass('dt-hidden').css('display','');
				$this.count=rows.length;
			}
			else {
				str = new RegExp(str, "gi");
				var filter = function(columns){
					for (var i = 0; i < columns.length; i++) {
						if (columns[i].search(str)>-1) {
							return true;
						}
					}
					return false
				}
				var count=0
				rows.each(function(){
					if (filter($(this).data('search'))) {
						$(this).removeClass('dt-hidden').css('display','block');
						count++;
					}
					else {
						$(this).addClass('dt-hidden').css('display','none');
					}
				});
				$this.count=count;
				if (count>0) {
					$this.info=1;
				}
				else {
					$this.info=2
				}
			}
			$this.pages = (count==0 ) ? 1 : Math.ceil($this.count/$this.options.rows);
			$this.table.find('.dt-pages .dt-page').removeClass('dt-hidden').css('display','').slice($this.pages).addClass('dt-hidden').css('display','none');
			$this.rows = $this.source.find('tbody tr:not(.dt-hidden)');
			
			if ($this.pages<=1) {
				$this.table.find('.dt-pages').hide();
			}
			else {
				$this.table.find('.dt-pages').show();
			}
		}
	};	
	// function to sets defaults
	SNXtableSets = {
		defaults: {
			template: null,			//template function function(params), return promise wit rendered template
			rows: 10,				//rows per page
			visiblePages: 5,		//pages in pagination
			start: 0,				//start page
			widthFixed: true,		//column width fixed as in souce table
			sort: [0,1],			//default sorting [row, order]
			sortableColumns: null,	//array of: ids of colums; if null all true
			searchableColumns: false,//array of: ids of colums; if null all true
			filter: '',
			lang: function(){ return null }//text for info line - lang(lang_id, params)
		},
		set: function(options){
			this.defaults = $.extend(this.defaults, options);
		}
	};
	$.SNXtable = function(options) {
		return SNXtableSets.set(options);
	}
	$.fn.SNXtable = function(options) {
		return this.each(function(){
			if (!$.data(this, 'SNXtable')) {
				$.data(this, 'SNXtable', new SNXtable(this, options));
			}
		});
	};
	
	$.fn.SNXsort = function(selector, sort){
	 	var elements=this.find(selector);
	 	var flag=$('<div id="flag">');
	 	elements.eq(0).before(flag);
	 	flag.after(elements.sort(sort));
	 	flag.remove()
	 	return this;
	};

})(jQuery);
