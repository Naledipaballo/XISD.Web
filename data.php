<?php

$patient = $_POST['patient'];
$doctor = $_POST['doctor'];
$date = $_POST['date'];
$notes = $_POST['notes'];

$fileName = $_FILES["prescription"]["name"];
$tempName = $_FILES["prescription"]["tmp_name"];

$folder = "uploads/" . $fileName;

if(move_uploaded_file($tempName, $folder))
{
    echo "<script>
    alert('Prescription uploaded successfully.');
    window.location='dashboard.html';
    </script>";
}
else
{
    echo "<script>
    alert('Upload failed.');
    window.location='prescription.html';
    </script>";
}

?>