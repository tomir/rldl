var api=new RLDL.Api,tl=new RLDL.Template,ll=new RLDL.I18n;ll.load("privacy");var $RD={};$RD.isMobile=!1,$RD.loader={counter:0,start:function(){0===this.counter&&$("body").addClass("loading"),this.counter++},end:function(o){this.counter--,(0===this.counter||o)&&(this.counter=0,$("body").removeClass("loading"))}},$RD.reloadContent=function(){$RD.hideModal(),$RD.loader.start(),$RD.checkLogin().done(function(o){$("#body").empty().append(tl.render("body",o)),$("#terms a").html(ll._("App terms and privacy policy")),1==o.user.login&&$RD.loadFollows(),$("#top").empty().append(tl.render("top",o)),$("title").html(ll._("User account and privacy")),$RD.actionFix(),$RD.loader.end()})},$RD.actionFix=function(){$("a:not(.data-action)[data-action]").addClass("data-action").click(function(o){o.preventDefault();var e=$(this).data("action").split("/");switch(e[0]){case"login":$RD.doLogin();break;case"logout":$RD.doLogout();break;case"loginWindow":if($RD.isMobile)window.location.href=$(this).attr("href");else{var t=window.open($(this).attr("href")),l=0,a=function(){t&&t.closed?($RD.reloadContent(),l=0):(l++,l>=10?(l=0,api.get("auth").done(function(o){1==o.user?$RD.reloadContent():setTimeout(a,1e3)})):setTimeout(a,1e3))};a()}break;case"unfollow":2==e.length&&$RD.showModal({msg:"Are you sure you want to unfollow?",buttons:[{title:"Yes",action:"unfollowConfirmed/"+e[1]},{title:"No",action:"hideModal"}]});break;case"unfollowConfirmed":$RD.hideModal(),2==e.length&&($RD.loader.start(),api["delete"]("campaign/"+e[1]+"/follow").done(function(){$("#follow-"+e[1]).remove(),0==$("#follows ul li").length&&$("#follows").empty(),$RD.loader.end()}).fail(function(o){401==o.code&&$RD.doLogin(),$RD.loader.end()}));break;case"delete":$RD.showModal({msg:"Are you sure you want to delete your acount?",buttons:[{title:"Yes",action:"deleteConfirmed"},{title:"No",action:"hideModal"}]});break;case"deleteConfirmed":$RD.doDelete();break;case"hideModal":$RD.hideModal()}})},$RD.checkLogin=function(){var o=$.Deferred();return api.get("auth").done(function(e){1==e.user?api.get("me").done(function(t){RLDL._config.set({locale:t.locale}),e.user=t,e.user.login=!0,ll.load("privacy").done(function(){o.resolve(e)})}).fail(function(){o.resolve(e)}):api.get("auth/user").done(function(t){e.user={},t.probability>.5?(RLDL._config.set({locale:t.locale}),e.user=t,e.user.login=!1):(t.hasOwnProperty("locale")&&RLDL._config.set({locale:t.locale}),e.user.login=!1),ll.load("privacy").done(function(){o.resolve(e)})}).fail(function(){o.resolve(e)})}).fail(function(){o.resolve()}),o},$RD.doLogout=function(){$RD.loader.start(),api["delete"]("auth").always(function(){$RD.reloadContent(),$RD.loader.end()})},$RD.doDelete=function(){$RD.loader.start(),api["delete"]("me").always(function(){$RD.reloadContent(),$RD.showModal({msg:"Your account has been deleted.",buttons:[{title:"Close",action:"hideModal"}]}),$RD.loader.end()})},$RD.doLogin=function(){$RD.loader.start(),api.get("auth/methods").done(function(o){var e={buttons:[],msg:"Login using your favorite platform."};$.each(o.data,function(t){e.buttons.push({title:o.data[t].name,action:"loginWindow",url:o.data[t].url+"?redirect="+($RD.isMobile?encodeURI(window.location.href):"close")})}),$RD.showModal(e),$RD.loader.end()})},$RD.showModal=function(o){$("#modal").empty().append(tl.render("modal",o)),$RD.actionFix(),$("body").addClass("modal")},$RD.hideModal=function(){$("body").removeClass("modal"),$("#modal").empty()},$RD.loadFollows=function(){$RD.loader.start();var o=$.Deferred(),o=$RD.loadFollows.loader("me/follows?limit=100");o.done(function(o){"null"!=typeof o&&($("#follows").empty().append(tl.render("follows",{campaigns:o})),0==$("#follows ul li").length?$("#follows").empty():$RD.actionFix()),$RD.loader.end()})},$RD.loadFollows.loader=function(o){var e=$.Deferred();return api.get(o).done(function(o){o.hasOwnProperty("paging")?$RD.loadFollows.loader(o.paging.next).done(function(t){e.resolve($.merge(o.data,"null"!=typeof t?t:[]))}):e.resolve(o.data)}).fail(function(){e.resolve(null)}),e},$(function(){$RD.loader.start(),$RD.isMobile=$("body").hasClass("mobile")}),$.when(tl.setTemplateFile("privacy","privacy/layout"),tl.setTemplateFile("top","privacy/top"),tl.setTemplateFile("modal","privacy/modal"),tl.setTemplateFile("body","privacy/body"),tl.setTemplateFile("follows","privacy/follows")).done(function(){$(function(){$RD.reloadContent(),$RD.loader.end()})});