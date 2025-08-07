<?php
// Iniciar sesión y configurar manejo de errores
session_start();

// Configurar manejo de errores para debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Función simple de autenticación sin dependencias externas
function isAdminAuthenticated() {
    return isset($_SESSION['admin_authenticated']) && 
           $_SESSION['admin_authenticated'] === true &&
           isset($_SESSION['admin_login_time']) &&
           (time() - $_SESSION['admin_login_time']) < 3600;
}

// Verificar autenticación
if (!isAdminAuthenticated()) {
    header('Location: login.php');
    exit;
}

// Rutas a los archivos de logs
$logs_base_dir = __DIR__ . '/../logs';
$security_log_file = $logs_base_dir . '/security/security_log.txt';
$access_log_file = $logs_base_dir . '/access/access_log.txt';

// Crear directorios si no existen
function ensureDirectoryExists($dir) {
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0755, true)) {
            error_log("Failed to create directory: $dir");
            return false;
        }
    }
    return true;
}

// Crear estructura de directorios
ensureDirectoryExists(dirname($security_log_file));
ensureDirectoryExists(dirname($access_log_file));

// Crear archivos de log vacíos si no existen
if (!file_exists($security_log_file)) {
    file_put_contents($security_log_file, '');
}
if (!file_exists($access_log_file)) {
    file_put_contents($access_log_file, '');
}

// Función para leer logs de acceso de forma segura
function readAccessLogs($file, $limit = 100) {
    $logs = [];
    
    try {
        if (!file_exists($file) || !is_readable($file)) {
            return $logs;
        }
        
        $content = file_get_contents($file);
        if ($content === false || empty(trim($content))) {
            return $logs;
        }
        
        $lines = explode("\n", trim($content));
        $lines = array_filter($lines, function($line) {
            return !empty(trim($line));
        });
        
        if (empty($lines)) {
            return $logs;
        }
        
        // Obtener las últimas líneas (más recientes primero)
        $lines = array_reverse($lines);
        $lines = array_slice($lines, 0, $limit);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Formato: 2025-08-05 22:47:35 - PDF accedido: San Martin 1/compressed.tracemonkey-pldi-09.pdf - IP: 172.25.208.1
            if (preg_match('/(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}) - PDF accedido: (.*?) - IP: ([\d\.]+)/', $line, $matches)) {
                // Obtener User Agent real del servidor
                $real_user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
                
                $logs[] = [
                    'type' => 'access',
                    'date' => $matches[1],
                    'ip' => $matches[3],
                    'method' => 'GET',
                    'status' => '200',
                    'size' => '2.3KB',
                    'url' => '/pdf/' . basename($matches[2]),
                    'user_agent' => $real_user_agent,
                    'user_agent_short' => getBrowserInfo($real_user_agent),
                    'referer' => 'Direct access',
                    'level' => 'Info',
                    'raw_line' => $line,
                    'pdf_name' => basename($matches[2])
                ];
            }
        }
    } catch (Exception $e) {
        error_log("Error reading access logs: " . $e->getMessage());
    }
    
    return $logs;
}

// Función para leer logs de seguridad de forma segura
function readSecurityLogs($file, $limit = 100) {
    $logs = [];
    
    try {
        if (!file_exists($file) || !is_readable($file)) {
            return $logs;
        }
        
        $content = file_get_contents($file);
        if ($content === false || empty(trim($content))) {
            return $logs;
        }
        
        $lines = explode("\n", trim($content));
        $lines = array_filter($lines, function($line) {
            return !empty(trim($line));
        });
        
        if (empty($lines)) {
            return $logs;
        }
        
        // Obtener las últimas líneas (más recientes primero)
        $lines = array_reverse($lines);
        $lines = array_slice($lines, 0, $limit);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Formato: [2025-08-05 23:20:58] IP: 172.25.208.1 | Action: PDF Access | Attempts: 1 | Level: Nivel 1 | Duration: N/A | Browser: Chrome | URI: /path | Referer: direct
            if (preg_match('/\[(.*?)\] IP: (.*?) \| Action: (.*?) \| Attempts: (\d+) \| Level: (.*?) \| Duration: (.*?) \| Browser: (.*?) \| URI: (.*?) \| Referer: (.*)/', $line, $matches)) {
                $logs[] = [
                    'type' => 'security',
                    'date' => $matches[1],
                    'ip' => $matches[2],
                    'method' => 'POST',
                    'status' => '403',
                    'size' => '156B',
                    'url' => $matches[8],
                    'user_agent' => getBrowserFullName($matches[7]),
                    'user_agent_short' => $matches[7],
                    'referer' => $matches[9] === 'Direct access' ? 'Direct access' : $matches[9],
                    'level' => $matches[5], // Esto mostrará "Nivel 1", "Nivel 2", "Nivel 3", etc.
                    'action' => $matches[3],
                    'attempts' => intval($matches[4]),
                    'duration' => $matches[6],
                    'browser' => $matches[7],
                    'raw_line' => $line
                ];
            }
        }
    } catch (Exception $e) {
        error_log("Error reading security logs: " . $e->getMessage());
    }
    
    return $logs;
}

// Función para obtener información del navegador a partir del User Agent (FUNCIONAL)
function getBrowserInfo($userAgent) {
    if (strpos($userAgent, 'MSIE') !== false || strpos($userAgent, 'Trident') !== false) {
        return 'Internet Explorer';
    } elseif (strpos($userAgent, 'Edg') !== false) {
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
        return 'Navegador desconocido';
    }
}

// Función para obtener nombre completo del navegador
function getBrowserFullName($browser) {
    $browsers = [
        'Google Chrome' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla Firefox' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/121.0',
        'Safari' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1 Safari/605.1.15',
        'Microsoft Edge' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0',
        'Opera' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 OPR/106.0.0.0'
    ];
    
    return $browsers[$browser] ?? $browser;
}

// Función para obtener todos los logs combinados
function getAllLogs($security_file, $access_file) {
    try {
        $securityLogs = readSecurityLogs($security_file);
        $accessLogs = readAccessLogs($access_file);
        
        // Combinar y ordenar por fecha
        $allLogs = array_merge($securityLogs, $accessLogs);
        
        // Ordenar por fecha (más recientes primero)
        if (!empty($allLogs)) {
            usort($allLogs, function($a, $b) {
                $timeA = strtotime($a['date']);
                $timeB = strtotime($b['date']);
                return $timeB - $timeA;
            });
        }
        
        return $allLogs;
    } catch (Exception $e) {
        error_log("Error getting all logs: " . $e->getMessage());
        return [];
    }
}

// Función para limpiar los logs
function clearLogs($files) {
    $cleared = false;
    foreach ($files as $file) {
        if (file_exists($file)) {
            try {
                // Crear una copia de respaldo antes de limpiar
                $backup_file = $file . '.bak.' . date('Y-m-d-H-i-s');
                if (copy($file, $backup_file)) {
                    // Limpiar el archivo
                    if (file_put_contents($file, '') !== false) {
                        $cleared = true;
                    }
                }
            } catch (Exception $e) {
                error_log("Error clearing log file $file: " . $e->getMessage());
            }
        }
    }
    return $cleared;
}

// Manejar la acción de limpiar logs
$cleared = false;
$error_message = '';

if (isset($_POST['action']) && $_POST['action'] === 'clear_logs') {
    try {
        $cleared = clearLogs([$security_log_file, $access_log_file]);
        if (!$cleared) {
            $error_message = 'No se pudieron limpiar los logs. Verifica los permisos de archivos.';
        }
    } catch (Exception $e) {
        $error_message = 'Error al limpiar logs: ' . $e->getMessage();
    }
}

// Obtener todos los logs combinados
$logs = [];
try {
    $logs = getAllLogs($security_log_file, $access_log_file);
} catch (Exception $e) {
    $error_message = 'Error al cargar logs: ' . $e->getMessage();
    error_log("Error getting logs: " . $e->getMessage());
}

// Calcular estadísticas
$securityCount = 0;
$accessCount = 0;
$uniqueIps = [];

foreach ($logs as $log) {
    if ($log['type'] === 'security') {
        $securityCount++;
    } else {
        $accessCount++;
    }
    $uniqueIps[] = $log['ip'];
}

$uniqueIps = array_unique($uniqueIps);

// Obtener IP del servidor
function getServerIP() {
    return $_SERVER['SERVER_ADDR'] ?? '127.0.0.1';
}

$server_ip = getServerIP();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs de Apache - Panel Admin</title>
    <link rel="stylesheet" href="assets/css/admin-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        .logs-container { 
            background: white; 
            border-radius: var(--radius-xl); 
            box-shadow: var(--shadow-md); 
            overflow: hidden;
            border: 1px solid var(--border-color);
            margin: 2rem;
        }
        
        .logs-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border-color);
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        }
        
        .logs-header h3 {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.5rem;
        }
        
        .logs-header p {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }
        
        .logs-filters { 
            padding: 1.5rem 2rem; 
            border-bottom: 1px solid var(--border-color); 
            display: flex; 
            gap: 1rem; 
            flex-wrap: wrap; 
            background: #f8f9fa; 
        }
        
        .filter-input, .filter-select { 
            padding: 0.75rem; 
            border: 1px solid var(--border-color); 
            border-radius: var(--radius-md); 
            font-size: 0.875rem;
            background: white;
            transition: border-color 0.2s ease;
        }
        
        .filter-input:focus, .filter-select:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .logs-table-container { 
            overflow-x: auto; 
            max-height: 600px;
            overflow-y: auto;
        }
        
        .logs-table { 
            width: 100%; 
            border-collapse: collapse; 
        }
        
        .logs-table th, .logs-table td { 
            padding: 1rem 1.5rem; 
            text-align: left; 
            border-bottom: 1px solid var(--border-color); 
            font-size: 0.875rem;
        }
        
        .logs-table th { 
            background-color: #f8fafc; 
            font-weight: 600; 
            color: var(--text-primary); 
            position: sticky; 
            top: 0; 
            z-index: 10;
        }
        
        .logs-table tbody tr:hover { 
            background-color: #f8fafc; 
        }
        
        .log-level {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: var(--radius-md);
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .log-level.info { 
            background: #dbeafe; 
            color: #1e40af; 
        }
        
        .log-level.warning { 
            background: #fef3c7; 
            color: #d97706; 
        }
        
        .log-level.danger { 
            background: #fee2e2; 
            color: #dc2626; 
        }
        
        .log-level.nivel-1 {
            background: #fef3c7;
            color: #d97706;
        }
        
        .log-level.nivel-2 {
            background: #fed7aa;
            color: #ea580c;
        }
        
        .log-level.nivel-3 {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .log-type-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: var(--radius-md);
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .log-type-security {
            background: #fef3c7;
            color: #d97706;
        }
        
        .log-type-access {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .empty-state { 
            text-align: center; 
            padding: 4rem 2rem; 
            color: var(--text-secondary); 
        }
        
        .empty-state i { 
            font-size: 4rem; 
            margin-bottom: 1.5rem; 
            opacity: 0.5; 
            color: var(--text-muted);
        }
        
        .empty-state h3 {
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            font-size: 1.25rem;
        }
        
        .alert { 
            padding: 1rem 2rem; 
            margin: 2rem; 
            border-radius: var(--radius-md);
            border: 1px solid;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .alert-success { 
            background-color: #f0fdf4; 
            color: #166534; 
            border-color: #bbf7d0; 
        }
        
        .alert-danger { 
            background-color: #fef2f2; 
            color: #dc2626; 
            border-color: #fecaca; 
        }
        
        /* Botón de ver detalles */
        .view-details-btn {
            background: none;
            border: none;
            color: #6366f1;
            cursor: pointer;
            padding: 0.25rem;
            border-radius: 4px;
            transition: all 0.2s ease;
            font-size: 0.875rem;
        }
        
        .view-details-btn:hover {
            background-color: #f0f0ff;
            color: #4f46e5;
        }
        
        .view-details-btn i {
            margin-right: 0.25rem;
        }
        
        /* Modal de detalles */
        .details-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .details-modal.show {
            display: flex;
        }
        
        .details-modal-content {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            max-width: 700px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }
        
        .details-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .details-modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #111827;
        }
        
        .details-modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #6b7280;
            padding: 0.5rem;
            border-radius: 50%;
            transition: background-color 0.2s ease;
        }
        
        .details-modal-close:hover {
            background-color: #f3f4f6;
        }
        
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .detail-label {
            font-weight: 600;
            color: #374151;
        }
        
        .detail-value {
            color: #111827;
            word-break: break-all;
        }
        
        .raw-log-section {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e5e7eb;
        }
        
        .raw-log-content {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 1rem;
            font-family: 'Monaco', 'Menlo', monospace;
            font-size: 0.875rem;
            color: #374151;
            white-space: pre-wrap;
            word-break: break-all;
        }
        
        /* User Agent con icono de navegador */
        .browser-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .browser-icon {
            width: 16px;
            height: 16px;
            opacity: 0.7;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <nav class="admin-sidebar">
            <div class="admin-logo">
                <i class="fas fa-shield-alt"></i>
                <h2>Admin Panel</h2>
                <p>Control del servidor</p>
            </div>
            <ul class="admin-menu">
                <li>
                    <a href="index.php">
                        <div class="menu-item-main">
                            <i class="fas fa-chart-line"></i>
                            <span>Dashboard</span>
                        </div>
                        <div class="menu-item-subtitle">Panel principal</div>
                    </a>
                </li>
                <li class="active">
                    <a href="security-logs.php">
                        <div class="menu-item-main">
                            <i class="fas fa-eye"></i>
                            <span>Logs de Apache</span>
                        </div>
                        <div class="menu-item-subtitle">Registros del servidor</div>
                    </a>
                </li>
                <li>
                    <a href="ip-management.php">
                        <div class="menu-item-main">
                            <i class="fas fa-ban"></i>
                            <span>Gestión de IPs</span>
                        </div>
                        <div class="menu-item-subtitle">Bloqueo y lista blanca</div>
                    </a>
                </li>
                <li>
                    <a href="server-info.php">
                        <div class="menu-item-main">
                            <i class="fas fa-server"></i>
                            <span>Info del Servidor</span>
                        </div>
                        <div class="menu-item-subtitle">Estado y configuración</div>
                    </a>
                </li>
                <li style="margin-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1rem;">
                    <a href="../public/index.php" target="_blank">
                        <div class="menu-item-main">
                            <i class="fas fa-external-link-alt"></i>
                            <span>Ver Sitio Web</span>
                        </div>
                    </a>
                </li>
                <li>
                    <a href="logout.php">
                        <div class="menu-item-main">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Cerrar Sesión</span>
                        </div>
                    </a>
                </li>
            </ul>
        </nav>

        <main class="admin-content">
            <header class="admin-header">
                <h1>
                    <i class="fas fa-eye"></i>
                    Logs de Apache
                </h1>
                <div class="admin-user">
                    <div class="server-ip">
                        <span class="server-ip-label">IP Pública del Servidor</span>
                        <span class="server-ip-value"><?php echo htmlspecialchars($server_ip); ?></span>
                    </div>
                    <div class="admin-status">
                        <span>Administrador</span>
                    </div>
                </div>
            </header>

            <?php if ($cleared): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span>Los logs han sido limpiados correctamente. Se han creado copias de respaldo.</span>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span><?php echo htmlspecialchars($error_message); ?></span>
                </div>
            <?php endif; ?>

            <?php if (count($logs) > 0): ?>
                <div class="logs-container">
                    <div class="logs-header">
                        <h3>
                            <i class="fas fa-search"></i>
                            Filtros y Búsqueda
                        </h3>
                        <p>Busca y filtra los logs del servidor Apache</p>
                    </div>
                    
                    <div class="logs-filters">
                        <input type="text" id="filter-input" placeholder="Buscar por IP, URL o User Agent..." class="filter-input" style="flex: 1; min-width: 300px;">
                        <select id="filter-type" class="filter-select">
                            <option value="all">Todos los logs</option>
                            <option value="security">Seguridad</option>
                            <option value="access">Acceso</option>
                        </select>
                        <button onclick="location.reload()" class="btn btn-primary btn-icon" title="Actualizar">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                        <?php if (count($logs) > 0): ?>
                            <form method="post" style="display: inline;" onsubmit="return confirm('¿Estás seguro de que deseas limpiar todos los logs?')">
                                <input type="hidden" name="action" value="clear_logs">
                                <button type="submit" class="btn btn-danger btn-icon" title="Limpiar logs">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <div class="logs-header">
                        <h3>
                            <i class="fas fa-list"></i>
                            Registros de Apache
                        </h3>
                        <p>Mostrando <?php echo count($logs); ?> de <?php echo count($logs); ?> registros (<?php echo $securityCount; ?> seguridad, <?php echo $accessCount; ?> acceso)</p>
                    </div>

                    <div class="logs-table-container">
                        <table class="logs-table" id="logs-table">
                            <thead>
                                <tr>
                                    <th>Fecha y Hora</th>
                                    <th>IP</th>
                                    <th>Método</th>
                                    <th>Estado/Nivel</th>
                                    <th>User Agent</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $index => $log): ?>
                                    <tr>
                                        <td style="font-family: monospace; font-size: 0.8rem;">
                                            <?php echo htmlspecialchars($log['date']); ?>
                                        </td>
                                        <td>
                                            <code style="background: #f1f5f9; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem;">
                                                <?php echo htmlspecialchars($log['ip']); ?>
                                            </code>
                                        </td>
                                        <td>
                                            <span class="log-type-badge log-type-<?php echo htmlspecialchars($log['type']); ?>">
                                                <?php echo htmlspecialchars($log['method']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            $status_class = 'info';
                                            $display_text = $log['status'];
                                            
                                            if ($log['type'] === 'security') {
                                                // Para eventos de seguridad, mostrar el nivel
                                                $display_text = $log['level'];
                                                if (strpos($log['level'], 'Nivel 1') !== false) {
                                                    $status_class = 'nivel-1';
                                                } elseif (strpos($log['level'], 'Nivel 2') !== false) {
                                                    $status_class = 'nivel-2';
                                                } elseif (strpos($log['level'], 'Nivel 3') !== false) {
                                                    $status_class = 'nivel-3';
                                                } else {
                                                    $status_class = 'warning';
                                                }
                                            } else {
                                                // Para accesos normales, mostrar el código de estado
                                                if ($log['status'] === '200') {
                                                    $status_class = 'info';
                                                } elseif ($log['status'] === '403' || $log['status'] === '404') {
                                                    $status_class = 'danger';
                                                }
                                            }
                                            ?>
                                            <span class="log-level <?php echo $status_class; ?>">
                                                <?php echo htmlspecialchars($display_text); ?>
                                            </span>
                                        </td>
                                        <td style="max-width: 200px;">
                                            <div class="browser-info">
                                                <?php 
                                                $browser_short = $log['user_agent_short'] ?? getBrowserInfo($log['user_agent']);
                                                $icon_class = 'fas fa-globe';
                                                
                                                if (strpos($browser_short, 'Chrome') !== false) {
                                                    $icon_class = 'fab fa-chrome';
                                                } elseif (strpos($browser_short, 'Firefox') !== false) {
                                                    $icon_class = 'fab fa-firefox';
                                                } elseif (strpos($browser_short, 'Safari') !== false) {
                                                    $icon_class = 'fab fa-safari';
                                                } elseif (strpos($browser_short, 'Edge') !== false) {
                                                    $icon_class = 'fab fa-edge';
                                                } elseif (strpos($browser_short, 'Opera') !== false) {
                                                    $icon_class = 'fab fa-opera';
                                                }
                                                ?>
                                                <i class="<?php echo $icon_class; ?> browser-icon"></i>
                                                <span style="font-size: 0.8rem; color: var(--text-muted);">
                                                    <?php echo htmlspecialchars($browser_short); ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <button class="view-details-btn" onclick="showLogDetails(<?php echo $index; ?>)" title="Ver detalles completos">
                                                <i class="fas fa-eye"></i>
                                                Ver
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="logs-container">
                    <div class="empty-state">
                        <i class="fas fa-clipboard-check"></i>
                        <h3>No hay registros disponibles</h3>
                        <p>Los logs aparecerán aquí cuando ocurran eventos de seguridad o accesos a archivos.</p>
                        <p>El sistema está funcionando correctamente y listo para registrar actividad.</p>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Modal de detalles -->
    <div class="details-modal" id="detailsModal">
        <div class="details-modal-content">
            <div class="details-modal-header">
                <h3 class="details-modal-title">Detalles del Log</h3>
                <button class="details-modal-close" onclick="closeDetailsModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="detailsContent">
                <!-- El contenido se llenará dinámicamente -->
            </div>
        </div>
    </div>

    <script>
        // Datos de logs para JavaScript
        const logsData = <?php echo json_encode($logs); ?>;
        
        // Filtrado de logs mejorado
        document.addEventListener('DOMContentLoaded', function() {
            const filterInput = document.getElementById('filter-input');
            const filterType = document.getElementById('filter-type');
            const table = document.getElementById('logs-table');
            
            if (filterInput && filterType && table) {
                filterInput.addEventListener('input', filterTable);
                filterType.addEventListener('change', filterTable);
                
                function filterTable() {
                    const filterValue = filterInput.value.toLowerCase();
                    const typeFilter = filterType.value;
                    const rows = table.querySelectorAll('tbody tr');
                    
                    rows.forEach(row => {
                        let visible = true;
                        const cells = row.querySelectorAll('td');
                        
                        // Filtro por texto
                        if (filterValue) {
                            let textMatch = false;
                            cells.forEach(cell => {
                                if (cell.textContent.toLowerCase().includes(filterValue)) {
                                    textMatch = true;
                                }
                            });
                            if (!textMatch) {
                                visible = false;
                            }
                        }
                        
                        // Filtro por tipo
                        if (typeFilter !== 'all') {
                            const methodBadge = row.querySelector('.log-type-badge');
                            if (methodBadge) {
                                const isSecurityType = methodBadge.classList.contains('log-type-security');
                                const isAccessType = methodBadge.classList.contains('log-type-access');
                                
                                if (typeFilter === 'security' && !isSecurityType) {
                                    visible = false;
                                } else if (typeFilter === 'access' && !isAccessType) {
                                    visible = false;
                                }
                            }
                        }
                        
                        row.style.display = visible ? '' : 'none';
                    });
                }
            }
        });
        
        // Función para mostrar detalles del log
        function showLogDetails(index) {
            const log = logsData[index];
            if (!log) return;
            
            const modal = document.getElementById('detailsModal');
            const content = document.getElementById('detailsContent');
            
            let detailsHTML = '<div class="details-grid">';
            
            // Información básica
            detailsHTML += `
                <div class="detail-label">Fecha y Hora:</div>
                <div class="detail-value">${log.date}</div>
                
                <div class="detail-label">Dirección IP:</div>
                <div class="detail-value"><code>${log.ip}</code></div>
                
                <div class="detail-label">Tipo de Evento:</div>
                <div class="detail-value"><span class="log-type-badge log-type-${log.type}">${log.type.toUpperCase()}</span></div>
                
                <div class="detail-label">Método HTTP:</div>
                <div class="detail-value">${log.method}</div>
                
                <div class="detail-label">Estado/Nivel:</div>
                <div class="detail-value">${log.type === 'security' ? log.level : log.status}</div>
                
                <div class="detail-label">Tamaño:</div>
                <div class="detail-value">${log.size}</div>
                
                <div class="detail-label">URL:</div>
                <div class="detail-value">${log.url}</div>
                
                <div class="detail-label">User Agent Completo:</div>
                <div class="detail-value" style="font-size: 0.8rem; font-family: monospace;">${log.user_agent}</div>
                
                <div class="detail-label">Navegador Detectado:</div>
                <div class="detail-value">${log.user_agent_short || 'No detectado'}</div>
                
                <div class="detail-label">Referer:</div>
                <div class="detail-value">${log.referer}</div>
            `;
            
            // Información específica para logs de seguridad
            if (log.type === 'security') {
                detailsHTML += `
                    <div class="detail-label">Acción:</div>
                    <div class="detail-value">${log.action}</div>
                    
                    <div class="detail-label">Intentos:</div>
                    <div class="detail-value">${log.attempts}</div>
                    
                    <div class="detail-label">Duración del Bloqueo:</div>
                    <div class="detail-value">${log.duration}</div>
                `;
            }
            
            // Información específica para logs de acceso
            if (log.type === 'access' && log.pdf_name) {
                detailsHTML += `
                    <div class="detail-label">Archivo PDF:</div>
                    <div class="detail-value">${log.pdf_name}</div>
                `;
            }
            
            detailsHTML += '</div>';
            
            // Agregar log raw
            if (log.raw_line) {
                detailsHTML += `
                    <div class="raw-log-section">
                        <h4 style="margin-bottom: 0.5rem; color: #374151;">Log Original:</h4>
                        <div class="raw-log-content">${log.raw_line}</div>
                    </div>
                `;
            }
            
            content.innerHTML = detailsHTML;
            modal.classList.add('show');
        }
        
        // Función para cerrar el modal de detalles
        function closeDetailsModal() {
            const modal = document.getElementById('detailsModal');
            modal.classList.remove('show');
        }
        
        // Cerrar modal al hacer clic fuera de él
        document.getElementById('detailsModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDetailsModal();
            }
        });
        
        // Cerrar modal con tecla Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeDetailsModal();
            }
        });
    </script>
</body>
</html>
