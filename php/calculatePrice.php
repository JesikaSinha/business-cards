<?php
include_once __DIR__ . '/../includes/functions.php';

// quick check from the command line: php php/calculatePrice.php
$tests = array(
    array(100, 'Matte', 20),
    array(100, 'glossy', 25),
    array(500, 'MATTE', 80),
    array(500, 'Glossy', 100),
    array(1000, 'Matte', 140),
    array(1000, 'Glossy', 180)
);

foreach ($tests as $t) {
    $got = calculatePrice($t[0], $t[1]);
    $pass = ($got == $t[2]) ? 'OK' : 'FAIL';
    echo $pass . "  calculatePrice(" . $t[0] . ", " . $t[1] . ") => " . $got . "\n";
}
