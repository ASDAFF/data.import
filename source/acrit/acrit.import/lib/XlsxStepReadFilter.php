<?php
namespace Acrit\Import;

class XlsxStepReadFilter implements \PhpOffice\PhpSpreadsheet\Reader\IReadFilter
{
	private $need_row_start;
	private $need_row_end;

	public function __construct($row_start, $row_end)
	{
		$this->need_row_start = $row_start;
		$this->need_row_end   = $row_end;
	}

	public function readCell($column, $row, $worksheetName = '')
	{
		if ($row >= $this->need_row_start && $row <= $this->need_row_end) {
			return true;
		}
		return false;
	}
}