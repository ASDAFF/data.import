<?php
define("ADMIN_MODULE_NAME", "webprostor.import");

$intID = intval($_REQUEST['ID']);

if (0 < $intID) {
	$strPath = $_SERVER['DOCUMENT_ROOT'].'/bitrix/tmp/'.ADMIN_MODULE_NAME.'/';
	//$strName = 'plan_export_'.$intID.'.xml';
	$strName = $_SERVER['HTTP_HOST'].'_plan_export_'.$intID.'.xml';
	if (file_exists($strPath.$strName) && is_file($strPath.$strName)) {
		header('Content-type: text/xml');
		header('Content-Disposition: attachment; filename="'.$strName.'"');
		readfile($strPath.$strName);
		unlink($strPath.$strName);
	}
}