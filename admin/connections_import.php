<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/data.import/prolog.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/data.import/include.php");

use Bitrix\Main\Localization\Loc,
	Bitrix\Main\Loader,
	Bitrix\Highloadblock\HighloadBlockTable;

$module_id = 'data.import';
$moduleAccessLevel = $APPLICATION->GetGroupRight($module_id);

if ($moduleAccessLevel == "D")
    $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));

if($back_url=='')
{
	//$back_url = '/bitrix/admin/data.import_connections.php?lang='.$lang;
	$back_url = '/bitrix/admin/data.import_plan_edit.php?ID='.$PLAN_ID.'&lang='.$lang;
}

$strWarning = "";

$aTabs = array(
  array("DIV" => "main", "TAB" => Loc::getMessage("ELEMENT_TAB"), "ICON"=>"", "TITLE"=>Loc::getMessage("ELEMENT_TAB_TITLE")),
);

$tabControl = new CAdminTabControl("tabControl", $aTabs);

$PLAN_ID = intval($PLAN_ID);
$pData = new CDataImportPlan;
$cData = new CDataImportPlanConnections;

if($_SERVER["REQUEST_METHOD"] == "POST" && strlen($Update)>0 && check_bitrix_sessid() && $moduleAccessLevel == 'W' && $NO_PLAN_ID != 'Y')
{
	if($PLAN_ID>0)
	{
		$plan = $pData->GetById($PLAN_ID);
		if(!$plan->ExtractFields("plan_"))
			$PLAN_ID=0;
		
		if($PLAN_ID>0)
		{
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
			
			$entitiesArray = $scriptData->GetEntities($PLAN_ID);
			
			if($plan_IMPORT_FORMAT == "XML")
			{
				$attributesArray = $entitiesArray["ATTRIBUTES"];
				$entitiesArray = $entitiesArray["KEYS"];
			}
			
			if(isset($ENTITIES) && is_array($ENTITIES))
			{
				$entitiesArray_temp = [];
				foreach($ENTITIES as $ENTITY_ID)
				{
					$entitiesArray_temp[$ENTITY_ID] = $entitiesArray[$ENTITY_ID];
				}
				$entitiesArray = $entitiesArray_temp;
				unset($entitiesArray_temp);
			}
			else
				$strWarning.= Loc::getMessage("ERROR_NO_IMPORT_CONNECTIONS");
			
			$CConnectionFields = new CDataImportPlanConnectionsFields;
			
			if($plan_IMPORT_IBLOCK_SECTIONS=="Y" || $plan_IMPORT_IBLOCK_PROPERTIES=="Y")
				$iblockSectionFields = $CConnectionFields->GetFields("SECTION", $plan_IBLOCK_ID);
			
			if($plan_IMPORT_IBLOCK_ELEMENTS=="Y" || $plan_IMPORT_IBLOCK_PROPERTIES=="Y" || $plan_IMPORT_CATALOG_PRODUCT_OFFERS=="Y")
				$iblockElementFields = $CConnectionFields->GetFields("ELEMENT");
		
			if($plan_IMPORT_IBLOCK_PROPERTIES == "Y")
			{
				$propertiesArray = Array();
				$propRes = CIBlockProperty::GetList(Array("ID"=>"asc", "name"=>"asc"), Array("IBLOCK_ID"=>$plan_IBLOCK_ID));
				while($propFields = $propRes->GetNext())
				{
					$propertiesArray[$propFields["ID"]] = htmlspecialcharsbx($propFields["NAME"]);
				}
			}
		
			if($plan_IMPORT_CATALOG_PRODUCTS=="Y")
				$catalogProductFields = $CConnectionFields->GetFields("PRODUCT");
			
			if($plan_IMPORT_CATALOG_PRICES=="Y")
				$catalogProductPrice = $CConnectionFields->GetFields("PRICE");
			
			if($plan_IMPORT_HIGHLOAD_BLOCK_ENTITIES=="Y")
				$highloadBlockEntityFields = $CConnectionFields->GetFields("ENTITIES", false, $plan_HIGHLOAD_BLOCK);
			
			$compareProperties = false;
			if($COMPARE_WITH_PROPERTIES == 'Y' || $COMPARE_WITH_PROPERTIES_OFFERS == 'Y')
			{
				$transParams = Array(
					"change_case" => "U",
					"replace_space" => "_",
					"replace_other" => ""
				);
				$transParamsHB = Array(
					"change_case" => "L",
					"replace_space" => "",
					"replace_other" => ""
				);
				
				$ibp = new \CIBlockProperty;
				$obUserField = new \CUserTypeEntity();
				
				if($COMPARE_WITH_PROPERTIES == 'Y')
				{
					$compareProperties_IBLOCK_ID = $plan_IBLOCK_ID;
				}
				elseif($COMPARE_WITH_PROPERTIES_OFFERS == 'Y' && CModule::IncludeModule("catalog"))
				{
					$catalogSKU = CCatalogSKU::GetInfoByProductIBlock($plan_IBLOCK_ID);
					
					$compareProperties_IBLOCK_ID = $catalogSKU["IBLOCK_ID"];
				}
				
				if(isset($compareProperties_IBLOCK_ID) && $compareProperties_IBLOCK_ID > 0)
				{
					$compareProperties = true;
				}
			}
			
			if(is_array($entitiesArray) && count($entitiesArray))
			{
				$sort = isset($START_SORT)?intVal($START_SORT):0;
				
				foreach($entitiesArray as $ENTITY => $NAME)
				{
					$NAME = trim($NAME);
					
					$connectionFields = Array();
					$connectionFields["ACTIVE"] = $ACTIVE;
					$connectionFields["ENTITY"] = $ENTITY;
					$connectionFields["NAME"] = $NAME;
					$connectionFields["PLAN_ID"] = $PLAN_ID;
					$connectionFields["SORT"] = $sort;
					
					if($compareProperties)
					{
						if($UPPERCASE_FIRST_SYMBOL == 'Y')
							$NAME = CDataCoreFunctions::mb_ucfirst($NAME);
						
						$PROPERTY_ID = false;
						$PROPERTY_CODE = Cutil::translit($NAME, LANGUAGE_ID, $transParams);
						
						$properties = $ibp->GetList(
							["sort" => "asc"], 
							["IBLOCK_ID" => $compareProperties_IBLOCK_ID, "NAME" => $NAME]
						);
						while ($prop_fields = $properties->GetNext())
						{
							$PROPERTY_ID = $prop_fields["ID"];
						}
						
						if(!$PROPERTY_ID)
						{
							$properties = $ibp->GetList(
								["sort" => "asc"], 
								["IBLOCK_ID" => $compareProperties_IBLOCK_ID, "CODE" => $PROPERTY_CODE]
							);
							while ($prop_fields = $properties->GetNext())
							{
								$PROPERTY_ID = $prop_fields["ID"];
							}
						}
						
						$PROPERTY_TYPE = 'S';
						$PROPERTY_MULTIPLE = $PROPERTY_MULTIPLE == 'Y' ? 'Y' : 'N';
						$USER_TYPE = '';
						$USER_TYPE_SETTINGS = '';
						$propertyTypeArr = explode(':', $PROPERTY_TYPE_USER_TYPE, 2);
						if($propertyTypeArr[0] != '')
							$PROPERTY_TYPE = $propertyTypeArr[0];
						if(isset($propertyTypeArr[1]) && $propertyTypeArr[1] != '')
							$USER_TYPE = $propertyTypeArr[1];
						if($USER_TYPE == 'directory' && Loader::includeModule("highloadblock"))
						{
							$highloadBlockTableName = "b_".strtolower($PROPERTY_CODE);
							$highloadBlockName = ucwords(Cutil::translit($NAME, LANGUAGE_ID, $transParamsHB));
							$highloadBlock = HighloadBlockTable::getList(array(
								'select' => array("TABLE_NAME"), 
								'filter' => array("TABLE_NAME" => $highloadBlockTableName), 
								'order' => array('ID' => 'ASC'), 
								'limit' => '1', 
							))->fetch();
							
							if(!$highloadBlock)
							{
								$result = Bitrix\Highloadblock\HighloadBlockTable::add(array(
									'NAME' => $highloadBlockName,
									'TABLE_NAME' => $highloadBlockTableName,
								));
								if (!$result->isSuccess()) {
									$strWarning.= implode(', ', $result->getErrorMessages());
									continue;
								}
								else
								{
									$highBlockID = $result->getId();
									$arFieldsName = [
										'UF_NAME' => [
											'USER_TYPE_ID' => 'string',
											'MANDATORY' => 'Y',
											'LABEL' => Loc::getMessage('TABLE_HEADING_PROPERTY_TYPE_USER_TYPE_S_DIRECTORY_NAME'),
										],
										'UF_SORT' => [
											'USER_TYPE_ID' => 'integer',
											'MANDATORY' => 'N',
											'LABEL' => Loc::getMessage('TABLE_HEADING_PROPERTY_TYPE_USER_TYPE_S_DIRECTORY_SORT'),
										],
										'UF_XML_ID' => [
											'USER_TYPE_ID' => 'string',
											'MANDATORY' => 'Y',
											'LABEL' => Loc::getMessage('TABLE_HEADING_PROPERTY_TYPE_USER_TYPE_S_DIRECTORY_XML_ID'),
										],
										'UF_FILE' => [
											'USER_TYPE_ID' => 'file',
											'MANDATORY' => 'N',
											'LABEL' => Loc::getMessage('TABLE_HEADING_PROPERTY_TYPE_USER_TYPE_S_DIRECTORY_FILE'),
										],
										'UF_LINK' => [
											'USER_TYPE_ID' => 'string',
											'MANDATORY' => 'N',
											'LABEL' => Loc::getMessage('TABLE_HEADING_PROPERTY_TYPE_USER_TYPE_S_DIRECTORY_LINK'),
										],
										'UF_DEF' => [
											'USER_TYPE_ID' => 'boolean',
											'MANDATORY' => 'N',
											'LABEL' => Loc::getMessage('TABLE_HEADING_PROPERTY_TYPE_USER_TYPE_S_DIRECTORY_DEF'),
										],
										'UF_DESCRIPTION' => [
											'USER_TYPE_ID' => 'string',
											'MANDATORY' => 'N',
											'LABEL' => Loc::getMessage('TABLE_HEADING_PROPERTY_TYPE_USER_TYPE_S_DIRECTORY_DESCRIPTION'),
										],
										'UF_FULL_DESCRIPTION' => [
											'USER_TYPE_ID' => 'string',
											'MANDATORY' => 'N',
											'LABEL' => Loc::getMessage('TABLE_HEADING_PROPERTY_TYPE_USER_TYPE_S_DIRECTORY_FULL_DESCRIPTION'),
										],
									];
									$intSortStep = 100;
									foreach($arFieldsName as $fieldName => $fieldValue)
									{
										$arUserField = array(
											"ENTITY_ID" => "HLBLOCK_".$highBlockID,
											"FIELD_NAME" => $fieldName,
											"USER_TYPE_ID" => $fieldValue['USER_TYPE_ID'],
											"XML_ID" => "",
											"SORT" => $intSortStep,
											"MULTIPLE" => "N",
											"MANDATORY" => $fieldValue['MANDATORY'],
											"SHOW_FILTER" => "N",
											"SHOW_IN_LIST" => "Y",
											"EDIT_IN_LIST" => "Y",
											"IS_SEARCHABLE" => "N",
											"SETTINGS" => array(),
										);
										$arUserField["EDIT_FORM_LABEL"] = [
											LANGUAGE_ID => $fieldValue['LABEL'],
										];
										$arUserField["LIST_COLUMN_LABEL"] = $arUserField["EDIT_FORM_LABEL"];
										$arUserField["LIST_FILTER_LABEL"] = $arUserField["EDIT_FORM_LABEL"];
										$obUserField->Add($arUserField);
										$intSortStep += 100;
									}
								}
							}
							
							$USER_TYPE_SETTINGS = [
								"size" => "1", 
								"width" => "0", 
								"group" => "N", 
								"multiple" => "N", 
								"TABLE_NAME" => $highloadBlockTableName,
							];
						}
						
						if(!$PROPERTY_ID)
						{
							$arPropertyFields =[
								"NAME" => $NAME,
								"ACTIVE" => "Y",
								"CODE" => $PROPERTY_CODE,
								"SORT" => 500,
								"MULTIPLE" => $PROPERTY_MULTIPLE,
								"PROPERTY_TYPE" => $PROPERTY_TYPE,
								"USER_TYPE" => $USER_TYPE,
								"USER_TYPE_SETTINGS" => $USER_TYPE_SETTINGS,
								"IBLOCK_ID" => $compareProperties_IBLOCK_ID,
								"FEATURES" => [
									'n_0' => [
										"IS_ENABLED" => "Y",
										"ID" => 'n_0',
										"MODULE_ID" => "iblock",
										"FEATURE_ID" => "DETAIL_PAGE_SHOW",
									],
								],
							];
				
							$properties = $ibp->GetList(
								["sort" => "desc"], 
								["IBLOCK_ID" => $compareProperties_IBLOCK_ID]
							);
							$propMax = $properties->Fetch();
							
							if($propMax["SORT"])
								$arPropertyFields['SORT'] = $propMax["SORT"] + 100;
				
							$PROPERTY_ID = $ibp->Add($arPropertyFields);
						}
			
						if($PROPERTY_ID)
						{
							if($COMPARE_WITH_PROPERTIES == 'Y')
								$connectionFields["IBLOCK_ELEMENT_PROPERTY"] = $PROPERTY_ID;
							elseif($COMPARE_WITH_PROPERTIES_OFFERS == 'Y')
								$connectionFields["IBLOCK_ELEMENT_OFFER_PROPERTY"] = $PROPERTY_ID;
						}
						
						unset($PROPERTY_ID, $PROPERTY_CODE);
					}
					else
					{
						$ENTITY_REAL_NAME = substr($NAME, 3, strlen($NAME));
						
						switch(substr($NAME, 0, 3))
						{
							case("IC_"):
								$GROUP_DEPTH_LEVEL = intVal(substr($ENTITY_REAL_NAME, 5, strlen($ENTITY_REAL_NAME)));
								$connectionFields["IBLOCK_SECTION_FIELD"] = "NAME";
								$connectionFields["IBLOCK_SECTION_DEPTH_LEVEL"] = ++$GROUP_DEPTH_LEVEL;
								$connectionFields["USE_IN_SEARCH"] = "Y";
								break;
							case("IE_"):
								if(is_set($iblockElementFields[$ENTITY_REAL_NAME]))
									$connectionFields["IBLOCK_ELEMENT_FIELD"] = $ENTITY_REAL_NAME;
								
								if($ENTITY_REAL_NAME == "PREVIEW_PICTURE" || $ENTITY_REAL_NAME == "DETAIL_PICTURE")
									$connectionFields["IS_IMAGE"] = "Y";
								elseif($ENTITY_REAL_NAME == "NAME")
									$connectionFields["IS_REQUIRED"] = "Y";
								break;
							case("IP_"):
								$ENTITY_PROP_ID = substr($NAME, 7, strlen($NAME));
								if(is_set($propertiesArray[$ENTITY_PROP_ID]))
									$connectionFields["IBLOCK_ELEMENT_PROPERTY"] = $ENTITY_PROP_ID;
								break;
							case("CP_"):
								if(is_set($catalogProductFields[$ENTITY_REAL_NAME]))
									$connectionFields["CATALOG_PRODUCT_FIELD"] = $ENTITY_REAL_NAME;
								break;
							case("CV_"):
								if($ENTITY_REAL_NAME == "PRICE_1")
									$connectionFields["CATALOG_PRODUCT_PRICE"] = "PRICE";
								elseif($ENTITY_REAL_NAME == "CURRENCY_1")
									$connectionFields["CATALOG_PRODUCT_PRICE"] = "CURRENCY";
								elseif(is_set($catalogProductPrice[$ENTITY_REAL_NAME]))
									$connectionFields["CATALOG_PRODUCT_PRICE"] = $ENTITY_REAL_NAME;
								break;
							case("UF_"):
								if(isset($iblockSectionFields[$NAME]))
									$connectionFields["IBLOCK_SECTION_FIELD"] = $NAME;
								elseif(isset($highloadBlockEntityFields[$NAME]))
								{
									$connectionFields["HIGHLOAD_BLOCK_ENTITY_FIELD"] = $NAME;
									if($NAME == "UF_FILE")
										$connectionFields["IS_IMAGE"] = "Y";
								}
									
								break;
						}
					}
					
					$connectionName = $ENTITY_NAME.' ['.$ENTITY.']<br />';
					
					$ID = $cData->Add($connectionFields);
					$res = ($ID>0);
					
					if(!$res)
					{
						if(strlen($strWarning)>0)
							$strWarning.= $connectionName.' '.$cData->LAST_ERROR;
						else
							$strWarning.= Loc::getMessage("MESSAGE_IMPORT_ERROR").":<br />".$connectionName.' '.$cData->LAST_ERROR."";
					}
					else
					{
						$sort += 10;
					}
				}
			}
			else
				$strWarning.= Loc::getMessage("ERROR_NO_CONNECTIONS");
			
			if($compareProperties)
			{
				unset($transParams, $ibp);
			}
			
			if(strlen($apply)<=0 && strlen($strWarning)==0)
			{
				if(strlen($back_url)>0)
				{
					$REDIRECT_AFTER_CONNECTIONS_IMPORT = COption::GetOptionString($module_id, "REDIRECT_AFTER_CONNECTIONS_IMPORT");
					
					if($REDIRECT_AFTER_CONNECTIONS_IMPORT == "table")
						LocalRedirect('/bitrix/admin/data.import_plan_connections_edit.php?ID='.$PLAN_ID.'&lang='.$lang);
					else
						LocalRedirect('/bitrix/admin/data.import_connections.php?PLAN_ID='.$PLAN_ID.'&apply_filter=Y&find_plan_id='.$PLAN_ID.'&lang='.$lang);
				}
			}
		}
	}
}

$queryObject = $pData->getList(Array($b = "sort" => $o = "asc"), array());
$listPlans = array();
while($plan = $queryObject->getNext())
	$listPlans[$plan["ID"]] = htmlspecialcharsbx($plan["NAME"]).' ['.$plan["ID"].']';

$APPLICATION->SetTitle(Loc::getMessage("IMPORT_PAGE_TITLE"));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

if($PLAN_ID > 0)
{
	$aMenu = array(
		array(
			"TEXT" => Loc::getMessage("CONNECTIONS_LIST"),
			"TITLE" => Loc::getMessage("CONNECTIONS_LIST_TITLE"),
			"LINK" => $back_url,
			"ICON" => "btn_list"
		)
	);
}

$context = new CAdminContextMenu((array)$aMenu);
$context->Show();
?>
<?CAdminMessage::ShowOldStyleError($strWarning);?>
<form method="POST" id="connections_import" name="CONNECTIONS_IMPORT" action="data.import_connections_import.php?lang=<?echo LANG?>">
<?=bitrix_sessid_post()?>
<input type="hidden" name="Update" value="Y">
<input type="hidden" name="PLAN_ID" value="<?echo $PLAN_ID?>">
<? if(!$PLAN_ID>0) { ?>
<input type="hidden" name="NO_PLAN_ID" value="Y">
<? } ?>
<?if(strlen($back_url)>0):?><input type="hidden" name="back_url" value="<?=htmlspecialcharsbx($back_url)?>"><?endif?>
<?
$tabControl->Begin();
$tabControl->BeginNextTab();

$arFormFields = [];

$arFormFields["MAIN"]["ITEMS"][] = Array(
	"CODE" => "PLAN_ID",
	"REQUIRED" => "Y",
	"DISABLED" => $PLAN_ID>0?"Y":'N',
	"REFRESH" => $PLAN_ID>0?"N":'Y',
	"TYPE" => "SELECT",
	"LABEL" => Loc::getMessage("TABLE_HEADING_PLAN_ID"),
	"VALUE" => $PLAN_ID,
	"ITEMS" => $listPlans,
);
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
	
	if(isset($scriptData))
	{
		$entitiesArray = $scriptData->GetEntities($PLAN_ID);
		
		if($plan_IMPORT_FORMAT == "XML")
		{
			$attributesArray = $entitiesArray["ATTRIBUTES"];
			$entitiesArray = $entitiesArray["KEYS"];
		}
		
		if(!is_array($entitiesArray))
		{
			$arFormFields["MAIN"]["ITEMS"][] = Array(
				"TYPE" => "NOTE",
				"VALUE" => Loc::getMessage("ERROR_NO_IMPORT_FILE_EXIST"),
				"PARAMS" => [
					"TYPE" => "danger",
					"ICON" => "info",
				]
			);
		}
		
		$arFormFields["MAIN"]["ITEMS"][] = Array(
			"ID" => "entities",
			"CODE" => "ENTITIES[]",
			"TYPE" => "SELECT",
			"VALUE" => (is_array($entitiesArray)?array_keys($entitiesArray):''),
			"LABEL" => Loc::getMessage("TABLE_HEADING_ENTITIES"),
			"DESCRIPTION" => Loc::getMessage("TABLE_HEADING_ENTITIES_HELP"),
			"ITEMS" => $entitiesArray,
			"PARAMS" => [
				"MULTIPLE" => "Y",
				"CHECK_ALL" => "Y",
				"CHECK_ALL_CHECKED" => "Y"
			]
		);
	}
	$arFormFields["ADDITIONAL"]["LABEL"] = Loc::getMessage("TABLE_HEADING_ADDITIONAL");
	$arFormFields["ADDITIONAL"]["ITEMS"][] = Array(
		"CODE" => "ACTIVE",
		"TYPE" => "CHECKBOX",
		"LABEL" => Loc::getMessage("TABLE_HEADING_ACTIVE"),
		"VALUE" => $ACTIVE?$ACTIVE:"Y",
	);
	$arFormFields["ADDITIONAL"]["ITEMS"][] = Array(
		"CODE" => "START_SORT",
		"TYPE" => "NUMBER",
		"LABEL" => Loc::getMessage("TABLE_HEADING_START_SORT"),
		"VALUE" => 0,
		"PARAMS" => [
			"STEP" => 10,
		]
	);
}

if($plan_IMPORT_IBLOCK_PROPERTIES == "Y")
{
	$arFormFields["PROPERTIES"]["LABEL"] = Loc::getMessage("TABLE_HEADING_PROPERTIES");
	if($plan_IMPORT_IBLOCK_ELEMENTS == "Y")
	{
		$arFormFields["PROPERTIES"]["ITEMS"][] = Array(
			"CODE" => "COMPARE_WITH_PROPERTIES",
			"TYPE" => "CHECKBOX",
			"LABEL" => Loc::getMessage("TABLE_HEADING_COMPARE_WITH_PROPERTIES"),
			"DESCRIPTION" => Loc::getMessage("TABLE_HEADING_COMPARE_WITH_PROPERTIES_HELP"),
			"VALUE" => "N",
		);
	}
	if($plan_IMPORT_CATALOG_PRODUCT_OFFERS == 'Y')
	{
		$arFormFields["PROPERTIES"]["ITEMS"][] = Array(
			"CODE" => "COMPARE_WITH_PROPERTIES_OFFERS",
			"TYPE" => "CHECKBOX",
			"LABEL" => Loc::getMessage("TABLE_HEADING_COMPARE_WITH_PROPERTIES_OFFERS"),
			"DESCRIPTION" => Loc::getMessage("TABLE_HEADING_COMPARE_WITH_PROPERTIES_HELP"),
			"VALUE" => "N",
		);
	}
	$arFormFields["PROPERTIES"]["ITEMS"][] = Array(
		"CODE" => "PROPERTY_TYPE_USER_TYPE",
		"TYPE" => "SELECT",
		"LABEL" => Loc::getMessage("TABLE_HEADING_PROPERTY_TYPE_USER_TYPE"),
		"ITEMS" => [
			'S' => Loc::getMessage("TABLE_HEADING_PROPERTY_TYPE_USER_TYPE_S"),
			'N' => Loc::getMessage("TABLE_HEADING_PROPERTY_TYPE_USER_TYPE_N"),
			'L' => Loc::getMessage("TABLE_HEADING_PROPERTY_TYPE_USER_TYPE_L"),
			'S:directory' => Loc::getMessage("TABLE_HEADING_PROPERTY_TYPE_USER_TYPE_S_DIRECTORY"),
		],
		"VALUE" => "S",
	);
	$arFormFields["PROPERTIES"]["ITEMS"][] = Array(
		"CODE" => "PROPERTY_MULTIPLE",
		"TYPE" => "CHECKBOX",
		"LABEL" => Loc::getMessage("TABLE_HEADING_PROPERTY_MULTIPLE"),
		"VALUE" => "N",
	);
	$arFormFields["PROPERTIES"]["ITEMS"][] = Array(
		"CODE" => "UPPERCASE_FIRST_SYMBOL",
		"TYPE" => "CHECKBOX",
		"LABEL" => Loc::getMessage("TABLE_HEADING_UPPERCASE_FIRST_SYMBOL"),
		"VALUE" => "N",
	);
}

CDataCoreFunctions::ShowFormFields($arFormFields);

$tabControl->Buttons(
	/*array(
		"disabled"=>($moduleAccessLevel<"W" || !is_array($entitiesArray)),
		"back_url"=>$back_url,
		"btnApply" => false,
	)*/
);
?>
<button 
title="<?=Loc::getMessage("SAVE_TITLE")?>" 
class="ui-btn ui-btn-success" 
type="submit" 
value="Y" 
name="save"
<?=($moduleAccessLevel<"W" || !is_array($entitiesArray))?" disabled":""?>
>
	<?=Loc::getMessage("SAVE")?>
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
<script type="text/javaScript">
<!--
BX.ready(function() {
	BX.UI.Hint.init(BX('connections_import'));
});
//-->
</script>
<?

	echo CDataCoreFunctions::showAlertBegin('warning', false);
	echo Loc::getMessage("DATA_IMPORT_CONNECTIONS_IMPORT_NOTE");
	echo CDataCoreFunctions::showAlertEnd();
?>
</form>
<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>