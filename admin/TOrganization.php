<?php
/**
 * 組織
 */
class TOrganization extends TIProtocol  {
	
	private $m_PageParam; 	// 傳給頁面的參數
	
	// ==========================================================================
	// 首頁
	function __construct()
	{
		parent::__construct();
	}
	
	//
	public function Main($iData) 
	{
		$this->m_data = $iData;
		
		// 動作執行
		$_act = (isset($_POST['active']))?$_POST['active']:'DefaultPage';
		$this->$_act();
	}
	
	// ==========================================================================
	//
	
	/**
	 * 列表資料顯示
	 * @param $iSubCode : 開啟指定頁面代碼
	 */
	private function ShowList($iSubCode = '')
	{
		
		if ($iSubCode == -1 || $iSubCode == "")
		{
			// 撈取子帳號 和 自己本身
			$_sql = 'select
							uid, account, name, credit_limit, state, father, competence
						from user
						where father=?';
			$this->m_Read->exec($_sql, $this->m_data->uid);
			$_ret = $this->m_Read->fetchAll();
		}
		else
		{
			
			// 撈取指定權限 的 帳號
			$_sql = 'select
							uid, account, name, credit_limit, state, father, competence
						from user
						where competence=?';
			$this->m_Read->exec($_sql, $iSubCode);
			$_ret = $this->m_Read->fetchAll();
			
			// 過濾 和自己相關的帳戶
			$_TUser = new TUser();
			$_userlist = $_TUser->FindSubAccAll($this->m_data->uid);
			$_TUser = null;
			$_userlist = explode(",", $_userlist);
			
			$_tmp = [];
			foreach ($_ret as $val)
			{
				if (in_array($val['uid'], $_userlist))
				{
					array_push($_tmp, $val);
				}
			}
			//
			$_ret = $_tmp;
			$_tmp = null;
		}
		
		//
		$_list = $this->GetListInfo($_ret);
		$this->m_PageParam['list'] = $_list;
	}
	
	/**
	 * 基本頁面
	 */
	private function ShowBasic()
	{
		// 設定要帶的參數
		$this->m_PageParam['token'] = TSecurity::encrypt(json_encode($this->m_data));
		$this->m_PageParam['page'] = "TOrganization";
		$this->m_PageParam['competence'] = $this->m_data->competence;
		
		//
		TViewBase::Main('TOrganization/TOrganization', $this->m_PageParam);
	}
	
	// ==========================================================================
	// 頁面動作
	
	/**
	 * 預設頁面
	 */
	private function DefaultPage()
	{
		$this->ShowList();
		$this->ShowBasic();
	}
		
	/**
	 * 尋找指定 玩家
	 */
	private function ToFind()
	{
		$_find = $_POST['name'];
		
		// 撈取指定的帳號
		$_sql = 'select
							uid, account, name, credit_limit, state, father, competence
						from user
						where (account=? or name=?) and competence<?';
		$this->m_Read->exec($_sql, $_find, $_find, $this->m_data->competence);
		$_ret = $this->m_Read->fetchAll();
		
		$_list = $this->GetListInfo($_ret);
		$this->m_PageParam['list'] = $_list;
		// 顯示頁面
		$this->ShowBasic();
	}
	
	/**
	 * 創建新玩家
	 */
	private function ToCreate($iSubID = null)
	{
		$_fatherid = $this->m_data->uid;
		if ($iSubID != null)
		{
			$_fatherid = $iSubID;
		}
		
		$_TUser = new TUser();
		
		//
		if (isset($_POST['complete']))
		{
			$_create_father = $_POST['create_father'];
			
			// -------------------------------
			// 接收參數
			$_param = array();
			
			$ipassword = TSecurity::Sha1Encrypt($_POST['password']);
			
			// 基本參數			
			$_param["father"] = $_create_father; // 父帳號
			
			$_param["account"] = $_POST['account']; // 帳號
			$_param["password"] = $ipassword; // 密碼
			$_param["name"] = $_POST['name']; // 名稱
			$_param["competence"] = $_POST['setcompetence']; // 權限
			$_param["money"] = $_POST['money']; // 總額度
			$_param["pay_type"] = $_POST['pay_type']; // 付款方式
			
			// 判斷 參數設定 為 新建 或複製其他帳號
			if ($_POST['setrule'] == "new")
			{
				// 新建參數					
				// 0: 六合彩 (lottery); 1: 大樂透 (lotto); 2: 539 (five)
				for($i = 0; $i <3; ++$i)
				{
					$_type = "";
					if ($i == 0) $_type = "lottery";
					if ($i == 1) $_type = "lotto";
					if ($i == 2) $_type = "five";
			
					// 0: 佔成; 1: 退水; 2: 單筆上限; 3: 單號上限; 4: 丟公司 這邊不設定 5:單邊
					$_temp = array();
					for($j=0; $j<6; ++$j)
					{
						$_ret = $this->GetSetValue($_type, $j);
						
						// 檢查是否有小於0
						foreach ($_ret as $k)
						{
							if ($k != '' && $k < 0)
							{
								echo '<script>alert("設定的參數不可小於0");history.go(-1);</script>';
								return;
							}
						}
						
						array_push($_temp, $_ret);
					}
					
					//
					$_param[$_type] = $_temp;
				}				
			}
			else if ($_POST['setrule'] == "copy")
			{
				// 複製其他帳號的設定
					
				// - 直接輸入要複製的帳號
				$_account = '';
				if ($_POST['copy_account'] != '')
				{
					$_account = $_POST['copy_account'];
				}
				else
				{
					$_account = $_POST['select_account'];
					$_account = explode("/", $_account);
					$_account = $_account[0];
				}
			
				// 取得uid
				$_sql = 'select uid from user where account=?';
				$this->m_Read->exec($_sql, $_account);
				$_uid = $this->m_Read->fetch();
				$_uid = $_uid['uid'];
				
				// 撈取該帳號資料
				$_data = $_TUser->GetUserInfor($_uid);
					
				// 公用參數 ::二星、三星、四星 散單是否限制單邊上限 ＊六合、大樂、539通用設定
				$_param["single_limit"] = $_data["single_limit"];
				//
				$_param["lottery"] = $_data["lottery"];
				$_param["lotto"] = $_data["lotto"];
				$_param["five"] = $_data["five"];
			}
			
			//
			$_ret = $_TUser->CreateUser($_param);
			
			if ($_ret != 1)
			{
				if ($_ret == 2)		$_error_code = 200002;
				else $_error_code = 200001;
				//
				$_msg = '帳號建立失敗. 失敗代碼:'.$_error_code;
			}else $_msg = '帳號建立成功';
			//
			echo '<script>alert("'.$_msg.'");</script>';
			$this->ShowList();
			$this->ShowBasic();
		}
		else 
		{
			// 開啟建立帳號頁面
			
			// 撈取子帳號列表
			$_sql = "select account, name from user where father=?";
			$this->m_Read->exec($_sql, $_fatherid);
			$_ret = $this->m_Read->fetchAll();
				
			$_list = array();
			foreach ($_ret as $val)
			{
				array_push($_list, $val['account'].'/'.$val['name']);
			}
						
			// 撈取帳號 積累本金資料 
			$_TBetPrice = new TBetPrice();
			$_cost = [];
			for($i=0; $i<3; ++$i)
			{
				for($k=1; $k<=9; ++$k)
				{
					$_val = $_TBetPrice->GetAllPrice($i, $_fatherid, $k, true);
					$_cost[$i][$k] = $_val;
				}
			}
						
			//
			$_limit = $_TUser->GetUserInfor($_fatherid);
			
			// 設定要帶的參數
			$this->m_PageParam['token'] = TSecurity::encrypt(json_encode($this->m_data));
			$this->m_PageParam['page'] = "TOrganization";
			$this->m_PageParam['active'] = 'ToCreate';
			$this->m_PageParam['select_account_list'] = $_list;
			$this->m_PageParam['cost'] = $_cost;
			$this->m_PageParam['limit'] = $_limit;
			$this->m_PageParam['create_father'] = $_fatherid;
			
			// 新增下層 特製處理
			if (isset($this->m_PageParam['Creatby']) == true)
			{
				// 取得目標的權限
				$this->m_PageParam['competence'] = $_TUser->GetUserCompetence($_fatherid);
			}
			else 
			{
				$this->m_PageParam['competence'] = $this->m_data->competence;
			}
			
			TViewBase::Main('TOrganization/CreatePage', $this->m_PageParam);
		}
		//
		$_TUser = null;
	}
	
	/**
	 * 開啟 指定子頁面
	 */
	private function SubPage()
	{
		$_sub = $_POST['name'];
		$this->ShowList($_sub);
		$this->ShowBasic();
	}
	
	/**
	 * 新增下層
	 */
	private function CreateBy()
	{
		$this->m_PageParam['Creatby'] = $_POST['name'];
		self::ToCreate($_POST['name']);
	}
	
	/**
	 * 整組移桶
	 */
	 private function MoveBy()
	 {
	 	$_TUser = new TUser(); 	
	 	
	 	if (isset($_POST['complete']))
	 	{
	 		
	 		$_uid = $_POST['moveby'];
	 		$_newfather = ($_POST['SelectAccount'] == '')?$_POST['accmenu']:$_POST['SelectAccount'];
	 		
	 		// 找出新父帳號 uid
	 		$_fuid = $_TUser->GetUidByAccount($_newfather);
	 		
	 		// 更換父帳
	 		$_ret = $_TUser->ChangeFather($_uid, $_fuid['uid']);
	 		
	 		$_msg = ($_ret)?'更新成功':'更新失敗';
	 		echo '<script>alert("'.$_msg.'");</script>';
	 		
	 		//
	 		$this->ShowList();
	 		$this->ShowBasic();
	 	}
	 	else 
	 	{
	 		// 被移的領頭羊
	 		$this->m_PageParam['moveby'] = $_POST['name']; // 其實他是uid xd
	 		$_data = $_TUser->GetUserName($_POST['name']);
	 		$this->m_PageParam['account'] = $_data['account'];
	 		$this->m_PageParam['name'] = $_data['name'];
	 		$this->m_PageParam['competence'] = $_TUser->GetUserCompetence($_POST['name']);
	 		
	 		// 查父帳
	 		$_fuid = $_TUser->GetFatherUid($_POST['name']);
	 		$_data = $_TUser->GetUserName($_fuid);
	 		$this->m_PageParam['faccount'] = $_data['account'];
	 		$this->m_PageParam['fname'] = $_data['name'];
	 		$_fcompetence = $_TUser->GetUserCompetence($_fuid);
	 			 		
	 		// 取得父帳號列表
	 		$_list = $_TUser->GetCompetenceList($_fcompetence);
	 		
	 		$_acclist = [];
	 		foreach($_list as $val)
	 		{
	 			// 去除 本來父帳
	 			if ($val != $_fuid)
	 			{
		 			$_tmp = [];
	 				$_data = $_TUser->GetUserName($val);
			 		$_tmp['uid'] = $val;
			 		$_tmp['account'] = $_data['account'];
			 		$_tmp['name'] = $_data['name'];
			 		$_tmp['competence'] = $_fcompetence;
			 			
			 		array_push($_acclist, $_tmp);
	 			}
	 		}
	 		
	 		//
	 		$this->m_PageParam['list'] = $_acclist;
	 		
	 		// 設定要帶的參數
	 		$this->m_PageParam['token'] = TSecurity::encrypt(json_encode($this->m_data));
	 		$this->m_PageParam['page'] = "TOrganization";
	 		$this->m_PageParam['active'] = 'MoveBy';
	 		
	 		TViewBase::Main('TOrganization/MoveBy', $this->m_PageParam);
	 	}
	 	
	 	$_TUser = null;
	 }
	
	/**
	 * 對指定玩家 進行設定
	 */
	private function ToSet()
	{
		//
		if (isset($_POST['complete']))
		{
			$_uid = $_POST['uid'];
			// -------------------------------
			// 接收參數
			$_param = array();
		
			// 基本參數
			$_param["uid"] = $_uid;
			$_param["password"] = $_POST['password']; // 密碼
			$_param["name"] = $_POST['name']; // 名稱
			$_param["money"] = $_POST['money']; // 總額度
			$_param["spend_credit"] = $_POST['spend_credit']; // 目前消費額度 (付費制適用)
			$_param["pay_type"] = $_POST['pay_type']; // 付款方式
			$_param["competence"] = $_POST['setcompetence']; // 權限
														
			// 0: 六合彩 (lottery); 1: 大樂透 (lotto); 2: 539 (five)
			for($i = 0; $i <3; ++$i)
			{
				$_type = "";
				if ($i == 0) $_type = "lottery";
				if ($i == 1) $_type = "lotto";
				if ($i == 2) $_type = "five";
					
				// 0: 佔成; 1: 退水; 2: 單筆上限; 3: 單號上限; 4: 丟公司 5:單邊
				$_temp = array();
				for($j=0; $j<6; ++$j)
				{
					$_ret = $this->GetSetValue($_type, $j);
					
					// 檢查是否有小於0
					foreach ($_ret as $k)
					{
						if ($k != '' && $k < 0)
						{
							echo '<script>alert("設定的參數不可小於0");history.go(-1);</script>';
							return;
						}
					}
					
					array_push($_temp, $_ret);
				}
					
				//
				$_param[$_type] = $_temp;
			}
							
			//
			$_TUser = new TUser();
			$_ret = $_TUser->UPDateParam($_param);
			$_TUser = null;
			if ($_ret != 1)
			{
				if ($_ret == 2)		$_error_code = 200002;
				else $_error_code = 200001;
				//
				TLogs::log("TOrganization-ToSet", $_error_code);
			}else echo "<script>alert('資料修改成功！');</script>";
			//
			$this->ShowList();
			$this->ShowBasic();
		}
		else
		{
			$_uid = $_POST['name'];
			
			// 撈取帳號 積累本金資料
			$_TBetPrice = new TBetPrice();
			$_cost = [];
			for($i=0; $i<3; ++$i)
			{
				for($k=1; $k<=9; ++$k)
				{
					$_val = $_TBetPrice->GetAllPrice($i, $_uid, $k);
					$_cost[$i][$k] = $_val;
				}
			}
			$_TBetPrice = null;
						
			// 撈取帳號資料
			$_TUser = new TUser();
			$_data = $_TUser->GetUserInfor($_uid);
			$_limit = $_TUser->GetUserInfor($_TUser->GetFatherUid($_uid));
			$_TUser = null;
												
			// 設定要帶的參數
			$_param = [
					'token'=>TSecurity::encrypt(json_encode($this->m_data)),
					'page'=>"TOrganization",
					'active'=>'ToSet',
					'cost'=>$_cost,
					'data'=>$_data,
					'limit'=>$_limit,
					'competence'=>$this->m_data->competence,
					'f_competence'=>$_limit['competence'],
			];
			TViewBase::Main('TOrganization/SetPage', $_param);
		}
	}
	
	/**
	 * 對指定玩家 進行 帳號啟動
	 */
	private function ToOn()
	{
		$_TUser = new TUser();
		$_ret = $_TUser->UpdateState($_POST['name'], TUser::STATE_ON);
		if (!$_ret)
		{
			echo "<script>alert('帳號啟用失敗！');</script>";
		}
		//
		$this->ShowList();
		$this->ShowBasic();
	}
	
	/**
	 * 對指定玩家 進行 帳號 限制
	 */
	private function ToLimit()
	{
		$_TUser = new TUser();
		$_ret = $_TUser->UpdateState($_POST['name'], TUser::STATE_LIMIT);
		if (!$_ret)
		{
			echo "<script>alert('帳號停押失敗！');</script>";
		}
		//
		$this->ShowList();
		$this->ShowBasic();
	}
	
	/**
	 * 對指定玩家 進行 帳號 停用
	 */
	private function ToStop()
	{
		$_TUser = new TUser();
		$_ret = $_TUser->UpdateState($_POST['name'], TUser::STATE_STOP);
		if (!$_ret)
		{
			echo "<script>alert('帳號停用失敗！');</script>";
		}
		//
		$this->ShowList();
		$this->ShowBasic();
	}
	
	/**
	 * 對指定玩家 進行 帳號 刪除
	 */
	private function ToDel()
	{
		$_uid = $_POST['name'];
		
		// 取得刪除帳號的清單
		$_TUser = new TUser();
		$_list = $_TUser->FindSubAccAll($_uid);
		
		// 帳號轉陣列
		$_list = explode(",", $_list);
		
		/*
		 * 檢查 : 有未結的單 不可刪除
		 */
		// 未結帳的期 有哪些
		$_TAwardControl = new TAwardControl();
		$_idlist = $_TAwardControl->GetNoOutAward();
		$_TAwardControl = null;
		
		// 撈取未結帳的期數 的 各帳號 下注單號
		$_TBilling = new TBilling();
		foreach ($_idlist as $val)
		{
			foreach ($_list as $_val_userid)
			{
				$_data = $_TBilling->GetBetByUser($val, $_val_userid);
				if (count($_data) != 0)
				{
					echo '<script>alert("有未結帳的單，無法刪除該帳號。");</script>';
					$this->ShowList();
					$this->ShowBasic();
					return false;
				}
			}
		}
		
		// 開始交易
		$ret = $this->m_Writer->beginTransaction();
		if (false == $ret) {
		
			$_msg = "beginTransaction fail! error code: ".$this->m_Writer->errorCode()."<br>\n";
			TLogs::log("TOrganization-del", $_msg);
			TLogs::log("TOrganization-del", 200003);
			$this->m_Writer->rollBack();
			$this->ShowList();
			$this->ShowBasic();
			return false;
		}
		
		foreach ($_list as $_val)
		{
			$_ret = $_TUser->DelUser($_val);
			if (!$_ret)
			{
				TLogs::log("TOrganization-del", "del user is fail.");
				TLogs::log("TOrganization-del", 200003);
				$this->m_Writer->rollBack();
				$this->ShowList();
				$this->ShowBasic();
				return false;
			}
		}
		
		// 交易完成
		$this->m_Writer->commit();
		$_TUser = null;
		//
		echo "<script>alert('".$_uid."帳號及所屬子帳號均已刪除成功！');</script>";
		//
		$this->ShowList();
		$this->ShowBasic();
	}
	
	// =====================================================
	
	/**
	 * 撈取列表的顯示資訊
	 * @param $_iUserList : USER LIST
	 *
	 * @return array {
	 * 		uid：玩家id
	 * 		account : 玩家帳號
	 * 		name : 暱稱
	 * 		credit_limit : 額度
	 * 		state : 狀態
	 * 		father : 上層帳號 id
	 * 		fatherName : 上層帳號暱稱
	 * 		competence : 權限
	 * 		isslave : 是否為登入者的下層帳號
	 * 		lottery : 六合彩的佔成資料
	 * 		lotto : 大樂透的佔成資料
	 * 		five : 539的佔成資料
	 * }
	 */
	private function GetListInfo($_iUserList)
	{
		$_TUser = new TUser();
		$_list = array();
		foreach($_iUserList as $val)
		{
			$_temp = array();
	
			// 撈取 父帳號資訊
			$_sql = "select account, name from user where uid=?";
			$this->m_Read->exec($_sql, $val['father']);
			$_fatherName = $this->m_Read->fetch();
	
			// 撈取 設定資訊
			$_lottery = $_TUser->GetAcctSub($val['uid'], 0, 0);
			$_lotto = $_TUser->GetAcctSub($val['uid'], 1, 0);
			$_five = $_TUser->GetAcctSub($val['uid'], 2, 0);
				
			//
			$_temp['uid'] = $val['uid'];
			$_temp['account'] = $val['account'];
			$_temp['name'] = $val['name'];
			$_temp['credit_limit'] = $val['credit_limit'];
			$_temp['state'] = $val['state'];
			$_temp['father'] = $_fatherName['account'];
			$_temp['fatherName'] = $_fatherName['name'];
			$_temp['competence'] = $val['competence'];
				
			// 判斷是否為子屬
			if ($val['father'] == $this->m_data->uid)
			{
				$_temp['issub'] = true;
			}else $_temp['issub'] = false;
	
			$_temp['lottery'] = $_lottery;
			$_temp['lotto'] = $_lotto;
			$_temp['five'] = $_five;
	
			//
			array_push($_list, $_temp);
		}
		//
		$_TUser = null;
		return $_list;
	}
	
	/**
	 * 取得 創建/設定 參數的值
	 * @param $iType : default - lottery: 六合彩; lotto: 大樂透; five: 539
	 * @param $iAttributes : default - 0: 佔成; 1: 退水; 2: 單筆上限; 3: 單號上限; 4: 丟公司 5:單邊上限(可收金額設定)
	 */
	private function GetSetValue($iType, $iAttributes)
	{
		$_paramName = $iType."_".$iAttributes."_";
	
		$_ret = array();
		$_ret['all_car'] = (isset($_POST[$_paramName.'all_car']))?$_POST[$_paramName.'all_car']:0;// 全車
		$_ret['sp'] = (isset($_POST[$_paramName.'sp']))?$_POST[$_paramName.'sp']:0; // 特碼
		$_ret['tw'] = (isset($_POST[$_paramName.'tw']))?$_POST[$_paramName.'tw']:0; // 台號
		$_ret['tawisan'] = (isset($_POST[$_paramName.'tawisan']))?$_POST[$_paramName.'tawisan']:0; // 特尾三
		$_ret['star_2'] = (isset($_POST[$_paramName.'star_2']))?$_POST[$_paramName.'star_2']:0; // 二星
		$_ret['star_3'] = (isset($_POST[$_paramName.'star_3']))?$_POST[$_paramName.'star_3']:0; // 三星
		$_ret['star_4'] = (isset($_POST[$_paramName.'star_4']))?$_POST[$_paramName.'star_4']:0; // 四星
		$_ret['pong_2'] = (isset($_POST[$_paramName.'pong_2']))?$_POST[$_paramName.'pong_2']:0; // 天碰二
		$_ret['pong_3'] = (isset($_POST[$_paramName.'pong_3']))?$_POST[$_paramName.'pong_3']:0; // 天碰三
		//
		return $_ret;
	}
}
