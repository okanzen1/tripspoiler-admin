<?php

/*
 * database/data/cities.tsv.gz dosyasını üretir.
 * Kaynak: https://download.geonames.org/export/dump/cities15000.zip (CC BY 4.0)
 * Kullanım: php database/data/build-cities.php cities15000.txt /tmp/cities.tsv
 *           (sonra başlık ekleyip gzip ile cities.tsv.gz yap)
 *
 * "Şehir" = ilçe değil:
 */
// Kural: PPLC/PPLA her zaman; diğerleri (TR hariç) nüfus >= 200k ve 30 km içinde daha büyük seçilmiş şehir yoksa
[$_, $in, $out] = $argv;
$rows = [];
foreach (file($in) as $line) {
    $f = explode("\t", rtrim($line, "\n"));
    if (in_array($f[7], ['PPLX', 'PPLH'])) continue;
    $rows[] = ['id' => $f[0], 'name' => $f[1], 'cc' => $f[8], 'fc' => $f[7], 'pop' => (int) $f[14], 'lat' => (float) $f[4], 'lng' => (float) $f[5]];
}
usort($rows, fn ($a, $b) => $b['pop'] <=> $a['pop']);
$dist = function ($a, $b) {
    $r = M_PI / 180; $dLat = ($b['lat'] - $a['lat']) * $r; $dLng = ($b['lng'] - $a['lng']) * $r;
    $h = sin($dLat / 2) ** 2 + cos($a['lat'] * $r) * cos($b['lat'] * $r) * sin($dLng / 2) ** 2;
    return 12742 * asin(sqrt($h));
};
$kept = [];
// 1) başkent + il/bölge merkezleri
foreach ($rows as $c) if (in_array($c['fc'], ['PPLC', 'PPLA'])) $kept[$c['cc']][] = $c;
// 2) büyük şehirler (banliyö değilse)
foreach ($rows as $c) {
    if (in_array($c['fc'], ['PPLC', 'PPLA']) || $c['cc'] === 'TR' || $c['pop'] < 200000) continue;
    foreach ($kept[$c['cc']] ?? [] as $k) if ($k['pop'] > $c['pop'] && $dist($k, $c) < 30) continue 2;
    $kept[$c['cc']][] = $c;
}
$fh = fopen($out, 'w');
foreach ($kept as $cc => $list) {
    usort($list, fn ($a, $b) => $b['pop'] <=> $a['pop']);
    foreach ($list as $c) fwrite($fh, implode("\t", [$c['id'], $cc, $c['name'], $c['pop'], $c['lat'], $c['lng']]) . "\n");
}
