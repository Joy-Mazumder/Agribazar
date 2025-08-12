<?php
session_start();
require 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        echo "Please enter both email and password.";
        exit;
    }

    $stmt = $conn->prepare("SELECT seller_id, full_name, password FROM sellers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($seller_id, $name, $hashed_password);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {

            // Check account status from user_meta
            $statusStmt = $conn->prepare("SELECT status FROM user_meta WHERE user_id = ? AND user_type = 'seller'");
            $statusStmt->bind_param("i", $seller_id);
            $statusStmt->execute();
            $statusStmt->bind_result($status);
            $statusStmt->fetch();
            $statusStmt->close();

            if ($status === 'inactive') {
                echo "
    <div style='
        max-width: 600px;
        margin: 80px auto;
        padding: 30px;
        background-color: #fff3f3;
        border: 1px solid #f5c2c2;
        border-left: 6px solid #d8000c;
        border-radius: 8px;
        font-family: Arial, sans-serif;
        color: #333;
        text-align: center;
    '>
        <h2 style='color: #d8000c;'>Account Inactive ❌</h2>
        <p style='font-size: 16px; line-height: 1.6;'>
            Your seller account is currently <strong>inactive or restricted</strong> due to a violation of our platform's guidelines or pending approval.
        </p>
        <p style='font-size: 16px;'>
            Please review our <strong>Community Guidelines</strong> to ensure compliance.
        </p>
        <p style='font-size: 16px; margin-top: 15px;'>
            To activate your account or resolve this issue, please contact our support team at:<br>
            <a href='mailto:agribazarteam@gmail.com' style='color: #c0392b; font-weight: bold;'>agribazarteam@gmail.com</a>
        </p>
        <p style='margin-top: 20px; font-size: 14px; color: #777;'>
            Thank you for your cooperation. – AgriBazar Team
        </p>
    </div>
    ";
                exit;
            }


            $_SESSION['seller_id'] = $seller_id;

            $ip_address = $_SERVER['REMOTE_ADDR'];
            $user_type = 'seller';
            $device_info = $_SERVER['HTTP_USER_AGENT'];
            $session_id = session_id();

            $metaCheck = $conn->prepare("SELECT user_id FROM user_meta WHERE user_id = ? AND user_type = 'seller'");
            $metaCheck->bind_param("i", $seller_id);
            $metaCheck->execute();
            $metaCheck->store_result();

            if ($metaCheck->num_rows === 0) {
                $insertMeta = $conn->prepare("INSERT INTO user_meta (user_id, ip_address, user_type, device_info, is_logged_in, session_id) VALUES (?, ?, ?, ?, 1, ?)");
                $insertMeta->bind_param("issss", $seller_id, $ip_address, $user_type, $device_info, $session_id);
                $insertMeta->execute();
                $insertMeta->close();
            } else {
                $updateMeta = $conn->prepare("UPDATE user_meta SET is_logged_in = 1, session_id = ?, ip_address = ?, device_info = ? WHERE user_id = ? AND user_type = 'seller'");
                $updateMeta->bind_param("sssi", $session_id, $ip_address, $device_info, $seller_id);
                $updateMeta->execute();
                $updateMeta->close();
            }

            $metaCheck->close();

            $quotes = [
                "🧑‍🌾 'Your effort feeds the nation – thank you, seller!' – AgriBazar",
                "📈 'Sell more. Grow more. Thrive with AgriBazar.'",
                "🌿 'From your hands to their homes – sellers make it happen!'",
                "🌾 'Great things grow when sellers believe.' – AgriBazar",
                "💼 'Your marketplace to shine – welcome to AgriBazar seller family.'",
                "🌟 'You grow. We help you grow more – AgriBazar for sellers.'",
                "🚜 'Turn harvest into happiness with AgriBazar sales.'",
                "📦 'Delivering your passion – one order at a time.'",
                "👨‍🌾 'Your farm. Your shop. Your brand – AgriBazar Seller Hub.'",
            ];

            $quote = $quotes[array_rand($quotes)];

            $hour = date('H');
            if ($hour < 12) {
                $greeting = "Good Morning";
            } elseif ($hour < 18) {
                $greeting = "Good Afternoon";
            } else {
                $greeting = "Good Evening";
            }

            echo "
<div style='
    max-width: 600px;
    margin: 60px auto;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 8px 20px rgba(0,0,0,0.08);
    font-family: \"Segoe UI\", sans-serif;
'>
    <div style='
        background-color: #2e7d32;
        padding: 25px;
        color: white;
        text-align: center;
    '>
        <h2 style='margin: 0; font-size: 28px;'>👋 $greeting, " . htmlspecialchars($name) . "!</h2>
        <p style='margin-top: 10px; font-size: 16px;'>Welcome back to <strong>AgriBazar</strong> Seller Portal</p>
    </div>
    <div style='
        background-color: #ffffff;
        padding: 30px 25px;
        text-align: center;
        color: #2e7d32;
    '>
        <p style='font-size: 18px; margin-bottom: 25px;'>🌾 <em>$quote</em></p>
        <a href='seller_dashboard.php' style='
            background-color: #4CAF50;
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            font-size: 16px;
            border-radius: 6px;
            transition: background-color 0.3s ease;
        ' onmouseover=\"this.style.backgroundColor='#388e3c'\" onmouseout=\"this.style.backgroundColor='#4CAF50'\">
            Go to Dashboard 📊
        </a>
    </div>
</div>
";

            exit;
        } else {
            echo "Invalid password.";
        }
    } else {
        echo "No seller account found with that email.";
    }

    $stmt->close();
}
