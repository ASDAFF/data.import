<?php
IncludeModuleLangFile(__FILE__);

class acrit_import extends CModule
{
    const MODULE_ID = 'acrit.import';
    var $MODULE_ID = 'acrit.import';
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $MODULE_CSS;
    var $_1297345488 = '';
    private $_743548614;
    private $_705703000;
    private $_558281114;

    function __construct()
    {
        $this->_743548614 = ToLower(preg_replace('#^acrit\.(.*?)$#i', '$1', $this->MODULE_ID));
        $this->_705703000 = ToUpper(substr($this->_743548614, 0, 1)) . substr($this->_743548614, 1);
        $this->_558281114 = ToUpper($this->_743548614);
        $arModuleVersion = [];
        include(__DIR__ . '/version.php');
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = GetMessage('acrit.import_MODULE_NAME');
        $this->MODULE_DESCRIPTION = GetMessage('acrit.import_MODULE_DESC');
        $this->PARTNER_NAME = GetMessage('acrit.import_PARTNER_NAME');
        $this->PARTNER_URI = GetMessage('acrit.import_PARTNER_URI');
    }

    function InstallDB($_1455945158 = [])
    {
        global $DB, $DBType, $APPLICATION;
        $this->_813524427 = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/db/' . $DBType . '/install.sql');
        if ($this->_813524427 !== false) {
            $APPLICATION->ThrowException(implode('', $this->_813524427));
            return false;
        }
        return true;
    }

    function UnInstallDB($_1455945158 = [])
    {
        global $DB, $DBType, $APPLICATION;
        $this->_813524427 = false;
        if (!array_key_exists('savedata', $_1455945158) || $_1455945158['savedata'] != 'Y') {
            $this->_813524427 = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/db/' . $DBType . '/uninstall.sql');
            if ($this->_813524427 !== false) {
                $APPLICATION->ThrowException(implode('', $this->_813524427));
                return false;
            }
        }
        UnRegisterModuleDependences('main', 'OnBuildGlobalMenu', self::MODULE_ID, 'CAcritImport', 'OnBuildGlobalMenu');
        return true;
    }

    function InstallEvents()
    {
        return true;
    }

    function UnInstallEvents()
    {
        return true;
    }

    function InstallFiles($_1455945158 = [])
    {
        if (is_dir($_179413161 = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . self::MODULE_ID . '/admin')) {
            if ($_1153808043 = opendir($_179413161)) {
                while (false !== $_372278251 = readdir($_1153808043)) {
                    if ($_372278251 == '..' || $_372278251 == '.' || $_372278251 == 'menu.php') {
                        continue;
                    }
                    readdir($_1158703453 = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin/' . self::MODULE_ID . '_' . $_372278251, '<' . '? require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/' . self::MODULE_ID . '/admin/' . $_372278251 . '");?' . '>');
                }
                closedir($_1153808043);
            }
        }
        if ($_ENV['COMPUTERNAME'] != 'BX') {
            CopyDirFiles($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/components', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/components/', true, true);
            CopyDirFiles($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/js', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/js', true, true);
            CopyDirFiles($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/themes', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/themes', true, true);
            CopyDirFiles($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/gadgets', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/gadgets', true, true);
            CopyDirFiles($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/tools', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/tools', true, true);
            CopyDirFiles($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/files', $_SERVER['DOCUMENT_ROOT'] . '/bitrix', true);
            foreach ($this->_748413946 as $_704613911) {
                CopyDirFiles($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/public', $_704613911, true, true);
            }
        }
        return true;
    }

    function UnInstallFiles()
    {
        if (is_dir($_179413161 = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . self::MODULE_ID . '/admin')) {
            if ($_1153808043 = opendir($_179413161)) {
                while (false !== $_372278251 = readdir($_1153808043)) {
                    if ($_372278251 == '..' || $_372278251 == '.') {
                        continue;
                    }
                    unlink($_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin/' . self::MODULE_ID . '_' . $_372278251);
                }
                closedir($_1153808043);
            }
        }
        if ($_ENV['COMPUTERNAME'] != 'BX') {
            DeleteDirFilesEx('/bitrix/acrit.import_run_bgrnd.php');
            DeleteDirFilesEx('/bitrix/acrit.import_run_index.php');
            DeleteDirFilesEx('/bitrix/js/acrit.import/');
            DeleteDirFilesEx('/bitrix/gadgets/acrit/import/');
            DeleteDirFilesEx('/bitrix/tools/acrit.import/');
            DeleteDirFilesEx('/bitrix/themes/.default/acrit/import/');
            DeleteDirFilesEx('/bitrix/themes/.default/acrit.import.css');
            DeleteDirFilesEx('/upload/acrit_import/');
            DeleteDirFiles($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/components', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/components/');
            foreach ($this->_748413946 as $_704613911) {
                DeleteDirFiles($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/public', $_704613911);
            }
        }
        return true;
    }

    function DoInstall()
    {
        global $APPLICATION, $DB;
        $GLOBALS['ACRIT_MODULE_ID'] = $this->MODULE_ID;
        $GLOBALS['ACRIT_MODULE_NAME'] = $this->MODULE_NAME;
        if ($APPLICATION->GetGroupRight('main') < 'W') {
            return;
        }
        if (!\Bitrix\Main\Loader::includeModule('acrit.core')) {
            $APPLICATION->ThrowException(getMessage('ACRIT_' . $this->_558281114 . '_NO_CORE'));
            return false;
        }
        $_1449388756 = $DB->Query("SELECT * FROM b_option WHERE `MODULE_ID`='{$this->MODULE_ID}' AND `NAME`='~bsm_stop_date'");
        if ($_1449388756->Fetch()) {
            $DB->Query("DELETE FROM b_option WHERE `MODULE_ID`='{$this->MODULE_ID}' AND `NAME`='~bsm_stop_date'");
        }
        $_76374494 = Bitrix\Main\Application::getInstance()->getManagedCache();
        $_76374494->clean('b_option:' . self::MODULE_ID, 'b_option');
        $this->InstallFiles();
        $this->InstallDB();
        RegisterModule(self::MODULE_ID);
        $APPLICATION->IncludeAdminFile(GetMessage('MOD_INST_OK'), __DIR__ . '/step3.php');
    }

    function DoUninstall()
    {
        global $APPLICATION, $DB, $step;
        $step = (int)$step;
        if ($step < 1) {
            $APPLICATION->IncludeAdminFile(GetMessage('acrit.import_UNINSTALL_TITLE'), $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/uninst_form.php');
        } else {
            CAgent::RemoveModuleAgents(self::MODULE_ID);
            UnRegisterModule(self::MODULE_ID);
            $this->UnInstallDB();
            $this->UnInstallFiles();
            $_1449388756 = $DB->Query("SELECT * FROM b_option WHERE `MODULE_ID`='{$this->MODULE_ID}' AND `NAME`='~bsm_stop_date'");
            if ($_1449388756->Fetch()) {
                $DB->Query("DELETE FROM b_option WHERE `MODULE_ID`='{$this->MODULE_ID}' AND `NAME`='~bsm_stop_date'");
            }
            $_76374494 = Bitrix\Main\Application::getInstance()->getManagedCache();
            $_76374494->clean('b_option:' . self::MODULE_ID, 'b_option');
            $APPLICATION->IncludeAdminFile(GetMessage('acrit.import_UNINSTALL_TITLE'), $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/uninst_mail.php');
        }
    }
}