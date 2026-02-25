document.addEventListener('DOMContentLoaded', () => {
  const hoverMenus = document.querySelectorAll('[data-hover-menu]');
  const searchInput = document.getElementById('customer-search');
  const showFilter = document.getElementById('show-filter');
  const modelFilter = document.getElementById('model-filter');
  const pageSizeSelect = document.getElementById('page-size');
  const resultSummary = document.getElementById('result-summary');
  const emptyState = document.getElementById('empty-state');
  const pagination = document.getElementById('pagination');
  const prevPageBtn = document.getElementById('prev-page');
  const nextPageBtn = document.getElementById('next-page');
  const filterToggleBtn = document.getElementById('toggle-filters');
  const filtersPanel = document.getElementById('filters-panel');
  const clearFiltersBtn = document.getElementById('clear-filters');
  const importTrigger = document.getElementById('import-trigger');
  const importFileInput = document.getElementById('import-file');
  const importForm = importFileInput ? importFileInput.closest('form') : null;
  const tableBody = document.getElementById('customer-table-body');
  const rows = tableBody ? Array.from(tableBody.querySelectorAll('tr')) : [];
  let currentPage = 1;

  hoverMenus.forEach(menu => {
    menu.addEventListener('mouseenter', () => {
      menu.classList.add('is-open');
    });

    menu.addEventListener('mouseleave', () => {
      menu.classList.remove('is-open');
    });
  });

  if (importTrigger && importFileInput) {
    importTrigger.addEventListener('click', () => {
      importFileInput.click();
    });

    importFileInput.addEventListener('change', () => {
      if (importFileInput.files && importFileInput.files.length > 0 && importForm) {
        importForm.submit();
      }
    });
  }

  if (filterToggleBtn && filtersPanel) {
    filterToggleBtn.addEventListener('click', () => {
      filtersPanel.hidden = !filtersPanel.hidden;
    });
  }

  if (clearFiltersBtn) {
    clearFiltersBtn.addEventListener('click', () => {
      if (modelFilter) {
        modelFilter.value = '';
      }
      if (showFilter) {
        showFilter.value = 'all';
      }
      if (searchInput) {
        searchInput.value = '';
      }
      currentPage = 1;

      if (rows.length > 0 && searchInput && pageSizeSelect && resultSummary && pagination) {
        applyFilters();
      } else {
        if (resultSummary) {
          resultSummary.textContent = '0-0 of 0';
        }
        if (pagination) {
          pagination.innerHTML = '';
        }
      }
    });
  }

  if (!searchInput || rows.length === 0 || !pageSizeSelect || !resultSummary || !pagination) {
    if (resultSummary) {
      resultSummary.textContent = '0-0 of 0';
    }
    if (pagination) {
      pagination.innerHTML = '';
    }
    if (prevPageBtn) {
      prevPageBtn.disabled = true;
    }
    if (nextPageBtn) {
      nextPageBtn.disabled = true;
    }
    return;
  }

  function filterRows() {
    const keyword = searchInput.value.trim().toLowerCase();
    const showValue = showFilter ? showFilter.value : 'all';
    const modelValue = modelFilter ? modelFilter.value.trim().toLowerCase() : '';

    return rows.filter(row => {
      const rowName = row.dataset.name || '';
      const rowPhone = row.dataset.phone || '';
      const rowAddress = row.dataset.address || '';
      const rowVehicleNo = row.dataset.vehicleNo || '';
      const rowModel = row.dataset.model || '';

      const searchable = [rowName, rowPhone, rowAddress, rowVehicleNo, rowModel].join(' ');
      const matchesKeyword = keyword === '' || searchable.includes(keyword);

      let matchesShow = true;
      if (showValue === 'with-vehicle') {
        matchesShow = rowVehicleNo !== '';
      }
      if (showValue === 'without-vehicle') {
        matchesShow = rowVehicleNo === '';
      }

      const matchesModel = modelValue === '' || rowModel === modelValue;
      return matchesKeyword && matchesShow && matchesModel;
    });
  }

  function renderPagination(totalPages) {
    pagination.innerHTML = '';

    for (let page = 1; page <= totalPages; page++) {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = `page-btn${page === currentPage ? ' active' : ''}`;
      button.textContent = String(page);
      button.addEventListener('click', () => {
        currentPage = page;
        applyFilters();
      });
      pagination.appendChild(button);
    }
  }

  function applyFilters() {
    const filteredRows = filterRows();
    const pageSize = Math.max(parseInt(pageSizeSelect.value, 10) || 10, 1);
    const totalRecords = filteredRows.length;
    const totalPages = Math.max(1, Math.ceil(totalRecords / pageSize));

    if (currentPage > totalPages) {
      currentPage = totalPages;
    }

    const startIndex = (currentPage - 1) * pageSize;
    const endIndex = startIndex + pageSize;
    const visibleRows = filteredRows.slice(startIndex, endIndex);

    rows.forEach(row => {
      row.style.display = visibleRows.includes(row) ? '' : 'none';
    });

    resultSummary.textContent = `${totalRecords === 0 ? 0 : startIndex + 1}-${Math.min(endIndex, totalRecords)} of ${totalRecords}`;
    if (emptyState) {
      emptyState.hidden = totalRecords !== 0;
    }

    renderPagination(totalPages);

    if (prevPageBtn) {
      prevPageBtn.disabled = currentPage <= 1;
    }
    if (nextPageBtn) {
      nextPageBtn.disabled = currentPage >= totalPages;
    }
  }

  searchInput.addEventListener('input', () => {
    currentPage = 1;
    applyFilters();
  });

  if (showFilter) {
    showFilter.addEventListener('change', () => {
      currentPage = 1;
      applyFilters();
    });
  }

  if (modelFilter) {
    modelFilter.addEventListener('change', () => {
      currentPage = 1;
      applyFilters();
    });
  }

  pageSizeSelect.addEventListener('change', () => {
    currentPage = 1;
    applyFilters();
  });

  if (prevPageBtn) {
    prevPageBtn.addEventListener('click', () => {
      if (currentPage > 1) {
        currentPage -= 1;
        applyFilters();
      }
    });
  }

  if (nextPageBtn) {
    nextPageBtn.addEventListener('click', () => {
      const filteredRows = filterRows();
      const pageSize = Math.max(parseInt(pageSizeSelect.value, 10) || 10, 1);
      const totalPages = Math.max(1, Math.ceil(filteredRows.length / pageSize));
      if (currentPage < totalPages) {
        currentPage += 1;
        applyFilters();
      }
    });
  }

  applyFilters();
});
