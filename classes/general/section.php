<?
class CDataImportSection
{
	function __construct()
	{
		if(CModule::IncludeModule('iblock'))
			return true;
		else
			return false;
	}
	
	public static function GetSectionIdByField($arFilter, $arSelect)
	{
		$el = new CIBlockSection;
		
		$sectionRes = $el->GetList(
			["SORT" => "ASC"], 
			$arFilter, 
			false, 
			["nPageSize"=>1], 
			$arSelect
		);
		$findSection = false;
		while($findSection2 = $sectionRes->GetNext())
		{
			$ID = $findSection2['ID'];
			$findSection = $findSection2;
		}
		
		return $ID;
	}
}
?>