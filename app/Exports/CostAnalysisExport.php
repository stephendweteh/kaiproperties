<?php

namespace App\Exports;

use App\Models\Ticket;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class CostAnalysisExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths
{
    protected $tickets;

    public function __construct($tickets = null)
    {
        $this->tickets = $tickets;
    }

    /**
     * Retrieve the collection of tickets grouped by property and category
     */
    public function collection()
    {
        $tickets = $this->tickets ?? Ticket::all();

        $rows = collect();

        // Add summary section
        $totalBudget = $tickets->sum('estimated_cost') ?? 0;
        $totalCost = $tickets->sum('actual_cost') ?? 0;
        $variance = $totalBudget - $totalCost;
        $utilization = $totalBudget > 0 ? ($totalCost / $totalBudget) * 100 : 0;

        $rows->push(['COST ANALYSIS REPORT']);
        $rows->push(['Generated on', Carbon::now()->format('Y-m-d H:i:s')]);
        $rows->push([]);
        $rows->push(['KEY METRICS']);
        $rows->push(['Total Budget', 'GHS ' . number_format($totalBudget, 2)]);
        $rows->push(['Total Cost', 'GHS ' . number_format($totalCost, 2)]);
        $rows->push(['Cost Variance', 'GHS ' . number_format($variance, 2)]);
        $rows->push(['Budget Utilization', round($utilization, 2) . '%']);
        $rows->push([]);

        // Category breakdown
        $categoryTotals = $tickets
            ->groupBy('category_id')
            ->map(function ($group) {
                return [
                    'category' => $group->first()->category->name ?? 'Uncategorized',
                    'budget' => $group->sum('estimated_cost') ?? 0,
                    'actual' => $group->sum('actual_cost') ?? 0,
                    'count' => $group->count(),
                ];
            })
            ->sortByDesc('actual');

        $rows->push(['COST BREAKDOWN BY CATEGORY']);
        $rows->push(['Category', 'Budget', 'Actual Cost', 'Variance', 'Ticket Count']);

        foreach ($categoryTotals as $cat) {
            $variance = $cat['budget'] - $cat['actual'];
            $rows->push([
                $cat['category'],
                'GHS ' . number_format($cat['budget'], 2),
                'GHS ' . number_format($cat['actual'], 2),
                'GHS ' . number_format($variance, 2),
                $cat['count'],
            ]);
        }

        $rows->push([]);

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

        $rows->push(['PROJECT SUMMARY']);
        $rows->push(['Property', 'Budget', 'Actual Cost', 'Utilization', 'Status']);

        foreach ($propertyTotals as $prop) {
            $rows->push([
                $prop['property'],
                'GHS ' . number_format($prop['budget'], 2),
                'GHS ' . number_format($prop['actual'], 2),
                round($prop['utilization'], 2) . '%',
                $prop['status'],
            ]);
        }

        return $rows;
    }

    public function headings(): array
    {
        return [];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            4 => ['font' => ['bold' => true, 'size' => 12]],
            9 => ['font' => ['bold' => true, 'size' => 12]],
            15 => ['font' => ['bold' => true, 'size' => 12]],
            // Header rows for sections
            5 => ['font' => ['bold' => true]],
            6 => ['font' => ['bold' => true]],
            7 => ['font' => ['bold' => true]],
            8 => ['font' => ['bold' => true]],
            10 => ['font' => ['bold' => true]],
            11 => ['font' => ['bold' => true]],
            16 => ['font' => ['bold' => true]],
            17 => ['font' => ['bold' => true]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25,
            'B' => 20,
            'C' => 20,
            'D' => 20,
            'E' => 15,
        ];
    }
}
