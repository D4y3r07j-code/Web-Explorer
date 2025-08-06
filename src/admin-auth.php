<?php
/**
 * Funciones de autenticación para el panel de administración
 */

require_once __DIR__ . '/ip-manager.php';

function isAdminAuthenticated() {
    return isset($_SESSION['admin_authenticated']) && 
           $_SESSION['admin_authenticated'] === true &&
           isset($_SESSION['admin_login_time']) &&
           (time() - $_SESSION['admin_login_time']) < 3600; // 1 hora de sesión
}

function getSecurityStats() {
    // Inicializar IPManager para obtener datos reales
    $ipManager = new IPManager();
    $ipStats = $ipManager->getStats();
    
    // Obtener estadísticas reales de los archivos de log
    $stats = [
        'security_events' => 0,
        'blocked_ips' => $ipStats['total_blocked'], // Usar datos reales del IPManager
        'pdf_views' => 0,
        'security_trend' => 0,
        'blocked_trend' => 0,
        'pdf_trend' => 0
    ];
    
    // Contar eventos de seguridad reales
    $security_log_file = __DIR__ . '/../logs/security/security_log.txt';
    if (file_exists($security_log_file)) {
        $lines = file($security_log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $stats['security_events'] = count($lines);
        
        // Calcular tendencia (eventos de hoy vs ayer)
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        $today_events = 0;
        $yesterday_events = 0;
        
        foreach ($lines as $line) {
            if (strpos($line, $today) !== false) {
                $today_events++;
            } elseif (strpos($line, $yesterday) !== false) {
                $yesterday_events++;
            }
        }
        
        $stats['security_trend'] = $today_events - $yesterday_events;
    }
    
    // Calcular tendencia de IPs bloqueadas usando IPManager
    $blocked_ips = $ipManager->getBlockedIPs();
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    
    $today_blocks = 0;
    $yesterday_blocks = 0;
    
    foreach ($blocked_ips as $ip => $ip_data) {
        if (isset($ip_data['blocked_at'])) {
            $blocked_date = date('Y-m-d', strtotime($ip_data['blocked_at']));
            if ($blocked_date === $today) {
                $today_blocks++;
            } elseif ($blocked_date === $yesterday) {
                $yesterday_blocks++;
            }
        }
    }
    
    $stats['blocked_trend'] = $today_blocks - $yesterday_blocks;
    
    // Contar accesos a PDFs reales
    $access_log_file = __DIR__ . '/../logs/access/access_log.txt';
    if (file_exists($access_log_file)) {
        $lines = file($access_log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $stats['pdf_views'] = count($lines);
        
        // Calcular tendencia de PDFs
        $today_views = 0;
        $yesterday_views = 0;
        
        foreach ($lines as $line) {
            if (strpos($line, $today) !== false) {
                $today_views++;
            } elseif (strpos($line, $yesterday) !== false) {
                $yesterday_views++;
            }
        }
        
        if ($yesterday_views > 0) {
            $stats['pdf_trend'] = round((($today_views - $yesterday_views) / $yesterday_views) * 100, 1);
        } else {
            $stats['pdf_trend'] = $today_views > 0 ? 100 : 0;
        }
    }
    
    return $stats;
}

function getRecentActivity() {
    $activities = [];
    
    // Leer actividad de logs de seguridad
    $security_log_file = __DIR__ . '/../logs/security/security_log.txt';
    if (file_exists($security_log_file)) {
        $lines = file($security_log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $recent_lines = array_slice(array_reverse($lines), 0, 3); // Últimas 3 líneas
        
        foreach ($recent_lines as $line) {
            if (preg_match('/\[(.*?)\] IP: (.*?) \| Action: (.*?) \|/', $line, $matches)) {
                $timestamp = $matches[1];
                $ip = $matches[2];
                $action = $matches[3];
                
                $activities[] = [
                    'icon' => getActionIcon($action),
                    'description' => "Evento de seguridad: {$action} desde IP {$ip}",
                    'time' => getTimeAgo($timestamp),
                    'type' => getActionType($action)
                ];
            }
        }
    }
    
    // Leer actividad de bloqueos permanentes
    $permanent_blocks_file = __DIR__ . '/../logs/security/permanent_blocks.txt';
    if (file_exists($permanent_blocks_file)) {
        $lines = file($permanent_blocks_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $recent_lines = array_slice(array_reverse($lines), 0, 2); // Últimas 2 líneas
        
        foreach ($recent_lines as $line) {
            if (preg_match('/\[(.*?)\] IP (.*?) bloqueada permanentemente/', $line, $matches)) {
                $timestamp = $matches[1];
                $ip = $matches[2];
                
                $activities[] = [
                    'icon' => 'ban',
                    'description' => "IP {$ip} bloqueada permanentemente por múltiples violaciones",
                    'time' => getTimeAgo($timestamp),
                    'type' => 'danger'
                ];
            }
        }
    }
    
    // Leer actividad de accesos a PDFs
    $access_log_file = __DIR__ . '/../logs/access/access_log.txt';
    if (file_exists($access_log_file)) {
        $lines = file($access_log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $recent_lines = array_slice(array_reverse($lines), 0, 2); // Últimas 2 líneas
        
        foreach ($recent_lines as $line) {
            if (preg_match('/(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}) - PDF accedido: (.*?) - IP: (.*?)$/', $line, $matches)) {
                $timestamp = $matches[1];
                $pdf_path = $matches[2];
                $ip = $matches[3];
                
                $pdf_name = basename($pdf_path);
                
                $activities[] = [
                    'icon' => 'file-pdf',
                    'description' => "PDF accedido: {$pdf_name} desde IP {$ip}",
                    'time' => getTimeAgo($timestamp),
                    'type' => 'success'
                ];
            }
        }
    }
    
    // Ordenar por tiempo (más reciente primero)
    usort($activities, function($a, $b) {
        return strcmp($b['time'], $a['time']);
    });
    
    // Limitar a 5 actividades
    return array_slice($activities, 0, 5);
}

function getActionIcon($action) {
    $icons = [
        'menú contextual' => 'mouse-pointer',
        'tecla F12' => 'keyboard',
        'herramientas de desarrollador' => 'code',
        'consola de desarrollador' => 'terminal',
        'ver código fuente' => 'eye',
        'impresión' => 'print',
        'arrastrar elementos' => 'arrows-alt',
        'copiar contenido' => 'copy',
        'guardar página' => 'save',
        'manipulación del bloqueo' => 'shield-alt',
        'bloqueo aplicado' => 'ban',
        'bloqueo finalizado' => 'unlock'
    ];
    
    return $icons[$action] ?? 'exclamation-triangle';
}

function getActionType($action) {
    $dangerous_actions = [
        'herramientas de desarrollador',
        'manipulación del bloqueo',
        'bloqueo aplicado'
    ];
    
    $warning_actions = [
        'tecla F12',
        'consola de desarrollador',
        'ver código fuente'
    ];
    
    if (in_array($action, $dangerous_actions)) {
        return 'danger';
    } elseif (in_array($action, $warning_actions)) {
        return 'warning';
    } else {
        return 'success';
    }
}

function getTimeAgo($timestamp) {
    $time = strtotime($timestamp);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'Hace ' . $diff . ' segundo' . ($diff != 1 ? 's' : '');
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return 'Hace ' . $minutes . ' minuto' . ($minutes != 1 ? 's' : '');
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return 'Hace ' . $hours . ' hora' . ($hours != 1 ? 's' : '');
    } else {
        $days = floor($diff / 86400);
        return 'Hace ' . $days . ' día' . ($days != 1 ? 's' : '');
    }
}
?>
