<?php

session_start();

include("dbconnect.php");


/* Check if customer is logged in */

if (!isset($_SESSION['user_id'])) {

    echo "<script>
            alert('Please login first.');
            window.location.href='login.html';
          </script>";

    exit();
}


if ($_SERVER["REQUEST_METHOD"] != "POST") {

    header("Location: upload_prescription.html");
    exit();

}


$user_id = $_SESSION['user_id'];


/* Check if a file was uploaded */

if (!isset($_FILES['prescription']) ||
    $_FILES['prescription']['error'] != 0) {

    echo "<script>
            alert('Please select a prescription file.');
            window.location.href='upload_prescription.html';
          </script>";

    exit();
}


$file = $_FILES['prescription'];

$file_name = $file['name'];
$file_tmp = $file['tmp_name'];
$file_size = $file['size'];


/* Get file extension */

$file_extension =
    strtolower(
        pathinfo(
            $file_name,
            PATHINFO_EXTENSION
        )
    );


/* Allowed file types */

$allowed_types = array(
    "jpg",
    "jpeg",
    "png",
    "pdf"
);


if (!in_array($file_extension, $allowed_types)) {

    echo "<script>
            alert('Only JPG, JPEG, PNG and PDF files are allowed.');
            window.location.href='upload_prescription.html';
          </script>";

    exit();
}


/* Maximum file size: 5 MB */

if ($file_size > 5 * 1024 * 1024) {

    echo "<script>
            alert('File size must be less than 5 MB.');
            window.location.href='upload_prescription.html';
          </script>";

    exit();
}


/* Create a unique file name */

$new_file_name =
    "prescription_" .
    $user_id . "_" .
    time() . "." .
    $file_extension;


$upload_directory = "uploads/";


/* Make sure uploads folder exists */

if (!is_dir($upload_directory)) {

    mkdir(
        $upload_directory,
        0755,
        true
    );
}


$file_path =
    $upload_directory .
    $new_file_name;


/* Move uploaded file */

if (move_uploaded_file(
    $file_tmp,
    $file_path
)) {

    /*
       Save file information in database
    */

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO prescriptions
        (user_id, file_name, file_path)
        VALUES (?, ?, ?)"
    );


    mysqli_stmt_bind_param(
        $stmt,
        "iss",
        $user_id,
        $file_name,
        $file_path
    );


    if (mysqli_stmt_execute($stmt)) {

        echo "<script>
                alert('Prescription uploaded successfully!');
                window.location.href='shop.html';
              </script>";

    } else {

        /* Delete uploaded file if database save fails */

        if (file_exists($file_path)) {
            unlink($file_path);
        }

        echo "<script>
                alert('Prescription could not be saved.');
                window.location.href='upload_prescription.html';
              </script>";
    }


    mysqli_stmt_close($stmt);

} else {

    echo "<script>
            alert('File upload failed.');
            window.location.href='upload_prescription.html';
          </script>";
}


mysqli_close($conn);

?>