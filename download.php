<?php
require_once 'config.php';

if (!isset($_GET['file'])) {
    header('HTTP/1.0 400 Bad Request');
    echo '缺少文件参数';
    exit;
}

$fileName = basename($_GET['file']);
$downloadName = isset($_GET['name']) ? basename($_GET['name']) : $fileName;

$filePath = UPLOAD_DIR . $fileName;

if (!file_exists($filePath)) {
    header('HTTP/1.0 404 Not Found');
    echo '文件不存在';
    exit;
}

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

readfile($filePath);
