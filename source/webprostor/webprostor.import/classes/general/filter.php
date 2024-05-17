<?
class CWebprostorImportFilter
{
	static public function getCompareList()
	{
		$list = Array("=", "!=", "<", "<=", ">", ">=");
		return $list;
	}
	
	static private function clearCode($code)
	{
		if(strpos($code, "SECTIONS_") === 0)
		{
			$code = str_replace("SECTIONS_", "", $code);
		}
		if(strpos($code, "ELEMENTS_") === 0)
		{
			$code = str_replace("ELEMENTS_", "", $code);
		}
		if(strpos($code, "PROPERTIES_") === 0)
		{
			$code = str_replace("PROPERTIES_", "", $code);
		}
		if(strpos($code, "PRODUCTS_") === 0)
		{
			//$code = str_replace("PRODUCTS_", "CATALOG_", $code);
			$code = str_replace("PRODUCTS_", "", $code);
		}
		if(strpos($code, "PRICES_") === 0)
		{
			if($code == 'PRICES_PRICE' || $code == 'PRICES_CURRENCY')
			{
				if(CModule::IncludeModule("catalog"))
				{
					$basePriceGroup = CCatalogGroup::GetBaseGroup();
					$basePrice = $basePriceGroup["ID"];
				}
				else
					$basePrice = 1;
				$code = str_replace("PRICES_", "", $code);
				$code = $code."_{$basePrice}";
			}
			else
				$code = str_replace("PRICES_", "", $code);
		}
		if(strpos($code, "STORES_") === 0)
		{
			$code = str_replace("STORES_STORE_", "STORE_AMOUNT_", $code);
		}
		return $code;
	}
	
	static public function SerializePreFilter($arToFilter)
	{
		if (CheckSerializedData($arToFilter)) {
			$arData = json_decode($arToFilter);
		}
		$arFilter = false;
		$compareList = self::getCompareList();
		if(is_array($arData) && count($arData)>0)
		{
			$i = 0;
			foreach($arData as $key => $data)
			{
				if($key%3 == 0)
					$i++;
				switch($data->name)
				{
					case("OBJECT"):
						$arTempFilter[$i]["CODE"] = self::clearCode($data->value);
						break;
					case("OBJECT_COMPARE"):
						switch($data->value)
						{
							case(6):
								break;
							default:
								$prepend = $compareList[$data->value];
								$append = false;
								break;
						}
						$arTempFilter[$i]["CODE"] = $prepend.$arTempFilter[$i]["CODE"].$append;
						break;
					case("OBJECT_VALUE"):
						$arTempFilter[$i]["VALUE"] = $data->value;
						break;
				}
			}
		}
		if(is_array($arTempFilter) && count($arTempFilter)>0)
		{
			foreach($arTempFilter as $item)
			{
				$arFilter[$item["CODE"]] = $item["VALUE"];
			}
		}
		
		return $arFilter;
	}
}
?>