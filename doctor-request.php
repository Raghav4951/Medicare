<?php
include 'db.php';

$message = '';
$messageType = '';

if (isset($_POST['submit_request'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $passwordRaw = $_POST['password'];
    $specialization = trim($_POST['specialization']);
    $phone = trim($_POST['phone']);
    $experience = trim($_POST['experience']);
    $requestMessage = trim($_POST['message']);

    if ($name === '' || $email === '' || $passwordRaw === '' || $specialization === '') {
        $message = "Please fill all required fields.";
        $messageType = "error";
    } else {
        $checkDoctor = $conn->prepare("SELECT id FROM doctors WHERE email = ?");
        $checkDoctor->bind_param("s", $email);
        $checkDoctor->execute();
        $doctorResult = $checkDoctor->get_result();

        $checkRequest = $conn->prepare("SELECT id, status FROM doctor_requests WHERE email = ?");
        $checkRequest->bind_param("s", $email);
        $checkRequest->execute();
        $requestResult = $checkRequest->get_result();

        if ($doctorResult->num_rows > 0) {
            $message = "A doctor account with this email already exists.";
            $messageType = "error";
        } elseif ($requestResult->num_rows > 0) {
            $existing = $requestResult->fetch_assoc();
            $message = "A doctor join request already exists for this email. Current status: " . $existing['status'];
            $messageType = "error";
        } else {
            $hashedPassword = password_hash($passwordRaw, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO doctor_requests (name, email, password, specialization, phone, experience, message, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')");
            $stmt->bind_param("sssssss", $name, $email, $hashedPassword, $specialization, $phone, $experience, $requestMessage);

            if ($stmt->execute()) {
                $message = "Your request has been submitted successfully. Admin will review it soon.";
                $messageType = "success";
            } else {
                $message = "Something went wrong while submitting your request.";
                $messageType = "error";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Request to Join as Doctor | MediCare</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        :root {
            --primary: #0d9488;
            --bg: #f8fafc;
            --card: #ffffff;
            --border: #e5e7eb;
            --text: #0f172a;
            --muted: #64748b;
            --success-bg: #dcfce7;
            --success-text: #166534;
            --error-bg: #fee2e2;
            --error-text: #991b1b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Segoe UI", sans-serif;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #ecfeff, #f8fafc);
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 30px 15px;
        }

        .container {
            width: 100%;
            max-width: 1000px;
            background: var(--card);
            display: grid;
            grid-template-columns: 1.1fr 1fr;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 50px rgba(0, 0, 0, .08);
        }

        .left {
            padding: 40px;
        }

        .left h1 {
            color: var(--primary);
            margin-bottom: 8px;
        }

        .left p {
            color: var(--muted);
            margin-bottom: 25px;
        }

        form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .full {
            grid-column: 1 / -1;
        }

        input,
        textarea {
            width: 100%;
            padding: 14px;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 14px;
        }

        textarea {
            min-height: 110px;
            resize: vertical;
        }

        input:focus,
        textarea:focus {
            outline: none;
            border-color: var(--primary);
        }

        .btn {
            grid-column: 1 / -1;
            padding: 14px;
            border: none;
            border-radius: 10px;
            background: var(--primary);
            color: white;
            font-size: 15px;
            cursor: pointer;
        }

        .btn:hover {
            background: #0f766e;
        }

        .notice {
            grid-column: 1 / -1;
            padding: 12px 14px;
            border-radius: 10px;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .success {
            background: var(--success-bg);
            color: var(--success-text);
        }

        .error {
            background: var(--error-bg);
            color: var(--error-text);
        }

        .bottom-link {
            margin-top: 18px;
            text-align: center;
            color: var(--muted);
            font-size: 14px;
        }

        .bottom-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        .right {
            background: linear-gradient(160deg, #0d9488, #14b8a6);
            color: white;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .right h2 {
            margin-bottom: 12px;
            font-size: 28px;
        }

        .right ul {
            margin-top: 18px;
            padding-left: 20px;
            line-height: 1.9;
        }

        @media(max-width: 900px) {
            .container {
                grid-template-columns: 1fr;
            }

            .right {
                display: none;
            }

            form {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="left">
            <h1>MediCare</h1>
            <p>Request to join our doctor platform</p>

            <form action="save_doctor.php" method="POST" enctype="multipart/form-data">
                <?php if ($message !== '') { ?>
                    <div class="notice <?php echo $messageType; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php } ?>
                <input type="text" name="name" placeholder="Full Name" required>
                <input type="email" name="email" placeholder="Email Address" required>
                <input type="text" name="phone" placeholder="Phone Number" required>
                <input type="text" name="specialization" placeholder="Specialization" required>
                <input type="text" name="license_number" placeholder="Medical License Number" required>
                <input type="text" name="medical_council" placeholder="Medical Council Name" required>
                <input type="number" name="experience" placeholder="Years of Experience" required>
                <input type="text" name="hospital_name" placeholder="Hospital/Clinic Name" required>

                <label>Upload Profile Photo</label>
                <input type="file" name="profile_photo" required>

                <label>Upload ID Proof</label>
                <input type="file" name="id_proof" required>

                <label>Upload Degree Certificate</label>
                <input type="file" name="degree_certificate" required>

                <input type="password" name="password" placeholder="Create Password" required>

                <button type="submit">Apply for Verification</button>
            </form>

            <div class="bottom-link">
                Already approved? <a href="login.php">Login here</a>
            </div>
        </div>

        <div class="right">
            <h2>Join MediCare as a Doctor</h2>
            <p>Submit your request and our admin team will review your profile before activation.</p>

            <ul>
                <li>Verified onboarding flow</li>
                <li>Admin approval before listing</li>
                <li>More trust for patients</li>
                <li>Safer platform management</li>
            </ul>
        </div>
    </div>
</body>

</html>