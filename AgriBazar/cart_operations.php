<?php
session_start();
include '../db_connect.php';

// Enable strict error reporting for MySQLi
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Check if user is logged in
$user_id = $_SESSION['consumer_id'] ?? $_SESSION['seller_id'] ?? null;

// For testing/demo purposes
if (!$user_id) {
    $user_id = 101;
    $_SESSION['consumer_id'] = $user_id;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['message'] = "Invalid request method.";
    header("Location: ../cart.php");
    exit;
}

$action = $_POST['action'] ?? '';
$product_id = intval($_POST['product_id'] ?? 0);

try {
    if ($action === 'add_to_cart' && $product_id > 0) {
        // Check if product exists
        $stmt = $conn->prepare("SELECT product_id, product_name, price, quantity_available FROM products WHERE product_id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();

        if (!$product) {
            $_SESSION['message'] = "Product not found.";
            header("Location: ../products.php");
            exit;
        }

        if ($product['quantity_available'] <= 0) {
            $_SESSION['message'] = "Product is out of stock.";
            header("Location: ../products.php");
            exit;
        }

        // Check if already in cart
        $stmt = $conn->prepare("SELECT cart_item_id, quantity FROM cart_items WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $user_id, $product_id);
        $stmt->execute();
        $existing_item = $stmt->get_result()->fetch_assoc();

        if ($existing_item) {
            $new_quantity = $existing_item['quantity'] + 1;
            if ($new_quantity > $product['quantity_available']) {
                $_SESSION['message'] = "Cannot add more items. Stock limit reached.";
                header("Location: ../cart.php");
                exit;
            }
            $stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE cart_item_id = ?");
            $stmt->bind_param("ii", $new_quantity, $existing_item['cart_item_id']);
            $stmt->execute();
        } else {
            $stmt = $conn->prepare("INSERT INTO cart_items (user_id, product_id, quantity, added_at) VALUES (?, ?, 1, NOW())");
            $stmt->bind_param("ii", $user_id, $product_id);
            $stmt->execute();
        }

        $_SESSION['message'] = "{$product['product_name']} added to cart!";
        header("Location: ../cart.php");
        exit;

    } elseif ($action === 'update_quantity') {
        $cart_item_id = intval($_POST['cart_item_id'] ?? 0);
        $quantity = intval($_POST['quantity'] ?? 0);

        if ($cart_item_id <= 0 || $quantity <= 0) {
            $_SESSION['message'] = "Invalid parameters.";
            header("Location: ../cart.php");
            exit;
        }

        $stmt = $conn->prepare("SELECT c.cart_item_id, p.product_id, p.product_name, p.quantity_available 
                                FROM cart_items c 
                                JOIN products p ON c.product_id = p.product_id 
                                WHERE c.cart_item_id = ? AND c.user_id = ?");
        $stmt->bind_param("ii", $cart_item_id, $user_id);
        $stmt->execute();
        $cart_item = $stmt->get_result()->fetch_assoc();

        if (!$cart_item) {
            $_SESSION['message'] = "Cart item not found.";
            header("Location: ../cart.php");
            exit;
        }

        if ($quantity > $cart_item['quantity_available']) {
            $_SESSION['message'] = "Requested quantity exceeds available stock.";
            header("Location: ../cart.php");
            exit;
        }

        $stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE cart_item_id = ?");
        $stmt->bind_param("ii", $quantity, $cart_item_id);
        $stmt->execute();

        $_SESSION['message'] = "Quantity updated successfully.";
        header("Location: ../cart.php");
        exit;

    } elseif ($action === 'remove_from_cart') {
        $cart_item_id = intval($_POST['cart_item_id'] ?? 0);

        if ($cart_item_id <= 0) {
            $_SESSION['message'] = "Invalid cart item ID.";
            header("Location: ../cart.php");
            exit;
        }

        $stmt = $conn->prepare("SELECT cart_item_id FROM cart_items WHERE cart_item_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $cart_item_id, $user_id);
        $stmt->execute();

        if ($stmt->get_result()->num_rows === 0) {
            $_SESSION['message'] = "Cart item not found.";
            header("Location: ../cart.php");
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM cart_items WHERE cart_item_id = ?");
        $stmt->bind_param("i", $cart_item_id);
        $stmt->execute();

        $_SESSION['message'] = "Item removed from cart.";
        header("Location: ../cart.php");
        exit;

    } else {
        $_SESSION['message'] = "Invalid request.";
        header("Location: ../cart.php");
        exit;
    }
} catch (Exception $e) {
    $_SESSION['message'] = "Database error: " . $e->getMessage();
    header("Location: ../cart.php");
    exit;
}
