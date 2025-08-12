<?php
session_start();
include 'db_connect.php';

$user_id = $_SESSION['consumer_id'] ?? $_SESSION['seller_id'] ?? null;

// Get cart count and total for current user
$cart_count = 0;
$cart_total = 0;
if ($user_id) {
    $cart_sql = "SELECT COUNT(DISTINCT c.product_id) as count, SUM(c.quantity * p.price) as total 
                 FROM cart_items c 
                 LEFT JOIN products p ON c.product_id = p.product_id 
                 WHERE c.user_id = ?";
    $cart_stmt = $conn->prepare($cart_sql);
    $cart_stmt->bind_param("i", $user_id);
    $cart_stmt->execute();
    $cart_result = $cart_stmt->get_result();
    $cart_data = $cart_result->fetch_assoc();
    $cart_count = $cart_data['count'] ?? 0;
    $cart_total = $cart_data['total'] ?? 0;
}
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Title -->
    <title>AgriBazar</title>
    <!-- AgriBazar Tab Bar Icon -->
    <link rel="Icon" href="assets/img/logo.png">
    <!-- Font Awesome CSS -->
    <link rel="stylesheet" href="assets/plugins/font-awesome/css/font-awesome.min.css">
    <!-- Main CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <!-- Preloader -->
    <div class="preloader">
        <div class="spinner">
            <div class="ring ring1"></div>
            <div class="ring ring2"></div>
            <div class="ring ring3"></div>
        </div>
    </div>




    <!-- Header -->
    <section class="header">
        <div class="container">
            <div class="row align-center">
                <div class="col-xxl-2">
                    <div class="logo">
                        <a href="#">
                            <img src="assets/img/logo.png" alt="img">
                        </a>
                    </div>
                </div>
                <div class="col-xxl-6">
                    <div class="search d-flex justify-space-around">
                        <form method="GET" action="shop.php" style="display: flex; width: 100%;">
                            <input type="text" name="search" placeholder="Search by category, product or brand" style="flex: 1;">
                            <button type="submit" class="text-capitalize">
                                <i class="fa fa-search"></i>search
                            </button>
                        </form>
                    </div>
                </div>
                <div class="col-xxl-4">
                    <div class="header-right d-flex justify-space-between align-center">
                        <div class="wish-list text-center">
                            <a href="#">
                                <i class="fa-regular fa-heart"></i>
                            </a>
                        </div>


                        <div class="user-account">
                            <?php if ($user_id): ?>
                                <a href="logout.php" class="d-flex align-center">
                                    <i class="fa-solid fa-user-minus"></i><br>
                                    <h6 class="fw-600"><span>Log out</span>account</h6>
                                </a>
                            <?php else: ?>
                                <img src="assets/img/svg/star-icon-1.svg" alt="svg">
                                <a href="login.html" class="d-flex align-center">
                                    <i class="fa-solid fa-user-plus"></i>
                                    <h6 class="fw-600"><span>Login your</span>account</h6>
                                </a>
                                <div class="account-sep">
                                    <div class="account-login">
                                        <a href="consumer-login.html">consumer login</a>
                                        <span>or</span>
                                        <a href="seller-login.html">seller login</a>
                                    </div>
                                    <p>don't have any account?</p>
                                    <div class="account-register">
                                        <a href="consumer-registration.html">consumer sign up</a>
                                        <span>or</span>
                                        <a href="seller-registration.html">seller sign up</a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <img src="assets/img/svg/star-icon-1.svg" alt="svg">
                        <div class="user-cart d-flex align-center">
                            <div class="cart-icon">
                                <a href="cart.php">
                                    <i class="fa fa-shopping-cart"></i>
                                </a>
                                <span class="text-center"><?php echo $cart_count; ?></span>
                            </div>
                            <a href="cart.php">
                                <h6 class="fw-600"><span>cart items</span>৳<?php echo number_format($cart_total, 2); ?></h6>
                            </a>
                        </div>
                        <?php if (isset($_SESSION['seller_id'])): ?>
                            <div class="seller-dashboard-btn d-flex align-center" style="margin-left: 20px;">
                                <a href="seller_dashboard.php" style="
        background: linear-gradient(135deg, #43a047, #2e7d32);
        color: white;
        padding: 10px 18px;
        border-radius: 30px;
        text-decoration: none;
        font-weight: 600;
        font-size: 15px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        display: flex;
        align-items: center;
        gap: 8px;
        transition: 0.3s ease all;
    " onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                                    <i class="fa fa-briefcase"></i>Seller Dashboard
                                </a>
                            </div>

                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- /Header -->

    <!-- Header Bottom -->
    <section class="header-bottom">
        <div class="container">
            <div class="row align-center">
                <div class="col-xxl-3">
                    <div class="category sticky d-flex justify-space-between">
                        <div class="categories-bar d-flex align-center">
                            <i class="fa-solid fa-bars"></i>
                            <span class="text-capitalize">all categories</span>
                        </div>
                        <div class="categories-icon">
                            <i class="fa-solid fa-angle-down"></i>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-4">
                    <ul class="menu d-flex justify-space-between align-center text-capitalize flex-wrap">
                        <li><a href="#">home</a></li>
                        <li><a href="#">about</a></li>
                        <li><a href="shop.php">shop</a></li>
                        <li><a href="#">blog</a></li>
                        <li><a href="#">contact</a></li>
                    </ul>
                </div>
                <div class="col-xxl-5">
                    <div class="hotline d-flex justify-flex-end align-center">
                        <a href="#" class="text-capitalize">hotline: <span>(+00)91 1245 6859</span></a>
                        <a href="#" class="flash-btn text-uppercase"><i class="fa-solid fa-bolt"></i> flash sale</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- /Header Bottom -->

    <!-- Banner Area -->
    <section class="banner-area">
        <div class="container">
            <div class="row align-center">
                <div class="col-xxl-3">
                    <div class="category-content">
                        <ul class="category-list">
                            <li class="text-capitalize"><a href=""><img src="assets/img/banner/menu-1.png" alt=""> vegitables</a></li>
                            <li class="text-capitalize"><a href=""><img src="assets/img/banner/menu-2.png" alt="">soft drinks</a></li>
                            <li class="text-capitalize"><a href=""><img src="assets/img/banner/menu-3.png" alt="">fresh fruits</a></li>
                            <li class="text-capitalize"><a href=""><img src="assets/img/banner/menu-4.png" alt="">meat & fish</a></li>
                            <li class="text-capitalize"><a href=""><img src="assets/img/banner/menu-5.png" alt="">milk & cream</a></li>
                            <li class="text-capitalize"><a href=""><img src="assets/img/banner/menu-6.png" alt="">frozen foods</a></li>
                            <li class="text-capitalize"><a href=""><img src="assets/img/banner/menu-7.png" alt="">dairy products</a></li>
                            <li class="text-capitalize"><a href=""><img src="assets/img/banner/menu-8.png" alt="">bottled water</a></li>
                        </ul>
                        <a href="#" class="btn">view all ></a>
                    </div>
                </div>
                <div class="col-xxl-9">
                    <div class="banner" style="background-image: url('assets/img/banner/bg-1.jpg');">
                        <div class="banner-content">
                            <div class="discount">
                                <img src="assets/img/svg/star-icon-3.svg" alt="img">
                                <span>get up to 30% off</span>
                            </div>
                            <h1>try best <span>organic</span> products</h1>
                            <p>visit our discount shop will get 30% off every organic products, fruits and vegitables.</p>
                            <a href="#" class="btn btn-bg1"><i class="fa-solid fa-basket-shopping"></i>shop now</a>
                        </div>
                        <div class="banner-img">
                            <img src="assets/img/banner/banner-1.png" alt="">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- /Banner Area -->

    <!-- Feature Area -->
    <section class="feature">
        <div class="container">
            <div class="row">
                <div class="col-xxl-3">
                    <div class="feature-offer" style="background-image: url('assets/img/feature/feature-1.jpg');">
                        <span>hurry up</span>
                        <h6>20% off</h6>
                        <a href="#">buy now <i class="fa-solid fa-circle-chevron-right"></i></a>
                    </div>
                </div>
                <div class="col-xxl-9">
                    <div class="feature-box">
                        <div class="single-feature">
                            <div class="feature-img">
                                <img src="assets/img/feature/feature-2.jpg" alt="img">
                            </div>
                            <div class="feature-content">
                                <h5>free shiping</h5>
                                <p>shipping on your order. no extra charges for delivery.</p>
                            </div>
                        </div>
                        <div class="single-feature">
                            <div class="feature-img">
                                <img src="assets/img/feature/feature-2.jpg" alt="img">
                            </div>
                            <div class="feature-content">
                                <h5>Secure Payments</h5>
                                <p>our financial information is protected</p>
                            </div>
                        </div>
                        <div class="single-feature">
                            <div class="feature-img">
                                <img src="assets/img/feature/feature-2.jpg" alt="img">
                            </div>
                            <div class="feature-content">
                                <h5>24 Hour Support</h5>
                                <p>We're here to help wheneveryou need us.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- /Feature Area -->

    <!-- Featured Products -->
    <section class="featured-product pt-80 pb-80" style="background-image: url('assets/img/available-product/available-product-shape2.png');">
        <div class="container">
            <div class="row">
                <div class="col-xxl-12">
                    <div class="section-title-area d-flex align-center justify-space-between">
                        <div class="section-title">
                            <h3>Featured <span>products</span> <img src="assets/img/svg/star-icon-3.svg" alt="img"></h3>
                            <p>discover our handpicked selection of top-rated and trending items. limited time offers and exclusive deals await!</p>
                        </div>
                        <div class="section-product-btn">
                            <a href="#" class="btn btn-bg1">view more</a>
                        </div>
                    </div>
                </div>

            </div>
            <div class="featured-product-wrapper">
                <!-- Product card start -->
                <div class="single-featured-product"
                    style="background-image: url('assets/img/available-product/available-product-shape.png');">
                    <div class="available-product-img">
                        <a href="#">
                            <img src="assets/img/available-product/available-product.jpg" alt="img">
                        </a>
                    </div>
                    <div class="featured-product-content">
                        <span>500gm</span>
                        <h5>
                            <a href="#">green spinach</a>
                        </h5>
                    </div>
                    <div class="featured-product-rating">
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                    </div>
                    <div class="featured-product-price">
                        <span>$29.00</span>
                    </div>
                    <div class="featured-product-btn">
                        <a href="#" class="btn btn-bg1">add to cart</a>
                    </div>
                </div>
                <!-- Product card end -->

                <!-- Product card start -->
                <div class="single-featured-product"
                    style="background-image: url('assets/img/available-product/available-product-shape.png');">
                    <div class="available-product-img">
                        <a href="#">
                            <img src="assets/img/available-product/available-product-2.jpg" alt="img">
                        </a>
                    </div>
                    <div class="featured-product-content">
                        <span>500gm</span>
                        <h5>
                            <a href="#">green spinach</a>
                        </h5>
                    </div>
                    <div class="featured-product-rating">
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                    </div>
                    <div class="featured-product-price">
                        <span>$29.00</span>
                    </div>
                    <div class="featured-product-btn">
                        <a href="#" class="btn btn-bg1">add to cart</a>
                    </div>
                </div>
                <!-- Product card end -->

                <!-- Product card start -->
                <div class="single-featured-product"
                    style="background-image: url('assets/img/available-product/available-product-shape.png');">
                    <div class="featured-product-img">
                        <a href="#">
                            <img src="assets/img/available-product/available-product-3.jpg" alt="img">
                        </a>
                    </div>
                    <div class="featured-product-content">
                        <span>500gm</span>
                        <h5>
                            <a href="#">green spinach</a>
                        </h5>
                    </div>
                    <div class="featured-product-rating">
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                    </div>
                    <div class="featured-product-price">
                        <span>$29.00</span>
                    </div>
                    <div class="featured-product-btn">
                        <a href="#" class="btn btn-bg1">add to cart</a>
                    </div>
                </div>
                <!-- Product card end -->

                <!-- Product card start -->
                <div class="single-featured-product"
                    style="background-image: url('assets/img/available-product/available-product-shape.png');">
                    <div class="featured-product-img">
                        <a href="#">
                            <img src="assets/img/available-product/available-product4.jpg" alt="img">
                        </a>
                    </div>
                    <div class="featured-product-content">
                        <span>500gm</span>
                        <h5>
                            <a href="#">green spinach</a>
                        </h5>
                    </div>
                    <div class="featured-product-rating">
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                    </div>
                    <div class="featured-product-price">
                        <span>$29.00</span>
                    </div>
                    <div class="featured-product-btn">
                        <a href="#" class="btn btn-bg1">add to cart</a>
                    </div>
                </div>
                <!-- Product card end -->
            </div>
        </div>
    </section>
    <!-- /Featured Products -->


    <!-- Banner 2 -->
    <section class="banner2 pt-80 pb-80">
        <div class="container">
            <div class="row">
                <div class="col-xxl-6">
                    <div class="banner2-wrapper" style="background-image: url('assets/img/banner/banner-bg-1.jpg');">
                        <div class="banner2-content">
                            <h5>
                                <img src="assets/img/svg/star-icon-3.svg" alt="img">
                                <span>new arrival</span>
                                <img src="assets/img/svg/star-icon-3.svg" alt="img">
                            </h5>
                            <h3>organic raw green beans & seed</h3>
                            <p>get 40% discount for new arrival</p>
                            <a href="#" class="btn btn-bg1">shop now</a>
                        </div>
                        <div class="banner2-img">
                            <img src="assets/img/banner/banner-img-1.png" alt="">
                        </div>
                    </div>
                </div>
                <div class="col-xxl-6">
                    <div class="banner2-wrapper" style="background-image: url('assets/img/banner/banner-bg-2.jpg');">
                        <div class="banner2-content">
                            <h5>
                                <img src="assets/img/svg/star-icon-3.svg" alt="img">
                                <span>hot deals</span>
                                <img src="assets/img/svg/star-icon-3.svg" alt="img">
                            </h5>
                            <h3>organic Fruits and vegetables</h3>
                            <p>get 30% discount for new arrival</p>
                            <a href="#" class="btn btn-bg1">shop now</a>
                        </div>
                        <div class="banner2-img">
                            <img src="assets/img/banner/banner-img-2.png" alt="">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- /Banner 2 -->

    <!-- Banner 3 -->
    <section class="banner3 pt-80 pb-80" style="background-image: url('assets/img/banner/banner-bg-3.jpg');">
        <div class="banner3-shape"><img src="assets/img/banner/banner-shape.png" alt="img"></div>
        <div class="container">
            <div class="row align-center">
                <div class="col-xxl-6">
                    <div class="banner3-img">
                        <div class="banner3-img-border">
                            <img src="assets/img/banner/banner-img-3.png" alt="img">
                        </div>
                        <div class="banner3-discount">
                            <img src="assets/img/banner/banner-discount-.png" alt="img">
                        </div>
                    </div>
                </div>
                <div class="col-xxl-6">
                    <div class="banner3-item">
                        <h5>hurry up! <span>offer end in</span> <img src="assets/img/svg/star-icon-3.svg" alt="img"></h5>
                        <div class="banner3-countdown">
                            <div class="countdown">
                                <div class="banner3-countdown-item">
                                    <span class="countdown-value">140</span>
                                    <span class="countdown-label">days</span>
                                </div>
                                <div class="banner3-countdown-separator">:</div>
                                <div class="banner3-countdown-item">
                                    <span class="countdown-value">15</span>
                                    <span class="countdown-label">hours</span>
                                </div>
                                <div class="banner3-countdown-separator">:</div>
                                <div class="banner3-countdown-item">
                                    <span class="countdown-value">28</span>
                                    <span class="countdown-label">mins</span>
                                </div>
                                <div class="banner3-countdown-separator">:</div>
                                <div class="banner3-countdown-item">
                                    <span class="countdown-value">30</span>
                                    <span class="countdown-label">secs</span>
                                </div>
                            </div>
                        </div>
                        <h3>pure & organic fruits, Veggies, and meat</h3>
                        <p>Enjoy a massive 45% discount on your entire order. That's right, save big on your favorite items and treat yourself to something special.</p>
                        <div class="banner3-btn">
                            <a href="#" class="btn btn-bg1">shop now</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- /Banner 3 -->

    <!-- Banner 4 -->
    <section class="banner4 pt-80 pb-80" style="background-image: url('assets/img/banner/banner-bg-4.png');">
        <div class="container">
            <div class="row">
                <div class="col-xxl-4">
                    <div class="banner4-single" style="background-image: url('assets/img/banner/banner-img-5.png');">
                        <div class="banner4-item">
                            <h5>20% off</h5>
                            <h3>Daily Breakfast Items & Beverages</h3>
                            <div class="banner4-btn">
                                <a href="#">buy now <i class="fa-solid fa-circle-chevron-right"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-4">
                    <div class="banner4-single" style="background-image: url('assets/img/banner/banner-img-4.png');">
                        <div class="banner4-item">
                            <h5>20% off</h5>
                            <h3>Healthy organic dry gains foods</h3>
                            <div class="banner4-btn">
                                <a href="#">buy now <i class="fa-solid fa-circle-chevron-right"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-4">
                    <div class="banner4-single" style="background-image: url('assets/img/banner/banner-img-6.png');">
                        <div class="banner4-item">
                            <h5>20% off</h5>
                            <h3>New Pet Arrivals Food & Accessories</h3>
                            <div class="banner4-btn">
                                <a href="#">buy now <i class="fa-solid fa-circle-chevron-right"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- /Banner 4 -->

    <!-- available products -->
    <section class="available-product pt-80 pb-80" style="background-image: url('assets/img/available-product/available-product-shape2.png');">
        <div class="container">
            <div class="row">
                <div class="col-xxl-12">
                    <div class="section-title-area d-flex align-center justify-space-between">
                        <div class="section-title">
                            <h3>available <span>products</span> <img src="assets/img/svg/star-icon-3.svg" alt="img"></h3>
                            <p>our bestsellers! discover the customer favorites that everyone's talking about.</p>
                        </div>
                        <div class="section-product-btn">
                            <a href="#" class="btn btn-bg1">view more</a>
                        </div>
                    </div>
                </div>

            </div>
            <div class="available-product-wrapper">
                <!-- Product card start -->
                <div class="single-available-product"
                    style="background-image: url('assets/img/available-product/available-product-shape.png');">
                    <div class="available-product-img">
                        <a href="#">
                            <img src="assets/img/available-product/available-product.jpg" alt="img">
                        </a>
                    </div>
                    <div class="available-product-content">
                        <span>500gm</span>
                        <h5>
                            <a href="#">green spinach</a>
                        </h5>
                    </div>
                    <div class="available-product-rating">
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                    </div>
                    <div class="available-product-price">
                        <span>$29.00</span>
                    </div>
                    <div class="available-product-btn">
                        <a href="#" class="btn btn-bg1">add to cart</a>
                    </div>
                </div>
                <!-- Product card start -->

                <!-- Product card start -->
                <div class="single-available-product"
                    style="background-image: url('assets/img/available-product/available-product-shape.png');">
                    <div class="available-product-img">
                        <a href="#">
                            <img src="assets/img/available-product/available-product-2.jpg" alt="img">
                        </a>
                    </div>
                    <div class="available-product-content">
                        <span>500gm</span>
                        <h5>
                            <a href="#">green spinach</a>
                        </h5>
                    </div>
                    <div class="available-product-rating">
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                    </div>
                    <div class="available-product-price">
                        <span>$29.00</span>
                    </div>
                    <div class="available-product-btn">
                        <a href="#" class="btn btn-bg1">add to cart</a>
                    </div>
                </div>
                <!-- Product card start -->

                <!-- Product card start -->
                <div class="single-available-product"
                    style="background-image: url('assets/img/available-product/available-product-shape.png');">
                    <div class="available-product-img">
                        <a href="#">
                            <img src="assets/img/available-product/available-product-3.jpg" alt="img">
                        </a>
                    </div>
                    <div class="available-product-content">
                        <span>500gm</span>
                        <h5>
                            <a href="#">green spinach</a>
                        </h5>
                    </div>
                    <div class="available-product-rating">
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                    </div>
                    <div class="available-product-price">
                        <span>$29.00</span>
                    </div>
                    <div class="available-product-btn">
                        <a href="#" class="btn btn-bg1">add to cart</a>
                    </div>
                </div>
                <!-- Product card start -->

                <!-- Product card start -->
                <div class="single-available-product"
                    style="background-image: url('assets/img/available-product/available-product-shape.png');">
                    <div class="available-product-img">
                        <a href="#">
                            <img src="assets/img/available-product/available-product4.jpg" alt="img">
                        </a>
                    </div>
                    <div class="available-product-content">
                        <span>500gm</span>
                        <h5>
                            <a href="#">green spinach</a>
                        </h5>
                    </div>
                    <div class="available-product-rating">
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                    </div>
                    <div class="available-product-price">
                        <span>$29.00</span>
                    </div>
                    <div class="available-product-btn">
                        <a href="#" class="btn btn-bg1">add to cart</a>
                    </div>
                </div>
                <!-- Product card start -->

                <!-- Product card start -->
                <div class="single-available-product"
                    style="background-image: url('assets/img/available-product/available-product-shape.png');">
                    <div class="available-product-img">
                        <a href="#">
                            <img src="assets/img/available-product/available-product-5.jpg" alt="img">
                        </a>
                    </div>
                    <div class="available-product-content">
                        <span>500gm</span>
                        <h5>
                            <a href="#">green spinach</a>
                        </h5>
                    </div>
                    <div class="available-product-rating">
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                    </div>
                    <div class="available-product-price">
                        <span>$29.00</span>
                    </div>
                    <div class="available-product-btn">
                        <a href="#" class="btn btn-bg1">add to cart</a>
                    </div>
                </div>
                <!-- Product card start -->

                <!-- Product card start -->
                <div class="single-available-product"
                    style="background-image: url('assets/img/available-product/available-product-shape.png');">
                    <div class="available-product-img">
                        <a href="#">
                            <img src="assets/img/available-product/available-product-6.png" alt="img">
                        </a>
                    </div>
                    <div class="available-product-content">
                        <span>500gm</span>
                        <h5>
                            <a href="#">green spinach</a>
                        </h5>
                    </div>
                    <div class="available-product-rating">
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                    </div>
                    <div class="available-product-price">
                        <span>$29.00</span>
                    </div>
                    <div class="available-product-btn">
                        <a href="#" class="btn btn-bg1">add to cart</a>
                    </div>
                </div>
                <!-- Product card start -->

                <!-- Product card start -->
                <div class="single-available-product"
                    style="background-image: url('assets/img/available-product/available-product-shape.png');">
                    <div class="available-product-img">
                        <a href="#">
                            <img src="assets/img/available-product/available-product-7.png" alt="img">
                        </a>
                    </div>
                    <div class="available-product-content">
                        <span>500gm</span>
                        <h5>
                            <a href="#">green spinach</a>
                        </h5>
                    </div>
                    <div class="available-product-rating">
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                    </div>
                    <div class="available-product-price">
                        <span>$29.00</span>
                    </div>
                    <div class="available-product-btn">
                        <a href="#" class="btn btn-bg1">add to cart</a>
                    </div>
                </div>
                <!-- Product card start -->

                <!-- Product card start -->
                <div class="single-available-product"
                    style="background-image: url('assets/img/available-product/available-product-shape.png');">
                    <div class="available-product-img">
                        <a href="#">
                            <img src="assets/img/available-product/available-product-8.png" alt="img">
                        </a>
                    </div>
                    <div class="available-product-content">
                        <span>500gm</span>
                        <h5>
                            <a href="#">green spinach</a>
                        </h5>
                    </div>
                    <div class="available-product-rating">
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                    </div>
                    <div class="available-product-price">
                        <span>$29.00</span>
                    </div>
                    <div class="available-product-btn">
                        <a href="#" class="btn btn-bg1">add to cart</a>
                    </div>
                </div>
                <!-- Product card start -->

                <!-- Product card start -->
                <div class="single-available-product"
                    style="background-image: url('assets/img/available-product/available-product-shape.png');">
                    <div class="available-product-img">
                        <a href="#">
                            <img src="assets/img/available-product/available-product-9.png" alt="img">
                        </a>
                    </div>
                    <div class="available-product-content">
                        <span>500gm</span>
                        <h5>
                            <a href="#">green spinach</a>
                        </h5>
                    </div>
                    <div class="available-product-rating">
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                    </div>
                    <div class="available-product-price">
                        <span>$29.00</span>
                    </div>
                    <div class="available-product-btn">
                        <a href="#" class="btn btn-bg1">add to cart</a>
                    </div>
                </div>
                <!-- Product card start -->

                <!-- Product card start -->
                <div class="single-available-product"
                    style="background-image: url('assets/img/available-product/available-product-shape.png');">
                    <div class="available-product-img">
                        <a href="#">
                            <img src="assets/img/available-product/available-product-10.png" alt="img">
                        </a>
                    </div>
                    <div class="available-product-content">
                        <span>500gm</span>
                        <h5>
                            <a href="#">green spinach</a>
                        </h5>
                    </div>
                    <div class="available-product-rating">
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                    </div>
                    <div class="available-product-price">
                        <span>$29.00</span>
                    </div>
                    <div class="available-product-btn">
                        <a href="#" class="btn btn-bg1">add to cart</a>
                    </div>
                </div>
                <!-- Product card start -->

            </div>
        </div>
    </section>
    <!-- /available products -->

    <!-- news letter -->
    <section class="newsletter" style="background-image: url('assets/img/newslatter/newsletter-bg.jpg');">
        <div class="newsletter-shape1"><img src="assets/img/newslatter/newslatter-shape-1.png" alt=""></div>
        <div class="newsletter-shape2"><img src="assets/img/newslatter/newslatter-shape-2.png" alt=""></div>
        <div class="container">
            <div class="row align-center">
                <div class="col-xxl-6">
                    <div class="newsletter-wrapper">
                        <div class="newsletter-item">
                            <h3>subscribe newsletter</h3>
                            <p>Enjoy early access to sales, new product launches, expert advice, and special offers delivered straight to your inbox.</p>
                            <form action="#">
                                <div class="newsletter-subscribe">
                                    <input type="email" placeholder="Enter Your Email">
                                    <button type="submit" class="btn btn-bg1">subscribe</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-6">
                    <div class="newsletter-img">
                        <img src="assets/img/newslatter/newslatter-1.png" alt="img">
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- /news letter -->

    <!-- Footer Area -->
    <section class="footer pt-60 pb-60" style="background-image: url('assets/img/footer/footer-bg.jpg');">
        <div class="container">
            <div class="row">
                <div class="col-xxl-3">
                    <div class="footer-info-area">
                        <div class="footer-logo">
                            <a href="#"><img src="assets/img/logo.png" alt="img"></a>
                        </div>
                        <div class="footer-content">
                            <p>AgriBazar is a online grocery shop. we are selling grocery products</p>
                            <div class="footer-content-box">
                                <a href="#"><i class="fa-solid fa-headset"></i></a>
                                <a href="#">
                                    <h5><span>emergency support</span>+123 4567 8901</h5>
                                </a>
                            </div>
                            <ul class="footer-content-list">
                                <li>
                                    <a href="#">
                                        <i class="fa-solid fa-location-dot"></i>
                                        <p>123 Main Street, Anytown, California 90210 user-account</p>
                                    </a>
                                </li>
                                <li>
                                    <a href="#">
                                        <i class="fa-solid fa-envelope-open-text"></i>
                                        <p>support@domain.com</p>
                                    </a>
                                </li>
                                <li>
                                    <a href="#">
                                        <i class="fa-solid fa-headset"></i>
                                        <p>+00 123 5555 888</p>
                                    </a>
                                </li>
                                <li>
                                    <a href="#">
                                        <i class="fa-regular fa-clock"></i>
                                        <p>10:00 - 18:00 Monday - saturday</p>
                                    </a>
                                </li>
                            </ul>
                            <div class="footer-social">
                                <h5>follow us:</h5>
                                <div class="footer-social-icon">
                                    <i class="fa-brands fa-facebook-f"></i>
                                    <i class="fa-brands fa-linkedin-in"></i>
                                    <i class="fa-brands fa-twitter"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-3">
                    <div class="single-footer">
                        <div class="single-footer-title">
                            <h2>company</h2>
                        </div>
                        <ul class="single-footer-content">
                            <li><a href="">about us</a></li>
                            <li><a href="">delevery information</a></li>
                            <li><a href="">privacy policy</a></li>
                            <li><a href="">terms & condition</a></li>
                            <li><a href="">contact us</a></li>
                            <li><a href="">carrers</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-xxl-3">
                    <div class="single-footer">
                        <div class="single-footer-title">
                            <h2>quick links</h2>
                        </div>
                        <ul class="single-footer-content">
                            <li><a href="">about company</a></li>
                            <li><a href="">articles & blogs</a></li>
                            <li><a href="">flash sales</a></li>
                            <li><a href="">checkout</a></li>
                            <li><a href="">FAQs Page</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-xxl-3">
                    <div class="single-footer">
                        <div class="single-footer-title">
                            <h2>support</h2>
                        </div>
                        <ul class="single-footer-content">
                            <li><a href="">my account</a></li>
                            <li><a href="">payment method</a></li>
                            <li><a href="">license & permit</a></li>
                            <li><a href="">our partners</a></li>
                            <li><a href="">support center</a></li>
                            <li><a href="">shopping cart</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="footer-bottom">
        <div class="container">
            <div class="row">
                <div class="col-xxl-6">
                    <div class="footer-bottom-copyright">
                        <p>Copyright 2025 &copy; <a href="#">AgriBazar</a>. All rights reserved.</p>
                    </div>
                </div>
                <div class="col-xxl-6">
                    <div class="footer-bottom-img text-right">
                        <img src="assets/img/footer/footer-boottom-payment.png" alt="img">
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- /Footer Area -->

















    <script>
        // --------Pre-loader-----------
        window.addEventListener("load", () => {
            setTimeout(() => {
                document.querySelector(".preloader").classList.add("hidden");
            }, 1000); // Wait 2 seconds before hiding
        });
    </script>






</body>

</html>