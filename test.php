<?php
include 'FDF.php';
$readonly= array();
$hidden= array();

$fields= array(
    'Shipper'       => 'John Smith c\o Jane',
    'PO#'          => '800) 555-1234',
    'FXF Priority'  => true
);
$fdf= new FDF($fields, $readonly, $hidden);
print((string)$fdf);
?>
