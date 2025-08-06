<?php
require_once __DIR__ . '/ip-manager.php';

class SecurityMiddleware {
    private $ipManager;
    
    public function __construct() {
        $this->ipManager = new IPManager();
    }
    
    public function checkIP($ip) {
        // Limpiar bloqueos expirados (solo los temporales)
        $this->ipManager->cleanExpiredBlocks();
        
        // Verificar si la IP está bloqueada
        if ($this->ipManager->isBlocked($ip)) {
            $blocked_data = $this->getBlockedIPData($ip);
            $this->showBlockedPage($ip, $blocked_data);
            exit;
        }
    }
    
    private function getBlockedIPData($ip) {
        $blocked_ips = $this->ipManager->getBlockedIPs();
        return $blocked_ips[$ip] ?? null;
    }
    
    private function showBlockedPage($ip, $blocked_data) {
        http_response_code(403);
        
        // Determinar si es bloqueo permanente o temporal
        $is_permanent = !isset($blocked_data['duration']) || $blocked_data['duration'] === null;
        $reason = $blocked_data['reason'] ?? 'Múltiples violaciones de seguridad detectadas';
        $blocked_at = $blocked_data['blocked_at'] ?? 'Fecha desconocida';
        $blocked_by = $blocked_data['blocked_by'] ?? 'Sistema de Seguridad';
        
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>IP Bloqueada - Web Explorer</title>
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                
                body {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                    background-color: rgba(0, 0, 0, 0.98);
                    margin: 0;
                    padding: 0;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                    color: white;
                }
                
                .blocked-container {
                    background-color: #121212;
                    border-radius: 12px;
                    padding: 40px;
                    max-width: 500px;
                    width: 90%;
                    text-align: center;
                    color: white;
                    animation: fadeIn 0.4s ease-out;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
                }
                
                .blocked-icon {
                    font-size: 50px;
                    margin-bottom: 24px;
                    color: #ff3b30;
                    background-color: transparent;
                    width: 80px;
                    height: 80px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    border-radius: 50%;
                    border: 3px solid #ff3b30;
                }
                
                .blocked-title {
                    font-size: 28px;
                    margin-bottom: 16px;
                    color: white;
                    font-weight: 600;
                }
                
                .blocked-subtitle {
                    font-size: 16px;
                    margin-bottom: 24px;
                    color: rgba(255, 255, 255, 0.7);
                    font-weight: 400;
                }
                
                .block-type-badge {
                    background-color: #ff3b30;
                    color: white;
                    padding: 8px 16px;
                    border-radius: 6px;
                    font-weight: 600;
                    display: inline-block;
                    margin-bottom: 24px;
                    font-size: 14px;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }
                
                .ip-display {
                    background-color: rgba(255, 255, 255, 0.1);
                    padding: 12px 20px;
                    border-radius: 8px;
                    display: inline-block;
                    margin: 16px 0;
                    font-family: 'Courier New', monospace;
                    font-size: 18px;
                    font-weight: bold;
                    letter-spacing: 1px;
                    border: 1px solid rgba(255, 255, 255, 0.2);
                    color: white;
                }
                
                .blocked-reason {
                    background-color: rgba(255, 59, 48, 0.1);
                    border-left: 3px solid #ff3b30;
                    padding: 16px;
                    margin: 20px 0;
                    border-radius: 4px;
                    text-align: left;
                    font-size: 14px;
                    line-height: 1.5;
                }
                
                .block-info-grid {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 12px;
                    margin: 20px 0;
                    width: 100%;
                }
                
                .info-item {
                    background-color: rgba(255, 255, 255, 0.05);
                    padding: 12px;
                    border-radius: 6px;
                    border: 1px solid rgba(255, 255, 255, 0.1);
                    text-align: left;
                }
                
                .info-label {
                    font-size: 11px;
                    color: rgba(255, 255, 255, 0.6);
                    margin-bottom: 4px;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    font-weight: 500;
                }
                
                .info-value {
                    font-size: 13px;
                    font-weight: 600;
                    color: white;
                }
                
                .blocked-info {
                    background-color: rgba(255, 255, 255, 0.05);
                    border-radius: 8px;
                    padding: 20px;
                    margin: 20px 0;
                    text-align: left;
                    border: 1px solid rgba(255, 255, 255, 0.1);
                }
                
                .blocked-info h4 {
                    color: #ff3b30;
                    font-size: 16px;
                    margin-bottom: 12px;
                    font-weight: 600;
                }
                
                .blocked-info p {
                    font-size: 14px;
                    line-height: 1.6;
                    margin-bottom: 12px;
                    color: rgba(255, 255, 255, 0.8);
                }
                
                .blocked-info ul {
                    margin: 12px 0;
                    padding-left: 20px;
                }
                
                .blocked-info li {
                    font-size: 13px;
                    line-height: 1.5;
                    margin-bottom: 6px;
                    color: rgba(255, 255, 255, 0.7);
                }
                
                .contact-info {
                    background-color: rgba(59, 130, 246, 0.1);
                    border-left: 3px solid #3b82f6;
                    padding: 16px;
                    margin: 20px 0;
                    border-radius: 4px;
                    text-align: left;
                    font-size: 14px;
                    line-height: 1.5;
                }
                
                .security-badge {
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
                    background-color: rgba(255, 255, 255, 0.1);
                    padding: 10px 16px;
                    border-radius: 20px;
                    font-size: 12px;
                    margin-top: 20px;
                    border: 1px solid rgba(255, 255, 255, 0.2);
                    font-weight: 500;
                    color: rgba(255, 255, 255, 0.8);
                }
                
                .warning-text {
                    color: #fbbf24;
                    font-weight: 600;
                }
                
                .critical-text {
                    color: #ff3b30;
                    font-weight: 600;
                }
                
                @keyframes fadeIn {
                    0% {
                        opacity: 0;
                        transform: translateY(-20px);
                    }
                    100% {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }
                
                @media (max-width: 768px) {
                    .blocked-container {
                        padding: 30px 20px;
                        max-width: 400px;
                    }
                    
                    .blocked-icon {
                        font-size: 40px;
                        width: 70px;
                        height: 70px;
                    }
                    
                    .blocked-title {
                        font-size: 24px;
                    }
                    
                    .ip-display {
                        font-size: 16px;
                        padding: 10px 16px;
                    }
                    
                    .block-info-grid {
                        grid-template-columns: 1fr;
                    }
                }
            </style>
        </head>
        <body>
            <div class="blocked-container">
                <div class="blocked-icon">
                    <i class="fas fa-ban"></i>
                </div>
                
                <h1 class="blocked-title">IP Bloqueada</h1>
                <p class="blocked-subtitle">Tu dirección IP ha sido bloqueada por el sistema de seguridad</p>
                
                <div class="block-type-badge">
                    <?php echo $is_permanent ? 'BLOQUEO PERMANENTE' : 'BLOQUEO TEMPORAL'; ?>
                </div>
                
                <div class="ip-display"><?php echo htmlspecialchars($ip); ?></div>
                
                <div class="blocked-reason">
                    <strong><i class="fas fa-exclamation-triangle"></i> Razón del bloqueo:</strong><br>
                    <?php echo htmlspecialchars($reason); ?>
                </div>
                
                <div class="block-info-grid">
                    <div class="info-item">
                        <div class="info-label">Fecha de bloqueo</div>
                        <div class="info-value"><?php echo htmlspecialchars($blocked_at); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Bloqueado por</div>
                        <div class="info-value"><?php echo htmlspecialchars($blocked_by); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Tipo de bloqueo</div>
                        <div class="info-value"><?php echo $is_permanent ? 'Permanente' : 'Temporal'; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Estado</div>
                        <div class="info-value critical-text">🔒 Activo</div>
                    </div>
                </div>
                
                <div class="blocked-info">
                    <?php if ($is_permanent): ?>
                        <h4>🚨 BLOQUEO PERMANENTE ACTIVADO</h4>
                        <p>Tu dirección IP ha sido <span class="critical-text">bloqueada permanentemente</span> debido a múltiples violaciones de seguridad (9 o más intentos de acceso no autorizado).</p>
                        
                        <p><strong>¿Qué significa esto?</strong></p>
                        <ul>
                            <li>El bloqueo es <strong>permanente</strong> y no expira automáticamente</li>
                            <li>Solo un <strong>administrador del sistema</strong> puede remover este bloqueo</li>
                            <li>Todos los intentos de acceso desde esta IP serán denegados</li>
                            <li>Esta acción ha sido registrada en el sistema de seguridad</li>
                        </ul>
                    <?php else: ?>
                        <h4>⏰ BLOQUEO TEMPORAL ACTIVADO</h4>
                        <p>Tu dirección IP ha sido bloqueada temporalmente debido a actividades sospechosas.</p>
                        
                        <p><strong>¿Cuánto durará el bloqueo?</strong></p>
                        <p>Este es un bloqueo temporal que expirará automáticamente. Después de este tiempo, el acceso será restaurado.</p>
                    <?php endif; ?>
                </div>
                
                <div class="contact-info">
                    <p><strong>¿Crees que esto es un error?</strong></p>
                    <p>Si consideras que tu IP fue bloqueada por error, contacta al administrador del sistema. 
                    <?php if ($is_permanent): ?>
                    <span class="warning-text">Solo un administrador puede remover bloqueos permanentes.</span>
                    <?php endif; ?>
                    </p>
                </div>
                
                <div class="security-badge">
                    <i class="fas fa-shield-alt"></i>
                    <span>Sistema de Seguridad WebExplorer</span>
                </div>
            </div>
        </body>
        </html>
        <?php
    }
    
    public function getClientIP() {
        $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Si hay múltiples IPs, tomar la primera
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}
?>
