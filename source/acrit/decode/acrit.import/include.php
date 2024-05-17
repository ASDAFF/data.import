<?php
IncludeModuleLangFile(__FILE__);

use Bitrix\Iblock\PropertyIndex\Manager;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Web\HttpClient;

final class CAcritImport
{
    const MODULE_ID = 'acrit.import';
    const STEPS_LOG_FILENAME = '/upload/acrit_import_steps.txt';
    const DEBUG_LOG_FILENAME = '/upload/acrit_import_log.txt';
    private static $_2080335436;
    private static $_1320038369;

    public static function OnBuildGlobalMenu(&$_612763271, &$_1874313120)
    {
        global $USER, $APPLICATION, $adminMenu, $adminPage;
        if ($APPLICATION->GetGroupRight('main') < 'R') {
            return;
        }
        if (!is_object($adminMenu) || !is_subclass_of($adminMenu, CAdminMenu::class)) {
            return;
        }
        if (is_array($adminMenu->_612763271) && array_key_exists('global_menu_acrit', $adminMenu->_612763271)) {
            return;
        }
        $_505647080 = COption::GetOptionString(self::MODULE_ID, 'acritmenu_groupname');
        if (strlen(trim($_505647080)) <= 0) {
            $_505647080 = GetMessage('ACRITMENU_GROUPNAME_DEFAULT');
        }
        $_1037398832 = ['menu_id' => 'acrit', 'sort' => 150, 'text' => $_505647080, 'title' => GetMessage('ACRIT_MENU_TITLE'), 'icon' => 'clouds_menu_icon', 'page_icon' => 'clouds_page_icon', 'items_id' => 'global_menu_acrit', 'items' => []];
        $_612763271['global_menu_acrit'] = $_1037398832;
    }

    public static function runBgrRequest($_1977540610, $_2043960)
    {
        $_1864978365 = Option::get(self::MODULE_ID, "https") == 'Y';
        static $_671163703;
        if (!isset($_671163703)) {
            $_671163703 = SITE_SERVER_NAME;
            if (trim($_671163703) == '') {
                $_671163703 = COption::GetOptionString('main', 'server_name');
            }
        }
        $_90739360 = true;
        if (PHP_SAPI != 'cli') {
            $_90739360 = false;
        }
        if ($_90739360) {
            if (strpos($_1977540610, 'import_run_bgrnd') !== false) {
                $_1986256421 = true;
            } else {
                $_1986256421 = false;
            }
            if ($_1986256421) {
                $_651999475 = realpath(__DIR__ . '/../../' . self::MODULE_ID . '_run_bgrnd.php') . ' ' . (int)$_2043960['profile'] . ' ' . (int)$_2043960['count'] . ' ' . (int)$_2043960['next_item'];
            } else {
                $_651999475 = realpath(__DIR__ . '/../../' . self::MODULE_ID . '_run_index.php') . ' ' . (int)$_2043960['profile'] . ' ' . (int)$_2043960['count'] . ' ' . (int)$_2043960['step'] . ' ' . (int)$_2043960['last_index'];
            }
            self::runBgr($_651999475);
        } else {
            $_883566641 = ($_1864978365 ? 'https://' : 'http://') . $_671163703 . $_1977540610;
            $_2057651945 = http_build_query($_2043960);
            $_1273599686 = curl_init();
            curl_setopt_array($_1273599686, [CURLOPT_POST => 1, CURLOPT_HEADER => 0, CURLOPT_RETURNTRANSFER => 1, CURLOPT_SSL_VERIFYPEER => 1, CURLOPT_URL => $_883566641, CURLOPT_POSTFIELDS => $_2057651945, CURLOPT_FRESH_CONNECT => true, CURLOPT_TIMEOUT => 1,]);
            curl_exec($_1273599686);
            curl_close($_1273599686);
        }
    }

    public static function runBgr(string $_1421119932, ?string $_887057244 = null): void
    {
        if ($_887057244 === null) {
            $_887057244 = Loader::getDocumentRoot() . self::STEPS_LOG_FILENAME;
        }
        exec(self::getPhpPath() . ' -f ' . $_1421119932 . ' >> ' . $_887057244);
    }

    public static function getPhpPath(): string
    {
        $_31848946 = \Acrit\Core\Cli::getFullCommand(self::MODULE_ID, 'cron.php');
        $_1697868103 = $_31848946['PHP_PATH'] . ' ' . $_31848946['PHP_CONFIG'];
        if (empty($_1697868103)) {
            $_1697868103 = 'php';
        }
        return $_1697868103;
    }

    public static function checkExtensions(): array
    {
        $_275697120 = [];
        $_1534087448 = ['XMLReader', 'Zip'];
        foreach ($_1534087448 as $_796502831) {
            if (!extension_loaded($_796502831)) {
                $_275697120[] = $_796502831;
            }
        }
        return $_275697120;
    }

    public static function setLogLabel(): void
    {
        if (!self::$_2080335436) {
            self::$_2080335436 = str_replace('.', '', (string)microtime(true));
        }
    }

    public static function getLogMemory(): string
    {
        $_1395730604 = memory_get_usage();
        $_1930908653 = 0;
        while (floor($_1395730604 / 1024) > 0) {
            $_1930908653++;
            $_1395730604 /= 1024;
        }
        $_28084542 = ['b', 'Kb', 'Mb'];
        $_1068964291 = round($_1395730604, 2) . ' ' . $_28084542[$_1930908653];
        return $_1068964291;
    }

    public static function getLogMemoryDiff(): string
    {
        if (!self::$_1320038369) {
            self::$_1320038369 = 0;
        }
        $_1395730604 = memory_get_usage();
        $_612969258 = ($_1395730604 - self::$_1320038369);
        self::$_1320038369 = $_1395730604;
        $_1930908653 = 0;
        while (floor($_612969258 / 1024) > 0) {
            $_1930908653++;
            $_612969258 /= 1024;
        }
        $_28084542 = ['b', 'Kb', 'Mb'];
        $_2016038051 = round($_612969258, 2) . ' ' . $_28084542[$_1930908653];
        return $_2016038051;
    }

    public static function Log(string $_925366870): void
    {
        self::setLogLabel();
        $_887057244 = Option::get(self::MODULE_ID, 'filelog_debug');
        if ($_887057244 == 'Y') {
            $_1395730604 = self::getLogMemory();
            $_612969258 = self::getLogMemoryDiff();
            file_put_contents(Loader::getDocumentRoot() . self::DEBUG_LOG_FILENAME, $_925366870, FILE_APPEND);
            $_147491576 = date('d.m.Y H:i:s');
            $_147491576 .= ' label ' . self::$_2080335436;
            $_147491576 .= ' memory ' . $_1395730604;
            $_147491576 .= ' memory_diff ' . $_612969258;
            file_put_contents(Loader::getDocumentRoot() . self::DEBUG_LOG_FILENAME, '
---
' . $_147491576 . '

', FILE_APPEND);
        }
    }

    public static function startMultipleElemsUpdate(int $_85793413): void
    {
        Loader::includeModule('iblock');
        Manager::enableDeferredIndexing();
        if (Loader::includeModule('catalog')) {
            \Bitrix\Catalog\Product\Sku::enableDeferredCalculation();
        }
        \CAllIBlock::disableTagCache($_85793413);
    }

    public static function endMultipleElemsUpdate(int $_85793413): void
    {
        \CAllIBlock::enableTagCache($_85793413);
        \CAllIBlock::clearIblockTagCache($_85793413);
        if (Loader::includeModule('catalog')) {
            \Bitrix\Catalog\Product\Sku::disableDeferredCalculation();
            \Bitrix\Catalog\Product\Sku::calculate();
        }
        Manager::disableDeferredIndexing();
        Manager::runDeferredIndexing($_85793413);
        \CIBlockSection::ReSort($_85793413);
    }

    public static function isPropertyIndexEnabled(): bool
    {
        return Option::get(self::MODULE_ID, 'indexing') == 'Y';
    }

    public static function searchModuleIndexingEnabled(): bool
    {
        return Option::get(self::MODULE_ID, 'search_indexing') == 'Y';
    }

    public static function networkRequest(string $_1901466203, array &$_1944412921 = [])
    {
        $_1470653233 = ['disableSslVerification' => true];
        $_319664761 = new HttpClient($_1470653233);
        $_171359473 = $_319664761->get($_1901466203);
        $_1944412921 = $_319664761->getHeaders()->toArray();
        return $_171359473;
    }

    public static function getRemoteFileSize(string $_1901466203, ?string $_1103939437 = '', ?string $_1213144054 = ''): int
    {
        $_1470653233 = ['disableSslVerification' => true];
        if (!empty($_1103939437) && !empty($_1213144054)) {
            $_1470653233['headers'] = ['Authorization' => sprintf('Basic %s', base64_encode($_1103939437 . ':' . $_1213144054))];
        }
        $_319664761 = new HttpClient($_1470653233);
        $_1944412921 = $_319664761->head($_1901466203);
        if (is_object($_1944412921)) {
            $_1944412921 = $_1944412921->toArray();
        } else {
            $_1944412921 = [];
        }
        return (int)$_1944412921['content-length']['values'][0];
    }
}

function AcritImportGetImportTypes()
{
    $_538517437 = ['csv' => ['name' => 'CSV', 'file_ext' => 'csv', 'source_types' => ['file', 'url'], 'class' => 'ImportCsv',], 'xml' => ['name' => 'XML', 'file_ext' => 'xml', 'source_types' => ['file', 'url'], 'class' => 'ImportXml',], 'xlsx' => ['name' => 'XLSX', 'file_ext' => 'xlsx', 'source_types' => ['file', 'url'], 'class' => 'ImportXlsx',], 'iblock' => ['name' => GetMessage('ACRIT_IMPORT_TYPES_IBLOCK'), 'file_ext' => 'xml', 'source_types' => ['file', 'url'], 'class' => 'ImportIblock',], 'yml' => ['name' => 'YML', 'file_ext' => 'xml', 'source_types' => ['file', 'url'], 'class' => 'ImportYml',], 'ozon' => ['name' => 'API OZON', 'file_ext' => 'rest_api', 'source_types' => ['oauth'], 'class' => 'ImportOzon',], 'adsapi' => ['name' => 'API ads-api.ru', 'file_ext' => 'rest_api', 'source_types' => ['oauth'], 'class' => 'ImportAdsapi',],];
    foreach (GetModuleEvents(CAcritImport::MODULE_ID, 'OnGetImportTypes', true) as $_790886506) {
        $_538517437 = ExecuteModuleEventEx($_790886506, [$_538517437]);
    }
    return $_538517437;
}

function AcritImportGetSourceTypes()
{
    $_538517437 = ['file' => ['name' => GetMessage('ACRIT_IMPORT_SOURCE_TYPES_FILE'),], 'url' => ['name' => GetMessage('ACRIT_IMPORT_SOURCE_TYPES_SERVER'),], 'oauth' => ['name' => GetMessage('ACRIT_IMPORT_SOURCE_TYPES_OAUTH'),],];
    foreach (GetModuleEvents(CAcritImport::MODULE_ID, 'OnGetImportSTypes', true) as $_790886506) {
        $_538517437 = ExecuteModuleEventEx($_790886506, [$_538517437]);
    }
    return $_538517437;
}

function AcritImportGetImportObj($_1152524825)
{
    $_157107239 = false;
    try {
        \Bitrix\Main\Loader::includeModule('iblock');
        $_992201798 = \Acrit\Import\ProfileTable::getById($_1152524825)->fetch();
        $_538517437 = AcritImportGetImportTypes();
        if (isset($_538517437[$_992201798['TYPE']]['class'])) {
            $_1488664087 = '\Acrit\Import\\' . $_538517437[$_992201798['TYPE']]['class'];
            $_157107239 = new $_1488664087($_1152524825);
        }
    } catch (\Throwable $_345786114) {
        \CAcritImport::Log('Error in AcritImportGetImportObj ' . $_345786114->getMessage());
    }
    return $_157107239;
}

\Bitrix\Main\Loader::includeModule('acrit.core');?>