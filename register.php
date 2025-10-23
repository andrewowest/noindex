<?php
require __DIR__.'/inc/common.php';
$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  csrf_check();
  $invite = trim($_POST['invite'] ?? '');
  $u = trim($_POST['u'] ?? '');
  $p = $_POST['p'] ?? '';

  // Check if invite code is valid
  $stmt = db()->prepare("SELECT username FROM users WHERE invite_code = ?");
  $stmt->execute([$invite]);
  $inviter = $stmt->fetch();

  if(!$inviter){
    $err='invalid invite';
  } elseif(get_user($u)){
    $err='username taken';
  } else {
    $newUser = [
      'username' => $u,
      'pass' => password_hash($p, PASSWORD_DEFAULT),
      'role' => 'member',
      'joined' => now(),
      'invite_code' => strtoupper(bin2hex(random_bytes(5))),
      'invited_by' => $inviter['username'],
      'bio' => '',
      'avatar' => '',
      'last_seen' => now()
    ];
    save_user($newUser);
    $_SESSION['u']=$u;
    if(!headers_sent()) header('Location: /index.php');
    exit;
  }
}
header_html('register', 'register'); ?>
<h1>Register</h1>
<?php if($err): ?><div class="meta" style="color:#f77;"><?=h($err)?></div><?php endif; ?>
<form method="post">
  <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
  <label>Invite code</label>
  <input name="invite" required>
  <label>Username</label>
  <input name="u" required>
  <label>Password</label>
  <input type="password" name="p" required>
  <div style="margin-top:10px;"><button class="btn">create account</button></div>
</form>
<?php footer_html(); ?>
