<?php

namespace nblackwe;

class Csv {

	protected $csv;

	public function fromFile($filename, $options = array()) {

		$bac_ = ini_get('auto_detect_line_endings');
		ini_set('auto_detect_line_endings', true);

		$handle = fopen($filename, 'r');

		if (!$handle) {
			throw new Exception('Invalid File, or Failed to read');
		}
		$csv = $this->fromResource($handle);
		fclose($handle);
		ini_set('auto_detect_line_endings', $bac_);

		$this->csv = $csv;
		return $this;
	}

	public  function fromString($text, $options = array()) {

		$bac_ = ini_get('auto_detect_line_endings');
		ini_set('auto_detect_line_endings', true);

		$handle = fopen('data://text/plain,' . $text, 'r');
		$csv = $this->fromResource($handle);
		fclose($handle);
		ini_set('auto_detect_line_endings', $bac_);

		$this->csv = $csv;
		return $this;
	}

	public function fromResource($handle, $options = array()) {
		$default = array(
			'hasHeader' => true,
			'length' => 0,
		);
		// , 'encoding'=>'UTF-8'

		$csv = array_merge(array(
			'rows' => array(),
		), $default, array_intersect_key($options, $default));

		if (key_exists('hasHeader', $csv) && $csv['hasHeader']) {
			$csv['header'] = array();
		}

		// stream_encoding($handle, $csv['encoding']);

		$c = 0;
		while (($data = fgetcsv($handle, 0, ',')) !== false) {
			if (key_exists('header', $csv) && count($csv['header']) == 0) {
				$c++;
				$csv['header'] = $data;
				if ($csv['length'] == 0) {
					$csv['length'] = count($data);
				}
			} else {
				$c++;
				$csv['rows'][] = $data;
				$count = count($data);
				if ($count > $csv['length'] && $csv['length'] > 0) {
					throw new Exception(
						'CSV file contians longer than expected: (' . $csv['length'] . ':' . $count . ')');
				}
			}
		}

		if ($c <= 0) {
			throw new Exception('0 rows in document');
		}

		$this->csv = $csv;
		return $this;
	}

	public function create($header = false) {
		$csv = array(
			'rows' => array(),
		);
		if ($header) {
			$csv['header'] = $header;
		}

		$this->csv = $csv;
		return $this;
	}

	public function addRow($row) {
		$this->csv['rows'][] = $row;
		return $this;
	}

	public function toString() {
		// Generate CSV data from array
		$fh = fopen('php://temp', 'rw'); // don't create a file, attempt
		// to use memory instead
		fputcsv($fh, $this->csv['header']);
		foreach ($this->csv['rows'] as $row) {
			fputcsv($fh, $row);
		}
		rewind($fh);
		$csv = stream_get_contents($fh);
		fclose($fh);

		return $csv;
	}

	public function getFieldNames($file=null) {

		
		if (file_exists($file) && is_file($file)) {

			ini_set('auto_detect_line_endings', TRUE);
			$handle = fopen($file, "r");
			ini_set('auto_detect_line_endings', $bac_);

			if ($handle !== false) {
				$data = fgetcsv($handle, 0, ",");
				fclose($handle);
				if ($data !== false) {
					return $data;
				}
				throw new Exception('0 rows in document');
			} 
				
			throw new Exception('Invalid File, or Failed to read');
			
		}		


		$csv = $this->csv;

		if (is_array($csv)) {

			if (key_exists('header', $csv)) {
				return $csv['header'];
			}

			throw new Exception('Failed to find $field[\'header\']: ' . print_r(array_keys($csv), true));
		} 

		throw new Exception('Failed to find $field[\'header\']: ' . print_r(array_keys($csv), true));

	}

	public function countRows() {
		return count($this->csv['rows']);
	}

	public function countColumns() {
		if ($this->csv['length'] > 0) {
			return $this->csv['length'];
		}
		return (count($this->csv['rows']) > 0 ? count($this->csv['rows'][0]) : 0);
	}

	public function getHeader() {
		return $this->csv['header'];
	}

	/**
	 *
	 * @param array $csv
	 *            as returned by OpenCSV or CreateCSV
	 * @param int $index
	 *            row number starting at 0, not including header if it exists
	 * @return NULL
	 */
	public function getRow($index) {
		$row = $this->csv['rows'][$index];
		return $this->_pad($row, $this->csv['header']);
	}

	private static function _pad($row, $header) {
		while (count($row) < count($header)) {
			$row[] = null;
		}
		return $row;
	}

	public static function columnIndexOf($fieldName) {
		foreach ($this->csv['header'] as $i => $field) {
			if ($field == $fieldName) {
				return $i;
			}
		}
		return -1;
	}

	/**
	 *
	 * @param array $csv
	 *            csv object
	 * @param int $index
	 *            row index
	 * @param array $fieldNames
	 *            ordered array of names to accociate cell values (optional will use csv)
	 * @return array an string indexed array
	 */
	public function getRowArray($index, $fieldNames = false) {
		if (!$fieldNames) {
			$fieldNames = $this->csv['header'];
		}

		return $this->_combine($fieldNames, $this->getRow($index));
	}
	public function getRowObject($index, $fieldNames = false) {
		return (object) $this->getRowArray($index, $fieldNames);
	}

	private  function _combine($header, $row) {
		while (count($row) < count($header)) {
			$row[] = null;
		}
		if (count($header) != count($row)) {
			throw new Exception(
				'Expected number of fields to match header[' . implode(',', $header) . ']:' . count($header) . ' row@?[' .
				implode(',', $row) . ']:' . count($row));
		}
		return array_combine($header, $row);
	}

	public static function getMatchingRowKeys($match, $fieldNames = false) {
		if (!$fieldNames) {
			$fieldNames = $this->csv['header'];
		}

		$keys = array();

		if ((!key_exists('rows', $this->csv)) || (!is_array($this->csv['rows']))) {
			throw new Exception('Invalid $csv[\'rows\']');
		}

		foreach ($this->csv['rows'] as $index => $row) {
			$row_a = $this->_combine($fieldNames, $row);
			$true = true;
			foreach ($match as $k => $v) {
				if ($row_a[$k] != $v) {
					$true = false;
					break;
				}
			}
			if ($true) {
				$keys[] = $index;
			}

		}

		return $keys;
	}

	public function iterateRows($callback) {
		for ($i = 0; $i < $this->countRows(); $i++) {

			$continue = $callback($this->getRow($i), $i);
			if ($continue === false) {
				break;
			}

		}
	}

	public function iterateRowsArray($callback) {
		for ($i = 0; $i < $this->countRows(); $i++) {

			$continue = $callback($this->getRowArray($i), $i);
			if ($continue === false) {
				break;
			}

		}
	}
	public function iterateRowsObject($callback) {
		for ($i = 0; $i < $this->countRows(); $i++) {

			$continue = $callback($this->getRowObject($i), $i);
			if ($continue === false) {
				break;
			}

		}
	}

	/**
	 *
	 * @param array $csv
	 *            an array created by EasyCsv::OpenCsv or EasyCsv:CreateCsv
	 * @param string $fieldName
	 * @throws Exception on invalid field
	 * @return array of distinct values
	 */
	public function distinctValues($fieldName) {
		$values = array();
		$i = array_search($fieldName, $this->getHeader());
		if ($i === false) {
			throw new Exception('Invalid Field: ' . $fieldName);
		}
		$this->iterateRows(
			function ($row) use (&$values, $i) {

				if (!in_array($row[$i], $values)) {
					$values[] = $row[$i];
				}
			});

		return $values;
	}

	/**
	 *
	 * @param array $csv
	 *            an array created by EasyCsv::OpenCsv or EasyCsv:CreateCsv
	 * @param string $fieldName
	 * @throws Exception on invalid field
	 * @return boolean true if all values in column are unique;
	 */
	public function AreAllValuesUnique($fieldName) {
		$values = array();
		$i = array_search($fieldName,  $this->getHeader());
		if ($i === false) {
			throw new Exception('Invalid Field: ' . $fieldName);
		}
		$result = true;
		$this->iterateRows(
			function ($row) use (&$values, $i, &$result) {

				if (!in_array($row[$i], $values)) {
					$values[] = $row[$i];
				} else {
					$result = false;
				}
			});

		return $result;
	}

}