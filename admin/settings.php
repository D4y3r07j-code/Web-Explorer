<?php
session_start();
require_once '../src/admin-auth.php';
require_once '../config/app-config.php';
require_once '../config/security-config.php';

// Verificar autenticación
if (!isAdminAuthenticated()) {
    header('Location: login.php');
    exit;
}

$message = '';
$messageType = '';

// Procesar formulario de configuraciones
if ($_POST) {
    try {
        if (isset($_POST['update_general'])) {
            // Actualizar configuraciones generales
            $newConfig = [
                'site_title' => sanitize($_POST['site_title']),
                'items_per_page' => (int)$_POST['items_per_page'],
                'enable_thumbnails' => isset($_POST['enable_thumbnails']),
                'max_file_size' => (int)$_POST['max_file_size'],
                'allowed_extensions' => array_map('trim', explode(',', $_POST['allowed_extensions']))
            ];
            
            updateAppConfig($newConfig);
            $message = 'Configuraciones generales actualizadas correctamente';
            $messageType = 'success';
        }
        
        if (isset($_POST['update_security'])) {
            // Actualizar configuraciones de seguridad
            $newSecurityConfig = [
                'enable_logging' => isset($_POST['enable_logging']),
                'max_login_attempts' => (int)$_POST['max_login_attempts'],
                'session_timeout' => (int)$_POST['session_timeout'],
                'enable_ip_blocking' => isset($_POST['enable_ip_blocking']),
                'watermark_enabled' => isset($_POST['watermark_enabled']),
                'disable_right_click' => isset($_POST['disable_right_click'])
            ];
            
            updateSecurityConfig($newSecurityConfig);
            $message = 'Configuraciones de seguridad actualizadas correctamente';
            $messageType = 'success';
        }
        
        if (isset($_POST['change_password'])) {
            // Cambiar contraseña de administrador
            $currentPassword = $_POST['current_password'];
            $newPassword = $_POST['new_password'];
            $confirmPassword = $_POST['confirm_password'];
            
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                throw new Exception('Todos los campos son obligatorios');
            }
            
            if ($newPassword !== $confirmPassword) {
                throw new Exception('Las contraseñas no coinciden');
            }
            
            if (strlen($newPassword) < 8) {
                throw new Exception('La contraseña debe tener al menos 8 caracteres');
            }
            
            if (changeAdminPassword($currentPassword, $newPassword)) {
                $message = 'Contraseña cambiada correctamente';
                $messageType = 'success';
            } else {
                throw new Exception('Contraseña actual incorrecta');
            }
        }
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// Obtener configuraciones actuales
$appConfig = getAppConfig();
$securityConfig = getSecurityConfig();

function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function updateAppConfig($newConfig) {
    $configFile = '../config/app-config.php';
    $currentConfig = include $configFile;
    $updatedConfig = array_merge($currentConfig, $newConfig);
    
    $configContent = "<?php\nreturn " . var_export($updatedConfig, true) . ";\n";
    file_put_contents($configFile, $configContent);
}

function updateSecurityConfig($newConfig) {
    $configFile = '../config/security-config.php';
    $currentConfig = include $configFile;
    $updatedConfig = array_merge($currentConfig, $newConfig);
    
    $configContent = "<?php\nreturn " . var_export($updatedConfig, true) . ";\n";
    file_put_contents($configFile, $configContent);
}

function getAppConfig() {
    return include '../config/app-config.php';
}

function getSecurityConfig() {
    return include '../config/security-config.php';
}

function changeAdminPassword($currentPassword, $newPassword) {
    // Verificar contraseña actual
    $adminConfig = include '../config/admin-config.php';
    
    if (!password_verify($currentPassword, $adminConfig['password'])) {
        return false;
    }
    
    // Actualizar contraseña
    $adminConfig['password'] = password_hash($newPassword, PASSWORD_ARGON2ID);
    $configContent = "<?php\nreturn " . var_export($adminConfig, true) . ";\n";
    file_put_contents('../config/admin-config.php', $configContent);
    
    return true;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuraciones - Panel de Administración</title>
    <link rel="stylesheet" href="assets/css/admin-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <nav class="admin-sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-cog"></i> Admin Panel</h2>
            </div>
            <ul class="sidebar-menu">
                <li><a href="index.php"><i class="fas fa-dashboard"></i> Dashboard</a></li>
                <li><a href="security-logs.php"><i class="fas fa-shield-alt"></i> Logs de Seguridad</a></li>
                <li class="active"><a href="settings.php"><i class="fas fa-cog"></i> Configuraciones</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="admin-main">
            <div class="admin-header">
                <h1><i class="fas fa-cog"></i> Configuraciones del Sistema</h1>
                <div class="admin-user">
                    <span>Administrador</span>
                    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i></a>
                </div>
            </div>

            <div class="admin-content">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?>">
                        <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <!-- Configuraciones Generales -->
                <div class="settings-section">
                    <h2><i class="fas fa-sliders-h"></i> Configuraciones Generales</h2>
                    <form method="POST" class="settings-form">
                        <div class="form-group">
                            <label for="site_title">Título del Sitio:</label>
                            <input type="text" id="site_title" name="site_title" 
                                   value="<?php echo htmlspecialchars($appConfig['site_title'] ?? 'Explorador de Archivos'); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="items_per_page">Elementos por Página:</label>
                            <input type="number" id="items_per_page" name="items_per_page" 
                                   value="<?php echo $appConfig['items_per_page'] ?? 20; ?>" min="5" max="100" required>
                        </div>

                        <div class="form-group">
                            <label for="max_file_size">Tamaño Máximo de Archivo (MB):</label>
                            <input type="number" id="max_file_size" name="max_file_size" 
                                   value="<?php echo $appConfig['max_file_size'] ?? 50; ?>" min="1" max="500" required>
                        </div>

                        <div class="form-group">
                            <label for="allowed_extensions">Extensiones Permitidas (separadas por comas):</label>
                            <input type="text" id="allowed_extensions" name="allowed_extensions" 
                                   value="<?php echo implode(', ', $appConfig['allowed_extensions'] ?? ['pdf', 'jpg', 'png', 'txt']); ?>" required>
                        </div>

                        <div class="form-group checkbox-group">
                            <label>
                                <input type="checkbox" name="enable_thumbnails" 
                                       <?php echo ($appConfig['enable_thumbnails'] ?? true) ? 'checked' : ''; ?>>
                                <span class="checkmark"></span>
                                Habilitar Miniaturas
                            </label>
                        </div>

                        <button type="submit" name="update_general" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar Configuraciones Generales
                        </button>
                    </form>
                </div>

                <!-- Configuraciones de Seguridad -->
                <div class="settings-section">
                    <h2><i class="fas fa-shield-alt"></i> Configuraciones de Seguridad</h2>
                    <form method="POST" class="settings-form">
                        <div class="form-group">
                            <label for="max_login_attempts">Máximo Intentos de Login:</label>
                            <input type="number" id="max_login_attempts" name="max_login_attempts" 
                                   value="<?php echo $securityConfig['max_login_attempts'] ?? 3; ?>" min="1" max="10" required>
                        </div>

                        <div class="form-group">
                            <label for="session_timeout">Tiempo de Sesión (minutos):</label>
                            <input type="number" id="session_timeout" name="session_timeout" 
                                   value="<?php echo $securityConfig['session_timeout'] ?? 30; ?>" min="5" max="480" required>
                        </div>

                        <div class="form-group checkbox-group">
                            <label>
                                <input type="checkbox" name="enable_logging" 
                                       <?php echo ($securityConfig['enable_logging'] ?? true) ? 'checked' : ''; ?>>
                                <span class="checkmark"></span>
                                Habilitar Registro de Logs
                            </label>
                        </div>

                        <div class="form-group checkbox-group">
                            <label>
                                <input type="checkbox" name="enable_ip_blocking" 
                                       <?php echo ($securityConfig['enable_ip_blocking'] ?? true) ? 'checked' : ''; ?>>
                                <span class="checkmark"></span>
                                Habilitar Bloqueo por IP
                            </label>
                        </div>

                        <div class="form-group checkbox-group">
                            <label>
                                <input type="checkbox" name="watermark_enabled" 
                                       <?php echo ($securityConfig['watermark_enabled'] ?? true) ? 'checked' : ''; ?>>
                                <span class="checkmark"></span>
                                Habilitar Marca de Agua en PDFs
                            </label>
                        </div>

                        <div class="form-group checkbox-group">
                            <label>
                                <input type="checkbox" name="disable_right_click" 
                                       <?php echo ($securityConfig['disable_right_click'] ?? true) ? 'checked' : ''; ?>>
                                <span class="checkmark"></span>
                                Deshabilitar Clic Derecho
                            </label>
                        </div>

                        <button type="submit" name="update_security" class="btn btn-primary">
                            <i class="fas fa-shield-alt"></i> Guardar Configuraciones de Seguridad
                        </button>
                    </form>
                </div>

                <!-- Cambiar Contraseña -->
                <div class="settings-section">
                    <h2><i class="fas fa-key"></i> Cambiar Contraseña de Administrador</h2>
                    <form method="POST" class="settings-form" id="password-form">
                        <div class="form-group">
                            <label for="current_password">Contraseña Actual:</label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>

                        <div class="form-group">
                            <label for="new_password">Nueva Contraseña:</label>
                            <input type="password" id="new_password" name="new_password" required minlength="8">
                            <small>Mínimo 8 caracteres</small>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirmar Nueva Contraseña:</label>
                            <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
                        </div>

                        <button type="submit" name="change_password" class="btn btn-warning">
                            <i class="fas fa-key"></i> Cambiar Contraseña
                        </button>
                    </form>
                </div>

                <!-- Información del Sistema -->
                <div class="settings-section">
                    <h2><i class="fas fa-info-circle"></i> Información del Sistema</h2>
                    <div class="system-info">
                        <div class="info-item">
                            <strong>Versión PHP:</strong> <?php echo PHP_VERSION; ?>
                        </div>
                        <div class="info-item">
                            <strong>Servidor Web:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Desconocido'; ?>
                        </div>
                        <div class="info-item">
                            <strong>Espacio en Disco:</strong> 
                            <?php 
                            $bytes = disk_free_space('.');
                            $gb = round($bytes / 1024 / 1024 / 1024, 2);
                            echo $gb . ' GB disponibles';
                            ?>
                        </div>
                        <div class="info-item">
                            <strong>Última Actualización:</strong> <?php echo date('d/m/Y H:i:s', filemtime(__FILE__)); ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/admin-scripts.js"></script>
</body>
</html>
