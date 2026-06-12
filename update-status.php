<?php
session_start();
include '../config.php'; // Ensure this points to your DB connection file
require_once '../vendor/autoload.php';

use Twilio\Rest\Client;

// Ensure doctor is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== "doctor") {
    header("Location: ../login.php");
    exit();
}

$doctor_id = (int) $_SESSION['user_id'];

// Validate request parameters
if (!isset($_GET['id']) || !isset($_GET['action'])) {
    header("Location: dashboard.php?msg=invalid_request&type=error");
    exit();
}

$appointment_id = (int) $_GET['id'];
$action = strtolower(trim($_GET['action']));

// Allowed actions
$allowedActions = ['approve', 'reject'];
if (!in_array($action, $allowedActions)) {
    header("Location: dashboard.php?msg=invalid_action&type=error");
    exit();
}

// Determine new status
$newStatus = ($action === 'approve') ? 'Approved' : 'Rejected';

/*
|--------------------------------------------------------------------------
| Fetch Appointment, Patient, and Doctor Details
|--------------------------------------------------------------------------
*/
$stmt = $conn->prepare("
    SELECT 
        a.id,
        a.doctor_id,
        a.status,
        a.appointment_date,
        a.appointment_time,
        p.name AS patient_name,
        p.phone AS patient_phone,
        d.name AS doctor_name
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    JOIN doctors d ON a.doctor_id = d.id
    WHERE a.id = ? AND a.doctor_id = ?
    LIMIT 1
");

$stmt->bind_param("ii", $appointment_id, $doctor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: dashboard.php?msg=appointment_not_found&type=error");
    exit();
}

$appointment = $result->fetch_assoc();

// Prevent duplicate approval/rejection
if ($appointment['status'] !== 'Pending') {
    header("Location: dashboard.php?msg=already_updated&type=warning");
    exit();
}

/*
|--------------------------------------------------------------------------
| Update Appointment Status
|--------------------------------------------------------------------------
*/
$updateStmt = $conn->prepare("
    UPDATE appointments 
    SET status = ? 
    WHERE id = ? AND doctor_id = ?
");

$updateStmt->bind_param("sii", $newStatus, $appointment_id, $doctor_id);

if (!$updateStmt->execute()) {
    header("Location: dashboard.php?msg=update_failed&type=error");
    exit();
}

/*
|--------------------------------------------------------------------------
| Send SMS Notification via Twilio
|--------------------------------------------------------------------------
*/
$patientName = $appointment['patient_name'];
$doctorName = $appointment['doctor_name'];
$appointmentDate = date("d M Y", strtotime($appointment['appointment_date']));
$appointmentTime = date("h:i A", strtotime($appointment['appointment_time']));
$patientPhone = trim($appointment['patient_phone']);

// Ensure phone number exists
if (!empty($patientPhone)) {

    // Format phone number for India (+91)
    if (strpos($patientPhone, '+') !== 0) {
        $patientPhone = '+91' . preg_replace('/\D/', '', $patientPhone);
    }

    // Twilio Credentials
    $sid = "AC5e232167e490edb2ae31ab7160b32978";
    $token = "536a7bcaa6906a1bd06b8f1cd6f7016a";
    $twilioNumber = "+14788127621";

    try {
        $client = new Client($sid, $token);

        if ($action === 'approve') {
            $messageBody = "Hello $patientName, your appointment with Dr. $doctorName on $appointmentDate at $appointmentTime has been APPROVED. Please log in to MediCare+ to complete the payment.";
        } else {
            $messageBody = "Hello $patientName, your appointment with Dr. $doctorName on $appointmentDate at $appointmentTime has been REJECTED. Please book another slot via MediCare+.";
        }

        $client->messages->create(
            $patientPhone,
            [
                'from' => $twilioNumber,
                'body' => $messageBody
            ]
        );

    } catch (Exception $e) {
        // Log error without interrupting approval process
        error_log("Twilio SMS Error: " . $e->getMessage());
    }
}

/*
|--------------------------------------------------------------------------
| Redirect Back to Dashboard
|--------------------------------------------------------------------------
*/
if ($action === 'approve') {
    header("Location: dashboard.php?msg=appointment_approved&type=success");
} else {
    header("Location: dashboard.php?msg=appointment_rejected&type=success");
}

exit();
?>