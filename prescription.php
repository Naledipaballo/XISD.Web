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

// 2. Handle File Upload & Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_prescription'])) {
    $patient_name        = trim($_POST['patient_name']);
    $doctor_name         = trim($_POST['doctor_name']);
    $prescription_number = trim($_POST['prescription_number']);
    $prescription_date   = $_POST['prescription_date'];
    $delivery_method     = $_POST['delivery_method'];
    $notes               = trim($_POST['notes']);

    // Check if file was uploaded
    if (isset($_FILES['prescription_file']) && $_FILES['prescription_file']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "uploads/prescriptions/";

        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $original_name = basename($_FILES["prescription_file"]["name"]);
        $file_ext      = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        $allowed_types = array('pdf', 'jpg', 'jpeg', 'png');

        if (in_array($file_ext, $allowed_types)) {
            $new_filename = "rx_" . $user_id . "_" . time() . "." . $file_ext;
            $target_file  = $target_dir . $new_filename;

            if (move_uploaded_file($_FILES["prescription_file"]["tmp_name"], $target_file)) {
                $insert_sql = "INSERT INTO prescriptions 
                    (user_id, patient_name, doctor_name, prescription_number, prescription_date, delivery_method, file_name, notes, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending')";

                $stmt = mysqli_prepare($conn, $insert_sql);
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "isssssss", $user_id, $patient_name, $doctor_name, $prescription_number, $prescription_date, $delivery_method, $new_filename, $notes);

                    if (mysqli_stmt_execute($stmt)) {
                        $message = "Prescription uploaded successfully! Status: Pending Approval.";
                    } else {
                        $message = "Database Error: Could not save record.";
                    }
                    mysqli_stmt_close($stmt);
                }
            } else {
                $message = "Error uploading prescription file to server.";
            }
        } else {
            $message = "Invalid file format. Only PDF, JPG, JPEG, and PNG files are allowed.";
        }
    } else {
        $message = "Please attach a valid prescription file.";
    }
}

// 3. Fetch user's previous uploads
$rx_query = "SELECT * FROM prescriptions WHERE user_id = ? ORDER BY upload_date DESC";
$rx_stmt = mysqli_prepare($conn, $rx_query);
mysqli_stmt_bind_param($rx_stmt, "i", $user_id);
mysqli_stmt_execute($rx_stmt);
$prescriptions = mysqli_stmt_get_result($rx_stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescription - Pill Point Delivery</title>
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
        .rx-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
            background: #fff;
        }
        .rx-table th, .rx-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .rx-table th {
            background-color: #007bff;
            color: white;
        }
    </style>
</head>

<body>

<!-- Header -->
<div class="header">
    <span class="menu-btn" onclick="openNav()">&#9776;</span>
    <h1 class="title">Pill Point Delivery</h1>
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
    <a href="myaccount.php">My Account</a>
    <a href="contact.php">Contact Us</a>
    <a href="logout.php">Logout</a>
</div>

<div id="main">
    <h1 class="title">Prescriptions</h1>

    <div class="prescription-container">

        <?php if (!empty($message)): ?>
            <div class="alert-msg"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form action="prescription.php" method="POST" enctype="multipart/form-data">

            <div class="row">
                <div class="input-box">
                    <label>Patient Name</label>
                    <input type="text" name="patient_name" placeholder="Enter Patient Name" required>
                </div>

                <div class="input-box">
                    <label>Doctor's Name</label>
                    <input type="text" name="doctor_name" placeholder="Enter Doctor's Name" required>
                </div>
            </div>

            <div class="row">
                <div class="input-box full">
                    <label>Prescription Number</label>
                    <input type="text" name="prescription_number" placeholder="Prescription Number">
                </div>
            </div>

            <div class="row">
                <div class="input-box">
                    <label>Prescription Date</label>
                    <input type="date" name="prescription_date" required>
                </div>

                <div class="input-box">
                    <label>Delivery Method</label>
                    <select name="delivery_method" required>
                        <option value="">Select Method</option>
                        <option value="Home Delivery">Home Delivery</option>
                        <option value="Collect In Store">Collect In Store</option>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="input-box">
                    <label>Upload Prescription (PDF, JPG, PNG)</label>
                    <input type="file" name="prescription_file" accept=".pdf, .jpg, .jpeg, .png" required>
                </div>

                <div class="input-box">
                    <label>Additional Notes</label>
                    <textarea name="notes" rows="4" placeholder="Enter any special instructions"></textarea>
                </div>
            </div>

            <button type="submit" name="submit_prescription" class="btn">Upload Prescription</button>

        </form>

        <!-- Previous Prescriptions Table -->
        <h3 style="margin-top: 30px;">Your Uploaded Prescriptions</h3>
        <table class="rx-table">
            <thead>
                <tr>
                    <th>Patient</th>
                    <th>Doctor</th>
                    <th>Method</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>File</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($prescriptions) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($prescriptions)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['patient_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['doctor_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['delivery_method']); ?></td>
                            <td><?php echo $row['prescription_date']; ?></td>
                            <td><strong><?php echo htmlspecialchars($row['status']); ?></strong></td>
                            <td>
                                <a href="uploads/prescriptions/<?php echo htmlspecialchars($row['file_name']); ?>" target="_blank">View File</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center;">No uploaded prescriptions found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

    </div>
</div>

<script src="script.js"></script>

</body>
</html>
<?php 
mysqli_stmt_close($rx_stmt);
mysqli_close($conn); 
?>

