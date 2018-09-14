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
        $csv->addRow(array(
            '1',
            '2',
            '3'
        ));
        $csv->addRow(array(
            '4',
            '5',
            '6'
        ));
       $csv->addRow(array(
            '7',
            '8',
            '9'
        ));
        
        $this->assertEquals(3, $csv->countRows());
        $row = $csv->getRowArray(1);
        $this->assertEquals('5', $row['two']);
        $row = $csv->getRow(1);
        $this->assertEquals('5', $row[1]);
        
        $asserts = array();
       $csv->iterateRowsArray(
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
        
        // file_put_contents(__DIR__.'/test.csv', EasyCsv::Write($csv));
    }
}