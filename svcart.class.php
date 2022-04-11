<?php
/**
 * @class  svcart
 * @author singleview(root@singleview.co.kr)
 * @brief  svcart
 */
class svcart extends ModuleObject 
{
/**
 * @brief 모듈 설치 실행
 **/
	function moduleInstall()
	{
	}
/**
 * @brief 설치가 이상없는지 체크
 **/
	function checkUpdate()
	{
	}
/**
 * @brief 업데이트(업그레이드)
 **/
	/*function moduleUpdate()
	{
		$oDB = &DB::getInstance();

		if(!$oDB->isColumnExists('svcart', 'document_srl'))
			$oDB->addColumn('svcart', 'document_srl', 'number', 11, 0, TRUE);
	}*/
}
/* End of file svcart.class.php */
/* Location: ./modules/svcart/svcart.class.php */