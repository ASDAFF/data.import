<?
IncludeModuleLangFile(__FILE__);

class CWebprostorImportPlanConnectionsFields
{
	
	static public function GetUseInCode($params = [])
	{
		$result = Array(
			"" => "-",
			//"OFFER" => GetMessage("CODE_OFFER"),
		);
		if($params["SECTIONS"] == "Y")
			$result["SECTION"] = GetMessage("CODE_SECTION");
		if($params["ELEMENTS"] == "Y")
			$result["ELEMENT"] = GetMessage("CODE_ELEMENT");
		if($params["ENTITIES"] == "Y")
			$result["ENTITY"] = GetMessage("CODE_ENTITY");
		
		return $result;
	}
	
	static public function GetFields($type, $IBLOCK_ID = false, $HIGHLOAD_BLOCK = false, $additionalFields = true)
	{
		$fields = Array();
		
		switch($type)
		{
			case("ELEMENT"):
				$fields = Array(
					"ID" => GetMessage("ELEMENT_FIELD_ID"),
					"CODE" => GetMessage("FIELD_CODE"),
					"XML_ID" => GetMessage("FIELD_XML_ID"),
					"NAME" => GetMessage("ELEMENT_FIELD_NAME"),
					"IBLOCK_SECTION_ID" => GetMessage("ELEMENT_FIELD_IBLOCK_SECTION_ID"),
					"IBLOCK_SECTION" => GetMessage("ELEMENT_FIELD_IBLOCK_SECTION"),
					"ACTIVE" => GetMessage("FIELD_ACTIVE"),
					"ACTIVE_FROM" => GetMessage("ELEMENT_FIELD_ACTIVE_FROM"),
					"ACTIVE_TO" => GetMessage("ELEMENT_FIELD_ACTIVE_TO"),
					"SORT" => GetMessage("FIELD_SORT"),
					"PREVIEW_PICTURE" => GetMessage("ELEMENT_FIELD_PREVIEW_PICTURE"),
					"DETAIL_PICTURE" => GetMessage("FIELD_DETAIL_PICTURE"),
					"PREVIEW_TEXT" => GetMessage("ELEMENT_FIELD_PREVIEW_TEXT"),
					"PREVIEW_TEXT_TYPE" => GetMessage("ELEMENT_FIELD_PREVIEW_TEXT_TYPE"),
					"DETAIL_TEXT" => GetMessage("ELEMENT_FIELD_DETAIL_TEXT"),
					"DETAIL_TEXT_TYPE" => GetMessage("ELEMENT_FIELD_DETAIL_TEXT_TYPE"),
					"DATE_CREATE" => GetMessage("ELEMENT_FIELD_DATE_CREATE"),
					"CREATED_BY" => GetMessage("ELEMENT_FIELD_CREATED_BY"),
					"MODIFIED_BY" => GetMessage("ELEMENT_FIELD_MODIFIED_BY"),
					"SHOW_COUNTER" => GetMessage("ELEMENT_FIELD_SHOW_COUNTER"),
					"SHOW_COUNTER_START" => GetMessage("ELEMENT_FIELD_SHOW_COUNTER_START"),
					"TAGS" => GetMessage("ELEMENT_FIELD_TAGS"),
					"ELEMENT_META_TITLE" => GetMessage("FIELD_META_TITLE"),
					"ELEMENT_META_KEYWORDS" => GetMessage("FIELD_META_KEYWORDS"),
					"ELEMENT_META_DESCRIPTION" => GetMessage("FIELD_META_DESCRIPTION"),
				);
				break;
			case("SECTION"):
				$fields = Array(
					"ID" => GetMessage("SECTION_FIELD_ID"),
					"CODE" => GetMessage("FIELD_CODE"),
					"XML_ID" => GetMessage("FIELD_XML_ID"),
					"IBLOCK_SECTION_ID" => GetMessage("SECTION_FIELD_IBLOCK_SECTION_ID"),
					"SORT" => GetMessage("FIELD_SORT"),
					"NAME" => GetMessage("SECTION_FIELD_NAME"),
					"ACTIVE" => GetMessage("FIELD_ACTIVE"),
					"PICTURE" => GetMessage("SECTION_FIELD_PICTURE"),
					"DESCRIPTION" => GetMessage("SECTION_FIELD_DESCRIPTION"),
					"DESCRIPTION_TYPE" => GetMessage("SECTION_FIELD_DESCRIPTION_TYPE"),
					"DATE_CREATE" => GetMessage("SECTION_FIELD_DATE_CREATE"),
					"CREATED_BY" => GetMessage("SECTION_FIELD_CREATED_BY"),
					"MODIFIED_BY" => GetMessage("SECTION_FIELD_MODIFIED_BY"),
					"DETAIL_PICTURE" => GetMessage("FIELD_DETAIL_PICTURE"),
					"SECTION_META_TITLE" => GetMessage("FIELD_META_TITLE"),
					"SECTION_META_KEYWORDS" => GetMessage("FIELD_META_KEYWORDS"),
					"SECTION_META_DESCRIPTION" => GetMessage("FIELD_META_DESCRIPTION"),
				);
				if($IBLOCK_ID)
				{
					$arSort = array("SORT" => "ASC");
					$arFilter = array("ENTITY_ID" => "IBLOCK_{$IBLOCK_ID}_SECTION", "LANG" => LANGUAGE_ID);
					$rsData = CUserTypeEntity::GetList($arSort , $arFilter);
					while($arRes = $rsData->Fetch())
					{
						$fields[$arRes["FIELD_NAME"]] = htmlspecialchars($arRes["EDIT_FORM_LABEL"])." [".$arRes["FIELD_NAME"]."]";
					}
				}
				break;
			case("PRODUCT"):
				$fields = Array(
					"ID" => GetMessage("ELEMENT_FIELD_ID"),
					"VAT_ID" => GetMessage("CATALOG_FIELD_VAT_ID"),
					"VAT_INCLUDED" => GetMessage("CATALOG_FIELD_VAT_INCLUDED"),
					"QUANTITY" => GetMessage("CATALOG_FIELD_QUANTITY"),
					"QUANTITY_RESERVED" => GetMessage("CATALOG_FIELD_QUANTITY_RESERVED"),
					"QUANTITY_TRACE" => GetMessage("CATALOG_FIELD_QUANTITY_TRACE"),
					"CAN_BUY_ZERO" => GetMessage("CATALOG_FIELD_CAN_BUY_ZERO"),
					"SUBSCRIBE" => GetMessage("CATALOG_FIELD_SUBSCRIBE"),
					"PURCHASING_PRICE" => GetMessage("CATALOG_FIELD_PURCHASING_PRICE"),
					"PURCHASING_CURRENCY" => GetMessage("CATALOG_FIELD_PURCHASING_CURRENCY"),
					"WEIGHT" => GetMessage("CATALOG_FIELD_WEIGHT"),
					"WEIGHT_KG" => GetMessage("CATALOG_FIELD_WEIGHT_KG"),
					"WIDTH" => GetMessage("CATALOG_FIELD_WIDTH"),
					"WIDTH_SM" => GetMessage("CATALOG_FIELD_WIDTH_SM"),
					"LENGTH" => GetMessage("CATALOG_FIELD_LENGTH"),
					"LENGTH_SM" => GetMessage("CATALOG_FIELD_LENGTH_SM"),
					"HEIGHT" => GetMessage("CATALOG_FIELD_HEIGHT"),
					"HEIGHT_SM" => GetMessage("CATALOG_FIELD_HEIGHT_SM"),
					"DIMENSIONS" => GetMessage("CATALOG_FIELD_DIMENSIONS"),
					"MEASURE" => GetMessage("CATALOG_FIELD_MEASURE"),
					"MEASURE_RATIO" => GetMessage("CATALOG_FIELD_MEASURE_RATIO"),
					"BARCODE" => GetMessage("CATALOG_FIELD_BARCODE"),
				);
				if(!$additionalFields)
					unset(
						$fields['ID'], 
						$fields['WEIGHT_KG'], 
						$fields['WIDTH_SM'], 
						$fields['LENGTH_SM'], 
						$fields['HEIGHT_SM'], 
						$fields['DIMENSIONS'],
						$fields['MEASURE_RATIO'], 
						$fields['BARCODE']
					);
				break;
			case("PRICE"):
				if(CModule::IncludeModule("catalog"))
				{
					$fields = Array(
						"PRODUCT_ID" => GetMessage("PRICE_FIELD_PRODUCT_ID"),
						"PRICE" => GetMessage("PRICE_FIELD_PRICE"),
						"CURRENCY" => GetMessage("PRICE_FIELD_CURRENCY"),
						"QUANTITY_FROM" => GetMessage("PRICE_FIELD_QUANTITY_FROM"),
						"QUANTITY_TO" => GetMessage("PRICE_FIELD_QUANTITY_TO"),
					);
					$dbPriceType = CCatalogGroup::GetList(
						array("SORT" => "ASC"),
						array("BASE" => "N")
					);
					while ($arPriceType = $dbPriceType->Fetch())
					{
						$fields["PRICE_".$arPriceType["ID"]] = GetMessage("PRICE_FIELD_PRICE_2", Array("#PRICE_ID#" => $arPriceType["NAME_LANG"]));
						$fields["CURRENCY_".$arPriceType["ID"]] = GetMessage("PRICE_FIELD_CURRENCY_2", Array("#PRICE_ID#" => $arPriceType["NAME_LANG"]));
						$fields["EXTRA_ID_".$arPriceType["ID"]] = GetMessage("PRICE_FIELD_EXTRA_ID_2", Array("#PRICE_ID#" => $arPriceType["NAME_LANG"]));
						$fields["QUANTITY_FROM_".$arPriceType["ID"]] = GetMessage("PRICE_FIELD_QUANTITY_FROM_2", Array("#PRICE_ID#" => $arPriceType["NAME_LANG"]));
						$fields["QUANTITY_TO_".$arPriceType["ID"]] = GetMessage("PRICE_FIELD_QUANTITY_TO_2", Array("#PRICE_ID#" => $arPriceType["NAME_LANG"]));
					}
					if(!$additionalFields)
						unset(
							$fields['PRODUCT_ID']
						);
				}
				break;
			case("STORE"):
				if(CModule::IncludeModule("catalog"))
				{
					$fields = Array();
					$dbStore = CCatalogStore::GetList(
						array("SORT" => "ASC"),
						array()
					);
					while ($arStore = $dbStore->Fetch())
					{
						$fields["STORE_".$arStore["ID"]] = htmlspecialcharsbx($arStore["TITLE"]).' ['.$arStore["ID"].']';
					}
				}
				break;
			case("ENTITIES"):
				if(CModule::IncludeModule("highloadblock"))
				{
					$fields = Array();
					if($HIGHLOAD_BLOCK)
					{
						$arSort = array("SORT" => "ASC");
						$arFilter = array("ENTITY_ID" => "HLBLOCK_{$HIGHLOAD_BLOCK}", "LANG" => LANGUAGE_ID);
						$rsData = CUserTypeEntity::GetList($arSort , $arFilter);
						while($arRes = $rsData->Fetch())
						{
							$fields[$arRes["FIELD_NAME"]] = htmlspecialchars($arRes["EDIT_FORM_LABEL"])." [".$arRes["FIELD_NAME"]."]";
						}
					}
				}
				break;
		}
		
		return $fields;
	}
}
?>