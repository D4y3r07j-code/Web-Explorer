<?php
// Verificar bloqueo de IP antes de mostrar contenido
require_once '../src/security-middleware.php';

$securityMiddleware = new SecurityMiddleware();
$client_ip = $securityMiddleware->getClientIP();
$securityMiddleware->checkIP($client_ip);

// Configuración
$root_dir = './campamentos'; // Enlace simbólico a /srv/samba/campamentos/
$title = 'Explorador de Archivos';

// Verificar y sanitizar el parámetro path
if (!isset($_GET['path']) || empty($_GET['path'])) {
    header('Location: index.php');
    exit;
}

$folder_path_param = $_GET['path'];
// Decodificar el nombre de la carpeta para manejar espacios
$folder_path = $root_dir . '/' . $folder_path_param;

// Verificar que la carpeta exista y esté dentro del directorio raíz
if (!file_exists($folder_path) || !is_dir($folder_path) || strpos(realpath($folder_path), realpath($root_dir)) !== 0) {
    header('Location: index.php');
    exit;
}

// Función para contar archivos y subcarpetas
function countContents($dir) {
    // Solo contar archivos PDF
    $files = count(array_filter(glob("$dir/*.pdf"), 'is_file'));
    $subdirs = count(array_filter(glob("$dir/*"), 'is_dir'));
    return ['files' => $files, 'subdirs' => $subdirs];
}

// Obtener solo archivos PDF
$files = array_filter(glob($folder_path . '/*.pdf'), 'is_file');
$subdirs = array_filter(glob($folder_path . '/*'), 'is_dir');

// Determinar si la carpeta está completamente vacía
$is_empty = empty($files) && empty($subdirs);

// Obtener el nombre de la carpeta actual para mostrar en la navegación
$folder_name = basename($folder_path);

// Construir la ruta de navegación
$path_parts = explode('/', $folder_path_param);
$parent_path = '';

if (count($path_parts) > 1) {
    // Si hay más de una parte en la ruta, construir la ruta padre
    array_pop($path_parts); // Eliminar la última parte (carpeta actual)
    $parent_path = implode('/', $path_parts);
    $parent_url = 'folder.php?path=' . urlencode($parent_path);
} else {
    // Si estamos en el primer nivel, volver al índice
    $parent_url = 'index.php';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title . ' - ' . $folder_name; ?></title>
    <?php include './src/theme-handler.php'; ?>
    <link rel="stylesheet" href="./assets/css/styles.css">
    <link rel="stylesheet" href="./assets/css/security.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <!-- Protección contra visualización local -->
    <script src="./assets/js/local-protection.js"></script>
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
                <a href="<?php echo $parent_url; ?>" class="nav-button"><i class="fas fa-arrow-left"></i> <span class="nav-text">Volver</span></a>
                <span class="current-folder"><?php echo $folder_name; ?></span>
            </div>
            
            <div class="search-filter-container">
                <div class="search-container">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="search-input" placeholder="Buscar en esta carpeta...">
                </div>
                
                <div class="filter-sort-container">
                    <!-- Botón de ordenar -->
                    <button id="sort-btn" class="sort-button">
                        <i class="fas fa-sort-amount-down"></i>
                        <span class="filter-text">Ordenar</span>
                    </button>
                    <div id="sort-dropdown" class="sort-dropdown">
                        <div class="filter-section">
                            <h3>Ordenar por</h3>
                            <div class="filter-option" data-filter="sort" data-value="name-asc">
                                <i class="fas fa-sort-alpha-down"></i> Nombre (A-Z)
                            </div>
                            <div class="filter-option" data-filter="sort" data-value="name-desc">
                                <i class="fas fa-sort-alpha-down-alt"></i> Nombre (Z-A)
                            </div>
                            <div class="filter-option" data-filter="sort" data-value="date-desc">
                                <i class="fas fa-clock"></i> Más reciente
                            </div>
                            <div class="filter-option" data-filter="sort" data-value="date-asc">
                                <i class="fas fa-history"></i> Más antiguo
                            </div>
                        </div>
                    </div>
                    
                    <!-- Botón de filtrar -->
                    <button id="filter-btn" class="filter-button">
                        <i class="fas fa-filter"></i>
                        <span class="filter-text">Filtrar</span>
                    </button>
                    <div id="filter-dropdown" class="filter-dropdown">
                        <div class="filter-section">
                            <h3>Filtrar por tipo</h3>
                            <div class="filter-types-container">
                                <div class="filter-option" data-filter="type" data-value="all">
                                    <i class="fas fa-globe"></i> Todos los elementos
                                </div>
                                <?php if (!empty($subdirs)): ?>
                                <div class="filter-option" data-filter="type" data-value="folder">
                                    <i class="fas fa-folder"></i> Carpetas
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($files)): ?>
                                <div class="filter-option" data-filter="type" data-value="pdf">
                                    <i class="fas fa-file-pdf"></i> PDFs
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="filter-section">
                            <h3>Filtrar por fecha</h3>
                            <div class="date-filter-container">
                                <input type="date" id="date-filter" class="date-filter-input" aria-label="Filtrar por fecha">
                                <button id="apply-date-filter" class="filter-date-button">
                                    <i class="fas fa-check"></i> Aplicar
                                </button>
                                <button id="clear-date-filter" class="filter-date-button">
                                    <i class="fas fa-times"></i> Limpiar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if ($is_empty): ?>
                <div class="empty-message">Carpeta vacía</div>
            <?php else: ?>
                <div id="content-container">
                    <div class="unified-container" id="unified-list">
                        <!-- Subcarpetas -->
                        <?php if (!empty($subdirs)): ?>
                            <?php foreach ($subdirs as $subdir): ?>
                                <?php 
                                $subdir_name = basename($subdir);
                                // Contar archivos PDF y subcarpetas
                                $contents = countContents($subdir);
                                $file_count = $contents['files'];
                                $subdir_count = $contents['subdirs'];
                                // URL con parámetro directo
                                $subdir_url = 'folder.php?path=' . urlencode($folder_path_param . '/' . $subdir_name);
                                // Obtener fecha de modificación
                                $subdir_modified = filemtime($subdir);
                                $subdir_modified_str = date('d/m/Y H:i', $subdir_modified);
                                ?>
                                <a href="<?php echo $subdir_url; ?>" class="folder-item" title="<?php echo htmlspecialchars($subdir_name); ?>" data-name="<?php echo htmlspecialchars($subdir_name); ?>" data-type="folder" data-modified="<?php echo $subdir_modified_str; ?>">
                                    <div class="folder-icon">
                                        <i class="fas fa-folder"></i>
                                    </div>
                                    <div class="folder-name"><?php echo $subdir_name; ?></div>
                                    <div class="folder-count">
                                        <?php echo $file_count; ?> PDF<?php echo $file_count != 1 ? 's' : ''; ?>
                                        <?php if ($subdir_count > 0): ?>
                                            - <?php echo $subdir_count; ?> subcarpeta<?php echo $subdir_count != 1 ? 's' : ''; ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="info-button" data-info-type="folder" data-info-name="<?php echo htmlspecialchars($subdir_name); ?>" data-info-modified="<?php echo $subdir_modified_str; ?>" data-info-files="<?php echo $file_count; ?>" data-info-subdirs="<?php echo $subdir_count; ?>">
                                        <i class="fas fa-info"></i>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <!-- Archivos PDF -->
                        <?php if (!empty($files)): ?>
                            <?php foreach ($files as $file): ?>
                                <?php 
                                $file_name = basename($file);
                                $file_modified = filemtime($file);
                                $file_modified_str = date('d/m/Y H:i', $file_modified);
                                $file_size = filesize($file);
                                $file_size_str = formatFileSize($file_size);
                                // URL con parámetro directo
                                $file_url = 'viewer.php?file=' . urlencode($folder_path_param . '/' . $file_name);
                                ?>
                                <a href="<?php echo $file_url; ?>" class="file-item" title="<?php echo htmlspecialchars($file_name); ?>" data-name="<?php echo htmlspecialchars($file_name); ?>" data-type="file" data-extension="pdf">
                                    <div class="file-icon">
                                        <i class="fas fa-file-pdf"></i>
                                    </div>
                                    <div class="file-name"><?php echo $file_name; ?></div>
                                    <div class="file-date"><?php echo $file_modified_str; ?></div>
                                    <div class="info-button" data-info-type="file" data-info-name="<?php echo htmlspecialchars($file_name); ?>" data-info-modified="<?php echo $file_modified_str; ?>" data-info-size="<?php echo $file_size_str; ?>" data-info-extension="PDF">
                                        <i class="fas fa-info"></i>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <!-- Mensaje unificado para cuando no hay resultados -->
                        <div id="no-results-unified" class="no-results">
                            <i class="fas fa-search"></i>
                            <p>No se encontraron elementos que coincidan con tu búsqueda</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="./assets/js/script.js"></script>
    <script src="./assets/js/security.js"></script>
    <!-- Modal de información -->
    <div class="info-modal" id="info-modal">
        <div class="info-modal-content">
            <div class="info-modal-header">
                <div class="info-modal-icon">
                    <i class="fas fa-folder"></i>
                </div>
                <div class="info-modal-title">Información</div>
                <button class="info-modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="info-modal-body">
                <!-- El contenido se llenará dinámicamente con JavaScript -->
            </div>
        </div>
    </div>
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
