document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('jobCardForm');
  const successAlert = document.getElementById('successAlert');
  const errorAlert = document.getElementById('errorAlert');
  const hoverMenus = document.querySelectorAll('[data-hover-menu]');
  const baySelect = document.getElementById('bay_code');
  const bayPreview = document.getElementById('bayPreview');

  hoverMenus.forEach(menu => {
    menu.addEventListener('mouseenter', () => menu.classList.add('is-open'));
    menu.addEventListener('mouseleave', () => menu.classList.remove('is-open'));
  });

  if (!form) {
    return;
  }

  function updateBayPreview() {
    if (!baySelect || !bayPreview) {
      return;
    }

    const selectedText = baySelect.options[baySelect.selectedIndex]?.text || '';
    const selectedValue = String(baySelect.value || '').trim();

    bayPreview.classList.remove('bay-tone-1', 'bay-tone-2', 'bay-tone-3', 'bay-tone-4');

    if (selectedValue === '') {
      bayPreview.textContent = 'No bay selected';
      return;
    }

    const toneMap = {
      bay1: 'bay-tone-1',
      bay2: 'bay-tone-2',
      bay3: 'bay-tone-3',
      bay4: 'bay-tone-4',
      bay5: 'bay-tone-1',
      bay6: 'bay-tone-2',
      bay7: 'bay-tone-3',
      bayW: 'bay-tone-4',
    };

    bayPreview.classList.add(toneMap[selectedValue] || 'bay-tone-1');
    bayPreview.textContent = `Selected: ${selectedText}`;
  }

  if (baySelect) {
    baySelect.addEventListener('change', updateBayPreview);
    updateBayPreview();
  }

  const requiredFields = [
    'vehicle_number',
    'vehicle_model',
    'service_date',
    'customer_name',
    'phone_number',
    'kms',
    'bay_code',
    'mechanic_name',
    'customer_address',
    'demanded_jobs',
  ];

  function showMessage(type, message) {
    if (!successAlert || !errorAlert) return;

    successAlert.hidden = true;
    errorAlert.hidden = true;

    if (type === 'success') {
      successAlert.textContent = message;
      successAlert.hidden = false;
    } else {
      errorAlert.textContent = message;
      errorAlert.hidden = false;
    }
  }

  function validateForm() {
    for (const fieldName of requiredFields) {
      const field = form.elements[fieldName];
      if (!field || String(field.value || '').trim() === '') {
        return `Please fill required field: ${fieldName.replace('_', ' ')}`;
      }
    }

    const phone = String(form.elements.phone_number.value || '').trim();
    if (!/^\d{10}$/.test(phone)) {
      return 'Phone number must be 10 digits.';
    }

    const fuel = form.querySelector('input[name="fuel_level"]:checked');
    if (!fuel) {
      return 'Please select fuel level.';
    }

    return '';
  }

  form.addEventListener('submit', async event => {
    event.preventDefault();
    showMessage('error', '');

    const validationError = validateForm();
    if (validationError) {
      showMessage('error', validationError);
      return;
    }

    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.textContent = 'Saving...';
    }

    try {
      const formData = new FormData(form);
      const response = await fetch('save_jobcard.php', {
        method: 'POST',
        body: formData,
      });

      const data = await response.json();
      if (!response.ok || !data.success) {
        throw new Error(data.message || 'Failed to save job card.');
      }

      showMessage('success', data.message || 'Job card created successfully.');
      if (data.viewUrl) {
        window.location.href = data.viewUrl;
        return;
      }
      form.reset();
      form.elements.service_date.valueAsDate = new Date();
    } catch (error) {
      showMessage('error', error.message || 'Unable to submit form.');
    } finally {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Create Job Card';
      }
    }
  });

  const today = new Date();
  const serviceDateInput = form.elements.service_date;
  if (serviceDateInput && !serviceDateInput.value) {
    serviceDateInput.valueAsDate = today;
  }
});

// Track repair date filtering
document.addEventListener('DOMContentLoaded', () => {
  const startDateInput = document.getElementById('startDate');
  const endDateInput = document.getElementById('endDate');
  const clearDateBtn = document.getElementById('clearDateFilter');
  const exportBtn = document.getElementById('exportBtn');
  const trackTable = document.querySelector('.track-table tbody');

  if (!startDateInput || !endDateInput || !trackTable) {
    return;
  }

  function filterByDate() {
    const startDate = startDateInput.value;
    const endDate = endDateInput.value;
    const rows = trackTable.querySelectorAll('tr');

    rows.forEach(row => {
      const dateCell = row.querySelector('td:nth-child(6) input[type="date"]');
      if (!dateCell) return;

      const rowDate = dateCell.value;

      let show = true;
      if (startDate && rowDate < startDate) {
        show = false;
      }
      if (endDate && rowDate > endDate) {
        show = false;
      }

      row.style.display = show ? '' : 'none';
    });

    // Update export link with date filters
    if (exportBtn) {
      let exportUrl = 'export_track.php';
      const params = [];
      if (startDate) params.push(`start_date=${encodeURIComponent(startDate)}`);
      if (endDate) params.push(`end_date=${encodeURIComponent(endDate)}`);
      if (params.length > 0) {
        exportUrl += '?' + params.join('&');
      }
      exportBtn.href = exportUrl;
    }
  }

  if (startDateInput) {
    startDateInput.addEventListener('change', filterByDate);
  }

  if (endDateInput) {
    endDateInput.addEventListener('change', filterByDate);
  }

  if (clearDateBtn) {
    clearDateBtn.addEventListener('click', () => {
      if (startDateInput) startDateInput.value = '';
      if (endDateInput) endDateInput.value = '';
      filterByDate();
    });
  }
});
