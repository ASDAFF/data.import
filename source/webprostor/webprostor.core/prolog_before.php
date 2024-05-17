<?
use Bitrix\Main\Config\Option;

$HIDE_DONATION_ALERT = Option::get('webprostor.core', "HIDE_DONATION_ALERT");
if($HIDE_DONATION_ALERT != 'Y'){
?>
<div class="ui-alert ui-alert-primary ui-alert-icon-info">
    <span class="ui-alert-message">
		<?=GetMessage('WEBPROSTOR_CORE_DONUT');?>
	</span>
</div>
<? } ?>