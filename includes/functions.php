<?php
if (!session_id()) {
    session_start();
}

// prices from the brief. size is ignored on purpose
function calculatePrice($quantity, $paperType) {
    $prices = array(
        100 => array('matte' => 20, 'glossy' => 25),
        500 => array('matte' => 80, 'glossy' => 100),
        1000 => array('matte' => 140, 'glossy' => 180)
    );

    $quantity = (int)$quantity;
    $paperType = strtolower(trim($paperType));

    if (isset($prices[$quantity][$paperType])) {
        return $prices[$quantity][$paperType];
    }

    return false;
}

function money($n) {
    return '$' . number_format($n, 2);
}

function e($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function get_cart() {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = array();
    }
    return $_SESSION['cart'];
}

function cart_count() {
    return count(get_cart());
}

function cart_total() {
    $total = 0;
    foreach (get_cart() as $row) {
        $total += $row['price'];
    }
    return $total;
}

function set_msg($text, $ok = true) {
    $_SESSION['msg'] = $text;
    $_SESSION['msg_ok'] = $ok;
}

function take_msg() {
    if (!isset($_SESSION['msg'])) {
        return null;
    }
    $out = array(
        'text' => $_SESSION['msg'],
        'ok' => !empty($_SESSION['msg_ok'])
    );
    unset($_SESSION['msg'], $_SESSION['msg_ok']);
    return $out;
}
