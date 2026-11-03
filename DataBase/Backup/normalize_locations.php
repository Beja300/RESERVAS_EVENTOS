<?php

/**
 * Normaliza las ubicaciones existentes (backfill único).
 *
 * Rellena el distrito (y el cantón) cuando vienen vacíos en tblocation
 * usando el dataset oficial de Costa Rica (App/Data/locations.php).
 *
 * Estados que corrige:
 *   - distrito vacío -> primer distrito real de ese cantón.
 *   - cantón vacío   -> primer cantón real de esa provincia.
 *
 * Uso (solo por línea de comandos):
 *   php normalize_locations.php --dry-run   # solo muestra qué corregiría
 *   php normalize_locations.php             # aplica los cambios
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este script solo puede ejecutarse por línea de comandos.');
}

require_once __DIR__ . '/../../Configuration/DataBase.php';

$dryRun = in_array('--dry-run', $argv ?? [], true);

$locations = require __DIR__ . '/../../App/Data/locations.php';
$pdo = DataBase::getConnection();

$rows = $pdo
    ->query('SELECT tblocationid, tblocationprovince, tblocationcanton, tblocationdistrict FROM tblocation ORDER BY tblocationid')
    ->fetchAll(PDO::FETCH_ASSOC);

$updates = 0;
$skipped = 0;

foreach ($rows as $row) {
    $id = (int) $row['tblocationid'];
    $province = trim((string) $row['tblocationprovince']);
    $canton = trim((string) $row['tblocationcanton']);
    $district = trim((string) $row['tblocationdistrict']);

    if ($province === '') {
        $skipped++;
        continue;
    }

    $newCanton = $canton;
    if ($newCanton === '' && isset($locations[$province])) {
        $newCanton = (string) array_key_first($locations[$province]);
    }

    $newDistrict = $district;
    if ($newDistrict === '' && $newCanton !== '' && !empty($locations[$province][$newCanton] ?? [])) {
        $newDistrict = (string) $locations[$province][$newCanton][0];
    }

    if ($newCanton === $canton && $newDistrict === $district) {
        continue;
    }

    if ($dryRun) {
        $before = $province . ' · ' . ($canton !== '' ? $canton : '?') . ' · ' . ($district !== '' ? $district : '?');
        $after = $province . ' · ' . ($newCanton !== '' ? $newCanton : '?') . ' · ' . ($newDistrict !== '' ? $newDistrict : '?');
        printf("  #%d  %s  →  %s\n", $id, $before, $after);
    } else {
        $pdo->prepare(
            'UPDATE tblocation SET tblocationcanton = :canton, tblocationdistrict = :district WHERE tblocationid = :id'
        )->execute([
            ':canton'   => $newCanton,
            ':district' => $newDistrict,
            ':id'       => $id,
        ]);
    }

    $updates++;
}

if ($dryRun) {
    echo $updates === 0
        ? "Nada que corregir.\n"
        : "---\n{$updates} fila(s) se corregirían.\n";
} else {
    echo "Ubicaciones normalizadas: {$updates} (omitidas por provincia vacía: {$skipped}).\n";
}