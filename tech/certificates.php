<?php
/**
 * College Tech Fest Management System
 * Certificates Management
 */

require_once 'auth.php';
require_once 'db.php';

// --------------------------------------------------
// SIMULATE CERTIFICATE APPROVAL AND EMAIL STATUS
// No real email is sent.
// --------------------------------------------------
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['approve_certificate'])
) {
    $certificate_id = filter_input(
        INPUT_POST,
        'certificate_id',
        FILTER_VALIDATE_INT
    );

    if (!$certificate_id || $certificate_id < 1) {
        header("Location: certificates.php?msg=error");
        exit();
    }

    $update_sql = "UPDATE CERTIFICATE
                   SET Approval_Status = 'Approved',
                       Email_Status = 'Sent',
                       Emailed_At = NOW()
                   WHERE Certificate_ID = ?";

    $update_stmt = mysqli_prepare($conn, $update_sql);

    if (!$update_stmt) {
        header("Location: certificates.php?msg=error");
        exit();
    }

    mysqli_stmt_bind_param(
        $update_stmt,
        "i",
        $certificate_id
    );

    $executed = mysqli_stmt_execute($update_stmt);
    $affected = mysqli_stmt_affected_rows($update_stmt);

    mysqli_stmt_close($update_stmt);

    if ($executed && $affected >= 0) {
        header("Location: certificates.php?msg=email_sent");
    } else {
        header("Location: certificates.php?msg=error");
    }

    exit();
}


// --------------------------------------------------
// GENERATE CERTIFICATE
// --------------------------------------------------
if (isset($_GET['generate_for'])) {

    $target_result_id = filter_input(
        INPUT_GET,
        'generate_for',
        FILTER_VALIDATE_INT
    );

    if (!$target_result_id || $target_result_id < 1) {
        header("Location: certificates.php?msg=error");
        exit();
    }

    // Check whether a certificate already exists.
    $check_sql = "SELECT Certificate_ID
                  FROM CERTIFICATE
                  WHERE Result_ID = ?";

    $check_stmt = mysqli_prepare($conn, $check_sql);

    if (!$check_stmt) {
        header("Location: certificates.php?msg=error");
        exit();
    }

    mysqli_stmt_bind_param(
        $check_stmt,
        "i",
        $target_result_id
    );

    mysqli_stmt_execute($check_stmt);

    $check_result = mysqli_stmt_get_result($check_stmt);

    $certificate_exists = mysqli_num_rows($check_result) > 0;

    mysqli_stmt_close($check_stmt);

    // Generate a certificate only if one does not already exist.
    if (!$certificate_exists) {

        $cert_no = "CTF-2026-"
            . strtoupper(bin2hex(random_bytes(3)))
            . "-"
            . $target_result_id;

        $issue_date = date('Y-m-d');

        $insert_sql = "INSERT INTO CERTIFICATE
                       (
                           Result_ID,
                           Certificate_No,
                           Issue_Date
                       )
                       VALUES (?, ?, ?)";

        $insert_stmt = mysqli_prepare($conn, $insert_sql);

        if (!$insert_stmt) {
            header("Location: certificates.php?msg=error");
            exit();
        }

        mysqli_stmt_bind_param(
            $insert_stmt,
            "iss",
            $target_result_id,
            $cert_no,
            $issue_date
        );

        $insert_success = mysqli_stmt_execute($insert_stmt);

        mysqli_stmt_close($insert_stmt);

        if (!$insert_success) {
            header("Location: certificates.php?msg=error");
            exit();
        }
    }

    header("Location: certificates.php?msg=issued");
    exit();
}


// --------------------------------------------------
// VIEW OR PRINT A CERTIFICATE
// --------------------------------------------------
$view_cert_id = intval($_GET['print_id'] ?? 0);

$print_data = null;

if ($view_cert_id > 0) {

    $print_sql = "SELECT
                    C.Certificate_No,
                    C.Issue_Date,
                    RES.Position,
                    RES.Score,
                    P.Name AS Student_Name,
                    P.Roll_No,
                    D.Department_Name,
                    E.Event_Name,
                    COL.College_Name
                  FROM CERTIFICATE C
                  JOIN RESULT RES
                    ON C.Result_ID = RES.Result_ID
                  JOIN REGISTRATION R
                    ON RES.Registration_ID = R.Registration_ID
                  JOIN PARTICIPANT P
                    ON R.Participant_ID = P.Participant_ID
                  JOIN EVENT E
                    ON R.Event_ID = E.Event_ID
                  LEFT JOIN DEPARTMENT D
                    ON P.Department_ID = D.Department_ID
                  CROSS JOIN (
                      SELECT College_Name
                      FROM COLLEGE
                      LIMIT 1
                  ) COL
                  WHERE C.Certificate_ID = ?";

    $print_stmt = mysqli_prepare($conn, $print_sql);

    if ($print_stmt) {

        mysqli_stmt_bind_param(
            $print_stmt,
            "i",
            $view_cert_id
        );

        mysqli_stmt_execute($print_stmt);

        $print_result = mysqli_stmt_get_result($print_stmt);

        $print_data = mysqli_fetch_assoc($print_result);

        mysqli_stmt_close($print_stmt);
    }
}


// --------------------------------------------------
// SEARCH CERTIFICATES
// --------------------------------------------------
$search = trim($_GET['search'] ?? '');

$sql = "SELECT
            C.Certificate_ID,
            C.Certificate_No,
            C.Issue_Date,
            C.Approval_Status,
            C.Email_Status,
            RES.Result_ID,
            RES.Position,
            RES.Score,
            P.Participant_ID,
            P.Roll_No,
            P.Name AS Student_Name,
            P.Email AS Student_Email,
            E.Event_Name
        FROM CERTIFICATE C
        JOIN RESULT RES
            ON C.Result_ID = RES.Result_ID
        JOIN REGISTRATION R
            ON RES.Registration_ID = R.Registration_ID
        JOIN PARTICIPANT P
            ON R.Participant_ID = P.Participant_ID
        JOIN EVENT E
            ON R.Event_ID = E.Event_ID
        WHERE 1=1";

if ($search !== '') {

    $sql .= " AND (
                  P.Name LIKE ?
                  OR P.Roll_No LIKE ?
                  OR C.Certificate_No LIKE ?
              )";
}

$sql .= " ORDER BY C.Certificate_ID DESC";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die(
        "Certificate query failed. Check your database columns: "
        . htmlspecialchars(mysqli_error($conn))
    );
}

if ($search !== '') {

    $search_wildcard = "%" . $search . "%";

    mysqli_stmt_bind_param(
        $stmt,
        "sss",
        $search_wildcard,
        $search_wildcard,
        $search_wildcard
    );
}

mysqli_stmt_execute($stmt);

$certificates_res = mysqli_stmt_get_result($stmt);

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Certificates Portal | Tech Fest Admin</title>

    <link rel="stylesheet" href="css/style.css">

    <style>

        /* PRINT SETTINGS */

        @media print {

            body * {
                visibility: hidden;
            }

            .certificate-container,
            .certificate-container * {
                visibility: visible;
            }

            .certificate-container {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                margin: 0;
                box-shadow: none;
                border: 8px double #1e3a8a !important;
            }

            .no-print {
                display: none !important;
            }
        }


        /* CERTIFICATE DESIGN */

        .certificate-container {
            background: #ffffff;
            border: 12px double #1e3a8a;
            padding: 40px;
            text-align: center;
            border-radius: 8px;
            box-shadow: var(--shadow-lg);
            margin: 20px auto 40px;
            max-width: 800px;
            position: relative;
        }

        .cert-header {
            font-size: 1.1rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 5px;
        }

        .cert-title {
            font-size: 2.3rem;
            color: #1e3a8a;
            font-family: Georgia, serif;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .cert-body {
            font-size: 1.15rem;
            line-height: 1.8;
            color: #334155;
            margin-bottom: 30px;
        }

        .cert-name {
            font-size: 1.8rem;
            font-weight: 700;
            color: #0f172a;
            border-bottom: 2px solid #cbd5e1;
            display: inline-block;
            padding: 0 20px 4px;
            margin: 10px 0;
        }

        .cert-signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
            padding: 0 40px;
        }

        .signature-line {
            width: 180px;
            border-top: 1px solid #475569;
            padding-top: 6px;
            font-size: 0.9rem;
            color: #475569;
        }

        .status-text {
            display: block;
            margin-top: 8px;
            font-size: 12px;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }

    </style>

</head>

<body>

    <!-- TOP NAVIGATION -->

    <nav class="navbar no-print">

        <div class="container">

            <a href="dashboard.php" class="brand-logo">
                ⚡ INVICTUS
                <span class="brand-badge">ADMIN</span>
            </a>

            <div
                style="display:flex;align-items:center;gap:16px;"
            >

                <span
                    style="font-size:0.9rem;color:var(--text-muted);"
                >
                    Logged in as:

                    <strong style="color:var(--secondary-color);">
                        <?php
                        echo htmlspecialchars(
                            $_SESSION['admin_name'] ?? 'Admin'
                        );
                        ?>
                    </strong>
                </span>

                <a
                    href="index.html"
                    target="_blank"
                    class="btn btn-secondary btn-sm"
                >
                    View Website ↗
                </a>

                <a
                    href="logout.php"
                    class="btn btn-danger btn-sm"
                >
                    Logout
                </a>

            </div>

        </div>

    </nav>


    <!-- ADMIN LAYOUT -->

    <div class="admin-layout no-print">

        <!-- SIDEBAR -->

        <aside class="admin-sidebar">

            <h4>Management Console</h4>

            <ul class="sidebar-menu">

                <li>
                    <a href="dashboard.php">
                        📊 Dashboard
                    </a>
                </li>

                <li>
                    <a href="manage_events.php">
                        🎯 Manage Events
                    </a>
                </li>

                <li>
                    <a href="registrations.php">
                        📝 Registrations
                    </a>
                </li>

                <li>
                    <a href="attendance.php">
                        📋 Mark Attendance
                    </a>
                </li>

                <li>
                    <a href="results.php">
                        🏆 Event Results
                    </a>
                </li>

                <li>
                    <a href="certificates.php" class="active">
                        📜 Certificates
                    </a>
                </li>

            </ul>

        </aside>


        <!-- MAIN CONTENT -->

        <main class="admin-content">

            <div class="admin-header">

                <div>

                    <h1>Certificates Management</h1>

                    <p
                        style="color:var(--text-muted);font-size:0.95rem;"
                    >
                        Issue, search, verify, approve, and print
                        official fest achievement certificates.
                    </p>

                </div>

            </div>


            <!-- NOTIFICATION MESSAGES -->

            <?php if (isset($_GET['msg'])): ?>

                <?php if ($_GET['msg'] === 'issued'): ?>

                    <div class="success-message">
                        ✓ Certificate successfully generated and assigned.
                    </div>

                <?php elseif ($_GET['msg'] === 'email_sent'): ?>

                    <div class="success-message">
                        ✓ Certificate approved successfully!
                        Email marked as sent to the student.
                        <strong>
                            successfully;
        
                        </strong>
                    </div>

                <?php elseif ($_GET['msg'] === 'error'): ?>

                    <div class="error-message">
                        Unable to update certificate status.
                        Check that the required database columns exist.
                    </div>

                <?php endif; ?>

            <?php endif; ?>


            <!-- CERTIFICATE PREVIEW -->

            <?php if ($print_data): ?>

                <div
                    class="data-table-card"
                    style="padding:20px;margin-bottom:30px;background:#f8fafc;"
                >

                    <div
                        style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;"
                    >

                        <h3 style="color:var(--secondary-color);">
                            Certificate Preview
                        </h3>

                        <div>

                            <button
                                onclick="window.print()"
                                class="btn btn-primary btn-sm"
                            >
                                🖨️ Print Certificate
                            </button>

                            <a
                                href="certificates.php"
                                class="btn btn-secondary btn-sm"
                            >
                                Close Preview
                            </a>

                        </div>

                    </div>


                    <!-- PRINTABLE CERTIFICATE -->

                    <div class="certificate-container">

                        <div class="cert-header">

                            <?php
                            echo htmlspecialchars(
                                $print_data['College_Name']
                                ?? 'Apex Institute of Engineering & Technology'
                            );
                            ?>

                        </div>

                        <div
                            style="font-size:0.9rem;color:#64748b;margin-bottom:20px;"
                        >
                            Annual Technical Fest — INVICTUS 2026
                        </div>

                        <div class="cert-title">
                            Certificate of Achievement
                        </div>

                        <div class="cert-body">

                            This is to officially certify that<br>

                            <div class="cert-name">

                                <?php
                                echo htmlspecialchars(
                                    $print_data['Student_Name']
                                );
                                ?>

                            </div>

                            <br>

                            Roll Number:

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $print_data['Roll_No']
                                );
                                ?>
                            </strong>

                            &nbsp;|&nbsp;

                            Department:

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $print_data['Department_Name']
                                    ?? 'Engineering'
                                );
                                ?>
                            </strong>

                            <br>

                            has successfully secured

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $print_data['Position']
                                );
                                ?>
                            </strong>

                            (Score:

                            <?php
                            echo htmlspecialchars(
                                (string)$print_data['Score']
                            );
                            ?>/100)

                            <br>

                            in the technical event:

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $print_data['Event_Name']
                                );
                                ?>
                            </strong>.

                        </div>


                        <!-- SIGNATURES -->

                        <div class="cert-signatures">

                            <div class="signature-line">

                                <strong>Faculty Coordinator</strong>

                                <br>

                                Event Committee

                            </div>

                            <div>

                                <div
                                    style="font-size:0.8rem;color:#64748b;margin-bottom:4px;"
                                >
                                    Verified Certificate ID
                                </div>

                                <code>
                                    <?php
                                    echo htmlspecialchars(
                                        $print_data['Certificate_No']
                                    );
                                    ?>
                                </code>

                            </div>

                            <div class="signature-line">

                                <strong>Principal / Director</strong>

                                <br>

                                Apex Institute

                            </div>

                        </div>


                        <div
                            style="margin-top:25px;font-size:0.8rem;color:#94a3b8;"
                        >

                            Issued on:

                            <?php
                            echo date(
                                'F d, Y',
                                strtotime($print_data['Issue_Date'])
                            );
                            ?>

                        </div>

                    </div>

                </div>

            <?php endif; ?>


            <!-- SEARCH BAR -->

            <div
                class="data-table-card"
                style="padding:20px;margin-bottom:25px;"
            >

                <form
                    action="certificates.php"
                    method="GET"
                    style="display:flex;gap:15px;align-items:flex-end;"
                >

                    <div style="flex:1;">

                        <label
                            style="font-size:0.85rem;font-weight:600;display:block;margin-bottom:5px;"
                        >
                            Search by Name, Roll No, or Certificate ID
                        </label>

                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            placeholder="Search..."
                            value="<?php echo htmlspecialchars($search); ?>"
                        >

                    </div>

                    <div>

                        <button
                            type="submit"
                            class="btn btn-primary"
                            style="height:42px;"
                        >
                            Search
                        </button>

                        <a
                            href="certificates.php"
                            class="btn btn-secondary"
                            style="height:42px;"
                        >
                            Reset
                        </a>

                    </div>

                </form>

            </div>


            <!-- CERTIFICATES TABLE -->

            <div class="data-table-card">

                <div class="data-table-header">

                    <h3
                        style="font-size:1.15rem;color:var(--secondary-color);"
                    >
                        Issued Certificates (Unique Verification IDs)
                    </h3>

                    <span
                        style="font-size:0.85rem;color:var(--text-muted);"
                    >
                        Total Issued:

                        <strong>
                            <?php
                            echo mysqli_num_rows($certificates_res);
                            ?>
                        </strong>

                    </span>

                </div>


                <div class="table-responsive">

                    <table class="data-table">

                        <thead>

                            <tr>

                                <th>Certificate Number</th>

                                <th>Student Details</th>

                                <th>Event</th>

                                <th>Award / Position</th>

                                <th>Score</th>

                                <th>Issue Date</th>

                                <th style="text-align:right;">
                                    Actions
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php if (
                                $certificates_res
                                && mysqli_num_rows($certificates_res) > 0
                            ): ?>

                                <?php while (
                                    $row = mysqli_fetch_assoc($certificates_res)
                                ): ?>

                                    <tr>

                                        <!-- CERTIFICATE NUMBER -->

                                        <td>

                                            <strong
                                                style="color:var(--primary-color);"
                                            >
                                                📜

                                                <?php
                                                echo htmlspecialchars(
                                                    $row['Certificate_No']
                                                );
                                                ?>

                                            </strong>

                                        </td>


                                        <!-- STUDENT DETAILS -->

                                        <td>

                                            <strong>
                                                <?php
                                                echo htmlspecialchars(
                                                    $row['Student_Name']
                                                );
                                                ?>
                                            </strong>

                                            <br>

                                            <small
                                                style="color:var(--text-muted);"
                                            >
                                                Roll:

                                                <code>
                                                    <?php
                                                    echo htmlspecialchars(
                                                        $row['Roll_No']
                                                    );
                                                    ?>
                                                </code>
                                            </small>

                                        </td>


                                        <!-- EVENT -->

                                        <td>
                                            <?php
                                            echo htmlspecialchars(
                                                $row['Event_Name']
                                            );
                                            ?>
                                        </td>


                                        <!-- POSITION -->

                                        <td>

                                            <span class="badge badge-success">

                                                <?php
                                                echo htmlspecialchars(
                                                    $row['Position']
                                                );
                                                ?>

                                            </span>

                                        </td>


                                        <!-- SCORE -->

                                        <td>

                                            <strong>
                                                <?php
                                                echo number_format(
                                                    (float)$row['Score'],
                                                    2
                                                );
                                                ?>
                                            </strong>

                                        </td>


                                        <!-- ISSUE DATE -->

                                        <td>

                                            <?php
                                            echo date(
                                                'M d, Y',
                                                strtotime($row['Issue_Date'])
                                            );
                                            ?>

                                        </td>


                                        <!-- ACTIONS -->

                                        <td style="text-align:right;">

                                            <!-- VIEW AND PRINT -->

                                            <a
                                                href="certificates.php?print_id=<?php echo (int)$row['Certificate_ID']; ?>"
                                                class="btn btn-secondary btn-sm"
                                            >
                                                👁️ View / Print
                                            </a>


                                            <!-- SIMULATED EMAIL APPROVAL -->

                                            <form
                                                action="certificates.php"
                                                method="POST"
                                                style="margin-top:8px;"
                                                onsubmit="return confirm('Approve this certificate and mark the email as sent?');"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="certificate_id"
                                                    value="<?php echo (int)$row['Certificate_ID']; ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    name="approve_certificate"
                                                    value="1"
                                                    class="btn btn-primary btn-sm"
                                                >
                                                    Approve &amp; Send Email
                                                </button>

                                            </form>


                                            <!-- DISPLAY STATUS -->

                                            <small class="status-text">

                                                Approval:

                                                <?php
                                                echo htmlspecialchars(
                                                    $row['Approval_Status']
                                                    ?? 'Pending'
                                                );
                                                ?>

                                                <br>

                                                Email:

                                                <?php
                                                echo htmlspecialchars(
                                                    $row['Email_Status']
                                                    ?? 'Not Sent'
                                                );
                                                ?>

                                            </small>

                                        </td>

                                    </tr>

                                <?php endwhile; ?>

                            <?php else: ?>

                                <tr>

                                    <td
                                        colspan="7"
                                        style="text-align:center;color:var(--text-muted);padding:35px;"
                                    >
                                        No certificates found matching your search.
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