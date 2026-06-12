<?php
include '../db.php';
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'doctor') {
    exit('unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit('invalid');
}

$doctor_id = (int) $_SESSION['user_id'];
$appointment_id = isset($_POST['appointment_id']) ? (int) $_POST['appointment_id'] : 0;

if ($appointment_id <= 0) {
    exit('invalid');
}

/* get patient_id from appointment */
$appointmentQuery = mysqli_query($conn, "
    SELECT patient_id 
    FROM appointments 
    WHERE id = $appointment_id AND doctor_id = $doctor_id 
    LIMIT 1
");

if (!$appointmentQuery || mysqli_num_rows($appointmentQuery) === 0) {
    exit('appointment not found');
}

$appointmentData = mysqli_fetch_assoc($appointmentQuery);
$patient_id = (int) $appointmentData['patient_id'];

$medicine = mysqli_real_escape_string($conn, trim($_POST['medicines'] ?? ''));
$test = mysqli_real_escape_string($conn, trim($_POST['tests'] ?? ''));
$precaution = mysqli_real_escape_string($conn, trim($_POST['precautions'] ?? ''));

/* remove placeholder text */
if ($medicine === 'No medicines added yet.') {
    $medicine = '';
}
if ($test === 'No tests added yet.') {
    $test = '';
}
if ($precaution === 'No advice added yet.') {
    $precaution = '';
}

/* check existing prescription */
$check = mysqli_query($conn, "
    SELECT id 
    FROM prescriptions 
    WHERE appointment_id = $appointment_id AND doctor_id = $doctor_id 
    LIMIT 1
");

if ($check && mysqli_num_rows($check) > 0) {
    $existing = mysqli_fetch_assoc($check);
    $prescription_id = (int) $existing['id'];

    $update = mysqli_query($conn, "
        UPDATE prescriptions SET
            patient_id = $patient_id,
            medicine = '$medicine',
            test = '$test',
            precaution = '$precaution'
        WHERE id = $prescription_id
    ");

    if ($update) {
        mysqli_query($conn, "
            UPDATE appointments 
            SET status = 'Completed' 
            WHERE id = $appointment_id AND doctor_id = $doctor_id
        ");
        echo 'success';
    } else {
        echo 'error: ' . mysqli_error($conn);
    }
} else {
    $insert = mysqli_query($conn, "
        INSERT INTO prescriptions (
            appointment_id,
            doctor_id,
            patient_id,
            medicine,
            test,
            precaution
        ) VALUES (
            $appointment_id,
            $doctor_id,
            $patient_id,
            '$medicine',
            '$test',
            '$precaution'
        )
    ");

    if ($insert) {
        mysqli_query($conn, "
            UPDATE appointments 
            SET status = 'Completed' 
            WHERE id = $appointment_id AND doctor_id = $doctor_id
        ");
        echo 'success';
    } else {
        echo 'error: ' . mysqli_error($conn);
    }
}
?>