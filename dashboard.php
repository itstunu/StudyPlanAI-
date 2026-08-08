<?php
require_once 'config/db.php';
checkAuth();

$message = isset($_GET['message']) ? $_GET['message'] : '';
$day = isset($_GET['day']) ? $_GET['day'] : date('l');
$days_of_week = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

$stmt = $pdo->prepare("SELECT * FROM routines WHERE user_id = ? AND day_of_week = ? ORDER BY start_time");
$stmt->execute([$_SESSION['user_id'], $day]);
$routines = $stmt->fetchAll();

$hasRoutines = !empty($routines);
$dayType = $hasRoutines ? 'Workday' : 'Weekend';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Assistant - Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">

    <!-- Navbar -->
    <div class="navbar flex justify-between align-center">
        <h1 class="text-white">Student Assistant <span class="text-primary">Dashboard</span></h1>
        <div class="flex align-center">
            <span class="text-light mr-2">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</span>
            <a href="dashboard.php" class="btn">Dashboard</a>
            <a href="logout.php" class="btn btn-secondary">Logout</a>
        </div>
    </div>

    <!-- Message -->
    <?php if ($message): ?>
        <div class="card alert-success mb-3"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <!-- Day Selector -->
    <div class="card mb-3">
        <h2>Select Day</h2>
        <div class="flex mb-3">
            <?php foreach ($days_of_week as $d): ?>
                <a href="dashboard.php?day=<?php echo urlencode($d); ?>" 
                   class="btn day-btn <?php echo $d == $day ? 'active' : ''; ?>">
                   <?php echo $d; ?>
                </a>
            <?php endforeach; ?>
        </div>
        <p>
            <strong>Selected:</strong> <?php echo $day; ?> 
            <span class="time-badge <?php echo $hasRoutines ? 'bg-success' : 'bg-warning'; ?>">
                <?php echo $dayType; ?>
            </span>
            | <strong>Date:</strong> <?php echo date('F j, Y'); ?>
        </p>
    </div>

    <!-- Stats -->
    <div class="stats mb-3">
        <div class="stat-card text-center">
            <div class="stat-number"><?php echo count($routines); ?></div>
            <div class="stat-label">Routines Today</div>
        </div>
        <div class="stat-card text-center">
            <div class="stat-number <?php echo $hasRoutines ? 'text-success' : 'text-warning'; ?>">
                <?php echo $dayType; ?>
            </div>
            <div class="stat-label">Day Type</div>
        </div>
        <div class="stat-card text-center">
            <div class="stat-number">24/7</div>
            <div class="stat-label">System Active</div>
        </div>
    </div>

    <!-- Two Column Layout -->
    <div class="grid mb-3">
        <div class="card">
            <h2>Add New Routine</h2>
            <form method="POST" action="add_routine.php">
                <input type="hidden" name="day" value="<?php echo htmlspecialchars($day); ?>">
                <div class="form-group">
                    <input type="text" name="title" class="form-control" placeholder="Activity Title" required>
                </div>
                <div class="form-group">
                    <label>Start Time:</label>
                    <input type="time" name="start_time" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>End Time:</label>
                    <input type="time" name="end_time" class="form-control" required>
                </div>
                <div class="form-group">
                    <input type="text" name="location" class="form-control" placeholder="Location">
                </div>
                <button type="submit" class="btn">Add Routine</button>
            </form>
        </div>

        <div class="card">
            <h2>Generate Study Plan</h2>
            <p class="text-muted mb-3">
                <?php if ($hasRoutines): ?>
                    Generate study plan around your <?php echo count($routines); ?> routines
                <?php else: ?>
                    Create a study plan for your <?php echo strtolower($dayType); ?>
                <?php endif; ?>
            </p>
            <form method="GET" action="generate_plan.php">
                <input type="hidden" name="day" value="<?php echo htmlspecialchars($day); ?>">
                <div class="form-group">
                    <label>Study Duration (minutes):</label>
                    <input type="number" name="session_duration" class="form-control" value="60" min="30" max="180">
                </div>
                <div class="form-group">
                    <label>Break Duration (minutes):</label>
                    <input type="number" name="break_duration" class="form-control" value="15" min="5" max="60">
                </div>
                <button type="submit" class="btn">Generate Smart Plan</button>
            </form>
        </div>
    </div>

    <!-- Routines List -->
    <div class="card mb-3">
        <h2>Your Routines for <?php echo $day; ?> 
            <span class="time-badge <?php echo $hasRoutines ? 'bg-success' : 'bg-warning'; ?>"><?php echo $dayType; ?></span>
        </h2>

        <?php if (!$hasRoutines): ?>
            <div class="empty-state text-center">
                <h3>No routines scheduled</h3>
                <p>It's a <?php echo strtolower($dayType); ?>! Add routines to make it productive.</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Time Slot</th>
                        <th>Activity</th>
                        <th>Location</th>
                        <th>Duration</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($routines as $routine): 
                        $start = strtotime($routine['start_time']);
                        $end = strtotime($routine['end_time']);
                        $duration = ($end - $start) / 60;
                    ?>
                        <tr>
                            <td><span class="time-badge"><?php echo date('g:i A', $start); ?> - <?php echo date('g:i A', $end); ?></span></td>
                            <td><?php echo htmlspecialchars($routine['title']); ?></td>
                            <td><?php echo htmlspecialchars($routine['location']) ?: 'N/A'; ?></td>
                            <td><?php echo $duration; ?> min</td>
                            <td>
                                <a href="delete_routine.php?id=<?php echo $routine['id']; ?>&day=<?php echo urlencode($day); ?>" 
                                   class="btn btn-danger" onclick="return confirm('Delete this routine?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Quick Tips -->
    <div class="card">
        <h2>Quick Tips</h2>
        <div class="grid">
            <div>
                <h4>Be Specific</h4>
                <p>Instead of "Study", write "Study Calculus Chapter 3"</p>
            </div>
            <div>
                <h4>Time Blocks</h4>
                <p>Schedule 60-90 minute study sessions with breaks</p>
            </div>
            <div>
                <h4>Consistency</h4>
                <p>Try to study at the same time daily</p>
            </div>
            <div>
                <h4>Prioritize</h4>
                <p>Schedule difficult subjects during peak focus times</p>
            </div>
        </div>
    </div>

</div>

</body>
</html>
