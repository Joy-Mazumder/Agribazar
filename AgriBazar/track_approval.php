<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['track_seller_id'])) {
    die("Unauthorized access.");
}

$seller_id = $_SESSION['track_seller_id'];

// Step 1: Check admin approval status
$stmt = $conn->prepare("SELECT admin_approved FROM sellers WHERE seller_id = ?");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$stmt->bind_result($admin_approved);
$stmt->fetch();
$stmt->close();

if ($admin_approved == 1) {
    // Step 2: Update user_meta on approval
    $session_id = session_id();
    $status = 'active';

    $update_query = "UPDATE user_meta 
                     SET session_id = ?, is_logged_in = 1, status = ? 
                     WHERE user_id = ? AND user_type = 'seller'";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("ssi", $session_id, $status, $seller_id);
    $update_stmt->execute();
    $update_stmt->close();

    // ✅ Step 3: Auto login seller after approval
    $_SESSION['seller_id'] = $seller_id;

    echo "<div style='
        padding: 25px; 
        background: #e3f2fd; 
        color: #1565c0; 
        border: 2px solid #90caf9; 
        border-radius: 12px; 
        font-family: Arial, sans-serif; 
        box-shadow: 0 4px 8px rgba(21, 101, 192, 0.2);
        max-width: 600px; 
        margin: 30px auto;
    '>
        <h2 style='margin-top: 0; font-size: 26px;'>🎉 Congratulations!</h2>
        <p style='font-size: 18px; line-height: 1.5; margin: 15px 0;'>
            ✅ Your seller account has been approved!<br>
            🛒 You can now access your shop dashboard, add products, and grow your business.<br>
            💼 Best wishes for your journey on AgriBazar!
        </p>
        <a href='seller_dashboard.php' style='
            display: inline-block; 
            padding: 10px 25px; 
            background-color: #1565c0; 
            color: white; 
            text-decoration: none; 
            font-weight: bold; 
            border-radius: 6px;
            transition: background-color 0.3s ease;
        ' onmouseover=\"this.style.backgroundColor='#0d47a1'\" onmouseout=\"this.style.backgroundColor='#1565c0'\">
            Start Journey
        </a>
    </div>";

} elseif ($admin_approved == 0) {
    // Show pending approval message
    echo "<div style='
        padding: 25px; 
        background: #fff8e1; 
        color: #f57c00; 
        border: 2px solid #ffe082; 
        border-radius: 12px; 
        font-family: Arial, sans-serif; 
        box-shadow: 0 4px 8px rgba(245, 124, 0, 0.2);
        max-width: 600px; 
        margin: 30px auto;
    '>
        <h2 style='margin-top: 0; font-size: 26px;'>🕒 Still Pending Approval</h2>
        <p style='font-size: 18px; line-height: 1.5; margin: 15px 0;'>
            Sorry for the delay. Your application is still under review by our admin team.<br>
            📱 Please keep your phone near you — our team may contact you soon for verification.
        </p>
        <a href='track_approval.php' style='
            display: inline-block; 
            margin-top: 20px;
            padding: 10px 25px; 
            background-color: #ff9800; 
            color: white; 
            text-decoration: none; 
            font-weight: bold; 
            border-radius: 6px;
            transition: background-color 0.3s ease;
        ' onmouseover=\"this.style.backgroundColor='#ef6c00'\" onmouseout=\"this.style.backgroundColor='#ff9800'\">
            Refresh Status
        </a>
    </div>";
} else {
    echo "Invalid seller status.";
}
?>
