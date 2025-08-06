<?php
/**
 * Gestor de IPs - Manejo de listas blancas y negras
 * Proyecto Web Explorer
 */

class IPManager {
    private $blocked_file;
    private $whitelist_file;
    private $data_dir;
    
    public function __construct() {
        $this->data_dir = __DIR__ . '/../data/ip-management';
        $this->blocked_file = $this->data_dir . '/blocked_ips.json';
        $this->whitelist_file = $this->data_dir . '/whitelist_ips.json';
        
        // Crear directorio si no existe con permisos correctos
        if (!is_dir($this->data_dir)) {
            if (!mkdir($this->data_dir, 0755, true)) {
                error_log("IPManager: No se pudo crear el directorio: " . $this->data_dir);
            }
        }
        
        // Verificar permisos del directorio
        if (!is_writable($this->data_dir)) {
            error_log("IPManager: El directorio no es escribible: " . $this->data_dir);
        }
        
        // Crear archivos si no existen
        $this->initializeFiles();
    }
    
    /**
     * Inicializar archivos JSON
     */
    private function initializeFiles() {
        if (!file_exists($this->blocked_file)) {
            if (file_put_contents($this->blocked_file, json_encode([], JSON_PRETTY_PRINT)) === false) {
                error_log("IPManager: No se pudo crear el archivo: " . $this->blocked_file);
            } else {
                chmod($this->blocked_file, 0644);
            }
        }
        
        if (!file_exists($this->whitelist_file)) {
            if (file_put_contents($this->whitelist_file, json_encode([], JSON_PRETTY_PRINT)) === false) {
                error_log("IPManager: No se pudo crear el archivo: " . $this->whitelist_file);
            } else {
                chmod($this->whitelist_file, 0644);
            }
        }
    }
    
    /**
     * Bloquear una IP
     */
    public function blockIP($ip, $reason = '', $duration = null, $admin_ip = '') {
        try {
            if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                return ['success' => false, 'message' => 'Dirección IP inválida'];
            }
            
            // Verificar permisos antes de proceder
            if (!$this->checkPermissions()) {
                return ['success' => false, 'message' => 'Error de permisos: No se puede escribir en el directorio de datos'];
            }
            
            $blocked_ips = $this->getBlockedIPsData();
            
            // Verificar si ya está bloqueada
            if (isset($blocked_ips[$ip])) {
                return ['success' => false, 'message' => 'La IP ya está bloqueada'];
            }
            
            // Agregar a la lista de bloqueadas
            $blocked_ips[$ip] = [
                'reason' => $reason ?: 'Bloqueada manualmente',
                'blocked_at' => date('Y-m-d H:i:s'),
                'blocked_by' => $admin_ip,
                'duration' => $duration,
                'expires_at' => $duration ? date('Y-m-d H:i:s', time() + ($duration * 60)) : null
            ];
            
            if ($this->saveBlockedIPs($blocked_ips)) {
                return ['success' => true, 'message' => 'IP bloqueada correctamente'];
            } else {
                return ['success' => false, 'message' => 'Error al guardar la IP bloqueada - Verificar permisos de archivos'];
            }
            
        } catch (Exception $e) {
            error_log("IPManager::blockIP Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error interno: ' . $e->getMessage()];
        }
    }
    
    /**
     * Desbloquear una IP
     */
    public function unblockIP($ip) {
        try {
            if (!$this->checkPermissions()) {
                return ['success' => false, 'message' => 'Error de permisos: No se puede escribir en el directorio de datos'];
            }
            
            $blocked_ips = $this->getBlockedIPsData();
            
            if (!isset($blocked_ips[$ip])) {
                return ['success' => false, 'message' => 'La IP no está bloqueada'];
            }
            
            unset($blocked_ips[$ip]);
            
            if ($this->saveBlockedIPs($blocked_ips)) {
                return ['success' => true, 'message' => 'IP desbloqueada correctamente'];
            } else {
                return ['success' => false, 'message' => 'Error al desbloquear la IP - Verificar permisos de archivos'];
            }
            
        } catch (Exception $e) {
            error_log("IPManager::unblockIP Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error interno: ' . $e->getMessage()];
        }
    }
    
    /**
     * Agregar IP a lista blanca
     */
    public function addToWhitelist($ip, $reason = '') {
        try {
            if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                return ['success' => false, 'message' => 'Dirección IP inválida'];
            }
            
            // Verificar permisos antes de proceder
            if (!$this->checkPermissions()) {
                return ['success' => false, 'message' => 'Error de permisos: No se puede escribir en el directorio de datos'];
            }
            
            $whitelist = $this->getWhitelistData();
            
            // Verificar si ya está en whitelist
            if (isset($whitelist[$ip])) {
                return ['success' => false, 'message' => 'La IP ya está en la lista blanca'];
            }
            
            // Agregar a whitelist
            $whitelist[$ip] = [
                'reason' => $reason ?: 'IP de confianza',
                'added_at' => date('Y-m-d H:i:s')
            ];
            
            if ($this->saveWhitelist($whitelist)) {
                return ['success' => true, 'message' => 'IP agregada a la lista blanca correctamente'];
            } else {
                return ['success' => false, 'message' => 'Error al agregar la IP a la lista blanca - Verificar permisos de archivos'];
            }
            
        } catch (Exception $e) {
            error_log("IPManager::addToWhitelist Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error interno: ' . $e->getMessage()];
        }
    }
    
    /**
     * Remover IP de lista blanca
     */
    public function removeFromWhitelist($ip) {
        try {
            if (!$this->checkPermissions()) {
                return ['success' => false, 'message' => 'Error de permisos: No se puede escribir en el directorio de datos'];
            }
            
            $whitelist = $this->getWhitelistData();
            
            if (!isset($whitelist[$ip])) {
                return ['success' => false, 'message' => 'La IP no está en la lista blanca'];
            }
            
            unset($whitelist[$ip]);
            
            if ($this->saveWhitelist($whitelist)) {
                return ['success' => true, 'message' => 'IP removida de la lista blanca correctamente'];
            } else {
                return ['success' => false, 'message' => 'Error al remover la IP de la lista blanca - Verificar permisos de archivos'];
            }
            
        } catch (Exception $e) {
            error_log("IPManager::removeFromWhitelist Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error interno: ' . $e->getMessage()];
        }
    }
    
    /**
     * Obtener IPs bloqueadas para mostrar
     */
    public function getBlockedIPs() {
        return $this->getBlockedIPsData();
    }
    
    /**
     * Obtener lista blanca para mostrar
     */
    public function getWhitelist() {
        return $this->getWhitelistData();
    }
    
    /**
     * Obtener estadísticas
     */
    public function getStats() {
        $blocked = $this->getBlockedIPsData();
        $whitelist = $this->getWhitelistData();
        
        $permanent_blocks = 0;
        $temporary_blocks = 0;
        
        foreach ($blocked as $data) {
            if (isset($data['duration']) && $data['duration']) {
                $temporary_blocks++;
            } else {
                $permanent_blocks++;
            }
        }
        
        return [
            'permanent_blocks' => $permanent_blocks,
            'temporary_blocks' => $temporary_blocks,
            'whitelist_count' => count($whitelist),
            'total_blocked' => count($blocked)
        ];
    }
    
    /**
     * Limpiar bloqueos expirados
     */
    public function cleanExpiredBlocks() {
        $blocked_ips = $this->getBlockedIPsData();
        $cleaned = false;
        $current_time = time();
        
        foreach ($blocked_ips as $ip => $data) {
            if (isset($data['expires_at']) && $data['expires_at'] && strtotime($data['expires_at']) < $current_time) {
                unset($blocked_ips[$ip]);
                $cleaned = true;
            }
        }
        
        if ($cleaned) {
            $this->saveBlockedIPs($blocked_ips);
        }
        
        return $cleaned;
    }
    
    /**
     * Verificar si una IP está bloqueada
     */
    public function isBlocked($ip) {
        $blocked_ips = $this->getBlockedIPsData();
        
        if (!isset($blocked_ips[$ip])) {
            return false;
        }
        
        $data = $blocked_ips[$ip];
        
        // Verificar si el bloqueo ha expirado
        if (isset($data['expires_at']) && $data['expires_at'] && strtotime($data['expires_at']) < time()) {
            // Remover bloqueo expirado
            unset($blocked_ips[$ip]);
            $this->saveBlockedIPs($blocked_ips);
            return false;
        }
        
        return true;
    }
    
    /**
     * Verificar si una IP está en whitelist
     */
    public function isWhitelisted($ip) {
        $whitelist = $this->getWhitelistData();
        return isset($whitelist[$ip]);
    }
    
    /**
     * Verificar permisos de archivos y directorios
     */
    private function checkPermissions() {
        // Verificar si el directorio existe y es escribible
        if (!is_dir($this->data_dir)) {
            if (!mkdir($this->data_dir, 0755, true)) {
                error_log("IPManager: No se pudo crear el directorio: " . $this->data_dir);
                return false;
            }
        }
        
        if (!is_writable($this->data_dir)) {
            error_log("IPManager: El directorio no es escribible: " . $this->data_dir);
            return false;
        }
        
        // Verificar archivos
        if (file_exists($this->blocked_file) && !is_writable($this->blocked_file)) {
            error_log("IPManager: El archivo blocked_ips.json no es escribible");
            return false;
        }
        
        if (file_exists($this->whitelist_file) && !is_writable($this->whitelist_file)) {
            error_log("IPManager: El archivo whitelist_ips.json no es escribible");
            return false;
        }
        
        return true;
    }
    
    /**
     * Obtener información de diagnóstico
     */
    public function getDiagnosticInfo() {
        return [
            'data_dir' => $this->data_dir,
            'data_dir_exists' => is_dir($this->data_dir),
            'data_dir_writable' => is_writable($this->data_dir),
            'blocked_file' => $this->blocked_file,
            'blocked_file_exists' => file_exists($this->blocked_file),
            'blocked_file_writable' => file_exists($this->blocked_file) ? is_writable($this->blocked_file) : 'N/A',
            'whitelist_file' => $this->whitelist_file,
            'whitelist_file_exists' => file_exists($this->whitelist_file),
            'whitelist_file_writable' => file_exists($this->whitelist_file) ? is_writable($this->whitelist_file) : 'N/A',
            'php_user' => get_current_user(),
            'permissions_ok' => $this->checkPermissions()
        ];
    }
    
    // Métodos privados para manejo de archivos
    
    private function getBlockedIPsData() {
        if (!file_exists($this->blocked_file)) {
            $this->initializeFiles();
            return [];
        }
        
        $content = file_get_contents($this->blocked_file);
        if ($content === false) {
            error_log("IPManager: No se pudo leer el archivo: " . $this->blocked_file);
            return [];
        }
        
        $data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("IPManager: Error JSON en blocked_ips: " . json_last_error_msg());
            return [];
        }
        
        return is_array($data) ? $data : [];
    }
    
    private function getWhitelistData() {
        if (!file_exists($this->whitelist_file)) {
            $this->initializeFiles();
            return [];
        }
        
        $content = file_get_contents($this->whitelist_file);
        if ($content === false) {
            error_log("IPManager: No se pudo leer el archivo: " . $this->whitelist_file);
            return [];
        }
        
        $data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("IPManager: Error JSON en whitelist: " . json_last_error_msg());
            return [];
        }
        
        return is_array($data) ? $data : [];
    }
    
    private function saveBlockedIPs($data) {
        $json_data = json_encode($data, JSON_PRETTY_PRINT);
        if ($json_data === false) {
            error_log("IPManager: Error al codificar JSON para blocked_ips");
            return false;
        }
        
        $result = file_put_contents($this->blocked_file, $json_data);
        if ($result === false) {
            error_log("IPManager: No se pudo escribir en: " . $this->blocked_file);
            return false;
        }
        
        return true;
    }
    
    private function saveWhitelist($data) {
        $json_data = json_encode($data, JSON_PRETTY_PRINT);
        if ($json_data === false) {
            error_log("IPManager: Error al codificar JSON para whitelist");
            return false;
        }
        
        $result = file_put_contents($this->whitelist_file, $json_data);
        if ($result === false) {
            error_log("IPManager: No se pudo escribir en: " . $this->whitelist_file);
            return false;
        }
        
        return true;
    }
}
?>
