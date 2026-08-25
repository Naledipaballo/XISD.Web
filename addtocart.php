<?php

include("dbconnect.php");

$medicine = $_POST['medicine_name'];
$price = $_POST['price'];

/* Check if the medicine is already in the cart */
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
    "INSERT INTO cart(medicine_name,price,quantity)
     VALUES('$medicine','$price',1)");
}

echo "<script>
alert('Medicine added to cart!');
window.location='cart.php';
</script>";

?>