import { Link, usePage, useForm } from '@inertiajs/react';
import PlatformLayout from '@/Layouts/PlatformLayout';

export default function Users({ users, roles, filters }) {
    const { flash } = usePage().props;
    const { post } = useForm();
    return (
        <PlatformLayout title="Users">
            <div className="flex items-center justify-between mb-6">
                <h1 className="text-2xl font-bold text-gray-900">Users ({users.total})</h1>
                <form><input type="text" name="search" defaultValue={filters.search} placeholder="Search users..." className="px-3 py-2 border border-gray-300 rounded-lg text-sm" /><button type="submit" className="ml-2 px-4 py-2 bg-purple-600 text-white rounded-lg text-sm">Search</button></form>
            </div>
            <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden"><table className="w-full text-sm"><thead className="bg-gray-50"><tr><th className="text-left px-4 py-3 font-medium text-gray-500">Name</th><th className="text-left px-4 py-3 font-medium text-gray-500">Email</th><th className="text-left px-4 py-3 font-medium text-gray-500">Role</th><th className="text-left px-4 py-3 font-medium text-gray-500">Organizations</th></tr></thead><tbody className="divide-y divide-gray-100">{users.data?.map(u=>(<tr key={u.id} className="hover:bg-gray-50"><td className="px-4 py-3 font-medium text-gray-900">{u.name}</td><td className="px-4 py-3 text-gray-500 text-xs">{u.email}</td><td className="px-4 py-3"><select defaultValue={u.roles?.[0]?.name||''} onChange={e=>{if(e.target.value)post(`/platform/users/${u.id}/role`,{data:{role:e.target.value}})}} className="px-2 py-1 border border-gray-300 rounded text-xs"><option value="">No Role</option>{roles.map(r=><option key={r.id} value={r.name}>{r.name}</option>)}</select></td><td className="px-4 py-3 text-xs text-gray-400">{u.organizations?.map(o=>o.name).join(', ')||'—'}</td></tr>))}</tbody></table></div>
        </PlatformLayout>
    );
}