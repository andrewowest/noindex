<?php
/**
 * Database initialization script
 * Run this once to create the SQLite database
 */

define('APP_ROOT', __DIR__);
define('DATA', APP_ROOT . '/data');

// Create data directory if needed
@mkdir(DATA, 0775, true);

$dbPath = DATA . '/forum.db';

// Connect to SQLite
$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Create tables
$db->exec("
CREATE TABLE IF NOT EXISTS settings (
  key TEXT PRIMARY KEY,
  value TEXT
);

CREATE TABLE IF NOT EXISTS users (
  username TEXT PRIMARY KEY,
  pass TEXT NOT NULL,
  role TEXT DEFAULT 'member',
  joined INTEGER NOT NULL,
  invite_code TEXT UNIQUE,
  invited_by TEXT,
  bio TEXT DEFAULT '',
  avatar TEXT DEFAULT '',
  last_seen INTEGER,
  FOREIGN KEY (invited_by) REFERENCES users(username)
);

CREATE TABLE IF NOT EXISTS categories (
  id TEXT PRIMARY KEY,
  name TEXT NOT NULL,
  description TEXT DEFAULT ''
);

CREATE TABLE IF NOT EXISTS threads (
  id TEXT NOT NULL,
  category_id TEXT NOT NULL,
  title TEXT NOT NULL,
  author TEXT NOT NULL,
  created INTEGER NOT NULL,
  PRIMARY KEY (category_id, id),
  FOREIGN KEY (category_id) REFERENCES categories(id),
  FOREIGN KEY (author) REFERENCES users(username)
);

CREATE TABLE IF NOT EXISTS posts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  thread_id TEXT NOT NULL,
  category_id TEXT NOT NULL,
  author TEXT NOT NULL,
  time INTEGER NOT NULL,
  body TEXT NOT NULL,
  FOREIGN KEY (category_id, thread_id) REFERENCES threads(category_id, id),
  FOREIGN KEY (author) REFERENCES users(username)
);

CREATE INDEX IF NOT EXISTS idx_posts_thread ON posts(category_id, thread_id, time);
CREATE INDEX IF NOT EXISTS idx_posts_author ON posts(author);
CREATE INDEX IF NOT EXISTS idx_threads_category ON threads(category_id, created);
CREATE INDEX IF NOT EXISTS idx_users_last_seen ON users(last_seen);
");

// Insert default settings
$db->exec("
INSERT OR IGNORE INTO settings (key, value) VALUES
  ('site_name', 'longreply.club'),
  ('items_per_page', '25'),
  ('posts_per_page', '50')
");

// Insert default admin user
$passHash = password_hash('d33ts', PASSWORD_DEFAULT);
$inviteCode = strtoupper(bin2hex(random_bytes(5)));
$now = time();

$stmt = $db->prepare("
INSERT OR IGNORE INTO users (username, pass, role, joined, invite_code, invited_by, last_seen)
VALUES ('andrew', :pass, 'admin', :now, :code, NULL, :now)
");
$stmt->execute([':pass' => $passHash, ':now' => $now, ':code' => $inviteCode]);

// Insert default category
$db->exec("
INSERT OR IGNORE INTO categories (id, name, description)
VALUES ('general', 'General Discussion', 'Talk about anything')
");

// Insert welcome thread and post
$stmt = $db->prepare("
INSERT OR IGNORE INTO threads (id, category_id, title, author, created)
VALUES ('welcome', 'general', 'Welcome to longreply', 'andrew', :now)
");
$stmt->execute([':now' => $now]);

$stmt = $db->prepare("
INSERT OR IGNORE INTO posts (thread_id, category_id, author, time, body)
VALUES ('welcome', 'general', 'andrew', :now, 'This is your first post. Go nuts!')
");
$stmt->execute([':now' => $now]);

echo "✓ Database initialized successfully at: {$dbPath}\n";
echo "✓ Default admin user: andrew (password: d33ts)\n";
echo "✓ Ready to use!\n";
