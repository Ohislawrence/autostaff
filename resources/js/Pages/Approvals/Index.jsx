import { Head, usePage, useForm } from '@inertiajs/react';
import TenantLayout from '@/Layouts/TenantLayout';

export default function Index({ pending }) {
    const { flash } = usePage().props;
    const { post: approve } = useForm();
    const { post: deny } = useForm();

    return (
        <TenantLayout header="Approvals">
            <Head title="Approvals" />
            {flash?.success && <div className="mb-4 p-4 bg-green-50 text-green-700 rounded-lg text-sm">{flash.success}</div>}
            {flash?.error && <div className="mb-4 p-4 bg-red-50 text-red-700 rounded-lg text-sm">{flash.error}</div>}

            <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <table className="w-full text-sm">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Action</th>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Customer</th>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">AI Employee</th>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Details</th>
                            <th className="text-right px-4 py-3 font-medium text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-100">
                        {pending.map(item => (
                            <tr key={item.id} className="hover:bg-gray-50 align-top">
                                <td className="px-4 py-3">
                                    <p className="font-medium text-gray-900">{item.tool_name}</p>
                                    <p className="text-xs text-gray-400">{item.tool_identifier}</p>
                                </td>
                                <td className="px-4 py-3 text-gray-500">{item.customer?.name || '—'}</td>
                                <td className="px-4 py-3 text-gray-500">{item.ai_employee || '—'}</td>
                                <td className="px-4 py-3">
                                    <pre className="text-xs text-gray-500 bg-gray-50 rounded p-2 overflow-x-auto">
                                        {JSON.stringify(item.input_parameters, null, 2)}
                                    </pre>
                                </td>
                                <td className="px-4 py-3 text-right">
                                    <div className="flex gap-2 justify-end">
                                        <button
                                            onClick={() => approve(`/approvals/${item.id}/approve`)}
                                            className="px-3 py-1.5 bg-green-600 text-white rounded-lg text-xs font-medium"
                                        >
                                            Approve
                                        </button>
                                        <button
                                            onClick={() => { if (confirm('Deny this action?')) deny(`/approvals/${item.id}/deny`); }}
                                            className="px-3 py-1.5 bg-red-100 text-red-700 rounded-lg text-xs font-medium"
                                        >
                                            Deny
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        ))}
                        {pending.length === 0 && (
                            <tr>
                                <td colSpan={5} className="px-4 py-8 text-center text-gray-400">
                                    No pending approvals.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </TenantLayout>
    );
}