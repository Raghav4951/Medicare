<?php
session_start();
include "../db.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != "patient") {
    header("Location: ../login.php");
    exit();
}

if (isset($_GET['id'])) {

    $appointment_id = $_GET['id'];
    $patient_id = $_SESSION['user_id'];

    // Make sure patient owns this appointment
    $check = mysqli_query($conn, "
        SELECT * FROM appointments 
        WHERE id='$appointment_id' AND patient_id='$patient_id'
    ");

    if (mysqli_num_rows($check) > 0) {

        mysqli_query($conn, "
            UPDATE appointments 
            SET status='Cancelled' 
            WHERE id='$appointment_id'
        ");
    }
}

header("Location: my-appointment.php");
exit();