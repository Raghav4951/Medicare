<?php
include '../db.php';
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] != "patient") {
  header("Location: ../login.php");
  exit();
}

$user_id = $_SESSION['user_id'];

// fetch patient data
$query = $conn->prepare("SELECT * FROM patients WHERE id=?");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc();

// fetch doctors
$docQuery = $conn->prepare("SELECT * FROM doctors LIMIT 3");
$docQuery->execute();
$docResult = $docQuery->get_result();

// fetch appointments
$appQuery = $conn->prepare("
  SELECT a.*, d.name AS doctor_name 
  FROM appointments a
  JOIN doctors d ON a.doctor_id = d.id
  WHERE a.patient_id = ?
  ORDER BY a.appointment_date DESC, a.appointment_time DESC
  LIMIT 3
");
$appQuery->bind_param("i", $user_id);
$appQuery->execute();
$appResult = $appQuery->get_result();

// 🔥 NEW: fetch health history
$histQuery = $conn->prepare("SELECT * FROM health_history WHERE patient_id = ? ORDER BY event_date DESC LIMIT 2");
$histQuery->bind_param("i", $user_id);
$histQuery->execute();
$histResult = $histQuery->get_result();
?>

<!DOCTYPE html>
<html>

<head>
  <title>MediCare+ | Patient Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <style>
    :root {
      --primary: #0d9488;
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

    .menu-btn {
      position: absolute;
      top: 25px;
      left: 20px;
      font-size: 28px;
      color: white;
      cursor: pointer;
      z-index: 1000;
      transition: 0.3s;
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
      background: var(--card);
      padding: 18px;
      border-radius: 18px;
      margin-bottom: 16px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, .06);
    }

    .grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
      gap: 15px;
    }

    .action {
      cursor: pointer;
      transition: 0.3s;
    }

    .action:hover {
      transform: translateY(-5px);
    }

    .two-col {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
    }

    @media(max-width:768px) {
      .two-col {
        grid-template-columns: 1fr;
      }
    }

    .item {
      padding: 12px;
      border-radius: 12px;
      background: #f1f5f9;
      margin-bottom: 10px;
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

    /* Back/Close Button in Sidebar */
    .back-btn {
      color: white;
      font-size: 24px;
      padding: 10px 20px;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 10px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      margin-bottom: 10px;
    }

    .sidebar h2 {
      color: white;
      text-align: center;
      margin-bottom: 20px;
    }

    .sidebar ul {
      list-style-type: none;
      padding: 0;
      margin: 0;
    }

    .sidebar a {
      text-decoration: none;
      color: white;
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 15px 25px;
      font-size: 18px;
      transition: 0.3s;
    }

    .sidebar a:hover {
      background-color: #145c59;
      padding-left: 30px;
    }

    .main-content {
      transition: margin-left 0.3s;
      position: relative;
    }

    @media(min-width: 769px) {
      .main-content.shift {
        margin-left: 260px;
      }
    }

    /* Dim background when sidebar is open */
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
  </style>
</head>

<body>

  <div class="overlay" id="overlay" onclick="toggleMenu()"></div>

  <div class="main-content" id="mainContent">

    <div class="menu-btn" onclick="toggleMenu()">☰</div>

    <div class="sidebar" id="sidebar">
      <div class="back-btn" onclick="toggleMenu()">←</div>
      <h2>MediCare+</h2>
      <ul>
        <li><a href="dashboard.php">🏠 Dashboard</a></li>
        <li><a href="book-appointment.php">📅 Book Appointment</a></li>
        <li><a href="my-appointment.php">📋 My Appointments</a></li>
        <li><a href="prescription.php">📜 Health History</a></li>
        <li><a href="doctors.php">👨‍⚕️ Doctors</a></li>
        <li><a href="My-profile.php">🙍 My Profile</a></li>
        <li><a href="#" onclick="logout()">🚪 Logout</a></li>
      </ul>
    </div>

    <div class="header">
      <h2>Welcome, <?php echo htmlspecialchars($user['name']); ?> 👋</h2>
      <p><?php echo htmlspecialchars($user['email']); ?></p>
    </div>

    <div class="container">

      <div class="grid">
        <div class="card action" onclick="go('book-appointment.php')">
          <h3>📅 Book</h3>
          <p>New Appointment</p>
        </div>
        <div class="card action" onclick="go('prescription.php')">
          <h3>📜 History</h3>
          <p>View Health Records</p>
        </div>
        <div class="card action" onclick="go('prescription.php')">
          <h3>💊 Reports</h3>
          <p>View prescriptions</p>
        </div>
        <div class="card action" onclick="go('payment-success.php')">
          <h3>💳 Payments</h3>
          <p>Bills & history</p>
        </div>
      </div>

      <div class="card">
        <h3>📜 Your Recent Health History</h3>
        <?php if ($histResult->num_rows > 0): ?>
          <?php while ($history = $histResult->fetch_assoc()): ?>
            <div class="item" style="border-left: 4px solid var(--primary);">
              <strong><?php echo htmlspecialchars($history['event_name']); ?></strong>
              <small style="float: right;"><?php echo $history['event_date']; ?></small>
              <p style="margin: 5px 0 0; font-size: 13px; color: var(--muted);">
                <?php echo htmlspecialchars($history['notes']); ?>
              </p>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <p style="color: var(--muted); font-size: 14px;">No health history records found.</p>
        <?php endif; ?>
      </div>

      <div class="two-col">
        <div class="card">
          <h3>👨‍⚕️ Recommended Doctors</h3>
          <?php while ($doc = $docResult->fetch_assoc()): ?>
            <div class="item">
              <strong><?php echo htmlspecialchars($doc['name']); ?></strong><br>
              <small><?php echo htmlspecialchars($doc['specialization'] ?? 'General'); ?></small>
            </div>
          <?php endwhile; ?>
        </div>

        <div class="card">
          <h3>📅 Your Appointments</h3>
          <?php if ($appResult->num_rows > 0): ?>
            <?php while ($app = $appResult->fetch_assoc()): ?>
              <div class="item">
                <strong><?php echo htmlspecialchars($app['doctor_name']); ?></strong><br>
                <small><?php echo $app['appointment_date']; ?> • <?php echo $app['appointment_time']; ?></small><br>
                <span class="status-<?php echo $app['status']; ?>"><?php echo ucfirst($app['status']); ?></span>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <p>No appointments yet</p>
          <?php endif; ?>
        </div>
      </div>

      <div class="card">
        <h3>💡 Health Tips</h3>
        <ul class="tips">
          <li>💧 Drink 2-3 liters water daily</li>
          <li>🥗 Eat healthy & balanced diet</li>
        </ul>
      </div>

    </div>
  </div>

  <script>
    function toggleMenu() {
      const sidebar = document.getElementById("sidebar");
      const content = document.getElementById("mainContent");
      const overlay = document.getElementById("overlay");

      sidebar.classList.toggle("active");
      content.classList.toggle("shift");
      overlay.classList.toggle("active");
    }

    function go(page) { window.location.href = page; }
    function logout() { window.location.href = "../login.php"; }
  </script>

</body>

</html>