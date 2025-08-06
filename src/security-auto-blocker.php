<?php
/**
 * Sistema automático de bloqueo de IPs por eventos de seguridad
 */

require_once __DIR__ . '/ip-manager.php';

class SecurityAutoBlocker {
    private $ipManager;
    private $security_events_file;
    
    public function __construct() {
        $this->ipManager = new IPManager();
        $this->security_events_file = __DIR__ . '/../logs/security/security_events.json';
        
        // Crear directorio si no existe
        $dir = dirname($this->security_events_file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        // Crear archivo si no existe
        if (!file_exists($this->security_events_file)) {
            file_put_contents($this->security_events_file, json_encode([]));
        }
    }
    
    /**
     * Procesar evento de seguridad y determinar si se debe bloquear la IP
     */
    public function processSecurityEvent($ip, $action, $level, $attempts) {
        // Registrar el evento
        $this->logSecurityEvent($ip, $action, $level, $attempts);
        
        // Si se alcanzaron 9 o más intentos, bloquear automáticamente la IP de forma PERMANENTE
        if ($attempts >= 9) {
            $result = $this->autoBlockIPPermanently($ip, $attempts);
            return [
                'success' => true,
                'blocked' => $result['success'],
                'message' => $result['message'],
                'permanent' => true
            ];
        }
        
        return [
            'success' => true,
            'blocked' => false,
            'message' => 'Evento registrado'
        ];
    }
    
    /**
     * Bloquear automáticamente una IP de forma PERMANENTE por eventos de seguridad
     */
    private function autoBlockIPPermanently($ip, $attempts) {
        $reason = "BLOQUEO AUTOMÁTICO PERMANENTE - {$attempts} violaciones de seguridad detectadas. Solo un administrador puede remover este bloqueo.";
        
        // Bloquear PERMANENTEMENTE (sin duración = permanente)
        $result = $this->ipManager->blockIP($ip, $reason, null, 'Sistema de Seguridad Automático');
        
        if ($result['success']) {
            // Log adicional para el bloqueo automático permanente
            error_log("PERMANENT AUTO-BLOCK: IP $ip blocked permanently after $attempts security violations");
            
            // Registrar en log especial de bloqueos permanentes
            $this->logPermanentBlock($ip, $attempts);
        }
        
        return $result;
    }
    
    /**
     * Registrar bloqueo permanente en log especial
     */
    private function logPermanentBlock($ip, $attempts) {
        $permanent_blocks_file = __DIR__ . '/../logs/security/permanent_blocks.txt';
        $timestamp = date('Y-m-d H:i:s');
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        $log_entry = sprintf(
            "[%s] PERMANENT BLOCK - IP: %s | Attempts: %d | User-Agent: %s | Auto-blocked by security system\n",
            $timestamp,
            $ip,
            $attempts,
            $user_agent
        );
        
        file_put_contents($permanent_blocks_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Registrar evento de seguridad en archivo JSON
     */
    private function logSecurityEvent($ip, $action, $level, $attempts) {
        $events = $this->getSecurityEvents();
        
        $event = [
            'ip' => $ip,
            'action' => $action,
            'level' => $level,
            'attempts' => $attempts,
            'timestamp' => date('Y-m-d H:i:s'),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'permanent_block_triggered' => $attempts >= 9
        ];
        
        $events[] = $event;
        
        // Mantener solo los últimos 1000 eventos
        if (count($events) > 1000) {
            $events = array_slice($events, -1000);
        }
        
        file_put_contents($this->security_events_file, json_encode($events, JSON_PRETTY_PRINT));
    }
    
    /**
     * Obtener eventos de seguridad
     */
    private function getSecurityEvents() {
        if (!file_exists($this->security_events_file)) {
            return [];
        }
        
        $content = file_get_contents($this->security_events_file);
        $events = json_decode($content, true);
        
        return is_array($events) ? $events : [];
    }
    
    /**
     * Verificar si una IP debe ser bloqueada
     */
    public function shouldBlockIP($ip) {
        return $this->ipManager->isBlocked($ip);
    }
    
    /**
     * Obtener estadísticas de eventos de seguridad
     */
    public function getSecurityStats() {
        $events = $this->getSecurityEvents();
        $today = date('Y-m-d');
        
        $stats = [
            'total_events' => count($events),
            'today_events' => 0,
            'level_1_events' => 0,
            'level_2_events' => 0,
            'level_3_events' => 0,
            'permanent_blocks_triggered' => 0,
            'auto_blocked_ips' => 0
        ];
        
        foreach ($events as $event) {
            if (strpos($event['timestamp'], $today) === 0) {
                $stats['today_events']++;
            }
            
            switch ($event['level']) {
                case 0:
                    $stats['level_1_events']++;
                    break;
                case 1:
                    $stats['level_2_events']++;
                    break;
                case 2:
                    $stats['level_3_events']++;
                    break;
            }
            
            if (isset($event['permanent_block_triggered']) && $event['permanent_block_triggered']) {
                $stats['permanent_blocks_triggered']++;
                $stats['auto_blocked_ips']++;
            }
        }
        
        return $stats;
    }
    
    /**
     * Obtener lista de IPs bloqueadas permanentemente
     */
    public function getPermanentlyBlockedIPs() {
        $blocked_ips = $this->ipManager->getBlockedIPs();
        $permanent_blocks = [];
        
        foreach ($blocked_ips as $ip => $data) {
            // Si no tiene duración o la duración es null, es permanente
            if (!isset($data['duration']) || $data['duration'] === null) {
                $permanent_blocks[$ip] = $data;
            }
        }
        
        return $permanent_blocks;
    }
}

// Endpoint para recibir eventos de seguridad desde JavaScript
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['security_event'])) {
    header('Content-Type: application/json');
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    
    // Obtener IP real del cliente
    $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    foreach ($ip_keys as $key) {
        if (!empty($_SERVER[$key])) {
            $client_ip = $_SERVER[$key];
            if (strpos($client_ip, ',') !== false) {
                $client_ip = trim(explode(',', $client_ip)[0]);
            }
            if (filter_var($client_ip, FILTER_VALIDATE_IP)) {
                $ip = $client_ip;
                break;
            }
        }
    }
    
    $action = $_POST['action'] ?? 'unknown';
    $level = isset($_POST['level']) ? intval($_POST['level']) : -1;
    $attempts = isset($_POST['attempts']) ? intval($_POST['attempts']) : 0;
    
    $autoBlocker = new SecurityAutoBlocker();
    $result = $autoBlocker->processSecurityEvent($ip, $action, $level, $attempts);
    
    // Log adicional para debugging
    error_log("Security Auto-Blocker: IP=$ip, Action=$action, Level=$level, Attempts=$attempts, Blocked=" . ($result['blocked'] ? 'YES' : 'NO'));
    
    echo json_encode($result);
    exit;
}
?>
