<?php

namespace Fineprint\Packs\Forum;

/**
 * Fineprint Forum Pack
 * Semantic blocks for forum platforms
 */

function load() {
  require_once __DIR__ . '/../blocks.php';
  
  // ============================================
  // ITERATOR BLOCKS (Loops)
  // ============================================
  
  /**
   * {block:Threads}
   * Loop through all threads
   */
  \Fineprint\register_iterator_block('Threads', function($context) {
    $db = \db();
    $stmt = $db->query("
      SELECT t.*, 
        (SELECT MAX(time) FROM posts WHERE thread_id = t.id) as last_post_time,
        (SELECT author FROM posts WHERE thread_id = t.id ORDER BY time DESC LIMIT 1) as last_post_author,
        (SELECT COUNT(*) FROM posts WHERE thread_id = t.id) as reply_count
      FROM threads t
      ORDER BY COALESCE(last_post_time, created) DESC
      LIMIT 100
    ");
    
    $threads = $stmt->fetchAll();
    
    // Transform for template
    return array_map(function($t) {
      $lastTime = $t['last_post_time'] ?? $t['created'];
      $formattedTime = date('g:ia', (int)$lastTime);
      return [
        'ThreadID' => $t['id'],
        'ThreadTitle' => $t['title'],
        'ThreadAuthor' => $t['author'],
        'ThreadCategory' => $t['category_id'],
        'ThreadCreated' => $t['created'],
        'ThreadURL' => '/thread.php?id=' . $t['id'],
        'ReplyCount' => (int)($t['reply_count'] ?? 0) - 1, // Subtract OP
        'LastPostTime' => $formattedTime,
        'LastPostAuthor' => $t['last_post_author'] ?? $t['author']
      ];
    }, $threads);
  }, [
    'ThreadID', 'ThreadTitle', 'ThreadAuthor', 'ThreadCategory', 
    'ThreadCreated', 'ThreadURL', 'ReplyCount', 'LastPostTime', 'LastPostAuthor'
  ], 'Loops through all forum threads');
  
  /**
   * {block:TopThreads}
   * Renders top threads (ranked list, no grouping)
   */
  \Fineprint\register_variable_block('TopThreads', function($context) {
    $currentMode = $context['current_mode'] ?? 'latest';
    
    // Only render if we're in top mode
    if($currentMode !== 'top') {
      return ['__html' => ''];
    }
    
    $threads = $context['grouped_threads'] ?? [];
    if(empty($threads)) {
      return ['__html' => ''];
    }
    
    $html = '<section class="thread-group thread-group--top">';
    $html .= '<ul class="thread-list thread-list--top">';
    
    $index = 0;
    foreach($threads as $t) {
      $index++;
      $threadId = $t['id'] ?? '';
      $title = htmlspecialchars($t['title'] ?? '');
      $author = htmlspecialchars($t['author'] ?? '');
      $postCount = max(0, (int)($t['post_count'] ?? 0) - 1);
      $lastPostTime = $t['last_post_time'] ?? $t['created'] ?? time();
      $timeAgo = relative_time((int)$lastPostTime);
      $threadUrl = '/thread.php?id=' . urlencode($threadId);
      $rank = sprintf('%02d', $index);
      
      $html .= '<li class="thread-list__item">';
      $html .= '<a href="' . htmlspecialchars($threadUrl) . '" class="thread-item">';
      $html .= '<div class="thread-item__rank">' . $rank . '</div>';
      $html .= '<div class="thread-item__title">' . $title . '</div>';
      $html .= '<div class="thread-item__meta meta">';
      $html .= '<span class="thread-item__meta-piece">';
      $html .= '<span class="thread-username">' . $author . '</span>';
      $html .= '<span class="thread-item__badge thread-item__badge--started">posted</span>';
      $html .= '</span>';
      $html .= '<span class="thread-item__meta-piece">' . htmlspecialchars($timeAgo) . '</span>';
      $html .= '<span class="thread-item__meta-piece">' . $postCount . ' replies</span>';
      $html .= '</div>';
      $html .= '</a>';
      $html .= '</li>';
    }
    
    $html .= '</ul>';
    $html .= '</section>';
    
    return ['__html' => $html];
  }, 'Renders top threads in ranked list format');
  
  /**
   * {block:ThreadsByDate}
   * Loop through threads grouped by date
   */
  \Fineprint\register_iterator_block('ThreadsByDate', function($context) {
    $currentMode = $context['current_mode'] ?? 'latest';
    
    // Don't render if we're in top mode (TopThreads block handles that)
    if($currentMode === 'top') {
      return [];
    }
    
    // Check if threads were already processed by index.php
    if(isset($context['grouped_threads'])) {
      $grouped = $context['grouped_threads'];
    } else {
      // Fallback: fetch threads ourselves
      $db = \db();
      $stmt = $db->query("
        SELECT t.*, 
          (SELECT MAX(time) FROM posts WHERE thread_id = t.id) as last_post_time,
          (SELECT author FROM posts WHERE thread_id = t.id ORDER BY time DESC LIMIT 1) as last_post_author,
          (SELECT COUNT(*) FROM posts WHERE thread_id = t.id) as reply_count
        FROM threads t
        ORDER BY COALESCE(last_post_time, created) DESC
        LIMIT 100
      ");
      
      $threads = $stmt->fetchAll();
      
      // Group by date
      $grouped = [];
      foreach($threads as $t) {
        $lastActivity = $t['last_post_time'] ?? $t['created'];
        $date = date('Y-m-d', (int)$lastActivity);
        if(!isset($grouped[$date])) {
          $grouped[$date] = [];
        }
        $grouped[$date][] = $t;
      }
    }
    
    // Transform to template format
    $result = [];
    foreach($grouped as $date => $dateThreads) {
      $threadCount = count($dateThreads);
      $postsForDate = array_sum(array_map(fn($t) => max(0, (int)($t['post_count'] ?? $t['reply_count'] ?? 0) - 1), $dateThreads));
      
      $result[] = [
        'DateHeading' => format_date_heading($date),
        'Date' => $date,
        'DateMeta' => $threadCount . ' threads · ' . $postsForDate . ' replies',
        'Threads' => array_map(function($t) {
          $lastTime = $t['last_post_time'] ?? $t['created'];
          $formattedTime = date('g:ia', (int)$lastTime);
          return [
            'ThreadID' => $t['id'],
            'ThreadTitle' => $t['title'],
            'ThreadAuthor' => $t['author'],
            'ThreadCategory' => $t['category_id'],
            'ThreadCreated' => $t['created'],
            'ThreadURL' => '/thread.php?id=' . $t['id'],
            'ReplyCount' => max(0, (int)($t['post_count'] ?? $t['reply_count'] ?? 0) - 1),
            'LastPostTime' => $formattedTime,
            'LastPostAuthor' => $t['last_post_author'] ?? $t['author']
          ];
        }, $dateThreads)
      ];
    }
    
    return $result;
  }, [
    'DateHeading', 'Date', 'DateMeta', 'Threads'
  ], 'Loops through threads grouped by date of last activity');
  
  /**
   * {block:Posts}
   * Loop through posts in current thread
   */
  \Fineprint\register_iterator_block('Posts', function($context) {
    $threadId = $context['thread_id'] ?? null;
    if(!$threadId) return [];
    
    $posts = \get_posts(null, $threadId, 1000);
    
    return array_map(function($p) {
      return [
        'PostID' => $p['id'],
        'PostAuthor' => $p['author'],
        'PostBody' => $p['body'],
        'PostTime' => $p['time'],
        'PostURL' => '#post-' . $p['id'],
        'PostNumber' => $p['post_number'] ?? 1
      ];
    }, $posts);
  }, [
    'PostID', 'PostAuthor', 'PostBody', 'PostTime', 'PostURL', 'PostNumber'
  ], 'Loops through posts in the current thread');
  
  /**
   * {block:Categories}
   * Loop through forum categories
   */
  \Fineprint\register_iterator_block('Categories', function($context) {
    $categories = \categories();
    
    return array_map(function($cat) {
      return [
        'CategoryID' => $cat['id'],
        'CategoryName' => $cat['name'],
        'CategoryURL' => '/index.php?category=' . urlencode($cat['id'])
      ];
    }, $categories);
  }, [
    'CategoryID', 'CategoryName', 'CategoryURL'
  ], 'Loops through forum categories');
  
  // ============================================
  // CONDITIONAL BLOCKS
  // ============================================
  
  /**
   * {block:IfUser}
   * Show content if user is logged in
   */
  \Fineprint\register_conditional_block('IfUser', function($context) {
    return $context['user'] ?? false;
  }, 'Shows content only if user is logged in');
  
  /**
   * {block:IfGuest}
   * Show content if user is NOT logged in
   */
  \Fineprint\register_conditional_block('IfGuest', function($context) {
    return $context['guest'] ?? false;
  }, 'Shows content only if user is NOT logged in');
  
  /**
   * {block:IfAdmin}
   * Show content if user is admin/mod
   */
  \Fineprint\register_conditional_block('IfAdmin', function($context) {
    return $context['is_admin'] ?? false;
  }, 'Shows content only if user has admin or moderator privileges');
  
  /**
   * {block:IfOnlineUsers}
   * Show content if there are online users
   */
  \Fineprint\register_conditional_block('IfOnlineUsers', function($context) {
    return $context['has_online_users'] ?? false;
  }, 'Shows content only if there are users currently online');
  
  /**
   * {block:IfStats}
   * Show content if forum stats are available
   */
  \Fineprint\register_conditional_block('IfStats', function($context) {
    return $context['has_stats'] ?? false;
  }, 'Shows content only if forum statistics are available');
  
  /**
   * {block:IfPageSummary}
   * Show content if page has filters/summary
   */
  \Fineprint\register_conditional_block('IfPageSummary', function($context) {
    return $context['has_page_summary'] ?? false;
  }, 'Shows content only if page has filter/summary information');
  
  /**
   * {block:Header}
   * Renders the site header with navigation
   */
  \Fineprint\register_variable_block('Header', function($context) {
    $siteName = htmlspecialchars($context['site_name'] ?? 'Forum');
    $isUser = $context['user'] ?? false;
    $isAdmin = $context['is_admin'] ?? false;
    
    $html = '<!DOCTYPE html>';
    $html .= '<html lang="en">';
    $html .= '<head>';
    $html .= '<meta charset="UTF-8">';
    $html .= '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    $html .= '<title>' . htmlspecialchars($context['page_title'] ?? 'Home') . ' · ' . $siteName . '</title>';
    $html .= '<link rel="stylesheet" href="/inc/fineprint/packs/base.css">';
    
    // Theme CSS with cache busting
    $theme = $context['theme'] ?? 'nofollow.club';
    $themePath = __DIR__ . '/../../themes/' . $theme . '/style.css';
    $version = file_exists($themePath) ? filemtime($themePath) : time();
    $html .= '<link rel="stylesheet" href="/themes/' . htmlspecialchars($theme) . '/style.css?v=' . $version . '">';
    
    // Custom CSS if available
    if(isset($context['custom_css'])) {
      $html .= $context['custom_css'];
    }
    
    // TinyMDE markdown editor
    $html .= '<script src="https://unpkg.com/tiny-markdown-editor/dist/tiny-mde.min.js"></script>';
    
    $html .= '</head>';
    $pageName = $context['page_name'] ?? '';
    $bodyClass = $pageName ? 'page-' . htmlspecialchars($pageName) : '';
    $html .= '<body class="' . $bodyClass . '">';
    $html .= '<header class="sitebar">';
    $html .= '<div class="container">';
    $html .= '<a class="brand" href="/index.php">' . $siteName . '</a>';
    $html .= '<nav>';
    
    if($isUser) {
      $html .= '<a href="/search.php" class="nav-ico" aria-label="search">';
      $html .= '<svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true">';
      $html .= '<circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2" fill="none"></circle>';
      $html .= '<line x1="16.5" y1="16.5" x2="22" y2="22" stroke="currentColor" stroke-width="2" stroke-linecap="round"></line>';
      $html .= '</svg>';
      $html .= '</a>';
      $html .= '<a href="/profile.php">profile</a>';
      $html .= '<a href="/logout.php">sign out</a>';
      
      if($isAdmin) {
        $html .= '<a href="/admin/index.php" class="nav-admin">admin</a>';
      }
    } else {
      $html .= '<a href="/login.php">sign in</a>';
    }
    
    $html .= '</nav>';
    $html .= '</div>';
    $html .= '</header>';
    
    return ['__html' => $html];
  }, 'Renders the site header with navigation');
  
  /**
   * {block:Footer}
   * Renders the site footer
   */
  \Fineprint\register_variable_block('Footer', function($context) {
    $siteName = htmlspecialchars($context['site_name'] ?? 'Forum');
    
    $html = '';
    
    // Footer
    $html .= '<footer class="footer"><div class="container">';
    $html .= '<div class="meta">Built with <a href="https://github.com/andrewowest/noindex" target="_blank" rel="noopener">Noindex</a> × <a href="https://github.com/andrewowest/fineprint" target="_blank" rel="noopener">Fineprint</a></div>';
    $html .= '</div></footer>';
    
    // Footer scripts
    if(isset($context['footer_scripts']['__html'])) {
      $html .= $context['footer_scripts']['__html'];
    }
    
    $html .= '</body>';
    $html .= '</html>';
    
    return ['__html' => $html];
  }, 'Renders the site footer');
  
  /**
   * {block:PageSummary}
   * Renders the page filter/view controls
   */
  \Fineprint\register_variable_block('PageSummary', function($context) {
    $filters = $context['page_filters'] ?? [];
    $viewOptions = $context['page_views'] ?? [];
    $currentFilter = $context['current_filter'] ?? null;
    $currentMode = $context['current_mode'] ?? 'latest';
    $isLoggedIn = $context['user'] ?? false;
    
    // Build filter dropdown
    $filterLabel = 'Filter';
    foreach($filters as $f) {
      if($f['value'] === $currentFilter) {
        $filterLabel = $f['label'];
        break;
      }
    }
    
    $html = '<section class="page-summary" aria-label="thread filters">';
    $html .= '<div class="summary-filters">';
    
    // Filter dropdown
    $html .= '<div class="filter-select" data-dropdown>';
    $html .= '<button type="button" class="filter-select__trigger" data-dropdown-trigger>';
    $html .= '<span class="filter-select__value">' . htmlspecialchars($filterLabel) . '</span>';
    $html .= '<svg class="filter-select__arrow" width="10" height="6" viewBox="0 0 10 6" fill="none">';
    $html .= '<path d="M1 1l4 4 4-4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>';
    $html .= '</svg>';
    $html .= '</button>';
    $html .= '<div class="filter-select__menu" data-dropdown-menu>';
    
    foreach($filters as $filterOption) {
      $requiresLogin = $filterOption['requires_login'] ?? false;
      if($requiresLogin && !$isLoggedIn) continue;
      
      $isActive = $currentFilter === $filterOption['value'];
      $filterHref = '/index.php?' . http_build_query([
        'mode' => $currentMode,
        'filter' => $filterOption['value']
      ]);
      
      $activeClass = $isActive ? ' is-active' : '';
      $html .= '<a href="' . htmlspecialchars($filterHref) . '" class="filter-select__option' . $activeClass . '">';
      $html .= htmlspecialchars($filterOption['label']);
      $html .= '</a>';
    }
    
    $html .= '</div></div>';
    
    // View toggle
    $html .= '<nav class="view-toggle" role="tablist" aria-label="view mode">';
    
    foreach($viewOptions as $tab) {
      $isCurrent = $currentMode === $tab['value'];
      $tabHref = '/index.php?' . http_build_query([
        'mode' => $tab['value'],
        'filter' => $currentFilter
      ]);
      
      $activeClass = $isCurrent ? ' is-active' : '';
      $selected = $isCurrent ? 'true' : 'false';
      
      $html .= '<a class="view-toggle__item' . $activeClass . '" href="' . htmlspecialchars($tabHref) . '" ';
      $html .= 'role="tab" aria-selected="' . $selected . '">';
      $html .= htmlspecialchars($tab['label']);
      $html .= '</a>';
    }
    
    $html .= '</nav>';
    $html .= '</div>';
    $html .= '<a href="/new_thread.php" class="btn btn--primary summary-cta">+ NEW THREAD</a>';
    $html .= '</section>';
    
    return ['__html' => $html]; // Mark as safe HTML
  }, 'Renders the page filter and view controls');
  
  /**
   * {block:ModernHeader}
   * Renders modern header for default theme
   */
  \Fineprint\register_variable_block('ModernHeader', function($context) {
    $siteName = htmlspecialchars($context['site_name'] ?? 'Forum');
    $isUser = $context['user'] ?? false;
    $isAdmin = $context['is_admin'] ?? false;
    
    $html = '<!DOCTYPE html>';
    $html .= '<html lang="en">';
    $html .= '<head>';
    $html .= '<meta charset="UTF-8">';
    $html .= '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    $html .= '<title>' . htmlspecialchars($context['page_title'] ?? 'Home') . ' · ' . $siteName . '</title>';
    $html .= '<link rel="stylesheet" href="/inc/fineprint/packs/base.css">';
    
    // Theme CSS with cache busting
    $theme = $context['theme'] ?? 'default';
    $themePath = __DIR__ . '/../../themes/' . $theme . '/style.css';
    $version = file_exists($themePath) ? filemtime($themePath) : time();
    $html .= '<link rel="stylesheet" href="/themes/' . htmlspecialchars($theme) . '/style.css?v=' . $version . '">';
    
    // TinyMDE markdown editor
    $html .= '<script src="https://unpkg.com/tiny-markdown-editor/dist/tiny-mde.min.js"></script>';
    
    $html .= '</head>';
    $pageName = $context['page_name'] ?? '';
    $bodyClass = $pageName ? 'page-' . htmlspecialchars($pageName) : '';
    $html .= '<body class="' . $bodyClass . '">';
    $html .= '<header class="modern-header">';
    $html .= '<div class="modern-header__inner">';
    $html .= '<div class="modern-header__brand">';
    $html .= '<a class="modern-logo" href="/index.php">' . $siteName . '</a>';
    $html .= '</div>';
    $html .= '<nav class="modern-nav">';
    
    if($isUser) {
      $html .= '<a href="/new_thread.php" class="modern-nav__link modern-nav__link--primary">+ New Thread</a>';
      $html .= '<a href="/search.php" class="modern-nav__link modern-nav__link--icon" aria-label="search">';
      $html .= '<svg viewBox="0 0 24 24" width="16" height="16">';
      $html .= '<circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2" fill="none"></circle>';
      $html .= '<line x1="16.5" y1="16.5" x2="22" y2="22" stroke="currentColor" stroke-width="2" stroke-linecap="round"></line>';
      $html .= '</svg>';
      $html .= '</a>';
      $html .= '<a href="/profile.php" class="modern-nav__link">Profile</a>';
      
      if($isAdmin) {
        $html .= '<a href="/admin/index.php" class="modern-nav__link modern-nav__link--admin">Admin</a>';
      }
      
      $html .= '<a href="/logout.php" class="modern-nav__link modern-nav__link--secondary">Sign Out</a>';
    } else {
      $html .= '<a href="/login.php" class="modern-nav__link">Sign In</a>';
      $html .= '<a href="/register.php" class="modern-nav__link modern-nav__link--primary">Register</a>';
    }
    
    $html .= '</nav>';
    $html .= '</div>';
    $html .= '</header>';
    
    return ['__html' => $html];
  }, 'Renders modern header with navigation for default theme');
  
  /**
   * {block:ModernFooter}
   * Renders modern footer for default theme
   */
  \Fineprint\register_variable_block('ModernFooter', function($context) {
    $html = '<footer class="modern-footer">';
    $html .= '<p class="modern-footer__text">Built with <a href="https://github.com/andrewowest/noindex" target="_blank" rel="noopener">Noindex</a> × <a href="https://github.com/andrewowest/fineprint" target="_blank" rel="noopener">Fineprint</a></p>';
    $html .= '</footer>';
    
    // Footer scripts
    if(isset($context['footer_scripts']['__html'])) {
      $html .= $context['footer_scripts']['__html'];
    }
    
    $html .= '</body>';
    $html .= '</html>';
    
    return ['__html' => $html];
  }, 'Renders modern footer for default theme');
}

/**
 * Helper: Format date heading
 */
function format_date_heading($date) {
  $timestamp = strtotime($date);
  $today = strtotime('today');
  $yesterday = strtotime('yesterday');
  
  if($timestamp >= $today) {
    return 'Today';
  } elseif($timestamp >= $yesterday) {
    return 'Yesterday';
  } else {
    return date('F j, Y', $timestamp);
  }
}
