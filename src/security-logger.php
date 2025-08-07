<?php
/**
 * Sistema de registro de eventos de seguridad mejorado
 * Maneja tanto logs de seguridad como logs de acceso
 */

// Configuración - logs fuera del directorio web
$security_log_dir = __DIR__ . '/../logs/security';
$access_log_dir = __DIR__ . '/../logs/access';
$security_log_file = $security_log_dir . '/security_log.txt';
$access_log_file = $access_log_dir . '/access_log.txt';

// Crear directorios de logs si no existen
if (!file_exists($security_log_dir)) {
    mkdir($security_log_dir, 0755, true);
}
if (!file_exists($access_log_dir)) {
    mkdir($access_log_dir, 0755, true);
}

/**
 * Registra un evento de seguridad en el archivo de log
 */
function logSecurityEvent($action, $level = -1, $attempts = 0, $blockDuration = 0) {
    global $security_log_file;
    
    // Obtener información del cliente
    $ip = getClientIP();
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $referer = $_SERVER['HTTP_REFERER'] ?? 'Direct access';
    $requestUri = $_SERVER['REQUEST_URI'] ?? 'Unknown';
    
    // Obtener fecha y hora actual
    $date = date('Y-m-d H:i:s');
    
    // Determinar el tipo de navegador
    $browser = getBrowserInfo($userAgent);
    
    // Formatear el nivel de seguridad
    $levelText = "Warning";
    if ($level >= 0) {
        $levelText = "Nivel " . ($level + 1); // Convertir 0,1,2 a Nivel 1,2,3
    }
    
    // Formatear el mensaje de log
    $logMessage = sprintf(
        "[%s] IP: %s | Action: %s | Attempts: %d | Level: %s | Duration: %s | Browser: %s | URI: %s | Referer: %s\n",
        $date,
        $ip,
        $action,
        $attempts,
        $levelText,
        ($blockDuration > 0) ? formatBlockDuration($blockDuration) : "N/A",
        $browser,
        $requestUri,
        $referer
    );
    
    // Escribir en el archivo de log
    $result = file_put_contents($security_log_file, $logMessage, FILE_APPEND | LOCK_EX);
    
    // Log adicional para debugging
    error_log("Security Event Logged: $action, Level: $levelText, IP: $ip, Attempts: $attempts, File: $security_log_file, Result: " . ($result ? 'SUCCESS' : 'FAILED'));
    
    return $result !== false;
}

/**
 * Registra un acceso a PDF
 */
function logPDFAccess($pdfPath) {
    global $access_log_file;
    
    $ip = getClientIP();
    $date = date('Y-m-d H:i:s');
    
    $logMessage = sprintf(
        "%s - PDF accedido: %s - IP: %s\n",
        $date,
        $pdfPath,
        $ip
    );
    
    return file_put_contents($access_log_file, $logMessage, FILE_APPEND | LOCK_EX) !== false;
}

/**
 * Obtiene la IP real del cliente
 */
function getClientIP() {
    $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    
    foreach ($ip_keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = $_SERVER[$key];
            // Si hay múltiples IPs, tomar la primera
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

/**
 * Obtiene información del navegador a partir del User Agent
 */
function getBrowserInfo($userAgent) {
    if (strpos($userAgent, 'MSIE') !== false || strpos($userAgent, 'Trident') !== false) {
        return 'Internet Explorer';
    } elseif (strpos($userAgent, 'Edge') !== false) {
        return 'Microsoft Edge';
    } elseif (strpos($userAgent, 'Firefox') !== false) {
        return 'Mozilla Firefox';
    } elseif (strpos($userAgent, 'Chrome') !== false && strpos($userAgent, 'Safari') !== false) {
        return 'Google Chrome';
    } elseif (strpos($userAgent, 'Safari') !== false) {
        return 'Safari';
    } elseif (strpos($userAgent, 'Opera') !== false || strpos($userAgent, 'OPR') !== false) {
        return 'Opera';
    } else {
        return substr($userAgent, 0, 50) . '...';
    }
}

/**
 * Formatea la duración del bloqueo en un formato legible
 */
function formatBlockDuration($seconds) {
    if ($seconds < 60) {
        return "$seconds segundos";
    } elseif ($seconds < 3600) {
        $minutes = floor($seconds / 60);
        return "$minutes minutos";
    } else {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        return "$hours horas, $minutes minutos";
    }
}

// Configurar headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * Endpoint para recibir eventos de seguridad desde JavaScript
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'] ?? 'unknown';
    $level = isset($_POST['level']) ? intval($_POST['level']) : -1;
    $attempts = isset($_POST['attempts']) ? intval($_POST['attempts']) : 0;
    $blockDuration = isset($_POST['duration']) ? intval($_POST['duration']) : 0;
    
    // Log para debugging
    error_log("SECURITY LOGGER: Received event - Action=$action, Level=$level, Attempts=$attempts, Duration=$blockDuration");
    
    $success = logSecurityEvent($action, $level, $attempts, $blockDuration);
    
    // Responder con JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Event logged successfully' : 'Failed to log event',
        'debug' => [
            'action' => $action,
            'level' => $level,
            'attempts' => $attempts,
            'duration' => $blockDuration,
            'ip' => getClientIP(),
            'log_file' => $security_log_file,
            'file_writable' => is_writable(dirname($security_log_file))
        ]
    ]);
    exit;
}

// Si se llama directamente para registrar acceso a PDF
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['log_pdf'])) {
    $pdfPath = $_GET['pdf_path'] ?? 'Unknown';
    $success = logPDFAccess($pdfPath);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => $success]);
    exit;
}

// Endpoint para testing - eliminar en producción
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['test_security'])) {
    $testSuccess = logSecurityEvent('test event', 2, 9, 1200);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $testSuccess,
        'message' => 'Test security event logged',
        'file_exists' => file_exists($security_log_file),
        'file_writable' => is_writable(dirname($security_log_file)),
        'log_file_path' => $security_log_file
    ]);
    exit;
}

// Si no hay parámetros válidos, mostrar información de debug
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'Security Logger Active',
        'log_file' => $security_log_file,
        'file_exists' => file_exists($security_log_file),
        'dir_writable' => is_writable(dirname($security_log_file)),
        'current_ip' => getClientIP(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}
?>
