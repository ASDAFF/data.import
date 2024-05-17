<?
class CDataImportCSV
{
	const TZP = ";";
	const ZPT = ",";
	const TAB = "	";
	const SPS = " ";
	CONST MODULE_ID = "data.import";
	
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
	
	public function ParseFile($file = '', $char = "UTF-8", $getRowNumber = false, $delimiter = 'TZP')
	{
		global $PLAN_ID;
		if($delimiter == '')
			$delimiter = 'TZP';
		
		$result = Array();
		
		if(is_file($file))
		{
			if($getRowNumber !== false && $getRowNumber>=0)
			{
				$handle = @fopen($file, "r");
				if ($handle)
				{
					$i = 0;
					while($rowData = fgetcsv($handle, 0, constant('self::'.$delimiter)))
					{
						if($getRowNumber == $i)
						{
							if((defined("BX_UTF") && BX_UTF) && $char != "UTF-8")
							{
								$result["DATA"] = self::convArray($rowData, $char, "UTF-8");
							}
							elseif(((defined("BX_UTF") && !BX_UTF) || !defined("BX_UTF")) && $char != "WINDOWS-1251")
							{
								$result["DATA"] = self::convArray($rowData, $char, "WINDOWS-1251");
							}
							else
							{
								$result["DATA"] = $rowData;
							}
							if(is_array($result["DATA"]))
								$result["ITEMS_COUNT"] = count($result["DATA"]);
							break;
						}
						else
						{
							$i++;
						}
					}
				}
				fclose($handle);
			}
			else
			{
				CheckDirPath($_SERVER["DOCUMENT_ROOT"].'/bitrix/tmp/'.self::MODULE_ID.'/');
				$tempFile = $_SERVER["DOCUMENT_ROOT"].'/bitrix/tmp/'.self::MODULE_ID.'/'.$PLAN_ID.'_data.txt';
		
				if($tempFile && is_file($file) && is_file($tempFile) && (filemtime($file) > filemtime($tempFile)))
				{
					unlink($tempFile);
				}
		
				if($tempFile && is_file($file) && is_file($tempFile) && (filemtime($file) < filemtime($tempFile)))
				{
					$tempData = file_get_contents($tempFile);
					$data = unserialize($tempData);
				}
				else
				{
					$handle = @fopen($file, "r");
					if ($handle)
					{
						while($rowData = fgetcsv($handle, 0, constant('self::'.$delimiter)))
						{
							$data[] = $rowData;
						}
					}
					fclose($handle);
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
				
				if(is_array($result["DATA"]))
				{
					$result["ITEMS_COUNT"] = count($result["DATA"]);
			
					if($tempFile && !is_file($tempFile))
						file_put_contents($tempFile, serialize($result["DATA"]));
				}
			}
		}
		
		return $result;
	}
	
	public function GetDataArray($data = Array(), $params = Array(), $startFrom = 0)
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
		$CPlan = new CDataImportPlan;
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
			$GLOBALS["PLAN_ID"] = $PLAN_ID;
			
			$fileInfo = self::ParseFile($IMPORT_FILE, $planInfo["IMPORT_FILE_SHARSET"], $planInfo["CSV_XLS_NAME_LINE"], $planInfo["CSV_DELIMITER"]);
			$entities = $fileInfo["DATA"];
			
			if($tempFile && !is_file($tempFile) && $entities)
				file_put_contents($tempFile, serialize($entities));
		}
		
		return $entities;
	}
	
	public function GetSample($PLAN_ID = false, $IMPORT_FILE = false)
	{
		$CPlan = new CDataImportPlan;
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
			$GLOBALS["PLAN_ID"] = $PLAN_ID;
			
			$fileInfo = self::ParseFile($IMPORT_FILE, $planInfo["IMPORT_FILE_SHARSET"], $planInfo["CSV_XLS_START_LINE"], $planInfo["CSV_DELIMITER"]);
			$entities = $fileInfo["DATA"];
			
			if($tempFile && !is_file($tempFile) && $entities)
				file_put_contents($tempFile, serialize($entities));
		}
		
		return $entities;
	}
}