<?php

$servername = "localhost";
$username = "root";
$password = "";
$database = "pillpointdelivery";

$conn = mysqli_connect($servername,$username,$password,$database);

if(!$conn)
{
    die("Connection Failed: " . mysqli_connect_error());
}

?>

<?php
include("dbconnect.php");

$medicine = $_POST['medicine_name'];
$price = $_POST['price'];

$check = mysqli_query($conn,
"SELECT * FROM cart WHERE medicine_name='$medicine'");

if(mysqli_num_rows($check) > 0)
{
    mysqli_query($conn,
    "UPDATE cart
     SET quantity = quantity + 1
     WHERE medicine_name='$medicine'");
}
else
{
    mysqli_query($conn,
    "INSERT INTO cart (medicine_name, price, quantity)
     VALUES ('$medicine', '$price', 1)");
}

header("Location: cart.php");
exit();
?>