<?
namespace Webprostor\Import\Format;

use Bitrix\Main\Localization\Loc,
	Box\Spout\Reader\Common\Creator\ReaderEntityFactory;

class ODS
{
	CONST MODULE_ID = "webprostor.import";
	
	function __construct()
	{
		require_once dirname(__FILE__).'/../../classes/scripts/spout/Autoloader/autoload.php';
		
		if(!extension_loaded('zip'))
		{
			$errorArray = Array(
				"MESSAGE" => Loc::getMessage("WEBPROSTOR_IMPORT_ERROR_PHP_EXTENSION_ZIP_NOT_INCLUDED"),
				"TAG" => "ZIP_NOT_INCLUDED",
				"MODULE_ID" => "WEBPROSTOR.IMPORT",
				"ENABLE_CLOSE" => "Y"
			);
			$notifyID = CAdminNotify::Add($errorArray);
		}
		
		if(!extension_loaded('xmlreader'))
		{
			$errorArray = Array(
				"MESSAGE" => Loc::getMessage("WEBPROSTOR_IMPORT_ERROR_PHP_EXTENSION_XMLREADER_NOT_INCLUDED"),
				"TAG" => "XMLREADER_NOT_INCLUDED",
				"MODULE_ID" => "WEBPROSTOR.IMPORT",
				"ENABLE_CLOSE" => "Y"
			);
			$notifyID = CAdminNotify::Add($errorArray);
		}
	}
	
	private function convArray($array, $from_char, $in_char = "UTF-8")
	{
		if(is_array($array) && count($array) > 0)
		{
			foreach ($array as $key => $value)
			{
				if(is_array($value))
					$array[$key] = self::convArray($value, $from_char, $in_char);
				else
					$array[$key] = iconv($from_char, $in_char, $value);
			}
		}
		return $array;
	}
	
	public function GetSheets($file = false, $char = "UTF-8")
	{
		global $PLAN_ID;
		
		if($file)
			$file = $_SERVER["DOCUMENT_ROOT"].$file;
		
		CheckDirPath($_SERVER["DOCUMENT_ROOT"].'/bitrix/tmp/'.self::MODULE_ID.'/');
		$tempFile = $_SERVER["DOCUMENT_ROOT"].'/bitrix/tmp/'.self::MODULE_ID.'/'.$PLAN_ID.'_sheets.txt';
		
		if($tempFile && is_file($file) && is_file($tempFile) && (filemtime($file) > filemtime($tempFile)))
		{
			unlink($tempFile);
		}
		
		if($tempFile && is_file($file) && is_file($tempFile) && (filemtime($file) < filemtime($tempFile)))
		{
			$tempData = file_get_contents($tempFile);
			$data = unserialize($tempData);
		}
		elseif(is_file($file) && $ods = ReaderEntityFactory::createODSReader($file))
		{
			$ods->open($file);

			foreach ($ods->getSheetIterator() as $id => $sheet) {
				$data[$sheet->getIndex()] = $sheet->getName();
			}

			$ods->close();
		
			if(is_array($data))
			{
				if((defined("BX_UTF") && BX_UTF) && $char != "UTF-8")
				{
					$data = self::convArray($data, $char, "UTF-8");
				}
				elseif(((defined("BX_UTF") && !BX_UTF) || !defined("BX_UTF")) && $char != "WINDOWS-1251")
				{
					$data = self::convArray($data, $char, "WINDOWS-1251");
				}
			}
			
			if($tempFile && !is_file($tempFile) && $data)
				file_put_contents($tempFile, serialize($data));
		}
		
		if(is_array($data))
			return $data;
		else
			return false;
	}
	
	public function ParseFile($file, $char = "UTF-8", $sheet = 0, $nameLine = 0, $maxRows = false)
	{
		global $PLAN_ID;
		$result = Array();
		
		if(!$sheet)
			$sheet = 0;
		
		if($nameLine && $maxRows)
			$maxRows += $nameLine;
		
		if($maxRows)
		{
			CheckDirPath($_SERVER["DOCUMENT_ROOT"].'/bitrix/tmp/'.self::MODULE_ID.'/');
			$tempFile = $_SERVER["DOCUMENT_ROOT"].'/bitrix/tmp/'.self::MODULE_ID.'/'.$PLAN_ID.'_data.txt';
		}
		
		if($tempFile && is_file($file) && is_file($tempFile) && (filemtime($file) > filemtime($tempFile)))
		{
			unlink($tempFile);
		}
		
		if($tempFile && is_file($file) && is_file($tempFile) && (filemtime($file) < filemtime($tempFile)))
		{
			$tempData = file_get_contents($tempFile);
			$data = unserialize($tempData);
		}
		elseif(is_file($file) && $ods = ReaderEntityFactory::createODSReader($file))
		{
			$ods->open($file);

			foreach ($ods->getSheetIterator() as $sheetSingle) {
				if($sheetSingle->getIndex() != $sheet)
					continue;
				foreach ($sheetSingle->getRowIterator() as $index => $row) {
					--$index;
					$cells = $row->getCells();
					$rowValues = [];
					foreach ($cells as $cell) {
						$rowValues[] = $cell->getValue();
					}
					$data[] = $rowValues;
					if($maxRows !== false && $index > $maxRows)
						break;
				}
			}

			$ods->close();
		}
		
		if(is_array($data))
		{
			if(!is_file($tempFile))
			{
				if((defined("BX_UTF") && BX_UTF) && $char != "UTF-8")
				{
					$result["DATA"] = self::convArray($data, $char, "UTF-8");
				}
				elseif(((defined("BX_UTF") && !BX_UTF) || !defined("BX_UTF")) && $char != "WINDOWS-1251")
				{
					$result["DATA"] = self::convArray($data, $char, "WINDOWS-1251");
				}
				else
					$result["DATA"] = $data;
			}
			else
			{
				$result["DATA"] = $data;
			}
		}
		
		unset($data);
		
		if(is_array($result["DATA"]))
		{
			$result["ITEMS_COUNT"] = count($result["DATA"]);
			
			if($tempFile && !is_file($tempFile))
				file_put_contents($tempFile, serialize($result["DATA"]));
		}
		
		return $result;
	}
	
	public function GetDataArray($data, $params, $startFrom = 0)
	{
		if($params["CSV_XLS_START_LINE"]>0)
			$data = array_slice($data, $params["CSV_XLS_START_LINE"], count($data));
		
		$dataLineCount = count($data);
		$importFinished = false;
		
		$data = array_slice($data, $startFrom, $params["ITEMS_PER_ROUND"]);
		
		$endTo = $startFrom + $params["ITEMS_PER_ROUND"];
		if($endTo >= $dataLineCount)
		{
			$endTo = 0;
			$importFinished = true;
		}
		
		$result = Array(
			"DATA_ARRAY" => $data,
			"START_FROM" => $endTo,
			"FINISHED" => $importFinished,
		);
		
		return $result;
	}
	
	public function GetEntities($PLAN_ID = false, $IMPORT_FILE = false)
	{
		$CPlan = new \CWebprostorImportPlan;
		$planInfo = $CPlan->GetById($PLAN_ID)->Fetch();
		
		if(!$IMPORT_FILE)
			$IMPORT_FILE = $_SERVER["DOCUMENT_ROOT"].$planInfo["IMPORT_FILE"];
		
		CheckDirPath($_SERVER["DOCUMENT_ROOT"].'/bitrix/tmp/'.self::MODULE_ID.'/');
		$tempFile = $_SERVER["DOCUMENT_ROOT"].'/bitrix/tmp/'.self::MODULE_ID.'/'.$PLAN_ID.'_entities.txt';
		
		if($tempFile && is_file($IMPORT_FILE) && is_file($tempFile) && (filemtime($IMPORT_FILE) > filemtime($tempFile)))
		{
			unlink($tempFile);
		}
		
		if($tempFile && is_file($IMPORT_FILE) && is_file($tempFile) && (filemtime($IMPORT_FILE) < filemtime($tempFile)))
		{
			$tempData = file_get_contents($tempFile);
			$entities = unserialize($tempData);
		}
		else
		{
			$fileInfo = self::ParseFile($IMPORT_FILE, $planInfo["IMPORT_FILE_SHARSET"], $planInfo["XLS_SHEET"], $planInfo["CSV_XLS_NAME_LINE"], 1);
			$entities = $fileInfo["DATA"][$planInfo["CSV_XLS_NAME_LINE"]];
			
			if($tempFile && !is_file($tempFile) && $entities)
				file_put_contents($tempFile, serialize($entities));
		}
		
		return $entities;
		
	}
	
	public function GetSample($PLAN_ID = false, $IMPORT_FILE = false)
	{
		$CPlan = new \CWebprostorImportPlan;
		$planInfo = $CPlan->GetById($PLAN_ID)->Fetch();
		
		if(!$IMPORT_FILE)
			$IMPORT_FILE = $_SERVER["DOCUMENT_ROOT"].$planInfo["IMPORT_FILE"];
		
		CheckDirPath($_SERVER["DOCUMENT_ROOT"].'/bitrix/tmp/'.self::MODULE_ID.'/');
		$tempFile = $_SERVER["DOCUMENT_ROOT"].'/bitrix/tmp/'.self::MODULE_ID.'/'.$PLAN_ID.'_sample.txt';
		
		if($tempFile && is_file($IMPORT_FILE) && is_file($tempFile) && (filemtime($IMPORT_FILE) > filemtime($tempFile)))
		{
			unlink($tempFile);
		}
		
		if($tempFile && is_file($IMPORT_FILE) && is_file($tempFile) && (filemtime($IMPORT_FILE) < filemtime($tempFile)))
		{
			$tempData = file_get_contents($tempFile);
			$entities = unserialize($tempData);
		}
		else
		{
			$fileInfo = self::ParseFile($IMPORT_FILE, $planInfo["IMPORT_FILE_SHARSET"], $planInfo["XLS_SHEET"], $planInfo["CSV_XLS_START_LINE"], 1);
			$entities = $fileInfo["DATA"][$planInfo["CSV_XLS_START_LINE"]];
			
			if($tempFile && !is_file($tempFile) && $entities)
				file_put_contents($tempFile, serialize($entities));
		}
		
		return $entities;
	}
}
?>