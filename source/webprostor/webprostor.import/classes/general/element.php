<?
class CWebprostorImportElement
{
	function __construct()
	{
		if(CModule::IncludeModule('iblock'))
			return true;
		else
			return false;
	}
	
	public static function GetElementIdByField($IBLOCK_ID, $SEARCH_BY = false, $value = false, $addLinkElement = false)
	{
		if(is_array($value))
			$result = Array();
		else
			$result = false;
		
		if(
			$SEARCH_BY
			&& $value
			&& (
				(is_string($value) && !empty($value))
				|| (is_array($value) && count($value)>0)
			)
		)
		{
		
			if(!is_array($value))
				$value = Array($value);
			
			$el = new CIBlockElement;
			foreach($value as $sValue)
			{
				if(is_array($sValue) && isset($sValue['VALUE']))
					$sValue = $sValue['VALUE'];
				
				$arFilter = Array(
					"IBLOCK_ID" => $IBLOCK_ID, 
					$SEARCH_BY => $sValue
				);
				$arSelect = Array("IBLOCK_ID", "ID", $SEARCH_BY);

				$elementRes = $el->GetList(
					["SORT" => "ASC"], 
					$arFilter, 
					false, 
					["nPageSize"=>1], 
					$arSelect
				);
				
				$findElement = false;
				$ID = false;
				
				while($findElement = $elementRes->GetNext())
				{
					$ID = $findElement['ID'];
				}
				
				if(!$ID && $addLinkElement == "Y" && $SEARCH_BY == "NAME")
				{
					$arFields = $arFilter;
					
					$arFields["CODE"] = CWebprostorImportUtils::TranslitValue($sValue, "-", "-");
					
					$ID = $el->Add($arFields, false, true, false);
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
	
	public static function GetElementNameByID($ELEMENT_ID = false, $params)
	{
		if(!$ELEMENT_ID)
			return false;
		
		$result = false;
		
		$el = new CIBlockElement;
		
		$elementRes = $el->GetList(
			["SORT" => "ASC"], 
			["IBLOCK_ID" => $params["IBLOCK_ID"], "ID" => $ELEMENT_ID], 
			false, 
			["nPageSize"=>1], 
			["NAME"]
		);
		
		while($elementArr = $elementRes->GetNextElement())
		{
			$result = $elementArr['NAME'];
		}
		
		return $result;
	}
}
?>