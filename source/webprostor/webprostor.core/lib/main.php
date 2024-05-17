<?
namespace Webprostor\Core;

use Bitrix\Main\Config\Option,
	Bitrix\Main\Localization\Loc;

class Main
{
	const MODULE_ID = 'webprostor.core';
	
	public static function OnBeforeGroupDeleteHandler($ID)
	{
		$USERS_DENY_DELETION_GROUPS = Option::get(self::MODULE_ID, 'USERS_DENY_DELETION_GROUPS');
		$userGroupsDenied = unserialize($USERS_DENY_DELETION_GROUPS);
		if($userGroupsDenied !== false)
		{
			if(in_array($ID, $userGroupsDenied))
			{
				global $APPLICATION;
				$APPLICATION->throwException(Loc::getMessage('WEBPROSTOR_CORE_IBLOCK_DENY_DELETION_ELEMENTS'), ['#ID#' => $ID]);
				return false;
			}
		}
	}
}