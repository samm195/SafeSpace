<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us – SafeSpace</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --purple-main: #6c5ce7;
            --pink-main: #fd79a8;
            --text-dark: #2d3436;
            --text-light: #636e72;
            --bg-purple-light: #f5f3ff;
            --bg-pink-light: #fff0f5;
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
            line-height: 1.8;
            overflow-x: hidden;
        }

        a { text-decoration: none; transition: 0.3s; }

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
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo-img { height: 70px; }

        .logo-text {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--purple-main);
            letter-spacing: 1px;
        }

        .nav-quote {
            color: var(--text-light);
            font-weight: 600;
            font-style: italic;
            font-size: 1.1rem;
            letter-spacing: 0.5px;
            background: -webkit-linear-gradient(135deg, var(--purple-main), var(--pink-main));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* --- About Content --- */
        .about-header {
            padding: 150px 20px 80px;
            text-align: center;
            background: linear-gradient(135deg, var(--bg-purple-light) 0%, #fff 100%);
        }

        .about-header h1 {
            font-size: 3.5rem;
            color: var(--purple-main);
            margin-bottom: 20px;
        }

        .about-header p {
            font-size: 1.2rem;
            color: var(--text-light);
            max-width: 700px;
            margin: 0 auto;
        }

        .about-section {
            max-width: 900px;
            margin: 0 auto;
            padding: 60px 20px;
        }

        .game-rules {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-top: 50px;
        }

        .rule-card {
            background: #fff;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            border-top: 5px solid var(--pink-main);
            text-align: center;
            transition: all 0.3s ease;
        }

        .rule-card:hover {
            transform: translateY(-12px);
            box-shadow: 0 15px 30px rgba(108, 92, 231, 0.15);
        }

        .rule-card h3 {
            color: var(--purple-main);
            font-size: 1.5rem;
            margin-bottom: 15px;
        }

        /* --- Pro Articles Banner --- */
        .pro-guides {
            background: linear-gradient(135deg, var(--purple-main) 0%, var(--pink-main) 100%);
            color: #fff;
            padding: 50px 40px;
            border-radius: 20px 20px 0 0; /* Flat bottom to connect to creator section */
            margin-top: 60px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(108, 92, 231, 0.2);
        }

        .pro-guides h2 {
            font-size: 2.2rem;
            margin-bottom: 20px;
            color: #fff;
        }

        .pro-guides p {
            font-size: 1.15rem;
            margin-bottom: 0;
            opacity: 0.95;
            line-height: 1.7;
        }

        /* --- New: Become a Creator Section --- */
        .creator-unlock {
            background-color: #fff;
            border: 3px dashed #dfe4ea;
            border-top: none; /* Connects smoothly to the Pro section */
            padding: 40px;
            border-radius: 0 0 20px 20px;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .creator-unlock:hover {
            border-color: var(--pink-main);
            background-color: var(--bg-pink-light);
        }

        .creator-unlock h2 {
            color: var(--text-dark);
            margin-bottom: 15px;
            font-size: 1.8rem;
        }

        .creator-steps {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .step-box {
            background: var(--card-gray, #f1f2f6);
            padding: 20px;
            border-radius: 12px;
            flex: 1;
            min-width: 220px;
        }

        .step-box span {
            display: block;
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .step-box h4 { color: var(--purple-main); margin-bottom: 5px; }
        .step-box p { font-size: 0.9rem; color: var(--text-light); line-height: 1.5; }

        .story-text {
            background-color: var(--bg-purple-light);
            padding: 50px 40px;
            border-radius: 20px;
            margin-top: 60px;
            text-align: center;
        }

        .story-text h2 {
            color: var(--purple-main);
            margin-bottom: 20px;
            font-size: 2.2rem;
        }

        /* --- Footer --- */
        footer {
            background-color: var(--text-dark);
            color: #b2bec3;
            padding: 40px;
            text-align: center;
            margin-top: 50px;
        }

        @media (max-width: 768px) {
            header { padding: 15px 20px; flex-direction: column; gap: 10px; }
            .nav-quote { display: none; } /* Hide quote on mobile to save space */
            .about-header h1 { font-size: 2.5rem; }
            .pro-guides { border-radius: 20px; margin-bottom: 20px; }
            .creator-unlock { border-radius: 20px; border-top: 3px dashed #dfe4ea; }
        }
    </style>
</head>
<body>

    <header>
        <div class="logo-container">
            <img src="images/logo.png" alt="SafeSpace Logo" class="logo-img">
            <span class="logo-text">SafeSpace</span>
        </div>
        <div class="nav-quote">
            "Your life is the main quest. Play it well."
        </div>
    </header>

    <div class="about-header">
        <h1>Welcome to SafeSpace! 🎮</h1>
        <p>Imagine if real life was a video game, and the main character was YOU. That is exactly what we built.</p>
    </div>

    <div class="about-section">
        <div style="text-align: center; margin-bottom: 40px;">
            <h2 style="color: var(--purple-main); font-size: 2.5rem; margin-bottom: 20px;">What are you in for?</h2>
            <p style="font-size: 1.1rem; color: var(--text-light);">
                Sometimes, doing homework, drinking enough water, or finding a job can feel a bit... overwhelming. 
                But what if every time you did something good for yourself, you earned Experience Points (XP), leveled up, and unlocked achievements? <br><br>
                SafeSpace turns your daily habits, mental health routines, and career goals into an epic quest. You play the game by taking care of yourself!
            </p>
        </div>

        <h2 style="text-align: center; color: var(--purple-main); margin-top: 60px;">How to Play 🕹️</h2>
        <div class="game-rules">
            <div class="rule-card">
                <h3>1. Pick a Quest</h3>
                <p>Choose a challenge from our categories. It could be drinking a glass of water, updating your resume, or taking a screen break.</p>
            </div>
            <div class="rule-card">
                <h3>2. Do the Thing</h3>
                <p>Complete the task in real life! Put your phone down, get it done, and prove you are capable of awesome things.</p>
            </div>
            <div class="rule-card">
                <h3>3. Level Up!</h3>
                <p>Log back in to check off your quest. Earn points, build up your daily streak, and watch your character level up!</p>
            </div>
        </div>

        <div class="pro-guides">
            <h2>Learn from the Pros 🧙‍♂️</h2>
            <p>
                Every great player needs a little advice along the way! That is why we teamed up with real-life experts to bring you the best <strong>Tips & Hacks</strong>. <br><br>
                Inside SafeSpace, you can freely read articles written by certified therapists, fitness coaches, career advisors, and study experts. Whether you want to beat exam stress, prepare for an interview, or figure out how to get better sleep, our professionals are right here to guide you.
            </p>
        </div>
        
        <div class="creator-unlock">
            <h2>Unlock the Creator Role ✍️</h2>
            <p style="color: var(--text-light); max-width: 700px; margin: 0 auto;">Have knowledge to share? You don't have to just be a player—you can become a guide! We are always looking for passionate students, mentors, and experts to write articles for our community.</p>
            
            <div class="creator-steps">
                <div class="step-box">
                    <span>💡</span>
                    <h4>1. Have a Skill</h4>
                    <p>Be knowledgeable in academics, fitness, career growth, or mental wellness.</p>
                </div>
                <div class="step-box">
                    <span>📤</span>
                    <h4>2. Submit Request</h4>
                    <p>Head to your Profile, write a short pitch, and upload proof of your expertise (like a resume or certificate).</p>
                </div>
                <div class="step-box">
                    <span>🔓</span>
                    <h4>3. Get Approved</h4>
                    <p>Once our admins review your request, you will unlock the ability to publish your own articles!</p>
                </div>
            </div>
        </div>

        <div class="story-text">
            <h2>Why We Built This</h2>
            <p style="font-size: 1.1rem; color: var(--text-dark);">
                Growing up, studying, and "adulting" can be tough. It is easy to get stressed out or forget to take care of yourself. 
                We created SafeSpace because we believe personal growth shouldn't feel like a chore. It should feel rewarding! <br><br>
                Whether you want to build better study habits, calm your mind, or prepare for your future career, this is your safe zone to grow, play, and become the best version of yourself.
            </p>
        </div>
    </div>

    <footer>
        <p>&copy; 2026 SafeSpace Project. Built to empower students and young adults.</p>
    </footer>

</body>
</html>