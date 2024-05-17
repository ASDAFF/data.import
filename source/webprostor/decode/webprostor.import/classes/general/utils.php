<?
class CWebprostorImportUtils
{
	function __construct()
	{
		if(CModule::IncludeModule('iblock'))
			return true;
		else
			return false;
	}
	
	public static function TranslitValue(string $value = '', string $replace_space = '-', string $replace_other = '-', int $max_len = 100, string $change_case = "L") : string
	{
		$arTransParams = [
			"replace_space" => $replace_space,
			"replace_other" => $replace_other,
			
			"max_len" => $max_len,
			"change_case" => $change_case,
		];
		
		$result = Cutil::translit($value, LANGUAGE_ID, $arTransParams);
		
		return $result;
	}
	
	public static function ClearField($string)
	{
		$result = $string;
		
		if(is_string($result))
		{
			$result = trim($result, '""');
			$result = trim($result);
			$result = str_replace('""', '"', $result);
		}
		
		return $result;
	}
	
	public static function ClearPrice($string, $floatval = false)
	{
		$result = $string;
		
		$result = trim($result, '');
		
		$result = str_replace(' ', '', $result);
		$result = str_replace(chr(194).chr(160), '', $result);

		$result = str_replace(',', '.', $result);
		
		if($floatval)
			$result = floatval($result);
		
		return $result;
	}
	
	public static function CompareCodeSort($a, $b)
	{
		if ($a == $b) {
			return 0;
		}
		return ($a < $b) ? -1 : 1;
	}
	
	/*private static function CheckArray(&$rule, &$field)
	{		
		$result = true;
		
		if($rule["IS_ARRAY"] == "N" && is_array($field))
		{
			$result = false;
		}
		
		return $result;
	}*/
	
	public static function CheckRequired(&$rule, &$field)
	{
		$result = true;
		
		if($rule["IS_REQUIRED"] == "Y" && empty($field))
		{
			$result = false;
		}
		
		return $result;
	}
	
	public static function CheckArrayContinue(&$rule, &$field)
	{		
		$result = false;
		
		if($rule["IS_ARRAY"] == "N" && is_array($field))
		{
			$result = true;
		}
		
		return $result;
	}
	
	public static function CheckAttribute(&$rule, &$temp_field, &$field)
	{
		if(isset($rule["ENTITY_ATTRIBUTE"]) && $rule["ENTITY_ATTRIBUTE"] != '')
		{
			if(is_array($temp_field) && isset($temp_field[$rule["ENTITY_ATTRIBUTE"]]))
			{
				$field = $temp_field[$rule["ENTITY_ATTRIBUTE"]];
			} 
			elseif(!is_array($temp_field))
			{
				$field = $temp_field;
			}
			else
			{
				$field = null;
			}
		}
	}
	
	public static function mb_ucfirst($str)
	{
		$fc = mb_strtoupper(mb_substr($str, 0, 1));
		return $fc . mb_substr($str, 1);
	}
	
	public static function mb_lcfirst($str)
	{
		$fc = mb_strtolower(mb_substr($str, 0, 1));
		return $fc . mb_substr($str, 1);
	}
	
	public static function replaceMacroses(&$str, &$planParams)
	{
		$str = str_replace('#ITEMS_PER_ROUND#', $planParams['ITEMS_PER_ROUND'], $str);
		$str = str_replace('#DATE_1#', date('d.m.y'), $str);
		$str = str_replace('#DATE_2#', date('d.m.Y'), $str);
	}
}
?>