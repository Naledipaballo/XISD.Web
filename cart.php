<?php
session_start();
include("dbconnect.php");

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Please login first.'); window.location.href = 'login.html';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle quantity updates or item removal
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_cart'])) {
        if (isset($_POST['qty']) && is_array($_POST['qty'])) {
            foreach ($_POST['qty'] as $cart_id => $quantity) {
                $quantity = (int)$quantity;
                $cart_id = (int)$cart_id;
                if ($quantity > 0) {
                    $update_sql = "UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?";
                    $stmt = mysqli_prepare($conn, $update_sql);
                    if ($stmt) {
                        mysqli_stmt_bind_param($stmt, "iii", $quantity, $cart_id, $user_id);
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_close($stmt);
                    }
                }
            }
        }
    } elseif (isset($_POST['remove_item'])) {
        $cart_id = (int)$_POST['cart_id'];
        $del_sql = "DELETE FROM cart WHERE id = ? AND user_id = ?";
        $stmt = mysqli_prepare($conn, $del_sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ii", $cart_id, $user_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }
}

// Fetch cart items securely using direct procedural query to prevent any prepare statement mismatches
$safe_user_id = mysqli_real_escape_string($conn, $user_id);
$query = "SELECT * FROM cart WHERE user_id = '$safe_user_id'";
$result = mysqli_query($conn, $query);

$total_amount = 0.00;
$cart_items = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $total_amount += ($row['price'] * $row['quantity']);
        $cart_items[] = $row;
    }
}

$shipping_fee = 35.00;
$grand_total = $total_amount > 0 ? $total_amount + $shipping_fee : 0.00;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Pill Point Delivery</title>
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

        .cart-page-container {
            width: 90%;
            max-width: 650px;
            margin: 110px auto 40px auto;
        }

        .cart-page-title {
            text-align: center;
            color: #003049;
            font-size: 32px;
            margin-bottom: 25px;
            font-weight: bold;
        }

        .cart-items-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-bottom: 25px;
        }

        .cart-item-card {
            background: #fff;
            border-radius: 12px;
            padding: 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }

        .cart-item-details {
            flex-grow: 1;
        }

        .cart-item-title {
            font-size: 16px;
            font-weight: bold;
            color: #003049;
            margin-bottom: 4px;
            text-transform: capitalize;
        }

        .cart-item-price {
            font-size: 15px;
            font-weight: bold;
            color: #C1121F;
            margin-top: 4px;
        }

        .cart-item-controls {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 8px;
        }

        .qty-input {
            width: 50px;
            text-align: center;
            padding: 6px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
            outline: none;
        }

        .remove-btn {
            background: #C1121F;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 13px;
            font-weight: bold;
            padding: 6px 12px;
            border-radius: 6px;
        }

        .remove-btn:hover {
            background: #780000;
        }

        .cart-summary-box {
            background: #fff;
            border-radius: 12px;
            padding: 22px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            margin-bottom: 25px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 15px;
            color: #475569;
        }

        .summary-row.total-row {
            font-size: 18px;
            font-weight: bold;
            color: #003049;
            border-top: 1px solid #eee;
            padding-top: 12px;
            margin-top: 12px;
        }

        .cart-action-buttons {
            display: flex;
            gap: 12px;
        }

        .btn {
            background: #669BBC;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            font-size: 15px;
            text-align: center;
            text-decoration: none;
            flex: 1;
        }

        .btn:hover {
            background: #003049;
        }

        .btn-outline {
            background: transparent;
            color: #003049;
            border: 2px solid #003049;
        }

        .btn-outline:hover {
            background: #003049;
            color: white;
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
    <a href="storelocator.php">Store Locator</a>
    <a href="cart.php">Cart</a>
    <a href="trackorder.php">Track Order</a>
    <a href="myaccount.php">My Account</a>
    <a href="contact.php">Contact Us</a>
    <a href="logout.php">Logout</a>
</div>

<div class="cart-page-container">
    <h2 class="cart-page-title">Shopping Cart</h2>

    <?php if (!empty($cart_items)): ?>
        <form action="cart.php" method="POST">
            
            <div class="cart-items-list">
                <?php foreach ($cart_items as $item): ?>
                    <div class="cart-item-card">
                        <div class="cart-item-details">
                            <div class="cart-item-title"><?php echo htmlspecialchars($item['medicine_name']); ?></div>
                            <div class="cart-item-price">R<?php echo number_format($item['price'], 2); ?></div>
                            
                            <div class="cart-item-controls">
                                <label style="font-size: 13px; font-weight: bold; color: #003049;">Qty:</label>
                                <input type="number" name="qty[<?php echo $item['id']; ?>]" value="<?php echo $item['quantity']; ?>" min="1" class="qty-input">
                                
                                <button type="submit" name="remove_item" value="1" onclick="document.getElementById('cart_id_input').value='<?php echo $item['id']; ?>';" class="remove-btn">Remove</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Hidden field for deletion tracking -->
            <input type="hidden" name="cart_id" id="cart_id_input" value="">

            <!-- Summary Box -->
            <div class="cart-summary-box">
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span>R<?php echo number_format($total_amount, 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>Shipping Fee</span>
                    <span>R<?php echo number_format($shipping_fee, 2); ?></span>
                </div>
                <div class="summary-row total-row">
                    <span>Total</span>
                    <span>R<?php echo number_format($grand_total, 2); ?></span>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="cart-action-buttons">
                <button type="submit" name="update_cart" class="btn btn-outline">Update Quantities</button>
                <a href="checkout.php" class="btn">Checkout Now</a>
            </div>

        </form>
    <?php else: ?>
        <div style="background: #fff; border-radius: 12px; padding: 40px; text-align: center; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">
            <p style="font-size: 16px; color: #64748b; margin-bottom: 20px;">Your shopping cart is currently empty.</p>
            <a href="medicines.php" class="btn" style="display: inline-block; width: auto; padding: 12px 30px; text-decoration: none;">Shop Medication</a>
        </div>
    <?php endif; ?>
</div>

<script src="script.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>