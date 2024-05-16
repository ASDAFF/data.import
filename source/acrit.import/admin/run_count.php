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

CModule::IncludeModule($moduleId);

use Acrit\Import,
	Acrit\Import\Agents,
	Bitrix\Main\Config\Option;

$arErrors = [];
$count    = 0;

$profile_id = $_REQUEST['profile'] ?: 0;
if (!$profile_id) {
	$arErrors[] = 'Profile ID is empty';
}

// Check other import runs
if (Agents::isLocked($profile_id)) {
	$arErrors[] = 'Profile locked';
}

if (!$arErrors) {
	$obImport = AcritImportGetImportObj($profile_id);
	if ($obImport) {
		$count = $obImport->count();
	} else {
		$arErrors[] = sprintf('Profile [%s] is not created', $profile_id);
	}
}

echo json_encode([
	'count' => (int)$count,
	'errors' => $arErrors,
]);
