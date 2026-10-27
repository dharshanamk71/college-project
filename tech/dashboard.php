<?php
/**
 * Admin Dashboard: dashboard.php
 * Demonstrates SQL Aggregation Functions: COUNT, SUM
 * Demonstrates Multi-table JOIN queries
 */

require_once 'auth.php';
require_once 'db.php';

// 1. Fetch Aggregated Statistics
// Total Students (Participants)
$q_students = mysqli_query($conn, "SELECT COUNT(*) AS total_students FROM PARTICIPANT");
$total_students = mysqli_fetch_assoc($q_students)['total_students'] ?? 0;

// Total Events
$q_events = mysqli_query($conn, "SELECT COUNT(*) AS total_events FROM EVENT");
$total_events = mysqli_fetch_assoc($q_events)['total_events'] ?? 0;

// Total Registrations
$q_reg = mysqli_query($conn, "SELECT COUNT(*) AS total_registrations FROM REGISTRATION");
$total_registrations = mysqli_fetch_assoc($q_reg)['total_registrations'] ?? 0;

// Total Revenue (SUM from PAYMENT)
$q_rev = mysqli_query($conn, "SELECT SUM(Amount) AS total_revenue FROM PAYMENT");
$total_revenue = mysqli_fetch_assoc($q_rev)['total_revenue'] ?? 0.00;

// Total Attendance Marked
$q_att = mysqli_query($conn, "SELECT COUNT(*) AS total_present FROM ATTENDANCE WHERE Status = 'Present'");
$total_present = mysqli_fetch_assoc($q_att)['total_present'] ?? 0;

// 2. Fetch Recent Registrations with JOIN
$recent_sql = "SELECT 
                    R.Registration_ID,
                    R.Registration_Date,
                    R.Status AS Reg_Status,
                    P.Name AS Student_Name,
                    P.Roll_No,
                    E.Event_Name,
                    E.Event_Type,
                    PAY.Amount,
                    PAY.Transaction_ID
               FROM REGISTRATION R
               JOIN PARTICIPANT P ON R.Participant_ID = P.Participant_ID
               JOIN EVENT E ON R.Event_ID = E.Event_ID
               LEFT JOIN PAYMENT PAY ON R.Registration_ID = PAY.Registration_ID
               ORDER BY R.Registration_Date DESC
               LIMIT 6";
$recent_res = mysqli_query($conn, $recent_sql);

// 3. Fetch Event-wise Registration Counts (GROUP BY demonstration)
$event_counts_sql = "SELECT 
                        E.Event_Name,
                        E.Max_Participants,
                        COUNT(R.Registration_ID) AS Reg_Count
                     FROM EVENT E
                     LEFT JOIN REGISTRATION R ON E.Event_ID = R.Event_ID
                     GROUP BY E.Event_ID, E.Event_Name, E.Max_Participants
                     ORDER BY Reg_Count DESC";
$event_counts_res = mysqli_query($conn, $event_counts_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Tech Fest Portal</title>
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
                <li><a href="dashboard.php" class="active">📊 Dashboard</a></li>
                <li><a href="manage_events.php">🎯 Manage Events</a></li>
                <li><a href="registrations.php">📝 Registrations</a></li>
                <li><a href="attendance.php">📋 Mark Attendance</a></li>
                <li><a href="results.php">🏆 Event Results</a></li>
                <li><a href="certificates.php">📜 Certificates</a></li>
            </ul>
        </aside>

        <!-- Main Content Area -->
        <main class="admin-content">
            <div class="admin-header">
                <div>
                    <h1>Fest Management Overview</h1>
                    <p style="color: var(--text-muted); font-size: 0.95rem;">Live system metrics, SQL aggregations, and recent student activities</p>
                </div>
                <div>
                    <a href="manage_events.php" class="btn btn-primary">+ Add New Event</a>
                </div>
            </div>

            <!-- Stats Metric Cards -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px;">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_students; ?></div>
                    <div class="stat-label">Registered Students</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_events; ?></div>
                    <div class="stat-label">Active Events</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_registrations; ?></div>
                    <div class="stat-label">Total Bookings</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="color: var(--success-color);">₹<?php echo number_format($total_revenue, 2); ?></div>
                    <div class="stat-label">Fee Collection</div>
                </div>
            </div>

            <!-- Event Fill Rate Overview (GROUP BY demonstration) -->
            <div class="data-table-card" style="margin-bottom: 30px;">
                <div class="data-table-header">
                    <h3 style="font-size: 1.15rem; color: var(--secondary-color);">Event Capacity & Fill Rate (SQL GROUP BY)</h3>
                    <span style="font-size: 0.85rem; color: var(--text-muted);">Real-time participant counts</span>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Event Name</th>
                                <th>Registered</th>
                                <th>Max Capacity</th>
                                <th>Fill Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($event_counts_res && mysqli_num_rows($event_counts_res) > 0): ?>
                                <?php while($ec = mysqli_fetch_assoc($event_counts_res)): 
                                    $pct = $ec['Max_Participants'] > 0 ? round(($ec['Reg_Count'] / $ec['Max_Participants']) * 100) : 0;
                                ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($ec['Event_Name']); ?></strong></td>
                                        <td><?php echo $ec['Reg_Count']; ?></td>
                                        <td><?php echo $ec['Max_Participants']; ?></td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 10px;">
                                                <div style="flex: 1; background: #e2e8f0; height: 8px; border-radius: 4px; overflow: hidden; min-width: 100px;">
                                                    <div style="background: var(--primary-color); height: 100%; width: <?php echo min(100, $pct); ?>%;"></div>
                                                </div>
                                                <span style="font-size: 0.85rem; font-weight: 600; width: 40px;"><?php echo $pct; ?>%</span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; color: var(--text-muted);">No events found in database.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Registrations Table (SQL JOIN demonstration) -->
            <div class="data-table-card">
                <div class="data-table-header">
                    <h3 style="font-size: 1.15rem; color: var(--secondary-color);">Recent Student Registrations (SQL JOIN)</h3>
                    <a href="registrations.php" class="btn btn-secondary btn-sm">View All Registrations →</a>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Reg ID</th>
                                <th>Student Name</th>
                                <th>Roll Number</th>
                                <th>Event</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Registered Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recent_res && mysqli_num_rows($recent_res) > 0): ?>
                                <?php while($reg = mysqli_fetch_assoc($recent_res)): ?>
                                    <tr>
                                        <td><strong>#<?php echo $reg['Registration_ID']; ?></strong></td>
                                        <td><?php echo htmlspecialchars($reg['Student_Name']); ?></td>
                                        <td><code><?php echo htmlspecialchars($reg['Roll_No']); ?></code></td>
                                        <td><?php echo htmlspecialchars($reg['Event_Name']); ?></td>
                                        <td>₹<?php echo number_format($reg['Amount'] ?? 0, 2); ?></td>
                                        <td><span class="badge badge-success"><?php echo htmlspecialchars($reg['Reg_Status']); ?></span></td>
                                        <td><?php echo date('M d, Y h:i A', strtotime($reg['Registration_Date'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 30px;">No registrations recorded yet.</td>
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
