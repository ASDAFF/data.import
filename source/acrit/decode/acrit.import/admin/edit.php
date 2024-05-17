<?
$moduleId = "acrit.import";
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");
/**
 * @global $APPLICATION \CMain
 * @global $USER \CUser
 * @global $DB \CDatabase
 * @global $USER_FIELD_MANAGER \CUserTypeManager
 * @global $BX_MENU_CUSTOM \CMenuCustom
 * @global $stackCacheManager \CStackCacheManager
 */

use Bitrix\Main\SystemException,
	Acrit\Import,
	Acrit\Import\Agents,
	Bitrix\Main\Application;

IncludeModuleLangFile(__FILE__);

$moduleStatus = CModule::IncludeModuleEx($moduleId);

$request = Application::getInstance()->getContext()->getRequest();
$right   = $APPLICATION->GetGroupRight($moduleId);

if (! function_exists('CheckRights')) {
	function CheckRights($right, $action): bool
	{
		if ($action == "read" && $right < "R") {
			try {
				throw new SystemException(GetMessage("ACRIT_IMPORT_RIGHTS_NOT_TOREAD"));
			} catch (SystemException $exception) {
				global $lastException;
				$lastException = $exception->getMessage();
			}
			return false;
		}

		if ($action == "write" && $right < "W") {
			try {
				throw new SystemException(GetMessage("ACRIT_IMPORT_RIGHTS_NOT_TOWRITE"));
			} catch (SystemException $exception) {
				global $lastException;
				$lastException = $exception->getMessage();
			}
			return false;
		}
		return true;
	}
}

if ($moduleStatus == MODULE_DEMO_EXPIRED) {
	$buyLicenceUrl = GetMessage('ACRIT_IMPORT_BUY_LICENCE_URL');
	require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php"); ?>
	<div class="adm-info-message">
		<div class="acrit_note_button">
			<a href="<?=$buyLicenceUrl?>" target="_blank" class="adm-btn adm-btn-save"><?=GetMessage("ACRIT_IMPORT_DEMOEND_BUY_LICENCE_INFO")?></a>
		</div>
		<div class="acrit_note_text"><?=GetMessage("ACRIT_IMPORT_DEMOEND_PERIOD_INFO");?></div>
		<div class="acrit_note_clr"></div>
	</div>
	<?
} elseif (CheckRights($right, "read")) {
	require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/$moduleId/include.php");

	/** @var $STEP_SHOW string */
	/** @var $STEP_SAVE string */
	/** @var $action string */
	/** @var $ID string */
	/** @var $copy string */

	$STEP_SHOW = (int)$STEP_SHOW;
	if ($STEP_SHOW <= 0) {
		$STEP_SHOW = 1;
	}
	$STEP_SAVE = (int)$STEP_SAVE;
	if ($STEP_SAVE <= 0) {
		$STEP_SAVE = $STEP_SHOW;
	}

	// region button steps
	if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["backButton"]) && strlen($_POST["backButton"]) > 0) {
		$STEP_SHOW = $STEP_SHOW - 2;
	}
	if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["backButton2"]) && strlen($_POST["backButton2"]) > 0) {
		$STEP_SHOW = 1;
	}
	// stay on current step
	if ($request->isPost() && !empty($request->getPost('apply_btn'))) {
		$STEP_SHOW = $STEP_SAVE;
	}
	if (!empty($_POST['allclose_btn'])) {
		LocalRedirect("acrit.import_list.php?lang=" . LANGUAGE_ID);
	}
	// endregion

	$lastException = false;

	$obProfile       = new Import\ProfileTable();
	$obProfileFields = new Import\ProfileFieldsTable();

	CUtil::InitJSCore(["ajax", "jquery"]);
	$APPLICATION->AddHeadScript("/bitrix/js/iblock/iblock_edit.js");
	$APPLICATION->AddHeadScript("/bitrix/js/" . $moduleId . "/main.js");
	$APPLICATION->AddHeadScript("/bitrix/js/" . $moduleId . "/select2.min.js");
	$APPLICATION->SetAdditionalCss("/bitrix/themes/.default/acrit/import/select2.min.css");
	$t = CJSCore::getExtInfo("jquery");
	CJSCore::Init(["core_condtree"]);
	if (!is_array($t) || !isset($t["js"]) || !file_exists(Application::getDocumentRoot() . $t["js"])) {
		try {
			throw new SystemException(GetMessage("ACRIT_IMPORT_JQUERY_REQUIRE"));
		} catch (SystemException $exception) {
			global $lastException;
			$lastException = $exception->getMessage();
		}
	}

	if (!CModule::IncludeModule("iblock")) {
		return false;
	}
	//CModule::IncludeModule( "catalog" );

	$incl_catalog = (CModule::IncludeModule("catalog"));
	//$incl_sale = ( !CModule::IncludeModule( "sale" ) ) ? false : true;
	$incl_currency = (CModule::IncludeModule("currency"));

	$POST_RIGHT = $APPLICATION->GetGroupRight($moduleId);
	if ($POST_RIGHT == "D") {
		$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
	}

	$ID            = (int)$ID;        // Id of the edited record
	$bCopy         = ($action == "copy");
	$message       = null;
	$bVarsFromForm = false;

	/**
	 * Copy profile
	 */
	if ($copy && $ID > 0) {
		$arProfile = $obProfile::getByID($ID)->fetch();

		unset($arProfile["ID"]);

		$res = $obProfile::add($arProfile);
		if ($res->isSuccess()) {
			$new_id = $res->getId();
			$res    = $obProfileFields::getList([
				'order' => ['ID' => 'asc'],
				'filter' => ['PARENT_ID' => $ID],
			]);
			while ($arFields = $res->fetch()) {
				unset($arFields['ID']);
				$arFields['PARENT_ID'] = $new_id;
				$obProfileFields::add($arFields);
			}
			LocalRedirect($APPLICATION->GetCurPageParam("ID=$new_id", ["ID", "copy"]));
		} else {
			LocalRedirect($APPLICATION->GetCurPageParam("", ["ID", "copy"]));
		}
		die();
	}

	/**
	 * Prepare request data
	 */
	if (isset($_REQUEST['SOURCE'])) {
		$PROFILE['SOURCE'] = $_REQUEST['SOURCE'];
	}

	// region Data save
	function CheckFields($step): bool
	{
		global $PROFILE, $APPLICATION, $ID;

		$requiredFields = [];
		if ($step == 1) {
			$requiredFields = [
				"TYPE", "SOURCE", "IBLOCK_ID" //"NAME", "CODE",
			];
		}

		foreach ($requiredFields as $field) {
			if (!$PROFILE[$field]) {
				try {
					throw new SystemException(GetMessage("ACRIT_IMPORT_REQUIRED_FIELD_FAIL", ["#CODE#" => $field, "#NAME#" => GetMessage("ACRIT_IMPORT_FLD_" . $field)]));
				} catch (SystemException $exception) {
					global $lastException;
					$lastException = $exception->getMessage();
				}
				return false;
			}
		}

		return true;
	}

	if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST["backButton"]) && !isset($_POST["backButton2"]) && check_bitrix_sessid() && $STEP_SHOW > 1) { //&& !$bUnlockMode &&
		if (!$_POST['next_btn']) {
			$rightCheck  = CheckRights($right, "write");
			$fieldsCheck = CheckFields($STEP_SAVE);
			if ($fieldsCheck && $rightCheck) {
				// Save data
				$arFields = [];
				if (!empty($PROFILE)) {
					foreach ($PROFILE as $k => $value) {
						if (is_array($value) && !in_array($k, ['SOURCE_PARAM_3'])) {
							$arFields[$k] = implode(',', $value);
						} else {
							$arFields[$k] = $value;
						}
					}
				}
				if (!empty($arFields)) {
					if ($ID) {
						$res = $obProfile::update($ID, $arFields);
						if (!$res->isSuccess()) {
							$lastException = implode(' ', $res->getErrorMessages());
						}
					} else {
						$res = $obProfile::add($arFields);
						if ($res->isSuccess()) {
							$ID    = $res->getId();
							$query = parse_url($_SERVER["REQUEST_URI"], PHP_URL_QUERY);
							parse_str($query, $arQuery);
							$arQuery["ID"] = $ID;
							LocalRedirect("acrit.import_edit.php?" . http_build_query($arQuery));
							die();
						} else {
							$lastException = implode(' ', $res->getErrorMessages());
						}
					}
				}
				unset($arFields);

				if ($STEP_SAVE == 2) {
					/** @var string $SAVE_TYPE */
					/** @var array $FLDSMAP_PARAMS */

					// Save fields mapping for existed profile
					// Don't update fields, if updated only general options of profile
					if ($ID && $SAVE_TYPE != 'general') {
						// Clear old data
						$res = $obProfileFields::getList([
							'order' => ['ID' => 'asc'],
							'filter' => ['PARENT_ID' => $ID],
						]);
						while ($arField = $res->fetch()) {
							$obProfileFields::delete($arField['ID']);
						}
						// Save new data
						$arFieldsMapping = [];
						if (!empty($FLDSMAP)) {
							foreach ($FLDSMAP as $k => $value) {
								if ($value) {
									$arFieldsMapping[$k] = $value;
								}
							}
						}
						foreach ($arFieldsMapping as $f_fld => $ib_fld) {
							// Save data
							$arFields = [
								'PARENT_ID' => $ID,
								'F_FIELD' => $f_fld,
								'IB_FIELD' => $ib_fld,
								'PARAMS' => $FLDSMAP_PARAMS[$f_fld],
							];
							$res      = $obProfileFields->add($arFields);
							if (!$res->isSuccess()) {
								$lastException = implode(' ', $res->getErrorMessages());
								break;
							}
						}
						// Check identifier
						if (!isset($arFieldsMapping[$PROFILE['ELEMENT_IDENTIFIER']])) {
							$lastException = GetMessage("ACRIT_IMPORT_VYBRANNYY_IDENTIFIKA");
						}
					}
				}

				if ($STEP_SAVE == 4 && empty($request->getPost('apply_btn'))) {
					LocalRedirect("acrit.import_list.php?lang=" . LANGUAGE_ID);
					die();
				}
			}
		}
	}

	// If has errors
	if ($lastException && $STEP_SHOW > $STEP_SAVE) {
		// Back to the previous step
		$STEP_SHOW -= 1;
	}
	// endregion

	/**
	 * Get fresh data
	 */
	$arImportTypes = AcritImportGetImportTypes();
	$arSourceTypes = AcritImportGetSourceTypes();

	if (isset($ID) && ((int)$ID > 0)) {
		$arProfile = $obProfile::getByID($ID)->fetch();
	}
	//    else{
	//        $arProfile = $obProfileUtils->GetDefaults();
	//    }

	if (!empty($PROFILE)) {
		foreach ($PROFILE as $k => $value) {
			$arProfile[$k] = $value;
		}
	}
	// Default type
	if (!$arProfile['TYPE']) {
		$arProfile['TYPE'] = 'csv';
	}
	// Default source type
	if (is_array($arImportTypes[$arProfile['TYPE']]['source_types'])) {
		if (!in_array($arProfile['SOURCE_TYPE'], $arImportTypes[$arProfile['TYPE']]['source_types'])) {
			$arProfile['SOURCE_TYPE'] = $arImportTypes[$arProfile['TYPE']]['source_types'][0];
		}
	} else {
		if ($arProfile['SOURCE_TYPE'] != $arImportTypes[$arProfile['TYPE']]['source_types']) {
			$arProfile['SOURCE_TYPE'] = $arImportTypes[$arProfile['TYPE']]['source_types'];
		}
	}
	// Default params of fields
	/** @var \Acrit\Import\Import $import_class */
	$import_class    = 'Acrit\Import\\' . $arImportTypes[$arProfile['TYPE']]['class'];
	$arProfileFields = $import_class::getProfileParams();

	/**
	 * Get profile fields
	 */

	if ($STEP_SHOW == 2) {
		try {
			// Get fields from source
			$arSourceFields = [];
			$obImport       = AcritImportGetImportObj($ID);
			if ($obImport) {
				// Get additional settings
				$arProfileAddSettings = $obImport->getProfileAddSettings();
				// Get fields comparisons
				$obImport->prepareSource(1);        // download file after one hour later
				$arSourceFields = $obImport->fields();
			}
		} catch (Throwable $e) {
			$lastException = $e->getMessage();
		}

		// region IBlock fields

		$arIBlockFields['MAIN']          = [
			'TITLE' => GetMessage("ACRIT_IMPORT_OSNOVNYE_PARAMETRY"),
		];
		$arIBlockFields['MAIN']['ITEMS'] = [
			'ID' => array(
				'ID' => 'ID',
				'NAME' => 'ID '.GetMessage("ACRIT_IMPORT_ELEMENTA")
			),
			'XML_ID' => [
				'ID' => 'XML_ID',
				'NAME' => GetMessage("ACRIT_IMPORT_IBLOCK_FIELDS_XML_ID")
			],
			'SORT' => [
				'ID' => 'SORT',
				'NAME' => GetMessage("ACRIT_IMPORT_INDEKS_SORTIROVKI")
			],
//            'EXTERNAL_ID' => array(
//                'ID' => 'EXTERNAL_ID',
//                'NAME' => GetMessage("ACRIT_IMPORT_VNESNIY_KOD")
//            ),
			'NAME' => [
				'ID' => 'NAME',
				'NAME' => GetMessage("ACRIT_IMPORT_IMA_ELEMENTA")
			],
			'CODE' => [
				'ID' => 'CODE',
				'NAME' => GetMessage("ACRIT_IMPORT_KOD_ELEMENTA")
			],
			'ACTIVE' => [
				'ID' => 'ACTIVE',
				'NAME' => GetMessage("ACRIT_IMPORT_FLD_ACTIVE")
			],
			'DATE_ACTIVE_FROM' => [
				'ID' => 'DATE_ACTIVE_FROM',
				'NAME' => GetMessage("ACRIT_IMPORT_NACALO_AKTIVNOSTI")
			],
			'DATE_ACTIVE_TO' => [
				'ID' => 'DATE_ACTIVE_TO',
				'NAME' => GetMessage("ACRIT_IMPORT_OKONCANIE_AKTIVNOSTI")
			],
			'TAGS' => [
				'ID' => 'TAGS',
				'NAME' => GetMessage("ACRIT_IMPORT_TEGI")
			],
			'PREVIEW_PICTURE' => [
				'ID' => 'PREVIEW_PICTURE',
				'NAME' => GetMessage("ACRIT_IMPORT_IZOBRAJENIE_DLA_ANON")
			],
			'DETAIL_PICTURE' => [
				'ID' => 'DETAIL_PICTURE',
				'NAME' => GetMessage("ACRIT_IMPORT_DETALQNOE_IZOBRAJENI")
			],
			'PREVIEW_TEXT_TYPE' => [
				'ID' => 'PREVIEW_TEXT_TYPE',
				'NAME' => GetMessage("ACRIT_IMPORT_TIP_OPISANIA_DLA_ANO")
			],
			'PREVIEW_TEXT' => [
				'ID' => 'PREVIEW_TEXT',
				'NAME' => GetMessage("ACRIT_IMPORT_OPISANIE_DLA_ANONSA")
			],
			'DETAIL_TEXT_TYPE' => [
				'ID' => 'DETAIL_TEXT_TYPE',
				'NAME' => GetMessage("ACRIT_IMPORT_TIP_DETALQNOGO_OPISA")
			],
			'DETAIL_TEXT' => [
				'ID' => 'DETAIL_TEXT',
				'NAME' => GetMessage("ACRIT_IMPORT_DETALQNOE_OPISANIE")
			],
		];
		// IBlock properties
		$arIBlockFields['PROPS'] = [
			'TITLE' => GetMessage("ACRIT_IMPORT_SVOYSTVA"),
		];
		if ($arProfile['IBLOCK_ID']) {
			$ob = CIBlockProperty::GetList(["sort" => "asc", "name" => "asc"], ["ACTIVE" => "Y", "IBLOCK_ID" => $arProfile['IBLOCK_ID']]);
			while ($arProp = $ob->GetNext()) {
				$arIBlockFields['PROPS']['ITEMS']['PROP_' . $arProp['ID']] = [
					'ID' => 'PROP_' . $arProp['ID'],
					'NAME' => GetMessage("ACRIT_IMPORT_SVOYSTVO") . $arProp['NAME'] . '"',
				];
			}
		}
		// IBlock categories
		$arIBlockFields['CATEGORIES'] = [
			'TITLE' => GetMessage("ACRIT_IMPORT_PRIVAZKA_K_KATEGORII"),
			'ITEMS' => [
				'CATEGORY_ID' => [
					'ID' => 'CATEGORY_ID',
					'NAME' => 'ID ' . GetMessage("ACRIT_IMPORT_KATEGORII")
				],
				'CATEGORY_CODE' => [
					'ID' => 'CATEGORY_CODE',
					'NAME' => GetMessage("ACRIT_IMPORT_SIMVOLQNYY_KOD_KATEG")
				],
				'CATEGORY_NAME' => [
					'ID' => 'CATEGORY_NAME',
					'NAME' => GetMessage("ACRIT_IMPORT_NAZVANIE_KATEGORII")
				],
				'CATEGORY_XML_ID' => [
					'ID' => 'CATEGORY_XML_ID',
					'NAME' => GetMessage("ACRIT_IMPORT_IBLOCK_FIELDS_XML_ID") . ' ' . GetMessage("ACRIT_IMPORT_KATEGORII")
				],
			]
		];
		// SEO
		$arIBlockFields['SEO'] = [
			'TITLE' => 'SEO-' . GetMessage("ACRIT_IMPORT_POLA"),
			'ITEMS' => [
				'CATEGORY_ID' => [
					'ID' => 'SEO_H1',
					'NAME' => GetMessage("ACRIT_IMPORT_ZAGOLOVOK_ELEMENTA")
				],
				'SEO_TITLE' => [
					'ID' => 'SEO_TITLE',
					'NAME' => GetMessage("ACRIT_IMPORT_ZAGOLOVOK_OKNA_BRAUZ")
				],
				'SEO_KEYWORDS' => [
					'ID' => 'SEO_KEYWORDS',
					'NAME' => GetMessage("ACRIT_IMPORT_KLUCEVYE_SLOVA")
				],
				'SEO_DESCRIPTION' => [
					'ID' => 'SEO_DESCRIPTION',
					'NAME' => GetMessage("ACRIT_IMPORT_META_OPISANIE")
				],
				'SEO_PREVIEW_PICTURE_ALT' => [
					'ID' => 'SEO_PREVIEW_PICTURE_ALT',
					'NAME' => 'ALT ' . GetMessage("ACRIT_IMPORT_DLA_KARTINKI_ANONSA")
				],
				'SEO_PREVIEW_PICTURE_TITLE' => [
					'ID' => 'SEO_PREVIEW_PICTURE_TITLE',
					'NAME' => 'TITLE ' . GetMessage("ACRIT_IMPORT_DLA_KARTINKI_ANONSA")
				],
				'SEO_PREVIEW_PICTURE_FILENAME' => [
					'ID' => 'SEO_PREVIEW_PICTURE_FILENAME',
					'NAME' => GetMessage("ACRIT_IMPORT_IMA_FAYLA_DLA_KARTIN")
				],
				'SEO_DETAIL_PICTURE_ALT' => [
					'ID' => 'SEO_DETAIL_PICTURE_ALT',
					'NAME' => 'ALT ' . GetMessage("ACRIT_IMPORT_DLA_DETALQNOY_KARTIN")
				],
				'SEO_DETAIL_PICTURE_TITLE' => [
					'ID' => 'SEO_DETAIL_PICTURE_TITLE',
					'NAME' => 'TITLE ' . GetMessage("ACRIT_IMPORT_DLA_DETALQNOY_KARTIN")
				],
				'SEO_DETAIL_PICTURE_FILENAME' => [
					'ID' => 'SEO_DETAIL_PICTURE_FILENAME',
					'NAME' => GetMessage("ACRIT_IMPORT_IMA_FAYLA_DLA_DETALQ")
				],
			]
		];

		// catalog fields
		if ($incl_catalog) {
			$arIBlockFields['CATALOG'] = [
				'TITLE' => GetMessage("ACRIT_IMPORT_CATALOG_FIELDS"),
				'ITEMS' => [
					'CATALOG_WEIGHT' => [
						'ID' => 'CATALOG_WEIGHT',
						'NAME' =>  GetMessage("ACRIT_IMPORT_CATALOG_FIELDS_WEIGHT")
					],
					'CATALOG_WIDTH' => [
						'ID' => 'CATALOG_WIDTH',
						'NAME' =>  GetMessage("ACRIT_IMPORT_CATALOG_FIELDS_WIDTH")
					],
					'CATALOG_LENGTH' => [
						'ID' => 'CATALOG_LENGTH',
						'NAME' =>  GetMessage("ACRIT_IMPORT_CATALOG_FIELDS_LENGTH")
					],
					'CATALOG_HEIGHT' => [
						'ID' => 'CATALOG_HEIGHT',
						'NAME' =>  GetMessage("ACRIT_IMPORT_CATALOG_FIELDS_HEIGHT")
					],
					'CATALOG_CAT_BARCODE' => [
						'ID' => 'CATALOG_CAT_BARCODE',
						'NAME' =>  GetMessage("ACRIT_IMPORT_CATALOG_FIELDS_CAT_BARCODE")
					],
				]
			];
		}

		// Catalog prices
		if ($incl_catalog) {
			$arIBlockFields['PRICES'] = [
				'TITLE' => GetMessage("ACRIT_IMPORT_CENY"),
			];
			$arIBlockFields['PRICES']['ITEMS']['CATALOG_PURCHASING_PRICE'] = [
				'ID' => 'CATALOG_PURCHASING_PRICE',
				'NAME' =>  GetMessage("ACRIT_IMPORT_CATALOG_FIELDS_PURCHASING_PRICE")
			];
			$arIBlockFields['PRICES']['ITEMS'][ 'CATALOG_PURCHASING_CURRENCY' ] = [
				'ID' => 'CATALOG_PURCHASING_CURRENCY',
				'NAME' => GetMessage("ACRIT_IMPORT_CATALOG_CURRENCY", ['#PRICE_NAME#' => GetMessage("ACRIT_IMPORT_CATALOG_FIELDS_PURCHASING_PRICE")])
			];

			$res = \Bitrix\Catalog\GroupTable::getList([
				'filter' => [],
				'order' => ['ID' => 'asc'],
			]);
			while ($arItem = $res->fetch()) {
				$arIBlockFields['PRICES']['ITEMS']['PRICE_' . $arItem['ID']] = [
					'ID' => 'PRICE_' . $arItem['ID'],
					'NAME' => $arItem['NAME'] . ', ID ' . $arItem['ID']
				];
				$arIBlockFields['PRICES']['ITEMS'][ 'CURRENCY_' . $arItem['ID']] = [
					'ID' => 'CURRENCY_' . $arItem['ID'],
					'NAME' => GetMessage("ACRIT_IMPORT_CATALOG_CURRENCY", ['#PRICE_NAME#' => $arItem['NAME'] . ', ID ' . $arItem['ID']])
				];
			}
		}
		// Catalog stores
		if ($incl_catalog) {
			$arIBlockFields['STORES']                      = [
				'TITLE' => GetMessage("ACRIT_IMPORT_SKLADY"),
			];
			$arIBlockFields['STORES']['ITEMS']['QUANTITY'] = [
				'ID' => 'QUANTITY',
				'NAME' => GetMessage("ACRIT_IMPORT_DOSTUPNOE_KOLICESTVO")
			];
			$res = \Bitrix\Catalog\StoreTable::getList([
				'filter' => [],
				'order' => ['ID' => 'asc'],
			]);
			while ($arItem = $res->fetch()) {
				$arIBlockFields['STORES']['ITEMS']['STORE_' . $arItem['ID']] = [
					'ID' => 'STORE_' . $arItem['ID'],
					'NAME' => $arItem['TITLE'] . ', ID ' . $arItem['ID'] . ' (' . ($arItem['ACTIVE'] == 'Y' ? GetMessage("ACRIT_IMPORT_AKTIVNYY") : GetMessage("ACRIT_IMPORT_NE_AKTIVNYY")) . ')'
				];
			}
		}
		// Catalog Offer Parent
		$arIBlockFields['OFFERS'] = [
			'TITLE' => GetMessage("ACRIT_IMPORT_PRINADLEJNOSTQ_TORGO"),
			'ITEMS' => [
				'OFFER_PARENT_XML_ID' => [
					'ID' => 'OFFER_PARENT_XML_ID',
					'NAME' => GetMessage("ACRIT_IMPORT_XML_ID_RODITELQSKOGO_TO")
				],
				'OFFER_PARENT_ID' => [
					'ID' => 'OFFER_PARENT_ID',
					'NAME' => GetMessage("ACRIT_IMPORT_ID_RODITELQSKOGO_TOVARA")
				],
				'OFFER_PARENT_CODE' => [
					'ID' => 'OFFER_PARENT_CODE',
					'NAME' => GetMessage("ACRIT_IMPORT_KOD_RODITELQSKOGO_TO")
				],
				'OFFER_PARENT_NAME' => [
					'ID' => 'OFFER_PARENT_NAME',
					'NAME' => GetMessage("ACRIT_IMPORT_IMA_RODITELQSKOGO_TO")
				],
			]
		];
		// IBlock category params
		$arIBlockFields['CATEG_PARAMS'] = [
			'TITLE' => GetMessage("ACRIT_IMPORT_PARAMETRY_DLA_NOVOY"),
			'ITEMS' => [
				'CATEG_PARAMS_ID' => [
					'ID' => 'CATEG_PARAMS_ID',
					'NAME' => 'ID'
				],
				'CATEG_PARAMS_NAME' => [
					'ID' => 'CATEG_PARAMS_NAME',
					'NAME' => GetMessage("ACRIT_IMPORT_NAZVANIE")
				],
				'CATEG_PARAMS_CODE' => [
					'ID' => 'CATEG_PARAMS_CODE',
					'NAME' => GetMessage("ACRIT_IMPORT_SIMVOLQNYY_KOD")
				],
				'CATEG_PARAMS_EXTERNAL_ID' => [
					'ID' => 'CATEG_PARAMS_EXTERNAL_ID',
					'NAME' => GetMessage("ACRIT_IMPORT_VNESNIY_KOD")
				],
				'CATEG_PARAMS_ACTIVE' => [
					'ID' => 'CATEG_PARAMS_ACTIVE',
					'NAME' => GetMessage("ACRIT_IMPORT_FLD_ACTIVE")
				],
				'CATEG_PARAMS_SORT' => [
					'ID' => 'CATEG_PARAMS_SORT',
					'NAME' => GetMessage("ACRIT_IMPORT_SORTIROVKA")
				],
				'CATEG_PARAMS_IMAGE' => [
					'ID' => 'CATEG_PARAMS_IMAGE',
					'NAME' => GetMessage("ACRIT_IMPORT_IZOBRAJENIE")
				],
				'CATEG_PARAMS_PICTURE' => [
					'ID' => 'CATEG_PARAMS_PICTURE',
					'NAME' => GetMessage("ACRIT_IMPORT_DETALQNAA_KARTINKA")
				],
				'CATEG_PARAMS_DESCRIPTION' => [
					'ID' => 'CATEG_PARAMS_DESCRIPTION',
					'NAME' => GetMessage("ACRIT_IMPORT_FLD_DESCRIPTION")
				],
				'CATEG_PARAMS_SEO_H1' => [
					'ID' => 'CATEG_PARAMS_SEO_H1',
					'NAME' => GetMessage("ACRIT_IMPORT_ZAGOLOVOK_KATEGORII")
				],
				'CATEG_PARAMS_SEO_TITLE' => [
					'ID' => 'CATEG_PARAMS_SEO_TITLE',
					'NAME' => GetMessage("ACRIT_IMPORT_ZAGOLOVOK_OKNA_BRAUZ")
				],
				'CATEG_PARAMS_SEO_KEYWORDS' => [
					'ID' => 'CATEG_PARAMS_SEO_KEYWORDS',
					'NAME' => GetMessage("ACRIT_IMPORT_KLUCEVYE_SLOVA")
				],
				'CATEG_PARAMS_SEO_DESCRIPTION' => [
					'ID'   => 'CATEG_PARAMS_SEO_DESCRIPTION',
					'NAME' => GetMessage("ACRIT_IMPORT_META_OPISANIE")
				],
			]
		];
		$userFieldList = Import\Helper::getUserFieldList([
			'=ENTITY_ID' => sprintf('IBLOCK_%d_SECTION', $arProfile['IBLOCK_ID'])
		]);
		foreach ($userFieldList as $sectionUField) {
			$internalName = 'CATEG_PARAMS_' . $sectionUField['FIELD_NAME'];
			$arIBlockFields['CATEG_PARAMS']['ITEMS'][ $internalName ] = [
				'ID'   => $internalName,
				'NAME' => $sectionUField['MAIN_USER_FIELD_TITLE_EDIT_FORM_LABEL'] ?? $sectionUField['FIELD_NAME']
			];
		}
		unset($userFieldList);
		// endregion

		// Get saved data
		if ($ID) {
			$res = $obProfileFields::getList([
				'order' => ['ID' => 'asc'],
				'filter' => ['PARENT_ID' => $ID],
			]);
			while ($arField = $res->fetch()) {
				if (isset($arSourceFields[$arField['F_FIELD']])) {
					$arSourceFields[$arField['F_FIELD']]['SAVED_FIELD'] = $arField['IB_FIELD'];
					$arSourceFields[$arField['F_FIELD']]['PARAMS']      = $arField['PARAMS'];
				}
			}
		}

		// region List of fields params
		$arFieldsParams                     = [];
		$arFieldsParams['required']         = [
			'TYPE' => 'boolean',
			'DEFAULT' => 'N',
			'LABEL' => GetMessage("ACRIT_IMPORT_OBAZATELQNOE"),
			'HINT' => GetMessage("ACRIT_IMPORT_IGNORIROVATQ_ELEMENT"),
		];
		$arFieldsParams['not_empty']        = [
			'TYPE' => 'boolean',
			'DEFAULT' => 'N',
			'LABEL' => GetMessage("ACRIT_IMPORT_PARAMS_NOT_EMPTY"),
			'HINT' => GetMessage("ACRIT_IMPORT_PARAMS_NOT_EMPTY_HINT"),
		];
		$arFieldsParams['milt_delimiter']   = [
			'TYPE' => 'string',
			'DEFAULT' => '',
			'LABEL' => GetMessage("ACRIT_IMPORT_PARAMS_MULT_DELIM"),
			'HINT' => GetMessage("ACRIT_IMPORT_PARAMS_MULT_DELIM_HINT"),
		];
		$arFieldsParams['url_decode']       = [
			'TYPE' => 'boolean',
			'DEFAULT' => 'N',
			'LABEL' => GetMessage("ACRIT_IMPORT_DEKODIROVANIE_STROKI"),
			'HINT' => GetMessage("ACRIT_IMPORT_PRI_NAHOJDENII_V_STR"),
		];
		$arFieldsParams['register_change']  = [
			'TYPE' => 'list',
			'DEFAULT' => '',
			'LABEL' => GetMessage("ACRIT_IMPORT_IZMENITQ_REGISTR"),
			'HINT' => GetMessage("ACRIT_IMPORT_PREOBRAZOVATQ_REGIST"),
			'LIST' => [
				'low' => GetMessage("ACRIT_IMPORT_NA_NIJNIY"),
				'up' => GetMessage("ACRIT_IMPORT_NA_VERHNIY"),
				'first' => GetMessage("ACRIT_IMPORT_NA_PERVUU_ZAGLAVNUU"),
				'each' => GetMessage("ACRIT_IMPORT_NA_KAJDUU_ZAGLAVNUU"),
			],
		];
		$arFieldsParams['str_limit']        = [
			'TYPE' => 'num',
			'DEFAULT' => '0',
			'LABEL' => GetMessage("ACRIT_IMPORT_MAKSIMALQNOE_KOLICES"),
			'HINT' => GetMessage("ACRIT_IMPORT_SIMVOLY_NE_POPAVSIE"),
		];
		$arFieldsParams['price_vatincl']    = [
			'TYPE' => 'boolean',
			'DEFAULT' => 'N',
			'LABEL' => GetMessage("ACRIT_IMPORT_POMECATQ_CENU_KAK_VK"),
			'HINT' => GetMessage("ACRIT_IMPORT_VATINCL"),
		];
		$arFieldsParams['str_dateformat']   = [
			'TYPE' => 'string',
			'DEFAULT' => '',
			'PLACEHOLDER' => CSite::GetDateFormat(),
			'LABEL' => GetMessage("ACRIT_IMPORT_DATE_FORMAT"),
			'HINT' => GetMessage("ACRIT_IMPORT_DATEFORMAT"),
		];
		$arFieldsParams['num_round']        = [
			'TYPE' => 'boolean',
			'DEFAULT' => 'N',
			'LABEL' => GetMessage("ACRIT_IMPORT_ROUNDING"),
			'HINT' => GetMessage("ACRIT_IMPORT_NUMROUNDING"),
			'ADD_PARAMS' => [
				'GENERAL' => GetMessage("ACRIT_ROUND_GENERAL"),
				'TOHIGHEST' => GetMessage("ACRIT_ROUND_TOHIGHEST"),
				'TOLOWEST' => GetMessage("ACRIT_ROUND_TOLOWEST"),
				'TOEVEN' => GetMessage("ACRIT_ROUND_TOEVEN"),
				'TOODD' => GetMessage("ACRIT_ROUND_TOODD"),
				'TONINE' => GetMessage("ACRIT_ROUND_TONINE"),
			],
			'ADD_PRECISION' => [
				'TOSOTOIA' => GetMessage("ACRIT_PRECISION_TOSOTOIA"),
				'TODESYATAYA' => GetMessage("ACRIT_PRECISION_TODESYATAYA"),
				'TOONE' => GetMessage("ACRIT_PRECISION_TOONE"),
				'TODOZEN' => GetMessage("ACRIT_PRECISION_TODOZEN"),
				'TOHUNDREDS' => GetMessage("ACRIT_PRECISION_TOHUNDREDS"),
				'THOUSENDS' => GetMessage("ACRIT_PRECISION_THOUSENDS"),
			],
		];
		$arFieldsParams['cut_htmltags']     = [
			'TYPE' => 'boolean',
			'DEFAULT' => 'N',
			'LABEL' => GetMessage("ACRIT_IMPORT_CUTHTML"),
			'HINT' => GetMessage("ACRIT_IMPORT_CUTHTML_HINT"),
		];
		$arFieldsParams['cut_special']      = [
			'TYPE' => 'boolean',
			'DEFAULT' => 'N',
			'LABEL' => GetMessage("ACRIT_IMPORT_CUTSPECIAL"),
			'HINT' => GetMessage("ACRIT_IMPORT_CUTSPECIAL_HINT"),
		];
		$arFieldsParams['html_to_special']  = [
			'TYPE' => 'boolean',
			'DEFAULT' => 'N',
			'LABEL' => "HTML-" . GetMessage("ACRIT_IMPORT_SUSNOSTI_V_SIMVOLY"),
			'HINT' => GetMessage("ACRIT_IMPORT_PREOBRAZOVATQ_SPECIA"),
		];
		$arFieldsParams['work_picture']     = [
			'TYPE' => 'boolean',
			'DEFAULT' => 'N',
			'ADD_PARAMS' => [
				'WIDTH' => GetMessage("ACRIT_IMAGES_WIDTH"),
				'HEIGHT' => GetMessage("ACRIT_IMAGES_HEIGHT"),
				'PROCESS_TYPE' => GetMessage("ACRIT_IMAGES_PROCESS_TYPE"),
				'PROPORTIONAL' => GetMessage("ACRIT_IMAGES_PROPORTIONAL_TYPE"),
				'CUT' => GetMessage("ACRIT_IMAGES_TYPE_CUT"),
				'QUALITY' => GetMessage("ACRIT_IMAGES_QUALITY"),
				'FILE_EXTENSION' => GetMessage("ACRIT_IMAGES_FILE_EXTENSION"),
			],
			'LABEL' => GetMessage("ACRIT_IMPORT_PROCESS_IMAGES"),
			'HINT' => GetMessage("ACRIT_IMPORT_PROCESS_IMAGES_HINT"),
		];
		$arFieldsParams['formula']          = [
			'TYPE' => 'string',
			'DEFAULT' => '',
			'LABEL' => GetMessage("ACRIT_IMPORT_FORMULA"),
			'HINT' => GetMessage("ACRIT_IMPORT_MODIFIKACIA_ZNACENIA"),
		];
		$arFieldsParams['cond_values_req']  = [
			'TYPE' => 'string',
			'MULTIPLE' => true,
			'DEFAULT' => '',
			'LABEL' => GetMessage("ACRIT_IMPORT_OBAZATELQNYE_ZNACENI"),
			'HINT' => GetMessage("ACRIT_IMPORT_ZAGRUJATQ_POZICIU_TO"),
		];
		$arFieldsParams['cond_values_excl'] = [
			'TYPE' => 'string',
			'MULTIPLE' => true,
			'DEFAULT' => '',
			'LABEL' => GetMessage("ACRIT_IMPORT_ISKLUCAUSIE_ZNACENIA"),
			'HINT' => GetMessage("ACRIT_IMPORT_NE_ZAGRUJATQ_POZICIU"),
		];
		$arFieldsParams['sect_hierarchy']   = [
			'TYPE' => 'boolean',
			'DEFAULT' => 'N',
			'LABEL' => GetMessage("ACRIT_IMPORT_PARAMS_SECT_HIERARCHY_LABEL"),
			'HINT' => GetMessage("ACRIT_IMPORT_PARAMS_SECT_HIERARCHY_HINT"),
		];
		// endregion

		// Values of fields params
		foreach ($arSourceFields as $k => $arSField) {
			foreach ($arFieldsParams as $name => $arFieldsParam) {
				if (isset($arSField['PARAMS'][$name])) {
					$arSourceFields[$k]['PARAMS'][$name] = $arSField['PARAMS'][$name];
				} else {
					$arSourceFields[$k]['PARAMS'][$name] = $arFieldsParam['DEFAULT'];
				}
			}
		}
	}

	if ($STEP_SHOW == 3) {
		// Sections for default
		$arIBSections = [];
		$arFilter     = [
			'IBLOCK_ID' => $arProfile['IBLOCK_ID'],
			'GLOBAL_ACTIVE' => 'Y',
		];
		$arSelect     = ['IBLOCK_ID', 'ID', 'NAME', 'DEPTH_LEVEL', 'IBLOCK_SECTION_ID'];
		$arOrder      = ['left_margin' => 'ASC', 'SORT' => 'ASC'];
		$rsSections   = CIBlockSection::GetList($arOrder, $arFilter, false, $arSelect);
		while ($arSection = $rsSections->GetNext()) {
			$arFields     = [
				'value' => $arSection['ID'],
			];
			$level_visual = str_repeat('-', $arSection['DEPTH_LEVEL']);
			$arFields['name'] = $level_visual . ' ' . $arSection['NAME'];
			if ($arSection['ID'] == $arProfile['DEFAULT_SECTION_ID']) {
				$arFields['selected'] = true;
			}
			$arIBSections[$arSection['ID']] = $arFields;
		}

	}

	/**
	 * Display admin page
	 */

	$profileTitle = GetMessage( "ACRIT_IMPORT_EDITPROFILE" ).": #".$arProfile["ID"]." ".$arProfile["NAME"];
	$APPLICATION->SetTitle( ( $ID > 0 ? $profileTitle : GetMessage( "ACRIT_IMPORT_ADDPROFILE" ) ) );

	require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");

	// AcritLicence::Show();

	$aMenu = [
		[
			"TEXT" => GetMessage("ACRIT_IMPORT_LIST"),
			"TITLE" => GetMessage("ACRIT_IMPORT_LIST"),
			"LINK" => "acrit.import_list.php?lang=" . LANG,
			"ICON" => "btn_list",
		],
		/*array(
			"TEXT" => GetMessage( "ACRIT_IMPORT_INSTRUCTION" ),
			"TITLE" => GetMessage( "ACRIT_IMPORT_INSTRUCTION" ),
			"LINK" => "http://www.acrit-studio.ru/technical-support/configuring-the-module-export-on-trade-portals/",
			"LINK_PARAM" => "target='blank'",
			"ICON" => "",
		),*/
	];

	if ($ID > 0) {
		$aMenu[] = ["SEPARATOR" => "Y"];
		$aMenu[] = [
			"TEXT" => GetMessage("ACRIT_IMPORT_ADD"),
			"TITLE" => GetMessage("ACRIT_IMPORT_ADD"),
			"LINK" => "acrit.import_edit.php?lang=" . LANG,
			"ICON" => "btn_new",
		];
		$aMenu[] = [
			"TEXT" => GetMessage("ACRIT_IMPORT_COPY"),
			"TITLE" => GetMessage("ACRIT_IMPORT_COPY"),
			"LINK" => "acrit.import_edit.php?copy=$ID&ID=$ID&lang=" . LANG,
			"ICON" => "btn_copy",
		];
		$aMenu[] = [
			"TEXT" => GetMessage("ACRIT_IMPORT_DEL"),
			"TITLE" => GetMessage("ACRIT_IMPORT_DEL"),
			"LINK" => "javascript:if(confirm('" . GetMessage("ACRIT_IMPORT_PROFILE_DELETE_CONFIRM") . "'))window.location='acrit.import_list.php?ID=" . $ID . "&action=delete&lang=" . LANG . "&" . bitrix_sessid_get() . "';",
			"ICON" => "btn_delete",
		];
		$aMenu[] = ["SEPARATOR" => "Y"];
	}
	$context = new CAdminContextMenu($aMenu);
	$context->Show();

	if (!isset($_REQUEST["ajax"]) && !isset($_REQUEST["ib"]) && !isset($_REQUEST["ajax_start"]) && !isset($_REQUEST["ajax_count"]) && !isset($_POST["auth"])) {
		$arTabs   = [
			[
				"DIV" => "step1",
				"TAB" => GetMessage("ACRIT_IMPORT_STEP1_TAB_NAME"),
				"ICON" => "main_user_edit",
				"TITLE" => GetMessage("ACRIT_IMPORT_STEP1_TAB_TITLE")
			],
		];
		$arTabs[] = [
			"DIV" => "step2",
			"TAB" => GetMessage("ACRIT_IMPORT_STEP2_TAB_NAME"),
			"ICON" => "main_user_edit",
			"TITLE" => GetMessage("ACRIT_IMPORT_STEP2_TAB_TITLE")
		];
		$arTabs[] = [
			"DIV" => "step3",
			"TAB" => GetMessage("ACRIT_IMPORT_STEP3_TAB_NAME"),
			"ICON" => "main_user_edit",
			"TITLE" => GetMessage("ACRIT_IMPORT_STEP3_TAB_TITLE")
		];
		$arTabs[] = [
			"DIV" => "step4",
			"TAB" => GetMessage("ACRIT_IMPORT_STEP4_TAB_NAME"),
			"ICON" => "main_user_edit",
			"TITLE" => GetMessage("ACRIT_IMPORT_STEP4_TAB_TITLE")
		];

		$tabControl = new CAdminTabControl("tabControlAcritImportProfileEdit", $arTabs, false, true);

		if ($lastException) {
			CAdminMessage::ShowMessage(
				[
					"MESSAGE" => $lastException,
					"HTML" => "TRUE",
				]
			);
		}

		// Check modules
		if (!extension_loaded('XMLReader')) {
			CAdminMessage::ShowMessage(
				[
					"MESSAGE" => GetMessage("ACRIT_IMPORT_SERVER_EXT_NOT_FOUND", ['#MODULE_NAME#' => 'XMLReader']),
					"HTML" => "TRUE",
				]
			);
			return;
		}

		// Notify if has updates
		include 'include/update_notifier.php';

		$bDemo        = false;
		$moduleStatus = CModule::IncludeModuleEx($moduleId);
		if ($moduleStatus != 1) {
			$bDemo = true;

			$timeDemoOff = "";
			if ($info = CModule::CreateModuleObject($moduleId)) {
				$timeDemoOff = ConvertTimeStamp($GLOBALS["SiteExpireDate_" . str_replace(".", "_", $info->MODULE_ID)], "SHORT");
			}
		}
		?>

		<? if ($bDemo) { ?>
			<? $buyLicenceUrl = GetMessage("ACRIT_IMPORT_BUY_LICENCE_URL"); ?>
			<div class="adm-info-message" style="float: right; padding-left: 20px;">
				<div style="padding-left: 20px; float: right;">
					<a href="<?=$buyLicenceUrl?>" target="_blank" class="adm-btn adm-btn-save"><?=GetMessage("ACRIT_IMPORT_BUY_LICENCE_INFO")?></a>
				</div>
				<div style="float: left; padding-top: 5px;"><?=GetMessage("ACRIT_IMPORT_DEMO_PERIOD_INFO") . "<b>" . $timeDemoOff . "</b>";?></div>
				<div style="clear: both;"></div>
			</div>
		<?
		} ?>

		<?
		// Check extensions
		$ext_not_found = CAcritImport::checkExtensions();
		if (!empty($ext_not_found)):?>
			<div class="adm-info-message">
				<?=GetMessage("ACRIT_IMPORT_SERVER_EXT_NOT_FOUND", ['#MODULE_NAME#' => implode(', ', $ext_not_found)]);?>
			</div>
		<?endif; ?>

		<? if ($arProfile['ID'] && (Agents::isLocked($arProfile['ID']) || Agents::isDoubleRun($arProfile['ID'], 0))) { ?>
			<div class="adm-info-message" id="acrit_imp_lock_reset">
				<div><?=GetMessage("ACRIT_IMPORT_LOCK_RESET_DESCR")?> <a href="#"><?=GetMessage("ACRIT_IMPORT_LOCK_RESET_LINK")?></a></div>
			</div>
		<? } ?>

		<div style="clear: both;"></div>

		<form method="POST" action="" ENCTYPE="multipart/form-data" id="import_form" name="import_form">
			<?// check session id?>
			<?=bitrix_sessid_post();?>
			<?
			// show bookmark headers
			$tabControl->Begin();
			?>
			<div id="waitContainer" style="position: fixed; float: right; width: 100%; right: 0;"></div>
			<?
			$tabControl->BeginNextTab();
			if ($STEP_SHOW == 1) {
				include 'include/edit_step1.php';
			}
			$tabControl->EndTab();

			$tabControl->BeginNextTab();
			if ($STEP_SHOW == 2) {
				include 'include/edit_step2.php';
			}
			$tabControl->EndTab();

			$tabControl->BeginNextTab();
			if ($STEP_SHOW == 3) {
				include 'include/edit_step3.php';
			}
			$tabControl->EndTab();

			$tabControl->BeginNextTab();
			if ($STEP_SHOW == 4) {
				include 'include/edit_step4.php';
			}

			$tabControl->EndTab();

			// end of form - show save buttons
			$tabControl->Buttons();

			if ($STEP_SHOW < 5): ?>
				<input type="hidden" name="STEP_SHOW" value="<?=($STEP_SHOW + 1);?>">
				<input type="hidden" name="STEP_SAVE" value="<?=$STEP_SHOW;?>">
				<input type="hidden" name="SAVE_TYPE" value="">
				<? echo bitrix_sessid_post(); ?>

				<?if ($STEP_SHOW > 1): ?>
					<input type="submit" name="backButton" value="&lt;&lt; <?=GetMessage("ACRIT_IMPORT_STEP_BACK");?>">
				<?endif;

				if ($STEP_SHOW == 4):?>
					<input type="submit" name="backButton2" value="&lt;&lt; <?=GetMessage("ACRIT_IMPORT_STEP_FIRST");?>">
					<? if ($right < "W"):?>
						<input type="submit" value="<?=GetMessage("ACRIT_IMPORT_CLOSE_FINAL");?>" name="allclose_btn" class="adm-btn-save">
					<? else:?>
						<input type="submit" value="<?=GetMessage("ACRIT_IMPORT__SIMPLE_STEP_APPLY");?>" name="apply_btn">
						<input type="submit" value="<?=GetMessage("ACRIT_IMPORT_NEXT_FINAL");?>" name="submit_btn" class="adm-btn-save">
					<?endif; ?>
				<? else:?>
					<?
					if ($right < "W"):?>
						<input type="submit" value="<?=GetMessage("ACRIT_IMPORT__SIMPLE_STEP_NEXT");?> &gt;&gt;" name="next_btn" class="adm-btn-save">
					<? else:?>
						<input type="submit" value="<?=GetMessage("ACRIT_IMPORT__SIMPLE_STEP_APPLY");?>" name="apply_btn">
						<input type="submit" value="<?=GetMessage("ACRIT_IMPORT_STEP_NEXT");?> &gt;&gt;" name="submit_btn" class="adm-btn-save">
					<?endif; ?>
				<?endif;
				/*if ($STEP_SHOW == 2)
				{
				?>
					<script type="text/javascript">
						DeactivateAllExtra();
						ChangeExtra();
					</script>
				<?
				}*/
				?>
			<?
			endif;

			$tabControl->End();
			$tabControl->ShowWarnings("import_form", $message);
			?>
		</form>
		<?
	}
}
?>
<script type="text/javascript">
	/** @var {BX.adminTabControl} tabControlAcritImportProfileEdit */
	<?
	for ($i = 1; $i <= 4; $i++):
		if ($i == $STEP_SHOW):?>
			tabControlAcritImportProfileEdit.SelectTab("step<?=$i;?>");
		<? else: ?>
			tabControlAcritImportProfileEdit.DisableTab("step<?=$i;?>");
		<? endif;
	endfor;
	?>
	acrit_import_profile_id = '<?=$ID;?>';

	// Lang
	BX.message({'ACRIT_IMPORT_AGENTS_EDIT': '<?=GetMessage('ACRIT_IMPORT_AGENTS_EDIT');?>'});
	BX.message({'ACRIT_IMPORT_AGENTS_DEL': '<?=GetMessage('ACRIT_IMPORT_AGENTS_DEL');?>'});
	BX.message({'ACRIT_IMPORT_AGENTS_DEL_WARN': '<?=GetMessage('ACRIT_IMPORT_AGENTS_DEL_WARN');?>'});
</script>
<style>
	.adm-info-message-center {margin:0px auto; display:flex; flex-direction: column; align-items: center}
	#tabControlAcritImportProfileEdit_layout .adm-detail-iblock-types {width:300px;}
	.fields-add-params-s {margin-top: 5px;margin-left: 10px;}
</style>

<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php"); ?>