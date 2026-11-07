<?php
session_start();
include("dbconnect.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch reminders for current user safely
$query = "SELECT * FROM reminders WHERE user_id = ? ORDER BY id DESC";
$stmt = mysqli_prepare($conn, $query);
$result = null;
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reminders - Pill Point Delivery</title>
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

        body {
            background: #f8f9fa;
            margin: 0;
            font-family: Arial, sans-serif;
        }

        .container {
            max-width: 950px;
            margin: 110px auto 40px auto;
            background: #fff;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .container h2 {
            color: #003049;
            margin-bottom: 5px;
        }

        .reminder-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px; 
            background: #fff; 
        }

        .reminder-table th, .reminder-table td { 
            border: 1px solid #e2e8f0; 
            padding: 12px; 
            text-align: left; 
            font-size: 14px;
            color: #334155;
        }

        .reminder-table th { 
            background-color: #669BBC; 
            color: white; 
            font-weight: bold;
        }

        .btn-delete { 
            color: #fff; 
            background-color: #dc3545; 
            padding: 6px 12px; 
            text-decoration: none; 
            border-radius: 4px; 
            font-size: 13px; 
            font-weight: bold;
        }

        .btn-delete:hover {
            background-color: #b02a37;
        }

        .btn {
            background: #669BBC;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
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

<div class="container">
    <h2>Scheduled Medication Reminders</h2>
    <p style="color: #64748b; font-size: 14px; margin-bottom: 20px;">Manage and view all your active medical dosage schedules</p>
    
    <a href="reminders.php" class="btn" style="margin-bottom:15px;">+ Add New Reminder</a>

    <table class="reminder-table">
        <thead>
            <tr>
                <th>Medication</th>
                <th>Dosage</th>
                <th>Time</th>
                <th>Date</th>
                <th>Repeat</th>
                <th>Notification</th>
                <th>Notes</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($row['medication_name'] ?? ''); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['dosage'] ?? 'N/A'); ?></td>
                        <td><?php echo !empty($row['reminder_time']) ? date('H:i', strtotime($row['reminder_time'])) : 'N/A'; ?></td>
                        <td><?php echo htmlspecialchars($row['reminder_date'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($row['repeat_frequency'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($row['notification_type'] ?? 'Push Notification'); ?></td>
                        <td><?php echo htmlspecialchars($row['notes'] ?? '-'); ?></td>
                        <td>
                            <a href="deletereminder.php?id=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this reminder?');">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" style="text-align: center; color: #64748b; padding: 20px;">No reminders configured yet.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
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
<?php 
if ($stmt) {
    mysqli_stmt_close($stmt);
}
mysqli_close($conn); 
?>