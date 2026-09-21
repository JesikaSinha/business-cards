<?php
include_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: cart.php');
    exit;
}

$i = isset($_POST['index']) ? (int)$_POST['index'] : -1;
$cart = get_cart();

if (isset($cart[$i])) {
    unset($cart[$i]);
    $_SESSION['cart'] = array_values($cart);
}

set_msg('Removed.');
header('Location: cart.php');
exit;
