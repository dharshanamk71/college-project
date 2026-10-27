<?php
/**
 * Backend Processing: attendance_process.php
 * Handles attendance Insert / Update operations.
 */

require_once 'auth.php';
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: attendance.php");
    exit();
}

$reg_id         = intval($_POST['registration_id'] ?? 0);
$status         = trim($_POST['status'] ?? 'Present');
$redirect_event = intval($_POST['redirect_event'] ?? 0);

if ($reg_id <= 0 || empty($status)) {
    header("Location: attendance.php?error=invalid_data");
    exit();
}

// Check if attendance already exists for this registration
$check_stmt = mysqli_prepare(
    $conn,
    "SELECT Attendance_ID
     FROM ATTENDANCE
     WHERE Registration_ID = ?"
);

mysqli_stmt_bind_param($check_stmt, "i", $reg_id);
mysqli_stmt_execute($check_stmt);

$res = mysqli_stmt_get_result($check_stmt);

if ($row = mysqli_fetch_assoc($res)) {

    // Attendance already exists - UPDATE
    mysqli_stmt_close($check_stmt);

    $upd_stmt = mysqli_prepare(
        $conn,
        "UPDATE ATTENDANCE
         SET Status = ?,
             Attendance_Status = ?,
             Check_In_Time = CURRENT_TIMESTAMP
         WHERE Attendance_ID = ?"
    );

    mysqli_stmt_bind_param(
        $upd_stmt,
        "ssi",
        $status,
        $status,
        $row['Attendance_ID']
    );

    mysqli_stmt_execute($upd_stmt);
    mysqli_stmt_close($upd_stmt);

} else {

    // Attendance does not exist - INSERT
    mysqli_stmt_close($check_stmt);

    $ins_stmt = mysqli_prepare(
        $conn,
        "INSERT INTO ATTENDANCE
         (Registration_ID, Attendance_Status, Check_In_Time, Status)
         VALUES (?, ?, CURRENT_TIMESTAMP, ?)"
    );

    mysqli_stmt_bind_param(
        $ins_stmt,
        "iss",
        $reg_id,
        $status,
        $status
    );

    mysqli_stmt_execute($ins_stmt);
    mysqli_stmt_close($ins_stmt);
}

// Go back to attendance page
header("Location: attendance.php?msg=marked&event_id=$redirect_event");
exit();
?>