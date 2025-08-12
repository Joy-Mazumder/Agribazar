<?php
require 'db_connect.php';

$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $nid_number = trim($_POST['nid_number']);


    if (empty($full_name)) {
        $errors[] = "Full name is required.";
    }

    if (empty($phone)) {
        $errors[] = "Phone number is required.";
    } elseif (!preg_match('/^\+?\d{7,15}$/', $phone)) {
        $errors[] = "Phone number format is invalid.";
    } elseif (!preg_match('/^01[0-9]{9}$/', $phone)) {
        $errors[] = "Phone number must be 11 digits and start with '01'.";
    }

    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }

    if (empty($confirm_password)) {
        $errors[] = "Confirm password is required.";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    if (empty($nid_number)) {
        $errors[] = "NID number is required.";
    } elseif (!preg_match('/^[0-9A-Za-z]+$/', $nid_number)) {
        $errors[] = "NID number must be alphanumeric.";
    }


    if (empty($errors)) {

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);


        $full_name = $conn->real_escape_string($full_name);
        $phone = $conn->real_escape_string($phone);
        $email = $conn->real_escape_string($email);
        $nid_number = $conn->real_escape_string($nid_number);


        $sql = "INSERT INTO consumers (full_name, phone, email, password, nid_number)
                VALUES ('$full_name', '$phone', '$email', '$hashed_password', '$nid_number')";

        if ($conn->query($sql) === TRUE) {
            echo "<p style='color:green;'>Registration successful! You can now <a href='consumer-login.html'>login</a>.</p>";
        } else {
            echo "<p style='color:red;'>Error: " . $conn->error . "</p>";
        }
    }
}

// Show errors if any
if (!empty($errors)) {
    echo "<ul style='color:red;'>";
    foreach ($errors as $error) {
        echo "<li>" . htmlspecialchars($error) . "</li>";
    }
    echo "</ul>";
}
