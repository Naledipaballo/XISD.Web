<?php
session_start();
include("dbconnect.php");

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Please login first.'); window.location.href = 'login.html';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = "";
$error_message = "";

// Handle saving reminder
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_reminder'])) {
    $medication_name = trim($_POST['medication_name'] ?? '');
    $dosage = trim($_POST['dosage'] ?? '');
    $reminder_time = trim($_POST['reminder_time'] ?? '');
    $reminder_date = trim($_POST['reminder_date'] ?? '');
    $repeat_frequency = trim($_POST['repeat_frequency'] ?? '');
    $notification_type = trim($_POST['notification_type'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if (empty($medication_name) || empty($dosage) || empty($reminder_time) || empty($reminder_date) || empty($repeat_frequency) || empty($notification_type)) {
        $error_message = "Please fill in all required reminder fields.";
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO reminders (user_id, medication_name, dosage, reminder_time, reminder_date, repeat_frequency, notification_type, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "isssssss", $user_id, $medication_name, $dosage, $reminder_time, $reminder_date, $repeat_frequency, $notification_type, $notes);
            if (mysqli_stmt_execute($stmt)) {
                $success_message = "Reminder saved successfully!";
            } else {
                $error_message = "Database error saving reminder: " . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
        } else {
            $error_message = "Database query preparation failed.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Reminder - Pill Point Delivery</title>
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
            background: #ffffff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .header-title {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            margin: 0;
            color: #003049;
        }

        .sidebar {
            height: 100%;
            width: 0;
            position: fixed;
            z-index: 2000;
            top: 0;
            left: 0;
            background-color: #003049;
            overflow-x: hidden;
            transition: 0.3s;
            padding-top: 60px;
        }

        .sidebar a {
            padding: 10px 15px 10px 30px;
            text-decoration: none;
            font-size: 18px;
            color: #fff;
            display: block;
            transition: 0.3s;
        }

        .sidebar a:hover {
            background-color: #669BBC;
        }

        .sidebar .closebtn {
            position: absolute;
            top: 10px;
            right: 20px;
            font-size: 36px;
            margin-left: 50px;
        }

        .reminder-container {
            max-width: 650px;
            margin: 110px auto 40px auto;
            background: #fff;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .reminder-container h2 {
            color: #003049;
            text-align: center;
            margin-bottom: 8px;
        }

        .reminder-container p.subtitle {
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
    <span class="menu-btn" onclick="openNav()" style="cursor: pointer; font-size: 22px;">&#9776;</span>
    <h1 class="header-title">Pill Point Delivery</h1>
</div>

<div id="sidebar" class="sidebar">
    <a href="javascript:void(0)" class="closebtn" onclick="closeNav()">&times;</a>
    <a href="dashboard.php">Dashboard</a>
    <a href="medicines.php">Shop Medication</a>
    <a href="prescription.php">Prescription</a>
    <a href="reminders.php">Set Reminder</a>
    <a href="viewreminders.php">My Reminders</a>
    <a href="storelocator.php">Store Locator</a>
    <a href="cart.php">Cart</a>
    <a href="trackorder.php">Track Order</a>
    <a href="myaccount.php">My Account</a>
    <a href="logout.php">Logout</a>
</div>

<div class="reminder-container">
    <h2>Medication Reminders</h2>
    <p class="subtitle">Set reminders so you never miss a dose</p>

    <?php if (!empty($error_message)): ?>
        <p style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 6px; text-align: center; font-weight: bold; margin-bottom: 20px;"><?php echo htmlspecialchars($error_message); ?></p>
    <?php endif; ?>

    <?php if (!empty($success_message)): ?>
        <p style="background: #d4edda; color: #155724; padding: 12px; border-radius: 6px; text-align: center; font-weight: bold; margin-bottom: 20px;"><?php echo htmlspecialchars($success_message); ?></p>
    <?php endif; ?>

    <form action="reminders.php" method="POST">
        <div class="form-group">
            <label>Medication Name *</label>
            <input type="text" name="medication_name" required placeholder="e.g. Allergex Tablets">
        </div>

        <div class="form-group">
            <label>Dosage *</label>
            <input type="text" name="dosage" required placeholder="e.g. 1 Tablet (50mg)">
        </div>

        <div class="form-group">
            <label>Reminder Time *</label>
            <input type="time" name="reminder_time" required>
        </div>

        <div class="form-group">
            <label>Reminder Date *</label>
            <input type="date" name="reminder_date" required>
        </div>

        <div class="form-group">
            <label>Repeat Frequency *</label>
            <select name="repeat_frequency" required>
                <option value="Once Off">Once Off</option>
                <option value="Daily">Daily</option>
                <option value="Twice Daily">Twice Daily</option>
                <option value="Weekly">Weekly</option>
                <option value="As Needed">As Needed</option>
            </select>
        </div>

        <div class="form-group">
            <label>Notification Type *</label>
            <select name="notification_type" required>
                <option value="Push Notification">Push Notification</option>
                <option value="SMS Alert">SMS Alert</option>
                <option value="Email Alert">Email Alert</option>
            </select>
        </div>

        <div class="form-group">
            <label>Notes / Instructions (Optional)</label>
            <textarea name="notes" rows="3" placeholder="e.g. Take with food"></textarea>
        </div>

        <button type="submit" name="save_reminder" class="btn">Save Reminder</button>
    </form>
</div>

<script>
    function openNav() {
        document.getElementById("sidebar").style.width = "250px";
    }

    function closeNav() {
        document.getElementById("sidebar").style.width = "0";
    }
</script>
</body>
</html>
<?php mysqli_close($conn); ?>