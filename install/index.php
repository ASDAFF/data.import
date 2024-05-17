<?php
IncludeModuleLangFile(__FILE__);

class data_import extends CModule
{
    const MODULE_ID = 'data.import';
    var $MODULE_ID = 'data.import';
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $MODULE_CSS;
    var $_413966939 = '';
    var $MODULE_GROUP_RIGHTS = "Y";

    function __construct()
    {
        $arModuleVersion = array();
        include(dirname(__FILE__) . '/version.php');
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = GetMessage('DATA_IMPORT_MODULE_NAME');
        $this->MODULE_DESCRIPTION = GetMessage('DATA_IMPORT_MODULE_DESC');
        $this->PARTNER_NAME = GetMessage('DATA_IMPORT_PARTNER_NAME');
        $this->PARTNER_URI = GetMessage('DATA_IMPORT_PARTNER_URI');
    }

    function InstallDB($_35409257 = array())
    {
        global $DB, $APPLICATION;
        $this->_634482454 = false;
        $this->_634482454 = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . self::MODULE_ID . '/install/db/' . strtolower($DB->type) . '/install.sql');
        if ($this->_634482454 !== false) {
            $APPLICATION->ThrowException(implode('<br>', $this->_634482454));
            return false;
        }
        return true;
    }

    function UnInstallDB($_35409257 = array())
    {
        global $DB, $APPLICATION;
        $this->_634482454 = false;
        if (!array_key_exists('SAVE_TABLES', $_35409257) || ($_35409257['SAVE_TABLES'] != 'Y')) {
            $this->_634482454 = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . self::MODULE_ID . '/install/db/' . strtolower($DB->type) . '/uninstall.sql');
        }
        if ($this->_634482454 !== false) {
            $APPLICATION->ThrowException(implode('<br>', $this->_634482454));
            return false;
        }
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

    function InstallFiles($_35409257 = array())
    {
        if (is_dir($_1001494807 = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . self::MODULE_ID . '/admin')) {
            if ($_849255489 = opendir($_1001494807)) {
                while (false !== $_1261683911 = readdir($_849255489)) {
                    if ($_1261683911 == '..' || $_1261683911 == '.' || $_1261683911 == 'menu.php') continue;
                    file_put_contents($_1130991497 = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin/' . self::MODULE_ID . '_' . $_1261683911, '<' . '? require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/' . self::MODULE_ID . '/admin/' . $_1261683911 . '");?' . '>');
                }
                closedir($_849255489);
            }
        }
        CheckDirPath($_SERVER['DOCUMENT_ROOT'] . '/bitrix/tools/' . self::MODULE_ID . '/');
        if (is_dir($_1001494807 = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . self::MODULE_ID . '/tools')) {
            if ($_849255489 = opendir($_1001494807)) {
                while (false !== $_1261683911 = readdir($_849255489)) {
                    if ($_1261683911 == '..' || $_1261683911 == '.') continue;
                    file_put_contents($_1130991497 = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/tools/' . self::MODULE_ID . '/' . $_1261683911, '<' . '? require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/' . self::MODULE_ID . '/tools/' . $_1261683911 . '");?' . '>');
                }
                closedir($_849255489);
            }
        }
        CopyDirFiles($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . self::MODULE_ID . '/install/bitrix/', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/', true, true);
        $_1577163763 = COption::GetOptionString('main', 'upload_dir', 'upload');
        if (!is_dir($_SERVER['DOCUMENT_ROOT'] . "/{$_1577163763}/" . self::MODULE_ID)) mkdir($_SERVER['DOCUMENT_ROOT'] . "/{$_1577163763}/" . self::MODULE_ID, BX_DIR_PERMISSIONS);
        if (!is_dir($_SERVER['DOCUMENT_ROOT'] . "/{$_1577163763}/" . self::MODULE_ID . '/csv')) mkdir($_SERVER['DOCUMENT_ROOT'] . "/{$_1577163763}/" . self::MODULE_ID . '/csv', BX_DIR_PERMISSIONS);
        if (!is_dir($_SERVER['DOCUMENT_ROOT'] . "/{$_1577163763}/" . self::MODULE_ID . '/xls')) mkdir($_SERVER['DOCUMENT_ROOT'] . "/{$_1577163763}/" . self::MODULE_ID . '/xls', BX_DIR_PERMISSIONS);
        if (!is_dir($_SERVER['DOCUMENT_ROOT'] . "/{$_1577163763}/" . self::MODULE_ID . '/xml')) mkdir($_SERVER['DOCUMENT_ROOT'] . "/{$_1577163763}/" . self::MODULE_ID . '/xml', BX_DIR_PERMISSIONS);
        if (!is_dir($_SERVER['DOCUMENT_ROOT'] . "/{$_1577163763}/" . self::MODULE_ID . '/ods')) mkdir($_SERVER['DOCUMENT_ROOT'] . "/{$_1577163763}/" . self::MODULE_ID . '/ods', BX_DIR_PERMISSIONS);
        if (!is_dir($_SERVER['DOCUMENT_ROOT'] . "/{$_1577163763}/" . self::MODULE_ID . '/data')) mkdir($_SERVER['DOCUMENT_ROOT'] . "/{$_1577163763}/" . self::MODULE_ID . '/data', BX_DIR_PERMISSIONS);
        if (!is_dir($_SERVER['DOCUMENT_ROOT'] . "/{$_1577163763}/" . self::MODULE_ID . '/data/images')) mkdir($_SERVER['DOCUMENT_ROOT'] . "/{$_1577163763}/" . self::MODULE_ID . '/data/images', BX_DIR_PERMISSIONS);
        if (!is_dir($_SERVER['DOCUMENT_ROOT'] . "/{$_1577163763}/" . self::MODULE_ID . '/data/files')) mkdir($_SERVER['DOCUMENT_ROOT'] . "/{$_1577163763}/" . self::MODULE_ID . '/data/files', BX_DIR_PERMISSIONS);
        return true;
    }

    function UnInstallFiles($_35409257 = array())
    {
        if (is_dir($_1001494807 = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . self::MODULE_ID . '/admin')) {
            if ($_849255489 = opendir($_1001494807)) {
                while (false !== $_1261683911 = readdir($_849255489)) {
                    if ($_1261683911 == '..' || $_1261683911 == '.') continue;
                    unlink($_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin/' . self::MODULE_ID . '_' . $_1261683911);
                }
                closedir($_849255489);
            }
        }
        if (is_dir($_1001494807 = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . self::MODULE_ID . '/tools')) {
            if ($_849255489 = opendir($_1001494807)) {
                while (false !== $_1261683911 = readdir($_849255489)) {
                    if ($_1261683911 == '..' || $_1261683911 == '.') continue;
                    unlink($_SERVER['DOCUMENT_ROOT'] . '/bitrix/tools/' . self::MODULE_ID . '/' . $_1261683911);
                }
                closedir($_849255489);
            }
        }
        DeleteDirFilesEx('/bitrix/panel/' . self::MODULE_ID . '/');
        DeleteDirFilesEx('/bitrix/tools/' . self::MODULE_ID . '/');
        DeleteDirFilesEx('/bitrix/gadgets/data/import.agents/');
        rmdir($_SERVER['DOCUMENT_ROOT'] . '/bitrix/gadgets/data/');
        if (!array_key_exists('DELETE_IMPORT_DIR', $_35409257) || ($_35409257['DELETE_IMPORT_DIR'] != 'Y')) {
            $_1577163763 = COption::GetOptionString('main', 'upload_dir', 'upload');
            DeleteDirFilesEx("/{$_1577163763}/" . self::MODULE_ID);
        }
        return true;
    }

    function DoInstall()
    {
        global $APPLICATION, $step;
        $step = IntVal($step);
        if ($step < 2) {
            $GLOBALS['APPLICATION']->IncludeAdminFile(GetMessage('DATA_MODULE_INSTALL'), $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . self::MODULE_ID . '/install/install_1.php');
        } elseif ($step == 2) {
            if ($this->InstallDB() && $this->InstallEvents() && $this->InstallFiles()) {
                RegisterModule(self::MODULE_ID);
                return true;
            } else return false;
        }
    }

    function DoUninstall()
    {
        global $APPLICATION, $step;
        $step = IntVal($step);
        if ($step < 2) {
            $GLOBALS['APPLICATION']->IncludeAdminFile(GetMessage('DATA_MODULE_DELETE'), $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . self::MODULE_ID . '/install/uninstall_1.php');
        } elseif ($step == 2) {
            if ($this->UnInstallDB(array('SAVE_TABLES' => $_REQUEST['SAVE_TABLES'])) && $this->UnInstallEvents() && $this->UnInstallFiles(array('DELETE_IMPORT_DIR' => $_REQUEST['DELETE_IMPORT_DIR']))) {
                COption::RemoveOption(self::MODULE_ID);
                CAdminNotify::DeleteByModule(self::MODULE_ID);
                CAgent::RemoveModuleAgents(self::MODULE_ID);
                UnRegisterModule(self::MODULE_ID);
                return true;
            } else return false;
        }
    }

    function GetModuleRightList()
    {
        $_2033152511 = array('reference_id' => array('D', 'R', 'T', 'W'), 'reference' => array('[D] ' . GetMessage('DATA_IMPORT_RIGHT_DENIED'), '[R] ' . GetMessage('DATA_IMPORT_RIGHT_READ'), '[T] ' . GetMessage('DATA_IMPORT_RIGHT_MANUALLY'), '[W] ' . GetMessage('DATA_IMPORT_RIGHT_ADMIN')));
        return $_2033152511;
    }
} ?>