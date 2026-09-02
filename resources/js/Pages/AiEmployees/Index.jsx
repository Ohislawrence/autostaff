import { Head, Link, usePage, useForm } from '@inertiajs/react';
import TenantLayout from '@/Layouts/TenantLayout';

export default function Index({ employees }) {
    const { flash } = usePage().props;
    const { post } = useForm();

    const getStatusBadge = (employee) => {
        if (!employee.is_active) return { text: 'Inactive', class: 'bg-gray-100 text-gray-600' };
        return { text: 'Active', class: 'bg-green-100 text-green-700' };
    };

    return (
        <TenantLayout header="AI Employees">
            <Head title="AI Employees" />

            {/* Flash messages */}
            {flash?.success && (
                <div className="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-xl text-sm flex items-start gap-2 shadow-sm">
                    <svg className="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                    </svg>
                    <div>
                        <p className="font-medium">{flash.success}</p>
                        {flash.success.includes('created') && (
                            <p className="text-xs text-green-600 mt-1">
                                👉 Next steps: <strong>Activate</strong> your AI employee, then click <strong>Channels</strong> to connect it to web chat or other channels.
                            </p>
                        )}
                    </div>
                </div>
            )}

            {/* Header */}
            <div className="flex items-center justify-between mb-6">
                <p className="text-gray-500 text-sm">
                    Manage your AI employees — configure their roles, tools, and channels.
                </p>
                <Link
                    href="/ai-employees/create"
                    className="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl font-medium hover:from-blue-700 hover:to-blue-800 transition-all shadow-md shadow-blue-200 text-sm"
                >
                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Create AI Employee
                </Link>
            </div>

            {/* Employees Grid */}
            {employees.length === 0 ? (
                <div className="bg-white/80 backdrop-blur rounded-xl shadow-lg border border-blue-100 p-12 text-center">
                    <div className="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg shadow-blue-200">
                        <svg className="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5" />
                        </svg>
                    </div>
                    <h3 className="text-lg font-semibold text-gray-900 mb-2">No AI Employees Yet</h3>
                    <p className="text-gray-500 mb-6 max-w-md mx-auto">
                        Create your first AI employee to start handling customer conversations, generating leads, and automating business tasks.
                    </p>
                    <Link
                        href="/ai-employees/create"
                        className="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl font-medium hover:from-blue-700 hover:to-blue-800 transition-all shadow-md shadow-blue-200"
                    >
                        Create Your First AI Employee
                    </Link>
                </div>
            ) : (
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    {employees.map((employee) => {
                        const status = getStatusBadge(employee);
                        return (
                            <div key={employee.id} className="bg-white/80 backdrop-blur rounded-xl shadow-lg border border-blue-100 p-6 hover:shadow-xl hover:border-blue-200 transition-all">
                                <div className="flex items-start justify-between mb-4">
                                    <div className="flex items-center gap-3">
                                        <div className="w-10 h-10 bg-gradient-to-br from-blue-100 to-blue-200 rounded-full flex items-center justify-center text-lg shadow-sm">
                                            {employee.avatar || '🤖'}
                                        </div>
                                        <div>
                                            <h3 className="font-semibold text-gray-900 text-sm">{employee.name}</h3>
                                            <p className="text-xs text-gray-500">{employee.role}</p>
                                        </div>
                                    </div>
                                    <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${status.class}`}>
                                        {status.text}
                                    </span>
                                </div>

                                <p className="text-xs text-gray-500 mb-4 line-clamp-2">
                                    {employee.description || 'No description'}
                                </p>

                                <div className="flex items-center gap-4 text-xs text-gray-400 mb-4">
                                    <span>💬 {employee.conversations_count || 0} chats</span>
                                    <span>🎯 {employee.leads_count || 0} leads</span>
                                </div>

                                <div className="flex items-center gap-2 pt-3 border-t border-gray-100">
                                    <button
                                        onClick={() => post(`/ai-employees/${employee.id}/toggle`)}
                                        className="px-2 py-1.5 text-xs rounded-lg font-medium transition-colors flex-1 text-center"
                                        style={{
                                            backgroundColor: employee.is_active ? '#FEF2F2' : '#F0FDF4',
                                            color: employee.is_active ? '#DC2626' : '#16A34A'
                                        }}
                                    >
                                        {employee.is_active ? 'Deactivate' : 'Activate'}
                                    </button>
                                    <Link
                                        href={`/ai-employees/${employee.id}/edit`}
                                        className="px-2 py-1.5 text-xs rounded-lg bg-gray-100 text-gray-600 font-medium hover:bg-gray-200 transition-colors flex-1 text-center"
                                    >
                                        Edit
                                    </Link>
                                    <Link
                                        href={`/ai-employees/${employee.id}/channels`}
                                        className="px-2 py-1.5 text-xs rounded-lg bg-gradient-to-r from-amber-400 to-amber-500 text-white font-medium hover:from-amber-500 hover:to-amber-600 transition-all shadow-sm flex-1 text-center"
                                    >
                                        Channels
                                    </Link>
                                </div>
                            </div>
                        );
                    })}
                </div>
            )}
        </TenantLayout>
    );
}