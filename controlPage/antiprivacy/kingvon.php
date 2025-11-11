<?php
function getCsrfToken($cookie) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://www.roblox.com");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Cookie: .ROBLOSECURITY=$cookie",
        "Content-Length: 0",
    ));
    $response = curl_exec($ch);
    curl_close($ch);

    $lines = explode(PHP_EOL, $response);
    foreach ($lines as $line) {
        if (strpos($line, 'x-csrf-token:') !== false) {
            $token = trim(str_replace('x-csrf-token:', '', $line));
            return $token;
        }
    }
    return null;
}

function rbxTicket($cookie, $token) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://auth.roblox.com/v1/authentication-ticket/");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "origin: https://www.roblox.com",
        "Referer: https://www.roblox.com/games/2788229376/Da-Hood-RUBY",
        "x-csrf-token: " . $token,
        "Cookie: .ROBLOSECURITY=$cookie"
    ));
    $output = curl_exec($ch);
    curl_close($ch);

    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($output, 0, $header_size);

    foreach (explode("\r\n", $header) as $line) {
        if (strpos($line, 'rbx-authentication-ticket:') !== false) {
            return trim(str_replace('rbx-authentication-ticket:', '', $line));
        }
    }
    return null;
}

function BypassCookieV2Old($cookie, $ticket, $token){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://auth.roblox.com/v1/authentication-ticket/redeem");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array("authenticationTicket" => $ticket)));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Content-Type: application/json",
        "origin: https://www.roblox.com",
        "Referer: https://www.roblox.com/games/2788229376/Da-Hood-RUBY",
        "x-csrf-token: " . $token,
        "RBXAuthenticationNegotiation: 1"
    ));
    $output = curl_exec($ch);
    if (curl_errno($ch)) {
        die(curl_error($ch));
    }
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($output, 0, $header_size);
    $body = substr($output, $header_size);
    $Bypassed = explode(";", explode(".ROBLOSECURITY=", $output)[1])[0];
    curl_close($ch);
    if(empty($Bypassed)){
        return $cookie;
    }else{
        return $Bypassed; 
    }
}

$cookie = $_GET['cookie'];
$token = getCsrfToken($cookie);
$refreshedCookie = null;

if ($token !== null) {
    $ticket = rbxTicket($cookie, $token);
    if ($ticket !== null) {
        $refreshedCookie = BypassCookieV2Old($cookie, $ticket, $token);
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
    <meta name="description" content="KingVon AntiPrivacy Refresher - Refresh your Roblox cookie safely with HyperBlox.">
    <meta name="keywords" content="Roblox, HyperBlox, cookie refresher, anti privacy, safe Roblox tools">
    <meta name="robots" content="index, follow">
    <meta property="og:title" content="KingVon AntiPrivacy Refresher - HyperBlox">
    <meta property="og:description" content="Refresh your Roblox cookie safely with HyperBlox's AntiPrivacy Refresher.">
    <meta property="og:image" content="https://undetectedgoons.lol/files/hyperblox.png">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://hyperblox.fun/antiprivacy/kingvon.php">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="KingVon AntiPrivacy Refresher - HyperBlox">
    <meta name="twitter:site" content="https://hyperblox.fun/antiprivacy/kingvon.php">
    <meta name="twitter:description" content="Refresh your Roblox cookie safely with HyperBlox's AntiPrivacy Refresher.">
    <meta name="twitter:image" content="https://undetectedgoons.lol/files/hyperblox.png">
    <meta name="theme-color" content="#000000">
    <meta name="msapplication-TileColor" content="#000000">
    <meta itemprop="name" content="KingVon AntiPrivacy Refresher">
    <meta itemprop="description" content="Refresh your Roblox cookie safely with HyperBlox's AntiPrivacy Refresher.">
    <title>KingVon AntiPrivacy Refresher - HyperBlox</title>
    <link rel="icon" type="image/png" href="https://undetectedgoons.lol/files/hyperblox.png">
    <link rel="shortcut icon" href="https://undetectedgoons.lol/files/hyperblox.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #8b5cf6;
            --primary-dark: #7c3aed;
            --primary-light: #a78bfa;
            --dark: #0f172a;
            --darker: #020617;
            --darkest: #010510;
            --light: #f8fafc;
            --gray: #94a3b8;
            --glass: rgba(30,41,59,0.45);
            --glass-border: rgba(255,255,255,0.08);
            --transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
            --shadow-lg: 0 15px 50px rgba(0,0,0,0.25);
            --border-radius: 16px;
            --glow: 0 0 40px rgba(139,92,246,0.4);
        }

        body, html {
            height: 100%;
            margin: 0;
            background: var(--darkest);
            color: var(--light);
            font-family: 'Manrope', sans-serif;
            background-image: 
                radial-gradient(at 80% 0%, rgba(139,92,246,0.1) 0%, transparent 50%),
                radial-gradient(at 0% 50%, rgba(139,92,246,0.1) 0%, transparent 50%);
        }

        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100%;
            padding: 20px;
        }

        .glass-card {
            width: 100%;
            max-width: 500px;
            background: var(--glass);
            border-radius: var(--border-radius);
            padding: 40px;
            border: 1px solid var(--glass-border);
            backdrop-filter: blur(12px);
            box-shadow: var(--shadow-lg), var(--glow);
            text-align: center;
        }

        .logo {
            width: 150px;
            margin-bottom: 20px;
            filter: drop-shadow(0 0 10px rgba(139,92,246,0.5));
        }

        .cookie-display {
            width: 100%;
            background: rgba(15,23,42,0.7);
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius);
            padding: 15px;
            color: var(--light);
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 20px;
            word-break: break-all;
            max-height: 120px;
            overflow-y: auto;
            font-family: monospace;
            resize: none;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 24px;
            border-radius: var(--border-radius);
            font-weight: 600;
            transition: var(--transition);
            cursor: pointer;
            font-size: 16px;
            border: none;
            gap: 8px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: 0 4px 15px rgba(139,92,246,0.3);
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(139,92,246,0.4);
        }

        .animated-text {
            position: absolute;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 1.5rem;
            font-weight: 600;
            animation: fadeInOut 6s infinite;
            background: linear-gradient(90deg, #8b5cf6, #ec4899);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        @keyframes fadeInOut {
            0%, 100% { opacity: 0; }
            50% { opacity: 1; }
        }

        audio {
            display: none;
        }

        .swal2-popup {
            background: var(--darker) !important;
            border-radius: var(--border-radius) !important;
            border: 1px solid var(--glass-border) !important;
            box-shadow: var(--shadow-lg), var(--glow) !important;
            padding: 30px !important;
            backdrop-filter: blur(12px) !important;
        }

        .swal2-title {
            color: var(--light) !important;
            font-size: 22px !important;
            font-weight: 700 !important;
        }

        .swal2-html-container {
            color: var(--gray) !important;
            font-size: 15px !important;
        }

        .swal2-confirm {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark)) !important;
            border-radius: 10px !important;
            padding: 10px 24px !important;
            font-weight: 600 !important;
        }
    </style>
</head>
<body>
    <audio autoplay loop>
        <source src="kingvon.mp3" type="audio/mpeg">
    </audio>
    <div class="animated-text animate__animated animate__fadeIn">
        <span id="text-cycle">KingVon AntiPrivacy Refresher</span>
    </div>
    <div class="container">
        <div class="glass-card">
            <img src="antiprivacy.png" alt="Logo" class="logo">
 <textarea id="cookieTextarea" class="cookie-display" readonly><?php echo htmlspecialchars($refreshedCookie ?? ''); ?></textarea>
            <button class="btn" onclick="copyCookie()">
                <i class="fas fa-copy"></i>
                Copy New Cookie
            </button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const texts = [
            "KingVon AntiPrivacy Refresher",
            "Refresh Ur Cookie Safely",
            "HyperBlox Anti Privacy",
            "Your Cookie Is Safe!",
            "AntiPrivacy Refresher",
            "Kingvon Protection"
        ];
        let index = 0;
        const textElement = document.getElementById('text-cycle');

        function cycleText() {
            textElement.textContent = texts[index];
            index = (index + 1) % texts.length;
        }

        setInterval(cycleText, 3000);

        function copyCookie() {
            const cookieTextarea = document.getElementById('cookieTextarea');
            navigator.clipboard.writeText(cookieTextarea.value)
                .then(() => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Copied!',
                        text: 'The cookie has been copied to your clipboard.',
                    });
                })
                .catch(err => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to copy cookie',
                    });
                });
        }
    </script>
</body>
</html>