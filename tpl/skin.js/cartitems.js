(function($) {
	jQuery(function($) {
			// declared in cartitems.html
			$('#deleteCart').click(function() {
					var cart_srls = new Array();
					$('input[name=cart]:checked').each(function() {
							cart_srls[cart_srls.length] = $(this).val();
					});
		if (!cart_srls.length) {
			alert(xe.lang.msg_select_items_in_order_to_delete);
			return false;
		}
					var params = new Array();
					params['cart_srls'] = cart_srls.join(',');
// Google Analytics Code Begin (20151121) singleview.co.kr
					gaectkCart.removeSelected( cart_srls );
// Google Analytics Code End (20151121) singleview.co.kr
					var responses = ['error','message'];
					exec_xml('svcart', 'procSvcartDeleteCart', params, completeDeleteCart, responses);
			});
	});
})(jQuery);

/**
 * npay order
 */
function onClickNaverpayBuy( sUrl ) 
{
	if (confirm('네이버페이로 이동하시겠습니까?')) 
	{
		var cartnos = makeList();
		if (!cartnos.length)
			return;
		if (cartnos.length < g_total_items)
		{
			if (!confirm('선택하신 ' + cartnos.length + '개 상품만 주문합니다.')) 
				return;
			else
			{
				current_url = current_url.setQuery('document_srl', '');
				location.href = current_url.setQuery('mid', g_sSvcartMid).setQuery('act','dispSvcartNpayBuy').setQuery('cartnos',cartnos);
			}
		}
		else
		{
			current_url = current_url.setQuery('document_srl', '');
			location.href = current_url.setQuery('mid', g_sSvcartMid).setQuery('act','dispSvcartNpayBuy');
		}
	}
}
/**
 * npay wish handler; wishlist_nc 이외의 명칭은 인식하지 않음
 * 장바구니 화면에서는 찜 버튼을 숨기기 때문에 작동시키지 않음
 */
function wishlist_nc( sUrl ) 
{
	return false;
}

function progressOrderIndividual(sSvorderMid, cartno, login_chk) 
{
	// Google Analytics Code Begin (20210728) singleview.co.kr
	gaectkCart.checkoutSelected(cartno);
	// Google Analytics Code End (20210728) singleview.co.kr
	if(login_chk == "Y") 
		location.href = current_url.setQuery('mid', sSvorderMid).setQuery('act','dispSvorderOrderForm').setQuery('cartnos',cartno);
	else 
		location.href = current_url.setQuery('mid', sSvorderMid).setQuery('act','dispSvcartLogin').setQuery('cartnos',cartno);
}

function progressOrderItems(login_chk, sSvorderMid) 
{
	var cartnos = makeList();
	if (!cartnos.length)
		return;

	if( !sSvorderMid.length )
	{
		alert('svorder 모듈을 연결하세요!');
		return;
	}

// Google Analytics Code Begin (20151121) singleview.co.kr
	gaectkCart.checkoutSelected( cartnos );
// Google Analytics Code End (20151121) singleview.co.kr

	if (cartnos.length < g_total_items)
		if (!confirm('선택하신 ' + cartnos.length + '개 상품만 주문합니다.')) return;

	if(login_chk == "Y") 
		location.href = current_url.setQuery('mid', sSvorderMid).setQuery('act','dispSvorderOrderForm').setQuery('cartnos',cartnos);
	else 
		location.href = current_url.setQuery('mid', sSvorderMid).setQuery('act','dispSvorderLogin').setQuery('cartnos',cartnos);
}

/*
 * callback function of procNstoreDeleteCart.
 */
function completeDeleteCart(ret_obj) 
{
	alert(ret_obj['message']);
	location.href = current_url;
}

function deleteCartItem(cart_srl) 
{
	var cart_srls = new Array();
	cart_srls[cart_srls.length] = cart_srl;
// Google Analytics Code Begin (20151121) singleview.co.kr
	gaectkCart.removeSelected( cart_srls );
// Google Analytics Code End (20151121) singleview.co.kr
	var params = new Array();
	params['cart_srls'] = cart_srls.join(',');
	var responses = ['error','message'];
	exec_xml('svcart', 'procSvcartDeleteCart', params, completeDeleteCart, responses);
}
