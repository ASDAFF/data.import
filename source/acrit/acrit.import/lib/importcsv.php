<?php
namespace Acrit\Import;

class ImportCsv extends Import
{
	private $source_addr;
	private $delimiter;
	private $field_delimiter;

	private function setDefaults()
	{
		if (!$this->delimiter) {
			$this->delimiter = ';';
		}
		if (!$this->field_delimiter) {
			$this->field_delimiter = '';
		}
	}

	public function setSource()
	{
		parent::setSource();
		$this->source_addr = $this->arProfile['SOURCE'];
	}

	public function setDelimiter($value)
	{
		if ($value) {
			$this->delimiter = $value;
		}
	}

	public function setFieldDelimiter($value)
	{
		if ($value) {
			$this->field_delimiter = $value;
		}
	}

	// Additional settings of profile
	public function fieldsPreParams()
	{
		$arFieldsParams                           = [
			'title' => GetMessage('ACRIT_IMPORT_STEP2_SUBTIT0_CSV'),
		];
		$arFieldsParams['fields']['section']      = [
			'DB_FIELD' => 'DELIMITER',
			'TYPE' => 'string',
			'DEFAULT' => ';',
			'LABEL' => GetMessage('ACRIT_IMPORT_SOURCE_DELIMITER_LABEL'),
			'PLACEHOLDER' => '',
			'HINT' => '',
		];
		$arFieldsParams['fields']['first_titles'] = [
			'DB_FIELD' => 'PARAM_3',
			'TYPE' => 'boolean',
			'DEFAULT' => 'Y',
			'LABEL' => GetMessage("ACRIT_IMPORT_SOURCE_FIRST_TITLES_LABEL"),
			'PLACEHOLDER' => '',
			'HINT' => '',
		];
		$arFieldsParams['fields']['delimiter']    = [
			'DB_FIELD' => 'PARAM_1',
			'TYPE' => 'string',
			'DEFAULT' => '',
			'LABEL' => GetMessage('ACRIT_IMPORT_SOURCE_MULTIPLE_DELIMITER_LABEL'),
			'PLACEHOLDER' => '',
			'HINT' => '',
		];
		return $arFieldsParams;
	}

	public function fields()
	{
		$param_has_titles = $this->arProfile['SOURCE_PARAM_3'] != 'N' ? true : false;
		$arSourceFields   = [];
		$arRows           = $this->get(self::STEP_BY_COUNT, 2);
		$arRow            = $arRows[0];
		if (!empty($arRow)) {
			foreach ($arRow as $i => $value) {
				if ($value) {
					$example_row        = $param_has_titles ? 1 : 0;
					$arSourceFields[$i] = [
						'ID' => $i,
						'NAME' => $this->convStrEncoding($value),
						'EXAMPLE' => $this->convStrEncoding($arRows[$example_row][$i]),
					];
				}
			}
		}
		return $arSourceFields;
	}

	public function count()
	{
		$count            = 0;
		$param_has_titles = $this->arProfile['SOURCE_PARAM_3'] != 'N' ? true : false;
		$this->setDefaults();
		if ($this->source_addr) {
			$file = new \SplFileObject($this->source_addr);
			$file->setFlags(\SplFileObject::READ_CSV);
			$i = 0;
			while ($arRow = $file->fgetcsv($this->delimiter)) {
				if ($param_has_titles && $i == 0) {
					$i++;
					continue;
				}
				$arRow = array_filter($arRow);
				if (empty($arRow)) {
					$i++;
					continue;
				}
				$count++;
				$i++;
			}
		}
		return $count;
	}

	public function get($type = self::STEP_NO, $limit = 0, $last_item = 0)
	{
		$arRows = [];
		$this->setDefaults();
		$this->setDelimiter($this->arProfile['SOURCE_DELIMITER']);
		if ($type == self::STEP_BY_COUNT) {
			$item_start = $last_item;
			$item_end   = $last_item + $limit - 1;
		}
		if ($this->source_addr) {
			$file = new \SplFileObject($this->source_addr);
			$file->setFlags(\SplFileObject::READ_CSV);
			$i = 0;
			while ($arRow = $file->fgetcsv($this->delimiter)) {
				if ($type == self::STEP_BY_COUNT && ($i < $item_start || $i > $item_end)) {
					break;
				}
				$arRow = array_filter($arRow);
				if (empty($arRow)) {
					$i++;
					continue;
				}
				// Fill result array
				$arRows[] = $arRow;
				$i++;
			}
		}
		return $arRows;
	}

	public function import($type = self::STEP_NO, $limit = 0, $next_item = 0)
	{
		parent::onBeforeImport($type, $limit, $next_item);

		\CModule::IncludeModule('iblock');
		$item_start = 0;
		$item_end   = 0;
		$this->setDefaults();
		$this->setFieldDelimiter($this->arProfile['SOURCE_PARAM_1']);
		$param_has_titles = $this->arProfile['SOURCE_PARAM_3'] != 'N';
		if ($type == self::STEP_BY_COUNT) {
			$item_start = $next_item;
			$item_end   = $next_item + $limit - 1;
		} elseif ($type == self::STEP_BY_TYME) {
			$item_start = $next_item;
			$step_time  = $limit;
			$start_time = time();
		}
		if ($this->source_addr) {
			// Get data from source
			$file = new \SplFileObject($this->source_addr);
			$file->setFlags(\SplFileObject::READ_CSV);
			$i = 0;
			while ($arRow = $file->fgetcsv($this->delimiter)) {
				if (($type == self::STEP_BY_COUNT || $type == self::STEP_BY_TYME) && $i < $item_start) {
					$i++;
					continue;
				}
				if ($type == self::STEP_BY_COUNT) {
					if ($i > $item_end) {
						break;
					}
				} elseif ($type == self::STEP_BY_TYME) {
					$exec_time = time() - $start_time;
					if ($step_time && $exec_time > $step_time) {
						$item_end = $i;
						break;
					}
				}
				if ($param_has_titles && $i == 0) {
					$i++;
					continue;
				}
				foreach ($arRow as $key => $value) {
					$value = $this->convStrEncoding($value);
					if ($this->field_delimiter && strpos($value, $this->field_delimiter) !== false) {
						$value = explode($this->field_delimiter, $value);
					}
					$arRow[$key] = $value;
				}
				// Import data into IBlock
				$this->saveIBData($arRow, $i + 1);
				$i++;
			}
		}
		return ($item_end + 1);
	}
}
