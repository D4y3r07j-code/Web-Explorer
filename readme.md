# Proyecto Web Explorer

Un explorador web seguro especializado en la visualización de archivos PDF con un sistema de administración integrado.

## 🚀 Características

- **Exploración segura de PDFs**: Solo permite acceso a archivos PDF
- **Interfaz moderna**: Diseño responsive con modo claro/oscuro
- **Sistema de seguridad**: Múltiples niveles de protección
- **Panel de administración**: Dashboard completo para monitoreo
- **Logs de seguridad**: Registro detallado de eventos
- **Visor PDF integrado**: Basado en PDF.js con protecciones adicionales

## 📁 Estructura del Proyecto

\`\`\`
Proyecto-Web-Explorer/
├── public/          # Archivos públicos accesibles desde web
├── admin/           # Panel de administración (acceso restringido)
├── logs/            # Logs del sistema (privado)
├── src/             # Lógica del sistema (privado)
├── config/          # Configuraciones (privado)
└── campamentos/     # Enlace simbólico a archivos PDF
\`\`\`

## 🔧 Instalación

1. **Clonar el repositorio**:
   \`\`\`bash
   cd /var/www/html/
   git clone [URL_DEL_REPO] Proyecto-Web-Explorer
   \`\`\`

2. **Configurar permisos**:
   \`\`\`bash
   sudo chown -R www-data:www-data Proyecto-Web-Explorer/
   sudo chmod -R 755 Proyecto-Web-Explorer/
   sudo chmod -R 777 Proyecto-Web-Explorer/logs/
   \`\`\`

3. **Crear enlace simbólico a archivos**:
   \`\`\`bash
   ln -s /ruta/a/tus/archivos/pdf Proyecto-Web-Explorer/public/campamentos
   \`\`\`

4. **Configurar Apache**:
   - Asegurar que mod_rewrite esté habilitado
   - Configurar el .htaccess según tus necesidades

## 🔐 Acceso al Panel de Administración

- **URL**: `http://tu-servidor/Proyecto-Web-Explorer/admin/`
- **Usuario por defecto**: `admin`
- **Contraseña por defecto**: `password` (¡CAMBIAR INMEDIATAMENTE!)

## ⚙️ Configuración

### Configuración de Seguridad

Editar `config/security-config.php`:

\`\`\`php
<?php
return [
    'max_attempts' => 3,
    'block_duration' => 300, // 5 minutos
    'allowed_ips' => ['127.0.0.1', '::1'],
    'log_level' => 'INFO'
];
?>
\`\`\`

### Configuración de la Aplicación

Editar `config/app-config.php`:

\`\`\`php
<?php
return [
    'app_name' => 'Web Explorer',
    'files_directory' => '/ruta/a/archivos/pdf',
    'max_file_size' => '50MB',
    'allowed_extensions' => ['pdf']
];
?>
\`\`\`

## 🛡️ Características de Seguridad

- **Protección contra acceso directo**: Archivos críticos protegidos
- **Logs de seguridad**: Registro de todos los eventos
- **Autenticación de admin**: Sistema de login seguro
- **Protección PDF**: Prevención de descarga no autorizada
- **Headers de seguridad**: Configuración robusta de Apache

## 📊 Monitoreo

### Logs Disponibles

- **Seguridad**: `logs/security/security_log.txt`
- **Accesos**: `logs/access/access_log.txt`
- **Errores**: `logs/errors/error_log.txt`

### Dashboard de Admin

El panel de administración proporciona:

- Estadísticas en tiempo real
- Visualización de logs
- Configuración del sistema
- Monitoreo de seguridad

## 🔄 Mantenimiento

### Limpieza de Logs

\`\`\`bash
# Crear respaldo antes de limpiar
cp logs/security/security_log.txt logs/security/security_log.txt.bak.$(date +%Y%m%d)

# Limpiar log (mantener últimas 1000 líneas)
tail -n 1000 logs/security/security_log.txt > logs/security/security_log.txt.tmp
mv logs/security/security_log.txt.tmp logs/security/security_log.txt
\`\`\`

### Actualización de Permisos

\`\`\`bash
sudo chown -R www-data:www-data /var/www/html/Proyecto-Web-Explorer/
sudo chmod -R 755 /var/www/html/Proyecto-Web-Explorer/
sudo chmod -R 777 /var/www/html/Proyecto-Web-Explorer/logs/
\`\`\`

## 🚨 Solución de Problemas

### Error 403 en Admin

1. Verificar IP en `.htaccess`
2. Comprobar permisos de directorio
3. Revisar logs de Apache

### PDFs no se muestran

1. Verificar enlace simbólico
2. Comprobar permisos de archivos
3. Revisar configuración de PDF.js

### Logs no se generan

1. Verificar permisos de escritura en `logs/`
2. Comprobar configuración de PHP
3. Revisar `src/security-logger.php`

## 📝 Changelog

### v2.0.0 (Actual)
- Reestructuración completa del proyecto
- Panel de administración separado
- Sistema de logs mejorado
- Seguridad reforzada

### v1.0.0
- Versión inicial del explorador
- Funcionalidad básica de PDFs

## 🤝 Contribución

1. Fork del proyecto
2. Crear rama para feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit de cambios (`git commit -am 'Agregar nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Crear Pull Request

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver `LICENSE` para más detalles.

## 👥 Soporte

Para soporte técnico:
- Crear issue en GitHub
- Revisar logs del sistema
- Consultar documentación

---

**Nota**: Este sistema está diseñado para entornos controlados. Asegurar configuración adecuada de seguridad antes de usar en producción.
