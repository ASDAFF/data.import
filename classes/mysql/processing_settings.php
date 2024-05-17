<?
IncludeModuleLangFile(__FILE__);

class CDataImportProcessingSettings
{
	var $DB_NAME = "data_import_processing_settings";
	var $LAST_ERROR = "";
	var $LAST_MESSAGE = "";
	
	public function GetList($arOrder = Array("ID"=>"ASC"), $arFilter = false, $arSelect = false)
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
					case("ACTIVE"):
						$strSelect .= "ACTIVE, ";
						break;
					case("SORT"):
						$strSelect .= "SORT, ";
						break;
					case("PROCESSING_TYPE"):
						$strSelect .= "PROCESSING_TYPE, ";
						break;
					case("PARAMS"):
						$strSelect .= "PARAMS, ";
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
				if (is_array($value))
				{
					if ($prop == "ID")
					{
						foreach($value as $valueOne)
						{
							if ($valueOne)
							{
								$arSqlWhereOR[$prop.$valueOne] = " `ID` = ".$valueOne." ";
							}
						}
					}
				}
				elseif ($value)
				{
					if ($prop == "ID") $arSqlWhere[$prop] = " `ID` = ".$value." ";
					elseif ($prop == "ACTIVE") $arSqlWhere[$prop] = " `ACTIVE` = '".$value."' ";
					elseif ($prop == "SORT") $arSqlWhere[$prop] = " `SORT` = '".$value."' ";
					elseif ($prop == "PROCESSING_TYPE") $arSqlWhere[$prop] = " `PROCESSING_TYPE` = '".$value."' ";
					elseif ($prop == "PARAMS") $arSqlWhere[$prop] = " `PARAMS` = '".$value."' ";
				}
			}
		}

		if(count($arSqlWhere) > 0)
			$strSqlWhere = " WHERE ".implode("AND", $arSqlWhere);
		else
			$strSqlWhere = "";
		
		if(is_array($arSqlWhereOR) && count($arSqlWhereOR) > 0)
		{
			if($strSqlWhere == "")
				$strSqlWhere .= " WHERE ".implode("OR", $arSqlWhereOR);
			else
				$strSqlWhere .= " AND (".implode("OR", $arSqlWhereOR).")";
		}
		
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
				elseif ($by == "ACTIVE") $arSqlOrder[$by] = " `ACTIVE` ".$order." ";
				elseif ($by == "SORT") $arSqlOrder[$by] = " `SORT` ".$order." ";
				elseif ($by == "PROCESSING_TYPE") $arSqlOrder[$by] = " `PROCESSING_TYPE` ".$order." ";
				elseif ($by == "PARAMS") $arSqlOrder[$by] = " `PARAMS` ".$order." ";
			}
		}

		if(count($arSqlOrder) > 0)
			$strSqlOrder = " ORDER BY ".implode(",", $arSqlOrder);
		else
			$strSqlOrder = "";
		//echo $strSql.$strSqlWhere.$strSqlOrder;
		$res = $DB->Query($strSql.$strSqlWhere.$strSqlOrder, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		return $res;
		
	}
	
	public function GetById($ID)
	{
		global $DB;
		$ID = IntVal($ID);
		$strSql = '
			SELECT 
				*
			FROM 
				`'.$this->DB_NAME.'`
			WHERE 
				`ID` = "'.$ID.'"
		';
		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		return $res;
		
	}
	
	public function CheckFields(&$arFields, $ID = false)
	{
		global $DB;
		
		$this->LAST_ERROR = "";
		$aMsg = array();
		
		if(!$arFields["PROCESSING_TYPE"])
			$aMsg[] = array("id"=>"PROCESSING_TYPE", "text"=>GetMessage("PROCESSING_TYPE_NOT_SET"));
		
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