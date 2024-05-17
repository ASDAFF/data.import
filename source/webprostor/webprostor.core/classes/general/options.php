<?
use Bitrix\Main\Security,
	Bitrix\Main\Localization\Loc,
	Bitrix\Main\Config,
	Bitrix\Main\Config\Option;

Class CWebprostorCoreOptions
{
	public $arCurOptionValues = array();
	
	private $module_id = '';
	private $arTabs = array();
	private $arGroups = array();
	private $arOptions = array();
	private $need_access_tab = false;
	
	private $main_tab_div_id = 'edit_main_tab';
	const MAIN_TAB_DIV_ID = 'edit_main_tab';
	
	private $access_tab_div_id = 'edit_access_tab';
	//const ACCESS_TAB_DIV_ID = 'edit_access_tab';
	private $clear_cache = false;
	
	public static function GetTabs()
	{
		$i = 0; $arTabs = Array();
		$rsSites = CSite::GetList($by="sort", $order="asc", Array());
		while ($arSite = $rsSites->Fetch())
		{
			$arTabs[$i] = array(
				'DIV' => $arSite["LID"],
				'TAB' => $arSite["NAME"].' ['.$arSite["LID"].']',
				'ICON' => '',
				'TITLE' => $arSite["NAME"]
			);
	
			$i++;
		}
		
		return $arTabs;
	}
	
	public static function GetGroups($groups = Array(), $arTabs = Array(), $additionalGroups = Array())
	{
		$arGroups = Array();
		foreach($arTabs as $i => $tab)
		{
			$groupPrefix = strtoupper($tab["DIV"]);
			foreach($groups as $code => $group)
			{
				$arGroups[$groupPrefix."_".$code] = array(
					'TITLE' => $group, 
					'TAB' => $i,
				);
			}
		}
		if(is_array($additionalGroups) && count($additionalGroups)>0)
		{
			foreach($additionalGroups as $code => $group)
			{
				$arGroups[$code] = array(
					'TITLE' => $group, 
					'TAB' => self::MAIN_TAB_DIV_ID,
				);
			}
		}
		
		return $arGroups;
	}
	
	public static function GetOptions($options = Array(), $arTabs = Array(), $additionalOptions = Array())
	{
		$arOptions = Array();
		foreach($arTabs as $i => $tab)
		{
			$groupPrefix = strtoupper($tab["DIV"]);
			foreach($options as $option)
			{
				$arOptions[$groupPrefix."_".$option["CODE"]] = array(
					'GROUP' => $groupPrefix."_".$option["GROUP"],
					'TITLE' => $option["TITLE"],
					'TYPE' => $option["TYPE"],
					'ENCRYPT' => $option["ENCRYPT"],
					'DEFAULT' => $option["DEFAULT"],
					'SORT' => $option["SORT"],
					'NOTES' => $option["NOTES"],
					'USER_FIELD' => $option["USER_FIELD"],
					'COLS' => $option["COLS"],
					'ROWS' => $option["ROWS"],
					'REFRESH' => $option["REFRESH"],
					'VALUES' => $option["VALUES"],
					'SIZE' => $option["SIZE"],
					'FIELD_SIZE' => $option["FIELD_SIZE"],
					'FIELD_READONLY' => $option["FIELD_READONLY"],
					'BUTTON_TEXT' => $option["BUTTON_TEXT"],
					'MAXLENGTH' => $option["MAXLENGTH"],
					'MIN' => $option["MIN"],
					'MAX' => $option["MAX"],
					'SITE_ID' => $option["SITE_ID"],
				);
			}
		}
		
		if(is_array($additionalOptions) && count($additionalOptions)>0)
		{
			foreach($additionalOptions as $option)
			{
				$arOptions[$option["CODE"]] = array(
					'GROUP' => $option["GROUP"],
					'TITLE' => $option["TITLE"],
					'CODE' => $option["CODE"],
					'TYPE' => $option["TYPE"],
					'ENCRYPT' => $option["ENCRYPT"],
					'DEFAULT' => $option["DEFAULT"],
					'SORT' => $option["SORT"],
					'NOTES' => $option["NOTES"],
					'COLS' => $option["COLS"],
					'ROWS' => $option["ROWS"],
					'REFRESH' => $option["REFRESH"],
					'VALUES' => $option["VALUES"],
					'SIZE' => $option["SIZE"],
					'FIELD_SIZE' => $option["FIELD_SIZE"],
					'SIZE' => $option["SIZE"],
					'FIELD_READONLY' => $option["FIELD_READONLY"],
					'BUTTON_TEXT' => $option["BUTTON_TEXT"],
					'MAXLENGTH' => $option["MAXLENGTH"],
					'MIN' => $option["MIN"],
					'MAX' => $option["MAX"],
					'SITE_ID' => $option["SITE_ID"],
				);
			}
		}
		
		return $arOptions;
	}
	
	public function __construct($module_id = false, $arTabs = Array(), $arGroups = Array(), $arOptions = Array(), $need_main_tab = false, $need_access_tab = false, $clear_cache = false)
	{
		$this->module_id = $module_id;
		$this->arTabs = $arTabs;
		$this->arGroups = $arGroups;
		$this->arOptions = $arOptions;
		$this->need_access_tab = $need_access_tab;
		$this->moduleAccessLevel = CMain::GetGroupRight($module_id);
		$this->clear_cache = $clear_cache;
		
		if($need_main_tab)
		{
			$this->arTabs[] = array(
				'DIV' => $this->main_tab_div_id,
				'TAB' => Loc::getMessage("WEBPROSTOR_CORE_OPTIONS_MAIN_TAB_NAME"),
				'ICON' => '',
				'TITLE' => Loc::getMessage("WEBPROSTOR_CORE_OPTIONS_MAIN_TAB_TITLE")
			);
		}
		
		if($need_access_tab)
		{
			$this->arTabs[] = array(
				'DIV' => $this->access_tab_div_id,
				'TAB' => Loc::getMessage("WEBPROSTOR_CORE_OPTIONS_ACCESS_TAB_NAME"),
				'ICON' => '',
				'TITLE' => Loc::getMessage("WEBPROSTOR_CORE_OPTIONS_ACCESS_TAB_TITLE")
			);
		}

		if($_REQUEST['save'] && $this->moduleAccessLevel == "W" && check_bitrix_sessid())
		{
			$this->SaveOptions();
			if($this->need_access_tab)
			{
				$this->SaveGroupRight();
			}
		}
		
		if($_REQUEST['restoreDefault'] && $this->moduleAccessLevel == "W" && check_bitrix_sessid())
		{
			if(strlen($_REQUEST['restoreDefault'])>0)
				$this->RestoreOptions();
		}
		
		$this->GetCurOptionValues();
	}
	
	private function SaveOptions()
	{
		if($this->clear_cache)
			BXClearCache(true, $this->module_id);
		foreach($this->arOptions as $opt => $arOptParams)
		{
			if($arOptParams['TYPE'] != 'CUSTOM')
			{
				$val = $_REQUEST[$opt];
	
				if($arOptParams['TYPE'] == 'CHECKBOX' && $val != 'Y')
					$val = 'N';
				elseif(is_array($val))
					$val = serialize($val);
					
				$encrypted = false;
				if($arOptParams['ENCRYPT'] == 'Y' && $val != '')
				{
					$cryptoOptions = Config\Configuration::getValue('crypto');
					if (!empty($cryptoOptions['crypto_key']))
					{
						try
						{
							$cipher = new Security\Cipher();
							$val = $cipher->encrypt(
								$val,
								$cryptoOptions['crypto_key']
							);
							$encrypted = true;
						}
						catch (Security\SecurityException $e)
						{
						}
					}
					if($encrypted)
						$val = base64_encode($val);
				}
					
				if($arOptParams['SITE_ID'] != '')
					$siteId = $arOptParams['SITE_ID'];
				else
					$siteId = '';

				Option::set($this->module_id, $opt, $val, $siteId);
				if($encrypted)
					Option::set($this->module_id, $opt.'_encrypted', 'Y', $siteId);
				/*else
					Option::delete($this->module_id, ['name' => $opt.'_encrypted', 'site_id' => $siteId]);*/
			}
		}
	}
	
	private function RestoreOptions()
	{
		Option::delete($this->module_id);
	}
    
    private function SaveGroupRight()
    {
		
        CMain::DelGroupRight($this->module_id);  
        $GROUP = $_REQUEST['GROUPS'];
        $RIGHT = $_REQUEST['RIGHTS'];
        
        foreach($GROUP as $k => $v) {
            if($k == 0)
			{
               // Option::set($this->module_id, 'GROUP_DEFAULT_RIGHT', $RIGHT[0], 'Right for groups by default');       
                Option::set($this->module_id, 'GROUP_DEFAULT_RIGHT', $RIGHT[0]);       
            } 
            else {
				if($GROUP[$k] && $RIGHT[$k])
					CMain::SetGroupRight($this->module_id, $GROUP[$k], $RIGHT[$k]);                
            }  
        }  
        
                   
    }
	
	private function GetCurOptionValues()
	{
		
		if(is_array($this->arOptions))
		{
			foreach($this->arOptions as $opt => $arOptParams)
			{
				if($arOptParams['TYPE'] != 'CUSTOM')
				{
					if($arOptParams['SITE_ID'] != '')
						$siteId = $arOptParams['SITE_ID'];
					else
						$siteId = '';
					
					$this->arCurOptionValues[$opt] = Option::get($this->module_id, $opt, $arOptParams['DEFAULT'], $siteId);
					
					if($arOptParams['ENCRYPT'] == 'Y')
					{
						$encrypted = false;
						$encrypted = Option::get($this->module_id, $opt.'_encrypted', false, $siteId);
						if($encrypted == 'Y')
						{
							$this->arCurOptionValues[$opt] = base64_decode($this->arCurOptionValues[$opt]);
							$cryptoOptions = Config\Configuration::getValue('crypto');
							if (!empty($cryptoOptions['crypto_key']))
							{
								try
								{
									$cipher = new Security\Cipher();
									$this->arCurOptionValues[$opt] = $cipher->decrypt(
										$this->arCurOptionValues[$opt],
										$cryptoOptions['crypto_key']
									);
								}
								catch (Security\SecurityException $e)
								{
								}
							}
						}
					}
					if(in_array($arOptParams['TYPE'], array('MSELECT')))
						$this->arCurOptionValues[$opt] = unserialize($this->arCurOptionValues[$opt]);
				}
			}
		}
	}
	
	public function ShowHTML()
	{
		global $APPLICATION;
		
		if(isset($_REQUEST["save"]))
		{
			CAdminMessage::ShowMessage(array("MESSAGE"=>Loc::getMessage("WEBPROSTOR_CORE_OPTIONS_SAVE_SUCCESS"), "TYPE"=>"OK"));
		}
		
		if(isset($_REQUEST["restoreDefault"]))
		{
			CAdminMessage::ShowMessage(array("MESSAGE"=>Loc::getMessage("WEBPROSTOR_CORE_OPTIONS_RESET_SUCCESS"), "TYPE"=>"OK"));
		}

		$arP = array();
		
		if(is_array($this->arGroups))
		{
			foreach($this->arGroups as $group_id => $group_params)
				$arP[$group_params['TAB']][$group_id] = array();
		}
		
		if(is_array($this->arOptions))
		{
			foreach($this->arOptions as $option => $arOptParams)
			{
				$val = $this->arCurOptionValues[$option];

				if($arOptParams['SORT'] < 0 || !isset($arOptParams['SORT']))
					$arOptParams['SORT'] = 0;
				
				$label = (isset($arOptParams['TITLE']) && $arOptParams['TITLE'] != '') ? $arOptParams['TITLE'] : '';
				$opt = htmlspecialcharsbx($option);

				switch($arOptParams['TYPE'])
				{
					case("IBLOCK"):
						if(CModule::IncludeModule("iblock"))
						{
							$input = GetIBlockDropDownListEx(
								intVal($val),
								'IBLOCK_TYPE_ID',
								$opt,
								array(
									"MIN_PERMISSION" => $arOptParams["MIN_PERMISSION"],
								),
								'',
								'',
								'class="adm-detail-iblock-types select-search"',
								'class="adm-detail-iblock-list select-search"'
							);
							if($arOptParams['REFRESH'] == 'Y')
								$input .= '<button type="submit" name="refresh" class="ui-ctl-after ui-ctl-icon-forward"></button>';
						}
						break;
					case("HIGHLOAD_BLOCK"):
						if(CModule::IncludeModule("highloadblock"))
						{
							$HLblocks = Array(
								'REFERENCE' => Array(
									Loc::getMessage("WEBPROSTOR_CORE_OPTIONS_SELECT"),
								),
								'REFERENCE_ID' => Array(
									""
								)
							);
							$rsData = \Bitrix\Highloadblock\HighloadBlockTable::getList();
							while($hldata = $rsData->Fetch())
							{
								$HLblocks["REFERENCE_ID"][] = $hldata["ID"];
								$HLblocks["REFERENCE"][] = htmlspecialcharsbx($hldata["NAME"]).' ['.$hldata["TABLE_NAME"].']';
							}
							$input = SelectBoxFromArray($opt, $HLblocks, $val, '', ' class="select-search"', ($arOptParams['REFRESH'] == 'Y' ? true : false), ($arOptParams['REFRESH'] == 'Y' ? $this->module_id : ''));
							if($arOptParams['REFRESH'] == 'Y')
								$input .= '<button type="submit" name="refresh" class="ui-ctl-after ui-ctl-icon-forward"></button>';
						}
						break;
					case("BLOG"):
						if(CModule::IncludeModule("BLOG"))
						{
							$BLOGS = Array(
								'REFERENCE' => Array(
									Loc::getMessage("WEBPROSTOR_CORE_OPTIONS_SELECT"),
								),
								'REFERENCE_ID' => Array(
									""
								)
							);
							$dbBlogs = CBlog::GetList(["NAME" => "ASC"], [], false, false, ["ID", "NAME"]);
							while ($arBlog = $dbBlogs->Fetch())
							{
								$BLOGS["REFERENCE_ID"][] = $arBlog["ID"];
								$BLOGS["REFERENCE"][] = htmlspecialcharsbx($arBlog["NAME"]).' ['.$arBlog["ID"].']';
							}
							$input = SelectBoxFromArray($opt, $BLOGS, $val, '', ' class="select-search"', ($arOptParams['REFRESH'] == 'Y' ? true : false), ($arOptParams['REFRESH'] == 'Y' ? $this->module_id : ''));
							if($arOptParams['REFRESH'] == 'Y')
								$input .= '<button type="submit" name="refresh" class="ui-ctl-after ui-ctl-icon-forward"></button>';
						}
						break;
					case 'CHECKBOX':
						$input = '<label class="ui-ctl ui-ctl-checkbox ui-ctl-xs"><input class="ui-ctl-element" type="checkbox" name="'.$opt.'" id="'.$opt.'" value="Y"'.($val == 'Y' ? ' checked' : '').' '.($arOptParams['REFRESH'] == 'Y' ? 'onclick="document.forms[\''.$this->module_id.'\'].submit();"' : '').''.($arOptParams['DISABLED'] == 'Y' ? 'disabled' : '').''.($arOptParams['FIELD_READONLY'] == 'Y' ? ' onclick="return false;" readonly' : '').' /></label>';
						break;
					case 'TEXTAREA':
					case 'TEXT':
						if(!isset($arOptParams['COLS']))
							$arOptParams['COLS'] = 25;
						if(!isset($arOptParams['ROWS']))
							$arOptParams['ROWS'] = 5;
						$input = '<div class="ui-ctl ui-ctl-textarea"><textarea class="ui-ctl-element" cols="'.$arOptParams['COLS'].'" rows="'.$arOptParams['ROWS'].'" name="'.$opt.'">'.htmlspecialcharsbx($val).'</textarea>';
						if($arOptParams['REFRESH'] == 'Y')
							$input .= '<button type="submit" name="refresh" class="ui-ctl-after ui-ctl-icon-forward"></button>';
						$input .= '</div>';
						break;
					case 'SELECT':
						$input = SelectBoxFromArray($opt, $arOptParams['VALUES'], $val, '', ' class="select-search"', ($arOptParams['REFRESH'] == 'Y' ? true : false), ($arOptParams['REFRESH'] == 'Y' ? $this->module_id : ''));
						if($arOptParams['REFRESH'] == 'Y')
							$input .= '<button type="submit" name="refresh" class="ui-ctl-after ui-ctl-icon-forward"></button>';
						break;
					case 'MSELECT':
						$input = SelectBoxMFromArray($opt.'[]', $arOptParams['VALUES'], $val, "", false, ($arOptParams['FIELD_SIZE'] ? $arOptParams['FIELD_SIZE'] : 5), "class=\"typeselect select-search\"");
						if($arOptParams['REFRESH'] == 'Y')
							$input .= '<button type="submit" name="refresh" class="ui-ctl-after ui-ctl-icon-forward"></button>';
						break;
					case 'CALENDAR':
						$input = '<input class="ui-ctl-element" type="text" value="'.htmlspecialcharsbx($val).'" name="'.htmlspecialcharsbx($option).'" />'.Calendar(htmlspecialcharsbx($option), "");
						break;
					case 'CALENDAR_DATE':
						$input = CalendarDate(htmlspecialcharsbx($option), htmlspecialcharsbx($val), $this->module_id, 18, '');
						break;
					case 'COLORPICKER':
						CJSCore::Init(['color_picker']);
						if(!isset($arOptParams['FIELD_SIZE']))
							$arOptParams['FIELD_SIZE'] = 25;
						//$input = '<input name="'.$opt.'" size="'.$arOptParams['FIELD_SIZE'].'" value="'.htmlspecialcharsbx($val).'" type="color" />';
						$input = '<input name="'.$opt.'" id="COLORPICKER_'.$opt.'" size="'.$arOptParams['FIELD_SIZE'].'" value="'.htmlspecialcharsbx($val).'" type="text" />';
						/*if($arOptParams['REFRESH'] == 'Y')
							$input .= '<button type="submit" name="refresh" class="ui-ctl-after ui-ctl-icon-forward"></button>';*/
						$input .= "<script>
						  BX.ready(function() {
							var element = BX('COLORPICKER_{$opt}');

							BX.bind(element, 'focus', function () {
								new BX.ColorPicker({
									bindElement: element,
									defaultColor: '#ffffff',
									selectedColor: '".htmlspecialcharsbx($val)."',
									colorPreview: true,
									allowCustomColor: true,
									onColorSelected: function (item) {
										element.value = item
									},
									popupOptions: {
										angle: true,
										autoHide: true,
										closeByEsc: true,
										/*events: {
											onPopupClose: function () {
												
											}
										}*/
									}
								}).open();
							})
						  })
						</script>";
						break;
					case 'FILE':
						if(!isset($arOptParams['FIELD_SIZE']))
							$arOptParams['FIELD_SIZE'] = 25;
						if(!isset($arOptParams['BUTTON_TEXT']))
							$arOptParams['BUTTON_TEXT'] = '...';
						CAdminFileDialog::ShowScript(Array(
							'event' => 'BX_FD_'.$opt,
							'arResultDest' => Array('FUNCTION_NAME' => 'BX_FD_ONRESULT_'.$opt),
							'arPath' => Array(),
							'select' => 'F',
							'operation' => 'O',
							'showUploadTab' => true,
							'showAddToMenuTab' => false,
							'fileFilter' => '',
							'allowAllFiles' => true,
							'SaveConfig' => true
						));
						$input = 	'<div class="ui-ctl ui-ctl-textbox ui-ctl-ext-after-icon"><input class="ui-ctl-element" id="__FD_PARAM_'.$opt.'" name="'.$opt.'" size="'.$arOptParams['FIELD_SIZE'].'" value="'.htmlspecialcharsbx($val).'" type="text" style="float: left;" '.($arOptParams['FIELD_READONLY'] == 'Y' ? 'readonly' : '').' />
									<button class="ui-ctl-after ui-ctl-icon-dots" onclick="window.BX_FD_'.$opt.'(); return false;">'.$arOptParams['BUTTON_TEXT'].'</button>
									<script>
										setTimeout(function(){
											if (BX("bx_fd_input_'.strtolower($opt).'"))
												BX("bx_fd_input_'.strtolower($opt).'").onclick = window.BX_FD_'.$opt.';
										}, 200);
										window.BX_FD_ONRESULT_'.$opt.' = function(filename, filepath)
										{
											var oInput = BX("__FD_PARAM_'.$opt.'");
											if (typeof filename == "object")
												oInput.value = filename.src;
											else
												oInput.value = (filepath + "/" + filename).replace(/\/\//ig, \'/\');
										}
									</script>';
						if($arOptParams['REFRESH'] == 'Y')
							$input .= '<button type="submit" name="refresh" class="ui-ctl-after ui-ctl-icon-forward"></button>';
						$input .= '</div>';
						break;
					case 'SUBMIT':
						$input = '<button class="ui-btn ui-btn-success" type="submit" name="'.$opt.'">'.$arOptParams['BUTTON_TEXT'].'</button>';
						break;
					case 'CUSTOM':
						$input = $arOptParams['VALUE'];
						break;
					default:
						if(!isset($arOptParams['SIZE']))
							$arOptParams['SIZE'] = 25;
						if(!isset($arOptParams['MAXLENGTH']))
							$arOptParams['MAXLENGTH'] = 255;
						
						$input = '<div class="ui-ctl ui-ctl-textbox ui-ctl-ext-after-icon"><input class="ui-ctl-element" type="'.
							($arOptParams['TYPE'] == 'INT' ? 'number' : ($arOptParams['TYPE'] == 'PASSWORD' ? 'password' :'text'))
							.'" size="'.$arOptParams['SIZE'].'"'.
							($arOptParams['TYPE'] == 'PASSWORD'?' autocomplete="new-password"':'').
							(isset($arOptParams['MIN'])?' min="'.$arOptParams['MIN'].'"':'').
							(isset($arOptParams['MAX'])?' max="'.$arOptParams['MAX'].'"':'')
							.' maxlength="'.$arOptParams['MAXLENGTH'].'" value="'.htmlspecialcharsbx($val).'" name="'.htmlspecialcharsbx($option).'" />';
						if($arOptParams['REFRESH'] == 'Y')
							$input .= '<button type="submit" name="refresh" class="ui-ctl-after ui-ctl-icon-forward"></button>';
						$input .= '</div>';
						break;
				}

				if(isset($arOptParams['NOTES']) && $arOptParams['NOTES'] != '' || $arOptParams['USER_FIELD'] == 'Y')
				{
					if($arOptParams['USER_FIELD'] == 'Y')
					{
						$arOptParams['NOTES'] = 'Option::get("webprostor.core", "'.$opt.'");';
					}
					$input .= 	'<div class="notes">
									<table cellspacing="0" cellpadding="0" border="0" class="notes">
										<tbody>
											<tr class="top">
												<td class="left"><div class="empty"></div></td>
												<td><div class="empty"></div></td>
												<td class="right"><div class="empty"></div></td>
											</tr>
											<tr>
												<td class="left"><div class="empty"></div></td>
												<td class="content">
													'.$arOptParams['NOTES'].'
												</td>
												<td class="right"><div class="empty"></div></td>
											</tr>
											<tr class="bottom">
												<td class="left"><div class="empty"></div></td>
												<td><div class="empty"></div></td>
												<td class="right"><div class="empty"></div></td>
											</tr>
										</tbody>
									</table>
								</div>';
				}

				$arP[$this->arGroups[$arOptParams['GROUP']]['TAB']][$arOptParams['GROUP']]['OPTIONS'][] = $label != '' ? '<tr><td valign="top" width="50%">'.$label.':</td><td valign="top" width="50%" nowrap>'.$input.'</td></tr>' : '<tr><td valign="top" colspan="2" align="center">'.$input.'</td></tr>';
				$arP[$this->arGroups[$arOptParams['GROUP']]['TAB']][$arOptParams['GROUP']]['OPTIONS_SORT'][] = $arOptParams['SORT'];
			}

			$tabControl = new CAdminTabControl('tabControl', $this->arTabs, true, true);
			$tabControl->Begin();
			echo '<form name="'.$this->module_id.'" method="POST" action="'.$APPLICATION->GetCurPage().'?mid='.$this->module_id.'&lang='.LANGUAGE_ID.'" enctype="multipart/form-data">'.bitrix_sessid_post();

			foreach($arP as $tab => $groups)
			{
				$tabControl->BeginNextTab();

				foreach($groups as $group_id => $group)
				{
					if(is_array($group['OPTIONS_SORT']) && count($group['OPTIONS_SORT']) > 0)
					{
						echo '<tr class="heading"><td colspan="2">'.$this->arGroups[$group_id]['TITLE'].'</td></tr>';
						
						array_multisort($group['OPTIONS_SORT'], $group['OPTIONS']);
						foreach($group['OPTIONS'] as $opt)
							echo $opt;
					}
				}
			}
			
			$module_id = $this->module_id;
			
			if($this->need_access_tab)
			{
				$tabControl->BeginNextTab();
				require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights.php");
			}
			
			$moduleAccessLevel = $APPLICATION->GetGroupRight($module_id);
			
			/*$tabControl->Buttons(
				array(
					"disabled" => ($moduleAccessLevel < "W"),
					"btnApply" => false,
					"back_url" => $_REQUEST["back_url"],
				)
			);*/
			$tabControl->Buttons();
			if (isset($_REQUEST["IFRAME"]) && $_REQUEST["IFRAME"] === "Y")
			{
				?>
				<input type="hidden" name="IFRAME" value="Y" />
				<input type="hidden" name="IFRAME_TYPE" value="<?=$_REQUEST["IFRAME_TYPE"]?>" />
				<?
			}
			?>
			<button 
			title="<?=Loc::getMessage("WEBPROSTOR_CORE_SAVE_TITLE")?>" 
			class="ui-btn ui-btn-success" 
			type="submit" 
			value="Y" 
			name="save"
			<?=$moduleAccessLevel>="W"?"":" disabled"?>
			>
				<?=Loc::getMessage("WEBPROSTOR_CORE_SAVE")?>
			</button>
			<button 
			title="<?=Loc::getMessage("WEBPROSTOR_CORE_CANCEL_TITLE")?>" 
			class="ui-btn ui-btn-link" 
			type="submit" 
			name="cancel" 
			<?if($_REQUEST["back_url"] != ''){?>
			onclick="top.window.location='<?=$_REQUEST["back_url"]?>'"
			<? } ?>
			>
				<?=Loc::getMessage("WEBPROSTOR_CORE_CANCEL")?>
			</button>
			<button 
			title="<?=Loc::getMessage("WEBPROSTOR_CORE_RESTORE_TITLE")?>" 
			class="ui-btn ui-btn-primary" 
			type="submit" 
			value="Y" 
			name="restoreDefault"
			onclick="return confirm('<?=Loc::getMessage("WEBPROSTOR_CORE_OPTIONS_RESET_TITLE")?>')"
			<?=$moduleAccessLevel>="W"?"":" disabled"?>
			>
				<?=Loc::getMessage("WEBPROSTOR_CORE_OPTIONS_RESET")?>
			</button>
			<?
			$tabControl->End();
			
			//echo '<input type="submit" name="restoreDefault" onclick="return confirm(\''.Loc::getMessage("WEBPROSTOR_CORE_OPTIONS_RESET_TITLE").'\')" value="'.Loc::getMessage("WEBPROSTOR_CORE_OPTIONS_RESET").'"'.(($moduleAccessLevel < "W")?" disabled":"").' />';
			
		}
	}
}
?>