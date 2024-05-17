<?php

namespace Acrit\Import;

use Bitrix\Main;
use Bitrix\Iblock\PropertyTable;

final class IblockProp
{
	const TYPE_STRING = 'string';
	protected $iblock_id, $profile_id, $prop_name, $params;

	function __construct($intIblockId, $intProfileId, $strName, $arParams = [])
	{
		$this->iblock_id  = $intIblockId;
		$this->profile_id = $intProfileId;
		$this->prop_name  = $strName;
		$params_def       = [
			'type' => self::TYPE_STRING,
			'multiple' => false,
		];
		$this->params     = array_merge($params_def, $arParams);
	}

	/**
	 * @return mixed
	 */
	public function getIblockId()
	{
		return $this->iblock_id;
	}

	/**
	 * @return mixed
	 */
	public function getProfileId()
	{
		return $this->profile_id;
	}

	/**
	 * @return mixed
	 */
	public function getPropName()
	{
		return $this->prop_name;
	}

	public function getCode()
	{
		$name   = $this->getPropName();
		$params = [
			'replace_space' => '_',
			'replace_other' => '_'
		];
		$code   = \CUtil::translit($name, "ru", $params);
		$code   = strtoupper($code) . '_PRFL_' . $this->getProfileId();
		return $code;
	}

	public function find($code)
	{
		$result = false;
		$res    = PropertyTable::getList([
			'select' => ['ID'],
			'filter' => [
				'IBLOCK_ID' => $this->getIblockId(),
				'=CODE' => $code,
			],
			"cache" => ["ttl" => 3600, "cache_joins" => true]
		]);
		if ($row = $res->fetch()) {
			$result = $row['ID'];
		}
		return $result;
	}

	public function create()
	{
		$prop_code = $this->getCode();
		if ($prop_id = $this->find($prop_code)) {
			$result = $prop_id;
		} else {
			$fields = [
				'NAME' => $this->getPropName(),
				'CODE' => $prop_code,
				'IBLOCK_ID' => $this->getIblockId(),
				'ACTIVE' => 'Y',
			];
			if ($this->params['multiple']) {
				$fields['MULTIPLE'] = 'Y';
			}
			if ($this->params['type'] == self::TYPE_STRING) {
				$fields['PROPERTY_TYPE'] = 'S';
			}
			$iblockproperty = new \CIBlockProperty;
			$result         = $iblockproperty->Add($fields);
			PropertyTable::cleanCache();
		}
		return $result;
	}
}
