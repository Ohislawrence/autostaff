import { Head, router, useForm, usePage } from '@inertiajs/react';
import TenantLayout from '@/Layouts/TenantLayout';

export default function Scheduling({ availabilities, services, days }) {
    const { flash } = usePage().props;

    const availabilityForm = useForm({
        day_of_week: 'monday',
        start_time: '09:00',
        end_time: '17:00',
        slot_duration_minutes: 30,
    });

    const serviceForm = useForm({
        name: '',
        description: '',
        duration_minutes: 30,
        price: '',
    });

    const submitAvailability = (e) => {
        e.preventDefault();
        availabilityForm.post('/settings/availability', { onSuccess: () => availabilityForm.reset() });
    };

    const submitService = (e) => {
        e.preventDefault();
        serviceForm.post('/settings/services', { onSuccess: () => serviceForm.reset() });
    };

    const capitalize = (s) => s.charAt(0).toUpperCase() + s.slice(1);

    return (
        <TenantLayout header="Scheduling">
            <Head title="Scheduling" />

            {flash?.success && <div className="mb-4 p-4 bg-green-50 text-green-700 rounded-lg text-sm">{flash.success}</div>}
            {flash?.error && <div className="mb-4 p-4 bg-red-50 text-red-700 rounded-lg text-sm">{flash.error}</div>}

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 className="text-lg font-semibold text-gray-900 mb-1">Business Hours</h2>
                    <p className="text-xs text-gray-500 mb-4">Define when appointments can be booked and the slot length.</p>

                    <form onSubmit={submitAvailability} className="grid grid-cols-2 gap-3 mb-6">
                        <div>
                            <label className="block text-xs font-medium text-gray-600 mb-1">Day</label>
                            <select
                                value={availabilityForm.data.day_of_week}
                                onChange={(e) => availabilityForm.setData('day_of_week', e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                            >
                                {days.map((d) => <option key={d} value={d}>{capitalize(d)}</option>)}
                            </select>
                        </div>
                        <div>
                            <label className="block text-xs font-medium text-gray-600 mb-1">Slot length (min)</label>
                            <input
                                type="number"
                                min="5"
                                step="5"
                                value={availabilityForm.data.slot_duration_minutes}
                                onChange={(e) => availabilityForm.setData('slot_duration_minutes', e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                            />
                        </div>
                        <div>
                            <label className="block text-xs font-medium text-gray-600 mb-1">Start</label>
                            <input
                                type="time"
                                value={availabilityForm.data.start_time}
                                onChange={(e) => availabilityForm.setData('start_time', e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                                required
                            />
                        </div>
                        <div>
                            <label className="block text-xs font-medium text-gray-600 mb-1">End</label>
                            <input
                                type="time"
                                value={availabilityForm.data.end_time}
                                onChange={(e) => availabilityForm.setData('end_time', e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                                required
                            />
                        </div>
                        <button
                            type="submit"
                            disabled={availabilityForm.processing}
                            className="col-span-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 disabled:opacity-50"
                        >
                            Add Hours
                        </button>
                    </form>

                    {availabilities.length === 0 ? (
                        <p className="text-sm text-gray-400">No business hours configured.</p>
                    ) : (
                        <ul className="divide-y divide-gray-100">
                            {availabilities.map((a) => (
                                <li key={a.id} className="flex items-center justify-between py-2 text-sm">
                                    <span className="capitalize text-gray-700">{a.day_of_week}</span>
                                    <span className="text-gray-500">{a.start_time} – {a.end_time} ({a.slot_duration_minutes}m)</span>
                                    <button
                                        onClick={() => { if (confirm(`Remove ${a.day_of_week} hours?`)) router.delete(`/settings/availability/${a.id}`); }}
                                        className="text-xs text-red-600 hover:text-red-700"
                                    >
                                        Remove
                                    </button>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
                <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 className="text-lg font-semibold text-gray-900 mb-1">Services</h2>
                    <p className="text-xs text-gray-500 mb-4">Offerings customers can book, with default duration and price.</p>

                    <form onSubmit={submitService} className="grid grid-cols-2 gap-3 mb-6">
                        <div className="col-span-2">
                            <label className="block text-xs font-medium text-gray-600 mb-1">Name</label>
                            <input
                                type="text"
                                value={serviceForm.data.name}
                                onChange={(e) => serviceForm.setData('name', e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                                required
                            />
                        </div>
                        <div className="col-span-2">
                            <label className="block text-xs font-medium text-gray-600 mb-1">Description</label>
                            <input
                                type="text"
                                value={serviceForm.data.description}
                                onChange={(e) => serviceForm.setData('description', e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                            />
                        </div>
                        <div>
                            <label className="block text-xs font-medium text-gray-600 mb-1">Duration (min)</label>
                            <input
                                type="number"
                                min="5"
                                step="5"
                                value={serviceForm.data.duration_minutes}
                                onChange={(e) => serviceForm.setData('duration_minutes', e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                            />
                        </div>
                        <div>
                            <label className="block text-xs font-medium text-gray-600 mb-1">Price</label>
                            <input
                                type="number"
                                min="0"
                                step="0.01"
                                value={serviceForm.data.price}
                                onChange={(e) => serviceForm.setData('price', e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                            />
                        </div>
                        <button
                            type="submit"
                            disabled={serviceForm.processing}
                            className="col-span-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 disabled:opacity-50"
                        >
                            Add Service
                        </button>
                    </form>

                    {services.length === 0 ? (
                        <p className="text-sm text-gray-400">No services configured.</p>
                    ) : (
                        <ul className="divide-y divide-gray-100">
                            {services.map((s) => (
                                <li key={s.id} className="flex items-center justify-between py-2 text-sm">
                                    <span className="text-gray-700">{s.name}</span>
                                    <span className="text-gray-500">{s.duration_minutes}m{s.price ? ` · $${s.price}` : ''}</span>
                                    <button
                                        onClick={() => { if (confirm(`Remove ${s.name}?`)) router.delete(`/settings/services/${s.id}`); }}
                                        className="text-xs text-red-600 hover:text-red-700"
                                    >
                                        Remove
                                    </button>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            </div>
        </TenantLayout>
    );
}
