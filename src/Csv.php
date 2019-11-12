<?php

namespace nblackwe;

class Csv {

	protected $csv;

	public function fromFile($filename, $options=array()) {

		$restore = ini_get('auto_detect_line_endings');
		ini_set('auto_detect_line_endings', true);

		$handle = fopen($filename, 'r');

		if (!$handle) {
			throw new \Exception('Invalid File, or Failed to read');
		}
		$this->fromResource($handle, $options);

		fclose($handle);
		ini_set('auto_detect_line_endings', $restore);

	
		return $this;
	}

	public  function fromString($text, $options=array()) {

		$restore = ini_get('auto_detect_line_endings');
		ini_set('auto_detect_line_endings', true);

		$handle = fopen('data://text/plain,' . $text, 'r');
		$this->fromResource($handle, $options);
		fclose($handle);
		ini_set('auto_detect_line_endings', $restore);

	
		return $this;
	}

	public function fromResource($handle,$options=array()) {
		$default = array(
			'hasHeader' => true,
			'maxReadlines'=>-1, //read all
			'length' => 0,
			'countOnly'=>false,
			'startAt'=>0,
			'number'=>-1 //fill all
		);
		// , 'encoding'=>'UTF-8'

		$csv = array_merge(array(
			'rows' => array(),
		), $default, array_intersect_key($options, $default));

		if (key_exists('hasHeader', $csv) && $csv['hasHeader']) {
			$csv['header'] = array();
		}

		// stream_encoding($handle, $csv['encoding']);

		$lineCount = 0;
		while (($data = fgetcsv($handle, 0, ',')) !== false) {
			if (key_exists('header', $csv) && count($csv['header']) == 0) {
				$lineCount++;
				$csv['header'] = $data;
				if ($csv['length'] == 0) {
					$csv['length'] = count($data);
				}

				continue;

			}

			

			$this->validateRow($data, $csv['length']); 
			

			$lineCount++;
			if(key_exists('countOnly', $csv)&&$csv['countOnly']===true){
				$csv['rows'][]=null;
				continue;
			}

			if(key_exists('startAt', $csv)&&$csv['startAt']>=$lineCount){
				$csv['rows'][]=null;
				continue;
			}

			if(key_exists('number', $csv)&&$csv['number']>0){

				if(key_exists('startAt', $csv)&&$csv['startAt']+$csv['number']<$lineCount-1){
					$csv['rows'][]=null;
					continue;
				}

	
			}

			$csv['rows'][] = $data;
			
			
			if(key_exists('maxReadlines', $csv)&&$csv['maxReadlines']>=1){
				if($lineCount>=$csv['maxReadlines']){
					break;
				}
			}
			
		}

		if ($lineCount <= 0) {
			throw new \Exception('0 rows in document');
		}

		$this->csv = $csv;
		return $this;
	}

	private function validateRow($data, $len){
		$count = count($data);
		if ($count > $len && $len > 0) {
			throw new \Exception(
				'CSV file contians longer than expected: (' . $len . ':' . $count . ')');
		}

	}

	public function create($header = null) {
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

	public function addRows($rows) {
		foreach($rows as $row){
			$this->csv['rows'][] = $row;
		}
		return $this;
	}

	public function toFile($filename) {
		file_put_contents($filename, $this->toString());
		return $this;
	}
	public function toString() {
		// Generate CSV data from array
		$resource = fopen('php://temp', 'rw'); // don't create a file, attempt
		// to use memory instead
		fputcsv($resource, $this->csv['header']);
		foreach ($this->csv['rows'] as $row) {
			fputcsv($resource, $row);
		}
		rewind($resource);
		$csv = stream_get_contents($resource);
		fclose($resource);

		return $csv;
	}

	public function getFieldNames($file=null) {

		
		if (file_exists($file) && is_file($file)) {

			$restore = ini_get('auto_detect_line_endings');
			ini_set('auto_detect_line_endings', TRUE);
			$handle = fopen($file, "r");
			ini_set('auto_detect_line_endings', $restore);

			if ($handle !== false) {
				$data = fgetcsv($handle, 0, ",");
				fclose($handle);
				if ($data !== false) {
					return $data;
				}
				throw new \Exception('0 rows in document');
			} 
				
			throw new \Exception('Invalid File, or Failed to read');
			
		}		


		$csv = $this->csv;

		if (is_array($csv)) {

			if (key_exists('header', $csv)) {
				return $csv['header'];
			}

			throw new \Exception('Failed to find $field[\'header\']: ' . print_r(array_keys($csv), true));
		} 

		throw new \Exception('Failed to find $field[\'header\']: ' . print_r(array_keys($csv), true));

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

	private function _pad($row, $header) {
		while (count($row) < count($header)) {
			$row[] = null;
		}
		return $row;
	}

	public function columnIndexOf($fieldName) {
		foreach ($this->csv['header'] as $i => $field) {
			if ($field == $fieldName) {
				return $i;
			}
		}
		return -1;
	}

	/**
	 *
	 * @param int $index
	 *            row index
	 * @param array $fieldNames
	 *            ordered array of names to accociate cell values (optional will use csv)
	 * @return array an string indexed array
	 */
	public function getRowAssoc($index, $fieldNames = null) {
		if (!$fieldNames) {
			$fieldNames = $this->csv['header'];
		}

		return $this->_combine($fieldNames, $this->getRow($index));
	}
	public function getRowObject($index, $fieldNames = null) {
		return (object) $this->getRowAssoc($index, $fieldNames);
	}

	private  function _combine($header, $row) {
		while (count($row) < count($header)) {
			$row[] = null;
		}
		if (count($header) != count($row)) {
			throw new \Exception(
				'Expected number of fields to match header[' . implode(',', $header) . ']:' . count($header) . ' row@?[' .
				implode(',', $row) . ']:' . count($row));
		}
		return array_combine($header, $row);
	}




	public function getRowsWithValue($rowLikeValue) {
		

		//if (!$fieldNames) {
			$fieldNames = $this->csv['header'];
		//}

		$keys = array();

		if ((!key_exists('rows', $this->csv)) || (!is_array($this->csv['rows']))) {
			throw new \Exception('Invalid $csv[\'rows\']');
		}

		foreach ($this->csv['rows'] as $index => $row) {
			$row_a = $row; //$this->_combine($fieldNames, $row);
			$true = true;
			foreach ($rowLikeValue as $k => $v) {

				if(in_array($k, $fieldNames)){
					$k=array_search($k,  $fieldNames);
				}

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

	public function iterateRows($callback, $options=array()) {


		$i=0;
		$num=$this->countRows();

		if(key_exists('startAt', $options)){
			$i=$options['startAt'];
		}

		if(key_exists('number', $options)){
			$num=min($num, $i+$options['number']);
		}

		for ($i; $i < $num; $i++) {

			$continue = $callback($this->getRow($i), $i);
			if ($continue === false) {
				break;
			}
		}

		return $this;
	}

	public function iterateRowsAssoc($callback, $options) {

		$i=0;
		$num=$this->countRows();

		if(key_exists('startAt', $options)){
			$i=$options['startAt'];
		}

		if(key_exists('number', $options)){
			$num=min($num, $i+$options['number']);
		}

		for ($i; $i < $num; $i++) {

			$continue = $callback($this->getRowAssoc($i), $i);
			if ($continue === false) {
				break;
			}

		}
		return $this;
	}
	public function iterateRowsObject($callback) {
		return $this->iterateRowsAssoc(function($v, $i)use($callback){
			return $callback((object) $v, $i);
		});
	}

	/**
	 *
	 * @param string $fieldName
	 * @throws Exception on invalid field
	 * @return array of distinct values
	 */
	public function distinctValues($fieldName) {
		$values = array();
		$index = array_search($fieldName, $this->getHeader());
		if ($index === false) {
			throw new \Exception('Invalid Field: ' . $fieldName);
		}
		$this->iterateRows(
			function ($row) use (&$values, $index) {

				if (!in_array($row[$index], $values)) {
					$values[] = $row[$index];
				}
			});

		return $values;
	}

	/**
	 *
	 * @param string $fieldName
	 * @throws Exception on invalid field
	 * @return boolean true if all values in column are unique;
	 */
	public function areAllValuesUnique($fieldName) {
		$values = array();
		$index = array_search($fieldName,  $this->getHeader());
		if ($index === false) {
			throw new \Exception('Invalid Field: ' . $fieldName);
		}
		$result = true;
		$this->iterateRows(
			function ($row) use (&$values, $index, &$result) {

				if (!in_array($row[$index], $values)) {
					$values[] = $row[$index];
					return;
				}
					
				$result = false;
				
			});

		return $result;
	}

}