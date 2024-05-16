<?php
namespace Acrit\Import;

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Loader;

trait ImportRepository
{
	/**
	 * @param ?string $strHlTableName
	 * @return ?\Bitrix\Main\ORM\Data\DataManager
	 */
	public function getHlBlockDataManager(?string $strHlTableName)
	{
		if ($strHlTableName != '' && Loader::includeModule('highloadblock')) {
			$arHLBlock = HighloadBlockTable::getList(['filter' => ['TABLE_NAME' => $strHlTableName]])->fetch();
			if ($arHLBlock) {
				$obEntity = HighloadBlockTable::compileEntity($arHLBlock);
				return $obEntity->getDataClass();
			}
		}
		return null;
	}

	public function getPropertyItems_S_directory($arProp): array
	{
		$arResult       = [];

		$strHlTableName = (string)$arProp['USER_TYPE_SETTINGS']['TABLE_NAME'];
		$strEntityDataClass = $this->getHlBlockDataManager($strHlTableName);

		if ($strEntityDataClass !== null) {
			$resData = $strEntityDataClass::getList([
				'filter' => [],
				'select' => ['ID', 'UF_NAME', 'UF_XML_ID'],
				'order' => ['ID' => 'ASC'],
			]);
			while ($arItem = $resData->fetch()) {
				$arResult[$arItem['UF_XML_ID']] = $arItem['UF_NAME'];
			}
		}
		unset($strHlTableName, $strEntityDataClass, $resData, $arItem);
		return $arResult;
	}

	/**
	 * @param array{'LINK_IBLOCK_ID':int} $arProp
	 * @return array
	 */
	public function getPropertyItems_E(array $arProp): array
	{
		$arResult       = [];
		$rs = \Bitrix\Iblock\ElementTable::getList([
			'order'     => ['ID' => 'ASC'],
			'filter'    => ['IBLOCK_ID' => (int)$arProp['LINK_IBLOCK_ID']],
			'select'    => ['ID', 'NAME']
		]);
		while ($ar = $rs->fetch()) {
			$arResult[ $ar['ID'] ] = $ar['NAME'];
		}
		return $arResult;
	}

	/**
	 * Fill the iblock info
	 */
	protected function fillIBlockData($iblock_id): void
	{
		if (!\CModule::IncludeModule("iblock")) {
			return;
		}
		if (!$this->arIBlockData) {
			$this->arIBlockData = [];
		}
		if (!$this->arIBlockData['PROPS']) {
			$this->arIBlockData['PROPS'] = [];
			$obProps                     = \CIBlockProperty::GetList(["sort" => "asc", "name" => "asc"], ["IBLOCK_ID" => $iblock_id]);
			while ($arProp = $obProps->GetNext()) {
				$bComplexString = $this->isPropertyType_S_Complex($arProp);

				// Get enum values
				if ($arProp['PROPERTY_TYPE'] == 'L') {
					$arProp['VALUES']       = [];
					$arProp['VALUES_BY_ID'] = [];
					$obValues               = \CIBlockPropertyEnum::GetList(["DEF" => "DESC", "SORT" => "ASC"], ["IBLOCK_ID" => $iblock_id, "PROPERTY_ID" => $arProp['ID']]);
					while ($arValue = $obValues->GetNext()) {
						$arProp['VALUES'][$arValue['ID']]       = $arValue;
						$arProp['VALUES_BY_ID'][$arValue['ID']] = trim($arValue['VALUE']);
					}
				} elseif ($arProp['PROPERTY_TYPE'] == 'S' && $bComplexString) {
					// Get reference values
					if ($arProp['USER_TYPE'] == 'directory' && $arProp['USER_TYPE_SETTINGS']['TABLE_NAME'] != '') {
						$arProp['VALUES_BY_ID'] = $this->getPropertyItems_S_directory($arProp);
					}
				} elseif ($arProp['PROPERTY_TYPE'] == 'E') {
					// Get reference values
					if ((int)$arProp['LINK_IBLOCK_ID'] > 0) {
						$arProp['VALUES_BY_ID'] = $this->getPropertyItems_E($arProp);
					}
				}
				$this->arIBlockData['PROPS'][$arProp['ID']] = $arProp;
			}
		}
		if (\CModule::IncludeModule("catalog") && !$this->arIBlockData['PRICES']) {
			$this->arIBlockData['PRICES'] = [];
			$res        = \Bitrix\Catalog\GroupTable::getList([
				'filter' => [],
				'order' => ['ID' => 'asc'],
			]);
			while ($arItem = $res->fetch()) {
				$this->arIBlockData['PRICES'][$arItem['ID']] = $arItem;
			}
		}
		if (\CModule::IncludeModule("catalog") && !$this->arIBlockData['STORES']) {
			$this->arIBlockData['STORES'] = [];
			$res        = \Bitrix\Catalog\StoreTable::getList([
				'filter' => [],
				'order' => ['ID' => 'asc'],
			]);
			while ($arItem = $res->fetch()) {
				$this->arIBlockData['STORES'][$arItem['ID']] = $arItem;
			}
		}
		if (\CModule::IncludeModule("catalog") && !$this->arIBlockData['OFFERS']) {
			$arOffers                                          = \CCatalogSKU::GetInfoByOfferIBlock($iblock_id);
			$this->arIBlockData['OFFERS']['PRODUCT_IBLOCK_ID'] = $arOffers ? $arOffers["PRODUCT_IBLOCK_ID"] : false;
			$this->arIBlockData['OFFERS']['SKU_IBLOCK_ID']     = $arOffers ? $arOffers["IBLOCK_ID"] : false;
		}
	}

	/**
	 * Get section from cache field arIBlockSections
	 *
	 * @param $field
	 * @param $value
	 * @param $parent_id
	 *
	 * @return false|mixed
	 */
	protected function getIBSectionId($field, $value, $parent_id = 0)
	{
		$section_id = false;
		if ($value) {
			// Get sections
			if (!$this->arIBlockSections) {
				$arSelect = ['ID', 'XML_ID', 'CODE', 'NAME', 'IBLOCK_SECTION_ID'];
				$arFilter = ['IBLOCK_ID' => $this->arProfile['IBLOCK_ID']];
				if ($parent_id) {
					$arFilter['IBLOCK_SECTION_ID'] = $parent_id;
				}
				$arOrder = ['ID' => 'asc'];
				$res     = \Bitrix\Iblock\SectionTable::getList([
					'select' => $arSelect,
					'filter' => $arFilter,
					'order' => $arOrder,
				]);
				while ($arItem = $res->fetch()) {
					$this->arIBlockSections[$arItem['ID']] = $arItem;
				}
			}
			// Find ID
			foreach ($this->arIBlockSections as $arItem) {
				if ($arItem[$field] == $value && (!$parent_id || ($parent_id && $arItem['IBLOCK_SECTION_ID'] == $parent_id))) {
					$section_id = $arItem['ID'];
					break;
				}
			}
		}
		return $section_id;
	}

	/**
	 * Fill arIBlockSections class field
	 * @param $section_id
	 *
	 * @return bool
	 */
	protected function addIBSectionsCache($section_id): bool
	{
		$result = false;
		if ($section_id) {
			$res = \Bitrix\Iblock\SectionTable::getList([
				'select' => ['ID', 'XML_ID', 'CODE', 'NAME', 'IBLOCK_SECTION_ID'],
				'filter' => ['ID' => $section_id],
				'order' => ['ID' => 'asc'],
			]);
			if ($arItem = $res->fetch()) {
				$this->arIBlockSections[$arItem['ID']] = $arItem;
				$result                                = true;
			}
		}
		return $result;
	}


	/**
	 * Create sections list from multiple field and add to the iblock element
	 */
	protected function createSectionsList($arSectsName, $iblock_id, &$arElSList)
	{
		$bUpdateSearch = \CAcritImport::searchModuleIndexingEnabled();

		$bs = new \CIBlockSection;
		foreach ($arSectsName as $name) {
			$arSFields['NAME']      = $name;
			$arSFields['CODE']      = $this->getSectionCode($name);
			$arSFields["IBLOCK_ID"] = $iblock_id;
			$section_id             = $bs->Add($arSFields, self::SECTION_RESORT, $bUpdateSearch);
			if ($section_id) {
				$arElSList[] = $section_id;
				$this->addIBSectionsCache($section_id);
			} else {
				$this->obLog->add(GetMessage("ACRIT_IMPORT_STROKA") . ' ' . $this->row_i . ': [section] ' . $bs->LAST_ERROR, Log::TYPE_ERROR);
			}
		}
	}

	/**
	 * Create sections hierarchy from multiple field and add leaf section to the iblock element
	 */
	protected function createSectionsHierarchy($arSectsName, $iblock_id, $parent_section, &$arElSList)
	{
		$bUpdateSearch = \CAcritImport::searchModuleIndexingEnabled();

		$last_section_id = false;
		$bs              = new \CIBlockSection;
		$section_id      = $parent_section;
		$i               = 0;
		foreach ($arSectsName as $name) {
			$arSFields['NAME']              = $name;
			$arSFields['CODE']              = $this->getSectionCode($name);
			$arSFields["IBLOCK_ID"]         = $iblock_id;
			$arSFields["IBLOCK_SECTION_ID"] = $section_id;
			$section_id                     = $bs->Add($arSFields, self::SECTION_RESORT, $bUpdateSearch);
			if ($section_id) {
				$this->addIBSectionsCache($section_id);
			} else {
				$this->obLog->add(GetMessage("ACRIT_IMPORT_STROKA") . ' ' . $this->row_i . ': [section] ' . $bs->LAST_ERROR, Log::TYPE_ERROR);
				break;
			}
			$i++;
		}
		if ($i >= count($arSectsName)) {
			$last_section_id = $section_id;
		}
		if ($last_section_id) {
			$arElSList[] = $last_section_id;
		}
	}

	public static function setProductBarCode(int $productId, string $barcode = '')
	{
		if ($productId <= 0) {
			return false;
		}

		global $USER;
		$dbBarCode = \CCatalogStoreBarCode::getList([], ["PRODUCT_ID" => $productId]);
		$arBarCode = $dbBarCode->GetNext();
		if ($arBarCode === false) {
			$dbBarCode = \CCatalogStoreBarCode::Add(["PRODUCT_ID" => $productId, "BARCODE" => $barcode, "CREATED_BY" => $USER->GetID()]);
		} elseif ($arBarCode["BARCODE"] != $barcode) {
			$dbBarCode = \CCatalogStoreBarCode::Update($arBarCode["ID"], ["BARCODE" => $barcode, "MODIFIED_BY" => $USER->GetID()]);
		}
		return $dbBarCode;
	}

	/**
	 * Добавление недостающих значений свойств перед завязкой на них
	 * @param string $prop_k
	 * @param mixed $value
	 * @return void
	 */
	private function addNewRelayedItemsToIblockFromValues(string $prop_k, $value): void
	{
		$arPropData = $this->arIBlockData['PROPS'][$prop_k];

		if ($arPropData['PROPERTY_TYPE'] == 'E' || $arPropData['PROPERTY_TYPE'] == 'L'
			|| ($arPropData['PROPERTY_TYPE'] == 'S' && $this->isPropertyType_S_Complex($arPropData))
		) {
			if (!is_array($arPropData['VALUES_BY_ID'])) {
				$arPropData['VALUES_BY_ID'] = [];
			}
			$arIDs = array_flip((array)$arPropData['VALUES_BY_ID']);
			if (!is_array($value)) {
				$value = [$value];
			}
			foreach ($value as $value_val) {
				if (isset($arIDs[$value_val])) {
					continue;
				}

				$nId = null;
				if ($arPropData['PROPERTY_TYPE'] == 'E') {
					$el  = new \CIBlockElement();
					$nId = (int)$el->Add([
						'ACTIVE'    => 'Y',
						'IBLOCK_ID' => $arPropData['LINK_IBLOCK_ID'],
						'NAME'      => $value_val,
						'CODE'      => $this->getElementCode($value_val)
					], false, \CAcritImport::searchModuleIndexingEnabled());
				} else if ($arPropData['PROPERTY_TYPE'] == 'L') {
					$nId = (int)\CIBlockPropertyEnum::Add([
						'PROPERTY_ID' => $arPropData['ID'],
						'VALUE'       => $value_val,
						'SORT'        => 500
					]);
				} else if ($arPropData['PROPERTY_TYPE'] == 'S' && $arPropData['USER_TYPE'] == 'directory') {
					$strHlTableName = (string)$arPropData['USER_TYPE_SETTINGS']['TABLE_NAME'];
					$strEntityDataClass = $this->getHlBlockDataManager($strHlTableName);
					if ($strEntityDataClass !== null) {
						$xmlId = md5(uniqid("", true));
						$result = $strEntityDataClass::add([
							'UF_XML_ID'     => $xmlId,
							'UF_NAME'       => $value_val
						]);
						if ($result->isSuccess()) {
							$nId = $xmlId;
						}
					}
				}

				if ($nId !== null) {
					$arIDs[$value_val] = $nId;
					$arPropData['VALUES_BY_ID'][$nId] = $value_val;
				} else {
					if ($arPropData['PROPERTY_TYPE'] == 'E') {
						$this->obLog->add(GetMessage("ACRIT_IMPORT_STROKA") . ' ' . $this->row_i
							. ' (' . GetMessage("ACRIT_IMPORT_DIRECTORY_E_ADD_ERROR", ['#LINK_IBLOCK_ID#' => $arPropData['LINK_IBLOCK_ID']]) . ') : '
							. $el->LAST_ERROR, Log::TYPE_MESSAGE);
					} else if ($arPropData['PROPERTY_TYPE'] == 'L') {
						$this->obLog->add(GetMessage("ACRIT_IMPORT_STROKA") . ' ' . $this->row_i
							. ' (' . GetMessage("ACRIT_IMPORT_DIRECTORY_L_ADD_ERROR", ['#LINK_IBLOCK_ID#' => $arPropData['IBLOCK_ID']]) . ')', Log::TYPE_MESSAGE);
					} else if ($arPropData['PROPERTY_TYPE'] == 'S' && $arPropData['USER_TYPE'] == 'directory') {
						$this->obLog->add(GetMessage("ACRIT_IMPORT_STROKA") . ' ' . $this->row_i
							. ' (' . GetMessage("ACRIT_IMPORT_DIRECTORY_S_directory_ADD_ERROR", ['#LINK_IBLOCK_ID#' => $strHlTableName]) . ') : '
							. implode(',', $result->getErrors()), Log::TYPE_MESSAGE);
					}
				}
			}
			$this->arIBlockData['PROPS'][$prop_k] = $arPropData;
		}
	}

	private function isPropertyType_S_Complex($arPropData): bool
	{
		/** @noinspection InArrayMissUseInspection */
		$bComplexString = in_array($arPropData['USER_TYPE'], [
			'directory',        // PropertyTable::USER_TYPE_DIRECTORY from main 23
			// TODO
			/*PropertyTable::USER_TYPE_XML_ID,
			PropertyTable::USER_TYPE_ELEMENT_AUTOCOMPLETE,
			PropertyTable::USER_TYPE_ELEMENT_LIST,
			PropertyTable::USER_TYPE_SKU,
			PropertyTable::USER_TYPE_DIRECTORY*/
		], false);
		return $bComplexString;
	}
}