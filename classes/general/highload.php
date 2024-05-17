<?
class CDataImportHighload
{
	function __construct()
	{
		if(CModule::IncludeModule('highloadblock'))
			return true;
		else
			return false;
	}
	
	public static function GetHighloadblockIdByValue($table, $value, &$params)
	{
		CModule::IncludeModule('highloadblock');
		
		if(is_array($value))
			$result = Array();
		else
			$result = false;
		
		if(!is_array($value))
			$value = Array($value);
		
		if(!empty($table) && !empty($value))
		{
			$rsData = \Bitrix\Highloadblock\HighloadBlockTable::getList(array('filter'=>array('TABLE_NAME'=>$table)));
			if($hldata = $rsData->Fetch())
			{
				$hlentity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hldata);
				$hlDataClass = $hlentity->getDataClass(); 
				
				foreach($value as $sValue)
				{
					if(is_array($sValue) && isset($sValue['VALUE']))
						$sValue = $sValue['VALUE'];
					
					$sValue = trim($sValue);
					
					if(empty($sValue))
						continue;
					
					$ID = false;
					$res = $hlDataClass::getList(
						array(
							'filter' => array(
								'UF_XML_ID' => $sValue,
							), 
							'select' => ["UF_XML_ID"], 
							'order' => array(
								'UF_XML_ID' => 'asc'
							),
							"limit" => 1,
						)
					);
					
					if ($row = $res->fetch()) {
						$ID = $row["UF_XML_ID"];
					} 
					
					if(!$ID)
					{
						$res = $hlDataClass::getList(
							array(
								'filter' => array(
									'UF_NAME' => $sValue,
								), 
								'select' => ["UF_XML_ID"], 
								'order' => array(
									'UF_NAME' => 'asc'
								),
								"limit" => 1,
							)
						);
						
						if ($row = $res->fetch()) {
							$ID = $row["UF_XML_ID"];
						} 
					}
					
					if(!$ID && $params["PROPERTIES_ADD_DIRECTORY_ENTITY"] == "Y")
					{
						$newEntityParams = Array(
							'UF_NAME'=> $sValue,
							'UF_SORT'=> 500,
						);
						
						$xml_id = CDataImportUtils::TranslitValue($sValue, "_", "_");
						
						if(!is_numeric($xml_id))
							$newEntityParams["UF_XML_ID"] = $xml_id;
						else
							$newEntityParams["UF_XML_ID"] = $table.'_'.$xml_id;
						
						$entityResult = $hlDataClass::Add($newEntityParams);
						if($entityResult->isSuccess())
						{
							$ID = $newEntityParams["UF_XML_ID"];
						}
					}
					
					if($ID)
					{
						if(!is_array($result))
							$result = $ID;
						else
							$result[] = $ID;
					}
				}
			}
		}
		
		return $result;
	}
}
?>