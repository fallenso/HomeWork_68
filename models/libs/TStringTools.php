<?php
/**
 * 字串處理相關工具
 * @author Fallen
 * @version 1.0
 */
class TStringTools
{

	/**
	 * 檢查是否全為英數
	 * @param $istr : 字串
	 *
	 * @return bool : true:沒有英數; false: 有英數
	 */
	public static function IsFullBritishNumber($istr)
	{
		return (ctype_alnum($istr)) ? true : false;
	}

	/**
	 * 清除空白
	 * @param $istr : 字串
	 *
	 * @return string
	 */
	 public static function RemovingBlank($istr)
	 {
	 	$_str = trim($istr);
	 	#$_str = preg_replace('/\s(?=)/', '', $_str);
	 	// 去除各種空白 定義 含 html
	 	$_str = preg_replace("/(\s|\&nbsp\;|　|\xc2\xa0)/", "", strip_tags($_str));
	 	return $_str;
	 }
		 
	 
}
