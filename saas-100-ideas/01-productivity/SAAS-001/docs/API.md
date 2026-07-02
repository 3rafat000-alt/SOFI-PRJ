# API Specification — TaskSync Pro (SAAS-001)

> **Gate:** 3 · **Owner:** API & Integration Specialist (Marco Blackwood) · **Status:** Locked for Gate 4 Build
> **Consumes:** ARCHITECTURE.md, PRD.md, PERSONAS.md, JOURNEY_MAP.md
> **Produces:** This document + `openapi.yaml`
> **TKT:** TKT-008

---

## 1. OpenAPI Overview

| Property | Value |
|---|---|
| **Base URL** | `https://api.tasksyncpro.com/api/v1` (prod) / `http://localhost:8000/api/v1` (dev) |
| **Auth** | Bearer token (Laravel Sanctum) |
| **Content-Type** | `application/json` (request/response) |
| **Accept** | `application/json` |
| **Rate Limiting** | 60 req/min (auth routes), 300 req/min (general), 30 req/min (reports) |
| **Pagination** | Cursor-based. Default 15, max 100 per page. |
| **Locale** | `Accept-Language: ar|en` — Arabic RTL support |
| **Timezone** | `X-Timezone: Asia/Riyadh` header — overrides user timezone |
| **Idempotency** | `Idempotency-Key` header on POST/PUT — 24h window, 409 on conflict |

### 1.1 Standard Envelope

```json
{
  "data": { ... },
  "meta": {
    "request_id": "req_abc123",
    "timestamp": "2026-06-25T10:30:00Z"
  }
}
```

### 1.2 Error Envelope

```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The given data was invalid.",
    "details": {
      "email": ["The email field is required."],
      "password": ["The password must be at least 8 characters."]
    },
    "meta": {
      "request_id": "req_abc123"
    }
  }
}
```

---

## 2. Authentication Endpoints

### 2.1 Register

```
POST /api/v1/auth/register
Rate limit: 10/min per IP
```

**Request:**
```json
{
  "name": "سارة أحمد",
  "email": "sara@example.com",
  "password": "SecureP@ss123",
  "password_confirmation": "SecureP@ss123",
  "workspace_name": "فريق التسويق",
  "locale": "ar",
  "timezone": "Asia/Riyadh"
}
```

**Response (201):**
```json
{
  "data": {
    "user": {
      "id": "u_01h3xz...",
      "name": "سارة أحمد",
      "email": "sara@example.com",
      "avatar_url": null,
      "locale": "ar",
      "timezone": "Asia/Riyadh",
      "created_at": "2026-06-25T10:30:00Z"
    },
    "workspace": {
      "id": "w_01h3xy...",
      "name": "فريق التسويق",
      "slug": "frq-altswyq",
      "role": "owner",
      "member_count": 1,
      "plan": "free",
      "created_at": "2026-06-25T10:30:00Z"
    },
    "token": "1|abc123def456..."
  },
  "meta": {
    "request_id": "req_abc123",
    "timestamp": "2026-06-25T10:30:00Z"
  }
}
```

### 2.2 Login

```
POST /api/v1/auth/login
Rate limit: 20/min per email
```

**Request:**
```json
{
  "email": "sara@example.com",
  "password": "SecureP@ss123"
}
```

**Response (200):**
```json
{
  "data": {
    "user": { /* User object */ },
    "workspace": { /* Current workspace object */ },
    "workspaces": [ /* All accessible workspaces */ ],
    "token": "2|ghi789jkl012..."
  }
}
```

### 2.3 Logout

```
POST /api/v1/auth/logout
Auth: Bearer token
```

**Response (200):**
```json
{
  "data": {
    "message": "Logged out successfully."
  }
}
```

### 2.4 Forgot Password

```
POST /api/v1/auth/forgot-password
Rate limit: 3/min per email
```

**Request:**
```json
{
  "email": "sara@example.com"
}
```

**Response (200):**
```json
{
  "data": {
    "message": "Password reset link sent to your email."
  }
}
```

### 2.5 Reset Password

```
POST /api/v1/auth/reset-password
```

**Request:**
```json
{
  "token": "reset_token_from_email",
  "email": "sara@example.com",
  "password": "NewSecureP@ss456",
  "password_confirmation": "NewSecureP@ss456"
}
```

**Response (200):**
```json
{
  "data": {
    "message": "Password reset successfully."
  }
}
```

### 2.6 Current User Profile

```
GET /api/v1/auth/me
Auth: Bearer token
```

**Response (200):**
```json
{
  "data": {
    "id": "u_01h3xz...",
    "name": "سارة أحمد",
    "email": "sara@example.com",
    "avatar_url": "https://storage.tasksyncpro.com/avatars/u_01h3xz.jpg",
    "locale": "ar",
    "timezone": "Asia/Riyadh",
    "current_workspace_id": "w_01h3xy...",
    "created_at": "2026-06-25T10:30:00Z",
    "workspaces": [
      {
        "id": "w_01h3xy...",
        "name": "فريق التسويق",
        "slug": "frq-altswyq",
        "role": "owner",
        "plan": "free"
      }
    ]
  }
}
```

---

## 3. Workspace Endpoints

### 3.1 List Workspaces

```
GET /api/v1/workspaces
Auth: Bearer token
```

**Response (200):**
```json
{
  "data": [
    {
      "id": "w_01h3xy...",
      "name": "فريق التسويق",
      "slug": "frq-altswyq",
      "description": null,
      "logo_url": null,
      "role": "owner",
      "member_count": 5,
      "project_count": 3,
      "plan": "pro",
      "created_at": "2026-06-25T10:30:00Z"
    }
  ],
  "meta": {
    "request_id": "req_abc123",
    "timestamp": "2026-06-25T10:30:00Z"
  }
}
```

### 3.2 Create Workspace

```
POST /api/v1/workspaces
Auth: Bearer token
```

**Request:**
```json
{
  "name": "فريق تقني",
  "description": "فريق تطوير البرمجيات",
  "timezone": "Asia/Riyadh"
}
```

**Response (201):**
```json
{
  "data": {
    "id": "w_01h3xy...",
    "name": "فريق تقني",
    "slug": "frq-tqny",
    "description": "فريق تطوير البرمجيات",
    "logo_url": null,
    "role": "owner",
    "member_count": 1,
    "project_count": 0,
    "plan": "free",
    "created_at": "2026-06-25T10:30:00Z"
  }
}
```

### 3.3 Update Workspace

```
PUT /api/v1/workspaces/{id}
Auth: Bearer token
Scope: workspace.owner or workspace.admin
```

**Request:**
```json
{
  "name": "فريق التسويق الرقمي",
  "description": "فريق التسويق الرقمي المحدث"
}
```

**Response (200):** Updated workspace object.

### 3.4 Delete Workspace

```
DELETE /api/v1/workspaces/{id}
Auth: Bearer token
Scope: workspace.owner only
```

**Response (200):**
```json
{
  "data": {
    "message": "Workspace deleted successfully."
  }
}
```

Note: Soft delete. 30-day recovery window. Only owner can delete. Must have at least one other owner or transfer ownership first.

### 3.5 List Members

```
GET /api/v1/workspaces/{id}/members
Auth: Bearer token
Scope: workspace.member
```

**Response (200):**
```json
{
  "data": [
    {
      "id": "u_01h3xz...",
      "name": "سارة أحمد",
      "email": "sara@example.com",
      "avatar_url": null,
      "role": "owner",
      "joined_at": "2026-06-25T10:30:00Z",
      "task_count": 12
    }
  ],
  "meta": {
    "total": 5,
    "per_page": 15
  }
}
```

### 3.6 Invite Member

```
POST /api/v1/workspaces/{id}/invite
Auth: Bearer token
Scope: workspace.owner or workspace.admin
```

**Request:**
```json
{
  "email": "ahmed@example.com",
  "role": "member",
  "message": "انضم لفريقنا في TaskSync!",
  "channel": "email"
}
```

`channel` enum: `email` | `whatsapp`

**Response (201):**
```json
{
  "data": {
    "invitation": {
      "id": "inv_01h3xz...",
      "email": "ahmed@example.com",
      "role": "member",
      "status": "pending",
      "expires_at": "2026-07-25T10:30:00Z",
      "channel": "email",
      "created_at": "2026-06-25T10:30:00Z"
    },
    "message": "Invitation sent successfully."
  }
}
```

---

## 4. Project Endpoints

### 4.1 List Projects

```
GET /api/v1/projects?workspace_id={id}&status=active&page=1&per_page=20
Auth: Bearer token
Scope: workspace.member
```

**Query Parameters:**
| Param | Type | Description |
|---|---|---|
| `workspace_id` | string | Filter by workspace (required) |
| `status` | enum | `active` / `archived` / `all` |
| `search` | string | Search in name/description |
| `page` | int | Page number |
| `per_page` | int | Items per page (max 100) |

**Response (200):**
```json
{
  "data": [
    {
      "id": "p_01h3xz...",
      "workspace_id": "w_01h3xy...",
      "name": "حملة إطلاق المنتج",
      "description": "مهام إطلاق المنتج الجديد",
      "color": "#4F46E5",
      "status": "active",
      "task_count": {
        "total": 24,
        "todo": 8,
        "in_progress": 10,
        "done": 6
      },
      "member_count": 4,
      "start_date": "2026-07-01",
      "end_date": "2026-08-15",
      "created_at": "2026-06-25T10:30:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 20,
    "total": 52
  }
}
```

### 4.2 Create Project

```
POST /api/v1/projects
Auth: Bearer token
Scope: workspace.member
Plan limit: checked against workspace plan
```

**Request:**
```json
{
  "workspace_id": "w_01h3xy...",
  "name": "حملة إطلاق المنتج",
  "description": "مهام إطلاق المنتج الجديد",
  "color": "#4F46E5",
  "start_date": "2026-07-01",
  "end_date": "2026-08-15"
}
```

**Response (201):** Full project object with `task_count: { total: 0, todo: 0, in_progress: 0, done: 0 }`.

### 4.3 Get Project

```
GET /api/v1/projects/{id}
Auth: Bearer token
Scope: project.member
```

**Response (200):**
```json
{
  "data": {
    "id": "p_01h3xz...",
    "workspace_id": "w_01h3xy...",
    "name": "حملة إطلاق المنتج",
    "description": "مهام إطلاق المنتج الجديد",
    "color": "#4F46E5",
    "status": "active",
    "task_count": { "total": 24, "todo": 8, "in_progress": 10, "done": 6 },
    "member_count": 4,
    "start_date": "2026-07-01",
    "end_date": "2026-08-15",
    "members": [ /* Member summary objects */ ],
    "created_at": "2026-06-25T10:30:00Z",
    "updated_at": "2026-06-26T14:00:00Z"
  }
}
```

### 4.4 Update Project

```
PUT /api/v1/projects/{id}
Auth: Bearer token
Scope: project.admin or project.owner
```

**Request:**
```json
{
  "name": "حملة إطلاق v2",
  "end_date": "2026-09-01",
  "color": "#7C3AED"
}
```

**Response (200):** Updated project object.

### 4.5 Delete Project

```
DELETE /api/v1/projects/{id}
Auth: Bearer token
Scope: project.admin or project.owner
```

**Response (200):**
```json
{ "data": { "message": "Project deleted successfully." } }
```

Soft delete. Tasks archived with project.

### 4.6 List Tasks in Project

```
GET /api/v1/projects/{id}/tasks?status=todo&assignee_id={id}&page=1
Auth: Bearer token
Scope: project.member
```

**Response (200):** Paginated task list (see Task endpoints for shape).

---

## 5. Task Endpoints

### 5.1 List Tasks

```
GET /api/v1/tasks?project_id={id}&assignee_id={id}&status=todo,in_progress&priority=high&due_date=2026-07-15&search=مهمة&page=1&per_page=50
Auth: Bearer token
Scope: workspace.member
```

**Query Parameters:**
| Param | Type | Description |
|---|---|---|
| `workspace_id` | string | Required — filter by workspace |
| `project_id` | string | Filter by project |
| `assignee_id` | string | Filter by assignee |
| `status` | comma-separated | `todo`,`in_progress`,`done` |
| `priority` | enum | `urgent`,`high`,`medium`,`low` |
| `due_date_from` | date | Start of due date range |
| `due_date_to` | date | End of due date range |
| `search` | string | Full-text search (Arabic + English) |
| `tags` | comma-separated | Tag IDs |
| `include` | comma-separated | `comments`,`attachments`,`time_entries` |
| `cursor` | string | Cursor-based pagination |
| `limit` | int | Max 100 |

**Response (200):**
```json
{
  "data": [
    {
      "id": "t_01h3xz...",
      "project_id": "p_01h3xz...",
      "project_name": "حملة إطلاق المنتج",
      "title": "تصميم الصفحة الرئيسية",
      "description": "تصميم واجهة المستخدم للصفحة الرئيسية باستخدام Figma",
      "status": "in_progress",
      "priority": "high",
      "position": 3,
      "assignee": {
        "id": "u_01h3xz...",
        "name": "ليلى محمد",
        "avatar_url": null
      },
      "creator": {
        "id": "u_01h3xz...",
        "name": "سارة أحمد"
      },
      "due_date": "2026-07-10",
      "estimated_minutes": 480,
      "logged_minutes": 120,
      "tags": [
        { "id": "tag_01h3xz...", "name": "تصميم", "color": "#EF4444" }
      ],
      "comments_count": 3,
      "attachments_count": 2,
      "is_overdue": false,
      "created_at": "2026-06-25T10:30:00Z",
      "updated_at": "2026-06-26T14:00:00Z"
    }
  ],
  "meta": {
    "next_cursor": "eyJpZCI6InRfMDFoM3l6In0=",
    "has_more": true
  }
}
```

### 5.2 Create Task

```
POST /api/v1/tasks
Auth: Bearer token
Scope: project.member
Plan limit: checked against workspace plan
```

**Request:**
```json
{
  "project_id": "p_01h3xz...",
  "title": "تصميم الصفحة الرئيسية",
  "description": "تصميم واجهة المستخدم للصفحة الرئيسية باستخدام Figma",
  "priority": "high",
  "assignee_id": "u_01h3xz...",
  "due_date": "2026-07-10",
  "estimated_minutes": 480,
  "tags": ["tag_01h3xz..."]
}
```

**Response (201):**
```json
{
  "data": {
    "id": "t_01h3xz...",
    "project_id": "p_01h3xz...",
    "title": "تصميم الصفحة الرئيسية",
    "status": "todo",
    "priority": "high",
    "position": 1,
    "assignee": { /* User summary */ },
    "due_date": "2026-07-10",
    "estimated_minutes": 480,
    "logged_minutes": 0,
    "tags": [ /* Tag objects */ ],
    "comments_count": 0,
    "attachments_count": 0,
    "is_overdue": false,
    "created_at": "2026-06-25T10:30:00Z",
    "updated_at": "2026-06-25T10:30:00Z"
  }
}
```

**WebSocket Event:** `TaskCreated` broadcast to `project.{id}` channel.

### 5.3 Get Task

```
GET /api/v1/tasks/{id}
Auth: Bearer token
Scope: project.member
```

Includes: comments, attachments, time_entries (last 50).

### 5.4 Update Task

```
PUT /api/v1/tasks/{id}
Auth: Bearer token
Scope: project.member
```

**Request:**
```json
{
  "title": "تصميم الصفحة الرئيسية (v2)",
  "status": "done",
  "priority": "medium",
  "assignee_id": "u_01h3xz...",
  "due_date": "2026-07-15",
  "estimated_minutes": 600,
  "description": "تحديث التصميم بناءً على ملاحظات العميل"
}
```

**Response (200):** Updated task object.

**WebSocket Event:** `TaskUpdated` broadcast to `project.{id}` channel. If assignee changed, also `TaskAssigned` event.

### 5.5 Delete Task

```
DELETE /api/v1/tasks/{id}
Auth: Bearer token
Scope: project.admin
```

**Response (200):** `{ "data": { "message": "Task deleted." } }`

Soft delete. **WebSocket Event:** `TaskDeleted` broadcast.

### 5.6 Reorder Tasks (Kanban)

```
PUT /api/v1/tasks/reorder
Auth: Bearer token
Scope: project.member
```

**Request:**
```json
{
  "project_id": "p_01h3xz...",
  "orders": [
    { "id": "t_01h3xz...", "status": "todo", "position": 1 },
    { "id": "t_01h3y7...", "status": "todo", "position": 2 },
    { "id": "t_01h3y8...", "status": "in_progress", "position": 1 },
    { "id": "t_01h3y9...", "status": "done", "position": 1 }
  ]
}
```

**Response (200):**
```json
{
  "data": {
    "message": "Tasks reordered successfully.",
    "reordered_count": 4
  }
}
```

**WebSocket Event:** `TaskMoved` broadcast to `project.{id}` channel.

### 5.7 Quick Status Change

```
PATCH /api/v1/tasks/{id}/status
Auth: Bearer token
Scope: project.member
```

**Request:**
```json
{
  "status": "in_progress"
}
```

**Response (200):** Updated task object (lightweight).

**WebSocket Event:** `TaskUpdated` broadcast.

---

## 6. Time Entry Endpoints

### 6.1 List Time Entries

```
GET /api/v1/time-entries?user_id={id}&task_id={id}&from=2026-07-01&to=2026-07-15&page=1
Auth: Bearer token
```

**Response (200):**
```json
{
  "data": [
    {
      "id": "te_01h3xz...",
      "task_id": "t_01h3xz...",
      "task_title": "تصميم الصفحة الرئيسية",
      "project_name": "حملة إطلاق المنتج",
      "user_id": "u_01h3xz...",
      "user_name": "سارة أحمد",
      "started_at": "2026-07-05T09:00:00Z",
      "ended_at": "2026-07-05T11:30:00Z",
      "duration_minutes": 150,
      "note": "عملت على الهيدر والفوتر",
      "is_manual": false,
      "created_at": "2026-07-05T09:00:00Z"
    }
  ],
  "meta": { "current_page": 1, "last_page": 5, "total": 68 }
}
```

### 6.2 Start Timer

```
POST /api/v1/time-entries/start
Auth: Bearer token
```

**Request:**
```json
{
  "task_id": "t_01h3xz...",
  "note": "بدء العمل على التصميم"
}
```

**Response (201):**
```json
{
  "data": {
    "id": "te_01h3xz...",
    "task_id": "t_01h3xz...",
    "user_id": "u_01h3xz...",
    "started_at": "2026-07-05T09:00:00Z",
    "ended_at": null,
    "duration_minutes": null,
    "is_running": true,
    "note": "بدء العمل على التصميم",
    "is_manual": false
  }
}
```

**WebSocket Event:** `TimerStarted` broadcast to `user.{id}` and `task.{id}` channels.

**Error (409):** Timer already running on another task.

### 6.3 Stop Timer

```
POST /api/v1/time-entries/stop
Auth: Bearer token
```

**Request:**
```json
{
  "note": "انتهيت من التصميم الأساسي"
}
```

**Response (200):**
```json
{
  "data": {
    "id": "te_01h3xz...",
    "task_id": "t_01h3xz...",
    "started_at": "2026-07-05T09:00:00Z",
    "ended_at": "2026-07-05T11:30:00Z",
    "duration_minutes": 150,
    "is_running": false,
    "note": "انتهيت من التصميم الأساسي"
  }
}
```

**WebSocket Event:** `TimerStopped` broadcast to `user.{id}` and `task.{id}` channels.

### 6.4 Manual Time Entry (Create)

```
POST /api/v1/time-entries
Auth: Bearer token
```

**Request:**
```json
{
  "task_id": "t_01h3xz...",
  "started_at": "2026-07-05T14:00:00Z",
  "ended_at": "2026-07-05T15:30:00Z",
  "note": "مراجعة التصميم مع الفريق",
  "is_manual": true
}
```

**Response (201):** Time entry object.

### 6.5 Update Time Entry

```
PUT /api/v1/time-entries/{id}
Auth: Bearer token (own entries only)
```

**Request:**
```json
{
  "started_at": "2026-07-05T14:00:00Z",
  "ended_at": "2026-07-05T16:00:00Z",
  "note": "تصحيح الوقت"
}
```

### 6.6 Delete Time Entry

```
DELETE /api/v1/time-entries/{id}
Auth: Bearer token (own entries only)
```

### 6.7 Time Report

```
GET /api/v1/time-entries/report?workspace_id={id}&from=2026-07-01&to=2026-07-31&group_by=day&user_id={id}&project_id={id}
Auth: Bearer token
Scope: workspace.member
```

**Query Parameters:**
| Param | Type | Description |
|---|---|---|
| `workspace_id` | string | Required |
| `from` | date | Start date (required) |
| `to` | date | End date (required) |
| `group_by` | enum | `day` / `week` / `month` / `user` / `project` |
| `user_id` | string | Filter by user |
| `project_id` | string | Filter by project |

**Response (200):**
```json
{
  "data": {
    "summary": {
      "total_minutes": 24000,
      "total_hours": 400,
      "billable_minutes": 19200,
      "avg_daily_hours": 6.5,
      "period": { "from": "2026-07-01", "to": "2026-07-31" }
    },
    "entries": [
      {
        "date": "2026-07-05",
        "minutes": 480,
        "projects": [
          { "project_id": "p_01h3xz...", "project_name": "حملة إطلاق المنتج", "minutes": 300 },
          { "project_id": "p_01h3yy...", "project_name": "موقع الشركة", "minutes": 180 }
        ]
      }
    ],
    "export_url": "https://api.tasksyncpro.com/api/v1/time-entries/report/export?token=...&format=csv"
  },
  "meta": { "request_id": "req_abc123" }
}
```

---

## 7. Comment Endpoints

### 7.1 List Comments

```
GET /api/v1/tasks/{id}/comments?page=1&per_page=50
Auth: Bearer token
Scope: project.member
```

**Response (200):**
```json
{
  "data": [
    {
      "id": "c_01h3xz...",
      "task_id": "t_01h3xz...",
      "user": { "id": "u_01h3xz...", "name": "سارة أحمد", "avatar_url": null },
      "body": "تم الانتهاء من التصميم، يرجى المراجعة @ليلى",
      "mentions": ["u_01h3yy..."],
      "created_at": "2026-07-05T11:30:00Z",
      "updated_at": null,
      "can_delete": true
    }
  ],
  "meta": { "total": 12, "per_page": 50 }
}
```

### 7.2 Create Comment

```
POST /api/v1/tasks/{id}/comments
Auth: Bearer token
Scope: project.member
```

**Request:**
```json
{
  "body": "تم الانتهاء من التصميم، يرجى المراجعة @ليلى"
}
```

**Response (201):** Comment object. `@mentions` auto-detected, notifications dispatched.

**WebSocket Event:** `CommentAdded` broadcast to `task.{id}` channel.

### 7.3 Delete Comment

```
DELETE /api/v1/comments/{id}
Auth: Bearer token (own comment only, or project admin)
```

**Response (200):**
```json
{ "data": { "message": "Comment deleted." } }
```

---

## 8. Attachment Endpoints

### 8.1 Upload Attachment

```
POST /api/v1/tasks/{id}/attachments
Auth: Bearer token
Scope: project.member
Content-Type: multipart/form-data
Max size: 10MB per file
Allowed types: jpg,png,gif,svg,pdf,doc,docx,xls,xlsx,zip
```

**Request (multipart/form-data):**
| Field | Type | Description |
|---|---|---|
| `file` | file | The file to upload |
| `name` | string | Optional display name |

**Response (201):**
```json
{
  "data": {
    "id": "a_01h3xz...",
    "task_id": "t_01h3xz...",
    "user_id": "u_01h3xz...",
    "name": "تصميم_الصفحة_الرئيسية.png",
    "size": 245760,
    "mime_type": "image/png",
    "url": "https://storage.tasksyncpro.com/attachments/a_01h3xz.png",
    "thumbnail_url": "https://storage.tasksyncpro.com/thumbnails/a_01h3xz.png",
    "created_at": "2026-07-05T11:30:00Z"
  }
}
```

### 8.2 Delete Attachment

```
DELETE /api/v1/attachments/{id}
Auth: Bearer token (uploader or project admin)
```

### 8.3 Download Attachment

```
GET /api/v1/attachments/{id}/download
Auth: Bearer token
Scope: project.member
```

Redirects to presigned S3 URL (15-minute expiry).

---

## 9. Tag Endpoints

### 9.1 List Tags

```
GET /api/v1/tags?workspace_id={id}
Auth: Bearer token
Scope: workspace.member
```

**Response (200):**
```json
{
  "data": [
    { "id": "tag_01h3xz...", "workspace_id": "w_01h3xy...", "name": "تصميم", "color": "#EF4444", "task_count": 15 },
    { "id": "tag_01h3yy...", "workspace_id": "w_01h3xy...", "name": "تطوير", "color": "#3B82F6", "task_count": 28 }
  ]
}
```

### 9.2 Create Tag

```
POST /api/v1/tags
Auth: Bearer token
Scope: workspace.admin
```

**Request:**
```json
{
  "workspace_id": "w_01h3xy...",
  "name": "اجتماعات",
  "color": "#F59E0B"
}
```

### 9.3 Delete Tag

```
DELETE /api/v1/tags/{id}
Auth: Bearer token
Scope: workspace.admin
```

Removes tag from all tasks (pivot table cleanup).

---

## 10. Notification Endpoints

### 10.1 List Notifications

```
GET /api/v1/notifications?page=1&per_page=20&type=task_assigned&read=false
Auth: Bearer token
```

**Query Parameters:**
| Param | Type | Description |
|---|---|---|
| `type` | enum | `task_assigned`, `task_due_soon`, `@mention`, `invite`, `timer_reminder` |
| `read` | boolean | Filter by read status |

**Response (200):**
```json
{
  "data": [
    {
      "id": "n_01h3xz...",
      "type": "task_assigned",
      "title": "مهمة جديدة",
      "body": "تم تعيينك في مهمة 'تصميم الصفحة الرئيسية'",
      "data": {
        "task_id": "t_01h3xz...",
        "project_id": "p_01h3xz...",
        "assignee_name": "ليلى محمد"
      },
      "read_at": null,
      "created_at": "2026-07-05T09:00:00Z"
    }
  ],
  "meta": {
    "total": 15,
    "unread_count": 3,
    "per_page": 20
  }
}
```

### 10.2 Mark as Read

```
PUT /api/v1/notifications/{id}/read
Auth: Bearer token
```

### 10.3 Mark All as Read

```
PUT /api/v1/notifications/read-all
Auth: Bearer token
```

**Response (200):**
```json
{
  "data": {
    "message": "All notifications marked as read.",
    "count": 3
  }
}
```

---

## 11. Dashboard / Analytics

### 11.1 Dashboard Stats

```
GET /api/v1/dashboard/stats?workspace_id={id}
Auth: Bearer token
Scope: workspace.member
```

**Response (200):**
```json
{
  "data": {
    "tasks": {
      "total": 45,
      "todo": 12,
      "in_progress": 18,
      "done": 15,
      "overdue": 4,
      "upcoming_week": 8
    },
    "projects": {
      "active": 3,
      "archived": 1
    },
    "time": {
      "today_minutes": 240,
      "week_minutes": 1200,
      "month_minutes": 4800
    },
    "members": {
      "total": 5,
      "active_today": 3
    }
  }
}
```

### 11.2 Recent Activity

```
GET /api/v1/dashboard/activity?workspace_id={id}&limit=20
Auth: Bearer token
Scope: workspace.member
```

**Response (200):**
```json
{
  "data": [
    {
      "id": "act_01h3xz...",
      "type": "task_created",
      "user": { "id": "u_01h3xz...", "name": "سارة أحمد" },
      "description": "أنشأ مهمة 'تصميم الصفحة الرئيسية'",
      "project_name": "حملة إطلاق المنتج",
      "created_at": "2026-07-05T09:00:00Z"
    }
  ]
}
```

---

## 12. Webhook Endpoints

### 12.1 List Webhooks

```
GET /api/v1/webhooks?workspace_id={id}
Auth: Bearer token
Scope: workspace.admin
```

**Response (200):**
```json
{
  "data": [
    {
      "id": "wh_01h3xz...",
      "workspace_id": "w_01h3xy...",
      "url": "https://hooks.slack.com/services/T00/B00/xxx",
      "events": ["task.created", "task.updated", "task.deleted"],
      "is_active": true,
      "last_sent_at": "2026-07-05T09:00:00Z",
      "last_status_code": 200,
      "created_at": "2026-06-25T10:30:00Z"
    }
  ]
}
```

### 12.2 Create Webhook

```
POST /api/v1/webhooks
Auth: Bearer token
Scope: workspace.admin
```

**Request:**
```json
{
  "workspace_id": "w_01h3xy...",
  "url": "https://hooks.slack.com/services/T00/B00/xxx",
  "events": ["task.created", "task.updated", "task.deleted"],
  "secret": "whsec_abc123def456"
}
```

`events` supported values:
- `task.created` — New task created
- `task.updated` — Task details/status/assignee changed
- `task.deleted` — Task soft deleted
- `time_entry.created` — Timer started / manual entry
- `time_entry.updated` — Timer stopped / entry edited
- `project.created`
- `project.updated`
- `project.deleted`
- `comment.created`
- `member.joined`

**Response (201):** Full webhook object. `secret` returned once only.

### 12.3 Delete Webhook

```
DELETE /api/v1/webhooks/{id}
Auth: Bearer token
Scope: workspace.admin
```

### 12.4 Test Webhook

```
POST /api/v1/webhooks/{id}/test
Auth: Bearer token
Scope: workspace.admin
```

**Response (200):**
```json
{
  "data": {
    "status_code": 200,
    "response_body": "ok",
    "duration_ms": 345,
    "sent_at": "2026-07-05T09:00:00Z"
  }
}
```

Sends test payload: `{ "event": "webhook.test", "workspace_id": "...", "timestamp": "..." }`.

---

## 13. WebSocket Events (Laravel Reverb)

### 13.1 Channel Architecture

| Channel | Auth | Purpose |
|---|---|---|
| `private-user.{user_id}` | Sanctum token | Per-user notifications, timer events |
| `private-project.{project_id}` | Member check | Task CRUD, comment, timer on project tasks |
| `private-workspace.{workspace_id}` | Admin check | Member join/leave, workspace settings |

### 13.2 Event Definitions

| Event | Channel | Payload (data field) |
|---|---|---|
| `TaskCreated` | `private-project.{id}` | `{ task: TaskObject, project_id: string }` |
| `TaskUpdated` | `private-project.{id}` | `{ task: TaskObject, changed: string[] }` |
| `TaskDeleted` | `private-project.{id}` | `{ task_id: string, project_id: string }` |
| `TaskMoved` | `private-project.{id}` | `{ task_id: string, from_status: string, to_status: string, position: int }` |
| `CommentAdded` | `private-project.{id}` | `{ comment: CommentObject, task_id: string }` |
| `TimerStarted` | `private-user.{id}` | `{ time_entry: TimeEntryObject }` |
| `TimerStopped` | `private-user.{id}` | `{ time_entry: TimeEntryObject, duration_minutes: int }` |
| `MemberJoined` | `private-workspace.{id}` | `{ user: UserSummary, role: string }` |

### 13.3 Client Usage (Vue + Echo)

```js
// Listen to private project channel
Echo.private(`project.${projectId}`)
    .listen('TaskCreated', (e) => {
        taskStore.addTask(e.task);
    })
    .listen('TaskMoved', (e) => {
        taskStore.moveTask(e.task_id, e.to_status, e.position);
    });

// Listen to private user channel
Echo.private(`user.${userId}`)
    .listen('TimerStarted', (e) => {
        timerStore.syncTimer(e.time_entry);
    });
```

### 13.4 Client Usage (Flutter)

```dart
// Using laravel_echo flutter package
final echo = Echo(...);

echo.private('project.$projectId')
    .listen('TaskCreated', (event) {
        context.read<TaskBloc>().add(TaskReceived(event['task']));
    });
```

---

## 14. Error Responses

### 14.1 401 Unauthenticated

```json
{
  "error": {
    "code": "UNAUTHENTICATED",
    "message": "Unauthenticated. Provide a valid Bearer token.",
    "meta": { "request_id": "req_abc123" }
  }
}
```

### 14.2 403 Forbidden

```json
{
  "error": {
    "code": "FORBIDDEN",
    "message": "You do not have permission to perform this action.",
    "details": {
      "required_role": "admin",
      "current_role": "member"
    },
    "meta": { "request_id": "req_abc123" }
  }
}
```

### 14.3 404 Not Found

```json
{
  "error": {
    "code": "NOT_FOUND",
    "message": "Resource not found.",
    "meta": { "request_id": "req_abc123" }
  }
}
```

### 14.4 422 Validation Error

```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The given data was invalid.",
    "details": {
      "email": ["The email has already been taken."],
      "password": ["The password must be at least 8 characters."]
    },
    "meta": { "request_id": "req_abc123" }
  }
}
```

### 14.5 429 Too Many Requests

```json
{
  "error": {
    "code": "RATE_LIMIT_EXCEEDED",
    "message": "Too many requests. Please slow down.",
    "details": {
      "retry_after_seconds": 45,
      "limit": 60,
      "remaining": 0,
      "resets_at": "2026-07-05T09:01:00Z"
    },
    "meta": { "request_id": "req_abc123" }
  }
}
```

Headers: `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `X-RateLimit-Reset`, `Retry-After`.

### 14.6 409 Conflict

```json
{
  "error": {
    "code": "CONFLICT",
    "message": "Resource conflict. Timer already running.",
    "meta": { "request_id": "req_abc123" }
  }
}
```

### 14.7 413 Payload Too Large

```json
{
  "error": {
    "code": "PAYLOAD_TOO_LARGE",
    "message": "File exceeds maximum size of 10MB.",
    "meta": { "request_id": "req_abc123" }
  }
}
```

### 14.8 500 Server Error

```json
{
  "error": {
    "code": "SERVER_ERROR",
    "message": "An unexpected error occurred.",
    "meta": { "request_id": "req_abc123" }
  }
}
```

---

## 15. Webhook Payloads

### 15.1 Outgoing Webhook Format

Every outgoing webhook POSTs to the configured URL with:

```json
{
  "event": "task.created",
  "workspace_id": "w_01h3xy...",
  "timestamp": "2026-07-05T09:00:00Z",
  "data": { /* event-specific payload */ },
  "signature": "sha256=abc123..."
}
```

**Signature:** HMAC-SHA256 of `event + timestamp + JSON.stringify(data)` using webhook secret.

### 15.2 Event Payloads

**task.created:**
```json
{
  "event": "task.created",
  "workspace_id": "w_01h3xy...",
  "timestamp": "2026-07-05T09:00:00Z",
  "data": {
    "id": "t_01h3xz...",
    "project_id": "p_01h3xz...",
    "title": "تصميم الصفحة الرئيسية",
    "status": "todo",
    "priority": "high",
    "assignee": { "id": "u_01h3xz...", "email": "layla@example.com" },
    "due_date": "2026-07-10"
  }
}
```

**task.updated:**
```json
{
  "event": "task.updated",
  "workspace_id": "w_01h3xy...",
  "timestamp": "2026-07-05T09:00:00Z",
  "data": {
    "id": "t_01h3xz...",
    "changes": ["status", "priority"],
    "previous": { "status": "todo", "priority": "high" },
    "current": { "status": "in_progress", "priority": "urgent" }
  }
}
```

### 15.3 Retry Policy

| Attempt | Delay | Notes |
|---|---|---|
| 1 | 0s | Immediate |
| 2 | 10s | — |
| 3 | 60s | — |
| 4 | 300s | — |
| 5 | 3600s | Final attempt |

After 5 failures: webhook auto-disabled, admin notified via email.

### 15.4 Idempotency

Webhooks carry `event` + `timestamp` for dedup. Consumers should ignore events with `timestamp` older than 5 minutes from their clock.

---

## 16. Rate Limiting Details

| Scope | Limit | Window | Burst |
|---|---|---|---|
| Auth (register, login, forgot) | 10/min | 1 min | 5 |
| Auth (authenticated) | 60/min | 1 min | 10 |
| General API | 300/min | 1 min | 50 |
| Reports | 30/min | 1 min | 5 |
| Webhook delivery | 100/min per webhook | 1 min | 20 |
| File upload | 10/min | 1 min | 3 |
| Invite | 20/hour | 1 hour | — |

Headers returned on all responses: `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `X-RateLimit-Reset`.

---

## 17. Integration Specifications

### 17.1 WhatsApp Cloud API (Meta)

**Purpose:** Send task assignment notifications, due-date reminders, invite links.

**Setup:**
1. Meta Business Account + WhatsApp Business Account
2. Register phone number
3. Create permanent access token
4. Configure webhook for incoming messages (optional)

**Environment Variables:**
```
WHATSAPP_PHONE_NUMBER_ID=123456789
WHATSAPP_ACCESS_TOKEN=EAAx...
WHATSAPP_API_VERSION=v18.0
WHATSAPP_WEBHOOK_VERIFY_TOKEN=tasksync_verify_2026
```

**Notification Flow:**
```
TaskAssigned event → Laravel Notification → WhatsAppChannel
  → HTTP POST to https://graph.facebook.com/v18.0/{phone-number-id}/messages
  → Body: { messaging_product: "whatsapp", to: "9665xxxxxxxx", 
            type: "template", template: { name: "task_assigned", 
            language: { code: "ar" }, components: [...] } }
  → Response: { messaging_product: "whatsapp", messages: [{ id: "wamid.xxx" }] }
```

**Template Approval:** Submit WhatsApp message templates for:
- `task_assigned` — "لقد تم تعيينك في مهمة {task_title} في مشروع {project_name}"
- `task_due_soon` — "مهمتك {task_title} تستحق خلال {hours} ساعات"
- `invite_workspace` — "تمت دعوتك للانضمام إلى {workspace_name} في TaskSync Pro"

**Rate Limits:** 250 conversations/day per phone number (free tier). Monitor via WhatsApp Manager.

### 17.2 Slack Incoming Webhook

**Purpose:** Send task updates to Slack channels (team notifications).

**Setup:**
1. User creates Slack app → Incoming Webhooks
2. Select channel → Get webhook URL
3. Configure in TaskSync workspace settings

**Payload Format:**
```json
{
  "blocks": [
    {
      "type": "header",
      "text": { "type": "plain_text", "text": "✅ مهمة جديدة: تص…" }
    },
    {
      "type": "section",
      "fields": [
        { "type": "mrkdwn", "text": "*المشروع:*\nحملة إطلاق المنتج" },
        { "type": "mrkdwn", "text": "*الأولوية:*\n🔴 عالية" },
        { "type": "mrkdwn", "text": "*المسؤول:*\nليلى محمد" },
        { "type": "mrkdwn", "text": "*تاريخ الاستحقاق:*\n10 يوليو 2026" }
      ]
    },
    {
      "type": "actions",
      "elements": [
        { "type": "button", "text": { "type": "plain_text", "text": "عرض المهمة" }, "url": "https://app.tasksyncpro.com/tasks/t_01h3xz..." },
        { "type": "button", "text": { "type": "plain_text", "text": "تغيير الحالة" }, "url": "https://app.tasksyncpro.com/tasks/t_01h3xz..." }
      ]
    }
  ]
}
```

**Events Mapped to Slack:**
| Event | Channel Default |
|---|---|
| `task.created` | `#task-updates` |
| `task.updated` (status change) | `#task-updates` |
| `comment.@mention` | `#mentions` |
| `task.due_soon` | `#reminders` |

### 17.3 Google Calendar Sync

**Purpose:** Sync task due dates to Google Calendar.

**OAuth 2.0 Setup:**
1. Google Cloud Console → Enable Calendar API
2. OAuth consent screen → Scopes: `https://www.googleapis.com/auth/calendar.events`
3. Client ID + Client Secret → Configure in TaskSync

**Auth Flow:**
```
User → Settings → Integrations → Google Calendar → "Connect"
  → Redirect to Google OAuth (consent screen)
  → Callback → POST /api/v1/integrations/google/callback
  → Store refresh_token (encrypted) against user
  → 200 OK → "Google Calendar connected"
```

**Sync Flow:**
```
Task due_date set/updated → GoogleCalendarSync job dispatched to queue
  → Refresh token (if expired)
  → POST https://www.googleapis.com/calendar/v3/calendars/primary/events
  → Body: {
      summary: task_title,
      description: task_url,
      start: { date: due_date, timeZone: user_timezone },
      end: { date: due_date, timeZone: user_timezone },
      source: { title: "TaskSync Pro", url: task_url }
    }
  → Store google_event_id on task
```

**Webhook (Push Notifications — optional):**
```
POST https://www.googleapis.com/calendar/v3/channels/watch
  → { id: channel_uuid, type: "web_hook", address: "https://api.tasksyncpro.com/webhooks/google/calendar" }
  → Google sends POST on event changes
  → Sync changes back to TaskSync
```

**Sync directions:**
| Direction | When | Implementation |
|---|---|---|
| TaskSync → Google Calendar | Task created/updated with due_date | Queue job |
| Google Calendar → TaskSync | Webhook notification received | Queue job (future) |
| Initial one-way sync | MVP | TaskSync → Google Calendar only |

---

## 18. Integration Auth Matrix

| Integration | Auth Method | Token Storage | Refresh |
|---|---|---|---|
| WhatsApp Cloud API | Permanent access token (Meta Business) | `.env` (server-side) | Manual rotation |
| Slack Incoming Webhook | Webhook URL (secret) | DB `webhook.url` encrypted | User reconfigures |
| Google Calendar | OAuth 2.0 (refresh token) | DB `user_integrations.refresh_token` encrypted | Auto-refresh via Google |
| Email (SMTP) | SMTP credentials | `.env` (server-side) | Manual |
| Firebase Cloud Messaging | Service account JSON | `.env` (server-side) | Auto via Google metadata |

---

## 19. OpenAPI 3.0 Spec

See `openapi.yaml` in this directory for machine-readable endpoint definitions, schemas, and examples.

---

## 20. Development & Testing

### 20.1 Local Setup

```bash
# Install dependencies
composer install
cp .env.example .env
php artisan key:generate

# Database
php artisan migrate --seed

# Serve API
php artisan serve

# Queue (in separate terminal)
php artisan horizon

# WebSocket (in separate terminal)
php artisan reverb:start
```

### 20.2 Testing Webhooks (Local)

Use ngrok to expose local server:
```bash
ngrok http 8000
# Set WEBHOOK_BASE_URL=https://xxxx.ngrok.io in .env
```

### 20.3 Testing WhatsApp/Slack

```bash
# Test Slack webhook via artisan
php artisan webhook:test-slack --url=https://hooks.slack.com/... --event=task.created

# Test WhatsApp
php artisan notification:test-whatsapp --to=9665xxxxxxxx --template=task_assigned
```

### 20.4 API Testing with Postman

Import `openapi.yaml` into Postman for full collection with auth headers, environments, and examples.

---

*Generated by API & Integration Specialist · Gate 3 · 2026-06-25*
*Locked for Gate 4 Build — any changes require Architecture Review Board approval.*
