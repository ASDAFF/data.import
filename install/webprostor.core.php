<?
IncludeModuleLangFile(__FILE__);
?>
<?=CAdminMessage::ShowMessage(array("MESSAGE"=>GetMessage("DATA_CORE_NOT_INSTALL"), "HTML"=>true, "TYPE"=>"ERROR"));?>
<a class="adm-btn" target="_blank" href="/bitrix/admin/update_system_partner.php?addmodule=data.core"><?=GetMessage("DATA_CORE_INSTALL")?></a>
<a class="adm-btn" href="javascript:void(0)" onClick="window.location.reload();"><?=GetMessage("MOD_RELOAD")?></a>