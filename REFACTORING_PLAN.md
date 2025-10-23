# Refactoring Plan for Self-Contained Themes

## Current State
- `assets/style.css` contains ~2318 lines mixing admin and forum styles
- Both themes rely heavily on base CSS
- Themes are NOT self-contained

## Task 1: Self-Contained Themes

### Step 1.1: Identify Style Categories
**Admin-only styles (keep in assets/style.css):**
- Lines 1566-2090: Admin panel, theme editor, admin forms, tables, badges
- Lines 2313-2316: Admin mobile responsive
- Lines 2079-2089: Admin stats mobile

**Forum styles (move to themes):**
- Lines 1-1565: Header, navigation, forum insights, threads, posts, profiles, forms
- Lines 2092-2312: Search page, mobile responsive for forum
- Lines 1947-2078: Mobile responsive for forum elements

### Step 1.2: Create Clean assets/style.css
Extract and keep ONLY:
- CSS variables (lines 1-20)
- Basic resets (lines 22-32)
- Admin styles (lines 1566-1945)
- Admin mobile responsive (lines 2079-2089, 2313-2316)

### Step 1.3: Add Forum Styles to nofollow.club Theme
The theme currently has ~483 lines. Need to add:
- Header/navigation (.sitebar)
- Buttons (.btn, .btn--primary)
- Forum insights
- Thread groups, lists, items
- Posts and replies
- Profile pages
- Forms (thread, reply, profile edit)
- Search
- Footer
- Mobile responsive styles

### Step 1.4: Add Forum Styles to default Theme
The theme currently has ~603 lines with overrides. Need to add:
- All base forum styles
- Then apply modern theme overrides on top

## Task 2: Noindex Release

### Directory Structure
```
/Volumes/External Disk/Code/Noindex/
├── README.md
├── admin/
├── assets/
├── inc/
├── themes/
│   └── default/  (only)
├── *.php files
└── .htaccess
```

### README.md Contents
- Project description
- Installation instructions
- Configuration guide
- Theming system overview
- License information

## Task 3: Fineprint Release

### Directory Structure
```
/Volumes/External Disk/Code/Fineprint/
├── README.md
├── src/
│   ├── Fineprint.php
│   └── packs/
├── examples/
└── LICENSE
```

### README.md Contents
- What is Fineprint
- Installation
- Basic usage
- Block system
- Variable system
- Creating packs
- Examples

## Estimated Complexity
- Task 1: HIGH - Requires careful extraction and testing
- Task 2: MEDIUM - Mostly file copying and documentation
- Task 3: MEDIUM - Extraction and documentation

## Recommendation
Complete tasks sequentially with testing between each major step.
