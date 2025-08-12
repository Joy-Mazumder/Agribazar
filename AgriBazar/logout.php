<?php
session_start();
include 'db_connect.php';

$user_id = $_SESSION['consumer_id'] ?? $_SESSION['seller_id'] ?? null;

if ($user_id) {
    $query = "UPDATE user_meta SET is_logged_in = 0, session_id = NULL WHERE user_id = $user_id";
    mysqli_query($conn, $query);
}

session_unset();
session_destroy();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="refresh" content="2;url=index.php">
    <meta charset="UTF-8">
    <title>Logging Out...</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            background: #f4f4f4;
            color: #333;
        }
        .message-box {
            padding: 30px 50px;
            border: 2px solid #4CAF50;
            background: #e8f9e9;
            border-radius: 12px;
            text-align: center;
        }
        .message-box h2 {
            margin-bottom: 10px;
            color: #4CAF50;
        }
        .message-box p {
            font-size: 16px;
        }
    </style>
</head>
<body>
    <div class="message-box">
        <h2>✅ You have been logged out successfully!</h2>
        <p>Redirecting to homepage...</p>
    </div>
</body>
</html>
