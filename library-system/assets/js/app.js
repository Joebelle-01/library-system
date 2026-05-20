document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.datatable').forEach((table) => {
    if (window.jQuery && window.jQuery.fn.DataTable) {
      window.jQuery(table).DataTable({ responsive: true, pageLength: 10, order: [] });
    }
  });

  document.querySelectorAll('.needs-validation').forEach((form) => {
    form.addEventListener('submit', (event) => {
      if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
      }
      form.classList.add('was-validated');
    });
  });

  const flash = document.querySelector('[data-flash-message]');
  if (flash && window.Swal) {
    Swal.fire({
      toast: true,
      position: 'top-end',
      timer: 2600,
      showConfirmButton: false,
      icon: flash.dataset.flashType === 'error' ? 'error' : 'success',
      title: flash.dataset.flashMessage
    });
  }

  document.querySelectorAll('.delete-form').forEach((form) => {
    form.addEventListener('submit', (event) => {
      if (!window.Swal) return;
      event.preventDefault();
      Swal.fire({
        title: 'Delete this record?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        confirmButtonColor: '#dc3545'
      }).then((result) => {
        if (result.isConfirmed) form.submit();
      });
    });
  });

  document.querySelectorAll('.edit-book').forEach((button) => {
    button.addEventListener('click', () => {
      const book = JSON.parse(button.dataset.book);
      fillFields('book_', book);
      bootstrap.Modal.getOrCreateInstance(document.getElementById('bookModal')).show();
    });
  });

  document.querySelectorAll('.edit-student').forEach((button) => {
    button.addEventListener('click', () => {
      const student = JSON.parse(button.dataset.student);
      fillFields('student_', student);
      bootstrap.Modal.getOrCreateInstance(document.getElementById('studentModal')).show();
    });
  });

  const borrowBookSelect = document.getElementById('borrowBookSelect');
  if (borrowBookSelect) {
    borrowBookSelect.addEventListener('change', async () => {
      const target = document.getElementById('availabilityText');
      if (!borrowBookSelect.value) {
        target.textContent = 'Choose a book to check availability.';
        return;
      }
      const response = await fetch(`${window.APP_URL || '/library-system'}/admin/api_book.php?id=${borrowBookSelect.value}`);
      const data = await response.json();
      target.textContent = `${data.available_copies || 0} available copies`;
      target.className = (Number(data.available_copies) > 0) ? 'form-text text-success' : 'form-text text-danger';
    });
  }

  renderDashboardCharts();
  renderWarehouseCharts();

  // Reset bookModal when closed to prevent state leakage
  const bookModal = document.getElementById('bookModal');
  if (bookModal) {
    bookModal.addEventListener('hidden.bs.modal', () => {
      const form = bookModal.querySelector('form');
      if (form) {
        form.reset();
        form.classList.remove('was-validated');
      }
      const bookId = document.getElementById('book_id');
      if (bookId) bookId.value = '';
    });
  }

  // Reset studentModal when closed to prevent state leakage
  const studentModal = document.getElementById('studentModal');
  if (studentModal) {
    studentModal.addEventListener('hidden.bs.modal', () => {
      const form = studentModal.querySelector('form');
      if (form) {
        form.reset();
        form.classList.remove('was-validated');
      }
      const studentId = document.getElementById('student_id');
      if (studentId) studentId.value = '';
    });
  }
});

function fillFields(prefix, data) {
  Object.keys(data).forEach((key) => {
    const field = document.getElementById(prefix + key);
    if (field) field.value = data[key] ?? '';
  });
}

async function renderDashboardCharts() {
  const trendCanvas = document.getElementById('borrowTrendChart');
  const topCanvas = document.getElementById('topBooksChart');
  if (!trendCanvas || !window.Chart) return;
  const response = await fetch(`${window.APP_URL || '/library-system'}/admin/api_dashboard.php`);
  const data = await response.json();
  new Chart(trendCanvas, {
    type: 'line',
    data: {
      labels: data.trend.map((item) => item.label),
      datasets: [{ label: 'Borrows', data: data.trend.map((item) => item.total), borderColor: '#2563eb', backgroundColor: 'rgba(37,99,235,.12)', tension: .35, fill: true }]
    }
  });
  new Chart(topCanvas, {
    type: 'bar',
    data: {
      labels: data.topBooks.map((item) => item.label),
      datasets: [{ label: 'Borrow Count', data: data.topBooks.map((item) => item.total), backgroundColor: ['#2563eb', '#16a34a', '#f59e0b', '#dc2626', '#7c3aed'] }]
    },
    options: { indexAxis: 'y' }
  });
}

function renderWarehouseCharts() {
  const monthly = document.getElementById('warehouseMonthlyChart');
  if (!monthly || !window.Chart) return;
  const labels = JSON.parse(monthly.dataset.labels || '[]');
  const borrows = JSON.parse(monthly.dataset.borrows || '[]');
  const fines = JSON.parse(monthly.dataset.fines || '[]');
  new Chart(monthly, {
    type: 'bar',
    data: {
      labels,
      datasets: [
        { label: 'Borrows', data: borrows, backgroundColor: '#2563eb' },
        { label: 'Fines', data: fines, backgroundColor: '#16a34a' }
      ]
    }
  });
  const fine = document.getElementById('fineChart');
  if (fine) {
    new Chart(fine, {
      type: 'doughnut',
      data: {
        labels,
        datasets: [{ data: fines, backgroundColor: ['#16a34a', '#2563eb', '#f59e0b', '#dc2626', '#7c3aed', '#0891b2'] }]
      }
    });
  }
}

