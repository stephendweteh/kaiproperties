<?php

namespace App\Exports;

use Illuminate\Support\Facades\View;

class CostAnalysisPdfExport
{
    protected $tickets;

    public function __construct($tickets = null)
    {
        $this->tickets = $tickets;
    }

    public function generate()
    {
        $data = $this->prepareData();
        
        $html = View::make('exports.cost-analysis-pdf', $data)->render();
        
        $pdf = \PDF::loadHTML($html);
        $pdf->setPaper('A4', 'portrait');
        
        return $pdf;
    }

    protected function prepareData()
    {
        $tickets = $this->tickets;
        
        $totalBudget = $tickets->sum('estimated_cost') ?? 0;
        $totalCost = $tickets->sum('actual_cost') ?? 0;
        $variance = $totalBudget - $totalCost;
        $utilization = $totalBudget > 0 ? ($totalCost / $totalBudget) * 100 : 0;

        // Category breakdown
        $categoryTotals = $tickets
            ->groupBy('maintenance_category_id')
            ->map(function ($group) {
                return [
                    'category' => $group->first()->category->name ?? 'Uncategorized',
                    'budget' => $group->sum('estimated_cost') ?? 0,
                    'actual' => $group->sum('actual_cost') ?? 0,
                    'count' => $group->count(),
                ];
            })
            ->sortByDesc('actual');

        // Project summary
        $propertyTotals = $tickets
            ->groupBy('property_id')
            ->map(function ($group) {
                $budget = $group->sum('estimated_cost') ?? 0;
                $actual = $group->sum('actual_cost') ?? 0;
                $util = $budget > 0 ? ($actual / $budget) * 100 : 0;

                return [
                    'property' => $group->first()->property->name ?? 'Uncategorized',
                    'budget' => $budget,
                    'actual' => $actual,
                    'utilization' => $util,
                    'status' => $util > 100 ? 'Over Budget' : ($util > 80 ? 'At Risk' : 'On Track'),
                ];
            })
            ->sortByDesc('actual');

        return [
            'generatedAt' => now()->format('Y-m-d H:i:s'),
            'totalBudget' => $totalBudget,
            'totalCost' => $totalCost,
            'variance' => $variance,
            'utilization' => $utilization,
            'categories' => $categoryTotals,
            'properties' => $propertyTotals,
        ];
    }
}
