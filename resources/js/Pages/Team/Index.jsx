import { Head, usePage, useForm } from '@inertiajs/react';
import { useState } from 'react';
import TenantLayout from '@/Layouts/TenantLayout';

export default function Index({ team, roles }) {
    const { auth, flash } = usePage().props;
    const { post } = useForm();
    const [showInvite, setShowInvite] = useState(false);
    const inviteForm = useForm({ user_email: '', role: 'Agent' });

    const roleColors = {
        'Tenant Owner': 'bg-purple-100 text-purple-700',
        'Tenant Admin': 'bg-blue-100 text-blue-700',
        'Manager': 'bg-green-100 text-green-700',
        'Agent': 'bg-orange-100 text-orange-700',
        'Viewer': 'bg-gray-100 text-gray-600',
    };

    return (
        <TenantLayout header="Team">
            <Head title="Team" />
            
            {flash?.success && <div className="mb-4 p-4 bg-green-50 text-green-700 rounded-lg text-sm">{flash.success}</div>}
            {flash?.error && <div className="mb-4 p-4 bg-red-50 text-red-700 rounded-lg text-sm">{flash.error}</div>}

            <div className="flex items-center justify-between mb-6">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Team ({team.length})</h1>
                    <p className="text-sm text-gray-500 mt-0.5">Manage team members and their roles</p>
                </div>
                <button onClick={() => setShowInvite(!showInvite)} className="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium hover:bg-primary-700 transition">
                    + Invite Member
                </button>
            </div>

            {showInvite && (
                <div className="mb-6 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 className="font-semibold text-gray-900 mb-4">Invite Team Member</h3>
                    <p className="text-xs text-gray-500 mb-4">Enter the email of a registered user to add them to your organization.</p>
                    <form onSubmit={e => { e.preventDefault(); inviteForm.post('/team/add', { onSuccess: () => setShowInvite(false) }); }} className="flex gap-3 items-end">
                        <div className="flex-1">
                            <label className="block text-xs font-medium text-gray-600 mb-1">Email Address *</label>
                            <input type="email" required value={inviteForm.data.user_email} onChange={e => inviteForm.setData('user_email', e.target.value)} className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="colleague@example.com" />
                        </div>
                        <div>
                            <label className="block text-xs font-medium text-gray-600 mb-1">Role</label>
                            <select value={inviteForm.data.role} onChange={e => inviteForm.setData('role', e.target.value)} className="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                {roles.map(r => <option key={r.id} value={r.name}>{r.name}</option>)}
                            </select>
                        </div>
                        <button type="submit" disabled={inviteForm.processing} className="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium">{inviteForm.processing ? 'Adding...' : 'Add Member'}</button>
                        <button type="button" onClick={() => setShowInvite(false)} className="px-4 py-2 border border-gray-300 rounded-lg text-sm">Cancel</button>
                    </form>
                </div>
            )}

            <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <table className="w-full text-sm">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Member</th>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Role</th>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Last Active</th>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-100">
                        {team.map(member => {
                            const isCurrentUser = member.id === auth.user?.id;
                            return (
                                <tr key={member.id} className="hover:bg-gray-50">
                                    <td className="px-4 py-3">
                                        <div className="flex items-center gap-3">
                                            <div className="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center">
                                                <span className="text-sm font-medium text-primary-700">
                                                    {member.name?.charAt(0)?.toUpperCase()}
                                                </span>
                                            </div>
                                            <div>
                                                <div className="flex items-center gap-2">
                                                    <span className="font-medium text-gray-900">{member.name}</span>
                                                    {member.is_owner && (
                                                        <span className="px-1.5 py-0.5 bg-purple-100 text-purple-700 rounded text-xs font-medium">Owner</span>
                                                    )}
                                                    {isCurrentUser && (
                                                        <span className="px-1.5 py-0.5 bg-blue-100 text-blue-700 rounded text-xs font-medium">You</span>
                                                    )}
                                                </div>
                                                <p className="text-xs text-gray-400">{member.email}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td className="px-4 py-3">
                                        {member.is_owner ? (
                                            <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${roleColors[member.role] || 'bg-gray-100 text-gray-600'}`}>
                                                {member.role}
                                            </span>
                                        ) : (
                                            <select
                                                defaultValue={member.role}
                                                onChange={(e) => {
                                                    if (e.target.value && e.target.value !== member.role) {
                                                        post(`/team/${member.id}/role`, { data: { role: e.target.value } });
                                                    }
                                                }}
                                                disabled={isCurrentUser}
                                                className="px-2 py-1 border border-gray-300 rounded text-xs"
                                            >
                                                {roles.map(r => (
                                                    <option key={r.id} value={r.name}>{r.name}</option>
                                                ))}
                                            </select>
                                        )}
                                    </td>
                                    <td className="px-4 py-3 text-xs text-gray-400">
                                        {member.last_login || 'N/A'}
                                    </td>
                                    <td className="px-4 py-3">
                                        {!member.is_owner && !isCurrentUser && (
                                            <button
                                                onClick={() => {
                                                    if (confirm(`Remove ${member.name} from the team?`)) {
                                                        post(`/team/${member.id}/remove`);
                                                    }
                                                }}
                                                className="text-xs text-red-600 hover:text-red-700 font-medium"
                                            >
                                                Remove
                                            </button>
                                        )}
                                    </td>
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
            </div>

            {/* Roles Reference */}
            <div className="mt-6 bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h3 className="font-semibold text-gray-900 mb-3">Role Permissions</h3>
                <div className="grid grid-cols-2 gap-3 text-sm">
                    {[
                        { role: 'Tenant Owner', desc: 'Full access: manage team, billing, AI employees, settings', color: 'text-purple-700' },
                        { role: 'Tenant Admin', desc: 'Manage AI employees, knowledge, automations, analytics', color: 'text-blue-700' },
                        { role: 'Manager', desc: 'Handle conversations, customers, leads, pipeline', color: 'text-green-700' },
                        { role: 'Agent', desc: 'Answer conversations, view customers & leads', color: 'text-orange-700' },
                        { role: 'Viewer', desc: 'Read-only access to conversations and analytics', color: 'text-gray-700' },
                    ].map(r => (
                        <div key={r.role} className="p-3 bg-gray-50 rounded-lg">
                            <p className={`font-medium ${r.color}`}>{r.role}</p>
                            <p className="text-xs text-gray-500 mt-0.5">{r.desc}</p>
                        </div>
                    ))}
                </div>
            </div>
        </TenantLayout>
    );
}