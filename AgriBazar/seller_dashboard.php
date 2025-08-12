<?php
session_start();
include 'db_connect.php'; // your DB connection file

$user_id = $_SESSION['user_id'] ?? null;
$seller_id = $_SESSION['seller_id'] ?? null;

$seller_name = 'John Doe'; 
$profile_image = 'assets/img/seller/hero-1.png'; 

$sort_option = $_GET['sort'] ?? ''; // e.g., 'lowtohigh', 'hightolow', 'availability'

$order_by = "p.product_id DESC"; // default order

if ($sort_option === 'lowtohigh') {
    $order_by = "p.price ASC";
} elseif ($sort_option === 'hightolow') {
    $order_by = "p.price DESC";
} elseif ($sort_option === 'availability') {
    $order_by = "p.quantity_available DESC";
}

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

// Fetch products for this seller with ordering
$sql = "SELECT p.*
        FROM products p
        WHERE p.seller_id = ?
        ORDER BY $order_by";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$result = $stmt->get_result();

if ($seller_id) {
    $sql = "SELECT s.full_name, um.profile_image
            FROM sellers s
            LEFT JOIN user_meta um ON um.user_id = s.seller_id
            WHERE s.seller_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $seller_id);
    $stmt->execute();
    $seller_result = $stmt->get_result();
    $seller = $seller_result->fetch_assoc();

    if ($seller) {
        $seller_name = htmlspecialchars($seller['full_name'] ?? $seller_name);
        $profile_image = !empty($seller['profile_image']) ? $seller['profile_image'] : $profile_image;
    }
}

// Handle product upload form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$seller_id) {
        die("You must be logged in as a seller to upload products.");
    }

    // Basic validation & sanitization
    $product_name = trim($_POST['product_name'] ?? '');
    $weight = floatval($_POST['weight'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 0);
    $category = $_POST['category'] ?? '';
    $description = trim($_POST['product_desc'] ?? '');
    $weight_value = floatval($_POST['weight_value'] ?? 0);
    $weight_unit = $_POST['weight_unit'] ?? '';
    $quantity = intval($_POST['quantity'] ?? 0);
    $price = floatval($_POST['price'] ?? 0);

    if ($price < 0) {
    $error_message = "Price cannot be negative.";
    }

    if ($weight_value <= 0 || $quantity <= 0) {
        $error_message = "Weight and quantity must be positive numbers.";
    } elseif (empty($weight_unit)) {
        $error_message = "Please select a weight unit.";
    } else {
        $weight = $weight_value . ' ' . $weight_unit;
    }
    // Check required fields
    if (empty($product_name) || $weight <= 0 || $quantity <= 0 || empty($category)) {
        $error_message = "Please fill in all required fields correctly.";
    } else {
        // Handle file upload
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['product_image']['tmp_name'];
            $file_name = basename($_FILES['product_image']['name']);
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

            if (!in_array($file_ext, $allowed_ext)) {
                $error_message = "Invalid image format. Allowed: jpg, jpeg, png, gif.";
            } else {
                // Create uploads/products directory if not exists
                $upload_dir = 'uploads/products/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                // Generate unique filename
                $new_file_name = uniqid('prod_', true) . '.' . $file_ext;
                $dest_path = $upload_dir . $new_file_name;

                if (move_uploaded_file($file_tmp, $dest_path)) {
                    // Save product data into DB
                    $insert_sql = "INSERT INTO products 
                        (seller_id, product_name, description, image_url, quantity_available, price, product_category, weight, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                    
                    $stmt = $conn->prepare($insert_sql);
                    $stmt->bind_param(
                        "isssidss",
                        $seller_id,
                        $product_name,
                        $description,
                        $dest_path,
                        $quantity,
                        $price,
                        $category,
                        $weight
                    );

                    if ($stmt->execute()) {
                        $success_message = "Product uploaded successfully.";
                    } else {
                        $error_message = "Database error: " . $stmt->error;
                        // Optionally delete uploaded file if DB insert fails
                        unlink($dest_path);
                    }
                } else {
                    $error_message = "Failed to move uploaded file.";
                }
            }
        } else {
            $error_message = "Please upload a product image.";
        }
    }
    if (isset($success_message)) {
    $_SESSION['success_message'] = $success_message;
    header("Location: notification.php");
    exit;
   }

  if (isset($error_message)) {
    $_SESSION['error_message'] = $error_message;
    header("Location: notification.php");
    exit;
   }

}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>AgriBazar</title>
    <link rel="Icon" href="assets/img/logo.png" />
    <link rel="stylesheet" href="assets/plugins/font-awesome/css/font-awesome.min.css" />
    <link rel="stylesheet" href="assets/css/style.css" />
    <style>
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin: 30px 0;
            flex-wrap: wrap;
        }
        
        .action-btn {
            background: linear-gradient(135deg, #2e7d32 0%, #4caf50 100%);
            color: white;
            padding: 14px 28px;
            border: none;
            border-radius: 25px;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(46, 125, 50, 0.3);
            min-width: 160px;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(46, 125, 50, 0.4);
            background: linear-gradient(135deg, #1b5e20 0%, #388e3c 100%);
        }
        
        .action-btn.active {
            background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
            box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3);
        }
        
        .form-section, .products-section {
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .form-section.active, .products-section.active {
            display: block;
            opacity: 1;
        }
        
        .enhanced-category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .category-option {
            background: linear-gradient(135deg, #e8f5e9 0%, #f1f8e9 100%);
            border-radius: 12px;
            padding: 16px 24px;
            cursor: pointer;
            font-weight: 600;
            color: #2e7d32;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }
        
        .category-option::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(46, 125, 50, 0.1), transparent);
            transition: left 0.5s ease;
        }
        
        .category-option:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 125, 50, 0.2);
            border-color: #4caf50;
        }
        
        .category-option:hover::before {
            left: 100%;
        }
        
        .category-option input[type="radio"] {
            accent-color: #2e7d32;
            width: 18px;
            height: 18px;
        }
        
        .enhanced-input {
            padding: 14px 20px;
            border-radius: 12px;
            border: 2px solid #e0e0e0;
            font-size: 16px;
            transition: all 0.3s ease;
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        }
        
        .enhanced-input:focus {
            outline: none;
            border-color: #4caf50;
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
            transform: translateY(-1px);
        }
        
        .update-form {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 40px;
            border: 1px solid #e8f5e9;
        }
        
        .update-btn {
            background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 20px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(255, 107, 53, 0.3);
        }
        
        .update-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(255, 107, 53, 0.4);
        }
    </style>
</head>

<body>
    <div class="preloader">
        <div class="spinner">
            <div class="ring ring1"></div>
            <div class="ring ring2"></div>
            <div class="ring ring3"></div>
        </div>
    </div>

    <section class="header custom-header">
        <div class="container">
            <div class="row align-center">
                <div class="col-xxl-2">
                    <div class="logo">
                        <a href="index.php">
                            <img src="assets/img/logo.png" alt="img" />
                        </a>
                    </div>
                </div>
                <div class="col-xxl-6">
                    <ul class="menu custom-menu d-flex align-center text-capitalize flex-wrap justify-center">
                        <li><a href="index.php">home</a></li>
                        <li><a href="#">about</a></li>
                        <li><a href="shop.php">shop</a></li>
                        <li><a href="#">blog</a></li>
                        <li><a href="#">contact</a></li>
                    </ul>
                </div>
                <div class="col-xxl-4">
                    <div class="header-right d-flex justify-space-between align-center">
                        <div class="wish-list text-center">
                            <a href="#">
                                <i class="fa-regular fa-heart"></i>
                            </a>
                        </div>
                        <img src="assets/img/svg/star-icon-1.svg" alt="svg" />
                        <div class="user-account">
                            <?php if ($seller_id): ?>
                                <a href="logout.php" class="d-flex align-center">
                                    <i class="fa-solid fa-user-minus"></i><br />
                                    <h6 class="fw-600"><span>Log out</span>account</h6>
                                </a>
                            <?php else: ?>
                                <img src="assets/img/svg/star-icon-1.svg" alt="svg" />
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
                        <img src="assets/img/svg/star-icon-1.svg" alt="svg" />
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
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="banner-area" style="background-image: url('assets/img/login-register-page/banner-bg.jpg');">
        <div class="container">
            <div class="row align-center">
                <div class="col-xxl-7">
                    <div class="banner-content">
                        <div class="banner-title">
                            <h1 class="text-capitalize"><span>seller</span> dashboard</h1>
                        </div>
                        <div class="banner-path d-flex align-center">
                            <a href="index.html">home</a>
                            <span>-</span>
                            <a href="login.html">dashboard</a>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-5">
                    <div class="banner-img">
                        <img src="assets/img/login-register-page/banner.png" alt="img" />
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="seller-dashboard pt-80 pb-80">
        <div class="container">
            <div class="seller-dashboard-wrapper"
                style="background-image: url('assets/img/login-register-page/login-bg.jpg'); background-size: cover; background-position: center; padding: 40px; border-radius: 12px; text-align: center;">

                <div class="profile-pic-wrapper"
                    style="margin-bottom: 5px; position: relative; display: inline-block; width: 180px; height: 180px;">
                    <div class="pic-holder" style="position: relative; width: 180px; height: 180px; cursor: pointer;">
                        <img id="profilePic" class="pic" src="<?= $profile_image ?>" alt="Seller Profile"
                            style="width: 180px; height: 180px; border-radius: 50%; object-fit: cover; border: 4px solid #2e7d32; display: block;" />

                        <label for="newProfilePhoto" style="
                            position: absolute;
                            top: 0;
                            left: 0;
                            width: 180px;
                            height: 180px;
                            border-radius: 50%;
                            background-color: rgba(46, 125, 50, 0.75);
                            color: white;
                            font-size: 12px;
                            font-weight: 600;
                            display: flex;
                            flex-direction: column;
                            justify-content: center;
                            align-items: center;
                            opacity: 0;
                            transition: opacity 0.3s ease;
                            user-select: none;
                            text-align: center;
                            padding: 0 8px;
                        ">
                            <i class="fa fa-camera fa-lg" style="margin-bottom: 6px;"></i>
                            Click here to change <br /> your profile photo
                        </label>

                        <input class="uploadProfileInput" type="file" name="profile_pic" id="newProfilePhoto"
                            accept="image/*"
                            style="opacity: 0; position: absolute; top: 0; left: 0; width: 180px; height: 180px; cursor: pointer; border-radius: 50%;" />
                    </div>
                </div>

                <h3 style="margin: 8px 0 14px; font-weight: 700; color: #2e7d32; font-size: 22px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
                    <?= $seller_name ?>
                </h3>

                <div style="display: inline-flex; align-items: center; gap: 6px; background-color: #ffeb3b; color: #5d4037; font-weight: 600; font-size: 13px; padding: 5px 14px; border-radius: 20px; box-shadow: 0 2px 6px rgba(0,0,0,0.12); user-select: none;">
                    <i class="fa fa-star" style="color: #fb4c2d;"></i> Top Rated Seller
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <button class="action-btn" id="uploadBtn" onclick="showSection('upload')">
                    <i class="fa fa-plus" style="margin-right: 8px;"></i>Upload New Product
                </button>
                <button class="action-btn active" id="productsBtn" onclick="showSection('products')">
                    <i class="fa fa-list" style="margin-right: 8px;"></i>Show My Products
                </button>
            </div>

            <!-- Upload Form Section -->
            <div class="form-section" id="uploadSection">
                <div class="update-form">
                    <form action="" method="post" enctype="multipart/form-data">
                        <h4 style="font-weight: 700; font-size: 28px; color: #2e7d32; margin-bottom: 40px; text-align: center;">
                            <i class="fa fa-upload" style="margin-right: 10px;"></i>Upload New Product for Sale
                        </h4>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 30px;">
                            <div>
                                <label style="display: block; margin-bottom: 10px; font-weight: 600; color: #2e7d32; font-size: 16px;">
                                    <i class="fa fa-image" style="margin-right: 8px;"></i>Upload Product Image
                                </label>
                                <input type="file" name="product_image" class="enhanced-input" required accept="image/*" style="width: 100%;" />
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 10px; font-weight: 600; color: #2e7d32; font-size: 16px;">
                                    <i class="fa fa-tag" style="margin-right: 8px;"></i>Product Name
                                </label>
                                <input type="text" name="product_name" placeholder="Enter product name" class="enhanced-input" required style="width: 100%;" />
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 25px; margin-bottom: 30px;">
                            <div>
                                <label style="display: block; margin-bottom: 10px; font-weight: 600; color: #2e7d32; font-size: 16px;">
                                    <i class="fa fa-balance-scale" style="margin-right: 8px;"></i>Weight
                                </label>
                                <input type="number" name="weight_value" placeholder="Weight" min="0.01" step="0.01" class="enhanced-input" required style="width: 100%;" />
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 10px; font-weight: 600; color: #2e7d32; font-size: 16px;">
                                    <i class="fa fa-cube" style="margin-right: 8px;"></i>Unit
                                </label>
                                <select name="weight_unit" class="enhanced-input" required style="width: 100%;">
                                    <option value="" disabled selected>Select unit</option>
                                    <option value="gm">gm</option>
                                    <option value="kg">kg</option>
                                    <option value="hali">hali</option>
                                    <option value="dozen">dozen</option>
                                    <option value="liter">liter</option>
                                    <option value="pices">pices</option>
                                </select>
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 10px; font-weight: 600; color: #2e7d32; font-size: 16px;">
                                    <i class="fa fa-sort-numeric-asc" style="margin-right: 8px;"></i>Quantity
                                </label>
                                <input type="number" name="quantity" placeholder="Available quantity" min="1" class="enhanced-input" required style="width: 100%;" />
                            </div>
                        </div>

                        <div style="margin-bottom: 40px;">
                            <label style="display: block; margin-bottom: 10px; font-weight: 600; color: #2e7d32; font-size: 16px;">
                                <i class="fa fa-money" style="margin-right: 8px;"></i>Price (BDT)
                            </label>
                            <input type="number" name="price" placeholder="Enter price in BDT" min="0" step="0.01" class="enhanced-input" required style="width: 100%; max-width: 300px;" />
                        </div>

                        <div style="margin-bottom: 40px;">
                            <h4 style="font-weight: 700; color: #2e7d32; margin-bottom: 20px; font-size: 20px;">
                                <i class="fa fa-sitemap" style="margin-right: 10px;"></i>Select Product Category
                            </h4>
                            <div class="enhanced-category-grid">
                                <label class="category-option">
                                    <input type="radio" name="category" value="" required />
                                    <i class="fa fa-hand-pointer-o" style="color: #f39c12;"></i>
                                    Select Category
                                </label>
                                <label class="category-option">
                                    <input type="radio" name="category" value="vegetables" required />
                                    <i class="fa fa-leaf" style="color: #27ae60;"></i>
                                    Vegetables
                                </label>
                                <label class="category-option">
                                    <input type="radio" name="category" value="fruits" required />
                                    <i class="fa fa-apple" style="color: #e74c3c;"></i>
                                    Fruits
                                </label>
                                <label class="category-option">
                                    <input type="radio" name="category" value="grains_cereals" required />
                                    <i class="fa fa-grain" style="color: #f39c12;"></i>
                                    Grains & Cereals
                                </label>
                                <label class="category-option">
                                    <input type="radio" name="category" value="spices_herbs" required />
                                    <i class="fa fa-cutlery" style="color: #d35400;"></i>
                                    Spices & Herbs
                                </label>
                                <label class="category-option">
                                    <input type="radio" name="category" value="oil_essentials" required />
                                    <i class="fa fa-tint" style="color: #f1c40f;"></i>
                                    Oil & Cooking Essentials
                                </label>
                                <label class="category-option">
                                    <input type="radio" name="category" value="fertilizers_seeds" required />
                                    <i class="fa fa-seedling" style="color: #2ecc71;"></i>
                                    Fertilizers & Seeds
                                </label>
                                <label class="category-option">
                                    <input type="radio" name="category" value="dairy_animal_products" required />
                                    <i class="fa fa-glass" style="color: #3498db;"></i>
                                    Dairy & Animal Products
                                </label>
                                <label class="category-option">
                                    <input type="radio" name="category" value="organic_deshi" required />
                                    <i class="fa fa-certificate" style="color: #27ae60;"></i>
                                    Organic & Deshi Products
                                </label>
                                <label class="category-option">
                                    <input type="radio" name="category" value="others" required />
                                    <i class="fa fa-ellipsis-h" style="color: #95a5a6;"></i>
                                    Others
                                </label>
                            </div>
                        </div>

                        <div style="margin-bottom: 40px;">
                            <label style="display: block; margin-bottom: 10px; font-weight: 600; color: #2e7d32; font-size: 16px;">
                                <i class="fa fa-edit" style="margin-right: 8px;"></i>Product Description
                            </label>
                            <textarea name="product_desc" placeholder="Describe your product in detail..." rows="6" 
                                style="width: 100%; padding: 16px; border-radius: 12px; border: 2px solid #e0e0e0; font-size: 16px; resize: vertical; font-family: inherit;"></textarea>
                        </div>

                        <div style="text-align: center;">
                            <button type="submit" class="action-btn" style="min-width: 200px; font-size: 18px;">
                                <i class="fa fa-upload" style="margin-right: 10px;"></i>Upload Product
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Products Section -->
            <div class="products-section active" id="productsSection">
                <section class="my-product pt-40 pb-80" style="background-image: url('assets/img/available-product/available-product-shape2.png');">
                    <div class="container">
                        <div class="row align-center">
                            <div class="col-xxl-6">
                                <div class="section-title-area">
                                    <div class="section-title">
                                        <h3><span>my</span> products<img src="assets/img/svg/star-icon-3.svg" alt="img"></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xxl-6">
                                <div class="product-selection text-right">
                                    <form method="GET" action="">
                                        <select name="sort" onchange="this.form.submit()" class="text-right" style="padding: 10px; border-radius: 8px; border: 2px solid #e0e0e0;">
                                            <option value="" class="text-capitalize" <?= $sort_option == '' ? 'selected' : '' ?>>Default Sort</option>
                                            <option value="lowtohigh" class="text-capitalize" <?= $sort_option == 'lowtohigh' ? 'selected' : '' ?>>Low to High</option>
                                            <option value="hightolow" class="text-capitalize" <?= $sort_option == 'hightolow' ? 'selected' : '' ?>>High to Low</option>
                                            <option value="availability" class="text-capitalize" <?= $sort_option == 'availability' ? 'selected' : '' ?>>Availability</option>
                                        </select>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="my-product-wrapper">
                            <?php
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    // Prepare image URLs and safe output - Use database image
                                    $productImage = !empty($row['image_url']) ? htmlspecialchars($row['image_url']) : 'assets/img/available-product/available-product.jpg';
                                    $weight = htmlspecialchars($row['weight'] ?? 'N/A');
                                    $productName = htmlspecialchars($row['product_name']);
                                    $price = number_format($row['price'], 2);
                                    $productId = $row['product_id'];
                            ?>
                                    <!-- Product card start -->
                                    <div class="single-my-product" style="background-image: url('assets/img/available-product/available-product-shape.png');">
                                        <div class="my-product-img">
                                            <a href="#">
                                                <img src="<?= $productImage ?>" alt="img">
                                            </a>
                                        </div>
                                        <div class="my-product-content">
                                            <span><?= $weight ?></span>
                                            <h5>
                                                <a href="#"><?= $productName ?></a>
                                            </h5>
                                        </div>
                                        <div class="my-product-rating">
                                            <i class="fa-solid fa-star"></i>
                                            <i class="fa-solid fa-star"></i>
                                            <i class="fa-solid fa-star"></i>
                                            <i class="fa-solid fa-star"></i>
                                            <i class="fa-solid fa-star"></i>
                                        </div>
                                        <div class="my-product-price">
                                            <span>৳<?= $price ?></span>
                                        </div>
                                        <div class="my-product-btn">
                                            <button class="update-btn" onclick="showUpdateForm(<?= $productId ?>)">
                                                <i class="fa fa-edit" style="margin-right: 5px;"></i>Update
                                            </button>
                                        </div>
                                    </div>
                                    <!-- Product card end -->
                            <?php
                                }
                            } else {
                                echo "<div style='text-align: center; padding: 60px; color: #666; font-size: 18px;'>
                                        <i class='fa fa-box-open' style='font-size: 48px; margin-bottom: 20px; color: #ccc;'></i>
                                        <p>No products found. Upload your first product to get started!</p>
                                      </div>";
                            }

                            $stmt->close();
                            $conn->close();
                            ?>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Update Product Form Modal -->
            <div id="updateModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; overflow-y: auto;">
                <div style="background: white; margin: 50px auto; max-width: 800px; border-radius: 20px; padding: 40px; position: relative;">
                    <button onclick="closeUpdateForm()" style="position: absolute; top: 20px; right: 20px; background: none; border: none; font-size: 24px; cursor: pointer; color: #666;">
                        <i class="fa fa-times"></i>
                    </button>
                    
                    <h4 style="font-weight: 700; font-size: 28px; color: #2e7d32; margin-bottom: 30px; text-align: center;">
                        <i class="fa fa-edit" style="margin-right: 10px;"></i>Update Product
                    </h4>
                    
                    <form id="updateProductForm" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="product_id" id="updateProductId">
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 30px;">
                            <div>
                                <label style="display: block; margin-bottom: 10px; font-weight: 600; color: #2e7d32; font-size: 16px;">
                                    <i class="fa fa-image" style="margin-right: 8px;"></i>Product Image (optional)
                                </label>
                                <input type="file" name="product_image" class="enhanced-input" accept="image/*" style="width: 100%;" />
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 10px; font-weight: 600; color: #2e7d32; font-size: 16px;">
                                    <i class="fa fa-tag" style="margin-right: 8px;"></i>Product Name
                                </label>
                                <input type="text" name="product_name" id="updateProductName" class="enhanced-input" required style="width: 100%;" />
                            </div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 25px; margin-bottom: 30px;">
                            <div>
                                <label style="display: block; margin-bottom: 10px; font-weight: 600; color: #2e7d32; font-size: 16px;">
                                    <i class="fa fa-balance-scale" style="margin-right: 8px;"></i>Weight
                                </label>
                                <input type="number" name="weight_value" id="updateWeight" min="0.01" step="0.01" class="enhanced-input" required style="width: 100%;" />
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 10px; font-weight: 600; color: #2e7d32; font-size: 16px;">
                                    <i class="fa fa-cube" style="margin-right: 8px;"></i>Unit
                                </label>
                                <select name="weight_unit" id="updateWeightUnit" class="enhanced-input" required style="width: 100%;">
                                    <option value="gm">gm</option>
                                    <option value="kg">kg</option>
                                </select>
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 10px; font-weight: 600; color: #2e7d32; font-size: 16px;">
                                    <i class="fa fa-sort-numeric-asc" style="margin-right: 8px;"></i>Quantity
                                </label>
                                <input type="number" name="quantity" id="updateQuantity" min="1" class="enhanced-input" required style="width: 100%;" />
                            </div>
                        </div>
                        
                        <div style="margin-bottom: 30px;">
                            <label style="display: block; margin-bottom: 10px; font-weight: 600; color: #2e7d32; font-size: 16px;">
                                <i class="fa fa-money" style="margin-right: 8px;"></i>Price (BDT)
                            </label>
                            <input type="number" name="price" id="updatePrice" min="0" step="0.01" class="enhanced-input" required style="width: 100%; max-width: 300px;" />
                        </div>
                        
                        <div style="text-align: center;">
                            <button type="submit" class="action-btn" style="min-width: 200px;">
                                <i class="fa fa-save" style="margin-right: 8px;"></i>Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
            const picHolder = document.querySelector('.pic-holder');
            const overlayLabel = picHolder.querySelector('label[for="newProfilePhoto"]');

            picHolder.addEventListener('mouseenter', () => {
                overlayLabel.style.opacity = '1';
            });
            picHolder.addEventListener('mouseleave', () => {
                overlayLabel.style.opacity = '0';
            });

            function showSection(section) {
                const uploadSection = document.getElementById('uploadSection');
                const productsSection = document.getElementById('productsSection');
                const uploadBtn = document.getElementById('uploadBtn');
                const productsBtn = document.getElementById('productsBtn');

                if (section === 'upload') {
                    uploadSection.classList.add('active');
                    productsSection.classList.remove('active');
                    uploadBtn.classList.add('active');
                    productsBtn.classList.remove('active');
                } else {
                    uploadSection.classList.remove('active');
                    productsSection.classList.add('active');
                    uploadBtn.classList.remove('active');
                    productsBtn.classList.add('active');
                }
            }

            function showUpdateForm(productId) {
                document.getElementById('updateModal').style.display = 'block';
                document.getElementById('updateProductId').value = productId;
                // Here you would typically fetch the product data and populate the form
                // For now, we'll leave the form empty to be filled by the user
            }

            function closeUpdateForm() {
                document.getElementById('updateModal').style.display = 'none';
            }

            // Close modal when clicking outside
            document.getElementById('updateModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    closeUpdateForm();
                }
            });

            // Preloader
            window.addEventListener('load', function() {
                document.querySelector('.preloader').style.display = 'none';
            });
        </script>
    </section>
</body>

</html>

    <!-- Feature Area -->
    <section class="feature custom-feature pt-20 pb-20">
        <div class="container">
            <div class="row">
                <div class="col-xxl-3">
                    <div class="single-feature">
                        <div class="feature-img">
                            <img src="assets/img/feature/feature-2.jpg" alt="img">
                        </div>
                        <div class="feature-content">
                            <h5>free shiping</h5>
                            <p>shipping on your order. no extra charges for delivery.</p>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-3">
                    <div class="single-feature">
                        <div class="feature-img">
                            <img src="assets/img/feature/feature-3.jpg" alt="img">
                        </div>
                        <div class="feature-content">
                            <h5>secure payments</h5>
                            <p>our financial information is protected</p>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-3">
                    <div class="single-feature">
                        <div class="feature-img">
                            <img src="assets/img/feature/feature-4.jpg" alt="img">
                        </div>
                        <div class="feature-content">
                            <h5>24 hour support</h5>
                            <p>we're here to help whenever you need us.</p>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-3">
                    <div class="single-feature">
                        <div class="feature-img">
                            <img src="assets/img/feature/feature-5.jpg" alt="img">
                        </div>
                        <div class="feature-content">
                            <h5>best prices & offers</h5>
                            <p>provides unbeatable deals on a wide range of products</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- /Feature Area -->


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


        //Profile Photo Update
        document.addEventListener("change", function(event) {
            if (event.target.classList.contains("uploadProfileInput")) {
                var triggerInput = event.target;
                var currentImg = triggerInput.closest(".pic-holder").querySelector(".pic")
                    .src;
                var holder = triggerInput.closest(".pic-holder");
                var wrapper = triggerInput.closest(".profile-pic-wrapper");

                var alerts = wrapper.querySelectorAll('[role="alert"]');
                alerts.forEach(function(alert) {
                    alert.remove();
                });

                triggerInput.blur();
                var files = triggerInput.files || [];
                if (!files.length || !window.FileReader) {
                    return;
                }

                if (/^image/.test(files[0].type)) {
                    var reader = new FileReader();
                    reader.readAsDataURL(files[0]);

                    reader.onloadend = function() {
                        holder.classList.add("uploadInProgress");
                        holder.querySelector(".pic").src = this.result;

                        var loader = document.createElement("div");
                        loader.classList.add("upload-loader");
                        loader.innerHTML =
                            '<div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div>';
                        holder.appendChild(loader);

                        setTimeout(function() {
                            holder.classList.remove("uploadInProgress");
                            loader.remove();

                            var random = Math.random();
                            if (random < 0.9) {
                                wrapper.innerHTML +=
                                    '<div class="snackbar show" role="alert"><i class="fa fa-check-circle text-success"></i> Profile image updated successfully</div>';
                                triggerInput.value = "";
                                setTimeout(function() {
                                    wrapper.querySelector('[role="alert"]').remove();
                                }, 3000);
                            } else {
                                holder.querySelector(".pic").src = currentImg;
                                wrapper.innerHTML +=
                                    '<div class="snackbar show" role="alert"><i class="fa fa-times-circle text-danger"></i> There is an error while uploading! Please try again later.</div>';
                                triggerInput.value = "";
                                setTimeout(function() {
                                    wrapper.querySelector('[role="alert"]').remove();
                                }, 3000);
                            }
                        }, 1500);
                    };
                } else {
                    wrapper.innerHTML +=
                        '<div class="alert alert-danger d-inline-block p-2 small" role="alert">Please choose a valid image.</div>';
                    setTimeout(function() {
                        var invalidAlert = wrapper.querySelector('[role="alert"]');
                        if (invalidAlert) {
                            invalidAlert.remove();
                        }
                    }, 3000);
                }
            }
        });
    </script>






</body>

</html>