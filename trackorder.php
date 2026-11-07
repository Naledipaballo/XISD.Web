<?php
session_start();
include("dbconnect.php");

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Please login first.'); window.location.href = 'login.html';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];
$search_result = null;
$error_message = "";

// Handle order search
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['track_order'])) {
    $order_num = trim($_POST['order_number'] ?? '');
    if (!empty($order_num)) {
        $stmt = mysqli_prepare($conn, "SELECT * FROM orders WHERE user_id = ? AND (order_number = ? OR id = ?)");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "isi", $user_id, $order_num, $order_num);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($result)) {
                $search_result = $row;
            } else {
                $error_message = "No order found with number: " . htmlspecialchars($order_num);
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Fetch user's existing orders safely
$orders_list = [];
$list_stmt = mysqli_prepare($conn, "SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC");
if ($list_stmt) {
    mysqli_stmt_bind_param($list_stmt, "i", $user_id);
    mysqli_stmt_execute($list_stmt);
    $list_res = mysqli_stmt_get_result($list_stmt);
    while ($row = mysqli_fetch_assoc($list_res)) {
        $orders_list[] = $row;
    }
    mysqli_stmt_close($list_stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Order - Pill Point Delivery</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            box-sizing: border-box;
        }

        .header-title {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            margin: 0;
        }

        .track-container {
            max-width: 650px;
            margin: 110px auto 40px auto;
            background: #fff;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .track-container h2 {
            color: #003049;
            text-align: center;
            margin-bottom: 8px;
        }

        .track-container p.subtitle {
            text-align: center;
            color: #64748b;
            margin-bottom: 25px;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #003049;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 15px;
            outline: none;
        }

        .btn {
            background: #669BBC;
            color: white;
            border: none;
            padding: 14px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px;
            width: 100%;
        }

        .btn:hover {
            background: #003049;
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
        }

        .orders-table th, .orders-table td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
            font-size: 14px;
            color: #334155;
        }

        .orders-table th {
            background: #f8f9fa;
            color: #003049;
            font-weight: bold;
        }

        .result-box {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
    </style>
</head>
<body>

<div class="header">
    <span class="menu-btn" onclick="openNav()">&#9776;</span>
    <h1 class="header-title">Pill Point Delivery</h1>
</div>

<div id="sidebar" class="sidebar">
    <a href="javascript:void(0)" class="closebtn" onclick="closeNav()">&times;</a>
    <a href="dashboard.php">Dashboard</a>
    <a href="medicines.php">Shop Medication</a>
    <a href="prescription.php">Prescription</a>
    <a href="reminders.php">Reminders</a>
    <a href="storelocator.php">Store Locator</a>
    <a href="cart.php">Cart</a>
    <a href="trackorder.php">Track Order</a>
    <a href="myaccount.php">My Account</a>
    <a href="contact.php">Contact Us</a>
    <a href="logout.php">Logout</a>
</div>

<div class="track-container">
    <h2>Track Your Order</h2>
    <p class="subtitle">Enter your tracking number to check delivery status</p>

    <?php if (!empty($error_message)): ?>
        <p style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 6px; text-align: center; font-weight: bold; margin-bottom: 20px;"><?php echo htmlspecialchars($error_message); ?></p>
    <?php endif; ?>

    <form action="trackorder.php" method="POST">
        <div class="form-group">
            <label>Order Number / ID</label>
            <input type="text" name="order_number" required placeholder="e.g. PPD-173098 or #3">
        </div>
        <button type="submit" name="track_order" class="btn">Track Order</button>
    </form>

    <?php if ($search_result): ?>
        <div class="result-box">
            <h3 style="margin-top: 0; color: #166534;">Order Status Details</h3>
            <p><strong>Order ID:</strong> #<?php echo $search_result['id']; ?></p>
            <p><strong>Status:</strong> <span style="color: #2563eb; font-weight: bold;"><?php echo htmlspecialchars($search_result['status'] ?? 'Processing'); ?></span></p>
            <p><strong>Total Amount:</strong> R<?php 
                $amt = $search_result['total_price'] ?? $search_result['total_amount'] ?? $search_result['total'] ?? $search_result['amount'] ?? 0;
                echo number_format((float)$amt, 2); 
            ?></p>
            <p><strong>Delivery Address:</strong> <?php echo htmlspecialchars($search_result['delivery_address'] ?? 'N/A'); ?></p>
            <p><strong>Order Date:</strong> <?php echo htmlspecialchars($search_result['order_date'] ?? $search_result['created_at'] ?? 'N/A'); ?></p>
        </div>
    <?php endif; ?>

    <h3 style="margin-top: 35px; color: #003049; font-size: 18px;">Your Existing Orders</h3>
    <?php if (empty($orders_list)): ?>
        <p style="color: #64748b; font-size: 14px;">You have no past orders yet.</p>
    <?php else: ?>
        <table class="orders-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders_list as $ord): ?>
                    <tr>
                        <td>#<?php echo $ord['id']; ?></td>
                        <td>R<?php 
                            $row_amt = $ord['total_price'] ?? $ord['total_amount'] ?? $ord['total'] ?? $ord['amount'] ?? 0;
                            echo number_format((float)$row_amt, 2); 
                        ?></td>
                        <td><span style="font-weight: bold; color: #1d4ed8;"><?php echo htmlspecialchars($ord['status'] ?? 'Processing'); ?></span></td>
                        <td><?php echo htmlspecialchars($ord['order_date'] ?? $ord['created_at'] ?? 'N/A'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script src="script.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>