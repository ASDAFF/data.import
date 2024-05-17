<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/data.import/prolog.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/data.import/include.php");

use Bitrix\Main\Localization\Loc;

$module_id = 'data.import';
$moduleAccessLevel = $APPLICATION->GetGroupRight($module_id);

if ($moduleAccessLevel == "D")
    $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));

if($back_url=='')
	$back_url = '/bitrix/admin/data.import_connections.php?lang='.$lang;

$strError = "";

$aTabs = array(
  array("DIV" => "main", "TAB" => Loc::getMessage("ELEMENT_TAB"), "ICON"=>"", "TITLE"=>Loc::getMessage("ELEMENT_TAB_TITLE")),
);

$tabControl = new CAdminTabControl("tabControl", $aTabs);

$sTableID = "data_import_plans_connections";
$ID = intval($ID);
$COPY_ID = intval($COPY_ID);
$PLAN_ID = intval($PLAN_ID);
$bVarsFromForm = false;
$pData = new CDataImportPlan;
$cData = new CDataImportPlanConnections;
$cProcessingData = new CDataImportProcessingSettings;
$cProcessingTypesData = new CDataImportProcessingSettingsTypes;

if($_SERVER["REQUEST_METHOD"] == "POST" && strlen($Update)>0 && check_bitrix_sessid() && $moduleAccessLevel == 'W' && $NO_PLAN_ID != 'Y')
{
	$connectionFields = Array();
	$connectionFields["ACTIVE"] = $ACTIVE;
	$connectionFields["PLAN_ID"] = $PLAN_ID;
	$connectionFields["ENTITY"] = $ENTITY;
	$connectionFields["ENTITY_ATTRIBUTE"] = $ENTITY_ATTRIBUTE;
	$connectionFields["NAME"] = $ENTITY_NAME[$ENTITY];
	$connectionFields["SORT"] = $SORT;
	$connectionFields["IBLOCK_SECTION_FIELD"] = $IBLOCK_SECTION_FIELD;
	$connectionFields["IBLOCK_SECTION_DEPTH_LEVEL"] = $IBLOCK_SECTION_DEPTH_LEVEL;
	$connectionFields["IBLOCK_SECTION_PARENT_FIELD"] = $IBLOCK_SECTION_PARENT_FIELD;
	$connectionFields["IBLOCK_ELEMENT_FIELD"] = $IBLOCK_ELEMENT_FIELD;
	$connectionFields["IBLOCK_ELEMENT_OFFER_FIELD"] = $IBLOCK_ELEMENT_OFFER_FIELD;
	$connectionFields["IBLOCK_ELEMENT_PROPERTY"] = $IBLOCK_ELEMENT_PROPERTY;
	$connectionFields["IBLOCK_ELEMENT_OFFER_PROPERTY"] = $IBLOCK_ELEMENT_OFFER_PROPERTY;
	$connectionFields["IBLOCK_ELEMENT_PROPERTY_E"] = $IBLOCK_ELEMENT_PROPERTY_E;
	$connectionFields["IBLOCK_ELEMENT_PROPERTY_G"] = $IBLOCK_ELEMENT_PROPERTY_G;
	$connectionFields["IBLOCK_ELEMENT_PROPERTY_M"] = $IBLOCK_ELEMENT_PROPERTY_M;
	$connectionFields["CATALOG_PRODUCT_FIELD"] = $CATALOG_PRODUCT_FIELD;
	$connectionFields["CATALOG_PRODUCT_OFFER_FIELD"] = $CATALOG_PRODUCT_OFFER_FIELD;
	$connectionFields["CATALOG_PRODUCT_PRICE"] = $CATALOG_PRODUCT_PRICE;
	$connectionFields["CATALOG_PRODUCT_STORE_AMOUNT"] = $CATALOG_PRODUCT_STORE_AMOUNT;
	$connectionFields["HIGHLOAD_BLOCK_ENTITY_FIELD"] = $HIGHLOAD_BLOCK_ENTITY_FIELD;
	$connectionFields["IS_IMAGE"] = $IS_IMAGE;
	$connectionFields["IS_FILE"] = $IS_FILE;
	$connectionFields["IS_URL"] = $IS_URL;
	$connectionFields["IS_REQUIRED"] = $IS_REQUIRED;
	$connectionFields["IS_ARRAY"] = $IS_ARRAY;
	$connectionFields["USE_IN_SEARCH"] = $USE_IN_SEARCH;
	$connectionFields["USE_IN_CODE"] = $USE_IN_CODE;
	$connectionFields["PROCESSING_TYPES"] = base64_encode(serialize($PROCESSING_TYPES));

	if($ID>0)
		$res = $cData->Update($ID, $connectionFields);
	else
	{
		$ID = $cData->Add($connectionFields);
		$res = ($ID>0);
	}

	if(!$res)
	{
		$strError.= Loc::getMessage("MESSAGE_SAVE_ERROR").":<br />".$cData->LAST_ERROR."";
		$bVarsFromForm = true;
	}
	else
	{
		if(strlen($apply)<=0)
		{
			if(strlen($back_url)>0)
				LocalRedirect("/".ltrim($back_url, "/"));
		}
		else
			LocalRedirect($APPLICATION->GetCurPage()."?lang=".$lang."&ID=".UrlEncode($ID)."&PLAN_ID=".UrlEncode($PLAN_ID)."&".$tabControl->ActiveTabParam());
	}
}

ClearVars("str_");
$str_ACTIVE = "Y";
$str_SORT = "500";

if($ID>0 || $COPY_ID>0)
{
	if($ID>0)
		$result = $cData->GetById($ID);
	else
		$result = $cData->GetById($COPY_ID);
	if(!$result->ExtractFields("str_"))
		$ID='';
	if($COPY_ID>0)
	{
		$PLAN_ID = $str_PLAN_ID;
	}
}

$APPLICATION->SetTitle(($ID>0? Loc::getMessage("ELEMENT_EDIT_TITLE").': '.$str_NAME.' ['.$str_ENTITY.']' : Loc::getMessage("ELEMENT_ADD_TITLE")));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

if($PLAN_ID>0)
{
	$plan = $pData->GetById($PLAN_ID);
	if(!$plan->ExtractFields("plan_"))
		$PLAN_ID=0;
	
	switch($plan_IMPORT_FORMAT)
	{
		case("CSV"):
			$scriptData = new CDataImportCSV;
			break;
		case("XML"):
			$scriptData = new CDataImportXML;
			break;
		case("XLS"):
			$scriptData = new CDataImportXLS;
			break;
		case("XLSX"):
			$scriptData = new CDataImportXLSX;
			break;
		case("ODS"):
		case("XODS"):
			$scriptData = new Data\Import\Format\ODS;
			break;
		case("JSON"):
			$scriptData = new Data\Import\Format\JSON;
			break;
	}
	
	if($PLAN_ID>0)
	{
		$entitiesArray = $scriptData->GetEntities($PLAN_ID);
		if($plan_IMPORT_FORMAT == "XML")
		{
			$attributesArray = $entitiesArray["ATTRIBUTES"];
			$entitiesArray = $entitiesArray["KEYS"];
		}
		if((!is_array($entitiesArray) || count($entitiesArray) == 0) && !$ID)
		{
			if($plan_IMPORT_FORMAT == "XML" && $plan_XML_ENTITY == "")
				$strError .= Loc::getMessage("ERROR_NO_XML_ENTITY").'<br />';
			else
				$strError .= Loc::getMessage("ERROR_NO_IMPORT_FILE_EXIST").'<br />';
		}
		
		$CConnectionFields = new CDataImportPlanConnectionsFields;
		
		$isCatalogSKU = false;
		if(CModule::IncludeModule("catalog"))
		{
			$isCatalogSKU = CCatalogSKU::GetInfoByProductIBlock($plan_IBLOCK_ID);
			$plan_OFFERS_IBLOCK_ID = $isCatalogSKU["IBLOCK_ID"];
			$plan_OFFERS_SKU_PROPERTY_ID = $isCatalogSKU["SKU_PROPERTY_ID"];
		}
		
		if($plan_IMPORT_IBLOCK_SECTIONS=="Y" || $plan_IMPORT_IBLOCK_PROPERTIES=="Y")
		{
			$iblockSectionFields = Array("" => Loc::getMessage("MESSAGE_DO_NOT_USE"));
			$iblockSectionFields = array_merge($iblockSectionFields, $CConnectionFields->GetFields("SECTION", $plan_IBLOCK_ID));
		}
		
		if($plan_IMPORT_IBLOCK_ELEMENTS=="Y" || $plan_IMPORT_IBLOCK_PROPERTIES=="Y" || $plan_IMPORT_CATALOG_PRODUCT_OFFERS=="Y")
		{
			$iblockElementFields = Array("" => Loc::getMessage("MESSAGE_DO_NOT_USE"));
			$iblockElementFields = array_merge($iblockElementFields, $CConnectionFields->GetFields("ELEMENT"));
		}
		
		if($plan_IMPORT_IBLOCK_PROPERTIES == "Y")
		{
			$propertiesArray = Array("" => Loc::getMessage("MESSAGE_DO_NOT_USE"));
			$propRes = CIBlockProperty::GetList(Array("ID"=>"asc", "name"=>"asc"), Array("IBLOCK_ID"=>$plan_IBLOCK_ID));
			while($propFields = $propRes->GetNext())
			{
				$propertiesArray[$propFields["ID"]] = htmlspecialcharsbx($propFields["NAME"]).' ['.$propFields["ID"].']';
			}
		}
		
		if($plan_IMPORT_CATALOG_PRODUCT_OFFERS == "Y" && $isCatalogSKU)
		{
			$offersPropertiesArray = Array("" => Loc::getMessage("MESSAGE_DO_NOT_USE"));
			$propRes = CIBlockProperty::GetList(Array("ID"=>"asc", "name"=>"asc"), Array("IBLOCK_ID"=>$plan_OFFERS_IBLOCK_ID));
			while($propFields = $propRes->GetNext())
			{
				if($propFields["ID"] != $plan_OFFERS_SKU_PROPERTY_ID)
					$offersPropertiesArray[$propFields["ID"]] = htmlspecialcharsbx($propFields["NAME"]).' ['.$propFields["ID"].']';
			}
		}
		
		if($plan_IMPORT_CATALOG_PRODUCTS=="Y" || $plan_IMPORT_CATALOG_PRODUCT_OFFERS == "Y")
		{
			$catalogProductFields = Array("" => Loc::getMessage("MESSAGE_DO_NOT_USE"));
			$catalogProductFields = array_merge($catalogProductFields, $CConnectionFields->GetFields("PRODUCT"));
		}
		
		if($plan_IMPORT_CATALOG_PRICES=="Y" || $plan_IMPORT_CATALOG_PRODUCT_OFFERS == "Y")
		{
			$catalogProductPrice = Array("" => Loc::getMessage("MESSAGE_DO_NOT_USE"));
			$catalogProductPrice = array_merge($catalogProductPrice, $CConnectionFields->GetFields("PRICE"));
		}
		
		if($plan_IMPORT_CATALOG_STORE_AMOUNT=="Y")
		{
			$catalogProductStore = Array("" => Loc::getMessage("MESSAGE_DO_NOT_USE"));
			$catalogProductStore = array_merge($catalogProductStore, $CConnectionFields->GetFields("STORE"));
		}
		
		if($plan_IMPORT_HIGHLOAD_BLOCK_ENTITIES=="Y")
		{
			$highloadBlockEntityFields = Array("" => Loc::getMessage("MESSAGE_DO_NOT_USE"));
			$highloadBlockEntityFields = array_merge($highloadBlockEntityFields, $CConnectionFields->GetFields("ENTITIES", false, $plan_HIGHLOAD_BLOCK));
		}
		
		if($plan_IMPORT_FORMAT != "XML")
		{
			$useInCodeValues = $CConnectionFields->GetUseInCode([
				"SECTIONS" => $plan_IMPORT_IBLOCK_SECTIONS,
				"ELEMENTS" => $plan_IMPORT_IBLOCK_ELEMENTS,
			]);
		}
	}
}
	
$queryObject = $pData->getList(Array($b = "sort" => $o = "asc"), array());
$listPlans = array();
while($plan = $queryObject->getNext())
	$listPlans[$plan["ID"]] = htmlspecialcharsbx($plan["NAME"]).' ['.$plan["ID"].']';

$queryObject = $cProcessingData->getList(Array($b = "sort" => $o = "asc"), array());
$listTypes = Array();
while($type = $queryObject->getNext())
	$listTypes[$type["ID"]] = htmlspecialcharsbx($type["PROCESSING_TYPE"]).' ['.$type["ID"].'] '.$cProcessingTypesData->GetParamsValue($type["PARAMS"], '; ');

if($bVarsFromForm)
{
	$DB->InitTableVarsForEdit($sTableID, "", "str_");
}

$arFields["MAIN"]["LABEL"] = Loc::getMessage("TABLE_HEADING_MAIN");

if($ID>0)
{
	$arFields["MAIN"]["ITEMS"][] = Array(
		"CODE" => "ID",
		"TYPE" => "LABEL",
		"LABEL" => Loc::getMessage("TABLE_HEADING_ID"),
		"VALUE" => $str_ID,
	);
}

/*if(!$PLAN_ID>0)
{*/
	$arFields["MAIN"]["ITEMS"][] = Array(
		"CODE" => "PLAN_ID",
		"TYPE" => "SELECT",
		"DISABLED" => $PLAN_ID>0?"Y":'N',
		"REFRESH" => $PLAN_ID>0?"N":'Y',
		"LABEL" => Loc::getMessage("TABLE_HEADING_PLAN_ID"),
		"VALUE" => $str_PLAN_ID>0?$str_PLAN_ID:$PLAN_ID,
		"ITEMS" => $listPlans,
	);
/*}
else
{
	$arFields["MAIN"]["ITEMS"][] = Array(
		"CODE" => "PLAN_ID",
		"TYPE" => "LABEL",
		"LABEL" => Loc::getMessage("TABLE_HEADING_PLAN_ID"),
		"VALUE" => $PLAN_ID,
	);
}*/

if($PLAN_ID>0)
{
	$arFields["MAIN"]["ITEMS"][] = Array(
		"CODE" => "ACTIVE",
		"TYPE" => "CHECKBOX",
		"LABEL" => Loc::getMessage("TABLE_HEADING_ACTIVE"),
		"DESCRIPTION" => Loc::getMessage("TABLE_HEADING_ACTIVE_NOTE"),
		"VALUE" => $str_ACTIVE,
	);

	$arFields["MAIN"]["ITEMS"][] = Array(
		"CODE" => "IS_REQUIRED",
		"TYPE" => "CHECKBOX",
		"LABEL" => Loc::getMessage("TABLE_HEADING_IS_REQUIRED"),
		"DESCRIPTION" => Loc::getMessage("TABLE_HEADING_IS_REQUIRED_NOTE"),
		"VALUE" => $str_IS_REQUIRED,
	);
	$arFields["MAIN"]["ITEMS"][] = Array(
		"CODE" => "IS_ARRAY",
		"TYPE" => "CHECKBOX",
		"LABEL" => Loc::getMessage("TABLE_HEADING_IS_ARRAY"),
		"DESCRIPTION" => Loc::getMessage("TABLE_HEADING_IS_ARRAY_NOTE"),
		"VALUE" => $str_IS_ARRAY,
	);

	$arFields["MAIN"]["ITEMS"][] = Array(
		"CODE" => "USE_IN_SEARCH",
		"TYPE" => "CHECKBOX",
		"LABEL" => Loc::getMessage("TABLE_HEADING_USE_IN_SEARCH"),
		"DESCRIPTION" => Loc::getMessage("TABLE_HEADING_USE_IN_SEARCH_NOTE"),
		"VALUE" => $str_USE_IN_SEARCH,
	);

	if($plan_IMPORT_FORMAT != "XML")
	{
		$arFields["MAIN"]["ITEMS"][] = Array(
			"CODE" => "USE_IN_CODE",
			"TYPE" => "SELECT",
			"ITEMS" => $useInCodeValues,
			"LABEL" => Loc::getMessage("TABLE_HEADING_USE_IN_CODE"),
			"DESCRIPTION" => Loc::getMessage("TABLE_HEADING_USE_IN_CODE_NOTE"),
			"VALUE" => $str_USE_IN_CODE,
		);
	}

	$arFields["MAIN"]["ITEMS"][] = Array(
		"CODE" => "SORT",
		"TYPE" => "TEXT",
		"PARAMS" => Array(
			"SIZE" => "10",
			"MAXLENGTH" => "11",
		),
		"LABEL" => Loc::getMessage("TABLE_HEADING_ENTITY_SORT"),
		"VALUE" => $str_SORT,
	);
	
	$arFields["ENTITIES"]["LABEL"] = Loc::getMessage("TABLE_HEADING_ENTITIES");

	if(is_array($entitiesArray) && count($entitiesArray))
	{
		$entitiesArraySelect = [];
		foreach($entitiesArray as $key => $value)
		{
			$entitiesArraySelect[$key] = $value.' ['.$key.']';
		}
		$arFields["ENTITIES"]["ITEMS"][] = Array(
			"CODE" => "ENTITY",
			"TYPE" => "SELECT",
			"LABEL" => Loc::getMessage("TABLE_HEADING_ENTITY"),
			"VALUE" => $str_ENTITY,
			"ITEMS" => $entitiesArraySelect,
		);
	}
	else
	{
		$arFields["ENTITIES"]["ITEMS"][] = Array(
			"CODE" => "ENTITY",
			"TYPE" => "HIDDEN",
			"LABEL" => Loc::getMessage("TABLE_HEADING_ENTITY"),
			"VALUE" => $str_ENTITY,
		);
	}
	
	$arFields["ENTITIES"]["ITEMS"][] = Array(
		"CODE" => "NAME",
		"TYPE" => "TEXT",
		"DISABLED" => "Y",
		"PARAMS" => Array(
			"SIZE" => "30",
			"MAXLENGTH" => "255",
		),
		"LABEL" => Loc::getMessage("TABLE_HEADING_NAME"),
		"DESCRIPTION" => Loc::getMessage("TABLE_HEADING_NAME_NOTE"),
		"VALUE" => $str_NAME,
	);

	if($plan_IMPORT_FORMAT == "XML" || $plan_IMPORT_FORMAT == "JSON")
	{
		$arFields["ENTITIES"]["ITEMS"][] = Array(
			"CODE" => "ENTITY_ATTRIBUTE",
			"TYPE" => "TEXT",
			"PARAMS" => Array(
				"SIZE" => "30",
				"MAXLENGTH" => "255",
			),
			"LABEL" => Loc::getMessage("TABLE_HEADING_ENTITY_ATTRIBUTE"),
			"VALUE" => $str_ENTITY_ATTRIBUTE,
		);
	}

	$arFields["FILES"]["LABEL"] = Loc::getMessage("TABLE_HEADING_FILES");

	$arFields["FILES"]["ITEMS"][] = Array(
		"CODE" => "IS_IMAGE",
		"TYPE" => "CHECKBOX",
		"LABEL" => Loc::getMessage("TABLE_HEADING_IS_IMAGE"),
		"DESCRIPTION" => Loc::getMessage("TABLE_HEADING_IS_IMAGE_NOTE"),
		"VALUE" => $str_IS_IMAGE,
	);
	$arFields["FILES"]["ITEMS"][] = Array(
		"CODE" => "IS_FILE",
		"TYPE" => "CHECKBOX",
		"LABEL" => Loc::getMessage("TABLE_HEADING_IS_FILE"),
		"DESCRIPTION" => Loc::getMessage("TABLE_HEADING_IS_FILE_NOTE"),
		"VALUE" => $str_IS_FILE,
	);
	$arFields["FILES"]["ITEMS"][] = Array(
		"CODE" => "IS_URL",
		"TYPE" => "CHECKBOX",
		"LABEL" => Loc::getMessage("TABLE_HEADING_IS_URL"),
		"DESCRIPTION" => Loc::getMessage("TABLE_HEADING_IS_URL_NOTE"),
		"VALUE" => $str_IS_URL,
	);

	if($str_PROCESSING_TYPES)
	{
		$processingTypesArr = unserialize(base64_decode($str_PROCESSING_TYPES));
		if(!is_array($processingTypesArr))
			$processingTypesArr = Array();
	}
	else
	{
		$processingTypesArr = array();
	}

	$arFields["PROCESSING"]["LABEL"] = Loc::getMessage("TABLE_HEADING_PROCESSING_TYPES");
	$arFields["PROCESSING"]["ITEMS"][] = Array(
		"CODE" => "PROCESSING_TYPES[]",
		"ID" => "PROCESSING_TYPES",
		"TYPE" => "SELECT",
		"LABEL" => Loc::getMessage("TABLE_HEADING_USE_PROCESSING_TYPE"),
		"VALUE" => $processingTypesArr,
		"ITEMS" => $listTypes,
		"PARAMS" => array(
			"MULTIPLE" => "Y",
			"CHECK_ALL" => "Y",
		),
	);
}

if($plan_IMPORT_IBLOCK_SECTIONS=="Y")
{
	$arFields["SECTIONS"]["LABEL"] = Loc::getMessage("TABLE_HEADING_SECTIONS");
	$arFields["SECTIONS"]["ITEMS"][] = Array(
		"CODE" => "IBLOCK_SECTION_FIELD",
		"TYPE" => "SELECT",
		"LABEL" => Loc::getMessage("TABLE_HEADING_IBLOCK_SECTION_FIELD"),
		"VALUE" => $str_IBLOCK_SECTION_FIELD,
		"ITEMS" => $iblockSectionFields,
	);
	if($plan_IMPORT_FORMAT == "XML")
	{
		$arFields["SECTIONS"]["ITEMS"][] = Array(
			"CODE" => "IBLOCK_SECTION_PARENT_FIELD",
			"TYPE" => "SELECT",
			"LABEL" => Loc::getMessage("TABLE_HEADING_IBLOCK_SECTION_PARENT_FIELD"),
			"VALUE" => $str_IBLOCK_SECTION_PARENT_FIELD,
			"ITEMS" => $iblockSectionFields,
		);
	}
	else
	{
		$dl = 1;
		$sectionDepthLevels = Array("" => Loc::getMessage("MESSAGE_DO_NOT_USE"));
		while($dl <= intVal($plan_CSV_XLS_MAX_DEPTH_LEVEL))
		{
			$sectionDepthLevels[$dl] = $dl;
			$dl++;
		}
		$arFields["SECTIONS"]["ITEMS"][] = Array(
			"CODE" => "IBLOCK_SECTION_DEPTH_LEVEL",
			"TYPE" => "SELECT",
			"LABEL" => Loc::getMessage("TABLE_IBLOCK_SECTION_DEPTH_LEVEL"),
			"VALUE" => $str_IBLOCK_SECTION_DEPTH_LEVEL,
			"ITEMS" => $sectionDepthLevels,
		);
	}
}
if($plan_IMPORT_IBLOCK_ELEMENTS=="Y")
{
	$arFields["ELEMENTS"]["LABEL"] = Loc::getMessage("TABLE_HEADING_ELEMENTS");
	$arFields["ELEMENTS"]["ITEMS"][] = Array(
		"CODE" => "IBLOCK_ELEMENT_FIELD",
		"TYPE" => "SELECT",
		"LABEL" => Loc::getMessage("TABLE_HEADING_IBLOCK_ELEMENT_FIELD"),
		"VALUE" => $str_IBLOCK_ELEMENT_FIELD,
		"ITEMS" => $iblockElementFields,
	);
}

if($plan_IMPORT_IBLOCK_PROPERTIES=="Y")
{
	$arFields["PROPERTIES"]["LABEL"] = Loc::getMessage("TABLE_HEADING_PROPERTIES");
	$arFields["PROPERTIES"]["ITEMS"][] = Array(
		"CODE" => "IBLOCK_ELEMENT_PROPERTY",
		"TYPE" => "SELECT",
		"LABEL" => Loc::getMessage("TABLE_HEADING_IBLOCK_ELEMENT_PROPERTY"),
		"VALUE" => $str_IBLOCK_ELEMENT_PROPERTY,
		"ITEMS" => $propertiesArray,
	);
	$arFields["PROPERTIES"]["ITEMS"][] = Array(
		"CODE" => "IBLOCK_ELEMENT_PROPERTY_M",
		"TYPE" => "SELECT",
		"LABEL" => Loc::getMessage("TABLE_HEADING_IBLOCK_ELEMENT_PROPERTY_M"),
		"VALUE" => $str_IBLOCK_ELEMENT_PROPERTY_M,
		"ITEMS" => Array(
			"" => Loc::getMessage("MESSAGE_DO_NOT_USE"),
			"latitude" => Loc::getMessage("MAP_LATITUDE"),
			"longitude" => Loc::getMessage("MAP_LONGITUDE")
		),
	);
	$arFields["PROPERTIES"]["ITEMS"][] = Array(
		"CODE" => "IBLOCK_ELEMENT_PROPERTY_E",
		"TYPE" => "SELECT",
		"LABEL" => Loc::getMessage("TABLE_HEADING_IBLOCK_ELEMENT_PROPERTY_E"),
		"DESCRIPTION" => Loc::getMessage("TABLE_HEADING_IBLOCK_ELEMENT_PROPERTY_E_NOTE"),
		"VALUE" => $str_IBLOCK_ELEMENT_PROPERTY_E,
		"ITEMS" => $iblockElementFields,
	);
	$arFields["PROPERTIES"]["ITEMS"][] = Array(
		"CODE" => "IBLOCK_ELEMENT_PROPERTY_G",
		"TYPE" => "SELECT",
		"LABEL" => Loc::getMessage("TABLE_HEADING_IBLOCK_ELEMENT_PROPERTY_G"),
		"VALUE" => $str_IBLOCK_ELEMENT_PROPERTY_G,
		"ITEMS" => $iblockSectionFields,
	);
}

if($plan_IMPORT_CATALOG_PRODUCT_OFFERS == "Y" && $isCatalogSKU)
{
	$arFields["OFFERS"]["LABEL"] = Loc::getMessage("TABLE_HEADING_OFFERS");
	$arFields["OFFERS"]["ITEMS"][] = Array(
		"CODE" => "IBLOCK_ELEMENT_OFFER_FIELD",
		"TYPE" => "SELECT",
		"LABEL" => Loc::getMessage("TABLE_HEADING_IBLOCK_ELEMENT_OFFER_FIELD"),
		"VALUE" => $str_IBLOCK_ELEMENT_OFFER_FIELD,
		"ITEMS" => $iblockElementFields,
	);
	$arFields["OFFERS"]["ITEMS"][] = Array(
		"CODE" => "IBLOCK_ELEMENT_OFFER_PROPERTY",
		"TYPE" => "SELECT",
		"LABEL" => Loc::getMessage("TABLE_HEADING_IBLOCK_ELEMENT_OFFER_PROPERTY"),
		"VALUE" => $str_IBLOCK_ELEMENT_OFFER_PROPERTY,
		"ITEMS" => $offersPropertiesArray,
	);
	$arFields["PRODUCTS"]["LABEL"] = Loc::getMessage("TABLE_HEADING_PRODUCTS");
	$arFields["PRODUCTS"]["ITEMS"][] = Array(
		"CODE" => "CATALOG_PRODUCT_OFFER_FIELD",
		"TYPE" => "SELECT",
		"LABEL" => Loc::getMessage("TABLE_HEADING_CATALOG_PRODUCT_OFFER_FIELD"),
		"VALUE" => $str_CATALOG_PRODUCT_OFFER_FIELD,
		"ITEMS" => $catalogProductFields,
	);
}

if($plan_IMPORT_CATALOG_PRODUCTS=="Y")
{
	$arFields["PRODUCTS"]["LABEL"] = Loc::getMessage("TABLE_HEADING_PRODUCTS");
	$arFields["PRODUCTS"]["ITEMS"][] = Array(
		"CODE" => "CATALOG_PRODUCT_FIELD",
		"TYPE" => "SELECT",
		"LABEL" => Loc::getMessage("TABLE_HEADING_CATALOG_PRODUCT_FIELD"),
		"VALUE" => $str_CATALOG_PRODUCT_FIELD,
		"ITEMS" => $catalogProductFields,
	);
}

if($plan_IMPORT_CATALOG_PRICES=="Y")
{
	$arFields["PRICES"]["LABEL"] = Loc::getMessage("TABLE_HEADING_PRICES");
	$arFields["PRICES"]["ITEMS"][] = Array(
		"CODE" => "CATALOG_PRODUCT_PRICE",
		"TYPE" => "SELECT",
		"LABEL" => Loc::getMessage("TABLE_HEADING_CATALOG_PRODUCT_PRICE"),
		"VALUE" => $str_CATALOG_PRODUCT_PRICE,
		"ITEMS" => $catalogProductPrice,
	);
}

if($plan_IMPORT_CATALOG_STORE_AMOUNT=="Y")
{
	$arFields["STORE_AMOUNT"]["LABEL"] = Loc::getMessage("TABLE_HEADING_STORE_AMOUNT");
	$arFields["STORE_AMOUNT"]["ITEMS"][] = Array(
		"CODE" => "CATALOG_PRODUCT_STORE_AMOUNT",
		"TYPE" => "SELECT",
		"LABEL" => Loc::getMessage("TABLE_HEADING_CATALOG_PRODUCT_STORE_AMOUNT"),
		"VALUE" => $str_CATALOG_PRODUCT_STORE_AMOUNT,
		"ITEMS" => $catalogProductStore,
	);
}

if($plan_IMPORT_HIGHLOAD_BLOCK_ENTITIES=="Y")
{
	$arFields["HIGHLOAD_ENTITIES"]["LABEL"] = Loc::getMessage("TABLE_HEADING_HIGHLOAD_ENTITIES");
	$arFields["HIGHLOAD_ENTITIES"]["ITEMS"][] = Array(
		"CODE" => "HIGHLOAD_BLOCK_ENTITY_FIELD",
		"TYPE" => "SELECT",
		"LABEL" => Loc::getMessage("TABLE_HEADING_HIGHLOAD_BLOCK_ENTITY_FIELD"),
		"VALUE" => $str_HIGHLOAD_BLOCK_ENTITY_FIELD,
		"ITEMS" => $highloadBlockEntityFields,
	);
}

CAdminMessage::ShowOldStyleError($strError);

$aMenu = array(
	array(
		"TEXT" => Loc::getMessage("CONNECTIONS_LIST"),
		"TITLE" => Loc::getMessage("CONNECTIONS_LIST_TITLE"),
		"LINK" => "data.import_connections.php?lang=".LANGUAGE_ID,
		"ICON" => "btn_list"
	)
);

if($ID>0)
{
	$aMenu[] = array("SEPARATOR"=>"Y");
	
	if($moduleAccessLevel == 'W')
	{
		$aMenu[] = array(
			"TEXT"  => Loc::getMessage("BTN_ACTIONS"),
			"TITLE" => Loc::getMessage("BTN_ACTIONS_TITLE"),
			"ICON"  => "btn_new",
			"MENU"  => Array(
				array(
					"TEXT" => Loc::getMessage("ADD_CONNECTION"),
					"TITLE" => Loc::getMessage("ADD_CONNECTION_TITLE"),
					"LINK" => "data.import_connection_edit.php?lang=".LANGUAGE_ID,
					"ICON" => "edit"
				),
				array(
					"TEXT" => Loc::getMessage("COPY_CONNECTION"),
					"TITLE" => Loc::getMessage("COPY_CONNECTION_TITLE"),
					"LINK" => "data.import_connection_edit.php?COPY_ID={$ID}&lang=".LANGUAGE_ID,
					"ICON" => "copy"
				),
				array(
					"TEXT" => Loc::getMessage("DEL_CONNECTION"),
					"TITLE" => Loc::getMessage("DEL_CONNECTION_TITLE"),
					"LINK" => "javascript:if(confirm('".GetMessageJS("DEL_CONNECTION_CONFIRM")."')) window.location='/bitrix/admin/data.import_connections.php?ID=".$ID."&action=delete&lang=".LANGUAGE_ID."&".bitrix_sessid_get()."';",
					"ICON" => "delete"
				),
			),
		);
	}
}

$context = new CAdminContextMenu($aMenu);
$context->Show();
?>
<form method="POST" id="form" name="form" action="data.import_connection_edit.php?lang=<?echo LANG?>">
<?=bitrix_sessid_post()?>
<input type="hidden" name="Update" value="Y">
<?
if($ID>0) {
?>
<input type="hidden" name="ID" value="<?echo $ID?>">
<? } ?>
<input type="hidden" name="PLAN_ID" value="<?echo $PLAN_ID?>">
<? if(!$PLAN_ID>0) { ?>
<input type="hidden" name="NO_PLAN_ID" value="Y">
<? } ?>
<?
if(is_array($entitiesArray))
{
foreach($entitiesArray as $value => $label)
{
?>
<input type="hidden" name="ENTITY_NAME[<?=$value;?>]" value="<?=htmlspecialcharsbx($label);?>" />
<?
}
}
?>
<?if(strlen($back_url)>0):?><input type="hidden" name="back_url" value="<?=htmlspecialcharsbx($back_url)?>"><?endif?>
<?
$tabControl->Begin();
$tabControl->BeginNextTab();

CDataCoreFunctions::ShowFormFields($arFields);

$tabControl->Buttons(
	/*array(
		"disabled"=>($moduleAccessLevel<"W" || ((!is_array($entitiesArray) || count($entitiesArray) == 0) && !$ID)),
		"back_url"=>$back_url,
	)*/
);
?>
<button 
title="<?=Loc::getMessage("SAVE_TITLE")?>" 
class="ui-btn ui-btn-success" 
type="submit" 
value="Y" 
name="save"
<?=($moduleAccessLevel<"W" || ((!is_array($entitiesArray) || count($entitiesArray) == 0) && !$ID))?" disabled":""?>
>
	<?=Loc::getMessage("SAVE")?>
</button>
<button 
title="<?=Loc::getMessage("APPLY_TITLE")?>" 
class="ui-btn ui-btn-primary" 
type="submit" 
value="Y" 
name="apply"
<?=($moduleAccessLevel<"W" || ((!is_array($entitiesArray) || count($entitiesArray) == 0) && !$ID))?" disabled":""?>
>
	<?=Loc::getMessage("APPLY")?>
</button>
<button 
title="<?=Loc::getMessage("CANCEL_TITLE")?>" 
class="ui-btn ui-btn-link" 
type="button" 
name="cancel" 
onclick="top.window.location='<?=$back_url?>'"
>
	<?=Loc::getMessage("CANCEL")?>
</button>
<?
$tabControl->End();
?>
</form>
<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>