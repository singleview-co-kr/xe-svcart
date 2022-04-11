<?php
/**
 * @class  svcartModel
 * @author singleview(root@singleview.co.kr)
 * @brief  svcartModel
 */
class svcartModel extends svcart
{
/**
 * @brief 
 **/
	public function init() 
	{
		//if (!$this->module_info->thumbnail_width) $this->module_info->thumbnail_width = 150;
		//if (!$this->module_info->thumbnail_height) $this->module_info->thumbnail_height = 150;
	}
/**
 * @brief 
 **/
	public function getModuleConfig()
	{
		$oModuleModel = &getModel('module');
		return $oModuleModel->getModuleConfig('svcart');
		//$config = $oModuleModel->getModuleConfig('svcart');
		//if (!$config->cart_thumbnail_width) $config->cart_thumbnail_width = 100;
		//if (!$config->cart_thumbnail_height) $config->cart_thumbnail_height = 100;
		//if (!$config->favorite_thumbnail_width) $config->favorite_thumbnail_width = 100;
		//if (!$config->favorite_thumbnail_height) $config->favorite_thumbnail_height = 100;
		//if (!$config->order_thumbnail_width) $config->order_thumbnail_width = 100;
		//if (!$config->order_thumbnail_height) $config->order_thumbnail_height = 100;
		//if (!$config->address_input) $config->address_input = 'krzip';
		//if (!$config->mileage_method) $config->mileage_method = 'svcart';
		//$oCurrencyModule = &getModel('currency');
		//$currency = $oCurrencyModule->getModuleConfig();
		//if (!$currency->currency) $config->currency = 'KRW';
		//else	$config->currency = $currency->currency;
		//if (!$currency->as_sign) $config->as_sign = 'Y';
		//else	$config->as_sign = $currency->as_sign;
		//if (!$currency->decimals) $config->decimals = 0;
		//else	$config->decimals = $currency->decimals;
		//if( getClass('svitem') )
		//{
		//	$oSvitemModel = &getModel('svitem');
		//	$currency = $oSvitemModel->getCurrencyInfo();
		//	$currency = $oSvcurrencyModel->getModuleConfig();
		//}
		//else
		//	return new BaseObject(-1,'msg_svitem_uninstalled');
		//$config->currency = $currency->currency;
		//$config->as_sign = $currency->as_sign;
		//$config->decimals = $currency->decimals;
		//return $config;
	}
/**
 * @brief 회원 혹은 비회원 카트 정보 반환. 로그인 했을 때 비회원 카트에 남아 있던 상품을 회원 카트로 옮겨 담아준다.
 * $sCartNos: act=dispSvorderOrderForm&cartnos=xxxx,yyyy 에서 사용
 */
	public function getCartInfo($sCartNos=null)
	{
		$oConfig = $this->getModuleConfig();
		$nExpirationDays = $oConfig->cart_expiration_days;
		$sOldestDatetime = null;
		if( isset( $nExpirationDays ) )
			$sOldestDatetime = date('Ymdhis', strtotime('-'.$nExpirationDays.' days'));

		$oLoggedInfo = Context::get('logged_info');
		$sNonKey = $_COOKIE['non_key'];
		$oCartInfo = null;
		if( !$oLoggedInfo ) // 로그인 안되어 있을 때 비회원카트 정보를 가져옴
			$oCartInfo = $this->getGuestCartInfo($sNonKey, $sOldestDatetime, $sCartNos);
		else
		{
			if($sNonKey) // 로그인되고 non_key가 있으면(비회원으로 담은 상품이 있으면) 회원 카트로 이동
			{
				$oSvcartController = &getController('svcart');
				$oSvcartController->updateGuestCartItems($oLoggedInfo->member_srl, $sNonKey);
			}
			// 로그인 되어 있을 때 회원 카트정보 가져옴
			$oCartInfo = $this->getMemberCartInfo( $oLoggedInfo->member_srl, $sOldestDatetime, $sCartNos);
		}
		return $oCartInfo;
	}
/**
 * @brief cart items
 * $sCartNos: act=dispSvorderOrderForm&cartnos=xxxx,yyyy 에서 사용
 **/
	public function getGuestCartInfo($sNonKey, $sOldestDatetime, $sCartNos=null)
	{
		$oArgs->non_key = $sNonKey;
		if( $sOldestDatetime)
			$oArgs->startdate = $sOldestDatetime;
		$oArgs->cartnos = $sCartNos;
		$oRst= executeQueryArray('svcart.getNonCartItems', $oArgs);
		if (!$oRst->toBool())
			return $oRst;
		$aItemList = $oRst->data;
		return $this->_discountItems($aItemList);
	}
/**
 * @brief cart items
 * $sCartNos: act=dispSvorderOrderForm&cartnos=xxxx,yyyy 에서 사용
 **/
	public function getMemberCartInfo($nMemberSrl, $sOldestDatetime, $sCartNos=null)
	{
        $oArgs = new stdClass();
		$oArgs->cartnos = $sCartNos;
		$oArgs->member_srl = $nMemberSrl;
		if($sOldestDatetime)
			$oArgs->startdate = $sOldestDatetime;
		$oRst = executeQueryArray('svcart.getCartItems', $oArgs);
		if(!$oRst->toBool())
			return $oRst;

		$aItemList = $oRst->data;
		return $this->_discountItems($aItemList);
	}
/**
 * @brief svitem/tpl/skin.js/loadcount.js에서 ajax 호출
 * svcart/tpl/skin.js/loadcount.js에서 ajax 호출
 **/
	public function getSvcartFavoriteItems() 
	{
		if(Context::get('image_width') && Context::get('image_height'))
		{
			$image_width = Context::get('image_width');
			$image_height = context::get('image_height');
		}
		else
		{
			$image_width = 50;
			$image_height = 50;
		}

		$logged_info = Context::get('logged_info');
		if (!$logged_info)
			return new BaseObject(-1, 'msg_invalid_request');
		$member_srl = $logged_info->member_srl;
		$item_list = $this->getFavoriteItems($member_srl, $image_width, $image_height);
		$this->add('data', $item_list);
		$this->add('item_count', count($item_list));
	}
/**
 * @brief svcart.view.php::dispSvcartFavoriteItems()에서 호출
 **/
	public function getFavoriteItems($nMemberSrl)
	{
		// my group list
		$oMemberModel = &getModel('member');
		$aGroupList = $oMemberModel->getMemberGroups($nMemberSrl);
		// favorite items
		$oArgs->member_srl = $nMemberSrl;
		$output = executeQueryArray('svcart.getFavoriteItems', $oArgs);
		if (!$output->toBool()) 
			return $output;
		$aFavoriteItems = $output->data;
		
		$oSvitemModel = &getModel('svitem');
		$oSvpromotionModel = &getModel('svpromotion');
		foreach( $aFavoriteItems as $nIdx => $oVal )
		{
			$oItemInfo = $oSvitemModel->getItemInfoByItemSrl($oVal->item_srl);
			if(!$oItemInfo)
				return new BaseObject(-1, 'Item not found.');

			$oVal->item_name = $oItemInfo->item_name;
			$oVal->thumb_file_srl = $oItemInfo->thumb_file_srl;
			$oVal->document_srl = $oItemInfo->document_srl;
			$oVal->price = $oItemInfo->price;

			$output = $oSvpromotionModel->getPromotionInfoForItemDetailPage($oItemInfo);
			$oVal->giveaway['item_name'] = $output['giveaway']->conditional_additional_discount_giveaway_item_name;
			$oVal->giveaway['item_price'] = $output['giveaway']->conditional_additional_discount_giveaway_item_price;
			$oVal->giveaway['item_url'] = $output['giveaway']->conditional_additional_discount_giveaway_item_url;
			$oVal->unconditional['discount_info'] = $output['unconditional_disc']->discount_info;
			foreach( $output['conditional']->promotion as $key => $val )
			{
				if( $val->conditional_additional_discount_amount > 0 )
				{
					$nConditionalAdditionalDisc += $val->conditional_additional_discount_amount;
					if( $val->conditional_additional_discount_type == 'fblike' )
						$oFbLikePromotion = $output['conditional']->promotion[$key];
					if( $val->conditional_additional_discount_type == 'fbshare' )
						$oFbSharePromotion = $output['conditional']->promotion[$key];
				}
			}
			$oVal->conditional = $oFbSharePromotion;
		}
		$aFavoriteItems = $oSvpromotionModel->getItemPriceList($aFavoriteItems);
		return $aFavoriteItems;
	}
/**
 * @brief 
 **/
	public function getSvcartCartItems() 
	{
		/*if(Context::get('image_width') && Context::get('image_height'))
		{
			$image_width = Context::get('image_width');
			$image_height = context::get('image_height');
		}
		else
		{
			$image_width = 50;
			$image_height = 50;
		}*/
		$oCartInfo = $this->getCartInfo();//null, $image_width, $image_height);
		$this->add('data', $oCartInfo->item_list);
		$this->add('item_count', count($oCartInfo->item_list));
	}
/**
 * @brief svitem.model.php::getNpayScriptBySvcartMid()에서 호출
 * svcart.view.php::dispSvcartCartItems()에서 호출
 * @return
 */
	public function getNpayScriptBySvcartMid($nSvcartModuleSrl, $sCallerModule, $nItemSrl=null, $nAvailableStock=null)
	{
		if( !$nSvcartModuleSrl )
			return new BaseObject(-1, 'msg_invalid_svcart_module_srl');
		$oModuleModel = &getModel('module');
		$oSvcartMidConfig = $oModuleModel->getModuleInfoByModuleSrl($nSvcartModuleSrl);

		if( $oSvcartMidConfig->npay_toggle )
		{
			$oLoggedInfo = Context::get('logged_info');
			$oModuleGrant = $oModuleModel->getGrant($oSvcartMidConfig, $oLoggedInfo);
			if( $oModuleGrant->display_npay_btn)
			{
				Context::set('item_srl', $nItemSrl );
				Context::set('svcart_mid', $oSvcartMidConfig->mid );

				$oConfig = $this->getModuleConfig();
				if( !$oConfig->npay_mode )
					Context::set('npay_test_server', 'test-' );

				switch( $sCallerModule )
				{
					case 'svitem':
						Context::set('npay_btn_cnt', 2 );
						break;
					case 'svcart':
						Context::set('npay_btn_cnt', 1 );
						break;
				}

				Context::set('npay_btn_key', $oConfig->npay_btn_key );
				if( $nItemSrl )
					$bBtnEnable = $nAvailableStock ? 'Y' : 'N';
				else
					$bBtnEnable = 'Y';

				Context::set('npay_btn_enable', $bBtnEnable );
				$oTemplate = &TemplateHandler::getInstance();
				$sPath = _XE_PATH_.'modules/svcart/tpl/';
				$aNpayScript['global'] = $oTemplate->compile($sPath, '_npay_handler_global_to_svitem.html');
				$aNpayScript['btn'] = $oTemplate->compile($sPath, '_npay_handler_button_to_svitem.html');
			}
		}
		$oRst = new BaseObject();
		$oRst->add( 'aNpayScript', $aNpayScript );
		return $oRst;
	}
/**
 * @brief return module name in sitemap
 **/
    public function triggerModuleListInSitemap(&$obj)
    {
        array_push($obj, 'svcart');
    }
/**
 * @brief svitem.admin.view.php에서 호출
 **/
	public function getModInstList()
	{
		$output = executeQueryArray('svcart.getModInstList', $args);
		return $output->data;
	}
/**
 * @brief 그룹할인이 있으면 그룹할인으로 적용하고 그룹할인이 없을 때는 상품별 할인 적용.
 */
	private function _discountItems(&$aItemList)
	{
		$oSvitemModel = &getModel('svitem');
//// GA EEC params를 불러오는 과정이 svpromotion과 엮이면서 svitem.item.php를 여러번 호출하며 중복연산하여 매우 비효율적임
// 이거 없애도 됨?
		foreach( $aItemList as $nIdx=>$oCartVal )
		{
			$oItemInfo = $oSvitemModel->getItemInfoByItemSrl( $oCartVal->item_srl );
			$oCartVal->enhanced_item_info = $oItemInfo->enhanced_item_info;
			$oCartVal->item_code = $oItemInfo->item_code;
			$oCartVal->item_name = $oItemInfo->item_name;
			$oCartVal->price = $oItemInfo->price; // svpromotion 계산 용 -> item_price로 변경해야 함
			$oCartVal->item_price = $oItemInfo->price; // cartitems.html 표시 용
			$oCartVal->document_srl = $oItemInfo->document_srl;
			$oCartVal->thumb_file_srl = $oItemInfo->thumb_file_srl;
			$oCartVal->taxfree = $oItemInfo->taxfree;
		}
		$oSvpromotionModel = &getModel('svpromotion');
		return $oSvpromotionModel->getItemPriceCart($aItemList );
	}
/*
 * @brief 폐기 예정
 */
/*	public function getCartItems($member_srl, $cartnos=null, $width=null, $height=null) 
	{
		$oMemberModel = &getModel('member');
		// my group list
		$group_list = $oMemberModel->getMemberGroups($member_srl);

		// default values
		if (!$width) $width = 80;
		if (!$height) $height = 80;

		// cart items
		$args->member_srl = $member_srl;
		$args->module_srl = $module_srl;
		$args->cartnos = $cartnos;
		$output= executeQueryArray('svcart.getCartItems', $args);
		if (!$output->toBool()) return $output;
		$item_list = $output->data;
		if (!is_array($item_list)) $item_list = array();
		return $this->discountItems($item_list, $group_list, $width,$height);
	}*/
}
/* End of file svcart.model.php */
/* Location: ./modules/svcart/svcart.model.php */