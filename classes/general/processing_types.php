<?
IncludeModuleLangFile(__FILE__);

class CDataImportProcessingSettingsTypes
{
	
	public static function GetParams() : array
	{
		$params = Array();
		
		$params["ENCODING"] = Array(
			"" => GetMessage("PARAM_NOT_SET"),
			"UTF-8" => "UTF-8",
			"windows-1251" => "windows-1251",
		);
		
		$params["MODE"] = Array(
			"MB_CASE_UPPER",
			"MB_CASE_LOWER",
			"MB_CASE_TITLE",
			"MB_CASE_FOLD",
			"MB_CASE_LOWER_SIMPLE",
			"MB_CASE_UPPER_SIMPLE",
			"MB_CASE_TITLE_SIMPLE",
			"MB_CASE_FOLD_SIMPLE",
		);
		
		$params["FLAGS"] = Array(
			"PREG_SPLIT_NO_EMPTY" => "PREG_SPLIT_NO_EMPTY",
			"PREG_SPLIT_DELIM_CAPTURE" => "PREG_SPLIT_DELIM_CAPTURE",
			"PREG_SPLIT_OFFSET_CAPTURE" => "PREG_SPLIT_OFFSET_CAPTURE",
		);
		
		$params["PAD_TYPE"] = Array(
			"STR_PAD_RIGHT" => GetMessage("PARAM_STR_PAD_RIGHT"),
			"STR_PAD_LEFT" => GetMessage("PARAM_STR_PAD_LEFT"),
			"STR_PAD_BOTH" => GetMessage("PARAM_STR_PAD_BOTH"),
		);
		
		$params["LANG"] = Array();
		$rsLang = CLanguage::GetList($by="lid", $order="desc", Array());
		while ($arLang = $rsLang->Fetch())
		{
			$params["LANG"][$arLang["LANGUAGE_ID"]] = $arLang["NAME"];
		}
		
		$params["CHANGE_CASE"] = Array(
			"" => GetMessage("PARAM_NOT_CHANGE"),
			"L" => GetMessage("PARAM_CHANGE_CASE_L"),
			"U" => GetMessage("PARAM_CHANGE_CASE_U"),
		);
		
		return $params;
	}
	
	public static function GetParamsValue($value, $sep = '<br />')
	{
		$paramsArr = unserialize(base64_decode($value));
		$PARAMS_VALUE = '';
		if(!is_array($paramsArr))
		{
			$PARAMS_VALUE = GetMessage("PARAMS_NO");
		}
		else
		{
			foreach($paramsArr as $code => $param)
			{
				$PARAMS_VALUE .= "{$code}: {$param}{$sep}";
			}
		}
		
		return rtrim($PARAMS_VALUE, $sep);
	}
	
	private static function GetParamsValueArray($params) : array
	{
		if($params)
		{
			$paramssArr = unserialize(base64_decode($params));
			if(!is_array($paramssArr))
				$paramssArr = Array();
		}
		else
		{
			$paramssArr = array();
		}
		
		return $paramssArr;
	}
	
	public static function GetTypes($orderByGroups = false) : array
	{
		$fields = Array();
		
		$strip = Array(
			"trim" => GetMessage("trim"),
			"ltrim" => GetMessage("ltrim"),
			"rtrim" => GetMessage("rtrim"),
			"strip_tags" => GetMessage("strip_tags"),
		);
		
		$fields = array_merge($fields, $strip);
		
		$slashes = Array(
			"addslashes" => GetMessage("addslashes"),
			"stripslashes" => GetMessage("stripslashes"),
		);
		
		$fields = array_merge($fields, $slashes);
		
		$case = Array(
			"strtolower" => GetMessage("strtolower"),
			"strtoupper" => GetMessage("strtoupper"),
			"mb_strtolower" => GetMessage("mb_strtolower"),
			"mb_strtoupper" => GetMessage("mb_strtoupper"),
			"mb_convert_case" => GetMessage("mb_convert_case"),
			"ucfirst" => GetMessage("ucfirst"),
			"lcfirst" => GetMessage("lcfirst"),
			"ucwords" => GetMessage("ucwords"),
		);
		
		$fields = array_merge($fields, $case);
		
		$array = Array(
			"explode" => GetMessage("explode"),
			"preg_split" => GetMessage("preg_split"),
			"array_shift" => GetMessage("array_shift"),
			"array_pop" => GetMessage("array_pop"),
			"array_slice" => GetMessage("array_slice"),
		);
		
		$fields = array_merge($fields, $array);
		
		$format = Array(
			"money_format" => GetMessage("money_format"),
			"number_format" => GetMessage("number_format"),
		);
		if(version_compare(PHP_VERSION, '7.3.0', '>='))
		{
			unset($format["money_format"]);
		}
		
		$fields = array_merge($fields, $format);
		
		$replace = Array(
			"str_replace" => GetMessage("str_replace"),
			"preg_replace" => GetMessage("preg_replace"),
			"str_pad" => GetMessage("str_pad"),
		);
		
		$fields = array_merge($fields, $replace);
		
		$search = Array(
			"strstr" => GetMessage("strstr"),
			"stristr" => GetMessage("stristr"),
			"strrchr" => GetMessage("strrchr"),
		);
		
		$fields = array_merge($fields, $search);
		
		$val = Array(
			"intval" => GetMessage("intval"),
			"floatval" => GetMessage("floatval"),
			"strval" => GetMessage("strval"),
		);
		
		$fields = array_merge($fields, $val);
		
		$arithmetic = Array(
			"abs" => GetMessage("abs"),
			"ceil" => GetMessage("ceil"),
			"floor" => GetMessage("floor"),
			"round" => GetMessage("round"),
			"arithmetic_addition" => GetMessage("arithmetic_addition"),
			"arithmetic_subtraction" => GetMessage("arithmetic_subtraction"),
			"arithmetic_multiplication" => GetMessage("arithmetic_multiplication"),
			"arithmetic_division" => GetMessage("arithmetic_division"),
			"arithmetic_modulo" => GetMessage("arithmetic_modulo"),
			"arithmetic_exponentiation" => GetMessage("arithmetic_exponentiation"),
		);
		
		$fields = array_merge($fields, $arithmetic);
		
		$bitrix = Array(
			"translit" => GetMessage("translit"),
		);
		
		$fields = array_merge($fields, $bitrix);
		
		$logic = Array(
			"eval" => GetMessage("eval"),
		);
		
		$fields = array_merge($fields, $logic);
		
		return $fields;
	}
	
	public static function ApplyProcessingRules(&$field, $rules)
	{
		
		global $IMPORT_PARAMS, $ITEM_FIELDS, $entities;
		
		$cProcessingSettingsData = new CDataImportProcessingSettings;
		if(count($rules))
		{
			$ruleRes = $cProcessingSettingsData->GetList(Array("SORT" => "ASC"), Array("ID" => $rules));
			while($ruleArr = $ruleRes->Fetch())
			{
				if($ruleArr["ACTIVE"] == "Y")
				{
					$ruleArr["PARAMS"] = self::GetParamsValueArray($ruleArr["PARAMS"]);
					$function = $ruleArr["PROCESSING_TYPE"];
					switch($function)
					{
						case("trim"):
						case("rtrim"):
						case("ltrim"):
							if($ruleArr["PARAMS"]["CHARACTER_MASK"])
								$field = $function($field, $ruleArr["PARAMS"]["CHARACTER_MASK"]);
							else
								$field = $function($field);
							break;
						case("strip_tags"):
							if($ruleArr["PARAMS"]["ALLOWABLE_TAGS"])
								$field = $function($field, $ruleArr["PARAMS"]["ALLOWABLE_TAGS"]);
							else
								$field = $function($field);
							break;
						case("intval"):
						case("floatval"):
						case("strval"):
						case("addslashes"):
						case("stripslashes"):
						case("strtolower"):
						case("strtoupper"):
						case("lcfirst"):
							$field = $function($field);
							break;
						case("ucfirst"):
							$field = CDataCoreFunctions::mb_ucfirst($field);
							break;
						case("abs"):
						case("ceil"):
						case("floor"):
						case("round"):
							if($field)
								$field = $function($field);
							break;
						case("array_shift"):
						case("array_pop"):
							if(is_array($field))
							{
								$field = $function($field);
							}
							break;
						case("mb_strtolower"):
						case("mb_strtoupper"):
							if($ruleArr["PARAMS"]["ENCODING"])
								$field = $function($field, $ruleArr["PARAMS"]["ENCODING"]);
							else
								$field = $function($field);
							break;
						case("mb_convert_case"):
							if($ruleArr["PARAMS"]["ENCODING"])
								$field = $function($field, $ruleArr["PARAMS"]["MODE"], $ruleArr["PARAMS"]["ENCODING"]);
							else
								$field = $function($field, $ruleArr["PARAMS"]["MODE"]);
							break;
						case("ucwords"):
							if($ruleArr["PARAMS"]["DELIMITERS"])
								$field = $function($field, $ruleArr["PARAMS"]["DELIMITERS"]);
							else
								$field = $function($field);
							break;
						case("explode"):
							if(is_string($field) && stripos($field, $ruleArr["PARAMS"]["DELIMITER"]) !== false)
							{
								if(isset($ruleArr["PARAMS"]["LIMIT"]) && $ruleArr["PARAMS"]["LIMIT"] != "")
									$field = $function("{$ruleArr["PARAMS"]["DELIMITER"]}", $field, $ruleArr["PARAMS"]["LIMIT"]);
								else
									$field = $function("{$ruleArr["PARAMS"]["DELIMITER"]}", $field);
							}
							break;
						case("preg_split"):
							if($ruleArr["PARAMS"]["FLAGS"])
							{
								if(in_array("PREG_SPLIT_NO_EMPTY", $ruleArr["PARAMS"]["FLAGS"]))
									$FLAGS =+ PREG_SPLIT_NO_EMPTY;
								if(in_array("PREG_SPLIT_DELIM_CAPTURE", $ruleArr["PARAMS"]["FLAGS"]))
									$FLAGS =+ PREG_SPLIT_DELIM_CAPTURE;
								if(in_array("PREG_SPLIT_OFFSET_CAPTURE", $ruleArr["PARAMS"]["FLAGS"]))
									$FLAGS =+ PREG_SPLIT_OFFSET_CAPTURE;
								$field = $function($ruleArr["PARAMS"]["PATTERN"], $field, $ruleArr["PARAMS"]["LIMIT"] == ""?NULL:$ruleArr["PARAMS"]["LIMIT"], $FLAGS);
								unset($FLAGS);
							}
							else
								$field = $function($ruleArr["PARAMS"]["PATTERN"], $field, $ruleArr["PARAMS"]["LIMIT"] == ""?NULL:$ruleArr["PARAMS"]["LIMIT"]);
							break;
						case("array_slice"):
							if(is_array($field))
							{
								if($ruleArr["PARAMS"]["OFFSET"] > 0 && count($field) <= $ruleArr["PARAMS"]["OFFSET"])
									continue;
								
								$field = $function($field, $ruleArr["PARAMS"]["OFFSET"] == ""?0:$ruleArr["PARAMS"]["OFFSET"], $ruleArr["PARAMS"]["LENGTH"] == ""?NULL:intVal($ruleArr["PARAMS"]["LENGTH"]), $ruleArr["PARAMS"]["PRESERVE_KEYS"] == "Y"?true:false);
							}
							break;
						case("money_format"):
							if(version_compare(PHP_VERSION, '7.3.0', '<='))
							{
								$field = money_format($ruleArr["PARAMS"]["FORMAT"], $field);
							}
							break;
						case("number_format"):
							$field = $function($field, isset($ruleArr["PARAMS"]["DECIMALS"])?$ruleArr["PARAMS"]["DECIMALS"]:0, isset($ruleArr["PARAMS"]["DEC_POINT"])?$ruleArr["PARAMS"]["DEC_POINT"]:".", isset($ruleArr["PARAMS"]["THOUSANDS_SEP"])?$ruleArr["PARAMS"]["THOUSANDS_SEP"]:",");
							break;
						case("str_replace"):
							if(isset($ruleArr["PARAMS"]["COUNT"]))
								$field = $function($ruleArr["PARAMS"]["SEARCH"], $ruleArr["PARAMS"]["REPLACE"], $field, $ruleArr["PARAMS"]["COUNT"]);
							else
								$field = $function($ruleArr["PARAMS"]["SEARCH"], $ruleArr["PARAMS"]["REPLACE"], $field);
							break;
						case("preg_replace"):
							if($ruleArr["PARAMS"]["PATTERN"] != "" && $ruleArr["PARAMS"]["REPLACEMENT"] != "")
							{
								if(isset($ruleArr["PARAMS"]["COUNT"]))
									$field = $function($ruleArr["PARAMS"]["PATTERN"], $ruleArr["PARAMS"]["REPLACEMENT"], $field, isset($ruleArr["PARAMS"]["LIMIT"])?$ruleArr["PARAMS"]["LIMIT"]:-1, $ruleArr["PARAMS"]["COUNT"]);
								else
									$field = $function($ruleArr["PARAMS"]["PATTERN"], $ruleArr["PARAMS"]["REPLACEMENT"], $field, isset($ruleArr["PARAMS"]["LIMIT"])?$ruleArr["PARAMS"]["LIMIT"]:-1);
							}
							break;
						case("strstr"):
						case("stristr"):
							if(isset($ruleArr["PARAMS"]["NEEDLE"]) && $ruleArr["PARAMS"]["NEEDLE"] != "")
							{
								$temp = $function($field, $ruleArr["PARAMS"]["NEEDLE"], $ruleArr["PARAMS"]["BEFORE_NEEDLE"] == "Y"?true:false);
								if($temp !== false)
									$field = $temp;
								unset($temp);
							}
							break;
						case("strrchr"):
							if(isset($ruleArr["PARAMS"]["NEEDLE"]) && $ruleArr["PARAMS"]["NEEDLE"] != "")
							{
								$temp = $function($field, $ruleArr["PARAMS"]["NEEDLE"]);
								if($temp !== false)
									$field = $temp;
								unset($temp);
							}
							break;
						case("str_pad"):
							if($ruleArr["PARAMS"]["PAD_STRING"] != "" && $ruleArr["PARAMS"]["PAD_LENGTH"] > 0)
							{
								$field = $function($field, $ruleArr["PARAMS"]["PAD_LENGTH"], $ruleArr["PARAMS"]["PAD_STRING"], $ruleArr["PARAMS"]["PAD_TYPE"] == "STR_PAD_BOTH"?STR_PAD_BOTH:($ruleArr["PARAMS"]["PAD_TYPE"] == "STR_PAD_LEFT"?STR_PAD_LEFT:STR_PAD_RIGHT));
							}
							break;
						case("arithmetic_addition"):
							if(isset($field) && is_numeric($field) && isset($ruleArr["PARAMS"]["VALUE"]) && is_numeric($ruleArr["PARAMS"]["VALUE"]))
								$field = $field + $ruleArr["PARAMS"]["VALUE"];
							break;
						case("arithmetic_subtraction"):
							if(isset($field) && is_numeric($field) && isset($ruleArr["PARAMS"]["VALUE"]) && is_numeric($ruleArr["PARAMS"]["VALUE"]))
								$field = $field - $ruleArr["PARAMS"]["VALUE"];
							break;
						case("arithmetic_multiplication"):
							if(isset($field) && is_numeric($field) && isset($ruleArr["PARAMS"]["VALUE"]) && is_numeric($ruleArr["PARAMS"]["VALUE"]))
								$field = $field * $ruleArr["PARAMS"]["VALUE"];
							break;
						case("arithmetic_division"):
							if(isset($field) && is_numeric($field) && isset($ruleArr["PARAMS"]["VALUE"]) && is_numeric($ruleArr["PARAMS"]["VALUE"]))
								$field = $field / $ruleArr["PARAMS"]["VALUE"];
							break;
						case("arithmetic_modulo"):
							if(isset($field) && is_numeric($field) && isset($ruleArr["PARAMS"]["VALUE"]) && is_numeric($ruleArr["PARAMS"]["VALUE"]))
								$field = $field % $ruleArr["PARAMS"]["VALUE"];
							break;
						case("arithmetic_exponentiation"):
							if(isset($field) && is_numeric($field) && isset($ruleArr["PARAMS"]["VALUE"]) && is_numeric($ruleArr["PARAMS"]["VALUE"]))
								$field = $field ** $ruleArr["PARAMS"]["VALUE"];
							break;
						case("translit"):
							if(isset($field) && is_string($field))
								$field = Cutil::translit($field, $ruleArr["PARAMS"]["LANG"], Array(
									"max_len" => $ruleArr["PARAMS"]["MAX_LEN"]?$ruleArr["PARAMS"]["MAX_LEN"]:100,
									"change_case" => $ruleArr["PARAMS"]["CHANGE_CASE"]==""?false:$ruleArr["PARAMS"]["CHANGE_CASE"],
									"replace_space" => $ruleArr["PARAMS"]["REPLACE_SPACE"]?$ruleArr["PARAMS"]["REPLACE_SPACE"]:"_",
									"replace_other" => $ruleArr["PARAMS"]["REPLACE_OTHER"]?$ruleArr["PARAMS"]["REPLACE_OTHER"]:"_",
									"delete_repeat_replace" => $ruleArr["PARAMS"]["DELETE_REPEAT_REPLACE"]=="Y"?true:false,
									"safe_chars" => $ruleArr["PARAMS"]["SAFE_CHARS"],
								));
							break;
						case("eval"):
							eval("{$ruleArr["PARAMS"]["CODE"]}");
							break;
					}
				}
			}
		}
	}
}
?>