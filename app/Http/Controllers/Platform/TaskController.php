<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformTask;
use App\Models\PlatformTaskCompletion;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TaskController extends Controller
{
    public function index()
    {
        $tasks = PlatformTask::with('completions')
            ->orderByRaw("status = 'completed' asc")
            ->orderBy('due_date')
            ->get()
            ->map(fn (PlatformTask $task) => $this->present($task));

        return Inertia::render('Platform/Tasks', [
            'tasks' => $tasks,
            'today' => now()->toDateString(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['required', 'string'],
            'priority' => ['required', 'string'],
            'recurrence' => ['required', 'string'],
            'due_date' => ['nullable', 'date'],
        ]);

        PlatformTask::create($data);

        return back()->with('success', 'Task added.');
    }

    public function toggle(PlatformTask $task)
    {
        $today = now()->toDateString();
        $hasToday = $task->completions()->where('completed_on', $today)->exists();

        if ($hasToday) {
            // Un-complete today: reopen the task and drop today's completion.
            $task->completions()->where('completed_on', $today)->delete();
            $task->update(['status' => 'open', 'completed_at' => null]);
        } else {
            $task->completions()->create(['completed_on' => $today]);
            $task->update(['status' => 'completed', 'completed_at' => now()]);
        }

        $task->update(['streak_count' => $this->recomputeStreak($task)]);

        return back()->with('success', 'Task updated.');
    }

    public function destroy(PlatformTask $task)
    {
        $task->delete();

        return back()->with('success', 'Task removed.');
    }

    /**
     * Count the most recent consecutive days (ending today) with a completion.
     */
    protected function recomputeStreak(PlatformTask $task): int
    {
        $dates = $task->completions()
            ->orderByDesc('completed_on')
            ->pluck('completed_on')
            ->map(fn ($d) => $d->toDateString())
            ->unique()
            ->values();

        $cursor = now()->toDateString();
        $streak = 0;

        while ($dates->contains($cursor)) {
            $streak++;
            $cursor = now()->subDays($streak)->toDateString();
        }

        return $streak;
    }

    protected function present(PlatformTask $task): array
    {
        $doneToday = $task->completions
            ->contains(fn ($c) => $c->completed_on->toDateString() === now()->toDateString());

        return [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'category' => $task->category,
            'priority' => $task->priority,
            'status' => $task->status,
            'recurrence' => $task->recurrence,
            'due_date' => $task->due_date,
            'completed_at' => $task->completed_at,
            'streak_count' => $task->streak_count,
            'done_today' => $doneToday,
        ];
    }
}
