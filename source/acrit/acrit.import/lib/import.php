<?php
namespace Acrit\Import;

use Bitrix\Main\Config\Option,
	Bitrix\Main\Loader,
	Bitrix\Main\Localization\Loc,
	Bitrix\Catalog\ProductTable,
	Bitrix\Catalog\PriceTable,
	Bitrix\Main\Web\HttpClient,
	Bitrix\Main\EventManager,
	Bitrix\Iblock\PropertyTable,
	Bitrix\Highloadblock\HighloadBlockTable;

use CAcritImport;

class Import
{
	use ImportRepository;
	use ImportHelpers;

	const KEY_DELIMITER = '_';
	const STEP_NO = 0;
	const STEP_BY_COUNT = 1;
	const STEP_BY_TYME = 2;
	const SOURCE_FILE = 'file';
	const SOURCE_URL = 'url';
	const SOURCE_FTP = 'ftp';
	const SOURCE_OAUTH = 'oauth';
	const SECTION_RESORT = false;

	/**
	 * @var array
	 * @see \Acrit\Import\ProfileTable::getMap()
	 */
	protected $arProfile;
	protected $arFieldsMap;

	protected $arFieldsParams;
	protected $arFieldsNameRepl;
	protected $arIBlockData;
	protected $arIBlockSections;
	/**
	 * @var Log
	 */
	protected $obLog;
	protected $arTypeParams;
	protected $row_i;

	protected $arIBlock;

	function __construct($ID = 0)
	{
		if ($ID) {
			$this->fillProfile($ID);
		} else {
			throw new \Exception(GetMessage("ACRIT_IMPORT_NE_ZADAN_PROFILQ_IMP"));
		}
	}

	/**
	 * @return array
	 * @see \Acrit\Import\ProfileTable::getMap()
	 */
	public function getArProfile(): array
	{
		return $this->arProfile;
	}

	/**
	 * @deprecated use getArProfile()
	 * @return array
	 */
	public function getProfile()
	{
		return $this->getArProfile();
	}

	/**
	 * @param array $arProfile
	 */
	public function setArProfile(array $arProfile): void
	{
		$this->arProfile = $arProfile;
	}

	protected function fillProfile($profile_id = 0)
	{
		if (!$this->arProfile) {
			$this->arProfile = ProfileTable::getById($profile_id)->fetch();
			// Additional data of profile type
			$arImportTypes      = AcritImportGetImportTypes();
			$this->arTypeParams = $arImportTypes[$this->arProfile['TYPE']];
			// Default source
			if (!$this->arProfile['SOURCE_TYPE']) {
				if (is_array($this->arTypeParams['source_types'])) {
					$this->arProfile['SOURCE_TYPE'] = $this->arTypeParams['source_types'][0];
				} else {
					$this->arProfile['SOURCE_TYPE'] = $this->arTypeParams['source_types'];
				}
			}
			// Fill source
			$this->setSource();

			if ((int)$this->arProfile['IBLOCK_ID'] > 0) {
				$this->arIBlock = \CIBlock::GetArrayByID( (int)$this->arProfile['IBLOCK_ID'] );
			}
		}
	}

	/**
	 * Prepare main types of sources
	 */
	protected function setSource()
	{
		if ($this->arProfile['SOURCE_TYPE'] == $this::SOURCE_FILE) {
			if (file_exists(Loader::getDocumentRoot() . $this->arProfile['SOURCE'])) {
				$this->arProfile['SOURCE'] = Loader::getDocumentRoot() . $this->arProfile['SOURCE'];
			} else {
				throw new \RuntimeException(GetMessage("ACRIT_IMPORT_ERROR_FNF"));
			}
		} elseif ($this->arProfile['SOURCE_TYPE'] == $this::SOURCE_URL) {
			$file_dest = $this->getTmpDir() . '/' . $this->arProfile['ID'];
			$file_dest .= $this->arTypeParams['file_ext'] ? '.' . $this->arTypeParams['file_ext'] : '';

			$this->arProfile['SOURCE_URL'] = $this->arProfile['SOURCE'];
			$this->arProfile['SOURCE'] = $file_dest;                        // TODO dont change source!
		} elseif ($this->arProfile['SOURCE_TYPE'] == $this::SOURCE_FTP) {
			//TODO: Fill
		} elseif ($this->arProfile['SOURCE_TYPE'] == $this::SOURCE_OAUTH) {
			//TODO: Fill
		}
	}

	public function fields()
	{
		return [];
	}

	/**
	 * Additional settings of profile
	 * @return array
	 */
	public function fieldsPreParams()
	{
		return [];
	}

	public function fieldsPreParamsValues($arFieldsParams)
	{
		if (!empty($arFieldsParams['fields'])) {
			foreach ($arFieldsParams['fields'] as $k => $arParam) {
				if (isset($this->arProfile['SOURCE_' . $arParam['DB_FIELD']])) {
					if ($arParam['TYPE'] == 'list_multiple') {
						$arFieldsParams['fields'][$k]['VALUE'] = explode(',', $this->arProfile['SOURCE_' . $arParam['DB_FIELD']]);
					} else {
						$arFieldsParams['fields'][$k]['VALUE'] = $this->arProfile['SOURCE_' . $arParam['DB_FIELD']];
					}
				} else {
					$arFieldsParams['fields'][$k]['VALUE'] = $arParam['DEFAULT'];
				}
			}
		}
		return $arFieldsParams;
	}

	/**
	 * Параметры профиля, используются как в интерфейсе, так и при сохранении
	 * @return array[]
	 */
	public static function getProfileParams()
	{
		return [
			'SOURCE' => [
				'name' => GetMessage("ACRIT_IMPORT_FIELDS_SOURCE_NAME"),
				'hint' => GetMessage("ACRIT_IMPORT_FIELDS_SOURCE_NAME_HINT"),
				'default' => '',
				'display' => true,
			],
			'SOURCE_TYPE' => [
				'name' => GetMessage("ACRIT_IMPORT_FIELDS_SOURCE_TYPE_NAME"),
				'hint' => GetMessage("ACRIT_IMPORT_FIELDS_SOURCE_TYPE_NAME_HINT"),
				'default' => '',
				'display' => true,
			],
			'SOURCE_LOGIN' => [
				'name' => GetMessage("ACRIT_IMPORT_FIELDS_SOURCE_LOGIN_NAME"),
				'hint' => GetMessage("ACRIT_IMPORT_FIELDS_SOURCE_LOGIN_NAME_HINT"),
				'default' => '',
				'display' => true,
			],
			'SOURCE_KEY' => [
				'name' => GetMessage("ACRIT_IMPORT_FIELDS_SOURCE_KEY_NAME"),
				'hint' => GetMessage("ACRIT_IMPORT_FIELDS_SOURCE_KEY_NAME_HINT"),
				'default' => '',
				'display' => true,
			],
			'ENCODING' => [
				'name' => GetMessage("ACRIT_IMPORT_FIELDS_ENCODING_NAME"),
				'hint' => GetMessage("ACRIT_IMPORT_FIELDS_ENCODING_HINT"),
				'default' => '',
				'display' => true,
			],
		];
	}

	public function count()
	{
		return 0;
	}

	public function onBeforeImport($type = self::STEP_NO, $limit = 0, $next_item = 0): void
	{
		if ($next_item == 0) {
			// start per-step import run, first step
			$handlers = EventManager::getInstance()->findEventHandlers(\CAcritImport::MODULE_ID, "OnBeforeImportProfileRun");
			foreach ($handlers as $handler) {
				\ExecuteModuleEventEx($handler, [$this]);
			}
		}

		// per-step import run, every step
		$handlers = EventManager::getInstance()->findEventHandlers(\CAcritImport::MODULE_ID, "OnBeforeImportProfileRunStep");
		foreach ($handlers as $handler) {
			\ExecuteModuleEventEx($handler, [$this, $type, $limit, $next_item]);
		}
	}

	public function getProfileAddSettings()
	{
		$arProfileAddSettings = $this->fieldsPreParams();
		$arProfileAddSettings = $this->fieldsPreParamsValues($arProfileAddSettings);
		return $arProfileAddSettings;
	}

	protected function initLog(): void
	{
		if (!$this->obLog) {
			$this->obLog = new Log();
			$this->obLog->setProfileId($this->arProfile['ID']);
		}
	}

	public function getLog()
	{
		$this->initLog();
		return $this->obLog;
	}

	/**
	 * Prepare main types of sources. Now only SOURCE_URL id supported and download file if its changed.
	 * @params int $hoursChanged = 0 just in case recheck after hours, or only by remote size
	 * @return bool true if source is changed.
	 */
	public function prepareSource(int $hoursChanged = 0): bool
	{
		$bSourceChanged = true;
		try {
			// Load data from other server
			if ($this->arProfile['SOURCE_TYPE'] == $this::SOURCE_URL) {
				$file_dest = $this->getTmpDir() . '/' . $this->arProfile['ID'];
				$file_dest .= $this->arTypeParams['file_ext'] ? '.' . $this->arTypeParams['file_ext'] : '';

				$this->arProfile['SOURCE_LOGIN'] = trim((string)$this->arProfile['SOURCE_LOGIN']);
				$this->arProfile['SOURCE_KEY'] = trim((string)$this->arProfile['SOURCE_KEY']);

				$bSourceChanged = !file_exists($file_dest)
					|| CAcritImport::getRemoteFileSize($this->arProfile['SOURCE_URL'], $this->arProfile['SOURCE_LOGIN'], $this->arProfile['SOURCE_KEY']) !== filesize($file_dest);

				if ($bSourceChanged || ($hoursChanged > 0 && (time() - filemtime($file_dest)) > 3600 * $hoursChanged)) {
					$this->copyImportFile($file_dest, (int)Option::get(\CAcritImport::MODULE_ID, 'history_cnt_url_files', 0));
				}
			}
		} catch (\Throwable $error) {
			$handlers = EventManager::getInstance()->findEventHandlers(\CAcritImport::MODULE_ID, "OnPrepareSourceError");
			foreach ($handlers as $handler) {
				\ExecuteModuleEventEx($handler, [$this, $error]);
			}
			$bSourceChanged = false;
		}
		return $bSourceChanged;
	}

	/**
	 * May be used in OnPrepareSourceError-event
	 * @return bool
	 */
	public function clearCachedSource(): bool
	{
		if ($this->arProfile['SOURCE_TYPE'] == $this::SOURCE_URL) {
			$file_dest = $this->getTmpDir() . '/' . $this->arProfile['ID'];
			$file_dest .= $this->arTypeParams['file_ext'] ? '.' . $this->arTypeParams['file_ext'] : '';
			if (file_exists($file_dest)) {
				return unlink($file_dest);
			}
		}
		return true;
	}

	/**
	 * Fill the fields map
	 */
	protected function fillFieldsMap(): void
	{
		if (!$this->arFieldsMap) {
			$this->arFieldsMap = [];
			$obProfileFields   = new ProfileFieldsTable();
			$res               = $obProfileFields::getList([
				'order' => ['ID' => 'asc'],
				'filter' => ['PARENT_ID' => $this->arProfile['ID']],
			]);
			while ($arField = $res->fetch()) {
				$this->arFieldsMap[$arField['F_FIELD']] = $arField['IB_FIELD'];
			}
		}
	}

	/**
	 * Fill the fields params
	 */
	protected function fillFieldsParams(): void
	{
		if (!$this->arFieldsParams) {
			$this->arFieldsParams = [];
			$obProfileFields      = new ProfileFieldsTable();
			$res                  = $obProfileFields::getList([
				'order' => ['ID' => 'asc'],
				'filter' => ['PARENT_ID' => $this->arProfile['ID']],
			]);
			while ($arField = $res->fetch()) {
				$this->arFieldsParams[$arField['F_FIELD']] = $arField['PARAMS'];
			}
		}
	}

	/**
	 * Fill the codes confirmity
	 */
	protected function fillFieldsNameRepl(): void
	{
		if (!$this->arFieldsNameRepl) {
			$this->arFieldsNameRepl = [
				"SEO_H1" => "ELEMENT_PAGE_TITLE",
				"SEO_TITLE" => "ELEMENT_META_TITLE",
				"SEO_KEYWORDS" => "ELEMENT_META_KEYWORDS",
				"SEO_DESCRIPTION" => "ELEMENT_META_DESCRIPTION",
				"SEO_PREVIEW_PICTURE_ALT" => "ELEMENT_PREVIEW_PICTURE_FILE_ALT",
				"SEO_PREVIEW_PICTURE_TITLE" => "ELEMENT_PREVIEW_PICTURE_FILE_TITLE",
				"SEO_PREVIEW_PICTURE_FILENAME" => "ELEMENT_PREVIEW_PICTURE_FILE_NAME",
				"SEO_DETAIL_PICTURE_ALT" => "ELEMENT_DETAIL_PICTURE_FILE_ALT",
				"SEO_DETAIL_PICTURE_TITLE" => "ELEMENT_DETAIL_PICTURE_FILE_TITLE",
				"SEO_DETAIL_PICTURE_FILENAME" => "ELEMENT_DETAIL_PICTURE_FILE_NAME",
				"CATEG_PARAMS_NAME" => "NAME",
				"CATEG_PARAMS_CODE" => "CODE",
				"CATEG_PARAMS_EXTERNAL_ID" => "EXTERNAL_ID",
				"CATEG_PARAMS_PARENT_ID" => "IBLOCK_SECTION_ID",
				"CATEG_PARAMS_ACTIVE" => "ACTIVE",
				"CATEG_PARAMS_SORT" => "SORT",
				"CATEG_PARAMS_IMAGE" => "PICTURE",
				"CATEG_PARAMS_PICTURE" => "DETAIL_PICTURE",
				"CATEG_PARAMS_DESCRIPTION" => "SECTION_META_DESCRIPTION",
				"CATEG_PARAMS_SEO_H1" => "SECTION_PAGE_TITLE",
				"CATEG_PARAMS_SEO_TITLE" => "SECTION_META_TITLE",
				"CATEG_PARAMS_SEO_KEYWORDS" => "SECTION_META_KEYWORDS",
				"CATEG_PARAMS_SEO_DESCRIPTION" => "SECTION_META_DESCRIPTION",
			];

			$userFieldList = Helper::getUserFieldList([
				'=ENTITY_ID' => sprintf('IBLOCK_%d_SECTION', $this->getArProfile()['IBLOCK_ID'])
			]);
			foreach ($userFieldList as $sectionUField) {
				$internalName = 'CATEG_PARAMS_' . $sectionUField['FIELD_NAME'];
				$this->arFieldsNameRepl[ $internalName ] = $sectionUField['FIELD_NAME'];
			}
		}
	}

	/**
	 * @link https://www.acrit-studio.ru/technical-support/nastroyka-modulya-universalnyy-import/optsii-i-modifikatory-dlya-poley/
	 * @param $value
	 * @param $field_k
	 * @return array|float|int|mixed|string|string[]|null
	 */
	protected function modifValueByParams($value, $field_k)
	{
		if (!empty($this->arFieldsParams[$field_k])) {
			if ($this->arFieldsParams[$field_k]['url_decode'] == 'Y') {
				$value = urldecode($value);
			}
			if ($this->arFieldsParams[$field_k]['register_change']) {
				switch ($this->arFieldsParams[$field_k]['register_change']) {
					case 'low':
						$value = strtolower($value);
						break;
					case 'up':
						$value = strtoupper($value);
						break;
					case 'first':
						$value = ucfirst($value);
						break;
					case 'each':
						$value = ucwords($value);
						break;
				}
			}
			if ($this->arFieldsParams[$field_k]['str_limit']) {
				$value = substr($value, 0, (int)$this->arFieldsParams[$field_k]['str_limit']);
			}
			if ($this->arFieldsParams[$field_k]['str_dateformat']) {
				/** @noinspection GlobalVariableUsageInspection */
				$DB    = $GLOBALS["DB"];
				$value = $DB->FormatDate($value, $this->arFieldsParams[$field_k]['str_dateformat'], \CSite::GetDateFormat("FULL"));
			}
			if ($this->arFieldsParams[$field_k]['cut_htmltags'] == 'Y') {
				$value = strip_tags($value);
			}
			if ($this->arFieldsParams[$field_k]['cut_special'] == 'Y') {
				$value = $this->clearStr($value);
			}
			if ($this->arFieldsParams[$field_k]['html_to_special'] == 'Y') {
				$value = htmlspecialchars_decode($value);
			}
			if ($this->arFieldsParams[$field_k]['num_round']['checked'] == 'Y') {
				$value = (float)str_replace(',', '.', $value);
				switch ($this->arFieldsParams[$field_k]['num_round']['ADD_PARAMS']) {
					case 'GENERAL':
						$method = "none";
						break;
					case 'TOHIGHEST':
						$method = PHP_ROUND_HALF_UP;
						break;
					case 'TOLOWEST':
						$method = PHP_ROUND_HALF_DOWN;
						break;
					case 'TOEVEN':
						$method = PHP_ROUND_HALF_EVEN;
						break;
					case 'TOODD':
						$method = PHP_ROUND_HALF_ODD;
						break;
					case 'TONINE':
						$method = 'TONINE';
						break;
					default:
						$method = PHP_ROUND_HALF_UP;
				}
				switch ($this->arFieldsParams[$field_k]['num_round']['ADD_PRECISION']) {
					case 'TOSOTOIA':
						$presicion     = 2;
						$multiplicator = 0.01;
						break;
					case 'TODESYATAYA':
						$presicion     = 1;
						$multiplicator = 0.1;
						break;
					case 'TOONE':
						$presicion     = 0;
						$multiplicator = 1;
						break;
					case 'TODOZEN':
						$presicion     = -1;
						$multiplicator = 10;
						break;
					case 'TOHUNDREDS':
						$presicion     = -2;
						$multiplicator = 100;
						break;
					case 'THOUSENDS':
						$presicion     = -3;
						$multiplicator = 1000;
						break;
					default:
						$presicion     = 0;
						$multiplicator = 1;
				}
				if ($method == 'none') {
					$value = round($value, $presicion);
				} elseif ($method == 'TONINE') {
					$value = ((int)($value / $multiplicator) + (9 - (int)($value / $multiplicator) % 10)) * $multiplicator;
				} else {
					$value = round($value, $presicion, $method);
				}
			}
			if (trim($this->arFieldsParams[$field_k]['formula'])) {
				$formula = trim($this->arFieldsParams[$field_k]['formula']);
				$formula = str_replace("X1", $value, $formula);
				try {
					$value = eval("return $formula;");
				} catch (\Throwable $error) {
					$this->obLog->add('(saveIBData->modifValueByParams) formula error: ' . $error->getMessage(), Log::TYPE_ERROR);
					$value = '';
				}
			}
		}
		return $value;
	}

	protected function modifPictureByParams($value, $field_k)
	{
		if ($this->arFieldsParams[$field_k]['work_picture']['checked'] == 'Y') {
			$iwidth  = $this->arFieldsParams[$field_k]['work_picture']['width'];
			$iheight = $this->arFieldsParams[$field_k]['work_picture']['height'];
			if ($iwidth != '' && $iheight != '' && $arTempFile = \CFile::MakeFileArray($value)) {
				$resize_type = $this->arFieldsParams[$field_k]['work_picture']['process_type'] == 'cut' ? BX_RESIZE_IMAGE_EXACT : BX_RESIZE_IMAGE_PROPORTIONAL;
				$quality     = $this->arFieldsParams[$field_k]['work_picture']['quality'] != '' ? $this->arFieldsParams[$field_k]['work_picture']['quality'] : false;
				$destinationFile = $_SERVER['DOCUMENT_ROOT'] . "/upload/tmp/" . date("dmY") . "/" . "resized" . date("dmY") . rand(1, 1000) . ".jpg";

				if (\CFile::ResizeImageFile(
					$sourceFile = $arTempFile['tmp_name'],
					$destinationFile,
					$arSize = ['width' => $iwidth, 'height' => $iheight],
					$resizeType = $resize_type,
					$arWaterMark = [],
					$jpgQuality = $quality,
					$arFilters = false
				)) {
					$value = $destinationFile;
				} // If image wasn't created
				else {
					$value = false;
				}
			}
		}
		return $value;
	}

	protected function checkRequiredByParams($value, $field_k)
	{
		$res = true;
		// Check value
		if (isset($this->arFieldsParams[$field_k]['required']) && $this->arFieldsParams[$field_k]['required'] == 'Y'
			&& !$value) {
			$res = false;
		}
		// Check required values
		if (!empty(array_filter($this->arFieldsParams[$field_k]['cond_values_req']))) {
			$check = false;
			foreach ($this->arFieldsParams[$field_k]['cond_values_req'] as $check_value) {
				if ($check_value && $value == $check_value) {
					$check = true;
				}
			}
			if (!$check) {
				$res = false;
			}
		}
		// Check excluded values
		if (!empty(array_filter($this->arFieldsParams[$field_k]['cond_values_excl']))) {
			foreach ($this->arFieldsParams[$field_k]['cond_values_excl'] as $check_value) {
				if ($check_value && $value == $check_value) {
					$res = false;
				}
			}
		}
		return $res;
	}

	/**
	 * Import item in iblock
	 */
	protected function saveIBData($arInputRow, $row_i = 0)
	{
		$arImpRes = [
			'errors'        => [],
			'message'       => []
		];
		$this->initLog();
		$this->row_i = $row_i;

		\CAcritImport::Log('(saveIBData) arInputRow ' . print_r($arInputRow, true));

		if (empty(array_filter($arInputRow))) {
			\CAcritImport::Log('(saveIBData) Error: ' . GetMessage("ACRIT_IMPORT_SAVEIBDATA_ERROR_EMPTY"));
			$arImpRes['errors'][] = [
				GetMessage("ACRIT_IMPORT_SAVEIBDATA_ERROR_EMPTY"),
			];
			return $arImpRes;
		}
		if (!$this->arProfile['ID'] || !$this->arProfile['IBLOCK_ID']) {
			\CAcritImport::Log('(saveIBData) Error: ' . GetMessage("ACRIT_IMPORT_NE_ZADANY_BAZOVYE_DA"));
			$arImpRes['errors'][] = [
				GetMessage("ACRIT_IMPORT_NE_ZADANY_BAZOVYE_DA"),
			];
			return $arImpRes;
		}
		$this->fillFieldsMap();
		if (!$this->arFieldsMap || !is_array($this->arFieldsMap) || empty($this->arFieldsMap)) {
			\CAcritImport::Log('(saveIBData) Error: ' . GetMessage("ACRIT_IMPORT_NE_ZADANY_BAZOVYE_DA"));
			$arImpRes['errors'][] = [
				GetMessage("ACRIT_IMPORT_NE_ZADANY_BAZOVYE_DA"),
			];
			return $arImpRes;
		}
		$this->fillFieldsParams();
		$this->fillIBlockData($this->arProfile['IBLOCK_ID']);
		$this->fillFieldsNameRepl();
		// default update filelds
		$arIBFields     = [
			'IBLOCK_ID' => $this->arProfile['IBLOCK_ID'],
		];
		$arIBPropValues = [];
		$arIdentifier   = false;
		$arFilter       = [
			'IBLOCK_ID' => $this->arProfile['IBLOCK_ID'],
		];

		$arItemProduct          = [];
		$arItemStores           = [];
		$arItemPrices           = [];
		$arItemPricesCurrencies = [];
		$arAUCategory           = [];
		$arSectFields           = [];
		$arTmpFiles             = [];

		$bUpdateSearch = \CAcritImport::searchModuleIndexingEnabled();

		\Bitrix\Main\Diag\Debug::startTimeLabel('Create new infoblock properties');

		// region Create new infoblock properties
		// And Try to group unused multiple values, ex. "yml_catalog_shop_offers_offer_picture_2", "yml_catalog_shop_offers_offer_picture_3", ...
		$arFieldsNames = $this->fields();
		foreach ($arInputRow as $k => $value) {
			if (isset($this->arFieldsMap[$k]) && strpos($this->arFieldsMap[$k], 'CREATE_PROP__') === 0) {
				$params_codes = explode('__', $this->arFieldsMap[$k]);

				$name         = $arFieldsNames[$k]['NAME'];
				$iblock_id    = $this->arProfile['IBLOCK_ID'];
				$profile_id   = $this->arProfile['ID'];
				$arParams     = [];
				if ($params_codes[1] == 'STRING') {
					$arParams['type'] = IblockProp::TYPE_STRING;
				}
				if ($params_codes[2] == 'MULT') {
					$arParams['multiple'] = true;
				}
				$props       = new IblockProp($iblock_id, $profile_id, $name, $arParams);
				$new_prop_id = $props->create();
				// Set new prop in the fields map
				if ($new_prop_id) {
					$new_prop_code         = 'PROP_' . $new_prop_id;
					$this->arFieldsMap[$k] = $new_prop_code;
					// Save in DB
					$res = ProfileFieldsTable::getList([
						'filter' => [
							'=PARENT_ID' => $this->arProfile['ID'],
							'=F_FIELD' => $k,
						],
						'select' => ['ID'],
						"cache" => ["ttl" => 3600, "cache_joins" => true]
					]);
					if ($p_field = $res->fetch()) {
						$pf_id           = $p_field['ID'];
						ProfileFieldsTable::update($pf_id, [
							'IB_FIELD' => $new_prop_code,
						]);
					}
				}
			} elseif (!isset($this->arFieldsMap[$k]) && preg_match('#(?<field>.+?)(?<cnt>\d+)$#', $k, $kMatch) && (int)$kMatch['cnt'] > 1) {
				// Try to group unused multiple values, ex. "yml_catalog_shop_offers_offer_picture_2", "yml_catalog_shop_offers_offer_picture_3", ...
				$groupKey = $kMatch['field'] . '1';
				if (!is_array($arInputRow[$groupKey])) {
					$arInputRow[$groupKey] = [$arInputRow[$groupKey]];
				}
				$arInputRow[$groupKey][] = $value;
			}
		}
		// endregion

		$handlers = EventManager::getInstance()->findEventHandlers(\CAcritImport::MODULE_ID, "OnStartProcessRowInSaveIBData");
		foreach ($handlers as $handler) {
			\ExecuteModuleEventEx($handler, [$this, &$arInputRow]);
		}

		\Bitrix\Main\Diag\Debug::endTimeLabel('Create new infoblock properties');
		\Bitrix\Main\Diag\Debug::startTimeLabel('Find item in database');

		// region Find item in database
		$ib_item_id = false;
		foreach ($arInputRow as $k => $value) {
			if (! isset($this->arFieldsMap[$k])) {
				continue;
			}
			if (strpos($this->arFieldsMap[$k], 'PROP_') === 0) {
				$prop_k = str_replace('PROP_', '', $this->arFieldsMap[$k]);
				// Element identifier
				if ($k == $this->arProfile['ELEMENT_IDENTIFIER'] && !is_array($value)) {
					$value        = trim($value);
					$value        = $this->modifValueByParams($value, $k);
					$arIdentifier = [
						'key' => 'PROPERTY_' . $prop_k,
						'value' => $value
					];
					break;
				}
			} else {
				// Element identifier
				if ($k == $this->arProfile['ELEMENT_IDENTIFIER']) {
					$value        = $this->modifValueByParams($value, $k);
					$arIdentifier = [
						'key' => $this->arFieldsMap[$k],
						'value' => $value
					];
					break;
				}
			}
		}
		if ($arIdentifier['key'] && $arIdentifier['value']) {
			$arFilter[/*'=' .*/$arIdentifier['key']] = $arIdentifier['value'];
		} else {
			\CAcritImport::Log('(saveIBData) Error: ' . GetMessage("ACRIT_IMPORT_SAVEIBDATA_ERROR_NOTFINDID"));
			$arImpRes['errors'][] = [
				GetMessage("ACRIT_IMPORT_SAVEIBDATA_ERROR_NOTFINDID"),
			];
			return $arImpRes;
		}

		$handlers = EventManager::getInstance()->findEventHandlers(\CAcritImport::MODULE_ID, "OnBeforeFindExistsItemInSaveIBData");
		foreach ($handlers as $handler) {
			\ExecuteModuleEventEx($handler, [$this, &$arInputRow, &$arFilter]);
		}

		// Try to find item
		$arIBItem = [];

		$handlers = EventManager::getInstance()->findEventHandlers(\CAcritImport::MODULE_ID, "OnFindItem");
		foreach ($handlers as $arEvent) {
			// make own user fast select item, ex. on raw sql-query
			$ib_item_id = \ExecuteModuleEventEx($arEvent, [$this, $arFilter]);
			if ($ib_item_id > 0) {
				break;
			}
		}
		if ($ib_item_id === false) {
			$res = \CIBlockElement::GetList(['ID' => 'asc'], $arFilter, false, false, ['ID', 'IBLOCK_ID']);
			if ($obItem = $res->GetNextElement()) {
				$arIBItem   = $obItem->GetFields();
				$ib_item_id = $arIBItem['ID'];
			}
		}

		// Ignore new or exist elements if option set
		if ((!$ib_item_id && $this->arProfile['ACTIONS_NEW_ELEMENTS'] == 'not_create')
			|| ($ib_item_id && $this->arProfile['ACTIONS_EXIST_ELEMENTS'] == 'ignore')
		) {
			$this->obLog->incImportStatParam('skipped_items');
			return $arImpRes;
		}

		// Load properties
		if ($ib_item_id) {
			$res = \CIBlockElement::GetList(['ID' => 'asc'], ['IBLOCK_ID' => $this->arProfile['IBLOCK_ID'], 'ID' => $ib_item_id]);
			if ($obItem = $res->GetNextElement()) {
				$arIBItem               = $obItem->GetFields();
				$arIBItem["PROPERTIES"] = $obItem->GetProperties();
			}
		}
		// endregion

		\Bitrix\Main\Diag\Debug::endTimeLabel('Find item in database');

		$handlers = EventManager::getInstance()->findEventHandlers(\CAcritImport::MODULE_ID, "OnBeforeProcessRowInSaveIBData");
		foreach ($handlers as $handler) {
			\ExecuteModuleEventEx($handler, [$this, &$arInputRow, $arIBItem]);
		}

		\Bitrix\Main\Diag\Debug::startTimeLabel('Fill data');

		// region Fill data
		foreach ($arInputRow as $k => $value) {
			if (! isset($this->arFieldsMap[$k])) {
				continue;
			}
			// If has multiple delimiter
			if (!is_array($value) && $this->arFieldsParams[$k]['milt_delimiter'] && strpos($value, $this->arFieldsParams[$k]['milt_delimiter']) !== false) {
				$value = explode($this->arFieldsParams[$k]['milt_delimiter'], $value);
			}
			// Prepare value
			if (is_array($value)) {
				foreach ($value as $i => $val) {
					if (!is_array($val)) {
						$value[$i] = trim($val);
					}
				}
			} else {
				$value = trim($value);
			}
			// Check required param
			if (!$this->checkRequiredByParams($value, $k)) {
				$this->obLog->incImportStatParam('skipped_items');
				$this->obLog->add(GetMessage("ACRIT_IMPORT_STROKA") . ' ' . $this->row_i . ' (' . $arIdentifier['key'] . ' = "' . $arIdentifier['value'] . '"): ' . GetMessage("ACRIT_IMPORT_PROPUSK"), Log::TYPE_SKIP);
				return false;
			}
			// Properties
			if (strpos($this->arFieldsMap[$k], 'PROP_') === 0) {
				$prop_k = str_replace('PROP_', '', $this->arFieldsMap[$k]);
				if ($ib_item_id && $this->arFieldsParams[$k]['not_empty'] == 'Y' && $arIBItem['PROPERTIES'][$prop_k]['VALUE']) {
					continue;
				}
				if (isset($this->arIBlockData['PROPS'][$prop_k])) {
					// fill VALUES_BY_ID to E-props, L-props, S-directory-props
					$this->addNewRelayedItemsToIblockFromValues($prop_k, $value);

					$arPropData = $this->arIBlockData['PROPS'][$prop_k];

					// Get IDs of list values
					if ($arPropData['PROPERTY_TYPE'] == 'L' || $arPropData['PROPERTY_TYPE'] == 'E'
						|| ($arPropData['PROPERTY_TYPE'] == 'S' && $this->isPropertyType_S_Complex($arPropData))
					) {
						if (isset($arPropData['VALUES_BY_ID']) && is_array($arPropData['VALUES_BY_ID'])) {
							$arIDs = array_flip($arPropData['VALUES_BY_ID']);
							if (is_array($value)) {
								foreach ($value as $i => $value_val) {
									if ($arIDs[$value_val]) {
										$value[$i] = $arIDs[$value_val];
									}
								}
							} else {
								$value = $arIDs[$value];
							}
						}
					} // Get files data
					elseif ($arPropData["PROPERTY_TYPE"] == 'F') {
						if (is_array($value)) {
							foreach ($value as $i => $value_val) {
								// Prepare modifications of path
								$value_val = $this->modifValueByParams($value_val, $k);
								// If there is enabled to process the image and do not use the information block settings
								if ($this->arFieldsParams[$k]["work_picture"]["checked"] == "Y" && $this->arProfile['ACTIONS_IB_IMG_MODIF'] != 'Y') {
									// If can't create a new picture and can't return image path
									if (!$path = $this->modifPictureByParams($this->arProfile["IMGS_SOURCE_PATH"] . $value_val, $k)) {
										// Get old picture
										$path = $this->arProfile["IMGS_SOURCE_PATH"] . $value_val;
									}
								} else {
									$path = $this->arProfile["IMGS_SOURCE_PATH"] . $value_val;
								}
								if ($path) {
									if ($url_path = $this->getServerFile($path)) {
										$path         = $url_path;
										$arTmpFiles[] = $url_path;
									}
									$arTmpFile = \CFile::MakeFileArray($path);
									$value[$i] = ['VALUE' => $arTmpFile, 'DESCRIPTION' => $arTmpFile['name']];
								}
								// make possible to delete files in handlers by pass "del" => "Y"
								if (isset($value_val['del'])) {
									$value[$i]['del'] = $value_val['del'];
								}
							}
						} else {
							// Prepare modifications of path
							$value = $this->modifValueByParams($value, $k);
							// If there is enabled to process the image and do not use the information block settings
							if ($this->arFieldsParams[$k]["work_picture"]["checked"] == "Y" && $this->arProfile['ACTIONS_IB_IMG_MODIF'] != 'Y') {
								// If can't create a new picture and can't return image path
								if (!$path = $this->modifPictureByParams($this->arProfile["IMGS_SOURCE_PATH"] . $value, $k)) {
									// Get old picture
									$path = $this->arProfile["IMGS_SOURCE_PATH"] . $value;
								}
							} else {
								$path = $this->arProfile["IMGS_SOURCE_PATH"] . $value;
							}
							if ($path) {
								if ($url_path = $this->getServerFile($path)) {
									$path         = $url_path;
									$arTmpFiles[] = $url_path;
								}
								$arTmpFile = \CFile::MakeFileArray($path);
								$value     = ['VALUE' => $arTmpFile, 'DESCRIPTION' => $arTmpFile['name']];
							}
						}
					}

					// Multiple value must be array
					if ($arPropData["MULTIPLE"] == "Y" && !is_array($value)) {
						$value = [$value];
					}
					// Value modifications
					if ($arPropData["PROPERTY_TYPE"] != 'F') {
						if (is_array($value)) {
							foreach ($value as $i => $value_val) {
								$value[$i] = $this->modifValueByParams($value_val, $k);
							}
						} else {
							$value = $this->modifValueByParams($value, $k);
						}
					}
					// Save value
					// $arIBPropValues[$prop_k] = $value;
					if (isset($arIBPropValues[$prop_k])) {
						if (is_array($arIBPropValues[$prop_k]) && isset($arIBPropValues[$prop_k][0])) {
							if (isset($value[0])) {
								$arIBPropValues[$prop_k] = array_merge($arIBPropValues[$prop_k], $value);
							} else {
								$arIBPropValues[$prop_k][] = $value;
							}
						} else {
							$arIBPropValues[$prop_k] = [$arIBPropValues[$prop_k], $value];
						}
					} else {
						$arIBPropValues[$prop_k] = $value;
					}
				}
			} // Sections relay to elements
			elseif (strpos($this->arFieldsMap[$k], 'CATEGORY_') === 0) {
				$section_field = false;
				if (strpos($this->arFieldsMap[$k], 'CATEGORY_XML_ID') === 0) {
					$section_field = 'XML_ID';
				} elseif (strpos($this->arFieldsMap[$k], 'CATEGORY_CODE') === 0) {
					$section_field = 'CODE';
				} elseif (strpos($this->arFieldsMap[$k], 'CATEGORY_NAME') === 0) {
					$section_field = 'NAME';
				} elseif (strpos($this->arFieldsMap[$k], 'CATEGORY_ID') === 0) {
					$section_field = 'ID';
				}
				if ($section_field) {
					// Sections hierarchy in YML, get section names and reuse code above
					if (!is_array($value) && $this->arFieldsParams[$k]['sect_hierarchy'] == 'Y' && $this->arProfile['ACTIONS_SECTIONS_CREATE'] == 'Y') {
						if ($section_field == 'NAME') {
							if (method_exists($this, 'getInProfileSectionsHierarchy')) {
								$sectionsOfferInImport = $this->getInProfileSectionsHierarchy($arInputRow);
								if (count($sectionsOfferInImport) > 1) {
									$value = $sectionsOfferInImport;
									unset($sectionsOfferInImport);
								}
							}
						}
					}

					// For multiple fields
					if (is_array($value)) {
						$arClear = [];
						foreach ($value as $i => $item_val) {
							if (trim($item_val)) {
								$arClear[] = trim($item_val);
							}
						}
						$value = $arClear;
						unset($arClear);
						$arSectsForAdd = [];
						// Sections list
						if ($this->arFieldsParams[$k]['sect_hierarchy'] != 'Y') {
							foreach ($value as $i => $item_val) {
								if ($section_id = $this->getIBSectionId($section_field, $item_val)) {
									$arIBFields['IBLOCK_SECTION'][] = $section_id;
								} elseif ($section_field == 'NAME') {
									$arSectsForAdd[] = $item_val;
								}
							}
							// Create news sections
							if (!empty($arSectsForAdd) && $this->arProfile['ACTIONS_SECTIONS_CREATE'] == 'Y') {
								$this->createSectionsList($arSectsForAdd, $arIBFields["IBLOCK_ID"], $arIBFields['IBLOCK_SECTION']);
							}
						} // Sections hierarchy
						else {
							$section_id  = 0;
							$last_finded = 0;
							foreach ($value as $i => $item_val) {
								if ($section_id !== false && $section_id = $this->getIBSectionId($section_field, $item_val, $section_id)) {
									$last_finded = $section_id;
								} else if ($section_field == 'NAME') {
									$arSectsForAdd[] = $item_val;
								}
							}
							// Create news sections hierarchy
							if (!empty($arSectsForAdd)) {
								if ($this->arProfile['ACTIONS_SECTIONS_CREATE'] == 'Y') {
									$this->createSectionsHierarchy($arSectsForAdd, $arIBFields["IBLOCK_ID"], $last_finded, $arIBFields['IBLOCK_SECTION']);
								}
							} else {
								$arIBFields['IBLOCK_SECTION'][] = $last_finded;
							}
						}
					} else {
						if ($section_id = $this->getIBSectionId($section_field, $value)) {
							$arIBFields['IBLOCK_SECTION'][] = $section_id;
						} else {
							// Only for single value field
							$arSectFields[$section_field] = $value;
						}
					}
				}
			} // Section params
			elseif (strpos($this->arFieldsMap[$k], 'CATEG_PARAMS_') === 0) {
				$arAUCategory[] = $this->modifValueByParams($this->arFieldsMap[$k], $k);
			} // SEO
			elseif (strpos($this->arFieldsMap[$k], 'SEO_') === 0) {
				if (isset($this->arFieldsNameRepl[$this->arFieldsMap[$k]])) {
					$arIBFields['IPROPERTY_TEMPLATES'][$this->arFieldsNameRepl[$this->arFieldsMap[$k]]] = $this->modifValueByParams($value, $k);
				}
			} // Price
			elseif (strpos($this->arFieldsMap[$k], 'PRICE_') === 0) {
				$priceId = explode(self::KEY_DELIMITER, $this->arFieldsMap[$k])[1];
				$arPrice = [
					"CATALOG_GROUP_ID" => $priceId,
					"PRICE" => $this->modifValueByParams($value, $k)
				];
				if ($this->arFieldsParams[$k]['price_vatincl']) {
					$arItemProduct['VAT_INCLUDED'] = $this->arFieldsParams[$k]['price_vatincl'];
				}
				$arItemPrices[] = $arPrice;
			}// Currency
			elseif (strpos($this->arFieldsMap[$k], 'CURRENCY_') === 0) {
				$priceId = explode(self::KEY_DELIMITER, $this->arFieldsMap[$k])[1];
				$arItemPricesCurrencies[ $priceId ] = $this->modifValueByParams($value, $k);
			} // Store
			elseif (strpos($this->arFieldsMap[$k], 'STORE_') === 0) {
				$arItemStores[] = [
					"STORE_ID" => explode(self::KEY_DELIMITER, $this->arFieldsMap[$k])[1],
					"AMOUNT"   => $this->modifValueByParams($value, $k)
				];
			} // Offers
			elseif (strpos($this->arFieldsMap[$k], 'OFFER_') === 0) {
				$find_field = false;
				if (strpos($this->arFieldsMap[$k], 'OFFER_PARENT_XML_ID') === 0) {
					$find_field = 'XML_ID';
				} elseif (strpos($this->arFieldsMap[$k], 'OFFER_PARENT_CODE') === 0) {
					$find_field = 'CODE';
				} elseif (strpos($this->arFieldsMap[$k], 'OFFER_PARENT_NAME') === 0) {
					$find_field = 'NAME';
				} elseif (strpos($this->arFieldsMap[$k], 'OFFER_PARENT_ID') === 0) {
					$find_field = 'ID';
				}
				if ($find_field && $this->arIBlockData['OFFERS']['PRODUCT_IBLOCK_ID']) {
					$arFilter = [
						'IBLOCK_ID' => $this->arIBlockData['OFFERS']['PRODUCT_IBLOCK_ID'],
						$find_field => $this->modifValueByParams($value, $k)
					];
					$res      = \CIBlockElement::GetList(['ID' => 'asc'], $arFilter, false, false, ['ID']);
					if ($obItem = $res->GetNextElement()) {
						$arIBItem      = $obItem->GetFields();
						$offer_item_id = $arIBItem['ID'];
					}
				}
				if ($offer_item_id) {
					// Find prop with sku link
					$sku_prop_id = false;
					foreach ($this->arIBlockData['PROPS'] as $prop_id => $arProp) {
						if ($arProp['USER_TYPE'] == 'SKU') {
							$sku_prop_id = $prop_id;
						}
					}
					if ($sku_prop_id) {
						$arIBPropValues[$sku_prop_id] = $offer_item_id;
					}
				}
			} // Product quantity and catalog fields
			elseif ($this->arFieldsMap[$k] == 'QUANTITY') {
				$arItemProduct['QUANTITY'] = (int)$this->modifValueByParams($value, $k);
			} else if (strpos($this->arFieldsMap[$k], 'CATALOG_') === 0) {
				$kx = str_replace('CATALOG_', '', $this->arFieldsMap[$k]);
				$arItemProduct[$kx] = $this->modifValueByParams($value, $k);
			}
			// Main fields
			else {
				//                    // Fields
				//                    switch ($this->arFieldsMap[$k]) {
				//                        case 'DATE_ACTIVE_FROM':
				//                        case 'DATE_ACTIVE_TO':
				//                            $value = $value;
				//                            break;
				//                    }
				if ($ib_item_id && $this->arFieldsParams[$k]['not_empty'] == 'Y' && $arIBItem[$this->arFieldsMap[$k]]) {
					continue;
				}
				if ($this->arFieldsMap[$k] == "PREVIEW_PICTURE" || $this->arFieldsMap[$k] == "DETAIL_PICTURE") {
					// Prepare modifications of path
					$value = $this->modifValueByParams($value, $k);
					// If there is enabled to process the image and do not use the information block settings
					if ($this->arFieldsParams[$k]["work_picture"]["checked"] == "Y" && $this->arProfile['ACTIONS_IB_IMG_MODIF'] != 'Y') {
						// If can't create a new picture and can't return image path
						if (!$path = $this->modifPictureByParams($this->arProfile["IMGS_SOURCE_PATH"] . $value, $k)) {
							// Get old picture
							$path = $this->arProfile["IMGS_SOURCE_PATH"] . $value;
						}
					} else {
						$path = $this->arProfile["IMGS_SOURCE_PATH"] . $value;
					}
					if ($path) {
						$extension = $this->arFieldsParams[$k]["work_picture"]["file_extension"] ?: false;
						if ($url_path = $this->getServerFile($path, $extension)) {
							$path         = $url_path;
							$arTmpFiles[] = $url_path;
						}
						$arIBFields[$this->arFieldsMap[$k]] = \CFile::MakeFileArray($path);
					}
				} elseif ($this->arFieldsMap[$k] == "PREVIEW_TEXT" || $this->arFieldsMap[$k] == "DETAIL_TEXT") {
					$arIBFields[$this->arFieldsMap[$k]] = $this->modifValueByParams($value, $k);
					// TODO: Add option
					$arIBFields[$this->arFieldsMap[$k] . '_TYPE'] = 'html';
				} else {
					$arIBFields[$this->arFieldsMap[$k]] = $this->modifValueByParams($value, $k);
				}
			}
		}

		// Modifications
		if (!$arIBFields['CODE'] && !$ib_item_id && $arIBFields['NAME']) {
			$arIBFields['CODE'] = $this->getElementCode($arIBFields['NAME']);
		}
		if (empty($arItemProduct['PURCHASING_CURRENCY'])) {
			$arItemProduct['PURCHASING_CURRENCY'] = Option::get('sale', 'default_currency', "RUB");
		}

		if (count($arAUCategory) > 0) {
			// Fill data of modified section
			foreach ($arAUCategory as $fld_name) {
				if (isset($this->arFieldsNameRepl[$fld_name]) && $this->arFieldsNameRepl[$fld_name]) {
					$value = $arInputRow[ array_search($fld_name, $this->arFieldsMap, false) ];
					if ($value) {
						if ($fld_name == "CATEG_PARAMS_IMAGE" || $fld_name == "CATEG_PARAMS_PICTURE") {
							if ($this->arFieldsParams[$k]["work_picture"]["checked"] == "Y" && $this->arProfile['ACTIONS_IB_IMG_MODIF'] != "Y") {
								if (!$path = $this->modifPictureByParams($value, $k)) {
									$path = $this->arProfile["IMGS_SOURCE_PATH"] . $value;
								}
							} else {
								$path = $this->arProfile["IMGS_SOURCE_PATH"] . $value;
							}
							$arSectFields[$this->arFieldsNameRepl[$fld_name]] = \CFile::MakeFileArray($path);
						} elseif (0 === strpos($this->arFieldsNameRepl[$fld_name], "UF_")) {
							$arSectFields[$this->arFieldsNameRepl[$fld_name]] = Helper::normalizeUfFieldValue(
								sprintf('IBLOCK_%d_SECTION', $this->getArProfile()['IBLOCK_ID']),
								$this->arFieldsNameRepl[$fld_name],
								$value
							);
						} else {
							$arSectFields[$this->arFieldsNameRepl[$fld_name]] = $value;
						}
					}
				}
			}
		}
		if (!is_array($arIBFields["IBLOCK_SECTION"])) {
			$arIBFields["IBLOCK_SECTION"] = [];
		}
		// end Modifications
		// endregion

		\Bitrix\Main\Diag\Debug::endTimeLabel('Fill data');

		$handlers = EventManager::getInstance()->findEventHandlers(\CAcritImport::MODULE_ID, "OnAfterFillData");
		foreach ($handlers as $handler) {
			\ExecuteModuleEventEx($handler, [$this, &$arSectFields, &$arIBFields, &$arItemProduct, &$arItemPrices, &$arItemPricesCurrencies, &$arItemStores]);
		}

		\Bitrix\Main\Diag\Debug::startTimeLabel('Add or update modified section');

		// region Add or update modified section
		if (!empty($arSectFields)) {
			// Find section by info for section create|update
			if ($arSectFields['ID']) {
				$section_id = $this->getIBSectionId('ID', $arSectFields['ID']);
			} elseif ($arSectFields['CODE']) {
				$section_id = $this->getIBSectionId('CODE', $arSectFields['CODE']);
			} elseif ($arSectFields['NAME']) {
				$section_id = $this->getIBSectionId('NAME', $arSectFields['NAME']);
			} elseif ($arSectFields['XML_ID']) {
				$section_id = $this->getIBSectionId('XML_ID', $arSectFields['XML_ID']);
			}
			$bs = new \CIBlockSection();
			if ($section_id) {
				$res = $bs->Update($section_id, $arSectFields, self::SECTION_RESORT, $bUpdateSearch);
			} elseif ($this->arProfile['ACTIONS_SECTIONS_CREATE'] == 'Y' && $arSectFields['NAME']) {
				if (!$arSectFields['CODE'] && $arSectFields['NAME']) {
					$arSectFields['CODE'] = $this->getSectionCode($arSectFields['NAME']);
				}
				$arSectFields["IBLOCK_ID"] = $arIBFields["IBLOCK_ID"];
				$section_id                = $bs->Add($arSectFields, self::SECTION_RESORT, $bUpdateSearch);
				if ($section_id) {
					$this->addIBSectionsCache($section_id);
				} else {
					$this->obLog->add(GetMessage("ACRIT_IMPORT_STROKA") . ' ' . $this->row_i . ' (' . $arIdentifier['key'] . ' = "' . $arIdentifier['value'] . '"): [section] ' . $bs->LAST_ERROR, Log::TYPE_ERROR);
				}
			}
			if ($section_id) {
				/** @noinspection UnsupportedStringOffsetOperationsInspection */
				$arIBFields["IBLOCK_SECTION"][] = $section_id;
			}
		}
		// endregion

		\Bitrix\Main\Diag\Debug::endTimeLabel('Add or update modified section');
		\Bitrix\Main\Diag\Debug::startTimeLabel('Adding to the IBlock');

		// region Adding to the IBlock
		$obIBItem = new \CIBlockElement();
		if ($arIBFields['IBLOCK_ID']) {
			// Option Iblock image modifications
			$ib_img_modif = $this->arProfile['ACTIONS_IB_IMG_MODIF'] == 'Y';
			// Update item
			if ($ib_item_id) {
				// Option of link by section
				if ($this->arProfile['ACTIONS_SECTIONS_LINK'] != 'all') {
					unset($arIBFields["IBLOCK_SECTION"]);
				}
				// Update
				unset($arIBFields["CODE"], $arIBFields["ID"]);
				$obIBItem->Update($ib_item_id, $arIBFields, false, $bUpdateSearch, $ib_img_modif);
			} // Add item
			else {
				if ($this->arProfile['ACTIONS_NEW_ELEMENTS'] == 'activate') {
					$arIBFields['ACTIVE'] = 'Y';
				} else {
					$arIBFields['ACTIVE'] = 'N';
				}
				// Option "Default section"
				if ((!$arIBFields["IBLOCK_SECTION"] || empty($arIBFields["IBLOCK_SECTION"])) && $this->arProfile['DEFAULT_SECTION_ID']) {
					/** @noinspection UnsupportedStringOffsetOperationsInspection */
					$arIBFields["IBLOCK_SECTION"][] = $this->arProfile['DEFAULT_SECTION_ID'];
				}
				// Option of link by section
				if ($this->arProfile['ACTIONS_SECTIONS_LINK'] == 'no') {
					unset($arIBFields["IBLOCK_SECTION"]);
				}
				// Add
				unset($arIBFields["ID"]);
				$ib_item_id = $obIBItem->Add($arIBFields, false, $bUpdateSearch, $ib_img_modif);
			}

			if ($ib_item_id) {
				// Update properties
				if (!empty($arIBPropValues)) {
					\CIBlockElement::SetPropertyValuesEx($ib_item_id, $arIBFields['IBLOCK_ID'], $arIBPropValues);
				}

				static $catalogCheck;
				if (Loader::includeModule('catalog')) {
					if (!isset($catalogCheck[$arIBFields['IBLOCK_ID']])) {
						$catalogCheck[$arIBFields['IBLOCK_ID']] = \Bitrix\Catalog\CatalogIblockTable::getById($arIBFields['IBLOCK_ID'])->fetch();
					}
				}
				if ($catalogCheck[$arIBFields['IBLOCK_ID']]) {
					//$useStoreControl = (string)Option::get('catalog', 'default_use_store_control') === 'Y';
					$updateCatalogArFields = static function (&$arFields) use ($arItemProduct, $offer_item_id) {
						// new from 1.12.8
						foreach (['WEIGHT' => 'float', 'WIDTH' => 'float', 'LENGTH' => 'float', 'HEIGHT' => 'float',
							         'PURCHASING_PRICE' => 'float', 'PURCHASING_CURRENCY' => 'string'] as $fld => $type) {
							if (!isset($arItemProduct[$fld])) {
								continue;
							}
							if ($type == 'float') {
								$arItemProduct[$fld] = (float)$arItemProduct[$fld];
							}
							$arFields[$fld] = $arItemProduct[$fld];
						}

						if ($arItemProduct['VAT_INCLUDED'] == 'Y') {
							$arFields['VAT_INCLUDED'] = 'Y';
						}
						if (isset($arItemProduct['QUANTITY'])) {
							$arFields['QUANTITY'] = (float)$arItemProduct['QUANTITY'];
						}
						$arFields['AVAILABLE'] = ProductTable::calculateAvailable($arFields);
					};

					// Create the product
					$res = ProductTable::getList([
						'filter' => [
							"ID" => $ib_item_id,
						],
						'select' => ['*']
					]);
					if (!$arProduct = $res->fetch()) {
						$arFields        = [
							'ID' => $ib_item_id,
							'QUANTITY_TRACE' => ProductTable::STATUS_DEFAULT,
							'CAN_BUY_ZERO' => ProductTable::STATUS_DEFAULT,
						];
						$updateCatalogArFields($arFields);
						if ($offer_item_id) {
							$arFields['TYPE'] = ProductTable::TYPE_OFFER;
						} else {
							$arFields['TYPE'] = ProductTable::TYPE_PRODUCT;
						}
						ProductTable::add($arFields);
					} else {
						if ($this->arProfile['ACTIONS_DEFAULT_CATALOG_FIELDS'] == 'Y') {
							$arFields = [
								'QUANTITY_TRACE' => ProductTable::STATUS_DEFAULT,
								'CAN_BUY_ZERO' => ProductTable::STATUS_DEFAULT,
							];
						} else {
							$arFields = [
								'QUANTITY_TRACE' => $arProduct['QUANTITY_TRACE'],
								'CAN_BUY_ZERO' => $arProduct['CAN_BUY_ZERO'],
							];
						}
						$updateCatalogArFields($arFields);
						if (!empty($arFields)) {
							ProductTable::update($arProduct['ID'], $arFields);
						}
					}
					unset($updateCatalogArFields);

					if (!empty($arItemProduct['CAT_BARCODE'])) {
						if (is_array($arItemProduct['CAT_BARCODE'])) {
							$arItemProduct['CAT_BARCODE'] = current($arItemProduct['CAT_BARCODE']);
						}
						self::setProductBarCode($ib_item_id, $arItemProduct['CAT_BARCODE']);
					}

					// Updating the prices
					if (is_array($arItemPrices)) {
						foreach ($arItemPrices as $arPrice) {
							$currency = $arItemPricesCurrencies[$arPrice["CATALOG_GROUP_ID"]] ?? Option::get('sale', 'default_currency', "RUB");
							$arFields = [
								"PRODUCT_ID" => $ib_item_id,
								"CATALOG_GROUP_ID" => $arPrice["CATALOG_GROUP_ID"],
								"PRICE" => $arPrice["PRICE"],
								"PRICE_SCALE" => $arPrice["PRICE"],
								"CURRENCY" => $currency
							];
							$res      = PriceTable::getList([
								'filter' => [
									"PRODUCT_ID" => $ib_item_id,
									"CATALOG_GROUP_ID" => $arPrice["CATALOG_GROUP_ID"],
								],
							]);
							if ($arItem = $res->fetch()) {
								$price_id = $arItem['ID'];
								PriceTable::update($price_id, $arFields);
							} else {
								PriceTable::add($arFields);
							}
						}
						// Option deactivate if null
						if ($this->arProfile['ACTIONS_PRICE_NULL'] == 'Y' && $arPrice["PRICE"] <= 0) {
							$price = false;
							$res   = PriceTable::getList(['filter' => ["PRODUCT_ID" => $ib_item_id]]);
							while ($arItem = $res->fetch()) {
								if ($arItem['PRICE']) {
									$price = true;
								}
							}
							if (!$price) {
								$arIBFields = [
									'ACTIVE' => 'N',
								];
								$obIBItem->Update($ib_item_id, $arIBFields, false, false);
							}
						}
					}
					// Updating the stores
					if (is_array($arItemStores)) {
						foreach ($arItemStores as $arIStore) {
							$arFields           = [
								"PRODUCT_ID" => $ib_item_id,
								"STORE_ID" => $arIStore["STORE_ID"],
							];
							$rsStore            = \CCatalogStoreProduct::GetList([], $arFields);
							$arFields["AMOUNT"] = $arIStore["AMOUNT"];
							if ($arStore = $rsStore->Fetch()) {
								\CCatalogStoreProduct::Update($arStore["ID"], $arFields);
							} else {
								\CCatalogStoreProduct::Add($arFields);
							}
						}
						// Option deactivate if null
						if ($this->arProfile['ACTIONS_AMOUNT_NULL'] == 'Y') {
							$amount  = 0;
							$rsStore = \CCatalogStoreProduct::GetList([], ["PRODUCT_ID" => $ib_item_id]);
							while ($arStore = $rsStore->Fetch()) {
								$amount += $arStore['AMOUNT'];
							}
							if ($amount == 0) {
								$arIBFields = [
									'ACTIVE' => 'N',
								];
								$obIBItem->Update($ib_item_id, $arIBFields, false, false);
							}
						}
					}
				}
			}

			//TODO: What should it do, if has errors?
			if ($obIBItem->LAST_ERROR) {
				$arImpRes['errors'][] = $obIBItem->LAST_ERROR;
				$this->obLog->add(GetMessage("ACRIT_IMPORT_STROKA") . ' ' . $this->row_i . ' (' . $arIdentifier['key'] . ' = "' . $arIdentifier['value'] . '"): ' . $obIBItem->LAST_ERROR, Log::TYPE_ERROR);
				$this->obLog->incImportStatParam('error_items');
			} else {
				$arImpRes['message'][] = $ib_item_id;
				$this->obLog->add('ID: ' . $ib_item_id, Log::TYPE_SUCCESS);
				$this->obLog->incImportStatParam('imported_items');
			}

			$handlers = EventManager::getInstance()->findEventHandlers(\CAcritImport::MODULE_ID, "OnAfterSaveIBData");
			foreach ($handlers as $handler) {
				\ExecuteModuleEventEx($handler, [$this, &$arInputRow, $arIBItem, $arImpRes]);
			}
		}
		// endregion

		\Bitrix\Main\Diag\Debug::endTimeLabel('Adding to the IBlock');

		// Clear tmp files
		if (!empty($arTmpFiles)) {
			foreach ($arTmpFiles as $file_name) {
				unlink($file_name);
			}
		}

		return $arImpRes;
	}

}
