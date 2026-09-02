<?php

session_start();

include("dbconnect.php");


if (!isset($_SESSION['user_id'])) {

    echo "<script>
            alert('Please login first.');
            window.location.href='login.html';
          </script>";

    exit();
}


$user_id = $_SESSION['user_id'];


$stmt = mysqli_prepare(
    $conn,
    "SELECT id,
            medicine_name,
            dosage,
            reminder_time,
            reminder_date
     FROM medication_reminders
     WHERE user_id = ?
     ORDER BY reminder_date ASC, reminder_time ASC"
);


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $user_id
);


mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

?>

<!DOCTYPE html>
<html>

<head>

    <title>Pill Point Delivery - My Reminders</title>

    <link rel="stylesheet" href="css/style.css">

</head>

<body>

<header>

    <h1>Pill Point Delivery</h1>

    <nav>
        <a href="home.html">Home</a>
        <a href="shop.html">Shop</a>
        <a href="cart.html">Cart</a>
        <a href="track_order.html">Track Order</a>
        <a href="my_account.html">My Account</a>
        <a href="medication_reminders.html">Reminders</a>
        <a href="logout.php">Logout</a>
    </nav>

</header>


<main>

    <div class="container">

        <h2>My Medication Reminders</h2>


        <?php

        if (mysqli_num_rows($result) == 0) {

            echo "<p>You do not have any medication reminders.</p>";

        } else {

        ?>

        <table>

            <thead>

                <tr>
                    <th>Medicine</th>
                    <th>Dosage</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Action</th>
                </tr>

            </thead>

            <tbody>

            <?php

            while ($reminder = mysqli_fetch_assoc($result)) {

            ?>

                <tr>

                    <td>
                        <?php
                        echo htmlspecialchars(
                            $reminder['medicine_name']
                        );
                        ?>
                    </td>

                    <td>
                        <?php
                        echo htmlspecialchars(
                            $reminder['dosage']
                        );
                        ?>
                    </td>

                    <td>
                        <?php
                        echo htmlspecialchars(
                            $reminder['reminder_date']
                        );
                        ?>
                    </td>

                    <td>
                        <?php
                        echo htmlspecialchars(
                            $reminder['reminder_time']
                        );
                        ?>
                    </td>

                    <td>

                        <form action="delete_reminder.php"
                              method="POST">

                            <input type="hidden"
                                   name="reminder_id"
                                   value="<?php
                                   echo $reminder['id'];
                                   ?>">

                            <button type="submit">
                                Delete
                            </button>

                        </form>

                    </td>

                </tr>

            <?php

            }

            ?>

            </tbody>

        </table>

        <?php

        }

        ?>

        <br>

        <a href="medication_reminders.html">
            <button type="button">
                Add New Reminder
            </button>
        </a>

        <a href="home.html">
            <button type="button">
                Back to Home
            </button>
        </a>

    </div>

</main>

</body>

</html>

<?php

mysqli_stmt_close($stmt);
mysqli_close($conn);

?>