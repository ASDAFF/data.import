<?
namespace Webprostor\Import\Format;

class JSON
{
	CONST MODULE_ID = "webprostor.import";
	
	function __construct()
	{
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
	
	/*public function ParseFile($file, $char = "UTF-8", $sheet = 0, $nameLine = 0, $maxRows = false)
	{
		
	}*/
	
	public function GetData(&$planParams, $replace_next_url = true)
	{
		$result = [];
		
		if($replace_next_url)
		{
			$json_next_url_check = filter_var($planParams["JSON_NEXT_URL"], FILTER_VALIDATE_URL);
			if($json_next_url_check !== false)
				$planParams["IMPORT_FILE_URL"] = $json_next_url_check;
		}
		
		\CWebprostorImportUtils::replaceMacroses($planParams["IMPORT_FILE_URL"], $planParams);
		
		CheckDirPath($_SERVER["DOCUMENT_ROOT"].'/bitrix/tmp/'.self::MODULE_ID.'/');
		$tempFile = $_SERVER["DOCUMENT_ROOT"].'/bitrix/tmp/'.self::MODULE_ID.'/'.$planParams['ID'].'_data.txt';
		
		if($tempFile && is_file($tempFile) && ((filemtime($tempFile) + $planParams['JSON_CACHE_TIME'] - time()) <= 0))
		{
			unlink($tempFile);
		}
		
		if($tempFile && is_file($tempFile))
		{
			$tempData = file_get_contents($tempFile);
			$data = unserialize($tempData);
		}
		else
		{
			$code = unserialize(base64_decode($planParams['JSON_GET_DATA']));
			eval($code);
			
			if($replace_next_url)
			{
				$CPlan = new \CWebprostorImportPlan;
				$CPlan->UpdateJsonNextUrl($planParams['ID'], ($json_next_url ? $json_next_url : false));
			}
		}
		
		if(is_array($data))
		{
			if(!is_file($tempFile))
			{
				if((defined("BX_UTF") && BX_UTF) && $planParams['IMPORT_FILE_SHARSET'] != "UTF-8")
				{
					$result["DATA"] = self::convArray($data, $planParams['IMPORT_FILE_SHARSET'], "UTF-8");
				}
				elseif(((defined("BX_UTF") && !BX_UTF) || !defined("BX_UTF")) && $planParams['IMPORT_FILE_SHARSET'] != "WINDOWS-1251")
				{
					$result["DATA"] = self::convArray($data, $planParams['IMPORT_FILE_SHARSET'], "WINDOWS-1251");
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
	
	public function GetEntities($PLAN_ID = false)
	{
		$CPlan = new \CWebprostorImportPlan;
		$planParams = $CPlan->GetById($PLAN_ID)->Fetch();
		
		CheckDirPath($_SERVER["DOCUMENT_ROOT"].'/bitrix/tmp/'.self::MODULE_ID.'/');
		$tempFile = $_SERVER["DOCUMENT_ROOT"].'/bitrix/tmp/'.self::MODULE_ID.'/'.$PLAN_ID.'_entities.txt';
		
		if($tempFile && is_file($tempFile) && ((filemtime($tempFile) + $planParams['JSON_CACHE_TIME'] - time()) <= 0))
		{
			unlink($tempFile);
		}
		
		if($tempFile && is_file($tempFile))
		{
			$tempData = file_get_contents($tempFile);
			$entities = unserialize($tempData);
		}
		else
		{
			$fileInfo = self::getData($planParams, false);
			if(is_array($fileInfo["DATA"][0]))
				$entities = array_keys($fileInfo["DATA"][0]);
			
			if($tempFile && !is_file($tempFile) && $entities)
				file_put_contents($tempFile, serialize($entities));
		}
		
		return $entities;
	}
	
	public function GetSample($PLAN_ID = false)
	{
		$CPlan = new \CWebprostorImportPlan;
		$planParams = $CPlan->GetById($PLAN_ID)->Fetch();
		
		CheckDirPath($_SERVER["DOCUMENT_ROOT"].'/bitrix/tmp/'.self::MODULE_ID.'/');
		$tempFile = $_SERVER["DOCUMENT_ROOT"].'/bitrix/tmp/'.self::MODULE_ID.'/'.$PLAN_ID.'_sample.txt';
		
		if($tempFile && is_file($tempFile) && ((filemtime($tempFile) + $planParams['JSON_CACHE_TIME'] - time()) <= 0))
		{
			unlink($tempFile);
		}
		
		if($tempFile && is_file($tempFile))
		{
			$tempData = file_get_contents($tempFile);
			$entities = unserialize($tempData);
		}
		else
		{
			$fileInfo = self::getData($planParams);
			if(is_array($fileInfo["DATA"][0]))
				$entities = array_values($fileInfo["DATA"][0]);
			
			if($tempFile && !is_file($tempFile) && $entities)
				file_put_contents($tempFile, serialize($entities));
		}
		
		return $entities;
	}
}