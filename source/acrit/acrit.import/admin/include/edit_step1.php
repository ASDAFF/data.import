<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
IncludeModuleLangFile(__FILE__);

/** @var array $arProfile */
/** @var array $arSourceTypes */

if (! function_exists('GetIBlockDropDownListCust')) {
	function GetIBlockDropDownListCust($IBLOCK_ID, $strTypeName, $strIBlockName, $arFilter = false, $strAddType = '', $strAddIBlock = ''): string
	{
		if (!is_array($arFilter)) {
			$arFilter = [];
		}
		$arFilter["MIN_PERMISSION"] = "R";

		return GetIBlockDropDownListEx($IBLOCK_ID, $strTypeName, $strIBlockName, $arFilter, '', '', $strAddType, $strAddIBlock);
	}
}
?>

<?
if (!empty($arProfileFields)) {
	foreach ($arProfileFields as $code => $arField) {
		if (!$arField['display']) {
		    echo '<input type="hidden" name="PROFILE['.$code.']" value="'.$arField['default'].'" />' . "\n";
		}
	}
}
?>

<tr class="heading" align="center">
    <td colspan="2"><b><?=GetMessage("ACRIT_IMPORT_STEP1_SUBTIT1"); ?></b></td>
</tr>

<tr>
    <td>
	    <?=\Acrit\Core\Helper::showHint(GetMessage("ACRIT_IMPORT_FLD_NAME_HINT"))?>
	    <b><?=GetMessage("ACRIT_IMPORT_FLD_NAME"); ?></b>:
    </td>
    <td>
        <input type="text" name="PROFILE[NAME]" value="<?=$arProfile['NAME'];?>" size="70" />
    </td>
</tr>

<tr>
    <td><?=GetMessage("ACRIT_IMPORT_FLD_CODE"); ?>:</td>
    <td>
        <input type="text" name="PROFILE[CODE]" value="<?=$arProfile['CODE'];?>" size="20" />
    </td>
</tr>

<tr>
    <td>
	    <?=\Acrit\Core\Helper::showHint(GetMessage("ACRIT_IMPORT_FLD_TYPE_HINT"))?>
	    <b><?=GetMessage("ACRIT_IMPORT_FLD_TYPE"); ?></b>:
    </td>
    <td>
        <?if(!empty($arImportTypes)):?>
        <select name="PROFILE[TYPE]" id="profile_type">
            <?foreach($arImportTypes as $k => $arType):?>
                <option value="<?=$k;?>"<?=$arProfile['TYPE']==$k?' selected':'';?>><?=$arType['name'];?></option>
            <?endforeach;?>
        </select>
        <?endif;?>
    </td>
</tr>

<tr>
	<td colspan="2" >
		<div class="adm-info-message-center">
			<div class="adm-info-message">
				<a href="https://www.acrit-studio.ru/technical-support/nastroyka-modulya-universalnyy-import/" target="_blank"><?=GetMessage('ACRIT_IMPORT_STEP1_HELP_ALL')?></a><br />
				<a href="https://www.acrit-studio.ru/technical-support/nastroyka-modulya-universalnyy-import/obshchie-nastroyki-profilya/" target="_blank"><?=GetMessage("ACRIT_IMPORT_STEP1_HELP_PROFILES"); ?></a><br />
				<?if ($arProfile['TYPE'] == 'csv'):?>
					<a href="https://www.acrit-studio.ru/technical-support/nastroyka-modulya-universalnyy-import/nastroyka-importa-iz-csv-formata/" target="_blank"><?=GetMessage("ACRIT_IMPORT_STEP1_HELP_CSV"); ?></a>
				<?endif;?>
				<?if ($arProfile['TYPE'] == 'xml'):?>
					<a href="https://www.acrit-studio.ru/technical-support/nastroyka-modulya-universalnyy-import/nastroyka-importa-iz-xml-faylov/" target="_blank"><?=GetMessage("ACRIT_IMPORT_STEP1_HELP_XML"); ?></a>
				<?endif;?>
				<?if ($arProfile['TYPE'] == 'xlsx'):?>
					<a href="https://www.acrit-studio.ru/technical-support/nastroyka-modulya-universalnyy-import/nastroyka-importa-iz-xlsx-faylov/" target="_blank"><?=GetMessage("ACRIT_IMPORT_STEP1_HELP_XLSX"); ?></a>
				<?endif;?>
				<?if ($arProfile['TYPE'] == 'iblock'):?>
					<a href="https://www.acrit-studio.ru/technical-support/nastroyka-modulya-universalnyy-import/nastroyka-importa-iz-xml-s-dannymi-infobloka/" target="_blank"><?=GetMessage("ACRIT_IMPORT_STEP1_HELP_IBLOCK"); ?></a>
				<?endif;?>
				<?if ($arProfile['TYPE'] == 'yml'):?>
					<a href="https://www.acrit-studio.ru/technical-support/nastroyka-modulya-universalnyy-import/nastroyka-importa-iz-yml-faylov/" target="_blank"><?=GetMessage("ACRIT_IMPORT_STEP1_HELP_YML"); ?></a>
				<?endif;?>
				<?if ($arProfile['TYPE'] == 'adsapi'):?>
					<a href="https://www.acrit-studio.ru/technical-support/nastroyka-modulya-universalnyy-import/nastroyka-importa-iz-servisa-ads-api-ru/" target="_blank"><?=GetMessage("ACRIT_IMPORT_STEP1_HELP_ADSAPI"); ?></a>
				<?endif;?>

				<?=GetMessage("ACRIT_IMPORT_STEP1_HELP_OPTIONS_PHP", [
					'#max_input_vars#' => ini_get('max_input_vars'),
					'#allow_url_fopen#'=> ini_get('allow_url_fopen'),
				])?>
			</div>
		</div>
	</td>
</tr>

<tr class="heading" align="center">
    <td colspan="2"><b><?=GetMessage("ACRIT_IMPORT_STEP1_SUBTIT2"); ?></b></td>
</tr>

<?if ($arProfileFields['SOURCE_TYPE']['display']):?>
<tr>
    <td>
	    <?=\Acrit\Core\Helper::showHint($arProfileFields['SOURCE_TYPE']['hint'])?>
	    <?=$arProfileFields['SOURCE_TYPE']['name'];?>:
    </td>
    <td>
        <?if(!empty($arImportTypes[$arProfile['TYPE']]['source_types'])):?>
        <select name="PROFILE[SOURCE_TYPE]" id="source_type">
            <?foreach($arImportTypes[$arProfile['TYPE']]['source_types'] as $k):?>
            <?$arType = $arSourceTypes[$k];?>
            <option value="<?=$k;?>"<?=$arProfile['SOURCE_TYPE']==$k?' selected':'';?>><?=$arType['name'];?></option>
            <?endforeach;?>
        </select>
        <?endif;?>
    </td>
</tr>
<?endif;?>

<?if ($arProfileFields['SOURCE']['display']):?>
	<?if ($arProfile['SOURCE_TYPE'] == 'file'):?>
		<tr>
		    <td width="40%">
			    <?=\Acrit\Core\Helper::showHint($arProfileFields['SOURCE']['hint'])?>
			    <b><?=$arProfileFields['SOURCE']['name'];?></b>:
		    </td>
		    <td width="60%">
		        <input type="text" name="SOURCE" value="<?=$arProfile['SOURCE'];?>" size="61" />
		        <input type="button" value="<?=GetMessage("ACRIT_IMPORT_OPEN"); ?>" OnClick="BtnClick()">
		        <?
		        $file_filter = $arImportTypes[$arProfile['TYPE']]['file_ext'];
		        $arFileDialogParams = [
		            "event" => "BtnClick",
		            "arResultDest" => [
		                "FORM_NAME" => "import_form",
		                "FORM_ELEMENT_NAME" => "SOURCE",
		            ],
		            "arPath" => [
		                "SITE" => SITE_ID,
		                "PATH" => "/".COption::GetOptionString("main", "upload_dir", "upload"),
		            ],
		            "select" => 'F',
		            "operation" => 'O',
		            "showUploadTab" => true,
		            "showAddToMenuTab" => false,
		            "fileFilter" => $file_filter,
		            "allowAllFiles" => true,
		            "SaveConfig" => true,
		        ];
		        ?>
		        <?CAdminFileDialog::ShowScript($arFileDialogParams);?>
		    </td>
		</tr>
	<?elseif ($arProfile['SOURCE_TYPE'] == 'url'):?>
		<tr>
		    <td width="40%">
			    <?=\Acrit\Core\Helper::showHint($arProfileFields['SOURCE']['hint'])?>
			    <b><?=$arProfileFields['SOURCE']['name'];?></b>:
		    </td>
		    <td width="60%">
		        <input type="text" name="SOURCE" value="<?=$arProfile['SOURCE'];?>" size="61" />
		    </td>
		</tr>
		<tr>
			<td></td>
			<td>
				<div class="adm-info-message">
					<?=GetMessage("ACRIT_IMPORT_STEP1_HELP_SOURCE_BASIC_AUTHORIZATION")?>
				</div>
			</td>
		</tr>
	<?elseif ($arProfile['SOURCE_TYPE'] == 'oauth'):?>
		<tr>
		    <td width="40%">
			    <?=\Acrit\Core\Helper::showHint($arProfileFields['SOURCE']['hint'])?>
			    <b><?=$arProfileFields['SOURCE']['name'];?></b>:
		    </td>
		    <td width="60%">
		        <input type="text" name="SOURCE" value="<?=$arProfile['SOURCE'];?>" size="61" />
		    </td>
		</tr>
	<?endif;?>
<?endif;?>

<?if ($arProfile['SOURCE_TYPE'] == 'oauth' || $arProfile['SOURCE_TYPE'] == 'url'):?>
	<?if ($arProfileFields['SOURCE_LOGIN']['display']):?>
		<tr>
		    <td>
			    <?=\Acrit\Core\Helper::showHint($arProfileFields['SOURCE_LOGIN']['hint'])?>
			    <?=$arProfileFields['SOURCE_LOGIN']['name'];?>:
		    </td>
		    <td>
		        <input type="text" name="PROFILE[SOURCE_LOGIN]" value="<?=htmlspecialcharsbx($arProfile['SOURCE_LOGIN'])?>" size="30" />
		    </td>
		</tr>
	<?endif;?>
	<?if ($arProfileFields['SOURCE_KEY']['display']):?>
		<tr>
		    <td>
			    <?=\Acrit\Core\Helper::showHint($arProfileFields['SOURCE_KEY']['hint'])?>
			    <?=$arProfileFields['SOURCE_KEY']['name'];?>:
		    </td>
		    <td>
		        <input type="text" name="PROFILE[SOURCE_KEY]" value="<?=htmlspecialcharsbx($arProfile['SOURCE_KEY'])?>" size="30" />
		    </td>
		</tr>
	<?endif;?>
<?endif;?>

<?if ($arProfileFields['ENCODING']['display']):?>
<tr>
    <td>
	    <?=\Acrit\Core\Helper::showHint($arProfileFields['ENCODING']['hint'])?>
        <?=$arProfileFields['ENCODING']['name'];?>:
    </td>
    <td>
        <input type="text" name="PROFILE[ENCODING]" value="<?=$arProfile['ENCODING'];?>" size="15" placeholder="UTF-8" />
    </td>
</tr>
<?endif;?>

<tr>
    <td>
	    <?=\Acrit\Core\Helper::showHint(GetMessage('ACRIT_IMPORT_FLD_IBLOCK_ID_HINT'))?>
	    <b><?=GetMessage("ACRIT_IMPORT_FLD_IBLOCK_ID"); ?></b>:
    </td>
    <td>
        <?echo GetIBlockDropDownListCust($arProfile['IBLOCK_ID'], 'IBLOCK_TYPE_ID', 'PROFILE[IBLOCK_ID]', false, 'class="adm-detail-iblock-types"', 'class="adm-detail-iblock-list"'); ?>
    </td>
</tr>


