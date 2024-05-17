<?php
/**
 * @global $APPLICATION \CMain
 * @global $USER \CUser
 * @global $DB \CDatabase
 * @global $USER_FIELD_MANAGER \CUserTypeManager
 * @global $BX_MENU_CUSTOM \CMenuCustom
 * @global $stackCacheManager \CStackCacheManager
 */

IncludeModuleLangFile(__FILE__);

if ($APPLICATION->GetGroupRight("acrit.import") != "D") {
	$aMenu = [
		"parent_menu" => "global_menu_acrit",
		"section" => GetMessage("ACRIT_IMPORT_MENU_SECTION"),
		"sort" => 100,
		"text" => GetMessage("ACRIT_IMPORT_MENU_SECTION"),
		"title" => GetMessage("ACRIT_IMPORT_MENU_TEXT"),
		"url" => "",
		"icon" => "extension_menu_icon",
		"page_icon" => "",
		"items_id" => "menu_acrit.import",
		"items" => [
			[
				"text" => GetMessage("ACRIT_IMPORT_MENU_TITLE"),
				"url" => "acrit.import_list.php?lang=" . LANGUAGE_ID,
				"more_url" => [
					"acrit_import_list.php",
					"acrit_import_edit.php",
					"acrit.import_edit.php"
				],
				"title" => GetMessage("ACRIT_IMPORT_MENU_TITLE"),
			],
			[
				"text" => GetMessage("ACRIT_IMPORT_MENU_SETTINGS"),
				"url" => "settings.php?lang=ru&mid=acrit.import&mid_menu=1",
				"more_url" => ["settings.php?lang=ru&mid=acrit.import&mid_menu=1"],
				"title" => GetMessage("ACRIT_IMPORT_MENU_SETTINGS")
			],
			[
				"text" => GetMessage("ACRIT_IMPORT_MENU_SUPPORT"),
				"url" => "acrit_import_new_support.php?lang=" . LANGUAGE_ID,
				"more_url" => ["acrit_import_support.php", "acrit.import_support.php"],
				"title" => GetMessage("ACRIT_IMPORT_MENU_SUPPORT")
			],
		]
	];
	return $aMenu;
}
return false;