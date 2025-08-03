<?php
// Configuración
$root_dir = '../campamentos'; // Enlace simbólico a /srv/samba/campamentos/

// Verificar y sanitizar el parámetro file
if (!isset($_GET['file']) || empty($_GET['file'])) {
    header('HTTP/1.1 404 Not Found');
    exit('Archivo no encontrado');
}

$file_path = $_GET['file'];
$full_path = $root_dir . '/' . $file_path;

// Verificar que el archivo exista y esté dentro del directorio raíz
if (!file_exists($full_path) || !is_file($full_path) || strpos(realpath($full_path), realpath($root_dir)) !== 0) {
    header('HTTP/1.1 404 Not Found');
    exit('Archivo no encontrado');
}

// Verificar que sea un PDF
$file_ext = strtolower(pathinfo($full_path, PATHINFO_EXTENSION));
if ($file_ext !== 'pdf') {
    header('HTTP/1.1 403 Forbidden');
    exit('Tipo de archivo no permitido');
}

// Servir el PDF
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . basename($full_path) . '"');
header('Content-Length: ' . filesize($full_path));
header('Accept-Ranges: bytes');
header('Cache-Control: public, max-age=3600');

readfile($full_path);
exit;
?>

