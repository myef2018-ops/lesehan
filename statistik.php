<?php
// Sebaran kunjungan Lesehan — dibaca dari counter.txt (total) dan
// counter-log.jsonl (tanggal + negara + bagian). Tanpa data pribadi:
// yang tercatat hanya kode negara, bukan alamat IP pengunjung.
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');

$total = 0;
$f = __DIR__ . '/counter.txt';
if (is_file($f)) {
    $total = (int) file_get_contents($f);
}

$byCountry = [];
$byDay = [];
$bySection = [];
$logged = 0;
$log = __DIR__ . '/counter-log.jsonl';
if (is_file($log)) {
    foreach (file($log, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $r = json_decode($line, true);
        if (!is_array($r) || empty($r['c']) || empty($r['d'])) {
            continue;
        }
        $logged++;
        $byCountry[$r['c']] = ($byCountry[$r['c']] ?? 0) + 1;
        $byDay[$r['d']] = ($byDay[$r['d']] ?? 0) + 1;
        $s = $r['s'] ?? 'beranda';
        $bySection[$s] = ($bySection[$s] ?? 0) + 1;
    }
}
arsort($byCountry);
krsort($byDay);
arsort($bySection);

$names = [
    'ID' => 'Indonesia', 'MY' => 'Malaysia', 'SG' => 'Singapura',
    'US' => 'Amerika Serikat', 'GB' => 'Britania Raya', 'AU' => 'Australia',
    'NL' => 'Belanda', 'DE' => 'Jerman', 'FR' => 'Prancis', 'JP' => 'Jepang',
    'KR' => 'Korea Selatan', 'CN' => 'Tiongkok', 'TW' => 'Taiwan',
    'HK' => 'Hong Kong', 'IN' => 'India', 'CA' => 'Kanada', 'BR' => 'Brasil',
    'RU' => 'Rusia', 'TR' => 'Turki', 'SA' => 'Arab Saudi', 'AE' => 'Uni Emirat Arab',
    'EG' => 'Mesir', 'NG' => 'Nigeria', 'ZA' => 'Afrika Selatan', 'KE' => 'Kenya',
    'TH' => 'Thailand', 'VN' => 'Vietnam', 'PH' => 'Filipina', 'BN' => 'Brunei',
    'KH' => 'Kamboja', 'LA' => 'Laos', 'MM' => 'Myanmar', 'BD' => 'Bangladesh',
    'PK' => 'Pakistan', 'LK' => 'Sri Lanka', 'NP' => 'Nepal', 'AF' => 'Afganistan',
    'IQ' => 'Irak', 'IR' => 'Iran', 'JO' => 'Yordania', 'KW' => 'Kuwait',
    'QA' => 'Qatar', 'OM' => 'Oman', 'YE' => 'Yaman', 'IL' => 'Israel',
    'PS' => 'Palestina', 'SY' => 'Suriah', 'LB' => 'Lebanon', 'UZ' => 'Uzbekistan',
    'KZ' => 'Kazakhstan', 'MN' => 'Mongolia', 'ES' => 'Spanyol', 'IT' => 'Italia',
    'PT' => 'Portugal', 'CH' => 'Swiss', 'AT' => 'Austria', 'BE' => 'Belgia',
    'SE' => 'Swedia', 'NO' => 'Norwegia', 'DK' => 'Denmark', 'FI' => 'Finlandia',
    'PL' => 'Polandia', 'CZ' => 'Ceko', 'HU' => 'Hungaria', 'GR' => 'Yunani',
    'IE' => 'Irlandia', 'NZ' => 'Selandia Baru', 'MX' => 'Meksiko', 'AR' => 'Argentina',
    'CL' => 'Chili', 'CO' => 'Kolombia', 'PE' => 'Peru', 'VE' => 'Venezuela',
    'UA' => 'Ukraina', 'BY' => 'Belarus', 'XX' => 'Tidak diketahui',
];
$sections = [
    'beranda' => 'Beranda', 'sinau' => 'Sinau',
    'ceria' => 'Belajar Ceria', 'lapor' => 'Lapor Pak Rektor',
];
function esc($s) {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sebaran Kunjungan — Lesehan</title>
<style>
body{margin:0;background:#faf7f2;color:#22312b;font-family:"Avenir Next",Avenir,-apple-system,"Segoe UI",sans-serif;font-size:18px;line-height:1.6}
.wrap{max-width:760px;margin:0 auto;padding:40px 20px 64px}
h1{color:#173f35;font-size:32px;margin:0 0 4px}
p.lede{color:#5b6b62;margin:0 0 28px}
h2{color:#173f35;font-size:22px;margin:36px 0 10px}
table{width:100%;border-collapse:collapse;background:#fff;border:1px solid #e3ddd2;border-radius:10px;overflow:hidden}
th,td{padding:10px 14px;text-align:left;border-bottom:1px solid #efe9dd}
th{background:#173f35;color:#f0f8f2;font-weight:600}
tr:last-child td{border-bottom:0}
td.num,th.num{text-align:right}
.bar{height:8px;background:#e3ddd2;border-radius:4px;overflow:hidden;min-width:80px}
.bar>span{display:block;height:100%;background:#173f35}
.note{color:#5b6b62;font-size:15px;margin-top:28px}
a{color:#173f35}
</style>
</head>
<body>
<div class="wrap">
<h1>Sebaran Kunjungan Lesehan</h1>
<p class="lede">Total kunjungan: <strong><?= number_format($total, 0, ',', '.') ?></strong> &middot; Tercatat sebarannya: <strong><?= number_format($logged, 0, ',', '.') ?></strong> kunjungan.</p>

<?php if ($logged === 0): ?>
<p>Belum ada data sebaran. Pencatatan sebaran dimulai 10 November 2026; kunjungan sebelumnya hanya tersimpan sebagai angka total.</p>
<?php else: ?>

<h2>Per negara</h2>
<table>
<tr><th>Negara</th><th class="num">Kunjungan</th><th class="num">Bagian</th><th></th></tr>
<?php $max = max($byCountry); foreach ($byCountry as $cc => $n): $pct = $logged ? round($n / $logged * 100, 1) : 0; ?>
<tr>
<td><?= esc($names[$cc] ?? $cc) ?> (<?= esc($cc) ?>)</td>
<td class="num"><?= number_format($n, 0, ',', '.') ?></td>
<td class="num"><?= number_format($pct, 1, ',', '.') ?>%</td>
<td><div class="bar"><span style="width:<?= max(2, round($n / $max * 100)) ?>%"></span></div></td>
</tr>
<?php endforeach; ?>
</table>

<h2>Per hari (30 hari terakhir)</h2>
<table>
<tr><th>Tanggal</th><th class="num">Kunjungan</th></tr>
<?php $days = array_slice($byDay, 0, 30, true); foreach ($days as $d => $n): ?>
<tr><td><?= esc($d) ?></td><td class="num"><?= number_format($n, 0, ',', '.') ?></td></tr>
<?php endforeach; ?>
</table>

<h2>Per bagian situs</h2>
<table>
<tr><th>Bagian</th><th class="num">Kunjungan</th></tr>
<?php foreach ($bySection as $s => $n): ?>
<tr><td><?= esc($sections[$s] ?? $s) ?></td><td class="num"><?= number_format($n, 0, ',', '.') ?></td></tr>
<?php endforeach; ?>
</table>

<?php endif; ?>

<p class="note">Sebaran hanya tercatat sejak 10 November 2026; kunjungan sebelumnya hanya tersimpan sebagai angka total. Negara ditentukan dari alamat IP pengunjung — alamat IP tidak disimpan. Negara pengunjung VPN terbaca dari lokasi server VPN-nya.<br>IP Geolocation by <a href="https://db-ip.com" target="_blank" rel="noopener">DB-IP</a>.</p>
<p class="note"><a href="/">Kembali ke Lesehan</a></p>
</div>
</body>
</html>
