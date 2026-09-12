# Nomdal — Platform Overview

> **What it is:** Nomdal is a **multi-tenant SaaS platform** that lets businesses create, configure, deploy, and manage **AI-powered "digital employees"** that run repetitive business work. The centerpiece is an **outbound AI Sales Employee** that finds prospects, qualifies them, and follows up automatically — then turns interested replies into a CRM opportunity, a quote, an invoice, and a calendar meeting. Across WhatsApp, web chat, email, REST API, and MCP-connected tools (Gmail, Calendar, CRM, Microsoft 365, custom).
>
> Tagline: **"A chatbot answers. An AI employee works."** — *AI employees that run your repetitive business work.*

---

## 1. Tech Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 13.8 (PHP 8.3) |
| Frontend | React 19 + Inertia.js + Tailwind CSS 4 |
| Database | MySQL 8 / PostgreSQL 16 |
| Queue / Cache | Redis |
| AI Provider | DeepSeek API |
| Auth | Laravel Sanctum + Spatie Permission (6 roles, 78 permissions) |
| Payments | Nomba (NGN + USD) |
| Deployment | Docker / Docker Compose |

**Demo login:** `owner@nomdal.test` / `password`

---

## 2. Product Surfaces (Three Apps in One)

1. **Marketing / Public site** (`/`, `/product`, `/templates`, `/pricing`, `/marketplace`, etc.) — Blade views under `resources/views/frontpage/`.
2. **Tenant app** (logged-in business users) — React/Inertia pages under `resources/js/Pages/` with `TenantLayout`.
3. **Platform Admin** ("Platform Owner" super-admin) — React/Inertia pages under `resources/js/Pages/Platform/` with `PlatformLayout`.

---

## 3. Marketing Site (Public)

**Menu:** Product · AI Employees · Plugins · Pricing · Sign in · Start free

**Pages** (routes in `routes/web.php`):

| Route | Purpose |
|---|---|
| `/` | Home — repositioned hero ("AI employees that run your repetitive business work"), WhatsApp demo, capability ticker, sales-first features, comparison, FAQ |
| `/product` | Product — outcome-first pillars (Find Customers → Do the Work → Connect → Measure), knowledge, tools, channels, CRM/automation/analytics, security |
| `/templates` | AI Employees — core roles, anatomy, industry templates |
| `/marketplace` | Public plugin marketplace (browse-only) |
| `/pricing` | Pricing — 5 tiers (Free + 4 paid), comparison table, FAQ |
| `/docs` | Documentation |
| `/connect` | Integrations |
| `/about`, `/services`, `/contact` | Company pages |
| `/privacy`, `/terms` | Legal |
| `/sitemap.xml` | SEO sitemap |

---

## 4. Tenant App — Dashboard & Navigation

### 4.1 Dashboard (`/dashboard`)

A **money-first** dashboard:

- **"This Month" headline:** Revenue · Qualified Leads · Deals Won · Prospects Contacted · Pipeline Value (+ AI actions completed)
- **AI Employee Activity** — per-employee rollup (conversations, leads, prospects found/qualified/contacted/replied)
- **Operational KPIs:** Customers · Conversations · Leads · Revenue · Open Conversations · AI Employees · Resolution Rate · Appointments
- **Conversations trend chart** (7 days) + **Recent activity feed**

### 4.2 Tenant Sidebar Menu (from `TenantLayout.jsx`)

| Section | Items |
|---|---|
| **MAIN** | Dashboard, Inbox |
| **AI WORKFORCE** | AI Employees, Knowledge, Automations |
| **CUSTOMERS** | Customers, Leads |
| **GROWTH** | Prospecting |
| **OPERATIONS** | Products, Orders, Appointments |
| **INSIGHTS** | Analytics |
| **MANAGEMENT** | Team, Files, Help, Integrations, Plugins, Installed Plugins, Billing, Settings, Scheduling, API Keys |

*(Items are permission-gated — each declares a `permission` and hides if the user lacks it.)*

---

## 5. AI Employee Functionality (Core Feature)

### 5.1 What an AI employee is

An `AiEmployee` model (`app/Models/AiEmployee.php`) holds:

- **Identity:** name, role, department, description, avatar
- **Behavior:** system instructions, personality, tone, language
- **Intelligence:** knowledge-base IDs, enabled tools, AI model, temperature, max tool calls, max context messages
- **Deployment:** allowed channels, working hours, escalation rules, response settings
- **State:** `is_active`, `is_template`, `template_type`, counters (conversations, leads generated, escalation rate)

### 5.2 Employee lifecycle (routes)

| Action | Route |
|---|---|
| List | `GET /ai-employees` |
| Create | `GET/POST /ai-employees` |
| Edit / Update | `GET/PUT /ai-employees/{id}` |
| Toggle active | `POST /ai-employees/{id}/toggle` |
| Test | `POST /ai-employees/{id}/test` |
| Delete | `DELETE /ai-employees/{id}` |
| Channels | `GET /ai-employees/{id}/channels` |
| Public chat | `POST /chat/{uuid}/message` |

Plan limits are enforced on creation via the **Usage Tracker** (e.g. Free/Starter = 1 AI employee).

### 5.3 Built-in employee templates

From `AiEmployeeController::getTemplates()`:

- **Sales Employee** — finds prospects, qualifies leads, follows up
- **Support Employee** — resolves issues, checks orders, escalates
- **Receptionist** — books appointments, answers enquiries
- **Order Employee** — product enquiries, orders, invoices
- **Admin Employee** — repetitive requests, documents, reports
- **Technical Support** — troubleshooting workflows
- **HR Assistant** — policies, benefits, PTO, onboarding
- **Booking & Reservations Agent** — reservations, availability, confirmations
- **Sales Development Rep (SDR)** — outbound prospecting (feature-flagged)

Plus any **platform-published templates** created under Platform → AI Templates.

### 5.4 Core roles (marketing)

Sales Employee · Support Employee · Receptionist · Order Employee · Admin Employee

### 5.5 Industry vertical templates

E-commerce store · Furniture & retail · Clinic & wellness · Salon & spa · Real estate · Law firm

### 5.6 Departments & "Recommend my workforce"

Employees are grouped by **department**:

| Department | Templates |
|---|---|
| 💰 Revenue | Sales, Lead Qualifier, Sales Development Rep |
| 💬 Customer Experience | Support, Receptionist |
| 🛒 Operations | E-commerce, Booking |
| 🧑💼 Administration | HR, Technical Support |

**"✨ Recommend my workforce"** (`/ai-employees/workforce`) suggests a team based on what the business sells and deploys it in one click (respecting plan limits).

---

## 6. Knowledge Base + RAG

- Upload documents: **PDF, DOCX, TXT, CSV, URLs**
- **Semantic search** via embeddings + cosine similarity
- Multiple knowledge bases per organization (and per employee)
- **Knowledge Gaps** (`/knowledge-gaps`) — a "learning loop" surfacing unanswered questions

Models: `KnowledgeBase`, `KnowledgeSource`, `KnowledgeDocument`, `KnowledgeChunk`, `KnowledgeEmbedding`, `KnowledgeGap`.

---

## 7. Tool Framework (41 built-in tools)

Registered in `app/Providers/AppServiceProvider.php` via `ToolRegistry`. Tools are the "work" AI employees can do.

### Commerce & Catalog
`search_products`, `get_product`, `get_price`, `check_inventory`, `search_store`, `create_store_order`

### CRM & Leads
`create_lead`, `create_customer`, `get_customer`

### Orders & Shipping
`create_order`, `get_order`, `get_order_status`, `cancel_order`, `track_shipment`

### Cart & Discounts
`add_to_cart`, `get_cart`, `apply_discount`

### Appointments
`schedule_appointment`, `get_available_slots`, `cancel_appointment`, `reschedule_appointment`

### Quotations, Invoices & Payments
`generate_quotation`, `generate_invoice`, `record_payment`, `send_followup`

### Support & Ticketing
`create_ticket`, `update_ticket`, `transfer_to_human`, `create_task`

### Knowledge & Documents
`knowledge_search`, `extract_document_data`, `generate_report`

### Outbound Prospecting (SDR)
`hunt_icp`, `qualify_prospect`, `research_prospect`, `run_outbound_pipeline`, `draft_outreach`, `send_outreach`, `get_prospecting_status`, `generate_proposal`, `book_meeting`

*(Plus **dynamic MCP tools** resolved per-tenant through a `McpToolRegistrar`.)*

---

## 8. Multi-Channel

| Channel | Notes |
|---|---|
| 💬 WhatsApp | Cloud API (native, Africa-first) |
| 🌐 Web Chat | Embeddable `<script>` widget |
| ✉️ Email | Inbox + follow-ups |
| 🔌 REST API | Documented v1 (Sanctum) |
| 🧩 MCP Tools | Gmail, Calendar, CRM, Microsoft 365, custom |
| 🛒 Commerce | Shopify + WooCommerce sync |

---

## 9. MCP External Tools

Connect external Model Context Protocol (MCP) servers so AI employees call third-party tools through the same **permission → policy → approval → audit** pipeline:

- Tenant-scoped connections, credentials encrypted at rest + OAuth token refresh
- Google OAuth (authorization-code) for Calendar and Gmail
- Per-tenant rate limiting, circuit breaker, retry
- SSRF protection + prompt-injection sanitization
- Enable synced tools per employee (grouped as External/MCP tools)
- Feature flag `MCP_ENABLED` + provider allow-list

---

## 10. CRM, Leads & Automations

- **CRM** — lightweight pipeline, configurable lead scoring, AI-generated lead attribution
- **Leads / Customers** — `/leads`, `/customers` with AI summaries
- **Automation Engine** — Trigger → Conditions → Actions; **6 triggers, 10 condition operators, 7 action types** (`/automations`)

---

## 11. E-Commerce & Appointments

- **Products / Orders** — catalog, variants, inventory, orders, shipments, carts, promo codes
- **Quotations / Invoices / Payments** — quote & invoice generation, payment recording (Nomba)
- **Appointments** — scheduling with availability, services, conflict checks, rescheduling, cancellations (`/appointments`, `/settings/scheduling`)

---

## 12. Analytics, Reports & Approvals

- **Analytics** (`/analytics`) — **12-KPI dashboard** with conversation funnel, 6-month trends, AI cost tracking
- **Reports** (`/reports`) — scheduled AI-generated business performance reports
- **Approvals** (`/approvals`) — human-approval queue for AI tool executions (approve/deny)

---

## 13. Pricing (5 Tiers)

Source of truth: `database/seeders/PlansTableSeeder.php` (mirrored in `frontpage/pricing.blade.php`).

| Plan | Price (NGN / USD) | AI Employees | Messages/mo | Highlights |
|---|---|---|---|---|
| **Free** | ₦0 | 1 | 100 | Web chat only, 1 KB (20 sources), basic lead tracking, community support |
| **Starter** | ₦45,000 / **$29** | 1 | 500 | Web chat only, 1 KB (50 sources), basic CRM, 5 automations, 5 team members, email support |
| **Business** ⭐ | ₦150,000 / **$99** | 3 | 3,000 | All channels (Web, Email, WhatsApp), full CRM, appointments, commerce, 20 automations, 15 team, priority support |
| **Professional** | ₦375,000 / **$249** | 10 | 10,000 | All channels + webhooks, custom tools, REST API, 50 automations, 50 team, 99.5% SLA |
| **Enterprise** | Custom | Unlimited | Unlimited | White-label (add-on), custom integrations, dedicated infra, 500+ team, 24/7 phone, 99.9% SLA, QBRs |

- **Annual billing:** ~2 months free (−17%)
- **Payments:** Naira via **Nomba**, USD for international
- New workspaces auto-start on the **Free** plan (auto-subscribed on signup); prorated plan changes

---

## 14. Billing & Payments

- **Billing page** (`/billing`): subscribe, cancel, invoices
- **Nomba callback** for NGN payment confirmation
- **Subscription invoices** (blade: `resources/views/invoices/subscription.blade.php`)
- **Tax & invoices** — platform-level tax settings + invoice generation
- **Multi-currency** — NGN default; USD, GHS, KES, ZAR, EUR, GBP, CAD, AUD, INR, JPY symbols supported in dashboard

---

## 15. Platform Admin (Platform Owner)

Menu (from `PlatformLayout.jsx`):

| Section | Items |
|---|---|
| **PLATFORM** | Dashboard, Organizations (tenants), Users, Subscriptions, Plugins |
| **GROWTH** | Prospecting, Prospecting Settings, Suppression List |
| **AI PLATFORM** | Providers, Models, Templates, Tools, Feature Flags |
| **OPERATIONS** | Usage & Costs, System Health, Queues, Failed Jobs, AI Runs |
| **SUPPORT** | Announcements, Tickets, Knowledge Processing, Integrations, Integration Docs |
| **SYSTEM** | Settings, Tax & Billing, Invoices |

Key platform-admin capabilities:
- Manage tenants (toggle active, assign plans, set AI budget, add users, start support sessions)
- Manage platform users & roles
- Edit plans & pricing
- Manage AI providers, models, templates, tools, feature flags
- Monitor usage/costs, system health, queues, failed jobs, AI runs/evaluations
- Manage plugins, announcements, tickets, invoices, tax settings

---

## 16. Plugins & Marketplace

- **Public marketplace** (`/marketplace`) — browse published plugins
- **Tenant plugin directory** (`/plugins`) — browse + download plugin versions
- **Plugin installations** (`/plugins/installations`) — install, manage, regenerate signing secret, revoke
- **WordPress plugin** shipped under `plugins/wordpress/nomdal-connect/`
- Runtime + webhook delivery (`PluginRuntimeController`, `WebhookController`)

---

## 17. Outbound Prospecting (the AI Sales Employee — tenant-facing)

The outbound engine is now a **tenant-facing product surface** at `/prospecting` (previously platform-only). It runs the full closed loop:

**Hunt → Qualify → Research → Outreach → Follow-up → Reply → Book meeting → CRM → Quote → Invoice**

### 17.1 Web search (platform shared or bring-your-own key)
- Each tenant can use the **platform-level shared search** (default) or connect their own **Serper.dev** / **Brave Search** API key at `/prospecting/settings` (tenant keys encrypted at rest).
- Choosing **None** disables live search, and hunts fall back to AI-generated suggestions (with a warning banner).

### 17.2 Buyer personas
- **Buyer personas** (`/prospecting/personas`) — tenant-scoped `BuyerPersona` library describing the *human* decision-maker (role titles, goals, pains, objections, buying triggers, messaging hooks, value props, channels, keywords).
- **AI-assisted generation** — `BuyerPersonaService::generate()` drafts a persona from an offer via DeepSeek.
- **Template library** — a seeded set of global platform personas (`is_template = true`, no org) that tenants can one-click clone into their workspace (`BuyerPersonaTemplateSeeder` + `/prospecting/personas/use-template`).
- A campaign can attach a persona (`buyer_persona_id` + `buyer_persona_snapshot`); the persona feeds Hunt queries, Qualify scoring (`persona_fit`), Research notes, and Outreach copy.

### 17.3 Hunt & Qualify
- **Hunt** (`hunt_icp`) — web search + DeepSeek generation, per-campaign `daily_limit`; queries merge ICP keywords/job-titles with persona roles/keywords.
- **Qualify** (`qualify_prospect`) — 1–10 scoring, threshold 7 → `qualified`.

### 17.4 Research ("why this prospect")
- **Research** (`research_prospect`) — fetches the prospect's online presence (via the tenant's search key) and generates evidence-backed "why good fit" notes + sources (`research_notes`, `research_sources`).

### 17.5 Outreach & Follow-up
- **Outreach** (`draft_outreach` / `send_outreach`) — 2-pass personalized cold email.
- **Follow-up** — automatic cadence (day 3 / 7 / 14, max 3) for contacted prospects who don't reply (`ProspectFollowupService` + `ProspectFollowupJob`).

### 17.6 Reply → revenue (auto-conversion)
- **Reply** — `ReplyAlertService` classifies intent (interested / not interested) and alerts via Telegram/email.
- **Interested → auto-conversion** (`ProspectConversionService`): creates a **CRM Lead** (stage "Qualified", `estimated_value`), a **service quotation**, and an **invoice** — no product catalog needed.
- **Proposal** (`generate_proposal`) — service/custom-amount quote + invoice for agencies and service businesses.

### 17.7 Book meeting → Google Calendar
- **Book meeting** (`book_meeting`) — creates a Google Calendar event via the tenant's Google Calendar MCP connection (`CalendarMeetingService`); marks the prospect `meeting_booked_at` + `meeting_link`.

### 17.8 Surface & compliance
- **Campaigns** — create/toggle/delete, run hunt/qualify/research/outreach/follow-up.
- **Prospects** — list, detail, qualify, research, convert, book meeting, suppress.
- **Suppression list** — opt-out / compliance enforcement.
- **Compliance** — tenant scope + GDPR-style columns, one-click unsubscribe (`/prospecting/unsubscribe/{token}`), `ComplianceGate`.
- Delivery & reply webhooks.

---

## 18. Onboarding — "Get Your First Customer"

The onboarding wizard (`OnboardingController` + `Onboarding/Wizard.jsx`) walks a new business through an **outcome flow** (not just "create an employee"):

`create_org → configure_profile → sales_goal ("what do you sell?") → icp ("who are your customers?") → launch → results`

On launch, `FirstCustomerService` automatically:
1. Creates an **AI Sales Employee** (SDR) tagged `revenue`.
2. Builds a **ProspectingCampaign** from the ICP.
3. Runs **Hunt → Qualify** synchronously so scored prospects appear immediately.

The results step shows ranked prospects ("Prospects found"), with links to the Prospecting dashboard and the main dashboard.

---

## 19. API (REST v1)

```
POST /api/v1/messages
GET  /api/v1/conversations
GET  /api/v1/customers
POST /api/v1/customers
POST /api/v1/leads
GET  /api/v1/leads
POST /api/v1/orders
GET  /api/v1/orders/{id}
GET  /api/v1/ai-employees
```

API keys managed under `/settings/api-keys` (with scopes + revocation).

---

## 20. Security & Governance

- CSRF / XSS / SQL-injection protection (Laravel defaults)
- Multi-layered **prompt-injection defenses**
- **Tenant isolation** via global scopes
- **Encrypted credentials** at rest (MCP/integrations)
- **SSRF protection** on MCP endpoints
- **Rate limiting** (Redis) + MCP **circuit breaker**
- **Audit logging** on all write operations
- **RBAC** — 6 roles, 78 permissions (Spatie)

---

## 21. Development Phases (all ✅ complete)

**Platform foundation:**
1. ✅ Foundation (multi-tenancy, auth, database)
2. ✅ AI Core Engine (DeepSeek provider, orchestrator)
3. ✅ Tool Framework (tools + human confirmation)
4. ✅ Knowledge Base + RAG
5. ✅ CRM & Leads
6. ✅ Channels (Web Chat + WhatsApp)
7. ✅ E-Commerce & Appointments
8. ✅ Automation & Workflows
9. ✅ Analytics & Billing
10. ✅ Hardening & Deployment

**Repositioning & growth ("AI employees that run repetitive work"):**
11. ✅ Phase 0 — Repositioning copy (sales-outcome first, WhatsApp emphasis)
12. ✅ Phase 1 — "Get Your First Customer" onboarding + tenant-facing prospecting
13. ✅ Phase 2 — Money-centric dashboard
14. ✅ Phase 3 — Departments + "AI Workforce as a Service"
15. ✅ Phase 4 — Free tier + WhatsApp emphasis

**Closed-loop outbound (gap-closing):**
16. ✅ Tenant bring-your-own web-search API key
17. ✅ Research / enrichment ("why this prospect")
18. ✅ Automatic follow-up cadence
19. ✅ Reply intent → opportunity + quote + invoice
20. ✅ Book meeting → Google Calendar

---

*This document was generated by inspecting the Nomdal codebase (routes, layouts, models, seeders, controllers, and frontend pages). It reflects the current implemented state of the platform.*



