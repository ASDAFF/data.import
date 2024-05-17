<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

$bAlertColored = false;
if (CModule::IncludeModule('data.import'))
{
	$ImportAgent = new CDataImportAgent();
	
	$bAlertColored = $ImportAgent->getPlansWithDeletedAgents();
	if(!$bAlertColored)
		$bAlertColored = $ImportAgent->SearchAgent(false, 'Import', 'N');
	if(!$bAlertColored)
		$bAlertColored = $ImportAgent->SearchAgent(false, 'Load', 'N');
}

$arDescription = Array(
	'NAME' => GetMessage("DATA_IMPORT_AGENTS_GADGET_NAME"),
	'DESCRIPTION' => GetMessage("DATA_IMPORT_AGENTS_GADGET_DESCRIPTION"),
	'DISABLED' => 'N',
	'ICON' => '',
	'GROUP' => Array('ID'=>'other'),
	"TITLE_ICON_CLASS" => "bx-gadgets-import-agents" . ($bAlertColored ? " bx-gadgets-import-agents-alert" : ""),
	"NOPARAMS" => "Y",
	"AI_ONLY" => true,
	"COLOURFUL" => true
);