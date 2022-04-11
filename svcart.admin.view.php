<?php
/**
 * @class  svcartAdminView
 * @author singleview(root@singleview.co.kr)
 * @brief  svcartAdminView
 */ 
class svcartAdminView extends svcart
{
/**
 * @brief Contructor
 **/
	public function init() 
	{
		// module이 svshopmaster일때 관리자 레이아웃으로
		if(Context::get('module') == 'svshopmaster')
		{
			$sClassPath = _XE_PATH_ . 'modules/svshopmaster/svshopmaster.class.php';
			if(file_exists($sClassPath))
			{
				require_once($sClassPath);
				$oSvshopmaster = new svshopmaster;
				$oSvshopmaster->init($this);
			}
		}

		// module_srl이 있으면 미리 체크하여 존재하는 모듈이면 module_info 세팅
		$module_srl = Context::get('module_srl');
		if(!$module_srl && $this->module_srl)
		{
			$module_srl = $this->module_srl;
			Context::set('module_srl', $module_srl);
		}

		$oModuleModel = &getModel('module');

		// module_srl이 넘어오면 해당 모듈의 정보를 미리 구해 놓음
		if($module_srl) 
		{
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
			if(!$module_info) 
			{
				Context::set('module_srl','');
				$this->act = 'list';
			} 
			else 
			{
				ModuleModel::syncModuleToSite($module_info);
				$this->module_info = $module_info;
				Context::set('module_info',$module_info);
			}
		}
		if($module_info && !in_array($module_info->module, array('svcart')))
			return $this->stop("msg_invalid_request");
		
		//if(Context::get('module')=='svshopmaster')
		//{
		//	$this->setLayoutPath('');
		//	$this->setLayoutFile('common_layout');
		//}

		// set template file
		$tpl_path = $this->module_path.'tpl';
		$this->setTemplatePath($tpl_path);
		$this->setTemplateFile('index');
		Context::set('tpl_path', $tpl_path);
	}
/**
 * @brief 
 **/
	public function dispSvcartAdminModInstList() 
	{
		//$args->sort_index = "module_srl";
		//$args->page = Context::get('page');
		//$args->list_count = 20;
		//$args->page_count = 10;
		//$args->s_module_category_srl = Context::get('module_category_srl');
		//$output = executeQueryArray('svcart.getModInstList', $args);
		//$store_list = $output->data;
		$oSvcartAdminModel = &getAdminModel('svcart');
		$aSvcartMid = $oSvcartAdminModel->getModInstList(Context::get('page'));
		$store_list = $aSvcartMid;

		if(!is_array($store_list)) 
			$store_list = array();

		Context::set('store_list', $store_list);
		$oModuleModel = &getModel('module');
		$module_category = $oModuleModel->getModuleCategories();
		Context::set('module_category', $module_category);
		$this->setTemplateFile('modinstlist');
	}
/**
 * @brief 
 **/
	public function dispSvcartAdminConfig() 
	{
		$oSvcartModel = &getModel('svcart');
		$config = $oSvcartModel->getModuleConfig();
		Context::set('config',$config);
		// get groups
		$oMemberModel = &getModel('member');
		$group_list = $oMemberModel->getGroups();
		Context::set('group_list', $group_list);
		
		$this->setTemplateFile('config');
	}
/**
 * @brief 
 **/
	public function dispSvcartAdminNpayConfig()
	{
		$oSvcartModel = &getModel('svcart');
		$oConfig = $oSvcartModel->getModuleConfig();
		Context::set('config', $oConfig);
		$this->setTemplateFile('npay_config');
	}
/**
 * @brief 
 **/
	public function dispSvcartAdminInsertModInst() 
	{
		// 스킨 목록을 구해옴
		$oModuleModel = &getModel('module');
		$skin_list = $oModuleModel->getSkins($this->module_path);
		Context::set('skin_list',$skin_list);

		$mskin_list = $oModuleModel->getSkins($this->module_path, "m.skins");
		Context::set('mskin_list', $mskin_list);

		// 레이아웃 목록을 구해옴
		$oLayoutModel = &getModel('layout');
		$layout_list = $oLayoutModel->getLayoutList();
		Context::set('layout_list', $layout_list);
		$mobile_layout_list = $oLayoutModel->getLayoutList(0,"M");
		Context::set('mlayout_list', $mobile_layout_list);

		// svpg plugin list
		//$oSvpgModel = &getModel('svpg');
		//$oSvPgModules = $oSvpgModel->getSvpgList();
		//Context::set('svpg_modules', $oSvPgModules);

		// svorder linked module list
		$oSvorderAdminModel = &getAdminModel('svorder');
		$oSvorderModules = $oSvorderAdminModel->getMidList();
		Context::set( 'svorder_modules', $oSvorderModules );

		//$oSvcartModel = &getModel('svcart');
		//Context::set('delivery_companies', $oSvcartModel->getDeliveryCompanies());

		$oEditorModel = &getModel('editor');
		$config = $oEditorModel->getEditorConfig(0);
		// 에디터 옵션 변수를 미리 설정
		$option = new stdClass();
		$option->skin = $config->editor_skin;
		$option->content_style = $config->content_style;
		$option->content_font = $config->content_font;
		$option->content_font_size = $config->content_font_size;
		$option->colorset = $config->sel_editor_colorset;
		$option->allow_fileupload = true;
		$option->enable_default_component = true;
		$option->enable_component = true;
		$option->disable_html = false;
		$option->height = 200;
		$option->enable_autosave = false;
		$option->primary_key_name = 'module_srl';
		$option->content_key_name = 'delivery_info';
		$editor = $oEditorModel->getEditor($this->module_info->module_srl, $option);
		Context::set('editor', $editor);

		$module_category = $oModuleModel->getModuleCategories();
		Context::set('module_category', $module_category);

		//////////////////////
		$oModuleAdminModel = &getAdminModel('module');
		$sGrantContent = $oModuleAdminModel->getModuleGrantHTML($this->module_info->module_srl, $this->xml_info->grant);
		Context::set('grant_content', $sGrantContent);
		//////////////////////

		$this->setTemplateFile('insertmodinst');
	}
/**
 * @brief 
 **/
	public function dispSvcartAdminAdditionSetup() 
	{
		// content는 다른 모듈에서 call by reference로 받아오기에 미리 변수 선언만 해 놓음
		$content = '';
		$oEditorView = &getView('editor');
		$oEditorView->triggerDispEditorAdditionSetup($content);
		Context::set('setup_content', $content);
		$this->setTemplateFile('additionsetup');
	}
/**
 * @brief 스킨 정보 보여줌
 **/
	public function dispSvcartAdminSkinInfo() 
	{
		// 공통 모듈 권한 설정 페이지 호출
		$oModuleAdminModel = &getAdminModel('module');
		$skin_content = $oModuleAdminModel->getModuleSkinHTML($this->module_info->module_srl);
		Context::set('skin_content', $skin_content);
		$this->setTemplateFile('skininfo');
	}
/**
 * @brief 스킨 정보 보여줌
 **/
	public function dispSvcartAdminMobileSkinInfo() 
	{
		// 공통 모듈 권한 설정 페이지 호출
		$oModuleAdminModel = &getAdminModel('module');
		$skin_content = $oModuleAdminModel->getModuleMobileSkinHTML($this->module_info->module_srl);
		Context::set('skin_content', $skin_content);
		$this->setTemplateFile('skininfo');
	}
/**
 * @brief 미완료 장바구니 리스트 표시
 **/
	public function dispSvcartAdminCartManagement() 
	{
		if(!Context::get('s_year'))
			Context::set('s_year', date('Y'));
		$args = new stdClass();
		$args->regdate = Context::get('s_year');
	   	if(Context::get('s_month')) 
			$args->regdate = $args->regdate . Context::get('s_month');

		$oSvitemAdminModel = &getAdminModel('svitem');
		$aDisplayingItems = $oSvitemAdminModel->getAllDisplayingItemList();
		$aItemInfoByItemSrl = array();
		foreach( $aDisplayingItems as $nIdx => $oItemVal )
        {
            if(is_null($aItemInfoByItemSrl[$oItemVal->item_srl]))
                $aItemInfoByItemSrl[$oItemVal->item_srl] = new stdClass();
			$aItemInfoByItemSrl[$oItemVal->item_srl]->item_name = $oItemVal->item_name;
        }

		if( !defined(svorder::ORDER_STATE_ON_DEPOSIT) )
			getClass('svorder');
		$args->order_status = svorder::ORDER_STATE_ON_DEPOSIT;
		$args->page = Context::get('page');
		$output = executeQueryArray('svcart.getCartList', $args);
		if(!$output->toBool()) 
			return $output;
		
		$oMemberModel = &getModel('member');
		foreach( $output->data as $key=>$val)
		{
			$val->item_name = $aItemInfoByItemSrl[$val->item_srl]->item_name;
			if( $val->member_srl )
			{
				$oMemberInfo = $oMemberModel->getMemberInfoByMemberSrl($val->member_srl);
				$val->user_id = $oMemberInfo->user_id;
			}
			else
				$val->user_id = '비회원';
		}
		$order_list = $output->data;
		Context::set('list', $order_list);
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('page_navigation', $output->page_navigation);
		$this->setTemplateFile('cart_management');
	}
/**
 * @brief 장바구니 상세 내역 보기
 **/
	public function dispSvcartAdminCartDetail() 
	{
		$oSvcartAdminModel = getAdminModel('svcart');
		$nCartSrl = Context::get('cart_srl');
		$oOrderInfo = $oSvcartAdminModel->getCartItem($nCartSrl);
		Context::set('order_info', $oOrderInfo);
		$this->setTemplateFile('cart_detail');
	}
/**
 * @brief display the grant information
 **/
	public function dispSvcartAdminGrantInfo() 
	{
		// get the grant infotmation from admin module
		$oModuleAdminModel = &getAdminModel('module');
		$sGrantContent = $oModuleAdminModel->getModuleGrantHTML($this->module_info->module_srl, $this->xml_info->grant);
		Context::set('grant_content', $sGrantContent);
		$this->setTemplateFile('grantinfo');
	}
/**
 * @brief 
 **/
	/*function getNewsFromSingleview()
	{
		//Retrieve recent news and set them into context
		$newest_news_url = sprintf("http://singleview.co.kr/?module=newsagency&act=getNewsagencyArticle&inst=notice&top=6&loc=%s", _XE_LOCATION_);
		$cache_file = sprintf("%sfiles/cache/svcart_news.%s.cache.php", _XE_PATH_, _XE_LOCATION_);
		if(!file_exists($cache_file) || filemtime($cache_file)+ 60*60 < time())
		{
			// Considering if data cannot be retrieved due to network problem, modify filemtime to prevent trying to reload again when refreshing textmessageistration page
			// Ensure to access the textmessageistration page even though news cannot be displayed
			FileHandler::writeFile($cache_file,'');
			FileHandler::getRemoteFile($newest_news_url, $cache_file, null, 1, 'GET', 'text/html', array('REQUESTURL'=>getFullUrl('')));
		}

		if(file_exists($cache_file)) 
		{
			$oXml = new XeXmlParser();
			$buff = $oXml->parse(FileHandler::readFile($cache_file));

			$item = $buff->zbxe_news->item;
			if($item) 
			{
				if(!is_array($item)) 
				{
					$item = array($item);
				}

				foreach($item as $key => $val) {
					$obj = null;
					$obj->title = $val->body;
					$obj->date = $val->attrs->date;
					$obj->url = $val->attrs->url;
					$news[] = $obj;
				}
				Context::set('news', $news);
			}
			Context::set('released_version', $buff->zbxe_news->attrs->released_version);
			Context::set('download_link', $buff->zbxe_news->attrs->download_link);
		}
	}*/
}
/* End of file svcart.admin.view.php */
/* Location: ./modules/svcart/svcart.admin.view.php */