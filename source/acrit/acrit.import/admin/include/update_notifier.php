<?
/** @var string $moduleId */
use \Bitrix\Main\Localization\Loc,
    \Acrit\Import\Helper;

Loc::loadMessages(__FILE__);

$intDateTo = null;

// 30 min check updates
$cache = Bitrix\Main\Data\Cache::createInstance();
if ($cache->initCache(1800, $moduleId . 'updates', 'php/' . $moduleId . '/updates')) {
	$arAvailableUpdates = $cache->getVars();
} elseif ($cache->startDataCache()) {
	$arAvailableUpdates = Helper::checkModuleUpdates($moduleId, $intDateTo);
	$cache->endDataCache($arAvailableUpdates);
}

$moduleVersion = \Acrit\Core\Helper::getModuleVersion($moduleId);
foreach ($arAvailableUpdates as $strVersion => $update) {
	if (checkVersion($moduleVersion, $strVersion)) {
		unset($arAvailableUpdates[$strVersion]);
	}
}
if (is_array($arAvailableUpdates) && !empty($arAvailableUpdates)) {
	$intMaxDisplayUpdates = 10;

	$arAvailableUpdates = array_reverse($arAvailableUpdates);
	$intUpdatesCount = count($arAvailableUpdates);
	$arAvailableUpdates = array_slice($arAvailableUpdates, 0, $intMaxDisplayUpdates);
	ob_start();
	?>
	<style>
	#acrit-imp-update-notifier-details-toggle{
		border-bottom:1px dashed #2675d7;
		color:#2675d7;
		text-decoration:none;
	}
	#acrit-imp-update-notifier-details-toggle:hover{
		border-bottom:0;
	}
	#acrit-imp-update-notifier-details-block{
		display:none;
	}
	#acrit-imp-update-notifier-details-block ul{
		margin-bottom:4px;
		margin-left:0;
		padding-left:18px;
	}
	</style>
	<div id="acrit-imp-update-notifier-details-block">
		<ul>
			<?foreach($arAvailableUpdates as $strVersion => $strDescription):?>
				<li><div><b><?=$strVersion;?></b>.<br/><?=$strDescription;?></div><br/></li>
			<?endforeach?>
		</ul>
		<a href="/bitrix/admin/update_system_partner.php?lang=<?=LANGUAGE_ID?>&addmodule=acrit.import"
			target="_blank" class="adm-btn adm-btn-green">
			<?=Loc::getMessage('ACRIT_EXP_UPDATE_NOTIFIER_UPDATE');?>
		</a>
	</div>
	<script>
		$(function (){
			$('#acrit-imp-update-notifier-details-toggle').bind('click', function(e){
				e.preventDefault();
				$('#acrit-imp-update-notifier-details-block').toggle();
			});
		});
	</script>
	<?
	$strDetails = ob_get_clean();
	print Helper::showSuccess(Loc::getMessage('ACRIT_EXP_UPDATE_NOTIFIER_AVAILABLE', [
		'#COUNT#' => $intUpdatesCount,
	]), $strDetails);
}
elseif(is_numeric($intDateTo) && $intDateTo>0 && $intDateTo<=time()){
	$strRenewUrl = 'https://marketplace.1c-bitrix.ru/tobasket.php?ID='.$moduleId;
	if(LICENSE_KEY != 'DEMO') {
		$strLicense = md5('BITRIX'.LICENSE_KEY.'LICENCE');
		$strRenewUrl .= '&lckey='.$strLicense;
	}
	$strMessage = Loc::getMessage('ACRIT_EXP_UPDATE_NOTIFIER_RENEW_LICENSE', [
		'#DATE#' => date(\CDatabase::DateFormatToPHP(FORMAT_DATE), $intDateTo),
		'#LINK#' => $strRenewUrl,
	]);
	print Helper::showNote($strMessage, false);
}

?>