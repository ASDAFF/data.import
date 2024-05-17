<?
define("NO_KEEP_STATISTIC", true);
define("STOP_STATISTICS", true);
define("NO_AGENT_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS",true);
define("BX_CAT_CRON", true);
define('NO_AGENT_CHECK', true);

use Bitrix\Main\Localization\Loc;

class CWebprostorImport
{
	CONST MODULE_ID = "webprostor.import";
	CONST ITEMS_PER_ROUND = 50;
	//CONST DEFAULT_CURRENCY = 'RUB';
	CONST DELETE_OLD_PROPERTY_FILE_VALUE = true;
	CONST CURL_TIMEOUT = 5;
	
	CONST NOTIFY_LIMIT = 10000;
	
	CONST DEBUG_MEMORY = false;
	CONST DEBUG_EXECUTION_TIME = false;
	CONST DEBUG_COLLECT_CYCLES = false;
	
	/*private $module_id = 'webprostor.import';
	private $notify_limit = 10000;
	
	public function __construct()
	{
		$this->notify_limit = COption::GetOptionString($this->module_id, "NOTIFY_LIMIT", 10000);
	}*/
	
	public static function CheckEntitiesNames($PLAN_ID = false, $entities = Array()) : array
	{
		$errors = [];
		
		$planCData = new CWebprostorImportPlanConnections;
		$planCRes = $planCData->GetList(
			["SORT" => "ASC"], 
			["PLAN_ID" => $PLAN_ID, "ACTIVE" => "Y"], 
			["ENTITY", "NAME"]
		);
		while($connect = $planCRes->GetNext(true, false))
		{
			if(!(
				isset($entities[$connect["ENTITY"]]) && 
				htmlspecialcharsbx(str_replace(["\r", "\n"], '', $entities[$connect["ENTITY"]])) == $connect["NAME"]
			))
			{
				$errors[$connect["NAME"]] = $entities[$connect["ENTITY"]];
			}
		}
		unset($planCData, $connect, $entities);
		
		return $errors;
	}
	
	private static function getActiveConnectionsCount($PLAN_ID = false) : int
	{
		$result = 0;
		
		if($PLAN_ID > 0)
		{
			$CConnection = new CWebprostorImportPlanConnections;
			$connectionsRes = $CConnection->GetList(Array("SORT" => "ASC"), Array("PLAN_ID" => $PLAN_ID, 'ACTIVE' => 'Y'));
			$result = $connectionsRes->SelectedRowsCount();
			
			unset($CConnection);
		}
		
		return $result;
	}
	
	private static function addUserEntitesByPlanId(&$PLAN_ID, &$entities)
	{
		$planCData = new CWebprostorImportPlanConnections;
		$planCRes = $planCData->GetList(
			["SORT"=>"ASC"], 
			["PLAN_ID" => $PLAN_ID, "ACTIVE" => "Y"],
			["NAME"]
		);
		while($planConnection = $planCRes->GetNext(true, false))
		{
			if($planConnection['NAME'] != '' && !in_array($planConnection['NAME'], $entities))
				$entities[] = $planConnection['NAME'];
		}
		unset($planCData);
	}
	
	private static function GetPlanEntityConnection($PLAN_ID = false, $entities = Array(), $USE_ENTITY_NAME = "N")
	{
		$result = Array();
		
		foreach($entities as $code => $entity)
		{
			if($USE_ENTITY_NAME == 'Y')
			{
				$result[$entity] = Array(
					"RULES" => Array(),
				);
			}
			else
			{
				$result[$code] = Array(
					"NAME" => $entity,
					"RULES" => Array(),
				);
			}
		}
		
		$planCData = new CWebprostorImportPlanConnections;
		$planCRes = $planCData->GetList(
			["SORT"=>"ASC"], 
			["PLAN_ID" => $PLAN_ID, "ACTIVE" => "Y"]
		);
		while($planConnection = $planCRes->GetNext(true, false))
		{
			if($USE_ENTITY_NAME == 'Y')
				$condition = is_array($result[$planConnection["NAME"]]);
			else
				$condition = is_array($result[$planConnection["ENTITY"]]);
			
			if($condition)
			{
				$ruleFields = Array(
					"IBLOCK_SECTION_FIELD" => $planConnection["IBLOCK_SECTION_FIELD"],
					"IBLOCK_SECTION_DEPTH_LEVEL" => $planConnection["IBLOCK_SECTION_DEPTH_LEVEL"],
					"IBLOCK_SECTION_PARENT_FIELD" => $planConnection["IBLOCK_SECTION_PARENT_FIELD"],
					"IBLOCK_ELEMENT_FIELD" => $planConnection["IBLOCK_ELEMENT_FIELD"],
					"IBLOCK_ELEMENT_PROPERTY" => $planConnection["IBLOCK_ELEMENT_PROPERTY"],
					"IBLOCK_ELEMENT_PROPERTY_E" => $planConnection["IBLOCK_ELEMENT_PROPERTY_E"],
					"IBLOCK_ELEMENT_PROPERTY_G" => $planConnection["IBLOCK_ELEMENT_PROPERTY_G"],
					"IBLOCK_ELEMENT_PROPERTY_M" => $planConnection["IBLOCK_ELEMENT_PROPERTY_M"],
					"IBLOCK_ELEMENT_OFFER_FIELD" => $planConnection["IBLOCK_ELEMENT_OFFER_FIELD"],
					"IBLOCK_ELEMENT_OFFER_PROPERTY" => $planConnection["IBLOCK_ELEMENT_OFFER_PROPERTY"],
					"CATALOG_PRODUCT_FIELD" => $planConnection["CATALOG_PRODUCT_FIELD"],
					"CATALOG_PRODUCT_OFFER_FIELD" => $planConnection["CATALOG_PRODUCT_OFFER_FIELD"],
					"CATALOG_PRODUCT_PRICE" => $planConnection["CATALOG_PRODUCT_PRICE"],
					"CATALOG_PRODUCT_STORE_AMOUNT" => $planConnection["CATALOG_PRODUCT_STORE_AMOUNT"],
					"HIGHLOAD_BLOCK_ENTITY_FIELD" => $planConnection["HIGHLOAD_BLOCK_ENTITY_FIELD"],
					"IS_IMAGE" => $planConnection["IS_IMAGE"],
					"IS_FILE" => $planConnection["IS_FILE"],
					"IS_URL" => $planConnection["IS_URL"],
					"IS_ARRAY" => $planConnection["IS_ARRAY"],
					"IS_REQUIRED" => $planConnection["IS_REQUIRED"],
					"USE_IN_SEARCH" => $planConnection["USE_IN_SEARCH"],
					"USE_IN_CODE" => $planConnection["USE_IN_CODE"],
					"SORT" => $planConnection["SORT"],
				);
				if($planConnection["PROCESSING_TYPES"])
				{
					$ruleFields["PROCESSING_TYPES"] = unserialize(base64_decode($planConnection["PROCESSING_TYPES"]));
					if(!is_array($ruleFields["PROCESSING_TYPES"]))
						$ruleFields["PROCESSING_TYPES"] = Array();
				}
				else
				{
					$ruleFields["PROCESSING_TYPES"] = array();
				}
				if(isset($planConnection["ENTITY_ATTRIBUTE"]))
					$ruleFields["ENTITY_ATTRIBUTE"] = $planConnection["ENTITY_ATTRIBUTE"];
				
				if($USE_ENTITY_NAME == 'Y')
					$result[$planConnection["NAME"]]["RULES"][] = $ruleFields;
				else
					$result[$planConnection["ENTITY"]]["RULES"][] = $ruleFields;
			}
		}
		unset($condition);
		foreach($result as $k => $item)
		{
			if(empty($item["RULES"]))
				unset($result[$k]);
		}
		
		/*if($USE_ENTITY_NAME == "Y")
		{
			foreach($result as $k => $item)
			{
				$result[$item["NAME"]] = $item;
				unset($result[$item["NAME"]]["NAME"]);
				unset($result[$k]);
			}
		}*/
		unset($planCData);
		return $result;
	}
	
	private static function ExtractArchiveTo($url, $path_to, &$planParams)
	{
		$result = false;
		
		if($planParams["VALIDATE_URL"] != "N")
			$archive = filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED);
		else
			$archive = $url;
		
		if($archive && is_dir($_SERVER["DOCUMENT_ROOT"].$path_to))
		{
			$temp = CFile::MakeFileArray($archive);
			
			if(is_array($temp))
			{
				switch($temp["type"])
				{
					case("application/x-rar"):
						if(extension_loaded('rar'))
						{
							$rar_file = rar_open($temp["tmp_name"]);
							$list = rar_list($rar_file);
							foreach($list as $file)
							{
								$entry = rar_entry_get($rar_file, $file);
								$entry->extract($_SERVER["DOCUMENT_ROOT"].$path_to);
							}
							rar_close($rar_file);
						}
						else
						{
							$errorArray = Array(
								"MESSAGE" => Loc::getMessage("RAR_NOT_INCLUDED"),
								"TAG" => "RAR_NOT_INCLUDED",
								"MODULE_ID" => self::MODULE_ID,
								"ENABLE_CLOSE" => "Y"
							);
							$notifyID = CAdminNotify::Add($errorArray);
						}
						break;
					case("application/zip"):
						if(extension_loaded('zip'))
						{
							$zip = new ZipArchive;
							if($zip->open($temp["tmp_name"]) === TRUE)
							{
								$result = $zip->extractTo($_SERVER["DOCUMENT_ROOT"].$path_to);
								$zip->close();
							}
							unset($zip);
						}
						else
						{
							$errorArray = Array(
								"MESSAGE" => Loc::getMessage("ZIP_NOT_INCLUDED"),
								"TAG" => "ZIP_NOT_INCLUDED",
								"MODULE_ID" => self::MODULE_ID,
								"ENABLE_CLOSE" => "Y"
							);
							$notifyID = CAdminNotify::Add($errorArray);
						}
						break;
					case("application/x-gzip"):
						if(extension_loaded('zlib'))
						{
							$buffer_size = 4096;
							$out_file_name = $_SERVER["DOCUMENT_ROOT"].$path_to.str_replace('.gz', '', $temp["name"]); 

							$file = gzopen($temp["tmp_name"], 'rb');
							$out_file = fopen($out_file_name, 'wb'); 

							while (!gzeof($file))
							{
								fwrite($out_file, gzread($file, $buffer_size));
							}

							fclose($out_file);
							gzclose($file);
						}
						else
						{
							$errorArray = Array(
								"MESSAGE" => Loc::getMessage("ZLIB_NOT_INCLUDED"),
								"TAG" => "ZLIB_NOT_INCLUDED",
								"MODULE_ID" => self::MODULE_ID,
								"ENABLE_CLOSE" => "Y"
							);
							$notifyID = CAdminNotify::Add($errorArray);
						}
						break;
				}
			}
		}
		return $result;
	}
	
	public static function Load($PLAN_ID = false)
	{
		$resultText = "CWebprostorImport::Load({$PLAN_ID});";
		
		$planData = new CWebprostorImportPlan;
		$planParams = $planData->GetByID($PLAN_ID)->Fetch();
		
		if($planParams["IMPORT_FILE_URL"] == '')
			return $resultText;
		unset($planData);
		
		if($planParams["DEBUG_EVENTS"] == "Y")
			$DEBUG_EVENTS = true;
		
		if($planParams["DEBUG_URL"] == "Y")
			$DEBUG_URL = true;
		
		if($planParams["CURL_TIMEOUT"] > 0)
			$CURL_TIMEOUT = $planParams["CURL_TIMEOUT"];
		else
			$CURL_TIMEOUT = self::CURL_TIMEOUT;
		
		if($DEBUG_EVENTS && $DEBUG_URL)
		{
			$logLoad = new CWebprostorImportLog;
			$logLoadData = Array("PLAN_ID" => $PLAN_ID);
		}
		
		$IMPORT_FILE = $_SERVER["DOCUMENT_ROOT"].$planParams["IMPORT_FILE"];
		$IMPORT_FILE_URL = $planParams["IMPORT_FILE_URL"];
		
		CWebprostorImportUtils::replaceMacroses($IMPORT_FILE_URL, $planParams);
		/*
		$IMPORT_FILE_URL = str_replace('#ITEMS_PER_ROUND#', $planParams['ITEMS_PER_ROUND'], $IMPORT_FILE_URL);
		$IMPORT_FILE_URL = str_replace('#DATE_1#', date('d.m.y'), $IMPORT_FILE_URL);
		$IMPORT_FILE_URL = str_replace('#DATE_2#', date('d.m.Y'), $IMPORT_FILE_URL);*/
		
		$IMPORT_FILE_REPLACE = $planParams["IMPORT_FILE_REPLACE"];
		
		$PATH_TO_IMAGES = $planParams["PATH_TO_IMAGES"];
		$PATH_TO_IMAGES_URL = $planParams["PATH_TO_IMAGES_URL"];
		
		$PATH_TO_FILES = $planParams["PATH_TO_FILES"];
		$PATH_TO_FILES_URL = $planParams["PATH_TO_FILES_URL"];
		
		$NEED_AUTH = false;
		if($planParams["URL_LOGIN"] != '' && $planParams["URL_PASSWORD"])
		{
			$NEED_AUTH = true;
		
			$URL_LOGIN = $planParams["URL_LOGIN"];
			$URL_PASSWORD = $planParams["URL_PASSWORD"];
		}
			
		if(extension_loaded('curl'))
		{

			if($planParams["VALIDATE_URL"] != "N")
				$urlF = filter_var($IMPORT_FILE_URL, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED);
			else
				$urlF = $IMPORT_FILE_URL;
				
			if($DEBUG_EVENTS && $DEBUG_URL)
			{
				$logLoadData["EVENT"] = "LOAD_IMPORT_FILE";
				$logLoadData["MESSAGE"] = Loc::getMessage("EVENT_GET_URL_FILE");
				$logLoadData["DATA"] = $urlF;
				$logLoad->Add($logLoadData);
			}
			
			if(is_file($IMPORT_FILE) && $IMPORT_FILE_REPLACE == "Y")
			{
				unlink($IMPORT_FILE);
			}
		
			if($planParams["IMPORT_FILE"] != '' && $urlF && !is_file($IMPORT_FILE))
			{
				$urlParams = parse_url($urlF);
				
				$handle = curl_init();
				
				curl_setopt($handle, CURLOPT_URL, $urlF);
				curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
				curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, $CURL_TIMEOUT);
				if($NEED_AUTH)
				{
					curl_setopt($handle, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
					curl_setopt($handle, CURLOPT_USERPWD, $URL_LOGIN . ":" . $URL_PASSWORD);
				}
				if($planParams["CURL_FOLLOWLOCATION"] == "Y")
				{
					curl_setopt($handle, CURLOPT_FOLLOWLOCATION, TRUE);
				}
				
				$response = curl_exec($handle);
				
				$httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
				
				if($httpCode == 200 || ($httpCode == 226 && strtolower($urlParams["scheme"]) == 'ftp'))
				{
					$temp = CFile::MakeFileArray($urlF);
					if(is_array($temp))
					{
						if($DEBUG_EVENTS && $DEBUG_URL)
						{
							$logLoadData["EVENT"] = "LOAD_IMPORT_FILE";
							$logLoadData["MESSAGE"] = Loc::getMessage("MESSAGE_URL_OK");
							//$logLoadData["DATA"] = base64_encode(serialize(array_merge(array_keys($temp), $temp)));
							$logLoadData["DATA"] = base64_encode(serialize($temp));
							$logLoad->Add($logLoadData);
						}
						rename($temp["tmp_name"], $IMPORT_FILE);
					}
					unset($temp);
				}
				elseif($DEBUG_EVENTS && $DEBUG_URL)
				{
					$logLoadData["EVENT"] = "LOAD_IMPORT_FILE";
					$logLoadData["MESSAGE"] = Loc::getMessage("ERROR_GET_URL_FILE", Array("#ERROR_CODE#" => $httpCode));
					$logLoadData["DATA"] = "";
					$logLoad->Add($logLoadData);
				}
			}
			
			if($PATH_TO_IMAGES && $PATH_TO_IMAGES_URL)
			{
				$result = self::ExtractArchiveTo($PATH_TO_IMAGES_URL, $PATH_TO_IMAGES, $planParams);
			}
			
			if($PATH_TO_FILES && $PATH_TO_FILES_URL)
			{
				$result = self::ExtractArchiveTo($PATH_TO_FILES_URL, $PATH_TO_FILES, $planParams);
			}
		}
		else
		{
			$errorArray = Array(
				"MESSAGE" => Loc::getMessage("CURL_NOT_INCLUDED"),
				"TAG" => "CURL_NOT_INCLUDED",
				"MODULE_ID" => self::MODULE_ID,
				"ENABLE_CLOSE" => "Y"
			);
			$notifyID = CAdminNotify::Add($errorArray);
		}
		unset($logLoad, $planParams);
		
		return $resultText;
	}
	
	public static function Import($PLAN_ID = false, $startFrom = 0, $endTo = false, $IMPORT_FILE = false)
	{
		if(self::DEBUG_EXECUTION_TIME)
			$scriptExecutionTime = microtime(true);
		
		if(self::DEBUG_MEMORY)
			CWebprostorCoreFunctions::dump(['START IMPORT', CWebprostorCoreFunctions::ConvertFileSize(memory_get_usage())]);
		
		$rsModule = CModule::IncludeModuleEx(self::MODULE_ID);
		if($rsModule == MODULE_DEMO_EXPIRED)
		{
			$errorArray = Array(
				"MESSAGE" => Loc::getMessage("MODULE_DEMO_EXPIRED"),
				"TAG" => "WEBPROSTOR_IMPORT_MODULE_DEMO_EXPIRED",
				"MODULE_ID" => self::MODULE_ID,
				"ENABLE_CLOSE" => "Y"
			);
			$notifyID = CAdminNotify::Add($errorArray);
			return;
		}
		elseif($rsModule == MODULE_NOT_FOUND)
		{
			return;
		}

		if(!$rsModule) {
			return;
		}
		
		$logData = new CWebprostorImportLog;
		$logFields = Array("PLAN_ID" => $PLAN_ID);
		
		$resultText = "CWebprostorImport::Import({$PLAN_ID});";
		
		if(!$PLAN_ID)
		{
			$logFields["EVENT"] = "IMPORT_PLAN";
			$logFields["MESSAGE"] = Loc::getMessage("ERROR_NO_PLAN");
			unset($logFields["DATA"]);
			$logData->Add($logFields);
			
			return $resultText;
		}
		
		$planData = new CWebprostorImportPlan;
		$planParams = $planData->GetByID($PLAN_ID)->Fetch();
		
		if(!is_array($planParams))
			return false;
		
		if($planParams["IMPORT_CATALOG_PRODUCT_OFFERS"] == "Y" && CModule::IncludeModule("catalog"))
		{
			$catalogSKU = CCatalogSKU::GetInfoByProductIBlock($planParams["IBLOCK_ID"]);
			
			$planParams["OFFERS_IBLOCK_ID"] = $catalogSKU["IBLOCK_ID"];
			$planParams["OFFERS_SKU_PROPERTY_ID"] = $catalogSKU["SKU_PROPERTY_ID"];
		}
		
		global $DEBUG_PLAN_ID, $DEBUG_IMAGES, $DEBUG_FILES, $DEBUG_URL, $DEBUG_EVENTS, $DEBUG_IMPORT_SECTION, $DEBUG_IMPORT_ELEMENTS, $DEBUG_IMPORT_PROPERTIES, $DEBUG_IMPORT_PRODUCTS, $DEBUG_IMPORT_OFFERS, $DEBUG_IMPORT_PRICES, $DEBUG_IMPORT_STORE_AMOUNT, $DEBUG_IMPORT_ENTITIES, $CURL_TIMEOUT, $CURL_FOLLOWLOCATION;
		
		$DEBUG_PLAN_ID = $PLAN_ID;
		$DEBUG_EVENTS = false;
		$DEBUG_IMAGES = false;
		$DEBUG_FILES = false;
		$DEBUG_URL = false;
		$DEBUG_IMPORT_SECTION = false;
		$DEBUG_IMPORT_ELEMENTS = false;
		$DEBUG_IMPORT_PROPERTIES = false;
		$DEBUG_IMPORT_PRODUCTS = false;
		$DEBUG_IMPORT_OFFERS = false;
		$DEBUG_IMPORT_PRICES = false;
		$DEBUG_IMPORT_STORE_AMOUNT = false;
		$DEBUG_IMPORT_ENTITIES = false;
		
		$CURL_TIMEOUT = self::CURL_TIMEOUT;
		$CURL_FOLLOWLOCATION = ($planParams["CURL_FOLLOWLOCATION"] == "Y"?true:false);
		
		if($planParams["DEBUG_EVENTS"] == "Y")
			$DEBUG_EVENTS = true;
		
		if($planParams["DEBUG_IMAGES"] == "Y")
			$DEBUG_IMAGES = true;
		
		if($planParams["DEBUG_FILES"] == "Y")
			$DEBUG_FILES = true;
		
		if($planParams["DEBUG_URL"] == "Y")
			$DEBUG_URL = true;
		
		if($planParams["DEBUG_IMPORT_SECTION"] == "Y")
			$DEBUG_IMPORT_SECTION = true;
		
		if($planParams["DEBUG_IMPORT_ELEMENTS"] == "Y")
			$DEBUG_IMPORT_ELEMENTS = true;
		
		if($planParams["DEBUG_IMPORT_PROPERTIES"] == "Y")
			$DEBUG_IMPORT_PROPERTIES = true;
		
		if($planParams["DEBUG_IMPORT_PRODUCTS"] == "Y")
			$DEBUG_IMPORT_PRODUCTS = true;
		
		if($planParams["DEBUG_IMPORT_OFFERS"] == "Y")
			$DEBUG_IMPORT_OFFERS = true;
		
		if($planParams["DEBUG_IMPORT_PRICES"] == "Y")
			$DEBUG_IMPORT_PRICES = true;
		
		if($planParams["DEBUG_IMPORT_STORE_AMOUNT"] == "Y")
			$DEBUG_IMPORT_STORE_AMOUNT = true;
		
		if($planParams["DEBUG_IMPORT_ENTITIES"] == "Y")
			$DEBUG_IMPORT_ENTITIES = true;
		
		if(!$planParams["ITEMS_PER_ROUND"]>0)
			$planParams["ITEMS_PER_ROUND"] = self::ITEMS_PER_ROUND;
		
		if($planParams["CURL_TIMEOUT"] > $CURL_TIMEOUT)
			$CURL_TIMEOUT = $planParams["CURL_TIMEOUT"];
		
		$rsHandlers = GetModuleEvents(self::MODULE_ID, "onBeforeImport");
		while($arHandler = $rsHandlers->Fetch())
		{
			ExecuteModuleEvent($arHandler, $PLAN_ID, $planParams);
		}
		
		if($DEBUG_EVENTS)
		{
			$logFields["EVENT"] = "START_PLAN_CYCLE";
			$logFields["MESSAGE"] = Loc::getMessage("EVENT_START_PLAN");
			$logFields["DATA"] = "--STEP START--";
			$logData->Add($logFields);
		}
		
		if(!$IMPORT_FILE)
			$IMPORT_FILE = $_SERVER["DOCUMENT_ROOT"].$planParams["IMPORT_FILE"];
		
		if(!is_file($IMPORT_FILE) && $planParams["IMPORT_FORMAT"] != 'JSON')
		{
			if($DEBUG_EVENTS)
			{
				$logFields["EVENT"] = "GET_IMPORT_FILE";
				$logFields["MESSAGE"] = Loc::getMessage("ERROR_NO_IMPORT_FILE");
				$logFields["DATA"] = $planParams["IMPORT_FILE"];
				$logData->Add($logFields);
			}
			return $resultText;
		}
		elseif($DEBUG_EVENTS)
		{
			$logFields["EVENT"] = "START_PARSE_FILE";
			$logFields["MESSAGE"] = Loc::getMessage("EVENT_START_PARSE_FILE");
			$logFields["DATA"] = $planParams["IMPORT_FILE"];
			$logData->Add($logFields);
		}
		
		$GLOBALS["PLAN_ID"] = $PLAN_ID; //it for GetEntities
		
		$connections_count = self::getActiveConnectionsCount($PLAN_ID);
		
		if($connections_count == 0)
		{
			if($DEBUG_EVENTS)
			{
				$logFields["EVENT"] = "CHECK_ACTIVE_CONNECTIONS";
				$logFields["MESSAGE"] = Loc::getMessage("ERROR_NO_ACTIVE_CONNECTIONS");
				$logFields["DATA"] = '';
				$logData->Add($logFields);
			}
			
			return $resultText;
		}
		
		if(self::DEBUG_MEMORY)
			CWebprostorCoreFunctions::dump(['GET CONNECTIONS', CWebprostorCoreFunctions::ConvertFileSize(memory_get_usage())]);
		
		switch($planParams["IMPORT_FORMAT"])
		{
			case("CSV"):
				$scriptData = new CWebprostorImportCSV;
				$fileData = $scriptData->ParseFile($IMPORT_FILE, $planParams["IMPORT_FILE_SHARSET"], $planParams["CSV_XLS_FINISH_LINE"], $planParams["CSV_DELIMITER"]);
				break;
			case("XML"):
				$scriptData = new CWebprostorImportXML;
				$fileData = $scriptData->ParseFile(
					$IMPORT_FILE, 
					$planParams["IMPORT_FILE_SHARSET"], 
					$planParams["XML_ENTITY_GROUP"], 
					$planParams["XML_ENTITY"], 
					$planParams["XML_PARSE_PARAMS_TO_PROPERTIES"], 
					$planParams["XML_ENTITY_PARAM"], 
					$planParams["ITEMS_PER_ROUND"], 
					$startFrom
				);
				break;
			case("XLS"):
				$scriptData = new CWebprostorImportXLS;
				$fileData = $scriptData->ParseFile($IMPORT_FILE, $planParams["IMPORT_FILE_SHARSET"], $planParams["XLS_SHEET"], false, $planParams["CSV_XLS_FINISH_LINE"]);
				break;
			case("XLSX"):
				$scriptData = new CWebprostorImportXLSX;
				$fileData = $scriptData->ParseFile($IMPORT_FILE, $planParams["IMPORT_FILE_SHARSET"], $planParams["XLS_SHEET"], false, $planParams["CSV_XLS_FINISH_LINE"]);
				break;
			case("ODS"):
			case("XODS"):
				$scriptData = new Webprostor\Import\Format\ODS;
				$fileData = $scriptData->ParseFile($IMPORT_FILE, $planParams["IMPORT_FILE_SHARSET"], $planParams["XLS_SHEET"], false, $planParams["CSV_XLS_FINISH_LINE"]);
				break;
			case("JSON"):
				$scriptData = new Webprostor\Import\Format\JSON;
				$fileData = $scriptData->GetData($planParams);
				break;
			default:
				return false;
		}
		
		global $entities;
		
		$entities = $scriptData->GetEntities($PLAN_ID, $IMPORT_FILE);
		
		if(self::DEBUG_MEMORY)
			CWebprostorCoreFunctions::dump(['GET ENTITIES', CWebprostorCoreFunctions::ConvertFileSize(memory_get_usage())]);
		
		if($planParams["IMPORT_FILE_CHECK"] != "N")
		{
			if($planParams["IMPORT_FORMAT"] == "XML")
			{
				if($planParams["XML_USE_ENTITY_NAME"] != "Y")
					$checkEntitiesResult = self::CheckEntitiesNames($PLAN_ID, $entities["KEYS"]);
			}
			else
				$checkEntitiesResult = self::CheckEntitiesNames($PLAN_ID, $entities);
			
			if(isset($checkEntitiesResult))
			{
				if(count($checkEntitiesResult))
				{
					if($DEBUG_EVENTS)
					{
						$logFields["EVENT"] = "CHECK_ENTITIES_NAMES";
						$logFields["MESSAGE"] = Loc::getMessage("ERROR_ENTITIES_AND_NAMES_NOT_IDENTICAL");
						//$logFields["DATA"] = base64_encode(serialize(array_merge(array_keys($checkEntitiesResult), $checkEntitiesResult)));
						$logFields["DATA"] = base64_encode(serialize($checkEntitiesResult));
						$logData->Add($logFields);
					}
					
					$errorArray = [
						"MESSAGE" => Loc::getMessage("ERROR_ENTITIES_AND_NAMES_NOT_IDENTICAL_NOTIFY", ["#PLAN_ID#" => $PLAN_ID, "#LANG_ID#" => LANGUAGE_ID]),
						"TAG" => "CHECK_ENTITIES_NAMES_".$PLAN_ID,
						"MODULE_ID" => self::MODULE_ID,
						"NOTIFY_TYPE" => "E",
						"ENABLE_CLOSE" => "Y"
					];
					$notifyID = CAdminNotify::Add($errorArray);
					
					return $resultText;
				}
				else
				{
					CAdminNotify::DeleteByTag("CHECK_ENTITIES_NAMES_".$PLAN_ID);
					
					if($DEBUG_EVENTS)
					{
						$logFields["EVENT"] = "CHECK_ENTITIES_NAMES_OK";
						$logFields["MESSAGE"] = Loc::getMessage("MESSAGE_CHECK_ENTITIES_NAMES_OK");
						$logFields["DATA"] = "";
						$logData->Add($logFields);
					}
				}
			}
		}
		
		if(!$fileData)
		{
			if($DEBUG_EVENTS)
			{
				$logFields["EVENT"] = "GET_FILE_DATA";
				$logFields["MESSAGE"] = Loc::getMessage("ERROR_NO_FILE_DATA");
				unset($logFields["DATA"]);
				$logData->Add($logFields);
			}
			return $resultText;
		}
		elseif($DEBUG_EVENTS)
		{
			$logFields["EVENT"] = "PARSE_FILE";
			$logFields["MESSAGE"] = Loc::getMessage("EVENT_PARSE_FILE", Array("#COUNT#" => $fileData["ITEMS_COUNT"]));
			$logFields["DATA"] = "";
			$logData->Add($logFields);
		}
		
		$fileDataArrayRes = $scriptData->GetDataArray($fileData["DATA"], $planParams, $startFrom);
		unset($scriptData);
		
		$fileDataArray = $fileDataArrayRes["DATA_ARRAY"];
		
		if(self::DEBUG_MEMORY)
			CWebprostorCoreFunctions::dump(['GET DATA ARRAY', CWebprostorCoreFunctions::ConvertFileSize(memory_get_usage())]);
		
		unset($fileData);
		
		if(!$fileDataArray && !is_array($fileDataArray))
		{
			if($DEBUG_EVENTS)
			{
				$logFields["EVENT"] = "GET_DATA_ARRAY";
				$logFields["MESSAGE"] = Loc::getMessage("ERROR_CANNOT_GET_DATA_ARRAY");
				unset($logFields["DATA"]);
				$logData->Add($logFields);
			}
			return $resultText;
		}
		elseif($DEBUG_EVENTS)
		{
			$logFields["EVENT"] = "GET_DATA_ARRAY";
			$logFields["MESSAGE"] = Loc::getMessage("EVENT_GET_DATA_ARRAY", Array("#COUNT#" => count($fileDataArray)));
			$logFields["DATA"] = "";
			$logData->Add($logFields);
		}
		
		if($fileDataArrayRes["START_FROM"]>0)
		{
			$resultText = "CWebprostorImport::Import({$PLAN_ID}, {$fileDataArrayRes["START_FROM"]});";
		}
		
		if($startFrom == 0)
		{
			$searchIdData = new CWebprostorImportPlanSearchId;
			$searchIdRes = $searchIdData->GetList(false, ['PLAN_ID' => $PLAN_ID], ['ID']);
			while($searchIdArr = $searchIdRes->GetNext())
			{
				$searchIdData->Delete($searchIdArr['ID']);
			}
			
			$startImportDateTime = time();
			
			$UpdateLastImportDate = $planData->UpdateLastImportDate($PLAN_ID, date("d.m.Y H:i:s"));
			
			if($UpdateLastImportDateTime) {
				$planParams["LAST_IMPORT_DATE"] = $startImportDateTime;
			}
		}
		
		$UpdateLastStartImportDate = $planData->UpdateLastImportDate($PLAN_ID, date("d.m.Y H:i:s"), 'LAST_STEP_IMPORT_DATE');
		
		if($planParams["IMPORT_FORMAT"] == "XML") {
			$entities = $entities["KEYS"];
			self::addUserEntitesByPlanId($PLAN_ID, $entities);
			$planEntityConnection = self::GetPlanEntityConnection($PLAN_ID, $entities, $planParams["XML_USE_ENTITY_NAME"]);
		}
		elseif($planParams["IMPORT_FORMAT"] == "JSON") {
			$planEntityConnection = self::GetPlanEntityConnection($PLAN_ID, $entities, true);
		}
		else
		{
			$planEntityConnection = self::GetPlanEntityConnection($PLAN_ID, $entities);
		}
		unset($entities);
		
		if(!$planEntityConnection && !is_array($planEntityConnection))
		{
			if($DEBUG_EVENTS)
			{
				$logFields["EVENT"] = "GET_PLAN_ENTITY_CONNECTION";
				$logFields["MESSAGE"] = Loc::getMessage("ERROR_CANNOT_GET_PLAN_ENTITY_CONNECTION");
				unset($logFields["DATA"]);
				$logData->Add($logFields);
			}
			return $resultText;
		}
		elseif($DEBUG_EVENTS)
		{
			$logFields["EVENT"] = "GET_PLAN_ENTITY_CONNECTION";
			$logFields["MESSAGE"] = Loc::getMessage("EVENT_GET_PLAN_ENTITY_CONNECTION", Array("#COUNT#" => count((array)$planEntityConnection)));
			//$logFields["DATA"] = '';
			$logFields["DATA"] = base64_encode(serialize($planEntityConnection));
			$logData->Add($logFields);
		}
		
		$importParams = Array(
			"IMPORT_FORMAT" => $planParams["IMPORT_FORMAT"],
			"LAST_IMPORT_DATE" => $planParams["LAST_IMPORT_DATE"],
			
			"IMPORT_SECTIONS" => $planParams["IMPORT_IBLOCK_SECTIONS"],
			"SECTIONS_UPDATE_SEARCH" => $planParams["SECTIONS_UPDATE_SEARCH"],
			"SECTIONS_ADD" => $planParams["SECTIONS_ADD"],
			"SECTIONS_DEFAULT_ACTIVE" => $planParams["SECTIONS_DEFAULT_ACTIVE"],
			"SECTIONS_DEFAULT_SECTION_ID" => intVal($planParams["SECTIONS_DEFAULT_SECTION_ID"]),
			"SECTIONS_UPDATE" => $planParams["SECTIONS_UPDATE"],
			
			"IMPORT_ELEMENTS" => $planParams["IMPORT_IBLOCK_ELEMENTS"],
			"ELEMENTS_PREFILTER" => $planParams["ELEMENTS_PREFILTER"],
			"ELEMENTS_SKIP_IMPORT_WITHOUT_SECTION" => $planParams["ELEMENTS_SKIP_IMPORT_WITHOUT_SECTION"],
			"ELEMENTS_2_STEP_SEARCH" => $planParams["ELEMENTS_2_STEP_SEARCH"],
			"ELEMENTS_UPDATE_SEARCH" => $planParams["ELEMENTS_UPDATE_SEARCH"],
			"ELEMENTS_ADD" => $planParams["ELEMENTS_ADD"],
			"ELEMENTS_DEFAULT_ACTIVE" => $planParams["ELEMENTS_DEFAULT_ACTIVE"],
			"ELEMENTS_DEFAULT_SECTION_ID" => intVal($planParams["ELEMENTS_DEFAULT_SECTION_ID"]),
			"ELEMENTS_DEFAULT_DESCRIPTION_TYPE" => $planParams["ELEMENTS_DEFAULT_DESCRIPTION_TYPE"],
			"ELEMENTS_UPDATE" => $planParams["ELEMENTS_UPDATE"],
			"ELEMENTS_DONT_UPDATE_IMAGES_IF_EXISTS" => $planParams["ELEMENTS_DONT_UPDATE_IMAGES_IF_EXISTS"],
			
			"IMPORT_PROPERTIES" => $planParams["IMPORT_IBLOCK_PROPERTIES"],
			//"PROPERTIES_ADD" => $planParams["PROPERTIES_ADD"],
			"PROPERTIES_UPDATE" => $planParams["PROPERTIES_UPDATE"],
			"PROPERTIES_UPDATE_SKIP_IF_EVENT_SEARCH" => $planParams["PROPERTIES_UPDATE_SKIP_IF_EVENT_SEARCH"],
			"PROPERTIES_RESET" => $planParams["PROPERTIES_RESET"],
			"PROPERTIES_TRANSLATE_XML_ID" => $planParams["PROPERTIES_TRANSLATE_XML_ID"],
			"PROPERTIES_SET_DEFAULT_VALUES" => $planParams["PROPERTIES_SET_DEFAULT_VALUES"],
			"PROPERTIES_SKIP_DOWNLOAD_URL_UPDATE" => $planParams["PROPERTIES_SKIP_DOWNLOAD_URL_UPDATE"],
			"PROPERTIES_USE_MULTITHREADED_DOWNLOADING" => $planParams["PROPERTIES_USE_MULTITHREADED_DOWNLOADING"],
			"PROPERTIES_WHATERMARK" => $planParams["PROPERTIES_WHATERMARK"],
			"PROPERTIES_INCREMENT_TO_MULTIPLE" => $planParams["PROPERTIES_INCREMENT_TO_MULTIPLE"],
			"PROPERTIES_ADD_LIST_ENUM" => $planParams["PROPERTIES_ADD_LIST_ENUM"],
			"PROPERTIES_ADD_DIRECTORY_ENTITY" => $planParams["PROPERTIES_ADD_DIRECTORY_ENTITY"],
			"PROPERTIES_ADD_LINK_ELEMENT" => $planParams["PROPERTIES_ADD_LINK_ELEMENT"],
			
			"IMPORT_PRODUCTS" => $planParams["IMPORT_CATALOG_PRODUCTS"],
			"PRODUCTS_ADD" => $planParams["PRODUCTS_ADD"],
			"PRODUCTS_UPDATE" => $planParams["PRODUCTS_UPDATE"],
			"PRODUCTS_DEFAULT_CURRENCY" => $planParams["PRODUCTS_DEFAULT_CURRENCY"],
			
			"IMPORT_OFFERS" => $planParams["IMPORT_CATALOG_PRODUCT_OFFERS"],
			"OFFERS_ADD" => $planParams["OFFERS_ADD"],
			"OFFERS_UPDATE" => $planParams["OFFERS_UPDATE"],
			"OFFERS_SET_NAME_FROM_ELEMENT" => $planParams["OFFERS_SET_NAME_FROM_ELEMENT"],
			
			"IMPORT_PRICES" => $planParams["IMPORT_CATALOG_PRICES"],
			"PRICES_ADD" => $planParams["PRICES_ADD"],
			"PRICES_UPDATE" => $planParams["PRICES_UPDATE"],
			"PRICES_DEFAULT_CURRENCY" => $planParams["PRICES_DEFAULT_CURRENCY"],
			"PRICES_EXTRA_VALUE" => $planParams["PRICES_EXTRA_VALUE"],
			
			"IMPORT_STORE_AMOUNT" => $planParams["IMPORT_CATALOG_STORE_AMOUNT"],
			"STORE_AMOUNT_ADD" => $planParams["STORE_AMOUNT_ADD"],
			"STORE_AMOUNT_UPDATE" => $planParams["STORE_AMOUNT_UPDATE"],
			
			"IMPORT_HIGHLOAD_BLOCK_ENTITIES" => $planParams["IMPORT_HIGHLOAD_BLOCK_ENTITIES"],
			"ENTITIES_ADD" => $planParams["ENTITIES_ADD"],
			"ENTITIES_UPDATE" => $planParams["ENTITIES_UPDATE"],
			"ENTITIES_TRANSLATE_XML_ID" => $planParams["ENTITIES_TRANSLATE_XML_ID"],
			"ENTITIES_ADD_LIST_ENUM" => $planParams["ENTITIES_ADD_LIST_ENUM"],
			
			"IBLOCK_ID" => $planParams["IBLOCK_ID"],
			"HIGHLOAD_BLOCK" => $planParams["HIGHLOAD_BLOCK"],
			
			"OFFERS_IBLOCK_ID" => $planParams["OFFERS_IBLOCK_ID"],
			"OFFERS_SKU_PROPERTY_ID" => $planParams["OFFERS_SKU_PROPERTY_ID"],
			
			"PATH_TO_IMAGES" => $planParams["PATH_TO_IMAGES"],
			"RESIZE_IMAGE" => $planParams["RESIZE_IMAGE"],
			
			"PATH_TO_FILES" => $planParams["PATH_TO_FILES"],
			"VALIDATE_URL" => $planParams["VALIDATE_URL"],
			"RAW_URL_DECODE" => $planParams["RAW_URL_DECODE"]
		);
		if($planParams["PRODUCTS_PARAMS"])
		{
			$importParams["PRODUCTS_PARAMS"] = unserialize(base64_decode($planParams["PRODUCTS_PARAMS"]));
			if(!is_array($importParams["PRODUCTS_PARAMS"]))
				$importParams["PRODUCTS_PARAMS"] = Array();
		}
		else
		{
			$importParams["PRODUCTS_PARAMS"] = array();
		}
		
		if($planParams["IMPORT_FORMAT"] != "XML")
		{
			$importParams["SECTIONS_MAX_DEPTH_LEVEL"] = $planParams["CSV_XLS_MAX_DEPTH_LEVEL"];
		}
		else
		{
			$importParams["XML_PARSE_PARAMS_TO_PROPERTIES"] = $planParams["XML_PARSE_PARAMS_TO_PROPERTIES"];
			$importParams["XML_SEARCH_BY_PROPERTY_CODE_FIRST"] = $planParams["XML_SEARCH_BY_PROPERTY_CODE_FIRST"];
			$importParams["XML_SEARCH_ONLY_ACTIVE_PROPERTY"] = $planParams["XML_SEARCH_ONLY_ACTIVE_PROPERTY"];
			$importParams["XML_ADD_PROPERTIES_FOR_PARAMS"] = $planParams["XML_ADD_PROPERTIES_FOR_PARAMS"];
			$importParams["XML_PROPERTY_LIST_PAGE_SHOW"] = $planParams["XML_PROPERTY_LIST_PAGE_SHOW"];
			$importParams["XML_PROPERTY_DETAIL_PAGE_SHOW"] = $planParams["XML_PROPERTY_DETAIL_PAGE_SHOW"];
			$importParams["XML_PROPERTY_YAMARKET_COMMON"] = $planParams["XML_PROPERTY_YAMARKET_COMMON"];
			$importParams["XML_PROPERTY_YAMARKET_TURBO"] = $planParams["XML_PROPERTY_YAMARKET_TURBO"];
		}
		
		$importData = self::ImportData(
			$PLAN_ID,
			$fileDataArray, 
			$planEntityConnection,
			$importParams
		);
		
		unset($fileDataArray, $planEntityConnection, $importParams);
		
		if($DEBUG_EVENTS)
		{
			if($planParams["IMPORT_IBLOCK_SECTIONS"] == "Y" && $DEBUG_IMPORT_SECTION == "Y")
			{
				$logFields["EVENT"] = "SECTIONS_IS_IMPORTED";
				$logFields["MESSAGE"] = Loc::getMessage("EVENT_SECTIONS_IS_IMPORTED");
				//$logFields["DATA"] = base64_encode(serialize(array_merge(array_keys($importData["SECTIONS"]), $importData["SECTIONS"])));
				$logFields["DATA"] = base64_encode(serialize($importData["SECTIONS"]));
				$logData->Add($logFields);
			}
			
			if($planParams["IMPORT_IBLOCK_ELEMENTS"] == "Y" && $DEBUG_IMPORT_ELEMENTS == "Y")
			{
				$logFields["EVENT"] = "ELEMENTS_IS_IMPORTED";
				$logFields["MESSAGE"] = Loc::getMessage("EVENT_ELEMENTS_IS_IMPORTED");
				//$logFields["DATA"] = base64_encode(serialize(array_merge(array_keys($importData["ELEMENTS"]), $importData["ELEMENTS"])));
				$logFields["DATA"] = base64_encode(serialize($importData["ELEMENTS"]));
				$logData->Add($logFields);
			}
			
			if($planParams["IMPORT_IBLOCK_PROPERTIES"] == "Y" && $DEBUG_IMPORT_PROPERTIES == "Y")
			{
				$logFields["EVENT"] = "PROPERTIES_IS_IMPORTED";
				$logFields["MESSAGE"] = Loc::getMessage("EVENT_PROPERTIES_IS_IMPORTED");
				//$logFields["DATA"] = base64_encode(serialize(array_merge(array_keys($importData["PROPERTIES"]), $importData["PROPERTIES"])));
				$logFields["DATA"] = base64_encode(serialize($importData["PROPERTIES"]));
				$logData->Add($logFields);
			}
			
			if($planParams["IMPORT_CATALOG_PRODUCTS"] == "Y" && $DEBUG_IMPORT_PRODUCTS == "Y")
			{
				$logFields["EVENT"] = "PRODUCTS_IS_IMPORTED";
				$logFields["MESSAGE"] = Loc::getMessage("EVENT_PRODUCTS_IS_IMPORTED");
				//$logFields["DATA"] = base64_encode(serialize(array_merge(array_keys($importData["PRODUCTS"]), $importData["PRODUCTS"])));
				$logFields["DATA"] = base64_encode(serialize($importData["PRODUCTS"]));
				$logData->Add($logFields);
			}
			if($planParams["IMPORT_CATALOG_PRODUCT_OFFERS"] == "Y" && $DEBUG_IMPORT_OFFERS == "Y")
			{
				$logFields["EVENT"] = "OFFERS_IS_IMPORTED";
				$logFields["MESSAGE"] = Loc::getMessage("EVENT_OFFERS_IS_IMPORTED");
				//$logFields["DATA"] = base64_encode(serialize(array_merge(array_keys($importData["OFFERS"]), $importData["OFFERS"])));
				$logFields["DATA"] = base64_encode(serialize($importData["OFFERS"]));
				$logData->Add($logFields);
			}
			
			if($planParams["IMPORT_CATALOG_PRICES"] == "Y" && $DEBUG_IMPORT_PRICES == "Y")
			{
				$logFields["EVENT"] = "PRICES_IS_IMPORTED";
				$logFields["MESSAGE"] = Loc::getMessage("EVENT_PRICES_IS_IMPORTED");
				//$logFields["DATA"] = base64_encode(serialize(array_merge(array_keys($importData["PRICES"]), $importData["PRICES"])));
				$logFields["DATA"] = base64_encode(serialize($importData["PRICES"]));
				$logData->Add($logFields);
			}
			
			if($planParams["IMPORT_CATALOG_STORE_AMOUNT"] == "Y" && $DEBUG_IMPORT_STORE_AMOUNT == "Y")
			{
				$logFields["EVENT"] = "STORE_AMOUNT_IS_IMPORTED";
				$logFields["MESSAGE"] = Loc::getMessage("EVENT_STORE_AMOUNT_IS_IMPORTED");
				//$logFields["DATA"] = base64_encode(serialize(array_merge(array_keys($importData["STORE_AMOUNTS"]), $importData["STORE_AMOUNTS"])));
				$logFields["DATA"] = base64_encode(serialize($importData["STORE_AMOUNTS"]));
				$logData->Add($logFields);
			}
			
			if($planParams["IMPORT_HIGHLOAD_BLOCK_ENTITIES"] == "Y" && $DEBUG_IMPORT_ENTITIES == "Y")
			{
				$logFields["EVENT"] = "ENTITIES_IS_IMPORTED";
				$logFields["MESSAGE"] = Loc::getMessage("EVENT_ENTITIES_IS_IMPORTED");
				//$logFields["DATA"] = base64_encode(serialize(array_merge(array_keys($importData["ENTITIES"]), $importData["ENTITIES"])));
				$logFields["DATA"] = base64_encode(serialize($importData["ENTITIES"]));
				$logData->Add($logFields);
			}
		}
		unset($importData);
		
		if($DEBUG_EVENTS)
		{
			$logFields["EVENT"] = "FINISH_PLAN_CYCLE";
			$logFields["MESSAGE"] = Loc::getMessage("EVENT_FINISH_PLAN");
			$logFields["DATA"] = "--STEP FINISH--";
			$logData->Add($logFields);
		}
		
		if($fileDataArrayRes["FINISHED"])
		{
			if(!isset($searchIdData))
				$searchIdData = new CWebprostorImportPlanSearchId;
			
			/*$lastImportDateRes = $planData->GetLastImportDate($PLAN_ID);
			$lastImportDateArr = $lastImportDateRes -> Fetch();
			
			$lastImportDate = $lastImportDateArr["LAST_IMPORT_DATE"];*/
			//$lastImportDateTime = $planParams["LAST_IMPORT_DATE_TIME"];
			
			$rsHandlers = GetModuleEvents(self::MODULE_ID, "onBeforeFinishedImport");
			while($arHandler = $rsHandlers->Fetch())
			{
				ExecuteModuleEvent($arHandler, $PLAN_ID, $planParams, $planParams['LAST_IMPORT_DATE']/*$lastImportDate, $lastImportDateTime*/);
			}
			
			/*DeleteDirFilesEx('/bitrix/tmp/'.self::MODULE_ID.'/'.$PLAN_ID.'_cron.txt');
			DeleteDirFilesEx('/bitrix/tmp/'.self::MODULE_ID.'/'.$PLAN_ID.'_entities.txt');
			DeleteDirFilesEx('/bitrix/tmp/'.self::MODULE_ID.'/'.$PLAN_ID.'_sample.txt');
			DeleteDirFilesEx('/bitrix/tmp/'.self::MODULE_ID.'/'.$PLAN_ID.'_total.txt');
			DeleteDirFilesEx('/bitrix/tmp/'.self::MODULE_ID.'/'.$PLAN_ID.'_data.txt');
			DeleteDirFilesEx('/bitrix/tmp/'.self::MODULE_ID.'/'.$PLAN_ID.'_sheets.txt');*/
			
			foreach (glob($_SERVER['DOCUMENT_ROOT'].'/bitrix/tmp/'.self::MODULE_ID.'/'.$PLAN_ID.'*.txt') as $fileToDelete) {
				unlink($fileToDelete);
			}
			
			if($planParams["IMPORT_FILE_DELETE"] == "Y")
			{
				$deleteFile = unlink($IMPORT_FILE);
				if($DEBUG_EVENTS)
				{
					$logFields["EVENT"] = "IMPORT_FILE_DELETE";
					$logFields["DATA"] = $IMPORT_FILE;
					$logFields["MESSAGE"] = ($deleteFile?Loc::getMessage("EVENT_IMPORT_FILE_DELETE_OK"):Loc::getMessage("EVENT_IMPORT_FILE_DELETE_NO"));
					$logData->Add($logFields);
				}
			}
			if($planParams["CLEAR_IMAGES_DIR"] == "Y")
			{
				$clearImagesDir = CWebprostorImportFinishEvents::DeleteDirFilesOnly($planParams["PATH_TO_IMAGES"]);
				if($DEBUG_EVENTS)
				{
					$logFields["EVENT"] = "CLEAR_IMAGES_DIR";
					$logFields["DATA"] = $planParams["PATH_TO_IMAGES"];
					$logFields["MESSAGE"] = ($clearImagesDir?Loc::getMessage("EVENT_CLEAR_DIR_OK"):Loc::getMessage("EVENT_CLEAR_DIR_NO"));
					$logData->Add($logFields);
				}
			}
			if($planParams["CLEAR_UPLOAD_TMP_DIR"] == "Y")
			{
				$upload_dir = COption::GetOptionString("main", "upload_dir", "upload");
				$clearUploadTmpDir = CWebprostorImportFinishEvents::DeleteDirFilesOnly("/{$upload_dir}/tmp/");
				if($DEBUG_EVENTS)
				{
					$logFields["EVENT"] = "CLEAR_UPLOAD_TMP_DIR";
					$logFields["DATA"] = "/{$upload_dir}/tmp/";
					$logFields["MESSAGE"] = ($clearUploadTmpDir?Loc::getMessage("EVENT_CLEAR_DIR_OK"):Loc::getMessage("EVENT_CLEAR_DIR_NO"));
					$logData->Add($logFields);
				}
			}
			if($planParams["CLEAR_FILES_DIR"] == "Y")
			{
				$clearFilesDir = CWebprostorImportFinishEvents::DeleteDirFilesOnly($planParams["PATH_TO_FILES"]);
				if($DEBUG_EVENTS)
				{
					$logFields["EVENT"] = "CLEAR_FILES_DIR";
					$logFields["DATA"] = $planParams["PATH_TO_FILES"];
					$logFields["MESSAGE"] = ($clearFilesDir?Loc::getMessage("EVENT_CLEAR_DIR_OK"):Loc::getMessage("EVENT_CLEAR_DIR_NO"));
					$logData->Add($logFields);
				}
			}
			
			if($planParams["IMPORT_IBLOCK_SECTIONS"] == "Y")
			{
				if($planParams["SECTIONS_OUT_ACTION"] != "N" || $planParams["SECTIONS_IN_ACTION"] != "N")
				{
					$searchSectionIdList = [];
					$searchIdRes = $searchIdData->GetList(
						false, 
						[
							'PLAN_ID' => $PLAN_ID, 
							'OBJECT_TYPE' => 'SECTION'
						], 
						['OBJECT_ID'],
						false
					);
					while($searchIdArr = $searchIdRes->GetNext())
					{
						if($searchIdArr['OBJECT_ID'] > 0)
							$searchSectionIdList[] = $searchIdArr['OBJECT_ID'];
					}
				
					if(count($searchSectionIdList) > 0)
					{
						if($planParams["SECTIONS_OUT_ACTION"] != "N")
						{
							$changedOutSections = CWebprostorImportFinishEvents::ChangeOutSections(
								$searchSectionIdList,
								$planParams["SECTIONS_OUT_ACTION"], 
								$planParams["IBLOCK_ID"], 
								$planParams["SECTIONS_OUT_ACTION_FILTER"], 
								$planParams["SECTIONS_UPDATE_SEARCH"]
							);
							
							if($DEBUG_EVENTS)
							{
								$logFields["EVENT"] = "CHANGE_OUT_SECTIONS";
								//$logFields["DATA"] = base64_encode(serialize(array_merge(array_keys($changedOutSections), $changedOutSections)));
								$logFields["DATA"] = base64_encode(serialize($changedOutSections));
								$logFields["MESSAGE"] = Loc::getMessage("EVENT_CHANGE_OUT_SECTIONS");
								$logData->Add($logFields);
							}
						}
						
						if($planParams["SECTIONS_IN_ACTION"] != "N")
						{
							$changedInSections = CWebprostorImportFinishEvents::ChangeInSections(
								$searchSectionIdList,
								$planParams["SECTIONS_IN_ACTION"], 
								$planParams["IBLOCK_ID"], 
								$planParams["SECTIONS_IN_ACTION_FILTER"], 
								$planParams["SECTIONS_UPDATE_SEARCH"]
							);
							
							if($DEBUG_EVENTS)
							{
								$logFields["EVENT"] = "CHANGE_IN_SECTIONS";
								//$logFields["DATA"] = base64_encode(serialize(array_merge(array_keys($changedInSections), $changedInSections)));
								$logFields["DATA"] = base64_encode(serialize($changedInSections));
								$logFields["MESSAGE"] = Loc::getMessage("EVENT_CHANGE_IN_SECTIONS");
								$logData->Add($logFields);
							}
						}
						unset($searchSectionIdList);
					}
				}
			}
			
			if($planParams["IMPORT_IBLOCK_ELEMENTS"] == "Y")
			{
				if($planParams["ELEMENTS_OUT_ACTION"] != "N" || $planParams["ELEMENTS_IN_ACTION"] != "N")
				{
					$searchElementIdList = [];
					$searchIdRes = $searchIdData->GetList(
						false, 
						[
							'PLAN_ID' => $PLAN_ID, 
							'OBJECT_TYPE' => 'ELEMENT'
						], 
						['OBJECT_ID'],
						false
					);
					while($searchIdArr = $searchIdRes->GetNext())
					{
						if($searchIdArr['OBJECT_ID'] > 0)
							$searchElementIdList[] = $searchIdArr['OBJECT_ID'];
					}
				
					if(count($searchElementIdList) > 0)
					{
						if($planParams["ELEMENTS_OUT_ACTION"] != "N")
						{
							$changedOutElements = CWebprostorImportFinishEvents::ChangeOutElements(
								$searchElementIdList,
								$planParams["ELEMENTS_OUT_ACTION"], 
								$planParams["IBLOCK_ID"], 
								$planParams["ELEMENTS_OUT_ACTION_FILTER"], 
								$planParams["ELEMENTS_UPDATE_SEARCH"]
							);
							
							if($DEBUG_EVENTS)
							{
								$logFields["EVENT"] = "CHANGE_OUT_ELEMENTS";
								//$logFields["DATA"] = base64_encode(serialize(array_merge(array_keys($changedOutElements), $changedOutElements)));
								$logFields["DATA"] = base64_encode(serialize($changedOutElements));
								$logFields["MESSAGE"] = Loc::getMessage("EVENT_CHANGE_OUT_ELEMENTS");
								$logData->Add($logFields);
							}
						}
						
						if($planParams["ELEMENTS_IN_ACTION"] != "N")
						{
							$changedInElements = CWebprostorImportFinishEvents::ChangeInElements(
								$searchElementIdList,
								$planParams["ELEMENTS_IN_ACTION"], 
								$planParams["IBLOCK_ID"], 
								$planParams["ELEMENTS_IN_ACTION_FILTER"], 
								$planParams["ELEMENTS_UPDATE_SEARCH"]
							);
							
							if($DEBUG_EVENTS)
							{
								$logFields["EVENT"] = "CHANGE_IN_ELEMENTS";
								//$logFields["DATA"] = base64_encode(serialize(array_merge(array_keys($changedInElements), $changedInElements)));
								$logFields["DATA"] = base64_encode(serialize($changedInElements));
								$logFields["MESSAGE"] = Loc::getMessage("EVENT_CHANGE_IN_ELEMENTS");
								$logData->Add($logFields);
							}
						}
						unset($searchElementIdList);
					}
				}
			}

			if($planParams["IMPORT_CATALOG_PRODUCT_OFFERS"] == "Y")
			{
				if($planParams["OFFERS_OUT_ACTION"] != "N" || $planParams["OFFERS_IN_ACTION"] != "N")
				{
					$searchOfferIdList = [];
					$searchIdRes = $searchIdData->GetList(
						false, 
						[
							'PLAN_ID' => $PLAN_ID, 
							'OBJECT_TYPE' => 'OFFER'
						], 
						['OBJECT_ID'],
						false
					);
					while($searchIdArr = $searchIdRes->GetNext())
					{
						if($searchIdArr['OBJECT_ID'] > 0)
							$searchOfferIdList[] = $searchIdArr['OBJECT_ID'];
					}
				
					if(count($searchOfferIdList) > 0)
					{
						if($planParams["OFFERS_OUT_ACTION"] != "N")
						{
							$changedOutOffers = CWebprostorImportFinishEvents::ChangeOutElements(
								$searchOfferIdList,
								$planParams["OFFERS_OUT_ACTION"], 
								$planParams["OFFERS_IBLOCK_ID"], 
								$planParams["OFFERS_OUT_ACTION_FILTER"]
							);
							
							if($DEBUG_EVENTS)
							{
								$logFields["EVENT"] = "CHANGE_OUT_OFFERS";
								//$logFields["DATA"] = base64_encode(serialize(array_merge(array_keys($changedOutOffers), $changedOutOffers)));
								$logFields["DATA"] = base64_encode(serialize($changedOutOffers));
								$logFields["MESSAGE"] = Loc::getMessage("EVENT_CHANGE_OUT_OFFERS");
								$logData->Add($logFields);
							}
						}

						if($planParams["OFFERS_IN_ACTION"] != "N")
						{
							$changedInOffers = CWebprostorImportFinishEvents::ChangeInElements(
								$searchOfferIdList,
								$planParams["OFFERS_IN_ACTION"], 
								$planParams["OFFERS_IBLOCK_ID"], 
								$planParams["OFFERS_IN_ACTION_FILTER"]
							);
							
							if($DEBUG_EVENTS)
							{
								$logFields["EVENT"] = "CHANGE_IN_OFFERS";
								//$logFields["DATA"] = base64_encode(serialize(array_merge(array_keys($changedInOffers), $changedInOffers)));
								$logFields["DATA"] = base64_encode(serialize($changedInOffers));
								$logFields["MESSAGE"] = Loc::getMessage("EVENT_CHANGE_IN_OFFERS");
								$logData->Add($logFields);
							}
						}
						unset($searchOfferIdList);
					}
				}
			}
		
			if($DEBUG_EVENTS)
			{
				$logListRes = $logData->GetList(
					["SORT"=>"ASC"],
					false,
					["ID"]
				);
				
				$NOTIFY_LIMIT = COption::GetOptionString(self::MODULE_ID, "NOTIFY_LIMIT", self::NOTIFY_LIMIT);
				
				if(intVal($logListRes->SelectedRowsCount()) >= $NOTIFY_LIMIT)
				{
					$errorArray = Array(
						"MESSAGE" => Loc::getMessage("LOGS_ARE_TOO_BIG"),
						"TAG" => "LOGS_ARE_TOO_BIG",
						"MODULE_ID" => self::MODULE_ID,
						"ENABLE_CLOSE" => "Y"
					);
					$notifyID = CAdminNotify::Add($errorArray);
				}
			}
			
			$rsHandlers = GetModuleEvents(self::MODULE_ID, "onAfterFinishedImport");
			while($arHandler = $rsHandlers->Fetch())
			{
				ExecuteModuleEvent($arHandler, $PLAN_ID, $planParams, $lastImportDate, $lastImportDateTime);
			}
			
			//$searchIdData = new CWebprostorImportPlanSearchId;
			$searchIdRes = $searchIdData->GetList(false, ['PLAN_ID' => $PLAN_ID], ['ID']);
			while($searchIdArr = $searchIdRes->GetNext())
			{
				$searchIdData->Delete($searchIdArr['ID']);
			}
			unset($searchIdData);
			
			$UpdateLastFinishImportDate = $planData->UpdateLastImportDate($PLAN_ID, date("d.m.Y H:i:s"), "LAST_FINISH_IMPORT_DATE");
			
			if($planParams["AGENT_ONETIME_EXECUTION"] == "Y" && $planParams["ACTIVE"] == 'Y')
			{
				$planParamsNew = $planParams;
				$planParamsNew['ACTIVE'] = 'N';
				$planParamsNew['AGENT_ONETIME_EXECUTION'] = 'N';
				unset(
					$planParamsNew['LAST_IMPORT_DATE'],
					$planParamsNew['LAST_STEP_IMPORT_DATE'],
					$planParamsNew['LAST_FINISH_IMPORT_DATE']
				);
				$planData->Update($PLAN_ID, $planParamsNew);
			}
			
			if($planParams["PROCESSINGS_AFTER_FINISH"] != '' && CModule::IncludeModule('webprostor.massprocessing'))
			{
				$processingsAfterFinishArray = unserialize(base64_decode($planParams["PROCESSINGS_AFTER_FINISH"]));
				if(is_array($processingsAfterFinishArray))
				{
					$cProcessing = new \Webprostor\MassProcessing\Processing;
					$CRunner = new \Webprostor\MassProcessing\Runner;
					foreach($processingsAfterFinishArray as $processingID)
					{
						if(($processingDataRes = $cProcessing->GetById($processingID)) && ($processingDataArr = $processingDataRes->Fetch()))
						{
							$CRunner->prepareRunner($processingID, $processingDataArr);
						}
					}
					unset($cProcessing, $CRunner);
				}
			}
		}
		else
		{
			if($planParams["CLEAR_UPLOAD_TMP_DIR"] == "Y")
			{
				$upload_dir = COption::GetOptionString("main", "upload_dir", "upload");
				$clearUploadTmpDir = CWebprostorImportFinishEvents::DeleteDirFilesOnly("/{$upload_dir}/tmp/");
				if($DEBUG_EVENTS)
				{
					$logFields["EVENT"] = "CLEAR_UPLOAD_TMP_DIR";
					$logFields["DATA"] = "/{$upload_dir}/tmp/";
					$logFields["MESSAGE"] = ($clearUploadTmpDir?Loc::getMessage("EVENT_CLEAR_DIR_OK"):Loc::getMessage("EVENT_CLEAR_DIR_NO"));
					$logData->Add($logFields);
				}
			}
		}
		
		unset($planData, $planParams, $logFields, $logData);
		
		if(self::DEBUG_EXECUTION_TIME)
			CWebprostorCoreFunctions::dump(['SCRIPT EXECUTION TIME', round(microtime(true) - $scriptExecutionTime, 4).' s.']);
		
		if(self::DEBUG_MEMORY)
			CWebprostorCoreFunctions::dump(['FINISH IMPORT', CWebprostorCoreFunctions::ConvertFileSize(memory_get_usage())]);
		
		$collectedCycles = gc_collect_cycles();
		
		if(self::DEBUG_COLLECT_CYCLES)
			CWebprostorCoreFunctions::dump(['COLLECTED CYCLES', $collectedCycles]);
		
		return $resultText;
	}
	
	private static function GetImportResultCount(&$result, $code, $event)
	{
		switch($event)
		{
			case("ADD"):
				$result[$code]["ADD"]++;
				break;
			case("UPDATE"):
				$result[$code]["UPDATE"]++;
				break;
			/*case("SKIP"):
				$result[$code]["SKIP"]++;
				break;*/
			case("ERROR"):
				$result[$code]["ERROR"]++;
				break;
		}
	}
	
	private static function GetImportResultCountPriceStore(&$result, $code, $events)
	{
		foreach($events as $event => $count)
		{
			$result[$code][$event] = $result[$code][$event]+$count;
		}
	}
	
	private static function ImportData($PLAN_ID, $data, &$rules, &$params)
	{
		
		$result = Array(
			"SECTIONS" => Array(
				"ADD" => 0,
				"UPDATE" => 0,
				"ERROR" => 0,
			),
			"ELEMENTS" => Array(
				"ADD" => 0,
				"UPDATE" => 0,
				"ERROR" => 0,
			),
			"PROPERTIES" => Array(
				"ADD" => 0,
				"UPDATE" => 0,
				"ERROR" => 0,
			),
			"PRODUCTS" => Array(
				"ADD" => 0,
				"UPDATE" => 0,
				"ERROR" => 0,
			),
			"OFFERS" => Array(
				"ADD" => 0,
				"UPDATE" => 0,
				"ERROR" => 0,
			),
			"PRICES" => Array(
				"ADD" => 0,
				"UPDATE" => 0,
				"SKIP" => 0,
				"ERROR" => 0,
			),
			"STORE_AMOUNTS" => Array(
				"ADD" => 0,
				"UPDATE" => 0,
				"SKIP" => 0,
				"ERROR" => 0,
			),
			"ENTITIES" => Array(
				"ADD" => 0,
				"UPDATE" => 0,
				"ERROR" => 0,
			)
		);
		
		global $DEBUG_EVENTS, $DEBUG_IMPORT_SECTION, $DEBUG_IMPORT_ELEMENTS, $DEBUG_IMPORT_PROPERTIES, $DEBUG_IMPORT_PRODUCTS, $DEBUG_IMPORT_OFFERS, $DEBUG_IMPORT_PRICES, $DEBUG_IMPORT_STORE_AMOUNT, $DEBUG_IMPORT_ENTITIES, $ERROR_MESSAGE;
		
		if($DEBUG_EVENTS)
		{
			$logData = new CWebprostorImportLog;
			$logFields = Array("PLAN_ID" => $PLAN_ID);
		}
		
		if($params["IMPORT_SECTIONS"] == "Y" || $params["IMPORT_ELEMENTS"] == "Y" || $params["IMPORT_PROPERTIES"] == "Y" || $params["IMPORT_OFFERS"] == "Y")
		{
			CModule::IncludeModule("iblock");
			
			$iblockParams = CIBlock::GetFields($params["IBLOCK_ID"]);
			
			if($params["IMPORT_SECTIONS"] == "Y")
			{
				$iblockSectionParams = Array(
					"NAME" => $iblockParams["SECTION_NAME"],
					"PICTURE" => $iblockParams["SECTION_PICTURE"],
					"DESCRIPTION_TYPE" => $iblockParams["SECTION_DESCRIPTION_TYPE"],
					"DESCRIPTION" => $iblockParams["SECTION_DESCRIPTION"],
					"DETAIL_PICTURE" => $iblockParams["SECTION_DETAIL_PICTURE"],
					"CODE" => $iblockParams["SECTION_CODE"],
				);
			}
			if($params["IMPORT_ELEMENTS"] == "Y")
			{
				$iblockElementParams = Array(
					"IBLOCK_SECTION" => $iblockParams["IBLOCK_SECTION"],
					"ACTIVE" => $iblockParams["ACTIVE"],
					"ACTIVE_FROM" => $iblockParams["ACTIVE_FROM"],
					"ACTIVE_TO" => $iblockParams["ACTIVE_TO"],
					"SORT" => $iblockParams["SORT"],
					"NAME" => $iblockParams["NAME"],
					"PREVIEW_PICTURE" => $iblockParams["PREVIEW_PICTURE"],
					"PREVIEW_TEXT_TYPE" => $iblockParams["PREVIEW_TEXT_TYPE"],
					"PREVIEW_TEXT" => $iblockParams["PREVIEW_TEXT"],
					"DETAIL_PICTURE" => $iblockParams["DETAIL_PICTURE"],
					"DETAIL_TEXT_TYPE" => $iblockParams["DETAIL_TEXT_TYPE"],
					"DETAIL_TEXT" => $iblockParams["DETAIL_TEXT"],
					"CODE" => $iblockParams["CODE"],
					"TAGS" => $iblockParams["TAGS"],
				);
			}
			if($params["IMPORT_PROPERTIES"] == "Y")
			{
				$iblockPropertiesParams = Array(
					"PREVIEW_PICTURE" => Array(
						"USE_WATERMARK_TEXT" => $iblockParams["PREVIEW_PICTURE"]["DEFAULT_VALUE"]["USE_WATERMARK_TEXT"],
						"WATERMARK_TEXT" => $iblockParams["PREVIEW_PICTURE"]["DEFAULT_VALUE"]["WATERMARK_TEXT"],
						"WATERMARK_TEXT_FONT" => $iblockParams["PREVIEW_PICTURE"]["DEFAULT_VALUE"]["WATERMARK_TEXT_FONT"],
						"WATERMARK_TEXT_COLOR" => $iblockParams["PREVIEW_PICTURE"]["DEFAULT_VALUE"]["WATERMARK_TEXT_COLOR"],
						"WATERMARK_TEXT_SIZE" => $iblockParams["PREVIEW_PICTURE"]["DEFAULT_VALUE"]["WATERMARK_TEXT_SIZE"],
						"WATERMARK_TEXT_POSITION" => $iblockParams["PREVIEW_PICTURE"]["DEFAULT_VALUE"]["WATERMARK_TEXT_POSITION"],
						"USE_WATERMARK_FILE" => $iblockParams["PREVIEW_PICTURE"]["DEFAULT_VALUE"]["USE_WATERMARK_FILE"],
						"WATERMARK_FILE" => $iblockParams["PREVIEW_PICTURE"]["DEFAULT_VALUE"]["WATERMARK_FILE"],
						"WATERMARK_FILE_ALPHA" => $iblockParams["PREVIEW_PICTURE"]["DEFAULT_VALUE"]["WATERMARK_FILE_ALPHA"],
						"WATERMARK_FILE_POSITION" => $iblockParams["PREVIEW_PICTURE"]["DEFAULT_VALUE"]["WATERMARK_FILE_POSITION"],
					),
					"DETAIL_PICTURE" => Array(
						"USE_WATERMARK_TEXT" => $iblockParams["DETAIL_PICTURE"]["DEFAULT_VALUE"]["USE_WATERMARK_TEXT"],
						"WATERMARK_TEXT" => $iblockParams["DETAIL_PICTURE"]["DEFAULT_VALUE"]["WATERMARK_TEXT"],
						"WATERMARK_TEXT_FONT" => $iblockParams["DETAIL_PICTURE"]["DEFAULT_VALUE"]["WATERMARK_TEXT_FONT"],
						"WATERMARK_TEXT_COLOR" => $iblockParams["DETAIL_PICTURE"]["DEFAULT_VALUE"]["WATERMARK_TEXT_COLOR"],
						"WATERMARK_TEXT_SIZE" => $iblockParams["DETAIL_PICTURE"]["DEFAULT_VALUE"]["WATERMARK_TEXT_SIZE"],
						"WATERMARK_TEXT_POSITION" => $iblockParams["DETAIL_PICTURE"]["DEFAULT_VALUE"]["WATERMARK_TEXT_POSITION"],
						"USE_WATERMARK_FILE" => $iblockParams["DETAIL_PICTURE"]["DEFAULT_VALUE"]["USE_WATERMARK_FILE"],
						"WATERMARK_FILE" => $iblockParams["DETAIL_PICTURE"]["DEFAULT_VALUE"]["WATERMARK_FILE"],
						"WATERMARK_FILE_ALPHA" => $iblockParams["DETAIL_PICTURE"]["DEFAULT_VALUE"]["WATERMARK_FILE_ALPHA"],
						"WATERMARK_FILE_POSITION" => $iblockParams["DETAIL_PICTURE"]["DEFAULT_VALUE"]["WATERMARK_FILE_POSITION"],
					)
				);
			}
			if($params["IMPORT_OFFERS"] == "Y")
			{
				$offersIblockParams = CIBlock::GetFields($params["OFFERS_IBLOCK_ID"]);
				
				$iblockOfferParams = Array(
					"IBLOCK_SECTION" => $offersIblockParams["IBLOCK_SECTION"],
					"ACTIVE" => $offersIblockParams["ACTIVE"],
					"ACTIVE_FROM" => $offersIblockParams["ACTIVE_FROM"],
					"ACTIVE_TO" => $offersIblockParams["ACTIVE_TO"],
					"SORT" => $offersIblockParams["SORT"],
					"NAME" => $offersIblockParams["NAME"],
					"PREVIEW_PICTURE" => $offersIblockParams["PREVIEW_PICTURE"],
					"PREVIEW_TEXT_TYPE" => $offersIblockParams["PREVIEW_TEXT_TYPE"],
					"PREVIEW_TEXT" => $offersIblockParams["PREVIEW_TEXT"],
					"DETAIL_PICTURE" => $offersIblockParams["DETAIL_PICTURE"],
					"DETAIL_TEXT_TYPE" => $offersIblockParams["DETAIL_TEXT_TYPE"],
					"DETAIL_TEXT" => $offersIblockParams["DETAIL_TEXT"],
					"CODE" => $offersIblockParams["CODE"],
					"TAGS" => $offersIblockParams["TAGS"],
				);
			}
		}
		if($params["IMPORT_PRODUCTS"] == "Y" || $params["IMPORT_PRICES"] == "Y" || $params["IMPORT_STORE_AMOUNT"] == "Y")
		{
			CModule::IncludeModule("catalog");
		}
		if($params["IMPORT_HIGHLOAD_BLOCK_ENTITIES"] == "Y")
		{
			CModule::IncludeModule("highloadblock");
		}
		
		if(
			$params["IMPORT_ELEMENTS"] == "Y" && 
			(
				$params["ELEMENTS_ADD"] == "Y" || 
				$params["ELEMENTS_UPDATE"] == "Y"
			)
		)
			$isImportElement = true;
		else
			$isImportElement = false;
		
		if(
			$params["IMPORT_ELEMENTS"] == "Y" && 
			(
				$params["PRODUCTS_ADD"] == "Y" || 
				$params["PRODUCTS_UPDATE"] == "Y"
			)
		)
			$isImportElementProduct = true;
		else
			$isImportElementProduct = false;
		
		if(
			$params["IMPORT_OFFERS"] == "Y" && 
			(
				$params["OFFERS_ADD"] == "Y" || 
				$params["OFFERS_UPDATE"] == "Y"
			)
		)
			$isImportOfferElement = true;
		else
			$isImportOfferElement = false;
		
		if(
			$params["IMPORT_OFFERS"] == "Y" && 
			(
				$params["PRODUCTS_ADD"] == "Y" || 
				$params["PRODUCTS_UPDATE"] == "Y"
			)
		)
			$isImportOfferProduct = true;
		else
			$isImportOfferProduct = false;
		
		global $IMPORT_PARAMS, $ITEM_FIELDS;
		
		$IMPORT_PARAMS = $params;
		
		//if($params["IMPORT_PRODUCTS"] == "Y" && $params["IMPORT_PRICES"] == "Y")
		if($params["IMPORT_PRICES"] == "Y")
		{
			$basePrice = CCatalogGroup::GetBaseGroup();
		}
		
		$searchIdData = new CWebprostorImportPlanSearchId;
		
		foreach($data as $item)
		{
			if(!is_array($item))
				continue;
			
			$ITEM_FIELDS = $item;
			
			if($params["IMPORT_SECTIONS"] == "Y")
			{
				$sectionData = self::GetSectionArray($item, $rules, $params, $iblockSectionParams);
				
				if($sectionData === false)
				{
					if($DEBUG_EVENTS && $DEBUG_IMPORT_SECTION)
					{
						$logFields["EVENT"] = "IMPORT_SECTIONS";
						$logFields["MESSAGE"] = Loc::getMessage("ERROR_REQUIRED_FIELDS_NOT_FILLED", Array("#OBJECT#" => "Section"));
						if($ERROR_MESSAGE)
						{
							$logFields["DATA"] = $ERROR_MESSAGE;
							$ERROR_MESSAGE = false;
						}
						$logData->Add($logFields);
					}
				}
				
				if($sectionData && is_array($sectionData["FIELDS_PARENT"]))
				{
					/*if($params["SECTIONS_DEFAULT_SECTION_ID"]>0)
					{
						$sectionData["FIELDS_PARENT"][1]["SECTION_ID"] = $params["SECTIONS_DEFAULT_SECTION_ID"];
						$sectionData["FIELDS_PARENT"][1]["IBLOCK_SECTION_ID"] = $params["SECTIONS_DEFAULT_SECTION_ID"];
						$sectionData["SEARCH_PARENT"][1][] = "SECTION_ID";
					}*/
					$importParentSection = self::ImportSection($sectionData["FIELDS_PARENT"], $sectionData["SEARCH_PARENT"], $params, true); /*onlySearch because don't add Parent*/
				}
				
				if($params["SECTIONS_DEFAULT_SECTION_ID"]>0)
				{
					$sectionData["FIELDS"][1]["SECTION_ID"] = $params["SECTIONS_DEFAULT_SECTION_ID"];
					$sectionData["FIELDS"][1]["IBLOCK_SECTION_ID"] = $params["SECTIONS_DEFAULT_SECTION_ID"];
					$sectionData["SEARCH"][1][] = "SECTION_ID";
				}
				
				$canImportSection = true;
				
				if(is_array($importParentSection))
				{
					$searchIdFields = [
						"PLAN_ID" => $PLAN_ID,
						"OBJECT_TYPE" => 'SECTION',
						"OBJECT_ID" => $importSection['ID'],
					];
					if(!empty($searchIdFields['OBJECT_ID']))
						$searchIdData->Add($searchIdFields);
					
					if($importParentSection["ID"] > 0)
					{
						$sectionData["FIELDS"][1]["SECTION_ID"] = $importParentSection["ID"];
						$sectionData["FIELDS"][1]["IBLOCK_SECTION_ID"] = $importParentSection["ID"];
						$sectionData["SEARCH"][1][] = "SECTION_ID";
						
						if($DEBUG_EVENTS && $DEBUG_IMPORT_SECTION)
						{
							$logFields["EVENT"] = "IMPORT_OBJECT_NEW";
							$logFields["MESSAGE"] = Loc::getMessage("MESSAGE_IMPORT_OBJECT_NEW", Array("#OBJECT#" => "Parent Section"));
							//$logFields["DATA"] = base64_encode(serialize(array_merge(array_keys($importParentSection), $importParentSection)));
							$logFields["DATA"] = base64_encode(serialize($importParentSection));
							$logData->Add($logFields);
						}
						
						self::GetImportResultCount($result, "SECTIONS", $importParentSection["EVENT"]);
					}
					else
					{
						$canImportSection = false;
						
						if($DEBUG_EVENTS && $DEBUG_IMPORT_SECTION)
						{
							$logFields["EVENT"] = "IMPORT_SECTIONS";
							$logFields["MESSAGE"] = Loc::getMessage("MESSAGE_IMPORT_SKIP_IMPORT_WITHOUT_SECTION", Array("#OBJECT#" => "Section"));
							//$logFields["DATA"] = base64_encode(serialize(array_merge(array_keys($sectionData["FIELDS_PARENT"][1]), $sectionData["FIELDS_PARENT"][1])));
							$logFields["DATA"] = base64_encode(serialize($sectionData["FIELDS_PARENT"][1]));
							$logData->Add($logFields);
						}
					}
				}
				
				if($sectionData && is_array($sectionData["FIELDS"]) && $canImportSection)
				{
					$importSection = self::ImportSection($sectionData["FIELDS"], $sectionData["SEARCH"], $params);
				}
				else
				{
					if($DEBUG_EVENTS && $DEBUG_IMPORT_SECTION)
					{
						$logFields["EVENT"] = "IMPORT_SECTIONS";
						$logFields["MESSAGE"] = Loc::getMessage("ERROR_REQUIRED_FIELDS_NOT_FILLED", Array("#OBJECT#" => "Section"));
						//$logFields["DATA"] = base64_encode(serialize(array_merge(array_keys($item), $item)));
						//$logFields["DATA"] = base64_encode(serialize($item));
						if($ERROR_MESSAGE)
						{
							$logFields["DATA"] = $ERROR_MESSAGE;
							$ERROR_MESSAGE = false;
						}
						$logData->Add($logFields);
					}
				}
				unset($sectionData);
				
				if(is_array($importSection))
				{
					$searchIdFields = [
						"PLAN_ID" => $PLAN_ID,
						"OBJECT_TYPE" => 'SECTION',
						"OBJECT_ID" => $importSection['ID'],
					];
					if(!empty($searchIdFields['OBJECT_ID']))
						$searchIdData->Add($searchIdFields);
					
					if($DEBUG_EVENTS && $DEBUG_IMPORT_SECTION)
					{
						$logFields["EVENT"] = "IMPORT_OBJECT_NEW";
						$logFields["MESSAGE"] = Loc::getMessage("MESSAGE_IMPORT_OBJECT_NEW", Array("#OBJECT#" => "Section"));
						//$logFields["DATA"] = base64_encode(serialize(array_merge(array_keys($importSection), $importSection)));
						$logFields["DATA"] = base64_encode(serialize($importSection));
						$logData->Add($logFields);
					}
					
					self::GetImportResultCount($result, "SECTIONS", $importSection["EVENT"]);
				}
			}
			if($params["IMPORT_ELEMENTS"] == "Y")
			{
				if(
					(
						$params["IMPORT_SECTIONS"] == "Y" && 
						$params["ELEMENTS_SKIP_IMPORT_WITHOUT_SECTION"] = "Y" && 
						$importSection["ID"]>0
					) || 
					$params["ELEMENTS_SKIP_IMPORT_WITHOUT_SECTION"] == "N" || 
					$params["IMPORT_SECTIONS"] == "N"
				)
				{
					$elementData = self::GetElementArray($item, $rules, $params, $importSection["ID"], $iblockElementParams);
					
					if($elementData)
					{
						$importElement = self::ImportElement($elementData, $params);
					}
					else
					{
						if($DEBUG_EVENTS && $DEBUG_IMPORT_ELEMENTS)
						{
							$logFields["EVENT"] = "IMPORT_ELEMENTS";
							$logFields["MESSAGE"] = Loc::getMessage("ERROR_REQUIRED_FIELDS_NOT_FILLED", Array("#OBJECT#" => "Element"));
							if($ERROR_MESSAGE)
							{
								$logFields["DATA"] = $ERROR_MESSAGE;
								$ERROR_MESSAGE = false;
							}
							$logData->Add($logFields);
						}
					}
					unset($elementData);

					if(is_array($importElement))
					{
						$searchIdFields = [
							"PLAN_ID" => $PLAN_ID,
							"OBJECT_TYPE" => 'ELEMENT',
							"OBJECT_ID" => $importElement['ID'],
						];
						if(!empty($searchIdFields['OBJECT_ID']))
							$searchIdData->Add($searchIdFields);
						
						if($DEBUG_EVENTS && $DEBUG_IMPORT_ELEMENTS)
						{
							$logFields["EVENT"] = "IMPORT_OBJECT_NEW";
							$logFields["MESSAGE"] = Loc::getMessage("MESSAGE_IMPORT_OBJECT_NEW", Array("#OBJECT#" => "Element"));
							//$logFields["DATA"] = base64_encode(serialize(array_merge(array_keys($importElement), $importElement)));
							$logFields["DATA"] = base64_encode(serialize($importElement));
							$logData->Add($logFields);
						}
						
						self::GetImportResultCount($result, "ELEMENTS", $importElement["EVENT"]);
					}
				}
				elseif($DEBUG_EVENTS && $DEBUG_IMPORT_ELEMENTS)
				{
					$logFields["EVENT"] = "IMPORT_SKIP_IMPORT_WITHOUT_SECTION";
					$logFields["MESSAGE"] = Loc::getMessage("MESSAGE_IMPORT_SKIP_IMPORT_WITHOUT_SECTION", Array("#OBJECT#" => "Element"));
					$logFields["DATA"] = "";
					$logData->Add($logFields);
				}
			}
			
			if(
				$params["IMPORT_PROPERTIES"] == "Y" && 
				$params["PROPERTIES_UPDATE"] == "Y" && 
				//$params["IMPORT_OFFERS"] != "Y" && 
				
				!$isImportOfferElement && 
				(
					$params["PROPERTIES_UPDATE_SKIP_IF_EVENT_SEARCH"] != 'Y' || 
					(
						$params["PROPERTIES_UPDATE_SKIP_IF_EVENT_SEARCH"] == 'Y' && 
						!empty($importElement["EVENT"]) && 
						$importElement["EVENT"] != 'SEARCH'
					)
				)
			)
			{
				$propertyData = self::GetPropertiesArray($item, $rules, $params, $iblockPropertiesParams, $params["IBLOCK_ID"], "IBLOCK_ELEMENT_PROPERTY", $importElement["EVENT"]);
				
				if($propertyData && $importElement["ID"]>0)
				{
					$importProperty = self::ImportProperties($propertyData, $params, $importElement["ID"], $params["IBLOCK_ID"]);
				}
				else
				{
					if($DEBUG_EVENTS && $DEBUG_IMPORT_PROPERTIES)
					{
						if(!$importElement["ID"]>0)
						{
							$logFields["EVENT"] = "IMPORT_PROPERTIES";
							$logFields["MESSAGE"] = Loc::getMessage("ERROR_NO_ELEMENT_ID");
							$logFields["DATA"] = "";
						}
						elseif(!$propertyData)
						{
							$logFields["EVENT"] = "IMPORT_PROPERTIES";
							$logFields["MESSAGE"] = Loc::getMessage("ERROR_REQUIRED_FIELDS_NOT_FILLED", Array("#OBJECT#" => "Properties"));
							//$logFields["DATA"] = base64_encode(serialize(array_merge(array_keys($item), $item)));
							//$logFields["DATA"] = base64_encode(serialize($item));
							if($ERROR_MESSAGE)
							{
								$logFields["DATA"] = $ERROR_MESSAGE;
								$ERROR_MESSAGE = false;
							}
						}
						$logData->Add($logFields);
					}
				}
				unset($propertyData);
				
				if(is_array($importProperty))
				{
					if($DEBUG_EVENTS && $DEBUG_IMPORT_PROPERTIES)
					{
						$logFields["EVENT"] = "IMPORT_OBJECT_NEW";
						$logFields["MESSAGE"] = Loc::getMessage("MESSAGE_IMPORT_OBJECT_NEW", Array("#OBJECT#" => "Property"));
						//$logFields["DATA"] = base64_encode(serialize(array_merge(array_keys($importProperty), $importProperty)));
						$logFields["DATA"] = base64_encode(serialize($importProperty));
						$logData->Add($logFields);
					}
					
					self::GetImportResultCount($result, "PROPERTIES", $importProperty["EVENT"]);
				}
			}

			if($isImportElementProduct == true && $params["IMPORT_PRODUCTS"] == "Y")
			{
				if($importElement["ID"]>0)
				{
					$productData = self::GetProductArray($item, $rules, $params, $importElement["ID"], "CATALOG_PRODUCT_FIELD");
					
					if($productData)
					{
						$importProduct = self::ImportProduct($productData, $params, "PRODUCTS");
					}
					else
					{
						if($DEBUG_EVENTS && $DEBUG_IMPORT_PRODUCTS)
						{
							$logFields["EVENT"] = "IMPORT_PRODUCTS";
							$logFields["MESSAGE"] = Loc::getMessage("ERROR_REQUIRED_FIELDS_NOT_FILLED", Array("#OBJECT#" => "Product"));
							//$logFields["DATA"] = base64_encode(serialize(array_merge(array_keys($item), $item)));
							//$logFields["DATA"] = base64_encode(serialize($item));
							if($ERROR_MESSAGE)
							{
								$logFields["DATA"] = $ERROR_MESSAGE;
								$ERROR_MESSAGE = false;
							}
							$logData->Add($logFields);
						}
					}
					unset($productData);
					
					if(is_array($importProduct))
					{
						if($DEBUG_EVENTS && $DEBUG_IMPORT_PRODUCTS)
						{
							$logFields["EVENT"] = "IMPORT_OBJECT_NEW";
							$logFields["MESSAGE"] = Loc::getMessage("MESSAGE_IMPORT_OBJECT_NEW", Array("#OBJECT#" => "Product"));
							//$logFields["DATA"] = base64_encode(serialize(array_merge(array_keys($importProduct), $importProduct)));
							$logFields["DATA"] = base64_encode(serialize($importProduct));
							$logData->Add($logFields);
						}
						
						self::GetImportResultCount($result, "PRODUCTS", $importProduct["EVENT"]);
					}
				}
				else
				{
					if($DEBUG_EVENTS && $DEBUG_IMPORT_PRODUCTS)
					{
						$logFields["EVENT"] = "IMPORT_PRODUCTS";
						$logFields["MESSAGE"] = Loc::getMessage("ERROR_NO_ELEMENT_ID_FOR_PRODUCT");
						$logFields["DATA"] = "";
						$logData->Add($logFields);
					}
				}
			}
			
			if($params["IMPORT_ELEMENTS"] == "Y" && $params["IMPORT_OFFERS"] == "Y")
			{
				if($importElement["ID"]>0)
				{
					$elementOfferData = self::GetElementOfferArray($item, $rules, $params, $importElement["ID"], $iblockOfferParams, $iblockPropertiesParams);
					
					if($elementOfferData)
					{
						$importElementOffer = self::ImportElementOffer($elementOfferData, $params);
					}
					else
					{
						if($DEBUG_EVENTS && $DEBUG_IMPORT_OFFERS)
						{
							$logFields["EVENT"] = "IMPORT_OFFERS";
							$logFields["MESSAGE"] = Loc::getMessage("ERROR_REQUIRED_FIELDS_NOT_FILLED", Array("#OBJECT#" => "Offer"));
							//$logFields["DATA"] = base64_encode(serialize(array_merge(array_keys($item), $item)));
							//$logFields["DATA"] = base64_encode(serialize($item));
							if($ERROR_MESSAGE)
							{
								$logFields["DATA"] = $ERROR_MESSAGE;
								$ERROR_MESSAGE = false;
							}
							$logData->Add($logFields);
						}
					}
					unset($elementOfferData);
				}
				else
				{
					if($DEBUG_EVENTS && $DEBUG_IMPORT_OFFERS)
					{
						$logFields["EVENT"] = "IMPORT_OFFERS";
						$logFields["MESSAGE"] = Loc::getMessage("ERROR_NO_ELEMENT_ID_FOR_OFFERS");
						$logFields["DATA"] = "";
						$logData->Add($logFields);
					}
				}
				
				if(is_array($importElementOffer))
				{
					$searchIdFields = [
						"PLAN_ID" => $PLAN_ID,
						"OBJECT_TYPE" => 'OFFER',
						"OBJECT_ID" => $importElementOffer['ID'],
					];
					if(!empty($searchIdFields['OBJECT_ID']))
						$searchIdData->Add($searchIdFields);
					
					if($DEBUG_EVENTS && $DEBUG_IMPORT_OFFERS)
					{
						$logFields["EVENT"] = "IMPORT_OBJECT_NEW";
						$logFields["MESSAGE"] = Loc::getMessage("MESSAGE_IMPORT_OBJECT_NEW", Array("#OBJECT#" => "ElementOffer"));
						//$logFields["DATA"] = base64_encode(serialize(array_merge(array_keys($importElementOffer), $importElementOffer)));
						$logFields["DATA"] = base64_encode(serialize($importElementOffer));
						$logData->Add($logFields);
					}
					self::GetImportResultCount($result, "OFFERS", $importElementOffer["EVENT"]);
				}
			}
			
			if($params["IMPORT_PROPERTIES"] == "Y" && $params["PROPERTIES_UPDATE"] == "Y" && $params["IMPORT_OFFERS"] == "Y")
			{
				$propertyOfferData = self::GetPropertiesArray($item, $rules, $params, $iblockPropertiesParams, $params["OFFERS_IBLOCK_ID"], "IBLOCK_ELEMENT_OFFER_PROPERTY", $importElementOffer["EVENT"]);
				
				if($propertyOfferData && $importElementOffer["ID"]>0)
				{
					$importOfferProperty = self::ImportProperties($propertyOfferData, $params, $importElementOffer["ID"], $params["OFFERS_IBLOCK_ID"]);
				}
				else
				{
					if($DEBUG_EVENTS && $DEBUG_IMPORT_PROPERTIES)
					{
						if(!$importElementOffer["ID"]>0)
						{
							$logFields["EVENT"] = "IMPORT_PROPERTIES";
							$logFields["MESSAGE"] = Loc::getMessage("ERROR_NO_ELEMENT_ID");
							$logFields["DATA"] = "";
						}
						elseif(!$propertyOfferData)
						{
							$logFields["EVENT"] = "IMPORT_PROPERTIES";
							$logFields["MESSAGE"] = Loc::getMessage("ERROR_REQUIRED_FIELDS_NOT_FILLED", Array("#OBJECT#" => "Properties"));
							//$logFields["DATA"] = base64_encode(serialize(array_merge(array_keys($item), $item)));
							//$logFields["DATA"] = base64_encode(serialize($item));
							if($ERROR_MESSAGE)
							{
								$logFields["DATA"] = $ERROR_MESSAGE;
								$ERROR_MESSAGE = false;
							}
						}
						$logData->Add($logFields);
					}
				}
				unset($propertyOfferData);
				
				if(is_array($importOfferProperty))
				{
					if($DEBUG_EVENTS && $DEBUG_IMPORT_PROPERTIES)
					{
						$logFields["EVENT"] = "IMPORT_OBJECT_NEW";
						$logFields["MESSAGE"] = Loc::getMessage("MESSAGE_IMPORT_OBJECT_NEW", Array("#OBJECT#" => "Property"));
						//$logFields["DATA"] = base64_encode(serialize(array_merge(array_keys($importOfferProperty), $importOfferProperty)));
						$logFields["DATA"] = base64_encode(serialize($importOfferProperty));
						$logData->Add($logFields);
					}
					
					self::GetImportResultCount($result, "PROPERTIES", $importOfferProperty["EVENT"]);
				}
			}
			
			if($isImportOfferProduct == true && $params["IMPORT_PRODUCTS"] == "Y")
			{
				
				if($importElementOffer["ID"]>0)
				{
					$productOfferData = self::GetProductArray($item, $rules, $params, $importElementOffer["ID"], "CATALOG_PRODUCT_OFFER_FIELD");
				}
				else
				{
					if($DEBUG_EVENTS && $DEBUG_IMPORT_OFFERS)
					{
						$logFields["EVENT"] = "IMPORT_OFFERS";
						$logFields["MESSAGE"] = Loc::getMessage("ERROR_NO_OFFER_ID_FOR_PRODUCT");
						$logFields["DATA"] = "";
						$logData->Add($logFields);
					}
				}
				
				if($productOfferData)
				{
					$importProductOffer = self::ImportProduct($productOfferData, $params, "PRODUCTS");
				}
				else
				{
					if($DEBUG_EVENTS && $DEBUG_IMPORT_OFFERS)
					{
						$logFields["EVENT"] = "IMPORT_OFFERS";
						$logFields["MESSAGE"] = Loc::getMessage("ERROR_REQUIRED_FIELDS_NOT_FILLED", Array("#OBJECT#" => "ProductOffer"));
						//$logFields["DATA"] = base64_encode(serialize(array_merge(array_keys($item), $item)));
						//$logFields["DATA"] = base64_encode(serialize($item));
						if($ERROR_MESSAGE)
						{
							$logFields["DATA"] = $ERROR_MESSAGE;
							$ERROR_MESSAGE = false;
						}
						$logData->Add($logFields);
					}
				}
				unset($productOfferData);
				
				if(is_array($importProductOffer))
				{
					if($DEBUG_EVENTS && $DEBUG_IMPORT_OFFERS)
					{
						$logFields["EVENT"] = "IMPORT_OBJECT_NEW";
						$logFields["MESSAGE"] = Loc::getMessage("MESSAGE_IMPORT_OBJECT_NEW", Array("#OBJECT#" => "ProductOffer"));
						//$logFields["DATA"] = base64_encode(serialize(array_merge(array_keys($importProductOffer), $importProductOffer)));
						$logFields["DATA"] = base64_encode(serialize($importProductOffer));
						$logData->Add($logFields);
					}
					
					self::GetImportResultCount($result, "PRODUCTS", $importProductOffer["EVENT"]);
				}
			}
			
			if($importElement["ID"]>0 && CModule::IncludeModule("catalog"))
			{
				$importElementIsExistProduct = CCatalogProduct::IsExistProduct($importElement["ID"]);
			}
			else
				$importElementIsExistProduct = false;
			
			if($importElementOffer["ID"]>0 && CModule::IncludeModule("catalog"))
			{
				$importElementOfferIsExistProduct = CCatalogProduct::IsExistProduct($importElementOffer["ID"]);
			}
			else
				$importElementOfferIsExistProduct = false;
			
			if($params["IMPORT_PRODUCTS"] == "Y" && $params["IMPORT_PRICES"] == "Y")
			{
				
				if($importElement["ID"]>0 || $importElementOffer["ID"]>0)
				{
					
					//if($isImportElementProduct == true && $importElement["ID"] && !$importElementOffer["ID"])
					if($importElementIsExistProduct == true && $importElement["ID"] && !$importElementOffer["ID"])
					{
						$priceData = self::GetPriceArray($item, $rules, $params, $importElement["ID"], $basePrice["ID"]);
					}
					//elseif($isImportOfferProduct == true && $importElementOffer["ID"])
					elseif($importElementOfferIsExistProduct == true && $importElementOffer["ID"])
					{
						$priceData = self::GetPriceArray($item, $rules, $params, $importElementOffer["ID"], $basePrice["ID"]);
					}
					
					if($priceData)
					{
						$importPrice = self::ImportPrice($priceData, $params);
					}
					else
					{
						if($DEBUG_EVENTS && $DEBUG_IMPORT_PRICES)
						{
							$logFields["EVENT"] = "IMPORT_PRICES";
							$logFields["MESSAGE"] = Loc::getMessage("ERROR_REQUIRED_FIELDS_NOT_FILLED", Array("#OBJECT#" => "Price"));
							//$logFields["DATA"] = base64_encode(serialize(array_merge(array_keys($item), $item)));
							//$logFields["DATA"] = base64_encode(serialize($item));
							if($ERROR_MESSAGE)
							{
								$logFields["DATA"] = $ERROR_MESSAGE;
								$ERROR_MESSAGE = false;
							}
							$logData->Add($logFields);
						}
					}
					unset($priceData);
					
					if(is_array($importPrice["LOGS"]))
					{
						foreach($importPrice["LOGS"] as $singlePrice)
						{
							if($DEBUG_EVENTS && $DEBUG_IMPORT_PRICES)
							{
								$logFields["EVENT"] = "IMPORT_OBJECT_NEW";
								$logFields["MESSAGE"] = Loc::getMessage("MESSAGE_IMPORT_OBJECT_NEW", Array("#OBJECT#" => "Price"));
								//$logFields["DATA"] = base64_encode(serialize(array_merge(array_keys($singlePrice), $singlePrice)));
								$logFields["DATA"] = base64_encode(serialize($singlePrice));
								$logData->Add($logFields);
							}
						}
						
						self::GetImportResultCountPriceStore($result, "PRICES", $importPrice["SYSTEM"]["EVENT"]);
					}
				}
				else
				{
					if($DEBUG_EVENTS && $DEBUG_IMPORT_PRICES)
					{
						$logFields["EVENT"] = "IMPORT_PRICES";
						$logFields["MESSAGE"] = Loc::getMessage("ERROR_NO_PRODUCT_ID_FOR_PRICE");
						$logFields["DATA"] = "";
						$logData->Add($logFields);
					}
				}
			}
			//if($params["IMPORT_PRODUCTS"] == "Y" && $params["IMPORT_STORE_AMOUNT"] == "Y")
			if($params["IMPORT_PRODUCTS"] == "Y" && $params["IMPORT_STORE_AMOUNT"] == "Y")
			{
				if($importElement["ID"]>0 || $importElementOffer["ID"]>0)
				{
					if($importElementIsExistProduct == true && $importElement["ID"] && !$importElementOffer["ID"])
					{
						$storeAmountData = self::GetStoreAmountArray($item, $rules, $params, $importElement["ID"]);
					}
					elseif($importElementOfferIsExistProduct == true && $importElementOffer["ID"])
					{
						$storeAmountData = self::GetStoreAmountArray($item, $rules, $params, $importElementOffer["ID"]);
					}
					
					if($storeAmountData)
					{
						$importStoreAmount = self::ImportStoreAmount($storeAmountData, $params);
					}
					else
					{
						if($DEBUG_EVENTS && $DEBUG_IMPORT_STORE_AMOUNT)
						{
							$logFields["EVENT"] = "IMPORT_STORE_AMOUNT";
							$logFields["MESSAGE"] = Loc::getMessage("ERROR_REQUIRED_FIELDS_NOT_FILLED", Array("#OBJECT#" => "StoreAmount"));
							//$logFields["DATA"] = base64_encode(serialize(array_merge(array_keys($item), $item)));
							//$logFields["DATA"] = base64_encode(serialize($item));
							if($ERROR_MESSAGE)
							{
								$logFields["DATA"] = $ERROR_MESSAGE;
								$ERROR_MESSAGE = false;
							}
							$logData->Add($logFields);
						}
					}
					unset($storeAmountData);
					
					if(is_array($importStoreAmount["LOGS"]))
					{
						foreach($importStoreAmount["LOGS"] as $singleStoreAmount)
						{
							if($DEBUG_EVENTS && $DEBUG_IMPORT_STORE_AMOUNT)
							{
								$logFields["EVENT"] = "IMPORT_OBJECT_NEW";
								$logFields["MESSAGE"] = Loc::getMessage("MESSAGE_IMPORT_OBJECT_NEW", Array("#OBJECT#" => "StoreAmount"));
								//$logFields["DATA"] = base64_encode(serialize(array_merge(array_keys($singleStoreAmount), $singleStoreAmount)));
								$logFields["DATA"] = base64_encode(serialize($singleStoreAmount));
								$logData->Add($logFields);
							}
						}
						
						self::GetImportResultCountPriceStore($result, "STORE_AMOUNTS", $importStoreAmount["SYSTEM"]["EVENT"]);
					}
				}
				else
				{
					if($DEBUG_EVENTS && $DEBUG_IMPORT_STORE_AMOUNT)
					{
						$logFields["EVENT"] = "IMPORT_STORE_AMOUNT";
						$logFields["MESSAGE"] = Loc::getMessage("ERROR_NO_PRODUCT_ID_FOR_STORE_AMOUNT");
						$logFields["DATA"] = "";
						$logData->Add($logFields);
					}
				}
			}
			
			if($params["IMPORT_HIGHLOAD_BLOCK_ENTITIES"] == "Y")
			{
				$entityData = self::GetEntityArray($item, $rules, $params);
				
				if($entityData)
				{
					$importEntity = self::ImportEntity($entityData, $params);
				}
				unset($entityData);
				
				if(is_array($importEntity))
				{
					if($DEBUG_EVENTS && $DEBUG_IMPORT_ENTITIES)
					{
						$logFields["EVENT"] = "IMPORT_OBJECT_NEW";
						$logFields["MESSAGE"] = Loc::getMessage("MESSAGE_IMPORT_OBJECT_NEW", Array("#OBJECT#" => "Entity"));
						//$logFields["DATA"] = base64_encode(serialize(array_merge(array_keys($importEntity), $importEntity)));
						$logFields["DATA"] = base64_encode(serialize($importEntity));
						$logData->Add($logFields);
					}
					
					self::GetImportResultCount($result, "ENTITIES", $importEntity["EVENT"]);
				}
			}
			
			unset(
				$importParentSection,
				$importSection,
				$importElement,
				$importProperty,
				$importElementOffer,
				$importProduct,
				$importProductOffer,
				$importOfferProperty,
				$importPrice,
				$importStoreAmount
			);
			
		}
		
		unset(
			$data, 
			$IMPORT_PARAMS, 
			$ITEM_FIELDS, 
			$iblockSectionParams, 
			$iblockElementParams, 
			$iblockPropertiesParams, 
			$iblockOfferParams,
			$searchIdData,
			$logData
		);
		
		return $result;
	}
	
	private static function AddWhatermarkToFiles(&$files = Array(), $multiple = true, $rule, $defaultParams, $curl = false)
	{
		if(!$multiple)
		{
			$temp[] = $files;
			$files = $temp;
		}
		
		if(count($files))
		{
			switch($rule)
			{
				case("P"):
					$params = $defaultParams["PREVIEW_PICTURE"];
					break;
				case("D"):
					$params = $defaultParams["DETAIL_PICTURE"];
					break;
			}
			$upload_dir = COption::GetOptionString("main", "upload_dir", "upload");
			foreach($files as $id => $file)
			{
				$is_image = CFile::IsImage($file["tmp_name"]);
				$resizeTo = $_SERVER['DOCUMENT_ROOT'].'/'.$upload_dir.'/tmp/'.$file["name"];
				if($is_image)
				{
					if($params["USE_WATERMARK_TEXT"] === "Y")
					{
						if($curl)
						{
							$arWatermark = array(
								"position" => $params["WATERMARK_FILE_POSITION"],
								'type' => 'text',
								"font" => $_SERVER["DOCUMENT_ROOT"].Rel2Abs("/", $params["WATERMARK_TEXT_FONT"]),
								"coefficient" => $params["WATERMARK_TEXT_SIZE"],
								"text" => $params["WATERMARK_TEXT"],
								"color" => $params["WATERMARK_TEXT_COLOR"],
							);
						}
						else
						{
							$filterPicture = CIBlock::FilterPicture($file["tmp_name"], array(
								"name" => "watermark",
								"position" => $params["WATERMARK_TEXT_POSITION"],
								"type" => "text",
								"coefficient" => $params["WATERMARK_TEXT_SIZE"],
								"text" => $params["WATERMARK_TEXT"],
								"font" => $_SERVER["DOCUMENT_ROOT"].Rel2Abs("/", $params["WATERMARK_TEXT_FONT"]),
								"color" => $params["WATERMARK_TEXT_COLOR"],
							));
						}
					}
					elseif($params["USE_WATERMARK_FILE"] === "Y")
					{
						if($curl)
						{
							$arWatermark = array(
								"position" => $params["WATERMARK_FILE_POSITION"],
								'type' => 'file',
								'size' => 'real',
								"alpha_level" => 100 - min(max($params["WATERMARK_FILE_ALPHA"], 0), 100),
								"file" => $_SERVER["DOCUMENT_ROOT"].Rel2Abs("/", $params["WATERMARK_FILE"]),
							);
						}
						else
						{
							$filterPicture = CIBlock::FilterPicture($file["tmp_name"], array(
								"name" => "watermark",
								"position" => $params["WATERMARK_FILE_POSITION"],
								"type" => "file",
								"size" => "real",
								"alpha_level" => 100 - min(max($params["WATERMARK_FILE_ALPHA"], 0), 100),
								"file" => $_SERVER["DOCUMENT_ROOT"].Rel2Abs("/", $params["WATERMARK_FILE"]),
							));
						}
					}
					
					if($curl)
					{
						$imageSize = getimagesize($file["tmp_name"]);
						$filterPicture = CFile::ResizeImageFile(
							$file["tmp_name"],
							$resizeTo,
							Array("width" => $imageSize[0], "height" => $imageSize[1]),
							BX_RESIZE_IMAGE_PROPORTIONAL_ALT,
							$arWatermark,
							false,
							false
						);
						if($filterPicture)
						{
							$file = CFile::MakeFileArray($resizeTo);
							$files[$id] = $file;
						}
					}
				}
			}
		}
	}
	
	private static function GetFileArrayByFileName($fileName = '', $fileDir = '', $multiply = false, $value = false, $useWhatermark = false, $whaterMarkParams = Array(), $DEBUG_THIS = false, &$params)
	{
		$fileFullDir = $_SERVER["DOCUMENT_ROOT"].$fileDir;
		$result = false;
		
		global $DEBUG_EVENTS, $DEBUG_PLAN_ID;
		if($DEBUG_EVENTS && $DEBUG_THIS)
		{
			$logFile = new CWebprostorImportLog;
			$logFileData = Array("PLAN_ID" => $DEBUG_PLAN_ID);
		}
		
		if(!is_array($fileName))
		{
			if($params["RAW_URL_DECODE"] != "N")
				$fileName = rawurldecode($fileName);
			
			$fileName = Array($fileName);
		}
		
		foreach($fileName as $fileSingleName)
		{
			$fileFullPath = $_SERVER["DOCUMENT_ROOT"].$fileDir.$fileSingleName;
			
			$fileSingleName = strval($fileSingleName);
			
			if($DEBUG_EVENTS && $DEBUG_THIS)
			{
				$logFileData["EVENT"] = "GET_FILE";
				$logFileData["MESSAGE"] = Loc::getMessage("EVENT_GET_FILE");
				$logFileData["DATA"] = $fileFullPath;
				$logFile->Add($logFileData);
			}
			
			if(file_exists($fileFullPath))
			{
				$result[] = CFile::MakeFileArray($fileFullPath);
			}
			elseif(file_exists($fileFullDir) && strlen($fileDir)>0)
			{
			
				if($DEBUG_EVENTS && $DEBUG_THIS)
				{
					$logFileData["EVENT"] = "GET_FILE_SCAN";
					$logFileData["MESSAGE"] = Loc::getMessage("EVENT_GET_FILE_SCAN");
					$logFileData["DATA"] = $fileSingleName;
					$logFile->Add($logFileData);
				}
				$files = scandir($fileFullDir, 0);
				
				foreach($files as $file)
				{
					if ($file == '..' || $file == '.')
						continue;
					$fileFounded = strpos($file, $fileSingleName);
					if(is_int($fileFounded))
					{
						if(is_file($fileFullDir.$file))
							$result[] = CFile::MakeFileArray($fileFullDir.$file);
						if(!$multiply)
							break 1;
					}
				}
			}
		}

		if($result && count($result))
		{
			if($DEBUG_EVENTS && $DEBUG_THIS)
			{
				$logFileData["MESSAGE"] = Loc::getMessage("MESSAGE_FILE_OK");
				//$logFileData["DATA"] = base64_encode(serialize(array_merge(array_keys($result[0]), $result[0])));
				$logFileData["DATA"] = base64_encode(serialize($result[0]));
			}
			
			if($useWhatermark && $useWhatermark != "N")
				self::AddWhatermarkToFiles($result, true, $useWhatermark, $whaterMarkParams, false);
			
			if($multiply && count($result) > 1)
			{
				$newArray = Array();
				
				if($DEBUG_EVENTS && $DEBUG_THIS)
				{
					$logFileData["MESSAGE"] = Loc::getMessage("MESSAGE_FILES_OK", Array("#COUNT#" => count($result)));
					$logFileData["DATA"] = '';
				}
				foreach($result as $key => $array)
				{
					$newArray[] = Array(
						"VALUE" => $array,
						"DESCRIPTION" => $array["name"],
					);
				}
				
				$result = $newArray;
			}
			elseif($multiply && is_array($value) && count($result) == 1)
			{
				$temp = $result[0];
				unset($result[0]);
				if(isset($value["VALUE"]["name"]))
				{
					$result["n0"] = $value;
					$result["n1"] = Array("VALUE" => $temp, "DESCRIPTION" => $temp["name"]);
				}
				elseif(!isset($value["name"]))
				{
					$result = $value;
					$result["n".count($value)] = Array("VALUE" => $temp, "DESCRIPTION" => $temp["name"]);
				}
				unset($temp);
			}
			else
			{
				if($multiply)
					$result = Array(
						"VALUE" => $result[0],
						"DESCRIPTION" => $result[0]["name"],
					);
				else
					$result = $result[0];
			}
			
			if($DEBUG_EVENTS && $DEBUG_THIS)
			{
				$logFileData["EVENT"] = "GET_FILE";
				$logFile->Add($logFileData);
			}
		}
		elseif($DEBUG_EVENTS && $DEBUG_THIS)
		{
			$logFileData["EVENT"] = "GET_FILE";
			$logFileData["MESSAGE"] = Loc::getMessage("ERROR_GET_FILE");
			$logFileData["DATA"] = "";
			$logFile->Add($logFileData);
		}
		
		unset($logFile);
		
		return $result;
	}
	
	private static function CheckObjectValuesToUrl(&$arFields, &$params)
	{
		if(is_array($arFields) && count($arFields))
		{
			foreach($arFields as $code => $value)
			{
				if(is_string($value) && strpos($value, "GET_FILE_URL_") !== false)
				{
					$fileArr = self::GetFileArrayByUrl(str_replace("GET_FILE_URL_", "", $value), false, false, false, [], $params);
					if($fileArr)
						$arFields[$code] = $fileArr;
					
				}
			}
		}
	}
	
	private static function GetFileArrayByUrlMulti($urls = false, $value = false, $useWhatermark = false, $whaterMarkParams = Array(), &$params)
	{
		if($value)
			$result = $value;
		
		$result = false;
		
		if(!extension_loaded('curl'))
		{
			$errorArray = Array(
				"MESSAGE" => Loc::getMessage("CURL_NOT_INCLUDED"),
				"TAG" => "CURL_NOT_INCLUDED",
				"MODULE_ID" => self::MODULE_ID,
				"ENABLE_CLOSE" => "Y"
			);
			$notifyID = CAdminNotify::Add($errorArray);
		}
		elseif(is_array($urls))
		{
			global $DEBUG_EVENTS, $DEBUG_PLAN_ID, $DEBUG_URL, $CURL_TIMEOUT, $CURL_FOLLOWLOCATION;
		
			if($DEBUG_EVENTS && $DEBUG_URL)
			{
				$logUrl = new CWebprostorImportLog;
				$logUrlData = Array("PLAN_ID" => $DEBUG_PLAN_ID);
			}
			
			$multiCurl = [];
			$curlResults = [];
			
			$mh = curl_multi_init();
			$fileNames = [];
			
			foreach ($urls as $url)
			{
				if(!is_string($url) || $url == '')
					continue;
		
				$url = ltrim($url);
				$url = rtrim($url);
				$url = str_replace(" ", "%20", $url);
				$url = htmlspecialchars_decode($url);
				
				if($params["RAW_URL_DECODE"] != "N")
					$url = rawurldecode($url);
				
				if($params["VALIDATE_URL"] != "N")
					$url = filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED);
		
				if($DEBUG_EVENTS && $DEBUG_URL)
				{
					if($url)
					{
						$logUrlData["EVENT"] = "GET_URL_FILE";
						$logUrlData["MESSAGE"] = Loc::getMessage("EVENT_GET_URL_FILE");
						$logUrlData["DATA"] = $url;
						$logUrl->Add($logUrlData);
					}
					else
					{
						$logUrlData["EVENT"] = "GET_URL_FILE";
						$logUrlData["MESSAGE"] = Loc::getMessage("ERROR_URL_NOT_VALIDE");
						$logUrlData["DATA"] = $url;
						$logUrl->Add($logUrlData);
					}
				}
				
				if($url)
				{
					$urlParams = parse_url($url);
					$filename = pathinfo($urlParams['path'], PATHINFO_FILENAME);
					$fileNames[$url] = $filename;
					
					$multiCurl[$url] = curl_init();
					
					curl_setopt($multiCurl[$url], CURLOPT_URL, $url);
					curl_setopt($multiCurl[$url], CURLOPT_RETURNTRANSFER, TRUE);
					curl_setopt($multiCurl[$url], CURLOPT_CONNECTTIMEOUT, $CURL_TIMEOUT);
					
					if($CURL_FOLLOWLOCATION)
					{
						curl_setopt($multiCurl[$url], CURLOPT_FOLLOWLOCATION, true);
					}
					
					curl_multi_add_handle($mh, $multiCurl[$url]);
				}
			}
			
			$running = null;
			do
			{
				$status = curl_multi_exec($mh, $running);
				/*if ($running)
				{
					curl_multi_select($mh);
				}*/
			} 
			while ($running > 0);
			unset($running, $status);
			
			foreach ($multiCurl as $k => $ch) {
				$temp = tempnam(sys_get_temp_dir(), 'webprostor.import');
				$handle = fopen($temp, "w");
				fwrite($handle, curl_multi_getcontent($ch));
				fclose($handle);

				$curlResults[$k] = CFile::MakeFileArray($temp);
				curl_multi_remove_handle($mh, $multiCurl[$k]);
			}
			
			curl_multi_close($mh);
			
			foreach ($multiCurl as $k => $ch) {
				curl_close($multiCurl[$k]);
			}
			
			if(is_array($curlResults))
			{
				$result = [];
				foreach($curlResults as $url => $temp)
				{
					if(is_array($temp))
					{
						if(GetFileType($temp["tmp_name"]) == "UNKNOWN")
						{
							switch($temp["type"])
							{
								case("image/gif"):
									$new_tmp_name_format = '.gif';
									break;
								case("image/jpeg"):
									$new_tmp_name_format = '.jpg';
									break;
								case("image/png"):
									$new_tmp_name_format = '.png';
									break;
								case("image/webp"):
									$new_tmp_name_format = '.webp';
									break;
							}
							if($new_tmp_name_format)
							{
								$new_tmp_name = str_ireplace($new_tmp_name_format, '', $temp["tmp_name"]) . $new_tmp_name_format;
								//$new_name = str_ireplace($new_tmp_name_format, '', $temp["name"]).$new_tmp_name_format;
								$new_name = (count($result)+1). $new_tmp_name_format;
								rename($temp["tmp_name"], $new_tmp_name);
								$temp["tmp_name"] = $new_tmp_name;
								$temp["name"] = $new_name;
							}
							unset($new_tmp_name, $new_tmp_name_format, $new_name);
						}
						if($DEBUG_EVENTS && $DEBUG_URL)
						{
							$logUrlData["EVENT"] = "GET_URL_FILE";
							$logUrlData["MESSAGE"] = Loc::getMessage("MESSAGE_URL_OK");
							//$logUrlData["DATA"] = base64_encode(serialize(array_merge(array_keys($temp), $temp)));
							$logUrlData["DATA"] = base64_encode(serialize($temp));
							$logUrl->Add($logUrlData);
						}
						if($useWhatermark && $useWhatermark != "N")
							self::AddWhatermarkToFiles($temp, false, $useWhatermark, $whaterMarkParams, true);
						
						$result['n'.count($result)] = Array("VALUE" => $temp, "DESCRIPTION" => $temp["name"]);
					}
				}
			}
			unset($curlResults, $fileNames, $logUrl);
		}
		
		return $result;
	}
	
	private static function GetFileArrayByUrl($url = '', $property = false, $value = false, $useWhatermark = false, $whaterMarkParams = Array(), &$params)
	{
		$result = false;
		
		if(!is_string($url) || $url == '')
			return $value?$value:$result;
		
		$url = ltrim($url);
		$url = rtrim($url);
		$url = str_replace(" ", "%20", $url);
		$url = htmlspecialchars_decode($url);
		
		global $DEBUG_EVENTS, $DEBUG_PLAN_ID, $DEBUG_URL, $CURL_TIMEOUT, $CURL_FOLLOWLOCATION;
		
		if($DEBUG_EVENTS && $DEBUG_URL)
		{
			$logUrl = new CWebprostorImportLog;
			$logUrlData = Array("PLAN_ID" => $DEBUG_PLAN_ID);
		}
		
		if($params["RAW_URL_DECODE"] != "N")
			$url = rawurldecode($url);
		
		if($params["VALIDATE_URL"] != "N")
			$url = filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED);
		
		if($DEBUG_EVENTS && $DEBUG_URL)
		{
			if($url)
			{
				$logUrlData["EVENT"] = "GET_URL_FILE";
				$logUrlData["MESSAGE"] = Loc::getMessage("EVENT_GET_URL_FILE");
				$logUrlData["DATA"] = $url;
				$logUrl->Add($logUrlData);
			}
			else
			{
				$logUrlData["EVENT"] = "GET_URL_FILE";
				$logUrlData["MESSAGE"] = Loc::getMessage("ERROR_URL_NOT_VALIDE");
				$logUrlData["DATA"] = $url;
				$logUrl->Add($logUrlData);
			}
		}
		
		if(!extension_loaded('curl'))
		{
			$errorArray = Array(
				"MESSAGE" => Loc::getMessage("CURL_NOT_INCLUDED"),
				"TAG" => "CURL_NOT_INCLUDED",
				"MODULE_ID" => self::MODULE_ID,
				"ENABLE_CLOSE" => "Y"
			);
			$notifyID = CAdminNotify::Add($errorArray);
		}
		/*
		if($params["RAW_URL_DECODE"] != "N")
			$url = rawurldecode($url);*/
		
		if($url && extension_loaded('curl'))
		{
			$urlParams = parse_url($url);
			
			$handle = curl_init();
			
			curl_setopt($handle, CURLOPT_URL, $url);
			curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, $CURL_TIMEOUT);
			
			if($CURL_FOLLOWLOCATION)
			{
				curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
			}
			
			$response = curl_exec($handle);
			$httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
			
			if($httpCode == 200 || ($httpCode == 226 && strtolower($urlParams["scheme"]) == 'ftp'))
			{
				$tempFile = tempnam(sys_get_temp_dir(), 'webprostor.import');
				$handleTemp = fopen($tempFile, "w");
				fwrite($handleTemp, $response);
				fclose($handleTemp);
				
				$temp = CFile::MakeFileArray($tempFile);
			}
			elseif($DEBUG_EVENTS && $DEBUG_URL)
			{
				$logUrlData["EVENT"] = "GET_URL_FILE";
				$logUrlData["MESSAGE"] = Loc::getMessage("ERROR_GET_URL_FILE", Array("#ERROR_CODE#" => $httpCode));
				$logUrlData["DATA"] = "";
				$logUrl->Add($logUrlData);
			}
			
			curl_close($handle);
			
			if(is_array($temp))
			{
				if(GetFileType($temp["tmp_name"]) == "UNKNOWN")
				{
					switch($temp["type"])
					{
						case("image/gif"):
							$new_tmp_name_format = '.gif';
							break;
						case("image/jpeg"):
							$new_tmp_name_format = '.jpg';
							break;
						case("image/png"):
							$new_tmp_name_format = '.png';
							break;
						case("image/webp"):
							$new_tmp_name_format = '.webp';
							break;
					}
					if($new_tmp_name_format)
					{
						$new_tmp_name = str_ireplace($new_tmp_name_format, '', $temp["tmp_name"]).$new_tmp_name_format;
						//$new_name = str_ireplace($new_tmp_name_format, '', $temp["name"]).$new_tmp_name_format;
						$new_name = 'file'.$new_tmp_name_format;
						rename($temp["tmp_name"], $new_tmp_name);
						$temp["tmp_name"] = $new_tmp_name;
						$temp["name"] = $new_name;
					}
					unset($new_tmp_name, $new_tmp_name_format, $new_name);
				}
				if($DEBUG_EVENTS && $DEBUG_URL)
				{
					$logUrlData["EVENT"] = "GET_URL_FILE";
					$logUrlData["MESSAGE"] = Loc::getMessage("MESSAGE_URL_OK");
					//$logUrlData["DATA"] = base64_encode(serialize(array_merge(array_keys($temp), $temp)));
					$logUrlData["DATA"] = base64_encode(serialize($temp));
					$logUrl->Add($logUrlData);
				}
				if($property)
				{
					if($useWhatermark && $useWhatermark != "N")
						self::AddWhatermarkToFiles($temp, false, $useWhatermark, $whaterMarkParams, true);
					
					if(is_array($value) && isset($value["VALUE"]["name"]))
					{
						$result["n0"] = $value;
						$result["n1"] = Array("VALUE" => $temp, "DESCRIPTION" => $temp["name"]);
					}
					elseif(is_array($value) && !isset($value["name"]))
					{
						$result = $value;
						$result["n".count($value)] = Array("VALUE" => $temp, "DESCRIPTION" => $temp["name"]);
					}
					else
					{
						if(is_array($temp[0]) && isset($temp[0]["name"]))
							$result = Array("VALUE" => $temp[0], "DESCRIPTION" => $temp[0]["name"]);
						else
							$result = Array("VALUE" => $temp, "DESCRIPTION" => $temp["name"]);
					}
				}
				else
				{
					$result = $temp;
				}
			}
		}
		
		unset($logUrl);
		
		if(!$result && $value)
			$result = $value;
		
		return $result;
	}
	
	private static function CheckFields(&$fields, $params, &$additionalCode = false)
	{
		foreach($params as $code => $param)
		{
			if(
				(!isset($fields[$code]) || empty($fields[$code]))
				&& ($param["IS_REQUIRED"] == "Y" && is_string($param["DEFAULT_VALUE"]) && strlen($param["DEFAULT_VALUE"])>0)
			)
			{
				$fields[$code] = $param["DEFAULT_VALUE"];
			}
		}
		
		$previewImageParams = $params["PREVIEW_PICTURE"]["DEFAULT_VALUE"];
		
		$codeParams = $params["CODE"]["DEFAULT_VALUE"];
		if($codeParams["TRANSLITERATION"] == "Y")
		{
			if(is_array($additionalCode) && count($additionalCode))
			{
				uasort($additionalCode, 'CWebprostorImportUtils::CompareCodeSort');
				foreach($additionalCode as $value => $sort)
				{
					$translitName .= ' '.$value;
				}
				$translitName = trim($translitName);
			}
			elseif(!empty($fields["CODE"]))
				$translitName = $fields["CODE"];
			elseif(!empty($fields["NAME"]))
				$translitName = $fields["NAME"];
			
			if($translitName)
			{
				$fields["CODE"] = CWebprostorImportUtils::TranslitValue($translitName, $codeParams["TRANS_SPACE"], $codeParams["TRANS_OTHER"], $codeParams["TRANS_LEN"], $codeParams["TRANS_CASE"]);
			}
		}
	}
	
	private static function CheckImageFileUrl(&$rule, &$field, &$params, &$resultFields)
	{
		global $DEBUG_IMAGES, $DEBUG_FILES;
		
		if(($rule["IS_IMAGE"] == "Y" || $rule["IS_FILE"] == "Y") && $rule["IS_URL"] != "Y")
		{
			if($rule["IS_IMAGE"] == "Y")
			{
				$fileArr = self::GetFileArrayByFileName($field, $params["PATH_TO_IMAGES"], false, false, false, Array(), $DEBUG_IMAGES, $params);
				if($fileArr)
				{
					if(isset($resultFields) && is_array($resultFields))
					{
						if(!empty($resultFields) && !is_numeric(array_key_first($resultFields)))
						{
							$temp = $resultFields;
							$resultFields = [$temp];
							unset($temp);
						}
						$resultFields[] = $fileArr;
					}
					else
						$resultFields = $fileArr;
					
				}
			}
			elseif($rule["IS_FILE"] == "Y")
			{
				$fileArr = self::GetFileArrayByFileName($field, $params["PATH_TO_FILES"], false, false, false, Array(), $DEBUG_FILES, $params);
				if($fileArr)
				{
					if(isset($resultFields) && is_array($resultFields))
					{
						if(!empty($resultFields) && !is_numeric(array_key_first($resultFields)))
						{
							$temp = $resultFields;
							$resultFields = [$temp];
							unset($temp);
						}
						$resultFields[] = $fileArr;
					}
					else
						$resultFields = $fileArr;
				}
			}
			return true;
		}
		elseif($rule["IS_URL"] == "Y")
		{
			if(
				$rule["IBLOCK_ELEMENT_FIELD"] == "PREVIEW_PICTURE" || $rule["IBLOCK_ELEMENT_FIELD"] == "DETAIL_PICTURE"
				|| $rule["IBLOCK_ELEMENT_OFFER_FIELD"] == "PREVIEW_PICTURE" || $rule["IBLOCK_ELEMENT_OFFER_FIELD"] == "DETAIL_PICTURE"
				|| $rule["IBLOCK_SECTION_FIELD"] == "PICTURE" || $rule["IBLOCK_SECTION_FIELD"] == "DETAIL_PICTURE"
				|| $rule["IBLOCK_SECTION_PARENT_FIELD"] == "PICTURE" || $rule["IBLOCK_SECTION_PARENT_FIELD"] == "DETAIL_PICTURE"
				|| $rule["HIGHLOAD_BLOCK_ENTITY_FIELD"] == "UF_FILE"
			)
			{
				$resultFields = "GET_FILE_URL_".$field;
			}
			else
			{
				$fileArr = self::GetFileArrayByUrl($field, false, false, false, [], $params);
				
				if(isset($resultFields) && is_array($resultFields))
				{
					if(!empty($resultFields) && !is_numeric(array_key_first($resultFields)))
					{
						$temp = $resultFields;
						$resultFields = [$temp];
						unset($temp);
					}
					$resultFields[] = $fileArr;
				}
				else
					$resultFields = $fileArr;
			}
			return true;
		}
		else
			return false;
	}
	
	private static function CheckImageFileUrlForProperty(&$rule, &$field, &$params, &$resultFields, &$elementPropertyImages, &$elementPropertyFiles, $checkRule = "IBLOCK_ELEMENT_OFFER_PROPERTY", $whaterMarkParams, $objectEvent)
	{
		global $DEBUG_IMAGES, $DEBUG_FILES;
		
		if(($rule["IS_IMAGE"] == "Y" || $rule["IS_FILE"] == "Y") && $rule["IS_URL"] != "Y")
		{
			if($rule["IS_IMAGE"] == "Y")
			{
				if(is_array($field) && count($field))
				{
					foreach($field as $value)
					{
						$fileArr = self::GetFileArrayByFileName($value, $params["PATH_TO_IMAGES"], CWebprostorImportProperty::GetPropertyMultiply($rule[$checkRule]), $fileArr, $params["PROPERTIES_WHATERMARK"], $whaterMarkParams, $DEBUG_IMAGES, $params);
					}
				}
				else
				{
					$fileArr = self::GetFileArrayByFileName($field, $params["PATH_TO_IMAGES"], CWebprostorImportProperty::GetPropertyMultiply($rule[$checkRule]), $resultFields[$rule[$checkRule]], $params["PROPERTIES_WHATERMARK"], $whaterMarkParams, $DEBUG_IMAGES, $params);
				}
				
				if($fileArr)
					$resultFields[$rule[$checkRule]] = $fileArr;
				
				if(self::DELETE_OLD_PROPERTY_FILE_VALUE)
					$elementPropertyImages[] = $rule[$checkRule];
			}
			elseif($rule["IS_FILE"] == "Y")
			{
				if(is_array($field) && count($field))
				{
					foreach($field as $value)
					{
						$fileArr = self::GetFileArrayByFileName($value, $params["PATH_TO_FILES"], CWebprostorImportProperty::GetPropertyMultiply($rule[$checkRule]), $fileArr, $params["PROPERTIES_WHATERMARK"], Array(), $DEBUG_FILES, $params);
					}
				}
				else
				{
					$fileArr = self::GetFileArrayByFileName($field, $params["PATH_TO_FILES"], CWebprostorImportProperty::GetPropertyMultiply($rule[$checkRule]), $resultFields[$rule[$checkRule]], $params["PROPERTIES_WHATERMARK"], Array(), $DEBUG_FILES, $params);
				}
				
				if($fileArr)
					$resultFields[$rule[$checkRule]] = $fileArr;

				if(self::DELETE_OLD_PROPERTY_FILE_VALUE)
					$elementPropertyFiles[] = $rule[$checkRule];
			}
		}
		elseif($rule["IS_URL"] == "Y")
		{
			if(!($checkRule == "IBLOCK_ELEMENT_PROPERTY" && $objectEvent == "UPDATE" && $params["PROPERTIES_SKIP_DOWNLOAD_URL_UPDATE"] == "Y"))
			{
				if(is_array($field) && count($field) > 0)
				{
					if($params["PROPERTIES_USE_MULTITHREADED_DOWNLOADING"] != 'Y')
					{
						foreach($field as $value)
						{
							$fileArr = self::GetFileArrayByUrl($value, true, $fileArr, $params["PROPERTIES_WHATERMARK"], $whaterMarkParams, $params);
						}
					}
					else
					{
						$fileArr = self::GetFileArrayByUrlMulti($field, $resultFields[$rule[$checkRule]], $params["PROPERTIES_WHATERMARK"], $whaterMarkParams, $params);
					}
				}
				else
				{
					$fileArr = self::GetFileArrayByUrl($field, true, $resultFields[$rule[$checkRule]], $params["PROPERTIES_WHATERMARK"], $whaterMarkParams, $params);
				}
				
				if($fileArr)
					$resultFields[$rule[$checkRule]]  = $fileArr;
			}
		}
		else
			return false;
	}
	
	private static function CheckMap(&$rule, &$field, &$resultFields)
	{
		if($rule["IBLOCK_ELEMENT_PROPERTY_M"] == "latitude" || $rule["IBLOCK_ELEMENT_PROPERTY_M"] == "longitude")
		{
			$resultFields[strtoupper($rule["IBLOCK_ELEMENT_PROPERTY_M"])] = $field;
			return true;
		}
		else
			return false;
	}
	
	private static function checkUserField($code = false, $area = false, &$params)
	{
		$result = false;
		
		if(is_string($code) && strpos($code, "UF_") === 0)
		{
			$result['IS_USER_FIELD'] = true;
			if($area && $params['IBLOCK_ID'])
			$rsData = CUserTypeEntity::GetList(array("SORT" => "ASC"), array("ENTITY_ID" => "IBLOCK_{$params['IBLOCK_ID']}_{$area}", "FIELD_NAME" => $code));
		
			while($arRes = $rsData->Fetch())
			{
				$result['IS_MULTIPLE'] = $arRes['MULTIPLE'];
			}
			
			unset($rsData, $arRes);
		}
		
		return $result;
	}
	
	private static function GetSectionArray($item, &$rules, &$params, $defaultParams)
	{
		$sectionFields = Array();
		$sectionSearch = Array();
		
		global $ERROR_MESSAGE;
		
		foreach($item as $entity => $field)
		{
			/*if($params['IMPORT_FORMAT'] == 'XML')
				$temp_field = $field;*/
			$temp_field = $field;
			
			if(isset($rules[$entity]))
			{
				foreach($rules[$entity]["RULES"] as $rule)
				{
					/*if($params['IMPORT_FORMAT'] == 'XML')
						CWebprostorImportUtils::CheckAttribute($rule, $temp_field, $field);*/
					CWebprostorImportUtils::CheckAttribute($rule, $temp_field, $field);
				
					CWebprostorImportProcessingSettingsTypes::ApplyProcessingRules($field, $rule["PROCESSING_TYPES"]);
					
					$CheckRequired = CWebprostorImportUtils::CheckRequired($rule, $field);
					
					if($CheckRequired === false)
					{
						$ERROR_MESSAGE = Loc::getMessage('ERROR_REQUIRED_EMPTY_FIELD', ['#ENTITY#' => $entity]);
						return false;
					}
					
					$CheckArrayContinue = CWebprostorImportUtils::CheckArrayContinue($rule, $field);
					
					if($CheckArrayContinue === true)
						continue;
					
					if(!empty($rule["IBLOCK_SECTION_PARENT_FIELD"]) || !empty($rule["IBLOCK_SECTION_FIELD"]))
					{
						if(
							!empty($rule["IBLOCK_SECTION_PARENT_FIELD"]) && 
							(
								empty($rule["IBLOCK_SECTION_DEPTH_LEVEL"]) || 
								$rule["IBLOCK_SECTION_DEPTH_LEVEL"] === "0"
							)
						)
						{
							
							if(!isset($rule["IBLOCK_SECTION_DEPTH_LEVEL"]) || $rule["IBLOCK_SECTION_DEPTH_LEVEL"] === "0")
							{
								$rule["IBLOCK_SECTION_DEPTH_LEVEL"] = 1;
							}
							if(($rule["IS_REQUIRED"] == "N" && !empty($field)) || ($rule["IS_REQUIRED"] == "Y" || !empty($field)))
							{
								
								$sectionParentFields[$rule["IBLOCK_SECTION_DEPTH_LEVEL"]][$rule["IBLOCK_SECTION_PARENT_FIELD"]] = CWebprostorImportUtils::ClearField($field);
								
								if($rule["USE_IN_SEARCH"] == "Y")
									$sectionParentSearch[$rule["IBLOCK_SECTION_DEPTH_LEVEL"]][] = $rule["IBLOCK_SECTION_PARENT_FIELD"];
							}
						}
						elseif
						(
							!empty($rule["IBLOCK_SECTION_FIELD"]) && 
							(
								(
									!is_null($params["SECTIONS_MAX_DEPTH_LEVEL"]) && 
									$rule["IBLOCK_SECTION_DEPTH_LEVEL"] <= $params["SECTIONS_MAX_DEPTH_LEVEL"]
								) || 
								(
									is_null($params["SECTIONS_MAX_DEPTH_LEVEL"]) &&
									(
										empty($rule["IBLOCK_SECTION_DEPTH_LEVEL"]) || 
										$rule["IBLOCK_SECTION_DEPTH_LEVEL"] === "0"
									)
								)
							)
						)
						{
							if(!isset($rule["IBLOCK_SECTION_DEPTH_LEVEL"]) || $rule["IBLOCK_SECTION_DEPTH_LEVEL"] === "0")
							{
								$rule["IBLOCK_SECTION_DEPTH_LEVEL"] = 1;
							}
							if(($rule["IS_REQUIRED"] == "N" && !empty($field)) || ($rule["IS_REQUIRED"] == "Y" || !empty($field)))
							{
								$checkUserField = self::checkUserField($rule["IBLOCK_SECTION_FIELD"], 'SECTION', $params);
								
								if($checkUserField && $checkUserField['IS_MULTIPLE'] == 'Y' && !isset($sectionFields[$rule["IBLOCK_SECTION_DEPTH_LEVEL"]][$rule["IBLOCK_SECTION_FIELD"]]))
									$sectionFields[$rule["IBLOCK_SECTION_DEPTH_LEVEL"]][$rule["IBLOCK_SECTION_FIELD"]] = [];
								
								$CheckImageFileUrl = self::CheckImageFileUrl($rule, $field, $params, $sectionFields[$rule["IBLOCK_SECTION_DEPTH_LEVEL"]][$rule["IBLOCK_SECTION_FIELD"]]);
								
								if($CheckImageFileUrl === false)
								{
									if(strpos($rule["IBLOCK_SECTION_FIELD"], "SECTION_META") !== false)
									{
										$sectionFields[$rule["IBLOCK_SECTION_DEPTH_LEVEL"]]["IPROPERTY_TEMPLATES"][$rule["IBLOCK_SECTION_FIELD"]] = CWebprostorImportUtils::ClearField($field);
									}
									if(is_array($sectionFields[$rule["IBLOCK_SECTION_DEPTH_LEVEL"]][$rule["IBLOCK_SECTION_FIELD"]]))
										$sectionFields[$rule["IBLOCK_SECTION_DEPTH_LEVEL"]][$rule["IBLOCK_SECTION_FIELD"]][] = CWebprostorImportUtils::ClearField($field);
									else
										$sectionFields[$rule["IBLOCK_SECTION_DEPTH_LEVEL"]][$rule["IBLOCK_SECTION_FIELD"]] = CWebprostorImportUtils::ClearField($field);
								}
								
								if($rule["USE_IN_SEARCH"] == "Y")
									$sectionSearch[$rule["IBLOCK_SECTION_DEPTH_LEVEL"]][] = $rule["IBLOCK_SECTION_FIELD"];
								
								if($rule["USE_IN_CODE"] == "SECTION")
									$sectionCode[$rule["IBLOCK_SECTION_DEPTH_LEVEL"]][$field] = $rule["SORT"];
							}
						}
					}
				}
			}
		}
		
		$result = Array(
			"FIELDS" => $sectionFields,
			"SEARCH" => $sectionSearch,
			"ADDITIONAL_CODE" => $sectionCode,
			"FIELDS_PARENT" => $sectionParentFields,
			"SEARCH_PARENT" => $sectionParentSearch
		);
		
		if(is_array($sectionFields) && count($sectionFields)>0)
		{
			asort($sectionFields);
			foreach($sectionFields as $dl => $section)
			{
				$addCode = [];
				for($i = $dl; $i > 0; $i--)
				{
					if(is_array($result["ADDITIONAL_CODE"][$i]))
						$addCode = array_merge($addCode, $result["ADDITIONAL_CODE"][$i]);
				}
				$result["FIELDS"][$dl]["IBLOCK_ID"] = $params["IBLOCK_ID"];
				$fieldsIsChecked = self::CheckFields($result["FIELDS"][$dl], $defaultParams, $addCode);
				unset($addCode);
			}
		}
		
		if(is_array($sectionParentFields) && count($sectionParentFields)>0)
		{
			asort($sectionParentFields);
			foreach($sectionParentFields as $pdl => $parentSection)
			{
				$result["FIELDS_PARENT"][$pdl]["IBLOCK_ID"] = $params["IBLOCK_ID"];
				
				$fieldsIsChecked = self::CheckFields($result["FIELDS_PARENT"][$pdl], $defaultParams);
			}
		}
		
		return $result;
	}
	
	private static function ImportSection($arFields = Array(), $arSearchBy = Array(), $params = Array(), $onlySearch = false)
	{
		global $DEBUG_PLAN_ID, $DEBUG_EVENTS, $DEBUG_IMPORT_SECTION;
		
		if(!is_array($arFields))
			return false;
		
		if($DEBUG_EVENTS && $DEBUG_IMPORT_SECTION)
		{
			$logSection = new CWebprostorImportLog;
			$logSectionData = Array("PLAN_ID" => $DEBUG_PLAN_ID);
		}
		
		$bs = new CIBlockSection;
		ksort($arFields);
		
		foreach($arFields as $DEPTH_LEVEL => $arSection)
		{
			$arFilter = Array();
			if($arSection["IBLOCK_ID"])
				$arFilter['IBLOCK_ID'] = $arSection["IBLOCK_ID"];
			if($DEPTH_LEVEL > 1 && $PARENT_SECTION_ID)
			{
				$arFilter['SECTION_ID'] = $PARENT_SECTION_ID;
				$arSection['IBLOCK_SECTION_ID'] = $PARENT_SECTION_ID;
			}
			
			//$arSelect = Array("IBLOCK_ID", "ID", "UF_*", "IBLOCK_SECTION_ID", "DEPTH_LEVEL");
			$arSelect = Array("IBLOCK_ID", "ID", "IBLOCK_SECTION_ID", "DEPTH_LEVEL");
			if(is_array($arSearchBy[$DEPTH_LEVEL]) && count($arSearchBy[$DEPTH_LEVEL])>0)
			{
				foreach($arSearchBy[$DEPTH_LEVEL] as $code)
				{
					$arFilter[$code] = $arSection[$code];
					if(!in_array($code, $arSelect))
						$arSelect[] = $code;
				}
			}
			
			if(
				is_array($arFilter) && 
				count($arFilter)>0 && 
				!(count($arFilter) == 1 && array_key_exists("IBLOCK_ID", $arFilter)) && 
				!(count($arFilter) == 2 && array_key_exists("IBLOCK_ID", $arFilter) && array_key_exists("SECTION_ID", $arFilter))
			)
			{
				if($DEBUG_EVENTS && $DEBUG_IMPORT_SECTION)
				{
					$logSectionData["EVENT"] = "IMPORT_ITEM_FILTER_DEPTH_LEVEL_".$DEPTH_LEVEL;
					$logSectionData["MESSAGE"] = Loc::getMessage("MESSAGE_IMPORT_ITEM_FILTER", Array("#OBJECT#" => "Section"));
					//$logSectionData["DATA"] = base64_encode(serialize(array_merge(array_keys($arFilter), $arFilter)));
					$logSectionData["DATA"] = base64_encode(serialize($arFilter));
					$logSection->Add($logSectionData);
				}
				
				$sectionsRes = $bs->GetList(
					["SORT" => "ASC"], 
					$arFilter, 
					false, 
					$arSelect, 
					["nPageSize"=>1]
				);
				$findSection = false;
				while($findSection2 = $sectionsRes->GetNext(true, false))
				{
					$ID = $findSection2['ID'];
					$event = "SEARCH";
					
					$findSection = $findSection2;
				}
			}
			else
			{
				if($DEBUG_EVENTS && $DEBUG_IMPORT_SECTION)
				{
					$logSectionData["EVENT"] = "IMPORT_ITEM_FILTER_NO_SEARCH";
					$logSectionData["MESSAGE"] = Loc::getMessage("MESSAGE_IMPORT_ITEM_FILTER_NO_SEARCH", Array("#OBJECT#" => "Section"));
					$logSection->Add($logSectionData);
				}
			}
			
			if($DEBUG_EVENTS && $DEBUG_IMPORT_SECTION)
			{
				if($ID > 0)
				{
					$logSectionData["EVENT"] = "IMPORT_ITEM_FILTER_RESULT";
					$logSectionData["MESSAGE"] = Loc::getMessage("MESSAGE_IMPORT_ITEM_FILTER_RESULT", Array("#OBJECT#" => "Section"));
					//$logSectionData["DATA"] = base64_encode(serialize(array_merge(array_keys($findSection), $findSection)));
					$logSectionData["DATA"] = base64_encode(serialize($findSection));
					$logSection->Add($logSectionData);
				}
				elseif(!$ID && !$onlySearch)
				{
					$logSectionData["EVENT"] = "IMPORT_ITEM_FILTER_NO_RESULT";
					$logSectionData["MESSAGE"] = Loc::getMessage("MESSAGE_IMPORT_ITEM_FILTER_NO_RESULT", Array("#OBJECT#" => "Section"));
					//$logSectionData["DATA"] = base64_encode(serialize(array_merge(array_keys($arSection), $arSection)));
					$logSectionData["DATA"] = base64_encode(serialize($arSection));
					$logSection->Add($logSectionData);
				}
			}
			
			if(!$onlySearch)
			{
				if($ID > 0)
				{
					if($params["SECTIONS_UPDATE"] == "Y")
					{
						if(!isset($arSection["TIMESTAMP_X"]))
							$arSection["TIMESTAMP_X"] = date("d.m.Y H:i:s");
						
						self::CheckObjectValuesToUrl($arSection, $params);
						
						$res = $bs->Update($ID, $arSection, true, ($params["SECTIONS_UPDATE_SEARCH"] == "Y"?true:false), ($params["RESIZE_IMAGE"] == "Y"?true:false));
						if($res)
							$event = "UPDATE";
					}
				}
				else
				{
					if($params["SECTIONS_ADD"] == "Y")
					{
						if($params["SECTIONS_DEFAULT_ACTIVE"] == "Y")
						{
							$arSection["ACTIVE"] = "Y";
						}
						else
						{
							$arSection["ACTIVE"] = "N";
						}
						
						self::CheckObjectValuesToUrl($arSection, $params);
						
						$ID = $bs->Add($arSection, true, ($params["SECTIONS_UPDATE_SEARCH"] == "Y"?true:false), ($params["RESIZE_IMAGE"] == "Y"?true:false));
						$res = ($ID>0);
						if($res)
							$event = "ADD";
					}
					else
					{
						if($DEBUG_EVENTS && $DEBUG_IMPORT_SECTION)
						{
							$logSectionData["EVENT"] = "IMPORT_ITEM_ADD_DISABLED";
							$logSectionData["MESSAGE"] = Loc::getMessage("MESSAGE_IMPORT_ITEM_ADD_DISABLED", Array("#OBJECT#" => "Section"));
							$logSectionData["DATA"] = null;
							$logSection->Add($logSectionData);
						}
					}
				}
			}
			
			if($ID>0)
				$PARENT_SECTION_ID = $ID;
			else
				unset($PARENT_SECTION_ID);
			
			if(isset($res) && !$res)
			{
				$event = "ERROR";
				$error = $bs->LAST_ERROR;
			}
		
			$result = Array(
				"ID" => $ID,
				"EVENT" => $event,
				"ERROR" => $error,
			);
			
			unset(
				$ID,
				$event,
				$error
			);
		}
			
		unset($bs, $sectionsRes, $res, $logSection);
		
		return $result;
	}

	private static function GetElementArray($item, &$rules, &$params, $SECTION_ID = false, &$defaultParams)
	{
		$elementFields = Array();
		$elementCode = Array();
		$elementSearch = Array();
		$elementSearchProperty = Array();
		$elementSearchPropertyLinkFields = Array();
		$elementPropertyImages = Array();
		$elementPropertyFiles = Array();
		
		global $DEBUG_IMAGES, $DEBUG_FILES, $ERROR_MESSAGE;
					
		foreach($item as $entity => $field)
		{
			$temp_field = $field;
			if(isset($rules[$entity]))
			{
				foreach($rules[$entity]["RULES"] as $rule)
				{
					CWebprostorImportUtils::CheckAttribute($rule, $temp_field, $field);
					
					CWebprostorImportProcessingSettingsTypes::ApplyProcessingRules($field, $rule["PROCESSING_TYPES"]);
					
					$CheckRequired = CWebprostorImportUtils::CheckRequired($rule, $field);
					
					if($CheckRequired === false)
					{
						$ERROR_MESSAGE = Loc::getMessage('ERROR_REQUIRED_EMPTY_FIELD', ['#ENTITY#' => $entity]);
						return false;
					}
					
					$CheckArrayContinue = CWebprostorImportUtils::CheckArrayContinue($rule, $field);
					
					if($CheckArrayContinue === true)
						continue;
						
					if(!empty($rule["IBLOCK_SECTION_FIELD"]) || !empty($rule["IBLOCK_ELEMENT_FIELD"]) || !empty($rule["IBLOCK_ELEMENT_PROPERTY"]) || $rule["USE_IN_CODE"] == 'ELEMENT')
					{
						if(!empty($rule["IBLOCK_ELEMENT_FIELD"]))
						{
							if($rule["IS_REQUIRED"] == "N" || ($rule["IS_REQUIRED"] == "Y" || !empty($field)))
							{
								
								$CheckImageFileUrl = self::CheckImageFileUrl($rule, $field, $params, $elementFields[$rule["IBLOCK_ELEMENT_FIELD"]]);
								
								if($CheckImageFileUrl === false)
								{
									if(strpos($rule["IBLOCK_ELEMENT_FIELD"], "ELEMENT_META") !== false)
									{
										$elementFields["IPROPERTY_TEMPLATES"][$rule["IBLOCK_ELEMENT_FIELD"]] = CWebprostorImportUtils::ClearField($field);
									}
									$elementFields[$rule["IBLOCK_ELEMENT_FIELD"]] = CWebprostorImportUtils::ClearField($field);
								}
								
								if($rule["USE_IN_SEARCH"] == "Y")
									$elementSearch[] = $rule["IBLOCK_ELEMENT_FIELD"];
							}
						}
						if(!empty($rule["IBLOCK_ELEMENT_PROPERTY"]))
						{
							if($rule["IS_REQUIRED"] == "N" || ($rule["IS_REQUIRED"] == "Y" || !empty($field)))
							{
								
								$elementFields["PROPERTY_VALUES"][$rule["IBLOCK_ELEMENT_PROPERTY"]] = CWebprostorImportUtils::ClearField($field);
								
								if($rule["USE_IN_SEARCH"] == "Y")
								{
									$elementSearchProperty[] = $rule["IBLOCK_ELEMENT_PROPERTY"];
									if($rule["IBLOCK_ELEMENT_PROPERTY_E"] != "")
									{
										$elementSearchPropertyLinkFields[$rule["IBLOCK_ELEMENT_PROPERTY"]] = $rule["IBLOCK_ELEMENT_PROPERTY_E"];
									}
								}
							}
						}
						
						if
						(
							(
								$rule["IS_REQUIRED"] == "N" 
								|| (
									$rule["IS_REQUIRED"] == "Y" 
									|| !empty($field)
								)
							)
							&& $rule["USE_IN_CODE"] == "ELEMENT"
						)
						{
							$elementCode[$field] = $rule["SORT"];
						}
					}
				}
			}
		}
		unset($item);
		
		if(is_array($elementFields) && count($elementFields)>0)
		{
			$elementFields["IBLOCK_ID"] = $params["IBLOCK_ID"];
		}
		if($SECTION_ID>0)
		{
			$elementFields["IBLOCK_SECTION_ID"] = $SECTION_ID;
		}
		
		$result = Array(
			"FIELDS" => $elementFields,
			"UPDATE_FIELDS" => $elementFields,
			"ADDITIONAL_CODE" => $elementCode,
			"SEARCH" => $elementSearch,
			"SEARCH_PROPERTY" => $elementSearchProperty,
			"SEARCH_PROPERTY_LINK_FIELDS" => $elementSearchPropertyLinkFields,
			"DELETE_PROPERTY_IMAGES" => $elementPropertyImages,
			"DELETE_PROPERTY_FILES" => $elementPropertyFiles
		);
		
		unset(
			$elementFields, 
			$elementCode, 
			$elementSearch, 
			$elementSearchProperty, 
			$elementSearchPropertyLinkFields, 
			$elementPropertyImages, 
			$elementPropertyFiles
		);
		
		$fieldsIsChecked = self::CheckFields($result["FIELDS"], $defaultParams, $result["ADDITIONAL_CODE"]);
		
		return $result;
	}
	
	private static function GetElementOfferArray($item, &$rules, &$params, $ELEMENT_ID = false, &$defaultParams, &$whaterMarkParams)
	{
		$elementFields = Array();

		if($params["PROPERTIES_SET_DEFAULT_VALUES"] == "Y" && CModule::IncludeModule("webprostor.core"))
			$elementFields["PROPERTY_VALUES"] = CWebprostorCoreIblock::GetDefaultProperties($params["OFFERS_IBLOCK_ID"], ["USE_LIST_VALUE" => "Y", "USE_HTML_TEXT" => "Y"]);
		
		$elementSearch = Array();
		$elementSearchProperty = Array();
		$elementPropertyImages = Array();
		$elementPropertyFiles = Array();
		
		global $DEBUG_IMAGES, $DEBUG_FILES, $ERROR_MESSAGE;
		
		foreach($item as $entity => $field)
		{
			$temp_field = $field;
			if(isset($rules[$entity]))
			{
				foreach($rules[$entity]["RULES"] as $rule)
				{
					CWebprostorImportUtils::CheckAttribute($rule, $temp_field, $field);
					
					CWebprostorImportProcessingSettingsTypes::ApplyProcessingRules($field, $rule["PROCESSING_TYPES"]);
					
					$CheckRequired = CWebprostorImportUtils::CheckRequired($rule, $field);
					
					if($CheckRequired === false)
					{
						$ERROR_MESSAGE = Loc::getMessage('ERROR_REQUIRED_EMPTY_FIELD', ['#ENTITY#' => $entity]);
						return false;
					}
					
					if(!empty($rule["IBLOCK_ELEMENT_OFFER_FIELD"]) || !empty($rule["IBLOCK_ELEMENT_OFFER_PROPERTY"]))
					{
						
						if(!empty($rule["IBLOCK_ELEMENT_OFFER_FIELD"]))
						{
						
							$CheckArrayContinue = CWebprostorImportUtils::CheckArrayContinue($rule, $field);
							if($CheckArrayContinue === true)
								continue;
							
							if($rule["IS_REQUIRED"] == "N" || ($rule["IS_REQUIRED"] == "Y" || !empty($field)))
							{
								
								$CheckImageFileUrl = self::CheckImageFileUrl($rule, $field, $params, $elementFields[$rule["IBLOCK_ELEMENT_OFFER_FIELD"]]);
								
								if($CheckImageFileUrl === false)
								{
									$elementFields[$rule["IBLOCK_ELEMENT_OFFER_FIELD"]] = CWebprostorImportUtils::ClearField($field);
								}
								
								if($rule["USE_IN_SEARCH"] == "Y")
									$elementSearch[] = $rule["IBLOCK_ELEMENT_OFFER_FIELD"];
							}
						}
						
						if(!empty($rule["IBLOCK_ELEMENT_OFFER_PROPERTY"]))
						{
							
							if($rule["IS_REQUIRED"] == "N" || ($rule["IS_REQUIRED"] == "Y" || !empty($field)))
							{
								
								$CheckMap = self::CheckMap($rule, $field, $elementFields["PROPERTY_VALUES"][$rule["IBLOCK_ELEMENT_OFFER_PROPERTY"]]);
								
								$CheckImageFileUrlForProperty = self::CheckImageFileUrlForProperty($rule, $field, $params, $elementFields["PROPERTY_VALUES"], $elementPropertyImages, $elementPropertyFiles, "IBLOCK_ELEMENT_OFFER_PROPERTY", $whaterMarkParams, '');
								
								if($CheckMap === false && $CheckImageFileUrlForProperty === false)
								{
									CWebprostorImportProperty::LinkPropertyValue($rule["IBLOCK_ELEMENT_OFFER_PROPERTY"], $field, $ID, $params["OFFERS_IBLOCK_ID"], $params, $data, $elementFields["PROPERTY_VALUES"][$rule["IBLOCK_ELEMENT_OFFER_PROPERTY"]]);
								}
								
								if($rule["USE_IN_SEARCH"] == "Y")
									$elementSearchProperty[] = $rule["IBLOCK_ELEMENT_OFFER_PROPERTY"];
							}
						}
					}
				}
			}
		}
		unset($temp_field);
		
		if(count($elementFields)>0)
		{
			$elementFields["IBLOCK_ID"] = $params["OFFERS_IBLOCK_ID"];
			
			if($ELEMENT_ID>0)
			{
				$elementFields["PROPERTY_VALUES"][$params["OFFERS_SKU_PROPERTY_ID"]] = $ELEMENT_ID;
				$elementSearchProperty[] = $params["OFFERS_SKU_PROPERTY_ID"];
				
				if($params["OFFERS_SET_NAME_FROM_ELEMENT"] == "Y")
				{
					$elementFields["NAME"] = CWebprostorImportElement::GetElementNameByID($ELEMENT_ID, $params);
				}
			}
		}
		
		$result = Array(
			"FIELDS" => $elementFields,
			"UPDATE_FIELDS" => $elementFields,
			"SEARCH" => $elementSearch,
			"SEARCH_PROPERTY" => $elementSearchProperty,
			"DELETE_PROPERTY_IMAGES" => $elementPropertyImages,
			"DELETE_PROPERTY_FILES" => $elementPropertyFiles
		);
		
		unset(
			$elementFields,
			$elementSearch,
			$elementSearchProperty,
			$elementPropertyImages,
			$elementPropertyFiles
		);
		
		$fieldsIsChecked = self::CheckFields($result["FIELDS"], $defaultParams);
		
		return $result;
	}
	
	private static function ImportElement($data, &$params)
	{
		
		global $DEBUG_PLAN_ID, $DEBUG_EVENTS, $DEBUG_IMPORT_ELEMENTS;

		if($DEBUG_EVENTS && $DEBUG_IMPORT_ELEMENTS)
		{
			$logElement = new CWebprostorImportLog;
			$logElementData = Array("PLAN_ID" => $DEBUG_PLAN_ID);
		}
		
		$el = new CIBlockElement;
		$arFields = $data["FIELDS"];
		$arSearchBy = $data["SEARCH"];
		$arSearchByProperty = $data["SEARCH_PROPERTY"];
		
		if($arFields["IBLOCK_ID"]>0)
			$arFilter = Array('IBLOCK_ID'=>$arFields["IBLOCK_ID"]);
		$arSelect = Array("IBLOCK_ID", "ID");
		
		if($params["ELEMENTS_DEFAULT_SECTION_ID"]>0)
		{
			$arFilter['SECTION_ID'] = $params["ELEMENTS_DEFAULT_SECTION_ID"];
			$arFields['IBLOCK_SECTION_ID'] = $params["ELEMENTS_DEFAULT_SECTION_ID"];
			$arSelect[] = "IBLOCK_SECTION_ID";
		}
		
		if($params["ELEMENTS_DONT_UPDATE_IMAGES_IF_EXISTS"] == 'Y')
		{
			if(!isset($arSelect['PREVIEW_PICTURE']) && isset($arFields['PREVIEW_PICTURE']))
				$arSelect[] = 'PREVIEW_PICTURE';
			if(!isset($arSelect['DETAIL_PICTURE']) && isset($arFields['DETAIL_PICTURE']))
				$arSelect[] = 'DETAIL_PICTURE';
		}

		foreach($arSearchBy as $code)
		{
			if($arFields[$code] != "")
			{
				$arFilter[$code] = $arFields[$code];
				if(!in_array($code, $arSelect))
					$arSelect[] = $code;
			}
		}
		unset($arSearchBy);
		foreach($arSearchByProperty as $code)
		{
			$propRes = CIBlockProperty::GetById($code);
			$propFields = $propRes->Fetch();
			
			if($propFields["PROPERTY_TYPE"] == "E" && isset($data["SEARCH_PROPERTY_LINK_FIELDS"][$code]))
				$PROPERTY_CODE = "PROPERTY_".$code.".".$data["SEARCH_PROPERTY_LINK_FIELDS"][$code];
			elseif($propFields["PROPERTY_TYPE"] == "G" && isset($data["SEARCH_PROPERTY_LINK_FIELDS"][$code]))
				$PROPERTY_CODE = "PROPERTY_".$code.".".$data["SEARCH_PROPERTY_LINK_FIELDS"][$code];
			elseif($propFields["PROPERTY_TYPE"] == "L")
				$PROPERTY_CODE = "PROPERTY_".$code."_VALUE";
			else
				$PROPERTY_CODE = "PROPERTY_".$code;
			
			if($arFields["PROPERTY_VALUES"][$code] != "")
			{
				$arFilter[$PROPERTY_CODE] = $arFields["PROPERTY_VALUES"][$code];
				if(!in_array($code, $arSelect))
					$arSelect[] = $PROPERTY_CODE;
			}
		}
		unset($arSearchByProperty);
		
		if($params["ELEMENTS_2_STEP_SEARCH"] == "Y")
		{
			unset($arFilter['SECTION_ID']);
			unset($arFilter['IBLOCK_SECTION_ID']);
			$tempSectionID = $arFields['IBLOCK_SECTION_ID'];
			unset($arFields['IBLOCK_SECTION_ID']);
		}
		
		if(
			is_array($arFilter) && 
			count($arFilter)>0 && 
			!(count($arFilter) == 1 && array_key_exists("IBLOCK_ID", $arFilter))
		)
		{
			$arPreFilter = CWebprostorImportFilter::SerializePreFilter($params['ELEMENTS_PREFILTER']);
			if(is_array($arPreFilter))
			{
				$arFilter = array_merge($arPreFilter, $arFilter);
			}
			
			if($DEBUG_EVENTS && $DEBUG_IMPORT_ELEMENTS)
			{
				$logElementData["EVENT"] = "IMPORT_ITEM_FILTER";
				$logElementData["MESSAGE"] = Loc::getMessage("MESSAGE_IMPORT_ITEM_FILTER", Array("#OBJECT#" => "Element"));
				//$logElementData["DATA"] = base64_encode(serialize(array_merge(array_keys($arFilter), $arFilter)));
				$logElementData["DATA"] = base64_encode(serialize($arFilter));
				$logElement->Add($logElementData);
			}
			
			$elementRes = $el->GetList(
				["SORT" => "ASC"], 
				$arFilter, 
				false, 
				["nPageSize"=>1], 
				$arSelect
			);
			$findElement = false;
			while($findElement2 = $elementRes->GetNext(true, false))
			{
				$ID = $findElement2['ID'];
				$event = "SEARCH";
				
				$findElement = $findElement2;
			}
		}
		else
		{
			if($DEBUG_EVENTS && $DEBUG_IMPORT_ELEMENTS)
			{
				$logElementData["EVENT"] = "IMPORT_ITEM_FILTER_NO_SEARCH";
				$logElementData["MESSAGE"] = Loc::getMessage("MESSAGE_IMPORT_ITEM_FILTER_NO_SEARCH", Array("#OBJECT#" => "Element"));
				$logElement->Add($logElementData);
			}
		}
		
		unset($arFields["PROPERTY_VALUES"]);
		
		if($ID > 0)
		{
			if($params["ELEMENTS_UPDATE"] == "Y")
			{
				if($DEBUG_EVENTS && $DEBUG_IMPORT_ELEMENTS)
				{
					$logElementData["EVENT"] = "IMPORT_ITEM_FILTER_RESULT";
					$logElementData["MESSAGE"] = Loc::getMessage("MESSAGE_IMPORT_ITEM_FILTER_RESULT", Array("#OBJECT#" => "Element"));
					//$logElementData["DATA"] = base64_encode(serialize(array_merge(array_keys($findElement), $findElement)));
					$logElementData["DATA"] = base64_encode(serialize($findElement));
					$logElement->Add($logElementData);
				}
				
				foreach($arFields as $code => $value)
				{
					if($code == "CODE" && $data["ADDITIONAL_CODE"] != '')
						continue;
					
					if($params["ELEMENTS_DONT_UPDATE_IMAGES_IF_EXISTS"] == 'Y')
					{
						if($code == 'PREVIEW_PICTURE' && isset($findElement['PREVIEW_PICTURE']))
							unset($arFields[$code]);
						if($code == 'DETAIL_PICTURE' && isset($findElement['DETAIL_PICTURE']))
							unset($arFields[$code]);
					}
					
					if(!isset($data["UPDATE_FIELDS"][$code]))
						unset($arFields[$code]);
				}
				
				self::CheckObjectValuesToUrl($arFields, $params);
				
				unset($data);
				
				$res = $el->Update($ID, $arFields, false, ($params["ELEMENTS_UPDATE_SEARCH"] == "Y"?true:false), ($params["RESIZE_IMAGE"] == "Y"?true:false));
				if($res)
					$event = "UPDATE";
			}
		}
		else
		{
		
			if($params["ELEMENTS_2_STEP_SEARCH"] == "Y" && isset($tempSectionID))
			{
				$arFields['IBLOCK_SECTION_ID'] = $tempSectionID;
			}
			if($params["ELEMENTS_ADD"] == "Y" && is_array($arFields) && count($arFields)>0)
			{
				if($DEBUG_EVENTS && $DEBUG_IMPORT_ELEMENTS)
				{
					$logElementData["EVENT"] = "IMPORT_ITEM_FILTER_NO_RESULT";
					$logElementData["MESSAGE"] = Loc::getMessage("MESSAGE_IMPORT_ITEM_FILTER_NO_RESULT", Array("#OBJECT#" => "Element"));
					//$logElementData["DATA"] = base64_encode(serialize(array_merge(array_keys($arFields), $arFields)));
					$logElementData["DATA"] = base64_encode(serialize($arFields));
					$logElement->Add($logElementData);
				}
				
				if($params["ELEMENTS_DEFAULT_ACTIVE"] == "Y")
					$arFields["ACTIVE"] = "Y";
				else
					$arFields["ACTIVE"] = "N";
				
				if($params["ELEMENTS_DEFAULT_DESCRIPTION_TYPE"] != "def")
				{
					if(isset($arFields["PREVIEW_TEXT"]))
						$arFields["PREVIEW_TEXT_TYPE"] = $params["ELEMENTS_DEFAULT_DESCRIPTION_TYPE"];
					if(isset($arFields["DETAIL_TEXT"]))
						$arFields["DETAIL_TEXT_TYPE"] = $params["ELEMENTS_DEFAULT_DESCRIPTION_TYPE"];
				}
				
				self::CheckObjectValuesToUrl($arFields, $params);
				
				$ID = $el->Add($arFields, false, ($params["ELEMENTS_UPDATE_SEARCH"] == "Y"?true:false), ($params["RESIZE_IMAGE"] == "Y"?true:false));
				$res = ($ID>0);
				if($res)
					$event = "ADD";
			}
		}
		
		unset($tempSectionID, $arFields);
		
		if(isset($res) && !$res)
		{
			$event = "ERROR";
			$error = $el->LAST_ERROR;
			
			if($DEBUG_EVENTS && $DEBUG_IMPORT_ELEMENTS)
			{
				$logElementData["EVENT"] = "ADD_UPDATE_ITEM";
				$logElementData["MESSAGE"] = Loc::getMessage("ERROR_CANNOT_ADD_UPDATE_ITEM");
				$logElementData["DATA"] = $error;
				$logElement->Add($logElementData);
			}
		}
		
		if(!is_null($ID) || !is_null($event) || !is_null($error))
		{
			$result = Array(
				"ID" => $ID,
				"EVENT" => $event,
				"ERROR" => $error,
			);
		}
		
		unset($el, $res, $elementRes, $logElement);
		
		return $result;
	}
	
	private static function ImportElementOffer($data, $params = Array())
	{
		global $DEBUG_PLAN_ID, $DEBUG_EVENTS, $DEBUG_IMPORT_ELEMENTS;
		
		if($DEBUG_EVENTS && $DEBUG_IMPORT_ELEMENTS)
		{
			$logElement = new CWebprostorImportLog;
			$logElementData = Array("PLAN_ID" => $DEBUG_PLAN_ID);
		}
		
		$el = new CIBlockElement;
		$arFields = $data["FIELDS"];
		$arSearchBy = $data["SEARCH"];
		$arSearchByProperty = $data["SEARCH_PROPERTY"];
		
		$arFilter = Array('IBLOCK_ID'=>$arFields["IBLOCK_ID"]);
		$arSelect = Array("IBLOCK_ID", "ID");
		foreach($arSearchBy as $code)
		{
			if($arFields[$code] != "")
			{
				$arFilter[$code] = $arFields[$code];
				if(!in_array($code, $arSelect))
					$arSelect[] = $code;
			}
		}
		foreach($arSearchByProperty as $code)
		{
			if($arFields["PROPERTY_VALUES"][$code] != "")
			{
				$arFilter["PROPERTY_".$code] = $arFields["PROPERTY_VALUES"][$code];
				if(!in_array($code, $arSelect))
					$arSelect[] = "PROPERTY_".$code;
			}
		}
			
		if(
			is_array($arFilter) && 
			count($arFilter)>0 && 
			!(count($arFilter) == 1 && array_key_exists("IBLOCK_ID", $arFilter))
		)
		{
			
			if($DEBUG_EVENTS && $DEBUG_IMPORT_ELEMENTS)
			{
				$logElementData["EVENT"] = "IMPORT_ITEM_FILTER";
				$logElementData["MESSAGE"] = Loc::getMessage("MESSAGE_IMPORT_ITEM_FILTER", Array("#OBJECT#" => "ElementOffer"));
				//$logElementData["DATA"] = base64_encode(serialize(array_merge(array_keys($arFilter), $arFilter)));
				$logElementData["DATA"] = base64_encode(serialize($arFilter));
				$logElement->Add($logElementData);
			}
			
			$elementRes = $el->GetList(
				["SORT" => "ASC"], 
				$arFilter, 
				false, 
				["nPageSize"=>1], 
				$arSelect
			);
			$findElement = false;
			while($findElement2 = $elementRes->GetNext(true, false))
			{
				$ID = $findElement2['ID'];
				$event = "SEARCH";
				
				$findElement = $findElement2;
			}
		}
		else
		{
			if($DEBUG_EVENTS && $DEBUG_IMPORT_ELEMENTS)
			{
				$logElementData["EVENT"] = "IMPORT_ITEM_FILTER_NO_SEARCH";
				$logElementData["MESSAGE"] = Loc::getMessage("MESSAGE_IMPORT_ITEM_FILTER_NO_SEARCH", Array("#OBJECT#" => "ElementOffer"));
				$logElement->Add($logElementData);
			}
		}
		
		if($DEBUG_EVENTS && $DEBUG_IMPORT_ELEMENTS)
		{
			if($ID > 0)
			{
				$logElementData["EVENT"] = "IMPORT_ITEM_FILTER_RESULT";
				$logElementData["MESSAGE"] = Loc::getMessage("MESSAGE_IMPORT_ITEM_FILTER_RESULT", Array("#OBJECT#" => "ElementOffer"));
				//$logElementData["DATA"] = base64_encode(serialize(array_merge(array_keys($findElement), $findElement)));
				$logElementData["DATA"] = base64_encode(serialize($findElement));
				$logElement->Add($logElementData);
			}
			elseif(!$ID)
			{
				$logElementData["EVENT"] = "IMPORT_ITEM_FILTER_NO_RESULT";
				$logElementData["MESSAGE"] = Loc::getMessage("MESSAGE_IMPORT_ITEM_FILTER_NO_RESULT", Array("#OBJECT#" => "ElementOffer"));
				//$logElementData["DATA"] = base64_encode(serialize(array_merge(array_keys($arFields), $arFields)));
				$logElementData["DATA"] = base64_encode(serialize($arFields));
				$logElement->Add($logElementData);
			}
		}
		
		if($ID > 0)
		{
			if($params["OFFERS_UPDATE"] == "Y")
			{
				unset($arFields["PROPERTY_VALUES"]);
						
				self::CheckObjectValuesToUrl($arFields, $params);
				
				$res = $el->Update($ID, $arFields, false, false, ($params["RESIZE_IMAGE"] == "Y"?true:false));
				if($res)
					$event = "UPDATE";
			}
		}
		else
		{
			if($params["OFFERS_ADD"] == "Y")
			{	
				self::CheckObjectValuesToUrl($arFields, $params);
				
				$ID = $el->Add($arFields, false, true, ($params["RESIZE_IMAGE"] == "Y")?true:false);
				$res = ($ID>0);
				if($res)
					$event = "ADD";
			}
		}
		
		if(isset($res) && !$res)
		{
			$event = "ERROR";
			$error = $el->LAST_ERROR;
			
			if($DEBUG_EVENTS && $DEBUG_IMPORT_ELEMENTS)
			{
				$logElementData["EVENT"] = "ADD_UPDATE_ITEM";
				$logElementData["MESSAGE"] = Loc::getMessage("ERROR_CANNOT_ADD_UPDATE_ITEM");
				$logElementData["DATA"] = $error;
				$logElement->Add($logElementData);
			}
		}
		
		unset($el, $res, $elementRes, $logElement);
		
		$result = Array(
			"ID" => $ID,
			"EVENT" => $event,
			"ERROR" => $error
		);
		
		return $result;
	}
	
	private static function GetPropertiesArray($item, &$rules, &$params, &$whaterMarkParams, $IBLOCK_ID = false, $CONNECTION_TYPE = "IBLOCK_ELEMENT_PROPERTY", $objectEvent = '')
	{
		if($params["PROPERTIES_SET_DEFAULT_VALUES"] == "Y" && CModule::IncludeModule("webprostor.core"))
			$elementProperties = CWebprostorCoreIblock::GetDefaultProperties($IBLOCK_ID, ["USE_LIST_VALUE" => "Y", "SKIP_HTML" => "Y", "USE_HTML_TEXT" => "Y"]);
		else
			$elementProperties = Array();
		
		$elementSearchProperty = Array();
		$elementSearchPropertyE = Array();
		$elementSearchPropertyG = Array();
		$elementPropertyImages = Array();
		$elementPropertyFiles = Array();
		
		global $DEBUG_IMAGES, $DEBUG_FILES, $ERROR_MESSAGE;

		foreach($item as $entity => $field)
		{
			$temp_field = $field;
			if(isset($rules[$entity]))
			{
				foreach($rules[$entity]["RULES"] as $rule)
				{
					if(!empty($rule[$CONNECTION_TYPE]))
					{
						CWebprostorImportUtils::CheckAttribute($rule, $temp_field, $field);
						
						CWebprostorImportProcessingSettingsTypes::ApplyProcessingRules($field, $rule["PROCESSING_TYPES"]);
						
						$CheckRequired = CWebprostorImportUtils::CheckRequired($rule, $field);
						
						if($CheckRequired === false)
						{
							$ERROR_MESSAGE = Loc::getMessage('ERROR_REQUIRED_EMPTY_FIELD', ['#ENTITY#' => $entity]);
							return false;
						}
						
						if($rule["IS_REQUIRED"] == "N" || ($rule["IS_REQUIRED"] == "Y" && !empty($field)))
						{
							$CheckMap = self::CheckMap($rule, $field, $elementProperties[$rule[$CONNECTION_TYPE]]);
							$CheckImageFileUrlForProperty = self::CheckImageFileUrlForProperty($rule, $field, $params, $elementProperties, $elementPropertyImages, $elementPropertyFiles, $CONNECTION_TYPE, $whaterMarkParams, $objectEvent);
							
							if($CheckMap === false && $CheckImageFileUrlForProperty === false)
							{
								$elementProperties[$rule[$CONNECTION_TYPE]] = CWebprostorImportUtils::ClearField($field);
							}
							
							if($rule["USE_IN_SEARCH"] == "Y")
								$elementSearchProperty[] = $rule[$CONNECTION_TYPE];
							
							if($rule["IBLOCK_ELEMENT_PROPERTY_E"])
								$elementSearchPropertyE[$rule[$CONNECTION_TYPE]] = $rule["IBLOCK_ELEMENT_PROPERTY_E"];
							
							if($rule["IBLOCK_ELEMENT_PROPERTY_G"])
								$elementSearchPropertyG[$rule[$CONNECTION_TYPE]] = $rule["IBLOCK_ELEMENT_PROPERTY_G"];
						}
					}
				}
			}
		}
		
		if(isset($params["XML_PARSE_PARAMS_TO_PROPERTIES"]) && $params["XML_PARSE_PARAMS_TO_PROPERTIES"] == "Y" && is_array($item["param_array"]) && count($item["param_array"])>0)
		{
			$presetProperties = CWebprostorImportProperty::PresetPropertiesByParam($item["param_array"], $params, $IBLOCK_ID);
			
			if(is_array($presetProperties) && count($presetProperties)>0)
			{
				$elementProperties = array_replace_recursive($elementProperties, $presetProperties);
			}
		}
		
		unset($item);
		
		$result = Array(
			"PROPERTIES" => $elementProperties,
			"SEARCH_PROPERTY" => $elementSearchProperty,
			"SEARCH_PROPERTY_E" => $elementSearchPropertyE,
			"SEARCH_PROPERTY_G" => $elementSearchPropertyG,
			"DELETE_PROPERTY_IMAGES" => $elementPropertyImages,
			"DELETE_PROPERTY_FILES" => $elementPropertyFiles
		);
		
		unset(
			$elementProperties,
			$elementSearchProperty,
			$elementSearchPropertyE,
			$elementSearchPropertyG,
			$elementPropertyImages,
			$elementPropertyFiles
		);
		
		return $result;
	}
	
	private static function ImportProperties($data, &$params, $ELEMENT_ID = false, $IBLOCK_ID = false)
	{
		global $DEBUG_PLAN_ID, $DEBUG_EVENTS, $DEBUG_IMPORT_PROPERTIES;
		
		if($DEBUG_EVENTS && $DEBUG_IMPORT_PROPERTIES)
		{
			$logProperties = new CWebprostorImportLog;
			$logPropertiesData = Array("PLAN_ID" => $DEBUG_PLAN_ID);
		}
		
		$el = new CIBlockElement;
		$arProperties = $data["PROPERTIES"];
		$arPropertyValues = Array();
		$arSearchByProperty = $data["SEARCH_PROPERTY"];
		$arDeleteValueProperty = array_merge($data["DELETE_PROPERTY_IMAGES"], $data["DELETE_PROPERTY_FILES"]);
		
		$arFilter = Array('IBLOCK_ID' => $IBLOCK_ID);
		$arSelect = Array("IBLOCK_ID", "ID");
		
		foreach($arSearchByProperty as $code)
		{
			$arFilter["PROPERTY_".$code] = $arProperties[$code];
			if(!in_array($code, $arSelect))
				$arSelect[] = "PROPERTY_".$code;
		}
		unset($arSearchByProperty);
		
		$findElement = false;
			
		if(!$ELEMENT_ID)
		{
			
			if($DEBUG_EVENTS && $DEBUG_IMPORT_PROPERTIES)
			{
				$logPropertiesData["EVENT"] = "IMPORT_ITEM_FILTER";
				$logPropertiesData["MESSAGE"] = Loc::getMessage("MESSAGE_IMPORT_ITEM_FILTER", Array("#OBJECT#" => "Properties"));
				//$logPropertiesData["DATA"] = base64_encode(serialize(array_merge(array_keys($arFilter), $arFilter)));
				$logPropertiesData["DATA"] = base64_encode(serialize($arFilter));
				$logProperties->Add($logPropertiesData);
			}
			
			$elementRes = $el->GetList(
				["SORT" => "ASC"], 
				$arFilter, 
				false, 
				["nPageSize"=>1], 
				$arSelect
			);
			while($findElement2 = $elementRes->GetNextElement())
			{
				$ID = $findElement2['ID'];
				$findElement = $findElement2;
				$findProperties = $findElement2->GetProperties(false, ['ID' => array_keys($data['PROPERTIES'])]);
			}
		
		}
		else
		{
			$ID = $ELEMENT_ID;
			$elementRes = CIBlockElement::GetByID($ID);
			if($findElement2 = $elementRes->GetNextElement())
			{
				$findElement = $findElement2;
				$findProperties = $findElement2->GetProperties(false, ['ID' => array_keys($data['PROPERTIES'])]);
			}
		}
		
		if($DEBUG_EVENTS && $DEBUG_IMPORT_PROPERTIES)
		{
			if($ID > 0)
			{
				$logPropertiesData["EVENT"] = "IMPORT_ITEM_FILTER_RESULT_COUNT";
				$logPropertiesData["MESSAGE"] = Loc::getMessage("MESSAGE_IMPORT_ITEM_FILTER_RESULT_COUNT", Array("#OBJECT#" => "Properties", "#COUNT#" => count((array)$findProperties)));
				//$logPropertiesData["DATA"] = "";
				$logPropertiesData["DATA"] = base64_encode(serialize(CWebprostorImportProperty::getPropertiesIdAndValue($findProperties)));
				$logProperties->Add($logPropertiesData);
			}
			elseif(!$ID)
			{
				$logPropertiesData["EVENT"] = "IMPORT_ITEM_FILTER_NO_RESULT";
				$logPropertiesData["MESSAGE"] = Loc::getMessage("MESSAGE_IMPORT_ITEM_FILTER_NO_RESULT", Array("#OBJECT#" => "Properties"));
				unset($logPropertiesData["DATA"]);
				$logProperties->Add($logPropertiesData);
			}
		}
		
		if($ID > 0)
		{
			if(count($arDeleteValueProperty)>0)
			{
				foreach($arDeleteValueProperty as $propertyId)
				{
					CWebprostorImportProperty::DeleteOldFilesByProperty($ID, $IBLOCK_ID, $propertyId);
				}
			}
			
			foreach($arProperties as $code => $value)
			{
				CWebprostorImportProperty::LinkPropertyValue($code, $value, $ID, $IBLOCK_ID, $params, $data, $arPropertyValues[$code]);
			}
		
			if($DEBUG_EVENTS && $DEBUG_IMPORT_PROPERTIES)
			{
				$logPropertiesData["EVENT"] = "IMPORT_OBJECT_UPDATE_COUNT";
				$logPropertiesData["MESSAGE"] = Loc::getMessage("MESSAGE_IMPORT_OBJECT_UPDATE_COUNT", Array("#OBJECT#" => "Properties", "#COUNT#" => count($arPropertyValues)));
				//$logPropertiesData["DATA"] = '';
				$logPropertiesData["DATA"] = base64_encode(serialize($arPropertyValues));
				$logProperties->Add($logPropertiesData);
			}
			
			if($params["PROPERTIES_RESET"] != "Y")
			{
				$res = CIBlockElement::SetPropertyValuesEx(
					$ID, 
					$IBLOCK_ID, 
					$arPropertyValues
				);
			}
			else
			{
				$res = CIBlockElement::SetPropertyValues(
					$ID, 
					$IBLOCK_ID, 
					$arPropertyValues
				);
			}
			
			if($res == NULL)
				$event = "UPDATE";
		}
		
		unset($arProperties, $data, $arPropertyValues, $arDeleteValueProperty);
		
		if(isset($res) && $res != NULL)
		{
			$event = "ERROR";
			$error = $el->LAST_ERROR;
		}
		
		unset($el, $res, $elementRes, $logProperties);
		
		$result = Array(
			"ID" => $ID,
			"EVENT" => $event,
			"ERROR" => $error
		);
		
		return $result;
	}
	
	private static function parseDimensions(&$productFields)
	{
		$dimensions = $productFields["DIMENSIONS"];
		$dimensionsArr = explode("/", $productFields["DIMENSIONS"]);
		
		if(is_array($dimensionsArr) && count($dimensionsArr)==3)
		{
			foreach($dimensionsArr as $id => $dimension)
			{
				$dimensionsArr[$id] = str_replace(",", '.', str_replace(" ", '', $dimension)) * 10;
			}
			
			$productFields["LENGTH"] = $dimensionsArr[0];
			$productFields["WIDTH"] = $dimensionsArr[1];
			$productFields["HEIGHT"] = $dimensionsArr[2];
		}
	}
	
	private static function parseWeight(&$productFields)
	{
		$productFields["WEIGHT"] = str_replace(",", '.', str_replace(" ", '', $productFields["WEIGHT_KG"])) * 1000;
	}
	
	private static function parseDimension(&$productFields, $field = false)
	{
		$productFields[$field] = str_replace(",", '.', str_replace(" ", '', $productFields[$field."_SM"])) * 10;
	}
	
	private static function GetProductArray($item, &$rules, &$params, $ELEMENT_ID = false, $checkRule = "CATALOG_PRODUCT_FIELD")
	{
		global $ERROR_MESSAGE;
		
		$productFields = [];
		
		foreach($item as $entity => $field)
		{
			$temp_field = $field;
			if(isset($rules[$entity]))
			{
				foreach($rules[$entity]["RULES"] as $rule)
				{
					if(!empty($rule[$checkRule]))
					{
						CWebprostorImportUtils::CheckAttribute($rule, $temp_field, $field);
						
						CWebprostorImportProcessingSettingsTypes::ApplyProcessingRules($field, $rule["PROCESSING_TYPES"]);
						
						$CheckArrayContinue = CWebprostorImportUtils::CheckArrayContinue($rule, $field);
						if($CheckArrayContinue === true)
							continue;
						
						$CheckRequired = CWebprostorImportUtils::CheckRequired($rule, $field);
						if($CheckRequired === false)
						{
							$ERROR_MESSAGE = Loc::getMessage('ERROR_REQUIRED_EMPTY_FIELD', ['#ENTITY#' => $entity]);
							return false;
						}
					
						if($rule["IS_REQUIRED"] == "N" || ($rule["IS_REQUIRED"] == "Y" || !empty($field)))
						{
							
							$productFields[$rule[$checkRule]] = $field;
						}
					}
				}
			}
		}
		unset($item, $temp_field);
		
		if($ELEMENT_ID>0)
		{
			$productFields["ID"] = $ELEMENT_ID;
		}

		if($productFields["BARCODE"] != '')
		{
			$productFields["BARCODE"] = intval($productFields["BARCODE"]);
		}

		if($productFields["DIMENSIONS"] != '')
		{
			self::parseDimensions($productFields);
		}
		else
		{
			if($productFields["WIDTH_SM"] != '' && !isset($productFields["WIDTH"]))
			{
				self::parseDimension($productFields, 'WIDTH');
			}
			if($productFields["LENGTH_SM"] != '' && !isset($productFields["LENGTH"]))
			{
				self::parseDimension($productFields, 'LENGTH');
			}
			if($productFields["HEIGHT_SM"] != '' && !isset($productFields["HEIGHT"]))
			{
				self::parseDimension($productFields, 'HEIGHT');
			}
		}

		if($productFields["WEIGHT_KG"] != '' && !isset($productFields["WEIGHT"]))
		{
			self::parseWeight($productFields);
		}
		
		if(!$productFields["PURCHASING_CURRENCY"] && $productFields["PURCHASING_PRICE"])
		{
			$productFields["PURCHASING_CURRENCY"] = $params["PRODUCTS_DEFAULT_CURRENCY"];
		}
		
		if(!$productFields["VAT_ID"] && $params["PRODUCTS_PARAMS"]["PRODUCTS_VAT_ID"])
		{
			$productFields["VAT_ID"] = $params["PRODUCTS_PARAMS"]["PRODUCTS_VAT_ID"];
		}
		
		if(!$productFields["VAT_INCLUDED"] && $params["PRODUCTS_PARAMS"]["PRODUCTS_VAT_INCLUDED"])
		{
			$productFields["VAT_INCLUDED"] = $params["PRODUCTS_PARAMS"]["PRODUCTS_VAT_INCLUDED"];
		}
		
		if(!$productFields["MEASURE"] && $params["PRODUCTS_PARAMS"]["PRODUCTS_MEASURE"])
		{
			$productFields["MEASURE"] = $params["PRODUCTS_PARAMS"]["PRODUCTS_MEASURE"];
		}
		
		if(!$productFields["QUANTITY"] && strlen($params["PRODUCTS_PARAMS"]["PRODUCTS_QUANTITY"]) != 0 && (int)$params["PRODUCTS_PARAMS"]["PRODUCTS_QUANTITY"] >= 0)
		{
			$productFields["QUANTITY"] = intVal($params["PRODUCTS_PARAMS"]["PRODUCTS_QUANTITY"]);
		}
		
		if(!$productFields["QUANTITY_TRACE"] && $params["PRODUCTS_PARAMS"]["PRODUCTS_QUANTITY_TRACE"])
		{
			$productFields["QUANTITY_TRACE"] = $params["PRODUCTS_PARAMS"]["PRODUCTS_QUANTITY_TRACE"];
		}
		
		if(!$productFields["CAN_BUY_ZERO"] && $params["PRODUCTS_PARAMS"]["PRODUCTS_USE_STORE"])
		{
			$productFields["CAN_BUY_ZERO"] = $params["PRODUCTS_PARAMS"]["PRODUCTS_USE_STORE"];
		}
		
		if(!$productFields["SUBSCRIBE"] && $params["PRODUCTS_PARAMS"]["PRODUCTS_SUBSCRIBE"])
		{
			$productFields["SUBSCRIBE"] = $params["PRODUCTS_PARAMS"]["PRODUCTS_SUBSCRIBE"];
		}
		
		$result = Array(
			"FIELDS" => $productFields
		);
		unset($productFields);
		
		return $result;
	}
	
	private static function ImportProduct($data, &$params, $type = "PRODUCTS")
	{
		
		global $DEBUG_PLAN_ID, $DEBUG_EVENTS, $DEBUG_IMPORT_PRODUCTS, $APPLICATION;
		
		if($DEBUG_EVENTS && $DEBUG_IMPORT_PRODUCTS)
		{
			$logProducts = new CWebprostorImportLog;
			$logProductsData = Array("PLAN_ID" => $DEBUG_PLAN_ID);
		}
		
		$arFields = $data["FIELDS"];
		unset($data);
		
		$arProduct = \Bitrix\Catalog\Model\Product::getList([
			'select' => ['ID'],
			"filter" => ["ID" => $arFields["ID"]]
		])->fetch();
		
		if($arFields["BARCODE"])
		{
			$arBarCodeFields = 
			[
				"BARCODE" => $arFields["BARCODE"],
				"PRODUCT_ID" => $arFields["ID"],
				"STORE_ID" => 0,
			];
		}
		$arMeasureRatioFields = false;
		if($arFields["MEASURE_RATIO"])
		{
			$arMeasureRatioFields = 
			[
				"RATIO" => $arFields["MEASURE_RATIO"],
				"PRODUCT_ID" => $arFields["ID"],
			];
		}
		
		/*Check this code*/
		if(!$arFields["PURCHASING_CURRENCY"] && isset($arFields["PURCHASING_PRICE"]))
		{
			$arFields["PURCHASING_CURRENCY"] = $params["PRODUCTS_DEFAULT_CURRENCY"];
		}

		if ($arProduct)
		{
			$event = "SEARCH";
			
			if($DEBUG_EVENTS && $DEBUG_IMPORT_PRODUCTS)
			{
				$logProductsData["EVENT"] = "IMPORT_PRODUCTS_FILTER_RESULT";
				$logProductsData["MESSAGE"] = Loc::getMessage("MESSAGE_IMPORT_ITEM_FILTER_RESULT", Array("#OBJECT#" => "Product"));
				//$logProductsData["DATA"] = base64_encode(serialize(array_merge(array_keys($arFields), $arFields)));
				$logProductsData["DATA"] = base64_encode(serialize($arFields));
				$logProducts->Add($logProductsData);
			}
			
			if ($params[$type."_UPDATE"] == "Y")
			{
				$res = \Bitrix\Catalog\Model\Product::update($arProduct["ID"], $arFields);
				
				if(isset($res) && $res->isSuccess())
				{
					$event = "UPDATE";
					$ID = $res->getId();
					
					if(is_array($arBarCodeFields))
					{
						$dbBarCode = CCatalogStoreBarCode::GetList([], ["PRODUCT_ID" => $arBarCodeFields["PRODUCT_ID"]], false, false, ["ID"]);
						$arBarCode = $dbBarCode->Fetch();
						
						if($arBarCode["ID"]>0)
							CCatalogStoreBarCode::Update($arBarCode["ID"], $arBarCodeFields);
						else
							CCatalogStoreBarCode::Add($arBarCodeFields);
					}
					
					if(is_array($arMeasureRatioFields))
					{
						$dbMeasureRatio = CCatalogMeasureRatio::getList([], ["PRODUCT_ID" => $arMeasureRatioFields["PRODUCT_ID"]], false, false, ["ID"]);
						$arMeasureRatio = $dbMeasureRatio->Fetch();

						if($arMeasureRatio["ID"]>0)
							CCatalogMeasureRatio::update($arMeasureRatio["ID"], $arMeasureRatioFields);
						else
							CCatalogMeasureRatio::add($arMeasureRatioFields);
					}
				}
			}
		}
		else
		{
			if($params[$type."_ADD"] == "Y")
			{
				$res = \Bitrix\Catalog\Model\Product::add($arFields);
				if(isset($res) && $res->isSuccess())
				{
					$event = "ADD";
					$ID = $res->getId();
					
					if(is_array($arBarCodeFields))
						CCatalogStoreBarCode::Add($arBarCodeFields);
					
					if(is_array($arMeasureRatioFields))
						CCatalogMeasureRatio::add($arMeasureRatioFields);
					
					if($DEBUG_EVENTS && $DEBUG_IMPORT_PRODUCTS)
					{
						$logProductsData["EVENT"] = "IMPORT_PRODUCTS_FILTER_NO_RESULT";
						$logProductsData["MESSAGE"] = Loc::getMessage("MESSAGE_IMPORT_ITEM_FILTER_NO_RESULT", Array("#OBJECT#" => "Product"));
						//$logProductsData["DATA"] = base64_encode(serialize(array_merge(array_keys($arFields), $arFields)));
						$logProductsData["DATA"] = base64_encode(serialize($arFields));
						$logProducts->Add($logProductsData);
					}
				}
			}
		}
		unset($arFields, $logProducts);
		
		if(isset($res) && !$res->isSuccess())
		{
			$event = "ERROR";
			$error = implode('<br />', $res->getErrorMessages());
		}
		
		$result = Array(
			"ID" => $ID,
			"EVENT" => $event,
			"ERROR" => $error
		);
		
		return $result;
	}
	
	private static function GetPriceArray($item, &$rules, &$params, $ELEMENT_ID = false, $BASE_PRICE = false)
	{
		global $ERROR_MESSAGE;
		
		$priceFields = [];
		
		foreach($item as $entity => $field)
		{
			$temp_field = $field;
			if(isset($rules[$entity]))
			{
				foreach($rules[$entity]["RULES"] as $rule)
				{
					if(!empty($rule["CATALOG_PRODUCT_PRICE"]))
					{
						CWebprostorImportUtils::CheckAttribute($rule, $temp_field, $field);
						
						CWebprostorImportProcessingSettingsTypes::ApplyProcessingRules($field, $rule["PROCESSING_TYPES"]);
						
						$CheckArrayContinue = CWebprostorImportUtils::CheckArrayContinue($rule, $field);
						if($CheckArrayContinue === true)
							continue;
						
						$CheckRequired = CWebprostorImportUtils::CheckRequired($rule, $field);
						
						if($CheckRequired === false)
						{
							$ERROR_MESSAGE = Loc::getMessage('ERROR_REQUIRED_EMPTY_FIELD', ['#ENTITY#' => $entity]);
							return false;
						}
					
						if($rule["IS_REQUIRED"] == "N" || ($rule["IS_REQUIRED"] == "Y" || !empty($field)))
						{
							$priceFields[$rule["CATALOG_PRODUCT_PRICE"]] = $field;
						}
					}
				}
			}
		}
		unset($item);
		
		if(isset($priceFields["PRICE"]))
		{
			$prices[$BASE_PRICE]["PRICE"] = CWebprostorImportUtils::ClearPrice($priceFields["PRICE"], true);
			if($params["PRICES_EXTRA_VALUE"] != 0)
			{
				$prices[$BASE_PRICE]["PRICE"] = $prices[$BASE_PRICE]["PRICE"] + ($prices[$BASE_PRICE]["PRICE"]/100 * $params["PRICES_EXTRA_VALUE"]);
			}
			
			unset($priceFields["PRICE"]);
		}
		
		if(isset($priceFields["CURRENCY"]))
		{
			if($priceFields["CURRENCY"] == 'RUR')
				$priceFields["CURRENCY"] = 'RUB';
			
			$prices[$BASE_PRICE]["CURRENCY"] = $priceFields["CURRENCY"];
			unset($priceFields["CURRENCY"]);
		}
		elseif(!$priceFields["CURRENCY"] && isset($prices[$BASE_PRICE]["PRICE"]))
		{
			$prices[$BASE_PRICE]["CURRENCY"] = $params["PRICES_DEFAULT_CURRENCY"];
		}
		
		if(isset($priceFields["QUANTITY_FROM"]))
		{
			$prices[$BASE_PRICE]["QUANTITY_FROM"] = $priceFields["QUANTITY_FROM"];
			unset($priceFields["QUANTITY_FROM"]);
		}
		
		if(isset($priceFields["QUANTITY_TO"]))
		{
			$prices[$BASE_PRICE]["QUANTITY_TO"] = $priceFields["QUANTITY_TO"];
			unset($priceFields["QUANTITY_TO"]);
		}
		
		$dbPriceType = CCatalogGroup::GetList(
			["SORT" => "ASC"],
			["BASE" => "N"],
			false,
			false,
			["ID"]
		);
		while ($arPriceType = $dbPriceType->Fetch())
		{
			$PRICE_ID = $arPriceType["ID"];
			
			if(isset($priceFields["PRICE_".$PRICE_ID]) || isset($priceFields["EXTRA_ID_".$PRICE_ID]))
			{
				if(isset($priceFields["PRICE_".$PRICE_ID]))
				{
					$prices[$PRICE_ID]["PRICE"] = CWebprostorImportUtils::ClearPrice($priceFields["PRICE_".$PRICE_ID], true);
		
					unset($priceFields["PRICE_".$PRICE_ID]);
				
					if(isset($priceFields["CURRENCY_".$PRICE_ID]))
					{
						if($priceFields["CURRENCY_".$PRICE_ID] == 'RUR')
							$priceFields["CURRENCY_".$PRICE_ID] = 'RUB';
						$prices[$PRICE_ID]["CURRENCY"] = $priceFields["CURRENCY_".$PRICE_ID];
						unset($priceFields["CURRENCY_".$PRICE_ID]);
					}
					elseif(!$priceFields["CURRENCY_".$PRICE_ID])
					{
						$prices[$PRICE_ID]["CURRENCY"] = $params["PRICES_DEFAULT_CURRENCY"];
					}
				}
				
				if(isset($priceFields["EXTRA_ID_".$PRICE_ID]))
				{
					$prices[$PRICE_ID]["EXTRA_ID"] = $priceFields["EXTRA_ID_".$PRICE_ID];
					unset($priceFields["EXTRA_ID_".$PRICE_ID]);
				}
				elseif($params["PRICES_EXTRA_VALUE"] != 0)
				{
					$prices[$PRICE_ID]["PRICE"] = $prices[$PRICE_ID]["PRICE"] + ($prices[$PRICE_ID]["PRICE"]/100 * $params["PRICES_EXTRA_VALUE"]);
				}
		
				if(isset($priceFields["QUANTITY_FROM_".$PRICE_ID]))
				{
					$prices[$PRICE_ID]["QUANTITY_FROM"] = $priceFields["QUANTITY_FROM_".$PRICE_ID];
					unset($priceFields["QUANTITY_FROM_".$PRICE_ID]);
				}
				
				if(isset($priceFields["QUANTITY_TO_".$PRICE_ID]))
				{
					$prices[$PRICE_ID]["QUANTITY_TO"] = $priceFields["QUANTITY_TO_".$PRICE_ID];
					unset($priceFields["QUANTITY_TO_".$PRICE_ID]);
				}
			}
		}
		
		if($ELEMENT_ID>0)
		{
			$priceFields["PRODUCT_ID"] = $ELEMENT_ID;
		}
		
		$result = Array(
			"FIELDS" => $priceFields
		);
		unset($priceFields);
		
		if(is_array($prices) && count($prices)>0)
		{
			$result["PRICES"] = $prices;
		}
		unset($prices);
		
		return $result;
	}
	
	private static function ImportPrice($data, $params)
	{
		global $DEBUG_PLAN_ID, $DEBUG_EVENTS, $DEBUG_IMPORT_PRICES, $APPLICATION;
		
		if($DEBUG_EVENTS && $DEBUG_IMPORT_PRICES)
		{
			$logPrices = new CWebprostorImportLog;
			$logPricesData = Array("PLAN_ID" => $DEBUG_PLAN_ID);
		}
		
		$arFields = $data["FIELDS"];
		$arPrices = $data["PRICES"];
		
		if(is_array($arPrices) && count($arPrices)>0)
		{
			//$pr->DeleteByProduct($arFields["PRODUCT_ID"]);
			foreach($arPrices as $PRICE_ID => $PRICE_DATA)
			{
				$PRICE_DATA["PRODUCT_ID"] = $arFields["PRODUCT_ID"];
				$priceFilter = [
					"PRODUCT_ID" => $arFields["PRODUCT_ID"],
					"CATALOG_GROUP_ID" => $PRICE_ID
				];
				if(!empty($PRICE_DATA['QUANTITY_FROM']))
					$priceFilter['QUANTITY_FROM'] = $PRICE_DATA['QUANTITY_FROM'];
				if(!empty($PRICE_DATA['QUANTITY_TO']))
					$priceFilter['QUANTITY_TO'] = $PRICE_DATA['QUANTITY_TO'];
				
				$arPrice = \Bitrix\Catalog\Model\Price::getList([
					'select' => ['ID'],
					'filter' => $priceFilter,
				])->fetch();

				if ($arPrice)
				{
					$event = "SEARCH";
					if ($params["PRICES_UPDATE"] == "Y")
					{
						if($DEBUG_EVENTS && $DEBUG_IMPORT_PRICES)
						{
							$logPricesData["EVENT"] = "IMPORT_PRICES_FILTER_RESULT";
							$logPricesData["MESSAGE"] = Loc::getMessage("MESSAGE_IMPORT_ITEM_FILTER_RESULT", Array("#OBJECT#" => "Price"));
							//$logPricesData["DATA"] = base64_encode(serialize(array_merge(array_keys($PRICE_DATA), $PRICE_DATA)));
							$logPricesData["DATA"] = base64_encode(serialize($PRICE_DATA));
							$logPrices->Add($logPricesData);
						}
						
						$res = \Bitrix\Catalog\Model\Price::update($arPrice["ID"], $PRICE_DATA);
						
						if(isset($res) && $res->isSuccess())
						{
							$event = "UPDATE";
							$ID = $res->getId();
						}
					}
				}
				else
				{
					if($params["PRICES_ADD"] == "Y")
					{
						$PRICE_DATA["CATALOG_GROUP_ID"] = $PRICE_ID;
			
						if($DEBUG_EVENTS && $DEBUG_IMPORT_PRICES)
						{
							$logPricesData["EVENT"] = "IMPORT_PRICES_FILTER_NO_RESULT";
							$logPricesData["MESSAGE"] = Loc::getMessage("MESSAGE_IMPORT_ITEM_FILTER_NO_RESULT", Array("#OBJECT#" => "Price"));
							//$logPricesData["DATA"] = base64_encode(serialize(array_merge(array_keys($PRICE_DATA), $PRICE_DATA)));
							$logPricesData["DATA"] = base64_encode(serialize($PRICE_DATA));
							$logPrices->Add($logPricesData);
						}
						$res = \Bitrix\Catalog\Model\Price::add($PRICE_DATA);
						if(isset($res) && $res->isSuccess())
						{
							$event = "ADD";
							$ID = $res->getId();
						}
					}
					else
					{
						$event = "SKIP";
					}
				}
				
				if(isset($res) && !$res->isSuccess())
				{
					$event = "ERROR";
					$error = implode('<br />', $res->getErrorMessages());
				}
				
				$result["SYSTEM"]["ID"][] = $ID;
				++$result["SYSTEM"]["EVENT"][$event];
				$result["SYSTEM"]["ERROR"][] = $error;
				
				$result["LOGS"][] = [
					"ID" => $ID,
					"EVENT" => $event,
					"ERROR" => $error,
				];
				
			}
		}
		
		unset($logPrices);
		
		return $result;
	}
	
	private static function GetStoreAmountArray($item, &$rules, &$params, $ELEMENT_ID = false)
	{
		global $ERROR_MESSAGE;
		
		$storeAmountFields = [];
		
		foreach($item as $entity => $field)
		{
			$temp_field = $field;
			if(isset($rules[$entity]))
			{
				foreach($rules[$entity]["RULES"] as $rule)
				{
					if(!empty($rule["CATALOG_PRODUCT_STORE_AMOUNT"]))
					{
						CWebprostorImportUtils::CheckAttribute($rule, $temp_field, $field);
						
						CWebprostorImportProcessingSettingsTypes::ApplyProcessingRules($field, $rule["PROCESSING_TYPES"]);
						
						$CheckArrayContinue = CWebprostorImportUtils::CheckArrayContinue($rule, $field);
						if($CheckArrayContinue === true)
							continue;
						
						$CheckRequired = CWebprostorImportUtils::CheckRequired($rule, $field);
						
						if($CheckRequired === false)
						{
							$ERROR_MESSAGE = Loc::getMessage('ERROR_REQUIRED_EMPTY_FIELD', ['#ENTITY#' => $entity]);
							return false;
						}
					
						if($rule["IS_REQUIRED"] == "N" || ($rule["IS_REQUIRED"] == "Y" || !empty($field)))
						{
							
							$storeAmountFields[$rule["CATALOG_PRODUCT_STORE_AMOUNT"]] = $field;
						}
					}
				}
			}
		}
		
		$dbStore = CCatalogStore::GetList(
			["SORT" => "ASC"],
			[],
			false,
			false, 
			["ID"]
		);
		
		while ($arStore = $dbStore->Fetch())
		{
			$STORE_ID = $arStore["ID"];
			if(isset($storeAmountFields["STORE_".$STORE_ID]))
			{
				$stores[$STORE_ID]["AMOUNT"] = CWebprostorImportUtils::ClearPrice($storeAmountFields["STORE_".$STORE_ID]);
				unset($storeAmountFields["STORE_".$STORE_ID]);
			}
		}
		
		if($ELEMENT_ID>0)
		{
			$storeAmountFields["PRODUCT_ID"] = $ELEMENT_ID;
		}
		
		$result = Array(
			"FIELDS" => $storeAmountFields
		);
		unset($storeAmountFields);
		
		if(is_array($stores) && count($stores)>0)
		{
			$result["STORES"] = $stores;
		}
		unset($stores);
		
		return $result;
	}
	
	private static function ImportStoreAmount($data, $params)
	{
		global $DEBUG_PLAN_ID, $DEBUG_EVENTS, $DEBUG_IMPORT_STORE_AMOUNT;
		
		if($DEBUG_EVENTS && $DEBUG_IMPORT_STORE_AMOUNT)
		{
			$logStores = new CWebprostorImportLog;
			$logStoresData = Array("PLAN_ID" => $DEBUG_PLAN_ID);
		}
		
		$arFields = $data["FIELDS"];
		$arStores = $data["STORES"];
		unset($data);
		
		if(is_array($arStores) && count($arStores)>0)
		{
			foreach($arStores as $STORE_ID => $STORE_DATA)
			{
				$STORE_DATA["PRODUCT_ID"] = $arFields["PRODUCT_ID"];
				$arStoreAmount = \Bitrix\Catalog\StoreProductTable::getList([
					'select' => ['ID'],
					"filter" => [
						"PRODUCT_ID" => $arFields["PRODUCT_ID"],
						"STORE_ID" => $STORE_ID
					]
				])->fetch();

				if ($arStoreAmount)
				{
					$event = "SEARCH";
					if ($params["STORE_AMOUNT_UPDATE"] == "Y")
					{
						if($DEBUG_EVENTS && $DEBUG_IMPORT_STORE_AMOUNT)
						{
							$logStoresData["EVENT"] = "IMPORT_STORE_AMOUNT_FILTER_RESULT";
							$logStoresData["MESSAGE"] = Loc::getMessage("MESSAGE_IMPORT_ITEM_FILTER_RESULT", Array("#OBJECT#" => "StoreAmount"));
							//$logStoresData["DATA"] = base64_encode(serialize(array_merge(array_keys($STORE_DATA), $STORE_DATA)));
							$logStoresData["DATA"] = base64_encode(serialize($STORE_DATA));
							$logStores->Add($logStoresData);
						}
						
						$res = \Bitrix\Catalog\StoreProductTable::update($arStoreAmount["ID"], $STORE_DATA);
						
						if(isset($res) && $res->isSuccess())
						{
							$event = "UPDATE";
							$ID = $res->getId();
						}
					}
				}
				else
				{
					if($params["STORE_AMOUNT_ADD"] == "Y")
					{
						if($DEBUG_EVENTS && $DEBUG_IMPORT_STORE_AMOUNT)
						{
							$logStoresData["EVENT"] = "IMPORT_STORE_AMOUNT_FILTER_NO_RESULT";
							$logStoresData["MESSAGE"] = Loc::getMessage("MESSAGE_IMPORT_ITEM_FILTER_NO_RESULT", Array("#OBJECT#" => "StoreAmount"));
							//$logStoresData["DATA"] = base64_encode(serialize(array_merge(array_keys($STORE_DATA), $STORE_DATA)));
							$logPricesData["DATA"] = base64_encode(serialize($STORE_DATA));
							$logStores->Add($logStoresData);
						}
						$STORE_DATA["STORE_ID"] = $STORE_ID;
						
						$res = \Bitrix\Catalog\StoreProductTable::add($STORE_DATA);
						
						if(isset($res) && $res->isSuccess())
						{
							$event = "ADD";
							$ID = $res->getId();
						}
					}
					else
					{
						$event = "SKIP";
					}
				}
				
				if(isset($res) && !$res->isSuccess())
				{
					$event = "ERROR";
					$error = implode('<br />', $res->getErrorMessages());
				}
				
				$result["SYSTEM"]["ID"][] = $ID;
				++$result["SYSTEM"]["EVENT"][$event];
				$result["SYSTEM"]["ERROR"][] = $error;
				
				$result["LOGS"][] = [
					"ID" => $ID,
					"EVENT" => $event,
					"ERROR" => $error,
				];
				
			}
		}
		
		unset($logStores);
		
		return $result;
	}
	
	private static function GetEntityArray($item, &$rules, &$params) : array
	{
		$entityFields = Array();
		$entitySearch = Array();
		
		global $DEBUG_IMAGES, $DEBUG_FILES, $ERROR_MESSAGE;
		
		foreach($item as $entity => $field)
		{
			$temp_field = $field;
			if(isset($rules[$entity]))
			{
				foreach($rules[$entity]["RULES"] as $rule)
				{
					if(!empty($rule["HIGHLOAD_BLOCK_ENTITY_FIELD"]))
					{
						CWebprostorImportUtils::CheckAttribute($rule, $temp_field, $field);
						
						CWebprostorImportProcessingSettingsTypes::ApplyProcessingRules($field, $rule["PROCESSING_TYPES"]);
						
						$CheckArrayContinue = CWebprostorImportUtils::CheckArrayContinue($rule, $field);
						if($CheckArrayContinue === true)
							continue;
						
						$CheckRequired = CWebprostorImportUtils::CheckRequired($rule, $field);
						
						if($CheckRequired === false)
						{
							$ERROR_MESSAGE = Loc::getMessage('ERROR_REQUIRED_EMPTY_FIELD', ['#ENTITY#' => $entity]);
							return false;
						}
					
						if($rule["IS_REQUIRED"] == "N" || ($rule["IS_REQUIRED"] == "Y" || !empty($field)))
						{
							
							$CheckImageFileUrl = self::CheckImageFileUrl($rule, $field, $params, $entityFields[$rule["HIGHLOAD_BLOCK_ENTITY_FIELD"]]);
							
							if($CheckImageFileUrl === false)
							{
								$entityFields[$rule["HIGHLOAD_BLOCK_ENTITY_FIELD"]] = CWebprostorImportUtils::ClearField($field);
							}
							
							if($rule["USE_IN_SEARCH"] == "Y")
								$entitySearch[] = $rule["HIGHLOAD_BLOCK_ENTITY_FIELD"];
							
							if($rule["USE_IN_CODE"] == "ENTITY")
							{
								$entityCode[$field] = $rule["SORT"];
							}
						}
					}
				}
			}
		}
		unset($temp_field);
		
		if(is_array($entityCode) && count($entityCode))
		{
			uasort($entityCode, 'CWebprostorImportUtils::CompareCodeSort');
			foreach($entityCode as $value => $sort)
			{
				$translitName .= ' '.$value;
			}
			$translitName = trim($translitName);
		}
		elseif(empty($entityFields["UF_XML_ID"]) && !empty($entityFields["UF_NAME"]) && $params["ENTITIES_TRANSLATE_XML_ID"] != "N")
		{
			$translitName = $entityFields["UF_NAME"];
		}
		
		if($translitName)
		{
			$xml_id = CWebprostorImportUtils::TranslitValue($translitName, "_", "_");
			
			unset($translitName);
			if(!is_numeric($xml_id))
				$entityFields["UF_XML_ID"] = $xml_id;
			else
				$entityFields["UF_XML_ID"] = $params["HIGHLOAD_BLOCK"].'_'.$xml_id;
		}
		
		$result = Array(
			"FIELDS" => $entityFields,
			"SEARCH" => $entitySearch
		);
		unset($entityFields, $entitySearch);
		
		return $result;
	}
	
	private static function ImportEntity($data, &$params)
	{
		global $DEBUG_PLAN_ID, $DEBUG_EVENTS, $DEBUG_IMPORT_ENTITIES;
		
		if($DEBUG_EVENTS && $DEBUG_IMPORT_ENTITIES)
		{
			$logEntity = new CWebprostorImportLog;
			$logEntityData = Array("PLAN_ID" => $DEBUG_PLAN_ID);
		}
		
		$arFields = $data["FIELDS"];
		$arSearchBy = $data["SEARCH"];
		unset($data);
		
		$arFilter = Array();
		$arSelect = Array("ID");
		
		foreach($arSearchBy as $code)
		{
			$arFilter[$code] = $arFields[$code];
			if(!in_array($code, $arSelect))
				$arSelect[] = $code;
		}
		
		if(is_array($arFilter) && count($arFilter)>0)
		{
			if($DEBUG_EVENTS && $DEBUG_IMPORT_ENTITIES)
			{
				$logEntityData["EVENT"] = "IMPORT_ITEM_FILTER";
				$logEntityData["MESSAGE"] = Loc::getMessage("MESSAGE_IMPORT_ITEM_FILTER", Array("#OBJECT#" => "Entity"));
				//$logEntityData["DATA"] = base64_encode(serialize(array_merge(array_keys($arFilter), $arFilter)));
				$logEntityData["DATA"] = base64_encode(serialize($arFilter));
				$logEntity->Add($logEntityData);
			}
			
			$rsData = \Bitrix\Highloadblock\HighloadBlockTable::getList(
				[
					'filter' => [
						'ID' => $params["HIGHLOAD_BLOCK"]
					]
				]
			);
			
			if($hldata = $rsData->Fetch())
			{
				$hlentity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hldata);
				$hlDataClass = $hlentity->getDataClass(); 
				$res = $hlDataClass::getList(
					array(
						'filter' => $arFilter, 
						'select' => $arSelect, 
						'order' => array(),
					)
				);
					
				$findEntity = false;
				
				if ($row = $res->fetch()) {
					$findEntity = $row;
					$ID = $row["ID"];
					$event = "SEARCH";
				} 
			
				if($DEBUG_EVENTS && $DEBUG_IMPORT_ENTITIES)
				{
					if($ID > 0)
					{
						$logEntityData["EVENT"] = "IMPORT_ITEM_FILTER_RESULT";
						$logEntityData["MESSAGE"] = Loc::getMessage("MESSAGE_IMPORT_ITEM_FILTER_RESULT", Array("#OBJECT#" => "Entity"));
						//$logEntityData["DATA"] = base64_encode(serialize(array_merge(array_keys($findEntity), $findEntity)));
						$logEntityData["DATA"] = base64_encode(serialize($findEntity));
						$logEntity->Add($logEntityData);
					}
					elseif(!$ID)
					{
						$logEntityData["EVENT"] = "IMPORT_ITEM_FILTER_NO_RESULT";
						$logEntityData["MESSAGE"] = Loc::getMessage("MESSAGE_IMPORT_ITEM_FILTER_NO_RESULT", Array("#OBJECT#" => "Entity"));
						//$logEntityData["DATA"] = base64_encode(serialize(array_merge(array_keys($arFields), $arFields)));
						$logEntityData["DATA"] = base64_encode(serialize($arFields));
						$logEntity->Add($logEntityData);
					}
				}
				
				self::LinkUserFieldValue("HLBLOCK_".$params["HIGHLOAD_BLOCK"], $arFields, $params);
				
				if($ID > 0)
				{
					if($params["ENTITIES_UPDATE"] == "Y")
					{
						self::CheckObjectValuesToUrl($arFields, $params);
							
						$res = $hlDataClass::Update($ID, $arFields);
						if($res->isSuccess())
							$event = "UPDATE";
					}
				}
				else
				{
					if($params["ENTITIES_ADD"] == "Y")
					{
						self::CheckObjectValuesToUrl($arFields, $params);
						
						$res = $hlDataClass::Add($arFields);
						if($res->isSuccess())
						{
							$ID = $res->getId();
							$event = "ADD";
						}
					}
				}
				
				if(($params["ENTITIES_ADD"] == "Y" || $params["ENTITIES_UPDATE"] == "Y") && $event != "SEARCH" && !$res->isSuccess())
				{
					$event = "ERROR";
					$error = $res->getErrorMessages();
					
					if($DEBUG_EVENTS && $DEBUG_IMPORT_ENTITIES)
					{
						$logEntityData["EVENT"] = "ADD_UPDATE_ITEM";
						$logEntityData["MESSAGE"] = Loc::getMessage("ERROR_CANNOT_ADD_UPDATE_ITEM");
						//$logEntityData["DATA"] = base64_encode(serialize(array_merge(array_keys($error), $error)));
						$logEntityData["DATA"] = base64_encode(serialize($error));
						
						$logEntity->Add($logEntityData);
					}
				}
			}
		}
		else
		{
			if($DEBUG_EVENTS && $DEBUG_IMPORT_ENTITIES)
			{
				$logEntityData["EVENT"] = "IMPORT_ITEM_FILTER_NO_SEARCH";
				$logEntityData["MESSAGE"] = Loc::getMessage("MESSAGE_IMPORT_ITEM_FILTER_NO_SEARCH", Array("#OBJECT#" => "Entity"));
				$logEntity->Add($logEntityData);
			}
		}
		
		if(!is_null($ID) || !is_null($event) || !is_null($error))
		{
			$result = Array(
				"ID" => $ID,
				"EVENT" => $event,
				"ERROR" => $error,
			);
		}
		
		unset($logEntity);
		
		return $result;
	}
	
	private static function LinkUserFieldValue($object = false, &$arFields, &$params)
	{
		if(is_array($arFields) && count($arFields)>0)
		{
			foreach($arFields as $code => $value)
			{
				$rsData = CUserTypeEntity::GetList(
					[
						"SORT" => "ASC"
					], 
					[
						"ENTITY_ID" => $object, 
						"FIELD_NAME" => $code, 
						"LANG" => LANGUAGE_ID
					]
				);
				
				while($arRes = $rsData->Fetch())
				{
					switch($arRes["USER_TYPE_ID"])
					{
						case("enumeration"):
        
							$arFields[$code] = self::GetUserFieldEnumByValue($arRes["ID"], $value, $params);
							break;
						default:
							break;
					}
				}
			}
		}
	}
	
	private static function GetUserFieldEnumByValue(int $userFieldID = NULL, $value, &$params) : string
	{
		$result = '';
		
		if(!empty($value) && $userFieldID)
		{
			$ID = false;
			$xml_id = false;
			
			$userFieldEnumRes = CUserFieldEnum::GetList(
				[
					"SORT" => "ASC"
				], 
				[
					"USER_FIELD_ID" => $userFieldID,
					"VALUE" => $value,
				]
			);
			
			if($userFieldEnumArr = $userFieldEnumRes->GetNext())
			{
				$ID = $userFieldEnumArr["ID"];
			}
			
			if($params["ENTITIES_TRANSLATE_XML_ID"] == "Y")
			{
				$xml_id = CWebprostorImportUtils::TranslitValue($value, "_", "_");
			}
				
			if(!$ID && $xml_id)
			{
			
				$userFieldEnumRes = CUserFieldEnum::GetList(
					[
						"SORT" => "ASC"
					], 
					[
						"USER_FIELD_ID" => $userFieldID,
						"XML_ID" => $xml_id,
					]
				);
				
				if($userFieldEnumArr = $userFieldEnumRes->GetNext())
				{
					$ID = $userFieldEnumArr["ID"];
				}
			}
			
			if(!$ID && $params["ENTITIES_ADD_LIST_ENUM"] == "Y")
			{
				
				$obEnum = new CUserFieldEnum;
				$obEnumParams = [
					"VALUE" => $value,
					"SORT" => intVal($value),
				];
				
				if($xml_id)
				{
					$obEnumParams["XML_ID"] = $xml_id;
				}
				
				$obEnumResult = $obEnum->SetEnumValues(
					$userFieldID, 
					[
						"n0" => $obEnumParams,
					]
				);
				
				if($obEnumResult)
				{
					$userFieldEnumRes = CUserFieldEnum::GetList(
						[
							"SORT" => "ASC"
						], 
						[
							"USER_FIELD_ID" => $userFieldID,
							"VALUE" => $value,
						]
					);
					
					if($userFieldEnumArr = $userFieldEnumRes->GetNext())
					{
						$ID = $userFieldEnumArr["ID"];
					}
				}
			}
			
			if($ID)
			{
				$result = $ID;
			}
		}
		
		unset($obEnum);
		
		return $result;
	}
}
?>