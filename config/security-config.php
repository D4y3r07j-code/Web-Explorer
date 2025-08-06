<?php
/**
 * Configuración de seguridad
 * Proyecto Web Explorer
 */

return [
    // Configuración de intentos de acceso
    'max_login_attempts' => 3,
    'lockout_duration' => 300, // 5 minutos en segundos
    'attempt_window' => 900, // 15 minutos para contar intentos
    'session_timeout' => 30, // minutos
    
    // IPs permitidas para admin (vacío = todas)
    'admin_allowed_ips' => [
        '127.0.0.1',
        '::1',
        // Agregar IPs adicionales aquí
        // '192.168.1.100',
    ],
    'enable_ip_blocking' => true,
    
    // Configuración de logs de seguridad
    'security_logging' => [
        'enabled' => true,
        'log_file' => '../logs/security/security_log.txt',
        'log_level' => 'INFO', // DEBUG, INFO, WARNING, ERROR
        'max_log_size' => 10485760, // 10MB
        'rotate_logs' => true,
    ],
    'enable_logging' => true,
    'log_retention_days' => 30,
    
    // Configuración de protección PDF
    'pdf_protection' => [
        'prevent_download' => true,
        'prevent_print' => true,
        'prevent_copy' => true,
        'watermark_enabled' => true,
        'watermark_text' => 'CONFIDENCIAL',
    ],
    'pdf_protection_enabled' => true,
    'disable_right_click' => true,
    
    // Headers de seguridad
    'security_headers' => [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'DENY',
        'X-XSS-Protection' => '1; mode=block',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'",
    ],
    
    // Configuración de autenticación
    'authentication' => [
        'password_min_length' => 8,
        'require_special_chars' => true,
        'require_numbers' => true,
        'require_uppercase' => true,
        'session_regenerate' => true,
        'remember_me_duration' => 2592000, // 30 días
    ],
    
    // Configuración de archivos permitidos
    'file_security' => [
        'allowed_extensions' => ['pdf'],
        'max_file_size' => 52428800, // 50MB
        'scan_for_malware' => false, // Requiere ClamAV
        'quarantine_suspicious' => false,
    ],
    
    // Configuración de rate limiting
    'rate_limiting' => [
        'enabled' => true,
        'max_requests_per_minute' => 60,
        'max_requests_per_hour' => 1000,
        'block_duration' => 300, // 5 minutos
    ],
    
    // Configuración de monitoreo
    'monitoring' => [
        'track_user_activity' => true,
        'track_file_access' => true,
        'track_failed_attempts' => true,
        'alert_on_suspicious_activity' => true,
        'alert_email' => '', // Email para alertas
    ],
    
    // Configuración de backup de seguridad
    'security_backup' => [
        'backup_logs' => true,
        'backup_frequency' => 'daily', // daily, weekly, monthly
        'backup_retention' => 30, // días
        'encrypt_backups' => false,
    ],
    
    // Configuración de desarrollo/debug
    'debug' => [
        'log_all_requests' => false,
        'show_debug_info' => false,
        'allow_debug_mode' => false,
    ],
];
?>
