<?
IncludeModuleLangFile(__FILE__);
$MODULE_ID = "data.import";

if($APPLICATION->GetGroupRight($MODULE_ID)>"D")
{
	if(!CModule::IncludeModule($MODULE_ID))
		return false;
	
	$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/panel/'.$MODULE_ID.'/theme/menu.css');
	
	$plans = Array();
	$CPlan = new CDataImportPlan;
	$plansRes = $CPlan->GetList(Array("SORT"=>"ASC"), Array("SHOW_IN_MENU" => "Y"));
	while($plan = $plansRes->GetNext())
	{
		$plans[] = Array(
			"module_id" => $MODULE_ID,
			"text" => $plan["NAME"],
			"title" => $plan["NAME"],
			"url" => "data.import_plan_edit.php?ID=".$plan["ID"]."&lang=".LANGUAGE_ID,
			"items_id" => "data_import_plan_edit_".$plan["ID"],
		);
	}
	
	$aMenu = array(
		"parent_menu" => "global_menu_data",
		"section" => $MODULE_ID,
		"sort" => 100,
		"text" => GetMessage("DATA_IMPORT_MAIN_MENU_TEXT"),
		"icon" => "data_import",
		"page_icon" => "",
		"items_id" => "data_import",
		"more_url" => array(
			"data.import_conversion.php",
		),
		"items" => array(
			array(
				"module_id" => $MODULE_ID,
				"text" => GetMessage("DATA_IMPORT_INNER_MENU_PLANS_TEXT"),
				"title" => GetMessage("DATA_IMPORT_INNER_MENU_PLANS_TITLE"),
				"url" => "data.import_plans.php?lang=".LANGUAGE_ID,
				"icon" => "fileman_menu_icon",
				"more_url" => array(
					"data.import_plan_edit.php", 
					"data.import_plan_connections_edit"
				),
				"items_id" => "data_import_plans",
				"items" => $plans,
			),
			array(
				"module_id" => $MODULE_ID,
				"text" => GetMessage("DATA_IMPORT_INNER_MENU_PLAN_CONNECTIONS_TEXT"),
				"title" => GetMessage("DATA_IMPORT_INNER_MENU_PLAN_CONNECTIONS_TITLE"),
				"url" => "data.import_connections.php?lang=".LANGUAGE_ID,
				"icon" => "workflow_menu_icon",
				"items_id" => "data_import_connections",
				"more_url" => array(
					"data.import_connection_edit.php",
					"data.import_connections_import.php",
				),
			),
			array(
				"module_id" => $MODULE_ID,
				"text" => GetMessage("DATA_IMPORT_INNER_MENU_PROCESSING_SETTINGS_TEXT"),
				"title" => GetMessage("DATA_IMPORT_INNER_MENU_PROCESSING_SETTINGS_TITLE"),
				"url" => "data.import_processing_settings.php?lang=".LANGUAGE_ID,
				"icon" => "util_menu_icon",
				"items_id" => "data_processing_settings",
				"more_url" => array(
					"data.import_processing_setting_edit.php",
				),
			),
			array(
				"module_id" => $MODULE_ID,
				"text" => GetMessage("DATA_IMPORT_INNER_MENU_MANUALLY_TEXT"),
				"title" => GetMessage("DATA_IMPORT_INNER_MENU_MANUALLY_TITLE"),
				//"url" => "data.import_manually.php?lang=".LANGUAGE_ID,
				"icon" => "update_menu_icon_partner",
				"items_id" => "data_import_manually",
				"items" => array(
					[
						"module_id" => $MODULE_ID,
						"text" => GetMessage("DATA_IMPORT_INNER_MENU_MANUALLY_PLANS"),
						"url" => "data.import_manually.php?lang=".LANGUAGE_ID,
						"items_id" => "data_import_manually_plans",
					],
					[
						"module_id" => $MODULE_ID,
						"text" => GetMessage("DATA_IMPORT_INNER_MENU_MANUALLY_LIST_VALUES"),
						"url" => "data.import_list_values.php?lang=".LANGUAGE_ID,
						"items_id" => "data_import_manually_list_values",
					],
				)
			),
			array(
				"module_id" => $MODULE_ID,
				"text" => GetMessage("DATA_IMPORT_INNER_MENU_LOGS_TEXT"),
				"title" => GetMessage("DATA_IMPORT_INNER_MENU_LOGS_TITLE"),
				"url" => "data.import_logs.php?lang=".LANGUAGE_ID,
				"icon" => "update_marketplace",
				"items_id" => "data_import_logs",
			),
			array(
				"module_id" => $MODULE_ID,
				"text" => GetMessage("DATA_INSTRUCTION"),
				"icon" => "learning_menu_icon",
				//"url" => "https://data.ru/learning/course/course1/index",
				"url" => 'javascript:void(window.open("https://data.ru/learning/course/course1/index", "_blank"));',
			),
			/*array(
				"module_id" => $MODULE_ID,
				"text" => GetMessage("DATA_SUPPORT"),
				"icon" => "support_menu_icon",
				"url" => "https://data.ru/support/tickets/",
			),*/
		)
	);
	
	if($_REQUEST['mode'] == 'chain')
		array_pop($aMenu['items']);

	return $aMenu;
}

return false;
?> 
