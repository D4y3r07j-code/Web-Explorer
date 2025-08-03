<?php
// Verificar autenticación (deberías implementar un sistema de autenticación real)
$authorized = false;

// Verificación simple con contraseña (NOTA: Esto es solo para demostración, implementa un sistema seguro)
if (isset($_POST['password']) && $_POST['password'] === 'admin123') {
    $authorized = true;
    // Establecer una cookie de sesión
    setcookie('admin_auth', md5('authorized_admin'), time() + 3600, '/');
} elseif (isset($_COOKIE['admin_auth']) && $_COOKIE['admin_auth'] === md5('authorized_admin')) {
    $authorized = true;
}

// Ruta al archivo de logs
$log_file = '../logs/security_log.txt';
$log_exists = file_exists($log_file);

// Función para leer y formatear los logs
function readLogs($file, $limit = 100) {
    if (!file_exists($file)) {
        return [];
    }
    
    $logs = [];
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    // Obtener las últimas líneas (más recientes primero)
    $lines = array_reverse($lines);
    $lines = array_slice($lines, 0, $limit);
    
    foreach ($lines as $line) {
        // Extraer información del log usando expresiones regulares
        if (preg_match('/\[(.*?)\] IP: (.*?) \| Action: (.*?) \| Attempts: (\d+) \| Level: (.*?) \| Duration: (.*?) \| Browser: (.*?) \| URI: (.*?) \| Referer: (.*)/', $line, $matches)) {
            $logs[] = [
                'date' => $matches[1],
                'ip' => $matches[2],
                'action' => $matches[3],
                'attempts' => $matches[4],
                'level' => $matches[5],
                'duration' => $matches[6],
                'browser' => $matches[7],
                'uri' => $matches[8],
                'referer' => $matches[9]
            ];
        }
    }
    
    return $logs;
}

// Función para limpiar los logs
function clearLogs($file) {
    if (file_exists($file)) {
        // Crear una copia de respaldo antes de limpiar
        $backup_file = $file . '.bak.' . date('Y-m-d-H-i-s');
        copy($file, $backup_file);
        
        // Limpiar el archivo
        file_put_contents($file, '');
        return true;
    }
    return false;
}

// Manejar la acción de limpiar logs
if ($authorized && isset($_POST['action']) && $_POST['action'] === 'clear_logs') {
    $cleared = clearLogs($log_file);
}

// Obtener los logs si el usuario está autorizado
$logs = $authorized && $log_exists ? readLogs($log_file) : [];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Logs de Seguridad</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        /* Estilos específicos para el panel de administración */
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .admin-title {
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .admin-actions {
            display: flex;
            gap: 10px;
        }
        
        .admin-button {
            background-color: var(--accent-color);
            color: white;
            border: none;
            border-radius: 4px;
            padding: 8px 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.2s;
        }
        
        .admin-button:hover {
            background-color: var(--hover-color);
        }
        
        .admin-button.danger {
            background-color: var(--danger-color);
        }
        
        .admin-button.danger:hover {
            background-color: #d32f2f;
        }
        
        .logs-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 0.9rem;
        }
        
        .logs-table th, .logs-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        .logs-table th {
            background-color: var(--card-bg);
            font-weight: 600;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .logs-table tr:hover {
            background-color: var(--hover-bg-color);
        }
        
        .log-level {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .log-level.warning {
            background-color: rgba(245, 158, 11, 0.2);
            color: var(--warning-color);
        }
        
        .log-level.level-0 {
            background-color: rgba(59, 130, 246, 0.2);
            color: var(--info-color);
        }
        
        .log-level.level-1 {
            background-color: rgba(245, 158, 11, 0.2);
            color: var(--warning-color);
        }
        
        .log-level.level-2 {
            background-color: rgba(249, 115, 22, 0.2);
            color: var(--orange-color);
        }
        
        .log-level.level-3 {
            background-color: rgba(239, 68, 68, 0.2);
            color: var(--danger-color);
        }
        
        .login-form {
            max-width: 400px;
            margin: 100px auto;
            padding: 20px;
            background-color: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .login-form h2 {
            margin-bottom: 20px;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            background-color: var(--bg-color);
            color: var(--text-color);
        }
        
        .form-actions {
            margin-top: 20px;
            text-align: center;
        }
        
        .form-actions button {
            background-color: var(--accent-color);
            color: white;
            border: none;
            border-radius: 4px;
            padding: 10px 20px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .form-actions button:hover {
            background-color: var(--hover-color);
        }
        
        .empty-logs {
            text-align: center;
            padding: 40px;
            color: var(--text-muted);
        }
        
        .empty-logs i {
            font-size: 3rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .table-container {
            max-height: 70vh;
            overflow-y: auto;
            border: 1px solid var(--border-color);
            border-radius: 4px;
        }
        
        /* Estilos para el filtro */
        .filter-container {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filter-input {
            flex: 1;
            min-width: 200px;
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            background-color: var(--bg-color);
            color: var(--text-color);
        }
        
        .filter-select {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            background-color: var(--bg-color);
            color: var(--text-color);
        }
        
        /* Estilos responsivos */
        @media (max-width: 768px) {
            .logs-table {
                font-size: 0.8rem;
            }
            
            .logs-table th, .logs-table td {
                padding: 8px 5px;
            }
            
            .admin-actions {
                flex-direction: column;
            }
            
            .table-container {
                max-height: 60vh;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>Panel de Logs de Seguridad</h1>
            <div class="header-icons">
                <button id="refresh-btn" title="Actualizar" onclick="location.reload()"><i class="fas fa-sync-alt"></i></button>
                <button id="theme-toggle" title="Cambiar tema"><i class="fas fa-moon"></i></button>
            </div>
        </div>
    </header>

    <main>
        <?php if (!$authorized): ?>
            <!-- Formulario de login -->
            <div class="login-form">
                <h2>Acceso Restringido</h2>
                <form method="post" action="">
                    <div class="form-group">
                        <label for="password">Contraseña:</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="form-actions">
                        <button type="submit">Acceder</button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="admin-container">
                <div class="admin-header">
                    <div class="admin-title">
                        <i class="fas fa-shield-alt"></i> Registros de Seguridad
                    </div>
                    <div class="admin-actions">
                        <a href="../../index.php" class="admin-button">
                            <i class="fas fa-home"></i> Volver al Inicio
                        </a>
                        <?php if ($log_exists && count($logs) > 0): ?>
                            <form method="post" action="" onsubmit="return confirm('¿Estás seguro de que deseas limpiar todos los logs? Esta acción no se puede deshacer.')">
                                <input type="hidden" name="action" value="clear_logs">
                                <button type="submit" class="admin-button danger">
                                    <i class="fas fa-trash-alt"></i> Limpiar Logs
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (isset($cleared) && $cleared): ?>
                    <div class="alert alert-success" style="background-color: rgba(34, 197, 94, 0.2); color: var(--success-color); padding: 10px; border-radius: 4px; margin-bottom: 20px;">
                        <i class="fas fa-check-circle"></i> Los logs han sido limpiados correctamente. Se ha creado una copia de respaldo.
                    </div>
                <?php endif; ?>
                
                <?php if ($log_exists && count($logs) > 0): ?>
                    <div class="filter-container">
                        <input type="text" id="filter-input" class="filter-input" placeholder="Filtrar logs...">
                        <select id="filter-field" class="filter-select">
                            <option value="all">Todos los campos</option>
                            <option value="date">Fecha</option>
                            <option value="ip">IP</option>
                            <option value="action">Acción</option>
                            <option value="level">Nivel</option>
                            <option value="browser">Navegador</option>
                        </select>
                    </div>
                    
                    <div class="table-container">
                        <table class="logs-table" id="logs-table">
                            <thead>
                                <tr>
                                    <th>Fecha y Hora</th>
                                    <th>IP</th>
                                    <th>Acción</th>
                                    <th>Intentos</th>
                                    <th>Nivel</th>
                                    <th>Duración</th>
                                    <th>Navegador</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($log['date']); ?></td>
                                        <td><?php echo htmlspecialchars($log['ip']); ?></td>
                                        <td><?php echo htmlspecialchars($log['action']); ?></td>
                                        <td><?php echo htmlspecialchars($log['attempts']); ?></td>
                                        <td>
                                            <?php 
                                            $level_class = 'warning';
                                            if (strpos($log['level'], 'Level') !== false) {
                                                $level_num = intval(str_replace('Level ', '', $log['level']));
                                                $level_class = 'level-' . $level_num;
                                            }
                                            ?>
                                            <span class="log-level <?php echo $level_class; ?>">
                                                <?php echo htmlspecialchars($log['level']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($log['duration']); ?></td>
                                        <td><?php echo htmlspecialchars($log['browser']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php elseif ($log_exists): ?>
                    <div class="empty-logs">
                        <i class="fas fa-clipboard-check"></i>
                        <p>No hay registros de seguridad disponibles.</p>
                    </div>
                <?php else: ?>
                    <div class="empty-logs">
                        <i class="fas fa-exclamation-circle"></i>
                        <p>El archivo de logs no existe. Se creará automáticamente cuando ocurra un evento de seguridad.</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>

    <script>
        // Función para filtrar la tabla de logs
        document.addEventListener('DOMContentLoaded', function() {
            const filterInput = document.getElementById('filter-input');
            const filterField = document.getElementById('filter-field');
            const table = document.getElementById('logs-table');
            
            if (filterInput && filterField && table) {
                filterInput.addEventListener('input', filterTable);
                filterField.addEventListener('change', filterTable);
                
                function filterTable() {
                    const filterValue = filterInput.value.toLowerCase();
                    const filterBy = filterField.value;
                    const rows = table.querySelectorAll('tbody tr');
                    
                    rows.forEach(row => {
                        let visible = false;
                        
                        if (filterBy === 'all') {
                            // Buscar en todas las celdas
                            const cells = row.querySelectorAll('td');
                            cells.forEach(cell => {
                                if (cell.textContent.toLowerCase().includes(filterValue)) {
                                    visible = true;
                                }
                            });
                        } else {
                            // Buscar en la columna específica
                            let columnIndex = 0;
                            switch (filterBy) {
                                case 'date': columnIndex = 0; break;
                                case 'ip': columnIndex = 1; break;
                                case 'action': columnIndex = 2; break;
                                case 'level': columnIndex = 4; break;
                                case 'browser': columnIndex = 6; break;
                            }
                            
                            const cell = row.querySelectorAll('td')[columnIndex];
                            if (cell && cell.textContent.toLowerCase().includes(filterValue)) {
                                visible = true;
                            }
                        }
                        
                        row.style.display = visible ? '' : 'none';
                    });
                }
            }
            
            // Inicializar el tema
            const themeToggle = document.getElementById('theme-toggle');
            if (themeToggle) {
                themeToggle.addEventListener('click', function() {
                    window.toggleTheme();
                });
            }
        });
    </script>
</body>
</html>
