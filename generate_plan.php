<?php
require_once 'config/db.php';
checkAuth();

$day = isset($_GET['day']) ? $_GET['day'] : date('l');
$session_duration = isset($_GET['session_duration']) ? (int)$_GET['session_duration'] : 60;
$break_duration = isset($_GET['break_duration']) ? (int)$_GET['break_duration'] : 15;
$is_weekend = ($day == 'Saturday' || $day == 'Sunday');

$stmt = $pdo->prepare("SELECT * FROM routines WHERE user_id = ? AND day_of_week = ? ORDER BY start_time");
$stmt->execute([$_SESSION['user_id'], $day]);
$routines = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT sleep_start, sleep_end FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
$sleep_start = $user['sleep_start'] ?: '23:00:00';
$sleep_end = $user['sleep_end'] ?: '07:00:00';

function timeToMinutes($time) {
    $parts = explode(':', $time);
    return (int)$parts[0] * 60 + (int)$parts[1];
}
function minutesToTime($minutes) {
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    return sprintf('%02d:%02d', $hours, $mins);
}
function formatTimeDisplay($minutes) {
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    if ($hours > 0) return sprintf('%d hr %02d min', $hours, $mins);
    return sprintf('%d min', $mins);
}

// Find free slots
function findFreeSlots($routines, $sleep_start_min, $sleep_end_min) {
    $day_start = 6*60;
    $day_end = 23*60;
    $busy_slots = [];

    if ($sleep_start_min < $sleep_end_min) {
        $busy_slots[] = ['start'=>$sleep_start_min,'end'=>$sleep_end_min];
    } else {
        $busy_slots[] = ['start'=>$sleep_start_min,'end'=>24*60];
        $busy_slots[] = ['start'=>0,'end'=>$sleep_end_min];
    }

    foreach ($routines as $r) {
        $busy_slots[] = ['start'=>timeToMinutes($r['start_time']),'end'=>timeToMinutes($r['end_time'])];
    }

    usort($busy_slots, fn($a,$b)=>$a['start']-$b['start']);
    $merged = [];
    foreach($busy_slots as $s){
        if(empty($merged) || $s['start']>$merged[count($merged)-1]['end']) $merged[]=$s;
        else $merged[count($merged)-1]['end']=max($merged[count($merged)-1]['end'],$s['end']);
    }

    $free_slots = [];
    $current_time = $day_start;
    foreach($merged as $slot){
        if($current_time<$slot['start']) $free_slots[]=['start'=>$current_time,'end'=>$slot['start'],'duration'=>$slot['start']-$current_time];
        $current_time = max($current_time,$slot['end']);
    }
    if($current_time<$day_end) $free_slots[]=['start'=>$current_time,'end'=>$day_end,'duration'=>$day_end-$current_time];

    return array_values(array_filter($free_slots, fn($s)=>$s['duration']>=15));
}

// Generate sessions
function generateStudySessions($free_slots, $session_duration, $break_duration, $is_weekend){
    $sessions = [];
    $max_sessions_per_slot = $is_weekend ? 4 : 3;

    foreach($free_slots as $slot){
        $available_time = $slot['duration'];
        $start_time = $slot['start'];
        $session_count = 0;

        while($available_time >= $session_duration && $session_count<$max_sessions_per_slot){
            $sessions[]=['type'=>'study','start'=>$start_time,'end'=>$start_time+$session_duration,'duration'=>$session_duration];
            $available_time -= $session_duration;
            $session_count++;
            $start_time += $session_duration;

            if($available_time >= $break_duration && $session_count<$max_sessions_per_slot){
                $sessions[]=['type'=>'break','start'=>$start_time,'end'=>$start_time+$break_duration,'duration'=>$break_duration];
                $available_time -= $break_duration;
                $start_time += $break_duration;
            }
        }
    }
    return $sessions;
}

function calculateStats($sessions){
    $stats=['study'=>0,'study_time'=>0,'break_time'=>0,'total'=>count($sessions)];
    foreach($sessions as $s){
        if($s['type']=='study'){$stats['study']++;$stats['study_time']+=$s['duration'];}
        else $stats['break_time']+=$s['duration'];
    }
    $stats['efficiency'] = $stats['study_time']>0 ? min(100, round(($stats['study_time']/480)*100)) : 0;
    return $stats;
}

$sleep_start_min = timeToMinutes($sleep_start);
$sleep_end_min = timeToMinutes($sleep_end);
$free_slots = findFreeSlots($routines,$sleep_start_min,$sleep_end_min);
$study_sessions = generateStudySessions($free_slots,$session_duration,$break_duration,$is_weekend);
$stats = calculateStats($study_sessions);

$subject_suggestions = [
    'Monday'=>['Mathematics','Physics','Programming'],
    'Tuesday'=>['Chemistry','Biology','English'],
    'Wednesday'=>['History','Geography','Economics'],
    'Thursday'=>['Mathematics','Computer Science','Statistics'],
    'Friday'=>['Languages','Literature','Arts'],
    'Saturday'=>['Revision','Practice Tests','Projects'],
    'Sunday'=>['Planning','Reading','Research']
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Study Plan - <?php echo $day;?></title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <div class="navbar flex space-between align-center mb-30">
        <h1>Study Plan for <?php echo $day;?></h1>
        <div>
            <a href="dashboard.php?day=<?php echo urlencode($day);?>" class="btn bg-secondary">← Dashboard</a>
            <a href="logout.php" class="btn bg-danger">Logout</a>
        </div>
    </div>

    <!-- Plan Summary -->
    <div class="card mb-30">
        <h2>Today's Overview</h2>
        <div class="stats-grid">
            <div class="text-center">
                <div class="stat-number text-primary"><?php echo $stats['study'];?></div>
                <div class="text-light">Study Sessions</div>
            </div>
            <div class="text-center">
                <div class="stat-number text-success"><?php echo formatTimeDisplay($stats['study_time']);?></div>
                <div class="text-light">Total Study Time</div>
            </div>
            <div class="text-center">
                <div class="stat-number text-warning"><?php echo formatTimeDisplay($stats['break_time']);?></div>
                <div class="text-light">Break Time</div>
            </div>
            <div class="text-center">
                <div class="stat-number text-info"><?php echo $stats['efficiency'];?>%</div>
                <div class="text-light">Efficiency</div>
            </div>
        </div>
    </div>

    <!-- Free Slots -->
    <?php if(!empty($free_slots)): ?>
    <div class="card mb-30">
        <h2>Free Time Slots</h2>
        <?php foreach($free_slots as $i=>$slot): ?>
            <div class="free-slot">
                <span class="time-badge">Slot <?php echo $i+1;?></span>
                <strong><?php echo minutesToTime($slot['start']);?> - <?php echo minutesToTime($slot['end']);?></strong>
                <span class="text-success"><?php echo formatTimeDisplay($slot['duration']);?> available</span>
            </div>
        <?php endforeach;?>
    </div>
    <?php endif; ?>

    <!-- Study Sessions -->
    <?php if(!empty($study_sessions)): ?>
    <div class="card mb-30">
        <h2>Recommended Study Plan</h2>
        <?php $counter=0; foreach($study_sessions as $s): $counter++; ?>
            <div class="<?php echo $s['type']=='study'?'session-study':'session-break';?>">
                <div class="flex space-between align-center mb-10">
                    <div>
                        <span class="time-badge"><?php echo minutesToTime($s['start']);?> - <?php echo minutesToTime($s['end']);?></span>
                        <strong>
                            <?php echo $s['type']=='study' ? "Study Session ".ceil($counter/2) : "Break Time";?>
                        </strong>
                    </div>
                    <span class="badge <?php echo $s['type']=='study'?'bg-success':'bg-warning';?>">
                        <?php echo $s['duration'];?> min
                    </span>
                </div>
                <?php if($s['type']=='study'): ?>
                    <p class="text-light">Focus on: <?php echo $subject_suggestions[$day][$counter%3];?></p>
                    <p class="text-light"><em>Tip: Stay focused, take notes, and avoid distractions!</em></p>
                <?php else: ?>
                    <p class="text-light">Break Activity: Stretch, hydrate, relax eyes.</p>
                <?php endif; ?>
            </div>
        <?php endforeach;?>
    </div>
    <?php endif; ?>

    <!-- Actions -->
    <div class="text-center mb-30">
        <a href="dashboard.php?day=<?php echo urlencode($day);?>" class="btn bg-secondary">← Dashboard</a>
        <a href="generate_plan.php?day=<?php echo urlencode($day);?>&session_duration=<?php echo $session_duration;?>&break_duration=<?php echo $break_duration;?>" 
           class="btn bg-success">Regenerate Plan</a>
        <button onclick="window.print()" class="btn bg-info">Print Plan</button>
    </div>
</div>
</body>
</html>
