<?
class CWebprostorImportProperty
{
	function __construct()
	{
		if(CModule::IncludeModule('iblock'))
			return true;
		else
			return false;
	}
	
	public static function GetPropertyMultiply($PROPERTY_ID)
	{
		$result = false;
		
		$res = CIBlockProperty::GetByID($PROPERTY_ID);
		if($arProperty = $res->GetNext())
		{
			if($arProperty['MULTIPLE'] == "Y")
				$result = true;
		}

		return $result;
	}
	
	public static function PresetPropertiesByParam($data = [], &$params, $IBLOCK_ID = false)
	{
		$result = array();
		
		$newData = [];
		
		foreach($data as $item)
		{
			$itemName = ($item["CODE"] != '' ? $item["CODE"] : CWebprostorImportUtils::ClearField($item["NAME"]));
			if($itemName)
			{
				if(is_array($newData) && array_key_exists($itemName, $newData))
				{
					if(!is_array($newData[$itemName]['VALUE']))
						$newData[$itemName]['VALUE'] = [$newData[$itemName]['VALUE']];
					
					if(!is_array($newData[$itemName]['UNIT']))
						$newData[$itemName]['UNIT'] = [$newData[$itemName]['UNIT']];
					
					$newData[$itemName]['VALUE'][] = CWebprostorImportUtils::ClearField($item['VALUE']);
					$newData[$itemName]['UNIT'][] = CWebprostorImportUtils::ClearField($item['UNIT']);
				}
				else
				{
					$newData[$itemName] = $item;
				}
			}
		}
		unset($data);
		
		foreach($newData as $itemName => $item)
		{
			$itemName = CWebprostorImportUtils::ClearField($item["NAME"]);
			$itemUnit = $item["UNIT"];
			$itemValue = $item["VALUE"];
			$itemCode = $item["CODE"];
			
			$PROPERTY_ID = false;
			
			if($params["XML_SEARCH_BY_PROPERTY_CODE_FIRST"] == "Y" && $itemCode != '')
			{
				$propertiesFilter = ["IBLOCK_ID" => $IBLOCK_ID, "CODE" => $itemCode];
				
				if($params["XML_SEARCH_ONLY_ACTIVE_PROPERTY"] != "N")
				{
					$propertiesFilter['ACTIVE'] = 'Y';
				}
				$properties = CIBlockProperty::GetList(
					["sort" => "asc"], 
					$propertiesFilter
				);
				while ($prop_fields = $properties->GetNext())
				{
					$PROPERTY_ID = $prop_fields["ID"];
				}
			}
			
			if(!$PROPERTY_ID && $params["XML_SEARCH_BY_PROPERTY_CODE_FIRST"] != "Y")
			{
				$propertiesFilter = ["IBLOCK_ID" => $IBLOCK_ID, "NAME" => $itemName];
				if($params["XML_SEARCH_ONLY_ACTIVE_PROPERTY"] != "N")
				{
					$propertiesFilter['ACTIVE'] = 'Y';
				}
				$properties = CIBlockProperty::GetList(
					["sort" => "asc"], 
					$propertiesFilter
				);
				while ($prop_fields = $properties->GetNext())
				{
					$PROPERTY_ID = $prop_fields["ID"];
				}
			}
			
			if(!$PROPERTY_ID && $params["XML_ADD_PROPERTIES_FOR_PARAMS"] == "Y")
			{
				if($params["XML_SEARCH_BY_PROPERTY_CODE_FIRST"] == "Y" && $itemCode != '')
				{
					$CODE = $itemCode;
				}
				else
				{
					$CODE = CWebprostorImportUtils::TranslitValue($itemName, "_", "");
					$CODE = strtoupper($CODE);
					$CODE = trim($CODE, "_");
				}
				
				$FEATURES = Array();
				$PROPERTY_SORT = 500;
			
				if($params["XML_PROPERTY_LIST_PAGE_SHOW"] == "Y")
				{
					$featuresIndex = "n_".count($FEATURES);
					$FEATURES[$featuresIndex] = Array(
						"IS_ENABLED" => "Y",
						"ID" => $featuresIndex,
						"MODULE_ID" => "iblock",
						"FEATURE_ID" => "LIST_PAGE_SHOW",
					);
				}
				if($params["XML_PROPERTY_DETAIL_PAGE_SHOW"] == "Y")
				{
					$featuresIndex = "n_".count($FEATURES);
					$FEATURES[$featuresIndex] = Array(
						"IS_ENABLED" => "Y",
						"ID" => $featuresIndex,
						"MODULE_ID" => "iblock",
						"FEATURE_ID" => "DETAIL_PAGE_SHOW",
					);
				}
				if($params["XML_PROPERTY_YAMARKET_COMMON"] == "Y")
				{
					$featuresIndex = "n_".count($FEATURES);
					$FEATURES[$featuresIndex] = Array(
						"IS_ENABLED" => "Y",
						"ID" => $featuresIndex,
						"MODULE_ID" => "yandex.market",
						"FEATURE_ID" => "YAMARKET_COMMON",
					);
				}
				if($params["XML_PROPERTY_YAMARKET_TURBO"] == "Y")
				{
					$featuresIndex = "n_".count($FEATURES);
					$FEATURES[$featuresIndex] = Array(
						"IS_ENABLED" => "Y",
						"ID" => $featuresIndex,
						"MODULE_ID" => "yandex.market",
						"FEATURE_ID" => "YAMARKET_TURBO",
					);
				}
				
				$properties = CIBlockProperty::GetList(
					["sort" => "desc"], 
					["IBLOCK_ID" => $IBLOCK_ID]
				);
				$propMax = $properties->Fetch();
				if($propMax["SORT"])
					$PROPERTY_SORT = $propMax["SORT"] + 100;
				
				$PROPERTY_TYPE = "S";
				
				$arFields = [
					"NAME" => $itemName,
					"ACTIVE" => "Y",
					"MULTIPLE" => is_array($itemValue)?'Y':"N",
					"CODE" => $CODE,
					"SORT" => $PROPERTY_SORT,
					"PROPERTY_TYPE" => $PROPERTY_TYPE,
					"IBLOCK_ID" => $IBLOCK_ID,
					"WITH_DESCRIPTION" => ((is_array($itemUnit) || (is_string($itemUnit) && $itemUnit!=""))?"Y":"N"),
					"FEATURES" => $FEATURES,
				];
				
				$ibp = new CIBlockProperty;
				$PROPERTY_ID = $ibp->Add($arFields);
			}
			
			if($PROPERTY_ID)
			{
				if(is_array($itemValue))
				{
					foreach($itemValue as $key => $itemValue2)
					{
						$result[$PROPERTY_ID][] = Array(
							"VALUE" => $itemValue2,
							"DESCRIPTION" => $itemUnit[$key],
						);
					}
				}
				elseif(is_string($itemUnit) && $itemUnit != "")
				{
					$result[$PROPERTY_ID] = Array(
						"VALUE" => $itemValue,
						"DESCRIPTION" => $itemUnit,
					);
				}
				else
				{
					$result[$PROPERTY_ID] = $itemValue;
				}
				
			}
			else
			{
				//$ibp->LAST_ERROR;
			}
			unset($itemName, $itemUnit, $itemValue);
		}
		
		return $result;
	}
	
	public static function LinkPropertyValue($code, $value, $ID, $IBLOCK_ID, &$params, $data, &$result)
	{
		$propertyRes = CIBlockProperty::GetByID($code, $IBLOCK_ID);
		if($propertyArray = $propertyRes->GetNext())
		{
			switch($propertyArray["PROPERTY_TYPE"])
			{
				case("S"):
					if($propertyArray["USER_TYPE"] == "directory")
					{
						$highloadblockTable = $propertyArray["USER_TYPE_SETTINGS"]["TABLE_NAME"];
						$value = CWebprostorImportHighload::GetHighloadblockIdByValue($highloadblockTable, $value, $params);
					}
					elseif($propertyArray["USER_TYPE"] == "map_yandex" || $propertyArray["USER_TYPE"] == "map_google")
					{
						$value = Array("VALUE" => "{$value["LATITUDE"]},{$value["LONGITUDE"]}");
					}
					break;
				case("L"):
					$value = self::GetPropertyIdByValue($IBLOCK_ID, $code, $value, $params);
					break;
				case("E"):
					$value = CWebprostorImportElement::GetElementIdByField($propertyArray["LINK_IBLOCK_ID"], $data["SEARCH_PROPERTY_E"][$code], $value, $params["PROPERTIES_ADD_LINK_ELEMENT"]);
					break;
				case("G"):
					if($value)
					{
						$value = CWebprostorImportSection::GetSectionIdByField(
							Array(
								"IBLOCK_ID" => $propertyArray["LINK_IBLOCK_ID"], 
								$data["SEARCH_PROPERTY_G"][$code] => (is_array($value) && isset($value['VALUE']) ? $value['VALUE'] : $value)
							),
							Array("IBLOCK_ID", "ID", $data["SEARCH_PROPERTY_G"][$code])
						);
					}
					break;
				default:
					break;
			}
			
			if($propertyArray["MULTIPLE"] == "Y" && $propertyArray["PROPERTY_TYPE"] != "F" && $params["PROPERTIES_INCREMENT_TO_MULTIPLE"] == "Y")
			{
				$propertyValueRes = CIBlockElement::GetProperty($IBLOCK_ID, $ID, array("sort" => "asc"), array("ID" => $code));
				
				while ($propertyValue = $propertyValueRes->GetNext())
				{
					if($propertyValue['VALUE'])
						$VALUES[] = $propertyValue['VALUE'];
				}
				
				if(is_array($VALUES))
				{
					if(!is_array($value))
					{
						if(!in_array($value, $VALUES))
							$VALUES[] = $value;
					}
					else
					{
						foreach($value as $sValue)
						{
							if(!in_array($sValue, $VALUES))
								$VALUES[] = $sValue;
						}
					}
				}
				else
					$VALUES = $value;
				
				$result = $VALUES;
				unset($VALUES);
			}
			else
			{
				$result = $value;
			}
		}
	}
	
	public static function GetPropertyIdByValue($IBLOCK_ID, $code, $value, &$params, $sort = 500)
	{
		if(is_array($value))
			$result = Array();
		else
			$result = false;
		
		if(!is_array($value))
			$value = Array($value);
		
		if(!empty($value) && !empty($code))
		{
			foreach($value as $sValue)
			{
				if(is_array($sValue) && isset($sValue['VALUE']))
					$sValue = $sValue['VALUE'];
				
				if(!is_string($sValue) || strlen($sValue)==0)
					continue;
				
				$sValue = CWebprostorImportUtils::ClearField($sValue);
				
				$ID = false;
				$xml_id = false;
				
				$propertyRes = CIBlockProperty::GetPropertyEnum(
					$code, 
					[], 
					[
						"IBLOCK_ID"=>$IBLOCK_ID, 
						"VALUE"=>$sValue
					]
				);
				if($propertyValueArr = $propertyRes->GetNext())
				{
					$ID = $propertyValueArr["ID"];
				}
					
				if($params["PROPERTIES_TRANSLATE_XML_ID"] == "Y")
				{
					$xml_id = CWebprostorImportUtils::TranslitValue($sValue, "-", "-");
				}
				
				if($ID && $params["PROPERTIES_UPDATE_LIST_ENUM"] == 'Y')
				{
					$ibpenum = new CIBlockPropertyEnum;
					$ibpenumParams = [
						'PROPERTY_ID' => $code,
						'VALUE' => $sValue,
						"SORT" => $sort !== false ? $sort : intVal($sValue),
					];
					
					if($xml_id)
					{
						$ibpenumParams["XML_ID"] = $xml_id;
					}
					$ibpenum->Update($ID, $ibpenumParams);
				}
				
				if(!$ID && $xml_id)
				{
					$propertyRes = CIBlockProperty::GetPropertyEnum(
						$code, 
						[], 
						[
							"IBLOCK_ID"=>$IBLOCK_ID, 
							"EXTERNAL_ID"=>$xml_id
						]
					);
					if($propertyValueArr = $propertyRes->GetNext())
					{
						$ID = $propertyValueArr["ID"];
					}
				}
				
				if(!$ID && $params["PROPERTIES_ADD_LIST_ENUM"] == "Y")
				{
					$ibpenum = new CIBlockPropertyEnum;
					$ibpenumParams = [
						'PROPERTY_ID' => $code,
						'VALUE' => $sValue,
						"SORT" => $sort !== false ? $sort : intVal($sValue),
					];
					
					if($xml_id)
					{
						$ibpenumParams["XML_ID"] = $xml_id;
					}
					
					$ID = $ibpenum->Add($ibpenumParams);
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
		
		return $result;
	}
	
	public static function DeleteOldFilesByProperty($ELEMENT_ID, $IBLOCK_ID, $PROPERTY_ID)
	{
		$res = CIBlockElement::SetPropertyValuesEx(
			$ELEMENT_ID, 
			$IBLOCK_ID, 
			Array(
				$PROPERTY_ID => array(
					"del" => "Y"
				)
			)
		);
		
		return $res;
	}
	
	public static function DeleteValues($IBLOCK_ID = false, $PROPERTY_ID = false, $EXCLUDE = false)
	{
		if($IBLOCK_ID && $PROPERTY_ID)
		{
			$arSort = ["SORT"=>"ASC"];
			$arFilter = ["IBLOCK_ID"=>$IBLOCK_ID, "PROPERTY_ID"=>$PROPERTY_ID];
			if(is_array($EXCLUDE))
				$arFilter['!ID'] = $EXCLUDE;
			$property_enums = CIBlockPropertyEnum::GetList($arSort, $arFilter);
			while($enum_fields = $property_enums->GetNext())
			{
				CIBlockPropertyEnum::Delete($enum_fields["ID"]);
			}
		}
	}
	
	public static function getPropertiesIdAndValue($values = [])
	{
		$result = [];
		
		foreach($values as $value)
		{
			$result[$value['ID']] = is_array($value['VALUE']) ? implode(' / ', $value['VALUE']) : $value['VALUE'];
		}
		
		return $result;
	}
}
?>