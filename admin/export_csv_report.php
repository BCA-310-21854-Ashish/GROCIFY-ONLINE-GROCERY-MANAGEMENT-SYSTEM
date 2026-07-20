<?php

session_start();
require_once '../config/db.php';
require_once '../config/report_helper.php';
$date=$_GET['date'] ?? date('Y-m-d');
$data=getDailyReportData($date);
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="daily_report_'.$date.'.csv"');
$out=fopen('php://output','w');
fputcsv($out,['Date','Total Sales','Total Orders','Average Order Value','Products Sold']);
fputcsv($out,[$data['date'],$data['total_sales'],$data['total_orders'],$data['avg_order_value'],$data['total_products_sold']]);
fputcsv($out,[]);
fputcsv($out,['Top Products']);
fputcsv($out,['Product','Qty Sold','Revenue']);
foreach($data['top_products'] as $p){fputcsv($out,[$p['name'],$p['qty_sold'],$p['revenue']]);}
fclose($out);
