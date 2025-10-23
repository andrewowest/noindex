<?php
require __DIR__.'/../inc/common.php';
$me = require_login();
if( (($me['role']??'member')!=='admin') && (($me['role']??'member')!=='mod') ){ 
  if(!headers_sent()) header('Location: /index.php'); 
  exit; 
}

$s = settings();
$success = false;

// Handle form submission
if($_SERVER['REQUEST_METHOD'] === 'POST'){
  csrf_check();
  
  // Debug all POST data
  error_log("=== FORM SUBMISSION ===");
  error_log("POST data: " . print_r($_POST, true));
  
  $newSettings = [
    'site_name' => trim($_POST['site_name'] ?? ''),
    'active_theme' => trim($_POST['active_theme'] ?? $s['active_theme'] ?? 'nofollow.club'),
    'items_per_page' => (int)($_POST['items_per_page'] ?? 25),
    'posts_per_page' => (int)($_POST['posts_per_page'] ?? 50),
    'enable_view_latest' => isset($_POST['enable_view_latest']) ? '1' : '0',
    'enable_view_active' => isset($_POST['enable_view_active']) ? '1' : '0',
    'enable_view_top' => isset($_POST['enable_view_top']) ? '1' : '0',
    'enable_filter_active' => isset($_POST['enable_filter_active']) ? '1' : '0',
    'enable_filter_unanswered' => isset($_POST['enable_filter_unanswered']) ? '1' : '0',
    'enable_filter_mine' => isset($_POST['enable_filter_mine']) ? '1' : '0',
  ];
  
  error_log("Settings to save: " . print_r($newSettings, true));
  save_settings($newSettings);
  $s = settings();
  error_log("Theme after save: " . ($s['active_theme'] ?? 'NOT SET'));
  $success = true;
}

header_html('Admin Settings', 'admin');
?>

<div class="admin-header">
  <nav class="admin-nav">
    <a href="/admin/index.php" class="admin-nav__link">Dashboard</a>
    <a href="/admin/settings.php" class="admin-nav__link admin-nav__link--active">Settings</a>
    <a href="/admin/users.php" class="admin-nav__link">Users</a>
    <a href="/admin/content.php" class="admin-nav__link">Content</a>
    <a href="/admin/themes.php" class="admin-nav__link">Themes</a>
  </nav>
</div>

<?php if($success): ?>
<div class="admin-alert admin-alert--success">
  Settings saved successfully!
</div>
<?php endif; ?>

<div class="admin-section">
  <h2 class="admin-section__title">General Settings</h2>
  <form method="post" class="admin-form" onsubmit="console.log('Form submitting...', new FormData(this))">
    <input type="hidden" name="csrf" value="<?=csrf_token()?>">
    
    <div class="admin-form__group">
      <label for="site_name" class="admin-form__label">Site Name</label>
      <input 
        type="text" 
        id="site_name" 
        name="site_name" 
        value="<?=h($s['site_name'] ?? 'longreply.club')?>"
        class="admin-form__input"
        required
      >
      <div class="admin-form__help">The name of your forum</div>
    </div>

    <div class="admin-form__group">
      <label for="items_per_page" class="admin-form__label">Threads Per Page</label>
      <input 
        type="number" 
        id="items_per_page" 
        name="items_per_page" 
        value="<?=h($s['items_per_page'] ?? 25)?>"
        class="admin-form__input"
        min="1"
        max="100"
        required
      >
      <div class="admin-form__help">Initial number of threads to load (Load More button will fetch more)</div>
    </div>

    <div class="admin-form__group">
      <label for="active_theme" class="admin-form__label">Active Theme</label>
      <select 
        id="active_theme" 
        name="active_theme" 
        class="admin-form__input"
      >
        <?php
        require_once __DIR__.'/../inc/fineprint/runtime.php';
        $themes = \Fineprint\list_themes();
        $activeTheme = $s['active_theme'] ?? 'default';
        error_log("Active theme from DB: " . $activeTheme);
        error_log("Available themes: " . implode(', ', $themes));
        foreach($themes as $theme):
        ?>
          <option value="<?=h($theme)?>" <?=($theme === $activeTheme ? 'selected' : '')?>>
            <?=h($theme)?>
          </option>
        <?php endforeach; ?>
      </select>
      <div class="admin-form__help">Choose which theme to use for the forum</div>
    </div>

    <div class="admin-form__group">
      <label for="posts_per_page" class="admin-form__label">Replies Per Page</label>
      <input 
        type="number" 
        id="posts_per_page" 
        name="posts_per_page" 
        value="<?=h($s['posts_per_page'] ?? 50)?>"
        class="admin-form__input"
        min="1"
        max="200"
        required
      >
      <div class="admin-form__help">Number of replies to show per thread page</div>
    </div>

    <div class="admin-form__actions">
      <button type="submit" class="btn btn--primary">Save Settings</button>
    </div>
  </form>
</div>

<div class="admin-section">
  <h2 class="admin-section__title">Forum Views</h2>
  <form method="post" class="admin-form">
    <input type="hidden" name="csrf" value="<?=csrf_token()?>">
    <input type="hidden" name="site_name" value="<?=h($s['site_name'] ?? 'longreply.club')?>">
    <input type="hidden" name="items_per_page" value="<?=h($s['items_per_page'] ?? 25)?>">
    <input type="hidden" name="posts_per_page" value="<?=h($s['posts_per_page'] ?? 50)?>">
    <?php if(($s['enable_filter_active'] ?? '1') === '1'): ?><input type="hidden" name="enable_filter_active" value="1"><?php endif; ?>
    <?php if(($s['enable_filter_unanswered'] ?? '1') === '1'): ?><input type="hidden" name="enable_filter_unanswered" value="1"><?php endif; ?>
    <?php if(($s['enable_filter_mine'] ?? '1') === '1'): ?><input type="hidden" name="enable_filter_mine" value="1"><?php endif; ?>
    
    <div class="admin-form__help" style="margin-bottom: 20px;">Enable or disable different view modes on the homepage</div>
    
    <div class="admin-form__group">
      <label class="admin-form__checkbox">
        <input type="checkbox" name="enable_view_latest" <?=($s['enable_view_latest'] ?? '1') === '1' ? 'checked' : ''?>>
        <span>Enable "Latest" view</span>
      </label>
      <div class="admin-form__help">Shows threads grouped by day of last activity</div>
    </div>

    <div class="admin-form__group">
      <label class="admin-form__checkbox">
        <input type="checkbox" name="enable_view_active" <?=($s['enable_view_active'] ?? '1') === '1' ? 'checked' : ''?>>
        <span>Enable "Active" view</span>
      </label>
      <div class="admin-form__help">Shows threads grouped by week, sorted by most replies</div>
    </div>

    <div class="admin-form__group">
      <label class="admin-form__checkbox">
        <input type="checkbox" name="enable_view_top" <?=($s['enable_view_top'] ?? '1') === '1' ? 'checked' : ''?>>
        <span>Enable "Top" view</span>
      </label>
      <div class="admin-form__help">Shows top 50 threads ranked by reply count</div>
    </div>

    <div class="admin-form__actions">
      <button type="submit" class="btn btn--primary">Save Settings</button>
    </div>
  </form>
</div>

<div class="admin-section">
  <h2 class="admin-section__title">Thread Filters</h2>
  <form method="post" class="admin-form">
    <input type="hidden" name="csrf" value="<?=csrf_token()?>">
    <input type="hidden" name="site_name" value="<?=h($s['site_name'] ?? 'longreply.club')?>">
    <input type="hidden" name="items_per_page" value="<?=h($s['items_per_page'] ?? 25)?>">
    <input type="hidden" name="posts_per_page" value="<?=h($s['posts_per_page'] ?? 50)?>">
    <?php if(($s['enable_view_latest'] ?? '1') === '1'): ?><input type="hidden" name="enable_view_latest" value="1"><?php endif; ?>
    <?php if(($s['enable_view_active'] ?? '1') === '1'): ?><input type="hidden" name="enable_view_active" value="1"><?php endif; ?>
    <?php if(($s['enable_view_top'] ?? '1') === '1'): ?><input type="hidden" name="enable_view_top" value="1"><?php endif; ?>
    
    <div class="admin-form__help" style="margin-bottom: 20px;">Enable or disable different filter options</div>
    
    <div class="admin-form__group">
      <label class="admin-form__checkbox">
        <input type="checkbox" name="enable_filter_active" <?=($s['enable_filter_active'] ?? '1') === '1' ? 'checked' : ''?>>
        <span>Enable "Active" filter</span>
      </label>
      <div class="admin-form__help">Shows all threads with recent activity</div>
    </div>

    <div class="admin-form__group">
      <label class="admin-form__checkbox">
        <input type="checkbox" name="enable_filter_unanswered" <?=($s['enable_filter_unanswered'] ?? '1') === '1' ? 'checked' : ''?>>
        <span>Enable "Unanswered" filter</span>
      </label>
      <div class="admin-form__help">Shows threads with no replies</div>
    </div>

    <div class="admin-form__group">
      <label class="admin-form__checkbox">
        <input type="checkbox" name="enable_filter_mine" <?=($s['enable_filter_mine'] ?? '1') === '1' ? 'checked' : ''?>>
        <span>Enable "My Threads" filter</span>
      </label>
      <div class="admin-form__help">Shows threads started by the logged-in user</div>
    </div>

    <div class="admin-form__actions">
      <button type="submit" class="btn btn--primary">Save Settings</button>
    </div>
  </form>
</div>

<?php footer_html(); ?>
