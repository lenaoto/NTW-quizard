<?php
header('Content-Type: application/json');

$trazenaRijec = strtolower($_GET['rijec'] ?? '');

$xml = simplexml_load_file('rjecnik.xml');

foreach ($xml->rijec as $r) {
    if (strtolower($r->naziv) === $trazenaRijec) {
        echo json_encode([
            'rijec' => (string)$r->naziv,
            'definicija' => (string)$r->definicija
        ]);
        exit;
    }
}

echo json_encode([
    'error' => 'Riječ nije pronađena'
]);
