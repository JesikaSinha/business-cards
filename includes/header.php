<?php
if (!isset($page_title)) {
    $page_title = 'Business Cards';
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo e($page_title); ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>

<div class="site-header">
    <div class="wrap header-inner">
        <a class="logo" href="index.php">Pressroom</a>
        <div class="header-nav">
            <a href="index.php">Product</a>
            <a href="cart.php">Cart <span class="cart-count"><?php echo cart_count(); ?></span></a>
        </div>
    </div>
</div>

<div class="wrap">
