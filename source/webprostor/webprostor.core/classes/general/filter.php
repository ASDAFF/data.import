<?
use Bitrix\Main\Loader;

Class CWebprostorCoreFilter
{
	public static function GetFieldsForFilter($IBLOCK_ID = false)
	{
		$filterFields = array(
			array(
				"id" => "NAME",
				"name" => GetMessage("IBLIST_A_NAME"),
				"filterable" => "",
				"quickSearch" => "",
				"default" => true
			),
			array(
				"id" => "ID",
				"name" => "ID",
				"type" => "number",
				"filterable" => ""
			),
			array(
				"id" => "CODE",
				"name" => GetMessage("IBLIST_A_CODE"),
				"filterable" => ""
			),
			array(
				"id" => "EXTERNAL_ID",
				"name" => GetMessage("IBLIST_A_EXTCODE"),
				"filterable" => ""
			),
		);
		
		$bCatalog = Loader::includeModule("catalog");

		if ($bCatalog)
		{
			$productTypeList = CCatalogAdminTools::getIblockProductTypeList($IBLOCK_ID, true);
			$filterFields[] = array(
				"id" => "CATALOG_TYPE",
				"name" => GetMessage("IBLIST_A_CATALOG_TYPE"),
				"type" => "list",
				"items" => $productTypeList,
				"params" => array("multiple" => "Y"),
				"filterable" => ""
			);
			$filterFields[] = array(
				"id" => "CATALOG_BUNDLE",
				"name" => GetMessage("IBLIST_A_CATALOG_BUNDLE"),
				"type" => "list",
				"items" => array(
					"Y" => GetMessage("IBLOCK_YES"),
					"N" => GetMessage("IBLOCK_NO")
				),
				"filterable" => ""
			);
			$filterFields[] = array(
				"id" => "CATALOG_AVAILABLE",
				"name" => GetMessage("IBLIST_A_CATALOG_AVAILABLE"),
				"type" => "list",
				"items" => array(
					"Y" => GetMessage("IBLOCK_YES"),
					"N" => GetMessage("IBLOCK_NO")
				),
				"filterable" => ""
			);
			$filterFields[] = array(
				"id" => "QUANTITY",
				"name" => GetMessage("IBLIST_A_CATALOG_QUANTITY_EXT"),
				"type" => "number",
				"filterable" => ""
			);
			
			$measureList[0] = GetMessage("IBLOCK_ALL");
			$measureIterator = CCatalogMeasure::getList(
				array(),
				array(),
				false,
				false,
				array('ID', 'MEASURE_TITLE', 'SYMBOL_RUS')
			);
			while($measure = $measureIterator->Fetch())
			{
				$measureList[$measure['ID']] = ($measure['SYMBOL_RUS'] != ''
					? $measure['SYMBOL_RUS']
					: $measure['MEASURE_TITLE']
				);
			}
			unset($measure, $measureIterator);
			asort($measureList);
			
			$filterFields[] = array(
				"id" => "MEASURE",
				"name" => GetMessage("IBLIST_A_CATALOG_MEASURE_TITLE"),
				"type" => "list",
				"items" => $measureList,
				"params" => array("multiple" => "Y"),
				"filterable" => ""
			);
		}
		
		return $filterFields;
	}
	public static function GetFieldsForForm($IBLOCK_ID = false, $properties = Array(), $default_values = Array())
	{
		$filterFields["MAIN"] = Array(
			"LABEL" => GetMessage("MAIN"),
			"ITEMS" => array(
				array(
					"CODE" => "ID",
					"LABEL" => "ID",
					"TYPE" => "RANGE",
				),
				array(
					"CODE" => "NAME",
					"LABEL" => GetMessage("IBLIST_A_NAME"),
					"TYPE" => "TEXT",
				),
				array(
					"CODE" => "CODE",
					"LABEL" => GetMessage("IBLIST_A_CODE"),
					"TYPE" => "TEXT",
				),
				array(
					"CODE" => "EXTERNAL_ID",
					"LABEL" => GetMessage("IBLIST_A_EXTCODE"),
					"TYPE" => "TEXT",
				),
			)
		);
		
		if(count($properties))
		{
			$filterFields["PROPERTIES"] = Array("LABEL" => GetMessage("PROPERTIES"));
			foreach($properties as $property)
			{
				$temp = Array(
					"CODE" => $property["id"],
					"LABEL" => $property["name"],
					"TYPE" => strtoupper($property["type"]),
					"ITEMS" => $property["items"],
					"PARAMS" => is_array($property["params"])?array_change_key_case($property["params"], CASE_UPPER):$property["params"],
				);
				if(in_array($temp["TYPE"], Array("NUMBER")))
				{
					$temp["TYPE"] = "RANGE";
				}
				if(in_array($temp["TYPE"], Array("ENTITY_SELECTOR")))
				{
					$temp["TYPE"] = "HIGHLOAD_ENTITES";
					$paramsEntities = $property['params']['dialogOptions']['entities'];
					$paramsEntitiesOptions = array_pop($paramsEntities);
					$temp["PARAMS"]["TABLE_ID"] = $paramsEntitiesOptions['options']['highloadblockId'];
				}
				if(!in_array($temp["TYPE"], Array("TEXT", "SELECT", "LIST", "RANGE", "HIGHLOAD_ENTITES")))
				{
					$temp["TYPE"] = "TEXT";
				}
				if(in_array($temp["TYPE"], Array("SELECT", "LIST", "HIGHLOAD_ENTITES")) && $temp["PARAMS"]["MULTIPLE"] != "Y")
				{
					$temp["PARAMS"]["MULTIPLE"] = "Y";
				}
				if(isset($temp["ITEMS"]["NOT_REF"]))
				{
					unset($temp["ITEMS"]["NOT_REF"]);
				}
				$filterFields["PROPERTIES"]["ITEMS"][] = $temp;
				unset($temp);
			}
		}
		
		$bCatalog = Loader::includeModule("catalog");

		if ($bCatalog)
		{
			$filterFields["CATALOG"] = Array("LABEL" => GetMessage("CATALOG"));
			$productTypeList = CCatalogAdminTools::getIblockProductTypeList($IBLOCK_ID, true);
			$filterFields["CATALOG"]["ITEMS"][] = array(
				"CODE" => "CATALOG_TYPE",
				"LABEL" => GetMessage("IBLIST_A_CATALOG_TYPE"),
				"TYPE" => "LIST",
				"ITEMS" => $productTypeList,
				"PARAMS" => array("MULTIPLE" => "Y"),
			);
			$filterFields["CATALOG"]["ITEMS"][] = array(
				"CODE" => "CATALOG_BUNDLE",
				"LABEL" => GetMessage("IBLIST_A_CATALOG_BUNDLE"),
				"TYPE" => "LIST",
				"ITEMS" => array(
					"N" => GetMessage("IBLOCK_NO"),
					"Y" => GetMessage("IBLOCK_YES"),
				),
				"PARAMS" => array("MULTIPLE" => "Y"),
			);
			$filterFields["CATALOG"]["ITEMS"][] = array(
				"CODE" => "CATALOG_AVAILABLE",
				"LABEL" => GetMessage("IBLIST_A_CATALOG_AVAILABLE"),
				"TYPE" => "LIST",
				"ITEMS" => array(
					"Y" => GetMessage("IBLOCK_YES"),
					"N" => GetMessage("IBLOCK_NO"),
				),
				"PARAMS" => array("MULTIPLE" => "Y"),
			);
			$filterFields["CATALOG"]["ITEMS"][] = array(
				"CODE" => "QUANTITY",
				"LABEL" => GetMessage("IBLIST_A_CATALOG_QUANTITY_EXT"),
				"TYPE" => "RANGE",
			);
			
			$measureIterator = CCatalogMeasure::getList(
				array(),
				array(),
				false,
				false,
				array('ID', 'MEASURE_TITLE', 'SYMBOL_RUS')
			);
			while($measure = $measureIterator->Fetch())
			{
				$measureList[$measure['ID']] = ($measure['SYMBOL_RUS'] != ''
					? $measure['SYMBOL_RUS']
					: $measure['MEASURE_TITLE']
				);
			}
			unset($measure, $measureIterator);
			asort($measureList);
			
			$filterFields["CATALOG"]["ITEMS"][] = array(
				"CODE" => "MEASURE",
				"LABEL" => GetMessage("IBLIST_A_CATALOG_MEASURE_TITLE"),
				"TYPE" => "LIST",
				"ITEMS" => $measureList,
				"PARAMS" => array("MULTIPLE" => "Y"),
			);
		}
		
		foreach($filterFields as $k1 => $arSection)
		{
			if(count($arSection["ITEMS"]))
			{
				foreach($arSection["ITEMS"] as $k2 => $field)
				{
					if($field["PARAMS"]["MULTIPLE"] == "Y")
						$filterFields[$k1]["ITEMS"][$k2]["CODE"] = "PREFILTER[".$field["CODE"]."][]";
					else
						$filterFields[$k1]["ITEMS"][$k2]["CODE"] = "PREFILTER[".$field["CODE"]."]";
					$filterFields[$k1]["ITEMS"][$k2]["VALUE"] = $default_values[$field["CODE"]];
				}
			}
		}
		
		return $filterFields;
	}
}
?>