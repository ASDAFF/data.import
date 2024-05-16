<?php
namespace Acrit\Import;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use JetBrains\PhpStorm\ExpectedValues;

Loc::loadMessages(__FILE__);

/**
 * managed log in module
 */
final class Log
{
	public const TYPE_MESSAGE = 0;
	public const TYPE_ERROR = 1;
	public const TYPE_SKIP = 2;
	public const TYPE_SUCCESS = 3;

	protected $profile_id;

	protected array $arLog;
	protected array $arImportStat;

	public function __construct()
	{
		$this->arLog = [];
		$this->arImportStat = [];
	}

	public function add($value, $type = self::TYPE_MESSAGE)
	{
		$this->arLog[] = [
			'text' => $value,
			'type' => $type,
		];
	}

	public function setProfileId($profile_id)
	{
		$this->profile_id = $profile_id;
	}

	public function save(array $arTypes = [self::TYPE_ERROR, self::TYPE_SKIP, self::TYPE_MESSAGE]): void
	{
		$arErrors = $this->getList($arTypes, true); // all save
		if (empty($arErrors)) {
			return;
		}
		// To file
		$logs_file = Option::get(\CAcritImport::MODULE_ID, "logs_path");
		if ($logs_file) {
			file_put_contents(Loader::getDocumentRoot() . $logs_file, implode("\n", $arErrors), FILE_APPEND);
			file_put_contents(Loader::getDocumentRoot() . $logs_file, "\n---\n" . date('d.m.Y H:i:s') . "\n\n", FILE_APPEND);
		}
		// To email
		$logs_email = Option::get(\CAcritImport::MODULE_ID, "logs_email");
		if ($logs_email) {
			$email_from = Option::get('main', 'email_from');
			$to         = $logs_email;
			$subject    = 'Acrit Import Logs, profile is ' . $this->profile_id;
			$message    = implode("\n", $arErrors);
			$headers    = '';
			if ($email_from) {
				$headers = 'From: ' . $email_from . "\r\n" .
					'Reply-To: ' . $email_from . "\r\n";
			}
			$headers .= 'X-Mailer: PHP/' . PHP_VERSION;
			mail($to, $subject, $message, $headers);
		}
		// To Event log
		if (Option::get(\CAcritImport::MODULE_ID, "logs_events") == 'Y') {
			\CEventLog::Add([
				"SEVERITY" => "SECURITY",
				"AUDIT_TYPE_ID" => "IMPORT_ERRORS",
				"MODULE_ID" => \CAcritImport::MODULE_ID,
				"ITEM_ID" => $this->profile_id,
				"DESCRIPTION" => implode("\n", $arErrors),
			]);
		}
	}

	public function getList($arTypes = [], $strip_tags = false): array
	{
		$arList = [];
		if (!empty($this->arLog)) {
			foreach ($this->arLog as $arItem) {
				if (in_array($arItem['type'], $arTypes)) {
					$arList[] = $strip_tags ? strip_tags($arItem['text']) : $arItem['text'];
				}
			}
		}
		return $arList;
	}

	/**
	 * get stat of error types
	 * @return int[]
	 */
	public function getStat(): array
	{
		$arRep = [
			self::TYPE_MESSAGE => 0,
			self::TYPE_ERROR => 0,
			self::TYPE_SKIP => 0,
			self::TYPE_SUCCESS => 0,
		];
		if (!empty($this->arLog)) {
			foreach ($this->arLog as $arItem) {
				$arRep[$arItem['type']]++;
			}
		}
		return $arRep;
	}

	public function getCount(): int
	{
		return count($this->arLog);
	}

	/**
	 * too save all log messages in lang/ru/log.php file
	 * @param $code
	 * @param $replace
	 * @return string|null
	 */
	public static function getMessage($code, $replace = null)
	{
		return Loc::getMessage($code, $replace);
	}

	// region Import Items Statistic

	public function setImportStatParam(string $param, $value)
	{
		$this->arImportStat[$param] = $value;
	}

	public function incImportStatParam(
		#[ExpectedValues(['imported_items', 'skipped_items', 'error_items'])]
		string $param,
		int $inc_value = 1
	)
	{
		if (!empty($param)) {
			if (!isset($this->arImportStat[$param])) {
				$this->arImportStat[$param] = 0;
			}
			$this->arImportStat[$param] += $inc_value;
		}
	}

	/**
	 * One run statistic
	 * @return array{'imported_items':int, 'skipped_items': int, 'error_items': int}
	 */
	public function getImportStat(): array
	{
		foreach (['imported_items', 'skipped_items', 'error_items'] as $key) {
			if (!isset($this->arImportStat[$key])) {
				$this->arImportStat[$key] = 0;
			}
		}
		return $this->arImportStat;
	}

	public function getImportStatStartMessage(string $dateTime): string
	{
		return self::getMessage('ACRIT_IMPORT_HEADER_RESULT_IMPORT_LOG', [
			'#DATETIME#' => $dateTime
		]);
	}
	public function getImportStatFooterMessage(string $progress = ''): string
	{
		$arStat   = $this->getStat();
		$arReport = [
			'success' => $arStat[self::TYPE_SUCCESS],
			'errors'  => $arStat[self::TYPE_ERROR],
			'skip'    => $arStat[self::TYPE_SKIP],
		];
		$arImportStat   = $this->getImportStat();
		$replace =  $arReport + $arImportStat;
		foreach ($replace as $k => $v) {
			$replace['#' . strtoupper($k) . '#'] = $v;
			unset($replace[$k]);
		}
		$replace['#PROGRESS_STR#'] = $progress;

		$message = self::getMessage('ACRIT_IMPORT_FOOTER_RESULT_IMPORT_LOG', $replace);
		return $message;
	}
	public function getImportStatFooterCompleteMessage(string $dateTime): string
	{
		return self::getMessage('ACRIT_IMPORT_FOOTER_RESULT_COMPLETE_IMPORT_LOG', [
			'#DATETIME#' => $dateTime
		]);
	}



	// endregion
}
