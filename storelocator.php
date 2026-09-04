<?php
session_start();
include("dbconnect.php");

// Verify user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<script>
            alert('Please login first.');
            window.location.href = 'login.html';
          </script>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store Locator - Pill Point Delivery</title>
    <link rel="stylesheet" href="style.css">

    <script>
        function findStore(){
            var address = document.getElementById("address").value;

            if(address === ""){
                alert("Please enter your address.");
                return;
            }

            var map = "https://www.google.com/maps?q=" +
                      encodeURIComponent(address + " pharmacy") +
                      "&output=embed";

            document.getElementById("map").src = map;
        }
    </script>
</head>

<body>

<!-- HEADER -->
<div class="header">
    <span class="menu-btn" onclick="openNav()">&#9776;</span>
    <h1 class="header-title">Pill Point Delivery</h1>
    <a href="myaccount.php">
        <img src="photo.jpeg" class="profile-icon" alt="Profile">
    </a>
</div>

<!-- SIDEBAR -->
<div id="sidebar" class="sidebar">
    <a href="javascript:void(0)" class="closebtn" onclick="closeNav()">&times;</a>
    <a href="dashboard.php">Dashboard</a>
    <a href="medicines.php">Shop Medication</a>
    <a href="prescription.php">Prescription</a>
    <a href="reminders.php">Reminders</a>
    <a href="storelocator.php">Store Locator</a>
    <a href="cart.php">Cart</a>
    <a href="myaccount.php">My Account</a>
    <a href="contact.html">Contact Us</a>
    <a href="logout.php">Logout</a>
</div>

<!-- STORE LOCATOR -->
<div class="locator-container">
    <h2>Store Locator</h2>
    <p>Enter your address to find the nearest pharmacy.</p>

    <input type="text" id="address" placeholder="Enter your address">

    <button onclick="findStore()" class="btn">
        Find Pharmacy
    </button>

    <iframe id="map" src="https://www.google.com/maps?q=pharmacy&output=embed" style="width:100%; height:400px; border:0; margin-top:20px;"></iframe>
</div>

<script src="script.js"></script>

</body>
</html>
<?php mysqli_close($conn); ?>