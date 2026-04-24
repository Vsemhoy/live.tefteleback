<?php
$csvPath = __DIR__ . '/SQL/section_id_map.csv';
$outPath = __DIR__ . '/SQL/evt_sections_ensure_hostmapped.sql';
$rows = [];
if (($h = fopen($csvPath, 'rb')) !== false) {
    $header = fgetcsv($h);
    while (($r = fgetcsv($h)) !== false) {
        $row = array_combine($header, $r);
        $rows[$row['target_section_id']] = $row;
    }
    fclose($h);
}
$lines = [];
$lines[] = 'START TRANSACTION;';
$lines[] = '-- Ensure section ids required by evt_events_import_hostmapped.sql exist.';
foreach ($rows as $r) {
    $id = str_replace("'", "''", $r['target_section_id']);
    $name = str_replace("'", "''", $r['target_name']);
    $lines[] = "INSERT INTO `evt_sections` (`id`,`user_id`,`name`,`literals`,`description`,`sort_order`,`access`,`color`,`bgcolor`,`icon`,`decor`,`seo`,`is_archived`,`is_default`,`created_at`,`updated_at`)";
    $lines[] = "SELECT '$id','01KNHVWYBVJT0X6QN30HJ4VDVJ','$name',NULL,NULL,0,1,NULL,NULL,NULL,NULL,NULL,0,0,NOW(),NOW()";
    $lines[] = "WHERE NOT EXISTS (SELECT 1 FROM `evt_sections` WHERE `id`='$id');";
    $lines[] = '';
}
$lines[] = 'COMMIT;';
file_put_contents($outPath, implode(PHP_EOL, $lines) . PHP_EOL);
echo "written\n";
?>
