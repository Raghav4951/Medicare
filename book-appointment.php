<?php
$message = "";
session_start();
include "../config.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != "patient") {
  header("Location: ../login.php");
  exit();
}

$patient_id = $_SESSION['user_id'];
$doctors = mysqli_query($conn, "SELECT * FROM doctors");

$patientQuery = $conn->prepare("SELECT * FROM patients WHERE id=?");
$patientQuery->bind_param("i", $patient_id);
$patientQuery->execute();
$patientResult = $patientQuery->get_result();
$patientData = $patientResult->fetch_assoc();

$familyStmt = $conn->prepare("SELECT * FROM family_members WHERE patient_id=? ORDER BY name ASC");
$familyStmt->bind_param("i", $patient_id);
$familyStmt->execute();
$familyMembers = $familyStmt->get_result();

date_default_timezone_set("Asia/Kolkata");
$today = date("Y-m-d");
$three_month_later = date("Y-m-d", strtotime("+3 months"));

if (isset($_POST['book'])) {
  $doctor_id = $_POST['doctor'] ?? '';
  $date = $_POST['date'] ?? '';
  $time = $_POST['time'] ?? '';
  $booking_for = $_POST['booking_for'] ?? 'self';

  $family_member_id = null;

  if ($booking_for !== 'self') {
    $family_member_id = (int) $booking_for;

    $checkFamily = $conn->prepare("SELECT id FROM family_members WHERE id=? AND patient_id=?");
    $checkFamily->bind_param("ii", $family_member_id, $patient_id);
    $checkFamily->execute();
    $familyCheckResult = $checkFamily->get_result();

    if ($familyCheckResult->num_rows === 0) {
      $message = "Invalid family member selected";
    }
  }

  $prescriptionPath = null;
  $targetDir = "../uploads/Old_prescription/";

  if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
  }

  if (isset($_FILES["Prescription"]) && $_FILES["Prescription"]["error"] == 0) {
    $fileName = time() . "_" . basename($_FILES["Prescription"]["name"]);
    $targetFile = $targetDir . $fileName;
    $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

    if (in_array($fileType, ["jpg", "jpeg", "png", "pdf"])) {
      if (move_uploaded_file($_FILES["Prescription"]["tmp_name"], $targetFile)) {
        $prescriptionPath = $fileName;
      }
    }
  }

  if (empty($message)) {
    $stmt = $conn->prepare("SELECT id FROM appointments WHERE doctor_id=? AND appointment_date=? AND appointment_time=?");
    $stmt->bind_param("iss", $doctor_id, $date, $time);
    $stmt->execute();
    $check = $stmt->get_result();

    if (empty($doctor_id) || empty($date) || empty($time) || empty($booking_for)) {
      $message = "All fields are required";
    } elseif ($date < $today) {
      $message = "Past date not allowed";
    } elseif ($date > $three_month_later) {
      $message = "Appointment can only be booked within 3 months";
    } elseif ($check->num_rows > 0) {
      $message = "This slot is already booked";
    } else {
      $insert = $conn->prepare("INSERT INTO appointments (patient_id, family_member_id, doctor_id, appointment_date, appointment_time, report_file) VALUES (?, ?, ?, ?, ?, ?)");
      $insert->bind_param("iiisss", $patient_id, $family_member_id, $doctor_id, $date, $time, $prescriptionPath);

      if ($insert->execute()) {
        $message = "Appointment Booked Successfully. Redirecting to My Appointments...";
        $redirect = true; // trigger redirect
      } else {
        $message = "Error: " . $conn->error;
      }
    }
  }
}
?>

<!DOCTYPE html>
<html>

<head>
  <title>Book Appointment | MediCare+</title>
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
      font-family: 'Segoe UI', sans-serif;
      background: var(--bg);
    }

    .header {
      background: linear-gradient(135deg, #0d9488, #14b8a6);
      color: white;
      padding: 30px 20px 70px 65px;
      border-radius: 0 0 30px 30px;
    }

    .container {
      padding: 20px;
      margin-top: -50px;
    }

    .main-card {
      background: var(--card);
      padding: 20px;
      border-radius: 18px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.06);
    }

    .inner-layout {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
      margin-bottom: 20px;
    }

    @media(max-width:768px) {
      .inner-layout {
        grid-template-columns: 1fr;
      }
    }

    .inner-card {
      background: #ffffff;
      padding: 20px;
      border-radius: 18px;
      box-shadow: 0 4px 14px rgba(0, 0, 0, 0.04);
      border: 1px solid #e5e7eb;
    }

    .mini-card {
      background: #f1f5f9;
      padding: 15px;
      border-radius: 14px;
      margin-bottom: 15px;
    }

    .mini-card h3 {
      margin: 0 0 8px 0;
    }

    label {
      font-weight: 600;
      margin-top: 10px;
      display: block;
    }

    input,
    select {
      width: 100%;
      padding: 12px;
      margin-top: 6px;
      border-radius: 10px;
      border: 1px solid #cbd5e1;
      box-sizing: border-box;
    }

    button {
      margin-top: 15px;
      width: 100%;
      padding: 12px;
      border: none;
      border-radius: 12px;
      background: var(--primary);
      color: white;
      cursor: pointer;
    }

    .doctor-grid,
    .patient-grid {
      display: grid;
      gap: 15px;
      margin-top: 12px;
      max-height: 250px;
      overflow-y: auto;
    }

    .doctor-card,
    .patient-card {
      background: #f1f5f9;
      padding: 15px;
      border-radius: 14px;
      cursor: pointer;
      border-left: 4px solid var(--primary);
      transition: 0.3s;
    }

    .doctor-card.active,
    .patient-card.active {
      border: 2px solid #28a745;
      background: #eaffea;
    }

    .doctor-card:hover,
    .patient-card:hover {
      transform: translateY(-3px);
    }

    .doctor-card h4,
    .patient-card h4 {
      margin: 0;
    }

    .doctor-card p,
    .patient-card p {
      margin: 5px 0 0;
      color: var(--muted);
    }

    .menu-btn {
      position: absolute;
      top: 20px;
      left: 20px;
      font-size: 24px;
      color: white;
      cursor: pointer;
      z-index: 1001;
      background: rgba(0, 0, 0, 0.2);
      padding: 5px 10px;
      border-radius: 8px;
      transition: 0.3s;
    }

    .menu-btn:hover {
      background: rgba(0, 0, 0, 0.4);
    }

    .sidebar {
      position: fixed;
      width: 280px;
      height: 100vh;
      left: -280px;
      top: 0;
      background: #1e8c84;
      transition: all 0.3s ease-in-out;
      z-index: 1005;
      box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
      display: flex;
      flex-direction: column;
      padding-top: 60px;
    }

    .sidebar.active {
      left: 0;
    }

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

    .sidebar a {
      display: block;
      color: #ccf2f0;
      padding: 16px 25px;
      text-decoration: none;
      font-size: 16px;
      font-weight: 500;
      border-bottom: 1px solid rgba(255, 255, 255, 0.05);
      transition: 0.2s;
    }

    .sidebar a:hover {
      background: #0d9488;
      color: white;
      padding-left: 35px;
    }

    .sidebar::before {
      content: "MediCare+";
      padding: 20px 25px;
      font-size: 22px;
      font-weight: bold;
      color: white;
      display: block;
    }

    .overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(15, 23, 42, 0.6);
      backdrop-filter: blur(4px);
      z-index: 1004;
    }

    .overlay.active {
      display: block;
    }

    .slots {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
      gap: 10px;
      margin-top: 10px;
    }

    .slots button {
      padding: 10px;
      border-radius: 8px;
      border: none;
      font-size: 13px;
      font-weight: 500;
      transition: 0.2s;
      margin-top: 0;
    }

    .slots button.available {
      background: #e6f9ec;
      color: #1e7e34;
    }

    .slots button.available:hover {
      background: #28a745;
      color: #fff;
    }

    .slots button.booked {
      background: #fdecea;
      color: #dc3545;
      cursor: not-allowed;
    }

    .slots button.past {
      background: #eee;
      color: #777;
      cursor: not-allowed;
    }

    .slots button.active {
      background: #007bff;
      color: white;
    }

    .loader {
      text-align: center;
      padding: 15px;
      font-weight: bold;
      color: #666;
    }

    .success-box {
      background: #dcfce7;
      color: #166534;
      padding: 10px;
      border-radius: 10px;
      margin-bottom: 15px;
      font-weight: 600;
    }

    .summary-card {
      background: #f8fafc;
      padding: 20px;
      border-radius: 18px;
      border: 1px solid #e2e8f0;
    }

    .summary-card h3 {
      margin-top: 0;
    }

    .summary-card p {
      margin: 8px 0;
    }

    .rules-box ul {
      margin: 10px 0 0;
      padding-left: 20px;
      color: var(--muted);
    }

    .rules-box li {
      margin-bottom: 8px;
    }
  </style>
</head>

<body>

  <div class="overlay" id="overlay" onclick="toggleMenu()"></div>

  <div class="menu-btn" onclick="toggleMenu()">☰</div>
  <div class="sidebar" id="sidebar">

    <div class="back-btn" onclick="toggleMenu()">←</div>

    <a href="dashboard.php">🏠 Dashboard</a>
    <a href="book-appointment.php">📅 Book Appointment</a>
    <a href="my-appointment.php">📋 My Appointments</a>
    <a href="prescription.php">📜 Health History</a>
    <a href="doctors.php">👨‍⚕️ Doctors</a>
    <a href="my-profile.php">🙍 My Profile</a>
    <a href="../login.php">🚪 Logout</a>
  </div>

  <div class="header">
    <h2>Book Appointment</h2>
    <p>Select doctor, patient and schedule your visit</p>
  </div>

  <div class="container">
    <div class="main-card">

      <?php if ($message != "") { ?>
        <div class="success-box"><?php echo $message; ?></div>
      <?php } ?>

      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="doctor" id="doctorInput">
        <input type="hidden" name="time" id="timeInput">
        <input type="hidden" name="booking_for" id="bookingForInput" value="self">

        <div class="inner-layout">

          <!-- LEFT CARD -->
          <div class="inner-card">
            <div class="mini-card">
              <h3>👨‍⚕️ Selected Doctor</h3>
              <p id="docName">None selected</p>
              <p id="docSpec" style="color:gray;"></p>
            </div>

            <div class="mini-card">
              <h3>🧑 Selected Patient</h3>
              <p id="patientName"><?php echo htmlspecialchars($patientData['name']); ?></p>
              <p id="patientRelation" style="color:gray;">Self</p>
            </div>

            <label>Select Date</label>
            <input type="date" name="date" id="dateInput" min="<?php echo $today; ?>"
              max="<?php echo $three_month_later; ?>" required>

            <label>Select Time Slot</label>
            <div class="slots"></div>

            <label>Upload Previous Prescription (Optional)</label>
            <input type="file" name="Prescription" accept=".jpeg,.png,.jpg,.pdf">
          </div>

          <!-- RIGHT CARD -->
          <div class="inner-card">
            <div class="mini-card rules-box">
              <h3>📌 Booking Rules</h3>
              <ul>
                <li>Only within 3 months</li>
                <li>No past time booking</li>
                <li>Select one doctor and one patient</li>
                <li>Carry prescription if available</li>
              </ul>
            </div>

            <h3>👨‍⚕️ Doctors</h3>
            <input type="text" placeholder="Search doctor..." onkeyup="filterDoctors(this.value)">

            <div class="doctor-grid" id="doctorList">
              <?php
              mysqli_data_seek($doctors, 0);
              while ($doc = mysqli_fetch_assoc($doctors)) { ?>
                <div class="doctor-card" data-id="<?php echo $doc['id']; ?>"
                  data-name="<?php echo htmlspecialchars($doc['name']); ?>"
                  data-spec="<?php echo htmlspecialchars($doc['specialization']); ?>"
                  onclick="selectDoctor('<?php echo $doc['id']; ?>','<?php echo addslashes(htmlspecialchars($doc['name'])); ?>','<?php echo addslashes(htmlspecialchars($doc['specialization'])); ?>')">
                  <h4><?php echo htmlspecialchars($doc['name']); ?></h4>
                  <p><?php echo htmlspecialchars($doc['specialization']); ?></p>
                </div>
              <?php } ?>
            </div>

            <h3 style="margin-top:20px;">🧑 Patients</h3>
            <div class="patient-grid">
              <div class="patient-card active" data-id="self"
                onclick="selectPatient('self', '<?php echo addslashes(htmlspecialchars($patientData['name'])); ?>', 'Self')">
                <h4><?php echo htmlspecialchars($patientData['name']); ?></h4>
                <p>Self</p>
              </div>

              <?php while ($family = $familyMembers->fetch_assoc()) { ?>
                <div class="patient-card" data-id="<?php echo $family['id']; ?>"
                  onclick="selectPatient('<?php echo $family['id']; ?>', '<?php echo addslashes(htmlspecialchars($family['name'])); ?>', '<?php echo addslashes(htmlspecialchars($family['relation'])); ?>')">
                  <h4><?php echo htmlspecialchars($family['name']); ?></h4>
                  <p><?php echo htmlspecialchars($family['relation']); ?></p>
                </div>
              <?php } ?>
            </div>
          </div>

        </div>

        <!-- SUMMARY OUTSIDE -->
        <div class="summary-card">
          <h3>📋 Summary</h3>
          <p>Patient: <span id="sumPatient"><?php echo htmlspecialchars($patientData['name']); ?></span></p>
          <p>Doctor: <span id="sumDoctor">-</span></p>
          <p>Date: <span id="sumDate">-</span></p>
          <p>Time: <span id="sumTime">-</span></p>
        </div>

        <button type="submit" name="book">Confirm Appointment</button>
      </form>

    </div>
  </div>

  <script>
    function toggleMenu() {
      document.getElementById("sidebar").classList.toggle("active");
      document.getElementById("overlay").classList.toggle("active");
    }

    function selectDoctor(id, name, spec) {
      document.getElementById("doctorInput").value = id;
      document.getElementById("docName").innerText = name;
      document.getElementById("docSpec").innerText = spec;
      document.getElementById("sumDoctor").innerText = name;

      document.querySelectorAll(".doctor-card").forEach(card => {
        card.classList.remove("active");
        if (card.dataset.id == id) {
          card.classList.add("active");
        }
      });

      const dateVal = document.getElementById("dateInput").value;
      if (dateVal) fetchBookedSlots(dateVal, id);
    }

    function selectPatient(id, name, relation) {
      document.getElementById("bookingForInput").value = id;
      document.getElementById("patientName").innerText = name;
      document.getElementById("patientRelation").innerText = relation;
      document.getElementById("sumPatient").innerText = name;

      document.querySelectorAll(".patient-card").forEach(card => {
        card.classList.remove("active");
        if (card.dataset.id == id) {
          card.classList.add("active");
        }
      });
    }

    function setTime(time, btn) {
      document.getElementById("timeInput").value = time;
      document.getElementById("sumTime").innerText = time;

      document.querySelectorAll(".slots button").forEach(b => b.classList.remove("active"));
      btn.classList.add("active");
    }

    function filterDoctors(value) {
      value = value.toLowerCase();
      document.querySelectorAll(".doctor-card").forEach(card => {
        let name = card.getAttribute("data-name").toLowerCase();
        card.style.display = name.includes(value) ? "block" : "none";
      });
    }

    function fetchBookedSlots(date, doctor_id) {
      if (!doctor_id) return;

      const container = document.querySelector(".slots");
      container.innerHTML = `<div class="loader">Loading slots...</div>`;

      fetch("get_slot.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `date=${date}&doctor_id=${doctor_id}`
      })
        .then(res => res.json())
        .then(data => {
          renderSlots(data, date);
        })
        .catch(err => {
          console.error(err);
          renderSlots([], date);
        });
    }

    function renderSlots(bookedSlots = [], selectedDate = null) {
      const container = document.querySelector(".slots");
      container.innerHTML = "";

      if (!Array.isArray(bookedSlots)) bookedSlots = [];

      const now = new Date();

      const generateRange = (start, end) => {
        for (let h = start; h < end; h++) {
          for (let m = 0; m < 60; m += 10) {
            let timeStrDB = `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;

            let ampm = h >= 12 ? "PM" : "AM";
            let displayH = h % 12 || 12;
            let displayTime = `${displayH}:${String(m).padStart(2, '0')} ${ampm}`;

            let btn = document.createElement("button");
            btn.type = "button";
            btn.innerText = displayTime;

            let isPast = false;
            if (selectedDate) {
              let slotDateTime = new Date(selectedDate + "T" + timeStrDB);
              if (slotDateTime < now) isPast = true;
            }

            if (bookedSlots.map(t => t && t.substring(0, 5)).includes(timeStrDB)) {
              btn.classList.add("booked");
              btn.disabled = true;
            } else if (isPast) {
              btn.classList.add("past");
              btn.disabled = true;
            } else {
              btn.classList.add("available");
              btn.onclick = function () {
                setTime(timeStrDB, this);
              };
            }

            container.appendChild(btn);
          }
        }
      };

      generateRange(10, 14);
      generateRange(16, 20);
    }

    document.getElementById("dateInput").addEventListener("change", function () {
      document.getElementById("sumDate").innerText = this.value;

      const doctor_id = document.getElementById("doctorInput").value;

      if (!doctor_id) {
        alert("Please select a doctor first");
        this.value = "";
        return;
      }

      fetchBookedSlots(this.value, doctor_id);
    });

    window.onload = function () {
      renderSlots([], null);
      selectPatient('self', '<?php echo addslashes(htmlspecialchars($patientData['name'])); ?>', 'Self');
    };
  </script>
  <?php if (isset($redirect) && $redirect === true): ?>
    <script>
      setTimeout(function () {
        window.location.href = "my-appointment.php";
      }, 5000); // 5 seconds
    </script>
  <?php endif; ?>
</body>

</html>