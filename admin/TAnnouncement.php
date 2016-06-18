<?php
/**
 * 公告
 */
class TAnnouncement extends TIProtocol  {
	
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
		
		// 
		if (isset($_POST['active']))
		{
			$_active = $_POST['active'];
			$this->$_active();
		}else $this->ShowBasic();
	}
	
	// ==========================================================================
	// 頁面控制
	/**
	 * 基本頁面
	 */
	private function ShowBasic()
	{
		// 撈取資料
		$_sql = ($this->m_data->competence >= 6)?'select * from marquee':
		'select * from marquee where state=1';
		$this->m_Read->exec($_sql);
		$_ret = $this->m_Read->fetchAll();
				
		$_list = array();
		foreach ($_ret as $val)
		{
			$_tmp = array();
			$_tmp['id'] = $val['id'];
			$_tmp['state'] = $val['state'];
			$_tmp['context'] = $val['context'];
			$_tmp['time'] = date('Y-m-d h:i:s', $val['time']);
			//
			array_push($_list, $_tmp);
		}
		
		// 設定要帶的參數
		$_param = [
				'token'=>TSecurity::encrypt(json_encode($this->m_data)),
				'page'=>"TAnnouncement",
				'competence'=>$this->m_data->competence,
				'list'=>$_list
		];
		TViewBase::Main('TAnnouncement/TAnnouncement', $_param);
	}
	
	/**
	 * 新建
	 */
	private function ToNew()
	{
		$_id = $_POST['id'];
		
		if (isset($_POST['torun']))
		{
			$_con = $_POST['context'];
			//
			$_sql = 'insert into marquee (context, time) values (?, ?)';
			$_ret = $this->m_Writer->exec($_sql, $_con, time());
			if (!$_ret)
			{
				TLogs::log("TAnnouncement-ToNew", "ToNew marquee is fail.");
				echo '<script>alert("資料建立失敗！ ");</script>';
			}else echo '<script>alert("資料建立成功！跑馬燈將於下次登入時更新。 ");</script>';
				
			//
			$this->ShowBasic();
		}
		else
		{
			//				
			// 設定要帶的參數
			$_param = [
					'token'=>TSecurity::encrypt(json_encode($this->m_data)),
					'page'=>"TAnnouncement",
					'competence'=>$this->m_data->competence,
					'active'=>'ToNew',
			];
			TViewBase::Main('TAnnouncement/TAnnouncement_Set', $_param);
		}
	}
	
	/**
	 * 修改
	 */
	private function ToFix()
	{
		$_id = $_POST['id'];
		
		if (isset($_POST['torun']))
		{
			$_con = $_POST['context'];
			//
			$_sql = "update marquee set context=? where id=?";
			$_ret = $this->m_Writer->exec($_sql, $_con, $_id);
			if (!$_ret)
			{
				TLogs::log("TAnnouncement-ToFix", "ToFix marquee is fail.");
				echo '<script>alert("資料修改失敗！ ");</script>';
			}else echo '<script>alert("資料修改成功！跑馬燈將於下次登入時更新。 ");</script>';
			
			//
			$this->ShowBasic();
		}
		else 
		{
			//
			// 撈取資料
			$_sql = 'select context from marquee where id=?';
			$this->m_Read->exec($_sql, $_id);
			$_ret = $this->m_Read->fetch();
			
			// 設定要帶的參數
			$_param = [
					'token'=>TSecurity::encrypt(json_encode($this->m_data)),
					'page'=>"TAnnouncement",
					'competence'=>$this->m_data->competence,
					'active'=>'ToFix',
					'id'=>$_id,
					'context'=>$_ret['context'],
			];
			TViewBase::Main('TAnnouncement/TAnnouncement_Set', $_param);
		}
	}
	
	/**
	 * 開關
	 */
	private function ToSwitch()
	{
		$_id = $_POST['id'];
		$_sql = "update marquee set state = !state where id=?";
		$_ret = $this->m_Writer->exec($_sql, $_id);
		//
		echo '<script>alert("資料修改成功！跑馬燈將於下次登入時更新。 ");</script>';
		$this->ShowBasic();
	}
	
	/**
	 * 刪除
	 */
	private function ToDel()
	{
		$_id = $_POST['id'];
		//
		$_sql  = "delete from marquee where id=?";
		$_ret = $this->m_Writer->exec($_sql, $_id);
		if (!$_ret)
		{
			TLogs::log("TAnnouncement-delete", "del marquee is fail.");
			echo '<script>alert("資料刪除失敗！ ");</script>';
		}else echo '<script>alert("資料刪除成功！ ");</script>';
		//
		$this->ShowBasic();
	}
	
	// ==================================================================
	
	
}