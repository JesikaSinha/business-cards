<?php
include_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: index.php');
    exit;
}

$size = isset($_POST['size']) ? $_POST['size'] : '';
$paper = isset($_POST['paper']) ? $_POST['paper'] : '';
$qty = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;

$error = '';

if ($size != 'Standard' && $size != 'Square') {
    $error = 'Pick a size.';
}

$price = calculatePrice($qty, $paper);
if ($price === false) {
    $error = 'That quantity / paper combo is not in the price table.';
}

$samples = array(
    'classic' => 'Sam Lee sample — Classic',
    'navy' => 'Sam Lee sample — Navy',
    'minimal' => 'Sam Lee sample — Minimal',
    'stripe' => 'Sam Lee sample — Gold stripe'
);
$sample = isset($_POST['sample']) ? $_POST['sample'] : 'classic';
if (!isset($samples[$sample])) {
    $sample = 'classic';
}

$has_file = isset($_FILES['artwork']) && $_FILES['artwork']['error'] != UPLOAD_ERR_NO_FILE;

if ($error == '') {
    if (!$has_file) {
        $orig = $samples[$sample];
        $saved = 'sample-' . $sample;
    } else {
        $file = $_FILES['artwork'];

        if ($file['error'] != UPLOAD_ERR_OK) {
            $error = 'Upload failed, try again.';
        } else {
            $orig = $file['name'];
            $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
            $ok_ext = array('pdf', 'png', 'jpg', 'jpeg', 'ai');

            if (!in_array($ext, $ok_ext)) {
                $error = 'File should be PDF, PNG, JPG or AI.';
            } elseif ($file['size'] > 25 * 1024 * 1024) {
                $error = 'File is too big (max 25MB).';
            }
        }

        if ($error == '') {
            $dir = __DIR__ . '/uploads';
            if (!is_dir($dir)) {
                mkdir($dir, 0777);
            }

            if (!is_writable($dir)) {
                $error = 'Cannot write to uploads/. Try chmod 777 uploads';
            } else {
                $saved = uniqid('art_') . '.' . $ext;
                if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $saved)) {
                    $error = 'Could not save the file.';
                }
            }
        }
    }
}

if ($error != '') {
    set_msg($error, false);
    header('Location: index.php');
    exit;
}

$_SESSION['cart'] = get_cart();
$_SESSION['cart'][] = array(
    'name' => 'Custom Business Cards',
    'size' => $size,
    'paper' => $paper,
    'quantity' => $qty,
    'price' => $price,
    'artworkName' => $orig,
    'artworkFile' => $saved,
    'sample' => $sample
);

set_msg('Added to cart.');
header('Location: cart.php');
exit;
