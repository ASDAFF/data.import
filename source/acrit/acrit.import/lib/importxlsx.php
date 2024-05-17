<?php
namespace Acrit\Import;

use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class ImportXlsx extends Import
{
	private $source_addr;
	private $colnums;
	private $rowcount;
	private $field_delimiter;

	function __construct($ID = 0)
	{
		parent::__construct($ID);

		if (\Bitrix\Main\Loader::includeModule('acrit.core')) {
			\Acrit\Core\Helper::includePhpSpreadSheet();
		} else {
			throw new \RuntimeException("Can't initialize PhpSpreadSheet lib in acrit.core module");
		}
	}

	private function setDefaults()
	{
		if (!$this->field_delimiter) {
			$this->field_delimiter = '|';
		}
	}

	public function setSource()
	{
		parent::setSource();
		$this->source_addr = $this->arProfile['SOURCE'];
	}

	public function setFieldDelimiter($value)
	{
		if ($value) {
			$this->field_delimiter = $value;
		}
	}

	public function fields()
	{
		$param_has_titles = $this->arProfile['SOURCE_PARAM_3'] != 'N' ? true : false;
		$arSourceFields   = array();
		$arRows           = $this->get(self::STEP_BY_COUNT, 2);
		$arRow            = $arRows[0];
		if (!empty($arRow)) {
			foreach ($arRow as $i => $value) {
				if ($value) {
					$example_row        = $param_has_titles ? 1 : 0;
					$arSourceFields[$i] = array(
						'ID' => $i,
						'NAME' => $this->convStrEncoding($value),
						'EXAMPLE' => $this->convStrEncoding($arRows[$example_row][$i]),
					);
				}
			}
		}
		return $arSourceFields;
	}

	public function count()
	{
		$this->setDefaults();
		$param_row_start  = (int)$this->arProfile['SOURCE_PARAM_1'];
		$param_row_start  = $param_row_start > 0 ? $param_row_start : 1;
		$param_has_titles = $this->arProfile['SOURCE_PARAM_3'] != 'N' ? true : false;
		if ($param_has_titles) {
			$param_row_start++;
		}
		$reader = new Xlsx();
		$reader->setReadDataOnly(true);
		$spreadsheet    = $reader->load($this->source_addr);
		$cells          = $spreadsheet->getActiveSheet()->getCellCollection();
		$this->rowcount = $cells->getHighestRow() - $param_row_start;
		return $this->rowcount;
	}

	public function get($type = self::STEP_NO, $limit = 0, $next_item = 0)
	{
		$row_start = (int)$this->arProfile['SOURCE_PARAM_1'];
		$row_start = $row_start > 0 ? $row_start : 1;
		$reader    = new Xlsx();
		$reader->setReadDataOnly(true);
		if ($limit > 0) {
			$filter = new XlsxStepReadFilter($row_start, $row_start + $limit - 1);
			$reader->setReadFilter($filter);
		}
		$spreadsheet  = $reader->load($this->source_addr);
		$cells_values = $spreadsheet->getActiveSheet()->toArray();
		return $cells_values;
	}

	public function import($type = self::STEP_NO, $limit = 0, $next_item = 0)
	{
		parent::onBeforeImport($type, $limit, $next_item);

		$item_start = 0;
		$item_end   = 0;
		$this->setDefaults();
		$this->setFieldDelimiter($this->arProfile['SOURCE_PARAM_2']);
		$param_has_titles = $this->arProfile['SOURCE_PARAM_3'] != 'N';
		$param_row_start  = (int)$this->arProfile['SOURCE_PARAM_1'];
		$param_row_start  = $param_row_start > 0 ? $param_row_start - 1 : 0;
		if ($param_has_titles) {
			$param_row_start++;
		}
		if ($type == self::STEP_BY_COUNT) {
			$item_start = $next_item;
			$item_end   = $next_item + $limit - 1;
		} elseif ($type == self::STEP_BY_TYME) {
			$item_start = $next_item;
			$step_time  = $limit;
			$start_time = time();
		}
		if ($item_start < $param_row_start) {
			$item_start = $param_row_start;
		}
		if ($this->source_addr) {
			$reader = new Xlsx();
			$reader->setReadDataOnly(true);

			//$filter = new XlsxStepReadFilter($item_start + 1, $item_end + 1);
			//$reader->setReadFilter($filter);

			$spreadsheet    = $reader->load($this->source_addr);
			$cells          = $spreadsheet->getActiveSheet()->getCellCollection();
			$this->rowcount = $cells->getHighestRow();
			$i              = $next_item;
			while ($i <= $this->rowcount - 1) {
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
				$arRow  = [];
				$filter = new XlsxStepReadFilter($i + 1, $i + 1);
				$reader->setReadFilter($filter);
				$spreadsheet = $reader->load($this->source_addr);
				$cell_values = $spreadsheet->getActiveSheet()->toArray();
				foreach ($cell_values[$i] as $value) {
					$value = $value == '#VALUE!' ? '' : $value;
					if ($this->field_delimiter && strpos($value, $this->field_delimiter) !== false) {
						$value = explode($this->field_delimiter, $value);
					}
					$arRow[] = $value;
				}
				$this->saveIBData($arRow, $i);
				$i++;
			}
		}
		return ($item_end + 1);
	}

	public function fieldsPreParams()
	{
		$arFieldsParams                           = [
			'title' => GetMessage("ACRIT_IMPORT_XLSX_PARAMS_TITLE"),
		];
		$arFieldsParams['fields']['section']      = [
			'DB_FIELD' => 'PARAM_1',
			'TYPE' => 'number',
			'DEFAULT' => '1',
			'LABEL' => GetMessage("ACRIT_IMPORT_XLSX_PARAM_1"),
			'PLACEHOLDER' => GetMessage("ACRIT_IMPORT_XLSX_PARAM_1_LABEL"),
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
			'DB_FIELD' => 'PARAM_2',
			'TYPE' => 'string',
			'DEFAULT' => '|',
			'LABEL' => GetMessage('ACRIT_IMPORT_SOURCE_MULTIPLE_DELIMITER_LABEL'),
			'PLACEHOLDER' => '',
			'HINT' => '',
		];
		return $arFieldsParams;
	}
}
