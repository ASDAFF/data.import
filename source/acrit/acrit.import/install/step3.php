<?php
if( !check_bitrix_sessid() ) return;
/**
 * @global $APPLICATION \CMain
 * @global $USER \CUser
 * @global $DB \CDatabase
 * @global $USER_FIELD_MANAGER \CUserTypeManager
 * @global $BX_MENU_CUSTOM \CMenuCustom
 * @global $stackCacheManager \CStackCacheManager
 */

CAdminMessage::ShowMessage( ["MESSAGE" => GetMessage( "MOD_INST_OK" ), "TYPE" => "OK"]);
?>

<?
include $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/acrit.core/install/module_installed.php';
/** @var string $strExportInstalledMessage  */
print $strExportInstalledMessage;
?>

<form action="<?=$APPLICATION->GetCurPage()?>" method="get">
	<p>
		<input type="hidden" name="lang" value="<?=LANG?>" />
		<input type="submit" value="<?=GetMessage( "MOD_BACK" )?>" />
	</p>
</form>
