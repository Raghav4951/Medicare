<?php
session_start();
include "../config.php";

if ($_SERVER['REQUEST_METHOD'] == "POST") {

    $appointment_id = $_POST['appointment_id'];

    if (isset($_FILES['prescription']) && $_FILES['prescription']['error'] == 0) {

        $file = $_FILES['prescription'];

        $filename = time() . "_" . basename($file['name']);
        $target = "../uploads/" . $filename;

        $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            die("Invalid file type");
        }

        if (move_uploaded_file($file['tmp_name'], $target)) {

            $stmt = $conn->prepare("UPDATE appointments SET new_prescription=? WHERE id=?");
            $stmt->bind_param("si", $filename, $appointment_id);
            $stmt->execute();

            echo "Upload Successful";
        } else {
            echo "Upload Failed";
        }

    } else {
        echo "No file selected";
    }
}
?>