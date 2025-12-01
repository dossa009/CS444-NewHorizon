<?php
include("DataBase.php");
include("DB.php");
$dom = new DOMDocument();
$dom->load("app.js");
$month = $dom->getElementById('month-year');
$result = $mysqli->query("SELECT * FROM Calendar_Events WHERE MONTH(Calendar_Events.Event_Date) == $month");

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $start = new DateTime($row["start_date"]);
        $end = new DateTime($row["end_date"]);

        while ($start <= $end) {
            $eventsFromDB[] = [
                "id" => $row["id"],
                "title" => "{$row['course_name']} - {$row['instructor_name']}",
                "date" => $start->format('Y-m-d'),
                "start" => $row["start_date"],
                "end" => $row["end_date"],
                "start_time" => $row["start_time"],
                "end_time" => $row["end_time"],
            ];
            $start->modify('+1 day');
        }
    }
}
$mysqli->close();
?>