<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/data.import/prolog.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/data.import/include.php");

use Bitrix\Main\Localization\Loc;

$module_id = 'data.import';
$moduleAccessLevel = $APPLICATION->GetGroupRight($module_id);

if ($moduleAccessLevel == "D")
    $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));

if($back_url=='')
	$back_url = '/bitrix/admin/data.import_processing_settings.php?lang='.$lang;

$strWarning = "";

$aTabs = array(
	array("DIV" => "main", "TAB" => Loc::getMessage("ELEMENT_TAB"), "ICON"=>"", "TITLE"=>Loc::getMessage("ELEMENT_TAB_TITLE")),
);

$tabControl = new CAdminTabControl("tabControl", $aTabs);

$sTableID = "data_import_processing_settings";
$ID = intval($ID);
$COPY_ID = intval($COPY_ID);
$bVarsFromForm = false;
$cData = new CDataImportProcessingSettings;
$cProcessingTypesData = new CDataImportProcessingSettingsTypes;

if($_SERVER["REQUEST_METHOD"] == "POST" && strlen($Update)>0 && check_bitrix_sessid() && $moduleAccessLevel=="W")
{
	$processingFields = Array(
		"ACTIVE" => $ACTIVE,
		"SORT" => $SORT,
		"PROCESSING_TYPE" => $PROCESSING_TYPE,
		"PARAMS" => base64_encode(serialize($PARAMS)),
	);

	if($ID>0)
		$res = $cData->Update($ID, $processingFields);
	else
	{
		$ID = $cData->Add($processingFields);
		$res = ($ID>0);
	}

	if(!$res)
	{
		$strWarning.= Loc::getMessage("MESSAGE_SAVE_ERROR").":<br />".$cData->LAST_ERROR."";
		$bVarsFromForm = true;
	}
	else
	{
		if(strlen($apply)<=0 && strlen($save)>0)
		{
			if(strlen($back_url)>0)
				LocalRedirect("/".ltrim($back_url, "/"));
		}
		else
			LocalRedirect($APPLICATION->GetCurPage()."?lang=".$lang."&ID=".UrlEncode($ID)."&".$tabControl->ActiveTabParam());
	}
}

ClearVars("str_");
$str_ACTIVE = "Y";
$str_SORT = "500";

if($ID>0 || $COPY_ID>0)
{
	if($ID>0)
		$result = $cData->GetById($ID);
	else
		$result = $cData->GetById($COPY_ID);
	
	if(!$result->ExtractFields("str_"))
		$ID='';
}

$APPLICATION->SetTitle(($ID>0? Loc::getMessage("ELEMENT_EDIT_TITLE").': ID '.$str_ID.' ['.$str_PROCESSING_TYPE.']' : Loc::getMessage("ELEMENT_ADD_TITLE")));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

$listTypes = Array('' => Loc::getMessage("FIELD_PROCESSING_TYPE_CHOISE"));
$types = $cProcessingTypesData->GetTypes();
foreach($types as $code => $type)
{
	$types[$code] = $type.' ['.$code.']';
}
$listTypes = array_merge($listTypes, $types);
$listParams = $cProcessingTypesData->GetParams();

if($bVarsFromForm)
{
	$DB->InitTableVarsForEdit($sTableID, "", "str_");
}

$arFields["MAIN"]["LABEL"] = Loc::getMessage("GROUP_MAIN");

if($ID>0)
{
	$arFields["MAIN"]["ITEMS"][] = Array(
		"CODE" => "ID",
		"TYPE" => "LABEL",
		"LABEL" => "ID",
		"VALUE" => $str_ID,
	);
}

$arFields["MAIN"]["ITEMS"][] = Array(
	"CODE" => "ACTIVE",
	"TYPE" => "CHECKBOX",
	"LABEL" => Loc::getMessage("FIELD_ACTIVE"),
	"VALUE" => $str_ACTIVE,
);

$arFields["MAIN"]["ITEMS"][] = Array(
	"CODE" => "SORT",
	"TYPE" => "TEXT",
	"LABEL" => Loc::getMessage("FIELD_SORT"),
	"VALUE" => $str_SORT,
	"PARAMS" => Array(
		"SIZE" => "10",
		"MAXLENGTH" => "11",
	),
);

$arFields["MAIN"]["ITEMS"][] = Array(
	"CODE" => "PROCESSING_TYPE",
	"TYPE" => "SELECT",
	"LABEL" => Loc::getMessage("FIELD_PROCESSING_TYPE"),
	"VALUE" => $str_PROCESSING_TYPE,
	"ITEMS" => $listTypes,
	"REFRESH" => "Y",
);

if($str_PARAMS)
{
	$paramsArr = unserialize(base64_decode($str_PARAMS));
	if(!is_array($paramsArr))
		$paramsArr = Array();
}
else
{
	$paramsArr = array();
}

$arFields["PARAMS"]["LABEL"] = Loc::getMessage("GROUP_PARAMS");
if(!$ID>0 && !$COPY_ID > 0)
{
	$arFields["PARAMS"]["ITEMS"][] = Array(
		"CODE" => "PARAMS",
		"TYPE" => "LABEL",
		"LABEL" => Loc::getMessage("MESSAGE_ADD_BEFORE"),
		"DESCRIPTION" => Loc::getMessage("MESSAGE_ADD_BEFORE_DESCRIPTION"),
	);
}
else
{
	switch($str_PROCESSING_TYPE)
	{
		case("trim"):
		case("ltrim"):
		case("rtrim"):
			$arFields["PARAMS"]["ITEMS"][] = Array(
				"CODE" => "PARAMS[CHARACTER_MASK]",
				"TYPE" => "TEXT",
				"LABEL" => Loc::getMessage("FIELD_PARAMS_CHARACTER_MASK"),
				"DESCRIPTION" => Loc::getMessage("FIELD_PARAMS_CHARACTER_MASK_DESCRIPTION"),
				"VALUE" => $paramsArr["CHARACTER_MASK"],
			);
			break;
		case("strip_tags"):
			$arFields["PARAMS"]["ITEMS"][] = Array(
				"CODE" => "PARAMS[ALLOWABLE_TAGS]",
				"TYPE" => "TEXT",
				"LABEL" => Loc::getMessage("FIELD_PARAMS_ALLOWABLE_TAGS"),
				"VALUE" => $paramsArr["ALLOWABLE_TAGS"],
			);
			break;
		case("mb_strtolower"):
		case("mb_strtoupper"):
			$arFields["PARAMS"]["ITEMS"][] = Array(
				"CODE" => "PARAMS[ENCODING]",
				"TYPE" => "TEXT",
				"LABEL" => Loc::getMessage("FIELD_PARAMS_ENCODING"),
				"VALUE" => $paramsArr["ENCODING"],
			);
			break;
		case("mb_convert_case"):
			$arFields["PARAMS"]["ITEMS"][] = Array(
				"CODE" => "PARAMS[MODE]",
				"TYPE" => "SELECT",
				"LABEL" => Loc::getMessage("FIELD_PARAMS_MODE"),
				"VALUE" => $paramsArr["MODE"],
				"ITEMS" => $listParams["MODE"],
			);
			$arFields["PARAMS"]["ITEMS"][] = Array(
				"CODE" => "PARAMS[ENCODING]",
				"TYPE" => "SELECT",
				"LABEL" => Loc::getMessage("FIELD_PARAMS_ENCODING"),
				"VALUE" => $paramsArr["ENCODING"],
				"ITEMS" => $listParams["ENCODING"],
			);
			break;
		case("ucwords"):
			$arFields["PARAMS"]["ITEMS"][] = Array(
				"CODE" => "PARAMS[DELIMITERS]",
				"TYPE" => "TEXT",
				"LABEL" => Loc::getMessage("FIELD_PARAMS_DELIMITERS"),
				"VALUE" => $paramsArr["DELIMITERS"],
			);
			break;
		case("explode"):
			$arFields["PARAMS"]["ITEMS"][] = Array(
				"CODE" => "PARAMS[DELIMITER]",
				"TYPE" => "TEXT",
				"LABEL" => Loc::getMessage("FIELD_PARAMS_DELIMITER"),
				"VALUE" => $paramsArr["DELIMITER"],
			);
			$arFields["PARAMS"]["ITEMS"][] = Array(
				"CODE" => "PARAMS[LIMIT]",
				"TYPE" => "NUMBER",
				"LABEL" => Loc::getMessage("FIELD_PARAMS_LIMIT"),
				"VALUE" => $paramsArr["LIMIT"],
			);
			break;
		case("preg_split"):
			$arFields["PARAMS"]["ITEMS"][] = Array(
				"CODE" => "PARAMS[PATTERN]",
				"TYPE" => "TEXT",
				"LABEL" => Loc::getMessage("FIELD_PARAMS_PATTERN"),
				"VALUE" => $paramsArr["PATTERN"],
			);
			$arFields["PARAMS"]["ITEMS"][] = Array(
				"CODE" => "PARAMS[LIMIT]",
				"TYPE" => "NUMBER",
				"LABEL" => Loc::getMessage("FIELD_PARAMS_LIMIT"),
				"VALUE" => $paramsArr["LIMIT"],
				"PARAMS" => Array(
					"MIN" => -1,
				),
			);
			$arFields["PARAMS"]["ITEMS"][] = Array(
				"CODE" => "PARAMS[FLAGS][]",
				"TYPE" => "SELECT",
				"LABEL" => Loc::getMessage("FIELD_PARAMS_FLAGS"),
				"VALUE" => $paramsArr["FLAGS"],
				"ITEMS" => $listParams["FLAGS"],
				"PARAMS" => Array(
					"MULTIPLE" => "Y",
				),
			);
			break;
		case("money_format"):
			$arFields["PARAMS"]["ITEMS"][] = Array(
				"CODE" => "PARAMS[FORMAT]",
				"TYPE" => "TEXT",
				"LABEL" => Loc::getMessage("FIELD_PARAMS_FORMAT"),
				"VALUE" => $paramsArr["FORMAT"],
			);
			break;
		case("number_format"):
			$arFields["PARAMS"]["ITEMS"][] = Array(
				"CODE" => "PARAMS[DECIMALS]",
				"TYPE" => "NUMBER",
				"LABEL" => Loc::getMessage("FIELD_PARAMS_DECIMALS"),
				"VALUE" => $paramsArr["DECIMALS"],
				"PARAMS" => Array(
					"MIN" => 0,
				),
			);
			$arFields["PARAMS"]["ITEMS"][] = Array(
				"CODE" => "PARAMS[DEC_POINT]",
				"TYPE" => "TEXT",
				"LABEL" => Loc::getMessage("FIELD_PARAMS_DEC_POINT"),
				"VALUE" => $paramsArr["DEC_POINT"],
			);
			$arFields["PARAMS"]["ITEMS"][] = Array(
				"CODE" => "PARAMS[THOUSANDS_SEP]",
				"TYPE" => "TEXT",
				"LABEL" => Loc::getMessage("FIELD_PARAMS_THOUSANDS_SEP"),
				"VALUE" => $paramsArr["THOUSANDS_SEP"],
			);
			break;
		case("str_replace"):
			$arFields["PARAMS"]["ITEMS"][] = Array(
				"CODE" => "PARAMS[SEARCH]",
				"TYPE" => "TEXT",
				"LABEL" => Loc::getMessage("FIELD_PARAMS_SEARCH"),
				"VALUE" => $paramsArr["SEARCH"],
			);
			$arFields["PARAMS"]["ITEMS"][] = Array(
				"CODE" => "PARAMS[REPLACE]",
				"TYPE" => "TEXT",
				"LABEL" => Loc::getMessage("FIELD_PARAMS_REPLACE"),
				"VALUE" => $paramsArr["REPLACE"],
			);
			break;
		case("preg_replace"):
			$arFields["PARAMS"]["ITEMS"][] = Array(
				"CODE" => "PARAMS[PATTERN]",
				"TYPE" => "TEXT",
				"LABEL" => Loc::getMessage("FIELD_PARAMS_PATTERN"),
				"VALUE" => $paramsArr["PATTERN"],
			);
			$arFields["PARAMS"]["ITEMS"][] = Array(
				"CODE" => "PARAMS[REPLACEMENT]",
				"TYPE" => "TEXT",
				"LABEL" => Loc::getMessage("FIELD_PARAMS_REPLACE"),
				"VALUE" => $paramsArr["REPLACEMENT"],
				"DESCRIPTION" => Loc::getMessage("FIELD_PARAMS_REPLACEMENT_DESCRIPTION"),
			);
			break;
		case("array_slice"):
			$arFields["PARAMS"]["ITEMS"][] = Array(
				"CODE" => "PARAMS[OFFSET]",
				"TYPE" => "NUMBER",
				"LABEL" => Loc::getMessage("FIELD_PARAMS_OFFSET"),
				"VALUE" => $paramsArr["OFFSET"],
			);
			$arFields["PARAMS"]["ITEMS"][] = Array(
				"CODE" => "PARAMS[LENGTH]",
				"TYPE" => "NUMBER",
				"LABEL" => Loc::getMessage("FIELD_PARAMS_LENGTH"),
				"VALUE" => $paramsArr["LENGTH"],
			);
			$arFields["PARAMS"]["ITEMS"][] = Array(
				"CODE" => "PARAMS[PRESERVE_KEYS]",
				"TYPE" => "CHECKBOX",
				"LABEL" => Loc::getMessage("FIELD_PARAMS_PRESERVE_KEYS"),
				"VALUE" => $paramsArr["PRESERVE_KEYS"],
			);
			break;
		case("strstr"):
		case("stristr"):
			$arFields["PARAMS"]["ITEMS"][] = Array(
				"CODE" => "PARAMS[NEEDLE]",
				"TYPE" => "TEXT",
				"LABEL" => Loc::getMessage("FIELD_PARAMS_NEEDLE"),
				"VALUE" => $paramsArr["NEEDLE"],
			);
			$arFields["PARAMS"]["ITEMS"][] = Array(
				"CODE" => "PARAMS[BEFORE_NEEDLE]",
				"TYPE" => "CHECKBOX",
				"LABEL" => Loc::getMessage("FIELD_PARAMS_BEFORE_NEEDLE"),
				"VALUE" => $paramsArr["BEFORE_NEEDLE"],
			);
			break;
		case("strrchr"):
			$arFields["PARAMS"]["ITEMS"][] = Array(
				"CODE" => "PARAMS[NEEDLE]",
				"TYPE" => "TEXT",
				"LABEL" => Loc::getMessage("FIELD_PARAMS_NEEDLE"),
				"VALUE" => $paramsArr["NEEDLE"],
			);
			break;
		case("str_pad"):
			$arFields["PARAMS"]["ITEMS"][] = Array(
				"CODE" => "PARAMS[PAD_LENGTH]",
				"TYPE" => "NUMBER",
				"REQUIRED" => "Y",
				"LABEL" => Loc::getMessage("FIELD_PARAMS_PAD_LENGTH"),
				"DESCRIPTION" => Loc::getMessage("FIELD_PARAMS_PAD_LENGTH_NOTE"),
				"VALUE" => $paramsArr["PAD_LENGTH"],
				"PARAMS" => Array(
					"MIN" => 0
				),
			);
			$arFields["PARAMS"]["ITEMS"][] = Array(
				"CODE" => "PARAMS[PAD_STRING]",
				"TYPE" => "TEXT",
				"REQUIRED" => "Y",
				"LABEL" => Loc::getMessage("FIELD_PARAMS_PAD_STRING"),
				"VALUE" => $paramsArr["PAD_STRING"],
				"PARAMS" => Array(
					"SIZE" => 10
				),
			);
			$arFields["PARAMS"]["ITEMS"][] = Array(
				"CODE" => "PARAMS[PAD_TYPE]",
				"TYPE" => "SELECT",
				"LABEL" => Loc::getMessage("FIELD_PARAMS_PAD_TYPE"),
				"VALUE" => $paramsArr["PAD_TYPE"]?$paramsArr["PAD_TYPE"]:"STR_PAD_RIGHT",
				"ITEMS" => $listParams["PAD_TYPE"],
			);
			break;
		case("arithmetic_addition"):
		case("arithmetic_subtraction"):
		case("arithmetic_multiplication"):
		case("arithmetic_division"):
		case("arithmetic_modulo"):
		case("arithmetic_exponentiation"):
			$arFields["PARAMS"]["ITEMS"][] = Array(
				"CODE" => "PARAMS[VALUE]",
				"TYPE" => "TEXT",
				"LABEL" => Loc::getMessage("FIELD_PARAMS_".strtoupper($str_PROCESSING_TYPE)),
				"VALUE" => $paramsArr["VALUE"],
				"PARAMS" => Array(
					"SIZE" => 10
				),
			);
			break;
		case("translit"):
			$arFields["PARAMS"]["ITEMS"][] = Array(
				"CODE" => "PARAMS[LANG]",
				"TYPE" => "SELECT",
				"LABEL" => Loc::getMessage("FIELD_PARAMS_LANG"),
				"VALUE" => $paramsArr["LANG"],
				"ITEMS" => $listParams["LANG"],
			);
			$arFields["PARAMS"]["ITEMS"][] = Array(
				"CODE" => "PARAMS[MAX_LEN]",
				"TYPE" => "TEXT",
				"LABEL" => Loc::getMessage("FIELD_PARAMS_LENGTH"),
				"VALUE" => $paramsArr["MAX_LEN"]?$paramsArr["MAX_LEN"]:100,
				"PARAMS" => Array(
					"SIZE" => 5
				),
			);
			$arFields["PARAMS"]["ITEMS"][] = Array(
				"CODE" => "PARAMS[CHANGE_CASE]",
				"TYPE" => "SELECT",
				"LABEL" => Loc::getMessage("FIELD_PARAMS_CHANGE_CASE"),
				"VALUE" => $paramsArr["CHANGE_CASE"],
				"ITEMS" => $listParams["CHANGE_CASE"],
			);
			$arFields["PARAMS"]["ITEMS"][] = Array(
				"CODE" => "PARAMS[REPLACE_SPACE]",
				"TYPE" => "TEXT",
				"LABEL" => Loc::getMessage("FIELD_PARAMS_REPLACE_SPACE"),
				"VALUE" => $paramsArr["REPLACE_SPACE"]?$paramsArr["REPLACE_SPACE"]:"_",
				"PARAMS" => Array(
					"SIZE" => 5
				),
			);
			$arFields["PARAMS"]["ITEMS"][] = Array(
				"CODE" => "PARAMS[REPLACE_OTHER]",
				"TYPE" => "TEXT",
				"LABEL" => Loc::getMessage("FIELD_PARAMS_REPLACE_OTHER"),
				"VALUE" => $paramsArr["REPLACE_OTHER"]?$paramsArr["REPLACE_OTHER"]:"_",
				"PARAMS" => Array(
					"SIZE" => 5
				),
			);
			$arFields["PARAMS"]["ITEMS"][] = Array(
				"CODE" => "PARAMS[DELETE_REPEAT_REPLACE]",
				"TYPE" => "CHECKBOX",
				"LABEL" => Loc::getMessage("FIELD_PARAMS_DELETE_REPEAT_REPLACE"),
				"VALUE" => $paramsArr["DELETE_REPEAT_REPLACE"]?$paramsArr["DELETE_REPEAT_REPLACE"]:"Y",
			);
			$arFields["PARAMS"]["ITEMS"][] = Array(
				"CODE" => "PARAMS[SAFE_CHARS]",
				"TYPE" => "TEXT",
				"LABEL" => Loc::getMessage("FIELD_PARAMS_SAFE_CHARS"),
				"VALUE" => $paramsArr["SAFE_CHARS"],
				"PARAMS" => Array(
					"SIZE" => 20
				),
			);
			break;
		case("eval"):
			$arFields["PARAMS"]["ITEMS"][] = Array(
				"CODE" => "PARAMS[CODE]",
				"ID" => "eval_code",
				"TYPE" => "CODE_EDITOR",
				//"LABEL" => Loc::getMessage("FIELD_PARAMS_CODE"),
				"DESCRIPTION" => Loc::getMessage("FIELD_PARAMS_CODE_NOTE"),
				"VALUE" => $paramsArr["CODE"]?$paramsArr["CODE"]:"\$field = \$field;",
				"PARAMS" => Array(
					"HEIGHT" => 350,
					"CODE_EDITOR" => true,
					"SYNTAX" => 'php',
				),
			);
			$arFields["PARAMS"]["ITEMS"][] = Array(
				"TYPE" => "NOTE",
				"VALUE" => Loc::getMessage("FIELD_PARAMS_CODE_ATTENTION"),
				"PARAMS" => Array(
					"TYPE" => "warning",
					"ICON" => ''
				),
			);
			break;
		default:
			$arFields["PARAMS"]["ITEMS"][] = Array(
				"CODE" => "PARAMS",
				"TYPE" => "LABEL",
				"LABEL" => Loc::getMessage("MESSAGE_NO_PARAMS"),
			);
			break;
	}
}

$aMenu = array(
	array(
		"TEXT" => Loc::getMessage("ELEMENTS_LIST"),
		"LINK" => "data.import_processing_settings.php?lang=".LANGUAGE_ID,
		"ICON" => "btn_list"
	)
);

if($ID>0)
{
	$aMenu[] = array("SEPARATOR"=>"Y");
	
	if($moduleAccessLevel=="W")
	{
		$aMenu[] = array(
			"TEXT"  => Loc::getMessage("BTN_ACTIONS"),
			"ICON"  => "btn_new",
			"MENU"  => Array(
				array(
					"TEXT" => Loc::getMessage("ADD_ELEMENT"),
					"LINK" => "data.import_processing_setting_edit.php?lang=".LANGUAGE_ID,
					"ICON" => "edit"
				),
				array(
					"TEXT" => Loc::getMessage("COPY_ELEMENT"),
					"LINK" => "data.import_processing_setting_edit.php?COPY_ID={$ID}&lang=".LANGUAGE_ID,
					"ICON" => "copy"
				),
				array(
					"TEXT" => Loc::getMessage("DEL_ELEMENT"),
					"LINK" => "javascript:if(confirm('".GetMessageJS("DEL_ELEMENT_CONFIRM")."')) window.location='/bitrix/admin/data.import_processing_settings.php?ID=".$ID."&action=delete&lang=".LANGUAGE_ID."&".bitrix_sessid_get()."';",
					"ICON" => "delete"
				),
			),
		);
	}
}

$context = new CAdminContextMenu($aMenu);
$context->Show();
?>
<?CAdminMessage::ShowOldStyleError($strWarning);?>
<form method="POST" id="form" name="form" action="data.import_processing_setting_edit.php?lang=<?echo LANG?>">
<?=bitrix_sessid_post()?>
<input type="hidden" name="Update" value="Y">
<?
if($ID>0) {
?>
<input type="hidden" name="ID" value="<?echo $ID?>">
<? } ?>
<?if(strlen($back_url)>0):?><input type="hidden" name="back_url" value="<?=htmlspecialcharsbx($back_url)?>"><?endif?>
<?
$tabControl->Begin();
$tabControl->BeginNextTab();

CDataCoreFunctions::ShowFormFields($arFields);

$tabControl->Buttons(
	/*array(
		"disabled"=>($moduleAccessLevel<"W"),
		"back_url"=>$back_url,
	)*/
);
?>
<button 
title="<?=Loc::getMessage("SAVE_TITLE")?>" 
class="ui-btn ui-btn-success" 
type="submit" 
value="Y" 
name="save"
<?=$moduleAccessLevel>="W"?"":" disabled"?>
>
	<?=Loc::getMessage("SAVE")?>
</button>
<button 
title="<?=Loc::getMessage("APPLY_TITLE")?>" 
class="ui-btn ui-btn-primary" 
type="submit" 
value="Y" 
name="apply"
<?=$moduleAccessLevel>="W"?"":" disabled"?>
>
	<?=Loc::getMessage("APPLY")?>
</button>
<button 
title="<?=Loc::getMessage("CANCEL_TITLE")?>" 
class="ui-btn ui-btn-link" 
type="button" 
name="cancel" 
onclick="top.window.location='<?=$back_url?>'"
>
	<?=Loc::getMessage("CANCEL")?>
</button>
<?
$tabControl->End();
?>
</form>
<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>