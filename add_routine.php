<?php
require_once 'config/db.php';
checkAuth(); // Make sure user is logged in

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $user_id    = $_SESSION['user_id'];
    $day        = trim($_POST['day']);
    $title      = trim($_POST['title']);
    $start_time = $_POST['start_time'];
    $end_time   = $_POST['end_time'];
    $location   = trim($_POST['location']);

    // Check if end time is valid
    if (strtotime($end_time) <= strtotime($start_time)) {
        header("Location: dashboard.php?day=" . urlencode($day) . "&message=Error: End time must be after start time!");
        exit();
    }

    // Check if time overlaps with existing routine
    $stmt = $pdo->prepare("
        SELECT id FROM routines 
        WHERE user_id = ? 
        AND day_of_week = ?
        AND (
            (start_time < ? AND end_time > ?)
        )
    ");
    $stmt->execute([$user_id, $day, $end_time, $start_time]);

    if ($stmt->rowCount() > 0) {
        header("Location: dashboard.php?day=" . urlencode($day) . "&message=Error: Time slot overlaps with existing routine!");
        exit();
    }

    // Insert routine into database
    try {
        $stmt = $pdo->prepare("
            INSERT INTO routines (user_id, day_of_week, title, start_time, end_time, location)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $day, $title, $start_time, $end_time, $location]);

        header("Location: dashboard.php?day=" . urlencode($day) . "&message=✅ Routine added successfully!");
        exit();

    } catch (PDOException $e) {
        header("Location: dashboard.php?day=" . urlencode($day) . "&message=❌ Failed to add routine. Please try again!");
        exit();
    }

} else {
    header("Location: dashboard.php");
    exit();
}
?>
