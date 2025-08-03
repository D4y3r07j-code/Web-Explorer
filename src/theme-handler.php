<?php
// Este archivo se incluirá en todas las páginas PHP para manejar el tema de manera consistente
?>
<!-- Script para aplicar el tema antes de renderizar la página -->
<script>
  // Aplicar el tema guardado inmediatamente para evitar parpadeos
  (function() {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'light') {
      document.documentElement.classList.add('light-theme');
    } else {
      document.documentElement.classList.remove('light-theme');
    }
  })();
  
  // Función global para cambiar el tema que puede ser llamada desde cualquier página
  window.toggleTheme = function() {
    const isDarkMode = !document.documentElement.classList.contains('light-theme');
    if (isDarkMode) {
      // Cambiar a modo claro
      document.documentElement.classList.add('light-theme');
      localStorage.setItem('theme', 'light');
    } else {
      // Cambiar a modo oscuro
      document.documentElement.classList.remove('light-theme');
      localStorage.setItem('theme', 'dark');
    }
    
    // Actualizar el icono si existe
    const themeToggle = document.getElementById('theme-toggle');
    if (themeToggle) {
      const icon = themeToggle.querySelector('i');
      if (icon) {
        if (isDarkMode) {
          icon.classList.remove('fa-moon');
          icon.classList.add('fa-sun');
        } else {
          icon.classList.remove('fa-sun');
          icon.classList.add('fa-moon');
        }
      }
    }
    
    return isDarkMode; // Devuelve si ahora está en modo oscuro
  };
  
  // Inicializar el icono correcto cuando la página carga
  document.addEventListener('DOMContentLoaded', function() {
    const themeToggle = document.getElementById('theme-toggle');
    if (themeToggle) {
      const icon = themeToggle.querySelector('i');
      const isLightTheme = document.documentElement.classList.contains('light-theme');
      if (icon) {
        if (isLightTheme) {
          icon.classList.remove('fa-moon');
          icon.classList.add('fa-sun');
        } else {
          icon.classList.remove('fa-sun');
          icon.classList.add('fa-moon');
        }
      }
    }
  });
</script>

