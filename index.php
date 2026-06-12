<?php
include 'db.php';

/*
|--------------------------------------------------------------------------
| Fetch Services from Doctors Table
|--------------------------------------------------------------------------
| Since your DB already has a doctors table and specialization field,
| we can show services using DISTINCT specializations.
*/
$services = [];
$servicesQuery = "SELECT DISTINCT specialization 
                  FROM doctors 
                  WHERE specialization IS NOT NULL 
                  AND specialization != '' 
                  ORDER BY specialization ASC 
                  LIMIT 8";
$servicesResult = mysqli_query($conn, $servicesQuery);

if ($servicesResult) {
    while ($row = mysqli_fetch_assoc($servicesResult)) {
        $services[] = $row['specialization'];
    }
}

/*
|--------------------------------------------------------------------------
| Fetch Doctors
|--------------------------------------------------------------------------
| If status column exists and stores Active/Inactive, this query will show
| active doctors first. If some rows are empty, it still works.
*/
$doctors = [];
$doctorsQuery = "SELECT id, name, specialization, status 
                 FROM doctors 
                 ORDER BY 
                    CASE 
                        WHEN status = 'Active' THEN 0 
                        ELSE 1 
                    END,
                    id DESC
                 LIMIT 8";
$doctorsResult = mysqli_query($conn, $doctorsQuery);

if ($doctorsResult) {
    while ($row = mysqli_fetch_assoc($doctorsResult)) {
        $doctors[] = $row;
    }
}

/*
|--------------------------------------------------------------------------
| Helper function for doctor avatar initials
|--------------------------------------------------------------------------
*/
function getInitials($name) {
    $name = trim($name);
    if ($name === '') return 'DR';

    $parts = preg_split('/\s+/', $name);
    $initials = '';

    foreach ($parts as $part) {
        if ($part !== '') {
            $initials .= strtoupper(substr($part, 0, 1));
        }
        if (strlen($initials) >= 2) break;
    }

    return $initials ?: 'DR';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Medicare</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Google Font -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Poppins', sans-serif;
      scroll-behavior: smooth;
    }

    body {
      background: #f9fbfd;
      color: #333;
    }

    a {
      text-decoration: none;
    }

    /* NAVBAR */
    nav {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 18px 50px;
      background: #fff;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
      position: sticky;
      top: 0;
      z-index: 1000;
    }

    nav h1 {
      color: #0a74da;
      font-size: 28px;
      font-weight: 700;
    }

    .nav-links {
      display: flex;
      align-items: center;
      gap: 18px;
      flex-wrap: wrap;
    }

    nav a {
      color: #333;
      font-size: 15px;
      font-weight: 500;
    }

    nav a:hover {
      color: #0a74da;
    }

    .nav-btn {
      background: #0a74da;
      color: #fff;
      padding: 10px 16px;
      border-radius: 8px;
    }

    .nav-btn:hover {
      color: #fff;
      background: #085fb0;
    }

    /* HERO */
    .hero {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 30px;
      padding: 70px 50px;
      flex-wrap: wrap;
    }

    .hero-text {
      max-width: 560px;
    }

    .hero-text .tag {
      display: inline-block;
      background: #eaf4ff;
      color: #0a74da;
      padding: 8px 14px;
      border-radius: 999px;
      font-size: 13px;
      font-weight: 600;
      margin-bottom: 18px;
    }

    .hero h2 {
      font-size: 44px;
      line-height: 1.2;
      margin-bottom: 18px;
      color: #0f172a;
    }

    .hero p {
      font-size: 16px;
      color: #64748b;
      margin-bottom: 25px;
    }

    .hero-actions {
      display: flex;
      gap: 14px;
      flex-wrap: wrap;
    }

    .btn {
      background: #0a74da;
      color: white;
      padding: 12px 22px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      display: inline-block;
      transition: 0.3s;
      font-size: 14px;
      font-weight: 600;
    }

    .btn:hover {
      background: #085fb0;
    }

    .btn-outline {
      background: #fff;
      color: #0a74da;
      border: 1px solid #0a74da;
    }

    .btn-outline:hover {
      background: #0a74da;
      color: #fff;
    }

    .hero-image {
      flex: 1;
      min-width: 260px;
      text-align: center;
    }

    .hero-image img {
      width: 100%;
      max-width: 360px;
    }

    /* STATS */
    .stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 20px;
      padding: 0 50px 60px;
    }

    .stat-box {
      background: #fff;
      padding: 24px;
      border-radius: 16px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
      text-align: center;
    }

    .stat-box h3 {
      font-size: 28px;
      color: #0a74da;
      margin-bottom: 8px;
    }

    .stat-box p {
      color: #64748b;
      font-size: 14px;
    }

    /* SECTION */
    .section {
      padding: 70px 50px;
      text-align: center;
    }

    .section h2 {
      margin-bottom: 14px;
      font-size: 34px;
      color: #0f172a;
    }

    .section-subtitle {
      max-width: 700px;
      margin: 0 auto 40px;
      color: #64748b;
      font-size: 15px;
    }

    /* SERVICES */
    .services {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 25px;
    }

    .service {
      background: white;
      padding: 28px 22px;
      border-radius: 14px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
      transition: 0.3s;
    }

    .service:hover {
      transform: translateY(-5px);
    }

    .service-icon {
      width: 54px;
      height: 54px;
      border-radius: 50%;
      margin: 0 auto 16px;
      display: flex;
      justify-content: center;
      align-items: center;
      background: #eaf4ff;
      color: #0a74da;
      font-size: 22px;
      font-weight: 700;
    }

    .service h3 {
      font-size: 18px;
      margin-bottom: 8px;
    }

    .service p {
      color: #64748b;
      font-size: 14px;
    }

    /* DOCTORS */
    .doctors {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
      gap: 30px;
    }

    .card {
      background: white;
      padding: 24px 20px;
      border-radius: 14px;
      text-align: center;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
      transition: 0.3s;
    }

    .card:hover {
      transform: translateY(-5px);
    }

    .doctor-avatar {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      margin: 0 auto 16px;
      background: linear-gradient(135deg, #0a74da, #38bdf8);
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 30px;
      font-weight: 700;
      letter-spacing: 1px;
    }

    .card h3 {
      margin-bottom: 6px;
      font-size: 20px;
      color: #0f172a;
    }

    .card p {
      color: gray;
      margin-bottom: 10px;
      font-size: 14px;
    }

    .status-badge {
      display: inline-block;
      margin-bottom: 14px;
      padding: 6px 12px;
      border-radius: 999px;
      font-size: 12px;
      font-weight: 600;
    }

    .status-active {
      background: #dcfce7;
      color: #15803d;
    }

    .status-inactive {
      background: #fee2e2;
      color: #b91c1c;
    }

    /* APPOINTMENT */
    .appointment {
      background: linear-gradient(135deg, #0a74da, #1d4ed8);
      color: white;
      padding: 70px 20px;
      text-align: center;
      margin-top: 20px;
    }

    .appointment h2 {
      font-size: 34px;
      margin-bottom: 10px;
    }

    .appointment p {
      max-width: 650px;
      margin: 0 auto;
      opacity: 0.95;
    }

    .appointment .btn-light {
      background: white;
      color: #0a74da;
      margin-top: 22px;
      padding: 12px 22px;
      border-radius: 8px;
      display: inline-block;
      font-weight: 600;
    }

    .appointment .btn-light:hover {
      background: #eaf4ff;
    }

    /* FOOTER */
    footer {
      background: #111;
      color: white;
      text-align: center;
      padding: 22px;
      font-size: 14px;
    }

    /* EMPTY MESSAGE */
    .empty-box {
      background: #fff;
      border-radius: 14px;
      padding: 28px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
      color: #64748b;
    }

    /* RESPONSIVE */
    @media(max-width: 900px) {
      nav {
        padding: 16px 20px;
        flex-direction: column;
        gap: 14px;
      }

      .hero,
      .section,
      .stats {
        padding-left: 20px;
        padding-right: 20px;
      }

      .hero {
        flex-direction: column;
        text-align: center;
        padding-top: 50px;
      }

      .hero-text {
        max-width: 100%;
      }

      .hero-actions {
        justify-content: center;
      }

      .section h2 {
        font-size: 28px;
      }

      .hero h2 {
        font-size: 34px;
      }
    }
  </style>
</head>

<body>

  <!-- NAVBAR -->
  <nav>
    <h1>Medicare</h1>

    <div class="nav-links">
      <a href="#home">Home</a>
      <a href="#services">Services</a>
      <a href="#doctors">Doctors</a>
      <a href="about.php">About</a>
      <a href="contact.php">Contact</a>
      <a href="login.php" class="nav-btn">Login</a>
    </div>
  </nav>

  <!-- HERO -->
  <section class="hero" id="home">
    <div class="hero-text">
      <span class="tag">Trusted Healthcare Platform</span>
      <h2>Your Health, Our Priority</h2>
      <p>
        Book appointments with qualified doctors anytime, anywhere.
        Explore specialists, manage consultations, and access better healthcare
        through one simple platform.
      </p>

      <div class="hero-actions">
        <a href="login.php" class="btn">Book Appointment</a>
        <a href="#doctors" class="btn btn-outline">View Doctors</a>
      </div>
    </div>

    <div class="hero-image">
      <img src="https://cdn-icons-png.flaticon.com/512/2966/2966487.png" alt="Healthcare Illustration">
    </div>
  </section>

  <!-- STATS -->
  <section class="stats">
    <div class="stat-box">
      <h3>
        <?php echo count($doctors); ?>+
      </h3>
      <p>Doctors Listed</p>
    </div>

    <div class="stat-box">
      <h3>
        <?php echo count($services); ?>+
      </h3>
      <p>Specialized Services</p>
    </div>

    <div class="stat-box">
      <h3>24/7</h3>
      <p>Easy Access</p>
    </div>

    <div class="stat-box">
      <h3>100%</h3>
      <p>Patient Focused</p>
    </div>
  </section>

  <!-- SERVICES -->
  <section class="section" id="services">
    <h2>Our Services</h2>
    <p class="section-subtitle">
      We connect patients with trusted medical specialists across different areas of healthcare.
    </p>

    <?php if (!empty($services)) { ?>
    <div class="services">
      <?php foreach ($services as $service) { ?>
      <div class="service">
        <div class="service-icon">+</div>
        <h3>
          <?php echo htmlspecialchars($service); ?>
        </h3>
        <p>
          Expert consultation and healthcare support in
          <?php echo htmlspecialchars($service); ?>.
        </p>
      </div>
      <?php } ?>
    </div>
    <?php } else { ?>
    <div class="empty-box">
      No services available right now.
    </div>
    <?php } ?>
  </section>

  <!-- DOCTORS -->
  <section class="section" id="doctors">
    <h2>Our Expert Doctors</h2>
    <p class="section-subtitle">
      Meet our experienced healthcare professionals and choose the right specialist for your care.
    </p>

    <?php if (!empty($doctors)) { ?>
    <div class="doctors">
      <?php foreach ($doctors as $doctor) { 
                    $doctorName = trim($doctor['name']);
                    $specialization = trim($doctor['specialization']);
                    $status = isset($doctor['status']) && trim($doctor['status']) !== '' ? trim($doctor['status']) : 'Active';

                    $badgeClass = strtolower($status) === 'active' ? 'status-active' : 'status-inactive';
                ?>
      <div class="card">
        <div class="doctor-avatar">
          <?php echo htmlspecialchars(getInitials($doctorName)); ?>
        </div>

        <h3>
          Dr.
          <?php echo htmlspecialchars($doctorName); ?>
        </h3>

        <p>
          <?php echo htmlspecialchars($specialization !== '' ? $specialization : 'General Specialist'); ?>
        </p>

        <span class="status-badge <?php echo $badgeClass; ?>">
          <?php echo htmlspecialchars($status); ?>
        </span>
        <br>

        <a href="login.php" class="btn">Book</a>
      </div>
      <?php } ?>
    </div>
    <?php } else { ?>
    <div class="empty-box">
      No doctors available right now.
    </div>
    <?php } ?>
  </section>

  <!-- APPOINTMENT -->
  <section class="appointment">
    <h2>Book An Appointment</h2>
    <p>
      Quick and easy scheduling with our doctors. Log in as a patient and choose the doctor that best fits your needs.
    </p>
    <a href="login.php" class="btn-light">Get Appointment</a>
  </section>

  <!-- FOOTER -->
  <footer>
    <p>© 2026 Medicare. All rights reserved.</p>
  </footer>

</body>

</html>