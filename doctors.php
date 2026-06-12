<?php
session_start();
include "../config.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != "patient") {
    header("Location: ../login.php");
    exit();
}

// Fetch doctors
$result = $conn->query("SELECT * FROM doctors WHERE status = 'Active' ORDER BY id DESC"); ?>

<!DOCTYPE html>
<html>

<head>
    <title>Doctors | MediCare+</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        :root {
            --primary: #0d9488;
            --bg: #f8fafc;
        }

        body {
            margin: 0;
            font-family: 'Segoe UI';
            background: var(--bg);
        }

        /* SIDEBAR */
        .sidebar {
            position: fixed;
            left: -260px;
            width: 260px;
            height: 100%;
            background: #1e8c84;
            transition: 0.3s;
            z-index: 1000;
        }

        .sidebar.active {
            left: 0;
        }

        .sidebar h2 {
            color: #fff;
            text-align: center;
            margin-top: 15px;
        }

        .sidebar a {
            display: block;
            color: #fff;
            padding: 14px 20px;
            text-decoration: none;
        }

        .sidebar a:hover {
            background: #145c59;
        }

        /* BACK BUTTON */
        .back-btn {
            color: white;
            font-size: 22px;
            padding: 10px 20px;
            cursor: pointer;
        }

        /* MENU BUTTON */
        .menu-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            font-size: 26px;
            color: #fff;
            cursor: pointer;
        }

        /* HEADER */
        .header {
            background: linear-gradient(135deg, #0d9488, #14b8a6);
            color: #fff;
            padding: 30px 20px 60px 60px;
            border-radius: 0 0 30px 30px;
        }

        /* CONTAINER */
        .container {
            padding: 20px;
            margin-top: -40px;
        }

        /* CARD */
        .doctor {
            background: #fff;
            padding: 18px;
            border-radius: 16px;
            margin-bottom: 15px;
            box-shadow: 0 8px 18px rgba(0, 0, 0, .06);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* BUTTON */
        button {
            background: var(--primary);
            color: #fff;
            border: none;
            padding: 10px 16px;
            border-radius: 10px;
            cursor: pointer;
        }

        button:hover {
            background: #0f766e;
        }

        /* OVERLAY */
        .overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.4);
        }

        .overlay.active {
            display: block;
        }
    </style>
</head>

<body>

    <div class="overlay" id="overlay" onclick="toggleMenu()"></div>

    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">
        <div class="back-btn" onclick="toggleMenu()">←</div>
        <h2>MediCare+</h2>

        <a href="dashboard.php">🏠 Dashboard</a>
        <a href="book-appointment.php">📅 Book Appointment</a>
        <a href="my-appointment.php">📋 My Appointments</a>
        <a href="prescription.php">📜 Prescriptions</a>
        <a href="../logout.php">🚪 Logout</a>
    </div>

    <!-- HEADER -->
    <div class="header">
        <div class="menu-btn" onclick="toggleMenu()">☰</div>
        <h2>👨‍⚕️ Available Doctors</h2>
        <p>Select a doctor to book appointment</p>
    </div>

    <!-- CONTENT -->
    <div class="container">

        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>

                <div class="doctor">
                    <div>
                        <h3>Dr.
                            <?php echo htmlspecialchars($row['name']); ?>
                        </h3>
                        <p>
                            <?php echo htmlspecialchars($row['specialization']); ?>
                            .
                            (Currently <?php echo htmlspecialchars($row['status']); ?>d)
                        </p>
                    </div>

                    <form action="book-appointment.php" method="GET">
                        <input type="hidden" name="doctor_id" value="<?php echo $row['id']; ?>">
                        <button type="submit">Book</button>
                    </form>
                </div>

            <?php endwhile; ?>
        <?php else: ?>
            <p>No doctors available</p>
        <?php endif; ?>

    </div>

    <script>
        function toggleMenu() {
            document.getElementById("sidebar").classList.toggle("active");
            document.getElementById("overlay").classList.toggle("active");
        }
    </script>

</body>

</html>