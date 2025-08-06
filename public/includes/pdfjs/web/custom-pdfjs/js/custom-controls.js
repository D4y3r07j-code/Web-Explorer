// Script para controles personalizados de PDF.js - Versión limpia sin duplicar seguridad

// Esperar a que PDF.js se inicialice completamente
function waitForPDFJS() {
  if (window.PDFViewerApplication && window.PDFViewerApplication.initialized) {
    setupCustomControls()
  } else {
    setTimeout(waitForPDFJS, 100)
  }
}

// Configurar los controles personalizados
function setupCustomControls() {
  const pdfApp = window.PDFViewerApplication
  const pdfViewer = pdfApp.pdfViewer

  // Crear e insertar los controles personalizados en toolbarViewerLeft
  const toolbarViewerLeft = document.getElementById("toolbarViewerLeft")

  // Limpiar el contenido existente de toolbarViewerLeft
  while (toolbarViewerLeft.firstChild) {
    toolbarViewerLeft.removeChild(toolbarViewerLeft.firstChild)
  }

  // Crear el contenedor de controles personalizados
  const controlsContainer = document.createElement("div")
  controlsContainer.className = "pdf-controls"

  // Crear controles de página
  const pageControls = document.createElement("div")
  pageControls.className = "pdf-page-controls"

  const prevButton = document.createElement("button")
  prevButton.id = "prev-page"
  prevButton.className = "pdf-control-button"
  prevButton.setAttribute("aria-label", "Página anterior")
  prevButton.setAttribute("title", "Página anterior")
  prevButton.innerHTML = `
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
      <polyline points="15 18 9 12 15 6"></polyline>
    </svg>
  `

  const pageInfo = document.createElement("div")
  pageInfo.id = "page-info"
  pageInfo.className = "pdf-page-info"
  pageInfo.textContent = "1 de 1"

  const nextButton = document.createElement("button")
  nextButton.id = "next-page"
  nextButton.className = "pdf-control-button"
  nextButton.setAttribute("aria-label", "Página siguiente")
  nextButton.setAttribute("title", "Página siguiente")
  nextButton.innerHTML = `
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
      <polyline points="9 18 15 12 9 6"></polyline>
    </svg>
  `

  pageControls.appendChild(prevButton)
  pageControls.appendChild(pageInfo)
  pageControls.appendChild(nextButton)

  // Crear controles de zoom
  const zoomControls = document.createElement("div")
  zoomControls.className = "pdf-zoom-controls"

  const zoomOutButton = document.createElement("button")
  zoomOutButton.id = "zoom-out"
  zoomOutButton.className = "pdf-control-button hidden-mobile"
  zoomOutButton.setAttribute("aria-label", "Reducir zoom")
  zoomOutButton.setAttribute("title", "Reducir zoom")
  zoomOutButton.innerHTML = `
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
      <circle cx="11" cy="11" r="8"></circle>
      <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
      <line x1="8" y1="11" x2="14" y2="11"></line>
    </svg>
  `

  const zoomLevel = document.createElement("div")
  zoomLevel.id = "zoom-level"
  zoomLevel.className = "pdf-zoom-level"
  zoomLevel.textContent = "100%"

  const zoomInButton = document.createElement("button")
  zoomInButton.id = "zoom-in"
  zoomInButton.className = "pdf-control-button hidden-mobile"
  zoomInButton.setAttribute("aria-label", "Aumentar zoom")
  zoomInButton.setAttribute("title", "Aumentar zoom")
  zoomInButton.innerHTML = `
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
      <circle cx="11" cy="11" r="8"></circle>
      <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
      <line x1="11" y1="8" x2="11" y2="14"></line>
      <line x1="8" y1="11" x2="14" y2="11"></line>
    </svg>
  `

  // Botón de restablecer zoom para PC
  const zoomResetButton = document.createElement("button")
  zoomResetButton.id = "zoom-reset"
  zoomResetButton.className = "pdf-control-button"
  zoomResetButton.setAttribute("aria-label", "Restablecer zoom")
  zoomResetButton.setAttribute("title", "Restablecer zoom")
  zoomResetButton.innerHTML = `
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" class="desktop-icon">
      <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path>
      <path d="M3 3v5h5"></path>
    </svg>
  `

  // Botón de restablecer zoom específico para móvil
  const zoomResetMobileButton = document.createElement("button")
  zoomResetMobileButton.id = "zoom-reset-mobile"
  zoomResetMobileButton.className = "pdf-control-button"
  zoomResetMobileButton.setAttribute("aria-label", "Restablecer zoom")
  zoomResetMobileButton.setAttribute("title", "Restablecer zoom")
  zoomResetMobileButton.innerHTML = `
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
      <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path>
      <path d="M3 3v5h5"></path>
    </svg>
  `

  zoomControls.appendChild(zoomOutButton)
  zoomControls.appendChild(zoomLevel)
  zoomControls.appendChild(zoomInButton)
  zoomControls.appendChild(zoomResetButton)
  zoomControls.appendChild(zoomResetMobileButton)

  // Añadir los controles al contenedor
  controlsContainer.appendChild(pageControls)
  controlsContainer.appendChild(zoomControls)

  // Añadir el contenedor a la barra de herramientas
  toolbarViewerLeft.appendChild(controlsContainer)

  // Actualizar información de página
  function updatePageInfo() {
    const currentPage = pdfViewer.currentPageNumber || 1
    const totalPages = pdfViewer.pagesCount || 1
    document.getElementById("page-info").textContent = `${currentPage} de ${totalPages}`

    // Habilitar/deshabilitar botones de navegación
    document.getElementById("prev-page").disabled = currentPage <= 1
    document.getElementById("next-page").disabled = currentPage >= totalPages
  }

  // Actualizar nivel de zoom
  function updateZoomLevel() {
    const currentScale = pdfViewer.currentScale || 1
    const zoomPercent = Math.round(currentScale * 100)
    document.getElementById("zoom-level").textContent = `${zoomPercent}%`
  }

  // Configurar botones de navegación
  document.getElementById("prev-page").addEventListener("click", () => {
    if (pdfViewer.currentPageNumber > 1) {
      pdfApp.page = pdfViewer.currentPageNumber - 1
      updatePageInfo()
    }
  })

  document.getElementById("next-page").addEventListener("click", () => {
    if (pdfViewer.currentPageNumber < pdfViewer.pagesCount) {
      pdfApp.page = pdfViewer.currentPageNumber + 1
      updatePageInfo()
    }
  })

  // Configurar botones de zoom
  document.getElementById("zoom-in").addEventListener("click", () => {
    pdfApp.zoomIn()
    updateZoomLevel()
  })

  document.getElementById("zoom-out").addEventListener("click", () => {
    pdfApp.zoomOut()
    updateZoomLevel()
  })

  document.getElementById("zoom-reset").addEventListener("click", () => {
    pdfApp.zoomReset()
    updateZoomLevel()
  })

  // Configurar el botón de restablecer zoom para móvil
  document.getElementById("zoom-reset-mobile").addEventListener("click", () => {
    pdfApp.zoomReset()
    updateZoomLevel()
  })

  // Escuchar eventos de cambio de página
  pdfApp.eventBus.on("pagechanging", (evt) => {
    updatePageInfo()
  })

  // Escuchar eventos de cambio de zoom
  pdfApp.eventBus.on("scalechanging", (evt) => {
    updateZoomLevel()
  })

  // Inicializar información
  pdfApp.initializedPromise.then(() => {
    updatePageInfo()
    updateZoomLevel()
  })
}

// Iniciar cuando el documento esté listo
document.addEventListener("DOMContentLoaded", () => {
  waitForPDFJS()
})
