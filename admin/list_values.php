<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/data.import/prolog.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/data.import/include.php");

use Bitrix\Main\Localization\Loc;

$module_id = 'data.import';
$moduleAccessLevel = $APPLICATION->GetGroupRight($module_id);

if ($moduleAccessLevel == "D")
    $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));

@set_time_limit(0);

$arErrors = [];
$arMessages = [];

if($_SERVER["REQUEST_METHOD"] == "POST" && ($_REQUEST["LoadProperties"]=="Y" || $_REQUEST["Import"]=="Y" || $_REQUEST["LoadValues"]=="Y"))
{
	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_js.php");
	
	define("NO_KEEP_STATISTIC", true);
	define("NOT_CHECK_PERMISSIONS",true);
	define("BX_CAT_CRON", true);
	define('NO_AGENT_CHECK', true);
	
	if($_REQUEST["Import"]=="Y")
	{
		if(array_key_exists("NS", $_POST) && is_array($_POST["NS"]))
		{
			$NS = $_POST["NS"];
		}
		else
		{
			if(!$IBLOCK_ID)
			{
				$arErrors[] = Loc::getMessage("DATA_IMPORT_LIST_VALUES_ERROR_IBLOCK_ID")."<br />";
			}
			elseif(!$PROPERTY_ID)
			{
				$arErrors[] = Loc::getMessage("DATA_IMPORT_LIST_VALUES_ERROR_PROPERTY_ID")."<br />";
			}
			elseif(!$LIST_VALUES)
			{
				$arErrors[] = Loc::getMessage("DATA_IMPORT_LIST_VALUES_ERROR_LIST_VALUES")."<br />";
			}
			else
			{
				$ITEMS = explode("\r\n", $LIST_VALUES);
				if(count((array)$ITEMS) == 0)
				{
					$arErrors[] = Loc::getMessage("DATA_IMPORT_LIST_VALUES_ERROR_LIST_VALUES")."<br />";
				}
				$NS = array(
					"START_TIME" => microtime(true),
					"ID" => [],
					"CURRENT" => 0,
					"ITEMS" => $ITEMS,
					"TOTAL" => count((array)$ITEMS),
					"PARAMS" => [
						'PROPERTIES_TRANSLATE_XML_ID' => 'Y',
						'PROPERTIES_ADD_LIST_ENUM' => 'Y',
						'PROPERTIES_UPDATE_LIST_ENUM' => ($UPDATE_VALUES == 'Y' ? 'Y' : 'N'),
					],
				);
			}
		}
		
		if(!check_bitrix_sessid())
		{
			$arErrors[] = Loc::getMessage("DATA_IMPORT_LIST_VALUES_ERROR_SESSION_EXPIRED")."<br />";
		}
		elseif($moduleAccessLevel < 'T')
		{
			$arErrors[] = Loc::getMessage("ACCESS_DENIED")."<br />";
		}
		?>
		<script>
			CloseWaitWindow();
		</script>
		<?
		if(count($arErrors) == 0)
		{
			$scriptExecutionTime = microtime(true);
			
			$currentItem = $NS["CURRENT"];
			
			if($SET_SORT == 'Y')
				$sort = $NS["CURRENT"] + 1;
			else
				$sort = false;
			
			$value = $NS["ITEMS"][$currentItem];
			if($CASE_FIRST == 'U')
				$value = CDataImportUtils::mb_ucfirst($value);
			elseif($CASE_FIRST == 'L')
				$value = CDataImportUtils::mb_lcfirst($value);
			
			$ENUM_ID = CDataImportProperty::GetPropertyIdByValue($IBLOCK_ID, $PROPERTY_ID, $value, $NS["PARAMS"], $sort);
			if($ENUM_ID)
				$NS['ID'][] = $ENUM_ID;
			
			$NS["CURRENT"]++;
			
			if($NS["CURRENT"] < $NS["TOTAL"])
			{
				$progressTotal = intval($NS["TOTAL"]);
				$progressValue = intval($NS["CURRENT"]);
				
				CDataCoreFunctions::showProgressBar(
					'default', 
					Loc::getMessage("DATA_IMPORT_LIST_VALUES_PROGRESS", array(
						"#DONE#" => number_format(intVal($NS["CURRENT"]), 0, "", " "),
						"#TOTAL#"=> number_format(intVal($NS["TOTAL"]), 0, "", " "),
					)),
					Loc::getMessage("DATA_IMPORT_LIST_VALUES_PROGRESS_STEP", array(
						"#TIME#"=> round(microtime(true) - $scriptExecutionTime, 2),
					)),
					$progressValue,
					$progressTotal
				);
				
				echo '<script>DoNext('.CUtil::PhpToJSObject(array("NS"=>$NS)).');</script>';
			}
			else
			{
				$TIME_MIN = round((microtime(true) - $NS["START_TIME"])/60, 0);
				$TIME_SEC = round((microtime(true) - $NS["START_TIME"] - ($TIME_MIN*60)), 2);
				
				$arMessages[] = Loc::getMessage("DATA_IMPORT_LIST_VALUES_PROGRESS_FINISH", array(
					"#TOTAL#"=> number_format(intVal($NS["TOTAL"]), 0, "", " "),
					"#TIME_MIN#"=> $TIME_MIN,
					"#TIME_SEC#"=> abs($TIME_SEC),
				));
				
				if($DELETE_OTHER_VALUES == 'Y')
					CDataImportProperty::DeleteValues($IBLOCK_ID, $PROPERTY_ID, $NS['ID']);
				
				echo '<script>EndImport();</script>';
			}
		}
		else
		{
			echo '<script>EndImport();</script>';
		}
	
		if(is_array($arErrors))
		{
			foreach($arErrors as $strMessage)
			{
				CDataCoreFunctions::showAlertBegin('danger', 'danger');
				echo $strMessage;
				CDataCoreFunctions::showAlertEnd();
			}
		}
	
		if(is_array($arMessages))
		{
			foreach($arMessages as $strMessage)
			{
				CDataCoreFunctions::showAlertBegin('success', 'info');
				echo $strMessage;
				CDataCoreFunctions::showAlertEnd();
			}
		}
	}
	elseif($_REQUEST["LoadProperties"]=="Y" && check_bitrix_sessid())
	{
		if(CIBlock::GetPermission($IBLOCK_ID) >= 'W')
		{
			$queryList = CIBlockProperty::GetList(Array("sort"=>"asc", "name"=>"asc"), Array("IBLOCK_ID" => $IBLOCK_ID, "PROPERTY_TYPE" => "L"));
			$listProperties = [];
			while($property = $queryList->GetNext())
			{
				$listProperties[$property['ID']] = htmlspecialcharsbx($property["NAME"]).' ['.$property['ID'].']';
			}
		}
		
		if(count($listProperties) > 0)
		{
			echo \Bitrix\Main\Web\Json::encode($listProperties);
		}
		else
		{
			echo \Bitrix\Main\Web\Json::encode(Loc::getMessage('DATA_IMPORT_LIST_VALUES_PROPERTY_ID_EMPTY'));
		}
	}
	elseif($_REQUEST["LoadValues"]=="Y" && check_bitrix_sessid())
	{
		if(CIBlock::GetPermission($IBLOCK_ID) >= 'W')
		{
			$arSort = ["SORT"=>"ASC"];
			$arFilter = ["IBLOCK_ID"=>$IBLOCK_ID, "PROPERTY_ID"=>$PROPERTY_ID];
			$property_enums = CIBlockPropertyEnum::GetList($arSort, $arFilter);
			$listValues = [];
			while($enum_fields = $property_enums->GetNext())
			{
				$listValues[] = $enum_fields['VALUE'];
			}
		}
		
		if(count($listValues) > 0)
		{
			echo \Bitrix\Main\Web\Json::encode($listValues);
		}
		else
		{
			echo \Bitrix\Main\Web\Json::encode(Loc::getMessage('DATA_IMPORT_LIST_VALUES_LOAD_VALUES_EMPTY'));
		}
	}

	require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin_js.php");
}

$APPLICATION->SetTitle( Loc::getMessage("DATA_IMPORT_LIST_VALUES_PAGE_TITLE") );
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
?>
<div id="import_result_div"></div>
<?
$aTabs = array(
	array(
		"DIV" => "FORM",
		"TAB" => Loc::getMessage("DATA_IMPORT_LIST_VALUES_TAB_NAME"),
		"ICON" => "",
		"TITLE" => Loc::getMessage("DATA_IMPORT_LIST_VALUES_TAB_DESCRIPTION")
	),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs);
?>
<script language="JavaScript" type="text/javascript">
$(document).ready(function(){
	$('#IBLOCK_TYPE_ID').on('change', function(){
		$('select#property_id').empty();
		checkStartButton();
	})
	$('#IBLOCK_ID').on('change', function(){
		loadProperties($(this).val());
	})
	$('#property_id').on('change', function(){
		$('textarea#list_values').val('');
		console.log('1');
		checkStartButton();
	})
	$('#list_values').on('input', function(){
		checkStartButton();
	})
	$('#load_values').on('click', function(){
		var IBLOCK_ID = $('#IBLOCK_ID').val();
		var PROPERTY_ID = $('#property_id').val();
		loadValues(IBLOCK_ID, PROPERTY_ID);
	})
	function checkStartButton()
	{
		var list_values = $('#list_values').val();
		var IBLOCK_ID = $('#IBLOCK_ID').val();
		var property_id = $('#property_id').val();
		
		if(list_values != '' && property_id > 0)
			$('#start_button').removeAttr('disabled');
		else
			$('#start_button').attr('disabled', 'disabled');
		
		if(IBLOCK_ID > 0)
			$('#property_id').removeAttr('disabled');
		else
			$('#property_id').attr('disabled', 'disabled');
		
		if(property_id > 0)
			$('#load_values').removeAttr('disabled');
		else
			$('#load_values').attr('disabled', 'disabled');
	}
	function loadValues(IBLOCK_ID, PROPERTY_ID)
	{
		if(IBLOCK_ID>0 && PROPERTY_ID>0)
		{
			ShowWaitWindow();
			
			$.ajax({
				url: 'data.import_list_values.php',
				method: 'post',
				dataType: 'json',
				data: {
					lang: '<?echo LANG?>',
					sessid: '<?echo bitrix_sessid()?>',
					IBLOCK_ID: IBLOCK_ID,
					PROPERTY_ID: PROPERTY_ID,
					LoadValues: 'Y',
				},
				success: function(data){
					$('textarea#list_values').empty();
					if(typeof data == 'object')
					{
						var listValuesNew = '';
						var index = 1;
						for(var key in data) {
							if(data[key] != '')
							{
								listValuesNew = listValuesNew + data[key];
								if(index < data.length)
									listValuesNew = listValuesNew + '\r\n';
							}
							index++;
						}
						if(listValuesNew != '')
						{
							$('textarea#list_values').val(listValuesNew);
						}
					}
					else
					{
						BX.UI.Notification.Center.notify({
							content: data,
							position: "top-right"
						});
					}
					checkStartButton();
					CloseWaitWindow();
				},
			});
		}
	}
	function loadProperties(IBLOCK_ID)
	{
		if(IBLOCK_ID>0)
		{
			ShowWaitWindow();
			
			$.ajax({
				url: 'data.import_list_values.php',
				method: 'post',
				dataType: 'json',
				data: {
					lang: '<?echo LANG?>',
					sessid: '<?echo bitrix_sessid()?>',
					IBLOCK_ID: IBLOCK_ID,
					LoadProperties: 'Y',
				},
				success: function(data){
					$('select#property_id').empty();
					if(typeof data == 'object')
					{
						$('select#property_id, textarea#list_values').removeAttr('disabled');
						for(var key in data) {
							$('select#property_id').append($('<option>', {
								value: key,
								text: data[key]
							}));
						}
					}
					else
					{
						$('select#property_id, textarea#list_values').attr('disabled', 'disabled');
						BX.UI.Notification.Center.notify({
							content: data,
							position: "top-right"
						});
					}
					checkStartButton();
					CloseWaitWindow();
				},
			});
		}
	}
});
var running = false;
function DoNext(NS)
{
	var IBLOCK_ID = $('#IBLOCK_ID').val();
	var PROPERTY_ID = $('#property_id').val();
	var CASE_FIRST = $('#case_first').val();
	var SET_SORT = $('#set_sort').val();
	var UPDATE_VALUES = $('#update_values').val();
	var DELETE_OTHER_VALUES = $('#delete_other_values').val();
	var LIST_VALUES = $('#list_values').serialize();
	
	var queryString = 'Import=Y'
		+ '&lang=<?echo LANG?>'
		+ '&<?echo bitrix_sessid_get()?>'
		+ '&IBLOCK_ID=' + IBLOCK_ID
		+ '&PROPERTY_ID=' + PROPERTY_ID
		+ '&CASE_FIRST=' + CASE_FIRST
		+ '&SET_SORT=' + SET_SORT
		+ '&UPDATE_VALUES=' + UPDATE_VALUES
		+ '&DELETE_OTHER_VALUES=' + DELETE_OTHER_VALUES
		+ '&' + LIST_VALUES;
	;

	if(running)
	{
		ShowWaitWindow();
		BX.ajax.post(
			'data.import_list_values.php?'+queryString,
			NS,
			function(result){
				document.getElementById('import_result_div').innerHTML = result;
			}
		);
	}
}
function StartImport()
{
	if(running == false)
	{
		running = true;
		
		if($("#start_button").is(":visible") && $("#start_button").attr('disabled') != "disabled")
		{
			$("#start_button").addClass('ui-btn-wait').attr("disabled", "disabled");
		}

		if($("#stop_button").is(":visible") && $("#stop_button").attr('disabled') == "disabled")
		{
			$("#stop_button").removeAttr("disabled");
		}
			
		DoNext();
	}
}
function EndImport()
{
	running = false;
	
	if($("#start_button").is(":visible"))
	{
		$("#start_button").removeClass('ui-btn-wait').removeAttr("disabled");
	}

	if($("#stop_button").is(":visible") && $("#stop_button").attr('disabled') != "disabled")
	{
		$("#stop_button").attr("disabled", "disabled");
	}
	
	window.history.pushState("data_import_list_values", "", "data.import_list_values.php?lang=<?=LANG?>" );
}
BX.ready(function(){
	BX.UI.Hint.init(BX('data_import_manually'));
});
</script>
<form id="data_import_manually" method="POST" action="<?=$APPLICATION->GetCurPage()?>?lang=<?echo LANG?>" ENCTYPE="multipart/form-data" name="data_import_manually">
<?
$tabControl->Begin();
$tabControl->BeginNextTab();

$arFields["IBLOCK"]["ITEMS"][] = Array(
	"CODE" => "IBLOCK_ID",
	"TYPE" => "IBLOCK",
	"REQUIRED" => "Y",
	"LABEL" => Loc::getMessage("DATA_IMPORT_LIST_VALUES_IBLOCK_ID"),
	"VALUE" => $IBLOCK_ID,
	"PARAMS" => Array(
		"MIN_PERMISSION" => "W"
	),
);
$arFields["IBLOCK"]["ITEMS"][] = Array(
	"CODE" => "PROPERTY_ID",
	"TYPE" => "PROPERTY",
	"REQUIRED" => "Y",
	"DISABLED" => $IBLOCK_ID ? "N" : "Y",
	"LABEL" => Loc::getMessage("DATA_IMPORT_LIST_VALUES_PROPERTY_ID"),
	"DESCRIPTION" => Loc::getMessage("DATA_IMPORT_LIST_VALUES_PROPERTY_ID_EMPTY"),
	"PARAMS" => Array(
		"IBLOCK_ID" => $IBLOCK_ID, 
		"PROPERTY_TYPE" => "L"
	),
	"VALUE" => $PROPERTY_ID,
);
$arFields["IBLOCK"]["ITEMS"][] = Array(
	"CODE" => "LOAD_VALUES",
	"TYPE" => "BUTTON",
	"DISABLED" => $PROPERTY_ID ? "N" : "Y",
	"LABEL" => Loc::getMessage("DATA_IMPORT_LIST_VALUES_LOAD_VALUES"),
	"VALUE" => 'javascript:;',
	"PARAMS" => Array(
		"TEXT" => Loc::getMessage("DATA_IMPORT_LIST_VALUES_LOAD_VALUES_TEXT"),
		"TARGET" => "_self",
	),
);
$arFields["IBLOCK"]["ITEMS"][] = Array(
	"CODE" => "LIST_VALUES",
	"TYPE" => "TEXTAREA",
	"REQUIRED" => "Y",
	"DISABLED" => $PROPERTY_ID ? "N" : "Y",
	"LABEL" => Loc::getMessage("DATA_IMPORT_LIST_VALUES_LIST_VALUES"),
	"PARAMS" => Array(
		"PLACEHOLDER" => Loc::getMessage("DATA_IMPORT_LIST_VALUES_LIST_VALUES_PLACEHOLDER"),
		"COLS" => 60,
		"ROWS" => 10,
	),
	"VALUE" => $LIST_VALUES,
);
$arFields["PARAMS"]["LABEL"] = Loc::getMessage("DATA_IMPORT_LIST_VALUES_OPTIONS");
$arFields["PARAMS"]["ITEMS"][] = Array(
	"CODE" => "CASE_FIRST",
	"TYPE" => "SELECT",
	"LABEL" => Loc::getMessage("DATA_IMPORT_LIST_VALUES_CASE_FIRST"),
	"ITEMS" => [
		'' => Loc::getMessage("DATA_IMPORT_LIST_VALUES_CASE_FIRST_NO"),
		'U' => Loc::getMessage("DATA_IMPORT_LIST_VALUES_CASE_FIRST_U"),
		'L' => Loc::getMessage("DATA_IMPORT_LIST_VALUES_CASE_FIRST_L"),
	],
	"PARAMS" => Array(
		"PLACEHOLDER" => Loc::getMessage("DATA_IMPORT_LIST_VALUES_PROPERTY_ID_EMPTY"),
	),
	"VALUE" => $CASE_FIRST,
);
$arFields["PARAMS"]["ITEMS"][] = Array(
	"CODE" => "SET_SORT",
	"TYPE" => "CHECKBOX",
	"LABEL" => Loc::getMessage("DATA_IMPORT_LIST_VALUES_SET_SORT"),
	"DESCRIPTION" => Loc::getMessage("DATA_IMPORT_LIST_VALUES_SET_SORT_NOTE"),
	"VALUE" => $SET_SORT ? $SET_SORT : "N",
);
$arFields["PARAMS"]["ITEMS"][] = Array(
	"CODE" => "UPDATE_VALUES",
	"TYPE" => "CHECKBOX",
	"LABEL" => Loc::getMessage("DATA_IMPORT_LIST_VALUES_UPDATE_VALUES"),
	"DESCRIPTION" => Loc::getMessage("DATA_IMPORT_LIST_VALUES_UPDATE_VALUES_NOTE"),
	"VALUE" => $UPDATE_VALUES ? $UPDATE_VALUES : "N",
);
$arFields["PARAMS"]["ITEMS"][] = Array(
	"CODE" => "DELETE_OTHER_VALUES",
	"TYPE" => "CHECKBOX",
	"LABEL" => Loc::getMessage("DATA_IMPORT_LIST_VALUES_DELETE_OTHER_VALUES"),
	"DESCRIPTION" => Loc::getMessage("DATA_IMPORT_LIST_VALUES_DELETE_OTHER_VALUES_NOTE"),
	"VALUE" => $DELETE_OTHER_VALUES ? $DELETE_OTHER_VALUES : "N",
);

CDataCoreFunctions::ShowFormFields($arFields);

$tabControl->Buttons();
?>
	<button type="button" id="start_button" value="Y" OnClick="StartImport();" class="ui-btn ui-btn-success ui-btn-icon-start" disabled>
		<?echo Loc::getMessage("DATA_IMPORT_LIST_VALUES_START")?>
	</button>
	<button class="ui-btn ui-btn ui-btn-icon-stop" type="button" id="stop_button" value="Y" OnClick="EndImport();" disabled>
		<?echo Loc::getMessage("DATA_IMPORT_LIST_VALUES_STOP")?>
	</button>
<?
$tabControl->End();
?>
<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>