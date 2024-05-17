<?
define("ADMIN_MODULE_NAME", "webprostor.core");

CJSCore::Init(array("jquery3"));

$GLOBALS['APPLICATION']->AddHeadScript('/bitrix/panel/webprostor.core/select2/js/select2.min.js');
$GLOBALS['APPLICATION']->AddHeadScript('/bitrix/panel/webprostor.core/select2/js/ru.js');
$GLOBALS['APPLICATION']->AddHeadScript('/bitrix/panel/webprostor.core/select2/js/main.js');

$GLOBALS['APPLICATION']->AddHeadScript('/bitrix/panel/webprostor.core/main.js');

$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/panel/webprostor.core/select2/css/select2.min.css');
$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/panel/webprostor.core/select2/css/style.css');

\Bitrix\Main\UI\Extension::load("ui.buttons");
\Bitrix\Main\UI\Extension::load("ui.buttons.icons");
\Bitrix\Main\UI\Extension::load("ui.alerts");
\Bitrix\Main\UI\Extension::load("ui.icons");
\Bitrix\Main\UI\Extension::load("ui.forms"); 
\Bitrix\Main\UI\Extension::load("ui.hint");
?>