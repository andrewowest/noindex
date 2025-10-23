<?php
require __DIR__.'/inc/common.php';
$me = current_user(); if(!$me){ if(!headers_sent()) header('Location: /login.php'); exit; }
$u = $_GET['u'] ?? $me['username'];
$view = get_user($u) ?? $me;

header_html($view['username'], 'profile');
$avatarPath = !empty($view['avatar']) ? '/uploads/avatars/'.h($view['avatar']) : '';
?>
<div class="profile-header">
  <div class="profile-header__avatar">
    <?php if($avatarPath): ?>
      <img src="<?=$avatarPath?>" alt="<?=h($view['username'])?>">
    <?php else: ?>
      <?=strtoupper(substr($view['username'],0,1))?>
    <?php endif; ?>
  </div>
  <div class="profile-header__content">
    <h1 class="profile-header__name"><?=h($view['username'])?></h1>
    <div class="profile-header__meta">
      <span class="profile-header__role"><?=h($view['role']??'member')?></span>
      <span class="profile-header__separator">•</span>
      <span>Joined <?=date('M j, Y', $view['joined']??now())?></span>
      <?php if(!empty($view['invited_by'])): ?>
        <span class="profile-header__separator">•</span>
        <span>Invited by <a href="/profile.php?u=<?=h($view['invited_by'])?>"><?=h($view['invited_by'])?></a></span>
      <?php endif; ?>
    </div>
    <?php if($view['username']===$me['username']): ?>
      <div class="profile-header__actions">
        <a href="/profile_edit.php" class="profile-header__edit-link">Edit Profile</a>
      </div>
    <?php endif; ?>
    <?php if ($view['username']===$me['username'] && !empty($view['invite_code'])): ?>
      <div class="profile-header__invite">
        <span class="profile-header__invite-label">Invite code:</span>
        <code class="profile-header__invite-code"><?=h($view['invite_code'])?></code>
      </div>
    <?php endif; ?>
  </div>
</div>

<section class="profile-section">
  <h2 class="profile-section__title">Threads by <?=h($view['username'])?></h2>
  <ul class="profile-threads">
  <?php
  $stmt = db()->prepare("SELECT * FROM threads WHERE author = ? ORDER BY created DESC");
  $stmt->execute([$view['username']]);
  $threads = $stmt->fetchAll();

  if(empty($threads)){
    echo '<li class="profile-threads__empty">No threads yet.</li>';
  } else {
    foreach($threads as $t){
      $postCount = (int)($t['post_count'] ?? 0);
      $createdDate = date('M j, Y', (int)($t['created'] ?? now()));
      echo '<li class="profile-thread">';
      echo '<a href="/thread.php?id='.h($t['id']).'" class="profile-thread__title">'.h($t['title']).'</a>';
      echo '<div class="profile-thread__meta">';
      echo '<span>'.$createdDate.'</span>';
      echo '<span class="profile-thread__separator">•</span>';
      echo '<span>'.$postCount.' replies</span>';
      echo '</div>';
      echo '</li>';
    }
  }
  ?>
  </ul>
</section>
<?php footer_html(); ?>
