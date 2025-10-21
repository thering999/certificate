<?php
/**
 * download_sample_csv.php
 * ดาวน์โหลด sample.csv ด้วย UTF-8 BOM เพื่อให้ Excel เปิดภาษาไทยถูกต้อง
 */

// ส่ง headers เพื่อให้ส่งออกเป็น CSV
header('Content-Type: text/csv; charset=utf-8-sig');
header('Content-Disposition: attachment; filename=sample.csv');
header('Pragma: no-cache');
header('Expires: 0');

// เปิด output stream
$output = fopen('php://output', 'w');

// เขียน UTF-8 BOM
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// เขียนหัวเรื่อง
fputcsv($output, ['ชื่อ-นามสกุล'], ',');

// เขียนข้อมูลตัวอย่าง
$names = [
    'นายสมชาย ใจดี',
    'นางสาวสุดา สวยงาม',
    'นายประเสริฐ ขยัน',
    'นางสมหญิง ไทยยุติธรรม',
];

foreach ($names as $name) {
    fputcsv($output, [$name], ',');
}

fclose($output);
exit;
?>
