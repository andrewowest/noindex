<?php
require __DIR__.'/inc/common.php';
require_login();

$query = trim($_GET['q'] ?? '');
$searchThreads = isset($_GET['threads']) ? $_GET['threads'] === '1' : true;
$searchPosts = isset($_GET['posts']) ? $_GET['posts'] === '1' : true;
$dateFrom = $_GET['from'] ?? '';
$dateTo = $_GET['to'] ?? '';
$results = [];

if($query !== ''){
  $db = db();
  $searchTerm = '%' . $query . '%';
  $threadResults = [];
  $postResults = [];
  
  // Search threads
  if($searchThreads){
    $sql = "
      SELECT 
        t.id,
        t.title,
        t.author,
        t.created,
        'thread' as type,
        (SELECT COUNT(*) FROM posts WHERE thread_id = t.id) as reply_count
      FROM threads t
      WHERE (t.title LIKE ? OR t.author LIKE ?)
    ";
    $params = [$searchTerm, $searchTerm];
    
    if($dateFrom !== ''){
      $sql .= " AND t.created >= ?";
      $params[] = strtotime($dateFrom);
    }
    if($dateTo !== ''){
      $sql .= " AND t.created <= ?";
      $params[] = strtotime($dateTo . ' 23:59:59');
    }
    
    $sql .= " ORDER BY t.created DESC LIMIT 50";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $threadResults = $stmt->fetchAll();
  }
  
  // Search posts
  if($searchPosts){
    $sql = "
      SELECT 
        p.id,
        p.thread_id,
        p.body,
        p.author,
        p.time,
        t.title as thread_title,
        'post' as type
      FROM posts p
      JOIN threads t ON p.thread_id = t.id
      WHERE (p.body LIKE ? OR p.author LIKE ?)
    ";
    $params = [$searchTerm, $searchTerm];
    
    if($dateFrom !== ''){
      $sql .= " AND p.time >= ?";
      $params[] = strtotime($dateFrom);
    }
    if($dateTo !== ''){
      $sql .= " AND p.time <= ?";
      $params[] = strtotime($dateTo . ' 23:59:59');
    }
    
    $sql .= " ORDER BY p.time DESC LIMIT 50";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $postResults = $stmt->fetchAll();
  }
  
  $results = array_merge($threadResults, $postResults);
  usort($results, function($a, $b){
    $timeA = $a['type'] === 'thread' ? $a['created'] : $a['time'];
    $timeB = $b['type'] === 'thread' ? $b['created'] : $b['time'];
    return $timeB - $timeA;
  });
}

header_html('search', 'search');
?>

<form method="get" class="search-form">
  <div class="search-form__group">
    <input 
      type="text" 
      name="q" 
      value="<?=h($query)?>"
      placeholder="Search..." 
      class="search-form__input"
      autofocus
    >
    <button type="submit" class="btn btn--primary">Search</button>
  </div>
  
  <div class="search-form__filters">
    <div class="search-form__filter-group">
      <label class="search-form__checkbox">
        <input type="checkbox" name="threads" value="1" <?=$searchThreads ? 'checked' : ''?>>
        <span>Threads</span>
      </label>
      <label class="search-form__checkbox">
        <input type="checkbox" name="posts" value="1" <?=$searchPosts ? 'checked' : ''?>>
        <span>Posts</span>
      </label>
    </div>
    
    <div class="search-form__date-group">
      <input 
        type="date" 
        name="from" 
        value="<?=h($dateFrom)?>"
        placeholder="From"
        class="search-form__date"
      >
      <span class="search-form__date-separator">→</span>
      <input 
        type="date" 
        name="to" 
        value="<?=h($dateTo)?>"
        placeholder="To"
        class="search-form__date"
      >
    </div>
  </div>
</form>

<?php if($query !== ''): ?>
  <div class="search-results">
    <h2 class="search-results__title"><?=count($results)?> result<?=count($results) !== 1 ? 's' : ''?> for "<?=h($query)?>"</h2>
    
    <?php if(empty($results)): ?>
      <div class="empty-state">
        <div class="empty-state__label">No results found</div>
      </div>
    <?php else: ?>
      <div class="search-results__list">
        <?php foreach($results as $result): ?>
          <?php if($result['type'] === 'thread'): ?>
            <div class="search-result">
              <div class="search-result__type">Thread</div>
              <a href="/thread.php?id=<?=$result['id']?>" class="search-result__title">
                <?=h($result['title'])?>
              </a>
              <div class="search-result__meta">
                by <span class="thread-username"><?=h($result['author'])?></span>
                · <?=relative_time($result['created'])?>
                · <?=(int)$result['reply_count']?> replies
              </div>
            </div>
          <?php else: ?>
            <div class="search-result">
              <div class="search-result__type">Post</div>
              <a href="/thread.php?id=<?=$result['thread_id']?>#post-<?=$result['id']?>" class="search-result__title">
                <?=h($result['thread_title'])?>
              </a>
              <div class="search-result__excerpt">
                <?=h(substr($result['body'], 0, 200))?>...
              </div>
              <div class="search-result__meta">
                by <span class="thread-username"><?=h($result['author'])?></span>
                · <?=relative_time($result['time'])?>
              </div>
            </div>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php footer_html(); ?>
