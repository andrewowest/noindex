<?php
require_once __DIR__ . '/../inc/common.php';
require_once __DIR__ . '/../inc/fineprint/packs/forum.php';

header('Content-Type: application/json');

$offset = (int)($_GET['offset'] ?? 0);
$limit = 20;
$view = $_GET['view'] ?? 'latest';

// Get threads based on view
$orderBy = match($view) {
  'top' => 'post_count DESC',
  'active' => 'last_post_time DESC',
  default => 'COALESCE(last_post_time, created) DESC'
};

$stmt = db()->prepare("
  SELECT t.*, 
    (SELECT MAX(time) FROM posts WHERE thread_id = t.id) as last_post_time,
    (SELECT author FROM posts WHERE thread_id = t.id ORDER BY time DESC LIMIT 1) as last_post_author,
    (SELECT COUNT(*) FROM posts WHERE thread_id = t.id) as post_count
  FROM threads t
  ORDER BY {$orderBy}
  LIMIT :limit OFFSET :offset
");

$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$threads = $stmt->fetchAll();

// Format threads
$formatted = [];
foreach($threads as $t) {
  $lastTime = $t['last_post_time'] ?? $t['created'];
  $formattedTime = date('g:ia', (int)$lastTime);
  
  $formatted[] = [
    'id' => $t['id'],
    'title' => $t['title'],
    'author' => $t['author'],
    'url' => '/thread.php?id=' . urlencode($t['id']),
    'reply_count' => (int)($t['post_count'] ?? 0),
    'last_post_time' => $formattedTime,
    'created' => $t['created'],
    'last_activity' => $t['last_post_time'] ?? $t['created']
  ];
}

// Group by date only for latest view
if($view === 'latest' || $view === 'active') {
  $grouped = [];
  foreach($formatted as $thread) {
    $date = date('Y-m-d', (int)$thread['last_activity']);
    if(!isset($grouped[$date])) {
      $grouped[$date] = [
        'date' => $date,
        'heading' => \Fineprint\Packs\Forum\format_date_heading($date),
        'threads' => []
      ];
    }
    $grouped[$date]['threads'][] = $thread;
  }
  
  echo json_encode([
    'groups' => array_values($grouped),
    'has_more' => count($threads) === $limit
  ]);
} else {
  // For top view, return flat list
  echo json_encode([
    'threads' => $formatted,
    'has_more' => count($threads) === $limit
  ]);
}
