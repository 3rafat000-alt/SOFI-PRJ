# PROTOTYPE SPEC — GoldPrice (SAAS-065)
> Owner: UI/UX Designer · Gate 2

## Screen: Live Price Board (maps to Journey Stage: عرض الأسعار)
- **Layout:** Horizontal scrolling ticker top, below grid of metal cards (XAU/XAG/XPT/PA), each showing bid/ask/change%/chart sparkline
- **Components:** TickerTape, MetalCard, SparklineChart, CurrencySelector, ChangeBadge (green/red)
- **States:** Empty (no data — offline) | Loading (skeleton cards) | Error (source API down — show stale + warning) | Edge (market closed — show last price)
- **Key Interaction:** Tap metal card → opens chart detail
- **Friction Resolved:** #1 (aggregated from 5 sources)

## Screen: Chart Detail (maps to Journey Stage: الرسم البياني)
- **Layout:** Full-width candlestick chart with timeframe selector bar, below indicators toggle + volume
- **Components:** CandlestickChart, TimeframeBar (1m/5m/15m/1h/4h/1d/1w/1M), IndicatorToggle (RSI/MACD/SMA/EMA/BB), ChartToolbar (drawing tools)
- **States:** Empty (no data) | Loading (chart rendering) | Error (data gap) | Edge (market hours — show active session highlight)
- **Key Interaction:** Touch-drag to see OHLCV values at cursor
- **Friction Resolved:** #5 (professional indicators at affordable price)

## Screen: Alert Creator (maps to Journey Stage: إضافة تنبيه)
- **Layout:** Card form — select metal → condition (above/below/cross) → value → notification method (push/email/whatsapp)
- **Components:** MetalSelect, ConditionSelect, ValueInput, NotificationToggleList, AlertHistoryList
- **States:** Empty (no alerts) | Loading (saving) | Error (invalid value) | Edge (max free alerts reached — upsell)
- **Key Interaction:** Toggle alert on/off from list without editing
- **Friction Resolved:** #2 (instant price alerts)

## Screen: Portfolio (maps to Journey Stage: المحفظة)
- **Layout:** Top card (total value, P&L, allocation pie), below transaction list + add transaction FAB
- **Components:** PortfolioSummaryCard, PnLBadge, AllocationPieChart, TransactionList, AddTransactionForm
- **States:** Empty (no transactions — "add your first" CTA) | Loading | Error | Edge (currency mismatch alert)
- **Key Interaction:** Swipe transaction to edit/delete
- **Friction Resolved:** #4 (portfolio tracking simplified)

## Screen: Calculator (maps to Journey Stage: الحاسبة)
- **Layout:** Tab bar (Weight Conversion / Purity / Zakat / Currency), large input field, instant result display
- **Components:** CalculatorTabBar, NumericInput, UnitSelect (gram/oz/tola), PuritySlider (24k/22k/21k/18k), CurrencyFlag, ResultDisplay
- **States:** Empty (default state) | Loading (fetching rate) | Error (conversion failed) | Edge (nisab threshold not met — zakat = 0)
- **Key Interaction:** Type value → instant result updates (no button needed)
- **Friction Resolved:** #3 (zakat calculation made simple)

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| Button | Primary #D97706, Secondary #1E3A5F, Ghost | hover/active/disabled/loading | 44px |
| MetalCard | Gold/Silver/Platinum/Palladium | loading/ready/error/offline | Sparkline + price change |
| CandlestickChart | All timeframes | loading/ready/error | Touch to crosshair |
| TickerTape | Horizontal scroll, metal list | loading/ready/animated | Auto-scroll, pause on hover |
| ChangeBadge | Positive/Negative/Neutral | — | Green #059669 / Red #DC2626 |
| AlertToggle | Switch | on/off/loading | Push notification preview |
| CalculatorTab | Weight/Purity/Zakat/Currency | active/inactive | Instant conversion |
| PortfolioCard | Summary with chart | loading/ready/empty | Pull-to-refresh for latest prices |
| PnLBadge | Profit/Loss/Break-even | — | + green / - red / 0 gray |
