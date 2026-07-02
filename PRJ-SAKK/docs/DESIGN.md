# SAKK Design System — الهوية البصرية

> **Damascene Burgundy · Antique Gold · Warm Marble**
> Light-only · RTL-first · WCAG 2.2 AA
> Single source of truth — mirrors `globals.css`, `app_colors.dart`, `app_theme.dart`, `sakk-tokens.css`, `admin.css`

---

## 1. Brand Foundation

### 1.1 Identity

| Attribute | Value |
|-----------|-------|
| Brand name | صكك — SAKK |
| Tagline | محفظتك الرقمية السورية — Your Syrian Digital Wallet |
| Voice | Professional, warm, trustworthy |
| Language | Arabic (primary), English (secondary) |
| Direction | RTL-first, LTR for card numbers/data |
| Platform | Flutter mobile · Next.js web · Laravel admin |

### 1.2 Brand Values (from PRD §2)

| Arabic | English | Design Implication |
|--------|---------|-------------------|
| الأمان | Security | Clear visual hierarchy, no hidden actions, confirmation on destructive ops |
| الشمولية | Inclusivity | RTL-first, large touch targets (44px+), readable type, WCAG AA |
| الابتكار | Innovation | Glass morphism, subtle motion, geometric patterns |
| الثقة | Trust | Consistent spacing, predictable interactions, no surprises |

### 1.3 Key Design Laws

1. **Light-only.** No dark mode. The canvas is always marble/white. Only card faces carry saturated colour.
2. **Solid colours only.** No text gradients, no rainbow. Exceptions: card faces (gradient backgrounds) and admin gold CTAs.
3. **Gold is decorative.** Gold (#B58A3C) fails WCAG AA for body text. Use for large headings (18px+), icons, borders, accents — never for body copy.
4. **Every figure uses tabular numerals.** Money, balances, stats always `font-variant-numeric: tabular-nums`.
5. **Arabic primary, English secondary.** All UI strings start in Arabic. English fits the available space.

---

## 2. Color System

### 2.1 Brand Palette

| Token | Hex | Usage |
|-------|-----|-------|
| **Primary (Damascene Burgundy)** | `#6E1B2D` | Buttons, links, active states, info badges |
| Primary Hover | `#8E2A3D` | Button hover |
| Primary Light | `#F7E9EC` | Soft backgrounds, selected states, info-light |
| Primary Dark | `#4A1320` | Sidebar, pressed states |
| **Secondary (Rosewood)** | `#8E2A3D` | Secondary actions, hover states |
| Secondary Light | `#F0DDE1` | Soft secondary bg |
| **Accent (Antique Gold)** | `#B58A3C` | Headings (18px+), icons, decorative borders |
| Accent Hover | `#C9A24B` | Gold button hover |
| Accent Light | `#D9B978` | Gold background tints |
| **Gold (Bright)** | `#C9A24B` | Card gradients, CTA buttons |
| Gold Hover | `#D9B978` | Gold hover |
| Gold Light | `#E8D0A0` | Gold bg tints |

### 2.2 Surface Palette

| Token | Hex | Usage |
|-------|-----|-------|
| **Background (Warm Marble)** | `#F7F3EE` | App canvas, page bg |
| Background Secondary | `#F2ECE5` | Input fill, section alt bg |
| **Surface** | `#FFFFFF` | Cards, sheets, modals, dropdowns |
| Surface Hover | `#FAF7F4` | Card hover, item hover |
| Surface Active | `#F5F0EB` | Active/pressed state |

### 2.3 Text Palette

| Token | Hex | WCAG AA on bg | WCAG AA on white |
|-------|-----|---------------|-------------------|
| **Text Primary** | `#2A1A1F` | 15.02:1 ✅ | 16.59:1 ✅ |
| Text Secondary | `#6E5F63` | 5.46:1 ✅ | 6.03:1 ✅ |
| Text Tertiary | `#786469` | 4.97:1 ✅ | 5.49:1 ✅ |
| Text Muted | `#86787B` | 3.81:1 ❌ body / ✅ 18px+ | 4.21:1 ❌ body / ✅ 18px+ |
| Text Hint | `#A99FA2` | Placeholder only, decorative | — |

> **Note:** Text Muted `#86787B` fails WCAG AA for body text (<18px). Use only for disabled states, placeholders, and decorative text. For secondary body copy, always use Text Secondary `#6E5F63`.

### 2.4 Semantic Palette

| Token | Hex | Usage | WCAG AA on white |
|-------|-----|-------|-------------------|
| **Success** | `#1F9D55` | Positive balances, success badges | 3.49:1 ❌ body / ✅ 18px+ |
| Success Light | `#E4F6EC` | Success bg | — |
| **Error** | `#C0392B` | Errors, destructive actions | 5.44:1 ✅ |
| Error Light | `#FBEAE8` | Error bg | — |
| **Warning** | `#B58A3C` | Pending states, warnings | 3.15:1 ❌ body / ✅ 18px+ |
| Warning Light | `#F7EEDA` | Warning bg | — |
| **Info** | `#6E1B2D` | Info badges (same as primary) | 11.30:1 ✅ |
| Info Light | `#F7E9EC` | Info bg (same as primary light) | — |

> **Recommendation:** For success/warning body text on white surfaces, use darker variants: Success text `#155E36`, Warning text `#B45309` (from admin.css).

### 2.5 Card Gradients

| Card Type | Gradient | Start | End |
|-----------|----------|-------|-----|
| **Visa** | Velvet Wine | `#7A2236` | `#4A1320` |
| **Mastercard** | Deep Wine | `#9B3A4D` | `#6E1B2D` |
| **Gold** | Antique Gold | `#C9A24B` | `#8F6B2A` |
| **Platinum** | Warm Stone | `#8A7E74` | `#5C534C` |
| **Accent** | Wine→Gold | `#6E1B2D` | `#B58A3C` |

All card gradients use `145deg` angle (or `135deg` for accent).

### 2.6 Wallet Accents

| Wallet | Colour |
|--------|--------|
| USD | `#1F9D55` (success green) |
| SYP | `#B58A3C` (gold) |

### 2.7 Borders

| Token | Value |
|-------|-------|
| Border | `rgba(0, 0, 0, 0.08)` |
| Border Hover | `rgba(0, 0, 0, 0.14)` |
| Ring (focus) | `#6E1B2D` |
| Ring Focus | `#8B2A3D` |

### 2.8 (Admin) Sidebar Palette

| Token | Hex |
|-------|-----|
| Sidebar bg | `#4A1320` (wine-dark) |
| Sidebar bg soft | `#5E1828` |
| Sidebar text | `rgba(247,243,238,0.60)` |
| Sidebar text strong | `#F7F3EE` |
| Sidebar hover | `rgba(247,243,238,0.07)` |
| Sidebar active bg | `rgba(181,138,60,0.18)` |
| Sidebar active text | `#C9A24B` |
| Sidebar border | `rgba(247,243,238,0.09)` |

> **Brand rule:** The wine-dark sidebar is the ONLY dark surface in the product. It signals "admin power" and never carries user-facing content.

---

## 3. Typography

### 3.1 Font Stack

| Role | Font | Weights |
|------|------|---------|
| **Primary (all UI)** | IBM Plex Sans Arabic | 300, 400, 500, 600, 700 |
| **Mono (code/data)** | IBM Plex Mono | 400, 600 |
| **Admin (web panel)** | IBM Plex Sans Arabic (same) | 300, 400, 500, 600, 700 |

> Self-hosted woff2 at `/sakk-assets/fonts/`. All weights except 300 available (300 removed per a11y audit — too thin for legibility).

### 3.2 Type Scale

| Token | Size | Line Height | Usage |
|-------|------|-------------|-------|
| `--fs-xs` | 12px / 0.75rem | 1.4 | Labels, badges, secondary meta |
| `--fs-sm` | 14px / 0.875rem | 1.4 | Body secondary, hints |
| `--fs-base` | 16px / 1rem | 1.5 | Body text, inputs, buttons |
| `--fs-lg` | 18px / 1.125rem | 1.4 | Card titles, subheadings |
| `--fs-xl` | 20px / 1.25rem | 1.3 | Section headings |
| `--fs-2xl` | 24px / 1.5rem | 1.2 | Page headings |
| `--fs-3xl` | 32px / 2rem | 1.2 | Hero headings |
| `--fs-4xl` | 40px / 2.5rem | 1.1 | Large hero |
| `--fs-5xl` | 56px / 3.5rem | 1.1 | Display |
| `--fs-6xl` | 72px / 4.5rem | 1.1 | Marketing hero |

### 3.3 Weight Semantics

| Weight | Usage |
|--------|-------|
| 300 (Light) | REMOVED — not legible at body sizes |
| 400 (Regular) | Body text, input content |
| 500 (Medium) | Body emphasis, nav items |
| 600 (SemiBold) | Buttons, card titles, active nav |
| 700 (Bold) | Headings, stat values, CTAs |

### 3.4 Tabular Numerals

Every money value, balance, stat, and figure MUST use tabular numerals:

```css
font-variant-numeric: tabular-nums;
font-feature-settings: "tnum" 1;
```

Applied via classes `.tnum`, `.money`, `[data-money]`, and auto-applied to `.stat-value`, `.balance`, `.kpi-value`.

---

## 4. Spacing & Layout

### 4.1 Spacing Scale

| Token | Rem | px | Usage |
|-------|-----|-----|-------|
| `--sp-1` | 0.25rem | 4px | Micro |
| `--sp-2` | 0.5rem | 8px | Element gap (sm) |
| `--sp-3` | 0.75rem | 12px | Element gap |
| `--sp-4` | 1rem | 16px | Content gap (sm), card padding (mobile) |
| `--sp-5` | 1.25rem | 20px | Admin card body |
| `--sp-6` | 1.5rem | 24px | Card padding, content gap, grid gap |
| `--sp-8` | 2rem | 32px | Content gap (lg), card padding (lg) |
| `--sp-10` | 2.5rem | 40px | Section padding |
| `--sp-12` | 3rem | 48px | Section padding |
| `--sp-3xl` | 4rem | 64px | Section (sm) |
| `--sp-4xl` | 6rem | 96px | Section |

### 4.2 Section Vertical Padding

| Token | Value | Usage |
|-------|-------|-------|
| `--spacing-section` | 8rem (128px) | Default section |
| `--spacing-section-sm` | 5rem (80px) | Compact section |
| `--spacing-section-lg` | 10rem (160px) | Hero/full section |

### 4.3 Layout Dims (Admin)

| Token | Value |
|-------|-------|
| Navbar height | 64px |
| Sidebar width | 272px (1280px+: 300px, 768-1023px: 240px) |
| Sidebar collapsed | 72px |
| Rail width | 56px |
| Mobile bottom nav | 60px + safe-area-bottom |

### 4.4 Border Radius

| Token | Value | Usage |
|-------|-------|-------|
| `--r-sm` / `--radius-sm` | 8px | Buttons, inputs, chips |
| `--r-md` / `--radius-md` | 12px | Inputs, secondary cards |
| `--r-lg` / `--radius-lg` | 16px | Cards, modals |
| `--r-xl` / `--radius-xl` | 20px | Modals, dialogs |
| `--r-2xl` | 24px | Admin cards, empty states |
| `--r-full` | 9999px | Badges, pills, avatars |

---

## 5. Shadows & Elevation

### 5.1 Drop Shadows

| Token | Value | Usage |
|-------|-------|-------|
| `--shadow-sm` | `0 1px 3px rgba(42,26,31,0.04)` | Cards, buttons |
| `--shadow-md` | `0 4px 12px -2px rgba(42,26,31,0.05), 0 2px 4px -2px rgba(42,26,31,0.03)` | Card hover, dropdowns |
| `--shadow-lg` | `0 12px 24px -6px rgba(42,26,31,0.06), 0 4px 8px -4px rgba(42,26,31,0.03)` | Modals, menus |
| `--shadow-xl` | `0 20px 48px -8px rgba(42,26,31,0.08), 0 8px 16px -6px rgba(42,26,31,0.03)` | Large modals |

### 5.2 Glass Shadows

| Token | Value |
|-------|-------|
| `--shadow-glass` | `0 8px 32px rgba(42,26,31,0.05), 0 0 0 1px rgba(0,0,0,0.04)` |
| `--shadow-glass-lg` | `0 16px 48px rgba(42,26,31,0.06), 0 0 0 1px rgba(0,0,0,0.05)` |

### 5.3 Glow Effects

| Token | Value | Usage |
|-------|-------|-------|
| `--shadow-glow-primary` | `0 0 24px rgba(110,27,45,0.12), 0 0 48px rgba(110,27,45,0.05)` | Primary accents |
| `--shadow-glow-accent` | `0 0 24px rgba(181,138,60,0.15), 0 0 48px rgba(181,138,60,0.06)` | Gold accents |
| `--shadow-glow-gold` | `0 0 24px rgba(201,162,75,0.15), 0 0 48px rgba(201,162,75,0.06)` | Gold bright |
| Glow Success | `0 0 24px rgba(16,185,129,0.3), 0 0 48px rgba(16,185,129,0.1)` | Success states |
| Glow Error | `0 0 24px rgba(239,68,68,0.3), 0 0 48px rgba(239,68,68,0.1)` | Error states |
| Glow Warning | `0 0 24px rgba(245,158,11,0.3), 0 0 48px rgba(245,158,11,0.1)` | Warning states |

### 5.4 Admin Shadows

| Token | Value |
|-------|-------|
| `--shadow-xs` | `0 1px 2px rgba(42,26,31,0.04)` |
| `--sh-wine` | `0 10px 30px rgba(110,27,45,0.18)` |
| `--sh-gold` | `0 8px 24px rgba(181,138,60,0.22)` |

---

## 6. Motion

### 6.1 Easing Curves

| Token | Curve | Usage |
|-------|-------|-------|
| `--ease-out-expo` | `cubic-bezier(0.19, 1, 0.22, 1)` | Entrances, hero animations |
| `--ease-in-out-expo` | `cubic-bezier(0.87, 0, 0.13, 1)` | Smooth transitions |
| `--ease-spring` | `cubic-bezier(0.34, 1.56, 0.64, 1)` | Micro-interactions, press |
| `--ease-glass` | `cubic-bezier(0.23, 1, 0.32, 1)` | Glass hover, float |
| `--ease-smooth` | `cubic-bezier(0.4, 0, 0.2, 1)` | Default UI transitions |

### 6.2 Duration

| Token | Value | Usage |
|-------|-------|-------|
| Fast | 120–150ms | Press feedback, toggle, icon swap |
| Normal | 220–300ms | Card hover, tab switch, fade |
| Slow | 380–500ms | Page transitions, hero reveals |

### 6.3 Animations

| Name | Duration | Easing | Keyframes | Usage |
|------|----------|--------|-----------|-------|
| `fade-in` | 0.4s | ease-smooth | `0→1 opacity` | Page load, content reveal |
| `fade-up` | 0.5s | ease-out-expo | `translateY(24px)→0, 0→1` | Section entrances |
| `scale-in` | 0.35s | ease-out-expo | `scale(0.92)→1, 0→1` | Modal, dialog |
| `shimmer` | 1.8–2.5s | linear | `bg-position -200%→200%` | Skeleton loading |
| `float` | 4s / 6s | ease-glass | `translateY(0→-10→-4→0)` | Decorative elements |
| `glow-pulse` | 3s | ease-smooth | `box-shadow intensity` | Status indicators |
| `rotate-slow` | 20s | linear | `0°→360°` | Loading spinners |
| `draw-line` | 1.5s | ease-out-expo | `stroke-dashoffset` | SVG path reveal |
| `pulse-soft` | 3s | ease-smooth | `opacity 0.6→1→0.6` | Status pulses |
| `pulse-ring` | 2.5s | ease-out | `scale 1→2, opacity 0.08→0` | Notification rings |
| `goldPulse` | 2s | ease-out | `box-shadow 0→7→0` | Live data dot |

### 6.4 Stagger Delays

| Class | Delay | Usage |
|-------|-------|-------|
| `.stagger-1` | 0ms | First item in list |
| `.stagger-2` | 80ms | Second item |
| `.stagger-3` | 160ms | Third item |
| `.stagger-4` | 240ms | Fourth item |
| `.stagger-5` | 320ms | Fifth item |
| `.stagger-6` | 400ms | Sixth item |

### 6.5 Reduced Motion

```css
@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}
```

### 6.6 Touch Device Hover Gate

```css
@media (hover: none) and (pointer: coarse) {
  .hover-lift { transform: none !important; }
  .hover-glow { box-shadow: none !important; }
}
```

---

## 7. Glass Morphism

### 7.1 Glass Surface

SAKK glass is **frosted marble** — always on light backgrounds, never dark.

```css
.glass {
  background: rgba(255, 255, 255, 0.7);
  backdrop-filter: blur(16px) saturate(140%);
  border: 1px solid rgba(0, 0, 0, 0.06);
  border-radius: 16px;
}
```

### 7.2 Glass Card Variant

```css
.card-glass {
  background: rgba(255, 255, 255, 0.7);
  backdrop-filter: blur(16px);
  border: 1px solid rgba(0, 0, 0, 0.06);
  border-radius: 24px;
  box-shadow: var(--shadow-glass);
  transition: all 0.3s ease-smooth;
}
.card-glass:hover {
  background: rgba(255, 255, 255, 0.85);
  border-color: rgba(0, 0, 0, 0.12);
  box-shadow: var(--shadow-glass-lg);
  transform: translateY(-2px);
}
```

### 7.3 Admin Glass

```css
.glass-marble {
  background: rgba(255, 255, 255, 0.62);
  backdrop-filter: blur(16px) saturate(140%);
  border: 1px solid rgba(110, 27, 45, 0.10);
  border-radius: 16px;
  box-shadow: var(--shadow-md);
}
```

### 7.4 Glass Border System

| Token | Value |
|-------|-------|
| `--glass-border` | `rgba(0, 0, 0, 0.06)` |
| `--glass-border-hover` | `rgba(0, 0, 0, 0.12)` |
| Admin glass border | `rgba(110, 27, 45, 0.10)` |
| Chart card border | `rgba(181, 138, 60, 0.12)` (gold hairline) |

---

## 8. Patterns

### 8.1 Damascene Geometric (8-Pointed Star)

```css
.bg-geometric {
  background-image:
    radial-gradient(circle at 25% 25%, rgba(110, 27, 45, 0.03) 0%, transparent 50%),
    radial-gradient(circle at 75% 75%, rgba(181, 138, 60, 0.03) 0%, transparent 50%),
    radial-gradient(circle at 50% 50%, rgba(201, 162, 75, 0.02) 0%, transparent 50%);
}
```

### 8.2 Arabesque Overlay

```css
.bg-arabesque {
  background-image:
    repeating-linear-gradient(45deg, transparent, transparent 20px,
      rgba(110,27,45,0.02) 20px, rgba(110,27,45,0.02) 21px),
    repeating-linear-gradient(-45deg, transparent, transparent 20px,
      rgba(181,138,60,0.015) 20px, rgba(181,138,60,0.015) 21px);
}
```

### 8.3 Damask Tile (مُعَيَّن دمشقي)

```css
.bg-damask {
  background-image:
    linear-gradient(45deg, rgba(110,27,45,0.03) 25%, transparent 25%),
    linear-gradient(-45deg, rgba(110,27,45,0.03) 25%, transparent 25%),
    linear-gradient(45deg, transparent 75%, rgba(110,27,45,0.03) 75%),
    linear-gradient(-45deg, transparent 75%, rgba(110,27,45,0.03) 75%);
  background-size: 40px 40px;
  background-position: 0 0, 0 20px, 20px -20px, -20px 0px;
}
```

### 8.4 Grid & Dot Patterns

| Class | Style | Size |
|-------|-------|------|
| `.bg-grid` | Line grid (ink@3%) | 40px |
| `.bg-grid-sm` | Line grid (ink@3%) | 20px |
| `.bg-dot` | Dot grid (ink@4%) | 20px |
| `.bg-dot-sm` | Dot grid (ink@4%) | 12px |

### 8.5 Noise Overlay

Subtle SVG noise on body:

```css
body::before {
  content: "";
  position: fixed;
  inset: 0;
  z-index: 9999;
  pointer-events: none;
  opacity: 0.03;
  background-image: url("data:image/svg+xml,...fractalNoise...");
  background-size: 256px 256px;
}
```

### 8.6 Admin Hero Gradient

```css
--grad-hero: radial-gradient(1200px 600px at 80% -10%, #F7E9EC 0%, transparent 60%),
             radial-gradient(900px 500px at -10% 10%, #F7EEDA 0%, transparent 55%),
             var(--marble);
```

---

## 9. RTL Guidelines

### 9.1 Direction

- Arabic UI: `direction: rtl`
- All spacing uses `margin-inline-start/end` or `padding-inline-start/end` — never `left`/`right`
- Flexbox gap preferred over margin for spacing
- Card numbers: always LTR (`direction: ltr`) regardless of UI direction
- Avatar stacks: `flex-direction: row-reverse` for RTL stacking

### 9.2 RTL-Aware Utilities

| Property | RTL Equivalent |
|----------|----------------|
| `left` | `inset-inline-start` |
| `right` | `inset-inline-end` |
| `margin-left` | `margin-inline-start` |
| `padding-right` | `padding-inline-end` |
| `border-left` | `border-inline-start` |
| `translateX(-24px)` | Same (works for both) |
| `text-align: left` | `text-align: start` |
| `text-align: right` | `text-align: end` |

### 9.3 Arabic Typography Rules

- IBM Plex Sans Arabic handles Arabic glyphs natively — no separate font needed
- Line height: 1.6 for body (Arabic needs more leading than Latin)
- Font weight 400 at 16px minimum for body legibility
- Numbers in Arabic text remain Latin (Western Arabic numerals) for financial context
- Currency symbols before amount: `$50` not `50$`

### 9.4 LTR Exceptions

Elements that remain LTR in RTL mode:
- Card numbers (`direction: ltr; letter-spacing: 0.12em`)
- Crypto addresses
- Code snippets
- Email addresses

---

## 10. Component Library

### 10.1 Button

**Base:**
```css
.sakk-btn {
  display: inline-flex; align-items: center; justify-content: center;
  gap: 8px; padding: 0.5rem 1.125rem;
  font-size: 14px; font-weight: 700;
  border-radius: 12px; border: 1.5px solid transparent;
  cursor: pointer; outline: none;
  transition: all 0.12s ease-out;
  white-space: nowrap; line-height: 1.25;
}
```

| Variant | Default | Hover | Active | Disabled | Focus |
|---------|---------|-------|--------|----------|-------|
| **Primary** | bg `#6E1B2D`, fg white, shadow-sm | bg `#4A1320`, shadow-md, translateY(-1px) | darker | opacity 0.5, no-pointer | outline 2px wine |
| **Gold** | bg `linear-gradient(135deg,#C9A24B→#8F6B2A)`, fg white, shadow gold | filter brightness(1.08), shadow-lg | darker | opacity 0.5 | outline 2px wine |
| **Ghost** | bg transparent, fg `#6E5F63` | bg `#F2ECE5`, fg `#2A1A1F` | bg `#EAE0D6` | opacity 0.5 | outline 2px wine |
| **Outline** | bg transparent, fg `#6E1B2D`, border `#6E1B2D` | bg `#F5E8EB` | bg `#EDD0D5` | opacity 0.5 | outline 2px wine |
| **Danger** | bg `#C0392B`, fg white | bg `#922B21`, translateY(-1px) | darker | opacity 0.5 | outline 2px wine |
| **Icon** | same as ghost, `padding: 0.5rem`, `aspect-ratio: 1` | — | — | — | — |
| **Secondary** | bg white, fg `#2A1A1F`, border `#E8DED6` | bg `#F2ECE5` | bg `#EAE0D6` | opacity 0.5 | outline 2px wine |

**Sizes:**

| Size | Padding | Font | Radius |
|------|---------|------|--------|
| xs | `0.25rem 0.625rem` | 12px | 8px |
| sm | `0.375rem 0.75rem` | 12px | 8px |
| md (default) | `0.5rem 1.125rem` | 14px | 12px |
| lg | `0.75rem 1.5rem` | 16px | 16px |
| xl | `0.875rem 2rem` | 18px | 16px |
| icon | `0.5rem` (square) | — | 12px |
| icon-sm | `0.3rem` (square) | — | 8px |

**Loading state:** Button shows spinner overlay, `pointer-events: none`, `opacity: 0.75`.

### 10.2 Input

**Base:**
```css
.sakk-input {
  width: 100%;
  padding: 0.625rem 0.875rem;
  font-size: 14px; font-weight: 500;
  color: #2A1A1F;
  background: #F2ECE5;
  border: 1.5px solid #E8DED6;
  border-radius: 12px;
  transition: all 0.12s;
  outline: none;
  line-height: 1.5;
  direction: rtl;
}
```

| State | Style |
|-------|-------|
| **Default** | bg `#F2ECE5`, border `#E8DED6` |
| **Hover** | border `#D4C4B8` |
| **Focus** | bg white, border `#6E1B2D`, `box-shadow: 0 0 0 3px rgba(110,27,45,0.18)` |
| **Error** | border `#C0392B`, `box-shadow: 0 0 0 3px rgba(192,57,43,0.15)` |
| **Success** | border `#1F9D55`, `box-shadow: 0 0 0 3px rgba(31,157,85,0.15)` |
| **Disabled** | bg `#EAE0D6`, opacity 0.65, cursor not-allowed |
| **With icon (start)** | `padding-inline-start: 2.75rem` |
| **With icon (end)** | `padding-inline-end: 2.75rem` |

**Floating label (Flutter):**
- Label colour: `#6E5F63`, size 14px
- Hint colour: `#A99FA2`, size 14px
- Helper text: 12px, `#6E5F63`
- Error message: 12px, `#C0392B`, weight 600, with icon

**Textarea:** Same styles, `min-height` as needed.

**Select/Input group:** Same as input, `cursor: pointer`.

### 10.3 Card

**Base (white card):**
```css
.sakk-card {
  background: #FFFFFF;
  border: 1px solid #E8DED6;
  border-radius: 24px;
  box-shadow: 0 1px 3px rgba(42,26,31,0.04);
  overflow: hidden;
  transition: box-shadow 0.22s, transform 0.22s;
}
```

| Variant | Default | Hover | Active/Press |
|---------|---------|-------|-------------|
| **Default** | shadow-sm | shadow-md | — |
| **Hover-lift** | shadow-sm, translateY(0) | shadow-lg, translateY(-2px) | — |
| **Glass** | glass bg, border, shadow-glass | bg 0.85, shadow-glass-lg, translateY(-2px) | scale(0.98) |
| **Press** | — | — | scale(0.97) |

**Card sections:**
- Header: `padding: 1rem 1.5rem`, bottom border `#F0E8DF`
- Body: `padding: 1.5rem`
- Footer: `padding: 1rem 1.5rem`, top border `#F0E8DF`, bg `#F2ECE5`

**Stat card (admin):**
```css
.sakk-stat {
  border-radius: 24px;
  padding: 1.25rem;
  position: relative;
  overflow: hidden;
}
.sakk-stat::after {
  content: '';
  position: absolute;
  inset-inline-end: 0; top: 0; bottom: 0;
  width: 3px;
  background: #B58A3C;
  opacity: 0;
  transition: opacity 0.22s;
}
.sakk-stat:hover::after { opacity: 1; }
```

**Velvet card (card face):**
```css
.sakk-velvet-card {
  background: linear-gradient(145deg, #7A2236 0%, #4A1320 100%);
  border-radius: 16px;
  box-shadow: 0 10px 30px rgba(110,27,45,0.18);
  border: 1px solid rgba(181,138,60,0.45);
  color: #fff;
  position: relative;
  overflow: hidden;
}
.sakk-velvet-card::after {
  content: '';
  position: absolute; inset: 0;
  background: linear-gradient(105deg, transparent 30%, rgba(201,162,75,0.28) 47%, transparent 62%);
  mix-blend-mode: screen;
  pointer-events: none;
}
```

### 10.4 Bottom Navigation

**Mobile (Flutter + web responsive):**

| Token | Value |
|-------|-------|
| Height | 60px + env(safe-area-inset-bottom) |
| Background | white |
| Top border | `1px solid #E8DED6` |
| Shadow | shadow-md |
| Active colour | `#B58A3C` (gold) |
| Inactive colour | `#A0909A` |
| Active indicator | 2.5px gold bar at top, `inset-inline: 20%` |

**Flutter BottomNavigationBar (app_theme.dart):**
- bg: white
- Selected: `#2A1A1F` (textPrimary)
- Unselected: `#A99FA2` (textHint)
- Type: fixed
- Selected label: 12px, weight 600
- Unselected label: 12px

### 10.5 AppBar

| Token | Value |
|-------|-------|
| Background | transparent (shows surface through) |
| Elevation | 0 (flat) |
| Scrolled under elevation | 0 (no shadow on scroll) |
| Title alignment | center |
| Icon colour | `#2A1A1F` |
| Title style | 18px, weight 600, `#2A1A1F` |

### 10.6 Dialog

**Base:**
```css
/* Backdrop */
.modal-backdrop {
  background: rgba(42,26,31,0.48);
  backdrop-filter: blur(4px);
}
/* Box */
.modal-box {
  background: #FFFFFF;
  border-radius: 24px;
  box-shadow: var(--shadow-xl);
  max-height: calc(100dvh - 2rem);
  border: 1px solid #E8DED6;
}
```

| Variant | Header | Footer |
|---------|--------|--------|
| **Default** | `padding: 1.25rem 1.5rem`, border-bottom `#F0E8DF` | right-aligned actions, bg `#F2ECE5` |
| **Destructive/Confirm** | bg `#FBEAE8`, title `#922B21` | danger + cancel buttons |

Body: `padding: 1.5rem`. Title: 18px, weight 700.

### 10.7 Snackbar / Toast

| Variant | Background | Text | Border |
|---------|-----------|------|--------|
| **Success** | `#E4F6EC` | `#155E36` | `#1F9D55` |
| **Error** | `#FBEAE8` | `#922B21` | `#C0392B` |
| **Warning** | `#F7EEDA` | `#B45309` | `#B58A3C` |
| **Info** | `#F7E9EC` | `#6E1B2D` | `#6E1B2D` |

- Behaviour: floating (not inline)
- Radius: 12px
- Shadow: shadow-lg
- Animation: slide-up 0.28s, auto-dismiss 4.7s
- Position: bottom, `inset-inline-start: 1.25rem`

### 10.8 Chip

| Token | Value |
|-------|-------|
| Background | `#F2ECE5` |
| Selected bg | `#F7E9EC` |
| Label | 12px, `#2A1A1F` |
| Radius | 8px |

**Admin badge variants:**

| Variant | Background | Text |
|---------|-----------|------|
| Success/Active | `#D4F5E2` | `#155E36` |
| Warning/Pending | `#FEF3C7` | `#B45309` |
| Danger/Rejected | `#FDE8E6` | `#922B21` |
| Info/Frozen | `#D6EAF8` | `#154360` |
| Wine | `#EDD0D5` | `#6E1B2D` |
| Gold | `rgba(181,138,60,0.15)` | `#8F6B2A` |
| Cream | `#F7F3EE`, border `#E8DED6` | `#6E5F63` |
| Slate | `#EAE0D6` | `#6E5F63` |
| Outline | transparent, border `#D4C4B8` | `#6E5F63` |

### 10.9 Skeleton

```css
.skeleton {
  background: linear-gradient(90deg, #FFFFFF 25%, #FAF7F4 50%, #FFFFFF 75%);
  background-size: 200% 100%;
  animation: shimmer 1.8s infinite linear;
  border-radius: 12px;
}
```

**Admin skeleton sizes:**

| Class | Shape |
|-------|-------|
| `.sakk-skeleton-text` | 14px height, 60% width |
| `.sakk-skeleton-title` | 24px height, 40% width |
| `.sakk-skeleton-card` | 120px height, radius 20px |
| `.sakk-skeleton-pill` | 32px height, 100px width, radius 999px |
| `.sakk-skeleton-chart` | 200px height, radius 16px |

### 10.10 Data Table (Admin)

| Element | Style |
|---------|-------|
| Wrapper | bg white, border `#E8DED6`, radius 24px, shadow-sm |
| Toolbar | `padding: 1rem 1.25rem`, border-bottom `#F0E8DF` |
| Header th | `padding: 0.75rem 1.25rem`, 12px, weight 700, colour `#A0909A`, bg `#F2ECE5` |
| Body td | `padding: 0.875rem 1.25rem`, 14px, colour `#6E5F63`, weight 500 |
| Row hover | bg `#F2ECE5` |
| Sortable th | cursor pointer, hover colour `#6E1B2D` |
| Empty state | `padding: 3rem 1.5rem`, centered, colour `#A0909A` |

### 10.11 Tabs (Admin)

| Element | Style |
|---------|-------|
| Container | `display: flex`, `border-bottom: 1.5px solid #E8DED6`, overflow-x auto |
| Tab item | `padding: 0.75rem 1.125rem`, 14px, weight 600, colour `#A0909A` |
| Active tab | colour `#6E1B2D`, `border-bottom: 2px solid #6E1B2D`, weight 700 |
| Tab count | pill inside tab, bg `#F2ECE5` normally, `#F5E8EB` when active |

### 10.12 Pagination (Admin)

| Element | Style |
|---------|-------|
| Page link | `min-width: 34px`, height 34px, 14px, weight 600, colour `#6E5F63` |
| Active | bg `#6E1B2D`, fg white |
| Hover | colour `#6E1B2D`, border `#6E1B2D` |

### 10.13 Avatar

| Size | Dims | Font |
|------|------|------|
| sm | 28px | 12px |
| md | 36px | 14px |
| lg | 44px | 16px |
| xl | 56px | 18px |

- Default bg: `#6E1B2D`, fg white, weight 700
- Border-radius: full circle
- Image: `object-fit: cover`

### 10.14 Switch / Toggle

| Element | Style |
|---------|-------|
| Track (off) | bg `#D4C4B8`, 42×24px, radius 999px |
| Track (on) | bg `#6E1B2D` |
| Thumb | 20×20px, white, shadow |
| Transition | inset-inline-start 0.12s |

### 10.15 Progress Indicator

| Token | Value |
|-------|-------|
| Colour | `#2A1A1F` (textPrimary) |
| Linear track | `rgba(169,159,162,0.2)` |

---

## 11. Copy Guidelines

### 11.1 Language Rules

| Context | Language | Example |
|---------|----------|---------|
| Primary UI | Arabic | "تحويل", "محفظتي", "الرصيد" |
| Secondary labels | English | "USD", "SYP", "Settings" |
| Error messages | Arabic | "حدث خطأ، حاول مرة أخرى" |
| Success messages | Arabic | "تمت العملية بنجاح" |
| Data/numbers | Latin | "$50.00", "1,234.56" |
| System terms | English | "KYC", "PIN", "2FA", "API" |
| Card holder name | Latin uppercase | "AHMAD ALI" |

### 11.2 Tone

| Attribute | Direction |
|-----------|-----------|
| Professional | Formal Arabic (فصحى مبسطة), not dialect |
| Warm | Use "من فضلك", "شكراً", respectful address |
| Trustworthy | Clear error messages, never vague |
| Concise | Short phrases, minimal text on buttons |

### 11.3 Microcopy Patterns

**Error states:**
```
"حدث خطأ، حاول مرة أخرى" — General error
"الرصيد غير كافٍ" — Insufficient balance
"يرجى التحقق من المعلومات" — Validation error
"الجهاز بانتظار الموافقة" — Device pending
"تم رفض هذا الجهاز" — Device rejected
"48 ساعة أمنية متبقية" — Security hold
```

**Empty states:**
```
"لا توجد معاملات بعد" — No transactions
"لا توجد بطاقات بعد" — No cards
"لم تقم بإضافة أهداف ادخار" — No savings goals
"قم بدعوة أصدقائك واربح $5" — Referral CTA
```

**Loading states:**
```
"جاري التحميل..." — Loading
"جاري تنفيذ العملية..." — Processing
"جاري تأكيد الدفع..." — Confirming payment
```

**Confirmation:**
```
"هل أنت متأكد؟" — Are you sure?
"لن يمكنك التراجع عن هذا الإجراء" — Cannot be undone
"تأكيد الدفع" — Confirm payment
"إلغاء" — Cancel
"حذف" — Delete
"تأكيد" — Confirm
```

### 11.4 Number Formatting

| Locale | Decimal | Grouping | Example |
|--------|---------|----------|---------|
| Arabic UI | `.` (dot) | `,` (comma) | ١٢٣,٤٥٦.٧٨ |
| English UI | `.` (dot) | `,` (comma) | 123,456.78 |
| Always | Latin digits | — | 123 not ١٢٣ |

---

## 12. WCAG 2.2 AA Compliance Matrix

### 12.1 Contrast Pass/Fail

| Pair | Ratio | AA Normal | AA Large | Status |
|------|-------|-----------|----------|--------|
| Primary `#6E1B2D` on white `#FFFFFF` | 11.30:1 | ✅ | ✅ | **PASS** |
| Primary `#6E1B2D` on bg `#F7F3EE` | 10.23:1 | ✅ | ✅ | **PASS** |
| Text Primary `#2A1A1F` on white | 16.59:1 | ✅ | ✅ | **PASS** |
| Text Primary `#2A1A1F` on bg | 15.02:1 | ✅ | ✅ | **PASS** |
| Text Secondary `#6E5F63` on white | 6.03:1 | ✅ | ✅ | **PASS** |
| Text Secondary `#6E5F63` on bg | 5.46:1 | ✅ | ✅ | **PASS** |
| Text Tertiary `#786469` on white | 5.49:1 | ✅ | ✅ | **PASS** |
| Text Tertiary `#786469` on bg | 4.97:1 | ✅ | ✅ | **PASS** |
| Text Muted `#86787B` on white | 4.21:1 | ❌ | ✅ | **Large only** |
| Text Muted `#86787B` on bg | 3.81:1 | ❌ | ✅ | **Large only** |
| Gold `#B58A3C` on white | 3.15:1 | ❌ | ✅ | **Large only** |
| Gold `#B58A3C` on bg | 2.85:1 | ❌ | ❌ | **Decorative only** |
| Error `#C0392B` on white | 5.44:1 | ✅ | ✅ | **PASS** |
| Success `#1F9D55` on white | 3.49:1 | ❌ | ✅ | **Large only** |
| Warning `#B58A3C` on white | 3.15:1 | ❌ | ✅ | **Large only** |
| White on Primary `#6E1B2D` | 11.30:1 | ✅ | ✅ | **PASS** |
| White on Primary Dark `#4A1320` | 14.94:1 | ✅ | ✅ | **PASS** |

### 12.2 WCAG Requirements Met

| Criterion | Implementation |
|-----------|---------------|
| **1.4.1 Use of Color** | Information never conveyed by colour alone. Icons + labels accompany all colour-coded states. |
| **1.4.3 Contrast (AA)** | Body text ≥4.5:1. Gold/success/warning used only at ≥18px or decorative. |
| **1.4.4 Resize Text** | `rem` units everywhere. No text size lock. |
| **1.4.10 Reflow** | No horizontal scroll at 320px. Grids wrap, tables overflow-x auto. |
| **1.4.12 Text Spacing** | Line height 1.5 for body, no fixed heights that clip text. |
| **2.1.1 Keyboard** | All interactive elements focusable. `:focus-visible` ring on everything. |
| **2.4.7 Focus Visible** | `outline: 2px solid #8B2A3D` on all `:focus-visible`. |
| **2.5.5 Target Size** | All touch targets ≥44px (social icons, buttons, nav items). |
| **2.5.8 Target Size (AA)** | Minimum 24×24px with 4px spacing. |
| **3.3.1 Error Identification** | `aria-invalid`, `aria-describedby`, `role="alert"` on forms. |
| **3.3.2 Labels/Instructions** | Every input has visible label + `aria-label` where icon-only. |
| **4.1.2 Name, Role, Value** | Custom components expose proper ARIA roles. |
| **4.1.3 Status Messages** | Toasts use `role="status"`, errors use `role="alert"`. |

### 12.3 Key Accessibility Patterns

- **Reduced motion:** All animations disabled via `prefers-reduced-motion: reduce`
- **Touch devices:** `hover: none` disables hover-dependent interactions
- **Scrollbar:** Thin custom scrollbar (6px), always visible
- **Selection colour:** `rgba(110,27,45,0.12)` — respects brand
- **Skip links:** Visible on focus for keyboard navigation
- **ARIA labels:** All icon buttons, nav items, and inputs

---

## 13. File Map & Source of Truth

| File | Platform | Purpose |
|------|----------|---------|
| `frontend/src/app/globals.css` | Web (Next.js) | Tailwind v4 theme + design tokens + components |
| `backend/public/sakk-assets/sakk-tokens.css` | Web (landing) | Canonical CSS tokens. Mirror of app_colors.dart |
| `backend/public/sakk-admin/admin.css` | Admin panel | Admin component library (2344 lines) |
| `mobile/lib/core/theme/app_colors.dart` | Mobile (Flutter) | Colour constants + AppColorsTheme extension |
| `mobile/lib/core/theme/app_theme.dart` | Mobile (Flutter) | Material 3 ThemeData with all component themes |
| `mobile/lib/core/theme/theme_provider.dart` | Mobile (Flutter) | Light-only theme provider |
| `docs/DESIGN.md` | All | **This file** — single source of truth |

### 13.1 Design Tokens Cross-Reference

| Token | CSS var | Flutter | Admin CSS |
|-------|---------|--------|-----------|
| Primary | `--color-primary` | `AppColors.primary` | `--wine` |
| Primary Light | `--color-primary-light` | `AppColors.primaryLight` | `--wine-soft` |
| Primary Dark | `--color-primary-dark` | `AppColors.primaryDark` | `--wine-dark` |
| Accent | `--color-accent` | `AppColors.accent` | `--gold` |
| Background | `--color-background` | `AppColors.background` | `--bg` / `--marble` |
| Surface | `--color-surface` | `AppColors.surface` | `--surface` |
| Text Primary | `--color-text-primary` | `AppColors.textPrimary` | `--ink` |
| Text Secondary | `--color-text-secondary` | `AppColors.textSecondary` | `--ink-2` |
| Success | `--color-success` | `AppColors.success` | `--success` |
| Error | `--color-error` | `AppColors.error` | `--danger` |
| Warning | `--color-warning` | `AppColors.warning` | `--warning` |

> **Rule:** When adding a new colour, add it to ALL four sources simultaneously. The CSS `globals.css` is the authoring source; `app_colors.dart` mirrors it exactly.

---

## 14. Z-Index Scale

| Level | Value | Elements |
|-------|-------|----------|
| Dropdown | 100 | Menus, popovers |
| Sticky | 200 | Sticky headers |
| Navbar | 300 | Topbar, sidebar |
| Overlay | 400 | Backdrops, mobile sidebar overlay |
| Modal | 500 | Dialogs, modals |
| Toast | 300 | Toast notifications |
| Noise overlay | 9999 | SVG noise (decorative) |

---

## 15. Responsive Breakpoints

| Breakpoint | Width | Admin sidebar |
|------------|-------|---------------|
| Mobile | ≤767px | Drawer (hidden, slides in) |
| Tablet | 768–1023px | 240px wide |
| Desktop | 1024–1279px | 272px wide |
| Wide | ≥1280px | 300px wide, max 1440px container |

Admin main content padding:
- Desktop: 1.5rem
- Tablet: 1rem
- Mobile (≤480px): 1rem

---

*End of SAKK Design System. الهوية البصرية لصكك — محفظتك الرقمية السورية.*
