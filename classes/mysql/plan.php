<?
use Bitrix\Main\Localization\Loc;

class CDataImportPlan
{
	var $MODULE_ID = "data.import";
	
	var $DB_NAME = "data_import_plans";
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
					case("ACTIVE"):
						$strSelect .= "ACTIVE, ";
						break;
					case("NAME"):
						$strSelect .= "NAME, ";
						break;
					case("SORT"):
						$strSelect .= "SORT, ";
						break;
					case("SHOW_IN_MENU"):
						$strSelect .= "SHOW_IN_MENU, ";
						break;
					case("SHOW_IN_MANUALLY"):
						$strSelect .= "SHOW_IN_MANUALLY, ";
						break;
					case("IBLOCK_ID"):
						$strSelect .= "IBLOCK_ID, ";
						break;
					case("HIGHLOAD_BLOCK"):
						$strSelect .= "HIGHLOAD_BLOCK, ";
						break;
					case("IMPORT_FORMAT"):
						$strSelect .= "IMPORT_FORMAT, ";
						break;
					case("IMPORT_FILE_URL"):
						$strSelect .= "IMPORT_FILE_URL, ";
						break;
					case("ITEMS_PER_ROUND"):
						$strSelect .= "ITEMS_PER_ROUND, ";
						break;
					case("IMPORT_FILE"):
						$strSelect .= "IMPORT_FILE, ";
						break;
					case("IMPORT_FILE_SHARSET"):
						$strSelect .= "IMPORT_FILE_SHARSET, ";
						break;
					case("PATH_TO_IMAGES"):
						$strSelect .= "PATH_TO_IMAGES, ";
						break;
					case("PATH_TO_FILES"):
						$strSelect .= "PATH_TO_FILES, ";
						break;
					case("DEBUG_EVENTS"):
						$strSelect .= "DEBUG_EVENTS, ";
						break;
					case("AGENT_INTERVAL"):
						$strSelect .= "AGENT_INTERVAL, ";
						break;
					case("AGENT_ID"):
						$strSelect .= "AGENT_ID, ";
						break;
					case("IMPORT_CATALOG_PRODUCTS"):
						$strSelect .= "IMPORT_CATALOG_PRODUCTS, ";
						break;
					case("PRODUCTS_ADD"):
						$strSelect .= "PRODUCTS_ADD, ";
						break;
					case("PRODUCTS_UPDATE"):
						$strSelect .= "PRODUCTS_UPDATE, ";
						break;
					case("IMPORT_CATALOG_PRODUCT_OFFERS"):
						$strSelect .= "IMPORT_CATALOG_PRODUCT_OFFERS, ";
						break;
					case("OFFERS_ADD"):
						$strSelect .= "OFFERS_ADD, ";
						break;
					case("OFFERS_UPDATE"):
						$strSelect .= "OFFERS_UPDATE, ";
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
		$arSqlWhereOr = Array();
		if(is_array($arFilter) && count($arFilter)>0)
		{
			foreach($arFilter as $prop=>$value)
			{
				$prop = strtoupper($prop);
				
				if ($value)
				{
					if ($prop == "ID") $arSqlWhere[$prop] = ' `ID` = "'.$value.'" ';
					elseif ($prop == "ACTIVE") $arSqlWhere[$prop] = ' `ACTIVE` = "'.$value.'" ';
					elseif ($prop == "NAME") $arSqlWhere[$prop] = ' `NAME` = "'.$value.'" ';
					elseif ($prop == "?NAME") $arSqlWhere[$prop] = ' `NAME` LIKE "%'.$value.'%" ';
					elseif ($prop == "SORT") $arSqlWhere[$prop] = ' `SORT` = "'.$value.'" ';
					elseif ($prop == "SHOW_IN_MENU") $arSqlWhere[$prop] = ' `SHOW_IN_MENU` = "'.$value.'" ';
					elseif ($prop == "SHOW_IN_MANUALLY") $arSqlWhere[$prop] = ' `SHOW_IN_MANUALLY` = "'.$value.'" ';
					elseif ($prop == "IBLOCK_ID") $arSqlWhere[$prop] = ' `IBLOCK_ID` = "'.$value.'" ';
					elseif ($prop == "HIGHLOAD_BLOCK") $arSqlWhere[$prop] = ' `HIGHLOAD_BLOCK` = "'.$value.'" ';
					elseif ($prop == "IMPORT_FORMAT") $arSqlWhere[$prop] = ' `IMPORT_FORMAT` = "'.$value.'" ';
					elseif ($prop == "ITEMS_PER_ROUND") $arSqlWhere[$prop] = ' `ITEMS_PER_ROUND` = "'.$value.'" ';
					elseif ($prop == "IMPORT_FILE") $arSqlWhere[$prop] = ' `IMPORT_FILE` = "'.$value.'" ';
					elseif ($prop == "IMPORT_FILE_SHARSET") $arSqlWhere[$prop] = ' `IMPORT_FILE_SHARSET` = "'.$value.'" ';
					elseif ($prop == "IMPORT_FILE_URL") $arSqlWhere[$prop] = ' `IMPORT_FILE_URL` = "'.$value.'" ';
					elseif ($prop == "PATH_TO_IMAGES") $arSqlWhere[$prop] = ' `PATH_TO_IMAGES` = "'.$value.'" ';
					elseif ($prop == "PATH_TO_FILES") $arSqlWhere[$prop] = ' `PATH_TO_FILES` = "'.$value.'" ';
					elseif ($prop == "DEBUG_EVENTS") $arSqlWhere[$prop] = ' `DEBUG_EVENTS` = "'.$value.'" ';
					elseif ($prop == "AGENT_INTERVAL") $arSqlWhere[$prop] = ' `AGENT_INTERVAL` = "'.$value.'" ';
					elseif ($prop == "AGENT_ID") $arSqlWhere[$prop] = ' `AGENT_ID` = "'.$value.'" ';
					elseif ($prop == "IMPORT_CATALOG_PRODUCTS") $arSqlWhere[$prop] = ' `IMPORT_CATALOG_PRODUCTS` = "'.$value.'" ';
					elseif ($prop == "IMPORT_CATALOG_PRODUCT_OFFERS") $arSqlWhere[$prop] = ' `IMPORT_CATALOG_PRODUCT_OFFERS` = "'.$value.'" ';
					elseif ($prop == "SECTIONS_ADD") $arSqlWhere[$prop] = ' `SECTIONS_ADD` = "'.$value.'" ';
					elseif ($prop == "ELEMENTS_ADD") $arSqlWhere[$prop] = ' `ELEMENTS_ADD` = "'.$value.'" ';
				}
			}
		}
		
		if(count($arSqlWhere) > 0)
		{
			$strSqlWhere = " WHERE ".implode((isset($arFilter["LOGIC"]) && $arFilter["LOGIC"]=="OR"?"OR":"AND"), $arSqlWhere);
		}
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
				elseif ($by == "NAME") $arSqlOrder[$by] = " `NAME` ".$order." ";
				elseif ($by == "SORT") $arSqlOrder[$by] = " `SORT` ".$order." ";
				elseif ($by == "SHOW_IN_MENU") $arSqlOrder[$by] = " `SHOW_IN_MENU` ".$order." ";
				elseif ($by == "SHOW_IN_MANUALLY") $arSqlOrder[$by] = " `SHOW_IN_MANUALLY` ".$order." ";
				elseif ($by == "IBLOCK_ID") $arSqlOrder[$by] = " `IBLOCK_ID` ".$order." ";
				elseif ($by == "HIGHLOAD_BLOCK") $arSqlOrder[$by] = " `HIGHLOAD_BLOCK` ".$order." ";
				elseif ($by == "IMPORT_FORMAT") $arSqlOrder[$by] = " `IMPORT_FORMAT` ".$order." ";
				elseif ($by == "ITEMS_PER_ROUND") $arSqlOrder[$by] = " `ITEMS_PER_ROUND` ".$order." ";
				elseif ($by == "IMPORT_FILE") $arSqlOrder[$by] = " `IMPORT_FILE` ".$order." ";
				elseif ($by == "IMPORT_FILE_SHARSET") $arSqlOrder[$by] = " `IMPORT_FILE_SHARSET` ".$order." ";
				elseif ($by == "IMPORT_FILE_URL") $arSqlOrder[$by] = " `IMPORT_FILE_URL` ".$order." ";
				elseif ($by == "PATH_TO_IMAGES") $arSqlOrder[$by] = " `PATH_TO_IMAGES` ".$order." ";
				elseif ($by == "PATH_TO_FILES") $arSqlOrder[$by] = " `PATH_TO_FILES` ".$order." ";
				elseif ($by == "DEBUG_EVENTS") $arSqlOrder[$by] = " `DEBUG_EVENTS` ".$order." ";
				elseif ($by == "AGENT_INTERVAL") $arSqlOrder[$by] = " `AGENT_INTERVAL` ".$order." ";
				elseif ($by == "AGENT_INTERVAL_URL") $arSqlOrder[$by] = " `AGENT_INTERVAL_URL` ".$order." ";
				elseif ($by == "AGENT_ID") $arSqlOrder[$by] = " `AGENT_ID` ".$order." ";
				elseif ($by == "IMPORT_CATALOG_PRODUCTS") $arSqlOrder[$by] = " `IMPORT_CATALOG_PRODUCTS` ".$order." ";
				elseif ($by == "PRODUCTS_ADD") $arSqlOrder[$by] = " `PRODUCTS_ADD` ".$order." ";
				elseif ($by == "PRODUCTS_UPDATE") $arSqlOrder[$by] = " `PRODUCTS_UPDATE` ".$order." ";
				elseif ($by == "IMPORT_CATALOG_PRODUCT_OFFERS") $arSqlOrder[$by] = " `IMPORT_CATALOG_PRODUCT_OFFERS` ".$order." ";
				elseif ($by == "OFFERS_ADD") $arSqlOrder[$by] = " `OFFERS_ADD` ".$order." ";
				elseif ($by == "OFFERS_UPDATE") $arSqlOrder[$by] = " `OFFERS_UPDATE` ".$order." ";
				elseif ($by == "LAST_IMPORT_DATE") $arSqlOrder[$by] = " `LAST_IMPORT_DATE` ".$order." ";
				elseif ($by == "LAST_STEP_IMPORT_DATE") $arSqlOrder[$by] = " `LAST_STEP_IMPORT_DATE` ".$order." ";
				elseif ($by == "LAST_FINISH_IMPORT_DATE") $arSqlOrder[$by] = " `LAST_FINISH_IMPORT_DATE` ".$order." ";
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
	
	/*public function GetLastImportDate($ID)
	{
		global $DB;
		$ID = IntVal($ID);
		$strSql = '
			SELECT 
				`LAST_IMPORT_DATE`
			FROM 
				`'.$this->DB_NAME.'`
			WHERE 
				`ID` = "'.$ID.'"
		';
		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		return $res;
		
	}*/
	
	private function CheckFields(&$arFields, $ID = false)
	{
		global $DB;
		
		$this->LAST_ERROR = "";
		$aMsg = array();
		
		if(strlen($arFields["NAME"])==0)
			$aMsg[] = array("id"=>"NAME", "text"=>Loc::getMessage("NAME_EMPTY"));

		if((!$arFields["IBLOCK_ID"] && !$arFields["HIGHLOAD_BLOCK"]) || ($arFields["IBLOCK_ID"] == 0 && $arFields["HIGHLOAD_BLOCK"] == 0))
		{
			$aMsg[] = array("id"=>"IBLOCK_ID", "text"=>Loc::getMessage("IMPORT_TYPE_NOT_SET"));
		}
		elseif($arFields["IBLOCK_ID"] && $arFields["HIGHLOAD_BLOCK"])
		{
			$aMsg[] = array("id"=>"IBLOCK_ID", "text"=>Loc::getMessage("IMPORT_TYPE_CANT_MULTIPLE"));
		}
		
		if($arFields["HIGHLOAD_BLOCK"] && ($arFields["IMPORT_IBLOCK_SECTIONS"] == "Y" || $arFields["IMPORT_IBLOCK_ELEMENTS"] == "Y" || $arFields["IMPORT_IBLOCK_PROPERTIES"] == "Y" || $arFields["IMPORT_CATALOG_PRODUCTS"] == "Y" || $arFields["IMPORT_CATALOG_PRODUCT_OFFERS"] == "Y" || $arFields["IMPORT_CATALOG_PRICES"] == "Y" || $arFields["IMPORT_CATALOG_STORE_AMOUNT"] == "Y"))
		{
			$aMsg[] = array("id"=>"HIGHLOAD_BLOCK", "text"=>Loc::getMessage("HIGHLOAD_BLOCK_ONLY"));
		}
		if($arFields["CSV_XLS_MAX_DEPTH_LEVEL"])
			intVal($arFields["CSV_XLS_MAX_DEPTH_LEVEL"]);
		
		if($arFields["IMPORT_FORMAT"] != "XML" && $arFields["IBLOCK_ID"] && (!$arFields["CSV_XLS_MAX_DEPTH_LEVEL"] || $arFields["CSV_XLS_MAX_DEPTH_LEVEL"] == 0))
		{
			$arFields["CSV_XLS_MAX_DEPTH_LEVEL"] = 3;
		}
		
		if($arFields["IMPORT_FORMAT"] == "XML" && $arFields["XML_ENTITY"] == '' && $ID)
		{
			$aMsg[] = array("id"=>"XML_ENTITY", "text"=>Loc::getMessage("XML_ENTITY_REQUIRED"));
		}
		
		if($arFields["CURL_TIMEOUT"] && $arFields["CURL_TIMEOUT"] <= 0)
		{
			$aMsg[] = array("id"=>"CURL_TIMEOUT", "text"=>Loc::getMessage("CURL_TIMEOUT_MIN"));
		}
		
		if($arFields["IBLOCK_ID"] && $arFields["IMPORT_HIGHLOAD_BLOCK_ENTITIES"] == "Y")
			$aMsg[] = array("id"=>"IMPORT_HIGHLOAD_BLOCK_ENTITIES", "text"=>Loc::getMessage("CANT_USE_IMPORT_HIGHLOAD_BLOCK_ENTITIES_WITH_IBLOCK_ID"));
		
		if(!$arFields["IMPORT_FORMAT"])
			$aMsg[] = array("id"=>"IMPORT_FORMAT", "text"=>Loc::getMessage("IMPORT_FORMAT_EMPTY"));
		
		if(!$arFields["ITEMS_PER_ROUND"]>0)
			$aMsg[] = array("id"=>"ITEMS_PER_ROUND", "text"=>Loc::getMessage("ITEMS_PER_ROUND_IS_NULL"));
		
		if(!$arFields["IMPORT_FILE"] && $arFields["IMPORT_FORMAT"] != 'JSON')
			$aMsg[] = array("id"=>"IMPORT_FILE", "text"=>Loc::getMessage("IMPORT_FILE_EMPTY"));
			
		if($arFields["IMPORT_FORMAT"] && $arFields["IMPORT_FILE"])
		{
			if($arFields["IMPORT_FORMAT"] == 'JSON')
				$arFields["IMPORT_FILE"] = '';
			else
			{
				$fileInfo = pathinfo($arFields["IMPORT_FILE"]);
				if(strtolower($arFields["IMPORT_FORMAT"]) != strtolower($fileInfo["extension"]) && !(strtolower($arFields["IMPORT_FORMAT"]) == "xml" && $fileInfo["extension"] == "yml"))
					$aMsg[] = array("id"=>"IMPORT_FORMAT", "text"=>Loc::getMessage("INCORRECT_IMPORT_FORMAT"));
			}
		}
			
		if($arFields["IMPORT_IBLOCK_SECTIONS"] == "Y" && intVal($arFields["ELEMENTS_DEFAULT_SECTION_ID"])>0)
		{
			$aMsg[] = array("id"=>"ELEMENTS_DEFAULT_SECTION_ID", "text"=>Loc::getMessage("DISABLE_ELEMENTS_DEFAULT_SECTION_ID"));
		}
			
		if(strlen($arFields["PATH_TO_IMAGES"])>0 && substr($arFields["PATH_TO_IMAGES"], -1) != "/")
		{
			$aMsg[] = array("id"=>"PATH_TO_IMAGES", "text"=>Loc::getMessage("PATH_TO_IMAGES_END_SLASH"));
		}
			
		if(strlen($arFields["PATH_TO_FILES"])>0 && substr($arFields["PATH_TO_FILES"], -1) != "/")
		{
			$aMsg[] = array("id"=>"PATH_TO_FILES", "text"=>Loc::getMessage("PATH_TO_FILES_END_SLASH"));
		}
		
		if($arFields["IMPORT_CATALOG_PRODUCTS"] == "N" && $arFields["IMPORT_CATALOG_PRICES"] == "Y")
		{
			$aMsg[] = array("id"=>"IMPORT_CATALOG_PRODUCTS", "text"=>Loc::getMessage("IMPORT_PRODUCT_FOR_PRICE_REQUIRED"));
		}
		
		if($arFields["IMPORT_CATALOG_PRODUCTS"] == "N" && $arFields["IMPORT_CATALOG_STORE_AMOUNT"] == "Y")
			$aMsg[] = array("id"=>"IMPORT_CATALOG_PRODUCTS", "text"=>Loc::getMessage("IMPORT_PRODUCT_FOR_STORE_AMOUNT_REQUIRED"));
		
		if($arFields["IMPORT_CATALOG_PRODUCTS"] == "Y" && ($arFields["IMPORT_IBLOCK_ELEMENTS"] != "Y" && $arFields["IMPORT_CATALOG_PRODUCT_OFFERS"] != "Y"))
			$aMsg[] = array("id"=>"IMPORT_CATALOG_PRODUCTS", "text"=>Loc::getMessage("IMPORT_ELEMENTS_OR_OFFERS_REQUIRED"));
		
		if($arFields["IMPORT_CATALOG_PRODUCT_OFFERS"] == "Y" && $arFields["IBLOCK_ID"] > 0 && CModule::IncludeModule("catalog") && !CCatalogSKU::GetInfoByProductIBlock($arFields["IBLOCK_ID"]))
			$aMsg[] = array("id"=>"IMPORT_CATALOG_PRODUCT_OFFERS", "text"=>Loc::getMessage("IBLOCK_HAVE_NOT_SKU"));
		
		if($arFields["IMPORT_IBLOCK_ELEMENTS"] != "Y" && $arFields["IMPORT_CATALOG_PRODUCT_OFFERS"] == "Y")
			$aMsg[] = array("id"=>"IMPORT_IBLOCK_ELEMENTS", "text"=>Loc::getMessage("IMPORT_ELEMENTS_FOR_OFFERS_REQUIRED"));
		
		if(
			($arFields["IMPORT_IBLOCK_ELEMENTS"] == "Y" && ($arFields["ELEMENTS_ADD"] == "Y" || $arFields["ELEMENTS_UPDATE"] == "Y" )) && 
			($arFields["IMPORT_CATALOG_PRODUCT_OFFERS"] == "Y" && ($arFields["OFFERS_ADD"] == "Y" || $arFields["OFFERS_UPDATE"] == "Y" ))
		)
			$aMsg[] = array("id"=>"IMPORT_IBLOCK_ELEMENTS", "text"=>Loc::getMessage("ONLY_ELEMENTS_OR_OFFERS"));
		
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
		
		switch($arFields["IMPORT_FORMAT"])
		{
			case("CSV"):
				$arFields["CSV_IGNORE_FLINE"] = "Y";
				$arFields["CSV_DELIMITER"] = "TZP";
				break;
			case("XML"):
				break;
			case("XLS"):
				$arFields["XLS_SHEET"] = "0";
				break;
		}
		
		/*if(isset($arFields["LAST_IMPORT_DATE"]))
			unset($arFields["LAST_IMPORT_DATE"]);
		
		if(isset($arFields["LAST_STEP_IMPORT_DATE"]))
			unset($arFields["LAST_STEP_IMPORT_DATE"]);
		
		if(isset($arFields["LAST_FINISH_IMPORT_DATE"]))
			unset($arFields["LAST_FINISH_IMPORT_DATE"]);*/
		
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
		
		$PLAN_ID = intval($DB->LastID());
		
		if($arFields["ACTIVE"] == "Y")
		{
			$planAgentIsUpdated = self::AddAgent($PLAN_ID, $arFields["AGENT_INTERVAL"]);
			if($arFields["IMPORT_FILE_URL"] != '')
				$planAgentIsUpdated = self::AddAgent($PLAN_ID, $arFields["AGENT_INTERVAL_URL"], "Load", $arFields["AGENT_TIME_URL"]);
		}
		
		return $PLAN_ID;
		
	}
	
	public function Update($ID, $arFields, $skipCheck = false)
	{
		global $DB, $APPLICATION;
		$ID = IntVal($ID);
		
		if(!$skipCheck)
		{
			$importAgent = new CDataImportAgent;
			if($arFields["ACTIVE"] == "Y")
			{
				$arFields["AGENT_ID"] = self::UpdateAgent($ID, $arFields["AGENT_INTERVAL"]);
				if($arFields["IMPORT_FILE_URL"] != '')
					$planAgentIsUpdated = self::UpdateAgent($ID, $arFields["AGENT_INTERVAL_URL"], "Load", $arFields["AGENT_TIME_URL"]);
				else
					$importAgent->Delete($ID, "Load");
			}
			else
			{
				if($importAgent->Delete($ID))
				{
					$arFields["AGENT_ID"] = 0;
				}
				$importAgent->Delete($ID, "Load");
			}
		
			if(!$this->CheckFields($arFields, $ID))
				return false;
		}
		
		$strUpdate = $DB->PrepareUpdate($this->DB_NAME, $arFields, LANGUAGE_ID);
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
		
		if(!$skipCheck)
		{
			//DeleteDirFilesEx('/bitrix/tmp/'.$this->MODULE_ID);
			unlink($_SERVER["DOCUMENT_ROOT"].'/bitrix/tmp/'.$this->MODULE_ID.'/'.$ID.'_cron.txt');
			unlink($_SERVER["DOCUMENT_ROOT"].'/bitrix/tmp/'.$this->MODULE_ID.'/'.$ID.'_entities.txt');
			unlink($_SERVER["DOCUMENT_ROOT"].'/bitrix/tmp/'.$this->MODULE_ID.'/'.$ID.'_total.txt');
			unlink($_SERVER["DOCUMENT_ROOT"].'/bitrix/tmp/'.$this->MODULE_ID.'/'.$ID.'_data.txt');
			unlink($_SERVER["DOCUMENT_ROOT"].'/bitrix/tmp/'.$this->MODULE_ID.'/'.$ID.'_sample.txt');
			unlink($_SERVER["DOCUMENT_ROOT"].'/bitrix/tmp/'.$this->MODULE_ID.'/'.$ID.'_sheets.txt');
		}
		
		return true;
		
	}
	
	public function UpdateLastImportDate($ID, $value, $key = 'LAST_IMPORT_DATE')
	{
		$arFields[$key] = $value;
		$result = self::Update($ID, $arFields, true);
		
		return $result;
	}
	
	public function UpdateJsonNextUrl($ID, $value = false)
	{
		$result = self::UpdateLastImportDate($ID, $value, 'JSON_NEXT_URL');
		return $result;
	}
	
	public function AddAgent($ID, $AGENT_INTERVAL, $TYPE = "Import", $TIME = false)
	{
		global $DB;
		
		$importAgent = new CDataImportAgent;
		$AGENT_ID = $importAgent->Update($ID, $AGENT_INTERVAL, $TYPE, $TIME);
		
		if(!$TYPE)
			$result = $DB->Update("data_import_plans", Array("AGENT_ID" => $AGENT_ID), "WHERE ID='".$ID."'", $err_mess.__LINE__, false, true);
	}
	
	public function UpdateAgent($ID, $AGENT_INTERVAL, $TYPE = "Import", $TIME = false)
	{
		$importAgent = new CDataImportAgent;
		$AGENT_ID = $importAgent->Update($ID, $AGENT_INTERVAL, $TYPE, $TIME);
		
		return $AGENT_ID;
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
		{
			$DB->Commit();
			
			$importAgent = new CDataImportAgent;
			$importAgent->Delete($ID);
			$importAgent->Delete($ID, "Load");
			
			$CConnections = new CDataImportPlanConnections;
			$CConnections->DeleteByPlanId($ID);
		}
		else
			$DB->Rollback();

		return $res;
	}
	
	public static function getFormats()
	{
		return [
			"CSV" => Loc::getMessage("DATA_IMPORT_FORMAT_CSV").' [CSV]',
			"XML" => Loc::getMessage("DATA_IMPORT_FORMAT_XML").' [XML]',
			"XLS" => Loc::getMessage("DATA_IMPORT_FORMAT_XLS").' [XLS]',
			"XLSX" => Loc::getMessage("DATA_IMPORT_FORMAT_XLSX").' [XLSX]',
			"ODS" => Loc::getMessage("DATA_IMPORT_FORMAT_ODS").' [ODS]',
			"XODS" => Loc::getMessage("DATA_IMPORT_FORMAT_XODS").' [XODS]',
			"JSON" => Loc::getMessage("DATA_IMPORT_FORMAT_JSON").' [JSON]',
		];
	}
}
?>