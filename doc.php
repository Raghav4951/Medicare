<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>MediCare | Doctor Appointment</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  font-family: "Segoe UI", sans-serif;
}

body {
  background: #f8fbff;
  color: #111827;
}

/* NAVBAR */
header {
  background: #ffffff;
  padding: 15px 60px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.logo {
  font-size: 22px;
  font-weight: 700;
  color: #2563eb;
}

nav a {
  margin: 0 12px;
  text-decoration: none;
  color: #374151;
  font-weight: 500;
}

.nav-btn {
  padding: 8px 16px;
  border-radius: 6px;
  border: none;
  cursor: pointer;
}

.login {
  background: transparent;
  border: 1px solid #2563eb;
  color: #2563eb;
}

.signup {
  background: #2563eb;
  color: #fff;
}

/* HERO */
.hero {
  min-height: 520px;
  background:
    linear-gradient(to right, #f8fbff 55%, rgba(248,251,255,0.6)),
    url("https://images.unsplash.com/photo-1606813902917-6e5e9aef9f68?auto=format&fit=crop&w=900&q=80");
  background-repeat: no-repeat;
  background-position: right center;
  background-size: contain;
  display: flex;
  align-items: center;
  padding: 60px;
}

.hero-content {
  width: 55%;
}

.hero h1 {
  font-size: 46px;
  font-weight: 700;
}

.hero h1 span {
  color: #2563eb;
}

.hero p {
  margin: 15px 0 30px;
  color: #6b7280;
}

/* SEARCH */
.search-box {
  display: flex;
  gap: 10px;
  margin-bottom: 30px;
}

.search-box select,
.search-box input {
  padding: 12px;
  border-radius: 6px;
  border: 1px solid #ddd;
}

.search-box button {
  padding: 12px 20px;
  background: #2563eb;
  border: none;
  color: #fff;
  border-radius: 6px;
  cursor: pointer;
}

/* STEPS */
.steps {
  display: flex;
  gap: 25px;
}

.steps div {
  font-weight: 600;
}

/* SECTION */
section {
  padding: 60px;
}

.section-title {
  font-size: 28px;
  margin-bottom: 30px;
}

/* SPECIALTIES */
.specialties {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px,1fr));
  gap: 20px;
}

.card {
  background: #fff;
  padding: 25px;
  border-radius: 12px;
  box-shadow: 0 10px 20px rgba(0,0,0,0.05);
  text-align: center;
}

/* DOCTORS */
.doctors {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(230px,1fr));
  gap: 25px;
}

.doctor-card img {
  width: 100%;
  border-radius: 12px;
}

.doctor-card h4 {
  margin-top: 10px;
}

.doctor-card button {
  margin-top: 10px;
  padding: 8px;
  width: 100%;
  background: #2563eb;
  border: none;
  color: white;
  border-radius: 6px;
}

/* APPOINTMENT */
.appointment {
  background: #ffffff;
  border-radius: 15px;
  padding: 40px;
  box-shadow: 0 15px 30px rgba(0,0,0,0.05);
}

.appointment form {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px,1fr));
  gap: 15px;
}

.appointment input,
.appointment select,
.appointment button {
  padding: 12px;
  border-radius: 6px;
  border: 1px solid #ddd;
}

.appointment button {
  background: #2563eb;
  color: white;
  border: none;
  cursor: pointer;
}

/* FOOTER */
footer {
  background: #1e3a8a;
  color: white;
  padding: 40px 60px;
  margin-top: 60px;
}

footer p {
  opacity: 0.8;
}

/* MOBILE */
@media(max-width:900px) {
  header, section, .hero {
    padding: 30px;
  }
  .hero-content {
    width: 100%;
    text-align: center;
  }
  .hero {
    background: #f8fbff;
  }
}
</style>
</head>

<body>

<header>
  <div class="logo">MediCare</div>
  <nav>
    <a href="doc.html">Home</a>
    <a href="about.html">About</a>
    <a href="services.html">Services</a>
    <a href="doctors.html">Doctors</a>
    <a href="contact.html">Contact</a>
  <button class="nav-btn login" onclick="window.location.href='login.html'"> Login </button>
 <button class="nav-btn signup" onclick="window.location.href='signup.html'">
 Sign Up
</button>

  </nav>
</header>

<section class="hero">
  <div class="hero-content">
    <h1>Find & Book a <span>Doctor</span></h1>
    <p>Schedule an appointment with experienced doctors near you.</p>

    <div class="search-box">
      <select><option>Select Specialty</option></select>
      <input type="text" placeholder="Enter Location">
      <input type="date">
      <button>Search</button>
    </div>

    <div class="steps">
      <div>01 Search Doctor</div>
      <div>02 Book Appointment</div>
      <div>03 Get Treatment</div>
    </div>
  </div>
</section>

<section>
  <h2 class="section-title">Top Specialties</h2>
  <div class="specialties">
    <div class="card">Cardiology</div>
    <div class="card">Dentistry</div>
    <div class="card">Pediatrics</div>
    <div class="card">Orthopedics</div>
  </div>
</section>

<section>
  <h2 class="section-title">Our Expert Doctors</h2>
  <div class="doctors">
    <div class="doctor-card card">
      <img src="https://randomuser.me/api/portraits/women/44.jpg">
      <h4>Dr. Sarah Johnson</h4>
      <p>Cardiologist</p>
      <button>View Profile</button>
    </div>
    <div class="doctor-card card">
      <img src="https://randomuser.me/api/portraits/men/32.jpg">
      <h4>Dr. James Smith</h4>
      <p>Dentist</p>
      <button>View Profile</button>
    </div>
    <div class="doctor-card card">
      <img src="https://randomuser.me/api/portraits/women/65.jpg">
      <h4>Dr. Emily Brown</h4>
      <p>Pediatrician</p>
      <button>View Profile</button>
    </div>
    <div class="doctor-card card">
      <img src="https://randomuser.me/api/portraits/men/76.jpg">
      <h4>Dr. Michael Lee</h4>
      <p>Orthopedic</p>
      <button>View Profile</button>
    </div>
  </div>
</section>

<section>
  <h2 class="section-title">Book an Appointment</h2>
  <div class="appointment">
    <form>
      <input type="text" placeholder="Your Name">
      <input type="email" placeholder="Your Email">
      <select><option>Select Department</option></select>
      <input type="date">
      <button>Book Appointment</button>
    </form>
  </div>
</section>

<footer>
  <p>© 2026 MediCare. All rights reserved.</p>
</footer>

</body>
</html>
