<?php
/**
 * about login token
 */
class TLoginToken extends TIProtocol
{
	function __construct()
	{
		parent::__construct();
	}
	
	// ==========================================================================
	// 主程式
	/**
	 * 判斷 token 是否為同
	 * @param $ikey
	 * @param $itoken
	 *
	 * @return bool
	 */
	public function IsLoginToken($ikey, $itoken)
	{
		$_token = $this->m_Redis->get($ikey);
		return ($_token != "" && $_token == $itoken)?true:false;
	}

	/**
	 * 更新 token 存活時間
	 * @param $ikey
	 * @param $itoken
	 */
	public function UPDateLoginTokenTime($ikey, $itoken)
	{
		$this->m_Redis->setex($ikey, TOKEN_TIMES, $itoken);
	}

	/**
	 * 儲存token
	 * @param $ikey
	 * @param $itoken
	 */
	public function SaveLoginToken($ikey, $itoken)
	{
		$this->m_Redis->setex($ikey, TOKEN_TIMES, $itoken);
	}

	/**
	 * 取得Login Token
	 * @param $ikey
	 *
	 * @return login token [string]
	 */
	public function GetLoginToken($ikey)
	{
		return md5('logintoken_'.$ikey.'_'.time());
	}
	
}
