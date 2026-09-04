<?php
session_start();
include("dbconnect.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch reminders for current user
$query = "SELECT * FROM reminders WHERE user_id = ? ORDER BY reminder_date ASC, reminder_time ASC";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Reminders - Pill Point Delivery</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .reminder-table { width: 100%; border-collapse: collapse; margin-top: 20px; background: #fff; }
        .reminder-table th, .reminder-table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        .reminder-table th { background-color: #007bff; color: white; }
        .btn-delete { color: #fff; background-color: #dc3545; padding: 5px 10px; text-decoration: none; border-radius: 4px; font-size: 0.9em; }
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
    <a href="reminders.php">Set Reminder</a>
    <a href="viewreminders.php">My Reminders</a>
    <a href="storelocator.php">Store Locator</a>
    <a href="cart.php">Cart</a>
    <a href="myaccount.php">My Account</a>
    <a href="contact.php">Contact Us</a>
    <a href="logout.php">Logout</a>
</div>

<div class="container" style="padding: 20px;">
    <h2>Scheduled Medication Reminders</h2>
    <a href="reminders.php" class="btn" style="text-decoration:none; display:inline-block; margin-bottom:15px;">+ Add New Reminder</a>

    <table class="reminder-table">
        <thead>
            <tr>
                <th>Medication</th>
                <th>Dosage</th>
                <th>Time</th>
                <th>Date</th>
                <th>Repeat</th>
                <th>Type</th>
                <th>Notes</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($row['medication_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['dosage']); ?></td>
                        <td><?php echo date('H:i', strtotime($row['reminder_time'])); ?></td>
                        <td><?php echo $row['reminder_date']; ?></td>
                        <td><?php echo htmlspecialchars($row['repeat_frequency']); ?></td>
                        <td><?php echo htmlspecialchars($row['notification_type']); ?></td>
                        <td><?php echo htmlspecialchars($row['notes']); ?></td>
                        <td>
                            <a href="deletereminder.php?id=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this reminder?');">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" style="text-align: center;">No reminders configured.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script src="script.js"></script>
</body>
</html>
<?php 
mysqli_stmt_close($stmt);
mysqli_close($conn); 
?>