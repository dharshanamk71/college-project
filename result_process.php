<?php
/**
 * Backend Processing: result_process.php
 * Handles saving and deleting results in the RESULT table.
 * Automatically links to CERTIFICATE creation where appropriate.
 */

require_once 'auth.php';
require_once 'db.php';

$action = $_REQUEST['action'] ?? '';

if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $reg_id      = intval($_POST['registration_id'] ?? 0);
    $position    = trim($_POST['position'] ?? 'Participant');
    $score       = floatval($_POST['score'] ?? 0);
    $result_date = trim($_POST['result_date'] ?? date('Y-m-d'));
    $remarks     = trim($_POST['remarks'] ?? '');

    if ($reg_id <= 0 || empty($position) || empty($result_date)) {
        header("Location: results.php?error=" . urlencode("Please fill in all mandatory result fields."));
        exit();
    }

    // Check if result already declared for this registration
    $chk_stmt = mysqli_prepare($conn, "SELECT Result_ID FROM RESULT WHERE Registration_ID = ?");
    mysqli_stmt_bind_param($chk_stmt, "i", $reg_id);
    mysqli_stmt_execute($chk_stmt);
    $chk_res = mysqli_stmt_get_result($chk_stmt);

    if ($row = mysqli_fetch_assoc($chk_res)) {
        // Update existing result
        $result_id = $row['Result_ID'];
        mysqli_stmt_close($chk_stmt);

        $upd = mysqli_prepare($conn, "UPDATE RESULT SET Position = ?, Score = ?, Result_Declared_Date = ?, Remarks = ? WHERE Result_ID = ?");
        mysqli_stmt_bind_param($upd, "sdssi", $position, $score, $result_date, $remarks, $result_id);
        mysqli_stmt_execute($upd);
        mysqli_stmt_close($upd);
    } else {
        // Insert new result
        mysqli_stmt_close($chk_stmt);
        $ins = mysqli_prepare($conn, "INSERT INTO RESULT (Registration_ID, Position, Score, Result_Declared_Date, Remarks) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($ins, "isdss", $reg_id, $position, $score, $result_date, $remarks);
        mysqli_stmt_execute($ins);
        $result_id = mysqli_insert_id($conn);
        mysqli_stmt_close($ins);

        // Automatically issue Certificate for winners / participants
        $cert_no = "CTF-2026-" . strtoupper(bin2hex(random_bytes(3))) . "-" . $result_id;
        $issue_date = date('Y-m-d');
        $cert_stmt = mysqli_prepare($conn, "INSERT INTO CERTIFICATE (Result_ID, Certificate_No, Issue_Date) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($cert_stmt, "iss", $result_id, $cert_no, $issue_date);
        mysqli_stmt_execute($cert_stmt);
        mysqli_stmt_close($cert_stmt);
    }

    header("Location: results.php?msg=saved");
    exit();

} elseif ($action === 'delete') {
    $result_id = intval($_GET['result_id'] ?? 0);

    if ($result_id <= 0) {
        header("Location: results.php?error=" . urlencode("Invalid result ID."));
        exit();
    }

    $del_stmt = mysqli_prepare($conn, "DELETE FROM RESULT WHERE Result_ID = ?");
    mysqli_stmt_bind_param($del_stmt, "i", $result_id);
    mysqli_stmt_execute($del_stmt);
    mysqli_stmt_close($del_stmt);

    header("Location: results.php?msg=deleted");
    exit();

} else {
    header("Location: results.php");
    exit();
}
?>
