<?php
include("DataBase.php");
include("DB.php");

header('Content-Type: application/json');

$month = intval($_GET['month']);
$year = intval($_GET['year']);

$query = " SELECT * FROM Calendar_Events
            WHERE MONTH(start_date) = $month
            AND YEAR(start_date) = $year";

$result = $mysqli->query($query);
$events = [];

while ($row = $result->fetch_assoc()) {
    $events[] = $row;
}

echo json_encode($events);
?>