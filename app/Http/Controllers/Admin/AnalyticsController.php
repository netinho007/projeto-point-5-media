<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\SiteVisit;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Throwable;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $selectedMonth = $this->resolveMonth($request->string('month')->toString());
        $start = $selectedMonth->copy()->startOfMonth();
        $end = $selectedMonth->copy()->endOfMonth();

        $uniqueVisitors = SiteVisit::query()
            ->whereBetween('created_at', [$start, $end])
            ->distinct()
            ->count('visitor_token');

        $pageViews = SiteVisit::query()
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $resumesSubmitted = Application::query()
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $conversionRate = $uniqueVisitors > 0
            ? round(($resumesSubmitted / $uniqueVisitors) * 100, 1)
            : 0;

        $previousStart = $start->copy()->subMonth()->startOfMonth();
        $previousEnd = $previousStart->copy()->endOfMonth();

        $previousVisitors = SiteVisit::query()
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->distinct()
            ->count('visitor_token');

        $previousResumes = Application::query()
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->count();

        $visitorDelta = $this->percentDelta($uniqueVisitors, $previousVisitors);
        $resumeDelta = $this->percentDelta($resumesSubmitted, $previousResumes);

        $visitorsByDay = SiteVisit::query()
            ->selectRaw('DATE(created_at) as day, COUNT(DISTINCT visitor_token) as total')
            ->whereBetween('created_at', [$start, $end])
            ->groupByRaw('DATE(created_at)')
            ->pluck('total', 'day');

        $resumesByDay = Application::query()
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->whereBetween('created_at', [$start, $end])
            ->groupByRaw('DATE(created_at)')
            ->pluck('total', 'day');

        $dailySeries = [];
        $seriesEnd = $selectedMonth->isSameMonth(now()) ? now()->startOfDay() : $end->copy()->startOfDay();
        foreach (CarbonPeriod::create($start->copy()->startOfDay(), $seriesEnd) as $date) {
            $key = $date->format('Y-m-d');
            $dailySeries[] = [
                'label' => $date->format('d/m'),
                'visitors' => (int) ($visitorsByDay[$key] ?? 0),
                'resumes' => (int) ($resumesByDay[$key] ?? 0),
            ];
        }

        $twelveMonthStart = $selectedMonth->copy()->startOfMonth()->subMonths(11);
        $twelveMonthEnd = $selectedMonth->copy()->endOfMonth();

        $visitorsByMonth = SiteVisit::query()
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month_key, COUNT(DISTINCT visitor_token) as total")
            ->whereBetween('created_at', [$twelveMonthStart, $twelveMonthEnd])
            ->groupByRaw("DATE_FORMAT(created_at, '%Y-%m')")
            ->pluck('total', 'month_key');

        $resumesByMonth = Application::query()
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month_key, COUNT(*) as total")
            ->whereBetween('created_at', [$twelveMonthStart, $twelveMonthEnd])
            ->groupByRaw("DATE_FORMAT(created_at, '%Y-%m')")
            ->pluck('total', 'month_key');

        $monthlySeries = [];
        for ($i = 0; $i < 12; $i++) {
            $month = $twelveMonthStart->copy()->addMonths($i);
            $key = $month->format('Y-m');
            $monthlySeries[] = [
                'label' => $month->copy()->locale('pt_BR')->translatedFormat('M/y'),
                'visitors' => (int) ($visitorsByMonth[$key] ?? 0),
                'resumes' => (int) ($resumesByMonth[$key] ?? 0),
            ];
        }

        return view('admin.analytics.index', [
            'selectedMonth' => $selectedMonth,
            'selectedMonthValue' => $selectedMonth->format('Y-m'),
            'selectedMonthLabel' => $selectedMonth->copy()->locale('pt_BR')->translatedFormat('F \d\e Y'),
            'previousMonthValue' => $selectedMonth->copy()->subMonth()->format('Y-m'),
            'nextMonthValue' => $selectedMonth->copy()->addMonth()->format('Y-m'),
            'canGoNext' => $selectedMonth->copy()->addMonth()->startOfMonth()->lte(now()->startOfMonth()),
            'uniqueVisitors' => $uniqueVisitors,
            'pageViews' => $pageViews,
            'resumesSubmitted' => $resumesSubmitted,
            'conversionRate' => $conversionRate,
            'visitorDelta' => $visitorDelta,
            'resumeDelta' => $resumeDelta,
            'dailySeries' => $dailySeries,
            'monthlySeries' => $monthlySeries,
        ]);
    }

    private function resolveMonth(string $value): Carbon
    {
        if (preg_match('/^\d{4}-\d{2}$/', $value)) {
            try {
                $month = Carbon::createFromFormat('Y-m', $value)->startOfMonth();

                if ($month->lte(now()->startOfMonth())) {
                    return $month;
                }
            } catch (Throwable $exception) {
                // Fallback abaixo.
            }
        }

        return now()->startOfMonth();
    }

    private function percentDelta(int $current, int $previous): ?float
    {
        if ($previous === 0) {
            return $current === 0 ? 0.0 : null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
