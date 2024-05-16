<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
IncludeModuleLangFile(__FILE__);
/**
 * @global $APPLICATION \CMain
 * @global $USER \CUser
 * @global $DB \CDatabase
 * @global $USER_FIELD_MANAGER \CUserTypeManager
 * @global $BX_MENU_CUSTOM \CMenuCustom
 * @global $stackCacheManager \CStackCacheManager
 */
?>
<tr class="heading" align="center">
    <td colspan="2"><b><?=GetMessage("ACRIT_IMPORT_STEP5_SUBTIT1"); ?></b></td>
</tr>
<?/*
<tr>
    <td><?=GetMessage("ACRIT_IMPORT_DLITELQNOSTQ_ODNOY_I")?></td>
    <td width="50%">
        <input type="text" name="PROFILE[SCHEDULE_DURATION]" value="<?=$arProfile['SCHEDULE_DURATION']?$arProfile['SCHEDULE_DURATION']:'0';?>" size="10" />
    </td>
</tr>
*/?>
<tr>
    <td colspan="2">
        <table class="adm-list-table" id="acrit_imp_agents_list">
            <thead>
                <tr class="adm-list-table-header">
                    <td class="adm-list-table-cell"><div class="adm-list-table-cell-inner"><?=GetMessage('ACRIT_IMPORT_AGENTS_TITLES_ID');?></div></td>
                    <td class="adm-list-table-cell"><div class="adm-list-table-cell-inner"><?=GetMessage('ACRIT_IMPORT_AGENTS_TITLES_INTERVAL');?></div></td>
                    <td class="adm-list-table-cell"><div class="adm-list-table-cell-inner"><?=GetMessage('ACRIT_IMPORT_AGENTS_TITLES_LAST_START');?></div></td>
                    <td class="adm-list-table-cell"><div class="adm-list-table-cell-inner"><?=GetMessage('ACRIT_IMPORT_AGENTS_TITLES_NEXT_START');?></div></td>
                    <td class="adm-list-table-cell"></td>
                    <td class="adm-list-table-cell"></td>
                </tr>
            </thead>
            <tbody>
                <tr class="adm-list-table-row action-add-row" id="acrit_imp_agents_add">
                    <td class="adm-list-table-cell"></td>
                    <td class="adm-list-table-cell"><input type="text" name="interval_min" value="1440" size="10" placeholder="1440" /><?=GetMessage('ACRIT_IMPORT_AGENTS_INTERVAL_MIN');?></td>
                    <td class="adm-list-table-cell"></td>
                    <td class="adm-list-table-cell">
                        <?$APPLICATION->IncludeComponent(
                            "bitrix:main.calendar",
                            "",
                            [
                                "SHOW_INPUT" => "Y",
                                "FORM_NAME" => "import_form",
                                "INPUT_NAME" => "start_time",
                                "INPUT_VALUE" => date('d.m.Y H:i:s', strtotime(date('d.m.Y H:00:00')) + 3600),
                                "SHOW_TIME" => "Y",
                                "HIDE_TIMEBAR" => "N"
                            ],
                            false
                        );?>
                    </td>
                    <td class="adm-list-table-cell"></td>
                    <td class="adm-list-table-cell"><a href="#" class="adm-btn adm-btn-save" id="acrit_imp_agent_add"><?=GetMessage('ACRIT_IMPORT_AGENTS_ADD');?></a></td>
                </tr>
            </tbody>
        </table>
        <div class="acrit-imp-agents-servtime">
            <?=GetMessage('ACRIT_IMPORT_AGENTS_SERVER_TIME');?><?=ConvertTimeStamp(time(), 'FULL');?>
        </div>
    </td>
</tr>

<tr class="heading" align="center">
    <td colspan="2"><b><?=GetMessage("ACRIT_IMPORT_STEP5_SUBTIT_CRON"); ?></b></td>
</tr>
<tr>
    <td colspan="2">
        <?=GetMessage("ACRIT_IMPORT_CRON_DESCR");?>
	    <p><strong><code><?=CAcritImport::getPhpPath()?> -f <?=$_SERVER['DOCUMENT_ROOT'];?>/bitrix/modules/acrit.import/scripts/cron.php <?=$ID;?></code></strong></p>
        <div class="adm-info-message-wrap">
            <div class="adm-info-message">
	            <?=GetMessage("ACRIT_IMPORT_CRON_NOTE", ['#LANG#' => LANGUAGE_ID]);?>
            </div>
        </div>
    </td>
</tr>

<tr class="heading" align="center">
    <td colspan="2"><b><?=GetMessage("ACRIT_IMPORT_STEP5_SUBTIT2"); ?></b></td>
</tr>
<style>
    .run-disabled {
        pointer-events: none;
        cursor: default;
        color: #888;
    }
</style>
<tr>
    <td></td>
    <td width="50%">
        <a href="#" class="adm-btn adm-btn-save" id="start_import" style="margin-bottom: 4px;"><?=GetMessage("ACRIT_IMPORT_RUNNOW_START")?></a>
        <a href="#" class="adm-btn adm-btn-disabled" id="stop_import" style="margin-bottom: 4px;"><?=GetMessage("ACRIT_IMPORT_RUNNOW_STOP")?></a>
        <div id="start_import_progress"></div>
        <div class="start-import-result" id="start_import_result" style="display:none;">
            <div class="start-import-result-all"><?=GetMessage("ACRIT_IMPORT_VSEGO_OBRABOTANO")?><span>0</span></div>
            <div class="start-import-result-good"><?=GetMessage("ACRIT_IMPORT_USPESNO_IMPORTIROVAN")?><span>0</span></div>
            <div class="start-import-result-skip"><?=GetMessage("ACRIT_IMPORT_PROPUSENO")?><span>0</span></div>
            <div class="start-import-result-bad"><?=GetMessage("ACRIT_IMPORT_S_OSIBKAMI")?><span>0</span></div>
        </div>
    </td>
</tr>
<tr>
    <td><?=GetMessage("ACRIT_IMPORT_RUN_ERRORS")?>:</td>
    <td>
        <textarea class="start-import-errors" id="start_import_errors"></textarea>
    </td>
</tr>
