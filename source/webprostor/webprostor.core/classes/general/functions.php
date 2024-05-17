<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/webprostor.core/prolog.php");

use Bitrix\Main\Localization\Loc,
	Bitrix\Main\Config\Option,
	Bitrix\Main\Loader;

Class CWebprostorCoreFunctions
{
	public static function dump($data)
	{
		echo '<pre>';
		var_dump($data);
		echo '</pre>';
	}
	
	public static function GenerateRandomCode($lenght = false)
	{
		$min_lenght= 0;
		$max_lenght = 100;
		$bigL = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
		$smallL = "abcdefghijklmnopqrstuvwxyz";
		$number = "0123456789";
		$bigB = str_shuffle($bigL);
		$smallS = str_shuffle($smallL);
		$numberS = str_shuffle($number);
		$subA = substr($bigB,0,5);
		$subB = substr($bigB,6,5);
		$subC = substr($bigB,10,5);
		$subD = substr($smallS,0,5);
		$subE = substr($smallS,6,5);
		$subF = substr($smallS,10,5);
		$subG = substr($numberS,0,5);
		$subH = substr($numberS,6,5);
		$subI = substr($numberS,10,5);
		$RandCode1 = str_shuffle($subA.$subG.$subD.$subB.$subH.$subF.$subC.$subI.$subE);
		$RandCode2 = str_shuffle($RandCode1);
		$RandCode = $RandCode1.$RandCode2;
		
		if ($lenght>$min_lenght && $lenght<$max_lenght)
		{
			$result = substr($RandCode,0,$lenght);
		}
		else
		{
			$result = $RandCode;
		}
		
		return $result;
	}
	
	public static function GetCorrectWord($num, $str1, $str2, $str3)
	{
		$val = $num % 100;

		if ($val > 10 && $val < 20) return $num .' '. $str3;
		else {
			$val = $num % 10;
			if ($val == 1) return $num .' '. $str1;
			elseif ($val > 1 && $val < 5) return $num .' '. $str2;
			else return $num .' '. $str3;
		}
	}
	
	public static function ReturnBytes($val)
	{
		$val = trim($val);
		$last = strtolower($val[strlen($val)-1]);
		switch($last) {
			case 'g':
				$val *= 1024;
			case 'm':
				$val *= 1024;
			case 'k':
				$val *= 1024;
		}

		return $val;
	}
	
	public static function mb_ucfirst($str)
	{
		$result = '';
		
		if(is_string($str) && strlen($str) > 0)
			$result = mb_strtoupper(mb_substr($str, 0, 1)).mb_substr($str, 1);

		return $result;
	}
	
	public static function ShowFormFields($arFields = Array())
	{
		global $USER;
		if(is_array($arFields) && count($arFields))
		{
			foreach($arFields as $arSection)
			{
			if(isset($arSection["LABEL"]) && $arSection["LABEL"])
			{
			?>
			<tr class="heading">
				<td colspan="2"><?=$arSection["LABEL"]?></td>
			</tr>
			<?
			}
			if(isset($arSection["ITEMS"]) && is_array($arSection["ITEMS"]) && count($arSection["ITEMS"]))
			{
			foreach($arSection["ITEMS"] as $field)
			{
			?>
			<tr<?if($field["ID"]):?> id="<?=$field["ID"]?>_tr"<?endif;?><?if($field["REQUIRED"]=="Y"):?> class="adm-detail-required-field"<?endif;?><?if($field["DISPLAY"]=="N"):?> style="display: none"<?endif;?>>
				<?if($field["LABEL"]):?>
				<td width="50%"<?if($field["ID"]):?> id="<?=$field["ID"]?>_td_label"<?endif;?> class="adm-detail-content-cell-l"><?if($field["REQUIRED"]=="Y" && isset($field["TYPE"])):?><span style="cursor: pointer;" data-hint="<?=Loc::getMessage('WEBPROSTOR_CORE_FUNCTIONS_REQUIRED');?>" data-hint-no-icon>&#9888;</span> <?endif;?><?=$field["LABEL"]?>:</td>
				<?endif;?>
				<td<?if(!$field["LABEL"]):?> colspan="2"<?endif;?><?if($field["ID"]):?> id="<?=$field["ID"]?>_td_value"<?endif;?> class="adm-detail-content-cell-r">
					<?
					$isAdmin = $USER->IsAdmin();
					switch($field["TYPE"])
					{
						case("EDITOR"):
							global $APPLICATION;
						?>
						<?$APPLICATION->IncludeComponent(
							"bitrix:fileman.light_editor",
							"",
							Array(
								"CONTENT" => html_entity_decode($field["VALUE"]),
								"INPUT_NAME" => $field["CODE"],
								"INPUT_ID" => $field["ID"],
								"WIDTH" => "100%",
								"HEIGHT" => "300px",
								"RESIZABLE" => "Y",
								"AUTO_RESIZE" => "Y",
								"VIDEO_ALLOW_VIDEO" => "N",
								"USE_FILE_DIALOGS" => "N",
								"ID" => "",	
								"JS_OBJ_NAME" => ""
							)
						);
						?>
						<?
						break;
						case("PICTURE"):
						?>
						<?
						echo CFile::InputFile("PICTURE", $field["PARAMS"]["SIZE"]?$field["PARAMS"]["SIZE"]:20, $field["VALUE"], false, $field["PARAMS"]["MAX_FILE_SIZE"]?$field["PARAMS"]["MAX_FILE_SIZE"]:0, $field["PARAMS"]["FILE_TYPE"]?$field["PARAMS"]["FILE_TYPE"]:"IMAGE", "class=typefile", $field["PARAMS"]["DESCRIPTION_SIZE"]?$field["PARAMS"]["DESCRIPTION_SIZE"]:0, "class=typeinput", "", true, true);
						if (!is_array($field["VALUE"]) && strlen($field["VALUE"])>0 || is_array($field["VALUE"]) && count($field["VALUE"]) > 0):
							?>
							<input type="hidden" name="<?=$field["CODE"]?>_old" value="<?=$field["VALUE"]?>" /><br>
							<?
							if($field["PARAMS"]["SHOW_IMAGE"] != "N")
							{
								echo CFile::ShowImage($field["VALUE"], $field["PARAMS"]["IMAGE_WIDTH"]?$field["PARAMS"]["IMAGE_WIDTH"]:200, $field["PARAMS"]["IMAGE_HEIGHT"]?$field["PARAMS"]["IMAGE_HEIGHT"]:200, "border=0", "", true);
							}
						endif;
						?>
						<?
						break;
						case("USER_GROUP"):
						$arUserGroupList = array();

						$rsUserGroups = CGroup::GetList(($by="sort"), ($order="asc"));
						while ($arGroup = $rsUserGroups->Fetch())
						{
							$arUserGroupList[] = array(
								'ID' => intval($arGroup['ID']),
								'NAME' => htmlspecialcharsbx($arGroup['NAME']),
							);
						}
						?>
					<select name="<?=$field["CODE"]?>" id="<?=$field["ID"]?$field["ID"]:strtolower($field["CODE"])?>" multiple size="8" class="select-search">
					<?
					foreach ($arUserGroupList as &$arOneGroup)
					{
						?><option value="<? echo $arOneGroup["ID"]; ?>" <?if (in_array($arOneGroup["ID"], $field["VALUE"])) echo " selected"?>><? echo "[".$arOneGroup["ID"]."] ".htmlspecialcharsbx($arOneGroup["NAME"]); ?></option><?
					}
					if (isset($arOneGroup))
						unset($arOneGroup);
					?>
					</select>
					<?if($field["PARAMS"]["CHECK_ALL"] == "Y" && $field["PARAMS"]["MULTIPLE"] == "Y") {?>
					&nbsp;<label for="<?=$field["ID"]?>_checkbox"><input type="checkbox" id="<?=$field["ID"]?>_checkbox" > <?=Loc::getMessage("WEBPROSTOR_CORE_FUNCTIONS_SELECT_ALL")?></label>
					<script>
					$(document).ready(function(){
						$('#<?=$field["ID"]?>_checkbox').click(function()
						{
							if($(this).is(':checked'))
							{
								$('select#<?=$field["ID"]?>').find('option').prop("selected",true);
								$('select#<?=$field["ID"]?>').trigger('change');
							}
							else
							{
								$('select#<?=$field["ID"]?>').find('option').prop("selected",false);
								$('select#<?=$field["ID"]?>').trigger('change');
							}
						});
					});
					</script>
					<? } ?>
						<?
						break;
						case("IBLOCK"):
							echo GetIBlockDropDownListEx(
								intVal($field["VALUE"]),
								$field["PARAMS"]["IBLOCK_TYPE_ID"]?$field["PARAMS"]["IBLOCK_TYPE_ID"]:"IBLOCK_TYPE_ID",
								$field["CODE"]?$field["CODE"]:"IBLOCK_ID",
								array(
									"MIN_PERMISSION" => $field["PARAMS"]["MIN_PERMISSION"],
								),
								'',
								'',
								'class="adm-detail-iblock-types select-search"',
								'class="adm-detail-iblock-list select-search"'
							);
							if($field['REFRESH'] == 'Y')
								echo '<button class="ui-btn ui-btn-sm" type="submit" name="apply" value="Y">'.Loc::getMessage("WEBPROSTOR_CORE_FUNCTIONS_APPLY").'</button>';
							break;
						break;
						case("IBLOCK_TREE"):
							?>
							<?if($field["DISABLED"] == "Y") {?><input type="hidden" name="<?=$field["CODE"]?>" value="<?=$field["VALUE"]?>" /><? } ?>
							<select name="<?=$field["CODE"]?>" id="<?=$field["ID"]?$field["ID"]:strtolower($field["CODE"])?>" class="<?=$field["CLASS"]?$field["CLASS"]:"select-search"?>"<?if($field["DISABLED"] == "Y") {?> disabled<? } ?>>
								<?
								if($field["PARAMS"]["ADD_ZERO"] == "Y")
								{
								?>
									<option value=""><?=$field["PARAMS"]["ZERO_LABEL"]?$field["PARAMS"]["ZERO_LABEL"]:Loc::getMessage('WEBPROSTOR_CORE_FUNCTIONS_SELECT_IBLOCK')?></option>
								<?
								}
								$db_iblock_type = CIBlockType::GetList();
								while($ar_iblock_type = $db_iblock_type->Fetch())
								{
									if($arIBType = CIBlockType::GetByIDLang($ar_iblock_type["ID"], LANG))
									{
										$resIblocks = CIBlock::GetList(Array("NAME"=>"ASC"), Array("TYPE"=>$ar_iblock_type["ID"], "MIN_PERMISSION" => $field["PARAMS"]["MIN_PERMISSION"]));
										if(intVal($resIblocks->SelectedRowsCount())>0)
										{
											?>
											<optgroup label="<?echo htmlspecialcharsEx($arIBType["NAME"]);?> [<?=$ar_iblock_type["ID"]?>]">
											<?
											while($iblock = $resIblocks->Fetch()):
											?>
											<option value="<?=$iblock["ID"]?>" <?if($field["VALUE"]==$iblock["ID"]) echo 'selected';?>><?=htmlspecialcharsbx($iblock["NAME"])?> [<?=$iblock["ID"];?>]</option>
											<?
											endwhile;
											?>
											</optgroup>
											<?
									   }
									}
								}
								?>
							</select>
							<?
						break;
						case("BLOG"):
							if (CModule::IncludeModule('blog'))
							{
							?>
							<?if($field["DISABLED"] == "Y") {?><input type="hidden" name="<?=$field["CODE"]?>" value="<?=$field["VALUE"]?>" /><? } ?>
							<select name="<?=$field["CODE"]?>" id="<?=$field["ID"]?$field["ID"]:strtolower($field["CODE"])?>" class="<?=$field["CLASS"]?$field["CLASS"]:"select-search"?>"<?if($field["DISABLED"] == "Y") {?> disabled<? } ?>>
								<?
								if($field["PARAMS"]["ADD_ZERO"] == "Y")
								{
								?>
									<option value=""><?=$field["PARAMS"]["ZERO_LABEL"]?$field["PARAMS"]["ZERO_LABEL"]:Loc::getMessage('WEBPROSTOR_CORE_FUNCTIONS_SELECT_IBLOCK')?></option>
								<?
								}
								$dbBlogs = CBlog::GetList(["NAME" => "ASC"], [], false, false, ["ID", "NAME"]);
								while ($arBlog = $dbBlogs->Fetch())
								{
									?>
									<option value="<?=$arBlog["ID"]?>" <?if($field["VALUE"]==$arBlog["ID"]) echo 'selected';?>><?=htmlspecialcharsbx($arBlog["NAME"])?> [<?=$arBlog["ID"];?>]</option>
									<?
								}
								?>
							</select>
							<?
							}
						break;
						case("HIGHLOAD_BLOCK"):
							if (CModule::IncludeModule('highloadblock'))
							{
								if($field["PARAMS"]["ADD_ZERO"] != "N")
									$HLblocks = Array('' => $field["PARAMS"]["ZERO_LABEL"]?$field["PARAMS"]["ZERO_LABEL"]:Loc::getMessage('WEBPROSTOR_CORE_FUNCTIONS_SELECT_HB'));
								else
									$HLblocks = Array();
								$rsData = \Bitrix\Highloadblock\HighloadBlockTable::getList();
								while($hldata = $rsData->Fetch())
								{
									$rights = \Bitrix\HighloadBlock\HighloadBlockRightsTable::getOperationsName($hldata["ID"]);
									
									if(is_array($rights) && in_array('hl_element_write', $rights) || $isAdmin)
									{
										$HLblocks[$hldata["ID"]] = htmlspecialcharsbx($hldata["NAME"]).' ['.$hldata["TABLE_NAME"].']';
									}
									
									unset($rights);
								}
						?>
					<?if($field["DISABLED"] == "Y") {?><input type="hidden" name="<?=$field["CODE"]?>" value="<?=$field["VALUE"]?>" /><? } ?>
					<select name="<?=$field["CODE"]?>" id="<?=$field["ID"]?$field["ID"]:strtolower($field["CODE"])?>"<?if($field["REFRESH"] == "Y") {?> onchange="this.form.submit()"<? } ?> class="<?=$field["CLASS"]?$field["CLASS"]:"select-search"?>"<?if($field["PARAMS"]["MULTIPLE"] == "Y") {?> multiple<? } ?><?if($field["DISABLED"] == "Y") {?> disabled<? } ?>>
						<?foreach($HLblocks as $id => $value):?>
						<option value="<?=$id?>" <?if($field["VALUE"]==$id || (is_array($field["VALUE"]) && in_array($id, $field["VALUE"]))) echo 'selected';?>><?=$value?></option>
						<?endforeach;?>
					</select>
					<?if($field["PARAMS"]["CHECK_ALL"] == "Y" && $field["PARAMS"]["MULTIPLE"] == "Y") {?>
					&nbsp;<label for="<?=$field["ID"]?>_checkbox"><input type="checkbox" id="<?=$field["ID"]?>_checkbox" > <?=Loc::getMessage("WEBPROSTOR_CORE_FUNCTIONS_SELECT_ALL")?></label>
					<script>
					$(document).ready(function(){
						$('#<?=$field["ID"]?>_checkbox').click(function()
						{
							if($(this).is(':checked'))
							{
								$('select#<?=$field["ID"]?>').find('option').prop("selected",true);
								$('select#<?=$field["ID"]?>').trigger('change');
							}
							else
							{
								$('select#<?=$field["ID"]?>').find('option').prop("selected",false);
								$('select#<?=$field["ID"]?>').trigger('change');
							}
						});
					});
					</script>
					<? } ?>
						<?
							}
						break;
						case("SITE"):
						case("SALE_PERSON_TYPE"):
						case("SALE_ORDER_PROPS"):
						case("SALE_STATUS"):
						case("SALE_DELIVERY_SERVICE"):
						case("SALE_PAY_SYSTEM"):
						case("SECTION"):
						case("ELEMENT"):
						case("ELEMENT_PICTURE"):
						case("ELEMENT_TEXT"):
						case("PROPERTY"):
						case("PRICE"):
						case("CURRENCY"):
						case("VAT"):
						case("MEASURE"):
						case("CATALOG_TYPE"):
						case("LIST"):
						case("SELECT"):
						case("HIGHLOAD_ENTITES"):
							if($field["TYPE"] == 'SITE')
							{
								$arSort = Array("sort"=>"asc");
								$arFilter = [];
								if(isset($field["PARAMS"]["ACTIVE"]))
								{
									$arFilter['ACTIVE'] = $field["PARAMS"]["ACTIVE"];
								}
								$queryList = \Bitrix\Main\SiteTable::getList(array(
									'order' => $arSort,
									'select' => ['NAME', 'ID'],
									'filter' => $arFilter,
								))->fetchAll();
								foreach ($queryList as $site) {
									if($field["PARAMS"]["IS_SHOP"] == 'Y')
									{
										$siteIsShop = Option::get("sale", "SHOP_SITE_".$site["ID"], "");
										if($siteIsShop == $site["ID"])
											$field["ITEMS"][$site['ID']] = htmlspecialcharsbx($site["NAME"]).' ['.$site['ID'].']';
									}
									else
										$field["ITEMS"][$site['ID']] = htmlspecialcharsbx($site["NAME"]).' ['.$site['ID'].']';
								}
								unset($queryList, $arSort, $arFilter);
							}
							elseif($field["TYPE"] == 'SALE_PERSON_TYPE')
							{
								$arSort = Array("sort"=>"asc");
								$arFilter = [];
								if(isset($field["PARAMS"]["ACTIVE"]))
								{
									$arFilter['ACTIVE'] = $field["PARAMS"]["ACTIVE"];
								}
								if(isset($field["PARAMS"]["SITE_ID"]))
								{
									$arFilter['=PERSON_TYPE_SITE.SITE_ID'] = $field["PARAMS"]["SITE_ID"];
								}
								$queryList = \Bitrix\Sale\Internals\PersonTypeTable::getList(array(
									'order' => $arSort,
									'select' => ['NAME', 'ID'],
									'filter' => $arFilter,
								))->fetchAll();
								foreach ($queryList as $personType) {
									$field["ITEMS"][$personType['ID']] = htmlspecialcharsbx($personType["NAME"]).' ['.$personType['ID'].']';
								}
								unset($queryList, $arSort, $arFilter);
							}
							elseif($field["TYPE"] == 'SALE_ORDER_PROPS')
							{
								$arSort = Array("sort"=>"asc");
								$arFilter = [];
								if(isset($field["PARAMS"]["ACTIVE"]))
								{
									$arFilter['ACTIVE'] = $field["PARAMS"]["ACTIVE"];
								}
								if(isset($field["PARAMS"]["TYPE"]))
								{
									$arFilter['TYPE'] = $field["PARAMS"]["TYPE"];
								}
								if(isset($field["PARAMS"]["PERSON_TYPE_ID"]))
								{
									$arFilter['PERSON_TYPE_ID'] = $field["PARAMS"]["PERSON_TYPE_ID"];
								}
								$queryList = \Bitrix\Sale\Internals\OrderPropsTable::getList(array(
									'order' => $arSort,
									'select' => ['NAME', 'ID'],
									'filter' => $arFilter,
								))->fetchAll();
								foreach ($queryList as $orderProps) {
									$field["ITEMS"][$orderProps['ID']] = htmlspecialcharsbx($orderProps["NAME"]).' ['.$orderProps['ID'].']';
								}
								unset($queryList, $arSort, $arFilter);
							}
							elseif($field["TYPE"] == 'SALE_STATUS')
							{
								$arSort = Array('STATUS.SORT'=>'ASC');
								$arFilter = ['LID'=>LANGUAGE_ID];
								if(isset($field["PARAMS"]["TYPE"]))
								{
									$arFilter['STATUS.TYPE'] = $field["PARAMS"]["TYPE"];
								}
								if($field["REQUIRED"] != "Y" && $field["PARAMS"]["MULTIPLE"] != 'Y')
								{
									$field["ITEMS"][0] = $field["PARAMS"]['ZERO_LABEL'] ? $field["PARAMS"]['ZERO_LABEL'] : Loc::getMessage('WEBPROSTOR_CORE_FUNCTIONS_NOT_SET');
								}
								$queryList = \Bitrix\Sale\Internals\StatusLangTable::getList(array(
									'order' => $arSort,
									'select' => array('STATUS_ID','NAME'),
									'filter' => $arFilter,
								))->fetchAll();
								foreach ($queryList as $status) {
									$field["ITEMS"][$status['STATUS_ID']] = htmlspecialcharsbx($status["NAME"]).' ['.$status['STATUS_ID'].']';
								}
								unset($queryList, $arSort, $arFilter);
							}
							elseif($field["TYPE"] == 'SALE_DELIVERY_SERVICE')
							{
								if(isset($field["PARAMS"]["SITE_ID"]) && !empty($field["PARAMS"]["SITE_ID"]))
								{
									$order = \Bitrix\Sale\Order::create($field["PARAMS"]["SITE_ID"]);
									$shipmentCollection = $order->getShipmentCollection();
									$shipment = $shipmentCollection->createItem();
									$deliveryServicesAll = \Bitrix\Sale\Delivery\Services\Manager::getRestrictedObjectsList($shipment);
									
									foreach($deliveryServicesAll as $deliveryService)
									{
										$field["ITEMS"][$deliveryService->getId()] = htmlspecialcharsbx($deliveryService->getName()).' ['.$deliveryService->getId().']';
									}
									unset($order, $shipmentCollection, $shipment, $deliveryServicesAll);
								}
								else
								{
									$arSort = Array("sort"=>"asc");
									$arFilter = [];
									if(isset($field["PARAMS"]["ACTIVE"]))
									{
										$arFilter['ACTIVE'] = $field["PARAMS"]["ACTIVE"];
									}
									$queryList = \Bitrix\Sale\Delivery\Services\Table::getList(array(
										'order' => $arSort,
										'select' => array('ID','NAME'),
										'filter' => $arFilter,
									))->fetchAll();
									foreach ($queryList as $delivery) {
										$field["ITEMS"][$delivery['ID']] = htmlspecialcharsbx($delivery["NAME"]).' ['.$delivery['ID'].']';
									}
									unset($queryList, $arSort, $arFilter);
								}
							}
							elseif($field["TYPE"] == 'SALE_PAY_SYSTEM')
							{
								$arSort = Array("sort"=>"asc");
								$arFilter = [];
								if(isset($field["PARAMS"]["ACTIVE"]))
								{
									$arFilter['ACTIVE'] = $field["PARAMS"]["ACTIVE"];
								}
								$queryList = \Bitrix\Sale\PaySystem\Manager::getList(array(
									'order' => $arSort,
									'select' => array('ID','NAME'),
									'filter' => $arFilter,
								))->fetchAll();
								foreach ($queryList as $paySystem) {
									$dbRestriction = \Bitrix\Sale\Internals\ServiceRestrictionTable::getList(array(
										'select' => array('PARAMS'),
										'filter' => array(
											'SERVICE_ID' => $paySystem['ID'],
											'SERVICE_TYPE' => \Bitrix\Sale\Services\PaySystem\Restrictions\Manager::SERVICE_TYPE_PAYMENT
										)
									));
									$restrictions = array();
									while ($restriction = $dbRestriction->fetch())
									{
										if(is_array($restriction['PARAMS']))
											$restrictions = array_merge($restrictions, $restriction['PARAMS']);
									}
									if(isset($field["PARAMS"]["SITE_ID"]) && !empty($field["PARAMS"]["SITE_ID"]) && is_array($restrictions['SITE_ID']) && !in_array($field["PARAMS"]["SITE_ID"], $restrictions['SITE_ID']))
										continue;
									
									if(isset($field["PARAMS"]["PERSON_TYPE_ID"]) && !empty($field["PARAMS"]["PERSON_TYPE_ID"]) && is_array($restrictions['PERSON_TYPE_ID']) && !in_array($field["PARAMS"]["PERSON_TYPE_ID"], $restrictions['PERSON_TYPE_ID']))
										continue;
									
									$field["ITEMS"][$paySystem['ID']] = htmlspecialcharsbx($paySystem["NAME"]).' ['.$paySystem['ID'].']';
								}
								unset($queryList, $arSort, $arFilter, $dbRestriction, $restrictions);
							}
							elseif($field["TYPE"] == 'ELEMENT_PICTURE')
							{
								$field["ITEMS"] = [
									'PREVIEW_PICTURE' => Loc::getMessage('WEBPROSTOR_CORE_FUNCTIONS_PREVIEW_PICTURE'),
									'DETAIL_PICTURE' => Loc::getMessage('WEBPROSTOR_CORE_FUNCTIONS_DETAIL_PICTURE'),
								];
								if($field["REQUIRED"] != "Y" && $field["PARAMS"]["MULTIPLE"] != 'Y')
								{
									$field["ITEMS"] = [0 => $field["PARAMS"]['ZERO_LABEL'] ? $field["PARAMS"]['ZERO_LABEL'] : Loc::getMessage('WEBPROSTOR_CORE_FUNCTIONS_NOT_SET')] + $field["ITEMS"];
								}
							}
							elseif($field["TYPE"] == 'ELEMENT_TEXT')
							{
								$field["ITEMS"] = [
									'PREVIEW_TEXT' => Loc::getMessage('WEBPROSTOR_CORE_FUNCTIONS_PREVIEW_TEXT'),
									'DETAIL_TEXT' => Loc::getMessage('WEBPROSTOR_CORE_FUNCTIONS_DETAIL_TEXT'),
								];
								if($field["REQUIRED"] != "Y" && $field["PARAMS"]["MULTIPLE"] != 'Y')
								{
									$field["ITEMS"] = [0 => $field["PARAMS"]['ZERO_LABEL'] ? $field["PARAMS"]['ZERO_LABEL'] : Loc::getMessage('WEBPROSTOR_CORE_FUNCTIONS_NOT_SET')] + $field["ITEMS"];
								}
							}
							elseif($field["TYPE"] == 'PRICE' && Loader::includeModule("catalog"))
							{
								$arSort = Array('SORT' => 'ASC', 'BASE' => 'DESC');
								if($USER->IsAdmin())
									$arFilter = [];
								else
									$arFilter = ['CAN_ACCESS' => 'Y', 'CAN_BUY' => 'Y'];
								if(isset($field["PARAMS"]["ACTIVE"]))
								{
									$arFilter['ACTIVE'] = $field["PARAMS"]["ACTIVE"];
								}
								if(isset($field["PARAMS"]["BASE"]))
								{
									$arFilter['BASE'] = $field["PARAMS"]["BASE"];
								}
								if($field["REQUIRED"] != "Y" && $field["PARAMS"]["MULTIPLE"] != 'Y')
								{
									$field["ITEMS"][0] = $field["PARAMS"]['ZERO_LABEL'] ? $field["PARAMS"]['ZERO_LABEL'] : Loc::getMessage('WEBPROSTOR_CORE_FUNCTIONS_NOT_SET');
								}
								$queryList = \CCatalogGroup::GetList($arSort, $arFilter);
								while($price = $queryList->GetNext())
								{
									$field["ITEMS"][$price['ID']] = htmlspecialcharsbx($price["NAME_LANG"]).' ['.$price['ID'].']';
								}
								unset($arSort, $arFilter);
							}
							elseif($field["TYPE"] == 'CURRENCY' && Loader::includeModule("currency"))
							{
								$queryList = \CCurrency::GetList(($by = "sort"), ($order = "desc"), LANGUAGE_ID);
								while($currency = $queryList->GetNext())
								{
									$field["ITEMS"][$currency['CURRENCY']] = htmlspecialcharsbx($currency["FULL_NAME"]).' ['.$currency['CURRENCY'].']';
								}
							}
							elseif($field["TYPE"] == 'VAT' && Loader::includeModule("catalog"))
							{
								$queryList = \CCatalogVat::GetListEx(['sort' => 'asc'], [], false, false, ['ID', 'NAME', 'RATE']);
								while($vat = $queryList->GetNext())
								{
									$field["ITEMS"][$vat['ID']] = htmlspecialcharsbx($vat["NAME"]).($vat["RATE"] != '' ? ' ['.$vat["RATE"].']' : '');
								}
							}
							elseif($field["TYPE"] == 'MEASURE' && Loader::includeModule("catalog"))
							{
								$queryList = \Bitrix\Catalog\MeasureTable::getList()->fetchAll();
			
								foreach ($queryList as $measure)
								{
									$measure_name = $measure["MEASURE_TITLE"] ? $measure["MEASURE_TITLE"] : \CCatalogMeasureClassifier::getMeasureTitle($measure["CODE"], 'MEASURE_TITLE');
									$field["ITEMS"][$measure["ID"]] = htmlspecialcharsbx($measure_name).' ['.$measure["ID"].']';
								}
							}
							elseif($field["TYPE"] == 'CATALOG_TYPE' && $field["PARAMS"]["IBLOCK_ID"] > 0 && Loader::includeModule("catalog"))
							{
								$field["ITEMS"] = CCatalogAdminTools::getIblockProductTypeList($field["PARAMS"]["IBLOCK_ID"], true);
							}
							elseif(
								empty($field["ITEMS"]) && 
								$field["PARAMS"]["IBLOCK_ID"] > 0 && 
								CIBlock::GetPermission($field["PARAMS"]["IBLOCK_ID"]) >= 'R'
							)
							{
								if($field["TYPE"] == 'PROPERTY')
								{
									$arSort = Array("sort"=>"asc", "name"=>"asc");
									$arFilter = Array("IBLOCK_ID" => $field["PARAMS"]["IBLOCK_ID"]);
									if(isset($field["PARAMS"]["PROPERTY_TYPE"]))
									{
										$arFilter['PROPERTY_TYPE'] = $field["PARAMS"]["PROPERTY_TYPE"];
									}
									if(isset($field["PARAMS"]["PROPERTY_MULTIPLE"]))
									{
										$arFilter['MULTIPLE'] = $field["PARAMS"]["PROPERTY_MULTIPLE"];
									}
									if(isset($field["PARAMS"]["ACTIVE"]))
									{
										$arFilter['ACTIVE'] = $field["PARAMS"]["ACTIVE"];
									}
									if(isset($field["PARAMS"]["CODE"]))
									{
										$arFilter['CODE'] = $field["PARAMS"]["CODE"];
									}
									if(isset($field["PARAMS"]["USER_TYPE"]))
									{
										$arFilter['USER_TYPE'] = $field["PARAMS"]["USER_TYPE"];
									}
									if($field["REQUIRED"] != "Y" && $field["PARAMS"]["MULTIPLE"] != 'Y')
									{
										$field["ITEMS"][0] = $field["PARAMS"]['ZERO_LABEL'] ? $field["PARAMS"]['ZERO_LABEL'] : Loc::getMessage('WEBPROSTOR_CORE_FUNCTIONS_NOT_SET');
									}
									$queryList = \Bitrix\Iblock\PropertyTable::getList(array(
										'order' => $arSort,
										'select' => ['NAME', 'ID'],
										'filter' => $arFilter,
									))->fetchAll();
									$items = false;
									foreach ($queryList as $property) {
										$items[$property['ID']] = htmlspecialcharsbx($property["NAME"]).' ['.$property['ID'].']';
									}
									if($field["PARAMS"]['OFFER_PROPERTIES'] == true && Loader::includeModule('catalog'))
									{
										$iblockCatalogInfo = CCatalogSKU::GetInfoByProductIBlock($field["PARAMS"]["IBLOCK_ID"]);
										$IBLOCK_ID_OFFER = $iblockCatalogInfo['IBLOCK_ID'];
										
										if($IBLOCK_ID_OFFER)
										{
											$arFilter['IBLOCK_ID'] = $IBLOCK_ID_OFFER;
											$queryList = \Bitrix\Iblock\PropertyTable::getList(array(
												'order' => $arSort,
												'select' => ['NAME', 'ID'],
												'filter' => $arFilter,
											))->fetchAll();
											$itemsOffer = false;
											foreach ($queryList as $property) {
												$itemsOffer[$property['ID']] = htmlspecialcharsbx($property["NAME"]).' ['.$property['ID'].']';
											}
										}
									}
									if($items)
									{
										if($itemsOffer)
										{
											$field["ITEMS"][] = [
												'NAME' => Loc::getMessage('WEBPROSTOR_CORE_FUNCTIONS_PROPERTIES'),
												'ITEMS' => $items,
											];
										}
										else
										{
											if($field["ITEMS"])
											{
												$field["ITEMS"] = $field["ITEMS"] + $items;
											}
											else
											{
												$field["ITEMS"] = $items;
											}
										}
									}
									if($itemsOffer)
									{
										$field["ITEMS"][] = [
											'NAME' => Loc::getMessage('WEBPROSTOR_CORE_FUNCTIONS_PROPERTIES_OFFER'),
											'ITEMS' => $itemsOffer,
										];
									}
										
									unset($queryList, $arSort, $arFilter, $items, $itemsOffer);
								}
								elseif($field["TYPE"] == 'SECTION')
								{
									$arSort = Array("left_margin"=>"asc", "sort"=>"asc", "name"=>"asc");
									$arFilter = Array("IBLOCK_ID" => $field["PARAMS"]["IBLOCK_ID"]);
									if($field["REQUIRED"] != "Y" && $field["PARAMS"]["MULTIPLE"] != 'Y')
									{
										$field["ITEMS"][0] = $field["PARAMS"]['ZERO_LABEL'] ? $field["PARAMS"]['ZERO_LABEL'] : Loc::getMessage('WEBPROSTOR_CORE_FUNCTIONS_NOT_SET');
									}
									if(isset($field["PARAMS"]["ACTIVE"]))
									{
										$arFilter['ACTIVE'] = $field["PARAMS"]["ACTIVE"];
									}
									if(isset($field["PARAMS"]["SECTION_ID"]))
									{
										$arFilter['SECTION_ID'] = $field["PARAMS"]["SECTION_ID"];
									}
									$queryList = \CIBlockSection::GetList($arSort, $arFilter, false, ['NAME', 'ID', 'DEPTH_LEVEL']);
									while($section = $queryList->GetNext())
									{
										$prefix = '';
										$depth_level = $section['DEPTH_LEVEL'];
										while($depth_level > 1)
										{
											$prefix .= '- ';
											--$depth_level;
										}
										$field["ITEMS"][$section['ID']] = $prefix . htmlspecialcharsbx($section["NAME"]).' ['.$section['ID'].']';
									}
									unset($arSort, $arFilter);
								}
								elseif($field["TYPE"] == 'ELEMENT')
								{
									$arSort = Array("sort"=>"asc", "name"=>"asc");
									$arFilter = Array("IBLOCK_ID" => $field["PARAMS"]["IBLOCK_ID"]);
									if($field["REQUIRED"] != "Y" && $field["PARAMS"]["MULTIPLE"] != 'Y')
									{
										$field["ITEMS"][0] = $field["PARAMS"]['ZERO_LABEL'] ? $field["PARAMS"]['ZERO_LABEL'] : Loc::getMessage('WEBPROSTOR_CORE_FUNCTIONS_NOT_SET');
									}
									if(isset($field["PARAMS"]["ACTIVE"]))
									{
										$arFilter['ACTIVE'] = $field["PARAMS"]["ACTIVE"];
									}
									if(isset($field["PARAMS"]["SECTION_ID"]))
									{
										$arFilter['SECTION_ID'] = $field["PARAMS"]["SECTION_ID"];
									}
									$queryList = \CIBlockElement::GetList($arSort, $arFilter, false, false, ['NAME', 'ID']);
									while($element = $queryList->GetNext())
									{
										$field["ITEMS"][$element['ID']] = $prefix . htmlspecialcharsbx($element["NAME"]).' ['.$element['ID'].']';
									}
									unset($arSort, $arFilter);
								}
							}
							elseif($field["TYPE"] == 'HIGHLOAD_ENTITES')
							{
								if(\CModule::IncludeModule('highloadblock') && $field["PARAMS"]['TABLE_ID'] != "")
								{
									$rsData = \Bitrix\Highloadblock\HighloadBlockTable::getList(array('filter'=>array('ID'=>$field["PARAMS"]['TABLE_ID'])));
									if($hldata = $rsData->Fetch())
									{
										$hlentity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hldata);
										$hlDataClass = $hlentity->getDataClass(); 
										$res = $hlDataClass::getList(
											array(
												'filter' => array(), 
												'select' => array("*"), 
												'order' => array(
													'UF_XML_ID' => 'asc'
												)
											)
										);
										
										while ($row = $res->fetch()) {
											$field["ITEMS"][$row['UF_XML_ID']] = htmlspecialcharsbx($row["UF_NAME"]).' ['.$row["UF_XML_ID"].']';
										} 
									}
								}
							}
						?>
					<?if($field["DISABLED"] == "Y") {?><input type="hidden" name="<?=$field["CODE"]?>" value="<?=$field["VALUE"]?>" /><? } ?>
					<?
					$dataAttributes = '';
					if($field["DATA"] && is_array($field["DATA"]))
					{
						foreach($field["DATA"] as $attrName => $attrValue)
						{
							$dataAttributes .= " {$attrName}='{$attrValue}'";
						}
					}
					?>
					<select name="<?=$field["CODE"]?>" id="<?=$field["ID"]?$field["ID"]:strtolower($field["CODE"])?>"<?if($field["REFRESH"] == "Y") {?> onchange="this.form.submit()"<? } ?> class="<?=$field["CLASS"]?$field["CLASS"]:"select-search"?>"<?if($field["PARAMS"]["MULTIPLE"] == "Y") {?> multiple<? } ?><?if($field["DISABLED"] == "Y") {?> disabled<? } ?> placeholder="<?=$field["PARAMS"]["PLACEHOLDER"]?>"<?=$dataAttributes?>>
						<?foreach($field["ITEMS"] as $id => $value):?>
						<?if(!is_array($value)){?>
						<option value="<?=$id?>" <?if($field["VALUE"] == $id || (is_array($field["VALUE"]) && in_array($id, $field["VALUE"]))) echo 'selected';?>><?=$value?></option>
						<?} elseif(is_array($value['ITEMS']) && count($value['ITEMS'])>0) {?>
						<optgroup label="<?=$value["NAME"]?>">
						<?foreach($value["ITEMS"] as $id2 => $value2):?>
							<option value="<?=$id2?>" <?if($field["VALUE"]==$id2 || (is_array($field["VALUE"]) && in_array($id2, $field["VALUE"]))) echo 'selected';?>><?=$value2?></option>
						<?endforeach;?>
						</optgroup>
						<? } ?>
						<?endforeach;?>
					</select>
					<?if($field["PARAMS"]["CHECK_ALL"] == "Y" && $field["PARAMS"]["MULTIPLE"] == "Y") {?>
					&nbsp;<label for="<?=$field["ID"]?>_checkbox"><input type="checkbox" id="<?=$field["ID"]?>_checkbox"<?if($field["PARAMS"]["CHECK_ALL_CHECKED"] == "Y") echo ' checked';?>> <?=Loc::getMessage("WEBPROSTOR_CORE_FUNCTIONS_SELECT_ALL")?></label>
					<script>
					$(document).ready(function(){
						$('#<?=$field["ID"]?>_checkbox').click(function()
						{
							if($(this).is(':checked'))
							{
								$('select#<?=$field["ID"]?>').find('option').prop("selected",true);
								$('select#<?=$field["ID"]?>').trigger('change');
							}
							else
							{
								$('select#<?=$field["ID"]?>').find('option').prop("selected",false);
								$('select#<?=$field["ID"]?>').trigger('change');
							}
						});
					});
					</script>
					<? } ?>
						<?
						if($field['REFRESH'] == 'Y')
							echo '<button class="ui-btn ui-btn-sm" type="submit" name="apply" value="Y">'.Loc::getMessage("WEBPROSTOR_CORE_FUNCTIONS_APPLY").'</button>';
						break;
						case("RANGE"):
						?>
					<?=Loc::getMessage("WEBPROSTOR_CORE_FUNCTIONS_FROM")?>: <input type="text" name="<?=$field["CODE"]?>[MIN]" size="3"  maxlength="<?=$field["PARAMS"]["MAXLENGTH"]?>" value="<?=isset($field["VALUE"]["MIN"])?$field["VALUE"]["MIN"]:""?>">
					<?=Loc::getMessage("WEBPROSTOR_CORE_FUNCTIONS_TO")?>: <input type="text" name="<?=$field["CODE"]?>[MAX]" size="3"  maxlength="<?=$field["PARAMS"]["MAXLENGTH"]?>" value="<?=isset($field["VALUE"]["MAX"])?$field["VALUE"]["MAX"]:""?>">
						<?
						break;
						case("TEXT"):
						?>
					<input type="text" placeholder="<?=$field["PARAMS"]["PLACEHOLDER"]?>" name="<?=$field["CODE"]?>" id="<?=$field["ID"]?$field["ID"]:strtolower($field["CODE"])?>" size="<?=$field["PARAMS"]["SIZE"]?>"  maxlength="<?=$field["PARAMS"]["MAXLENGTH"]?>" value="<?=$field["VALUE"]?>"<?if($field["DISABLED"] == "Y") {?> disabled<? } ?><?if(isset($field["PARAMS"]["AUTOCOMPLETE"])) {?> autocomplete="<?=$field["PARAMS"]["AUTOCOMPLETE"]?"on":"off";?>"<? } ?><?if(isset($field["PARAMS"]["LIST"]) && is_array($field["PARAMS"]["LIST"])){?> list="<?=$field["ID"]?$field["ID"]:strtolower($field["CODE"])?>_list"<? } ?>>
					<?if(isset($field["PARAMS"]["LIST"]) && is_array($field["PARAMS"]["LIST"])){?>
					<datalist id="<?=$field["ID"]?$field["ID"]:strtolower($field["CODE"])?>_list">
					<?foreach($field["PARAMS"]["LIST"] as $option){?>
						<option><?=$option?></option>
					<? } ?>
					</datalist>
					<? } ?>
						<?
						break;
						case("HIDDEN"):
						?>
					<input type="hidden" placeholder="<?=$field["PARAMS"]["PLACEHOLDER"]?>" name="<?=$field["CODE"]?>" id="<?=$field["ID"]?$field["ID"]:strtolower($field["CODE"])?>" size="<?=$field["PARAMS"]["SIZE"]?>"  maxlength="<?=$field["PARAMS"]["MAXLENGTH"]?>" value="<?=$field["VALUE"]?>"<?if($field["DISABLED"] == "Y") {?> disabled<? } ?>>
						<?
						break;
						case("PASSWORD"):
						?>
					<input type="password" placeholder="<?=$field["PARAMS"]["PLACEHOLDER"]?>" name="<?=$field["CODE"]?>" id="<?=$field["ID"]?$field["ID"]:strtolower($field["CODE"])?>" size="<?=$field["PARAMS"]["SIZE"]?>"  maxlength="<?=$field["PARAMS"]["MAXLENGTH"]?>" value="<?=$field["VALUE"]?>"<?if($field["DISABLED"] == "Y") {?> disabled<? } ?> autocomplete="new-password">
						<?
						break;
						case("CODE_EDITOR"):
						case("TEXTAREA"):
						?>
					<textarea type="text" placeholder="<?=$field["PARAMS"]["PLACEHOLDER"]?>" name="<?=$field["CODE"]?>" id="<?=$field["ID"]?$field["ID"]:strtolower($field["CODE"])?>" cols="<?=$field["PARAMS"]["COLS"]?>" rows="<?=$field["PARAMS"]["ROWS"]?>" maxlength="<?=$field["PARAMS"]["MAXLENGTH"]?>"<?if($field["DISABLED"] == "Y") {?> disabled<? } ?>><?=$field["VALUE"]?></textarea>
					<?if(isset($field["PARAMS"]["CODE_EDITOR"]) && $field["PARAMS"]["CODE_EDITOR"]) {
						CCodeEditor::Show(array(
							'textareaId' => $field["ID"]?$field["ID"]:strtolower($field["CODE"]),
							'width' => $field["PARAMS"]["WIDTH"] ? $field["PARAMS"]["WIDTH"] : false,
							'height' => $field["PARAMS"]["HEIGHT"] ? $field["PARAMS"]["HEIGHT"] : 350,
							'forceSyntax' => $field["PARAMS"]["SYNTAX"] ? $field["PARAMS"]["SYNTAX"] : 'php',//php, js, sql, css
							'defaultHighlight' => $field["PARAMS"]["HIGHLIGHT"] ? $field["PARAMS"]["HIGHLIGHT"] : false,
							'defaultTheme' => $field["PARAMS"]["THEME"] ? $field["PARAMS"]["THEME"] : 'light',
						));
					}?>
						<?
						break;
						case("CALENDAR"):
						?>
					<input type="text" name="<?=$field["CODE"]?>" id="<?=$field["ID"]?$field["ID"]:strtolower($field["CODE"])?>" size="<?=$field["PARAMS"]["SIZE"]?>" value="<?=$field["VALUE"]?>" class="typeinput"<?if($field["DISABLED"] == "Y") {?> disabled<? } ?>>
					<?=Calendar($field["CODE"], "")?>
						<?
						break;
						case("TIME"):
						?>
					<input type="time" name="<?=$field["CODE"]?>" id="<?=$field["ID"]?$field["ID"]:strtolower($field["CODE"])?>" min="<?=$field["PARAMS"]["MIN"]?>" max="<?=$field["PARAMS"]["MAX"]?>" step="<?=$field["PARAMS"]["STEP"]?>" value="<?=$field["VALUE"]?>" class="adm-input typeinput"<?if($field["DISABLED"] == "Y") {?> disabled<? } ?>>
						<?
						break;
						case("CALENDAR_DATE"):
						?>
					<?=CalendarDate($field["CODE"], $field["VALUE"], $field["PARAMS"]["FORM_NAME"], $field["PARAMS"]["SIZE"]?$field["PARAMS"]["SIZE"]:15, $field["PARAMS"]["CLASS"])?>
						<?
						break;
						case("CALENDAR_PERIOD"):
						?>
					<?=CalendarPeriod($field["CODE"]["FROM"], $field["VALUE"]["FROM"], $field["CODE"]["TO"], $field["VALUE"]["TO"], "", $field["PARAMS"]["SELECT_ENABLED"]?$field["PARAMS"]["SELECT_ENABLED"]:"N", "typeselect", "typeinput", $field["PARAMS"]["SIZE"])?>
						<?
						break;
						case("USER"):
						case("NUMBER"):
						?>
					<input type="number" class="adm-input" name="<?=$field["CODE"]?>" id="<?=$field["ID"]?$field["ID"]:strtolower($field["CODE"])?>" size="<?=$field["PARAMS"]["SIZE"]?>" min="<?=$field["PARAMS"]["MIN"]?>" max="<?=$field["PARAMS"]["MAX"]?>" step="<?=$field["PARAMS"]["STEP"]?>" maxlength="<?=$field["PARAMS"]["MAXLENGTH"]?>" value="<?=$field["VALUE"]?>"<?if($field["DISABLED"] == "Y") {?> disabled<? } ?>>
						<?
							if($field["TYPE"] == 'USER')
							{
								?>
								<input type="button" name="FIND_USER_<?=$field["CODE"]?>" id="find_user_<?=$field["ID"]?$field["ID"]:strtolower($field["CODE"])?>" onclick="window.open('/bitrix/admin/user_search.php?lang=ru&amp;FN=<?=$field["PARAMS"]["FORM_ID"]?>&amp;FC=<?=$field["ID"]?$field["ID"]:strtolower($field["CODE"])?>', '', 'scrollbars=yes,resizable=yes,width=760,height=500,top='+Math.floor((screen.height - 560)/2-14)+',left='+Math.floor((screen.width - 760)/2-5));" value="...">
								<?
							}
						break;
						case("CHECKBOX"):?>
					<input type="hidden" name="<?=$field["CODE"]?>" value="N">
					<input type="checkbox" name="<?=$field["CODE"]?>" id="<?=$field["ID"]?$field["ID"]:strtolower($field["CODE"])?>" value="<?=($field["VALUE"]?$field["VALUE"]:'N')?>" onClick="javascript:WebprostorCoreCheckActive('<?=$field["ID"]?$field["ID"]:strtolower($field["CODE"])?>')"<?if($field["VALUE"]=="Y") echo ' checked';?><?if($field["DISABLED"] == "Y") {?> disabled<? } ?>>
						<?
						break;
						case("FILE"):?>
						<input type="file" id="<?=$field["ID"]?$field["ID"]:strtolower($field["CODE"])?>" name="<?=$field["CODE"]?>"<?if($field["PARAMS"]["MULTIPLE"] == "Y") {?> multiple<? } ?> accept="<?=$field["PARAMS"]["ACCEPT"]?>" >
						<?
						break;
						case("BUTTON"):?>
					<a class="ui-btn ui-btn-sm" id="<?=$field["ID"]?$field["ID"]:strtolower($field["CODE"])?>" href="<?=$field["VALUE"]?>" target="<?=$field["PARAMS"]["TARGET"]?$field["PARAMS"]["TARGET"]:"_blank";?>"<?if($field["EVENTS"]["ONCLICK"] != ''){?> onClick="<?=$field["EVENTS"]["ONCLICK"]?>"<?}?><?if($field["DISABLED"] == "Y") {?> disabled<? } ?>><span><?=$field["PARAMS"]["TEXT"]?></span></a>
						<?
						break;
						case("WINDOW_DIALOG"):?>
						<script>
						$(document).ready(function(){
							
							function save_<?=$field["ID"]?>()
							{
								var form_<?=$field["ID"]?> = JSON.stringify($( "#<?=$field["CODE"]?>_RESULTS" ).serializeArray());
								
								$("#<?=$field["ID"]?>").val(form_<?=$field["ID"]?>);
								
								$("#dialog_<?=$field["ID"]?>").dialog( "close" );
							}
							
							$("#dialog_<?=$field["ID"]?>").dialog(
								{
									title: "<?=$field["LABEL"]?>",
									autoOpen: false,
									height: <?=$field["PARAMS"]["HEIGHT"]?$field["PARAMS"]["HEIGHT"]:400?>,
									width: <?=$field["PARAMS"]["WIDTH"]?$field["PARAMS"]["WIDTH"]:600?>,
									modal: <?=$field["PARAMS"]["MODAL"] != "N"?"true":"false"?>,
									draggable: <?=$field["PARAMS"]["DRAGGABLE"] != "N"?"true":"false"?>,
									buttons: {
										"<?=Loc::getMessage("WEBPROSTOR_CORE_FUNCTIONS_APPLY");?>": save_<?=$field["ID"]?>,
									}
								}
							);
							
							$( "#open_<?=$field["ID"]?>" ).button().on( "click", function() {

								var dataValues = <?=CUtil::PhpToJsObject($field["DATA"])?>;
								dataValues.INPUT_VALUE = $('input[name=<?=$field["CODE"]?>]').val();
								$.ajax(
								{
									url: "<?=$field["PARAMS"]["AJAX_URL"]?>",
									data: dataValues,
									success: function(data){
										$("#dialog_<?=$field["ID"]?>").html(data);
									}   
								}
								);
								$("#dialog_<?=$field["ID"]?>").dialog( "open" );
							});
						});
						</script>
						<div id="dialog_<?=$field["ID"]?>"></div>
						<input type="hidden" id="<?=$field["ID"]?>" name="<?=$field["CODE"]?>" value="<?=$field["VALUE"]?>">
						<a id="open_<?=$field["ID"]?>" class="ui-btn ui-btn-sm ui-btn-light-border" ><?=Loc::getMessage("WEBPROSTOR_CORE_FUNCTIONS_OPEN");?></a>
						<?
						break;
						case("CONDITIONS"):
							if(Loader::includeModule("catalog"))
							{
							$field["VALUE"] = $field["VALUE"] ? unserialize(base64_decode($field["VALUE"])) : [];
							
							$obCond = new \CCatalogCondTree();
							$boolCond = $obCond->Init(
								BT_COND_MODE_DEFAULT,
								BT_COND_BUILD_CATALOG,
								[
									'FORM_NAME' => $field["PARAMS"]["FORM_NAME"],
									'CONT_ID' => $field["ID"]?$field["ID"]:strtolower($field["CODE"]),
									'JS_NAME' => 'js_'.$field["ID"]?$field["ID"]:strtolower($field["CODE"]),
								]
							);
							if ($boolCond) 
								$obCond->Show($field["VALUE"]);
							?>
							<div id="<?=$field["ID"]?$field["ID"]:strtolower($field["CODE"])?>" style="position: relative; z-index: 1;"></div>
							<?
							}
						break;
						case("FILE_DIALOG"):
							$BtnClickFileEvent = $field["CODE"].'_open';
							?>
						<input type="text" name="<?=$field["CODE"]?>" id="<?=$field["ID"]?$field["ID"]:strtolower($field["CODE"])?>" value="<?=$field["VALUE"]?>" placeholder="<?=$field["PARAMS"]["PLACEHOLDER"]?>" size="<?=$field["PARAMS"]["SIZE"]?>" maxlength="<?=$field["PARAMS"]["MAXLENGTH"]?>" />
						<button type="button" class="ui-btn ui-btn-sm ui-btn-icon-add ui-btn-light-border" value="<?echo Loc::getMessage("WEBPROSTOR_CORE_FUNCTIONS_OPEN"); ?>" OnClick="<?=$BtnClickFileEvent?>()"><?echo Loc::getMessage("WEBPROSTOR_CORE_FUNCTIONS_OPEN"); ?></button>
						<?
						CAdminFileDialog::ShowScript(
							array(
								"event" => $BtnClickFileEvent,
								"arResultDest" => array(
									"FORM_NAME" => $field["PARAMS"]["FORM_NAME"],
									"FORM_ELEMENT_NAME" => $field["CODE"],
								) ,
								"arPath" => array(
									"SITE" => SITE_ID,
									"PATH" => $field["PARAMS"]["PATH"],
								) ,
								"select" => $field["PARAMS"]["SELECT"]?$field["PARAMS"]["SELECT"]:"F",
								"operation" => 'O',
								"showUploadTab" => $field["PARAMS"]["ALLOW_UPLOAD"] == "Y"?true:false,
								"showAddToMenuTab" => false,
								"fileFilter" => $field["PARAMS"]["ALLOW_FILE_FORMATS"]?$field["PARAMS"]["ALLOW_FILE_FORMATS"]:"",
								"allowAllFiles" => false,
								"SaveConfig" => false,
							)
						);
						?>
						<?
						break;
						case("NOTE"):
							echo CWebprostorCoreFunctions::showAlertBegin($field["PARAMS"]["TYPE"], $field["PARAMS"]["ICON"]);
							echo $field["VALUE"];
							echo CWebprostorCoreFunctions::showAlertEnd();
						break;
						default:?>
						<?=$field["VALUE"]?>
						<?
						break;
					}
					?>
					<?
					if($field["DESCRIPTION"])
					{
						//echo ShowJSHint($field["DESCRIPTION"]);
						?>
						<span data-hint="<?=$field["DESCRIPTION"]?>"></span>
						<?
					} 
					?>
				</td>
			</tr>
			<?
			}
			}
			} 
		} 
	}
	
	public static function PrepareProxyList($list = false)
	{
		if(strlen($list)>0)
		{
			$result = Array();
			$list_arr = explode("\n",$list);
			foreach($list_arr as $proxy)
			{
				$proxy = trim($proxy);
				if(strlen($proxy)>0)
				{
					if(strpos($proxy, "|") && strpos($proxy, ":"))
					{
						$proxy_temp = explode('|', $proxy);
						$proxy_temp_ip = explode(':', $proxy_temp[0]);
						$temp_result_ip = Array(
							"ip" => $proxy_temp_ip[0],
							"port" => $proxy_temp_ip[1],
						);
						$proxy_temp_user = explode(':', $proxy_temp[1]);
						$temp_result_user = Array(
							"login" => $proxy_temp_user[0],
							"password" => $proxy_temp_user[1],
						);
						$result[] = array_merge($temp_result_ip, $temp_result_user);
					}
					elseif(strpos($proxy, ":"))
					{
						$proxy_temp = explode(':', $proxy);
						$result[] = Array(
							"ip" => $proxy_temp[0],
							"port" => $proxy_temp[1],
						);
					}
				}
			}
			if(is_array($result) && count($result)>0)
				return $result;
		}
		
		return false;
	}
	
	public static function ConvertFileSize($size)
	{
		$unit = ['b','kb','mb','gb','tb','pb'];
		return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
	}
	
	public static function showAlertBegin($type = '', $icon = '')
	{
		echo '<div class="ui-alert ui-alert-'.($type?$type:"default").''.($icon?" ui-alert-icon-".$icon:"").'">';
		echo '<div class="ui-alert-message">';
	}
	
	public static function showAlertEnd()
	{
		echo '</div></div>';
	}
	
	public static function showProgressBar($type = 'default', $text_before = '', $text_after = '', $value = 0, $total = 100)
	{
		echo '
			<div class="ui-progressbar ui-progressbar-'.$type.' ui-progressbar-bg ui-progressbar-lg">
				<div class="ui-progressbar-text-before">
					<strong>'.$text_before.'</strong>
				</div>
				<div class="ui-progressbar-track">
					<div class="ui-progressbar-bar" style="width:'.$value/($total/100).'%;"></div>
				</div>
				<div class="ui-progressbar-text-after">'.$text_after.'</div>
			</div>';
	}
}
?>