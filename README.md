# 🤖 AI Employee Platform

A production-ready, multi-tenant SaaS platform that allows businesses to create, configure, deploy, and manage AI-powered digital employees that perform real business workflows.

## Architecture

```
User Message → Message Processor → Intent Analysis → AI Orchestrator
    → Knowledge Retrieval (RAG) → Tool Selection → Tool Execution
    → Result Validation → AI Response → User
```

## Tech Stack

- **Backend**: Laravel 13.8 (PHP 8.3)
- **Frontend**: React 19 + Inertia.js + Tailwind CSS 4
- **Database**: MySQL 8 / PostgreSQL 16
- **Queue/Cache**: Redis
- **AI Provider**: DeepSeek API
- **Auth**: Laravel Sanctum + Spatie Permission

## Quick Start (Local Development)

```bash
# Clone and install
composer install
npm install

# Configure environment
cp .env.example .env
php artisan key:generate

# Run database migrations
php artisan migrate --seed

# Start development server
composer run dev
```

**Demo Login:** `owner@nomdal.test` / `password`

## Docker Deployment

```bash
# Build and start
docker compose up -d

# The app will be available at http://localhost:8080
```

## Key Features

### AI Employees
Create AI employees with configurable roles, personality, knowledge, tools, and channels.

- 🤖 **Sales Employee** — Generate and qualify leads, provide product info, create orders
- 🎧 **Support Employee** — Resolve questions, check order status, create tickets  
- 📞 **Receptionist** — Schedule appointments, handle inquiries

### Knowledge Base + RAG
Upload documents (PDF, DOCX, TXT, CSV) and URLs. The AI retrieves relevant information using semantic search with embeddings and cosine similarity.

### Tool Framework (13 built-in tools)
```
search_products | get_product | get_price | check_inventory
create_lead | create_customer | get_customer
create_order | get_order | get_order_status | cancel_order
transfer_to_human | create_task
```

### Multi-Channel
- 💬 Website Chat Widget (embeddable `<script>`)
- 📱 WhatsApp (Cloud API)
- 🔌 REST API (Sanctum auth)
- 🤖 MCP external tools (Gmail, Calendar, CRM, Microsoft 365, custom)
- 📧 Email (coming soon)

### MCP External Tools
Connect external Model Context Protocol (MCP) servers so AI employees can call third-party tools (Gmail, Google Calendar, CRM, Microsoft 365, or any custom MCP server) through the same permission → policy → approval → audit pipeline as built-in tools.

- Tenant-scoped connections with credentials encrypted at rest + OAuth token refresh
- Google OAuth login (authorization-code flow) for Calendar and Gmail
- Per-tenant rate limiting, circuit breaker, and retry
- SSRF protection on endpoints and prompt-injection sanitization
- Enable synced tools per AI employee from the employee editor (grouped as External/MCP tools)
- Feature flag (`MCP_ENABLED`) and provider allow-list for staged rollout

### CRM & Lead Scoring
Lightweight CRM with lead pipeline, configurable lead scoring, and AI-generated lead attribution.

### Automation Engine
Trigger → Conditions → Actions workflow system with 6 triggers, 10 condition operators, and 7 action types.

### Analytics & Billing
12 KPI dashboard with conversation funnel, 6-month trends, and AI cost tracking. 4 subscription tiers.

## API

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

## Security

- CSRF/XSS/SQL injection protection (Laravel defaults)
- Prompt injection defenses (multi-layered)
- Tenant isolation via global scopes
- Encrypted integration credentials (MCP included)
- SSRF protection on MCP endpoints (private/reserved addresses rejected)
- Rate limiting (Redis) + MCP circuit breaker
- Audit logging on all write operations
- Role-based access control (6 roles, 78 permissions)

## Development Phases

1. ✅ Foundation (multi-tenancy, auth, database)
2. ✅ AI Core Engine (DeepSeek provider, orchestrator)
3. ✅ Tool Framework (13 tools + human confirmation)
4. ✅ Knowledge Base + RAG
5. ✅ CRM & Leads
6. ✅ Channels (Web Chat + WhatsApp)
7. ✅ E-Commerce & Appointments
8. ✅ Automation & Workflows
9. ✅ Analytics & Billing
10. ✅ Hardening & Deployment

## License

MIT