<?php

namespace Acrit\Import;

use Bitrix\Main\Config\Option;

class Helper
{
	/**
	 *    Check updates
	 */

	public static function checkModuleUpdates($strModuleID, &$intDateTo)
	{
		$arAvailableUpdates = [];
		include_once($_SERVER["DOCUMENT_ROOT"] . '/bitrix/modules/main/classes/general/update_client_partner.php');
		if (class_exists('\CUpdateClientPartner')) {
			$arUpdateList = \CUpdateClientPartner::GetUpdatesList($errorMessage, LANGUAGE_ID, 'Y', [],
				['fullmoduleinfo' => 'Y']);
			if (is_array($arUpdateList) && is_array($arUpdateList['MODULE'])) {
				foreach ($arUpdateList['MODULE'] as $arModuleData) {
					if ($arModuleData['@']['ID'] == $strModuleID) {
						if (preg_match('#^(\d{1,2})\.(\d{1,2})\.(\d{4})$#', $arModuleData['@']['DATE_TO'], $arMatch)) {
							$intDateTo = mktime(23, 59, 59, $arMatch[2], $arMatch[1], $arMatch[3]);
						}
						if (is_array($arModuleData['#']) && is_array($arModuleData['#']['VERSION'])) {
							foreach ($arModuleData['#']['VERSION'] as $arVersion) {
								$arAvailableUpdates[$arVersion['@']['ID']] = $arVersion['#']['DESCRIPTION'][0]['#'];
							}
						}
					}
				}
			}
		}
		return $arAvailableUpdates;
	}

	/**
	 *    Show success
	 */

	public static function showSuccess($strMessage = null, $strDetails = null)
	{
		ob_start();
		\CAdminMessage::ShowMessage([
			'MESSAGE' => $strMessage,
			'DETAILS' => $strDetails,
			'HTML' => true,
			'TYPE' => 'OK',
		]);
		return ob_get_clean();
	}

	/**
	 *    Show note
	 */
	public static function showNote($strNote, $bCompact = false, $bCenter = false, $bReturn = false)
	{
		if ($bReturn) {
			ob_start();
		}
		$arClass = [];
		if ($bCompact) {
			$arClass[] = 'acrit-exp-note-compact';
		}
		if ($bCenter) {
			$arClass[] = 'acrit-exp-note-center';
		}
		print '<div class="' . implode(' ', $arClass) . '">';
		print BeginNote();
		print $strNote;
		print EndNote();
		print '</div>';
		if ($bReturn) {
			return ob_get_clean();
		}
	}

	public static function GetHttpHost(): string
	{
		$arHttpHost = explode(":", $_SERVER["HTTP_HOST"]);
		return $arHttpHost[0];
	}

	/**
	 * @param array $filter = []
	 * @param array $getList = []
	 * @return array{array{'ID':int, 'FIELD_NAME':string, 'ENTITY_ID':string, 'USER_TYPE_ID':string, 'MULTIPLE':string, 'MAIN_USER_FIELD_TITLE_EDIT_FORM_LABEL':string}}
	 */
	public static function getUserFieldList(array $filter = [], array $getList = []): array
	{
		$list = [];
		$userFields = \Bitrix\Main\UserFieldTable::getList(array_merge([
			'select'   =>   ['ID', 'FIELD_NAME', 'ENTITY_ID', 'USER_TYPE_ID', 'MULTIPLE', 'SETTINGS', 'TITLE'],
			'filter'   =>   array_merge([
				'=MAIN_USER_FIELD_TITLE_LANGUAGE_ID'   =>   LANGUAGE_ID
			], $filter),
			'runtime'   =>   [
				'TITLE'         =>   [
					'data_type'      =>   \Bitrix\Main\UserFieldLangTable::getEntity(),
					'reference'      =>   [
						'=this.ID'      =>   'ref.USER_FIELD_ID',
					],
				],
			],
		], $getList));

		while ($arUserField = $userFields->fetch()) {
			$list[] = $arUserField;
		}
		return $list;
	}

	/**
	 * @param string $entityId
	 * @param string $fieldName
	 * @param string|array|mixed $value
	 * @return float|int|float[]|int[]|array
	 */
	public static function normalizeUfFieldValue(string $entityId, string $fieldName, $value)
	{
		// select structure of uf-props
		static $entityIdPropList = [];
		if (empty($entityIdPropList)) {
			$list = self::getUserFieldList([
				'=ENTITY_ID' => $entityId
			]);
			foreach ($list as $prop) {
				if ($prop['USER_TYPE_ID'] == 'enumeration') {
					$prop['VALUES'] = [];
					$rs = \CUserFieldEnum::GetList(['VALUE' => 'ASC'], ['USER_FIELD_ID' => $prop['ID']]);
					while ($enum = $rs->Fetch()) {
						$prop['VALUES'][ $enum['ID'] ] = $enum['VALUE'];
					}
				}
				$entityIdPropList[ $prop['ENTITY_ID'] ][ $prop['FIELD_NAME'] ] = $prop;
			}
		}

		$propSettings = $entityIdPropList[$entityId][$fieldName] ?? null;
		if ($propSettings === null) {
			return $value;
		}

		// modification raw-value relay to prop type
		$modificator = null;
		switch ($propSettings['USER_TYPE_ID']) {
			case 'integer':
				$modificator = static function ($val) {
					return (int)str_replace([' '], [''], (string)$val);
				};
				break;
			case 'double':
				$modificator = static function ($val) {
					return (float)str_replace([' ', ','], ['', '.'], (string)$val);
				};
				break;
			case 'file':
				$modificator = static function ($val) {
					return \CFile::MakeFileArray($val);
				};
				break;
			case 'enumeration':
				$modificator = static function ($val) use ($propSettings, &$entityIdPropList) {
					if (!in_array($val, $propSettings['VALUES'], false)) {
						$obEnum = new \CUserFieldEnum();
						$obEnum->SetEnumValues($propSettings['ID'], [
							'n0' => [
								'VALUE' => $val,
								'SORT'  => count($propSettings['VALUES']) * 10 + 10
							]
						]);

						// reselect enum items
						$propSettings['VALUES'] = [];
						$rs = \CUserFieldEnum::GetList(['VALUE' => 'ASC'], ['USER_FIELD_ID' => $propSettings['ID']]);
						while ($enum = $rs->Fetch()) {
							$propSettings['VALUES'][ $enum['ID'] ] = $enum['VALUE'];
						}

						$etalonProp =& $entityIdPropList[ $propSettings['ENTITY_ID'] ][ $propSettings['FIELD_NAME'] ];
						$etalonProp['VALUES'] = $propSettings['VALUES'];
					}
					return array_search($val, $propSettings['VALUES'], false);
				};
				break;
			default:            // @todo in this case has more field-types
				break;
		}

		if (is_callable($modificator)) {
			if (is_array($value)) {
				foreach ($value as &$v) {
					$v = $modificator($v);
				}
				unset($v);
			} else {
				$value = $modificator($value);
			}
		}

		return $value;
	}
}
