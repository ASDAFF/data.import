<?
/**
 * Example :
 * sudo -u bitrix php -f ROOT_PATH/bitrix/modules/acrit.import/scripts/cron.php 4
 * and see log in /home/bitrix/www/upload/acrit_import_steps.txt
 *
 * force fun without check file changes:
 * sudo -u bitrix php -f ROOT_PATH/bitrix/modules/acrit.import/scripts/cron.php 4 force
 */

// performance fixs
define("STOP_STATISTICS",       true);
define("NO_KEEP_STATISTIC",     true);
define("NO_AGENT_STATISTIC",    "Y");
define("NOT_CHECK_PERMISSIONS", true);
define("DisableEventsCheck",    true);
define("BX_SECURITY_SHOW_MESSAGE", true);
// Виртуальная сессия
define('BX_SECURITY_SESSION_VIRTUAL', true);

$_SERVER["DOCUMENT_ROOT"] = $DOCUMENT_ROOT = realpath(__DIR__ . "/../../../../");
require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php";

// subbuf reset
set_time_limit(0);
while (ob_get_level()) {
	ob_end_flush();
}

$profile_id = (int)$argv[1];
define('IMPORT_FORCE_RUN', isset($argv[2]) && $argv[2] == 'force');

CModule::IncludeModule('acrit.import');
\Acrit\Import\Agents::runImport($profile_id, 1);

// \EOF