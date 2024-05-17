<?
defined('B_PROLOG_INCLUDED') || die();
/**
 * @global $APPLICATION \CMain
 * @global $USER \CUser
 * @global $DB \CDatabase
 * @global $USER_FIELD_MANAGER \CUserTypeManager
 * @global $BX_MENU_CUSTOM \CMenuCustom
 * @global $stackCacheManager \CStackCacheManager
 */

define("MODULE_ID", "acrit.import");
$module_id = MODULE_ID;
$siteId = defined('SITE_ID') ? SITE_ID : LANGUAGE_ID;

$incl_res = CModule::IncludeModuleEx(MODULE_ID);
switch ($incl_res) {
	case MODULE_NOT_FOUND:
		echo BeginNote();
		echo '<span class="required">' . GetMessage('ACRIT_IMPORT_WARN_MODULE_NOT_FOUND') . '</span>';
		echo EndNote();
		break;
	case MODULE_DEMO:
		echo BeginNote();
		echo '<span class="required">' . GetMessage('ACRIT_IMPORT_WARN_MODULE_DEMO') . '</span>';
		echo EndNote();
		break;
	case MODULE_DEMO_EXPIRED:
		echo BeginNote();
		echo '<span class="required">' . GetMessage('ACRIT_IMPORT_WARN_MODULE_DEMO_EXPIRED') . '</span>';
		echo EndNote();
		break;
	default: // MODULE_INSTALLED
}

\Bitrix\Main\Loader::includeModule('catalog');
\Bitrix\Main\Loader::includeModule('currency');

use Bitrix\Main,
	Acrit\Import,
	Bitrix\Main\Localization\Loc;

$RIGHT = $APPLICATION->GetGroupRight(MODULE_ID);
if ($RIGHT < "R") {
	return false;
}


/**
 * Options list
 */

$arCurrencies = [];
$cur_def      = '';
$res          = \Bitrix\Currency\CurrencyTable::getList([]);
while ($arItem = $res->fetch()) {
	$arCurrencies[$arItem['CURRENCY']] = $arItem['CURRENCY'];
	if ($arItem['BASE'] == 'Y') {
		$cur_def = $arItem['CURRENCY'];
	}
}

$arStores = [];
$res      = \Bitrix\Catalog\StoreTable::getList([
	'order' => ['ID' => 'asc'],
	'filter' => ['ACTIVE' => 'Y'],
]);
while ($arItem = $res->fetch()) {
	$arStores[$arItem['ID']] = $arItem['TITLE'] . ' (' . $arItem['ID'] . ')';
}

$aTabs = [
	'main' => [
		'TAB' => GetMessage("ACRIT_IMPORT_NASTROYKI_MODULA"),//Loc::getMessage('ACRIT_IMPORT_SECTION_TAB'),
		'OPTIONS' => [
			GetMessage("ACRIT_IMPORT_SETTINGS_MAIN"),
			[
				'https',
				GetMessage("ACRIT_IMPORT_SETTINGS_HTTPS"),
				CMain::IsHTTPS() ? "Y" : "N",
				[
					"checkbox",
					"",
					""
				]
			],
			[
				'note' => GetMessage('ACRIT_IMPORT_SETTINGS_PHP_PATH') . GetMessage('ACRIT_IMPORT_CORE_CONFIGS_URI', ['#LANG#' => LANGUAGE_ID]),
			],
			GetMessage("ACRIT_IMPORT_PARAMETRY_PERFOEMANCE"),
			[
				'indexing',
				GetMessage("ACRIT_IMPORT_SETTINGS_INDEXING"),
				"N",
				[
					"checkbox",
					"",
					""
				]
			],
			[
				'search_indexing',
				GetMessage("ACRIT_IMPORT_SETTINGS_SEARCH_INDEXING"),
				"Y",
				[
					"checkbox",
					"",
					""
				]
			],
			[
				'manual_step_limit',
				GetMessage("ACRIT_IMPORT_SETTINGS_MANUAL_STEP_LIMIT"),
				10,
				['text', 30]
			],
			[
				'cli_step_limit',
				GetMessage("ACRIT_IMPORT_SETTINGS_CLI_STEP_LIMIT"),
				1000,
				['text', 30]
			],
			GetMessage("ACRIT_IMPORT_PARAMETRY_LOGIROVANI"),
			[
				'logs_path',
				GetMessage("ACRIT_IMPORT_PUTQ_K_FAYLU_LOGOV_N"),
				null,
				['text', 52],
			],
			[
				'logs_email',
				'E-mail ' . GetMessage("ACRIT_IMPORT_DLA_OTPRAVKI_LOGOV"),
				null,
				['text', 25],
			],
			[
				'logs_events',
				GetMessage("ACRIT_IMPORT_SOHRANATQ_OTCET_V_RA"),
				"N",
				[
					"checkbox",
					"",
					""
				]
			],
			[
				'history_cnt_url_files',
				GetMessage("ACRIT_IMPORT_HISTORY_CNT_URL_FILES"),
				10,
				['text', 10],
			]
		]
	]
];

$arTabs = [
	["DIV" => "setting1", "TAB" => GetMessage("ACRIT_IMPORT_NASTROYKI_MODULA"), "ICON" => "main_user_edit", "TITLE" => GetMessage("ACRIT_IMPORT_NASTROYKI_MODULA")],
	["DIV" => "setting2", "TAB" => GetMessage("ACRIT_IMPORT_TAB1_DESCR"), "ICON" => "main_user_edit", "TITLE" => GetMessage("ACRIT_IMPORT_TAB1_DESCR")],
];

// Check extensions
$ext_not_found = CAcritImport::checkExtensions();
if (!empty($ext_not_found)) {
	$aTabs['main']['OPTIONS'][] = [
		"note" => GetMessage("ACRIT_IMPORT_SERVER_EXT_NOT_FOUND", ['#MODULE_NAME#' => implode(', ', $ext_not_found)]),
	];
}


/**
 * Save data
 */
$tabControl = new CAdminTabControl('tabControl', $arTabs);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && strlen($_REQUEST['save']) > 0 && check_bitrix_sessid()) {
	$Update = $_REQUEST['save'];
	foreach ($aTabs as $aTab) {
		__AdmSettingsSaveOptions(MODULE_ID, $aTab['OPTIONS']);
	}
	ob_start();
	require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/admin/group_rights.php");
	ob_end_clean();

	LocalRedirect($APPLICATION->GetCurPage() . '?lang=' . LANGUAGE_ID . '&mid_menu=1&mid=' . urlencode(MODULE_ID) .
		'&tabControl_active_tab=' . urlencode($_REQUEST['tabControl_active_tab']) . '&sid=' . urlencode($siteId));
}


/**
 * Show form
 */

?>
	<form method='post' action='' name='bootstrap'>
		<? $tabControl->Begin();

		$tabControl->BeginNextTab();
			__AdmSettingsDrawList(MODULE_ID, $aTabs['main']['OPTIONS']);
		?>
		<?
		$tabControl->BeginNextTab();
			require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/admin/group_rights.php");
		$tabControl->Buttons(['btnApply' => false, 'btnCancel' => false, 'btnSaveAndAdd' => false]); ?>

		<?=bitrix_sessid_post();?>
		<? $tabControl->End(); ?>
	</form>

	<div class="adm-info-message">
		<a href="https://www.acrit-studio.ru/technical-support/nastroyka-modulya-universalnyy-import/razdel-nastroyki-modulya/"
		   target="_blank"><?=GetMessage("ACRIT_IMPORT_OPTIONS_HELP");?></a>
	</div>
<?