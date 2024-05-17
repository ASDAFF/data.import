<?
define("NO_KEEP_STATISTIC", true);
define("STOP_STATISTICS", true);
define("NO_AGENT_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS",true);
define("BX_CAT_CRON", true);
define('NO_AGENT_CHECK', true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/webprostor.import/prolog.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/webprostor.import/include.php");

use Bitrix\Main\Localization\Loc;

$module_id = 'webprostor.import';
$moduleAccessLevel = $APPLICATION->GetGroupRight($module_id);

if ($moduleAccessLevel == "D")
    $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));

$cData = new CWebprostorImportPlan;

@set_time_limit(0);

$arErrors = [];
$arMessages = [];

if($_SERVER["REQUEST_METHOD"] == "POST" && $_REQUEST["Import"]=="Y")
{
	
	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_js.php");
	
	if(array_key_exists("NS", $_POST) && is_array($_POST["NS"]))
	{
		$NS = $_POST["NS"];
	}
	else
	{
		if(!$PLAN_ID)
		{
			$arErrors[] = Loc::getMessage("WEBPROSTOR_IMPORT_PLAN_ID_ERROR")."<br />";
		}
		else
		{
			$planRes = $cData->GetByID($PLAN_ID);
			$planParams = $planRes->Fetch();
			
			if($planParams['SHOW_IN_MANUALLY'] == 'N' && !$USER->IsAdmin())
			{
				$arErrors[] = Loc::getMessage("WEBPROSTOR_IMPORT_PLAN_ID_SHOW_IN_MANUALLY_ERROR")."<br />";
				unset($planParams);
			}
			else
			{
				if($LOAD_FILES == "Y")
					CWebprostorImport::Load($PLAN_ID);
				
				if($UPLOAD_FILE != '' && is_string($UPLOAD_FILE))
				{
					$IMPORT_FILE = $_SERVER["DOCUMENT_ROOT"].$UPLOAD_FILE;
				}
				else
				{
					$IMPORT_FILE = $_SERVER["DOCUMENT_ROOT"].$planParams["IMPORT_FILE"];
				}
				
				$CConnection = new CWebprostorImportPlanConnections;
				$connectionsRes = $CConnection->GetList(Array("SORT" => "ASC"), Array("PLAN_ID" => $PLAN_ID, 'ACTIVE' => 'Y'));
				$connections_count = $connectionsRes->SelectedRowsCount();
				
				$fileInfo = pathinfo($IMPORT_FILE);
			}
			
			if($planParams)
			{
				if(
					strtolower($planParams["IMPORT_FORMAT"]) != strtolower($fileInfo["extension"]) && 
					!(strtolower($planParams["IMPORT_FORMAT"]) == "xml" && 
					$fileInfo["extension"] == "yml") && 
					$planParams["IMPORT_FORMAT"] != 'JSON'
				)
				{
					$arErrors[] = Loc::getMessage("WEBPROSTOR_IMPORT_INCORRECT_IMPORT_FORMAT")."<br />";
				}
				elseif(!is_file($IMPORT_FILE) && $planParams["IMPORT_FORMAT"] != 'JSON')
				{
					$arErrors[] = Loc::getMessage("WEBPROSTOR_IMPORT_IMPORT_FILE_ERROR")."<br />";
				}
				elseif($connections_count == 0)
				{
					$arErrors[] = Loc::getMessage("WEBPROSTOR_IMPORT_IMPORT_NO_ACTIVE_CONNECTIONS")."<br />";
				}
				else
				{
					$GLOBALS["PLAN_ID"] = $PLAN_ID; //it for GetEntities
					
					switch($planParams["IMPORT_FORMAT"])
					{
						case("CSV"):
							$scriptData = new CWebprostorImportCSV;
							$fileData = $scriptData->ParseFile($IMPORT_FILE, $planParams["IMPORT_FILE_SHARSET"], $planParams["CSV_XLS_FINISH_LINE"], $planParams["CSV_DELIMITER"]);
							break;
						case("XML"):
							$scriptData = new CWebprostorImportXML;
							/*$fileData = $scriptData->ParseFile($IMPORT_FILE, $planParams["IMPORT_FILE_SHARSET"], $planParams["XML_ENTITY_GROUP"], $planParams["XML_ENTITY"], $planParams["XML_PARSE_PARAMS_TO_PROPERTIES"], $planParams["ITEMS_PER_ROUND"]);*/
							$fileData["ITEMS_COUNT"] = $scriptData->GetTotalCount(
								$IMPORT_FILE, 
								$planParams["XML_ENTITY_GROUP"], 
								$planParams["XML_ENTITY"]
							);
							/*if(!$scriptData->isValidate())
								$arErrors[] = Loc::getMessage('WEBPROSTOR_IMPORT_ERROR_XML_FILE_IS_INVALID');*/
							break;
						case("XLS"):
							$scriptData = new CWebprostorImportXLS;
							$fileData = $scriptData->ParseFile($IMPORT_FILE, $planParams["IMPORT_FILE_SHARSET"], $planParams["XLS_SHEET"], false, $planParams["CSV_XLS_FINISH_LINE"]);
							break;
						case("XLSX"):
							$scriptData = new CWebprostorImportXLSX;
							$fileData = $scriptData->ParseFile($IMPORT_FILE, $planParams["IMPORT_FILE_SHARSET"], $planParams["XLS_SHEET"], false, $planParams["CSV_XLS_FINISH_LINE"]);
							break;
						case("ODS"):
						case("XODS"):
							$scriptData = new Webprostor\Import\Format\ODS;
							$fileData = $scriptData->ParseFile($IMPORT_FILE, $planParams["IMPORT_FILE_SHARSET"], $planParams["XLS_SHEET"], false, $planParams["CSV_XLS_FINISH_LINE"]);
							break;
						case("JSON"):
							$scriptData = new Webprostor\Import\Format\JSON;
							$fileData = $scriptData->GetData($planParams);
							break;
						default:
							return false;
					}
					
					$entities = $scriptData->GetEntities($PLAN_ID, $IMPORT_FILE);
					
					if($planParams["IMPORT_FILE_CHECK"] != "N" && $entities)
					{
						if($planParams["IMPORT_FORMAT"] == "XML")
						{
							if($planParams["XML_USE_ENTITY_NAME"] != "Y")
								$checkEntitiesResult = CWebprostorImport::CheckEntitiesNames($PLAN_ID, $entities["KEYS"]);
						}
						else
							$checkEntitiesResult = CWebprostorImport::CheckEntitiesNames($PLAN_ID, $entities);
						
						if(isset($checkEntitiesResult) && is_array($checkEntitiesResult) && count($checkEntitiesResult))
							$arErrors[] = Loc::getMessage("WEBPROSTOR_IMPORT_ENTITIES_AND_NAMES_NOT_IDENTICAL", ["#ENTITIES#" => implode(', ', $checkEntitiesResult)])."<br />";
					}
					
					$TOTAL = $fileData["ITEMS_COUNT"];
					if($TOTAL == 0)
						$arErrors[] = Loc::getMessage('WEBPROSTOR_IMPORT_ERROR_FILE_EMPTY');
					
					unset($fileData, $planParams, $entities);
					
					$NS = array(
						"START_FROM" => intVal($START_FROM),
						"START_TIME" => microtime(true),
						"IMPORT_FILE" => $IMPORT_FILE,
						"TOTAL" => $TOTAL,
					);
				}
			}
		}
	}
	
	if(!check_bitrix_sessid())
	{
		$arErrors[] = Loc::getMessage("WEBPROSTOR_IMPORT_ERROR_SESSION_EXPIRED")."<br />";
	}
	elseif($moduleAccessLevel < 'T')
	{
		$arErrors[] = Loc::getMessage("ACCESS_DENIED")."<br />";
	}
	elseif(!$PLAN_ID)
	{
		$arErrors[] = Loc::getMessage("WEBPROSTOR_IMPORT_PLAN_ID_ERROR")."<br />";
	}
	?>
	<script>
		CloseWaitWindow();
	</script>
	<?
		
	if(count($arErrors) == 0)
	{
		$scriptExecutionTime = microtime(true);
		
		$currentItem = $NS["START_FROM"];
		
		//$compareText = "CWebprostorImport::Import({$PLAN_ID}, 0);";
		$import = CWebprostorImport::Import($PLAN_ID, $currentItem, false, $NS['IMPORT_FILE']);
		
		preg_match('/CWebprostorImport::Import\([0-9]*\,\s(.*)\);/', $import, $matches);
		$NS["START_FROM"] = $matches[1];
		
		$progressTotal = intval($NS["TOTAL"]);
		$progressValue = intval($NS["START_FROM"]);
		
		if($NS["START_FROM"] > 0)
		{
			CWebprostorCoreFunctions::showProgressBar(
				'default', 
				Loc::getMessage("IMPORT_PROGRESS", array(
					"#DONE#" => number_format(intVal($NS["START_FROM"]), 0, "", " "),
					"#TOTAL#"=> number_format(intVal($NS["TOTAL"]), 0, "", " "),
				)),
				Loc::getMessage("IMPORT_PROGRESS_STEP", array(
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
			
			$arMessages[] = Loc::getMessage("IMPORT_PROGRESS_FINISH", array(
				"#TOTAL#"=> number_format(intVal($NS["TOTAL"]), 0, "", " "),
				"#TIME_MIN#"=> $TIME_MIN,
				"#TIME_SEC#"=> abs($TIME_SEC),
			));
			
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
			CWebprostorCoreFunctions::showAlertBegin('danger', 'danger');
			echo $strMessage;
			CWebprostorCoreFunctions::showAlertEnd();
		}
	}
	
	if(is_array($arMessages))
	{
		foreach($arMessages as $strMessage)
		{
			CWebprostorCoreFunctions::showAlertBegin('success', 'info');
			echo $strMessage;
			CWebprostorCoreFunctions::showAlertEnd();
		}
	}

	require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin_js.php");
}

$APPLICATION->SetTitle( Loc::getMessage("WEBPROSTOR_IMPORT_MANUALLY_PAGE_TITLE") );
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
?>
<div id="import_result_div"></div>
<?
$aTabs = array(
	array(
		"DIV" => "FORM",
		"TAB" => Loc::getMessage("WEBPROSTOR_IMPORT_MANUALLY_TAB_NAME"),
		"ICON" => "",
		"TITLE" => Loc::getMessage("WEBPROSTOR_IMPORT_MANUALLY_TAB_DESCRIPTION")
	),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs);
?>
<script language="JavaScript" type="text/javascript">
var running = false;
var oldNS = '';
function DoNext(NS)
{
	var PLAN_ID = parseInt(document.getElementById('PLAN_ID').value);
	var START_FROM = parseInt(document.getElementById('START_FROM').value);
	var LOAD_FILES = document.getElementById('LOAD_FILES').value;
	var UPLOAD_FILE = document.getElementById('UPLOAD_FILE').value;
	var queryString = 'Import=Y'
		+ '&lang=<?echo LANG?>'
		+ '&<?echo bitrix_sessid_get()?>'
		+ '&START_FROM=' + START_FROM
		+ '&LOAD_FILES=' + LOAD_FILES
		+ '&UPLOAD_FILE=' + UPLOAD_FILE
		+ '&PLAN_ID=' + PLAN_ID;
	;

	if(running)
	{
		ShowWaitWindow();
		
		BX.ajax({
			url: 'webprostor.import_manually.php?'+queryString,
			method: 'POST',
			dataType: 'html',
			data: NS,
			timeout: <?=ini_get('max_execution_time');?>,
			onsuccess: function(result){
				document.getElementById('import_result_div').innerHTML = result;
			},
			onfailure: function(result){
				CloseWaitWindow();
				EndImport();
				
				BX.UI.Notification.Center.notify({
					content: '<?=Loc::getMessage("WEBPROSTOR_IMPORT_ERROR_RESPONSE_STATUS")?>',
					position: "top-right"
				});
			}
		});
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
	
	window.history.pushState("webprostor_import_manually", "", "webprostor.import_manually.php?lang=<?=LANG?>" );
}
BX.ready(function(){
	BX.UI.Hint.init(BX('webprostor_import_manually'));
});
</script>
<form id="webprostor_import_manually" method="POST" action="<?=$APPLICATION->GetCurPage()?>?lang=<?echo LANG?>" ENCTYPE="multipart/form-data" name="webprostor_import_manually">
<?
$listPlansFilter = [];
if(!$USER->IsAdmin())
	$listPlansFilter['SHOW_IN_MANUALLY'] = 'Y';
$queryObject = $cData->getList(Array("ID" => "DESC"), $listPlansFilter);
$listPlans = array();
while($plan = $queryObject->getNext())
	$listPlans[$plan["ID"]] = htmlspecialcharsbx($plan["NAME"]).' ['.$plan["ID"].']';

$tabControl->Begin();
$tabControl->BeginNextTab();

$arFields["IMPORT"]["ITEMS"][] = Array(
	"CODE" => "PLAN_ID",
	"ID" => "PLAN_ID",
	"REQUIRED" => "Y",
	"TYPE" => "SELECT",
	"LABEL" => Loc::getMessage("WEBPROSTOR_IMPORT_PLAN_ID"),
	"ITEMS" => $listPlans,
	"VALUE" => intVal($PLAN_ID),
);

$arFields["IMPORT"]["ITEMS"][] = Array(
	"CODE" => "START_FROM",
	"ID" => "START_FROM",
	"TYPE" => "NUMBER",
	"LABEL" => Loc::getMessage("WEBPROSTOR_IMPORT_START_FROM"),
	"DESCRIPTION" => Loc::getMessage("WEBPROSTOR_IMPORT_START_FROM_DESCRIPTION"),
	"VALUE" => intval($START_FROM),
	"PARAMS" => Array(
		"MIN" => 0
	),
);
$arFields["FILES"]['LABEL'] = Loc::getMessage("WEBPROSTOR_IMPORT_FILES");
$arFields["FILES"]["ITEMS"][] = Array(
	"CODE" => "LOAD_FILES",
	"ID" => "LOAD_FILES",
	"TYPE" => "CHECKBOX",
	"LABEL" => Loc::getMessage("WEBPROSTOR_IMPORT_LOAD_FILES"),
	"DESCRIPTION" => Loc::getMessage("WEBPROSTOR_IMPORT_LOAD_FILES_DESCRIPTION"),
	"VALUE" => $LOAD_FILES,
);
$arFields["FILES"]["ITEMS"][] = Array(
	"CODE" => "UPLOAD_FILE",
	"ID" => "UPLOAD_FILE",
	"TYPE" => "FILE_DIALOG",
	"LABEL" => Loc::getMessage("WEBPROSTOR_IMPORT_IMPORT_FILE"),
	"DESCRIPTION" => Loc::getMessage("WEBPROSTOR_IMPORT_IMPORT_FILE_DESCRIPTION"),
	"PARAMS" => Array(
		"SIZE" => 30,
		"MAXLENGTH" => 255,
		"FORM_NAME" => "webprostor_import_manually",
		"SELECT" => "F",
		"ALLOW_UPLOAD" => "Y",
		"ALLOW_FILE_FORMATS" => "csv,xml,yml,xls,xlsx",
		"PATH" => "/".COption::GetOptionString("main", "upload_dir", "upload"),
	)
);

CWebprostorCoreFunctions::ShowFormFields($arFields);

$tabControl->Buttons();
?>
	<button type="button" id="start_button" value="Y" OnClick="StartImport();" class="ui-btn ui-btn-success ui-btn-icon-start"<?=count($listPlans)==0 || $moduleAccessLevel < 'T'?' disabled=""':''?>><?echo Loc::getMessage("WEBPROSTOR_IMPORT_MANUALLY_START")?></button>
	<button class="ui-btn ui-btn ui-btn-icon-stop" type="button" id="stop_button" value="Y" OnClick="EndImport();" disabled><?echo Loc::getMessage("WEBPROSTOR_IMPORT_MANUALLY_STOP")?></button>
<?
$tabControl->End();

if(isset($_REQUEST['PLAN_ID']) && check_bitrix_sessid())
{
	$ID = intval($_REQUEST['PLAN_ID']);
	if($ID > 0)
	{
?>
<script>
BX.ready(BX.defer(function(){
	StartImport();
}));
</script>
<?
	}
}
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>