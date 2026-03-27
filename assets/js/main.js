// Global variables
let currentPage = 1;
let itemsPerPage = 10;

// Document ready function
$(document).ready(function () {
  // Initialize tooltips
  var tooltipTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="tooltip"]'),
  );
  var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });

  // Initialize popovers
  var popoverTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="popover"]'),
  );
  var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
    return new bootstrap.Popover(popoverTriggerEl);
  });

  // Auto-hide alerts after 5 seconds
  setTimeout(function () {
    $('.alert').fadeOut('slow');
  }, 5000);

  // Form validation
  $('.needs-validation').on('submit', function (event) {
    if (this.checkValidity() === false) {
      event.preventDefault();
      event.stopPropagation();
    }
    this.classList.add('was-validated');
  });

  // Confirm delete
  $('.delete-confirm').on('click', function (e) {
    if (
      !confirm(
        'Are you sure you want to delete this item? This action cannot be undone.',
      )
    ) {
      e.preventDefault();
      return false;
    }
  });

  // Search functionality
  $('#searchInput').on('keyup', function () {
    var value = $(this).val().toLowerCase();
    $('.searchable-table tbody tr').filter(function () {
      $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
    });
  });

  // Print functionality
  $('.print-btn').on('click', function () {
    window.print();
  });
});

// Function to show loading spinner
function showLoading() {
  if ($('.spinner-wrapper').length === 0) {
    $('body').append(
      '<div class="spinner-wrapper"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>',
    );
  }
  $('.spinner-wrapper').fadeIn();
}

// Function to hide loading spinner
function hideLoading() {
  $('.spinner-wrapper').fadeOut();
}

// Function to format date
function formatDate(date) {
  let d = new Date(date);
  let month = '' + (d.getMonth() + 1);
  let day = '' + d.getDate();
  let year = d.getFullYear();

  if (month.length < 2) month = '0' + month;
  if (day.length < 2) day = '0' + day;

  return [year, month, day].join('-');
}

// Function to format currency
function formatCurrency(amount) {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(amount);
}

// Function to get URL parameters
function getUrlParameter(name) {
  name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
  var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
  var results = regex.exec(location.search);
  return results === null
    ? ''
    : decodeURIComponent(results[1].replace(/\+/g, ' '));
}

// Function to export table to CSV
function exportTableToCSV(tableId, filename) {
  let csv = [];
  let rows = document.querySelectorAll('#' + tableId + ' tr');

  for (let i = 0; i < rows.length; i++) {
    let row = [],
      cols = rows[i].querySelectorAll('td, th');

    for (let j = 0; j < cols.length; j++) {
      let data = cols[j].innerText
        .replace(/(\r\n|\n|\r)/gm, '')
        .replace(/(\s\s)/gm, ' ');
      data = data.replace(/"/g, '""');
      row.push('"' + data + '"');
    }

    csv.push(row.join(','));
  }

  let csvFile = new Blob([csv.join('\n')], { type: 'text/csv' });
  let downloadLink = document.createElement('a');
  downloadLink.download = filename + '.csv';
  downloadLink.href = window.URL.createObjectURL(csvFile);
  downloadLink.style.display = 'none';
  document.body.appendChild(downloadLink);
  downloadLink.click();
  document.body.removeChild(downloadLink);
}

// Function to handle AJAX requests
function ajaxRequest(url, method, data, successCallback, errorCallback) {
  showLoading();

  $.ajax({
    url: url,
    type: method,
    data: data,
    dataType: 'json',
    success: function (response) {
      hideLoading();
      if (successCallback) successCallback(response);
    },
    error: function (xhr, status, error) {
      hideLoading();
      console.error('AJAX Error:', error);
      if (errorCallback) {
        errorCallback(error);
      } else {
        alert('An error occurred. Please try again.');
      }
    },
  });
}

// Chart color palettes
const chartColors = {
  primary: '#4e73df',
  success: '#1cc88a',
  info: '#36b9cc',
  warning: '#f6c23e',
  danger: '#e74a3b',
  secondary: '#858796',
};

// Function to create a bar chart
function createBarChart(ctx, labels, data, label, color) {
  return new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [
        {
          label: label,
          data: data,
          backgroundColor: color || chartColors.primary,
          borderColor: color || chartColors.primary,
          borderWidth: 1,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          beginAtZero: true,
        },
      },
    },
  });
}

// Function to create a line chart
function createLineChart(ctx, labels, data, label, color) {
  return new Chart(ctx, {
    type: 'line',
    data: {
      labels: labels,
      datasets: [
        {
          label: label,
          data: data,
          borderColor: color || chartColors.primary,
          backgroundColor: 'rgba(78, 115, 223, 0.05)',
          borderWidth: 2,
          fill: true,
          tension: 0.4,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          beginAtZero: true,
        },
      },
    },
  });
}

// Function to create a pie chart
function createPieChart(ctx, data, labels, colors) {
  return new Chart(ctx, {
    type: 'pie',
    data: {
      labels: labels,
      datasets: [
        {
          data: data,
          backgroundColor: colors || [
            chartColors.primary,
            chartColors.success,
            chartColors.info,
            chartColors.warning,
            chartColors.danger,
          ],
          borderWidth: 0,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
    },
  });
}

// Function to show notification
function showNotification(message, type = 'info') {
  let alertClass = '';
  switch (type) {
    case 'success':
      alertClass = 'alert-success';
      break;
    case 'error':
      alertClass = 'alert-danger';
      break;
    case 'warning':
      alertClass = 'alert-warning';
      break;
    default:
      alertClass = 'alert-info';
  }

  let notification = $(
    '<div class="alert ' +
      alertClass +
      ' alert-dismissible fade show" role="alert">' +
      message +
      '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
      '</div>',
  );

  $('.container-fluid').prepend(notification);

  setTimeout(function () {
    notification.fadeOut('slow', function () {
      $(this).remove();
    });
  }, 5000);
}
