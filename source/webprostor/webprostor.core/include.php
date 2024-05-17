<?
CModule::AddAutoloadClasses(
	"webprostor.core",
	array(
		"CWebprostorCoreFilter" => "classes/general/filter.php",
		"CWebprostorCoreFunctions" => "classes/general/functions.php",
		"CWebprostorCoreIblock" => "classes/general/iblock.php",
		"CWebprostorCoreOptions" => "classes/general/options.php",
		"\Webprostor\Core\Condition" => "lib/condition.php",
		"\Webprostor\Core\Main" => "lib/main.php",
		"\Webprostor\Core\Iblock" => "lib/iblock.php",
		"\Webprostor\Core\Property\UserGroup" => "lib/property/user_group.php",
		"\Webprostor\Core\Property\CatalogPrice" => "lib/property/catalog_price.php",
	)
);
?>