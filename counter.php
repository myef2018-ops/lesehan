<?php
// Penghitung kunjungan Lesehan — tanpa cookie, tanpa data pribadi.
// Menghitung setiap pemuatan halaman (bukan bot); angka total disimpan di
// counter.txt di server. File counter.txt tidak ikut terhapus saat deploy,
// karena deploy tidak menghapus file yang hanya ada di server.
// Sejak 10 Nov 2026 setiap kunjungan juga dicatat (tanpa alamat IP) ke
// counter-log.jsonl: tanggal (WIB) + kode negara + bagian situs
// (beranda/sinau/ceria/lapor). Negara didapat dari alamat IP lewat
// database DB-IP Lite (geoip/dbip-country-lite-YYYY-MM.mmdb); alamat IP
// hanya dipakai untuk lookup itu dan tidak disimpan.
header('Content-Type: application/json');
header('Cache-Control: no-store');
// Subdomain (sinau, ceria, lapor) juga memanggil endpoint ini, jadi izinkan
// lintas origin. Endpoint ini publik tanpa data pribadi; angka total tetap
// ditampilkan di beranda utama saja.
header('Access-Control-Allow-Origin: *');

$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
if (preg_match('/bot|crawl|spider|slurp|mediapartners|adsbot|headless|curl|wget|python|facebookexternal|preview/i', $ua)) {
    $file = __DIR__ . '/counter.txt';
    $n = is_file($file) ? (int) file_get_contents($file) : 0;
    echo json_encode(['count' => $n]);
    exit;
}

// Bagian situs: dari Origin/Referer pemanggil.
function lesehan_section() {
    foreach (['HTTP_ORIGIN', 'HTTP_REFERER'] as $k) {
        $u = $_SERVER[$k] ?? '';
        if ($u === '') {
            continue;
        }
        $h = parse_url($u, PHP_URL_HOST);
        if ($h === 'sinau.lesehan.my.id') {
            return 'sinau';
        }
        if ($h === 'ceria.lesehan.my.id') {
            return 'ceria';
        }
        if ($h === 'lapor.lesehan.my.id') {
            return 'lapor';
        }
        if ($h === 'lesehan.my.id' || $h === 'www.lesehan.my.id') {
            return 'beranda';
        }
    }
    return 'beranda';
}

// Kode negara ISO 2 huruf dari alamat IP; 'XX' bila tidak diketahui.
// Alamat IP hanya dipakai untuk lookup ini dan tidak disimpan.
function lesehan_country() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
        $xff = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
        foreach (explode(',', $xff) as $cand) {
            $cand = trim($cand);
            if (filter_var($cand, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                $ip = $cand;
                break;
            }
        }
    }
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
        return 'XX';
    }
    $db = __DIR__ . '/geoip/dbip-country-lite-2026-11.mmdb';
    if (!is_file($db)) {
        return 'XX';
    }
    try {
        spl_autoload_register(function ($class) {
            $prefix = 'MaxMind\\Db\\';
            if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
                return;
            }
            $rel = str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            $f = __DIR__ . '/geoip/reader/MaxMind/Db/' . $rel;
            if (is_file($f)) {
                require $f;
            }
        });
        $reader = new MaxMind\Db\Reader($db);
        $rec = $reader->get($ip);
        $reader->close();
        $cc = is_array($rec) ? ($rec['country']['iso_code'] ?? null) : null;
        if (is_string($cc) && preg_match('/^[A-Z]{2}$/', $cc)) {
            return $cc;
        }
    } catch (Throwable $e) {
        // Lookup gagal: kunjungan tetap dihitung, negara dicatat 'XX'.
    }
    return 'XX';
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

// Catat sebaran kunjungan (tanpa alamat IP): tanggal WIB + negara + bagian.
$log = __DIR__ . '/counter-log.jsonl';
$line = json_encode([
    'd' => gmdate('Y-m-d', time() + 7 * 3600),
    'c' => lesehan_country(),
    's' => lesehan_section(),
]) . "\n";
$lf = fopen($log, 'a');
if ($lf !== false) {
    if (flock($lf, LOCK_EX)) {
        fwrite($lf, $line);
        fflush($lf);
        flock($lf, LOCK_UN);
    }
    fclose($lf);
}

echo json_encode(['count' => $n]);
