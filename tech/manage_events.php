<?php
/**
 * Event Management: manage_events.php
 * Demonstrates CRUD (Create, Read, Update, Delete) operations on EVENT table.
 * Demonstrates Foreign Key relationships to VENUE and COORDINATOR.
 */

require_once 'auth.php';
require_once 'db.php';

// Check if admin is editing an existing event
$is_edit = false;
$edit_data = [
    'Event_ID' => '',
    'Event_Name' => '',
    'Description' => '',
    'Event_Type' => 'Coding',
    'Team_Size' => 1,
    'Max_Participants' => 50,
    'Registration_Fee' => '0.00',
    'Event_Date' => '',
    'Last_Date_to_Register' => '',
    'Coordinator_ID' => '',
    'Venue_ID' => ''
];

if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $stmt = mysqli_prepare($conn, "SELECT * FROM EVENT WHERE Event_ID = ?");
    mysqli_stmt_bind_param($stmt, "i", $edit_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($res)) {
        $is_edit = true;
        $edit_data = $row;
    }
    mysqli_stmt_close($stmt);
}

// Fetch all venues for dropdown
$venues_res = mysqli_query($conn, "SELECT Venue_ID, Venue_Name, Location FROM VENUE ORDER BY Venue_Name ASC");

// Fetch all coordinators for dropdown
$coordinators_res = mysqli_query($conn, "SELECT Coordinator_ID, Name, Department FROM COORDINATOR ORDER BY Name ASC");

// Fetch all events
$events_sql = "SELECT 
                    E.*,
                    (SELECT COUNT(*) 
                     FROM REGISTRATION R 
                     WHERE R.Event_ID = E.Event_ID) AS Total_Registered
               FROM EVENT E
               ORDER BY E.Event_Date ASC, E.Event_ID DESC";
$events_res = mysqli_query($conn, $events_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events | Tech Fest Admin</title>
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
                <li><a href="manage_events.php" class="active">🎯 Manage Events</a></li>
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
                    <h1>Event Management (CRUD)</h1>
                    <p style="color: var(--text-muted); font-size: 0.95rem;">Create, update, view, or remove college tech fest competitions</p>
                </div>
            </div>

            <!-- Feedback Messages -->
            <?php if (isset($_GET['msg'])): ?>
                <?php if ($_GET['msg'] === 'added'): ?>
                    <div class="alert alert-success">✓ New event added successfully to MySQL database.</div>
                <?php elseif ($_GET['msg'] === 'updated'): ?>
                    <div class="alert alert-success">✓ Event details updated successfully.</div>
                <?php elseif ($_GET['msg'] === 'deleted'): ?>
                    <div class="alert alert-success">✓ Event and associated records deleted successfully.</div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger">⚠️ <?php echo htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>

            <!-- Form: Add / Edit Event -->
            <div class="data-table-card" style="margin-bottom: 35px; padding: 25px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="font-size: 1.25rem; color: var(--secondary-color);">
                        <?php echo $is_edit ? '✏️ Edit Event Details (ID #' . $edit_data['Event_ID'] . ')' : '➕ Add New Technical Event'; ?>
                    </h3>
                    <?php if ($is_edit): ?>
                        <a href="manage_events.php" class="btn btn-secondary btn-sm">Cancel Edit</a>
                    <?php endif; ?>
                </div>

                <form action="event_process.php" method="POST">
                    <input type="hidden" name="action" value="<?php echo $is_edit ? 'update' : 'create'; ?>">
                    <?php if ($is_edit): ?>
                        <input type="hidden" name="event_id" value="<?php echo $edit_data['Event_ID']; ?>">
                    <?php endif; ?>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="event_name">Event Name *</label>
                            <input type="text" id="event_name" name="event_name" class="form-control" value="<?php echo htmlspecialchars($edit_data['Event_Name']); ?>" placeholder="e.g. CodeSprint Hackathon" required>
                        </div>
                        <div class="form-group">
                            <label for="event_type">Category / Type *</label>
                            <select id="event_type" name="event_type" class="form-control" required>
                                <option value="Coding" <?php echo $edit_data['Event_Type'] === 'Coding' ? 'selected' : ''; ?>>Coding</option>
                                <option value="Web Design" <?php echo $edit_data['Event_Type'] === 'Web Design' ? 'selected' : ''; ?>>Web Design</option>
                                <option value="Debugging" <?php echo $edit_data['Event_Type'] === 'Debugging' ? 'selected' : ''; ?>>Debugging</option>
                                <option value="Robotics" <?php echo $edit_data['Event_Type'] === 'Robotics' ? 'selected' : ''; ?>>Robotics</option>
                                <option value="Quiz" <?php echo $edit_data['Event_Type'] === 'Quiz' ? 'selected' : ''; ?>>Quiz</option>
                                <option value="Gaming" <?php echo $edit_data['Event_Type'] === 'Gaming' ? 'selected' : ''; ?>>Gaming</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">Event Description *</label>
                        <textarea id="description" name="description" class="form-control" rows="3" placeholder="Provide problem statement guidelines and competition rules..." required><?php echo htmlspecialchars($edit_data['Description']); ?></textarea>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px;">
                        <div class="form-group">
                            <label for="team_size">Team Size (Max Members) *</label>
                            <input type="number" id="team_size" name="team_size" class="form-control" min="1" max="10" value="<?php echo htmlspecialchars($edit_data['Team_Size']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="max_participants">Participant Capacity *</label>
                            <input type="number" id="max_participants" name="max_participants" class="form-control" min="5" max="500" value="<?php echo htmlspecialchars($edit_data['Max_Participants']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="registration_fee">Registration Fee (₹) *</label>
                            <input type="number" step="0.01" id="registration_fee" name="registration_fee" class="form-control" min="0" value="<?php echo htmlspecialchars($edit_data['Registration_Fee']); ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="event_date">Event Date *</label>
                            <input type="date" id="event_date" name="event_date" class="form-control" value="<?php echo htmlspecialchars($edit_data['Event_Date']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="last_date">Registration Deadline *</label>
                            <input type="date" id="last_date" name="last_date" class="form-control" value="<?php echo htmlspecialchars($edit_data['Last_Date_to_Register']); ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="venue_id">Assign Venue (Foreign Key)</label>
                            <select id="venue_id" name="venue_id" class="form-control">
                                <option value="">-- Select Venue --</option>
                                <?php 
                                mysqli_data_seek($venues_res, 0);
                                while($v = mysqli_fetch_assoc($venues_res)): 
                                ?>
                                    <option value="<?php echo $v['Venue_ID']; ?>" <?php echo $edit_data['Venue_ID'] == $v['Venue_ID'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($v['Venue_Name'] . ' (' . $v['Location'] . ')'); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="coordinator_id">Assign Faculty Coordinator (Foreign Key)</label>
                            <select id="coordinator_id" name="coordinator_id" class="form-control">
                                <option value="">-- Select Coordinator --</option>
                                <?php 
                                mysqli_data_seek($coordinators_res, 0);
                                while($c = mysqli_fetch_assoc($coordinators_res)): 
                                ?>
                                    <option value="<?php echo $c['Coordinator_ID']; ?>" <?php echo $edit_data['Coordinator_ID'] == $c['Coordinator_ID'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($c['Name'] . ' - ' . $c['Department']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <div style="margin-top: 15px;">
                        <button type="submit" class="btn btn-primary" style="padding: 10px 24px;">
                            <?php echo $is_edit ? '💾 Update Event' : '➕ Save Event'; ?>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Table: View All Events -->
            <div class="data-table-card">
                <div class="data-table-header">
                    <h3 style="font-size: 1.15rem; color: var(--secondary-color);">All Technical Events (MySQL Records)</h3>
                    <span style="font-size: 0.85rem; color: var(--text-muted);">Total: <?php echo mysqli_num_rows($events_res); ?> events</span>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Event Name</th>
                                <th>Category</th>
                                <th>Date</th>
                                <th>Fee</th>
                                <th>Capacity</th>
                                <th>Booked</th>
                                <th>Venue</th>
                                <th>Coordinator</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($events_res && mysqli_num_rows($events_res) > 0): ?>
                                <?php while($evt = mysqli_fetch_assoc($events_res)): ?>
                                    <tr>
                                        <td><strong>#<?php echo $evt['Event_ID']; ?></strong></td>
                                        <td><strong><?php echo htmlspecialchars($evt['Event_Name']); ?></strong></td>
                                        <td><span class="badge badge-info"><?php echo htmlspecialchars($evt['Event_Type']); ?></span></td>
                                        <td><?php echo $evt['Event_Date']; ?></td>
                                        <td>₹<?php echo number_format($evt['Registration_Fee'], 2); ?></td>
                                        <td><?php echo $evt['Max_Participants']; ?></td>
                                        <td>
                                            <span class="badge <?php echo $evt['Total_Registered'] >= $evt['Max_Participants'] ? 'badge-danger' : 'badge-success'; ?>">
                                                <?php echo $evt['Total_Registered']; ?> / <?php echo $evt['Max_Participants']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($evt['Venue_Name'] ?? 'Unassigned'); ?></td>
                                        <td><?php echo htmlspecialchars($evt['Coordinator_Name'] ?? 'Unassigned'); ?></td>
                                        <td style="text-align: right; white-space: nowrap;">
                                            <a href="manage_events.php?edit_id=<?php echo $evt['Event_ID']; ?>" class="btn btn-secondary btn-sm">Edit</a>
                                            <a href="event_process.php?action=delete&event_id=<?php echo $evt['Event_ID']; ?>" 
                                               class="btn btn-danger btn-sm" 
                                               onclick="return confirm('Are you sure you want to delete this event? All related registrations, schedules, and results will also be deleted.')">Delete</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" style="text-align: center; color: var(--text-muted); padding: 30px;">No events registered in database.</td>
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
