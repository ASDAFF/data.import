<?
set_time_limit(0);
ignore_user_abort(true);
$_SERVER["DOCUMENT_ROOT"] = $DOCUMENT_ROOT = realpath(dirname(__FILE__)."/..");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/acrit.import/scripts/run_bgrnd.php");
?>
