<?php
session_start();
include 'db_connect.php';

header('Content-Type: application/json');

$user_id = $_SESSION['consumer_id'] ?? $_SESSION['seller_id'] ?? null;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Please login to update cart']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'update_quantity':
                $cart_id = intval($_POST['cart_id']);
                $quantity = intval($_POST['quantity']);
                
                if ($cart_id <= 0 || $quantity <= 0) {
                    echo json_encode(['success' => false, 'message' => 'Invalid cart item or quantity']);
                    exit;
                }
                
                // Check if cart item belongs to user and get product info
                $check_sql = "SELECT c.cart_item_id, p.product_name, p.quantity_available 
                             FROM cart_items c 
                             JOIN products p ON c.product_id = p.product_id 
                             WHERE c.cart_item_id = ? AND c.user_id = ?";
                $stmt = $conn->prepare($check_sql);
                $stmt->bind_param("ii", $cart_id, $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 0) {
                    echo json_encode(['success' => false, 'message' => 'Cart item not found']);
                    exit;
                }
                
                $cart_item = $result->fetch_assoc();
                
                if ($quantity > $cart_item['quantity_available']) {
                    echo json_encode(['success' => false, 'message' => 'Quantity exceeds available stock']);
                    exit;
                }
                
                // Update quantity
                $update_sql = "UPDATE cart_items SET quantity = ? WHERE cart_item_id = ?";
                $stmt = $conn->prepare($update_sql);
                $stmt->bind_param("ii", $quantity, $cart_id);
                $stmt->execute();
                
                echo json_encode(['success' => true, 'message' => 'Quantity updated successfully']);
                break;
                
            case 'remove_item':
                $cart_id = intval($_POST['cart_id']);
                
                if ($cart_id <= 0) {
                    echo json_encode(['success' => false, 'message' => 'Invalid cart item']);
                    exit;
                }
                
                // Check if cart item belongs to user
                $check_sql = "SELECT cart_item_id FROM cart_items WHERE cart_item_id = ? AND user_id = ?";
                $stmt = $conn->prepare($check_sql);
                $stmt->bind_param("ii", $cart_id, $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 0) {
                    echo json_encode(['success' => false, 'message' => 'Cart item not found']);
                    exit;
                }
                
                // Remove item
                $delete_sql = "DELETE FROM cart_items WHERE cart_item_id = ?";
                $stmt = $conn->prepare($delete_sql);
                $stmt->bind_param("i", $cart_id);
                $stmt->execute();
                
                echo json_encode(['success' => true, 'message' => 'Item removed from cart']);
                break;
                
            case 'empty_cart':
                // Remove all items for current user
                $delete_sql = "DELETE FROM cart_items WHERE user_id = ?";
                $stmt = $conn->prepare($delete_sql);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                
                echo json_encode(['success' => true, 'message' => 'Cart emptied successfully']);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                break;
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error updating cart: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>