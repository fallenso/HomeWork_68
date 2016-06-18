<?php
/**
 * Marquee
 */
class TMarquee extends TIProtocol 
{
	
	function __construct()
	{
		parent::__construct();
	}
	
	public function Main()
	{
		// 撈取跑馬燈資料
		$_sql = "select context from marquee where state=1";
		$this->m_Read->exec($_sql);
		$_ret = $this->m_Read->fetchAll();
		
		$_msg = array();
		foreach($_ret as $val)
		{
			array_push($_msg, $val['context']);
		}
		//
		$_marquee = '';
		foreach($_msg as $val)
		{
			($_marquee=='')?$_marquee.= '●'.$val:
			$_marquee.= '&nbsp;&nbsp;&nbsp;&nbsp;●'.$val;
		}
		//
		return $_marquee;
	}
	
}