<?php
include("dbconnect.php");

// Restrict access to POST requests only
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: signup.html");
    exit();
}

// Sanitize/capture inputs safely
$first_name = $_POST['first_name'] ?? '';
$last_name  = $_POST['last_name']  ?? '';
$age        = $_POST['age']        ?? '';
$phone      = $_POST['phone']      ?? '';
$email      = $_POST['email']      ?? '';
$address    = $_POST['address']    ?? '';
$province   = $_POST['province']   ?? '';
$username   = $_POST['username']   ?? '';
$password   = $_POST['password']   ?? '';

// 1. Check if username or email already exists
$check_query = "SELECT id FROM customers WHERE username = ? OR email = ?";
$check_stmt  = mysqli_prepare($conn, $check_query);

if (!$check_stmt) {
    die("Database Error (Check Query Failed): " . mysqli_error($conn));
}

mysqli_stmt_bind_param($check_stmt, "ss", $username, $email);
mysqli_stmt_execute($check_stmt);
$result = mysqli_stmt_get_result($check_stmt);

if (mysqli_num_rows($result) > 0) {
    echo "<script>
            alert('Username or email already exists.');
            window.location.href = 'signup.html';
          </script>";
    exit();
}

// 2. Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// 3. Insert new user record
$insert_query = "INSERT INTO customers (first_name, last_name, age, phone, email, address, province, username, password) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

$insert_stmt = mysqli_prepare($conn, $insert_query);

if (!$insert_stmt) {
    die("Database Error (Insert Query Failed): " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $insert_stmt, 
    "ssissssss", 
    $first_name, 
    $last_name, 
    $age, 
    $phone, 
    $email, 
    $address, 
    $province, 
    $username, 
    $hashed_password
);

if (mysqli_stmt_execute($insert_stmt)) {
    echo "<script>
            alert('Registration successful!');
            window.location.href = 'login.html';
          </script>";
} else {
    echo "Error executing query: " . mysqli_stmt_error($insert_stmt);
}

mysqli_stmt_close($check_stmt);
mysqli_stmt_close($insert_stmt);
mysqli_close($conn);
?>