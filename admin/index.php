<?php
// Panel de administración principal
session_start();

// Verificar autenticación
require_once '../src/admin-auth.php';

if (!isAdminAuthenticated()) {
    header('Location: login.php');
    exit;
}

$stats = getSecurityStats();

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
    <title>Dashboard de Administración - Web Explorer</title>
    <link rel="stylesheet" href="assets/css/admin-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
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
                <li class="active">
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
                    <i class="fas fa-chart-line"></i>
                    Dashboard de Administración
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

            <!-- Estadísticas - Solo 3 tarjetas -->
            <div class="admin-stats">
                <div class="stat-card">
                    <div class="stat-content">
                        <div class="stat-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['security_events']; ?></h3>
                            <p>Eventos de Seguridad</p>
                            <div class="stat-change <?php echo $stats['security_trend'] > 0 ? 'negative' : 'positive'; ?>">
                                <i class="fas fa-arrow-<?php echo $stats['security_trend'] > 0 ? 'up' : 'down'; ?>"></i>
                                <?php echo abs($stats['security_trend']); ?> desde ayer
                            </div>
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-content">
                        <div class="stat-icon">
                            <i class="fas fa-ban"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['blocked_ips']; ?></h3>
                            <p>IPs Bloqueadas</p>
                            <div class="stat-change <?php echo $stats['blocked_trend'] > 0 ? 'negative' : 'positive'; ?>">
                                <i class="fas fa-arrow-<?php echo $stats['blocked_trend'] > 0 ? 'up' : 'down'; ?>"></i>
                                <?php echo abs($stats['blocked_trend']); ?> desde ayer
                            </div>
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-content">
                        <div class="stat-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($stats['pdf_views']); ?></h3>
                            <p>PDFs Accedidos</p>
                            <div class="stat-change <?php echo $stats['pdf_trend'] > 0 ? 'positive' : 'negative'; ?>">
                                <i class="fas fa-arrow-<?php echo $stats['pdf_trend'] > 0 ? 'up' : 'down'; ?>"></i>
                                <?php echo abs($stats['pdf_trend']); ?>% desde ayer
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="admin-widgets">
                <div class="widget" style="grid-column: 1 / -1;">
                    <h3>
                        <i class="fas fa-chart-line"></i>
                        Actividad Reciente
                    </h3>
                    <div class="recent-activity">
                        <p style="color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 1rem;">
                            Últimas acciones registradas en el servidor
                        </p>
                        <?php foreach (getRecentActivity() as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon <?php echo $activity['type'] ?? 'success'; ?>">
                                    <i class="fas fa-<?php echo $activity['icon']; ?>"></i>
                                </div>
                                <div class="activity-details">
                                    <p><?php echo htmlspecialchars($activity['description']); ?></p>
                                    <small><?php echo $activity['time']; ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Animación de contadores
        document.addEventListener('DOMContentLoaded', function() {
            const counters = document.querySelectorAll('.stat-info h3');
            
            counters.forEach(counter => {
                const target = parseInt(counter.textContent.replace(/,/g, ''));
                let current = 0;
                const increment = target / 50;
                
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        current = target;
                        clearInterval(timer);
                    }
                    counter.textContent = Math.floor(current).toLocaleString();
                }, 30);
            });
        });
    </script>
</body>
</html>
