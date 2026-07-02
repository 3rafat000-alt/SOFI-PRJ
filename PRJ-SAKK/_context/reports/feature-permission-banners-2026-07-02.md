# Feature Report: طلب الاذن للاشعارات والموقع في لوحة التحكم
> PRJ-SAKK · 2026-07-02 · commit `24ba09f`

## Summary
Added browser-native permission-request banners for **Notifications** (Notification API) and **Geolocation** (Geolocation API) in the admin control panel layout. Non-intrusive, dismissible, 7-day localStorage persistence.

## What was built
| Component | Type | Behavior |
|-----------|------|----------|
| `permissionPrompt` | Alpine.js data component | Checks `Notification.permission`, `navigator.permissions.query({name:'geolocation'})`, localStorage for 7-day dismiss expiry |
| Notification banner | x-show banner | Icon + Arabic text "فعل الإشعارات ليصلك التنبيهات حتى خارج لوحة التحكم" + تفعيل/لا الآن buttons |
| Geolocation banner | x-show banner | Icon + Arabic text "فعل الموقع للاستفادة من الخدمات المكانية" + تفعيل/لا الآن buttons |

## Files changed
- `backend/resources/views/layouts/admin.blade.php` (+115 lines, -9)

## Design decisions
- **No backend changes** — purely frontend, no model/controller/migration needed
- **SAKK design tokens** — all colors use existing `--surface`, `--border-light`, `--gold`, `--accent-soft`, `--text-primary`, `--radius-lg` vars
- **Existing UI patterns** — `.btn-success`, `.btn-ghost`, `.btn-sm`, `<x-heroicon>`
- **x-cloak + x-transition** — prevents flash before Alpine boots, smooth entrance animation
- **7-day localStorage expiry** — dismissed banners stay hidden for 7 days via timestamp comparison

## Verification
- `php -l` — ✅ No syntax errors
- `php artisan view:cache` — ✅ Compiled successfully
- `php artisan test --filter=Admin` — ✅ 289 passed (822 assertions), zero regressions
- Security re-scan — original 2🔴 findings DISMISSED as safe (e()-escaped values)

## Known limitations (deferred)
- Settings toggle (enable/disable banners globally) — not implemented, frontend only
- No server-side tracking of which admins have granted permissions
