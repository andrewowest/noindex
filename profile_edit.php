<?php
require __DIR__.'/inc/common.php';
$me = require_login();
$err=''; $ok='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  csrf_check();
  $user = get_user($me['username']);

  if(isset($_POST['bio'])){
    $user['bio']=substr(trim($_POST['bio']),0,2000);
    $ok='saved';
  }
  
  if(isset($_POST['timezone'])){
    $user['timezone']=trim($_POST['timezone']);
    $ok='saved';
  }

  if(!empty($_FILES['avatar']['name']) && $_FILES['avatar']['error']===UPLOAD_ERR_OK){
    $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
    if(!in_array($ext,['png','jpg','jpeg','gif','webp'])){ $err='invalid image'; }
    else{
      if(!is_dir(UPLOADS.'/avatars')) @mkdir(UPLOADS.'/avatars',0775,true);
      $fn = $me['username'].'_'.bin2hex(random_bytes(4)).'.'.$ext;
      move_uploaded_file($_FILES['avatar']['tmp_name'], UPLOADS.'/avatars/'.$fn);
      $user['avatar']=$fn;
      $ok='saved';
    }
  }

  save_user($user);
}
$me = current_user();
header_html('Edit Profile', 'edit_profile'); ?>
<div class="profile-edit-form">
  <?php if($ok): ?><div class="profile-edit-message profile-edit-message--success"><?=$ok?></div><?php endif; ?>
  <?php if($err): ?><div class="profile-edit-message profile-edit-message--error"><?=$err?></div><?php endif; ?>

  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
    <div class="profile-edit-avatar">
      <div class="avatar"><?php if(!empty($me['avatar'])): ?><img src="/uploads/avatars/<?=h($me['avatar'])?>"><?php else: ?><?=strtoupper(substr($me['username'],0,1))?><?php endif; ?></div>
      <label><input type="file" name="avatar" style="display:none;" onchange="this.form.submit();"><a href="#" onclick="this.previousElementSibling.click();return false;">Upload Avatar</a></label>
    </div>
    <label>Bio</label>
    <textarea name="bio" rows="6" placeholder="Write a short bio…" data-markdown><?=h($me['bio'] ?? '')?></textarea>
    
    <label>Timezone</label>
    <select name="timezone" class="profile-edit-select">
      <?php
      $timezones = timezone_identifiers_list();
      $userTz = $me['timezone'] ?? 'UTC';
      foreach($timezones as $tz){
        $selected = ($tz === $userTz) ? 'selected' : '';
        echo '<option value="'.h($tz).'" '.$selected.'>'.h($tz).'</option>';
      }
      ?>
    </select>
    
    <div class="profile-edit-actions"><button class="btn btn--primary">Save</button></div>
  </form>
</div>

<?php footer_html(); ?>
