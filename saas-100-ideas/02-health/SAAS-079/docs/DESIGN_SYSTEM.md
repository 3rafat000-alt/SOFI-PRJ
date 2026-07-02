# Design System — PharmacyRx
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** PharmacyRx — صيدليتي
- **Logo concept:** Mortar + pestle + Rx cross icon in teal
- **Brand personality:** Caring, Professional, Clean, Reliable, Modern

## Color Palette
- **Primary:** `#0F766E` — Teal (headers, buttons, main identity)
- **Secondary:** `#14B8A6` — Light Teal (accents, highlights, secondary elements)
- **White:** `#FFFFFF` — White (background, cards — clean medical look)
- **Neutral:** `#F0FDFA` — Soft mint (bg), `#1F2937` — dark (text), `#6B7280` — grey (labels)
- **Semantic:** Success `#10B981` · Warning `#F59E0B` · Error `#EF4444` · Info `#3B82F6`

## Typography
- **Headings:** Noto Sans Arabic (clean medical) — sizes: 26/22/18/16px
- **Body:** Noto Sans Arabic — 14px (patient app), 13px (pharmacy dashboard)
- **Arabic:** Noto Sans Arabic — clear legible Arabic
- **Medicine names:** 16px semibold (drug names prominent)

## Spacing
- Base unit: 4px
- Padding: 16/24/32px
- Border radius: 12px (cards), 8px (buttons), 6px (inputs)
- Max content width: 1100px

## Iconography
- Style: Outline, 24px
- Library: Lucide Icons (pill, syringe, camera, heart, phone, video)

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg-teal-700 #0F766E, white text, 8px radius | hover: #115E59 / active / disabled |
| Input Field | border #D1D5DB, 6px radius, 14px | focus: ring teal-500, error: ring red |
| MedicineCard | White bg, shadow-sm, 12px radius | available: green dot / OOS: red dot |
| OCRResult | Confidence badge (high/medium/low) | verified/needs-review/failed |
| ConsultationRoom | Video: full-screen, chat: side panel | waiting/active/ended |
| ReminderCard | Medicine icon + time + dosage | taken/missed/upcoming |
