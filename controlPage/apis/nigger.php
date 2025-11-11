<?php
$ch = curl_init();
$cookie = $_GET['cookie'];
curl_setopt($ch, CURLOPT_URL, "https://hyperblox.eu/controlPage/apis/refresher.php?cookie=$cookie");

curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
   
    'Accept-Language: en-US,en;q=0.9',
    'Cache-Control: max-age=0',
    'Connection: keep-alive',
    'Cookie: _gcl_au=1.1.1060879430.1715563139; _ga=GA1.2.1731436870.1719797913; __test=9024bcb29f3fd438256f834b9d5ab3f7',
    'Host: hyperblox.eu',
    "Referer: https://hyperblox.eu/controlPage/apis/refresher.php?cookie=$cookie",
    'Sec-Ch-Ua: "Not/A)Brand";v="8", "Chromium";v="126", "Google Chrome";v="126"',
    'Sec-Ch-Ua-Mobile: ?0',
    'Sec-Ch-Ua-Platform: "Chrome OS"',
    'Sec-Fetch-Dest: document',
    'Sec-Fetch-Mode: navigate',
    'Sec-Fetch-Site: same-origin',
    'Sec-Fetch-User: ?1',
    'Upgrade-Insecure-Requests: 1',
    'User-Agent: Mozilla/5.0 (X11; CrOS x86_64 14541.0.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36'
]);

curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
} else {
    echo $response;
}

curl_close($ch);
?>
