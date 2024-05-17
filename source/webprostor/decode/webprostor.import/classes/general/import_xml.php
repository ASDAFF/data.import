<?
use Bitrix\Main\Localization\Loc;

class CWebprostorImportXML
{
	CONST MODULE_ID = "webprostor.import";

	var $DEBUG_MEMORY;
	var $DEBUG_EXECUTION_TIME;
	protected $isValidate = true;
	
	function __construct($debug_memory = false, $debug_time = false)
	{
		if($debug_memory)
			$this->DEBUG_MEMORY = true;
		else
			$this->DEBUG_MEMORY = false;
		
		if($debug_time)
			$this->DEBUG_EXECUTION_TIME = true;
		else
			$this->DEBUG_EXECUTION_TIME = false;
		
		/*if(!extension_loaded('xmlreader'))
		{
			$errorArray = Array(
				"MESSAGE" => Loc::getMessage("XMLREADER_NOT_INCLUDED"),
				"TAG" => "XMLREADER_NOT_INCLUDED",
				"MODULE_ID" => "WEBPROSTOR.IMPORT",
				"ENABLE_CLOSE" => "Y"
			);
			$notifyID = CAdminNotify::Add($errorArray);
		}*/
		if(!extension_loaded('dom'))
		{
			$errorArray = Array(
				"MESSAGE" => Loc::getMessage("DOM_NOT_INCLUDED"),
				"TAG" => "DOM_NOT_INCLUDED",
				"MODULE_ID" => "WEBPROSTOR.IMPORT",
				"ENABLE_CLOSE" => "Y"
			);
			$notifyID = CAdminNotify::Add($errorArray);
		}
	}
	
	private function convArray($array, $from_char, $in_char = "UTF-8")
	{
		if(is_array($array) && count($array) > 0)
		{
			foreach ($array as $key => $value)
			{
				if(is_array($value))
					$array[$key] = self::convArray($value, $from_char, $in_char);
				else
				{
					$array[$key] = iconv($from_char, $in_char, $value);
				}
			}
		}
		return $array;
	}
	
	private function checkVal($value)
	{
		if($value === 'false')
			$value = Loc::getMessage("PARAM_VALUE_FALSE");
		if($value === 'true')
			$value = Loc::getMessage("PARAM_VALUE_TRUE");
		
		return $value;
	}
	
	public function ParseFile(
		string $file, 
		string $char = "UTF-8", 
		$entity_group = false, 
		$entity = false, 
		string $parse_params = "N", 
		$entity_param = 'param', 
		$limit = false, 
		$start_element = 0
	) : array
	{
		$result = [];
		
		if(!file_exists($file))
			return $result;
		
		if($this->DEBUG_EXECUTION_TIME)
			$scriptExecutionTime = microtime(true);
		
		if($this->DEBUG_MEMORY)
			CWebprostorCoreFunctions::dump(['START IMPORT', CWebprostorCoreFunctions::ConvertFileSize(memory_get_usage())]);
		
		if(!extension_loaded('dom'))
		{
			$xml = simplexml_load_file($file, 'SimpleXMLElement', LIBXML_NOCDATA);
			
			$all_count = count($xml->$entity);
			
			$json_array = json_decode(json_encode($xml), 1);
			
			$data = $json_array;
			
			unset($json_array);
		}
		else
		{
			
			$doc = new DOMDocument;
			//$doc->validateOnParse = true;
			$doc->load($file, LIBXML_NOCDATA | LIBXML_COMPACT | LIBXML_BIGLINES | LIBXML_PARSEHUGE | LIBXML_DTDVALID);
			
			//if($doc->validate())
			if(1)
			{
				if($entity_group && $entity)
				{
					$xpath = new DOMXPath($doc);
					$entites = $xpath->query("//{$entity_group}/{$entity}");
					
					if($entites->length === 0)
					{
						$entites_group = $doc->getElementsByTagName($entity_group);
						foreach($entites_group as $inner_entity)
						{
							$entites = $inner_entity->getElementsByTagName($entity);
						}
					}
				}
				elseif($entity)
				{
					$entites = $doc->getElementsByTagName($entity);
				}
				
				for($i = $start_element; $i<($start_element + $limit); $i++)
				{
					if($entites[$i] != NULL)
					{
						$node = simplexml_import_dom($entites[$i]);
						
						$node_data = json_decode(json_encode($node, JSON_OBJECT_AS_ARRAY), 1);

						if($parse_params === "Y")
						{
							if($entity_param == '' && !$entity_param)
								$entity_param = 'param';
							if(is_array($node_data[$entity_param]) && count($node_data[$entity_param])>0)
							{
								foreach($node_data[$entity_param] as $c => $param)
								{
									if(is_int($c))
									{
										$param_data = 
										json_decode(
											json_encode(
												$node->param[$c]->attributes(), 
												JSON_OBJECT_AS_ARRAY
											), 
											1
										);
									}
									
									if(
										isset($param_data["@attributes"]["name"])
										&& isset($param)
										&& $param_data != $param
									)
									{
										$node_data["param_array"][] = Array(
											"NAME" => $param_data["@attributes"]["name"],
											"CODE" => $param_data["@attributes"]["code"],
											"UNIT" => $param_data["@attributes"]["unit"],
											"VALUE" => self::checkVal($param),
										);
									}
								}
							}
							//elseif(($props_count = $entites[$i]->getElementsByTagName("prop")->length) > 0)
							elseif(is_array($node_data["props"]["prop"]) && count($node_data["props"]["prop"])>0)
							{
								foreach($node_data["props"]["prop"] as $c => $prop)
								{
									if(is_int($c))
									{
										$param_data = 
										json_decode(
											json_encode(
												$node->props->prop[$c]->attributes(), 
												JSON_OBJECT_AS_ARRAY
											), 
											1
										);
									}
									if(
										isset($param_data["@attributes"]["name"])
										&& isset($node_data["props"]["prop"][$c])
										&& !isset($node_data["props"]["prop"][$c]["@attributes"])
									)
									{
										$node_data["param_array"][] = Array(
											"NAME" => $param_data["@attributes"]["name"],
											"UNIT" => $param_data["@attributes"]["unit"],
											"VALUE" => self::checkVal($node_data["props"]["prop"][$c]),
										);
									}
								}
							}
							elseif(is_array($node_data["properties"]["property"]) && count($node_data["properties"]["property"])>0)
							{
								foreach($node_data["properties"]["property"] as $c => $property)
								{
									if(is_int($c))
									{
										$param_data = 
										json_decode(
											json_encode(
												$node->properties->property[$c]->attributes(), 
												JSON_OBJECT_AS_ARRAY
											), 
											1
										);
									}
									if(
										isset($param_data["@attributes"]["name"]) 
										&& isset($node_data["properties"]["property"][$c]) 
										&& !isset($node_data["properties"]["property"][$c]["@attributes"])
									)
									{
										$node_data["param_array"][] = Array(
											"NAME" => $param_data["@attributes"]["name"],
											"CODE" => $param_data["@attributes"]["code"],
											"UNIT" => $param_data["@attributes"]["unit"],
											"VALUE" => self::checkVal($node_data["properties"]["property"][$c]),
										);
									}
								}
							}
							$rsHandlers = GetModuleEvents(self::MODULE_ID, "onAfterXmlParseParamsToProperties");
							while($arHandler = $rsHandlers->Fetch())
							{
								$additionalParams = ExecuteModuleEvent($arHandler, $node, $node_data);
							}
							
							if(is_array($additionalParams))
								$node_data["param_array"] = $additionalParams;
							
							unset($additionalParams, $param_data);
						}
						
						$data[] = $node_data;
					
					}
					
					$all_count++;
				}
			}
			else
			{
				$this->isValidate = false; 
			}
		}
		
		if((defined("BX_UTF") && BX_UTF) && $char != "UTF-8")
		{
			$result["DATA"] = self::convArray($data, $char, "UTF-8");
		}
		elseif(((defined("BX_UTF") && !BX_UTF) || !defined("BX_UTF")) && $char != "WINDOWS-1251")
		{
			$result["DATA"] = self::convArray($data, $char, "WINDOWS-1251");
		}
		else
			$result["DATA"] = $data;
		
		unset($data);
		
		$result["ITEMS_COUNT"] = $all_count;
		
		if($this->DEBUG_MEMORY)
			CWebprostorCoreFunctions::dump(['START IMPORT', CWebprostorCoreFunctions::ConvertFileSize(memory_get_usage())]);
		
		if($this->DEBUG_EXECUTION_TIME)
			echo round(microtime(true) - $scriptExecutionTime, 4).' s.';
		
		return $result;
	}
	
	public function __ParseFile($file, $char = "UTF-8", $entity, $parse_params = "N", $limit = false, $start_element = 0)
	{
		$result = [];
		$has_next_node = false;
		
		if(!extension_loaded('xmlreader'))
		{
			$xml = simplexml_load_file($file, 'SimpleXMLElement', LIBXML_NOCDATA);
			
			$all_count = count($xml->$entity);
			
			$json_array = json_decode(json_encode($xml), 1);
			
			$data = $json_array;
			
			unset($json_array);
		}
		else
		{
			$xml = new XMLReader;
			$xml->open($file, null, LIBXML_NOCDATA);
			
			$doc = new DOMDocument;
			$doc->validateOnParse = true;
			$doc->load($file);
			
			$data = [];
			
			$all_count = 0;
			$counter = 0;
			
			while($xml->read())
			{
				
				if($limit && $all_count >= $limit)
				{
					$has_next_node = true;
					break 1;
				}
				
				if($xml->nodeType == XMLReader::ELEMENT)
				{
					if($xml->localName == $entity)
					{
						
						if($start_element > $counter)
						{
							$counter++;
							continue;
						}
						
						$node_dom = $doc->importNode($xml->expand(), true);
						
						$node = simplexml_import_dom($node_dom);
						unset($node_dom);
						
						$node_data = json_decode(json_encode($node, JSON_OBJECT_AS_ARRAY), 1);
						unset($node);
						
						if($parse_params === "Y")
						{
							$xml2 = simplexml_load_string($xml->readOuterXml());
							$param_count = count($xml2->param);
							if($param_count>0)
							{
								for($i = 0; $i < $param_count; $i ++)
								{
									$param_data = json_decode(json_encode($xml2->param[$i], JSON_OBJECT_AS_ARRAY), 1);
									if(isset($param_data["@attributes"]["name"]) && isset($param_data[0]))
									{
										$node_data["param_array"][] = Array(
											"NAME" => $param_data["@attributes"]["name"],
											"UNIT" => $param_data["@attributes"]["unit"],
											"VALUE" => self::checkVal($param_data[0]),
										);
									}
									unset($param_data);
								}
							}
							else
							{
								$product_chars_label = Loc::getMessage("PRODUCT_CHARS_LABEL");
								
								$param_count = count($xml2->$product_chars_label);
								if($param_count>0)
								{
									$char_data_tmp = json_decode(json_encode($xml2->$product_chars_label, JSON_OBJECT_AS_ARRAY), 1);
									$char_data = $char_data_tmp[Loc::getMessage("PRODUCT_CHAR_LABEL")];
									unset($char_data_tmp);
									if(count($char_data)>0)
									{
										foreach($char_data as $char_val)
										{
											if(isset($char_val[Loc::getMessage("PRODUCT_CHAR_NAME_LABEL")]) && isset($char_val[Loc::getMessage("PRODUCT_CHAR_VALUE_LABEL")]))
											{
												$node_data["param_array"][] = Array(
													"NAME" => $char_val[Loc::getMessage("PRODUCT_CHAR_NAME_LABEL")],
													"VALUE" => self::checkVal($char_val[Loc::getMessage("PRODUCT_CHAR_VALUE_LABEL")]),
												);
											}
										}
									}
									unset($char_data);
								}
								else
								{
									$param_count = count($xml2->prop);
									if($param_count>0)
									{
										for($i = 0; $i < $param_count; $i ++)
										{
											$param_data = json_decode(json_encode($xml2->prop[$i], JSON_OBJECT_AS_ARRAY), 1);
											if(isset($param_data["@attributes"]["name"]) && isset($param_data[0]))
											{
												$node_data["param_array"][] = Array(
													"NAME" => $param_data["@attributes"]["name"],
													"UNIT" => $param_data["@attributes"]["unit"],
													"VALUE" => self::checkVal($param_data[0]),
												);
											}
											unset($param_data);
										}
									}
								}
							}
							unset($xml2);
						}
						
						if(($xml->hasAttributes && count($node_data) == 2) || count($node_data) == 1)
						{
							$node_data[$entity] = $node_data[0];
							unset($node_data[0]);
						}
						$data[] = $node_data;
						$all_count++;
						
						unset($node_data);
					}
				}
			}
			
			$xml->close();
		}
		
		if((defined("BX_UTF") && BX_UTF) && $char != "UTF-8")
		{
			$result["DATA"] = self::convArray($data, $char, "UTF-8");
		}
		elseif(((defined("BX_UTF") && !BX_UTF) || !defined("BX_UTF")) && $char != "WINDOWS-1251")
		{
			$result["DATA"] = self::convArray($data, $char, "WINDOWS-1251");
		}
		else
			$result["DATA"] = $data;
		
		unset($data);
		
		$result["ITEMS_COUNT"] = $all_count;
		$result["NEXT_NODE"] = $has_next_node;
		
		return $result;
	}
	
	public function GetTotalCount(&$file, &$entity_group, &$entity)/* : int*/
	{
		$result = 0;
		
		if(is_file($file))
		{
			CheckDirPath($_SERVER["DOCUMENT_ROOT"].'/bitrix/tmp/'.self::MODULE_ID.'/');
			$tempFile = $_SERVER["DOCUMENT_ROOT"].'/bitrix/tmp/'.self::MODULE_ID.'/'.$GLOBALS["PLAN_ID"].'_total.txt';
		}
		
		if($tempFile && is_file($tempFile) && (filemtime($file) > filemtime($tempFile)))
		{
			unlink($tempFile);
		}
		
		if($tempFile && is_file($tempFile) && (filemtime($file) < filemtime($tempFile)))
		{
			$tempData = file_get_contents($tempFile);
			$result = unserialize($tempData);
			unset($tempData);
		}
		else
		{
			if(extension_loaded('dom'))
			{
				$doc = new DOMDocument;
				$doc->validateOnParse = true;
				$doc->load($file, LIBXML_DTDVALID);
				
				//if($doc->validate())
				if(1)
				{
					if($entity_group && $entity)
					{
						$xp = new DOMXPath($doc);
						$result = $xp->evaluate('count(//'.$entity_group.'/'.$entity.')');
					}
					elseif($entity)
					{
						$result = $doc->getElementsByTagName($entity)->length;
					}
				}
				else
				{
					if($tempFile)
						unlink($tempFile);
					$this->isValidate = false; 
				}
			}
			
			if($tempFile && !is_file($tempFile))
				file_put_contents($tempFile, serialize($result));
		}
		
		return $result;
	}
	
	public function GetDataArray($data, &$params, $startFrom = 0)
	{
		$importFinished = false;
		
		if(!extension_loaded('dom'))
		{
			$temp = $data[$params["XML_ENTITY"]];
		}
		else
		{
			$temp = $data;
		}
		unset($data);
		
		foreach($temp as $key => $values)
		{
			foreach($values as $code => $value)
			{
				$new_data[$key][$code] = $value;
			}
		}
		unset($temp);
		
		$IMPORT_FILE = $_SERVER["DOCUMENT_ROOT"].$params["IMPORT_FILE"];
		$endTo = $startFrom + $params["ITEMS_PER_ROUND"];
		
		if($endTo >= self::GetTotalCount($IMPORT_FILE, $params["XML_ENTITY_GROUP"], $params["XML_ENTITY"], $params["ID"]))
		{
			$endTo = 0;
			$importFinished = true;
		}
		
		$result = Array(
			"DATA_ARRAY" => $new_data,
			"START_FROM" => $endTo,
			"FINISHED" => $importFinished,
		);
		
		return $result;
	}
	
	public function __GetDataArray($data, &$params, $startFrom = 0, $next_node = false)
	{
		$importFinished = false;
		
		if(!extension_loaded('xmlreader'))
		{
			$temp = $data[$params["XML_ENTITY"]];
		}
		else
		{
			$temp = $data;
		}
		unset($data);
		
		foreach($temp as $key => $values)
		{
			foreach($values as $code => $value)
			{
				$new_data[$key][$code] = $value;
			}
		}
		unset($temp);
		
		$endTo = $startFrom + $params["ITEMS_PER_ROUND"];

		if($next_node === false)
		{
			$endTo = 0;
			$importFinished = true;
		}
		
		$result = Array(
			"DATA_ARRAY" => $new_data,
			"START_FROM" => $endTo,
			"FINISHED" => $importFinished,
		);
		
		return $result;
	}
	
	public function GetEntities($PLAN_ID = false, $IMPORT_FILE = false)
	{
		$CPlan = new CWebprostorImportPlan;
		$planRes = $CPlan->GetById($PLAN_ID);
		$planInfo = $planRes->Fetch();
		
		$entities = [];
		
		if(!$IMPORT_FILE)
			$IMPORT_FILE = $_SERVER["DOCUMENT_ROOT"].$planInfo["IMPORT_FILE"];
		
		if(is_file($IMPORT_FILE))
		{
			CheckDirPath($_SERVER["DOCUMENT_ROOT"].'/bitrix/tmp/'.self::MODULE_ID.'/');
			$tempFile = $_SERVER["DOCUMENT_ROOT"].'/bitrix/tmp/'.self::MODULE_ID.'/'.$PLAN_ID.'_entities.txt';
		}
		
		if($tempFile && is_file($tempFile) && (filemtime($IMPORT_FILE) > filemtime($tempFile)))
		{
			unlink($tempFile);
		}
		
		if($tempFile && is_file($tempFile) && (filemtime($IMPORT_FILE) < filemtime($tempFile)))
		{
			$tempData = file_get_contents($tempFile);
			$entities = unserialize($tempData);
			unset($tempData);
		}
		else
		{
			$fileInfo = self::ParseFile(
				$IMPORT_FILE, 
				$planInfo["IMPORT_FILE_SHARSET"], 
				$planInfo["XML_ENTITY_GROUP"], 
				$planInfo["XML_ENTITY"], 
				"N", 
				$planInfo["XML_ENTITY_PARAM"],
				1
			);
			
			if(is_array($fileInfo) && count($fileInfo) > 0)
			{
				if(!extension_loaded('dom'))
				{
					$entities = array_slice($fileInfo["DATA"][$planInfo["XML_ENTITY"]], 0, 1);
				}
				else
				{
					if(is_array($fileInfo["DATA"]))
						$entities = array_slice($fileInfo["DATA"], 0, 1);
				}
				unset($fileInfo);
			}
			
			if($tempFile && !is_file($tempFile) && $entities)
				file_put_contents($tempFile, serialize($entities));
		}
		unset($planInfo);
		
		if(is_array($entities[0]))
		{
			$entities_keys = array_keys($entities[0]);
			$entities_attributes = false;
		
			foreach($entities[0] as $key => $attribute)
			{
				if(is_array($attribute))
					$entities_attributes[$key] = array_keys($attribute);
			}
		}
		
		$entities = Array(
			"KEYS" => $entities_keys, 
			"ATTRIBUTES" => $entities_attributes
		);
		
		unset(
			$entities_keys,
			$entities_attributes
		);
		
		return $entities;
	}
	
	public function isValidate()
	{
		return $this->isValidate;
	}
}