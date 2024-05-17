<?
IncludeModuleLangFile(__FILE__);
?>
<?=CAdminMessage::ShowMessage(array("MESSAGE"=>GetMessage("BITRIX_IBLOCK_NOT_INSTALL"), "HTML"=>true, "TYPE"=>"ERROR"));?>
<a class="adm-btn" target="_blank" href="/bitrix/admin/module_admin.php?action=&lang=<?=LANG?>&id=iblock&sessid=<?=bitrix_sessid();?>&install=<?=GetMessage("BITRIX_IBLOCK_INSTALL")?>"><?=GetMessage("BITRIX_IBLOCK_INSTALL")?></a>
<a class="adm-btn" href="javascript:void(0)" onClick="window.location.reload();"><?=GetMessage("MOD_RELOAD")?></a>