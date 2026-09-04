<?php
session_start();
include("dbconnect.php");

if (!isset($_SESSION['user_id'])) {
    echo "<script>
            alert('Please login first.');
            window.location.href = 'login.html';
          </script>";
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle form submission to save a reminder
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $medication_name   = trim($_POST['medication_name'] ?? '');
    $dosage            = trim($_POST['dosage'] ?? '');
    $reminder_time     = trim($_POST['reminder_time'] ?? '');
    $reminder_date     = trim($_POST['reminder_date'] ?? '');
    $repeat_frequency  = trim($_POST['repeat_frequency'] ?? '');
    $notification_type = trim($_POST['notification_type'] ?? '');
    $notes             = trim($_POST['notes'] ?? '');

    if (!empty($medication_name) && !empty($reminder_time) && !empty($reminder_date)) {
        $sql = "INSERT INTO reminders (user_id, medication_name, dosage, reminder_time, reminder_date, repeat_frequency, notification_type, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "isssssss", $user_id, $medication_name, $dosage, $reminder_time, $reminder_date, $repeat_frequency, $notification_type, $notes);
            if (mysqli_stmt_execute($stmt)) {
                echo "<script>alert('Reminder set successfully!'); window.location.href = 'viewreminders.php';</script>";
                exit();
            } else {
                $error = "Error saving reminder.";
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        $error = "Please fill in all required fields.";
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
        .form-container { max-width: 500px; margin: 30px auto; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
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

<div class="form-container">
    <h2>Set Medication Reminder</h2>
    <?php if (isset($error)): ?>
        <p style="color: red;"><?php echo $error; ?></p>
    <?php endif; ?>

    <form action="reminders.php" method="POST">
        <div class="form-group">
            <label>Medication Name *</label>
            <input type="text" name="medication_name" required placeholder="e.g. Paracetamol">
        </div>

        <div class="form-group">
            <label>Dosage *</label>
            <input type="text" name="dosage" required placeholder="e.g. 1 Pill / 5ml">
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
            <label>Repeat Frequency</label>
            <select name="repeat_frequency">
                <option value="Once">Once</option>
                <option value="Daily">Daily</option>
                <option value="Weekly">Weekly</option>
            </select>
        </div>

        <div class="form-group">
            <label>Notification Type</label>
            <select name="notification_type">
                <option value="Push">Push Notification</option>
                <option value="SMS">SMS</option>
                <option value="Email">Email</option>
            </select>
        </div>

        <div class="form-group">
            <label>Notes</label>
            <textarea name="notes" rows="3" placeholder="Additional instructions..."></textarea>
        </div>

        <button type="submit" class="btn" style="width: 100%;">Save Reminder</button>
    </form>
    
    <div style="margin-top: 15px; text-align: center;">
        <a href="viewreminders.php" style="color: #007bff; text-decoration: none;">View Scheduled Reminders</a>
    </div>
</div>

<script src="script.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>