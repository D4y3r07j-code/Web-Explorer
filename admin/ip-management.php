<?php
session_start();
require_once '../src/admin-auth.php';
require_once '../src/ip-manager.php';

if (!isAdminAuthenticated()) {
    header('Location: login.php');
    exit;
}

$ipManager = new IPManager();
$message = '';
$messageType = '';

// Obtener IP del cliente
function getClientIP() {
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

$current_ip = getClientIP();

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'block_permanent':
            $ip = trim($_POST['ip'] ?? '');
            $reason = trim($_POST['reason'] ?? '');
            
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                $result = $ipManager->blockIP($ip, $reason, null, $current_ip);
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
            } else {
                $message = 'Dirección IP inválida';
                $messageType = 'error';
            }
            break;
            
        case 'block_temporary':
            $ip = trim($_POST['ip'] ?? '');
            $reason = trim($_POST['reason'] ?? '');
            $duration = intval($_POST['duration'] ?? 60);
            
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                $result = $ipManager->blockIP($ip, $reason, $duration, $current_ip);
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
            } else {
                $message = 'Dirección IP inválida';
                $messageType = 'error';
            }
            break;
            
        case 'unblock':
            $ip = trim($_POST['ip'] ?? '');
            $result = $ipManager->unblockIP($ip);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            break;
            
        case 'add_whitelist':
            $ip = trim($_POST['ip'] ?? '');
            $reason = trim($_POST['reason'] ?? '');
            
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                $result = $ipManager->addToWhitelist($ip, $reason);
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
            } else {
                $message = 'Dirección IP inválida';
                $messageType = 'error';
            }
            break;
            
        case 'remove_whitelist':
            $ip = trim($_POST['ip'] ?? '');
            $result = $ipManager->removeFromWhitelist($ip);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            break;
            
        case 'clean_expired':
            $cleaned = $ipManager->cleanExpiredBlocks();
            $message = $cleaned ? 'Bloqueos expirados limpiados correctamente' : 'No hay bloqueos expirados para limpiar';
            $messageType = 'success';
            break;
    }
}

// Obtener datos
$blocked_ips = $ipManager->getBlockedIPs();
$whitelist = $ipManager->getWhitelist();
$stats = $ipManager->getStats();

// Obtener IP del servidor
function getServerIP() {
    return $_SERVER['SERVER_ADDR'] ?? '203.0.113.42';
}

$server_ip = getServerIP();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de IPs - Panel Admin</title>
    <link rel="stylesheet" href="assets/css/admin-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        .ip-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            padding: 2rem;
        }
        
        .ip-stat-card {
            background: var(--card-bg);
            border-radius: var(--radius-xl);
            padding: 1.5rem;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            min-height: 140px;
            display: flex;
            align-items: center;
        }
        
        .ip-stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .ip-stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }
        
        .ip-stat-card:nth-child(1)::before {
            background: linear-gradient(90deg, var(--danger), #f87171);
        }
        
        .ip-stat-card:nth-child(2)::before {
            background: linear-gradient(90deg, var(--success), #34d399);
        }
        
        .ip-stat-content {
            display: flex;
            align-items: center;
            gap: 1rem;
            width: 100%;
        }
        
        .ip-stat-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }
        
        .ip-stat-card:nth-child(1) .ip-stat-icon {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }
        
        .ip-stat-card:nth-child(2) .ip-stat-icon {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }
        
        .ip-stat-info {
            flex: 1;
        }
        
        .ip-stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            color: var(--text-primary);
        }
        
        .ip-stat-label {
            color: var(--text-secondary);
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .ip-stat-subtitle {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
        }
        
        .ip-tabs {
            display: flex;
            background: var(--card-bg);
            border-radius: var(--radius-xl) var(--radius-xl) 0 0;
            overflow: hidden;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            border-bottom: none;
            margin: 0 2rem;
        }
        
        .ip-tab {
            flex: 1;
            padding: 1rem 1.5rem;
            background: #f8fafc;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .ip-tab.active {
            background: var(--card-bg);
            color: var(--primary-blue);
            border-bottom: 3px solid var(--primary-blue);
        }
        
        .ip-tab:hover:not(.active) {
            background: #f1f5f9;
        }
        
        .ip-tab-content {
            display: none;
            background: var(--card-bg);
            border-radius: 0 0 var(--radius-xl) var(--radius-xl);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            border-top: none;
            margin: 0 2rem 2rem;
            overflow: hidden;
        }
        
        .ip-tab-content.active {
            display: block;
        }
        
        .ip-content-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border-color);
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        }
        
        .ip-content-header h3 {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.5rem;
        }
        
        .ip-content-header p {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }
        
        .ip-content-body {
            padding: 2rem;
        }
        
        .ip-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .ip-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            margin-bottom: 1rem;
            background: #f8fafc;
            transition: all 0.2s ease;
        }
        
        .ip-item:hover {
            background: #f1f5f9;
            transform: translateY(-1px);
            box-shadow: var(--shadow-sm);
        }
        
        .ip-item:last-child {
            margin-bottom: 0;
        }
        
        .ip-info h4 {
            margin: 0 0 0.5rem 0;
            color: var(--text-primary);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .ip-badge {
            background: var(--danger);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: var(--radius-md);
            font-size: 0.75rem;
            font-weight: 600;
            font-family: 'Monaco', 'Menlo', monospace;
        }
        
        .ip-requests {
            background: var(--text-muted);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: var(--radius-sm);
            font-size: 0.75rem;
            margin-left: 0.5rem;
        }
        
        .ip-info p {
            margin: 0.25rem 0;
            font-size: 0.875rem;
            color: var(--text-secondary);
        }
        
        .ip-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .form-section {
            background: var(--card-bg);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            margin: 0 2rem 2rem;
            overflow: hidden;
        }
        
        .form-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border-color);
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        }
        
        .form-header h3 {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.5rem;
        }
        
        .form-body {
            padding: 2rem;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .form-grid.single {
            grid-template-columns: 1fr;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-primary);
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            transition: border-color 0.2s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
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
        
        .empty-state h4 {
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
            background-color: var(--success-light);
            color: var(--success);
            border-color: #bbf7d0;
        }
        
        .alert-error {
            background-color: var(--danger-light);
            color: var(--danger);
            border-color: #fecaca;
        }
        
        .alert-info {
            background-color: #dbeafe;
            color: #1d4ed8;
            border-color: #93c5fd;
        }
        
        @media (max-width: 768px) {
            .ip-stats {
                grid-template-columns: 1fr;
                padding: 1rem;
            }
            
            .ip-tabs {
                margin: 0 1rem;
            }
            
            .ip-tab-content {
                margin: 0 1rem 1rem;
            }
            
            .form-section {
                margin: 0 1rem 1rem;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
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
                <li>
                    <a href="security-logs.php">
                        <div class="menu-item-main">
                            <i class="fas fa-eye"></i>
                            <span>Logs de Apache</span>
                        </div>
                        <div class="menu-item-subtitle">Registros del servidor</div>
                    </a>
                </li>
                <li class="active">
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
                    <i class="fas fa-ban"></i>
                    Gestión de IPs
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

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : ($messageType === 'info' ? 'info-circle' : 'exclamation-circle'); ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Estadísticas de IPs -->
            <div class="ip-stats">
                <div class="ip-stat-card">
                    <div class="ip-stat-content">
                        <div class="ip-stat-icon">
                            <i class="fas fa-ban"></i>
                        </div>
                        <div class="ip-stat-info">
                            <div class="ip-stat-number"><?php echo $stats['total_blocked']; ?></div>
                            <div class="ip-stat-label">IPs Bloqueadas</div>
                            <div class="ip-stat-subtitle">Direcciones IP denegadas</div>
                        </div>
                    </div>
                </div>

                <div class="ip-stat-card">
                    <div class="ip-stat-content">
                        <div class="ip-stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="ip-stat-info">
                            <div class="ip-stat-number"><?php echo $stats['whitelist_count']; ?></div>
                            <div class="ip-stat-label">Lista Blanca</div>
                            <div class="ip-stat-subtitle">Direcciones IP autorizadas</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pestañas -->
            <div class="ip-tabs">
                <button class="ip-tab active" onclick="showTab('blocked')">
                    <i class="fas fa-ban"></i>
                    IPs Bloqueadas
                </button>
                <button class="ip-tab" onclick="showTab('whitelist')">
                    <i class="fas fa-check-circle"></i>
                    Lista Blanca
                </button>
                <div style="margin-left: auto; padding: 0.5rem 1rem;">
                    <button class="btn btn-primary" onclick="showTab('add')">
                        <i class="fas fa-plus"></i>
                        Agregar IP
                    </button>
                </div>
            </div>

            <!-- Contenido de IPs Bloqueadas -->
            <div id="blocked-tab" class="ip-tab-content active">
                <div class="ip-content-header">
                    <h3>
                        <i class="fas fa-ban"></i>
                        Direcciones IP Bloqueadas
                    </h3>
                    <p>Lista de IPs que tienen el acceso denegado al servidor</p>
                </div>
                <div class="ip-content-body">
                    <?php if (empty($blocked_ips)): ?>
                        <div class="empty-state">
                            <i class="fas fa-check-circle"></i>
                            <h4>No hay IPs bloqueadas</h4>
                            <p>Todas las IPs tienen acceso libre al sistema.</p>
                        </div>
                    <?php else: ?>
                        <div class="ip-list">
                            <?php foreach ($blocked_ips as $ip => $data): ?>
                                <div class="ip-item">
                                    <div class="ip-info">
                                        <h4>
                                            <span class="ip-badge"><?php echo htmlspecialchars($ip); ?></span>
                                            <span class="ip-requests">247 requests</span>
                                        </h4>
                                        <p><strong>Razón:</strong> <?php echo htmlspecialchars($data['reason'] ?: 'Múltiples intentos de acceso fallidos'); ?></p>
                                        <p><strong>Bloqueada el:</strong> <?php echo htmlspecialchars($data['blocked_at']); ?></p>
                                        <?php if (isset($data['expires_at']) && $data['expires_at']): ?>
                                            <p><strong>Expira el:</strong> <?php echo htmlspecialchars($data['expires_at']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="ip-actions">
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="action" value="unblock">
                                            <input type="hidden" name="ip" value="<?php echo htmlspecialchars($ip); ?>">
                                            <button type="submit" class="btn btn-danger" onclick="return confirm('¿Desbloquear esta IP?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Contenido de Lista Blanca -->
            <div id="whitelist-tab" class="ip-tab-content">
                <div class="ip-content-header">
                    <h3>
                        <i class="fas fa-check-circle"></i>
                        Lista Blanca de IPs
                    </h3>
                    <p>IPs autorizadas que siempre tienen acceso al servidor</p>
                </div>
                <div class="ip-content-body">
                    <?php if (empty($whitelist)): ?>
                        <div class="empty-state">
                            <i class="fas fa-shield-alt"></i>
                            <h4>No hay IPs en whitelist</h4>
                            <p>Las IPs de confianza aparecerán aquí.</p>
                        </div>
                    <?php else: ?>
                        <div class="ip-list">
                            <?php foreach ($whitelist as $ip => $data): ?>
                                <div class="ip-item">
                                    <div class="ip-info">
                                        <h4>
                                            <span class="ip-badge" style="background: var(--success);"><?php echo htmlspecialchars($ip); ?></span>
                                        </h4>
                                        <p><strong>Razón:</strong> <?php echo htmlspecialchars($data['reason'] ?: 'IP de confianza'); ?></p>
                                        <p><strong>Agregada el:</strong> <?php echo htmlspecialchars($data['added_at']); ?></p>
                                    </div>
                                    <div class="ip-actions">
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="action" value="remove_whitelist">
                                            <input type="hidden" name="ip" value="<?php echo htmlspecialchars($ip); ?>">
                                            <button type="submit" class="btn btn-danger" onclick="return confirm('¿Remover de whitelist?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Formulario para Agregar IP -->
            <div id="add-tab" class="ip-tab-content">
                <div class="form-section">
                    <div class="form-header">
                        <h3>
                            <i class="fas fa-plus"></i>
                            Agregar Nueva IP
                        </h3>
                    </div>
                    <div class="form-body">
                        <h4>Bloqueo Permanente</h4>
                        <form method="post">
                            <input type="hidden" name="action" value="block_permanent">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Dirección IP:</label>
                                    <input type="text" name="ip" placeholder="192.168.1.100" required>
                                </div>
                                <div class="form-group">
                                    <label>Razón del Bloqueo:</label>
                                    <textarea name="reason" placeholder="Actividad sospechosa, spam, etc." rows="3"></textarea>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-ban"></i>
                                Bloquear Permanentemente
                            </button>
                        </form>

                        <hr style="margin: 2rem 0; border: none; border-top: 1px solid var(--border-color);">

                        <h4>Agregar a Lista Blanca</h4>
                        <form method="post">
                            <input type="hidden" name="action" value="add_whitelist">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Dirección IP:</label>
                                    <input type="text" name="ip" placeholder="192.168.1.100" required>
                                </div>
                                <div class="form-group">
                                    <label>Razón:</label>
                                    <input type="text" name="reason" placeholder="IP de confianza, administrador, etc.">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-plus"></i>
                                Agregar a Lista Blanca
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function showTab(tabName) {
            // Ocultar todas las pestañas
            document.querySelectorAll('.ip-tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.ip-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Mostrar la pestaña seleccionada
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Activar el botón correspondiente
            if (tabName !== 'add') {
                event.target.classList.add('active');
            }
        }

        // Animación de contadores
        document.addEventListener('DOMContentLoaded', function() {
            const counters = document.querySelectorAll('.ip-stat-number');
            
            counters.forEach(counter => {
                const target = parseInt(counter.textContent.replace(/,/g, ''));
                let current = 0;
                const increment = target / 30;
                
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        current = target;
                        clearInterval(timer);
                    }
                    counter.textContent = Math.floor(current).toLocaleString();
                }, 50);
            });
        });
    </script>
</body>
</html>
