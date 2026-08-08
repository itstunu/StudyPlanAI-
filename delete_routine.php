<?php
require_once 'config/db.php';
checkAuth();

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $day = isset($_GET['day']) ? $_GET['day'] : date('l');
    
    try {
        // Verify ownership before deleting
        $stmt = $pdo->prepare("DELETE FROM routines WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $_SESSION['user_id']]);
        
        if ($stmt->rowCount() > 0) {
            header("Location: dashboard.php?day=" . urlencode($day) . "&message=✅ Routine deleted successfully!");
        } else {
            header("Location: dashboard.php?day=" . urlencode($day) . "&message=❌ Routine not found or access denied!");
        }
        exit();
    } catch(PDOException $e) {
        header("Location: dashboard.php?day=" . urlencode($day) . "&message=❌ Failed to delete routine!");
        exit();
    }
} else {
    header("Location: dashboard.php");
    exit();
}
?>