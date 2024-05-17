<?php
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");
IncludeModuleLangFile(__FILE__);

/** @var array $arProfile */
/** @var bool $incl_catalog */

?>
<tr class="heading" align="center">
	<td colspan="2"><b><?=GetMessage("ACRIT_IMPORT_STEP3_SUBTIT1");?></b></td>
</tr>

<tr>
	<td colspan="2">
		<div class="adm-info-message-center">
		<div class="adm-info-message">
			<a href="https://www.acrit-studio.ru/technical-support/nastroyka-modulya-universalnyy-import/obshchie-nastroyki-profilya/"
			   target="_blank"><?=GetMessage("ACRIT_IMPORT_HELP_MAIN_PROPS");?></a><br/>
		</div>
		</div>
	</td>
</tr>

<? /*
<tr>
    <td><?=GetMessage("ACRIT_IMPORT_ELEMENTY_KOTORYH_NE")?></td>
    <td width="50%">
        <select name="PROFILE[ACTIONS_NOT_IN_FILE]">
            <option value=""<?=$arProfile['ACTIONS_NOT_IN_FILE']==''?' selected':'';?>><?=GetMessage("ACRIT_IMPORT_NE_TROGATQ")?></option>
            <option value="deactivate"<?=$arProfile['ACTIONS_NOT_IN_FILE']=='deactivate'?' selected':'';?>><?=GetMessage("ACRIT_IMPORT_DEAKTIVIROVATQ")?></option>
            <option value="delete"<?=$arProfile['ACTIONS_NOT_IN_FILE']=='delete'?' selected':'';?>><?=GetMessage("ACRIT_IMPORT_UDALITQ")?></option>
        </select>
    </td>
</tr>
*/ ?>

<tr>
	<td>
		<?=\Acrit\Core\Helper::showHint(GetMessage("ACRIT_IMPORT_NOVYE_ELEMENTY_HINT"));?>
		<?=GetMessage("ACRIT_IMPORT_NOVYE_ELEMENTY")?>
	</td>
	<td>
		<select name="PROFILE[ACTIONS_NEW_ELEMENTS]">
			<option value="activate"<?=$arProfile['ACTIONS_NEW_ELEMENTS'] == 'activate' ? ' selected' : '';?>><?=GetMessage("ACRIT_IMPORT_SRAZU_AKTIVIROVATQ")?></option>
			<option value="not_activate"<?=$arProfile['ACTIONS_NEW_ELEMENTS'] == 'not_activate' ? ' selected' : '';?>><?=GetMessage("ACRIT_IMPORT_NE_AKTIVIROVATQ")?></option>
			<option value="not_create"<?=$arProfile['ACTIONS_NEW_ELEMENTS'] == 'not_create' ? ' selected' : '';?>><?=GetMessage("ACRIT_IMPORT_NE_SOZDAVATQ")?></option>
		</select>
	</td>
</tr>

<tr>
	<td>
		<?=\Acrit\Core\Helper::showHint(GetMessage("ACRIT_IMPORT_PARAMS_ITEMS_EXISTS_HINT"));?>
		<?=GetMessage("ACRIT_IMPORT_PARAMS_ITEMS_EXISTS")?>
	</td>
	<td>
		<select name="PROFILE[ACTIONS_EXIST_ELEMENTS]">
			<option value="update"<?=$arProfile['ACTIONS_EXIST_ELEMENTS'] == 'update' ? ' selected' : '';?>><?=GetMessage("ACRIT_IMPORT_PARAMS_ITEMS_EXISTS_UPDATE")?></option>
			<option value="ignore"<?=$arProfile['ACTIONS_EXIST_ELEMENTS'] == 'ignore' ? ' selected' : '';?>><?=GetMessage("ACRIT_IMPORT_PARAMS_ITEMS_EXISTS_IGNORE")?></option>
		</select>
	</td>
</tr>

<? if ($incl_catalog && \Bitrix\Catalog\CatalogIblockTable::getById($arProfile['IBLOCK_ID'])->fetch()): ?>

	<tr>
		<td>
			<?=\Acrit\Core\Helper::showHint(GetMessage("ACRIT_IMPORT_DEFAULT_CATALOG_FIELD_TOVAR_HINT"));?>
			<?=GetMessage("ACRIT_IMPORT_DEFAULT_CATALOG_FIELD_TOVAR")?>
		</td>
		<td>
			<input type="hidden" name="PROFILE[ACTIONS_DEFAULT_CATALOG_FIELDS]" value="N">
			<input type="checkbox" name="PROFILE[ACTIONS_DEFAULT_CATALOG_FIELDS]" value="Y"<?=($arProfile['ACTIONS_DEFAULT_CATALOG_FIELDS'] == "Y") ? ' checked' : ''?>>
		</td>
	</tr>

	<tr>
		<td>
			<?=\Acrit\Core\Helper::showHint(GetMessage("ACRIT_IMPORT_DEAKTIVIROVATQ_TOVAR_HINT"));?>
			<?=GetMessage("ACRIT_IMPORT_DEAKTIVIROVATQ_TOVAR")?>
		</td>
		<td>
			<input type="hidden" name="PROFILE[ACTIONS_PRICE_NULL]" value="N">
			<input type="checkbox" name="PROFILE[ACTIONS_PRICE_NULL]" value="Y"<?=($arProfile['ACTIONS_PRICE_NULL'] == "Y") ? ' checked' : ''?>>
		</td>
	</tr>

	<tr>
		<td>
			<?=\Acrit\Core\Helper::showHint(GetMessage("ACRIT_IMPORT_DEAKTIVIROVATQ_TOVAR1_HINT"));?>
			<?=GetMessage("ACRIT_IMPORT_DEAKTIVIROVATQ_TOVAR1")?>
		</td>
		<td>
			<input type="hidden" name="PROFILE[ACTIONS_AMOUNT_NULL]" value="N">
			<input type="checkbox" name="PROFILE[ACTIONS_AMOUNT_NULL]" value="Y"<?=($arProfile['ACTIONS_AMOUNT_NULL'] == "Y") ? ' checked' : ''?>>
		</td>
	</tr>

<? endif; ?>

<tr>
	<td>
		<?=\Acrit\Core\Helper::showHint(GetMessage("ACRIT_IMPORT_PRIVAZYVATQ_K_RAZDEL_HINT"));?>
		<?=GetMessage("ACRIT_IMPORT_PRIVAZYVATQ_K_RAZDEL")?>
	</td>
	<td>
		<select name="PROFILE[ACTIONS_SECTIONS_LINK]">
			<option value="all"<?=$arProfile['ACTIONS_SECTIONS_LINK'] == 'all' ? ' selected' : '';?>><?=GetMessage("ACRIT_IMPORT_VSE_ELEMENTY")?></option>
			<option value="new"<?=$arProfile['ACTIONS_SECTIONS_LINK'] == 'new' ? ' selected' : '';?>><?=GetMessage("ACRIT_IMPORT_TOLQKO_NOVYE")?></option>
			<option value="no"<?=$arProfile['ACTIONS_SECTIONS_LINK'] == 'no' ? ' selected' : '';?>><?=GetMessage("ACRIT_IMPORT_NICEGO")?></option>
		</select>
	</td>
</tr>

<tr>
	<td>
		<?=\Acrit\Core\Helper::showHint(GetMessage("ACRIT_IMPORT_RAZDEL_PO_UMOLCANIU_HINT"));?>
		<?=GetMessage("ACRIT_IMPORT_RAZDEL_PO_UMOLCANIU")?>
	</td>
	<td>
		<select name="PROFILE[DEFAULT_SECTION_ID]">
			<option value=""><?=GetMessage("ACRIT_IMPORT_KORNEVOY_RAZDEL")?></option>
			<? if (!empty($arIBSections)): ?>
				<? foreach ($arIBSections as $arItem): ?>
					<option value="<?=$arItem['value'];?>"<?=$arItem['selected'] ? ' selected' : '';?>><?=$arItem['name'];?></option>
				<? endforeach; ?>
			<? endif; ?>
		</select>
	</td>
</tr>

<tr class="heading" align="center">
	<td colspan="2"><b><?=GetMessage("ACRIT_IMPORT_STEP3_SUBTIT2");?></b></td>
</tr>

<tr>
	<td>
		<?=\Acrit\Core\Helper::showHint(GetMessage("ACRIT_IMPORT_SOZDAVATQ_NOVYE_RAZD_HINT"));?>
		<?=GetMessage("ACRIT_IMPORT_SOZDAVATQ_NOVYE_RAZD")?>
	</td>
	<td>
		<input type="hidden" name="PROFILE[ACTIONS_SECTIONS_CREATE]" value="N">
		<input type="checkbox" name="PROFILE[ACTIONS_SECTIONS_CREATE]" value="Y"<?=($arProfile['ACTIONS_SECTIONS_CREATE'] != "N") ? ' checked' : ''?>>
	</td>
</tr>

<tr class="heading" align="center">
	<td colspan="2"><b><?=GetMessage("ACRIT_IMPORT_STEP3_SUBTIT3");?></b></td>
</tr>

<?/*
TOFUTURE
<tr>
	<td><label for="profile_imgs_source_type"><? echo GetMessage("ACRIT_IMPORT_FLD_IMGS_SOURCE_TYPE"); ?></label>:</td>
	<td>
		<select name="PROFILE[IMGS_SOURCE_TYPE]" id="profile_imgs_source_type">
			<option value="loc_server"<?=$arProfile['IMGS_SOURCE_TYPE'] == 'server_or_site' ? ' selected' : '';?>><?=GetMessage("ACRIT_IMPORT_IMGS_SOURCE_TYPE_SERVER")?></option>
		</select>
	</td>
</tr>
*/?>

<tr>
	<td>
		<?=\Acrit\Core\Helper::showHint(GetMessage("ACRIT_IMPORT_FLD_IMGS_SOURCE_HINT") . ': ' . GetMessage("ACRIT_IMPORT_IMGS_SOURCE_TYPE_SERVER"))?>
		<? echo GetMessage("ACRIT_IMPORT_FLD_IMGS_SOURCE"); ?>:
	</td>
	<td>
		<input type="text" name="PROFILE[IMGS_SOURCE_PATH]" value="<?=$arProfile['IMGS_SOURCE_PATH'];?>" size="50"/>
	</td>
</tr>

<tr>
	<td>
		<?=\Acrit\Core\Helper::showHint(GetMessage("ACRIT_IMPORT_PRIMENITQ_NASTROYKI_HINT"));?>
		<?=GetMessage("ACRIT_IMPORT_PRIMENITQ_NASTROYKI")?>
	</td>
	<td>
		<input type="hidden" name="PROFILE[ACTIONS_IB_IMG_MODIF]" value="N">
		<input type="checkbox" name="PROFILE[ACTIONS_IB_IMG_MODIF]" value="Y"<?=($arProfile['ACTIONS_IB_IMG_MODIF'] == "Y") ? ' checked' : ''?>>
	</td>
</tr>