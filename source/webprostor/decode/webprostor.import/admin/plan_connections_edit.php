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
	$back_url = "webprostor.import_plan_edit.php?ID=".$ID."&lang=".LANG;

$aTabs = array(
  array("DIV" => "main", "TAB" => Loc::getMessage("ELEMENT_TAB_1"), "ICON"=>"", "TITLE"=>Loc::getMessage("ELEMENT_TAB_1_TITLE")),
);

$sTableID = "webprostor_import_plans_connections";
$ID = intval($ID);
$message = null;
$strError = '';
$bVarsFromForm = false;

$arFields = array();

$tabControl = new CAdminTabControl("tabControl", $aTabs);

$CConnection = new CWebprostorImportPlanConnections;
$CConnectionFields = new CWebprostorImportPlanConnectionsFields;
$cProcessingData = new CWebprostorImportProcessingSettings;
$cProcessingTypesData = new CWebprostorImportProcessingSettingsTypes;

if($REQUEST_METHOD == "POST" && ($save!="" || $apply!="") && $moduleAccessLevel=="W" && check_bitrix_sessid()) 
{

	$arFields = Array(
		"ID" => $CONNECTIONS_ID,
		"ACTIVE" => $ACTIVE,
		"ENTITY" => $ENTITY,
		"NAME" => $NAME,
		"ENTITY_ATTRIBUTE" => $ENTITY_ATTRIBUTE,
		"SORT" => $SORT,
		"IBLOCK_SECTION_FIELD" => $IBLOCK_SECTION_FIELD,
		"IBLOCK_SECTION_DEPTH_LEVEL" => $IBLOCK_SECTION_DEPTH_LEVEL,
		"IBLOCK_SECTION_PARENT_FIELD" => $IBLOCK_SECTION_PARENT_FIELD,
		"IBLOCK_ELEMENT_FIELD" => $IBLOCK_ELEMENT_FIELD,
		"IBLOCK_ELEMENT_OFFER_FIELD" => $IBLOCK_ELEMENT_OFFER_FIELD,
		"IBLOCK_ELEMENT_PROPERTY" => $IBLOCK_ELEMENT_PROPERTY,
		"IBLOCK_ELEMENT_PROPERTY_E" => $IBLOCK_ELEMENT_PROPERTY_E,
		"IBLOCK_ELEMENT_PROPERTY_G" => $IBLOCK_ELEMENT_PROPERTY_G,
		"IBLOCK_ELEMENT_PROPERTY_M" => $IBLOCK_ELEMENT_PROPERTY_M,
		"IBLOCK_ELEMENT_OFFER_PROPERTY" => $IBLOCK_ELEMENT_OFFER_PROPERTY,
		"CATALOG_PRODUCT_FIELD" => $CATALOG_PRODUCT_FIELD,
		"CATALOG_PRODUCT_OFFER_FIELD" => $CATALOG_PRODUCT_OFFER_FIELD,
		"CATALOG_PRODUCT_PRICE" => $CATALOG_PRODUCT_PRICE,
		"CATALOG_PRODUCT_STORE_AMOUNT" => $CATALOG_PRODUCT_STORE_AMOUNT,
		"HIGHLOAD_BLOCK_ENTITY_FIELD" => $HIGHLOAD_BLOCK_ENTITY_FIELD,
		"IS_IMAGE" => $IS_IMAGE,
		"IS_FILE" => $IS_FILE,
		"IS_URL" => $IS_URL,
		"IS_ARRAY" => $IS_ARRAY,
		"IS_REQUIRED" => $IS_REQUIRED,
		"USE_IN_SEARCH" => $USE_IN_SEARCH,
		"USE_IN_CODE" => $USE_IN_CODE,
		"ENTITY_NAME" => $ENTITY_NAME,
		"PROCESSING_TYPES" => $PROCESSING_TYPES,
	);
	
	if($ID > 0)
	{
		$res = $CConnection->UpdatePlanConnections($ID, $arFields);
	}
	else
	{
		$exception = new CApplicationException(Loc::getMessage("ERROR_PLAN_ID_NO_SET"), WP_IMPORT_PLAN_ID_NO_SET);
		$APPLICATION->ThrowException($exception); 
	}
	
	if($res)
	{
		if ($apply != "")
			$message = new CAdminMessage(Array("MESSAGE" => Loc::getMessage("CONNECTIONS_SAVED"), "TYPE" => "OK"));
		else
			LocalRedirect("/bitrix/admin/webprostor.import_plan_edit.php?ID=".$ID."&lang=".LANG);
	}
	else
	{
		if($e = $APPLICATION->GetException())
			$message = new CAdminMessage(Loc::getMessage("MESSAGE_SAVE_ERROR"), $e);
		$bVarsFromForm = true;
	}
}

if($ID>0)
{
    $cData = new CWebprostorImportPlan;
	$element = $cData->GetById($ID);
	if(!$element->ExtractFields("plan_"))
		$ID=0;
	
	switch($plan_IMPORT_FORMAT)
	{
		case("CSV"):
			$scriptData = new CWebprostorImportCSV;
			break;
		case("XML"):
			$scriptData = new CWebprostorImportXML;
			break;
		case("XLS"):
			$scriptData = new CWebprostorImportXLS;
			break;
		case("XLSX"):
			$scriptData = new CWebprostorImportXLSX;
			break;
		case("ODS"):
		case("XODS"):
			$scriptData = new Webprostor\Import\Format\ODS;
			break;
		case("JSON"):
			$scriptData = new Webprostor\Import\Format\JSON;
			break;
	}
	
	if($ID>0)
	{
		$entitiesArray = $scriptData->GetEntities($ID);
		if($plan_IMPORT_FORMAT == "XML")
		{
			$attributesArray = $entitiesArray["ATTRIBUTES"];
			$entitiesArray = $entitiesArray["KEYS"];
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
		}
		if(!is_array($entitiesArray) || count($entitiesArray) == 0)
		{
			if($plan_IMPORT_FORMAT == "XML" && $plan_XML_ENTITY == "")
				$strError .= Loc::getMessage("ERROR_NO_XML_ENTITY").'<br />';
			else
				$strError .= Loc::getMessage("ERROR_NO_IMPORT_FILE_EXIST").'<br />';
		}
	}
}

if($bVarsFromForm)
	$DB->InitTableVarsForEdit($sTableID, "", "str_");

$APPLICATION->SetTitle(($ID>0? Loc::getMessage("ELEMENT_EDIT_TITLE_1").': '.$plan_NAME.' ['.$ID.']' : Loc::getMessage("ELEMENT_EDIT_TITLE_2")));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

if($ID>0)
{
	if($plan_IMPORT_IBLOCK_SECTIONS == "Y")
	{
		$connectionsRes = $CConnection->GetList(
			Array("SORT" => "ASC"), 
			Array("PLAN_ID" => $ID, "!IBLOCK_SECTION_FIELD" => false, "USE_IN_SEARCH" => "Y", "ACTIVE" => "Y"),
			['ID']
		);
		if (intval($connectionsRes->SelectedRowsCount()) == 0)
			$strError .= Loc::getMessage("MESSAGE_ERROR_NO_SECTION_SEARCH_CONNECTION").'<br />';
	}
	
	if($plan_IMPORT_IBLOCK_ELEMENTS == "Y" && $plan_IMPORT_IBLOCK_PROPERTIES == "N")
	{
		$connectionsRes = $CConnection->GetList(
			Array("SORT" => "ASC"), 
			Array("PLAN_ID" => $ID, "!IBLOCK_ELEMENT_FIELD" => false, "USE_IN_SEARCH" => "Y", "ACTIVE" => "Y"),
			['ID']
		);
		if (intval($connectionsRes->SelectedRowsCount()) == 0)
			$strError .= Loc::getMessage("MESSAGE_ERROR_NO_ELEMENT_SEARCH_CONNECTION").'<br />';
	}
	elseif($plan_IMPORT_IBLOCK_ELEMENTS == "Y" && $plan_IMPORT_IBLOCK_PROPERTIES == "Y")
	{
		$connectionsRes1 = $CConnection->GetList(
			Array("SORT" => "ASC"), 
			Array("PLAN_ID" => $ID, "!IBLOCK_ELEMENT_FIELD" => false, "USE_IN_SEARCH" => "Y", "ACTIVE" => "Y"),
			['ID']
		);
		$connectionsRes2 = $CConnection->GetList(
			Array("SORT" => "ASC"), 
			Array("PLAN_ID" => $ID, "!IBLOCK_ELEMENT_PROPERTY" => false, "USE_IN_SEARCH" => "Y", "ACTIVE" => "Y"),
			['ID']
		);
		if (intval($connectionsRes1->SelectedRowsCount()) == 0 && intval($connectionsRes2->SelectedRowsCount()) == 0)
			$strError .= Loc::getMessage("MESSAGE_ERROR_NO_ELEMENT_OR_PROPERTY_SEARCH_CONNECTION").'<br />';
		
		$connectionsRes3 = $CConnection->GetList(
			Array("SORT" => "ASC"), 
			Array("PLAN_ID" => $ID, "!IBLOCK_ELEMENT_PROPERTY" => false, "!IBLOCK_ELEMENT_PROPERTY_E" => false, "ACTIVE" => "Y"),
			['ID', 'IBLOCK_ELEMENT_PROPERTY']
		);
		while($iblockPropertE = $connectionsRes3->GetNext())
		{
			$iblockPropertEarray = \CIBlockProperty::GetByID($iblockPropertE['IBLOCK_ELEMENT_PROPERTY'])->Fetch();
			if($iblockPropertEarray['PROPERTY_TYPE'] != 'E')
			{
				$strError .= Loc::getMessage("MESSAGE_ERROR_IBLOCK_ELEMENT_PROPERTY_TYPE_IS_NOT_E", ['#CONNECTION_ID#' => $iblockPropertE['ID'], '#PROPERTY_NAME#' => htmlspecialcharsbx($iblockPropertEarray['NAME'])]).'<br />';
			}
			elseif($iblockPropertEarray['LINK_IBLOCK_ID'] == '0')
			{
				$strError .= Loc::getMessage("MESSAGE_ERROR_IBLOCK_ELEMENT_PROPERTY_TYPE_E_HAVE_NOT_LINK_IBLOCK_ID", ['#CONNECTION_ID#' => $iblockPropertE['ID'], '#PROPERTY_NAME#' => htmlspecialcharsbx($iblockPropertEarray['NAME'])]).'<br />';
			}
		}
		unset($iblockPropertE, $iblockPropertEarray);
	}
	
	if($plan_IMPORT_CATALOG_PRODUCT_OFFERS == "Y" && $plan_IMPORT_IBLOCK_PROPERTIES == "N")
	{
		$connectionsRes = $CConnection->GetList(
			Array("SORT" => "ASC"), 
			Array("PLAN_ID" => $ID, "!IBLOCK_ELEMENT_OFFER_FIELD" => false, "USE_IN_SEARCH" => "Y", "ACTIVE" => "Y"),
			['ID']
		);
		if (intval($connectionsRes->SelectedRowsCount()) == 0)
			$strError .= Loc::getMessage("MESSAGE_ERROR_NO_OFFER_SEARCH_CONNECTION").'<br />';
	}
	elseif($plan_IMPORT_CATALOG_PRODUCT_OFFERS == "Y" && $plan_IMPORT_IBLOCK_PROPERTIES == "Y")
	{
		$connectionsRes1 = $CConnection->GetList(
			Array("SORT" => "ASC"), 
			Array("PLAN_ID" => $ID, "!IBLOCK_ELEMENT_OFFER_FIELD" => false, "USE_IN_SEARCH" => "Y", "ACTIVE" => "Y"),
			['ID']
		);
		$connectionsRes2 = $CConnection->GetList(
			Array("SORT" => "ASC"), 
			Array("PLAN_ID" => $ID, "!IBLOCK_ELEMENT_OFFER_PROPERTY" => false, "USE_IN_SEARCH" => "Y", "ACTIVE" => "Y"),
			['ID']
		);
		if (intval($connectionsRes1->SelectedRowsCount()) == 0 && intval($connectionsRes2->SelectedRowsCount()) == 0)
			$strError .= Loc::getMessage("MESSAGE_ERROR_NO_OFFER_OR_PROPERTY_SEARCH_CONNECTION").'<br />';
	}
	
	if($plan_IMPORT_HIGHLOAD_BLOCK_ENTITIES == "Y")
	{
		$connectionsRes = $CConnection->GetList(
			Array("SORT" => "ASC"), 
			Array("PLAN_ID" => $ID, "!HIGHLOAD_BLOCK_ENTITY_FIELD" => false, "USE_IN_SEARCH" => "Y", "ACTIVE" => "Y"),
			['ID']
		);
		if (intval($connectionsRes->SelectedRowsCount()) == 0)
			$strError .= Loc::getMessage("MESSAGE_ERROR_NO_HIGHLOAD_SEARCH_CONNECTION").'<br />';
	}

}

if($strError != "")
{
	CWebprostorCoreFunctions::showAlertBegin('danger', 'danger');
	echo $strError;
	CWebprostorCoreFunctions::showAlertEnd();
}

if($message)
	echo $message->Show();
	
$aMenu = array(
	array(
		"TEXT"=>Loc::getMessage("PLAN_EDIT"),
		"TITLE"=>Loc::getMessage("PLAN_EDIT_TITLE"),
		"LINK"=>"webprostor.import_plan_edit.php?ID=".$ID."&lang=".LANG,
		"ICON"=>"btn_list",
	)
);

$aMenu[] = array("SEPARATOR"=>"Y");
$aSubMenu = array();

if($SHOW_ACTIVE != "Y")
{
	$aSubMenu[] = array(
		"TEXT"  => Loc::getMessage("ONLY_ACTIVE"),
		"LINK"=>"webprostor.import_plan_connections_edit.php?ID={$ID}&lang=".LANG."&SHOW_ACTIVE=Y",
		"ICON"  => "",
	);
}

if($SHOW_ACTIVE != "N")
{
	$aSubMenu[] = array(
		"TEXT"  => Loc::getMessage("ONLY_NOT_ACTIVE"),
		"LINK"=>"webprostor.import_plan_connections_edit.php?ID={$ID}&lang=".LANG."&SHOW_ACTIVE=N",
		"ICON"  => "",
	);
}

if($SHOW_REQUIRED != "Y")
{
	$aSubMenu[] = array(
		"TEXT"  => Loc::getMessage("ONLY_REQUIRED"),
		"LINK"=>"webprostor.import_plan_connections_edit.php?ID={$ID}&lang=".LANG."&SHOW_REQUIRED=Y",
		"ICON"  => "",
	);
}

if($SHOW_REQUIRED != "N")
{
	$aSubMenu[] = array(
		"TEXT"  => Loc::getMessage("ONLY_NOT_REQUIRED"),
		"LINK"=>"webprostor.import_plan_connections_edit.php?ID={$ID}&lang=".LANG."&SHOW_REQUIRED=N",
		"ICON"  => "",
	);
}

if($SHOW_IMAGE != "Y")
{
	$aSubMenu[] = array(
		"TEXT"  => Loc::getMessage("ONLY_IMAGE"),
		"LINK"=>"webprostor.import_plan_connections_edit.php?ID={$ID}&lang=".LANG."&SHOW_IMAGE=Y",
		"ICON"  => "",
	);
}

if($SHOW_IMAGE != "N")
{
	$aSubMenu[] = array(
		"TEXT"  => Loc::getMessage("ONLY_NOT_IMAGE"),
		"LINK"=>"webprostor.import_plan_connections_edit.php?ID={$ID}&lang=".LANG."&SHOW_IMAGE=N",
		"ICON"  => "",
	);
}

if($SHOW_FILE != "Y")
{
	$aSubMenu[] = array(
		"TEXT"  => Loc::getMessage("ONLY_FILE"),
		"LINK"=>"webprostor.import_plan_connections_edit.php?ID={$ID}&lang=".LANG."&SHOW_FILE=Y",
		"ICON"  => "",
	);
}

if($SHOW_FILE != "N")
{
	$aSubMenu[] = array(
		"TEXT"  => Loc::getMessage("ONLY_NOT_FILE"),
		"LINK"=>"webprostor.import_plan_connections_edit.php?ID={$ID}&lang=".LANG."&SHOW_FILE=N",
		"ICON"  => "",
	);
}

if($SHOW_URL != "Y")
{
	$aSubMenu[] = array(
		"TEXT"  => Loc::getMessage("ONLY_URL"),
		"LINK"=>"webprostor.import_plan_connections_edit.php?ID={$ID}&lang=".LANG."&SHOW_URL=Y",
		"ICON"  => "",
	);
}

if($SHOW_URL != "N")
{
	$aSubMenu[] = array(
		"TEXT"  => Loc::getMessage("ONLY_NOT_URL"),
		"LINK"=>"webprostor.import_plan_connections_edit.php?ID={$ID}&lang=".LANG."&SHOW_URL=N",
		"ICON"  => "",
	);
}

if($SHOW_SEARCH != "Y")
{
	$aSubMenu[] = array(
		"TEXT"  => Loc::getMessage("ONLY_SEARCH"),
		"LINK"=>"webprostor.import_plan_connections_edit.php?ID={$ID}&lang=".LANG."&SHOW_SEARCH=Y",
		"ICON"  => "",
	);
}

if($SHOW_SEARCH != "N")
{
	$aSubMenu[] = array(
		"TEXT"  => Loc::getMessage("ONLY_NOT_SEARCH"),
		"LINK"=>"webprostor.import_plan_connections_edit.php?ID={$ID}&lang=".LANG."&SHOW_SEARCH=N",
		"ICON"  => "",
	);
}

if($SHOW_ACTIVE || $SHOW_REQUIRED || $SHOW_IMAGE || $SHOW_FILE || $SHOW_URL || $SHOW_SEARCH)
{
	$aSubMenu[] = array(
		"TEXT"  => Loc::getMessage("SHOW_ALL"),
		"LINK"=>"webprostor.import_plan_connections_edit.php?ID={$ID}&lang=".LANG,
		"ICON"  => "view",
	);
}

$aMenu[] = array(
	"TEXT"  => Loc::getMessage("BTN_ACTIONS"),
	"ICON"  => "btn_new",
	"MENU"  => $aSubMenu,
);

$context = new CAdminContextMenu($aMenu);

$context->Show();

/*elseif($element->LAST_ERROR!="")
	CAdminMessage::ShowMessage($element->LAST_ERROR);*/
	
if (($moduleAccessLevel < 'W' && $moduleAccessLevel <> 'D') || ($ID==0 || !$ID))
{
	echo BeginNote();
	if ($moduleAccessLevel < 'W' && $moduleAccessLevel <> 'D')
		echo Loc::getMessage('MESSAGE_NOT_SAVE_ACCESS');
	if ($ID==0 || !$ID)
		echo Loc::getMessage('MESSAGE_NO_PLAN_ID');
	echo EndNote();
}
?>
<form method="POST" name="PLAN_CONNECTIONS_EDIT" id="plan_connections_edit" Action="<?echo $APPLICATION->GetCurPage()?>" ENCTYPE="multipart/form-data">
<?echo bitrix_sessid_post();?>
<?
$tabControl->Begin();

$isCatalogSKU = false;
if(Loader::includeModule("catalog"))
{
	$isCatalogSKU = CCatalogSKU::GetInfoByProductIBlock($plan_IBLOCK_ID);
	$plan_OFFERS_IBLOCK_ID = $isCatalogSKU["IBLOCK_ID"];
	$plan_OFFERS_SKU_PROPERTY_ID = $isCatalogSKU["SKU_PROPERTY_ID"];
}
?>
<?
$tabControl->BeginNextTab();
?>
<?
if($ID>0)
{
?>
<tr>
	<td colspan="2" align="center">
	
		<?
		if(is_array($entitiesArray) && (($plan_IMPORT_FORMAT == "XML" && $plan_XML_USE_ENTITY_NAME != "Y") || $plan_IMPORT_FORMAT != "XML"))
		{
			foreach($entitiesArray as $value => $label)
			{
			?>
			<input type="hidden" name="ENTITY_NAME[<?=$value;?>]" value="<?=htmlspecialcharsbx(str_replace(["\r", "\n"], '', $label));?>" />
			<?
			}
		}
		?>
		
		<?
		if($plan_IMPORT_IBLOCK_SECTIONS=="Y" || $plan_IMPORT_IBLOCK_PROPERTIES=="Y")
			$iblockSectionFields = $CConnectionFields->GetFields("SECTION", $plan_IBLOCK_ID);
		
		if($plan_IMPORT_IBLOCK_ELEMENTS=="Y" || $plan_IMPORT_IBLOCK_PROPERTIES=="Y" || $plan_IMPORT_CATALOG_PRODUCT_OFFERS=="Y")
		{
			$iblockElementFields = $CConnectionFields->GetFields("ELEMENT");
		}
		
		if($plan_IMPORT_IBLOCK_PROPERTIES == "Y")
		{
			$propertiesArray = Array();
			$propRes = CIBlockProperty::GetList(Array("ID"=>"asc", "name"=>"asc"), Array("IBLOCK_ID"=>$plan_IBLOCK_ID));
			while($propFields = $propRes->GetNext())
			{
				$propertiesArray[$propFields["ID"]] = htmlspecialcharsbx(addslashes(str_replace("\r\n",'',$propFields["NAME"]))).' ['.$propFields["ID"].']';
			}
			$coordinates = Array(
				"latitude" => Loc::getMessage("MAP_LATITUDE"),
				"longitude" => Loc::getMessage("MAP_LONGITUDE")
			);
		}
		
		if($plan_IMPORT_CATALOG_PRODUCT_OFFERS == "Y" && $isCatalogSKU)
		{
			$offersPropertiesArray = Array();
			$propRes = CIBlockProperty::GetList(Array("ID"=>"asc", "name"=>"asc"), Array("IBLOCK_ID"=>$plan_OFFERS_IBLOCK_ID));
			while($propFields = $propRes->GetNext())
			{
				if($propFields["ID"] != $plan_OFFERS_SKU_PROPERTY_ID)
					$offersPropertiesArray[$propFields["ID"]] = htmlspecialcharsbx(addslashes($propFields["NAME"])).' ['.$propFields["ID"].']';
			}
		}
		
		if($plan_IMPORT_CATALOG_PRODUCTS=="Y")
			$catalogProductFields = $CConnectionFields->GetFields("PRODUCT");
		
		if($plan_IMPORT_CATALOG_PRICES=="Y" || $plan_IMPORT_CATALOG_PRODUCT_OFFERS == "Y")
			$catalogProductPrice = $CConnectionFields->GetFields("PRICE");
		
		if($plan_IMPORT_CATALOG_STORE_AMOUNT=="Y")
			$catalogProductStore = $CConnectionFields->GetFields("STORE");
		
		if($plan_IMPORT_HIGHLOAD_BLOCK_ENTITIES=="Y")
			$highloadBlockEntityFields = $CConnectionFields->GetFields("ENTITIES", false, $plan_HIGHLOAD_BLOCK);
		
		/*if($plan_IMPORT_FORMAT != "XML")
		{*/
			$useInCodeValues = $CConnectionFields->GetUseInCode([
				"SECTIONS" => ($plan_IMPORT_FORMAT != "XML"?$plan_IMPORT_IBLOCK_SECTIONS:"N"),
				"ELEMENTS" => $plan_IMPORT_IBLOCK_ELEMENTS,
				"ENTITIES" => $plan_IMPORT_HIGHLOAD_BLOCK_ENTITIES,
			]);
		//}
		
		$queryObject = $cProcessingData->getList(Array($b = "sort" => $o = "asc"), array());
		$listTypes = Array();
		while($type = $queryObject->getNext())
			$listTypes[$type["ID"]] = htmlspecialcharsbx($type["PROCESSING_TYPE"]).' ['.$type["ID"].'] '.$cProcessingTypesData->GetParamsValue($type["PARAMS"], '; ');
		?>
		<script type="text/javascript" language="JavaScript">
		<!--
		var EntitiesValues = [], EntitiesLabels = [], entitiesArray = [];
		<?foreach($entitiesArray as $value => $label):?>
		<?$label = str_replace(array("\r\n", "\r", "\n"), ' ', $label);?>
		EntitiesValues[EntitiesValues.length] = '<?=$value?>'; EntitiesLabels[EntitiesLabels.length] = '<?=htmlspecialcharsbx($label)?>'; entitiesArray[entitiesArray.length] = { id: '<?=$value?>', text: '<?=htmlspecialcharsbx(str_replace(["\r", "\n"], '', $label))?> [<?=$value?>]' }; <?endforeach;?> 		
		$(document).ready(function() {
			$('.select-search-entities').select2({ 
				language: "ru",
				data: entitiesArray,
			}); 
		});
		
		<?if($plan_IMPORT_IBLOCK_SECTIONS=="Y" || $plan_IMPORT_IBLOCK_PROPERTIES=="Y") {?>
		<?if($plan_IMPORT_FORMAT != "XML") {?>
		var sectionDepthLevelsValues = [], sectionDepthLevelsLabels = [], sectionDepthLevels = [];
		<?foreach($sectionDepthLevels as $value => $label):?>
		sectionDepthLevelsValues[sectionDepthLevelsValues.length] = '<?=$value?>'; sectionDepthLevelsLabels[sectionDepthLevelsLabels.length] = '<?=htmlspecialcharsbx($label)?>'; sectionDepthLevels[sectionDepthLevels.length] = { id: '<?=$value?>', text: '<?=htmlspecialcharsbx($label)?>' }; <?endforeach;?>
		$(document).ready(function() {
			$('.select-search-section-depth').select2({ 
				language: "ru",
				data: sectionDepthLevels,
			}); 
		});
		<? } ?>
		var IblockSectionFieldsValues = [''], IblockSectionFieldsLabels = ['<?=Loc::getMessage("MESSAGE_DO_NOT_USE");?>'], iblockSectionFields = [];
		<?foreach($iblockSectionFields as $value => $label):?>
		IblockSectionFieldsValues[IblockSectionFieldsValues.length] = '<?=$value?>'; IblockSectionFieldsLabels[IblockSectionFieldsLabels.length] = '<?=htmlspecialcharsbx($label)?>'; iblockSectionFields[iblockSectionFields.length] = { id: '<?=$value?>', text: '<?=htmlspecialcharsbx($label)?>' }; <?endforeach;?>
		$(document).ready(function() {
			$('.select-search-sections').select2({ 
				language: "ru",
				data: iblockSectionFields,
			}); 
		});
		<? } ?>
		
		<?if($plan_IMPORT_IBLOCK_ELEMENTS=="Y" || $plan_IMPORT_IBLOCK_PROPERTIES=="Y" || $plan_IMPORT_CATALOG_PRODUCT_OFFERS=="Y")	{?>
		var IblockFieldsValues = [''], IblockFieldsLabels = ['<?=Loc::getMessage("MESSAGE_DO_NOT_USE");?>'], iblockElementFields = [];
		<?foreach($iblockElementFields as $value => $label):?>
		IblockFieldsValues[IblockFieldsValues.length] = '<?=$value?>'; IblockFieldsLabels[IblockFieldsLabels.length] = '<?=htmlspecialcharsbx($label)?>'; iblockElementFields[iblockElementFields.length] = { id: '<?=$value?>', text: '<?=htmlspecialcharsbx($label)?>' }; <?endforeach;?>
		$(document).ready(function() {
			$('.select-search-elements').select2({ 
				language: "ru",
				data: iblockElementFields,
			}); 
		});
		<? } ?>
		
		<? if($plan_IMPORT_IBLOCK_PROPERTIES == "Y") { ?>
		var IblockPropertiesValues = [''], IblockPropertiesLabels = ['<?=Loc::getMessage("MESSAGE_DO_NOT_USE");?>'], propertiesArray = [];
		var IblockPropertiesMapValues = ['','latitude','longitude'], IblockPropertiesMapLabels = ['<?=Loc::getMessage("MESSAGE_DO_NOT_USE");?>','<?=Loc::getMessage("MAP_LATITUDE");?>','<?=Loc::getMessage("MAP_LONGITUDE");?>'];
		<?foreach($propertiesArray as $value => $label):?>
		IblockPropertiesValues[IblockPropertiesValues.length] = '<?=$value?>'; IblockPropertiesLabels[IblockPropertiesLabels.length] = '<?=htmlspecialcharsbx($label)?>'; propertiesArray[propertiesArray.length] = { id: '<?=$value?>', text: '<?=htmlspecialcharsbx($label)?>' };
		<?endforeach;?>
		$(document).ready(function() {
			$('.select-search-properties').select2({ 
				language: "ru",
				data: propertiesArray,
			}); 
		});
		<? } ?>
		
		<? if($plan_IMPORT_CATALOG_PRODUCT_OFFERS == "Y" && $isCatalogSKU) { ?>
		var IblockOffersPropertiesValues = [''], IblockOffersPropertiesLabels = ['<?=Loc::getMessage("MESSAGE_DO_NOT_USE");?>'], offersPropertiesArray = [];
		<?foreach($offersPropertiesArray as $value => $label):?>
		IblockOffersPropertiesValues[IblockOffersPropertiesValues.length] = '<?=$value?>'; IblockOffersPropertiesLabels[IblockOffersPropertiesLabels.length] = '<?=$label?>'; offersPropertiesArray[offersPropertiesArray.length] = { id: '<?=$value?>', text: '<?=htmlspecialcharsbx($label)?>' };
 		<?endforeach;?>
		$(document).ready(function() {
			$('.select-search-properties-offer').select2({ 
				language: "ru",
				data: offersPropertiesArray,
			}); 
		});
		<? } ?>
		
		<?if($plan_IMPORT_CATALOG_PRODUCTS=="Y")	{?>
		var CatalogFieldsValues = [''], CatalogFieldsLabels = ['<?=Loc::getMessage("MESSAGE_DO_NOT_USE");?>'], catalogProductFields = [];
		<?foreach($catalogProductFields as $value => $label):?>
		CatalogFieldsValues[CatalogFieldsValues.length] = '<?=$value?>';
		CatalogFieldsLabels[CatalogFieldsLabels.length] = '<?=$label?>';
		catalogProductFields[catalogProductFields.length] = {
			id: '<?=$value?>',
			text: '<?=htmlspecialcharsbx($label)?>'
		};
		<?endforeach;?>
		$(document).ready(function() {
			$('.select-search-catalog-product').select2({ 
				language: "ru",
				data: catalogProductFields,
			}); 
		});
		<? } ?>
		
		<?if($plan_IMPORT_CATALOG_PRICES=="Y" || $plan_IMPORT_CATALOG_PRODUCT_OFFERS == "Y")	{?>
		var CatalogPricesValues = [''], CatalogPricesLabels = ['<?=Loc::getMessage("MESSAGE_DO_NOT_USE");?>'], catalogProductPrice = [];
		<?foreach($catalogProductPrice as $value => $label):?>
		CatalogPricesValues[CatalogPricesValues.length] = '<?=$value?>'; CatalogPricesLabels[CatalogPricesLabels.length] = '<?=$label?>'; catalogProductPrice[catalogProductPrice.length] = { id: '<?=$value?>', text: '<?=htmlspecialcharsbx($label)?>' };
 		<?endforeach;?>
		$(document).ready(function() {
			$('.select-search-price').select2({ 
				language: "ru",
				data: catalogProductPrice,
			}); 
		});
		<? } ?>
		
		<?if($plan_IMPORT_CATALOG_STORE_AMOUNT=="Y") {?>
		var CatalogStoresValues = [''], CatalogStoresLabels = ['<?=Loc::getMessage("MESSAGE_DO_NOT_USE");?>'], catalogProductStore = [];
		<?foreach($catalogProductStore as $value => $label):?>
		CatalogStoresValues[CatalogStoresValues.length] = '<?=$value?>'; CatalogStoresLabels[CatalogStoresLabels.length] = '<?=$label?>'; catalogProductStore[catalogProductStore.length] = { id: '<?=$value?>', text: '<?=htmlspecialcharsbx($label)?>' };
 		<?endforeach;?>
		$(document).ready(function() {
			$('.select-search-store').select2({ 
				language: "ru",
				data: catalogProductStore,
			}); 
		});
		<? } ?>
		
		<?if($plan_IMPORT_HIGHLOAD_BLOCK_ENTITIES=="Y")	{?>
		var HighloadEntitiesValues = [''], HighloadEntitiesLabels = ['<?=Loc::getMessage("MESSAGE_DO_NOT_USE");?>'], highloadEntities = [];
		<?foreach($highloadBlockEntityFields as $value => $label):?>
		HighloadEntitiesValues[HighloadEntitiesValues.length] = '<?=$value?>'; HighloadEntitiesLabels[HighloadEntitiesLabels.length] = '<?=$label?>'; highloadEntities[highloadEntities.length] = { id: '<?=$value?>', text: '<?=htmlspecialcharsbx($label)?>' };
 		<?endforeach;?>
		$(document).ready(function() {
			$('.select-search-highload').select2({ 
				language: "ru",
				data: highloadEntities,
			}); 
		});
		<? } ?>
		
		var UseInCodeValues = [], UseInCodeLabels = [], useInCode = [];
		<?foreach($useInCodeValues as $value => $label):?>
		UseInCodeValues[UseInCodeValues.length] = '<?=$value?>'; UseInCodeLabels[UseInCodeLabels.length] = '<?=$label?>'; useInCode[useInCode.length] = { id: '<?=$value?>', text: '<?=htmlspecialcharsbx($label)?>' };
 		<?endforeach;?>
		$(document).ready(function() {
			$('.select-search-code').select2({ 
				language: "ru",
				data: useInCode,
			}); 
		});
		
		var ProcessingTypesValues = [], ProcessingTypesLabels = [], ProcessingTypes = [];
		<?foreach($listTypes as $value => $label):
		$label = addslashes($label);
		$label = htmlspecialcharsbx($label);
		$label = preg_replace('/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/', '', $label);
		?>
		ProcessingTypesValues[ProcessingTypesValues.length] = '<?=$value?>'; 
		ProcessingTypesLabels[ProcessingTypesLabels.length] = '<?=$label?>'; 
		ProcessingTypes[ProcessingTypes.length] = { id: '<?=$value?>', text: '<?=$label?>' };
 		<?endforeach;?>
		$(document).ready(function() {
			$('.select-search-processing').select2({ 
				language: "ru",
				data: ProcessingTypes,
			}); 
		});
		
		var CheckboxValues = ['N', 'Y'], CheckboxLabels = ['<?=Loc::getMessage("OPTION_CHECKBOX_NO");?>', '<?=Loc::getMessage("OPTION_CHECKBOX_YES");?>'];
		
		function CheckRowClass(rowEl)
		{
			var rowClass = rowEl.className;
			if(rowClass != 'data-row checked')
				rowEl.className = 'data-row checked';
			else
				rowEl.className = 'data-row';
		}
		
		function AddConnectionRow(tableID)
		{
			var tableRef = document.getElementById(tableID);
			
			var rows = tableRef.getElementsByTagName("tbody")[0].getElementsByTagName("tr").length;
			var newRow = tableRef.insertRow(rows);
			var newRowId = 'new_connection_'+rows;
			newRow.id = newRowId;
			newRow.className = "data-row";
			
			WebprostorCoreAddNewCellButton(newRow, 'center', 'button', function(){WebprostorCoreRemoveRow(newRowId)}, '', 'ui-btn ui-btn-danger ui-btn-icon-remove');
			WebprostorCoreAddNewCellSelect(newRow, 'center', 'PROCESSING_TYPES[' + 'new_' + (rows - 1) + '][]', ProcessingTypesValues, ProcessingTypesLabels, false, true, true);
			<?if($plan_IMPORT_IBLOCK_PROPERTIES=="Y"){?>
			WebprostorCoreAddNewCellSelect(newRow, 'center', 'IBLOCK_ELEMENT_PROPERTY_M[]', IblockPropertiesMapValues, IblockPropertiesMapLabels);
			WebprostorCoreAddNewCellSelect(newRow, 'center', 'IBLOCK_ELEMENT_PROPERTY_G[]', IblockSectionFieldsValues, IblockSectionFieldsLabels);
			WebprostorCoreAddNewCellSelect(newRow, 'center', 'IBLOCK_ELEMENT_PROPERTY_E[]', IblockFieldsValues, IblockFieldsLabels);
			<? } ?>
			WebprostorCoreAddNewCellSelect(newRow, 'center', 'USE_IN_CODE[]', UseInCodeValues, UseInCodeLabels);
			WebprostorCoreAddNewCellSelect(newRow, 'center', 'USE_IN_SEARCH[]', CheckboxValues, CheckboxLabels);
			WebprostorCoreAddNewCellSelect(newRow, 'center', 'IS_REQUIRED[]', CheckboxValues, CheckboxLabels);
			WebprostorCoreAddNewCellSelect(newRow, 'center', 'IS_ARRAY[]', CheckboxValues, CheckboxLabels);
			WebprostorCoreAddNewCellSelect(newRow, 'center', 'IS_FILE[]', CheckboxValues, CheckboxLabels);
			WebprostorCoreAddNewCellSelect(newRow, 'center', 'IS_URL[]', CheckboxValues, CheckboxLabels);
			WebprostorCoreAddNewCellSelect(newRow, 'center', 'IS_IMAGE[]', CheckboxValues, CheckboxLabels);
			<?if($plan_IMPORT_HIGHLOAD_BLOCK_ENTITIES=="Y"){?>
			WebprostorCoreAddNewCellSelect(newRow, 'center', 'HIGHLOAD_BLOCK_ENTITY_FIELD[]', HighloadEntitiesValues, HighloadEntitiesLabels);
			<? } ?>
			<?if($plan_IMPORT_CATALOG_STORE_AMOUNT=="Y"){?>
			WebprostorCoreAddNewCellSelect(newRow, 'center', 'CATALOG_PRODUCT_STORE_AMOUNT[]', CatalogStoresValues, CatalogStoresLabels);
			<? } ?>
			<?if($plan_IMPORT_CATALOG_PRICES=="Y"){?>
			WebprostorCoreAddNewCellSelect(newRow, 'center', 'CATALOG_PRODUCT_PRICE[]', CatalogPricesValues, CatalogPricesLabels);
			<? } ?>
			<? if($plan_IMPORT_CATALOG_PRODUCT_OFFERS == "Y" && $isCatalogSKU) { ?>
			WebprostorCoreAddNewCellSelect(newRow, 'center', 'CATALOG_PRODUCT_OFFER_FIELD[]', CatalogFieldsValues, CatalogFieldsLabels);
			<? } ?>
			<?if($plan_IMPORT_CATALOG_PRODUCTS=="Y"){?>
			WebprostorCoreAddNewCellSelect(newRow, 'center', 'CATALOG_PRODUCT_FIELD[]', CatalogFieldsValues, CatalogFieldsLabels);
			<? } ?>
			<? if($plan_IMPORT_CATALOG_PRODUCT_OFFERS == "Y" && $isCatalogSKU) {?>
			WebprostorCoreAddNewCellSelect(newRow, 'center', 'IBLOCK_ELEMENT_OFFER_PROPERTY[]', IblockOffersPropertiesValues, IblockOffersPropertiesLabels);
			WebprostorCoreAddNewCellSelect(newRow, 'center', 'IBLOCK_ELEMENT_OFFER_FIELD[]', IblockFieldsValues, IblockFieldsLabels);
			<? } ?>
			<?if($plan_IMPORT_IBLOCK_PROPERTIES=="Y"){?>
			WebprostorCoreAddNewCellSelect(newRow, 'center', 'IBLOCK_ELEMENT_PROPERTY[]', IblockPropertiesValues, IblockPropertiesLabels);
			<? } ?>
			<?if($plan_IMPORT_IBLOCK_ELEMENTS=="Y"){?>
			WebprostorCoreAddNewCellSelect(newRow, 'center', 'IBLOCK_ELEMENT_FIELD[]', IblockFieldsValues, IblockFieldsLabels);
			<? } ?>
			<?if($plan_IMPORT_IBLOCK_SECTIONS=="Y"){?>
			<?if($plan_IMPORT_FORMAT == "XML") { ?>
			WebprostorCoreAddNewCellSelect(newRow, 'center', 'IBLOCK_SECTION_PARENT_FIELD[]', IblockSectionFieldsValues, IblockSectionFieldsLabels);
			<? } else {	?>
			WebprostorCoreAddNewCellSelect(newRow, 'center', 'IBLOCK_SECTION_DEPTH_LEVEL[]', sectionDepthLevelsValues, sectionDepthLevelsLabels);
			<? } ?>
			WebprostorCoreAddNewCellSelect(newRow, 'center', 'IBLOCK_SECTION_FIELD[]', IblockSectionFieldsValues, IblockSectionFieldsLabels);
			<? } ?>
			WebprostorCoreAddNewCellInput(newRow, 'center', 'number', 'SORT[]', 500);
			<?if($plan_IMPORT_FORMAT == "XML" || $plan_IMPORT_FORMAT == "JSON") { ?>
			WebprostorCoreAddNewCellInput(newRow, 'center', 'text', 'ENTITY_ATTRIBUTE[]');
			<? } ?>
			WebprostorCoreAddNewCellSelect(newRow, 'center', 'ACTIVE[]', CheckboxValues, CheckboxLabels);
			WebprostorCoreAddNewCellInput(newRow, 'center', '<?=($plan_IMPORT_FORMAT == "XML" && $plan_XML_USE_ENTITY_NAME == "Y") ? 'text' : 'hidden';?>', 'NAME[]', false, false, false, '<?=($plan_IMPORT_FORMAT == "XML" && $plan_XML_USE_ENTITY_NAME == "Y") ? 'entitiesArray' : '';?>', <?=$plan_IMPORT_FORMAT != "XML" ? 'true' : 'false';?>);
			<?if(($plan_IMPORT_FORMAT == "XML" && $plan_XML_USE_ENTITY_NAME != "Y") || $plan_IMPORT_FORMAT != "XML") { ?>
			WebprostorCoreAddNewCellSelect(newRow, 'center', 'ENTITY[]', EntitiesValues, EntitiesLabels);
			<? } ?>
			WebprostorCoreAddNewCellInput(newRow, 'center', 'hidden', 'CONNECTIONS_ID[]');
	
			$('#' + newRowId + ' select').select2({ 
				language: "ru",
			});
		}
		-->
		</script>
		<?
		if($plan_IMPORT_FORMAT == "XML" && $plan_XML_USE_ENTITY_NAME == "Y" && is_array($entitiesArray))
		{
			echo '<datalist id="entitiesArray">';
			foreach($entitiesArray as $value => $label)
			{
				echo '<option value="'.htmlspecialcharsbx(str_replace(["\r", "\n"], '', $label)).'" />';
			}
			echo '</datalist>';
		}
		?>
		<div style="overflow: hidden; width: 100%; display: table; table-layout: fixed;">
		<div id="table_CONNECTIONS_WRAPPER">
		<table class="internal" id="table_CONNECTIONS">
			<tbody>
				<tr class="heading">
					<td class="internal-left">ID</td>
					<?if(($plan_IMPORT_FORMAT == "XML" && $plan_XML_USE_ENTITY_NAME != "Y") || $plan_IMPORT_FORMAT != "XML") { ?>
					<td><?=Loc::getMessage("CONNECTIONS_TABLE_HEADING_ENTITY");?></td>
					<? } ?>
					<td><?=($plan_IMPORT_FORMAT == "XML" && $plan_XML_USE_ENTITY_NAME == "Y") ? Loc::getMessage("CONNECTIONS_TABLE_HEADING_TAG") : Loc::getMessage("CONNECTIONS_TABLE_HEADING_NAME").'<span data-hint="'.Loc::getMessage("CONNECTIONS_TABLE_HEADING_NAME_HINT").'"></span>';?></td>
					<td><?=Loc::getMessage("CONNECTIONS_TABLE_HEADING_ACTIVE");?><span data-hint="<?=Loc::getMessage("CONNECTIONS_TABLE_HEADING_ACTIVE_HINT");?>"></span></td>
					<?if($plan_IMPORT_FORMAT == "XML" || $plan_IMPORT_FORMAT == "JSON") { ?>
					<td><?=Loc::getMessage("CONNECTIONS_TABLE_HEADING_ENTITY_ATTRIBUTE");?> <span class="required" style="vertical-align: super; font-size: smaller;">1</span></td>
					<? } ?>
					<td><?=Loc::getMessage("CONNECTIONS_TABLE_HEADING_ENTITY_SORT");?></td>
					<?if($plan_IMPORT_IBLOCK_SECTIONS=="Y"){?>
					<td><?=Loc::getMessage("CONNECTIONS_TABLE_HEADING_IBLOCK_SECTION_FIELD");?></td>
					<?if($plan_IMPORT_FORMAT == "XML") { ?>
					<td><?=Loc::getMessage("CONNECTIONS_TABLE_HEADING_IBLOCK_SECTION_PARENT_FIELD");?></td>
					<? } else {	?>
					<td><?=Loc::getMessage("CONNECTIONS_TABLE_HEADING_IBLOCK_SECTION_DEPTH_LEVEL");?></td>
					<? } ?>
					<? } ?>
					<?if($plan_IMPORT_IBLOCK_ELEMENTS=="Y"){?>
					<td><?=Loc::getMessage("CONNECTIONS_TABLE_HEADING_IBLOCK_ELEMENT_FIELD");?></td>
					<? } ?>
					<?if($plan_IMPORT_IBLOCK_PROPERTIES=="Y"){?>
					<td><?=Loc::getMessage("CONNECTIONS_TABLE_HEADING_IBLOCK_ELEMENT_PROPERTY");?></td>
					<? } ?>
					<? if($plan_IMPORT_CATALOG_PRODUCT_OFFERS == "Y" && $isCatalogSKU) { ?>
					<td><?=Loc::getMessage("CONNECTIONS_TABLE_HEADING_IBLOCK_ELEMENT_OFFER_FIELD");?></td>
					<td><?=Loc::getMessage("CONNECTIONS_TABLE_HEADING_IBLOCK_ELEMENT_OFFER_PROPERTY");?></td>
					<? } ?>
					<?if($plan_IMPORT_CATALOG_PRODUCTS=="Y"){?>
					<td><?=Loc::getMessage("CONNECTIONS_TABLE_HEADING_CATALOG_PRODUCT_FIELD");?></td>
					<? } ?>
					<? if($plan_IMPORT_CATALOG_PRODUCT_OFFERS == "Y" && $isCatalogSKU) { ?>
					<td><?=Loc::getMessage("CONNECTIONS_TABLE_HEADING_CATALOG_PRODUCT_OFFER_FIELD");?></td>
					<? } ?>
					<?if($plan_IMPORT_CATALOG_PRICES=="Y"){?>
					<td><?=Loc::getMessage("CONNECTIONS_TABLE_HEADING_CATALOG_PRODUCT_PRICE");?></td>
					<? } ?>
					<?if($plan_IMPORT_CATALOG_STORE_AMOUNT=="Y"){?>
					<td><?=Loc::getMessage("CONNECTIONS_TABLE_HEADING_CATALOG_PRODUCT_STORE_AMOUNT");?></td>
					<? } ?>
					<?if($plan_IMPORT_HIGHLOAD_BLOCK_ENTITIES=="Y"){?>
					<td><?=Loc::getMessage("CONNECTIONS_TABLE_HEADING_HIGHLOAD_BLOCK_ENTITY");?></td>
					<? } ?>
					<td><?=Loc::getMessage("CONNECTIONS_TABLE_HEADING_IS_IMAGE");?><span data-hint="<?=Loc::getMessage("CONNECTIONS_TABLE_HEADING_IS_IMAGE_HINT");?>"></span></td>
					<td><?=Loc::getMessage("CONNECTIONS_TABLE_HEADING_IS_URL");?><span data-hint="<?=Loc::getMessage("CONNECTIONS_TABLE_HEADING_IS_URL_HINT");?>"></span></td>
					<td><?=Loc::getMessage("CONNECTIONS_TABLE_HEADING_IS_FILE");?><span data-hint="<?=Loc::getMessage("CONNECTIONS_TABLE_HEADING_IS_FILE_HINT");?>"></span></td>
					<td><?=Loc::getMessage("CONNECTIONS_TABLE_HEADING_IS_ARRAY");?><span data-hint="<?=Loc::getMessage("CONNECTIONS_TABLE_HEADING_IS_ARRAY_HINT");?>"></span></td>
					<td><?=Loc::getMessage("CONNECTIONS_TABLE_HEADING_IS_REQUIRED");?><span data-hint="<?=Loc::getMessage("CONNECTIONS_TABLE_HEADING_IS_REQUIRED_HINT");?>"></span></td>
					<td><?=Loc::getMessage("CONNECTIONS_TABLE_HEADING_USE_IN_SEARCH");?><span data-hint="<?=Loc::getMessage("CONNECTIONS_TABLE_HEADING_USE_IN_SEARCH_HINT");?>"></span></td>
					<td><?=Loc::getMessage("CONNECTIONS_TABLE_HEADING_USE_IN_CODE");?><span data-hint="<?=Loc::getMessage("CONNECTIONS_TABLE_HEADING_USE_IN_CODE_HINT");?>"></span></td>
					<?if($plan_IMPORT_IBLOCK_PROPERTIES=="Y"){?>
					<td><?=Loc::getMessage("CONNECTIONS_TABLE_HEADING_IBLOCK_ELEMENT_PROPERTY_E");?><span data-hint="<?=Loc::getMessage("CONNECTIONS_TABLE_HEADING_IBLOCK_ELEMENT_PROPERTY_E_HINT");?>"></span></td>
					<td><?=Loc::getMessage("CONNECTIONS_TABLE_HEADING_IBLOCK_ELEMENT_PROPERTY_G");?></td>
					<td><?=Loc::getMessage("CONNECTIONS_TABLE_HEADING_IBLOCK_ELEMENT_PROPERTY_M");?></td>
					<? } ?>
					<td><?=Loc::getMessage("CONNECTIONS_TABLE_HEADING_PROCESSING_TYPES");?></td>
					<td class="internal-right">
					</td>
				</tr>
				<?
				function CheckDisplay($data)
				{
					$display = 'table-row';
					$arrayCheck = Array(
						"SHOW_ACTIVE" => "ACTIVE",
						"SHOW_REQUIRED" => "IS_REQUIRED",
						"SHOW_IMAGE" => "IS_IMAGE",
						"SHOW_FILE" => "IS_FILE",
						"SHOW_URL" => "IS_URL",
						"SHOW_SEARCH" => "USE_IN_SEARCH",
					);
					
					foreach($arrayCheck as $code => $value)
					{
						if($_REQUEST[$code] && $_REQUEST[$code] != $data[$value])
						{
							$display = "none";
							break;
						}
					}
					return $display;
				}
				$connectionsRes = $CConnection->GetList(Array("SORT" => "ASC"), Array("PLAN_ID" => $ID));
				while($connectionArr = $connectionsRes->GetNext())
				{
					$CONNECTIONS_ID = $connectionArr["ID"];
					$ACTIVE = $connectionArr["ACTIVE"];
					$ENTITY = $connectionArr["ENTITY"];
					$ENTITY_ATTRIBUTE = $connectionArr["ENTITY_ATTRIBUTE"];
					$NAME = $connectionArr["NAME"];
					$SORT = $connectionArr["SORT"];
					$IBLOCK_ELEMENT_FIELD = $connectionArr["IBLOCK_ELEMENT_FIELD"];
					$IBLOCK_ELEMENT_PROPERTY = $connectionArr["IBLOCK_ELEMENT_PROPERTY"];
					$IBLOCK_ELEMENT_OFFER_FIELD = $connectionArr["IBLOCK_ELEMENT_OFFER_FIELD"];
					$IBLOCK_ELEMENT_OFFER_PROPERTY = $connectionArr["IBLOCK_ELEMENT_OFFER_PROPERTY"];
					$IBLOCK_SECTION_FIELD = $connectionArr["IBLOCK_SECTION_FIELD"];
					$IBLOCK_SECTION_DEPTH_LEVEL = $connectionArr["IBLOCK_SECTION_DEPTH_LEVEL"];
					$IBLOCK_SECTION_PARENT_FIELD = $connectionArr["IBLOCK_SECTION_PARENT_FIELD"];
					$CATALOG_PRODUCT_FIELD = $connectionArr["CATALOG_PRODUCT_FIELD"];
					$CATALOG_PRODUCT_OFFER_FIELD = $connectionArr["CATALOG_PRODUCT_OFFER_FIELD"];
					$CATALOG_PRODUCT_PRICE = $connectionArr["CATALOG_PRODUCT_PRICE"];
					$CATALOG_PRODUCT_STORE_AMOUNT = $connectionArr["CATALOG_PRODUCT_STORE_AMOUNT"];
					$HIGHLOAD_BLOCK_ENTITY_FIELD = $connectionArr["HIGHLOAD_BLOCK_ENTITY_FIELD"];
					$IS_IMAGE = $connectionArr["IS_IMAGE"];
					$IS_FILE = $connectionArr["IS_FILE"];
					$IS_URL = $connectionArr["IS_URL"];
					$IS_ARRAY = $connectionArr["IS_ARRAY"];
					$IS_REQUIRED = $connectionArr["IS_REQUIRED"];
					$USE_IN_SEARCH = $connectionArr["USE_IN_SEARCH"];
					$USE_IN_CODE = $connectionArr["USE_IN_CODE"];
					$IBLOCK_ELEMENT_PROPERTY_E = $connectionArr["IBLOCK_ELEMENT_PROPERTY_E"];
					$IBLOCK_ELEMENT_PROPERTY_G = $connectionArr["IBLOCK_ELEMENT_PROPERTY_G"];
					$IBLOCK_ELEMENT_PROPERTY_M = $connectionArr["IBLOCK_ELEMENT_PROPERTY_M"];
					$PROCESSING_TYPES = $connectionArr["PROCESSING_TYPES"];
				?>
				<tr class="data-row" id="row_connection_<?=$CONNECTIONS_ID;?>" onClick="javascript:CheckRowClass(this);" style="display: <?=CheckDisplay($connectionArr);?>">
					<td align="center">
						<input type="hidden" name="CONNECTIONS_ID[]" value="<?=$CONNECTIONS_ID;?>" />
						<?=$CONNECTIONS_ID;?>
					</td>
					<?if(($plan_IMPORT_FORMAT == "XML" && $plan_XML_USE_ENTITY_NAME != "Y") || $plan_IMPORT_FORMAT != "XML") { ?>
					<td align="center">
						<?if(is_array($entitiesArray) && count($entitiesArray)) { ?>
						<select name="ENTITY[]" class="select-search-entities">
							<?if($ENTITY) {?>
							<option value="<?=$ENTITY?>" selected><?=$entitiesArray[$ENTITY]?> [<?=$ENTITY?>]</option>
							<? } ?>
						</select>
						<?} else {?>
						<input type="hidden" name="ENTITY[]" value="<?=$ENTITY?>" />
						<em><?=$NAME?> [<?=$ENTITY?>]</em>
						<? } ?>
					</td>
					<? } ?>
					<td align="center">
						<input type="text" name="NAME[]" class="adm-input" value="<?=$NAME;?>"<?if(($plan_IMPORT_FORMAT == "XML" && $plan_XML_USE_ENTITY_NAME != "Y") || $plan_IMPORT_FORMAT != "XML") { ?> disabled=""<? } else { ?> list="entitiesArray"<? } ?>/>
						<?if($plan_IMPORT_FORMAT == "XML" && $plan_XML_USE_ENTITY_NAME == "Y"){?>
						<input type="hidden" name="ENTITY[]" value="<?=$ENTITY?>" />
						<? } ?>
					</td>
					<td align="center">
						<select name="ACTIVE[]" class="select-search">
							<option value="N"><?=Loc::getMessage("OPTION_CHECKBOX_NO_ACTIVE");?></option>
							<option value="Y"<?if($ACTIVE == "Y") echo ' selected';?>><?=Loc::getMessage("OPTION_CHECKBOX_YES");?></option>
						</select>
					</td>
					<?if($plan_IMPORT_FORMAT == "XML" || $plan_IMPORT_FORMAT == "JSON") { ?>
					<td align="center">
						<input type="text" name="ENTITY_ATTRIBUTE[]" class="adm-input" value="<?=$ENTITY_ATTRIBUTE;?>" />
					</td>
					<? } ?>
					<td align="center">
						<input type="number" name="SORT[]" class="adm-input" value="<?=$SORT;?>" />
					</td>
					<?if($plan_IMPORT_IBLOCK_SECTIONS=="Y"){?>
					<td align="center">
						<select name="IBLOCK_SECTION_FIELD[]" class="select-search-sections">
							<option value=""><?=Loc::getMessage("MESSAGE_DO_NOT_USE");?></option>
							<?if($IBLOCK_SECTION_FIELD) {?>
							<option value="<?=$IBLOCK_SECTION_FIELD?>" selected><?=$iblockSectionFields[$IBLOCK_SECTION_FIELD]?></option>
							<? } ?>
						</select>
					</td>
					<?if($plan_IMPORT_FORMAT == "XML") { ?>
					<td align="center">
						<select name="IBLOCK_SECTION_PARENT_FIELD[]" class="select-search-sections">
							<option value=""><?=Loc::getMessage("MESSAGE_DO_NOT_USE");?></option>
							<?if($IBLOCK_SECTION_PARENT_FIELD) {?>
							<option value="<?=$IBLOCK_SECTION_PARENT_FIELD?>" selected><?=$iblockSectionFields[$IBLOCK_SECTION_PARENT_FIELD]?></option>
							<? } ?>
						</select>
					</td>
					<? } else {	?>
					<td align="center">
						<select name="IBLOCK_SECTION_DEPTH_LEVEL[]" class="select-search-section-depth">
							<option value=""><?=Loc::getMessage("MESSAGE_DO_NOT_USE");?></option>
							<?if($IBLOCK_SECTION_DEPTH_LEVEL) {?>
							<option value="<?=$IBLOCK_SECTION_DEPTH_LEVEL?>" selected><?=$sectionDepthLevels[$IBLOCK_SECTION_DEPTH_LEVEL]?></option>
							<? } ?>
						</select>
					</td>
					<? } ?>
					<? } ?>
					<?if($plan_IMPORT_IBLOCK_ELEMENTS=="Y"){?>
					<td align="center">
						<select name="IBLOCK_ELEMENT_FIELD[]" class="select-search-elements">
							<option value=""><?=Loc::getMessage("MESSAGE_DO_NOT_USE");?></option>
							<?if($IBLOCK_ELEMENT_FIELD) {?>
							<option value="<?=$IBLOCK_ELEMENT_FIELD?>" selected><?=$iblockElementFields[$IBLOCK_ELEMENT_FIELD]?></option>
							<? } ?>
						</select>
					</td>
					<? } ?>
					<?if($plan_IMPORT_IBLOCK_PROPERTIES=="Y"){?>
					<td align="center">
						<select name="IBLOCK_ELEMENT_PROPERTY[]" class="select-search-properties">
							<option value=""><?=Loc::getMessage("MESSAGE_DO_NOT_USE");?></option>
							<?if($IBLOCK_ELEMENT_PROPERTY) {?>
							<option value="<?=$IBLOCK_ELEMENT_PROPERTY?>" selected><?=$propertiesArray[$IBLOCK_ELEMENT_PROPERTY]?></option>
							<? } ?>
						</select>
					</td>
					<? } ?>
					<? if($plan_IMPORT_CATALOG_PRODUCT_OFFERS == "Y" && $isCatalogSKU) { ?>
					<td align="center">
						<select name="IBLOCK_ELEMENT_OFFER_FIELD[]" class="select-search-elements">
							<option value=""><?=Loc::getMessage("MESSAGE_DO_NOT_USE");?></option>
							<?if($IBLOCK_ELEMENT_OFFER_FIELD) {?>
							<option value="<?=$IBLOCK_ELEMENT_OFFER_FIELD?>" selected><?=$iblockElementFields[$IBLOCK_ELEMENT_OFFER_FIELD]?></option>
							<? } ?>
						</select>
					</td>
					<td align="center">
						<select name="IBLOCK_ELEMENT_OFFER_PROPERTY[]" class="select-search-properties-offer">
							<option value=""><?=Loc::getMessage("MESSAGE_DO_NOT_USE");?></option>
							<?if($IBLOCK_ELEMENT_OFFER_PROPERTY) {?>
							<option value="<?=$IBLOCK_ELEMENT_OFFER_PROPERTY?>" selected><?=$offersPropertiesArray[$IBLOCK_ELEMENT_OFFER_PROPERTY]?></option>
							<? } ?>
						</select>
					</td>
					<? } ?>
					<?if($plan_IMPORT_CATALOG_PRODUCTS=="Y"){?>
					<td align="center">
						<select name="CATALOG_PRODUCT_FIELD[]" class="select-search-catalog-product">
							<option value=""><?=Loc::getMessage("MESSAGE_DO_NOT_USE");?></option>
							<?if($CATALOG_PRODUCT_FIELD) {?>
							<option value="<?=$CATALOG_PRODUCT_FIELD?>" selected><?=$catalogProductFields[$CATALOG_PRODUCT_FIELD]?></option>
							<? } ?>
						</select>
					</td>
					<? } ?>
					<? if($plan_IMPORT_CATALOG_PRODUCT_OFFERS == "Y" && $isCatalogSKU) { ?>
					<td align="center">
						<select name="CATALOG_PRODUCT_OFFER_FIELD[]" class="select-search-catalog-product">
							<option value=""><?=Loc::getMessage("MESSAGE_DO_NOT_USE");?></option>
							<?if($CATALOG_PRODUCT_OFFER_FIELD) {?>
							<option value="<?=$CATALOG_PRODUCT_OFFER_FIELD?>" selected><?=$catalogProductFields[$CATALOG_PRODUCT_OFFER_FIELD]?></option>
							<? } ?>
						</select>
					</td>
					<? } ?>
					<?if($plan_IMPORT_CATALOG_PRICES=="Y"){?>
					<td align="center">
						<select name="CATALOG_PRODUCT_PRICE[]" class="select-search-price">
							<option value=""><?=Loc::getMessage("MESSAGE_DO_NOT_USE");?></option>
							<?if($CATALOG_PRODUCT_PRICE) {?>
							<option value="<?=$CATALOG_PRODUCT_PRICE?>" selected><?=$catalogProductPrice[$CATALOG_PRODUCT_PRICE]?></option>
							<? } ?>
						</select>
					</td>
					<? } ?>
					<?if($plan_IMPORT_CATALOG_STORE_AMOUNT=="Y"){?>
					<td align="center">
						<select name="CATALOG_PRODUCT_STORE_AMOUNT[]" class="select-search-store">
							<option value=""><?=Loc::getMessage("MESSAGE_DO_NOT_USE");?></option>
							<?if($CATALOG_PRODUCT_STORE_AMOUNT) {?>
							<option value="<?=$CATALOG_PRODUCT_STORE_AMOUNT?>" selected><?=$catalogProductStore[$CATALOG_PRODUCT_STORE_AMOUNT]?></option>
							<? } ?>
						</select>
					</td>
					<? } ?>
					<?if($plan_IMPORT_HIGHLOAD_BLOCK_ENTITIES=="Y"){?>
					<td align="center">
						<select name="HIGHLOAD_BLOCK_ENTITY_FIELD[]" class="select-search-highload">
							<option value=""><?=Loc::getMessage("MESSAGE_DO_NOT_USE");?></option>
							<?if($HIGHLOAD_BLOCK_ENTITY_FIELD) {?>
							<option value="<?=$HIGHLOAD_BLOCK_ENTITY_FIELD?>" selected><?=$highloadBlockEntityFields[$HIGHLOAD_BLOCK_ENTITY_FIELD]?></option>
							<? } ?>
						</select>
					</td>
					<? } ?>
					<td align="center">
						<select name="IS_IMAGE[]" class="select-search">
							<option value="N"><?=Loc::getMessage("OPTION_CHECKBOX_NO");?></option>
							<option value="Y"<?if($IS_IMAGE == "Y") echo ' selected';?>><?=Loc::getMessage("OPTION_CHECKBOX_YES");?></option>
						</select>
					</td>
					<td align="center">
						<select name="IS_URL[]" class="select-search">
							<option value="N"><?=Loc::getMessage("OPTION_CHECKBOX_NO");?></option>
							<option value="Y"<?if($IS_URL == "Y") echo ' selected';?>><?=Loc::getMessage("OPTION_CHECKBOX_YES");?></option>
						</select>
					</td>
					<td align="center">
						<select name="IS_FILE[]" class="select-search">
							<option value="N"><?=Loc::getMessage("OPTION_CHECKBOX_NO");?></option>
							<option value="Y"<?if($IS_FILE == "Y") echo ' selected';?>><?=Loc::getMessage("OPTION_CHECKBOX_YES");?></option>
						</select>
					</td>
					<td align="center">
						<select name="IS_ARRAY[]" class="select-search">
							<option value="N"><?=Loc::getMessage("OPTION_CHECKBOX_NO");?></option>
							<option value="Y"<?if($IS_ARRAY == "Y") echo ' selected';?>><?=Loc::getMessage("OPTION_CHECKBOX_YES");?></option>
						</select>
					</td>
					<td align="center">
						<select name="IS_REQUIRED[]" class="select-search">
							<option value="N"><?=Loc::getMessage("OPTION_CHECKBOX_NO");?></option>
							<option value="Y"<?if($IS_REQUIRED == "Y") echo ' selected';?>><?=Loc::getMessage("OPTION_CHECKBOX_YES");?></option>
						</select>
					</td>
					<td align="center">
						<select name="USE_IN_SEARCH[]" class="select-search">
							<option value="N"><?=Loc::getMessage("OPTION_CHECKBOX_NO");?></option>
							<option value="Y"<?if($USE_IN_SEARCH == "Y") echo ' selected';?>><?=Loc::getMessage("OPTION_CHECKBOX_YES");?></option>
						</select>
					</td>
					<?//if($plan_IMPORT_FORMAT != "XML") {?>
					<td align="center">
						<select name="USE_IN_CODE[]" class="select-search-code">
							<?if($USE_IN_CODE) {?>
							<option value="<?=$USE_IN_CODE?>" selected><?=$useInCodeValues[$USE_IN_CODE]?></option>
							<? } ?>
						</select>
					</td>
					<?// } ?>
					<?if($plan_IMPORT_IBLOCK_PROPERTIES=="Y"){?>
					<td align="center">
						<select name="IBLOCK_ELEMENT_PROPERTY_E[]" class="select-search-elements">
							<option value=""><?=Loc::getMessage("MESSAGE_DO_NOT_USE");?></option>
							<?if($IBLOCK_ELEMENT_PROPERTY_E) {?>
							<option value="<?=$IBLOCK_ELEMENT_PROPERTY_E?>" selected><?=$iblockElementFields[$IBLOCK_ELEMENT_PROPERTY_E]?></option>
							<? } ?>
						</select>
					</td>
					<td align="center">
						<select name="IBLOCK_ELEMENT_PROPERTY_G[]" class="select-search-sections">
							<option value=""><?=Loc::getMessage("MESSAGE_DO_NOT_USE");?></option>
							<?if($IBLOCK_ELEMENT_PROPERTY_G) {?>
							<option value="<?=$IBLOCK_ELEMENT_PROPERTY_G?>" selected><?=$iblockSectionFields[$IBLOCK_ELEMENT_PROPERTY_G]?></option>
							<? } ?>
						</select>
					</td>
					<td align="center">
						<select name="IBLOCK_ELEMENT_PROPERTY_M[]" class="select-search">
							<option value=""><?=Loc::getMessage("MESSAGE_DO_NOT_USE");?></option>
							<?
							foreach($coordinates as $k => $field)
							{
							?>
							<option value="<?=$k?>" <?if($IBLOCK_ELEMENT_PROPERTY_M==$k) echo 'selected';?>><?=$field?> [<?=$k;?>]</option>
							<?
							}
							?>
						</select>
					</td>
					<? } ?>
					<td align="center">
					<?
					if($PROCESSING_TYPES)
					{
						$processingTypesArr = unserialize(base64_decode($PROCESSING_TYPES));
						if(!is_array($processingTypesArr))
							$processingTypesArr = Array();
					}
					else
					{
						$processingTypesArr = array();
					}
					?>
						<select name="PROCESSING_TYPES[<?=$CONNECTIONS_ID?>][]" class="select-search-processing" multiple>
							<?/*<option value=""><?=Loc::getMessage("MESSAGE_DO_NOT_USE");?></option>*/?>
							<?
							if(is_array($processingTypesArr))
							{
							foreach($processingTypesArr as $procId)
							{
							?>
							<option value="<?=$procId?>" selected><?=$listTypes[$procId]?></option>
							<?
							}
							}
							?>
						</select>
					</td>
					<td align="center">
						<button class="ui-btn ui-btn-danger ui-btn-icon-remove" type="button" onClick="WebprostorCoreRemoveRow('row_connection_<?=$CONNECTIONS_ID;?>')" title="<?=Loc::getMessage("BTN_DELETE_CONNECTION");?>"<?=$moduleAccessLevel != 'W'?' disabled=""':''?> /></button>
					</td>
				</tr>
				<?
				}
				?>
			</tbody>
		</table>
		</div>
		</div>
		<script>
		BX.ready(function(){
			BX.UI.Hint.init(BX('table_CONNECTIONS'));
			const ears = new BX.UI.Ears({
				container: document.querySelector('#table_CONNECTIONS_WRAPPER'),
				smallSize: true,
				noScrollbar: false
			});
			ears.init();
		});
		</script>
	</td>
</tr>
<tr>
	<td colspan="2" align="left" style="padding-top: 10px;">
		<button class="ui-btn ui-btn-icon-add ui-btn-light-border" type="button" onClick="javascript:AddConnectionRow('table_CONNECTIONS');" value="Y"<?if(!is_array($entitiesArray) || count($entitiesArray) == 0 || $moduleAccessLevel != 'W'):?> disabled<?else:?>style="position: sticky; left: 10px;"<?endif;?>><?=Loc::getMessage("BTN_ADD_CONNECTION");?></button>
	</td>
</tr>
<? } ?>
<?
$tabControl->Buttons();
?>
<button 
title="<?=Loc::getMessage("SAVE_TITLE")?>" 
class="ui-btn ui-btn-success" 
type="submit" 
value="Y" 
name="save"
<?=$moduleAccessLevel>="W" && $ID > 0 ?"":" disabled"?>
>
	<?=Loc::getMessage("SAVE")?>
</button>
<button 
title="<?=Loc::getMessage("APPLY_TITLE")?>" 
class="ui-btn ui-btn-primary" 
type="submit" 
value="Y" 
name="apply"
<?=$moduleAccessLevel>="W" && $ID > 0?"":" disabled"?>
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
<input type="hidden" name="SHOW_ACTIVE" value="<?=$SHOW_ACTIVE?>">
<input type="hidden" name="SHOW_REQUIRED" value="<?=$SHOW_REQUIRED?>">
<input type="hidden" name="SHOW_IMAGE" value="<?=$SHOW_IMAGE?>">
<input type="hidden" name="SHOW_FILE" value="<?=$SHOW_FILE?>">
<input type="hidden" name="SHOW_URL" value="<?=$SHOW_URL?>">
<input type="hidden" name="SHOW_SEARCH" value="<?=$SHOW_SEARCH?>">
<?
if($ID>0) {
?>
  <input type="hidden" name="ID" value="<?=$ID?>">
<?
}

$tabControl->End();

if($ID>0)
{
	if($plan_IMPORT_FORMAT == "XML")
	{
		echo CWebprostorCoreFunctions::showAlertBegin('warning', false);
		if(is_array($entitiesArray) && $plan_XML_USE_ENTITY_NAME == "Y")
		{
			echo Loc::getMessage("ENTITY_ATTRIBUTE_NOTE_0");
			echo '<ul>';
			foreach($entitiesArray as $value => $label)
			{
				echo '<li>'.htmlspecialcharsbx(str_replace(["\r", "\n"], '', $label)).'</li>';
			}
			echo '</ul>';
		}
		echo Loc::getMessage("ENTITY_ATTRIBUTE_NOTE_1");
		foreach($attributesArray as $k => $attributeGroup)
		{
			echo '<strong>'.$k.':</strong><br />';
			echo '<ul>';
			foreach($attributeGroup as $k2 => $attributeName)
			{
				echo '<li>'.$attributeName.'</li>';
			}
			echo '</ul>';
		}
		echo CWebprostorCoreFunctions::showAlertEnd();
	}
	elseif($scriptData)
	{
		$sample = $scriptData->GetSample($ID);
		if(is_array($entitiesArray) && is_array($sample) && count($entitiesArray) == count($sample))
		{
			echo '<h3>'.Loc::getMessage('WEBPROSTOR_IMPORT_NOTE_3').'</h3>';
			echo '<div class="adm-list-table-wrap"><table class="adm-list-table"><thead><tr class="adm-list-table-header"><td class="adm-list-table-cell"><div class="adm-list-table-cell-inner">'.Loc::getMessage('WEBPROSTOR_IMPORT_NOTE_4').'</div></td><td class="adm-list-table-cell"><div class="adm-list-table-cell-inner">'.Loc::getMessage('WEBPROSTOR_IMPORT_NOTE_5').'</div></td></tr></thead><tbody>';
			foreach($entitiesArray as $key => $thead)
			{
				echo '<tr class="adm-list-table-row"><td class="adm-list-table-cell align-left">'.$thead.'</td><td class="adm-list-table-cell align-left">';
				if(is_array($sample[$key]))
				{
					foreach($sample[$key] as $key2 => $value2)
					{
						echo '<strong>'.$key2.'</strong>: '.$value2.'<br />';
					}
				}
				else
					echo $sample[$key];
				echo '</td></tr>';
			}
			echo '</tbody></table></div>';
			if($plan_IMPORT_FORMAT == "JSON")
			{
				echo CWebprostorCoreFunctions::showAlertBegin('warning', false);
				echo Loc::getMessage("ENTITY_ATTRIBUTE_NOTE_1");
				echo CWebprostorCoreFunctions::showAlertEnd();
			}
		}
	}
}
	
$tabControl->ShowWarnings("PLAN_EDIT", $message);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");