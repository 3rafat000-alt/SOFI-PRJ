# PRD: GoldPrice (SAAS-065)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner**: منصة متابعة أسعار الذهب والعملات — تنبيهات لحظية، تحليلات فنية، حاسبة استثمار، ومحفظة افتراضية.
- **Problem statement**: تجار الذهب والمستثمرون الأفراد في المنطقة العربية يفتقرون إلى أداة موحدة لمتابعة أسعار الذهب والفضة والعملات، مع تحليلات فنية وتنبيهات فورية باللغة العربية.
- **Proposed solution**: Laravel API + React Dashboard + Flutter App — لوحة أسعار لحظية، رسوم بيانية تفاعلية، تنبيهات مخصصة، حاسبة زكاة/استثمار.

## 2. Market & Opportunity
- **Target market size**: سوق تطبيقات الاستثمار والذهب ~$3B عالمياً، الشرق الأوسط ~$200M (الذهب ثقافة استثمارية في الخليج).
- **Customer segment**: B2C — تجار ذهب، مستثمرون أفراد، متداولو عملات. B2B — محلات ذهب، بنوك تقدم خدمات استثمار.
- **Competitor landscape**:
  1. **Gold Price Live**: تطبيق عالمي بدون دعم عربي أو تنبيهات مصرية/سعودية.
  2. **Kitco**: موقع أمريكي، بيانات ممتازة لكن بدون تطبيق جوال عربي.
  3. **Xe.com**: عملات فقط، بدون ذهب أو تحليلات.
  4. **تداول**: منصة سعودية للأسهم، لا تغطي الذهب.
  5. **Gold IQ**: تطبيق عربي محدود، بدون تحليلات فنية.
- **Differentiation**: عربي كامل (اللهجات المحلية)، أسعار لحظية من 5 مصادر، تنبيهات ذكية (سعر/نسبة تغير/مؤشر فني)، حاسبة استثمار + زكاة، محفظة افتراضية.

## 3. User Personas

### Primary: أبو فيصل — تاجر ذهب (سوق الذهب)
- **الدور**: يشتري ويبيع ذهب بالجملة والتجزئة، يحتاج متابعة الأسعار لحظياً.
- **الأهداف**: معرفة سعر الشراء والبيع، تنبيه عند وصول سعر معين، تحليل اتجاهات السوق.
- **نقاط الألم**: الأسعار تتغير بسرعة، يعتمد على واتساب الجماعة، يخاف من التقلبات.

### Secondary: سلمى — مستثمرة فردية
- **الدور**: تستثمر في الذهب والفضة كتحوط، تتابع السوق يومياً.
- **الأهداف**: متابعة محفظتها، حساب الزكاة، تنبيهات لفرص الشراء.
- **نقاط الألم**: لا تفهم التحليلات الفنية المعقدة، تريد أداة بسيطة بعربية.

### Admin: يوسف — محلل مالي
- **الدور**: يقدم استشارات استثمارية، يكتب تقارير عن الذهب.
- **الأهداف**: تحليل فني متقدم، رسوم بيانية، تصدير بيانات للتقارير.
- **نقاط الألم**: الأدوات المتقدمة غالية (TradingView $50/شهر)، لا يوجد تحليل عربي.

## 4. Features by Platform

### Laravel API (Backend)
- Core domain models: User, Metal, Currency, PriceTick, Alert, Portfolio, Transaction, ChartIndicator, NewsArticle
- RESTful endpoints: CRUD for all models
- Auth: Sanctum + social login
- Price aggregation: fetch from 5+ sources (GoldAPI, XE, local exchanges), deduplication, average calculation
- WebSocket streaming: Laravel Reverb for real-time price ticks
- Alert engine: condition matching (price above/below, % change, technical indicator cross)
- Portfolio tracking: buy/sell transactions, P&L calculation, allocation
- Gold calculator: purity conversion (24k/22k/21k/18k), weight units (gram/oz/tola), currency conversion
- Zakat calculator: gold/silver nisab threshold, due amount
- Historical data: daily OHLCV storage for charting
- News aggregation: RSS feeds from financial news sources

### React Dashboard (Web)
- Live price board: gold (all purities), silver, platinum, major currency pairs
- Interactive charts: candlestick, line, area — timeframes (1m, 5m, 15m, 1h, 4h, 1d, 1w, 1M)
- Technical indicators: RSI, MACD, Moving Averages (SMA/EMA), Bollinger Bands
- Alerts manager: create price/indicator alerts
- Portfolio dashboard: holdings, current value, P&L, allocation pie chart
- Calculator: gold price by weight/purity, currency converter, zakat
- News feed: filtered by gold/currency tags
- Admin panel: manage price sources, users, exchange rates

### Flutter App (Mobile)
- Live prices: scrolling ticker, tap for detail chart
- Push alerts: price target reached, unusual volatility, news impact
- Quick calculator: grams → SAR/EGP/AED, purity conversion
- Portfolio: add transaction, view holdings, real-time P&L
- Widget support: iOS home screen widget, Android widget
- Dark theme: traders prefer dark mode for charts
- Offline: last prices cached, alerts evaluated on server

## 5. Data Model (MVP)

| Entity | Fields | Relationships |
|---|---|---|
| User | id, name, email, preferences, trading_timezone | hasMany Alert, Portfolio |
| Metal | id, code (XAU/XAG/XPT), name_ar, name_en, unit (gram/oz/tola) | hasMany PriceTick |
| PriceTick | id, metal_id, currency, bid, ask, high, low, source, timestamp | belongsTo Metal |
| Currency | id, code (USD/SAR/EGP/AED), name_ar, rate_to_usd, updated_at | — |
| Alert | id, user_id, metal_id, condition (above/below/cross), value, active, triggered_at | belongsTo User/Metal |
| Portfolio | id, user_id, name, type (real/virtual) | belongsTo User, hasMany Transaction |
| Transaction | id, portfolio_id, metal_id, type (buy/sell), quantity_grams, price_per_gram, total, fees, date | belongsTo Portfolio/Metal |
| NewsArticle | id, title_ar, title_en, source, url, summary, published_at, tags | — |

## 6. API Endpoints (MVP)

| Method | Endpoint | Description |
|---|---|---|
| GET | /api/prices/live | Current live prices (all metals, all currencies) |
| GET | /api/prices/history?metal=XAU&from=...&to=... | Historical OHLCV data |
| GET | /api/prices/stream | WebSocket endpoint for real-time ticks |
| POST | /api/alerts | Create price alert |
| GET | /api/alerts | List user alerts |
| DELETE | /api/alerts/{id} | Delete alert |
| GET | /api/portfolio | User portfolio holdings |
| POST | /api/portfolio/transactions | Add transaction |
| GET | /api/calculator/convert?weight=...&from=gram&to=oz&purity=24 | Price conversion |
| GET | /api/calculator/zakat | Zakat calculation (gold + cash) |

## 7. User Interface (Screen List)

### Dashboard screens (React)
- Login/Register
- Live prices page: grid of metals with bid/ask, change %
- Chart page: interactive chart, timeframe selector, indicator toggle
- Alerts page: create, list, edit, enable/disable alerts
- Portfolio page: holdings, transactions, P&L
- Calculator: tabs (converter, purity, zakat, currency)
- News page: filtered feed
- Settings: preferences, notifications, currency default

### Mobile screens (Flutter)
- Home: price ticker (scrollable), favorite metals, quick calculator
- Detail: metal detailed chart, alerts button
- Alerts: add alert (condition + value + method push/email)
- Portfolio: list → detail → add transaction
- Calculator: simple input → instant result
- News: headline list → article view
- Settings: dark/light theme, currency, notifications

### Screen flow (text)
```
Home → Live Prices → Tap Metal → Chart + Indicators
        ├── Alerts → Create Alert → Set Condition → Save
        ├── Portfolio → Holdings → Add Transaction → P&L
        ├── Calculator → Weight/Purity Conversion → Zakat → Currency
        └── News → Filter → Read Article
```

## 8. Business Model
- **Free**: $0 — delayed prices (15 min), 3 alerts, basic charts
- **Premium**: $7/month — real-time prices, unlimited alerts, technical indicators, portfolio tracking
- **Trader**: $15/month — API access, advanced indicators, multi-portfolio, priority support
- **Free trial**: 7-day Premium trial for new users

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2)**: Laravel API — Price aggregation, Metal/Currency CRUD, WebSocket stream
- **Phase 2 (Weeks 3-4)**: React Dashboard — Live prices, Charts, Alerts manager
- **Phase 3 (Weeks 5-6)**: Flutter App — Live prices, Alerts, Portfolio, Calculator
- **Phase 4 (Weeks 7-8)**: Portfolio engine, Zakat calc, News integration, Testing, Deploy

## 10. Risk & Mitigation
- **Technical**: Price source API reliability — strategy: multi-source fallback, caching layer, health checks.
- **Technical**: WebSocket scale — strategy: Laravel Reverb with Redis, horizontal scaling.
- **Market**: Free alternatives (Kitco, XE) — strategy: Arabic language as moat, local gold market data (souq prices).
- **Regulatory**: Financial data compliance — strategy: disclaimers, no trading execution, data with delay option.
