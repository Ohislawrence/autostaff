<?php

namespace App\Http\Controllers;

use App\Ai\Tools\ToolExecutor;
use App\Models\ToolExecution;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ApprovalController extends Controller
{
    public function __construct(protected ToolExecutor $toolExecutor) {}

    /**
     * List pending human approvals (tool executions awaiting confirmation).
     */
    public function index()
    {
        $organization = current_org();

        $pending = ToolExecution::where('organization_id', $organization->id)
            ->where('status', 'pending')
            ->where('requires_confirmation', true)
            ->with(['tool', 'conversation.customer', 'aiEmployee'])
            ->latest()
            ->get()
            ->map(fn ($e) => [
                'id' => $e->id,
                'tool_name' => $e->tool?->name ?? 'Unknown tool',
                'tool_identifier' => $e->tool?->identifier,
                'input_parameters' => $e->input_parameters,
                'conversation_id' => $e->conversation_id,
                'customer' => $e->conversation?->customer ? [
                    'name' => trim(($e->conversation->customer->first_name ?? '') . ' ' . ($e->conversation->customer->last_name ?? '')),
                    'email' => $e->conversation->customer->email,
                ] : null,
                'ai_employee' => $e->aiEmployee?->name,
                'created_at' => $e->created_at?->toISOString(),
            ])
            ->values();

        return Inertia::render('Approvals/Index', [
            'pending' => $pending,
        ]);
    }

    /**
     * Approve and execute a pending tool execution.
     */
    public function approve(ToolExecution $toolExecution)
    {
        $orgId = current_org_id();
        abort_if($toolExecution->organization_id !== $orgId, 403);

        $result = $this->toolExecutor->confirm($toolExecution->id, auth()->id());

        return back()->with(
            $result['status'] === 'success' ? 'success' : 'error',
            $result['message'] ?? ($result['error'] ?? 'Approval processed.')
        );
    }

    /**
     * Deny a pending tool execution (no execution happens).
     */
    public function deny(ToolExecution $toolExecution)
    {
        $orgId = current_org_id();
        abort_if($toolExecution->organization_id !== $orgId, 403);
        abort_if($toolExecution->status !== 'pending', 409, 'This approval is no longer pending.');

        $toolExecution->update([
            'status' => 'denied',
            'was_confirmed' => false,
        ]);

        return back()->with('success', 'Approval denied.');
    }
}