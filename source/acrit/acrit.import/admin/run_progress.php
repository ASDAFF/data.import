<?
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

$count   = $_REQUEST['count'] ?: 0;
$current = $_REQUEST['current'] ?: 0;

if ($count) {
	if ($current > $count) {
		$current = $count;
	}
	$percent = $current / $count * 100;

	CAdminMessage::ShowMessage([
		"MESSAGE" => "",
		"DETAILS" => "#PROGRESS_BAR#",
		"HTML" => true,
		"TYPE" => "PROGRESS",
		"PROGRESS_TOTAL" => 100,
		"PROGRESS_VALUE" => $percent,
	]);
}