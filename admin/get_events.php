<?php
require_once '../config/database.php';

$query = "SELECT id, event_title as title, event_date as start, event_date as end, event_type, description 
          FROM school_calendar";
$result = mysqli_query($conn, $query);

$events = [];
while ($row = mysqli_fetch_assoc($result)) {
  $color = '#3788d8';
  switch ($row['event_type']) {
    case 'holiday':
      $color = '#dc3545';
      break;
    case 'exam':
      $color = '#ffc107';
      break;
    case 'meeting':
      $color = '#28a745';
      break;
    default:
      $color = '#007bff';
  }

  $events[] = [
    'id' => $row['id'],
    'title' => $row['title'],
    'start' => $row['start'],
    'end' => $row['end'],
    'color' => $color,
    'description' => $row['description']
  ];
}

header('Content-Type: application/json');
echo json_encode($events);
