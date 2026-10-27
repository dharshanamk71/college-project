<?php
/**
 * API: get_departments.php
 * Fetches all academic departments from MySQL.
 */
require_once 'db.php';

header('Content-Type: application/json');

$sql = "SELECT Department_ID, Department_Name, Location FROM DEPARTMENT ORDER BY Department_Name ASC";
$result = mysqli_query($conn, $sql);

$departments = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $departments[] = $row;
    }
}

echo json_encode($departments);
?>
