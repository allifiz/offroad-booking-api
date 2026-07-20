<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $to = isset($validated['date_to'])
            ? CarbonImmutable::parse($validated['date_to'])
            : CarbonImmutable::today();
        $from = isset($validated['date_from'])
            ? CarbonImmutable::parse($validated['date_from'])
            : $to->subDays(29);

        if ($from->diffInDays($to) > 366) {
            return back()->withErrors(['date_from' => 'Rentang laporan maksimal 366 hari.']);
        }

        return view('admin.reports.index', compact('from', 'to'));
    }
}
