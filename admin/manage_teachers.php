<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

checkPermission(['admin']);

$page_title = 'School Calendar';

// Handle event creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_event'])) {
  $event_title = sanitize($_POST['event_title']);
  $event_date = sanitize($_POST['event_date']);
  $start_time = sanitize($_POST['start_time']);
  $end_time = sanitize($_POST['end_time']);
  $event_type = sanitize($_POST['event_type']);
  $description = sanitize($_POST['description']);
  $target_audience = sanitize($_POST['target_audience']);
  $created_by = $_SESSION['user_id'];

  $query = "INSERT INTO school_calendar (event_title, event_date, start_time, end_time, event_type, description, target_audience, created_by) 
              VALUES ('$event_title', '$event_date', '$start_time', '$end_time', '$event_type', '$description', '$target_audience', $created_by)";

  if (mysqli_query($conn, $query)) {
    $_SESSION['message'] = 'Event added successfully!';
    $_SESSION['message_type'] = 'success';
  } else {
    $_SESSION['message'] = 'Error adding event!';
    $_SESSION['message_type'] = 'danger';
  }
  redirect('admin/calendar.php');
}

// Handle event deletion
if (isset($_GET['delete'])) {
  $event_id = (int)$_GET['delete'];
  $query = "DELETE FROM school_calendar WHERE id = $event_id";
  if (mysqli_query($conn, $query)) {
    $_SESSION['message'] = 'Event deleted successfully!';
    $_SESSION['message_type'] = 'success';
  } else {
    $_SESSION['message'] = 'Error deleting event!';
    $_SESSION['message_type'] = 'danger';
  }
  redirect('admin/calendar.php');
}

// Get events for current month
$current_month = date('Y-m');
$events = mysqli_query($conn, "SELECT * FROM school_calendar WHERE event_date LIKE '$current_month%' ORDER BY event_date");

// Get upcoming events
$upcoming = mysqli_query($conn, "SELECT * FROM school_calendar WHERE event_date >= CURDATE() ORDER BY event_date LIMIT 10");

include '../includes/header.php';
?>

<div class="row">
  <div class="col-md-8">
    <div class="card">
      <div class="card-header">
        <h6 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Calendar View</h6>
      </div>
      <div class="card-body">
        <div id="calendar"></div>
      </div>
    </div>

    <div class="card mt-4">
      <div class="card-header">
        <h6 class="mb-0"><i class="fas fa-list me-2"></i>Upcoming Events</h6>
      </div>
      <div class="card-body">
        <div class="list-group">
          <?php if (mysqli_num_rows($upcoming) > 0): ?>
            <?php while ($event = mysqli_fetch_assoc($upcoming)): ?>
              <div class="list-group-item">
                <div class="d-flex w-100 justify-content-between">
                  <h6 class="mb-1">
                    <span class="badge bg-<?php
                                          echo $event['event_type'] == 'holiday' ? 'danger' : ($event['event_type'] == 'exam' ? 'warning' : ($event['event_type'] == 'event' ? 'info' : 'secondary'));
                                          ?> me-2">
                      <?php echo strtoupper($event['event_type']); ?>
                    </span>
                    <?php echo htmlspecialchars($event['event_title']); ?>
                  </h6>
                  <small><?php echo date('M d, Y', strtotime($event['event_date'])); ?></small>
                </div>
                <p class="mb-1"><?php echo htmlspecialchars($event['description']); ?></p>
                <small>
                  <i class="fas fa-clock me-1"></i>
                  <?php echo date('h:i A', strtotime($event['start_time'])); ?> -
                  <?php echo date('h:i A', strtotime($event['end_time'])); ?>
                </small>
                <div class="mt-2">
                  <a href="?delete=<?php echo $event['id']; ?>" class="btn btn-sm btn-danger delete-confirm">
                    <i class="fas fa-trash"></i> Delete
                  </a>
                </div>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <div class="text-center py-4">
              <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
              <p class="text-muted">No upcoming events</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card">
      <div class="card-header">
        <h6 class="mb-0"><i class="fas fa-plus me-2"></i>Add New Event</h6>
      </div>
      <div class="card-body">
        <form method="POST" action="">
          <div class="mb-3">
            <label class="form-label">Event Title *</label>
            <input type="text" class="form-control" name="event_title" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Event Date *</label>
            <input type="date" class="form-control" name="event_date" required>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Start Time</label>
              <input type="time" class="form-control" name="start_time">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">End Time</label>
              <input type="time" class="form-control" name="end_time">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Event Type *</label>
            <select class="form-select" name="event_type" required>
              <option value="event">General Event</option>
              <option value="holiday">Holiday</option>
              <option value="exam">Examination</option>
              <option value="meeting">Meeting</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Target Audience</label>
            <select class="form-select" name="target_audience">
              <option value="all">All</option>
              <option value="students">Students Only</option>
              <option value="teachers">Teachers Only</option>
              <option value="parents">Parents Only</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea class="form-control" name="description" rows="3"></textarea>
          </div>
          <button type="submit" name="add_event" class="btn btn-primary w-100">Add Event</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Include FullCalendar -->
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.css' rel='stylesheet' />
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.js'></script>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
      initialView: 'dayGridMonth',
      headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay'
      },
      events: 'get_events.php',
      eventClick: function(info) {
        alert('Event: ' + info.event.title);
      }
    });
    calendar.render();
  });
</script>

<style>
  #calendar {
    background: white;
    padding: 20px;
    border-radius: 10px;
  }
</style>

<?php include '../includes/footer.php'; ?>