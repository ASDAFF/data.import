<?
$strLang = 'ACRIT_IMPORT_';

$MESS["acrit.import_MODULE_NAME"] = "Универсальный импорт (acrit.import)";
$MESS["acrit.import_MODULE_DESC"] = "Универсальный импорт";
$MESS["acrit.import_PARTNER_NAME"] = "Веб-Студия АКРИТ";
$MESS["acrit.import_PARTNER_URI"] = "https://www.acrit-studio.ru";
#
$MESS[$strLang.'NO_CORE'] = '<span style="color:red; font-weight:bold;">Модуль служебных инструментов АКРИТ (acrit.core) не установлен.</span><br/>
	В связи с последними изменениями в модуле, для работы необходим <a href="/bitrix/admin/update_system_partner.php?addmodule=acrit.core&lang='.LANGUAGE_ID.'" target="_blank">модуль служебных инструментов</a>.<br/>
	Без него работа модуля невозможна.';
?>