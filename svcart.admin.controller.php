<?php
/**
 * @class  svcartAdminController
 * @author singleview(root@singleview.co.kr)
 * @brief  svcartAdminController
 */
class svcartAdminController extends svcart
{
/**
 * @brief 
 **/
	public function init() 
	{
	}
/**
 * @brief 모듈 환경설정값 쓰기
 **/
	public function procSvcartAdminConfig() 
	{
		$oMemberModel = &getModel('member');
		$aMemberGroup = $oMemberModel->getGroups();
		$oGuest = new stdClass();
		$oGuest->group_srl = 0;
		$aMemberGroup[0] = $oGuest;
		ksort($aMemberGroup);
		$aGroupCartPolicy = array();
		foreach( $aMemberGroup as $key=>$val )
		{
			if( Context::get('group_qty_'.$val->group_srl) )
				$aGroupCartPolicy[$val->group_srl] = (int)Context::get('group_qty_'.$val->group_srl);
		}

		$nCartExpirationDays = Context::get('cart_expiration_days');
		$oArgs = new stdClass();
		if( isset( $nCartExpirationDays ) )
			$oArgs->cart_expiration_days = $nCartExpirationDays;
		
		$oArgs->group_cart_policy = $aGroupCartPolicy;
		$oArgs->group_policy_toggle = trim( Context::get('group_policy_toggle') );
		// save module configuration.
		$oModuleControll = getController('module');
		$output = $oModuleControll->insertModuleConfig('svcart',$oArgs );
		$this->setMessage('success_updated');

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) 
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispSvcartAdminConfig','module_srl',Context::get('module_srl'));
			$this->setRedirectUrl($returnUrl);
			return;
		}
	}
/**
 * @brief NPay 설정 쓰기
 **/
	public function procSvcartAdminNpayConfig() 
	{
		$oArgs = Context::getRequestVars();
		$oArgs->npay_mert_key = trim($oArgs->npay_mert_key);
		$oArgs->npay_btn_key = trim($oArgs->npay_btn_key);
		$oRst = $this->_saveModuleConfig($oArgs);
		if(!$oRst->toBool())
			$this->setMessage( 'error_occured' );
		else
			$this->setMessage( 'success_updated' );

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) 
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispSvcartAdminNpayConfig','module_srl',Context::get('module_srl'));
			$this->setRedirectUrl($returnUrl);
		}
	}
/**
 * @brief 모듈 환경설정값 쓰기
 **/
	public function procSvcartAdminInsertModInst() 
	{
		// module 모듈의 model/controller 객체 생성
		$oModuleController = &getController('module');
		$oModuleModel = &getModel('module');

		// 게시판 모듈의 정보 설정
		$args = Context::getRequestVars();
		$args->module = 'svcart';

		// module_srl이 넘어오면 원 모듈이 있는지 확인
		if($args->module_srl) 
		{
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($args->module_srl);
			if($module_info->module_srl != $args->module_srl)
				unset($args->module_srl);
		}

		// module_srl의 값에 따라 insert/update
		if(!$args->module_srl) 
		{
			$output = $oModuleController->insertModule($args);
			$msg_code = 'success_registed';
		}
		else
		{
			$output = $oModuleController->updateModule($args);
			$msg_code = 'success_updated';
		}

		if(!$output->toBool())
			return $output;

		$this->add('module_srl',$output->get('module_srl'));
		$this->setMessage($msg_code);
		$returnUrl = getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispSvcartAdminInsertModInst','module_srl',$this->get('module_srl'));
		$this->setRedirectUrl($returnUrl);
	}
/**
 * @brief 
 **/
	public function procSvcartAdminDeleteModInst() 
	{
		$module_srl = Context::get('module_srl');
		$oModuleController = &getController('module');
		$output = $oModuleController->deleteModule($module_srl);
		if(!$output->toBool())
			return $output;

		$this->add('module','svcart');
		$this->add('page',Context::get('page'));
		$this->setMessage('success_deleted');
		$returnUrl = getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispSvcartAdminModInstList');
		$this->setRedirectUrl($returnUrl);
	}
/**
 * @brief arrange and save module config
 **/
	private function _saveModuleConfig($oArgs)
	{
		$oSvcartAdminModel = &getAdminModel('svcart');
		$oConfig = $oSvcartAdminModel->getModuleConfig();
		if(is_null($oConfig))
			$oConfig = new stdClass();
		foreach($oArgs as $key=>$val)
			$oConfig->{$key} = $val;

		$oModuleControll = getController('module');
		$output = $oModuleControll->insertModuleConfig('svcart', $oConfig);
		return $output;
	}
}
/* End of file svcart.admin.controller.php */
/* Location: ./modules/svcart/svcart.admin.controller.php */