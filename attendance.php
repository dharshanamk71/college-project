<?php
/**
 * Attendance Management: attendance.php
 * Demonstrates:
 * - Event-based participant filtering
 * - UPSERT (Insert or Update) logic on ATTENDANCE table
 * - JOIN queries connecting ATTENDANCE, REGISTRATION, PARTICIPANT, and EVENT
 */

require_once 'auth.php';
require_once 'db.php';

$selected_event = intval($_GET['event_id'] ?? 0);
$date_filter    = trim($_GET['date'] ?? date('Y-m-d'));
$search         = trim($_GET['search'] ?? '');

// Fetch all events for dropdown
$events_res = mysqli_query($conn, "SELECT Event_ID, Event_Name, Event_Date FROM EVENT ORDER BY Event_Date ASC");

// Fetch registrations along with current attendance state for the selected date
$query = "SELECT 
            R.Registration_ID,
            P.Participant_ID,
            P.Roll_No,
            P.Name AS Student_Name,
            P.Email AS Student_Email,
            E.Event_ID,
            E.Event_Name,
            A.Attendance_ID,
            A.Check_In_Time,
            A.Status AS Attendance_Status
          FROM REGISTRATION R
          JOIN PARTICIPANT P ON R.Participant_ID = P.Participant_ID
          JOIN EVENT E ON R.Event_ID = E.Event_ID
          LEFT JOIN ATTENDANCE A ON R.Registration_ID = A.Registration_ID
          WHERE R.Status = 'Confirmed'";

$params = [];
$types  = "";

if ($selected_event > 0) {
    $query .= " AND R.Event_ID = ?";
    $params[] = $selected_event;
    $types .= "i";
}

if (!empty($search)) {
    $query .= " AND (P.Name LIKE ? OR P.Roll_No LIKE ?)";
    $s_term = "%$search%";
    $params[] = $s_term;
    $params[] = $s_term;
    $types .= "ss";
}

$query .= " ORDER BY E.Event_Name ASC, P.Roll_No ASC";

$stmt = mysqli_prepare($conn, $query);

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$participants_res = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Attendance | Tech Fest Admin</title>
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
                <li><a href="registrations.php">📝 Registrations</a></li>
                <li><a href="attendance.php" class="active">📋 Mark Attendance</a></li>
                <li><a href="results.php">🏆 Event Results</a></li>
                <li><a href="certificates.php">📜 Certificates</a></li>
            </ul>
        </aside>

        <!-- Main Content Area -->
        <main class="admin-content">
            <div class="admin-header">
                <div>
                    <h1>Participant Attendance</h1>
                    <p style="color: var(--text-muted); font-size: 0.95rem;">Track attendance for registered students on the day of competitions</p>
                </div>
            </div>

            <!-- Notifications -->
            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'marked'): ?>
                <div class="alert alert-success">✓ Attendance record saved successfully.</div>
            <?php endif; ?>

            <!-- Filter Card -->
            <div class="data-table-card" style="padding: 20px; margin-bottom: 25px;">
                <form action="attendance.php" method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">
                    <div style="flex: 2; min-width: 200px;">
                        <label style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 5px;">Filter by Event</label>
                        <select name="event_id" class="form-control">
                            <option value="0">-- All Active Events --</option>
                            <?php 
                            mysqli_data_seek($events_res, 0);
                            while($e = mysqli_fetch_assoc($events_res)): ?>
                                <option value="<?php echo $e['Event_ID']; ?>" <?php echo $selected_event == $e['Event_ID'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($e['Event_Name'] . ' (' . $e['Event_Date'] . ')'); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div style="flex: 1.5; min-width: 150px;">
                        <label style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 5px;">Attendance Date</label>
                        <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($date_filter); ?>" required>
                    </div>

                    <div style="flex: 2; min-width: 180px;">
                        <label style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 5px;">Search Student</label>
                        <input type="text" name="search" class="form-control" placeholder="Roll No or Name..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>

                    <div>
                        <button type="submit" class="btn btn-primary" style="height: 42px;">Apply Filter</button>
                        <a href="attendance.php" class="btn btn-secondary" style="height: 42px;">Reset</a>
                    </div>
                </form>
            </div>

            <!-- Attendance Table -->
            <div class="data-table-card">
                <div class="data-table-header">
                    <h3 style="font-size: 1.15rem; color: var(--secondary-color);">
                        Attendance Sheet (Date: <?php echo date('M d, Y', strtotime($date_filter)); ?>)
                    </h3>
                    <span style="font-size: 0.85rem; color: var(--text-muted);">
                        Total Eligible: <strong><?php echo mysqli_num_rows($participants_res); ?></strong> participant(s)
                    </span>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Roll Number</th>
                                <th>Student Name</th>
                                <th>Registered Event</th>
                                <th>Attendance Status</th>
                                <th>Remarks</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($participants_res && mysqli_num_rows($participants_res) > 0): ?>
                                <?php while($row = mysqli_fetch_assoc($participants_res)): ?>
                                    <tr>
                                        <td><code><?php echo htmlspecialchars($row['Roll_No']); ?></code></td>
                                        <td><strong><?php echo htmlspecialchars($row['Student_Name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['Event_Name']); ?></td>
                                        <td>
                                            <?php if ($row['Attendance_Status'] === 'Present'): ?>
                                                <span class="badge badge-success">✓ Present</span>
                                            <?php elseif ($row['Attendance_Status'] === 'Absent'): ?>
                                                <span class="badge badge-danger">✗ Absent</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">⏳ Not Marked</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span style="font-size: 0.85rem; color: var(--text-muted);">
                                                <?php echo htmlspecialchars($row['Remarks'] ?? '—'); ?>
                                            </span>
                                        </td>
                                        <td style="text-align: right; white-space: nowrap;">
                                            <form action="attendance_process.php" method="POST" style="display: inline-flex; gap: 6px;">
                                                <input type="hidden" name="registration_id" value="<?php echo $row['Registration_ID']; ?>">
                                                <input type="hidden" name="date" value="<?php echo htmlspecialchars($date_filter); ?>">
                                                <input type="hidden" name="redirect_event" value="<?php echo $selected_event; ?>">
                                                
                                                <button type="submit" name="status" value="Present" class="btn btn-success btn-sm" <?php echo ($row['Attendance_Status'] === 'Present') ? 'disabled style="opacity:0.6;"' : ''; ?>>
                                                    Present
                                                </button>
                                                <button type="submit" name="status" value="Absent" class="btn btn-danger btn-sm" <?php echo ($row['Attendance_Status'] === 'Absent') ? 'disabled style="opacity:0.6;"' : ''; ?>>
                                                    Absent
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 35px;">
                                        No registered students found for the selected filter.
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
