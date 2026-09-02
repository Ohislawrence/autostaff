import { Link, usePage } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import TenantSwitcher from '@/Components/TenantSwitcher';

const navSections = [
    {
        section: 'MAIN',
        items: [
            { name: 'Dashboard', href: '/dashboard', icon: DashboardIcon, permission: null },
            { name: 'Inbox', href: '/inbox', icon: ChatIcon, permission: 'conversations.view' },
        ],
    },
    {
        section: 'AI WORKFORCE',
        items: [
            { name: 'AI Employees', href: '/ai-employees', icon: BotIcon, permission: 'ai-employees.view' },
            { name: 'Knowledge', href: '/knowledge', icon: BookIcon, permission: 'knowledge.view' },
            { name: 'Automations', href: '/automations', icon: ZapIcon, permission: 'automations.view' },
        ],
    },
    {
        section: 'CUSTOMERS',
        items: [
            { name: 'Customers', href: '/customers', icon: UsersIcon, permission: 'customers.view' },
            { name: 'Leads', href: '/leads', icon: TargetIcon, permission: 'leads.view' },
        ],
    },
    {
        section: 'OPERATIONS',
        items: [
            { name: 'Products', href: '/products', icon: PackageIcon, permission: 'products.view' },
            { name: 'Orders', href: '/orders', icon: CartIcon, permission: 'orders.view' },
            { name: 'Appointments', href: '/appointments', icon: CalendarIcon, permission: 'appointments.view' },
        ],
    },
    {
        section: 'INSIGHTS',
        items: [
            { name: 'Analytics', href: '/analytics', icon: ChartIcon, permission: 'analytics.view' },
        ],
    },
    {
        section: 'MANAGEMENT',
        items: [
            { name: 'Team', href: '/team', icon: UsersIcon, permission: 'organization.manage-team' },
            { name: 'Files', href: '/files', icon: FolderIcon, permission: 'settings.view' },
            { name: 'Help', href: '/help', icon: HelpIcon, permission: null },
            { name: 'Integrations', href: '/integrations', icon: LinkIcon, permission: 'ai-employees.manage-channels' },
            { name: 'Plugins', href: '/plugins', icon: PluginIcon, permission: null },
            { name: 'Installed Plugins', href: '/plugins/installations', icon: PluginIcon, permission: null },
            { name: 'Billing', href: '/billing', icon: CreditCardIcon, permission: 'billing.view' },
            { name: 'Settings', href: '/settings', icon: SettingsIcon, permission: 'settings.view' },
            { name: 'Scheduling', href: '/settings/scheduling', icon: CalendarIcon, permission: 'settings.view' },
            { name: 'API Keys', href: '/settings/api-keys', icon: KeyIcon, permission: 'api.manage-keys' },
        ],
    },
];

export default function TenantLayout({ children, header }) {
    const { auth, platformAnnouncements } = usePage().props;
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const [showAnnouncementPanel, setShowAnnouncementPanel] = useState(false);

    // Unread count comes from server-side read_at tracking
    const fullList = platformAnnouncements || [];
    const unreadAnnouncements = fullList.filter(a => !a.read_at);
    const bellList = fullList.slice(0, 5);
    const csrfToken = typeof document !== 'undefined' ? document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '' : '';

    // Filter navigation based on user permissions
    const userPermissions = auth?.user?.permissions || [];
    const filteredSections = navSections.map(section => {
        const visibleItems = section.items.filter(item => {
            if (!item.permission) return true; // Always show Dashboard
            return userPermissions.includes(item.permission);
        });
        return { ...section, items: visibleItems };
    }).filter(section => section.items.length > 0);

    return (
        <div className="min-h-screen bg-gradient-to-br from-blue-50 via-white to-amber-50">
            <nav className="fixed top-0 z-50 w-full bg-white/80 backdrop-blur-md border-b border-blue-100 shadow-sm">
                <div className="px-4 py-3 lg:px-6">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-4">
                            <button onClick={() => setSidebarOpen(!sidebarOpen)} className="lg:hidden p-2 rounded-lg hover:bg-blue-50 transition-colors">
                                <svg className="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                            </button>
                            <Link href="/dashboard" className="flex items-center gap-2">
                                <div className="w-8 h-8 bg-gradient-to-br from-blue-600 to-blue-700 rounded-lg flex items-center justify-center shadow-md shadow-blue-200">
                                    <svg className="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5" />
                                    </svg>
                                </div>
                                <span className="text-lg font-bold bg-gradient-to-r from-blue-700 to-blue-900 bg-clip-text text-transparent">{auth.organization?.name || 'AI Employee'}</span>
                            </Link>
                        </div>
                        <div className="flex items-center gap-3">
                            <TenantSwitcher />

                            {/* Announcements Bell */}
                            <div className="relative">
                                <button
                                    onClick={() => setShowAnnouncementPanel(!showAnnouncementPanel)}
                                    className="p-2 rounded-lg hover:bg-blue-50 transition-colors relative"
                                >
                                    <svg className="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                                    </svg>
                                    {unreadAnnouncements.length > 0 && (
                                        <span className="absolute -top-0.5 -right-0.5 w-5 h-5 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center shadow-sm">
                                            {unreadAnnouncements.length > 9 ? '9+' : unreadAnnouncements.length}
                                        </span>
                                    )}
                                </button>

                                {/* Announcements Dropdown */}
                                {showAnnouncementPanel && (
                                    <>
                                        <div className="fixed inset-0 z-10" onClick={() => setShowAnnouncementPanel(false)} />
                                        <div className="absolute right-0 top-full mt-2 w-80 bg-white rounded-xl shadow-xl border border-gray-200 z-20 overflow-hidden">
                                            <div className="p-3 border-b border-gray-100 flex items-center justify-between">
                                                <h3 className="text-sm font-semibold text-gray-900">📢 Announcements</h3>
                                                <Link href="/announcements" className="text-xs text-primary-600 hover:text-primary-700 font-medium" onClick={() => setShowAnnouncementPanel(false)}>
                                                    View all →
                                                </Link>
                                            </div>
                                            <div className="max-h-80 overflow-y-auto">
                                                {bellList.length === 0 && (
                                                    <p className="text-xs text-gray-400 text-center py-6">No announcements yet.</p>
                                                )}
                                                {bellList.map(a => {
                                                    const isUnread = !a.read_at;
                                                    const typeColors = { info: 'border-blue-400 bg-blue-50', warning: 'border-amber-400 bg-amber-50', success: 'border-green-400 bg-green-50', danger: 'border-red-400 bg-red-50' };
                                                    return (
                                                        <div key={a.id} className={`px-4 py-3 border-l-4 ${typeColors[a.type] || typeColors.info} ${isUnread ? 'bg-white' : 'bg-gray-50 opacity-70'}`}>
                                                            <div className="flex items-start justify-between gap-2">
                                                                <div className="flex-1 min-w-0">
                                                                    <div className="flex items-center gap-1.5">
                                                                        {isUnread && <span className="w-1.5 h-1.5 bg-blue-500 rounded-full flex-shrink-0" />}
                                                                        <p className="text-sm font-medium text-gray-900">{a.title}</p>
                                                                    </div>
                                                                    {a.body && <p className="text-xs text-gray-500 mt-0.5 line-clamp-2">{a.body}</p>}
                                                                    <p className="text-[10px] text-gray-400 mt-1">{new Date(a.published_at).toLocaleDateString()}</p>
                                                                </div>
                                                                {isUnread && (
                                                                    <form method="POST" action={`/announcements/mark-read/${a.id}`} onSubmit={(e) => { e.stopPropagation(); }}>
                                                                        <input type="hidden" name="_token" value={csrfToken} />
                                                                        <button type="submit" className="p-0.5 text-gray-300 hover:text-gray-500 hover:bg-gray-100 rounded flex-shrink-0" title="Mark read">
                                                                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" /></svg>
                                                                        </button>
                                                                    </form>
                                                                )}
                                                            </div>
                                                        </div>
                                                    );
                                                })}
                                            </div>
                                        </div>
                                    </>
                                )}
                            </div>

                            <span className="text-sm text-gray-700 hidden sm:block">
                                {auth.user?.name}
                                {auth.user?.roles?.length > 0 && (
                                    <span className="ml-1 px-2 py-0.5 bg-gradient-to-r from-amber-400 to-amber-500 text-white rounded text-xs font-medium shadow-sm">
                                        {auth.user.roles[0]}
                                    </span>
                                )}
                            </span>
                            <Link href="/logout" method="post" as="button" className="text-sm text-gray-600 hover:text-blue-700 font-medium transition-colors">Logout</Link>
                        </div>
                    </div>
                </div>
            </nav>

            <aside className={`fixed top-0 left-0 z-40 w-64 h-screen pt-16 transition-transform bg-white/90 backdrop-blur-md border-r border-blue-100 lg:translate-x-0 shadow-xl shadow-blue-100/20 ${sidebarOpen ? 'translate-x-0' : '-translate-x-full'}`}>
                <div className="h-full px-3 pb-4 overflow-y-auto">
                    {filteredSections.map(section => (
                        <div key={section.section} className="mb-4">
                            <p className="px-3 text-xs font-bold text-blue-400 uppercase tracking-wider mb-2">{section.section}</p>
                            <ul className="space-y-1 font-medium">
                                {section.items.map((item) => {
                                    const isActive = window.location.pathname.startsWith(item.href);
                                    return (
                                        <li key={item.name}>
                                            <Link href={item.href} className={`flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-all ${isActive ? 'bg-gradient-to-r from-blue-600 to-blue-700 text-white shadow-md shadow-blue-200' : 'text-gray-700 hover:bg-blue-50 hover:text-blue-700'}`}>
                                                <item.icon className={`w-5 h-5 ${isActive ? 'text-white' : 'text-gray-400'}`} />
                                                {item.name}
                                            </Link>
                                        </li>
                                    );
                                })}
                            </ul>
                        </div>
                    ))}
                </div>
            </aside>

            {sidebarOpen && <div className="fixed inset-0 z-30 bg-gray-900/50 lg:hidden" onClick={() => setSidebarOpen(false)} />}

            <div className="lg:pl-64 pt-16">
                <main className="p-4 lg:p-8">
                    {header && (<div className="mb-6"><h1 className="text-2xl font-bold text-gray-900">{header}</h1></div>)}
                    {children}
                </main>
            </div>
        </div>
    );
}

// SVG icon components
function DashboardIcon({ className }) {
    return (<svg className={className} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1h-2z" /></svg>);
}
function BotIcon({ className }) {
    return (<svg className={className} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5" /></svg>);
}
function ChatIcon({ className }) {
    return (<svg className={className} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 01-.825-.242m9.345-8.334a2.126 2.126 0 00-.476-.095 48.64 48.64 0 00-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0011.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" /></svg>);
}
function UsersIcon({ className }) {
    return (<svg className={className} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>);
}
function TargetIcon({ className }) {
    return (<svg className={className} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>);
}
function BookIcon({ className }) {
    return (<svg className={className} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" /></svg>);
}
function PackageIcon({ className }) {
    return (<svg className={className} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" /></svg>);
}
function CartIcon({ className }) {
    return (<svg className={className} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" /></svg>);
}
function CalendarIcon({ className }) {
    return (<svg className={className} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25" /></svg>);
}
function ZapIcon({ className }) {
    return (<svg className={className} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" /></svg>);
}
function ChartIcon({ className }) {
    return (<svg className={className} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>);
}
function CreditCardIcon({ className }) {
    return (<svg className={className} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" /></svg>);
}
function SettingsIcon({ className }) {
    return (<svg className={className} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" /><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>);
}
function FolderIcon({ className }) {
    return (<svg className={className} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" /></svg>);
}
function HelpIcon({ className }) {
    return (<svg className={className} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" /></svg>);
}
function LinkIcon({ className }) {
    return (<svg className={className} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" /></svg>);
}
function KeyIcon({ className }) {
    return (<svg className={className} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" /></svg>);
}
function PluginIcon({ className }) {
    return (<svg className={className} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M8.25 4.5a2.25 2.25 0 114.5 0v1.5h4.5a2.25 2.25 0 012.25 2.25v4.5H21a2.25 2.25 0 110 4.5h-1.5v4.5a2.25 2.25 0 01-2.25 2.25h-4.5V22.5a2.25 2.25 0 11-4.5 0v-1.5H6a2.25 2.25 0 01-2.25-2.25v-4.5H2.25a2.25 2.25 0 110-4.5H3.75V6A2.25 2.25 0 016 3.75h4.5v-1.5a2.25 2.25 0 014.5 0z" /></svg>);
}
