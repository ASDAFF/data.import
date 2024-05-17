<?
$errors = false;
$notice = Array(
	"ZIP" => "NO",
	"XMLREADER" => "NO",
	"DOMDOCUMENT" => "NO",
	"CURL" => "NO",
);
if(phpversion() < "7.4.0")
	$errors["PHP"] = GetMessage("ERROR_PHP");
if(!CModule::IncludeModule("webprostor.core"))
	$errors["WEBPROSTOR_CORE"] = GetMessage("ERROR_WEBPROSTOR_CORE");
if(CUtil::Unformat(ini_get('memory_limit')) < 268435456)
	$errors["MEMORY_LIMIT"] = GetMessage("WEBPROSTOR_IMPORT_MEMORY_LIMIT", ['#MEMORY_LIMIT#' => ini_get('memory_limit')]);
if(extension_loaded('zip'))
	$notice["ZIP"] = "YES";
if(extension_loaded('xmlreader'))
	$notice["XMLREADER"] = "YES";
if(extension_loaded('dom'))
	$notice["DOMDOCUMENT"] = "YES";
if(extension_loaded('curl'))
	$notice["CURL"] = "YES";
?>
<form action="<?=$APPLICATION->GetCurPage()?>" onsubmit="this['inst'].disabled=true; return true;">
<?=bitrix_sessid_post()?>
	<input type="hidden" name="lang" value="<?=LANG?>">
	<input type="hidden" name="id" value="webprostor.import">
	<input type="hidden" name="install" value="Y">
	<input type="hidden" name="step" value="2">
	<?
	if(!$errors)
		CAdminMessage::ShowNote(GetMessage("CONDITIONS_SUCCESS"));
	?>
	<?if($errors) {?>
	<?foreach($errors as $error) { ?>
	<?CAdminMessage::ShowMessage($error);?>
	<? } ?>
	<? if(isset($errors["WEBPROSTOR_CORE"])) { ?>
	<a class="adm-btn" target="_blank" href="/bitrix/admin/update_system_partner.php?addmodule=webprostor.core"><?=GetMessage("WEBPROSTOR_CORE_INSTALL")?></a>
	<? } ?>
	<? } ?>
	<div class="adm-info-message-wrap">
		<div class="adm-info-message">
			<?foreach($notice as $ext => $notice) { ?>
			<?=GetMessage("NOTICE_EXTENSION", Array("#STATUS#" => GetMessage("NOTICE_{$notice}"), "#EXTENSION#" => $ext))?><br />
			<? } ?>
		</div>
	</div>
	<?/*<p><?=GetMessage("WEBPROSTOR_IMPORT_MEMORY_LIMIT")?>: <?=ini_get('memory_limit');?></p>
	<p><?=GetMessage("WEBPROSTOR_IMPORT_MAX_EXECUTION_TIME")?>: <?=ini_get('max_execution_time');?></p>*/?>
	<input type="submit" class="adm-btn-green" name="inst" value="<?=GetMessage("MOD_INSTALL")?>" <? if($errors) { ?>disabled<? } ?> />
	<? if($errors) { ?><a class="adm-btn" href="javascript:void(0)" onClick="window.location.reload();"><?=GetMessage("MOD_RELOAD")?></a><? } ?>
</form>