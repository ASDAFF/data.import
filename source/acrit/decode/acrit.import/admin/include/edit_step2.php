<?php
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");

/** @var array{'TYPE':string, 'SCAN_ALL_ITEMS_TO_FIND_FIELDS':string} $arProfile */

use \Bitrix\Main\Localization\Loc;

Loc::LoadMessages(__FILE__);
$loc_messages = Loc::loadLanguageFile(__FILE__);
?>
<script>
	let loc_messages = {
		<?foreach ($loc_messages as $k => $message):?>
			<?=$k;?>: '<?=str_replace(["\n", "\r"], '', $message);?>',
		<?endforeach;?>
	};
</script>

<?
// region Custom profile settings
if (!empty($arProfileAddSettings)) {?>
	<tr class="heading" align="center">
		<td colspan="2"><b><?=$arProfileAddSettings['title'];?></b></td>
	</tr>
	<?
	foreach ($arProfileAddSettings['fields'] as $k => $arParam) {
		$field_name = 'PROFILE[SOURCE_' . $arParam['DB_FIELD'] . ']';
		if ($arParam['TYPE'] != 'custom'):
			?>
			<tr>
				<td width="50%">
					<?
					echo !empty($arParam['HINT']) ? \Acrit\Core\Helper::showHint($arParam['HINT']) : '';
					?>
					<?=$arParam['LABEL'];?>:
				</td>
				<td>
					<? if ($arParam['TYPE'] == 'boolean'):?>
						<input type="hidden" name="<?=$field_name;?>" value="N">
						<input type="checkbox" name="<?=$field_name;?>" value="Y"<?=($arParam['VALUE'] == "Y") ? ' checked' : ''?> class="import-change-sbmt">
					<? elseif ($arParam['TYPE'] == 'list'):?>
						<select name="<?=$field_name;?>" class="import-change-sbmt select2-list">
							<? foreach ($arParam['LIST'] as $l_k => $l_value):?>
								<? if (!is_array($l_value)):?>
									<option value="<?=$l_k;?>"<?=($arParam['VALUE'] == $l_k) ? ' selected' : ''?>><?=$l_value;?></option>
								<? else:?>
									<optgroup label="<?=$l_value[key($l_value)];?>">
										<? foreach ($l_value as $v_k => $v_value):?>
											<option value="<?=$v_k;?>"<?=($arParam['VALUE'] == $v_k) ? ' selected' : ''?>><?=$v_value;?></option>
										<? endforeach; ?>
									</optgroup>
								<? endif; ?>
							<? endforeach; ?>
						</select>
					<? elseif ($arParam['TYPE'] == 'list_multiple'):?>
						<select multiple name="<?=$field_name;?>[]" class="import-change-sbmt">
							<? foreach ($arParam['LIST'] as $l_k => $l_value):?>
								<option value="<?=$l_k;?>"<?=in_array($l_k, (array)$arParam['VALUE']) ? ' selected' : ''?>><?=$l_value;?></option>
							<? endforeach; ?>
						</select>
					<? elseif ($arParam['TYPE'] == 'number'):?>
						<input type="text" name="<?=$field_name;?>" value="<?=$arParam['VALUE'];?>" size="5" class="import-change-sbmt"/>
					<? elseif ($arParam['TYPE'] == 'string'):?>
						<input type="text" name="<?=$field_name;?>" value="<?=$arParam['VALUE'];?>" placeholder="<?=$arParam['PLACEHOLDER'];?>" size="20" class="import-change-sbmt"/>
					<? endif; ?>
				</td>
			</tr>
		<? else:?>
			<?=$arParam['HTML'];?>
		<? endif; ?>
	<?}
}
// endregion
?>

<tr class="heading" align="center">
	<td colspan="2"><b><?=GetMessage("ACRIT_IMPORT_STEP2_SUBTIT1");?></b></td>
</tr>
<tr>
	<td colspan="2">
		<div class="adm-info-message-center">
			<div class="adm-info-message">
				<a href="https://www.acrit-studio.ru/technical-support/nastroyka-modulya-universalnyy-import/opisanie-poley-dlya-importa/"
				   target="_blank"><?=GetMessage("ACRIT_IMPORT_STEP2_HELP_FIELDS");?></a><br/>
				<a href="https://www.acrit-studio.ru/technical-support/nastroyka-modulya-universalnyy-import/optsii-i-modifikatory-dlya-poley/"
				   target="_blank"><?=GetMessage("ACRIT_IMPORT_STEP2_HELP_OPTIONS");?></a><br/>
				<a href="https://www.acrit-studio.ru/~84r-OnBeforeProcessRowInSaveIBData" target="_blank"><?=GetMessage("ACRIT_IMPORT_STEP2_HELP_API");?></a><br/>
			</div>
		</div>
	</td>
</tr>

<?if (in_array($arProfile['TYPE'], ['xml', 'yml', 'iblock'], false)) {?>
	<tr>
		<td>
			<?=\Acrit\Core\Helper::showHint(GetMessage("ACRIT_IMPORT_SCAN_ALL_ITEMS_TO_FIND_FIELDS_HINT"));?>
			<?=GetMessage("ACRIT_IMPORT_SCAN_ALL_ITEMS_TO_FIND_FIELDS")?>
		</td>
		<td>
			<input type="hidden" name="PROFILE[SCAN_ALL_ITEMS_TO_FIND_FIELDS]" value="N">
			<input type="checkbox" name="PROFILE[SCAN_ALL_ITEMS_TO_FIND_FIELDS]" value="Y"<?=($arProfile['SCAN_ALL_ITEMS_TO_FIND_FIELDS'] == "Y") ? ' checked' : ''?>>
		</td>
	</tr>
<?}?>

<?
/**
 * @var array{
 *     'MAIN': array{'ITEMS': array}, 'PROPS': array{'ITEMS': array}, 'CATEGORIES': array{'ITEMS': array}, 'SEO': array{'ITEMS': array},
 *     'CATALOG': array{'ITEMS': array}, 'PRICES': array{'ITEMS': array}, 'STORES': array{'ITEMS': array}, 'OFFERS': array{'ITEMS': array}, 'CATEG_PARAMS': array{'ITEMS': array}
 *  } $arIBlockFields
 * @var array $arProfile
 * @var array $arFieldsParams
 */

if (!empty($arSourceFields)): ?>
	<? foreach ($arSourceFields as $k => $arSField): ?>
		<tr>
			<td><?=GetMessage("ACRIT_IMPORT_POLE")?>
				<strong><?=$arSField['NAME'];?></strong><?=$arSField['EXAMPLE'] ? ' ("' . $arSField['EXAMPLE'] . '")' : '';?>:
			</td>
			<td>
				<select name="FLDSMAP[<?=$arSField['ID'];?>]" class="acrit-import-store-fields">
					<option value=""><?=GetMessage("ACRIT_IMPORT_IBLOCK_FIELDS_NO")?></option>
					<option value="CREATE_PROP__STRING"<?=$arSField['SAVED_FIELD'] == 'CREATE_PROP__STRING' ? ' selected' : '';?>><?=GetMessage("ACRIT_IMPORT_IBLOCK_FIELDS_CREATE_STRING")?></option>
					<option value="CREATE_PROP__STRING__MULT"<?=$arSField['SAVED_FIELD'] == 'CREATE_PROP__STRING__MULT' ? ' selected' : '';?>><?=GetMessage("ACRIT_IMPORT_IBLOCK_FIELDS_CREATE_STRING_MULT")?></option>
					<? foreach ($arIBlockFields as $ibg_k => $arIBGroup): ?>
						<optgroup label="<?=$arIBGroup['TITLE'];?>">
							<? foreach ($arIBGroup['ITEMS'] as $ibf_k => $arIBField): ?>
								<option value="<?=$arIBField['ID'];?>"<?=$arIBField['ID'] == $arSField['SAVED_FIELD'] ? ' selected' : '';?>><?=$arIBField['NAME'];?></option>
							<? endforeach; ?>
						</optgroup>
					<? endforeach; ?>
				</select>
				<?
				$display = false;
				foreach ($arSField['PARAMS'] as $name => $p_value):
					if ((!is_array($p_value) && ($p_value != $arFieldsParams[$name]['DEFAULT'])) ||
						(is_array($p_value) && (!empty(array_filter($p_value)) || $p_value["checked"] == "Y"))):
						$display = true;
					endif;
				endforeach;
				?>
				<div class="field-params"<?=!$display ? ' style="display: none;"' : '';?>>
					<? foreach ($arFieldsParams as $name => $arFieldsParam):
						?>
						<? $field_name = 'FLDSMAP_PARAMS[' . $arSField['ID'] . '][' . $name . ']'; ?>
						<div class="field-params-item">
							<?=\Acrit\Core\Helper::showHint($arFieldsParam['HINT']);?>
							<? if ($arFieldsParam['TYPE'] == 'boolean'): ?>
								<? if ($name == "num_round"): ?>
									<input type="checkbox" name="<?=$field_name;?>" id="<?='fldsmap_params_' . $arSField['ID'] . '_' . $name;?>"
									       value="Y"<?=(isset($arSField['PARAMS'][$name]['checked']) && $arSField['PARAMS'][$name]['checked'] == "Y") ? ' checked' : ''?>>
									<label for="<?='fldsmap_' . $arSField['ID'] . '_params_' . $name;?>"><?=$arFieldsParam['LABEL'];?></label>
									<input type="hidden" name="<?=$field_name . "[checked]"?>" class="hidden-numround-checked"
									       value="<?=(isset($arSField['PARAMS'][$name]['checked']) && $arSField['PARAMS'][$name]['checked'] == "Y") ? 'Y' : ''?>">

									<div class="fields-add-params fields-add-params-s"
									     style="<?=(isset($arSField['PARAMS'][$name]['checked']) && $arSField['PARAMS'][$name]['checked'] == "Y" ? " display: block;" : " display: none;")?>">
										<div class="field-params-item" style="display: inline-block">
											<select name="<?=$field_name?>[ADD_PARAMS]">
												<? foreach ($arFieldsParam['ADD_PARAMS'] as $key => $value): ?>
													<option name="<?=$field_name . "[" . $key . "]"?>"
													        value="<?=$key?>" <?=(isset($arSField['PARAMS'][$name]['ADD_PARAMS']) && $arSField['PARAMS'][$name]['ADD_PARAMS'] == $key ? 'selected' : '');?>><?=$value?></option>
												<? endforeach; ?>
											</select>
										</div>
										<div class="field-params-item" style="display: inline-block">
											<select name="<?=$field_name?>[ADD_PRECISION]">
												<? foreach ($arFieldsParam['ADD_PRECISION'] as $key => $value): ?>
													<option name="<?=$field_name . "[" . $key . "]"?>"
													        value="<?=$key?>" <?=(isset($arSField['PARAMS'][$name]['ADD_PRECISION']) && $arSField['PARAMS'][$name]['ADD_PRECISION'] == $key ? 'selected' : '');?>><?=$value?></option>
												<? endforeach; ?>
											</select>
										</div>
									</div>
								<? elseif ($name == "work_picture"): ?>
									<input type="checkbox" name="<?=$field_name;?>" id="<?='fldsmap_params_' . $arSField['ID'] . '_' . $name;?>"
									       value="Y"<?=(isset($arSField['PARAMS'][$name]['checked']) && $arSField['PARAMS'][$name]['checked'] == "Y") ? ' checked' : ''?>>
									<label for="<?='fldsmap_' . $arSField['ID'] . '_params_' . $name;?>"><?=$arFieldsParam['LABEL'];?></label>
									<input type="hidden" name="<?=$field_name . "[checked]"?>" class="hidden-workpicture-checked"
									       value="<?=(isset($arSField['PARAMS'][$name]['checked']) && $arSField['PARAMS'][$name]['checked'] == "Y") ? 'Y' : ''?>">

									<div class="fields-add-params fields-add-params-s"
									     style="<?=(isset($arSField['PARAMS'][$name]['checked']) && $arSField['PARAMS'][$name]['checked'] == "Y" ? " display: block;" : " display: none;")?>">
										<div class="field-params-item">
											<label for="width"><?=$arFieldsParam['ADD_PARAMS']['WIDTH']?></label>
											<input type="text" name="<?=$field_name . "[width]"?>"
											       value="<?=(isset($arSField['PARAMS'][$name]['width']) && $arSField['PARAMS'][$name]['width'] != "N" ? $arSField['PARAMS'][$name]['width'] : '');?>"
											       size="4"/>
											<label for="height"><?=$arFieldsParam['ADD_PARAMS']['HEIGHT']?></label>
											<input type="text" name="<?=$field_name . "[height]"?>"
											       value="<?=(isset($arSField['PARAMS'][$name]['height']) && $arSField['PARAMS'][$name]['height'] != "N" ? $arSField['PARAMS'][$name]['height'] : '');?>"
											       size="4"/>
										</div>
										<div class="field-params-item" style="margin: 5px 0px;">
											<div style="display: block">
												<label><?=$arFieldsParam['ADD_PARAMS']['PROCESS_TYPE']?></label>
											</div>
											<div style="display: inline-block">
												<input type="radio" id="prop<?=md5($field_name)?>" name="<?=$field_name . "[process_type]"?>" value="proportional"
													<?=(!isset($arSField['PARAMS'][$name]['process_type']) ||
													$arSField['PARAMS'][$name]['process_type'] == 'N' || $arSField['PARAMS'][$name]['process_type'] == 'proportional' || !$arSField['PARAMS'][$name]['process_type'] ? 'checked' : '')?>>
												<label for="prop<?=md5($field_name)?>"><?=$arFieldsParam['ADD_PARAMS']['PROPORTIONAL']?></label>
											</div>
											<div style="display: inline-block">
												<input type="radio" id="cut<?=md5($field_name)?>" name="<?=$field_name . "[process_type]"?>"
												       value="cut" <?=(isset($arSField['PARAMS'][$name]['process_type']) && $arSField['PARAMS'][$name]['process_type'] == 'cut' ? 'checked' : '')?>>
												<label for="cut<?=md5($field_name)?>"><?=$arFieldsParam['ADD_PARAMS']['CUT']?></label>
											</div>
										</div>
										<div class="field-params-item">
											<label>
												<?=$arFieldsParam['ADD_PARAMS']['QUALITY']?>
												<input type="text" name="<?=$field_name . "[quality]"?>"
												       value="<?=(isset($arSField['PARAMS'][$name]['quality']) && $arSField['PARAMS'][$name]['quality'] != "N" ? $arSField['PARAMS'][$name]['quality'] : '');?>"
												       placeholder="85" size="4"/>
											</label>
										</div>
										<div class="field-params-item">
											<label>
												<?=$arFieldsParam['ADD_PARAMS']['FILE_EXTENSION']?>
												<input type="text" name="<?=$field_name . "[file_extension]"?>"
												       value="<?=(isset($arSField['PARAMS'][$name]['file_extension']) && $arSField['PARAMS'][$name]['file_extension'] != "N" ? $arSField['PARAMS'][$name]['file_extension'] : '');?>"
												       size="4" placeholder="jpg"/>
											</label>
										</div>
									</div>
								<? else: ?>
									<label>
										<input type="checkbox" name="<?=$field_name;?>" id="<?='fldsmap_params_' . $arSField['ID'] . '_' . $name;?>" value="Y"<?=($arSField['PARAMS'][$name] == "Y") ? ' checked' : ''?>>
										<?=$arFieldsParam['LABEL'];?>
									</label>
								<? endif; ?>
							<? elseif ($arFieldsParam['TYPE'] == 'list'): ?>
								<label for="<?='fldsmap_params_' . $arSField['ID'] . '_' . $name;?>"><?=$arFieldsParam['LABEL'];?></label>
								<select name="<?=$field_name;?>">
									<option value="">-</option>
									<? foreach ($arFieldsParam['LIST'] as $l_k => $l_value): ?>
										<option value="<?=$l_k;?>"<?=($arSField['PARAMS'][$name] == $l_k) ? ' selected' : ''?>><?=$l_value;?></option>
									<? endforeach; ?>
								</select>
							<? elseif ($arFieldsParam['TYPE'] == 'num'): ?>
								<label for="<?='fldsmap_' . $arSField['ID'] . '_params_' . $name;?>"><?=$arFieldsParam['LABEL'];?></label>
								<input type="text" name="<?=$field_name;?>" value="<?=$arSField['PARAMS'][$name];?>" size="5"/>
							<? elseif ($arFieldsParam['TYPE'] == 'string'): ?>
								<label for="<?='fldsmap_' . $arSField['ID'] . '_params_' . $name;?>"><?=$arFieldsParam['LABEL'];?></label>
								<? if (!$arFieldsParam['MULTIPLE']): ?>
									<input type="text" name="<?=$field_name;?>" value="<?=htmlspecialcharsbx($arSField['PARAMS'][$name])?>" placeholder="<?=$arFieldsParam['PLACEHOLDER'];?>" size="30"/>
								<? else: ?>
									<div class="multiple">
										<div class="values">
											<? if (!$arSField['PARAMS'][$name] || empty(array_filter($arSField['PARAMS'][$name]))): ?>
												<div class="value-item">
													<input type="text" name="<?=$field_name;?>[]" value="" placeholder="<?=$arFieldsParam['PLACEHOLDER'];?>" size="30"/>
													<a href="#" class="del" style="display:none;"><?=GetMessage("ACRIT_IMPORT_UDALITQ")?></a>
												</div>
											<? else: ?>
												<? $i = 0; ?>
												<? foreach ($arSField['PARAMS'][$name] as $value): ?>
													<? if ($value): ?>
														<div class="value-item">
															<input type="text" name="<?=$field_name;?>[]" value="<?=$value;?>" placeholder="<?=$arFieldsParam['PLACEHOLDER'];?>" size="30"/>
															<a href="javascript:void(0);" class="del"<?=(!$i ? ' style="display:none;"' : '');?>><?=GetMessage("ACRIT_IMPORT_UDALITQ")?></a>
														</div>
														<? $i++; ?>
													<? endif; ?>
												<? endforeach; ?>
											<? endif; ?>
										</div>
										<div class="add-block"><a href="javascript:void(0);" class="add"><?=GetMessage("ACRIT_IMPORT_DOBAVITQ")?></a></div>
									</div>
								<? endif; ?>
							<? endif; ?>
						</div>
					<? endforeach; ?>
				</div>
				<div class="field-params-link"><a href="javascript:void(0);" title="<?=GetMessage("ACRIT_IMPORT_PARAMETRY_OBRABOTKI_HINT")?>"><?=GetMessage("ACRIT_IMPORT_PARAMETRY_OBRABOTKI")?></a></div>
			</td>
		</tr>
	<? endforeach; ?>
<? endif; ?>

<tr>
	<td colspan="2">
		<div id="tree_actions" style="position: relative; z-index: 1;"></div>
		<?
		/*$arCondParams = array(
			'FORM_NAME' => 'sale_discount_form',
			'CONT_ID' => 'tree_actions',
			'JS_NAME' => 'JSSaleAct',
			'PREFIX' => 'actrl',
			'INIT_CONTROLS' => array(
				'SITE_ID' => SITE_ID,
				'CURRENCY' => CSaleLang::GetLangCurrency(SITE_ID),
			),
			'SYSTEM_MESSAGES' => array(
				'SELECT_CONTROL' => GetMessage('BT_SALE_DISCOUNT_ACTIONS_SELECT_CONTROL'),
				'ADD_CONTROL' => GetMessage('BT_SALE_DISCOUNT_ACTIONS_ADD_CONTROL'),
				'DELETE_CONTROL' => GetMessage('BT_SALE_DISCOUNT_ACTIONS_DELETE_CONTROL'),
			),
		);
		$arActions = Array
(
	'CLASS_ID' => 'CondGroup',
	'DATA' => Array
		(
			'All' => 'AND'
		),
	'CHILDREN' => Array
		(
			0 => Array
				(
					'CLASS_ID' => 'GiftCondGroup',
					'DATA' => Array
						(
							'All' => 'AND'
						),

					'CHILDREN' => Array
						(
							0 => Array
								(
									'CLASS_ID' => 'GifterCondIBElement',
									'DATA' => Array
										(
											'Type' => 'one',
											'Value' => Array
												(
													0 => 37,
													1 => 38,
													2 => 36,
													3 => 40,
												),

										)

								)

						)

				)

		)

);
		$obCond = new CSaleActionTree();
		$boolCond = $obCond->Init(BT_COND_MODE_DEFAULT, BT_COND_BUILD_SALE_ACTIONS, $arCondParams);
		if (!$boolCond)
		{
			if ($ex = $APPLICATION->GetException())
				echo $ex->GetString()."<br>";
		}
		else
		{
			$obCond->Show($arActions);
		}*/
		?>
	</td>
</tr>

<? if (!empty($arSourceFields)): ?>
	<tr class="acrit-import-bgr-lightred">
		<td>
			<?=\Acrit\Core\Helper::showHint(GetMessage("ACRIT_IMPORT_POLE_IDENTIFIKATOR_E_HINT"))?>
			<?=GetMessage("ACRIT_IMPORT_POLE_IDENTIFIKATOR_E")?>
		</td>
		<td>
			<select name="PROFILE[ELEMENT_IDENTIFIER]">
				<? foreach ($arSourceFields as $k => $arSField): ?>
					<option value="<?=$arSField['ID'];?>"<?=$arProfile['ELEMENT_IDENTIFIER'] == $arSField['ID'] ? ' selected' : '';?>><?=$arSField['NAME'];?></option>
				<? endforeach; ?>
			</select>
		</td>
	</tr>
<? endif; ?>



