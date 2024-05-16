<?
@set_time_limit(0);
ignore_user_abort(true);

// performance fixs
define("STOP_STATISTICS", true);
define("NO_KEEP_STATISTIC", true);
define("NO_AGENT_STATISTIC", "Y");
define("NOT_CHECK_PERMISSIONS", true);
define("DisableEventsCheck", true);
define("BX_SECURITY_SHOW_MESSAGE", true);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/interface/admin_lib.php");

CModule::IncludeModule("acrit.import");

use Acrit\Import,
	Acrit\Import\Agents,
	Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Iblock\PropertyIndex\Manager;

// Prepare
$profile_id = $_REQUEST['profile'] ?: 0;
if ((int)$argv[1] > 0) {
	$profile_id = (int)$argv[1];
}
if (!$profile_id) {
	return false;
}
$productsLimitCount = $_REQUEST['count'] ?: 0;
if ((int)$argv[2] > 0) {
	$productsLimitCount = (int)$argv[2];
}
$stepNumber = $_REQUEST['step'] ?: 0;
if ((int)$argv[3] > 0) {
	$stepNumber = (int)$argv[3];
}
$productsIndexedInLastStepCount = $_REQUEST['last_index'] ?: 0;
if ((int)$argv[4] > 0) {
	$productsIndexedInLastStepCount = (int)$argv[4];
}

$stepTimeLimit = 10;

// Facet indexing
$obImport = AcritImportGetImportObj($profile_id);
if ($obImport) {
	$arProfile = $obImport->getProfile();
	if (Loader::includeModule('iblock')) {
		$index = Manager::createIndexer($arProfile['IBLOCK_ID']);

		// Start
		if ($stepNumber == 0) {
			$index->startIndex();
			$productsLimitCount = $index->estimateElementCount();
		}

		// Indexing process
		$productsIndexedCount = $index->continueIndex($stepTimeLimit) + $productsIndexedInLastStepCount;

		// Next step
		if ($productsIndexedCount < $productsLimitCount && $productsIndexedCount != $productsIndexedInLastStepCount) {
			$stepNumber++;
			CAcritImport::runBgrRequest('/bitrix/acrit.import_run_index.php', [
				'profile' => $profile_id,
				'count' => $productsLimitCount,
				'step' => $stepNumber,
				'last_index' => $productsIndexedCount,
				'mark' => md5(rand(1000, 1000000)),
			]);
		} // End
		else {
			$index->endIndex();
			foreach (GetModuleEvents("acrit.import", "OnAfterAcritImportIndexing", true) as $arEvent) {
				ExecuteModuleEventEx($arEvent, [$profile_id, $obImport]);
			}
		}
	}
}
