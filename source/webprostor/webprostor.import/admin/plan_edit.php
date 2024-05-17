<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/webprostor.import/prolog.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/webprostor.import/include.php");

use Bitrix\Main\Localization\Loc,
	Bitrix\Main\Loader;

$module_id = 'webprostor.import';
$moduleAccessLevel = $APPLICATION->GetGroupRight($module_id);

if ($moduleAccessLevel == "D")
    $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));

if($back_url=='')
	$back_url = "webprostor.import_plans.php?lang=".LANG;

$aTabs = [
	array("DIV" => "main", "TAB" => Loc::getMessage("ELEMENT_TAB_1"), "ICON"=>"", "TITLE"=>Loc::getMessage("ELEMENT_TAB_1_TITLE")),
	array("DIV" => "objects", "TAB" => Loc::getMessage("ELEMENT_TAB_2"), "ICON"=>"", "TITLE"=>Loc::getMessage("ELEMENT_TAB_2_TITLE")),
];

$sTableID = "webprostor_import_plans";
$ID = intval($ID);
$COPY_ID = intval($COPY_ID);
$message = null;
$strError = '';
//$strNotifications = '';
$bVarsFromForm = false;

$arFields = array();

$aTabs[] = array("DIV" => "setting", "TAB" => Loc::getMessage("ELEMENT_TAB_3"), "ICON"=>"", "TITLE"=>Loc::getMessage("ELEMENT_TAB_3_TITLE"));
$aTabs[] = array("DIV" => "debug", "TAB" => Loc::getMessage("ELEMENT_TAB_4"), "ICON"=>"", "TITLE"=>Loc::getMessage("ELEMENT_TAB_4_TITLE"));

$tabControl = new CAdminTabControl("tabControl", $aTabs);

if($REQUEST_METHOD == "POST" && ($save!="" || $apply!="") && $moduleAccessLevel=="W" && check_bitrix_sessid()) 
{
	$element = new CWebprostorImportPlan;

	$arFields = Array(
		"NAME" => $NAME,
		"SORT" => $SORT,
		"ACTIVE" => $ACTIVE,
		"SHOW_IN_MENU" => $SHOW_IN_MENU,
		"SHOW_IN_MANUALLY" => $SHOW_IN_MANUALLY,
		"IBLOCK_ID" => $IBLOCK_ID,
		"HIGHLOAD_BLOCK" => $HIGHLOAD_BLOCK,
		"IMPORT_FORMAT" => $IMPORT_FORMAT,
		"ITEMS_PER_ROUND" => $ITEMS_PER_ROUND,
		"IMPORT_FILE" => $IMPORT_FILE,
		"IMPORT_FILE_SHARSET" => $IMPORT_FILE_SHARSET,
		"IMPORT_FILE_DELETE" => $IMPORT_FILE_DELETE,
		"IMPORT_FILE_CHECK" => $IMPORT_FILE_CHECK,
		"IMPORT_FILE_URL" => $IMPORT_FILE_URL,
		"IMPORT_FILE_REPLACE" => $IMPORT_FILE_REPLACE,
		"URL_LOGIN" => $URL_LOGIN,
		"URL_PASSWORD" => $URL_PASSWORD,
		"IMPORT_IBLOCK_SECTIONS" => $IMPORT_IBLOCK_SECTIONS,
		"SECTIONS_UPDATE_SEARCH" => $SECTIONS_UPDATE_SEARCH,
		"SECTIONS_ADD" => $SECTIONS_ADD,
		"SECTIONS_DEFAULT_ACTIVE" => $SECTIONS_DEFAULT_ACTIVE,
		"SECTIONS_DEFAULT_SECTION_ID" => $SECTIONS_DEFAULT_SECTION_ID,
		"SECTIONS_UPDATE" => $SECTIONS_UPDATE,
		"SECTIONS_OUT_ACTION" => $SECTIONS_OUT_ACTION,
		"SECTIONS_OUT_ACTION_FILTER" => $SECTIONS_OUT_ACTION_FILTER,
		"SECTIONS_IN_ACTION" => $SECTIONS_IN_ACTION,
		"SECTIONS_IN_ACTION_FILTER" => $SECTIONS_IN_ACTION_FILTER,
		"IMPORT_IBLOCK_ELEMENTS" => $IMPORT_IBLOCK_ELEMENTS,
		"ELEMENTS_PREFILTER" => $ELEMENTS_PREFILTER,
		"ELEMENTS_SKIP_IMPORT_WITHOUT_SECTION" => $ELEMENTS_SKIP_IMPORT_WITHOUT_SECTION,
		"ELEMENTS_2_STEP_SEARCH" => $ELEMENTS_2_STEP_SEARCH,
		"ELEMENTS_UPDATE_SEARCH" => $ELEMENTS_UPDATE_SEARCH,
		"ELEMENTS_ADD" => $ELEMENTS_ADD,
		"ELEMENTS_DEFAULT_ACTIVE" => $ELEMENTS_DEFAULT_ACTIVE,
		"ELEMENTS_DEFAULT_SECTION_ID" => $ELEMENTS_DEFAULT_SECTION_ID,
		"ELEMENTS_DEFAULT_DESCRIPTION_TYPE" => $ELEMENTS_DEFAULT_DESCRIPTION_TYPE,
		"ELEMENTS_UPDATE" => $ELEMENTS_UPDATE,
		"ELEMENTS_DONT_UPDATE_IMAGES_IF_EXISTS" => $ELEMENTS_DONT_UPDATE_IMAGES_IF_EXISTS,
		"ELEMENTS_OUT_ACTION" => $ELEMENTS_OUT_ACTION,
		"ELEMENTS_OUT_ACTION_FILTER" => $ELEMENTS_OUT_ACTION_FILTER,
		"ELEMENTS_IN_ACTION" => $ELEMENTS_IN_ACTION,
		"ELEMENTS_IN_ACTION_FILTER" => $ELEMENTS_IN_ACTION_FILTER,
		"IMPORT_IBLOCK_PROPERTIES" => $IMPORT_IBLOCK_PROPERTIES,
		//"PROPERTIES_ADD" => $PROPERTIES_ADD,
		"PROPERTIES_UPDATE" => $PROPERTIES_UPDATE,
		"PROPERTIES_UPDATE_SKIP_IF_EVENT_SEARCH" => $PROPERTIES_UPDATE_SKIP_IF_EVENT_SEARCH,
		"PROPERTIES_RESET" => $PROPERTIES_RESET,
		"PROPERTIES_TRANSLATE_XML_ID" => $PROPERTIES_TRANSLATE_XML_ID,
		"PROPERTIES_SET_DEFAULT_VALUES" => $PROPERTIES_SET_DEFAULT_VALUES,
		"PROPERTIES_SKIP_DOWNLOAD_URL_UPDATE" => $PROPERTIES_SKIP_DOWNLOAD_URL_UPDATE,
		"PROPERTIES_USE_MULTITHREADED_DOWNLOADING" => $PROPERTIES_USE_MULTITHREADED_DOWNLOADING,
		"PROPERTIES_WHATERMARK" => $PROPERTIES_WHATERMARK,
		"PROPERTIES_INCREMENT_TO_MULTIPLE" => $PROPERTIES_INCREMENT_TO_MULTIPLE,
		"PROPERTIES_ADD_LIST_ENUM" => $PROPERTIES_ADD_LIST_ENUM,
		"PROPERTIES_ADD_DIRECTORY_ENTITY" => $PROPERTIES_ADD_DIRECTORY_ENTITY,
		"PROPERTIES_ADD_LINK_ELEMENT" => $PROPERTIES_ADD_LINK_ELEMENT,
		"IMPORT_CATALOG_PRODUCTS" => $IMPORT_CATALOG_PRODUCTS,
		"PRODUCTS_ADD" => $PRODUCTS_ADD,
		"PRODUCTS_UPDATE" => $PRODUCTS_UPDATE,
		"PRODUCTS_PARAMS" => base64_encode(serialize(Array(
			"PRODUCTS_VAT_ID" => $PRODUCTS_VAT_ID, 
			"PRODUCTS_VAT_INCLUDED" => $PRODUCTS_VAT_INCLUDED, 
			"PRODUCTS_MEASURE" => $PRODUCTS_MEASURE, 
			"PRODUCTS_QUANTITY" => $PRODUCTS_QUANTITY, 
			"PRODUCTS_QUANTITY_TRACE" => $PRODUCTS_QUANTITY_TRACE, 
			"PRODUCTS_USE_STORE" => $PRODUCTS_USE_STORE,
			"PRODUCTS_SUBSCRIBE" => $PRODUCTS_SUBSCRIBE
		))),
		"PRODUCTS_DEFAULT_CURRENCY" => $PRODUCTS_DEFAULT_CURRENCY,
		"IMPORT_CATALOG_PRODUCT_OFFERS" => $IMPORT_CATALOG_PRODUCT_OFFERS,
		"OFFERS_ADD" => $OFFERS_ADD,
		"OFFERS_UPDATE" => $OFFERS_UPDATE,
		"OFFERS_SET_NAME_FROM_ELEMENT" => $OFFERS_SET_NAME_FROM_ELEMENT,
		"OFFERS_OUT_ACTION" => $OFFERS_OUT_ACTION,
		"OFFERS_OUT_ACTION_FILTER" => $OFFERS_OUT_ACTION_FILTER,
		"OFFERS_IN_ACTION" => $OFFERS_IN_ACTION,
		"OFFERS_IN_ACTION_FILTER" => $OFFERS_IN_ACTION_FILTER,
		"IMPORT_CATALOG_PRICES" => $IMPORT_CATALOG_PRICES,
		"PRICES_ADD" => $PRICES_ADD,
		"PRICES_UPDATE" => $PRICES_UPDATE,
		"PRICES_DEFAULT_CURRENCY" => $PRICES_DEFAULT_CURRENCY,
		"PRICES_EXTRA_VALUE" => $PRICES_EXTRA_VALUE,
		"IMPORT_CATALOG_STORE_AMOUNT" => $IMPORT_CATALOG_STORE_AMOUNT,
		"STORE_AMOUNT_ADD" => $STORE_AMOUNT_ADD,
		"STORE_AMOUNT_UPDATE" => $STORE_AMOUNT_UPDATE,
		"IMPORT_HIGHLOAD_BLOCK_ENTITIES" => $IMPORT_HIGHLOAD_BLOCK_ENTITIES,
		"ENTITIES_ADD" => $ENTITIES_ADD,
		"ENTITIES_UPDATE" => $ENTITIES_UPDATE,
		"ENTITIES_TRANSLATE_XML_ID" => $ENTITIES_TRANSLATE_XML_ID,
		"ENTITIES_ADD_LIST_ENUM" => $ENTITIES_ADD_LIST_ENUM,
		"PATH_TO_IMAGES" => $PATH_TO_IMAGES,
		"CLEAR_IMAGES_DIR" => $CLEAR_IMAGES_DIR,
		"CLEAR_UPLOAD_TMP_DIR" => $CLEAR_UPLOAD_TMP_DIR,
		"PATH_TO_IMAGES_URL" => $PATH_TO_IMAGES_URL,
		"RESIZE_IMAGE" => $RESIZE_IMAGE,
		"PATH_TO_FILES" => $PATH_TO_FILES,
		"CLEAR_FILES_DIR" => $CLEAR_FILES_DIR,
		"PATH_TO_FILES_URL" => $PATH_TO_FILES_URL,
		"CURL_TIMEOUT" => $CURL_TIMEOUT,
		"CURL_FOLLOWLOCATION" => $CURL_FOLLOWLOCATION,
		"VALIDATE_URL" => $VALIDATE_URL,
		"RAW_URL_DECODE" => $RAW_URL_DECODE,
		"PROCESSINGS_AFTER_FINISH" => base64_encode(serialize($PROCESSINGS_AFTER_FINISH)),
		"AGENT_INTERVAL" => $AGENT_INTERVAL,
		"AGENT_INTERVAL_URL" => $AGENT_INTERVAL_URL,
		"AGENT_TIME_URL" => $AGENT_TIME_URL,
		"AGENT_ONETIME_EXECUTION" => $AGENT_ONETIME_EXECUTION,
		"CSV_DELIMITER" => $CSV_DELIMITER,
		"XLS_SHEET" => $XLS_SHEET,
		"CSV_XLS_NAME_LINE" => $CSV_XLS_NAME_LINE,
		"CSV_XLS_START_LINE" => $CSV_XLS_START_LINE,
		"CSV_XLS_FINISH_LINE" => $CSV_XLS_FINISH_LINE,
		"CSV_XLS_MAX_DEPTH_LEVEL" => $CSV_XLS_MAX_DEPTH_LEVEL,
		"XML_ENTITY_GROUP" => $XML_ENTITY_GROUP,
		"XML_ENTITY" => $XML_ENTITY,
		"XML_USE_ENTITY_NAME" => $XML_USE_ENTITY_NAME,
		"XML_PARSE_PARAMS_TO_PROPERTIES" => $XML_PARSE_PARAMS_TO_PROPERTIES,
		"XML_ENTITY_PARAM" => $XML_ENTITY_PARAM,
		"XML_SEARCH_BY_PROPERTY_CODE_FIRST" => $XML_SEARCH_BY_PROPERTY_CODE_FIRST,
		"XML_SEARCH_ONLY_ACTIVE_PROPERTY" => $XML_SEARCH_ONLY_ACTIVE_PROPERTY,
		"XML_ADD_PROPERTIES_FOR_PARAMS" => $XML_ADD_PROPERTIES_FOR_PARAMS,
		"XML_PROPERTY_LIST_PAGE_SHOW" => $XML_PROPERTY_LIST_PAGE_SHOW,
		"XML_PROPERTY_DETAIL_PAGE_SHOW" => $XML_PROPERTY_DETAIL_PAGE_SHOW,
		"XML_PROPERTY_YAMARKET_COMMON" => $XML_PROPERTY_YAMARKET_COMMON,
		"XML_PROPERTY_YAMARKET_TURBO" => $XML_PROPERTY_YAMARKET_TURBO,
		"JSON_CACHE_TIME" => $JSON_CACHE_TIME,
		"JSON_NEXT_URL" => $JSON_NEXT_URL,
		"JSON_GET_DATA" => base64_encode(serialize($JSON_GET_DATA)),
		"DEBUG_EVENTS" => $DEBUG_EVENTS,
		"DEBUG_IMAGES" => $DEBUG_IMAGES,
		"DEBUG_FILES" => $DEBUG_FILES,
		"DEBUG_URL" => $DEBUG_URL,
		"DEBUG_IMPORT_SECTION" => $DEBUG_IMPORT_SECTION,
		"DEBUG_IMPORT_ELEMENTS" => $DEBUG_IMPORT_ELEMENTS,
		"DEBUG_IMPORT_PROPERTIES" => $DEBUG_IMPORT_PROPERTIES,
		"DEBUG_IMPORT_PRODUCTS" => $DEBUG_IMPORT_PRODUCTS,
		"DEBUG_IMPORT_OFFERS" => $DEBUG_IMPORT_OFFERS,
		"DEBUG_IMPORT_PRICES" => $DEBUG_IMPORT_PRICES,
		"DEBUG_IMPORT_STORE_AMOUNT" => $DEBUG_IMPORT_STORE_AMOUNT,
		"DEBUG_IMPORT_ENTITIES" => $DEBUG_IMPORT_ENTITIES,
		"NOTE" => $NOTE,
	);
	
	if($ID > 0)
	{
		$res = $element->Update($ID, $arFields);
	}
	else
	{
		$ID = $element->Add($arFields);
		$res = ($ID > 0);
	}
	
	if($ID > 0 && $COPY_ID > 0)
	{
		$CConnection = new CWebprostorImportPlanConnections;
		$connections = Array();
		$connectionsRes = $CConnection->GetList(Array("SORT" => "ASC"), Array("PLAN_ID" => $COPY_ID));
		while($connectionArr = $connectionsRes->GetNext())
		{
			$connections["ID"][] = "copy_".$connectionArr["ID"];
			
			$connections["ENTITY"][] = $connectionArr["ENTITY"];
			$connections["ENTITY_ATTRIBUTE"][] = $connectionArr["ENTITY_ATTRIBUTE"];
			$connections["ACTIVE"][] = $connectionArr["ACTIVE"];
			$connections["NAME"][] = $connectionArr["NAME"];
			$connections["SORT"][] = $connectionArr["SORT"];
			$connections["IBLOCK_SECTION_FIELD"][] = $connectionArr["IBLOCK_SECTION_FIELD"];
			$connections["IBLOCK_SECTION_DEPTH_LEVEL"][] = $connectionArr["IBLOCK_SECTION_DEPTH_LEVEL"];
			$connections["IBLOCK_SECTION_PARENT_FIELD"][] = $connectionArr["IBLOCK_SECTION_PARENT_FIELD"];
			$connections["IBLOCK_ELEMENT_FIELD"][] = $connectionArr["IBLOCK_ELEMENT_FIELD"];
			$connections["IBLOCK_ELEMENT_PROPERTY"][] = $connectionArr["IBLOCK_ELEMENT_PROPERTY"];
			$connections["IBLOCK_ELEMENT_PROPERTY_E"][] = $connectionArr["IBLOCK_ELEMENT_PROPERTY_E"];
			$connections["IBLOCK_ELEMENT_PROPERTY_G"][] = $connectionArr["IBLOCK_ELEMENT_PROPERTY_G"];
			$connections["IBLOCK_ELEMENT_PROPERTY_M"][] = $connectionArr["IBLOCK_ELEMENT_PROPERTY_M"];
			$connections["IBLOCK_ELEMENT_OFFER_FIELD"][] = $connectionArr["IBLOCK_ELEMENT_OFFER_FIELD"];
			$connections["IBLOCK_ELEMENT_OFFER_PROPERTY"][] = $connectionArr["IBLOCK_ELEMENT_OFFER_PROPERTY"];
			$connections["CATALOG_PRODUCT_FIELD"][] = $connectionArr["CATALOG_PRODUCT_FIELD"];
			$connections["CATALOG_PRODUCT_OFFER_FIELD"][] = $connectionArr["CATALOG_PRODUCT_OFFER_FIELD"];
			$connections["CATALOG_PRODUCT_PRICE"][] = $connectionArr["CATALOG_PRODUCT_PRICE"];
			$connections["CATALOG_PRODUCT_STORE_AMOUNT"][] = $connectionArr["CATALOG_PRODUCT_STORE_AMOUNT"];
			$connections["HIGHLOAD_BLOCK_ENTITY_FIELD"][] = $connectionArr["HIGHLOAD_BLOCK_ENTITY_FIELD"];
			$connections["IS_IMAGE"][] = $connectionArr["IS_IMAGE"];
			$connections["IS_FILE"][] = $connectionArr["IS_FILE"];
			$connections["IS_URL"][] = $connectionArr["IS_URL"];
			$connections["IS_REQUIRED"][] = $connectionArr["IS_REQUIRED"];
			$connections["USE_IN_SEARCH"][] = $connectionArr["USE_IN_SEARCH"];
			$connections["USE_IN_CODE"][] = $connectionArr["USE_IN_CODE"];
			$connections["PROCESSING_TYPES"][] = unserialize(base64_decode($connectionArr["PROCESSING_TYPES"]));
			
		}
		if(count($connections)>0)
		{
			$resCopyConnections = $CConnection->UpdatePlanConnections($ID, $connections);
		}
	}
	
	if($res)
	{
		if ($apply != "")
			LocalRedirect("/bitrix/admin/webprostor.import_plan_edit.php?ID=".$ID."&mess=ok&lang=".LANG."&".$tabControl->ActiveTabParam());
		else
			LocalRedirect("/bitrix/admin/webprostor.import_plans.php?lang=".LANG);
	}
	else
	{
		if($e = $APPLICATION->GetException())
		{
			$message = new CAdminMessage(Loc::getMessage("MESSAGE_SAVE_ERROR"), $e);
			$strError = $e;
		}
		$bVarsFromForm = true;
	}
}
elseif($_GET['import_status'] == 'error')
{
	$message = new CAdminMessage(Loc::getMessage("MESSAGE_IMPORT_ERROR"));
	$strError = Loc::getMessage("MESSAGE_IMPORT_ERROR");
}

$str_SHOW_IN_MANUALLY = "Y";
$str_NAME = "";
$str_SORT = "500";
$str_IMPORT_FORMAT = "CSV";
$str_ITEMS_PER_ROUND = "100";
$str_RESIZE_IMAGE = "Y";
$str_CURL_TIMEOUT = "5";
$str_AGENT_INTERVAL = "600";
$str_AGENT_INTERVAL_URL = "86400";
$str_XLS_SHEET = 0;
$str_IMPORT_FILE_DELETE = "N";
$str_IMPORT_FILE_CHECK = "Y";

if($ID>0 || $COPY_ID>0)
{
    $cData = new CWebprostorImportPlan;
	if($ID>0)
		$element = $cData->GetById($ID);
	else
		$element = $cData->GetById($COPY_ID);
	
	if(!$element->ExtractFields("str_"))
		$ID=0;
}

if($str_AGENT_ID>0)
{
	$agentArr = CAgent::GetList(
		false,
		[
			'ID' => $str_AGENT_ID,
			'MODULE_ID' => $module_id,
		]
	)->Fetch();
	if(is_array($agentArr))
	{
		if($agentArr['ACTIVE'] == 'N')
			$strWarning .= Loc::getMessage("WEBPROSTOR_IMPORT_AGENT_DEACTIVTED");
	}
	else
		$strWarning .= Loc::getMessage("WEBPROSTOR_IMPORT_AGENT_DELETED");
}
elseif($str_ACTIVE == 'Y')
	$strWarning .= Loc::getMessage("WEBPROSTOR_IMPORT_AGENT_DELETED");
	
if(intVal($str_IBLOCK_ID)>0)
{
	if(Loader::includeModule("catalog"))
	{
		$catalogSKU = CCatalogSKU::GetInfoByProductIBlock($str_IBLOCK_ID);
		
		$str_OFFER_IBLOCK_ID = $catalogSKU["IBLOCK_ID"];
	}
}

if($bVarsFromForm)
	$DB->InitTableVarsForEdit($sTableID, "", "str_");

$APPLICATION->SetTitle(($ID>0? Loc::getMessage("ELEMENT_EDIT_TITLE").': '.$str_NAME : Loc::getMessage("ELEMENT_ADD_TITLE")));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

$aMenu = array(
	array(
		"TEXT"=>Loc::getMessage("ELEMENTS_LIST"),
		"TITLE"=>Loc::getMessage("ELEMENTS_LIST_TITLE"),
		"LINK"=>"webprostor.import_plans.php?lang=".LANG,
		"ICON"=>"btn_list",
	)
);

$aMenu[] = array("SEPARATOR"=>"Y");

if($ID>0)
{

	$aMenu[] = array(
		"TEXT"  => Loc::getMessage("EDIT_CONNECTIONS_BTN"),
		"TITLE" => Loc::getMessage("EDIT_CONNECTIONS_BTN_TITLE"),
		"LINK"  => "webprostor.import_plan_connections_edit.php?ID=".$ID."&lang=".LANG,
		"ICON"  => "btn_green",
	);

	if($moduleAccessLevel=="W")
	{
		$aMenu[] = array(
			"TEXT"  => Loc::getMessage("IMPORT_CONNECTIONS"),
			"TITLE" => Loc::getMessage("IMPORT_CONNECTIONS_TITLE"),
			"LINK"  => "webprostor.import_connections_import.php?lang=".LANGUAGE_ID."&PLAN_ID=".$ID,
		);
	}

	$aMenuActions = [];
	if($moduleAccessLevel >= "T")
	{
		$aMenuActions[] = array(
			"TEXT"  => Loc::getMessage("START_IMPORT"),
			"LINK"  => "webprostor.import_manually.php?PLAN_ID=".$ID."&LOAD_FILES=Y&lang=".LANG."&".bitrix_sessid_get(),
			"LINK_PARAM"  => "target=\"_blank\"",
		);
	}
	$aMenuActions[] = array(
		"TEXT"  => Loc::getMessage("OPEN_LOGS"),
		"LINK"  => "webprostor.import_logs.php?PLAN_ID=".$ID."&find_plan_id=".$ID."&apply_filter=Y&lang=".LANG,
		"LINK_PARAM"  => "target=\"_blank\"",
	);
	if($moduleAccessLevel=="W")
	{
		$aMenuActions[] = array(
			"TEXT"  => Loc::getMessage("ELEMENT_ADD_BTN"),
			"TITLE" => Loc::getMessage("ELEMENT_ADD_BTN_TITLE"),
			"LINK"  => "webprostor.import_plan_edit.php?lang=".LANG,
			"ICON"  => "edit",
		);
		$aMenuActions[] = array(
			"TEXT"  => Loc::getMessage("COPY_BTN"),
			"TITLE" => Loc::getMessage("COPY_BTN_TITLE"),
			"LINK"  => "webprostor.import_plan_edit.php?COPY_ID={$ID}&lang=".LANG,
			"ICON"  => "copy",
		);
	}
	$aMenuActions[] = array(
		"TEXT"  => Loc::getMessage("EXPORT_BTN"),
		"TITLE" => Loc::getMessage("EXPORT_BTN_TITLE"),
		"LINK"  => "javascript:(new BX.CDialog({
					width: 310,
					height: 170,
					resizable: false,
					title: '".Loc::getMessage('EXPORT_TITLE')."',
					buttons: [BX.CAdminDialog.btnSave, BX.CAdminDialog.btnCancel],
					content: '<form action=\"".CUtil::JSEscape($GLOBALS['APPLICATION']->GetCurPageParam('', array('action')))."\" method=\"post\" enctype=\"multipart/form-data\">"
								.bitrix_sessid_post()
								."<input type=\"hidden\" name=\"action\" value=\"plan_export\" />"
								."<input type=\"hidden\" name=\"ID\" value=\"".$ID."\" />"
								.Loc::getMessage('EXPORT_PLAN_SETTINGS_NOTE')
								."<input type=\"checkbox\" name=\"plan_connections\" id=\"plan_connections\" value=\"Y\" checked=\"checked\" /><label for=\"plan_connections\">".Loc::getMessage('EXPORT_PLAN_CONNECTIONS')."</label><br />"
								."<input type=\"checkbox\" name=\"plan_properties\" id=\"plan_properties\" value=\"Y\" checked=\"checked\" /><label for=\"plan_properties\">".Loc::getMessage('EXPORT_PLAN_PROPERTIES')."</label><br />"
								."<input type=\"checkbox\" name=\"plan_processing_settings\" id=\"plan_processing_settings\" value=\"Y\" checked=\"checked\" /><label for=\"plan_processing_settings\">".Loc::getMessage('EXPORT_PLAN_PROCESSING_SETTINGS')."</label><br />"
							."</form>'
				})).Show()",
		"ICON"  => "export",
	);
	if($moduleAccessLevel=="W")
	{
		$aMenuActions[] = array(
			"TEXT"  => Loc::getMessage("ELEMENT_DELETE_BTN"),
			"TITLE" => Loc::getMessage("ELEMENT_DELETE_BTN_TITLE"),
			"LINK"  => "javascript:if(confirm('".Loc::getMessage("ELEMENT_DELETE_BTN_MESSAGE")."')) ".
			  "window.location='webprostor.import_plans.php?action=delete&ID[]=".CUtil::JSEscape($ID)."&lang=".LANGUAGE_ID."&".bitrix_sessid_get()."';",
			"ICON"  => "delete",
		);
	}
	
	$aMenu[] = array(
		"TEXT"  => Loc::getMessage("BTN_ACTIONS"),
		"TITLE" => Loc::getMessage("BTN_ACTIONS_TITLE"),
		"ICON"  => "btn_new",
		"MENU"  => $aMenuActions,
	);
}
else
{
	$importAction = "javascript:(new BX.CDialog({
					width: 410,
					height: 210,
					resizable: true,
					title: '".Loc::getMessage('IMPORT_TITLE')."',
					content: '<form action=\"".CUtil::JSEscape($GLOBALS['APPLICATION']->GetCurPageParam('', array('action')))."\" method=\"post\" enctype=\"multipart/form-data\">"
								.bitrix_sessid_post()
								."<input type=\"hidden\" name=\"action\" value=\"plan_import\" />"
								."<input type=\"file\" name=\"xml_file\" id=\"xml_file\" /><br/><br/>"
								."<label for=\"import_iblock_id\">".Loc::getMessage('FIELDS_IBLOCK_ID')."</label><br /><select id=\"import_iblock_id\" name=\"import_iblock_id\" class=\"select-search\">";
	$importAction .= "<option value=\"0\">".htmlspecialcharsbx(Loc::getMessage("FIELDS_BLOCK_ID_NO"))."</option>";
	$db_iblock_type = CIBlockType::GetList();
	while($ar_iblock_type = $db_iblock_type->Fetch())
	{
		if($arIBType = CIBlockType::GetByIDLang($ar_iblock_type["ID"], LANG))
		{
			$resIblocks = CIBlock::GetList(Array("NAME"=>"ASC"), Array("TYPE"=>$ar_iblock_type["ID"], "MIN_PERMISSION" => "W"));
			if(intVal($resIblocks->SelectedRowsCount())>0)
			{
				$importAction .= "<optgroup label=\"".htmlspecialcharsbx($arIBType["NAME"])." [".$ar_iblock_type["ID"]."]\">";
				while($iblock = $resIblocks->Fetch()){
					$importAction .= "<option value=\"".$iblock["ID"]."\">".htmlspecialcharsbx($iblock["NAME"])." [".$iblock["ID"]."]</option>";
				}
				$importAction .= "</optgroup>";
			}
		}
	}
	/*$resIblocks = CIBlock::GetList(Array("NAME"=>"ASC"), Array());
	$importAction .= "<option value=\"0\">".htmlspecialcharsbx(Loc::getMessage("FIELDS_BLOCK_ID_NO"))."</option>";
	while($iblock = $resIblocks->Fetch()){
		$importAction .= "<option value=\"".$iblock["ID"]."\">".htmlspecialcharsbx($iblock["NAME"])." [".$iblock["ID"]."]</option>";
	}*/
	$importAction .= "</select><br/><br/>"
								."<label for=\"import_highload_block\">".Loc::getMessage('FIELDS_HIGHLOAD_BLOCK')."</label><br /><select id=\"import_highload_block\" name=\"import_highload_block\" class=\"select-search\">";
	$rsData = \Bitrix\Highloadblock\HighloadBlockTable::getList();
	$importAction .= "<option value=\"0\">".htmlspecialcharsbx(Loc::getMessage("FIELDS_BLOCK_ID_NO"))."</option>";
	while($hldata = $rsData->Fetch())
	{
		$importAction .= "<option value=\"".$hldata["ID"]."\">".htmlspecialcharsbx($hldata["NAME"])." [".$hldata["TABLE_NAME"]."]</option>";
	}
	$importAction .= "</select><br/><br/>"
								."<center><input type=\"submit\" onclick=\"if(document.getElementById(\'xml_file\').value.length == \'0\' || (document.getElementById(\'import_iblock_id\').value == \'0\' && document.getElementById(\'import_highload_block\').value == \'0\')) return false;\" value=\"".Loc::getMessage('IMPORT_SUBMIT')."\" /></center>"
							."</form>'
				})).Show()";
	$aMenu[] = array(
		"TEXT"  => Loc::getMessage("IMPORT_BTN"),
		"TITLE" => Loc::getMessage("IMPORT_BTN_TITLE"),
		"LINK"  => $importAction,
		"ICON"  => "btn_copy",
	);
}

if($_REQUEST["mess"] == "ok" && $ID>0)
{
	CWebprostorCoreFunctions::showAlertBegin('success', 'info');
	echo Loc::getMessage("ELEMENT_SAVED");
	CWebprostorCoreFunctions::showAlertEnd();
}
if($strWarning)
{
	CWebprostorCoreFunctions::showAlertBegin('warning', 'warning');
	echo $strWarning;
	CWebprostorCoreFunctions::showAlertEnd();
}
if($message)
{
	//echo $message->Show();
	
	CWebprostorCoreFunctions::showAlertBegin('danger', 'danger');
	echo $strError;
	CWebprostorCoreFunctions::showAlertEnd();
}
elseif($element->LAST_ERROR != "")
{
	CWebprostorCoreFunctions::showAlertBegin('danger', 'danger');
	echo $element->LAST_ERROR;
	CWebprostorCoreFunctions::showAlertEnd();
}
	
if ($moduleAccessLevel < 'W' && $moduleAccessLevel <> 'D')
{
	echo BeginNote();
	echo Loc::getMessage('MESSAGE_NOT_SAVE_ACCESS');
	echo EndNote();
}

$context = new CAdminContextMenu($aMenu);

$context->Show();
?>
<form method="POST" name="PLAN_EDIT" id="plan_edit" Action="<?echo $APPLICATION->GetCurPage()?>" ENCTYPE="multipart/form-data">
<?echo bitrix_sessid_post();?>
<?
$tabControl->Begin();
?>
<?
$tabControl->BeginNextTab();

/*$IMPORT_FORMATS = Array(
	"CSV" => Loc::getMessage("FIELDS_IMPORT_FORMAT_CSV").' [CSV]',
	"XML" => Loc::getMessage("FIELDS_IMPORT_FORMAT_XML").' [XML]',
	"XLS" => Loc::getMessage("FIELDS_IMPORT_FORMAT_XLS").' [XLS]',
	"XLSX" => Loc::getMessage("FIELDS_IMPORT_FORMAT_XLSX").' [XLSX]',
	"ODS" => Loc::getMessage("FIELDS_IMPORT_FORMAT_ODS").' [ODS]',
	"XODS" => Loc::getMessage("FIELDS_IMPORT_FORMAT_XODS").' [XODS]',
);*/
$IMPORT_FILE_SHARSETS = Array(
	"UTF-8" => Loc::getMessage("FIELDS_IMPORT_FILE_SHARSET_UTF_8"),
	"WINDOWS-1251" => Loc::getMessage("FIELDS_IMPORT_FILE_SHARSET_WINDOWS_1251"),
);

$arFormFields = Array();
$arFormFields["MAIN"]["LABEL"] = Loc::getMessage("GROUP_MAIN");

if($ID>0)
{
	$arFormFields["MAIN"]["ITEMS"][] = Array(
		"CODE" => "OBJECT_ID",
		"REQUIRED" => "Y",
		"LABEL" => "ID",
		"VALUE" => $str_ID,
	);
}
$arFormFields["MAIN"]["ITEMS"][] = Array(
	"CODE" => "SHOW_IN_MENU",
	"DEFAULT" => "N",
	"REQUIRED" => "N",
	"TYPE" => "CHECKBOX",
	"LABEL" => Loc::getMessage("FIELDS_SHOW_IN_MENU"),
	"VALUE" => ($COPY_ID)?'N':$str_SHOW_IN_MENU,
);
$arFormFields["MAIN"]["ITEMS"][] = Array(
	"CODE" => "SHOW_IN_MANUALLY",
	"DEFAULT" => "Y",
	"REQUIRED" => "N",
	"TYPE" => "CHECKBOX",
	"LABEL" => Loc::getMessage("FIELDS_SHOW_IN_MANUALLY"),
	"DESCRIPTION" => Loc::getMessage("FIELDS_SHOW_IN_MANUALLY_NOTE"),
	"VALUE" => $str_SHOW_IN_MANUALLY,
);
$arFormFields["MAIN"]["ITEMS"][] = Array(
	"CODE" => "NAME",
	"REQUIRED" => "Y",
	"TYPE" => "TEXT",
	"LABEL" => Loc::getMessage("FIELDS_NAME"),
	"VALUE" => $str_NAME,
	"PARAMS" => Array(
		"SIZE" => 30,
		"MAXLENGTH" => 255,
	),
);
$arFormFields["MAIN"]["ITEMS"][] = Array(
	"CODE" => "SORT",
	"REQUIRED" => "N",
	"TYPE" => "NUMBER",
	"LABEL" => Loc::getMessage("FIELDS_SORT"),
	"VALUE" => $str_SORT,
	"PARAMS" => Array(
		"SIZE" => 8,
		"MAXLENGTH" => 11,
		"MIN" => 0,
	),
);
$arFormFields["MAIN"]["ITEMS"][] = Array(
	"CODE" => "IMPORT_FORMAT",
	"REQUIRED" => "Y",
	"TYPE" => "SELECT",
	"LABEL" => Loc::getMessage("FIELDS_IMPORT_FORMAT"),
	"VALUE" => $str_IMPORT_FORMAT,
	//"ITEMS" => $IMPORT_FORMATS,
	"ITEMS" => CWebprostorImportPlan::getFormats(),
);
$arFormFields["MAIN"]["ITEMS"][] = Array(
	"CODE" => "ITEMS_PER_ROUND",
	"REQUIRED" => "Y",
	"TYPE" => "NUMBER",
	"LABEL" => Loc::getMessage("FIELDS_ITEMS_PER_ROUND"),
	"VALUE" => $str_ITEMS_PER_ROUND,
	"PARAMS" => Array(
		"SIZE" => 8,
		"MAXLENGTH" => 11,
		"MIN" => 1,
	),
);

$arFormFields["IMPORT_TYPE"]["LABEL"] = Loc::getMessage("GROUP_IMPORT_TYPE");
$arFormFields["IMPORT_TYPE"]["ITEMS"][] = Array(
	"CODE" => "IBLOCK_ID",
	"REQUIRED" => "N",
	"TYPE" => "IBLOCK_TREE",
	"LABEL" => Loc::getMessage("FIELDS_IBLOCK_ID"),
	"DESCRIPTION" => Loc::getMessage("FIELDS_IBLOCK_ID_NOTE"),
	"VALUE" => $str_IBLOCK_ID,
	"PARAMS" => Array(
		"MIN_PERMISSION" => "W",
		"ADD_ZERO" => "Y",
		"ZERO_LABEL" => Loc::getMessage("FIELDS_BLOCK_ID_NO"),
	),
);
$arFormFields["IMPORT_TYPE"]["ITEMS"][] = Array(
	"CODE" => "HIGHLOAD_BLOCK",
	"REQUIRED" => "N",
	"TYPE" => "HIGHLOAD_BLOCK",
	"LABEL" => Loc::getMessage("FIELDS_HIGHLOAD_BLOCK"),
	"DESCRIPTION" => Loc::getMessage("FIELDS_HIGHLOAD_BLOCK_NOTE"),
	"VALUE" => $str_HIGHLOAD_BLOCK,
	"PARAMS" => Array(
		"ADD_ZERO" => "Y",
		"ZERO_LABEL" => Loc::getMessage("FIELDS_BLOCK_ID_NO"),
	),
);

$arFormFields["FILES"]["LABEL"] = Loc::getMessage("GROUP_FILES");
$arFormFields["FILES"]["ITEMS"][] = Array(
	"CODE" => "IMPORT_FILE",
	"REQUIRED" => "Y",
	"TYPE" => "FILE_DIALOG",
	"LABEL" => Loc::getMessage("FIELDS_IMPORT_FILE"),
	"DESCRIPTION" => Loc::getMessage("FIELDS_IMPORT_FILE_DESCRIPTION"),
	"VALUE" => $str_IMPORT_FILE,
	"PARAMS" => Array(
		"SIZE" => 30,
		"MAXLENGTH" => 255,
		"FORM_NAME" => "PLAN_EDIT",
		"SELECT" => "F",
		"ALLOW_UPLOAD" => "Y",
		"ALLOW_FILE_FORMATS" => "csv,xml,yml,xls,xlsx,ods,xods",
		"PATH" => "/".COption::GetOptionString("main", "upload_dir", "upload"),
	)
);
$arFormFields["FILES"]["ITEMS"][] = Array(
	"CODE" => "IMPORT_FILE_SHARSET",
	"REQUIRED" => "N",
	"TYPE" => "SELECT",
	"LABEL" => Loc::getMessage("FIELDS_IMPORT_FILE_SHARSET"),
	"DESCRIPTION" => Loc::getMessage("FIELDS_IMPORT_FILE_SHARSET_NOTE"),
	"VALUE" => $str_IMPORT_FILE_SHARSET,
	"ITEMS" => $IMPORT_FILE_SHARSETS,
);
$arFormFields["FILES"]["ITEMS"][] = Array(
	"CODE" => "IMPORT_FILE_DELETE",
	"REQUIRED" => "N",
	"TYPE" => "CHECKBOX",
	"LABEL" => Loc::getMessage("FIELDS_IMPORT_FILE_DELETE"),
	"VALUE" => $str_IMPORT_FILE_DELETE,
);
$arFormFields["FILES"]["ITEMS"][] = Array(
	"CODE" => "IMPORT_FILE_CHECK",
	"REQUIRED" => "N",
	"TYPE" => "CHECKBOX",
	"LABEL" => Loc::getMessage("FIELDS_IMPORT_FILE_CHECK"),
	"DESCRIPTION" => Loc::getMessage("FIELDS_IMPORT_FILE_CHECK_NOTE"),
	"VALUE" => $str_IMPORT_FILE_CHECK,
);
$arFormFields["FILES"]["ITEMS"][] = Array(
	"CODE" => "RESIZE_IMAGE",
	"DEFAULT" => "N",
	"REQUIRED" => "N",
	"TYPE" => "CHECKBOX",
	"LABEL" => Loc::getMessage("FIELDS_RESIZE_IMAGE"),
	"DESCRIPTION" => Loc::getMessage("FIELDS_RESIZE_IMAGE_NOTE"),
	"VALUE" => $str_RESIZE_IMAGE,
);
$arFormFields["FILES"]["ITEMS"][] = Array(
	"CODE" => "PATH_TO_IMAGES",
	"REQUIRED" => "N",
	"TYPE" => "FILE_DIALOG",
	"LABEL" => Loc::getMessage("FIELDS_PATH_TO_IMAGES"),
	"DESCRIPTION" => Loc::getMessage("FIELDS_PATH_TO_IMAGES_NOTE"),
	"VALUE" => $str_PATH_TO_IMAGES,
	"PARAMS" => Array(
		"SIZE" => 30,
		"MAXLENGTH" => 255,
		"FORM_NAME" => "PLAN_EDIT",
		"SELECT" => "D",
		"ALLOW_UPLOAD" => "N",
		"PATH" => "/".COption::GetOptionString("main", "upload_dir", "upload")."/webprostor.import/data/images/",
		"PLACEHOLDER" => "/upload/webprostor.import/data/images/",
	)
);
$arFormFields["FILES"]["ITEMS"][] = Array(
	"CODE" => "CLEAR_IMAGES_DIR",
	"REQUIRED" => "N",
	"DEFAULT" => "N",
	"TYPE" => "CHECKBOX",
	"LABEL" => Loc::getMessage("FIELDS_CLEAR_IMAGES_DIR"),
	"VALUE" => $str_CLEAR_IMAGES_DIR,
);
/*$arFormFields["FILES"]["ITEMS"][] = Array(
	"CODE" => "CLEAR_UPLOAD_TMP_DIR",
	"REQUIRED" => "N",
	"DEFAULT" => "N",
	"TYPE" => "CHECKBOX",
	"LABEL" => Loc::getMessage("FIELDS_CLEAR_UPLOAD_TMP_DIR"),
	"DESCRIPTION" => Loc::getMessage("FIELDS_CLEAR_UPLOAD_TMP_DIR_NOTE", ["#UPLOAD_DIR#" => COption::GetOptionString("main", "upload_dir", "upload")]),
	"VALUE" => $str_CLEAR_UPLOAD_TMP_DIR,
);*/
$arFormFields["FILES"]["ITEMS"][] = Array(
	"CODE" => "PATH_TO_FILES",
	"REQUIRED" => "N",
	"TYPE" => "FILE_DIALOG",
	"LABEL" => Loc::getMessage("FIELDS_PATH_TO_FILES"),
	"DESCRIPTION" => Loc::getMessage("FIELDS_PATH_TO_FILES_NOTE"),
	"VALUE" => $str_PATH_TO_FILES,
	"PARAMS" => Array(
		"SIZE" => 30,
		"MAXLENGTH" => 255,
		"FORM_NAME" => "PLAN_EDIT",
		"SELECT" => "D",
		"ALLOW_UPLOAD" => "N",
		"PATH" => "/".COption::GetOptionString("main", "upload_dir", "upload")."/webprostor.import/data/files/",
		"PLACEHOLDER" => "/upload/webprostor.import/data/files/",
	)
);
$arFormFields["FILES"]["ITEMS"][] = Array(
	"CODE" => "CLEAR_FILES_DIR",
	"REQUIRED" => "N",
	"DEFAULT" => "N",
	"TYPE" => "CHECKBOX",
	"LABEL" => Loc::getMessage("FIELDS_CLEAR_FILES_DIR"),
	"VALUE" => $str_CLEAR_FILES_DIR,
);

$arFormFields["FILES_URL"]["LABEL"] = Loc::getMessage("GROUP_FILES_URL");
$arFormFields["FILES_URL"]["ITEMS"][] = Array(
	"CODE" => "IMPORT_FILE_URL",
	"REQUIRED" => "N",
	"TYPE" => "TEXT",
	"LABEL" => Loc::getMessage("FIELDS_IMPORT_FILE_URL"),
	"DESCRIPTION" => Loc::getMessage("FIELDS_IMPORT_FILE_URL_NOTE"),
	"VALUE" => $str_IMPORT_FILE_URL,
	"PARAMS" => Array(
		"SIZE" => 60,
		"MAXLENGTH" => 255,
		"PLACEHOLDER" => "https://example.com/import.".strtolower($str_IMPORT_FORMAT),
	),
);
$arFormFields["FILES_URL"]["ITEMS"][] = Array(
	"CODE" => "IMPORT_FILE_URL_MACROS",
	"TYPE" => "NOTE",
	"LABEL" => Loc::getMessage("FIELDS_IMPORT_FILE_URL_MACROS"),
	"VALUE" => Loc::getMessage(
		"FIELDS_IMPORT_FILE_URL_MACROS_NOTE",
		[
			'#ITEMS_PER_ROUND_EXAMPLE#' => $str_ITEMS_PER_ROUND,
			'#DATE_1_EXAMPLE#' => date('d.m.y'),
			'#DATE_2_EXAMPLE#' => date('d.m.Y'),
		]
	),
	"PARAMS" => Array(
		"TYPE" => "warning",
		"ICON" => false,
	),
);
$arFormFields["FILES_URL"]["ITEMS"][] = Array(
	"CODE" => "IMPORT_FILE_REPLACE",
	"REQUIRED" => "N",
	"DEFAULT" => "N",
	"TYPE" => "CHECKBOX",
	"LABEL" => Loc::getMessage("FIELDS_IMPORT_FILE_REPLACE"),
	"DESCRIPTION" => Loc::getMessage("FIELDS_IMPORT_FILE_REPLACE_NOTE"),
	"VALUE" => $str_IMPORT_FILE_REPLACE,
);
$arFormFields["FILES_URL"]["ITEMS"][] = Array(
	"CODE" => "URL_LOGIN",
	"REQUIRED" => "N",
	"TYPE" => "TEXT",
	"LABEL" => Loc::getMessage("FIELDS_URL_LOGIN"),
	"VALUE" => $str_URL_LOGIN,
	"PARAMS" => Array(
		"SIZE" => 30,
		"MAXLENGTH" => 255,
		"AUTOCOMPLETE" => false,
	),
);
$arFormFields["FILES_URL"]["ITEMS"][] = Array(
	"CODE" => "URL_PASSWORD",
	"REQUIRED" => "N",
	"TYPE" => "PASSWORD",
	"LABEL" => Loc::getMessage("FIELDS_URL_PASSWORD"),
	"VALUE" => $str_URL_PASSWORD,
	"PARAMS" => Array(
		"SIZE" => 30,
		"MAXLENGTH" => 255,
	),
);
$arFormFields["FILES_URL"]["ITEMS"][] = Array(
	"CODE" => "PATH_TO_IMAGES_URL",
	"REQUIRED" => "N",
	"TYPE" => "TEXT",
	"LABEL" => Loc::getMessage("FIELDS_PATH_TO_IMAGES_URL"),
	"DESCRIPTION" => Loc::getMessage("FIELDS_PATH_TO_IMAGES_URL_NOTE"),
	"VALUE" => $str_PATH_TO_IMAGES_URL,
	"PARAMS" => Array(
		"SIZE" => 60,
		"MAXLENGTH" => 255,
		"PLACEHOLDER" => "https://example.com/images.zip",
	),
);
$arFormFields["FILES_URL"]["ITEMS"][] = Array(
	"CODE" => "PATH_TO_FILES_URL",
	"REQUIRED" => "N",
	"TYPE" => "TEXT",
	"LABEL" => Loc::getMessage("FIELDS_PATH_TO_FILES_URL"),
	"DESCRIPTION" => Loc::getMessage("FIELDS_PATH_TO_FILES_URL_NOTE"),
	"VALUE" => $str_PATH_TO_FILES_URL,
	"PARAMS" => Array(
		"SIZE" => 60,
		"MAXLENGTH" => 255,
		"PLACEHOLDER" => "https://example.com/files.zip",
	),
);
$arFormFields["FILES_URL"]["ITEMS"][] = Array(
	"CODE" => "CURL_TIMEOUT",
	"REQUIRED" => "N",
	"TYPE" => "NUMBER",
	"LABEL" => Loc::getMessage("FIELDS_CURL_TIMEOUT"),
	"VALUE" => $str_CURL_TIMEOUT,
	"PARAMS" => Array(
		"SIZE" => 30,
		"MIN" => 1,
	),
);
$arFormFields["FILES_URL"]["ITEMS"][] = Array(
	"CODE" => "CURL_FOLLOWLOCATION",
	"REQUIRED" => "N",
	"TYPE" => "CHECKBOX",
	"LABEL" => Loc::getMessage("FIELDS_CURL_FOLLOWLOCATION"),
	"DESCRIPTION" => Loc::getMessage("FIELDS_CURL_FOLLOWLOCATION_DESCRIPTION"),
	"VALUE" => $str_CURL_FOLLOWLOCATION,
);
$arFormFields["FILES_URL"]["ITEMS"][] = Array(
	"CODE" => "VALIDATE_URL",
	"REQUIRED" => "N",
	"TYPE" => "CHECKBOX",
	"LABEL" => Loc::getMessage("FIELDS_VALIDATE_URL"),
	"DESCRIPTION" => Loc::getMessage("FIELDS_VALIDATE_URL_DESCRIPTION"),
	"VALUE" => $str_VALIDATE_URL,
);
$arFormFields["FILES_URL"]["ITEMS"][] = Array(
	"CODE" => "RAW_URL_DECODE",
	"REQUIRED" => "N",
	"TYPE" => "CHECKBOX",
	"LABEL" => Loc::getMessage("FIELDS_RAW_URL_DECODE"),
	"DESCRIPTION" => Loc::getMessage("FIELDS_RAW_URL_DECODE_DESCRIPTION"),
	"VALUE" => $str_RAW_URL_DECODE,
);
$arFormFields["FILES_URL"]["ITEMS"][] = Array(
	"CODE" => "AGENT_INTERVAL_URL",
	"REQUIRED" => "N",
	"TYPE" => "NUMBER",
	"LABEL" => Loc::getMessage("FIELDS_AGENT_INTERVAL_URL"),
	"VALUE" => $str_AGENT_INTERVAL_URL,
	"PARAMS" => Array(
		"SIZE" => 30,
		"MAXLENGTH" => 11,
		"MIN" => 60,
	),
);
$arFormFields["FILES_URL"]["ITEMS"][] = Array(
	"CODE" => "AGENT_TIME_URL",
	"REQUIRED" => "N",
	"TYPE" => "TIME",
	"LABEL" => Loc::getMessage("FIELDS_AGENT_TIME_URL"),
	"DESCRIPTION" => Loc::getMessage("FIELDS_AGENT_TIME_URL_NOTE"),
	"VALUE" => $str_AGENT_TIME_URL,
);

$arFormFields["POST_PROCESSING"]["LABEL"] = Loc::getMessage("GROUP_POST_PROCESSING");
if(!Loader::includeModule('webprostor.massprocessing'))
{
	$arFormFields["POST_PROCESSING"]["ITEMS"][] = Array(
		"CODE" => "WEBPROSTOR_MASSPROCESSING_NOTE",
		"TYPE" => "NOTE",
		"VALUE" => Loc::getMessage(
			"WEBPROSTOR_MASSPROCESSING_NOTE",
			[
				"#LANGUAGE_ID#" => LANGUAGE_ID,
			]
		),
		"PARAMS" => Array(
			"TYPE" => "primary",
			"ICON" => false,
		),
	);
}
else
{
	$massProcessingsArr = [];
	$processingClass = new \Webprostor\MassProcessing\Processing();
	$processingRes = $processingClass->GetList();
	while($processingArr = $processingRes->GetNext())
	{
		$massProcessingsArr[$processingArr['ID']] = htmlspecialcharsbx($processingArr['NAME']).' ['.$processingArr['ID'].']';
	}
	if($str_PROCESSINGS_AFTER_FINISH && is_string($str_PROCESSINGS_AFTER_FINISH))
	{
		$processingsAfterFinishValue = unserialize(base64_decode($str_PROCESSINGS_AFTER_FINISH));
		if(!is_array($processingsAfterFinishValue))
			$processingsAfterFinishValue = Array();
	}
	elseif(!is_array($str_PROCESSINGS_AFTER_FINISH))
	{
		$processingsAfterFinishValue = array();
	}
	$arFormFields["POST_PROCESSING"]["ITEMS"][] = Array(
		"CODE" => "PROCESSINGS_AFTER_FINISH[]",
		"TYPE" => "SELECT",
		"LABEL" => Loc::getMessage("FIELDS_PROCESSINGS_AFTER_FINISH"),
		"VALUE" => $processingsAfterFinishValue ? $processingsAfterFinishValue : $str_PROCESSINGS_AFTER_FINISH,
		"ITEMS" => $massProcessingsArr,
		"PARAMS" => [
			"MULTIPLE" => "Y",
		],
	);
}

$arFormFields["AGENT"]["LABEL"] = Loc::getMessage("GROUP_AGENT");
$arFormFields["AGENT"]["ITEMS"][] = Array(
	"CODE" => "ACTIVE",
	"REQUIRED" => "N",
	"DEFAULT" => "N",
	"TYPE" => "CHECKBOX",
	"LABEL" => Loc::getMessage("FIELDS_ACTIVE"),
	"DESCRIPTION" => Loc::getMessage("FIELDS_ACTIVE_NOTE"),
	"VALUE" => ($COPY_ID > 0)?'N':$str_ACTIVE,
);

if($str_AGENT_ID && !$COPY_ID)
{
	$arFormFields["AGENT"]["ITEMS"][] = Array(
		"CODE" => "AGENT_ID",
		"REQUIRED" => "N",
		"TYPE" => "BUTTON",
		"LABEL" => Loc::getMessage("FIELDS_AGENT_ID"),
		"VALUE" => 'agent_list.php?set_filter=Y&adm_filter_applied=0&find='.$str_AGENT_ID.'&find_type=id&find_module_id='.$module_id.'&lang='.LANG,
		"PARAMS" => Array(
			"TARGET" => "_blank",
			"TEXT" => Loc::getMessage("FIELDS_AGENT_OPEN"),
		),
	);
}
else
{
	$arFormFields["AGENT"]["ITEMS"][] = Array(
		"CODE" => "AGENT_ID",
		"REQUIRED" => "N",
		"LABEL" => Loc::getMessage("FIELDS_AGENT_ID"),
		"VALUE" => Loc::getMessage("FIELDS_AGENT_NO"),
		//"DESCRIPTION" => Loc::getMessage("FIELDS_AGENT_NO_NOTE"),
	);
}

$arFormFields["AGENT"]["ITEMS"][] = Array(
	"CODE" => "AGENT_INTERVAL",
	"REQUIRED" => "N",
	"TYPE" => "NUMBER",
	"LABEL" => Loc::getMessage("FIELDS_AGENT_INTERVAL"),
	"DESCRIPTION" => Loc::getMessage("FIELDS_AGENT_INTERVAL_NOTE"),
	"VALUE" => $str_AGENT_INTERVAL,
	"PARAMS" => Array(
		"SIZE" => 30,
		"MAXLENGTH" => 11,
		"MIN" => 60,
	),
);
$arFormFields["AGENT"]["ITEMS"][] = Array(
	"CODE" => "AGENT_ONETIME_EXECUTION",
	"REQUIRED" => "N",
	"TYPE" => "CHECKBOX",
	"LABEL" => Loc::getMessage("FIELDS_AGENT_ONETIME_EXECUTION"),
	"DESCRIPTION" => Loc::getMessage("FIELDS_AGENT_ONETIME_EXECUTION_NOTE"),
	"VALUE" => $str_AGENT_ONETIME_EXECUTION,
);
if($ID>0)
{
	$arFormFields["AGENT"]["ITEMS"][] = Array(
		"CODE" => "OBJECT_LAST_IMPORT_DATE",
		"LABEL" => "ID",
		"LABEL" => Loc::getMessage("FIELDS_LAST_IMPORT_DATE"),
		"DESCRIPTION" => Loc::getMessage("FIELDS_LAST_IMPORT_DATE_NOTE"),
		"VALUE" => $str_LAST_IMPORT_DATE,
	);
	
	$arFormFields["AGENT"]["ITEMS"][] = Array(
		"CODE" => "OBJECT_LAST_STEP_IMPORT_DATE",
		"LABEL" => "ID",
		"LABEL" => Loc::getMessage("FIELDS_LAST_STEP_IMPORT_DATE"),
		"DESCRIPTION" => Loc::getMessage("FIELDS_LAST_STEP_IMPORT_DATE_NOTE"),
		"VALUE" => $str_LAST_STEP_IMPORT_DATE,
	);
	
	$arFormFields["AGENT"]["ITEMS"][] = Array(
		"CODE" => "OBJECT_LAST_FINISH_IMPORT_DATE",
		"LABEL" => "ID",
		"LABEL" => Loc::getMessage("FIELDS_LAST_FINISH_IMPORT_DATE"),
		"DESCRIPTION" => Loc::getMessage("FIELDS_LAST_FINISH_IMPORT_DATE_NOTE"),
		"VALUE" => $str_LAST_FINISH_IMPORT_DATE,
	);
}

if($ID>0)
{
	$arFormFields["CRON"]["LABEL"] = Loc::getMessage("GROUP_CRON");
	
	$cron_file = $_SERVER['DOCUMENT_ROOT'].'/bitrix/tmp/'.$module_id.'/'.$ID.'_cron.txt';
	if(file_exists($cron_file))
	{
		$cronTemp = file_get_contents($cron_file);
		$cronData = unserialize($cronTemp);
		if(is_array($cronData) && array_key_exists('CURRENT', $cronData) && array_key_exists('TOTAL', $cronData))
		{
			$arFormFields["CRON"]["ITEMS"][] = Array(
				"CODE" => "CRON_PROGRESS",
				"LABEL" => "ID",
				"LABEL" => Loc::getMessage("CRON_PROGRESS"),
				"VALUE" => Loc::getMessage("CRON_PROGRESS_VALUE", ['#CURRENT#' => number_format($cronData['CURRENT'], 0, '', ' '), '#TOTAL#' => number_format($cronData['TOTAL'], 0, '', ' ')]),
			);
		}
	}

	$arFormFields["CRON"]["ITEMS"][] = Array(
		"CODE" => "CRON_NOTIFICATION",
		"TYPE" => "NOTE",
		"VALUE" => Loc::getMessage(
			"CRON_NOTIFICATION",
			[
				"#DOCUMENT_ROOT#" => $_SERVER["DOCUMENT_ROOT"],
				"#PLAN_ID#" => $ID,
				"#AGENT_INTERVAL#" => $str_AGENT_INTERVAL,
				"#AGENT_INTERVAL_URL#" => $str_AGENT_INTERVAL_URL,
				"#CRON_REGISTER_ARGC_ARGV#" => (ini_get('register_argc_argv') != true?Loc::getMessage("CRON_REGISTER_ARGC_ARGV"):""),
			]
		),
		"PARAMS" => Array(
			"TYPE" => "warning",
			"ICON" => false,
		),
	);
}

$arFormFields["NOTE"]["LABEL"] = Loc::getMessage("GROUP_NOTE");
$arFormFields["NOTE"]["ITEMS"][] = Array(
	"CODE" => "NOTE",
	"TYPE" => "EDITOR",
	"VALUE" => $str_NOTE
);

CWebprostorCoreFunctions::ShowFormFields($arFormFields);

$tabControl->BeginNextTab();

$arFormFields = [];

$DEFAULT_ACTIVE = [
	"Y" => Loc::getMessage("FIELDS_ACTION_A"),
	"N" => Loc::getMessage("FIELDS_ACTION_H")
];
$OFFER_OUT_ACTION = [
	"N" => Loc::getMessage("FIELDS_ACTION_N"),
	"H" => Loc::getMessage("FIELDS_ACTION_H"),
	"Q" => Loc::getMessage("FIELDS_ACTION_Q"),
	"D" => Loc::getMessage("FIELDS_ACTION_D")
];
$DESCRIPTIONS_TYPES = [
	"def" => Loc::getMessage("FIELDS_D"),
	"text" => "text",
	"html" => "html",
];
if(Loader::includeModule('catalog'))
{
	$OUT_ACTION = $OFFER_OUT_ACTION;
}
else
{
	$OUT_ACTION = [
		"N" => Loc::getMessage("FIELDS_ACTION_N"),
		"H" => Loc::getMessage("FIELDS_ACTION_H"),
		"D" => Loc::getMessage("FIELDS_ACTION_D")
	];
}
$SECTIONS_OUT_ACTION = [
	"N" => Loc::getMessage("FIELDS_ACTION_N"),
	"H" => Loc::getMessage("FIELDS_ACTION_H"),
	"D" => Loc::getMessage("FIELDS_ACTION_D")
];
$IN_ACTION = [
	"N" => Loc::getMessage("FIELDS_ACTION_N"),
	"A" => Loc::getMessage("FIELDS_ACTION_A")
];
$PRODUCT_DEFAULT_FIELDS = [
	"D" => Loc::getMessage("FIELDS_D"),
	"Y" => Loc::getMessage("FIELDS_Y"),
	"N" => Loc::getMessage("FIELDS_N")
];

if(Loader::includeModule("iblock") && $str_IBLOCK_ID > 0)
{
	$arFormFields["SECTIONS"]["LABEL"] = Loc::getMessage("GROUP_IMPORT_SECTIONS");
	$arFormFields["SECTIONS"]["ITEMS"][] = [
		"CODE" => "IMPORT_IBLOCK_SECTIONS",
		"TYPE" => "CHECKBOX",
		//"REQUIRED" => "Y",
		"LABEL" => Loc::getMessage("FIELDS_IMPORT_IBLOCK_SECTIONS"),
		"VALUE" => $str_IMPORT_IBLOCK_SECTIONS,
	];
	$arFormFields["SECTIONS"]["ITEMS"][] = [
		"CODE" => "SECTIONS_UPDATE_SEARCH",
		"TYPE" => "CHECKBOX",
		"REQUIRED" => "N",
		"LABEL" => Loc::getMessage("FIELDS_SECTIONS_UPDATE_SEARCH"),
		"DESCRIPTION" => Loc::getMessage("FIELDS_SECTIONS_UPDATE_SEARCH_DESCRIPTION"),
		"VALUE" => $str_SECTIONS_UPDATE_SEARCH,
	];
	$arFormFields["SECTIONS"]["ITEMS"][] = [
		"CODE" => "SECTIONS_ADD",
		"TYPE" => "CHECKBOX",
		"REQUIRED" => "N",
		"LABEL" => Loc::getMessage("FIELDS_SECTIONS_ADD"),
		"VALUE" => $str_SECTIONS_ADD,
	];
	if(\CIBlock::isUniqueSectionCode($str_IBLOCK_ID))
	{
		$arFormFields["SECTIONS"]["ITEMS"][] = [
			"TYPE" => "NOTE",
			"VALUE" => Loc::getMessage("FIELDS_SECTIONS_ADD_NOTIFICATION"),
			"PARAMS" => [
				'TYPE' => 'warning',
				'ICON' => 'warning'
			],
		];
	}
	$arFormFields["SECTIONS"]["ITEMS"][] = [
		"CODE" => "SECTIONS_DEFAULT_ACTIVE",
		"TYPE" => "SELECT",
		"LABEL" => Loc::getMessage("FIELDS_SECTIONS_DEFAULT_ACTIVE"),
		"VALUE" => $str_SECTIONS_DEFAULT_ACTIVE,
		"ITEMS" => $DEFAULT_ACTIVE,
	];
	$arFormFields["SECTIONS"]["ITEMS"][] = [
		"CODE" => "SECTIONS_DEFAULT_SECTION_ID",
		"TYPE" => "SECTION",
		"LABEL" => Loc::getMessage("FIELDS_SECTIONS_DEFAULT_SECTION_ID"),
		"DESCRIPTION" => Loc::getMessage("FIELDS_SECTIONS_DEFAULT_SECTION_ID_NOTE"),
		"VALUE" => $str_SECTIONS_DEFAULT_SECTION_ID,
		"PARAMS" => [
			'IBLOCK_ID' => $str_IBLOCK_ID
		],
	];
	$arFormFields["SECTIONS"]["ITEMS"][] = [
		"CODE" => "SECTIONS_UPDATE",
		"TYPE" => "CHECKBOX",
		"REQUIRED" => "N",
		"LABEL" => Loc::getMessage("FIELDS_SECTIONS_UPDATE"),
		"VALUE" => $str_SECTIONS_UPDATE,
	];
	$arFormFields["SECTIONS"]["ITEMS"][] = [
		"CODE" => "SECTIONS_OUT_ACTION",
		"TYPE" => "SELECT",
		"LABEL" => Loc::getMessage("FIELDS_SECTIONS_OUT_ACTION"),
		"VALUE" => $str_SECTIONS_OUT_ACTION,
		"ITEMS" => $SECTIONS_OUT_ACTION,
	];
	$arFormFields["SECTIONS"]["ITEMS"][] = [
		"CODE" => "SECTIONS_OUT_ACTION_FILTER",
		"ID" => "sections_out_action_filter",
		"TYPE" => "WINDOW_DIALOG",
		"LABEL" => Loc::getMessage("FIELDS_SECTIONS_OUT_ACTION_FILTER"),
		"VALUE" => $str_SECTIONS_OUT_ACTION_FILTER,
		"DATA" => [
			"IBLOCK_ID" => $str_IBLOCK_ID,
			"OBJECTS" => ["SECTIONS"],
			"CODE" => "SECTIONS_OUT_ACTION_FILTER",
		],
		"PARAMS" => [
			"AJAX_URL" => "/bitrix/admin/".$module_id."_window_filter.php",
			"MODAL" => "Y",
			"DRAGGABLE" => "Y",
			"WIDTH" => 600,
			"HEIGHT" => 500,
		]
	];
	$arFormFields["SECTIONS"]["ITEMS"][] = [
		"CODE" => "SECTIONS_IN_ACTION",
		"TYPE" => "SELECT",
		"LABEL" => Loc::getMessage("FIELDS_SECTIONS_IN_ACTION"),
		"VALUE" => $str_SECTIONS_IN_ACTION,
		"ITEMS" => $IN_ACTION,
	];
	$arFormFields["SECTIONS"]["ITEMS"][] = [
		"CODE" => "SECTIONS_IN_ACTION_FILTER",
		"ID" => "sections_in_action_filter",
		"TYPE" => "WINDOW_DIALOG",
		"LABEL" => Loc::getMessage("FIELDS_SECTIONS_IN_ACTION_FILTER"),
		"VALUE" => $str_SECTIONS_IN_ACTION_FILTER,
		"DATA" => [
			"IBLOCK_ID" => $str_IBLOCK_ID,
			"OBJECTS" => ["SECTIONS"],
			"CODE" => "SECTIONS_IN_ACTION_FILTER",
		],
		"PARAMS" => [
			"AJAX_URL" => "/bitrix/admin/".$module_id."_window_filter.php",
			"MODAL" => "Y",
			"DRAGGABLE" => "Y",
			"WIDTH" => 600,
			"HEIGHT" => 500,
		]
	];
	
	$arFormFields["ELEMENTS"]["LABEL"] = Loc::getMessage("GROUP_IMPORT_ELEMENTS");
	$arFormFields["ELEMENTS"]["ITEMS"][] = [
		"CODE" => "IMPORT_IBLOCK_ELEMENTS",
		"TYPE" => "CHECKBOX",
		//"REQUIRED" => "Y",
		"LABEL" => Loc::getMessage("FIELDS_IMPORT_IBLOCK_ELEMENTS"),
		"VALUE" => $str_IMPORT_IBLOCK_ELEMENTS,
	];
	$arFormFields["ELEMENTS"]["ITEMS"][] = [
		"CODE" => "ELEMENTS_PREFILTER",
		"ID" => "elements_prefilter",
		"TYPE" => "WINDOW_DIALOG",
		"LABEL" => Loc::getMessage("FIELDS_ELEMENTS_PREFILTER"),
		"VALUE" => $str_ELEMENTS_PREFILTER,
		"DATA" => [
			"IBLOCK_ID" => $str_IBLOCK_ID,
			"OBJECTS" => ["ELEMENTS", "PROPERTIES", "PRODUCTS", "PRICES"],
			"CODE" => "ELEMENTS_PREFILTER",
		],
		"PARAMS" => [
			"AJAX_URL" => "/bitrix/admin/".$module_id."_window_filter.php",
			"MODAL" => "Y",
			"DRAGGABLE" => "Y",
			"WIDTH" => 600,
			"HEIGHT" => 500,
		]
	];
	$arFormFields["ELEMENTS"]["ITEMS"][] = [
		"CODE" => "ELEMENTS_SKIP_IMPORT_WITHOUT_SECTION",
		"TYPE" => "CHECKBOX",
		"REQUIRED" => "N",
		"LABEL" => Loc::getMessage("FIELDS_ELEMENTS_SKIP_IMPORT_WITHOUT_SECTION"),
		"VALUE" => $str_ELEMENTS_SKIP_IMPORT_WITHOUT_SECTION,
	];
	$arFormFields["ELEMENTS"]["ITEMS"][] = [
		"CODE" => "ELEMENTS_2_STEP_SEARCH",
		"TYPE" => "CHECKBOX",
		"REQUIRED" => "N",
		"LABEL" => Loc::getMessage("FIELDS_ELEMENTS_2_STEP_SEARCH"),
		"DESCRIPTION" => Loc::getMessage("FIELDS_ELEMENTS_2_STEP_SEARCH_DESCRIPTION"),
		"VALUE" => $str_ELEMENTS_2_STEP_SEARCH,
	];
	$arFormFields["ELEMENTS"]["ITEMS"][] = [
		"CODE" => "ELEMENTS_UPDATE_SEARCH",
		"TYPE" => "CHECKBOX",
		"REQUIRED" => "N",
		"LABEL" => Loc::getMessage("FIELDS_ELEMENTS_UPDATE_SEARCH"),
		"DESCRIPTION" => Loc::getMessage("FIELDS_ELEMENTS_UPDATE_SEARCH_DESCRIPTION"),
		"VALUE" => $str_ELEMENTS_UPDATE_SEARCH,
	];
	$arFormFields["ELEMENTS"]["ITEMS"][] = [
		"CODE" => "ELEMENTS_ADD",
		"TYPE" => "CHECKBOX",
		"REQUIRED" => "N",
		"LABEL" => Loc::getMessage("FIELDS_ELEMENTS_ADD"),
		"VALUE" => $str_ELEMENTS_ADD,
	];
	if(\CIBlock::isUniqueElementCode($str_IBLOCK_ID))
	{
		$arFormFields["ELEMENTS"]["ITEMS"][] = [
			"TYPE" => "NOTE",
			"VALUE" => Loc::getMessage("FIELDS_ELEMENTS_ADD_NOTIFICATION"),
			"PARAMS" => [
				'TYPE' => 'warning',
				'ICON' => 'warning'
			],
		];
	}
	$arFormFields["ELEMENTS"]["ITEMS"][] = [
		"CODE" => "ELEMENTS_DEFAULT_ACTIVE",
		"TYPE" => "SELECT",
		"LABEL" => Loc::getMessage("FIELDS_ELEMENTS_DEFAULT_ACTIVE"),
		"VALUE" => $str_ELEMENTS_DEFAULT_ACTIVE,
		"ITEMS" => $DEFAULT_ACTIVE,
	];
	$arFormFields["ELEMENTS"]["ITEMS"][] = [
		"CODE" => "ELEMENTS_DEFAULT_SECTION_ID",
		"TYPE" => "SECTION",
		"LABEL" => Loc::getMessage("FIELDS_ELEMENTS_DEFAULT_SECTION_ID"),
		"DESCRIPTION" => Loc::getMessage("FIELDS_ELEMENTS_DEFAULT_SECTION_ID_NOTE"),
		"VALUE" => $str_ELEMENTS_DEFAULT_SECTION_ID,
		"PARAMS" => [
			'IBLOCK_ID' => $str_IBLOCK_ID
		],
	];
	$arFormFields["ELEMENTS"]["ITEMS"][] = [
		"CODE" => "ELEMENTS_DEFAULT_DESCRIPTION_TYPE",
		"TYPE" => "SELECT",
		"LABEL" => Loc::getMessage("FIELDS_ELEMENTS_DEFAULT_DESCRIPTION_TYPE"),
		"VALUE" => $str_ELEMENTS_DEFAULT_DESCRIPTION_TYPE,
		"ITEMS" => $DESCRIPTIONS_TYPES,
	];
	$arFormFields["ELEMENTS"]["ITEMS"][] = [
		"CODE" => "ELEMENTS_UPDATE",
		"TYPE" => "CHECKBOX",
		"LABEL" => Loc::getMessage("FIELDS_ELEMENTS_UPDATE"),
		"VALUE" => $str_ELEMENTS_UPDATE,
	];
	$arFormFields["ELEMENTS"]["ITEMS"][] = [
		"CODE" => "ELEMENTS_DONT_UPDATE_IMAGES_IF_EXISTS",
		"TYPE" => "CHECKBOX",
		"LABEL" => Loc::getMessage("FIELDS_ELEMENTS_DONT_UPDATE_IMAGES_IF_EXISTS"),
		"DESCRIPTION" => Loc::getMessage("FIELDS_ELEMENTS_DONT_UPDATE_IMAGES_IF_EXISTS_NOTE"),
		"VALUE" => $str_ELEMENTS_DONT_UPDATE_IMAGES_IF_EXISTS,
	];
	$arFormFields["ELEMENTS"]["ITEMS"][] = [
		"CODE" => "ELEMENTS_OUT_ACTION",
		"TYPE" => "SELECT",
		"LABEL" => Loc::getMessage("FIELDS_ELEMENTS_OUT_ACTION"),
		"VALUE" => $str_ELEMENTS_OUT_ACTION,
		"ITEMS" => $OUT_ACTION,
	];
	$arFormFields["ELEMENTS"]["ITEMS"][] = [
		"CODE" => "ELEMENTS_OUT_ACTION_FILTER",
		"ID" => "elements_out_action_filter",
		"TYPE" => "WINDOW_DIALOG",
		"LABEL" => Loc::getMessage("FIELDS_ELEMENTS_OUT_ACTION_FILTER"),
		"VALUE" => $str_ELEMENTS_OUT_ACTION_FILTER,
		"DATA" => [
			"IBLOCK_ID" => $str_IBLOCK_ID,
			"OBJECTS" => ["ELEMENTS", "PROPERTIES", "PRODUCTS", "PRICES", "STORES"],
			"CODE" => "ELEMENTS_OUT_ACTION_FILTER",
		],
		"PARAMS" => [
			"AJAX_URL" => "/bitrix/admin/".$module_id."_window_filter.php",
			"MODAL" => "Y",
			"DRAGGABLE" => "Y",
			"WIDTH" => 600,
			"HEIGHT" => 500,
		]
	];
	$arFormFields["ELEMENTS"]["ITEMS"][] = [
		"CODE" => "ELEMENTS_IN_ACTION",
		"TYPE" => "SELECT",
		"LABEL" => Loc::getMessage("FIELDS_ELEMENTS_IN_ACTION"),
		//"DESCRIPTION" => Loc::getMessage("FIELDS_ELEMENTS_IN_ACTION_DESCRIPTION"),
		"VALUE" => $str_ELEMENTS_IN_ACTION,
		"ITEMS" => $IN_ACTION,
	];
	$arFormFields["ELEMENTS"]["ITEMS"][] = [
		"CODE" => "ELEMENTS_IN_ACTION_FILTER",
		"ID" => "elements_in_action_filter",
		"TYPE" => "WINDOW_DIALOG",
		"LABEL" => Loc::getMessage("FIELDS_ELEMENTS_IN_ACTION_FILTER"),
		"VALUE" => $str_ELEMENTS_IN_ACTION_FILTER,
		"DATA" => [
			"IBLOCK_ID" => $str_IBLOCK_ID,
			"OBJECTS" => ["ELEMENTS", "PROPERTIES", "PRODUCTS", "PRICES", "STORES"],
			"CODE" => "ELEMENTS_IN_ACTION_FILTER",
		],
		"PARAMS" => [
			"AJAX_URL" => "/bitrix/admin/".$module_id."_window_filter.php",
			"MODAL" => "Y",
			"DRAGGABLE" => "Y",
			"WIDTH" => 600,
			"HEIGHT" => 500,
		]
	];
	
	$arFormFields["PROPERTIES"]["LABEL"] = Loc::getMessage("GROUP_IMPORT_ELEMENT_PROPERTIES");
	$arFormFields["PROPERTIES"]["ITEMS"][] = [
		"CODE" => "IMPORT_IBLOCK_PROPERTIES",
		"TYPE" => "CHECKBOX",
		"REQUIRED" => "N",
		"LABEL" => Loc::getMessage("FIELDS_IMPORT_IBLOCK_PROPERTIES"),
		"VALUE" => $str_IMPORT_IBLOCK_PROPERTIES,
	];
	$arFormFields["PROPERTIES"]["ITEMS"][] = [
		"CODE" => "PROPERTIES_UPDATE",
		"TYPE" => "CHECKBOX",
		"REQUIRED" => "N",
		"LABEL" => Loc::getMessage("FIELDS_PROPERTIES_UPDATE"),
		"VALUE" => $str_PROPERTIES_UPDATE,
	];
	$arFormFields["PROPERTIES"]["ITEMS"][] = [
		"CODE" => "PROPERTIES_UPDATE_SKIP_IF_EVENT_SEARCH",
		"TYPE" => "CHECKBOX",
		"REQUIRED" => "N",
		"LABEL" => Loc::getMessage("FIELDS_PROPERTIES_UPDATE_SKIP_IF_EVENT_SEARCH"),
		"DESCRIPTION" => Loc::getMessage("FIELDS_PROPERTIES_UPDATE_SKIP_IF_EVENT_SEARCH_NOTE"),
		"VALUE" => $str_PROPERTIES_UPDATE_SKIP_IF_EVENT_SEARCH,
	];
	$arFormFields["PROPERTIES"]["ITEMS"][] = [
		"CODE" => "PROPERTIES_RESET",
		"TYPE" => "CHECKBOX",
		"REQUIRED" => "N",
		"LABEL" => Loc::getMessage("FIELDS_PROPERTIES_RESET"),
		"VALUE" => $str_PROPERTIES_RESET,
	];
	$arFormFields["PROPERTIES"]["ITEMS"][] = [
		"CODE" => "PROPERTIES_SET_DEFAULT_VALUES",
		"TYPE" => "CHECKBOX",
		"REQUIRED" => "N",
		"LABEL" => Loc::getMessage("FIELDS_PROPERTIES_SET_DEFAULT_VALUES"),
		"VALUE" => $str_PROPERTIES_SET_DEFAULT_VALUES,
	];
	$arFormFields["PROPERTIES"]["ITEMS"][] = [
		"CODE" => "PROPERTIES_INCREMENT_TO_MULTIPLE",
		"TYPE" => "CHECKBOX",
		"REQUIRED" => "N",
		"LABEL" => Loc::getMessage("FIELDS_PROPERTIES_INCREMENT_TO_MULTIPLE"),
		"DESCRIPTION" => Loc::getMessage("FIELDS_PROPERTIES_INCREMENT_TO_MULTIPLE_NOTE"),
		"VALUE" => $str_PROPERTIES_INCREMENT_TO_MULTIPLE,
	];
	$arFormFields["PROPERTIES"]["ITEMS"][] = [
		"CODE" => "PROPERTIES_TRANSLATE_XML_ID",
		"TYPE" => "CHECKBOX",
		"REQUIRED" => "N",
		"LABEL" => Loc::getMessage("FIELDS_PROPERTIES_TRANSLATE_XML_ID"),
		"VALUE" => $str_PROPERTIES_TRANSLATE_XML_ID,
	];
	$arFormFields["PROPERTIES"]["ITEMS"][] = [
		"CODE" => "PROPERTIES_ADD_LIST_ENUM",
		"TYPE" => "CHECKBOX",
		"REQUIRED" => "N",
		"LABEL" => Loc::getMessage("FIELDS_PROPERTIES_ADD_LIST_ENUM"),
		"VALUE" => $str_PROPERTIES_ADD_LIST_ENUM,
	];
	$arFormFields["PROPERTIES"]["ITEMS"][] = [
		"CODE" => "PROPERTIES_ADD_DIRECTORY_ENTITY",
		"TYPE" => "CHECKBOX",
		"REQUIRED" => "N",
		"LABEL" => Loc::getMessage("FIELDS_PROPERTIES_ADD_DIRECTORY_ENTITY"),
		"VALUE" => $str_PROPERTIES_ADD_DIRECTORY_ENTITY,
	];
	$arFormFields["PROPERTIES"]["ITEMS"][] = [
		"CODE" => "PROPERTIES_ADD_LINK_ELEMENT",
		"TYPE" => "CHECKBOX",
		"REQUIRED" => "N",
		"LABEL" => Loc::getMessage("FIELDS_PROPERTIES_ADD_LINK_ELEMENT"),
		"DESCRIPTION" => Loc::getMessage("FIELDS_PROPERTIES_ADD_LINK_ELEMENT_NOTE"),
		"VALUE" => $str_PROPERTIES_ADD_LINK_ELEMENT,
	];
	$arFormFields["PROPERTIES"]["ITEMS"][] = [
		"CODE" => "PROPERTIES_SKIP_DOWNLOAD_URL_UPDATE",
		"TYPE" => "CHECKBOX",
		"REQUIRED" => "N",
		"LABEL" => Loc::getMessage("FIELDS_PROPERTIES_SKIP_DOWNLOAD_URL_UPDATE"),
		"DESCRIPTION" => Loc::getMessage("FIELDS_PROPERTIES_SKIP_DOWNLOAD_URL_UPDATE_NOTE"),
		"VALUE" => $str_PROPERTIES_SKIP_DOWNLOAD_URL_UPDATE,
	];
	$arFormFields["PROPERTIES"]["ITEMS"][] = [
		"CODE" => "PROPERTIES_USE_MULTITHREADED_DOWNLOADING",
		"TYPE" => "CHECKBOX",
		"LABEL" => Loc::getMessage("FIELDS_PROPERTIES_USE_MULTITHREADED_DOWNLOADING"),
		"VALUE" => $str_PROPERTIES_USE_MULTITHREADED_DOWNLOADING,
	];
	$arFormFields["PROPERTIES"]["ITEMS"][] = [
		"CODE" => "PROPERTIES_WHATERMARK",
		"TYPE" => "SELECT",
		"LABEL" => Loc::getMessage("FIELDS_PROPERTIES_WHATERMARK"),
		"VALUE" => $str_PROPERTIES_WHATERMARK,
		"ITEMS" => [
			"N" => Loc::getMessage("FIELDS_NO_W"),
			"P" => Loc::getMessage("FIELDS_W_FROM_PREVIEW"),
			"D" => Loc::getMessage("FIELDS_W_FROM_DETAIL")
		],
	];
}

/*if(Loader::includeModule("currency"))
{
	$currencyArr = [];
	$lcur = CCurrency::GetList(($by="sort"), ($order="desc"), LANGUAGE_ID);
	while($lcur_res = $lcur->Fetch())
	{
		$currencyArr[$lcur_res["CURRENCY"]] = htmlspecialcharsbx($lcur_res["FULL_NAME"]).' ['.$lcur_res["CURRENCY"].']';
	}
}*/

if(Loader::includeModule("catalog") && $str_IBLOCK_ID > 0)
{
	$arFormFields["PROPERTIES"]["LABEL"] = Loc::getMessage("GROUP_IMPORT_ELEMENT_PROPERTIES2");
	if($str_PRODUCTS_PARAMS)
	{
		$productsParamsArr = unserialize(base64_decode($str_PRODUCTS_PARAMS));
		if(!is_array($productsParamsArr))
			$productsParamsArr = Array();
	}
	else
	{
		$productsParamsArr = array();
	}
	
	/*$CATALOG_VATS = [];
	$vatsRes = \CCatalogVat::GetListEx(['sort' => 'asc']);
	while($vatsArr = $vatsRes->Fetch())
	{
		$CATALOG_VATS[$vatsArr["ID"]] = htmlspecialcharsbx($vatsArr["NAME"]).($vatsArr["RATE"] != '' ? ' ['.$vatsArr["RATE"].']' : '');
	}*/
	
	$arFormFields["PRODUCTS"]["LABEL"] = Loc::getMessage("GROUP_IMPORT_PRODUCTS");
	$arFormFields["PRODUCTS"]["ITEMS"][] = [
		"CODE" => "IMPORT_CATALOG_PRODUCTS",
		"TYPE" => "CHECKBOX",
		"LABEL" => Loc::getMessage("FIELDS_IMPORT_CATALOG_PRODUCTS"),
		"VALUE" => $str_IMPORT_CATALOG_PRODUCTS,
	];
	$arFormFields["PRODUCTS"]["ITEMS"][] = [
		"CODE" => "PRODUCTS_ADD",
		"TYPE" => "CHECKBOX",
		"LABEL" => Loc::getMessage("FIELDS_PRODUCTS_ADD"),
		"VALUE" => $str_PRODUCTS_ADD,
	];
	$arFormFields["PRODUCTS"]["ITEMS"][] = [
		"CODE" => "PRODUCTS_UPDATE",
		"TYPE" => "CHECKBOX",
		"LABEL" => Loc::getMessage("FIELDS_PRODUCTS_UPDATE"),
		"DESCRIPTION" => Loc::getMessage("FIELDS_PRODUCTS_UPDATE_NOTE"),
		"VALUE" => $str_PRODUCTS_UPDATE,
	];
	$arFormFields["PRODUCTS"]["ITEMS"][] = [
		"CODE" => "PRODUCTS_VAT_ID",
		"TYPE" => "VAT",
		"LABEL" => Loc::getMessage("FIELDS_PRODUCTS_VAT_ID"),
		"VALUE" => $productsParamsArr["PRODUCTS_VAT_ID"],
		//"ITEMS" => $CATALOG_VATS,
	];
	$arFormFields["PRODUCTS"]["ITEMS"][] = [
		"CODE" => "PRODUCTS_VAT_INCLUDED",
		"TYPE" => "CHECKBOX",
		"LABEL" => Loc::getMessage("FIELDS_PRODUCTS_VAT_INCLUDED"),
		"VALUE" => $productsParamsArr["PRODUCTS_VAT_INCLUDED"],
	];
	$arFormFields["PRODUCTS"]["ITEMS"][] = [
		"CODE" => "PRODUCTS_MEASURE",
		"TYPE" => "MEASURE",
		"LABEL" => Loc::getMessage("FIELDS_PRODUCTS_MEASURE"),
		"VALUE" => $productsParamsArr["PRODUCTS_MEASURE"] ? $productsParamsArr["PRODUCTS_MEASURE"] : 5,
	];
	$arFormFields["PRODUCTS"]["ITEMS"][] = [
		"CODE" => "PRODUCTS_QUANTITY",
		"TYPE" => "TEXT",
		"LABEL" => Loc::getMessage("FIELDS_PRODUCTS_QUANTITY"),
		"DESCRIPTION" => Loc::getMessage("FIELDS_PRODUCTS_QUANTITY_DESCRIPTION"),
		"VALUE" => $productsParamsArr["PRODUCTS_QUANTITY"],
	];
	$arFormFields["PRODUCTS"]["ITEMS"][] = [
		"CODE" => "PRODUCTS_QUANTITY_TRACE",
		"TYPE" => "SELECT",
		"LABEL" => Loc::getMessage("FIELDS_PRODUCTS_QUANTITY_TRACE"),
		"VALUE" => $productsParamsArr["PRODUCTS_QUANTITY_TRACE"],
		"ITEMS" => $PRODUCT_DEFAULT_FIELDS,
	];
	$arFormFields["PRODUCTS"]["ITEMS"][] = [
		"CODE" => "PRODUCTS_USE_STORE",
		"TYPE" => "SELECT",
		"LABEL" => Loc::getMessage("FIELDS_PRODUCTS_USE_STORE"),
		"VALUE" => $productsParamsArr["PRODUCTS_USE_STORE"],
		"ITEMS" => $PRODUCT_DEFAULT_FIELDS,
	];
	$arFormFields["PRODUCTS"]["ITEMS"][] = [
		"CODE" => "PRODUCTS_SUBSCRIBE",
		"TYPE" => "SELECT",
		"LABEL" => Loc::getMessage("FIELDS_PRODUCTS_SUBSCRIBE"),
		"VALUE" => $productsParamsArr["PRODUCTS_SUBSCRIBE"],
		"ITEMS" => $PRODUCT_DEFAULT_FIELDS,
	];
	$arFormFields["PRODUCTS"]["ITEMS"][] = [
		"CODE" => "PRODUCTS_DEFAULT_CURRENCY",
		"TYPE" => "CURRENCY",
		//"ITEMS" => $currencyArr,
		"LABEL" => Loc::getMessage("FIELDS_PRODUCTS_DEFAULT_CURRENCY"),
		"DESCRIPTION" => Loc::getMessage("FIELDS_PRICES_DEFAULT_CURRENCY_NOTE"),
		"VALUE" => $str_PRODUCTS_DEFAULT_CURRENCY ? $str_PRODUCTS_DEFAULT_CURRENCY : 'RUB',
	];
	
	$arFormFields["OFFERS"]["LABEL"] = Loc::getMessage("GROUP_IMPORT_PRODUCT_OFFERS");
	$arFormFields["OFFERS"]["ITEMS"][] = [
		"CODE" => "IMPORT_CATALOG_PRODUCT_OFFERS",
		"TYPE" => "CHECKBOX",
		"LABEL" => Loc::getMessage("FIELDS_IMPORT_CATALOG_PRODUCT_OFFERS"),
		"VALUE" => $str_IMPORT_CATALOG_PRODUCT_OFFERS,
	];
	$arFormFields["OFFERS"]["ITEMS"][] = [
		"CODE" => "OFFERS_ADD",
		"TYPE" => "CHECKBOX",
		"LABEL" => Loc::getMessage("FIELDS_OFFERS_ADD"),
		"VALUE" => $str_OFFERS_ADD,
	];
	if($str_OFFER_IBLOCK_ID > 0 && \CIBlock::isUniqueElementCode($str_OFFER_IBLOCK_ID))
	{
		$arFormFields["OFFERS"]["ITEMS"][] = [
			"TYPE" => "NOTE",
			"VALUE" => Loc::getMessage("FIELDS_OFFERS_ADD_NOTIFICATION"),
			"PARAMS" => [
				'TYPE' => 'warning',
				'ICON' => 'warning'
			],
		];
	}
	$arFormFields["OFFERS"]["ITEMS"][] = [
		"CODE" => "OFFERS_UPDATE",
		"TYPE" => "CHECKBOX",
		"LABEL" => Loc::getMessage("FIELDS_OFFERS_UPDATE"),
		"VALUE" => $str_OFFERS_UPDATE,
	];
	$arFormFields["OFFERS"]["ITEMS"][] = [
		"CODE" => "OFFERS_SET_NAME_FROM_ELEMENT",
		"TYPE" => "CHECKBOX",
		"LABEL" => Loc::getMessage("FIELDS_OFFERS_SET_NAME_FROM_ELEMENT"),
		"VALUE" => $str_OFFERS_SET_NAME_FROM_ELEMENT,
	];
	$arFormFields["OFFERS"]["ITEMS"][] = [
		"CODE" => "OFFERS_OUT_ACTION",
		"TYPE" => "SELECT",
		"LABEL" => Loc::getMessage("FIELDS_OFFERS_OUT_ACTION"),
		"VALUE" => $str_OFFERS_OUT_ACTION,
		"ITEMS" => $OFFER_OUT_ACTION,
	];
	$arFormFields["OFFERS"]["ITEMS"][] = [
		"CODE" => "OFFERS_OUT_ACTION_FILTER",
		"ID" => "offers_out_action_filter",
		"TYPE" => "WINDOW_DIALOG",
		"LABEL" => Loc::getMessage("FIELDS_OFFERS_OUT_ACTION_FILTER"),
		"VALUE" => $str_OFFERS_OUT_ACTION_FILTER,
		"DATA" => [
			"IBLOCK_ID" => $str_OFFER_IBLOCK_ID,
			"OBJECTS" => ["ELEMENTS", "PROPERTIES", "PRODUCTS", "PRICES", "STORES"],
			"CODE" => "OFFERS_OUT_ACTION_FILTER",
		],
		"PARAMS" => [
			"AJAX_URL" => "/bitrix/admin/".$module_id."_window_filter.php",
			"MODAL" => "Y",
			"DRAGGABLE" => "Y",
			"WIDTH" => 600,
			"HEIGHT" => 500,
		]
	];
	$arFormFields["OFFERS"]["ITEMS"][] = [
		"CODE" => "OFFERS_IN_ACTION",
		"TYPE" => "SELECT",
		"LABEL" => Loc::getMessage("FIELDS_OFFERS_IN_ACTION"),
		"VALUE" => $str_OFFERS_IN_ACTION,
		"ITEMS" => $IN_ACTION,
	];
	$arFormFields["OFFERS"]["ITEMS"][] = [
		"CODE" => "OFFERS_IN_ACTION_FILTER",
		"ID" => "offers_in_action_filter",
		"TYPE" => "WINDOW_DIALOG",
		"LABEL" => Loc::getMessage("FIELDS_OFFERS_IN_ACTION_FILTER"),
		"VALUE" => $str_OFFERS_IN_ACTION_FILTER,
		"DATA" => [
			"IBLOCK_ID" => $str_OFFER_IBLOCK_ID,
			"OBJECTS" => ["ELEMENTS", "PROPERTIES", "PRODUCTS", "PRICES", "STORES"],
			"CODE" => "OFFERS_IN_ACTION_FILTER",
		],
		"PARAMS" => [
			"AJAX_URL" => "/bitrix/admin/".$module_id."_window_filter.php",
			"MODAL" => "Y",
			"DRAGGABLE" => "Y",
			"WIDTH" => 600,
			"HEIGHT" => 500,
		]
	];
	
	$arFormFields["PRICES"]["LABEL"] = Loc::getMessage("GROUP_IMPORT_PRICES");
	$arFormFields["PRICES"]["ITEMS"][] = [
		"CODE" => "IMPORT_CATALOG_PRICES",
		"TYPE" => "CHECKBOX",
		"LABEL" => Loc::getMessage("FIELDS_IMPORT_CATALOG_PRICES"),
		"VALUE" => $str_IMPORT_CATALOG_PRICES,
	];
	$arFormFields["PRICES"]["ITEMS"][] = [
		"CODE" => "PRICES_ADD",
		"TYPE" => "CHECKBOX",
		"LABEL" => Loc::getMessage("FIELDS_PRICES_ADD"),
		"VALUE" => $str_PRICES_ADD,
	];
	$arFormFields["PRICES"]["ITEMS"][] = [
		"CODE" => "PRICES_UPDATE",
		"TYPE" => "CHECKBOX",
		"LABEL" => Loc::getMessage("FIELDS_PRICES_UPDATE"),
		"DESCRIPTION" => Loc::getMessage("FIELDS_PRICES_UPDATE_NOTE"),
		"VALUE" => $str_PRICES_UPDATE,
	];
	$arFormFields["PRICES"]["ITEMS"][] = [
		"CODE" => "PRICES_DEFAULT_CURRENCY",
		"TYPE" => "CURRENCY",
		//"ITEMS" => $currencyArr,
		"LABEL" => Loc::getMessage("FIELDS_PRICES_DEFAULT_CURRENCY"),
		"DESCRIPTION" => Loc::getMessage("FIELDS_PRICES_DEFAULT_CURRENCY_NOTE"),
		"VALUE" => $str_PRICES_DEFAULT_CURRENCY ? $str_PRICES_DEFAULT_CURRENCY : 'RUB',
	];
	$arFormFields["PRICES"]["ITEMS"][] = [
		"CODE" => "PRICES_EXTRA_VALUE",
		"TYPE" => "NUMBER",
		"LABEL" => Loc::getMessage("FIELDS_PRICES_EXTRA_VALUE"),
		"DESCRIPTION" => Loc::getMessage("FIELDS_PRICES_EXTRA_VALUE_NOTE"),
		"VALUE" => $str_PRICES_EXTRA_VALUE,
	];
	
	$arFormFields["STORE_AMOUNT"]["LABEL"] = Loc::getMessage("GROUP_IMPORT_STORE_AMOUNT");
	$arFormFields["STORE_AMOUNT"]["ITEMS"][] = [
		"CODE" => "IMPORT_CATALOG_STORE_AMOUNT",
		"TYPE" => "CHECKBOX",
		"LABEL" => Loc::getMessage("FIELDS_IMPORT_CATALOG_STORE_AMOUNT"),
		"VALUE" => $str_IMPORT_CATALOG_STORE_AMOUNT,
	];
	$arFormFields["STORE_AMOUNT"]["ITEMS"][] = [
		"CODE" => "STORE_AMOUNT_ADD",
		"TYPE" => "CHECKBOX",
		"LABEL" => Loc::getMessage("FIELDS_STORE_AMOUNT_ADD"),
		"VALUE" => $str_STORE_AMOUNT_ADD,
	];
	$arFormFields["STORE_AMOUNT"]["ITEMS"][] = [
		"CODE" => "STORE_AMOUNT_UPDATE",
		"TYPE" => "CHECKBOX",
		"LABEL" => Loc::getMessage("FIELDS_STORE_AMOUNT_UPDATE"),
		"DESCRIPTION" => Loc::getMessage("FIELDS_STORE_AMOUNT_UPDATE_NOTE"),
		"VALUE" => $str_STORE_AMOUNT_UPDATE,
	];
}

if(Loader::includeModule("highloadblock") && $str_HIGHLOAD_BLOCK != 0)
{
	$arFormFields["ENTITIES"]["LABEL"] = Loc::getMessage("GROUP_IMPORT_ENTITIES");
	$arFormFields["ENTITIES"]["ITEMS"][] = [
		"CODE" => "IMPORT_HIGHLOAD_BLOCK_ENTITIES",
		"TYPE" => "CHECKBOX",
		"LABEL" => Loc::getMessage("FIELDS_IMPORT_HIGHLOAD_BLOCK_ENTITIES"),
		"VALUE" => $str_IMPORT_HIGHLOAD_BLOCK_ENTITIES,
	];
	$arFormFields["ENTITIES"]["ITEMS"][] = [
		"CODE" => "ENTITIES_ADD",
		"TYPE" => "CHECKBOX",
		"LABEL" => Loc::getMessage("FIELDS_ENTITIES_ADD"),
		"VALUE" => $str_ENTITIES_ADD,
	];
	$arFormFields["ENTITIES"]["ITEMS"][] = [
		"CODE" => "ENTITIES_UPDATE",
		"TYPE" => "CHECKBOX",
		"LABEL" => Loc::getMessage("FIELDS_ENTITIES_UPDATE"),
		"VALUE" => $str_ENTITIES_UPDATE,
	];
	$arFormFields["ENTITIES"]["ITEMS"][] = [
		"CODE" => "ENTITIES_TRANSLATE_XML_ID",
		"TYPE" => "CHECKBOX",
		"REQUIRED" => "N",
		"LABEL" => Loc::getMessage("FIELDS_ENTITIES_TRANSLATE_XML_ID"),
		"VALUE" => $str_ENTITIES_TRANSLATE_XML_ID,
	];
	$arFormFields["ENTITIES"]["ITEMS"][] = [
		"CODE" => "ENTITIES_ADD_LIST_ENUM",
		"TYPE" => "CHECKBOX",
		"REQUIRED" => "N",
		"LABEL" => Loc::getMessage("FIELDS_ENTITIES_ADD_LIST_ENUM"),
		"VALUE" => $str_ENTITIES_ADD_LIST_ENUM,
	];
}

CWebprostorCoreFunctions::ShowFormFields($arFormFields);

$tabControl->BeginNextTab();

if($ID>0 || $COPY_ID>0)
{
	
	$arFormFields = [];
	
	$GLOBALS["PLAN_ID"] = $ID;

	switch($str_IMPORT_FORMAT)
	{
		case("CSV"):

			$CSV_DELIMITERS = Array(
				"TZP" => Loc::getMessage("FIELDS_CSV_DELIMITER_TZP"),
				"ZPT" => Loc::getMessage("FIELDS_CSV_DELIMITER_ZPT"),
				"TAB" => Loc::getMessage("FIELDS_CSV_DELIMITER_TAB"),
				"SPS" => Loc::getMessage("FIELDS_CSV_DELIMITER_SPS"),
			);
			$arFormFields["FORMAT"]["ITEMS"][] = [
				"CODE" => "CSV_DELIMITER",
				"TYPE" => "SELECT",
				"LABEL" => Loc::getMessage("FIELDS_CSV_DELIMITER"),
				"VALUE" => $str_CSV_DELIMITER,
				"ITEMS" => $CSV_DELIMITERS,
			];
			$arFormFields["FORMAT"]["ITEMS"][] = Array(
				"CODE" => "CSV_XLS_NAME_LINE",
				"TYPE" => "NUMBER",
				"LABEL" => Loc::getMessage("FIELDS_CSV_XLS_NAME_LINE"),
				"DESCRIPTION" => Loc::getMessage("FIELDS_CSV_XLS_FIRST_LINE_ZERO"),
				"VALUE" => $str_CSV_XLS_NAME_LINE,
				"PARAMS" => Array(
					"SIZE" => 5,
					"MAXLENGTH" => 3,
					"MIN" => 0,
				),
			);
			$arFormFields["FORMAT"]["ITEMS"][] = Array(
				"CODE" => "CSV_XLS_START_LINE",
				"TYPE" => "NUMBER",
				"LABEL" => Loc::getMessage("FIELDS_CSV_XLS_START_LINE"),
				"DESCRIPTION" => Loc::getMessage("FIELDS_CSV_XLS_FIRST_LINE_ZERO"),
				"VALUE" => $str_CSV_XLS_START_LINE,
				"PARAMS" => Array(
					"SIZE" => 5,
					"MAXLENGTH" => 3,
					"MIN" => 0,
				),
			);
			$arFormFields["FORMAT"]["ITEMS"][] = Array(
				"CODE" => "CSV_XLS_FINISH_LINE",
				"TYPE" => "TEXT",
				"LABEL" => Loc::getMessage("FIELDS_CSV_XLS_FINISH_LINE"),
				"DESCRIPTION" => Loc::getMessage("FIELDS_CSV_XLS_FIRST_LINE_ZERO"),
				"VALUE" => $str_CSV_XLS_FINISH_LINE,
			);
			$arFormFields["FORMAT"]["ITEMS"][] = Array(
				"CODE" => "CSV_XLS_MAX_DEPTH_LEVEL",
				"TYPE" => "NUMBER",
				"LABEL" => Loc::getMessage("FIELDS_CSV_XLS_MAX_DEPTH_LEVEL"),
				"VALUE" => $str_CSV_XLS_MAX_DEPTH_LEVEL,
				"PARAMS" => Array(
					"SIZE" => 5,
					"MAXLENGTH" => 3,
					"MIN" => 1,
				),
			);
			break;
			
		case("XML"):
			$arFormFields["FORMAT"]["LABEL"] = Loc::getMessage("GROUP_MAIN");
			$arFormFields["FORMAT"]["ITEMS"][] = Array(
				"CODE" => "XML_ENTITY_GROUP",
				"TYPE" => "TEXT",
				"LABEL" => Loc::getMessage("FIELDS_XML_ENTITY_GROUP"),
				"VALUE" => $str_XML_ENTITY_GROUP,
				"PARAMS" => Array(
					"SIZE" => 30,
					"MAXLENGTH" => 255,
				),
			);
			$arFormFields["FORMAT"]["ITEMS"][] = Array(
				"CODE" => "XML_ENTITY",
				"TYPE" => "TEXT",
				"REQUIRED" => "Y",
				"LABEL" => Loc::getMessage("FIELDS_XML_ENTITY"),
				"VALUE" => $str_XML_ENTITY,
				"PARAMS" => Array(
					"SIZE" => 30,
					"MAXLENGTH" => 255,
				),
			);
			$arFormFields["FORMAT"]["ITEMS"][] = Array(
				"CODE" => "XML_USE_ENTITY_NAME",
				"TYPE" => "CHECKBOX",
				"DEFAULT" => "Y",
				"LABEL" => Loc::getMessage("FIELDS_XML_USE_ENTITY_NAME"),
				"DESCRIPTION" => Loc::getMessage("FIELDS_XML_USE_ENTITY_NAME_NOTE"),
				"VALUE" => $str_XML_USE_ENTITY_NAME,
			);
			$arFormFields["FORMAT_PROPERTIES"]["LABEL"] = Loc::getMessage("GROUP_IMPORT_ELEMENT_PROPERTIES");
			$arFormFields["FORMAT_PROPERTIES"]["ITEMS"][] = Array(
				"CODE" => "XML_PARSE_PARAMS_TO_PROPERTIES",
				"TYPE" => "CHECKBOX",
				"DEFAULT" => "Y",
				"LABEL" => Loc::getMessage("FIELDS_XML_PARSE_PARAMS_TO_PROPERTIES"),
				"DESCRIPTION" => Loc::getMessage("FIELDS_XML_PARSE_PARAMS_TO_PROPERTIES_NOTE"),
				"VALUE" => $str_XML_PARSE_PARAMS_TO_PROPERTIES,
			);
			$arFormFields["FORMAT_PROPERTIES"]["ITEMS"][] = Array(
				"CODE" => "XML_ENTITY_PARAM",
				"TYPE" => "TEXT",
				"LABEL" => Loc::getMessage("FIELDS_XML_ENTITY_PARAM"),
				"DESCRIPTION" => Loc::getMessage("FIELDS_XML_ENTITY_PARAM_NOTE"),
				"VALUE" => $str_XML_ENTITY_PARAM,
				"PARAMS" => Array(
					"SIZE" => 30,
					"MAXLENGTH" => 255,
				),
			);
			$arFormFields["FORMAT_PROPERTIES"]["ITEMS"][] = Array(
				"CODE" => "XML_SEARCH_BY_PROPERTY_CODE_FIRST",
				"TYPE" => "CHECKBOX",
				"DEFAULT" => "N",
				"LABEL" => Loc::getMessage("FIELDS_XML_SEARCH_BY_PROPERTY_CODE_FIRST"),
				"DESCRIPTION" => Loc::getMessage("FIELDS_XML_SEARCH_BY_PROPERTY_CODE_FIRST_NOTE"),
				"VALUE" => $str_XML_SEARCH_BY_PROPERTY_CODE_FIRST,
			);
			$arFormFields["FORMAT_PROPERTIES"]["ITEMS"][] = Array(
				"CODE" => "XML_SEARCH_ONLY_ACTIVE_PROPERTY",
				"TYPE" => "CHECKBOX",
				"DEFAULT" => "Y",
				"LABEL" => Loc::getMessage("FIELDS_XML_SEARCH_ONLY_ACTIVE_PROPERTY"),
				"DESCRIPTION" => Loc::getMessage("FIELDS_XML_SEARCH_ONLY_ACTIVE_PROPERTY_NOTE"),
				"VALUE" => $str_XML_SEARCH_ONLY_ACTIVE_PROPERTY,
			);
			$arFormFields["FORMAT_PROPERTIES"]["ITEMS"][] = Array(
				"CODE" => "XML_ADD_PROPERTIES_FOR_PARAMS",
				"TYPE" => "CHECKBOX",
				"DEFAULT" => "Y",
				"LABEL" => Loc::getMessage("FIELDS_XML_ADD_PROPERTIES_FOR_PARAMS"),
				"VALUE" => $str_XML_ADD_PROPERTIES_FOR_PARAMS,
			);
			$arFormFields["FORMAT_PROPERTIES_NEW"]["LABEL"] = Loc::getMessage("GROUP_IMPORT_ELEMENT_PROPERTIES_NEW");
			$property_features_enabled = \COption::GetOptionString('iblock', 'property_features_enabled');
			if($property_features_enabled == 'Y')
			{
				$arFormFields["FORMAT_PROPERTIES_NEW"]["ITEMS"][] = Array(
					"CODE" => "XML_PROPERTY_LIST_PAGE_SHOW",
					"TYPE" => "CHECKBOX",
					"DEFAULT" => "N",
					"LABEL" => Loc::getMessage("FIELDS_XML_PROPERTY_LIST_PAGE_SHOW"),
					"VALUE" => $str_XML_PROPERTY_LIST_PAGE_SHOW,
				);
				$arFormFields["FORMAT_PROPERTIES_NEW"]["ITEMS"][] = Array(
					"CODE" => "XML_PROPERTY_DETAIL_PAGE_SHOW",
					"TYPE" => "CHECKBOX",
					"DEFAULT" => "Y",
					"LABEL" => Loc::getMessage("FIELDS_XML_PROPERTY_DETAIL_PAGE_SHOW"),
					"VALUE" => $str_XML_PROPERTY_DETAIL_PAGE_SHOW,
				);
				if(Loader::includeModule('yandex.market'))
				{
					$arFormFields["FORMAT_PROPERTIES_NEW"]["ITEMS"][] = Array(
						"CODE" => "XML_PROPERTY_YAMARKET_COMMON",
						"TYPE" => "CHECKBOX",
						"DEFAULT" => "N",
						"LABEL" => Loc::getMessage("FIELDS_XML_PROPERTY_YAMARKET_COMMON"),
						"VALUE" => $str_XML_PROPERTY_YAMARKET_COMMON,
					);
					$arFormFields["FORMAT_PROPERTIES_NEW"]["ITEMS"][] = Array(
						"CODE" => "XML_PROPERTY_YAMARKET_TURBO",
						"TYPE" => "CHECKBOX",
						"DEFAULT" => "N",
						"LABEL" => Loc::getMessage("FIELDS_XML_PROPERTY_YAMARKET_TURBO"),
						"VALUE" => $str_XML_PROPERTY_YAMARKET_TURBO,
					);
				}
			}
			break;
			
		case("XLS"):
		case("XLSX"):
			if($str_IMPORT_FORMAT == "XLSX")
			{
				$scriptData = new CWebprostorImportXLSX;
				$sheets = $scriptData->GetSheets($str_IMPORT_FILE, $str_IMPORT_FILE_SHARSET);
			}
			if($sheets)
			{
				$arFormFields["FORMAT"]["ITEMS"][] = [
					"CODE" => "XLS_SHEET",
					"TYPE" => "SELECT",
					"LABEL" => Loc::getMessage("FIELDS_XLS_SHEET"),
					"VALUE" => $str_XLS_SHEET,
					"ITEMS" => $sheets,
				];
			}
			else
			{
				$arFormFields["FORMAT"]["ITEMS"][] = Array(
					"CODE" => "XLS_SHEET",
					"TYPE" => "NUMBER",
					"LABEL" => Loc::getMessage("FIELDS_XLS_SHEET_NUMBER"),
					"VALUE" => $str_XLS_SHEET,
					"PARAMS" => Array(
						"SIZE" => 10,
						"MAXLENGTH" => 11,
						"MIN" => 0,
					),
				);
			}
			$arFormFields["FORMAT"]["ITEMS"][] = Array(
				"CODE" => "CSV_XLS_NAME_LINE",
				"TYPE" => "NUMBER",
				"LABEL" => Loc::getMessage("FIELDS_CSV_XLS_NAME_LINE"),
				"DESCRIPTION" => Loc::getMessage("FIELDS_CSV_XLS_FIRST_LINE_ZERO"),
				"VALUE" => $str_CSV_XLS_NAME_LINE,
				"PARAMS" => Array(
					"SIZE" => 5,
					"MAXLENGTH" => 3,
					"MIN" => 0,
				),
			);
			$arFormFields["FORMAT"]["ITEMS"][] = Array(
				"CODE" => "CSV_XLS_START_LINE",
				"TYPE" => "NUMBER",
				"LABEL" => Loc::getMessage("FIELDS_CSV_XLS_START_LINE"),
				"DESCRIPTION" => Loc::getMessage("FIELDS_CSV_XLS_FIRST_LINE_ZERO"),
				"VALUE" => $str_CSV_XLS_START_LINE,
				"PARAMS" => Array(
					"SIZE" => 5,
					"MAXLENGTH" => 3,
					"MIN" => 0,
				),
			);
			$arFormFields["FORMAT"]["ITEMS"][] = Array(
				"CODE" => "CSV_XLS_FINISH_LINE",
				"TYPE" => "TEXT",
				"LABEL" => Loc::getMessage("FIELDS_CSV_XLS_FINISH_LINE"),
				"DESCRIPTION" => Loc::getMessage("FIELDS_CSV_XLS_FIRST_LINE_ZERO"),
				"VALUE" => $str_CSV_XLS_FINISH_LINE,
			);
			$arFormFields["FORMAT"]["ITEMS"][] = Array(
				"CODE" => "CSV_XLS_MAX_DEPTH_LEVEL",
				"TYPE" => "NUMBER",
				"LABEL" => Loc::getMessage("FIELDS_CSV_XLS_MAX_DEPTH_LEVEL"),
				"VALUE" => $str_CSV_XLS_MAX_DEPTH_LEVEL,
				"PARAMS" => Array(
					"SIZE" => 5,
					"MAXLENGTH" => 3,
					"MIN" => 1,
				),
			);
			break;
		case("ODS"):
		case("XODS"):
			$scriptData = new Webprostor\Import\Format\ODS;
			$sheets = $scriptData->GetSheets($str_IMPORT_FILE, $str_IMPORT_FILE_SHARSET);
			$arFormFields["FORMAT"]["ITEMS"][] = [
				"CODE" => "XLS_SHEET",
				"TYPE" => "SELECT",
				"LABEL" => Loc::getMessage("FIELDS_XLS_SHEET"),
				"VALUE" => $str_XLS_SHEET,
				"ITEMS" => $sheets,
			];
			$arFormFields["FORMAT"]["ITEMS"][] = Array(
				"CODE" => "CSV_XLS_NAME_LINE",
				"TYPE" => "NUMBER",
				"LABEL" => Loc::getMessage("FIELDS_CSV_XLS_NAME_LINE"),
				"DESCRIPTION" => Loc::getMessage("FIELDS_CSV_XLS_FIRST_LINE_ZERO"),
				"VALUE" => $str_CSV_XLS_NAME_LINE,
				"PARAMS" => Array(
					"SIZE" => 5,
					"MAXLENGTH" => 3,
					"MIN" => 0,
				),
			);
			$arFormFields["FORMAT"]["ITEMS"][] = Array(
				"CODE" => "CSV_XLS_START_LINE",
				"TYPE" => "NUMBER",
				"LABEL" => Loc::getMessage("FIELDS_CSV_XLS_START_LINE"),
				"DESCRIPTION" => Loc::getMessage("FIELDS_CSV_XLS_FIRST_LINE_ZERO"),
				"VALUE" => $str_CSV_XLS_START_LINE,
				"PARAMS" => Array(
					"SIZE" => 5,
					"MAXLENGTH" => 3,
					"MIN" => 0,
				),
			);
			$arFormFields["FORMAT"]["ITEMS"][] = Array(
				"CODE" => "CSV_XLS_FINISH_LINE",
				"TYPE" => "TEXT",
				"LABEL" => Loc::getMessage("FIELDS_CSV_XLS_FINISH_LINE"),
				"DESCRIPTION" => Loc::getMessage("FIELDS_CSV_XLS_FIRST_LINE_ZERO"),
				"VALUE" => $str_CSV_XLS_FINISH_LINE,
			);
			$arFormFields["FORMAT"]["ITEMS"][] = Array(
				"CODE" => "CSV_XLS_MAX_DEPTH_LEVEL",
				"TYPE" => "NUMBER",
				"LABEL" => Loc::getMessage("FIELDS_CSV_XLS_MAX_DEPTH_LEVEL"),
				"VALUE" => $str_CSV_XLS_MAX_DEPTH_LEVEL,
				"PARAMS" => Array(
					"SIZE" => 5,
					"MAXLENGTH" => 3,
					"MIN" => 1,
				),
			);
			break;
		case("JSON"):
			$arFormFields["FORMAT"]["ITEMS"][] = Array(
				"CODE" => "JSON_CACHE_TIME",
				"TYPE" => "NUMBER",
				"LABEL" => Loc::getMessage("FIELDS_JSON_CACHE_TIME"),
				"VALUE" => $str_JSON_CACHE_TIME ? $str_JSON_CACHE_TIME : 3600,
				"PARAMS" => Array(
					"MIN" => 60,
				),
			);
			$arFormFields["FORMAT"]["ITEMS"][] = Array(
				"CODE" => "JSON_NEXT_URL",
				"TYPE" => "TEXT",
				//"DISABLED" => "Y",
				"LABEL" => Loc::getMessage("FIELDS_JSON_NEXT_URL"),
				"DESCRIPTION" => Loc::getMessage("FIELDS_JSON_NEXT_URL_DESCRIPTION"),
				"VALUE" => $str_JSON_NEXT_URL,
				"PARAMS" => Array(
					"SIZE" => 60,
				),
			);
			$JSON_GET_DATA = unserialize(base64_decode($str_JSON_GET_DATA));
			$arFormFields["DATA"]["LABEL"] = Loc::getMessage("FIELDS_JSON_GET_DATA");
			$arFormFields["DATA"]["ITEMS"][] = Array(
				"CODE" => "JSON_GET_DATA",
				"TYPE" => "CODE_EDITOR",
				"VALUE" => $JSON_GET_DATA ? $JSON_GET_DATA : $str_JSON_GET_DATA,
				"PARAMS" => Array(
					"HEIGHT" => 500,
					"CODE_EDITOR" => true,
					"SYNTAX" => 'php',
				),
			);
			$arFormFields["DATA"]["ITEMS"][] = Array(
				"TYPE" => "NOTE",
				"VALUE" => Loc::getMessage("FIELDS_JSON_GET_DATA_DESCRIPTION"),
				"PARAMS" => Array(
					"TYPE" => 'warning',
				),
			);
			break;
	}
	
	CWebprostorCoreFunctions::ShowFormFields($arFormFields);
}

$tabControl->BeginNextTab();

$arFormFields = Array();
$arFormFields["MAIN"]["LABEL"] = Loc::getMessage("GROUP_DEBUG_MAIN");

$arFormFields["MAIN"]["ITEMS"][] = Array(
	"CODE" => "DEBUG_EVENTS",
	"TYPE" => "CHECKBOX",
	"LABEL" => Loc::getMessage("FIELDS_DEBUG_EVENTS"),
	"DESCRIPTION" => Loc::getMessage("FIELDS_DEBUG_EVENTS_DESCRIPTION"),
	"VALUE" => $str_DEBUG_EVENTS,
);

$arFormFields["FILES"]["LABEL"] = Loc::getMessage("GROUP_DEBUG_FILES");
$arFormFields["FILES"]["ITEMS"][] = Array(
	"CODE" => "DEBUG_IMAGES",
	"TYPE" => "CHECKBOX",
	"LABEL" => Loc::getMessage("FIELDS_DEBUG_IMAGES"),
	"VALUE" => $str_DEBUG_IMAGES,
);
$arFormFields["FILES"]["ITEMS"][] = Array(
	"CODE" => "DEBUG_FILES",
	"TYPE" => "CHECKBOX",
	"LABEL" => Loc::getMessage("FIELDS_DEBUG_FILES"),
	"VALUE" => $str_DEBUG_FILES,
);
$arFormFields["FILES"]["ITEMS"][] = Array(
	"CODE" => "DEBUG_URL",
	"TYPE" => "CHECKBOX",
	"LABEL" => Loc::getMessage("FIELDS_DEBUG_URL"),
	"VALUE" => $str_DEBUG_URL,
);

$arFormFields["AREAS"]["LABEL"] = Loc::getMessage("GROUP_DEBUG_AREAS");
if($str_IBLOCK_ID > 0)
{
	if(Loader::includeModule("iblock"))
	{
		$arFormFields["AREAS"]["ITEMS"][] = Array(
			"CODE" => "DEBUG_IMPORT_SECTION",
			"TYPE" => "CHECKBOX",
			"LABEL" => Loc::getMessage("FIELDS_DEBUG_IMPORT_SECTION"),
			"VALUE" => $str_DEBUG_IMPORT_SECTION,
		);
		$arFormFields["AREAS"]["ITEMS"][] = Array(
			"CODE" => "DEBUG_IMPORT_ELEMENTS",
			"TYPE" => "CHECKBOX",
			"LABEL" => Loc::getMessage("FIELDS_DEBUG_IMPORT_ELEMENTS"),
			"VALUE" => $str_DEBUG_IMPORT_ELEMENTS,
		);
		$arFormFields["AREAS"]["ITEMS"][] = Array(
			"CODE" => "DEBUG_IMPORT_PROPERTIES",
			"TYPE" => "CHECKBOX",
			"LABEL" => Loc::getMessage("FIELDS_DEBUG_IMPORT_PROPERTIES"),
			"VALUE" => $str_DEBUG_IMPORT_PROPERTIES,
		);
	}
	if(Loader::includeModule("catalog"))
	{
		$arFormFields["AREAS"]["ITEMS"][] = Array(
			"CODE" => "DEBUG_IMPORT_PRODUCTS",
			"TYPE" => "CHECKBOX",
			"LABEL" => Loc::getMessage("FIELDS_DEBUG_IMPORT_PRODUCTS"),
			"VALUE" => $str_DEBUG_IMPORT_PRODUCTS,
		);
		$arFormFields["AREAS"]["ITEMS"][] = Array(
			"CODE" => "DEBUG_IMPORT_OFFERS",
			"TYPE" => "CHECKBOX",
			"LABEL" => Loc::getMessage("FIELDS_DEBUG_IMPORT_OFFERS"),
			"VALUE" => $str_DEBUG_IMPORT_OFFERS,
		);
		$arFormFields["AREAS"]["ITEMS"][] = Array(
			"CODE" => "DEBUG_IMPORT_PRICES",
			"TYPE" => "CHECKBOX",
			"LABEL" => Loc::getMessage("FIELDS_DEBUG_IMPORT_PRICES"),
			"VALUE" => $str_DEBUG_IMPORT_PRICES,
		);
		$arFormFields["AREAS"]["ITEMS"][] = Array(
			"CODE" => "DEBUG_IMPORT_STORE_AMOUNT",
			"TYPE" => "CHECKBOX",
			"LABEL" => Loc::getMessage("FIELDS_DEBUG_IMPORT_STORE_AMOUNT"),
			"VALUE" => $str_DEBUG_IMPORT_STORE_AMOUNT,
		);
	}
}
else
{
	if(Loader::includeModule("highloadblock"))
	{
		$arFormFields["AREAS"]["ITEMS"][] = Array(
			"CODE" => "DEBUG_IMPORT_ENTITIES",
			"TYPE" => "CHECKBOX",
			"LABEL" => Loc::getMessage("FIELDS_DEBUG_IMPORT_ENTITIES"),
			"VALUE" => $str_DEBUG_IMPORT_ENTITIES,
		);
	}
}

CWebprostorCoreFunctions::ShowFormFields($arFormFields);

$tabControl->Buttons();
?>
<button 
title="<?=Loc::getMessage("SAVE_TITLE")?>" 
class="ui-btn ui-btn-success" 
type="submit" 
value="Y" 
name="save"
<?=$moduleAccessLevel>="W"?"":" disabled"?>
>
	<?=Loc::getMessage("SAVE")?>
</button>
<button 
title="<?=Loc::getMessage("APPLY_TITLE")?>" 
class="ui-btn ui-btn-primary" 
type="submit" 
value="Y" 
name="apply"
<?=$moduleAccessLevel>="W"?"":" disabled"?>
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
<input type="hidden" name="lang" value="<?=LANG?>">
<?
if($ID>0 && !$COPY_ID) {
?>
  <input type="hidden" name="ID" value="<?=$ID?>">
<?
}
elseif($COPY_ID>0)
{
?>
  <input type="hidden" name="COPY_ID" value="<?=$COPY_ID?>">
<?
}

$tabControl->End();
?>
<script type="text/javaScript">
<!--
BX.ready(function() {
	BX.UI.Hint.init(BX('plan_edit'));
<?if(!$ID){?>
	tabControl.DisableTab("objects");
	tabControl.DisableTab("setting");
<? } ?>
});
//-->
</script>
<?
	
$tabControl->ShowWarnings("PLAN_EDIT", $message);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");