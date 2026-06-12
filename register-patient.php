<?php
include("../config.php");

if (isset($_POST['register'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $password_raw = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $status = 1;

    if ($password_raw !== $confirm_password) {
        echo "<script>alert('Password and Confirm Password do not match');</script>";
    } else {
        $check = mysqli_query($conn, "SELECT * FROM patients WHERE email='$email'");

        if (mysqli_num_rows($check) > 0) {
            echo "<script>alert('Email already registered');</script>";
        } else {
            $password = password_hash($password_raw, PASSWORD_DEFAULT);

            $query = "INSERT INTO patients (name, email, password, Phone, status) 
                      VALUES ('$name', '$email', '$password', '$phone', '$status')";

            if (mysqli_query($conn, $query)) {
                echo "<script>alert('Registration Successful'); window.location='../login.php';</script>";
            } else {
                echo "<script>alert('Something went wrong. Please try again.');</script>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Patient Register</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        :root {
            --primary: #0d9488;
            --primary-dark: #0f766e;
            --bg: #f1f5f9;
            --card: #ffffff;
            --border: #e2e8f0;
            --text: #0f172a;
            --muted: #64748b;
            --shadow: 0 20px 45px rgba(15, 23, 42, 0.08);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Segoe UI", sans-serif;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #ccfbf1, #f8fafc);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 15px;
        }

        .register-wrapper {
            width: 100%;
            max-width: 1050px;
            display: grid;
            grid-template-columns: 1.05fr 0.95fr;
            background: var(--card);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .form-side {
            padding: 40px;
            background: #fff;
        }

        .top-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 28px;
        }

        .brand {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary);
        }

        .login-mini a {
            text-decoration: none;
            color: var(--primary);
            font-weight: 600;
            font-size: 14px;
        }

        .profile-card {
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 28px;
            background: #ffffff;
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 18px;
            padding-bottom: 22px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 24px;
        }

        .profile-avatar {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), #14b8a6);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .profile-info h2 {
            font-size: 24px;
            color: var(--text);
            margin-bottom: 6px;
        }

        .profile-info p {
            color: var(--muted);
            font-size: 14px;
            line-height: 1.5;
        }

        .section-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 16px;
        }

        form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        label {
            font-size: 14px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 8px;
        }

        input {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid var(--border);
            border-radius: 12px;
            font-size: 14px;
            color: var(--text);
            background: #f8fafc;
            transition: 0.25s ease;
        }

        input:focus {
            outline: none;
            border-color: var(--primary);
            background: #fff;
            box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.12);
        }

        .btn-wrap {
            grid-column: 1 / -1;
            display: flex;
            gap: 12px;
            margin-top: 8px;
        }

        .btn {
            padding: 14px 22px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            transition: 0.25s ease;
        }

        .btn-primary {
            background: var(--primary);
            color: #fff;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-secondary {
            background: #e2e8f0;
            color: var(--text);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-secondary:hover {
            background: #cbd5e1;
        }

        .bottom-text {
            margin-top: 18px;
            text-align: center;
            color: var(--muted);
            font-size: 14px;
        }

        .bottom-text a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        .info-side {
            background: linear-gradient(135deg, #0f766e, #14b8a6);
            color: white;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
        }

        .info-side::before,
        .info-side::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.08);
        }

        .info-side::before {
            width: 220px;
            height: 220px;
            top: -60px;
            right: -60px;
        }

        .info-side::after {
            width: 160px;
            height: 160px;
            bottom: -40px;
            left: -40px;
        }

        .info-content {
            position: relative;
            z-index: 1;
        }

        .info-content h2 {
            font-size: 32px;
            margin-bottom: 14px;
            line-height: 1.2;
        }

        .info-content p {
            font-size: 15px;
            line-height: 1.8;
            opacity: 0.95;
            margin-bottom: 24px;
        }

        .feature-box {
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 16px;
            padding: 16px;
            margin-bottom: 14px;
            backdrop-filter: blur(4px);
        }

        .feature-box h4 {
            font-size: 16px;
            margin-bottom: 6px;
        }

        .feature-box span {
            font-size: 14px;
            opacity: 0.92;
            line-height: 1.6;
            display: block;
        }

        @media (max-width: 900px) {
            .register-wrapper {
                grid-template-columns: 1fr;
            }

            .info-side {
                display: none;
            }

            .form-side {
                padding: 24px;
            }

            .profile-card {
                padding: 20px;
            }

            form {
                grid-template-columns: 1fr;
            }

            .btn-wrap {
                flex-direction: column;
            }

            .profile-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>

<body>

    <div class="register-wrapper">

        <div class="form-side">
            <div class="top-bar">
                <div class="brand">MediCare</div>
                <div class="login-mini">
                    <a href="../login.php">Login</a>
                </div>
            </div>

            <div class="profile-card">
                <div class="profile-header">
                    <div class="profile-avatar">P</div>
                    <div class="profile-info">
                        <h2>Patient Registration</h2>
                        <p>Create your account to manage appointments, view your profile, and connect with doctors easily.</p>
                    </div>
                </div>

                <div class="section-title">Personal Information</div>

                <form method="POST">
                    <div class="form-group full">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" placeholder="Enter your full name" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="text" id="phone" name="phone" placeholder="Enter your phone number" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Create password" required>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm password" required>
                    </div>

                    <div class="btn-wrap">
                        <button type="submit" name="register" class="btn btn-primary">Create Account</button>
                        <a href="../login.php" class="btn btn-secondary">Back to Login</a>
                    </div>
                </form>

                <div class="bottom-text">
                    Already have an account?
                    <a href="../login.php">Login here</a>
                </div>
            </div>
        </div>

        <div class="info-side">
            <div class="info-content">
                <h2>Welcome to MediCare</h2>
                <p>
                    Your health platform to book appointments, manage personal details, and stay connected with trusted doctors.
                </p>

                <div class="feature-box">
                    <h4>Easy Registration</h4>
                    <span>Create your patient account in just a few steps with a clean and simple profile-style form.</span>
                </div>

                <div class="feature-box">
                    <h4>Manage Profile</h4>
                    <span>Keep your personal details updated and make your account ready for future appointments.</span>
                </div>

                <div class="feature-box">
                    <h4>Doctor Connection</h4>
                    <span>Find available doctors and manage your healthcare journey from one place.</span>
                </div>
            </div>
        </div>

    </div>

</body>

</html>