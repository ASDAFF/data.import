<?
namespace Webprostor\Core\Property;

use Bitrix\Main\GroupTable,
	Bitrix\Main\Localization\Loc;

class UserGroup
{
	public static function GetUserTypeDescription()
	{
		return [
			'PROPERTY_TYPE' => 'S',
			'USER_TYPE' => "WEBPROSTOR_CORE_PROPERTY_USER_GROUP",
			'DESCRIPTION' => Loc::getMessage('WEBPROSTOR_CORE_PROPERTY_USER_GROUP_DESCRIPTION'),
			
			'GetPropertyFieldHtml' => [__CLASS__, 'GetPropertyFieldHtml'],
			'GetPropertyFieldHtmlMulty' => [__CLASS__, 'GetPropertyFieldHtmlMulty'],
			'GetSettingsHTML' => [__CLASS__, 'GetSettingsHTML'],
			'GetAdminListViewHTML' => [__CLASS__, 'GetAdminListViewHTML'],
			'GetPublicViewHTML' => [__CLASS__, 'GetPublicViewHTML'],
			'GetUIFilterProperty' => [__CLASS__, 'GetUIFilterProperty'],
		];
	}

	protected static function __getUsersGroups()
	{
		$result = [];

		$groups = GroupTable::getList([
			'order' => ['C_SORT' => 'ASC'],
			'filter'  => ['ACTIVE' => 'Y'],
			'select'  => ['ID', 'NAME'],
		])->fetchAll();
		
		foreach($groups as $group)
		{
			$result[$group['ID']] = htmlspecialcharsbx($group['NAME']).' ['.$group['ID'].']';
		}

		return $result;
	}

	protected static function __getUsersGroupHTML($id, $adminSection = true)
	{
		$html = $id;

		$group = GroupTable::getById($id)->fetch();
		
		if($group)
		{
			if($adminSection)
				$html = '<a href="group_edit.php?lang='.LANGUAGE_ID.'&ID='.$group['ID'].'">'.$group['NAME'].' ['.$group['ID'].']</a>';
			else
				$html = $group['NAME'];
		}

		return $html;
	}

	public static function GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName)
	{
		$bEditProperty = $strHTMLControlName['MODE'] === 'EDIT_FORM';
		$propertyRequired = $arProperty['IS_REQUIRED'] === 'Y';
		
		$groups = self::__getUsersGroups();
		$val = ($value['VALUE'] ? $value['VALUE'] : $arProperty['DEFAULT_VALUE']);

		ob_start();
		?>
		<select name="<?=$strHTMLControlName['VALUE']?>">
			<?if($bEditProperty || !$propertyRequired):?>
			<option value="">-</option>
			<?endif;?>
			<?foreach($groups as $id => $name):?>
			<option value="<?=$id?>"<?=($val == $id ? ' selected' : '')?>><?=$name?></option>
			<?endforeach;?>
		</select>
		<?
		return ob_get_clean();
	}

	static function GetPropertyFieldHtmlMulty($arProperty, $value, $strHTMLControlName){
		$bEditProperty = $strHTMLControlName['MODE'] === 'EDIT_FORM';

		$groups = self::__getUsersGroups();
		$arValues = ($value && is_array($value) ? array_column($value, 'VALUE') : array($arProperty['DEFAULT_VALUE']));

		ob_start();
		?>
		<select name="<?=$strHTMLControlName['VALUE']?>[]" multiple size="<?=$arProperty['MULTIPLE_CNT']?>">
			<?foreach($groups as $id => $name):?>
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
			return self::__getUsersGroupHTML($arValue["VALUE"], true);
		else
			return '&nbsp;';
	}

	public static function GetPublicViewHTML($arProperty, $arValue, $strHTMLControlName) {
		if(strlen($arValue["VALUE"])>0)
            return self::__getUsersGroupHTML($arValue["VALUE"], false);
        else
            return '';
	}

	public static function GetUIFilterProperty($arProperty, $strHTMLControlName, &$fields)
	{
		$fields['type'] = 'list';
		$fields['items'] = self::__getUsersGroups();
		if($arProperty['MULTIPLE'])
			$fields['params'] = ['multiple' => 'Y'];
	}
}