<?php
/**
 * @class  svcartController
 * @author singleview(root@singleview.co.kr)
 * @brief  svcartController
 */
require_once(_XE_PATH_.'modules/svcart/svcart.view.php');
class svcartMobile extends svcartView
{
/**
 * @brief 
 **/
	function init()
	{
		$template_path = sprintf("%sm.skins/%s/",$this->module_path, $this->module_info->mskin);
		if(!is_dir($template_path)||!$this->module_info->mskin) 
		{
			$this->module_info->mskin = 'default';
			$template_path = sprintf("%sm.skins/%s/",$this->module_path, $this->module_info->mskin);
		}
		$this->setTemplatePath($template_path);
		Context::addJsFile('common/js/jquery.min.js');
		Context::addJsFile('common/js/xe.min.js');

		$logged_info = Context::get('logged_info');

		if($logged_info)
			Context::set('login_chk','Y');
		else if(!$logged_info)
			Context::set('login_chk','N');

		Context::set('hide_trolley', 'true');
	}
}
/* End of file svcart.mobile.php */
/* Location: ./modules/svcart/svcart.mobile.php */