<?php
/**
 * Registrations Management: registrations.php
 * Demonstrates:
 * - Multi-table INNER/LEFT JOINs across 5 tables
 * - Dynamic SQL WHERE filtering (search by roll no, name, event, status)
 * - Status updating and record deletion
 */

require_once 'auth.php';
require_once 'db.php';

// Handle Registration Status Change (e.g. Confirmed, Pending, Cancelled)
if (isset($_POST['update_status']) && isset($_POST['registration_id']) && isset($_POST['new_status'])) {
    $reg_id = intval($_POST['registration_id']);
    $new_status = trim($_POST['new_status']);
    
    $upd_stmt = mysqli_prepare($conn, "UPDATE REGISTRATION SET Status = ? WHERE Registration_ID = ?");
    mysqli_stmt_bind_param($upd_stmt, "si", $new_status, $reg_id);
    mysqli_stmt_execute($upd_stmt);
    mysqli_stmt_close($upd_stmt);

    header("Location: registrations.php?msg=status_updated");
    exit();
}

// Handle Registration Deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $del_id = intval($_GET['id']);
    $del_stmt = mysqli_prepare($conn, "DELETE FROM REGISTRATION WHERE Registration_ID = ?");
    mysqli_stmt_bind_param($del_stmt, "i", $del_id);
    mysqli_stmt_execute($del_stmt);
    mysqli_stmt_close($del_stmt);

    header("Location: registrations.php?msg=deleted");
    exit();
}

// Search and Filter Parameters
$search       = trim($_GET['search'] ?? '');
$event_filter = intval($_GET['event_id'] ?? 0);
$status_filter= trim($_GET['status'] ?? '');

// Fetch all events for filter dropdown
$all_events = mysqli_query($conn, "SELECT Event_ID, Event_Name FROM EVENT ORDER BY Event_Name ASC");

// Construct Dynamic SQL Query with JOINs
$sql = "SELECT 
            R.Registration_ID,
            R.Registration_Date,
            R.Status AS Reg_Status,
            P.Participant_ID,
            P.Roll_No,
            P.Name AS Student_Name,
            P.Email AS Student_Email,
            P.Phone_No AS Student_Phone,
            P.Semester,
            D.Department_Name,
            E.Event_ID,
            E.Event_Name,
            E.Registration_Fee,
            T.Team_Name,
            PAY.Transaction_ID,
            PAY.Amount AS Paid_Amount,
            PAY.Payment_Date
        FROM REGISTRATION R
        JOIN PARTICIPANT P ON R.Participant_ID = P.Participant_ID
        JOIN EVENT E ON R.Event_ID = E.Event_ID
        LEFT JOIN DEPARTMENT D ON P.Department_ID = D.Department_ID
        LEFT JOIN TEAM T ON R.Team_ID = T.Team_ID
        LEFT JOIN PAYMENT PAY ON R.Registration_ID = PAY.Registration_ID
        WHERE 1=1";

$params = [];
$types  = "";

if (!empty($search)) {
    $sql .= " AND (P.Name LIKE ? OR P.Roll_No LIKE ? OR P.Email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

if ($event_filter > 0) {
    $sql .= " AND R.Event_ID = ?";
    $params[] = $event_filter;
    $types .= "i";
}

if (!empty($status_filter)) {
    $sql .= " AND R.Status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$sql .= " ORDER BY R.Registration_Date DESC, R.Registration_ID DESC";

$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$registrations_res = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Registrations | Tech Fest Admin</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <!-- Top Navigation -->
    <nav class="navbar">
        <div class="container">
            <a href="dashboard.php" class="brand-logo">
                ⚡ INVICTUS <span class="brand-badge">ADMIN</span>
            </a>
            <div style="display: flex; align-items: center; gap: 16px;">
                <span style="font-size: 0.9rem; color: var(--text-muted);">
                    Logged in as: <strong style="color: var(--secondary-color);"><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?></strong>
                </span>
                <a href="index.html" target="_blank" class="btn btn-secondary btn-sm">View Website ↗</a>
                <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Admin Portal Layout -->
    <div class="admin-layout">
        <!-- Sidebar Navigation -->
        <aside class="admin-sidebar">
            <h4>Management Console</h4>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php">📊 Dashboard</a></li>
                <li><a href="manage_events.php">🎯 Manage Events</a></li>
                <li><a href="registrations.php" class="active">📝 Registrations</a></li>
                <li><a href="attendance.php">📋 Mark Attendance</a></li>
                <li><a href="results.php">🏆 Event Results</a></li>
                <li><a href="certificates.php">📜 Certificates</a></li>
            </ul>
        </aside>

        <!-- Main Content Area -->
        <main class="admin-content">
            <div class="admin-header">
                <div>
                    <h1>Student Registrations</h1>
                    <p style="color: var(--text-muted); font-size: 0.95rem;">Review student submissions, search records, and verify fees</p>
                </div>
            </div>

            <!-- Feedback Notifications -->
            <?php if (isset($_GET['msg'])): ?>
                <?php if ($_GET['msg'] === 'status_updated'): ?>
                    <div class="alert alert-success">✓ Registration status updated successfully.</div>
                <?php elseif ($_GET['msg'] === 'deleted'): ?>
                    <div class="alert alert-success">✓ Registration record removed from database.</div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Search & Filter Controls (Dynamic SQL WHERE demonstration) -->
            <div class="data-table-card" style="padding: 20px; margin-bottom: 25px;">
                <form action="registrations.php" method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">
                    <div style="flex: 2; min-width: 200px;">
                        <label style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 5px;">Search Student / Roll No</label>
                        <input type="text" name="search" class="form-control" placeholder="Search by name, roll no, or email..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>

                    <div style="flex: 1.5; min-width: 180px;">
                        <label style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 5px;">Filter by Event</label>
                        <select name="event_id" class="form-control">
                            <option value="0">All Events</option>
                            <?php 
                            mysqli_data_seek($all_events, 0);
                            while($ev = mysqli_fetch_assoc($all_events)): ?>
                                <option value="<?php echo $ev['Event_ID']; ?>" <?php echo $event_filter == $ev['Event_ID'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($ev['Event_Name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div style="flex: 1; min-width: 140px;">
                        <label style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 5px;">Status</label>
                        <select name="status" class="form-control">
                            <option value="">All Statuses</option>
                            <option value="Confirmed" <?php echo $status_filter === 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="Pending" <?php echo $status_filter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="Cancelled" <?php echo $status_filter === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>

                    <div>
                        <button type="submit" class="btn btn-primary" style="height: 42px;">Filter</button>
                        <a href="registrations.php" class="btn btn-secondary" style="height: 42px;">Reset</a>
                    </div>
                </form>
            </div>

            <!-- Registrations Table -->
            <div class="data-table-card">
                <div class="data-table-header">
                    <h3 style="font-size: 1.15rem; color: var(--secondary-color);">Registration Records</h3>
                    <span style="font-size: 0.85rem; color: var(--text-muted);">
                        Found <strong><?php echo mysqli_num_rows($registrations_res); ?></strong> registration(s)
                    </span>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Reg #</th>
                                <th>Student Details</th>
                                <th>Department</th>
                                <th>Registered Event</th>
                                <th>Team</th>
                                <th>Payment</th>
                                <th>Status</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($registrations_res && mysqli_num_rows($registrations_res) > 0): ?>
                                <?php while($row = mysqli_fetch_assoc($registrations_res)): ?>
                                    <tr>
                                        <td><strong>#<?php echo $row['Registration_ID']; ?></strong></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($row['Student_Name']); ?></strong><br>
                                            <small style="color: var(--text-muted);">Roll: <code><?php echo htmlspecialchars($row['Roll_No']); ?></code></small><br>
                                            <small style="color: var(--text-muted);"><?php echo htmlspecialchars($row['Student_Email']); ?> | <?php echo htmlspecialchars($row['Student_Phone']); ?></small>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($row['Department_Name'] ?? 'General'); ?><br>
                                            <small style="color: var(--text-muted);">Sem: <?php echo $row['Semester']; ?></small>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($row['Event_Name']); ?></strong><br>
                                            <small style="color: var(--text-muted);"><?php echo date('M d, Y', strtotime($row['Registration_Date'])); ?></small>
                                        </td>
                                        <td>
                                            <?php echo !empty($row['Team_Name']) ? '<span class="badge badge-info">' . htmlspecialchars($row['Team_Name']) . '</span>' : '<span style="color:var(--text-muted); font-size:0.85rem;">Individual</span>'; ?>
                                        </td>
                                        <td>
                                            <strong>₹<?php echo number_format($row['Paid_Amount'] ?? 0, 2); ?></strong><br>
                                            <small style="color: var(--text-muted);"><?php echo htmlspecialchars($row['Transaction_ID'] ?? 'N/A'); ?></small>
                                        </td>
                                        <td>
                                            <!-- Inline Status Form -->
                                            <form action="registrations.php" method="POST" style="display: inline-flex; align-items: center; gap: 6px;">
                                                <input type="hidden" name="update_status" value="1">
                                                <input type="hidden" name="registration_id" value="<?php echo $row['Registration_ID']; ?>">
                                                <select name="new_status" class="form-control" style="padding: 4px 8px; font-size: 0.82rem; width: auto;" onchange="this.form.submit()">
                                                    <option value="Confirmed" <?php echo $row['Reg_Status'] === 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                                    <option value="Pending" <?php echo $row['Reg_Status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="Cancelled" <?php echo $row['Reg_Status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                </select>
                                            </form>
                                        </td>
                                        <td style="text-align: right; white-space: nowrap;">
                                            <a href="attendance.php?search=<?php echo urlencode($row['Roll_No']); ?>" class="btn btn-secondary btn-sm" title="Mark Attendance">Attendance</a>
                                            <a href="registrations.php?action=delete&id=<?php echo $row['Registration_ID']; ?>" 
                                               class="btn btn-danger btn-sm" 
                                               onclick="return confirm('Are you sure you want to delete this registration?')">Delete</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 35px;">
                                        No registrations match your search criteria.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <script src="js/script.js"></script>
</body>
</html>
