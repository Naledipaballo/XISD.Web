<?php
session_start();
include("dbconnect.php");

// 1. Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<script>
            alert('Please login first.');
            window.location.href = 'login.html';
          </script>";
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";

// 2. Handle Profile Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_account'])) {
    $first_name = trim($_POST['first_name']);
    $last_name  = trim($_POST['last_name']);
    $email      = trim($_POST['email']);
    $phone      = trim($_POST['phone']);
    $address    = trim($_POST['address']);
    $new_pass   = trim($_POST['password']);

    if (!empty($new_pass)) {
        // Update profile WITH new hashed password
        $hashed_password = password_hash($new_pass, PASSWORD_BCRYPT);
        $update_sql = "UPDATE customers SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, password = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($stmt, "ssssssi", $first_name, $last_name, $email, $phone, $address, $hashed_password, $user_id);
    } else {
        // Update profile WITHOUT altering existing password
        $update_sql = "UPDATE customers SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($stmt, "sssssi", $first_name, $last_name, $email, $phone, $address, $user_id);
    }

    if (mysqli_stmt_execute($stmt)) {
        $message = "Account details updated successfully!";
    } else {
        $message = "Failed to update account: " . mysqli_error($conn);
    }
    mysqli_stmt_close($stmt);
}

// 3. Fetch existing customer details from database
$fetch_sql = "SELECT first_name, last_name, email, phone, address FROM customers WHERE id = ?";
$stmt = mysqli_prepare($conn, $fetch_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - Pill Point Delivery</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .alert-msg {
            padding: 10px;
            background-color: #d4edda;
            color: #155724;
            border-radius: 4px;
            margin-bottom: 15px;
            text-align: center;
        }
    </style>
</head>

<body>

<!-- Header -->
<div class="header">
    <span class="menu-btn" onclick="openNav()">&#9776;</span>
    <h1 class="header-title">Pill Point Delivery</h1>
</div>

<!-- Sidebar -->
<div id="sidebar" class="sidebar">
    <a href="javascript:void(0)" class="closebtn" onclick="closeNav()">&times;</a>
    <a href="dashboard.php">Dashboard</a>
    <a href="medicines.php">Shop Medication</a>
    <a href="prescription.php">Prescription</a>
    <a href="reminders.php">Reminders</a>
    <a href="storelocator.html">Store Locator</a>
    <a href="cart.php">Cart</a>
    <a href="myaccount.php">My Account</a>
    <a href="aboutus.html">About Us</a>
    <a href="logout.php">Logout</a>
</div>

<div class="account-container">

    <h2>My Account</h2>

    <?php if (!empty($message)): ?>
        <div class="alert-msg"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <div class="profile-picture">
        <img src="photo.jpeg" alt="Profile">
    </div>

    <form action="myaccount.php" method="POST">

        <div class="row">
            <div class="input-box">
                <label>First Name</label>
                <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" required>
            </div>

            <div class="input-box">
                <label>Last Name</label>
                <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" required>
            </div>
        </div>

        <div class="row">
            <div class="input-box">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
            </div>

            <div class="input-box">
                <label>Phone Number</label>
                <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
            </div>
        </div>

        <div class="input-box full">
            <label>Address</label>
            <textarea name="address" required><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
        </div>

        <div class="input-box full">
            <label>New Password (leave blank to keep current)</label>
            <input type="password" name="password" placeholder="Enter new password">
        </div>

        <button type="submit" name="update_account" class="save-btn">
            Save Changes
        </button>

        <a href="logout.php" class="logout-btn">
            Logout
        </a>

    </form>

</div>

<script src="script.js"></script>

</body>
</html>
<?php mysqli_close($conn); ?>