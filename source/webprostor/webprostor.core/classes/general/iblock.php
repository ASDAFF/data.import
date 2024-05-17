<?
Class CWebprostorCoreIblock
{
	public static function GetDefaultProperties($IBLOCK_ID = false, $params = [])
	{
		$result = Array();
		
		if(CModule::IncludeModule("iblock") && $IBLOCK_ID)
		{
			$propRes = CIBlockProperty::GetList(Array("sort"=>"asc"), Array("ACTIVE"=>"Y", "IBLOCK_ID"=>$IBLOCK_ID, "PROPERTY_TYPE" => "S"));
			while ($propArr = $propRes->GetNext(true, false))
			{
				if($propArr["DEFAULT_VALUE"])
				{
					if($propArr["USER_TYPE"] == "HTML" && $params["USE_HTML_TEXT"] == "Y")
						$result[$propArr["ID"]] = $propArr["DEFAULT_VALUE"]["TEXT"];
					else
						$result[$propArr["ID"]] = $propArr["DEFAULT_VALUE"];
				}
			}
			$propRes = CIBlockProperty::GetList(Array("sort"=>"asc"), Array("ACTIVE"=>"Y", "IBLOCK_ID"=>$IBLOCK_ID, "PROPERTY_TYPE" => "N"));
			while ($propArr = $propRes->GetNext(true, false))
			{
				if($propArr["DEFAULT_VALUE"])
				{
					$result[$propArr["ID"]] = $propArr["DEFAULT_VALUE"];
				}
			}
			$propRes = CIBlockProperty::GetList(Array("sort"=>"asc"), Array("ACTIVE"=>"Y", "IBLOCK_ID"=>$IBLOCK_ID, "PROPERTY_TYPE" => "L"));
			while ($propArr = $propRes->GetNext(true, false))
			{
				$listRes = CIBlockProperty::GetPropertyEnum($propArr["ID"], Array("sort"=>"asc"), Array("IBLOCK_ID"=>$IBLOCK_ID));
				while($listArr = $listRes->GetNext(true, false))
				{
					if($listArr["DEF"] == "Y")
					{
						$result[$propArr["ID"]] = $params["USE_LIST_VALUE"] == "Y"?$listArr["VALUE"]:$listArr["ID"];
						break;
					}
				}
			}
		}
		
		return $result;
	}
	
	public static function GetDefaultElementFields($IBLOCK_ID = false, array &$fields = Array())
	{
		$iblockParams = CIBlock::GetFields($IBLOCK_ID);
		
		$iblockElementParams = Array(
			"IBLOCK_SECTION" => $iblockParams["IBLOCK_SECTION"],
			"ACTIVE" => $iblockParams["ACTIVE"],
			"ACTIVE_FROM" => $iblockParams["ACTIVE_FROM"],
			"ACTIVE_TO" => $iblockParams["ACTIVE_TO"],
			"SORT" => $iblockParams["SORT"],
			"NAME" => $iblockParams["NAME"],
			"PREVIEW_PICTURE" => $iblockParams["PREVIEW_PICTURE"],
			"PREVIEW_TEXT_TYPE" => $iblockParams["PREVIEW_TEXT_TYPE"],
			"PREVIEW_TEXT" => $iblockParams["PREVIEW_TEXT"],
			"DETAIL_PICTURE" => $iblockParams["DETAIL_PICTURE"],
			"DETAIL_TEXT_TYPE" => $iblockParams["DETAIL_TEXT_TYPE"],
			"DETAIL_TEXT" => $iblockParams["DETAIL_TEXT"],
			"CODE" => $iblockParams["CODE"],
			"TAGS" => $iblockParams["TAGS"],
		);
		
		self::CheckFields($fields, $iblockElementParams);
	}
	
	private static function CheckFields(&$fields, $params)
	{
		foreach($params as $code => $param)
		{
			if(
				(!isset($fields[$code]) || empty($fields[$code]))
				&& ($param["IS_REQUIRED"] == "Y" && is_string($param["DEFAULT_VALUE"]) && strlen($param["DEFAULT_VALUE"])>0)
			)
			{
				$fields[$code] = $param["DEFAULT_VALUE"];
			}
		}
		
		$previewImageParams = $params["PREVIEW_PICTURE"]["DEFAULT_VALUE"];
		if($previewImageParams["FROM_DETAIL"] == "Y")
		{
			if($fields["DETAIL_PICTURE"] && is_array($fields["DETAIL_PICTURE"]))
			{
				if(
					($fields["PREVIEW_PICTURE"] && is_array($fields["PREVIEW_PICTURE"]) && $previewImageParams["UPDATE_WITH_DETAIL"] == "Y") || 
					(!$fields["PREVIEW_PICTURE"] && !is_array($fields["PREVIEW_PICTURE"]))
				)
				{
					$fields["PREVIEW_PICTURE"] = $fields["DETAIL_PICTURE"];
				}
			}
		}
		
		$codeParams = $params["CODE"]["DEFAULT_VALUE"];
		if($codeParams["TRANSLITERATION"] == "Y")
		{
			if(!empty($fields["CODE"]))
				$translitName = $fields["CODE"];
			elseif(!empty($fields["NAME"]))
				$translitName = $fields["NAME"];
			
			if($translitName)
			{
				$translitParams = array(
					"max_len" => $codeParams["TRANS_LEN"],
					"change_case" => $codeParams["TRANS_CASE"],
					"replace_space" => $codeParams["TRANS_SPACE"],
					"replace_other" => $codeParams["TRANS_OTHER"],
				);
				$fields["CODE"] = Cutil::translit($translitName, LANGUAGE_ID, $translitParams);
			}
		}
	}
}