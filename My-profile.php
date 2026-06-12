<?php
include '../db.php';
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] != "patient") {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";
$messageClass = "";

// -------------------- UPDATE PROFILE --------------------
if (isset($_POST['update_profile'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $Phone = trim($_POST['Phone'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $dob = trim($_POST['dob'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (empty($name) || empty($email)) {
        $message = "❌ Name and Email are required.";
        $messageClass = "error-msg";
    } else {
        $stmt = $conn->prepare("UPDATE patients SET name=?, email=?, Phone=?, gender=?, dob=?, address=? WHERE id=?");
        $stmt->bind_param("ssssssi", $name, $email, $Phone, $gender, $dob, $address, $user_id);

        if ($stmt->execute()) {
            $message = "✅ Profile updated successfully.";
            $messageClass = "success-msg";
        } else {
            $message = "❌ Failed to update profile.";
            $messageClass = "error-msg";
        }
    }
}

// -------------------- ADD FAMILY MEMBER --------------------
if (isset($_POST['add_family_member'])) {
    $fm_name = trim($_POST['fm_name'] ?? '');
    $fm_relation = trim($_POST['fm_relation'] ?? '');
    $fm_age = trim($_POST['fm_age'] ?? '');
    $fm_gender = trim($_POST['fm_gender'] ?? '');

    if (empty($fm_name) || empty($fm_relation)) {
        $message = "❌ Family member name and relation are required.";
        $messageClass = "error-msg";
    } else {
        $stmt = $conn->prepare("INSERT INTO family_members (patient_id, name, relation, age, gender) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issis", $user_id, $fm_name, $fm_relation, $fm_age, $fm_gender);

        if ($stmt->execute()) {
            $message = "✅ Family member added successfully.";
            $messageClass = "success-msg";
        } else {
            $message = "❌ Failed to add family member.";
            $messageClass = "error-msg";
        }
    }
}

// -------------------- DELETE FAMILY MEMBER --------------------
if (isset($_GET['delete_family_id'])) {
    $family_id = (int) $_GET['delete_family_id'];

    $stmt = $conn->prepare("DELETE FROM family_members WHERE id=? AND patient_id=?");
    $stmt->bind_param("ii", $family_id, $user_id);
    $stmt->execute();

    header("Location: my-profile.php");
    exit();
}

// -------------------- FETCH PATIENT DATA --------------------
$query = $conn->prepare("SELECT * FROM patients WHERE id=?");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die("Patient not found");
}

// -------------------- FETCH FAMILY MEMBERS --------------------
$familyQuery = $conn->prepare("SELECT * FROM family_members WHERE patient_id=? ORDER BY id DESC");
$familyQuery->bind_param("i", $user_id);
$familyQuery->execute();
$familyResult = $familyQuery->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>MediCare+ | My Profile</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        :root {
            --primary: #0d9488;
            --bg: #f8fafc;
            --card: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
            --danger: #ef4444;
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

        .profile-top {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .avatar {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0d9488, #14b8a6);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 34px;
            font-weight: bold;
            box-shadow: 0 8px 18px rgba(13, 148, 136, 0.25);
        }

        .profile-info h2 {
            margin: 0 0 5px 0;
            color: var(--text);
        }

        .profile-info p {
            margin: 0;
            color: var(--muted);
            font-size: 14px;
        }

        .profile-grid,
        .form-grid,
        .family-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 15px;
        }

        .info-box {
            background: #f1f5f9;
            border-radius: 14px;
            padding: 14px;
        }

        .info-box label {
            display: block;
            font-size: 13px;
            color: var(--muted);
            margin-bottom: 6px;
        }

        .info-box div {
            font-size: 16px;
            font-weight: 600;
            color: var(--text);
            word-break: break-word;
        }

        .badge {
            display: inline-block;
            padding: 8px 14px;
            background: #dcfce7;
            color: #166534;
            font-weight: 600;
            font-size: 13px;
            border-radius: 999px;
        }

        .section-title {
            margin: 0 0 15px 0;
            color: var(--text);
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

        .action-btn {
            display: inline-block;
            text-decoration: none;
            background: var(--primary);
            color: white;
            padding: 12px 18px;
            border-radius: 12px;
            font-weight: 600;
            transition: 0.3s;
            margin-top: 10px;
            border: none;
            cursor: pointer;
        }

        .action-btn:hover {
            background: #0f766e;
        }

        .danger-btn {
            background: #ef4444;
        }

        .danger-btn:hover {
            background: #dc2626;
        }

        .note {
            font-size: 13px;
            color: var(--muted);
            margin-top: 8px;
        }

        .alert {
            padding: 14px;
            border-radius: 12px;
            margin-bottom: 16px;
            font-weight: 600;
        }

        .success-msg {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .error-msg {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .input-group {
            margin-bottom: 15px;
        }

        .input-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 14px;
            font-weight: 600;
            color: var(--text);
        }

        .input-group input,
        .input-group select,
        .input-group textarea {
            width: 100%;
            padding: 12px;
            border-radius: 12px;
            border: 1px solid #dbe2ea;
            background: #f8fafc;
            box-sizing: border-box;
            font-size: 14px;
        }

        .input-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .hidden {
            display: none;
        }

        .family-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            align-items: start;
        }

        .family-left,
        .family-right {
            background: #f8fafc;
            border-radius: 16px;
            padding: 18px;
            border: 1px solid #e2e8f0;
        }

        .family-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .family-card {
            background: #f1f5f9;
            padding: 15px;
            border-radius: 14px;
            border-left: 4px solid var(--primary);
        }

        .family-card h4 {
            margin: 0 0 8px 0;
            color: var(--text);
        }

        .family-card p {
            margin: 4px 0;
            color: var(--muted);
            font-size: 14px;
        }

        @media(max-width: 768px) {
            .family-layout {
                grid-template-columns: 1fr;
            }
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
                <li><a href="my-profile.php">🙍 My Profile</a></li>
                <li><a href="#" onclick="logout()">🚪 Logout</a></li>
            </ul>
        </div>

        <div class="header">
            <h2>My Profile</h2>
            <p>View and manage your profile and family members</p>
        </div>

        <div class="container">

            <?php if ($message): ?>
                <div class="alert <?php echo $messageClass; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="profile-top">
                    <div class="avatar">
                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                    </div>

                    <div class="profile-info">
                        <h2><?php echo htmlspecialchars($user['name']); ?></h2>
                        <p><?php echo htmlspecialchars($user['email']); ?></p>
                        <div style="margin-top: 12px;">
                            <span class="badge">Patient Account</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <h3 class="section-title">Profile Details</h3>

                <div class="profile-grid">
                    <div class="info-box">
                        <label>Full Name</label>
                        <div><?php echo !empty($user['name']) ? htmlspecialchars($user['name']) : 'Not Available'; ?>
                        </div>
                    </div>

                    <div class="info-box">
                        <label>Email Address</label>
                        <div><?php echo !empty($user['email']) ? htmlspecialchars($user['email']) : 'Not Available'; ?>
                        </div>
                    </div>

                    <div class="info-box">
                        <label>Phone Number</label>
                        <div><?php echo !empty($user['Phone']) ? htmlspecialchars($user['Phone']) : 'Not Available'; ?>
                        </div>
                    </div>

                    <div class="info-box">
                        <label>Gender</label>
                        <div>
                            <?php echo !empty($user['gender']) ? htmlspecialchars($user['gender']) : 'Not Available'; ?>
                        </div>
                    </div>

                    <div class="info-box">
                        <label>Date of Birth</label>
                        <div><?php echo !empty($user['dob']) ? htmlspecialchars($user['dob']) : 'Not Available'; ?>
                        </div>
                    </div>

                    <div class="info-box">
                        <label>Address</label>
                        <div>
                            <?php echo !empty($user['address']) ? htmlspecialchars($user['address']) : 'Not Available'; ?>
                        </div>
                    </div>
                </div>

                <button type="button" class="action-btn" onclick="toggleEditProfile()">
                    Edit Profile
                </button>
            </div>

            <div class="card hidden" id="editProfileBox">
                <h3 class="section-title">Edit Profile</h3>

                <form method="POST">
                    <div class="form-grid">
                        <div class="input-group">
                            <label>Full Name</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>"
                                required>
                        </div>

                        <div class="input-group">
                            <label>Email Address</label>
                            <input type="email" name="email"
                                value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                        </div>

                        <div class="input-group">
                            <label>Phone Number</label>
                            <input type="text" name="Phone"
                                value="<?php echo htmlspecialchars($user['Phone'] ?? ''); ?>">
                        </div>

                        <div class="input-group">
                            <label>Gender</label>
                            <select name="gender">
                                <option value="">Select Gender</option>
                                <option value="Male" <?php if (($user['gender'] ?? '') == 'Male')
                                    echo 'selected'; ?>>Male
                                </option>
                                <option value="Female" <?php if (($user['gender'] ?? '') == 'Female')
                                    echo 'selected'; ?>>
                                    Female</option>
                                <option value="Other" <?php if (($user['gender'] ?? '') == 'Other')
                                    echo 'selected'; ?>>
                                    Other</option>
                            </select>
                        </div>

                        <div class="input-group">
                            <label>Date of Birth</label>
                            <input type="date" name="dob" value="<?php echo htmlspecialchars($user['dob'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Address</label>
                        <textarea name="address"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                    </div>

                    <button type="submit" name="update_profile" class="action-btn">Save Changes</button>
                    <button type="button" class="action-btn" onclick="toggleEditProfile()">Cancel</button>
                </form>
            </div>

            <div class="card">
                <h3 class="section-title">Family Members</h3>

                <div class="family-layout">

                    <!-- LEFT SIDE: ADD FAMILY MEMBER -->
                    <div class="family-left">
                        <h4 style="margin-top: 0; color: var(--text);">Add Family Member</h4>

                        <form method="POST">
                            <div class="input-group">
                                <label>Full Name</label>
                                <input type="text" name="fm_name" required>
                            </div>

                            <div class="input-group">
                                <label>Relation</label>
                                <input type="text" name="fm_relation" placeholder="Father / Mother / Wife / Son"
                                    required>
                            </div>

                            <div class="input-group">
                                <label>Age</label>
                                <input type="number" name="fm_age" min="0">
                            </div>

                            <div class="input-group">
                                <label>Gender</label>
                                <select name="fm_gender">
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>

                            <button type="submit" name="add_family_member" class="action-btn">Save Family
                                Member</button>
                        </form>
                    </div>

                    <!-- RIGHT SIDE: SHOW FAMILY MEMBERS -->
                    <div class="family-right">
                        <h4 style="margin-top: 0; color: var(--text);">Your Family Members</h4>

                        <?php if ($familyResult->num_rows > 0): ?>
                            <div class="family-list">
                                <?php while ($family = $familyResult->fetch_assoc()): ?>
                                    <div class="family-card">
                                        <h4><?php echo htmlspecialchars($family['name']); ?></h4>
                                        <p><strong>Relation:</strong> <?php echo htmlspecialchars($family['relation']); ?></p>
                                        <p><strong>Age:</strong>
                                            <?php echo !empty($family['age']) ? htmlspecialchars($family['age']) : 'Not Available'; ?>
                                        </p>
                                        <p><strong>Gender:</strong>
                                            <?php echo !empty($family['gender']) ? htmlspecialchars($family['gender']) : 'Not Available'; ?>
                                        </p>

                                        <a href="my-profile.php?delete_family_id=<?php echo $family['id']; ?>"
                                            class="action-btn danger-btn"
                                            onclick="return confirm('Delete this family member?')">
                                            Delete
                                        </a>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <p class="note">No family members added yet.</p>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
            <div class="card hidden" id="addFamilyBox">
                <h3 class="section-title">Add Family Member</h3>

                <form method="POST">
                    <div class="form-grid">
                        <div class="input-group">
                            <label>Full Name</label>
                            <input type="text" name="fm_name" required>
                        </div>

                        <div class="input-group">
                            <label>Relation</label>
                            <input type="text" name="fm_relation" placeholder="Father / Mother / Wife / Son" required>
                        </div>

                        <div class="input-group">
                            <label>Age</label>
                            <input type="number" name="fm_age" min="0">
                        </div>

                        <div class="input-group">
                            <label>Gender</label>
                            <select name="fm_gender">
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" name="add_family_member" class="action-btn">Save Family Member</button>
                    <button type="button" class="action-btn" onclick="toggleAddFamily()">Cancel</button>
                </form>
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

        function logout() {
            window.location.href = "../login.php";
        }

        function toggleEditProfile() {
            document.getElementById("editProfileBox").classList.toggle("hidden");
        }

        function toggleAddFamily() {
            document.getElementById("addFamilyBox").classList.toggle("hidden");
        }
    </script>

</body>

</html>