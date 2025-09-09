<?php
// public/image.php - Secure image server for private assets
if (!isset($_GET['path']) || empty($_GET['path'])) {
    http_response_code(400);
    exit('Invalid path');
}

$path = $_GET['path'];

// Sanitize: Only allow paths starting with 'assets/' and no traversal
if (strpos($path, '../') !== false || strpos($path, '..\\') !== false || !preg_match('/^assets\/[^.\/\\]+\.(jpg|jpeg|png|gif|webp)$/i', $path)) {
    http_response_code(403);
    exit('Forbidden');
}

// Resolve full server path (from public dir, go up to project root)
$fullPath = __DIR__ . '/../' . $path;  // e.g., /project/assets/hero1.jpg

if (!file_exists($fullPath) || !is_file($fullPath)) {
    // Fallback to public placeholder
    $fullPath = __DIR__ . '/assets/placeholder.png';  // Assume this exists in public/assets/
    if (!file_exists($fullPath)) {
        http_response_code(404);
        exit;
    }
}

// Get MIME type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $fullPath);
finfo_close($finfo);

// Headers for image
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($fullPath));
header('Cache-Control: public, max-age=86400');  // Cache 1 day
header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));

// Output image
readfile($fullPath);
exit;
?>