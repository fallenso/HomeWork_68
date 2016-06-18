<?php
// billing_father 資料表
class TBillingFather extends TIProtocol {
	
	function __construct()
	{
		parent::__construct();
	}
	
	// =====================================
	
	/**
	 * 取得該單 的層級資料
	 * @param $iBilling_id : 下注單單號
	 * @param $iUser : 只能到哪個老大
	 * 
	 * @return array
	 */
	public function GetCompetence($iBilling_id, $iUser)
	{
		$_sql = 'select competence, fuid from billing_father where billing_id=?';
		$this->m_Read->exec($_sql, $iBilling_id);
		$_data = $this->m_Read->fetchAll();
		
		/*
		 * 資料處理
		 */
		$_TUser = new TUser();
		
		// 取得老大的層級
		$_limitCompetence = $_TUser->GetUserCompetence($iUser);
		
		
		// 資料顯示整理
		$_TUser = new TUser();
		$_return = [];
		foreach ($_data as $val)
		{
			// 判斷層級
			if ($_limitCompetence > $val['competence'])
			{
				$_tmp = [];
				$_namedata = $_TUser->GetUserName($val['fuid']);
				$_tmp['uid'] = $val['fuid'];
				$_tmp['competence'] = $val['competence'];
				$_tmp['name'] = $_namedata['name'];
				$_tmp['account'] = $_namedata['account'];
				
				array_push($_return, $_tmp);
			}
		}
		$_TUser = null;
						
		return $_return;
	}
	
	/**
	 * 取得該階層的 tax, cost, getrefunded
	 * @param $iBilling_id : 下注單單號
	 * @param $iCompetence : 層級
	 * 
	 * @return array
	 */
	public function GetGrandTotal($iBilling_id, $iCompetence)
	{
		$_sql = 'select tax, cost, getrefunded from billing_father where billing_id=? and competence=?';
		$this->m_Read->exec($_sql, $iBilling_id, $iCompetence);
		$_data = $this->m_Read->fetch();
		//
		return $_data;
	}
	
	/**
	 * 取得指定層級 的小計 上繳金額 實際輸贏*(1-自己佔成)
	 * @param $iBilling_id : 下注單單號
	 * @param $iCompetence : 層級
	 * 
	 * @return tax
	 */
	public function GetTax($iBilling_id, $iCompetence)
	{
		$_sql = 'select tax from billing_father where billing_id=? and competence=?';
		$this->m_Read->exec($_sql, $iBilling_id, $iCompetence);
		$_data = $this->m_Read->fetch();
		
		if (isset($_data['tax']))
		{
			return $_data['tax'];
		}
		
		return 0;
	}
	
	/**
	 *  取得指定層級 的  佔成分配 :: 自己賠多少 賺多少
	 * @param $iBilling_id : 下注單單號
	 * @param $iCompetence : 層級
	 *
	 * @return tax
	 */
	public function GetCost($iBilling_id, $iCompetence)
	{
		$_sql = 'select cost from billing_father where billing_id=? and competence=?';
		$this->m_Read->exec($_sql, $iBilling_id, $iCompetence);
		$_data = $this->m_Read->fetch();
	
		if (isset($_data['cost']))
		{
			return $_data['cost'];
		}
	
		return 0;
	}
	
	/**
	 * 取得指定層級 的實際總量 : 下注總金額 * (1-自己佔成)
	 * @param $iBilling_id : 下注單單號
	 * @param $iCompetence : 層級
	 *
	 * @return tax
	 */
	public function GetRAll($iBilling_id, $iCompetence)
	{
		$_sql = 'select rall from billing_father where billing_id=? and competence=?';
		$this->m_Read->exec($_sql, $iBilling_id, $iCompetence);
		$_data = $this->m_Read->fetch();
	
		if (isset($_data['rall']))
		{
			return $_data['rall'];
		}
	
		return 0;
	}

	/**
	 * 刪除相關單號
	 * @param $ibilling_id
	 * 
	 * @return bool
	 */
	public function Del($ibilling_id)
	{
		$_sql = 'delete from billing_father where billing_id=?';
		$_ret = $this->m_Writer->exec($_sql, $ibilling_id);
		if (!$_ret)
		{
			return false;
		}
		//
		return true;
	}
	
	
}