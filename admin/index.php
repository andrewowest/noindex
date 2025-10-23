<?php
require __DIR__.'/../inc/common.php';
$me = require_login();
if( (($me['role']??'member')!=='admin') && (($me['role']??'member')!=='mod') ){ 
  if(!headers_sent()) header('Location: /index.php'); 
  exit; 
}

// Get stats
$db = db();
$totalUsers = $db->query("SELECT COUNT(*) as count FROM users")->fetch()['count'];
$totalThreads = $db->query("SELECT COUNT(*) as count FROM threads")->fetch()['count'];
$totalPosts = $db->query("SELECT COUNT(*) as count FROM posts")->fetch()['count'];
$recentUsers = $db->query("SELECT username, joined, role FROM users ORDER BY joined DESC LIMIT 5")->fetchAll();
$recentThreads = $db->query("SELECT id, title, author, created FROM threads ORDER BY created DESC LIMIT 5")->fetchAll();

header_html('Admin Dashboard', 'admin'); 
?>

<div class="admin-header">
  <nav class="admin-nav">
    <a href="/admin/index.php" class="admin-nav__link admin-nav__link--active">Dashboard</a>
    <a href="/admin/settings.php" class="admin-nav__link">Settings</a>
    <a href="/admin/users.php" class="admin-nav__link">Users</a>
    <a href="/admin/content.php" class="admin-nav__link">Content</a>
    <a href="/admin/themes.php" class="admin-nav__link">Themes</a>
  </nav>
</div>

<div class="admin-stats">
  <div class="admin-stat-card">
    <div class="admin-stat-card__value"><?=$totalUsers?></div>
    <div class="admin-stat-card__label">Total Users</div>
  </div>
  <div class="admin-stat-card">
    <div class="admin-stat-card__value"><?=$totalThreads?></div>
    <div class="admin-stat-card__label">Total Threads</div>
  </div>
  <div class="admin-stat-card">
    <div class="admin-stat-card__value"><?=$totalPosts?></div>
    <div class="admin-stat-card__label">Total Posts</div>
  </div>
  <div class="admin-stat-card">
    <div class="admin-stat-card__value"><?=count(online_users())?></div>
    <div class="admin-stat-card__label">Online Now</div>
  </div>
</div>

<div class="admin-grid">
  <div class="admin-section">
    <h2 class="admin-section__title">Recent Users</h2>
    <div class="admin-list">
      <?php foreach($recentUsers as $u): ?>
        <div class="admin-list-item">
          <div class="admin-list-item__main">
            <?=user_avatar($u['username'], 32)?>
            <div class="admin-list-item__content">
              <div class="admin-list-item__title">
                <a href="/profile.php?u=<?=h($u['username'])?>">@<?=h($u['username'])?></a>
              </div>
              <div class="admin-list-item__meta">
                <?=h($u['role'] ?? 'member')?> • joined <?=date('Y-m-d', $u['joined'])?>
              </div>
            </div>
          </div>
          <a href="/admin/users.php?edit=<?=urlencode($u['username'])?>" class="admin-list-item__action">Edit</a>
        </div>
      <?php endforeach; ?>
    </div>
    <a href="/admin/users.php" class="admin-section__link">View all users →</a>
  </div>

  <div class="admin-section">
    <h2 class="admin-section__title">Recent Threads</h2>
    <div class="admin-list">
      <?php foreach($recentThreads as $t): ?>
        <div class="admin-list-item">
          <div class="admin-list-item__content">
            <div class="admin-list-item__title">
              <a href="/thread.php?id=<?=h($t['id'])?>"><?=h($t['title'])?></a>
            </div>
            <div class="admin-list-item__meta">
              by @<?=h($t['author'])?> • <?=date('Y-m-d H:i', $t['created'])?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <a href="/admin/content.php" class="admin-section__link">Manage content →</a>
  </div>
</div>

<?php footer_html(); ?>
