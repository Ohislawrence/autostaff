import { usePage } from '@inertiajs/react';
import PlatformLayout from '@/Layouts/PlatformLayout';

export default function IntegrationDocs() {
    const { auth } = usePage().props;

    return (
        <PlatformLayout title="Integration Documentation">
            <h1 className="text-2xl font-bold text-gray-900 mb-2">Integration Documentation</h1>
            <p className="text-sm text-gray-500 mb-8">Step-by-step guides for integrating AI Employee into popular platforms and CMS systems.</p>

            {/* Table of Contents */}
            <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
                <h2 className="font-semibold text-gray-900 mb-3">Quick Navigation</h2>
                <div className="grid grid-cols-3 gap-2 text-sm">
                    {[
                        { href: '#web-chat', label: '1. Website Chat Widget', icon: '💬' },
                        { href: '#wordpress', label: '2. WordPress Integration', icon: '🔷' },
                        { href: '#shopify', label: '3. Shopify Integration', icon: '🛍️' },
                        { href: '#whatsapp', label: '4. WhatsApp Integration', icon: '📱' },
                        { href: '#rest-api', label: '5. REST API', icon: '🔌' },
                        { href: '#webhook', label: '6. Custom Webhooks', icon: '🔄' },
                        { href: '#mcp', label: '7. MCP External Tools', icon: '🤖' },
                    ].map(item => (
                        <a key={item.href} href={item.href} className="flex items-center gap-2 px-3 py-2 rounded-lg bg-gray-50 hover:bg-purple-50 text-gray-700 hover:text-purple-700">
                            <span>{item.icon}</span> {item.label}
                        </a>
                    ))}
                </div>
            </div>

            {/* Section 1: Website Chat Widget */}
            <section id="web-chat" className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <h2 className="text-xl font-bold text-gray-900 mb-2">💬 1. Website Chat Widget</h2>
                <p className="text-sm text-gray-600 mb-4">The fastest way to deploy your AI Employee. Add a single script tag to any website.</p>

                <pre className="bg-gray-900 text-green-400 p-4 rounded-lg text-sm font-mono mb-3 overflow-x-auto whitespace-pre-wrap">
{`<script src="{YOUR_DOMAIN}/widget/chat-widget.js"
  data-employee="{AI_EMPLOYEE_UUID}"
  data-greeting="👋 Hi! How can we help you today?"
  data-color="#4F46E5"
  data-position="bottom-right">
</script>`}
                </pre>

                <h3 className="font-semibold text-gray-900 mt-4 mb-2">Configuration Options</h3>
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead className="bg-gray-50"><tr><th className="text-left px-3 py-2 font-medium text-gray-500">Attribute</th><th className="text-left px-3 py-2 font-medium text-gray-500">Required</th><th className="text-left px-3 py-2 font-medium text-gray-500">Description</th></tr></thead>
                        <tbody className="divide-y divide-gray-100">
                            <tr><td className="px-3 py-2 font-mono text-xs">data-employee</td><td className="px-3 py-2"><span className="text-xs text-red-600">Yes</span></td><td className="px-3 py-2 text-xs text-gray-600">UUID of your AI Employee (found on AI Employees page)</td></tr>
                            <tr><td className="px-3 py-2 font-mono text-xs">data-greeting</td><td className="px-3 py-2"><span className="text-xs text-gray-400">No</span></td><td className="px-3 py-2 text-xs text-gray-600">Welcome message. Default: "Hi! How can I help?"</td></tr>
                            <tr><td className="px-3 py-2 font-mono text-xs">data-color</td><td className="px-3 py-2"><span className="text-xs text-gray-400">No</span></td><td className="px-3 py-2 text-xs text-gray-600">Primary color (hex). Default: #4F46E5</td></tr>
                            <tr><td className="px-3 py-2 font-mono text-xs">data-position</td><td className="px-3 py-2"><span className="text-xs text-gray-400">No</span></td><td className="px-3 py-2 text-xs text-gray-600">Position: "bottom-right" or "bottom-left"</td></tr>
                            <tr><td className="px-3 py-2 font-mono text-xs">data-hours</td><td className="px-3 py-2"><span className="text-xs text-gray-400">No</span></td><td className="px-3 py-2 text-xs text-gray-600">Working hours (24hr format): "09:00-17:00"</td></tr>
                        </tbody>
                    </table>
                </div>
                <p className="text-xs text-gray-500 mt-3">📌 <strong>Security note:</strong> The employee UUID is public-safe. Never expose API keys or secrets in browser JavaScript.</p>
            </section>

            {/* Section 2: WordPress */}
            <section id="wordpress" className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <h2 className="text-xl font-bold text-gray-900 mb-2">🔷 2. WordPress Integration</h2>
                <p className="text-sm text-gray-600 mb-4">Add your AI Employee to any WordPress site in under 2 minutes.</p>

                <h3 className="font-semibold text-gray-900 mt-4 mb-2">Method A: Using a Plugin (Recommended)</h3>
                <ol className="list-decimal list-inside text-sm text-gray-600 space-y-1 mb-4">
                    <li>Install the "Insert Headers and Footers" plugin by WPBeginner</li>
                    <li>Go to <strong>Settings → Insert Headers and Footers</strong></li>
                    <li>Paste the chat widget script into the <strong>"Scripts in Footer"</strong> box</li>
                    <li>Click <strong>Save</strong></li>
                    <li>That's it. The chat widget appears on every page.</li>
                </ol>

                <h3 className="font-semibold text-gray-900 mt-4 mb-2">Method B: Theme File Edit</h3>
                <ol className="list-decimal list-inside text-sm text-gray-600 space-y-1 mb-4">
                    <li>Go to <strong>Appearance → Theme File Editor</strong></li>
                    <li>Select <code className="bg-gray-100 px-1 rounded text-xs">footer.php</code></li>
                    <li>Paste the chat widget script just before the closing body tag</li>
                    <li>Click <strong>Update File</strong></li>
                </ol>

                <h3 className="font-semibold text-gray-900 mt-4 mb-2">Method C: WooCommerce Integration</h3>
                <p className="text-sm text-gray-600 mb-2">For product-aware AI (searches your WooCommerce catalogue):</p>
                <ol className="list-decimal list-inside text-sm text-gray-600 space-y-1">
                    <li>Enable WooCommerce REST API: <strong>WooCommerce → Settings → Advanced → REST API</strong></li>
                    <li>Create an API key with <strong>Read</strong> access</li>
                    <li>Configure the AI Employee's tools to include the WooCommerce product search</li>
                    <li>Add the chat widget script to your theme as described above</li>
                </ol>
                <pre className="bg-gray-900 text-green-400 p-3 rounded-lg text-xs font-mono mt-2">
{`GET https://yourstore.com/wp-json/wc/v3/products
Auth: Basic (Consumer Key:Consumer Secret)`}
                </pre>
            </section>

            {/* Section 3: Shopify */}
            <section id="shopify" className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <h2 className="text-xl font-bold text-gray-900 mb-2">🛍️ 3. Shopify Integration</h2>
                <p className="text-sm text-gray-600 mb-4">Integrate AI Employee with your Shopify store for product-aware customer support.</p>

                <h3 className="font-semibold text-gray-900 mt-4 mb-2">Add Chat Widget to Shopify</h3>
                <ol className="list-decimal list-inside text-sm text-gray-600 space-y-1 mb-4">
                    <li>Go to <strong>Online Store → Themes → Customize</strong></li>
                    <li>Click <strong>Theme Settings</strong> (or Edit Code for older themes)</li>
                    <li>Navigate to <code className="bg-gray-100 px-1 rounded text-xs">theme.liquid</code></li>
                    <li>Paste the chat widget script just before the closing body tag</li>
                    <li>Click <strong>Save</strong></li>
                </ol>

                <h3 className="font-semibold text-gray-900 mt-4 mb-2">Product Search Integration</h3>
                <ol className="list-decimal list-inside text-sm text-gray-600 space-y-1">
                    <li>Go to <strong>Settings → Apps and sales channels → Develop apps</strong></li>
                    <li>Create a new app with <strong>Read product listings</strong> permission</li>
                    <li>Get your Admin API access token</li>
                    <li>Configure the AI Employee's tools to include the Shopify product search</li>
                </ol>
                <pre className="bg-gray-900 text-green-400 p-3 rounded-lg text-xs font-mono mt-2">
{`GET https://{store}.myshopify.com/admin/api/2024-01/products.json
Header: X-Shopify-Access-Token: {token}`}
                </pre>
            </section>

            {/* Section 4: WhatsApp */}
            <section id="whatsapp" className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <h2 className="text-xl font-bold text-gray-900 mb-2">📱 4. WhatsApp Integration</h2>
                <p className="text-sm text-gray-600 mb-4">Connect your AI Employee to WhatsApp Business API.</p>

                <h3 className="font-semibold text-gray-900 mt-4 mb-2">Setup Steps</h3>
                <ol className="list-decimal list-inside text-sm text-gray-600 space-y-1 mb-4">
                    <li>Create a Meta Business account at <strong>business.facebook.com</strong></li>
                    <li>Create a WhatsApp Business App in the Meta Developer Portal</li>
                    <li>Get your <strong>Phone Number ID</strong> and <strong>Access Token</strong></li>
                    <li>Set the Webhook URL in Meta to your platform's webhook endpoint</li>
                    <li>Verify the webhook with your WHATSAPP_VERIFY_TOKEN</li>
                    <li>Subscribe to the <strong>messages</strong> webhook event</li>
                </ol>
                <pre className="bg-gray-900 text-green-400 p-3 rounded-lg text-xs font-mono mt-2">
{`Webhook URL: https://nomdal.com/api/v1/webhooks/whatsapp
Verify Token: {WHATSAPP_VERIFY_TOKEN}`}
                </pre>
                <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-sm text-yellow-700 mt-4">
                    ⚠️ <strong>Note:</strong> WhatsApp requires a verified Meta Business account. This process can take 2-5 business days.
                </div>
            </section>

            {/* Section 5: REST API */}
            <section id="rest-api" className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <h2 className="text-xl font-bold text-gray-900 mb-2">🔌 5. REST API Integration</h2>
                <p className="text-sm text-gray-600 mb-4">Use the REST API for custom integrations with any platform.</p>

                <h3 className="font-semibold text-gray-900 mt-4 mb-2">Authentication</h3>
                <p className="text-sm text-gray-600 mb-2">Generate an API key in the tenant dashboard under <strong>Settings → API Keys</strong>.</p>
                <pre className="bg-gray-900 text-green-400 p-3 rounded-lg text-xs font-mono mb-4">
{`Authorization: Bearer {API_KEY}`}
                </pre>

                <h3 className="font-semibold text-gray-900 mt-4 mb-2">Key Endpoints</h3>
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead className="bg-gray-50"><tr><th className="text-left px-3 py-2 font-medium text-gray-500">Method</th><th className="text-left px-3 py-2 font-medium text-gray-500">Endpoint</th><th className="text-left px-3 py-2 font-medium text-gray-500">Description</th></tr></thead>
                        <tbody className="divide-y divide-gray-100">
                            <tr><td className="px-3 py-2"><span className="px-2 py-0.5 bg-gray-100 text-gray-700 rounded text-xs font-bold">GET</span></td><td className="px-3 py-2 font-mono text-xs">/api/health</td><td className="px-3 py-2 text-xs text-gray-600">Health check (public, no auth)</td></tr>
                            <tr><td className="px-3 py-2"><span className="px-2 py-0.5 bg-green-100 text-green-700 rounded text-xs font-bold">POST</span></td><td className="px-3 py-2 font-mono text-xs">/api/v1/messages</td><td className="px-3 py-2 text-xs text-gray-600">Send message to AI Employee</td></tr>
                            <tr><td className="px-3 py-2"><span className="px-2 py-0.5 bg-blue-100 text-blue-700 rounded text-xs font-bold">GET</span></td><td className="px-3 py-2 font-mono text-xs">/api/v1/conversations</td><td className="px-3 py-2 text-xs text-gray-600">List conversations</td></tr>
                            <tr><td className="px-3 py-2"><span className="px-2 py-0.5 bg-blue-100 text-blue-700 rounded text-xs font-bold">GET</span></td><td className="px-3 py-2 font-mono text-xs">/api/v1/conversations/&#123;id&#125;</td><td className="px-3 py-2 text-xs text-gray-600">Get conversation details</td></tr>
                            <tr><td className="px-3 py-2"><span className="px-2 py-0.5 bg-blue-100 text-blue-700 rounded text-xs font-bold">GET</span></td><td className="px-3 py-2 font-mono text-xs">/api/v1/customers</td><td className="px-3 py-2 text-xs text-gray-600">List/search customers</td></tr>
                            <tr><td className="px-3 py-2"><span className="px-2 py-0.5 bg-green-100 text-green-700 rounded text-xs font-bold">POST</span></td><td className="px-3 py-2 font-mono text-xs">/api/v1/customers</td><td className="px-3 py-2 text-xs text-gray-600">Create a customer</td></tr>
                            <tr><td className="px-3 py-2"><span className="px-2 py-0.5 bg-blue-100 text-blue-700 rounded text-xs font-bold">GET</span></td><td className="px-3 py-2 font-mono text-xs">/api/v1/leads</td><td className="px-3 py-2 text-xs text-gray-600">List/search leads</td></tr>
                            <tr><td className="px-3 py-2"><span className="px-2 py-0.5 bg-green-100 text-green-700 rounded text-xs font-bold">POST</span></td><td className="px-3 py-2 font-mono text-xs">/api/v1/leads</td><td className="px-3 py-2 text-xs text-gray-600">Create a lead</td></tr>
                            <tr><td className="px-3 py-2"><span className="px-2 py-0.5 bg-blue-100 text-blue-700 rounded text-xs font-bold">GET</span></td><td className="px-3 py-2 font-mono text-xs">/api/v1/orders</td><td className="px-3 py-2 text-xs text-gray-600">List/search orders</td></tr>
                            <tr><td className="px-3 py-2"><span className="px-2 py-0.5 bg-green-100 text-green-700 rounded text-xs font-bold">POST</span></td><td className="px-3 py-2 font-mono text-xs">/api/v1/orders</td><td className="px-3 py-2 text-xs text-gray-600">Create an order</td></tr>
                            <tr><td className="px-3 py-2"><span className="px-2 py-0.5 bg-blue-100 text-blue-700 rounded text-xs font-bold">GET</span></td><td className="px-3 py-2 font-mono text-xs">/api/v1/orders/&#123;id&#125;</td><td className="px-3 py-2 text-xs text-gray-600">Get order details</td></tr>
                            <tr><td className="px-3 py-2"><span className="px-2 py-0.5 bg-blue-100 text-blue-700 rounded text-xs font-bold">GET</span></td><td className="px-3 py-2 font-mono text-xs">/api/v1/ai-employees</td><td className="px-3 py-2 text-xs text-gray-600">List AI Employees</td></tr>
                            <tr><td className="px-3 py-2"><span className="px-2 py-0.5 bg-blue-100 text-blue-700 rounded text-xs font-bold">GET</span></td><td className="px-3 py-2 font-mono text-xs">/api/v1/me</td><td className="px-3 py-2 text-xs text-gray-600">Get authenticated user profile</td></tr>
                        </tbody>
                    </table>
                </div>

                <h3 className="font-semibold text-gray-900 mt-4 mb-2">Example: Creating a Lead</h3>
                <pre className="bg-gray-900 text-green-400 p-4 rounded-lg text-xs font-mono whitespace-pre-wrap">
{`curl -X POST https://nomdal.com/api/v1/leads \\
  -H "Authorization: Bearer {API_KEY}" \\
  -H "Content-Type: application/json" \\
  -d '{
    "customer_id": 123,
    "stage": "new",
    "product_interest": "Widget Pro",
    "estimated_value": 1500,
    "notes": "Requested demo on pricing page"
  }'`}
                </pre>

                <h3 className="font-semibold text-gray-900 mt-4 mb-2">Example: Sending a Message</h3>
                <pre className="bg-gray-900 text-green-400 p-4 rounded-lg text-xs font-mono whitespace-pre-wrap">
{`curl -X POST https://nomdal.com/api/v1/messages \\
  -H "Authorization: Bearer {API_KEY}" \\
  -H "Content-Type: application/json" \\
  -d '{
    "ai_employee_uuid": "{EMPLOYEE_UUID}",
    "message": "What is the price of Product X?",
    "customer_email": "customer@example.com",
    "customer_name": "John Doe"
  }'`}
                </pre>
            </section>

            {/* Section 6: Webhooks */}
            <section id="webhook" className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <h2 className="text-xl font-bold text-gray-900 mb-2">🔄 6. Custom Webhooks</h2>
                <p className="text-sm text-gray-600 mb-4">Receive real-time event notifications from your AI Employee platform.</p>

                <h3 className="font-semibold text-gray-900 mt-4 mb-2">Available Events</h3>
                <div className="flex flex-wrap gap-2 mb-4">
                    {[
                        'conversation.created', 'conversation.closed', 'message.received',
                        'lead.created', 'lead.updated', 'lead.stage_changed',
                        'order.created', 'order.updated',
                        'appointment.created', 'appointment.updated',
                        'automation.triggered', 'automation.completed', 'automation.failed',
                        'ai.escalated', 'ai.tool_failed',
                    ].map(e => (
                        <span key={e} className="px-2 py-1 bg-gray-100 rounded text-xs font-mono">{e}</span>
                    ))}
                </div>

                <h3 className="font-semibold text-gray-900 mt-4 mb-2">Webhook Payload Format</h3>
                <pre className="bg-gray-900 text-green-400 p-4 rounded-lg text-xs font-mono mb-4 whitespace-pre-wrap">
{`{
  "event": "lead.created",
  "timestamp": "2026-08-09T02:30:00+01:00",
  "organization_uuid": "{ORG_UUID}",
  "data": {
    "lead": { ... }
  },
  "signature": "{HMAC_SHA256}"
}`}
                </pre>

                <h3 className="font-semibold text-gray-900 mt-4 mb-2">Verifying Webhook Signatures</h3>
                <p className="text-sm text-gray-600 mb-2">Verify the HMAC-SHA256 signature using your webhook secret:</p>
                <pre className="bg-gray-900 text-green-400 p-4 rounded-lg text-xs font-mono whitespace-pre-wrap">
{`$payload = file_get_contents("php://input");
$signature = $_SERVER["HTTP_X_SIGNATURE"];
$expected = hash_hmac("sha256", $payload, $webhookSecret);

if (hash_equals($expected, $signature)) {
    // Process webhook — signature valid
}`}
                </pre>
            </section>

            {/* Section 7: MCP External Tools */}
            <section id="mcp" className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <h2 className="text-xl font-bold text-gray-900 mb-2">🤖 7. MCP External Tools</h2>
                <p className="text-sm text-gray-600 mb-4">
                    Connect external Model Context Protocol (MCP) servers so AI employees can call third-party tools
                    (Gmail, Google Calendar, CRM, Microsoft 365, or any custom MCP server). MCP tools flow through the
                    same permission, approval, and audit pipeline as built-in tools.
                </p>

                <h3 className="font-semibold text-gray-900 mt-4 mb-2">Connecting a provider</h3>
                <ol className="text-sm text-gray-600 list-decimal list-inside space-y-2 mb-4">
                    <li>Go to <strong>Integrations → MCP Connections</strong>.</li>
                    <li>For Google providers, click <strong>Connect Google Calendar</strong> or <strong>Connect Gmail</strong> and sign in with Google.</li>
                    <li>For other providers, enter the MCP server URL and credentials (access/refresh token) and click <strong>Connect</strong>.</li>
                    <li>The platform discovers and syncs the server's tools.</li>
                    <li>Enable the synced tools for an AI employee from the employee's Create/Edit screen (under <strong>External tools (MCP)</strong>).</li>
                </ol>

                <h3 className="font-semibold text-gray-900 mt-4 mb-2">Supported providers</h3>
                <div className="flex flex-wrap gap-2 mb-4">
                    {['google_calendar', 'gmail', 'crm', 'microsoft_365', 'custom'].map(p => (
                        <span key={p} className="px-2 py-1 bg-gray-100 rounded text-xs font-mono">{p}</span>
                    ))}
                </div>

                <h3 className="font-semibold text-gray-900 mt-4 mb-2">Built-in guardrails</h3>
                <ul className="text-sm text-gray-600 list-disc list-inside space-y-1 mb-4">
                    <li>Tenant-isolated connections with credentials encrypted at rest</li>
                    <li>Per-tenant rate limiting + circuit breaker + retry</li>
                    <li>SSRF protection (private/reserved endpoints are rejected)</li>
                    <li>Tool name/description sanitization (prompt-injection defense)</li>
                    <li>Cost &amp; audit attribution per tool execution</li>
                </ul>

                <h3 className="font-semibold text-gray-900 mt-4 mb-2">Environment configuration</h3>
                <pre className="bg-gray-900 text-green-400 p-4 rounded-lg text-xs font-mono whitespace-pre-wrap">
{`MCP_ENABLED=true
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=/integrations/mcp/oauth/callback
GOOGLE_MCP_ENDPOINT=https://mcp.googleapis.com`}
                </pre>
            </section>

            <div className="bg-purple-50 border border-purple-200 rounded-xl p-6 text-center">
                <p className="text-purple-800 font-medium">Need help with a specific integration?</p>
                <p className="text-sm text-purple-600 mt-1">Contact support or check the full API documentation for custom integration guidance.</p>
            </div>
        </PlatformLayout>
    );
}