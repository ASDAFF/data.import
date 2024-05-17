<?
class CWebprostorImportAgent
{
	public static function Add($PLAN_ID = false, $AGENT_INTERVAL = 600, $TYPE = "Import", $TIME = false)
	{
		$period = 'N';
		$datecheck = date("d.m.Y H:i:s", time() + $AGENT_INTERVAL);
		
		if($TYPE == "Import")
			$next_exec = date("d.m.Y H:i:s", time() + $AGENT_INTERVAL);
		else
		{
			if($TIME)
			{
				$next_exec = date("d.m.Y {$TIME}");
				$check_agents = COption::GetOptionString("main", "check_agents");
				if($check_agents == 'Y')
					$period = 'Y';
			}
			else
				$next_exec = date("d.m.Y H:i:s");
		}
		
		$AGENT_ID = CAgent::AddAgent(
			"CWebprostorImport::{$TYPE}({$PLAN_ID});",
			"webprostor.import",
			$period,
			$AGENT_INTERVAL,
			$datecheck,
			"Y",
			$next_exec, 
			30
		);
		
		return $AGENT_ID;
	}
	
	public static function Update($PLAN_ID = false, $AGENT_INTERVAL = 600, $TYPE = "Import", $TIME = false)
	{
		$searchAndDelete = self::Delete($PLAN_ID, $TYPE);
		
		$AGENT_ID = self::Add($PLAN_ID, $AGENT_INTERVAL, $TYPE, $TIME);
		
		return $AGENT_ID;
	}
	
	public static function Delete($PLAN_ID = false, $TYPE = "Import")
	{
		$agents = self::SearchAgent($PLAN_ID, $TYPE);
		
		if(is_array($agents))
			CAgent::Delete($agents[0]["ID"]);
		
		return true;
	}
	
	public static function SearchAgent($PLAN_ID = false, $TYPE = "Import", $is_active = false)
	{
		global $DB;
		
		$result = false;
		
		if($PLAN_ID>0)
		{
			$query = "SELECT * FROM `b_agent` WHERE `NAME` LIKE '%CWebprostorImport::{$TYPE}({$PLAN_ID}%'";
		}
		else
		{
			$query = "SELECT * FROM `b_agent` WHERE `NAME` LIKE '%CWebprostorImport::{$TYPE}%'";
			if($is_active !== false)
			{
				$query .= " AND `ACTIVE` = '{$is_active}'";
			}
		}
			
		$cAgentResults = $DB->Query($query);
		$cAgentArray = array();
		
		while ($cAgentRow = $cAgentResults->Fetch()) {
			array_push($cAgentArray, $cAgentRow);
		}
		
		if(count($cAgentArray)>0)
		{
			$result = $cAgentArray;
		}
		
		return $result;
	}
	
	public static function getPlansWithDeletedAgents()
	{
		$result = false;
		$cPlans = new CWebprostorImportPlan;
		$plansRes = $cPlans->GetList(false, ['ACTIVE' => 'Y'], ['ID', 'AGENT_ID']);
		while($planArr = $plansRes->GetNext())
		{
			if($planArr['AGENT_ID'] > 0)
			{
				$agentArr = CAgent::GetByID($planArr['AGENT_ID'])->Fetch();
				if(!$agentArr)
					$result[] = $planArr['ID'];
			}
			else
				$result[] = $planArr['ID'];
		}
		return $result;
	}
}
?>