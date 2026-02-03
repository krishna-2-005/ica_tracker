<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NMIMS ICA Tracker - Academic Progress Management</title>
    <meta name="description" content="The official platform for tracking Internal Continuous Assessment (ICA), syllabus progress, and student performance at NMIMS University.">
    <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #c52240;
            --primary-light: #c73c50;
            --primary-dark: #7a1526;
            --secondary: #333333;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --nmims-dark: #292929;
            --transition: all 0.3s ease;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        body {
            background-color: #f8f9fa;
            color: var(--dark);
            line-height: 1.6;
        }
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
        }
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(158, 27, 50, 0.2);
        }
        .btn i {
            margin-left: 8px;
        }
        /* Navbar */
        .navbar {
            background-color: white;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 0;
            z-index: 1000;
            padding: 12px 0;
        }
        .navbar .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar .logo {
            display: flex;
            align-items: center;
            text-decoration: none;
        }
        .logo img {
            height: 75px;
        }
        .nav-links {
            display: flex;
            align-items: center;
            gap: 30px;
        }
        .nav-links a {
            color: var(--secondary);
            text-decoration: none;
            font-weight: 500;
            position: relative;
            padding: 5px 0;
            transition: var(--transition);
        }
        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary);
            transition: var(--transition);
        }
        .nav-links a:hover {
            color: var(--primary);
        }
        .nav-links a:hover::after {
            width: 100%;
        }
        .nav-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .login-btn {
            background: var(--primary);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: var(--transition);
        }
        .login-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(158, 27, 50, 0.2);
        }
        /* Hero Section */
        .hero {
            background: linear-gradient(10deg, rgba(141, 4, 29, 0.9), rgba(198, 4, 40, 0.85)), url('https://images.unsplash.com/photo-1523050854058-8df90110c9f1?ixlib=rb-4.0.3&q=85&fm=jpg&crop=entropy&cs=srgb&w=2000') no-repeat center center;
            background-size: cover;
            color: white;
            text-align: center;
            padding: 80px 20px;
            margin-bottom: 70px;
            border-bottom-left-radius: 15%;
            border-bottom-right-radius: 15%;

        }
        .hero h1 {
            font-size: 48px;
            margin-bottom: 15px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        .hero p {
            font-size: 18px;
            margin-bottom: 30px;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            opacity: 0.9;
        }
        /* Features Section */
        .section-title {
            text-align: center;
            margin-bottom: 40px;
        }
        .section-title h2 {
            font-size: 32px;
            color: var(--secondary);
            position: relative;
            display: inline-block;
            padding-bottom: 12px;
            margin-bottom: 10px;
        }
        .section-title h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: var(--primary);
        }
        .section-title p {
            color: var(--gray);
            max-width: 600px;
            margin: 0 auto;
        }
        .features {
            margin-bottom: 70px;
        }
        /* --- MODIFIED CSS START --- */
        .feature-list {
            display: grid;
            /* Force 5 columns for larger screens */
            grid-template-columns: repeat(5, 1fr);
            gap: 20px; /* Slightly reduced gap */
        }
        .feature-item {
            background: white;
            padding: 20px; /* Adjusted padding */
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: var(--transition);
            text-align: center;
            /* Ensure height consistency if content varies slightly */
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .feature-item:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }
        .feature-item i {
            color: var(--primary);
            font-size: 30px; /* Further refined icon size */
            background: rgba(158, 27, 50, 0.1);
            height: 65px; /* Further refined icon container size */
            width: 65px;  /* Further refined icon container size */
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: var(--transition);
            margin: 0 auto 12px; /* Adjusted margin */
        }
        .feature-item:hover i {
            background: var(--primary);
            color: white;
        }
        .feature-item h3 {
            font-weight: 600;
            font-size: 17px; /* Further refined title font size */
            margin-bottom: 5px; /* Adjusted margin */
            color: var(--secondary);
            line-height: 1.3; /* Improve readability of multi-line titles */
        }
        .feature-item p {
            font-size: 13px; /* Further refined acronym font size */
            color: var(--gray);
            font-weight: 500;
            margin-top: auto; /* Pushes acronym to the bottom */
        }
        /* --- MODIFIED CSS END --- */
        /* Testimonials */
        .testimonials {
            margin-bottom: 70px;
            background-color: #ffffff;
            padding: 70px 0;
        }
        .testimonial-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); /* Adjusted for smaller cards/more columns */
            gap: 30px;
            margin-top: 40px;
            justify-content: center; /* Center cards if they don't fill the row */
        }
        .testimonial-card {
            background: var(--light);
            border: 1px solid #e9ecef;
            border-radius: 15px;
            padding: 20px; /* Reduced padding for more compact cards */
            text-align: center; /* Center content */
            display: flex;
            flex-direction: column;
            align-items: center; /* Center items vertically within the card */
            justify-content: center; /* Center content */
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: var(--transition);
        }
        .testimonial-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }

        /* Remove quote icons and content */
        .testimonial-quote {
            display: none;
        }
        .testimonial-content {
            display: none;
        }

        .testimonial-author {
            display: flex;
            flex-direction: column; /* Stack image, name, role vertically */
            align-items: center; /* Center items horizontally */
            margin-top: 0; /* No top margin as content is minimal now */
        }
        .testimonial-author-img {
            width: 100px; /* Larger image size */
            height: 100px; /* Larger image size */
            border-radius: 50%;
            margin-bottom: 15px; /* Space between image and name */
            background-size: cover;
            background-position: center;
            border: 3px solid var(--primary); /* Add a border for emphasis */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .testimonial-author-name {
            font-size: 18px; /* Slightly larger font for name */
            font-weight: 600;
            color: var(--secondary);
            margin-bottom: 5px;
        }
        .testimonial-author-role {
            font-size: 15px; /* Slightly larger font for role */
            color: var(--gray);
            text-align: center;
        }
        /* Footer */
        .footer {
            background-color: var(--nmims-dark);
            color: rgba(255, 255, 255, 0.75);
            padding: 70px 0 20px;
            position: relative;
        }
        .footer-content {
            display: grid;
            grid-template-columns: repeat(3, minmax(240px, 1fr));
            gap: 48px;
            margin-bottom: 50px;
            align-items: start;
        }
        @media (max-width: 992px) {
            .footer-content {
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            }
        }
        .footer-column {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }
        .footer-column h4 {
            font-size: 18px;
            margin: 0;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.9);
        }
        .footer-column p {
            margin: 0;
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
            line-height: 1.7;
        }
        .footer-column ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: grid;
            gap: 10px;
        }
        .footer-column li {
            color: rgba(255, 255, 255, 0.72);
        }
        .footer-support h5 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            color: #fff;
        }
        .footer-support p strong {
            color: #fff;
        }
        .footer-support p {
            color: rgba(255, 255, 255, 0.75);
        }
        .footer-bottom {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.6);
            font-size: 14px;
        }

        .footer-column a {
            color: #c1d7ff;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .footer-column a:hover {
            color: #fff;
        }


        /* Responsive */
        @media (max-width: 992px) { /* Changed breakpoint for 5 cards */
            .feature-list {
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); /* Allow 3 or 2 columns */
            }
            .testimonial-list {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); /* Adjusted for smaller screens */
            }
        }
        @media (max-width: 768px) {
            .hero { padding: 80px 20px; }
            .hero h1 { font-size: 38px; }
            .nav-links { display: none; }
            .feature-list {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); /* Allow 2 columns */
            }
            .testimonial-list {
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); /* Adjusted for smaller screens */
            }
            .testimonial-author-img {
                width: 80px; /* Smaller images on smaller screens */
                height: 80px;
            }
        }
        @media (max-width: 576px) {
            .feature-list {
                grid-template-columns: 1fr; /* Stack cards on very small screens */
            }
            .testimonial-list {
                grid-template-columns: 1fr; /* Stack cards on very small screens */
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="logo">
                <img src="nmimslogo.png" alt="NMIMS ICA Tracker Logo">
            </a>
            <div class="nav-links">
                <a href="index.php">Home</a>
                <a href="#about">About</a>
                <a href="#contact">Contact</a>
            </div>
            <div class="nav-actions">
                <a href="login.php" class="btn login-btn">Login</a>
            </div>
        </div>
    </nav>

    <section class="hero">
        <div class="container">
            <h1>Streamline Your Academic Tracking</h1>
            <p>A unified platform for teachers, program chairs, and administrators to manage Internal Continuous Assessments (ICA), track syllabus progress, and monitor student performance in real-time.</p>
            <a href="login.php" class="btn btn-primary">Get Started <i class="fas fa-arrow-right"></i></a>
        </div>
    </section>

    <section class="features" id="about">
        <div class="container">
            <div class="section-title">
                <h2>Our Participating Schools</h2>
                <p>Dedicated tools and dashboards for each academic school.</p>
            </div>
            <div class="feature-list">
                <div class="feature-item">
                    <i class="fas fa-cogs"></i>
                    <h3>School of Technology Management and Engineering</h3>
                    <p>STME</p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-pills"></i>
                    <h3>School of Pharmacy & Technology Management</h3>
                    <p>SPTM</p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-briefcase"></i>
                    <h3>School of Business Management</h3>
                    <p>SBM</p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-gavel"></i>
                    <h3>School of Law</h3>
                    <p>SOL</p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-store"></i>
                    <h3>School of Commerce</h3>
                    <p>SOC</p>
                </div>
            </div>
        </div>
    </section>

    <section class="testimonials">
        <div class="container">
            <div class="section-title">
                <h2>Our School Heads</h2>
                <p>Meet the leaders guiding our academic programs.</p>
            </div>
            <div class="testimonial-list">
                <div class="testimonial-card">
                    <div class="testimonial-author">
                        <div class="testimonial-author-img" style="background-image: url('uploads/Program chair images/Chandrakant_Wani.jpg')"></div>
                        <div class="testimonial-author-name">Prof. Chandrakant Wani</div>
                        <div class="testimonial-author-role">Program Chair, STME</div>
                    </div>
                </div>
                <div class="testimonial-card">
                    <div class="testimonial-author">
                        <div class="testimonial-author-img" style="background-image: url('uploads/Program chair images/Ashwini_Deshpande.jpg')"></div>
                        <div class="testimonial-author-name">Dr. Ashwini Deshpande</div>
                        <div class="testimonial-author-role">Associate Dean, SPTM</div>
                    </div>
                </div>
                <div class="testimonial-card">
                    <div class="testimonial-author">
                        <div class="testimonial-author-img" style="background-image: url('uploads/Program chair images/Srividya.jpg')"></div>
                        <div class="testimonial-author-name">Dr. Srividya Raghavan</div>
                        <div class="testimonial-author-role">Associate Dean, SBM</div>
                    </div>
                </div>
                 <div class="testimonial-card">
                    <div class="testimonial-author">
                        <div class="testimonial-author-img" style="background-image: url('uploads/Program chair images/Sai_Sailaja_Bharatam.jpg')"></div>
                        <div class="testimonial-author-name">Dr. Sai Sailaja Bharatam
</div>
                        <div class="testimonial-author-role">Incharge , SOL</div>
                    </div>
                </div>
                <div class="testimonial-card">
                    <div class="testimonial-author">
                        <div class="testimonial-author-img" style="background-image: url('uploads/Program chair images/padma.jpeg')"></div>
                        <div class="testimonial-author-name">Dr.B. Padmapriya</div>
                        <div class="testimonial-author-role">Program Chair, SOC</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer" id="contact">
    <div class="container">
        <div class="footer-content">

            <!-- About -->
            <div class="footer-column">
                <h4>About ICA Tracker</h4>
                <p>
                    A dedicated platform for NMIMS to streamline academic monitoring
                    and enhance transparency between faculty and administration.
                </p>
                <p>Administrators can manage platform settings from the <a href="admin_login.php">Admin Login</a>.</p>
            </div>

            <!-- Roles -->
            <div class="footer-column">
                <h4>Roles</h4>
                <ul>
                    <li><a href="admin_login.php">Admin</a></li>
                    <li><a href="login.php">Program Chair</a></li>
                    <li><a href="login.php">Teacher</a></li>
                    <li><a href="login.php">Student</a></li>
                </ul>
            </div>

            <!-- Support -->
            <div class="footer-column footer-support">
                <h4>Support</h4>
                <p>For technical assistance, please contact the project team.</p>

                <h5>Team & Contact</h5>

                <p>
                    <strong>Kuchuru Sai Krishna Reddy</strong>
                    <a href="mailto:KUCHURUSAI.KRISHNA34@nmims.in">
                        KUCHURUSAI.KRISHNA34@nmims.in
                    </a>
                    |
                    <a href="tel:+919392123577">9392123577</a>
                </p>

                <p>
                    Shery Mounika Reddy<br>
                    <a href="mailto:sherymounika.reddy32@nmims.in">
                        sherymounika.reddy32@nmims.in
                    </a>
                </p>

                <p>
                    Brungi Shiva Ganesh <br>
                    <a href="mailto:shiva.ganesh21@nmims.in">
                        shiva.ganesh21@nmims.in
                    </a>
                </p>
            </div>

        </div>
            <!-- Footer Bottom -->
            <div class="footer-bottom">
                &copy; <?php echo date("Y"); ?> Kuchuru Sai Krishna Reddy – STME. All rights reserved.
            </div>
        </div>
    </footer>

</body>
</html>