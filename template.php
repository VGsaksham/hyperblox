<?php
session_start();
$src = $_SESSION['src'];
if ($src){
$newWidth = 585;
$newHeight = 559;
$imageData = file_get_contents($src);
$image = imagecreatefromstring($imageData);
$originalWidth = imagesx($image);
$originalHeight = imagesy($image);
$newImage = imagecreatetruecolor($newWidth, $newHeight);
imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
header('Content-Type: image/png');
imagejpeg($newImage);
imagedestroy($image);
imagedestroy($newImage);
}
else{
echo "<script> window.location.href = '/Copy-Clothes' </script>";
}
?>