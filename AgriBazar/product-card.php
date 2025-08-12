<?php
// product-card.php

// Expected variables:
// $product = [
//   'id' => int,
//   'name' => string,
//   'weight' => string,
//   'image' => string (image path/url),
//   'price' => float,
//   'rating' => int (1 to 5 stars),
// ];
// $type = 'shop' or 'my-products'

// Validate required variables
if (!isset($product) || !is_array($product)) {
    echo "<!-- product data missing -->";
    return;
}
if (!isset($type)) {
    $type = 'shop'; // default type
}

// Setup variables for class names and button
if ($type === 'shop') {
    $cardClass = "single-available-product";
    $wrapperStyle = "background-image: url('assets/img/available-product/available-product-shape.png');";
    $imgWrapperClass = "available-product-img";
    $contentClass = "available-product-content";
    $ratingClass = "available-product-rating";
    $priceClass = "available-product-price";
    $btnClass = "available-product-btn";
    $btnText = "add to cart";
    $btnHref = "#"; // TODO: update with actual add to cart link/action
} else if ($type === 'my-products') {
    $cardClass = "single-my-product";
    $wrapperStyle = "background-image: url('assets/img/available-product/available-product-shape.png');";
    $imgWrapperClass = "my-product-img";
    $contentClass = "my-product-content";
    $ratingClass = "my-product-rating";
    $priceClass = "my-product-price";
    $btnClass = "my-product-btn";
    $btnText = "update";
    $btnHref = "#"; // TODO: update with actual update link/action
} else {
    // Fallback to shop style
    $cardClass = "single-available-product";
    $wrapperStyle = "background-image: url('assets/img/available-product/available-product-shape.png');";
    $imgWrapperClass = "available-product-img";
    $contentClass = "available-product-content";
    $ratingClass = "available-product-rating";
    $priceClass = "available-product-price";
    $btnClass = "available-product-btn";
    $btnText = "add to cart";
    $btnHref = "#";
}

// Function to print stars based on rating
function renderStars($count) {
    $count = intval($count);
    $fullStar = '<i class="fa-solid fa-star"></i>';
    $emptyStar = '<i class="fa-regular fa-star"></i>';
    $starsHtml = "";
    for ($i=0; $i<5; $i++) {
        $starsHtml .= ($i < $count) ? $fullStar : $emptyStar;
    }
    return $starsHtml;
}
?>

<div class="<?php echo $cardClass; ?>" style="<?php echo $wrapperStyle; ?>">
    <div class="<?php echo $imgWrapperClass; ?>">
        <a href="#">
            <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
        </a>
    </div>
    <div class="<?php echo $contentClass; ?>">
        <span><?php echo htmlspecialchars($product['weight']); ?></span>
        <h5>
            <a href="#"><?php echo htmlspecialchars($product['name']); ?></a>
        </h5>
    </div>
    <div class="<?php echo $ratingClass; ?>">
        <?php echo renderStars($product['rating']); ?>
    </div>
    <div class="<?php echo $priceClass; ?>">
        <span>$<?php echo number_format($product['price'], 2); ?></span>
    </div>
    <div class="<?php echo $btnClass; ?>">
        <a href="<?php echo $btnHref; ?>" class="btn btn-bg1"><?php echo $btnText; ?></a>
    </div>
</div>
