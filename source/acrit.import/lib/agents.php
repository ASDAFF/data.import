<?php
namespace Acrit\Import;

use Bitrix\Main;
use Bitrix\Main\Config\Option;

class Agents
{
	const MODULE_ID = "acrit.import";

	public static function getList($profile_id)
	{
		$list = [];
		$db   = \CAgent::GetList(["NAME" => "ASC"], ['MODULE_ID' => self::MODULE_ID]);
		while ($arItem = $db->Fetch()) {
			if (strpos($arItem['NAME'], 'runImport(' . $profile_id)) {
				preg_match('/runImport\([0-9]+, ([0-9]+)\)/', $arItem['NAME'], $matches);
				if (!empty($matches)) {
					$arItem['params']['num'] = $matches[1];
				} else {
					$arItem['params']['num'] = 1;
				}
				$arItem['INTERVAL_MIN_SUFFIX'] = GetMessage('ACRIT_IMPORT_AGENTS_INTERVAL_MIN');
				$list[]                        = $arItem;
			}
		}
		return $list;
	}

	public static function add($profile_id, $interval = 86400, $next_ts = false)
	{
		$res = false;
		if ($profile_id && $interval) {
			$list = self::getList($profile_id);
			if (!empty($list)) {
				$num = $list[count($list) - 1]['params']['num'];
			} else {
				$num = 0;
			}
			$num++;
			//\CAgent::RemoveAgent("\\Acrit\\Import\\Agents::runImport(" . $profile_id . ");", self::MODULE_ID);
			$next_date = '';
			if ($next_ts) {
				$next_date = ConvertTimeStamp($next_ts, 'FULL');
			}
			$res = \CAgent::AddAgent(
				"\\Acrit\\Import\\Agents::runImport(" . $profile_id . ", " . $num . ");",
				self::MODULE_ID,
				"Y",    // start time is floating when no-periodic agents
				$interval, "", "Y", $next_date);
		}
		return $res;
	}

	public static function remove($agent_id)
	{
		\CAgent::Delete($agent_id);
	}

	/**
	 * @param int $profile_id
	 * @param int $num
	 * @return string
	 * @use in /bitrix/modules/acrit.import/scripts/cron.php
	 */
	public static function runImport($profile_id, $num = 0): string
	{
		$returnStr = '\\' . __METHOD__ . '('. $profile_id . ", " . $num . ");";
		if (! $profile_id) {
			return $returnStr;
		}
		// Check other import runs
		if (self::isLocked($profile_id)) {
			return $returnStr;
		}

		// Run import
		$obImport = AcritImportGetImportObj($profile_id);
		if ($obImport) {
			self::delRunPos($profile_id);

			$bSourceChanged = $obImport->prepareSource();

			if ($bSourceChanged || (defined('IMPORT_FORCE_RUN') && IMPORT_FORCE_RUN === true)) {
				$count = $obImport->count();
				\CAcritImport::runBgrRequest('/bitrix/acrit.import_run_bgrnd.php', [
					'profile' => $profile_id,
					'count' => $count,
					'next_item' => 0,
				]);
			} else {
				\CAcritImport::Log( Log::getMessage('ACRIT_IMPORT_SOURCE_WONT_CHANGED_LOG') );
			}
		}
		return $returnStr;
	}

	/**
	 * Locking import
	 */
	public static function addLock($profile_id)
	{
		$arList = self::getLock();
		if (!$arList) {
			$arList = [];
		}
		$arList[$profile_id] = time();
		$sList               = serialize($arList);
		$res                 = Option::set(self::MODULE_ID, "profiles_run_lock", $sList);
		return $res;
	}

	public static function delLock($profile_id)
	{
		$res    = false;
		$arList = self::getLock();
		if ($arList[$profile_id]) {
			unset($arList[$profile_id]);
			$sList = serialize($arList);
			$res   = Option::set(self::MODULE_ID, "profiles_run_lock", $sList);
		}
		return $res;
	}

	public static function getLock()
	{
		$sList  = Option::get(self::MODULE_ID, "profiles_run_lock");
		/** @noinspection UnserializeExploitsInspection */
		$arList = unserialize($sList);
		return $arList;
	}

	/**
	 * @param $profile_id
	 * @return false
	 * @deprecated
	 */
	public static function isLocked($profile_id)
	{
		$res = false;
//        // Check by time of last run
//        $arList = self::getLock();
//        if ($arList[$profile_id]) {
//            $last_start_ts = $arList[$profile_id];
//            if ((time() - $last_start_ts) < (3600 * 24)) {
//                $res = true;
//            }
//        }
		return $res;
	}

	/**
	 * Check a duplicate runs of profile
	 */

	// Check
	public static function isDoubleRun($profile_id, $pos)
	{
		$res = false;
		// Check by position of last run
		$arList = self::getLockPos();
		//AddMessage2Log('$arList: '.print_r($arList, true));
		if (isset($arList[$profile_id])) {
			$last_pos = $arList[$profile_id];
			if ($pos <= $last_pos) {
				$res = true;
			}
		}
		return $res;
	}

	// Get list
	public static function getLockPos()
	{
		$sList  = Option::get(self::MODULE_ID, "profiles_run_lock_pos");
		/** @noinspection UnserializeExploitsInspection */
		$arList = unserialize($sList);
		return $arList;
	}

	// Add
	public static function addRunPos($profile_id, $pos)
	{
		$arList = self::getLockPos();
		if (!$arList) {
			$arList = [];
		}
		$arList[$profile_id] = $pos;
		$sList               = serialize($arList);
		$res                 = Option::set(self::MODULE_ID, "profiles_run_lock_pos", $sList);
		return $res;
	}

	// Reset
	public static function delRunPos($profile_id)
	{
		$res    = false;
		$arList = self::getLockPos();
		if (isset($arList[$profile_id])) {
			unset($arList[$profile_id]);
			$sList = serialize($arList);
			$res   = Option::set(self::MODULE_ID, "profiles_run_lock_pos", $sList);
		}
		return $res;
	}
}
