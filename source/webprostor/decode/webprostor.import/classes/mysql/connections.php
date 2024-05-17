<?
IncludeModuleLangFile(__FILE__);

class CWebprostorImportPlanConnections
{
	var $DB_NAME = "webprostor_import_plans_connections";
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
					case("PLAN_ID"):
						$strSelect .= "PLAN_ID, ";
						break;
					case("ENTITY"):
						$strSelect .= "ENTITY, ";
						break;
					case("ENTITY_ATTRIBUTE"):
						$strSelect .= "ENTITY_ATTRIBUTE, ";
						break;
					case("NAME"):
						$strSelect .= "NAME, ";
						break;
					case("SORT"):
						$strSelect .= "SORT, ";
						break;
					case("IBLOCK_FIELD"):
						$strSelect .= "IBLOCK_FIELD, ";
						break;
					case("IBLOCK_ELEMENT_PROPERTY"):
						$strSelect .= "IBLOCK_ELEMENT_PROPERTY, ";
						break;
					case("IS_IMAGE"):
						$strSelect .= "IS_IMAGE, ";
						break;
					case("IS_FILE"):
						$strSelect .= "IS_FILE, ";
						break;
					case("IS_URL"):
						$strSelect .= "IS_URL, ";
						break;
					case("IS_ARRAY"):
						$strSelect .= "IS_ARRAY, ";
						break;
					case("IS_REQUIRED"):
						$strSelect .= "IS_REQUIRED, ";
						break;
					case("USE_IN_SEARCH"):
						$strSelect .= "USE_IN_SEARCH, ";
						break;
					case("USE_IN_CODE"):
						$strSelect .= "USE_IN_CODE, ";
						break;
					case("IBLOCK_SECTION_FIELD"):
						$strSelect .= "IBLOCK_SECTION_FIELD, ";
						break;
					case("IBLOCK_SECTION_FIELD"):
						$strSelect .= "IBLOCK_SECTION_FIELD, ";
						break;
					case("IBLOCK_SECTION_PARENT_FIELD"):
						$strSelect .= "IBLOCK_SECTION_PARENT_FIELD, ";
						break;
					case("IBLOCK_SECTION_DEPTH_LEVEL"):
						$strSelect .= "IBLOCK_SECTION_DEPTH_LEVEL, ";
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
				if (is_null($value))
				{
					if ($prop == "IBLOCK_SECTION_DEPTH_LEVEL") $arSqlWhere[$prop] = " `IBLOCK_SECTION_DEPTH_LEVEL` IS NULL ";
					elseif ($prop == "!IBLOCK_SECTION_DEPTH_LEVEL") $arSqlWhere[$prop] = " `IBLOCK_SECTION_DEPTH_LEVEL` IS NOT NULL ";
				}
				elseif ($value)
				{
					if ($prop == "ID") $arSqlWhere[$prop] = " `ID` = ".$value." ";
					elseif ($prop == "ACTIVE") $arSqlWhere[$prop] = " `ACTIVE` = '".$value."' ";
					elseif ($prop == "PLAN_ID") $arSqlWhere[$prop] = " `PLAN_ID` = '".$value."' ";
					elseif ($prop == "ENTITY") $arSqlWhere[$prop] = " `ENTITY` = '".$value."' ";
					elseif ($prop == "ENTITY_ATTRIBUTE") $arSqlWhere[$prop] = " `ENTITY_ATTRIBUTE` = '".$value."' ";
					elseif ($prop == "NAME") $arSqlWhere[$prop] = " `NAME` = '".$value."' ";
					elseif ($prop == "?NAME") $arSqlWhere[$prop] = " `NAME` LIKE '%".$value."%' ";
					elseif ($prop == "SORT") $arSqlWhere[$prop] = " `SORT` = '".$value."' ";
					elseif ($prop == "IBLOCK_FIELD") $arSqlWhere[$prop] = " `IBLOCK_FIELD` = '".$value."' ";
					elseif ($prop == "IBLOCK_PROPERTY") $arSqlWhere[$prop] = " `IBLOCK_PROPERTY` = '".$value."' ";
					elseif ($prop == "IS_IMAGE") $arSqlWhere[$prop] = " `IS_IMAGE` = '".$value."' ";
					elseif ($prop == "IS_FILE") $arSqlWhere[$prop] = " `IS_FILE` = '".$value."' ";
					elseif ($prop == "IS_URL") $arSqlWhere[$prop] = " `IS_URL` = '".$value."' ";
					elseif ($prop == "IS_ARRAY") $arSqlWhere[$prop] = " `IS_ARRAY` = '".$value."' ";
					elseif ($prop == "IS_REQUIRED") $arSqlWhere[$prop] = " `IS_REQUIRED` = '".$value."' ";
					elseif ($prop == "USE_IN_SEARCH") $arSqlWhere[$prop] = " `USE_IN_SEARCH` = '".$value."' ";
					elseif ($prop == "USE_IN_CODE") $arSqlWhere[$prop] = " `USE_IN_CODE` = '".$value."' ";
					elseif ($prop == "IBLOCK_SECTION_FIELD") $arSqlWhere[$prop] = " `IBLOCK_SECTION_FIELD` = '".$value."' ";
				}
				else
				{
					if ($prop == "!IBLOCK_SECTION_FIELD") $arSqlWhere[$prop] = " `IBLOCK_SECTION_FIELD` != '".$value."' ";
					elseif ($prop == "!IBLOCK_SECTION_PARENT_FIELD") $arSqlWhere[$prop] = " `IBLOCK_SECTION_PARENT_FIELD` != '".$value."' ";
					elseif ($prop == "!IBLOCK_ELEMENT_FIELD") $arSqlWhere[$prop] = " `IBLOCK_ELEMENT_FIELD` != '".$value."' ";
					elseif ($prop == "!IBLOCK_ELEMENT_PROPERTY") $arSqlWhere[$prop] = " `IBLOCK_ELEMENT_PROPERTY` != '".$value."' ";
					elseif ($prop == "!HIGHLOAD_BLOCK_ENTITY_FIELD") $arSqlWhere[$prop] = " `HIGHLOAD_BLOCK_ENTITY_FIELD` != '".$value."' ";
					elseif ($prop == "!IBLOCK_ELEMENT_PROPERTY_E") $arSqlWhere[$prop] = " `IBLOCK_ELEMENT_PROPERTY_E` != '".$value."' ";
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
				elseif ($by == "ACTIVE") $arSqlOrder[$by] = " `ACTIVE` ".$order." ";
				elseif ($by == "PLAN_ID") $arSqlOrder[$by] = " `PLAN_ID` ".$order." ";
				elseif ($by == "ENTITY") $arSqlOrder[$by] = " `ENTITY` ".$order." ";
				elseif ($by == "ENTITY_ATTRIBUTE") $arSqlOrder[$by] = " `ENTITY_ATTRIBUTE` ".$order." ";
				elseif ($by == "NAME") $arSqlOrder[$by] = " `NAME` ".$order." ";
				elseif ($by == "SORT") $arSqlOrder[$by] = " `SORT` ".$order." ";
				elseif ($by == "IBLOCK_FIELD") $arSqlOrder[$by] = " `IBLOCK_FIELD` ".$order." ";
				elseif ($by == "IBLOCK_PROPERTY") $arSqlOrder[$by] = " `IBLOCK_PROPERTY` ".$order." ";
				elseif ($by == "IS_IMAGE") $arSqlOrder[$by] = " `IS_IMAGE` ".$order." ";
				elseif ($by == "IS_FILE") $arSqlOrder[$by] = " `IS_FILE` ".$order." ";
				elseif ($by == "IS_URL") $arSqlOrder[$by] = " `IS_URL` ".$order." ";
				elseif ($by == "IS_ARRAY") $arSqlOrder[$by] = " `IS_ARRAY` ".$order." ";
				elseif ($by == "IS_REQUIRED") $arSqlOrder[$by] = " `IS_REQUIRED` ".$order." ";
				elseif ($by == "USE_IN_SEARCH") $arSqlOrder[$by] = " `USE_IN_SEARCH` ".$order." ";
				elseif ($by == "USE_IN_CODE") $arSqlOrder[$by] = " `USE_IN_CODE` ".$order." ";
			}
		}

		if(count($arSqlOrder) > 0)
			$strSqlOrder = " ORDER BY ".implode(",", $arSqlOrder);
		else
			$strSqlOrder = "";
		
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
		
		if(strlen($arFields["ENTITY"])==0)
			$aMsg[] = array("id"=>"ENTITY", "text"=>GetMessage("ENTITY_EMPTY"));
		
		if(!$arFields["PLAN_ID"])
			$aMsg[] = array("id"=>"PLAN_ID", "text"=>GetMessage("PLAN_ID_EMPTY"));
		
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
	
	function UpdatePlanConnections($PLAN_ID, $arFields)
	{
		$cPlanData = new CWebprostorImportPlan;
		$planRes = $cPlanData->GetById($PLAN_ID);
		$planInfo = $planRes->Fetch();
		
		$cData = self::GetList(Array(), Array("PLAN_ID" => $PLAN_ID));
		while($connection = $cData->GetNext())
		{
			if(!isset($arFields["ID"]) || !in_array($connection["ID"], $arFields["ID"]))
				self::Delete($connection["ID"]);
		}
		foreach($arFields["ID"] as $k => $CONNECTION_ID)
		{
			$connectionArr = Array(
				"PLAN_ID" => $PLAN_ID,
				"ACTIVE" => ($arFields["ACTIVE"][$k]=="Y")?"Y":"N",
				"ENTITY" => ($planInfo['IMPORT_FORMAT'] == "XML" && $planInfo['XML_USE_ENTITY_NAME'] == "Y") ? '0' : $arFields["ENTITY"][$k],
				"ENTITY_ATTRIBUTE" => $arFields["ENTITY_ATTRIBUTE"][$k],
				"NAME" => (isset($arFields["NAME"][$k]) ? $arFields["NAME"][$k] : $arFields["ENTITY_NAME"][$arFields["ENTITY"][$k]]),
				"SORT" => $arFields["SORT"][$k],
				"IBLOCK_SECTION_FIELD" => $arFields["IBLOCK_SECTION_FIELD"][$k],
				"IBLOCK_SECTION_DEPTH_LEVEL" => $arFields["IBLOCK_SECTION_DEPTH_LEVEL"][$k],
				"IBLOCK_SECTION_PARENT_FIELD" => $arFields["IBLOCK_SECTION_PARENT_FIELD"][$k],
				"IBLOCK_ELEMENT_FIELD" => $arFields["IBLOCK_ELEMENT_FIELD"][$k],
				"IBLOCK_ELEMENT_PROPERTY" => $arFields["IBLOCK_ELEMENT_PROPERTY"][$k],
				"IBLOCK_ELEMENT_PROPERTY_E" => $arFields["IBLOCK_ELEMENT_PROPERTY_E"][$k],
				"IBLOCK_ELEMENT_PROPERTY_G" => $arFields["IBLOCK_ELEMENT_PROPERTY_G"][$k],
				"IBLOCK_ELEMENT_PROPERTY_M" => $arFields["IBLOCK_ELEMENT_PROPERTY_M"][$k],
				"IBLOCK_ELEMENT_OFFER_FIELD" => $arFields["IBLOCK_ELEMENT_OFFER_FIELD"][$k],
				"IBLOCK_ELEMENT_OFFER_PROPERTY" => $arFields["IBLOCK_ELEMENT_OFFER_PROPERTY"][$k],
				"CATALOG_PRODUCT_FIELD" => $arFields["CATALOG_PRODUCT_FIELD"][$k],
				"CATALOG_PRODUCT_OFFER_FIELD" => $arFields["CATALOG_PRODUCT_OFFER_FIELD"][$k],
				"CATALOG_PRODUCT_PRICE" => $arFields["CATALOG_PRODUCT_PRICE"][$k],
				"CATALOG_PRODUCT_STORE_AMOUNT" => $arFields["CATALOG_PRODUCT_STORE_AMOUNT"][$k],
				"HIGHLOAD_BLOCK_ENTITY_FIELD" => $arFields["HIGHLOAD_BLOCK_ENTITY_FIELD"][$k],
				"IS_IMAGE" => ($arFields["IS_IMAGE"][$k]=="Y")?"Y":"N",
				"IS_FILE" => ($arFields["IS_FILE"][$k]=="Y")?"Y":"N",
				"IS_URL" => ($arFields["IS_URL"][$k]=="Y")?"Y":"N",
				"IS_ARRAY" => ($arFields["IS_ARRAY"][$k]=="Y")?"Y":"N",
				"IS_REQUIRED" => ($arFields["IS_REQUIRED"][$k]=="Y")?"Y":"N",
				"USE_IN_SEARCH" => ($arFields["USE_IN_SEARCH"][$k]=="Y")?"Y":"N",
				"USE_IN_CODE" => $arFields["USE_IN_CODE"][$k],
				"PROCESSING_TYPES" => (
					strpos($CONNECTION_ID, "copy_") === 0 && is_array($arFields["PROCESSING_TYPES"][$k]) ? 
					base64_encode(serialize($arFields["PROCESSING_TYPES"][$k])) :
					(
						is_array($arFields["PROCESSING_TYPES"][$CONNECTION_ID]) ?
						base64_encode(serialize($arFields["PROCESSING_TYPES"][$CONNECTION_ID])) : 
						(
							$CONNECTION_ID == '' && is_array($arFields["PROCESSING_TYPES"]['new_'.$k]) ?
							base64_encode(serialize($arFields["PROCESSING_TYPES"]['new_'.$k])) : ""
						)
					)
				),
			);
			
			if(isset($CONNECTION_ID) && $CONNECTION_ID>0 && strpos($CONNECTION_ID, "copy_") !== 0)
			{
				self::Update($CONNECTION_ID, $connectionArr);
			}
			else
			{
				$CONNECTION_ID = self::Add($connectionArr);
			}
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
	
	function DeleteByPlanId($PLAN_ID)
	{
		$cData = self::GetList(Array(), Array("PLAN_ID" => $PLAN_ID));
		while($connection = $cData->GetNext())
		{
			self::Delete($connection["ID"]);
		}
		return true;
	}
}
?>