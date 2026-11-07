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

// Handle pharmacy selection via POST (stores in session then redirects to checkout)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pharmacy_name'])) {
    $_SESSION['selected_pharmacy'] = [
        'name' => filter_input(INPUT_POST, 'pharmacy_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
        'address' => filter_input(INPUT_POST, 'pharmacy_address', FILTER_SANITIZE_FULL_SPECIAL_CHARS)
    ];
    header("Location: checkout.php");
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

    <!-- Leaflet CSS for Interactive Map -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>

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

        .locator-container {
            width: 90%;
            max-width: 1000px;
            margin: 110px auto 40px;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.10);
        }

        .locator-container h2 {
            color: #003049;
            margin-bottom: 10px;
        }

        /* Stacked search layout: input above button */
        .search-wrapper {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin: 20px 0;
            width: 100%;
        }

        .search-wrapper input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 7px;
            font-size: 16px;
            outline: none;
            box-sizing: border-box;
        }

        .btn {
            background: #669BBC;
            color: white;
            border: none;
            padding: 12px 22px;
            border-radius: 7px;
            cursor: pointer;
            font-weight: bold;
            width: 100%;
            font-size: 16px;
        }

        .btn:hover {
            background: #003049;
        }

        #map {
            width: 100%;
            height: 450px;
            border-radius: 10px;
            border: 1px solid #cbd5e1;
            margin-top: 15px;
        }

        .pharmacy-list {
            margin-top: 25px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 15px;
        }

        .pharmacy-card {
            background: #DFEEF3;
            border: 1px solid #bde0fe;
            border-radius: 8px;
            padding: 15px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .pharmacy-card h4 {
            color: #003049;
            margin-bottom: 5px;
            font-size: 16px;
        }

        .pharmacy-card p {
            font-size: 13px;
            color: #475569;
            margin-bottom: 12px;
        }

        .select-btn {
            background: #669BBC;
            color: white;
            border: none;
            padding: 8px 14px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            text-align: center;
        }

        .select-btn:hover {
            background: #003049;
        }
    </style>
</head>

<body>

<!-- HEADER -->
<div class="header">
    <span class="menu-btn" onclick="openNav()">&#9776;</span>
    <h1 class="header-title">Store Locator</h1>
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
    <a href="cart.php">My Cart</a>
    <a href="trackorder.php">Track Order</a>
    <a href="reminders.php">Reminders</a>
    <a href="storelocator.php">Store Locator</a>
    <a href="myaccount.php">My Account</a>
    <a href="logout.php">Logout</a>
</div>

<!-- STORE LOCATOR -->
<div class="locator-container">
    <p>Search your town or area to discover partner pharmacies near you. Click any pin on the map or select a pharmacy below to proceed directly to checkout.</p>

    <!-- Search box positioned directly above the Find Pharmacy button -->
    <div class="search-wrapper">
        <input type="text" id="address" placeholder="Enter town or area (e.g. Springs, Brakpan, Benoni)">
        <button onclick="findStore()" class="btn">Find Pharmacy</button>
    </div>

    <!-- Map Element -->
    <div id="map"></div>

    <!-- Available Pharmacies Selection List -->
    <h3 style="margin-top: 30px; color: #003049;">Partner Pharmacies</h3>
    <div class="pharmacy-list" id="pharmacy-list-container">
        <!-- Rendered via JavaScript -->
    </div>
</div>

<!-- Hidden Form for Checkout Redirect -->
<form id="pharmacy-form" action="storelocator.php" method="POST" style="display:none;">
    <input type="hidden" name="pharmacy_name" id="form-pharmacy-name">
    <input type="hidden" name="pharmacy_address" id="form-pharmacy-address">
</form>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    var map;
    var markersGroup;

    // Sample Partner Pharmacies Dataset
    var pharmacies = [
        { name: "Pill Point Central Pharmacy", address: "14 4th Street, Springs CBD, Springs", lat: -26.2550, lng: 28.4380 },
        { name: "Dis-Chem Pharmacy Springs Mall", address: "Springs Mall, Jan Smuts Rd, Casseldale, Springs", lat: -26.2731, lng: 28.4485 },
        { name: "Clicks Pharmacy Selection Park", address: "Selection Park Shopping Centre, Springs", lat: -26.2652, lng: 28.4290 },
        { name: "Pill Point Brakpan Branch", address: "623 Voortrekker Rd, Brakpan", lat: -26.2361, lng: 28.3698 },
        { name: "Clicks Pharmacy Mall@Range", address: "Mall at Range, Brakpan", lat: -26.2290, lng: 28.3580 }
    ];

    document.addEventListener("DOMContentLoaded", function() {
        // Center default view on Springs / East Rand
        map = L.map('map').setView([-26.2585, 28.4398], 13);

        // Standard OpenStreetMap tiles showing full street names
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        markersGroup = L.layerGroup().addTo(map);

        renderPharmacies(pharmacies);
    });

    function selectPharmacy(name, address) {
        document.getElementById("form-pharmacy-name").value = name;
        document.getElementById("form-pharmacy-address").value = address;
        document.getElementById("pharmacy-form").submit();
    }

    function renderPharmacies(list) {
        markersGroup.clearLayers();
        var container = document.getElementById("pharmacy-list-container");
        container.innerHTML = "";

        if (list.length === 0) {
            container.innerHTML = "<p>No pharmacies found in this area. Showing all available branches.</p>";
            return;
        }

        list.forEach(function(pharm) {
            // Add Map Marker
            var marker = L.marker([pharm.lat, pharm.lng]).addTo(markersGroup);
            
            var popupContent = `
                <div style="text-align:center; padding: 5px;">
                    <b style="color:#003049;">${pharm.name}</b><br>
                    <small>${pharm.address}</small><br><br>
                    <button onclick="selectPharmacy('${pharm.name.replace(/'/g, "\\'")}', '${pharm.address.replace(/'/g, "\\'")}')" 
                            style="background:#669BBC; color:white; border:none; padding:6px 12px; border-radius:4px; cursor:pointer; font-weight:bold;">
                        Select & Proceed to Checkout
                    </button>
                </div>
            `;
            marker.bindPopup(popupContent);

            // Add Card Below Map
            var card = document.createElement("div");
            card.className = "pharmacy-card";
            card.innerHTML = `
                <div>
                    <h4>${pharm.name}</h4>
                    <p>${pharm.address}</p>
                </div>
                <button class="select-btn" onclick="selectPharmacy('${pharm.name.replace(/'/g, "\\'")}', '${pharm.address.replace(/'/g, "\\'")}')">
                    Select Pharmacy
                </button>
            `;
            container.appendChild(card);
        });
    }

    function findStore() {
        var query = document.getElementById("address").value.trim();
        if (query === "") {
            alert("Please enter a town or address.");
            return;
        }

        // Search OpenStreetMap Nominatim Geocoding API
        fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query + ", South Africa")}`)
            .then(response => response.json())
            .then(data => {
                if (data && data.length > 0) {
                    var lat = parseFloat(data[0].lat);
                    var lon = parseFloat(data[0].lon);
                    
                    map.setView([lat, lon], 13);
                } else {
                    alert("Location not found. Showing nearby default partner stores.");
                }
            })
            .catch(err => {
                console.error(err);
                alert("Error searching location. Please try again.");
            });
    }
</script>

<script src="script.js"></script>

</body>
</html>
<?php mysqli_close($conn); ?>