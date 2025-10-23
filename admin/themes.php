<?php
require __DIR__.'/../inc/common.php';
require __DIR__.'/../inc/fineprint/runtime.php';

$me = require_login();
if( (($me['role']??'member')!=='admin') && (($me['role']??'member')!=='mod') ){ 
  if(!headers_sent()) header('Location: /index.php'); 
  exit; 
}

$themes = \Fineprint\list_themes();
$currentTheme = $_GET['theme'] ?? ($themes[0] ?? 'default');
$action = $_GET['action'] ?? 'edit';

// Handle save
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
  $css = $_POST['css'] ?? '';
  // Save only CSS, keep existing template
  $theme = \Fineprint\load_theme($currentTheme);
  \Fineprint\save_theme($currentTheme, $theme->getTemplate(), $css);
  header('Location: /admin/themes.php?theme=' . urlencode($currentTheme) . '&saved=1');
  exit;
}

// Handle upload
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['theme_zip'])) {
  $uploadedFile = $_FILES['theme_zip']['tmp_name'];
  $themeName = \Fineprint\upload_theme($uploadedFile);
  if($themeName) {
    header('Location: /admin/themes.php?theme=' . urlencode($themeName) . '&uploaded=1');
    exit;
  }
}

// Handle activate theme
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['activate'])) {
  $themeToActivate = $_POST['theme_to_activate'] ?? '';
  if($themeToActivate && in_array($themeToActivate, \Fineprint\list_themes())) {
    $settings = settings();
    $settings['active_theme'] = $themeToActivate;
    save_settings($settings);
    header('Location: /admin/themes.php?activated=1');
    exit;
  }
}

$theme = \Fineprint\load_theme($currentTheme);
$activeTheme = settings()['active_theme'] ?? 'default';

header_html('Themes', 'admin');
?>

<div class="admin-header">
  <nav class="admin-nav">
    <a href="/admin/index.php" class="admin-nav__link">Dashboard</a>
    <a href="/admin/settings.php" class="admin-nav__link">Settings</a>
    <a href="/admin/users.php" class="admin-nav__link">Users</a>
    <a href="/admin/content.php" class="admin-nav__link">Content</a>
    <a href="/admin/themes.php" class="admin-nav__link admin-nav__link--active">Themes</a>
  </nav>
</div>

<?php if(isset($_GET['saved'])): ?>
  <div class="alert alert--success">Theme saved successfully!</div>
<?php endif; ?>

<?php if(isset($_GET['uploaded'])): ?>
  <div class="alert alert--success">Theme uploaded successfully!</div>
<?php endif; ?>

<?php if(isset($_GET['created'])): ?>
  <div class="alert alert--success">Theme created successfully!</div>
<?php endif; ?>

<?php if(isset($_GET['activated'])): ?>
  <div class="alert alert--success">Theme activated! Refresh to see changes.</div>
<?php endif; ?>

<div class="theme-editor">
  <div class="theme-editor__top">
    <div class="theme-selector">
      <h3>Select Theme</h3>
      <div class="theme-selector__controls">
        <select id="theme-select" class="theme-selector__select" onchange="window.location.href='/admin/themes.php?theme='+encodeURIComponent(this.value)">
          <?php foreach($themes as $themeName): ?>
            <option value="<?=h($themeName)?>" <?=$themeName === $currentTheme ? 'selected' : ''?>>
              <?=h($themeName)?><?=$themeName === $activeTheme ? ' (Active)' : ''?>
            </option>
          <?php endforeach; ?>
        </select>
        
        <?php if($currentTheme !== $activeTheme): ?>
          <form method="post" style="margin:0;">
            <input type="hidden" name="theme_to_activate" value="<?=h($currentTheme)?>">
            <button type="submit" name="activate" class="btn btn--primary">
              Activate
            </button>
          </form>
        <?php else: ?>
          <span class="theme-active-badge">✓ Active</span>
        <?php endif; ?>
      </div>
    </div>
    
    <div class="theme-actions">
      <h3>Upload Theme</h3>
      <form method="post" enctype="multipart/form-data" class="upload-form">
        <label class="file-upload">
          <input type="file" name="theme_zip" accept=".zip" required style="display:none;" onchange="this.parentElement.querySelector('.file-upload__name').textContent = this.files[0]?.name || 'Choose ZIP file'">
          <span class="file-upload__button">Choose File</span>
          <span class="file-upload__name">Choose ZIP file</span>
        </label>
        <button type="submit" class="btn btn--primary">Upload</button>
      </form>
    </div>
  </div>
  
  <div class="theme-editor__main">
    <form method="post" class="theme-form">
      <div class="theme-form__section">
        <label class="theme-form__label">
          Custom Styles (CSS)
          <span class="theme-form__hint">Add your custom CSS to override default styles</span>
        </label>
        <textarea name="css" class="theme-form__textarea theme-form__textarea--code" rows="30"><?=h($theme->getCss())?></textarea>
      </div>
      
      <div class="theme-form__actions">
        <button type="submit" name="save" class="btn btn--primary">Save Styles</button>
      </div>
    </form>
    
    <div class="theme-info">
      <h3>Available CSS Variables</h3>
      <div class="css-vars">
        <code>--ink</code> <span>Main text color</span><br>
        <code>--ink-dim</code> <span>Secondary text</span><br>
        <code>--ink-muted</code> <span>Muted text</span><br>
        <code>--line</code> <span>Border color</span><br>
        <code>--box</code> <span>Box background</span><br>
        <code>--bg</code> <span>Page background</span><br>
        <code>--accent</code> <span>Accent color (purple)</span><br>
        <code>--accent-hover</code> <span>Accent hover</span><br>
        <code>--font-body</code> <span>Body font</span><br>
        <code>--font-mono</code> <span>Monospace font</span>
      </div>
    </div>
  </div>
</div>

<style>
.theme-editor {
  margin-top: 32px;
}

.theme-editor__top {
  display: grid;
  grid-template-columns: 1.5fr 1fr;
  gap: 24px;
  margin-bottom: 32px;
}

.theme-editor__main {
  max-width: 100%;
}

.theme-selector {
  background: rgba(24,27,34,0.3);
  border: 1px solid var(--line);
  border-radius: 6px;
  padding: 16px;
}

.theme-selector h3 {
  margin: 0 0 12px;
  font-size: 14px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.theme-selector__controls {
  display: flex;
  align-items: center;
  gap: 16px;
}

.theme-selector__select {
  flex: 1;
  height: var(--control-height);
  padding: 0 12px;
  margin-top: 12px;
  background: var(--box);
  border: 1px solid var(--line);
  border-radius: 4px;
  color: var(--ink);
  font-size: 12px;
  font-family: var(--font-mono);
  cursor: pointer;
  transition: border-color 0.2s ease;
}

.theme-selector__select:hover {
  border-color: var(--accent);
}

.theme-selector__select:focus {
  outline: none;
  border-color: var(--accent);
}

.theme-active-badge {
  height: var(--control-height);
  padding: 0 16px;
  margin-top: 12px;
  background: rgba(34,197,94,0.1);
  border: 1px solid rgba(34,197,94,0.3);
  border-radius: 4px;
  font-size: 12px;
  font-family: var(--font-mono);
  color: #4ade80;
  white-space: nowrap;
  display: inline-flex;
  align-items: center;
}

.theme-actions {
  background: rgba(24,27,34,0.3);
  border: 1px solid var(--line);
  border-radius: 6px;
  padding: 16px;
}

.theme-actions h3 {
  margin: 0 0 12px;
  font-size: 14px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.upload-form {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.file-upload {
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
}

.file-upload__button {
  padding: 8px 16px;
  background: rgba(109,93,255,0.15);
  border: 1px solid var(--accent);
  border-radius: 4px;
  color: var(--accent);
  font-size: 12px;
  font-family: var(--font-mono);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  transition: all 0.2s ease;
}

.file-upload:hover .file-upload__button {
  background: rgba(109,93,255,0.25);
}

.file-upload__name {
  font-size: 12px;
  color: var(--ink-dim);
  font-family: var(--font-mono);
}

.theme-form {
  background: rgba(24,27,34,0.3);
  border: 1px solid var(--line);
  border-radius: 6px;
  padding: 24px;
}

.theme-form__section {
  margin-bottom: 24px;
}

.theme-form__label {
  display: block;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 8px;
  color: var(--ink);
}

.theme-form__hint {
  display: block;
  font-size: 11px;
  font-weight: 400;
  text-transform: none;
  letter-spacing: 0;
  color: var(--ink-dim);
  margin-top: 4px;
}

.theme-form__textarea {
  width: 100%;
  padding: 12px;
  border: 1px solid var(--line);
  background: var(--box);
  color: var(--ink);
  font-size: 13px;
  font-family: var(--font-body);
  border-radius: 4px;
  resize: vertical;
}

.theme-form__textarea--code {
  font-family: var(--font-mono);
  font-size: 12px;
  line-height: 1.6;
  scrollbar-width: thin;
  scrollbar-color: rgba(109,93,255,0.3) rgba(24,27,34,0.5);
}

.theme-form__textarea--code::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

.theme-form__textarea--code::-webkit-scrollbar-track {
  background: rgba(24,27,34,0.5);
  border-radius: 4px;
}

.theme-form__textarea--code::-webkit-scrollbar-thumb {
  background: rgba(109,93,255,0.3);
  border-radius: 4px;
}

.theme-form__textarea--code::-webkit-scrollbar-thumb:hover {
  background: rgba(109,93,255,0.5);
}

.theme-form__textarea:focus {
  outline: none;
  border-color: var(--accent);
  background: rgba(24,27,34,0.8);
}

.theme-form__actions {
  display: flex;
  gap: 12px;
}

.alert {
  padding: 16px 20px;
  border-radius: 6px;
  margin-bottom: 24px;
  font-size: 13px;
  font-family: var(--font-mono);
  border: 1px solid;
}

.alert--success {
  background: rgba(34,197,94,0.08);
  border-color: rgba(34,197,94,0.25);
  color: #4ade80;
}

.theme-info {
  margin-top: 32px;
  padding: 24px;
  background: rgba(24,27,34,0.2);
  border: 1px solid var(--line);
  border-radius: 6px;
}

.theme-info h3 {
  margin: 0 0 16px;
  font-size: 14px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--ink);
}

.css-vars {
  font-size: 13px;
  line-height: 2;
}

.css-vars code {
  display: inline-block;
  min-width: 140px;
  padding: 2px 8px;
  background: rgba(109,93,255,0.1);
  border: 1px solid rgba(109,93,255,0.2);
  border-radius: 3px;
  font-family: var(--font-mono);
  font-size: 12px;
  color: var(--accent);
}

.css-vars span {
  color: var(--ink-dim);
  font-size: 12px;
}
</style>

<?php footer_html(); ?>
