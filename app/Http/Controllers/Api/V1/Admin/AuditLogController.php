<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event' => ['nullable', Rule::in(['created', 'updated', 'deleted'])],
            'actor_id' => ['nullable', 'integer', 'exists:users,id'],
            'subject_type' => ['nullable', 'string', 'max:255'],
            'subject_id' => ['nullable', 'integer', 'min:1'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $logs = AuditLog::query()
            ->with('actor:id,name,email,role')
            ->when($validated['event'] ?? null, fn ($query, $value) => $query->where('event', $value))
            ->when($validated['actor_id'] ?? null, fn ($query, $value) => $query->where('actor_id', $value))
            ->when($validated['subject_type'] ?? null, fn ($query, $value) => $query->where('subject_type', $value))
            ->when($validated['subject_id'] ?? null, fn ($query, $value) => $query->where('subject_id', $value))
            ->when($validated['date_from'] ?? null, fn ($query, $value) => $query->whereDate('created_at', '>=', $value))
            ->when($validated['date_to'] ?? null, fn ($query, $value) => $query->whereDate('created_at', '<=', $value))
            ->latest()
            ->paginate($validated['per_page'] ?? 20)
            ->withQueryString();

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    public function show(AuditLog $auditLog): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $auditLog->load('actor:id,name,email,role'),
        ]);
    }
}
