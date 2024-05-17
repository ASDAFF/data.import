<?
namespace Webprostor\Core;

use Bitrix\Main\Config\Option,
	Bitrix\Main\Localization\Loc;

class Iblock
{
	const MODULE_ID = 'webprostor.core';
	
	public static function OnBeforeIBlockSectionDeleteHandler($ID)
	{
		$IBLOCK_DENY_DELETION_SECTIONS = Option::get(self::MODULE_ID, 'IBLOCK_DENY_DELETION_SECTIONS');
		$iblockDenied = unserialize($IBLOCK_DENY_DELETION_SECTIONS);
		if($iblockDenied !== false)
		{
			$sectionInfo = \CIBlockSection::GetByID($ID)->Fetch();
			if(in_array($sectionInfo['IBLOCK_ID'], $iblockDenied))
			{
				global $APPLICATION;
				$APPLICATION->throwException(Loc::getMessage('WEBPROSTOR_CORE_IBLOCK_DENY_DELETION_SECTIONS'));
				return false;
			}
		}
	}
	
	public static function OnBeforeIBlockElementDeleteHandler($ID)
	{
		$IBLOCK_DENY_DELETION_ELEMENTS = Option::get(self::MODULE_ID, 'IBLOCK_DENY_DELETION_ELEMENTS');
		$iblockDenied = unserialize($IBLOCK_DENY_DELETION_ELEMENTS);
		if($iblockDenied !== false)
		{
			$IBLOCK_ID = \CIBlockElement::GetIBlockByID($ID);
			if(in_array($IBLOCK_ID, $iblockDenied))
			{
				global $APPLICATION;
				$APPLICATION->throwException(Loc::getMessage('WEBPROSTOR_CORE_IBLOCK_DENY_DELETION_ELEMENTS'));
				return false;
			}
		}
	}
}