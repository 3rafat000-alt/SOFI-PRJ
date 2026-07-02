# Design System — LoyaltyBox
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** LoyaltyBox — صندوق الولاء
- **Logo concept:** صندوق هدايا وردي مع نجمة ذهبية تخرج من الأعلى
- **Brand personality:** دافئ، مبتهج، كريم، بسيط، مرح

## Color Palette
- **Primary:** `#E65100` — برتقالي دافئ (أزرار، شريط علوي، شعار)
- **Secondary:** `#AD1457` — زهري غامق (بطاقات، أيقونات، عروض)
- **Accent:** `#2E7D32` — أخضر (مكافآت، نجاح، نقاط مكتسبة)
- **Neutral:** `#FFF3E0` (خلفيات) `#78909C` (نص ثانوي) `#37474F` (نص أساسي)
- **Semantic:** Points Earned `#2E7D32` · Points Burned `#E65100` · Expiring `#F9A825` · Expired `#BDBDBD`
- **Tiers:** Bronze `#795548` · Silver `#9E9E9E` · Gold `#F9A825` · Platinum `#E0E0E0`

## Typography
- **Headings:** Inter — sizes: 24/20/18/16px (700 weight)
- **Body:** Inter — 14px (400 weight)
- **Arabic:** Noto Sans Arabic — points display, reward names
- **Numbers:** bold 20px for points balance

## Spacing
- Base unit: 8px
- Padding: 16/24/32px
- Border radius: 16px (cards), 8px (buttons), 24px (pill badges)
- Points display: generous whitespace

## Iconography
- Style: Filled (rewards), Outline (UI)
- Library: Lucide
- Key icons: Gift, Star, Wallet, QrCode, Award, TrendingUp

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| LoyaltyCard | gradient bg per merchant, rounded-2xl, 16px padding | hover: scale(1.02), selected: border-2 border-secondary |
| PointsCounter | large number with animated increment | increment: green flash, decrement: orange flash |
| ProgressBar | rounded bar, gradient fill | 0-100% width, emoji at checkpoint (🎁 at 100%) |
| TierBadge | icon + tier name, colored | bronze/silver/gold/platinum distinct colors |
| QRButton | large circle, white bg, shadow | idle: pulse gently, scanning: rotate, success: checkmark |
| RewardCard | image top, points cost bottom | available: colored, claimed: gray overlay with ✓ |
| SetupStep | wizard step with field inputs | completed: check, active: current number, pending: gray |
