<?php
// Configuración de seguridad
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');

// Configuración
$root_dir = '../public/campamentos'; // Ruta corregida desde src/
$allowed_extensions = ['pdf'];

// Verificar parámetro file
if (!isset($_GET['file']) || empty($_GET['file'])) {
    http_response_code(404);
    die('Archivo no especificado');
}

$file_path = $_GET['file'];
$full_path = $root_dir . '/' . $file_path;

// Verificaciones de seguridad
if (!file_exists($full_path)) {
    http_response_code(404);
    die('Archivo no encontrado');
}

if (!is_file($full_path)) {
    http_response_code(403);
    die('Ruta no válida');
}

// Verificar que esté dentro del directorio permitido
$real_root = realpath($root_dir);
$real_file = realpath($full_path);

if (!$real_root || !$real_file || strpos($real_file, $real_root) !== 0) {
    http_response_code(403);
    die('Acceso denegado');
}

// Verificar extensión
$file_ext = strtolower(pathinfo($full_path, PATHINFO_EXTENSION));
if (!in_array($file_ext, $allowed_extensions)) {
    http_response_code(403);
    die('Tipo de archivo no permitido');
}

// Registrar acceso (opcional)
$log_entry = date('Y-m-d H:i:s') . " - PDF accedido: " . $file_path . " - IP: " . $_SERVER['REMOTE_ADDR'] . "\n";
file_put_contents('../logs/access/access_log.txt', $log_entry, FILE_APPEND | LOCK_EX);

// Configurar headers para PDF
header('Content-Type: application/pdf');
header('Content-Length: ' . filesize($full_path));
header('Content-Disposition: inline; filename="' . basename($full_path) . '"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

// Enviar archivo
readfile($full_path);
exit;
?>
