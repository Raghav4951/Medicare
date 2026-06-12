<?php
session_start();
include 'config.php';

$email = $_POST['email'];
$password = $_POST['password'];
$role = $_POST['role']; // from dropdown

if ($role == 'admin') {

  $sql = "SELECT * FROM admins WHERE email=?";
  $redirect = "admin/dashboard.php";

} elseif ($role == 'doctor') {

  $sql = "SELECT * FROM doctors WHERE email=?";
  $redirect = "doctors/dashboard.php";

} else {

  $sql = "SELECT * FROM patients WHERE email=?";
  $redirect = "patient/dashboard.php";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {

  $user = $result->fetch_assoc();

  if ($password == $user['password']) {

    session_regenerate_id(true);

    if ($role == 'admin') {
      $_SESSION['admin_id'] = $user['id'];
    } elseif ($role == 'doctor') {

      if ($user['doctor_status'] != 'active') {
        echo "Doctor not approved";
        exit();
      }

      $_SESSION['doctor_id'] = $user['id'];
    } else {
      $_SESSION['patient_id'] = $user['id'];
    }

    header("Location: $redirect");

  } else {
    echo "Invalid password";
  }

} else {
  echo "User not found";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>MediCare Secure Login</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    :root {
      --primary: #0d9488;
      --bg: #f8fafc;
      --card: #ffffff;
      --border: #e5e7eb;
      --text: #0f172a;
      --muted: #64748b;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: "Segoe UI", sans-serif;
    }

    body {
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      background: linear-gradient(135deg, #ecfeff, #f8fafc);
      padding: 20px;
    }

    .login-container {
      width: 900px;
      background: var(--card);
      display: grid;
      grid-template-columns: 1fr 1fr;
      border-radius: 18px;
      overflow: hidden;
      box-shadow: 0 20px 50px rgba(0, 0, 0, .08);
    }

    .left {
      padding: 40px;
    }

    .left h1 {
      color: var(--primary);
      font-size: 28px;
    }

    .left p {
      color: var(--muted);
      margin: 10px 0 30px;
    }

    .roles {
      display: flex;
      gap: 10px;
      margin-bottom: 24px;
    }

    .role-btn {
      flex: 1;
      padding: 12px;
      border: 1px solid var(--border);
      background: #f8fafc;
      border-radius: 10px;
      cursor: pointer;
      font-weight: 600;
    }

    .role-btn.active {
      background: var(--primary);
      color: #fff;
      border-color: var(--primary);
    }

    form {
      display: flex;
      flex-direction: column;
      gap: 16px;
    }

    input {
      padding: 14px;
      border-radius: 10px;
      border: 1px solid var(--border);
      font-size: 14px;
    }

    .login-btn {
      padding: 14px;
      border: none;
      border-radius: 10px;
      background: var(--primary);
      color: #fff;
      font-size: 15px;
      cursor: pointer;
    }

    .right {
      background: linear-gradient(160deg, #0d9488, #14b8a6);
      color: #fff;
      padding: 40px;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .register-link {
      text-align: center;
      font-size: 14px;
      margin-top: 12px;
      color: var(--muted);
    }

    .register-link a {
      color: var(--primary);
      text-decoration: none;
      font-weight: 600;
    }

    @media(max-width:900px) {
      .login-container {
        grid-template-columns: 1fr;
      }

      .right {
        display: none;
      }
    }
  </style>
</head>

<body>
  <div class="login-container">
    <div class="left">
      <h1>MediCare</h1>
      <p>Secure role-based login</p>

      <div class="roles">
        <button type="button" class="role-btn active" onclick="setRole('patient', this)">Patient</button>
        <button type="button" class="role-btn" onclick="setRole('doctor', this)">Doctor</button>
      </div>

      <form method="POST">
        <input type="hidden" name="role" id="roleInput" value="patient">
        <input type="email" name="email" id="emailInput" placeholder="Email Address" required>
        <input type="password" name="password" placeholder="Password" required>
        <button class="login-btn" name="login">Login</button>
      </form>

      <div class="register-link" id="patientRegisterLink">
        Don't have an account? <a href="patient/register-patient.php">Register as Patient</a>
      </div>

      <div class="register-link" id="doctorRequestLink" style="display:none;">
        Want to join as doctor? <a href="doctor-request.php">Request to Join</a>
      </div>
    </div>

    <div class="right">
      <h2>Healthcare Management Platform</h2>
      <p>Patients can register directly. Doctors join through admin approval for better trust and verification.</p>
    </div>
  </div>

  <script>
    function setRole(role, btn) {
      document.getElementById("roleInput").value = role;

      document.querySelectorAll(".role-btn").forEach(b => b.classList.remove("active"));
      btn.classList.add("active");

      const patientRegisterLink = document.getElementById("patientRegisterLink");
      const doctorRequestLink = document.getElementById("doctorRequestLink");
      const emailInput = document.getElementById("emailInput");

      emailInput.type = "email";
      emailInput.placeholder = "Email Address";

      if (role === "patient") {
        patientRegisterLink.style.display = "block";
        doctorRequestLink.style.display = "none";
      } else if (role === "doctor") {
        patientRegisterLink.style.display = "none";
        doctorRequestLink.style.display = "block";
      } else {
        patientRegisterLink.style.display = "none";
        doctorRequestLink.style.display = "none";
      }
    }

    window.onload = function () {
      const activeBtn = document.querySelector(".role-btn.active");
      setRole("patient", activeBtn);
    };
  </script>
</body>

</html>