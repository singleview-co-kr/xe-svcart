<?php
/**
 * @class  svcartController
 * @author singleview(root@singleview.co.kr)
 * @brief  svcartController
 */
class svcartController extends svcart
{
/**
 * @brief
 */
	public function procSvcartDeleteCart() 
	{
		$cart_srls = Context::get('cart_srls');
		$cart_srls = explode(',', $cart_srls);
		$args = new stdClass();
        foreach($cart_srls as $val) 
		{
			if (!$val)
				continue;
			$args->cart_srl = $val;
			$output = executeQuery('svcart.deleteCart', $args);
			if(!$output->toBool())
				return $output;
		}
		$this->setMessage('success_deleted');
	}
/**
 * @brief
 */
	public function procSvcartDeleteFavoriteItems() {
		$item_srls = Context::get('item_srls');
		$item_srls = explode(',', $item_srls);
		foreach($item_srls as $val) 
		{
			if(!$val && !$val == 0) 
				continue;
			$args->item_srl = $val;
			$output = executeQuery('svcart.deleteFavoriteItem', $args);
			if(!$output->toBool()) 
				return $output;
		}
		$this->setMessage('success_deleted');
	}
/**
 * @brief
 */
	public function procSvcartUpdateQuantity() 
	{
        $args = new stdClass();
		$args->cart_srl = (int)Context::get('cart_srl');
		$args->quantity = (int)Context::get('quantity');
		$oRst = $this->_validateCartAuthority();
		$nMaxQty = $oRst->nMaxQty;
		$nExistingCartQty = 0;
		foreach($oRst->aExistingCartQtyInfo as $key=>$val)
			$nExistingCartQty += $key == $args->cart_srl ? $args->quantity : $val;

		if($nExistingCartQty > $nMaxQty)
			return new BaseObject(-1, 'msg_exceed_qty_limit');

		$output = executeQuery('svcart.getCartItem', $args);
		if(!$output->toBool()) 
			return $output;

		$oConditionalPromotionInfo = unserialize($output->data->conditional_promotion);
		if($oConditionalPromotionInfo)
		{
			if($oConditionalPromotionInfo->version == '1.0')
			{
				foreach($oConditionalPromotionInfo->promotion as $nIdx => $oVal)
				{
					if(isset($oVal->resultant_giveaway_qty))
						$oVal->resultant_giveaway_qty = $args->quantity;
				}
			}
			$sConditionalPromotionInfo = serialize($oConditionalPromotionInfo);
			$args->conditional_promotion = $sConditionalPromotionInfo;
		}
		$output = executeQuery('svcart.updateCartItem', $args);
		if(!$output->toBool()) 
			return $output;
		$this->setMessage('success_changed');
	}
/**
 * @brief
 */
	public function createCartObj()
	{
		$oRst = $this->_validateCartAuthority();
		$nExistingCartQty = $oRst->nExistingCartQty;
		$nMaxQty = $oRst->nMaxQty;
		if($nExistingCartQty > $nMaxQty)
			return new BaseObject(-1, 'msg_exceed_qty_limit');

		$nRequstingCartQty = 0;
		$oSvitemModel = &getModel('svitem');
		$oModuleModel = &getModel('module');
		$oSvpromotionModel = &getModel('svpromotion');
		$sJsonData = Context::get('data');
		$aRequestedItemToCart = json_decode($sJsonData);
		foreach($aRequestedItemToCart as $nCartIdx => $oCartedItemInfo)
		{
			if(!$oCartedItemInfo) 
				continue;
			if(!$oCartedItemInfo->quantity)
				$oCartedItemInfo->quantity = 1;

			$nRequstingCartQty += $oCartedItemInfo->quantity;
			if($nExistingCartQty + $nRequstingCartQty > $nMaxQty)
				return new BaseObject(-1, 'msg_exceed_qty_limit');
			
            $oItemParam = new stdClass();
			$oItemParam->nItemSrl = $oCartedItemInfo->item_srl;
			$oItemInfo = $oSvitemModel->getItemInfoNewFull($oItemParam);
			if(!$oItemInfo)
				return new BaseObject(-1, 'msg_item_not_found');
			unset($oItemParam);

////////// 번들링 제품 선택 정보 확인 시작 /////////////////
			if(strlen($oCartedItemInfo->bundling_items) > 0)
			{
				$aDeterminedBundlingInfo = Array();
				$aFinalBundlings = explode(',', $oCartedItemInfo->bundling_items);
				$nElements = count($aFinalBundlings);
				if($nElements > 0)
				{
					for($i = 0; $i < $nElements; $i++)
					{
						if(strlen( $aFinalBundlings[$i] ) > 0)
						{
							$aSingleBundle = explode('^', $aFinalBundlings[$i]);
							$aDeterminedBundlingInfo[$i]->bundle_item_srl = $aSingleBundle[0];
							$aDeterminedBundlingInfo[$i]->bundle_quantity = $aSingleBundle[1];
						}
					}
				}
			}
////////// 번들링 제품 선택 정보 확인 끝 /////////////////
			// check stock
			$nStock = $oItemInfo->current_stock;
			if($stock !== null && ($stock < $oCartedItemInfo->quantity || $stock == 0))
				return new BaseObject(-1, sprintf( Context::getLang( 'msg_not_enough_stock'), $oItemInfo->item_name));

			// 수량 할인 기능 추가 위해 구조체 확장
            $oItemPromo = new stdClass();
			$oItemPromo->module_srl = $oItemInfo->nModuleSrl;
			$oItemPromo->item_srl = $oItemInfo->item_srl;
			$oItemPromo->quantity = $oCartedItemInfo->quantity;
			$oItemPromo->price = $oItemInfo->price;

			// 프로모션 정보 확인
			if($oCartedItemInfo->fb_liked == 1) // 아이템별 기본 할인 정책 가져오기
				$aRequestedPromotion[] = 'fblike';
			if($oCartedItemInfo->fb_shared == 1) // 아이템별 fb share 할인 정책 가져오기
				$aRequestedPromotion[] = 'fbshare';
			
			$oPromoRst = $oSvpromotionModel->getPromotionDetailForCartAddition($oItemPromo, $oCartedItemInfo, $aRequestedPromotion);
			if(!$oPromoRst->toBool())
				return $oPromoRst;
			$oPromotionInfo = $oPromoRst->get('item_promotion_info');
			unset($oPromoRst);
			unset($oItemPromo);
			
			// FB like 와 FB shr만 conditional_promotion에 남기고 장바구니 DB에 기록함
            $oArgs = new stdClass();
			$oArgs->conditional_promotion = $oPromotionInfo->conditional_promotion; 

			// 구매옵션 정보 확인
			$aOption = $oItemInfo->aBuyingOption; //$oSvitemModel->getOptions($oCartedItemInfo->item_srl);

			// 구매옵션이 있는 상품이면 구매옵션 선택 여부를 체크해야 한다.
			if(count($aOption) && !$oCartedItemInfo->option_srl)
				return new BaseObject(-1, 'msg_select_option');

			// 기본 배송회사ID 가져오기 위해 모듈정보 읽기
			$oArgs->cart_srl = 0; // will be passed by $oSvcartController->addItems
			$oArgs->module_srl = $oItemInfo->nModuleSrl;
			$oArgs->item_srl = $oItemInfo->item_srl;
			$oArgs->member_srl = 0;
			$oArgs->quantity = $oCartedItemInfo->quantity;
			$oArgs->price = $oItemInfo->price;
			$oArgs->option_srl = $oCartedItemInfo->option_srl;
			$oArgs->option_price = $aOption[$oCartedItemInfo->option_srl]->price;
			$oArgs->option_title = $aOption[$oCartedItemInfo->option_srl]->title;

			// 번들링 구매 정보 입력
			if(count((array)$aDeterminedBundlingInfo) > 0)
				$oArgs->bundling_order_info = serialize($aDeterminedBundlingInfo);
			// addItems will return $oArgs->cart_srl
			$oCartAddRst = $this->_addItemsToCart($oArgs);
			if(!$oCartAddRst->toBool())
				return $oCartAddRst;
			$aCartSrl[] = $oCartAddRst->get('cart_srl');
			unset($oCartAddRst);
			unset($oArgs);
		}
		$oRstFinal = new BaseObject();
		$oRstFinal->add('cart_srl_arr', $aCartSrl);
		return $oRstFinal;
	}
/**
 * svorder.controller.php::insertOrderFromNpay()에서 호출
 * Return the sequence value incremented by 1
 * Auto_increment column only used in the sequence table
 * @return int
 */
	public function getCartSrl()
	{
		$oDB_class = new DBMysqli;
		$query = sprintf("insert into `%ssvcart_sequence` (seq) values ('0')", $oDB_class->prefix);
		$oDB_class->_query($query);
		$sequence = $oDB_class->db_insert_id();
		if($sequence % 10000 == 0)
		{
			$query = sprintf("delete from  `%ssvcart_sequence` where seq < %d", $oDB_class->prefix, $sequence);
			$oDB_class->_query($query);
		}
		return $sequence;
	}
/**
 * @brief svorder.view.php::dispSvorderOrderForm()에서 호출
 * 장바구니 목록 화면에서 결제하기를 클릭한 시점 기록
 */
	public function markOfferDate($sCartnos)
	{
		$aCartNo = explode( ',', $sCartnos);
		if(count($aCartNo) == 0)
			return new BaseObject(-1,'msg_invalid_request');
		$oArg = new stdClass();
		foreach($aCartNo as $nIdx => $nCartSrl)
		{
			$oArg->cart_srl = $nCartSrl;
			$oRst = executeQuery('svcart.updateCartOfferDate', $oArg);
			if(!$oRst->toBool()) 
				return $oRst;
		}
		return new BaseObject();
	}
/**
 * @brief svitem.controller.php::procSvitemAddItemsToFavorites()에서 호출
 */
	public function addItemsToFavorites(&$in_args) 
	{
		$output = executeQuery('svcart.getFavoriteItemCount', $in_args);
		if(!$output->toBool()) 
			return $output;
		if($output->data && $output->data->count) 
			return new BaseObject(-1,'msg_duplicated_favorite_item');

		$output = executeQuery('svcart.insertFavoriteItem', $in_args);
		if(!$output->toBool()) 
			return $output;

		return new BaseObject();
	}
/**
 * @brief 로그인 했을 때 회원 카트로 이동 (non_key값을 삭제하고 member_srl값을 입력)
 * svcart.model.php::getCartInfo()에서 호출
 */
	public function updateGuestCartItems($member_srl, $non_key)
	{
		$args->member_srl = $member_srl;
		$args->non_key = $non_key;
		$args->del_non_key = '';
		$output = executeQuery('svcart.updateNonCartItem', $args);
		if(!$output->toBool()) 
			return $output;
	}
/**
 * @brief svpg.controller.php::procSvpgReviewOrder()에서 호출
 * set cart ordered and deactivated when purchaser transaction has been succeeded
 */
	public function deactivateCart($oParam)
	{
		if(!defined(svorder::ORDER_STATE_ON_DEPOSIT))
			getClass('svorder');
		$oArgs = new stdClass();
		switch($oParam->state) 
		{
			case '1': // not completed
			case '3': // failure
				$oArgs->order_status = svorder::ORDER_STATE_ON_DEPOSIT;
				break;
			case '2': // completed
				$oArgs->order_status = svorder::ORDER_STATE_PAID;
				break;
		}
		$oArgs->order_srl = $oParam->order_srl;
		return executeQuery('svcart.updateCartOrderSrlStatus', $oArgs);
	}
/**
 * @brief svpg.controller.php::procSvpgReviewOrder()에서 호출
 * 주문 절차 완료 후 svcart의 관련 품목을 비활성화하기 위해 svorder_srl을 먼저 기록함
 */
	public function setEstiOrderSrl($oArgs)
	{
		$aCartSrl = explode(',', $oArgs->cartnos);
		$oCartArgs = new stdClass();
		$oCartArgs->order_srl = $oArgs->order_srl;
		foreach($aCartSrl as $nIdx => $nCartSrl)
		{
			$oCartArgs->cart_srl = $nCartSrl;
			$oRst = executeQuery('svcart.updateCartItem', $oCartArgs);
			if(!$oRst->toBool()) 
				return $oRst;
		}
		return new BaseObject();
	}
/**
 * @brief
 */
	private function _validateCartAuthority()
	{
		$nMaxQty = 123456789; // set maximum sentinel
		$nExistingCartQty = 0;
		$oSvcartModel = &getModel('svcart');
		$oConfig = $oSvcartModel->getModuleConfig();
		if($oConfig->group_policy_toggle == 'on')
		{
			$logged_info = Context::get('logged_info');
			if(!$logged_info)
			{
				$logged_info->group_list[0] = 'guest';
				$logged_info->member_srl = 0;
			}
			foreach($logged_info->group_list as $key => $val)
			{
				if(isset($oConfig->group_cart_policy[$key]))
				{
					$nTempMaxQty = $oConfig->group_cart_policy[$key];
					if($nMaxQty > $nTempMaxQty)
						$nMaxQty = $nTempMaxQty;
				}
			}
			// check already existing carted item if member
			$aExistingCartList = null;
			if($logged_info->member_srl == 0)
			{
				if($_COOKIE['non_key'])
					$aExistingCartList = $oSvcartModel->getGuestCartInfo($_COOKIE['non_key']);
			}
			else if($logged_info->member_srl > 0)
				$aExistingCartList = $oSvcartModel->getMemberCartInfo($logged_info->member_srl);
			
			$nExistingCartQty = 0;
			$aExistingCartQtyInfo = array();
			foreach($aExistingCartList->item_list as $key => $val)
			{
				$nExistingCartQty += $val->quantity;
				$aExistingCartQtyInfo[$val->cart_srl] = $val->quantity;
			}
		}
        $oRst = new stdClass();
		$oRst->nExistingCartQty = $nExistingCartQty;
		$oRst->aExistingCartQtyInfo = $aExistingCartQtyInfo;
		$oRst->nMaxQty = $nMaxQty;
		return $oRst;
	}
/**
 * @brief
 */
	private function _addItemsToCart(&$in_args)
	{
		$oSvcartModel = &getModel('svcart');
		$config = $oSvcartModel->getModuleConfig();
		$logged_info = Context::get('logged_info');
		$cart_srl = $this->getCartSrl();
        $args = new stdClass();
		$args->cart_srl = $cart_srl;
		$args->module = $in_args->module;
		$args->item_srl = $in_args->item_srl;
		$args->member_srl = $in_args->member_srl;
		if(!$args->member_srl && $logged_info) 
			$args->member_srl = $logged_info->member_srl;

		$args->module_srl = $in_args->module_srl;
		$args->quantity = $in_args->quantity;
		$args->price = $in_args->price;
		$args->option_srl = $in_args->option_srl;
		$args->option_price = $in_args->option_price;
		$args->option_title = $in_args->option_title;
		$args->discount_amount = $in_args->discount_amount;
		$args->discount_info = serialize( $in_args->discount_info );
		$args->discounted_price = $in_args->discounted_price;
		$args->conditional_promotion = serialize( $in_args->conditional_promotion );
		if(strlen($in_args->bundling_order_info) > 0)
			$args->bundling_order_info = $in_args->bundling_order_info;

		if(!$logged_info)
		{
			if(!$_COOKIE['non_key'])
			{
				$args->non_key = $this->_getKey(); 
				setCookie('non_key', $args->non_key);
			}
			else
				$args->non_key = $_COOKIE['non_key']; 
		}
		//$args->non_key = $in_args->non_key;
		$output = executeQuery('svcart.insertCartItem', $args);
		if(!$output->toBool())
			return $output;
		unset($args);
		$retobj = new BaseObject();
		$retobj->add('cart_srl', $cart_srl);
		return $retobj;
	}
/**
 * @brief
 */
	private function _getKey()
	{
		$randval = rand(100000, 999999);
		$usec = explode(" ", microtime());
		$str_usec = str_replace(".", "", strval($usec[0]));
		$str_usec = substr($str_usec, 0, 6);
		return date("YmdHis") . $str_usec . $randval;
	}
}
/* End of file svcart.controller.php */
/* Location: ./modules/svcart/svcart.controller.php */