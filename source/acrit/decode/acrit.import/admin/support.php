<?php
$moduleId = "acrit.import";
require_once( $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php" );

LocalRedirect('/bitrix/admin/acrit_import_new_support.php?lang=' . LANGUAGE_ID, true, '301 Moved Permanently');