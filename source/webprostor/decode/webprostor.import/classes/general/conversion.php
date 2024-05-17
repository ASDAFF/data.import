<?
//IncludeModuleLangFile(__FILE__);

Class CWebprostorImportConversion
{
	private static function GetPlanByImportFormat($formats = Array())
	{
		$pData = new CWebprostorImportPlan;
		$result = false;
		
		foreach($formats as $format)
		{
			$planRes = $pData->GetList(Array("ID" => "ASC"), Array("IMPORT_FORMAT" => $format), Array("IMPORT_FORMAT", "ID", "NAME"));
			while($planData = $planRes->GetNext())
			{
				$result[] = $planData;
			}
		}
		
		return $result;
	}
	
	public static function ConvertConnectionSectionDepthLevel()
	{
		$cData = new CWebprostorImportPlanConnections;
		
		$planList = self::GetPlanByImportFormat(Array("CSV", "XLS"));
		
		if(count($planList))
		{
			foreach($planList as $planData)
			{
				
				$startDepth = 1;
				
				$cRes = $cData->GetList(
					Array("ID" => "ASC"), 
					Array("!IBLOCK_SECTION_PARENT_FIELD" => "", "IBLOCK_SECTION_FIELD" => "", "PLAN_ID" => $planData["ID"], "IBLOCK_SECTION_DEPTH_LEVEL" => NULL)
				);
				while($arData = $cRes->Fetch())
				{
					$arData["IBLOCK_SECTION_FIELD"] = $arData["IBLOCK_SECTION_PARENT_FIELD"];
					$arData["IBLOCK_SECTION_DEPTH_LEVEL"] = 1;
					$res = $cData->Update($arData["ID"], $arData);
					
					unset($arData);
					$startDepth = 2;
				}
				
				$cRes = $cData->GetList(
					Array("ID" => "ASC"), 
					Array("!IBLOCK_SECTION_FIELD" => "", "PLAN_ID" => $planData["ID"], "IBLOCK_SECTION_DEPTH_LEVEL" => NULL)
				);
				while($arData = $cRes->Fetch())
				{
					$arData["IBLOCK_SECTION_DEPTH_LEVEL"] = $startDepth;
					$res = $cData->Update($arData["ID"], $arData);
					
					unset($arData);
				}
				
				$cRes = $cData->GetList(
					Array("ID" => "ASC"), 
					Array("!IBLOCK_SECTION_PARENT_FIELD" => "", "!IBLOCK_SECTION_FIELD" => "", "PLAN_ID" => $planData["ID"], "!IBLOCK_SECTION_DEPTH_LEVEL" => NULL)
				);
				while($arData = $cRes->Fetch())
				{
					$arData["IBLOCK_SECTION_PARENT_FIELD"] = '';
					$res = $cData->Update($arData["ID"], $arData);
					
					unset($arData);
				}
			}
		}
		
		return true;
	}
	
	public static function ConvertOffersProductSetting()
	{
		global $DB;
		$pData = new CWebprostorImportPlan;
		
		$planRes = $pData->GetList(Array("ID" => "ASC"), Array("IMPORT_CATALOG_PRODUCTS" => "N", "IMPORT_CATALOG_PRODUCT_OFFERS" => "Y"), Array("ID", "NAME", "IMPORT_CATALOG_PRODUCTS", "PRODUCTS_ADD", "PRODUCTS_UPDATE", "IMPORT_CATALOG_PRODUCT_OFFERS", "OFFERS_ADD", "OFFERS_UPDATE"));
		while($planData = $planRes->Fetch())
		{
			$rsData = $pData->GetByID($planData["ID"]);
			$planTemp = $rsData->Fetch();
			
			$planTemp["IMPORT_CATALOG_PRODUCTS"] = $planData["IMPORT_CATALOG_PRODUCT_OFFERS"];
			$planTemp["PRODUCTS_ADD"] = $planData["OFFERS_ADD"];
			$planTemp["PRODUCTS_UPDATE"] = $planData["OFFERS_UPDATE"];
			
			$DB->StartTransaction();
			
			if(!$pData->Update($planData["ID"], $planTemp))
			{
				$DB->Rollback();
				return false;
			}
			
			$DB->Commit();
		}
		return true;
	}
	
	public static function ConvertXlsToXlsx()
	{
		global $DB;
		$pData = new CWebprostorImportPlan;
		
		$planRes = $pData->GetList(Array("ID" => "ASC"), Array("IMPORT_FORMAT" => "XLS"), Array("ID", "NAME", "IMPORT_FORMAT"));
		while($planData = $planRes->Fetch())
		{
			$rsData = $pData->GetByID($planData["ID"]);
			$planTemp = $rsData->Fetch();
			
			$fileInfo = pathinfo($planTemp["IMPORT_FILE"]);
			
			if(strtolower($fileInfo["extension"]) == 'xlsx')
			{
				$planTemp["IMPORT_FORMAT"] = "XLSX";
				$DB->StartTransaction();
				
				if(!$pData->Update($planData["ID"], $planTemp))
				{
					$DB->Rollback();
					return false;
				}
				
				$DB->Commit();
			}
		}
		return true;
	}
	
	public static function ConvertDefaultActive()
	{
		global $DB;
		$pData = new CWebprostorImportPlan;
		
		$planRes = $pData->GetList(
			Array("ID" => "ASC"), 
			Array("LOGIC" => "OR", "SECTIONS_ADD" => "Y", "ELEMENTS_ADD" => "Y"), 
			Array("ID", "NAME", "SECTIONS_ADD", "ELEMENTS_ADD")
		);
		while($planData = $planRes->Fetch())
		{
			$rsData = $pData->GetByID($planData["ID"]);
			$planTemp = $rsData->Fetch();
			
			if($planTemp["SECTIONS_ADD"] == "Y" && $planTemp["SECTIONS_DEFAULT_ACTIVE"] == "N")
				$planTemp["SECTIONS_DEFAULT_ACTIVE"] = "Y";
			
			if($planTemp["ELEMENTS_ADD"] == "Y" && $planTemp["ELEMENTS_DEFAULT_ACTIVE"] == "N")
				$planTemp["ELEMENTS_DEFAULT_ACTIVE"] = "Y";
			
			$DB->StartTransaction();
			
			if(!$pData->Update($planData["ID"], $planTemp))
			{
				$DB->Rollback();
				return false;
			}
			
			$DB->Commit();
		}
		
		return true;
	}
}