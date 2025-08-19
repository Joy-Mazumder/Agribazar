<?php
session_start();
include 'db_connect.php';

$user_id = $_SESSION['consumer_id'] ?? $_SESSION['seller_id'] ?? null;

if ($user_id) {
    $sql = "SELECT COUNT(DISTINCT product_id) AS count FROM cart_items WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $count = $result['count'] ?? 0;
} else {
    $count = 0;
}

echo json_encode(['count' => $count]);
