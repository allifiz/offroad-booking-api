<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'event' => ['nullable', Rule::in(['created', 'updated', 'deleted'])],
            'actor' => ['nullable', 'string', 'max:100'],
            'subject_type' => ['nullable', 'string', 'max:255'],
            'subject_id' => ['nullable', 'integer', 'min:1'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $logs = AuditLog::query()
            ->with('actor:id,name,email,role')
            ->when($validated['event'] ?? null, fn ($query, $value) => $query->where('event', $value))
            ->when($validated['actor'] ?? null, function ($query, string $actor): void {
                $query->whereHas('actor', fn ($query) => $query
                    ->where('name', 'like', "%{$actor}%")
                    ->orWhere('email', 'like', "%{$actor}%"));
            })
            ->when($validated['subject_type'] ?? null, fn ($query, $value) => $query->where('subject_type', 'like', "%{$value}%"))
            ->when($validated['subject_id'] ?? null, fn ($query, $value) => $query->where('subject_id', $value))
            ->when($validated['date_from'] ?? null, fn ($query, $value) => $query->whereDate('created_at', '>=', $value))
            ->when($validated['date_to'] ?? null, fn ($query, $value) => $query->whereDate('created_at', '<=', $value))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.audit-logs.index', compact('logs'));
    }

    public function show(AuditLog $auditLog): View
    {
        $auditLog->load('actor:id,name,email,role');

        return view('admin.audit-logs.show', compact('auditLog'));
    }
}
