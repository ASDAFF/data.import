<?
// performance fixs
define("STOP_STATISTICS", true);
define("NO_KEEP_STATISTIC", true);
define("NO_AGENT_STATISTIC", "Y");
define("NOT_CHECK_PERMISSIONS", true);
define("DisableEventsCheck", true);
define("BX_SECURITY_SHOW_MESSAGE", true);

include($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");

$moduleId = 'acrit.import';

/**
 * @global $APPLICATION \CMain
 * @global $USER \CUser
 * @global $DB \CDatabase
 * @global $USER_FIELD_MANAGER \CUserTypeManager
 * @global $BX_MENU_CUSTOM \CMenuCustom
 * @global $stackCacheManager \CStackCacheManager
 */

$right = $APPLICATION->GetGroupRight($moduleId);
if (!check_bitrix_sessid() || $right < "W") {
	die();
}

IncludeModuleLangFile(__FILE__);

\Bitrix\Main\Loader::includeModule($moduleId);
\Bitrix\Main\Loader::includeModule("iblock");
\Bitrix\Main\Loader::includeModule("catalog");

$params             = $_REQUEST['params'];
$arResult           = [];
$arResult['status'] = 'error';
$arResult['log']    = [];

/** @var $action string */

switch ($action) {
	// Agents list
	case 'agents_list':
		if ($params['profile_id']) {
			$arResult['list']   = \Acrit\Import\Agents::getList($params['profile_id']);
			$arResult['status'] = 'ok';
		}
		break;
	// Add new agent
	case 'agents_add':
		if ($params['profile_id'] && $params['period_min']) {
			$start_time = false;
			if ($params['start_time'] && $DB->IsDate($params['start_time'], FORMAT_DATETIME)) {
				$start_time = MakeTimeStamp($params['start_time'], FORMAT_DATETIME);
			}
			\Acrit\Import\Agents::add($params['profile_id'], (int)$params['period_min'] * 60, $start_time);
			$arResult['status'] = 'ok';
		}
		break;
	// Add new agent
	case 'agents_del':
		if ($params['id']) {
			\Acrit\Import\Agents::remove($params['id']);
			$arResult['status'] = 'ok';
		}
		break;
	// Profile lock reset
	case 'profile_lock_reset':
		if ($params['id']) {
			\Acrit\Import\Agents::delLock($params['id']);
			\Acrit\Import\Agents::delRunPos($params['id']);
			$arResult['status'] = 'ok';
		}
		break;
	// Add email to BitrixCloudMonitoring
	case 'monitor_add':
		\Acrit\Import\Informer::UpdateBitrixCloudMonitoring($params['add_email']);
		$arResult['status'] = 'ok';
		break;
}

$APPLICATION->RestartBuffer();
echo \Bitrix\Main\Web\Json::encode($arResult);
