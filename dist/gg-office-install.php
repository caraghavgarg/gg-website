<?php
// One-time installer for GG Office. Put this file in the empty folder of
// office.gargraghav.in, open it in the browser, and it deletes itself.
$zip = 'https://raw.githubusercontent.com/caraghavgarg/gg-website/main/dist/gg-office-web.zip?t=' . time();
$c = curl_init($zip);
curl_setopt_array($c, array(CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 120));
$raw = curl_exec($c);
if (!$raw || strlen($raw) < 1000) { die('Could not download GG Office.'); }
file_put_contents(__DIR__ . '/ggo.zip', $raw);
$z = new ZipArchive();
if ($z->open(__DIR__ . '/ggo.zip') !== true) { die('Could not open the download.'); }
for ($i = 0; $i < $z->numFiles; $i++) {
    $n = $z->getNameIndex($i);
    if (strpos($n, 'gg-office-web/') !== 0 || strpos($n, '..') !== false) { continue; }
    $rel = substr($n, 14);
    if ($rel === '') { continue; }
    $dest = __DIR__ . '/' . $rel;
    if (substr($n, -1) === '/') { @mkdir($dest, 0755, true); continue; }
    @mkdir(dirname($dest), 0755, true);
    file_put_contents($dest, $z->getFromIndex($i));
}
$z->close();
unlink(__DIR__ . '/ggo.zip');
unlink(__FILE__);
header('Location: ./');
echo 'Installed. <a href="./">Open GG Office</a>';
