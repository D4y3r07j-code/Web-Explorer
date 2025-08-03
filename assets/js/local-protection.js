// Script para detectar y prevenir la visualización local de páginas guardadas
;(() => {
  // URL correcta a la que redirigir
  const correctURL = "https://172.25.236.159/dashbord/"

  // Función para verificar si la página se está ejecutando localmente
  function isRunningLocally() {
    return (
      window.location.protocol === "file:" ||
      window.location.hostname === "localhost" ||
      window.location.hostname === "127.0.0.1" ||
      window.location.href.indexOf("file://") !== -1
    )
  }

  // Función para mostrar la pantalla de bloqueo
  function showLocalBlockScreen() {
    // Crear el contenedor principal
    const blockContainer = document.createElement("div")
    blockContainer.style.position = "fixed"
    blockContainer.style.top = "0"
    blockContainer.style.left = "0"
    blockContainer.style.width = "100%"
    blockContainer.style.height = "100%"
    blockContainer.style.backgroundColor = "rgba(0, 0, 0, 0.95)"
    blockContainer.style.zIndex = "99999"
    blockContainer.style.display = "flex"
    blockContainer.style.justifyContent = "center"
    blockContainer.style.alignItems = "center"
    blockContainer.style.fontFamily =
      "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif"

    // Crear el contenido del mensaje
    const messageBox = document.createElement("div")
    messageBox.style.backgroundColor = "#121212"
    messageBox.style.borderRadius = "12px"
    messageBox.style.padding = "40px"
    messageBox.style.maxWidth = "500px"
    messageBox.style.width = "90%"
    messageBox.style.textAlign = "center"
    messageBox.style.color = "white"
    messageBox.style.boxShadow = "0 4px 24px rgba(0, 0, 0, 0.5)"

    // Icono de advertencia
    const iconContainer = document.createElement("div")
    iconContainer.style.fontSize = "48px"
    iconContainer.style.color = "#ff3b30"
    iconContainer.style.marginBottom = "20px"
    iconContainer.innerHTML =
      '<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>'

    // Título del mensaje
    const title = document.createElement("h2")
    title.textContent = "Acceso no permitido"
    title.style.fontSize = "24px"
    title.style.marginBottom = "16px"
    title.style.color = "white"
    title.style.fontWeight = "600"

    // Mensaje de error
    const message = document.createElement("p")
    message.textContent =
      "Esta página no puede ser vista de manera local. Por favor, acceda a través del sitio web oficial."
    message.style.marginBottom = "30px"
    message.style.fontSize = "16px"
    message.style.lineHeight = "1.5"
    message.style.color = "rgba(255, 255, 255, 0.8)"

    // Botón para redirigir
    const redirectButton = document.createElement("button")
    redirectButton.textContent = "Ir a la página oficial"
    redirectButton.style.backgroundColor = "#ff3b30" // Cambiado de #2563eb a #ff3b30 (rojo)
    redirectButton.style.color = "white"
    redirectButton.style.border = "none"
    redirectButton.style.borderRadius = "6px"
    redirectButton.style.padding = "12px 30px" // Aumentado el padding horizontal de 24px a 30px
    redirectButton.style.fontSize = "16px"
    redirectButton.style.cursor = "pointer"
    redirectButton.style.fontWeight = "500"
    redirectButton.style.transition = "background-color 0.2s"
    redirectButton.style.minWidth = "200px" // Añadido ancho mínimo para evitar que se vea comprimido
    redirectButton.style.margin = "10px auto" // Centrar el botón y añadir margen

    // Efecto hover para el botón
    redirectButton.onmouseover = function () {
      this.style.backgroundColor = "#e62e29" // Color rojo más oscuro para el hover
    }
    redirectButton.onmouseout = function () {
      this.style.backgroundColor = "#ff3b30" // Volver al rojo original
    }

    // Acción del botón
    redirectButton.onclick = () => {
      window.location.href = correctURL
    }

    // Ensamblar todos los elementos
    messageBox.appendChild(iconContainer)
    messageBox.appendChild(title)
    messageBox.appendChild(message)
    messageBox.appendChild(redirectButton)
    blockContainer.appendChild(messageBox)

    // Añadir al body y ocultar el contenido original
    document.body.appendChild(blockContainer)

    // Ocultar el resto del contenido
    Array.from(document.body.children).forEach((child) => {
      if (child !== blockContainer) {
        child.style.display = "none"
      }
    })

    // Prevenir que se cierre con F12 o inspección
    document.addEventListener("keydown", (e) => {
      // Bloquear F12, Ctrl+Shift+I, etc.
      if (
        e.key === "F12" ||
        (e.ctrlKey && e.shiftKey && e.key === "I") ||
        (e.ctrlKey && e.shiftKey && e.key === "J") ||
        (e.ctrlKey && e.key === "u")
      ) {
        e.preventDefault()
        return false
      }
    })

    // Bloquear clic derecho
    document.addEventListener("contextmenu", (e) => {
      e.preventDefault()
      return false
    })
  }

  // Verificar si se está ejecutando localmente y mostrar la pantalla de bloqueo
  if (isRunningLocally()) {
    // Si el DOM ya está cargado
    if (document.readyState === "complete" || document.readyState === "interactive") {
      showLocalBlockScreen()
    } else {
      // Si el DOM aún no está cargado, esperar a que se cargue
      document.addEventListener("DOMContentLoaded", showLocalBlockScreen)
    }
  }
})()
