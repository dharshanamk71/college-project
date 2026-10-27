<?php
/**
 * Backend Processing: event_process.php
 * Handles Event Create, Update and Delete operations.
 */

require_once 'auth.php';
require_once 'db.php';

$action = $_REQUEST['action'] ?? '';

/* =========================
   CREATE EVENT
   ========================= */
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $name             = trim($_POST['event_name'] ?? '');
    $type             = trim($_POST['event_type'] ?? 'Coding');
    $max_participants = intval($_POST['max_participants'] ?? 50);
    $fee              = floatval($_POST['registration_fee'] ?? 0.00);
    $event_date       = trim($_POST['event_date'] ?? '');
    $venue_id         = intval($_POST['venue_id'] ?? 0);

    // Get venue name from VENUE table
    $venue = '';

    if ($venue_id > 0) {
        $venue_stmt = mysqli_prepare(
            $conn,
            "SELECT Venue_Name FROM VENUE WHERE Venue_ID = ?"
        );

        mysqli_stmt_bind_param($venue_stmt, "i", $venue_id);
        mysqli_stmt_execute($venue_stmt);

        $venue_result = mysqli_stmt_get_result($venue_stmt);

        if ($venue_row = mysqli_fetch_assoc($venue_result)) {
            $venue = $venue_row['Venue_Name'];
        }

        mysqli_stmt_close($venue_stmt);
    }

    // Basic validation
    if (empty($name) || empty($event_date)) {
        header("Location: manage_events.php?error=" . urlencode("Please fill in Event Name and Event Date."));
        exit();
    }

    // Default event time because the current form has no time field
    $event_time = "10:00:00";

    $sql = "INSERT INTO EVENT
            (Event_Name, Max_Participants, Registration_Fee, Event_Date, Event_Time, Venue, Event_Type)
            VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "sidssss",
        $name,
        $max_participants,
        $fee,
        $event_date,
        $event_time,
        $venue,
        $type
    );

    if (mysqli_stmt_execute($stmt)) {

        mysqli_stmt_close($stmt);

        header("Location: manage_events.php?msg=added");
        exit();

    } else {

        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);

        header(
            "Location: manage_events.php?error=" .
            urlencode("Failed to insert event: " . $error)
        );
        exit();
    }
}


/* =========================
   UPDATE EVENT
   ========================= */
elseif ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $event_id         = intval($_POST['event_id'] ?? 0);
    $name             = trim($_POST['event_name'] ?? '');
    $type             = trim($_POST['event_type'] ?? 'Coding');
    $max_participants = intval($_POST['max_participants'] ?? 50);
    $fee              = floatval($_POST['registration_fee'] ?? 0.00);
    $event_date       = trim($_POST['event_date'] ?? '');
    $venue_id         = intval($_POST['venue_id'] ?? 0);

    // Get venue name
    $venue = '';

    if ($venue_id > 0) {

        $venue_stmt = mysqli_prepare(
            $conn,
            "SELECT Venue_Name FROM VENUE WHERE Venue_ID = ?"
        );

        mysqli_stmt_bind_param($venue_stmt, "i", $venue_id);
        mysqli_stmt_execute($venue_stmt);

        $venue_result = mysqli_stmt_get_result($venue_stmt);

        if ($venue_row = mysqli_fetch_assoc($venue_result)) {
            $venue = $venue_row['Venue_Name'];
        }

        mysqli_stmt_close($venue_stmt);
    }

    if ($event_id <= 0 || empty($name) || empty($event_date)) {

        header(
            "Location: manage_events.php?error=" .
            urlencode("Please fill in Event Name and Event Date.")
        );
        exit();
    }

    // Default event time
    $event_time = "10:00:00";

    $sql = "UPDATE EVENT SET
                Event_Name = ?,
                Max_Participants = ?,
                Registration_Fee = ?,
                Event_Date = ?,
                Event_Time = ?,
                Venue = ?,
                Event_Type = ?
            WHERE Event_ID = ?";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "sidssssi",
        $name,
        $max_participants,
        $fee,
        $event_date,
        $event_time,
        $venue,
        $type,
        $event_id
    );

    if (mysqli_stmt_execute($stmt)) {

        mysqli_stmt_close($stmt);

        header("Location: manage_events.php?msg=updated");
        exit();

    } else {

        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);

        header(
            "Location: manage_events.php?error=" .
            urlencode("Failed to update event: " . $error)
        );
        exit();
    }
}


/* =========================
   DELETE EVENT
   ========================= */
elseif ($action === 'delete') {

    $event_id = intval($_GET['event_id'] ?? 0);

    if ($event_id <= 0) {

        header(
            "Location: manage_events.php?error=" .
            urlencode("Invalid event ID.")
        );
        exit();
    }

    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM EVENT WHERE Event_ID = ?"
    );

    mysqli_stmt_bind_param($stmt, "i", $event_id);

    if (mysqli_stmt_execute($stmt)) {

        mysqli_stmt_close($stmt);

        header("Location: manage_events.php?msg=deleted");
        exit();

    } else {

        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);

        header(
            "Location: manage_events.php?error=" .
            urlencode("Failed to delete event: " . $error)
        );
        exit();
    }
}


/* =========================
   INVALID ACTION
   ========================= */
else {

    header("Location: manage_events.php");
    exit();
}

?>