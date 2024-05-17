<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

/** @global CMain $APPLICATION */
global $APPLICATION;

use Bitrix\Main\Localization\Loc;

$module_id = 'data.import';
$moduleAccessLevel = $APPLICATION->GetGroupRight($module_id);

if ($moduleAccessLevel == "D")
{
	ShowError(Loc::getMessage('DATA_IMPORT_AGENTS_GADGET_ERROR_NO_ACCESS'));
	return;
}

if(!CModule::IncludeModule($module_id)) {
	return;
}

$APPLICATION->SetAdditionalCSS('/bitrix/gadgets/data/import.agents/styles.css');

$ImportAgent = new CDataImportAgent();
$bAlertDeleted = $ImportAgent->getPlansWithDeletedAgents();
$bAlertImport = $ImportAgent->SearchAgent(false, 'Import', 'N');
$bAlertLoad = $ImportAgent->SearchAgent(false, 'Load', 'N');
?>
<div class="bx-gadgets-content-layout-import-agents">
	<div class="bx-gadgets-title"><?echo Loc::getMessage("DATA_IMPORT_AGENTS_GADGET_TITLE")?></div>
	<div class="bx-gadget-bottom-cont bx-gadget-bottom-button-cont bx-gadget-mark-cont">
<?
	if ($bAlertDeleted)
	{
		$bAlert = 0;
		if(is_array($bAlertDeleted))
			$bAlert += count($bAlertDeleted);
?>
		<a class="bx-gadget-button" href="/bitrix/admin/data.import_plans.php?lang=<?echo LANGUAGE_ID?>&apply_filter=Y&ACTIVE=Y">
			<div class="bx-gadget-button-lamp"></div>
			<div class="bx-gadget-button-text"><?echo Loc::getMessage("DATA_IMPORT_AGENTS_GADGET_BUTTON_TEXT")?></div>
		</a>
		<div class="bx-gadget-desc bx-gadget-desc-wmark"><?echo Loc::getMessage("DATA_IMPORT_AGENTS_GADGET_MESSAGE_YES_DELETED_AGENTS", ['#COUNT#' => $bAlert])?></div>
	<?
	}
	elseif ($bAlertImport || $bAlertLoad)
	{
		$bAlert = 0;
		if(is_array($bAlertImport))
			$bAlert += count($bAlertImport);
		if(is_array($bAlertLoad))
			$bAlert += count($bAlertLoad);
?>
		<a class="bx-gadget-button" href="/bitrix/admin/agent_list.php?lang=<?echo LANGUAGE_ID?>&set_filter=Y&find_module_id=<?=$module_id?>&find_active=N">
			<div class="bx-gadget-button-lamp"></div>
			<div class="bx-gadget-button-text"><?echo Loc::getMessage("DATA_IMPORT_AGENTS_GADGET_BUTTON_TEXT")?></div>
		</a>
		<div class="bx-gadget-desc bx-gadget-desc-wmark"><?echo Loc::getMessage("DATA_IMPORT_AGENTS_GADGET_MESSAGE_YES_DEACTIVATED_AGENTS", ['#COUNT#' => $bAlert])?></div>
	<?
	}
	else
	{
?>
		<a class="bx-gadget-button" href="/bitrix/admin/agent_list.php?lang=<?echo LANGUAGE_ID?>&set_filter=Y&find_module_id=<?=$module_id?>">
			<div class="bx-gadget-button-lamp"></div>
			<div class="bx-gadget-button-text"><?echo Loc::getMessage("DATA_IMPORT_AGENTS_GADGET_BUTTON_TEXT")?></div>
		</a>
		<div class="bx-gadget-desc bx-gadget-desc-wmark"><?echo Loc::getMessage("DATA_IMPORT_AGENTS_GADGET_MESSAGE_NO_DEACTIVATED_AGENTS")?></div>
	<?
	}
?>
	</div>
</div>
<div class="bx-gadget-shield"></div>