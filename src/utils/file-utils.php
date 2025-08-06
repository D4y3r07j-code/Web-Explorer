<?php
/**
 * Utilidades para manejo de archivos
 * Proyecto Web Explorer
 */

/**
 * Formatear el tamaño de archivo en formato legible
 */
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

/**
 * Verificar si un archivo es PDF válido
 */
function isValidPDF($filePath) {
    if (!file_exists($filePath)) {
        return false;
    }
    
    // Verificar extensión
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    if ($extension !== 'pdf') {
        return false;
    }
    
    // Verificar header del archivo
    $handle = fopen($filePath, 'rb');
    if (!$handle) {
        return false;
    }
    
    $header = fread($handle, 4);
    fclose($handle);
    
    return $header === '%PDF';
}

/**
 * Obtener información detallada de un archivo PDF
 */
function getPDFInfo($filePath) {
    if (!isValidPDF($filePath)) {
        return false;
    }
    
    $info = [
        'name' => basename($filePath),
        'size' => filesize($filePath),
        'size_formatted' => formatFileSize(filesize($filePath)),
        'modified' => filemtime($filePath),
        'modified_formatted' => date('d/m/Y H:i', filemtime($filePath)),
        'extension' => 'pdf',
        'type' => 'application/pdf',
        'is_readable' => is_readable($filePath),
    ];
    
    return $info;
}

/**
 * Contar archivos PDF en un directorio
 */
function countPDFsInDirectory($directory) {
    if (!is_dir($directory)) {
        return 0;
    }
    
    $count = 0;
    $files = scandir($directory);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $filePath = $directory . DIRECTORY_SEPARATOR . $file;
        if (is_file($filePath) && isValidPDF($filePath)) {
            $count++;
        }
    }
    
    return $count;
}

/**
 * Contar subcarpetas en un directorio
 */
function countSubdirectoriesInDirectory($directory) {
    if (!is_dir($directory)) {
        return 0;
    }
    
    $count = 0;
    $files = scandir($directory);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $filePath = $directory . DIRECTORY_SEPARATOR . $file;
        if (is_dir($filePath)) {
            $count++;
        }
    }
    
    return $count;
}

/**
 * Obtener contenido de un directorio (solo PDFs y carpetas)
 */
function getDirectoryContents($directory, $sortBy = 'name', $sortOrder = 'asc') {
    if (!is_dir($directory)) {
        return [];
    }
    
    $items = [];
    $files = scandir($directory);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $filePath = $directory . DIRECTORY_SEPARATOR . $file;
        
        if (is_dir($filePath)) {
            // Es una carpeta
            $items[] = [
                'name' => $file,
                'type' => 'folder',
                'path' => $filePath,
                'size' => 0,
                'modified' => filemtime($filePath),
                'pdf_count' => countPDFsInDirectory($filePath),
                'subdir_count' => countSubdirectoriesInDirectory($filePath),
            ];
        } elseif (isValidPDF($filePath)) {
            // Es un PDF válido
            $items[] = [
                'name' => $file,
                'type' => 'pdf',
                'path' => $filePath,
                'size' => filesize($filePath),
                'modified' => filemtime($filePath),
                'extension' => 'pdf',
            ];
        }
    }
    
    // Ordenar elementos
    usort($items, function($a, $b) use ($sortBy, $sortOrder) {
        // Carpetas siempre primero
        if ($a['type'] === 'folder' && $b['type'] !== 'folder') return -1;
        if ($a['type'] !== 'folder' && $b['type'] === 'folder') return 1;
        
        $result = 0;
        switch ($sortBy) {
            case 'name':
                $result = strcasecmp($a['name'], $b['name']);
                break;
            case 'size':
                $result = $a['size'] - $b['size'];
                break;
            case 'modified':
                $result = $a['modified'] - $b['modified'];
                break;
            default:
                $result = strcasecmp($a['name'], $b['name']);
        }
        
        return $sortOrder === 'desc' ? -$result : $result;
    });
    
    return $items;
}

/**
 * Verificar si una ruta está dentro del directorio permitido
 */
function isPathSafe($path, $basePath) {
    $realPath = realpath($path);
    $realBasePath = realpath($basePath);
    
    if (!$realPath || !$realBasePath) {
        return false;
    }
    
    return strpos($realPath, $realBasePath) === 0;
}

/**
 * Limpiar nombre de archivo para evitar problemas de seguridad
 */
function sanitizeFileName($filename) {
    // Remover caracteres peligrosos
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
    
    // Evitar nombres de archivo problemáticos
    $dangerous = ['..', './', '\\', ':', '*', '?', '"', '<', '>', '|'];
    $filename = str_replace($dangerous, '', $filename);
    
    return $filename;
}

/**
 * Crear directorio de forma segura
 */
function createDirectorySafe($path, $permissions = 0755) {
    if (file_exists($path)) {
        return is_dir($path);
    }
    
    return mkdir($path, $permissions, true);
}

/**
 * Obtener el icono CSS para un tipo de archivo
 */
function getFileIcon($extension) {
    $extension = strtolower($extension);
    
    switch ($extension) {
        case 'pdf':
            return 'fas fa-file-pdf text-danger';
        default:
            return 'fas fa-file';
    }
}

/**
 * Verificar permisos de archivo
 */
function checkFilePermissions($filePath) {
    return [
        'exists' => file_exists($filePath),
        'readable' => is_readable($filePath),
        'writable' => is_writable($filePath),
        'executable' => is_executable($filePath),
        'size' => file_exists($filePath) ? filesize($filePath) : 0,
        'permissions' => file_exists($filePath) ? substr(sprintf('%o', fileperms($filePath)), -4) : null,
    ];
}
?>
