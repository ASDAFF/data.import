<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/webprostor.import/prolog.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/webprostor.import/include.php");

IncludeModuleLangFile(__FILE__);

$module_id = 'webprostor.import';
$moduleAccessLevel = $APPLICATION->GetGroupRight($module_id);

if ($moduleAccessLevel == "D")
    $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

$sTableID = "webprostor_import_processing_settings";

$oSort = new CAdminSorting($sTableID, "ID", "desc");
$lAdmin = new CAdminUiList($sTableID, $oSort);

$cData = new CWebprostorImportProcessingSettings;
$cProcessingTypesData = new CWebprostorImportProcessingSettingsTypes;

$arFilterFields = Array(
	"find_id",
	"find_active",
	"find_processing_type",
);

$lAdmin->InitFilter($arFilterFields);

$arFilter = array(
	"ID" => $find_id,
	"ACTIVE" => $find_active,
	"PROCESSING_TYPE" => $find_processing_type,
);

/* Prepare data for new filter */
$listTypes = $cProcessingTypesData->GetTypes();
foreach($listTypes as $code => $type)
{
	$listTypes[$code] = $code.' ('.$type.')';
}

$filterFields = array(
	array(
		"id" => "ID",
		"name" => "ID",
		"type" => "number",
		"filterable" => "",
		"default" => true
	),
	array(
		"id" => "ACTIVE",
		"name" => GetMessage("ACTIVE"),
		"type" => "checkbox",
		"filterable" => "",
		"default" => true
	),
	array(
		"id" => "PROCESSING_TYPE",
		"name" => GetMessage("PROCESSING_TYPE"),
		"filterable" => "",
		"type" => "list",
		"items" => $listTypes,
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
					$lAdmin->AddUpdateError(GetMessage("SAVING_ERROR")." ".$cData->LAST_ERROR, $ID);
					$DB->Rollback();
				}
			}
			else
			{
				$lAdmin->AddUpdateError(GetMessage("SAVING_ERROR")." ".GetMessage("ELEMENT_DOS_NOT_EXIST"), $ID);
				$DB->Rollback();
			}
			$DB->Commit();
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
				if(is_array($arFields))
				{
					foreach($arFields as $key=>$value)
						$arData[$key]=$value;
				}
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
			}
		}
	}
}

$arHeader = array(
	array(
		"id"	=>	"ID",
		"content"	=>	"ID",
		"sort"	=>	"id",
		"default"	=>	true,
	),
	array(
		"id"	=>	"ACTIVE",
		"content"	=>	GetMessage("ACTIVE"),
		"sort"	=>	"active",
		"default"	=>	true,
	),
	array(  
		"id"    =>	"PROCESSING_TYPE",
		"content"  =>	GetMessage("PROCESSING_TYPE"),
		"sort"    =>	"processing_type",
		"align"    =>	"center",
		"default"  =>	true,
	),
	array(  
		"id"    =>	"SORT",
		"content"  =>	GetMessage("SORT"),
		"sort"    =>	"sort",
		"align"    =>	"center",
		"default"  =>	true,
	),
	array(  
		"id"    =>	"PARAMS",
		"content"  =>	GetMessage("PARAMS"),
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
	$processing_edit_link = "webprostor.import_processing_setting_edit.php?ID=".$f_ID."&lang=".LANG;
	$processing_copy_link = "webprostor.import_processing_setting_edit.php?COPY_ID=".$f_ID."&lang=".LANG;
		
	$row =& $lAdmin->AddRow($f_ID, $arRes, $processing_edit_link, GetMessage("EDIT_PROCESSING"));
	
	$row->AddCheckField("ACTIVE");
	$row->AddInputField("SORT", array("size"=>"35"));
	$row->AddSelectField("PROCESSING_TYPE", $listTypes);
	
	$row->AddViewField("PARAMS", htmlspecialcharsbx($cProcessingTypesData->GetParamsValue($f_PARAMS)));
	
	$arActions = array();
	
	$arActions[] = array(
		"ICON" => "edit",
		"TEXT" => GetMessage("EDIT_PROCESSING"),
		"ACTION" => $lAdmin->ActionRedirect($processing_edit_link),
		"DEFAULT" => true,
	);
	
	if($moduleAccessLevel=="W")
	{
		$arActions[] = array(
			"ICON" => "copy",
			"TEXT" => GetMessage("COPY_PROCESSING"),
			"ACTION" => $lAdmin->ActionRedirect($processing_copy_link),
			"DEFAULT" => true,
		);
		
		if($f_ACTIVE == "Y")
		{
			$arActions[] = array(
				"TEXT" => GetMessage("LIST_DEACTIVATE"),
				"ACTION" => $lAdmin->ActionDoGroup($f_ID, "deactivate"),
				"ONCLICK" => "",
			);
		}
		else
		{
			$arActions[] = array(
				"TEXT" => GetMessage("LIST_ACTIVATE"),
				"ACTION" => $lAdmin->ActionDoGroup($f_ID, "activate"),
				"ONCLICK" => "",
			);
		}
		
		$arActions[] = array(
			"ICON"=>"delete",
			"TEXT"=>GetMessage("DELETE_PROCESSING"),
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
			"TEXT"=>GetMessage("ADD_PROCESSING"),
			"LINK"=>"webprostor.import_processing_setting_edit.php?lang=".LANGUAGE_ID,
		),
	);

	$lAdmin->AddAdminContextMenu($aContext);
	
	$lAdmin->AddGroupActionTable(
		Array(
			"edit"=>true,
			"delete"=>true,
			"for_all"=>true,
			"activate"=>GetMessage("LIST_ACTIVATE"),
			"deactivate"=>GetMessage("LIST_DEACTIVATE"),
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