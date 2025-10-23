<?php
require __DIR__.'/inc/common.php';
csrf_check();

$id = $_GET['id'] ?? '';
$me = current_user();

// Get thread by ID only
$stmt = db()->prepare("SELECT * FROM threads WHERE id = ?");
$stmt->execute([$id]);
$t = $stmt->fetch();

if (!$t){ header_html('thread', 'thread'); echo '<p>Thread not found.</p>'; footer_html(); exit; }

// Get posts early for form handling
$posts = get_posts($t['category_id'] ?? '', $id);
$firstPost = !empty($posts) ? $posts[0] : null;

// Mark thread as read for logged-in users
if ($me) {
  mark_thread_read($me['username'], $t['category_id'] ?? '', $id);
}

// Check if user can edit/delete
$canModerate = $me && (($me['role']??'member')==='admin' || ($me['role']??'member')==='mod');
$isAuthor = $me && $me['username'] === $t['author'];
$canEdit = $isAuthor || $canModerate;

// Handle thread actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if(isset($_POST['action'])){
    switch($_POST['action']){
      case 'delete_thread':
        if($canEdit){
          delete_thread($id);
          header('Location: /index.php');
          exit;
        }
        break;
      case 'edit_thread':
        if($canEdit && !empty($_POST['title']) && !empty($_POST['body'])){
          update_thread($id, trim($_POST['title']));
          // Update first post body
          if($firstPost){
            update_post($firstPost['id'], trim($_POST['body']));
          }
          header('Location: /thread.php?id='.urlencode($id));
          exit;
        }
        break;
      case 'delete_post':
        $postId = (int)($_POST['post_id'] ?? 0);
        $post = get_post($postId);
        if($post && ($me['username'] === $post['author'] || $canModerate)){
          delete_post($postId);
          header('Location: /thread.php?id='.urlencode($id));
          exit;
        }
        break;
      case 'edit_post':
        $postId = (int)($_POST['post_id'] ?? 0);
        $post = get_post($postId);
        if($post && !empty($_POST['body']) && ($me['username'] === $post['author'] || $canModerate)){
          update_post($postId, trim($_POST['body']));
          header('Location: /thread.php?id='.urlencode($id));
          exit;
        }
        break;
    }
  } elseif(!empty($_POST['body'])) {
    // Handle post submission
    $me = require_login();
    add_post($t['category_id'] ?? '', $id, $me['username'], trim($_POST['body']));
    header('Location: /thread.php?id='.urlencode($id));
    exit;
  }
}

header_html($t['title'], 'thread');
?>
<h1 class="thread-title"><?=h($t['title'])?></h1>
<div class="thread-first-post">
  <div class="post-avatar">
    <?=user_avatar($t['author'], 56)?>
  </div>
  <div class="thread-first-post__content">
    <div class="post-meta">
      <a href="/profile.php?u=<?=h($t['author'])?>" class="thread-username">@<?=h($t['author'])?></a>
      <span class="thread-item__badge thread-item__badge--started">posted</span>
      <span>at <?=date('H:i', (int)($t['created'] ?? now()))?></span>
      <?php if($canEdit): ?>
        <span class="post-controls">
          <button onclick="document.getElementById('edit-thread-form').style.display='block'; this.parentElement.style.display='none';" class="post-control-btn">Edit</button>
          <form method="post" style="display:inline;" onsubmit="return confirm('Delete this thread and all posts?');">
            <input type="hidden" name="csrf" value="<?=csrf_token()?>">
            <input type="hidden" name="action" value="delete_thread">
            <button type="submit" class="post-control-btn post-control-btn--danger">Delete</button>
          </form>
        </span>
      <?php endif; ?>
    </div>
    <?php if ($firstPost): ?>
    <div class="thread-first-post__body post-body" id="first-post-body">
      <?=render_text($firstPost['body'] ?? '')?>
    </div>
    <?php if($canEdit): ?>
    <div id="edit-thread-form" class="edit-form" style="display:none;">
      <form method="post" class="edit-form__form">
        <input type="hidden" name="csrf" value="<?=csrf_token()?>">
        <input type="hidden" name="action" value="edit_thread">
        <div class="edit-form__group">
          <label class="edit-form__label">Thread Title</label>
          <input 
            type="text" 
            name="title" 
            value="<?=h($t['title'])?>"
            class="edit-form__input"
            required
          >
        </div>
        <div class="edit-form__group">
          <label class="edit-form__label">Post Body</label>
          <textarea name="body" rows="10" class="edit-form__textarea" required data-markdown><?=h($firstPost['body'])?></textarea>
        </div>
        <div class="edit-form__actions">
          <button type="submit" class="btn btn--primary">Save</button>
          <button type="button" onclick="document.getElementById('edit-thread-form').style.display='none'; this.closest('.thread-first-post__content').querySelector('.post-controls').style.display='';" class="btn">Cancel</button>
        </div>
      </form>
    </div>
    <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<?php
if (!empty($posts) && count($posts) > 1):
  for ($i = 1; $i < count($posts); $i++):
    $p = $posts[$i];
?>
  <div class="post" id="post-<?=$p['id']?>">
    <div class="post-avatar">
      <?=user_avatar($p['author'], 40)?>
    </div>
    <div class="post-content">
      <div class="post-meta">
        <a href="/profile.php?u=<?=h($p['author'])?>" class="thread-username">@<?=h($p['author'])?></a>
        <span class="thread-item__badge thread-item__badge--replied">replied</span>
        <span>at <?=date('H:i', (int)($p['time'] ?? now()))?></span>
        <?php if($me && ($me['username'] === $p['author'] || $canModerate)): ?>
          <span class="post-controls">
            <button onclick="document.getElementById('edit-post-<?=$p['id']?>').style.display='block'; this.parentElement.style.display='none';" class="post-control-btn">Edit</button>
            <form method="post" style="display:inline;" onsubmit="return confirm('Delete this post?');">
              <input type="hidden" name="csrf" value="<?=csrf_token()?>">
              <input type="hidden" name="action" value="delete_post">
              <input type="hidden" name="post_id" value="<?=$p['id']?>">
              <button type="submit" class="post-control-btn post-control-btn--danger">Delete</button>
            </form>
          </span>
        <?php endif; ?>
      </div>
      <div class="post-body" id="post-body-<?=$p['id']?>"><?=render_text($p['body'] ?? '')?></div>
      <?php if($me && ($me['username'] === $p['author'] || $canModerate)): ?>
      <div id="edit-post-<?=$p['id']?>" class="edit-form" style="display:none; margin-top:12px;">
        <form method="post">
          <input type="hidden" name="csrf" value="<?=csrf_token()?>">
          <input type="hidden" name="action" value="edit_post">
          <input type="hidden" name="post_id" value="<?=$p['id']?>">
          <textarea name="body" rows="6" class="admin-form__textarea" required data-markdown><?=h($p['body'])?></textarea>
          <div style="margin-top:12px;">
            <button type="submit" class="btn btn--primary" style="margin-top:0; margin-right:8px;">Save</button>
            <button type="button" onclick="document.getElementById('edit-post-<?=$p['id']?>').style.display='none'; this.closest('.post-meta').querySelector('.post-controls').style.display='';" class="btn">Cancel</button>
          </div>
        </form>
      </div>
      <?php endif; ?>
    </div>
  </div>
<?php endfor; endif; ?>

<?php $me = current_user(); if($me): ?>
<section class="reply-section">
  <h3 class="reply-section__title">Post a Reply</h3>
  <form method="post" class="reply-form">
    <input type="hidden" name="csrf" value="<?=csrf_token()?>">
    <div class="reply-form__group">
      <textarea 
        name="body" 
        rows="8" 
        class="reply-form__textarea"
        placeholder="" 
        required 
        data-markdown
      ></textarea>
    </div>
    <div class="reply-form__actions">
      <button type="submit" class="btn btn--primary">Post Reply</button>
    </div>
  </form>
</section>
<?php endif; ?>

<?php footer_html(); ?>
