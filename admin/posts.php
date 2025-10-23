<?php
require __DIR__.'/../inc/common.php';
$me = require_login();
if( (($me['role']??'member')!=='admin') && (($me['role']??'member')!=='mod') ){ 
  if(!headers_sent()) header('Location: /index.php'); 
  exit; 
}

$db = db();
$success = '';
$error = '';
$editPost = null;
$threadId = $_GET['thread_id'] ?? '';

// Get thread info
$thread = $db->query("SELECT * FROM threads WHERE id = ".(int)$threadId)->fetch();
if(!$thread){
  header('Location: /admin/content.php');
  exit;
}

// Handle post edit
if(isset($_GET['edit_post'])){
  $editPost = get_post((int)$_GET['edit_post']);
}

// Handle form submissions
if($_SERVER['REQUEST_METHOD'] === 'POST'){
  csrf_check();
  
  if(isset($_POST['action'])){
    switch($_POST['action']){
      case 'update_post':
        $postId = (int)$_POST['post_id'];
        $body = trim($_POST['body'] ?? '');
        if($body){
          update_post($postId, $body);
          $success = 'Post updated successfully!';
          $editPost = null;
        } else {
          $error = 'Post body cannot be empty';
        }
        break;
        
      case 'delete_post':
        $postId = (int)$_POST['post_id'];
        delete_post($postId);
        $success = 'Post deleted successfully!';
        break;
    }
  }
}

// Get all posts in thread
$posts = get_posts($thread['category_id'], $threadId, 1000);

header_html('Manage Posts - ' . $thread['title'], 'admin');
?>

<div class="admin-header">
  <nav class="admin-nav">
    <a href="/admin/index.php" class="admin-nav__link">Dashboard</a>
    <a href="/admin/settings.php" class="admin-nav__link">Settings</a>
    <a href="/admin/users.php" class="admin-nav__link">Users</a>
    <a href="/admin/content.php" class="admin-nav__link">Content</a>
  </nav>
</div>

<div style="margin-bottom:24px;">
  <a href="/admin/content.php" class="admin-section__link">← Back to Content</a>
</div>

<div class="admin-section">
  <h2 class="admin-section__title">Thread: <?=h($thread['title'])?></h2>
  <div class="admin-list-item__meta">
    by @<?=h($thread['author'])?> • <?=date('Y-m-d H:i', $thread['created'])?> • <?=count($posts)?> posts
  </div>
</div>

<?php if($success): ?>
<div class="admin-alert admin-alert--success">
  <?=h($success)?>
</div>
<?php endif; ?>

<?php if($error): ?>
<div class="admin-alert admin-alert--error">
  <?=h($error)?>
</div>
<?php endif; ?>

<?php if($editPost): ?>
<div class="admin-section">
  <h2 class="admin-section__title">Edit Post</h2>
  <form method="post" class="admin-form">
    <input type="hidden" name="csrf" value="<?=csrf_token()?>">
    <input type="hidden" name="action" value="update_post">
    <input type="hidden" name="post_id" value="<?=$editPost['id']?>">
    
    <div class="admin-form__group">
      <label for="body" class="admin-form__label">Post Body</label>
      <textarea 
        id="body" 
        name="body" 
        rows="10"
        class="admin-form__textarea"
        required
      ><?=h($editPost['body'])?></textarea>
      <div class="admin-form__help">Markdown supported</div>
    </div>

    <div class="admin-form__group">
      <label class="admin-form__label">Author</label>
      <div>@<?=h($editPost['author'])?></div>
    </div>

    <div class="admin-form__group">
      <label class="admin-form__label">Posted</label>
      <div><?=date('Y-m-d H:i', $editPost['time'])?></div>
    </div>

    <div class="admin-form__actions">
      <button type="submit" class="btn btn--primary">Update Post</button>
      <a href="/admin/posts.php?thread_id=<?=$threadId?>" class="btn">Cancel</a>
    </div>
  </form>
</div>
<?php endif; ?>

<div class="admin-section">
  <h2 class="admin-section__title">All Posts (<?=count($posts)?>)</h2>
  <div class="admin-table-wrapper">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Author</th>
          <th>Body Preview</th>
          <th>Posted</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($posts as $p): ?>
        <tr>
          <td>
            <a href="/profile.php?u=<?=h($p['author'])?>">@<?=h($p['author'])?></a>
          </td>
          <td>
            <?php 
              $preview = strip_tags($p['body']);
              $preview = mb_strlen($preview) > 80 ? mb_substr($preview, 0, 80) . '...' : $preview;
              echo h($preview);
            ?>
          </td>
          <td><?=date('Y-m-d H:i', $p['time'])?></td>
          <td>
            <a href="/admin/posts.php?thread_id=<?=$threadId?>&edit_post=<?=$p['id']?>" class="admin-table__action">Edit</a>
            <form method="post" style="display:inline;" onsubmit="return confirm('Delete this post?');">
              <input type="hidden" name="csrf" value="<?=csrf_token()?>">
              <input type="hidden" name="action" value="delete_post">
              <input type="hidden" name="post_id" value="<?=$p['id']?>">
              <button type="submit" class="admin-table__action admin-table__action--danger">Delete</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php footer_html(); ?>
