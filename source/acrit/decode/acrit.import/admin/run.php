<?
$moduleId = "acrit.import";

// performance fixs
define("STOP_STATISTICS",       true);
define("NO_KEEP_STATISTIC",     true);
define("NO_AGENT_STATISTIC",    "Y");
define("NOT_CHECK_PERMISSIONS", true);
define("DisableEventsCheck",    true);
define("BX_SECURITY_SHOW_MESSAGE", true);
// Виртуальная сессия
define('BX_SECURITY_SESSION_VIRTUAL', true);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/interface/admin_lib.php");

// subbuf reset
set_time_limit(0);
while (ob_get_level()) {
	ob_end_flush();
}

CModule::IncludeModule('iblock');
CModule::IncludeModule($moduleId);

use Acrit\Import,
	Bitrix\Main\Config\Option;

// Prepare
$profile_id = $_REQUEST['profile'] ?: 0;
if (!$profile_id) {
	return false;
}
$next_item      = $_REQUEST['next_item'] ?: 0;
$limit          = $_REQUEST['limit'] ?: (int)Option::get($moduleId, 'manual_step_limit', 10);
$imported_count = (int)$_REQUEST['imported_count'];
$next_item_new  = 0;

// Import
$res      = false;
$obImport = AcritImportGetImportObj($profile_id);
if ($obImport) {

	// Run import
	CAcritImport::startMultipleElemsUpdate((int)$obImport->getArProfile()['IBLOCK_ID']);
	$next_item_new = $obImport->import(Import\Import::STEP_BY_TYME, $limit, $next_item);
	CAcritImport::endMultipleElemsUpdate((int)$obImport->getArProfile()['IBLOCK_ID']);

	// Update last start
	if ($next_item == 0) {
		$arFields = [
			'START_LAST_TIME' => new Bitrix\Main\Type\DateTime(date('Y-m-d H:i:s'), 'Y-m-d H:i:s'),
		];
		Acrit\Import\ProfileTable::update($profile_id, $arFields);
		// Mark facet index as invalid
		$arProfile = $obImport->getProfile();

		if (\CAcritImport::isPropertyIndexEnabled()) {
			Bitrix\Iblock\PropertyIndex\Manager::markAsInvalid($arProfile['IBLOCK_ID']);
		}
	}
	// Errors log
	$obLogs   = $obImport->getLog();
	$arErrors = $obLogs->getList([Import\Log::TYPE_ERROR, Import\Log::TYPE_SKIP], true);
	if (LANG_CHARSET == 'windows-1251') {
		foreach ($arErrors as $k => $error) {
			$arErrors[$k] = mb_convert_encoding($error, 'UTF-8', 'CP1251');
		}
	}
	$obLogs->save();

	// Import statistics
	$arLogStat      = $obLogs->getStat();
	$arReport       = [
		'success' => $arLogStat[Import\Log::TYPE_SUCCESS],
		'errors' => $arLogStat[Import\Log::TYPE_ERROR],
		'skip' => $arLogStat[Import\Log::TYPE_SKIP],
	];
	$arImportStat   = $obLogs->getImportStat();
	$imported_count += $arImportStat['imported_items'] + $arImportStat['skipped_items'];
}

echo json_encode([
	'next_item' => $next_item_new,
	'imported_count' => $imported_count,
	'errors' => $arErrors,
	'report' => $arReport,
], JSON_INVALID_UTF8_IGNORE);
