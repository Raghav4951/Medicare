<?php
include '../db.php';
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] != "admin") {
  header("Location: ../login.php");
  exit();
}

$doctors = mysqli_query($conn, "SELECT * FROM doctors ORDER BY id DESC");
?>

<!DOCTYPE html>
<html>

<head>
  <title>Doctors | MediCare</title>

  <style>
    :root {
      --primary: #2563eb;
      --secondary: #1e40af;
      --glass: rgba(255, 255, 255, .75);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: Segoe UI;
    }

    body {
      display: flex;
      height: 100vh;
      background: linear-gradient(135deg, #eef2ff, #f8fafc);
    }

    /* SIDEBAR */

    .sidebar {
      width: 270px;
      background: linear-gradient(180deg, #1e3a8a, #2563eb);
      color: white;
      padding: 30px 20px;
    }

    .sidebar h2 {
      text-align: center;
      margin-bottom: 40px;
    }

    .sidebar a {
      display: block;
      padding: 14px;
      margin-bottom: 10px;
      color: #e0e7ff;
      text-decoration: none;
      border-radius: 10px;
    }

    .sidebar a:hover {
      background: rgba(255, 255, 255, .2);
    }

    /* MAIN */

    .main {
      flex: 1;
      padding: 40px;
      overflow: auto;
    }

    .header {
      font-size: 26px;
      margin-bottom: 25px;
      font-weight: 600;
    }

    /* DOCTOR GRID */

    .doctors {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
    }

    .doctor-card {
      background: var(--glass);
      backdrop-filter: blur(12px);
      padding: 20px;
      border-radius: 20px;
      box-shadow: 0 20px 40px rgba(0, 0, 0, .1);
    }

    .doctor-card h3 {
      margin-bottom: 8px;
    }

    .doctor-card p {
      color: #555;
      margin-bottom: 12px;
    }

    button {
      padding: 8px 14px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      background: #ef4444;
      color: white;
    }

    button:hover {
      background: #dc2626;
    }
  </style>
</head>

<body>

  <div class="sidebar">

    <h2>MediCare</h2>

    <a href="../admin/dashboard.php">📊 Dashboard</a>
    <a href="../doctors/doctors.php">👨‍⚕️ Doctors</a>
    <a href="../admin/appointments.php">📅 Appointments</a>
    <a href="../admin/patient.php">🧑‍🤝‍🧑 Patients</a>
    <a href="../login.php">🚪 Logout</a>

  </div>

  <div class="main">

    <div class="header">Doctors Management</div>

    <div class="doctors">

      <?php while ($row = mysqli_fetch_assoc($doctors)) { ?>

        <div class="doctor-card">

          <h3><?php echo $row['name']; ?></h3>

          <p><?php echo $row['specialization']; ?></p>
          </p>

          <p>
            Status:
            <strong style="color:
            <?php echo ($row['status'] == "Active") ? 'green' : 'red'; ?>">
              <?php echo $row['status']; ?>
            </strong>
          </p>

          <div style="display:flex;gap:10px;">

            <a href="../admin/toggle_doctor.php?id=<?php echo $row['id']; ?>">
              <button style="background:#2563eb;">
                <?php echo ($row['status'] == "Active") ? 'Deactivate' : 'Activate'; ?>
              </button>
            </a>

            <a href="../admin/delete_doctor.php?id=<?php echo $row['id']; ?>">
              <button style="background:#ef4444;">Delete</button>
            </a>

          </div>
          </a>

        </div>

      <?php } ?>

    </div>

  </div>

</body>

</html>