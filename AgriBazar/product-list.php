<?php
session_start();
require_once "db_connect.php"; // Your DB connection file

// Get parameters (sanitize/filter)
$context = isset($_GET['context']) ? $_GET['context'] : 'shop'; // default 'shop'
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'default';

// For logged in user seller_id (for 'my-products' context)
$seller_id = isset($_SESSION['seller_id']) ? (int)$_SESSION['seller_id'] : null;

// Base SQL and params
$sql = "SELECT * FROM products WHERE 1";
$params = [];
$types = "";

// Modify SQL based on context
if ($context === 'my-products') {
    if (!$seller_id) {
        echo "Please login as seller to see your products.";
        exit;
    }
    $sql .= " AND seller_id = ?";
    $types .= "i";
    $params[] = $seller_id;
}

// Search handling (search both product_name and product_category)
if ($context !== 'my-products' && $search !== '') {
    // Only allow search for non-seller-product context
    $sql .= " AND (product_name LIKE ? OR product_category LIKE ?)";
    $types .= "ss";
    $likeSearch = "%" . $search . "%";
    $params[] = $likeSearch;
    $params[] = $likeSearch;
}

// Filter handling (sorting)
switch ($filter) {
    case 'lowtohigh':
        $sql .= " ORDER BY price ASC";
        break;
    case 'hightolow':
        $sql .= " ORDER BY price DESC";
        break;
    case 'availability':
        // Assuming availability means quantity_available > 0 first, then others
        $sql .= " ORDER BY (quantity_available > 0) DESC, created_at DESC";
        break;
    default:
        // Default: latest products first (created_at desc)
        $sql .= " ORDER BY created_at DESC";
        break;
}

// Prepare and execute statement
$stmt = $conn->prepare($sql);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// For filter dropdown persistence
function selected($value, $current) {
    return $value === $current ? "selected" : "";
}
?>

<section class="available-product pt-80 pb-80" style="background-image: url('assets/img/available-product/available-product-shape2.png');">
    <div class="container">
        <div class="row align-center">
            <div class="col-xxl-6">
                <div class="section-title-area">
                    <div class="section-title">
                        <h3>
                            <?php
                            if ($context === 'my-products') {
                                echo "<span>my</span> products";
                            } else {
                                echo "<span>shop</span> now";
                            }
                            ?>
                            <img src="assets/img/svg/star-icon-3.svg" alt="img">
                        </h3>
                        <p>
                            <?php
                            if ($context === 'my-products') {
                                echo "Manage your uploaded products below.";
                            } else {
                                echo "Discover our handpicked selection of top-rated and trending items. Limited time offers and exclusive deals await!";
                            }
                            ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-xxl-6">
                <div class="product-selection text-right">
                    <form method="get" action="">
                        <input type="hidden" name="context" value="<?php echo htmlspecialchars($context); ?>">
                        <?php if ($context !== 'my-products'): ?>
                            <input type="text" name="search" placeholder="Search products or categories..." value="<?php echo htmlspecialchars($search); ?>" />
                        <?php endif; ?>
                        <select name="filter" onchange="this.form.submit()">
                            <option value="default" <?php echo selected('default', $filter); ?>>Default Sort</option>
                            <option value="lowtohigh" <?php echo selected('lowtohigh', $filter); ?>>Sort by Low to High</option>
                            <option value="hightolow" <?php echo selected('hightolow', $filter); ?>>Sort by High to Low</option>
                            <option value="availability" <?php echo selected('availability', $filter); ?>>Availability</option>
                        </select>
                        <?php if ($context !== 'my-products'): ?>
                            <button type="submit" style="display:none;">Search</button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
        <div class="available-product-wrapper">
            <?php
            if ($result->num_rows === 0) {
                echo "<p>No products found matching your criteria.</p>";
            } else {
                while ($product = $result->fetch_assoc()) {
                    include 'product-card.php';
                }
            }
            ?>
        </div>
    </div>
</section>
