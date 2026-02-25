document.addEventListener('DOMContentLoaded', () => {
  const hoverMenus = document.querySelectorAll('[data-hover-menu]');
  const searchInput = document.getElementById('customerSearch');
  const addressFilter = document.getElementById('addressFilter');
  const clearFiltersBtn = document.getElementById('clearFilters');
  const tableBody = document.getElementById('customerTableBody');
  const visibleCount = document.getElementById('visibleCount');
  const totalCount = document.getElementById('totalCount');
  const prevPageBtn = document.getElementById('prevPage');
  const nextPageBtn = document.getElementById('nextPage');
  const pageInfo = document.getElementById('pageInfo');

  const ITEMS_PER_PAGE = 12;
  let currentPage = 1;
  let filteredRows = [];

  hoverMenus.forEach(menu => {
    menu.addEventListener('mouseenter', () => menu.classList.add('is-open'));
    menu.addEventListener('mouseleave', () => menu.classList.remove('is-open'));
  });

  function getFilteredRows() {
    if (!tableBody) return [];

    const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
    const selectedAddress = addressFilter ? addressFilter.value.toLowerCase() : '';
    const allRows = Array.from(tableBody.querySelectorAll('tr'));

    return allRows.filter(row => {
      const name = row.getAttribute('data-name') || '';
      const phone = row.getAttribute('data-phone') || '';
      const vehicle = row.getAttribute('data-vehicle') || '';
      const model = row.getAttribute('data-model') || '';
      const address = row.getAttribute('data-address') || '';

      const matchesSearch = !searchTerm || 
        name.includes(searchTerm) || 
        phone.includes(searchTerm) || 
        vehicle.includes(searchTerm) || 
        model.includes(searchTerm) ||
        address.includes(searchTerm);

      const matchesAddress = !selectedAddress || address === selectedAddress;

      return matchesSearch && matchesAddress;
    });
  }

  function updateTable() {
    if (!tableBody) return;

    filteredRows = getFilteredRows();
    const totalPages = Math.ceil(filteredRows.length / ITEMS_PER_PAGE);
    
    // Reset to page 1 if current page exceeds total pages
    if (currentPage > totalPages && totalPages > 0) {
      currentPage = totalPages;
    }
    if (currentPage < 1) {
      currentPage = 1;
    }

    const startIndex = (currentPage - 1) * ITEMS_PER_PAGE;
    const endIndex = startIndex + ITEMS_PER_PAGE;

    // Hide all rows first
    const allRows = tableBody.querySelectorAll('tr');
    allRows.forEach(row => row.style.display = 'none');

    // Show only current page rows
    filteredRows.slice(startIndex, endIndex).forEach(row => {
      row.style.display = '';
    });

    // Update counts
    if (visibleCount) {
      const showing = Math.min(filteredRows.length, endIndex) - startIndex;
      visibleCount.textContent = showing;
    }
    if (totalCount) {
      totalCount.textContent = filteredRows.length;
    }

    // Update pagination controls
    if (pageInfo) {
      pageInfo.textContent = `Page ${currentPage} of ${totalPages || 1}`;
    }
    if (prevPageBtn) {
      prevPageBtn.disabled = currentPage <= 1;
    }
    if (nextPageBtn) {
      nextPageBtn.disabled = currentPage >= totalPages || totalPages === 0;
    }
  }

  function filterTable() {
    currentPage = 1; // Reset to first page when filtering
    updateTable();
  }

  if (searchInput) {
    searchInput.addEventListener('input', filterTable);
  }

  if (addressFilter) {
    addressFilter.addEventListener('change', filterTable);
  }

  if (clearFiltersBtn) {
    clearFiltersBtn.addEventListener('click', () => {
      if (searchInput) searchInput.value = '';
      if (addressFilter) addressFilter.value = '';
      filterTable();
    });
  }

  if (prevPageBtn) {
    prevPageBtn.addEventListener('click', () => {
      if (currentPage > 1) {
        currentPage--;
        updateTable();
      }
    });
  }

  if (nextPageBtn) {
    nextPageBtn.addEventListener('click', () => {
      const totalPages = Math.ceil(filteredRows.length / ITEMS_PER_PAGE);
      if (currentPage < totalPages) {
        currentPage++;
        updateTable();
      }
    });
  }

  // Initial load
  updateTable();
});
