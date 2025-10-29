<?php
require_once __DIR__ . '/../classes/database.php';
require_once __DIR__ . '/../classes/sale.php';

$db = new Database();
$saleModel = new Sale($db);

$rows = $saleModel->salesLastDays(365);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=bulkas_sales.csv');

$out = fopen('php://output', 'w');
fputcsv($out, ['date', 'total']);
foreach ($rows as $r) {
    fputcsv($out, [$r['day'], $r['total']]);
}
fclose($out);
exit;
