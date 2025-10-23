<?php
require __DIR__.'/inc/common.php';

$me = current_user();

// Redirect to login wall if not logged in
if(!$me){
  if(!headers_sent()) header('Location: /login.php');
  exit;
}

header_html('threads', 'index');

$db = db();
$now = now();
$startOfDay = strtotime('today', $now);
$day24hAgo = $now - 86400;
$days7Ago = $now - (86400 * 7);

$todayThreadsStmt = $db->prepare("SELECT COUNT(*) AS c FROM threads WHERE created >= ?");
$todayThreadsStmt->execute([$startOfDay]);
$todayThreads = (int)($todayThreadsStmt->fetch()['c'] ?? 0);

$todayPostsStmt = $db->prepare("SELECT COUNT(*) AS c FROM posts WHERE time >= ?");
$todayPostsStmt->execute([$startOfDay]);
$todayPosts = (int)($todayPostsStmt->fetch()['c'] ?? 0);

$newMembersStmt = db()->prepare("SELECT COUNT(*) AS c FROM users WHERE joined >= ?");
$newMembersStmt->execute([$days7Ago]);
$newMembers = (int)($newMembersStmt->fetch()['c'] ?? 0);

$active24h = count(online_users(86400));

// Make stats available to template
global $_THEME_STATS;
$_THEME_STATS = [
  'threads' => $todayThreads,
  'posts' => $todayPosts,
  'members' => $newMembers,
  'active' => $active24h
];

$rawView = $_GET['view'] ?? null;
$mode = $_GET['mode'] ?? null;
if($mode === null){
  if($rawView === 'top'){ $mode = 'top'; }
  elseif($rawView === 'latest'){ $mode = 'latest'; }
  elseif($rawView === 'active'){ $mode = 'active'; }
}
$allowedModes = ['latest','top','active'];
if($mode === null || !in_array($mode, $allowedModes, true)){
  $mode = 'latest';
}

$filter = $_GET['filter'] ?? null;
if($filter === null && $rawView !== null && !in_array($rawView, $allowedModes, true)){
  $filter = $rawView;
}
$allowedFilters = ['active','unanswered'];
if($me){ $allowedFilters[] = 'mine'; }
if($filter !== null && !in_array($filter, $allowedFilters, true)){
  $filter = null;
}

// Get all threads with their latest post info
$s = settings();
$limit = (int)($s['items_per_page'] ?? 25);

$stmt = db()->query("
  SELECT t.*, 
    (SELECT MAX(time) FROM posts WHERE thread_id = t.id) as last_post_time,
    (SELECT author FROM posts WHERE thread_id = t.id ORDER BY time DESC LIMIT 1) as last_post_author,
    (SELECT COUNT(*) FROM posts WHERE thread_id = t.id) as post_count
  FROM threads t
  ORDER BY COALESCE(last_post_time, created) DESC
  LIMIT {$limit}
");
$threads = $stmt->fetchAll();

$displayThreads = $threads;
if($filter === 'unanswered'){
  $displayThreads = array_values(array_filter($displayThreads, fn($t) => (int)($t['post_count'] ?? 0) <= 1));
} elseif($filter === 'mine'){
  if($me){
    $displayThreads = array_values(array_filter($displayThreads, fn($t) => ($t['author'] ?? '') === $me['username']));
  } else {
    $displayThreads = [];
  }
}

$dateFilterParam = $_GET['date'] ?? null;
$activeDateFilter = null;
if($dateFilterParam !== null){
  $dateCheck = DateTime::createFromFormat('Y-m-d', $dateFilterParam);
  if($dateCheck !== false){
    $activeDateFilter = $dateCheck->format('Y-m-d');
    $displayThreads = array_values(array_filter($displayThreads, function(array $thread) use ($activeDateFilter){
      $lastActivityTs = (int)($thread['last_post_time'] ?? $thread['created'] ?? 0);
      if($lastActivityTs <= 0){
        return false;
      }
      return date('Y-m-d', $lastActivityTs) === $activeDateFilter;
    }));
  }
}

$isTopMode = ($mode === 'top');
$isActiveMode = ($mode === 'active');

if($isTopMode){
  usort($displayThreads, function(array $a, array $b){
    $countDiff = (int)($b['post_count'] ?? 0) <=> (int)($a['post_count'] ?? 0);
    if($countDiff !== 0) return $countDiff;
    $bTime = (int)($b['last_post_time'] ?? $b['created'] ?? 0);
    $aTime = (int)($a['last_post_time'] ?? $a['created'] ?? 0);
    return $bTime <=> $aTime;
  });
} elseif($isActiveMode) {
  // Group threads by week of last activity
  $grouped = [];
  foreach($displayThreads as $t){
    $lastActivity = (int)($t['last_post_time'] ?? $t['created']);
    // Get the Monday of the week for this timestamp
    $dayOfWeek = (int)date('N', $lastActivity); // 1 (Monday) through 7 (Sunday)
    $daysToMonday = $dayOfWeek - 1;
    $weekStart = strtotime("-{$daysToMonday} days", strtotime(date('Y-m-d', $lastActivity)));
    $weekKey = date('Y-m-d', $weekStart);
    if(!isset($grouped[$weekKey])) $grouped[$weekKey] = [];
    $grouped[$weekKey][] = $t;
  }
  // Sort threads within each week by post count
  foreach($grouped as &$weekThreads){
    usort($weekThreads, function(array $a, array $b){
      $countDiff = (int)($b['post_count'] ?? 0) <=> (int)($a['post_count'] ?? 0);
      if($countDiff !== 0) return $countDiff;
      $bTime = (int)($b['last_post_time'] ?? $b['created'] ?? 0);
      $aTime = (int)($a['last_post_time'] ?? $a['created'] ?? 0);
      return $bTime <=> $aTime;
    });
  }
  unset($weekThreads);
} else {
  // Group threads by date of last activity
  $grouped = [];
  foreach($displayThreads as $t){
    $lastActivity = $t['last_post_time'] ?? $t['created'];
    $date = date('Y-m-d', (int)$lastActivity);
    if(!isset($grouped[$date])) $grouped[$date] = [];
    $grouped[$date][] = $t;
  }
}

$s = settings();

// Build filters array based on settings
$filters = [];
if(($s['enable_filter_active'] ?? '1') === '1'){
  $filters[] = ['value' => 'active', 'label' => 'active'];
}
if(($s['enable_filter_unanswered'] ?? '1') === '1'){
  $filters[] = ['value' => 'unanswered', 'label' => 'unanswered'];
}
if(($s['enable_filter_mine'] ?? '1') === '1'){
  $filters[] = ['value' => 'mine', 'label' => 'my threads', 'requires_login' => true];
}

// Build view options based on settings
$viewOptions = [];
if(($s['enable_view_latest'] ?? '1') === '1'){
  $viewOptions[] = ['value' => 'latest', 'label' => 'latest'];
}
if(($s['enable_view_active'] ?? '1') === '1'){
  $viewOptions[] = ['value' => 'active', 'label' => 'active'];
}
if(($s['enable_view_top'] ?? '1') === '1'){
  $viewOptions[] = ['value' => 'top', 'label' => 'top'];
}

// Pass page summary data to Fineprint context
global $_THEME_STATS;
$_THEME_STATS['page_filters'] = $filters;
$_THEME_STATS['page_views'] = $viewOptions;
$_THEME_STATS['current_filter'] = $filter;
$_THEME_STATS['current_mode'] = $mode;
$_THEME_STATS['has_page_summary'] = true;

// Pass the grouped/sorted threads to Fineprint context
if($isTopMode) {
  // For top mode, pass flat sorted array
  $_THEME_STATS['grouped_threads'] = $displayThreads;
} elseif($isActiveMode) {
  // For active mode, pass grouped by week
  $_THEME_STATS['grouped_threads'] = $grouped;
} else {
  // For latest mode, pass grouped by date
  $_THEME_STATS['grouped_threads'] = $grouped ?? [];
}

// All the HTML generation is now handled by Fineprint blocks
// The ThreadsByDate block will render the threads
footer_html();
