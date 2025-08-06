<?php
/**
 * Configuración principal de la aplicación
 * Proyecto Web Explorer
 */

return [
    // Información de la aplicación
    'app_name' => 'Web Explorer',
    'app_version' => '2.0.0',
    'app_description' => 'Explorador web seguro para archivos PDF',
    'site_title' => 'Web Explorer', // Nuevo valor agregado
    
    // Configuración de archivos
    'files_directory' => '/srv/samba/campamentos/',
    'public_files_link' => 'campamentos',
    'max_file_size' => 50, // Actualizado a 50MB
    'allowed_extensions' => ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'txt', 'doc', 'docx'], // Actualizado con nuevas extensiones
    
    // Configuración de interfaz
    'items_per_page' => 20, // Actualizado a 20
    'default_theme' => 'dark',
    'show_file_info' => true,
    'show_folder_count' => true,
    'enable_thumbnails' => true, // Nuevo valor agregado
    
    // Configuración de PDF.js
    'pdfjs_path' => '/includes/pdfjs/web/viewer.html',
    'pdf_max_pages' => 1000,
    'pdf_default_zoom' => 'auto',
    
    // Configuración de cache
    'enable_cache' => true,
    'cache_duration' => 3600, // 1 hora
    'cache_directory' => '../cache/',
    
    // Configuración de logs
    'log_directory' => '../logs/',
    'log_max_size' => 10485760, // 10MB
    'log_retention_days' => 30,
    'logs_enabled' => true, // Nuevo valor agregado
    
    // URLs y rutas
    'base_url' => '/Proyecto-Web-Explorer/',
    'public_url' => '/Proyecto-Web-Explorer/public/',
    'admin_url' => '/Proyecto-Web-Explorer/admin/',
    'upload_path' => '../uploads/', // Nuevo valor agregado
    
    // Configuración de desarrollo
    'debug_mode' => false,
    'show_errors' => false,
    'log_queries' => false,
    
    // Configuración de sesión
    'session_timeout' => 3600, // 1 hora
    'session_name' => 'WebExplorerSession',
    'session_secure' => false, // Cambiar a true en HTTPS
    
    // Configuración de backup
    'auto_backup' => true,
    'backup_directory' => '../backups/',
    'backup_retention' => 7, // días
];
?>
