<?php
require __DIR__.'/../inc/common.php';
$me = require_login();
if( (($me['role']??'member')!=='admin') && (($me['role']??'member')!=='mod') ){ 
  if(!headers_sent()) header('Location: /index.php'); 
  exit; 
}

$success = false;
$error = '';
$editUser = null;

// Handle edit request
if(isset($_GET['edit'])){
  $editUser = get_user($_GET['edit']);
}

// Handle form submission
if($_SERVER['REQUEST_METHOD'] === 'POST'){
  csrf_check();
  
  if(isset($_POST['action']) && $_POST['action'] === 'update_role'){
    $username = $_POST['username'] ?? '';
    $newRole = $_POST['role'] ?? 'member';
    
    $user = get_user($username);
    if($user){
      $user['role'] = $newRole;
      save_user($user);
      $success = true;
      $editUser = null;
    } else {
      $error = 'User not found';
    }
  }
}

$allUsers = users();

header_html('Admin Users', 'admin');
?>

<div class="admin-header">
  <nav class="admin-nav">
    <a href="/admin/index.php" class="admin-nav__link">Dashboard</a>
    <a href="/admin/settings.php" class="admin-nav__link">Settings</a>
    <a href="/admin/users.php" class="admin-nav__link admin-nav__link--active">Users</a>
    <a href="/admin/content.php" class="admin-nav__link">Content</a>
    <a href="/admin/themes.php" class="admin-nav__link">Themes</a>
  </nav>
</div>

<?php if($success): ?>
<div class="admin-alert admin-alert--success">
  User updated successfully!
</div>
<?php endif; ?>

<?php if($error): ?>
<div class="admin-alert admin-alert--error">
  <?=h($error)?>
</div>
<?php endif; ?>

<?php if($editUser): ?>
<div class="admin-section">
  <h2 class="admin-section__title">Edit User: @<?=h($editUser['username'])?></h2>
  <form method="post" class="admin-form">
    <input type="hidden" name="csrf" value="<?=csrf_token()?>">
    <input type="hidden" name="action" value="update_role">
    <input type="hidden" name="username" value="<?=h($editUser['username'])?>">
    
    <div class="admin-form__group">
      <label for="role" class="admin-form__label">Role</label>
      <select id="role" name="role" class="admin-form__input">
        <option value="member" <?=($editUser['role']??'member')==='member'?'selected':''?>>Member</option>
        <option value="mod" <?=($editUser['role']??'member')==='mod'?'selected':''?>>Moderator</option>
        <option value="admin" <?=($editUser['role']??'member')==='admin'?'selected':''?>>Admin</option>
      </select>
    </div>

    <div class="admin-form__group">
      <label class="admin-form__label">Invite Code</label>
      <code class="admin-code"><?=h($editUser['invite_code'] ?? 'N/A')?></code>
    </div>

    <div class="admin-form__group">
      <label class="admin-form__label">Joined</label>
      <div><?=date('Y-m-d H:i', $editUser['joined'])?></div>
    </div>

    <div class="admin-form__actions">
      <button type="submit" class="btn btn--primary">Update User</button>
      <a href="/admin/users.php" class="btn">Cancel</a>
    </div>
  </form>
</div>
<?php endif; ?>

<div class="admin-section">
  <h2 class="admin-section__title">All Users (<?=count($allUsers)?>)</h2>
  <div class="admin-table-wrapper">
    <table class="admin-table">
      <thead>
        <tr>
          <th>User</th>
          <th>Role</th>
          <th>Joined</th>
          <th>Invite Code</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($allUsers as $u): ?>
        <tr>
          <td>
            <div style="display:flex; align-items:center; gap:8px;">
              <?=user_avatar($u['username'], 24)?>
              <a href="/profile.php?u=<?=h($u['username'])?>">@<?=h($u['username'])?></a>
            </div>
          </td>
          <td>
            <span class="admin-badge admin-badge--<?=h($u['role']??'member')?>">
              <?=h($u['role'] ?? 'member')?>
            </span>
          </td>
          <td><?=date('Y-m-d', $u['joined'])?></td>
          <td><code><?=h($u['invite_code'] ?? 'N/A')?></code></td>
          <td>
            <a href="/admin/users.php?edit=<?=urlencode($u['username'])?>" class="admin-table__action">Edit</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php footer_html(); ?>
