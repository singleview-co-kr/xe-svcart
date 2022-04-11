<?php
/**
 * @class  svcartNpay
 * @author singleview(root@singleview.co.kr)
 * @brief  svcartNpay class
 */
class svcartNpayItem
{ 
	private $id;
	private $name;
	private $tprice;
	private $uprice;
	private $option; //option이 여러 종류라면, 선택된 옵션을 슬래시(/)로 구분해서 표시하는 것을 권장한다.
	private $count;
	private $image;
	private $thumb;
	private $url;
	private $_g_bTestServerMode = true;
/**
 * @brief 
 */	
	public function svcartNpayItem()
	{
	}
/**
 * @brief 
 */	
	public function makeQueryStringBuy($oArg) 
	{
		if( is_object($oArg) )
		{
			$this->_setAttributes($oArg);
			$ret .= 'ITEM_ID=' . urlencode($this->id);
			$ret .= '&EC_MALL_PID=' . urlencode($this->id); // 네이버 쇼핑과 동시 입점하면 필수 전송해야 함
			$ret .= '&ITEM_NAME=' . urlencode($this->name);
			$ret .= '&ITEM_COUNT=' . $this->count;
			$ret .= '&ITEM_OPTION=' . urlencode($this->option);
			$ret .= '&ITEM_TPRICE=' . $this->tprice * $this->count;
			$ret .= '&ITEM_UPRICE=' . $this->uprice;
		}
		return $ret;
	}
/**
 * @brief 
 */	
	public function makeQueryStringWish($oArg) 
	{
		if( is_object($oArg) )
		{
			$this->_setAttributes($oArg);
			$ret .= 'ITEM_ID=' . urlencode($this->id);
			$ret .= '&ITEM_NAME=' . urlencode($this->name);
			$ret .= '&ITEM_UPRICE=' . $this->uprice;
			$ret .= '&ITEM_IMAGE=' . urlencode($this->image);
			$ret .= '&ITEM_THUMB=' . urlencode($this->thumb);
			$ret .= '&ITEM_URL=' . urlencode($this->url);
		}
		return $ret;
	}
/**
 * @brief npay 운영 서버로 전환
 */	
	public function releaseNpayServer()
	{
		$this->_g_bTestServerMode = false;
	}
/**
 * @brief 
 */	
	public function sendMsgToNpayServer($sQueryString)
	{
		$oRst = new BaseObject();
		$sTestServerPrefix = '';
		if( $this->_g_bTestServerMode )
			$sTestServerPrefix = 'test-';

		//$req_addr = 'ssl://test-pay.naver.com';
		$req_addr = 'ssl://'.$sTestServerPrefix .'pay.naver.com';
		$req_url = 'POST /customer/api/wishlist.nhn HTTP/1.1'; // utf-8
		// $req_url = 'POST /customer/api/CP949/wishlist.nhn HTTP/1.1'; // euc-kr
		//$req_host = 'test-pay.naver.com';
		$req_host = $sTestServerPrefix.'pay.naver.com';
		$req_port = 443;
var_dump( $req_host );
var_dump( $req_port );
var_dump( $errno );
var_dump( $errstr );
var_dump( $sQueryString );
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
var_dump( $header);
				if($header=="\r\n")
					break;
				else 
					$headers .= $header;
			}

			// get body
			while(!feof($nc_sock))
			{
				$bodys.=fgets($nc_sock,4096);
			}
			fclose($nc_sock);
			$resultCode = substr($headers,9,3);

			if($resultCode == 200) // success
			{

				// 한개일경우
				//$itemId = $bodys;
				// 여러개일경우
				//$itemIds = trim($bodys);
				//$itemIdList = split(",",$itemIds);
			} 
			else // fail
			{
				//echo $bodys;
				$oRst->setError(-1);
				$oRst->setMessage('fails');
			}
			$oRst->add('bodys', trim($bodys));
		}
		else //에러처리
		{
			$this->add("page", Context::get('page'));
			//$this->setMessage($msg_code);
			echo "$errstr ($errno)<br>\n";
			exit(-1);
		}
	}
/**
 * @brief 
 */	
	private function _setAttributes($oArg) 
	{
		foreach ($oArg as $sIdx => $sVal)
			$this->{$sIdx} = $sVal;
	}
};
/* End of file svcart.npay_item.php */
/* Location: ./modules/svcart/svcart.npay_item.php */