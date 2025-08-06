/**
 * Admin Scripts - JavaScript para el Panel de Administración
 * Proyecto Web Explorer
 */

document.addEventListener("DOMContentLoaded", () => {
  // Inicializar todas las funciones
  initPasswordValidation()
  initFormValidation()
  initAutoSave()
  initAlerts()
  initTooltips()
  initConfirmations()

  console.log("Admin scripts loaded successfully")
})

/**
 * Validación de contraseñas en tiempo real
 */
function initPasswordValidation() {
  const passwordForm = document.getElementById("password-form")
  if (!passwordForm) return

  const newPassword = document.getElementById("new_password")
  const confirmPassword = document.getElementById("confirm_password")

  if (newPassword) {
    // Crear indicador de fortaleza
    const strengthIndicator = createPasswordStrengthIndicator()
    newPassword.parentNode.appendChild(strengthIndicator)

    newPassword.addEventListener("input", function () {
      const strength = calculatePasswordStrength(this.value)
      updatePasswordStrengthIndicator(strengthIndicator, strength)
      validatePasswordMatch()
    })
  }

  if (confirmPassword) {
    confirmPassword.addEventListener("input", validatePasswordMatch)
  }

  function validatePasswordMatch() {
    if (!newPassword || !confirmPassword) return

    const isMatch = newPassword.value === confirmPassword.value
    const isEmpty = confirmPassword.value === ""

    if (isEmpty) {
      confirmPassword.setCustomValidity("")
      confirmPassword.classList.remove("valid", "invalid")
    } else if (isMatch) {
      confirmPassword.setCustomValidity("")
      confirmPassword.classList.remove("invalid")
      confirmPassword.classList.add("valid")
    } else {
      confirmPassword.setCustomValidity("Las contraseñas no coinciden")
      confirmPassword.classList.remove("valid")
      confirmPassword.classList.add("invalid")
    }
  }
}

// Crear indicador de fortaleza de contraseña
function createPasswordStrengthIndicator() {
  const container = document.createElement("div")
  container.className = "password-strength"
  container.innerHTML = `
        <div class="strength-bar">
            <div class="strength-fill"></div>
        </div>
        <div class="strength-text">Fortaleza de la contraseña</div>
    `

  // Agregar estilos CSS dinámicamente
  if (!document.getElementById("password-strength-styles")) {
    const styles = document.createElement("style")
    styles.id = "password-strength-styles"
    styles.textContent = `
            .password-strength {
                margin-top: 0.5rem;
            }
            .strength-bar {
                height: 4px;
                background-color: #e2e8f0;
                border-radius: 2px;
                overflow: hidden;
            }
            .strength-fill {
                height: 100%;
                transition: all 0.3s ease;
                border-radius: 2px;
            }
            .strength-text {
                font-size: 0.75rem;
                margin-top: 0.25rem;
                color: #64748b;
            }
            .form-group input.valid {
                border-color: #059669;
                box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
            }
            .form-group input.invalid {
                border-color: #dc2626;
                box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
            }
        `
    document.head.appendChild(styles)
  }

  return container
}

// Calcular fortaleza de contraseña
function calculatePasswordStrength(password) {
  let score = 0
  const feedback = []

  if (password.length >= 8) score += 25
  else feedback.push("Al menos 8 caracteres")

  if (/[a-z]/.test(password)) score += 25
  else feedback.push("Letras minúsculas")

  if (/[A-Z]/.test(password)) score += 25
  else feedback.push("Letras mayúsculas")

  if (/[0-9]/.test(password)) score += 25
  else feedback.push("Números")

  if (/[^A-Za-z0-9]/.test(password)) score += 25
  else feedback.push("Símbolos especiales")

  return { score: Math.min(score, 100), feedback }
}

// Actualizar indicador de fortaleza
function updatePasswordStrengthIndicator(indicator, strength) {
  const fill = indicator.querySelector(".strength-fill")
  const text = indicator.querySelector(".strength-text")

  fill.style.width = strength.score + "%"

  if (strength.score < 50) {
    fill.style.backgroundColor = "#dc2626"
    text.textContent = "Débil - " + strength.feedback.join(", ")
    text.style.color = "#dc2626"
  } else if (strength.score < 75) {
    fill.style.backgroundColor = "#d97706"
    text.textContent = "Media - " + strength.feedback.join(", ")
    text.style.color = "#d97706"
  } else {
    fill.style.backgroundColor = "#059669"
    text.textContent = "Fuerte"
    text.style.color = "#059669"
  }
}

/**
 * Validación general de formularios
 */
function initFormValidation() {
  const forms = document.querySelectorAll(".settings-form")

  forms.forEach((form) => {
    form.addEventListener("submit", function (e) {
      if (!validateForm(this)) {
        e.preventDefault()
        showAlert("Por favor, corrige los errores en el formulario", "error")
      }
    })

    // Validación en tiempo real
    const inputs = form.querySelectorAll("input[required]")
    inputs.forEach((input) => {
      input.addEventListener("blur", function () {
        validateField(this)
      })
    })
  })
}

// Validar campo individual
function validateField(field) {
  const isValid = field.checkValidity()

  if (isValid) {
    field.classList.remove("invalid")
    field.classList.add("valid")
  } else {
    field.classList.remove("valid")
    field.classList.add("invalid")
  }

  return isValid
}

// Validar formulario completo
function validateForm(form) {
  const fields = form.querySelectorAll("input[required]")
  let isValid = true

  fields.forEach((field) => {
    if (!validateField(field)) {
      isValid = false
    }
  })

  return isValid
}

/**
 * Auto-guardado de configuraciones
 */
function initAutoSave() {
  const autoSaveInputs = document.querySelectorAll('input[type="checkbox"]')

  autoSaveInputs.forEach((input) => {
    input.addEventListener("change", function () {
      if (this.dataset.autoSave !== "false") {
        debounce(autoSaveSettings, 1000)()
      }
    })
  })
}

// Función de auto-guardado
function autoSaveSettings() {
  showAlert("Configuraciones guardadas automáticamente", "success", 2000)
}

// Debounce para evitar múltiples llamadas
function debounce(func, wait) {
  let timeout
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout)
      func(...args)
    }
    clearTimeout(timeout)
    timeout = setTimeout(later, wait)
  }
}

/**
 * Gestión de alertas
 */
function initAlerts() {
  // Auto-ocultar alertas después de 5 segundos
  const alerts = document.querySelectorAll(".alert")
  alerts.forEach((alert) => {
    setTimeout(() => {
      fadeOut(alert)
    }, 5000)
  })
}

// Mostrar alerta dinámica
function showAlert(message, type = "info", duration = 5000) {
  const alertContainer = getOrCreateAlertContainer()

  const alert = document.createElement("div")
  alert.className = `alert alert-${type}`
  alert.innerHTML = `
        <i class="fas fa-${getAlertIcon(type)}"></i>
        ${message}
        <button class="alert-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `

  // Agregar estilos para el botón de cerrar
  if (!document.getElementById("alert-close-styles")) {
    const styles = document.createElement("style")
    styles.id = "alert-close-styles"
    styles.textContent = `
            .alert-close {
                background: none;
                border: none;
                color: inherit;
                cursor: pointer;
                margin-left: auto;
                padding: 0.25rem;
                border-radius: 4px;
                opacity: 0.7;
                transition: opacity 0.2s;
            }
            .alert-close:hover {
                opacity: 1;
            }
        `
    document.head.appendChild(styles)
  }

  alertContainer.appendChild(alert)

  // Auto-ocultar
  if (duration > 0) {
    setTimeout(() => {
      fadeOut(alert)
    }, duration)
  }
}

// Obtener o crear contenedor de alertas
function getOrCreateAlertContainer() {
  let container = document.getElementById("alert-container")
  if (!container) {
    container = document.createElement("div")
    container.id = "alert-container"
    container.style.cssText = `
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 1000;
            max-width: 400px;
        `
    document.body.appendChild(container)
  }
  return container
}

// Obtener icono para tipo de alerta
function getAlertIcon(type) {
  const icons = {
    success: "check-circle",
    error: "exclamation-triangle",
    warning: "exclamation-circle",
    info: "info-circle",
  }
  return icons[type] || "info-circle"
}

// Efecto fade out
function fadeOut(element) {
  element.style.transition = "opacity 0.3s ease"
  element.style.opacity = "0"
  setTimeout(() => {
    if (element.parentNode) {
      element.parentNode.removeChild(element)
    }
  }, 300)
}

/**
 * Tooltips
 */
function initTooltips() {
  const tooltipElements = document.querySelectorAll("[data-tooltip]")

  tooltipElements.forEach((element) => {
    element.addEventListener("mouseenter", showTooltip)
    element.addEventListener("mouseleave", hideTooltip)
  })
}

// Mostrar tooltip
function showTooltip(e) {
  const text = e.target.dataset.tooltip
  if (!text) return

  const tooltip = document.createElement("div")
  tooltip.className = "tooltip"
  tooltip.textContent = text
  tooltip.style.cssText = `
        position: absolute;
        background: #1e293b;
        color: white;
        padding: 0.5rem;
        border-radius: 4px;
        font-size: 0.75rem;
        z-index: 1000;
        pointer-events: none;
        white-space: nowrap;
    `

  document.body.appendChild(tooltip)

  const rect = e.target.getBoundingClientRect()
  tooltip.style.left = rect.left + "px"
  tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + "px"

  e.target._tooltip = tooltip
}

// Ocultar tooltip
function hideTooltip(e) {
  if (e.target._tooltip) {
    document.body.removeChild(e.target._tooltip)
    delete e.target._tooltip
  }
}

/**
 * Confirmaciones para acciones peligrosas
 */
function initConfirmations() {
  const dangerousActions = document.querySelectorAll("[data-confirm]")

  dangerousActions.forEach((element) => {
    element.addEventListener("click", function (e) {
      const message = this.dataset.confirm || "¿Estás seguro?"
      if (!confirm(message)) {
        e.preventDefault()
        return false
      }
    })
  })
}

/**
 * Utilidades adicionales
 */
const AdminUtils = {
  // Formatear bytes
  formatBytes: (bytes, decimals = 2) => {
    if (bytes === 0) return "0 Bytes"
    const k = 1024
    const dm = decimals < 0 ? 0 : decimals
    const sizes = ["Bytes", "KB", "MB", "GB", "TB"]
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    return Number.parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + " " + sizes[i]
  },

  // Copiar al portapapeles
  copyToClipboard: (text) => {
    navigator.clipboard
      .writeText(text)
      .then(() => {
        showAlert("Copiado al portapapeles", "success", 2000)
      })
      .catch(() => {
        showAlert("Error al copiar", "error", 2000)
      })
  },

  // Exportar configuraciones
  exportSettings: () => {
    const settings = {}
    const forms = document.querySelectorAll(".settings-form")

    forms.forEach((form) => {
      const formData = new FormData(form)
      for (const [key, value] of formData.entries()) {
        settings[key] = value
      }
    })

    const blob = new Blob([JSON.stringify(settings, null, 2)], {
      type: "application/json",
    })

    const url = URL.createObjectURL(blob)
    const a = document.createElement("a")
    a.href = url
    a.download = "admin-settings.json"
    a.click()
    URL.revokeObjectURL(url)

    showAlert("Configuraciones exportadas", "success")
  },
}

// Hacer disponible globalmente
window.AdminUtils = AdminUtils
window.showAlert = showAlert
