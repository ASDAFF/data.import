<?
class CDataImportLog
{
	var $DB_NAME = "data_import_logs";
	var $LAST_ERROR = "";
	var $LAST_MESSAGE = "";
	
	public function GetList($arOrder = Array("SORT"=>"ASC"), $arFilter = false, $arSelect = false)
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
					case("TIMESTAMP_X"):
						$strSelect .= "TIMESTAMP_X, ";
						break;
					case("PLAN_ID"):
						$strSelect .= "PLAN_ID, ";
						break;
					case("EVENT"):
						$strSelect .= "EVENT, ";
						break;
					case("MESSAGE"):
						$strSelect .= "MESSAGE, ";
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
		
		$arSqlWhere = Array();
		if(is_array($arFilter) && count($arFilter)>0)
		{
			foreach($arFilter as $prop=>$value)
			{
				$prop = strtoupper($prop);
				
				if ($value)
				{
					if ($prop == "ID") $arSqlWhere[$prop] = ' `ID` = "'.$value.'" ';
					elseif ($prop == "TIMESTAMP_X") $arSqlWhere[$prop] = ' `TIMESTAMP_X` = "'.ConvertDateTime($value, "YYYY-MM-DD HH:MI:SS", "ru").'" ';
					elseif ($prop == ">=TIMESTAMP_X") $arSqlWhere[$prop] = ' `TIMESTAMP_X` >= "'.ConvertDateTime($value, "YYYY-MM-DD HH:MI:SS", "ru").'" ';
					elseif ($prop == "<=TIMESTAMP_X") $arSqlWhere[$prop] = ' `TIMESTAMP_X` <= "'.ConvertDateTime($value, "YYYY-MM-DD HH:MI:SS", "ru").'" ';
					elseif ($prop == "PLAN_ID") $arSqlWhere[$prop] = ' `PLAN_ID` = "'.$value.'" ';
					elseif ($prop == "EVENT") $arSqlWhere[$prop] = " `EVENT` LIKE '%".$value."%' ";
					elseif ($prop == "MESSAGE") $arSqlWhere[$prop] = " `MESSAGE` = '".$value."' ";
					elseif ($prop == "?MESSAGE") $arSqlWhere[$prop] = " `MESSAGE` LIKE '%".$value."%' ";
				}
			}
		}

		if(count($arSqlWhere) > 0)
			$strSqlWhere = " WHERE ".implode("AND", $arSqlWhere);
		else
			$strSqlWhere = "";
		
		$arSqlOrder = Array();
		if(is_array($arOrder))
		{
			foreach($arOrder as $by=>$order)
			{
				$by = strtoupper($by);
				$order = strtoupper($order);
				if ($order!="ASC")
					$order = "DESC";
				
				if ($by == "ID") $arSqlOrder[$by] = " `ID` ".$order." ";
				elseif ($by == "TIMESTAMP_X") $arSqlOrder[$by] = " `TIMESTAMP_X` ".$order." ";
				elseif ($by == "PLAN_ID") $arSqlOrder[$by] = " `PLAN_ID` ".$order." ";
				elseif ($by == "EVENT") $arSqlOrder[$by] = " `EVENT` ".$order." ";
				elseif ($by == "MESSAGE") $arSqlOrder[$by] = " `MESSAGE` ".$order." ";
			}
		}

		if(count($arSqlOrder) > 0)
			$strSqlOrder = " ORDER BY ".implode(",", $arSqlOrder);
		else
			$strSqlOrder = "";
		
		$res = $DB->Query($strSql.$strSqlWhere.$strSqlOrder, false, "File: ".__FILE__."<br>Line: ".__LINE__);
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