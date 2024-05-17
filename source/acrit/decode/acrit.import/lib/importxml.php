<?php

namespace Acrit\Import;

use Bitrix\Main\Diag\Debug;
use XMLReader;

class ImportXml extends Import
{
	const KEY_XML_ASSOC_NODE_DELIMITER = '##';
	const KEY_XML_ASSOC_ATTR_DELIMITER = '%%';
	const ATTR_MODE_REPLACE = 0;
	const ATTR_MODE_ORIGINAL = 1;
	const LINK_MODE_ASSOC = 'assoc';
	const LINK_MODE_ORDER = 'order';

	private $source_addr;

	private function setDefaults()
	{
	}

	public function setSource()
	{
		parent::setSource();
		$this->source_addr = $this->arProfile['SOURCE'];
	}

	protected function getNodeDelimiter()
	{
		$link_mode = $this->getLinkMode();
		if ($link_mode == self::LINK_MODE_ASSOC) {
			$value = self::KEY_XML_ASSOC_NODE_DELIMITER;
		} else {
			$value = self::KEY_DELIMITER;
		}
		return $value;
	}

	protected function getAttrDelimiter()
	{
		$link_mode = $this->getLinkMode();
		if ($link_mode == self::LINK_MODE_ASSOC) {
			$value = self::KEY_XML_ASSOC_ATTR_DELIMITER;
		} else {
			$value = self::KEY_DELIMITER;
		}
		return $value;
	}

	protected function prepareNodeKey($value)
	{
		$link_mode = $this->getLinkMode();
		if ($link_mode == self::LINK_MODE_ASSOC) {
			$value = str_replace([self::KEY_XML_ASSOC_NODE_DELIMITER, self::KEY_XML_ASSOC_ATTR_DELIMITER], '__', $value);
		} else {
			$value = str_replace(self::KEY_DELIMITER, '__', $value);
		}
		return $value;
	}

	// Get nodes types of current level
	public function getSourceRoots()
	{
		$root_level = (int)$this->arProfile['SOURCE_ROOT_LEVEL'];
		$link_mode  = $this->getLinkMode();
		$arRows     = $this->get(self::STEP_BY_COUNT, 0, 0, $root_level, '', [], 1);
		$arList     = [];
		if (!empty($arRows)) {
			foreach ($arRows as $arRow) {
				$k  = key($arRow);
				$ar = explode($this->getNodeDelimiter(), $k);
				if ($link_mode == self::LINK_MODE_ASSOC) {
					$name = $ar[count($ar) - 1];
				} else {
					$name = $ar[count($ar) - 2];
				}
				$arList[$name] = $name;
			}
		}
		return $arList;
	}

	// Get variants of nodes attributes for params identification
	public function getIdentAttribsList()
	{
		$arList = [];

		// Root items
		$arSourceRoots = $this->getSourceRoots();
		// Item fields
		if ($this->arProfile['SOURCE_ROOT_ITEM'] && in_array($this->arProfile['SOURCE_ROOT_ITEM'], $arSourceRoots)) {
			$arRows = $this->get(self::STEP_BY_COUNT, 1, 0, false, false, [], 0, self::ATTR_MODE_ORIGINAL);
			$arRow  = $arRows[0];
			if (!empty($arRow)) {
				foreach ($arRow as $k => $value) {
					$ar = explode($this->getNodeDelimiter(), $k);
					//$name = $ar[count($ar) - 2];
					$key_base = '';
					for ($i = 0; $i < $this->arProfile['SOURCE_ROOT_LEVEL']; $i++) {
						$key_base .= $ar[$i] . $this->getNodeDelimiter();
					}
					$key_local = str_replace($key_base, '', $k);
					if (strpos($key_local, $this->getAttrDelimiter()) !== false) {
						$arList[] = $key_local;
					}
				}
			}
		}

		return $arList;
	}

	public function getLinkMode()
	{
		return $this->arProfile['SOURCE_PARAM_1'] ? $this->arProfile['SOURCE_PARAM_1'] : self::LINK_MODE_ORDER;
	}

	public function getIdentAttribs()
	{
		$arList = explode(',', $this->arProfile['SOURCE_PARAM_2']);
		return $arList;
	}

	public function getRootNode()
	{
		$root_node = $this->arProfile['SOURCE_ROOT_ITEM'];
		if ($root_node) {
			$arSourceRoots = $this->getSourceRoots();
			if (!isset($arSourceRoots[$root_node])) {
				$root_node = $arSourceRoots[key($arSourceRoots)];
			}
		}
		return $root_node;
	}

	// Additional settings of profile
	public function fieldsPreParams()
	{
		$arFieldsParams                         = [
			'title' => GetMessage('ACRIT_IMPORT_STEP2_SUBTIT0_XML'),
		];
		$arFieldsParams['fields']['root_level'] = [
			'DB_FIELD' => 'ROOT_LEVEL',
			'TYPE' => 'number',
			'DEFAULT' => '1',
			'LABEL' => GetMessage('ACRIT_IMPORT_SOURCE_ROOT_LEVEL_LABEL'),
			'PLACEHOLDER' => '',
			'HINT' => GetMessage('ACRIT_IMPORT_SOURCE_ROOT_LEVEL_HINT'),
		];
		$arSourceRoots                          = $this->getSourceRoots();
		if (!empty($arSourceRoots)) {
			$arFieldsParams['fields']['root_variants'] = [
				'DB_FIELD' => 'ROOT_ITEM',
				'TYPE' => 'list',
				'LABEL' => GetMessage("ACRIT_IMPORT_SOURCE_ROOT_ITEM_LABEL"),
				'HINT' => GetMessage('ACRIT_IMPORT_SOURCE_ROOT_ITEM_HINT'),
			];
			foreach ($arSourceRoots as $k => $value) {
				$arFieldsParams['fields']['root_variants']['LIST'][$k] = $value;
			}
		}
		$arFieldsParams['fields']['link_mode'] = [
			'DB_FIELD' => 'PARAM_1',
			'TYPE' => 'list',
			'LABEL' => GetMessage("ACRIT_IMPORT_SOURCE_XML_LINK_MODE_LABEL"),
			'LIST' => [
				self::LINK_MODE_ORDER => GetMessage("ACRIT_IMPORT_SOURCE_XML_LINK_MODE_ORDER"),
				self::LINK_MODE_ASSOC => GetMessage("ACRIT_IMPORT_SOURCE_XML_LINK_MODE_ASSOC"),
			],
			'DEFAULT' => self::LINK_MODE_ORDER,
			'HINT' => GetMessage('ACRIT_IMPORT_SOURCE_XML_LINK_MODE_HINT')
		];
		$link_mode                             = $this->getLinkMode();
		if ($link_mode == self::LINK_MODE_ASSOC) {
			$arIdentAttribs = $this->getIdentAttribsList();
			if (!empty($arIdentAttribs)) {
				$arFieldsParams['fields']['ident_attribs'] = [
					'DB_FIELD' => 'PARAM_2',
					'TYPE' => 'list_multiple',
					'LABEL' => GetMessage("ACRIT_IMPORT_SOURCE_IDENT_ATTRIBS_LABEL"),
					'HINT' => GetMessage('ACRIT_IMPORT_SOURCE_IDENT_ATTRIBS_HINT')
				];
				foreach ($arIdentAttribs as $value) {
					$name                                                      = $value;
					$name                                                      = str_replace([$this->getNodeDelimiter(), $this->getAttrDelimiter()], ' / ', $name);
					$arFieldsParams['fields']['ident_attribs']['LIST'][$value] = $name;
				}
			}
		}
		return $arFieldsParams;
	}

	public function fields()
	{
		$arSourceFields = [];
		$link_mode      = $this->getLinkMode();
		// Root item
		$root_node = $this->getRootNode();
		// Item fields
		if ($root_node) {

			// List of main fields
			static $arRow;
			if (empty($arRow)) {
				if ($this->getArProfile()['SCAN_ALL_ITEMS_TO_FIND_FIELDS'] == 'Y') {
					$arRow = [];
					$step = 500;
					for ($nextItem = 0, $nextItemCnt = ceil($this->count() / $step); $nextItem < $nextItemCnt; $nextItem += $step) {
						$arRows = $this->get(self::STEP_BY_COUNT, $step, $nextItem, $this->arProfile['SOURCE_ROOT_LEVEL'], $root_node);
						foreach ($arRows as $row) {
							$arRow += $row;
						}
					}
					ksort($arRow, SORT_NATURAL);
				} else {
					$arRows = $this->get(self::STEP_BY_COUNT, 1, 0, $this->arProfile['SOURCE_ROOT_LEVEL'], $root_node);
					$arRow  = $arRows[0];
				}
				unset($arRows, $row, $nextItem, $nextItemCnt);
			}

			if (!empty($arRow)) {
				foreach ($arRow as $k => $value) {
					$ar = explode($this->getNodeDelimiter(), $k);
					//$name = $ar[count($ar) - 2];
					$key_base = '';
					for ($i = 0; $i < $this->arProfile['SOURCE_ROOT_LEVEL']; $i++) {
						$key_base .= $ar[$i] . $this->getNodeDelimiter();
					}
					$name = str_replace($key_base, '', $k);
					$name = str_replace($this->getNodeDelimiter(), ' / ', $name);
					if ($link_mode == self::LINK_MODE_ASSOC) {
						$name = str_replace($this->getAttrDelimiter(), ' / ', $name);
					}
					$example = '';
					if (trim($value)) {
						$example = substr($value, 0, 10) . (strlen($value) > 10 ? '...' : '');
					}
					$arSourceFields[$k] = [
						'ID' => $k,
						'NAME' => $name,
						'EXAMPLE' => $example,
					];
				}
			}
		}
		return $arSourceFields;
	}

	public function find($callback = false, &$arCBParams = [], $type = self::STEP_NO, $limit = 0, $next_item = 0,
							$root_level = false, $root_node = false, $arRootPath = [], $level_limit = 0, $attr_mode = self::ATTR_MODE_REPLACE)
	{
		$item_start = 0;
		$item_end   = 0;
		$read_limit = 300;
		$this->setDefaults();
		if ($root_level === false) {
			$root_level = (int)$this->arProfile['SOURCE_ROOT_LEVEL'];
		}
		$root_level = $root_level > 0 ? $root_level - 1 : 0;
		if ($root_node === false) {
			$root_node = $this->getRootNode();
		}
		$level_limit = $level_limit >= 0 ? (int)$level_limit : 0;
		if ($type == self::STEP_BY_COUNT) {
			$item_start = $next_item;
			$item_end   = $next_item + $limit;
		} elseif ($type == self::STEP_BY_TYME) {
			$item_start = $next_item;
			$step_time  = $limit;
			$start_time = time();
		}
		$link_mode      = $this->getLinkMode();
		$arIdentAttribs = $this->getIdentAttribs();
		if ($this->source_addr) {
			// Fill result array
			libxml_use_internal_errors(true);
			$reader = new \XMLReader();
			$reader->open($this->source_addr);
			$arHierarchyCur = [];
			// Flag of start node parsing
			$item_open      = false;
			$arRow          = [];
			$node_i         = 0;
			$node_items_pos = 0;
			$last_key       = '';
			//$reader->setParserProperty(\XMLReader::VALIDATE, true);
			while ($reader->read()) {
				$cur_node  = $reader->name;
				$cur_level = $reader->depth;
				// Save value of last node key
				if (in_array($reader->nodeType, [\XMLReader::TEXT, \XMLReader::CDATA, \XMLReader::WHITESPACE, \XMLReader::SIGNIFICANT_WHITESPACE])) {
					if ($last_key) {
						$arRow[$last_key] = $this->convStrEncoding($reader->value, 'UTF-8');
						// Reset key
						$last_key = '';
					}
				}
				// Fill structure of xml
				if ($reader->nodeType != \XMLReader::ELEMENT && $reader->nodeType != \XMLReader::ATTRIBUTE) {
					continue;
				}
				if ($cur_level < $root_level) {
					$arHierarchyCur[$cur_level + 1] = $this->convStrEncoding($cur_node, 'UTF-8');
					continue;
				}
				// Start read of node
				if ($cur_level == $root_level) {
					// Reset limit for every root node
					$node_i = 0;
					// Step limit
					if ($type == self::STEP_BY_COUNT) {
						if ($limit && $node_items_pos >= $item_end) {
							break;
						}
					} elseif ($type == self::STEP_BY_TYME) {
						$exec_time = time() - $start_time;
						if ($step_time && $exec_time > $step_time) {
							$item_end = $node_items_pos;
							break;
						}
					}
					// Return items of last readed node
					if (!empty($arRow)) {
						if (is_callable($callback)) {
							call_user_func_array($callback, [$node_items_pos, $arRow, $arHierarchyCur, &$arCBParams]);
						}
						$arRow = [];
					}
					// Start read nodes list
					if (!$item_open) {
						if ($node_items_pos >= $item_start && (!$root_node || $root_node == $cur_node)) {
							$item_open = true;
							$last_node = $cur_node;
							$arRow     = [];
						}
					} // End read nodes list
					else {
						if ($root_node && $cur_node != $last_node) {
							$item_open = false;
						}
					}
					// Get number of current root node
					if (!$root_node || $root_node == $cur_node) {
						$node_items_pos++;
					}
				}
				// Parsing of child nodes and attributes
				if ($item_open && (!$level_limit || $cur_level < ($root_level + $level_limit))) {
					if ($node_i > $read_limit) {
						continue;
					}
					// Create new node key
					$arHierarchyCur[$cur_level + 1] = $this->convStrEncoding($cur_node, 'UTF-8');
					foreach ($arHierarchyCur as $j => $value) {
						if ($j > $cur_level + 1) {
							unset($arHierarchyCur[$j]);
						}
					}
					$last_key = implode($this->getNodeDelimiter(), $arHierarchyCur);
					// Assoc mode of linking
					if ($link_mode == self::LINK_MODE_ASSOC) {
						// Find values of attributes as a nodes
						$skip_a = -1;
						if ($attr_mode != self::ATTR_MODE_ORIGINAL) {
							if (!empty($arIdentAttribs)) {
								$a = 0;
								while ($reader->moveToAttributeNo($a)) {
									$last_key_attr = $last_key . $this->getAttrDelimiter() . $reader->name;
									foreach ($arIdentAttribs as $ident_variant) {
										if (strpos($last_key_attr, $ident_variant) !== false) {
											$last_key .= $this->getAttrDelimiter() . $this->prepareNodeKey($this->convStrEncoding($reader->value, 'UTF-8'));
											$skip_a   = $a;
											break;
										}
									}
									$a++;
								}
							}
						}
						// Add attributes to the list
						$arRow[$last_key] = '';
						$a                = 0;
						while ($reader->moveToAttributeNo($a)) {
							if ($a != $skip_a) {
								$new_key         = $last_key . $this->getAttrDelimiter() . $this->prepareNodeKey($reader->name);
								$arRow[$new_key] = $this->convStrEncoding($reader->value, 'UTF-8');
							}
							$a++;
						}
					} // Order mode of linking
					else {
						// Find values of attributes as a nodes
						$skip_a = -1;
						if ($attr_mode != self::ATTR_MODE_ORIGINAL) {
							if (!empty($arIdentAttribs)) {
								$a = 0;
								while ($reader->moveToAttributeNo($a)) {
									$last_key_attr = $last_key . $this->getAttrDelimiter() . $reader->name;
									foreach ($arIdentAttribs as $ident_variant) {
										if (strpos($last_key_attr, $ident_variant) !== false) {
											$last_key .= $this->getAttrDelimiter() . $this->prepareNodeKey($this->convStrEncoding($reader->value, 'UTF-8'));
											$skip_a   = $a;
											break;
										}
									}
									$a++;
								}
							}
						}
						// Numbering identical nodes
						$j = 1;
						while (isset($arRow[$last_key . $this->getNodeDelimiter() . $j])) {
							$j++;
						}
						$last_key         .= $this->getNodeDelimiter() . $j;
						$arRow[$last_key] = '';
						// Add attributes to the list
						$a = 0;
						while ($reader->moveToAttributeNo($a)) {
							if ($a != $skip_a) {
								$new_key         = $last_key . $this->getAttrDelimiter() . $this->prepareNodeKey($reader->name);
								$arRow[$new_key] = $this->convStrEncoding($reader->value, 'UTF-8');
							}
							$a++;
						}
					}
				}
				$node_i++;
			}
			// Callback for last item
			if (!empty($arRow)) {
				if (is_callable($callback)) {
					call_user_func_array($callback, [$node_items_pos, $arRow, $arHierarchyCur, &$arCBParams]);
				}
			}
			$reader->close();
			$this->initLog();
			$arError = array_filter((array)libxml_get_last_error());
			if (!empty($arError)) {
				$this->obLog->add(GetMessage("ACRIT_IMPORT_STROKA") . ' ' . $arError['line'] . ': ' . $arError['message'], \Acrit\Import\Log::TYPE_ERROR);
			}
		}
		return $item_end;
	}

	public function count()
	{
		$count = 0;
		$res   = $this->find(function ($i, $arRow, $arHierarchyCur, &$count) {
			$count++;
		}, $count, self::STEP_NO, 0, 0, false, false, [], 1);
		return $count;
	}

	public function get($type = self::STEP_NO, $limit = 0, $next_item = 0, $root_level = false, $root_node = false, $arRootPath = [], $level_limit = 0, $attr_mode = self::ATTR_MODE_REPLACE)
	{
		$arRows = [];
		$res    = $this->find(function ($i, $arRow, $arHierarchyCur, &$arRows) {
			$arRows[] = $arRow;
		}, $arRows, $type, $limit, $next_item, $root_level, $root_node, $arRootPath, $level_limit, $attr_mode);
		return $arRows;
	}

	public function import($type = self::STEP_NO, $limit = 0, $next_item = 0)
	{
		parent::onBeforeImport($type, $limit, $next_item);

		\CModule::IncludeModule('iblock');
		$root_level  = false;
		$root_node   = false;
		$arRootPath  = [];
		$level_limit = 0;
		$next_item   = $this->find(function ($i, $arRow, $arHierarchyCur, &$arRows) {
			$this->saveIBData($arRow, $i + 1);
		}, $arRows, $type, $limit, $next_item, $root_level, $root_node, $arRootPath, $level_limit);
		return $next_item;
	}

	// region api utils

	/**
	 * <code>
	 * foreach (SimpleXMLReader::readXmlFile('file.xml', 'item') as $item) {
	 *      echo $item->name . ": " . $item->price . "<br>";
	 * }
	 * </code>
	 *
	 * @param string $filePath
	 * @param string $needTagName
	 *
	 * @return \Generator
	 */
	public static function readXmlFile(string $filePath, string $needTagName): \Generator
	{
		$xml_reader = new XMLReader();
		$xml_reader->open($filePath);

		try {
			while ($xml_reader->read()) {
				if ($xml_reader->nodeType == XMLReader::ELEMENT && $xml_reader->name == $needTagName) {
					yield simplexml_load_string($xml_reader->readOuterXml());
				}
			}
		} finally {
			$xml_reader->close();
		}
	}

	/**
	 * <code>
	 * $reader = new \XMLReader();
	 * $reader->open('file.xml');
	 * SimpleXMLReader::readXmlFileCallback($xml, 'item', static function (array $item) {
	 *      // $item['tag']
	 *      // $item['childs']
	 *      // $item['attr']
	 *      // $item['text']
	 * });
	 * $reader->close();
	 * </code>
	 *
	 * @param \XMLReader $xml
	 * @param string     $needTagName
	 * @param callable   $callback
	 *
	 * @return array
	 */
	public static function readXmlCallback(XMLReader $xml, string $needTagName, callable $callback): array
	{
		$tree = [];

		while ($xml->read()) {
			if ($xml->nodeType == XMLReader::END_ELEMENT) {
				return $tree;
			} else if ($xml->nodeType == XMLReader::ELEMENT) {
				$node = [];

				$node['tag'] = $xml->name;

				if (!$xml->isEmptyElement) {
					$childs         = self::readXmlCallback($xml, $needTagName, $callback);
					$node['childs'] = $childs;
				}

				if ($xml->hasAttributes) {
					$attributes = [];
					while ($xml->moveToNextAttribute()) {
						$attributes[$xml->name] = $xml->value;
					}
					$node['attr'] = $attributes;
				}

				if ($node['tag'] == $needTagName) {
					$callback($node);
					$tree = [];     // drop find item recursive
				} else {
					$tree[] = $node;
				}
			} else if ($xml->nodeType == XMLReader::TEXT) {
				$node         = [];
				$node['text'] = $xml->value;
				$tree[]       = $node;
			}
		}

		return $tree;
	}

	// endregion
}