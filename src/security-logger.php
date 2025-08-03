<?php
/**
 * Sistema de registro de eventos de seguridad
 * Este archivo maneja el registro de intentos de violación de seguridad
 */

// Configuración
$log_dir = './logs';
$log_file = $log_dir . '/security_log.txt';

// Crear directorio de logs si no existe
if (!file_exists($log_dir)) {
    mkdir($log_dir, 0755, true);
}

/**
 * Registra un evento de seguridad en el archivo de log
 * 
 * @param string $action Acción que intentó realizar el usuario
 * @param int $level Nivel de seguridad aplicado (0-3)
 * @param int $attempts Número de intentos acumulados
 * @param int $blockDuration Duración del bloqueo en segundos (si aplica)
 * @return bool Éxito o fracaso al registrar
 */
function logSecurityEvent($action, $level = -1, $attempts = 0, $blockDuration = 0) {
    global $log_file;
    
    // Obtener información del cliente
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $referer = $_SERVER['HTTP_REFERER'] ?? 'Direct access';
    $requestUri = $_SERVER['REQUEST_URI'] ?? 'Unknown';
    
    // Obtener fecha y hora actual
    $date = date('Y-m-d H:i:s');
    
    // Determinar el tipo de navegador
    $browser = getBrowserInfo($userAgent);
    
    // Formatear el mensaje de log
    $logMessage = sprintf(
        "[%s] IP: %s | Action: %s | Attempts: %d | Level: %s | Duration: %s | Browser: %s | URI: %s | Referer: %s\n",
        $date,
        $ip,
        $action,
        $attempts,
        ($level >= 0) ? "Level $level" : "Warning",
        ($blockDuration > 0) ? formatBlockDuration($blockDuration) : "N/A",
        $browser,
        $requestUri,
        $referer
    );
    
    // Escribir en el archivo de log
    return file_put_contents($log_file, $logMessage, FILE_APPEND | LOCK_EX) !== false;
}

/**
 * Obtiene información del navegador a partir del User Agent
 * 
 * @param string $userAgent User Agent del cliente
 * @return string Información del navegador
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
        return $userAgent;
    }
}

/**
 * Formatea la duración del bloqueo en un formato legible
 * 
 * @param int $seconds Duración en segundos
 * @return string Duración formateada
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

/**
 * Endpoint para recibir eventos de seguridad desde JavaScript
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'] ?? 'unknown';
    $level = isset($_POST['level']) ? intval($_POST['level']) : -1;
    $attempts = isset($_POST['attempts']) ? intval($_POST['attempts']) : 0;
    $blockDuration = isset($_POST['duration']) ? intval($_POST['duration']) : 0;
    
    $success = logSecurityEvent($action, $level, $attempts, $blockDuration);
    
    // Responder con JSON
    header('Content-Type: application/json');
    echo json_encode(['success' => $success]);
    exit;
}
?>
