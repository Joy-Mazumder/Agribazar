<?php
session_start(); 

require 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST["full_name"]);
    $email = trim($_POST["email"]);
    $raw_password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];
    $phone_primary = trim($_POST["phone_primary"]);
    $phone_secondary = trim($_POST["phone_secondary"]);
    $nid_copy = $_FILES["nid_copy"];
    $profile_image = $_FILES["profile_image"]; // new profile image input

    // Required fields validation (including profile image)
    if (empty($full_name) || empty($email) || empty($raw_password) || empty($confirm_password) || empty($phone_primary) || empty($nid_copy["name"]) || empty($profile_image["name"])) {
        die("Missing required fields.");
    }

    // Validate profile_image file error, type and size (similar to nid_copy)
    if ($profile_image["error"] !== 0) {
        die("Profile image upload error.");
    }

    $allowed_image_types = ['image/jpeg', 'image/png'];
    if (!in_array($profile_image["type"], $allowed_image_types)) {
        die("Profile image must be JPG or PNG.");
    }

    if ($profile_image["size"] > 2 * 1024 * 1024) {
        die("Profile image must be under 2MB.");
    }

    // Your existing validations here (name, email, password, phone, nid_copy) remain unchanged
    if (!preg_match("/^[a-zA-Z\s]+$/", $full_name) || strlen($full_name) > 100) {
        die("Invalid full name.");
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
        die("Invalid email.");
    }
    if (strlen($raw_password) < 6) {
        die("Password must be at least 6 characters.");
    }
    if ($raw_password !== $confirm_password) {
        die("Password and Confirm Password do not match.");
    }
    $password = password_hash($raw_password, PASSWORD_DEFAULT);
    if (!preg_match("/^[0-9]{6,15}$/", $phone_primary)) {
        die("Invalid primary phone number.");
    }
    if (!empty($phone_secondary) && !preg_match("/^[0-9]{6,15}$/", $phone_secondary)) {
        die("Invalid secondary phone number.");
    }
    if ($nid_copy["error"] !== 0) {
        die("NID file upload error.");
    }
    $allowed_types = ['application/pdf', 'image/jpeg', 'image/png'];
    if (!in_array($nid_copy["type"], $allowed_types)) {
        die("NID file must be PDF, JPG, or PNG.");
    }
    if ($nid_copy["size"] > 2 * 1024 * 1024) {
        die("NID file must be under 2MB.");
    }

    // Check if user exists in consumers
    $stmt = $conn->prepare("SELECT user_id FROM consumers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($consumer_id);
    $exists_in_consumer = $stmt->fetch();
    $stmt->close();

    if (!$exists_in_consumer) {
        $nid_number = "N/A";
        $stmt = $conn->prepare("INSERT INTO consumers (full_name, phone, email, password, nid_number) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $full_name, $phone_primary, $email, $password, $nid_number);
        if (!$stmt->execute()) {
            die("Error inserting into consumers: " . $stmt->error);
        }
        $consumer_id = $stmt->insert_id;
        $stmt->close();
    }

    // Prevent duplicate seller registration
    $stmt = $conn->prepare("SELECT seller_id FROM sellers WHERE seller_id = ?");
    $stmt->bind_param("i", $consumer_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        die("You are already registered as a seller.");
    }
    $stmt->close();

    // Upload NID file
    $targetNIDDir = "uploads/nid/";
    if (!file_exists($targetNIDDir)) {
        mkdir($targetNIDDir, 0755, true);
    }
    $uniqueNIDName = time() . '_' . uniqid() . '_' . basename($nid_copy["name"]);
    $nidFilePath = $targetNIDDir . $uniqueNIDName;
    if (!move_uploaded_file($nid_copy["tmp_name"], $nidFilePath)) {
        die("Failed to upload NID copy.");
    }

    // Upload profile image file
    $targetProfileDir = "uploads/profile_images/";
    if (!file_exists($targetProfileDir)) {
        mkdir($targetProfileDir, 0755, true);
    }
    $uniqueProfileName = '_' . uniqid() . '_' . basename($profile_image["name"]);
    $profileFilePath = $targetProfileDir . $uniqueProfileName;
    if (!move_uploaded_file($profile_image["tmp_name"], $profileFilePath)) {
        die("Failed to upload profile image.");
    }

    // Insert into sellers with admin_approved=0 (inactive)
    $admin_approved = 0;
    $stmt = $conn->prepare("INSERT INTO sellers (seller_id, full_name, email, password, nid_file_path, admin_approved) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssi", $consumer_id, $full_name, $email, $password, $nidFilePath, $admin_approved);
    if (!$stmt->execute()) {
        die("Error inserting into sellers: " . $stmt->error);
    }
    $stmt->close();

    // Insert phones
    $stmt = $conn->prepare("INSERT INTO seller_phones (seller_id, phone, is_primary) VALUES (?, ?, 1)");
    $stmt->bind_param("is", $consumer_id, $phone_primary);
    $stmt->execute();
    $stmt->close();

    if (!empty($phone_secondary)) {
        $stmt = $conn->prepare("INSERT INTO seller_phones (seller_id, phone, is_primary) VALUES (?, ?, 0)");
        $stmt->bind_param("is", $consumer_id, $phone_secondary);
        $stmt->execute();
        $stmt->close();
    }

    // Insert into user_meta with status inactive, no session id, IP, device info, user_type = seller, and profile_image
    $ip = $_SERVER['REMOTE_ADDR'];
    $device = $_SERVER['HTTP_USER_AGENT'];
    $user_type = 'seller';

    $stmt = $conn->prepare("INSERT INTO user_meta (user_id, ip_address, profile_image, user_type, device_info, status) VALUES (?, ?, ?, ?, ?, 'inactive')");
    $stmt->bind_param("issss", $consumer_id, $ip, $profileFilePath, $user_type, $device);
    $stmt->execute();
    $stmt->close();

    // STORE seller_id in SESSION to avoid passing via URL
    $_SESSION['track_seller_id'] = $consumer_id;

    echo "<div style='
    padding: 25px; 
    background: #e6ffe6; 
    color: #2e7d32; 
    border: 2px solid #a5d6a7; 
    border-radius: 12px; 
    font-family: Arial, sans-serif; 
    box-shadow: 0 4px 8px rgba(46, 125, 50, 0.2);
    max-width: 600px; 
    margin: 30px auto;
'>
    <h2 style='margin-top: 0; font-size: 26px;'>✅ Seller Registration Successful!</h2>
    <p style='font-size: 19px; line-height: 1.5; margin: 15px 0;'>
        🕒 Your application has been received and is pending admin approval.<br>
        🚀 Once approved, you'll gain access to your seller dashboard and start earning more by showcasing your products on AgriBazar!
    </p>
    <p style='font-size: 16px; font-style: italic; color: #388e3c; margin-bottom: 25px;'>
        Stay tuned — we’ll notify you as soon as your account is approved.
    </p>
    <div style='display: flex; gap: 20px; justify-content: center;'>
        <a href='index.html' style='
            padding: 10px 25px; 
            background-color: #4caf50; 
            color: white; 
            text-decoration: none; 
            font-weight: bold; 
            border-radius: 6px;
            transition: background-color 0.3s ease;
        ' onmouseover=\"this.style.backgroundColor='#388e3c'\" onmouseout=\"this.style.backgroundColor='#4caf50'\">
            Go to Homepage
        </a>

        <!-- Link without seller_id -->
        <a href='track_approval.php' style='
            padding: 10px 25px; 
            background-color: #2196f3; 
            color: white; 
            text-decoration: none; 
            font-weight: bold; 
            border-radius: 6px;
            transition: background-color 0.3s ease;
        ' onmouseover=\"this.style.backgroundColor='#1976d2'\" onmouseout=\"this.style.backgroundColor='#2196f3'\">
            Track Approval
        </a>
    </div>
</div>";
} else {
    echo "Invalid request method.";
}
?>
