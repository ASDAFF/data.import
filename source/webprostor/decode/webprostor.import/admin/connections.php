<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/webprostor.import/prolog.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/webprostor.import/include.php");

IncludeModuleLangFile(__FILE__);

$module_id = 'webprostor.import';
$moduleAccessLevel = $APPLICATION->GetGroupRight($module_id);

if ($moduleAccessLevel == "D")
    $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

$sTableID = "webprostor_import_plans_connections";

$oSort = new CAdminSorting($sTableID, "SORT", "desc");
//$arOrder = (strtoupper($by) === "ID"? array($by => $order): array($by => $order, "ID" => "ASC"));
$lAdmin = new CAdminUiList($sTableID, $oSort);

$cData = new CWebprostorImportPlanConnections;
$pData = new CWebprostorImportPlan;

$arFilterFields = Array(
	"find_active",
	"find_plan_id",
	"find_name",
	"find_is_image",
	"find_is_file",
	"find_is_url",
	"find_is_required",
	"find_use_in_search",
);

$lAdmin->InitFilter($arFilterFields);

$arFilter = array(
	"ACTIVE" => $find_active,
	"PLAN_ID" => $find_plan_id,
	"?NAME" => $find_name,
	"IS_IMAGE" => $find_is_image,
	"IS_FILE" => $find_is_file,
	"IS_URL" => $find_is_url,
	"IS_REQUIRED" => $find_is_required,
	"USE_IN_SEARCH" => $find_use_in_search,
);

/* Prepare data for new filter */
$queryObject = $pData->getList(Array($b = "ID" => $o = "asc"), array());
$listPlans = array();
while($plan = $queryObject->getNext())
	$listPlans[$plan["ID"]] = htmlspecialcharsbx($plan["NAME"]).' ['.$plan["ID"].']';

$filterFields = array(
	array(
		"id" => "ACTIVE",
		"name" => GetMessage("ACTIVE"),
		"type" => "checkbox",
		"filterable" => "",
		"default" => true
	),
	array(
		"id" => "PLAN_ID",
		"name" => GetMessage("PLAN_ID"),
		"filterable" => "",
		"type" => "list",
		"items" => $listPlans,
		"default" => true
	),
	array(
		"id" => "NAME",
		"name" => GetMessage("NAME"),
		"filterable" => "?",
		"quickSearch" => "?",
		"default" => true
	),
	array(
		"id" => "IS_IMAGE",
		"name" => GetMessage("IS_IMAGE"),
		"type" => "list",
		"type" => "checkbox",
		"filterable" => ""
	),
	array(
		"id" => "IS_FILE",
		"name" => GetMessage("IS_FILE"),
		"type" => "checkbox",
		"filterable" => ""
	),
	array(
		"id" => "IS_URL",
		"name" => GetMessage("IS_URL"),
		"type" => "checkbox",
		"filterable" => ""
	),
	array(
		"id" => "IS_REQUIRED",
		"name" => GetMessage("IS_REQUIRED"),
		"type" => "checkbox",
		"filterable" => ""
	),
	array(
		"id" => "USE_IN_SEARCH",
		"name" => GetMessage("USE_IN_SEARCH"),
		"type" => "list",
		"type" => "checkbox",
		"filterable" => ""
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
					$lAdmin->AddUpdateError(GetMessage("SAVING_ERROR")." ".$cData->LAST_ERROR, $ID);
					$DB->Rollback();
				}
				else
					$DB->Commit();
			}
			else
			{
				$lAdmin->AddUpdateError(GetMessage("SAVING_ERROR")." ".GetMessage("ELEMENT_DOS_NOT_EXIST"), $ID);
				$DB->Rollback();
			}
		}
	}

	if($arID = $lAdmin->GroupAction())
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
				foreach($arFields as $key=>$value)
					$arData[$key]=$value;
			}
		
			switch($_REQUEST['action'])
			{
				case "delete":
					@set_time_limit(0);
					$DB->StartTransaction();
					if(!$cData->Delete($ID))
					{
						$DB->Rollback();
						$lAdmin->AddGroupError(GetMessage("DELETING_ERROR"), $ID);
					}
					$DB->Commit();
					break;
				case "activate":
				case "deactivate":
					$arData["ACTIVE"] = ($_REQUEST['action']=="activate"?"Y":"N");
					if(!$cData->Update($ID, $arData))
						$lAdmin->AddGroupError(GetMessage("UPDATING_ERROR").$cData->LAST_ERROR, $ID);

					break;
				case "is_required":
				case "not_required":
					$arData["IS_REQUIRED"] = ($_REQUEST['action']=="is_required"?"Y":"N");
					if(!$cData->Update($ID, $arData))
						$lAdmin->AddGroupError(GetMessage("UPDATING_ERROR").$cData->LAST_ERROR, $ID);

					break;
			}
		}
	}
}

$arHeader = array(
	array(
		"id"=>"ID",
		"content"=>"ID",
		"sort"=>"id",
		"default"=>true,
	),
	array(
		"id"=>"ACTIVE",
		"content"=>GetMessage("ACTIVE"),
		"sort"=>"active",
		"default"=>true,
	),
	array(  
		"id"    =>	"IS_REQUIRED",
		"content"  =>	GetMessage("IS_REQUIRED"),
		"sort"    =>	"is_required",
		"align"    =>	"center",
		"default"  =>	true,
	),
	array(
		"id"=>"PLAN_ID",
		"content"=>GetMessage("PLAN_ID"),
		"sort"=>"plan_id",
		"default"=>true,
	),
	array(
		"id"=>"ENTITY",
		"content"=>GetMessage("ENTITY"),
		"sort"=>"entity",
		"default"=>true,
	),
	array(  
		"id"    =>	"NAME",
		"content"  =>	GetMessage("NAME"),
		"sort"    =>	"name",
		"align"    =>	"center",
		"default"  =>	true,
	),
	array(
		"id"=>"ENTITY_ATTRIBUTE",
		"content"=>GetMessage("ENTITY_ATTRIBUTE"),
		"sort"=>"entity_attribute",
		"default"=>true,
	),
	array(  
		"id"    =>	"SORT",
		"content"  =>	GetMessage("SORT"),
		"sort"    =>	"sort",
		"align"    =>	"center",
		"default"  =>	true,
	),
	array(  
		"id"    =>	"IS_IMAGE",
		"content"  =>	GetMessage("IS_IMAGE"),
		"sort"    =>	"is_image",
		"align"    =>	"center",
		"default"  =>	true,
	),
	array(  
		"id"    =>	"IS_FILE",
		"content"  =>	GetMessage("IS_FILE"),
		"sort"    =>	"is_file",
		"align"    =>	"center",
		"default"  =>	true,
	),
	array(  
		"id"    =>	"IS_URL",
		"content"  =>	GetMessage("IS_URL"),
		"sort"    =>	"is_url",
		"align"    =>	"center",
		"default"  =>	true,
	),
	array(  
		"id"    =>	"USE_IN_SEARCH",
		"content"  =>	GetMessage("USE_IN_SEARCH"),
		"sort"    =>	"use_in_search",
		"align"    =>	"center",
		"default"  =>	true,
	),
);

$lAdmin->AddHeaders($arHeader);

$rsData = $cData->GetList(array($by=>$order), $arFilter);
$rsData = new CAdminUiResult($rsData, $sTableID);
$rsData->NavStart();

$lAdmin->SetNavigationParams($rsData);

while($arRes = $rsData->NavNext(true, "f_"))
{
	$connection_edit_link = "webprostor.import_connection_edit.php?ID=".$f_ID.'&PLAN_ID='.$f_PLAN_ID."&lang=".LANG;
	$connection_copy_link = "webprostor.import_connection_edit.php?COPY_ID=".$f_ID."&lang=".LANG;
		
	$row =& $lAdmin->AddRow($f_ID, $arRes, $connection_edit_link, GetMessage("EDIT_CONNECTION"));
	
	$row->AddViewField("PLAN_ID", '<a href="webprostor.import_plan_edit.php?ID='.$f_PLAN_ID.'&lang='.LANG.'">'.$listPlans[$f_PLAN_ID].'</a>');
	
	$row->AddViewField("ENTITY", '<a href="'.$connection_edit_link.'">'.$f_NAME.' ['.$f_ENTITY.']</a>');
	
	$row->AddCheckField("ACTIVE");
	$row->AddInputField("SORT", array("size"=>"35"));
	$row->AddInputField("ENTITY_ATTRIBUTE", array("size"=>"35"));
	$row->AddCheckField("IS_IMAGE");
	$row->AddCheckField("IS_FILE");
	$row->AddCheckField("IS_URL");
	$row->AddCheckField("IS_REQUIRED");
	$row->AddCheckField("USE_IN_SEARCH");
	
	$arActions = array();
	
	$arActions[] = array(
		"ICON" => "edit",
		"TEXT" => GetMessage("EDIT_CONNECTION"),
		"ACTION" => $lAdmin->ActionRedirect($connection_edit_link),
		"DEFAULT" => true,
	);
	
	if($moduleAccessLevel == 'W')
	{
		$arActions[] = array(
			"ICON" => "copy",
			"TEXT" => GetMessage("COPY_CONNECTION"),
			"ACTION" => $lAdmin->ActionRedirect($connection_copy_link),
			"DEFAULT" => true,
		);
		
		if($f_ACTIVE == "Y")
		{
			$arActions[] = array(
				"TEXT" => GetMessage("CONNECTIONS_LIST_DEACTIVATE"),
				"ACTION" => $lAdmin->ActionDoGroup($f_ID, "deactivate"),
				"ONCLICK" => "",
			);
		}
		else
		{
			$arActions[] = array(
				"TEXT" => GetMessage("CONNECTIONS_LIST_ACTIVATE"),
				"ACTION" => $lAdmin->ActionDoGroup($f_ID, "activate"),
				"ONCLICK" => "",
			);
		}
		
		if($f_IS_REQUIRED == "Y")
		{
			$arActions[] = array(
				"TEXT" => GetMessage("CONNECTIONS_LIST_NOT_REQUIRED"),
				"ACTION" => $lAdmin->ActionDoGroup($f_ID, "not_required"),
				"ONCLICK" => "",
			);
		}
		else
		{
			$arActions[] = array(
				"TEXT" => GetMessage("CONNECTIONS_LIST_IS_REQUIRED"),
				"ACTION" => $lAdmin->ActionDoGroup($f_ID, "is_required"),
				"ONCLICK" => "",
			);
		}
		
		$arActions[] = array(
			"ICON"=>"delete",
			"TEXT"=>GetMessage("DELETE_CONNECTION"),
			"ACTION"=>"if(confirm('".GetMessageJS("CONFIRM_DELETING")."')) ".$lAdmin->ActionDoGroup($f_ID, "delete")
		);
	}

	if(count($arActions))
		$row->AddActions($arActions);
}

$lAdmin->AddFooter(
	array(
		array(
			"title"=>GetMessage("MAIN_ADMIN_LIST_SELECTED"),
			"value"=>$rsData->SelectedRowsCount()
		),
		array(
			"counter"=>true,
			"title"=>GetMessage("MAIN_ADMIN_LIST_CHECKED"),
			"value"=>"0"
		),
	)
);

if ($moduleAccessLevel>="W")
{
	$aContext = array(
		array(
			"ICON"=>"btn_new",
			"TEXT"=>GetMessage("ADD_CONNECTION"),
			"LINK"=>"webprostor.import_connection_edit.php?lang=".LANGUAGE_ID."&PLAN_ID=".$arFilter["PLAN_ID"],
			"TITLE"=>GetMessage("ADD_CONNECTION_TITLE")
		),
		array(
			"TEXT"=>GetMessage("IMPORT_CONNECTIONS"),
			"LINK"=>"webprostor.import_connections_import.php?lang=".LANGUAGE_ID."&PLAN_ID=".$arFilter["PLAN_ID"],
			"TITLE"=>GetMessage("IMPORT_CONNECTIONS_TITLE")
		),
	);

	$lAdmin->AddAdminContextMenu($aContext);
	
	$lAdmin->AddGroupActionTable(
		Array(
			"edit"=>true,
			"delete"=>true,
			"for_all"=>true,
			"activate"=>GetMessage("CONNECTIONS_LIST_ACTIVATE"),
			"deactivate"=>GetMessage("CONNECTIONS_LIST_DEACTIVATE"),
			"is_required"=>GetMessage("CONNECTIONS_LIST_IS_REQUIRED"),
			"not_required"=>GetMessage("CONNECTIONS_LIST_NOT_REQUIRED"),
			)
	);
}
else
{
	$lAdmin->AddAdminContextMenu(array());
}

$lAdmin->CheckListMode();

$APPLICATION->SetTitle(GetMessage("PAGE_TITLE"));

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_admin_after.php");

$lAdmin->DisplayFilter($filterFields);
$lAdmin->DisplayList();

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");