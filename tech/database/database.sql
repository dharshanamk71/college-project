-- =======================================================
-- Database Name: CollegeTechFestDB
-- Project Title: College Tech Fest Management System
-- DBMS Concepts: PK, FK, UNIQUE, Cascading, Normalization
-- =======================================================

-- 1. Create Database if not exists
CREATE DATABASE IF NOT EXISTS CollegeTechFestDB;
USE CollegeTechFestDB;

-- Disable foreign key checks during table drop/creation
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS CERTIFICATE;
DROP TABLE IF EXISTS RESULT;
DROP TABLE IF EXISTS ATTENDANCE;
DROP TABLE IF EXISTS PAYMENT;
DROP TABLE IF EXISTS EVENT_SCHEDULE;
DROP TABLE IF EXISTS REGISTRATION;
DROP TABLE IF EXISTS TEAM;
DROP TABLE IF EXISTS EVENT;
DROP TABLE IF EXISTS VENUE;
DROP TABLE IF EXISTS COORDINATOR;
DROP TABLE IF EXISTS PARTICIPANT;
DROP TABLE IF EXISTS DEPARTMENT;
DROP TABLE IF EXISTS COLLEGE;
DROP TABLE IF EXISTS ADMIN;

SET FOREIGN_KEY_CHECKS = 1;

-- =======================================================
-- TABLE 1: COLLEGE
-- Represents the hosting institution.
-- =======================================================
CREATE TABLE COLLEGE (
    College_ID INT AUTO_INCREMENT PRIMARY KEY,
    College_Name VARCHAR(150) NOT NULL,
    Address VARCHAR(255) NOT NULL,
    Email VARCHAR(100) NOT NULL,
    Phone_No VARCHAR(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =======================================================
-- TABLE 2: DEPARTMENT
-- Represents academic departments within the college.
-- Demonstrates 1:N relationship (College -> Departments)
-- =======================================================
CREATE TABLE DEPARTMENT (
    Department_ID INT AUTO_INCREMENT PRIMARY KEY,
    Department_Name VARCHAR(100) NOT NULL,
    Location VARCHAR(100) NOT NULL,
    College_ID INT NOT NULL,
    FOREIGN KEY (College_ID) REFERENCES COLLEGE(College_ID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =======================================================
-- TABLE 3: PARTICIPANT (Student)
-- Holds student details. Demonstrates UNIQUE constraint on Roll_No.
-- =======================================================
CREATE TABLE PARTICIPANT (
    Participant_ID INT AUTO_INCREMENT PRIMARY KEY,
    Roll_No VARCHAR(50) NOT NULL UNIQUE,
    Name VARCHAR(100) NOT NULL,
    Email VARCHAR(100) NOT NULL,
    Phone_No VARCHAR(20) NOT NULL,
    Gender ENUM('Male', 'Female', 'Other') DEFAULT 'Male',
    Address VARCHAR(255),
    Semester INT NOT NULL,
    Year INT NOT NULL,
    Department_ID INT,
    FOREIGN KEY (Department_ID) REFERENCES DEPARTMENT(Department_ID) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =======================================================
-- TABLE 4: COORDINATOR
-- Faculty or student coordinators managing events.
-- =======================================================
CREATE TABLE COORDINATOR (
    Coordinator_ID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100) NOT NULL,
    Email VARCHAR(100) NOT NULL,
    Phone_No VARCHAR(20) NOT NULL,
    Department VARCHAR(100) NOT NULL,
    College_ID INT NOT NULL,
    FOREIGN KEY (College_ID) REFERENCES COLLEGE(College_ID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =======================================================
-- TABLE 5: VENUE
-- Rooms, auditoriums, and labs where events take place.
-- =======================================================
CREATE TABLE VENUE (
    Venue_ID INT AUTO_INCREMENT PRIMARY KEY,
    Venue_Name VARCHAR(100) NOT NULL,
    Location VARCHAR(100) NOT NULL,
    Capacity INT NOT NULL,
    Facilities TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =======================================================
-- TABLE 6: EVENT
-- Technical fest events like Hackathons, Coding contests, etc.
-- =======================================================
CREATE TABLE EVENT (
    Event_ID INT AUTO_INCREMENT PRIMARY KEY,
    Event_Name VARCHAR(100) NOT NULL,
    Description TEXT NOT NULL,
    Event_Type VARCHAR(50) NOT NULL, -- e.g., 'Coding', 'Web Dev', 'Robotics', 'Quiz', 'Gaming'
    Team_Size INT DEFAULT 1,
    Max_Participants INT NOT NULL,
    Registration_Fee DECIMAL(10, 2) DEFAULT 0.00,
    Event_Date DATE NOT NULL,
    Last_Date_to_Register DATE NOT NULL,
    Coordinator_ID INT,
    Venue_ID INT,
    FOREIGN KEY (Coordinator_ID) REFERENCES COORDINATOR(Coordinator_ID) ON DELETE SET NULL,
    FOREIGN KEY (Venue_ID) REFERENCES VENUE(Venue_ID) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =======================================================
-- TABLE 7: TEAM
-- Group of participants for team events.
-- =======================================================
CREATE TABLE TEAM (
    Team_ID INT AUTO_INCREMENT PRIMARY KEY,
    Team_Name VARCHAR(100) NOT NULL,
    Team_Leader_ID INT NOT NULL,
    Team_Size INT DEFAULT 1,
    FOREIGN KEY (Team_Leader_ID) REFERENCES PARTICIPANT(Participant_ID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =======================================================
-- TABLE 8: REGISTRATION
-- M:N relationship between PARTICIPANT and EVENT.
-- Demonstrates composite UNIQUE constraint (Participant_ID, Event_ID)
-- =======================================================
CREATE TABLE REGISTRATION (
    Registration_ID INT AUTO_INCREMENT PRIMARY KEY,
    Participant_ID INT NOT NULL,
    Event_ID INT NOT NULL,
    Team_ID INT NULL,
    Registration_Date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Status VARCHAR(30) DEFAULT 'Confirmed', -- 'Pending', 'Confirmed', 'Cancelled'
    UNIQUE KEY unique_participant_event (Participant_ID, Event_ID),
    FOREIGN KEY (Participant_ID) REFERENCES PARTICIPANT(Participant_ID) ON DELETE CASCADE,
    FOREIGN KEY (Event_ID) REFERENCES EVENT(Event_ID) ON DELETE CASCADE,
    FOREIGN KEY (Team_ID) REFERENCES TEAM(Team_ID) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =======================================================
-- TABLE 9: EVENT_SCHEDULE
-- Specific timing and venue slots for each event.
-- =======================================================
CREATE TABLE EVENT_SCHEDULE (
    Schedule_ID INT AUTO_INCREMENT PRIMARY KEY,
    Event_ID INT NOT NULL,
    Event_Date DATE NOT NULL,
    Start_Time TIME NOT NULL,
    End_Time TIME NOT NULL,
    Venue_ID INT,
    FOREIGN KEY (Event_ID) REFERENCES EVENT(Event_ID) ON DELETE CASCADE,
    FOREIGN KEY (Venue_ID) REFERENCES VENUE(Venue_ID) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =======================================================
-- TABLE 10: PAYMENT
-- Tracks fee payments for event registrations.
-- Demonstrates 1:1 or 1:N with REGISTRATION and UNIQUE Transaction_ID
-- =======================================================
CREATE TABLE PAYMENT (
    Payment_ID INT AUTO_INCREMENT PRIMARY KEY,
    Registration_ID INT NOT NULL,
    Amount DECIMAL(10, 2) NOT NULL,
    Payment_Date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Payment_Mode VARCHAR(50) DEFAULT 'Online UPI',
    Transaction_ID VARCHAR(100) NOT NULL UNIQUE,
    FOREIGN KEY (Registration_ID) REFERENCES REGISTRATION(Registration_ID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =======================================================
-- TABLE 11: ATTENDANCE
-- Tracks participant attendance on the day of event.
-- =======================================================
CREATE TABLE ATTENDANCE (
    Attendance_ID INT AUTO_INCREMENT PRIMARY KEY,
    Registration_ID INT NOT NULL,
    Date DATE NOT NULL,
    Status ENUM('Present', 'Absent') DEFAULT 'Present',
    Remarks VARCHAR(255) DEFAULT 'Marked by coordinator',
    FOREIGN KEY (Registration_ID) REFERENCES REGISTRATION(Registration_ID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =======================================================
-- TABLE 12: RESULT
-- Stores competition rankings and scores.
-- =======================================================
CREATE TABLE RESULT (
    Result_ID INT AUTO_INCREMENT PRIMARY KEY,
    Registration_ID INT NOT NULL,
    Position VARCHAR(50) NOT NULL, -- '1st Place', '2nd Place', '3rd Place', 'Special Mention'
    Score DECIMAL(6, 2) NOT NULL,
    Result_Declared_Date DATE NOT NULL,
    Remarks VARCHAR(255),
    FOREIGN KEY (Registration_ID) REFERENCES REGISTRATION(Registration_ID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =======================================================
-- TABLE 13: CERTIFICATE
-- Stores certificate issuance details linked to results.
-- Demonstrates UNIQUE Certificate_No.
-- =======================================================
CREATE TABLE CERTIFICATE (
    Certificate_ID INT AUTO_INCREMENT PRIMARY KEY,
    Result_ID INT NOT NULL,
    Certificate_No VARCHAR(100) NOT NULL UNIQUE,
    Issue_Date DATE NOT NULL,
    FOREIGN KEY (Result_ID) REFERENCES RESULT(Result_ID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =======================================================
-- TABLE 14: ADMIN
-- Administrator accounts for Fest Management Portal.
-- =======================================================
CREATE TABLE ADMIN (
    Admin_ID INT AUTO_INCREMENT PRIMARY KEY,
    Username VARCHAR(50) NOT NULL UNIQUE,
    Password VARCHAR(255) NOT NULL,
    Full_Name VARCHAR(100) NOT NULL,
    Role VARCHAR(50) DEFAULT 'Event Administrator'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =======================================================
-- SAMPLE DATA INSERTION
-- =======================================================

-- 1. Insert College
INSERT INTO COLLEGE (College_ID, College_Name, Address, Email, Phone_No) VALUES
(1, 'Apex Institute of Engineering & Technology', 'Campus Road, Knowledge Park, City', 'info@apextech.edu', '+91 9876543210');

-- 2. Insert Departments
INSERT INTO DEPARTMENT (Department_ID, Department_Name, Location, College_ID) VALUES
(1, 'Computer Science & Engineering', 'Block A, 3rd Floor', 1),
(2, 'Information Technology', 'Block A, 2nd Floor', 1),
(3, 'Electronics & Communication', 'Block B, 1st Floor', 1),
(4, 'Mechanical Engineering', 'Block C, Ground Floor', 1),
(5, 'Civil Engineering', 'Block C, 2nd Floor', 1);

-- 3. Insert Coordinators
INSERT INTO COORDINATOR (Coordinator_ID, Name, Email, Phone_No, Department, College_ID) VALUES
(1, 'Dr. Rajesh Sharma', 'rajesh.sharma@apextech.edu', '9811223344', 'Computer Science & Engineering', 1),
(2, 'Prof. Sunita Menon', 'sunita.menon@apextech.edu', '9822334455', 'Information Technology', 1),
(3, 'Dr. Amit Patel', 'amit.patel@apextech.edu', '9833445566', 'Electronics & Communication', 1),
(4, 'Prof. Neha Gupta', 'neha.gupta@apextech.edu', '9844556677', 'Computer Science & Engineering', 1);

-- 4. Insert Venues
INSERT INTO VENUE (Venue_ID, Venue_Name, Location, Capacity, Facilities) VALUES
(1, 'Dr. APJ Abdul Kalam Auditorium', 'Main Administrative Block', 500, 'Projector, Sound System, Central AC, High Speed WiFi'),
(2, 'Turing Computing Lab (Lab 101)', 'Block A, 1st Floor', 80, '80 Desktop PCs, LAN, Ubuntu OS, Dual Monitors for Mentors'),
(3, 'Ada Lovelace Lab (Lab 201)', 'Block A, 2nd Floor', 75, '75 High-Spec PCs, Fast Internet, Projector'),
(4, 'Robotics & IoT Center', 'Block B, Ground Floor', 60, 'Workbenches, Power Supply, Oscilloscopes, Testing Arena'),
(5, 'Seminar Hall 2', 'Block B, 3rd Floor', 120, 'Audio System, Projector, Podium');

-- 5. Insert Events
INSERT INTO EVENT (Event_ID, Event_Name, Description, Event_Type, Team_Size, Max_Participants, Registration_Fee, Event_Date, Last_Date_to_Register, Coordinator_ID, Venue_ID) VALUES
(1, 'CodeSprint Hackathon', 'A 6-hour intense coding hackathon to build solutions for real-world smart campus challenges.', 'Coding', 2, 60, 200.00, '2026-10-15', '2026-10-12', 1, 2),
(2, 'WebCraft UI/UX Challenge', 'Design and implement responsive, accessible web interfaces given a live prompt.', 'Web Design', 1, 40, 100.00, '2026-10-15', '2026-10-12', 2, 3),
(3, 'BugHunt: Code Debugging', 'Test your debugging acumen across C++, Java, and Python broken code snippets.', 'Debugging', 1, 50, 50.00, '2026-10-16', '2026-10-13', 4, 2),
(4, 'RoboWars Battle Arena', 'Autonomous and manual obstacle-avoiding bot race and combat challenge.', 'Robotics', 3, 30, 300.00, '2026-10-16', '2026-10-13', 3, 4),
(5, 'Tech Trivia Quiz', 'General technology, AI breakthroughs, computing history, and pop-culture quiz.', 'Quiz', 2, 80, 50.00, '2026-10-15', '2026-10-13', 1, 5),
(6, 'LAN Gaming Championship', 'Fast-paced multiplayer e-sports tournament with round-robin elimination.', 'Gaming', 1, 64, 150.00, '2026-10-16', '2026-10-14', 4, 1);

-- 6. Insert Event Schedules
INSERT INTO EVENT_SCHEDULE (Schedule_ID, Event_ID, Event_Date, Start_Time, End_Time, Venue_ID) VALUES
(1, 1, '2026-10-15', '09:30:00', '15:30:00', 2),
(2, 2, '2026-10-15', '10:00:00', '13:00:00', 3),
(3, 5, '2026-10-15', '14:00:00', '16:30:00', 5),
(4, 3, '2026-10-16', '10:00:00', '12:30:00', 2),
(5, 4, '2026-10-16', '11:00:00', '15:00:00', 4),
(6, 6, '2026-10-16', '13:00:00', '17:00:00', 1);

-- 7. Insert Participants
INSERT INTO PARTICIPANT (Participant_ID, Roll_No, Name, Email, Phone_No, Gender, Address, Semester, Year, Department_ID) VALUES
(1, '21CS001', 'Aarav Sharma', 'aarav.sharma@example.com', '9876500001', 'Male', 'Hostel 1, Room 102', 5, 3, 1),
(2, '21CS045', 'Ananya Verma', 'ananya.v@example.com', '9876500002', 'Female', 'City Heights, Flat 402', 5, 3, 1),
(3, '22IT012', 'Rohan Mehta', 'rohan.m@example.com', '9876500003', 'Male', 'Lake View Apartments', 3, 2, 2),
(4, '22IT034', 'Sneha Iyer', 'sneha.iyer@example.com', '9876500004', 'Female', 'Hostel 3, Room 210', 3, 2, 2),
(5, '21EC019', 'Vikramaditya Roy', 'vikram.roy@example.com', '9876500005', 'Male', 'Sector 14, House 55', 5, 3, 3),
(6, '23CS088', 'Pooja Nair', 'pooja.nair@example.com', '9876500006', 'Female', 'Green Valley Residency', 1, 1, 1),
(7, '22ME009', 'Karan Johar', 'karan.j@example.com', '9876500007', 'Male', 'Hostel 2, Room 305', 3, 2, 4),
(8, '21CS099', 'Tanvi Kulkarni', 'tanvi.k@example.com', '9876500008', 'Female', 'Sector 22, Block B', 7, 4, 1);

-- 8. Insert Teams
INSERT INTO TEAM (Team_ID, Team_Name, Team_Leader_ID, Team_Size) VALUES
(1, 'Binary Beasts', 1, 2),
(2, 'RoboKnights', 5, 2);

-- 9. Insert Registrations
INSERT INTO REGISTRATION (Registration_ID, Participant_ID, Event_ID, Team_ID, Registration_Date, Status) VALUES
(1, 1, 1, 1, '2026-10-01 10:15:00', 'Confirmed'),
(2, 2, 1, 1, '2026-10-01 10:20:00', 'Confirmed'),
(3, 3, 2, NULL, '2026-10-02 11:00:00', 'Confirmed'),
(4, 4, 3, NULL, '2026-10-02 14:30:00', 'Confirmed'),
(5, 5, 4, 2, '2026-10-03 09:45:00', 'Confirmed'),
(6, 6, 5, NULL, '2026-10-03 16:10:00', 'Confirmed'),
(7, 7, 6, NULL, '2026-10-04 12:00:00', 'Confirmed'),
(8, 8, 2, NULL, '2026-10-04 15:20:00', 'Confirmed');

-- 10. Insert Payments
INSERT INTO PAYMENT (Payment_ID, Registration_ID, Amount, Payment_Date, Payment_Mode, Transaction_ID) VALUES
(1, 1, 200.00, '2026-10-01 10:16:00', 'UPI', 'TXN9812480101'),
(2, 2, 200.00, '2026-10-01 10:21:00', 'UPI', 'TXN9812480102'),
(3, 3, 100.00, '2026-10-02 11:02:00', 'Debit Card', 'TXN9812480103'),
(4, 4, 50.00, '2026-10-02 14:32:00', 'UPI', 'TXN9812480104'),
(5, 5, 300.00, '2026-10-03 09:47:00', 'Net Banking', 'TXN9812480105'),
(6, 6, 50.00, '2026-10-03 16:12:00', 'UPI', 'TXN9812480106'),
(7, 7, 150.00, '2026-10-04 12:05:00', 'UPI', 'TXN9812480107'),
(8, 8, 100.00, '2026-10-04 15:22:00', 'Credit Card', 'TXN9812480108');

-- 11. Insert Attendance
INSERT INTO ATTENDANCE (Attendance_ID, Registration_ID, Date, Status, Remarks) VALUES
(1, 1, '2026-10-15', 'Present', 'Attended Hackathon on time'),
(2, 2, '2026-10-15', 'Present', 'Attended Hackathon on time'),
(3, 3, '2026-10-15', 'Present', 'Completed UI Challenge'),
(4, 4, '2026-10-16', 'Present', 'Solved 8/10 bugs'),
(5, 5, '2026-10-16', 'Present', 'Bot cleared stage 1 & 2'),
(6, 7, '2026-10-16', 'Absent', 'Did not report for round 1');

-- 12. Insert Results
INSERT INTO RESULT (Result_ID, Registration_ID, Position, Score, Result_Declared_Date, Remarks) VALUES
(1, 1, '1st Place', 94.50, '2026-10-16', 'Outstanding AI Smart Campus prototype'),
(2, 3, '1st Place', 91.00, '2026-10-16', 'Exceptional responsive design and accessibility'),
(3, 4, '2nd Place', 88.00, '2026-10-16', 'Fast debugging in C++ and Python'),
(4, 5, '1st Place', 95.00, '2026-10-16', 'Best obstacle clearance time in RoboWars');

-- 13. Insert Certificates
INSERT INTO CERTIFICATE (Certificate_ID, Result_ID, Certificate_No, Issue_Date) VALUES
(1, 1, 'CTF-2026-CERT-001', '2026-10-17'),
(2, 2, 'CTF-2026-CERT-002', '2026-10-17'),
(3, 3, 'CTF-2026-CERT-003', '2026-10-17'),
(4, 4, 'CTF-2026-CERT-004', '2026-10-17');

-- 14. Insert Admin Account
-- Default password: admin123
-- Stored both as standard bcrypt hash and plain password compatible
INSERT INTO ADMIN (Admin_ID, Username, Password, Full_Name, Role) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Fest Convener', 'Chief Administrator');
