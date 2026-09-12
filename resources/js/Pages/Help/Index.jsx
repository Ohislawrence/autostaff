import { Head } from '@inertiajs/react';
import TenantLayout from '@/Layouts/TenantLayout';
import { useState } from 'react';

const sections = [
    {
        id: 'getting-started',
        title: 'Getting Started',
        icon: '🚀',
        content: (
            <div className="space-y-6">
                <p className="text-gray-700">Welcome to the AI Employee platform! Follow the step-by-step guide below to get your AI up and running quickly.</p>

                <div className="bg-primary-50 border border-primary-100 rounded-xl p-5">
                    <h3 className="font-bold text-primary-800 text-lg mb-3">📋 Complete Setup Flow</h3>
                    <ol className="list-decimal list-inside space-y-3 text-sm text-gray-700">
                        <li><strong>Create an AI Employee</strong> → Pick a template or build custom. This defines your AI's role and behavior.</li>
                        <li><strong>Activate & Configure Channels</strong> → Turn it on and connect web chat, email, or WhatsApp.</li>
                        <li><strong>Upload Knowledge</strong> → Add documents so the AI can give accurate answers.</li>
                        <li><strong>Set Up Availability</strong> → Define business hours and services for appointment scheduling.</li>
                        <li><strong>Embed & Go Live</strong> → Add the chat widget to your website or start conversations.</li>
                    </ol>
                </div>

                <h3 className="font-bold text-gray-900 text-lg mt-6">🎯 After Creating an AI Employee</h3>
                <p className="text-sm text-gray-600">New AI Employees start as <span className="bg-gray-100 px-2 py-0.5 rounded text-xs font-medium">Inactive</span>. Here's what to do next:</p>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-3 mt-3">
                    <StepCard number="1" title="Activate" desc="Click the green Activate button on the AI Employee card. Only active employees handle conversations." />
                    <StepCard number="2" title="Configure Channels" desc="Click the Channels button → toggle Web Chat ON → copy the widget snippet to your website." />
                    <StepCard number="3" title="Upload Knowledge" desc="Go to Knowledge → create a base → upload documents. The AI will reference these when answering." />
                </div>

                <h3 className="font-bold text-gray-900 text-lg mt-6">📋 Setup by Template Type</h3>

                {/* Sales Template */}
                <div className="bg-blue-50 border border-blue-100 rounded-xl p-5">
                    <h4 className="font-bold text-blue-800 flex items-center gap-2">💰 AI Sales Employee</h4>
                    <p className="text-xs text-blue-600 mt-1 mb-3">Use this for lead generation, product inquiries, and order creation.</p>
                    <div className="space-y-2 text-sm text-blue-700">
                        <CheckItem text="Create the AI Sales Employee from the template" />
                        <CheckItem text="Activate it and connect to Web Chat or WhatsApp" />
                        <CheckItem text="Add your products in Products → the AI can search, price, and check stock" />
                        <CheckItem text="Upload product info to Knowledge so the AI can answer questions" />
                        <CheckItem text="Done! The AI will now generate leads and create orders automatically" />
                    </div>
                </div>

                {/* Support Template */}
                <div className="bg-green-50 border border-green-100 rounded-xl p-5">
                    <h4 className="font-bold text-green-800 flex items-center gap-2">🎧 AI Customer Support</h4>
                    <p className="text-xs text-green-600 mt-1 mb-3">Use this for answering questions, order tracking, and customer support.</p>
                    <div className="space-y-2 text-sm text-green-700">
                        <CheckItem text="Create the AI Support Employee from the template" />
                        <CheckItem text="Activate it and connect to Web Chat, Email, or WhatsApp" />
                        <CheckItem text="Upload FAQs, policies, and support docs to Knowledge" />
                        <CheckItem text="Done! The AI will answer questions and escalate to humans when needed" />
                    </div>
                </div>

                {/* Receptionist Template */}
                <div className="bg-purple-50 border border-purple-100 rounded-xl p-5">
                    <h4 className="font-bold text-purple-800 flex items-center gap-2">📞 AI Receptionist</h4>
                    <p className="text-xs text-purple-600 mt-1 mb-3">Use this for appointment scheduling, inquiries, and directing customers.</p>
                    <div className="space-y-2 text-sm text-purple-700">
                        <CheckItem text="Create the AI Receptionist from the template" />
                        <CheckItem text="Activate it and connect to Web Chat" />
                        <CheckItem text="Set up Availability (business days/hours) in Appointments" />
                        <CheckItem text="Define Services with durations and pricing in Appointments" />
                        <CheckItem text="Upload service info to Knowledge so the AI can answer questions" />
                        <CheckItem text="Done! The AI will schedule, reschedule, and cancel appointments" />
                    </div>
                </div>

                <h3 className="font-bold text-gray-900 text-lg mt-6">✅ Completion Checklist</h3>
                <p className="text-sm text-gray-500">After completing all steps, your AI Employee is ready to handle real customer conversations. You can monitor everything from the Dashboard and Inbox.</p>
            </div>
        ),
    },
    {
        id: 'ai-employees',
        title: 'AI Employees',
        icon: '🤖',
        content: (
            <div className="space-y-3">
                <p>AI Employees are the core of the platform. Each one is a configurable AI agent with its own role, personality, and capabilities.</p>
                <h4 className="font-semibold text-gray-900 mt-4">Key Concepts</h4>
                <ul className="list-disc list-inside space-y-1 text-sm">
                    <li><strong>Role</strong> — What the AI does (e.g., Sales Representative, Support Agent).</li>
                    <li><strong>Personality & Tone</strong> — How the AI communicates with customers.</li>
                    <li><strong>System Instructions</strong> — Detailed behavioral guidelines for the AI.</li>
                    <li><strong>Tools</strong> — Capabilities you grant (search products, create orders, etc.).</li>
                    <li><strong>Channels</strong> — Where the AI is available (web chat, email, WhatsApp).</li>
                    <li><strong>Active/Inactive</strong> — Toggle an AI Employee on or off anytime.</li>
                </ul>
                <h4 className="font-semibold text-gray-900 mt-4">Build a Workforce Fast</h4>
                <p className="text-sm text-gray-600">Click <strong>✨ Recommend my workforce</strong> to get a suggested team grouped by department — 💰 Revenue, 💬 Customer Experience, 🛒 Operations, and 🧑‍💼 Administration — then deploy them in one click.</p>
                <h4 className="font-semibold text-gray-900 mt-4">Where to Find Actions</h4>
                <ul className="list-disc list-inside space-y-1 text-sm">
                    <li><strong>Activate/Deactivate</strong> — Button on the AI Employee card.</li>
                    <li><strong>Edit</strong> — Change name, personality, tools, temperature.</li>
                    <li><strong>Channels</strong> — Connect web chat, email, or WhatsApp (get embed code here).</li>
                </ul>
            </div>
        ),
    },
    {
        id: 'channels',
        title: 'Channels & Integrations',
        icon: '🔌',
        content: (
            <div className="space-y-3">
                <p>Connect your AI Employees to various communication channels so customers can reach them.</p>
                <h4 className="font-semibold text-gray-900 mt-4">How to Configure Channels</h4>
                <ol className="list-decimal list-inside space-y-2 text-sm">
                    <li>Go to <strong>AI Employees</strong> → find your employee card.</li>
                    <li>Click the <strong>"Channels"</strong> button (purple, next to Edit).</li>
                    <li>Toggle channels ON/OFF for each employee.</li>
                    <li>For web chat, copy the embed snippet and paste it into your website HTML.</li>
                </ol>
                <h4 className="font-semibold text-gray-900 mt-4">Available Channels</h4>
                <ul className="list-disc list-inside space-y-1 text-sm">
                    <li><strong>Web Chat</strong> — Embed a chat widget directly on your website.</li>
                    <li><strong>Email</strong> — Connect via SMTP, Mailgun, SendGrid, or any provider (see Integrations).</li>
                    <li><strong>WhatsApp</strong> — Connect via WhatsApp Business API (see Integrations).</li>
                </ul>

                <div className="bg-yellow-50 border border-yellow-200 rounded-xl p-4 mt-4">
                    <h4 className="font-semibold text-yellow-800 flex items-center gap-2 mb-2">
                        <span>⚠️</span> WhatsApp Multi-Tenant Setup
                    </h4>
                    <div className="text-sm text-yellow-700 space-y-2">
                        <p><strong>Each organization must have its own WhatsApp Business Account.</strong> This is required by Meta and cannot be shared across tenants.</p>
                        <p><strong>Why?</strong> Each WhatsApp Business Phone Number has a unique <code className="bg-yellow-100 px-1 rounded text-xs">phone_number_id</code> that identifies which tenant receives messages. The platform uses this ID to route incoming messages to the correct organization.</p>
                        <p className="pt-2 border-t border-yellow-300"><strong>Setup Requirements:</strong></p>
                        <ul className="list-disc list-inside space-y-1 pl-3">
                            <li>Create a WhatsApp Business Account at business.facebook.com</li>
                            <li>Get your own Access Token and Phone Number ID from Meta</li>
                            <li>Configure your unique webhook in Meta Developer Portal</li>
                            <li>Complete business verification (2-5 business days)</li>
                        </ul>
                        <p className="pt-2 text-xs">💡 <strong>Tip:</strong> Test mode is available immediately with limited phone numbers while verification is pending.</p>
                    </div>
                </div>

                <h4 className="font-semibold text-gray-900 mt-4">Other Integrations</h4>
                <ul className="list-disc list-inside space-y-1 text-sm">
                    <li><strong>REST API</strong> — Integrate programmatically with your systems.</li>
                    <li><strong>Webhooks</strong> — Receive real-time event notifications.</li>
                </ul>
            </div>
        ),
    },
    {
        id: 'knowledge',
        title: 'Knowledge Base',
        icon: '📚',
        content: (
            <div className="space-y-3">
                <p>Upload your business documents, FAQs, web pages, and manuals so your AI Employees can provide accurate, context-aware responses using Retrieval-Augmented Generation (RAG).</p>
                
                <h4 className="font-semibold text-gray-900 mt-4">Source Types</h4>
                <ul className="list-disc list-inside space-y-1 text-sm">
                    <li><strong>Upload File</strong> — PDF, DOCX, TXT, or CSV files (max 20MB). The system extracts text automatically.</li>
                    <li><strong>Website URL</strong> — Paste any URL. The system scrapes the page content (title, description, and body text).</li>
                    <li><strong>Manual Text Entry</strong> — Type or paste text directly into the platform.</li>
                    <li><strong>FAQ Entry</strong> — Add question/answer style content manually.</li>
                </ul>

                <h4 className="font-semibold text-gray-900 mt-4">How It Works</h4>
                <ol className="list-decimal list-inside space-y-1 text-sm">
                    <li><strong>Create a Knowledge Base</strong> — Click "+ New" and give it a name (e.g., "Product Info", "Policies").</li>
                    <li><strong>Add Sources</strong> — Click "+ Upload" on a knowledge base, choose the source type, enter a title, and submit.</li>
                    <li><strong>Automatic Processing</strong> — The system extracts text, splits it into chunks, and indexes them for search.</li>
                    <li><strong>AI Retrieval</strong> — When a customer asks a question, the most relevant knowledge chunks are retrieved and provided to the AI as context.</li>
                </ol>

                <h4 className="font-semibold text-gray-900 mt-4">Source Statuses</h4>
                <ul className="list-disc list-inside space-y-1 text-sm">
                    <li><span className="bg-yellow-100 text-yellow-700 px-1.5 py-0.5 rounded text-xs font-medium">pending</span> — Queued for processing.</li>
                    <li><span className="bg-blue-100 text-blue-700 px-1.5 py-0.5 rounded text-xs font-medium">processing</span> — Text is being extracted and chunked.</li>
                    <li><span className="bg-green-100 text-green-700 px-1.5 py-0.5 rounded text-xs font-medium">completed</span> — Successfully processed and ready for retrieval.</li>
                    <li><span className="bg-red-100 text-red-700 px-1.5 py-0.5 rounded text-xs font-medium">failed</span> — Processing failed. The error message is shown. Delete and re-upload.</li>
                </ul>

                <h4 className="font-semibold text-gray-900 mt-4">RAG Inspector</h4>
                <p className="text-sm text-gray-600">Use the RAG Inspector on the Knowledge page to test what your AI "knows." Enter a query and see which chunks would be retrieved, along with relevance scores. If no chunks are found, try uploading more relevant documents or rephrasing your query.</p>

                <h4 className="font-semibold text-gray-900 mt-4">Managing Your Knowledge</h4>
                <ul className="list-disc list-inside space-y-1 text-sm">
                    <li><strong>Delete individual sources</strong> — Click the 🗑 icon next to any source in the Recent Sources table.</li>
                    <li><strong>Delete entire knowledge base</strong> — Click the 🗑 icon on a knowledge base card. This removes all sources, documents, chunks, and uploaded files.</li>
                    <li>Deletions are permanent and cannot be undone.</li>
                </ul>

                <h4 className="font-semibold text-gray-900 mt-4">Best Practices</h4>
                <ul className="list-disc list-inside space-y-1 text-sm">
                    <li>Use descriptive titles for sources — they appear in search results.</li>
                    <li>Break large documents into smaller, topic-focused ones for better retrieval accuracy.</li>
                    <li>Test your knowledge using the RAG Inspector before going live.</li>
                    <li>For URLs, prefer pages with actual text content over JavaScript-heavy SPAs.</li>
                </ul>
            </div>
        ),
    },
    {
        id: 'knowledge-gaps',
        title: 'Knowledge Gaps',
        icon: '🧠',
        content: (
            <div className="space-y-3">
                <p>When customers repeatedly ask questions your AI can't answer confidently, those questions are collected as "Knowledge Gaps" so you can close them with better training content.</p>
                <h4 className="font-semibold text-gray-900 mt-4">What Gets Flagged</h4>
                <ul className="list-disc list-inside space-y-1 text-sm">
                    <li>Low-confidence interpretations the AI wasn't sure about.</li>
                    <li>Conversations escalated to a human.</li>
                    <li>AI processing errors.</li>
                </ul>
                <h4 className="font-semibold text-gray-900 mt-4">Closing a Gap</h4>
                <ul className="list-disc list-inside space-y-1 text-sm">
                    <li>Open <strong>Knowledge Gaps</strong> to see the most frequent unanswered questions.</li>
                    <li>Resolve each gap — ideally by adding the answer to your Knowledge Base.</li>
                </ul>
            </div>
        ),
    },
    {
        id: 'conversations',
        title: 'Conversations & Inbox',
        icon: '💬',
        content: (
            <div className="space-y-3">
                <p>The Inbox is your central hub for monitoring and managing all customer conversations across every channel.</p>
                <h4 className="font-semibold text-gray-900 mt-4">Conversation States</h4>
                <ul className="list-disc list-inside space-y-1 text-sm">
                    <li><strong>AI Handling</strong> — The AI is actively managing the conversation.</li>
                    <li><strong>Human Required</strong> — The AI has escalated to a human team member.</li>
                    <li><strong>Assigned</strong> — A specific team member is handling it.</li>
                    <li><strong>Resolved</strong> — The conversation has been successfully completed.</li>
                    <li><strong>Closed</strong> — The conversation is archived.</li>
                </ul>
                <h4 className="font-semibold text-gray-900 mt-4">Inbox Features</h4>
                <ul className="list-disc list-inside space-y-1 text-sm">
                    <li>View all conversations across channels in one place.</li>
                    <li>Reply to customers directly from the inbox.</li>
                    <li>Assign conversations to specific team members.</li>
                    <li>Resolve or close conversations when finished.</li>
                </ul>
            </div>
        ),
    },
    {
        id: 'approvals',
        title: 'Approvals',
        icon: '✅',
        content: (
            <div className="space-y-3">
                <p>When your AI Employee attempts a sensitive action (payments, cancellations, invoice generation, or discounts over your configured limit), it pauses for human approval instead of executing automatically.</p>
                <h4 className="font-semibold text-gray-900 mt-4">How It Works</h4>
                <ul className="list-disc list-inside space-y-1 text-sm">
                    <li>AI proposes an action → it appears in <strong>Approvals</strong> as "pending".</li>
                    <li>Approve to execute, or deny to cancel it.</li>
                    <li>Every action is logged with input and output for review.</li>
                </ul>
                <h4 className="font-semibold text-gray-900 mt-4">Financial Guardrails</h4>
                <ul className="list-disc list-inside space-y-1 text-sm">
                    <li>Invoices and payments always require approval.</li>
                    <li>Quotations with discounts above your limit require approval.</li>
                    <li>Set limits in AI Employee escalation rules or organization policies.</li>
                </ul>
            </div>
        ),
    },
    {
        id: 'customers-leads',
        title: 'Customers & Leads',
        icon: '👥',
        content: (
            <div className="space-y-3">
                <p>Manage your customer relationships and sales pipeline directly from the platform.</p>
                <h4 className="font-semibold text-gray-900 mt-4">Customers</h4>
                <ul className="list-disc list-inside space-y-1 text-sm">
                    <li>Automatically created when a new person contacts your AI.</li>
                    <li>View customer details, conversation history, and associated leads/orders.</li>
                    <li>Update customer information as needed.</li>
                </ul>
                <h4 className="font-semibold text-gray-900 mt-4">Leads</h4>
                <ul className="list-disc list-inside space-y-1 text-sm">
                    <li>Leads are automatically generated from AI conversations.</li>
                    <li>5-stage pipeline: New → Contacted → Qualified → Proposal → Won.</li>
                    <li>AI automatically scores leads based on engagement and signals.</li>
                    <li>Track conversion rates and pipeline health on the Dashboard.</li>
                </ul>
            </div>
        ),
    },
    {
        id: 'commerce',
        title: 'Products & Orders',
        icon: '🛒',
        content: (
            <div className="space-y-3">
                <p>Manage your product catalog and process orders — all handled by your AI Employees.</p>
                <h4 className="font-semibold text-gray-900 mt-4">Products</h4>
                <ul className="list-disc list-inside space-y-1 text-sm">
                    <li>Add products with name, description, SKU, price, and categories.</li>
                    <li>Create product variants (size, color, etc.).</li>
                    <li>Track inventory levels.</li>
                    <li>AI Employees can search products, check prices, and check stock.</li>
                </ul>
                <h4 className="font-semibold text-gray-900 mt-4">Orders</h4>
                <ul className="list-disc list-inside space-y-1 text-sm">
                    <li>Orders created by AI Employees on behalf of customers.</li>
                    <li>Track order status: pending → confirmed → processing → shipped → delivered.</li>
                    <li>View order history and details.</li>
                </ul>
            </div>
        ),
    },
    {
        id: 'appointments',
        title: 'Appointments',
        icon: '📅',
        content: (
            <div className="space-y-3">
                <p>Let your AI Employees schedule and manage appointments with customers.</p>
                <h4 className="font-semibold text-gray-900 mt-4">Setup Steps</h4>
                <ol className="list-decimal list-inside space-y-1 text-sm">
                    <li>Go to <strong>Appointments</strong> → set up your <strong>Availability</strong> (business days & hours).</li>
                    <li>Define your <strong>Services</strong> (name, duration, price).</li>
                    <li>Create an <strong>AI Receptionist</strong> from the template — it will use these settings.</li>
                </ol>
                <h4 className="font-semibold text-gray-900 mt-4">Features</h4>
                <ul className="list-disc list-inside space-y-1 text-sm">
                    <li>AI Employees can check available time slots.</li>
                    <li>Schedule, reschedule, and cancel appointments automatically.</li>
                    <li>View all appointments in the calendar view.</li>
                </ul>
            </div>
        ),
    },
    {
        id: 'automations',
        title: 'Automations',
        icon: '⚡',
        content: (
            <div className="space-y-3">
                <p>Set up automated workflows that trigger when specific events happen.</p>
                <h4 className="font-semibold text-gray-900 mt-4">Trigger Types</h4>
                <ul className="list-disc list-inside space-y-1 text-sm">
                    <li><strong>New Lead</strong> — When a lead is created.</li>
                    <li><strong>New Message</strong> — When a customer sends a message.</li>
                    <li><strong>Order Created</strong> — When a new order is placed.</li>
                </ul>
                <h4 className="font-semibold text-gray-900 mt-4">Available Actions</h4>
                <ul className="list-disc list-inside space-y-1 text-sm">
                    <li>Send a message in the conversation.</li>
                    <li>Create a task for your team.</li>
                    <li>Notify a specific user.</li>
                    <li>Update a lead's stage.</li>
                    <li>Assign a conversation to a team member.</li>
                    <li>Call an external webhook.</li>
                    <li>Send an email notification.</li>
                </ul>
            </div>
        ),
    },
    {
        id: 'dashboard',
        title: 'Dashboard & Analytics',
        icon: '📊',
        content: (
            <div className="space-y-3">
                <p>The Dashboard gives you a real-time overview of your business performance.</p>
                <h4 className="font-semibold text-gray-900 mt-4">Key Metrics</h4>
                <ul className="list-disc list-inside space-y-1 text-sm">
                    <li><strong>Customers</strong> — Total and new customers.</li>
                    <li><strong>Conversations</strong> — Volume, open, and resolved.</li>
                    <li><strong>Leads</strong> — Pipeline status and conversion rate.</li>
                    <li><strong>Revenue</strong> — Total and monthly breakdown.</li>
                    <li><strong>AI Employees</strong> — Performance per AI agent.</li>
                    <li><strong>Charts</strong> — 7-day conversation and revenue trends.</li>
                </ul>
            </div>
        ),
    },
    {
        id: 'reports',
        title: 'Reports',
        icon: '📈',
        content: (
            <div className="space-y-3">
                <p>Automated business performance reports are generated daily, weekly, and monthly, so you can measure what your AI employees accomplished.</p>
                <h4 className="font-semibold text-gray-900 mt-4">What's Included</h4>
                <ul className="list-disc list-inside space-y-1 text-sm">
                    <li>Conversations, inquiries, leads, and customers.</li>
                    <li>Orders and revenue (in your currency).</li>
                    <li>Escalations and AI resolution rate.</li>
                    <li>AI runs, cost, and automation runs.</li>
                    <li>Open knowledge gaps.</li>
                </ul>
                <p className="text-sm text-gray-500">View past reports anytime under <strong>Reports</strong>.</p>
            </div>
        ),
    },
    {
        id: 'team',
        title: 'Team Management',
        icon: '👨‍👩‍👧‍👦',
        content: (
            <div className="space-y-3">
                <p>Invite team members and manage their roles and permissions.</p>
                <h4 className="font-semibold text-gray-900 mt-4">Roles</h4>
                <ul className="list-disc list-inside space-y-1 text-sm">
                    <li><strong>Organization Owner</strong> — Full access to everything.</li>
                    <li><strong>Tenant Admin</strong> — Manage most settings and view all data.</li>
                    <li><strong>Manager</strong> — Oversee operations and team performance.</li>
                    <li><strong>Agent</strong> — Handle escalated conversations and support.</li>
                    <li><strong>Viewer</strong> — Read-only access to dashboards and reports.</li>
                </ul>
            </div>
        ),
    },
    {
        id: 'settings',
        title: 'Settings & Currency',
        icon: '⚙️',
        content: (
            <div className="space-y-3">
                <p>Configure organization-wide settings that affect how your AI employees operate and how financial data is displayed.</p>
                <h4 className="font-semibold text-gray-900 mt-4">Currency</h4>
                <ul className="list-disc list-inside space-y-1 text-sm">
                    <li>Your chosen currency is used for products, orders, quotations, invoices, and reports.</li>
                    <li>Update it anytime from <strong>Settings → Currency</strong>.</li>
                    <li>New products default to your organization's currency.</li>
                </ul>
            </div>
        ),
    },
    {
        id: 'billing',
        title: 'Billing & Plans',
        icon: '💳',
        content: (
            <div className="space-y-3">
                <p>Manage your subscription and view usage.</p>
                <div className="bg-green-50 border border-green-100 rounded-xl p-4 text-sm text-green-700">
                    <strong>Free plan:</strong> every new workspace starts on a <strong>Free</strong> plan — 1 AI Employee, limited monthly usage — so you can try Nomdal before upgrading.
                </div>
                <h4 className="font-semibold text-gray-900 mt-4">Plan Limits</h4>
                <ul className="list-disc list-inside space-y-1 text-sm">
                    <li>Maximum AI Employees you can create.</li>
                    <li>Monthly message and tool call limits.</li>
                    <li>Knowledge source and storage limits.</li>
                    <li>Upgrade anytime from the Billing page.</li>
                </ul>
            </div>
        ),
    },
    {
        id: 'wordpress-plugin',
        title: 'WordPress Plugin',
        icon: '🧩',
        content: (
            <div className="space-y-5">
                <p className="text-gray-700">Connect your WordPress site to your Nomdal AI Employee with the official <strong>Nomdal Connect</strong> plugin. It syncs contact-form leads and WooCommerce orders straight into Nomdal.</p>

                <div className="bg-primary-50 border border-primary-100 rounded-xl p-5">
                    <h3 className="font-bold text-primary-800 text-lg mb-3">📥 Install & Connect</h3>
                    <ol className="list-decimal list-inside space-y-3 text-sm text-gray-700">
                        <li><strong>Get the plugin</strong> → Open <code className="bg-white px-1 rounded text-xs">Plugins</code> in the sidebar, find <strong>Nomdal Connect</strong>, and click <strong>Install</strong>. Enter your site URL to generate your credentials.</li>
                        <li><strong>Copy your credentials</strong> → Save the <strong>API key</strong> and <strong>signing secret</strong> (shown only once — store them somewhere safe).</li>
                        <li><strong>Download the .zip</strong> → Click <strong>Download .zip</strong> to get the plugin package.</li>
                        <li><strong>Install in WordPress</strong> → In your WordPress admin, go to <strong>Plugins → Add New → Upload Plugin</strong>, upload the .zip, and click <strong>Activate</strong>.</li>
                        <li><strong>Configure</strong> → Go to <strong>Settings → Nomdal</strong> and paste your Base URL, API key, and signing secret. Click <strong>Test Connection</strong>.</li>
                    </ol>
                </div>

                <h3 className="font-bold text-gray-900 text-lg">✨ What it does</h3>
                <div className="space-y-2 text-sm text-gray-700">
                    <CheckItem text="Contact Form 7 submissions are sent to Nomdal as new leads." />
                    <CheckItem text="WooCommerce orders are synced as orders (with line items), and customers are matched or created automatically." />
                    <CheckItem text="A heartbeat keeps the platform updated on when your site last connected." />
                    <CheckItem text="All requests are authenticated with your API key and signed with HMAC-SHA256 for security." />
                </div>

                <div className="bg-amber-50 border border-amber-100 rounded-xl p-4">
                    <p className="text-sm text-amber-800">💡 <strong>Tip:</strong> Your API key and signing secret are shown only once. If you lose them, go to <strong>Plugins → Installed Plugins</strong> and click <strong>Regenerate key</strong> to get new ones.</p>
                </div>

                <h3 className="font-bold text-gray-900 text-lg">🛠️ Manage your installs</h3>
                <ul className="list-disc list-inside space-y-1 text-sm text-gray-700">
                    <li><strong>Installed Plugins</strong> — view every site you've connected, its status, and last-seen time.</li>
                    <li><strong>Regenerate key</strong> — rotate your API key + signing secret without reinstalling.</li>
                    <li><strong>Revoke</strong> — instantly disconnect and block a site.</li>
                </ul>
            </div>
        ),
    },
    {
        id: 'sales-development-rep',
        title: 'Sales Development Rep',
        icon: '🎯',
        content: (
            <div className="space-y-5">
                <p>The <strong>Sales Development Rep (SDR)</strong> is an outbound AI Employee that finds, scores, contacts, and follows up with prospects — automatically.</p>

                <div className="bg-primary-50 border border-primary-100 rounded-xl p-5">
                    <h3 className="font-bold text-primary-800 mb-3">🎯 What it does</h3>
                    <ol className="list-decimal list-inside space-y-2 text-sm text-gray-700">
                        <li><strong>Hunt</strong> — finds prospects matching your Ideal Customer Profile (ICP).</li>
                        <li><strong>Qualify</strong> — scores every lead 1–10.</li>
                        <li><strong>Research</strong> — explains why each prospect is a good fit, with evidence.</li>
                        <li><strong>Outreach</strong> — writes and sends hyper-personalized, 2-pass AI emails.</li>
                        <li><strong>Follow-up</strong> — automatically re-touches prospects who don't reply (day 3 / 7 / 14).</li>
                        <li><strong>Alert</strong> — pings you the moment a prospect replies.</li>
                        <li><strong>Convert</strong> — turns interested replies into an opportunity + quote + invoice.</li>
                        <li><strong>Book meeting</strong> — schedules a meeting on your Google Calendar.</li>
                    </ol>
                </div>

                <div className="bg-amber-50 border border-amber-100 rounded-xl p-5">
                    <h4 className="font-bold text-amber-800 mb-2">🔎 Connect web search (recommended)</h4>
                    <p className="text-sm text-amber-700">For <strong>live</strong> prospect discovery, connect a search provider. Go to <strong>Prospecting → Search settings</strong> and use <strong>Nomdal's shared search</strong> or add your own <strong>Serper.dev</strong> / <strong>Brave Search</strong> API key. Without search, the SDR falls back to AI-generated suggestions.</p>
                </div>

                <h3 className="font-bold text-gray-900 text-lg">🗂️ Prospecting dashboard</h3>
                <p className="text-sm text-gray-600">Everything runs from the <strong>Prospecting</strong> menu:</p>
                <ul className="list-disc list-inside space-y-1 text-sm text-gray-700">
                    <li><strong>Campaigns</strong> — define who to target (ICP + offer), then run Hunt / Qualify / Research / Outreach / Follow up.</li>
                    <li><strong>Prospects</strong> — review ranked prospects, see why they're a good fit, and act on each one.</li>
                    <li><strong>Buyer personas</strong> — define the decision-maker you're targeting (generate with AI or build manually) to sharpen hunt, scoring and outreach copy.</li>
                    <li><strong>Search settings</strong> — use Nomdal's shared search or connect your own web-search API key.</li>
                    <li><strong>Suppression list</strong> — manage do-not-contact entries.</li>
                </ul>

                <h3 className="font-bold text-gray-900 text-lg">🚀 Get started</h3>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <StepCard number="1" title="Create the SDR" desc="AI Employees → Create → choose the 'Sales Development Rep' template (or enable its tools on a custom employee)." />
                    <StepCard number="2" title="Activate & connect" desc="Turn it on and connect a channel (Web Chat, WhatsApp, or email)." />
                    <StepCard number="3" title="Give it a goal" desc="Message it: 'Hunt for UK SaaS companies with 11–50 employees and pitch our product.'" />
                </div>

                <h3 className="font-bold text-gray-900 text-lg">🧠 Defining your Ideal Customer Profile</h3>
                <p className="text-sm text-gray-600">When you ask it to hunt, include the ICP so it targets the right people:</p>
                <ul className="list-disc list-inside space-y-1 text-sm text-gray-700">
                    <li><strong>Industry</strong> — e.g. SaaS, Fintech, Healthcare</li>
                    <li><strong>Company size</strong> — e.g. 11–50, 51–200</li>
                    <li><strong>Geography</strong> — e.g. USA, UK, Remote</li>
                    <li><strong>Job titles</strong> — e.g. CEO, Founder, VP Sales</li>
                    <li><strong>Keywords / budget / pain points</strong> — anything that defines a good fit</li>
                    <li><strong>Offer</strong> — what you're pitching</li>
                </ul>

                <h3 className="font-bold text-gray-900 text-lg">⚙️ How it runs</h3>
                <ul className="list-disc list-inside space-y-1 text-sm text-gray-700">
                    <li><strong>Hunt</strong> — uses live web search (when a search key is configured) plus DeepSeek to generate matching prospects.</li>
                    <li><strong>Qualify</strong> — scores each prospect 1–10; 7+ is treated as qualified.</li>
                    <li><strong>Research</strong> — looks at the prospect's online presence and explains why they're a good fit.</li>
                    <li><strong>Outreach</strong> — drafts, then refines, a personalized email (the 2-pass flow), sent only to qualified prospects.</li>
                    <li><strong>Follow-up</strong> — automatically re-touches prospects who haven't replied (day 3 / 7 / 14, up to 3 times).</li>
                    <li><strong>Alert</strong> — the instant a prospect replies, you're notified via Telegram/email.</li>
                </ul>

                <h3 className="font-bold text-gray-900 text-lg">💰 From reply to revenue</h3>
                <p className="text-sm text-gray-600">When a prospect replies and is interested, Nomdal can close the loop automatically:</p>
                <ul className="list-disc list-inside space-y-1 text-sm text-gray-700">
                    <li><strong>Opportunity</strong> — a CRM lead is created (stage "Qualified", with an estimated value).</li>
                    <li><strong>Quote</strong> — a proposal is generated at your service price (works for services, not just products).</li>
                    <li><strong>Invoice</strong> — an invoice is created for the proposal.</li>
                    <li><strong>Meeting</strong> — book a meeting on your Google Calendar (connect it under Integrations).</li>
                </ul>
                <p className="text-sm text-gray-600">You can also do these manually from a prospect's detail page (<strong>Convert to opportunity</strong> and <strong>Book meeting</strong>).</p>

                <div className="bg-yellow-50 border border-yellow-100 rounded-xl p-5">
                    <h4 className="font-bold text-yellow-800 mb-2">📅 On-demand vs scheduled</h4>
                    <p className="text-sm text-yellow-700">
                        <strong>On-demand:</strong> message the SDR whenever you want a hunt.<br />
                        <strong>Scheduled:</strong> active campaigns run automatically. Keep a campaign active to keep it going.
                    </p>
                </div>

                <p className="text-sm text-gray-600">Every send is protected by the compliance engine — see the <strong>Compliance & Deliverability</strong> section.</p>
            </div>
        ),
    },
    {
        id: 'compliance',
        title: 'Compliance & Deliverability',
        icon: '🛡️',
        content: (
            <div className="space-y-5">
                <p>Outbound prospecting is regulated. Nomdal enforces compliance <strong>automatically</strong> before any email is sent — you don't need to memorize the rules.</p>

                <h3 className="font-bold text-gray-900 text-lg">✅ Checks that run before every send</h3>
                <ul className="list-disc list-inside space-y-1 text-sm text-gray-700">
                    <li><strong>Do-not-contact list</strong> — unsubscribed, bounced, or complained addresses are never contacted again.</li>
                    <li><strong>Contact validation</strong> — syntax, domain (MX), disposable-domain, and role-account checks.</li>
                    <li><strong>Sourcing rules</strong> — blocked sources/regions are skipped.</li>
                    <li><strong>Data-subject basis</strong> — individuals/sole traders need a lawful basis (UK/GDPR); corporate B2B is handled.</li>
                    <li><strong>Identity</strong> — a real sender email and physical postal address must be present (CAN-SPAM).</li>
                    <li><strong>Opt-out link</strong> — every email includes a working unsubscribe link + header.</li>
                    <li><strong>Rate limit</strong> — a per-hour cap prevents blast sending.</li>
                </ul>

                <h3 className="font-bold text-gray-900 text-lg">🔁 Unsubscribe & bounce handling</h3>
                <ul className="list-disc list-inside space-y-1 text-sm text-gray-700">
                    <li>One-click <strong>unsubscribe</strong> instantly adds the address to the do-not-contact list.</li>
                    <li>Reply keywords like <em>"unsubscribe"</em> or <em>"opt out"</em> are honored automatically.</li>
                    <li>Hard bounces and spam complaints auto-suppress the address via webhook.</li>
                </ul>

                <h3 className="font-bold text-gray-900 text-lg">📬 Deliverability best practices</h3>
                <div className="bg-blue-50 border border-blue-100 rounded-xl p-5 text-sm text-blue-700">
                    <ul className="list-disc list-inside space-y-1">
                        <li>Send from a <strong>dedicated domain/subdomain</strong> (e.g. outreach.yourcompany.com).</li>
                        <li>Configure <strong>SPF, DKIM, and DMARC</strong> on that domain.</li>
                        <li>Set a valid <strong>physical postal address</strong> (Settings → Prospecting, or per campaign).</li>
                        <li>Start with low volume and warm up gradually.</li>
                    </ul>
                </div>

                <div className="bg-yellow-50 border border-yellow-100 rounded-xl p-5 text-sm text-yellow-700">
                    <strong>Note:</strong> AI-generated contacts do not require approval by default. You can require human approval per campaign (or globally) for extra control over risky contacts.
                </div>
            </div>
        ),
    },
    {
        id: 'faq',
        title: 'FAQ',
        icon: '❓',
        content: (
            <div className="space-y-4">
                <div>
                    <h4 className="font-semibold text-gray-900">Can I have multiple AI Employees?</h4>
                    <p className="text-sm text-gray-600 mt-1">Yes! You can create as many AI Employees as your plan allows. Each can have a different role, personality, and tool set.</p>
                </div>
                <div>
                    <h4 className="font-semibold text-gray-900">How do I embed the chat widget on my website?</h4>
                    <p className="text-sm text-gray-600 mt-1">Go to AI Employees → click <strong>Channels</strong> on any employee → toggle Web Chat ON → copy the snippet → paste before <code className="bg-gray-100 px-1 rounded text-xs">{'</body>'}</code> on your site.</p>
                </div>
                <div>
                    <h4 className="font-semibold text-gray-900">What happens when the AI can't answer?</h4>
                    <p className="text-sm text-gray-600 mt-1">The AI will automatically escalate the conversation to a human team member. You'll see these in your Inbox marked as "Human Required".</p>
                </div>
                <div>
                    <h4 className="font-semibold text-gray-900">How do I train my AI to be more accurate?</h4>
                    <p className="text-sm text-gray-600 mt-1">Upload relevant documents to the Knowledge Base (product info, FAQs, policies). The more quality content you provide, the better your AI will perform.</p>
                </div>
                <div>
                    <h4 className="font-semibold text-gray-900">Is customer data secure?</h4>
                    <p className="text-sm text-gray-600 mt-1">Yes. Each organization's data is completely isolated. Your customers' information is never shared across organizations.</p>
                </div>
                <div>
                    <h4 className="font-semibold text-gray-900">Can I switch between multiple organizations?</h4>
                    <p className="text-sm text-gray-600 mt-1">If you belong to multiple organizations, you'll see an organization switcher in the top navigation bar. Click it to switch contexts.</p>
                </div>
                <div>
                    <h4 className="font-semibold text-gray-900">Does the Sales Development Rep search the web 24/7?</h4>
                    <p className="text-sm text-gray-600 mt-1">No — it hunts on-demand when you message it, and on a schedule (active campaigns run automatically). It's a batch hunt, not a continuous crawler.</p>
                </div>
                <div>
                    <h4 className="font-semibold text-gray-900">How does outbound email stay compliant?</h4>
                    <p className="text-sm text-gray-600 mt-1">Every send passes an automatic compliance gate: do-not-contact list, email validation, sender identity + physical address, opt-out link, and rate limits. See the Compliance & Deliverability section.</p>
                </div>
                <div>
                    <h4 className="font-semibold text-gray-900">How do I get live web search for my hunts?</h4>
                    <p className="text-sm text-gray-600 mt-1">Go to <strong>Prospecting → Search settings</strong> and use Nomdal's shared search, or bring your own Serper.dev / Brave Search API key. Without search, Nomdal uses AI-generated prospect suggestions instead.</p>
                </div>
                <div>
                    <h4 className="font-semibold text-gray-900">Can the SDR book meetings on my calendar?</h4>
                    <p className="text-sm text-gray-600 mt-1">Yes — connect Google Calendar under <strong>Integrations</strong>, then use <strong>Book meeting</strong> on a prospect (or let the SDR call it when a prospect wants to meet).</p>
                </div>
            </div>
        ),
    },
];

function StepCard({ number, title, desc }) {
    return (
        <div className="bg-white rounded-lg border border-gray-200 p-4">
            <div className="w-7 h-7 rounded-full bg-primary-600 text-white flex items-center justify-center text-xs font-bold mb-2">{number}</div>
            <h4 className="font-semibold text-gray-900 text-sm mb-1">{title}</h4>
            <p className="text-xs text-gray-500">{desc}</p>
        </div>
    );
}

function CheckItem({ text }) {
    return (
        <div className="flex items-start gap-2">
            <svg className="w-4 h-4 mt-0.5 shrink-0 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
            </svg>
            <span>{text}</span>
        </div>
    );
}

export default function HelpIndex() {
    const [activeSection, setActiveSection] = useState('getting-started');

    const current = sections.find(s => s.id === activeSection);

    return (
        <TenantLayout header="Help & Documentation">
            <Head title="Help & Documentation" />

            <div className="flex gap-6">
                <div className="w-56 shrink-0 hidden lg:block">
                    <div className="sticky top-20 bg-white rounded-xl border border-gray-200 p-3">
                        <p className="text-xs font-semibold text-gray-400 uppercase tracking-wider px-2 mb-2">Topics</p>
                        <nav className="space-y-0.5">
                            {sections.map((section) => (
                                <button
                                    key={section.id}
                                    onClick={() => setActiveSection(section.id)}
                                    className={`w-full text-left px-3 py-2 rounded-lg text-sm transition-colors ${
                                        activeSection === section.id
                                            ? 'bg-primary-50 text-primary-700 font-medium'
                                            : 'text-gray-600 hover:bg-gray-50'
                                    }`}
                                >
                                    <span className="mr-2">{section.icon}</span>
                                    {section.title}
                                </button>
                            ))}
                        </nav>
                    </div>
                </div>

                <div className="flex-1 min-w-0">
                    <div className="lg:hidden mb-4">
                        <select
                            value={activeSection}
                            onChange={(e) => setActiveSection(e.target.value)}
                            className="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none"
                        >
                            {sections.map((section) => (
                                <option key={section.id} value={section.id}>
                                    {section.icon} {section.title}
                                </option>
                            ))}
                        </select>
                    </div>

                    {current && (
                        <div className="bg-white rounded-xl border border-gray-200 p-6 lg:p-8">
                            <h1 className="text-2xl font-bold text-gray-900 mb-1">
                                <span className="mr-3">{current.icon}</span>
                                {current.title}
                            </h1>
                            <div className="w-20 h-1 bg-primary-500 rounded-full mt-3 mb-6" />
                            <div className="prose prose-sm max-w-none text-gray-700">
                                {current.content}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </TenantLayout>
    );
}