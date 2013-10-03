<?php
include 'FDF.php';
$readonly= array();
$hidden= array();

$fields= array(
    'Date' => date('Y-m-d'),
    'PO #' => 'Test PO'
);
$fdf= new FDF($fields, $readonly, $hidden);
print((string)$fdf);
?>
