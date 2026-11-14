<?php
// 1. Include the database connection
include 'db_connect.php';

// 2. Fetch statistics from the database
$sql_challenges = "SELECT COUNT(*) as total FROM challenges";
$result_challenges = $conn->query($sql_challenges);
$total_challenges = ($result_challenges) ? $result_challenges->fetch_assoc()['total'] : 100;

$sql_users = "SELECT COUNT(*) as total FROM users";
$result_users = $conn->query($sql_users);
$total_users = ($result_users) ? $result_users->fetch_assoc()['total'] : 1000;

$sql_volunteering = "SELECT COUNT(*) as total FROM volunteering";
$result_volunteering = $conn->query($sql_volunteering);
$total_volunteering = ($result_volunteering) ? $result_volunteering->fetch_assoc()['total'] : 10;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeSpace - Gamified Personal Growth & Community Engagement</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* CSS variables based on logo colors */
        :root {
            --purple-main: #6c5ce7;
            --pink-main: #fd79a8;
            --text-dark: #2d3436;
            --text-light: #636e72;
            --bg-purple-light: #f5f3ff; /* New: Soft purple background */
            --bg-pink-light: #fff0f5;   /* New: Soft pink background */
            --sdg3-color: #4C9F38; /* Official SDG 3 Green */
            --sdg11-color: #F99D26; /* Official SDG 11 Orange */
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            color: var(--text-dark);
            background-color: #fff;
            line-height: 1.6;
            overflow-x: hidden;
        }

        a { text-decoration: none; transition: 0.3s; }
        
        /* Typography utility */
        .section-title {
            text-align: center;
            font-size: 2.5rem;
            color: var(--purple-main);
            margin-bottom: 50px;
        }

        /* Animation Keyframes */
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-on-load {
            opacity: 0;
            animation: fadeInUp 1s ease-out forwards;
        }

        /* --- Header / Navbar --- */
        header {
            background-color: #fff;
            padding: 10px 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            animation: fadeInDown 0.8s ease-out;
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo-img {
            height: 70px; /* Made the logo even bigger! */
        }

        .logo-text {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--purple-main);
            letter-spacing: 1px;
        }

        .nav-links {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .btn-login {
            color: var(--purple-main);
            font-weight: 600;
            padding: 8px 18px;
            border: 2px solid var(--purple-main);
            border-radius: 25px;
        }
        .btn-login:hover {
            background-color: var(--purple-main);
            color: #fff;
        }

        .btn-signup {
            background-color: var(--purple-main);
            color: #fff;
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 25px;
            box-shadow: 0 4px 6px rgba(108, 92, 231, 0.2);
        }
        .btn-signup:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(108, 92, 231, 0.3);
        }

        .nav-link {
            color: var(--text-dark);
            font-weight: 600;
        }
        .nav-link:hover {
            color: var(--purple-main);
        }

        /* --- Hero Section --- */
        .hero {
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
            background: linear-gradient(135deg, var(--bg-purple-light) 0%, #fff 100%);
            padding-top: 80px;
        }

        .hero-content {
            max-width: 800px;
            animation: fadeInUp 1.2s ease-out 0.2s forwards;
            opacity: 0;
            padding: 0 20px;
        }

        .hero h1 {
            font-size: 3.8rem;
            font-weight: 700;
            margin-bottom: 20px;
            background: linear-gradient(90deg, var(--purple-main) 0%, var(--pink-main) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1.2;
        }

        .hero p {
            font-size: 1.2rem;
            color: var(--text-light);
            margin-bottom: 40px;
            font-weight: 300;
        }

        /* --- Statistics Section --- */
        .stats {
            padding: 80px 50px;
            background-color: #fff; /* Clean white contrast */
            text-align: center;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .stat-card {
            background: #fff;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.04);
            border: 1px solid rgba(0,0,0,0.02);
            transition: 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        }

        .stat-number-wrapper {
            font-size: 4rem;
            font-weight: 700;
            color: var(--purple-main);
            margin-bottom: 10px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .stat-card p {
            font-size: 1.1rem;
            color: var(--text-light);
            font-weight: 400;
        }

        /* --- SDG Section --- */
        .sdg-section {
            padding: 100px 50px;
            background-color: var(--bg-pink-light); /* Soft pink background */
        }

        .sdg-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 40px;
            max-width: 1000px;
            margin: 0 auto;
        }

        .sdg-card {
            padding: 40px;
            border-radius: 20px;
            background: #fff;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            border-top: 5px solid #ccc;
            transition: 0.3s;
        }
        
        .sdg-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .sdg-card.sdg3 { border-color: var(--sdg3-color); }
        .sdg-card.sdg11 { border-color: var(--sdg11-color); }

        .sdg-card h3 {
            font-size: 1.5rem;
            margin-bottom: 15px;
        }
        .sdg3 h3 { color: var(--sdg3-color); }
        .sdg11 h3 { color: var(--sdg11-color); }

        /* --- Categories Section --- */
        .categories-section {
            padding: 100px 50px;
            background-color: var(--bg-purple-light); /* Soft purple background */
        }

        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .cat-card {
            background: #fff;
            padding: 25px 20px;
            border-radius: 15px;
            text-align: center;
            font-weight: 600;
            color: var(--purple-main);
            box-shadow: 0 4px 10px rgba(108, 92, 231, 0.08);
            border: 1px solid rgba(108, 92, 231, 0.1);
            transition: 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100px;
        }

        .cat-card:hover {
            background-color: var(--purple-main);
            color: #fff;
            transform: scale(1.05);
        }

        /* --- Footer --- */
        footer {
            background-color: var(--text-dark);
            color: #b2bec3;
            padding: 40px;
            text-align: center;
        }

    </style>
</head>
<body>

    <header>
        <div class="logo-container">
            <img src="images/logo.png" alt="SafeSpace Logo" class="logo-img">
            <span class="logo-text">SafeSpace</span>
        </div>
        <nav class="nav-links">
            <a href="about.php" class="nav-link">About Us</a>
            <a href="login.php" class="btn-login">Login</a>
            <a href="register.php" class="btn-signup">Sign Up</a>
        </nav>
    </header>

    <section class="hero">
        <div class="hero-content">
            <h1>Level Up Your Life, <br> One Habit at a Time.</h1>
            <p>SafeSpace is a gamified mental health platform built for students and young adults. Track your habits, earn points, protect your streak, and connect with your community. All in a secure environment.</p>
            <a href="register.php" class="btn-signup">Start Your Journey</a>
        </div>
    </section>

    <section class="stats animate-on-load">
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-number-wrapper">
                    <span class="counter" data-target="<?php echo $total_users; ?>">0</span>+
                </div>
                <p>Active Users</p>
            </div>
            <div class="stat-card">
                <div class="stat-number-wrapper">
                    <span class="counter" data-target="<?php echo $total_challenges; ?>">0</span>+
                </div>
                <p>Daily Challenges</p>
            </div>
            <div class="stat-card">
                <div class="stat-number-wrapper">
                    <span class="counter" data-target="<?php echo $total_volunteering; ?>">0</span>+
                </div>
                <p>Volunteering Opportunities</p>
            </div>
        </div>
    </section>

    <section class="sdg-section">
        <h2 class="section-title">Driven by Global Goals</h2>
        <div class="sdg-container">
            <div class="sdg-card sdg3">
                <h3>SDG 3: Good Health & Well-being</h3>
                <p>We are committed to ensuring healthy lives and promoting well-being for students. SafeSpace provides tools to manage stress, improve sleep hygiene, and foster mental calmness through actionable daily habits.</p>
            </div>
            <div class="sdg-card sdg11">
                <h3>SDG 11: Sustainable Communities</h3>
                <p>Making cities and human settlements inclusive, safe, resilient, and sustainable. SafeSpace connects students to local volunteering opportunities, fostering a strong, engaged, and supportive community.</p>
            </div>
        </div>
    </section>

    <section class="categories-section">
        <h2 class="section-title">Explore Challenge Categories</h2>
        <div class="categories-grid">
            <?php
            // Fetch only unique categories directly from the challenges table
            $cat_sql = "SELECT DISTINCT category FROM challenges WHERE category IS NOT NULL AND category != '' ORDER BY category ASC";
            $cat_result = $conn->query($cat_sql);

            if ($cat_result && $cat_result->num_rows > 0) {
                while($row = $cat_result->fetch_assoc()) {
                    echo '<div class="cat-card">' . htmlspecialchars($row['category']) . '</div>';
                }
            } else {
                // If the admin hasn't added any challenges yet, show a placeholder
                echo '<div class="cat-card">Categories Coming Soon!</div>';
            }
            ?>
        </div>
    </section>

    <footer>
        <p>&copy; 2026 SafeSpace Project. Built to empower students and young adults.</p>
    </footer>

    <script>
        const counters = document.querySelectorAll('.counter');
        const speed = 200; 

        const runCounters = () => {
            counters.forEach(counter => {
                const updateCount = () => {
                    const target = +counter.getAttribute('data-target'); 
                    const count = +counter.innerText; 

                    const inc = target / speed;

                    if (count < target) {
                        counter.innerText = Math.ceil(count + inc);
                        setTimeout(updateCount, 15); 
                    } else {
                        counter.innerText = target; 
                    }
                };
                updateCount();
            });
        };

        const statsSection = document.querySelector('.stats');
        const options = { threshold: 0.5 }; 

        const observer = new IntersectionObserver(function(entries, observer) {
            entries.forEach(entry => {
                if(entry.isIntersecting) {
                    runCounters();
                    observer.unobserve(entry.target); 
                }
            });
        }, options);

        observer.observe(statsSection);
    </script>
</body>
</html>