<?
class CDataImportFinishEvents
{
	function __construct()
	{
		if(CModule::IncludeModule('iblock'))
			return true;
		else
			return false;
	}
	
	public static function DeleteDirFilesOnly($fileDir)
	{
		if($fileDir != '')
			$fileFullDir = $_SERVER["DOCUMENT_ROOT"].$fileDir;
		
		if(file_exists($fileFullDir))
		{
			$dir = opendir($fileFullDir);
				
			while(($file = readdir($dir)) !== false)
			{
				if ($file == '..' || $file == '.')
					continue;
				if(is_dir($fileFullDir.$file))
				{
					DeleteDirFilesEx($fileDir.$file);
				}
				elseif(is_file($fileFullDir.$file))
				{
					unlink($fileFullDir.$file);
				}
			}
			
			closedir($dir);
			return true;
		}
		
		return false;
	}
	
	public static function ChangeOutSections(&$searchSectionIdList, $action, $IBLOCK_ID, $arPreFilter, $updateSearch = false)
	{
		global $DB, $DEBUG_PLAN_ID;
		
		$arPreFilter = CDataImportFilter::SerializePreFilter($arPreFilter);
		
		$hideCount = 0;
		$deleteCount = 0;
		$bs = new CIBlockSection;
		
		$arSelect = Array("IBLOCK_ID", "ID");
		$arFilter = Array(
			'IBLOCK_ID'=>$IBLOCK_ID,
			'!ID' => $searchSectionIdList
		);
		if($action == "H")
		{
			$arFilter["ACTIVE"] = "Y";
		}
		
		if(is_array($arPreFilter))
		{
			$arFilter = array_merge($arPreFilter, $arFilter);
		}
		
		$db_list = CIBlockSection::GetList(
			false, 
			$arFilter, 
			false, 
			$arSelect, 
			true
		);
		while($sectionArr = $db_list->GetNext())
		{
			switch($action)
			{
				case("H"):
					$arFields = Array(
						"ACTIVE" => "N",
					);
					$resSec = $bs->Update($sectionArr['ID'], $arFields, true, $updateSearch);
					if($resSec)
						$hideCount ++;
					
					break;
				case("D"):
					$resSec = $bs->Delete($sectionArr['ID']);
					if($resSec)
						$deleteCount ++;
					
					break;
			}
		}
		
		$result = Array(
			"HIDEN" => $hideCount,
			"DELETED" => $deleteCount
		);
		
		return $result;
	}
	
	public static function ChangeInSections(&$searchSectionIdList, $action, $IBLOCK_ID, $arPreFilter, $updateSearch = false)
	{
		global $DB, $DEBUG_PLAN_ID;
		
		$arPreFilter = CDataImportFilter::SerializePreFilter($arPreFilter);
		
		$activateCount = 0;
		$bs = new CIBlockSection;
		
		$arSelect = Array("IBLOCK_ID", "ID");
		$arFilter = [
			'IBLOCK_ID' => $IBLOCK_ID, 
			'ID' => $searchSectionIdList,
			"ACTIVE" => "N"
		];
		
		if(is_array($arPreFilter))
		{
			$arFilter = array_merge($arPreFilter, $arFilter);
		}

		$db_list = CIBlockSection::GetList(
			false, 
			$arFilter, 
			false, 
			$arSelect, 
			true
		);
		while($sectionArr = $db_list->GetNext())
		{
			switch($action)
			{
				case("A"):
					$arFields = Array(
						"ACTIVE" => "Y",
					);
					$resSec = $bs->Update($sectionArr['ID'], $arFields, true, $updateSearch);
					if($resSec)
						$activateCount ++;
					
					break;
			}
		}
		
		$result = Array(
			"ACTIVATED" => $activateCount
		);
		
		return $result;
	}
	
	public static function ChangeOutElements(&$searchElementIdList, $action, $IBLOCK_ID, $arPreFilter, $updateSearch = false)
	{
		global $DB, $DEBUG_PLAN_ID;
		
		$arPreFilter = CDataImportFilter::SerializePreFilter($arPreFilter);
		
		$hideCount = 0;
		$deleteCount = 0;
		$resetQuantityCount = 0;
		
		$el = new CIBlockElement;
		
		$arSelect = Array("IBLOCK_ID", "ID");
		$arFilter = [
			'IBLOCK_ID' => $IBLOCK_ID,
			'!ID' => $searchElementIdList
		];
		
		if($action == "H")
		{
			$arFilter["ACTIVE"] = "Y";
		}
		elseif($action == "Q")
		{
			$arFilter[">QUANTITY"] = 0;
		}
		
		if(is_array($arPreFilter))
		{
			$arFilter = array_merge($arPreFilter, $arFilter);
		}
		
		$elementsRes = CIBlockElement::GetList(
			false, 
			$arFilter, 
			false, 
			Array(), 
			$arSelect
		);
		while($element = $elementsRes->GetNextElement()) {
			$arFields = $element->GetFields();
			
			switch($action)
			{
				case("H"):
					$arLoadProductArray = Array(
						"ACTIVE" => "N",
					);
					$resultEl = $updateResult = $el->Update($arFields['ID'], $arLoadProductArray, false, $updateSearch);
					if($resultEl)
						$hideCount ++;
					
					break;
				case("D"):
					$resultEl = $deleteResult = $el->Delete($arFields['ID']);
					if($resultEl)
						$deleteCount ++;
					
					break;
				case("Q"):
					if(CModule::IncludeModule('catalog'))
					{
						$arProductFields = ['QUANTITY' => 0];
						$resultPr = CCatalogProduct::Update($arFields['ID'], $arProductFields);
						if($resultPr)
							$resetQuantityCount++;
					}
					
					break;
			}
		}
		
		$result = Array(
			"HIDEN" => $hideCount,
			"DELETED" => $deleteCount,
			"RESET_QUANTITY" => $resetQuantityCount
		);
		
		return $result;
	}
	
	public static function ChangeInElements(&$searchElementIdList, $action, $IBLOCK_ID, $arPreFilter, $updateSearch = false)
	{
		global $DB, $DEBUG_PLAN_ID;
		
		$arPreFilter = CDataImportFilter::SerializePreFilter($arPreFilter);
		
		$activateCount = 0;
		$el = new CIBlockElement;
		
		$arSelect = Array("IBLOCK_ID", "ID");
		$arFilter = [
			'IBLOCK_ID' => $IBLOCK_ID, 
			'ID' => $searchElementIdList,
			"ACTIVE" => "N"
		];

		if(is_array($arPreFilter))
		{
			$arFilter = array_merge($arPreFilter, $arFilter);
		}
		
		$elementsRes = CIBlockElement::GetList(
			false, 
			$arFilter, 
			false, 
			[], 
			$arSelect
		);
		
		while($element = $elementsRes->GetNextElement()) {
			$arFields = $element->GetFields();
			
			switch($action)
			{
				case("A"):
					$arLoadProductArray = Array(
						"ACTIVE" => "Y",
					);
					$resultEl = $el->Update($arFields['ID'], $arLoadProductArray, false, $updateSearch);
					if($resultEl)
						$activateCount ++;
					
					break;
			}
		}
		
		$result = Array(
			"ACTIVATED" => $activateCount
		);
		
		return $result;
	}
}
?>