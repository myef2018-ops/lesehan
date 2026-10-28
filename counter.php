<?php
// Penghitung kunjungan Lesehan — tanpa cookie, tanpa data pribadi.
// Menghitung setiap pemuatan halaman beranda (bukan bot); angka disimpan di
// counter.txt di server. File counter.txt tidak ikut terhapus saat deploy,
// karena deploy tidak menghapus file yang hanya ada di server.
header('Content-Type: application/json');
header('Cache-Control: no-store');

$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
if (preg_match('/bot|crawl|spider|slurp|mediapartners|adsbot|headless|curl|wget|python|facebookexternal|preview/i', $ua)) {
    $file = __DIR__ . '/counter.txt';
    $n = is_file($file) ? (int) file_get_contents($file) : 0;
    echo json_encode(['count' => $n]);
    exit;
}

$file = __DIR__ . '/counter.txt';
$fp = fopen($file, 'c+');
if ($fp === false) {
    http_response_code(500);
    echo json_encode(['count' => 0]);
    exit;
}
$n = 0;
if (flock($fp, LOCK_EX)) {
    $n = (int) stream_get_contents($fp);
    $n++;
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, (string) $n);
    fflush($fp);
    flock($fp, LOCK_UN);
} else {
    $n = (int) stream_get_contents($fp);
}
fclose($fp);
echo json_encode(['count' => $n]);
