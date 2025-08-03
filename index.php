<?php
// Configuración
$root_dir = './campamentos'; // Enlace simbólico a /srv/samba/campamentos/
$title = 'Explorador de Archivos';

// Verificar que el directorio exista
if (!file_exists($root_dir) || !is_dir($root_dir)) {
    die("Error: El directorio de campamentos no existe o no es accesible.");
}

// Obtener todas las carpetas en el directorio raíz
$folders = array_filter(glob($root_dir . '/*'), 'is_dir');

// Función para contar archivos y subcarpetas
function countContents($dir) {
    $files = count(array_filter(glob("$dir/*.pdf"), 'is_file'));
    $subdirs = count(array_filter(glob("$dir/*"), 'is_dir'));
    return ['files' => $files, 'subdirs' => $subdirs];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
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
                <button id="view-toggle" class="view-toggle" title="Cambiar vista"><i class="fas fa-list"></i></button>
                <button id="refresh-btn" title="Actualizar"><i class="fas fa-sync-alt"></i></button>
                <button id="theme-toggle" title="Cambiar tema"><i class="fas fa-moon"></i></button>
            </div>
        </div>
    </header>

    <main>
        <div class="container">
            <h2>Selecciona un Campamento</h2>
            
            <div class="search-filter-container">
                <div class="search-container">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="search-input" placeholder="Buscar carpetas...">
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
                                <?php if (!empty($folders)): ?>
                                <div class="filter-option" data-filter="type" data-value="folder">
                                    <i class="fas fa-folder"></i> Carpetas
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
            
            <div id="content-container">
                <div class="unified-container" id="unified-list">
                    <div class="table-header">
                        <div class="header-icon"></div>
                        <div class="header-name">Nombre</div>
                        <div class="header-info">Información</div>
                    </div>
                    
                    <?php foreach ($folders as $folder): ?>
                        <?php 
                        $folder_name = basename($folder);
                        $contents = countContents($folder);
                        $file_count = $contents['files'];
                        $subdir_count = $contents['subdirs'];
                        // URL con parámetro directo
                        $folder_url = 'folder.php?path=' . urlencode($folder_name);
                        // Obtener fecha de modificación para ordenar
                        $folder_modified = filemtime($folder);
                        $folder_modified_str = date('d/m/Y H:i', $folder_modified);
                        ?>
                        <a href="<?php echo $folder_url; ?>" class="folder-item" title="<?php echo htmlspecialchars($folder_name); ?>" data-name="<?php echo htmlspecialchars($folder_name); ?>" data-modified="<?php echo $folder_modified_str; ?>" data-type="folder">
                            <div class="folder-icon">
                                <i class="fas fa-folder"></i>
                            </div>
                            <div class="folder-name"><?php echo $folder_name; ?></div>
                            <div class="folder-count">
                                <?php echo $file_count; ?> archivo<?php echo $file_count != 1 ? 's' : ''; ?>
                                <?php if ($subdir_count > 0): ?>
                                    - <?php echo $subdir_count; ?> subcarpeta<?php echo $subdir_count != 1 ? 's' : ''; ?>
                                <?php endif; ?>
                            </div>
                            <div class="folder-date">
                                <i class="fas fa-clock"></i> <?php echo $folder_modified_str; ?>
                            </div>
                            <div class="info-button" data-info-type="folder" data-info-name="<?php echo htmlspecialchars($folder_name); ?>" data-info-modified="<?php echo $folder_modified_str; ?>" data-info-files="<?php echo $file_count; ?>" data-info-subdirs="<?php echo $subdir_count; ?>">
                                <i class="fas fa-info"></i>
                            </div>
                        </a>
                    <?php endforeach; ?>
                    
                    <?php if (empty($folders)): ?>
                        <div class="empty-message">No hay carpetas disponibles</div>
                    <?php endif; ?>
                    
                    <!-- Mensaje unificado para cuando no hay resultados -->
                    <div id="no-results-unified" class="no-results">
                        <i class="fas fa-search"></i>
                        <p>No se encontraron archivos que coincidan con tu búsqueda</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

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

    <script src="./assets/js/script.js"></script>
    <script src="./assets/js/security.js"></script>
</body>
</html>
