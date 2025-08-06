document.addEventListener("DOMContentLoaded", () => {
  // Configuración de la modal de información
  const infoModal = document.getElementById("info-modal")
  const infoModalContent = infoModal.querySelector(".info-modal-content")
  const infoModalClose = infoModal.querySelector(".info-modal-close")
  const infoModalBody = infoModal.querySelector(".info-modal-body")
  const infoModalIcon = infoModal.querySelector(".info-modal-icon i")
  const infoModalTitle = infoModal.querySelector(".info-modal-title")

  // Función para mostrar la modal de información
  function showInfoModal(element) {
    const infoType = element.getAttribute("data-info-type")
    const infoName = element.getAttribute("data-info-name")
    const infoModified = element.getAttribute("data-info-modified")

    // Cambiar el icono y título según el tipo
    if (infoType === "folder") {
      infoModalIcon.className = "fas fa-folder"
      infoModalTitle.textContent = "Información de carpeta"

      const infoFiles = element.getAttribute("data-info-files")
      const infoSubdirs = element.getAttribute("data-info-subdirs")

      // Construir el contenido para carpetas
      infoModalBody.innerHTML = `
        <div class="info-item">
          <div class="info-label">Nombre:</div>
          <div class="info-value">${infoName}</div>
        </div>
        <div class="info-item">
          <div class="info-label">Modificado:</div>
          <div class="info-value">${infoModified}</div>
        </div>
        <div class="info-item">
          <div class="info-label">Archivos:</div>
          <div class="info-value">${infoFiles} archivo${infoFiles != 1 ? "s" : ""}</div>
        </div>
        <div class="info-item">
          <div class="info-label">Subcarpetas:</div>
          <div class="info-value">${infoSubdirs} subcarpeta${infoSubdirs != 1 ? "s" : ""}</div>
        </div>
      `
    } else if (infoType === "file") {
      const infoExtension = element.getAttribute("data-info-extension")
      const infoSize = element.getAttribute("data-info-size")

      // Solo PDF
      infoModalIcon.className = "fas fa-file-pdf text-danger"
      infoModalTitle.textContent = "Información de PDF"

      // Construir el contenido para archivos PDF
      infoModalBody.innerHTML = `
        <div class="info-item">
          <div class="info-label">Nombre:</div>
          <div class="info-value">${infoName}</div>
        </div>
        <div class="info-item">
          <div class="info-label">Tipo:</div>
          <div class="info-value">PDF</div>
        </div>
        <div class="info-item">
          <div class="info-label">Tamaño:</div>
          <div class="info-value">${infoSize}</div>
        </div>
        <div class="info-item">
          <div class="info-label">Modificado:</div>
          <div class="info-value">${infoModified}</div>
        </div>
      `
    }

    // Mostrar la modal
    infoModal.classList.add("show")

    // Prevenir que el clic se propague al elemento padre
    event.preventDefault()
    event.stopPropagation()
  }

  // Cerrar la modal al hacer clic en el botón de cierre
  infoModalClose.addEventListener("click", () => {
    infoModal.classList.remove("show")
  })

  // Cerrar la modal al hacer clic fuera del contenido
  infoModal.addEventListener("click", (e) => {
    if (e.target === infoModal) {
      infoModal.classList.remove("show")
    }
  })

  // Añadir eventos a los botones de información
  document.addEventListener("click", (e) => {
    if (e.target.closest(".info-button")) {
      const infoButton = e.target.closest(".info-button")
      showInfoModal(infoButton)
    }
  })

  // Búsqueda en tiempo real
  const searchInput = document.getElementById("search-input")
  if (searchInput) {
    searchInput.addEventListener("input", () => {
      filterItems()
    })
  }

  // Cambio de tema
  const themeToggle = document.getElementById("theme-toggle")
  if (themeToggle) {
    themeToggle.addEventListener("click", () => {
      window.toggleTheme()
    })
  }

  // Botón de actualizar
  const refreshBtn = document.getElementById("refresh-btn")
  if (refreshBtn) {
    refreshBtn.addEventListener("click", () => {
      const icon = refreshBtn.querySelector("i")
      icon.classList.add("fa-spin")

      setTimeout(() => {
        location.reload()
      }, 300)
    })
  }

  // Eliminar el cambio de vista ya que solo usamos modo tarjeta
  // El botón de vista se mantiene oculto en el CSS

  // Corregir el comportamiento del botón volver en el visor
  const volverBtn = document.querySelector('.nav-button[href="javascript:history.back()"]')
  if (volverBtn) {
    volverBtn.addEventListener("click", (e) => {
      e.preventDefault()
      history.back()
    })
  }

  // Variables para almacenar los filtros activos
  let activeSort = "name-asc" // Ordenación predeterminada: A-Z
  let activeTypeFilter = "all" // Filtro de tipo predeterminado: todos
  let activeDateFilter = null // Filtro de fecha predeterminado: ninguno

  // Filtro y ordenación - NUEVA IMPLEMENTACIÓN CON BOTONES SEPARADOS
  const filterBtn = document.getElementById("filter-btn")
  const filterDropdown = document.getElementById("filter-dropdown")
  const sortBtn = document.getElementById("sort-btn")
  const sortDropdown = document.getElementById("sort-dropdown")

  // Crear overlay para el fondo en móvil (compartido entre ambos dropdowns)
  const filterOverlay = document.createElement("div")
  filterOverlay.className = "filter-overlay"
  document.body.appendChild(filterOverlay)

  // Función para crear botón de cierre para modales
  function createCloseButton(dropdown) {
    const closeBtn = document.createElement("button")
    closeBtn.className = "filter-close-btn"
    closeBtn.innerHTML = '<i class="fas fa-times"></i>'
    closeBtn.setAttribute("aria-label", "Cerrar")

    if (dropdown.firstChild) {
      dropdown.insertBefore(closeBtn, dropdown.firstChild)
    } else {
      dropdown.appendChild(closeBtn)
    }

    return closeBtn
  }

  // Configurar el dropdown de filtro
  if (filterBtn && filterDropdown) {
    const closeBtn = createCloseButton(filterDropdown)

    const showFilter = () => {
      if (sortDropdown && sortDropdown.classList.contains("show")) {
        sortDropdown.classList.remove("show")
        sortBtn.classList.remove("active")
      }

      if (window.innerWidth > 768) {
        const btnRect = filterBtn.getBoundingClientRect()
        filterDropdown.style.top = `${btnRect.bottom + window.scrollY}px`
        filterDropdown.style.right = `${window.innerWidth - btnRect.right}px`

        const viewportHeight = window.innerHeight
        const dropdownHeight = filterDropdown.offsetHeight
        const dropdownBottom = btnRect.bottom + dropdownHeight

        if (dropdownBottom > viewportHeight) {
          filterDropdown.style.top = `${btnRect.top - dropdownHeight + window.scrollY}px`
        }
      }

      filterDropdown.classList.add("show")
      filterOverlay.classList.add("show")
      filterBtn.classList.add("active")

      updateActiveFilterOptions()
    }

    const hideFilter = () => {
      filterDropdown.classList.remove("show")
      filterOverlay.classList.remove("show")
      filterBtn.classList.remove("active")
    }

    filterBtn.addEventListener("click", (e) => {
      e.stopPropagation()
      if (filterDropdown.classList.contains("show")) {
        hideFilter()
      } else {
        showFilter()
      }
    })

    closeBtn.addEventListener("click", hideFilter)

    const filterOptions = filterDropdown.querySelectorAll(".filter-option")
    filterOptions.forEach((option) => {
      option.addEventListener("click", () => {
        const filterType = option.getAttribute("data-filter")
        const filterValue = option.getAttribute("data-value")

        if (filterType === "type") {
          activeTypeFilter = filterValue

          if (filterValue === "all") {
            filterBtn.classList.remove("active")
          } else {
            filterBtn.classList.add("active")
          }
        }

        filterItems()
        updateActiveFilterOptions()

        setTimeout(() => {
          hideFilter()
        }, 200)
      })
    })
  }

  // Configurar los botones de filtro por fecha
  const dateFilterInput = document.getElementById("date-filter")
  const applyDateFilterBtn = document.getElementById("apply-date-filter")
  const clearDateFilterBtn = document.getElementById("clear-date-filter")

  if (applyDateFilterBtn) {
    applyDateFilterBtn.addEventListener("click", () => {
      if (dateFilterInput && dateFilterInput.value) {
        activeDateFilter = dateFilterInput.value

        if (filterBtn) {
          filterBtn.classList.add("active")
        }

        filterItems()

        setTimeout(() => {
          if (filterDropdown) {
            filterDropdown.classList.remove("show")
            filterOverlay.classList.remove("show")
            if (filterBtn) filterBtn.classList.remove("active")
          }
        }, 200)
      }
    })
  }

  if (clearDateFilterBtn) {
    clearDateFilterBtn.addEventListener("click", () => {
      if (dateFilterInput) {
        dateFilterInput.value = ""
        activeDateFilter = null

        if (filterBtn && activeTypeFilter === "all") {
          filterBtn.classList.remove("active")
        }

        filterItems()

        setTimeout(() => {
          if (filterDropdown) {
            filterDropdown.classList.remove("show")
            filterOverlay.classList.remove("show")
            if (filterBtn) filterBtn.classList.remove("active")
          }
        }, 200)
      }
    })
  }

  // Configurar el dropdown de ordenación
  if (sortBtn && sortDropdown) {
    const closeBtn = createCloseButton(sortDropdown)

    const showSort = () => {
      if (filterDropdown && filterDropdown.classList.contains("show")) {
        filterDropdown.classList.remove("show")
        filterBtn.classList.remove("active")
      }

      if (window.innerWidth > 768) {
        const btnRect = sortBtn.getBoundingClientRect()
        sortDropdown.style.top = `${btnRect.bottom + window.scrollY}px`
        sortDropdown.style.right = `${window.innerWidth - btnRect.right}px`

        const viewportHeight = window.innerHeight
        const dropdownHeight = sortDropdown.offsetHeight
        const dropdownBottom = btnRect.bottom + dropdownHeight

        if (dropdownBottom > viewportHeight) {
          sortDropdown.style.top = `${btnRect.top - dropdownHeight + window.scrollY}px`
        }
      }

      sortDropdown.classList.add("show")
      filterOverlay.classList.add("show")
      sortBtn.classList.add("active")

      updateActiveFilterOptions()
    }

    const hideSort = () => {
      sortDropdown.classList.remove("show")
      filterOverlay.classList.remove("show")
      sortBtn.classList.remove("active")
    }

    sortBtn.addEventListener("click", (e) => {
      e.stopPropagation()
      if (sortDropdown.classList.contains("show")) {
        hideSort()
      } else {
        showSort()
      }
    })

    closeBtn.addEventListener("click", hideSort)

    const sortOptions = sortDropdown.querySelectorAll(".filter-option")
    sortOptions.forEach((option) => {
      option.addEventListener("click", () => {
        const filterType = option.getAttribute("data-filter")
        const filterValue = option.getAttribute("data-value")

        if (filterType === "sort") {
          activeSort = filterValue

          const sortIcon = sortBtn.querySelector("i")
          if (filterValue.includes("asc")) {
            sortIcon.className = "fas fa-sort-amount-down"
          } else {
            sortIcon.className = "fas fa-sort-amount-up"
          }

          sortBtn.classList.add("active")
        }

        filterItems()
        updateActiveFilterOptions()

        setTimeout(() => {
          hideSort()
        }, 200)
      })
    })
  }

  // Evento para cerrar con el overlay
  filterOverlay.addEventListener("click", () => {
    if (filterDropdown) filterDropdown.classList.remove("show")
    if (sortDropdown) sortDropdown.classList.remove("show")
    filterOverlay.classList.remove("show")
    if (filterBtn) filterBtn.classList.remove("active")
    if (sortBtn) sortBtn.classList.remove("active")
  })

  // Cerrar los menús al hacer clic fuera de ellos (para escritorio)
  document.addEventListener("click", (e) => {
    if (
      !e.target.closest(".filter-button") &&
      !e.target.closest(".filter-dropdown") &&
      !e.target.closest(".sort-button") &&
      !e.target.closest(".sort-dropdown")
    ) {
      if (filterDropdown) filterDropdown.classList.remove("show")
      if (sortDropdown) sortDropdown.classList.remove("show")
      filterOverlay.classList.remove("show")
      if (filterBtn) filterBtn.classList.remove("active")
      if (sortBtn) sortBtn.classList.remove("active")
    }
  })

  // Ajustar posición de los dropdowns al cambiar el tamaño de la ventana
  window.addEventListener("resize", () => {
    if (window.innerWidth > 768) {
      if (filterDropdown && filterDropdown.classList.contains("show") && filterBtn) {
        const btnRect = filterBtn.getBoundingClientRect()
        filterDropdown.style.top = `${btnRect.bottom + window.scrollY}px`
        filterDropdown.style.right = `${window.innerWidth - btnRect.right}px`
      }

      if (sortDropdown && sortDropdown.classList.contains("show") && sortBtn) {
        const btnRect = sortBtn.getBoundingClientRect()
        sortDropdown.style.top = `${btnRect.bottom + window.scrollY}px`
        sortDropdown.style.right = `${window.innerWidth - btnRect.right}px`
      }
    }
  })

  // Función para marcar las opciones de filtro activas
  function updateActiveFilterOptions() {
    const filterOptions = document.querySelectorAll(".filter-option")
    filterOptions.forEach((option) => {
      const filterType = option.getAttribute("data-filter")
      const filterValue = option.getAttribute("data-value")

      option.classList.remove("active")

      if (
        (filterType === "sort" && filterValue === activeSort) ||
        (filterType === "type" && filterValue === activeTypeFilter)
      ) {
        option.classList.add("active")
      }
    })
  }

  // Función para filtrar y ordenar elementos - SOLO PDFs Y CARPETAS
  function filterItems() {
    const searchTerm = searchInput ? searchInput.value.toLowerCase() : ""
    const unifiedList = document.getElementById("unified-list")

    if (unifiedList) {
      const items = Array.from(unifiedList.querySelectorAll(".folder-item, .file-item"))
      let visibleItems = 0

      items.forEach((item) => {
        const itemName = item.querySelector(".folder-name, .file-name").textContent.toLowerCase()
        const itemType = item.getAttribute("data-type") || ""
        const itemExtension = item.getAttribute("data-extension") || ""

        // Obtener la fecha del elemento
        let itemDate = ""
        if (item.classList.contains("file-item") && item.querySelector(".file-date")) {
          itemDate = item.querySelector(".file-date").textContent
        } else {
          itemDate = item.getAttribute("data-modified") || ""
        }

        // Convertir la fecha del elemento a formato YYYY-MM-DD
        let itemDateFormatted = ""
        if (itemDate) {
          const dateParts = itemDate.split(" ")[0].split("/")
          if (dateParts.length === 3) {
            itemDateFormatted = `${dateParts[2]}-${dateParts[1].padStart(2, "0")}-${dateParts[0].padStart(2, "0")}`
          }
        }

        // Verificar coincidencias
        const matchesSearch = itemName.includes(searchTerm)

        // Filtro de tipo simplificado - solo carpetas y PDFs
        let matchesTypeFilter = true
        if (activeTypeFilter !== "all") {
          if (activeTypeFilter === "folder") {
            matchesTypeFilter = itemType === "folder"
          } else if (activeTypeFilter === "pdf") {
            matchesTypeFilter = itemExtension === "pdf"
          }
        }

        // Filtro de fecha
        let matchesDateFilter = true
        if (activeDateFilter && itemDateFormatted) {
          matchesDateFilter = itemDateFormatted === activeDateFilter
        }

        // Mostrar u ocultar elemento
        if (matchesSearch && matchesTypeFilter && matchesDateFilter) {
          item.style.display = ""
          visibleItems++
        } else {
          item.style.display = "none"
        }
      })

      // Ordenar elementos visibles
      sortItems(items, activeSort)

      // Mostrar mensaje si no hay resultados
      const noResultsUnified = document.getElementById("no-results-unified")
      if (noResultsUnified) {
        if (visibleItems === 0 && (searchTerm !== "" || activeTypeFilter !== "all" || activeDateFilter !== null)) {
          noResultsUnified.classList.add("visible")
        } else {
          noResultsUnified.classList.remove("visible")
        }
      }
    }
  }

  // Función para ordenar elementos
  function sortItems(items, sortOrder) {
    const visibleItems = items.filter((item) => item.style.display !== "none")

    visibleItems.sort((a, b) => {
      const nameA = a.getAttribute("data-name").toLowerCase()
      const nameB = b.getAttribute("data-name").toLowerCase()

      // Obtener fechas para ordenación
      let dateA, dateB
      if (a.classList.contains("file-item") && a.querySelector(".file-date")) {
        dateA = a.querySelector(".file-date").textContent
      } else {
        const folderCount = a.querySelector(".folder-count")
        if (folderCount) {
          dateA = a.getAttribute("data-modified") || "01/01/2000 00:00"
        } else {
          dateA = "01/01/2000 00:00"
        }
      }

      if (b.classList.contains("file-item") && b.querySelector(".file-date")) {
        dateB = b.querySelector(".file-date").textContent
      } else {
        const folderCount = b.querySelector(".folder-count")
        if (folderCount) {
          dateB = b.getAttribute("data-modified") || "01/01/2000 00:00"
        } else {
          dateB = "01/01/2000 00:00"
        }
      }

      // Convertir fechas a objetos Date
      const dateParts1 = dateA.split(" ")[0].split("/")
      const timeParts1 = dateA.split(" ")[1] ? dateA.split(" ")[1].split(":") : ["00", "00"]
      const dateObj1 = new Date(
        Number.parseInt(dateParts1[2]),
        Number.parseInt(dateParts1[1]) - 1,
        Number.parseInt(dateParts1[0]),
        Number.parseInt(timeParts1[0]),
        Number.parseInt(timeParts1[1]),
      )

      const dateParts2 = dateB.split(" ")[0].split("/")
      const timeParts2 = dateB.split(" ")[1] ? dateB.split(" ")[1].split(":") : ["00", "00"]
      const dateObj2 = new Date(
        Number.parseInt(dateParts2[2]),
        Number.parseInt(dateParts2[1]) - 1,
        Number.parseInt(dateParts2[0]),
        Number.parseInt(timeParts2[0]),
        Number.parseInt(timeParts2[1]),
      )

      if (sortOrder === "name-asc") {
        return nameA.localeCompare(nameB)
      } else if (sortOrder === "name-desc") {
        return nameB.localeCompare(nameA)
      } else if (sortOrder === "date-desc") {
        return dateObj2 - dateObj1
      } else if (sortOrder === "date-asc") {
        return dateObj1 - dateObj2
      }

      return 0
    })

    // Reordenar elementos en el DOM
    const parent = items[0].parentNode
    visibleItems.forEach((item) => {
      parent.appendChild(item)
    })
  }

  // Inicializar filtros al cargar
  updateActiveFilterOptions()

  // Función para corregir el scroll al cargar la página
  function fixInitialScroll() {
    document.body.style.overflow = "hidden"
    setTimeout(() => {
      document.body.style.overflow = ""
    }, 10)
  }

  fixInitialScroll()
})
