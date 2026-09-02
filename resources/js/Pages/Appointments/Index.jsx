import { Head, Link, usePage, useForm } from '@inertiajs/react';
import TenantLayout from '@/Layouts/TenantLayout';

export default function Index({ appointments, stats, filters }) {
    const { flash } = usePage().props;
    const { post } = useForm();

    const statusBadge = (status) => {
        const map = {
            scheduled: 'bg-blue-100 text-blue-700',
            confirmed: 'bg-green-100 text-green-700',
            cancelled: 'bg-red-100 text-red-700',
            completed: 'bg-emerald-100 text-emerald-700',
            no_show: 'bg-gray-100 text-gray-600',
        };
        return map[status] || 'bg-gray-100 text-gray-600';
    };

    return (
        <TenantLayout header="Appointments">
            <Head title="Appointments" />
            {flash?.success && <div className="mb-4 p-4 bg-green-50 text-green-700 rounded-lg text-sm">{flash.success}</div>}

            <div className="grid grid-cols-3 gap-4 mb-6">
                <div className="bg-white/80 backdrop-blur rounded-xl shadow-lg border border-blue-100 p-4">
                    <p className="text-2xl font-bold text-gray-900">{stats.today}</p>
                    <p className="text-xs text-gray-500">Today</p>
                </div>
                <div className="bg-white/80 backdrop-blur rounded-xl shadow-lg border border-blue-100 p-4">
                    <p className="text-2xl font-bold text-blue-600">{stats.upcoming}</p>
                    <p className="text-xs text-gray-500">Upcoming</p>
                </div>
                <div className="bg-white/80 backdrop-blur rounded-xl shadow-lg border border-blue-100 p-4">
                    <p className="text-2xl font-bold text-green-600">{stats.completed}</p>
                    <p className="text-xs text-gray-500">Completed</p>
                </div>
            </div>

            <div className="flex items-center justify-between mb-4">
                <form className="flex gap-2">
                    <input type="date" name="date" defaultValue={filters.date} className="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none transition-shadow" />
                    <select name="status" defaultValue={filters.status} className="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none transition-shadow">
                        <option value="">All</option>
                        <option value="scheduled">Scheduled</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    <button type="submit" className="px-4 py-2 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl text-sm font-medium hover:from-blue-700 hover:to-blue-800 transition-all shadow-md shadow-blue-200">Filter</button>
                </form>
                <Link href="/appointments/create" className="px-4 py-2 bg-gradient-to-r from-amber-400 to-amber-500 text-white rounded-xl text-sm font-medium hover:from-amber-500 hover:to-amber-600 transition-all shadow-md shadow-amber-200">+ New Appointment</Link>
            </div>

            <div className="bg-white/80 backdrop-blur rounded-xl shadow-lg border border-blue-100 overflow-hidden">
                <table className="w-full text-sm">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Customer</th>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Title</th>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Date/Time</th>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Status</th>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-100">
                        {appointments.data?.map(appt => (
                            <tr key={appt.id} className="hover:bg-gray-50">
                                <td className="px-4 py-3 font-medium text-gray-900">{appt.customer?.first_name} {appt.customer?.last_name}</td>
                                <td className="px-4 py-3 text-gray-600">{appt.title}</td>
                                <td className="px-4 py-3 text-xs text-gray-500">
                                    {new Date(appt.start_time).toLocaleString()}
                                </td>
                                <td className="px-4 py-3">
                                    <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${statusBadge(appt.status)}`}>{appt.status}</span>
                                </td>
                                <td className="px-4 py-3">
                                    <div className="flex gap-1">
                                        {appt.status === 'scheduled' && (
                                            <>
                                                <button onClick={() => post(`/appointments/${appt.id}/update`, { data: { status: 'confirmed' } })} className="text-xs text-green-600 hover:text-green-700 px-2 py-1 rounded bg-green-50">Confirm</button>
                                                <button onClick={() => post(`/appointments/${appt.id}/update`, { data: { status: 'cancelled' } })} className="text-xs text-red-600 hover:text-red-700 px-2 py-1 rounded bg-red-50">Cancel</button>
                                            </>
                                        )}
                                        {appt.status === 'confirmed' && (
                                            <button onClick={() => post(`/appointments/${appt.id}/update`, { data: { status: 'completed' } })} className="text-xs text-blue-600 hover:text-blue-700 px-2 py-1 rounded bg-blue-50 font-medium transition-colors">Complete</button>
                                        )}
                                    </div>
                                </td>
                            </tr>
                        ))}
                        {appointments.data?.length === 0 && (
                            <tr><td colSpan={5} className="px-4 py-8 text-center text-gray-400">No appointments yet.</td></tr>
                        )}
                    </tbody>
                </table>
            </div>
        </TenantLayout>
    );
}