jQuery(function($){
	//鍒涘缓DOM
	var 
	quickHTML = document.querySelector("div.quick_link_mian"),
	quickShell = $(document.createElement('div')).html(quickHTML).addClass('quick_links_wrap'),
	quickLinks = quickShell.find('.quick_links');
	quickPanel = quickLinks.next();
	quickShell.appendTo('.mui-mbar-tabs');
	
	//鍏蜂綋鏁版嵁鎿嶄綔 
	var 
	quickPopXHR,
	loadingTmpl = '<div class="loading" style="padding:30px 80px"><i></i><span>Loading...</span></div>',
	popTmpl = '<a href="javascript:;" class="ibar_closebtn" title="鍏抽棴"></a><div class="ibar_plugin_title"><h3><%=title%></h3></div><div class="pop_panel"><%=content%></div><div class="arrow"><i></i></div><div class="fix_bg"></div>',
	historyListTmpl = '<ul><%for(var i=0,len=items.length; i<5&&i<len; i++){%><li><a href="<%=items[i].productUrl%>" target="_blank" class="pic"><img alt="<%=items[i].productName%>" src="<%=items[i].productImage%>" width="60" height="60"/></a><a href="<%=items[i].productUrl%>" title="<%=items[i].productName%>" target="_blank" class="tit"><%=items[i].productName%></a><div class="price" title="鍗曚环"><em>&yen;<%=items[i].productPrice%></em></div></li><%}%></ul>',
	newMsgTmpl = '<ul><li><a href="#"><span class="tips">鏂板洖澶�<em class="num"><b><%=items.commentNewReply%></b></em></span>鍟嗗搧璇勪环/鏅掑崟</a></li><li><a href="#"><span class="tips">鏂板洖澶�<em class="num"><b><%=items.consultNewReply%></b></em></span>鍟嗗搧鍜ㄨ</a></li><li><a href="#"><span class="tips">鏂板洖澶�<em class="num"><b><%=items.messageNewReply%></b></em></span>鎴戠殑鐣欒█</a></li><li><a href="#"><span class="tips">鏂伴€氱煡<em class="num"><b><%=items.arrivalNewNotice%></b></em></span>鍒拌揣閫氱煡</a></li><li><a href="#"><span class="tips">鏂伴€氱煡<em class="num"><b><%=items.reduceNewNotice%></b></em></span>闄嶄环鎻愰啋</a></li></ul>',
	quickPop = quickShell.find('#quick_links_pop'),
	quickDataFns = {
		//璐墿淇℃伅
		message_list: {
			title: '璐墿杞�',
			content: '<div class="ibar_plugin_content"><div class="ibar_cart_group ibar_cart_product"><div class="ibar_cart_group_header"><span class="ibar_cart_group_title">妯℃澘鍫�</span><a href="#">鎴戠殑璐墿杞�</a></div><ul><li class="cart_item"><div class="cart_item_pic"><a href="#"><img src="images/xiez.jpg" /></a></div><div class="cart_item_desc"><a href="#" class="cart_item_name">澶忓閫忔皵鐪熺毊璞嗚眴闉嬪弽缁掔敺澹紤闂查瀷闊╃増纾ㄧ爞椹捐溅闉嬭嫳浼﹁埞闉嬬敺闉嬪瓙</a><div class="cart_item_sku"><span>灏虹爜锛�38鐮侊紙绮惧伐闄愰噺鐗堬級</span></div><div class="cart_item_price"><span class="cart_price">锟�700.00</span></div></div>	</li></ul></div><div class="cart_handler"><div class="cart_handler_header"><span class="cart_handler_left">鍏�<span class="cart_price">1</span>浠跺晢鍝�</span><span class="cart_handler_right">锟�569.00</span></div><a href="#" class="cart_go_btn" target="_blank">鍘昏喘鐗╄溅缁撶畻</a></div></div>',
			init:$.noop
		},
		
		//鎴戠殑璧勪骇
		history_list: {
			title: '鎴戠殑璧勪骇',
			content: '<div class="ibar_plugin_content"><div class="ia-head-list"><a href="#" target="_blank" class="pl"><div class="num">0</div><div class="text">浼樻儬鍒�</div></a><a href="#" target="_blank" class="pl"><div class="num">0</div><div class="text">绾㈠寘</div></a><a href="#" target="_blank" class="pl money"><div class="num">锟�0</div><div class="text">浣欓</div></a></div><div class="ga-expiredsoon"><div class="es-head">鍗冲皢杩囨湡浼樻儬鍒�</div><div class="ia-none">鎮ㄨ繕娌℃湁鍙敤鐨勭幇閲戝埜鍝︼紒</div></div><div class="ga-expiredsoon"><div class="es-head">鍗冲皢杩囨湡绾㈠寘</div><div class="ia-none">鎮ㄨ繕娌℃湁鍙敤鐨勭孩鍖呭摝锛�</div></div></div>			',
			init: $.noop
		},
		//缁欏鏈嶇暀瑷€
		leave_message: {
			title: '鎴戝叧娉ㄧ殑浜у搧',
			content: $("#ibar_gzcp").html(),
			init:$.noop
		},
		mpbtn_histroy:{
			title: '鎴戠殑瓒宠抗',
			content:'<div class="ibar_plugin_content"><div class="ibar-history-head">鍏�3浠朵骇鍝�<a href="#">娓呯┖</a></div><div class="ibar-moudle-product"><div class="imp_item"><a href="#" class="pic"><img src="images/xiez.jpg" width="100" height="100" /></a><p class="tit"><a href="#">澶忓閫忔皵鐪熺毊璞嗚眴闉嬪弽缁掔敺澹紤闂查瀷闊�</a></p><p class="price"><em>锟�</em>649.00</p><a href="#" class="imp-addCart">鍔犲叆璐墿杞�</a></div><div class="imp_item"><a href="#" class="pic"><img src="images/xiez.jpg" width="100" height="100" /></a><p class="tit"><a href="#">澶忓閫忔皵鐪熺毊璞嗚眴闉嬪弽缁掔敺澹紤闂查瀷闊�</a></p><p class="price"><em>锟�</em>649.00</p><a href="#" class="imp-addCart">鍔犲叆璐墿杞�</a></div><div class="imp_item"><a href="#" class="pic"><img src="images/xiez.jpg" width="100" height="100" /></a><p class="tit"><a href="#">澶忓閫忔皵鐪熺毊璞嗚眴闉嬪弽缁掔敺澹紤闂查瀷闊�</a></p><p class="price"><em>锟�</em>649.00</p><a href="#" class="imp-addCart">鍔犲叆璐墿杞�</a></div></div></div>',
			init: $.noop
		},
		mpbtn_wdsc:{
			title: '鏀惰棌鐨勪骇鍝�',
			content: '<div class="ibar_plugin_content"><div class="ibar_cart_group ibar_cart_product"><ul><li class="cart_item"><div class="cart_item_pic"><a href="#"><img src="images/xiez.jpg" /></a></div><div class="cart_item_desc"><a href="#" class="cart_item_name">澶忓閫忔皵鐪熺毊璞嗚眴闉嬪弽缁掔敺澹紤闂查瀷闊╃増纾ㄧ爞椹捐溅闉嬭嫳浼﹁埞闉嬬敺闉嬪瓙</a><div class="cart_item_sku"><span>灏虹爜锛�38鐮侊紙绮惧伐闄愰噺鐗堬級</span></div><div class="cart_item_price"><span class="cart_price">锟�700.00</span><a href="#" class="sc" title="鍒犻櫎"><img src="images/sc.png" alt="鍒犻櫎" /></a></div></div>	</li></ul></div><div class="cart_handler"><a href="#" class="cart_go_btn jiaru" target="_blank">鍔犲叆璐墿杞�</a></div></div>',
			init: $.noop
		},
		mpbtn_recharge:{
			title: '鎵嬫満鍏呭€�',
			content: '<div class="ibar_plugin_content"><form target="_blank" class="ibar_recharge_form"><div class="ibar_recharge-field"><label>鍙风爜</label><div class="ibar_recharge-fl"><div class="ibar_recharge-iwrapper"><input type="text" name="19" placeholder="鎵嬫満鍙风爜" /></div><i class="ibar_recharge-contact"></i></div></div><div class="ibar_recharge-field"><label>闈㈠€�</label><div class="ibar_recharge-fl"><p class="ibar_recharge-mod"><span class="ibar_recharge-val">100</span>鍏�</p><i class="ibar_recharge-arrow"></i><div class="ibar_recharge-vbox"><ul style="display:none;"><li><span>10</span>鍏�</li><li class="sanwe selected"><span>100</span>鍏�</li><li><span>20</span>鍏�</li><li class="sanwe"><span>200</span>鍏�</li><li><span>30</span>鍏�</li><li class="sanwe"><span>300</span>鍏�</li><li><span>50</span>鍏�</li><li class="sanwe"><span>500</span>鍏�</li></ul></div></div></div><div class="ibar_recharge-btn"><input type="submit" value="绔嬪嵆鍏呭€�" /></div></form></div>',
			init: $.noop
		}
	};
	
	//showQuickPop
	var 
	prevPopType,
	prevTrigger,
	doc = $(document),
	popDisplayed = false,
	hideQuickPop = function(){
		if(prevTrigger){
			prevTrigger.removeClass('current');
		}
		popDisplayed = false;
		prevPopType = '';
		quickPop.hide();
		quickPop.animate({left:280,queue:true});
	},
	showQuickPop = function(type){
		if(quickPopXHR && quickPopXHR.abort){
			quickPopXHR.abort();
		}
		if(type !== prevPopType){
			var fn = quickDataFns[type];
			quickPop.html(ds.tmpl(popTmpl, fn));
			fn.init.call(this, fn);
		}
		doc.unbind('click.quick_links').one('click.quick_links', hideQuickPop);

		quickPop[0].className = 'quick_links_pop quick_' + type;
		popDisplayed = true;
		prevPopType = type;
		quickPop.show();
		quickPop.animate({left:0,queue:true});
	};
	quickShell.bind('click.quick_links', function(e){
		e.stopPropagation();
	});
	quickPop.delegate('a.ibar_closebtn','click',function(){
		quickPop.hide();
		quickPop.animate({left:280,queue:true});
		if(prevTrigger){
			prevTrigger.removeClass('current');
		}
	});

	//閫氱敤浜嬩欢澶勭悊
	var 
	view = $(window),
	quickLinkCollapsed = !!ds.getCookie('ql_collapse'),
	getHandlerType = function(className){
		return className.replace(/current/g, '').replace(/\s+/, '');
	},
	showPopFn = function(){
		var type = getHandlerType(this.className);
		if(popDisplayed && type === prevPopType){
			return hideQuickPop();
		}
		showQuickPop(this.className);
		if(prevTrigger){
			prevTrigger.removeClass('current');
		}
		prevTrigger = $(this).addClass('current');
	},
	quickHandlers = {
		//璐墿杞︼紝鏈€杩戞祻瑙堬紝鍟嗗搧鍜ㄨ
		my_qlinks: showPopFn,
		message_list: showPopFn,
		history_list: showPopFn,
		leave_message: showPopFn,
		mpbtn_histroy:showPopFn,
		mpbtn_recharge:showPopFn,
		mpbtn_wdsc:showPopFn,
		//杩斿洖椤堕儴
		return_top: function(){
			ds.scrollTo(0, 0);
			hideReturnTop();
		}
	};
	quickShell.delegate('a', 'click', function(e){
		var type = getHandlerType(this.className);
		if(type && quickHandlers[type]){
			quickHandlers[type].call(this);
			e.preventDefault();
		}
	});
	
	//Return top
	var scrollTimer, resizeTimer, minWidth = 1350;

	function resizeHandler(){
		clearTimeout(scrollTimer);
		scrollTimer = setTimeout(checkScroll, 160);
	}
	
	function checkResize(){
		quickShell[view.width() > 1340 ? 'removeClass' : 'addClass']('quick_links_dockright');
	}
	function scrollHandler(){
		clearTimeout(resizeTimer);
		resizeTimer = setTimeout(checkResize, 160);
	}
	function checkScroll(){
		view.scrollTop()>100 ? showReturnTop() : hideReturnTop();
	}
	function showReturnTop(){
		quickPanel.addClass('quick_links_allow_gotop');
	}
	function hideReturnTop(){
		quickPanel.removeClass('quick_links_allow_gotop');
	}
	view.bind('scroll.go_top', resizeHandler).bind('resize.quick_links', scrollHandler);
	quickLinkCollapsed && quickShell.addClass('quick_links_min');
	resizeHandler();
	scrollHandler();
});