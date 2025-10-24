# noindex

The internet used to feel like a neighborhood, and noindex is built in that spirit. It’s invite-only, anti-scalable, and designed to be your "third place" on the web.

## Features

- **Invite-only** - Every member needs an invite code to join
- **Invite tree** - Track who invited who, building accountability
- **Clean, minimal design** - Focus on content, not clutter
- **Themeable** - Built on the Fineprint block templating system
- **Markdown support** - Write posts in Markdown
- **User profiles** - Custom avatars, bios, and invite codes
- **Search** - Full-text search across threads and posts
- **Admin panel** - Manage users, content, and settings
- **No dependencies** - Pure PHP with SQLite database
- **Private by default** - Content only visible to members

## Philosophy

Noindex is designed for **intentional communities**. Not viral growth. Not engagement metrics. Just people vouching for people, creating trusted spaces for real conversation.

The invite-only model creates:
- **Accountability** - Your invite tree matters
- **Quality over scale** - Intentionally small, pro-intimacy
- **Trust** - Small circles, friends of friends
- **Privacy** - Content stays within the community

## Requirements

- PHP 8.0 or higher
- SQLite3 extension
- Apache/Nginx with mod_rewrite

## Installation

1. **Clone or download** this repository to your web server

2. **Set permissions** on the data directory:
   ```bash
   chmod 755 data
   chmod 664 data/forum.db
   ```

3. **Configure your web server** to point to the forum directory

4. **Initialize the database** (if starting fresh):
   ```bash
   php init_db.php
   ```

5. **Get your admin invite code**:
   ```bash
   sqlite3 data/forum.db "SELECT invite_code FROM users WHERE username='andrew';"
   ```

6. **Configure settings** in `inc/common.php`:
   - Site name
   - Site tagline
   - Upload paths
   - Other preferences

## Invite System

### How It Works

1. **Admin gets first invite code** - Generated during database initialization
2. **Share your code** - Each user sees their invite code on their profile
3. **New users register** - They need a valid invite code to create an account
4. **Invite tree tracked** - Every profile shows "Invited by [username]"
5. **Accountability** - You're responsible for who you invite

### Managing Invites

**View your invite code:**
- Go to your profile
- Your unique code is displayed (only visible to you)

**Check who you invited:**
- View any user's profile
- See "Invited by [username]" in their profile meta

**Admin controls:**
- Ban users who abuse invites
- See full invite tree in admin panel
- Generate new codes if needed

## Theming

Noindex uses the [Fineprint](https://github.com/andrewowest/fineprint) templating system with `[block]` syntax.

### Theme Structure

```
themes/your-theme/
├── style.css          # Your theme's unique styles
├── template.html      # Main template (optional)
└── pages/            # Page-specific templates (optional)
    ├── index.html
    ├── thread.html
    └── profile.html
```

### Creating a Theme

1. Create a new directory in `themes/`
2. Add a `style.css` file with your styles
3. Optionally add `template.html` for custom HTML structure
4. Use Fineprint blocks for dynamic content

### Fineprint Blocks

Fineprint uses `[block]` syntax:

**Conditional blocks:**
```html
[if:user]
  <p>Welcome back, {username}!</p>
[/if:user]

[if:guest]
  <p>This content is private.</p>
[/if:guest]
```

**Iterator blocks:**
```html
[block:Threads]
  <h2>{thread_title}</h2>
  <p>by {thread_author}</p>
[/block:Threads]
```

**Available blocks:**
- `[if:user]` / `[if:guest]` - User authentication state
- `[if:is_admin]` - Admin privileges
- `[if:has_online_users]` - Online users available
- `[if:has_stats]` - Forum statistics available
- `[block:Threads]` - Loop through threads
- `[block:Posts]` - Loop through posts
- `[block:Categories]` - Loop through categories

### Theme Variables

Common variables you can use:
- `{site_name}` - Forum name
- `{page_title}` - Current page title
- `{username}` - Current user's username
- `{content}` - Main page content
- `{online_users}` - List of online users
- `{stat_threads}` - Thread count
- `{stat_posts}` - Post count

See the included `default` theme for a complete example.

### Base Styles

The forum pack includes `inc/fineprint/packs/base.css` with common forum styles (buttons, forms, posts, etc.). Your theme's `style.css` loads after base.css, so you only need to define your unique styling and overrides.

## Admin Panel

Access the admin panel at `/admin/` after logging in as an admin.

**Admin features:**
- User management (promote, ban, delete)
- View invite trees
- Content moderation (edit, delete threads/posts)
- Theme management (switch themes, edit CSS)
- Forum settings
- Statistics dashboard

## File Structure

```
noindex/
├── admin/              # Admin panel
├── api/                # AJAX endpoints
├── data/               # SQLite database
├── inc/                # Core PHP files
│   ├── common.php      # Configuration & helpers
│   └── fineprint/      # Templating system
│       └── packs/      # Forum pack with base.css
├── templates/          # PHP templates
├── themes/             # User-facing themes
│   └── default/        # Default theme
├── uploads/            # User uploads (avatars)
├── index.php           # Forum index (login wall if signed out)
├── thread.php          # Thread view
├── profile.php         # User profiles
├── login.php           # Login wall with registration
└── search.php          # Search page
```

## Security

- Passwords are hashed with `password_hash()`
- SQL injection protection via prepared statements
- XSS protection via `htmlspecialchars()`
- CSRF protection on forms
- File upload validation
- Invite code validation
- Private content (login required)

## Customization

### Changing Site Name

Edit `inc/common.php`:
```php
define('SITE_NAME', 'Your Community Name');
define('SITE_TAGLINE', 'Your tagline');
```

### Adding Custom Pages

1. Create a new PHP file (e.g., `about.php`)
2. Include the header: `require 'inc/common.php';`
3. Use `header_html()` and `footer_html()` for consistent layout
4. Add your content between

### Modifying the Database

The SQLite database is in `data/forum.db`. You can query it directly:
```bash
sqlite3 data/forum.db
```

## Best Practices

### Building Your Community

1. **Start small** - Invite people you trust
2. **Set expectations** - Make community guidelines clear
3. **Moderate actively** - Remove bad actors quickly
4. **Protect the vibe** - Quality over quantity
5. **Respect privacy** - What's shared stays in the community

### Managing Invites

- **Be selective** - You're vouching for people
- **Check your tree** - See who your invites invited
- **Revoke if needed** - Admins can ban users
- **Communicate** - Let people know what they're joining

## Contributing

Noindex is open source. Contributions welcome!

## License

MIT License - feel free to use, modify, and distribute.

## Credits

- Built by [@andrewowest](https://github.com/andrewowest)
- Powered by [Fineprint](https://github.com/andrewowest/fineprint) templating system
- Designed for intentional communities

## Support

For issues, questions, or feature requests, please open an issue on GitHub.

---

**noindex** - Invite-only communities. A third place.
