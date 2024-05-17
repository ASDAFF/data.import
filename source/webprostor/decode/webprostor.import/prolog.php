<?
define("ADMIN_MODULE_NAME", "webprostor.import");

$moduleAccessLevel = $APPLICATION->GetGroupRight(ADMIN_MODULE_NAME);

if ($moduleAccessLevel > "D")
{
	if(!CModule::IncludeModule("webprostor.core"))
	{
		$APPLICATION->IncludeAdminFile("webprostor.core", $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".ADMIN_MODULE_NAME."/install/webprostor.core.php");
	}
	elseif(!CModule::IncludeModule("iblock"))
	{
		$APPLICATION->IncludeAdminFile("bitrix.iblock", $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".ADMIN_MODULE_NAME."/install/bitrix.iblock.php");
	}
	elseif(!CModule::IncludeModule("highloadblock"))
	{
		$APPLICATION->IncludeAdminFile("bitrix.highloadblock", $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".ADMIN_MODULE_NAME."/install/bitrix.highloadblock.php");
	}
}

CJSCore::Init(array("jquery3"));

$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/panel/'.ADMIN_MODULE_NAME.'/theme/connections.css');

$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/panel/webprostor.core/select2/css/select2.min.css');
$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/panel/webprostor.core/select2/css/style.css');
$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/panel/webprostor.core/ui/css/jquery-ui.min.css');
$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/panel/webprostor.core/ui/css/jquery-ui.theme.min.css');

$GLOBALS['APPLICATION']->AddHeadScript('/bitrix/panel/webprostor.core/select2/js/select2.min.js');
if(defined("BX_UTF") || (defined("BX_UTF") && BX_UTF))
	$GLOBALS['APPLICATION']->AddHeadScript('/bitrix/panel/webprostor.core/select2/js/ru.js');
$GLOBALS['APPLICATION']->AddHeadScript('/bitrix/panel/webprostor.core/select2/js/main.js');
$GLOBALS['APPLICATION']->AddHeadScript('/bitrix/panel/webprostor.core/ui/js/jquery-ui.min.js');
$GLOBALS['APPLICATION']->AddHeadScript('/bitrix/panel/webprostor.core/main.js');

\Bitrix\Main\UI\Extension::load("ui.buttons");
\Bitrix\Main\UI\Extension::load("ui.buttons.icons");
\Bitrix\Main\UI\Extension::load("ui.progressbar");
\Bitrix\Main\UI\Extension::load("ui.alerts");
\Bitrix\Main\UI\Extension::load("ui.hint");
\Bitrix\Main\UI\Extension::load("ui.forms"); 
\Bitrix\Main\UI\Extension::load("ui.notification");
\Bitrix\Main\UI\Extension::load("ui.ears"); 

if ($action=='plan_export' && $ID>0)
{
	$strPath = $_SERVER['DOCUMENT_ROOT'].'/bitrix/tmp/'.ADMIN_MODULE_NAME.'/';
	$strName = $_SERVER['HTTP_HOST'].'_plan_export_'.$ID.'.xml';
	CheckDirPath($strPath);
	
	if ($hdlOutput = fopen($strPath.$strName, 'wb')) {
		fwrite($hdlOutput, '<?xml version="1.0" encoding="'.SITE_CHARSET.'"?>'."\n");
		fwrite($hdlOutput, '<webprostor_import>'."\n");
		$xml = '';
		CModule::IncludeModule(ADMIN_MODULE_NAME);
		$planData = new CWebprostorImportPlan;
		$planRes = $planData->GetByID($ID);
		$planParams = $planRes->Fetch();
		$xml .= "\t".'<params>'."\n";
		$arCData = array('NAME');
		$arExclude = array('ACTIVE', 'IBLOCK_ID', 'HIGHLOAD_BLOCK', 'SECTIONS_DEFAULT_SECTION_ID', 'ELEMENTS_DEFAULT_SECTION_ID', 'AGENT_ID');
		foreach($planParams as $code => $param)
		{
			if ($code == 'ID') {
				$code = 'OLD_ID';
			}
			if (in_array($code, $arExclude)) {
				continue;
			}
			if (in_array($code, $arCData) && strlen(trim($param))) {
				$param = '<![CDATA['.$param.']]>';
			}
			$xml .= "\t\t".'<'.strtolower($code).'>'.$param.'</'.strtolower($code).'>'."\n";
		}
		$xml .= "\t".'</params>'."\n";
		if ($_REQUEST['plan_connections'] == 'Y') {
			$xml .= "\t".'<connections>'."\n";
			$arCData = array('NAME');
			$arExclude = array('PLAN_ID');
			$CConnection = new CWebprostorImportPlanConnections;
			$connectionsRes = $CConnection->GetList(Array("SORT" => "ASC"), Array("PLAN_ID" => $ID));
			if ($_REQUEST['plan_properties'] == 'Y')
			{
				$exportProperties = Array();
				$exportOfferProperties = Array();
			}
			if ($_REQUEST['plan_processing_settings'] == 'Y')
			{
				$exportProcessing = Array();
			}
			while($connectionArr = $connectionsRes->GetNext(true, false))
			{
				$xml .= "\t\t".'<connection>'."\n";
				foreach($connectionArr as $code => $param)
				{
					if (in_array($code, $arExclude)) {
						continue;
					}
					if ($code == 'ID') {
						$code = 'OLD_ID';
					}
					if ($code == 'IBLOCK_SECTION_DEPTH_LEVEL' && $param == "0") {
						unset($param);
					}
					if ($code == 'IBLOCK_ELEMENT_PROPERTY' && $_REQUEST['plan_properties'] == 'Y' && $param>0) {
						$exportProperties[] = $param;
					}
					if ($code == 'IBLOCK_ELEMENT_OFFER_PROPERTY' && $_REQUEST['plan_properties'] == 'Y' && $param>0) {
						$exportOfferProperties[] = $param;
					}
					if ($code == 'PROCESSING_TYPES' && $_REQUEST['plan_processing_settings'] == 'Y' && strlen($param)>0) {
						
						$processingTypesArr = unserialize(base64_decode($param));
						if(!is_array($processingTypesArr))
							$processingTypesArr = Array();
						
						$exportProcessing = array_merge($exportProcessing, $processingTypesArr);
					}
					if (in_array($code, $arCData) && strlen(trim($param))) {
						$param = '<![CDATA['.$param.']]>';
					}
					$xml .= "\t\t\t".'<'.strtolower($code).'>'.$param.'</'.strtolower($code).'>'."\n";
				}
				if ($_REQUEST['plan_processing_settings'] == 'Y')
				{
					$exportProcessing = array_unique($exportProcessing);;
				}
				$xml .= "\t\t".'</connection>'."\n";
			}
			$xml .= "\t".'</connections>'."\n";
		}
		if ($_REQUEST['plan_properties'] == 'Y' && (is_array($exportProperties) && count($exportProperties)>0)) {
			$xml .= "\t".'<properties>'."\n";
			$arCData = array('NAME', 'DEFAULT_VALUE', 'HINT');
			$arExclude = array('IBLOCK_ID');
			foreach($exportProperties as $PROPERTY_ID)
			{
				$propertyRes = CIBlockProperty::GetByID($PROPERTY_ID);
				if($propertyArr = $propertyRes->GetNext(true, false))
				{
					$xml .= "\t\t".'<property>'."\n";
					foreach($propertyArr as $code => $param)
					{
						if (in_array($code, $arExclude)) {
							continue;
						}
						if ($code == 'ID') {
							$code = 'OLD_ID';
						}
						if (in_array($code, $arCData) && strlen(trim($param))) {
							$param = '<![CDATA['.$param.']]>';
						}
						if(is_array($param))
						{
							$param = base64_encode(serialize($param));
						}
						$xml .= "\t\t\t".'<'.strtolower($code).'>'.$param.'</'.strtolower($code).'>'."\n";
					}
					$xml .= "\t\t".'</property>'."\n";
				}
			}
			$xml .= "\t".'</properties>'."\n";
		}
		if ($_REQUEST['plan_properties'] == 'Y' && (is_array($exportOfferProperties) && count($exportOfferProperties)>0)) {
			$xml .= "\t".'<properties_offer>'."\n";
			$arCData = array('NAME', 'DEFAULT_VALUE', 'HINT');
			$arExclude = array('IBLOCK_ID');
			foreach($exportOfferProperties as $PROPERTY_ID)
			{
				$propertyRes = CIBlockProperty::GetByID($PROPERTY_ID);
				if($propertyArr = $propertyRes->GetNext(true, false))
				{
					$xml .= "\t\t".'<property>'."\n";
					foreach($propertyArr as $code => $param)
					{
						if (in_array($code, $arExclude)) {
							continue;
						}
						if ($code == 'ID') {
							$code = 'OLD_ID';
						}
						if (in_array($code, $arCData) && strlen(trim($param))) {
							$param = '<![CDATA['.$param.']]>';
						}
						if(is_array($param))
						{
							$param = base64_encode(serialize($param));
						}
						$xml .= "\t\t\t".'<'.strtolower($code).'>'.$param.'</'.strtolower($code).'>'."\n";
					}
					$xml .= "\t\t".'</property>'."\n";
				}
			}
			$xml .= "\t".'</properties_offer>'."\n";
		}
		if ($_REQUEST['plan_processing_settings'] == 'Y' && (is_array($exportProcessing) && count($exportProcessing)>0)) {
			$xml .= "\t".'<processing_settings>'."\n";
			$cProcessingData = new CWebprostorImportProcessingSettings;
			foreach($exportProcessing as $PROCESSING_ID)
			{
				$settingRes = $cProcessingData->GetByID($PROCESSING_ID);
				if($settingArr = $settingRes->Fetch())
				{
					$xml .= "\t\t".'<setting>'."\n";
					foreach($settingArr as $code => $param)
					{
						if ($code == 'ID') {
							$code = 'OLD_ID';
						}
						if(is_array($param))
						{
							$param = base64_encode(serialize($param));
						}
						$xml .= "\t\t\t".'<'.strtolower($code).'>'.$param.'</'.strtolower($code).'>'."\n";
					}
					$xml .= "\t\t".'</setting>'."\n";
				}
			}
			$xml .= "\t".'</processing_settings>'."\n";
		}
		fwrite($hdlOutput, $xml);
		fwrite($hdlOutput, '</webprostor_import>'."\n");
		fclose($hdlOutput);
	}
	?><script type="text/javascript">
		top.BX.closeWait();
		top.BX.WindowManager.Get().AllowClose(); 
		top.BX.WindowManager.Get().Close();
		window.location.href = '/bitrix/tools/<? echo ADMIN_MODULE_NAME; ?>/plan_export.php?ID=<? echo $ID; ?>';
	</script><?
	die();
}
if ($action=='plan_import' && !$_FILES['xml_file']['error'])
{
	$xmlPath = $_FILES['xml_file']['tmp_name'];
	$import_iblock_id = intVal($import_iblock_id);
	$import_highload_block = intVal($import_highload_block);
	if (file_exists($xmlPath) && CModule::IncludeModule(ADMIN_MODULE_NAME))
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/classes/general/xml.php');
		
		$xml = new CDataXML();
		if ($xml->Load($xmlPath))
		{
			if ($node = $xml->SelectNodes('/webprostor_import/params/'))
			{
				$arFields = array();
				$arParams = array_pop($node->__toArray());
				foreach ($arParams as $code => $v) {
					$arFields[strtoupper($code)] = isset($v[0]['#']['cdata-section']) && is_array($v[0]['#']['cdata-section']) ? $v[0]['#']['cdata-section'][0]['#'] : $v[0]['#'];
				}
				if($import_iblock_id>0)
					$arFields["IBLOCK_ID"] = $import_iblock_id;
				elseif($import_highload_block>0)
					$arFields["HIGHLOAD_BLOCK"] = $import_highload_block;
				$planData = new CWebprostorImportPlan;
				$newPlanID = $planData->Add($arFields);
			}
			if ($newPlanID>0)
			{
				if ($import_iblock_id>0)
				{
					$CProperty = new CIBlockProperty;
					if ($node = $xml->SelectNodes('/webprostor_import/properties/'))
					{
						$arProperties = Array();
						foreach ($node->children() as $child)
						{
							$PROPERTY_ID = false;
							$arProperty = array_pop($child->__toArray());
							$arPropertyFields = array('IBLOCK_ID' => $import_iblock_id);
							foreach ($arProperty as $code => $v) {
								$arPropertyFields[strtoupper($code)] = isset($v[0]['#']['cdata-section']) && is_array($v[0]['#']['cdata-section']) ? $v[0]['#']['cdata-section'][0]['#'] : $v[0]['#'];
							}
							$properties = CIBlockProperty::GetList(Array("sort"=>"asc"), Array("PROPERTY_TYPE"=>$arPropertyFields["PROPERTY_TYPE"], "CODE"=>$arPropertyFields["CODE"], "NAME"=>$arPropertyFields["NAME"], "IBLOCK_ID"=>$import_iblock_id));
							while ($prop_fields = $properties->GetNext())
							{
								$PROPERTY_ID = $prop_fields["ID"];
							}
							if(!$PROPERTY_ID)
							{
								$USER_TYPE_SETTINGS = unserialize(base64_decode($arPropertyFields["USER_TYPE_SETTINGS"]));
								if(is_array($USER_TYPE_SETTINGS) && count($USER_TYPE_SETTINGS)>0)
								{
									$arPropertyFields["USER_TYPE_SETTINGS"] = $USER_TYPE_SETTINGS;
								}
								$PROPERTY_ID = $CProperty->Add($arPropertyFields);
							}
							$arProperties[$arPropertyFields["OLD_ID"]] = $PROPERTY_ID;
						}
					}
					if ($node = $xml->SelectNodes('/webprostor_import/properties_offer/'))
					{
						if(CModule::IncludeModule("catalog"))
						{
							$catalogSKU = CCatalogSKU::GetInfoByProductIBlock($arFields["IBLOCK_ID"]);
							$import_offer_iblock_id = $catalogSKU["IBLOCK_ID"];
						}
						$arOfferProperties = Array();
						foreach ($node->children() as $child)
						{
							$PROPERTY_ID = false;
							$arOfferProperty = array_pop($child->__toArray());
							$arOfferPropertyFields = array('IBLOCK_ID' => $import_offer_iblock_id);
							foreach ($arOfferProperty as $code => $v) {
								$arOfferPropertyFields[strtoupper($code)] = isset($v[0]['#']['cdata-section']) && is_array($v[0]['#']['cdata-section']) ? $v[0]['#']['cdata-section'][0]['#'] : $v[0]['#'];
							}
							$properties = CIBlockProperty::GetList(Array("sort"=>"asc"), Array("PROPERTY_TYPE"=>$arOfferPropertyFields["PROPERTY_TYPE"], "CODE"=>$arOfferPropertyFields["CODE"], "NAME"=>$arOfferPropertyFields["NAME"], "IBLOCK_ID"=>$import_offer_iblock_id));
							while ($prop_fields = $properties->GetNext())
							{
								$PROPERTY_ID = $prop_fields["ID"];
							}
							if(!$PROPERTY_ID)
							{
								$USER_TYPE_SETTINGS = unserialize(base64_decode($arOfferPropertyFields["USER_TYPE_SETTINGS"]));
								if(is_array($USER_TYPE_SETTINGS) && count($USER_TYPE_SETTINGS)>0)
								{
									$arOfferPropertyFields["USER_TYPE_SETTINGS"] = $USER_TYPE_SETTINGS;
								}
								$PROPERTY_ID = $CProperty->Add($arOfferPropertyFields);
							}
							$arOfferProperties[$arOfferPropertyFields["OLD_ID"]] = $PROPERTY_ID;
						}
					}
				}
				if ($node = $xml->SelectNodes('/webprostor_import/processing_settings/'))
				{
					$arSettings = Array();
					$cProcessingData = new CWebprostorImportProcessingSettings;
					foreach ($node->children() as $child)
					{
						$SETTING_ID = false;
						$arProcessing = array_pop($child->__toArray());
						$arProcessingFields = array();
						foreach ($arProcessing as $code => $v) {
							$arProcessingFields[strtoupper($code)] = isset($v[0]['#']['cdata-section']) && is_array($v[0]['#']['cdata-section']) ? $v[0]['#']['cdata-section'][0]['#'] : $v[0]['#'];
						}
						$settings = $cProcessingData->GetList(Array("sort"=>"asc"), Array("PROCESSING_TYPE"=>$arProcessingFields["PROCESSING_TYPE"], "PARAMS"=>$arProcessingFields["PARAMS"]));
						while ($prop_setting = $settings->GetNext())
						{
							$SETTING_ID = $prop_setting["ID"];
						}
						if(!$SETTING_ID)
							$SETTING_ID = $cProcessingData->Add($arProcessingFields);
						$arSettings[$arProcessingFields["OLD_ID"]] = $SETTING_ID;
					}
				}
				if ($node = $xml->SelectNodes('/webprostor_import/connections/'))
				{
					$CConnection = new CWebprostorImportPlanConnections;
					foreach ($node->children() as $child)
					{
						$arConnection = array_pop($child->__toArray());
						$arConnectionFields = array('PLAN_ID' => $newPlanID);
						foreach ($arConnection as $code => $v) {
							$arConnectionFields[strtoupper($code)] = isset($v[0]['#']['cdata-section']) && is_array($v[0]['#']['cdata-section']) ? $v[0]['#']['cdata-section'][0]['#'] : $v[0]['#'];
						}
						
						if($arConnectionFields["IBLOCK_ELEMENT_PROPERTY"]>0)
							$arConnectionFields["IBLOCK_ELEMENT_PROPERTY"] = $arProperties[$arConnectionFields["IBLOCK_ELEMENT_PROPERTY"]];
						if($arConnectionFields["IBLOCK_ELEMENT_OFFER_PROPERTY"]>0)
							$arConnectionFields["IBLOCK_ELEMENT_OFFER_PROPERTY"] = $arOfferProperties[$arConnectionFields["IBLOCK_ELEMENT_OFFER_PROPERTY"]];
						
						$PROCESSING_TYPES = unserialize(base64_decode($arConnectionFields["PROCESSING_TYPES"]));
						if(is_array($PROCESSING_TYPES) && count($PROCESSING_TYPES)>0)
						{
							$arConnectionFields["PROCESSING_TYPES"] = Array();
							foreach($PROCESSING_TYPES as $SETTING_ID)
							{
								$arConnectionFields["PROCESSING_TYPES"][] = $arSettings[$SETTING_ID];
							}
							$arConnectionFields["PROCESSING_TYPES"] = base64_encode(serialize($arConnectionFields["PROCESSING_TYPES"]));
						}
						
						$CConnection->Add($arConnectionFields);
					}
				}
			}
		}
		
		if($newPlanID>0)
			LocalRedirect('/bitrix/admin/webprostor.import_plan_edit.php?ID='.$newPlanID.'&lang='.LANGUAGE_ID);
		else
			LocalRedirect('/bitrix/admin/webprostor.import_plan_edit.php?import_status=error&lang='.LANGUAGE_ID);
	}
}
?>