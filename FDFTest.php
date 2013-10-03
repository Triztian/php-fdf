<?php
ini_set('include_path', '/usr/local/lib/php/PEAR');
spl_autoload_register(function($class) {
    $includePath= getcwd() . '/' . $class . '.php';
    
    print('Including: ' . $includePath . "\n");
    print($class . '->' . $includePath . "\n");
    include $includePath;
});

use PHPUnit_Framework_TestCase;
use FDF;

class FDFTest extends PHPUnit_Framework_TestCase
{
    public function testContents() {
        $fields= array();
        $readonly= array();
        $hidden= array();

        $fdf= new FDF($data, $fields, $hidden);
        print((string)$fdf);
    }
}
