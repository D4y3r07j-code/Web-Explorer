// Script para añadir marca de agua al visor de PDF

// Función para crear y añadir la marca de agua
function addWatermark() {
  // Crear el contenedor de la marca de agua
  const watermarkContainer = document.createElement("div")
  watermarkContainer.className = "pdf-watermark"

  // Detectar si es un dispositivo móvil
  const isMobile = window.innerWidth <= 768

  if (isMobile) {
    // Para móvil: una sola marca de agua perfectamente centrada
    const watermarkText = document.createElement("div")
    watermarkText.className = "pdf-watermark-text mobile"
    watermarkText.textContent = "CONTENIDO PROTEGIDO"

    // Centrar perfectamente en la pantalla
    watermarkText.style.position = "fixed"
    watermarkText.style.top = "50%"
    watermarkText.style.left = "50%"
    watermarkText.style.transform = "translate(-50%, -50%) rotate(-45deg)"
    watermarkText.style.margin = "0"
    watermarkText.style.padding = "0"
    watermarkText.style.width = "100%"
    watermarkText.style.maxWidth = "none"
    watermarkText.style.textAlign = "center"

    // Añadir al contenedor
    watermarkContainer.appendChild(watermarkText)
  } else {
    // Para escritorio: tres marcas de agua distribuidas
    const positions = [
      { top: "25%", left: "50%" },
      { top: "50%", left: "50%" },
      { top: "75%", left: "50%" },
    ]

    // Añadir cada instancia de la marca de agua
    positions.forEach((position) => {
      const watermarkText = document.createElement("div")
      watermarkText.className = "pdf-watermark-text"
      watermarkText.textContent = "CONTENIDO PROTEGIDO"

      // Aplicar posiciones personalizadas
      watermarkText.style.position = "absolute"
      watermarkText.style.top = position.top
      watermarkText.style.left = position.left
      watermarkText.style.transform = "translate(-50%, -50%) rotate(-45deg)"
      watermarkText.style.margin = "0"
      watermarkText.style.padding = "0"
      watermarkText.style.width = "100%"

      // Añadir el texto al contenedor
      watermarkContainer.appendChild(watermarkText)
    })
  }

  // Obtener el contenedor del visor
  const viewerContainer = document.getElementById("viewerContainer")

  // Si el contenedor existe, añadir la marca de agua
  if (viewerContainer) {
    viewerContainer.appendChild(watermarkContainer)
  }
}

// Ejecutar cuando el documento esté listo
document.addEventListener("DOMContentLoaded", () => {
  // Esperar a que PDF.js se inicialice completamente
  function waitForPDFJS() {
    if (window.PDFViewerApplication && window.PDFViewerApplication.initialized) {
      addWatermark()
    } else {
      setTimeout(waitForPDFJS, 100)
    }
  }

  waitForPDFJS()
})

// Asegurarse de que la marca de agua permanezca incluso después de cambiar de página
window.addEventListener("pagerendered", () => {
  // Verificar si la marca de agua ya existe
  if (!document.querySelector(".pdf-watermark")) {
    addWatermark()
  }
})

// Volver a aplicar las marcas de agua cuando cambia el tamaño de la ventana
window.addEventListener("resize", () => {
  // Eliminar marcas de agua existentes
  const existingWatermark = document.querySelector(".pdf-watermark")
  if (existingWatermark) {
    existingWatermark.remove()
  }

  // Añadir nuevas marcas de agua adaptadas al tamaño actual
  addWatermark()
})
