<?php
$_SERVER["DOCUMENT_ROOT"] = realpath(dirname(__FILE__)."/../../../..");
$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];

define("NOT_CHECK_PERMISSIONS",true);
define("BX_CAT_CRON", true);
define("STOP_STATISTICS", true);
define("NO_KEEP_STATISTIC", true);
define("NO_AGENT_STATISTIC", true);
define('NO_AGENT_CHECK', true);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

set_time_limit(0);
$module_id = 'webprostor.import';

if (!\Bitrix\Main\Loader::includeModule($module_id))
	die('Can\'t include module');

$importType = false;
$PLAN_ID = 0;

if ($argv[1] == "Import" || $argv[1] == "Load")
	$importType = $argv[1];

if (isset($argv[2]))
	$PLAN_ID = (int)$argv[2];

if ($PLAN_ID <= 0)
	die('No plan id');

if ($importType === false)
	die('Import type not set');

$cData = new CWebprostorImportPlan;

$planRes = $cData->GetByID($PLAN_ID);
$planParams = $planRes->Fetch();
			
if (!$planParams)
	die('No plan params');

if($importType == "Load")
{
	$load = CWebprostorImport::Load($PLAN_ID);
}
elseif($importType == "Import")
{

	$IMPORT_FILE = $_SERVER["DOCUMENT_ROOT"].$planParams["IMPORT_FILE"];

	if($planParams["IMPORT_FORMAT"] != 'JSON' && !is_file($IMPORT_FILE))
		die('No import file');

	$cronParams = [
		"CURRENT" => 0,
		"TOTAL" => 0,
	];

	$GLOBALS["PLAN_ID"] = $PLAN_ID; //it for GetEntities
	
	switch($planParams["IMPORT_FORMAT"])
	{
		case("CSV"):
			$scriptData = new CWebprostorImportCSV;
			$fileData = $scriptData->ParseFile($IMPORT_FILE, $planParams["IMPORT_FILE_SHARSET"], $planParams["CSV_XLS_FINISH_LINE"], $planParams["CSV_DELIMITER"]);
			break;
		case("XML"):
			$scriptData = new CWebprostorImportXML;
			$fileData["ITEMS_COUNT"] = $scriptData->GetTotalCount(
				$IMPORT_FILE, 
				$planParams["XML_ENTITY_GROUP"], 
				$planParams["XML_ENTITY"]
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
					
	$entities = $scriptData->GetEntities($PLAN_ID);

	if($planParams["IMPORT_FILE_CHECK"] != "N" && $entities)
	{
		if($planParams["IMPORT_FORMAT"] == "XML")
		{
			if($planParams["XML_USE_ENTITY_NAME"] != "Y")
				$checkEntitiesResult = CWebprostorImport::CheckEntitiesNames($PLAN_ID, $entities["KEYS"]);
		}
		else
			$checkEntitiesResult = CWebprostorImport::CheckEntitiesNames($PLAN_ID, $entities);
		
		if(isset($checkEntitiesResult) && count($checkEntitiesResult))
			die('Entites and names not identical');
	}
	unset($entities);

	CheckDirPath($_SERVER["DOCUMENT_ROOT"].'/bitrix/tmp/'.$module_id.'/');
	$tempFile = $_SERVER["DOCUMENT_ROOT"].'/bitrix/tmp/'.$module_id.'/'.$PLAN_ID.'_cron.txt';
			
	if($tempFile && is_file($tempFile) && (filemtime($IMPORT_FILE) > filemtime($tempFile)))
	{
		unlink($tempFile);
	}
			
	if($tempFile && is_file($tempFile) && (filemtime($IMPORT_FILE) < filemtime($tempFile)))
	{
		$tempData = file_get_contents($tempFile);
		$cronParams = unserialize($tempData);
		unset($tempData);
	}
	else
	{
		$cronParams["TOTAL"] = $fileData["ITEMS_COUNT"];
		unset($fileData);
		
		if($tempFile && !is_file($tempFile))
			file_put_contents($tempFile, serialize($cronParams));
	}
	unset($planParams);

	$import = CWebprostorImport::Import($PLAN_ID, $cronParams["CURRENT"]);
	
	preg_match('/CWebprostorImport::Import\([0-9]*\,\s(.*)\);/', $import, $matches);
	if($matches[1] != 0)
	{
		$cronParams["CURRENT"] = (int)$matches[1];
		
		file_put_contents($tempFile, serialize($cronParams));
	}
}