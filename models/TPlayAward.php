<?php
/**
 * 中獎計算
 */
class TPlayAward  extends TIProtocol {
	
	function __construct()
	{
		parent::__construct();
	}
	
	// ==================================================
	//
	
	/**
	 * 計算 2/3/4 星 不兌獎 有多少組
	 * @param $iGame
	 * @param $iBetNun : 下注號碼內容 (1,2x3,4...)
	 * @param $iType : 玩法
	 * @param $iSpplay : 特殊玩法名稱
	 * 
	 * @return 
	 */
	public function GetCombNums($iGame, $iBetNun, $iType, $iSpplay = '')
	{
		// 拆組
		$_group = self::DeStrGroup($iBetNun);
				
		// 計算多少組
		$_num = 0;
		if (count($_group) > 1)
		{
			if ($iType == PLAY_ST2)
			{
				// 柱碰
				$_tg = self::ColBonbNum($_group);
				$_num = self::Star2Col($_tg);
				return $_num;
			}
			else if ($iType == PLAY_ST3)
			{
				// 柱碰 :: 是否為連柱碰
				if ($iSpplay == 'IsStarStraightPong')
				{
					$_num = self::Comb(count($_group[0]), 2) * self::Comb(count($_group[1]), 1);
				}
				else 
				{
					$_tg = self::ColBonbNum($_group);
					$_num = self::Star3Col($_tg);
				}
				return $_num;
			}
			else if ($iType == PLAY_ST4)
			{
				// 柱碰
				$_tg = self::ColBonbNum($_group);
				$_num = self::Star4Col($_tg);
				return $_num;
			}
			else if ($iType == PLAY_PN2)
			{
				$_first = count($_group[0]);
				unset($_group[0]);
				// 計算普通號 中幾個組別
	
				$_num = 0;
				foreach ($_group as $val)
				{
					$_num += $_first * count($val);
				}
				//
				return $_num;
			}
			else if ($iType == PLAY_PN3)
			{
				$_first = count($_group[0]);
				unset($_group[0]);
				
				if (count($_group) > 1)
				{
					// 柱碰
					$_tg = self::ColBonbNum($_group);
					$_num = $_first * self::Star2Col($_tg);
					return $_num;
				}
				else 
				{		
					$_num = $_first * self::Comb(count($_group[1]), 2);					
					return $_num;
				}
			}
			else return 0;
		}
		else if (count($_group) == 1)
		{
			
			// 連碰
			if ($iType == PLAY_SP)
			{
				
				return 1;
			}
			else if ($iType == PLAY_CAR)
			{
				if ($iGame == Lottery)
				{
					return 48;
				}
				else if ($iGame == Lotto)
				{
					return 48;
				}
				else return 38;
			}
			else if ($iType == PLAY_ST2)
			{
				$_num = count($_group[0]);
				if ($_num <= 1)
				{
					return 0;
				}else 
				{
					return self::Comb($_num, 2);
				}
			}
			else if ($iType == PLAY_ST3)
			{
				$_num = count($_group[0]);
				if ($_num <= 2)
				{
					return 0;
				}
				else
				{
					return self::Comb($_num, 3);
				}
			}
			else if ($iType == PLAY_ST4)
			{
				$_num = count($_group[0]);
				if ($_num <= 2)
				{
					return 0;
				}
				else
				{
					return self::Comb($_num, 4);
				}
			}
			else if ($iType == PLAY_TW)
			{
				return 1;
			}
			else if ($iType == PLAY_PN2)
			{
				return 0;
			}
			else if ($iType == PLAY_PN3)
			{
				return 0;
			}
			else if ($iType == PLAY_TWS)
			{
				return 1;
			}
			else return 0;
			
		}else return 0; // 沒中
	}
	
	
	/**
	 * 全車
	 * @param $iBet :　下注單 [billing 全格式]
	 * @param $iAward : 中獎獎號 整理過的資料
	 * 
	 * @return int 中幾支　＞　全車固定為1
	 */ 
	public function all_car($iBet, $iAward)
	{		
		// 全車的號碼
		$_car =  $iBet['num'];
		
		// 比對開獎號碼　看有沒有中
		$_num = 0;
		if ($_car == $iAward['num_1'] ||
			$_car == $iAward['num_2'] ||
			$_car == $iAward['num_3'] ||
			$_car == $iAward['num_4'] ||
			$_car == $iAward['num_5'] ||
			$_car == $iAward['num_6']
		)
		{
			if ($iBet['type'] == Lottery || $iBet['type'] == Lotto )
			{
				return 5;	// 5碰
			}
			else 
			{
				return 4;	// 539 - 4碰
			}
		}
		//
		return 0;
	}
	
	/**
	 * 特碼
	 * @param $iBet :　下注單 [billing 全格式]
	 * @param $iAward : 中獎獎號 整理過的資料
	 *
	 * @return int 中幾支　＞　特碼固定為１
	 */
	public function sp($iBet, $iAward)
	{
		// 全車的號碼
		$_sp =  $iBet['num'];
		
		// 比對開獎號碼　看有沒有中
		if ($_sp == $iAward['num_sp'])
		{
			return 1;
		}
		//
		return 0;
	}
	
	/**
	 * 台號
	 * @param $iBet :　下注單 [billing 全格式]
	 * @param $iAward : 中獎獎號 整理過的資料
	 *
	 * @return int 中幾支　＞　台號固定為１
	 */
	public function tw($iBet, $iAward)
	{
		// 簽的號碼
		$_tw =  $iBet['num'];
		
		// 比對開獎號碼　看有沒有中
		if ($_tw == $iAward['t_1'] ||
			$_tw == $iAward['t_2'] ||
			$_tw == $iAward['t_3'] ||
			$_tw == $iAward['t_4'] ||
			$_tw == $iAward['t_5'] 
		)
		{
			return 1;
		}
		//
		return 0;
	}
	
	/**
	 * 特尾三
	 * @param $iBet :　下注單 [billing 全格式]
	 * @param $iAward : 中獎獎號 整理過的資料
	 *
	 * @return int 中幾支　＞　特尾三固定為１
	 */
	public function tawisan($iBet, $iAward)
	{
		// 簽的號碼
		$_tawisan =  $iBet['num'];
		
		// 比對開獎號碼　看有沒有中
		if ($_tawisan == $iAward['tw3'] )
		{
			return 1;
		}
		//
		return 0;
	}
	
	/**
	 * 二星
	 * @param $iBet :　下注單 [billing 全格式]
	 * @param $iAward : 中獎獎號 整理過的資料
	 *
	 * @return int 中幾支
	 */
	public function star_2($iBet, $iAward)
	{
		// 簽的號碼
		$_num =  $iBet['num'];

		// 拆組
		$_group = self::DeStrGroup($_num);
		
		// 判斷 是否為 539 :5個號碼
		$_award = array();
		if ($iBet['type'] == 2)
		{
			array_push($_award, $iAward['num_1']);
			array_push($_award, $iAward['num_2']);
			array_push($_award, $iAward['num_3']);
			array_push($_award, $iAward['num_4']);
			array_push($_award, $iAward['num_5']);
		}
		else 
		{
			array_push($_award, $iAward['num_1']);
			array_push($_award, $iAward['num_2']);
			array_push($_award, $iAward['num_3']);
			array_push($_award, $iAward['num_4']);
			array_push($_award, $iAward['num_5']);
			array_push($_award, $iAward['num_6']);
		}

		// 整理出中獎的 號碼群組
		$_group = self::GetPingo($_group, $_award);
		$_return['group'] = $_group;
		
		// 計算中多少組
		$_num = 0;
		if (count($_group) > 1)
		{
			// 柱碰
			$_tg = self::ColBonbNum($_group);			
			$_num = self::Star2Col($_tg);
			
			$_return['fews'] = $_num;
			#return $_num;
		}
		else if (count($_group) == 1) 
		{
			// 連碰
			$_num = count($_group[0]);
			if ($_num <= 1)
			{
				$_return['fews'] = 0;
				#return 0;
			}
			else 
			{
				$_return['fews'] = self::Comb($_num, 2);
				#return self::Comb($_num, 2);
			}
		}
		else 
		{
			// 沒中
			#return 0;
			$_return['fews'] = 0;
		}
		return $_return;
	}
	
	/**
	 * 三星
	 * @param $iBet :　下注單 [billing 全格式]
	 * @param $iAward : 中獎獎號 整理過的資料
	 *
	 * @return int 中幾支　
	 */
	public function star_3($iBet, $iAward)
	{
		// 簽的號碼
		$_num =  $iBet['num'];

		// 拆組
		$_group = self::DeStrGroup($_num);
		
		// 判斷 是否為 539 :5個號碼
		$_award = array();
		if ($iBet['type'] == 2)
		{
			array_push($_award, $iAward['num_1']);
			array_push($_award, $iAward['num_2']);
			array_push($_award, $iAward['num_3']);
			array_push($_award, $iAward['num_4']);
			array_push($_award, $iAward['num_5']);
		}
		else 
		{
			array_push($_award, $iAward['num_1']);
			array_push($_award, $iAward['num_2']);
			array_push($_award, $iAward['num_3']);
			array_push($_award, $iAward['num_4']);
			array_push($_award, $iAward['num_5']);
			array_push($_award, $iAward['num_6']);
		}

		// 整理出中獎的 號碼群組
		$_group = self::GetPingo($_group, $_award);
		$_return['group'] = $_group;
		
		// 計算中多少組
		$_num = 0;
		if (count($_group) > 1)
		{
			// 柱碰
			$_tg = self::ColBonbNum($_group);				
			$_num = self::Star3Col($_tg);
			$_return['fews'] = $_num;
			#return $_num;
		}
		else if (count($_group) == 1) 
		{
			// 連碰
			$_num = count($_group[0]);
			if ($_num <= 2)
			{
				$_return['fews'] = 0;
				#return 0;
			}
			else 
			{
				$_return['fews'] = self::Comb($_num, 3);
				#return self::Comb($_num, 3);
			}
		}
		else 
		{
			// 沒中
			$_return['fews'] = 0;
			#return 0;
		}
		
		return $_return;
	}
	
	/**
	 * 四星
	 * @param $iBet :　下注單 [billing 全格式]
	 * @param $iAward : 中獎獎號 整理過的資料
	 *
	 * @return int 中幾支　
	 */
	public function star_4($iBet, $iAward)
	{
	// 簽的號碼
		$_num =  $iBet['num'];

		// 拆組
		$_group = self::DeStrGroup($_num);
		
		// 判斷 是否為 539 :5個號碼
		$_award = array();
		if ($iBet['type'] == 2)
		{
			array_push($_award, $iAward['num_1']);
			array_push($_award, $iAward['num_2']);
			array_push($_award, $iAward['num_3']);
			array_push($_award, $iAward['num_4']);
			array_push($_award, $iAward['num_5']);
		}
		else 
		{
			array_push($_award, $iAward['num_1']);
			array_push($_award, $iAward['num_2']);
			array_push($_award, $iAward['num_3']);
			array_push($_award, $iAward['num_4']);
			array_push($_award, $iAward['num_5']);
			array_push($_award, $iAward['num_6']);
		}

		// 整理出中獎的 號碼群組
		$_group = self::GetPingo($_group, $_award);
		$_return['group'] = $_group;
		// 計算中多少組
		$_num = 0;
		if (count($_group) > 1)
		{
			// 柱碰
			$_tg = self::ColBonbNum($_group);			
			$_num = self::Star4Col($_tg);
			$_return['fews'] = $_num;
			#return $_num;
		}
		else if (count($_group) == 1) 
		{
			// 連碰
			$_num = count($_group[0]);
			if ($_num <= 3)
			{
				$_return['fews'] = 0;
				#return 0;
			}
			else 
			{
				$_return['fews'] = self::Comb($_num, 4);
				#return self::Comb($_num, 4);
			}
		}
		else 
		{
			// 沒中
			$_return['fews'] = 0;
			#return 0;
		}
		return $_return;
	}
	
	/**
	 * 三星特殊玩法 ::連柱碰
	 * @param $iBet :　下注單 [billing 全格式]
	 * @param $iAward : 中獎獎號 整理過的資料
	 */
	public function Star3StraightPong($iBet, $iAward)
	{
		$_return['group'] = '';	// 返回中獎牌組
		$_return['fews'] = 0;	// 返回中的隻數
		$_num =  $iBet['num'];	// 簽的號碼
		
		// 拆組
		$_group = self::DeStrGroup($_num);
		
		// 判斷 是否為 539 :5個號碼
		$_award = array();
		if ($iBet['type'] == 2)
		{
			array_push($_award, $iAward['num_1']);
			array_push($_award, $iAward['num_2']);
			array_push($_award, $iAward['num_3']);
			array_push($_award, $iAward['num_4']);
			array_push($_award, $iAward['num_5']);
		}
		else
		{
			array_push($_award, $iAward['num_1']);
			array_push($_award, $iAward['num_2']);
			array_push($_award, $iAward['num_3']);
			array_push($_award, $iAward['num_4']);
			array_push($_award, $iAward['num_5']);
			array_push($_award, $iAward['num_6']);
		}
		
		// 整理出中獎的 號碼群組
		$_group = self::GetPingo($_group, $_award);
		$_return['group'] = $_group;
		
		// 計算中幾隻跟中的號碼
		$_return['fews'] = self::Comb(count($_group[0]), 2) * self::Comb(count($_group[1]), 1);
				
		//
		return $_return;
	}
	
	
	/**
	 * 天碰二
	 * @param $iBet :　下注單 [billing 全格式]
	 * @param $iAward : 中獎獎號 整理過的資料
	 *
	 * @return int 中幾支　
	 */
	public function pong_2($iBet, $iAward)
	{
		// 簽的號碼
		$_num =  $iBet['num'];

		// 拆組
		$_group = self::DeStrGroup($_num);

		// 先檢查 第一組 是否有中 特瑪
		$_ret = (in_array($iAward['num_sp'], $_group[0]));
		// 去除 第一組(特瑪組)
		unset($_group[0]);
				
		// 檢查是否有中特瑪
		if($_ret == true)
		{
			// 檢查後面是否有中
			$_award = array();
			array_push($_award, $iAward['num_1']);
			array_push($_award, $iAward['num_2']);
			array_push($_award, $iAward['num_3']);
			array_push($_award, $iAward['num_4']);
			array_push($_award, $iAward['num_5']);
			array_push($_award, $iAward['num_6']);
			$_group_ot = self::GetPingo($_group, $_award);
			$_return['group'] = $_group_ot;
			// 計算普通號 中幾個組別
			$_num = 0;
			foreach ($_group_ot as $val)
			{
				$_num += count($val);
			}
			//
			$_return['fews'] = $_num;
			#return $_num;
		}
		else 
		{
			$_return['fews'] = 0;
			#return 0;
		}
		
		$_return['group'] = $_group_ot;
		return $_return;
	}
	
	/**
	 * 天碰三
	 * @param $iBet :　下注單 [billing 全格式]
	 * @param $iAward : 中獎獎號 整理過的資料
	 *
	 * @return int 中幾支　
	 */
	public function pong_3($iBet, $iAward)
	{
		// 簽的號碼
		$_num =  $iBet['num'];

		// 拆組
		$_group = self::DeStrGroup($_num);
		
		// 先檢查 第一組 是否有中 特瑪
		$_ret = (in_array($iAward['num_sp'], $_group[0]));
		// 去除 第一組(特瑪組)
		unset($_group[0]);
		
		// 檢查是否有中特瑪
		if($_ret == true)
		{
			// 檢查後面是否有中
			$_award = array();
			array_push($_award, $iAward['num_1']);
			array_push($_award, $iAward['num_2']);
			array_push($_award, $iAward['num_3']);
			array_push($_award, $iAward['num_4']);
			array_push($_award, $iAward['num_5']);
			array_push($_award, $iAward['num_6']);

			// 整理出中獎的 號碼群組
			$_group = self::GetPingo($_group, $_award);
			$_return['group'] = $_group;
			// 計算中多少組
			$_num = 0;
			
			if (count($_group) > 1)
			{
				// 柱碰
				$_tg = self::ColBonbNum($_group);
				$_num = self::Star2Col($_tg);
				$_return['fews'] = $_num;
				#return $_num;
			}
			else if (count($_group) == 1)
			{
				return count($_group);
			}
		}else $_return['fews'] = 0; #return 0;
		
		return $_return;
	}
	
	// ================================================
	/**
	 * 計算柱碰 有幾組
	 */
	public function	ColBonbNum($iGroup)
	{
		// 柱碰 : 計算每組 的c幾取幾 為多少 Cn取1
		$_tmp = array();
		foreach ($iGroup as $val)
		{
			
			$_c = self::Comb(count($val), 1);
			array_push($_tmp, $_c);
		}
		//
		return $_tmp;
	}
		
	/**
	 * 組合字串 拆解成 組合數值陣列
	 * 1. 每柱 之間使用 x 來區隔
	   2. 同柱間的號碼使用 , 來區隔
		num_1,num_2 x num_1,num_2,…
	 */
	private function DeStrGroup($iStr)
	{
		// 先拆組 [x]
		$_gStr = explode("x", $iStr);
		
		// 每組 再拆 成 各數值
		$_group = array();
		foreach ($_gStr as $val)
		{
			$_math = explode(",", $val);
			$_tmp = [];
			foreach ($_math as $m)
			{
				if ($m != '')
				{
					array_push($_tmp, $m);
				}
			}
			//
			array_push($_group, $_tmp);
		}
		//
		return $_group;
	}

	/**
	 * 碰 整理相關中獎號碼組
	 * @param $iGroup : 玩家下注群組號碼
	 * @param $iAward : 中獎號碼
	 */
	private function GetPingo($iGroup, $iAward)
	{		
		$_tgroup = array();
		foreach ($iGroup as $val)
		{
			$_tma = array();
			foreach ($val as $math)
			{
				if (in_array($math, $iAward) == true)
				{
					array_push($_tma, $math);
				}
			}
			//
			if (count($_tma) != 0 )
			{
				array_push($_tgroup, $_tma);
			}
		}
		//
		return $_tgroup;
	}
	
	/**
	 * 計算2星的連住碰 贏多少隻
	 * @param $iAr : 陣列數組 : ex: [2,1,3,4]
	 */
	public function Star2Col($iAr)
	{
		$_sum = 0;
		for($i=0; $i<count($iAr)-1; ++$i)
		{
			$_sum += self::Star2Col_2for($iAr, $i);
		}
		//
		return $_sum;
	}
	
	// 2星多柱組數 計算
	public function Star2Col_2for($iAr, $iN)
	{
		$_root = $iAr[$iN];
		$_sum = 0;
		for($i=$iN+1; $i<count($iAr); ++$i)
		{
			$_sum += $_root * $iAr[$i];
		}
		//
		return $_sum;
	}
	
	/**
	 * 計算3星的連住碰 贏多少隻
	 * @param $iAr : 陣列數組 : ex: [2,1,3,4]
	 */
	public function Star3Col($iAr)
	{
		$_sum = 0;
		for($i=0; $i<count($iAr)-2; ++$i)
		{
			for($j=$i+1; $j<count($iAr)-1; ++$j)
			{
				for($k=$j+1; $k<count($iAr); ++$k)
				{
					$_sum += $iAr[$i] * $iAr[$j] * $iAr[$k];
				}
			}
		}
		//
		return $_sum;
	}
	
	/**
	 * 計算4星的連住碰 贏多少隻
	 * @param $iAr : 陣列數組 : ex: [2,1,3,4]
	 */
	public function Star4Col($iAr)
	{	
		$_sum = 0;
		for($m=0; $m<count($iAr)-3; ++$m)
		{
			for($i=$m+1; $i<count($iAr)-2; ++$i)
			{
				for($j=$i+1; $j<count($iAr)-1; ++$j)
				{
					for($k=$j+1; $k<count($iAr); ++$k)
					{
						$_sum += $iAr[$m] * $iAr[$i] * $iAr[$j] * $iAr[$k];
					}
				}
			}
		}
		//
		return $_sum;
	}
	
	/**
	 * c幾取幾 演算法
	 */
	private function Comb($n, $m)
	{
		if($n < 1) return 0;
		if ($n == $m) return 1;		
		return self::Stratum($n) / self::Stratum($m) / self::Stratum($n-$m);
	}
	
	/**
	 * 階層計算
	 */
	private function Stratum($n)
	{
		return ($n <= 1)?1:$n * self::Stratum($n-1);
	}
	
	// ================================================
	// 特殊
	
	/**
	 * 取得排列組合
	 * @param $iPlay : 玩法
	 * @param $iSprule : 特殊玩法
	 * @param $iNus [str]
	 */
	public static function GetSort($iPlay, $iSprule, $iNus)
	{
		$_output = [];
	
		if ($iPlay == PLAY_ST2)
		{
			$_output = self::GetSort_St2($iNus);
		}
		else if ($iPlay == PLAY_ST3)
		{
			if ($iSprule == 'IsStarStraightPong')
			{
				$_output = self::GetSort_St3_SP($iNus);
			}
			else
			{
				$_output = self::GetSort_St3($iNus);
			}
		}
		else if ($iPlay == PLAY_ST4)
		{
			if ($iSprule == 'IsStarStraightPong')
			{
				$_output = self::GetSort_St4_SP($iNus);
			}
			else
			{
				$_output = self::GetSort_St4($iNus);
			}
		}
		else if ($iPlay == PLAY_PN2)
		{
			$_output = self::GetSort_Pn2($iNus);
		}
		else if ($iPlay == PLAY_PN3)
		{
			$_output = self::GetSort_Pn3($iNus);
		}
	
		//
		return $_output;
	}
	
	/**
	 * 列出 有哪些排列組合 (二星用)
	 * @param $iNus [str]
	 */
	private static function GetSort_St2($iNus)
	{
		$_output = [];
	
		// 柱碰 檢查
		$_groups = explode("x", $iNus);
		if (count($_groups) == 1)
		{
			// 連碰
			$_nums = explode(",", $iNus);
			for ($i=0; $i<count($_nums)-1; ++$i)
			{
				for ($j=$i+1; $j<count($_nums); ++$j)
				{
					$_key = $_nums[$i].','.$_nums[$j];
					array_push($_output, $_key);
				}
			}
		}
		else
		{
			// 柱碰
			// 陣列化 | 去除 空元素
			$_new_grs = [];
			foreach ($_groups as $val)
			{
				$_tmp = explode(",", $val);
				$_tmp = TTools::DelArrayNull($_tmp);
				array_push($_new_grs, $_tmp);
			}
				
			// 選組
			for ($i=0; $i<count($_new_grs)-1; ++$i)
			{
				for ($j=$i+1; $j<count($_new_grs); ++$j)
				{
					// 選號
					$_nums_1 = $_new_grs[$i];
					$_nums_2 = $_new_grs[$j];
						
					for ($m=0; $m<count($_nums_1); ++$m)
					{
						for ($n=0; $n<count($_nums_2); ++$n)
						{
							$_key = $_nums_1[$m].','.$_nums_2[$n];
							array_push($_output, $_key);
						}
					}
				}
			}
		}
	
		//
		return $_output;
	}
	
	/**
	 * 列出 有哪些排列組合 (三星用)
	 * @param $iNus [str]
	 */
	private static function GetSort_St3($iNus)
	{
		$_output = [];
	
		// 柱碰 檢查
		$_groups = explode("x", $iNus);
		if (count($_groups) == 1)
		{
			// 連碰
			$_nums = explode(",", $iNus);
			for ($i=0; $i<count($_nums)-2; ++$i)
			{
				for ($j=$i+1; $j<count($_nums)-1; ++$j)
				{
					for ($k=$j+1; $k<count($_nums); ++$k)
					{
						$_key = $_nums[$i].','.$_nums[$j].','.$_nums[$k];
						array_push($_output, $_key);
					}
				}
			}
		}
		else
		{
			// 柱碰
			// 陣列化 | 去除 空元素
			$_new_grs = [];
			foreach ($_groups as $val)
			{
				$_tmp = explode(",", $val);
				$_tmp = TTools::DelArrayNull($_tmp);
				array_push($_new_grs, $_tmp);
			}
	
			// 選組
			for ($i=0; $i<count($_new_grs)-2; ++$i)
			{
				for ($j=$i+1; $j<count($_new_grs)-1; ++$j)
				{
					for ($k=$j+1; $k<count($_new_grs); ++$k)
					{
							
						// 選號
						$_nums_1 = $_new_grs[$i];
						$_nums_2 = $_new_grs[$j];
						$_nums_3 = $_new_grs[$k];
							
						for ($m=0; $m<count($_nums_1); ++$m)
						{
							for ($n=0; $n<count($_nums_2); ++$n)
							{
								for ($o=0; $o<count($_nums_3); ++$o)
								{
									$_key = $_nums_1[$m].','.$_nums_2[$n].','.$_nums_3[$o];
									array_push($_output, $_key);
								}
							}
						}
					}
				}
			}
				
		}
		//
		return $_output;
	}
	
	/**
	 * 列出 有哪些排列組合 (四星用)
	 * @param $iNus [str]
	 */
	private static function GetSort_St4($iNus)
	{
		$_output = [];
	
		// 柱碰 檢查
		$_groups = explode("x", $iNus);
		if (count($_groups) == 1)
		{
			// 連碰
			$_nums = explode(",", $iNus);
			for ($i=0; $i<count($_nums)-3; ++$i)
			{
				for ($j=$i+1; $j<count($_nums)-2; ++$j)
				{
					for ($k=$j+1; $k<count($_nums)-1; ++$k)
					{
						for ($m=$k+1; $m<count($_nums); ++$m)
						{
							$_key = $_nums[$i].','.$_nums[$j].','.$_nums[$k].','.$_nums[$m];
							array_push($_output, $_key);
						}
					}
				}
			}
		}
		else
		{
			// 柱碰
			// 陣列化 | 去除 空元素
			$_new_grs = [];
			foreach ($_groups as $val)
			{
				$_tmp = explode(",", $val);
				$_tmp = TTools::DelArrayNull($_tmp);
				array_push($_new_grs, $_tmp);
			}
				
			// 選組
			for ($i=0; $i<count($_new_grs)-3; ++$i)
			{
				for ($j=$i+1; $j<count($_new_grs)-2; ++$j)
				{
					for ($k=$j+1; $k<count($_new_grs)-1; ++$k)
					{
						for ($l=$k+1; $l<count($_new_grs); ++$l)
						{
							// 選號
							$_nums_1 = $_new_grs[$i];
							$_nums_2 = $_new_grs[$j];
							$_nums_3 = $_new_grs[$k];
							$_nums_4 = $_new_grs[$l];
	
							foreach ($_nums_1 as $m)
							{
								foreach ($_nums_2 as $n)
								{
									foreach ($_nums_3 as $o)
									{
										foreach ($_nums_4 as $p)
										{
											$_key = $m.','.$n.','.$o.','.$p;
											array_push($_output, $_key);
										}
									}
								}
							}
						}
					}
				}
			}
		}
		//
		return $_output;
	}
	
	/**
	 * 列出 有哪些排列組合 (三星-連柱碰用)
	 * @param $iNus [str]
	 */
	private static function GetSort_St3_SP($iNus)
	{
		$_output = [];
		//
		$_groups = explode("x", $iNus);
		$_nums_0 = explode(",", $_groups[0]);
		$_nums_1 = explode(",", $_groups[1]);
		$_nums_1 = TTools::DelArrayNull($_nums_1); // , 分隔關係 第一個會是空的
	
		//
		foreach ($_nums_0 as $val)
		{
			for ($i=0; $i<count($_nums_1)-1; ++$i)
			{
				for ($j=$i+1; $j<count($_nums_1); ++$j)
				{
					$_key = $val.','.$_nums_1[$i].','.$_nums_1[$j];
					array_push($_output, $_key);
				}
			}
		}
	
		//
		return $_output;
	}
	
	/**
	 * 列出 有哪些排列組合 (四星-連柱碰用)
	 * @param $iNus [str]
	 */
	private static function GetSort_St4_SP($iNus)
	{
		$_output = [];
		//
		$_groups = explode("x", $iNus);
		$_nums_0 = explode(",", $_groups[0]);
		$_nums_1 = explode(",", $_groups[1]);
		$_nums_1 = TTools::DelArrayNull($_nums_1); // , 分隔關係 第一個會是空的
	
		//
		for ($i=0; $i<count($_nums_0)-1; ++$i)
		{
			for ($j=$i+1; $j<count($_nums_0); ++$j)
			{
				for ($k=0; $k<count($_nums_1)-1; ++$k)
				{
					for ($m=$k+1; $m<count($_nums_1); ++$m)
					{
						$_key = $_nums_0[$i].','.$_nums_0[$j].','.$_nums_1[$k].','.$_nums_1[$m];
						array_push($_output, $_key);
					}
				}
			}
		}
	
		//
		return $_output;
	}
	
	/**
	 * 列出 有哪些排列組合 (天碰二用) [柱碰未判斷]
	 * @param $iNus [str]
	 */
	private static function GetSort_Pn2($iNus)
	{
		$_output = [];
		//
		$_groups = explode("x", $iNus);
	
		// 判斷是否為柱碰
		if (count($_groups) == 2)
		{
			$_nums_0 = explode(",", $_groups[0]);
			$_nums_1 = explode(",", $_groups[1]);
			$_nums_1 = TTools::DelArrayNull($_nums_1); // , 分隔關係 第一個會是空的
				
			for ($i=0; $i<count($_nums_0); ++$i)
			{
				for ($j=0; $j<count($_nums_1); ++$j)
				{
	
					$_key = $_nums_0[$i].','.$_nums_1[$j];
					array_push($_output, $_key);
				}
			}
		}
		else
		{
			// 柱碰
			// 陣列化 | 去除 空元素
			$_new_grs = [];
			foreach ($_groups as $val)
			{
				$_tmp = explode(",", $val);
				$_tmp = TTools::DelArrayNull($_tmp);
				array_push($_new_grs, $_tmp);
			}
				
			// 取出第一柱 (特碼柱)
			$_spnums = $_new_grs[0];
			unset($_new_grs[0]);
				
			foreach($_spnums as $sp_num)
			{
				foreach ($_new_grs as $val)
				{
					// 其他柱搭號
					foreach ($val as $o_num)
					{
						$_key = $sp_num.','.$o_num;
						array_push($_output, $_key);
					}
				}
			}
		}
		//
		return $_output;
	}
	
	/**
	 * 列出 有哪些排列組合 (天碰三用)  [柱碰未判斷]
	 * @param $iNus [str]
	 */
	private static function GetSort_Pn3($iNus)
	{
		$_output = [];
		//
		$_groups = explode("x", $iNus);
	
		// 判斷是否為柱碰
		if (count($_groups) == 2)
		{
			$_nums_0 = explode(",", $_groups[0]);
			$_nums_1 = explode(",", $_groups[1]);
			$_nums_1 = TTools::DelArrayNull($_nums_1); // , 分隔關係 第一個會是空的
				
			foreach ($_nums_0 as $val)
			{
				for ($i=0; $i<count($_nums_1)-1; ++$i)
				{
					for ($j=$i+1; $j<count($_nums_1); ++$j)
					{
						$_key = $val.','.$_nums_1[$i].','.$_nums_1[$j];
						array_push($_output, $_key);
					}
				}
			}
		}
		else
		{
			// 柱碰
				
			// 取出第一柱 (特碼柱)
			$_spnums = explode(",", $_groups[0]);
			unset($_groups[0]);
				
			// 陣列化 | 去除 空元素
			$_new_grs = [];
			foreach ($_groups as $val)
			{
				$_tmp = explode(",", $val);
				$_tmp = TTools::DelArrayNull($_tmp);
				array_push($_new_grs, $_tmp);
			}
				
			foreach($_spnums as $sp_num)
			{
				// 其他柱 - 第一柱
				for ($i=0; $i<count($_new_grs)-1; ++$i)
				{
					// 其他柱 - 第二柱
					for ($j=$i+1; $j<count($_new_grs); ++$j)
					{
						// 其他柱搭號
						foreach ($_new_grs[$i] as $m)
						{
							foreach ($_new_grs[$j] as $n)
							{
								$_key = $sp_num.','.$m.','.$n;
								array_push($_output, $_key);
							}
						}
					}
				}
			}
		}
		//
		return $_output;
	}
}