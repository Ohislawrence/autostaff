import { Head, Link, usePage } from '@inertiajs/react';

const sidebar = [
    { section: 'PLATFORM', items: [{ name: 'Dashboard', href: '/platform', icon: '📊' },{ name: 'Organizations', href: '/platform/tenants', icon: '🏢' },{ name: 'Users', href: '/platform/users', icon: '👥' },{ name: 'Subscriptions', href: '/platform/plans', icon: '💳' },{ name: 'Plugins', href: '/platform/plugins', icon: '🧩' }] },
    { section: 'GROWTH', items: [{ name: 'Prospecting', href: '/platform/prospecting', icon: '🎯' },{ name: 'Prospecting Settings', href: '/platform/prospecting/settings', icon: '🛠️' },{ name: 'Suppression List', href: '/platform/prospecting/suppression', icon: '🚫' }] },
    { section: 'AI PLATFORM', items: [{ name: 'Providers', href: '/platform/providers', icon: '🔌' },{ name: 'Models', href: '/platform/models', icon: '🧠' },{ name: 'Templates', href: '/platform/templates', icon: '📋' },{ name: 'Tools', href: '/platform/tools', icon: '🔧' },{ name: 'Feature Flags', href: '/platform/features', icon: '🚩' }] },
    { section: 'OPERATIONS', items: [{ name: 'Usage & Costs', href: '/platform/usage', icon: '📈' },{ name: 'System Health', href: '/platform/health', icon: '❤️' },{ name: 'Queues', href: '/platform/queues', icon: '📬' },{ name: 'Failed Jobs', href: '/platform/failed-jobs', icon: '❌' },{ name: 'AI Runs', href: '/platform/ai-runs', icon: '🔍' }] },
    { section: 'SUPPORT', items: [{ name: 'Announcements', href: '/platform/announcements', icon: '📢' },{ name: 'Tickets', href: '/platform/tickets', icon: '🎫' },{ name: 'Knowledge Processing', href: '/platform/knowledge-processing', icon: '📚' },{ name: 'Integrations', href: '/platform/integrations', icon: '🔗' },{ name: 'Integration Docs', href: '/platform/integration-docs', icon: '📖' }] },
    { section: 'SYSTEM', items: [{ name: 'Settings', href: '/platform/settings', icon: '⚙️' },{ name: 'Tax & Billing', href: '/platform/tax-settings', icon: '💰' },{ name: 'Invoices', href: '/platform/invoices', icon: '🧾' }] },
];

export default function PlatformLayout({ children, title }) {
    const { auth, flash } = usePage().props;

    return (
        <div className="min-h-screen bg-gradient-to-br from-blue-50 via-white to-amber-50">
            <nav className="bg-white/80 backdrop-blur-md border-b border-blue-100 px-6 py-3 flex items-center justify-between shadow-sm">
                <span className="text-lg font-bold bg-gradient-to-r from-blue-700 to-blue-900 bg-clip-text text-transparent">🤖 AI Employee — Platform Admin</span>
                <div className="flex items-center gap-3">
                    <span className="text-sm text-gray-700">{auth.user?.name} <span className="px-2 py-0.5 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded text-xs font-medium shadow-sm">Platform Owner</span></span>
                    <form method="POST" action="/logout"><input type="hidden" name="_token" value={document.querySelector('meta[name="csrf-token"]')?.content} /><button className="text-sm text-gray-600 hover:text-blue-700 font-medium">Logout</button></form>
                </div>
            </nav>
            <div className="flex">
                <aside className="w-56 bg-white/90 backdrop-blur-md border-r border-blue-100 min-h-[calc(100vh-57px)] p-3 overflow-y-auto shadow-xl shadow-blue-100/20">
                    {sidebar.map(section => (
                        <div key={section.section} className="mb-4">
                            <p className="px-3 text-xs font-bold text-blue-400 uppercase tracking-wider mb-2">{section.section}</p>
                            {section.items.map(item => (
                                <Link key={item.name} href={item.href} className={`flex items-center gap-2 px-3 py-2 rounded-lg text-sm mb-1 transition-all ${window.location.pathname === item.href ? 'bg-gradient-to-r from-blue-600 to-blue-700 text-white font-medium shadow-md shadow-blue-200' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-700'}`}>
                                    <span>{item.icon}</span> {item.name}
                                </Link>
                            ))}
                        </div>
                    ))}
                </aside>
                <main className="flex-1 p-8">
                    <Head title={title || 'Platform'} />
                    {flash?.success && <div className="mb-4 p-4 bg-green-50 text-green-700 rounded-lg text-sm">{flash.success}</div>}
                    {flash?.error && <div className="mb-4 p-4 bg-red-50 text-red-700 rounded-lg text-sm">{flash.error}</div>}
                    {children}
                </main>
            </div>
        </div>
    );
}