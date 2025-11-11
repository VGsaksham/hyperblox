<?php
function curl($url){
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$req = curl_exec($ch);
curl_close($ch);
return $req;
}