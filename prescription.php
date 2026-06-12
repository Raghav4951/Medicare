<?php
session_start();
include "../config.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != "patient") {
    header("Location: ../login.php");
    exit();
}

$patient_id = (int) $_SESSION['user_id'];

/* LEFT SIDE: Patient uploaded files from old system */
$query_old = $conn->prepare("
    SELECT a.*, d.name AS doctor_name
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.id
    WHERE a.patient_id = ?
      AND a.report_file IS NOT NULL
      AND a.report_file != ''
    ORDER BY a.appointment_date DESC
");
$query_old->bind_param("i", $patient_id);
$query_old->execute();
$result_old = $query_old->get_result();

/* RIGHT SIDE: Doctor saved prescriptions from prescriptions table */
$query_doc = $conn->prepare("
    SELECT 
        p.*,
        d.name AS doctor_name,
        a.appointment_date
    FROM prescriptions p
    JOIN doctors d ON p.doctor_id = d.id
    JOIN appointments a ON p.appointment_id = a.id
    WHERE p.patient_id = ?
    ORDER BY p.created_at DESC
");
$query_doc->bind_param("i", $patient_id);
$query_doc->execute();
$result_doc = $query_doc->get_result();

function safeText($value)
{
    return htmlspecialchars((string) $value);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Prescriptions | MediCare+</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        :root {
            --primary: #0d9488;
            --bg: #f8fafc;
            --card: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg);
            overflow-x: hidden;
        }

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
            display: flex;
            padding: 15px 25px;
            transition: 0.3s;
        }

        .sidebar a:hover {
            background-color: #145c59;
            padding-left: 30px;
        }

        .menu-btn {
            position: absolute;
            top: 25px;
            left: 20px;
            font-size: 28px;
            color: white;
            cursor: pointer;
            z-index: 1000;
        }

        .header {
            background: linear-gradient(135deg, #0d9488, #14b8a6);
            color: #fff;
            padding: 30px 20px 70px 65px;
            border-radius: 0 0 30px 30px;
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

        .container {
            padding: 20px;
            margin-top: -50px;
        }

        .split-view {
            display: flex;
            gap: 20px;
            align-items: flex-start;
        }

        .column {
            flex: 1;
        }

        .column-header {
            background: #fff;
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 15px;
            text-align: center;
            font-weight: bold;
            color: var(--primary);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        @media (max-width: 900px) {
            .split-view {
                flex-direction: column;
            }
        }

        .card {
            background: var(--card);
            padding: 20px;
            border-radius: 18px;
            margin-bottom: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, .06);
        }

        .meta {
            color: var(--muted);
            font-size: 13px;
            margin-top: 4px;
        }

        .actions a,
        .actions button {
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            margin-left: 8px;
            display: inline-block;
            cursor: pointer;
            border: none;
        }

        .view {
            background: var(--primary);
            color: #fff;
        }

        .download {
            background: #1e293b;
            color: #fff;
        }

        .overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.4);
            z-index: 1000;
        }

        .overlay.active {
            display: block;
        }

        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.85);
            z-index: 2000;
            padding: 20px;
            overflow-y: auto;
        }

        .modal-content {
            background: #fff;
            margin: 2% auto;
            width: 95%;
            max-width: 900px;
            min-height: 80vh;
            padding: 20px;
            border-radius: 18px;
            position: relative;
        }

        #previewBody {
            width: 100%;
            height: 100%;
        }

        #previewBody iframe,
        #previewBody img {
            width: 100%;
            height: 75vh;
            object-fit: contain;
            border-radius: 8px;
            border: none;
        }

        .close {
            position: absolute;
            right: 15px;
            top: 5px;
            font-size: 35px;
            cursor: pointer;
            color: #333;
        }

        .rx-view {
            border: 1px solid var(--border);
            border-radius: 18px;
            overflow: hidden;
            background: white;
        }

        .rx-head {
            background: linear-gradient(135deg, #0d9488, #14b8a6);
            color: white;
            padding: 18px 20px;
        }

        .rx-head h2 {
            margin: 0;
            font-size: 24px;
        }

        .rx-head p {
            margin: 6px 0 0;
            font-size: 13px;
            opacity: 0.95;
        }

        .rx-body {
            padding: 20px;
        }

        .rx-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(220px, 1fr));
            gap: 10px 18px;
            margin-bottom: 18px;
        }

        .rx-line {
            font-size: 14px;
            color: var(--text);
        }

        .rx-section {
            margin-top: 18px;
        }

        .rx-section h4 {
            margin: 0 0 8px;
            color: var(--primary);
            font-size: 16px;
        }

        .rx-box {
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
            background: #fcfcfd;
            padding: 12px;
            line-height: 1.6;
            color: #334155;
            word-break: break-word;
        }

        .empty-text {
            color: var(--muted);
            font-style: italic;
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
                <li><a href="prescription.php">💊 Prescriptions</a></li>
                <li><a href="../login.php">🚪 Logout</a></li>
            </ul>
        </div>

        <div class="header">
            <h2>📜 Prescription Management</h2>
            <p>Access your uploads and doctor reports side-by-side</p>
        </div>

        <div class="container">
            <div class="split-view">

                <div class="column">
                    <div class="column-header">📤 My Uploaded Prescriptions</div>
                    <?php if ($result_old->num_rows > 0): ?>
                        <?php while ($row = $result_old->fetch_assoc()):
                            $file = $row['report_file'];
                            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                            ?>
                            <div class="card">
                                <h3>👨‍⚕️ Dr. <?php echo safeText($row['doctor_name']); ?></h3>
                                <div class="meta">📅 <?php echo date("d M Y", strtotime($row['appointment_date'])); ?></div>
                                <div class="actions" style="margin-top:10px; text-align:right;">
                                    <a class="view"
                                        onclick="openFilePreview('<?php echo addslashes(htmlspecialchars($file)); ?>', '<?php echo safeText($ext); ?>')">View</a>
                                    <a href="../uploads/old_prescription/<?php echo rawurlencode($file); ?>" class="download"
                                        download>Download</a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="card">
                            <p style="text-align:center; color:var(--muted);">No uploads found.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="column">
                    <div class="column-header">👨‍⚕️ Doctor's Prescriptions</div>
                    <?php if ($result_doc->num_rows > 0): ?>
                        <?php while ($row = $result_doc->fetch_assoc()): ?>
                            <div class="card">
                                <h3>📋 Dr. <?php echo safeText($row['doctor_name']); ?></h3>
                                <div class="meta">📅 <?php echo date("d M Y", strtotime($row['appointment_date'])); ?></div>
                                <div class="actions" style="margin-top:10px; text-align:right;">
                                    <button class="view" type="button" onclick='openDoctorPrescription(
                                            <?php echo json_encode($row["doctor_name"] ?? ""); ?>,
                                            <?php echo json_encode($row["appointment_date"] ?? ""); ?>,
                                            <?php echo json_encode($row["patient_name"] ?? ""); ?>,
                                            <?php echo json_encode($row["age"] ?? ""); ?>,
                                            <?php echo json_encode($row["sex"] ?? ""); ?>,
                                            <?php echo json_encode($row["weight"] ?? ""); ?>,
                                            <?php echo json_encode($row["bp"] ?? ""); ?>,
                                            <?php echo json_encode($row["height"] ?? ""); ?>,
                                            <?php echo json_encode($row["medicine"] ?? ($row["medicines"] ?? "")); ?>,
                                            <?php echo json_encode($row["test"] ?? ($row["tests"] ?? "")); ?>,
                                            <?php echo json_encode($row["precaution"] ?? ($row["precautions"] ?? "")); ?>,
                                            <?php echo json_encode($row["timing_advice"] ?? ""); ?>
                                        )'>
                                        View
                                    </button>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="card">
                            <p style="text-align:center; color:var(--muted);">No doctor prescriptions found.</p>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>

    <div id="previewModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closePreview()">&times;</span>
            <div id="previewBody"></div>
        </div>
    </div>

    <script>
        function toggleMenu() {
            document.getElementById("sidebar").classList.toggle("active");
            document.getElementById("mainContent").classList.toggle("shift");
            document.getElementById("overlay").classList.toggle("active");
        }

        const UPLOADS_PATH = "../uploads/old_prescription/";

        function openFilePreview(fileName, ext) {
            const body = document.getElementById("previewBody");
            const fullPath = UPLOADS_PATH + fileName;

            if (ext === "pdf") {
                body.innerHTML = `<iframe src="${fullPath}#toolbar=0"></iframe>`;
            } else {
                body.innerHTML = `<img src="${fullPath}" alt="Prescription">`;
            }

            document.getElementById("previewModal").style.display = "block";
        }

        function formatBlock(value) {
            if (!value || value.trim() === "") {
                return `<div class="empty-text">Not added</div>`;
            }
            return value;
        }

        function escapeHtml(text) {
            const div = document.createElement("div");
            div.innerText = text || "";
            return div.innerHTML;
        }

        function openDoctorPrescription(doctorName, appointmentDate, patientName, age, sex, weight, bp, height, medicine, test, precaution, timingAdvice) {
            const body = document.getElementById("previewBody");

            body.innerHTML = `
                <div class="rx-view">
                    <div class="rx-head">
                        <h2>Doctor Prescription</h2>
                        <p>Dr. ${escapeHtml(doctorName)} | ${escapeHtml(appointmentDate)}</p>
                    </div>

                    <div class="rx-body">
                        <div class="rx-grid">
                            <div class="rx-line"><strong>Patient Name:</strong> ${escapeHtml(patientName || '')}</div>
                            <div class="rx-line"><strong>Age:</strong> ${escapeHtml(age || '')}</div>
                            <div class="rx-line"><strong>Sex:</strong> ${escapeHtml(sex || '')}</div>
                            <div class="rx-line"><strong>Weight:</strong> ${escapeHtml(weight || '')}</div>
                            <div class="rx-line"><strong>BP:</strong> ${escapeHtml(bp || '')}</div>
                            <div class="rx-line"><strong>Height:</strong> ${escapeHtml(height || '')}</div>
                        </div>

                        <div class="rx-section">
                            <h4>Medicines</h4>
                            <div class="rx-box">${formatBlock(medicine || '')}</div>
                        </div>

                        <div class="rx-section">
                            <h4>Tests</h4>
                            <div class="rx-box">${formatBlock(test || '')}</div>
                        </div>

                        <div class="rx-section">
                            <h4>Precautions</h4>
                            <div class="rx-box">${formatBlock(precaution || '')}</div>
                        </div>

                        <div class="rx-section">
                            <h4>Timing / Follow-up</h4>
                            <div class="rx-box">${formatBlock(timingAdvice || '')}</div>
                        </div>
                    </div>
                </div>
            `;

            document.getElementById("previewModal").style.display = "block";
        }

        function closePreview() {
            document.getElementById("previewModal").style.display = "none";
            document.getElementById("previewBody").innerHTML = "";
        }

        window.onclick = function (e) {
            if (e.target.id === "previewModal") closePreview();
        }
    </script>
</body>

</html>