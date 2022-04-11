<?php
/**
 * @class  SvcartAdminModel
 * @author singleview(root@singleview.co.kr)
 * @brief  SvcartAdminModel
 */ 
class svcartAdminModel extends svcart
{
/**
 * @brief 
 **/
	public function getModuleConfig()
	{
		$oModuleModel = &getModel('module');
		return $oModuleModel->getModuleConfig('svcart');
	}
/**
 * @brief 
 **/
	public function getSvcartAdminDeleteModInst()
	{
		$oModuleModel = &getModel('module');
		$module_srl = Context::get('module_srl');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
		Context::set('module_info', $module_info);
		$oTemplate = &TemplateHandler::getInstance();
		$tpl = $oTemplate->compile($this->module_path.'tpl', 'form_delete_modinst');
		$this->add('tpl', str_replace("\n"," ",$tpl));
	}
/**
 * @brief 
 **/
	public function getCartItem($cart_srl) 
	{
        $args = new stdClass();
		$args->cart_srl = $cart_srl;
		$output = executeQuery('svcart.getCartItem', $args);
		if(!$output->toBool() || count((array)$output->data ) == 0 ) 
			return;
		return $output->data;
	}
/**
 * @brief get module instance list
 **/
	public function getModInstList( $nPage = null ) 
	{
		$oArgs = new stdClass();
		$oArgs->sort_index = 'module_srl';
		$oArgs->page = $nPage;
		$oArgs->list_count = 20;
		$oArgs->page_count = 10;
		$oRst = executeQueryArray('svcart.getModInstList', $oArgs);
		return $oRst->data;
	}
}
/* End of file svcart.admin.model.php */
/* Location: ./modules/svcart/svcart.admin.model.php */