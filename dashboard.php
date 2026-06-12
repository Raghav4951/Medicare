<?php
include '../db.php';
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] != "doctor") {
  header("Location: ../login.php");
  exit();
}

$doctor_id = (int) $_SESSION['user_id'];
$today = date("d-m-y");

$doctor = null;
$doctorQuery = mysqli_query($conn, "SELECT * FROM doctors WHERE id = $doctor_id LIMIT 1");
if ($doctorQuery && mysqli_num_rows($doctorQuery) > 0) {
  $doctor = mysqli_fetch_assoc($doctorQuery);
}

$selected_date = isset($_GET['filter_date']) && $_GET['filter_date'] !== '' ? $_GET['filter_date'] : $today;
$statusMessage = '';
$statusType = '';

if (isset($_GET['msg'])) {
  $msg = $_GET['msg'];
  $type = isset($_GET['type']) ? $_GET['type'] : 'success';

  $messages = [
    'appointment_approved' => 'Appointment approved successfully.',
    'appointment_rejected' => 'Appointment rejected successfully.',
    'already_reviewed' => 'This appointment has already been reviewed.',
    'appointment_not_found' => 'Appointment not found or access denied.',
    'invalid_action' => 'Invalid action requested.',
    'invalid_request' => 'Invalid request.',
    'update_failed' => 'Failed to update appointment status.'
  ];

  if (isset($messages[$msg])) {
    $statusMessage = $messages[$msg];
    $statusType = $type;
  }
}

/* =========================
   DASHBOARD COUNTS
========================= */
$totalAppointments = 0;
$todayAppointments = 0;
$pendingAppointments = 0;
$approvedAppointments = 0;

$q1 = mysqli_query($conn, "SELECT COUNT(*) AS total FROM appointments WHERE doctor_id = $doctor_id");
if ($q1) {
  $totalAppointments = mysqli_fetch_assoc($q1)['total'];
}

$q2 = mysqli_query($conn, "SELECT COUNT(*) AS total FROM appointments WHERE doctor_id = $doctor_id AND appointment_date = '$today'");
if ($q2) {
  $todayAppointments = mysqli_fetch_assoc($q2)['total'];
}

$q3 = mysqli_query($conn, "SELECT COUNT(*) AS total FROM appointments WHERE doctor_id = $doctor_id AND status = 'Pending'");
if ($q3) {
  $pendingAppointments = mysqli_fetch_assoc($q3)['total'];
}

$q4 = mysqli_query($conn, "SELECT COUNT(*) AS total FROM appointments WHERE doctor_id = $doctor_id AND status = 'Approved'");
if ($q4) {
  $approvedAppointments = mysqli_fetch_assoc($q4)['total'];
}

/* =========================
   CURRENT / NEXT PATIENT
========================= */
$current_patient = null;
$current_query = "
    SELECT appointments.*, patients.name AS patient_name
    FROM appointments
    JOIN patients ON appointments.patient_id = patients.id
    WHERE appointments.doctor_id = '$doctor_id'
      AND appointments.appointment_date = '$today'
      AND appointments.status = 'Approved'
    ORDER BY appointment_time ASC
    LIMIT 1
";
$current_result = mysqli_query($conn, $current_query);
if ($current_result && mysqli_num_rows($current_result) > 0) {
  $current_patient = mysqli_fetch_assoc($current_result);
}

/* =========================
   APPOINTMENTS FOR FILTERED DATE
========================= */
$query = "
    SELECT appointments.*, patients.name AS patient_name
    FROM appointments
    JOIN patients ON appointments.patient_id = patients.id
    WHERE appointments.doctor_id = '$doctor_id'
      AND appointments.appointment_date = '$selected_date'
    ORDER BY appointment_time ASC
";
$result = mysqli_query($conn, $query);

/* =========================
   PENDING APPOINTMENTS ONLY
========================= */
$pendingQuery = "
    SELECT appointments.*, patients.name AS patient_name
    FROM appointments
    JOIN patients ON appointments.patient_id = patients.id
    WHERE appointments.doctor_id = '$doctor_id'
      AND appointments.status = 'Pending'
    ORDER BY appointments.appointment_date ASC, appointments.appointment_time ASC
";
$pendingResult = mysqli_query($conn, $pendingQuery);

if (!function_exists('getInitials')) {
  function getInitials($name)
  {
    $name = trim((string) $name);

    if ($name === '') {
      return 'DR';
    }

    $parts = preg_split('/\s+/', $name);
    $initials = '';

    foreach ($parts as $part) {
      if ($part !== '') {
        $initials .= strtoupper(substr($part, 0, 1));
      }

      if (strlen($initials) >= 2) {
        break;
      }
    }

    return $initials !== '' ? $initials : 'DR';
  }
}

$doctorName = $doctor && !empty($doctor['name']) ? $doctor['name'] : 'Doctor';
$doctorSpecialization = $doctor && !empty($doctor['specialization']) ? $doctor['specialization'] : 'Specialist';
$doctorPhone = $doctor && !empty($doctor['phone']) ? $doctor['phone'] : '9876543210';
$doctorHospital = $doctor && !empty($doctor['hospital_name']) ? $doctor['hospital_name'] : 'MediCare Clinic';
$doctorAddress = $doctor && !empty($doctor['address']) ? $doctor['address'] : 'Main Road, Your City, India';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Doctor Dashboard | MediCare</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    :root {
      --primary: #0d9488;
      --primary-dark: #0f766e;
      --bg: #f1f5f9;
      --sidebar: #0f172a;
      --card: #ffffff;
      --text: #0f172a;
      --muted: #64748b;
      --border: #e2e8f0;
      --success-bg: #dcfce7;
      --success-text: #166534;
      --warning-bg: #fef3c7;
      --warning-text: #92400e;
      --danger-bg: #fee2e2;
      --danger-text: #991b1b;
      --soft: #ccfbf1;
      --shadow: 0 14px 35px rgba(15, 23, 42, 0.08);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: "Segoe UI", sans-serif;
    }

    body {
      background: var(--bg);
      color: var(--text);
    }

    .dashboard {
      display: flex;
      min-height: 100vh;
    }

    .sidebar {
      width: 260px;
      background: var(--sidebar);
      color: #fff;
      padding: 28px 18px;
      position: sticky;
      top: 0;
      height: 100vh;
    }

    .brand {
      text-align: center;
      margin-bottom: 28px;
    }

    .brand h2 {
      font-size: 28px;
      color: #5eead4;
      margin-bottom: 6px;
    }

    .brand p {
      font-size: 13px;
      color: #cbd5e1;
    }

    .doctor-mini {
      background: rgba(255, 255, 255, 0.06);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 18px;
      padding: 18px;
      text-align: center;
      margin-bottom: 25px;
    }

    .doctor-avatar {
      width: 64px;
      height: 64px;
      border-radius: 50%;
      margin: 0 auto 12px;
      background: linear-gradient(135deg, #14b8a6, #2dd4bf);
      display: flex;
      justify-content: center;
      align-items: center;
      color: #fff;
      font-size: 24px;
      font-weight: 700;
    }

    .doctor-mini h3 {
      font-size: 17px;
      margin-bottom: 4px;
    }

    .doctor-mini p {
      color: #cbd5e1;
      font-size: 13px;
    }

    .menu a {
      display: block;
      color: #e2e8f0;
      text-decoration: none;
      padding: 13px 14px;
      margin-bottom: 10px;
      border-radius: 12px;
      transition: 0.25s;
      font-size: 15px;
      font-weight: 500;
    }

    .menu a:hover,
    .menu a.active {
      background: rgba(20, 184, 166, 0.16);
      color: #ffffff;
    }

    .content {
      flex: 1;
      padding: 28px;
    }

    .topbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 20px;
      flex-wrap: wrap;
      margin-bottom: 24px;
    }

    .welcome h1 {
      font-size: 30px;
      margin-bottom: 6px;
    }

    .welcome p {
      color: var(--muted);
      font-size: 15px;
    }

    .top-actions {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
    }

    .btn {
      display: inline-block;
      text-decoration: none;
      border: none;
      border-radius: 10px;
      padding: 11px 16px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: 0.25s;
    }

    .btn-primary {
      background: var(--primary);
      color: #fff;
    }

    .btn-primary:hover {
      background: var(--primary-dark);
    }

    .btn-light {
      background: #fff;
      color: var(--text);
      border: 1px solid var(--border);
    }

    .btn-light:hover {
      background: #f8fafc;
    }

    .hero-card {
      background: linear-gradient(135deg, #0d9488, #14b8a6);
      border-radius: 22px;
      padding: 28px;
      color: #fff;
      box-shadow: var(--shadow);
      margin-bottom: 24px;
      display: flex;
      justify-content: space-between;
      gap: 20px;
      align-items: center;
      flex-wrap: wrap;
    }

    .hero-card h2 {
      font-size: 28px;
      margin-bottom: 10px;
    }

    .hero-card p {
      max-width: 700px;
      opacity: 0.95;
      line-height: 1.6;
    }

    .next-box {
      background: rgba(255, 255, 255, 0.14);
      border: 1px solid rgba(255, 255, 255, 0.18);
      border-radius: 18px;
      padding: 18px;
      min-width: 270px;
    }

    .next-box h4 {
      margin-bottom: 8px;
      font-size: 15px;
    }

    .next-box .time {
      font-size: 22px;
      font-weight: 700;
      margin-bottom: 5px;
    }

    .stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 18px;
      margin-bottom: 24px;
    }

    .stat-card {
      background: var(--card);
      border-radius: 18px;
      padding: 22px;
      box-shadow: var(--shadow);
      border: 1px solid var(--border);
    }

    .stat-label {
      color: var(--muted);
      font-size: 14px;
      margin-bottom: 10px;
    }

    .stat-value {
      font-size: 32px;
      font-weight: 700;
      color: var(--text);
    }

    .grid {
      display: grid;
      grid-template-columns: 1.5fr 1.1fr;
      gap: 22px;
    }

    .panel {
      background: var(--card);
      border-radius: 18px;
      box-shadow: var(--shadow);
      border: 1px solid var(--border);
      overflow: hidden;
      min-height: 520px;
    }

    .panel-header {
      padding: 20px 22px;
      border-bottom: 1px solid var(--border);
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 12px;
      flex-wrap: wrap;
    }

    .panel-header h3 {
      font-size: 20px;
    }

    .panel-header p {
      color: var(--muted);
      font-size: 14px;
      margin-top: 4px;
    }

    .panel-body {
      padding: 20px 22px;
    }

    .filter-form {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      align-items: center;
    }

    .filter-form input[type="date"] {
      padding: 11px 14px;
      border-radius: 10px;
      border: 1px solid var(--border);
      background: #fff;
    }

    .table-wrap {
      overflow-x: auto;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      min-width: 760px;
    }

    th {
      background: #f8fafc;
      color: var(--muted);
      text-align: left;
      font-size: 13px;
      font-weight: 600;
      padding: 14px;
    }

    td {
      padding: 15px 14px;
      border-top: 1px solid var(--border);
      font-size: 14px;
      vertical-align: middle;
    }

    tr:hover td {
      background: #fcfcfd;
    }

    .badge {
      display: inline-block;
      padding: 6px 12px;
      border-radius: 999px;
      font-size: 12px;
      font-weight: 700;
    }

    .pending {
      background: var(--warning-bg);
      color: var(--warning-text);
    }

    .approved {
      background: var(--success-bg);
      color: var(--success-text);
    }

    .rejected,
    .completed {
      background: var(--danger-bg);
      color: var(--danger-text);
    }

    .paid {
      background: var(--success-bg);
      color: var(--success-text);
    }

    .unpaid {
      background: var(--danger-bg);
      color: var(--danger-text);
    }

    .small-actions {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }

    .btn-sm {
      padding: 8px 12px;
      font-size: 12px;
      border-radius: 8px;
      border: none;
      cursor: pointer;
      text-decoration: none;
      display: inline-block;
      font-weight: 600;
    }

    .approve-btn {
      background: #16a34a;
      color: #fff;
    }

    .reject-btn {
      background: #dc2626;
      color: #fff;
    }

    .empty {
      padding: 30px 10px;
      text-align: center;
      color: var(--muted);
    }

    .quick-list {
      display: grid;
      gap: 14px;
    }

    .quick-item {
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 16px;
      background: #fcfefe;
    }

    .quick-item h4 {
      font-size: 15px;
      margin-bottom: 6px;
    }

    .quick-item p {
      color: var(--muted);
      font-size: 13px;
      line-height: 1.5;
    }

    .quick-item strong {
      color: var(--text);
    }

    .footer-note {
      margin-top: 18px;
      font-size: 13px;
      color: var(--muted);
      text-align: center;
    }

    .full-width-panel {
      margin-top: 22px;
    }

    .pending-mini-table {
      width: 100%;
      border-collapse: collapse;
      min-width: unset;
    }

    .pending-mini-table th,
    .pending-mini-table td {
      padding: 12px 10px;
      border-top: 1px solid var(--border);
      font-size: 13px;
      text-align: left;
      vertical-align: middle;
    }

    .pending-mini-table th {
      background: #f8fafc;
      color: var(--muted);
      font-size: 12px;
    }

    .alert {
      padding: 14px 16px;
      border-radius: 12px;
      margin-bottom: 18px;
      font-size: 14px;
      font-weight: 600;
    }

    .alert-success {
      background: var(--success-bg);
      color: var(--success-text);
    }

    .alert-warning {
      background: var(--warning-bg);
      color: var(--warning-text);
    }

    .alert-error {
      background: var(--danger-bg);
      color: var(--danger-text);
    }

    /* Prescription Modal */
    .prescription-modal {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(15, 23, 42, 0.70);
      z-index: 9999;
      padding: 18px;
      overflow-y: auto;
    }

    .prescription-modal-content {
      width: 100%;
      max-width: 1180px;
      margin: 0 auto;
      background: white;
      border-radius: 24px;
      overflow: hidden;
      box-shadow: 0 28px 60px rgba(0, 0, 0, 0.22);
    }

    .prescription-modal-top {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 18px 22px;
      background: linear-gradient(135deg, #0f766e, #14b8a6);
      color: white;
    }

    .prescription-modal-top h3 {
      font-size: 24px;
    }

    .prescription-close {
      font-size: 30px;
      font-weight: 700;
      cursor: pointer;
      line-height: 1;
    }

    .prescription-modal-body {
      padding: 18px;
      background: #f8fafc;
    }

    .preview-card {
      background: white;
      border: 1px solid var(--border);
      border-radius: 20px;
      box-shadow: var(--shadow);
      overflow: hidden;
    }

    .preview-head {
      padding: 16px 18px;
      border-bottom: 1px solid var(--border);
      background: linear-gradient(180deg, #ffffff 0%, #f8fffe 100%);
    }

    .preview-head h3 {
      font-size: 20px;
      margin-bottom: 4px;
    }

    .preview-head p {
      color: var(--muted);
      font-size: 13px;
    }

    .rx-sheet {
      margin: 16px;
      border: 1px solid #cbd5e1;
      border-radius: 18px;
      overflow: hidden;
      background: white;
    }

    .rx-header-band {
      background: linear-gradient(135deg, #0f766e, #14b8a6);
      color: white;
      padding: 16px 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 14px;
      flex-wrap: wrap;
    }

    .rx-clinic-title {
      font-size: 28px;
      font-weight: 800;
      line-height: 1;
    }

    .rx-clinic-subtitle {
      font-size: 13px;
      opacity: 0.96;
      margin-top: 6px;
    }

    .rx-body {
      display: grid;
      grid-template-columns: 220px 1fr;
      min-height: 780px;
    }

    .rx-left {
      border-right: 2px solid #0f172a;
      padding: 18px 16px;
      background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    }

    .rx-left-block {
      margin-bottom: 18px;
      padding-bottom: 14px;
      border-bottom: 1px solid #e2e8f0;
    }

    .rx-left-block:last-child {
      border-bottom: none;
    }

    .rx-doctor-name {
      font-size: 22px;
      font-weight: 800;
      margin-bottom: 6px;
    }

    .rx-doctor-meta {
      font-size: 13px;
      color: #475569;
      line-height: 1.6;
    }

    .rx-left-heading {
      font-size: 15px;
      font-weight: 800;
      margin-bottom: 6px;
    }

    .rx-left-text {
      font-size: 13px;
      color: #334155;
      line-height: 1.7;
      white-space: pre-line;
    }

    .rx-right {
      padding: 20px 24px;
    }

    .rx-patient-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(220px, 1fr));
      gap: 12px 18px;
      margin-bottom: 14px;
    }

    .rx-patient-line {
      display: flex;
      gap: 8px;
      align-items: center;
      font-size: 14px;
    }

    .rx-patient-line strong {
      min-width: fit-content;
    }

    .rx-patient-line input,
    .rx-patient-line select {
      flex: 1;
      border: none;
      border-bottom: 1px dashed #64748b;
      padding: 4px 2px;
      min-height: 28px;
      font-size: 14px;
      background: transparent;
      outline: none;
    }

    .rx-symbol {
      font-size: 34px;
      font-weight: 800;
      margin: 8px 0 14px;
    }

    .entry-inline {
      border: 1px solid var(--border);
      border-radius: 12px;
      background: #fbfdff;
      padding: 8px 10px;
      margin-bottom: 8px;
    }

    .entry-row {
      display: grid;
      grid-template-columns: 2.6fr 90px 220px auto;
      gap: 8px;
      align-items: center;
    }

    .entry-row input,
    .entry-row select {
      width: 100%;
      border: 1px solid #cbd5e1;
      border-radius: 10px;
      padding: 10px 10px;
      font-size: 14px;
      outline: none;
      background: white;
    }

    .timing-group {
      display: flex;
      gap: 6px;
      flex-wrap: wrap;
      justify-content: flex-start;
    }

    .timing-chip {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 58px;
      height: 34px;
      border: 1px solid var(--border);
      border-radius: 10px;
      background: white;
      font-size: 12px;
      font-weight: 800;
      cursor: pointer;
      user-select: none;
      padding: 0 10px;
    }

    .timing-chip input {
      display: none;
    }

    .timing-chip.active {
      background: var(--soft);
      border-color: #5eead4;
      color: var(--primary-dark);
    }

    .entry-note {
      margin-top: 8px;
      font-size: 12px;
      color: var(--muted);
    }

    .rx-block {
      margin-top: 14px;
    }

    .rx-block h4 {
      font-size: 17px;
      margin-bottom: 8px;
    }

    .rx-box {
      border: 1px dashed #cbd5e1;
      border-radius: 12px;
      background: #fcfcfd;
      min-height: 44px;
      margin-top: 8px;
      padding: 8px 10px;
      line-height: 1.45;
      white-space: normal;
      color: #334155;
    }

    .medicine-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 6px;
      border-bottom: 1px dashed #dbe4ee;
      padding: 4px 0;
    }

    .medicine-item:last-child {
      border-bottom: none;
    }

    .medicine-text {
      flex: 1;
      min-width: 0;
      font-size: 13px;
      line-height: 1.4;
      word-break: break-word;
    }

    .medicine-remove {
      border: none;
      background: transparent;
      color: var(--danger);
      font-size: 11px;
      font-weight: 800;
      cursor: pointer;
      padding: 2px 4px;
      line-height: 1;
      white-space: nowrap;
      flex-shrink: 0;
    }

    .test-bar {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      margin-bottom: 10px;
    }

    .quick-chip {
      border: 1px solid var(--border);
      background: white;
      color: #334155;
      border-radius: 999px;
      padding: 8px 12px;
      font-size: 12px;
      font-weight: 700;
      cursor: pointer;
    }

    .quick-chip:hover {
      border-color: #14b8a6;
      color: var(--primary);
    }

    .selected-tests {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      min-height: 38px;
    }

    .selected-test {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: #e2fdf8;
      color: #115e59;
      border: 1px solid #99f6e4;
      padding: 8px 10px;
      border-radius: 999px;
      font-size: 12px;
      font-weight: 800;
    }

    .selected-test button {
      border: none;
      background: transparent;
      color: #0f766e;
      font-weight: 900;
      cursor: pointer;
      font-size: 14px;
      line-height: 1;
    }

    .advice-area {
      width: 100%;
      border: 1px solid #cbd5e1;
      border-radius: 10px;
      padding: 10px 12px;
      font-size: 14px;
      outline: none;
      resize: vertical;
      min-height: 90px;
      margin-top: 10px;
    }

    .prescription-actions {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      flex-wrap: wrap;
      padding: 0 16px 16px;
    }

    @media (max-width: 900px) {
      .rx-body {
        grid-template-columns: 1fr;
      }

      .rx-left {
        border-right: none;
        border-bottom: 2px solid #0f172a;
      }

      .rx-patient-grid,
      .entry-row {
        grid-template-columns: 1fr;
      }

      .rx-header-band {
        height: auto;
        flex-direction: column;
        align-items: flex-start;
      }
    }
  </style>
</head>

<body>
  <div class="dashboard">
    <aside class="sidebar">
      <div class="brand">
        <h2>MediCare</h2>
        <p>Doctor Workspace</p>
      </div>

      <div class="doctor-mini">
        <div class="doctor-avatar"><?php echo htmlspecialchars(getInitials($doctorName)); ?></div>
        <h3><?php echo htmlspecialchars($doctorName); ?></h3>
        <p><?php echo htmlspecialchars($doctorSpecialization); ?></p>
      </div>

      <nav class="menu">
        <a href="dashboard.php" class="active">🏠 Dashboard</a>
        <a href="send_reminders.php">📩 Send Reminders</a>
        <a href="../logout.php">🚪 Logout</a>
      </nav>
    </aside>

    <main class="content">
      <div class="topbar">
        <div class="welcome">
          <h1>Welcome back, <?php echo htmlspecialchars($doctorName); ?></h1>
          <p>Manage appointments, track your day, and respond to patient requests from one place.</p>
        </div>

        <div class="top-actions">
          <a href="send_reminders.php" class="btn btn-light">Send Reminder</a>
          <a href="../logout.php" class="btn btn-primary">Logout</a>
        </div>
      </div>

      <?php if ($statusMessage !== '') { ?>
        <div
          class="alert <?php echo $statusType === 'success' ? 'alert-success' : ($statusType === 'warning' ? 'alert-warning' : 'alert-error'); ?>">
          <?php echo htmlspecialchars($statusMessage); ?>
        </div>
      <?php } ?>

      <section class="hero-card">
        <div>
          <h2>Your schedule for <?php echo date("F d, Y", strtotime($selected_date)); ?></h2>
          <p>
            Review your appointments, approve pending requests, and stay prepared for consultations.
            Everything important for your day is shown below.
          </p>
        </div>

        <div class="next-box">
          <h4>Next Approved Patient</h4>
          <?php if ($current_patient) { ?>
            <div class="time"><?php echo date("h:i A", strtotime($current_patient['appointment_time'])); ?></div>
            <div><?php echo htmlspecialchars($current_patient['patient_name']); ?></div>
            <small><?php echo htmlspecialchars($current_patient['appointment_date']); ?></small>
          <?php } else { ?>
            <div class="time">No patient</div>
            <div>No approved appointments for today</div>
          <?php } ?>
        </div>
      </section>

      <section class="stats">
        <div class="stat-card">
          <div class="stat-label">Total Appointments</div>
          <div class="stat-value"><?php echo $totalAppointments; ?></div>
        </div>

        <div class="stat-card">
          <div class="stat-label">Today's Appointments</div>
          <div class="stat-value"><?php echo $todayAppointments; ?></div>
        </div>

        <div class="stat-card">
          <div class="stat-label">Pending Requests</div>
          <div class="stat-value"><?php echo $pendingAppointments; ?></div>
        </div>

        <div class="stat-card">
          <div class="stat-label">Approved Appointments</div>
          <div class="stat-value"><?php echo $approvedAppointments; ?></div>
        </div>
      </section>

      <section class="grid">
        <div class="panel">
          <div class="panel-header">
            <div>
              <h3>Appointments</h3>
              <p>View and manage appointments for a selected date.</p>
            </div>

            <form method="GET" class="filter-form">
              <input type="date" name="filter_date" value="<?php echo htmlspecialchars($selected_date); ?>">
              <button type="submit" class="btn btn-primary">Filter</button>
            </form>
          </div>

          <div class="panel-body">
            <div class="table-wrap">
              <table>
                <tr>
                  <th>ID</th>
                  <th>Patient</th>
                  <th>Date</th>
                  <th>Time</th>
                  <th>Status</th>
                  <th>Payment</th>
                  <th>Action</th>
                </tr>

                <?php if ($result && mysqli_num_rows($result) > 0) { ?>
                  <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                    <?php
                    $currentDateTime = strtotime(date("Y-m-d H:i:s"));
                    $appointmentDateTime = strtotime($row['appointment_date'] . ' ' . $row['appointment_time']);
                    $treatStartTime = $appointmentDateTime - (7 * 24 * 60 * 60);
                    $treatEndTime = $appointmentDateTime + (7 * 24 * 60 * 60);
                    $canTreat = ($currentDateTime >= $treatStartTime && $currentDateTime <= $treatEndTime);
                    ?>
                    <tr>
                      <td><?php echo $row['id']; ?></td>
                      <td><?php echo htmlspecialchars($row['patient_name']); ?></td>
                      <td><?php echo htmlspecialchars($row['appointment_date']); ?></td>
                      <td><?php echo date("h:i A", strtotime($row['appointment_time'])); ?></td>

                      <td>
                        <span class="badge <?php echo strtolower($row['status']); ?>">
                          <?php echo htmlspecialchars($row['status']); ?>
                        </span>
                      </td>

                      <td>
                        <span class="badge <?php echo strtolower($row['payment_status']); ?>">
                          <?php echo htmlspecialchars($row['payment_status']); ?>
                        </span>
                      </td>

                      <td>
                        <?php if ($row['status'] === "Pending") { ?>
                          <div class="small-actions">
                            <a class="btn-sm approve-btn"
                              href="update-status.php?id=<?php echo $row['id']; ?>&action=approve">Approve</a>
                            <a class="btn-sm reject-btn"
                              href="update-status.php?id=<?php echo $row['id']; ?>&action=reject">Reject</a>
                          </div>
                        <?php } elseif ($row['status'] === "Approved") { ?>
                          <div class="small-actions">
                            <?php if ($canTreat) { ?>
                              <button type="button" class="btn-sm approve-btn" onclick="openPrescription(
                                '<?php echo $row['id']; ?>',
                                '<?php echo htmlspecialchars($row['patient_name'], ENT_QUOTES); ?>',
                                '<?php echo htmlspecialchars($row['appointment_date'], ENT_QUOTES); ?>',
                                '<?php echo date("h:i A", strtotime($row['appointment_time'])); ?>'
                              )">
                                Treat
                              </button>
                            <?php } else { ?>
                              <button class="btn-sm" style="background:#94a3b8;color:#fff;cursor:not-allowed;"
                                disabled>Treat</button>
                            <?php } ?>
                          </div>
                        <?php } else { ?>
                          <span style="color:#64748b;">Reviewed</span>
                        <?php } ?>
                      </td>
                    </tr>
                  <?php } ?>
                <?php } else { ?>
                  <tr>
                    <td colspan="7">
                      <div class="empty">No appointments found for this date.</div>
                    </td>
                  </tr>
                <?php } ?>
              </table>
            </div>
          </div>
        </div>

        <div class="panel">
          <div class="panel-header">
            <div>
              <h3>Pending Appointments</h3>
              <p>Appointments waiting for your approval.</p>
            </div>
          </div>

          <div class="panel-body">
            <?php if ($pendingResult && mysqli_num_rows($pendingResult) > 0) { ?>
              <div class="table-wrap">
                <table class="pending-mini-table">
                  <tr>
                    <th>Patient</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Action</th>
                  </tr>

                  <?php while ($pendingRow = mysqli_fetch_assoc($pendingResult)) { ?>
                    <tr>
                      <td><?php echo htmlspecialchars($pendingRow['patient_name']); ?></td>
                      <td><?php echo htmlspecialchars($pendingRow['appointment_date']); ?></td>
                      <td><?php echo date("h:i A", strtotime($pendingRow['appointment_time'])); ?></td>
                      <td>
                        <div class="small-actions">
                          <a class="btn-sm approve-btn"
                            href="update-status.php?id=<?php echo $pendingRow['id']; ?>&action=approve">Approve</a>
                          <a class="btn-sm reject-btn"
                            href="update-status.php?id=<?php echo $pendingRow['id']; ?>&action=reject">Reject</a>
                        </div>
                      </td>
                    </tr>
                  <?php } ?>
                </table>
              </div>
            <?php } else { ?>
              <div class="empty">No pending appointments waiting for approval.</div>
            <?php } ?>
          </div>
        </div>
      </section>

      <section class="panel full-width-panel">
        <div class="panel-header">
          <div>
            <h3>Quick Overview</h3>
            <p>Helpful shortcuts for your daily work.</p>
          </div>
        </div>

        <div class="panel-body">
          <div class="quick-list">
            <div class="quick-item">
              <h4>Today's Focus</h4>
              <p>
                <strong><?php echo $todayAppointments; ?></strong> appointments are scheduled for today.
                Review pending requests before your consultation hours.
              </p>
            </div>

            <div class="quick-item">
              <h4>Pending Approvals</h4>
              <p>
                You currently have <strong><?php echo $pendingAppointments; ?></strong> pending appointment request(s)
                waiting for your approval.
              </p>
            </div>

            <div class="quick-item">
              <h4>Specialization</h4>
              <p>
                You are listed on the platform as a
                <strong><?php echo htmlspecialchars($doctorSpecialization); ?></strong>.
              </p>
            </div>

            <div class="quick-item">
              <h4>Need to remind patients?</h4>
              <p>
                Use the reminder tool to notify patients about upcoming appointments and improve attendance.
              </p>
            </div>
          </div>

          <div class="footer-note">
            Keep your appointment statuses updated so patients always see accurate information.
          </div>
        </div>
      </section>
    </main>
  </div>

  <div id="prescriptionModal" class="prescription-modal">
    <div class="prescription-modal-content">
      <div class="prescription-modal-top">
        <h3>Treat Patient</h3>
        <span class="prescription-close" onclick="closePrescription()">&times;</span>
      </div>

      <div class="prescription-modal-body">
        <div class="rx-sheet" id="rxSheet">
          <div class="rx-header-band">
            <div>
              <div class="rx-clinic-title"><?php echo htmlspecialchars($doctorHospital); ?></div>
              <div class="rx-clinic-subtitle">Your health, our priority</div>
            </div>
            <div style="font-size:13px; font-weight:800;">Doctor Prescription Copy</div>
          </div>

          <div class="rx-body">
            <div class="rx-left">
              <div class="rx-left-block">
                <div class="rx-doctor-name">Dr. <?php echo htmlspecialchars($doctorName); ?></div>
                <div class="rx-doctor-meta">
                  <?php echo htmlspecialchars($doctorSpecialization); ?><br>
                  MBBS, MD Medicine
                </div>
              </div>

              <div class="rx-left-block">
                <div class="rx-left-heading">Clinic Timing</div>
                <div class="rx-left-text">Mon - Fri
                  8:00 am - 1:00 pm
                  2:00 pm - 8:00 pm

                  Sat
                  8:00 am - 1:00 pm</div>
              </div>

              <div class="rx-left-block">
                <div class="rx-left-heading">Contact</div>
                <div class="rx-left-text">Call: <?php echo htmlspecialchars($doctorPhone); ?>
                  Emergency: <?php echo htmlspecialchars($doctorPhone); ?>
                  Appointment: <?php echo htmlspecialchars($doctorPhone); ?></div>
              </div>

              <div class="rx-left-block">
                <div class="rx-left-heading">Address</div>
                <div class="rx-left-text"><?php echo htmlspecialchars($doctorAddress); ?></div>
              </div>
            </div>

            <div class="rx-right">
              <div class="preview-head">
                <h3>Live Prescription</h3>
                <p>Doctor writes directly in the prescription area.</p>
              </div>

              <div class="rx-patient-grid">
                <div class="rx-patient-line">
                  <strong>Date:</strong>
                  <input type="text" id="rxDate" readonly>
                </div>

                <div class="rx-patient-line">
                  <strong>Appointment Time:</strong>
                  <input type="text" id="rxAppointmentTime" readonly>
                </div>

                <div class="rx-patient-line">
                  <strong>Name:</strong>
                  <input type="text" id="rxPatientName">
                </div>

                <div class="rx-patient-line">
                  <strong>Age:</strong>
                  <input type="text" id="rxAge" placeholder="Enter age">
                </div>

                <div class="rx-patient-line">
                  <strong>Sex:</strong>
                  <select id="rxSex">
                    <option value="">Select</option>
                    <option>Male</option>
                    <option>Female</option>
                    <option>Other</option>
                  </select>
                </div>

                <div class="rx-patient-line">
                  <strong>Weight:</strong>
                  <input type="text" id="rxWeight" placeholder="e.g. 72 kg">
                </div>

                <div class="rx-patient-line">
                  <strong>BP:</strong>
                  <input type="text" id="rxBP" placeholder="e.g. 120/80">
                </div>

                <div class="rx-patient-line">
                  <strong>Height:</strong>
                  <input type="text" id="rxHeight" placeholder="e.g. 170 cm">
                </div>
              </div>

              <div class="rx-symbol">℞</div>

              <div class="rx-block">
                <h4>Common Duration</h4>
                <div class="entry-inline">
                  <div class="entry-row" style="grid-template-columns: 1fr;">
                    <input type="text" id="commonDaysInput" value="5 days" placeholder="Common days for all medicines">
                  </div>
                  <div class="entry-note">This duration will apply to all medicines.</div>
                </div>
              </div>

              <div class="rx-block">
                <h4>Add Medicine</h4>

                <div class="entry-inline">
                  <div class="entry-row">
                    <input type="text" id="medicineNameInput" list="medicineOptions"
                      placeholder="Type medicine name...">
                    <input type="text" id="medicineDoseInput" value="1" placeholder="Dose">

                    <div class="timing-group">
                      <label class="timing-chip">
                        <input type="radio" name="timingOption" value="OD" checked> OD
                      </label>
                      <label class="timing-chip">
                        <input type="radio" name="timingOption" value="BD"> BD
                      </label>
                      <label class="timing-chip">
                        <input type="radio" name="timingOption" value="TDS"> TDS
                      </label>
                    </div>

                    <button class="btn btn-primary" type="button" onclick="addMedicine()">Add</button>
                  </div>

                  <div class="entry-note">Dose examples: 1, 1/2, 2, 5 ml.</div>
                </div>

                <div class="rx-box" id="medicinePreviewBox">No medicines added yet.</div>
              </div>

              <div class="rx-block">
                <h4>Tests</h4>

                <div class="test-bar">
                  <button class="quick-chip" type="button" onclick="addTest('CBC')">CBC</button>
                  <button class="quick-chip" type="button" onclick="addTest('LFT')">LFT</button>
                  <button class="quick-chip" type="button" onclick="addTest('KFT')">KFT</button>
                  <button class="quick-chip" type="button" onclick="addTest('ECG')">ECG</button>
                  <button class="quick-chip" type="button" onclick="addTest('X-Ray Chest')">X-Ray Chest</button>
                  <button class="quick-chip" type="button" onclick="addTest('Blood Sugar Fasting')">Sugar
                    Fasting</button>
                </div>

                <div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:10px;">
                  <input type="text" id="customTestInput" placeholder="Type custom test..."
                    style="flex:1; border:1px solid #cbd5e1; border-radius:10px; padding:10px 12px; font-size:14px;">
                  <button class="btn btn-light" type="button" onclick="addCustomTest()">+ Add Test</button>
                </div>

                <div class="selected-tests" id="selectedTests"></div>
                <div class="rx-box" id="testsPreviewBox" style="margin-top:10px;">No tests added yet.</div>
              </div>

              <div class="rx-block">
                <h4>Advice / Precautions</h4>

                <div class="test-bar">
                  <button class="quick-chip" type="button" onclick="appendAdvice('Take medicines after food.')">After
                    food</button>
                  <button class="quick-chip" type="button" onclick="appendAdvice('Take medicines before food.')">Before
                    food</button>
                  <button class="quick-chip" type="button"
                    onclick="appendAdvice('Drink plenty of water.')">Hydration</button>
                  <button class="quick-chip" type="button" onclick="appendAdvice('Take proper bed rest.')">Bed
                    rest</button>
                  <button class="quick-chip" type="button" onclick="appendAdvice('Avoid oily and spicy food.')">Avoid
                    spicy food</button>
                  <button class="quick-chip" type="button" onclick="appendAdvice('Follow up after 3 days.')">Review 3
                    days</button>
                  <button class="quick-chip" type="button" onclick="appendAdvice('Follow up after 7 days.')">Review 7
                    days</button>
                </div>

                <textarea class="advice-area" id="adviceBox"
                  placeholder="Write precautions and advice here..."></textarea>
                <div class="rx-box" id="advicePreviewBox" style="margin-top:10px;">No advice added yet.</div>
              </div>

              <div class="rx-block">
                <h4>Timing / Follow-up</h4>
                <textarea class="advice-area" id="followupBox"
                  placeholder="Write dosage timing or follow-up date here..."></textarea>
                <div class="rx-box" id="followupPreviewBox" style="margin-top:10px;">No follow-up added yet.</div>
              </div>
            </div>
          </div>
        </div>

        <div class="prescription-actions">
          <button type="button" class="btn btn-light" onclick="closePrescription()">Close</button>
          <button type="button" class="btn btn-primary" onclick="savePrescription()">Save Prescription</button>
        </div>

        <datalist id="medicineOptions">
          <option value="Paracetamol 500mg"></option>
          <option value="Dolo 650"></option>
          <option value="Amoxicillin 500mg"></option>
          <option value="Pantoprazole 40mg"></option>
          <option value="Cetirizine 10mg"></option>
          <option value="ORS"></option>
          <option value="Metformin 500mg"></option>
          <option value="Amlodipine 5mg"></option>
        </datalist>
      </div>
    </div>
  </div>

  <script>
    let currentAppointmentId = '';
    let medicines = [];
    let selectedTests = [];

    function openPrescription(id, patientName, appointmentDate, appointmentTime) {
      currentAppointmentId = id;

      medicines = [];
      selectedTests = [];

      document.getElementById('rxPatientName').value = patientName;
      document.getElementById('rxDate').value = appointmentDate;
      document.getElementById('rxAppointmentTime').value = appointmentTime;

      document.getElementById('rxAge').value = '';
      document.getElementById('rxSex').value = '';
      document.getElementById('rxWeight').value = '';
      document.getElementById('rxBP').value = '';
      document.getElementById('rxHeight').value = '';

      document.getElementById('commonDaysInput').value = '5 days';
      document.getElementById('medicineNameInput').value = '';
      document.getElementById('medicineDoseInput').value = '1';
      document.getElementById('customTestInput').value = '';
      document.getElementById('adviceBox').value = '';
      document.getElementById('followupBox').value = '';

      const defaultTiming = document.querySelector('input[name="timingOption"][value="OD"]');
      if (defaultTiming) defaultTiming.checked = true;

      renderTests();
      syncTimingChips();
      updatePreview();

      document.getElementById('prescriptionModal').style.display = 'block';
      document.body.style.overflow = 'hidden';
    }

    function closePrescription() {
      document.getElementById('prescriptionModal').style.display = 'none';
      document.body.style.overflow = 'auto';
    }

    function getTimingValue() {
      const selected = document.querySelector('input[name="timingOption"]:checked');
      return selected ? selected.value : 'OD';
    }

    function timingForPrescription(value) {
      if (value === 'OD') return 'Morning';
      if (value === 'BD') return 'Morning, Evening';
      if (value === 'TDS') return 'Morning, Afternoon, Evening';
      return value;
    }

    function syncTimingChips() {
      document.querySelectorAll('.timing-chip').forEach(chip => {
        const input = chip.querySelector('input');
        chip.classList.toggle('active', input.checked);
      });
    }

    function addMedicine() {
      const nameInput = document.getElementById('medicineNameInput');
      const doseInput = document.getElementById('medicineDoseInput');
      const daysInput = document.getElementById('commonDaysInput');

      const name = nameInput.value.trim();
      const dose = doseInput.value.trim() || '1';
      const days = daysInput.value.trim() || '5 days';
      const timing = getTimingValue();

      if (!name) {
        alert('Please enter medicine name.');
        return;
      }

      medicines.push({
        name,
        dose,
        timing,
        days
      });

      nameInput.value = '';
      doseInput.value = '1';
      document.querySelector('input[name="timingOption"][value="OD"]').checked = true;

      syncTimingChips();
      updatePreview();
      nameInput.focus();
    }

    function removeMedicine(index) {
      medicines.splice(index, 1);
      updatePreview();
    }

    function addTest(name) {
      if (!name || selectedTests.includes(name)) return;
      selectedTests.push(name);
      renderTests();
      updatePreview();
    }

    function addCustomTest() {
      const input = document.getElementById('customTestInput');
      const value = input.value.trim();
      if (!value) return;
      addTest(value);
      input.value = '';
    }

    function removeTest(name) {
      const index = selectedTests.indexOf(name);
      if (index !== -1) {
        selectedTests.splice(index, 1);
        renderTests();
        updatePreview();
      }
    }

    function renderTests() {
      const container = document.getElementById('selectedTests');
      container.innerHTML = '';

      selectedTests.forEach(test => {
        const item = document.createElement('div');
        item.className = 'selected-test';
        item.innerHTML = `<span>${escapeHtml(test)}</span><button type="button">×</button>`;
        item.querySelector('button').addEventListener('click', () => removeTest(test));
        container.appendChild(item);
      });
    }

    function appendAdvice(text) {
      const box = document.getElementById('adviceBox');
      const current = box.value.trim();
      box.value = current ? current + "\n" + text : text;
      updatePreview();
    }

    function updatePreview() {
      const medicineBox = document.getElementById('medicinePreviewBox');
      if (medicines.length === 0) {
        medicineBox.innerText = 'No medicines added yet.';
      } else {
        medicineBox.innerHTML = medicines.map((med, index) => {
          return `
            <div class="medicine-item">
              <div class="medicine-text">
                <strong>${index + 1}. ${escapeHtml(med.name)}</strong>
                | Dose: ${escapeHtml(med.dose)}
                | Timing: ${escapeHtml(timingForPrescription(med.timing))}
                | Duration: ${escapeHtml(med.days)}
              </div>
              <button class="medicine-remove" type="button" onclick="removeMedicine(${index})">Remove</button>
            </div>
          `;
        }).join('');
      }

      const testsBox = document.getElementById('testsPreviewBox');
      if (selectedTests.length === 0) {
        testsBox.innerText = 'No tests added yet.';
      } else {
        testsBox.innerText = selectedTests.map((t, i) => `${i + 1}. ${t}`).join("\n");
      }

      const adviceText = document.getElementById('adviceBox').value.trim();
      document.getElementById('advicePreviewBox').innerText = adviceText || 'No advice added yet.';

      const followupText = document.getElementById('followupBox').value.trim();
      document.getElementById('followupPreviewBox').innerText = followupText || 'No follow-up added yet.';
    }

    function escapeHtml(text) {
      const div = document.createElement('div');
      div.innerText = text;
      return div.innerHTML;
    }

    function savePrescription() {
      const formData = new URLSearchParams();
      formData.append('appointment_id', currentAppointmentId);
      formData.append('patient_name', document.getElementById('rxPatientName').value);
      formData.append('appointment_date', document.getElementById('rxDate').value);
      formData.append('appointment_time', document.getElementById('rxAppointmentTime').value);
      formData.append('age', document.getElementById('rxAge').value);
      formData.append('sex', document.getElementById('rxSex').value);
      formData.append('weight', document.getElementById('rxWeight').value);
      formData.append('bp', document.getElementById('rxBP').value);
      formData.append('height', document.getElementById('rxHeight').value);

      formData.append('medicines', document.getElementById('medicinePreviewBox').innerHTML);
      formData.append('tests', document.getElementById('testsPreviewBox').innerText);
      formData.append('precautions', document.getElementById('advicePreviewBox').innerText);
      formData.append('timing_advice', document.getElementById('followupPreviewBox').innerText);

      fetch('save-prescription.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: formData.toString()
      })
        .then(response => response.text())
        .then(result => {
          if (result.trim() === 'success') {
            alert('Prescription saved successfully.');
            closePrescription();
            window.location.reload();
          } else {
            alert('Failed to save prescription.');
          }
        })
        .catch(() => {
          alert('Something went wrong while saving prescription.');
        });
    }

    document.addEventListener('input', function (e) {
      if (e.target && e.target.id === 'commonDaysInput') {
        const commonDays = e.target.value.trim() || '5 days';
        medicines.forEach(med => {
          med.days = commonDays;
        });
        updatePreview();
      }

      if (e.target && e.target.id === 'adviceBox') {
        updatePreview();
      }

      if (e.target && e.target.id === 'followupBox') {
        updatePreview();
      }
    });

    document.addEventListener('change', function (e) {
      if (e.target && e.target.name === 'timingOption') {
        syncTimingChips();
      }
    });

    document.addEventListener('keydown', function (e) {
      if (e.target && e.target.id === 'customTestInput' && e.key === 'Enter') {
        e.preventDefault();
        addCustomTest();
      }
    });

    window.addEventListener('click', function (e) {
      const modal = document.getElementById('prescriptionModal');
      if (e.target === modal) {
        closePrescription();
      }
    });
  </script>
</body>

</html>