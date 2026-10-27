<?php
/**
 * Backend Processing: register_process.php
 * Handles student registration form submission.
 * 
 * Demonstrates DBMS Concepts:
 * - Prepared Statements (Security against SQL Injection)
 * - Multi-table operations (PARTICIPANT, TEAM, REGISTRATION, PAYMENT)
 * - UNIQUE constraint verification
 * - COUNT aggregate query to enforce Max_Participants limit
 */

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: register.html");
    exit();
}

// 1. Sanitize and retrieve POST data
$name          = trim($_POST['name'] ?? '');
$roll_no       = trim(strtoupper($_POST['roll_no'] ?? ''));
$email         = trim($_POST['email'] ?? '');
$phone         = trim($_POST['phone'] ?? '');
$gender        = trim($_POST['gender'] ?? 'Male');
$semester      = intval($_POST['semester'] ?? 1);
$year          = intval($_POST['year'] ?? 1);
$department_id = intval($_POST['department_id'] ?? 0);
$event_id      = intval($_POST['event_id'] ?? 0);
$team_name     = trim($_POST['team_name'] ?? '');

// 2. Form validation
if (empty($name) || empty($roll_no) || empty($email) || empty($phone) || $department_id <= 0 || $event_id <= 0) {
    header("Location: register.html?status=error&msg=" . urlencode("Please fill in all required fields."));
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: register.html?status=error&msg=" . urlencode("Invalid email address format."));
    exit();
}

// 3. Check Event Details & Seat Availability
$event_stmt = mysqli_prepare($conn, "SELECT Event_Name, Max_Participants, Registration_Fee FROM EVENT WHERE Event_ID = ?");
mysqli_stmt_bind_param($event_stmt, "i", $event_id);
mysqli_stmt_execute($event_stmt);
$event_res = mysqli_stmt_get_result($event_stmt);
$event_data = mysqli_fetch_assoc($event_res);
mysqli_stmt_close($event_stmt);

if (!$event_data) {
    header("Location: register.html?status=error&msg=" . urlencode("Selected event does not exist."));
    exit();
}

$event_name   = $event_data['Event_Name'];
$max_seats    = $event_data['Max_Participants'];
$reg_fee      = $event_data['Registration_Fee'];

// Count current registrations for this event
$count_stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM REGISTRATION WHERE Event_ID = ?");
mysqli_stmt_bind_param($count_stmt, "i", $event_id);
mysqli_stmt_execute($count_stmt);
$count_res = mysqli_stmt_get_result($count_stmt);
$count_row = mysqli_fetch_assoc($count_res);
mysqli_stmt_close($count_stmt);

if ($count_row['total'] >= $max_seats) {
    header("Location: register.html?status=error&msg=" . urlencode("Registration closed! Event '$event_name' has reached maximum capacity."));
    exit();
}

// 4. Find or Create Participant Record
$p_stmt = mysqli_prepare($conn, "SELECT Participant_ID FROM PARTICIPANT WHERE Roll_No = ?");
mysqli_stmt_bind_param($p_stmt, "s", $roll_no);
mysqli_stmt_execute($p_stmt);
$p_res = mysqli_stmt_get_result($p_stmt);

$participant_id = null;

if ($p_row = mysqli_fetch_assoc($p_res)) {
    // Participant exists, update current details
    $participant_id = $p_row['Participant_ID'];
    mysqli_stmt_close($p_stmt);

    $upd_stmt = mysqli_prepare($conn, "UPDATE PARTICIPANT SET Name = ?, Email = ?, Phone_No = ?, Gender = ?, Semester = ?, Year = ?, Department_ID = ? WHERE Participant_ID = ?");
    mysqli_stmt_bind_param($upd_stmt, "ssssiiii", $name, $email, $phone, $gender, $semester, $year, $department_id, $participant_id);
    mysqli_stmt_execute($upd_stmt);
    mysqli_stmt_close($upd_stmt);
} else {
    // New participant, insert into database
    mysqli_stmt_close($p_stmt);
    $ins_stmt = mysqli_prepare($conn, "INSERT INTO PARTICIPANT (Roll_No, Name, Email, Phone_No, Gender, Semester, Year, Department_ID) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($ins_stmt, "sssssiii", $roll_no, $name, $email, $phone, $gender, $semester, $year, $department_id);
    
    if (!mysqli_stmt_execute($ins_stmt)) {
        header("Location: register.html?status=error&msg=" . urlencode("Failed to save participant profile."));
        exit();
    }
    $participant_id = mysqli_insert_id($conn);
    mysqli_stmt_close($ins_stmt);
}

// 5. Verify UNIQUE Constraint: Has student already registered for this event?
$check_reg_stmt = mysqli_prepare($conn, "SELECT Registration_ID FROM REGISTRATION WHERE Participant_ID = ? AND Event_ID = ?");
mysqli_stmt_bind_param($check_reg_stmt, "ii", $participant_id, $event_id);
mysqli_stmt_execute($check_reg_stmt);
$check_reg_res = mysqli_stmt_get_result($check_reg_stmt);

if (mysqli_num_rows($check_reg_res) > 0) {
    mysqli_stmt_close($check_reg_stmt);
    header("Location: register.html?status=error&msg=" . urlencode("Roll No $roll_no is already registered for '$event_name'. Duplicate registrations are not allowed."));
    exit();
}
mysqli_stmt_close($check_reg_stmt);

// 6. Handle Team Creation (if team name provided)
$team_id = null;
if (!empty($team_name)) {
    $team_stmt = mysqli_prepare($conn, "INSERT INTO TEAM (Team_Name, Team_Leader_ID, Team_Size) VALUES (?, ?, 1)");
    mysqli_stmt_bind_param($team_stmt, "si", $team_name, $participant_id);
    if (mysqli_stmt_execute($team_stmt)) {
        $team_id = mysqli_insert_id($conn);
    }
    mysqli_stmt_close($team_stmt);
}

// 7. Insert into REGISTRATION Table
$reg_stmt = mysqli_prepare($conn, "INSERT INTO REGISTRATION (Participant_ID, Event_ID, Team_ID, Status) VALUES (?, ?, ?, 'Confirmed')");
mysqli_stmt_bind_param($reg_stmt, "iii", $participant_id, $event_id, $team_id);

if (!mysqli_stmt_execute($reg_stmt)) {
    mysqli_stmt_close($reg_stmt);
    header("Location: register.html?status=error&msg=" . urlencode("Failed to create registration record."));
    exit();
}
$registration_id = mysqli_insert_id($conn);
mysqli_stmt_close($reg_stmt);

// 8. Generate Payment Receipt
$txn_id = "TXN" . date("Ymd") . rand(10000, 99999);
$pay_stmt = mysqli_prepare($conn, "INSERT INTO PAYMENT (Registration_ID, Amount, Payment_Mode, Transaction_ID) VALUES (?, ?, 'Online UPI', ?)");
mysqli_stmt_bind_param($pay_stmt, "ids", $registration_id, $reg_fee, $txn_id);
mysqli_stmt_execute($pay_stmt);
mysqli_stmt_close($pay_stmt);

// 9. Redirect back with success message
$success_message = "Registered for '$event_name'. Transaction ID: $txn_id.";
header("Location: register.html?status=success&reg_id=$registration_id&msg=" . urlencode($success_message));
exit();
?>
