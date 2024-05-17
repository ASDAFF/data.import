<?
use Bitrix\Main\Localization\Loc;

AddEventHandler('main', 'OnBuildGlobalMenu', 'OnBuildGlobalMenuHandlerWebprostor');

function OnBuildGlobalMenuHandlerWebprostor(&$arGlobalMenu, &$arModuleMenu)
{
	$module_id = 'webprostor.core';
	
	$GLOBALS['APPLICATION']->SetAdditionalCss("/bitrix/panel/".$module_id."/menu/style.css");
	
	if($GLOBALS['APPLICATION']->GetGroupRight($module_id) >= 'R')
	{
		if($GLOBALS['APPLICATION']->GetGroupRight('main') >= 'W')
		{
			$items = array(
				array(
					"module_id" => $MODULE_ID,
					"text" => GetMessage("WEBPROSTOR_CORE_INNER_MENU_SETTINGS_TEXT"),
					"url" => "settings.php?lang=ru&mid=webprostor.core&lang=".LANGUAGE_ID,
					"icon" => "util_menu_icon",
				),
			);
		}
		$arMenu = array(
			'menu_id' => 'global_menu_aspro_webprostor',
			'text' => Loc::getMessage('WEBPROSTOR_CORE_SUPPORT_MENU_TEXT'),
			'sort' => 0,
			'items_id' => 'global_menu_aspro_webprostor_core',
			"icon" => "support_menu_icon",
			"url" => "webprostor.core_support.php?lang=".LANGUAGE_ID,
			"items" => $items
		);
	}
	
	$arGlobalMenu['global_menu_webprostor'] = array(
		'menu_id' => 'global_menu_webprostor',
		'text' => Loc::getMessage('WEBPROSTOR_CORE_GLOBAL_WEBPROSTOR_MENU_TEXT'),
		'sort' => 300,
		'items_id' => 'global_menu_webprostor_items',
	);

	$arGlobalMenu['global_menu_webprostor']['items'][$module_id] = $arMenu;
}