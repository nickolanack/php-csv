# php-csv
Simple csv reader writer

Minimal csv reader with some useful functions


## Install

```

git clone https://github.com/nickolanack/php-csv.git
cd php-csv
composer install


```


## Usage

```php

	include_once __DIR__ . '/vendor/autoload.php';


	//create a new csv and get string output
   	$csvString = (new nblackwe\Csv())->create(array( 'label-one',  'label-two',  'label-three'))
    	->addRow(array('1','2','3'))
    	->addRow(array('4','5','6'))
   		->addRows(array(
	   		array( '7', '8','9'),
	   		array( '1', '2','3'),
	   		array( '4', '5','6')
	    ))
	    ->toString();


	//read from a string
	$csv=(new nblackwe\Csv())->fromString($csvString); 


	//write to a file
	$csv->toFile('/tmp/test.csv');


	//read from a file
	(new nblackwe\Csv())->fromFile('/tmp/test.csv')
		->countRows(); // should be 5 (not counting header)


	//distinct values in a column
	$csv->distinctValues('label-one'); // should be array('1', '4', '7');
	
	

	

	//other functions


	
	$csv->getRowsWithValue(array('label-one'=>'1', 'label-three'=>'3')); // returns array(0,3)
    $csv->getRowsWithValue(array('label-three'=>'9')); // returns array(2)




    	


	//read from a resource can be used to change character encoding etc

	ini_set('auto_detect_line_endings', true);

    $handle = fopen('/tmp/test.csv', 'r');

    if (!$handle) {
        throw new Exception('Invalid File, or Failed to read');
    }
    $csv=(new nblackwe\Csv())->fromResource($handle);
    fclose($handle);


```


