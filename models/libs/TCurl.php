<?php

/**
 * curl
 */
class TCurl
{

	public function main($iurl, $iisPost = true, $iparm = '')
	{
		$ch = curl_init($iurl);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		//post資料給指定網頁
		if ($iisPost == true)
		{
			curl_setopt($ch, CURLOPT_POST, $iisPost);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $iparm);
		}

		// 以下二行為 https時 規避ssl的驗證
		#curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch,CURLOPT_FOLLOWLOCATION,true);	//是否抓取轉址。輸入的網址就會顯示在這個頁面上
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);	//將curl_exec()獲取的訊息以文件流的形式返回，而不是直接輸出。

		$output = curl_exec($ch);
		curl_close($ch);
		//
		return $output;
	}

}
