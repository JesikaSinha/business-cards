Business cards configurator (PHP).

Run it with XAMPP: http://localhost/shopify/index.php

Price is based on quantity + paper. Size doesn't change the price.

100  matte $20 / glossy $25
500  matte $80 / glossy $100
1000 matte $140 / glossy $180

index.php = product page
price.php = returns the price as JSON (used by the JS on the product page)
add-to-cart.php = saves the upload and puts the item in $_SESSION['cart']
cart.php = cart

If upload fails:
chmod 777 uploads
