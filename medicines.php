Dont<?php
session_start();
include("dbconnect.php");

// Handle Add to Cart action when a button is clicked
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['user_id'])) {
        echo "<script>alert('Please login first.'); window.location.href = 'login.html';</script>";
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $medicine_name = trim($_POST['medicine_name']);
    $price = (float)$_POST['price'];
    $quantity = 1;

    // Check if item already exists in the user's cart
    $check_sql = "SELECT id, quantity FROM cart WHERE user_id = ? AND medicine_name = ?";
    $stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($stmt, "is", $user_id, $medicine_name);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        // Increment quantity if it already exists
        $new_qty = $row['quantity'] + 1;
        $update_sql = "UPDATE cart SET quantity = ? WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "ii", $new_qty, $row['id']);
        mysqli_stmt_execute($update_stmt);
        mysqli_stmt_close($update_stmt);
    } else {
        // Insert new item into cart
        $insert_sql = "INSERT INTO cart (user_id, medicine_name, price, quantity) VALUES (?, ?, ?, ?)";
        $insert_stmt = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($insert_stmt, "isdi", $user_id, $medicine_name, $price, $quantity);
        mysqli_stmt_execute($insert_stmt);
        mysqli_stmt_close($insert_stmt);
    }
    mysqli_stmt_close($stmt);

    echo "<script>alert('Medication added to cart successfully!'); window.location.href = 'medicines.php';</script>";
    exit();
}

// Fetch all available medicines from the database (optional fallback check)
$query = "SELECT * FROM medicines ORDER BY medicine_name ASC";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Shop Medication - Pill Point Delivery</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<!-- Header -->
<div class="header">
    <span class="menu-btn" onclick="openNav()">&#9776;</span>
    <h1 class="header-title">Pill Point Delivery</h1>
    <a href="myaccount.html">
        <img src="photo.jpeg" class="profile-icon">
    </a>
</div>

<!-- Sidebar -->
<div id="sidebar" class="sidebar">
    <a href="javascript:void(0)" class="closebtn" onclick="closeNav()">&times;</a>
    <a href="dashboard.php">Dashboard</a>
    <a href="medicines.php">Shop Medication</a>
    <a href="prescription.php">Prescription</a>
    <a href="cart.php">My Cart</a>
    <a href="trackorder.php">Track Order</a>
    <a href="storelocator.php">Store Locator</a>
    <a href="myaccount.php">My Account</a>
    <a href="contact.php">Contact Us</a>
    <a href="login.html">Logout</a>
</div>

<!-- Shop -->
<div class="shop-container">

<h2>Shop Medication</h2>

<input type="text" id="searchInput" placeholder="Search medication..." onkeyup="searchMedicine()" class="search-box">

<div class="products" id="products">

    <!-- 1. Andolex-C Oral Gel -->
    <div class="product-card">
        <img src="medication/andolex-c-oralgel.jpg" alt="Andolex-C Oral Gel">
        <h3>andolex-c oral gel</h3>
        <h4>18g</h4>
        <p>R126.99</p>
        <form action="medicines.php" method="POST">
            <input type="hidden" name="medicine_name" value="andolex-c oral gel">
            <input type="hidden" name="price" value="126.99">
            <button type="submit" name="add_to_cart" class="cart-btn">Add to Cart</button>
        </form>
    </div>

    <!-- 2. Vicks Syrup -->
    <div class="product-card">
        <img src="medication/vicks-syrup.jpeg" alt="Vicks Syrup">
        <h3>Vicks Syrup</h3>
        <p>R120.00</p>
        <form action="medicines.php" method="POST">
            <input type="hidden" name="medicine_name" value="Vicks Syrup">
            <input type="hidden" name="price" value="120.00">
            <button type="submit" name="add_to_cart" class="cart-btn">Add to Cart</button>
        </form>
    </div>

    <!-- 3. Allergex Syrup -->
    <div class="product-card">
        <img src="medication/allergex-syrup.jpg" alt="Allergex Syrup">
        <h3>allergex syrup</h3>
        <p>R18.99</p>
        <form action="medicines.php" method="POST">
            <input type="hidden" name="medicine_name" value="allergex syrup">
            <input type="hidden" name="price" value="18.99">
            <button type="submit" name="add_to_cart" class="cart-btn">Add to Cart</button>
        </form>
    </div>

    <!-- 4. Allergex Tablets -->
    <div class="product-card">
        <img src="medication/allergex-tablets.jpg" alt="Allergex Tablets">
        <h3>allergex tablets</h3>
        <p>R25.99</p>
        <form action="medicines.php" method="POST">
            <input type="hidden" name="medicine_name" value="allergex tablets">
            <input type="hidden" name="price" value="25.99">
            <button type="submit" name="add_to_cart" class="cart-btn">Add to Cart</button>
        </form>
    </div>

    <!-- 5. Band Aid Plastic -->
    <div class="product-card">
        <img src="medication/band-aid-plastic.jpg" alt="Band Aid Plastic">
        <h3>Band Aid Plastic</h3>
        <p>R39.99</p>
        <form action="medicines.php" method="POST">
            <input type="hidden" name="medicine_name" value="Band Aid Plastic">
            <input type="hidden" name="price" value="39.99">
            <button type="submit" name="add_to_cart" class="cart-btn">Add to Cart</button>
        </form>
    </div>  

    <!-- 6. Benylin Syrup -->
    <div class="product-card">
        <img src="medication/benelyin-syrup.jpg" alt="Benylin Syrup">
        <h3>Benelyin Syrup</h3>
        <h4>100ml</h4>
        <p>R79.99</p>
        <form action="medicines.php" method="POST">
            <input type="hidden" name="medicine_name" value="Benelyin Syrup">
            <input type="hidden" name="price" value="79.99">
            <button type="submit" name="add_to_cart" class="cart-btn">Add to Cart</button>
        </form>
    </div>  
  
    <!-- 7. Berocca -->
    <div class="product-card">
        <img src="medication/berocca.jpg" alt="Berocca">
        <h3>Berocca</h3>
        <p>R120.00</p>
        <form action="medicines.php" method="POST">
            <input type="hidden" name="medicine_name" value="Berocca">
            <input type="hidden" name="price" value="120.00">
            <button type="submit" name="add_to_cart" class="cart-btn">Add to Cart</button>
        </form>
    </div>
    
    <!-- 8. Calpol -->
    <div class="product-card">
        <img src="medication/calpol.jpg" alt="Calpol">
        <h3>Calpol</h3>
        <h4>100ml</h4>
        <p>R67.99</p>
        <form action="medicines.php" method="POST">
            <input type="hidden" name="medicine_name" value="Calpol">
            <input type="hidden" name="price" value="67.99">
            <button type="submit" name="add_to_cart" class="cart-btn">Add to Cart</button>
        </form>
    </div>  

    <!-- 9. Canvex V -->
    <div class="product-card">
        <img src="medication/canvexv.jpeg" alt="Canvex V">
        <h3>Canvex V</h3>
        <p>R89.00</p>
        <form action="medicines.php" method="POST">
            <input type="hidden" name="medicine_name" value="Canvex V">
            <input type="hidden" name="price" value="89.00">
            <button type="submit" name="add_to_cart" class="cart-btn">Add to Cart</button>
        </form>
    </div>  

    <!-- 10. Compral -->
    <div class="product-card">
        <img src="medication/compral.jpg" alt="Compral">
        <h3>Compral</h3>
        <p>R83.99</p>
        <form action="medicines.php" method="POST">
            <input type="hidden" name="medicine_name" value="Compral">
            <input type="hidden" name="price" value="83.99">
            <button type="submit" name="add_to_cart" class="cart-btn">Add to Cart</button>
        </form>
    </div> 
    
    <!-- 11. Coryx Effervescent Tablets -->
    <div class="product-card">
        <img src="medication/coryx.png" alt="Coryx Effervescent Tablets">
        <h3>Coryx Effervescent Tablets</h3>
        <p>R72.99</p>
        <form action="medicines.php" method="POST">
            <input type="hidden" name="medicine_name" value="Coryx Effervescent Tablets">
            <input type="hidden" name="price" value="72.99">
            <button type="submit" name="add_to_cart" class="cart-btn">Add to Cart</button>
        </form>
    </div> 
    
    <!-- 12. Deep Heat -->
    <div class="product-card">
        <img src="medication/deepheat.jpg" alt="Deep Heat">
        <h3>Deep Heat</h3>
        <h4>35g</h4>
        <p>R74.99</p>
        <form action="medicines.php" method="POST">
            <input type="hidden" name="medicine_name" value="Deep Heat">
            <input type="hidden" name="price" value="74.99">
            <button type="submit" name="add_to_cart" class="cart-btn">Add to Cart</button>
        </form>
    </div>  

    <!-- 13. Linctagon Effervescent Tablet -->
    <div class="product-card">
        <img src="medication/linctagon-tablets.jpg" alt="Linctagon Effervescent Tablet">
        <h3>Linctagon Effervescent Tablet</h3>
        <p>R178.00</p>
        <form action="medicines.php" method="POST">
            <input type="hidden" name="medicine_name" value="Linctagon Effervescent Tablet">
            <input type="hidden" name="price" value="178.00">
            <button type="submit" name="add_to_cart" class="cart-btn">Add to Cart</button>
        </form>
    </div>  

    <!-- 14. Med-Lemon -->
    <div class="product-card">
        <img src="medication/med-lemon.jpg" alt="Med-Lemon">
        <h3>Med-Lemon</h3>
        <p>R89.99</p>
        <form action="medicines.php" method="POST">
            <input type="hidden" name="medicine_name" value="Med-Lemon">
            <input type="hidden" name="price" value="89.99">
            <button type="submit" name="add_to_cart" class="cart-btn">Add to Cart</button>
        </form>
    </div>  

    <!-- 15. Panado Effervescent Tablets -->
    <div class="product-card">
        <img src="medication/panado-effervescent.jpg" alt="Panado Effervescent Tablets">
        <h3>Panado Effervescent Tablets</h3>
        <p>R62.99</p>
        <form action="medicines.php" method="POST">
            <input type="hidden" name="medicine_name" value="Panado Effervescent Tablets">
            <input type="hidden" name="price" value="62.99">
            <button type="submit" name="add_to_cart" class="cart-btn">Add to Cart</button>
        </form>
    </div> 
    
    <!-- 16. Panado Tablets -->
    <div class="product-card">
        <img src="medication/panado-tablets.jpg" alt="Panado Tablets">
        <h3>Panado Tablets</h3>
        <h4>500mg</h4>
        <p>R37.99</p>
        <form action="medicines.php" method="POST">
            <input type="hidden" name="medicine_name" value="Panado Tablets">
            <input type="hidden" name="price" value="37.99">
            <button type="submit" name="add_to_cart" class="cart-btn">Add to Cart</button>
        </form>
    </div>
    
    <!-- 17. Rennie -->
    <div class="product-card">
        <img src="medication/rennie.jpg" alt="Rennie">
        <h3>Rennie</h3>
        <p>R143.20</p>
        <form action="medicines.php" method="POST">
            <input type="hidden" name="medicine_name" value="Rennie">
            <input type="hidden" name="price" value="143.20">
            <button type="submit" name="add_to_cart" class="cart-btn">Add to Cart</button>
        </form>
    </div>  

    <!-- 18. SinuTab Nasal Spray -->
    <div class="product-card">
        <img src="medication/sinutab-nasalspray.jpg" alt="SinuTab Nasal Spray">
        <h3>SinuTab Nasal Spray</h3>
        <h4>10ml</h4>
        <p>R74.99</p>
        <form action="medicines.php" method="POST">
            <input type="hidden" name="medicine_name" value="SinuTab Nasal Spray">
            <input type="hidden" name="price" value="74.99">
            <button type="submit" name="add_to_cart" class="cart-btn">Add to Cart</button>
        </form>
    </div>
    
    <!-- 19. Slow Mag -->
    <div class="product-card">
        <img src="medication/slowmag.png" alt="Slow Mag">
        <h3>Slow Mag</h3>
        <p>R83.99</p>
        <form action="medicines.php" method="POST">
            <input type="hidden" name="medicine_name" value="Slow Mag">
            <input type="hidden" name="price" value="83.99">
            <button type="submit" name="add_to_cart" class="cart-btn">Add to Cart</button>
        </form>
    </div> 

</div>

</div>

<script src="script.js"></script>

<script>
function searchMedicine(){
    let input = document.getElementById("searchInput").value.toUpperCase();
    let cards = document.getElementsByClassName("product-card");

    for(let i=0; i<cards.length; i++){
        let title = cards[i].getElementsByTagName("h3")[0];
        if(title.innerHTML.toUpperCase().indexOf(input) > -1){
            cards[i].style.display = "block";
        } else {
            cards[i].style.display = "none";
        }
    }
}
</script>

</body>
</html>
<?php mysqli_close($conn); ?>