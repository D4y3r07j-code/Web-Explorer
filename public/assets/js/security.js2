document.addEventListener("DOMContentLoaded", () => {
  // Configuración de niveles de seguridad
  const securityLevels = [
    { actions: 3, timeout: 60000, name: "Nivel 1" }, // 1 minuto
    { actions: 6, timeout: 300000, name: "Nivel 2" }, // 5 minutos
    { actions: 9, timeout: 600000, name: "Nivel 3" }, // 10 minutos
    { actions: 12, timeout: 1800000, name: "Nivel 4" }, // 30 minutos
  ]

  // Crear el contenedor para los mensajes de seguridad
  const securityMessageContainer = document.createElement("div")
  securityMessageContainer.id = "security-message-container"
  document.body.appendChild(securityMessageContainer)

  // Variable para controlar el tiempo entre mensajes
  let messageActive = false

  // Contador de acciones inválidas
  let invalidActions = Number.parseInt(localStorage.getItem("invalidActions") || "0")

  // Nivel de bloqueo actual (0-based index)
  let currentBlockLevel = Number.parseInt(localStorage.getItem("currentBlockLevel") || "-1")

  // Lista de elementos críticos que no deben ser eliminados
  const criticalElements = ["security-block-container"]

  // Añadir atributos de seguridad a elementos críticos
  criticalElements.forEach((id) => {
    const element = document.getElementById(id)
    if (element) {
      element.setAttribute("data-security", "protected")
    }
  })

  // Variable para detectar si las herramientas de desarrollador están abiertas
  let devToolsOpen = false

  // Verificar si hay un bloqueo activo al cargar la página
  checkForActiveBlock()

  // Función para mostrar mensajes de seguridad
  function showSecurityMessage(message) {
    // Si ya hay un mensaje activo, eliminar el anterior
    if (messageActive) {
      const oldMessages = document.querySelectorAll(".security-message")
      oldMessages.forEach((msg) => {
        msg.classList.add("hide")
        setTimeout(() => {
          if (msg.parentNode === securityMessageContainer) {
            securityMessageContainer.removeChild(msg)
          }
        }, 300)
      })
    }

    messageActive = true

    const messageElement = document.createElement("div")
    messageElement.className = "security-message"

    // Determinar el próximo nivel de bloqueo
    let nextLevelIndex = 0
    for (let i = 0; i < securityLevels.length; i++) {
      if (invalidActions < securityLevels[i].actions) {
        nextLevelIndex = i
        break
      }
    }

    const nextLevel = securityLevels[nextLevelIndex]
    const attemptsUntilBlock = nextLevel.actions - invalidActions

    // Contenido del mensaje con información de seguridad
    messageElement.innerHTML = `
      <div class="message-content">
        <i class="fas fa-exclamation-triangle security-icon"></i>
        <span class="message-text">${message}</span>
      </div>
      <div class="security-info">
        <div class="security-info-icon"><i class="fas fa-shield-alt"></i></div>
        <div class="security-info-content">
          <div class="security-info-title">Seguridad</div>
          <div class="security-info-attempts">Intentos: ${invalidActions}</div>
          <div class="security-info-warning">Bloqueo en: ${attemptsUntilBlock} más</div>
        </div>
      </div>
    `

    // Añadir clase de gravedad según los intentos restantes
    if (attemptsUntilBlock <= 1) {
      messageElement.classList.add("critical")
    } else if (attemptsUntilBlock <= 2) {
      messageElement.classList.add("warning")
    }

    // Añadir al contenedor
    securityMessageContainer.appendChild(messageElement)

    // Añadir clase para animación de entrada
    setTimeout(() => {
      messageElement.classList.add("show")
    }, 10)

    // Eliminar después de 3 segundos
    setTimeout(() => {
      messageElement.classList.add("hide")
      setTimeout(() => {
        if (messageElement.parentNode === securityMessageContainer) {
          securityMessageContainer.removeChild(messageElement)
        }
        messageActive = false
      }, 500) // Tiempo para la animación de salida
    }, 3000)
  }

  // Función para registrar una acción inválida
  function registerInvalidAction(actionType) {
    invalidActions++
    localStorage.setItem("invalidActions", invalidActions.toString())

    // Calcular cuántas acciones faltan para el siguiente bloqueo
    let nextBlockLevel = 0
    let actionsUntilBlock = 0

    // Determinar el siguiente nivel de bloqueo basado en el contador actual
    for (let i = 0; i < securityLevels.length; i++) {
      if (invalidActions < securityLevels[i].actions) {
        nextBlockLevel = i
        actionsUntilBlock = securityLevels[i].actions - invalidActions
        break
      }
    }

    // Si ya superamos todos los niveles, usar el último
    if (actionsUntilBlock <= 0) {
      nextBlockLevel = securityLevels.length - 1
      actionsUntilBlock = 0
    }

    // Mostrar mensaje de advertencia con el formato adecuado según la acción
    let messageText = ""

    switch (actionType) {
      case "menú contextual":
        messageText = "El clic derecho está deshabilitado en esta página"
        break
      case "tecla F12":
        messageText = "La tecla F12 está deshabilitada en esta página"
        break
      case "herramientas de desarrollador":
        messageText = "Las herramientas de desarrollador están deshabilitadas"
        break
      case "consola de desarrollador":
        messageText = "La consola de desarrollador está deshabilitada"
        break
      case "ver código fuente":
        messageText = "Ver el código fuente está deshabilitado"
        break
      case "impresión":
        messageText = "La impresión está deshabilitada en esta página"
        break
      case "arrastrar elementos":
        messageText = "No se permite arrastrar elementos de esta página"
        break
      case "copiar contenido":
        messageText = "No se permite copiar contenido de esta página"
        break
      case "guardar página":
        messageText = "No se permite guardar esta página"
        break
      case "manipulación del bloqueo":
        messageText = "Se ha detectado un intento de manipular el bloqueo de seguridad"
        break
      default:
        messageText = `Acción no permitida: ${actionType}`
    }

    showSecurityMessage(messageText)

    // Registrar el evento en el servidor
    logSecurityEvent(actionType, currentBlockLevel, invalidActions)

    // Verificar si se debe aplicar un bloqueo
    checkSecurityLevel()
  }

  // Función para enviar el evento de seguridad al servidor
  function logSecurityEvent(actionType, level = -1, attempts = 0, duration = 0) {
    // Crear los datos para enviar
    const formData = new FormData()
    formData.append("action", actionType)
    formData.append("level", level)
    formData.append("attempts", attempts)
    formData.append("duration", duration)

    // Enviar la solicitud al servidor
    fetch("./src/security-logger.php", {
      method: "POST",
      body: formData,
    })

    .then(response => {
      if (!response.ok) {
        throw new Error(`HTTP error! Status: ${response.status}`)
      }
      return response.json()
    })
    .then(data => {
      if (!data.success) {
        console.warn("Error al registrar evento de seguridad:", data)
      }
    })
    .catch((error) => {
      console.error("Error al registrar evento de seguridad:", error)
      // Intento de depuración - guardar en localStorage si el servidor falla
      const errorLog = JSON.parse(localStorage.getItem("securityErrors") || "[]")
      errorLog.push({
        time: new Date().toISOString(),
        action: actionType,
        level,
        attempts,
        error: error.message
    })
    // Limitar a los últimos 20 errores para no llenar el almacenamiento
    if (errorLog.length > 20) {
      errorLog.shift()
    }
    localStorage.setItem("securityErrors", JSON.stringify(errorLog))
  })
}

  // Función para verificar el nivel de seguridad actual
  function checkSecurityLevel() {
    // Verificar si debemos bloquear y en qué nivel
    for (let i = 0; i < securityLevels.length; i++) {
      if (invalidActions === securityLevels[i].actions) {
        // Exactamente el número de acciones para este nivel
        currentBlockLevel = i
        localStorage.setItem("currentBlockLevel", currentBlockLevel.toString())
        applySecurityBlock(currentBlockLevel)
        return
      }
    }
  }

  // Función para aplicar un bloqueo de seguridad
  function applySecurityBlock(levelIndex) {
    const level = securityLevels[levelIndex]
    const blockEndTime = Date.now() + level.timeout

    // Guardar información del bloqueo en localStorage
    localStorage.setItem("securityBlockLevel", levelIndex.toString())
    localStorage.setItem("securityBlockEndTime", blockEndTime.toString())

    // Registrar el bloqueo en el servidor
    logSecurityEvent("bloqueo aplicado", levelIndex, invalidActions, Math.floor(level.timeout / 1000))

    // Aplicar el bloqueo
    showBlockScreen(levelIndex, blockEndTime)
  }

  // Función para mostrar la pantalla de bloqueo
  function showBlockScreen(levelIndex, endTime) {
    // Crear el contenedor de bloqueo
    const blockContainer = document.createElement("div")
    blockContainer.id = "security-block-container"

    const level = securityLevels[levelIndex]
    const remainingTime = endTime - Date.now()
    const minutes = Math.floor(remainingTime / 60000)
    const seconds = Math.floor((remainingTime % 60000) / 1000)

    // Formatear el tiempo como MM:SS
    const formattedTime = `${minutes.toString().padStart(2, "0")}:${seconds.toString().padStart(2, "0")}`

    // Determinar el próximo nivel
    const nextLevelIndex = levelIndex + 1
    const nextLevelInfo =
      nextLevelIndex < securityLevels.length
        ? `Próximo nivel: ${securityLevels[nextLevelIndex].name} (${securityLevels[nextLevelIndex].actions - invalidActions} más)`
        : "Nivel máximo alcanzado"

    // Contenido HTML para la pantalla de bloqueo - ESTILO FIGMA CON NIVEL
    blockContainer.innerHTML = `
      <div class="security-block-content">
        <i class="fas fa-exclamation security-block-icon"></i>
        <h2>Acceso Restringido</h2>
        <div class="security-level-badge">${level.name}</div>
        <p>Se ha detectado un intento de acceso no autorizado.</p>
        <div class="security-attempts-info">
          <div class="attempts-count">Intentos inválidos: ${invalidActions}</div>
          <div class="next-level-info">${nextLevelInfo}</div>
        </div>
        <div class="security-timer-container">
          <div class="security-timer-text">
            La página se desbloqueará en: <span id="block-timer">${formattedTime}</span>
          </div>
          <div class="progress-container">
            <div id="progress-bar" class="progress-bar"></div>
          </div>
        </div>
      </div>
    `

    // Añadir al body y eliminar el contenido original
    document.body.appendChild(blockContainer)

    // Ocultar el contenido original
    Array.from(document.body.children).forEach((child) => {
      if (child !== blockContainer && child !== securityMessageContainer) {
        child.style.display = "none"
      }
    })

    // Iniciar el temporizador y la barra de progreso
    startBlockTimer(endTime, level.timeout)

    // Configurar el observador para detectar si alguien intenta eliminar el bloqueo
    setupBlockScreenObserver(blockContainer)
  }

  // Función para configurar el observador del bloqueo
  function setupBlockScreenObserver(blockContainer) {
    // Crear un observador para detectar si se elimina el contenedor de bloqueo
    const observer = new MutationObserver((mutations) => {
      for (const mutation of mutations) {
        if (mutation.type === "childList" && mutation.removedNodes.length > 0) {
          for (const removedNode of mutation.removedNodes) {
            // Verificar si el nodo eliminado es el contenedor de bloqueo
            if (removedNode === blockContainer) {
              // Si alguien elimina el bloqueo, lo restauramos inmediatamente
              // y registramos una acción inválida
              document.body.appendChild(blockContainer)
              registerInvalidAction("manipulación del bloqueo")
              return
            }
          }
        }
      }
    })

    // Observar el documento para detectar si se elimina el contenedor de bloqueo
    observer.observe(document.body, {
      childList: true,
      subtree: false,
    })

    // Guardar una referencia al observador
    window._blockObserver = observer
  }

  // Función para iniciar el temporizador de bloqueo
  function startBlockTimer(endTime, totalDuration) {
    const timerElement = document.getElementById("block-timer")
    const progressBar = document.getElementById("progress-bar")

    const timerInterval = setInterval(() => {
      const now = Date.now()
      const remainingTime = endTime - now

      if (remainingTime <= 0) {
        // Fin del bloqueo
        clearInterval(timerInterval)
        removeBlockScreen()
        return
      }

      // Actualizar el temporizador con formato MM:SS
      const minutes = Math.floor(remainingTime / 60000)
      const seconds = Math.floor((remainingTime % 60000) / 1000)
      timerElement.textContent = `${minutes.toString().padStart(2, "0")}:${seconds.toString().padStart(2, "0")}`

      // Actualizar la barra de progreso - AHORA DISMINUYE con el tiempo
      const progressPercentage = (remainingTime / totalDuration) * 100
      progressBar.style.width = `${progressPercentage}%`
    }, 1000)
  }

  // Función para eliminar la pantalla de bloqueo
  function removeBlockScreen() {
    const blockContainer = document.getElementById("security-block-container")
    if (blockContainer) {
      blockContainer.remove()
    }

    // Detener el observador del bloqueo si existe
    if (window._blockObserver) {
      window._blockObserver.disconnect()
      window._blockObserver = null
    }

    // Mostrar el contenido original
    Array.from(document.body.children).forEach((child) => {
      child.style.display = ""
    })

    // Limpiar información de bloqueo
    localStorage.removeItem("securityBlockLevel")
    localStorage.removeItem("securityBlockEndTime")

    // Registrar el desbloqueo en el servidor
    logSecurityEvent("bloqueo finalizado", currentBlockLevel, invalidActions)
  }

  // Función para verificar si hay un bloqueo activo al cargar la página
  function checkForActiveBlock() {
    const blockLevelStr = localStorage.getItem("securityBlockLevel")
    const blockEndTimeStr = localStorage.getItem("securityBlockEndTime")

    if (blockLevelStr && blockEndTimeStr) {
      const blockLevel = Number.parseInt(blockLevelStr)
      const blockEndTime = Number.parseInt(blockEndTimeStr)
      const now = Date.now()

      if (now < blockEndTime) {
        // Todavía hay un bloqueo activo
        showBlockScreen(blockLevel, blockEndTime)

        // Registrar que se ha cargado la página con un bloqueo activo
        logSecurityEvent(
          "bloqueo activo al cargar",
          blockLevel,
          invalidActions,
          Math.floor((blockEndTime - now) / 1000),
        )
      } else {
        // El bloqueo ha expirado
        localStorage.removeItem("securityBlockLevel")
        localStorage.removeItem("securityBlockEndTime")

        // No reiniciamos el contador de acciones inválidas
        // para mantener el progreso hacia el siguiente nivel
      }
    }
  }

  // Función para detectar si las herramientas de desarrollador están abiertas
  function checkDevTools() {
    const threshold = 160
    const widthThreshold = window.outerWidth - window.innerWidth > threshold
    const heightThreshold = window.outerHeight - window.innerHeight > threshold

    // Actualizar el estado de las herramientas de desarrollador
    devToolsOpen = widthThreshold || heightThreshold
  }

  // Verificar periódicamente si las herramientas de desarrollador están abiertas
  setInterval(checkDevTools, 1000)

  // 1. Bloquear clic derecho
  document.addEventListener("contextmenu", (e) => {
    e.preventDefault()
    registerInvalidAction("menú contextual")
    return false
  })

  // 2. Bloquear teclas de acceso a herramientas de desarrollador
  document.addEventListener(
    "keydown",
    (e) => {
      // F12
      if (e.key === "F12") {
        e.preventDefault()
        registerInvalidAction("tecla F12")
        return false
      }

      // Ctrl+Shift+I o Cmd+Option+I (Mac)
      if ((e.ctrlKey && e.shiftKey && e.key === "I") || (e.metaKey && e.altKey && e.key === "i")) {
        e.preventDefault()
        registerInvalidAction("herramientas de desarrollador")
        return false
      }

      // Ctrl+Shift+J o Cmd+Option+J (Mac) - Consola
      if ((e.ctrlKey && e.shiftKey && e.key === "J") || (e.metaKey && e.altKey && e.key === "j")) {
        e.preventDefault()
        registerInvalidAction("consola de desarrollador")
        return false
      }

      // Ctrl+U o Cmd+Option+U (Mac) - Ver código fuente
      if ((e.ctrlKey && e.key === "u") || (e.metaKey && e.altKey && e.key === "u")) {
        e.preventDefault()
        registerInvalidAction("ver código fuente")
        return false
      }

      // Ctrl+P o Cmd+P (Mac) - Imprimir - Bloqueo agresivo
      if ((e.ctrlKey && e.key === "p") || (e.metaKey && e.key === "p")) {
        e.preventDefault()
        e.stopPropagation()
        registerInvalidAction("impresión")
        return false
      }
    },
    true,
  ) // Usar captura para interceptar antes que otros manejadores

  // Bloqueo adicional para impresión
  window.addEventListener(
    "beforeprint",
    (e) => {
      registerInvalidAction("impresión")
    },
    true,
  )

  // 3. Detectar apertura de DevTools mediante cambio de tamaño de ventana
  let devtoolsDetectionInterval
  let previousWidth = window.outerWidth
  let previousHeight = window.outerHeight
  let zoomLevel = window.devicePixelRatio || 1

  function startDevToolsDetection() {
    // Obtener el nivel de zoom actual
    const currentZoomLevel = window.devicePixelRatio || 1

    // Verificar si el zoom ha cambiado
    const zoomChanged = Math.abs(currentZoomLevel - zoomLevel) > 0.01

    // Actualizar el nivel de zoom si ha cambiado
    if (zoomChanged) {
      zoomLevel = currentZoomLevel
      // Actualizar las dimensiones previas para evitar falsos positivos
      previousWidth = window.outerWidth
      previousHeight = window.outerHeight
      return // No continuar con la detección si el zoom cambió
    }

    // Calcular cambios en las dimensiones
    const widthDifference = Math.abs(window.outerWidth - previousWidth)
    const heightDifference = Math.abs(window.outerHeight - previousHeight)

    // Actualizar dimensiones previas
    previousWidth = window.outerWidth
    previousHeight = window.outerHeight

    // Verificar si hay un cambio significativo que no sea por zoom
    // DevTools generalmente causa cambios grandes y repentinos
    if ((widthDifference > 100 || heightDifference > 100) && !zoomChanged) {
      // Verificar si la diferencia entre outer e inner es significativa
      const threshold = 160 * zoomLevel // Ajustar el umbral según el zoom
      const widthThreshold = window.outerWidth - window.innerWidth * zoomLevel > threshold
      const heightThreshold = window.outerHeight - window.innerHeight * zoomLevel > threshold

      if (widthThreshold || heightThreshold) {
        registerInvalidAction("herramientas de desarrollador")

        // Intentar ocultar el contenido mientras las DevTools están abiertas
        document.documentElement.style.display = "none"
        setTimeout(() => {
          document.documentElement.style.display = "block"
        }, 500)
      }
    }
  }

  // Iniciar detección
  devtoolsDetectionInterval = setInterval(startDevToolsDetection, 1000)

  // 4. Deshabilitar arrastrar imágenes
  document.addEventListener("dragstart", (e) => {
    e.preventDefault()
    registerInvalidAction("arrastrar elementos")
    return false
  })

  // 5. Detectar cuando el usuario intenta copiar contenido
  document.addEventListener("copy", (e) => {
    // Permitir copia en campos de entrada
    if (e.target.tagName === "INPUT" || e.target.tagName === "TEXTAREA") {
      return true
    }
    e.preventDefault()
    registerInvalidAction("copiar contenido")
    return false
  })

  // 6. Mensaje al intentar guardar la página
  document.addEventListener("keydown", (e) => {
    // Ctrl+S o Cmd+S (Mac)
    if ((e.ctrlKey && e.key === "s") || (e.metaKey && e.key === "s")) {
      e.preventDefault()
      registerInvalidAction("guardar página")
      return false
    }
  })

  // Agregar estilos para la impresión
  const printStyles = document.createElement("style")
  printStyles.type = "text/css"
  printStyles.media = "print"
  printStyles.innerHTML = `
    @media print {
      body * {
        display: none !important;
      }
      body:after {
        content: "IMPRESIÓN NO PERMITIDA";
        display: block !important;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        font-size: 50px;
        font-weight: bold;
        color: #ff0000;
        text-align: center;
        padding-top: 40vh;
        background-color: white;
        z-index: 9999;
      }
    }
  `
  document.head.appendChild(printStyles)
})
