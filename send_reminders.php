<?php
session_start();
include '../db.php';
require '../vendor/autoload.php';

use Twilio\Rest\Client;

if (!isset($_SESSION['role']) || $_SESSION['role'] != "doctor") {
    header("Location: ../login.php");
    exit();
}

$doctor_id = (int) $_SESSION['user_id'];
$selected_date = isset($_GET['date']) && $_GET['date'] !== '' ? $_GET['date'] : date('Y-m-d', strtotime('+1 day'));
$message = '';
$messageType = '';

/*
|--------------------------------------------------------------------------
| Twilio Credentials
|--------------------------------------------------------------------------
| Replace these with your real Twilio credentials
*/
$sid = "AC5e232167e490edb2ae31ab7160b32978";
$token = "536a7bcaa6906a1bd06b8f1cd6f7016a";
$twilio_number = "+14788127621";

/*
|--------------------------------------------------------------------------
| Doctor details
|--------------------------------------------------------------------------
*/
$doctor = null;
$doctorQuery = mysqli_query($conn, "SELECT * FROM doctors WHERE id = $doctor_id LIMIT 1");
if ($doctorQuery && mysqli_num_rows($doctorQuery) > 0) {
    $doctor = mysqli_fetch_assoc($doctorQuery);
}

function getInitials($name)
{
    $name = trim($name);
    if ($name === '')
        return 'DR';

    $parts = preg_split('/\s+/', $name);
    $initials = '';

    foreach ($parts as $part) {
        if ($part !== '') {
            $initials .= strtoupper(substr($part, 0, 1));
        }
        if (strlen($initials) >= 2)
            break;
    }

    return $initials ?: 'DR';
}

$doctorName = $doctor && !empty($doctor['name']) ? $doctor['name'] : 'Doctor';
$doctorSpecialization = $doctor && !empty($doctor['specialization']) ? $doctor['specialization'] : 'Specialist';

/*
|--------------------------------------------------------------------------
| Fetch approved appointments for selected date
|--------------------------------------------------------------------------
*/
$appointments = [];
$appointmentsQuery = "
    SELECT 
        appointments.id,
        appointments.appointment_date,
        appointments.appointment_time,
        appointments.status,
        patients.name AS patient_name,
        patients.phone AS patient_phone
    FROM appointments
    JOIN patients ON appointments.patient_id = patients.id
    WHERE appointments.doctor_id = '$doctor_id'
      AND appointments.appointment_date = '$selected_date'
      AND appointments.status = 'Approved'
    ORDER BY appointments.appointment_time ASC
";

$appointmentsResult = mysqli_query($conn, $appointmentsQuery);
if ($appointmentsResult) {
    while ($row = mysqli_fetch_assoc($appointmentsResult)) {
        $appointments[] = $row;
    }
}

/*
|--------------------------------------------------------------------------
| Send reminders
|--------------------------------------------------------------------------
*/
if (isset($_POST['send_reminders'])) {
    if ($sid === "YOUR_ACCOUNT_SID" || $token === "YOUR_AUTH_TOKEN" || $twilio_number === "YOUR_TWILIO_NUMBER") {
        $message = "Please configure your Twilio credentials first in send_reminders.php.";
        $messageType = "error";
    } elseif (empty($appointments)) {
        $message = "No approved appointments found for the selected date.";
        $messageType = "error";
    } else {
        $successCount = 0;
        $failedCount = 0;

        try {
            $client = new Client($sid, $token);

            foreach ($appointments as $row) {
                $name = trim($row['patient_name']);
                $phoneRaw = preg_replace('/\D/', '', $row['patient_phone']);
                $timeFormatted = date("h:i A", strtotime($row['appointment_time']));
                $dateFormatted = date("F d, Y", strtotime($row['appointment_date']));

                if ($phoneRaw === '') {
                    $failedCount++;
                    continue;
                }

                if (strlen($phoneRaw) == 10) {
                    $phone = "+91" . $phoneRaw;
                } elseif (strlen($phoneRaw) > 10 && substr($phoneRaw, 0, 2) !== '91') {
                    $phone = "+" . $phoneRaw;
                } else {
                    $phone = "+" . $phoneRaw;
                }

                $body = "Reminder from MediCare: Hello $name, this is a reminder for your appointment with Dr. $doctorName on $dateFormatted at $timeFormatted.";

                try {
                    $client->messages->create(
                        $phone,
                        [
                            'from' => $twilio_number,
                            'body' => $body
                        ]
                    );
                    $successCount++;
                } catch (Exception $innerEx) {
                    $failedCount++;
                }
            }

            if ($successCount > 0 && $failedCount == 0) {
                $message = "All reminders were sent successfully to $successCount patient(s).";
                $messageType = "success";
            } elseif ($successCount > 0 && $failedCount > 0) {
                $message = "Reminders sent to $successCount patient(s), but $failedCount failed.";
                $messageType = "warning";
            } else {
                $message = "No reminders were sent. Please check Twilio setup and patient phone numbers.";
                $messageType = "error";
            }

        } catch (Exception $e) {
            $message = "Twilio error: " . $e->getMessage();
            $messageType = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Send Reminders | MediCare</title>
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
            max-width: 760px;
            opacity: 0.95;
            line-height: 1.6;
        }

        .highlight-box {
            background: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 18px;
            padding: 18px;
            min-width: 260px;
        }

        .highlight-box h4 {
            margin-bottom: 8px;
            font-size: 15px;
        }

        .highlight-number {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .grid {
            display: grid;
            grid-template-columns: 0.9fr 1.1fr;
            gap: 22px;
        }

        .panel {
            background: var(--card);
            border-radius: 18px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            overflow: hidden;
        }

        .panel-header {
            padding: 20px 22px;
            border-bottom: 1px solid var(--border);
        }

        .panel-header h3 {
            font-size: 20px;
            margin-bottom: 4px;
        }

        .panel-header p {
            color: var(--muted);
            font-size: 14px;
        }

        .panel-body {
            padding: 20px 22px;
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

        .filter-form {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        .filter-form input[type="date"] {
            padding: 12px 14px;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: #fff;
            min-width: 220px;
        }

        .info-list {
            display: grid;
            gap: 14px;
        }

        .info-item {
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 16px;
            background: #fcfefe;
        }

        .info-item h4 {
            margin-bottom: 6px;
            font-size: 15px;
        }

        .info-item p {
            color: var(--muted);
            font-size: 13px;
            line-height: 1.5;
        }

        .info-item strong {
            color: var(--text);
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 700px;
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

        .phone {
            color: var(--muted);
            font-size: 13px;
        }

        .empty {
            text-align: center;
            color: var(--muted);
            padding: 30px 10px;
        }

        .send-box {
            margin-top: 18px;
            padding: 18px;
            border: 1px dashed var(--border);
            border-radius: 14px;
            background: #f8fffe;
        }

        .send-box p {
            color: var(--muted);
            margin-bottom: 12px;
            font-size: 14px;
            line-height: 1.6;
        }

        @media (max-width: 1100px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 900px) {
            .dashboard {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .content {
                padding: 18px;
            }

            .welcome h1 {
                font-size: 26px;
            }

            .hero-card h2 {
                font-size: 24px;
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
                <h3>Dr. <?php echo htmlspecialchars($doctorName); ?></h3>
                <p><?php echo htmlspecialchars($doctorSpecialization); ?></p>
            </div>

            <nav class="menu">
                <a href="dashboard.php">🏠 Dashboard</a>
                <a href="send_reminders.php" class="active">📩 Send Reminders</a>
                <a href="../logout.php">🚪 Logout</a>
            </nav>
        </aside>

        <main class="content">
            <div class="topbar">
                <div class="welcome">
                    <h1>Appointment Reminders</h1>
                    <p>Notify patients about upcoming approved appointments using SMS reminders.</p>
                </div>

                <div class="top-actions">
                    <a href="dashboard.php" class="btn btn-light">Back to Dashboard</a>
                    <a href="../logout.php" class="btn btn-primary">Logout</a>
                </div>
            </div>

            <section class="hero-card">
                <div>
                    <h2>Send reminders for <?php echo date("F d, Y", strtotime($selected_date)); ?></h2>
                    <p>
                        This page shows all approved appointments for the selected date under your account.
                        You can review the patient list and send reminder messages directly through Twilio.
                    </p>
                </div>

                <div class="highlight-box">
                    <h4>Approved Appointments</h4>
                    <div class="highlight-number"><?php echo count($appointments); ?></div>
                    <div>ready for reminder sending</div>
                </div>
            </section>

            <?php if ($message !== '') { ?>
                <div
                    class="alert <?php echo $messageType === 'success' ? 'alert-success' : ($messageType === 'warning' ? 'alert-warning' : 'alert-error'); ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php } ?>

            <section class="grid">
                <div class="panel">
                    <div class="panel-header">
                        <h3>Reminder Controls</h3>
                        <p>Choose a date and send reminders only for approved appointments.</p>
                    </div>

                    <div class="panel-body">
                        <form method="GET" class="filter-form">
                            <input type="date" name="date" value="<?php echo htmlspecialchars($selected_date); ?>">
                            <button type="submit" class="btn btn-light">Load Appointments</button>
                        </form>

                        <div class="info-list">
                            <div class="info-item">
                                <h4>Selected Date</h4>
                                <p><strong><?php echo date("F d, Y", strtotime($selected_date)); ?></strong></p>
                            </div>

                            <div class="info-item">
                                <h4>Reminder Scope</h4>
                                <p>Only <strong>your approved appointments</strong> for the selected date are included.
                                </p>
                            </div>

                            <div class="info-item">
                                <h4>Twilio Setup</h4>
                                <p>Update <strong>Account SID</strong>, <strong>Auth Token</strong>, and <strong>Twilio
                                        Number</strong> in this file before sending messages.</p>
                            </div>
                        </div>

                        <div class="send-box">
                            <p>
                                When you click the button below, MediCare will attempt to send an SMS reminder to every
                                approved patient shown in the appointment list.
                            </p>

                            <form method="POST">
                                <button type="submit" name="send_reminders" class="btn btn-primary">Send All
                                    Reminders</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-header">
                        <h3>Approved Patient List</h3>
                        <p>These patients will receive reminders for the selected date.</p>
                    </div>

                    <div class="panel-body">
                        <div class="table-wrap">
                            <table>
                                <tr>
                                    <th>#</th>
                                    <th>Patient</th>
                                    <th>Phone</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                </tr>

                                <?php if (!empty($appointments)) { ?>
                                    <?php foreach ($appointments as $index => $row) { ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td><?php echo htmlspecialchars($row['patient_name']); ?></td>
                                            <td>
                                                <div><?php echo htmlspecialchars($row['patient_phone']); ?></div>
                                                <div class="phone">SMS destination</div>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['appointment_date']); ?></td>
                                            <td><?php echo date("h:i A", strtotime($row['appointment_time'])); ?></td>
                                            <td><?php echo htmlspecialchars($row['status']); ?></td>
                                        </tr>
                                    <?php } ?>
                                <?php } else { ?>
                                    <tr>
                                        <td colspan="6">
                                            <div class="empty">No approved appointments found for this date.</div>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>
</body>

</html>