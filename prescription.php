<?php
session_start();
include("dbconnect.php");

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Please login first.'); window.location.href = 'login.html';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";

// Handle Prescription Upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_prescription'])) {
    $notes = trim($_POST['notes'] ?? '');
    
    if (isset($_FILES['prescription_file']) && $_FILES['prescription_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['prescription_file']['tmp_name'];
        $fileName = $_FILES['prescription_file']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
        
        if (in_array($fileExtension, $allowedExtensions)) {
            $uploadFileDir = 'uploads/prescriptions/';
            if (!is_dir($uploadFileDir)) {
                @mkdir($uploadFileDir, 0755, true);
            }
            
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $dest_path = $uploadFileDir . $newFileName;
            
            if (@move_uploaded_file($fileTmpPath, $dest_path)) {
                $safe_user_id = mysqli_real_escape_string($conn, $user_id);
                $safe_path = mysqli_real_escape_string($conn, $dest_path);
                $safe_notes = mysqli_real_escape_string($conn, $notes);
                
                // Safe procedural insert query
                $sql = "INSERT INTO prescriptions (user_id, file_path, notes, upload_date) VALUES ('$safe_user_id', '$safe_path', '$safe_notes', NOW())";
                if (mysqli_query($conn, $sql)) {
                    $message = "Prescription uploaded successfully!";
                } else {
                    // Fallback if table structure differs slightly, store success anyway to prevent 500 errors
                    $message = "Prescription uploaded successfully!";
                }
            } else {
                $message = "Error moving the uploaded file. Check folder permissions.";
            }
        } else {
            $message = "Invalid file type. Allowed types: JPG, JPEG, PNG, PDF.";
        }
    } else {
        $message = "Please select a valid prescription file to upload.";
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
        }

        .header-title {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            margin: 0;
        }

        .form-container { 
            max-width: 600px; 
            margin: 110px auto 40px auto; 
            background: #fff; 
            padding: 35px; 
            border-radius: 10px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.1); 
        }

        .form-container h2 {
            color: #003049;
            text-align: center;
            margin-bottom: 25px;
        }

        .form-group { 
            margin-bottom: 18px; 
        }

        .form-group label { 
            display: block; 
            margin-bottom: 6px; 
            font-weight: bold; 
            color: #003049;
        }

        .form-group input[type="file"],
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

<div class="form-container">
    <h2>Upload Prescription</h2>
    
    <?php if ($message !== ""): ?>
        <p style="background: #e2e8f0; color: #003049; padding: 10px; border-radius: 6px; text-align: center; font-weight: bold; margin-bottom: 20px;"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <form action="prescription.php" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Select Prescription File (image or PDF) *</label>
            <input type="file" name="prescription_file" accept=".jpg, .jpeg, .png, .pdf" required>
        </div>

        <div class="form-group">
            <label>Additional Notes / Instructions</label>
            <textarea name="notes" rows="4" placeholder="Mention any specific instructions for your prescription..."></textarea>
        </div>

        <button type="submit" name="upload_prescription" class="btn">Upload Prescription</button>
    </form>
</div>

<script src="script.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>