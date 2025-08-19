<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include 'db_connect.php';

$user_id = $_SESSION['consumer_id'] ?? $_SESSION['seller_id'] ?? null;
$is_seller = isset($_SESSION['seller_id']);

// Get search parameter
$search_query = $_GET['search'] ?? '';
$sort_option = $_GET['sort'] ?? 'default';
$category_filter = $_GET['category'] ?? '';


// $sql = "SELECT p.*, s.full_name as seller_name, um.profile_image as seller_image 
//         FROM products p 
//         LEFT JOIN sellers s ON p.seller_id = s.seller_id 
//         LEFT JOIN user_meta um ON um.user_id = s.seller_id 
//         WHERE 1=1";
$sql = "SELECT p.*, s.full_name as seller_name 
        FROM products p 
        LEFT JOIN sellers s ON p.seller_id = s.seller_id  
        WHERE 1=1";

$params = [];
$types = "";

// Add search conditions
if (!empty($search_query)) {
    $sql .= " AND (p.product_name LIKE ? OR p.description LIKE ? OR p.product_category LIKE ?)";
    $search_param = "%{$search_query}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

// Add category filter
if (!empty($category_filter)) {
    $sql .= " AND p.product_category = ?";
    $params[] = $category_filter;
    $types .= "s";
}

// Add sorting
switch ($sort_option) {
    case 'lowtohigh':
        $sql .= " ORDER BY p.price ASC";
        break;
    case 'hightolow':
        $sql .= " ORDER BY p.price DESC";
        break;
    case 'availability':
        $sql .= " ORDER BY p.quantity_available DESC";
        break;
    case 'newest':
        $sql .= " ORDER BY p.created_at DESC";
        break;
    case 'name_asc':
        $sql .= " ORDER BY p.product_name ASC";
        break;
    default:
        $sql .= " ORDER BY p.created_at DESC";
        break;
}

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get cart count for current user
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


$category_sql = "SELECT DISTINCT product_category FROM products WHERE product_category IS NOT NULL AND product_category != ''";
$category_result = $conn->query($category_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop - AgriBazar</title>
    <link rel="Icon" href="assets/img/logo.png">
    <link rel="stylesheet" href="assets/plugins/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Original Thin Green Header Design */
        .header.custom-header {
            background: linear-gradient(135deg, #e8f5e9 0%, #f1f8e9 100%);
            border-bottom: 1px solid #e0e0e0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .header .logo img {
            filter: drop-shadow(0 2px 4px rgba(46, 125, 50, 0.3));
        }
        
        /* Enhanced Beautiful Banner with More Height */
        .shop-banner {
            background: linear-gradient(135deg, #1b5e20 0%, #2e7d32 20%, #388e3c 40%, #4caf50 60%, #66bb6a 80%, #81c784 100%);
            padding: 80px 0; /* Much more height */
            margin-bottom: 25px;
            color: white;
            position: relative;
            overflow: hidden;
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(46, 125, 50, 0.4);
        }
        
        /* Enhanced Beautiful Background Effects */
        .shop-banner::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 20%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(255, 255, 255, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 40% 60%, rgba(255, 255, 255, 0.05) 0%, transparent 50%);
            z-index: 1;
        }
        
        .shop-banner::after {
            content: '';
            position: absolute;
            top: -30%;
            right: -10%;
            width: 400px;
            height: 400px;
            background: 
                radial-gradient(circle, rgba(255, 255, 255, 0.15) 0%, rgba(255, 255, 255, 0.08) 40%, transparent 70%);
            border-radius: 50%;
            z-index: 1;
        }
        
        /* Additional decorative floating elements */
        .shop-banner .container::before {
            content: '';
            position: absolute;
            top: 10%;
            left: -15%;
            width: 250px;
            height: 250px;
            background: 
                radial-gradient(circle, rgba(129, 199, 132, 0.2) 0%, rgba(129, 199, 132, 0.1) 50%, transparent 70%);
            border-radius: 50%;
            z-index: 1;
            animation: float 6s ease-in-out infinite;
        }
        
        .shop-banner .container::after {
            content: '';
            position: absolute;
            bottom: 10%;
            right: -20%;
            width: 300px;
            height: 300px;
            background: 
                radial-gradient(circle, rgba(165, 214, 167, 0.15) 0%, transparent 60%);
            border-radius: 50%;
            z-index: 1;
            animation: float 8s ease-in-out infinite reverse;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(10deg); }
        }
        
        .banner-content {
            position: relative;
            z-index: 2;
            text-align: center;
            padding: 30px 0;
        }
        
        .banner-title h2 {
            font-size: 42px;
            font-weight: 900;
            margin-bottom: 20px;
            text-shadow: 0 4px 8px rgba(0, 0, 0, 0.4);
            line-height: 1.2;
            letter-spacing: -1px;
        }
        
        .banner-subtitle {
            font-size: 20px;
            opacity: 0.95;
            margin-bottom: 0;
            font-weight: 500;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .search-highlight-banner {
            color: #ffeb3b;
            font-weight: 900;
            text-shadow: 0 4px 12px rgba(0, 0, 0, 0.7);
            background: linear-gradient(135deg, rgba(255, 235, 59, 0.3) 0%, rgba(255, 235, 59, 0.1) 100%);
            padding: 6px 16px;
            border-radius: 15px;
            border: 3px solid rgba(255, 235, 59, 0.4);
            display: inline-block;
            transform: rotate(-2deg);
        }
        
        .banner-icon {
            font-size: 38px;
            margin-right: 15px;
            text-shadow: 0 4px 8px rgba(0, 0, 0, 0.5);
            animation: pulse 2s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        /* Search styling */
        .search-container {
            position: relative;
            transition: all 0.3s ease;
        }
        
        .search-box {
            width: 200px;
            transition: all 0.3s ease;
            padding: 10px 15px;
            border-radius: 25px;
            border: 2px solid #e0e0e0;
        }
        
        .search-box.expanded {
            width: 400px;
        }
        
        .search-box:focus {
            outline: none;
            border-color: #4caf50;
            box-shadow: 0 0 10px rgba(76, 175, 80, 0.3);
        }
        
        .search-btn {
            background: linear-gradient(135deg, #2e7d32 0%, #4caf50 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 20px;
            cursor: pointer;
            margin-left: 10px;
            transition: all 0.3s ease;
        }
        
        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(46, 125, 50, 0.3);
        }
        
        /* Dashboard button styling */
        .dashboard-btn {
            background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 20px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 13px;
            display: inline-block;
        }
        
        .dashboard-btn:hover {
            background: linear-gradient(135deg, #e55a2e 0%, #e8851b 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 107, 53, 0.4);
            color: white;
            text-decoration: none;
        }
        
        /* Filter Section */
        .filter-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .filter-row {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .filter-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .filter-select {
            padding: 10px 15px;
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            background: white;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .filter-select:focus {
            outline: none;
            border-color: #4caf50;
        }
        
        .clear-filters {
            background: #ff6b35;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s ease;
        }
        
        /* Enhanced Product Grid */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(285px, 1fr));
            gap: 20px;
            margin-top: 25px;
        }
        
        /* Ultra Enhanced Product Cards */
        .product-card {
            background: linear-gradient(145deg, #ffffff 0%, #fafafa 100%);
            border-radius: 20px;
            padding: 18px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(200, 230, 201, 0.5);
            height: auto;
        }
        
        .product-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(76, 175, 80, 0.03), transparent);
            transition: left 0.6s ease;
        }
        
        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            border-color: #4caf50;
        }
        
        .product-card:hover::before {
            left: 100%;
        }
        
        /* Enhanced Image Container */
        .product-image-container {
            position: relative;
            margin-bottom: 15px;
            overflow: hidden;
            border-radius: 15px;
        }
        
        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 15px;
            transition: transform 0.3s ease;
        }
        
        .product-card:hover .product-image {
            transform: scale(1.08);
        }
        
        /* Quantity Badge */
        .quantity-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 700;
            box-shadow: 0 3px 10px rgba(255, 107, 53, 0.3);
        }
        
        .product-info {
            text-align: center;
            position: relative;
            z-index: 2;
        }
        
        /* Much Bigger Product Name */
        .product-info h5 {
            color: #1565c0; /* Blue color */
            margin-bottom: 12px;
            font-weight: 800; /* Extra bold */
            font-size: 20px; /* Much bigger */
            line-height: 1.2;
            height: 50px; /* Increased height */
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-shadow: 0 1px 3px rgba(21, 101, 192, 0.2);
        }
        
        /* Price and Weight in Same Row */
        .price-weight-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 12px 0;
            padding: 10px 15px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px;
            border: 1px solid rgba(76, 175, 80, 0.1);
        }
        
        .product-price {
            font-size: 20px;
            font-weight: 800;
            color: #ff6b35;
            display: flex;
            align-items: center;
            gap: 2px;
        }
        
        .taka-sign {
            font-size: 16px;
            font-weight: 700;
        }
        
        .product-weight {
            color: #666;
            font-size: 12px;
            font-weight: 600;
            background: #e8f5e8;
            padding: 4px 8px;
            border-radius: 8px;
        }
        
        /* FIXED: Seller Info and Rating Row - COLUMN Layout */
        .seller-rating-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start; /* Changed for column layout */
            background: linear-gradient(135deg, #e8f5e8 0%, #f1f8e9 100%);
            padding: 15px 15px;
            border-radius: 15px;
            margin-bottom: 15px;
            border-left: 4px solid #4caf50;
            gap: 10px;
            min-height: 60px; /* Increased height for column layout */
        }
        
        /* Left Half - Seller Info in COLUMN (name above, verified below) */
        .seller-info-section {
            flex: 1;
            display: flex;
            flex-direction: column; /* COLUMN layout as requested */
            gap: 6px;
            text-align: left;
            justify-content: flex-start;
        }
        
        .seller-name {
            font-size: 13px;
            color: #2e7d32;
            font-weight: 700;
            margin: 0;
            line-height: 1.2;
        }
        
        /* Enhanced Seller Verified Badge - Below name */
        .verified-badge {
            background: linear-gradient(135deg, #4caf50 0%, #66bb6a 100%);
            color: white;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            box-shadow: 0 3px 8px rgba(76, 175, 80, 0.3);
            align-self: flex-start; /* Align to start of container */
            white-space: nowrap;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        /* Right Half - Enhanced Star Rating in COLUMN */
        .rating-section {
            flex: 1;
            display: flex;
            flex-direction: column; /* Column for stars above, text below */
            align-items: flex-end;
            gap: 4px;
            justify-content: flex-start;
        }
        
        .stars {
            color: #ffc107;
            font-size: 14px;
            text-shadow: 0 1px 3px rgba(255, 193, 7, 0.3);
            line-height: 1;
        }
        
        .rating-text {
            font-size: 10px;
            color: #f57c00;
            font-weight: 600;
            white-space: nowrap;
            line-height: 1;
        }
        
        /* Action Buttons Row - Cart and Wishlist */
        .action-buttons-row {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        /* Add to Cart Button */
        .add-to-cart-btn {
            flex: 1;
            background: linear-gradient(135deg, #2e7d32 0%, #4caf50 100%);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(46, 125, 50, 0.3);
        }
        
        .add-to-cart-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }
        
        .add-to-cart-btn:hover {
            background: linear-gradient(135deg, #1b5e20 0%, #388e3c 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(46, 125, 50, 0.4);
        }
        
        .add-to-cart-btn:hover::before {
            left: 100%;
        }
        
        .add-to-cart-btn:active {
            transform: translateY(0px);
        }
        
        .add-to-cart-btn:disabled {
            background: linear-gradient(135deg, #ccc 0%, #999 100%);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        /* Wishlist Heart Button - Beside Add to Cart */
        .wishlist-btn {
            background: rgba(255, 255, 255, 0.9);
            color: #ff6b6b;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            border: 2px solid #ff6b6b;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 16px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }
        
        .wishlist-btn:hover {
            background: #ff6b6b;
            color: white;
            transform: scale(1.1);
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.4);
        }
        
        .wishlist-btn.active {
            background: #ff6b6b;
            color: white;
        }
        
        /* No Products State */
        .no-products {
            text-align: center;
            padding: 60px 20px;
            color: #666;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px;
            margin: 30px 0;
        }
        
        .no-products i {
            font-size: 64px;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .no-products h3 {
            color: #2e7d32;
            margin-bottom: 10px;
            font-size: 20px;
        }
        
        /* Notification Styles */
        .cart-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #2e7d32 0%, #4caf50 100%);
            color: white;
            padding: 15px 25px;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(46, 125, 50, 0.4);
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.4s ease;
            z-index: 1000;
            font-weight: 600;
            max-width: 300px;
        }
        
        .cart-notification.show {
            opacity: 1;
            transform: translateX(0);
        }
        
        .cart-notification.error {
            background: linear-gradient(135deg, #d32f2f 0%, #f44336 100%);
            box-shadow: 0 6px 20px rgba(211, 47, 47, 0.4);
        }
        
        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: #ffffff;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .product-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 15px;
            }
            
            .search-box.expanded {
                width: 280px;
            }
            
            .product-card {
                padding: 16px;
            }
            
            .product-image {
                height: 180px;
            }
            
            .banner-title h2 {
                font-size: 32px;
            }
            
            .shop-banner {
                padding: 60px 0;
            }
        }
        
        @media (max-width: 480px) {
            .product-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .search-box {
                width: 140px;
            }
            
            .search-box.expanded {
                width: 220px;
            }
            
            .price-weight-row {
                flex-direction: column;
                gap: 5px;
            }
            
            .seller-rating-row {
                flex-direction: column;
                gap: 10px;
                min-height: auto;
            }
            
            .seller-info-section, .rating-section {
                text-align: center;
            }
            
            .banner-title h2 {
                font-size: 26px;
            }
            
            .shop-banner {
                padding: 50px 0;
            }
        }
    </style>
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

    <!-- Original Thin Green Header Design -->
    <section class="header custom-header">
        <div class="container">
            <div class="row align-center">
                <div class="col-xxl-2">
                    <div class="logo">
                        <a href="index.php">
                            <img src="assets/img/logo.png" alt="AgriBazar Logo">
                        </a>
                    </div>
                </div>
                <div class="col-xxl-6">
                    <div class="search d-flex justify-space-around search-container">
                        <form method="GET" class="d-flex align-center" style="width: 100%;">
                            <input type="text" 
                                   id="searchBox"
                                   name="search" 
                                   class="search-box" 
                                   placeholder="Search fresh products..." 
                                   value="<?php echo htmlspecialchars($search_query); ?>"
                                   onclick="expandSearch()">
                            <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort_option); ?>">
                            <input type="hidden" name="category" value="<?php echo htmlspecialchars($category_filter); ?>">
                            <button type="submit" class="search-btn text-capitalize">
                                <i class="fa fa-search"></i> search
                            </button>
                        </form>
                    </div>
                </div>
                <div class="col-xxl-4">
                    <div class="header-right d-flex justify-space-between align-center">
                        <div class="wish-list text-center">
                            <a href="#" style="color: #2e7d32;">
                                <i class="fa fa-heart"></i>
                            </a>
                        </div>
                        
                        <img src="assets/img/svg/star-icon-1.svg" alt="svg">
                        
                        <!-- Dashboard Button -->
                        <div class="dashboard-section">
                            <?php if ($is_seller): ?>
                                <a href="seller_dashboard.php" class="dashboard-btn">
                                    <i class="fa fa-tachometer"></i> Dashboard
                                </a>
                            <?php elseif ($user_id): ?>
                                <a href="#" class="dashboard-btn">
                                    <i class="fa fa-user"></i> 
                                </a>
                            <?php else: ?>
                                <a href="login.html" class="dashboard-btn">
                                    <i class="fa fa-sign-in"></i> Login
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <img src="assets/img/svg/star-icon-1.svg" alt="svg">
                        
                        <div class="user-cart d-flex align-center">
                            <div class="cart-icon">
                                <a href="cart.php" style="color: #2e7d32;">
                                    <i class="fa fa-shopping-cart"></i>
                                </a>
                                <span class="text-center" id="cartCounter" style="background: #ff6b35; color: white; border-radius: 50%; padding: 2px 6px; font-size: 12px; font-weight: 700;"><?php echo $cart_count; ?></span>
                            </div>
                            <a href="cart.php" style="color: #2e7d32; text-decoration: none;">
                                <h6 class="fw-600"><span>cart items</span>৳<?php echo number_format($cart_total, 2); ?></h6>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <section class="main-content">
        <div class="container">
           
            <div class="shop-banner">
                <div class="banner-content">
                    <div class="banner-title">
                        <?php if ($search_query): ?>
                            <h2><i class="fa fa-search banner-icon"></i>Search Results for "<span class="search-highlight-banner"><?php echo htmlspecialchars($search_query); ?></span>"</h2>
                            <p class="banner-subtitle">Found <?php echo $result->num_rows; ?> fresh product<?php echo ($result->num_rows !== 1) ? 's' : ''; ?> matching your search criteria from verified agricultural suppliers</p>
                        <?php else: ?>
                            <h2><i class="fa fa-leaf banner-icon"></i>Fresh Agricultural Products</h2>
                            <p class="banner-subtitle">Discover premium quality products directly from verified farmers and agricultural suppliers across Bangladesh</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <form method="GET" id="filterForm">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label style="color: #2e7d32; font-weight: 700;"><i class="fa fa-filter"></i> Category:</label>
                            <select name="category" class="filter-select" onchange="document.getElementById('filterForm').submit();">
                                <option value="">All Categories</option>
                                <?php while ($category = $category_result->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($category['product_category']); ?>" 
                                            <?php echo ($category_filter === $category['product_category']) ? 'selected' : ''; ?>>
                                        <?php echo ucwords(str_replace('_', ' ', $category['product_category'])); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label style="color: #2e7d32; font-weight: 700;"><i class="fa fa-sort"></i> Sort By:</label>
                            <select name="sort" class="filter-select" onchange="document.getElementById('filterForm').submit();">
                                <option value="default" <?php echo ($sort_option === 'default') ? 'selected' : ''; ?>>Latest Added</option>
                                <option value="lowtohigh" <?php echo ($sort_option === 'lowtohigh') ? 'selected' : ''; ?>>Price: Low to High</option>
                                <option value="hightolow" <?php echo ($sort_option === 'hightolow') ? 'selected' : ''; ?>>Price: High to Low</option>
                                <option value="availability" <?php echo ($sort_option === 'availability') ? 'selected' : ''; ?>>Most Available</option>
                                <option value="name_asc" <?php echo ($sort_option === 'name_asc') ? 'selected' : ''; ?>>Name A-Z</option>
                            </select>
                        </div>
                        
                        <?php if ($search_query || $category_filter || $sort_option !== 'default'): ?>
                            <button type="button" class="clear-filters" onclick="clearFilters()">
                                <i class="fa fa-times"></i> Clear
                            </button>
                        <?php endif; ?>
                    </div>
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
                </form>
            </div>

            
            <div class="product-grid">
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($product = $result->fetch_assoc()): ?>
                        <div class="product-card">
                            <div class="product-image-container">
                                <img src="<?php echo htmlspecialchars($product['image_url'] ?? 'assets/img/default-product.jpg'); ?>" 
                                     alt="<?php echo htmlspecialchars($product['product_name']); ?>" 
                                     class="product-image">
                                <div class="quantity-badge">
                                    Available: <?php echo $product['quantity_available']; ?>
                                </div>
                            </div>
                            
                            <div class="product-info">
                                <h5><?php echo htmlspecialchars($product['product_name']); ?></h5>
                                
                                <div class="price-weight-row">
                                    <div class="product-price">
                                        <span class="taka-sign">৳</span><?php echo number_format($product['price'], 2); ?>
                                    </div>
                                    <div class="product-weight">
                                        <?php echo htmlspecialchars($product['weight'] ?? 'per unit'); ?>
                                    </div>
                                </div>
                                
                                <!-- FIXED: Seller Info in COLUMN Layout (name above, verified below) -->
                                <div class="seller-rating-row">
                                    <div class="seller-info-section">
                                        <div class="seller-name"><?php echo htmlspecialchars($product['seller_name'] ?? 'Unknown Seller'); ?></div>
                                        <div class="verified-badge">Seller Verified</div>
                                    </div>
                                    
                                    <div class="rating-section">
                                        <div class="stars">
                                            <i class="fa fa-star"></i>
                                            <i class="fa fa-star"></i>
                                            <i class="fa fa-star"></i>
                                            <i class="fa fa-star"></i>
                                            <i class="fa fa-star-half-o"></i>
                                        </div>
                                        <div class="rating-text">4.5/1500 review</div>
                                    </div>
                                </div>
                                
                                <div class="action-buttons-row">
                                    <button class="add-to-cart-btn" 
                                            onclick="addToCart(<?php echo $product['product_id']; ?>)"
                                            <?php echo ($product['quantity_available'] <= 0) ? 'disabled' : ''; ?>>
                                        <?php echo ($product['quantity_available'] <= 0) ? 'Out of Stock' : 'Add to Cart'; ?>
                                    </button>
                                    <button class="wishlist-btn" onclick="toggleWishlist(<?php echo $product['product_id']; ?>)">
                                        <i class="fa fa-heart"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-products">
                        <i class="fa fa-search"></i>
                        <h3>No Products Found</h3>
                        <p>Try adjusting your search criteria or browse our categories.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Cart Notification Element -->
    <div id="cartNotification" class="cart-notification"></div>

    <!-- JavaScript for functionality -->
    <script>
        function expandSearch() {
            document.getElementById('searchBox').classList.add('expanded');
        }
        
        function clearFilters() {
            window.location.href = 'shop.php';
        }
        
        function addToCart(productId) {
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '<span class="loading"></span> Adding...';
            button.disabled = true;
            
            fetch('add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'product_id=' + productId + '&quantity=1'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Product added to cart successfully!', 'success');
                    updateCartCounter();
                } else {
                    showNotification(data.message || 'Error adding product to cart', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Network error. Please try again.', 'error');
            })
            .finally(() => {
                button.innerHTML = originalText;
                button.disabled = false;
            });
        }
        
        function toggleWishlist(productId) {
            showNotification('Wishlist feature coming soon!', 'info');
        }
        
        function showNotification(message, type = 'success') {
            const notification = document.getElementById('cartNotification');
            notification.className = `cart-notification ${type === 'error' ? 'error' : ''} show`;
            notification.textContent = message;
            
            setTimeout(() => {
                notification.classList.remove('show');
            }, 3000);
        }
        
        function updateCartCounter() {
            fetch('get_cart_count.php')
            .then(response => response.json())
            .then(data => {
                if (data.count !== undefined) {
                    document.getElementById('cartCounter').textContent = data.count;
                }
            })
            .catch(error => {
                console.error('Error updating cart counter:', error);
            });
        }
        
        // Remove preloader when page loads
        window.addEventListener('load', function() {
            const preloader = document.querySelector('.preloader');
            if (preloader) {
                preloader.style.opacity = '0';
                setTimeout(() => {
                    preloader.style.display = 'none';
                }, 500);
            }
        });
        
        // Expand search on focus
        document.addEventListener('DOMContentLoaded', function() {
            const searchBox = document.getElementById('searchBox');
            if (searchBox) {
                searchBox.addEventListener('focus', expandSearch);
            }
        });
    </script>
</body>
</html>



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

    <section class="footer-bottom ">
        <div class="container">
            <div class="row align-center">
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