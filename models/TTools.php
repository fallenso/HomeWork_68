<?php
class TTools {
	
	// 去除陣列空白 且重排元素
	public static function DelArrayNull($iArray)
	{
		$_output = [];
	
		// 有不能重複聲明 delspace 的問題
		/*
			function delspace($val) {
			return ($val == '' || $val == null)?false:true;
			}
			$tmp = array_filter($iArray, 'delspace');
		*/
		//
		foreach($iArray as $val)
		{
			if ($val != '' && $val != null)
			{
				array_push($_output, $val);
			}
		}
	
		//
		return $_output;
	}
	
	// 長度 轉成 百位
	public static function LenTurnFloot($iLen)
	{
		$_d = '1';
		for($i = 0; $i<$iLen; ++$i)
		{
			$_d .= '0';
		}
		return $_d;
	}
	
	/**
	 * 模糊比對 是否存在
	 * @param $iParent
	 * @param $iStr
	 * 
	 * @return bool : true:is exist
	 */ 
	public static function IsExist($iParent, $iStr)
	{
		$_comparison = strpos($iParent, $iStr);
		return (strlen($_comparison) == 0)?false:true;
	}
	
	/**
	 * 數值補位數
	 * str_pad(string,length,pad_string,pad_type)
	 * string		必需。规定要填充的字符串。
	 * length		必需。规定新字符串的长度。如果该值小于原始字符串的长度，则不进行任何操作。
	 * pad_string	可选。规定供填充使用的字符串。默认是空白。
	 * pad_type		可选。规定填充字符串的那边。

		可能的值：

		STR_PAD_BOTH - 填充到字符串的两头。如果不是偶数，则右侧获得额外的填充。
		STR_PAD_LEFT - 填充到字符串的左侧。
		STR_PAD_RIGHT - 填充到字符串的右侧。这是默认的。
	 * 
	 */
	
}