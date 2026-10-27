<?php
/**
 * Event Results Management: results.php
 * Demonstrates:
 * - Entering competition standings (Position, Score, Remarks)
 * - Joining RESULT with REGISTRATION, PARTICIPANT, and EVENT
 * - Managing result declarations and rankings
 */

require_once 'auth.php';
require_once 'db.php';

// Filter by Event
$event_filter = intval($_GET['event_id'] ?? 0);

// Fetch all events for dropdown
$events_list = mysqli_query($conn, "SELECT Event_ID, Event_Name FROM EVENT ORDER BY Event_Name ASC");

// Fetch confirmed participants for the dropdown to assign results
$candidates_sql = "SELECT 
                        R.Registration_ID,
                        P.Roll_No,
                        P.Name AS Student_Name,
                        E.Event_Name
                   FROM REGISTRATION R
                   JOIN PARTICIPANT P ON R.Participant_ID = P.Participant_ID
                   JOIN EVENT E ON R.Event_ID = E.Event_ID
                   WHERE R.Status = 'Confirmed'
                   ORDER BY E.Event_Name ASC, P.Name ASC";
$candidates_res = mysqli_query($conn, $candidates_sql);

// Fetch Declared Results
$results_sql = "SELECT 
                    RES.Result_ID,
                    RES.Position,
                    RES.Score,
                    RES.Result_Declared_Date,
                    RES.Remarks,
                    P.Roll_No,
                    P.Name AS Student_Name,
                    E.Event_ID,
                    E.Event_Name,
                    (SELECT Certificate_No FROM CERTIFICATE C WHERE C.Result_ID = RES.Result_ID LIMIT 1) AS Certificate_No
                FROM RESULT RES
                JOIN REGISTRATION R ON RES.Registration_ID = R.Registration_ID
                JOIN PARTICIPANT P ON R.Participant_ID = P.Participant_ID
                JOIN EVENT E ON R.Event_ID = E.Event_ID
                WHERE 1=1";

if ($event_filter > 0) {
    $results_sql .= " AND E.Event_ID = " . $event_filter;
}

$results_sql .= " ORDER BY E.Event_Name ASC, RES.Score DESC";
$results_res = mysqli_query($conn, $results_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Results | Tech Fest Admin</title>
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
                <li><a href="attendance.php">📋 Mark Attendance</a></li>
                <li><a href="results.php" class="active">🏆 Event Results</a></li>
                <li><a href="certificates.php">📜 Certificates</a></li>
            </ul>
        </aside>

        <!-- Main Content Area -->
        <main class="admin-content">
            <div class="admin-header">
                <div>
                    <h1>Competition Results</h1>
                    <p style="color: var(--text-muted); font-size: 0.95rem;">Declare winners, record evaluation scores, and publish rankings</p>
                </div>
            </div>

            <!-- Feedback Notifications -->
            <?php if (isset($_GET['msg'])): ?>
                <?php if ($_GET['msg'] === 'saved'): ?>
                    <div class="alert alert-success">✓ Result declared and saved successfully.</div>
                <?php elseif ($_GET['msg'] === 'deleted'): ?>
                    <div class="alert alert-success">✓ Result entry deleted.</div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger">⚠️ <?php echo htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>

            <!-- Form: Declare / Add Result -->
            <div class="data-table-card" style="padding: 25px; margin-bottom: 30px;">
                <h3 style="font-size: 1.2rem; color: var(--secondary-color); margin-bottom: 18px;">🏆 Declare New Event Result</h3>

                <form action="result_process.php" method="POST">
                    <input type="hidden" name="action" value="save">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="registration_id">Select Participant & Event *</label>
                            <select id="registration_id" name="registration_id" class="form-control" required>
                                <option value="">-- Choose Registered Participant --</option>
                                <?php 
                                mysqli_data_seek($candidates_res, 0);
                                while($c = mysqli_fetch_assoc($candidates_res)): ?>
                                    <option value="<?php echo $c['Registration_ID']; ?>">
                                        <?php echo htmlspecialchars($c['Roll_No'] . ' - ' . $c['Student_Name'] . ' (' . $c['Event_Name'] . ')'); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="position">Award / Position *</label>
                            <select id="position" name="position" class="form-control" required>
                                <option value="1st Place">🥇 1st Place (Winner)</option>
                                <option value="2nd Place">🥈 2nd Place (1st Runner Up)</option>
                                <option value="3rd Place">🥉 3rd Place (2nd Runner Up)</option>
                                <option value="Special Mention">🌟 Special Mention / Best Innovation</option>
                                <option value="Consolation Prize">🎖️ Consolation Prize</option>
                                <option value="Participant">📜 Participation Certification</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="score">Score / Points (out of 100) *</label>
                            <input type="number" step="0.01" min="0" max="100" id="score" name="score" class="form-control" placeholder="e.g. 95.5" required>
                        </div>
                        <div class="form-group">
                            <label for="result_date">Declaration Date *</label>
                            <input type="date" id="result_date" name="result_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="remarks">Evaluator Remarks / Project Note</label>
                        <input type="text" id="remarks" name="remarks" class="form-control" placeholder="e.g. Exceptional algorithmic efficiency and clean UI">
                    </div>

                    <div style="margin-top: 15px;">
                        <button type="submit" class="btn btn-primary" style="padding: 10px 24px;">Publish Result</button>
                    </div>
                </form>
            </div>

            <!-- Table of Declared Results -->
            <div class="data-table-card">
                <div class="data-table-header">
                    <h3 style="font-size: 1.15rem; color: var(--secondary-color);">Declared Results & Leaderboard</h3>
                    
                    <!-- Filter Dropdown -->
                    <form action="results.php" method="GET" style="display: flex; gap: 10px; align-items: center;">
                        <select name="event_id" class="form-control" style="width: auto; padding: 6px 12px;" onchange="this.form.submit()">
                            <option value="0">All Competitions</option>
                            <?php 
                            mysqli_data_seek($events_list, 0);
                            while($e = mysqli_fetch_assoc($events_list)): ?>
                                <option value="<?php echo $e['Event_ID']; ?>" <?php echo $event_filter == $e['Event_ID'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($e['Event_Name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Position</th>
                                <th>Student Details</th>
                                <th>Event</th>
                                <th>Score</th>
                                <th>Declared Date</th>
                                <th>Certificate</th>
                                <th>Remarks</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($results_res && mysqli_num_rows($results_res) > 0): ?>
                                <?php while($res = mysqli_fetch_assoc($results_res)): ?>
                                    <tr>
                                        <td>
                                            <?php if (strpos($res['Position'], '1st') !== false): ?>
                                                <span class="badge badge-success"><?php echo htmlspecialchars($res['Position']); ?></span>
                                            <?php elseif (strpos($res['Position'], '2nd') !== false): ?>
                                                <span class="badge badge-info"><?php echo htmlspecialchars($res['Position']); ?></span>
                                            <?php elseif (strpos($res['Position'], '3rd') !== false): ?>
                                                <span class="badge badge-warning"><?php echo htmlspecialchars($res['Position']); ?></span>
                                            <?php else: ?>
                                                <span class="badge" style="background:#e2e8f0; color:#334155;"><?php echo htmlspecialchars($res['Position']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($res['Student_Name']); ?></strong><br>
                                            <small style="color: var(--text-muted);">Roll: <code><?php echo htmlspecialchars($res['Roll_No']); ?></code></small>
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($res['Event_Name']); ?></strong></td>
                                        <td><strong style="color: var(--primary-color); font-size: 1.05rem;"><?php echo number_format($res['Score'], 2); ?></strong></td>
                                        <td><?php echo date('M d, Y', strtotime($res['Result_Declared_Date'])); ?></td>
                                        <td>
                                            <?php if (!empty($res['Certificate_No'])): ?>
                                                <a href="certificates.php?search=<?php echo urlencode($res['Roll_No']); ?>" style="font-size: 0.85rem; font-weight: 600;">
                                                    📜 <?php echo htmlspecialchars($res['Certificate_No']); ?>
                                                </a>
                                            <?php else: ?>
                                                <a href="certificates.php?generate_for=<?php echo $res['Result_ID']; ?>" class="btn btn-secondary btn-sm" style="font-size: 0.75rem; padding: 3px 8px;">
                                                    Issue Certificate
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span style="font-size: 0.85rem; color: var(--text-muted);">
                                                <?php echo htmlspecialchars($res['Remarks'] ?? '—'); ?>
                                            </span>
                                        </td>
                                        <td style="text-align: right;">
                                            <a href="result_process.php?action=delete&result_id=<?php echo $res['Result_ID']; ?>" 
                                               class="btn btn-danger btn-sm" 
                                               onclick="return confirm('Are you sure you want to delete this result entry?')">Delete</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 35px;">
                                        No results declared yet for this competition.
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
