<?php
session_start();
include 'db_connect.php';

header('Content-Type: application/json');

$user_id = $_SESSION['consumer_id'] ?? $_SESSION['seller_id'] ?? null;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Please login to process payment']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $card_number = $_POST['card_number'] ?? '';
    $card_expiry = $_POST['card_expiry'] ?? '';
    $card_cvc = $_POST['card_cvc'] ?? '';
    $card_name = $_POST['card_name'] ?? '';
    
    // Basic validation
    if (empty($card_number) || empty($card_expiry) || empty($card_cvc) || empty($card_name)) {
        echo json_encode(['success' => false, 'message' => 'Please fill all payment fields']);
        exit;
    }
    
    try {
        // Get cart items for current user
        $cart_sql = "SELECT c.cart_item_id, c.quantity, c.product_id, p.product_name, p.price, p.quantity_available
                     FROM cart_items c 
                     JOIN products p ON c.product_id = p.product_id 
                     WHERE c.user_id = ?";
        $stmt = $conn->prepare($cart_sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $cart_result = $stmt->get_result();
        
        if ($cart_result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Cart is empty']);
            exit;
        }
        
        // Start transaction
        $conn->begin_transaction();
        
        $total_amount = 0;
        $order_items = [];
        
        // Create out_of_stock_products table if it doesn't exist
        $check_out_of_stock_table = "SHOW TABLES LIKE 'out_of_stock_products'";
        $out_of_stock_result = $conn->query($check_out_of_stock_table);
        
        if ($out_of_stock_result->num_rows === 0) {
            $create_out_of_stock_table = "CREATE TABLE out_of_stock_products (
                out_of_stock_id INT AUTO_INCREMENT PRIMARY KEY,
                product_id INT NOT NULL,
                product_name VARCHAR(255) NOT NULL,
                description TEXT,
                price DECIMAL(10,2) NOT NULL,
                product_category VARCHAR(100),
                weight VARCHAR(50),
                image_url VARCHAR(500),
                seller_id INT NOT NULL,
                original_quantity INT DEFAULT 0,
                sold_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            $conn->query($create_out_of_stock_table);
        }

        // Process each cart item
        while ($item = $cart_result->fetch_assoc()) {
            // Check if sufficient stock is available
            if ($item['quantity_available'] < $item['quantity']) {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => "Insufficient stock for {$item['product_name']}. Available: {$item['quantity_available']}, Requested: {$item['quantity']}"]);
                exit;
            }
            
            $subtotal = $item['price'] * $item['quantity'];
            $total_amount += $subtotal;
            
            // Calculate new quantity after purchase
            $new_quantity = $item['quantity_available'] - $item['quantity'];
            
            if ($new_quantity <= 0) {
                // Product is sold out - move to out_of_stock_products table
                
                // First, get complete product details
                $product_details_sql = "SELECT * FROM products WHERE product_id = ?";
                $product_stmt = $conn->prepare($product_details_sql);
                $product_stmt->bind_param("i", $item['product_id']);
                $product_stmt->execute();
                $product_details = $product_stmt->get_result()->fetch_assoc();
                
                // Insert into out_of_stock_products table
                $insert_out_of_stock_sql = "INSERT INTO out_of_stock_products 
                    (product_id, product_name, description, price, product_category, weight, image_url, seller_id, original_quantity, sold_date) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                $out_of_stock_stmt = $conn->prepare($insert_out_of_stock_sql);
                $out_of_stock_stmt->bind_param("issdssiii", 
                    $product_details['product_id'],
                    $product_details['product_name'],
                    $product_details['description'],
                    $product_details['price'],
                    $product_details['product_category'],
                    $product_details['weight'],
                    $product_details['image_url'],
                    $product_details['seller_id'],
                    $product_details['quantity_available'] // Original quantity before this purchase
                );
                $out_of_stock_stmt->execute();
                
                // Delete product from main products table
                $delete_product_sql = "DELETE FROM products WHERE product_id = ?";
                $delete_stmt = $conn->prepare($delete_product_sql);
                $delete_stmt->bind_param("i", $item['product_id']);
                $delete_stmt->execute();
                
            } else {
                // Update product quantity (reduce stock)
                $update_product_sql = "UPDATE products SET quantity_available = ? WHERE product_id = ?";
                $update_stmt = $conn->prepare($update_product_sql);
                $update_stmt->bind_param("ii", $new_quantity, $item['product_id']);
                $update_stmt->execute();
            }
            
            $order_items[] = [
                'product_id' => $item['product_id'],
                'product_name' => $item['product_name'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'subtotal' => $subtotal,
                'stock_status' => ($new_quantity <= 0) ? 'sold_out' : 'in_stock'
            ];
        }
        
        // Create order record (optional - you can create an orders table for this)
        $order_sql = "INSERT INTO orders (user_id, total_amount, payment_method, order_status, created_at) 
                      VALUES (?, ?, 'Credit Card', 'Completed', NOW())";
        $order_stmt = $conn->prepare($order_sql);
        $order_stmt->bind_param("id", $user_id, $total_amount);
        
        // Check if orders table exists, if not create it
        $check_table = "SHOW TABLES LIKE 'orders'";
        $table_result = $conn->query($check_table);
        
        if ($table_result->num_rows === 0) {
            // Create orders table
            $create_orders_table = "CREATE TABLE orders (
                order_id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                total_amount DECIMAL(10,2) NOT NULL,
                payment_method VARCHAR(50) DEFAULT 'Credit Card',
                order_status VARCHAR(50) DEFAULT 'Completed',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            $conn->query($create_orders_table);
        }
        
        $order_stmt->execute();
        $order_id = $conn->insert_id;
        
        // Clear cart after successful order
        $clear_cart_sql = "DELETE FROM cart_items WHERE user_id = ?";
        $clear_stmt = $conn->prepare($clear_cart_sql);
        $clear_stmt->bind_param("i", $user_id);
        $clear_stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Payment successful! Order has been placed.',
            'order_id' => $order_id,
            'total_amount' => $total_amount,
            'order_items' => $order_items
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Payment processing error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>