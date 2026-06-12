<?php
session_start();
include "../db.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != "patient") {
    header("Location: ../login.php");
    exit();
}

$patient_id = $_SESSION['user_id'];

// Fetch patient info for the header
$userQuery = $conn->prepare("SELECT name, email FROM patients WHERE id=?");
$userQuery->bind_param("i", $patient_id);
$userQuery->execute();
$user = $userQuery->get_result()->fetch_assoc();

// Fetch appointments with doctor details
// Make sure your appointments table has 'status' and 'payment_status' columns
$query = "
SELECT appointments.*, doctors.name AS doctor_name, doctors.specialization
FROM appointments
JOIN doctors ON appointments.doctor_id = doctors.id
WHERE appointments.patient_id = '$patient_id'
ORDER BY appointment_date DESC
";

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>My Appointments | MediCare+</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        :root {
            --primary: #0d9488;
            --primary-dark: #145c59;
            --bg: #f8fafc;
            --card: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg);
            overflow-x: hidden;
        }

        /* SIDEBAR STYLES */
        .sidebar {
            position: fixed;
            width: 260px;
            height: 100vh;
            left: -260px;
            top: 0;
            background: #1e8c84;
            transition: 0.3s ease;
            z-index: 1001;
            padding-top: 10px;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.2);
        }

        .sidebar.active {
            left: 0;
        }

        .back-btn {
            color: white;
            font-size: 24px;
            padding: 10px 20px;
            cursor: pointer;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 10px;
        }

        .header {
            background: linear-gradient(135deg, #0d9488, #14b8a6);
            color: #fff;
            padding: 30px 20px 70px 65px;
            border-radius: 0 0 30px 30px;
        }

        .container {
            padding: 20px;
            margin-top: -50px;
        }

        .card {
            background: #fff;
            padding: 18px;
            border-radius: 18px;
            margin-bottom: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, .06);
        }

        .sidebar h2 {
            color: white;
            text-align: center;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
        }

        .sidebar a {
            text-decoration: none;
            color: white;
            display: block;
            padding: 15px 25px;
            transition: 0.3s;
        }

        .sidebar a:hover {
            background-color: #145c59;
        }

        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            z-index: 1000;
        }

        .overlay.active {
            display: block;
        }

        .menu-btn {
            font-size: 28px;
            color: var(--primary);
            cursor: pointer;
            margin-left: 20px;
            margin-top: 10px;
            display: inline-block;
        }

        /* TABLE STYLES */
        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        th {
            background: #f1f5f9;
            color: var(--muted);
            font-size: 12px;
        }

        /* STATUS COLORS */
        .status-pending {
            color: #f59e0b;
            font-weight: bold;
        }

        .status-approved {
            color: #16a34a;
            font-weight: bold;
        }

        .status-rejected {
            color: #dc2626;
            font-weight: bold;
        }

        /* YOUR SPECIFIC PAYMENT CLASS */
        .status-pay {
            color: #16a34a;
            font-weight: bold;
        }

        .status-unpaid {
            color: #dc2626;
            font-weight: bold;
        }

        .btn-pay {
            background: #16a34a;
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
        }

        .btn-cancel {
            color: #dc2626;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
        }
    </style>
</head>

<body>
    <div class="overlay" id="overlay" onclick="toggleMenu()"></div>
    <div class="menu-btn" onclick="toggleMenu()">☰</div>

    <nav class="sidebar" id="sidebar">
        <div class="back-btn" onclick="toggleMenu()">←</div>
        <h2>MediCare+</h2>
        <ul>
            <li><a href="dashboard.php">🏠 Dashboard</a></li>
            <li><a href="book-appointment.php">📅 Book Appointment</a></li>
            <li><a href="my-appointment.php">📋 My Appointments</a></li>
            <li><a href="../login.php" onclick="logout()">🚪 Logout</a></li>
        </ul>
    </nav>

    <div class="header">
        <h2>My Appointments 📋</h2>
        <p><?php echo htmlspecialchars($user['email']); ?></p>
    </div>

    <div class="container">
        <div class="card">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Doctor</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Action</th>
                            <th>Payment</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                            <tr>
                                <td><strong> <?php echo htmlspecialchars($row['doctor_name']); ?></strong></td>
                                <td><?php echo $row['appointment_date']; ?></td>
                                <td><?php echo $row['appointment_time']; ?></td>
                                <td>
                                    <span class="status-<?php echo strtolower($row['status']); ?>">
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $today = date("Y-m-d");
                                    // Case 1: Pending & Future - Allow Cancel
                                    if ($row['status'] == "Pending" && $row['appointment_date'] >= $today) { ?>
                                        <a href="cancel.php?id=<?php echo $row['id']; ?>" class="btn-cancel"
                                            onclick="return confirm('Cancel this appointment?')">Cancel</a>
                                    <?php }
                                    // Case 2: Approved & Not Paid - Show Pay Now
                                    elseif ($row['status'] == "Approved" && strtolower(trim($row['payment_status'] ?? '')) == "unpaid") { ?>
                                        <a href="../pay.php?id=<?php echo $row['id']; ?>" class="btn-pay">💳 Pay Now</a>
                                    <?php } else {
                                        echo "-";
                                    } ?>
                                </td>
                                <td>
                                    <?php
                                    $paymentStatus = strtolower(trim($row['payment_status'] ?? ''));

                                    if ($paymentStatus == "paid") {
                                        echo "<span class='status-pay'>✅ Paid</span>";
                                    } elseif ($paymentStatus == "pending_verification") {
                                        echo "<span class='status-pending'>Payment Verification is Pending</span>";
                                    } elseif ($paymentStatus == "pay_at_clinic") {
                                        echo "<span class='status-pending'>Pay at Clinic</span>";
                                    } else {
                                        echo "<span class='status-unpaid'>Unpaid</span>";
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function toggleMenu() {
            document.getElementById("sidebar").classList.toggle("active");
            document.getElementById("overlay").classList.toggle("active");
        }
        function logout() { return confirm("Are you sure you want to log out?"); }
    </script>
</body>

</html>