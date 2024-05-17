<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/data.import/prolog.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/data.import/include.php");

IncludeModuleLangFile(__FILE__);

$module_id = 'data.import';
$moduleAccessLevel = $APPLICATION->GetGroupRight($module_id);

if ($moduleAccessLevel == "D")
    $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

$cConversion = new CDataImportConversion;

$availableTags = Array();

$notifRes = CAdminNotify::GetList(array('ID' => 'DESC'), array('MODULE_ID'=>$module_id, 'TAG'=>$CONVERSION_TYPE));
while($notifArr = $notifRes->GetNext())
{
	$availableTags[] = str_replace("DATA_IMPORT_", "", $notifArr["TAG"]);
}

if($CONVERSION == "Y" && isset($CONVERSION_TYPE) && in_array($CONVERSION_TYPE, $availableTags) && $moduleAccessLevel=="W")
{
	switch($CONVERSION_TYPE)
	{
		case("CONVERT_CONNECTION_SECTION_DEPTH_LEVEL"):
			$result = $cConversion->ConvertConnectionSectionDepthLevel();
			break;
		case("CONVERT_OFFERS_PRODUCT_SETTING"):
			$result = $cConversion->ConvertOffersProductSetting();
			break;
		case("CONVERT_XLS_TO_XLSX"):
			$result = $cConversion->ConvertXlsToXlsx();
			break;
		case("CONVERT_DEFAULT_ACTIVE"):
			$result = $cConversion->ConvertDefaultActive();
			break;
	}
	
	if($result)	
	{
		CAdminNotify::DeleteByTag("DATA_IMPORT_".$CONVERSION_TYPE);
		$message = new CAdminMessage(Array("MESSAGE" => GetMessage($CONVERSION_TYPE), "TYPE" => "OK"));
	}
	else
		$message = new CAdminMessage(Array("MESSAGE" => GetMessage("CONVERSION_ERROR"), "TYPE" => "ERROR"));
}
else
{
	$message = new CAdminMessage(Array("MESSAGE" => GetMessage("NO_CONVERSIONS"), "TYPE" => "OK"));
}

$APPLICATION->SetTitle(GetMessage("CONVERSION_PAGE_TITLE"));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

if($message)
	echo $message->Show();

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>