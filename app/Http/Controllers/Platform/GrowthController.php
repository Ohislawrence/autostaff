<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\MarketingChannel;
use App\Models\PlatformGoal;
use App\Services\Platform\GrowthService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GrowthController extends Controller
{
    public function __construct(protected GrowthService $growth) {}

    // ---------- Marketing ----------

    public function marketing()
    {
        return Inertia::render('Platform/Marketing', [
            'marketing' => $this->growth->getMarketingStats(),
            'channelTypes' => $this->growth->channelTypes(),
            'metricNames' => $this->growth->metricNames(),
        ]);
    }

    public function storeChannel(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string'],
            'goal' => ['nullable', 'string'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'string'],
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        MarketingChannel::create($data);

        return back()->with('success', 'Marketing channel added.');
    }

    public function updateChannel(Request $request, MarketingChannel $channel)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string'],
            'goal' => ['nullable', 'string'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'string'],
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $channel->update($data);

        return back()->with('success', 'Marketing channel updated.');
    }

    public function destroyChannel(MarketingChannel $channel)
    {
        $channel->delete();

        return back()->with('success', 'Marketing channel removed.');
    }

    public function storeMetric(Request $request, MarketingChannel $channel)
    {
        $data = $request->validate([
            'metric' => ['required', 'string'],
            'value' => ['required', 'numeric'],
            'recorded_on' => ['required', 'date'],
        ]);

        $channel->metrics()->updateOrCreate(
            ['channel_id' => $channel->id, 'metric' => $data['metric'], 'recorded_on' => $data['recorded_on']],
            ['value' => $data['value']]
        );

        return back()->with('success', 'Metric logged.');
    }

    // ---------- Goals / Progress ----------

    public function goals()
    {
        return Inertia::render('Platform/Goals', [
            'goals' => $this->growth->getGoals(),
            'metricKeys' => $this->growth->metricKeys(),
        ]);
    }

    public function storeGoal(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'metric_key' => ['required', 'string'],
            'target' => ['required', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string'],
            'period' => ['required', 'string'],
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date'],
            'color' => ['nullable', 'string'],
        ]);

        PlatformGoal::create($data);

        return back()->with('success', 'Goal added.');
    }

    public function updateGoal(Request $request, PlatformGoal $goal)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'metric_key' => ['required', 'string'],
            'target' => ['required', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string'],
            'period' => ['required', 'string'],
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date'],
            'color' => ['nullable', 'string'],
        ]);

        $goal->update($data);

        return back()->with('success', 'Goal updated.');
    }

    public function destroyGoal(PlatformGoal $goal)
    {
        $goal->delete();

        return back()->with('success', 'Goal removed.');
    }

    // ---------- Achievements ----------

    public function achievements()
    {
        $this->growth->evaluateAchievements(auth()->user());

        return Inertia::render('Platform/Achievements', [
            'achievements' => $this->growth->getAchievements(auth()->user()),
        ]);
    }
}
