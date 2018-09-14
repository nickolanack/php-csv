<?php

class CsvTest extends PHPUnit_Framework_TestCase {

    /**
     * @runInSeparateProcess
     */
    public function testCreateCsv() {

        include_once dirname(__DIR__) . '/vendor/autoload.php';
        $csv = (new nblackwe\Csv())->create(array(
            'one',
            'two',
            'three'
        ));
        $csv->addRow(array('1', '2', '3' ))
            ->addRow(array('4', '5', '6' ))
            ->addRow(array('7', '8', '9' ))

            ->addRows(array(
               array( '1', '2','3'),
                array( '4', '5','6'),
                array( 'a', 'b','c')
            ));

        
        $this->assertEquals(6, $csv->countRows());
        $row = $csv->getRowAssoc(1);
        $this->assertEquals('5', $row['two']);
        $row = $csv->getRow(1);
        $this->assertEquals('5', $row[1]);
        
        $asserts = array();
        $csv->iterateRowsAssoc(
            function ($row_assoc, $i) use(&$asserts) {
                
                if ($i == 0) {
                    $asserts[] = array(
                        array(
                            'one' => '1',
                            'two' => '2',
                            'three' => '3'
                        ),
                        $row_assoc
                    );
                }
                if ($i == 1) {
                    $asserts[] = array(
                        array(
                            'one' => '4',
                            'two' => '5',
                            'three' => '6'
                        ),
                        $row_assoc
                    );
                }
                if ($i == 2) {
                    $asserts[] = array(
                        array(
                            'one' => '7',
                            'two' => '8',
                            'three' => '9'
                        ),
                        $row_assoc
                    );
                }
            });
        
        foreach ($asserts as $assert) {
            $this->assertEquals($assert[0], $assert[1]);
        }

        $this->assertEquals(array(1, 4), $csv->getRowsWithValue(array('4', '5', '6' )));
        $this->assertEquals(array(1, 4), $csv->getRowsWithValue(array('one'=>'4', 'three'=>'6' )));
        $this->assertEquals(array(0, 3), $csv->getRowsWithValue(array('1', '2' )));
        $this->assertEquals(array(5), $csv->getRowsWithValue(array('three'=>'c')));

        $this->assertFalse($csv->areAllValuesUnique('three'));
        $this->assertEquals(array('one', 'two', 'three'), $csv->getFieldNames());

        $csv->iterateRowsObject(function($array, $i){
            $this->assertTrue(is_object($array));
        });
        $csv->iterateRows(function($array, $i){
            $this->assertTrue(is_array($array));
        });

        $csv->toFile('/tmp/test.csv');


        $this->assertEquals(array('one', 'two', 'three'), (new nblackwe\Csv())->getFieldNames('/tmp/test.csv'));

        $csv=(new nblackwe\Csv())->fromFile('/tmp/test.csv');

        $this->assertEquals(6, $csv->countRows());
        $row = $csv->getRowAssoc(1);
        $this->assertEquals('5', $row['two']);
        $row = $csv->getRow(1);
        $this->assertEquals('5', $row[1]);

        $this->assertEquals(array('1', '4', '7', 'a'), $csv->distinctValues('one'));


        ini_set('auto_detect_line_endings', true);

        $handle = fopen('/tmp/test.csv', 'r');

        if (!$handle) {
            throw new Exception('Invalid File, or Failed to read');
        }
        $csv=(new nblackwe\Csv())->fromResource($handle);
        fclose($handle);


        $this->assertEquals(6, $csv->countRows());
        $row = $csv->getRowAssoc(1);
        $this->assertEquals('5', $row['two']);
        $row = $csv->getRow(1);
        $this->assertEquals('5', $row[1]);

        $this->assertEquals(array('1', '4', '7', 'a'), $csv->distinctValues('one'));

        $string=$csv->toString();


        $csv=(new nblackwe\Csv())->fromString($string);

        $this->assertEquals(6, $csv->countRows());
        $row = $csv->getRowAssoc(1);
        $this->assertEquals('5', $row['two']);
        $row = $csv->getRow(1);
        $this->assertEquals('5', $row[1]);

        $this->assertEquals(array('1', '4', '7', 'a'), $csv->distinctValues('one'));






        
        // file_put_contents(__DIR__.'/test.csv', EasyCsv::Write($csv));
    }
}