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
                $logs[] = [
                    'type' => 'access',
                    'date' => $matches[1],
                    'ip' => $matches[3],
                    'method' => 'GET',
                    'status' => '200',
                    'size' => '2.3KB',
                    'url' => '/pdf/' . basename($matches[2]),
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'referer' => 'Direct access',
                    'level' => 'Info'
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
                    'referer' => $matches[9] === 'Direct access' ? 'Direct access' : $matches[9],
                    'level' => $matches[5], // Esto mostrará "Nivel 1", "Nivel 2", "Nivel 3", etc.
                    'action' => $matches[3],
                    'attempts' => intval($matches[4])
                ];
            }
        }
    } catch (Exception $e) {
        error_log("Error reading security logs: " . $e->getMessage());
    }
    
    return $logs;
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
                                    <th>Tamaño</th>
                                    <th>URL</th>
                                    <th>User Agent</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
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
                                        <td style="font-family: monospace; font-size: 0.8rem;">
                                            <?php echo htmlspecialchars($log['size']); ?>
                                        </td>
                                        <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            <?php echo htmlspecialchars($log['url']); ?>
                                        </td>
                                        <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 0.8rem; color: var(--text-muted);">
                                            <?php echo htmlspecialchars($log['user_agent']); ?>
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

    <script>
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
    </script>
</body>
</html>
