<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/webprostor.import/prolog.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/webprostor.import/include.php");

use Bitrix\Main\Localization\Loc;

$module_id = 'webprostor.import';
$moduleAccessLevel = $APPLICATION->GetGroupRight($module_id);

if ($moduleAccessLevel == "D")
    $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));

$sTableID = "webprostor_import_plans";

$oSort = new CAdminSorting($sTableID, "ID", "desc");
$arOrder = (strtoupper($by) === "ID"? array($by => $order): array($by => $order, "ID" => "ASC"));
$lAdmin = new CAdminUiList($sTableID, $oSort);

$cData = new CWebprostorImportPlan;

$arFilterFields = Array(
	"find_id",
	"find_active",
	"find_name",
	"find_iblock_id",
	"find_highload_block",
	"find_import_format",
	"find_debug_events",
);

$lAdmin->InitFilter($arFilterFields);

$arFilter = Array(
	"ID" => $find_id,
	"ACTIVE" => $find_active,
	"NAME" => $find_name,
	"?NAME" => $find_name,
	"IBLOCK_ID" => $find_iblock_id,
	"HIGHLOAD_BLOCK" => $find_highload_block,
	"IMPORT_FORMAT" => $find_import_format,
	"DEBUG_EVENTS" => $find_debug_events,
);

/* Prepare data for new filter */
$queryObject = CIBlock::GetList(Array("ID"=>"ASC"), Array());
$listIblocks = array('' => Loc::getMessage("BLOCK_ID_NO"));
$listIblocksEdit = array(0 => Loc::getMessage("BLOCK_ID_NO"));
while($iblock = $queryObject->Fetch())
{
	$listIblocks[$iblock["ID"]] = htmlspecialcharsbx($iblock["NAME"]).' ['.$iblock["ID"].']';
	$listIblocksEdit[$iblock["ID"]] = '<a target="_blank" href="iblock_list_admin.php?IBLOCK_ID='.$iblock["ID"].'&type='.$iblock['IBLOCK_TYPE_ID'].'&lang='.LANG.'&find_section_section=0">'.htmlspecialcharsbx($iblock['NAME']).' ['.$iblock["ID"].']</a>';
}
$queryObject = \Bitrix\Highloadblock\HighloadBlockTable::getList();
$listHblocks = array('' => Loc::getMessage("BLOCK_ID_NO"));
$listHblocksEdit = array(0 => Loc::getMessage("BLOCK_ID_NO"));
while($hldata = $queryObject->Fetch())
{
	$listHblocks[$hldata["ID"]] = htmlspecialcharsbx($hldata["NAME"]).' ['.$hldata["TABLE_NAME"].']';
	$listHblocksEdit[$hldata["ID"]] = '<a target="_blank" href="highloadblock_rows_list.php?ENTITY_ID='.$hldata["ID"].'&lang='.LANG.'">'.htmlspecialcharsbx($hldata['NAME']).' ['.$hldata["TABLE_NAME"].']</a>';
}
/*$importFormats = Array(
	"CSV" => "CSV",
	"XML" => "XML",
	"XLS" => "XLS",
	"XLSX" => "XLSX",
	"ODS" => "ODS",
	"XODS" => "XODS",
);*/
$importFormats = CWebprostorImportPlan::getFormats();
$fileCharsets = Array(
	"UTF-8" => "UTF-8",
	"WINDOWS-1251" => "WINDOWS-1251",
);

$filterFields = array(
	array(
		"id" => "ID",
		"name" => "ID",
		"filterable" => "",
		"default" => true
	),
	array(
		"id" => "ACTIVE",
		"name" => Loc::getMessage("ACTIVE"),
		"type" => "checkbox",
		"filterable" => "",
		"default" => true
	),
	array(
		"id" => "NAME",
		"name" => Loc::getMessage("NAME"),
		"filterable" => "?",
		"quickSearch" => "?",
		"default" => true
	),
	array(
		"id" => "IBLOCK_ID",
		"name" => Loc::getMessage("IBLOCK_ID"),
		"filterable" => "",
		"type" => "list",
		"items" => $listIblocks,
		"default" => true
	),
	array(
		"id" => "HIGHLOAD_BLOCK",
		"name" => Loc::getMessage("HIGHLOAD_BLOCK"),
		"filterable" => "",
		"type" => "list",
		"items" => $listHblocks,
		"default" => true
	),
	array(
		"id" => "IMPORT_FORMAT",
		"name" => Loc::getMessage("IMPORT_FORMAT"),
		"filterable" => "",
		"type" => "list",
		"items" => $importFormats,
		"default" => true
	),
	array(
		"id" => "IMPORT_FILE_SHARSET",
		"name" => Loc::getMessage("IMPORT_FILE_SHARSET"),
		"filterable" => "",
		"type" => "list",
		"items" => $fileCharsets,
		"default" => true
	),
	array(
		"id" => "DEBUG_EVENTS",
		"name" => Loc::getMessage("DEBUG_EVENTS"),
		"type" => "checkbox",
		"filterable" => "",
		"default" => true
	),
);

$lAdmin->AddFilter($filterFields, $arFilter);

if($moduleAccessLevel=="W")
{
	if($lAdmin->EditAction())
	{
		foreach($FIELDS as $ID=>$arFields)
		{
			$DB->StartTransaction();
			$ID = IntVal($ID);
			
			if(!$lAdmin->IsUpdated($ID))
				continue;

			if(($rsData = $cData->GetByID($ID)) && ($arData = $rsData->Fetch()))
			{
				foreach($arFields as $key=>$value)
					$arData[$key]=$value;
					
				if(!$cData->Update($ID, $arData))
				{
					$lAdmin->AddGroupError(Loc::getMessage("SAVING_ERROR")." ".$cData->LAST_ERROR, $ID);
					$DB->Rollback();
				}
				else
					$DB->Commit();
			}
			else
			{
				$lAdmin->AddGroupError(Loc::getMessage("SAVING_ERROR")." ".Loc::getMessage("ELEMENT_DOS_NOT_EXIST"), $ID);
				$DB->Rollback();
			}
		}
	}

	if(($arID = $lAdmin->GroupAction()))
	{
		if (!empty($_REQUEST["action_all_rows_".$sTableID]) && $_REQUEST["action_all_rows_".$sTableID] === "Y")
		{
			$rsData = $cData->GetList(array($by=>$order), $arFilter);
			while($arRes = $rsData->Fetch())
				$arID[] = $arRes['ID'];
		}

		foreach($arID as $ID)
		{
			if(strlen($ID)<=0)
				continue;
			
			if(($rsData = $cData->GetByID($ID)) && ($arData = $rsData->Fetch()))
			{
				if(is_array($arFields))
				{
					foreach($arFields as $key=>$value)
						$arData[$key]=$value;
				}
			}
		
			if(isset($arData["LAST_IMPORT_DATE"]))
				unset($arData["LAST_IMPORT_DATE"]);
			
			if(isset($arData["LAST_STEP_IMPORT_DATE"]))
				unset($arData["LAST_STEP_IMPORT_DATE"]);
			
			if(isset($arData["LAST_FINISH_IMPORT_DATE"]))
				unset($arData["LAST_FINISH_IMPORT_DATE"]);
			
			switch($_REQUEST['action'])
			{
				case "delete":
					@set_time_limit(0);
					$DB->StartTransaction();
					if(!$cData->Delete($ID))
					{
						$DB->Rollback();
						$lAdmin->AddGroupError(Loc::getMessage("DELETING_ERROR"), $ID);
					}
					$DB->Commit();
					break;
				case "activate":
				case "deactivate":
					$arData["ACTIVE"] = ($_REQUEST['action']=="activate"?"Y":"N");
					if(!$cData->Update($ID, $arData))
						$lAdmin->AddGroupError(Loc::getMessage("UPDATING_ERROR").$cData->LAST_ERROR, $ID);

					break;
				case "debug":
				case "undebug":
					$arData["DEBUG_EVENTS"] = ($_REQUEST['action']=="debug"?"Y":"N");
					if(!$cData->Update($ID, $arData))
						$lAdmin->AddGroupError(Loc::getMessage("UPDATING_ERROR").$cData->LAST_ERROR, $ID);

					break;
			}
		}
	}
}

$arHeader = array(
	array(  
		"id"    =>	"ID",
		"content"  =>	"ID",
		"sort"    =>	"id",
		"align"    =>	"center",
		"default"  =>	true,
	),
	array(  
		"id"    =>	"ACTIVE",
		"content"  =>	Loc::getMessage("ACTIVE"),
		"sort"    =>	"active",
		"default"  =>	true,
	),
	array(  
		"id"    =>	"NAME",
		"content"  =>	Loc::getMessage("NAME"),
		"sort"    =>	"name",
		"default"  =>	true,
	),
	array(  
		"id"    =>	"SORT",
		"content"  =>	Loc::getMessage("SORT"),
		"sort"    =>	"sort",
		"default"  =>	true,
	),
	array(  
		"id"    =>	"IBLOCK_ID",
		"content"  =>	Loc::getMessage("IBLOCK_ID"),
		"sort"    =>	"iblock_id",
		"default"  =>	true,
	),
	array(  
		"id"    =>	"HIGHLOAD_BLOCK",
		"content"  =>	Loc::getMessage("HIGHLOAD_BLOCK"),
		"sort"    =>	"highload_block",
		"default"  =>	true,
	),
	array(  
		"id"    =>	"IMPORT_FORMAT",
		"content"  =>	Loc::getMessage("IMPORT_FORMAT"),
		"sort"    =>	"import_format",
		"default"  =>	true,
	),
	array(  
		"id"    =>	"ITEMS_PER_ROUND",
		"content"  =>	Loc::getMessage("ITEMS_PER_ROUND"),
		"sort"    =>	"items_per_round",
		"default"  =>	true,
	),
	array(  
		"id"    =>	"IMPORT_FILE",
		"content"  =>	Loc::getMessage("IMPORT_FILE"),
		"sort"    =>	"import_file",
		"default"  =>	true,
	),
	array(  
		"id"    =>	"IMPORT_FILE_URL",
		"content"  =>	Loc::getMessage("IMPORT_FILE_URL"),
		"sort"    =>	"import_file_url",
		"default"  =>	false,
	),
	array(  
		"id"    =>	"AGENT_INTERVAL_URL",
		"content"  =>	Loc::getMessage("AGENT_INTERVAL_URL"),
		"sort"    =>	"agent_interval_url",
		"default"  =>	false,
	),
	array(  
		"id"    =>	"PATH_TO_IMAGES",
		"content"  =>	Loc::getMessage("PATH_TO_IMAGES"),
		"sort"    =>	"path_to_images",
		"default"  =>	false,
	),
	array(  
		"id"    =>	"PATH_TO_FILES",
		"content"  =>	Loc::getMessage("PATH_TO_FILES"),
		"sort"    =>	"path_to_files",
		"default"  =>	false,
	),
	array(  
		"id"    =>	"DEBUG_EVENTS",
		"content"  =>	Loc::getMessage("DEBUG_EVENTS"),
		"sort"    =>	"debug_events",
		"default"  =>	true,
	),
	array(  
		"id"    =>	"AGENT_INTERVAL",
		"content"  =>	Loc::getMessage("AGENT_INTERVAL"),
		"sort"    =>	"agent_interval",
		"default"  =>	false,
	),
	array(  
		"id"    =>	"AGENT_ID",
		"content"  =>	Loc::getMessage("AGENT_ID"),
		"sort"    =>	"agent_id",
		"default"  =>	true,
	),
	array(  
		"id"    =>	"LAST_IMPORT_DATE",
		"content"  =>	Loc::getMessage("LAST_IMPORT_DATE"),
		"sort"    =>	"last_import_date",
		"default"  =>	false,
	),
	array(  
		"id"    =>	"LAST_STEP_IMPORT_DATE",
		"content"  =>	Loc::getMessage("LAST_STEP_IMPORT_DATE"),
		"sort"    =>	"last_step_import_date",
		"default"  =>	false,
	),
	array(  
		"id"    =>	"LAST_FINISH_IMPORT_DATE",
		"content"  =>	Loc::getMessage("LAST_FINISH_IMPORT_DATE"),
		"sort"    =>	"last_finish_import_date",
		"default"  =>	false,
	),
	array(  
		"id"    =>	"CRON_PROGRESS",
		"content"  =>	Loc::getMessage("CRON_PROGRESS"),
		"default"  =>	false,
	),
);

$lAdmin->AddHeaders($arHeader);

$rsData = $cData->GetList(array($by=>$order), $arFilter);
$rsData = new CAdminUiResult($rsData, $sTableID);
$rsData->NavStart();

$lAdmin->SetNavigationParams($rsData);

while($arRes = $rsData->NavNext(true, "f_"))
{
	$plan_edit_link = "webprostor.import_plan_edit.php?ID=".$f_ID."&lang=".LANG;
	
	$row =& $lAdmin->AddRow($f_ID, $arRes, $plan_edit_link, Loc::getMessage("EDIT_ELEMENT"));

	$row->AddCheckField("ACTIVE"); 
	$row->AddInputField("NAME", array("size"=>20));
	$row->AddInputField("SORT", array("size"=>5));
	$row->AddInputField("ITEMS_PER_ROUND", array("size"=>20));
	$row->AddSelectField("IBLOCK_ID", $listIblocks);
	$row->AddSelectField("HIGHLOAD_BLOCK", $listHblocks);
	$row->AddSelectField("IMPORT_FORMAT", $importFormats);
	$row->AddInputField("IMPORT_FILE", array("size"=>20));
	$row->AddInputField("IMPORT_FILE_URL", array("size"=>20));
	$row->AddInputField("PATH_TO_IMAGES", array("size"=>20));
	$row->AddInputField("PATH_TO_FILES", array("size"=>20));
	$row->AddCheckField("DEBUG_EVENTS"); 
	$row->AddInputField("AGENT_INTERVAL", array("size"=>5));
	$row->AddInputField("AGENT_INTERVAL_URL", array("size"=>5));
	
	$row->AddViewField("NAME", '<a href="'.$plan_edit_link.'">'.$f_NAME.'</a>');
	if($f_IMPORT_FILE_URL != '')
		$row->AddViewField("IMPORT_FILE_URL", '<a target="_blank" href="'.$f_IMPORT_FILE_URL.'">'.$f_IMPORT_FILE_URL.'</a>');
	$row->AddViewField("IBLOCK_ID", $listIblocksEdit[$f_IBLOCK_ID]);
	$row->AddViewField("HIGHLOAD_BLOCK", $listHblocksEdit[$f_HIGHLOAD_BLOCK]);

	if($f_AGENT_ID>0)
	{
		$agentArr = CAgent::GetList(
			false,
			[
				'ID' => $f_AGENT_ID,
				'MODULE_ID' => $module_id,
			]
		)->Fetch();
		if(is_array($agentArr))
		{
			$agentViewField = '';
			if($agentArr['ACTIVE'] == 'N')
				$agentViewField .= '<img src="/bitrix/panel/webprostor.ozon/theme/images/yellow.gif" title="'.Loc::getMessage("WEBPROSTOR_IMPORT_AGENT_DEACTIVTED").'" />&nbsp;';
			$agentViewField .= '<a class="ui-btn ui-btn-link" href="agent_list.php?set_filter=Y&adm_filter_applied=0&find='.$f_AGENT_ID.'&find_type=id&find_module_id='.$module_id.'&lang='.LANG.'" target="_blank"><span>'.Loc::getMessage("WEBPROSTOR_IMPORT_AGENT_OPEN").'</span></a>';
			$row->AddViewField("AGENT_ID", $agentViewField);
		}
		else
			$row->AddViewField("AGENT_ID", '<img src="/bitrix/panel/'.$module_id.'/theme/images/red.gif" title="'.Loc::getMessage("WEBPROSTOR_IMPORT_AGENT_DELETED").'" />');
	}
	elseif($f_ACTIVE == 'Y')
		$row->AddViewField("AGENT_ID", '<img src="/bitrix/panel/'.$module_id.'/theme/images/red.gif" title="'.Loc::getMessage("WEBPROSTOR_IMPORT_AGENT_DELETED").'" />');
	else
		$row->AddViewField("AGENT_ID", '<img src="/bitrix/panel/'.$module_id.'/theme/images/grey.gif" title="'.Loc::getMessage("WEBPROSTOR_IMPORT_NO_AGENT").'" />');
	
	//CRON_PROGRESS
	$cron_file = $_SERVER['DOCUMENT_ROOT'].'/bitrix/tmp/'.$module_id.'/'.$f_ID.'_cron.txt';
	if(file_exists($cron_file))
	{
		$cronTemp = file_get_contents($cron_file);
		$cronData = unserialize($cronTemp);
		if(is_array($cronData) && array_key_exists('CURRENT', $cronData) && array_key_exists('TOTAL', $cronData))
			$row->AddViewField("CRON_PROGRESS", Loc::getMessage("CRON_PROGRESS_VALUE", ['#CURRENT#' => number_format($cronData['CURRENT'], 0, '', ' '), '#TOTAL#' => number_format($cronData['TOTAL'], 0, '', ' ')]));
	}

	$arActions = Array();
	
	if($moduleAccessLevel=="W")
	{
		if($f_ACTIVE == "Y")
		{
			$arActions[] = array(
				"TEXT" => Loc::getMessage("LIST_DEACTIVATE"),
				"ACTION" => $lAdmin->ActionDoGroup($f_ID, "deactivate"),
				"ONCLICK" => "",
			);
		}
		else
		{
			$arActions[] = array(
				"TEXT" => Loc::getMessage("LIST_ACTIVATE"),
				"ACTION" => $lAdmin->ActionDoGroup($f_ID, "activate"),
				"ONCLICK" => "",
			);
		}
		
		if($f_DEBUG_EVENTS == "Y")
		{
			$arActions[] = array(
				"TEXT" => Loc::getMessage("LIST_UNDEBUG"),
				"ACTION" => $lAdmin->ActionDoGroup($f_ID, "undebug"),
				"ONCLICK" => "",
			);
		}
		else
		{
			$arActions[] = array(
				"TEXT" => Loc::getMessage("LIST_DEBUG"),
				"ACTION" => $lAdmin->ActionDoGroup($f_ID, "debug"),
				"ONCLICK" => "",
			);
		}
	}
	
	$arActions[] = array(
		"ICON"=>"edit",
		"DEFAULT"=>true,
		"TEXT"=>	Loc::getMessage("EDIT_ELEMENT"),
		"ACTION"=>$lAdmin->ActionRedirect("webprostor.import_plan_edit.php?ID=".$f_ID.'&lang='.LANG)
	);
	
	if($moduleAccessLevel=="W")
	{
		$arActions[] = array(
			"ICON"=>"copy",
			"DEFAULT"=>true,
			"TEXT"=>	Loc::getMessage("COPY_ELEMENT"),
			"ACTION"=>$lAdmin->ActionRedirect("webprostor.import_plan_edit.php?COPY_ID=".$f_ID.'&lang='.LANG)
		);
	}
	
	$arActions[] = array(
		"ICON"=>"rename",
		"DEFAULT"=>true,
		"TEXT"=>	Loc::getMessage("EDIT_CONNECTIONS"),
		"LINK"=>"webprostor.import_plan_connections_edit.php?ID=".$f_ID.'&lang='.LANG,
	);
	
	$arActions[] = array(
		"ICON"=>"view",
		"DEFAULT"=>true,
		"TEXT"=>	Loc::getMessage("CONNECTIONS_LIST"),
		"LINK"=>"webprostor.import_connections.php?PLAN_ID=".$f_ID."&find_plan_id=".$f_ID."&apply_filter=Y&lang=".LANG,
	);
	
	if($moduleAccessLevel=="W")
	{
		$arActions[] = array(
			"ICON"=>"pack",
			"DEFAULT"=>true,
			"TEXT"=>Loc::getMessage("IMPORT_CONNECTIONS"),
			"LINK"=>"webprostor.import_connections_import.php?PLAN_ID=".$f_ID.'&lang='.LANG,
		);
		
		$arActions[] = array(
			"ICON"=>"delete",
			"TEXT"=>Loc::getMessage("DELETE_ELEMENT"),
			"ACTION"=>"if(confirm('".GetMessageJS("CONFIRM_DELETING")."')) ".$lAdmin->ActionDoGroup($f_ID, "delete")
		);
	}
  
	$row->AddActions($arActions);
}

$lAdmin->AddFooter(
	array(
		array(
			"title"=>Loc::getMessage("MAIN_ADMIN_LIST_SELECTED"),
			"value"=>$rsData->SelectedRowsCount()
		),
		array(
			"counter"=>true,
			"title"=>Loc::getMessage("MAIN_ADMIN_LIST_CHECKED"),
			"value"=>"0"
		),
	)
);

if ($moduleAccessLevel>="W")
{
	$aContext = array(
		array(
			"TEXT"=>Loc::getMessage("ADD_ELEMENT"),
			"LINK"=>"webprostor.import_plan_edit.php?lang=".LANG,
			"TITLE"=>Loc::getMessage("ADD_ELEMENT_TITLE"),
			"ICON"=>"btn_new",
		),
	);
	
	$lAdmin->AddAdminContextMenu($aContext);
	
	$lAdmin->AddGroupActionTable(
		Array(
			"edit"=>true,
			"delete"=>true,
			"for_all"=>true,
			"activate"=>Loc::getMessage("LIST_ACTIVATE"),
			"deactivate"=>Loc::getMessage("LIST_DEACTIVATE"),
			"debug"=>Loc::getMessage("LIST_DEBUG"),
			"undebug"=>Loc::getMessage("LIST_UNDEBUG"),
		)
	);
}
else
{
	$lAdmin->AddAdminContextMenu(array());
}

$lAdmin->CheckListMode();

$APPLICATION->SetTitle(Loc::getMessage("PAGE_TITLE"));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

$lAdmin->DisplayFilter($filterFields);
$lAdmin->DisplayList();

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");