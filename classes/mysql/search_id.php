<?
class CDataImportPlanSearchId
{
	var $DB_NAME = "data_import_search_id";
	var $LAST_ERROR = "";
	var $LAST_MESSAGE = "";
	
	public function GetList($arOrder = Array("SORT"=>"ASC"), $arFilter = false, $arSelect = false, $limit = false)
	{
		global $DB;
		
		if(is_array($arSelect))
		{
			$strSelect = '';
			foreach($arSelect as $by=>$select)
			{
				switch($select)
				{
					case("ID"):
						$strSelect .= "ID, ";
						break;
					case("PLAN_ID"):
						$strSelect .= "PLAN_ID, ";
						break;
					case("LAST_IMPORT_DATE"):
						$strSelect .= "LAST_IMPORT_DATE, ";
						break;
					case("OBJECT_TYPE"):
						$strSelect .= "OBJECT_TYPE, ";
						break;
					case("OBJECT_ID"):
						$strSelect .= "OBJECT_ID, ";
						break;
				}
			}
			$strSelect = trim($strSelect, ", ");
		}
		else
			$strSelect = '*';
		
		$strSql = "
			SELECT
				{$strSelect}
			FROM `{$this->DB_NAME}`
		";
		
		$arSqlWhere = [];
		if(is_array($arFilter) && count($arFilter)>0)
		{
			foreach($arFilter as $prop=>$value)
			{
				$prop = strtoupper($prop);
				
				if ($value)
				{
					if ($prop == "ID") $arSqlWhere[$prop] = ' `ID` = "'.$value.'" ';
					elseif ($prop == "PLAN_ID") $arSqlWhere[$prop] = ' `PLAN_ID` = "'.$value.'" ';
					elseif ($prop == "LAST_IMPORT_DATE") $arSqlWhere[$prop] = " `LAST_IMPORT_DATE` = '".$value."' ";
					elseif ($prop == "OBJECT_TYPE") $arSqlWhere[$prop] = " `OBJECT_TYPE` = '".$value."' ";
					elseif ($prop == "OBJECT_ID") $arSqlWhere[$prop] = " `OBJECT_ID` = '".$value."' ";
				}
			}
		}

		if(is_array($arSqlWhere) && count($arSqlWhere) > 0)
			$strSqlWhere = " WHERE ".implode("AND", $arSqlWhere);
		else
			$strSqlWhere = "";
		
		$arSqlOrder = [];
		if(is_array($arOrder))
		{
			foreach($arOrder as $by=>$order)
			{
				$by = strtoupper($by);
				$order = strtoupper($order);
				if ($order!="ASC")
					$order = "DESC";
				
				if ($by == "ID") $arSqlOrder[$by] = " `ID` ".$order." ";
				elseif ($by == "PLAN_ID") $arSqlOrder[$by] = " `PLAN_ID` ".$order." ";
				elseif ($by == "LAST_IMPORT_DATE") $arSqlOrder[$by] = " `LAST_IMPORT_DATE` ".$order." ";
				elseif ($by == "OBJECT_TYPE") $arSqlOrder[$by] = " `OBJECT_TYPE` ".$order." ";
				elseif ($by == "OBJECT_ID") $arSqlOrder[$by] = " `OBJECT_ID` ".$order." ";
			}
		}

		if(is_array($arSqlOrder) && count($arSqlOrder) > 0)
			$strSqlOrder = " ORDER BY ".implode(",", $arSqlOrder);
		else
			$strSqlOrder = "";
		
		if($limit > 0)
			$strLimit = ' LIMIT '.$limit;
		else
			$strLimit = "";
		
		$res = $DB->Query($strSql.$strSqlWhere.$strSqlOrder.$strLimit, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		return $res;
		
	}
	
	private function CheckFields($arFields, $ID = false)
	{
		global $DB;
		
		$this->LAST_ERROR = "";
		$aMsg = array();
		
		if(!empty($aMsg))
		{
			$e = new CAdminException($aMsg);
			$GLOBALS["APPLICATION"]->ThrowException($e);
			$this->LAST_ERROR = $e->GetString();
			return false;
		}
		return true;
	}
	
	public function Add($arFields)
	{
		global $DB;
		
		if(!$this->CheckFields($arFields))
			return false;
		
		$strUpdate = $DB->PrepareInsert($this->DB_NAME, $arFields);
		if($strUpdate != "")
		{
			$strSql = "
			INSERT INTO 
				`{$this->DB_NAME}` 
			(".$strUpdate[0].") values(".$strUpdate[1].")
			";
			if(!$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__))
				return false;
		}
		
		return intval($DB->LastID());
		
	}
	
	public function Update($ID, $arFields)
	{
		global $DB;
		$ID = IntVal($ID);
		
		if(!$this->CheckFields($arFields, $ID))
			return false;
		
		$strUpdate = $DB->PrepareUpdate($this->DB_NAME, $arFields);
		if($strUpdate != "")
		{
			$strSql = '
			UPDATE 
				`'.$this->DB_NAME.'` 
			SET '.$strUpdate.'
			WHERE 
				ID = "'.$DB->ForSql($ID).'"
			';
			if(!$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__))
				return false;
		}
		
		return true;
		
	}
	
	public function Delete($ID)
	{
		global $DB;
		$ID = IntVal($ID);
		
		$DB->StartTransaction();
		$strSql = '
			DELETE 
			FROM 
				`'.$this->DB_NAME.'` 
			WHERE 
				`ID` = "'.$ID.'"
		';
		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		
		if($res)
			$DB->Commit();
		else
			$DB->Rollback();

		return $res;
	}
}
?>