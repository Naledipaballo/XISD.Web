<?php
session_start();
include("dbconnect.php");

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Please login first.'); window.location.href = 'login.html';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];
$error_message = "";

// Fetch cart items for the logged-in user
$cart_sql = "SELECT * FROM cart WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $cart_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$cart_result = mysqli_stmt_get_result($stmt);

$subtotal = 0.00;
$cart_items = [];
while ($row = mysqli_fetch_assoc($cart_result)) {
    $subtotal += ($row['price'] * $row['quantity']);
    $cart_items[] = $row;
}
mysqli_stmt_close($stmt);

$shipping_fee = 35.00;
$grand_total = $subtotal > 0 ? $subtotal + $shipping_fee : 0.00;

// Handle Order Placement
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['place_order'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $delivery_address = trim($_POST['delivery_address'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $payment_method = trim($_POST['payment_method'] ?? 'Cash on Delivery');

    if (empty($full_name) || empty($delivery_address) || empty($phone)) {
        $error_message = "Please fill in all required contact and shipping details.";
    } elseif (empty($cart_items)) {
        $error_message = "Your cart is empty.";
    } else {
        // Generate a unique tracking order number (e.g., PPD-17309825)
        $order_number = "PPD-" . strtoupper(substr(uniqid(), -6));

        $order_sql = "INSERT INTO orders (user_id, order_number, total_price, delivery_address, status, order_date) VALUES (?, ?, ?, ?, 'Processing', NOW())";
        $order_stmt = mysqli_prepare($conn, $order_sql);
        
        if ($order_stmt) {
            mysqli_stmt_bind_param($order_stmt, "isds", $user_id, $order_number, $grand_total, $delivery_address);
            if (mysqli_stmt_execute($order_stmt)) {
                $order_id = mysqli_insert_id($conn);
                mysqli_stmt_close($order_stmt);

                // Clear the user's cart after successful order placement
                $clear_cart = "DELETE FROM cart WHERE user_id = ?";
                $clear_stmt = mysqli_prepare($conn, $clear_cart);
                mysqli_stmt_bind_param($clear_stmt, "i", $user_id);
                mysqli_stmt_execute($clear_stmt);
                mysqli_stmt_close($clear_stmt);

                // Show the tracking order number to the user in an alert before redirecting
                echo "<script>
                        alert('Order placed successfully! Your Tracking Order Number is: $order_number. Use this number on the Track Order page.');
                        window.location.href = 'trackorder.php';
                      </script>";
                exit();
            } else {
                $error_message = "Error processing your order. Please try again.";
                mysqli_stmt_close($order_stmt);
            }
        } else {
            $error_message = "Database error during order creation.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Pill Point Delivery</title>
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

        .checkout-container {
            max-width: 650px;
            margin: 110px auto 40px auto;
            background: #fff;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .checkout-container h2 {
            color: #003049;
            text-align: center;
            margin-bottom: 8px;
        }

        .checkout-container p.subtitle {
            text-align: center;
            color: #64748b;
            margin-bottom: 25px;
            font-size: 14px;
        }

        .form-section {
            margin-bottom: 25px;
        }

        .form-section h3 {
            font-size: 16px;
            color: #003049;
            margin-bottom: 12px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 6px;
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

        .form-group input, 
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 15px;
            outline: none;
        }

        .payment-options {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }

        .payment-btn {
            flex: 1;
            padding: 12px;
            background: #f8f9fa;
            border: 2px solid #cbd5e1;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            color: #003049;
            text-align: center;
            font-size: 14px;
            transition: all 0.2s;
        }

        .payment-btn.active {
            background: #669BBC;
            color: white;
            border-color: #669BBC;
        }

        .payment-details-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            margin-bottom: 15px;
            display: none;
        }

        .order-summary {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
            color: #475569;
        }

        .summary-row.total {
            font-weight: bold;
            color: #003049;
            font-size: 17px;
            border-top: 1px solid #ddd;
            padding-top: 8px;
            margin-top: 8px;
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
    <a href="myaccount.php">My Account</a>
    <a href="contact.php">Contact Us</a>
    <a href="logout.php">Logout</a>
</div>

<div class="checkout-container">
    <h2>Checkout</h2>
    <p class="subtitle">Complete your purchase securely</p>

    <?php if (!empty($error_message)): ?>
        <p style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 6px; text-align: center; font-weight: bold; margin-bottom: 20px;"><?php echo htmlspecialchars($error_message); ?></p>
    <?php endif; ?>

    <form action="checkout.php" method="POST">
        
        <div class="form-section">
            <h3>1. Contact Information</h3>
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="full_name" required placeholder="e.g. Naledi Tshelane">
            </div>
            <div class="form-group">
                <label>Phone Number *</label>
                <input type="text" name="phone" required placeholder="e.g. 082 123 4567">
            </div>
        </div>

        <div class="form-section">
            <h3>2. Shipping Address</h3>
            <div class="form-group">
                <label>Delivery Address *</label>
                <textarea name="delivery_address" rows="3" required placeholder="Enter street address, suburb, or delivery location..."><?php echo isset($_SESSION['selected_pharmacy']['address']) ? htmlspecialchars($_SESSION['selected_pharmacy']['address']) : ''; ?></textarea>
            </div>
        </div>

        <div class="form-section">
            <h3>3. Payment Method</h3>
            <input type="hidden" name="payment_method" id="payment_method_input" value="Credit / Debit Card">
            
            <div class="payment-options">
                <div class="payment-btn active" onclick="selectPayment('card', this)">Credit / Debit Card</div>
                <div class="payment-btn" onclick="selectPayment('eft', this)">Instant EFT</div>
                <div class="payment-btn" onclick="selectPayment('cod', this)">Cash on Delivery</div>
            </div>

            <!-- Credit Card Details Box -->
            <div id="card-details" class="payment-details-box" style="display: block;">
                <div class="form-group">
                    <label>Card Number *</label>
                    <input type="text" placeholder="1234 5678 9012 3456" id="card_num">
                </div>
                <div style="display: flex; gap: 10px;">
                    <div class="form-group" style="flex: 1;">
                        <label>Expiry Date *</label>
                        <input type="text" placeholder="MM/YY" id="card_exp">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>CVV *</label>
                        <input type="password" placeholder="123" maxlength="4" id="card_cvv">
                    </div>
                </div>
            </div>

            <!-- Instant EFT Details Box -->
            <div id="eft-details" class="payment-details-box">
                <div class="form-group">
                    <label>Select Your Bank *</label>
                    <select id="eft_bank">
                        <option value="Absa">Absa</option>
                        <option value="FNB">First National Bank (FNB)</option>
                        <option value="Nedbank">Nedbank</option>
                        <option value="Standard Bank">Standard Bank</option>
                        <option value="Capitec">Capitec</option>
                        <option value="TymeBank">TymeBank</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3>4. Order Summary</h3>
            <div class="order-summary">
                <?php foreach ($cart_items as $item): ?>
                    <div class="summary-row">
                        <span><?php echo htmlspecialchars($item['medicine_name']); ?> (x<?php echo $item['quantity']; ?>)</span>
                        <span>R<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                    </div>
                <?php endforeach; ?>
                <div class="summary-row">
                    <span>Shipping Fee</span>
                    <span>R<?php echo number_format($shipping_fee, 2); ?></span>
                </div>
                <div class="summary-row total">
                    <span>Grand Total</span>
                    <span>R<?php echo number_format($grand_total, 2); ?></span>
                </div>
            </div>
        </div>

        <button type="submit" name="place_order" class="btn">Confirm & Place Order (R<?php echo number_format($grand_total, 2); ?>)</button>
    </form>
</div>

<script>
    function selectPayment(type, element) {
        document.querySelectorAll('.payment-btn').forEach(btn => btn.classList.remove('active'));
        element.classList.add('active');

        document.getElementById('card-details').style.display = 'none';
        document.getElementById('eft-details').style.display = 'none';

        document.getElementById('card_num').required = false;
        document.getElementById('card_exp').required = false;
        document.getElementById('card_cvv').required = false;

        if (type === 'card') {
            document.getElementById('card-details').style.display = 'block';
            document.getElementById('card_num').required = true;
            document.getElementById('card_exp').required = true;
            document.getElementById('card_cvv').required = true;
            document.getElementById('payment_method_input').value = 'Credit / Debit Card';
        } else if (type === 'eft') {
            document.getElementById('eft-details').style.display = 'block';
            document.getElementById('payment_method_input').value = 'Instant EFT';
        } else {
            document.getElementById('payment_method_input').value = 'Cash on Delivery';
        }
    }
</script>
<script src="script.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>