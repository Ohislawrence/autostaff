import { Head, Link, usePage } from '@inertiajs/react';
import TenantLayout from '@/Layouts/TenantLayout';

export default function Dashboard({ stats, dashboardType, greeting }) {
    const { auth } = usePage().props;
    const currency = auth.organization?.currency || 'NGN';
    const currencySymbol = { USD: '$', NGN: '₦', GHS: 'GH₵', KES: 'KSh', ZAR: 'R', EUR: '€', GBP: '£', CAD: 'CA$', AUD: 'A$', INR: '₹', JPY: '¥' }[currency] || (currency + ' ');

    if (stats?.empty) {
        return (
            <TenantLayout header="Dashboard">
                <Head title="Dashboard" />
                <div className="text-center py-20">
                    <p className="text-gray-400">No organization data available.</p>
                </div>
            </TenantLayout>
        );
    }

    const userPermissions = auth?.user?.permissions || [];
    const hasPermission = (p) => !p || userPermissions.includes(p);

    return (
        <TenantLayout header="Dashboard">
            <Head title="Dashboard" />

            {/* Greeting + Date */}
            <div className="mb-6 flex items-center justify-between">
                <div>
                    <h1 className="text-xl font-semibold text-gray-900">{greeting}</h1>
                    <p className="text-sm text-gray-500 mt-0.5">{stats.organization_name}</p>
                </div>
                <span className="text-sm text-gray-400">{new Date().toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' })}</span>
            </div>

            {/* This Month — money-first headline */}
            <div className="mb-6 bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                <div className="flex items-center justify-between px-5 py-4 border-b border-gray-50">
                    <div className="flex items-center gap-3">
                        <span className="flex items-center justify-center w-9 h-9 rounded-lg bg-gradient-to-br from-emerald-50 to-green-100 text-lg">💵</span>
                        <div>
                            <h2 className="text-sm font-semibold text-gray-900">This Month</h2>
                            <p className="text-xs text-gray-400">Revenue &amp; pipeline at a glance</p>
                        </div>
                    </div>
                    <span className="inline-flex items-center gap-1.5 text-xs font-medium text-gray-500 bg-gray-50 border border-gray-100 rounded-full px-3 py-1.5">
                        <span className="text-amber-500">⚡</span> {stats.ai_work_completed ?? 0} AI actions
                    </span>
                </div>
                <div className="grid grid-cols-2 md:grid-cols-5 divide-x divide-y md:divide-y-0 divide-gray-50">
                    <MoneyCard label="Revenue" value={formatRevenue(stats.revenue_this_month, currencySymbol)} color="text-green-700" icon="💰" />
                    <MoneyCard label="Qualified Leads" value={stats.qualified_leads ?? 0} color="text-amber-700" icon="🎯" />
                    <MoneyCard label="Deals Won" value={stats.deals_won ?? 0} sub={`${stats.deals_won_this_month ?? 0} this month`} color="text-blue-700" icon="🤝" />
                    <MoneyCard label="Prospects Contacted" value={stats.prospects_contacted ?? 0} color="text-purple-700" icon="📨" />
                    <MoneyCard label="Pipeline Value" value={formatRevenue(stats.pipeline_value, currencySymbol)} color="text-teal-700" icon="📈" />
                </div>
            </div>

            {/* AI Employee Activity */}
            {stats.ai_activity?.length > 0 && (
                <div className="mb-6 bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                    <div className="flex items-center gap-3 px-5 py-4 border-b border-gray-50">
                        <span className="flex items-center justify-center w-9 h-9 rounded-lg bg-gradient-to-br from-blue-50 to-indigo-100 text-lg">🤖</span>
                        <div>
                            <h2 className="text-sm font-semibold text-gray-900">AI Employee Activity</h2>
                            <p className="text-xs text-gray-400">What your AI workforce did</p>
                        </div>
                    </div>
                    <div className="divide-y divide-gray-50">
                        {stats.ai_activity.map((e) => (
                            <div key={e.name} className="flex flex-col sm:flex-row sm:items-center gap-3 px-5 py-3.5 hover:bg-gray-50/50 transition-colors">
                                <div className="flex items-center gap-3 sm:w-56 shrink-0">
                                    <span className="flex items-center justify-center w-10 h-10 rounded-lg bg-gray-50 text-xl shrink-0">{e.avatar || '🤖'}</span>
                                    <div className="min-w-0">
                                        <p className="text-sm font-semibold text-gray-900 truncate">{e.name}</p>
                                        <p className="text-xs text-gray-400 truncate">{e.role}{!e.is_active && ' · paused'}</p>
                                    </div>
                                </div>
                                <div className="flex-1 grid grid-cols-3 sm:grid-cols-6 gap-2">
                                    <ActivityStat label="Conversations" value={e.conversations} />
                                    <ActivityStat label="Leads" value={e.leads} />
                                    <ActivityStat label="Found" value={e.prospects_found} />
                                    <ActivityStat label="Qualified" value={e.prospects_qualified} />
                                    <ActivityStat label="Contacted" value={e.prospects_contacted} />
                                    <ActivityStat label="Replies" value={e.prospects_replied} />
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            )}

            {/* KPI Cards */}
            <div className="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
                <KpiCard label="Customers" value={stats.customers_count} color="text-blue-700" bg="bg-gradient-to-br from-blue-50 to-blue-100" />
                <KpiCard label="Conversations" value={stats.conversations_count} sub={`${stats.conversations_today} today`} color="text-purple-700" bg="bg-gradient-to-br from-purple-50 to-purple-100" />
                <KpiCard label="Leads" value={stats.leads_count} sub={`${stats.lead_conversion_rate}% won`} color="text-amber-700" bg="bg-gradient-to-br from-amber-50 to-amber-100" />
                <KpiCard label="Revenue" value={formatRevenue(stats.revenue, currencySymbol)} sub={`${stats.orders_count} orders`} color="text-green-700" bg="bg-gradient-to-br from-green-50 to-green-100" />
            </div>

            {/* Second KPI Row */}
            <div className="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
                <KpiCard label="Open Conversations" value={stats.open_conversations} sub={`${stats.human_required} need human`} color="text-orange-700" bg="bg-gradient-to-br from-orange-50 to-orange-100" />
                <KpiCard label="AI Employees" value={stats.ai_employees_count} color="text-blue-700" bg="bg-gradient-to-br from-blue-50 to-blue-100" />
                <KpiCard label="Resolution Rate" value={`${stats.resolution_rate}%`} color="text-teal-700" bg="bg-gradient-to-br from-teal-50 to-teal-100" />
                <KpiCard label="Appointments" value={stats.appointments_upcoming} sub={`${stats.appointments_today} today`} color="text-pink-700" bg="bg-gradient-to-br from-pink-50 to-pink-100" />
            </div>

            {/* Main grid: Charts + Side */}
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                {/* Conversations Trend Chart */}
                <div className="lg:col-span-2 bg-white/80 backdrop-blur rounded-xl shadow-lg border border-blue-100 p-5">
                    <h3 className="font-semibold text-gray-900 mb-1">Conversations (7 days)</h3>
                    <p className="text-xs text-gray-500 mb-4">Daily conversation volume</p>
                    <BarChart data={stats.conversations_trend} color="bg-gradient-to-t from-blue-600 to-blue-500" maxValue={Math.max(...(stats.conversations_trend || []).map(d => d.value), 1)} />
                </div>

                {/* AI Employees */}
                <div className="bg-white/80 backdrop-blur rounded-xl shadow-lg border border-blue-100 p-5">
                    <h3 className="font-semibold text-gray-900 mb-1">AI Employees</h3>
                    <p className="text-xs text-gray-500 mb-4">Conversation count</p>
                    {stats.ai_employees?.length > 0 ? (
                        <div className="space-y-3 max-h-64 overflow-y-auto">
                            {stats.ai_employees.map((e) => (
                                <div key={e.name} className="flex items-center justify-between">
                                    <div className="flex items-center gap-2 min-w-0">
                                        <div className={`w-8 h-8 rounded-full flex items-center justify-center shrink-0 ${e.is_active ? 'bg-gradient-to-br from-blue-500 to-blue-600 text-white shadow-md shadow-blue-200' : 'bg-gray-100'}`}>
                                            <span className={`text-xs font-bold ${e.is_active ? 'text-white' : 'text-gray-400'}`}>{e.name[0]}</span>
                                        </div>
                                        <div className="min-w-0">
                                            <p className="text-sm font-medium text-gray-900 truncate">{e.name}</p>
                                            <p className="text-xs text-gray-400">{e.is_active ? 'Active' : 'Inactive'}</p>
                                        </div>
                                    </div>
                                    <span className="text-sm font-semibold text-blue-700 ml-2">{e.conversations_count}</span>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <p className="text-sm text-gray-400 text-center py-6">No AI employees yet.</p>
                    )}
                </div>
            </div>

            {/* Third row: Lead pipeline + Revenue trend */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                {/* Lead Pipeline */}
                <div className="bg-white/80 backdrop-blur rounded-xl shadow-lg border border-blue-100 p-5">
                    <h3 className="font-semibold text-gray-900 mb-1">Lead Pipeline</h3>
                    <p className="text-xs text-gray-500 mb-4">By stage</p>
                    {stats.leads_pipeline && Object.values(stats.leads_pipeline).some(v => v > 0) ? (
                        <div className="flex items-end gap-1 h-24">
                            {Object.entries(stats.leads_pipeline).map(([stage, count]) => {
                                const maxVal = Math.max(...Object.values(stats.leads_pipeline), 1);
                                return (
                                    <div key={stage} className="flex-1 flex flex-col items-center justify-end h-full">
                                        <span className="text-xs font-semibold text-gray-700 mb-0.5">{count}</span>
                                        <div
                                            className="w-full bg-gradient-to-t from-amber-500 to-amber-400 rounded-t shadow-sm"
                                            style={{ height: `${(count / maxVal) * 80}%`, minHeight: count > 0 ? 8 : 0 }}
                                        />
                                        <span className="text-[10px] text-gray-400 mt-1 capitalize">{stage}</span>
                                    </div>
                                );
                            })}
                        </div>
                    ) : (
                        <p className="text-sm text-gray-400 text-center py-6">No leads in pipeline yet.</p>
                    )}
                </div>

                {/* Revenue Trend */}
                <div className="bg-white/80 backdrop-blur rounded-xl shadow-lg border border-blue-100 p-5">
                    <h3 className="font-semibold text-gray-900 mb-1">Revenue (7 days)</h3>
                    <p className="text-xs text-gray-500 mb-4">Daily revenue</p>
                    <BarChart data={stats.revenue_trend} color="bg-gradient-to-t from-green-600 to-green-500" maxValue={Math.max(...(stats.revenue_trend || []).map(d => d.value), 1)} formatValue={(v) => v > 0 ? currencySymbol + v.toLocaleString() : ''} />
                </div>
            </div>

            {/* Fourth row: Quick actions + Recent activity */}
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {/* Quick Actions */}
                {stats.quick_actions?.length > 0 && (
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                        <h3 className="font-semibold text-gray-900 mb-3">Quick Actions</h3>
                        <div className="flex flex-wrap gap-2">
                            {stats.quick_actions.map((action) => (
                                hasPermission(action.permission) && (
                                    <Link
                                        key={action.label}
                                        href={action.href}
                                        className={`px-3 py-2 rounded-lg text-xs font-medium text-white transition-colors ${action.color}`}
                                    >
                                        {action.label}
                                    </Link>
                                )
                            ))}
                        </div>
                    </div>
                )}

                {/* Recent Activity */}
                <div className="lg:col-span-2 bg-white/80 backdrop-blur rounded-xl shadow-lg border border-blue-100 p-5">
                    <h3 className="font-semibold text-gray-900 mb-1">Recent Activity</h3>
                    <p className="text-xs text-gray-500 mb-4">Latest events in your organization</p>
                    {stats.recent_activity?.length > 0 ? (
                        <div className="space-y-3">
                            {stats.recent_activity.map((a, i) => (
                                <Link key={i} href={a.href || '#'} className="flex items-start gap-3 p-2 -mx-2 rounded-lg hover:bg-gray-50 transition-colors">
                                    <div className={`w-8 h-8 rounded-lg flex items-center justify-center shrink-0 ${a.icon}`}>
                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d={a.icon_svg} />
                                        </svg>
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <p className="text-sm text-gray-700">{a.message}</p>
                                        {a.detail && <p className="text-xs text-gray-400 truncate">{a.detail}</p>}
                                    </div>
                                    <span className="text-xs text-gray-400 whitespace-nowrap">{a.time}</span>
                                </Link>
                            ))}
                        </div>
                    ) : (
                        <p className="text-sm text-gray-400 text-center py-6">No recent activity.</p>
                    )}
                </div>
            </div>
        </TenantLayout>
    );
}

// Reusable KPI card
function KpiCard({ label, value, sub, color, bg }) {
    return (
        <div className={`${bg} rounded-xl p-4`}>
            <p className={`text-2xl font-bold ${color}`}>{value ?? 0}</p>
            <p className="text-xs text-gray-600 font-medium">{label}</p>
            {sub && <p className="text-[11px] text-gray-400 mt-0.5">{sub}</p>}
        </div>
    );
}

// Simple CSS bar chart
function BarChart({ data, color, maxValue, formatValue }) {
    if (!data || data.length === 0) {
        return <p className="text-sm text-gray-400 text-center py-6">No data available.</p>;
    }

    return (
        <div className="flex items-end gap-1 h-32">
            {data.map((d, i) => (
                <div key={i} className="flex-1 flex flex-col items-center justify-end h-full">
                    {(formatValue ? formatValue(d.value) : d.value) > 0 && (
                        <span className="text-[10px] text-gray-500 mb-0.5 font-medium">
                            {formatValue ? formatValue(d.value) : d.value}
                        </span>
                    )}
                    <div
                        className={`w-full ${color} rounded-t transition-all duration-300`}
                        style={{ height: `${Math.max((d.value / maxValue) * 100, d.value > 0 ? 4 : 0)}%` }}
                    />
                    <span className="text-[10px] text-gray-400 mt-1 whitespace-nowrap">{d.label}</span>
                </div>
            ))}
        </div>
    );
}

function formatRevenue(amount, symbol) {
    const s = symbol || '₦';
    if (!amount || amount === 0) return s + '0';
    const num = parseFloat(amount);
    if (num >= 1000000) return s + (num / 1000000).toFixed(1) + 'M';
    if (num >= 1000) return s + (num / 1000).toFixed(1) + 'K';
    return s + num.toLocaleString(undefined, { minimumFractionDigits: 2 });
}

function MoneyCard({ label, value, sub, color }) {
    return (
        <div className="rounded-xl bg-gradient-to-br from-white to-gray-50 border border-gray-100 p-4">
            <p className={`text-2xl font-bold ${color}`}>{value ?? 0}</p>
            <p className="text-xs text-gray-600 font-medium">{label}</p>
            {sub && <p className="text-[11px] text-gray-400 mt-0.5">{sub}</p>}
        </div>
    );
}

function ActivityStat({ label, value }) {
    return (
        <div className="text-center">
            <p className="text-base font-bold text-gray-900">{value ?? 0}</p>
            <p className="text-[11px] text-gray-400">{label}</p>
        </div>
    );
}
