<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

$module_id = 'webprostor.import';

$moduleAccessLevel = $APPLICATION->GetGroupRight($module_id);

if ($moduleAccessLevel == "D")
    $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$module_id."/prolog.php");

$arTabs = Array();

$groupsMain = Array(
	"PLANS" => GetMessage("WEBPROSTOR_IMPORT_OPTIONS_GROUP_PLANS"),
	"LOGS" => GetMessage("WEBPROSTOR_IMPORT_OPTIONS_GROUP_LOGS"),
);

$groupsSites = Array();

$arGroups = CWebprostorCoreOptions::GetGroups($groupsSites, $arTabs, $groupsMain);
$arOptions = Array();

$optionsSites = Array();

$optionsMain = Array(
	Array(
		'CODE' => "REDIRECT_AFTER_CONNECTIONS_IMPORT",
		'GROUP' => "PLANS",
		'TYPE' => 'SELECT',
		'TITLE' => GetMessage("WEBPROSTOR_IMPORT_OPTIONS_OPTION_REDIRECT_AFTER_CONNECTIONS_IMPORT"),
		'SORT' => '10',
		'VALUES' => Array(
			'REFERENCE' => Array(
				GetMessage("WEBPROSTOR_IMPORT_OPTIONS_OPTION_REDIRECT_AFTER_CONNECTIONS_IMPORT_TABLE"), GetMessage("WEBPROSTOR_IMPORT_OPTIONS_OPTION_REDIRECT_AFTER_CONNECTIONS_IMPORT_LIST"),
			),
			'REFERENCE_ID' => Array(
				"table", "list",
			),
		),
	),
	Array(
		'CODE' => "NOTIFY_LIMIT",
		'GROUP' => "LOGS",
		'TITLE' => GetMessage("WEBPROSTOR_IMPORT_OPTIONS_OPTION_NOTIFY_LIMIT"),
		'SORT' => '20',
		'TYPE' => 'INT',
		'MIN' => '0',
	),
);

$arOptions = CWebprostorCoreOptions::GetOptions($optionsSites, $arTabs, $optionsMain);

$opt = new CWebprostorCoreOptions($module_id, $arTabs, $arGroups, $arOptions, $showMainTab = true, $showRightsTab = true);
$opt->ShowHTML();
?>