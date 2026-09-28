import { Head, Link, usePage } from '@inertiajs/react';

const sidebar = [
    { section: 'PLATFORM', items: [{ name: 'Dashboard', href: '/platform', icon: '📊' },{ name: 'Organizations', href: '/platform/tenants', icon: '🏢' },{ name: 'Users', href: '/platform/users', icon: '👥' },{ name: 'Subscriptions', href: '/platform/plans', icon: '💳' },{ name: 'Plugins', href: '/platform/plugins', icon: '🧩' },{ name: 'Blog', href: '/platform/blog', icon: '📝' }] },
    { section: 'GROWTH', items: [{ name: 'Prospecting', href: '/platform/prospecting', icon: '🎯' },{ name: 'Prospecting Settings', href: '/platform/prospecting/settings', icon: '🛠️' },{ name: 'Suppression List', href: '/platform/prospecting/suppression', icon: '🚫' }] },
    { section: 'OPERATOR', items: [{ name: 'Marketing', href: '/platform/marketing', icon: '📣' },{ name: 'Daily Tasks', href: '/platform/tasks', icon: '✅' },{ name: 'Achievements', href: '/platform/achievements', icon: '🏆' },{ name: 'Goals', href: '/platform/goals', icon: '🎯' }] },
    { section: 'AI PLATFORM', items: [{ name: 'Providers', href: '/platform/providers', icon: '🔌' },{ name: 'Models', href: '/platform/models', icon: '🧠' },{ name: 'Templates', href: '/platform/templates', icon: '📋' },{ name: 'Tools', href: '/platform/tools', icon: '🔧' },{ name: 'Feature Flags', href: '/platform/features', icon: '🚩' }] },
    { section: 'OPERATIONS', items: [{ name: 'Usage & Costs', href: '/platform/usage', icon: '📈' },{ name: 'System Health', href: '/platform/health', icon: '❤️' },{ name: 'Queues', href: '/platform/queues', icon: '📬' },{ name: 'Failed Jobs', href: '/platform/failed-jobs', icon: '❌' },{ name: 'AI Runs', href: '/platform/ai-runs', icon: '🔍' }] },
    { section: 'SUPPORT', items: [{ name: 'Announcements', href: '/platform/announcements', icon: '📢' },{ name: 'Tickets', href: '/platform/tickets', icon: '🎫' },{ name: 'Knowledge Processing', href: '/platform/knowledge-processing', icon: '📚' },{ name: 'Integrations', href: '/platform/integrations', icon: '🔗' },{ name: 'Integration Docs', href: '/platform/integration-docs', icon: '📖' }] },
    { section: 'SYSTEM', items: [{ name: 'Settings', href: '/platform/settings', icon: '⚙️' },{ name: 'Tax & Billing', href: '/platform/tax-settings', icon: '💰' },{ name: 'Invoices', href: '/platform/invoices', icon: '🧾' }] },
];

export default function PlatformLayout({ children, title }) {
    const { auth, flash } = usePage().props;

    return (
        <div className="min-h-screen bg-bone font-sans text-ink">
            <nav className="bg-bone/85 backdrop-blur-md border-b border-ink/10 px-6 py-3 flex items-center justify-between">
                <span className="flex items-center gap-2"><img src="/images/nomdal-favicon.png" alt="Nomdal" className="h-7 w-7 object-contain" /><span className="font-display text-lg font-black tracking-tight text-ink">Nomdal — Platform Admin</span></span>
                <div className="flex items-center gap-3">
                    <span className="text-sm text-ink-dim">{auth.user?.name} <span className="px-2 py-0.5 bg-lime/20 text-forest rounded text-xs font-bold">Platform Owner</span></span>
                    <form method="POST" action="/logout"><input type="hidden" name="_token" value={document.querySelector('meta[name="csrf-token"]')?.content} /><button className="text-sm text-ink-dim hover:text-ink font-semibold">Logout</button></form>
                </div>
            </nav>
            <div className="flex">
                <aside className="w-56 bg-white/70 backdrop-blur-md border-r border-ink/10 min-h-[calc(100vh-57px)] p-3 overflow-y-auto">
                    {sidebar.map(section => (
                        <div key={section.section} className="mb-4">
                            <p className="px-3 font-mono text-[11px] font-bold text-ink-faint uppercase tracking-wider mb-2">{section.section}</p>
                            {section.items.map(item => (
                                <Link key={item.name} href={item.href} className={`flex items-center gap-2 px-3 py-2 rounded-lg text-sm mb-1 font-semibold transition-all ${window.location.pathname === item.href ? 'bg-ink text-bone' : 'text-ink-dim hover:bg-white/70 hover:text-ink'}`}>
                                    <span>{item.icon}</span> {item.name}
                                </Link>
                            ))}
                        </div>
                    ))}
                </aside>
                <main className="flex-1 p-8">
                    <Head title={title || 'Platform'} />
                    {flash?.success && <div className="mb-4 p-4 bg-lime/10 text-forest rounded-xl border border-lime/40 text-sm">{flash.success}</div>}
                    {flash?.error && <div className="mb-4 p-4 bg-wine/10 text-wine rounded-xl border border-wine/40 text-sm">{flash.error}</div>}
                    {children}
                </main>
            </div>
        </div>
    );
}