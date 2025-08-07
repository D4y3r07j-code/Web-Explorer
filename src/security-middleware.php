<?php
/**
 * Middleware de seguridad - Verificación de IPs bloqueadas
 * Se incluye en todas las páginas principales para verificar bloqueos
 */

require_once __DIR__ . '/ip-manager.php';

class SecurityMiddleware {
    private $ipManager;
    
    public function __construct() {
        $this->ipManager = new IPManager();
    }
    
    public function getClientIP() {
        $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
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
    
    public function checkIP($ip) {
        // Verificar si está en whitelist (tiene prioridad)
        if ($this->ipManager->isWhitelisted($ip)) {
            return false; // No está bloqueada
        }
        
        // Verificar si está bloqueada
        if ($this->ipManager->isBlocked($ip)) {
            // Obtener información del bloqueo
            $blocked_ips = $this->ipManager->getBlockedIPs();
            $block_info = $blocked_ips[$ip] ?? null;
            
            // Mostrar página de bloqueo
            $this->showBlockedPage($ip, $block_info);
            exit;
        }
        
        return false;
    }
    
    private function showBlockedPage($ip, $block_info) {
        $is_permanent = !isset($block_info['expires_at']) || !$block_info['expires_at'];
        $block_type = $is_permanent ? 'PERMANENTE' : 'TEMPORAL';
        $reason = $block_info['reason'] ?? 'Múltiples violaciones de seguridad';
        $blocked_at = $block_info['blocked_at'] ?? date('Y-m-d H:i:s');
        $expires_at = $block_info['expires_at'] ?? null;
        $blocked_by = $block_info['blocked_by'] ?? 'Sistema automático';
        
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Acceso Bloqueado - Web Explorer</title>
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
                    background: rgba(0, 0, 0, 0.98);
                    color: #ffffff;
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 20px;
                    line-height: 1.6;
                }
                
                .blocked-container {
                    background: #121212;
                    border-radius: 16px;
                    padding: 40px;
                    max-width: 500px;
                    width: 100%;
                    text-align: center;
                    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
                    border: 1px solid #333;
                    animation: fadeIn 0.5s ease-out;
                }
                
                @keyframes fadeIn {
                    from {
                        opacity: 0;
                        transform: translateY(20px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }
                
                .blocked-icon {
                    width: 80px;
                    height: 80px;
                    background: #ff3b30;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin: 0 auto 24px;
                    border: 3px solid #ff6b6b;
                    box-shadow: 0 0 20px rgba(255, 59, 48, 0.3);
                }
                
                .blocked-icon i {
                    font-size: 32px;
                    color: white;
                }
                
                .blocked-title {
                    font-size: 28px;
                    font-weight: 700;
                    margin-bottom: 12px;
                    color: #ffffff;
                }
                
                .blocked-subtitle {
                    color: #cccccc;
                    margin-bottom: 24px;
                    font-size: 16px;
                }
                
                .block-type-badge {
                    display: inline-block;
                    background: #ff3b30;
                    color: white;
                    padding: 8px 16px;
                    border-radius: 20px;
                    font-size: 12px;
                    font-weight: 600;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    margin-bottom: 24px;
                }
                
                .ip-display {
                    background: #1a1a1a;
                    border: 1px solid #333;
                    border-radius: 12px;
                    padding: 16px;
                    margin-bottom: 24px;
                    font-family: 'Monaco', 'Menlo', monospace;
                    font-size: 18px;
                    font-weight: 600;
                    color: #ff3b30;
                    letter-spacing: 1px;
                }
                
                .block-details {
                    background: #1a1a1a;
                    border: 1px solid #333;
                    border-radius: 12px;
                    padding: 20px;
                    margin-bottom: 24px;
                    text-align: left;
                }
                
                .detail-grid {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 16px;
                    margin-bottom: 16px;
                }
                
                .detail-item {
                    background: #0a0a0a;
                    padding: 12px;
                    border-radius: 8px;
                    border: 1px solid #222;
                }
                
                .detail-label {
                    font-size: 11px;
                    text-transform: uppercase;
                    color: #888;
                    font-weight: 600;
                    letter-spacing: 0.5px;
                    margin-bottom: 4px;
                }
                
                .detail-value {
                    color: #ffffff;
                    font-weight: 500;
                    font-size: 14px;
                }
                
                .reason-section {
                    background: rgba(255, 59, 48, 0.1);
                    border: 1px solid rgba(255, 59, 48, 0.3);
                    border-radius: 8px;
                    padding: 16px;
                    margin-bottom: 16px;
                }
                
                .reason-label {
                    font-size: 11px;
                    text-transform: uppercase;
                    color: #ff6b6b;
                    font-weight: 600;
                    letter-spacing: 0.5px;
                    margin-bottom: 8px;
                    display: flex;
                    align-items: center;
                    gap: 6px;
                }
                
                .reason-text {
                    color: #ffffff;
                    font-size: 14px;
                    line-height: 1.5;
                }
                
                .info-section {
                    background: #1a1a1a;
                    border: 1px solid #333;
                    border-radius: 12px;
                    padding: 20px;
                    margin-bottom: 24px;
                    text-align: left;
                }
                
                .info-title {
                    color: #ffffff;
                    font-size: 16px;
                    font-weight: 600;
                    margin-bottom: 12px;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }
                
                .info-list {
                    list-style: none;
                    padding: 0;
                }
                
                .info-list li {
                    color: #cccccc;
                    font-size: 14px;
                    margin-bottom: 8px;
                    padding-left: 20px;
                    position: relative;
                }
                
                .info-list li:before {
                    content: '•';
                    color: #ff3b30;
                    position: absolute;
                    left: 0;
                    font-weight: bold;
                }
                
                .contact-section {
                    background: #0a0a0a;
                    border: 1px solid #222;
                    border-radius: 12px;
                    padding: 20px;
                    text-align: left;
                }
                
                .contact-title {
                    color: #ffffff;
                    font-size: 16px;
                    font-weight: 600;
                    margin-bottom: 8px;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }
                
                .contact-text {
                    color: #cccccc;
                    font-size: 14px;
                    line-height: 1.5;
                }
                
                .contact-email {
                    color: #ff3b30;
                    text-decoration: none;
                    font-weight: 500;
                }
                
                .contact-email:hover {
                    text-decoration: underline;
                }
                
                @media (max-width: 600px) {
                    .blocked-container {
                        padding: 30px 20px;
                        margin: 10px;
                    }
                    
                    .detail-grid {
                        grid-template-columns: 1fr;
                    }
                    
                    .blocked-title {
                        font-size: 24px;
                    }
                    
                    .ip-display {
                        font-size: 16px;
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
                    BLOQUEO <?php echo $block_type; ?>
                </div>
                
                <div class="ip-display">
                    <?php echo htmlspecialchars($ip); ?>
                </div>
                
                <div class="block-details">
                    <div class="reason-section">
                        <div class="reason-label">
                            <i class="fas fa-exclamation-triangle"></i>
                            Razón del bloqueo:
                        </div>
                        <div class="reason-text"><?php echo htmlspecialchars($reason); ?></div>
                    </div>
                    
                    <div class="detail-grid">
                        <div class="detail-item">
                            <div class="detail-label">Fecha de bloqueo</div>
                            <div class="detail-value"><?php echo date('d/m/Y H:i:s', strtotime($blocked_at)); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Bloqueado por</div>
                            <div class="detail-value"><?php echo htmlspecialchars($blocked_by); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Tipo de bloqueo</div>
                            <div class="detail-value"><?php echo $block_type; ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Estado</div>
                            <div class="detail-value" style="color: #ff3b30;">🔴 Activo</div>
                        </div>
                    </div>
                </div>
                
                <?php if ($is_permanent): ?>
                <div class="info-section">
                    <div class="info-title">
                        <i class="fas fa-shield-alt"></i>
                        🔒 BLOQUEO PERMANENTE ACTIVADO
                    </div>
                    <p style="color: #cccccc; font-size: 14px; margin-bottom: 12px;">
                        Tu dirección IP ha sido <strong style="color: #ff3b30;">bloqueada permanentemente</strong> debido a 
                        múltiples violaciones de seguridad (9 o más intentos de acceso no autorizados).
                    </p>
                    <div class="info-title" style="font-size: 14px; margin-bottom: 8px;">¿Qué significa esto?</div>
                    <ul class="info-list">
                        <li>El bloqueo es permanente y no expira automáticamente</li>
                        <li>Solo un administrador del sistema puede remover este bloqueo</li>
                        <li>Todos los intentos de acceso desde esta IP serán denegados</li>
                        <li>Esta acción ha sido registrada en el sistema de seguridad</li>
                    </ul>
                </div>
                <?php else: ?>
                <div class="info-section">
                    <div class="info-title">
                        <i class="fas fa-clock"></i>
                        Bloqueo Temporal
                    </div>
                    <p style="color: #cccccc; font-size: 14px;">
                        Este es un bloqueo temporal que expirará el: 
                        <strong style="color: #ff3b30;"><?php echo date('d/m/Y H:i:s', strtotime($expires_at)); ?></strong>
                    </p>
                </div>
                <?php endif; ?>
                
                <div class="contact-section">
                    <div class="contact-title">
                        <i class="fas fa-envelope"></i>
                        ¿Crees que esto es un error?
                    </div>
                    <p class="contact-text">
                        Si consideras que tu IP fue bloqueada por error, contacta al 
                        administrador del sistema en: 
                        <a href="mailto:admin@webexplorer.com" class="contact-email">admin@webexplorer.com</a>
                        proporcionando tu dirección IP y los detalles del problema.
                    </p>
                </div>
            </div>
        </body>
        </html>
        <?php
    }
}

// Crear instancia y ejecutar verificación automáticamente
$securityMiddleware = new SecurityMiddleware();
$client_ip = $securityMiddleware->getClientIP();
$securityMiddleware->checkIP($client_ip);
?>
