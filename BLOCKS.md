# Fineprint Forum Blocks

This document lists all available blocks in the `fineprint-forum` pack for nofollow.club.

## Block Syntax

Blocks use square brackets:

```html
[block:BlockName]
  <!-- Content here -->
[/block:BlockName]
```

Variables inside blocks use curly braces:

```html
{VariableName}
```

## Iterator Blocks

Iterator blocks loop through collections of items.

### `[block:Threads}`

**Type:** Iterator  
**Description:** Loops through all forum threads

**Variables:**
- `{ThreadID}` - Thread ID
- `{ThreadTitle}` - Thread title
- `{ThreadAuthor}` - Thread author username
- `{ThreadCategory}` - Thread category ID
- `{ThreadCreated}` - Thread creation timestamp
- `{ThreadURL}` - URL to view thread
- `{ReplyCount}` - Number of replies (excluding OP)
- `{LastPostTime}` - Timestamp of last post
- `{LastPostAuthor}` - Username of last poster

**Example:**
```html
[block:Threads]
  <div class="thread">
    <h2><a href="{ThreadURL}">{ThreadTitle}</a></h2>
    <p>by {ThreadAuthor} • {ReplyCount} replies</p>
  </div>
[/block:Threads]
```

---

### `[block:ThreadsByDate}`

**Type:** Iterator  
**Description:** Loops through threads grouped by date of last activity

**Variables:**
- `{DateHeading}` - Formatted date heading (e.g., "Today", "Yesterday", "January 15, 2025")
- `{Date}` - Raw date (YYYY-MM-DD)
- `{Threads}` - Nested block containing threads for this date (use `[block:Threads]` inside)

**Example:**
```html
[block:ThreadsByDate]
  <div class="thread-group">
    <h3>{DateHeading}</h3>
    [block:Threads]
      <div class="thread">
        <a href="{ThreadURL}">{ThreadTitle}</a>
      </div>
    [/block:Threads]
  </div>
[/block:ThreadsByDate]
```

---

### `[block:Posts}`

**Type:** Iterator  
**Description:** Loops through posts in the current thread

**Variables:**
- `{PostID}` - Post ID
- `{PostAuthor}` - Post author username
- `{PostBody}` - Post content (HTML)
- `{PostTime}` - Post timestamp
- `{PostURL}` - URL to post anchor
- `{PostNumber}` - Post number in thread

**Example:**
```html
[block:Posts}
  <div class="post" id="post-{PostID}">
    <div class="post-author">{PostAuthor}</div>
    <div class="post-body">{PostBody}</div>
    <div class="post-meta">#{PostNumber} • {PostTime}</div>
  </div>
[/block:Posts}
```

---

### `[block:Categories}`

**Type:** Iterator  
**Description:** Loops through forum categories

**Variables:**
- `{CategoryID}` - Category ID
- `{CategoryName}` - Category name
- `{CategoryURL}` - URL to filter by category

**Example:**
```html
[block:Categories}
  <a href="{CategoryURL}" class="category-tag">
    {CategoryName}
  </a>
[/block:Categories}
```

---

## Conditional Blocks

Conditional blocks show/hide content based on conditions.

### `[block:IfUser}`

**Type:** Conditional  
**Description:** Shows content only if user is logged in

**Example:**
```html
[block:IfUser}
  <a href="/profile.php">My Profile</a>
  <a href="/logout.php">Sign Out</a>
[/block:IfUser}
```

---

### `[block:IfGuest}`

**Type:** Conditional  
**Description:** Shows content only if user is NOT logged in

**Example:**
```html
[block:IfGuest}
  <a href="/login.php">Sign In</a>
  <a href="/register.php">Register</a>
[/block:IfGuest}
```

---

### `[block:IfAdmin}`

**Type:** Conditional  
**Description:** Shows content only if user has admin or moderator privileges

**Example:**
```html
[block:IfAdmin}
  <a href="/admin/index.php">Admin Panel</a>
[/block:IfAdmin}
```

---

### `[block:IfOnlineUsers}`

**Type:** Conditional  
**Description:** Shows content only if there are users currently online

**Example:**
```html
[block:IfOnlineUsers}
  <div class="online-users">
    Currently online: {online_users}
  </div>
[/block:IfOnlineUsers}
```

---

### `[block:IfStats}`

**Type:** Conditional  
**Description:** Shows content only if forum statistics are available

**Example:**
```html
[block:IfStats}
  <div class="stats">
    <span>{stat_threads} threads</span>
    <span>{stat_posts} posts</span>
    <span>{stat_members} members</span>
  </div>
[/block:IfStats}
```

---

### `[block:IfPageSummary}`

**Type:** Conditional  
**Description:** Shows content only if page has filter/summary information

**Example:**
```html
[block:IfPageSummary}
  <div class="page-summary">
    {page_summary}
  </div>
[/block:IfPageSummary}
```

---

## Global Variables

These variables are available throughout the template (not inside blocks):

- `{page_title}` - Current page title
- `{site_name}` - Forum name
- `{content}` - Main page content (legacy)
- `{online_users}` - HTML list of online users
- `{footer_scripts}` - JavaScript for the page
- `{page_summary}` - Page filter/summary HTML

### Stats Variables (when `[block:IfStats}` is true):

- `{stat_threads}` - Total thread count today
- `{stat_posts}` - Total post count today
- `{stat_members}` - New members in last 24h
- `{stat_active}` - Active users in last 24h

---

## Nesting Blocks

Blocks can be nested for complex layouts:

```html
[block:ThreadsByDate}
  <div class="date-group">
    <h3>{DateHeading}</h3>
    
    [block:Threads}
      <div class="thread">
        <h4>{ThreadTitle}</h4>
        
        [block:IfAdmin}
          <a href="/admin/threads.php?id={ThreadID}">Edit</a>
        [/block:IfAdmin}
      </div>
    [/block:Threads}
  </div>
[/block:ThreadsByDate}
```

---

## Creating Custom Blocks

To add custom blocks, edit `/inc/fineprint/packs/forum.php` and use the registration API:

```php
// Iterator block
\Fineprint\register_iterator_block('MyBlock', function($context) {
  return [
    ['Variable1' => 'value1'],
    ['Variable1' => 'value2']
  ];
}, ['Variable1'], 'Description of block');

// Conditional block
\Fineprint\register_conditional_block('IfMyCondition', function($context) {
  return true; // or false
}, 'Description of condition');
```
