<?php
include_once 'includes/functions.php';

header('Content-Type: application/json');

$qty = isset($_GET['quantity']) ? $_GET['quantity'] : 0;
$paper = isset($_GET['paper']) ? $_GET['paper'] : '';

$price = calculatePrice($qty, $paper);

if ($price === false) {
    echo json_encode(array('ok' => false, 'message' => 'Bad quantity or paper type'));
    exit;
}

echo json_encode(array(
    'ok' => true,
    'price' => $price,
    'formatted' => money($price),
    'note' => (int)$qty . ' · ' . $paper
));
