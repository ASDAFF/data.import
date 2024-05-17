<form action="<?=$APPLICATION->GetCurPage()?>" onsubmit="this['inst'].disabled=true; return true;">
<?=bitrix_sessid_post()?>
	<input type="hidden" name="lang" value="<?=LANG?>">
	<input type="hidden" name="id" value="webprostor.import">
	<input type="hidden" name="uninstall" value="Y">
	<input type="hidden" name="step" value="2">
	<?=CAdminMessage::ShowMessage(GetMessage("MOD_UNINST_WARN"))?>
	<p><?=GetMessage("DELETE_PARAMS")?>:</p>
	<p><input type="checkbox" name="SAVE_TABLES" id="save_tables" value="Y" checked><label for="save_tables"><?echo GetMessage("MOD_UNINST_SAVE_TABLES")?></label></p>
	<p><input type="checkbox" name="DELETE_IMPORT_DIR" id="delete_import_dir" value="Y" checked><label for="delete_import_dir"><?=GetMessage("DELETE_IMPORT_DIR")?></label></p>
	<input type="submit" name="inst" value="<?=GetMessage("MOD_UNINST_DEL")?>" />
</form>