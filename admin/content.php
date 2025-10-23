<?php
require __DIR__.'/../inc/common.php';
$me = require_login();
if( (($me['role']??'member')!=='admin') && (($me['role']??'member')!=='mod') ){ 
  if(!headers_sent()) header('Location: /index.php'); 
  exit; 
}

$db = db();

// Get all threads with post counts
$threads = $db->query("
  SELECT t.*, 
    (SELECT COUNT(*) FROM posts p WHERE p.thread_id = t.id) as post_count
  FROM threads t
  ORDER BY t.created DESC
  LIMIT 50
")->fetchAll();

header_html('Admin Content', 'admin');
?>

<div class="admin-header">
  <nav class="admin-nav">
    <a href="/admin/index.php" class="admin-nav__link">Dashboard</a>
    <a href="/admin/settings.php" class="admin-nav__link">Settings</a>
    <a href="/admin/users.php" class="admin-nav__link">Users</a>
    <a href="/admin/content.php" class="admin-nav__link admin-nav__link--active">Content</a>
    <a href="/admin/themes.php" class="admin-nav__link">Themes</a>
  </nav>
</div>

<div class="admin-section">
  <h2 class="admin-section__title">Recent Threads</h2>
  <div class="admin-table-wrapper">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Title</th>
          <th>Author</th>
          <th>Posts</th>
          <th>Created</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($threads as $t): ?>
        <tr>
          <td>
            <a href="/thread.php?id=<?=h($t['id'])?>"><?=h($t['title'])?></a>
          </td>
          <td>
            <a href="/profile.php?u=<?=h($t['author'])?>">@<?=h($t['author'])?></a>
          </td>
          <td><?=$t['post_count']?></td>
          <td><?=date('Y-m-d H:i', $t['created'])?></td>
          <td>
            <a href="/thread.php?id=<?=h($t['id'])?>" class="admin-table__action">View</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php footer_html(); ?>
