<?php
/**
 * 核心套件 :: 紀錄存方log訊息
 * @author Fallen
 * @version 1.0
 *
 * 本套件 為繼承用基底套件 繼承後透過 $this 來取用系統套件
 * @example: TLogs::log(類別名稱-執行的方法名稱, '要記錄的訊息');
 */
class TLogs
{

	/**
	 *
	 * @param string $iclassname : 類別名稱
	 * @param string $ierror: 要記錄的訊息
	 * @example: TLogs::log('類別名稱', '要記錄的訊息');
	 */
	public static function log($iClassname, $iError)
	{
		$imsg = TError::GetError($iError);
		//
		$_msg = '['.date('Y-m-d H:i:s').'] ['.$iClassname."] \r\n";
		$_msg.=	'來源ip為:'.$_SERVER["REMOTE_ADDR"]."\r\n";
		$_msg.= '訊息:'.$imsg."\r\n";

		$_error_msg = (ENVIRONMENT != PRODUCTION)?TError::GetError($iError, TError::TYPE_SYS):TError::GetError($iError);
		echo "<script>alert('".$_error_msg."');</script>";
		$_log = '['.date('Y-m-d H:i:s').'] ['.$iClassname."] \r\n".$iClassname.'-'.$_error_msg."\r\n";
		
		//
		$filepath = "../logs/".ENVIRONMENT."_".date("Y-m-d").".log";
		file_put_contents($filepath, $_log, FILE_APPEND);
	}
}
