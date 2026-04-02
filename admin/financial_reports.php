<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

checkPermission(['admin']);

$page_title = 'Financial Reports';

// Get filter parameters
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$payment_method = isset($_GET['payment_method']) ? $_GET['payment_method'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'summary';

// Get all classes for filter dropdown
$classes_query = "SELECT * FROM classes ORDER BY class_name, section";
$classes = mysqli_query($conn, $classes_query);

// Get payment statistics
$stats_query = "SELECT 
                    COUNT(*) as total_transactions,
                    SUM(amount) as total_amount,
                    AVG(amount) as avg_amount,
                    COUNT(DISTINCT student_id) as unique_students
                FROM fee_payments 
                WHERE payment_date BETWEEN '$start_date' AND '$end_date'";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Get payment method breakdown
$method_query = "SELECT 
                    payment_method,
                    COUNT(*) as count,
                    SUM(amount) as total
                FROM fee_payments 
                WHERE payment_date BETWEEN '$start_date' AND '$end_date'
                GROUP BY payment_method";
$method_breakdown = mysqli_query($conn, $method_query);

// Get daily collection for chart
$daily_query = "SELECT 
                    DATE(payment_date) as date,
                    SUM(amount) as total,
                    COUNT(*) as transactions
                FROM fee_payments 
                WHERE payment_date BETWEEN '$start_date' AND '$end_date'
                GROUP BY DATE(payment_date)
                ORDER BY date";
$daily_collection = mysqli_query($conn, $daily_query);

// Get class-wise collection
$class_wise_query = "SELECT 
                        c.class_name,
                        c.section,
                        SUM(fp.amount) as total_collected,
                        COUNT(fp.id) as transaction_count,
                        COUNT(DISTINCT fp.student_id) as student_count
                    FROM fee_payments fp
                    JOIN students s ON fp.student_id = s.id
                    JOIN classes c ON s.class_id = c.id
                    WHERE fp.payment_date BETWEEN '$start_date' AND '$end_date'
                    GROUP BY c.id
                    ORDER BY total_collected DESC";
$class_wise = mysqli_query($conn, $class_wise_query);

// Get recent payments
$recent_query = "SELECT fp.*, s.admission_number, u.full_name as student_name, c.class_name, c.section
                FROM fee_payments fp
                JOIN students s ON fp.student_id = s.id
                JOIN users u ON s.user_id = u.id
                JOIN classes c ON s.class_id = c.id
                WHERE fp.payment_date BETWEEN '$start_date' AND '$end_date'
                ORDER BY fp.payment_date DESC
                LIMIT 50";
$recent_payments = mysqli_query($conn, $recent_query);

// Get top paying students
$top_students_query = "SELECT 
                            s.admission_number,
                            u.full_name as student_name,
                            c.class_name,
                            c.section,
                            SUM(fp.amount) as total_paid,
                            COUNT(fp.id) as payment_count
                        FROM fee_payments fp
                        JOIN students s ON fp.student_id = s.id
                        JOIN users u ON s.user_id = u.id
                        JOIN classes c ON s.class_id = c.id
                        WHERE fp.payment_date BETWEEN '$start_date' AND '$end_date'
                        GROUP BY fp.student_id
                        ORDER BY total_paid DESC
                        LIMIT 10";
$top_students = mysqli_query($conn, $top_students_query);

include '../includes/header.php';
?>

<style>
  .financial-stat-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    transition: all 0.3s ease;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    position: relative;
    overflow: hidden;
  }

  .financial-stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(59, 130, 246, 0.15);
  }

  .financial-stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #3b82f6, #60a5fa);
  }

  .financial-stat-card h3 {
    font-size: 2rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
    color: #3b82f6;
  }

  .financial-stat-card p {
    margin: 0;
    color: #6c757d;
  }

  .filter-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
  }
</style>

<div class="row mb-4">
  <div class="col-12">
    <div class="welcome-card" style="background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%); border-radius: 15px; padding: 2rem;">
      <div class="row align-items-center">
        <div class="col-md-8">
          <h2 class="text-white">Financial Reports</h2>
          <p class="text-white-50 mb-0">Track fee collections, payment history, and revenue analysis.</p>
        </div>
        <div class="col-md-4 text-end">
          <i class="fas fa-chart-line" style="font-size: 4rem; opacity: 0.3; color: white;"></i>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Filter Section -->
<div class="filter-card">
  <form method="GET" action="" class="row g-3">
    <div class="col-md-3">
      <label class="form-label fw-semibold">Start Date</label>
      <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label fw-semibold">End Date</label>
      <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label fw-semibold">Class</label>
      <select class="form-select" name="class_id">
        <option value="">All Classes</option>
        <?php while ($class = mysqli_fetch_assoc($classes)): ?>
          <option value="<?php echo $class['id']; ?>" <?php echo $class_id == $class['id'] ? 'selected' : ''; ?>>
            <?php echo $class['class_name'] . ' ' . $class['section']; ?>
          </option>
        <?php endwhile; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label fw-semibold">Payment Method</label>
      <select class="form-select" name="payment_method">
        <option value="">All Methods</option>
        <option value="cash" <?php echo $payment_method == 'cash' ? 'selected' : ''; ?>>Cash</option>
        <option value="bank_transfer" <?php echo $payment_method == 'bank_transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
        <option value="online" <?php echo $payment_method == 'online' ? 'selected' : ''; ?>>Online</option>
        <option value="check" <?php echo $payment_method == 'check' ? 'selected' : ''; ?>>Check</option>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label fw-semibold">&nbsp;</label>
      <button type="submit" class="btn w-100" style="background-color: #3b82f6; color: white;">
        <i class="fas fa-search me-2"></i>Generate
      </button>
    </div>
  </form>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
  <div class="col-md-3 mb-3">
    <div class="financial-stat-card">
      <h3><?php echo number_format($stats['total_amount'] ?? 0, 2); ?></h3>
      <p>Total Collection</p>
      <small class="text-muted"><?php echo $stats['total_transactions'] ?? 0; ?> transactions</small>
    </div>
  </div>
  <div class="col-md-3 mb-3">
    <div class="financial-stat-card">
      <h3><?php echo number_format(($stats['avg_amount'] ?? 0), 2); ?></h3>
      <p>Average Payment</p>
      <small class="text-muted">Per transaction</small>
    </div>
  </div>
  <div class="col-md-3 mb-3">
    <div class="financial-stat-card">
      <h3><?php echo number_format($stats['unique_students'] ?? 0); ?></h3>
      <p>Students Paid</p>
      <small class="text-muted">Unique payers</small>
    </div>
  </div>
  <div class="col-md-3 mb-3">
    <div class="financial-stat-card">
      <h3><?php echo number_format(($stats['total_transactions'] ?? 0)); ?></h3>
      <p>Total Transactions</p>
      <small class="text-muted">Payment records</small>
    </div>
  </div>
</div>

<div class="row">
  <!-- Daily Collection Chart -->
  <div class="col-lg-8 mb-4">
    <div class="card shadow-sm">
      <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
          <i class="fas fa-chart-line me-2"></i>Daily Collection Trend
        </h6>
      </div>
      <div class="card-body">
        <canvas id="collectionChart" style="max-height: 350px; width: 100%;"></canvas>
      </div>
    </div>
  </div>

  <!-- Payment Method Breakdown -->
  <div class="col-lg-4 mb-4">
    <div class="card shadow-sm">
      <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
          <i class="fas fa-chart-pie me-2"></i>Payment Methods
        </h6>
      </div>
      <div class="card-body">
        <canvas id="paymentMethodChart" style="max-height: 250px;"></canvas>
        <div class="mt-3">
          <?php while ($method = mysqli_fetch_assoc($method_breakdown)): ?>
            <div class="d-flex justify-content-between mb-2">
              <span>
                <i class="fas fa-<?php
                                  echo $method['payment_method'] == 'cash' ? 'money-bill-wave' : ($method['payment_method'] == 'bank_transfer' ? 'university' : ($method['payment_method'] == 'online' ? 'globe' : 'check'));
                                  ?> me-2"></i>
                <?php echo ucfirst(str_replace('_', ' ', $method['payment_method'])); ?>
              </span>
              <span class="fw-bold"><?php echo number_format($method['total'], 2); ?></span>
            </div>
          <?php endwhile; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <!-- Class-wise Collection -->
  <div class="col-lg-6 mb-4">
    <div class="card shadow-sm">
      <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
          <i class="fas fa-chalkboard me-2"></i>Class-wise Collection
        </h6>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead style="background-color: #3b82f6; color: white;">
              <tr>
                <th>Class</th>
                <th>Students Paid</th>
                <th>Transactions</th>
                <th>Total Collected</th>
              </tr>
            </thead>
            <tbody>
              <?php if (mysqli_num_rows($class_wise) > 0): ?>
                <?php while ($class = mysqli_fetch_assoc($class_wise)): ?>
                  <tr>
                    <td><strong><?php echo $class['class_name'] . ' ' . $class['section']; ?></strong></td>
                    <td><?php echo $class['student_count']; ?></td>
                    <td><?php echo $class['transaction_count']; ?></td>
                    <td><span class="text-success fw-bold"><?php echo number_format($class['total_collected'], 2); ?></span></td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="4" class="text-center py-4">No payment data available</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Top Paying Students -->
  <div class="col-lg-6 mb-4">
    <div class="card shadow-sm">
      <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
          <i class="fas fa-trophy me-2"></i>Top Paying Students
        </h6>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead style="background-color: #3b82f6; color: white;">
              <tr>
                <th>Student Name</th>
                <th>Class</th>
                <th>Payments</th>
                <th>Total Paid</th>
              </tr>
            </thead>
            <tbody>
              <?php if (mysqli_num_rows($top_students) > 0): ?>
                <?php while ($student = mysqli_fetch_assoc($top_students)): ?>
                  <tr>
                    <td><?php echo $student['student_name']; ?><br><small class="text-muted"><?php echo $student['admission_number']; ?></small></td>
                    <td><?php echo $student['class_name'] . ' ' . $student['section']; ?></td>
                    <td><?php echo $student['payment_count']; ?></td>
                    <td><span class="text-success fw-bold"><?php echo number_format($student['total_paid'], 2); ?></span></td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="4" class="text-center py-4">No payment data available</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Recent Payments Table -->
<div class="card shadow-sm">
  <div class="card-header bg-white">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
      <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
        <i class="fas fa-history me-2"></i>Recent Payments
      </h6>
      <div class="mt-2 mt-md-0">
        <button class="btn btn-sm btn-outline-primary me-1" onclick="printReport()">
          <i class="fas fa-print me-1"></i> Print
        </button>
        <button class="btn btn-sm btn-outline-success" onclick="exportToCSV()">
          <i class="fas fa-file-csv me-1"></i> Export CSV
        </button>
      </div>
    </div>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive" id="paymentsTable">
      <table class="table table-hover mb-0">
        <thead style="background-color: #3b82f6; color: white;">
          <tr class="text-center">
            <th>Receipt No</th>
            <th>Date</th>
            <th>Student Name</th>
            <th>Admission No</th>
            <th>Class</th>
            <th>Amount</th>
            <th>Payment Method</th>
            <th>Transaction ID</th>
          </tr>
        </thead>
        <tbody>
          <?php if (mysqli_num_rows($recent_payments) > 0): ?>
            <?php while ($payment = mysqli_fetch_assoc($recent_payments)): ?>
              <tr>
                <td class="text-center"><strong><?php echo $payment['receipt_number']; ?></strong></td>
                <td class="text-center"><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                <td><?php echo $payment['student_name']; ?></td>
                <td class="text-center"><?php echo $payment['admission_number']; ?></td>
                <td><?php echo $payment['class_name'] . ' ' . $payment['section']; ?></td>
                <td class="text-center text-success fw-bold"><?php echo number_format($payment['amount'], 2); ?></td>
                <td class="text-center">
                  <span class="badge bg-<?php
                                        echo $payment['payment_method'] == 'cash' ? 'success' : ($payment['payment_method'] == 'bank_transfer' ? 'info' : ($payment['payment_method'] == 'online' ? 'primary' : 'warning'));
                                        ?>">
                    <?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?>
                  </span>
                </td>
                <td class="text-center"><small><?php echo $payment['transaction_id'] ?: 'N/A'; ?></small></td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="8" class="text-center py-4">No payment records found</td>
            </tr>
          <?php endif; ?>
        </tbody>
        <tfoot style="background-color: #f8f9fa;">
          <tr class="fw-bold">
            <td colspan="5" class="text-end">TOTAL:</td>
            <td class="text-center text-success"><?php echo number_format($stats['total_amount'] ?? 0, 2); ?></td>
            <td colspan="2"></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  // Daily Collection Chart
  const ctx = document.getElementById('collectionChart').getContext('2d');
  const dailyData = {
    labels: <?php
            $labels = [];
            $values = [];
            mysqli_data_seek($daily_collection, 0);
            while ($row = mysqli_fetch_assoc($daily_collection)) {
              $labels[] = date('M d', strtotime($row['date']));
              $values[] = $row['total'];
            }
            echo json_encode($labels);
            ?>,
    datasets: [{
      label: 'Daily Collection (USD)',
      data: <?php echo json_encode($values); ?>,
      borderColor: '#3b82f6',
      backgroundColor: 'rgba(59, 130, 246, 0.1)',
      borderWidth: 2,
      fill: true,
      tension: 0.4,
      pointBackgroundColor: '#3b82f6',
      pointBorderColor: '#fff',
      pointBorderWidth: 2,
      pointRadius: 4,
      pointHoverRadius: 6
    }]
  };

  new Chart(ctx, {
    type: 'line',
    data: dailyData,
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        tooltip: {
          callbacks: {
            label: function(context) {
              return '$' + context.parsed.y.toLocaleString();
            }
          }
        },
        legend: {
          position: 'top'
        }
      },
      scales: {
        y: {
          title: {
            display: true,
            text: 'Amount (USD)'
          },
          ticks: {
            callback: function(value) {
              return '$' + value.toLocaleString();
            }
          }
        },
        x: {
          title: {
            display: true,
            text: 'Date'
          }
        }
      }
    }
  });

  // Payment Method Chart
  const pieCtx = document.getElementById('paymentMethodChart').getContext('2d');
  <?php
  mysqli_data_seek($method_breakdown, 0);
  $method_labels = [];
  $method_values = [];
  $method_colors = [];
  while ($row = mysqli_fetch_assoc($method_breakdown)) {
    $method_labels[] = ucfirst(str_replace('_', ' ', $row['payment_method']));
    $method_values[] = $row['total'];
    $method_colors[] = $row['payment_method'] == 'cash' ? '#10b981' : ($row['payment_method'] == 'bank_transfer' ? '#3b82f6' : ($row['payment_method'] == 'online' ? '#8b5cf6' : '#f59e0b'));
  }
  ?>

  new Chart(pieCtx, {
    type: 'pie',
    data: {
      labels: <?php echo json_encode($method_labels); ?>,
      datasets: [{
        data: <?php echo json_encode($method_values); ?>,
        backgroundColor: <?php echo json_encode($method_colors); ?>,
        borderWidth: 0
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        legend: {
          position: 'bottom'
        },
        tooltip: {
          callbacks: {
            label: function(context) {
              let label = context.label || '';
              let value = context.parsed || 0;
              let total = <?php echo $stats['total_amount'] ?? 0; ?>;
              let percentage = total > 0 ? ((value / total) * 100).toFixed(2) : 0;
              return `${label}: $${value.toLocaleString()} (${percentage}%)`;
            }
          }
        }
      }
    }
  });

  // Print report
  function printReport() {
    var printContents = document.getElementById('paymentsTable').innerHTML;
    var title = '<h2 style="text-align:center;">Financial Report</h2>';
    var period = '<p style="text-align:center;">Period: <?php echo date('M d, Y', strtotime($start_date)); ?> - <?php echo date('M d, Y', strtotime($end_date)); ?></p>';
    var schoolName = '<h3 style="text-align:center;"><?php echo addslashes(getSetting('school_name', 'School Name')); ?></h3>';
    var stats = '<div class="row" style="margin: 20px 0;"><div class="col-md-4"><strong>Total Collection:</strong> $<?php echo number_format($stats['total_amount'] ?? 0, 2); ?></div><div class="col-md-4"><strong>Transactions:</strong> <?php echo $stats['total_transactions'] ?? 0; ?></div><div class="col-md-4"><strong>Students Paid:</strong> <?php echo $stats['unique_students'] ?? 0; ?></div></div><hr>';

    var printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Financial Report</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                body { padding: 20px; }
                @media print {
                    body { margin: 0; padding: 0; }
                    .btn { display: none; }
                }
            </style>
        </head>
        <body>
            ${schoolName}
            ${title}
            ${period}
            ${stats}
            <div class="table-responsive">
                ${printContents}
            </div>
            <p class="text-muted mt-4">Generated on: <?php echo date('Y-m-d H:i:s'); ?></p>
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
  }

  // Export to CSV
  function exportToCSV() {
    let csv = [];
    let rows = document.querySelectorAll('#paymentsTable table tr');

    for (let i = 0; i < rows.length; i++) {
      let row = [],
        cols = rows[i].querySelectorAll('td, th');
      for (let j = 0; j < cols.length; j++) {
        let text = cols[j].innerText.replace(/,/g, ' ');
        row.push('"' + text + '"');
      }
      csv.push(row.join(','));
    }

    let csvFile = new Blob([csv.join('\n')], {
      type: 'text/csv'
    });
    let downloadLink = document.createElement('a');
    downloadLink.download = 'Financial_Report_<?php echo date('Y-m-d'); ?>.csv';
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = 'none';
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
  }
</script>

<?php include '../includes/footer.php'; ?>