<?php
include_once 'includes/functions.php';

$items = get_cart();
$msg = take_msg();
$page_title = 'Cart';
include 'includes/header.php';
?>

<div class="cart-page">
    <p class="eyebrow">Cart</p>
    <h1>Your cart</h1>

    <?php if ($msg) { ?>
        <p class="flash <?php echo $msg['ok'] ? 'flash-ok' : 'flash-bad'; ?>"><?php echo e($msg['text']); ?></p>
    <?php } ?>

    <?php if (count($items) == 0) { ?>
        <p class="lede">Nothing in the cart yet.</p>
        <p><a class="btn-cart" href="index.php">Back to product</a></p>
    <?php } else { ?>
        <ul class="cart-page-list">
        <?php foreach ($items as $i => $item) { ?>
            <li>
                <div>
                    <h2><?php echo e($item['name']); ?></h2>
                    <p><?php echo e($item['size']); ?> · <?php echo e($item['paper']); ?> · Qty <?php echo (int)$item['quantity']; ?></p>
                    <p>Artwork: <?php echo e($item['artworkName']); ?></p>
                </div>
                <div class="cart-line-right">
                    <strong><?php echo money($item['price']); ?></strong>
                    <form action="remove-from-cart.php" method="post">
                        <input type="hidden" name="index" value="<?php echo (int)$i; ?>">
                        <button type="submit" class="btn-remove">Remove</button>
                    </form>
                </div>
            </li>
        <?php } ?>
        </ul>

        <div class="cart-page-total">
            <span>Total</span>
            <strong><?php echo money(cart_total()); ?></strong>
        </div>
        <p><a href="index.php">Add another</a></p>
    <?php } ?>
</div>

<?php include 'includes/footer.php'; ?>
