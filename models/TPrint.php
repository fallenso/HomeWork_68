<?php
/**
 * 單純把特製化的列印 提出來
 */

class TPrint  extends TIProtocol {
	
	function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * 特製列印
	 * @param unknown $idata : 要印的資料
	 */
	public static function PSmall($idata)
	{
		$_TPlayAward = new TPlayAward();
		
		$_dataSort = [];
		foreach ($idata as $val)
		{
			$_tmp = [];
			
			// 號碼紀錄
			if ($val['sprule'] != '')
			{
				$_sar = explode("x", $val['num']);
				$_tmp['num'] = $_sar[0].'雙星連碰'.$_sar[1];	// 號碼
			}
			else
			{
				$_tmp['num'] = $val['num'];	// 號碼
			}
							
			$_tmp['o_play'] = $val['play']; // 原始玩法紀錄
			$_tmp['sprule'] = $val['sprule']; // 紀錄是否為特殊玩法
			$_tmp['play'] = self::DePlay($val['play'], $val['num'], $val['sprule']);	// 玩法顯示
			
			$_tmp['price'] = $val['price'];	// 本金價格
			$_tmp['bet'] =	 $val['bet']; // 
			
			$_tmp['2s'] = 0;	// 單價
			$_tmp['3s'] = 0;	// 單價
			$_tmp['4s'] = 0;	// 單價
			
			switch($val['play'])	// 玩法分析
			{
				case PLAY_SP:	$_tmp['2s'] = $val['bet'] / 100;break;
				case PLAY_CAR:	$_tmp['2s'] = $val['bet'] / $val['fews'] / 100;break;
				case PLAY_ST2:	$_tmp['2s'] = $val['bet'] / $val['fews'] / 100;break;
				case PLAY_ST3:	$_tmp['3s'] = $val['bet'] / $val['fews'] / 100;break;
				case PLAY_ST4:	$_tmp['4s'] = $val['bet'] / $val['fews'] / 100;break;
				case PLAY_TW:	$_tmp['2s'] = $val['bet'] / 100;break;
				case PLAY_PN2:	$_tmp['2s'] = $val['bet'] / $val['fews'] / 100;break;
				case PLAY_PN3:	$_tmp['3s'] = $val['bet'] / $val['fews'] / 100;break;
				case PLAY_TWS:	$_tmp['2s'] = $val['bet'] / 100;break;
			}
			
			//
			array_push($_dataSort, $_tmp);
		}
						
		// 234 星 整合
		$_dstr = [];
		foreach($_dataSort as $val)
		{
			$_tmp = [];
			$_tmp['num'] = $val['num'];	// 號碼
			$_tmp['o_play'] = $val['o_play']; // 原始玩法紀錄
			$_tmp['sprule'] = $val['sprule']; // 紀錄是否為特殊玩法
			$_tmp['play'] = $val['play'];	// 玩法顯示
			$_tmp['price'] = $val['price'];	// 本金價格
			$_tmp['bet'] =	 $val['bet']; //
			$_tmp['2s'] = 0;	// 下注金額
			$_tmp['3s'] = 0;	// 下注金額
			$_tmp['4s'] = 0;	// 下注金額
						
			//
			if ($val['sprule'] == "" && ( $val['o_play'] == PLAY_ST2 || $val['o_play'] == PLAY_ST3 || $val['o_play'] == PLAY_ST4))
			{
				
				// 判斷 是否已在陣列內 且 本金 相同
				$_has = false;
				foreach ($_dstr as $k)
				{
					if ($val['num'] == $k['num'] && $val['price'] == $k['price'])
					{
						if ($k['sprule'] == "" && ( $k['o_play'] == PLAY_ST2 || $k['o_play'] == PLAY_ST3 || $k['o_play'] == PLAY_ST4))
						{
							$_has = true;
						}
					}
				}				
				
				if ($_has == false)
				{
					// 開始搜尋
					foreach($_dataSort as $val2)
					{
						if ($val['num'] == $val2['num'] && $val2['sprule'] == "" && $val['price'] == $val2['price'])
						{
							
							switch($val2['o_play'])	// 玩法分析
							{
								case PLAY_ST2:	$_tmp['2s'] += $val2['2s'];break;
								case PLAY_ST3:	$_tmp['3s'] += $val2['3s'];break;
								case PLAY_ST4:	$_tmp['4s'] += $val2['4s'];break;
							}
						}
					}
					
					//
					array_push($_dstr, $_tmp);
				}
			}
			else 
			{
				array_push($_dstr, $val);
			}
		}
				
		// 天碰整合
		$_return = [];
		foreach($_dstr as $val)
		{
			$_tmp = [];
			$_tmp['num'] = $val['num'];	// 號碼
			$_tmp['o_play'] = $val['o_play']; // 原始玩法紀錄
			$_tmp['sprule'] = $val['sprule']; // 紀錄是否為特殊玩法
			$_tmp['play'] = $val['play'];	// 玩法顯示
			$_tmp['price'] = $val['price'];	// 本金價格
			$_tmp['bet'] =	 $val['bet']; //
			$_tmp['2s'] = 0;	// 下注金額
			$_tmp['3s'] = 0;	// 下注金額
			$_tmp['4s'] = 0;	// 下注金額
			
			if ($val['o_play'] == PLAY_PN2 || $val['o_play'] == PLAY_PN3)
			{
				// 判斷 是否已在陣列內  且 本金 相同
				$_has = false;
				foreach ($_return as $k)
				{
					if ($val['num'] == $k['num'] && $val['price'] == $k['price'])
					{
						if ($k['o_play'] == PLAY_PN2 || $k['o_play'] == PLAY_PN3)
						{
							$_has = true;
						}
					}
				}
				
				if ($_has == false)
				{
					// 開始搜尋
					foreach($_dstr as $val2)
					{
						if ($val['num'] == $val2['num'] && $val['price'] == $val2['price'])
						{
								
							switch($val2['o_play'])	// 玩法分析
							{
								case PLAY_PN2:	$_tmp['2s'] += $val2['2s'];break;
								case PLAY_PN3:	$_tmp['3s'] += $val2['3s'];break;
							}
						}
					}
						
					//
					array_push($_return, $_tmp);
				}
		
			}
			else
			{
				array_push($_return, $val);
			}
		}
				
		return $_return;
	}

	// ========================================
	
	private function IsExist($itmp, $iMon)
	{
		foreach($iMon as $val)
		{
			// 確認號碼 / 玩法 (2/3/4) 天碰(2/3) 全車 特瑪 4種
			if ($val['num'] == $itmp['num'])
			{
				if ($val['o_play'] == PLAY_SP)
				{
					if ($itmp['num'] == PLAY_SP)
					{
						return $val;
					}
				}
				else if ($val['o_play'] == PLAY_CAR)
				{
					
				}
				else if ($val['o_play'] == PLAY_ST2 || $val['o_play'] == PLAY_ST3 || $val['o_play'] == PLAY_ST4 )
				{
					if ($itmp['o_play'] == PLAY_ST2 || $itmp['o_play'] == PLAY_ST3 || $itmp['o_play'] == PLAY_ST4 )
					{
						return $val;
					}
				}
				else if ($val['o_play'] == PLAY_PN2  || $val['o_play'] == PLAY_PN3)
				{
					if ($itmp['o_play'] == PLAY_PN2 || $itmp['o_play'] == PLAY_PN3 )
					{
						return $val;
					}	
				}
			}
		}
		return false;
	}
	
	// 回傳玩法文字
	private static function DePlay($iPlay, $iNum, $iSpPlay = '')
	{
		switch($iPlay)
		{
			case PLAY_SP:	return '特碼';
			case PLAY_CAR:	return '專車';
			case PLAY_ST2:
					
				$_comparison = strpos($iNum, 'x');
				if (strlen($_comparison) == 0)
				{
					return '連碰';
				}
				else
				{
					return '柱碰';
				}
		
				break;
			case PLAY_ST3:
		
				if ($iSpPlay != '')
				{
					if ($iSpPlay == 'IsStarStraightPong')
					{
						return '三星-連柱碰';
					}
				}
				else 
				{
					$_comparison = strpos($iNum, 'x');
					if (strlen($_comparison) == 0)
					{
						return '連碰';
					}
					else
					{
						return '柱碰';
					}
				}
		
				break;
			case PLAY_ST4:
		
				$_comparison = strpos($iNum, 'x');
				if (strlen($_comparison) == 0)
				{
					return  '連碰';
				}
				else
				{
					return  '柱碰';
				}
		
				break;
			case PLAY_TW:	return  '台號';
			case PLAY_PN2:	return '天碰-柱碰';
			case PLAY_PN3:	return '天碰-柱碰';
			case PLAY_TWS:	return '特尾三';
		}
	}
	
	
}