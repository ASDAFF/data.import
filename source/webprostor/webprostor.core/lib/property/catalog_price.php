<?
namespace Webprostor\Core\Property;

use Bitrix\Main\Loader,
	Bitrix\Main\Localization\Loc;

class CatalogPrice
{
	public static function GetUserTypeDescription()
	{
		return [
			'PROPERTY_TYPE' => 'S',
			'USER_TYPE' => "WEBPROSTOR_CORE_PROPERTY_CATALOG_PRICE",
			'DESCRIPTION' => Loc::getMessage('WEBPROSTOR_CORE_PROPERTY_CATALOG_PRICE_DESCRIPTION'),
			
			'GetPropertyFieldHtml' => [__CLASS__, 'GetPropertyFieldHtml'],
			'GetPropertyFieldHtmlMulty' => [__CLASS__, 'GetPropertyFieldHtmlMulty'],
			'GetSettingsHTML' => [__CLASS__, 'GetSettingsHTML'],
			'GetAdminListViewHTML' => [__CLASS__, 'GetAdminListViewHTML'],
			'GetPublicViewHTML' => [__CLASS__, 'GetPublicViewHTML'],
			'GetUIFilterProperty' => [__CLASS__, 'GetUIFilterProperty'],
		];
	}

	protected static function __getCatalogPrices()
	{
		$result = [];
		
		if(Loader::includeModule('catalog'))
		{
			$prices = \Bitrix\Catalog\GroupLangTable::getList([
				'order'  => ['CATALOG_GROUP_ID' => 'ASC'],
				'filter'  => ['LANG' => LANGUAGE_ID],
				'select'  => ['ID', 'NAME', 'CATALOG_GROUP_ID'],
			])->fetchAll();
			
			foreach($prices as $price)
			{
				$result[$price['CATALOG_GROUP_ID']] = htmlspecialcharsbx($price['NAME']).' ['.$price['CATALOG_GROUP_ID'].']';
			}
		}

		return $result;
	}

	protected static function __getCatalogPriceHTML($id, $adminSection = true)
	{
		$html = $id;

		$price = \Bitrix\Catalog\GroupLangTable::getList([
			'filter'  => ['LANG' => LANGUAGE_ID, 'CATALOG_GROUP_ID' => $id],
			'select'  => ['ID', 'NAME', 'CATALOG_GROUP_ID'],
		])->fetch();
		
		if($price)
		{
			if($adminSection)
				$html = '<a href="cat_group_edit.php?lang='.LANGUAGE_ID.'&ID='.$price['CATALOG_GROUP_ID'].'">'.$price['NAME'].' ['.$price['CATALOG_GROUP_ID'].']</a>';
			else
				$html = $price['NAME'];
		}

		return $html;
	}

	public static function GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName)
	{
		$bEditProperty = $strHTMLControlName['MODE'] === 'EDIT_FORM';
		$propertyRequired = $arProperty['IS_REQUIRED'] === 'Y';
		
		$prices = self::__getCatalogPrices();
		$val = ($value['VALUE'] ? $value['VALUE'] : $arProperty['DEFAULT_VALUE']);

		ob_start();
		?>
		<select name="<?=$strHTMLControlName['VALUE']?>">
			<?if($bEditProperty || !$propertyRequired):?>
			<option value="">-</option>
			<?endif;?>
			<?foreach($prices as $id => $name):?>
			<option value="<?=$id?>"<?=($val == $id ? ' selected' : '')?>><?=$name?></option>
			<?endforeach;?>
		</select>
		<?
		return ob_get_clean();
	}

	static function GetPropertyFieldHtmlMulty($arProperty, $value, $strHTMLControlName){
		$bEditProperty = $strHTMLControlName['MODE'] === 'EDIT_FORM';

		$prices = self::__getCatalogPrices();
		$arValues = ($value && is_array($value) ? array_column($value, 'VALUE') : array($arProperty['DEFAULT_VALUE']));

		ob_start();
		?>
		<select name="<?=$strHTMLControlName['VALUE']?>[]" multiple size="<?=$arProperty['MULTIPLE_CNT']?>">
			<?foreach($prices as $id => $name):?>
			<option value="<?=$id?>"<?=(in_array($id, $arValues) ? ' selected' : '')?>><?=$name?></option>
			<?endforeach;?>
		</select>
		<?
		return ob_get_clean();
	}

	public static function GetSettingsHTML($arProperty, $strHTMLControlName, &$arPropertyFields)
	{
		$arPropertyFields = array(
            'HIDE' => array(
            	'SMART_FILTER',
            	'SEARCHABLE',
            	'COL_COUNT',
            	'ROW_COUNT',
            	'FILTER_HINT',
            	'WITH_DESCRIPTION'
            ),
            'SET' => array(
            	'SMART_FILTER' => 'N',
            	'SEARCHABLE' => 'N',
            	'ROW_COUNT' => '10',
            	'WITH_DESCRIPTION' => 'N',
            ),
        );
		
		return $result;
	}

	public static function GetAdminListViewHTML($arProperty, $arValue, $strHTMLControlName)
	{
		if(strlen($arValue["VALUE"])>0)
			return self::__getCatalogPriceHTML($arValue["VALUE"], true);
		else
			return '&nbsp;';
	}

	public static function GetPublicViewHTML($arProperty, $arValue, $strHTMLControlName) {
		if(strlen($arValue["VALUE"])>0)
            return self::__getCatalogPriceHTML($arValue["VALUE"], false);
        else
            return '';
	}

	public static function GetUIFilterProperty($arProperty, $strHTMLControlName, &$fields)
	{
		$fields['type'] = 'list';
		$fields['items'] = self::__getCatalogPrices();
		if($arProperty['MULTIPLE'])
			$fields['params'] = ['multiple' => 'Y'];
	}
}