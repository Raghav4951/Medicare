<?php
include "../config.php";

if (isset($_POST['date']) && isset($_POST['doctor_id'])) {

    $date = $_POST['date'];
    $doctor_id = $_POST['doctor_id'];

    $stmt = $conn->prepare("SELECT appointment_time FROM appointments WHERE doctor_id=? AND appointment_date=?");
    $stmt->bind_param("is", $doctor_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();

    $booked = [];

    while ($row = $result->fetch_assoc()) {
        // 🔥 FIX: convert 10:00:00 → 10:00
        $time = date("H:i", strtotime($row['appointment_time']));
        $booked[] = $time;
    }

    echo json_encode($booked);
}
?>