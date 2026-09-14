import PlatformLayout from '@/Layouts/PlatformLayout';

const fmt = (n) => Number(n || 0).toLocaleString(undefined, { maximumFractionDigits: 2 });

export default function Achievements({ achievements }) {
    const unlocked = achievements.filter((a) => a.unlocked);
    const totalPoints = achievements.filter((a) => a.unlocked).reduce((sum, a) => sum + a.points, 0);

    return (
        <PlatformLayout title="Achievements">
            <div className="flex items-center justify-between mb-6">
                <h1 className="text-2xl font-bold text-gray-900">🏆 Achievements</h1>
                <div className="text-sm text-gray-500">
                    <span className="font-bold text-amber-600">{unlocked.length}</span> / {achievements.length} unlocked ·{' '}
                    <span className="font-bold text-amber-600">{totalPoints}</span> pts
                </div>
            </div>

            <div className="grid grid-cols-3 gap-4">
                {achievements.map((a) => (
                    <div key={a.key} className={`bg-white rounded-xl border p-5 ${a.unlocked ? 'border-amber-300 shadow-sm' : 'border-gray-200 opacity-80'}`}>
                        <div className="flex items-start justify-between">
                            <div className={`text-4xl ${a.unlocked ? '' : 'grayscale'}`}>{a.icon}</div>
                            <span className={`px-2 py-0.5 rounded-full text-xs font-bold ${a.unlocked ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-500'}`}>{a.points} pts</span>
                        </div>
                        <h3 className="font-semibold text-gray-900 mt-3">{a.name}</h3>
                        <p className="text-xs text-gray-500 mt-1 min-h-[32px]">{a.description}</p>
                        <div className="mt-3">
                            <div className="h-2 bg-gray-100 rounded-full overflow-hidden">
                                <div className={`h-full rounded-full ${a.unlocked ? 'bg-amber-500' : 'bg-blue-400'}`} style={{ width: `${a.percent}%` }} />
                            </div>
                            <div className="flex justify-between mt-1 text-xs text-gray-400">
                                <span>{fmt(a.progress)} / {fmt(a.target)}</span>
                                <span>{a.percent}%</span>
                            </div>
                        </div>
                        {a.unlocked && a.unlocked_at && (
                            <p className="text-xs text-green-600 mt-2">Unlocked {new Date(a.unlocked_at).toLocaleDateString()}</p>
                        )}
                    </div>
                ))}
                {achievements.length === 0 && <p className="col-span-3 text-center text-gray-400 py-10">No achievements yet.</p>}
            </div>
        </PlatformLayout>
    );
}
