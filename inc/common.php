<?php
if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
define('APP_ROOT', __DIR__ . '/..');
define('DATA', APP_ROOT . '/data');
define('UPLOADS', APP_ROOT . '/uploads');
date_default_timezone_set('UTC');

// Database connection
function db() {
  static $pdo = null;
  if ($pdo === null) {
    $dbPath = DATA . '/forum.db';
    if (!file_exists($dbPath)) {
      die('Database not initialized. Run init_db.php first.');
    }
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
  }
  return $pdo;
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function now(){ return time(); }

function relative_time($timestamp){
  $diff = time() - $timestamp;
  if($diff < 60) return 'just now';
  if($diff < 3600) return floor($diff / 60) . 'm ago';
  if($diff < 86400) return floor($diff / 3600) . 'h ago';
  if($diff < 604800) return floor($diff / 86400) . 'd ago';
  if($diff < 2592000) return floor($diff / 604800) . 'w ago';
  return date('M j, Y', $timestamp);
}

// Settings
function settings(){
  $stmt = db()->query("SELECT key, value FROM settings");
  $s = [];
  while ($row = $stmt->fetch()) {
    $s[$row['key']] = $row['value'];
  }
  return $s;
}

function save_settings($settings){
  $db = db();
  foreach ($settings as $key => $value) {
    $stmt = $db->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)");
    $stmt->execute([$key, $value]);
  }
}

// Users
function users(){
  $stmt = db()->query("SELECT * FROM users");
  $users = [];
  while ($row = $stmt->fetch()) {
    $users[$row['username']] = $row;
  }
  return $users;
}

function get_user($username){
  $stmt = db()->prepare("SELECT * FROM users WHERE username = ?");
  $stmt->execute([$username]);
  return $stmt->fetch() ?: null;
}

function save_user($user){
  $db = db();
  $stmt = $db->prepare("
    INSERT OR REPLACE INTO users (username, pass, role, joined, invite_code, invited_by, bio, avatar, last_seen)
    VALUES (:username, :pass, :role, :joined, :invite_code, :invited_by, :bio, :avatar, :last_seen)
  ");
  $stmt->execute([
    ':username' => $user['username'],
    ':pass' => $user['pass'],
    ':role' => $user['role'] ?? 'member',
    ':joined' => $user['joined'],
    ':invite_code' => $user['invite_code'] ?? null,
    ':invited_by' => $user['invited_by'] ?? null,
    ':bio' => $user['bio'] ?? '',
    ':avatar' => $user['avatar'] ?? '',
    ':last_seen' => $user['last_seen'] ?? null
  ]);
}

function current_user(){
  $u = $_SESSION['u'] ?? null;
  if (!$u) return null;
  return get_user($u);
}

function require_login(){
  $u = current_user();
  if (!$u){ if (!headers_sent()) header('Location: /login.php'); exit; }
  return $u;
}

function touch_last_seen(){
  $me = current_user();
  if (!$me) return;
  $stmt = db()->prepare("UPDATE users SET last_seen = ? WHERE username = ?");
  $stmt->execute([now(), $me['username']]);
}

function user_avatar($username, $size = 40) {
  if (!$username) {
    return '<div class="avatar avatar--fallback" style="width:' . $size . 'px; height:' . $size . 'px;"><span>?</span></div>';
  }
  $user = get_user($username);
  $avatar = ($user && isset($user['avatar'])) ? $user['avatar'] : '';
  if ($avatar) {
    $avatarPath = '/uploads/avatars/' . h($avatar);
    return '<img src="' . $avatarPath . '" alt="' . h($username) . '" class="avatar" width="' . $size . '" height="' . $size . '">';
  }
  // Fallback to initials
  $initial = strtoupper(substr($username, 0, 1));
  return '<div class="avatar avatar--fallback" style="width:' . $size . 'px; height:' . $size . 'px;"><span>' . h($initial) . '</span></div>';
}

function online_users($window=300){
  $cutoff = now() - $window;
  $stmt = db()->prepare("SELECT username FROM users WHERE last_seen >= ? ORDER BY username");
  $stmt->execute([$cutoff]);
  $users = [];
  while ($row = $stmt->fetch()) {
    $users[] = $row['username'];
  }
  return $users;
}

// Categories
function categories(){
  $stmt = db()->query("SELECT * FROM categories");
  $cats = [];
  while ($row = $stmt->fetch()) {
    $cats[$row['id']] = [
      'id' => $row['id'],
      'name' => $row['name'],
      'desc' => $row['description']
    ];
  }
  return $cats;
}

function save_category($cat){
  $stmt = db()->prepare("
    INSERT OR REPLACE INTO categories (id, name, description)
    VALUES (?, ?, ?)
  ");
  $stmt->execute([$cat['id'], $cat['name'], $cat['desc'] ?? '']);
}

// Threads
function get_threads_by_category($catId, $limit = 100, $offset = 0){
  $stmt = db()->prepare("
    SELECT * FROM threads
    WHERE category_id = ?
    ORDER BY created DESC
    LIMIT ? OFFSET ?
  ");
  $stmt->execute([$catId, $limit, $offset]);
  return $stmt->fetchAll();
}

function get_thread($catId, $threadId){
  $stmt = db()->prepare("SELECT * FROM threads WHERE category_id = ? AND id = ?");
  $stmt->execute([$catId, $threadId]);
  return $stmt->fetch() ?: null;
}

function save_thread($thread){
  $stmt = db()->prepare("
    INSERT OR REPLACE INTO threads (id, category_id, title, author, created)
    VALUES (?, ?, ?, ?, ?)
  ");
  $stmt->execute([
    $thread['id'],
    $thread['cat'],
    $thread['title'],
    $thread['author'],
    $thread['created']
  ]);
}

// Posts
function get_posts($catId, $threadId, $limit = 50, $offset = 0){
  $stmt = db()->prepare("
    SELECT * FROM posts
    WHERE category_id = ? AND thread_id = ?
    ORDER BY time ASC
    LIMIT ? OFFSET ?
  ");
  $stmt->execute([$catId, $threadId, $limit, $offset]);
  return $stmt->fetchAll();
}

function count_posts($catId, $threadId){
  $stmt = db()->prepare("SELECT COUNT(*) as count FROM posts WHERE category_id = ? AND thread_id = ?");
  $stmt->execute([$catId, $threadId]);
  return (int)$stmt->fetch()['count'];
}

function add_post($catId, $threadId, $author, $body){
  $stmt = db()->prepare("
    INSERT INTO posts (thread_id, category_id, author, time, body)
    VALUES (?, ?, ?, ?, ?)
  ");
  $stmt->execute([$threadId, $catId, $author, now(), $body]);
  return db()->lastInsertId();
}

function mark_thread_read($username, $catId, $threadId){
  if (!$username) return;
  $stmt = db()->prepare("
    INSERT OR REPLACE INTO thread_reads (username, category_id, thread_id, last_read_time)
    VALUES (?, ?, ?, ?)
  ");
  $stmt->execute([$username, $catId, $threadId, now()]);
}

function get_thread_last_read($username, $catId, $threadId){
  if (!$username) return null;
  $stmt = db()->prepare("
    SELECT last_read_time FROM thread_reads
    WHERE username = ? AND category_id = ? AND thread_id = ?
  ");
  $stmt->execute([$username, $catId, $threadId]);
  $result = $stmt->fetch();
  return $result ? (int)$result['last_read_time'] : null;
}

function update_post($postId, $body){
  $stmt = db()->prepare("UPDATE posts SET body = ? WHERE id = ?");
  $stmt->execute([$body, $postId]);
}

function delete_post($postId){
  $stmt = db()->prepare("DELETE FROM posts WHERE id = ?");
  $stmt->execute([$postId]);
}

function get_post($postId){
  $stmt = db()->prepare("SELECT * FROM posts WHERE id = ?");
  $stmt->execute([$postId]);
  return $stmt->fetch() ?: null;
}

function delete_thread($threadId){
  $db = db();
  // Delete all posts in thread first
  $stmt = $db->prepare("DELETE FROM posts WHERE thread_id = ?");
  $stmt->execute([$threadId]);
  // Delete thread
  $stmt = $db->prepare("DELETE FROM threads WHERE id = ?");
  $stmt->execute([$threadId]);
}

function update_thread($threadId, $title){
  $stmt = db()->prepare("UPDATE threads SET title = ? WHERE id = ?");
  $stmt->execute([$title, $threadId]);
}

function get_latest_thread_in_category($catId){
  $stmt = db()->prepare("
    SELECT * FROM threads
    WHERE category_id = ?
    ORDER BY created DESC
    LIMIT 1
  ");
  $stmt->execute([$catId]);
  return $stmt->fetch() ?: null;
}

// CSRF
function csrf_token(){
  if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(16));
  return $_SESSION['csrf'];
}

function csrf_check(){
  if($_SERVER['REQUEST_METHOD']==='POST'){
    $ok = isset($_POST['csrf']) && hash_equals($_SESSION['csrf']??'', $_POST['csrf']);
    if(!$ok){ http_response_code(400); exit('bad csrf'); }
  }
}

// Rendering
function render_text($txt){
  $x = htmlspecialchars((string)$txt, ENT_QUOTES, 'UTF-8');
  $x = preg_replace('/(^|\n)&gt;\s?(.*)/', "$1<blockquote>$2</blockquote>", $x);
  $x = preg_replace('/`([^`]+)`/','<code>$1</code>',$x);
  $x = nl2br($x);
  return $x;
}

function time_since($timestamp){
  $ts = (int)$timestamp;
  if($ts <= 0) return '';
  $now = now();
  $diff = max(0, $now - $ts);
  $units = [
    ['year', 31536000],
    ['month', 2592000],
    ['week', 604800],
    ['day', 86400],
    ['hour', 3600],
    ['minute', 60],
    ['second', 1],
  ];
  foreach($units as [$label, $seconds]){
    if($diff >= $seconds){
      $value = (int)floor($diff / $seconds);
      return $value.' '.$label.($value !== 1 ? 's' : '').' ago';
    }
  }
  return 'just now';
}

function render_text_full($txt){
  $x = htmlspecialchars((string)$txt, ENT_QUOTES, 'UTF-8');
  $x = preg_replace('/(^|\n)&gt;\s?(.*)/', "$1<blockquote>$2</blockquote>", $x);
  $x = preg_replace('/`([^`]+)`/','<code>$1</code>',$x);
  $x = preg_replace('/\\*\\*([^*]+)\\*\\*/','<strong>$1</strong>',$x);
  $x = preg_replace('/\\*([^*]+)\\*/','<em>$1</em>',$x);
  $x = preg_replace('/\\[(.*?)\\]\\((https?:\\/\\/[^\\s)]+)\\)/','<a href="$2" rel="nofollow noopener" target="_blank">$1</a>',$x);
  return nl2br($x);
}

// HTML layout - now uses Fineprint templates
function header_html($title='', $page=''){
  global $_THEME_CONTEXT;
  
  // Load Fineprint blocks (once per request)
  static $blocks_loaded = false;
  if(!$blocks_loaded) {
    require_once __DIR__ . '/fineprint/blocks.php';
    require_once __DIR__ . '/fineprint/packs/forum.php';
    \Fineprint\Packs\Forum\load();
    $blocks_loaded = true;
  }
  
  // Store page name for later
  $_THEME_CONTEXT['page_name'] = $page;
  
  // Update last_seen timestamp for current user
  touch_last_seen();
  
  $u = current_user(); 
  $s = settings(); 
  $site = $s['site_name'] ?? 'Forum';
  $activeTheme = $s['active_theme'] ?? 'nofollow.club';
  
  // Prepare online users for sidebar (always available)
  $online = online_users();
  $onlineHtml = '';
  if(!empty($online)) {
    $links = [];
    foreach($online as $name) {
      $links[] = '<a class="presence-name" href="/profile.php?u='.h($name).'">@'.h($name).'</a>';
    }
    $onlineHtml = implode(", ", $links);
  }
  
  // Store context for footer_html (preserve page_name if already set)
  $existingPageName = $_THEME_CONTEXT['page_name'] ?? '';
  $_THEME_CONTEXT = [
    'page_title' => $title ?: 'Home',
    'site_name' => $site,
    'user' => $u ? true : false,
    'guest' => !$u,
    'is_admin' => $u && (($u['role']??'member')==='admin' || ($u['role']??'member')==='mod'),
    'theme' => $activeTheme,
    'has_online_users' => !empty($online),
    'online_users_html' => $onlineHtml,
    'page_name' => $existingPageName  // Preserve the page name!
  ];
  
  // Using Fineprint - just start output buffering
  // The page template will be rendered in footer_html()
  ob_start();
}

function footer_html(){ 
  global $_THEME_CONTEXT;
  
  // Check if we're using Fineprint templates
  if(isset($_THEME_CONTEXT) && isset($_THEME_CONTEXT['theme'])) {
    try {
      require_once __DIR__ . '/fineprint/runtime.php';
      $theme = \Fineprint\load_theme($_THEME_CONTEXT['theme']);
      
      // Get the buffered content (should be empty for page templates)
      $content = ob_get_clean();
      
      // Use pre-prepared online users from header_html
      $hasOnlineUsers = $_THEME_CONTEXT['has_online_users'] ?? false;
      $onlineHtml = $_THEME_CONTEXT['online_users_html'] ?? '';
      
      // Prepare footer scripts
      $footerScripts = '<script>
// Custom dropdown behavior & TinyMDE markdown editor
document.addEventListener(\'DOMContentLoaded\', () => {
  const dropdowns = document.querySelectorAll(\'[data-dropdown]\');
  
  dropdowns.forEach(dropdown => {
    const trigger = dropdown.querySelector(\'[data-dropdown-trigger]\');
    const menu = dropdown.querySelector(\'[data-dropdown-menu]\');
    
    if (!trigger || !menu) return;
    
    trigger.addEventListener(\'click\', (e) => {
      e.stopPropagation();
      const isOpen = dropdown.classList.contains(\'is-open\');
      
      document.querySelectorAll(\'[data-dropdown].is-open\').forEach(d => {
        if (d !== dropdown) d.classList.remove(\'is-open\');
      });
      
      dropdown.classList.toggle(\'is-open\', !isOpen);
    });
    
    document.addEventListener(\'click\', () => {
      dropdown.classList.remove(\'is-open\');
    });
    
    menu.addEventListener(\'click\', (e) => {
      e.stopPropagation();
    });
  });

  if(window.TinyMDE){
    document.querySelectorAll(\'textarea[data-markdown]\').forEach((textarea) => {
      const editor = new TinyMDE.Editor({ textarea });
      const editorElement = textarea.nextElementSibling;
      if(editorElement){
        const toolbar = document.createElement(\'div\');
        toolbar.className = \'tm-toolbar\';
        editorElement.parentNode.insertBefore(toolbar, editorElement);
        new TinyMDE.CommandBar({
          element: toolbar,
          editor,
          commands: [\'bold\',\'italic\',\'strikethrough\',\'|\',\'h1\',\'h2\',\'|\',\'ul\',\'ol\',\'|\',\'blockquote\',\'hr\',\'|\',\'insertLink\',\'insertImage\']
        });
      }
      
      const form = textarea.closest(\'form\');
      if(form){
        form.addEventListener(\'submit\', () => {
          textarea.value = editor.getContent();
        });
      }
    });
  }
  
  // Load more threads functionality
  const loadMoreContainer = document.getElementById(\'load-more-container\');
  if(loadMoreContainer && document.querySelector(\'.thread-list\')){
    let offset = document.querySelectorAll(\'.thread-list__item\').length;
    const urlParams = new URLSearchParams(window.location.search);
    const currentView = urlParams.get(\'mode\') || urlParams.get(\'view\') || \'latest\';
    let isLoading = false;
    let hasMore = true;
    
    // Create load more button
    const loadMoreBtn = document.createElement(\'button\');
    loadMoreBtn.id = \'load-more-btn\';
    loadMoreBtn.className = \'btn-floating\';
    loadMoreBtn.innerHTML = \'<span style="margin-right:8px;">▼</span> Load More\';
    loadMoreContainer.appendChild(loadMoreBtn);
    
    console.log(\'Load more initialized. Offset:\', offset, \'View:\', currentView);
    
    loadMoreBtn.addEventListener(\'click\', async () => {
      if(isLoading) return;
      
      isLoading = true;
      loadMoreBtn.disabled = true;
      loadMoreBtn.textContent = \'Loading...\';
      
      const url = \'/api/load_more_threads.php?offset=\' + offset + \'&view=\' + currentView;
      console.log(\'Fetching more threads from:\', url);
      console.log(\'Current URL:\', window.location.href);
      console.log(\'View param:\', currentView);
      
      try {
        const response = await fetch(url);
        console.log(\'Response status:\', response.status);
        const data = await response.json();
        console.log(\'Received data:\', data);
        
        // Handle grouped data (latest/active views)
        if(data.groups && data.groups.length > 0){
          const container = document.querySelector(\'.main .container\');
          const loadMoreContainer = document.getElementById(\'load-more-container\');
          
          data.groups.forEach(group => {
            // Check if a section for this date already exists
            let existingSection = null;
            const allSections = document.querySelectorAll(\'.thread-group\');
            allSections.forEach(section => {
              const header = section.querySelector(\'.thread-group__label\');
              if(header && header.textContent === group.heading) {
                existingSection = section;
              }
            });
            
            if(existingSection) {
              // Append to existing date section
              const threadList = existingSection.querySelector(\'.thread-list\');
              group.threads.forEach(thread => {
                const li = document.createElement(\'li\');
                li.className = \'thread-list__item\';
                li.innerHTML = \'<a href="\' + thread.url + \'" class="thread-item">\' +
                  \'<div class="thread-item__title">\' + thread.title + \'</div>\' +
                  \'<div class="thread-item__meta meta">\' +
                    \'<span class="thread-item__meta-piece">\' +
                      \'<span class="thread-username">\' + thread.author + \'</span>\' +
                      \'<span class="thread-item__badge thread-item__badge--posted">posted</span>\' +
                    \'</span>\' +
                    \'<span class="thread-item__meta-piece">\' + thread.last_post_time + \'</span>\' +
                    \'<span class="thread-item__meta-piece">\' + thread.reply_count + \' replies</span>\' +
                  \'</div>\' +
                \'</a>\';
                threadList.appendChild(li);
              });
            } else {
              // Create new date section
              const section = document.createElement(\'section\');
              section.className = \'thread-group\';
              section.innerHTML = \'<header class="thread-group__header">\' +
                \'<h2 class="thread-group__label">\' + group.heading + \'</h2>\' +
              \'</header>\' +
              \'<ul class="thread-list"></ul>\';
              
              const threadList = section.querySelector(\'.thread-list\');
              group.threads.forEach(thread => {
                const li = document.createElement(\'li\');
                li.className = \'thread-list__item\';
                li.innerHTML = \'<a href="\' + thread.url + \'" class="thread-item">\' +
                  \'<div class="thread-item__title">\' + thread.title + \'</div>\' +
                  \'<div class="thread-item__meta meta">\' +
                    \'<span class="thread-item__meta-piece">\' +
                      \'<span class="thread-username">\' + thread.author + \'</span>\' +
                      \'<span class="thread-item__badge thread-item__badge--posted">posted</span>\' +
                    \'</span>\' +
                    \'<span class="thread-item__meta-piece">\' + thread.last_post_time + \'</span>\' +
                    \'<span class="thread-item__meta-piece">\' + thread.reply_count + \' replies</span>\' +
                  \'</div>\' +
                \'</a>\';
                threadList.appendChild(li);
              });
              
              container.insertBefore(section, loadMoreContainer);
            }
            
            offset += group.threads.length;
          });
          
          console.log(\'Added groups. New offset:\', offset);
        }
        // Handle flat data (top view)
        else if(data.threads && data.threads.length > 0){
          const allThreadLists = document.querySelectorAll(\'.thread-list\');
          const threadList = allThreadLists[allThreadLists.length - 1];
          
          if(threadList){
            data.threads.forEach(thread => {
              const li = document.createElement(\'li\');
              li.className = \'thread-list__item\';
              li.innerHTML = \'<a href="\' + thread.url + \'" class="thread-item">\' +
                \'<div class="thread-item__title">\' + thread.title + \'</div>\' +
                \'<div class="thread-item__meta meta">\' +
                  \'<span class="thread-item__meta-piece">\' +
                    \'<span class="thread-username">\' + thread.author + \'</span>\' +
                    \'<span class="thread-item__badge thread-item__badge--started">started</span>\' +
                  \'</span>\' +
                  \'<span class="thread-item__meta-piece">\' + thread.last_post_time + \'</span>\' +
                  \'<span class="thread-item__meta-piece">\' + thread.reply_count + \' posts</span>\' +
                \'</div>\' +
              \'</a>\';
              threadList.appendChild(li);
            });
            
            offset += data.threads.length;
            console.log(\'Added\', data.threads.length, \'threads. New offset:\', offset);
          }
        }
        
        if(!data.has_more){
          hasMore = false;
          loadMoreBtn.style.display = \'none\';
        } else {
          loadMoreBtn.disabled = false;
          loadMoreBtn.textContent = \'Load More\';
        }
        
        isLoading = false;
      } catch(error){
        console.error(\'Failed to load more threads:\', error);
        loadMoreBtn.disabled = false;
        loadMoreBtn.textContent = \'Load More\';
        isLoading = false;
      }
    });
  }
});
</script>';
      
      // Add stats if available
      global $_THEME_STATS;
      if(!isset($_THEME_STATS)) {
        // Calculate stats if not already set
        $db = db();
        $now = now();
        $startOfDay = strtotime('today', $now);
        $days7Ago = $now - (86400 * 7);
        
        $stmt = $db->prepare("SELECT COUNT(*) AS c FROM threads WHERE created >= ?");
        $stmt->execute([$startOfDay]);
        $todayThreads = (int)($stmt->fetch()['c'] ?? 0);
        
        $stmt = $db->prepare("SELECT COUNT(*) AS c FROM posts WHERE time >= ?");
        $stmt->execute([$startOfDay]);
        $todayPosts = (int)($stmt->fetch()['c'] ?? 0);
        
        $stmt = $db->prepare("SELECT COUNT(*) AS c FROM users WHERE joined >= ?");
        $stmt->execute([$days7Ago]);
        $newMembers = (int)($stmt->fetch()['c'] ?? 0);
        $active24h = count(online_users(86400));
        
        $_THEME_STATS = [
          'threads' => $todayThreads,
          'posts' => $todayPosts,
          'members' => $newMembers,
          'active' => $active24h
        ];
      }
      
      if(isset($_THEME_STATS)) {
        $_THEME_CONTEXT['has_stats'] = true;
        $_THEME_CONTEXT['stat_threads'] = $_THEME_STATS['threads'];
        $_THEME_CONTEXT['stat_posts'] = $_THEME_STATS['posts'];
        $_THEME_CONTEXT['stat_members'] = $_THEME_STATS['members'];
        $_THEME_CONTEXT['stat_active'] = $_THEME_STATS['active'];
        
        // Add page summary data if available
        if(isset($_THEME_STATS['has_page_summary'])) {
          $_THEME_CONTEXT['has_page_summary'] = true;
          $_THEME_CONTEXT['page_filters'] = $_THEME_STATS['page_filters'] ?? [];
          $_THEME_CONTEXT['page_views'] = $_THEME_STATS['page_views'] ?? [];
          $_THEME_CONTEXT['current_filter'] = $_THEME_STATS['current_filter'] ?? null;
          $_THEME_CONTEXT['current_mode'] = $_THEME_STATS['current_mode'] ?? 'latest';
        }
        
        // Pass grouped threads to context for ThreadsByDate block
        if(isset($_THEME_STATS['grouped_threads'])) {
          $_THEME_CONTEXT['grouped_threads'] = $_THEME_STATS['grouped_threads'];
        }
      }
      
      // Merge context (mark HTML as safe by wrapping in special marker)
      $_THEME_CONTEXT['has_online_users'] = $hasOnlineUsers;
      $_THEME_CONTEXT['online_users'] = ['__html' => $onlineHtml];
      $_THEME_CONTEXT['footer_scripts'] = ['__html' => $footerScripts];
      
      // Add custom CSS to context for Header block
      $customCss = trim($theme->getCss());
      if($customCss) {
        $cssTag = '<style id="theme-custom-css" data-theme="'.h($_THEME_CONTEXT['theme']).'" data-version="'.time().'">
/* Theme: '.$_THEME_CONTEXT['theme'].' */
'.$customCss.'
</style>';
        $_THEME_CONTEXT['custom_css'] = $cssTag;
      }
      
      // NOW render page-specific template with full context (no wrapper!)
      $pageName = $_THEME_CONTEXT['page_name'] ?? '';
      
      if($pageName) {
        $pageTemplatePath = __DIR__ . '/../themes/' . $_THEME_CONTEXT['theme'] . '/pages/' . $pageName . '.html';
        
        // If specific page template doesn't exist, try default.html
        if(!file_exists($pageTemplatePath)) {
          $pageTemplatePath = __DIR__ . '/../themes/' . $_THEME_CONTEXT['theme'] . '/pages/default.html';
        }
        
        if(file_exists($pageTemplatePath)) {
          $pageTemplate = file_get_contents($pageTemplatePath);
          
          // Make buffered content available as {content} for default template
          $_THEME_CONTEXT['content'] = ['__html' => $content];
          
          $pageParser = \Fineprint\parse_template($pageTemplate);
          $pageContent = $pageParser->render($_THEME_CONTEXT);
          echo $pageContent;
          return;
        }
      }
      
      // Final fallback: output buffered content without wrapper
      echo $content;
      return;
    } catch(\Exception $e) {
      echo "<!-- Fineprint error: " . htmlspecialchars($e->getMessage()) . " -->";
      echo $content; // Output buffered content as fallback
    }
  }
}
