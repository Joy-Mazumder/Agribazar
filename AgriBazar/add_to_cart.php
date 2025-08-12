<?php
// add_to_cart.php
session_start();
include 'db_connect.php';

header('Content-Type: application/json');

// Check if user is logged in
$user_id = $_SESSION['consumer_id'] ?? $_SESSION['seller_id'] ?? null;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Please login to add items to cart']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity'] ?? 1);
    
    if ($product_id <= 0 || $quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product or quantity']);
        exit;
    }
    
    try {
        // Check if product exists and has sufficient quantity
        $product_check = "SELECT product_name, quantity_available, price FROM products WHERE product_id = ?";
        $stmt = $conn->prepare($product_check);
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $product_result = $stmt->get_result();
        
        if ($product_result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            exit;
        }
        
        $product = $product_result->fetch_assoc();
        
        if ($product['quantity_available'] < $quantity) {
            echo json_encode(['success' => false, 'message' => 'Insufficient stock available']);
            exit;
        }
        
        // Check if item already exists in cart
        $cart_check = "SELECT cart_item_id, quantity FROM cart_items WHERE user_id = ? AND product_id = ?";
        $stmt = $conn->prepare($cart_check);
        $stmt->bind_param("ii", $user_id, $product_id);
        $stmt->execute();
        $cart_result = $stmt->get_result();
        
        if ($cart_result->num_rows > 0) {
            // Update existing cart item
            $cart_item = $cart_result->fetch_assoc();
            $new_quantity = $cart_item['quantity'] + $quantity;
            
            if ($new_quantity > $product['quantity_available']) {
                echo json_encode(['success' => false, 'message' => 'Cannot add more items. Stock limit reached.']);
                exit;
            }
            
            $update_sql = "UPDATE cart_items SET quantity = ? WHERE cart_item_id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("ii", $new_quantity, $cart_item['cart_item_id']);
            $stmt->execute();
        } else {
            // Insert new cart item
            $insert_sql = "INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("iii", $user_id, $product_id, $quantity);
            $stmt->execute();
        }
        
        // Get updated cart count and total
        $cart_sql = "SELECT COUNT(DISTINCT c.product_id) as count, SUM(c.quantity * p.price) as total 
                     FROM cart_items c 
                     LEFT JOIN products p ON c.product_id = p.product_id 
                     WHERE c.user_id = ?";
        $stmt = $conn->prepare($cart_sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $cart_result = $stmt->get_result();
        $cart_data = $cart_result->fetch_assoc();
        
        echo json_encode([
            'success' => true, 
            'message' => $product['product_name'] . ' added to cart successfully!',
            'cart_count' => $cart_data['count'] ?? 0,
            'cart_total' => number_format($cart_data['total'] ?? 0, 2)
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error adding item to cart: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>