import { Head, Link, useForm } from '@inertiajs/react';
import TenantLayout from '@/Layouts/TenantLayout';
import { useState, useEffect } from 'react';

export default function Create({ services, availabilities, customers }) {
    const { data, setData, post, processing, errors } = useForm({
        customer_id: '',
        service_id: '',
        title: '',
        description: '',
        start_time: '',
        end_time: '',
        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
    });

    const [selectedDate, setSelectedDate] = useState('');
    const [availableSlots, setAvailableSlots] = useState([]);
    const [loadingSlots, setLoadingSlots] = useState(false);
    const [selectedSlot, setSelectedSlot] = useState(null);

    // Fetch available slots when date changes
    useEffect(() => {
        if (selectedDate) {
            setLoadingSlots(true);
            fetch(`/appointments/slots?date=${selectedDate}`)
                .then(res => res.json())
                .then(data => {
                    setAvailableSlots(data.slots || []);
                    setLoadingSlots(false);
                })
                .catch(() => {
                    setAvailableSlots([]);
                    setLoadingSlots(false);
                });
        }
    }, [selectedDate]);

    // Handle slot selection
    const handleSlotSelect = (slot) => {
        setSelectedSlot(slot);
        const startDateTime = `${selectedDate} ${slot.start}:00`;
        const endDateTime = `${selectedDate} ${slot.end}:00`;
        setData({
            ...data,
            start_time: startDateTime,
            end_time: endDateTime,
        });
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/appointments');
    };

    return (
        <TenantLayout header="Schedule Appointment">
            <Head title="New Appointment" />

            <div className="max-w-3xl">
                {/* Info Banner */}
                <div className="mb-6 p-4 bg-gradient-to-r from-blue-50 to-blue-100 border border-blue-200 rounded-xl shadow-sm">
                    <h3 className="text-sm font-semibold text-blue-900 mb-1">📅 Scheduling an Appointment</h3>
                    <p className="text-xs text-blue-700">
                        Schedule appointments with your customers. Select a date to see available time slots based on your business hours.
                    </p>
                </div>

                <form onSubmit={handleSubmit}>
                    <div className="bg-white/80 backdrop-blur rounded-xl shadow-lg border border-blue-100 p-6 space-y-5">
                        <h2 className="text-lg font-semibold text-gray-900">Appointment Details</h2>

                        {/* Customer Selection */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Customer *
                            </label>
                            <select
                                value={data.customer_id}
                                onChange={(e) => setData('customer_id', e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-shadow"
                                required
                            >
                                <option value="">Select a customer</option>
                                {customers.map((customer) => (
                                    <option key={customer.id} value={customer.id}>
                                        {customer.first_name} {customer.last_name}
                                    </option>
                                ))}
                            </select>
                            {errors.customer_id && (
                                <p className="text-red-500 text-xs mt-1">{errors.customer_id}</p>
                            )}
                        </div>

                        {/* Service Selection (Optional) */}
                        {services.length > 0 && (
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Service (Optional)
                                </label>
                                <select
                                    value={data.service_id}
                                    onChange={(e) => setData('service_id', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-shadow"
                                >
                                    <option value="">None</option>
                                    {services.map((service) => (
                                        <option key={service.id} value={service.id}>
                                            {service.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        )}

                        {/* Title */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Appointment Title *
                            </label>
                            <input
                                type="text"
                                value={data.title}
                                onChange={(e) => setData('title', e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-shadow"
                                placeholder="e.g., Initial Consultation"
                                required
                            />
                            {errors.title && (
                                <p className="text-red-500 text-xs mt-1">{errors.title}</p>
                            )}
                        </div>

                        {/* Description */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Description
                            </label>
                            <textarea
                                value={data.description}
                                onChange={(e) => setData('description', e.target.value)}
                                rows={3}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-shadow"
                                placeholder="Additional notes or details about this appointment"
                            />
                        </div>

                        {/* Date Selection */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Date *
                            </label>
                            <input
                                type="date"
                                value={selectedDate}
                                onChange={(e) => {
                                    setSelectedDate(e.target.value);
                                    setSelectedSlot(null);
                                }}
                                min={new Date().toISOString().split('T')[0]}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-shadow"
                                required
                            />
                            {errors.start_time && (
                                <p className="text-red-500 text-xs mt-1">{errors.start_time}</p>
                            )}
                        </div>

                        {/* Available Time Slots */}
                        {selectedDate && (
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Available Time Slots *
                                </label>
                                {loadingSlots ? (
                                    <div className="p-4 bg-blue-50 rounded-lg text-center">
                                        <p className="text-sm text-blue-600">Loading available slots...</p>
                                    </div>
                                ) : availableSlots.length > 0 ? (
                                    <div className="grid grid-cols-3 md:grid-cols-4 gap-2">
                                        {availableSlots.map((slot, index) => (
                                            <button
                                                key={index}
                                                type="button"
                                                onClick={() => handleSlotSelect(slot)}
                                                className={`px-3 py-2 rounded-lg text-xs font-medium transition-all ${
                                                    selectedSlot?.start === slot.start
                                                        ? 'bg-gradient-to-r from-blue-600 to-blue-700 text-white shadow-md shadow-blue-200'
                                                        : 'bg-white border border-gray-300 text-gray-700 hover:border-blue-400 hover:bg-blue-50'
                                                }`}
                                            >
                                                {slot.label}
                                            </button>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                                        <p className="text-sm text-yellow-700">
                                            ⚠️ No available time slots for this date. Please select another date or update your business hours.
                                        </p>
                                    </div>
                                )}
                            </div>
                        )}

                        {/* Selected Time Display */}
                        {selectedSlot && (
                            <div className="p-4 bg-gradient-to-r from-blue-50 to-blue-100 border border-blue-200 rounded-lg">
                                <p className="text-sm font-medium text-blue-900">
                                    ✓ Selected Time: {new Date(selectedDate).toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' })} at {selectedSlot.label}
                                </p>
                            </div>
                        )}

                        {/* Hidden Timezone Field */}
                        <input type="hidden" value={data.timezone} />
                    </div>

                    {/* Submit Buttons */}
                    <div className="flex items-center gap-3 mt-6">
                        <button
                            type="submit"
                            disabled={processing || !selectedSlot}
                            className="px-5 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl font-medium hover:from-blue-700 hover:to-blue-800 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-md shadow-blue-200 text-sm"
                        >
                            {processing ? 'Scheduling...' : 'Schedule Appointment'}
                        </button>
                        <Link
                            href="/appointments"
                            className="px-5 py-2.5 bg-gray-100 text-gray-700 rounded-xl font-medium hover:bg-gray-200 transition-colors text-sm"
                        >
                            Cancel
                        </Link>
                    </div>
                </form>

                {/* Business Hours Info */}
                {availabilities.length > 0 && (
                    <div className="mt-6 bg-white/80 backdrop-blur rounded-xl shadow-sm border border-blue-100 p-5">
                        <h3 className="font-semibold text-gray-900 mb-3 text-sm">📋 Business Hours</h3>
                        <div className="space-y-2">
                            {availabilities.map((avail) => (
                                <div key={avail.id} className="flex items-center justify-between text-xs">
                                    <span className="text-gray-700 capitalize font-medium">{avail.day_of_week}</span>
                                    <span className="text-gray-500">
                                        {avail.start_time} - {avail.end_time}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {availabilities.length === 0 && (
                    <div className="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-xl">
                        <p className="text-sm text-yellow-700">
                            ⚠️ <strong>No business hours configured.</strong> Please set up your availability in Settings before scheduling appointments.
                        </p>
                    </div>
                )}
            </div>
        </TenantLayout>
    );
}
