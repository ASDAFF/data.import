<?php

namespace Acrit\Import;


class ImportIblock extends ImportXml
{
	protected $arKeys;
	protected $arFieldsDefault;

	function __construct($ID = 0)
	{
		parent::__construct($ID);

		$this->arKeys = [
			'ITEMS' => GetMessage("ACRIT_IMPORT_KOMMERCESKAAINFORMAC"),
			'ITEM' => GetMessage("ACRIT_IMPORT_KOMMERCESKAAINFORMAC1"),
			'ITEM_PROPS' => GetMessage("ACRIT_IMPORT_KOMMERCESKAAINFORMAC2"),
			'ITEM_PROP' => GetMessage("ACRIT_IMPORT_KOMMERCESKAAINFORMAC3"),
			'ITEM_PROP_S' => GetMessage("ACRIT_IMPORT_ZNACENIASVOYSTVA"),
			'ITEM_PROP_ID_S' => GetMessage("ACRIT_IMPORT_ID"),
			'ITEM_PROP_VALUE_S' => GetMessage("ACRIT_IMPORT_ZNACENIE"),
			'ITEM_PRICES' => GetMessage("ACRIT_IMPORT_KOMMERCESKAAINFORMAC4"),
			'ITEM_PRICE' => GetMessage("ACRIT_IMPORT_KOMMERCESKAAINFORMAC5"),
			'ITEM_PRICE_S' => GetMessage("ACRIT_IMPORT_CENA"),
			'ITEM_PRICE_ID_S' => GetMessage("ACRIT_IMPORT_IDTIPACENY"),
			'ITEM_PRICE_VALUE_S' => GetMessage("ACRIT_IMPORT_CENAZAEDINICU"),
			'STRUCT_PROPS' => GetMessage("ACRIT_IMPORT_KOMMERCESKAAINFORMAC6"),
			'STRUCT_PROP' => GetMessage("ACRIT_IMPORT_KOMMERCESKAAINFORMAC7"),
			'STRUCT_PROP_ID' => GetMessage("ACRIT_IMPORT_KOMMERCESKAAINFORMAC8"),
			'STRUCT_PROP_NAME' => GetMessage("ACRIT_IMPORT_KOMMERCESKAAINFORMAC9"),
			'STRUCT_PRICES' => GetMessage("ACRIT_IMPORT_KOMMERCESKAAINFORMAC10"),
			'STRUCT_PRICE' => GetMessage("ACRIT_IMPORT_KOMMERCESKAAINFORMAC11"),
			'STRUCT_PRICE_ID' => GetMessage("ACRIT_IMPORT_KOMMERCESKAAINFORMAC12"),
			'STRUCT_PRICE_NAME' => GetMessage("ACRIT_IMPORT_KOMMERCESKAAINFORMAC13"),
		];

		$this->arFieldsDefault = [
			$this->arKeys['ITEM'] . self::KEY_DELIMITER . GetMessage("ACRIT_IMPORT_ID"),
			$this->arKeys['ITEM'] . self::KEY_DELIMITER . GetMessage("ACRIT_IMPORT_NAIMENOVANIE"),
			$this->arKeys['ITEM'] . self::KEY_DELIMITER . GetMessage("ACRIT_IMPORT_BITRIKSTEGI"),
			$this->arKeys['ITEM'] . self::KEY_DELIMITER . GetMessage("ACRIT_IMPORT_GRUPPY_ID"),
			$this->arKeys['ITEM'] . self::KEY_DELIMITER . GetMessage("ACRIT_IMPORT_KARTINKA"),
		];
	}

	public function fieldsPreParams()
	{
		return [];
	}

	public function fields()
	{
		$arSourceFields = [];
		// For default fields of properties
		if ($this->arProfile['IBLOCK_ID']) {
			$this->fillIBlockData($this->arProfile['IBLOCK_ID']);
		}
		$arIBProps = [];
		if (!empty($this->arIBlockData['PROPS'])) {
			foreach ($this->arIBlockData['PROPS'] as $arItem) {
				if ($arItem['XML_ID']) {
					$arIBProps[$arItem['XML_ID']] = $arItem;
				}
			}
		}
		$arIBPrices = [];
		if (!empty($this->arIBlockData['PRICES'])) {
			foreach ($this->arIBlockData['PRICES'] as $arItem) {
				if ($arItem['XML_ID']) {
					$arIBPrices[$arItem['XML_ID']] = $arItem;
				}
			}
		}
		$arIBStores = [];
		if (!empty($this->arIBlockData['STORES'])) {
			foreach ($this->arIBlockData['STORES'] as $arItem) {
				if ($arItem['ID']) {
					$arIBStores[$arItem['ID']] = $arItem;
				}
			}
		}
		// Default fields
		if (!empty($this->arFieldsDefault)) {
			foreach ($this->arFieldsDefault as $k) {
				$ar = explode(self::KEY_DELIMITER, $k);
				//$name = $ar[count($ar) - 2];
				$key_base = '';
				for ($i = 0; $i < 3; $i++) {
					$key_base .= $ar[$i] . self::KEY_DELIMITER;
				}
				$name               = str_replace($key_base, '', $k);
				$name               = str_replace(self::KEY_DELIMITER, ' / ', $name);
				$example            = '';
				$arSourceFields[$k] = [
					'ID' => $k,
					'NAME' => $name,
					'EXAMPLE' => $example,
				];
			}
		}

		// List of main fields
		static $arRow;
		if (empty($arRow)) {
			if ($this->getArProfile()['SCAN_ALL_ITEMS_TO_FIND_FIELDS'] == 'Y') {
				$arRow = [];
				$step = 500;
				for ($nextItem = 0, $nextItemCnt = ceil($this->count() / $step); $nextItem < $nextItemCnt; $nextItem += $step) {
					$arRows = $this->get(self::STEP_BY_COUNT, $step, $nextItem, 4, GetMessage("ACRIT_IMPORT_PREDLOJENIA"));
					foreach ($arRows as $row) {
						$arRow += $row;
					}
				}
				ksort($arRow, SORT_NATURAL);
			} else {
				$arRows = $this->get(self::STEP_BY_COUNT, 1, 0, 4, GetMessage("ACRIT_IMPORT_PREDLOJENIA"));
				$arRow  = $arRows[0];
			}
		}
		unset($arRows, $row, $nextItem, $nextItemCnt);

		if (!empty($arRow)) {
			foreach ($arRow as $k => $value) {
				if (strpos($k, self::KEY_DELIMITER . $this->arKeys['ITEM_PROP_S']) !== false
					|| strpos($k, self::KEY_DELIMITER . $this->arKeys['ITEM_PRICE_S']) !== false
					|| $k == $this->arKeys['ITEM']
					|| $k == $this->arKeys['ITEM_PROPS']
					|| $k == $this->arKeys['ITEM_PRICES']) {
					continue;
				}
				$ar = explode(self::KEY_DELIMITER, $k);
				//$name = $ar[count($ar) - 2];
				$key_base = '';
				for ($i = 0; $i < 3; $i++) {
					$key_base .= $ar[$i] . self::KEY_DELIMITER;
				}
				$name    = str_replace($key_base, '', $k);
				$name    = str_replace(self::KEY_DELIMITER, ' / ', $name);
				$example = '';
				if (trim($value)) {
					$example = substr($value, 0, 10) . (strlen($value) > 10 ? '...' : '');
				}
				$arSourceFields[$k] = [
					'ID' => $k,
					'NAME' => $name,
					'EXAMPLE' => $example,
				];
			}
			// Default
			$arFieldsNameLinks = [
				GetMessage("ACRIT_IMPORT_NAIMENOVANIE") => 'NAME',
				GetMessage("ACRIT_IMPORT_BITRIKSTEGI") => 'TAGS',
			];
			foreach ($arFieldsNameLinks as $sf_name => $ib_name) {
				$k = $this->arKeys['ITEM'] . self::KEY_DELIMITER . $sf_name;
				if ($arSourceFields[$k]) {
					$arSourceFields[$k]['SAVED_FIELD'] = $ib_name;
				}
			}
		}
		// List of properties
		$arRows = $this->get(self::STEP_BY_COUNT, 0, 0, 4, GetMessage("ACRIT_IMPORT_SVOYSTVA"));
		foreach ($arRows as $arRow) {
			$sf_k = '';
			foreach ($arRow as $k => $value) {
				if (strpos($k, $this->arKeys['STRUCT_PROP_ID']) !== false) {
					$sf_k                  = $this->arKeys['ITEM_PROP'] . self::KEY_DELIMITER . $value;
					$sf_name               = $this->arKeys['ITEM_PROP_S'] . ' / ' . $value;
					$arSourceFields[$sf_k] = [
						'ID' => $sf_k,
						'NAME' => $sf_name,
						'EXAMPLE' => '',
					];
					// Default
					if ($arIBProps[$value]) {
						$arSourceFields[$sf_k]['SAVED_FIELD'] = 'PROP_' . $arIBProps[$value]['ID'];
					}
				} elseif (strpos($k, $this->arKeys['STRUCT_PROP_NAME']) !== false) {
					$arSourceFields[$sf_k]['NAME'] = $this->arKeys['ITEM_PROP_S'] . ' / ' . $value;
				}
			}
		}
		// List of prices
		$arRows = $this->get(self::STEP_BY_COUNT, 0, 0, 4, GetMessage("ACRIT_IMPORT_TIPYCEN"));
		foreach ($arRows as $arRow) {
			$sf_k = '';
			foreach ($arRow as $k => $value) {
				if (strpos($k, $this->arKeys['STRUCT_PRICE_ID']) !== false) {
					$sf_k                  = $this->arKeys['ITEM_PRICE'] . self::KEY_DELIMITER . $value;
					$sf_name               = $this->arKeys['ITEM_PRICE_S'] . ' / ' . $value;
					$arSourceFields[$sf_k] = [
						'ID' => $sf_k,
						'NAME' => $sf_name,
						'EXAMPLE' => '',
					];
					// Default
					if ($arIBPrices[$value]) {
						$arSourceFields[$sf_k]['SAVED_FIELD'] = 'PRICE_' . $arIBPrices[$value]['ID'];
					}
				} elseif (strpos($k, $this->arKeys['STRUCT_PRICE_NAME']) !== false) {
					$arSourceFields[$sf_k]['NAME'] = $this->arKeys['ITEM_PRICE_S'] . ' / ' . $value;
				}
			}
		}
		return $arSourceFields;
	}

	public function count()
	{
		$count = 0;
		$res   = $this->find(function ($i, $arRow, $arHierarchyCur, &$count) {
			$count++;
		}, $count, self::STEP_NO, 0, 0, 4, GetMessage("ACRIT_IMPORT_PREDLOJENIA"), [], 1);
		return $count;
	}

	public function get($type = self::STEP_NO, $limit = 0, $next_item = 0, $root_level = false, $root_node = false, $arRootPath = [], $level_limit = 0, $attr_mode = self::ATTR_MODE_REPLACE)
	{
		$arRows = [];
		$res    = $this->find(function ($i, $arRow, $arHierarchyCur, &$arRows) {
			$arRowNew = [];
			foreach ($arRow as $k => $value) {
				$k            = preg_replace('/_[0-9]+$/i', '', $k);
				$arRowNew[$k] = $value;
			}
			$arRows[] = $arRowNew;
		}, $arRows, $type, $limit, $next_item, $root_level, $root_node, $arRootPath, $level_limit);
		return $arRows;
	}

	public function import($type = self::STEP_NO, $limit = 0, $next_item = 0)
	{
		parent::onBeforeImport($type, $limit, $next_item);

		\CModule::IncludeModule('iblock');
		if ($this->arProfile['IBLOCK_ID']) {
			$this->fillIBlockData($this->arProfile['IBLOCK_ID']);
		}
		$this->fillFieldsMap();
		$next_item = $this->find(function ($i, $arRow, $arHierarchyCur, &$arRows) {
			$arRowNew     = [];
			$k_last_compl = '';
			//echo '<pre>'; print_r($this->arIBlockData['PROPS']); echo '</pre>';
			$arPValuesByXmlId = [];
			foreach ($this->arIBlockData['PROPS'] as $arProp) {
				if ($arProp['VALUES']) {
					foreach ($arProp['VALUES'] as $arValue) {
						$arPValuesByXmlId['PROP_' . $arProp['ID']][$arValue['XML_ID']] = $arValue['VALUE'];
					}
				}
			}
			foreach ($arRow as $k => $value) {
				$k = preg_replace('/_[0-9]+$/i', '', $k);
				// Properties
				if (strpos($k, $this->arKeys['ITEM_PROP']) !== false) {
					if ($k == $this->arKeys['ITEM_PROP'] . self::KEY_DELIMITER . $this->arKeys['ITEM_PROP_ID_S']) {
						$k_last_compl = $this->arKeys['ITEM_PROP'] . '_' . $value;
					} elseif ($k == $this->arKeys['ITEM_PROP'] . self::KEY_DELIMITER . $this->arKeys['ITEM_PROP_VALUE_S']) {
						// If value has in props section of XML
						if ($arPValuesByXmlId[$this->arFieldsMap[$k_last_compl]]) {
							if ($arRowNew[$k_last_compl]) {
								if (!is_array($arRowNew[$k_last_compl])) {
									$arRowNew[$k_last_compl] = [$arRowNew[$k_last_compl]];
								}
								$arRowNew[$k_last_compl][] = $arPValuesByXmlId[$this->arFieldsMap[$k_last_compl]][$value];
							} else {
								$arRowNew[$k_last_compl] = $arPValuesByXmlId[$this->arFieldsMap[$k_last_compl]][$value];
							}
						} // If it's a simple value
						else {
							if ($arRowNew[$k_last_compl]) {
								if (!is_array($arRowNew[$k_last_compl])) {
									$arRowNew[$k_last_compl] = [$arRowNew[$k_last_compl]];
								}
								$arRowNew[$k_last_compl][] = $value;
							} else {
								$arRowNew[$k_last_compl] = $value;
							}
						}
					}
				} // Prices
				elseif (strpos($k, $this->arKeys['ITEM_PRICE']) !== false) {
					if (strpos($k, $this->arKeys['ITEM_PRICE_S'] . self::KEY_DELIMITER . $this->arKeys['ITEM_PRICE_ID_S']) !== false) {
						$k_last_compl .= '_' . $value;
					} elseif (strpos($k, $this->arKeys['ITEM_PRICE_S'] . self::KEY_DELIMITER . $this->arKeys['ITEM_PRICE_VALUE_S']) !== false) {
						$arRowNew[$k_last_compl] = $value;
					} else {
						$k_last_compl = preg_replace('/_[0-9]+$/i', '', $k);
					}
				} else {
					$arRowNew[$k] = $value;
				}
			}
			// Import data
			$this->saveIBData($arRowNew, $i + 1);
		}, $arRows, $type, $limit, $next_item, 4, GetMessage("ACRIT_IMPORT_PREDLOJENIA"));
		return $next_item;
	}
}