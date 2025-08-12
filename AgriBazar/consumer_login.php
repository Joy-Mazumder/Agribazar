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

    $stmt = $conn->prepare("SELECT user_id, full_name, password FROM consumers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($user_id, $name, $hashed_password);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {

            // 🔍 Focused MySQL query to check consumer account status
            $statusCheck = $conn->prepare("SELECT status FROM user_meta WHERE user_id = ?");
            $statusCheck->bind_param("i", $user_id);
            $statusCheck->execute();
            $statusCheck->bind_result($status);
            $statusCheck->fetch();
            $statusCheck->close();

            // 🚫 If consumer is inactive
            if ($status === 'inactive') {
                echo "
                <div style='
                    max-width: 550px;
                    margin: 80px auto;
                    padding: 30px;
                    background-color: #fff3cd;
                    color: #856404;
                    border: 1px solid #ffeeba;
                    border-radius: 8px;
                    font-family: \"Segoe UI\", sans-serif;
                    box-shadow: 0 8px 20px rgba(0,0,0,0.05);
                    text-align: center;
                '>
                    <h2 style='margin-bottom: 15px;'>🚫 Account Temporarily Blocked</h2>
                    <p style='font-size: 16px;'>Your AgriBazar consumer account has been temporarily restricted due to unusual activity or policy violations.</p>
                    <p style='font-size: 15px; margin-top: 10px;'>Please wait a few days before trying again. Make sure to follow community rules during usage.</p>
                    <a href='index.html' style='
                        display: inline-block;
                        margin-top: 20px;
                        padding: 10px 22px;
                        background-color: #ff9800;
                        color: white;
                        text-decoration: none;
                        border-radius: 5px;
                        font-weight: bold;
                    '>Return Home</a>
                </div>
                ";
                exit;
            }

            // ✅ Login and set session
            $_SESSION['consumer_id'] = $user_id;

            $ip_address = $_SERVER['REMOTE_ADDR'];
            $user_type = 'consumer';
            $device_info = $_SERVER['HTTP_USER_AGENT'];
            $session_id = session_id();

            $checkStmt = $conn->prepare("SELECT user_id FROM user_meta WHERE user_id = ?");
            $checkStmt->bind_param("i", $user_id);
            $checkStmt->execute();
            $checkStmt->store_result();

            if ($checkStmt->num_rows === 0) {
                $insertStmt = $conn->prepare("INSERT INTO user_meta (user_id, ip_address, profile_image, user_type, device_info, is_logged_in, session_id) VALUES (?, ?, NULL, ?, ?, 1, ?)");
                $insertStmt->bind_param("issss", $user_id, $ip_address, $user_type, $device_info, $session_id);
                $insertStmt->execute();
                $insertStmt->close();
            } else {
                $updateStmt = $conn->prepare("UPDATE user_meta SET is_logged_in = 1, session_id = ?, ip_address = ?, device_info = ? WHERE user_id = ?");
                $updateStmt->bind_param("sssi", $session_id, $ip_address, $device_info, $user_id);
                $updateStmt->execute();
                $updateStmt->close();
            }

            $checkStmt->close();

            // 🧺 Consumer quotes
            $quotes = [
                "🌱 'By supporting farmers, you support life.' – Shop local, eat fresh!",
                "🍅 'Freshness starts from the farm, delivered to your door.' Fresh from farm to your home – only on AgriBazar!",
                "🧺 Support farmers, buy fresh – shop with AgriBazar – where purity meets purpose.",
                "🥕 'Healthy food grows from healthy soil – respect the farmer.'",
                "🍅 Nature’s best picks await you at AgriBazar!",
                "🌾 Farm-fresh deals every day – AgriBazar has you covered.",
                "🥬 Choose health, choose local – choose AgriBazar.",
                "🌿 Nature gives, we deliver – AgriBazar.",
                "🌾 A farmer’s care in every bite.",
                "🌤️ Nurtured by earth, served by AgriBazar.",
                "🍀 AgriBazar brings farms closer to you.",
                "🌻 Freshness begins with a seed – and ends in your cart.",
                "🧑‍🌾 A farmer’s harvest, your family’s feast.",
                "🌎 Better food. Better planet. AgriBazar cares.",
                "🌱 For every bite, a farmer’s pride grows.",
                "🍋 From the land of purity – brought to you by AgriBazar.",
                "🌾 Trust the farmer, trust AgriBazar.",
            ];

            $quote = $quotes[array_rand($quotes)];

            $hour = date('H');
            if ($hour < 12) {
                $greeting = "Hi 👋 Good Morning";
            } elseif ($hour < 18) {
                $greeting = "Hi 👋 Good Afternoon";
            } else {
                $greeting = "Hi 👋 Good Evening";
            }

            // ✅ Consumer Success Login UI (different from seller)
            echo "
            <div style='
                max-width: 600px;
                margin: 70px auto;
                border-radius: 10px;
                overflow: hidden;
                box-shadow: 0 10px 25px rgba(0,0,0,0.08);
                font-family: \"Segoe UI\", sans-serif;
            '>
                <div style='
                    background: linear-gradient(135deg, #43a047, #66bb6a);
                    padding: 30px;
                    color: white;
                    text-align: center;
                '>
                    <h2 style='margin: 0; font-size: 26px;'>$greeting, " . htmlspecialchars($name) . "!</h2>
                    <p style='margin-top: 10px; font-size: 16px;'>Thanks for trusting AgriBazar — your gateway to fresh, farm-picked vegetables !!</p>
                </div>
                <div style='
                    background-color: #ffffff;
                    padding: 30px;
                    text-align: center;
                    color: #388e3c;
                '>
                    <p style='font-size: 18px; margin-bottom: 25px;'>🧺 <em>$quote</em></p>
                    <a href='shop.php' style='
                        background-color: #4CAF50;
                        color: white;
                        padding: 12px 25px;
                        text-decoration: none;
                        font-size: 16px;
                        border-radius: 6px;
                        transition: background-color 0.3s ease;
                    ' onmouseover=\"this.style.backgroundColor='#388e3c'\" onmouseout=\"this.style.backgroundColor='#4CAF50'\">
                        Go to Shop 🛒
                    </a>
                </div>
            </div>
            ";
            exit;
        } else {
            echo "Invalid password.";
        }
    } else {
        echo "No account found with that email.";
    }

    $stmt->close();
}
?>
