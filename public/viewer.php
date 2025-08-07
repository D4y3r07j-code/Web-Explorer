<?php
// Verificar bloqueo de IP antes de mostrar contenido
require_once '../src/security-middleware.php';

$securityMiddleware = new SecurityMiddleware();
$client_ip = $securityMiddleware->getClientIP();
$securityMiddleware->checkIP($client_ip);

// Configuración
$root_dir = './campamentos'; // Enlace simbólico a /srv/samba/campamentos/
$title = 'Explorador de Archivos';

// Verificar y sanitizar el parámetro file
if (!isset($_GET['file']) || empty($_GET['file'])) {
    header('Location: index.php');
    exit;
}

$file_path = $_GET['file'];
$full_path = $root_dir . '/' . $file_path;

// Verificar que el archivo exista y esté dentro del directorio raíz
if (!file_exists($full_path) || !is_file($full_path) || strpos(realpath($full_path), realpath($root_dir)) !== 0) {
    header('Location: index.php');
    exit;
}

$file_name = basename($full_path);
$folder_path = dirname($file_path);
$file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

// Solo permitir archivos PDF
if ($file_ext !== 'pdf') {
    header('Location: index.php');
    exit;
}

// Obtener información del archivo
$file_size = filesize($full_path);
$file_modified = filemtime($full_path);

// Crear una URL segura para el PDF
$pdf_url = './src/get-pdf.php?file=' . urlencode($file_path);

// URL para volver a la carpeta
$back_url = 'folder.php?path=' . urlencode($folder_path);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title . ' - ' . $file_name; ?></title>
    <?php include '../src/theme-handler.php'; ?>
    <link rel="stylesheet" href="./assets/css/styles.css">
    <link rel="stylesheet" href="./assets/css/security.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>
<body>
    <header>
        <div class="container">
            <h1><?php echo $title; ?></h1>
            <div class="header-icons">
                <button id="refresh-btn" title="Actualizar"><i class="fas fa-sync-alt"></i></button>
                <button id="theme-toggle" title="Cambiar tema"><i class="fas fa-moon"></i></button>
            </div>
        </div>
    </header>

    <main>
        <div class="container">
            <div class="navigation-bar">
                <a href="index.php" class="nav-button"><i class="fas fa-home"></i> <span class="nav-text">Inicio</span></a>
                <div class="file-name-display"><?php echo $file_name; ?></div>
                <button id="close-viewer" class="close-button" onclick="window.location.href='<?php echo $back_url; ?>'"><i class="fas fa-times"></i></button>
            </div>
            
            <div class="viewer-container">
                <!-- Usar el visor incorporado de PDF.js -->
                <iframe src="./includes/pdfjs/web/viewer.html?file=<?php echo urlencode('/Web-Explorer/' . $pdf_url); ?>" 
                        width="100%" 
                        height="100%" 
                        style="border: none; min-height: 70vh;">
                </iframe>
            </div>
        </div>
    </main>

    <script src="./assets/js/script.js"></script>
    <script src="./assets/js/security.js"></script>
</body>
</html>

<?php
// Función para formatear el tamaño del archivo
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}
?>
