<?php
include_once 'includes/functions.php';

$size = 'Standard';
$paper = 'Matte';
$qty = 100;

if (isset($_GET['size']) && ($_GET['size'] == 'Standard' || $_GET['size'] == 'Square')) {
    $size = $_GET['size'];
}
if (isset($_GET['paper']) && ($_GET['paper'] == 'Matte' || $_GET['paper'] == 'Glossy')) {
    $paper = $_GET['paper'];
}
if (isset($_GET['quantity'])) {
    $q = (int)$_GET['quantity'];
    if ($q == 100 || $q == 500 || $q == 1000) {
        $qty = $q;
    }
}

$price = calculatePrice($qty, $paper);
$msg = take_msg();

$page_title = 'Custom Business Cards';
$load_js = true;
include 'includes/header.php';

$card_class = 'card-preview';
$card_class .= ($size == 'Square') ? ' size-square' : ' size-standard';
$card_class .= ($paper == 'Glossy') ? ' finish-glossy' : ' finish-matte';

$size_text = ($size == 'Square') ? 'Square · 2.5" × 2.5"' : 'Standard · 3.5" × 2"';
?>

<div class="product">
<div class="product-grid">

<div class="gallery">
    <div class="preview-stage">
        <div class="<?php echo $card_class; ?> sample-classic" id="card-preview">
            <div class="card-face">
                <p class="card-kicker">Sam Lee</p>
                <p class="card-role">Owner</p>
                <p class="card-meta">sam@email.com · 555-0199</p>
            </div>
        </div>
    </div>
    <p class="preview-caption">
        <span id="preview-size-label"><?php echo e($size_text); ?></span>
        ·
        <span id="preview-paper-label"><?php echo e($paper); ?></span>
        ·
        <span id="preview-sample-label">Classic</span>
    </p>

    <p class="sample-heading">Sample designs</p>
    <div class="sample-row" id="sample-row">
        <label class="sample-pick">
            <input type="radio" name="sample" value="classic" checked form="product-form">
            <span class="mini-card sample-classic">
                <b>Sam Lee</b>
                <small>Classic</small>
            </span>
        </label>
        <label class="sample-pick">
            <input type="radio" name="sample" value="navy" form="product-form">
            <span class="mini-card sample-navy">
                <b>Sam Lee</b>
                <small>Navy</small>
            </span>
        </label>
        <label class="sample-pick">
            <input type="radio" name="sample" value="minimal" form="product-form">
            <span class="mini-card sample-minimal">
                <b>Sam Lee</b>
                <small>Minimal</small>
            </span>
        </label>
        <label class="sample-pick">
            <input type="radio" name="sample" value="stripe" form="product-form">
            <span class="mini-card sample-stripe">
                <b>Sam Lee</b>
                <small>Gold stripe</small>
            </span>
        </label>
    </div>
</div>

<div class="configurator">
    <p class="eyebrow">Business cards</p>
    <h1>Custom Business Cards</h1>
    <p class="lede">Choose size, paper and quantity, then upload your artwork. Price changes when paper or qty changes.</p>

    <?php if ($msg) { ?>
        <p class="flash <?php echo $msg['ok'] ? 'flash-ok' : 'flash-bad'; ?>"><?php echo e($msg['text']); ?></p>
    <?php } ?>

    <form id="product-form" action="add-to-cart.php" method="post" enctype="multipart/form-data">

        <fieldset>
            <legend>Size</legend>
            <div class="option-row">
                <label class="chip">
                    <input type="radio" name="size" value="Standard" <?php if ($size == 'Standard') echo 'checked'; ?>>
                    <span><strong>Standard</strong><small>3.5" × 2"</small></span>
                </label>
                <label class="chip">
                    <input type="radio" name="size" value="Square" <?php if ($size == 'Square') echo 'checked'; ?>>
                    <span><strong>Square</strong><small>2.5" × 2.5"</small></span>
                </label>
            </div>
        </fieldset>

        <fieldset>
            <legend>Paper type</legend>
            <div class="option-row">
                <label class="chip">
                    <input type="radio" name="paper" value="Matte" <?php if ($paper == 'Matte') echo 'checked'; ?>>
                    <span><strong>Matte</strong><small id="paper-price-matte"><?php echo money(calculatePrice($qty, 'Matte')); ?></small></span>
                </label>
                <label class="chip">
                    <input type="radio" name="paper" value="Glossy" <?php if ($paper == 'Glossy') echo 'checked'; ?>>
                    <span><strong>Glossy</strong><small id="paper-price-glossy"><?php echo money(calculatePrice($qty, 'Glossy')); ?></small></span>
                </label>
            </div>
        </fieldset>

        <fieldset>
            <legend>Quantity</legend>
            <div class="option-row">
                <label class="chip">
                    <input type="radio" name="quantity" value="100" <?php if ($qty == 100) echo 'checked'; ?>>
                    <span>
                        <strong>100</strong>
                        <small>Matte <?php echo money(calculatePrice(100, 'Matte')); ?> · Glossy <?php echo money(calculatePrice(100, 'Glossy')); ?></small>
                    </span>
                </label>
                <label class="chip">
                    <input type="radio" name="quantity" value="500" <?php if ($qty == 500) echo 'checked'; ?>>
                    <span>
                        <strong>500</strong>
                        <small>Matte <?php echo money(calculatePrice(500, 'Matte')); ?> · Glossy <?php echo money(calculatePrice(500, 'Glossy')); ?></small>
                    </span>
                </label>
                <label class="chip">
                    <input type="radio" name="quantity" value="1000" <?php if ($qty == 1000) echo 'checked'; ?>>
                    <span>
                        <strong>1000</strong>
                        <small>Matte <?php echo money(calculatePrice(1000, 'Matte')); ?> · Glossy <?php echo money(calculatePrice(1000, 'Glossy')); ?></small>
                    </span>
                </label>
            </div>
        </fieldset>

        <div class="upload">
            <label for="artwork">Artwork</label>
            <input type="file" name="artwork" id="artwork" accept=".pdf,.png,.jpg,.jpeg,.ai">
            <p class="help">PDF, PNG, JPG or AI. Keep it under 25MB. Or pick a Sam Lee sample above if you just want to try the cart.</p>
            <p class="file-status" id="file-status" style="display:none;"></p>
        </div>

        <div class="purchase">
            <div class="price-block">
                <span class="price-label">Price</span>
                <strong class="price" id="price"><?php echo $price ? money($price) : '—'; ?></strong>
                <span class="price-note" id="price-note"><?php echo $qty . ' · ' . $paper; ?></span>
            </div>
            <button type="submit" class="btn-cart">Add to Cart</button>
        </div>
    </form>
</div>

</div>
</div>

<?php include 'includes/footer.php'; ?>
