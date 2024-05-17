<?php
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

use
	Bitrix\Main\Loader,
	Bitrix\Main\SystemException,
	Acrit\Import;

IncludeModuleLangFile(__FILE__);

$moduleStatus = CModule::IncludeModuleEx($moduleId);
if ($moduleStatus == MODULE_DEMO_EXPIRED) {
	$buyLicenceUrl = GetMessage("ACRIT_IMPORT_BUY_LICENCE_URL");
	require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php"); ?>
	<div class="adm-info-message">
		<div class="acrit_note_button">
			<a href="<?=$buyLicenceUrl?>" target="_blank" class="adm-btn adm-btn-save"><?=GetMessage("ACRIT_IMPORT_DEMOEND_BUY_LICENCE_INFO")?></a>
		</div>
		<div class="acrit_note_text"><?=GetMessage("ACRIT_IMPORT_DEMOEND_PERIOD_INFO");?></div>
		<div class="acrit_note_clr"></div>
	</div>
<?
} else {
	require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/$moduleId/include.php");

	CModule::IncludeModule($moduleId);

	CUtil::InitJSCore(["ajax", "jquery"]);
	$APPLICATION->AddHeadScript("/bitrix/js/iblock/iblock_edit.js");
	$APPLICATION->AddHeadScript("/bitrix/js/$moduleId/main.js");
	$t = CJSCore::getExtInfo("jquery");

	if (!is_array($t) || !isset($t["js"]) || !file_exists(Loader::getDocumentRoot() . $t["js"])) {
		$APPLICATION->ThrowException(GetMessage("ACRIT_IMPORT_JQUERY_REQUIRE"));
	}

	$POST_RIGHT = $APPLICATION->GetGroupRight($moduleId);
	if ($POST_RIGHT == "D")
		$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

	if (!CModule::IncludeModule("iblock")) {
		return false;
	}

	IncludeModuleLangFile(__FILE__);

	$sTableID = "tbl_acrit_import_profile";

	function CheckFilter()
	{
		global $FilterArr, $lAdmin;
		foreach ($FilterArr as $f) {
			global $$f;
		}

		return count($lAdmin->arFilterErrors) == 0;
	}

	$oSort     = new CAdminSorting($sTableID, "ID", "desc");
	$lAdmin    = new CAdminList($sTableID, $oSort);
	$obProfile = new Acrit\Import\ProfileTable();

	$FilterArr = [
		"find_id",
		"find_name",
		"find_active",
		"find_type",
//        "find_type_run",
		"find_timestamp",
		"find_start_last_time",
	];

	/** @var $find_name string */
	/** @var $find_active string */
	/** @var $find_type string */
	/** @var $find_timestamp string */
	/** @var $find_start_last_time string */
	/** @var $FIELDS array */

	/** @var $by string */
	/** @var $order string */

	$lAdmin->InitFilter($FilterArr);
	if (CheckFilter()) {
		$arFilter = [
//            "ID" => $find_id,
//            "NAME" => $find_name,
//            "ACTIVE" => $find_active,
//            "TYPE" => $find_type,
//            "TIMESTAMP_X" => $find_timestamp,
//            "START_LAST_TIME" => $find_start_last_time,
		];
		if (isset($find_id)) {
			$arFilter['ID'] = $find_id;
		}
		if (isset($find_id)) {
			$arFilter['NAME'] = $find_name;
		}
		if (isset($find_id)) {
			$arFilter['ACTIVE'] = $find_active;
		}
		if (isset($find_id)) {
			$arFilter['TYPE'] = $find_type;
		}
		if (isset($find_id)) {
			$arFilter['TIMESTAMP_X'] = $find_timestamp;
		}
		if (isset($find_id)) {
			$arFilter['START_LAST_TIME'] = $find_start_last_time;
		}
	}

	// Bulk edit
	if ($lAdmin->EditAction() && ($POST_RIGHT == "W")) {
		if (is_array($FIELDS) && !empty($FIELDS)) {
			foreach ($FIELDS as $ID => $arFields) {
				if (!$lAdmin->IsUpdated($ID)) {
					continue;
				}

				$DB->StartTransaction();
				$ID  = IntVal($ID);
				$res = $obProfile->update($ID, $arFields);
				if (!$res->isSuccess()) {
					$lAdmin->AddUpdateError(GetMessage("export_save_err") . $ID . ": " . implode(' ', $res->getErrorMessages()), $ID);
					$DB->Rollback();
				}
				$DB->Commit();
			}
		}
	}

	// region Group actions
	if (($arID = $lAdmin->GroupAction()) && $POST_RIGHT == "W") {
		// if selected "for all elements"
		if ($_REQUEST["action_target"] == "selected") {
			$rsData = $obProfile->getList([
				'order' => [$by => $order],
				'filter' => $arFilter
			]);

			while ($arRes = $rsData->fetch()) {
				$arID[] = $arRes["ID"];
			}
		}

		if (is_array($arID) && !empty($arID)) {
			foreach ($arID as $ID) {
				if (strlen($ID) <= 0)
					continue;

				$ID = IntVal($ID);

				switch ($_REQUEST["action"]) {
					case "delete":
						@set_time_limit(0);
						$DB->StartTransaction();

//                        CExportproplusAgent::DelAgent( $ID );
						if ($obProfile->Delete($ID)) {
							// Delete fields map
							$obProfileFields = new Import\ProfileFieldsTable();
							$res             = $obProfileFields->getList([
								'order' => ['ID' => 'asc'],
								'filter' => ['PARENT_ID' => $ID],
							]);
							while ($arField = $res->fetch()) {
								$obProfileFields->delete($arField['ID']);
							}
						} else {
							$DB->Rollback();
							$lAdmin->AddGroupError(GetMessage("rub_del_err"), $ID);
						}

						$DB->Commit();
						break;

					case "activate":
					case "deactivate":
						if (($rsData = $obProfile->getById($ID))) {
							$arData["ACTIVE"] = ($_REQUEST["action"] == "activate" ? "Y" : "N");

//                            if( $rsData["SETUP"]["TYPE_RUN"] == "cron" ){
//                                if( $rsData["ACTIVE"] != "Y" ){
//                                    CExportproplusAgent::DelAgent( $ID );
//                                }
//                                else{
//                                    CExportproplusAgent::AddAgent( $ID );
//                                }
//                            }
//                            else{
//                                CExportproplusAgent::DelAgent( $ID );
//                            }

							if (!$obProfile->update($ID, $arData)) {
								$lAdmin->AddGroupError(GetMessage("rub_save_error") . $obProfile->LAST_ERROR, $ID);
							}
						} else
							$lAdmin->AddGroupError(GetMessage("rub_save_error") . " " . GetMessage("rub_no_rubric"), $ID);
						break;
				}
			}
		}
	}
	// endregion

	$lAdmin->AddHeaders(
		[
			[
				"id" => "ID",
				"content" => "ID",
				"sort" => "ID",
				"align" => "right",
				"default" => true,
			],
			[
				"id" => "ACTIVE",
				"content" => GetMessage("import_active"),
				"sort" => "ACTIVE",
				"align" => "left",
				"default" => true,
			],
			[
				"id" => "NAME",
				"content" => GetMessage("IMPORT_LIST_NAME"),
				"sort" => "NAME",
				"default" => true,
			],
			[
				"id" => "TYPE",
				"content" => GetMessage("import_type"),
				"sort" => "TYPE",
				"default" => true,
			],
//            array(
//                "id" => "TYPE_RUN",
//                "content" => GetMessage( "import_type_run" ),
//                "sort" => "type_run",
//                "default" => true,
//            ),
			[
				"id" => "TIMESTAMP_X",
				"content" => GetMessage("import_updated"),
				"sort" => "TIMESTAMP_X",
				"default" => true,
			],
			[
				"id" => "START_LAST_TIME",
				"content" => GetMessage("import_start_last_time"),
				"sort" => "START_LAST_TIME",
				"default" => true,
			],
		]
	);

	$rsData = $obProfile->getList([
		'order' => [$by => $order],
		'filter' => $arFilter,
	]);

	$rsData = new CAdminResult($rsData, $sTableID);

	$rsData->NavStart();
	$lAdmin->NavText($rsData->GetNavPrint(GetMessage("import_nav")));

	while ($arRes = $rsData->NavNext(true, "f_")) {
		/** @var int $f_ID */
		/** @var int $f_NAME */

		if ($arRes["ACTIVE"] != "Y") {
			$statVal = "<div style='display: inline-block; width: 12px; height: 12px; margin: 3px 7px 0px 0px; border-radius: 50%; background: #a4a4a4;'></div> " . GetMessage("import_unloaded_offers_stat_unactive");
		}

		$row = &$lAdmin->AddRow($f_ID, $arRes);
		$row->AddViewField("NAME", '<a href="acrit.import_edit.php?ID=' . $f_ID . "&amp;lang=" . LANG . '" title="' . GetMessage("import_act_edit") . '">' . $f_NAME . "</a>");
		$row->AddInputField("NAME", ["size" => 20]);

		$arActions = [];
		if ($POST_RIGHT == "W") {
			$arActions[] = [
				"ICON" => "edit",
				"DEFAULT" => true,
				"TEXT" => GetMessage("import_act_edit"),
				"ACTION" => $lAdmin->ActionRedirect("acrit.import_edit.php?ID=" . $f_ID)
			];
		}

		if ($POST_RIGHT == "W") {
			$arActions[] = [
				"ICON" => "delete",
				"TEXT" => GetMessage("import_act_del"),
				"ACTION" => "if(confirm('" . GetMessage("import_act_del_conf") . "')) " . $lAdmin->ActionDoGroup($f_ID, "delete")
			];
		}
		$arActions[] = ["SEPARATOR" => true];
		if (is_set($arActions[count($arActions) - 1], "SEPARATOR")) {
			unset($arActions[count($arActions) - 1]);
		}

		$row->AddActions($arActions);
	}

	$lAdmin->AddFooter(
		[
			[
				"title" => GetMessage("MAIN_ADMIN_LIST_SELECTED"),
				"value" => $rsData->SelectedRowsCount()
			],
			[
				"counter" => true,
				"title" => GetMessage("MAIN_ADMIN_LIST_CHECKED"),
				"value" => "0"
			],
		]
	);

	$lAdmin->AddGroupActionTable(
		[
			"delete" => GetMessage("MAIN_ADMIN_LIST_DELETE"),
			"activate" => GetMessage("MAIN_ADMIN_LIST_ACTIVATE"),
			"deactivate" => GetMessage("MAIN_ADMIN_LIST_DEACTIVATE"),
		]
	);

	$aContext = [
		[
			"TEXT" => GetMessage("PROFILE_ADD_TITLE"),
			"LINK" => "acrit.import_edit.php?lang=" . LANG,
			"TITLE" => GetMessage("PROFILE_ADD_TITLE"),
			"ICON" => "btn_new",
		],
	];

	$lAdmin->AddAdminContextMenu($aContext);
	$lAdmin->CheckListMode();


	$APPLICATION->SetTitle(GetMessage("post_title"));

	require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");

	// Send message and show progress
	if (isset($_REQUEST["import_end"]) && $_REQUEST["import_end"] == 1 && isset($_REQUEST["import_id"]) && $_REQUEST["import_id"] > 0) {
		if (isset($_GET["SUCCESS"][0])) {
			foreach ($_GET["SUCCESS"] as $success) {
				CAdminMessage::ShowMessage(
					[
						"MESSAGE" => $success,
						"TYPE" => "OK"
					]
				);
			}
		}

		if (isset($_GET["ERROR"][0])) {
			foreach ($_GET["ERROR"] as $error) {
				CAdminMessage::ShowMessage($error);
			}
		}
	}

	//AcritLicence::Show();

	// Notify if has updates
	include 'include/update_notifier.php';

	echo BeginNote();
	echo GetMessage("ACRIT_TIME_ZONES_DIFF_DATE");
	echo EndNote();

	$lAdmin->DisplayList();
} ?>

<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php"); ?>