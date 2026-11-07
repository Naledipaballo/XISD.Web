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

// Handle Prescription Upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && (isset($_POST['upload_prescription']) || isset($_POST['add_prescription']))) {
    $patient_name = trim($_POST['patient_name'] ?? '');
    $doctor_name = trim($_POST['doctor_name'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    
    if (empty($patient_name) || empty($doctor_name)) {
        $error_message = "Please fill in both the patient name and doctor's name.";
    } elseif (!isset($_FILES['prescription_file']) || $_FILES['prescription_file']['error'] !== UPLOAD_ERR_OK) {
        $error_code = $_FILES['prescription_file']['error'] ?? 0;
        if ($error_code === 1 || $error_code === 2) {
            $error_message = "The uploaded file is too large for the server to accept.";
        } else {
            $error_message = "Please select a valid prescription file to upload. (Error code: " . $error_code . ")";
        }
    } else {
        $file_tmp = $_FILES['prescription_file']['tmp_name'];
        $file_size = $_FILES['prescription_file']['size'];
        
        // Limit to 5MB to prevent server crashes
        if ($file_size > 5 * 1024 * 1024) {
            $error_message = "File size must be under 5MB.";
        } else {
            $file_name = time() . "_" . preg_replace("/[^a-zA-Z0-9\._-]/", "", basename($_FILES['prescription_file']['name']));
            $upload_dir = "uploads/";

            if (!is_dir($upload_dir)) {
                @mkdir($upload_dir, 0755, true);
            }

            $target_path = $upload_dir . $file_name;

            if (@move_uploaded_file($file_tmp, $target_path)) {
                $stmt = mysqli_prepare($conn, "INSERT INTO prescriptions (user_id, patient_name, doctor_name, file_name, notes, status, upload_date) VALUES (?, ?, ?, ?, ?, 'Pending', NOW())");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "issss", $user_id, $patient_name, $doctor_name, $target_path, $notes);
                    if (mysqli_stmt_execute($stmt)) {
                        $success_message = "Prescription uploaded successfully!";
                    } else {
                        $error_message = "Database error: " . mysqli_stmt_error($stmt);
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $error_message = "Database query preparation failed: " . mysqli_error($conn);
                }
            } else {
                $error_message = "Failed to move uploaded file into the uploads folder.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Prescription - Pill Point Delivery</title>
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

        .prescription-container {
            max-width: 650px;
            margin: 110px auto 40px auto;
            background: #fff;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .prescription-container h2 {
            color: #003049;
            text-align: center;
            margin-bottom: 8px;
        }

        .prescription-container p.subtitle {
            text-align: center;
            color: #64748b;
            margin-bottom: 25px;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
            color: #003049;
            font-size: 14px;
        }

        .form-group input, 
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

<div class="prescription-container">
    <h2>Upload Prescription</h2>
    <p class="subtitle">Submit your doctor's prescription for quick fulfillment</p>

    <?php if (!empty($error_message)): ?>
        <p style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 6px; text-align: center; font-weight: bold; margin-bottom: 20px;"><?php echo htmlspecialchars($error_message); ?></p>
    <?php endif; ?>

    <?php if (!empty($success_message)): ?>
        <p style="background: #d4edda; color: #155724; padding: 12px; border-radius: 6px; text-align: center; font-weight: bold; margin-bottom: 20px;"><?php echo htmlspecialchars($success_message); ?></p>
    <?php endif; ?>

    <form action="prescription.php" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Patient Full Name *</label>
            <input type="text" name="patient_name" required placeholder="e.g. Naledi Tshelane">
        </div>
        <div class="form-group">
            <label>Doctor's Name *</label>
            <input type="text" name="doctor_name" required placeholder="e.g. Dr. Smith">
        </div>
        <div class="form-group">
            <label>Upload Prescription File (PDF or Image, Max 5MB) *</label>
            <input type="file" name="prescription_file" accept=".pdf, .jpg, .jpeg, .png" required style="padding: 10px; background: #f8f9fa;">
        </div>
        <div class="form-group">
            <label>Extra Information / Special Instructions (Optional)</label>
            <textarea name="notes" rows="3" placeholder="Add any extra notes or instructions..."></textarea>
        </div>
        <button type="submit" name="upload_prescription" class="btn">Upload Prescription</button>
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