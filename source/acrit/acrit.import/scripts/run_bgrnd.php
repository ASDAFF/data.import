<?
$moduleId = "acrit.import";

@set_time_limit(0);
ignore_user_abort(true);

ini_set('display_errors', true);
ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

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

CModule::IncludeModule($moduleId);

use Acrit\Import,
	Acrit\Import\Agents,
	Bitrix\Main\Config\Option,
	Bitrix\Main\Diag\Debug;
use Acrit\Import\ProfileTable;

// Prepare
$profile_id = $_REQUEST['profile'] ?: 0;
if ((int)$argv[1] > 0) {
	$profile_id = (int)$argv[1];
}
if (!$profile_id) {
	return false;
}

$count = $_REQUEST['count'] ?: 0;
if ((int)$argv[2] > 0) {
	$count = (int)$argv[2];
}

$next_item = $_REQUEST['next_item'] ?: 0;
if ((int)$argv[3] > 0) {
	$next_item = (int)$argv[3];
}

// Run mode
$run_mode = ($argc > 1) ? 'console' : 'agent';

if ($run_mode == 'agent') {
	// Check other import runs
	if (Agents::isLocked($profile_id)) {
		return;
	}
	// Check if is duplicate run
	if (Agents::isDoubleRun($profile_id, $next_item)) {
		return;
	}
}

// Lock the profile
Agents::addLock($profile_id);
Agents::addRunPos($profile_id, $next_item);

// Import
$obImport = AcritImportGetImportObj($profile_id);

if ($obImport) {
	// Run import
	$step_type     = ($run_mode == 'console') ? Import\Import::STEP_BY_COUNT : Import\Import::STEP_BY_TYME;
	$limit         = ($run_mode == 'console')
		? (int)Option::get(\CAcritImport::MODULE_ID, 'cli_step_limit', 1000)
		: 1; //(int)Option::get(\CAcritImport::MODULE_ID, 'manual_step_limit', 1);

	// Logs
	$obLogs   = $obImport->getLog();
	$sendLogs = static function () use ($obLogs) {
		// full log on cli run
		$obLogs->save([Import\Log::TYPE_ERROR, Import\Log::TYPE_SKIP, Import\Log::TYPE_MESSAGE]);
	};

	// Update last start
	if ($next_item == 0) {
		$arFields = [
			'START_LAST_TIME' => new Bitrix\Main\Type\DateTime(date('Y-m-d H:i:s'), 'Y-m-d H:i:s'),
		];
		ProfileTable::update($profile_id, $arFields);
		$obLogs->add($obLogs->getImportStatStartMessage(date('Y-m-d H:i:s')));
	}

	// Payload
	CAcritImport::startMultipleElemsUpdate((int)$obImport->getArProfile()['IBLOCK_ID']);
	$next_item_new = $obImport->import($step_type, $limit, $next_item);
	CAcritImport::endMultipleElemsUpdate((int)$obImport->getArProfile()['IBLOCK_ID']);
	// echo written in \CAcritImport::STEPS_LOG_FILENAME
	echo '(run background) ' . $next_item_new . PHP_EOL;

	// Agents lock
	Agents::delLock($profile_id);
	$progressStr = $next_item_new.'/'.$count . ' ' . round($next_item_new/$count * 100, 1) . '%';
	if ($next_item_new >= $count) {
		$progressStr = $count.'/'.$count . ' ' . '100%';
	}
	$obLogs->add($obLogs->getImportStatFooterMessage( $progressStr ), Import\Log::TYPE_MESSAGE);

	// Run next step
	if ($next_item_new && $next_item_new < $count) {
		$sendLogs();

		CAcritImport::runBgrRequest('/bitrix/acrit.import_run_bgrnd.php', [
			'profile'   => $profile_id,
			'count'     => $count,
			'next_item' => $next_item_new,
			'mark'      => md5(random_int(1000, 1000000)),
		]);
	} elseif ($next_item_new >= $count) {
		Agents::delRunPos($profile_id);
		$obLogs->add($obLogs->getImportStatFooterCompleteMessage(date('Y-m-d H:i:s')), Import\Log::TYPE_MESSAGE);
		$sendLogs();

		foreach (GetModuleEvents($moduleId, "OnAfterAcritImportProcess", true) as $arEvent) {
			ExecuteModuleEventEx($arEvent, [$profile_id, $obImport]);
		}
		// echo written in \CAcritImport::STEPS_LOG_FILENAME
		echo '(run background) DONE!' . PHP_EOL;

		// Run facet indexing
		$is_indexing = CAcritImport::isPropertyIndexEnabled();
		if ($is_indexing) {
			CAcritImport::runBgrRequest('/bitrix/acrit.import_run_index.php', [
				'profile' => $profile_id,
				'mark'    => md5(random_int(1000, 1000000)),
			]);
		}
	}
} else {
	Agents::delLock($profile_id);
	Agents::delRunPos($profile_id);
}
