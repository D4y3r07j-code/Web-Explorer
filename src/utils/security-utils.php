<?php
/**
 * Utilidades de seguridad
 * Proyecto Web Explorer
 */

/**
 * Verificar si una IP está en la lista de IPs permitidas
 */
function isIPAllowed($ip, $allowedIPs = []) {
    if (empty($allowedIPs)) {
        return true; // Si no hay restricciones, permitir todas
    }
    
    return in_array($ip, $allowedIPs);
}

/**
 * Obtener la IP real del cliente
 */
function getRealClientIP() {
    $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    
    foreach ($ipKeys as $key) {
        if (!empty($_SERVER[$key])) {
            $ips = explode(',', $_SERVER[$key]);
            $ip = trim($ips[0]);
            
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/**
 * Generar hash seguro para contraseñas
 */
function generateSecureHash($password) {
    return password_hash($password, PASSWORD_ARGON2ID, [
        'memory_cost' => 65536, // 64 MB
        'time_cost' => 4,       // 4 iteraciones
        'threads' => 3,         // 3 hilos
    ]);
}

/**
 * Verificar hash de contraseña
 */
function verifySecureHash($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generar token CSRF
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verificar token CSRF
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Limpiar entrada de usuario
 */
function sanitizeInput($input, $type = 'string') {
    switch ($type) {
        case 'string':
            return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
        case 'email':
            return filter_var(trim($input), FILTER_SANITIZE_EMAIL);
        case 'url':
            return filter_var(trim($input), FILTER_SANITIZE_URL);
        case 'int':
            return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
        case 'float':
            return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        default:
            return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Validar entrada de usuario
 */
function validateInput($input, $type = 'string', $options = []) {
    switch ($type) {
        case 'email':
            return filter_var($input, FILTER_VALIDATE_EMAIL) !== false;
        case 'url':
            return filter_var($input, FILTER_VALIDATE_URL) !== false;
        case 'ip':
            return filter_var($input, FILTER_VALIDATE_IP) !== false;
        case 'int':
            $min = $options['min'] ?? null;
            $max = $options['max'] ?? null;
            $flags = 0;
            $filterOptions = [];
            
            if ($min !== null) $filterOptions['min_range'] = $min;
            if ($max !== null) $filterOptions['max_range'] = $max;
            
            return filter_var($input, FILTER_VALIDATE_INT, [
                'options' => $filterOptions
            ]) !== false;
        case 'string':
            $minLength = $options['min_length'] ?? 0;
            $maxLength = $options['max_length'] ?? PHP_INT_MAX;
            $length = strlen($input);
            return $length >= $minLength && $length <= $maxLength;
        default:
            return true;
    }
}

/**
 * Registrar intento de login fallido
 */
function recordFailedLogin($ip, $username = '') {
    $logFile = '../logs/security/failed_logins.txt';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] Failed login attempt - IP: $ip, Username: $username\n";
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

/**
 * Verificar si una IP está bloqueada por intentos fallidos
 */
function isIPBlocked($ip) {
    $logFile = '../logs/security/failed_logins.txt';
    
    if (!file_exists($logFile)) {
        return false;
    }
    
    $config = include '../config/security-config.php';
    $maxAttempts = $config['max_login_attempts'];
    $lockoutDuration = $config['lockout_duration'];
    $attemptWindow = $config['attempt_window'];
    
    $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $recentAttempts = 0;
    $cutoffTime = time() - $attemptWindow;
    
    foreach (array_reverse($lines) as $line) {
        if (strpos($line, "IP: $ip") !== false) {
            preg_match('/\[(.*?)\]/', $line, $matches);
            if (isset($matches[1])) {
                $attemptTime = strtotime($matches[1]);
                if ($attemptTime > $cutoffTime) {
                    $recentAttempts++;
                } else {
                    break; // Los intentos más antiguos no nos interesan
                }
            }
        }
    }
    
    return $recentAttempts >= $maxAttempts;
}

/**
 * Generar nonce para CSP
 */
function generateNonce() {
    return base64_encode(random_bytes(16));
}

/**
 * Aplicar headers de seguridad
 */
function applySecurityHeaders() {
    $config = include '../config/security-config.php';
    $headers = $config['security_headers'];
    
    foreach ($headers as $header => $value) {
        header("$header: $value");
    }
}

/**
 * Verificar fuerza de contraseña
 */
function checkPasswordStrength($password) {
    $config = include '../config/security-config.php';
    $authConfig = $config['authentication'];
    
    $score = 0;
    $feedback = [];
    
    // Longitud mínima
    if (strlen($password) >= $authConfig['password_min_length']) {
        $score += 25;
    } else {
        $feedback[] = "La contraseña debe tener al menos {$authConfig['password_min_length']} caracteres";
    }
    
    // Mayúsculas
    if ($authConfig['require_uppercase'] && preg_match('/[A-Z]/', $password)) {
        $score += 25;
    } elseif ($authConfig['require_uppercase']) {
        $feedback[] = "La contraseña debe contener al menos una letra mayúscula";
    }
    
    // Números
    if ($authConfig['require_numbers'] && preg_match('/[0-9]/', $password)) {
        $score += 25;
    } elseif ($authConfig['require_numbers']) {
        $feedback[] = "La contraseña debe contener al menos un número";
    }
    
    // Caracteres especiales
    if ($authConfig['require_special_chars'] && preg_match('/[^a-zA-Z0-9]/', $password)) {
        $score += 25;
    } elseif ($authConfig['require_special_chars']) {
        $feedback[] = "La contraseña debe contener al menos un carácter especial";
    }
    
    return [
        'score' => $score,
        'strength' => $score >= 100 ? 'strong' : ($score >= 75 ? 'medium' : 'weak'),
        'feedback' => $feedback
    ];
}

/**
 * Rate limiting simple
 */
function checkRateLimit($identifier, $maxRequests = 60, $timeWindow = 60) {
    $cacheFile = "../cache/rate_limit_$identifier.txt";
    $now = time();
    
    if (file_exists($cacheFile)) {
        $data = json_decode(file_get_contents($cacheFile), true);
        
        // Limpiar requests antiguos
        $data['requests'] = array_filter($data['requests'], function($timestamp) use ($now, $timeWindow) {
            return ($now - $timestamp) < $timeWindow;
        });
        
        if (count($data['requests']) >= $maxRequests) {
            return false; // Rate limit excedido
        }
        
        $data['requests'][] = $now;
    } else {
        $data = ['requests' => [$now]];
    }
    
    // Crear directorio cache si no existe
    $cacheDir = dirname($cacheFile);
    if (!file_exists($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    
    file_put_contents($cacheFile, json_encode($data));
    return true;
}

/**
 * Limpiar archivos de cache antiguos
 */
function cleanupRateLimitCache($maxAge = 3600) {
    $cacheDir = '../cache/';
    if (!is_dir($cacheDir)) return;
    
    $files = glob($cacheDir . 'rate_limit_*.txt');
    $now = time();
    
    foreach ($files as $file) {
        if (($now - filemtime($file)) > $maxAge) {
            unlink($file);
        }
    }
}
?>
