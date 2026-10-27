<?php

require_once 'db.php';

header('Content-Type: application/json');

$sql = "SELECT
            Event_ID,
            Event_Name,
            Max_Participants,
            Registration_Fee,
            Event_Date,
            Event_Time,
            Venue,
            Event_Type
        FROM EVENT
        ORDER BY Event_Date ASC, Event_Name ASC";

$result = mysqli_query($conn, $sql);

$events = [];

while ($row = mysqli_fetch_assoc($result)) {
    $events[] = $row;
}

echo json_encode($events);

?>
