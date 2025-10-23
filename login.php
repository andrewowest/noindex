<?php
require __DIR__.'/inc/common.php';

// Handle login
$loginErr = '';
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) && $_POST['action'] === 'login'){
  csrf_check();
  $u = trim($_POST['u'] ?? '');
  $p = $_POST['p'] ?? '';
  $user = get_user($u);
  if ($user && password_verify($p, $user['pass'])){
    $_SESSION['u'] = $u;
    if (!headers_sent()) header('Location: /index.php');
    exit;
  } else $loginErr = 'Invalid username or password';
}

// Handle registration
$registerErr = '';
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) && $_POST['action'] === 'register'){
  csrf_check();
  $invite = trim($_POST['invite'] ?? '');
  $u = trim($_POST['u'] ?? '');
  $p = $_POST['p'] ?? '';

  // Check if invite code is valid
  $stmt = db()->prepare("SELECT username FROM users WHERE invite_code = ?");
  $stmt->execute([$invite]);
  $inviter = $stmt->fetch();

  if(!$inviter){
    $registerErr = 'Invalid invite code';
  } elseif(get_user($u)){
    $registerErr = 'Username already taken';
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

$s = settings();
$activeTheme = $s['active_theme'] ?? 'nofollow.club';
header_html('Welcome', 'login'); 
?>

<div class="login-wall">
  <div class="login-wall__header">
    <?php if($activeTheme === 'nofollow.club'): ?>
      <a href="/" class="login-wall__brand">nofollow.club</a>
    <?php else: ?>
      <h1 class="login-wall__title"><?=h($s['site_name'] ?? 'Community')?></h1>
      <p class="login-wall__tagline">An invite-only community. A third place.</p>
    <?php endif; ?>
  </div>

  <div class="login-wall__form">
    <div class="auth-form">
      <h2>Join the Community</h2>
      <?php if($registerErr): ?>
        <div class="form-error"><?=h($registerErr)?></div>
      <?php endif; ?>
      <form method="post">
        <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
        <input type="hidden" name="action" value="register">
        <input name="invite" required placeholder="Invite code" autofocus>
        <input name="u" required placeholder="Username">
        <input type="password" name="p" required placeholder="Password">
        <button type="submit" class="btn--primary">Create Account</button>
      </form>
      
      <div class="auth-divider">
        <span>or</span>
      </div>
      
      <?php if($loginErr): ?>
        <div class="form-error"><?=h($loginErr)?></div>
      <?php endif; ?>
      <form method="post" class="auth-form__secondary">
        <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
        <input type="hidden" name="action" value="login">
        <label>Already have an account?</label>
        <input name="u" required placeholder="Username">
        <input type="password" name="p" required placeholder="Password">
        <button type="submit" class="btn--primary">Sign In</button>
      </form>
    </div>
  </div>
</div>
<?php footer_html(); ?>
