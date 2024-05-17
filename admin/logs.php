<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/data.import/prolog.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/data.import/include.php");

IncludeModuleLangFile(__FILE__);

$module_id = 'data.import';
$moduleAccessLevel = $APPLICATION->GetGroupRight($module_id);

if ($moduleAccessLevel == "D")
    $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

CAdminNotify::DeleteByTag("LOGS_ARE_TOO_BIG");

$sTableID = "data_import_logs";

$oSort = new CAdminSorting($sTableID, "ID", "desc");
$arOrder = (strtoupper($by) === "ID"? array($by => $order): array($by => $order, "ID" => "ASC"));
$lAdmin = new CAdminUiList($sTableID, $oSort);

$cData = new CDataImportLog;
$pData = new CDataImportPlan;

$arFilterFields = Array(
	"find_plan_id",
	"find_timestamp_x",
	"find_event",
	"find_message",
);

$lAdmin->InitFilter($arFilterFields);

$arFilter = Array(
	"TIMESTAMP_X" => $find_timestamp_x,
	"PLAN_ID" => $find_plan_id,
	"EVENT"	=> $find_event,
	"?MESSAGE" => $find_message,
	"MESSAGE" => $find_message,
);

/* Prepare data for new filter */
$queryObject = $pData->getList(Array($b = "sort" => $o = "asc"), array());
$listPlans = array();
while($plan = $queryObject->getNext())
	$listPlans[$plan["ID"]] = htmlspecialcharsbx($plan["NAME"]).' ['.$plan["ID"].']';

$filterFields = array(
	array(
		"id" => "PLAN_ID",
		"name" => GetMessage("PLAN_ID"),
		"filterable" => "",
		"type" => "list",
		"items" => $listPlans,
		"default" => true
	),
	array(
		"id" => "TIMESTAMP_X",
		"name" => GetMessage("TIMESTAMP_X"),
		"filterable" => "",
		"type" => "date",
		"default" => true
	),
	array(
		"id" => "EVENT",
		"name" => GetMessage("EVENT"),
		"filterable" => "",
		"default" => true
	),
	array(
		"id" => "MESSAGE",
		"name" => GetMessage("MESSAGE"),
		"filterable" => "?",
		"quickSearch" => "?",
		"default" => true
	),
);

$lAdmin->AddFilter($filterFields, $arFilter);

if(($arID = $lAdmin->GroupAction()) && $moduleAccessLevel=="W")
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
		
		$ID = IntVal($ID);
    
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
		"id"    =>	"TIMESTAMP_X",
		"content"  =>	GetMessage("TIMESTAMP_X"),
		"sort"    =>	"timestamp_x",
		"align"    =>	"center",
		"default"  =>	true,
	),
	array(  
		"id"    =>	"PLAN_ID",
		"content"  =>	GetMessage("PLAN_ID"),
		"sort"    =>	"plan_id",
		"align"    =>	"center",
		"default"  =>	true,
	),
	array(  
		"id"    =>	"EVENT",
		"content"  =>	GetMessage("EVENT"),
		"sort"    =>	"event",
		"align"    =>	"center",
		"default"  =>	true,
	),
	array(  
		"id"    =>	"MESSAGE",
		"content"  =>	GetMessage("MESSAGE"),
		"sort"    =>	"message",
		"align"    =>	"center",
		"default"  =>	true,
	),
	array(  
		"id"    =>	"DATA",
		"content"  =>	GetMessage("DATA"),
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
	$row =& $lAdmin->AddRow($f_ID, $arRes);
	
	$DATA = unserialize(base64_decode($f_DATA));

	if(is_array($DATA))
	{
		$resultData = '';
		foreach($DATA as $code => $value)
		{
			$resultData .= $code.': <strong>'.(is_array($value) ? implode(' / ', $value) : strip_tags($value)).'</strong></br>';
		}
		//$row->AddViewField("DATA", implode(',', $DATA));
		$row->AddViewField("DATA", $resultData);
	}
	else
		$row->AddViewField("DATA", strip_tags($f_DATA));
	
	$row->AddViewField("PLAN_ID", '<a href="data.import_plan_edit.php?ID='.$f_PLAN_ID.'&lang='.LANG.'">'.$listPlans[$f_PLAN_ID].'</a>');

	$arActions = array();
	
	if($moduleAccessLevel=="W")
	{
		$arActions[] = array(
			"ICON"=>"delete",
			"TEXT"=>GetMessage("DELETE_ELEMENT"),
			"ACTION"=>"if(confirm('".GetMessageJS("CONFIRM_DELETING")."')) ".$lAdmin->ActionDoGroup($f_ID, "delete")
		);
	}
  
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
	$aContext = array();
	
	$lAdmin->AddAdminContextMenu($aContext);

	$lAdmin->AddGroupActionTable(
		Array(
			"delete"=>true,
			"for_all"=>true,
		)
	);
}
else
{
	$lAdmin->AddAdminContextMenu(array());
}

$lAdmin->CheckListMode();

$APPLICATION->SetTitle(GetMessage("PAGE_TITLE"));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

$lAdmin->DisplayFilter($filterFields);
$lAdmin->DisplayList();

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");