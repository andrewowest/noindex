# nofollow.club Design System

## Research-Backed Design Principles

This design system is built on research from Linear, Discourse, and UX best practices for scannability and information hierarchy.

### Core Philosophy
- **Speed & Clarity**: Inspired by Linear's focus on performance and intentional design
- **F-Pattern Optimization**: Thread lists optimized for natural left-to-right scanning
- **Information Density**: Maximum content with minimum cognitive load
- **Craft Over Convention**: Opinionated choices that prioritize user experience

---

## Typography System

### Font Stack
- **Body**: System fonts (-apple-system, BlinkMacSystemFont, Segoe UI, Noto Sans)
- **Mono**: SF Mono, Roboto Mono, Menlo, Monaco, Consolas

### Type Scale
```
--space-xs:   4px   (tight inline spacing)
--space-sm:   8px   (related elements)
--space-md:   16px  (component padding)
--space-lg:   24px  (section spacing)
--space-xl:   32px  (major sections)
--space-2xl:  48px  (page sections)
--space-3xl:  64px  (major page breaks)
```

### Hierarchy
- **Page titles**: 28px, weight 600, -0.01em tracking
- **Thread titles**: 15px, weight 500, -0.01em tracking
- **Body text**: 15px, line-height 1.6
- **Meta text**: 13px monospace, tabular-nums for counts
- **Labels**: 11-12px monospace, uppercase, 0.05-0.08em tracking

---

## Color System

### Semantic Colors
```css
--ink:          #e3e6ef  /* Primary text */
--ink-mid:      #b8bcc8  /* Secondary text */
--ink-dim:      #8b8f9a  /* Tertiary text */
--ink-muted:    #6b6f7a  /* Quaternary text */
--line:         #2c313c  /* Primary borders */
--line-subtle:  #23272e  /* Subtle dividers */
--box:          #181b22  /* Containers */
--box-hover:    #1e2229  /* Hover states */
--bg:           #0f1115  /* Background */
--accent:       #8b9eff  /* Interactive elements */
--accent-hover: #a5b4ff  /* Hover accent */
```

### Usage Guidelines
- **Accent color**: Links, usernames, interactive elements only
- **Muted colors**: Metadata, timestamps, secondary information
- **Backgrounds**: Minimal use, only for functional containers
- **Borders**: Subtle, used for separation not decoration

---

## Layout System

### Container Widths
- **Default**: 1080px max-width
- **Large screens (1280px+)**: 1200px max-width
- **Padding**: 24px (mobile), 48px (desktop)

### Grid Patterns

#### Thread List (Top View)
```
[rank] [title........................] [post count]
       [author · timestamp...........]
```
- 3-column grid: `auto 1fr auto`
- Rank: 32px min-width, right-aligned, tabular nums
- Title: Flexible, weight 500
- Meta: Fixed right column, tabular nums

#### Thread List (Latest View)
```
[title.............................] [post count]
[author · timestamp................]
```
- 2-column grid: `1fr auto`
- No rank column
- Grouped by date headers

---

## Component Patterns

### Thread Items
**Visual Anchors for F-Pattern Scanning:**
1. **Rank number** (top view): Right-aligned, creates visual column
2. **Thread title**: Medium weight, high contrast
3. **Author + timestamp**: Monospace, lower contrast
4. **Post count**: Right-aligned, tabular nums

**Hover States:**
- 2px left border (accent color)
- Background: `--box-hover`
- Title color: `--accent`
- Rank color: `--accent`

### Navigation
- **Sticky header**: 56px height, backdrop blur
- **Logo**: Monospace, blinking cursor animation
- **Nav links**: 11px uppercase, underline on hover

### Buttons
- **Primary**: Solid accent background, 36px height
- **Secondary**: Transparent with border
- **Control buttons**: Small (10px text), monospace

---

## Scannability Optimizations

### F-Pattern Support
1. **Left edge alignment**: Rank numbers create scanning anchor
2. **Title prominence**: Medium weight, high contrast
3. **Metadata grouping**: Monospace creates visual consistency
4. **Right-aligned counts**: Tabular nums for easy comparison

### Visual Hierarchy
1. **Size**: Titles larger than metadata
2. **Weight**: Titles medium (500), metadata regular (400)
3. **Color**: 4-level contrast system (ink → ink-mid → ink-dim → ink-muted)
4. **Spacing**: Consistent gaps using spacing scale

### Information Density
- **Compact padding**: 16px vertical on thread items
- **Grid layout**: Eliminates wasted space
- **Tabular numerics**: Numbers align for easy scanning
- **Monospace metadata**: Creates visual rhythm

---

## Interaction Design

### Transitions
```css
--transition-fast: 0.12s cubic-bezier(0.4, 0, 0.2, 1)
--transition-base: 0.2s cubic-bezier(0.4, 0, 0.2, 1)
```
- **Fast**: Color changes, simple state transitions
- **Base**: Layout shifts, complex animations

### Hover States
- **Threads**: Left border + background + title color
- **Links**: Underline (1px thickness, 2px offset)
- **Buttons**: Lift (-1px transform) + shadow

### Focus States
- **Keyboard navigation**: Dashed accent outline, 4px offset
- **Form inputs**: Accent border + 3px glow ring

---

## Responsive Breakpoints

### Mobile (< 640px)
- Reduced padding: 12px
- Smaller type: 14-15px
- Stacked layouts
- Full-width CTAs

### Tablet (640px - 1024px)
- Standard padding: 24px
- Default type sizes
- Flexible grids

### Desktop (1024px+)
- Increased padding: 48px
- Wider container: 1200px
- Multi-column grids

---

## Accessibility

### WCAG Compliance
- **Contrast ratios**: All text meets AA standards
- **Focus indicators**: Visible on all interactive elements
- **Keyboard navigation**: Full support with visible focus
- **Screen readers**: Semantic HTML, ARIA labels where needed

### Readability
- **Line height**: 1.6 for body text
- **Line length**: Max 1200px prevents eye strain
- **Font rendering**: Optimized with antialiasing
- **Spacing**: Generous whitespace for cognitive ease

---

## Performance

### Optimization Strategies
- **System fonts**: Zero font loading time
- **CSS transforms**: Hardware-accelerated animations
- **Minimal repaints**: Transitions on transform/opacity only
- **Backdrop blur**: Used sparingly (header only)

---

## Design Tokens Reference

```css
:root {
  /* Colors */
  --ink: #e3e6ef;
  --ink-mid: #b8bcc8;
  --ink-dim: #8b8f9a;
  --ink-muted: #6b6f7a;
  --accent: #8b9eff;
  
  /* Spacing */
  --space-xs: 4px;
  --space-sm: 8px;
  --space-md: 16px;
  --space-lg: 24px;
  --space-xl: 32px;
  --space-2xl: 48px;
  --space-3xl: 64px;
  
  /* Radii */
  --radius-sm: 4px;
  --radius-md: 6px;
  --radius-lg: 8px;
  
  /* Transitions */
  --transition-fast: 0.12s cubic-bezier(0.4, 0, 0.2, 1);
  --transition-base: 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
```

---

## Future Considerations

### Potential Enhancements
- Dark/light mode toggle
- Customizable density settings
- Thread preview on hover
- Keyboard shortcuts
- Advanced filtering UI
- Real-time updates with subtle animations

### Maintenance
- Review spacing scale quarterly
- Test new browser features
- Monitor performance metrics
- Gather user feedback on scannability
- A/B test layout variations
