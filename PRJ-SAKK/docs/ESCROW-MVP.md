# SAKK Escrow — نظام الضمان

> **MVP Design** · Post-Gate-3 Feature · Not blocking current release
> Priority: High (fintech trust gap) · Target: Gate 4 (Build)

---

## 1. Problem Statement — لماذا الضمان؟

SAKK handles P2P transfers, payment requests, gold, savings, and payroll. But it has **no mechanism to hold funds in trust** between two parties. Every transfer is irreversible once sent.

**Critical gaps in current model:**

| Scenario | Without Escrow | With Escrow |
|----------|---------------|-------------|
| **Real estate deposit** | Buyer sends ₤50M → seller ghosts → no recourse | Funds held by SAKK → released only on mutual confirmation |
| **Freelance services** | Client pays upfront → freelancer disappears | Client deposits → freelancer delivers → both confirm → release |
| **Service marketplace** | Service completed → client refuses to pay | Receiver confirms delivery → funds auto-release |
| **Installment purchase** | Seller needs guarantee buyer will complete payments | Escrow as staged release per milestone |
| **Cross-border (agent)** | Sender can't verify goods received before release | Agent confirms on behalf of sender |

**Market context:** Syria's economy runs on trust-based transactions (real estate, gold, freelance). SAKK already has the user base and wallet infrastructure. Escrow is the missing institutional trust layer.

---

## 2. MVP Scope — نطاق الإصدار الأول

### Core States

```
┌──────────┐    ┌──────────┐    ┌──────────┐    ┌───────────┐
│ PENDING  │───→│ ACTIVE   │───→│ RELEASED │    │ COMPLETED │
│(deposit) │    │(accepted)│    │(released)│    │ (done)    │
└──────────┘    └──────────┘    └──────────┘    └───────────┘
                      │                │
                      ▼                ▼
                ┌──────────┐    ┌───────────┐
                │ CANCELLED│    │ DISPUTED  │
                │(sender)  │    │(admin)    │
                └──────────┘    └─────┬─────┘
                                      │
                                      ▼
                                ┌───────────┐
                                │ RESOLVED  │
                                │(admin     │
                                │  rules)   │
                                └───────────┘
```

### MVP Actions

| Action | Who | When | Effect |
|--------|-----|------|--------|
| Create | Sender | Init | Funds debited from sender wallet → held in platform escrow wallet |
| Accept | Receiver | After create | Acknowledges terms, starts service window |
| Release | Receiver | After service | Triggers fund transfer to receiver wallet |
| Confirm | Sender | After release | Optional: sender confirms receipt (auto-release if no dispute in N days) |
| Cancel | Sender | Before receiver confirms | Funds returned to sender (minus fee) |
| Dispute | Either | Anytime after accept | Freezes funds → admin reviews → rules |

### Fee Model (MVP)

| Action | Fee | Notes |
|--------|-----|-------|
| Create escrow | 0.5% (min $0.50) | Platform revenue |
| Cancel before accept | Free | No fee refund (no cost) |
| Cancel after accept | 1.0% | Covers processing |
| Dispute resolution | 2.0% (loser pays) | Incentive alignment |

---

## 3. User Flow — تدفق المستخدم

### Happy path (Arabic)

```
1. المرسل (Sender)
   ────────────────
   → يفتح "إنشاء ضمان" في التطبيق
   → يختار المستلم (من قائمة جهات الاتصال/البحث)
   → يدخل المبلغ والعملة (USD/SYP)
   → يضيف وصف الخدمة/الاتفاق (اختياري: تاريخ التسليم)
   → يوافق على الرسوم (0.5%)
   → يؤكد بعملية تحقق (PIN/biometric)
   → يتم خصم المبلغ من المحفظة ↑ تحويل إلى محفظة الضمان
   → يصل إشعار للمستلم

2. المستلم (Receiver)
   ──────────────────
   → يصله إشعار "طلب ضمان جديد من [المرسل]"
   → يعرض التفاصيل: المبلغ، الوصف، الرسوم
   → خياران: قبول / رفض
   → عند القبول: يتم تجميد المبلغ في محفظة الضمان
   → يبدأ تنفيذ الخدمة

3. التسليم والتحرير (Release)
   ──────────────────────────
   → المستلم يضغط "تم التسليم" ← يرسل إشعار للمرسل
   → المرسل يراجع ← يضغط "تأكيد الاستلام"
   → يتم تحرير المبلغ تلقائياً إلى محفظة المستلم
   → رسالة تأكيد للطرفين

4. النزاع (Dispute) — المسار البديل
   ──────────────────────────────────
   → أحد الطرفين يضغط "فتح نزاع"
   → يكتب شرح النزاع (نص + صور اختيارية)
   → يتم تجميد المبلغ
   → إشعار للإدارة (admin notification)
   → المشرف يراجع طلبات الطرفين ← يتخذ قرار
   → يصرف المبلغ حسب قرار الإدارة
```

### Flow Diagram (simplified)

```
Sender                    SAKK                   Receiver
  │                        │                        │
  ├─ Create Escrow ───────→│                        │
  │                        ├─ Notify ──────────────→│
  │                        │                        ├─ Accept
  │                        │◄───────────────────────┤
  │◄─── Accepted ─────────┤                        │
  │                        │                        ├─ Deliver service
  │◄─── Delivery notice ──┤◄───────────────────────┤
  ├─ Confirm ─────────────→│                        │
  │                        ├─ Release funds ───────→│
  │◄─── Complete ─────────┤◄───────────────────────┤
```

---

## 4. Data Model — نموذج البيانات

### 4.1 `escrows` — جدول الضمانات

| Column | Type | Default | Nullable | Notes |
|--------|------|---------|----------|-------|
| id | bigint, auto-increment | | No | Primary key |
| uuid | uuid | | No | Public identifier |
| reference | varchar(50) | | No | Unique, format: ESC-XXXXXXXXXXXX |
| sender_id | bigint | | No | FK → users.id (creator) |
| receiver_id | bigint | | No | FK → users.id |
| amount | decimal(18,8) | | No | Escrow amount |
| currency | varchar(10) | | No | USD or SYP |
| fee | decimal(18,8) | 0.00000000 | No | Platform fee |
| fee_covered_by | varchar(20) | 'sender' | No | sender/receiver/split |
| description | text | | Yes | Service/agreement description |
| status | varchar(30) | 'pending' | No | pending/active/released/cancelled/disputed/resolved |
| sender_wallet_id | bigint | | No | FK → wallets.id (source of funds) |
| receiver_wallet_id | bigint | | Yes | FK → wallets.id (destination) |
| platform_wallet_id | bigint | | No | FK → wallets.id (holding wallet) |
| accepted_at | datetime | | Yes | When receiver accepted |
| released_at | datetime | | Yes | When funds released |
| cancelled_at | datetime | | Yes | When cancelled |
| cancelled_by | bigint | | Yes | FK → users.id |
| dispute_deadline | datetime | | Yes | Auto-release if no dispute by this date |
| admin_id | bigint | | Yes | FK → users.id (resolving admin) |
| admin_note | text | | Yes | Resolution reason |
| resolved_at | datetime | | Yes | |
| deleted_at | datetime | | Yes | Soft delete |
| created_at | datetime | | Yes | |
| updated_at | datetime | | Yes | |

**Indexes:** PK (id), UNIQUE (uuid), UNIQUE (reference), INDEX (sender_id), INDEX (receiver_id), INDEX (status), INDEX (sender_id, status)

**New migration entries (add to migration list):**

| # | Migration | Purpose |
|---|-----------|---------|
| N+1 | `2026_06_29_000001_create_escrows_table` | Core escrow table |
| N+2 | `2026_06_29_000002_create_escrow_messages_table` | Party communication |
| N+3 | `2026_06_29_000003_create_escrow_disputes_table` | Dispute records |

### 4.2 `escrow_messages` — جدول الرسائل

Communication between sender and receiver within an escrow (audit trail).

| Column | Type | Default | Nullable | Notes |
|--------|------|---------|----------|-------|
| id | bigint, auto-increment | | No | |
| escrow_id | bigint | | No | FK → escrows.id |
| sender_id | bigint | | No | FK → users.id |
| message | text | | No | Message content |
| attachment | json | | Yes | File URLs array (max 5) |
| created_at | datetime | | Yes | |

**Indexes:** PK (id), INDEX (escrow_id), INDEX (escrow_id, created_at)

### 4.3 `escrow_disputes` — جدول النزاعات

| Column | Type | Default | Nullable | Notes |
|--------|------|---------|----------|-------|
| id | bigint, auto-increment | | No | |
| escrow_id | bigint | | No | FK → escrows.id (unique) |
| opened_by | bigint | | No | FK → users.id (who disputed) |
| reason | text | | No | Sender's explanation |
| evidence | json | | Yes | Array of file URLs |
| status | varchar(20) | 'open' | No | open/under_review/resolved |
| admin_id | bigint | | Yes | FK → users.id (assigned admin) |
| admin_notes | text | | Yes | Internal admin notes |
| resolution | varchar(20) | | Yes | released_to_receiver / returned_to_sender / split |
| resolver_note | text | | Yes | Public-facing resolution reason |
| resolved_at | datetime | | Yes | |
| created_at | datetime | | Yes | |
| updated_at | datetime | | Yes | |

**Indexes:** PK (id), UNIQUE (escrow_id), INDEX (status), INDEX (admin_id)

### 4.4 Escrow Wallet

A dedicated **platform escrow wallet** holds all in-flight escrow funds (one platform wallet, not per-escrow — track per-escrow balances via `pending_balance` on the platform wallet, or use ledger entries).

**Option A (MVP-simple):** Single platform wallet `type='escrow'`. Each escrow's balance tracked via `escrows.amount`. Reconciliation via `PlatformRevenue`-style ledger.

**Option B (preferred for production):** Each escrow gets a virtual sub-account tracked via `escrows.platform_wallet_id` pointing to a shared escrow wallet + ledger table for balance attribution. MVP uses Option A.

---

## 5. Mobile Screens — Wireframe Descriptions

### 5.1 Escrow Creation Form — إنشاء ضمان

```
┌──────────────────────────────────┐
│  ← إنشاء ضمان                     │  AppBar
├──────────────────────────────────┤
│                                  │
│  [صورة المستلم]                   │  Avatar placeholder
│  إلى: [اختر مستلم]─ →            │  Search/select receiver
│                                  │
│  المبلغ                           │
│  ┌──────────────────────────┐    │
│  │  500.00                  │    │  Amount input (numeric)
│  └──────────────────────────┘    │
│  العملة: [USD ▼]    [SYP]       │  Currency toggle
│                                  │
│  وصف الخدمة/الاتفاق               │
│  ┌──────────────────────────┐    │
│  │  وصف موجز للخدمة...      │    │  Text area
│  └──────────────────────────┘    │
│                                  │
│  تاريخ التسليم المتوقع            │
│  ┌──────────────────────────┐    │
│  │  2026-07-15              │    │  Date picker
│  └──────────────────────────┘    │
│                                  │
│  ┌──────────────────────────┐    │
│  │  الرسوم: 0.5% = 2.50$   │    │  Fee breakdown
│  │  المحصلة: 502.50$       │    │
│  └──────────────────────────┘    │
│                                  │
│  [──────────────────────────]    │
│  │     إنشاء الضمان          │    │  Primary CTA (burgundy)
│  [──────────────────────────]    │
│                                  │
└──────────────────────────────────┘
```

**States:**
- **Default:** Empty form with validation hints
- **Validation error:** Red border on invalid field + inline error message
- **Submitting:** CTA shows spinner + "جاري الإنشاء..."
- **Success:** Redirect to escrow detail view + success toast
- **Error:** Bottom sheet "فشل الإنشاء" + retry

### 5.2 Escrow Detail View — تفاصيل الضمان

```
┌──────────────────────────────────┐
│  ← #ESC-A1B2C3D4E5F6     ☰      │  Reference + menu
├──────────────────────────────────┤
│                                  │
│  ┌─ Status Bar ──────────────┐   │
│  │  ○ ● ● ● ●               │   │  5-step progress
│  │  تم القبول                   │   │  Current state label
│  │  قيد التنفيذ ...            │   │
│  └──────────────────────────┘   │
│                                  │
│  500.00 USD                      │  Amount (large, tabular)
│  ضمان بين:                        │
│  [A] أحمد → [B] علي             │  Sender ↔ Receiver
│                                  │
│  ┌──────────────────────────┐    │
│  │  وصف: تجهيز أثاث مكتب     │    │  Description
│  │  تاريخ التسليم: 15 يوليو  │    │
│  └──────────────────────────┘    │
│                                  │
│  Timeline                        │
│  ┌──────────────────────────┐    │
│  │ ● 29 يونيو — تم الإنشاء  │    │  Event 1
│  │ ● 29 يونيو — تم القبول   │    │  Event 2
│  │ ○ انتظار التسليم         │    │  Next
│  └──────────────────────────┘    │
│                                  │
│  الرسائل (3)                      │  Message button
│  [──────────────────────────]    │
│  │   إرسال رسالة            │    │
│  [──────────────────────────]    │
│                                  │
│  [───────────]  [───────────]    │
│  │  فتح نزاع │  │  إلغاء   │    │  Conditional actions
│  [───────────]  [───────────]    │
│                                  │
└──────────────────────────────────┘
```

**States:**

| Status | What user sees | Available actions |
|--------|----------------|-------------------|
| `pending` | Pending deposit confirmation | Cancel (sender only) |
| `active` | Progress bar at step 2 | Dispute (either), Message, Cancel (sender, w/ fee) |
| `released` | Progress bar complete, success badge | View receipt, Message |
| `cancelled` | Grey badge "ملغي", timeline stops | View only |
| `disputed` | Red badge "نزاع", suspended spinner | View messages, Await admin |
| `resolved` | Resolution result (green/red badge) | View admin note |

### 5.3 Escrow List — قائمة الضمانات

```
┌──────────────────────────────────┐
│  ← الضمانات                 +    │  FAB to create
├──────────────────────────────────┤
│  [الكل] [نشطة] [مكتملة] [نزاع]   │  Tab bar
├──────────────────────────────────┤
│                                  │
│  ┌──────────────────────────┐    │
│  │ 500.00 USD    ● نشط     │    │  Card
│  │ تجهيز أثاث مكتب          │    │
│  │ أحمد ← علي    29 يونيو  │    │
│  └──────────────────────────┘    │
│                                  │
│  ┌──────────────────────────┐    │
│  │ 1,200.00 USD    ● مكتمل  │    │  Completed (gold check)
│  │ تصميم موقع                │    │
│  │ سارة ← كريم    28 يونيو  │    │
│  └──────────────────────────┘    │
│                                  │
│  ┌──────────────────────────┐    │
│  │ 8,500,000 SYP  ● نزاع   │    │  Dispute (red indicator)
│  │ شقة - عربون              │    │
│  │ خالد ← مها    25 يونيو  │    │
│  └──────────────────────────┘    │
│                                  │
│ موقع: أنت مرسل (5)  مستقبل (3)   │  Role filter chips
│                                  │
└──────────────────────────────────┘
```

**States:**
- **Empty (no escrows):** Illustration + "لا توجد ضمانات بعد" + CTA "إنشاء أول ضمان"
- **Loading:** Skeleton shimmer (3 cards)
- **Error:** "تعذر تحميل القائمة" + refresh button
- **Pagination:** Infinite scroll with `per_page=20`

### 5.4 Notification Types

| Trigger | Receiver | Sender |
|---------|----------|--------|
| Escrow created | "طلب ضمان جديد من [الاسم]" | "تم إنشاء الضمان #REF" |
| Escrow accepted | "تم قبول الضمان" | "قبول الضمان من [الاسم]" |
| Delivery marked | "تم تأكيد التسليم من [الاسم]" | "يرجى تأكيد استلام الخدمة" |
| Released | "تم تحرير المبلغ إلى محفظتك" | "تم تحرير المبلغ إلى [الاسم]" |
| Cancelled | "تم إلغاء الضمان" | "تم إلغاء الضمان" |
| Dispute opened | "تم فتح نزاع على الضمان #REF" | "تم فتح نزاع على الضمان #REF" |
| Dispute resolved | "تم البت في النزاع" | "تم البت في النزاع" |

---

## 6. Backend API Endpoints — نقاط النهاية

Base path: `/api/v1/escrows` (authenticated, Sanctum token)

### 6.1 Endpoint Table

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/escrows` | Sender | Create new escrow |
| GET | `/escrows` | Any | List user's escrows |
| GET | `/escrows/{uuid}` | Any | Get escrow details |
| POST | `/escrows/{uuid}/accept` | Receiver | Accept escrow |
| POST | `/escrows/{uuid}/release` | Receiver | Mark delivery as complete |
| POST | `/escrows/{uuid}/confirm` | Sender | Confirm receipt (release funds) |
| POST | `/escrows/{uuid}/cancel` | Sender | Cancel escrow |
| POST | `/escrows/{uuid}/dispute` | Either | Open dispute |
| GET | `/escrows/{uuid}/messages` | Either | List messages |
| POST | `/escrows/{uuid}/messages` | Either | Send message |

### 6.2 Request/Response Examples

**POST /api/v1/escrows** — Create escrow
```json
{
    "receiver_id": 2,
    "currency": "USD",
    "amount": 500.00,
    "description": "تجهيز أثاث مكتب - كرسي تنفيذي + طاولة",
    "delivery_deadline": "2026-07-15",
    "pin": "123456"
}
```
**Response** (201):
```json
{
    "success": true,
    "data": {
        "uuid": "550e8400-e29b-41d4-a716-446655440000",
        "reference": "ESC-A1B2C3D4E5F6",
        "amount": 500.00,
        "currency": "USD",
        "fee": 2.50,
        "status": "pending",
        "sender": { "id": 1, "full_name": "أحمد السوري" },
        "receiver": { "id": 2, "full_name": "علي الدمشقي" },
        "description": "تجهيز أثاث مكتب - كرسي تنفيذي + طاولة",
        "delivery_deadline": "2026-07-15T00:00:00+03:00",
        "created_at": "2026-06-29T10:30:00+03:00"
    },
    "message": "تم إنشاء الضمان بنجاح"
}
```

**GET /api/v1/escrows** — List escrows
```json
{
    "success": true,
    "data": [
        {
            "uuid": "550e8400-...",
            "reference": "ESC-A1B2C3D4E5F6",
            "amount": 500.00,
            "currency": "USD",
            "status": "active",
            "sender": { "id": 1, "full_name": "أحمد السوري" },
            "receiver": { "id": 2, "full_name": "علي الدمشقي" },
            "description": "تجهيز أثاث مكتب",
            "created_at": "2026-06-29T10:30:00+03:00"
        }
    ],
    "meta": {
        "current_page": 1,
        "last_page": 1,
        "per_page": 20,
        "total": 1
    }
}
```

**POST /api/v1/escrows/{uuid}/release** — Mark delivery complete (receiver)
```json
{
    "note": "تم تسليم الأثاث بالكامل"
}
```
**Response**:
```json
{
    "success": true,
    "data": {
        "uuid": "550e8400-...",
        "status": "awaiting_confirmation"
    },
    "message": "تم تأكيد التسليم. بانتظار تأكيد المستلم"
}
```

**POST /api/v1/escrows/{uuid}/confirm** — Confirm receipt + release funds (sender)
```json
{
    "pin": "123456"
}
```
**Response**:
```json
{
    "success": true,
    "data": {
        "uuid": "550e8400-...",
        "status": "released",
        "released_at": "2026-06-30T14:00:00+03:00"
    },
    "message": "تم تحرير المبلغ إلى محفظة المستلم"
}
```

**POST /api/v1/escrows/{uuid}/dispute** — Open dispute
```json
{
    "reason": "الخدمة لم تكتمل حسب الاتفاق",
    "evidence": ["https://api.sakk.app/storage/evidence-1.jpg"]
}
```
**Response**:
```json
{
    "success": true,
    "data": {
        "uuid": "550e8400-...",
        "status": "disputed",
        "dispute": {
            "opened_by": 1,
            "reason": "الخدمة لم تكتمل حسب الاتفاق",
            "status": "open"
        }
    },
    "message": "تم فتح نزاع. سيتم مراجعة الطلب من قبل الإدارة"
}
```

### 6.3 Admin Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/admin/escrows` | List all escrows (filter by status) |
| GET | `/api/v1/admin/escrows/{uuid}` | Full escrow detail with disputes |
| POST | `/api/v1/admin/escrows/{uuid}/resolve` | Resolve dispute (release/return/split) |
| POST | `/api/v1/admin/escrows/{uuid}/note` | Add internal admin note |

---

## 7. Backend Implementation Notes

### 7.1 Key Services

| Service | Responsibility |
|---------|----------------|
| `EscrowService` | Core business logic: create, accept, release, cancel |
| `EscrowDisputeService` | Dispute creation, admin resolution, evidence handling |
| `EscrowWalletService` | Hold/release funds via platform escrow wallet |

### 7.2 Concurrency & Safety

- **Pessimistic locking** (`lockForUpdate`) on all fund-moving operations (create, release, cancel, resolve)
- **Idempotency keys** on create + release + cancel (prevent double-spend on retry)
- **Status machine** enforced at DB level via check constraint or service gate (no direct status bypass)
- **2FA/PIN required** for create, release, confirm, cancel (per existing `VerifiesTransactionAuth` pattern)
- **Audit trail** via existing `activity_logs` table for all escrow state transitions

### 7.3 New Transaction Types

| Type | Event |
|------|-------|
| `escrow_hold` | Funds debited from sender → moved to platform escrow wallet |
| `escrow_release` | Funds released to receiver wallet |
| `escrow_refund` | Funds returned to sender on cancel |
| `escrow_fee` | Platform fee collected on release |
| `escrow_split` | Partial distribution on dispute resolution |

---

## 8. Priority & Timeline

| Phase | Items | Timeline | Dependencies |
|-------|-------|----------|--------------|
| **Phase 0** | Data model + migration + model + factory | Day 1 | None |
| **Phase 1** | EscrowService core (create, accept) | Day 2-3 | Phase 0 |
| **Phase 2** | Release + confirm + cancel flows | Day 4-5 | Phase 1 |
| **Phase 3** | Dispute + admin resolution | Day 6-7 | Phase 2 |
| **Phase 4** | API endpoints + validation + tests | Day 8-10 | Phase 2-3 |
| **Phase 5** | Mobile screens (Flutter) | Day 11-15 | Phase 4 |
| **Phase 6** | Notification wiring + edge cases | Day 16-17 | Phase 5 |

**Not MVP (Post-launch):**
- Auto-release timer (dispute deadline → auto-release on no-action)
- Escrow templates (reusable terms)
- Milestone-based staged release
- Smart contract integration
- Bulk escrow (payroll for freelancers)
- Arbitration panel (multi-admin dispute review)

---

## 9. Integration Points with Existing Features

| Feature | Integration |
|---------|-------------|
| **Wallets** | Escrow platform wallet (new `type` column or dedicated wallet) |
| **Transactions** | New txn types for hold/release/refund/fee |
| **Payment Requests** | Similar two-party flow; escrow adds the "hold" state payment requests lack |
| **Notifications** | All state transitions trigger push + in-app notification |
| **Admin Panel** | New `إدارة الضمانات` section: list, detail, dispute resolution |
| **Activity Logs** | Every state transition logged |
| **Payroll** | Escrow reuse for company→freelancer payments (post-MVP) |
| **Gold/Savings** | Not in scope — escrow holds fiat only in MVP |

---

> **Document Status:** Draft v1  
> **Author:** Chief Product Strategist (Dr. Amara Okafor) + UI/UX Designer (Daniel Kim)  
> **Date:** 2026-06-29  
> **Gate:** 3 (Architecture) — Ready for Gate 4 (Build) sprint planning
