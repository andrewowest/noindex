<?php
require __DIR__.'/inc/common.php';
$me = require_login();
csrf_check();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['title']) && !empty($_POST['body'])) {
  $title = trim($_POST['title']);
  $body = trim($_POST['body']);
  $threadId = strtolower(preg_replace('/[^a-z0-9]+/', '-', $title));
  $threadId = trim($threadId, '-');
  
  // Create thread
  $thread = [
    'id' => $threadId,
    'cat' => 'general',
    'title' => $title,
    'author' => $me['username'],
    'created' => now()
  ];
  save_thread($thread);
  
  // Add first post
  add_post('general', $threadId, $me['username'], $body);
  
  header('Location: /thread.php?id='.urlencode($threadId));
  exit;
}

header_html('New Thread', 'new_thread');
?>
<form method="post" class="thread-form">
  <input type="hidden" name="csrf" value="<?=csrf_token()?>">
  
  <div class="thread-form__group">
    <label for="thread-title" class="thread-form__label">Thread Title</label>
    <input 
      type="text" 
      id="thread-title"
      name="title" 
      class="thread-form__input"
      placeholder=""
      required 
      autofocus
    >
  </div>
  
  <div class="thread-form__group">
    <label for="thread-body" class="thread-form__label">Post Body</label>
    <textarea 
      id="thread-body"
      name="body" 
      rows="12" 
      class="thread-form__textarea"
      placeholder=""
      required 
      data-markdown
    ></textarea>
  </div>
  
  <div class="thread-form__actions">
    <button type="submit" class="btn btn--primary">Create Thread</button>
    <a href="/index.php" class="btn">Cancel</a>
  </div>
</form>

<?php footer_html(); ?>
