<?php
/**
 * @class  svcartView
 * @author singleview(root@singleview.co.kr)
 * @brief  svcartView
 */
class svcartView extends svcart
{
/**
 * @brief 
 **/
	public function init()
	{
		if(!$this->module_info->skin) 
			$this->module_info->skin = 'default';
		$skin = $this->module_info->skin;
		// 템플릿 경로 설정
		$this->setTemplatePath(sprintf('%sskins/%s', $this->module_path, $skin));

		$oLoggedInfo = Context::get('logged_info');
		if($oLoggedInfo) 
			Context::set('login_chk','Y');
		else
			Context::set('login_chk','N');

		Context::set('hide_trolley', 'true');
	}
/**
 * @brief 장바구니 목록 표시
 **/
	public function dispSvcartCartItems() 
	{
		$oSvcartModel = &getModel('svcart');
		$oCart = $oSvcartModel->getCartInfo();
		Context::set('list',$oCart->item_list);
		Context::set('sum_price',$oCart->sum_price);
		Context::set('total_price',$oCart->total_price);
		Context::set('delivery_fee',$oCart->delivery_fee);
		Context::set('total_discounted_price',$oCart->total_discounted_price);
		Context::set('total_discount_amount',$oCart->total_discount_amount);
		// get module config
		$config = $oSvcartModel->getModuleConfig();
		Context::set('config',$config);

		$oModuleModel = getModel('module');
		$nSvorderModuleSrl = $this->module_info->svorder_module_srl;
		if( !$nSvorderModuleSrl )
			return new BaseObject(-1, 'msg_svorder_module_not_configured');

		$oSvorderConfig = $oModuleModel->getModuleInfoByModuleSrl($nSvorderModuleSrl);
		$sSvorderMid = $oSvorderConfig->mid;
		if( strlen( $sSvorderMid ) == 0 )
			return new BaseObject(-1, 'msg_svorder_module_not_configured');

		Context::set('svorder_mid',$sSvorderMid);

		// get naverpay script - begin
		$oSvcartModel = &getModel('svcart');
		$oRst = $oSvcartModel->getNpayScriptBySvcartMid($this->module_srl, 'svcart');
		if (!$oRst->toBool())
			return $oRst;
		$aNpayScript = $oRst->get('aNpayScript');
		Context::set('npay_script_handler_global', $aNpayScript['global'] );
		Context::set('npay_script_handler_button', $aNpayScript['btn'] );
		// get naverpay script - end
		$this->setTemplateFile('cartitems');
	}
/**
 * @brief npay 구매하기 팝업창 처리를 위한 disp 메소드
 * 최초 시도에서는 class를 include 하려 했지만 
 * include로 메소드 호출하면 npay 서버가 Content-Type: text/plain;charset=ISO-8859-1 응답하며 처리 결과를 반환하지 않음
 * 정상 응답은 Content-Type: text/plain;charset=UTF-8 과 함께 반환됨
 **/
	public function dispSvcartNpayBuy() 
	{
		$sSvcartMid = Context::get('mid');
		if( !$sSvcartMid )
			return new BaseObject(-1, 'msg_invalid_svcart_mid');

		$oModuleModel = &getModel('module');
		$oSvcartMidConfig = $oModuleModel->getModuleInfoByMid($sSvcartMid);

		if( $oSvcartMidConfig->npay_toggle != 1 )
			return new BaseObject(-1, 'msg_npay_is_not_allowed');
		
		// check access right
		$oLoggedInfo = Context::get('logged_info');
		$oModuleGrant = $oModuleModel->getGrant($oSvcartMidConfig, $oLoggedInfo);
		if( !$oModuleGrant->display_npay_btn)
			return new BaseObject(-1, 'msg_npay_is_not_allowed');

		$oSvcartModel = &getModel('svcart');
		$oSvcartConfig = $oSvcartModel->getModuleConfig();

		if( !$oSvcartConfig->npay_shop_id || !$oSvcartConfig->npay_btn_key || !$oSvcartConfig->npay_mert_key )  
			return new BaseObject(-1, 'msg_npay_is_not_allowed');
		
		$sCartNos = Context::get('cartnos');
		$oSvcartModel = &getModel('svcart');
		$oParam->oCart = $oSvcartModel->getCartInfo( $sCartNos );
		$oParam->oLoggedInfo = $oLoggedInfo;
		$oParam->nClaimingReserves = 0;
		$oParam->sCouponSerial = '';
		$oSvorderModel = &getModel('svorder');
		$oRst = $oSvorderModel->confirmOffer( $oParam, 'new' );
		if(!$oRst->toBool())
			return $oRst;
		
		$oCart = $oRst->get('oCart');
		if( $oCart->delivfee_inadvance == 'Y' )
		{
			// SHIPPING_PRICE 배송료. 무료이면 0, 선불 또는 착불이면 배송료(0보다 커야 함). 착불이면서 배송료를 특정할 수 없는 경우에는 0
			// SHIPPING_TYPE  배송료 지불 방법. 무료이면 "FREE", 선불이면 "PAYED", 착불이면 "ONDELIVERY"
			if( $oCart->nDeliveryFee == 0 )
				$sShippingType = 'FREE';
			else
				$sShippingType = 'PAYED';
		}
		elseif( $oCart->delivfee_inadvance == 'N' )
			$sShippingType = 'ONDELIVERY';

		$nShippingPrice = $oCart->nDeliveryFee;
		
		$nSvshopMid = Context::get('shop_mid'); // shop_mid이 입력되면 개별 상세피이지에서 npay 버튼 클릭했다는 의미
		$nItemSrl = Context::get('item_srl'); // item_srl이 입력되면 개별 상세피이지에서 npay 버튼 클릭했다는 의미
		// 그러면 뒤로 가기 URL을 개별 상세페이지로 설정해야 함.
		if( $nSvshopMid && $nItemSrl )
			$sBackUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://'.$_SERVER[HTTP_HOST].'/'.$nSvshopMid.'/'.$nItemSrl;
		else
		{
			$sBackUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://'.$_SERVER[HTTP_HOST].$_SERVER[REQUEST_URI];
			$sBackUrl = urlencode( str_replace('dispSvcartNpayBuy','dispSvcartCartItems', $sBackUrl) );
		}

		$sShopId = $oSvcartConfig->npay_shop_id;
		$sQueryString = 'SHOP_ID='.urlencode($sShopId);
		$sQueryString .= '&CERTI_KEY='.urlencode($oSvcartConfig->npay_mert_key);
		$sQueryString .= '&SHIPPING_TYPE='.$sShippingType;
		$sQueryString .= '&SHIPPING_PRICE='.$nShippingPrice;
		//$sQueryString .= '&RESERVE1=&RESERVE2=&RESERVE3=&RESERVE4=&RESERVE5=';
		$sQueryString .= '&BACK_URL='.$sBackUrl;
		$sQueryString .= '&SA_CLICK_ID='.$_COOKIE["NVADID"]; //CTS
		// CPA 스크립트 가이드 설치 업체는 해당 값 전달
		$sQueryString .= '&CPA_INFLOW_CODE='.urlencode($_COOKIE["CPAValidator"]);
		$sQueryString .= '&NAVER_INFLOW_CODE='.urlencode($_COOKIE["NA_CO"]);
		
		$nTotalMoney = 0;
		require_once $this->module_path.'svcart.npay_item.php';
		$oNpayItem = new svcartNpayItem();
		foreach( $oCart->item_list as $nIdx=>$oVal )
		{
			$oArg->id = $oVal->item_srl;
			$oArg->name = $oVal->item_name;
			$oArg->uprice = $oVal->price;
			$oArg->count = $oVal->quantity;
			$oArg->tprice = $oVal->discounted_price;
			$nRealPrice = $oVal->discounted_price * $oVal->quantity;
			$oArg->option = '';
			$nTotalMoney += $nRealPrice;
			$sQueryString .= '&'.$oNpayItem->makeQueryStringBuy($oArg);
		}
		$nTotalPrice = (int)$nTotalMoney + (int)$nShippingPrice;
		$sQueryString .= '&TOTAL_PRICE='.$nTotalPrice;
		$sTestServerPrefix = '';
		if( $oSvcartConfig->npay_mode == 0 )
			$sTestServerPrefix = 'test-';
		
		$req_addr = 'ssl://'.$sTestServerPrefix .'pay.naver.com';
		$req_url = 'POST /customer/api/order.nhn HTTP/1.1'; // utf-8
		// $req_url = 'POST /customer/api/CP949/order.nhn HTTP/1.1'; // euc-kr
		$req_host = $sTestServerPrefix.'pay.naver.com';
		$req_port = 443;
		$nc_sock = @fsockopen($req_addr, $req_port, $errno, $errstr);
		if ($nc_sock) 
		{
			fwrite($nc_sock, $req_url."\r\n" );
			fwrite($nc_sock, "Host: ".$req_host.":".$req_port."\r\n" );
			fwrite($nc_sock, "Content-type: application/x-www-form-urlencoded; charset=utf-8\r\n");
			//fwrite($nc_sock, "Content-type: application/x-www-form-urlencoded; charset=CP949\r\n");
			fwrite($nc_sock, "Content-length: ".strlen($sQueryString)."\r\n");
			fwrite($nc_sock, "Accept: */*\r\n");
			fwrite($nc_sock, "\r\n");
			fwrite($nc_sock, $sQueryString."\r\n");
			fwrite($nc_sock, "\r\n");
			// get header
			while(!feof($nc_sock))
			{
				$header=fgets($nc_sock,4096);
				if($header=="\r\n")
					break;
				else 
					$headers .= $header;
			}
			// get body
			while(!feof($nc_sock))
				$bodys.=fgets($nc_sock,4096);
			fclose($nc_sock);
			$resultCode = substr($headers,9,3);
			if ($resultCode == 200)  // success 리턴받은 order_id로 주문서 page를 호출한다.
			{
				$orderId = $bodys;
				$orderUrl = 'https://'.$sTestServerPrefix.'pay.naver.com/customer/order.nhn';
				Context::set('sOrderUrl',$orderUrl);
				Context::set('sOrderId',$orderId);
				Context::set('sShopId',$sShopId);
				Context::set('nTotalPrice',$nTotalPrice);

				$oArgs->npay_order_srl = $orderId;
				$oArgs->member_srl = $oLoggedInfo->member_srl;
				$oArgs->cartnos = $sCartNos;
				$oArgs->deliv_fee = $nShippingPrice;
				$oArgs->ttl_price = $nTotalPrice;
				// user agent information to trace npay committed source/medium
				$oArgs->is_mobile_access = $_COOKIE['mobile'] == 'false' ? 'N' : 'Y';
				$oArgs->http_user_agent = trim( $_SERVER['HTTP_USER_AGENT'] );
				// utm_params information
				if( isset( $_SESSION['HTTP_INIT_SOURCE'] ) && strlen( $_SESSION['HTTP_INIT_SOURCE'] ) > 0 )
					$oArgs->utm_source = $_SESSION['HTTP_INIT_SOURCE'];
				if( isset( $_SESSION['HTTP_INIT_MEDIUM'] ) && strlen( $_SESSION['HTTP_INIT_MEDIUM'] ) > 0 )
					$oArgs->utm_medium = $_SESSION['HTTP_INIT_MEDIUM'];
				if( isset( $_SESSION['HTTP_INIT_CAMPAIGN'] ) && strlen( $_SESSION['HTTP_INIT_CAMPAIGN'] ) > 0 )
					$oArgs->utm_campaign = $_SESSION['HTTP_INIT_CAMPAIGN'];
				if( isset( $_SESSION['HTTP_INIT_KEYWORD'] ) && strlen( $_SESSION['HTTP_INIT_KEYWORD'] ) > 0 )
					$oArgs->utm_term = $_SESSION['HTTP_INIT_KEYWORD'];
				$oRst = executeQuery('svcart.insertNpayReserved', $oArgs);
				if (!$oRst->toBool())
					return $oRst;

				$this->setTemplatePath( $this->module_path.'tpl/');
				$this->setTemplateFile('_npay_redirect_to_buy');
			} 
			else // fail
				$oRst->setMessage('fails with code - '.$resultCode.' msg: '.$bodys); //echo $bodys;
		}
		else // socket 에러처리
			return new BaseObject(-1, "$errstr ($errno)"); //echo "$errstr ($errno)<br>\n";
	}
/**
 * @brief npay 찜 팝업창 처리를 위한 disp 메소드
 * 최초 시도에서는 class를 include 하려 했지만 
 * include로 메소드 호출하면 npay 서버가 Content-Type: text/plain;charset=ISO-8859-1 응답하며 처리 결과를 반환하지 않음
 * 정상 응답은 Content-Type: text/plain;charset=UTF-8 과 함께 반환됨
 **/
	public function dispSvcartNpayFavorite() 
	{
		$sSvcartMid = Context::get('mid');
		if( !$sSvcartMid )
			return new BaseObject(-1, 'msg_invalid_svcart_mid');

		$nItemSrl = Context::get('item_srl');
		if( !$nItemSrl )
			return new BaseObject(-1, 'msg_invalid_item_srl');

		$aItemSrl[] = $nItemSrl;

		$oLoggedInfo = Context::get('logged_info');
		//if( !$oLoggedInfo )
		//	return new BaseObject(-1, 'msg_login_required');

		$oSvcartController = &getController('svcart');
		$oArgs->item_srl = $nItemSrl;
		$oArgs->member_srl = $oLoggedInfo->member_srl;
		$oRst = $oSvcartController->addItemsToFavorites($oArgs);
		//if(!$oRst->toBool())
		//	return $oRst;

		$oModuleModel = &getModel('module');
		$oSvcartMidConfig = $oModuleModel->getModuleInfoByMid($sSvcartMid);
		if( $oSvcartMidConfig->npay_toggle != 1 )
			return new BaseObject(-1, 'msg_npay_is_not_allowed');

		// check access right
		$oModuleGrant = $oModuleModel->getGrant($oSvcartMidConfig, $oLoggedInfo);
		if( !$oModuleGrant->display_npay_btn)
			return new BaseObject(-1, 'msg_npay_is_not_allowed');

		$oSvcartModel = &getModel('svcart');
		$oSvcartConfig = $oSvcartModel->getModuleConfig();

		if( !$oSvcartConfig->npay_shop_id || !$oSvcartConfig->npay_btn_key || !$oSvcartConfig->npay_mert_key )  
			return new BaseObject(-1, 'msg_npay_is_not_allowed');

		$db_info = Context::getDBInfo();
		$sServerName = $db_info->default_url;
		
		$oSvitemView = &getView('svitem');
		$oSvitemModel = &getModel('svitem');
		$oModuleModel = &getModel('module');
		$oSvpromotionModel = &getModel('svpromotion');

		$sShopId = $oSvcartConfig->npay_shop_id;

		$sQueryString = 'SHOP_ID='.urlencode($sShopId);
		$sQueryString .= '&CERTI_KEY='.urlencode($oSvcartConfig->npay_mert_key);
		$sQueryString .= '&RESERVE1=&RESERVE2=&RESERVE3=&RESERVE4=&RESERVE5=';
		
		require_once $this->module_path.'svcart.npay_item.php';
		$oNpayItem = new svcartNpayItem();
		foreach( $aItemSrl as $nIdx => $nItemSrl )
		{
			$oArg->id = $nItemSrl;
			$oItemInfo = $oSvitemModel->getItemInfoByItemSrl($nItemSrl);
			$oModuleInfo = $oModuleModel->getModuleInfoByModuleSrl($oItemInfo->module_srl);
			$sMid = $oModuleInfo->mid;

			$oArg->name = $oItemInfo->item_name;
			$oArg->uprice = $oItemInfo->price;
			$oArg->image = str_replace('https', 'http', $oSvitemView->_dispThumbnailUrl( $oItemInfo->thumb_file_srl, 500, 500, 'crop') );
			$oArg->thumb = str_replace('https', 'http', $oSvitemView->_dispThumbnailUrl( $oItemInfo->thumb_file_srl, 300, 300, 'crop') );
			$oArg->url = $sServerName.$sMid.'/'.$oItemInfo->document_srl.'?utm_source=naver&utm_medium=referral&utm_campaign=NV_NS_REF_NPAY_WISH_00&utm_term=npay_wish_'.$oItemInfo->document_srl;
			$sQueryString .= '&'.$oNpayItem->makeQueryStringWish($oArg);
		}
		
		$sTestServerPrefix = '';
		if( $oSvcartConfig->npay_mode == 0 )
			$sTestServerPrefix = 'test-';

		$req_addr = 'ssl://'.$sTestServerPrefix.'pay.naver.com';
		$req_url = 'POST /customer/api/wishlist.nhn HTTP/1.1'; // utf-8
		// $req_url = 'POST /customer/api/CP949/wishlist.nhn HTTP/1.1'; // euc-kr
		$req_host = $sTestServerPrefix.'pay.naver.com';
		$req_port = 443;
		$nc_sock = @fsockopen($req_addr, $req_port, $errno, $errstr);
		if ($nc_sock) 
		{
			fwrite($nc_sock, $req_url."\r\n" );
			fwrite($nc_sock, "Host: ".$req_host.":".$req_port."\r\n" );
			fwrite($nc_sock, "Content-type: application/x-www-form-urlencoded; charset=utf-8\r\n"); // utf-8
			//fwrite($nc_sock, "Content-type: application/x-www-form-urlencoded; charset=CP949\r\n"); // euc-kr
			fwrite($nc_sock, "Content-length: ".strlen($sQueryString)."\r\n");
			fwrite($nc_sock, "Accept: */*\r\n");
			fwrite($nc_sock, "\r\n");
			fwrite($nc_sock, $sQueryString."\r\n");
			fwrite($nc_sock, "\r\n");
			// get header
			while(!feof($nc_sock))
			{
				$header=fgets($nc_sock,4096);
				if($header=="\r\n")
					break;
				else 
					$headers .= $header;
			}
			// get body
			while(!feof($nc_sock))
				$bodys.=fgets($nc_sock,4096);

			fclose($nc_sock);
			$resultCode = substr($headers,9,3);
			if ($resultCode == 200) // success
			{
				//$itemId = $bodys; // 한개일경우
				// 여러개일경우
				$itemIds = trim($bodys);
				$itemIdList = split(",",$itemIds);
				
				if( $_COOKIE['mobile'] == 'true' )
					$wishlistPopupUrl = 'https://'.$sTestServerPrefix.'m.pay.naver.com/mobile/customer/wishList.nhn';
				else
					$wishlistPopupUrl = 'https://'.$sTestServerPrefix.'pay.naver.com/customer/wishlistPopup.nhn';

				Context::set('sWishlistUrl',$wishlistPopupUrl);
				Context::set('sShopId',$sShopId);
				Context::set('aItemIds',$itemIdList);
				$this->setTemplatePath( $this->module_path.'tpl/');		
				$this->setTemplateFile('_npay_redirect_to_favorite');
			} 
			else // fail
				$oRst->setMessage('fails with code - '.$resultCode.' msg: '.$bodys); //echo $bodys;
		}
		else // socket 에러처리
			return new BaseObject(-1, "$errstr ($errno)"); //echo "$errstr ($errno)<br>\n";
	}
/**
 * @brief 
 **/
	public function dispSvcartFavoriteItems() 
	{
		$oSvcartModel = &getModel('svcart');
		$oLoggedInfo = Context::get('logged_info');
		if (!$oLoggedInfo) 
			return new BaseObject(-1, 'msg_login_required');

		// favorite items
		$aFavoriteItems = $oSvcartModel->getFavoriteItems($oLoggedInfo->member_srl);
		Context::set('favorite_items', $aFavoriteItems);

		// get module config
		$oConfig = $oSvcartModel->getModuleConfig();
		Context::set('config',$oConfig);

		$this->setTemplateFile('favoriteitems');
	}
/**
 * @brief 
 **/
	public function dispSvcartLogin() 
	{
		$oSvcartModel = &getModel('svcart');
		// get module config
		$config = $oSvcartModel->getModuleConfig();
		Context::set('config',$config);

		$this->setTemplateFile('login_form');
	}
/**
 * @brief 
 **/
	public function dispSvcartNonLoginOrder() 
	{
		$this->setTemplateFile('orderlistlogin');
	}
}
/* End of file svcart.view.php */
/* Location: ./modules/svcart/svcart.view.php */