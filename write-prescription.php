<?php
include '../db.php';
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] != "doctor") {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['appointment_id'])) {
    die("Invalid appointment.");
}

$doctor_id = (int) $_SESSION['user_id'];
$appointment_id = (int) $_GET['appointment_id'];

$query = "
    SELECT 
        appointments.*,
        patients.name AS patient_name,
        patients.email AS patient_email,
        patients.phone AS patient_phone,
        doctors.name AS doctor_name,
        doctors.specialization AS doctor_specialization
    FROM appointments
    JOIN patients ON appointments.patient_id = patients.id
    JOIN doctors ON appointments.doctor_id = doctors.id
    WHERE appointments.id = $appointment_id
      AND appointments.doctor_id = $doctor_id
      AND appointments.status = 'Approved'
    LIMIT 1
";

$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    die("Appointment not found or access denied.");
}

$data = mysqli_fetch_assoc($result);

$currentDateTime = strtotime(date("Y-m-d H:i:s"));
$appointmentDateTime = strtotime($data['appointment_date'] . ' ' . $data['appointment_time']);
$treatStartTime = $appointmentDateTime - (72 * 60 * 60);
$treatEndTime = $appointmentDateTime + (72 * 60 * 60);

if ($currentDateTime < $treatStartTime || $currentDateTime > $treatEndTime) {
    die("Prescription writing is only allowed from 24 hours before to 24 hours after appointment time.");
}

$medicineOptions = [
    "Paracetamol 500mg",
    "Paracetamol 650mg",
    "Amoxicillin 500mg",
    "Azithromycin 250mg",
    "Azithromycin 500mg",
    "Cefixime 200mg",
    "Cefuroxime 500mg",
    "Dolo 650",
    "Ibuprofen 400mg",
    "Aceclofenac 100mg",
    "Pantoprazole 40mg",
    "Omeprazole 20mg",
    "Rabeprazole 20mg",
    "Cetirizine 10mg",
    "Levocetirizine 5mg",
    "Montelukast 10mg",
    "ORS",
    "Vitamin C 500mg",
    "Vitamin D3",
    "Calcium Tablet",
    "Cough Syrup",
    "Antacid Syrup",
    "Metformin 500mg",
    "Metformin 850mg",
    "Amlodipine 5mg",
    "Atorvastatin 10mg",
    "Telmisartan 40mg",
    "Ondansetron 4mg",
    "Domperidone 10mg",
    "Zinc Tablet"
];

$testOptions = [
    "CBC",
    "Blood Sugar Fasting",
    "Blood Sugar PP",
    "HbA1c",
    "LFT",
    "KFT",
    "Urine Routine",
    "Urine Culture",
    "Lipid Profile",
    "Thyroid Profile",
    "X-Ray Chest",
    "X-Ray Knee",
    "Ultrasound Abdomen",
    "Ultrasound Pelvis",
    "ECG",
    "2D Echo",
    "CT Scan",
    "MRI",
    "Dengue Test",
    "Malaria Test",
    "COVID Test",
    "Vitamin D Test",
    "Iron Profile",
    "CRP",
    "ESR",
    "Serum Creatinine"
];

$message = '';
$messageType = '';
$savedPrescription = null;

$postedMedicines = $_POST['medicine_name'] ?? [''];
$postedDosages = $_POST['dosage'] ?? [''];
$postedDays = $_POST['days'] ?? [''];
$postedMorning = $_POST['morning'] ?? [];
$postedAfternoon = $_POST['afternoon'] ?? [];
$postedNight = $_POST['night'] ?? [];
$postedTests = $_POST['test_name'] ?? [''];
$precautionPosted = $_POST['precaution'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $medicineNames = $_POST['medicine_name'] ?? [];
    $dosages = $_POST['dosage'] ?? [];
    $daysArr = $_POST['days'] ?? [];
    $morningArr = $_POST['morning'] ?? [];
    $afternoonArr = $_POST['afternoon'] ?? [];
    $nightArr = $_POST['night'] ?? [];
    $testNames = $_POST['test_name'] ?? [];
    $precaution = trim($_POST['precaution'] ?? '');

    $medicineLines = [];
    foreach ($medicineNames as $index => $medicineName) {
        $medicineName = trim($medicineName);
        if ($medicineName === '') {
            continue;
        }

        $dose = trim($dosages[$index] ?? '');
        $days = trim($daysArr[$index] ?? '');
        $morning = isset($morningArr[$index]) ? 'Morning' : '';
        $afternoon = isset($afternoonArr[$index]) ? 'Afternoon' : '';
        $night = isset($nightArr[$index]) ? 'Night' : '';

        $timings = array_filter([$morning, $afternoon, $night]);
        $timingText = !empty($timings) ? implode('/', $timings) : 'No timing selected';
        $doseText = $dose !== '' ? $dose : 'No dosage';
        $daysText = $days !== '' ? $days . ' day(s)' : 'No days';

        $medicineLines[] = $medicineName . " | Dosage: " . $doseText . " | Time: " . $timingText . " | Duration: " . $daysText;
    }

    $testLines = [];
    foreach ($testNames as $testName) {
        $testName = trim($testName);
        if ($testName !== '') {
            $testLines[] = $testName;
        }
    }

    $medicineText = implode("\n", $medicineLines);
    $testText = implode(", ", $testLines);

    if ($medicineText === '' || $testText === '' || $precaution === '') {
        $message = "Please add medicine, test, and precaution.";
        $messageType = "error";
    } else {
        $checkTable = mysqli_query($conn, "SHOW TABLES LIKE 'prescriptions'");

        if (mysqli_num_rows($checkTable) == 0) {
            $createTable = "
                CREATE TABLE prescriptions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    appointment_id INT NOT NULL,
                    doctor_id INT NOT NULL,
                    patient_id INT NOT NULL,
                    medicine TEXT NOT NULL,
                    test TEXT NOT NULL,
                    precaution TEXT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ";
            mysqli_query($conn, $createTable);
        }

        $medicineEscaped = mysqli_real_escape_string($conn, $medicineText);
        $testEscaped = mysqli_real_escape_string($conn, $testText);
        $precautionEscaped = mysqli_real_escape_string($conn, $precaution);

        $insert = "
            INSERT INTO prescriptions (appointment_id, doctor_id, patient_id, medicine, test, precaution)
            VALUES (
                '{$data['id']}',
                '{$data['doctor_id']}',
                '{$data['patient_id']}',
                '$medicineEscaped',
                '$testEscaped',
                '$precautionEscaped'
            )
        ";

        if (mysqli_query($conn, $insert)) {
            $message = "Prescription saved successfully.";
            $messageType = "success";

            $savedPrescription = [
                'medicine' => $medicineText,
                'test' => $testText,
                'precaution' => $precaution
            ];

            $postedMedicines = [''];
            $postedDosages = [''];
            $postedDays = [''];
            $postedMorning = [];
            $postedAfternoon = [];
            $postedNight = [];
            $postedTests = [''];
            $precautionPosted = '';
        } else {
            $message = "Failed to save prescription.";
            $messageType = "error";
        }
    }
}

function oldValue($arr, $index)
{
    return htmlspecialchars($arr[$index] ?? '');
}

function isChecked($arr, $index)
{
    return isset($arr[$index]) ? 'checked' : '';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Write Prescription | MediCare</title>
    <style>
        :root {
            --primary: #0f766e;
            --primary-2: #14b8a6;
            --primary-soft: #ccfbf1;
            --dark: #0f172a;
            --muted: #64748b;
            --bg: #f8fafc;
            --card: rgba(255, 255, 255, 0.95);
            --border: #e2e8f0;
            --danger: #dc2626;
            --danger-soft: #fee2e2;
            --success: #166534;
            --success-soft: #dcfce7;
            --shadow: 0 20px 45px rgba(15, 23, 42, 0.08);
            --radius: 22px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: "Segoe UI", sans-serif;
        }

        body {
            background:
                radial-gradient(circle at top left, #ccfbf1 0%, transparent 32%),
                radial-gradient(circle at top right, #dbeafe 0%, transparent 26%),
                linear-gradient(180deg, #f8fafc 0%, #eef2ff 100%);
            color: var(--dark);
            padding: 24px;
        }

        .page {
            max-width: 1280px;
            margin: 0 auto;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 22px;
        }

        .title-box h1 {
            font-size: 34px;
            line-height: 1.1;
            margin-bottom: 8px;
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .title-box p {
            color: var(--muted);
            font-size: 15px;
        }

        .nav-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            padding: 12px 18px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: .25s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-2));
            color: white;
            box-shadow: 0 10px 24px rgba(20, 184, 166, 0.22);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
        }

        .btn-light {
            background: white;
            color: var(--dark);
            border: 1px solid var(--border);
        }

        .btn-light:hover {
            background: #f8fafc;
        }

        .btn-print {
            background: var(--dark);
            color: white;
        }

        .hero {
            background: linear-gradient(135deg, #0f766e, #14b8a6);
            color: white;
            border-radius: 28px;
            padding: 28px 30px;
            margin-bottom: 22px;
            box-shadow: var(--shadow);
        }

        .hero h2 {
            font-size: 28px;
            margin-bottom: 8px;
            font-weight: 800;
        }

        .hero p {
            opacity: 0.96;
            line-height: 1.65;
            max-width: 900px;
        }

        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 22px;
            margin-bottom: 22px;
        }

        .detail-card,
        .form-card,
        .print-card {
            background: var(--card);
            border: 1px solid rgba(226, 232, 240, 0.9);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            backdrop-filter: blur(10px);
        }

        .detail-card {
            padding: 24px;
        }

        .detail-card h3 {
            font-size: 21px;
            margin-bottom: 16px;
            font-weight: 800;
        }

        .info-list {
            display: grid;
            gap: 12px;
        }

        .info-row {
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 14px;
        }

        .info-row strong {
            display: block;
            font-size: 11px;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .08em;
            margin-bottom: 6px;
        }

        .info-row span {
            font-size: 15px;
            font-weight: 600;
            word-break: break-word;
        }

        .form-header {
            padding: 22px 24px;
            border-bottom: 1px solid var(--border);
            background: linear-gradient(180deg, #ffffff 0%, #f8fffe 100%);
            border-top-left-radius: var(--radius);
            border-top-right-radius: var(--radius);
        }

        .form-header h3 {
            font-size: 24px;
            margin-bottom: 6px;
            font-weight: 800;
        }

        .form-header p {
            color: var(--muted);
            font-size: 14px;
        }

        .form-body {
            padding: 24px;
        }

        .message {
            margin-bottom: 18px;
            padding: 14px 16px;
            border-radius: 16px;
            font-size: 14px;
            font-weight: 700;
        }

        .message.success {
            background: var(--success-soft);
            color: var(--success);
            border: 1px solid #bbf7d0;
        }

        .message.error {
            background: var(--danger-soft);
            color: var(--danger);
            border: 1px solid #fecaca;
        }

        .row-2 {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 22px;
            margin-bottom: 22px;
        }

        .section-card,
        .precaution-card {
            background: linear-gradient(180deg, #ffffff 0%, #fbffff 100%);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 20px;
        }

        .section-card h4,
        .precaution-card h4 {
            font-size: 20px;
            margin-bottom: 6px;
            font-weight: 800;
        }

        .section-card p,
        .precaution-card p {
            color: var(--muted);
            font-size: 13px;
            margin-bottom: 16px;
        }

        .medicine-list,
        .test-list {
            display: grid;
            gap: 14px;
        }

        .medicine-item,
        .test-item {
            border: 1px solid var(--border);
            border-radius: 18px;
            background: white;
            overflow: hidden;
        }

        .medicine-top,
        .test-top {
            padding: 16px;
            border-bottom: 1px solid var(--border);
            background: #fcfffe;
        }

        .medicine-fields {
            padding: 16px;
            display: grid;
            grid-template-columns: 1fr 170px 1fr;
            gap: 12px;
            align-items: start;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .field label {
            font-size: 13px;
            font-weight: 700;
            color: var(--dark);
        }

        .field input[type="text"],
        .field input[type="number"],
        textarea {
            width: 100%;
            border: 1px solid #cbd5e1;
            background: white;
            border-radius: 14px;
            padding: 13px 14px;
            font-size: 14px;
            outline: none;
            transition: .2s ease;
        }

        .field input:focus,
        textarea:focus {
            border-color: var(--primary-2);
            box-shadow: 0 0 0 4px rgba(20, 184, 166, 0.12);
        }

        .combo-wrap {
            position: relative;
        }

        .combo-input {
            padding-right: 44px !important;
            font-weight: 600;
        }

        .combo-icon {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
            font-size: 14px;
        }

        .timing-box {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            min-height: 48px;
            border: 1px solid var(--border);
            background: #f8fafc;
            border-radius: 14px;
            padding: 11px 12px;
            align-items: center;
        }

        .timing-box label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            cursor: pointer;
        }

        .timing-box input {
            accent-color: var(--primary);
        }

        .inline-actions {
            display: flex;
            justify-content: flex-end;
            padding: 0 16px 16px;
        }

        .btn-add-row,
        .btn-remove-row {
            border: none;
            border-radius: 12px;
            padding: 10px 14px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
        }

        .btn-add-row {
            background: var(--primary-soft);
            color: var(--primary);
        }

        .btn-remove-row {
            background: #fee2e2;
            color: #b91c1c;
        }

        textarea {
            min-height: 140px;
            resize: vertical;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 4px;
        }

        .print-card {
            margin-top: 22px;
            padding: 24px;
        }

        .print-card h3 {
            font-size: 24px;
            margin-bottom: 16px;
            font-weight: 800;
        }

        .print-prescription-box {
            border: 2px solid #cbd5e1;
            border-radius: 20px;
            padding: 24px;
            background: white;
        }

        .print-header {
            text-align: center;
            padding-bottom: 16px;
            border-bottom: 2px dashed #cbd5e1;
            margin-bottom: 18px;
        }

        .print-header h2 {
            font-size: 28px;
            margin-bottom: 4px;
        }

        .print-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-bottom: 18px;
        }

        .print-block {
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 14px;
            line-height: 1.8;
        }

        .print-section {
            margin-top: 18px;
        }

        .print-section h4 {
            margin-bottom: 8px;
            font-size: 18px;
        }

        .print-text {
            white-space: pre-line;
            line-height: 1.75;
            color: #334155;
        }

        .editable-preview {
            min-height: 420px;
            outline: none;
        }

        .editable-preview:focus {
            border-color: #94a3b8;
            box-shadow: 0 0 0 4px rgba(148, 163, 184, 0.15);
        }

        .preview-note {
            margin-top: 10px;
            color: var(--muted);
            font-size: 13px;
        }

        datalist {
            display: none;
        }

        @media (max-width: 1100px) {

            .details-grid,
            .row-2,
            .print-details,
            .medicine-fields {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 700px) {
            body {
                padding: 14px;
            }

            .title-box h1 {
                font-size: 28px;
            }

            .hero,
            .detail-card,
            .form-body,
            .form-header,
            .print-card {
                padding: 18px;
            }
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .topbar,
            .hero,
            .details-grid,
            .form-card,
            .message,
            .preview-note {
                display: none !important;
            }

            .page {
                max-width: 100%;
            }

            .print-card {
                box-shadow: none;
                border: none;
                padding: 0;
                margin: 0;
            }

            .print-prescription-box {
                border: none;
                padding: 0;
            }
        }
    </style>
</head>

<body>
    <div class="page">
        <div class="topbar">
            <div class="title-box">
                <h1>Write Prescription</h1>
                <p>Fast prescription workflow for consultation time.</p>
            </div>

            <div class="nav-actions">
                <button type="button" class="btn btn-print" onclick="window.print()">Print Prescription</button>
                <a href="dashboard.php" class="btn btn-light">Back to Dashboard</a>
            </div>
        </div>

        <section class="hero">
            <h2>Prescription Writing Panel</h2>
            <p>
                Type 2–3 letters to search medicines or tests, select them quickly, then fill dosage,
                timing, and duration without wasting time.
            </p>
        </section>

        <section class="details-grid">
            <div class="detail-card">
                <h3>Doctor Details</h3>
                <div class="info-list">
                    <div class="info-row">
                        <strong>Name</strong>
                        <span>Dr. <?php echo htmlspecialchars($data['doctor_name']); ?></span>
                    </div>
                    <div class="info-row">
                        <strong>Specialization</strong>
                        <span><?php echo htmlspecialchars($data['doctor_specialization']); ?></span>
                    </div>
                </div>
            </div>

            <div class="detail-card">
                <h3>Patient & Appointment Details</h3>
                <div class="info-list">
                    <div class="info-row">
                        <strong>Patient Name</strong>
                        <span><?php echo htmlspecialchars($data['patient_name']); ?></span>
                    </div>
                    <div class="info-row">
                        <strong>Email</strong>
                        <span><?php echo htmlspecialchars($data['patient_email']); ?></span>
                    </div>
                    <div class="info-row">
                        <strong>Phone</strong>
                        <span><?php echo htmlspecialchars($data['patient_phone']); ?></span>
                    </div>
                    <div class="info-row">
                        <strong>Appointment Date</strong>
                        <span><?php echo htmlspecialchars($data['appointment_date']); ?></span>
                    </div>
                    <div class="info-row">
                        <strong>Appointment Time</strong>
                        <span><?php echo date("h:i A", strtotime($data['appointment_time'])); ?></span>
                    </div>
                </div>
            </div>
        </section>

        <section class="form-card">
            <div class="form-header">
                <h3>Treatment Form</h3>
                <p>Search and select items quickly, then complete the remaining details.</p>
            </div>

            <div class="form-body">
                <?php if ($message !== '') { ?>
                    <div class="message <?php echo $messageType === 'success' ? 'success' : 'error'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php } ?>

                <form method="POST" id="prescriptionForm">
                    <div class="row-2">
                        <div class="section-card">
                            <h4>Medicines</h4>
                            <p>Type a few letters, choose medicine, then fill dosage, timing, and days.</p>

                            <div class="medicine-list" id="medicineList">
                                <?php
                                $medicineRowCount = max(1, count($postedMedicines));
                                for ($i = 0; $i < $medicineRowCount; $i++) {
                                    ?>
                                    <div class="medicine-item">
                                        <div class="medicine-top">
                                            <div class="field">
                                                <label>Medicine Search</label>
                                                <div class="combo-wrap">
                                                    <input type="text" name="medicine_name[]" list="medicineOptions"
                                                        class="combo-input" placeholder="Type medicine name..."
                                                        value="<?php echo oldValue($postedMedicines, $i); ?>"
                                                        autocomplete="off">
                                                    <span class="combo-icon">⌄</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="medicine-fields">
                                            <div class="field">
                                                <label>Dosage</label>
                                                <input type="text" name="dosage[]" placeholder="e.g. 1 tablet / 5 ml"
                                                    value="<?php echo oldValue($postedDosages, $i); ?>">
                                            </div>

                                            <div class="field">
                                                <label>Days</label>
                                                <input type="number" name="days[]" min="1" placeholder="e.g. 5"
                                                    value="<?php echo oldValue($postedDays, $i); ?>">
                                            </div>

                                            <div class="field">
                                                <label>Timing</label>
                                                <div class="timing-box">
                                                    <label><input type="checkbox" name="morning[<?php echo $i; ?>]"
                                                            value="1" <?php echo isChecked($postedMorning, $i); ?>>
                                                        Morning</label>
                                                    <label><input type="checkbox" name="afternoon[<?php echo $i; ?>]"
                                                            value="1" <?php echo isChecked($postedAfternoon, $i); ?>>
                                                        Afternoon</label>
                                                    <label><input type="checkbox" name="night[<?php echo $i; ?>]" value="1"
                                                            <?php echo isChecked($postedNight, $i); ?>> Night</label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="inline-actions">
                                            <button type="button" class="btn-remove-row"
                                                onclick="removeMedicineRow(this)">Remove</button>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>

                            <div class="form-actions">
                                <button type="button" class="btn-add-row" onclick="addMedicineRow()">+ Add
                                    Medicine</button>
                            </div>

                            <datalist id="medicineOptions">
                                <?php foreach ($medicineOptions as $medicine) { ?>
                                    <option value="<?php echo htmlspecialchars($medicine); ?>">
                                    <?php } ?>
                            </datalist>
                        </div>

                        <div class="section-card">
                            <h4>Tests</h4>
                            <p>Type a few letters and select each test quickly.</p>

                            <div class="test-list" id="testList">
                                <?php
                                $testRowCount = max(1, count($postedTests));
                                for ($i = 0; $i < $testRowCount; $i++) {
                                    ?>
                                    <div class="test-item">
                                        <div class="test-top">
                                            <div class="field">
                                                <label>Test Search</label>
                                                <div class="combo-wrap">
                                                    <input type="text" name="test_name[]" list="testOptions"
                                                        class="combo-input" placeholder="Type test name..."
                                                        value="<?php echo oldValue($postedTests, $i); ?>"
                                                        autocomplete="off">
                                                    <span class="combo-icon">⌄</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="inline-actions">
                                            <button type="button" class="btn-remove-row"
                                                onclick="removeTestRow(this)">Remove</button>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>

                            <div class="form-actions">
                                <button type="button" class="btn-add-row" onclick="addTestRow()">+ Add Test</button>
                            </div>

                            <datalist id="testOptions">
                                <?php foreach ($testOptions as $test) { ?>
                                    <option value="<?php echo htmlspecialchars($test); ?>">
                                    <?php } ?>
                            </datalist>
                        </div>
                    </div>

                    <div class="precaution-card">
                        <h4>Precautions</h4>
                        <p>Write all patient instructions clearly and simply.</p>
                        <div class="field">
                            <label for="precaution">Precaution Details</label>
                            <textarea name="precaution" id="precaution"
                                placeholder="Example: Drink plenty of water, avoid spicy food, take medicines after food, take proper rest..."
                                required><?php echo htmlspecialchars($precautionPosted); ?></textarea>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Save Prescription</button>
                        <button type="button" class="btn btn-print" onclick="window.print()">Print Prescription</button>
                        <a href="dashboard.php" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </section>

        <section class="print-card">
            <h3>Live Prescription Preview (Editable)</h3>
            <div class="print-prescription-box editable-preview" id="livePreview" contenteditable="true">
                <div class="print-header">
                    <h2>MediCare Prescription</h2>
                    <p>Doctor Prescription Copy</p>
                </div>

                <div class="print-details">
                    <div class="print-block">
                        <strong>Doctor:</strong> Dr. <?php echo htmlspecialchars($data['doctor_name']); ?><br>
                        <strong>Specialization:</strong> <?php echo htmlspecialchars($data['doctor_specialization']); ?>
                    </div>

                    <div class="print-block">
                        <strong>Patient:</strong> <?php echo htmlspecialchars($data['patient_name']); ?><br>
                        <strong>Date:</strong> <?php echo htmlspecialchars($data['appointment_date']); ?><br>
                        <strong>Time:</strong> <?php echo date("h:i A", strtotime($data['appointment_time'])); ?>
                    </div>
                </div>

                <div class="print-section">
                    <h4>Medicines</h4>
                    <div class="print-text" id="previewMedicines"></div>
                </div>

                <div class="print-section">
                    <h4>Tests</h4>
                    <div class="print-text" id="previewTests"></div>
                </div>

                <div class="print-section">
                    <h4>Precautions</h4>
                    <div class="print-text" id="previewPrecaution"></div>
                </div>
            </div>
            <p class="preview-note">This section updates automatically from the form and you can also type directly
                inside it.</p>
        </section>

        <?php if ($savedPrescription) { ?>
            <section class="print-card">
                <h3>Saved Prescription Preview</h3>
                <div class="print-prescription-box">
                    <div class="print-header">
                        <h2>MediCare Prescription</h2>
                        <p>Doctor Prescription Copy</p>
                    </div>

                    <div class="print-details">
                        <div class="print-block">
                            <strong>Doctor:</strong> Dr. <?php echo htmlspecialchars($data['doctor_name']); ?><br>
                            <strong>Specialization:</strong> <?php echo htmlspecialchars($data['doctor_specialization']); ?>
                        </div>

                        <div class="print-block">
                            <strong>Patient:</strong> <?php echo htmlspecialchars($data['patient_name']); ?><br>
                            <strong>Date:</strong> <?php echo htmlspecialchars($data['appointment_date']); ?><br>
                            <strong>Time:</strong> <?php echo date("h:i A", strtotime($data['appointment_time'])); ?>
                        </div>
                    </div>

                    <div class="print-section">
                        <h4>Medicines</h4>
                        <div class="print-text"><?php echo nl2br(htmlspecialchars($savedPrescription['medicine'])); ?></div>
                    </div>

                    <div class="print-section">
                        <h4>Tests</h4>
                        <div class="print-text"><?php echo htmlspecialchars($savedPrescription['test']); ?></div>
                    </div>

                    <div class="print-section">
                        <h4>Precautions</h4>
                        <div class="print-text"><?php echo nl2br(htmlspecialchars($savedPrescription['precaution'])); ?>
                        </div>
                    </div>
                </div>
            </section>
        <?php } ?>
    </div>

    <script>
        let medicineIndex = <?php echo max(1, count($postedMedicines)); ?>;
        let testIndex = <?php echo max(1, count($postedTests)); ?>;

        function addMedicineRow() {
            const container = document.getElementById('medicineList');

            const html = `
                <div class="medicine-item">
                    <div class="medicine-top">
                        <div class="field">
                            <label>Medicine Search</label>
                            <div class="combo-wrap">
                                <input
                                    type="text"
                                    name="medicine_name[]"
                                    list="medicineOptions"
                                    class="combo-input"
                                    placeholder="Type medicine name..."
                                    autocomplete="off"
                                >
                                <span class="combo-icon">⌄</span>
                            </div>
                        </div>
                    </div>

                    <div class="medicine-fields">
                        <div class="field">
                            <label>Dosage</label>
                            <input type="text" name="dosage[]" placeholder="e.g. 1 tablet / 5 ml">
                        </div>

                        <div class="field">
                            <label>Days</label>
                            <input type="number" name="days[]" min="1" placeholder="e.g. 5">
                        </div>

                        <div class="field">
                            <label>Timing</label>
                            <div class="timing-box">
                                <label><input type="checkbox" name="morning[${medicineIndex}]" value="1"> Morning</label>
                                <label><input type="checkbox" name="afternoon[${medicineIndex}]" value="1"> Afternoon</label>
                                <label><input type="checkbox" name="night[${medicineIndex}]" value="1"> Night</label>
                            </div>
                        </div>
                    </div>

                    <div class="inline-actions">
                        <button type="button" class="btn-remove-row" onclick="removeMedicineRow(this)">Remove</button>
                    </div>
                </div>
            `;

            container.insertAdjacentHTML('beforeend', html);
            medicineIndex++;
            attachPreviewListeners();
            updatePreview();
        }

        function removeMedicineRow(button) {
            const container = document.getElementById('medicineList');
            if (container.children.length > 1) {
                button.closest('.medicine-item').remove();
                updatePreview();
            }
        }

        function addTestRow() {
            const container = document.getElementById('testList');

            const html = `
                <div class="test-item">
                    <div class="test-top">
                        <div class="field">
                            <label>Test Search</label>
                            <div class="combo-wrap">
                                <input
                                    type="text"
                                    name="test_name[]"
                                    list="testOptions"
                                    class="combo-input"
                                    placeholder="Type test name..."
                                    autocomplete="off"
                                >
                                <span class="combo-icon">⌄</span>
                            </div>
                        </div>
                    </div>

                    <div class="inline-actions">
                        <button type="button" class="btn-remove-row" onclick="removeTestRow(this)">Remove</button>
                    </div>
                </div>
            `;

            container.insertAdjacentHTML('beforeend', html);
            testIndex++;
            attachPreviewListeners();
            updatePreview();
        }

        function removeTestRow(button) {
            const container = document.getElementById('testList');
            if (container.children.length > 1) {
                button.closest('.test-item').remove();
                updatePreview();
            }
        }

        function getTimingText(index) {
            const morning = document.querySelector(`input[name="morning[${index}]"]`);
            const afternoon = document.querySelector(`input[name="afternoon[${index}]"]`);
            const night = document.querySelector(`input[name="night[${index}]"]`);

            let timings = [];
            if (morning && morning.checked) timings.push("Morning");
            if (afternoon && afternoon.checked) timings.push("Afternoon");
            if (night && night.checked) timings.push("Night");

            return timings.length ? timings.join('/') : 'No timing selected';
        }

        function updatePreview() {
            const medicines = document.querySelectorAll('input[name="medicine_name[]"]');
            const dosages = document.querySelectorAll('input[name="dosage[]"]');
            const days = document.querySelectorAll('input[name="days[]"]');
            const tests = document.querySelectorAll('input[name="test_name[]"]');
            const precaution = document.getElementById('precaution');

            let medicinePreview = '';
            medicines.forEach((med, i) => {
                const medicineName = med.value.trim();
                if (medicineName !== '') {
                    const dose = (dosages[i] && dosages[i].value.trim() !== '') ? dosages[i].value.trim() : 'No dosage';
                    const duration = (days[i] && days[i].value.trim() !== '') ? days[i].value.trim() + ' day(s)' : 'No days';
                    const timing = getTimingText(i);

                    medicinePreview += medicineName + ' | Dosage: ' + dose + ' | Time: ' + timing + ' | Duration: ' + duration + '\n';
                }
            });

            let testPreview = '';
            tests.forEach((test) => {
                if (test.value.trim() !== '') {
                    if (testPreview !== '') testPreview += ', ';
                    testPreview += test.value.trim();
                }
            });

            document.getElementById('previewMedicines').innerText = medicinePreview !== '' ? medicinePreview : 'No medicines added yet.';
            document.getElementById('previewTests').innerText = testPreview !== '' ? testPreview : 'No tests added yet.';
            document.getElementById('previewPrecaution').innerText = precaution.value.trim() !== '' ? precaution.value.trim() : 'No precautions written yet.';
        }

        function attachPreviewListeners() {
            document.querySelectorAll('input[name="medicine_name[]"]').forEach(el => {
                el.removeEventListener('input', updatePreview);
                el.addEventListener('input', updatePreview);
            });

            document.querySelectorAll('input[name="dosage[]"]').forEach(el => {
                el.removeEventListener('input', updatePreview);
                el.addEventListener('input', updatePreview);
            });

            document.querySelectorAll('input[name="days[]"]').forEach(el => {
                el.removeEventListener('input', updatePreview);
                el.addEventListener('input', updatePreview);
            });

            document.querySelectorAll('input[name^="morning["], input[name^="afternoon["], input[name^="night["]').forEach(el => {
                el.removeEventListener('change', updatePreview);
                el.addEventListener('change', updatePreview);
            });

            document.querySelectorAll('input[name="test_name[]"]').forEach(el => {
                el.removeEventListener('input', updatePreview);
                el.addEventListener('input', updatePreview);
            });

            const precaution = document.getElementById('precaution');
            precaution.removeEventListener('input', updatePreview);
            precaution.addEventListener('input', updatePreview);
        }

        attachPreviewListeners();
        updatePreview();
    </script>
</body>

</html>